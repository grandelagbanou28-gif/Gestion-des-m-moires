<?php
require_once 'C:\wamp64\www\projetGenieLogiciel\UATM_GASA_FORMATION\config\database.php';
require_once 'C:\wamp64\www\projetGenieLogiciel\UATM_GASA_FORMATION\includes\functions.php';
startSecureSession();

$db = getDBConnection();

// Simuler le depot
$memoireId = 1;
$etudiantPrenom = 'Grandel';
$etudiantNom = 'AGBANOU';

// Notifier les professeurs
$professeurs = $db->prepare("SELECT id FROM utilisateurs WHERE role_id = 3 AND statut = 'actif'");
$professeurs->execute();
$count = 0;
while ($prof = $professeurs->fetch()) {
    createNotification(
        $prof['id'],
        'Nouveau memoire soumis',
        'Un nouveau memoire a ete soumis par ' . $etudiantPrenom . ' ' . $etudiantNom . '.',
        'info',
        'professeur/voir.php?id=' . $memoireId
    );
    $count++;
    echo "Notification creee pour professeur ID: {$prof['id']}\n";
}
echo "Total notifications creees: $count\n";
