<?php
// Inclure la configuration de la base de données
require_once('../config/database.php');

// Vérifier que l'utilisateur est connecté et est admin
requireRole('admin');

// Récupérer les paramètres
$sessionId = (int)($_GET['session_id'] ?? 0);
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

// Récupérer les résultats détaillés
if ($sessionType === 'vote') {
    // Récupérer les résultats par candidat
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.programme,
            u.username as candidate_name,
            u.email as candidate_email,
            COUNT(v.id) as vote_count,
            (SELECT COUNT(*) FROM votes WHERE vote_session_id = ?) as total_votes
        FROM candidatures c
        JOIN users u ON c.user_id = u.id
        LEFT JOIN votes v ON c.id = v.candidature_id AND v.vote_session_id = ?
        WHERE c.election_type_id = ? AND c.status = 'approved'
        GROUP BY c.id, c.programme, u.username, u.email
        ORDER BY vote_count DESC
    ");
    $stmt->execute([$sessionId, $sessionId, $session['election_type_id']]);
    $results = $stmt->fetchAll();
    
    // Calculer les pourcentages et rangs
    $totalVotes = $session['vote_count'];
    foreach ($results as &$result) {
        $result['percentage'] = $totalVotes > 0 ? round(($result['vote_count'] / $totalVotes) * 100, 1) : 0;
    }
    
    // Déterminer le gagnant
    if (!empty($results)) {
        $maxVotes = max(array_column($results, 'vote_count'));
        foreach ($results as &$result) {
            $result['is_winner'] = ($result['vote_count'] == $maxVotes && $maxVotes > 0);
        }
    }
    
    // Récupérer les statistiques de participation par heure
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') as hour,
            COUNT(*) as vote_count
        FROM votes 
        WHERE vote_session_id = ?
        GROUP BY DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')
        ORDER BY hour
    ");
    $stmt->execute([$sessionId]);
    $participationByHour = $stmt->fetchAll();
    
    // Récupérer les statistiques par classe
    $stmt = $pdo->prepare("
        SELECT 
            u.classe,
            COUNT(v.id) as vote_count,
            (SELECT COUNT(*) FROM users WHERE role = 'student' AND is_active = 1 AND classe = u.classe) as total_students
        FROM votes v
        JOIN users u ON v.voter_id = u.id
        WHERE v.vote_session_id = ?
        GROUP BY u.classe
        ORDER BY u.classe
    ");
    $stmt->execute([$sessionId]);
    $participationByClass = $stmt->fetchAll();
    
    foreach ($participationByClass as &$class) {
        $class['participation_rate'] = $class['total_students'] > 0 ? 
            round(($class['vote_count'] / $class['total_students']) * 100, 1) : 0;
    }
} else {
    // Pour les sessions de candidature, récupérer les candidatures
    $stmt = $pdo->prepare("
        SELECT 
            c.*,
            u.username as candidate_name,
            u.email as candidate_email,
            u.classe as candidate_class
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
    <title>Résultats de l'Élection - Vote ENSAE</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/admin_accueil.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    .results-container {
        padding: 30px;
        max-width: 1400px;
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

    .export-btn {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
        margin-left: 10px;
    }

    .export-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
    }

    .session-summary {
        background: white;
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        border: 1px solid #e1e5e9;
    }

    .summary-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 30px;
    }

    .summary-info h2 {
        margin: 0 0 10px 0;
        color: #333;
        font-size: 1.8em;
        font-weight: 600;
    }

    .summary-info p {
        margin: 0;
        color: #666;
        font-size: 1.1em;
    }

    .summary-stats {
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

    .results-section {
        background: white;
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        border: 1px solid #e1e5e9;
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }

    .section-title {
        font-size: 1.5em;
        font-weight: 600;
        color: #333;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .results-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .results-table th,
    .results-table td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid #e1e5e9;
    }

    .results-table th {
        background-color: #f8f9fa;
        font-weight: 600;
        color: #333;
    }

    .results-table tr:hover {
        background-color: #f8f9fa;
    }

    .winner-badge {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        padding: 5px 12px;
        border-radius: 15px;
        font-size: 0.8em;
        font-weight: 600;
        text-transform: uppercase;
    }

    .vote-bar {
        background: #e9ecef;
        border-radius: 10px;
        height: 20px;
        overflow: hidden;
        margin-top: 5px;
    }

    .vote-fill {
        height: 100%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 10px;
        transition: width 0.3s ease;
    }

    .charts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
        gap: 30px;
        margin-top: 30px;
    }

    .chart-container {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        border: 1px solid #e1e5e9;
    }

    .chart-title {
        font-size: 1.2em;
        font-weight: 600;
        color: #333;
        margin-bottom: 20px;
        text-align: center;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #666;
    }

    .empty-state i {
        font-size: 4em;
        color: #ddd;
        margin-bottom: 20px;
    }

    .empty-state h3 {
        margin: 0 0 10px 0;
        color: #333;
    }

    .empty-state p {
        margin: 0;
        font-size: 1.1em;
    }

    @media (max-width: 768px) {
        .results-container {
            padding: 20px;
        }

        .page-header {
            flex-direction: column;
            gap: 20px;
            align-items: stretch;
        }

        .summary-header {
            flex-direction: column;
            gap: 15px;
        }

        .summary-stats {
            grid-template-columns: 1fr;
        }

        .charts-grid {
            grid-template-columns: 1fr;
        }

        .results-table {
            font-size: 0.9em;
        }

        .results-table th,
        .results-table td {
            padding: 10px;
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
            <div class="results-container">
                <!-- En-tête de la page -->
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-chart-bar"></i>
                        Résultats de l'Élection
                    </h1>
                    <div>
                        <a href="elections.php" class="back-btn">
                            <i class="fas fa-arrow-left"></i>
                            Retour aux Élections
                        </a>
                        <button class="export-btn" onclick="exportResults()">
                            <i class="fas fa-download"></i>
                            Exporter
                        </button>
                    </div>
                </div>

                <!-- Résumé de la session -->
                <div class="session-summary">
                    <div class="summary-header">
                        <div class="summary-info">
                            <h2>
                                <?php echo $sessionType === 'vote' ? 'Élection' : 'Candidatures'; ?>
                                <?php echo htmlspecialchars($session['election_type']); ?>
                                <?php if ($session['club_name']): ?>
                                - <?php echo htmlspecialchars($session['club_name']); ?>
                                <?php endif; ?>
                            </h2>
                            <p>Résultats détaillés de la session créée par
                                <?php echo htmlspecialchars($session['created_by']); ?></p>
                        </div>
                    </div>

                    <!-- Statistiques principales -->
                    <div class="summary-stats">
                        <div class="stat-card">
                            <h3><?php echo date('d/m/Y', strtotime($session['start_time'])); ?></h3>
                            <p>Date de début</p>
                        </div>
                        <div class="stat-card">
                            <h3><?php echo date('d/m/Y', strtotime($session['end_time'])); ?></h3>
                            <p>Date de fin</p>
                        </div>
                        <?php if ($sessionType === 'vote'): ?>
                        <div class="stat-card">
                            <h3><?php echo $session['vote_count']; ?></h3>
                            <p>Votes exprimés</p>
                        </div>
                        <div class="stat-card">
                            <h3><?php echo $session['total_students'] > 0 ? round(($session['vote_count'] / $session['total_students']) * 100, 1) : 0; ?>%
                            </h3>
                            <p>Taux de participation</p>
                        </div>
                        <div class="stat-card">
                            <h3><?php echo count($results); ?></h3>
                            <p>Candidats</p>
                        </div>
                        <?php else: ?>
                        <div class="stat-card">
                            <h3><?php echo $session['candidate_count']; ?></h3>
                            <p>Candidats approuvés</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($sessionType === 'vote'): ?>
                <!-- Résultats des votes -->
                <div class="results-section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-trophy"></i>
                            Résultats Finaux
                        </h2>
                    </div>

                    <?php if (empty($results)): ?>
                    <div class="empty-state">
                        <i class="fas fa-chart-bar"></i>
                        <h3>Aucun résultat</h3>
                        <p>Aucun candidat n'a encore été approuvé pour cette élection</p>
                    </div>
                    <?php else: ?>
                    <table class="results-table">
                        <thead>
                            <tr>
                                <th>Rang</th>
                                <th>Candidat</th>
                                <th>Votes reçus</th>
                                <th>Pourcentage</th>
                                <th>Barre de progression</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                                    $rank = 1;
                                    foreach ($results as $result): 
                                    ?>
                            <tr>
                                <td><strong><?php echo $rank; ?></strong></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($result['candidate_name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($result['candidate_email']); ?></small>
                                </td>
                                <td><strong><?php echo $result['vote_count']; ?></strong></td>
                                <td><strong><?php echo $result['percentage']; ?>%</strong></td>
                                <td style="width: 200px;">
                                    <div class="vote-bar">
                                        <div class="vote-fill" style="width: <?php echo $result['percentage']; ?>%">
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($result['is_winner']): ?>
                                    <span class="winner-badge">
                                        <i class="fas fa-crown"></i> Gagnant
                                    </span>
                                    <?php else: ?>
                                    <span style="color: #666;">Participant</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php 
                                        $rank++;
                                    endforeach; 
                                    ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>

                <!-- Graphiques -->
                <?php if (!empty($results)): ?>
                <div class="charts-grid">
                    <!-- Graphique en camembert des résultats -->
                    <div class="chart-container">
                        <h3 class="chart-title">Répartition des Votes</h3>
                        <canvas id="resultsChart"></canvas>
                    </div>

                    <!-- Graphique de participation par heure -->
                    <?php if (!empty($participationByHour)): ?>
                    <div class="chart-container">
                        <h3 class="chart-title">Participation par Heure</h3>
                        <canvas id="participationChart"></canvas>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Participation par classe -->
                <?php if (!empty($participationByClass)): ?>
                <div class="chart-container">
                    <h3 class="chart-title">Participation par Classe</h3>
                    <canvas id="classParticipationChart"></canvas>
                </div>
                <?php endif; ?>
                <?php endif; ?>
                <?php else: ?>
                <!-- Résultats des candidatures -->
                <div class="results-section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-users"></i>
                            Candidatures Soumises
                        </h2>
                    </div>

                    <?php if (empty($candidatures)): ?>
                    <div class="empty-state">
                        <i class="fas fa-user-plus"></i>
                        <h3>Aucune candidature</h3>
                        <p>Aucune candidature n'a encore été soumise</p>
                    </div>
                    <?php else: ?>
                    <table class="results-table">
                        <thead>
                            <tr>
                                <th>Candidat</th>
                                <th>Email</th>
                                <th>Classe</th>
                                <th>Programme</th>
                                <th>Statut</th>
                                <th>Date de soumission</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($candidatures as $candidature): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($candidature['candidate_name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($candidature['candidate_email']); ?></td>
                                <td><?php echo htmlspecialchars($candidature['candidate_class']); ?></td>
                                <td><?php echo htmlspecialchars(substr($candidature['programme'], 0, 100)) . (strlen($candidature['programme']) > 100 ? '...' : ''); ?>
                                </td>
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

    <!-- Modal pour démarrer les votes (pour le footer) -->
    <div id="startVotesModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-play-circle"></i> Démarrer une Élection</h2>
                <span class="close-btn" onclick="closeStartVotesModal()">&times;</span>
            </div>
            <div class="modal-body">
                <p>Cette fonctionnalité est disponible depuis le Dashboard.</p>
                <div class="modal-actions">
                    <button class="admin-btn" onclick="closeStartVotesModal()">Fermer</button>
                    <button class="admin-btn" onclick="window.location.href='dashboard.php'">Aller au Dashboard</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal pour démarrer les candidatures (pour le footer) -->
    <div id="startCandModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-plus"></i> Ouvrir les Candidatures</h2>
                <span class="close-btn" onclick="closeStartCandModal()">&times;</span>
            </div>
            <div class="modal-body">
                <p>Cette fonctionnalité est disponible depuis le Dashboard.</p>
                <div class="modal-actions">
                    <button class="admin-btn" onclick="closeStartCandModal()">Fermer</button>
                    <button class="admin-btn" onclick="window.location.href='dashboard.php'">Aller au Dashboard</button>
                </div>
            </div>
        </div>
    </div>

    <?php include('../components/footer_admin.php'); ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/js/include.js"></script>
    <script src="../assets/js/state.js"></script>
    <script src="../assets/js/admin_accueil.js"></script>

    <script>
        // Fonctions pour les modals du footer
        function closeStartVotesModal() {
            document.getElementById('startVotesModal').style.display = 'none';
        }

        function closeStartCandModal() {
            document.getElementById('startCandModal').style.display = 'none';
        }

        // Fermer les modals en cliquant à l'extérieur
        window.addEventListener('click', (event) => {
            const startVotesModal = document.getElementById('startVotesModal');
            const startCandModal = document.getElementById('startCandModal');
            
            if (event.target === startVotesModal) {
                closeStartVotesModal();
            }
            if (event.target === startCandModal) {
                closeStartCandModal();
            }
        });

        // Données pour les graphiques
        const resultsData = <?php echo json_encode($results ?? []); ?>;
        const participationByHour = <?php echo json_encode($participationByHour ?? []); ?>;
        const participationByClass = <?php echo json_encode($participationByClass ?? []); ?>;

        // Graphique des résultats (camembert)
        if (resultsData.length > 0) {
            const ctx = document.getElementById('resultsChart').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: resultsData.map(r => r.candidate_name),
                    datasets: [{
                        data: resultsData.map(r => r.vote_count),
                        backgroundColor: [
                            '#667eea',
                            '#764ba2',
                            '#f093fb',
                            '#f5576c',
                            '#4facfe',
                            '#00f2fe',
                            '#43e97b',
                            '#38f9d7'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const candidate = resultsData[context.dataIndex];
                                    return `${candidate.candidate_name}: ${candidate.vote_count} votes (${candidate.percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Graphique de participation par heure
        if (participationByHour.length > 0) {
            const ctx = document.getElementById('participationChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: participationByHour.map(p => {
                        const date = new Date(p.hour);
                        return date.toLocaleString('fr-FR', {
                            day: '2-digit',
                            month: '2-digit',
                            hour: '2-digit'
                        });
                    }),
                    datasets: [{
                        label: 'Votes par heure',
                        data: participationByHour.map(p => p.vote_count),
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }

        // Graphique de participation par classe
        if (participationByClass.length > 0) {
            const ctx = document.getElementById('classParticipationChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: participationByClass.map(p => p.classe),
                    datasets: [{
                        label: 'Taux de participation (%)',
                        data: participationByClass.map(p => p.participation_rate),
                        backgroundColor: 'rgba(102, 126, 234, 0.8)',
                        borderColor: '#667eea',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    }
                }
            });
        }

        // Fonction d'export
        function exportResults() {
            // Créer un lien de téléchargement pour les résultats
            const data = {
                session: <?php echo json_encode($session); ?>,
                results: resultsData,
                participationByHour: participationByHour,
                participationByClass: participationByClass
            };

            const blob = new Blob([JSON.stringify(data, null, 2)], {
                type: 'application/json'
            });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `resultats_election_<?php echo $sessionId; ?>_<?php echo date('Y-m-d_H-i-s'); ?>.json`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);

            showNotification('Export en cours...', 'success');
        }

        function logout() {
            if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
                window.location.href = '../logout.php';
            }
        }
    </script>
</body>

</html>