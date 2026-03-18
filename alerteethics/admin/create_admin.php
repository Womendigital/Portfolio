<?php
session_start();

// Script pour créer le premier compte admin quand aucun n'existe
// Usage : ouvrir dans le navigateur à /admin/create_admin.php (à supprimer après usage)

require_once __DIR__ . '/../config/database.php';

// Vérifier que $pdo est disponible
if (!isset($pdo)) {
    http_response_code(500);
    echo "<h1>Erreur</h1><p>PDO non disponible. Vérifiez la connexion à la base de données.</p>";
    exit;
}

// Vérifier si la table admin_users existe et s'il y a au moins un admin
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM admin_users");
    $count = (int)$stmt->fetchColumn();
} catch (Exception $e) {
    // Table peut ne pas exister
    $count = null;
}

if ($count === null) {
    // Afficher les instructions pour créer la table
    echo "<h1>Table `admin_users` introuvable</h1>";
    echo "<p>Créez la table <code>admin_users</code> dans la base de données puis rechargez cette page.</p>";
    echo "<pre>CREATE TABLE admin_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  nom_complet VARCHAR(100) NOT NULL,
  role ENUM('super_admin','admin','moderateur') DEFAULT 'moderateur',
  actif TINYINT(1) DEFAULT 1,
  deux_fa_secret VARCHAR(32) DEFAULT NULL,
  dernier_login TIMESTAMP NULL DEFAULT NULL,
  date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  date_maj TIMESTAMP NULL DEFAULT NULL
);</pre>";
    exit;
}

// S'il y a déjà un admin, rediriger vers la page de login
if ($count > 0) {
    header('Location: login.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $nom_complet = trim($_POST['nom_complet'] ?? '');

    // Validation des champs
    if ($username === '' || $password === '' || $email === '' || $nom_complet === '') {
        $error = 'Veuillez remplir tous les champs.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse email invalide.';
    } elseif ($password !== $password2) {
        $error = 'Les mots de passe ne correspondent pas.';
    } elseif (strlen($password) < 8) {
        $error = 'Le mot de passe doit contenir au moins 8 caractères.';
    } else {
        // Hachage du mot de passe
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Préparation et exécution de la requête d'insertion
        $stmt = $pdo->prepare('INSERT INTO admin_users (username, email, password_hash, nom_complet, role) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$username, $email, $hash, $nom_complet, 'super_admin']);

        // Connecter l'admin et rediriger vers le tableau de bord
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $pdo->lastInsertId();
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_role'] = 'super_admin';
        
        header('Location: index.php');
        exit;
    }
}

?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Créer un compte administrateur</title>
  <style>
    body{font-family:Inter,system-ui,Arial;background:#0f1724;color:#e6eef6;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:20px}
    .box{background:#0b1220;padding:24px;border-radius:12px;border:1px solid rgba(255,255,255,0.04);width:100%;max-width:480px}
    label{display:block;margin-bottom:8px;font-size:14px;color:#9aa4b2}
    input{width:100%;padding:10px;margin-bottom:16px;border-radius:8px;border:1px solid rgba(255,255,255,0.06);background:transparent;color:#e6eef6;box-sizing:border-box}
    input:focus{outline:none;border-color:#441c8a}
    .btn{background:linear-gradient(90deg,#441c8a,#06b6d4);color:white;padding:12px 24px;border-radius:8px;border:0;cursor:pointer;font-weight:500;width:100%;transition:opacity 0.2s}
    .btn:hover{opacity:0.9}
    .muted{color:#9aa4b2;font-size:14px;margin-bottom:16px}
    .error{color:#ff6b6b;background:rgba(255,107,107,0.1);padding:10px;border-radius:6px;margin-bottom:16px;border-left:3px solid #ff6b6b}
    .success{color:#51cf66;background:rgba(81,207,102,0.1);padding:10px;border-radius:6px;margin-bottom:16px;border-left:3px solid #51cf66}
    pre{background:#071025;padding:12px;border-radius:6px;color:#9aa4b2;overflow:auto;font-size:13px;margin-top:16px}
    h2{margin-top:0;color:#e6eef6}
    hr{margin:20px 0;border:none;border-top:1px solid rgba(255,255,255,0.03)}
    .info-box{background:rgba(6,182,212,0.1);border:1px solid rgba(6,182,212,0.2);padding:12px;border-radius:8px;margin-bottom:20px}
    .password-rules{font-size:12px;color:#9aa4b2;margin-top:-10px;margin-bottom:16px}
  </style>
</head>
<body>
  <div class="box">
    <div class="info-box">
      <strong>⚠️ Important :</strong> Cette page est destinée à créer le premier compte administrateur. Supprimez ou protégez ce fichier après usage.
    </div>
    
    <h2>Créer le compte administrateur principal</h2>
    <p class="muted">Remplissez les informations ci-dessous pour créer le premier compte administrateur du système.</p>
    
    <?php if ($error): ?>
      <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <form method="post">
      <label for="nom_complet">Nom complet *</label>
      <input type="text" id="nom_complet" name="nom_complet" value="<?php echo htmlspecialchars($_POST['nom_complet'] ?? ''); ?>" required placeholder="Ex: Administrateur Principal">
      
      <label for="username">Nom d'utilisateur *</label>
      <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required placeholder="Ex: admin">
      
      <label for="email">Adresse email *</label>
      <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required placeholder="Ex: admin@alerteethics.sn">
      
      <label for="password">Mot de passe *</label>
      <input type="password" id="password" name="password" required placeholder="Minimum 8 caractères">
      <div class="password-rules">Le mot de passe doit contenir au moins 8 caractères.</div>
      
      <label for="password2">Confirmer le mot de passe *</label>
      <input type="password" id="password2" name="password2" required placeholder="Répétez le mot de passe">
      
      <button class="btn" type="submit">Créer le compte administrateur</button>
    </form>
    
    <hr>
    
    <div class="muted">
      <p>Le compte créé aura le rôle de <code>super_admin</code> avec tous les privilèges.</p>
      <p>Si la table <code>admin_users</code> est absente, créez-la avec la commande SQL suivante :</p>
    </div>
    
    <pre>CREATE TABLE admin_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  nom_complet VARCHAR(100) NOT NULL,
  role ENUM('super_admin','admin','moderateur') DEFAULT 'moderateur',
  actif TINYINT(1) DEFAULT 1,
  deux_fa_secret VARCHAR(32) DEFAULT NULL,
  dernier_login TIMESTAMP NULL DEFAULT NULL,
  date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  date_maj TIMESTAMP NULL DEFAULT NULL
);</pre>
    
    <div class="muted" style="margin-top: 16px; font-size: 12px;">
      <p><strong>Note :</strong> Votre base de données semble déjà contenir la table admin_users et un compte administrateur par défaut.</p>
      <p>Identifiants par défaut (selon le dump SQL) :</p>
      <ul style="margin: 8px 0; padding-left: 20px;">
        <li>Nom d'utilisateur : <code>admin</code></li>
        <li>Mot de passe : <code>password</code></li>
        <li>Email : <code>admin@alerteethics.sn</code></li>
      </ul>
    </div>
  </div>
</body>
</html>