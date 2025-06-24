<?php
// Inclure la configuration de la base de données
require_once('../config/database.php');

// Vérifier que l'utilisateur est connecté et est admin
requireRole('admin');

// Récupérer les statistiques pour le dashboard
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

// Élections récentes
$stmt = $pdo->query("
    SELECT 
        vs.id,
        vs.start_time,
        vs.end_time,
        et.name as election_type,
        c.name as club_name,
        (SELECT COUNT(*) FROM votes v WHERE v.vote_session_id = vs.id) as vote_count,
        (SELECT COUNT(*) FROM users WHERE role = 'student' AND is_active = 1) as total_students
    FROM vote_sessions vs
    LEFT JOIN election_types et ON vs.election_type_id = et.id
    LEFT JOIN clubs c ON vs.club_id = c.id
    WHERE vs.is_active = 1
    ORDER BY vs.start_time DESC
    LIMIT 5
");
$recent_elections = $stmt->fetchAll();

// Données de participation pour le graphique
$stmt = $pdo->query("
    SELECT 
        DATE(created_at) as vote_date,
        COUNT(*) as vote_count
    FROM votes 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY vote_date
");
$participation_data = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrateur - Vote ENSAE</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/admin_accueil.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
</head>

<body>
    <?php include('../components/header_admin.php'); ?>

    <div class="main-content">
        <aside class="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-user-shield"></i>
                <h3>Administration</h3>
            </div>
            <button class="sidebar-btn active" id="btn-dashboard">
                <i class="fas fa-tachometer-alt"></i>
                DASHBOARD
            </button>
            <button class="sidebar-btn" id="btn-gestion-elections">
                <i class="fas fa-vote-yea"></i>
                GESTION DES ELECTIONS
            </button>
            <button class="sidebar-btn" id="btn-gestion-candidats">
                <i class="fas fa-users"></i>
                GESTION DES CANDIDATS
            </button>
            <button class="sidebar-btn" id="btn-gestion-comites">
                <i class="fas fa-user-tie"></i>
                GESTION DES COMITES
            </button>
            <button class="sidebar-btn" id="btn-param-admin">
                <i class="fas fa-cog"></i>
                PARAMETRES
            </button>
            <button class="sidebar-btn" id="btn-profil">
                <i class="fas fa-user"></i>
                MON PROFIL
            </button>
            <button class="sidebar-btn logout" id="logoutBtn">
                <i class="fas fa-sign-out-alt"></i>
                Déconnexion
            </button>
        </aside>

        <section class="content" id="admin-content">
            <!-- Dashboard Principal -->
            <div class="dashboard-container" id="dashboard-view">
                <div class="dashboard-header">
                    <h1><i class="fas fa-tachometer-alt"></i> Dashboard Administrateur</h1>
                    <p>Vue d'ensemble du système de vote ENSAE</p>
                </div>

                <!-- Statistiques principales -->
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

                <!-- Actions rapides -->
                <div class="quick-actions">
                    <h2><i class="fas fa-bolt"></i> Actions Rapides</h2>
                    <div class="actions-grid">
                        <button class="action-card" id="startVotesBtn">
                            <i class="fas fa-play-circle"></i>
                            <h3>Démarrer un Vote</h3>
                            <p>Lancer une nouvelle élection</p>
                        </button>
                        <button class="action-card" id="startCandBtn">
                            <i class="fas fa-user-plus"></i>
                            <h3>Ouvrir Candidatures</h3>
                            <p>Autoriser les candidatures</p>
                        </button>
                        <button class="action-card">
                            <i class="fas fa-chart-bar"></i>
                            <h3>Voir Statistiques</h3>
                            <p>Analyser les résultats</p>
                        </button>
                        <button class="action-card">
                            <i class="fas fa-cog"></i>
                            <h3>Paramètres</h3>
                            <p>Configurer le système</p>
                        </button>
                    </div>
                </div>

                <!-- Élections récentes -->
                <div class="recent-elections">
                    <h2><i class="fas fa-clock"></i> Élections Récentes</h2>
                    <div class="elections-list">
                        <?php if (empty($recent_elections)): ?>
                        <div class="election-item">
                            <div class="election-status upcoming">Aucune élection</div>
                            <div class="election-info">
                                <h4>Aucune élection programmée</h4>
                                <p>Créez une nouvelle élection pour commencer</p>
                                <span class="election-time">Utilisez le bouton "Démarrer un Vote"</span>
                            </div>
                            <div class="election-stats">
                                <span>0 votes</span>
                                <span>0% participation</span>
                            </div>
                        </div>
                        <?php else: ?>
                        <?php foreach ($recent_elections as $election): ?>
                        <?php
                                $now = new DateTime();
                                $start = new DateTime($election['start_time']);
                                $end = new DateTime($election['end_time']);
                                $participation_rate = $election['total_students'] > 0 ? 
                                    round(($election['vote_count'] / $election['total_students']) * 100, 1) : 0;
                                
                                if ($now >= $start && $now <= $end) {
                                    $status = 'active';
                                    $status_text = 'En cours';
                                    $time_text = 'Termine dans ' . $now->diff($end)->format('%h h %i m');
                                } elseif ($now < $start) {
                                    $status = 'upcoming';
                                    $status_text = 'À venir';
                                    $time_text = 'Démarre dans ' . $now->diff($start)->format('%j j %h h');
                                } else {
                                    $status = 'completed';
                                    $status_text = 'Terminée';
                                    $time_text = 'Terminée le ' . $end->format('d/m/Y');
                                }
                                ?>
                        <div class="election-item <?php echo $status; ?>">
                            <div class="election-status <?php echo $status; ?>"><?php echo $status_text; ?></div>
                            <div class="election-info">
                                <h4>Élection <?php echo htmlspecialchars($election['election_type']); ?>
                                    <?php if ($election['club_name']): ?>
                                    - <?php echo htmlspecialchars($election['club_name']); ?>
                                    <?php endif; ?>
                                </h4>
                                <p><?php echo htmlspecialchars($election['election_type']); ?></p>
                                <span class="election-time"><?php echo $time_text; ?></span>
                            </div>
                            <div class="election-stats">
                                <span><?php echo $election['vote_count']; ?> votes</span>
                                <span><?php echo $participation_rate; ?>% participation</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Graphique de participation -->
                <div class="participation-chart">
                    <h2><i class="fas fa-chart-line"></i> Participation aux Votes</h2>
                    <div class="chart-container">
                        <canvas id="participationChart"></canvas>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Modal pour démarrer les votes -->
    <div id="startVotesModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-play-circle"></i> Démarrer une Élection</h2>
                <span class="close-btn" id="closeStartVotes">&times;</span>
            </div>
            <div id="step1">
                <div class="form-group">
                    <label for="voteType"><i class="fas fa-tag"></i> Type d'élection</label>
                    <select id="voteType">
                        <option value="" selected disabled>Choisir un type</option>
                        <option value="aes">AES</option>
                        <option value="club">Club</option>
                        <option value="classe">Classe</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button class="admin-btn" id="nextToDates">Suivant <i class="fas fa-arrow-right"></i></button>
                </div>
            </div>
            <div id="step2" style="display:none;">
                <div class="form-group">
                    <label for="startVote"><i class="fas fa-calendar-plus"></i> Date et heure de début</label>
                    <input type="datetime-local" id="startVote">
                </div>
                <div class="form-group">
                    <label for="endVote"><i class="fas fa-calendar-minus"></i> Date et heure de fin</label>
                    <input type="datetime-local" id="endVote">
                </div>
                <div class="form-actions">
                    <button class="admin-btn danger" id="cancelVoteModal"><i class="fas fa-times"></i> Annuler</button>
                    <button class="admin-btn" id="validateVoteModal"><i class="fas fa-check"></i> Valider</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal pour démarrer les candidatures -->
    <div id="startCandModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-plus"></i> Ouvrir les Candidatures</h2>
                <span class="close-btn" id="closeStartCand">&times;</span>
            </div>
            <div id="candStep1">
                <div class="form-group">
                    <label for="candType"><i class="fas fa-tag"></i> Catégorie</label>
                    <select id="candType">
                        <option value="" selected disabled>Choisir une catégorie</option>
                        <option value="aes">AES</option>
                        <option value="club">Club</option>
                        <option value="classe">Classe</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button class="admin-btn" id="candNextToDate">Suivant <i class="fas fa-arrow-right"></i></button>
                </div>
            </div>
            <div id="candStep2" style="display:none;">
                <div class="form-group">
                    <label for="startCandDate"><i class="fas fa-calendar-plus"></i> Date et heure de début</label>
                    <input type="datetime-local" id="startCandDate">
                </div>
                <div class="form-group">
                    <label for="endCandDate"><i class="fas fa-calendar-minus"></i> Date et heure de fin</label>
                    <input type="datetime-local" id="endCandDate">
                </div>
                <div class="form-actions">
                    <button class="admin-btn danger" id="cancelCandModal"><i class="fas fa-times"></i> Annuler</button>
                    <button class="admin-btn" id="validateCandModal"><i class="fas fa-check"></i> Valider</button>
                </div>
            </div>
        </div>
    </div>

    <?php include('../components/footer_admin.php'); ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/js/include.js"></script>
    <script src="../assets/js/state.js"></script>
    <script src="../assets/js/admin_accueil.js"></script>

    <!-- Données pour le graphique -->
    <script>
    const participationData = <?php echo json_encode($participation_data); ?>;
    </script>

</body>

</html>