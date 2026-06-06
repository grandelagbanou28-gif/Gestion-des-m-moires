<?php
$pageTitle = 'Mémoires - UATM GASA FORMATION';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../auth/session.php';

if (!isLoggedIn() || !hasRole('professeur')) {
    redirect($baseUrl . 'auth/login.php');
}

$userId = $_SESSION['user_id'];
$db = getDBConnection();

$filtre = $_GET['statut'] ?? '';
$search = trim($_GET['q'] ?? '');
$statuts = ['soumis', 'en_revision', 'valide', 'rejete'];

$sql = "
    SELECT m.*, f.nom as filiere_nom, u.nom as etudiant_nom, u.prenom as etudiant_prenom
    FROM memoires m 
    LEFT JOIN filieres f ON m.filiere_id = f.id
    LEFT JOIN utilisateurs u ON m.etudiant_id = u.id
    WHERE m.professeur_id = ?
";
$params = [$userId];

if (!empty($filtre) && in_array($filtre, $statuts)) {
    $sql .= " AND m.statut = ?";
    $params[] = $filtre;
}

if (!empty($search)) {
    $sql .= " AND (m.titre LIKE ? OR u.nom LIKE ? OR u.prenom LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY m.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$memoires = $stmt->fetchAll();

// Comptage
$comptages = [];
foreach ($statuts as $s) {
    $stmtC = $db->prepare("SELECT COUNT(*) FROM memoires WHERE professeur_id = ? AND statut = ?");
    $stmtC->execute([$userId, $s]);
    $comptages[$s] = $stmtC->fetchColumn();
}
$total = array_sum($comptages);
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1 class="dashboard-title">Mémoires assignés</h1>
        <div style="display: flex; gap: 0.5rem;">
            <form method="GET" action="" style="display: flex; gap: 0.5rem;">
                <input type="text" name="q" placeholder="Rechercher..." value="<?= sanitize($search) ?>" class="form-control" style="width: 220px;">
                <button type="submit" class="btn btn-outline btn-sm">Rechercher</button>
            </form>
        </div>
    </div>

    <!-- Filtres -->
    <div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
        <a href="memoires.php" class="btn btn-sm <?= empty($filtre) ? 'btn-primary' : 'btn-outline' ?>">
            Tous (<?= $total ?>)
        </a>
        <?php foreach ($statuts as $s): ?>
        <a href="memoires.php?statut=<?= $s ?>&q=<?= urlencode($search) ?>" class="btn btn-sm <?= $filtre === $s ? 'btn-primary' : 'btn-outline' ?>">
            <?= getStatusLabel($s) ?> (<?= $comptages[$s] ?>)
        </a>
        <?php endforeach; ?>
    </div>

    <div class="table-container">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Étudiant</th>
                        <th>Titre</th>
                        <th>Filière</th>
                        <th>Année</th>
                        <th>Statut</th>
                        <th>Soumis le</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($memoires)): ?>
                    <tr><td colspan="8" style="text-align: center; color: var(--gray-400); padding: 3rem;">
                        Aucun mémoire trouvé.
                    </td></tr>
                    <?php else: ?>
                    <?php foreach ($memoires as $i => $m): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= sanitize($m['etudiant_prenom'] . ' ' . $m['etudiant_nom']) ?></td>
                        <td><a href="<?= $baseUrl ?>professeur/voir.php?id=<?= $m['id'] ?>"><?= sanitize(substr($m['titre'], 0, 50)) ?><?= strlen($m['titre']) > 50 ? '...' : '' ?></a></td>
                        <td><?= sanitize($m['filiere_nom'] ?? '-') ?></td>
                        <td><?= sanitize($m['annee_academique']) ?></td>
                        <td><span class="status-badge <?= getStatusClass($m['statut']) ?>"><?= getStatusLabel($m['statut']) ?></span></td>
                        <td><?= $m['date_soumission'] ? date('d/m/Y', strtotime($m['date_soumission'])) : '-' ?></td>
                        <td>
                            <div style="display: flex; gap: 0.3rem;">
                                <a href="<?= $baseUrl ?>professeur/voir.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-primary">Examiner</a>
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
