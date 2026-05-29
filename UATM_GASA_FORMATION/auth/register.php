<?php
$pageTitle = 'Inscription - UATM GASA FORMATION';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/session.php';

if (isLoggedIn()) {
    redirect($baseUrl . getDashboardUrl());
}

$errors = [];
$formData = ['nom' => '', 'prenom' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de sécurité invalide.';
    } else {
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $role_id = intval($_POST['role_id'] ?? 4);
        
        $formData = compact('nom', 'prenom', 'email');
        
        // Validation
        if (empty($nom)) $errors[] = 'Le nom est requis.';
        if (strlen($nom) < 2) $errors[] = 'Le nom doit contenir au moins 2 caractères.';
        
        if (empty($prenom)) $errors[] = 'Le prénom est requis.';
        if (strlen($prenom) < 2) $errors[] = 'Le prénom doit contenir au moins 2 caractères.';
        
        if (empty($email)) {
            $errors[] = 'L\'email est requis.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'L\'email n\'est pas valide.';
        }
        
        if (empty($password)) {
            $errors[] = 'Le mot de passe est requis.';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
        }
        
        if ($password !== $password_confirm) {
            $errors[] = 'Les mots de passe ne correspondent pas.';
        }
        
        if (!in_array($role_id, [3, 4])) {
            $errors[] = 'Rôle invalide.';
        }
        
        if (empty($errors)) {
            $result = registerUser($nom, $prenom, $email, $password, $role_id);
            
            if ($result['success']) {
                setFlash('success', 'Compte créé avec succès. Vous pouvez maintenant vous connecter.');
                redirect('login.php');
            } else {
                $errors[] = $result['message'];
            }
        }
    }
}
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1 class="auth-title">Inscription</h1>
            <p class="auth-subtitle">Créez votre compte UATM GASA FORMATION</p>
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
        
        <form method="POST" action="" class="auth-form" id="registerForm" novalidate>
            <?= csrfField() ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="nom">Nom</label>
                    <input type="text" id="nom" name="nom" class="form-control" 
                           value="<?= sanitize($formData['nom']) ?>" required>
                    <span class="form-error" id="nomError"></span>
                </div>
                
                <div class="form-group">
                    <label for="prenom">Prénom</label>
                    <input type="text" id="prenom" name="prenom" class="form-control" 
                           value="<?= sanitize($formData['prenom']) ?>" required>
                    <span class="form-error" id="prenomError"></span>
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" 
                       value="<?= sanitize($formData['email']) ?>" required>
                <span class="form-error" id="emailError"></span>
            </div>
            
            <div class="form-group">
                <label for="role_id">Type de compte</label>
                <select id="role_id" name="role_id" class="form-control" required>
                    <option value="4" <?= ($role_id ?? 4) == 4 ? 'selected' : '' ?>>Étudiant</option>
                    <option value="3" <?= ($role_id ?? 0) == 3 ? 'selected' : '' ?>>Professeur</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <div class="password-input">
                    <input type="password" id="password" name="password" class="form-control" required>
                    <button type="button" class="password-toggle" onclick="togglePassword('password')">
                        &#128065;
                    </button>
                </div>
                <small class="form-help">Minimum 8 caractères</small>
                <span class="form-error" id="passwordError"></span>
            </div>
            
            <div class="form-group">
                <label for="password_confirm">Confirmer le mot de passe</label>
                <input type="password" id="password_confirm" name="password_confirm" class="form-control" required>
                <span class="form-error" id="passwordConfirmError"></span>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Créer mon compte</button>
        </form>
        
        <div class="auth-footer">
            <p>Déjà un compte ? <a href="login.php">Se connecter</a></p>
            <p><a href="<?= $baseUrl ?>index.php">&larr; Retour a l'accueil</a></p>
        </div>
    </div>
</div>

<script>
document.getElementById('registerForm').addEventListener('submit', function(e) {
    let valid = true;
    const fields = {
        nom: { el: document.getElementById('nom'), err: document.getElementById('nomError'), msg: 'Le nom est requis.' },
        prenom: { el: document.getElementById('prenom'), err: document.getElementById('prenomError'), msg: 'Le prénom est requis.' },
        email: { el: document.getElementById('email'), err: document.getElementById('emailError'), msg: 'Email invalide.' },
        password: { el: document.getElementById('password'), err: document.getElementById('passwordError'), msg: 'Minimum 8 caractères.' },
        password_confirm: { el: document.getElementById('password_confirm'), err: document.getElementById('passwordConfirmError'), msg: 'Les mots de passe ne correspondent pas.' }
    };
    
    Object.values(fields).forEach(f => f.err.textContent = '');
    
    if (!fields.nom.el.value.trim()) { fields.nom.err.textContent = fields.nom.msg; valid = false; }
    if (!fields.prenom.el.value.trim()) { fields.prenom.err.textContent = fields.prenom.msg; valid = false; }
    if (!fields.email.el.value.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(fields.email.el.value)) { fields.email.err.textContent = fields.email.msg; valid = false; }
    if (fields.password.el.value.length < 8) { fields.password.err.textContent = fields.password.msg; valid = false; }
    if (fields.password.el.value !== fields.password_confirm.el.value) { fields.password_confirm.err.textContent = fields.password_confirm.msg; valid = false; }
    
    if (!valid) e.preventDefault();
});

function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    field.type = field.type === 'password' ? 'text' : 'password';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
