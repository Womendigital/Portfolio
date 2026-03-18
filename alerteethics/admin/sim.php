<?php
require_once 'protect.php';
require_once __DIR__ . '/../config/database.php';

// Données simulées pour le développement
$stats = [];

// Simuler des données aléatoires mais cohérentes
srand(12345); // Seed fixe pour des données prévisibles

// Total des alertes
$stats['total_alertes'] = 247;

// Alertes par statut (distribution réaliste)
$statuts = [
    ['statut' => 'nouveau', 'count' => 42],
    ['statut' => 'en_cours', 'count' => 68],
    ['statut' => 'traite', 'count' => 95],
    ['statut' => 'cloture', 'count' => 32],
    ['statut' => 'rejete', 'count' => 10]
];
$stats['par_statut'] = $statuts;

// Types d'alerte (top 5)
$types_alerte = [
    ['nom' => 'Corruption', 'count' => 78],
    ['nom' => 'Harcèlement moral', 'count' => 56],
    ['nom' => 'Fraude', 'count' => 45],
    ['nom' => 'Discrimination', 'count' => 32],
    ['nom' => 'Conflit d\'intérêts', 'count' => 28]
];
$stats['par_type'] = $types_alerte;

// Évolution sur 12 mois (données réalistes)
$mois = [];
$dates = [];
$base_date = strtotime('-11 months');
for ($i = 0; $i < 12; $i++) {
    $date = date('Y-m', strtotime("+$i months", $base_date));
    $dates[] = $date;
    // Simuler une tendance à la hausse
    $count = rand(15, 35) + ($i * 2);
    $mois[] = [
        'mois' => $date,
        'count' => $count
    ];
}
$stats['par_mois'] = $mois;

// Dernières alertes
$noms_types = ['Corruption', 'Harcèlement moral', 'Fraude', 'Discrimination', 'Conflit d\'intérêts', 'Mauvaise gestion', 'Harcèlement sexuel'];
$dernieres_alertes = [];
$start_date = strtotime('-30 days');
for ($i = 1; $i <= 8; $i++) {
    $date = date('Y-m-d H:i:s', $start_date + ($i * rand(6, 72) * 3600));
    $statut = ['nouveau', 'en_cours', 'traite', 'cloture', 'rejete'][rand(0, 4)];
    $type = $noms_types[rand(0, count($noms_types) - 1)];
    $gravite = rand(1, 5);
    
    $dernieres_alertes[] = [
        'id' => 1000 + $i,
        'date_creation' => $date,
        'statut' => $statut,
        'niveau_gravite' => $gravite,
        'type_alerte' => $type
    ];
}
$stats['dernieres_alertes'] = $dernieres_alertes;

// Autres statistiques
$stats['temps_traitement_moyen'] = 48.5; // heures
$stats['gravite_moyenne'] = 3.2; // sur 5
$stats['aujourdhui'] = 7; // alertes aujourd'hui
$stats['cette_semaine'] = 42; // alertes cette semaine

// Calculer le taux de traitement
$total_traitees = 0;
foreach ($stats['par_statut'] as $s) {
    if (in_array($s['statut'], ['traite', 'cloture'])) {
        $total_traitees += $s['count'];
    }
}
$stats['taux_traitement'] = round(($total_traitees / $stats['total_alertes']) * 100, 1);

