<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();

if (!isLoggedIn() || !hasRole('administrateur')) {
    redirect($baseUrl . 'auth/login.php');
}

$db = getDBConnection();
$userId = intval($_GET['id'] ?? 0);

if ($userId <= 0) {
    redirect('utilisateurs.php');
}

// Empêcher la suppression de soi-même
if ($userId == $_SESSION['user_id']) {
    setFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
    redirect('utilisateurs.php');
}

$stmt = $db->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    setFlash('error', 'Utilisateur non trouvé.');
    redirect('utilisateurs.php');
}

// Empêcher suppression d'un admin (sauf soi-même déjà bloqué)
if ($user['role_id'] == 1) {
    $countAdmin = $db->query("SELECT COUNT(*) FROM utilisateurs WHERE role_id = 1 AND statut = 'actif'")->fetchColumn();
    if ($countAdmin <= 1) {
        setFlash('error', 'Impossible de supprimer le dernier administrateur.');
        redirect('utilisateurs.php');
    }
}

// Supprimer les mémoires et fichiers de l'étudiant
if ($user['role_id'] == 4) {
    $memoires = $db->prepare("SELECT fichier_pdf FROM memoires WHERE etudiant_id = ?");
    $memoires->execute([$userId]);
    while ($m = $memoires->fetch()) {
        $path = __DIR__ . '/../assets/uploads/' . $m['fichier_pdf'];
        if (file_exists($path)) unlink($path);
    }
}

// Supprimer l'utilisateur (CASCADE: mémoires, commentaires, likes, notifications, historiques)
$stmt = $db->prepare("DELETE FROM utilisateurs WHERE id = ?");
$stmt->execute([$userId]);

logAction($_SESSION['user_id'], 'supprimer', 'utilisateur', $userId, 'Suppression: ' . $user['prenom'] . ' ' . $user['nom']);

setFlash('success', 'Utilisateur supprimé avec succès.');
redirect('utilisateurs.php');
