<?php
$pageTitle = 'Mon Espace - UATM GASA FORMATION';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../auth/session.php';

if (!isLoggedIn() || !hasRole('etudiant')) {
    redirect($baseUrl . 'auth/login.php');
}

$userId = $_SESSION['user_id'];
$db = getDBConnection();

// Verifier le niveau
$stmt = $db->prepare("SELECT niveau FROM utilisateurs WHERE id = ?");
$stmt->execute([$userId]);
$niveau = $stmt->fetchColumn();
$peutDeposer = in_array($niveau, ['L3', 'M2']);

// Statistiques etudiant
$stmt = $db->prepare("SELECT COUNT(*) FROM memoires WHERE etudiant_id = ?");
$stmt->execute([$userId]);
$totalMemoires = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM memoires WHERE etudiant_id = ? AND statut = 'valide'");
$stmt->execute([$userId]);
$valides = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM memoires WHERE etudiant_id = ? AND statut = 'soumis'");
$stmt->execute([$userId]);
$enCours = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM memoires WHERE etudiant_id = ? AND statut = 'rejete'");
$stmt->execute([$userId]);
$rejetes = $stmt->fetchColumn();

// Derniers mémoires
$stmt = $db->prepare("
    SELECT m.*, f.nom as filiere_nom 
    FROM memoires m 
    LEFT JOIN filieres f ON m.filiere_id = f.id 
    WHERE m.etudiant_id = ? 
    ORDER BY m.created_at DESC 
    LIMIT 5
");
$stmt->execute([$userId]);
$derniersMemoires = $stmt->fetchAll();

// Notifications récentes
$stmt = $db->prepare("SELECT * FROM notifications WHERE utilisateur_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1 class="dashboard-title">Mon Espace Etudiant 
            <span style="font-size: 0.8rem; background: var(--gray-100); padding: 0.2rem 0.6rem; border-radius: 12px; font-weight: 400; color: var(--gray-500);"><?= sanitize($niveau ?: 'Niveau non defini') ?></span>
        </h1>
        <?php if ($peutDeposer): ?>
        <a href="deposer.php" class="btn btn-primary">+ Deposer un memoire</a>
        <?php else: ?>
        <span style="font-size: 0.85rem; color: var(--gray-400);">Depot reserve aux L3 et M2</span>
        <?php endif; ?>
    </div>

    <div class="dashboard-stats">
        <div class="dash-stat-card">
            <h3>Total mémoires</h3>
            <div class="value"><?= $totalMemoires ?></div>
        </div>
        <div class="dash-stat-card" style="border-left-color: var(--success)">
            <h3>Validés</h3>
            <div class="value" style="color: var(--success)"><?= $valides ?></div>
        </div>
        <div class="dash-stat-card" style="border-left-color: var(--warning)">
            <h3>En cours</h3>
            <div class="value" style="color: var(--warning)"><?= $enCours ?></div>
        </div>
        <div class="dash-stat-card" style="border-left-color: var(--danger)">
            <h3>Rejetés</h3>
            <div class="value" style="color: var(--danger)"><?= $rejetes ?></div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
        <!-- Derniers mémoires -->
        <div class="table-container">
            <div class="table-header">
                <h2 style="font-size: 1.1rem; color: var(--primary);">Mes mémoires récents</h2>
                <a href="memoires.php" class="btn btn-sm btn-outline">Voir tout</a>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Filière</th>
                            <th>Statut</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($derniersMemoires)): ?>
                        <tr><td colspan="4" style="text-align: center; color: var(--gray-400); padding: 2rem;">
                            Aucun mémoire déposé.
                        </td></tr>
                        <?php else: ?>
                        <?php foreach ($derniersMemoires as $m): ?>
                        <tr>
                            <td><a href="<?= $baseUrl ?>etudiant/voir.php?id=<?= $m['id'] ?>"><?= sanitize(substr($m['titre'], 0, 50)) ?><?= strlen($m['titre']) > 50 ? '...' : '' ?></a></td>
                            <td><?= sanitize($m['filiere_nom'] ?? '-') ?></td>
                            <td><span class="status-badge <?= getStatusClass($m['statut']) ?>"><?= getStatusLabel($m['statut']) ?></span></td>
                            <td><?= date('d/m/Y', strtotime($m['created_at'])) ?></td>
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
