<?php
// Inclure la configuration de la base de données
require_once('../config/database.php');

// Vérifier que l'utilisateur est connecté et est admin ou membre de comité
requireLogin();
if (!hasRole('admin') && !hasRole('committee')) {
    http_response_code(403);
    die('Accès non autorisé');
}

// Vérifier que c'est une requête AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die('Accès direct non autorisé');
}

// Récupérer l'action demandée
$action = $_POST['action'] ?? '';

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

// Vérifier si l'utilisateur peut gérer un type d'élection
function userCanManageType($userId, $typeId) {
    global $pdo;
    if (hasRole('admin')) {
        return true;
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM committee_election_types WHERE user_id = ? AND election_type_id = ?");
    $stmt->execute([$userId, $typeId]);
    return $stmt->fetchColumn() > 0;
}

// Vérifier les droits sur une session à partir de son identifiant
function ensureSessionPermission($sessionId, $table) {
    global $pdo;
    $table = $table === 'vote_sessions' ? 'vote_sessions' : 'candidature_sessions';
    $stmt = $pdo->prepare("SELECT election_type_id FROM {$table} WHERE id = ?");
    $stmt->execute([$sessionId]);
    $typeId = $stmt->fetchColumn();
    if (!$typeId) {
        sendResponse(false, 'Session non trouvée');
    }
    if (!userCanManageType($_SESSION['user_id'], $typeId)) {
        sendResponse(false, 'Droits insuffisants pour ce type d\'élection');
    }
}

try {
    switch ($action) {
        case 'start_vote_session':
            // Démarrer une session de vote
            $electionTypeId = (int)($_POST['election_type_id'] ?? 0);
            $clubId = !empty($_POST['club_id']) ? (int)$_POST['club_id'] : null;
            $startTime = $_POST['start_time'] ?? '';
            $endTime = $_POST['end_time'] ?? '';

            // Validation des données
            if (!$electionTypeId || !$startTime || !$endTime) {
                sendResponse(false, 'Toutes les informations sont requises');
            }

            if (!userCanManageType($_SESSION['user_id'], $electionTypeId)) {
                sendResponse(false, 'Droits insuffisants pour ce type d\'élection');
            }
            
            // Vérifier que les dates sont valides
            $startDateTime = new DateTime($startTime);
            $endDateTime = new DateTime($endTime);
            $now = new DateTime();
            
            if ($startDateTime <= $now) {
                sendResponse(false, 'La date de début doit être dans le futur');
            }
            
            if ($endDateTime <= $startDateTime) {
                sendResponse(false, 'La date de fin doit être après la date de début');
            }
            
            // Vérifier qu'il n'y a pas de conflit avec une autre session
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as conflict_count
                FROM vote_sessions 
                WHERE election_type_id = ? 
                AND (? IS NULL OR club_id = ?)
                AND is_active = 1
                AND (
                    (start_time <= ? AND end_time >= ?) OR
                    (start_time <= ? AND end_time >= ?) OR
                    (start_time >= ? AND end_time <= ?)
                )
            ");
            $stmt->execute([$electionTypeId, $clubId, $clubId, $startTime, $startTime, $endTime, $endTime, $startTime, $endTime]);
            $conflict = $stmt->fetch();
            
            if ($conflict['conflict_count'] > 0) {
                sendResponse(false, 'Il existe déjà une session de vote pour cette période');
            }
            
            // Insérer la nouvelle session de vote
            $stmt = $pdo->prepare("
                INSERT INTO vote_sessions (election_type_id, club_id, start_time, end_time, is_active, created_by)
                VALUES (?, ?, ?, ?, 1, ?)
            ");
            $stmt->execute([$electionTypeId, $clubId, $startTime, $endTime, $_SESSION['user_id']]);
            
            // Logger l'activité
            logActivity($_SESSION['user_id'], 'start_vote_session', "Session de vote créée pour le type $electionTypeId");
            
            sendResponse(true, 'Session de vote créée avec succès', ['session_id' => $pdo->lastInsertId()]);
            break;
            
        case 'start_candidature_session':
            // Démarrer une session de candidature
            $electionTypeId = (int)($_POST['election_type_id'] ?? 0);
            $clubId = !empty($_POST['club_id']) ? (int)$_POST['club_id'] : null;
            $startTime = $_POST['start_time'] ?? '';
            $endTime = $_POST['end_time'] ?? '';

            // Validation des données
            if (!$electionTypeId || !$startTime || !$endTime) {
                sendResponse(false, 'Toutes les informations sont requises');
            }

            if (!userCanManageType($_SESSION['user_id'], $electionTypeId)) {
                sendResponse(false, 'Droits insuffisants pour ce type d\'élection');
            }
            
            // Vérifier que les dates sont valides
            $startDateTime = new DateTime($startTime);
            $endDateTime = new DateTime($endTime);
            $now = new DateTime();
            
            if ($startDateTime <= $now) {
                sendResponse(false, 'La date de début doit être dans le futur');
            }
            
            if ($endDateTime <= $startDateTime) {
                sendResponse(false, 'La date de fin doit être après la date de début');
            }
            
            // Vérifier qu'il n'y a pas de conflit avec une autre session
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as conflict_count
                FROM candidature_sessions 
                WHERE election_type_id = ? 
                AND (? IS NULL OR club_id = ?)
                AND is_active = 1
                AND (
                    (start_time <= ? AND end_time >= ?) OR
                    (start_time <= ? AND end_time >= ?) OR
                    (start_time >= ? AND end_time <= ?)
                )
            ");
            $stmt->execute([$electionTypeId, $clubId, $clubId, $startTime, $startTime, $endTime, $endTime, $startTime, $endTime]);
            $conflict = $stmt->fetch();
            
            if ($conflict['conflict_count'] > 0) {
                sendResponse(false, 'Il existe déjà une session de candidature pour cette période');
            }
            
            // Insérer la nouvelle session de candidature
            $stmt = $pdo->prepare("
                INSERT INTO candidature_sessions (election_type_id, club_id, start_time, end_time, is_active, created_by)
                VALUES (?, ?, ?, ?, 1, ?)
            ");
            $stmt->execute([$electionTypeId, $clubId, $startTime, $endTime, $_SESSION['user_id']]);
            
            // Logger l'activité
            logActivity($_SESSION['user_id'], 'start_candidature_session', "Session de candidature créée pour le type $electionTypeId");
            
            sendResponse(true, 'Session de candidature créée avec succès', ['session_id' => $pdo->lastInsertId()]);
            break;
            
        case 'get_dashboard_stats':
            // Récupérer les statistiques du dashboard
            $stats = [];
            
            // Total des étudiants
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'student' AND is_active = 1");
            $stats['total_students'] = $stmt->fetch()['total'];
            
            // Total des votes
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM votes");
            $stats['total_votes'] = $stmt->fetch()['total'];
            
            // Total des candidats approuvés
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM candidatures WHERE status = 'approved'");
            $stats['total_candidates'] = $stmt->fetch()['total'];
            
            // Élections en cours
            $stmt = $pdo->query("
                SELECT COUNT(*) as total 
                FROM vote_sessions 
                WHERE is_active = 1 
                AND start_time <= NOW() 
                AND end_time >= NOW()
            ");
            $stats['active_elections'] = $stmt->fetch()['total'];
            
            // Participation des 7 derniers jours
            $stmt = $pdo->query("
                SELECT 
                    DATE(created_at) as vote_date,
                    COUNT(*) as vote_count
                FROM votes 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY DATE(created_at)
                ORDER BY vote_date
            ");
            $stats['participation_data'] = $stmt->fetchAll();
            
            sendResponse(true, 'Statistiques récupérées', $stats);
            break;
            
        case 'get_clubs':
            // Récupérer la liste des clubs
            $stmt = $pdo->query("SELECT id, name FROM clubs WHERE is_active = 1 ORDER BY name");
            $clubs = $stmt->fetchAll();
            
            sendResponse(true, 'Clubs récupérés', $clubs);
            break;
            
        case 'get_election_types':
            // Récupérer la liste des types d'élections
            $stmt = $pdo->query("SELECT id, name, description FROM election_types WHERE is_active = 1 ORDER BY name");
            $electionTypes = $stmt->fetchAll();
            
            sendResponse(true, 'Types d\'élections récupérés', $electionTypes);
            break;
            
        case 'load_clubs_for_candidature':
            // Récupérer les clubs pour les candidatures
            $electionTypeId = (int)($_POST['election_type_id'] ?? 0);
            
            if ($electionTypeId === 2) { // Type Club
                $stmt = $pdo->query("SELECT id, name FROM clubs WHERE is_active = 1 ORDER BY name");
                $clubs = $stmt->fetchAll();
            } else {
                $clubs = [];
            }
            
            sendResponse(true, 'Clubs récupérés', $clubs);
            break;
            
        case 'pause_vote_session':
            // Mettre en pause une session de vote
            $sessionId = (int)($_POST['session_id'] ?? 0);

            if (!$sessionId) {
                sendResponse(false, 'ID de session invalide');
            }

            ensureSessionPermission($sessionId, 'vote_sessions');
            
            $stmt = $pdo->prepare("UPDATE vote_sessions SET is_active = 0 WHERE id = ?");
            $stmt->execute([$sessionId]);
            
            if ($stmt->rowCount() > 0) {
                logActivity($_SESSION['user_id'], 'pause_vote_session', "Session de vote $sessionId mise en pause");
                sendResponse(true, 'Session de vote mise en pause avec succès');
            } else {
                sendResponse(false, 'Session de vote non trouvée');
            }
            break;
            
        case 'activate_vote_session':
            // Activer une session de vote
            $sessionId = (int)($_POST['session_id'] ?? 0);

            if (!$sessionId) {
                sendResponse(false, 'ID de session invalide');
            }

            ensureSessionPermission($sessionId, 'vote_sessions');
            
            $stmt = $pdo->prepare("UPDATE vote_sessions SET is_active = 1 WHERE id = ?");
            $stmt->execute([$sessionId]);
            
            if ($stmt->rowCount() > 0) {
                logActivity($_SESSION['user_id'], 'activate_vote_session', "Session de vote $sessionId activée");
                sendResponse(true, 'Session de vote activée avec succès');
            } else {
                sendResponse(false, 'Session de vote non trouvée');
            }
            break;

        case 'close_vote_session':
            // Clôturer immédiatement une session de vote
            $typeId = (int)($_POST['election_type_id'] ?? 0);
            if (!$typeId) {
                sendResponse(false, 'ID de type invalide');
            }

            if (!userCanManageType($_SESSION['user_id'], $typeId)) {
                sendResponse(false, 'Droits insuffisants pour ce type d\'élection');
            }

            $stmt = $pdo->prepare("UPDATE vote_sessions SET end_time = NOW(), is_active = 0 WHERE election_type_id = ? AND is_active = 1");
            $stmt->execute([$typeId]);

            if ($stmt->rowCount() > 0) {
                logActivity($_SESSION['user_id'], 'close_vote_session', "Vote pour le type $typeId clôturé");
                sendResponse(true, 'Session de vote clôturée');
            } else {
                sendResponse(false, 'Aucune session active trouvée');
            }
            break;
            
        case 'pause_candidature_session':
            // Mettre en pause une session de candidature
            $sessionId = (int)($_POST['session_id'] ?? 0);

            if (!$sessionId) {
                sendResponse(false, 'ID de session invalide');
            }

            ensureSessionPermission($sessionId, 'candidature_sessions');
            
            $stmt = $pdo->prepare("UPDATE candidature_sessions SET is_active = 0 WHERE id = ?");
            $stmt->execute([$sessionId]);
            
            if ($stmt->rowCount() > 0) {
                logActivity($_SESSION['user_id'], 'pause_candidature_session', "Session de candidature $sessionId mise en pause");
                sendResponse(true, 'Session de candidature mise en pause avec succès');
            } else {
                sendResponse(false, 'Session de candidature non trouvée');
            }
            break;

        case 'close_candidature_session':
            // Clôturer une session de candidature
            $typeId = (int)($_POST['election_type_id'] ?? 0);
            if (!$typeId) {
                sendResponse(false, 'ID de type invalide');
            }

            if (!userCanManageType($_SESSION['user_id'], $typeId)) {
                sendResponse(false, 'Droits insuffisants pour ce type d\'élection');
            }

            $stmt = $pdo->prepare("UPDATE candidature_sessions SET end_time = NOW(), is_active = 0 WHERE election_type_id = ? AND is_active = 1");
            $stmt->execute([$typeId]);

            if ($stmt->rowCount() > 0) {
                logActivity($_SESSION['user_id'], 'close_candidature_session', "Candidature pour le type $typeId clôturée");
                sendResponse(true, 'Session de candidature clôturée');
            } else {
                sendResponse(false, 'Aucune session active trouvée');
            }
            break;

        case 'create_committee':
            $name = trim($_POST['name'] ?? '');
            $emails = $_POST['emails'] ?? '';
            $typeIds = $_POST['election_type_ids'] ?? '';

            if (!$name || !$emails || !$typeIds) {
                sendResponse(false, 'Nom, emails et types sont requis');
            }

            $ids = array_filter(array_map('intval', explode(',', $typeIds)));
            if (empty($ids)) {
                sendResponse(false, 'Types d\'élection invalides');
            }

            $pdo->prepare("INSERT INTO committees (name, created_at) VALUES (?, NOW())")->execute([$name]);
            $committeeId = $pdo->lastInsertId();

            $emailList = array_filter(array_map('trim', explode(',', $emails)));
            foreach ($emailList as $email) {
                $stmt = $pdo->prepare("SELECT id, role FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                if (!$user) {
                    continue;
                }
                if ($user['role'] !== 'admin' && $user['role'] !== 'committee') {
                    $pdo->prepare("UPDATE users SET role = 'committee', updated_at = NOW() WHERE id = ?")->execute([$user['id']]);
                }
                $pdo->prepare("INSERT INTO committee_members (committee_id, user_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE committee_id = committee_id")->execute([$committeeId, $user['id']]);
                $insert = $pdo->prepare("INSERT INTO committee_election_types (user_id, election_type_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE user_id = user_id");
                foreach ($ids as $eid) {
                    $insert->execute([$user['id'], $eid]);
                }
            }

            logActivity($_SESSION['user_id'], 'create_committee', "Comité $committeeId créé");
            sendResponse(true, 'Comité créé avec succès', ['committee_id' => $committeeId]);
            break;
            
        case 'activate_candidature_session':
            // Activer une session de candidature
            $sessionId = (int)($_POST['session_id'] ?? 0);

            if (!$sessionId) {
                sendResponse(false, 'ID de session invalide');
            }

            ensureSessionPermission($sessionId, 'candidature_sessions');
            
            $stmt = $pdo->prepare("UPDATE candidature_sessions SET is_active = 1 WHERE id = ?");
            $stmt->execute([$sessionId]);
            
            if ($stmt->rowCount() > 0) {
                logActivity($_SESSION['user_id'], 'activate_candidature_session', "Session de candidature $sessionId activée");
                sendResponse(true, 'Session de candidature activée avec succès');
            } else {
                sendResponse(false, 'Session de candidature non trouvée');
            }
            break;
            
        case 'delete_vote_session':
            // Supprimer une session de vote
            $sessionId = (int)($_POST['session_id'] ?? 0);

            if (!$sessionId) {
                sendResponse(false, 'ID de session invalide');
            }

            ensureSessionPermission($sessionId, 'vote_sessions');
            
            // Vérifier s'il y a des votes associés
            $stmt = $pdo->prepare("SELECT COUNT(*) as vote_count FROM votes WHERE vote_session_id = ?");
            $stmt->execute([$sessionId]);
            $voteCount = $stmt->fetch()['vote_count'];
            
            if ($voteCount > 0) {
                sendResponse(false, 'Impossible de supprimer une session avec des votes. Supprimez d\'abord les votes.');
            }
            
            $stmt = $pdo->prepare("DELETE FROM vote_sessions WHERE id = ?");
            $stmt->execute([$sessionId]);
            
            if ($stmt->rowCount() > 0) {
                logActivity($_SESSION['user_id'], 'delete_vote_session', "Session de vote $sessionId supprimée");
                sendResponse(true, 'Session de vote supprimée avec succès');
            } else {
                sendResponse(false, 'Session de vote non trouvée');
            }
            break;
            
        case 'delete_candidature_session':
            // Supprimer une session de candidature
            $sessionId = (int)($_POST['session_id'] ?? 0);

            if (!$sessionId) {
                sendResponse(false, 'ID de session invalide');
            }

            ensureSessionPermission($sessionId, 'candidature_sessions');
            
            // Vérifier s'il y a des candidatures associées
            $stmt = $pdo->prepare("SELECT COUNT(*) as candidature_count FROM candidatures WHERE candidature_session_id = ?");
            $stmt->execute([$sessionId]);
            $candidatureCount = $stmt->fetch()['candidature_count'];
            
            if ($candidatureCount > 0) {
                sendResponse(false, 'Impossible de supprimer une session avec des candidatures. Supprimez d\'abord les candidatures.');
            }
            
            $stmt = $pdo->prepare("DELETE FROM candidature_sessions WHERE id = ?");
            $stmt->execute([$sessionId]);
            
            if ($stmt->rowCount() > 0) {
                logActivity($_SESSION['user_id'], 'delete_candidature_session', "Session de candidature $sessionId supprimée");
                sendResponse(true, 'Session de candidature supprimée avec succès');
            } else {
                sendResponse(false, 'Session de candidature non trouvée');
            }
            break;
            
        case 'get_session_details':
            // Récupérer les détails d'une session
            $sessionId = (int)($_POST['session_id'] ?? 0);
            $sessionType = $_POST['session_type'] ?? '';
            
            if (!$sessionId || !$sessionType) {
                sendResponse(false, 'Paramètres invalides');
            }
            
            if ($sessionType === 'vote') {
                $stmt = $pdo->prepare("
                    SELECT 
                        vs.*,
                        et.name as election_type,
                        c.name as club_name,
                        u.username as created_by,
                        (SELECT COUNT(*) FROM votes v WHERE v.vote_session_id = vs.id) as vote_count,
                        (SELECT COUNT(*) FROM users WHERE role = 'student' AND is_active = 1) as total_students
                    FROM vote_sessions vs
                    LEFT JOIN election_types et ON vs.election_type_id = et.id
                    LEFT JOIN clubs c ON vs.club_id = c.id
                    LEFT JOIN users u ON vs.created_by = u.id
                    WHERE vs.id = ?
                ");
            } else {
                $stmt = $pdo->prepare("
                    SELECT 
                        cs.*,
                        et.name as election_type,
                        c.name as club_name,
                        u.username as created_by,
                        (SELECT COUNT(*) FROM candidatures cand WHERE cand.election_type_id = cs.election_type_id AND cand.status = 'approved') as candidate_count
                    FROM candidature_sessions cs
                    LEFT JOIN election_types et ON cs.election_type_id = et.id
                    LEFT JOIN clubs c ON cs.club_id = c.id
                    LEFT JOIN users u ON cs.created_by = u.id
                    WHERE cs.id = ?
                ");
            }
            
            $stmt->execute([$sessionId]);
            $session = $stmt->fetch();
            
            if ($session) {
                sendResponse(true, 'Détails de session récupérés', $session);
            } else {
                sendResponse(false, 'Session non trouvée');
            }
            break;
            
        case 'get_candidate_details':
            // Récupérer les détails d'un candidat
            $candidateId = (int)($_POST['candidate_id'] ?? 0);
            
            if (!$candidateId) {
                sendResponse(false, 'ID de candidat invalide');
            }
            
            $stmt = $pdo->prepare("
                SELECT 
                    c.*,
                    u.username as candidate_name,
                    u.email as candidate_email,
                    u.classe as candidate_class,
                    et.name as election_type,
                    p.name as position_name,
                    cl.name as club_name
                FROM candidatures c
                JOIN users u ON c.user_id = u.id
                LEFT JOIN election_types et ON c.election_type_id = et.id
                LEFT JOIN positions p ON c.position_id = p.id
                LEFT JOIN clubs cl ON c.club_id = cl.id
                WHERE c.id = ?
            ");
            
            $stmt->execute([$candidateId]);
            $candidate = $stmt->fetch();
            
            if ($candidate) {
                sendResponse(true, 'Détails du candidat récupérés', $candidate);
            } else {
                sendResponse(false, 'Candidat non trouvé');
            }
            break;
            
        case 'approve_candidate':
            // Approuver un candidat
            $candidateId = (int)($_POST['candidate_id'] ?? 0);

            if (!$candidateId) {
                sendResponse(false, 'ID de candidat invalide');
            }

            $stmt = $pdo->prepare("SELECT election_type_id FROM candidatures WHERE id = ?");
            $stmt->execute([$candidateId]);
            $typeId = $stmt->fetchColumn();
            if (!$typeId) {
                sendResponse(false, 'Candidature non trouvée');
            }
            if (!userCanManageType($_SESSION['user_id'], $typeId)) {
                sendResponse(false, 'Droits insuffisants pour ce type d\'élection');
            }

            $stmt = $pdo->prepare("UPDATE candidatures SET status = 'approved', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$candidateId]);
            
            if ($stmt->rowCount() > 0) {
                logActivity($_SESSION['user_id'], 'approve_candidate', "Candidature $candidateId approuvée");
                sendResponse(true, 'Candidature approuvée avec succès');
            } else {
                sendResponse(false, 'Candidature non trouvée');
            }
            break;
            
        case 'reject_candidate':
            // Rejeter un candidat
            $candidateId = (int)($_POST['candidate_id'] ?? 0);
            $reason = $_POST['reason'] ?? '';

            if (!$candidateId) {
                sendResponse(false, 'ID de candidat invalide');
            }

            $stmt = $pdo->prepare("SELECT election_type_id FROM candidatures WHERE id = ?");
            $stmt->execute([$candidateId]);
            $typeId = $stmt->fetchColumn();
            if (!$typeId) {
                sendResponse(false, 'Candidature non trouvée');
            }
            if (!userCanManageType($_SESSION['user_id'], $typeId)) {
                sendResponse(false, 'Droits insuffisants pour ce type d\'élection');
            }

            $stmt = $pdo->prepare("UPDATE candidatures SET status = 'rejected', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$candidateId]);
            
            if ($stmt->rowCount() > 0) {
                $logMessage = "Candidature $candidateId rejetée";
                if ($reason) {
                    $logMessage .= " - Raison: $reason";
                }
                logActivity($_SESSION['user_id'], 'reject_candidate', $logMessage);
                sendResponse(true, 'Candidature rejetée avec succès');
            } else {
                sendResponse(false, 'Candidature non trouvée');
            }
            break;
            
        case 'delete_candidate':
            // Supprimer un candidat
            $candidateId = (int)($_POST['candidate_id'] ?? 0);
            
            if (!$candidateId) {
                sendResponse(false, 'ID de candidat invalide');
            }
            
            // Vérifier s'il y a des votes associés
            $stmt = $pdo->prepare("SELECT COUNT(*) as vote_count FROM votes WHERE candidature_id = ?");
            $stmt->execute([$candidateId]);
            $voteCount = $stmt->fetch()['vote_count'];
            
            if ($voteCount > 0) {
                sendResponse(false, 'Impossible de supprimer une candidature avec des votes. Supprimez d\'abord les votes.');
            }
            
            $stmt = $pdo->prepare("DELETE FROM candidatures WHERE id = ?");
            $stmt->execute([$candidateId]);
            
            if ($stmt->rowCount() > 0) {
                logActivity($_SESSION['user_id'], 'delete_candidate', "Candidature $candidateId supprimée");
                sendResponse(true, 'Candidature supprimée avec succès');
            } else {
                sendResponse(false, 'Candidature non trouvée');
            }
            break;
            
        case 'edit_vote_session':
            // Modifier une session de vote
            $sessionId = (int)($_POST['session_id'] ?? 0);
            $startTime = $_POST['start_time'] ?? '';
            $endTime = $_POST['end_time'] ?? '';
            
            if (!$sessionId || !$startTime || !$endTime) {
                sendResponse(false, 'Toutes les informations sont requises');
            }
            
            // Vérifier que les dates sont valides
            $startDateTime = new DateTime($startTime);
            $endDateTime = new DateTime($endTime);
            
            if ($endDateTime <= $startDateTime) {
                sendResponse(false, 'La date de fin doit être après la date de début');
            }
            
            // Vérifier qu'il n'y a pas de conflit avec une autre session
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as conflict_count
                FROM vote_sessions 
                WHERE id != ? 
                AND election_type_id = (SELECT election_type_id FROM vote_sessions WHERE id = ?)
                AND club_id = (SELECT club_id FROM vote_sessions WHERE id = ?)
                AND is_active = 1
                AND (
                    (start_time <= ? AND end_time >= ?) OR
                    (start_time <= ? AND end_time >= ?) OR
                    (start_time >= ? AND end_time <= ?)
                )
            ");
            $stmt->execute([$sessionId, $sessionId, $sessionId, $startTime, $startTime, $endTime, $endTime, $startTime, $endTime]);
            $conflict = $stmt->fetch();
            
            if ($conflict['conflict_count'] > 0) {
                sendResponse(false, 'Il existe déjà une session de vote pour cette période');
            }
            
            // Mettre à jour la session
            $stmt = $pdo->prepare("
                UPDATE vote_sessions 
                SET start_time = ?, end_time = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$startTime, $endTime, $sessionId]);
            
            if ($stmt->rowCount() > 0) {
                logActivity($_SESSION['user_id'], 'edit_vote_session', "Session de vote $sessionId modifiée");
                sendResponse(true, 'Session de vote modifiée avec succès');
            } else {
                sendResponse(false, 'Session de vote non trouvée');
            }
            break;
            
        case 'edit_candidature_session':
            // Modifier une session de candidature
            $sessionId = (int)($_POST['session_id'] ?? 0);
            $startTime = $_POST['start_time'] ?? '';
            $endTime = $_POST['end_time'] ?? '';
            
            if (!$sessionId || !$startTime || !$endTime) {
                sendResponse(false, 'Toutes les informations sont requises');
            }
            
            // Vérifier que les dates sont valides
            $startDateTime = new DateTime($startTime);
            $endDateTime = new DateTime($endTime);
            
            if ($endDateTime <= $startDateTime) {
                sendResponse(false, 'La date de fin doit être après la date de début');
            }
            
            // Vérifier qu'il n'y a pas de conflit avec une autre session
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as conflict_count
                FROM candidature_sessions 
                WHERE id != ? 
                AND election_type_id = (SELECT election_type_id FROM candidature_sessions WHERE id = ?)
                AND club_id = (SELECT club_id FROM candidature_sessions WHERE id = ?)
                AND is_active = 1
                AND (
                    (start_time <= ? AND end_time >= ?) OR
                    (start_time <= ? AND end_time >= ?) OR
                    (start_time >= ? AND end_time <= ?)
                )
            ");
            $stmt->execute([$sessionId, $sessionId, $sessionId, $startTime, $startTime, $endTime, $endTime, $startTime, $endTime]);
            $conflict = $stmt->fetch();
            
            if ($conflict['conflict_count'] > 0) {
                sendResponse(false, 'Il existe déjà une session de candidature pour cette période');
            }
            
            // Mettre à jour la session
            $stmt = $pdo->prepare("
                UPDATE candidature_sessions 
                SET start_time = ?, end_time = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$startTime, $endTime, $sessionId]);
            
            if ($stmt->rowCount() > 0) {
                logActivity($_SESSION['user_id'], 'edit_candidature_session', "Session de candidature $sessionId modifiée");
                sendResponse(true, 'Session de candidature modifiée avec succès');
            } else {
                sendResponse(false, 'Session de candidature non trouvée');
            }
            break;
            
        case 'add_committee_member':
            // Ajouter un membre au comité
            $email = $_POST['email'] ?? '';
            $role = $_POST['role'] ?? '';
            $typeIds = $_POST['election_type_ids'] ?? '';

            if (!$email || !$role || !$typeIds) {
                sendResponse(false, 'Email, rôle et types sont requis');
            }

            if (!in_array($role, ['admin', 'committee'])) {
                sendResponse(false, 'Rôle invalide');
            }

            // Nettoyer les ids des types d'élection
            $ids = array_filter(array_map('intval', explode(',', $typeIds)));
            if (empty($ids)) {
                sendResponse(false, 'Types d\'élection invalides');
            }

            // Vérifier si l'utilisateur existe
            $stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                sendResponse(false, 'Utilisateur non trouvé avec cet email');
            }

            // Mettre à jour le rôle si nécessaire
            if ($user['role'] !== $role) {
                $stmt = $pdo->prepare("UPDATE users SET role = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$role, $user['id']]);
            }

            // Insérer les liaisons comité <-> types d'élection
            $insert = $pdo->prepare("INSERT INTO committee_election_types (user_id, election_type_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE user_id = user_id");
            foreach ($ids as $eid) {
                $insert->execute([$user['id'], $eid]);
            }

            logActivity($_SESSION['user_id'], 'add_committee_member', "Utilisateur $user[id] ajouté au comité pour les types " . implode(',', $ids));
            sendResponse(true, 'Membre ajouté au comité avec succès');
            break;
            
        case 'get_member_details':
            // Récupérer les détails d'un membre
            $memberId = (int)($_POST['member_id'] ?? 0);
            
            if (!$memberId) {
                sendResponse(false, 'ID de membre invalide');
            }
            
            $stmt = $pdo->prepare(
                "SELECT
                    u.*,
                    (SELECT COUNT(*) FROM activity_logs WHERE user_id = u.id) as activity_count,
                    (SELECT COUNT(*) FROM vote_sessions WHERE created_by = u.id) as sessions_created,
                    (SELECT COUNT(*) FROM candidature_sessions WHERE created_by = u.id) as candidature_sessions_created,
                    GROUP_CONCAT(et.name SEPARATOR ', ') AS elections
                 FROM users u
                 LEFT JOIN committee_election_types cet ON cet.user_id = u.id
                 LEFT JOIN election_types et ON cet.election_type_id = et.id
                 WHERE u.id = ? AND u.role IN ('admin', 'committee')
                 GROUP BY u.id"
            );
            
            $stmt->execute([$memberId]);
            $member = $stmt->fetch();
            
            if ($member) {
                sendResponse(true, 'Détails du membre récupérés', $member);
            } else {
                sendResponse(false, 'Membre non trouvé');
            }
            break;
            
        case 'activate_member':
            // Activer un membre
            $memberId = (int)($_POST['member_id'] ?? 0);
            
            if (!$memberId) {
                sendResponse(false, 'ID de membre invalide');
            }
            
            // Vérifier que ce n'est pas l'utilisateur actuel
            if ($memberId == $_SESSION['user_id']) {
                sendResponse(false, 'Vous ne pouvez pas modifier votre propre statut');
            }
            
            $stmt = $pdo->prepare("UPDATE users SET is_active = 1, updated_at = NOW() WHERE id = ? AND role IN ('admin', 'committee')");
            $stmt->execute([$memberId]);
            
            if ($stmt->rowCount() > 0) {
                logActivity($_SESSION['user_id'], 'activate_member', "Membre $memberId activé");
                sendResponse(true, 'Membre activé avec succès');
            } else {
                sendResponse(false, 'Membre non trouvé');
            }
            break;
            
        case 'deactivate_member':
            // Désactiver un membre
            $memberId = (int)($_POST['member_id'] ?? 0);
            
            if (!$memberId) {
                sendResponse(false, 'ID de membre invalide');
            }
            
            // Vérifier que ce n'est pas l'utilisateur actuel
            if ($memberId == $_SESSION['user_id']) {
                sendResponse(false, 'Vous ne pouvez pas modifier votre propre statut');
            }
            
            $stmt = $pdo->prepare("UPDATE users SET is_active = 0, updated_at = NOW() WHERE id = ? AND role IN ('admin', 'committee')");
            $stmt->execute([$memberId]);
            
            if ($stmt->rowCount() > 0) {
                logActivity($_SESSION['user_id'], 'deactivate_member', "Membre $memberId désactivé");
                sendResponse(true, 'Membre désactivé avec succès');
            } else {
                sendResponse(false, 'Membre non trouvé');
            }
            break;
            
        case 'remove_member':
            // Retirer un membre du comité
            $memberId = (int)($_POST['member_id'] ?? 0);
            
            if (!$memberId) {
                sendResponse(false, 'ID de membre invalide');
            }
            
            // Vérifier que ce n'est pas l'utilisateur actuel
            if ($memberId == $_SESSION['user_id']) {
                sendResponse(false, 'Vous ne pouvez pas vous retirer vous-même du comité');
            }
            
            // Vérifier s'il y a des sessions créées par ce membre
            $stmt = $pdo->prepare("
                SELECT 
                    (SELECT COUNT(*) FROM vote_sessions WHERE created_by = ?) as vote_sessions,
                    (SELECT COUNT(*) FROM candidature_sessions WHERE created_by = ?) as candidature_sessions
            ");
            $stmt->execute([$memberId, $memberId]);
            $sessions = $stmt->fetch();
            
            if ($sessions['vote_sessions'] > 0 || $sessions['candidature_sessions'] > 0) {
                sendResponse(false, 'Impossible de retirer ce membre car il a créé des sessions. Transférez d\'abord les sessions.');
            }
            
            // Retirer le membre (remettre en tant qu'étudiant)
            $stmt = $pdo->prepare("UPDATE users SET role = 'student', updated_at = NOW() WHERE id = ? AND role IN ('admin', 'committee')");
            $stmt->execute([$memberId]);
            $pdo->prepare("DELETE FROM committee_election_types WHERE user_id = ?")->execute([$memberId]);
            
            if ($stmt->rowCount() > 0) {
                logActivity($_SESSION['user_id'], 'remove_member', "Membre $memberId retiré du comité");
                sendResponse(true, 'Membre retiré du comité avec succès');
            } else {
                sendResponse(false, 'Membre non trouvé');
            }
            break;

        case 'search_emails':
            // Rechercher les emails commençant par un terme donné
            $term = trim($_POST['query'] ?? '');

            $emails = [];
            if ($term !== '') {
                $stmt = $pdo->prepare("SELECT email FROM users WHERE email LIKE ? ORDER BY email LIMIT 10");
                $stmt->execute(["{$term}%"]);
                $emails = $stmt->fetchAll(PDO::FETCH_COLUMN);
            }

            sendResponse(true, 'Liste des emails', $emails);
            break;

        default:
            sendResponse(false, 'Action non reconnue');
            break;
    }
    
} catch (PDOException $e) {
    error_log("Erreur dans actions.php: " . $e->getMessage());
    sendResponse(false, 'Erreur de base de données');
} catch (Exception $e) {
    error_log("Erreur générale dans actions.php: " . $e->getMessage());
    sendResponse(false, 'Erreur interne du serveur');
}
?>