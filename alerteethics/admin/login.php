<?php
session_start();

// Si déjà connecté, rediriger
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

// Générer token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Erreur de sécurité. Veuillez réessayer.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Protection brute force
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = 0;
            $_SESSION['last_attempt_time'] = time();
        }
        
        if ($_SESSION['login_attempts'] >= 5) {
            $cooldown = 900;
            if (time() - $_SESSION['last_attempt_time'] < $cooldown) {
                $error = 'Trop de tentatives. Veuillez réessayer dans 15 minutes.';
                $username = '';
            } else {
                $_SESSION['login_attempts'] = 0;
            }
        }
        
        if ($error === '' && ($username === '' || $password === '')) {
            $error = 'Veuillez remplir tous les champs.';
            $_SESSION['login_attempts']++;
            $_SESSION['last_attempt_time'] = time();
        } elseif ($error === '') {
            try {
                require_once __DIR__ . '/../config/database.php';
                
                $stmt = $pdo->prepare('SELECT id, username, password_hash, nom_complet, role, actif FROM admin_users WHERE username = ? LIMIT 1');
                $stmt->execute([$username]);
                $row = $stmt->fetch();
                
                if ($row && password_verify($password, $row['password_hash'])) {
                    if ($row['actif'] != 1) {
                        $error = 'Ce compte est désactivé.';
                    } else {
                        // Connexion réussie
                        $_SESSION['admin_logged_in'] = true;
                        $_SESSION['admin_id'] = $row['id'];
                        $_SESSION['admin_username'] = $row['username'];
                        $_SESSION['admin_fullname'] = $row['nom_complet'];
                        $_SESSION['admin_role'] = $row['role'];
                        $_SESSION['last_activity'] = time();
                        
                        // Mettre à jour dernière connexion
                        $updateStmt = $pdo->prepare('UPDATE admin_users SET dernier_login = NOW() WHERE id = ?');
                        $updateStmt->execute([$row['id']]);
                        
                        // Journaliser la connexion
                        $auditStmt = $pdo->prepare('INSERT INTO audit_log (signalement_id, action_type, details, ip_address, user_agent, hash_entree) VALUES (0, "consultation", ?, ?, ?, ?)');
                        $auditStmt->execute([
                            'Connexion admin: ' . $row['username'],
                            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                            hash('sha256', $row['id'] . time() . rand())
                        ]);
                        
                        unset($_SESSION['login_attempts']);
                        unset($_SESSION['last_attempt_time']);
                        session_regenerate_id(true);
                        
                        header('Location: index.php');
                        exit;
                    }
                } else {
                    $error = 'Identifiants incorrects.';
                    $_SESSION['login_attempts']++;
                    $_SESSION['last_attempt_time'] = time();
                }
            } catch (Exception $e) {
                error_log('[admin/login] DB error: ' . $e->getMessage());
                $error = 'Erreur technique. Veuillez réessayer plus tard.';
            }
        }
    }
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administration - Connexion</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0f1724;
            --card: #0b1220;
            --accent: #441c8a;
            --accent-light: #6d28d9;
            --muted: #94a3b8;
            --text: #e2e8f0;
            --success: #059669;
            --danger: #dc2626;
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: radial-gradient(1200px 600px at 10% 10%, rgba(124,58,237,0.06), transparent), 
                        linear-gradient(180deg, #0d1422 0%, #131b2e 100%);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-card {
            width: 100%;
            max-width: 440px;
            background: rgba(15, 23, 36, 0.9);
            border-radius: 16px;
            padding: 40px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
        }
        
        .logo-section {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo-section img {
            height: 80px;
            margin-bottom: 15px;
        }
        
        .logo-section h1 {
            color: white;
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .logo-section p {
            color: var(--muted);
            font-size: 14px;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-danger {
            background: rgba(220, 38, 38, 0.1);
            border: 1px solid rgba(220, 38, 38, 0.2);
            color: #fca5a5;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            color: var(--muted);
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
        }
        
        input {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: white;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        input:focus {
            outline: none;
            border-color: var(--accent-light);
            box-shadow: 0 0 0 3px rgba(109, 40, 217, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--accent), var(--accent-light));
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(68, 28, 138, 0.4);
        }
        
        .back-link {
            display: block;
            text-align: center;
            color: var(--muted);
            text-decoration: none;
            font-size: 14px;
            margin-top: 20px;
            padding: 10px;
            border-radius: 6px;
            transition: all 0.3s;
        }
        
        .back-link:hover {
            color: white;
            background: rgba(255, 255, 255, 0.05);
        }
        
        .security-badge {
            text-align: center;
            color: var(--muted);
            font-size: 12px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo-section">
            <img src="../image/logoofficielle.png" alt="Alerte Sénégal">
            <h1>Administration</h1>
            <p>Espace sécurisé de gestion des alertes</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="post" id="loginForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            
            <div class="form-group">
                <label for="username">Identifiant</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" 
                       required autocomplete="username" autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            
            <button type="submit" class="btn">Se connecter</button>
            
            <a href="../index.php" class="back-link">
                ← Retour au site public
            </a>
        </form>
        
        <div class="security-badge">
            🔒 Sécurisé par cryptographie et authentification forte
        </div>
    </div>
    
    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = this.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = 'Connexion en cours...';
        });
    </script>
</body>
</html>