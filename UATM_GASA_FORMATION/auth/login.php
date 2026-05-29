<?php
$pageTitle = 'Connexion - UATM GASA FORMATION';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/session.php';

// Si déjà connecté, rediriger
if (isLoggedIn()) {
    redirect($baseUrl . getDashboardUrl());
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de sécurité invalide.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Validation
        if (empty($email)) {
            $errors[] = 'L\'email est requis.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'L\'email n\'est pas valide.';
        }
        
        if (empty($password)) {
            $errors[] = 'Le mot de passe est requis.';
        }
        
        // Tentative de connexion
        if (empty($errors)) {
            if (loginUser($email, $password)) {
                redirect($baseUrl . getDashboardUrl());
            } else {
                $errors[] = 'Email ou mot de passe incorrect.';
            }
        }
    }
}
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1 class="auth-title">Connexion</h1>
            <p class="auth-subtitle">Accédez à votre espace UATM GASA FORMATION</p>
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
        
        <form method="POST" action="" class="auth-form" id="loginForm" novalidate>
            <?= csrfField() ?>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" 
                       value="<?= sanitize($email) ?>" required autofocus>
                <span class="form-error" id="emailError"></span>
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <div class="password-input">
                    <input type="password" id="password" name="password" class="form-control" required>
                    <button type="button" class="password-toggle" onclick="togglePassword('password')">
                        &#128065;
                    </button>
                </div>
                <span class="form-error" id="passwordError"></span>
            </div>
            
            <div class="form-group form-check">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember" value="1">
                    <span>Se souvenir de moi</span>
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Se connecter</button>
        </form>
        
        <div class="auth-footer">
            <p>Pas encore de compte ? <a href="register.php">Créer un compte</a></p>
            <p><a href="<?= $baseUrl ?>index.php">&larr; Retour a l'accueil</a></p>
        </div>
    </div>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', function(e) {
    let valid = true;
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    const emailError = document.getElementById('emailError');
    const passwordError = document.getElementById('passwordError');
    
    emailError.textContent = '';
    passwordError.textContent = '';
    
    if (!email.value.trim()) {
        emailError.textContent = 'L\'email est requis.';
        valid = false;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
        emailError.textContent = 'Email invalide.';
        valid = false;
    }
    
    if (!password.value) {
        passwordError.textContent = 'Le mot de passe est requis.';
        valid = false;
    }
    
    if (!valid) e.preventDefault();
});

function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    field.type = field.type === 'password' ? 'text' : 'password';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
