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

// Debug: Log des informations de la requête
error_log("Candidature action - User ID: " . ($_SESSION['user_id'] ?? 'non défini'));
error_log("Candidature action - HTTP_X_REQUESTED_WITH: " . ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? 'non défini'));
error_log("Candidature action - POST action: " . ($_POST['action'] ?? 'non défini'));

// Vérifier que c'est une requête AJAX (version plus flexible)
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
$hasAction = isset($_POST['action']) && !empty($_POST['action']);

// Si ce n'est pas AJAX mais qu'il y a une action, on accepte quand même
// (certains navigateurs ne passent pas correctement l'en-tête AJAX)
if (!$isAjax && !$hasAction) {
    error_log("Candidature action - Accès refusé: pas AJAX et pas d'action");
    http_response_code(403);
    die('Accès direct non autorisé');
}

// Récupérer l'action demandée
$action = $_POST['action'] ?? '';

// Fonction pour valider et uploader une photo
function uploadPhoto($file) {
    $uploadDir = __DIR__ . '/../assets/img/candidates/';
    
    // Créer le dossier s'il n'existe pas
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Validation du fichier
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $maxSize = 2 * 1024 * 1024; // 2MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => 'Type de fichier non autorisé. Utilisez JPG, PNG ou GIF.'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'Fichier trop volumineux. Taille maximale : 2MB.'];
    }
    
    // Générer un nom de fichier unique
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'candidate_' . time() . '_' . uniqid() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    // Upload du fichier
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'path' => 'assets/img/candidates/' . $filename];
    } else {
        return ['success' => false, 'message' => 'Erreur lors de l\'upload du fichier.'];
    }
}

