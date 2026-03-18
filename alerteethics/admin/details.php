<?php
require_once 'protect.php';
require_once __DIR__ . '/../config/database.php';

// Vérifier l'ID de l'alerte
$id = $_GET['id'] ?? 0;
if (!$id) {
    header('Location: alerts.php');
    exit;
}

try {
    // Récupérer les détails de l'alerte
    $stmt = $pdo->prepare('SELECT 
        s.*, 
        t.nom as type_alerte, t.couleur, t.description as type_description,
        su.code_tracking,
        (SELECT COUNT(*) FROM pieces_jointes WHERE signalement_id = s.id) as pieces_jointes_count
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
    
    // Récupérer les pièces jointes
    $stmt = $pdo->prepare('SELECT * FROM pieces_jointes WHERE signalement_id = ? ORDER BY date_upload');
    $stmt->execute([$id]);
    $pieces_jointes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer l'historique d'audit
    $stmt = $pdo->prepare('SELECT * FROM audit_log WHERE signalement_id = ? ORDER BY timestamp DESC LIMIT 20');
    $stmt->execute([$id]);
    $historique = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Déchiffrer les données (simulé - en réalité vous aurez besoin de la clé)
    $contenu_json = '{"type": "' . ($alerte['type_alerte'] ?? '') . '", "description": "Contenu chiffré - nécessite clé de déchiffrement", "timestamp": "' . $alerte['date_creation'] . '"}';
    $contenu = json_decode($contenu_json, true);
    
} catch (Exception $e) {
    $error = "Erreur lors du chargement des détails: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Détail alerte #<?php echo $id; ?> - Administration</title>
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
        
        /* Header Actions */
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--muted);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .back-link:hover {
            background: rgba(255, 255, 255, 0.05);
            color: white;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--accent), var(--accent-light));
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(68, 28, 138, 0.4);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        /* Alert Header */
        .alert-header {
            background: var(--card);
            border-radius: 12px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.05);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .alert-info h2 {
            font-size: 1.5rem;
            color: white;
            margin-bottom: 0.5rem;
        }
        
        .alert-id {
            display: inline-block;
            background: rgba(68, 28, 138, 0.2);
            color: var(--accent-light);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .alert-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-top: 1rem;
        }
        
        .meta-item {
            display: flex;
            flex-direction: column;
        }
        
        .meta-label {
            color: var(--muted);
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }
        
        .meta-value {
            font-weight: 600;
            color: white;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .status-nouveau { background: rgba(59, 130, 246, 0.2); color: #93c5fd; }
        .status-en_cours { background: rgba(217, 119, 6, 0.2); color: #fbbf24; }
        .status-traite { background: rgba(5, 150, 105, 0.2); color: #34d399; }
        .status-cloture { background: rgba(107, 114, 128, 0.2); color: #9ca3af; }
        .status-rejete { background: rgba(220, 38, 38, 0.2); color: #fca5a5; }
        
        .gravite-badge {
            display: inline-block;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            text-align: center;
            line-height: 32px;
            font-weight: 700;
            font-size: 1rem;
        }
        
        .gravite-1 { background: rgba(34, 197, 94, 0.2); color: #4ade80; }
        .gravite-2 { background: rgba(234, 179, 8, 0.2); color: #facc15; }
        .gravite-3 { background: rgba(249, 115, 22, 0.2); color: #fb923c; }
        .gravite-4 { background: rgba(239, 68, 68, 0.2); color: #f87171; }
        .gravite-5 { background: rgba(185, 28, 28, 0.2); color: #dc2626; }
        
        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Content Card */
        .content-card {
            background: var(--card);
            border-radius: 12px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .card-header h3 {
            color: white;
            font-size: 1.2rem;
        }
        
        /* Content Display */
        .content-display {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .content-section {
            margin-bottom: 1.5rem;
        }
        
        .content-section h4 {
            color: var(--muted);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .content-text {
            color: white;
            line-height: 1.6;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        
        /* Files List */
        .files-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .file-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .file-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .file-icon {
            width: 40px;
            height: 40px;
            background: rgba(68, 28, 138, 0.2);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent-light);
            font-size: 1.2rem;
        }
        
        .file-details h4 {
            color: white;
            margin-bottom: 0.25rem;
        }
        
        .file-meta {
            color: var(--muted);
            font-size: 0.875rem;
        }
        
        /* Blockchain Info */
        .blockchain-info {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .blockchain-hash {
            font-family: monospace;
            background: rgba(0, 0, 0, 0.3);
            padding: 0.75rem;
            border-radius: 6px;
            word-break: break-all;
            font-size: 0.9rem;
            color: #93c5fd;
        }
        
        /* History List */
        .history-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .history-item {
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            border-left: 3px solid var(--accent);
        }
        
        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .history-action {
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
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .admin-container {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                display: none;
            }
            
            .alert-header {
                flex-direction: column;
                gap: 1.5rem;
            }
            
            .action-buttons {
                flex-wrap: wrap;
                justify-content: flex-start;
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
                    <h1>Détail de l'alerte</h1>
                    <p>Consultez toutes les informations</p>
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
            <!-- Header Actions -->
            <div class="header-actions">
                <a href="alerts.php" class="back-link">
                    ← Retour à la liste
                </a>
                <div class="action-buttons">
                    <a href="update_statut.php?id=<?php echo $id; ?>" class="btn btn-primary">
                        ✏️ Modifier le statut
                    </a>
                    <button onclick="printPage()" class="btn btn-secondary">
                        🖨️ Imprimer
                    </button>
                </div>
            </div>
            
            <!-- Alert Header -->
            <div class="alert-header">
                <div class="alert-info">
                    <span class="alert-id">ALERTE #<?php echo $id; ?></span>
                    <h2><?php echo htmlspecialchars($alerte['type_alerte'] ?? 'Non spécifié'); ?></h2>
                    
                    <div class="alert-meta">
                        <div class="meta-item">
                            <span class="meta-label">Création</span>
                            <span class="meta-value">
                                <?php echo date('d/m/Y à H:i', strtotime($alerte['date_creation'])); ?>
                            </span>
                        </div>
                        
                        <div class="meta-item">
                            <span class="meta-label">Dernière mise à jour</span>
                            <span class="meta-value">
                                <?php echo $alerte['date_maj'] ? date('d/m/Y à H:i', strtotime($alerte['date_maj'])) : 'Jamais'; ?>
                            </span>
                        </div>
                        
                        <div class="meta-item">
                            <span class="meta-label">Code de suivi</span>
                            <span class="meta-value">
                                <?php echo $alerte['code_tracking'] ?: 'Non généré'; ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div style="text-align: right;">
                    <div style="margin-bottom: 1rem;">
                        <span class="status-badge status-<?php echo $alerte['statut']; ?>">
                            <?php 
                            $statut_labels = [
                                'nouveau' => 'Nouveau',
                                'en_cours' => 'En cours',
                                'traite' => 'Traité',
                                'cloture' => 'Clôturé',
                                'rejete' => 'Rejeté'
                            ];
                            echo $statut_labels[$alerte['statut']] ?? $alerte['statut'];
                            ?>
                        </span>
                    </div>
                    
                    <?php if ($alerte['niveau_gravite']): ?>
                    <div style="margin-bottom: 0.5rem;">
                        <span class="gravite-badge gravite-<?php echo min($alerte['niveau_gravite'], 5); ?>">
                            <?php echo $alerte['niveau_gravite']; ?>
                        </span>
                        <span style="color: var(--muted); font-size: 0.9rem; margin-left: 0.5rem;">
                            Niveau de gravité
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <div style="color: var(--muted); font-size: 0.9rem;">
                        Canal: <?php echo $alerte['canal_soumission'] ?? 'web'; ?>
                    </div>
                </div>
            </div>
            
            <!-- Content Grid -->
            <div class="content-grid">
                <!-- Left Column: Main Content -->
                <div>
                    <!-- Description -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3>📝 Description détaillée</h3>
                        </div>
                        
                        <div class="content-display">
                            <div class="content-section">
                                <h4>Type de signalement</h4>
                                <div class="content-text">
                                    <?php echo htmlspecialchars($alerte['type_alerte'] ?? 'Non spécifié'); ?>
                                </div>
                            </div>
                            
                            <?php if ($alerte['type_description']): ?>
                            <div class="content-section">
                                <h4>Description du type</h4>
                                <div class="content-text">
                                    <?php echo htmlspecialchars($alerte['type_description']); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="content-section">
                                <h4>Contenu chiffré</h4>
                                <div class="content-text" style="font-family: monospace; color: var(--muted);">
                                    [CONTENU CHIFFRÉ - Nécessite clé de déchiffrement]
                                    <br><br>
                                    Hash du contenu: <?php echo $alerte['hash_contenu'] ?? 'N/A'; ?>
                                    <br>
                                    IV de chiffrement: <?php echo $alerte['iv_chiffrement'] ?? 'N/A'; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pièces jointes -->
                    <?php if ($pieces_jointes): ?>
                    <div class="content-card">
                        <div class="card-header">
                            <h3>📎 Pièces jointes (<?php echo count($pieces_jointes); ?>)</h3>
                        </div>
                        
                        <div class="files-list">
                            <?php foreach ($pieces_jointes as $file): ?>
                            <div class="file-item">
                                <div class="file-info">
                                    <div class="file-icon">
                                        <?php 
                                        $ext = pathinfo($file['nom_fichier_chiffre'], PATHINFO_EXTENSION);
                                        $icons = [
                                            'pdf' => '📕',
                                            'jpg' => '🖼️', 'jpeg' => '🖼️', 'png' => '🖼️',
                                            'doc' => '📄', 'docx' => '📄',
                                            'mp4' => '🎬', 'mov' => '🎬', 'avi' => '🎬'
                                        ];
                                        echo $icons[strtolower($ext)] ?? '📄';
                                        ?>
                                    </div>
                                    <div class="file-details">
                                        <h4>Fichier chiffré</h4>
                                        <div class="file-meta">
                                            Type: <?php echo htmlspecialchars($file['type_mime']); ?> •
                                            Taille: <?php echo round($file['taille_originale'] / 1024 / 1024, 2); ?> MB •
                                            Hash: <?php echo substr($file['hash_fichier'], 0, 16); ?>...
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <span style="color: var(--muted); font-size: 0.875rem;">
                                        <?php echo date('d/m/Y', strtotime($file['date_upload'])); ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Right Column: Side Info -->
                <div>
                    <!-- Blockchain Information -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3>🔗 Horodatage Blockchain</h3>
                        </div>
                        
                        <?php if ($alerte['blockchain_hash']): ?>
                        <div class="blockchain-info">
                            <h4 style="color: white; margin-bottom: 0.5rem;">Transaction vérifiée</h4>
                            <p style="color: var(--muted); margin-bottom: 1rem;">
                                Cette alerte a été horodatée sur la blockchain pour garantir son intégrité et sa non-altération.
                            </p>
                            <div class="blockchain-hash">
                                <?php echo $alerte['blockchain_hash']; ?>
                            </div>
                            <?php if ($alerte['blockchain_tx']): ?>
                            <div style="margin-top: 1rem;">
                                <span style="color: var(--muted);">TX ID: </span>
                                <span style="color: #93c5fd; font-family: monospace; font-size: 0.9rem;">
                                    <?php echo $alerte['blockchain_tx']; ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div style="text-align: center; padding: 2rem; color: var(--muted);">
                            <div style="font-size: 2rem; margin-bottom: 1rem;">⏳</div>
                            En attente d'horodatage blockchain
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Historique -->
                    <?php if ($historique): ?>
                    <div class="content-card">
                        <div class="card-header">
                            <h3>📋 Historique d'activité</h3>
                        </div>
                        
                        <div class="history-list">
                            <?php foreach ($historique as $entry): ?>
                            <div class="history-item">
                                <div class="history-header">
                                    <span class="history-action">
                                        <?php 
                                        $actions = [
                                            'creation' => 'Création',
                                            'modification_statut' => 'Changement de statut',
                                            'consultation' => 'Consultation',
                                            'upload_fichier' => 'Upload fichier',
                                            'acces_suivi' => 'Accès suivi'
                                        ];
                                        echo $actions[$entry['action_type']] ?? $entry['action_type'];
                                        ?>
                                    </span>
                                    <span class="history-time">
                                        <?php echo date('H:i', strtotime($entry['timestamp'])); ?>
                                    </span>
                                </div>
                                <div class="history-details">
                                    <?php if ($entry['ancien_statut'] && $entry['nouveau_statut']): ?>
                                    Statut: <?php echo $entry['ancien_statut']; ?> → <?php echo $entry['nouveau_statut']; ?>
                                    <?php endif; ?>
                                    <?php if ($entry['details']): ?>
                                    <br><?php echo htmlspecialchars($entry['details']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        function printPage() {
            window.print();
        }
        
        // Confirmation avant certaines actions
        function confirmAction(action, message) {
            return confirm(message || `Êtes-vous sûr de vouloir ${action} ?`);
        }
    </script>
</body>
</html>