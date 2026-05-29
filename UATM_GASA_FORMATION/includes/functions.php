<?php
/**
 * Fonctions utilitaires - UATM GASA FORMATION
 */

// Démarrer la session de manière sécurisée
function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 3600,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
    }
}

// Vérifier si l'utilisateur est connecté
function isLoggedIn() {
    startSecureSession();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Vérifier le rôle de l'utilisateur
function hasRole($roles) {
    if (!isLoggedIn()) return false;
    if (!is_array($roles)) $roles = [$roles];
    return in_array($_SESSION['user_role'], $roles);
}

// Rediriger
function redirect($url) {
    header("Location: $url");
    exit();
}

// Protection XSS
function sanitize($data) {
    if ($data === null) return '';
    return htmlspecialchars(trim((string) $data), ENT_QUOTES, 'UTF-8');
}

// Générer un token CSRF
function generateCSRFToken() {
    startSecureSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Vérifier le token CSRF
function verifyCSRFToken($token) {
    startSecureSession();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Champ CSRF hidden
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
}

// Messages flash
function setFlash($type, $message) {
    startSecureSession();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    startSecureSession();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Formater la taille du fichier
function formatFileSize($bytes) {
    $bytes = intval($bytes ?? 0);
    if ($bytes >= 1073741824) {
        return round($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return round($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return round($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' B';
}

// Obtenir le nom du rôle
function getRoleName($roleId) {
    $roles = [1 => 'Administrateur', 2 => 'Directeur', 3 => 'Professeur', 4 => 'Étudiant'];
    return $roles[$roleId] ?? 'Inconnu';
}

// Obtenir la classe de statut
function getStatusClass($statut) {
    $classes = [
        'brouillon' => 'status-draft',
        'soumis' => 'status-submitted',
        'en_revision' => 'status-review',
        'valide' => 'status-approved',
        'rejete' => 'status-rejected',
        'archive' => 'status-archived'
    ];
    return $classes[$statut] ?? 'status-default';
}

// Obtenir le libellé du statut
function getStatusLabel($statut) {
    $labels = [
        'brouillon' => 'Brouillon',
        'soumis' => 'Soumis',
        'en_revision' => 'En révision',
        'valide' => 'Validé',
        'rejete' => 'Rejeté',
        'archive' => 'Archivé'
    ];
    return $labels[$statut] ?? $statut;
}

// Journaliser une action
function logAction($userId, $action, $entite, $entiteId = null, $details = null) {
    $db = getDBConnection();
    $stmt = $db->prepare("INSERT INTO historiques (utilisateur_id, action, entite, entite_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $action, $entite, $entiteId, $details, $_SERVER['REMOTE_ADDR'] ?? null]);
}

// Créer une notification
function createNotification($userId, $titre, $message, $type = 'info', $lien = null) {
    $db = getDBConnection();
    $stmt = $db->prepare("INSERT INTO notifications (utilisateur_id, titre, message, type, lien) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $titre, $message, $type, $lien]);
}

// Compter les notifications non lues
function countUnreadNotifications($userId) {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE utilisateur_id = ? AND lu = 0");
    $stmt->execute([$userId]);
    return $stmt->fetchColumn();
}

// Obtenir l'URL du dashboard selon le rôle
function getDashboardUrl() {
    if (!isLoggedIn()) return 'auth/login.php';
    
    $role = $_SESSION['user_role'] ?? '';
    switch ($role) {
        case 'administrateur': return 'admin/';
        case 'directeur': return 'dashboard/directeur.php';
        case 'professeur': return 'professeur/';
        case 'etudiant': return 'etudiant/';
        default: return 'index.php';
    }
}
