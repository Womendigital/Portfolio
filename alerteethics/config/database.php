<?php

class Database {
    private $host = 'localhost';
    private $dbname = 'alerteethics';
    private $username = 'alert_app';  
    private $password = 'AppSecurePass123!';  
    
    public function getConnection() {
        try {
            $pdo = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            
            return $pdo;
            
        } catch(PDOException $e) {
          
            error_log("[" . date('Y-m-d H:i:s') . "] Erreur DB: " . $e->getMessage());
           
            throw new Exception("Service temporairement indisponible. Veuillez réessayer.");
        }
    }
}


try {
    $pdo = (new Database())->getConnection();
} catch(Exception $e) {
  
    http_response_code(503);
    die("<h1>Service temporairement indisponible</h1><p>Veuillez réessayer dans quelques instants.</p>");
}
?>