try {
    switch ($action) {
        case 'submit_candidature':
            // Soumettre une candidature
            $electionTypeId = (int)($_POST['electionType'] ?? 0);
            $positionId = (int)($_POST['position'] ?? 0);
            $clubId = !empty($_POST['club']) ? (int)$_POST['club'] : null;
            $programme = trim($_POST['programme'] ?? '');
            $nom = trim($_POST['nom'] ?? '');
            $prenom = trim($_POST['prenom'] ?? '');
            $classe = $_POST['classe'] ?? '';
            
            // Validation des données
            if (!$electionTypeId || !$positionId || !$programme || !$nom || !$prenom || !$classe) {
                sendResponse(false, 'Tous les champs obligatoires doivent être remplis');
            }
            
            if (strlen($programme) < 50) {
                sendResponse(false, 'Le programme électoral doit contenir au moins 50 caractères');
            }
            
            if (strlen($programme) > 5000) {
                sendResponse(false, 'Le programme électoral ne peut pas dépasser 5000 caractères');
            }
            
            // Vérifier que l'utilisateur n'a pas déjà une candidature en cours
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as has_candidature 
                FROM candidatures 
                WHERE user_id = ? AND status IN ('pending', 'approved')
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $hasCandidature = $stmt->fetch()['has_candidature'] > 0;
            
            if ($hasCandidature) {
                sendResponse(false, 'Vous avez déjà une candidature en cours');
            }
            
            // Vérifier qu'il y a une session de candidature active
            $stmt = $pdo->prepare("
                SELECT cs.* FROM candidature_sessions cs
                WHERE cs.is_active = 1 
                AND cs.election_type_id = ? 
                AND (? IS NULL OR cs.club_id = ?)
                AND cs.start_time <= NOW() 
                AND cs.end_time >= NOW()
                LIMIT 1
            ");
            $stmt->execute([$electionTypeId, $clubId, $clubId]);
            $activeSession = $stmt->fetch();
            
            if (!$activeSession) {
                sendResponse(false, 'Aucune session de candidature active pour ce type d\'élection');
            }
            
            // Vérifier que la position correspond au type d'élection
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as valid_position 
                FROM positions 
                WHERE id = ? AND election_type_id = ? AND is_active = 1
            ");
            $stmt->execute([$positionId, $electionTypeId]);
            $validPosition = $stmt->fetch()['valid_position'] > 0;
            
            if (!$validPosition) {
                sendResponse(false, 'Position invalide pour ce type d\'élection');
            }
            
            // Traitement de la photo si fournie
            $photoUrl = null;
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = uploadPhoto($_FILES['photo']);
                if (!$uploadResult['success']) {
                    sendResponse(false, $uploadResult['message']);
                }
                $photoUrl = $uploadResult['path'];
            }
            
            // Insérer la candidature
            $stmt = $pdo->prepare("
                INSERT INTO candidatures (
                    user_id, 
                    election_type_id, 
                    position_id, 
                    club_id, 
                    programme, 
                    photo_url, 
                    status, 
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            
            $stmt->execute([
                $_SESSION['user_id'],
                $electionTypeId,
                $positionId,
                $clubId,
                $programme,
                $photoUrl
            ]);
            
            $candidatureId = $pdo->lastInsertId();
            
            // Logger l'activité
            logActivity($_SESSION['user_id'], 'submit_candidature', "Candidature $candidatureId soumise pour le type $electionTypeId");
            
            sendResponse(true, 'Candidature soumise avec succès ! Elle sera examinée par l\'équipe administrative.', [
                'candidature_id' => $candidatureId
            ]);
            break;
            
        case 'get_my_candidatures':
            // Récupérer les candidatures de l'utilisateur
            $stmt = $pdo->prepare("
                SELECT 
                    c.*,
                    et.name as election_type,
                    p.name as position_name,
                    cl.name as club_name
                FROM candidatures c
                LEFT JOIN election_types et ON c.election_type_id = et.id
                LEFT JOIN positions p ON c.position_id = p.id
                LEFT JOIN clubs cl ON c.club_id = cl.id
                WHERE c.user_id = ?
                ORDER BY c.created_at DESC
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $candidatures = $stmt->fetchAll();
            
            sendResponse(true, 'Candidatures récupérées', $candidatures);
            break;
            
        case 'get_candidature_details':
            // Récupérer les détails d'une candidature spécifique
            $candidatureId = (int)($_POST['candidature_id'] ?? 0);
            
            if (!$candidatureId) {
                sendResponse(false, 'ID de candidature invalide');
            }
            
            $stmt = $pdo->prepare("
                SELECT 
                    c.*,
                    et.name as election_type,
                    p.name as position_name,
                    cl.name as club_name
                FROM candidatures c
                LEFT JOIN election_types et ON c.election_type_id = et.id
                LEFT JOIN positions p ON c.position_id = p.id
                LEFT JOIN clubs cl ON c.club_id = cl.id
                WHERE c.id = ? AND c.user_id = ?
            ");
            $stmt->execute([$candidatureId, $_SESSION['user_id']]);
            $candidature = $stmt->fetch();
            
            if (!$candidature) {
                sendResponse(false, 'Candidature non trouvée');
            }
            
            sendResponse(true, 'Détails de candidature récupérés', $candidature);
            break;
            
        case 'update_candidature':
            // Mettre à jour une candidature (si elle est encore en attente)
            $candidatureId = (int)($_POST['candidature_id'] ?? 0);
            $programme = trim($_POST['programme'] ?? '');
            
            if (!$candidatureId || !$programme) {
                sendResponse(false, 'Paramètres invalides');
            }
            
            if (strlen($programme) < 50) {
                sendResponse(false, 'Le programme électoral doit contenir au moins 50 caractères');
            }
            
            if (strlen($programme) > 5000) {
                sendResponse(false, 'Le programme électoral ne peut pas dépasser 5000 caractères');
            }
            
            // Vérifier que la candidature appartient à l'utilisateur et est en attente
            $stmt = $pdo->prepare("
                SELECT id FROM candidatures 
                WHERE id = ? AND user_id = ? AND status = 'pending'
            ");
            $stmt->execute([$candidatureId, $_SESSION['user_id']]);
            $candidature = $stmt->fetch();
            
            if (!$candidature) {
                sendResponse(false, 'Candidature non trouvée ou non modifiable');
            }
            
            // Traitement de la nouvelle photo si fournie
            $photoUrl = null;
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = uploadPhoto($_FILES['photo']);
                if (!$uploadResult['success']) {
                    sendResponse(false, $uploadResult['message']);
                }
                $photoUrl = $uploadResult['path'];
            }
            
            // Mettre à jour la candidature
            if ($photoUrl) {
                $stmt = $pdo->prepare("
                    UPDATE candidatures 
                    SET programme = ?, photo_url = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$programme, $photoUrl, $candidatureId]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE candidatures 
                    SET programme = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$programme, $candidatureId]);
            }
            
            // Logger l'activité
            logActivity($_SESSION['user_id'], 'update_candidature', "Candidature $candidatureId mise à jour");
            
            sendResponse(true, 'Candidature mise à jour avec succès');
            break;
            
        case 'delete_candidature':
            // Supprimer une candidature (si elle est encore en attente)
            $candidatureId = (int)($_POST['candidature_id'] ?? 0);
            
            if (!$candidatureId) {
                sendResponse(false, 'ID de candidature invalide');
            }
            
            // Vérifier que la candidature appartient à l'utilisateur et est en attente
            $stmt = $pdo->prepare("
                SELECT id, photo_url FROM candidatures 
                WHERE id = ? AND user_id = ? AND status = 'pending'
            ");
            $stmt->execute([$candidatureId, $_SESSION['user_id']]);
            $candidature = $stmt->fetch();
            
            if (!$candidature) {
                sendResponse(false, 'Candidature non trouvée ou non supprimable');
            }
            
            // Supprimer la photo si elle existe
            if ($candidature['photo_url']) {
                $photoPath = __DIR__ . '/../' . $candidature['photo_url'];
                if (file_exists($photoPath)) {
                    unlink($photoPath);
                }
            }
            
            // Supprimer la candidature
            $stmt = $pdo->prepare("DELETE FROM candidatures WHERE id = ?");
            $stmt->execute([$candidatureId]);
            
            // Logger l'activité
            logActivity($_SESSION['user_id'], 'delete_candidature', "Candidature $candidatureId supprimée");
            
            sendResponse(true, 'Candidature supprimée avec succès');
            break;
            
        case 'get_active_sessions':
            // Récupérer les sessions de candidature actives
            $stmt = $pdo->query("
                SELECT 
                    cs.*,
                    et.name as election_type,
                    c.name as club_name
                FROM candidature_sessions cs
                LEFT JOIN election_types et ON cs.election_type_id = et.id
                LEFT JOIN clubs c ON cs.club_id = c.id
                WHERE cs.is_active = 1 
                AND cs.start_time <= NOW() 
                AND cs.end_time >= NOW()
                ORDER BY cs.start_time DESC
            ");
            $activeSessions = $stmt->fetchAll();
            
            sendResponse(true, 'Sessions actives récupérées', $activeSessions);
            break;
            
        default:
            sendResponse(false, 'Action non reconnue');
            break;
    }
    
} catch (PDOException $e) {
    error_log("Erreur dans candidature_actions.php: " . $e->getMessage());
    sendResponse(false, 'Erreur de base de données: ' . $e->getMessage());
} catch (Exception $e) {
    error_log("Erreur générale dans candidature_actions.php: " . $e->getMessage());
    sendResponse(false, 'Erreur interne du serveur: ' . $e->getMessage());
}
?>