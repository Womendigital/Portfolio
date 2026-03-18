<?php
require_once 'protect.php';
require_once __DIR__ . '/../config/database.php';

// Récupérer les filtres
$type_filter = $_GET['type'] ?? '';
$statut_filter = $_GET['statut'] ?? '';
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';
$gravite_min = $_GET['gravite_min'] ?? '';
$gravite_max = $_GET['gravite_max'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 25;
$offset = ($page - 1) * $limit;

// Construire la requête avec filtres
$where_clauses = [];
$params = [];

if (!empty($type_filter)) {
    $where_clauses[] = 's.type_alerte_id = ?';
    $params[] = $type_filter;
}

if (!empty($statut_filter)) {
    $where_clauses[] = 's.statut = ?';
    $params[] = $statut_filter;
}

if (!empty($date_debut)) {
    $where_clauses[] = 's.date_creation >= ?';
    $params[] = $date_debut . ' 00:00:00';
}

if (!empty($date_fin)) {
    $where_clauses[] = 's.date_creation <= ?';
    $params[] = $date_fin . ' 23:59:59';
}

if (!empty($gravite_min)) {
    $where_clauses[] = 's.niveau_gravite >= ?';
    $params[] = $gravite_min;
}

if (!empty($gravite_max)) {
    $where_clauses[] = 's.niveau_gravite <= ?';
    $params[] = $gravite_max;
}

if (!empty($search)) {
    $where_clauses[] = '(t.nom LIKE ? OR s.id = ?)';
    $params[] = '%' . $search . '%';
    $params[] = intval($search);
}

$where_sql = $where_clauses ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Récupérer les alertes
try {
    // Compter le total
    $count_sql = "SELECT COUNT(*) as total FROM signalements s LEFT JOIN types_alerte t ON s.type_alerte_id = t.id $where_sql";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total = $stmt->fetchColumn();
    $total_pages = ceil($total / $limit);
    
    // Récupérer les données
    $sql = "SELECT 
        s.id, s.date_creation, s.date_maj, s.statut, s.niveau_gravite,
        s.blockchain_hash, s.canal_soumission,
        t.id as type_id, t.nom as type_alerte, t.couleur,
        COUNT(pj.id) as pieces_jointes
        FROM signalements s
        LEFT JOIN types_alerte t ON s.type_alerte_id = t.id
        LEFT JOIN pieces_jointes pj ON s.id = pj.signalement_id
        $where_sql
        GROUP BY s.id
        ORDER BY s.date_creation DESC
        LIMIT $limit OFFSET $offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $alertes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les types pour le filtre
    $stmt = $pdo->query('SELECT id, nom FROM types_alerte WHERE actif = 1 ORDER BY nom');
    $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "Erreur lors du chargement des alertes: " . $e->getMessage();
    $alertes = [];
    $types = [];
    $total = 0;
    $total_pages = 1;
}

// Options de statut
$statuts = [
    'nouveau' => 'Nouveau',
    'en_cours' => 'En cours',
    'traite' => 'Traité',
    'cloture' => 'Clôturé',
    'rejete' => 'Rejeté'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Liste des alertes - Administration</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0f1724;
            --card: #1a2332;
            --accent: #441c8a;
            --accent-light: #6d28d9;
            --muted: #94a3b8;
            --text: #b5b5c4ff;
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
        
        /* Filters */
        .filters-card {
            background: var(--card);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.05);
            margin-bottom: 2rem;
        }
        
        .filters-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .filters-header h3 {
            color: white;
            font-size: 1.1rem;
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        label {
            display: block;
            color: var(--muted);
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        select, input {
            width: 100%;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: white;
            font-size: 0.9rem;
            font-family: inherit;
        }
        
        select:focus, input:focus {
            outline: none;
            border-color: var(--accent-light);
            box-shadow: 0 0 0 3px rgba(109, 40, 217, 0.1);
        }
        
        .filter-actions {
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
            font-size: 0.9rem;
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
        
        /* Alerts Table */
        .alerts-card {
            background: var(--card);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .table-header h3 {
            color: white;
            font-size: 1.1rem;
        }
        
        .table-info {
            color: var(--muted);
            font-size: 0.9rem;
        }
        
        .table-container {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }
        
        th {
            text-align: left;
            padding: 1rem;
            color: var(--muted);
            font-weight: 600;
            font-size: 0.875rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            background: rgba(255, 255, 255, 0.02);
        }
        
        td {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        tr:hover {
            background: rgba(68, 28, 138, 0.05);
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-nouveau { background: rgba(59, 130, 246, 0.2); color: #93c5fd; }
        .status-en_cours { background: rgba(217, 119, 6, 0.2); color: #fbbf24; }
        .status-traite { background: rgba(5, 150, 105, 0.2); color: #34d399; }
        .status-cloture { background: rgba(107, 114, 128, 0.2); color: #9ca3af; }
        .status-rejete { background: rgba(220, 38, 38, 0.2); color: #fca5a5; }
        
        .gravite-badge {
            display: inline-block;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            text-align: center;
            line-height: 24px;
            font-weight: 600;
            font-size: 0.75rem;
        }
        
        .gravite-1 { background: rgba(34, 197, 94, 0.2); color: #4ade80; }
        .gravite-2 { background: rgba(234, 179, 8, 0.2); color: #facc15; }
        .gravite-3 { background: rgba(249, 115, 22, 0.2); color: #fb923c; }
        .gravite-4 { background: rgba(239, 68, 68, 0.2); color: #f87171; }
        .gravite-5 { background: rgba(185, 28, 28, 0.2); color: #dc2626; }
        
        .type-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .type-color {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }
        
        .files-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            background: rgba(255, 255, 255, 0.05);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .action-btn {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .action-btn.view {
            background: rgba(59, 130, 246, 0.1);
            color: #93c5fd;
            border: 1px solid rgba(59, 130, 246, 0.2);
        }
        
        .action-btn.view:hover {
            background: rgba(59, 130, 246, 0.2);
        }
        
        .action-btn.update {
            background: rgba(16, 185, 129, 0.1);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        
        .action-btn.update:hover {
            background: rgba(16, 185, 129, 0.2);
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }
        
        .page-btn {
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text);
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        
        .page-btn:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .page-btn.active {
            background: var(--accent);
            border-color: var(--accent);
            color: white;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .admin-container {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                display: none;
            }
            
            .filters-grid {
                grid-template-columns: 1fr;
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
                    <h1>Liste des alertes</h1>
                    <p>Gestion et suivi des signalements</p>
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
                <a href="alerts.php" class="nav-link active">
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
                <h2>Gestion des alertes</h2>
                <p>Consultez et gérez tous les signalements reçus</p>
            </div>
            
            <!-- Filters -->
            <div class="filters-card">
                <div class="filters-header">
                    <h3>Filtres de recherche</h3>
                </div>
                
                <form method="GET" id="filterForm">
                    <div class="filters-grid">
                        <div class="form-group">
                            <label for="type">Type d'alerte</label>
                            <select id="type" name="type">
                                <option value="">Tous les types</option>
                                <?php foreach ($types as $type): ?>
                                <option value="<?php echo $type['id']; ?>" <?php echo $type_filter == $type['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type['nom']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="statut">Statut</label>
                            <select id="statut" name="statut">
                                <option value="">Tous les statuts</option>
                                <?php foreach ($statuts as $value => $label): ?>
                                <option value="<?php echo $value; ?>" <?php echo $statut_filter == $value ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="gravite_min">Gravité min</label>
                            <select id="gravite_min" name="gravite_min">
                                <option value="">Toute gravité</option>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $gravite_min == $i ? 'selected' : ''; ?>>
                                    <?php echo $i; ?>
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="gravite_max">Gravité max</label>
                            <select id="gravite_max" name="gravite_max">
                                <option value="">Toute gravité</option>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $gravite_max == $i ? 'selected' : ''; ?>>
                                    <?php echo $i; ?>
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="date_debut">Date début</label>
                            <input type="date" id="date_debut" name="date_debut" value="<?php echo htmlspecialchars($date_debut); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="date_fin">Date fin</label>
                            <input type="date" id="date_fin" name="date_fin" value="<?php echo htmlspecialchars($date_fin); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="search">Recherche (ID ou type)</label>
                            <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="ID ou nom du type...">
                        </div>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="button" class="btn btn-secondary" onclick="resetFilters()">
                            Réinitialiser
                        </button>
                        <button type="submit" class="btn btn-primary">
                            🔍 Appliquer les filtres
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Alerts Table -->
            <div class="alerts-card">
                <div class="table-header">
                    <h3>Liste des alertes</h3>
                    <div class="table-info">
                        <?php echo $total; ?> alerte<?php echo $total > 1 ? 's' : ''; ?> trouvée<?php echo $total > 1 ? 's' : ''; ?>
                    </div>
                </div>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date création</th>
                                <th>Type</th>
                                <th>Statut</th>
                                <th>Gravité</th>
                                <th>Canal</th>
                                <th>Fichiers</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($alertes)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 3rem; color: var(--muted);">
                                    Aucune alerte trouvée avec ces critères.
                                </td>
                            </tr>
                            <?php endif; ?>
                            
                            <?php foreach ($alertes as $alerte): ?>
                            <tr>
                                <td style="font-weight: 600;">#<?php echo $alerte['id']; ?></td>
                                <td>
                                    <?php echo date('d/m/Y', strtotime($alerte['date_creation'])); ?><br>
                                    <small style="color: var(--muted);"><?php echo date('H:i', strtotime($alerte['date_creation'])); ?></small>
                                </td>
                                <td>
                                    <span class="type-badge">
                                        <span class="type-color" style="background: <?php echo $alerte['couleur']; ?>"></span>
                                        <?php echo htmlspecialchars($alerte['type_alerte']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $alerte['statut']; ?>">
                                        <?php echo $statuts[$alerte['statut']] ?? $alerte['statut']; ?>
                                    </span>
                                    <?php if ($alerte['date_maj']): ?>
                                    <br><small style="color: var(--muted);"><?php echo date('d/m/Y', strtotime($alerte['date_maj'])); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($alerte['niveau_gravite']): ?>
                                    <span class="gravite-badge gravite-<?php echo min($alerte['niveau_gravite'], 5); ?>">
                                        <?php echo $alerte['niveau_gravite']; ?>
                                    </span>
                                    <?php else: ?>
                                    <span style="color: var(--muted);">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $canal_labels = [
                                        'web' => '🌐 Web',
                                        'mobile' => '📱 Mobile',
                                       
                                    ];
                                    echo $canal_labels[$alerte['canal_soumission']] ?? $alerte['canal_soumission'];
                                    ?>
                                </td>
                                <td>
                                    <?php if ($alerte['pieces_jointes'] > 0): ?>
                                    <span class="files-badge">
                                        📎 <?php echo $alerte['pieces_jointes']; ?>
                                    </span>
                                    <?php else: ?>
                                    <span style="color: var(--muted);">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="details.php?id=<?php echo $alerte['id']; ?>" class="action-btn view">
                                            Voir
                                        </a>
                                        <a href="update_statut.php?id=<?php echo $alerte['id']; ?>" class="action-btn update">
                                            Modifier
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="page-btn">
                        ← Précédent
                    </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                       class="page-btn <?php echo $i == $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="page-btn">
                        Suivant →
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script>
        function resetFilters() {
            window.location.href = 'alerts.php';
        }
        
        // Auto-submit on filter change
        document.getElementById('filterForm').addEventListener('change', function() {
            this.submit();
        });
        
        // Set max date for date inputs to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('date_debut').max = today;
        document.getElementById('date_fin').max = today;
        
        // Ensure date_debut <= date_fin
        document.getElementById('date_debut').addEventListener('change', function() {
            document.getElementById('date_fin').min = this.value;
        });
        
        document.getElementById('date_fin').addEventListener('change', function() {
            document.getElementById('date_debut').max = this.value;
        });
    </script>
</body>
</html>