<?php
// Inclure la configuration de la base de données
require_once('../config/database.php');

// Vérifier que l'utilisateur est connecté et est admin
requireRole('admin');

// Récupérer les paramètres de filtrage
$statusFilter = $_GET['status'] ?? '';
$electionTypeFilter = $_GET['election_type'] ?? '';
$sessionFilter = $_GET['session_id'] ?? '';

// Construire la requête de base
$baseQuery = "
    SELECT 
        c.*,
        u.username as candidate_name,
        u.email as candidate_email,
        u.classe as candidate_class,
        et.name as election_type,
        p.name as position_name,
        cl.name as club_name,
        cs.start_time as session_start,
        cs.end_time as session_end
    FROM candidatures c
    JOIN users u ON c.user_id = u.id
    LEFT JOIN election_types et ON c.election_type_id = et.id
    LEFT JOIN positions p ON c.position_id = p.id
    LEFT JOIN clubs cl ON c.club_id = cl.id
    LEFT JOIN candidature_sessions cs ON c.election_type_id = cs.election_type_id
    WHERE 1=1
";

$params = [];

// Appliquer les filtres
if ($statusFilter) {
    $baseQuery .= " AND c.status = ?";
    $params[] = $statusFilter;
}

if ($electionTypeFilter) {
    $baseQuery .= " AND c.election_type_id = ?";
    $params[] = $electionTypeFilter;
}

if ($sessionFilter) {
    $baseQuery .= " AND cs.id = ?";
    $params[] = $sessionFilter;
}

$baseQuery .= " ORDER BY c.created_at DESC";

$stmt = $pdo->prepare($baseQuery);
$stmt->execute($params);
$candidatures = $stmt->fetchAll();

// Récupérer les types d'élections pour les filtres
$stmt = $pdo->query("SELECT id, name FROM election_types WHERE is_active = 1 ORDER BY name");
$electionTypes = $stmt->fetchAll();

