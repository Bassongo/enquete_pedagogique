<?php
// Inclure la configuration de la base de données
require_once('../config/database.php');

// Vérifier que l'utilisateur est connecté et est admin
requireRole('admin');

// Récupérer les paramètres
$sessionId = (int)($_GET['id'] ?? 0);
$sessionType = $_GET['type'] ?? '';

if (!$sessionId || !$sessionType) {
    header('Location: elections.php');
    exit();
}

// Récupérer les détails de la session
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

if (!$session) {
    header('Location: elections.php');
    exit();
}

// Déterminer le statut de la session
$now = new DateTime();
$start = new DateTime($session['start_time']);
$end = new DateTime($session['end_time']);

if ($session['is_active'] == 0) {
    $status = 'inactive';
    $status_text = 'Inactive';
} elseif ($now >= $start && $now <= $end) {
    $status = 'active';
    $status_text = 'En cours';
} elseif ($now < $start) {
    $status = 'upcoming';
    $status_text = 'À venir';
} else {
    $status = 'completed';
    $status_text = 'Terminée';
}

// Récupérer les données supplémentaires selon le type
if ($sessionType === 'vote') {
    // Récupérer les votes récents
    $stmt = $pdo->prepare("
        SELECT 
            v.*,
            u.username as voter_name,
            c.programme as candidate_programme
        FROM votes v
        JOIN users u ON v.voter_id = u.id
        JOIN candidatures c ON v.candidature_id = c.id
        WHERE v.vote_session_id = ?
        ORDER BY v.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$sessionId]);
    $recentVotes = $stmt->fetchAll();
    
    // Récupérer les statistiques par candidat
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.programme,
            u.username as candidate_name,
            COUNT(v.id) as vote_count
        FROM candidatures c
        JOIN users u ON c.user_id = u.id
        LEFT JOIN votes v ON c.id = v.candidature_id AND v.vote_session_id = ?
        WHERE c.election_type_id = ? AND c.status = 'approved'
        GROUP BY c.id, c.programme, u.username
        ORDER BY vote_count DESC
    ");
    $stmt->execute([$sessionId, $session['election_type_id']]);
    $candidateStats = $stmt->fetchAll();
} else {
    // Récupérer les candidatures
    $stmt = $pdo->prepare("
        SELECT 
            c.*,
            u.username as candidate_name,
            u.email as candidate_email
        FROM candidatures c
        JOIN users u ON c.user_id = u.id
        WHERE c.election_type_id = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$session['election_type_id']]);
    $candidatures = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails de Session - Vote ENSAE</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/admin_accueil.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <style>
        .details-container {
            padding: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e1e5e9;
        }
        
        .page-title {
            font-size: 2em;
            font-weight: 700;
            color: #333;
            margin: 0;
        }
        
        .back-btn {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.4);
        }
        
        .session-overview {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid #e1e5e9;
        }
        
        .session-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
        }
        
        .session-info h2 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 1.8em;
            font-weight: 600;
        }
        
        .session-info p {
            margin: 0;
            color: #666;
            font-size: 1.1em;
        }
        
        .session-status {
            padding: 12px 24px;
            border-radius: 25px;
            font-size: 0.9em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-upcoming {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-completed {
            background-color: #e2e3e5;
            color: #383d41;
        }
        
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .session-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            border: 1px solid #e1e5e9;
        }
        
        .stat-card h3 {
            margin: 0 0 5px 0;
            color: #333;
            font-size: 2em;
            font-weight: 700;
        }
        
        .stat-card p {
            margin: 0;
            color: #666;
            font-weight: 500;
        }
        
        .session-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .detail-section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #e1e5e9;
        }
        
        .detail-section h3 {
            margin: 0 0 15px 0;
            color: #333;
            font-size: 1.2em;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #e1e5e9;
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 500;
            color: #666;
        }
        
        .detail-value {
            font-weight: 600;
            color: #333;
        }
        
        .content-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid #e1e5e9;
        }
        
        .content-section h2 {
            margin: 0 0 20px 0;
            color: #333;
            font-size: 1.5em;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .data-table th,
        .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e1e5e9;
        }
        
        .data-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .data-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        
        .empty-state i {
            font-size: 3em;
            color: #ddd;
            margin-bottom: 15px;
        }
        
        .empty-state h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .empty-state p {
            margin: 0;
            font-size: 1em;
        }
        
        @media (max-width: 768px) {
            .details-container {
                padding: 20px;
            }
            
            .page-header {
                flex-direction: column;
                gap: 20px;
                align-items: stretch;
            }
            
            .session-header {
                flex-direction: column;
                gap: 15px;
            }
            
            .session-stats {
                grid-template-columns: 1fr;
            }
            
            .session-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <?php include('../components/header_admin.php'); ?>

    <div class="main-content">
        <aside class="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-user-shield"></i>
                <h3>Administration</h3>
            </div>
            <button class="sidebar-btn" onclick="window.location.href='dashboard.php'">
                <i class="fas fa-tachometer-alt"></i>
                DASHBOARD
            </button>
            <button class="sidebar-btn active" onclick="window.location.href='elections.php'">
                <i class="fas fa-vote-yea"></i>
                GESTION DES ELECTIONS
            </button>
            <button class="sidebar-btn" onclick="window.location.href='candidates.php'">
                <i class="fas fa-users"></i>
                GESTION DES CANDIDATS
            </button>
            <button class="sidebar-btn" onclick="window.location.href='committees.php'">
                <i class="fas fa-user-tie"></i>
                GESTION DES COMITES
            </button>
            <button class="sidebar-btn" onclick="window.location.href='settings.php'">
                <i class="fas fa-cog"></i>
                PARAMETRES
            </button>
            <button class="sidebar-btn logout" onclick="logout()">
                <i class="fas fa-sign-out-alt"></i>
                Déconnexion
            </button>
        </aside>

        <section class="content">
            <div class="details-container">
                <!-- En-tête de la page -->
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-<?php echo $sessionType === 'vote' ? 'vote-yea' : 'user-plus'; ?>"></i>
                        Détails de Session
                    </h1>
                    <a href="elections.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i>
                        Retour aux Élections
                    </a>
                </div>

                <!-- Vue d'ensemble de la session -->
                <div class="session-overview">
                    <div class="session-header">
                        <div class="session-info">
                            <h2>
                                <?php echo $sessionType === 'vote' ? 'Élection' : 'Candidatures'; ?> 
                                <?php echo htmlspecialchars($session['election_type']); ?>
                                <?php if ($session['club_name']): ?>
                                    - <?php echo htmlspecialchars($session['club_name']); ?>
                                <?php endif; ?>
                            </h2>
                            <p>Créée par <?php echo htmlspecialchars($session['created_by']); ?> le <?php echo date('d/m/Y à H:i', strtotime($session['created_at'])); ?></p>
                        </div>
                        <span class="session-status status-<?php echo $status; ?>"><?php echo $status_text; ?></span>
                    </div>

                    <!-- Statistiques -->
                    <div class="session-stats">
                        <div class="stat-card">
                            <h3><?php echo date('d/m/Y', strtotime($session['start_time'])); ?></h3>
                            <p>Date de début</p>
                        </div>
                        <div class="stat-card">
                            <h3><?php echo date('d/m/Y', strtotime($session['end_time'])); ?></h3>
                            <p>Date de fin</p>
                        </div>
                        <div class="stat-card">
                            <h3><?php echo $start->diff($end)->format('%j j %h h'); ?></h3>
                            <p>Durée totale</p>
                        </div>
                        <?php if ($sessionType === 'vote'): ?>
                            <div class="stat-card">
                                <h3><?php echo $session['vote_count']; ?></h3>
                                <p>Votes exprimés</p>
                            </div>
                            <div class="stat-card">
                                <h3><?php echo $session['total_students'] > 0 ? round(($session['vote_count'] / $session['total_students']) * 100, 1) : 0; ?>%</h3>
                                <p>Taux de participation</p>
                            </div>
                        <?php else: ?>
                            <div class="stat-card">
                                <h3><?php echo $session['candidate_count']; ?></h3>
                                <p>Candidats approuvés</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Détails de la session -->
                    <div class="session-details">
                        <div class="detail-section">
                            <h3><i class="fas fa-clock"></i> Informations Temporelles</h3>
                            <div class="detail-item">
                                <span class="detail-label">Heure de début</span>
                                <span class="detail-value"><?php echo date('H:i', strtotime($session['start_time'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Heure de fin</span>
                                <span class="detail-value"><?php echo date('H:i', strtotime($session['end_time'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Statut</span>
                                <span class="detail-value"><?php echo $status_text; ?></span>
                            </div>
                            <?php if ($status === 'active'): ?>
                                <div class="detail-item">
                                    <span class="detail-label">Temps restant</span>
                                    <span class="detail-value" id="timeRemaining">Calcul en cours...</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="detail-section">
                            <h3><i class="fas fa-info-circle"></i> Informations Générales</h3>
                            <div class="detail-item">
                                <span class="detail-label">Type d'élection</span>
                                <span class="detail-value"><?php echo htmlspecialchars($session['election_type']); ?></span>
                            </div>
                            <?php if ($session['club_name']): ?>
                                <div class="detail-item">
                                    <span class="detail-label">Club</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($session['club_name']); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="detail-item">
                                <span class="detail-label">Créé par</span>
                                <span class="detail-value"><?php echo htmlspecialchars($session['created_by']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Date de création</span>
                                <span class="detail-value"><?php echo date('d/m/Y H:i', strtotime($session['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contenu spécifique selon le type -->
                <?php if ($sessionType === 'vote'): ?>
                    <!-- Statistiques des candidats -->
                    <div class="content-section">
                        <h2><i class="fas fa-chart-bar"></i> Statistiques par Candidat</h2>
                        <?php if (empty($candidateStats)): ?>
                            <div class="empty-state">
                                <i class="fas fa-chart-bar"></i>
                                <h3>Aucun candidat</h3>
                                <p>Aucun candidat n'a encore été approuvé pour cette élection</p>
                            </div>
                        <?php else: ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Candidat</th>
                                        <th>Programme</th>
                                        <th>Votes reçus</th>
                                        <th>Pourcentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($candidateStats as $candidate): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($candidate['candidate_name']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($candidate['programme'], 0, 50)) . (strlen($candidate['programme']) > 50 ? '...' : ''); ?></td>
                                            <td><?php echo $candidate['vote_count']; ?></td>
                                            <td><?php echo $session['vote_count'] > 0 ? round(($candidate['vote_count'] / $session['vote_count']) * 100, 1) : 0; ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>

                    <!-- Votes récents -->
                    <div class="content-section">
                        <h2><i class="fas fa-history"></i> Votes Récents</h2>
                        <?php if (empty($recentVotes)): ?>
                            <div class="empty-state">
                                <i class="fas fa-vote-yea"></i>
                                <h3>Aucun vote</h3>
                                <p>Aucun vote n'a encore été exprimé</p>
                            </div>
                        <?php else: ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Électeur</th>
                                        <th>Candidat</th>
                                        <th>Date et heure</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentVotes as $vote): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($vote['voter_name']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($vote['candidate_programme'], 0, 50)) . (strlen($vote['candidate_programme']) > 50 ? '...' : ''); ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($vote['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <!-- Candidatures -->
                    <div class="content-section">
                        <h2><i class="fas fa-users"></i> Candidatures</h2>
                        <?php if (empty($candidatures)): ?>
                            <div class="empty-state">
                                <i class="fas fa-user-plus"></i>
                                <h3>Aucune candidature</h3>
                                <p>Aucune candidature n'a encore été soumise</p>
                            </div>
                        <?php else: ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Candidat</th>
                                        <th>Email</th>
                                        <th>Programme</th>
                                        <th>Statut</th>
                                        <th>Date de soumission</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($candidatures as $candidature): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($candidature['candidate_name']); ?></td>
                                            <td><?php echo htmlspecialchars($candidature['candidate_email']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($candidature['programme'], 0, 50)) . (strlen($candidature['programme']) > 50 ? '...' : ''); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $candidature['status']; ?>">
                                                    <?php echo ucfirst($candidature['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($candidature['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <?php include('../components/footer_admin.php'); ?>
    
    <script src="../assets/js/include.js"></script>
    <script src="../assets/js/state.js"></script>
    <script>
        // Calcul du temps restant pour les sessions actives
        function updateTimeRemaining() {
            const timeRemainingElement = document.getElementById('timeRemaining');
            if (!timeRemainingElement) return;
            
            const endTime = new Date('<?php echo $session['end_time']; ?>');
            const now = new Date();
            const diff = endTime - now;
            
            if (diff <= 0) {
                timeRemainingElement.textContent = 'Terminée';
                return;
            }
            
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            
            let timeString = '';
            if (days > 0) timeString += days + 'j ';
            if (hours > 0) timeString += hours + 'h ';
            timeString += minutes + 'm';
            
            timeRemainingElement.textContent = timeString;
        }
        
        // Mettre à jour le temps restant toutes les minutes
        if (document.getElementById('timeRemaining')) {
            updateTimeRemaining();
            setInterval(updateTimeRemaining, 60000);
        }
        
        function logout() {
            if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
                window.location.href = '../logout.php';
            }
        }
    </script>
</body>

</html> 