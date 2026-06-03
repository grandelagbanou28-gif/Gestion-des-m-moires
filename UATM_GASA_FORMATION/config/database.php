<?php
/**
 * Configuration de la base de donnees - UATM GASA FORMATION
 * InfinityFree MySQL
 */

define('DB_HOST', 'sql308.infinityfree.com');
define('DB_NAME', 'if0_42086197_if0_gestiondesmemoires_uatm');
define('DB_USER', 'if0_42086197');
define('DB_PASS', 'test280601');
define('DB_CHARSET', 'utf8mb4');

function getDBConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=3306;dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Erreur de connexion DB: " . $e->getMessage());
            die("Erreur de connexion a la base de donnees.");
        }
    }
    
    return $pdo;
}
