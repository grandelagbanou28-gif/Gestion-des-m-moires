<?php
$password_admin = password_hash('admin123', PASSWORD_DEFAULT);
$password_directeur = password_hash('directeur123', PASSWORD_DEFAULT);

echo "Admin: $password_admin\n";
echo "Directeur: $password_directeur\n";

$pdo = new PDO('mysql:host=localhost;dbname=uatm_gasa_memoires', 'root', '');

// Update admin
$stmt = $pdo->prepare("UPDATE utilisateurs SET password = ? WHERE id = 1");
$stmt->execute([$password_admin]);
echo "Admin mis a jour\n";

// Insert directeur
$stmt = $pdo->prepare("INSERT INTO utilisateurs (role_id, nom, prenom, email, password, statut) VALUES (2, 'Directeur', 'General', 'directeur@uatm-gasa.com', ?, 'actif')");
$stmt->execute([$password_directeur]);
echo "Directeur cree\n";
