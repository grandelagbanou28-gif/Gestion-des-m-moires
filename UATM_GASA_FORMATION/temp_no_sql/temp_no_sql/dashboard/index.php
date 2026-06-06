<?php
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();

if (!isLoggedIn()) {
    header("Location: ../auth/login.php");
    exit();
}

$role = $_SESSION['user_role'] ?? '';
switch ($role) {
    case 'directeur':
        header("Location: directeur.php");
        break;
    case 'administrateur':
        header("Location: ../admin/");
        break;
    case 'professeur':
        header("Location: ../professeur/");
        break;
    case 'etudiant':
        header("Location: ../etudiant/");
        break;
    default:
        header("Location: ../index.php");
}
exit();
