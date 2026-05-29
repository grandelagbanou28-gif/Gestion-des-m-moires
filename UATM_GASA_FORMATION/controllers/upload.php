<?php
/**
 * Contrôleur Upload - Gestion sécurisée des fichiers PDF
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();

define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10 Mo
define('ALLOWED_MIME', ['application/pdf']);
define('ALLOWED_EXT', ['pdf']);

function handleMemoireUpload($file, $userId) {
    // Vérifications de base
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'message' => 'Fichier invalide.'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE   => 'Le fichier dépasse la limite du serveur.',
            UPLOAD_ERR_FORM_SIZE  => 'Le fichier dépasse la taille maximale autorisée.',
            UPLOAD_ERR_PARTIAL    => 'Le fichier n\'a été que partiellement téléchargé.',
            UPLOAD_ERR_NO_FILE    => 'Aucun fichier n\'a été téléchargé.',
            UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant.',
            UPLOAD_ERR_CANT_WRITE => 'Erreur d\'écriture sur le disque.',
        ];
        return ['success' => false, 'message' => $errors[$file['error']] ?? 'Erreur inconnue.'];
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'Le fichier ne doit pas dépasser 10 Mo.'];
    }

    // Vérifier l'extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXT)) {
        return ['success' => false, 'message' => 'Seuls les fichiers PDF sont autorisés.'];
    }

    // Vérifier le MIME type réel
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $realMime = $finfo->file($file['tmp_name']);
    if (!in_array($realMime, ALLOWED_MIME)) {
        return ['success' => false, 'message' => 'Type de fichier non autorisé (MIME: ' . $realMime . ').'];
    }

    // Vérifier que c'est bien un PDF
    $header = file_get_contents($file['tmp_name'], false, null, 0, 4);
    if (substr($header, 0, 4) !== '%PDF') {
        return ['success' => false, 'message' => 'Le fichier n\'est pas un PDF valide.'];
    }

    // Générer un nom sécurisé
    $safeName = 'memoire_' . $userId . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    $destPath = UPLOAD_DIR . $safeName;

    // Déplacer le fichier
    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return ['success' => false, 'message' => 'Erreur lors de l\'enregistrement du fichier.'];
    }

    return [
        'success' => true,
        'message' => 'Fichier uploadé avec succès.',
        'filename' => $safeName,
        'original_name' => $file['name'],
        'size' => $file['size']
    ];
}

function deleteUploadedFile($filename) {
    $path = UPLOAD_DIR . basename($filename);
    if (file_exists($path)) {
        return unlink($path);
    }
    return false;
}
