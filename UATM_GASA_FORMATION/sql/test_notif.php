<?php
require_once 'C:\wamp64\www\projetGenieLogiciel\UATM_GASA_FORMATION\config\database.php';
require_once 'C:\wamp64\www\projetGenieLogiciel\UATM_GASA_FORMATION\includes\functions.php';
startSecureSession();

$db = getDBConnection();

// Creer une notification pour le professeur (id=3)
createNotification(
    3,
    'Test notification',
    'Ceci est un test de notification.',
    'info',
    'professeur/voir.php?id=1'
);

echo "Notification creee avec succes!\n";

// Verifier
$stmt = $db->prepare("SELECT * FROM notifications WHERE utilisateur_id = 3");
$stmt->execute();
$notifs = $stmt->fetchAll();
echo "Nombre de notifications: " . count($notifs) . "\n";
foreach ($notifs as $n) {
    echo "ID: {$n['id']}, Titre: {$n['titre']}, Lien: {$n['lien']}\n";
}
