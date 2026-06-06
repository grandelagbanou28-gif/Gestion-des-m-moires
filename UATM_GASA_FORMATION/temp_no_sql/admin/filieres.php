<?php
$pageTitle = 'Gestion Filières - UATM GASA FORMATION';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../auth/session.php';

if (!isLoggedIn() || !hasRole('administrateur')) {
    redirect($baseUrl . 'auth/login.php');
}

$db = getDBConnection();

$filtreStatut = $_GET['statut'] ?? '';
$search = trim($_GET['q'] ?? '');

$sql = "SELECT f.*, (SELECT COUNT(*) FROM memoires m WHERE m.filiere_id = f.id) as nb_memoires FROM filieres f WHERE 1=1";
$params = [];

if (!empty($filtreStatut) && in_array($filtreStatut, ['active', 'inactive'])) {
    $sql .= " AND f.statut = ?";
    $params[] = $filtreStatut;
}

if (!empty($search)) {
    $sql .= " AND (f.nom LIKE ? OR f.code LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY f.nom ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$filieres = $stmt->fetchAll();
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1 class="dashboard-title">Gestion des filières</h1>
        <a href="ajouter_filiere.php" class="btn btn-primary">+ Ajouter une filière</a>
    </div>

    <!-- Filtres -->
    <div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
        <a href="filieres.php" class="btn btn-sm <?= empty($filtreStatut) ? 'btn-primary' : 'btn-outline' ?>">Toutes</a>
        <a href="filieres.php?statut=active" class="btn btn-sm <?= $filtreStatut === 'active' ? 'btn-primary' : 'btn-outline' ?>">Actives</a>
        <a href="filieres.php?statut=inactive" class="btn btn-sm <?= $filtreStatut === 'inactive' ? 'btn-primary' : 'btn-outline' ?>">Inactives</a>
        <form method="GET" action="" style="display: flex; gap: 0.5rem; margin-left: auto;">
            <?php if (!empty($filtreStatut)): ?>
            <input type="hidden" name="statut" value="<?= sanitize($filtreStatut) ?>">
            <?php endif; ?>
            <input type="text" name="q" placeholder="Rechercher..." value="<?= sanitize($search) ?>" class="form-control" style="width: 200px;">
            <button type="submit" class="btn btn-outline btn-sm">Rechercher</button>
        </form>
    </div>

    <div class="table-container">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nom</th>
                        <th>Code</th>
                        <th>Description</th>
                        <th>Mémoires</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($filieres)): ?>
                    <tr><td colspan="7" style="text-align: center; color: var(--gray-400); padding: 3rem;">
                        Aucune filière trouvée.
                    </td></tr>
                    <?php else: ?>
                    <?php foreach ($filieres as $i => $f): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= sanitize($f['nom']) ?></strong></td>
                        <td><span style="font-size: 0.8rem; background: var(--gray-100); padding: 0.2rem 0.5rem; border-radius: 4px;"><?= sanitize($f['code']) ?></span></td>
                        <td><?= sanitize(substr($f['description'] ?? '', 0, 50)) ?><?= strlen($f['description'] ?? '') > 50 ? '...' : '' ?></td>
                        <td><?= $f['nb_memoires'] ?></td>
                        <td>
                            <span class="status-badge <?= $f['statut'] === 'active' ? 'status-approved' : 'status-draft' ?>">
                                <?= ucfirst($f['statut']) ?>
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 0.3rem;">
                                <a href="modifier_filiere.php?id=<?= $f['id'] ?>" class="btn btn-sm btn-outline">Modifier</a>
                                <a href="supprimer_filiere.php?id=<?= $f['id'] ?>" class="btn btn-sm btn-danger" data-confirm="Supprimer cette filière ?">Supprimer</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
