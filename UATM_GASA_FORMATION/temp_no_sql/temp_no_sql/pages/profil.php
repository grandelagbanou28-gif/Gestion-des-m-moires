<?php
$pageTitle = 'Mon Profil - UATM GASA FORMATION';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../auth/session.php';

if (!isLoggedIn()) {
    redirect($baseUrl . 'auth/login.php');
}

$db = getDBConnection();
$userId = $_SESSION['user_id'];

$stmt = $db->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Stats personnelles
$stats = [];
if (hasRole('etudiant')) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM memoires WHERE etudiant_id = ?");
    $stmt->execute([$userId]);
    $stats['memoires'] = $stmt->fetchColumn();
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM memoires WHERE etudiant_id = ? AND statut = 'valide'");
    $stmt->execute([$userId]);
    $stats['valides'] = $stmt->fetchColumn();
}

if (hasRole('professeur')) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM memoires WHERE professeur_id = ?");
    $stmt->execute([$userId]);
    $stats['assignes'] = $stmt->fetchColumn();
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM validations WHERE validateur_id = ?");
    $stmt->execute([$userId]);
    $stats['validations'] = $stmt->fetchColumn();
}

$errors = [];
$passwordErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de sécurité invalide.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_profile') {
            $nom = trim($_POST['nom'] ?? '');
            $prenom = trim($_POST['prenom'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $telephone = trim($_POST['telephone'] ?? '');
            $niveau = trim($_POST['niveau'] ?? '');

            if (empty($nom)) $errors[] = 'Le nom est requis.';
            if (empty($prenom)) $errors[] = 'Le prenom est requis.';
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalide.';

            // Verifier email doublon
            $stmt = $db->prepare("SELECT id FROM utilisateurs WHERE email = ? AND id != ? LIMIT 1");
            $stmt->execute([$email, $userId]);
            if ($stmt->fetch()) $errors[] = 'Cet email est deja utilise.';

            if (empty($errors)) {
                $stmt = $db->prepare("UPDATE utilisateurs SET nom = ?, prenom = ?, email = ?, telephone = ?, niveau = ? WHERE id = ?");
                $stmt->execute([$nom, $prenom, $email, $telephone, $niveau, $userId]);
                
                $_SESSION['user_nom'] = $nom;
                $_SESSION['user_prenom'] = $prenom;
                $_SESSION['user_email'] = $email;

                setFlash('success', 'Profil mis a jour avec succes.');
                redirect('profil.php');
            }
        }
        
        if ($action === 'change_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($currentPassword)) $passwordErrors[] = 'Le mot de passe actuel est requis.';
            if (strlen($newPassword) < 8) $passwordErrors[] = 'Le nouveau mot de passe doit contenir au moins 8 caractères.';
            if ($newPassword !== $confirmPassword) $passwordErrors[] = 'Les mots de passe ne correspondent pas.';

            if (empty($passwordErrors)) {
                if (!password_verify($currentPassword, $user['password'])) {
                    $passwordErrors[] = 'Le mot de passe actuel est incorrect.';
                } else {
                    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE utilisateurs SET password = ? WHERE id = ?");
                    $stmt->execute([$hashed, $userId]);
                    
                    logAction($userId, 'modifier_mot_de_passe', 'utilisateur', $userId);
                    
                    setFlash('success', 'Mot de passe modifié avec succès.');
                    redirect('profil.php');
                }
            }
        }
    }
    
    // Upload photo de profil
    if ($action === 'upload_photo') {
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Erreur lors du telechargement de la photo.';
        } else {
            $file = $_FILES['avatar'];
            $maxSize = 2 * 1024 * 1024; // 2 Mo
            
            if ($file['size'] > $maxSize) {
                $errors[] = 'La photo ne doit pas depasser 2 Mo.';
            } else {
                $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $realMime = $finfo->file($file['tmp_name']);
                
                if (!in_array($realMime, $allowedTypes)) {
                    $errors[] = 'Seuls les fichiers JPG et PNG sont autorises.';
                } else {
                    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $safeName = 'avatar_' . $userId . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
                    $destPath = __DIR__ . '/../assets/uploads/' . $safeName;
                    
                    // Supprimer l'ancienne photo
                    if (!empty($user['avatar'])) {
                        $oldPath = __DIR__ . '/../assets/uploads/' . $user['avatar'];
                        if (file_exists($oldPath)) unlink($oldPath);
                    }
                    
                    if (move_uploaded_file($file['tmp_name'], $destPath)) {
                        $stmt = $db->prepare("UPDATE utilisateurs SET avatar = ? WHERE id = ?");
                        $stmt->execute([$safeName, $userId]);
                        $_SESSION['user_avatar'] = $safeName;
                        
                        logAction($userId, 'modifier', 'utilisateur', $userId, 'Photo de profil mise a jour');
                        setFlash('success', 'Photo de profil mise a jour.');
                        redirect('profil.php');
                    } else {
                        $errors[] = 'Erreur lors de l\'enregistrement de la photo.';
                    }
                }
            }
        }
    }
    
    // Supprimer photo de profil
    if ($action === 'delete_photo') {
        if (!empty($user['avatar'])) {
            $path = __DIR__ . '/../assets/uploads/' . $user['avatar'];
            if (file_exists($path)) unlink($path);
            
            $stmt = $db->prepare("UPDATE utilisateurs SET avatar = NULL WHERE id = ?");
            $stmt->execute([$userId]);
            $_SESSION['user_avatar'] = null;
            
            logAction($userId, 'modifier', 'utilisateur', $userId, 'Photo de profil supprimee');
            setFlash('success', 'Photo de profil supprimee.');
        }
        redirect('profil.php');
    }
}
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1 class="dashboard-title">Mon Profil</h1>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
        <!-- Infos personnelles -->
        <div>
            <div class="card" style="margin-bottom: 1.5rem;">
                <div class="card-header">
                    <h2 style="font-size: 1.1rem; color: var(--primary);">Informations personnelles</h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?= sanitize($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <form method="POST" action="" novalidate>
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="update_profile">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="nom">Nom</label>
                                <input type="text" id="nom" name="nom" class="form-control" value="<?= sanitize($_POST['nom'] ?? $user['nom']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="prenom">Prénom</label>
                                <input type="text" id="prenom" name="prenom" class="form-control" value="<?= sanitize($_POST['prenom'] ?? $user['prenom']) ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?= sanitize($_POST['email'] ?? $user['email']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="telephone">Telephone</label>
                            <input type="tel" id="telephone" name="telephone" class="form-control" value="<?= sanitize($_POST['telephone'] ?? $user['telephone']) ?>">
                        </div>

                        <?php if (hasRole('etudiant')): ?>
                        <div class="form-group">
                            <label for="niveau">Niveau d'etudes</label>
                            <select id="niveau" name="niveau" class="form-control">
                                <option value="">-- Selectionner --</option>
                                <option value="L1" <?= ($user['niveau'] ?? '') == 'L1' ? 'selected' : '' ?>>Licence 1 (L1)</option>
                                <option value="L2" <?= ($user['niveau'] ?? '') == 'L2' ? 'selected' : '' ?>>Licence 2 (L2)</option>
                                <option value="L3" <?= ($user['niveau'] ?? '') == 'L3' ? 'selected' : '' ?>>Licence 3 (L3)</option>
                                <option value="M1" <?= ($user['niveau'] ?? '') == 'M1' ? 'selected' : '' ?>>Master 1 (M1)</option>
                                <option value="M2" <?= ($user['niveau'] ?? '') == 'M2' ? 'selected' : '' ?>>Master 2 (M2)</option>
                            </select>
                            <span class="form-help">Mettez a jour votre niveau au debut de chaque annee.</span>
                        </div>
                        <?php endif; ?>

                        <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                    </form>
                </div>
            </div>

            <!-- Changement mot de passe -->
            <div class="card">
                <div class="card-header">
                    <h2 style="font-size: 1.1rem; color: var(--primary);">Changer le mot de passe</h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($passwordErrors)): ?>
                    <div class="alert alert-error">
                        <ul>
                            <?php foreach ($passwordErrors as $error): ?>
                                <li><?= sanitize($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <form method="POST" action="" novalidate>
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="change_password">

                        <div class="form-group">
                            <label for="current_password">Mot de passe actuel</label>
                            <input type="password" id="current_password" name="current_password" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="new_password">Nouveau mot de passe</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" minlength="8" required>
                            <span class="form-help">Minimum 8 caractères</span>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirmer le mot de passe</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-secondary">Changer le mot de passe</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div>
            <!-- Avatar / Infos -->
            <div class="card" style="margin-bottom: 1.5rem;">
                <div class="card-body" style="text-align: center;">
                    <?php if (!empty($user['avatar']) && file_exists(__DIR__ . '/../assets/uploads/' . $user['avatar'])): ?>
                    <img src="<?= $baseUrl ?>assets/uploads/<?= sanitize($user['avatar']) ?>" 
                         alt="Photo de profil" 
                         style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid var(--primary); margin-bottom: 1rem;">
                    <?php else: ?>
                    <div style="width: 100px; height: 100px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; font-weight: 700; margin: 0 auto 1rem;">
                        <?= strtoupper(substr($user['prenom'], 0, 1) . substr($user['nom'], 0, 1)) ?>
                    </div>
                    <?php endif; ?>
                    <h3 style="margin-bottom: 0.25rem;"><?= sanitize($user['prenom'] . ' ' . $user['nom']) ?></h3>
                    <p style="color: var(--gray-500); font-size: 0.9rem; margin-bottom: 0.5rem;"><?= sanitize($user['email']) ?></p>
                    <span style="background: var(--gray-100); padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.8rem; text-transform: capitalize;"><?= getRoleName($user['role_id']) ?></span>
                </div>
            </div>

            <!-- Photo de profil -->
            <div class="card" style="margin-bottom: 1.5rem;">
                <div class="card-header">
                    <h2 style="font-size: 1rem; color: var(--primary);">Photo de profil</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="upload_photo">
                        <div class="form-group">
                            <label for="avatar">Choisir une photo (JPG, PNG, max 2Mo)</label>
                            <input type="file" id="avatar" name="avatar" class="form-control" accept="image/jpeg,image/png,image/jpg" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block btn-sm">Mettre a jour la photo</button>
                    </form>
                    <?php if (!empty($user['avatar'])): ?>
                    <form method="POST" action="" style="margin-top: 0.5rem;">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="delete_photo">
                        <button type="submit" class="btn btn-danger btn-block btn-sm" data-confirm="Supprimer la photo ?">Supprimer la photo</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stats -->
            <?php if (!empty($stats)): ?>
            <div class="card">
                <div class="card-header">
                    <h2 style="font-size: 1.1rem; color: var(--primary);">Statistiques</h2>
                </div>
                <div class="card-body">
                    <?php foreach ($stats as $key => $val): ?>
                    <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--gray-100);">
                        <span style="text-transform: capitalize;"><?= str_replace('_', ' ', $key) ?></span>
                        <strong><?= $val ?></strong>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Info compte -->
            <div class="card" style="margin-top: 1.5rem;">
                <div class="card-body" style="font-size: 0.85rem; color: var(--gray-500);">
                    <p><strong>Inscrit le :</strong> <?= date('d/m/Y', strtotime($user['created_at'])) ?></p>
                    <p><strong>Dernière connexion :</strong> <?= $user['derniere_connexion'] ? date('d/m/Y H:i', strtotime($user['derniere_connexion'])) : 'Jamais' ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
