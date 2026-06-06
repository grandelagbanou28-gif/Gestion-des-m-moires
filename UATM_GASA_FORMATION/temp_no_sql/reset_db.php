<?php
$host = 'sql308.infinityfree.com';
$user = 'if0_42086197';
$pass = 'test280601';
$dbname = 'if0_42086197_if0_gestiondesmemoires_uatm';

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "Connexion OK\n";

    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS `$table`");
        echo "Table supprimee: $table\n";
    }
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "Toutes les tables supprimees.\n";

    $sql = file_get_contents(__DIR__ . '/sql/infinityfree.sql');
    $pdo->exec($sql);
    echo "SQL reimporte avec succes!\n";

} catch (PDOException $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}
