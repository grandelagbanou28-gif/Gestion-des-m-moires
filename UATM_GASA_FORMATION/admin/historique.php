<?php
$pageTitle = 'Historique Système - UATM GASA FORMATION';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../auth/session.php';

if (!isLoggedIn() || !hasRole('administrateur')) {
    redirect($baseUrl . 'auth/login.php');
}

$db = getDBConnection();

$filtreAction = $_GET['action'] ?? '';
$filtreEntite = $_GET['entite'] ?? '';

$sql = "SELECT h.*, u.nom, u.prenom FROM historiques h JOIN utilisateurs u ON h.utilisateur_id = u.id WHERE 1=1";
$params = [];

if (!empty($filtreAction)) {
    $sql .= " AND h.action = ?";
    $params[] = $filtreAction;
}

if (!empty($filtreEntite)) {
    $sql .= " AND h.entite = ?";
    $params[] = $filtreEntite;
}

$sql .= " ORDER BY h.created_at DESC LIMIT 100";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$historiques = $stmt->fetchAll();

// Actions disponibles
$actionsDispo = $db->query("SELECT DISTINCT action FROM historiques ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);
$entitesDispo = $db->query("SELECT DISTINCT entite FROM historiques ORDER BY entite")->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1 class="dashboard-title">Historique système</h1>
        <a href="index.php" class="btn btn-outline btn-sm">← Retour au dashboard</a>
    </div>

    <!-- Filtres -->
    <div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
        <form method="GET" action="" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <select name="action" class="form-control" style="width: 160px;">
                <option value="">Toutes les actions</option>
                <?php foreach ($actionsDispo as $a): ?>
                <option value="<?= sanitize($a) ?>" <?= $filtreAction === $a ? 'selected' : '' ?>><?= sanitize($a) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="entite" class="form-control" style="width: 160px;">
                <option value="">Toutes les entités</option>
                <?php foreach ($entitesDispo as $e): ?>
                <option value="<?= sanitize($e) ?>" <?= $filtreEntite === $e ? 'selected' : '' ?>><?= sanitize($e) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-outline btn-sm">Filtrer</button>
            <a href="historique.php" class="btn btn-outline btn-sm">Réinitialiser</a>
        </form>
    </div>

    <div class="table-container">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Date/Heure</th>
                        <th>Utilisateur</th>
                        <th>Action</th>
                        <th>Entité</th>
                        <th>ID Entité</th>
                        <th>Détails</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($historiques)): ?>
                    <tr><td colspan="7" style="text-align: center; color: var(--gray-400); padding: 3rem;">
                        Aucun historique trouvé.
                    </td></tr>
                    <?php else: ?>
                    <?php foreach ($historiques as $h): ?>
                    <tr>
                        <td style="white-space: nowrap;"><?= date('d/m/Y H:i:s', strtotime($h['created_at'])) ?></td>
                        <td><?= sanitize($h['prenom'] . ' ' . $h['nom']) ?></td>
                        <td><span style="font-size: 0.8rem; background: var(--gray-100); padding: 0.15rem 0.5rem; border-radius: 4px;"><?= sanitize($h['action']) ?></span></td>
                        <td><?= sanitize($h['entite']) ?></td>
                        <td><?= $h['entite_id'] ?? '-' ?></td>
                        <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= sanitize($h['details'] ?? '') ?>"><?= sanitize(substr($h['details'] ?? '', 0, 50)) ?></td>
                        <td style="font-size: 0.8rem; color: var(--gray-400);"><?= sanitize($h['ip_address'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
