<?php
$pageTitle = 'Modifier Utilisateur - UATM GASA FORMATION';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../auth/session.php';

if (!isLoggedIn() || !hasRole('administrateur')) {
    redirect($baseUrl . 'auth/login.php');
}

$db = getDBConnection();
$userId = intval($_GET['id'] ?? 0);

if ($userId <= 0) {
    redirect('utilisateurs.php');
}

$stmt = $db->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    setFlash('error', 'Utilisateur non trouvé.');
    redirect('utilisateurs.php');
}

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
        if ($role_id <= 0) $errors[] = 'Rôle invalide.';

        // Email doublon
        $stmt = $db->prepare("SELECT id FROM utilisateurs WHERE email = ? AND id != ? LIMIT 1");
        $stmt->execute([$email, $userId]);
        if ($stmt->fetch()) $errors[] = 'Cet email est déjà utilisé par un autre compte.';

        if (empty($errors)) {
            if (!empty($password)) {
                if (strlen($password) < 8) {
                    $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
                } else {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE utilisateurs SET nom = ?, prenom = ?, email = ?, password = ?, role_id = ?, statut = ? WHERE id = ?");
                    $stmt->execute([$nom, $prenom, $email, $hashed, $role_id, $statut, $userId]);
                }
            } else {
                $stmt = $db->prepare("UPDATE utilisateurs SET nom = ?, prenom = ?, email = ?, role_id = ?, statut = ? WHERE id = ?");
                $stmt->execute([$nom, $prenom, $email, $role_id, $statut, $userId]);
            }

            if (empty($errors)) {
                logAction($_SESSION['user_id'], 'modifier', 'utilisateur', $userId, 'Modification: ' . $prenom . ' ' . $nom);
                
                // Notifier l'utilisateur modifié
                $notifMsg = 'Votre compte a été modifié par l\'administrateur.';
                $notifType = 'info';
                if ($statut === 'suspendu') {
                    $notifMsg = 'Votre compte a été suspendu. Contactez l\'administration.';
                    $notifType = 'warning';
                } elseif ($statut === 'inactif') {
                    $notifMsg = 'Votre compte a été désactivé.';
                    $notifType = 'error';
                }
                createNotification($userId, 'Mise à jour de votre compte', $notifMsg, $notifType);
                
                setFlash('success', 'Utilisateur modifié avec succès.');
                redirect('utilisateurs.php');
            }
        }
    }
}
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1 class="dashboard-title">Modifier l'utilisateur</h1>
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
            <form method="POST" action="" novalidate>
                <?= csrfField() ?>

                <div class="form-row">
                    <div class="form-group">
                        <label for="nom">Nom *</label>
                        <input type="text" id="nom" name="nom" class="form-control" value="<?= sanitize($_POST['nom'] ?? $user['nom']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="prenom">Prénom *</label>
                        <input type="text" id="prenom" name="prenom" class="form-control" value="<?= sanitize($_POST['prenom'] ?? $user['prenom']) ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?= sanitize($_POST['email'] ?? $user['email']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="password">Nouveau mot de passe (laisser vide pour conserver)</label>
                    <input type="password" id="password" name="password" class="form-control" minlength="8">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="role_id">Rôle *</label>
                        <select id="role_id" name="role_id" class="form-control" required>
                            <?php foreach ($roles as $r): ?>
                            <option value="<?= $r['id'] ?>" <?= (intval($_POST['role_id'] ?? $user['role_id']) == $r['id']) ? 'selected' : '' ?>>
                                <?= ucfirst(sanitize($r['nom'])) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="statut">Statut</label>
                        <select id="statut" name="statut" class="form-control">
                            <option value="actif" <?= ($_POST['statut'] ?? $user['statut']) === 'actif' ? 'selected' : '' ?>>Actif</option>
                            <option value="inactif" <?= ($_POST['statut'] ?? $user['statut']) === 'inactif' ? 'selected' : '' ?>>Inactif</option>
                            <option value="suspendu" <?= ($_POST['statut'] ?? $user['statut']) === 'suspendu' ? 'selected' : '' ?>>Suspendu</option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                    <a href="utilisateurs.php" class="btn btn-outline">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
