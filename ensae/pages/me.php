<?php
require_once('../config/database.php');
requireLogin();

// Récupérer les informations détaillées de l'utilisateur connecté
$stmt = $pdo->prepare("
    SELECT 
        u.*,
        (SELECT COUNT(*) FROM candidatures WHERE user_id = u.id) as total_candidatures,
        (SELECT COUNT(*) FROM votes WHERE voter_id = u.id) as total_votes
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
");
$stmt->execute([$_SESSION['user_id']]);
$candidatures = $stmt->fetchAll();

// Récupérer les votes de l'utilisateur
$stmt = $pdo->prepare("
    SELECT 
        v.*,
        et.name as election_type,
        cl.name as club_name,
        vs.start_time as session_start,
        vs.end_time as session_end,
        cand.username as candidate_name,
        p.name as position_name
    FROM votes v
    LEFT JOIN election_types et ON v.election_type_id = et.id
    LEFT JOIN clubs cl ON v.club_id = cl.id
    LEFT JOIN vote_sessions vs ON v.vote_session_id = vs.id
    LEFT JOIN candidatures cand_c ON v.candidature_id = cand_c.id
    LEFT JOIN users cand ON cand_c.user_id = cand.id
    LEFT JOIN positions p ON cand_c.position_id = p.id
    WHERE v.voter_id = ?
    ORDER BY v.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$votes = $stmt->fetchAll();

// Récupérer les types d'élections pour les actions de comité
$stmt = $pdo->query("SELECT id, name FROM election_types WHERE is_active = 1 ORDER BY name");
$election_types = $stmt->fetchAll();

// Récupérer les clubs pour les actions de comité
$stmt = $pdo->query("SELECT id, name FROM clubs WHERE is_active = 1 ORDER BY name");
$clubs = $stmt->fetchAll();

// Vérifier si l'utilisateur est membre de comité
$is_committee = in_array($_SESSION['user_role'], ['admin', 'committee']);
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
    <link rel="stylesheet" href="../assets/css/footer.css">
    <style>
    .profile-container {
        max-width: 800px;
        margin: 2rem auto;
        padding: 2rem;
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .profile-header {
        text-align: center;
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #f0f0f0;
    }

    .profile-header h1 {
        color: #333;
        font-size: 2.2em;
        margin-bottom: 0.5rem;
    }

    .profile-header p {
        color: #666;
        font-size: 1.1em;
    }

    .profile-photo {
        width: 120px;
        height: 120px;
        object-fit: cover;
        border-radius: 50%;
        display: block;
        margin: 0 auto 1rem auto;
        border: 4px solid #667eea;
    }

    .profile-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .info-section {
        background: #f8f9fa;
        padding: 1.5rem;
        border-radius: 10px;
        border-left: 4px solid #667eea;
    }

    .info-section h3 {
        color: #333;
        font-size: 1.2em;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .info-section h3 i {
        color: #667eea;
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
        border-bottom: 1px solid #e9ecef;
    }

    .info-item:last-child {
        border-bottom: none;
    }

    .info-label {
        font-weight: 600;
        color: #555;
    }

    .info-value {
        color: #333;
    }

    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.85em;
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

    .activity-section {
        margin-top: 2rem;
    }

    .activity-tabs {
        display: flex;
        border-bottom: 2px solid #e9ecef;
        margin-bottom: 1.5rem;
    }

    .activity-tab {
        padding: 1rem 2rem;
        background: none;
        border: none;
        cursor: pointer;
        font-weight: 600;
        color: #666;
        border-bottom: 3px solid transparent;
        transition: all 0.3s ease;
    }

    .activity-tab.active {
        color: #667eea;
        border-bottom-color: #667eea;
    }

    .activity-content {
        display: none;
    }

    .activity-content.active {
        display: block;
    }

    .activity-item {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        border-left: 4px solid #667eea;
    }

    .activity-item h4 {
        color: #333;
        margin-bottom: 0.5rem;
    }

    .activity-item p {
        color: #666;
        margin: 0.25rem 0;
    }

    .activity-date {
        font-size: 0.85em;
        color: #888;
    }

    .committee-actions {
        margin-top: 2rem;
        padding: 1.5rem;
        background: linear-gradient(135deg, #667eea, #764ba2);
        border-radius: 10px;
        color: white;
    }

    .committee-actions h3 {
        text-align: center;
        margin-bottom: 1rem;
        font-size: 1.3em;
    }

    .committee-buttons {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }

    .committee-btn {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: 2px solid rgba(255, 255, 255, 0.3);
        padding: 0.75rem 1rem;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .committee-btn:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: translateY(-2px);
    }

    .committee-btn.danger {
        background: rgba(220, 53, 69, 0.2);
        border-color: rgba(220, 53, 69, 0.3);
    }

    .committee-btn.danger:hover {
        background: rgba(220, 53, 69, 0.3);
    }

    @media (max-width: 768px) {
        .profile-container {
            margin: 1rem;
            padding: 1rem;
        }

        .profile-info {
            grid-template-columns: 1fr;
        }

        .activity-tabs {
            flex-direction: column;
        }

        .committee-buttons {
            grid-template-columns: 1fr;
        }
    }
    </style>
</head>

<body>
    <?php include('../components/header.php'); ?>

    <div class="profile-container">
        <div class="profile-header">
            <h1><i class="fas fa-user"></i> Mon Profil</h1>
            <p>Gérez vos informations personnelles et suivez votre activité</p>
            <?php if (!empty($user['photo_url'])): ?>
            <img src="<?php echo htmlspecialchars($user['photo_url']); ?>" class="profile-photo" alt="Photo de profil">
            <?php else: ?>
            <img src="../assets/img/ali.jpg" class="profile-photo" alt="Photo par défaut">
            <?php endif; ?>
        </div>

        <div class="profile-info">
            <div class="info-section">
                <h3><i class="fas fa-user-circle"></i> Informations Personnelles</h3>
                <div class="info-item">
                    <span class="info-label">Nom d'utilisateur :</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['username']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Email :</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Classe :</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['classe']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Rôle :</span>
                    <span class="info-value"><?php echo ucfirst(htmlspecialchars($user['role'])); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Inscrit depuis :</span>
                    <span class="info-value"><?php echo (new DateTime($user['created_at']))->format('d/m/Y'); ?></span>
                </div>
            </div>

            <div class="info-section">
                <h3><i class="fas fa-chart-bar"></i> Statistiques</h3>
                <div class="info-item">
                    <span class="info-label">Candidatures :</span>
                    <span class="info-value"><?php echo $user['total_candidatures']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Votes exprimés :</span>
                    <span class="info-value"><?php echo $user['total_votes']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Statut :</span>
                    <span class="info-value">
                        <span
                            class="status-badge <?php echo $user['is_active'] ? 'status-approved' : 'status-rejected'; ?>">
                            <?php echo $user['is_active'] ? 'Actif' : 'Inactif'; ?>
                        </span>
                    </span>
                </div>
            </div>
        </div>

        <div class="activity-section">
            <div class="activity-tabs">
                <button class="activity-tab active" onclick="showTab('candidatures')">
                    <i class="fas fa-user-tie"></i> Mes Candidatures
                </button>
                <button class="activity-tab" onclick="showTab('votes')">
                    <i class="fas fa-vote-yea"></i> Mes Votes
                </button>
            </div>

            <div id="candidatures" class="activity-content active">
                <?php if (empty($candidatures)): ?>
                <div class="activity-item">
                    <h4>Aucune candidature</h4>
                    <p>Vous n'avez pas encore soumis de candidature.</p>
                    <a href="candidat.php" class="committee-btn">Soumettre une candidature</a>
                </div>
                <?php else: ?>
                <?php foreach ($candidatures as $cand): ?>
                <div class="activity-item">
                    <h4><?php echo htmlspecialchars($cand['election_type']); ?> -
                        <?php echo htmlspecialchars($cand['position_name']); ?></h4>
                    <?php if ($cand['club_name']): ?>
                    <p><strong>Club :</strong> <?php echo htmlspecialchars($cand['club_name']); ?></p>
                    <?php endif; ?>
                    <p><strong>Statut :</strong>
                        <span class="status-badge status-<?php echo $cand['status']; ?>">
                            <?php echo ucfirst($cand['status']); ?>
                        </span>
                    </p>
                    <p><strong>Programme :</strong>
                        <?php echo substr(htmlspecialchars($cand['programme']), 0, 100); ?>...</p>
                    <p class="activity-date">Soumis le
                        <?php echo (new DateTime($cand['created_at']))->format('d/m/Y H:i'); ?></p>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div id="votes" class="activity-content">
                <?php if (empty($votes)): ?>
                <div class="activity-item">
                    <h4>Aucun vote</h4>
                    <p>Vous n'avez pas encore participé à un vote.</p>
                    <a href="vote.php" class="committee-btn">Voir les votes actifs</a>
                </div>
                <?php else: ?>
                <?php foreach ($votes as $vote): ?>
                <div class="activity-item">
                    <h4><?php echo htmlspecialchars($vote['election_type']); ?></h4>
                    <?php if ($vote['club_name']): ?>
                    <p><strong>Club :</strong> <?php echo htmlspecialchars($vote['club_name']); ?></p>
                    <?php endif; ?>
                    <p><strong>Voté pour :</strong> <?php echo htmlspecialchars($vote['candidate_name']); ?>
                        (<?php echo htmlspecialchars($vote['position_name']); ?>)</p>
                    <p class="activity-date">Voté le
                        <?php echo (new DateTime($vote['created_at']))->format('d/m/Y H:i'); ?></p>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($is_committee): ?>
        <div class="committee-actions">
            <h3><i class="fas fa-user-shield"></i> Actions de Comité</h3>
            <div class="committee-buttons">
                <button class="committee-btn" onclick="openModal('startCandModal')">
                    <i class="fas fa-user-plus"></i> Démarrer Candidatures
                </button>
                <button class="committee-btn danger" onclick="openModal('closeCandModal')">
                    <i class="fas fa-times-circle"></i> Fermer Candidatures
                </button>
                <button class="committee-btn" onclick="openModal('startVoteModal')">
                    <i class="fas fa-vote-yea"></i> Démarrer Votes
                </button>
                <button class="committee-btn danger" onclick="openModal('closeVoteModal')">
                    <i class="fas fa-stop-circle"></i> Fermer Votes
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modal pour démarrer les candidatures -->
    <div id="startCandModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('startCandModal')">&times;</span>
            <h3><i class="fas fa-user-plus"></i> Démarrer les Candidatures</h3>
            <div class="form-group">
                <label for="candType">Type d'élection</label>
                <select id="candType">
                    <option value="" selected disabled>Choisir un type</option>
                    <?php foreach ($election_types as $type): ?>
                    <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" id="clubGroup" style="display:none;">
                <label for="clubSelect">Club</label>
                <select id="clubSelect">
                    <option value="">-- Sélectionnez un club --</option>
                    <?php foreach ($clubs as $club): ?>
                    <option value="<?php echo $club['id']; ?>"><?php echo htmlspecialchars($club['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="startCandDate">Date et heure de début</label>
                <input type="datetime-local" id="startCandDate">
            </div>
            <div class="form-group">
                <label for="endCandDate">Date et heure de fin</label>
                <input type="datetime-local" id="endCandDate">
            </div>
            <div class="form-actions">
                <button class="committee-btn danger" onclick="closeModal('startCandModal')">Annuler</button>
                <button class="committee-btn" onclick="startCandidature()">Valider</button>
            </div>
        </div>
    </div>

    <!-- Modal pour fermer les candidatures -->
    <div id="closeCandModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('closeCandModal')">&times;</span>
            <h3><i class="fas fa-times-circle"></i> Fermer les Candidatures</h3>
            <div class="form-group">
                <label for="closeCandType">Type d'élection</label>
                <select id="closeCandType">
                    <option value="" selected disabled>Choisir un type</option>
                    <?php foreach ($election_types as $type): ?>
                    <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-actions">
                <button class="committee-btn danger" onclick="closeModal('closeCandModal')">Annuler</button>
                <button class="committee-btn" onclick="closeCandidature()">Valider</button>
            </div>
        </div>
    </div>

    <!-- Modal pour démarrer les votes -->
    <div id="startVoteModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('startVoteModal')">&times;</span>
            <h3><i class="fas fa-vote-yea"></i> Démarrer les Votes</h3>
            <div class="form-group">
                <label for="voteType">Type d'élection</label>
                <select id="voteType">
                    <option value="" selected disabled>Choisir un type</option>
                    <?php foreach ($election_types as $type): ?>
                    <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" id="voteClubGroup" style="display:none;">
                <label for="voteClubSelect">Club</label>
                <select id="voteClubSelect">
                    <option value="">-- Sélectionnez un club --</option>
                    <?php foreach ($clubs as $club): ?>
                    <option value="<?php echo $club['id']; ?>"><?php echo htmlspecialchars($club['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="startVoteDate">Date et heure de début</label>
                <input type="datetime-local" id="startVoteDate">
            </div>
            <div class="form-group">
                <label for="endVoteDate">Date et heure de fin</label>
                <input type="datetime-local" id="endVoteDate">
            </div>
            <div class="form-actions">
                <button class="committee-btn danger" onclick="closeModal('startVoteModal')">Annuler</button>
                <button class="committee-btn" onclick="startVote()">Valider</button>
            </div>
        </div>
    </div>

    <!-- Modal pour fermer les votes -->
    <div id="closeVoteModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('closeVoteModal')">&times;</span>
            <h3><i class="fas fa-stop-circle"></i> Fermer les Votes</h3>
            <div class="form-group">
                <label for="closeVoteType">Type d'élection</label>
                <select id="closeVoteType">
                    <option value="" selected disabled>Choisir un type</option>
                    <?php foreach ($election_types as $type): ?>
                    <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-actions">
                <button class="committee-btn danger" onclick="closeModal('closeVoteModal')">Annuler</button>
                <button class="committee-btn" onclick="closeVote()">Valider</button>
            </div>
        </div>
    </div>

    <?php include('../components/footer.php'); ?>

    <script src="../assets/js/state.js"></script>
    <script>
    function showTab(tabName) {
        // Masquer tous les contenus
        document.querySelectorAll('.activity-content').forEach(content => {
            content.classList.remove('active');
        });

        // Désactiver tous les onglets
        document.querySelectorAll('.activity-tab').forEach(tab => {
            tab.classList.remove('active');
        });

        // Afficher le contenu sélectionné
        document.getElementById(tabName).classList.add('active');

        // Activer l'onglet sélectionné
        event.target.classList.add('active');
    }

    function openModal(modalId) {
        document.getElementById(modalId).style.display = 'flex';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    // Gestion des types d'élection pour les modales
    document.getElementById('candType')?.addEventListener('change', function() {
        const clubGroup = document.getElementById('clubGroup');
        if (this.value == 2) { // Type Club
            clubGroup.style.display = 'block';
        } else {
            clubGroup.style.display = 'none';
        }
    });

    document.getElementById('voteType')?.addEventListener('change', function() {
        const voteClubGroup = document.getElementById('voteClubGroup');
        if (this.value == 2) { // Type Club
            voteClubGroup.style.display = 'block';
        } else {
            voteClubGroup.style.display = 'none';
        }
    });

    // Actions des modales
    function startCandidature() {
        const typeId = document.getElementById('candType').value;
        const clubId = document.getElementById('clubSelect').value;
        const startDate = document.getElementById('startCandDate').value;
        const endDate = document.getElementById('endCandDate').value;

        if (!typeId || !startDate || !endDate) {
            alert('Veuillez remplir tous les champs obligatoires');
            return;
        }

        if (typeId == 2 && !clubId) {
            alert('Veuillez sélectionner un club');
            return;
        }

        // Appel AJAX pour démarrer les candidatures
        fetch('../admin/actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=start_candidature_session&election_type_id=${typeId}&club_id=${clubId}&start_time=${startDate}&end_time=${endDate}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Session de candidature démarrée avec succès');
                    closeModal('startCandModal');
                    location.reload();
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur de connexion');
            });
    }

    function closeCandidature() {
        const typeId = document.getElementById('closeCandType').value;

        if (!typeId) {
            alert('Veuillez sélectionner un type d\'élection');
            return;
        }

        if (confirm('Êtes-vous sûr de vouloir fermer les candidatures pour ce type d\'élection ?')) {
            // Appel AJAX pour fermer les candidatures
            fetch('../admin/actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=close_candidature_session&election_type_id=${typeId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Session de candidature fermée avec succès');
                        closeModal('closeCandModal');
                        location.reload();
                    } else {
                        alert('Erreur: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur de connexion');
                });
        }
    }

    function startVote() {
        const typeId = document.getElementById('voteType').value;
        const clubId = document.getElementById('voteClubSelect').value;
        const startDate = document.getElementById('startVoteDate').value;
        const endDate = document.getElementById('endVoteDate').value;

        if (!typeId || !startDate || !endDate) {
            alert('Veuillez remplir tous les champs obligatoires');
            return;
        }

        if (typeId == 2 && !clubId) {
            alert('Veuillez sélectionner un club');
            return;
        }

        // Appel AJAX pour démarrer les votes
        fetch('../admin/actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=start_vote_session&election_type_id=${typeId}&club_id=${clubId}&start_time=${startDate}&end_time=${endDate}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Session de vote démarrée avec succès');
                    closeModal('startVoteModal');
                    location.reload();
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur de connexion');
            });
    }

    function closeVote() {
        const typeId = document.getElementById('closeVoteType').value;

        if (!typeId) {
            alert('Veuillez sélectionner un type d\'élection');
            return;
        }

        if (confirm('Êtes-vous sûr de vouloir fermer les votes pour ce type d\'élection ?')) {
            // Appel AJAX pour fermer les votes
            fetch('../admin/actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=close_vote_session&election_type_id=${typeId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Session de vote fermée avec succès');
                        closeModal('closeVoteModal');
                        location.reload();
                    } else {
                        alert('Erreur: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur de connexion');
                });
        }
    }

    // Fermer les modales en cliquant à l'extérieur
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }
    </script>
</body>

</html>