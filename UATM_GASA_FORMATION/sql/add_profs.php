<?php
require_once 'C:\wamp64\www\projetGenieLogiciel\UATM_GASA_FORMATION\config\database.php';
$db = getDBConnection();

$professeurs = [
    ['nom' => 'KPODEKON', 'prenom' => 'Andre', 'email' => 'kpodekon.andre@uatm-gasa.com'],
    ['nom' => 'TOSSOU', 'prenom' => 'Romuald', 'email' => 'tossou.romuald@uatm-gasa.com'],
    ['nom' => 'ADJANHOUMI', 'prenom' => 'Gildas', 'email' => 'adjanhoumi.gildas@uatm-gasa.com'],
    ['nom' => 'HOUNGNIDO', 'prenom' => 'Arnaud', 'email' => 'houngnido.arnaud@uatm-gasa.com'],
    ['nom' => 'DANSOU', 'prenom' => 'Romuald', 'email' => 'dansou.romuald@uatm-gasa.com'],
    ['nom' => 'GBENOU', 'prenom' => 'Smaila', 'email' => 'gbenou.smaila@uatm-gasa.com'],
    ['nom' => 'SEHOU', 'prenom' => 'Joachim', 'email' => 'sehou.joachim@uatm-gasa.com'],
    ['nom' => 'ADOCHI', 'prenom' => 'Pascal', 'email' => 'adochi.pascal@uatm-gasa.com'],
    ['nom' => 'AHAMAVI', 'prenom' => 'Tilak', 'email' => 'ahamavi.tilak@uatm-gasa.com'],
    ['nom' => 'GOLOU', 'prenom' => 'Geraud', 'email' => 'golou.geraud@uatm-gasa.com'],
    ['nom' => 'HOUNHOUHO', 'prenom' => 'Dorcas', 'email' => 'hounhouho.dorcas@uatm-gasa.com'],
    ['nom' => 'AZOULAY', 'prenom' => 'Andre', 'email' => 'azoulay.andre@uatm-gasa.com'],
    ['nom' => 'SEGBEDJI', 'prenom' => 'Sosthene', 'email' => 'segbedji.sosthene@uatm-gasa.com'],
    ['nom' => 'DOSSOU', 'prenom' => 'Gbenou', 'email' => 'dossou.gbenou@uatm-gasa.com'],
];

$password = password_hash('prof123', PASSWORD_DEFAULT);

foreach ($professeurs as $p) {
    $stmt = $db->prepare("INSERT IGNORE INTO utilisateurs (role_id, nom, prenom, email, password, statut) VALUES (3, ?, ?, ?, ?, 'actif')");
    $stmt->execute([$p['nom'], $p['prenom'], $p['email'], $password]);
    echo "Prof: {$p['prenom']} {$p['nom']} - OK\n";
}

echo "\nTotal professeurs:\n";
$count = $db->query("SELECT COUNT(*) FROM utilisateurs WHERE role_id = 3")->fetchColumn();
echo "$count professeurs actifs\n";
