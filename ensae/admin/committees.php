<?php
// Inclure la configuration de la base de données
require_once('../config/database.php');

// Vérifier que l'utilisateur est connecté et est admin
requireRole('admin');

// Récupérer les paramètres de filtrage
$roleFilter = $_GET['role'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$electionTypeFilter = $_GET['election_type'] ?? '';

// Construire la requête de base pour les membres du comité
$baseQuery = "
    SELECT
        u.id,
        u.username,
        u.email,
        u.classe,
        u.role,
        u.is_active,
        u.created_at,
        u.updated_at,
        (SELECT COUNT(*) FROM activity_logs WHERE user_id = u.id) as activity_count,
        (SELECT COUNT(*) FROM vote_sessions WHERE created_by = u.id) as sessions_created,
        (SELECT COUNT(*) FROM candidature_sessions WHERE created_by = u.id) as candidature_sessions_created,
        GROUP_CONCAT(et.name SEPARATOR ', ') AS elections
    FROM users u
    LEFT JOIN committee_election_types cet ON cet.user_id = u.id
    LEFT JOIN election_types et ON cet.election_type_id = et.id
    WHERE u.role IN ('admin', 'committee')
";

$params = [];

// Appliquer les filtres
if ($roleFilter) {
    $baseQuery .= " AND u.role = ?";
    $params[] = $roleFilter;
}

if ($statusFilter) {
    if ($statusFilter === 'active') {
        $baseQuery .= " AND u.is_active = 1";
    } elseif ($statusFilter === 'inactive') {
        $baseQuery .= " AND u.is_active = 0";
    }
}

if ($electionTypeFilter) {
    $baseQuery .= " AND cet.election_type_id = ?";
    $params[] = $electionTypeFilter;
}

$baseQuery .= " GROUP BY u.id ORDER BY u.role DESC, u.created_at DESC";

$stmt = $pdo->prepare($baseQuery);
$stmt->execute($params);
$committeeMembers = $stmt->fetchAll();

// Récupérer les statistiques
$stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users WHERE role IN ('admin', 'committee') GROUP BY role");
$roleStats = $stmt->fetchAll();

$stats = [
    'admin' => 0,
    'committee' => 0,
    'total' => 0,
    'active' => 0,
    'inactive' => 0
];

foreach ($roleStats as $stat) {
    $stats[$stat['role']] = $stat['count'];
    $stats['total'] += $stat['count'];
}

$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role IN ('admin', 'committee') AND is_active = 1");
$stats['active'] = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role IN ('admin', 'committee') AND is_active = 0");
$stats['inactive'] = $stmt->fetch()['count'];

// Récupérer les types d'élections pour les filtres
$stmt = $pdo->query("SELECT id, name FROM election_types WHERE is_active = 1 ORDER BY name");
$electionTypes = $stmt->fetchAll();

// Récupérer les sessions récentes pour les statistiques
$stmt = $pdo->query("
    SELECT 
        vs.id,
        vs.start_time,
        vs.end_time,
        et.name as election_type,
        u.username as created_by
    FROM vote_sessions vs
    LEFT JOIN election_types et ON vs.election_type_id = et.id
    LEFT JOIN users u ON vs.created_by = u.id
    WHERE vs.created_by IN (SELECT id FROM users WHERE role IN ('admin', 'committee'))
    ORDER BY vs.created_at DESC
    LIMIT 5
");
$recentSessions = $stmt->fetchAll();

// Récupérer les activités récentes
$stmt = $pdo->query("
    SELECT 
        al.*,
        u.username
    FROM activity_logs al
    JOIN users u ON al.user_id = u.id
    WHERE u.role IN ('admin', 'committee')
    ORDER BY al.created_at DESC
    LIMIT 10
");
$recentActivities = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Comités - Vote ENSAE</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/admin_accueil.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <style>
        .committees-container {
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
        
        .add-member-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        }
        
        .add-member-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid #e1e5e9;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .stat-card h3 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 2.5em;
            font-weight: 700;
        }
        
        .stat-card p {
            margin: 0;
            color: #666;
            font-weight: 500;
            font-size: 1.1em;
        }
        
        .stat-admin {
            border-left: 4px solid #dc3545;
        }
        
        .stat-committee {
            border-left: 4px solid #28a745;
        }
        
        .stat-active {
            border-left: 4px solid #17a2b8;
        }
        
        .stat-inactive {
            border-left: 4px solid #6c757d;
        }
        
        .filters {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid #e1e5e9;
        }
        
        .filters-row {
            display: flex;
            gap: 20px;
            align-items: end;
            flex-wrap: wrap;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .filter-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .filter-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .filter-actions {
            display: flex;
            gap: 10px;
        }
        
        .filter-btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
        }
        
        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        
        .members-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
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
        
        .members-grid {
            display: grid;
            gap: 20px;
        }
        
        .member-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #e1e5e9;
            transition: all 0.3s ease;
        }
        
        .member-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        }
        
        .member-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .member-info h3 {
            margin: 0 0 5px 0;
            color: #333;
            font-size: 1.2em;
            font-weight: 600;
        }
        
        .member-info p {
            margin: 0;
            color: #666;
            font-size: 0.9em;
        }
        
        .member-role {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .role-admin {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .role-committee {
            background-color: #d4edda;
            color: #155724;
        }
        
        .member-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        
        .detail-label {
            font-size: 0.8em;
            color: #666;
            font-weight: 500;
        }
        
        .detail-value {
            font-size: 0.9em;
            color: #333;
            font-weight: 600;
        }
        
        .member-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            font-size: 0.8em;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%);
            color: white;
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
            color: #212529;
        }
        
        .btn-info {
            background: linear-gradient(135deg, #17a2b8 0%, #20c997 100%);
            color: white;
        }
        
        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.2);
        }
        
        .sidebar-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid #e1e5e9;
            margin-bottom: 20px;
        }
        
        .sidebar-title {
            font-size: 1.2em;
            font-weight: 600;
            color: #333;
            margin: 0 0 15px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .activity-item {
            padding: 10px 0;
            border-bottom: 1px solid #e1e5e9;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }
        
        .activity-user {
            font-weight: 600;
            color: #333;
            font-size: 0.9em;
        }
        
        .activity-time {
            font-size: 0.8em;
            color: #666;
        }
        
        .activity-action {
            font-size: 0.85em;
            color: #666;
        }
        
        .session-item {
            padding: 12px 0;
            border-bottom: 1px solid #e1e5e9;
        }
        
        .session-item:last-child {
            border-bottom: none;
        }
        
        .session-title {
            font-weight: 600;
            color: #333;
            font-size: 0.9em;
            margin-bottom: 5px;
        }
        
        .session-meta {
            font-size: 0.8em;
            color: #666;
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

        #addMemberModal .form-group {
            position: relative;
        }

        #emailSuggestions {
            position: absolute;
            left: 0;
            right: 0;
            top: calc(100% + 2px);
            background: #fff;
            border: 1px solid #e1e5e9;
            border-radius: 8px;
            max-height: 150px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }

        #emailSuggestions div {
            padding: 8px 12px;
            cursor: pointer;
        }

        #emailSuggestions div:hover {
            background: #f1f1f1;
        }
        
        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .committees-container {
                padding: 20px;
            }
            
            .page-header {
                flex-direction: column;
                gap: 20px;
                align-items: stretch;
            }
            
            .filters-row {
                flex-direction: column;
            }
            
            .member-header {
                flex-direction: column;
                gap: 10px;
            }
            
            .member-actions {
                justify-content: center;
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
            <button class="sidebar-btn" onclick="window.location.href='elections.php'">
                <i class="fas fa-vote-yea"></i>
                GESTION DES ELECTIONS
            </button>
            <button class="sidebar-btn" onclick="window.location.href='candidates.php'">
                <i class="fas fa-users"></i>
                GESTION DES CANDIDATS
            </button>
            <button class="sidebar-btn active" onclick="window.location.href='committees.php'">
                <i class="fas fa-user-tie"></i>
                GESTION DES COMITES
            </button>
            <button class="sidebar-btn" onclick="window.location.href='settings.php'">
                <i class="fas fa-cog"></i>
                PARAMETRES
            </button>
            <button class="sidebar-btn" onclick="window.location.href='profil.php'">
                <i class="fas fa-user"></i>
                MON PROFIL
            </button>
            <button class="sidebar-btn logout" onclick="logout()">
                <i class="fas fa-sign-out-alt"></i>
                Déconnexion
            </button>
        </aside>

        <section class="content">
            <div class="committees-container">
                <!-- En-tête de la page -->
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-user-tie"></i>
                        Gestion des Comités
                    </h1>
                    <button class="add-member-btn" onclick="openAddMemberModal()">
                        <i class="fas fa-plus"></i>
                        Ajouter un Membre
                    </button>
                </div>

                <!-- Statistiques -->
                <div class="stats-overview">
                    <div class="stat-card stat-admin">
                        <h3><?php echo $stats['admin']; ?></h3>
                        <p>Administrateurs</p>
                    </div>
                    <div class="stat-card stat-committee">
                        <h3><?php echo $stats['committee']; ?></h3>
                        <p>Membres de Comité</p>
                    </div>
                    <div class="stat-card stat-active">
                        <h3><?php echo $stats['active']; ?></h3>
                        <p>Membres Actifs</p>
                    </div>
                    <div class="stat-card stat-inactive">
                        <h3><?php echo $stats['inactive']; ?></h3>
                        <p>Membres Inactifs</p>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="filters">
                    <form method="GET" action="committees.php" id="filterForm">
                        <div class="filters-row">
                            <div class="filter-group">
                                <label for="roleFilter">Rôle</label>
                                <select id="roleFilter" name="role">
                                    <option value="">Tous les rôles</option>
                                    <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Administrateur</option>
                                    <option value="committee" <?php echo $roleFilter === 'committee' ? 'selected' : ''; ?>>Membre de Comité</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label for="statusFilter">Statut</label>
                                <select id="statusFilter" name="status">
                                    <option value="">Tous les statuts</option>
                                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Actif</option>
                                    <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactif</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label for="typeFilter">Type d'élection</label>
                                <select id="typeFilter" name="election_type">
                                    <option value="">Tous les types</option>
                                    <?php foreach ($electionTypes as $type): ?>
                                        <option value="<?php echo $type['id']; ?>" <?php echo $electionTypeFilter == $type['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($type['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-actions">
                                <button type="submit" class="filter-btn btn-primary">
                                    <i class="fas fa-filter"></i>
                                    Filtrer
                                </button>
                                <button type="button" class="filter-btn btn-secondary" onclick="clearFilters()">
                                    <i class="fas fa-times"></i>
                                    Effacer
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Contenu principal -->
                <div class="content-grid">
                    <!-- Section des membres -->
                    <div class="members-section">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-users"></i>
                                Membres du Comité
                            </h2>
                        </div>
                        
                        <?php if (empty($committeeMembers)): ?>
                            <div class="empty-state">
                                <i class="fas fa-user-tie"></i>
                                <h3>Aucun membre</h3>
                                <p>Aucun membre de comité ne correspond aux critères sélectionnés</p>
                            </div>
                        <?php else: ?>
                            <div class="members-grid">
                                <?php foreach ($committeeMembers as $member): ?>
                                    <div class="member-card">
                                        <div class="member-header">
                                            <div class="member-info">
                                                <h3><?php echo htmlspecialchars($member['username']); ?></h3>
                                                <p><?php echo htmlspecialchars($member['email']); ?> • <?php echo htmlspecialchars($member['classe']); ?></p>
                                            </div>
                                            <span class="member-role role-<?php echo $member['role']; ?>">
                                                <?php echo $member['role'] === 'admin' ? 'Administrateur' : 'Comité'; ?>
                                            </span>
                                        </div>
                                        
                                        <div class="member-details">
                                            <div class="detail-item">
                                                <span class="detail-label">Statut</span>
                                                <span class="detail-value">
                                                    <?php echo $member['is_active'] ? 'Actif' : 'Inactif'; ?>
                                                </span>
                                            </div>
                                            <div class="detail-item">
                                                <span class="detail-label">Activités</span>
                                                <span class="detail-value"><?php echo $member['activity_count']; ?></span>
                                            </div>
                                            <div class="detail-item">
                                                <span class="detail-label">Sessions créées</span>
                                                <span class="detail-value"><?php echo $member['sessions_created']; ?></span>
                                            </div>
                                            <?php if (!empty($member['elections'])): ?>
                                            <div class="detail-item">
                                                <span class="detail-label">Élections</span>
                                                <span class="detail-value"><?php echo htmlspecialchars($member['elections']); ?></span>
                                            </div>
                                            <?php endif; ?>
                                            <div class="detail-item">
                                                <span class="detail-label">Membre depuis</span>
                                                <span class="detail-value"><?php echo date('d/m/Y', strtotime($member['created_at'])); ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="member-actions">
                                            <button class="action-btn btn-info" onclick="viewMember(<?php echo $member['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                                Voir
                                            </button>
                                            <?php if ($member['id'] != $_SESSION['user_id']): ?>
                                                <?php if ($member['is_active']): ?>
                                                    <button class="action-btn btn-warning" onclick="deactivateMember(<?php echo $member['id']; ?>)">
                                                        <i class="fas fa-pause"></i>
                                                        Désactiver
                                                    </button>
                                                <?php else: ?>
                                                    <button class="action-btn btn-success" onclick="activateMember(<?php echo $member['id']; ?>)">
                                                        <i class="fas fa-play"></i>
                                                        Activer
                                                    </button>
                                                <?php endif; ?>
                                                <button class="action-btn btn-danger" onclick="removeMember(<?php echo $member['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                    Retirer
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Sidebar avec activités et sessions -->
                    <div class="sidebar-content">
                        <!-- Activités récentes -->
                        <div class="sidebar-section">
                            <h3 class="sidebar-title">
                                <i class="fas fa-history"></i>
                                Activités Récentes
                            </h3>
                            <?php if (empty($recentActivities)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-history"></i>
                                    <h3>Aucune activité</h3>
                                    <p>Aucune activité récente</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recentActivities as $activity): ?>
                                    <div class="activity-item">
                                        <div class="activity-header">
                                            <span class="activity-user"><?php echo htmlspecialchars($activity['username']); ?></span>
                                            <span class="activity-time"><?php echo date('d/m H:i', strtotime($activity['created_at'])); ?></span>
                                        </div>
                                        <div class="activity-action"><?php echo htmlspecialchars($activity['action']); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Sessions récentes -->
                        <div class="sidebar-section">
                            <h3 class="sidebar-title">
                                <i class="fas fa-calendar"></i>
                                Sessions Récentes
                            </h3>
                            <?php if (empty($recentSessions)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-calendar"></i>
                                    <h3>Aucune session</h3>
                                    <p>Aucune session récente</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recentSessions as $session): ?>
                                    <div class="session-item">
                                        <div class="session-title">
                                            <?php echo htmlspecialchars($session['election_type']); ?>
                                        </div>
                                        <div class="session-meta">
                                            Par <?php echo htmlspecialchars($session['created_by']); ?><br>
                                            <?php echo date('d/m/Y', strtotime($session['start_time'])); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Modal d'ajout de membre -->
    <div id="addMemberModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-plus"></i> Ajouter un Membre</h2>
                <span class="close-btn" onclick="closeAddMemberModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="memberEmail"><i class="fas fa-envelope"></i> Email de l'utilisateur</label>
                    <input type="email" id="memberEmail" class="form-control" placeholder="email@ensae.sn" autocomplete="off">
                    <div id="emailSuggestions"></div>
                </div>
                <div class="form-group">
                    <label for="memberRole"><i class="fas fa-user-tag"></i> Rôle</label>
                    <select id="memberRole" class="form-control">
                        <option value="committee">Membre de Comité</option>
                        <option value="admin">Administrateur</option>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-vote-yea"></i> Types d'élection</label>
                    <?php foreach ($electionTypes as $type): ?>
                        <div>
                            <input type="checkbox" id="etype_<?php echo $type['id']; ?>" value="<?php echo $type['id']; ?>" class="election-checkbox">
                            <label for="etype_<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="form-actions">
                    <button class="admin-btn danger" onclick="closeAddMemberModal()">
                        <i class="fas fa-times"></i>
                        Annuler
                    </button>
                    <button class="admin-btn" onclick="addMember()">
                        <i class="fas fa-check"></i>
                        Ajouter
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de détails du membre -->
    <div id="memberModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user"></i> Détails du Membre</h2>
                <span class="close-btn" onclick="closeMemberModal()">&times;</span>
            </div>
            <div class="modal-body" id="memberModalBody">
                <!-- Le contenu sera chargé dynamiquement -->
            </div>
        </div>
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
    
    <script src="../assets/js/include.js"></script>
    <script src="../assets/js/state.js"></script>
    <script>
        // =====================================================
        // GESTION DES COMITÉS - FONCTIONS PRINCIPALES
        // =====================================================

        // Effacer les filtres
        function clearFilters() {
            window.location.href = 'committees.php';
        }

        // Modal d'ajout de membre
        function openAddMemberModal() {
            document.getElementById('addMemberModal').style.display = 'block';
        }

        function closeAddMemberModal() {
            document.getElementById('addMemberModal').style.display = 'none';
            document.getElementById('memberEmail').value = '';
            document.getElementById('memberRole').value = 'committee';
            document.querySelectorAll('.election-checkbox').forEach(cb => cb.checked = false);
        }

        function addMember() {
            const email = document.getElementById('memberEmail').value;
            const role = document.getElementById('memberRole').value;
            const elections = Array.from(document.querySelectorAll('.election-checkbox:checked')).map(cb => cb.value).join(',');

            if (!email) {
                showNotification('Veuillez saisir un email', 'error');
                return;
            }
            if (!elections) {
                showNotification('Veuillez sélectionner au moins un type d\'élection', 'error');
                return;
            }

            fetch('../admin/actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `action=add_committee_member&email=${encodeURIComponent(email)}&role=${role}&election_type_ids=${elections}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    closeAddMemberModal();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Erreur de connexion', 'error');
            });
        }

        // Suggestion d'emails lors de la saisie
        const emailInput = document.getElementById('memberEmail');
        const suggestionsBox = document.getElementById('emailSuggestions');
        let debounce;

        emailInput.addEventListener('input', () => {
            clearTimeout(debounce);
            const query = emailInput.value.trim();
            if (!query) {
                suggestionsBox.style.display = 'none';
                suggestionsBox.innerHTML = '';
                return;
            }
            debounce = setTimeout(() => {
                fetch('../admin/actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: `action=search_emails&query=${encodeURIComponent(query)}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success && Array.isArray(data.data) && data.data.length) {
                        suggestionsBox.innerHTML = '';
                        data.data.forEach(mail => {
                            const item = document.createElement('div');
                            item.textContent = mail;
                            item.onclick = () => {
                                emailInput.value = mail;
                                suggestionsBox.style.display = 'none';
                                suggestionsBox.innerHTML = '';
                            };
                            suggestionsBox.appendChild(item);
                        });
                        suggestionsBox.style.display = 'block';
                    } else {
                        suggestionsBox.style.display = 'none';
                        suggestionsBox.innerHTML = '';
                    }
                })
                .catch(() => {
                    suggestionsBox.style.display = 'none';
                });
            }, 300);
        });

        document.addEventListener('click', (e) => {
            if (!emailInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
                suggestionsBox.style.display = 'none';
            }
        });

        // Voir les détails d'un membre
        function viewMember(memberId) {
            fetch('../admin/actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `action=get_member_details&member_id=${memberId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const member = data.data;
                    const modalBody = document.getElementById('memberModalBody');
                    
                    modalBody.innerHTML = `
                        <div class="member-details-full">
                            <div class="detail-section">
                                <h3><i class="fas fa-user"></i> Informations Personnelles</h3>
                                <div class="detail-item">
                                    <span class="detail-label">Nom d'utilisateur</span>
                                    <span class="detail-value">${member.username}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Email</span>
                                    <span class="detail-value">${member.email}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Classe</span>
                                    <span class="detail-value">${member.classe}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Rôle</span>
                                    <span class="detail-value">${member.role === 'admin' ? 'Administrateur' : 'Membre de Comité'}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Statut</span>
                                    <span class="detail-value">${member.is_active ? 'Actif' : 'Inactif'}</span>
                                </div>
                                ${member.elections ? `
                                <div class="detail-item">
                                    <span class="detail-label">Élections</span>
                                    <span class="detail-value">${member.elections}</span>
                                </div>` : ''}
                                <div class="detail-item">
                                    <span class="detail-label">Membre depuis</span>
                                    <span class="detail-value">${formatDate(member.created_at)}</span>
                                </div>
                            </div>
                            
                            <div class="detail-section">
                                <h3><i class="fas fa-chart-bar"></i> Statistiques d'Activité</h3>
                                <div class="detail-item">
                                    <span class="detail-label">Nombre d'activités</span>
                                    <span class="detail-value">${member.activity_count}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Sessions de vote créées</span>
                                    <span class="detail-value">${member.sessions_created}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Sessions de candidature créées</span>
                                    <span class="detail-value">${member.candidature_sessions_created}</span>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    document.getElementById('memberModal').style.display = 'block';
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Erreur de connexion', 'error');
            });
        }

        function closeMemberModal() {
            document.getElementById('memberModal').style.display = 'none';
        }

        // Activer un membre
        function activateMember(memberId) {
            if (confirm('Êtes-vous sûr de vouloir activer ce membre ?')) {
                fetch('../admin/actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: `action=activate_member&member_id=${memberId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    showNotification('Erreur de connexion', 'error');
                });
            }
        }

        // Désactiver un membre
        function deactivateMember(memberId) {
            if (confirm('Êtes-vous sûr de vouloir désactiver ce membre ?')) {
                fetch('../admin/actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: `action=deactivate_member&member_id=${memberId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    showNotification('Erreur de connexion', 'error');
                });
            }
        }

        // Retirer un membre
        function removeMember(memberId) {
            if (confirm('Êtes-vous sûr de vouloir retirer ce membre du comité ? Cette action est irréversible.')) {
                fetch('../admin/actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: `action=remove_member&member_id=${memberId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    showNotification('Erreur de connexion', 'error');
                });
            }
        }

        // Fonctions utilitaires
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString('fr-FR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            `;

            // Add notification styles if not already present
            if (!document.querySelector('#notification-styles')) {
                const styles = document.createElement('style');
                styles.id = 'notification-styles';
                styles.textContent = `
                    .notification {
                        position: fixed;
                        bottom: 20px;
                        right: 20px;
                        background: white;
                        border-radius: 10px;
                        padding: 15px 20px;
                        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        z-index: 10000;
                        transform: translateX(100%);
                        opacity: 0;
                        transition: all 0.3s ease;
                        max-width: 300px;
                    }
                    .notification.success {
                        border-left: 4px solid #28a745;
                    }
                    .notification.error {
                        border-left: 4px solid #dc3545;
                    }
                    .notification.info {
                        border-left: 4px solid #17a2b8;
                    }
                    .notification i {
                        font-size: 1.2em;
                    }
                    .notification.success i {
                        color: #28a745;
                    }
                    .notification.error i {
                        color: #dc3545;
                    }
                    .notification.info i {
                        color: #17a2b8;
                    }
                `;
                document.head.appendChild(styles);
            }

            document.body.appendChild(notification);

            // Animation d'entrée
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
                notification.style.opacity = '1';
            }, 100);

            // Suppression automatique
            setTimeout(() => {
                notification.style.transform = 'translateX(100%)';
                notification.style.opacity = '0';
                setTimeout(() => {
                    if (document.body.contains(notification)) {
                        document.body.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }

        function logout() {
            if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
                window.location.href = '../logout.php';
            }
        }

        // Fonctions pour les modals du footer
        function closeStartVotesModal() {
            document.getElementById('startVotesModal').style.display = 'none';
        }

        function closeStartCandModal() {
            document.getElementById('startCandModal').style.display = 'none';
        }

        // Fermer les modals en cliquant à l'extérieur
        window.addEventListener('click', (event) => {
            const addModal = document.getElementById('addMemberModal');
            const memberModal = document.getElementById('memberModal');
            const startVotesModal = document.getElementById('startVotesModal');
            const startCandModal = document.getElementById('startCandModal');
            
            if (event.target === addModal) {
                closeAddMemberModal();
            }
            if (event.target === memberModal) {
                closeMemberModal();
            }
            if (event.target === startVotesModal) {
                closeStartVotesModal();
            }
            if (event.target === startCandModal) {
                closeStartCandModal();
            }
        });
    </script>
</body>

</html> 