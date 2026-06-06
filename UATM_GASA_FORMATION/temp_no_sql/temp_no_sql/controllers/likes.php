<?php
/**
 * Contrôleur Likes
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();

function toggleLike($memoireId, $userId) {
    $db = getDBConnection();
    
    // Vérifier que le mémoire est validé
    $stmt = $db->prepare("SELECT id FROM memoires WHERE id = ? AND statut = 'valide'");
    $stmt->execute([$memoireId]);
    if (!$stmt->fetch()) {
        return ['success' => false, 'message' => 'Mémoire introuvable.'];
    }
    
    // Vérifier si déjà liké
    $stmt = $db->prepare("SELECT id FROM likes WHERE memoire_id = ? AND utilisateur_id = ?");
    $stmt->execute([$memoireId, $userId]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        $stmt = $db->prepare("DELETE FROM likes WHERE id = ?");
        $stmt->execute([$existing['id']]);
        $liked = false;
    } else {
        $stmt = $db->prepare("INSERT INTO likes (memoire_id, utilisateur_id) VALUES (?, ?)");
        $stmt->execute([$memoireId, $userId]);
        $liked = true;
    }
    
    // Compter les likes
    $stmt = $db->prepare("SELECT COUNT(*) FROM likes WHERE memoire_id = ?");
    $stmt->execute([$memoireId]);
    $count = $stmt->fetchColumn();
    
    return ['success' => true, 'liked' => $liked, 'count' => $count];
}

function getLikeCount($memoireId) {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT COUNT(*) FROM likes WHERE memoire_id = ?");
    $stmt->execute([$memoireId]);
    return $stmt->fetchColumn();
}

function hasUserLiked($memoireId, $userId) {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT id FROM likes WHERE memoire_id = ? AND utilisateur_id = ? LIMIT 1");
    $stmt->execute([$memoireId, $userId]);
    return (bool) $stmt->fetch();
}
