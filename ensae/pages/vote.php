<?php
require_once('../config/database.php');
requireLogin();

// Récupérer les sessions de vote actives
$sessions = $pdo->query("
    SELECT vs.*, et.name as election_type, c.name as club_name
    FROM vote_sessions vs
    LEFT JOIN election_types et ON vs.election_type_id = et.id
    LEFT JOIN clubs c ON vs.club_id = c.id
    WHERE vs.is_active = 1 AND vs.start_time <= NOW() AND vs.end_time >= NOW()
    ORDER BY vs.start_time DESC
")->fetchAll();

// Fonction pour récupérer les candidats approuvés pour une session
function getCandidates($pdo, $session) {
    $sql = "
        SELECT c.id, c.programme, c.photo_url, u.username as candidate_name, u.classe as candidate_class, p.name as position_name
        FROM candidatures c
        JOIN users u ON c.user_id = u.id
        LEFT JOIN positions p ON c.position_id = p.id
        WHERE c.election_type_id = :election_type_id
        AND c.status = 'approved'
    ";
    $params = [':election_type_id' => $session['election_type_id']];
    if ($session['club_id']) {
        $sql .= " AND c.club_id = :club_id";
        $params[':club_id'] = $session['club_id'];
    }
    $sql .= " ORDER BY p.name, u.username";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Fonction pour savoir si l'utilisateur a déjà voté pour une session
function hasVoted($pdo, $userId, $sessionId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM votes WHERE voter_id = ? AND vote_session_id = ?");
    $stmt->execute([$userId, $sessionId]);
    return $stmt->fetchColumn() > 0;
}

// Gestion du vote (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote_session_id'], $_POST['candidate_id'])) {
    $sessionId = (int)$_POST['vote_session_id'];
    $candidateId = (int)$_POST['candidate_id'];
    $userId = $_SESSION['user_id'];
    if (!hasVoted($pdo, $userId, $sessionId)) {
        // Récupérer la session pour l'election_type_id
        $stmt = $pdo->prepare("SELECT election_type_id FROM vote_sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
        $row = $stmt->fetch();
        if ($row) {
            $electionTypeId = $row['election_type_id'];
            $stmt = $pdo->prepare("INSERT INTO votes (voter_id, candidature_id, election_type_id, vote_session_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, $candidateId, $electionTypeId, $sessionId]);
            $message = "<div style='color:green; font-weight:600; margin-bottom:15px;'>Votre vote a bien été enregistré !</div>";
        }
    } else {
        $message = "<div style='color:#e74c3c; font-weight:600; margin-bottom:15px;'>Vous avez déjà voté pour cette élection.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vote en ligne - ENSAE</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/vote.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    .vote-container {
        max-width: 1000px;
        margin: 40px auto;
        padding: 0 20px;
    }

    .vote-header {
        text-align: center;
        margin-bottom: 40px;
    }

    .vote-header h1 {
        color: #333;
        font-size: 2.5em;
        font-weight: 700;
        margin-bottom: 10px;
    }

    .vote-header p {
        color: #666;
        font-size: 1.1em;
    }

    .sessions-list {
        display: flex;
        flex-direction: column;
        gap: 30px;
    }

    .session-card {
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        padding: 25px;
        border: 1px solid #e1e5e9;
    }

    .session-title {
        font-size: 1.3em;
        font-weight: 600;
        color: #333;
        margin-bottom: 10px;
    }

    .session-info {
        color: #666;
        margin-bottom: 15px;
    }

    .candidates-list {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-top: 15px;
    }

    .candidate-card {
        background: #f8f9fa;
        border-radius: 10px;
        border: 1px solid #e1e5e9;
        padding: 15px;
        width: 260px;
        display: flex;
        flex-direction: column;
        align-items: center;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    }

    .candidate-photo {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        overflow: hidden;
        border: 2px solid #e1e5e9;
        margin-bottom: 10px;
    }

    .candidate-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .candidate-name {
        font-weight: 600;
        color: #333;
        font-size: 1.1em;
        margin-bottom: 5px;
    }

    .candidate-position {
        color: #667eea;
        font-size: 0.98em;
        margin-bottom: 3px;
    }

    .candidate-class {
        color: #888;
        font-size: 0.95em;
        margin-bottom: 8px;
    }

    .candidate-programme {
        color: #555;
        font-size: 0.95em;
        margin-bottom: 10px;
        text-align: center;
    }

    .vote-btn {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 10px 20px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }

    .vote-btn:disabled {
        background: #ccc;
        cursor: not-allowed;
    }

    .already-voted {
        color: #28a745;
        font-weight: 600;
        margin-top: 10px;
    }

    @media (max-width: 700px) {
        .candidates-list {
            flex-direction: column;
            align-items: center;
        }

        .candidate-card {
            width: 100%;
        }
    }
    </style>
</head>

<body>
    <?php include('../components/header.php'); ?>
    <div class="vote-container">
        <div class="vote-header">
            <h1><i class="fas fa-vote-yea"></i> Vote en ligne</h1>
            <p>Participez aux élections en toute sécurité</p>
        </div>
        <?php if (!empty($message)) echo $message; ?>
        <?php if (empty($sessions)): ?>
        <div
            style="text-align:center; padding:40px; background:#fff; border-radius:15px; box-shadow:0 4px 20px rgba(0,0,0,0.08);">
            <i class="fas fa-calendar-times" style="font-size:3em; color:#ccc;"></i>
            <h3 style="color:#666; margin:15px 0 5px;">Aucune élection en cours</h3>
            <p style="color:#999;">Il n'y a actuellement aucune session de vote active.</p>
        </div>
        <?php else: ?>
        <div class="sessions-list">
            <?php foreach ($sessions as $session): ?>
            <?php $candidates = getCandidates($pdo, $session); $userVoted = hasVoted($pdo, $_SESSION['user_id'], $session['id']); ?>
            <div class="session-card">
                <div class="session-title">
                    <?php echo htmlspecialchars($session['election_type']); ?>
                    <?php if ($session['club_name']): ?>
                    - <?php echo htmlspecialchars($session['club_name']); ?>
                    <?php endif; ?>
                </div>
                <div class="session-info">
                    <i class="fas fa-clock"></i> Termine le
                    <?php echo date('d/m/Y H:i', strtotime($session['end_time'])); ?>
                </div>
                <div class="candidates-list">
                    <?php if (empty($candidates)): ?>
                    <div style="color:#aaa;">Aucun candidat pour cette élection.</div>
                    <?php else: ?>
                    <?php foreach ($candidates as $cand): ?>
                    <div class="candidate-card">
                        <div class="candidate-photo">
                            <img src="<?php echo $cand['photo_url'] ? '../'.$cand['photo_url'] : '../assets/img/logo_ensae.png'; ?>"
                                alt="Photo de <?php echo htmlspecialchars($cand['candidate_name']); ?>">
                        </div>
                        <div class="candidate-name"><?php echo htmlspecialchars($cand['candidate_name']); ?></div>
                        <div class="candidate-position">
                            <?php echo htmlspecialchars($cand['position_name'] ?? 'Candidat'); ?></div>
                        <div class="candidate-class">Classe : <?php echo htmlspecialchars($cand['candidate_class']); ?>
                        </div>
                        <div class="candidate-programme">
                            <?php echo htmlspecialchars(mb_strimwidth($cand['programme'], 0, 120, '...')); ?></div>
                        <form method="post" style="margin-top:10px;">
                            <input type="hidden" name="vote_session_id" value="<?php echo $session['id']; ?>">
                            <input type="hidden" name="candidate_id" value="<?php echo $cand['id']; ?>">
                            <button type="submit" class="vote-btn" <?php if ($userVoted) echo 'disabled'; ?>>
                                <i class="fas fa-vote-yea"></i> Voter
                            </button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php if ($userVoted): ?>
                <div class="already-voted"><i class="fas fa-check-circle"></i> Vous avez déjà voté pour cette élection.
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php include('../components/footer.php'); ?>
</body>

</html>