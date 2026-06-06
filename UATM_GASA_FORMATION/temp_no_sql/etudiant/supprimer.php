<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../controllers/upload.php';
startSecureSession();

if (!isLoggedIn() || !hasRole('etudiant')) {
    redirect($baseUrl . 'auth/login.php');
}

$db = getDBConnection();
$userId = $_SESSION['user_id'];
$memoireId = intval($_GET['id'] ?? 0);

if ($memoireId <= 0) {
    redirect('memoires.php');
}

// Vérifier que le mémoire appartient à l'étudiant
$stmt = $db->prepare("SELECT * FROM memoires WHERE id = ? AND etudiant_id = ?");
$stmt->execute([$memoireId, $userId]);
$memoire = $stmt->fetch();

if (!$memoire) {
    setFlash('error', 'Mémoire non trouvé.');
    redirect('memoires.php');
}

// Seuls les brouillons ou rejetés
if (!in_array($memoire['statut'], ['brouillon', 'rejete'])) {
    setFlash('error', 'Ce mémoire ne peut pas être supprimé.');
    redirect('memoires.php');
}

// Supprimer le fichier
deleteUploadedFile($memoire['fichier_pdf']);

// Supprimer de la base (CASCADE supprime commentaires, likes, validations, etc.)
$stmt = $db->prepare("DELETE FROM memoires WHERE id = ? AND etudiant_id = ?");
$stmt->execute([$memoireId, $userId]);

logAction($userId, 'supprimer', 'memoire', $memoireId, 'Suppression mémoire: ' . $memoire['titre']);

setFlash('success', 'Mémoire supprimé avec succès.');
redirect('memoires.php');
