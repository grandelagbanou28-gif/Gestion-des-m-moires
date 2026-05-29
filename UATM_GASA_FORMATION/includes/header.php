<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
startSecureSession();

// Detecter le projet racine et construire le bon base URL
$projectDir = 'UATM_GASA_FORMATION';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/';

// Trouver la position du projet dans l'URI
$pos = strpos($scriptName, $projectDir);
if ($pos !== false) {
    $baseUrl = substr($scriptName, 0, $pos + strlen($projectDir)) . '/';
} else {
    $baseUrl = '/';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="UATM GASA FORMATION - Gestion des mémoires universitaires">
    <title><?= $pageTitle ?? 'UATM GASA FORMATION' ?></title>
    <link rel="stylesheet" href="<?= $baseUrl ?>assets/css/style.css?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/projetGenieLogiciel/UATM_GASA_FORMATION/assets/css/style.css') ?>">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="<?= $baseUrl ?>index.php" class="nav-brand">
                <img src="<?= $baseUrl ?>assets/images/image.webp" alt="UATM GASA" class="nav-logo">
                <span class="brand-text">UATM GASA</span>
            </a>
            <button class="nav-toggle" id="navToggle" aria-label="Menu">
                <span></span><span></span><span></span>
            </button>
            <div class="nav-menu" id="navMenu">
                <a href="<?= $baseUrl ?>index.php" class="nav-link">Accueil</a>
                <a href="<?= $baseUrl ?>pages/bibliotheque.php" class="nav-link">Bibliothèque</a>
                <?php if (isLoggedIn()): ?>
                    <a href="<?= $baseUrl ?><?= getDashboardUrl() ?>" class="nav-link">Dashboard</a>
                    <div class="nav-dropdown">
                        <button class="nav-link nav-dropdown-toggle">
                            <?php if (!empty($_SESSION['user_avatar']) && file_exists(dirname(__DIR__) . '/assets/uploads/' . $_SESSION['user_avatar'])): ?>
                            <img src="<?= $baseUrl ?>assets/uploads/<?= sanitize($_SESSION['user_avatar']) ?>" alt="Avatar" style="width: 28px; height: 28px; border-radius: 50%; object-fit: cover; margin-right: 0.3rem; vertical-align: middle;">
                            <?php endif; ?>
                            <?= sanitize($_SESSION['user_prenom'] ?? 'User') ?>
                            <?php $unread = countUnreadNotifications($_SESSION['user_id']); ?>
                            <?php if ($unread > 0): ?>
                                <span class="badge"><?= $unread ?></span>
                            <?php endif; ?>
                        </button>
                        <div class="nav-dropdown-menu">
                            <a href="<?= $baseUrl ?>pages/profil.php" class="dropdown-item">Mon Profil</a>
                            <a href="<?= $baseUrl ?>pages/notifications.php" class="dropdown-item">
                                Notifications
                                <?php if ($unread > 0): ?>
                                    <span class="badge"><?= $unread ?></span>
                                <?php endif; ?>
                            </a>
                            <?php if (hasRole(['administrateur'])): ?>
                            <a href="<?= $baseUrl ?>pages/rapports.php" class="dropdown-item">Rapports</a>
                            <?php endif; ?>
                            <hr class="dropdown-divider">
                            <a href="<?= $baseUrl ?>auth/logout.php" class="dropdown-item text-danger">Déconnexion</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?= $baseUrl ?>auth/login.php" class="nav-link">Connexion</a>
                    <a href="<?= $baseUrl ?>auth/register.php" class="btn btn-primary btn-sm">Inscription</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <?php $flash = getFlash(); if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible">
        <?= sanitize($flash['message']) ?>
        <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
    <?php endif; ?>
    <main class="main-content">
