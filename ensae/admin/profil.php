<?php
require_once('../config/database.php');
requireRole('admin'); // Les membres de comité sont aussi acceptés

$user = getCurrentUser();
if (!$user) {
    header('Location: ../login.php');
    exit();
}

// Récupérer les logs d'activité
$stmt = $pdo->prepare("SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 15");
$stmt->execute([$user['id']]);
$logs = $stmt->fetchAll();
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

    .logs-list {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .logs-list li {
        padding: 10px 0;
        border-bottom: 1px solid #e1e5e9;
        font-size: 0.97em;
        color: #444;
    }

    .logs-list li:last-child {
        border-bottom: none;
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
                <!-- Modifier infos -->
                <div class="profile-section">
                    <div class="section-header"><i class="fas fa-edit"></i> Modifier mes informations</div>
                    <form id="editInfoForm"
                        onsubmit="event.preventDefault(); showNotification('Fonctionnalité à venir', 'info');">
                        <div class="form-group">
                            <label for="editUsername">Nom d'utilisateur</label>
                            <input type="text" id="editUsername" class="form-control"
                                value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label for="editEmail">Email</label>
                            <input type="email" id="editEmail" class="form-control"
                                value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                        </div>
                        <div class="profile-actions">
                            <button class="admin-btn" disabled>Enregistrer</button>
                        </div>
                    </form>
                </div>
                <!-- Changer mot de passe -->
                <div class="profile-section">
                    <div class="section-header"><i class="fas fa-key"></i> Changer mon mot de passe</div>
                    <form id="changePwdForm"
                        onsubmit="event.preventDefault(); showNotification('Fonctionnalité à venir', 'info');">
                        <div class="form-group">
                            <label for="currentPwd">Mot de passe actuel</label>
                            <input type="password" id="currentPwd" class="form-control" disabled>
                        </div>
                        <div class="form-group">
                            <label for="newPwd">Nouveau mot de passe</label>
                            <input type="password" id="newPwd" class="form-control" disabled>
                        </div>
                        <div class="form-group">
                            <label for="confirmPwd">Confirmer le nouveau mot de passe</label>
                            <input type="password" id="confirmPwd" class="form-control" disabled>
                        </div>
                        <div class="profile-actions">
                            <button class="admin-btn" disabled>Changer le mot de passe</button>
                        </div>
                    </form>
                </div>
                <!-- Logs -->
                <div class="profile-section">
                    <div class="section-header"><i class="fas fa-history"></i> Mon historique d'activité</div>
                    <?php if (empty($logs)): ?>
                    <div style="color:#888;">Aucune activité récente.</div>
                    <?php else: ?>
                    <ul class="logs-list">
                        <?php foreach ($logs as $log): ?>
                        <li>
                            <span style="font-weight:600;"><?php echo htmlspecialchars($log['action']); ?></span>
                            <span
                                style="color:#888; margin-left:10px;"><?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?></span>
                            <?php if ($log['details']): ?><br><span style="color:#666; font-size:0.95em;">Détails :
                                <?php echo htmlspecialchars($log['details']); ?></span><?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
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

        function logout() {
            if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
                window.location.href = '../logout.php';
            }
        }

        function showNotification(msg, type) {
            alert(msg); // Remplacer par une vraie notification si besoin
        }
    </script>
</body>

</html>