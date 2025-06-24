<?php
// Inclure la configuration de la base de données
require_once('../config/database.php');

// Vérifier que l'utilisateur est connecté et est admin
requireRole('admin');

// Récupérer les sessions de vote
$stmt = $pdo->query("
    SELECT 
        vs.id,
        vs.start_time,
        vs.end_time,
        vs.is_active,
        et.name as election_type,
        c.name as club_name,
        u.username as created_by,
        vs.created_at,
        (SELECT COUNT(*) FROM votes v WHERE v.vote_session_id = vs.id) as vote_count,
        (SELECT COUNT(*) FROM users WHERE role = 'student' AND is_active = 1) as total_students
    FROM vote_sessions vs
    LEFT JOIN election_types et ON vs.election_type_id = et.id
    LEFT JOIN clubs c ON vs.club_id = c.id
    LEFT JOIN users u ON vs.created_by = u.id
    ORDER BY vs.created_at DESC
");
$voteSessions = $stmt->fetchAll();

// Récupérer les sessions de candidature
$stmt = $pdo->query("
    SELECT 
        cs.id,
        cs.start_time,
        cs.end_time,
        cs.is_active,
        et.name as election_type,
        c.name as club_name,
        u.username as created_by,
        cs.created_at,
        (SELECT COUNT(*) FROM candidatures cand WHERE cand.election_type_id = cs.election_type_id AND cand.status = 'approved') as candidate_count
    FROM candidature_sessions cs
    LEFT JOIN election_types et ON cs.election_type_id = et.id
    LEFT JOIN clubs c ON cs.club_id = c.id
    LEFT JOIN users u ON cs.created_by = u.id
    ORDER BY cs.created_at DESC
");
$candidatureSessions = $stmt->fetchAll();

// Récupérer les types d'élections pour les filtres
$stmt = $pdo->query("SELECT id, name FROM election_types WHERE is_active = 1 ORDER BY name");
$electionTypes = $stmt->fetchAll();

// Récupérer les clubs pour les filtres
$stmt = $pdo->query("SELECT id, name FROM clubs WHERE is_active = 1 ORDER BY name");
$clubs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Élections - Vote ENSAE</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/admin_accueil.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <style>
    .elections-container {
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

    .create-btn {
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

    .create-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }

    .tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 30px;
        border-bottom: 2px solid #e1e5e9;
    }

    .tab {
        padding: 15px 25px;
        background: none;
        border: none;
        font-size: 16px;
        font-weight: 500;
        color: #666;
        cursor: pointer;
        transition: all 0.3s;
        border-bottom: 3px solid transparent;
    }

    .tab.active {
        color: #667eea;
        border-bottom-color: #667eea;
    }

    .tab:hover {
        color: #667eea;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    .filters {
        background: white;
        border-radius: 15px;
        padding: 20px;
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
        padding: 10px 15px;
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

    .sessions-grid {
        display: grid;
        gap: 20px;
    }

    .session-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        border: 1px solid #e1e5e9;
        transition: all 0.3s ease;
    }

    .session-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    .session-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 20px;
    }

    .session-info h3 {
        margin: 0 0 5px 0;
        color: #333;
        font-size: 1.3em;
        font-weight: 600;
    }

    .session-info p {
        margin: 0;
        color: #666;
        font-size: 0.9em;
    }

    .session-status {
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 0.85em;
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

    .session-details {
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

    .session-actions {
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

    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .btn-success {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
    }

    .btn-warning {
        background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
        color: #212529;
    }

    .btn-danger {
        background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%);
        color: white;
    }

    .btn-secondary {
        background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
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
        .elections-container {
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

        .session-header {
            flex-direction: column;
            gap: 15px;
        }

        .session-actions {
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
            <div class="elections-container">
                <!-- En-tête de la page -->
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-vote-yea"></i>
                        Gestion des Élections
                    </h1>
                    <button class="create-btn" onclick="openCreateModal()">
                        <i class="fas fa-plus"></i>
                        Créer une Élection
                    </button>
                </div>

                <!-- Onglets -->
                <div class="tabs">
                    <button class="tab active" onclick="switchTab('vote-sessions')">
                        <i class="fas fa-vote-yea"></i>
                        Sessions de Vote
                        <span class="badge"><?php echo count($voteSessions); ?></span>
                    </button>
                    <button class="tab" onclick="switchTab('candidature-sessions')">
                        <i class="fas fa-user-plus"></i>
                        Sessions de Candidature
                        <span class="badge"><?php echo count($candidatureSessions); ?></span>
                    </button>
                </div>

                <!-- Filtres -->
                <div class="filters">
                    <div class="filters-row">
                        <div class="filter-group">
                            <label for="typeFilter">Type d'élection</label>
                            <select id="typeFilter" onchange="applyFilters()">
                                <option value="">Tous les types</option>
                                <?php foreach ($electionTypes as $type): ?>
                                <option value="<?php echo $type['id']; ?>">
                                    <?php echo htmlspecialchars($type['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="statusFilter">Statut</label>
                            <select id="statusFilter" onchange="applyFilters()">
                                <option value="">Tous les statuts</option>
                                <option value="active">En cours</option>
                                <option value="upcoming">À venir</option>
                                <option value="completed">Terminées</option>
                                <option value="inactive">Inactives</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="dateFilter">Date de création</label>
                            <input type="date" id="dateFilter" onchange="applyFilters()">
                        </div>
                    </div>
                </div>

                <!-- Contenu des onglets -->
                <div id="vote-sessions" class="tab-content active">
                    <?php if (empty($voteSessions)): ?>
                    <div class="empty-state">
                        <i class="fas fa-vote-yea"></i>
                        <h3>Aucune session de vote</h3>
                        <p>Créez votre première session de vote pour commencer</p>
                    </div>
                    <?php else: ?>
                    <div class="sessions-grid">
                        <?php foreach ($voteSessions as $session): ?>
                        <?php
                                $now = new DateTime();
                                $start = new DateTime($session['start_time']);
                                $end = new DateTime($session['end_time']);
                                $participation_rate = $session['total_students'] > 0 ? 
                                    round(($session['vote_count'] / $session['total_students']) * 100, 1) : 0;
                                
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
                                ?>
                        <div class="session-card" data-type="<?php echo $session['election_type']; ?>"
                            data-status="<?php echo $status; ?>">
                            <div class="session-header">
                                <div class="session-info">
                                    <h3>
                                        Élection <?php echo htmlspecialchars($session['election_type']); ?>
                                        <?php if ($session['club_name']): ?>
                                        - <?php echo htmlspecialchars($session['club_name']); ?>
                                        <?php endif; ?>
                                    </h3>
                                    <p>Créée par <?php echo htmlspecialchars($session['created_by']); ?> le
                                        <?php echo date('d/m/Y H:i', strtotime($session['created_at'])); ?></p>
                                </div>
                                <span
                                    class="session-status status-<?php echo $status; ?>"><?php echo $status_text; ?></span>
                            </div>

                            <div class="session-details">
                                <div class="detail-item">
                                    <span class="detail-label">Début</span>
                                    <span
                                        class="detail-value"><?php echo date('d/m/Y H:i', strtotime($session['start_time'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Fin</span>
                                    <span
                                        class="detail-value"><?php echo date('d/m/Y H:i', strtotime($session['end_time'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Votes exprimés</span>
                                    <span class="detail-value"><?php echo $session['vote_count']; ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Participation</span>
                                    <span class="detail-value"><?php echo $participation_rate; ?>%</span>
                                </div>
                            </div>

                            <div class="session-actions">
                                <button class="action-btn btn-primary"
                                    onclick="viewSession(<?php echo $session['id']; ?>, 'vote')">
                                    <i class="fas fa-eye"></i>
                                    Voir
                                </button>
                                <?php if ($status === 'upcoming' || $status === 'inactive'): ?>
                                <button class="action-btn btn-success"
                                    onclick="editSession(<?php echo $session['id']; ?>, 'vote')">
                                    <i class="fas fa-edit"></i>
                                    Modifier
                                </button>
                                <?php endif; ?>
                                <?php if ($status === 'active'): ?>
                                <button class="action-btn btn-warning"
                                    onclick="pauseSession(<?php echo $session['id']; ?>, 'vote')">
                                    <i class="fas fa-pause"></i>
                                    Pause
                                </button>
                                <?php endif; ?>
                                <?php if ($status === 'inactive'): ?>
                                <button class="action-btn btn-success"
                                    onclick="activateSession(<?php echo $session['id']; ?>, 'vote')">
                                    <i class="fas fa-play"></i>
                                    Activer
                                </button>
                                <?php endif; ?>
                                <button class="action-btn btn-secondary"
                                    onclick="viewResults(<?php echo $session['id']; ?>, 'vote')">
                                    <i class="fas fa-chart-bar"></i>
                                    Résultats
                                </button>
                                <button class="action-btn btn-danger"
                                    onclick="deleteSession(<?php echo $session['id']; ?>, 'vote')">
                                    <i class="fas fa-trash"></i>
                                    Supprimer
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div id="candidature-sessions" class="tab-content">
                    <?php if (empty($candidatureSessions)): ?>
                    <div class="empty-state">
                        <i class="fas fa-user-plus"></i>
                        <h3>Aucune session de candidature</h3>
                        <p>Créez votre première session de candidature pour commencer</p>
                    </div>
                    <?php else: ?>
                    <div class="sessions-grid">
                        <?php foreach ($candidatureSessions as $session): ?>
                        <?php
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
                                ?>
                        <div class="session-card" data-type="<?php echo $session['election_type']; ?>"
                            data-status="<?php echo $status; ?>">
                            <div class="session-header">
                                <div class="session-info">
                                    <h3>
                                        Candidatures <?php echo htmlspecialchars($session['election_type']); ?>
                                        <?php if ($session['club_name']): ?>
                                        - <?php echo htmlspecialchars($session['club_name']); ?>
                                        <?php endif; ?>
                                    </h3>
                                    <p>Créée par <?php echo htmlspecialchars($session['created_by']); ?> le
                                        <?php echo date('d/m/Y H:i', strtotime($session['created_at'])); ?></p>
                                </div>
                                <span
                                    class="session-status status-<?php echo $status; ?>"><?php echo $status_text; ?></span>
                            </div>

                            <div class="session-details">
                                <div class="detail-item">
                                    <span class="detail-label">Début</span>
                                    <span
                                        class="detail-value"><?php echo date('d/m/Y H:i', strtotime($session['start_time'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Fin</span>
                                    <span
                                        class="detail-value"><?php echo date('d/m/Y H:i', strtotime($session['end_time'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Candidats approuvés</span>
                                    <span class="detail-value"><?php echo $session['candidate_count']; ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Durée</span>
                                    <span
                                        class="detail-value"><?php echo $start->diff($end)->format('%j j %h h'); ?></span>
                                </div>
                            </div>

                            <div class="session-actions">
                                <button class="action-btn btn-primary"
                                    onclick="viewSession(<?php echo $session['id']; ?>, 'candidature')">
                                    <i class="fas fa-eye"></i>
                                    Voir
                                </button>
                                <?php if ($status === 'upcoming' || $status === 'inactive'): ?>
                                <button class="action-btn btn-success"
                                    onclick="editSession(<?php echo $session['id']; ?>, 'candidature')">
                                    <i class="fas fa-edit"></i>
                                    Modifier
                                </button>
                                <?php endif; ?>
                                <?php if ($status === 'active'): ?>
                                <button class="action-btn btn-warning"
                                    onclick="pauseSession(<?php echo $session['id']; ?>, 'candidature')">
                                    <i class="fas fa-pause"></i>
                                    Pause
                                </button>
                                <?php endif; ?>
                                <?php if ($status === 'inactive'): ?>
                                <button class="action-btn btn-success"
                                    onclick="activateSession(<?php echo $session['id']; ?>, 'candidature')">
                                    <i class="fas fa-play"></i>
                                    Activer
                                </button>
                                <?php endif; ?>
                                <button class="action-btn btn-secondary"
                                    onclick="viewCandidates(<?php echo $session['id']; ?>)">
                                    <i class="fas fa-users"></i>
                                    Candidats
                                </button>
                                <button class="action-btn btn-danger"
                                    onclick="deleteSession(<?php echo $session['id']; ?>, 'candidature')">
                                    <i class="fas fa-trash"></i>
                                    Supprimer
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>

    <!-- Modal de création d'élection -->
    <div id="createElectionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-plus"></i> Créer une Élection</h2>
                <span class="close-btn" onclick="closeCreateModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="electionType"><i class="fas fa-tag"></i> Type d'élection</label>
                    <select id="electionType" class="form-control" onchange="loadClubsForElection()">
                        <option value="" selected disabled>Choisir un type</option>
                        <?php foreach ($electionTypes as $type): ?>
                            <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" id="clubGroup" style="display: none;">
                    <label for="electionClub"><i class="fas fa-users"></i> Club (optionnel)</label>
                    <select id="electionClub" class="form-control">
                        <option value="">Aucun club spécifique</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="startDate"><i class="fas fa-calendar-plus"></i> Date et heure de début</label>
                    <input type="datetime-local" id="startDate" class="form-control">
                </div>
                <div class="form-group">
                    <label for="endDate"><i class="fas fa-calendar-minus"></i> Date et heure de fin</label>
                    <input type="datetime-local" id="endDate" class="form-control">
                </div>
                <div class="form-actions">
                    <button class="admin-btn danger" onclick="closeCreateModal()">
                        <i class="fas fa-times"></i>
                        Annuler
                    </button>
                    <button class="admin-btn" onclick="createElection()">
                        <i class="fas fa-check"></i>
                        Créer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal d'édition d'élection -->
    <div id="editElectionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> Modifier l'Élection</h2>
                <span class="close-btn" onclick="closeEditModal()">&times;</span>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editSessionId">
                <input type="hidden" id="editSessionType">
                <div class="form-group">
                    <label for="editStartDate"><i class="fas fa-calendar-plus"></i> Date et heure de début</label>
                    <input type="datetime-local" id="editStartDate" class="form-control">
                </div>
                <div class="form-group">
                    <label for="editEndDate"><i class="fas fa-calendar-minus"></i> Date et heure de fin</label>
                    <input type="datetime-local" id="editEndDate" class="form-control">
                </div>
                <div class="form-actions">
                    <button class="admin-btn danger" onclick="closeEditModal()">
                        <i class="fas fa-times"></i>
                        Annuler
                    </button>
                    <button class="admin-btn" onclick="saveEditElection()">
                        <i class="fas fa-save"></i>
                        Enregistrer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php include('../components/footer_admin.php'); ?>

    <script src="../assets/js/include.js"></script>
    <script src="../assets/js/state.js"></script>
    <script>
    // =====================================================
    // GESTION DES ÉLECTIONS - FONCTIONS PRINCIPALES
    // =====================================================

    // Changement d'onglet
    function switchTab(tabName) {
        // Masquer tous les contenus
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });

        // Retirer la classe active de tous les onglets
        document.querySelectorAll('.tab').forEach(tab => {
            tab.classList.remove('active');
        });

        // Afficher le contenu sélectionné
        document.getElementById(tabName).classList.add('active');

        // Ajouter la classe active à l'onglet cliqué
        event.target.classList.add('active');
    }

    // Application des filtres
    function applyFilters() {
        const typeFilter = document.getElementById('typeFilter').value;
        const statusFilter = document.getElementById('statusFilter').value;
        const dateFilter = document.getElementById('dateFilter').value;

        const cards = document.querySelectorAll('.session-card');

        cards.forEach(card => {
            let show = true;

            // Filtre par type
            if (typeFilter && card.dataset.type !== typeFilter) {
                show = false;
            }

            // Filtre par statut
            if (statusFilter && card.dataset.status !== statusFilter) {
                show = false;
            }

            // Filtre par date (à implémenter selon les besoins)

            card.style.display = show ? 'block' : 'none';
        });
    }

    // Modal de création
    function openCreateModal() {
        document.getElementById('createElectionModal').style.display = 'block';
    }

    function closeCreateModal() {
        document.getElementById('createElectionModal').style.display = 'none';
        // Réinitialiser le formulaire
        document.getElementById('electionType').value = '';
        document.getElementById('electionClub').value = '';
        document.getElementById('startDate').value = '';
        document.getElementById('endDate').value = '';
        document.getElementById('clubGroup').style.display = 'none';
    }

    function loadClubsForElection() {
        const electionType = document.getElementById('electionType').value;
        const clubGroup = document.getElementById('clubGroup');
        const clubSelect = document.getElementById('electionClub');

        if (electionType === '2') { // Type Club
            // Charger les clubs depuis la base de données
            fetch('../admin/actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'action=get_clubs'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        clubSelect.innerHTML = '<option value="">Aucun club spécifique</option>';
                        data.data.forEach(club => {
                            const option = document.createElement('option');
                            option.value = club.id;
                            option.textContent = club.name;
                            clubSelect.appendChild(option);
                        });
                        clubGroup.style.display = 'block';
                    }
                });
        } else {
            clubGroup.style.display = 'none';
        }
    }

    function createElection() {
        const electionType = document.getElementById('electionType').value;
        const electionClub = document.getElementById('electionClub').value;
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;

        if (!electionType || !startDate || !endDate) {
            showNotification('Veuillez remplir tous les champs obligatoires', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'start_vote_session');
        formData.append('election_type_id', electionType);
        formData.append('club_id', electionClub);
        formData.append('start_time', startDate);
        formData.append('end_time', endDate);

        fetch('../admin/actions.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    closeCreateModal();
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

    // Actions sur les sessions
    function viewSession(sessionId, type) {
        // Rediriger vers la page de détails
        window.location.href = `session_details.php?id=${sessionId}&type=${type}`;
    }

    function editSession(sessionId, type) {
        // Récupérer les détails de la session
        fetch('../admin/actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `action=get_session_details&session_id=${sessionId}&session_type=${type}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remplir le modal avec les données existantes
                document.getElementById('editSessionId').value = sessionId;
                document.getElementById('editSessionType').value = type;
                document.getElementById('editStartDate').value = data.data.start_time.replace(' ', 'T');
                document.getElementById('editEndDate').value = data.data.end_time.replace(' ', 'T');
                
                // Afficher le modal
                document.getElementById('editElectionModal').style.display = 'block';
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showNotification('Erreur de connexion', 'error');
        });
    }

    function closeEditModal() {
        document.getElementById('editElectionModal').style.display = 'none';
        // Réinitialiser le formulaire
        document.getElementById('editSessionId').value = '';
        document.getElementById('editSessionType').value = '';
        document.getElementById('editStartDate').value = '';
        document.getElementById('editEndDate').value = '';
    }

    function saveEditElection() {
        const sessionId = document.getElementById('editSessionId').value;
        const sessionType = document.getElementById('editSessionType').value;
        const startDate = document.getElementById('editStartDate').value;
        const endDate = document.getElementById('editEndDate').value;
        
        if (!sessionId || !sessionType || !startDate || !endDate) {
            showNotification('Veuillez remplir tous les champs', 'error');
            return;
        }
        
        const action = sessionType === 'vote' ? 'edit_vote_session' : 'edit_candidature_session';
        
        const formData = new FormData();
        formData.append('action', action);
        formData.append('session_id', sessionId);
        formData.append('start_time', startDate);
        formData.append('end_time', endDate);
        
        fetch('../admin/actions.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                closeEditModal();
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

    function pauseSession(sessionId, type) {
        if (confirm('Êtes-vous sûr de vouloir mettre en pause cette session ?')) {
            const action = type === 'vote' ? 'pause_vote_session' : 'pause_candidature_session';

            fetch('../admin/actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: `action=${action}&session_id=${sessionId}`
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

    function activateSession(sessionId, type) {
        if (confirm('Êtes-vous sûr de vouloir activer cette session ?')) {
            const action = type === 'vote' ? 'activate_vote_session' : 'activate_candidature_session';

            fetch('../admin/actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: `action=${action}&session_id=${sessionId}`
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

    function viewResults(sessionId, type) {
        // Rediriger vers la page des résultats
        window.location.href = `results.php?session_id=${sessionId}&type=${type}`;
    }

    function viewCandidates(sessionId) {
        // Rediriger vers la page des candidats
        window.location.href = `candidates.php?session_id=${sessionId}`;
    }

    function deleteSession(sessionId, type) {
        if (confirm('Êtes-vous sûr de vouloir supprimer cette session ? Cette action est irréversible.')) {
            const action = type === 'vote' ? 'delete_vote_session' : 'delete_candidature_session';

            fetch('../admin/actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: `action=${action}&session_id=${sessionId}`
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
        const createModal = document.getElementById('createElectionModal');
        const editModal = document.getElementById('editElectionModal');
        
        if (event.target === createModal) {
            closeCreateModal();
        }
        if (event.target === editModal) {
            closeEditModal();
        }
    });
    </script>
</body>

</html>