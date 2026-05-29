<?php
/**
 * Handler AJAX - Gestion des likes
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit();
}

$memoireId = intval($_POST['memoire_id'] ?? 0);
$userId = $_SESSION['user_id'];

if ($memoireId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID invalide.']);
    exit();
}

$db = getDBConnection();

// Vérifier que le mémoire existe et est validé
$stmt = $db->prepare("SELECT id, statut FROM memoires WHERE id = ?");
$stmt->execute([$memoireId]);
$memoire = $stmt->fetch();

if (!$memoire || $memoire['statut'] !== 'valide') {
    echo json_encode(['success' => false, 'message' => 'Mémoire introuvable.']);
    exit();
}

// Vérifier si déjà liké
$stmt = $db->prepare("SELECT id FROM likes WHERE memoire_id = ? AND utilisateur_id = ?");
$stmt->execute([$memoireId, $userId]);
$existing = $stmt->fetch();

if ($existing) {
    // Retirer le like
    $stmt = $db->prepare("DELETE FROM likes WHERE id = ?");
    $stmt->execute([$existing['id']]);
    $liked = false;
} else {
    // Ajouter le like
    $stmt = $db->prepare("INSERT INTO likes (memoire_id, utilisateur_id) VALUES (?, ?)");
    $stmt->execute([$memoireId, $userId]);
    $liked = true;
}

// Compter les likes
$stmt = $db->prepare("SELECT COUNT(*) FROM likes WHERE memoire_id = ?");
$stmt->execute([$memoireId]);
$count = $stmt->fetchColumn();

echo json_encode(['success' => true, 'liked' => $liked, 'count' => $count]);
