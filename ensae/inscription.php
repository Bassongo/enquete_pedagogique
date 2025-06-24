<?php
// =====================================================
// PAGE D'INSCRIPTION - E-ELECTION ENSAE DAKAR
// =====================================================

// Configuration de la base de données
require_once 'config/database.php';

// Initialisation des variables
$error_message = '';
$success_message = '';
$form_data = [];

// Traitement du formulaire d'inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Récupération des données du formulaire
        $email = trim($_POST['reg-email'] ?? '');
        $username = trim($_POST['reg-username'] ?? '');
        $password = $_POST['reg-password'] ?? '';
        $confirm_password = $_POST['reg-confirm-password'] ?? '';
        $classe = $_POST['reg-classe'] ?? '';

        // Validation des données
        $errors = [];
        
        // Validation email
        if (empty($email)) {
            $errors[] = "L'adresse email est obligatoire.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "L'adresse email n'est pas valide.";
        } else {
            // Vérification OBLIGATOIRE que l'email existe dans la table gmail
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM gmail WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetchColumn() == 0) {
                    $errors[] = "Votre email n'est pas dans notre base de données d'étudiants ENSAE. Vous ne pouvez pas vous inscrire.";
                }
            } catch (PDOException $e) {
                // Si la table gmail n'existe pas, on bloque l'inscription
                $errors[] = "Erreur de vérification : la base de données des étudiants n'est pas accessible. Veuillez contacter l'administration.";
                error_log("Table gmail non trouvée lors de l'inscription: " . $e->getMessage());
            }
        }

        // Validation username
        if (empty($username)) {
            $errors[] = "Le nom d'utilisateur est obligatoire.";
        } elseif (strlen($username) < 3) {
            $errors[] = "Le nom d'utilisateur doit contenir au moins 3 caractères.";
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = "Le nom d'utilisateur ne peut contenir que des lettres, chiffres et underscores.";
        }

        // Validation mot de passe
        if (empty($password)) {
            $errors[] = "Le mot de passe est obligatoire.";
        } elseif (strlen($password) < 6) {
            $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
        }

        // Validation confirmation mot de passe
        if ($password !== $confirm_password) {
            $errors[] = "Les mots de passe ne correspondent pas.";
        }

        // Validation classe
        if (empty($classe)) {
            $errors[] = "La classe est obligatoire.";
        } elseif (!in_array($classe, ['AS1', 'AS2', 'AS3'])) {
            $errors[] = "Classe invalide.";
        }

        // Vérification si l'email existe déjà
        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = "Cette adresse email est déjà utilisée.";
            }
        }

        // Vérification si le username existe déjà
        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $errors[] = "Ce nom d'utilisateur est déjà utilisé.";
            }
        }

        // Si aucune erreur, création de l'utilisateur
        if (empty($errors)) {
            // Hashage du mot de passe
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Insertion dans la base de données
            $stmt = $pdo->prepare("
                INSERT INTO users (email, username, password_hash, classe, role) 
                VALUES (?, ?, ?, ?, 'student')
            ");
            
            if ($stmt->execute([$email, $username, $password_hash, $classe])) {
                $success_message = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
                
                // Log de l'activité
                $user_id = $pdo->lastInsertId();
                $stmt = $pdo->prepare("
                    INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent) 
                    VALUES (?, 'inscription', 'Nouvelle inscription utilisateur', ?, ?)
                ");
                $stmt->execute([$user_id, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']);

                // Redirection après 3 secondes
                header("refresh:3;url=login.php");
            } else {
                $errors[] = "Erreur lors de l'inscription. Veuillez réessayer.";
            }
        }

        // Affichage des erreurs
        if (!empty($errors)) {
            $error_message = implode('<br>', $errors);
            // Sauvegarde des données pour réaffichage
            $form_data = [
                'email' => $email,
                'username' => $username,
                'classe' => $classe
            ];
        }

    } catch (PDOException $e) {
        $error_message = "Erreur de base de données. Veuillez réessayer plus tard.";
        error_log("Erreur inscription: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Vote ENSAE</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/inscription.css">
</head>

<body>

   <?php include('components/header_home.php'); ?>

    <div class="container">
        <main class="page-content">
            <div class="auth-container">
                <h1>Créer un compte</h1>

                <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
                <?php endif; ?>

                <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                    <p>Redirection vers la page de connexion...</p>
                </div>
                <?php endif; ?>

                <form id="registrationForm" class="auth-form" method="POST" action="">
                    <div class="form-group">
                        <label for="reg-email">Adresse email ENSAE</label>
                        <input type="email" id="reg-email" name="reg-email"
                            value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" required
                            placeholder="votre email">
                        <small>Utilisez votre email ENSAE officiel</small>
                    </div>

                    <div class="form-group">
                        <label for="reg-username">Nom d'utilisateur</label>
                        <input type="text" id="reg-username" name="reg-username"
                            value="<?php echo htmlspecialchars($form_data['username'] ?? ''); ?>" required
                            placeholder="votre nom d'utilisateur" pattern="[a-zA-Z0-9_]+"
                            title="Lettres, chiffres et underscores uniquement">
                        <small>3 caractères minimum, lettres, chiffres et underscores uniquement</small>
                    </div>

                    <div class="form-group">
                        <label for="reg-password">Mot de passe</label>
                        <input type="password" id="reg-password" name="reg-password" required minlength="6"
                            placeholder="Votre mot de passe">
                        <small>6 caractères minimum</small>
                    </div>

                    <div class="form-group">
                        <label for="reg-confirm-password">Confirmer le mot de passe</label>
                        <input type="password" id="reg-confirm-password" name="reg-confirm-password" required
                            placeholder="Confirmez votre mot de passe">
                    </div>

                    <div class="form-group">
                        <label for="reg-classe">Classe</label>
                        <select id="reg-classe" name="reg-classe" required>
                            <option value="">-- Sélectionnez votre classe --</option>
                            <option value="AS1" <?php echo ($form_data['classe'] ?? '') === 'AS1' ? 'selected' : ''; ?>>
                                AS1</option>
                            <option value="AS2" <?php echo ($form_data['classe'] ?? '') === 'AS2' ? 'selected' : ''; ?>>
                                AS2</option>
                            <option value="AS3" <?php echo ($form_data['classe'] ?? '') === 'AS3' ? 'selected' : ''; ?>>
                                AS3</option>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">S'inscrire</button>
                        <a href="login.php" class="btn btn-secondary">Déjà un compte ? Se connecter</a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <?php include('components/footer_home.php'); ?>


    <script>
    // Validation côté client
    document.getElementById('registrationForm').addEventListener('submit', function(e) {
        const password = document.getElementById('reg-password').value;
        const confirmPassword = document.getElementById('reg-confirm-password').value;
        const email = document.getElementById('reg-email').value;
        const username = document.getElementById('reg-username').value;

        // Vérification des mots de passe
        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Les mots de passe ne correspondent pas.');
            return false;
        }

        // Vérification de la longueur du mot de passe
        if (password.length < 6) {
            e.preventDefault();
            alert('Le mot de passe doit contenir au moins 6 caractères.');
            return false;
        }



        // Vérification du nom d'utilisateur
        if (username.length < 3) {
            e.preventDefault();
            alert('Le nom d\'utilisateur doit contenir au moins 3 caractères.');
            return false;
        }

        if (!/^[a-zA-Z0-9_]+$/.test(username)) {
            e.preventDefault();
            alert('Le nom d\'utilisateur ne peut contenir que des lettres, chiffres et underscores.');
            return false;
        }
    });

    // Validation en temps réel
    document.getElementById('reg-confirm-password').addEventListener('input', function() {
        const password = document.getElementById('reg-password').value;
        const confirmPassword = this.value;

        if (password !== confirmPassword && confirmPassword !== '') {
            this.setCustomValidity('Les mots de passe ne correspondent pas.');
        } else {
            this.setCustomValidity('');
        }
    });
    </script>

</body>

</html>