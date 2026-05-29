<?php
$pageTitle = 'Mes Mémoires - UATM GASA FORMATION';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../auth/session.php';

if (!isLoggedIn() || !hasRole('etudiant')) {
    redirect($baseUrl . 'auth/login.php');
}

$userId = $_SESSION['user_id'];
$db = getDBConnection();

// Filtre par statut
$filtre = $_GET['statut'] ?? '';
$statuts = ['brouillon', 'soumis', 'en_revision', 'valide', 'rejete', 'archive'];

$sql = "
    SELECT m.*, f.nom as filiere_nom 
    FROM memoires m 
    LEFT JOIN filieres f ON m.filiere_id = f.id 
    WHERE m.etudiant_id = ?
";
$params = [$userId];

if (!empty($filtre) && in_array($filtre, $statuts)) {
    $sql .= " AND m.statut = ?";
    $params[] = $filtre;
}

$sql .= " ORDER BY m.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$memoires = $stmt->fetchAll();

// Comptage par statut
$comptages = [];
foreach ($statuts as $s) {
    $stmtC = $db->prepare("SELECT COUNT(*) FROM memoires WHERE etudiant_id = ? AND statut = ?");
    $stmtC->execute([$userId, $s]);
    $comptages[$s] = $stmtC->fetchColumn();
}
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1 class="dashboard-title">Mes Mémoires</h1>
        <a href="deposer.php" class="btn btn-primary">+ Déposer un mémoire</a>
    </div>

    <!-- Filtres -->
    <div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
        <a href="memoires.php" class="btn btn-sm <?= empty($filtre) ? 'btn-primary' : 'btn-outline' ?>">
            Tous (<?= $total = array_sum($comptages) ?>)
        </a>
        <?php foreach ($statuts as $s): ?>
        <?php if ($comptages[$s] > 0): ?>
        <a href="memoires.php?statut=<?= $s ?>" class="btn btn-sm <?= $filtre === $s ? 'btn-primary' : 'btn-outline' ?>">
            <?= getStatusLabel($s) ?> (<?= $comptages[$s] ?>)
        </a>
        <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <!-- Liste -->
    <div class="table-container">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Titre</th>
                        <th>Filière</th>
                        <th>Année</th>
                        <th>Statut</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($memoires)): ?>
                    <tr><td colspan="7" style="text-align: center; color: var(--gray-400); padding: 3rem;">
                        Aucun mémoire trouvé.
                    </td></tr>
                    <?php else: ?>
                    <?php foreach ($memoires as $i => $m): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><a href="<?= $baseUrl ?>etudiant/voir.php?id=<?= $m['id'] ?>"><?= sanitize(substr($m['titre'], 0, 60)) ?><?= strlen($m['titre']) > 60 ? '...' : '' ?></a></td>
                        <td><?= sanitize($m['filiere_nom'] ?? '-') ?></td>
                        <td><?= sanitize($m['annee_academique']) ?></td>
                        <td><span class="status-badge <?= getStatusClass($m['statut']) ?>"><?= getStatusLabel($m['statut']) ?></span></td>
                        <td><?= date('d/m/Y', strtotime($m['created_at'])) ?></td>
                        <td>
                            <div style="display: flex; gap: 0.3rem;">
                                <a href="<?= $baseUrl ?>etudiant/voir.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-outline" title="Voir">Voir</a>
                                <?php if (in_array($m['statut'], ['brouillon', 'rejete'])): ?>
                                <a href="modifier.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-outline" title="Modifier">Modifier</a>
                                <a href="supprimer.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-danger" data-confirm="Supprimer ce mémoire ?">Supprimer</a>
                                <?php endif; ?>
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
