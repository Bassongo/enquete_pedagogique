<?php
// Inclure la configuration de la base de données
require_once('../config/database.php');

// Vérifier que l'utilisateur est connecté
requireLogin();

// Récupérer les types d'élections disponibles
$stmt = $pdo->query("SELECT id, name FROM election_types WHERE is_active = 1 ORDER BY name");
$electionTypes = $stmt->fetchAll();

// Récupérer les statistiques globales
$stats = [];

// Total des étudiants
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'student' AND is_active = 1");
$stats['total_students'] = $stmt->fetch()['total'];

// Total des votes
$stmt = $pdo->query("SELECT COUNT(*) as total FROM votes");
$stats['total_votes'] = $stmt->fetch()['total'];

// Total des candidats
$stmt = $pdo->query("SELECT COUNT(*) as total FROM candidatures WHERE status = 'approved'");
$stats['total_candidates'] = $stmt->fetch()['total'];

// Élections actives
$stmt = $pdo->query("
    SELECT COUNT(*) as total 
    FROM vote_sessions 
    WHERE is_active = 1 
    AND start_time <= NOW() 
    AND end_time >= NOW()
");
$stats['active_elections'] = $stmt->fetch()['total'];

// Participation par classe
$stmt = $pdo->query("
    SELECT 
        u.classe,
        COUNT(DISTINCT v.voter_id) as voters,
        COUNT(*) as total_votes
    FROM users u
    LEFT JOIN votes v ON u.id = v.voter_id
    WHERE u.role = 'student' AND u.is_active = 1
    GROUP BY u.classe
    ORDER BY u.classe
");
$participationByClass = $stmt->fetchAll();

// Données de participation des 7 derniers jours
$stmt = $pdo->query("
    SELECT 
        DATE(created_at) as vote_date,
        COUNT(*) as vote_count
    FROM votes 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY vote_date
");
$participationData = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Statistiques des Votes - Vote ENSAE</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/statistique.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    body {
        background: #f4f7fb;
        font-family: 'Montserrat', Arial, sans-serif;
    }

    .main-stats {
        max-width: 1200px;
        margin: 2rem auto;
        padding: 2.5rem 1.5rem;
        background: #fff;
        border-radius: 22px;
        box-shadow: 0 8px 32px 0 rgba(37, 99, 235, 0.10);
    }

    .stats-header {
        text-align: center;
        margin-bottom: 2.5rem;
    }

    .stats-header h1 {
        color: #2563eb;
        font-size: 2.7em;
        font-weight: 700;
        margin-bottom: 10px;
        letter-spacing: -1px;
    }

    .stats-header p {
        color: #555;
        font-size: 1.15em;
    }

    .stats-overview {
        margin-bottom: 2.5rem;
    }

    .stats-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 2rem;
        justify-content: center;
    }

    .stat-card {
        background: linear-gradient(135deg, #e0e7ff 60%, #f0fdfa 100%);
        border-radius: 18px;
        box-shadow: 0 2px 10px #2563eb11;
        padding: 2rem 2.5rem;
        min-width: 220px;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        overflow: hidden;
        transition: box-shadow 0.2s;
    }

    .stat-card:hover {
        box-shadow: 0 6px 24px #2563eb22;
    }

    .stat-icon {
        font-size: 2.5em;
        margin-bottom: 0.7rem;
        color: #2563eb;
        background: #e0e7ff;
        border-radius: 50%;
        padding: 0.5em;
        box-shadow: 0 2px 8px #2563eb11;
    }

    .stat-content h3 {
        font-size: 2.1rem;
        font-weight: 700;
        color: #2563eb;
        margin-bottom: 0.3rem;
    }

    .stat-content p {
        font-size: 1.1rem;
        color: #444;
        margin-bottom: 0.2rem;
    }

    .stat-change {
        font-size: 0.95em;
        font-weight: 600;
        border-radius: 8px;
        padding: 2px 10px;
        margin-top: 0.2em;
        display: inline-block;
    }

    .stat-change.positive {
        background: #e0f7e9;
        color: #1abc9c;
    }

    .stat-change.neutral {
        background: #f3f4f6;
        color: #888;
    }

    .stat-change.negative {
        background: #ffeaea;
        color: #e74c3c;
    }

    .charts-section {
        margin-bottom: 2.5rem;
    }

    .charts-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 2rem;
        justify-content: center;
    }

    .chart-container {
        background: #f8fafc;
        border-radius: 16px;
        box-shadow: 0 2px 10px #2563eb11;
        padding: 2rem 1.5rem;
        min-width: 320px;
        flex: 1 1 350px;
        max-width: 540px;
    }

    .chart-container h3 {
        color: #2563eb;
        font-size: 1.15em;
        margin-bottom: 1.2em;
        text-align: center;
    }

    .chart-wrapper {
        width: 100%;
        min-height: 260px;
    }

    .detail-section {
        margin-bottom: 2.5rem;
    }

    .detail-section h3 {
        color: #2563eb;
        font-size: 1.15em;
        margin-bottom: 1.2em;
    }

    .detail-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 2rem;
        justify-content: center;
    }

    .detail-card {
        background: linear-gradient(135deg, #e0e7ff 60%, #f0fdfa 100%);
        border-radius: 16px;
        box-shadow: 0 2px 10px #2563eb11;
        padding: 1.5rem 1.2rem;
        min-width: 260px;
        max-width: 340px;
        flex: 1 1 320px;
        margin-bottom: 1.5rem;
        position: relative;
        overflow: hidden;
    }

    .detail-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }

    .detail-header h4 {
        color: #2563eb;
        font-size: 1.1em;
        font-weight: 700;
        margin: 0;
    }

    .participation-rate {
        background: #e0f7e9;
        color: #1abc9c;
        font-weight: 600;
        border-radius: 8px;
        padding: 2px 10px;
        font-size: 0.95em;
    }

    .detail-stats {
        display: flex;
        gap: 1.5rem;
        margin-bottom: 0.7rem;
    }

    .stat-item {
        text-align: center;
    }

    .stat-label {
        color: #888;
        font-size: 0.98em;
    }

    .stat-value {
        font-size: 1.15em;
        font-weight: 600;
        color: #2563eb;
    }

    .progress-bar {
        background: #e0e7ff;
        border-radius: 8px;
        height: 10px;
        width: 100%;
        margin-top: 0.5em;
    }

    .progress-fill {
        background: linear-gradient(90deg, #2563eb, #1abc9c);
        height: 100%;
        border-radius: 8px;
        transition: width 0.7s cubic-bezier(.4, 2, .6, 1);
    }

    .recent-elections {
        margin-bottom: 2.5rem;
    }

    .recent-elections h3 {
        color: #2563eb;
        font-size: 1.15em;
        margin-bottom: 1.2em;
    }

    .elections-list {
        display: flex;
        flex-wrap: wrap;
        gap: 1.5rem;
    }

    .election-card {
        background: #f8fafc;
        border-radius: 12px;
        box-shadow: 0 2px 8px #2563eb11;
        padding: 1.2rem 1rem;
        min-width: 220px;
        flex: 1 1 220px;
    }

    .election-title {
        color: #2563eb;
        font-weight: 600;
        font-size: 1.05em;
        margin-bottom: 0.5em;
    }

    .election-date {
        color: #888;
        font-size: 0.95em;
        margin-bottom: 0.2em;
    }

    .election-status {
        font-size: 0.95em;
        font-weight: 600;
        border-radius: 8px;
        padding: 2px 10px;
        display: inline-block;
    }

    .election-status.active {
        background: #e0f7e9;
        color: #1abc9c;
    }

    .election-status.closed {
        background: #ffeaea;
        color: #e74c3c;
    }

    @media (max-width: 900px) {
        .main-stats {
            padding: 1rem 0.2rem 2rem 0.2rem;
        }

        .stats-grid,
        .detail-grid,
        .charts-grid {
            flex-direction: column;
            align-items: center;
        }

        .stat-card,
        .detail-card,
        .chart-container {
            min-width: 160px;
            max-width: 100%;
        }
    }
    </style>
</head>

<body>
    <?php include('../components/header.php'); ?>

    <main class="main-stats">
        <div class="stats-header">
            <h1><i class="fas fa-chart-bar"></i> Tableau de Bord des Votes</h1>
            <p>Statistiques détaillées et visuelles du système de vote ENSAE</p>
        </div>

        <!-- Statistiques globales -->
        <section class="stats-overview">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['total_students']); ?></h3>
                        <p>Étudiants inscrits</p>
                        <span class="stat-change positive">Actifs</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-vote-yea"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['total_votes']); ?></h3>
                        <p>Votes exprimés</p>
                        <span class="stat-change positive">Total</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['total_candidates']); ?></h3>
                        <p>Candidats approuvés</p>
                        <span class="stat-change neutral">Validés</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['active_elections']); ?></h3>
                        <p>Élections en cours</p>
                        <span class="stat-change positive">Actives</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Sélecteur du type d'élection -->
        <section class="selection-section" style="margin-bottom:2.5rem;">
            <div class="selection-container" style="text-align:center;">
                <label for="type-stats" style="font-weight:600; color:#2563eb;"><i class="fas fa-filter"></i> Type
                    d'élection :</label>
                <select id="type-stats"
                    style="margin-left:10px; padding:7px 15px; border-radius:8px; border:1px solid #e1e5e9; font-size:1em;">
                    <option value="">Tous les types</option>
                    <?php foreach ($electionTypes as $type): ?>
                    <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </section>

        <!-- Graphiques -->
        <section class="charts-section">
            <div class="charts-grid">
                <!-- Graphique de participation -->
                <div class="chart-container">
                    <h3><i class="fas fa-chart-line"></i> Participation aux Votes (7 derniers jours)</h3>
                    <div class="chart-wrapper">
                        <canvas id="participationChart"></canvas>
                    </div>
                </div>

                <!-- Graphique de participation par classe -->
                <div class="chart-container">
                    <h3><i class="fas fa-chart-pie"></i> Participation par Classe</h3>
                    <div class="chart-wrapper">
                        <canvas id="classParticipationChart"></canvas>
                    </div>
                </div>
            </div>
        </section>

        <!-- Détail par classe -->
        <section class="detail-section">
            <h3><i class="fas fa-list"></i> Détail par Classe</h3>
            <div class="detail-grid">
                <?php foreach ($participationByClass as $class): ?>
                <?php 
                    $participationRate = $stats['total_students'] > 0 ? 
                        round(($class['voters'] / $stats['total_students']) * 100, 1) : 0;
                ?>
                <div class="detail-card">
                    <div class="detail-header">
                        <h4>Classe <?php echo htmlspecialchars($class['classe']); ?></h4>
                        <span class="participation-rate"><?php echo $participationRate; ?>%</span>
                    </div>
                    <div class="detail-stats">
                        <div class="stat-item">
                            <span class="stat-label">Votants :</span>
                            <span class="stat-value"><?php echo $class['voters']; ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Votes totaux :</span>
                            <span class="stat-value"><?php echo $class['total_votes']; ?></span>
                        </div>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $participationRate; ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Élections récentes -->
        <section class="recent-elections">
            <h3><i class="fas fa-clock"></i> Élections Récentes</h3>
            <div id="recent-elections-list" class="elections-list">
                <!-- Chargé dynamiquement via AJAX -->
            </div>
        </section>
    </main>

    <?php include('../components/footer.php'); ?>

    <script src="../assets/js/state.js"></script>
    <script>
    // Chart.js - Participation 7 jours
    const participationData = <?php echo json_encode($participationData); ?>;
    const participationByClass = <?php echo json_encode($participationByClass); ?>;
    // Participation 7 jours
    const ctx1 = document.getElementById('participationChart').getContext('2d');
    new Chart(ctx1, {
        type: 'line',
        data: {
            labels: participationData.map(d => d.vote_date),
            datasets: [{
                label: 'Votes par jour',
                data: participationData.map(d => d.vote_count),
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37,99,235,0.08)',
                tension: 0.3,
                fill: true,
                pointRadius: 5,
                pointHoverRadius: 8,
                pointBackgroundColor: '#2563eb',
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    enabled: true
                },
                title: {
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
    // Chart.js - Participation par classe
    const ctx2 = document.getElementById('classParticipationChart').getContext('2d');
    new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: participationByClass.map(c => c.classe),
            datasets: [{
                label: 'Votants',
                data: participationByClass.map(c => c.voters),
                backgroundColor: ['#2563eb', '#1abc9c', '#fbbf24', '#f472b6', '#a3e635', '#f87171'],
                borderWidth: 2,
                borderColor: '#fff',
            }]
        },
        options: {
            responsive: true,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#2563eb',
                        font: {
                            weight: 600
                        }
                    }
                },
                tooltip: {
                    enabled: true
                },
            }
        }
    });
    </script>
</body>

</html>