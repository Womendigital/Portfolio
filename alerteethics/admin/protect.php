<?php
session_start();

// Vérifier si l'admin est connecté
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Vérifier l'inactivité (30 minutes par défaut)
$inactive = 1800; // 30 minutes en secondes
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $inactive) {
    session_destroy();
    header('Location: login.php?expired=1');
    exit;
}

// Mettre à jour le temps d'activité
$_SESSION['last_activity'] = time();

// Vérifications de sécurité
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_username'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Vérifier que l'admin est toujours actif en base
require_once __DIR__ . '/../config/database.php';
try {
    $stmt = $pdo->prepare('SELECT id, actif FROM admin_users WHERE id = ?');
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch();
    
    if (!$admin || $admin['actif'] != 1) {
        session_destroy();
        header('Location: login.php?inactive=1');
        exit;
    }
} catch (Exception $e) {
    // En cas d'erreur DB, déconnecter par sécurité
    session_destroy();
    header('Location: login.php?error=db');
    exit;
}

// Constantes pour faciliter l'accès
define('ADMIN_ID', $_SESSION['admin_id']);
define('ADMIN_USERNAME', $_SESSION['admin_username']);
define('ADMIN_ROLE', $_SESSION['admin_role'] ?? 'moderateur');

// Fonction pour vérifier les permissions
function hasPermission($requiredRole) {
    $roles = ['moderateur' => 1, 'admin' => 2, 'super_admin' => 3];
    return ($roles[ADMIN_ROLE] ?? 0) >= ($roles[$requiredRole] ?? 0);
}
?>