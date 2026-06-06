<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();

if (!isLoggedIn() || !hasRole('administrateur')) {
    redirect($baseUrl . 'auth/login.php');
}

$db = getDBConnection();
$filiereId = intval($_GET['id'] ?? 0);

if ($filiereId <= 0) {
    redirect('filieres.php');
}

$stmt = $db->prepare("SELECT * FROM filieres WHERE id = ?");
$stmt->execute([$filiereId]);
$filiere = $stmt->fetch();

if (!$filiere) {
    setFlash('error', 'Filière non trouvée.');
    redirect('filieres.php');
}

// Vérifier s'il y a des mémoires liés
$stmt = $db->prepare("SELECT COUNT(*) FROM memoires WHERE filiere_id = ?");
$stmt->execute([$filiereId]);
$nbMemoires = $stmt->fetchColumn();

if ($nbMemoires > 0) {
    setFlash('error', 'Impossible de supprimer cette filière : ' . $nbMemoires . ' mémoire(s) associé(s).');
    redirect('filieres.php');
}

$stmt = $db->prepare("DELETE FROM filieres WHERE id = ?");
$stmt->execute([$filiereId]);

logAction($_SESSION['user_id'], 'supprimer', 'filiere', $filiereId, 'Suppression: ' . $filiere['nom']);

setFlash('success', 'Filière supprimée avec succès.');
redirect('filieres.php');
