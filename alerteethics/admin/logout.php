<?php
session_start();

// Journaliser la déconnexion
if (isset($_SESSION['admin_id'])) {
    require_once __DIR__ . '/../config/database.php';
    try {
        $stmt = $pdo->prepare('INSERT INTO audit_log (signalement_id, action_type, details, ip_address, user_agent, hash_entree) VALUES (0, "consultation", ?, ?, ?, ?)');
        $stmt->execute([
            'Déconnexion admin: ' . ($_SESSION['admin_username'] ?? 'unknown'),
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            hash('sha256', ($_SESSION['admin_id'] ?? 0) . time() . rand())
        ]);
    } catch (Exception $e) {
        // Ignorer les erreurs de journalisation
    }
}

// Détruire la session
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Rediriger vers la page de connexion
header('Location: login.php?logout=1');
exit;
?>