<?php
// =====================================================
// CONFIGURATION DE LA BASE DE DONNÉES
// =====================================================

// Paramètres de connexion à la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'e_election_ensae');
define('DB_USER', 'root');  // À modifier selon votre configuration
define('DB_PASS', 'M5a5r7c3@');      // À modifier selon votre configuration
define('DB_CHARSET', 'utf8mb4');

// Options PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
];

try {
    // Création de la connexion PDO
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    // Test de la connexion
    $pdo->query('SELECT 1');
    
} catch (PDOException $e) {
    // En cas d'erreur de connexion
    error_log("Erreur de connexion à la base de données: " . $e->getMessage());
    
    // Affichage d'une erreur utilisateur (en production, ne pas afficher les détails)
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        die("Erreur de connexion à la base de données: " . $e->getMessage());
    } else {
        die("Erreur de connexion à la base de données. Veuillez contacter l'administrateur.");
    }
}

// Fonction utilitaire pour nettoyer les entrées
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Fonction pour vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Fonction pour vérifier le rôle de l'utilisateur
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

// Fonction pour rediriger si non connecté
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Fonction pour rediriger si pas le bon rôle
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header('Location: accueil.php');
        exit();
    }
}

// Fonction pour obtenir les informations de l'utilisateur connecté
function getCurrentUser() {
    global $pdo;
    
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id, email, username, classe, role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération de l'utilisateur: " . $e->getMessage());
        return null;
    }
}

// Fonction pour logger une activité
function logActivity($user_id, $action, $details = '') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_id,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch (PDOException $e) {
        error_log("Erreur lors du log d'activité: " . $e->getMessage());
    }
}

// Configuration des sessions
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuration du fuseau horaire
date_default_timezone_set('Africa/Dakar');

// Configuration des erreurs (à désactiver en production)
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// Configuration de la sécurité
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));

// Headers de sécurité
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
?>