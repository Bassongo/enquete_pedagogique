<?php
// Inclure la configuration de la base de données
require_once('../config/database.php');

// Vérifier que l'utilisateur est connecté
requireLogin();

// Récupérer les informations de l'utilisateur
$stmt = $pdo->prepare("
    SELECT 
        u.*,
        (SELECT COUNT(*) FROM candidatures WHERE user_id = u.id) as total_candidatures,
        (SELECT COUNT(*) FROM votes WHERE voter_id = u.id) as total_votes,
        (SELECT COUNT(*) FROM candidatures WHERE user_id = u.id AND status = 'approved') as candidatures_approuvees
    FROM users u 
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Récupérer les candidatures de l'utilisateur
$stmt = $pdo->prepare("
    SELECT 
        c.*,
        et.name as election_type,
        p.name as position_name,
        cl.name as club_name,
        cs.start_time as session_start,
        cs.end_time as session_end
    FROM candidatures c
    LEFT JOIN election_types et ON c.election_type_id = et.id
    LEFT JOIN positions p ON c.position_id = p.id
    LEFT JOIN clubs cl ON c.club_id = cl.id
    LEFT JOIN candidature_sessions cs ON c.candidature_session_id = cs.id
    WHERE c.user_id = ?
    ORDER BY c.created_at DESC
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$candidatures = $stmt->fetchAll();

// Récupérer l'activité récente
$stmt = $pdo->prepare("
    SELECT 
        al.*,
        DATE_FORMAT(al.created_at, '%d/%m/%Y %H:%i') as formatted_date
    FROM activity_logs al
    WHERE al.user_id = ?
    ORDER BY al.created_at DESC
    LIMIT 10
");
$stmt->execute([$_SESSION['user_id']]);
$activites = $stmt->fetchAll();

// Récupérer les votes récents
$stmt = $pdo->prepare("
    SELECT 
        v.*,
        vs.start_time as vote_session_start,
        vs.end_time as vote_session_end,
        et.name as election_type,
        cl.name as club_name,
        DATE_FORMAT(v.created_at, '%d/%m/%Y %H:%i') as vote_date
    FROM votes v
    LEFT JOIN vote_sessions vs ON v.vote_session_id = vs.id
    LEFT JOIN election_types et ON v.election_type_id = et.id
    LEFT JOIN clubs cl ON vs.club_id = cl.id
    WHERE v.voter_id = ?
    ORDER BY v.created_at DESC
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$votes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - Vote ENSAE</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/profil.css">
    <style>
    .profile-container {
        max-width: 1200px;
        margin: 40px auto;
        padding: 0 20px;
    }

    .profile-header {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border-radius: 15px;
        padding: 40px;
        text-align: center;
        margin-bottom: 30px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .profile-header h1 {
        font-size: 2.5em;
        font-weight: 700;
        margin-bottom: 10px;
    }

    .profile-header p {
        font-size: 1.1em;
        opacity: 0.9;
    }

    .profile-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        border-radius: 10px;
        padding: 25px;
        text-align: center;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-5px);
    }

    .stat-card i {
        font-size: 2.5em;
        color: #667eea;
        margin-bottom: 15px;
    }

    .stat-card h3 {
        font-size: 2em;
        font-weight: 700;
        color: #333;
        margin-bottom: 5px;
    }

    .stat-card p {
        color: #666;
        font-size: 0.9em;
    }

    .profile-tabs {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .tab-buttons {
        display: flex;
        background: #f8f9fa;
        border-bottom: 1px solid #e1e5e9;
    }

    .tab-button {
        flex: 1;
        padding: 20px;
        background: none;
        border: none;
        cursor: pointer;
        font-size: 1em;
        font-weight: 600;
        color: #666;
        transition: all 0.3s ease;
    }

    .tab-button.active {
        background: white;
        color: #667eea;
        border-bottom: 3px solid #667eea;
    }

    .tab-content {
        padding: 30px;
    }

    .tab-pane {
        display: none;
    }

    .tab-pane.active {
        display: block;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .info-card {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        border: 1px solid #e1e5e9;
    }

    .info-card h4 {
        color: #333;
        font-size: 1.1em;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .info-card h4 i {
        color: #667eea;
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #e1e5e9;
    }

    .info-item:last-child {
        border-bottom: none;
    }

    .info-label {
        font-weight: 600;
        color: #333;
    }

    .info-value {
        color: #666;
    }

    .candidature-item {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 15px;
        border: 1px solid #e1e5e9;
    }

    .candidature-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .candidature-title {
        font-weight: 600;
        color: #333;
        font-size: 1.1em;
    }

    .candidature-status {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.8em;
        font-weight: 600;
    }

    .status-pending {
        background: #fff3cd;
        color: #856404;
    }

    .status-approved {
        background: #d4edda;
        color: #155724;
    }

    .status-rejected {
        background: #f8d7da;
        color: #721c24;
    }

    .candidature-details {
        color: #666;
        font-size: 0.9em;
        margin-bottom: 10px;
    }

    .activity-item {
        display: flex;
        align-items: center;
        padding: 15px 0;
        border-bottom: 1px solid #e1e5e9;
    }

    .activity-item:last-child {
        border-bottom: none;
    }

    .activity-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #667eea;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        font-size: 0.9em;
    }

    .activity-content {
        flex: 1;
    }

    .activity-action {
        font-weight: 600;
        color: #333;
        margin-bottom: 5px;
    }

    .activity-details {
        color: #666;
        font-size: 0.9em;
    }

    .activity-time {
        color: #999;
        font-size: 0.8em;
    }

    .vote-item {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 10px;
        border: 1px solid #e1e5e9;
    }

    .vote-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .vote-type {
        font-weight: 600;
        color: #333;
    }

    .vote-date {
        color: #666;
        font-size: 0.9em;
    }

    .vote-details {
        color: #666;
        font-size: 0.9em;
    }

    .empty-state {
        text-align: center;
        padding: 40px;
        color: #666;
    }

    .empty-state i {
        font-size: 3em;
        color: #ddd;
        margin-bottom: 20px;
    }

    .btn-edit {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.9em;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }

    .btn-edit:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }

    @media (max-width: 768px) {
        .profile-stats {
            grid-template-columns: 1fr;
        }

        .tab-buttons {
            flex-direction: column;
        }

        .info-grid {
            grid-template-columns: 1fr;
        }
    }
    </style>
</head>

<body>
    <?php include('../components/header.php'); ?>

    <div class="profile-container">
        <!-- En-tête du profil -->
        <div class="profile-header">
            <h1><i class="fas fa-user-circle"></i> Mon Profil</h1>
            <p>Bienvenue, <?php echo htmlspecialchars($user['username']); ?> !</p>
        </div>

        <!-- Statistiques -->
        <div class="profile-stats">
            <div class="stat-card">
                <i class="fas fa-vote-yea"></i>
                <h3><?php echo $user['total_votes']; ?></h3>
                <p>Votes exprimés</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-user-tie"></i>
                <h3><?php echo $user['total_candidatures']; ?></h3>
                <p>Candidatures</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-check-circle"></i>
                <h3><?php echo $user['candidatures_approuvees']; ?></h3>
                <p>Candidatures approuvées</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-calendar-alt"></i>
                <h3><?php echo $user['classe']; ?></h3>
                <p>Classe</p>
            </div>
        </div>

        <!-- Onglets -->
        <div class="profile-tabs">
            <div class="tab-buttons">
                <button class="tab-button active" onclick="showTab('info')">
                    <i class="fas fa-info-circle"></i> Informations
                </button>
                <button class="tab-button" onclick="showTab('candidatures')">
                    <i class="fas fa-user-tie"></i> Mes Candidatures
                </button>
                <button class="tab-button" onclick="showTab('votes')">
                    <i class="fas fa-vote-yea"></i> Mes Votes
                </button>
                <button class="tab-button" onclick="showTab('activite')">
                    <i class="fas fa-history"></i> Activité
                </button>
            </div>

            <!-- Contenu des onglets -->
            <div class="tab-content">
                <!-- Onglet Informations -->
                <div id="info" class="tab-pane active">
                    <div class="info-grid">
                        <div class="info-card">
                            <h4><i class="fas fa-user"></i> Informations personnelles</h4>
                            <div class="info-item">
                                <span class="info-label">Nom d'utilisateur:</span>
                                <span class="info-value"><?php echo htmlspecialchars($user['username']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Email:</span>
                                <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Classe:</span>
                                <span class="info-value"><?php echo htmlspecialchars($user['classe']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Rôle:</span>
                                <span class="info-value">
                                    <?php 
                                    switch($user['role']) {
                                        case 'admin': echo 'Administrateur'; break;
                                        case 'committee': echo 'Membre du comité'; break;
                                        default: echo 'Étudiant'; break;
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>

                        <div class="info-card">
                            <h4><i class="fas fa-calendar"></i> Informations de compte</h4>
                            <div class="info-item">
                                <span class="info-label">Membre depuis:</span>
                                <span
                                    class="info-value"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Dernière mise à jour:</span>
                                <span
                                    class="info-value"><?php echo date('d/m/Y', strtotime($user['updated_at'])); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Statut:</span>
                                <span class="info-value">
                                    <?php echo $user['is_active'] ? 'Actif' : 'Inactif'; ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div style="text-align: center; margin-top: 30px;">
                        <button class="btn-edit" onclick="alert('Fonctionnalité en cours de développement')">
                            <i class="fas fa-edit"></i> Modifier mes informations
                        </button>
                    </div>
                </div>

                <!-- Onglet Candidatures -->
                <div id="candidatures" class="tab-pane">
                    <?php if (empty($candidatures)): ?>
                    <div class="empty-state">
                        <i class="fas fa-user-tie"></i>
                        <h3>Aucune candidature</h3>
                        <p>Vous n'avez pas encore soumis de candidature.</p>
                        <a href="candidat.php" class="btn-edit">
                            <i class="fas fa-plus"></i> Créer une candidature
                        </a>
                    </div>
                    <?php else: ?>
                    <?php foreach ($candidatures as $candidature): ?>
                    <div class="candidature-item">
                        <div class="candidature-header">
                            <div class="candidature-title">
                                <?php echo htmlspecialchars($candidature['election_type']); ?> -
                                <?php echo htmlspecialchars($candidature['position_name']); ?>
                                <?php if ($candidature['club_name']): ?>
                                (<?php echo htmlspecialchars($candidature['club_name']); ?>)
                                <?php endif; ?>
                            </div>
                            <span class="candidature-status status-<?php echo $candidature['status']; ?>">
                                <?php 
                                switch($candidature['status']) {
                                    case 'pending': echo 'En attente'; break;
                                    case 'approved': echo 'Approuvée'; break;
                                    case 'rejected': echo 'Rejetée'; break;
                                }
                                ?>
                            </span>
                        </div>
                        <div class="candidature-details">
                            <strong>Soumise le:</strong>
                            <?php echo date('d/m/Y', strtotime($candidature['created_at'])); ?>
                            <?php if ($candidature['session_start'] && $candidature['session_end']): ?>
                            <br><strong>Session:</strong>
                            <?php echo date('d/m/Y H:i', strtotime($candidature['session_start'])); ?> -
                            <?php echo date('d/m/Y H:i', strtotime($candidature['session_end'])); ?>
                            <?php endif; ?>
                        </div>
                        <div class="candidature-details">
                            <strong>Programme:</strong>
                            <?php echo htmlspecialchars(substr($candidature['programme'], 0, 100)) . (strlen($candidature['programme']) > 100 ? '...' : ''); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="mes-candidatures.php" class="btn-edit">
                            <i class="fas fa-list"></i> Voir toutes mes candidatures
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Onglet Votes -->
                <div id="votes" class="tab-pane">
                    <?php if (empty($votes)): ?>
                    <div class="empty-state">
                        <i class="fas fa-vote-yea"></i>
                        <h3>Aucun vote</h3>
                        <p>Vous n'avez pas encore participé à des élections.</p>
                        <a href="vote.php" class="btn-edit">
                            <i class="fas fa-vote-yea"></i> Participer aux élections
                        </a>
                    </div>
                    <?php else: ?>
                    <?php foreach ($votes as $vote): ?>
                    <div class="vote-item">
                        <div class="vote-header">
                            <div class="vote-type">
                                <?php echo htmlspecialchars($vote['election_type']); ?>
                                <?php if ($vote['club_name']): ?>
                                - <?php echo htmlspecialchars($vote['club_name']); ?>
                                <?php endif; ?>
                            </div>
                            <div class="vote-date"><?php echo $vote['vote_date']; ?></div>
                        </div>
                        <div class="vote-details">
                            Vote exprimé lors de l'élection
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="resultat.php" class="btn-edit">
                            <i class="fas fa-chart-bar"></i> Voir les résultats
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Onglet Activité -->
                <div id="activite" class="tab-pane">
                    <?php if (empty($activites)): ?>
                    <div class="empty-state">
                        <i class="fas fa-history"></i>
                        <h3>Aucune activité</h3>
                        <p>Aucune activité récente à afficher.</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($activites as $activite): ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <?php 
                            switch($activite['action']) {
                                case 'connexion': echo '<i class="fas fa-sign-in-alt"></i>'; break;
                                case 'deconnexion': echo '<i class="fas fa-sign-out-alt"></i>'; break;
                                case 'submit_candidature': echo '<i class="fas fa-user-plus"></i>'; break;
                                case 'vote': echo '<i class="fas fa-vote-yea"></i>'; break;
                                default: echo '<i class="fas fa-info"></i>'; break;
                            }
                            ?>
                        </div>
                        <div class="activity-content">
                            <div class="activity-action">
                                <?php 
                                switch($activite['action']) {
                                    case 'connexion': echo 'Connexion'; break;
                                    case 'deconnexion': echo 'Déconnexion'; break;
                                    case 'submit_candidature': echo 'Candidature soumise'; break;
                                    case 'vote': echo 'Vote exprimé'; break;
                                    default: echo ucfirst($activite['action']); break;
                                }
                                ?>
                            </div>
                            <div class="activity-details"><?php echo htmlspecialchars($activite['details']); ?></div>
                        </div>
                        <div class="activity-time"><?php echo $activite['formatted_date']; ?></div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include('../components/footer.php'); ?>

    <script src="../assets/js/include.js"></script>
    <script src="../assets/js/state.js"></script>
    <script>
    function showTab(tabName) {
        // Masquer tous les onglets
        const tabPanes = document.querySelectorAll('.tab-pane');
        tabPanes.forEach(pane => pane.classList.remove('active'));

        // Désactiver tous les boutons
        const tabButtons = document.querySelectorAll('.tab-button');
        tabButtons.forEach(button => button.classList.remove('active'));

        // Afficher l'onglet sélectionné
        document.getElementById(tabName).classList.add('active');

        // Activer le bouton correspondant
        event.target.classList.add('active');
    }
    </script>
</body>

</html>