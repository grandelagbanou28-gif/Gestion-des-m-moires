<?php
/**
 * Gestion de la session utilisateur
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();

function loginUser($email, $password) {
    $db = getDBConnection();
    
    $stmt = $db->prepare("
        SELECT u.*, r.nom as role_nom 
        FROM utilisateurs u 
        JOIN roles r ON u.role_id = r.id 
        WHERE u.email = ? AND u.statut = 'actif' 
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        // Mettre à jour la dernière connexion
        $update = $db->prepare("UPDATE utilisateurs SET derniere_connexion = NOW() WHERE id = ?");
        $update->execute([$user['id']]);
        
        // Régénérer la session
        session_regenerate_id(true);
        
        // Stocker les données en session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nom'] = $user['nom'];
        $_SESSION['user_prenom'] = $user['prenom'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role_nom'];
        $_SESSION['user_role_id'] = $user['role_id'];
        $_SESSION['user_avatar'] = $user['avatar'];
        
        // Journaliser
        logAction($user['id'], 'connexion', 'utilisateur', $user['id']);
        
        return true;
    }
    
    return false;
}

function registerUser($nom, $prenom, $email, $password, $role_id = 4) {
    $db = getDBConnection();
    
    // Vérifier si l'email existe déjà
    $stmt = $db->prepare("SELECT id FROM utilisateurs WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Cet email est déjà utilisé.'];
    }
    
    // Hasher le mot de passe
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insérer l'utilisateur
    $stmt = $db->prepare("
        INSERT INTO utilisateurs (role_id, nom, prenom, email, password, statut) 
        VALUES (?, ?, ?, ?, ?, 'actif')
    ");
    $stmt->execute([$role_id, $nom, $prenom, $email, $hashedPassword]);
    
    $userId = $db->lastInsertId();
    
    // Journaliser
    logAction($userId, 'inscription', 'utilisateur', $userId);
    
    // Notifier les administrateurs
    $admins = $db->prepare("SELECT id FROM utilisateurs WHERE role_id = 1 AND statut = 'actif'");
    $admins->execute();
    while ($admin = $admins->fetch()) {
        createNotification(
            $admin['id'],
            'Nouvel utilisateur inscrit',
            $prenom . ' ' . $nom . ' s\'est inscrit en tant que ' . getRoleName($role_id) . '.',
            'info',
            'admin/utilisateurs.php'
        );
    }
    
    return ['success' => true, 'message' => 'Compte créé avec succès.', 'user_id' => $userId];
}

function logoutUser() {
    if (isLoggedIn()) {
        logAction($_SESSION['user_id'], 'deconnexion', 'utilisateur', $_SESSION['user_id']);
    }
    session_unset();
    session_destroy();
    header("Location: " . dirname($_SERVER['SCRIPT_NAME']) . "/login.php");
    exit();
}
