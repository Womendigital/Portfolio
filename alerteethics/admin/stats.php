<?php
require_once 'protect.php';
require_once __DIR__ . '/../config/database.php';

// Fonction pour générer des données de démonstration
function generateDemoStats() {
    // Données de démonstration cohérentes et réalistes
    $data = [];
    
    // Types d'alerte avec données simulées
    $types = [
        ['id' => 1, 'nom' => 'Corruption', 'couleur' => '#e74c3c', 'total' => 78],
        ['id' => 2, 'nom' => 'Harcèlement moral', 'couleur' => '#9b59b6', 'total' => 56],
        ['id' => 3, 'nom' => 'Harcèlement sexuel', 'couleur' => '#e67e22', 'total' => 45],
        ['id' => 4, 'nom' => 'Fraude', 'couleur' => '#f1c40f', 'total' => 42],
        ['id' => 5, 'nom' => 'Discrimination', 'couleur' => '#34495e', 'total' => 38],
        ['id' => 6, 'nom' => 'Conflit d\'intérêts', 'couleur' => '#1abc9c', 'total' => 35],
        ['id' => 7, 'nom' => 'Mauvaise gestion', 'couleur' => '#3498db', 'total' => 32],
        ['id' => 8, 'nom' => 'Atteinte environnementale', 'couleur' => '#27ae60', 'total' => 28],
        ['id' => 9, 'nom' => 'Autre', 'couleur' => '#95a5a6', 'total' => 25]
    ];
    
    // Ajouter des métriques aux types
    foreach ($types as &$type) {
        $type['gravite_moyenne'] = rand(25, 45) / 10; // 2.5 à 4.5
        $type['nouveaux'] = rand(5, 15);
        $type['traites'] = $type['total'] - $type['nouveaux'];
    }
    $data['stats_type'] = $types;
    
    // Évolution sur 12 mois
    $evolution = [];
    $currentMonth = date('n');
    $currentYear = date('Y');
    
    for ($i = 11; $i >= 0; $i--) {
        $month = $currentMonth - $i;
        $year = $currentYear;
        
        if ($month < 1) {
            $month += 12;
            $year--;
        }
        
        $monthName = date('M', mktime(0, 0, 0, $month, 1, $year));
        $count = rand(15, 45) + ($i * 2); // Tendance à la hausse
        $gravite = rand(28, 38) / 10;
        $traites = round($count * (rand(60, 85) / 100));
        
        $evolution[] = [
            'mois' => $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT),
            'total' => $count,
            'gravite_moyenne' => $gravite,
            'traites' => $traites
        ];
    }
    $data['stats_evolution'] = $evolution;
    
    // Statistiques par statut
    $statuts = [
        ['statut' => 'nouveau', 'total' => 42, 'gravite_moyenne' => 3.2, 'temps_traitement' => null],
        ['statut' => 'en_cours', 'total' => 68, 'gravite_moyenne' => 3.4, 'temps_traitement' => 24.5],
        ['statut' => 'traite', 'total' => 158, 'gravite_moyenne' => 3.6, 'temps_traitement' => 72.3],
        ['statut' => 'cloture', 'total' => 32, 'gravite_moyenne' => 3.1, 'temps_traitement' => 168.5],
        ['statut' => 'rejete', 'total' => 18, 'gravite_moyenne' => 2.8, 'temps_traitement' => 48.2]
    ];
    $data['stats_statut'] = $statuts;
    
    // Délais de traitement
    $delais = [
        ['delai' => '≤ 24h', 'total' => 65, 'moyenne_heures' => 18.4],
        ['delai' => '24h-72h', 'total' => 98, 'moyenne_heures' => 45.2],
        ['delai' => '3-7 jours', 'total' => 42, 'moyenne_heures' => 120.7],
        ['delai' => '> 7 jours', 'total' => 15, 'moyenne_heures' => 240.3]
    ];
    $data['stats_delai'] = $delais;
    
    // Top 10 alertes critiques
    $top_gravite = [];
    for ($i = 1; $i <= 10; $i++) {
        $date = date('Y-m-d H:i:s', strtotime('-' . rand(1, 90) . ' days'));
        $gravite = rand(4, 5);
        $type = $types[array_rand($types)];
        $statut = ['nouveau', 'en_cours', 'traite'][array_rand(['nouveau', 'en_cours', 'traite'])];
        
        $top_gravite[] = [
            'id' => 1000 + $i,
            'date_creation' => $date,
            'niveau_gravite' => $gravite,
            'type_alerte' => $type['nom'],
            'couleur' => $type['couleur'],
            'statut' => $statut
        ];
    }
    // Trier par gravité
    usort($top_gravite, function($a, $b) {
        return $b['niveau_gravite'] <=> $a['niveau_gravite'];
    });
    $data['top_gravite'] = $top_gravite;
    
    // Statistiques résumées
    $total = array_sum(array_column($types, 'total'));
    $gravite_moyenne = array_sum(array_column($types, 'gravite_moyenne')) / count($types);
    $temps_moyen = ($delais[0]['moyenne_heures'] * $delais[0]['total'] + 
                   $delais[1]['moyenne_heures'] * $delais[1]['total'] + 
                   $delais[2]['moyenne_heures'] * $delais[2]['total'] + 
                   $delais[3]['moyenne_heures'] * $delais[3]['total']) / 
                   ($delais[0]['total'] + $delais[1]['total'] + $delais[2]['total'] + $delais[3]['total']);
    
    $data['stats_resume'] = [
        'total_alertes' => $total,
        'gravite_moyenne' => round($gravite_moyenne, 1),
        'temps_moyen_heures' => round($temps_moyen, 1),
        'premiere_alerte' => date('Y-m-d', strtotime('-6 months')),
        'derniere_alerte' => date('Y-m-d H:i:s')
    ];
    
    return $data;
}

