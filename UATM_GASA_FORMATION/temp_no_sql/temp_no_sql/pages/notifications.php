<?php
$pageTitle = 'Notifications - UATM GASA FORMATION';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../auth/session.php';

if (!isLoggedIn()) {
    redirect($baseUrl . 'auth/login.php');
}

$db = getDBConnection();
$userId = $_SESSION['user_id'];

// Marquer comme lu si demandé
if (isset($_GET['read']) && $_GET['read'] === 'all') {
    $stmt = $db->prepare("UPDATE notifications SET lu = 1 WHERE utilisateur_id = ? AND lu = 0");
    $stmt->execute([$userId]);
    setFlash('success', 'Toutes les notifications ont été marquées comme lues.');
    redirect('notifications.php');
}

if (isset($_GET['read_id'])) {
    $notifId = intval($_GET['read_id']);
    $stmt = $db->prepare("UPDATE notifications SET lu = 1 WHERE id = ? AND utilisateur_id = ?");
    $stmt->execute([$notifId, $userId]);
    redirect('notifications.php');
}

// Supprimer
if (isset($_GET['delete'])) {
    $notifId = intval($_GET['delete']);
    $stmt = $db->prepare("DELETE FROM notifications WHERE id = ? AND utilisateur_id = ?");
    $stmt->execute([$notifId, $userId]);
    redirect('notifications.php');
}

if (isset($_GET['delete_all'])) {
    $stmt = $db->prepare("DELETE FROM notifications WHERE utilisateur_id = ?");
    $stmt->execute([$userId]);
    setFlash('success', 'Toutes les notifications ont été supprimées.');
    redirect('notifications.php');
}

// Récupérer les notifications
$notifications = $db->prepare("
    SELECT * FROM notifications WHERE utilisateur_id = ? ORDER BY created_at DESC
");
$notifications->execute([$userId]);
$notifications = $notifications->fetchAll();

$nbLues = 0;
$nbNonLues = 0;
foreach ($notifications as $n) {
    if ($n['lu']) $nbLues++;
    else $nbNonLues++;
}
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1 class="dashboard-title">Notifications</h1>
        <div style="display: flex; gap: 0.5rem;">
            <?php if ($nbNonLues > 0): ?>
            <a href="notifications.php?read=all" class="btn btn-outline btn-sm">Tout marquer comme lu (<?= $nbNonLues ?>)</a>
            <?php endif; ?>
            <?php if (!empty($notifications)): ?>
            <a href="notifications.php?delete_all=1" class="btn btn-danger btn-sm" data-confirm="Supprimer toutes les notifications ?">Tout supprimer</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($notifications)): ?>
    <div class="card">
        <div class="card-body" style="text-align: center; padding: 3rem;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">&#128276;</div>
            <h3 style="color: var(--gray-500);">Aucune notification</h3>
        </div>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="card-body" style="padding: 0;">
            <?php foreach ($notifications as $n): ?>
            <div style="display: flex; justify-content: space-between; align-items: flex-start; padding: 1rem 1.25rem; border-bottom: 1px solid var(--gray-100); <?= $n['lu'] ? '' : 'background: var(--gray-50); border-left: 3px solid var(--accent);' ?>">
                <div style="flex: 1;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                        <?php
                        $icons = ['info' => '&#8505;', 'success' => '&#9989;', 'warning' => '&#9888;', 'error' => '&#10060;'];
                        $colors = ['info' => 'var(--info)', 'success' => 'var(--success)', 'warning' => 'var(--warning)', 'error' => 'var(--danger)'];
                        ?>
                        <span style="color: <?= $colors[$n['type']] ?? 'var(--gray-400)' ?>; font-size: 1.1rem;"><?= $icons[$n['type']] ?? '&#8226;' ?></span>
                        <strong style="font-size: 0.95rem; color: var(--gray-800);"><?= sanitize($n['titre']) ?></strong>
                        <?php if (!$n['lu']): ?>
                        <span style="width: 8px; height: 8px; background: var(--accent); border-radius: 50; display: inline-block;"></span>
                        <?php endif; ?>
                    </div>
                    <p style="font-size: 0.9rem; color: var(--gray-600); margin-bottom: 0.25rem;"><?= sanitize($n['message']) ?></p>
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <span style="font-size: 0.8rem; color: var(--gray-400);"><?= date('d/m/Y H:i', strtotime($n['created_at'])) ?></span>
                        <?php if ($n['lien']): ?>
                        <a href="<?= $baseUrl ?><?= ltrim(sanitize($n['lien']), '/') ?>" style="font-size: 0.8rem;">Voir</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="display: flex; gap: 0.3rem; margin-left: 1rem;">
                    <?php if (!$n['lu']): ?>
                    <a href="notifications.php?read_id=<?= $n['id'] ?>" class="btn btn-sm btn-outline" title="Marquer comme lu">&#10003;</a>
                    <?php endif; ?>
                    <a href="notifications.php?delete=<?= $n['id'] ?>" class="btn btn-sm btn-outline" title="Supprimer" style="color: var(--danger);">&#10005;</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
