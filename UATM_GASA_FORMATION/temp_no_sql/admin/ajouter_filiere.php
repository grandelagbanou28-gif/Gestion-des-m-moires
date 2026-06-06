<?php
$pageTitle = 'Ajouter Filière - UATM GASA FORMATION';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../auth/session.php';

if (!isLoggedIn() || !hasRole('administrateur')) {
    redirect($baseUrl . 'auth/login.php');
}

$db = getDBConnection();
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
        if (strlen($code) > 20) $errors[] = 'Le code ne doit pas dépasser 20 caractères.';
        if (!in_array($statut, ['active', 'inactive'])) $errors[] = 'Statut invalide.';

        // Code doublon
        $stmt = $db->prepare("SELECT id FROM filieres WHERE code = ? LIMIT 1");
        $stmt->execute([$code]);
        if ($stmt->fetch()) $errors[] = 'Ce code de filière existe déjà.';

        if (empty($errors)) {
            $stmt = $db->prepare("INSERT INTO filieres (nom, code, description, statut) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nom, $code, $description, $statut]);

            logAction($_SESSION['user_id'], 'creer', 'filiere', $db->lastInsertId(), 'Création: ' . $nom);

            // Notifier les professeurs
            $profs = $db->prepare("SELECT id FROM utilisateurs WHERE role_id = 3 AND statut = 'actif'");
            $profs->execute();
            while ($prof = $profs->fetch()) {
                createNotification(
                    $prof['id'],
                    'Nouvelle filiere ajoutee',
                    'La filiere "' . $nom . '" a ete creee par l\'administrateur.',
                    'info',
                    'pages/bibliotheque.php'
                );
            }

            setFlash('success', 'Filière créée avec succès.');
            redirect('filieres.php');
        }
    }
}
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1 class="dashboard-title">Ajouter une filière</h1>
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
                    <input type="text" id="nom" name="nom" class="form-control" value="<?= sanitize($_POST['nom'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="code">Code *</label>
                    <input type="text" id="code" name="code" class="form-control" value="<?= sanitize($_POST['code'] ?? '') ?>" maxlength="20" required style="text-transform: uppercase;">
                    <span class="form-help">Ex: INFO, GEST, DROI</span>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="3"><?= sanitize($_POST['description'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="statut">Statut</label>
                    <select id="statut" name="statut" class="form-control">
                        <option value="active" <?= ($_POST['statut'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($_POST['statut'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary">Créer la filière</button>
                    <a href="filieres.php" class="btn btn-outline">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
