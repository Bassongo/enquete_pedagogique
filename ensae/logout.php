<?php
// =====================================================
// PAGE DE DÉCONNEXION - E-ELECTION ENSAE DAKAR
// =====================================================

// Configuration de la base de données
require_once 'config/database.php';

// Vérification si l'utilisateur est connecté
if (isLoggedIn()) {
    $user = getCurrentUser();
    
    if ($user) {
        // Log de la déconnexion
        logActivity($user['id'], 'deconnexion', 'Déconnexion utilisateur');
        
        // Calcul de la durée de session
        $session_duration = time() - ($_SESSION['login_time'] ?? time());
        $duration_minutes = round($session_duration / 60, 2);
        
        // Log de la durée de session
        logActivity($user['id'], 'duree_session', "Session de {$duration_minutes} minutes");
    }
}

// Destruction de toutes les variables de session
$_SESSION = array();

// Destruction du cookie de session si il existe
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruction de la session
session_destroy();

// Redirection vers la page d'accueil avec message de succès
$success_message = "Vous avez été déconnecté avec succès.";
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Déconnexion - Vote ENSAE</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/login.css">
    <style>
    .logout-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 60vh;
        padding: 2rem;
    }

    .logout-box {
        background: var(--white);
        padding: 3rem 2rem;
        border-radius: var(--radius);
        box-shadow: 0 4px 20px rgba(37, 99, 235, 0.15);
        text-align: center;
        max-width: 500px;
        width: 100%;
    }

    .logout-icon {
        font-size: 4rem;
        color: var(--primary);
        margin-bottom: 1rem;
    }

    .logout-title {
        color: var(--primary);
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .logout-message {
        color: var(--text);
        font-size: 1.1rem;
        margin-bottom: 2rem;
        line-height: 1.6;
    }

    .logout-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }

    .btn {
        padding: 0.8rem 1.5rem;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 1rem;
    }

    .btn-primary {
        background: var(--primary);
        color: var(--white);
    }

    .btn-primary:hover {
        background: var(--secondary);
        transform: translateY(-2px);
    }

    .btn-secondary {
        background: var(--border);
        color: var(--text);
    }

    .btn-secondary:hover {
        background: var(--gray);
        color: var(--white);
    }

    .auto-redirect {
        margin-top: 2rem;
        padding: 1rem;
        background: var(--primary-light);
        border-radius: 8px;
        color: var(--primary);
        font-size: 0.9rem;
    }

    .countdown {
        font-weight: bold;
        color: var(--accent);
    }

    @media (max-width: 600px) {
        .logout-box {
            padding: 2rem 1rem;
            margin: 1rem;
        }

        .logout-actions {
            flex-direction: column;
        }

        .btn {
            width: 100%;
            text-align: center;
        }
    }
    </style>
</head>

<body class="login-bg">
    <div id="header"></div>

    <div class="logout-container">
        <div class="logout-box">
            <div class="logout-icon">👋</div>
            <h1 class="logout-title">Déconnexion réussie</h1>

            <div class="logout-message">
                <?php echo htmlspecialchars($success_message); ?>
                <br><br>
                Merci d'avoir utilisé la plateforme de vote ENSAE.
                <br>
                Votre session a été fermée en toute sécurité.
            </div>

            <div class="logout-actions">
                <a href="login.php" class="btn btn-primary">Se reconnecter</a>
                <a href="Home.html" class="btn btn-secondary">Retour à l'accueil</a>
            </div>

            <div class="auto-redirect">
                <p>Redirection automatique vers la page d'accueil dans <span class="countdown" id="countdown">10</span>
                    secondes...</p>
            </div>
        </div>
    </div>

    <div id="footer"></div>

    <!-- Inclusion dynamique du header/footer -->
    <script src="assets/js/include.js"></script>


    <script>
    // Compte à rebours pour la redirection automatique
    let countdown = 10;
    const countdownElement = document.getElementById('countdown');

    const timer = setInterval(function() {
        countdown--;
        countdownElement.textContent = countdown;

        if (countdown <= 0) {
            clearInterval(timer);
            window.location.href = 'Home.php';
        }
    }, 1000);

    // Permettre à l'utilisateur d'annuler la redirection automatique
    document.addEventListener('click', function() {
        clearInterval(timer);
        document.querySelector('.auto-redirect').style.display = 'none';
    });

    // Animation d'entrée
    document.addEventListener('DOMContentLoaded', function() {
        const logoutBox = document.querySelector('.logout-box');
        logoutBox.style.opacity = '0';
        logoutBox.style.transform = 'translateY(20px)';

        setTimeout(function() {
            logoutBox.style.transition = 'all 0.5s ease';
            logoutBox.style.opacity = '1';
            logoutBox.style.transform = 'translateY(0)';
        }, 100);
    });

    // Effet de survol sur les boutons
    document.querySelectorAll('.btn').forEach(btn => {
        btn.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 4px 12px rgba(37, 99, 235, 0.3)';
        });

        btn.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = 'none';
        });
    });
    </script>
</body>

</html>