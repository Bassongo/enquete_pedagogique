<?php
// =====================================================
// PAGE DE CONNEXION - E-ELECTION ENSAE DAKAR
// =====================================================

// Configuration de la base de données
require_once 'config/database.php';

// Initialisation des variables
$error_message = '';
$success_message = '';
$form_data = [];

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Récupération des données du formulaire
        $email = trim($_POST['login-identifiant'] ?? '');
        $password = $_POST['login-password'] ?? '';

        // Validation des données
        $errors = [];

        // Validation email
        if (empty($email)) {
            $errors[] = "L'adresse email est obligatoire.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "L'adresse email n'est pas valide.";
        }

        // Validation mot de passe
        if (empty($password)) {
            $errors[] = "Le mot de passe est obligatoire.";
        }

        // Si aucune erreur de validation, tentative de connexion
        if (empty($errors)) {
            // Recherche de l'utilisateur par email
            $stmt = $pdo->prepare("SELECT id, email, username, password_hash, classe, role, is_active FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && $user['is_active']) {
                // Vérification du mot de passe
                if (password_verify($password, $user['password_hash'])) {
                    // Connexion réussie
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_username'] = $user['username'];
                    $_SESSION['user_classe'] = $user['classe'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['login_time'] = time();

                    // Log de la connexion réussie
                    logActivity($user['id'], 'connexion', 'Connexion réussie');

                    // Redirection selon le rôle
                    if ($user['role'] === 'admin') {
                        header('Location: admin/dashboard.php');
                    } else {
                        header('Location: pages/index.php');
                    }
                    exit();
                } else {
                    $errors[] = "Mot de passe incorrect.";
                }
            } else if ($user && !$user['is_active']) {
                $errors[] = "Votre compte a été désactivé. Veuillez contacter l'administrateur.";
            } else {
                $errors[] = "Aucun compte trouvé avec cette adresse email.";
            }
        }

        // Affichage des erreurs
        if (!empty($errors)) {
            $error_message = implode('<br>', $errors);
            // Sauvegarde de l'email pour réaffichage
            $form_data = ['email' => $email];
            
            // Log de tentative de connexion échouée
            if (!empty($email)) {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                if ($user) {
                    logActivity($user['id'], 'tentative_connexion_echouee', 'Mot de passe incorrect');
                }
            }
        }

    } catch (PDOException $e) {
        $error_message = "Erreur de base de données. Veuillez réessayer plus tard.";
        error_log("Erreur connexion: " . $e->getMessage());
    }
}

// Vérification si l'utilisateur est déjà connecté
if (isLoggedIn()) {
    $user = getCurrentUser();
    if ($user['role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: pages/index.php');
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Vote ENSAE</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>

<body class="login-bg">
        <?php include('components/header_home.php'); ?>


    <div class="login-container">
        <div class="login-box">
            <h2>Se connecter</h2>

            <?php if ($error_message): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
            <?php endif; ?>

            <form id="loginForm" class="auth-form" method="POST" action="">
                <div class="form-group">
                    <label for="login-identifiant">Adresse email :</label>
                    <input type="email" id="login-identifiant" name="login-identifiant"
                        value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" required
                        placeholder="votre.email@ensae.sn">
                </div>

                <div class="form-group">
                    <label for="login-password">Mot de passe :</label>
                    <input type="password" id="login-password" name="login-password" required
                        placeholder="Votre mot de passe">
                </div>

                <div class="login-actions">
                    <button type="submit" class="btn btn-primary">Se connecter</button>
                    <a href="inscription.php" class="btn btn-secondary">Créer un compte</a>
                    <a href="home.php" class="btn btn-link">Retour à l'accueil</a>
                </div>
            </form>

            <div class="login-help">
                <p><small>Mot de passe oublié ? Contactez l'administrateur.</small></p>
                <p><small>Utilisez votre email ENSAE officiel pour vous connecter.</small></p>
            </div>
        </div>
    </div>

    <?php include('components/footer_home.php'); ?>

    <!-- Inclusion dynamique du header/footer -->
    <script src="assets/js/include.js"></script>
    <script>
    // Validation côté client
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        const email = document.getElementById('login-identifiant').value.trim();
        const password = document.getElementById('login-password').value.trim();

        if (!email) {
            e.preventDefault();
            alert('Veuillez saisir votre adresse email.');
            return false;
        }

        if (!password) {
            e.preventDefault();
            alert('Veuillez saisir votre mot de passe.');
            return false;
        }

        // Validation basique du format email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            e.preventDefault();
            alert('Veuillez saisir une adresse email valide.');
            return false;
        }
    });

    // Effacer les messages d'erreur après 5 secondes
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.remove();
            }, 500);
        });
    }, 5000);
    </script>
</body>

</html>