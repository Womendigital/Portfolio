<?php
require_once 'protect.php';
require_once __DIR__ . '/../config/database.php';

// Vérifier l'ID de l'alerte
$id = $_GET['id'] ?? 0;
if (!$id) {
    header('Location: alerts.php?error=no_id');
    exit;
}

// Initialiser les variables
$error = '';
$success = '';
$alerte = null;
$historique = [];

try {
    // Récupérer les détails de l'alerte
    $stmt = $pdo->prepare('SELECT 
        s.*, 
        t.nom as type_alerte, t.couleur,
        su.code_tracking
        FROM signalements s
        LEFT JOIN types_alerte t ON s.type_alerte_id = t.id
        LEFT JOIN suivis su ON s.id = su.signalement_id
        WHERE s.id = ?');
    $stmt->execute([$id]);
    $alerte = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$alerte) {
        header('Location: alerts.php?error=notfound');
        exit;
    }
    
    // Récupérer l'historique des statuts
    $stmt = $pdo->prepare('SELECT * FROM audit_log WHERE signalement_id = ? AND action_type = "modification_statut" ORDER BY timestamp DESC');
    $stmt->execute([$id]);
    $historique = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Traitement du formulaire de mise à jour
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nouveau_statut = trim($_POST['statut'] ?? '');
        $commentaire = trim($_POST['commentaire'] ?? '');
        
        // Validation
        $statuts_autorises = ['nouveau', 'en_cours', 'traite', 'cloture', 'rejete'];
        
        if (!in_array($nouveau_statut, $statuts_autorises)) {
            $error = 'Statut invalide.';
        } elseif ($nouveau_statut === $alerte['statut']) {
            $error = 'Le statut est déjà "' . $alerte['statut'] . '".';
        } else {
            // Démarrer une transaction
            $pdo->beginTransaction();
            
            try {
                // Mettre à jour le statut
                $stmt = $pdo->prepare('UPDATE signalements SET statut = ?, date_maj = NOW() WHERE id = ?');
                $stmt->execute([$nouveau_statut, $id]);
                
                // Journaliser dans audit_log (le trigger s'en occupe déjà)
                // Mais nous ajoutons une entrée manuelle avec le commentaire
                $details = "Changement de statut: " . $alerte['statut'] . " → " . $nouveau_statut;
                if ($commentaire) {
                    $details .= " - Commentaire: " . $commentaire;
                }
                
                $stmt = $pdo->prepare('INSERT INTO audit_log 
                    (signalement_id, action_type, ancien_statut, nouveau_statut, details, ip_address, user_agent, hash_entree) 
                    VALUES (?, "modification_statut", ?, ?, ?, ?, ?, ?)');
                
                $hash_entree = hash('sha256', $id . time() . rand() . ADMIN_USERNAME);
                $stmt->execute([
                    $id,
                    $alerte['statut'],
                    $nouveau_statut,
                    $details,
                    $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                    $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                    $hash_entree
                ]);
                
                // Valider la transaction
                $pdo->commit();
                
                // Mettre à jour l'objet alerte
                $alerte['statut'] = $nouveau_statut;
                $alerte['date_maj'] = date('Y-m-d H:i:s');
                
                // Ajouter à l'historique local
                array_unshift($historique, [
                    'ancien_statut' => $alerte['statut'],
                    'nouveau_statut' => $nouveau_statut,
                    'details' => $details,
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
                
                $success = 'Statut mis à jour avec succès !';
                
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
        }
    }
    
} catch (Exception $e) {
    $error = "Erreur lors du traitement: " . $e->getMessage();
}

// Définition des statuts disponibles
$statuts = [
    'nouveau' => [
        'label' => 'Nouveau',
        'description' => 'Alerte nouvellement reçue, en attente de traitement',
        'icon' => '🆕',
        'color' => '#3b82f6'
    ],
    'en_cours' => [
        'label' => 'En cours',
        'description' => 'Alerte en cours d\'investigation',
        'icon' => '🔄',
        'color' => '#f59e0b'
    ],
    'traite' => [
        'label' => 'Traité',
        'description' => 'Alerte traitée, en attente de clôture',
        'icon' => '✅',
        'color' => '#10b981'
    ],
    'cloture' => [
        'label' => 'Clôturé',
        'description' => 'Alerte clôturée définitivement',
        'icon' => '🔒',
        'color' => '#6b7280'
    ],
    'rejete' => [
        'label' => 'Rejeté',
        'description' => 'Alerte rejetée (non fondée, hors champ, etc.)',
        'icon' => '❌',
        'color' => '#ef4444'
    ]
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Modifier le statut - Administration</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0f1724;
            --card: #1a2332;
            --accent: #441c8a;
            --accent-light: #6d28d9;
            --muted: #94a3b8;
            --text: #e2e8f0;
            --success: #059669;
            --warning: #d97706;
            --danger: #dc2626;
            --info: #3b82f6;
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }
        
        /* Header */
        .admin-header {
            background: rgba(15, 23, 36, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding: 0 2rem;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }
        
        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 70px;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .logo-section img {
            height: 50px;
        }
        
        .logo-text h1 {
            font-size: 1.2rem;
            color: white;
        }
        
        .logo-text p {
            font-size: 0.8rem;
            color: var(--muted);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--accent), var(--accent-light));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        .logout-btn {
            background: rgba(220, 38, 38, 0.1);
            color: #fca5a5;
            border: 1px solid rgba(220, 38, 38, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        
        .logout-btn:hover {
            background: rgba(220, 38, 38, 0.2);
        }
        
        /* Sidebar */
        .admin-container {
            max-width: 1400px;
            margin: 70px auto 0;
            display: grid;
            grid-template-columns: 240px 1fr;
            min-height: calc(100vh - 70px);
        }
        
        .sidebar {
            background: var(--card);
            border-right: 1px solid rgba(255, 255, 255, 0.05);
            padding: 2rem 0;
        }
        
        .nav-links {
            display: flex;
            flex-direction: column;
        }
        
        .nav-link {
            padding: 0.75rem 2rem;
            color: var(--muted);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(68, 28, 138, 0.1);
            color: white;
            border-left-color: var(--accent);
        }
        
        /* Main Content */
        .main-content {
            padding: 2rem;
            overflow-y: auto;
        }
        
        .page-header {
            margin-bottom: 2rem;
        }
        
        .page-header h2 {
            font-size: 1.8rem;
            color: white;
            margin-bottom: 0.5rem;
        }
        
        .page-header p {
            color: var(--muted);
        }
        
        /* Alert Info */
        .alert-info-card {
            background: var(--card);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.05);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .alert-details h3 {
            font-size: 1.3rem;
            color: white;
            margin-bottom: 0.5rem;
        }
        
        .alert-meta {
            display: flex;
            gap: 2rem;
            color: var(--muted);
            font-size: 0.9rem;
        }
        
        .current-status {
            text-align: right;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .status-nouveau { background: rgba(59, 130, 246, 0.2); color: #93c5fd; }
        .status-en_cours { background: rgba(217, 119, 6, 0.2); color: #fbbf24; }
        .status-traite { background: rgba(5, 150, 105, 0.2); color: #34d399; }
        .status-cloture { background: rgba(107, 114, 128, 0.2); color: #9ca3af; }
        .status-rejete { background: rgba(220, 38, 38, 0.2); color: #fca5a5; }
        
        /* Status Update Form */
        .update-card {
            background: var(--card);
            border-radius: 12px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.05);
            margin-bottom: 2rem;
        }
        
        .form-header {
            margin-bottom: 2rem;
        }
        
        .form-header h3 {
            color: white;
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }
        
        .form-header p {
            color: var(--muted);
        }
        
        /* Messages */
        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .message-success {
            background: rgba(5, 150, 105, 0.1);
            border: 1px solid rgba(5, 150, 105, 0.2);
            color: #34d399;
        }
        
        .message-error {
            background: rgba(220, 38, 38, 0.1);
            border: 1px solid rgba(220, 38, 38, 0.2);
            color: #fca5a5;
        }
        
        /* Status Options */
        .status-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .status-option {
            padding: 1.5rem;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            background: rgba(255, 255, 255, 0.02);
        }
        
        .status-option:hover {
            border-color: rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.05);
            transform: translateY(-2px);
        }
        
        .status-option.selected {
            border-color: var(--accent-light);
            background: rgba(109, 40, 217, 0.1);
            box-shadow: 0 10px 30px rgba(68, 28, 138, 0.2);
        }
        
        .status-option.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }
        
        .status-icon {
            font-size: 2rem;
            margin-bottom: 0.75rem;
        }
        
        .status-label {
            font-weight: 600;
            color: white;
            margin-bottom: 0.25rem;
        }
        
        .status-desc {
            color: var(--muted);
            font-size: 0.85rem;
            line-height: 1.4;
        }
        
        /* Comment Form */
        .comment-form {
            margin-bottom: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        label {
            display: block;
            color: var(--muted);
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        textarea {
            width: 100%;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: white;
            font-family: inherit;
            font-size: 0.95rem;
            resize: vertical;
            min-height: 100px;
        }
        
        textarea:focus {
            outline: none;
            border-color: var(--accent-light);
            box-shadow: 0 0 0 3px rgba(109, 40, 217, 0.1);
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.95rem;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--accent), var(--accent-light));
            color: white;
        }
        
        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(68, 28, 138, 0.4);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text);
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-decoration: none;
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }
        
        /* Status History */
        .history-card {
            background: var(--card);
            border-radius: 12px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .history-header {
            margin-bottom: 1.5rem;
        }
        
        .history-header h3 {
            color: white;
            font-size: 1.2rem;
        }
        
        .history-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .history-item {
            padding: 1.25rem;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 8px;
            border-left: 3px solid var(--accent);
        }
        
        .history-header-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .history-status {
            font-weight: 600;
            color: white;
        }
        
        .history-time {
            color: var(--muted);
            font-size: 0.875rem;
        }
        
        .history-details {
            color: var(--muted);
            font-size: 0.9rem;
            line-height: 1.5;
        }
        
        .history-user {
            color: var(--accent-light);
            font-weight: 500;
        }
        
        /* Workflow Visualization */
        .workflow-container {
            background: var(--card);
            border-radius: 12px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.05);
            margin-bottom: 2rem;
        }
        
        .workflow-header {
            margin-bottom: 1.5rem;
        }
        
        .workflow-header h3 {
            color: white;
            font-size: 1.2rem;
        }
        
        .workflow-steps {
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            margin: 2rem 0;
        }
        
        .workflow-steps::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 2px;
            background: rgba(255, 255, 255, 0.1);
            z-index: 1;
        }
        
        .workflow-step {
            position: relative;
            z-index: 2;
            text-align: center;
            width: 120px;
        }
        
        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.75rem;
            font-size: 1.2rem;
            transition: all 0.3s;
        }
        
        .step-circle.active {
            background: var(--accent);
            color: white;
            transform: scale(1.1);
            box-shadow: 0 0 20px rgba(109, 40, 217, 0.5);
        }
        
        .step-circle.completed {
            background: var(--success);
            color: white;
        }
        
        .step-label {
            color: var(--muted);
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .step-label.active {
            color: white;
            font-weight: 600;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .admin-container {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                display: none;
            }
            
            .alert-info-card {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
            
            .current-status {
                text-align: left;
            }
            
            .status-options {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .workflow-steps {
                flex-wrap: wrap;
                justify-content: center;
                gap: 2rem;
            }
            
            .workflow-step {
                width: 80px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="admin-header">
        <div class="header-content">
            <div class="logo-section">
                <img src="../image/logoofficielle.png" alt="Alerte Sénégal">
                <div class="logo-text">
                    <h1>Modifier le statut</h1>
                    <p>Suivi et gestion des alertes</p>
                </div>
            </div>
            
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr(ADMIN_USERNAME, 0, 2)); ?>
                </div>
                <div>
                    <div style="font-weight: 600;"><?php echo htmlspecialchars($_SESSION['admin_fullname']); ?></div>
                    <div style="font-size: 0.8rem; color: var(--muted);"><?php echo htmlspecialchars(ADMIN_ROLE); ?></div>
                </div>
                <a href="logout.php" class="logout-btn">Déconnexion</a>
            </div>
        </div>
    </header>
    
    <!-- Main Container -->
    <div class="admin-container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="nav-links">
                <a href="index.php" class="nav-link">
                    📊 Tableau de bord
                </a>
                <a href="alerts.php" class="nav-link">
                    ⚡ Alertes
                </a>
                <a href="stats.php" class="nav-link">
                    📈 Statistiques
                </a>
                <a href="settings.php" class="nav-link">
                    ⚙️ Paramètres
                </a>
            </div>
        </nav>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h2>Mise à jour du statut</h2>
                <p>Modifiez et suivez l'évolution du traitement de cette alerte</p>
            </div>
            
            <!-- Alert Information -->
            <div class="alert-info-card">
                <div class="alert-details">
                    <h3>Alerte #<?php echo $id; ?></h3>
                    <div class="alert-meta">
                        <div>
                            <strong>Type:</strong> <?php echo htmlspecialchars($alerte['type_alerte'] ?? 'Non spécifié'); ?>
                        </div>
                        <div>
                            <strong>Création:</strong> <?php echo date('d/m/Y H:i', strtotime($alerte['date_creation'])); ?>
                        </div>
                        <div>
                            <strong>Code:</strong> <?php echo $alerte['code_tracking'] ?? 'N/A'; ?>
                        </div>
                    </div>
                </div>
                <div class="current-status">
                    <div class="status-badge status-<?php echo $alerte['statut']; ?>">
                        <?php echo $statuts[$alerte['statut']]['label']; ?>
                    </div>
                    <div style="color: var(--muted); font-size: 0.9rem;">
                        Statut actuel
                    </div>
                </div>
            </div>
            
            <!-- Messages -->
            <?php if ($success): ?>
                <div class="message message-success">
                    <span>✅</span>
                    <span><?php echo $success; ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message message-error">
                    <span>❌</span>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>
            
            <!-- Workflow Visualization -->
            <div class="workflow-container">
                <div class="workflow-header">
                    <h3>Workflow de traitement</h3>
                    <p style="color: var(--muted); margin-top: 0.5rem;">
                        Visualisez le parcours de traitement de cette alerte
                    </p>
                </div>
                
                <div class="workflow-steps">
                    <?php 
                    $workflow_steps = ['nouveau', 'en_cours', 'traite', 'cloture'];
                    $current_step_index = array_search($alerte['statut'], $workflow_steps);
                    ?>
                    
                    <?php foreach ($workflow_steps as $index => $step): ?>
                    <div class="workflow-step">
                        <div class="step-circle 
                            <?php echo $index < $current_step_index ? 'completed' : ''; ?>
                            <?php echo $index == $current_step_index ? 'active' : ''; ?>">
                            <?php echo $statuts[$step]['icon']; ?>
                        </div>
                        <div class="step-label <?php echo $index == $current_step_index ? 'active' : ''; ?>">
                            <?php echo $statuts[$step]['label']; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($alerte['statut'] === 'rejete'): ?>
                <div style="text-align: center; padding: 1rem; background: rgba(220, 38, 38, 0.1); border-radius: 8px; color: #fca5a5;">
                    ⚠️ Cette alerte a été rejetée. Pour la réactiver, changez son statut vers un autre état.
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Update Form -->
            <div class="update-card">
                <div class="form-header">
                    <h3>Sélectionnez le nouveau statut</h3>
                    <p>Choisissez le statut approprié pour cette alerte. Le changement sera journalisé.</p>
                </div>
                
                <form method="POST" id="statusForm">
                    <!-- Status Options -->
                    <div class="status-options" id="statusOptions">
                        <?php foreach ($statuts as $value => $info): ?>
                        <div class="status-option 
                            <?php echo $value === $alerte['statut'] ? 'disabled' : ''; ?>"
                            data-value="<?php echo $value; ?>"
                            onclick="<?php echo $value === $alerte['statut'] ? '' : "selectStatus('$value')"; ?>">
                            <div class="status-icon"><?php echo $info['icon']; ?></div>
                            <div class="status-label"><?php echo $info['label']; ?></div>
                            <div class="status-desc"><?php echo $info['description']; ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Hidden input for selected status -->
                    <input type="hidden" name="statut" id="selectedStatus" value="" required>
                    
                    <!-- Comment -->
                    <div class="comment-form">
                        <div class="form-group">
                            <label for="commentaire">Commentaire (optionnel)</label>
                            <textarea 
                                id="commentaire" 
                                name="commentaire" 
                                placeholder="Ajoutez un commentaire pour expliquer le changement de statut..."
                                maxlength="500"></textarea>
                            <div style="text-align: right; color: var(--muted); font-size: 0.85rem; margin-top: 0.25rem;">
                                <span id="charCount">0</span>/500 caractères
                            </div>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="form-actions">
                        <a href="details.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                            ← Retour aux détails
                        </a>
                        <button type="submit" id="submitBtn" class="btn btn-primary" disabled>
                            💾 Enregistrer le changement
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Status History -->
            <div class="history-card">
                <div class="history-header">
                    <h3>Historique des statuts</h3>
                    <p style="color: var(--muted); margin-top: 0.5rem;">
                        Journal complet des modifications de statut
                    </p>
                </div>
                
                <?php if (empty($historique)): ?>
                <div style="text-align: center; padding: 3rem; color: var(--muted);">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📝</div>
                    Aucun changement de statut enregistré pour cette alerte.
                </div>
                <?php else: ?>
                <div class="history-list">
                    <?php foreach ($historique as $entry): ?>
                    <div class="history-item">
                        <div class="history-header-line">
                            <div class="history-status">
                                <?php 
                                $old_status = $entry['ancien_statut'] ?? 'inconnu';
                                $new_status = $entry['nouveau_statut'] ?? 'inconnu';
                                echo $statuts[$old_status]['label'] ?? $old_status; 
                                ?> 
                                → 
                                <?php echo $statuts[$new_status]['label'] ?? $new_status; ?>
                            </div>
                            <div class="history-time">
                                <?php echo date('d/m/Y H:i', strtotime($entry['timestamp'])); ?>
                            </div>
                        </div>
                        <div class="history-details">
                            <?php if ($entry['details']): ?>
                            <?php echo htmlspecialchars($entry['details']); ?>
                            <?php endif; ?>
                            
                            <?php if (isset($entry['ip_address']) && $entry['ip_address'] !== '0.0.0.0'): ?>
                            <div style="margin-top: 0.5rem; font-size: 0.85rem;">
                                IP: <?php echo htmlspecialchars($entry['ip_address']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script>
        // Current status
        const currentStatus = '<?php echo $alerte['statut']; ?>';
        let selectedStatus = '';
        
        // Select status
        function selectStatus(status) {
            selectedStatus = status;
            document.getElementById('selectedStatus').value = status;
            
            // Update UI
            document.querySelectorAll('.status-option').forEach(option => {
                if (option.dataset.value === status) {
                    option.classList.add('selected');
                } else {
                    option.classList.remove('selected');
                }
            });
            
            // Enable submit button
            document.getElementById('submitBtn').disabled = false;
            
            // Show confirmation message
            const currentLabel = document.querySelector(`.status-option[data-value="${currentStatus}"] .status-label`).textContent;
            const newLabel = document.querySelector(`.status-option[data-value="${status}"] .status-label`).textContent;
            
            // Update workflow visualization
            updateWorkflow(status);
        }
        
        // Update workflow visualization
        function updateWorkflow(newStatus) {
            const steps = ['nouveau', 'en_cours', 'traite', 'cloture'];
            const newIndex = steps.indexOf(newStatus);
            
            document.querySelectorAll('.workflow-step').forEach((step, index) => {
                const circle = step.querySelector('.step-circle');
                const label = step.querySelector('.step-label');
                
                // Remove all classes
                circle.classList.remove('active', 'completed');
                label.classList.remove('active');
                
                // Add appropriate classes
                if (index < newIndex) {
                    circle.classList.add('completed');
                } else if (index === newIndex) {
                    circle.classList.add('active');
                    label.classList.add('active');
                }
            });
        }
        
        // Character counter for comment
        document.getElementById('commentaire').addEventListener('input', function() {
            const charCount = this.value.length;
            document.getElementById('charCount').textContent = charCount;
            
            if (charCount > 500) {
                this.value = this.value.substring(0, 500);
                document.getElementById('charCount').textContent = 500;
                document.getElementById('charCount').style.color = 'var(--danger)';
            } else if (charCount > 450) {
                document.getElementById('charCount').style.color = 'var(--warning)';
            } else {
                document.getElementById('charCount').style.color = 'var(--muted)';
            }
        });
        
        // Form submission confirmation
        document.getElementById('statusForm').addEventListener('submit', function(e) {
            if (!selectedStatus) {
                e.preventDefault();
                alert('Veuillez sélectionner un statut.');
                return;
            }
            
            const currentLabel = document.querySelector(`.status-option[data-value="${currentStatus}"] .status-label`).textContent;
            const newLabel = document.querySelector(`.status-option[data-value="${selectedStatus}"] .status-label`).textContent;
            
            if (!confirm(`Confirmez-vous le changement de statut de "${currentLabel}" à "${newLabel}" ?`)) {
                e.preventDefault();
                return;
            }
            
            // Disable button to prevent double submission
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '⏳ Enregistrement en cours...';
        });
        
        // Prevent resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
        
        // Initialize character counter
        document.getElementById('commentaire').dispatchEvent(new Event('input'));
        
        // Show status descriptions on hover
        document.querySelectorAll('.status-option').forEach(option => {
            if (!option.classList.contains('disabled')) {
                option.addEventListener('mouseenter', function() {
                    const status = this.dataset.value;
                    const label = this.querySelector('.status-label').textContent;
                    const desc = this.querySelector('.status-desc').textContent;
                    
                    // You could add a tooltip here if needed
                });
            }
        });
        
        // Keyboard navigation for status options
        document.addEventListener('keydown', function(e) {
            if (e.target.tagName === 'TEXTAREA') return;
            
            const options = Array.from(document.querySelectorAll('.status-option:not(.disabled)'));
            if (options.length === 0) return;
            
            let currentIndex = options.findIndex(opt => opt.classList.contains('selected'));
            
            switch(e.key) {
                case 'ArrowRight':
                case 'ArrowDown':
                    e.preventDefault();
                    currentIndex = (currentIndex + 1) % options.length;
                    break;
                case 'ArrowLeft':
                case 'ArrowUp':
                    e.preventDefault();
                    currentIndex = (currentIndex - 1 + options.length) % options.length;
                    break;
                case 'Enter':
                    if (currentIndex >= 0) {
                        e.preventDefault();
                        options[currentIndex].click();
                        document.getElementById('commentaire').focus();
                    }
                    break;
                default:
                    return;
            }
            
            if (currentIndex >= 0) {
                selectStatus(options[currentIndex].dataset.value);
            }
        });
        
        // Auto-select first option if none selected and form submitted
        document.getElementById('statusForm').addEventListener('submit', function(e) {
            if (!selectedStatus) {
                e.preventDefault();
                const firstOption = document.querySelector('.status-option:not(.disabled)');
                if (firstOption) {
                    selectStatus(firstOption.dataset.value);
                    setTimeout(() => {
                        this.submit();
                    }, 100);
                } else {
                    alert('Aucun statut disponible pour la sélection.');
                }
            }
        });
    </script>
</body>
</html>