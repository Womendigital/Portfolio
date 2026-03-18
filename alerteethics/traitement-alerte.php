<?php
// traitement-alerte.php - Script de traitement des alertes
session_start();

// Activer le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Vérifier que c'est bien une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Méthode non autorisée'
    ]);
    exit();
}

// Connexion à la base de données
require_once 'config/database.php';

/**
 * Fonction pour chiffrer les données avec AES-256-CBC
 */
function encryptData($data, $key, $iv) {
    $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
    return $encrypted;
}

/**
 * Fonction pour générer un code de suivi unique
 */
function generateTrackingCode() {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = 'AS-';
    for ($i = 0; $i < 8; $i++) {
        $code .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $code;
}

/**
 * Fonction pour obtenir l'ID du type d'alerte
 */
function getTypeAlerteId($type) {
    $map = [
        'corruption' => 1,
        'harcelement' => 2,
        'harassment' => 3,
        'discrimination' => 5,
        'fraude' => 4,
        'detournement' => 1,
        'conflit' => 6,
        'autre' => 9
    ];
    
    return $map[strtolower($type)] ?? 9;
}

/**
 * Fonction pour déterminer le niveau de gravité
 */
function determineGravite($type) {
    $gravites = [
        'corruption' => 4,
        'harcelement' => 3,
        'harassment' => 4,
        'discrimination' => 3,
        'fraude' => 4,
        'detournement' => 5,
        'conflit' => 3,
        'autre' => 2
    ];
    
    return $gravites[strtolower($type)] ?? 2;
}

// Variables pour le traitement
$response = [
    'success' => false,
    'message' => '',
    'trackingCode' => '',
    'errors' => []
];

// Récupérer la langue
$lang = $_POST['lang'] ?? 'fr';

try {
    // Récupération et validation des données
    $alertType = trim($_POST['alertType'] ?? '');
    $institution = trim($_POST['institution'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $additionalInfo = trim($_POST['additionalInfo'] ?? '');
    $consent = isset($_POST['consent']) && $_POST['consent'] === 'on';
    
    // Validation des champs requis
    $errors = [];
    
    if (empty($alertType)) {
        $errors[] = $lang === 'fr' ? 'Le type d\'alerte est requis' : 'Alert type is required';
    }
    
    if (empty($institution)) {
        $errors[] = $lang === 'fr' ? 'L\'institution est requise' : 'Institution is required';
    }
    
    if (empty($location)) {
        $errors[] = $lang === 'fr' ? 'La localisation est requise' : 'Location is required';
    }
    
    if (empty($description)) {
        $errors[] = $lang === 'fr' ? 'La description est requise' : 'Description is required';
    }
    
    if (!$consent) {
        $errors[] = $lang === 'fr' ? 'Vous devez accepter les conditions' : 'You must accept the terms';
    }
    
    if (!empty($errors)) {
        $response['errors'] = $errors;
        $response['message'] = implode(', ', $errors);
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
    
    // Connexion à la base de données
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Préparation des données pour le chiffrement
    $alertData = [
        'type' => $alertType,
        'institution' => $institution,
        'location' => $location,
        'description' => $description,
        'additionalInfo' => $additionalInfo,
        'timestamp' => date('Y-m-d H:i:s'),
        'lang' => $lang
    ];
    
    // Génération des clés de chiffrement
    $encryptionKey = bin2hex(random_bytes(32)); // Clé de 256 bits
    $iv = openssl_random_pseudo_bytes(16); // IV de 16 bytes
    $iv_hex = bin2hex($iv); // Conversion en hex pour stockage
    
    // Chiffrement des données
    $jsonData = json_encode($alertData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $contenuChiffre = encryptData($jsonData, hex2bin($encryptionKey), $iv);
    $hashContenu = hash('sha256', $contenuChiffre);
    
    // Obtention de l'ID du type d'alerte et de la gravité
    $typeAlerteId = getTypeAlerteId($alertType);
    $niveauGravite = determineGravite($alertType);
    
    // Démarrer une transaction
    $pdo->beginTransaction();
    
    try {
        // Insertion dans la table signalements
        $stmt = $pdo->prepare("
            INSERT INTO signalements 
            (contenu_chiffre, statut, type_alerte_id, niveau_gravite, canal_soumission, iv_chiffrement, hash_contenu) 
            VALUES (?, 'nouveau', ?, ?, 'web', ?, ?)
        ");
        
        $stmt->execute([
            $contenuChiffre,
            $typeAlerteId,
            $niveauGravite,
            $iv_hex,
            $hashContenu
        ]);
        
        $signalementId = $pdo->lastInsertId();
        
        // Génération du code de suivi
        $trackingCode = generateTrackingCode();
        
        // Hash de la clé de déchiffrement
        $cleHash = hash('sha256', $encryptionKey);
        
        // Insertion dans la table suivis
        $stmt = $pdo->prepare("
            INSERT INTO suivis 
            (code_tracking, signalement_id, cle_dechiffrement_hash) 
            VALUES (?, ?, ?)
        ");
        
        $stmt->execute([
            $trackingCode,
            $signalementId,
            $cleHash
        ]);
        
        // Gestion des fichiers uploadés (optionnel)
        if (!empty($_FILES['evidence']['name'][0])) {
            $fileCount = count($_FILES['evidence']['name']);
            
            for ($i = 0; $i < $fileCount; $i++) {
                if ($_FILES['evidence']['error'][$i] === UPLOAD_ERR_OK) {
                    $fileName = $_FILES['evidence']['name'][$i];
                    $fileTmpName = $_FILES['evidence']['tmp_name'][$i];
                    $fileSize = $_FILES['evidence']['size'][$i];
                    $fileType = $_FILES['evidence']['type'][$i];
                    
                    // Validation de la taille du fichier (10MB max)
                    if ($fileSize > 10 * 1024 * 1024) {
                        continue;
                    }
                    
                    // Validation du type de fichier
                    $allowedTypes = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'mp4', 'mov', 'avi'];
                    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    
                    if (!in_array($fileExtension, $allowedTypes)) {
                        continue;
                    }
                    
                    // Lecture et chiffrement du fichier
                    $fileContent = file_get_contents($fileTmpName);
                    $fileIv = openssl_random_pseudo_bytes(16);
                    $fileIvHex = bin2hex($fileIv);
                    
                    $encryptedFileName = encryptData($fileName, hex2bin($encryptionKey), $fileIv);
                    $encryptedContent = encryptData($fileContent, hex2bin($encryptionKey), $fileIv);
                    $fileHash = hash('sha256', $encryptedContent);
                    
                    // Insertion dans la table pieces_jointes
                    $stmt = $pdo->prepare("
                        INSERT INTO pieces_jointes 
                        (signalement_id, nom_fichier_chiffre, contenu_chiffre, type_mime, taille_originale, hash_fichier, iv_chiffrement) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $signalementId,
                        $encryptedFileName,
                        $encryptedContent,
                        $fileType,
                        $fileSize,
                        $fileHash,
                        $fileIvHex
                    ]);
                }
            }
        }
        
        // Journalisation dans audit_log
        $stmt = $pdo->prepare("
            INSERT INTO audit_log 
            (signalement_id, action_type, details, ip_address, user_agent, hash_entree) 
            VALUES (?, 'creation', ?, ?, ?, ?)
        ");
        
        $details = $lang === 'fr' 
            ? 'Nouveau signalement créé via le formulaire web' 
            : 'New report created via web form';
        
        $hashEntree = hash('sha256', $signalementId . time() . rand());
        $stmt->execute([
            $signalementId,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            $hashEntree
        ]);
        
        // Valider la transaction
        $pdo->commit();
        
        // Succès de la soumission
        $response['success'] = true;
        $response['trackingCode'] = $trackingCode;
        $response['message'] = $lang === 'fr' 
            ? 'Votre alerte a été soumise avec succès! Votre code de suivi est: ' . $trackingCode
            : 'Your alert has been submitted successfully! Your tracking code is: ' . $trackingCode;
            
    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    error_log("Erreur PDO traitement alerte: " . $e->getMessage());
    $response['message'] = $lang === 'fr' 
        ? 'Erreur technique lors du traitement. Veuillez réessayer.' 
        : 'Technical error during processing. Please try again.';
        
} catch (Exception $e) {
    error_log("Erreur générale traitement alerte: " . $e->getMessage());
    $response['message'] = $lang === 'fr' 
        ? 'Une erreur est survenue. Veuillez réessayer.' 
        : 'An error occurred. Please try again.';
}

// Retourner la réponse en JSON
header('Content-Type: application/json');
echo json_encode($response);
exit();