<?php
require_once('../config/database.php');
requireLogin();
if (!hasRole('admin') && !hasRole('committee')) {
    header('Location: ../login.php');
    exit();
}

$user = getCurrentUser();
if (!$user) {
    header('Location: ../login.php');
    exit();
}


// Types d'élections accessibles
if ($user['role'] === 'admin') {
    $stmt = $pdo->query("SELECT id, name FROM election_types WHERE is_active = 1 ORDER BY name");
    $election_types = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT et.id, et.name FROM election_types et JOIN committee_election_types cet ON et.id = cet.election_type_id WHERE et.is_active = 1 AND cet.user_id = ? ORDER BY et.name");
    $stmt->execute([$user['id']]);
    $election_types = $stmt->fetchAll();
}

// Clubs pour les sessions
$stmt = $pdo->query("SELECT id, name FROM clubs WHERE is_active = 1 ORDER BY name");
$clubs = $stmt->fetchAll();

$is_committee = in_array($user['role'], ['admin', 'committee']);
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
    <link rel="stylesheet" href="../assets/css/admin_accueil.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <style>
    .profile-container {
        max-width: 700px;
        margin: 40px auto;
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        padding: 40px;
    }

    .profile-title {
        font-size: 2em;
        font-weight: 700;
        margin-bottom: 30px;
        color: #333;
    }

    .profile-section {
        margin-bottom: 35px;
    }

    .section-header {
        font-size: 1.2em;
        font-weight: 600;
        color: #555;
        margin-bottom: 18px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .form-group {
        margin-bottom: 18px;
    }

    .form-group label {
        font-weight: 500;
        color: #333;
        display: block;
        margin-bottom: 6px;
    }

    .form-control {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e1e5e9;
        border-radius: 8px;
        font-size: 15px;
    }

    .form-control:focus {
        outline: none;
        border-color: #667eea;
    }

    .profile-actions {
        display: flex;
        gap: 15px;
        margin-top: 18px;
    }

    .admin-btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        font-size: 15px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
    }

    .admin-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
    }

    .danger {
        background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%);
    }

    .profile-info-list {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .profile-info-list li {
        padding: 10px 0;
        border-bottom: 1px solid #e1e5e9;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .profile-info-list li:last-child {
        border-bottom: none;
    }

    .info-label {
        font-weight: 500;
        color: #333;
        min-width: 120px;
    }

    .info-value {
        color: #555;
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

    @media (max-width: 700px) {
        .profile-container {
            padding: 15px;
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
                <i class="fas fa-tachometer-alt"></i> DASHBOARD
            </button>
            <button class="sidebar-btn" onclick="window.location.href='elections.php'">
                <i class="fas fa-vote-yea"></i> GESTION DES ELECTIONS
            </button>
            <button class="sidebar-btn" onclick="window.location.href='candidates.php'">
                <i class="fas fa-users"></i> GESTION DES CANDIDATS
            </button>
            <button class="sidebar-btn" onclick="window.location.href='committees.php'">
                <i class="fas fa-user-tie"></i> GESTION DES COMITES
            </button>
            <button class="sidebar-btn" onclick="window.location.href='settings.php'">
                <i class="fas fa-cog"></i> PARAMETRES
            </button>
            <button class="sidebar-btn active" onclick="window.location.href='profil.php'">
                <i class="fas fa-user"></i> MON PROFIL
            </button>
            <button class="sidebar-btn logout" onclick="logout()">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </button>
        </aside>
        <section class="content">
            <div class="profile-container">
                <div class="profile-title"><i class="fas fa-user"></i> Mon Profil</div>
                <!-- Infos personnelles -->
                <div class="profile-section">
                    <div class="section-header"><i class="fas fa-id-card"></i> Informations personnelles</div>
                    <ul class="profile-info-list">
                        <li><span class="info-label">Nom d'utilisateur</span><span
                                class="info-value"><?php echo htmlspecialchars($user['username']); ?></span></li>
                        <li><span class="info-label">Email</span><span
                                class="info-value"><?php echo htmlspecialchars($user['email']); ?></span></li>
                        <li><span class="info-label">Classe</span><span
                                class="info-value"><?php echo htmlspecialchars($user['classe']); ?></span></li>
                        <li><span class="info-label">Rôle</span><span
                                class="info-value"><?php echo $user['role'] === 'admin' ? 'Administrateur' : 'Membre de Comité'; ?></span>
                        </li>
                        <li><span class="info-label">Statut</span><span
                                class="info-value"><?php echo $user['is_active'] ? 'Actif' : 'Inactif'; ?></span></li>
                    </ul>
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
        </section>
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

    <?php include('../components/footer_admin.php'); ?>
    <script src="../assets/js/include.js"></script>
    <script src="../assets/js/state.js"></script>
    <script src="../assets/js/admin_accueil.js"></script>

    <script>
    function openModal(id) {
        document.getElementById(id).style.display = 'flex';
    }

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }

    document.getElementById('candType')?.addEventListener('change', (e) => {
        document.getElementById('clubGroup').style.display = (e.target.value == 2) ? 'block' : 'none';
    });

    document.getElementById('voteType')?.addEventListener('change', (e) => {
        document.getElementById('voteClubGroup').style.display = (e.target.value == 2) ? 'block' : 'none';
    });

    function startCandidature() {
        const typeId = document.getElementById('candType').value;
        const clubId = document.getElementById('clubSelect').value;
        const startDate = document.getElementById('startCandDate').value;
        const endDate = document.getElementById('endCandDate').value;
        if (!typeId || !startDate || !endDate) { alert('Veuillez remplir tous les champs obligatoires'); return; }
        if (typeId == 2 && !clubId) { alert('Veuillez sélectionner un club'); return; }
        fetch('actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=start_candidature_session&election_type_id=${typeId}&club_id=${clubId}&start_time=${startDate}&end_time=${endDate}`
        }).then(r=>r.json()).then(d=>{ if(d.success){alert('Session de candidature démarrée'); closeModal('startCandModal'); location.reload(); } else { alert('Erreur: '+d.message); } });
    }

    function closeCandidature() {
        const typeId = document.getElementById('closeCandType').value;
        if (!typeId) { alert('Veuillez sélectionner un type d\'élection'); return; }
        if (confirm('Êtes-vous sûr de vouloir fermer les candidatures ?')) {
            fetch('actions.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=close_candidature_session&election_type_id=${typeId}`
            }).then(r=>r.json()).then(d=>{ if(d.success){alert('Session de candidature fermée'); closeModal('closeCandModal'); location.reload(); } else { alert('Erreur: '+d.message); } });
        }
    }

    function startVote() {
        const typeId = document.getElementById('voteType').value;
        const clubId = document.getElementById('voteClubSelect').value;
        const startDate = document.getElementById('startVoteDate').value;
        const endDate = document.getElementById('endVoteDate').value;
        if (!typeId || !startDate || !endDate) { alert('Veuillez remplir tous les champs obligatoires'); return; }
        if (typeId == 2 && !clubId) { alert('Veuillez sélectionner un club'); return; }
        fetch('actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=start_vote_session&election_type_id=${typeId}&club_id=${clubId}&start_time=${startDate}&end_time=${endDate}`
        }).then(r=>r.json()).then(d=>{ if(d.success){alert('Session de vote démarrée'); closeModal('startVoteModal'); location.reload(); } else { alert('Erreur: '+d.message); } });
    }

    function closeVote() {
        const typeId = document.getElementById('closeVoteType').value;
        if (!typeId) { alert('Veuillez sélectionner un type d\'élection'); return; }
        if (confirm('Êtes-vous sûr de vouloir fermer les votes ?')) {
            fetch('actions.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=close_vote_session&election_type_id=${typeId}`
            }).then(r=>r.json()).then(d=>{ if(d.success){alert('Session de vote fermée'); closeModal('closeVoteModal'); location.reload(); } else { alert('Erreur: '+d.message); } });
        }
    }

    window.onclick = function(e){ if(e.target.classList.contains('modal')) e.target.style.display='none'; }

    function logout(){ if(confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) window.location.href='../logout.php'; }

    function showNotification(msg,type){ alert(msg); }
    </script>
</body>

</html>