// Générer les données de démonstration
$stats = generateDemoStats();
extract($stats); // Crée les variables à partir du tableau

// Données de session pour l'affichage
if (!isset($_SESSION['admin_fullname'])) {
    $_SESSION['admin_fullname'] = 'Administrateur Principal';
}
if (!defined('ADMIN_ROLE')) {
    define('ADMIN_ROLE', 'Super Admin');
}
if (!defined('ADMIN_USERNAME')) {
    define('ADMIN_USERNAME', 'admin');
}

// Fonction utilitaire pour convertir hex en rgb
function hex2rgb($hex, $alpha = 1) {
    $hex = str_replace("#", "", $hex);
    
    if(strlen($hex) == 3) {
        $r = hexdec(substr($hex,0,1).substr($hex,0,1));
        $g = hexdec(substr($hex,1,1).substr($hex,1,1));
        $b = hexdec(substr($hex,2,1).substr($hex,2,1));
    } else {
        $r = hexdec(substr($hex,0,2));
        $g = hexdec(substr($hex,2,2));
        $b = hexdec(substr($hex,4,2));
    }
    
    return "rgba($r, $g, $b, $alpha)";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Statistiques Avancées - Dashboard Alerte Sénégal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }
        
        body {
            background: #0b0f19;
            color: #ffffff;
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        /* Layout fixe */
        .container {
            display: grid;
            grid-template-columns: 280px 1fr;
            grid-template-rows: 80px 1fr;
            grid-template-areas: 
                "sidebar header"
                "sidebar main";
            height: 100vh;
            width: 100vw;
            min-width: 1400px;
        }
        
        /* Header */
        .header {
            grid-area: header;
            background: linear-gradient(90deg, #0b0f19 0%, #141a2a 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
            backdrop-filter: blur(20px);
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .page-title h1 {
            font-size: 24px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 4px;
        }
        
        .page-title p {
            color: #8a94a6;
            font-size: 13px;
            letter-spacing: 0.5px;
        }
        
        .period-selector {
            display: flex;
            gap: 8px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 6px;
            border-radius: 12px;
        }
        
        .period-btn {
            padding: 10px 20px;
            background: transparent;
            border: none;
            color: #8a94a6;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .period-btn:hover {
            background: rgba(255, 255, 255, 0.05);
            color: #ffffff;
        }
        
        .period-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 24px;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 18px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
        }
        
        .user-avatar {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 18px;
        }
        
        .user-info h4 {
            font-size: 14px;
            font-weight: 600;
            color: white;
        }
        
        .user-info p {
            font-size: 12px;
            color: #8a94a6;
            margin-top: 2px;
        }
        
        .logout-btn {
            background: rgba(239, 68, 68, 0.1);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.2);
            padding: 12px 20px;
            border-radius: 12px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .logout-btn:hover {
            background: rgba(239, 68, 68, 0.2);
        }
        
        /* Sidebar */
        .sidebar {
            grid-area: sidebar;
            background: linear-gradient(180deg, #0b0f19 0%, #111827 100%);
            border-right: 1px solid rgba(255, 255, 255, 0.08);
            padding: 40px 0;
            position: relative;
            overflow-y: auto;
        }
        
        .sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 1px;
            height: 100%;
            background: linear-gradient(to bottom, transparent, rgba(102, 126, 234, 0.3), transparent);
        }
        
        .logo {
            padding: 0 30px 40px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            margin-bottom: 30px;
        }
        
        .logo img {
            height: 50px;
            filter: brightness(0) invert(1);
        }
        
        .logo-text {
            margin-top: 15px;
        }
        
        .logo-text h2 {
            font-size: 20px;
            font-weight: 700;
            color: white;
            letter-spacing: 0.5px;
        }
        
        .logo-text p {
            font-size: 12px;
            color: #8a94a6;
            margin-top: 4px;
        }
        
        .nav-section {
            padding: 0 20px;
            margin-bottom: 30px;
        }
        
        .nav-title {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #8a94a6;
            margin-bottom: 16px;
            padding-left: 10px;
        }
        
        .nav-menu {
            list-style: none;
        }
        
        .nav-item {
            margin-bottom: 6px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px 20px;
            color: #8a94a6;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .nav-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 3px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .nav-link:hover {
            background: rgba(255, 255, 255, 0.05);
            color: #ffffff;
            padding-left: 24px;
        }
        
        .nav-link:hover::before {
            opacity: 1;
        }
        
        .nav-link.active {
            background: rgba(102, 126, 234, 0.1);
            color: #ffffff;
            padding-left: 24px;
        }
        
        .nav-link.active::before {
            opacity: 1;
        }
        
        .nav-icon {
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        
        .nav-text {
            font-size: 15px;
            font-weight: 500;
            letter-spacing: 0.3px;
        }
        
        /* Main Content */
        .main-content {
            grid-area: main;
            padding: 40px;
            overflow-y: auto;
            background: #0b0f19;
        }
        
        /* Stats Summary */
        .stats-summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.8) 0%, rgba(15, 23, 42, 0.8) 100%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 28px;
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }
        
        .stat-card:hover {
            transform: translateY(-8px);
            border-color: rgba(102, 126, 234, 0.3);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
        }
        
        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
            font-size: 24px;
        }
        
        .stat-trend {
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            backdrop-filter: blur(10px);
        }
        
        .trend-up {
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        
        .trend-down {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        
        .stat-value {
            font-size: 40px;
            font-weight: 800;
            color: white;
            margin-bottom: 8px;
            line-height: 1;
        }
        
        .stat-label {
            color: #8a94a6;
            font-size: 14px;
            font-weight: 500;
        }
        
        /* Charts Grid */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            margin-bottom: 40px;
        }
        
        .chart-container {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.8) 0%, rgba(15, 23, 42, 0.8) 100%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 28px;
            backdrop-filter: blur(10px);
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
        }
        
        .chart-title h3 {
            font-size: 18px;
            font-weight: 700;
            color: white;
            margin-bottom: 6px;
        }
        
        .chart-title p {
            color: #8a94a6;
            font-size: 13px;
        }
        
        .chart-actions {
            display: flex;
            gap: 8px;
        }
        
        .chart-btn {
            padding: 10px 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: #8a94a6;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .chart-btn:hover {
            background: rgba(255, 255, 255, 0.08);
            color: white;
        }
        
        .chart-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
        }
        
        .chart-wrapper {
            height: 320px;
            position: relative;
        }
        
        /* Tables Grid */
        .tables-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            margin-bottom: 40px;
        }
        
        .table-container {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.8) 0%, rgba(15, 23, 42, 0.8) 100%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 28px;
            backdrop-filter: blur(10px);
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        
        .table-header h3 {
            font-size: 18px;
            font-weight: 700;
            color: white;
        }
        
        .table-actions {
            display: flex;
            gap: 8px;
        }
        
        .table-btn {
            padding: 10px 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: #8a94a6;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .table-btn:hover {
            background: rgba(255, 255, 255, 0.08);
            color: white;
        }
        
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        thead {
            background: rgba(255, 255, 255, 0.03);
        }
        
        th {
            padding: 18px 20px;
            text-align: left;
            color: #8a94a6;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }
        
        td {
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 14px;
        }
        
        tbody tr {
            transition: all 0.3s;
        }
        
        tbody tr:hover {
            background: rgba(255, 255, 255, 0.03);
        }
        
        tbody tr:last-child td {
            border-bottom: none;
        }
        
        .type-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .type-color {
            width: 16px;
            height: 16px;
            border-radius: 50%;
        }
        
        .badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        
        .badge-success {
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        
        .badge-warning {
            background: rgba(245, 158, 11, 0.15);
            color: #f59e0b;
            border: 1px solid rgba(245, 158, 11, 0.2);
        }
        
        .badge-danger {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        
        .badge-info {
            background: rgba(14, 165, 233, 0.15);
            color: #0ea5e9;
            border: 1px solid rgba(14, 165, 233, 0.2);
        }
        
        .badge-muted {
            background: rgba(148, 163, 184, 0.15);
            color: #94a3b8;
            border: 1px solid rgba(148, 163, 184, 0.2);
        }
        
        .gravite-indicator {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
        }
        
        .text-center {
            text-align: center;
        }
        
        .font-bold {
            font-weight: 700;
        }
        
        /* Footer */
        .footer {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            text-align: center;
            color: #8a94a6;
            font-size: 13px;
        }
        
        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <div class="header-left">
                <div class="page-title">
                    <h1>Tableau de bord statistique</h1>
                    <p>Analyse complète des signalements - Données de démonstration</p>
                </div>
                
                <div class="period-selector">
                    <button class="period-btn active">7 jours</button>
                    <button class="period-btn">30 jours</button>
                    <button class="period-btn">3 mois</button>
                    <button class="period-btn">6 mois</button>
                    <button class="period-btn">1 an</button>
                    <button class="period-btn">Tout</button>
                </div>
            </div>
            
            <div class="header-right">
                <div class="user-profile">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr(ADMIN_USERNAME, 0, 2)); ?>
                    </div>
                    <div class="user-info">
                        <h4><?php echo htmlspecialchars($_SESSION['admin_fullname']); ?></h4>
                        <p><?php echo htmlspecialchars(ADMIN_ROLE); ?></p>
                    </div>
                </div>
                
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Déconnexion
                </a>
            </div>
        </header>
        
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <img src="../image/logoofficielle.png" alt="Alerte Sénégal">
                <div class="logo-text">
                    <h2>Alerte Sénégal</h2>
                    <p>Système de signalement éthique</p>
                </div>
            </div>
            
            <div class="nav-section">
                <h3 class="nav-title">Navigation</h3>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="index.php" class="nav-link">
                            <div class="nav-icon">
                                <i class="fas fa-home"></i>
                            </div>
                            <span class="nav-text">Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="alerts.php" class="nav-link">
                            <div class="nav-icon">
                                <i class="fas fa-bell"></i>
                            </div>
                            <span class="nav-text">Alertes</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="stats.php" class="nav-link active">
                            <div class="nav-icon">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                            <span class="nav-text">Statistiques</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="settings.php" class="nav-link">
                            <div class="nav-icon">
                                <i class="fas fa-cog"></i>
                            </div>
                            <span class="nav-text">Paramètres</span>
                        </a>
                    </li>
                </ul>
            </div>
            
            <div class="nav-section">
                <h3 class="nav-title">Système</h3>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="users.php" class="nav-link">
                            <div class="nav-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <span class="nav-text">Utilisateurs</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="logs.php" class="nav-link">
                            <div class="nav-icon">
                                <i class="fas fa-history"></i>
                            </div>
                            <span class="nav-text">Logs système</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="reports.php" class="nav-link">
                            <div class="nav-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <span class="nav-text">Rapports</span>
                        </a>
                    </li>
                </ul>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Stats Summary -->
            <div class="stats-summary">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <span class="stat-trend trend-up">
                            <i class="fas fa-arrow-up"></i> 12.5%
                        </span>
                    </div>
                    <div class="stat-value"><?php echo $stats_resume['total_alertes']; ?></div>
                    <div class="stat-label">Alertes totales</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <span class="stat-trend trend-down">
                            <i class="fas fa-arrow-down"></i> 3.2%
                        </span>
                    </div>
                    <div class="stat-value"><?php echo $stats_resume['gravite_moyenne']; ?>/5</div>
                    <div class="stat-label">Gravité moyenne</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <span class="stat-trend trend-up">
                            <i class="fas fa-arrow-up"></i> 8.7%
                        </span>
                    </div>
                    <div class="stat-value"><?php echo $stats_resume['temps_moyen_heures']; ?>h</div>
                    <div class="stat-label">Temps moyen de traitement</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-calendar"></i>
                        </div>
                        <span class="stat-trend trend-up">
                            <i class="fas fa-arrow-up"></i> 5.4%
                        </span>
                    </div>
                    <div class="stat-value">
                        <?php 
                        $diff = round((time() - strtotime($stats_resume['premiere_alerte'])) / (60 * 60 * 24));
                        echo $diff . 'j';
                        ?>
                    </div>
                    <div class="stat-label">Période analysée</div>
                </div>
            </div>
            
            <!-- Charts -->
            <div class="charts-grid">
                <!-- Chart 1: Distribution par type -->
                <div class="chart-container">
                    <div class="chart-header">
                        <div class="chart-title">
                            <h3>Distribution par type d'alerte</h3>
                            <p>Répartition des signalements par catégorie</p>
                        </div>
                        <div class="chart-actions">
                            <button class="chart-btn active">Barres</button>
                            <button class="chart-btn">Camembert</button>
                            <button class="chart-btn">Export</button>
                        </div>
                    </div>
                    <div class="chart-wrapper">
                        <canvas id="typeChart"></canvas>
                    </div>
                </div>
                
                <!-- Chart 2: Évolution temporelle -->
                <div class="chart-container">
                    <div class="chart-header">
                        <div class="chart-title">
                            <h3>Évolution mensuelle</h3>
                            <p>Progression des alertes sur les 12 derniers mois</p>
                        </div>
                        <div class="chart-actions">
                            <button class="chart-btn active">Mensuel</button>
                            <button class="chart-btn">Hebdomadaire</button>
                            <button class="chart-btn">Export</button>
                        </div>
                    </div>
                    <div class="chart-wrapper">
                        <canvas id="evolutionChart"></canvas>
                    </div>
                </div>
                
                <!-- Chart 3: Distribution par statut -->
                <div class="chart-container">
                    <div class="chart-header">
                        <div class="chart-title">
                            <h3>Statut des alertes</h3>
                            <p>Répartition par état de traitement</p>
                        </div>
                        <div class="chart-actions">
                            <button class="chart-btn active">Circulaire</button>
                            <button class="chart-btn">Stacked</button>
                            <button class="chart-btn">Export</button>
                        </div>
                    </div>
                    <div class="chart-wrapper">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
                
                <!-- Chart 4: Temps de traitement -->
                <div class="chart-container">
                    <div class="chart-header">
                        <div class="chart-title">
                            <h3>Délais de traitement</h3>
                            <p>Temps nécessaire pour traiter les alertes</p>
                        </div>
                        <div class="chart-actions">
                            <button class="chart-btn active">Barres</button>
                            <button class="chart-btn">Donut</button>
                            <button class="chart-btn">Export</button>
                        </div>
                    </div>
                    <div class="chart-wrapper">
                        <canvas id="delaiChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Tables -->
            <div class="tables-grid">
                <!-- Table 1: Alertes par type détaillé -->
                <div class="table-container">
                    <div class="table-header">
                        <h3>Alertes par type (détail)</h3>
                        <div class="table-actions">
                            <button class="table-btn"><i class="fas fa-download"></i> CSV</button>
                            <button class="table-btn"><i class="fas fa-print"></i> Print</button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Type d'alerte</th>
                                    <th class="text-center">Total</th>
                                    <th class="text-center">Nouveaux</th>
                                    <th class="text-center">Traités</th>
                                    <th class="text-center">Gravité</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stats_type as $type): ?>
                                <tr>
                                    <td>
                                        <div class="type-cell">
                                            <div class="type-color" style="background: <?php echo $type['couleur']; ?>"></div>
                                            <span class="font-bold"><?php echo htmlspecialchars($type['nom']); ?></span>
                                        </div>
                                    </td>
                                    <td class="text-center font-bold"><?php echo $type['total']; ?></td>
                                    <td class="text-center">
                                        <span class="badge badge-info"><?php echo $type['nouveaux']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-success"><?php echo $type['traites']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <div class="gravite-indicator" style="
                                            background: <?php echo hex2rgb($type['couleur'], 0.1); ?>;
                                            color: <?php echo $type['couleur']; ?>;
                                            border: 1px solid <?php echo hex2rgb($type['couleur'], 0.3); ?>;
                                        ">
                                            <?php echo number_format($type['gravite_moyenne'], 1); ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Table 2: Top 10 alertes critiques -->
                <div class="table-container">
                    <div class="table-header">
                        <h3>Top 10 alertes les plus graves</h3>
                        <div class="table-actions">
                            <button class="table-btn"><i class="fas fa-download"></i> CSV</button>
                            <button class="table-btn"><i class="fas fa-print"></i> Print</button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID Alerte</th>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Statut</th>
                                    <th class="text-center">Gravité</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_gravite as $alerte): ?>
                                <?php 
                                $badge_class = [
                                    'nouveau' => 'badge-info',
                                    'en_cours' => 'badge-warning',
                                    'traite' => 'badge-success',
                                    'cloture' => 'badge-muted',
                                    'rejete' => 'badge-danger'
                                ][$alerte['statut']] ?? 'badge-muted';
                                
                                $badge_text = [
                                    'nouveau' => 'Nouveau',
                                    'en_cours' => 'En cours',
                                    'traite' => 'Traité',
                                    'cloture' => 'Clôturé',
                                    'rejete' => 'Rejeté'
                                ][$alerte['statut']] ?? $alerte['statut'];
                                ?>
                                <tr>
                                    <td class="font-bold">#<?php echo $alerte['id']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($alerte['date_creation'])); ?></td>
                                    <td>
                                        <div class="type-cell">
                                            <div class="type-color" style="background: <?php echo $alerte['couleur']; ?>"></div>
                                            <span><?php echo htmlspecialchars($alerte['type_alerte']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <?php echo $badge_text; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="gravite-indicator" style="
                                            background: <?php echo hex2rgb('#ef4444', 0.1); ?>;
                                            color: #ef4444;
                                            border: 1px solid <?php echo hex2rgb('#ef4444', 0.3); ?>;
                                        ">
                                            <?php echo $alerte['niveau_gravite']; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="footer">
                <p>© <?php echo date('Y'); ?> Alerte Sénégal - Système de signalement éthique</p>
                <p style="margin-top: 8px; color: rgba(138, 148, 166, 0.7);">
                    <i class="fas fa-info-circle"></i>
                    Données de démonstration - Dernière mise à jour: <?php echo date('d/m/Y H:i'); ?>
                </p>
            </div>
        </main>
    </div>
    
    <script>
        // Chart 1: Distribution par type d'alerte
        const typeCtx = document.getElementById('typeChart').getContext('2d');
        const typeLabels = <?php echo json_encode(array_column($stats_type, 'nom')); ?>;
        const typeData = <?php echo json_encode(array_column($stats_type, 'total')); ?>;
        const typeColors = <?php echo json_encode(array_column($stats_type, 'couleur')); ?>;
        
        new Chart(typeCtx, {
            type: 'bar',
            data: {
                labels: typeLabels,
                datasets: [{
                    label: 'Nombre d\'alertes',
                    data: typeData,
                    backgroundColor: typeColors.map(color => hex2rgb(color, 0.8)),
                    borderColor: typeColors.map(color => hex2rgb(color, 1)),
                    borderWidth: 1,
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.05)',
                            drawBorder: false,
                        },
                        ticks: {
                            color: '#8a94a6',
                            font: {
                                family: "'Inter', sans-serif",
                                size: 12
                            },
                            padding: 10
                        },
                        border: {
                            display: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#8a94a6',
                            font: {
                                family: "'Inter', sans-serif",
                                size: 12
                            },
                            maxRotation: 45
                        },
                        border: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(30, 41, 59, 0.95)',
                        titleColor: '#ffffff',
                        bodyColor: '#8a94a6',
                        borderColor: 'rgba(102, 126, 234, 0.3)',
                        borderWidth: 1,
                        cornerRadius: 12,
                        padding: 16,
                        titleFont: {
                            family: "'Inter', sans-serif",
                            size: 13,
                            weight: '600'
                        },
                        bodyFont: {
                            family: "'Inter', sans-serif",
                            size: 13
                        },
                        displayColors: true,
                        callbacks: {
                            label: function(context) {
                                return ` ${context.dataset.label}: ${context.raw}`;
                            }
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index',
                }
            }
        });
        
        // Chart 2: Évolution temporelle
        const evolutionCtx = document.getElementById('evolutionChart').getContext('2d');
        const evolutionLabels = <?php echo json_encode(array_map(function($e) {
            return date('M Y', strtotime($e['mois'] . '-01'));
        }, $stats_evolution)); ?>;
        const evolutionData = <?php echo json_encode(array_column($stats_evolution, 'total')); ?>;
        
        new Chart(evolutionCtx, {
            type: 'line',
            data: {
                labels: evolutionLabels,
                datasets: [{
                    label: 'Alertes totales',
                    data: evolutionData,
                    borderColor: '#667eea',
                    backgroundColor: hex2rgb('#667eea', 0.1),
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#667eea',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    pointHoverBorderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.05)',
                            drawBorder: false,
                        },
                        ticks: {
                            color: '#8a94a6',
                            font: {
                                family: "'Inter', sans-serif",
                                size: 12
                            },
                            padding: 10
                        },
                        border: {
                            display: false
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.05)',
                            drawBorder: false,
                        },
                        ticks: {
                            color: '#8a94a6',
                            font: {
                                family: "'Inter', sans-serif",
                                size: 12
                            },
                            padding: 10
                        },
                        border: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        labels: {
                            color: '#8a94a6',
                            font: {
                                family: "'Inter', sans-serif",
                                size: 13,
                                weight: '500'
                            },
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(30, 41, 59, 0.95)',
                        titleColor: '#ffffff',
                        bodyColor: '#8a94a6',
                        borderColor: 'rgba(102, 126, 234, 0.3)',
                        borderWidth: 1,
                        cornerRadius: 12,
                        padding: 16,
                        titleFont: {
                            family: "'Inter', sans-serif",
                            size: 13,
                            weight: '600'
                        },
                        bodyFont: {
                            family: "'Inter', sans-serif",
                            size: 13
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index',
                }
            }
        });
        
        // Chart 3: Distribution par statut
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusLabels = <?php echo json_encode(array_map(function($s) {
            $labels = [
                'nouveau' => 'Nouveau',
                'en_cours' => 'En cours',
                'traite' => 'Traité',
                'cloture' => 'Clôturé',
                'rejete' => 'Rejeté'
            ];
            return $labels[$s['statut']] ?? $s['statut'];
        }, $stats_statut)); ?>;
        const statusData = <?php echo json_encode(array_column($stats_statut, 'total')); ?>;
        const statusColors = ['#3b82f6', '#f59e0b', '#10b981', '#8a94a6', '#ef4444'];
        
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusData,
                    backgroundColor: statusColors.map(color => hex2rgb(color, 0.8)),
                    borderColor: statusColors.map(color => hex2rgb(color, 1)),
                    borderWidth: 2,
                    hoverOffset: 20,
                    borderRadius: 8,
                    spacing: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            color: '#8a94a6',
                            padding: 20,
                            font: {
                                family: "'Inter', sans-serif",
                                size: 13
                            },
                            usePointStyle: true,
                            pointStyle: 'circle',
                            pointRadius: 6
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(30, 41, 59, 0.95)',
                        titleColor: '#ffffff',
                        bodyColor: '#8a94a6',
                        borderColor: 'rgba(102, 126, 234, 0.3)',
                        borderWidth: 1,
                        cornerRadius: 12,
                        padding: 16,
                        titleFont: {
                            family: "'Inter', sans-serif",
                            size: 13,
                            weight: '600'
                        },
                        bodyFont: {
                            family: "'Inter', sans-serif",
                            size: 13
                        },
                        callbacks: {
                            label: function(context) {
                                const value = context.raw;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return ` ${value} alertes (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
        
        // Chart 4: Temps de traitement
        const delaiCtx = document.getElementById('delaiChart').getContext('2d');
        const delaiLabels = <?php echo json_encode(array_column($stats_delai, 'delai')); ?>;
        const delaiData = <?php echo json_encode(array_column($stats_delai, 'total')); ?>;
        const delaiColors = ['#10b981', '#f59e0b', '#f97316', '#ef4444'];
        
        new Chart(delaiCtx, {
            type: 'bar',
            data: {
                labels: delaiLabels,
                datasets: [{
                    label: 'Nombre d\'alertes',
                    data: delaiData,
                    backgroundColor: delaiColors.map(color => hex2rgb(color, 0.8)),
                    borderColor: delaiColors.map(color => hex2rgb(color, 1)),
                    borderWidth: 1,
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.05)',
                            drawBorder: false,
                        },
                        ticks: {
                            color: '#8a94a6',
                            font: {
                                family: "'Inter', sans-serif",
                                size: 12
                            },
                            padding: 10
                        },
                        border: {
                            display: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#8a94a6',
                            font: {
                                family: "'Inter', sans-serif",
                                size: 12
                            }
                        },
                        border: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(30, 41, 59, 0.95)',
                        titleColor: '#ffffff',
                        bodyColor: '#8a94a6',
                        borderColor: 'rgba(102, 126, 234, 0.3)',
                        borderWidth: 1,
                        cornerRadius: 12,
                        padding: 16,
                        titleFont: {
                            family: "'Inter', sans-serif",
                            size: 13,
                            weight: '600'
                        },
                        bodyFont: {
                            family: "'Inter', sans-serif",
                            size: 13
                        },
                        displayColors: true,
                        callbacks: {
                            afterLabel: function(context) {
                                const delai = <?php echo json_encode($stats_delai); ?>;
                                const index = context.dataIndex;
                                return `Moyenne: ${delai[index]?.moyenne_heures || 0} heures`;
                            }
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index',
                }
            }
        });
        
        // Interactions pour les boutons
        document.querySelectorAll('.period-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                // Ici vous pouvez ajouter la logique pour changer les données
            });
        });
        
        document.querySelectorAll('.chart-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const parent = this.parentElement;
                parent.querySelectorAll('.chart-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                // Ici vous pouvez ajouter la logique pour changer le type de graphique
            });
        });
        
        // Animation d'entrée pour les cartes
        const statCards = document.querySelectorAll('.stat-card');
        statCards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    </script>
</body>
</html>