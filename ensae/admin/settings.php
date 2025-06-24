<?php
require_once('../config/database.php');
requireRole('admin');

// Récupérer les paramètres généraux (exemple statique, à remplacer par la BDD si besoin)
$systemName = 'Système de Vote ENSAE';
$contactEmail = 'contact@ensae.sn';

// Rôles disponibles (exemple statique)
$roles = [
    ['name' => 'admin', 'label' => 'Administrateur'],
    ['name' => 'committee', 'label' => 'Membre de Comité'],
    ['name' => 'student', 'label' => 'Étudiant'],
];
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres Système - Vote ENSAE</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/admin_accueil.css">
    <link rel="stylesheet" href="../assets/css/footer.css">
    <style>
    .settings-container {
        max-width: 900px;
        margin: 40px auto;
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        padding: 40px;
    }

    .settings-title {
        font-size: 2em;
        font-weight: 700;
        margin-bottom: 30px;
        color: #333;
    }

    .settings-section {
        margin-bottom: 40px;
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

    .settings-actions {
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

    .section-divider {
        border-top: 2px solid #e1e5e9;
        margin: 40px 0;
    }

    .roles-list {
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .roles-list li {
        padding: 10px 0;
        border-bottom: 1px solid #e1e5e9;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .roles-list li:last-child {
        border-bottom: none;
    }

    .role-label {
        font-weight: 500;
        color: #333;
    }

    .add-role-form {
        display: flex;
        gap: 10px;
        margin-top: 10px;
    }

    .security-options {
        display: flex;
        gap: 30px;
        flex-wrap: wrap;
    }

    .security-option {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 18px 22px;
        border: 1px solid #e1e5e9;
        min-width: 220px;
        margin-bottom: 10px;
    }

    .security-option label {
        font-weight: 500;
        color: #333;
    }

    .backup-actions {
        display: flex;
        gap: 20px;
    }

    @media (max-width: 700px) {
        .settings-container {
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
            <button class="sidebar-btn active" onclick="window.location.href='settings.php'">
                <i class="fas fa-cog"></i> PARAMETRES
            </button>
            <button class="sidebar-btn" onclick="window.location.href='profil.php'">
                <i class="fas fa-user"></i> MON PROFIL
            </button>
            <button class="sidebar-btn logout" onclick="logout()">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </button>
        </aside>
        <section class="content">
            <div class="settings-container">
                <div class="settings-title"><i class="fas fa-cog"></i> Paramètres du Système</div>
                <!-- Paramètres généraux -->
                <div class="settings-section">
                    <div class="section-header"><i class="fas fa-sliders-h"></i> Paramètres Généraux</div>
                    <div class="form-group">
                        <label for="systemName">Nom du système</label>
                        <input type="text" id="systemName" class="form-control"
                            value="<?php echo htmlspecialchars($systemName); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="contactEmail">Email de contact</label>
                        <input type="email" id="contactEmail" class="form-control"
                            value="<?php echo htmlspecialchars($contactEmail); ?>" disabled>
                    </div>
                    <div class="settings-actions">
                        <button class="admin-btn" disabled>Enregistrer</button>
                    </div>
                </div>
                <div class="section-divider"></div>
                <!-- Gestion des rôles -->
                <div class="settings-section">
                    <div class="section-header"><i class="fas fa-user-tag"></i> Gestion des Rôles</div>
                    <ul class="roles-list">
                        <?php foreach ($roles as $role): ?>
                        <li><span class="role-label"><?php echo htmlspecialchars($role['label']); ?></span> <span
                                class="role-name">(<?php echo $role['name']; ?>)</span></li>
                        <?php endforeach; ?>
                    </ul>
                    <form class="add-role-form" onsubmit="event.preventDefault(); alert('Fonctionnalité à venir');">
                        <input type="text" class="form-control" placeholder="Nom du rôle personnalisé" disabled>
                        <button class="admin-btn" disabled>Ajouter</button>
                    </form>
                </div>
                <div class="section-divider"></div>
                <!-- Paramètres de sécurité -->
                <div class="settings-section">
                    <div class="section-header"><i class="fas fa-shield-alt"></i> Paramètres de Sécurité</div>
                    <div class="security-options">
                        <div class="security-option">
                            <label><input type="checkbox" disabled> Forcer le changement de mot de passe à la première
                                connexion</label>
                        </div>
                        <div class="security-option">
                            <label><input type="checkbox" checked disabled> Activer les logs d'activité</label>
                        </div>
                        <div class="security-option">
                            <label><input type="checkbox" disabled> Restreindre l'accès par IP (à venir)</label>
                        </div>
                    </div>
                </div>
                <div class="section-divider"></div>
                <!-- Sauvegarde et restauration -->
                <div class="settings-section">
                    <div class="section-header"><i class="fas fa-database"></i> Sauvegarde & Restauration</div>
                    <div class="backup-actions">
                        <button class="admin-btn" disabled>Exporter la base de données</button>
                        <button class="admin-btn danger" disabled>Restaurer une sauvegarde</button>
                    </div>
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
    </script>
</body>

</html>