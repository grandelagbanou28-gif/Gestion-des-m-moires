<?php
$pageTitle = 'Gestion Utilisateurs - UATM GASA FORMATION';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../auth/session.php';

if (!isLoggedIn() || !hasRole('administrateur')) {
    redirect($baseUrl . 'auth/login.php');
}

$db = getDBConnection();

$filtreRole = intval($_GET['role'] ?? 0);
$search = trim($_GET['q'] ?? '');

$sql = "SELECT u.*, r.nom as role_nom FROM utilisateurs u JOIN roles r ON u.role_id = r.id WHERE 1=1";
$params = [];

if ($filtreRole > 0) {
    $sql .= " AND u.role_id = ?";
    $params[] = $filtreRole;
}

if (!empty($search)) {
    $sql .= " AND (u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY u.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$utilisateurs = $stmt->fetchAll();

$roles = $db->query("SELECT * FROM roles ORDER BY id")->fetchAll();
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1 class="dashboard-title">Gestion des utilisateurs</h1>
        <a href="ajouter_utilisateur.php" class="btn btn-primary">+ Ajouter un utilisateur</a>
    </div>

    <!-- Filtres -->
    <div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; flex-wrap: wrap; align-items: center;">
        <form method="GET" action="" style="display: flex; gap: 0.5rem;">
            <select name="role" class="form-control" style="width: 180px;">
                <option value="0">Tous les rôles</option>
                <?php foreach ($roles as $r): ?>
                <option value="<?= $r['id'] ?>" <?= $filtreRole == $r['id'] ? 'selected' : '' ?>><?= ucfirst(sanitize($r['nom'])) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="q" placeholder="Rechercher..." value="<?= sanitize($search) ?>" class="form-control" style="width: 220px;">
            <button type="submit" class="btn btn-outline btn-sm">Filtrer</button>
        </form>
    </div>

    <div class="table-container">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nom complet</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th>Dernière connexion</th>
                        <th>Inscrit le</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($utilisateurs)): ?>
                    <tr><td colspan="8" style="text-align: center; color: var(--gray-400); padding: 3rem;">
                        Aucun utilisateur trouvé.
                    </td></tr>
                    <?php else: ?>
                    <?php foreach ($utilisateurs as $i => $u): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= sanitize($u['prenom'] . ' ' . $u['nom']) ?></strong></td>
                        <td><?= sanitize($u['email']) ?></td>
                        <td><span style="font-size: 0.8rem; background: var(--gray-100); padding: 0.2rem 0.5rem; border-radius: 12px; text-transform: capitalize;"><?= sanitize($u['role_nom']) ?></span></td>
                        <td>
                            <span class="status-badge <?= $u['statut'] === 'actif' ? 'status-approved' : ($u['statut'] === 'suspendu' ? 'status-rejected' : 'status-draft') ?>">
                                <?= ucfirst(sanitize($u['statut'])) ?>
                            </span>
                        </td>
                        <td style="font-size: 0.85rem; color: var(--gray-400);">
                            <?= $u['derniere_connexion'] ? date('d/m/Y H:i', strtotime($u['derniere_connexion'])) : 'Jamais' ?>
                        </td>
                        <td style="font-size: 0.85rem;"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                        <td>
                            <div style="display: flex; gap: 0.3rem;">
                                <a href="modifier_utilisateur.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline">Modifier</a>
                                <a href="supprimer_utilisateur.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-danger" data-confirm="Supprimer cet utilisateur ?">Supprimer</a>
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
