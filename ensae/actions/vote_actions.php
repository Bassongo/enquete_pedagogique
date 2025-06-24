<?php
// Inclure la configuration de la base de données
require_once(__DIR__ . '/../config/database.php');

// Fonction pour envoyer une réponse JSON
function sendResponse($success, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

// Vérifier que l'utilisateur est connecté (version AJAX)
if (!isLoggedIn()) {
    sendResponse(false, 'Utilisateur non connecté');
}

// Vérifier que c'est une requête AJAX (version plus flexible)
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
$hasAction = isset($_POST['action']) && !empty($_POST['action']);

// Si ce n'est pas AJAX mais qu'il y a une action, on accepte quand même
// (certains navigateurs ne passent pas correctement l'en-tête AJAX)
if (!$isAjax && !$hasAction) {
    http_response_code(403);
    die('Accès direct non autorisé');
}

// Récupérer l'action demandée
$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_candidates':
            // Récupérer les candidats pour une session de vote
            $sessionId = (int)($_POST['session_id'] ?? 0);
            
            if (!$sessionId) {
                sendResponse(false, 'ID de session invalide');
            }
            
            // Récupérer les informations de la session
            $stmt = $pdo->prepare("
                SELECT 
                    vs.*,
                    et.name as election_type,
                    c.name as club_name
                FROM vote_sessions vs
                LEFT JOIN election_types et ON vs.election_type_id = et.id
                LEFT JOIN clubs c ON vs.club_id = c.id
                WHERE vs.id = ? AND vs.is_active = 1
            ");
            $stmt->execute([$sessionId]);
            $session = $stmt->fetch();
            
            if (!$session) {
                sendResponse(false, 'Session de vote non trouvée');
            }
            
            // Vérifier que la session est active
            $now = new DateTime();
            $start = new DateTime($session['start_time']);
            $end = new DateTime($session['end_time']);
            
            if ($now < $start || $now > $end) {
                sendResponse(false, 'Cette session de vote n\'est pas active');
            }
            
            // Vérifier que l'utilisateur n'a pas déjà voté
            $stmt = $pdo->prepare("SELECT COUNT(*) as has_voted FROM votes WHERE voter_id = ? AND vote_session_id = ?");
            $stmt->execute([$_SESSION['user_id'], $sessionId]);
            $hasVoted = $stmt->fetch()['has_voted'] > 0;
            
            if ($hasVoted) {
                sendResponse(false, 'Vous avez déjà voté pour cette élection');
            }
            
            // Récupérer les candidats approuvés
            $stmt = $pdo->prepare("
                SELECT 
                    c.id,
                    c.programme,
                    c.photo_url,
                    u.username as candidate_name,
                    u.classe as candidate_class,
                    p.name as position_name,
                    cl.name as club_name
                FROM candidatures c
                JOIN users u ON c.user_id = u.id
                LEFT JOIN positions p ON c.position_id = p.id
                LEFT JOIN clubs cl ON c.club_id = cl.id
                WHERE c.election_type_id = ? 
                AND c.status = 'approved'
                AND (? IS NULL OR c.club_id = ?)
                ORDER BY p.name, u.username
            ");
            $stmt->execute([$session['election_type_id'], $session['club_id'], $session['club_id']]);
            $candidates = $stmt->fetchAll();
            
            sendResponse(true, 'Candidats récupérés', [
                'candidates' => $candidates,
                'session_info' => [
                    'election_type' => $session['election_type'],
                    'club_name' => $session['club_name']
                ]
            ]);
            break;
            
        case 'submit_vote':
            // Soumettre un vote
            $sessionId = (int)($_POST['session_id'] ?? 0);
            $candidateId = (int)($_POST['candidate_id'] ?? 0);
            
            if (!$sessionId || !$candidateId) {
                sendResponse(false, 'Paramètres invalides');
            }
            
            // Vérifier que la session est active
            $stmt = $pdo->prepare("
                SELECT * FROM vote_sessions 
                WHERE id = ? AND is_active = 1 
                AND start_time <= NOW() AND end_time >= NOW()
            ");
            $stmt->execute([$sessionId]);
            $session = $stmt->fetch();
            
            if (!$session) {
                sendResponse(false, 'Session de vote non active');
            }
            
            // Vérifier que l'utilisateur n'a pas déjà voté
            $stmt = $pdo->prepare("SELECT COUNT(*) as has_voted FROM votes WHERE voter_id = ? AND vote_session_id = ?");
            $stmt->execute([$_SESSION['user_id'], $sessionId]);
            $hasVoted = $stmt->fetch()['has_voted'] > 0;
            
            if ($hasVoted) {
                sendResponse(false, 'Vous avez déjà voté pour cette élection');
            }
            
            // Vérifier que le candidat existe et est approuvé
            $stmt = $pdo->prepare("
                SELECT c.*, u.username 
                FROM candidatures c 
                JOIN users u ON c.user_id = u.id
                WHERE c.id = ? AND c.status = 'approved'
            ");
            $stmt->execute([$candidateId]);
            $candidate = $stmt->fetch();
            
            if (!$candidate) {
                sendResponse(false, 'Candidat non trouvé ou non approuvé');
            }
            
            // Insérer le vote
            $stmt = $pdo->prepare("
                INSERT INTO votes (voter_id, candidature_id, election_type_id, vote_session_id)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $candidateId,
                $session['election_type_id'],
                $sessionId
            ]);
            
            // Logger l'activité
            logActivity($_SESSION['user_id'], 'vote_submitted', "Vote soumis pour la session $sessionId");
            
            sendResponse(true, 'Vote enregistré avec succès');
            break;
            
        case 'get_vote_history':
            // Récupérer l'historique des votes de l'utilisateur
            $stmt = $pdo->prepare("
                SELECT 
                    v.created_at,
                    vs.start_time,
                    vs.end_time,
                    et.name as election_type,
                    c.name as club_name,
                    u.username as candidate_name,
                    p.name as position_name
                FROM votes v
                JOIN vote_sessions vs ON v.vote_session_id = vs.id
                JOIN election_types et ON v.election_type_id = et.id
                JOIN candidatures cand ON v.candidature_id = cand.id
                JOIN users u ON cand.user_id = u.id
                LEFT JOIN positions p ON cand.position_id = p.id
                LEFT JOIN clubs c ON vs.club_id = c.id
                WHERE v.voter_id = ?
                ORDER BY v.created_at DESC
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $votes = $stmt->fetchAll();
            
            sendResponse(true, 'Historique récupéré', $votes);
            break;
            
        default:
            sendResponse(false, 'Action non reconnue');
            break;
    }
    
} catch (PDOException $e) {
    error_log("Erreur dans vote_actions.php: " . $e->getMessage());
    sendResponse(false, 'Erreur de base de données: ' . $e->getMessage());
} catch (Exception $e) {
    error_log("Erreur générale dans vote_actions.php: " . $e->getMessage());
    sendResponse(false, 'Erreur interne du serveur: ' . $e->getMessage());
}
?>