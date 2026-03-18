<?php
// traitement-suivi.php - Script de traitement du suivi d'alerte
session_start();
header('Content-Type: application/json');

// Connexion à la base de données
require_once 'config/database.php';

// Variables de réponse
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

// Langue par défaut
$lang = $_POST['lang'] ?? 'fr';

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = $lang === 'fr' 
        ? 'Méthode non autorisée' 
        : 'Method not allowed';
    echo json_encode($response);
    exit();
}

// Récupérer le code de suivi
$tracking_code = trim($_POST['tracking_code'] ?? '');

// Validation du code de suivi
if (empty($tracking_code)) {
    $response['message'] = $lang === 'fr' 
        ? 'Veuillez saisir un code de suivi' 
        : 'Please enter a tracking code';
    echo json_encode($response);
    exit();
}

// Nettoyer et formater le code
$tracking_code = strtoupper(preg_replace('/[^A-Z0-9\-]/', '', $tracking_code));

// Validation du format
if (!preg_match('/^[A-Z0-9\-]{8,20}$/', $tracking_code)) {
    $response['message'] = $lang === 'fr' 
        ? 'Format de code de suivi invalide' 
        : 'Invalid tracking code format';
    echo json_encode($response);
    exit();
}

try {
    // Connexion à la base de données
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Requête pour récupérer les informations de l'alerte
    $stmt = $pdo->prepare("
        SELECT 
            s.id,
            s.date_creation,
            s.statut,
            s.niveau_gravite,
            s.canal_soumission,
            s.blockchain_hash,
            s.date_maj,
            su.code_tracking,
            su.dernier_acces,
            su.nombre_acces,
            ta.nom as type_alerte_nom
        FROM suivis su
        JOIN signalements s ON su.signalement_id = s.id
        LEFT JOIN types_alerte ta ON s.type_alerte_id = ta.id
        WHERE su.code_tracking = ?
    ");
    
    $stmt->execute([$tracking_code]);
    $alert_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($alert_info) {
        // Mettre à jour les statistiques d'accès
        $update_stmt = $pdo->prepare("
            UPDATE suivis 
            SET dernier_acces = NOW(), nombre_acces = nombre_acces + 1 
            WHERE code_tracking = ?
        ");
        $update_stmt->execute([$tracking_code]);
        
        // Journalisation dans audit_log
        $audit_stmt = $pdo->prepare("
            INSERT INTO audit_log 
            (signalement_id, action_type, details, ip_address, user_agent, hash_entree) 
            VALUES (?, 'acces_suivi', ?, ?, ?, SHA2(CONCAT(?, NOW(), RAND()), 256))
        ");
        
        $audit_stmt->execute([
            $alert_info['id'],
            'Consultation du suivi via code: ' . $tracking_code,
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            $_SERVER['HTTP_USER_AGENT'] ?? 'Inconnu',
            $alert_info['id']
        ]);
        
        // Préparer la réponse
        $response['success'] = true;
        $response['data'] = [
            'id' => $alert_info['id'],
            'date_creation' => $alert_info['date_creation'],
            'statut' => $alert_info['statut'],
            'niveau_gravite' => (int)$alert_info['niveau_gravite'],
            'canal_soumission' => $alert_info['canal_soumission'],
            'blockchain_hash' => $alert_info['blockchain_hash'],
            'date_maj' => $alert_info['date_maj'],
            'code_tracking' => $alert_info['code_tracking'],
            'dernier_acces' => $alert_info['dernier_acces'],
            'nombre_acces' => (int)$alert_info['nombre_acces'],
            'type_alerte_nom' => $alert_info['type_alerte_nom']
        ];
        
        $response['message'] = $lang === 'fr' 
            ? 'Signalement trouvé avec succès' 
            : 'Report found successfully';
            
    } else {
        $response['message'] = $lang === 'fr' 
            ? 'Code de suivi non trouvé. Vérifiez le code et réessayez.' 
            : 'Tracking code not found. Please check the code and try again.';
    }
    
} catch (PDOException $e) {
    error_log("Erreur base de données suivi: " . $e->getMessage());
    $response['message'] = $lang === 'fr' 
        ? 'Erreur technique lors de la recherche. Veuillez réessayer.' 
        : 'Technical error during search. Please try again.';
        
} catch (Exception $e) {
    error_log("Erreur générale suivi: " . $e->getMessage());
    $response['message'] = $lang === 'fr' 
        ? 'Une erreur est survenue. Veuillez réessayer.' 
        : 'An error occurred. Please try again.';
}

// Retourner la réponse JSON
echo json_encode($response);
exit();