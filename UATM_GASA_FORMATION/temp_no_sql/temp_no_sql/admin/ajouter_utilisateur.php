<?php
$pageTitle = 'Ajouter Utilisateur - UATM GASA FORMATION';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../auth/session.php';

if (!isLoggedIn() || !hasRole('administrateur')) {
    redirect($baseUrl . 'auth/login.php');
}

$db = getDBConnection();
$roles = $db->query("SELECT * FROM roles ORDER BY id")->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de sécurité invalide.';
    } else {
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role_id = intval($_POST['role_id'] ?? 0);
        $statut = $_POST['statut'] ?? 'actif';

        if (empty($nom)) $errors[] = 'Le nom est requis.';
        if (empty($prenom)) $errors[] = 'Le prénom est requis.';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalide.';
        if (strlen($password) < 8) $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
        if ($role_id <= 0) $errors[] = 'Rôle invalide.';
        if (!in_array($statut, ['actif', 'inactif', 'suspendu'])) $errors[] = 'Statut invalide.';

        // Vérifier email doublon
        $stmt = $db->prepare("SELECT id FROM utilisateurs WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) $errors[] = 'Cet email est déjà utilisé.';

        if (empty($errors)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("
                INSERT INTO utilisateurs (role_id, nom, prenom, email, password, statut) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$role_id, $nom, $prenom, $email, $hashedPassword, $statut]);

            logAction($_SESSION['user_id'], 'creer', 'utilisateur', $db->lastInsertId(), 'Création: ' . $prenom . ' ' . $nom);

            // Notifier le nouvel utilisateur
            createNotification(
                $db->lastInsertId(),
                'Bienvenue sur UATM GASA',
                'Votre compte a ete cree par l\'administrateur. Vous pouvez des maintenant vous connecter.',
                'success',
                'auth/login.php'
            );

            setFlash('success', 'Utilisateur créé avec succès.');
            redirect('utilisateurs.php');
        }
    }
}
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1 class="dashboard-title">Ajouter un utilisateur</h1>
        <a href="utilisateurs.php" class="btn btn-outline btn-sm">← Retour</a>
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
            <form method="POST" action="" id="formUtilisateur" novalidate>
                <?= csrfField() ?>

                <div class="form-row">
                    <div class="form-group">
                        <label for="nom">Nom *</label>
                        <input type="text" id="nom" name="nom" class="form-control" value="<?= sanitize($_POST['nom'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="prenom">Prénom *</label>
                        <input type="text" id="prenom" name="prenom" class="form-control" value="<?= sanitize($_POST['prenom'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?= sanitize($_POST['email'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe * (min 8 caractères)</label>
                    <input type="password" id="password" name="password" class="form-control" minlength="8" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="role_id">Rôle *</label>
                        <select id="role_id" name="role_id" class="form-control" required>
                            <?php foreach ($roles as $r): ?>
                            <option value="<?= $r['id'] ?>" <?= (intval($_POST['role_id'] ?? 0) == $r['id']) ? 'selected' : '' ?>>
                                <?= ucfirst(sanitize($r['nom'])) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="statut">Statut</label>
                        <select id="statut" name="statut" class="form-control">
                            <option value="actif" <?= ($_POST['statut'] ?? 'actif') === 'actif' ? 'selected' : '' ?>>Actif</option>
                            <option value="inactif" <?= ($_POST['statut'] ?? '') === 'inactif' ? 'selected' : '' ?>>Inactif</option>
                            <option value="suspendu" <?= ($_POST['statut'] ?? '') === 'suspendu' ? 'selected' : '' ?>>Suspendu</option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary">Créer l'utilisateur</button>
                    <a href="utilisateurs.php" class="btn btn-outline">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
