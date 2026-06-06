<?php
$pageTitle = 'Modifier Filière - UATM GASA FORMATION';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../auth/session.php';

if (!isLoggedIn() || !hasRole('administrateur')) {
    redirect($baseUrl . 'auth/login.php');
}

$db = getDBConnection();
$filiereId = intval($_GET['id'] ?? 0);

if ($filiereId <= 0) {
    redirect('filieres.php');
}

$stmt = $db->prepare("SELECT * FROM filieres WHERE id = ?");
$stmt->execute([$filiereId]);
$filiere = $stmt->fetch();

if (!$filiere) {
    setFlash('error', 'Filière non trouvée.');
    redirect('filieres.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de sécurité invalide.';
    } else {
        $nom = trim($_POST['nom'] ?? '');
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $description = trim($_POST['description'] ?? '');
        $statut = $_POST['statut'] ?? 'active';

        if (empty($nom)) $errors[] = 'Le nom est requis.';
        if (empty($code)) $errors[] = 'Le code est requis.';

        // Code doublon
        $stmt = $db->prepare("SELECT id FROM filieres WHERE code = ? AND id != ? LIMIT 1");
        $stmt->execute([$code, $filiereId]);
        if ($stmt->fetch()) $errors[] = 'Ce code existe déjà pour une autre filière.';

        if (empty($errors)) {
            $stmt = $db->prepare("UPDATE filieres SET nom = ?, code = ?, description = ?, statut = ? WHERE id = ?");
            $stmt->execute([$nom, $code, $description, $statut, $filiereId]);

            logAction($_SESSION['user_id'], 'modifier', 'filiere', $filiereId, 'Modification: ' . $nom);

            setFlash('success', 'Filière modifiée avec succès.');
            redirect('filieres.php');
        }
    }
}
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1 class="dashboard-title">Modifier la filière</h1>
        <a href="filieres.php" class="btn btn-outline btn-sm">← Retour</a>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= sanitize($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="card" style="max-width: 600px;">
        <div class="card-body">
            <form method="POST" action="" novalidate>
                <?= csrfField() ?>

                <div class="form-group">
                    <label for="nom">Nom de la filière *</label>
                    <input type="text" id="nom" name="nom" class="form-control" value="<?= sanitize($_POST['nom'] ?? $filiere['nom']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="code">Code *</label>
                    <input type="text" id="code" name="code" class="form-control" value="<?= sanitize($_POST['code'] ?? $filiere['code']) ?>" maxlength="20" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="3"><?= sanitize($_POST['description'] ?? $filiere['description']) ?></textarea>
                </div>

                <div class="form-group">
                    <label for="statut">Statut</label>
                    <select id="statut" name="statut" class="form-control">
                        <option value="active" <?= ($_POST['statut'] ?? $filiere['statut']) === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($_POST['statut'] ?? $filiere['statut']) === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                    <a href="filieres.php" class="btn btn-outline">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