// Données de session simulées (si non définies)
if (!isset($_SESSION['admin_fullname'])) {
    $_SESSION['admin_fullname'] = 'Admin Principal';
}
if (!defined('ADMIN_ROLE')) {
    define('ADMIN_ROLE', 'Super Admin');
}
if (!defined('ADMIN_USERNAME')) {
    define('ADMIN_USERNAME', 'admin');
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - Administration Alerte Sénégal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <style>
        :root {
            --primary: #441c8a;
            --primary-light: #6d28d9;
            --secondary: #1a2332;
            --dark: #0f1724;
            --light: #ffffff;
            --gray: #94a3b8;
            --gray-dark: #475569;
            --success: #059669;
            --warning: #d97706;
            --danger: #dc2626;
            --info: #3b82f6;
            --border: #2d3748;
            --card-bg: #1a2332;
            --sidebar-bg: #0f1724;
        }
        
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
            font-family: 'Inter', sans-serif;
        }
        
        body {
            background: var(--dark);
            color: var(--light);
            font-size: 14px;
            line-height: 1.5;
            height: 100vh;
            overflow: hidden;
        }
        
        /* Layout */
        .dashboard-container {
            display: grid;
            grid-template-columns: 260px 1fr;
            grid-template-rows: 70px 1fr;
            grid-template-areas: 
                "sidebar header"
                "sidebar main";
            height: 100vh;
        }
        
        /* Header */
        .header {
            grid-area: header;
            background: var(--card-bg);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            position: relative;
            z-index: 10;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--light);
        }
        
        .header-subtitle {
            font-size: 13px;
            color: var(--gray);
            margin-top: 2px;
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        /* User Profile */
        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 15px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            border: 1px solid var(--border);
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .user-profile:hover {
            background: rgba(255, 255, 255, 0.08);
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }
        
        .user-info h4 {
            font-size: 14px;
            font-weight: 600;
            color: var(--light);
        }
        
        .user-info p {
            font-size: 12px;
            color: var(--gray);
        }
        
        /* Logout Button */
        .logout-btn {
            background: rgba(220, 38, 38, 0.1);
            color: #fca5a5;
            border: 1px solid rgba(220, 38, 38, 0.2);
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .logout-btn:hover {
            background: rgba(220, 38, 38, 0.2);
        }
        
        /* Sidebar */
        .sidebar {
            grid-area: sidebar;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--border);
            padding: 30px 0;
            overflow-y: auto;
        }
        
        .logo {
            padding: 0 25px 30px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 25px;
        }
        
        .logo img {
            height: 45px;
            filter: brightness(0) invert(1);
        }
        
        .logo-text {
            margin-top: 10px;
        }
        
        .logo-text h2 {
            font-size: 18px;
            font-weight: 700;
            color: var(--light);
        }
        
        .logo-text p {
            font-size: 12px;
            color: var(--gray);
            margin-top: 2px;
        }
        
        .nav-section {
            padding: 0 20px;
            margin-bottom: 25px;
        }
        
        .nav-title {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--gray);
            margin-bottom: 15px;
            padding-left: 5px;
        }
        
        .nav-menu {
            list-style: none;
        }
        
        .nav-item {
            margin-bottom: 5px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            color: var(--gray);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .nav-link:hover {
            background: rgba(255, 255, 255, 0.05);
            color: var(--light);
        }
        
        .nav-link.active {
            background: rgba(68, 28, 138, 0.15);
            color: var(--light);
            border-left-color: var(--primary);
        }
        
        .nav-icon {
            width: 20px;
            height: 20px;
            stroke: currentColor;
            stroke-width: 1.5;
            fill: none;
        }
        
        .nav-text {
            font-size: 14px;
            font-weight: 500;
        }
        
        /* Main Content */
        .main-content {
            grid-area: main;
            padding: 30px;
            overflow-y: auto;
            background: #0d1422;
        }
        
        /* Page Header */
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--light);
            margin-bottom: 8px;
        }
        
        .page-header p {
            color: var(--gray);
            font-size: 15px;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 25px;
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        
        .stat-title {
            font-size: 14px;
            color: var(--gray);
            font-weight: 500;
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(68, 28, 138, 0.15);
            color: var(--primary-light);
        }
        
        .stat-icon svg {
            width: 24px;
            height: 24px;
        }
        
        .stat-content {
            display: flex;
            align-items: baseline;
            gap: 10px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--light);
            line-height: 1;
        }
        
        .stat-change {
            font-size: 13px;
            font-weight: 500;
            padding: 4px 8px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .stat-change.positive {
            background: rgba(5, 150, 105, 0.15);
            color: var(--success);
        }
        
        .stat-change.negative {
            background: rgba(220, 38, 38, 0.15);
            color: var(--danger);
        }
        
        .stat-subtext {
            font-size: 13px;
            color: var(--gray);
            margin-top: 8px;
        }
        
        /* Charts Section */
        .charts-section {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .chart-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 25px;
            border: 1px solid var(--border);
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .chart-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--light);
        }
        
        .chart-actions {
            display: flex;
            gap: 10px;
        }
        
        .chart-btn {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border);
            color: var(--gray);
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .chart-btn:hover {
            background: rgba(255, 255, 255, 0.08);
            color: var(--light);
        }
        
        .chart-btn.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }
        
        .chart-container {
            height: 320px;
            position: relative;
        }
        
        /* Recent Alerts */
        .recent-alerts {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 25px;
            border: 1px solid var(--border);
        }
        
        .alerts-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .alerts-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--light);
        }
        
        .alerts-link {
            color: var(--primary-light);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
        }
        
        .alerts-link:hover {
            gap: 10px;
        }
        
        .alerts-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .alerts-table thead {
            border-bottom: 1px solid var(--border);
        }
        
        .alerts-table th {
            padding: 12px 15px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .alerts-table tbody tr {
            border-bottom: 1px solid var(--border);
            transition: all 0.3s;
        }
        
        .alerts-table tbody tr:hover {
            background: rgba(255, 255, 255, 0.03);
        }
        
        .alerts-table tbody tr:last-child {
            border-bottom: none;
        }
        
        .alerts-table td {
            padding: 15px;
            font-size: 14px;
        }
        
        .alert-id {
            font-weight: 600;
            color: var(--light);
        }
        
        .alert-date {
            color: var(--gray);
            font-size: 13px;
        }
        
        .alert-type {
            display: inline-block;
            padding: 4px 12px;
            background: rgba(68, 28, 138, 0.15);
            color: var(--primary-light);
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .alert-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }
        
        .status-nouveau .status-dot { background: #3b82f6; }
        .status-en_cours .status-dot { background: #f59e0b; }
        .status-traite .status-dot { background: #10b981; }
        .status-cloture .status-dot { background: #6b7280; }
        .status-rejete .status-dot { background: #ef4444; }
        
        .status-nouveau { background: rgba(59, 130, 246, 0.15); color: #93c5fd; }
        .status-en_cours { background: rgba(245, 158, 11, 0.15); color: #fcd34d; }
        .status-traite { background: rgba(16, 185, 129, 0.15); color: #34d399; }
        .status-cloture { background: rgba(107, 114, 128, 0.15); color: #9ca3af; }
        .status-rejete { background: rgba(239, 68, 68, 0.15); color: #fca5a5; }
        
        .alert-gravity {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .gravity-level {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
        }
        
        .gravity-1 { background: rgba(34, 197, 94, 0.2); color: #4ade80; }
        .gravity-2 { background: rgba(234, 179, 8, 0.2); color: #facc15; }
        .gravity-3 { background: rgba(249, 115, 22, 0.2); color: #fb923c; }
        .gravity-4 { background: rgba(239, 68, 68, 0.2); color: #f87171; }
        .gravity-5 { background: rgba(185, 28, 28, 0.2); color: #dc2626; }
        
        .alert-action {
            color: var(--primary-light);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .alert-action:hover {
            color: var(--primary);
        }
        
        /* Notification */
        .dev-notification {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.2);
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 13px;
            color: #93c5fd;
        }
        
        .dev-notification .icon {
            font-size: 16px;
        }
        
        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 3px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--gray-dark);
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Header -->
        <header class="header">
            <div class="header-left">
                <div>
                    <h1 class="header-title">Tableau de bord</h1>
                    <p class="header-subtitle">Vue d'ensemble des signalements - Données de démonstration</p>
                </div>
            </div>
            
            <div class="header-right">
                <a href="logout.php" class="logout-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    Déconnexion
                </a>
                
                <div class="user-profile">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr(ADMIN_USERNAME, 0, 1)); ?>
                    </div>
                    <div class="user-info">
                        <h4><?php echo htmlspecialchars($_SESSION['admin_fullname']); ?></h4>
                        <p><?php echo htmlspecialchars(ADMIN_ROLE); ?></p>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <img src="../image/logoofficielle.png" alt="Alerte Sénégal">
                <div class="logo-text">
                    <h2>Alerte Sénégal</h2>
                    <p>Administration</p>
                </div>
            </div>
            
            <div class="nav-section">
                <h3 class="nav-title">Navigation</h3>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="index.php" class="nav-link active">
                            <svg class="nav-icon" viewBox="0 0 24 24">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                            <span class="nav-text">Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="alerts.php" class="nav-link">
                            <svg class="nav-icon" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            <span class="nav-text">Alertes</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="stats.php" class="nav-link">
                            <svg class="nav-icon" viewBox="0 0 24 24">
                                <path d="M18 20V10"></path>
                                <path d="M12 20V4"></path>
                                <path d="M6 20v-6"></path>
                            </svg>
                            <span class="nav-text">Statistiques</span>
                        </a>
                    </li>
                </ul>
            </div>
            
            <div class="nav-section">
                <h3 class="nav-title">Administration</h3>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="settings.php" class="nav-link">
                            <svg class="nav-icon" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="3"></circle>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                            </svg>
                            <span class="nav-text">Paramètres</span>
                        </a>
                    </li>
                </ul>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
           
            
            <div class="page-header">
                <h1>Vue d'ensemble</h1>
                <p>Analyse complète des signalements et performance du système</p>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <h3 class="stat-title">Total des alertes</h3>
                        <div class="stat-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['total_alertes']; ?></div>
                        <span class="stat-change positive">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                                <polyline points="17 6 23 6 23 12"></polyline>
                            </svg>
                            +<?php echo $stats['cette_semaine']; ?> cette semaine
                        </span>
                    </div>
                    <p class="stat-subtext"><?php echo $stats['aujourdhui']; ?> nouvelles alertes aujourd'hui</p>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <h3 class="stat-title">Alertes traitées</h3>
                        <div class="stat-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['taux_traitement']; ?>%</div>
                        <span class="stat-change positive">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                                <polyline points="17 6 23 6 23 12"></polyline>
                            </svg>
                            +2.5%
                        </span>
                    </div>
                    <p class="stat-subtext">Taux de traitement global</p>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <h3 class="stat-title">Temps moyen</h3>
                        <div class="stat-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['temps_traitement_moyen']; ?>h</div>
                        <span class="stat-change negative">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="23 18 13.5 8.5 8.5 13.5 1 6"></polyline>
                                <polyline points="17 18 23 18 23 12"></polyline>
                            </svg>
                            -1.2h
                        </span>
                    </div>
                    <p class="stat-subtext">Temps moyen de traitement</p>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <h3 class="stat-title">Gravité moyenne</h3>
                        <div class="stat-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                <line x1="12" y1="9" x2="12" y2="13"></line>
                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['gravite_moyenne']; ?>/5</div>
                        <span class="stat-change positive">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                                <polyline points="17 6 23 6 23 12"></polyline>
                            </svg>
                            +0.3
                        </span>
                    </div>
                    <p class="stat-subtext">Niveau de gravité moyen</p>
                </div>
            </div>
            
            <!-- Charts Section -->
            <div class="charts-section">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">Évolution des alertes</h3>
                        <div class="chart-actions">
                            <button class="chart-btn active">12 mois</button>
                            <button class="chart-btn">6 mois</button>
                            <button class="chart-btn">30 jours</button>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="evolutionChart"></canvas>
                    </div>
                </div>
                
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">Répartition par type</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="typeChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Recent Alerts -->
            <div class="recent-alerts">
                <div class="alerts-header">
                    <h3 class="alerts-title">Alertes récentes</h3>
                    <a href="alerts.php" class="alerts-link">
                        Voir toutes
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </a>
                </div>
                
                <table class="alerts-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Statut</th>
                            <th>Gravité</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['dernieres_alertes'] as $alerte): ?>
                        <tr>
                            <td class="alert-id">#<?php echo $alerte['id']; ?></td>
                            <td class="alert-date"><?php echo date('d/m/Y H:i', strtotime($alerte['date_creation'])); ?></td>
                            <td><span class="alert-type"><?php echo htmlspecialchars($alerte['type_alerte'] ?? 'Non spécifié'); ?></span></td>
                            <td>
                                <span class="alert-status status-<?php echo $alerte['statut']; ?>">
                                    <span class="status-dot"></span>
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
                            </td>
                            <td>
                                <?php if ($alerte['niveau_gravite']): ?>
                                <div class="alert-gravity">
                                    <span class="gravity-level gravity-<?php echo min($alerte['niveau_gravite'], 5); ?>">
                                        <?php echo $alerte['niveau_gravite']; ?>
                                    </span>
                                </div>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="details.php?id=<?php echo $alerte['id']; ?>" class="alert-action">Voir</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <script>
        // Évolution Chart
        const evolutionCtx = document.getElementById('evolutionChart').getContext('2d');
        const evolutionLabels = <?php echo json_encode(array_map(function($m) {
            return date('M Y', strtotime($m['mois'] . '-01'));
        }, $stats['par_mois'])); ?>;
        const evolutionData = <?php echo json_encode(array_column($stats['par_mois'], 'count')); ?>;
        
        new Chart(evolutionCtx, {
            type: 'line',
            data: {
                labels: evolutionLabels,
                datasets: [{
                    label: 'Nombre d\'alertes',
                    data: evolutionData,
                    borderColor: '#6d28d9',
                    backgroundColor: 'rgba(109, 40, 217, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#6d28d9',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(26, 35, 50, 0.95)',
                        titleColor: '#ffffff',
                        bodyColor: '#94a3b8',
                        borderColor: '#2d3748',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 6
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#2d3748'
                        },
                        ticks: {
                            color: '#94a3b8',
                            font: {
                                size: 12
                            }
                        },
                        border: {
                            color: '#2d3748'
                        }
                    },
                    x: {
                        grid: {
                            color: '#2d3748'
                        },
                        ticks: {
                            color: '#94a3b8',
                            font: {
                                size: 12
                            }
                        },
                        border: {
                            color: '#2d3748'
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
        
        // Type Chart
        const typeCtx = document.getElementById('typeChart').getContext('2d');
        const typeLabels = <?php echo json_encode(array_column($stats['par_type'], 'nom')); ?>;
        const typeData = <?php echo json_encode(array_column($stats['par_type'], 'count')); ?>;
        const typeColors = ['#6d28d9', '#3b82f6', '#10b981', '#f59e0b', '#ef4444'];
        
        new Chart(typeCtx, {
            type: 'doughnut',
            data: {
                labels: typeLabels,
                datasets: [{
                    data: typeData,
                    backgroundColor: typeColors,
                    borderWidth: 0,
                    borderRadius: 6,
                    spacing: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            color: '#94a3b8',
                            padding: 15,
                            font: {
                                size: 12
                            },
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(26, 35, 50, 0.95)',
                        titleColor: '#ffffff',
                        bodyColor: '#94a3b8',
                        borderColor: '#2d3748',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 6,
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
        
        // Chart period buttons
        document.querySelectorAll('.chart-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.querySelectorAll('.chart-btn').forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                // Simulation du changement de période
                const period = this.textContent.trim();
                const notification = document.querySelector('.dev-notification');
                const originalText = notification.querySelector('span:not(.icon)').textContent;
                notification.querySelector('span:not(.icon)').textContent = 
                    `Mode démonstration : Changement de période à ${period} (données simulées)`;
                
                setTimeout(() => {
                    notification.querySelector('span:not(.icon)').textContent = originalText;
                }, 2000);
            });
        });
        
        // Update user avatar with first letter
        document.querySelector('.user-avatar').textContent = '<?php echo strtoupper(substr(ADMIN_USERNAME, 0, 1)); ?>';
        
        // Simuler des mises à jour en temps réel (démonstration)
        setTimeout(() => {
            const todayCount = document.querySelector('.stat-card:nth-child(1) .stat-subtext');
            const currentCount = parseInt(todayCount.textContent.match(/\d+/)[0]);
            const newCount = currentCount + Math.floor(Math.random() * 3);
            todayCount.textContent = `${newCount} nouvelles alertes aujourd'hui`;
            
            // Notification discrète
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: rgba(16, 185, 129, 0.9);
                color: white;
                padding: 10px 16px;
                border-radius: 8px;
                font-size: 13px;
                z-index: 1000;
                animation: slideIn 0.3s ease-out;
            `;
            notification.textContent = `Nouvelle alerte reçue - Total: ${newCount} aujourd'hui`;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-out forwards';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }, 8000);
        
        // Ajouter les animations CSS
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>