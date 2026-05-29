<?php
$pageTitle = 'Bibliothèque Numérique - UATM GASA FORMATION';
require_once __DIR__ . '/../includes/header.php';

$db = getDBConnection();

// Filtres
$search = trim($_GET['q'] ?? '');
$filiereId = intval($_GET['filiere'] ?? 0);
$annee = trim($_GET['annee'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$parPage = 12;
$offset = ($page - 1) * $parPage;

$sql = "
    SELECT m.*, f.nom as filiere_nom, f.code as filiere_code,
           u.nom as etudiant_nom, u.prenom as etudiant_prenom,
           (SELECT COUNT(*) FROM likes l WHERE l.memoire_id = m.id) as nb_likes,
           (SELECT COUNT(*) FROM commentaires c WHERE c.memoire_id = m.id) as nb_commentaires
    FROM memoires m 
    LEFT JOIN filieres f ON m.filiere_id = f.id
    LEFT JOIN utilisateurs u ON m.etudiant_id = u.id
    WHERE m.statut = 'valide'
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (MATCH(m.titre, m.description, m.mot_cles) AGAINST(? IN BOOLEAN MODE) OR m.titre LIKE ? OR u.nom LIKE ? OR u.prenom LIKE ?)";
    $like = "%$search%";
    $params[] = $search;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($filiereId > 0) {
    $sql .= " AND m.filiere_id = ?";
    $params[] = $filiereId;
}

if (!empty($annee)) {
    $sql .= " AND m.annee_academique = ?";
    $params[] = $annee;
}

// Compter le total
$countSql = str_replace("SELECT m.*, f.nom as filiere_nom, f.code as filiere_code,\n           u.nom as etudiant_nom, u.prenom as etudiant_prenom,\n           (SELECT COUNT(*) FROM likes l WHERE l.memoire_id = m.id) as nb_likes,\n           (SELECT COUNT(*) FROM commentaires c WHERE c.memoire_id = m.id) as nb_commentaires", "SELECT COUNT(*)", $sql);
$stmtCount = $db->prepare($countSql);
$stmtCount->execute($params);
$total = $stmtCount->fetchColumn();
$totalPages = ceil($total / $parPage);

$sql .= " ORDER BY m.date_validation DESC LIMIT ? OFFSET ?";
$params[] = $parPage;
$params[] = $offset;

$stmt = $db->prepare($sql);
$stmt->execute($params);
$memoires = $stmt->fetchAll();

// Filières disponibles
$filieres = $db->query("SELECT * FROM filieres WHERE statut = 'active' ORDER BY nom")->fetchAll();

// Années disponibles
$annees = $db->query("SELECT DISTINCT annee_academique FROM memoires WHERE statut = 'valide' ORDER BY annee_academique DESC")->fetchAll(PDO::FETCH_COLUMN);

// Likés par l'utilisateur connecté
$likedIds = [];
if (isLoggedIn()) {
    $stmtL = $db->prepare("SELECT memoire_id FROM likes WHERE utilisateur_id = ?");
    $stmtL->execute([$_SESSION['user_id']]);
    $likedIds = $stmtL->fetchAll(PDO::FETCH_COLUMN);
}
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1 class="dashboard-title">Bibliothèque Numérique</h1>
        <p style="color: var(--gray-500);"><?= $total ?> mémoire(s) validé(s) disponible(s)</p>
    </div>

    <!-- Filtres de recherche -->
    <div class="card" style="margin-bottom: 1.5rem;">
        <div class="card-body">
            <form method="GET" action="" style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: flex-end;">
                <div class="form-group" style="flex: 2; min-width: 200px; margin-bottom: 0;">
                    <label for="q" style="font-size: 0.8rem;">Rechercher</label>
                    <input type="text" id="q" name="q" class="form-control" placeholder="Titre, auteur, mot-clé..." value="<?= sanitize($search) ?>">
                </div>
                <div class="form-group" style="flex: 1; min-width: 150px; margin-bottom: 0;">
                    <label for="filiere" style="font-size: 0.8rem;">Filière</label>
                    <select id="filiere" name="filiere" class="form-control">
                        <option value="">Toutes</option>
                        <?php foreach ($filieres as $f): ?>
                        <option value="<?= $f['id'] ?>" <?= $filiereId == $f['id'] ? 'selected' : '' ?>><?= sanitize($f['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="flex: 1; min-width: 120px; margin-bottom: 0;">
                    <label for="annee" style="font-size: 0.8rem;">Année</label>
                    <select id="annee" name="annee" class="form-control">
                        <option value="">Toutes</option>
                        <?php foreach ($annees as $a): ?>
                        <option value="<?= sanitize($a) ?>" <?= $annee === $a ? 'selected' : '' ?>><?= sanitize($a) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Rechercher</button>
                <a href="bibliotheque.php" class="btn btn-outline">Réinitialiser</a>
            </form>
        </div>
    </div>

    <!-- Résultats -->
    <?php if (empty($memoires)): ?>
    <div class="card">
        <div class="card-body" style="text-align: center; padding: 3rem;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">&#128269;</div>
            <h3 style="color: var(--gray-500);">Aucun mémoire trouvé</h3>
            <p style="color: var(--gray-400);">Essayez avec d'autres critères de recherche.</p>
        </div>
    </div>
    <?php else: ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.25rem;">
        <?php foreach ($memoires as $m): ?>
        <div class="card" style="transition: transform 0.2s;">
            <div class="card-body">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
                    <span style="font-size: 0.75rem; background: var(--primary); color: white; padding: 0.2rem 0.6rem; border-radius: 12px;">
                        <?= sanitize($m['filiere_code'] ?? '') ?>
                    </span>
                    <span style="font-size: 0.75rem; color: var(--gray-400);"><?= sanitize($m['annee_academique']) ?></span>
                </div>
                <h3 style="font-size: 1rem; margin-bottom: 0.5rem; line-height: 1.4;">
                    <a href="<?= $baseUrl ?>pages/lire.php?id=<?= $m['id'] ?>" style="color: var(--gray-800);"><?= sanitize(substr($m['titre'], 0, 80)) ?><?= strlen($m['titre']) > 80 ? '...' : '' ?></a>
                </h3>
                <p style="font-size: 0.85rem; color: var(--gray-500); margin-bottom: 0.75rem;">
                    <?= sanitize($m['etudiant_prenom'] . ' ' . $m['etudiant_nom']) ?>
                </p>
                <?php if (!empty($m['mot_cles'])): ?>
                <div style="margin-bottom: 0.75rem;">
                    <?php foreach (array_slice(explode(',', $m['mot_cles']), 0, 3) as $kc): ?>
                    <span style="font-size: 0.7rem; background: var(--gray-100); padding: 0.15rem 0.4rem; border-radius: 8px; margin-right: 0.2rem; display: inline-block; margin-bottom: 0.2rem;">
                        <?= sanitize(trim($kc)) ?>
                    </span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--gray-100); padding-top: 0.75rem; margin-top: 0.5rem;">
                    <div style="display: flex; gap: 1rem; font-size: 0.85rem;">
                        <button class="like-btn" data-id="<?= $m['id'] ?>" style="background: none; border: none; cursor: pointer; color: <?= in_array($m['id'], $likedIds) ? 'var(--danger)' : 'var(--gray-400)' ?>; font-size: 0.9rem;">
                            <?= in_array($m['id'], $likedIds) ? '&#9829;' : '&#9825;' ?> <span class="like-count"><?= $m['nb_likes'] ?></span>
                        </button>
                        <span style="color: var(--gray-400);">&#128172; <?= $m['nb_commentaires'] ?></span>
                    </div>
                    <a href="<?= $baseUrl ?>pages/lire.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-outline">Lire</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div style="display: flex; justify-content: center; gap: 0.3rem; margin-top: 2rem;">
        <?php if ($page > 1): ?>
        <a href="?q=<?= urlencode($search) ?>&filiere=<?= $filiereId ?>&annee=<?= urlencode($annee) ?>&page=<?= $page - 1 ?>" class="btn btn-sm btn-outline">&#8249; Précédent</a>
        <?php endif; ?>
        
        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
        <a href="?q=<?= urlencode($search) ?>&filiere=<?= $filiereId ?>&annee=<?= urlencode($annee) ?>&page=<?= $i ?>" 
           class="btn btn-sm <?= $i === $page ? 'btn-primary' : 'btn-outline' ?>"><?= $i ?></a>
        <?php endfor; ?>
        
        <?php if ($page < $totalPages): ?>
        <a href="?q=<?= urlencode($search) ?>&filiere=<?= $filiereId ?>&annee=<?= urlencode($annee) ?>&page=<?= $page + 1 ?>" class="btn btn-sm btn-outline">Suivant &#8250;</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<script>
document.querySelectorAll('.like-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const el = this;
        fetch('like.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'memoire_id=' + id
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const countEl = el.querySelector('.like-count');
                countEl.textContent = data.count;
                el.style.color = data.liked ? 'var(--danger)' : 'var(--gray-400)';
                el.innerHTML = (data.liked ? '&#9829;' : '&#9825;') + ' <span class="like-count">' + data.count + '</span>';
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
