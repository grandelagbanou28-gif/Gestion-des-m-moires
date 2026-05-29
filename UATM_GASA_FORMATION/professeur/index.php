<?php
$pageTitle = 'Espace Professeur - UATM GASA FORMATION';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../auth/session.php';

if (!isLoggedIn() || !hasRole('professeur')) {
    redirect($baseUrl . 'auth/login.php');
}

$userId = $_SESSION['user_id'];
$db = getDBConnection();

// Statistiques
$stmt = $db->prepare("SELECT COUNT(*) FROM memoires WHERE professeur_id = ?");
$stmt->execute([$userId]);
$assignes = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM memoires WHERE professeur_id = ? AND statut = 'soumis'");
$stmt->execute([$userId]);
$enAttente = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM memoires WHERE professeur_id = ? AND statut = 'en_revision'");
$stmt->execute([$userId]);
$enRevision = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM memoires WHERE professeur_id = ? AND statut = 'valide'");
$stmt->execute([$userId]);
$valides = $stmt->fetchColumn();

// Mémoires en attente (soumis ou en révision)
$stmt = $db->prepare("
    SELECT m.*, f.nom as filiere_nom, u.nom as etudiant_nom, u.prenom as etudiant_prenom
    FROM memoires m 
    LEFT JOIN filieres f ON m.filiere_id = f.id
    LEFT JOIN utilisateurs u ON m.etudiant_id = u.id
    WHERE m.professeur_id = ? AND m.statut IN ('soumis', 'en_revision')
    ORDER BY m.date_soumission ASC
    LIMIT 10
");
$stmt->execute([$userId]);
$memoiresEnAttente = $stmt->fetchAll();

// Notifications
$stmt = $db->prepare("SELECT * FROM notifications WHERE utilisateur_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1 class="dashboard-title">Espace Professeur</h1>
        <a href="memoires.php" class="btn btn-primary">Voir tous les mémoires</a>
    </div>

    <div class="dashboard-stats">
        <div class="dash-stat-card">
            <h3>Assignés</h3>
            <div class="value"><?= $assignes ?></div>
        </div>
        <div class="dash-stat-card" style="border-left-color: var(--warning)">
            <h3>En attente</h3>
            <div class="value" style="color: var(--warning)"><?= $enAttente ?></div>
        </div>
        <div class="dash-stat-card" style="border-left-color: var(--info)">
            <h3>En révision</h3>
            <div class="value" style="color: var(--info)"><?= $enRevision ?></div>
        </div>
        <div class="dash-stat-card" style="border-left-color: var(--success)">
            <h3>Validés</h3>
            <div class="value" style="color: var(--success)"><?= $valides ?></div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
        <!-- Mémoires en attente -->
        <div class="table-container">
            <div class="table-header">
                <h2 style="font-size: 1.1rem; color: var(--primary);">Mémoires en attente</h2>
                <a href="memoires.php?statut=soumis" class="btn btn-sm btn-outline">Voir tout</a>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Étudiant</th>
                            <th>Titre</th>
                            <th>Filière</th>
                            <th>Statut</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($memoiresEnAttente)): ?>
                        <tr><td colspan="5" style="text-align: center; color: var(--gray-400); padding: 2rem;">
                            Aucun mémoire en attente.
                        </td></tr>
                        <?php else: ?>
                        <?php foreach ($memoiresEnAttente as $m): ?>
                        <tr>
                            <td><?= sanitize($m['etudiant_prenom'] . ' ' . $m['etudiant_nom']) ?></td>
                            <td><a href="<?= $baseUrl ?>professeur/voir.php?id=<?= $m['id'] ?>"><?= sanitize(substr($m['titre'], 0, 40)) ?><?= strlen($m['titre']) > 40 ? '...' : '' ?></a></td>
                            <td><?= sanitize($m['filiere_nom'] ?? '-') ?></td>
                            <td><span class="status-badge <?= getStatusClass($m['statut']) ?>"><?= getStatusLabel($m['statut']) ?></span></td>
                            <td>                                <a href="<?= $baseUrl ?>professeur/voir.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-primary">Examiner</a></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Notifications -->
        <div class="card">
            <div class="card-header">
                <h2 style="font-size: 1.1rem; color: var(--primary);">Notifications</h2>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if (empty($notifications)): ?>
                <p style="text-align: center; color: var(--gray-400); padding: 1.5rem;">Aucune notification.</p>
                <?php else: ?>
                <?php foreach ($notifications as $n): ?>
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid var(--gray-100); <?= $n['lu'] ? '' : 'background: var(--gray-50);' ?>">
                    <div style="font-size: 0.85rem; font-weight: 600; color: var(--gray-700);"><?= sanitize($n['titre']) ?></div>
                    <div style="font-size: 0.8rem; color: var(--gray-500);"><?= sanitize($n['message']) ?></div>
                    <div style="font-size: 0.75rem; color: var(--gray-400); margin-top: 0.25rem;"><?= date('d/m/Y H:i', strtotime($n['created_at'])) ?></div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