// Récupérer les sessions de candidature pour les filtres
$stmt = $pdo->query("
    SELECT 
        cs.id,
        cs.start_time,
        cs.end_time,
        et.name as election_type,
        c.name as club_name
    FROM candidature_sessions cs
    LEFT JOIN election_types et ON cs.election_type_id = et.id
    LEFT JOIN clubs c ON cs.club_id = c.id
    WHERE cs.is_active = 1
    ORDER BY cs.start_time DESC
");
$candidatureSessions = $stmt->fetchAll();

// Récupérer les positions pour les filtres
$stmt = $pdo->query("SELECT id, name, election_type_id FROM positions WHERE is_active = 1 ORDER BY name");
$positions = $stmt->fetchAll();

// Récupérer les clubs pour les filtres
$stmt = $pdo->query("SELECT id, name FROM clubs WHERE is_active = 1 ORDER BY name");
$clubs = $stmt->fetchAll();

// Statistiques
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM candidatures GROUP BY status");
$statusStats = $stmt->fetchAll();

$stats = [
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
    'total' => 0
];

foreach ($statusStats as $stat) {
    $stats[$stat['status']] = $stat['count'];
    $stats['total'] += $stat['count'];
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Candidats - Vote ENSAE</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/admin_accueil.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <style>
        .candidates-container {
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
        
        .stat-pending {
            border-left: 4px solid #ffc107;
        }
        
        .stat-approved {
            border-left: 4px solid #28a745;
        }
        
        .stat-rejected {
            border-left: 4px solid #dc3545;
        }
        
        .stat-total {
            border-left: 4px solid #667eea;
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
        
        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .filter-group select:focus,
        .filter-group input:focus {
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
        
        .candidates-grid {
            display: grid;
            gap: 20px;
        }
        
        .candidate-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid #e1e5e9;
            transition: all 0.3s ease;
        }
        
        .candidate-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .candidate-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        
        .candidate-info h3 {
            margin: 0 0 5px 0;
            color: #333;
            font-size: 1.3em;
            font-weight: 600;
        }
        
        .candidate-info p {
            margin: 0;
            color: #666;
            font-size: 0.9em;
        }
        
        .candidate-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .candidate-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .detail-label {
            font-size: 0.85em;
            color: #666;
            font-weight: 500;
        }
        
        .detail-value {
            font-size: 1em;
            color: #333;
            font-weight: 600;
        }
        
        .candidate-programme {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #e1e5e9;
        }
        
        .programme-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .programme-content {
            color: #666;
            line-height: 1.6;
            max-height: 100px;
            overflow-y: auto;
        }
        
        .candidate-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .action-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 0.85em;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
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
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
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
            .candidates-container {
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
            
            .candidate-header {
                flex-direction: column;
                gap: 15px;
            }
            
            .candidate-actions {
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
            <button class="sidebar-btn active" onclick="window.location.href='candidates.php'">
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
            <div class="candidates-container">
                <!-- En-tête de la page -->
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-users"></i>
                        Gestion des Candidats
                    </h1>
                </div>

                <!-- Statistiques -->
                <div class="stats-overview">
                    <div class="stat-card stat-total">
                        <h3><?php echo $stats['total']; ?></h3>
                        <p>Total Candidatures</p>
                    </div>
                    <div class="stat-card stat-pending">
                        <h3><?php echo $stats['pending']; ?></h3>
                        <p>En Attente</p>
                    </div>
                    <div class="stat-card stat-approved">
                        <h3><?php echo $stats['approved']; ?></h3>
                        <p>Approuvées</p>
                    </div>
                    <div class="stat-card stat-rejected">
                        <h3><?php echo $stats['rejected']; ?></h3>
                        <p>Rejetées</p>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="filters">
                    <form method="GET" action="candidates.php" id="filterForm">
                        <div class="filters-row">
                            <div class="filter-group">
                                <label for="statusFilter">Statut</label>
                                <select id="statusFilter" name="status">
                                    <option value="">Tous les statuts</option>
                                    <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>En attente</option>
                                    <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approuvées</option>
                                    <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejetées</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label for="electionTypeFilter">Type d'élection</label>
                                <select id="electionTypeFilter" name="election_type">
                                    <option value="">Tous les types</option>
                                    <?php foreach ($electionTypes as $type): ?>
                                        <option value="<?php echo $type['id']; ?>" <?php echo $electionTypeFilter == $type['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($type['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label for="sessionFilter">Session de candidature</label>
                                <select id="sessionFilter" name="session_id">
                                    <option value="">Toutes les sessions</option>
                                    <?php foreach ($candidatureSessions as $session): ?>
                                        <option value="<?php echo $session['id']; ?>" <?php echo $sessionFilter == $session['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($session['election_type']); ?>
                                            <?php if ($session['club_name']): ?>
                                                - <?php echo htmlspecialchars($session['club_name']); ?>
                                            <?php endif; ?>
                                            (<?php echo date('d/m/Y', strtotime($session['start_time'])); ?>)
                                        </option>
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

                <!-- Liste des candidatures -->
                <?php if (empty($candidatures)): ?>
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <h3>Aucune candidature</h3>
                        <p>Aucune candidature ne correspond aux critères sélectionnés</p>
                    </div>
                <?php else: ?>
                    <div class="candidates-grid">
                        <?php foreach ($candidatures as $candidature): ?>
                            <div class="candidate-card">
                                <div class="candidate-header">
                                    <div class="candidate-info">
                                        <h3><?php echo htmlspecialchars($candidature['candidate_name']); ?></h3>
                                        <p><?php echo htmlspecialchars($candidature['candidate_email']); ?> • <?php echo htmlspecialchars($candidature['candidate_class']); ?></p>
                                    </div>
                                    <span class="candidate-status status-<?php echo $candidature['status']; ?>">
                                        <?php 
                                        switch($candidature['status']) {
                                            case 'pending': echo 'En attente'; break;
                                            case 'approved': echo 'Approuvée'; break;
                                            case 'rejected': echo 'Rejetée'; break;
                                        }
                                        ?>
                                    </span>
                                </div>
                                
                                <div class="candidate-details">
                                    <div class="detail-item">
                                        <span class="detail-label">Type d'élection</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($candidature['election_type']); ?></span>
                                    </div>
                                    <?php if ($candidature['position_name']): ?>
                                        <div class="detail-item">
                                            <span class="detail-label">Position</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($candidature['position_name']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($candidature['club_name']): ?>
                                        <div class="detail-item">
                                            <span class="detail-label">Club</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($candidature['club_name']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="detail-item">
                                        <span class="detail-label">Date de soumission</span>
                                        <span class="detail-value"><?php echo date('d/m/Y H:i', strtotime($candidature['created_at'])); ?></span>
                                    </div>
                                </div>
                                
                                <div class="candidate-programme">
                                    <div class="programme-title">
                                        <i class="fas fa-file-alt"></i>
                                        Programme électoral
                                    </div>
                                    <div class="programme-content">
                                        <?php echo nl2br(htmlspecialchars($candidature['programme'])); ?>
                                    </div>
                                </div>
                                
                                <div class="candidate-actions">
                                    <button class="action-btn btn-info" onclick="viewCandidate(<?php echo $candidature['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                        Voir
                                    </button>
                                    <?php if ($candidature['status'] === 'pending'): ?>
                                        <button class="action-btn btn-success" onclick="approveCandidate(<?php echo $candidature['id']; ?>)">
                                            <i class="fas fa-check"></i>
                                            Approuver
                                        </button>
                                        <button class="action-btn btn-danger" onclick="rejectCandidate(<?php echo $candidature['id']; ?>)">
                                            <i class="fas fa-times"></i>
                                            Rejeter
                                        </button>
                                    <?php elseif ($candidature['status'] === 'approved'): ?>
                                        <button class="action-btn btn-warning" onclick="rejectCandidate(<?php echo $candidature['id']; ?>)">
                                            <i class="fas fa-times"></i>
                                            Rejeter
                                        </button>
                                    <?php elseif ($candidature['status'] === 'rejected'): ?>
                                        <button class="action-btn btn-success" onclick="approveCandidate(<?php echo $candidature['id']; ?>)">
                                            <i class="fas fa-check"></i>
                                            Approuver
                                        </button>
                                    <?php endif; ?>
                                    <button class="action-btn btn-danger" onclick="deleteCandidate(<?php echo $candidature['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                        Supprimer
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <!-- Modal de détails du candidat -->
    <div id="candidateModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user"></i> Détails du Candidat</h2>
                <span class="close-btn" onclick="closeCandidateModal()">&times;</span>
            </div>
            <div class="modal-body" id="candidateModalBody">
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
        // GESTION DES CANDIDATS - FONCTIONS PRINCIPALES
        // =====================================================

        // Effacer les filtres
        function clearFilters() {
            window.location.href = 'candidates.php';
        }

        // Voir les détails d'un candidat
        function viewCandidate(candidateId) {
            fetch('../admin/actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `action=get_candidate_details&candidate_id=${candidateId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const candidate = data.data;
                    const modalBody = document.getElementById('candidateModalBody');
                    
                    modalBody.innerHTML = `
                        <div class="candidate-details-full">
                            <div class="detail-section">
                                <h3><i class="fas fa-user"></i> Informations Personnelles</h3>
                                <div class="detail-item">
                                    <span class="detail-label">Nom d'utilisateur</span>
                                    <span class="detail-value">${candidate.candidate_name}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Email</span>
                                    <span class="detail-value">${candidate.candidate_email}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Classe</span>
                                    <span class="detail-value">${candidate.candidate_class}</span>
                                </div>
                            </div>
                            
                            <div class="detail-section">
                                <h3><i class="fas fa-vote-yea"></i> Informations de Candidature</h3>
                                <div class="detail-item">
                                    <span class="detail-label">Type d'élection</span>
                                    <span class="detail-value">${candidate.election_type}</span>
                                </div>
                                ${candidate.position_name ? `
                                    <div class="detail-item">
                                        <span class="detail-label">Position</span>
                                        <span class="detail-value">${candidate.position_name}</span>
                                    </div>
                                ` : ''}
                                ${candidate.club_name ? `
                                    <div class="detail-item">
                                        <span class="detail-label">Club</span>
                                        <span class="detail-value">${candidate.club_name}</span>
                                    </div>
                                ` : ''}
                                <div class="detail-item">
                                    <span class="detail-label">Statut</span>
                                    <span class="detail-value status-${candidate.status}">${getStatusText(candidate.status)}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Date de soumission</span>
                                    <span class="detail-value">${formatDate(candidate.created_at)}</span>
                                </div>
                            </div>
                            
                            <div class="detail-section">
                                <h3><i class="fas fa-file-alt"></i> Programme Électoral</h3>
                                <div class="programme-full">
                                    ${candidate.programme.replace(/\n/g, '<br>')}
                                </div>
                            </div>
                        </div>
                    `;
                    
                    document.getElementById('candidateModal').style.display = 'block';
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Erreur de connexion', 'error');
            });
        }

        function closeCandidateModal() {
            document.getElementById('candidateModal').style.display = 'none';
        }

        // Approuver un candidat
        function approveCandidate(candidateId) {
            if (confirm('Êtes-vous sûr de vouloir approuver cette candidature ?')) {
                fetch('../admin/actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: `action=approve_candidate&candidate_id=${candidateId}`
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

        // Rejeter un candidat
        function rejectCandidate(candidateId) {
            const reason = prompt('Raison du rejet (optionnel):');
            
            fetch('../admin/actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `action=reject_candidate&candidate_id=${candidateId}&reason=${encodeURIComponent(reason || '')}`
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

        // Supprimer un candidat
        function deleteCandidate(candidateId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cette candidature ? Cette action est irréversible.')) {
                fetch('../admin/actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: `action=delete_candidate&candidate_id=${candidateId}`
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
        function getStatusText(status) {
            switch(status) {
                case 'pending': return 'En attente';
                case 'approved': return 'Approuvée';
                case 'rejected': return 'Rejetée';
                default: return status;
            }
        }

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

        // Fermer le modal en cliquant à l'extérieur
        window.addEventListener('click', (event) => {
            const modal = document.getElementById('candidateModal');
            if (event.target === modal) {
                closeCandidateModal();
            }
        });

        // Fonctions pour les modals du footer
        function closeStartVotesModal() {
            document.getElementById('startVotesModal').style.display = 'none';
        }

        function closeStartCandModal() {
            document.getElementById('startCandModal').style.display = 'none';
        }

        // Fermer les modals en cliquant à l'extérieur
        window.addEventListener('click', (event) => {
            const modal = document.getElementById('candidateModal');
            const startVotesModal = document.getElementById('startVotesModal');
            const startCandModal = document.getElementById('startCandModal');
            
            if (event.target === modal) {
                closeCandidateModal();
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