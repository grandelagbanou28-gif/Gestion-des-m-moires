<?php
$pageTitle = 'Modifier Mémoire - UATM GASA FORMATION';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/../controllers/upload.php';

if (!isLoggedIn() || !hasRole('etudiant')) {
    redirect($baseUrl . 'auth/login.php');
}

$db = getDBConnection();
$userId = $_SESSION['user_id'];
$memoireId = intval($_GET['id'] ?? 0);

if ($memoireId <= 0) {
    redirect('memoires.php');
}

// Récupérer le mémoire
$stmt = $db->prepare("SELECT * FROM memoires WHERE id = ? AND etudiant_id = ?");
$stmt->execute([$memoireId, $userId]);
$memoire = $stmt->fetch();

if (!$memoire) {
    setFlash('error', 'Mémoire non trouvé.');
    redirect('memoires.php');
}

// Seuls les mémoires brouillon ou rejetés sont modifiables
if (!in_array($memoire['statut'], ['brouillon', 'rejete'])) {
    setFlash('error', 'Ce mémoire ne peut plus être modifié.');
    redirect('memoires.php');
}

$filieres = $db->query("SELECT * FROM filieres WHERE statut = 'active' ORDER BY nom")->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de sécurité invalide.';
    } else {
        $titre = trim($_POST['titre'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $filiere_id = intval($_POST['filiere_id'] ?? 0);
        $annee_academique = trim($_POST['annee_academique'] ?? '');
        $mot_cles = trim($_POST['mot_cles'] ?? '');

        if (empty($titre)) $errors[] = 'Le titre est requis.';
        if (strlen($titre) < 5) $errors[] = 'Le titre doit contenir au moins 5 caractères.';
        if (empty($description)) $errors[] = 'La description est requise.';
        if ($filiere_id <= 0) $errors[] = 'Filière invalide.';
        if (empty($annee_academique)) $errors[] = 'L\'année académique est requise.';

        // Nouveau fichier optionnel
        $newFilename = $memoire['fichier_pdf'];
        $newSize = $memoire['taille_fichier'];

        if (isset($_FILES['fichier_pdf']) && $_FILES['fichier_pdf']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = handleMemoireUpload($_FILES['fichier_pdf'], $userId);
            if ($uploadResult['success']) {
                // Supprimer l'ancien fichier
                deleteUploadedFile($memoire['fichier_pdf']);
                $newFilename = $uploadResult['filename'];
                $newSize = $uploadResult['size'];
            } else {
                $errors[] = $uploadResult['message'];
            }
        }

        if (empty($errors)) {
            $stmt = $db->prepare("
                UPDATE memoires 
                SET titre = ?, description = ?, filiere_id = ?, annee_academique = ?, mot_cles = ?, 
                    fichier_pdf = ?, taille_fichier = ?, statut = 'soumis', updated_at = NOW()
                WHERE id = ? AND etudiant_id = ?
            ");
            $stmt->execute([$titre, $description, $filiere_id, $annee_academique, $mot_cles, $newFilename, $newSize, $memoireId, $userId]);

            logAction($userId, 'modifier', 'memoire', $memoireId, 'Modification mémoire: ' . $titre);

            setFlash('success', 'Mémoire modifié et resoumis avec succès !');
            redirect('memoires.php');
        }
    }
}
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1 class="dashboard-title">Modifier le Mémoire</h1>
        <a href="<?= $baseUrl ?>etudiant/voir.php?id=<?= $memoireId ?>" class="btn btn-outline btn-sm">&larr; Retour</a>
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

    <div class="card" style="max-width: 800px;">
        <div class="card-body">
            <form method="POST" action="" enctype="multipart/form-data" id="modifierForm" novalidate>
                <?= csrfField() ?>

                <div class="form-group">
                    <label for="titre">Titre du mémoire *</label>
                    <input type="text" id="titre" name="titre" class="form-control" 
                           value="<?= sanitize($_POST['titre'] ?? $memoire['titre']) ?>" required maxlength="500">
                    <span class="form-error" id="titreError"></span>
                </div>

                <div class="form-group">
                    <label for="description">Description / Résumé *</label>
                    <textarea id="description" name="description" class="form-control" rows="5" required><?= sanitize($_POST['description'] ?? $memoire['description']) ?></textarea>
                    <span class="form-error" id="descriptionError"></span>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="filiere_id">Filière *</label>
                        <select id="filiere_id" name="filiere_id" class="form-control" required>
                            <option value="">-- Sélectionner --</option>
                            <?php foreach ($filieres as $f): ?>
                            <option value="<?= $f['id'] ?>" <?= (intval($_POST['filiere_id'] ?? $memoire['filiere_id']) == $f['id']) ? 'selected' : '' ?>>
                                <?= sanitize($f['nom']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="form-error" id="filiereError"></span>
                    </div>

                    <div class="form-group">
                        <label for="annee_academique">Année académique *</label>
                        <input type="text" id="annee_academique" name="annee_academique" class="form-control" 
                               value="<?= sanitize($_POST['annee_academique'] ?? $memoire['annee_academique']) ?>" required>
                        <span class="form-error" id="anneeError"></span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="mot_cles">Mots-clés</label>
                    <input type="text" id="mot_cles" name="mot_cles" class="form-control" 
                           placeholder="Séparés par des virgules" value="<?= sanitize($_POST['mot_cles'] ?? $memoire['mot_cles']) ?>">
                </div>

                <div class="form-group">
                    <label for="fichier_pdf">Remplacer le fichier PDF (optionnel, max 10 Mo)</label>
                    <input type="file" id="fichier_pdf" name="fichier_pdf" class="form-control" accept=".pdf,application/pdf">
                    <span class="form-help">Fichier actuel: <?= sanitize($memoire['fichier_pdf']) ?></span>
                    <span class="form-error" id="fileError"></span>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary">Modifier et resoumettre</button>
                    <a href="<?= $baseUrl ?>etudiant/voir.php?id=<?= $memoireId ?>" class="btn btn-outline">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('modifierForm').addEventListener('submit', function(e) {
    let valid = true;
    document.querySelectorAll('.form-error').forEach(el => el.textContent = '');
    
    const titre = document.getElementById('titre');
    if (!titre.value.trim()) { document.getElementById('titreError').textContent = 'Le titre est requis.'; valid = false; }
    else if (titre.value.length < 5) { document.getElementById('titreError').textContent = 'Minimum 5 caractères.'; valid = false; }
    
    const desc = document.getElementById('description');
    if (!desc.value.trim()) { document.getElementById('descriptionError').textContent = 'La description est requise.'; valid = false; }
    
    const filiere = document.getElementById('filiere_id');
    if (!filiere.value) { document.getElementById('filiereError').textContent = 'Sélectionnez une filière.'; valid = false; }
    
    const annee = document.getElementById('annee_academique');
    if (!annee.value.trim()) { document.getElementById('anneeError').textContent = 'L\'année académique est requise.'; valid = false; }
    
    const fichier = document.getElementById('fichier_pdf');
    if (fichier.files.length) {
        if (fichier.files[0].size > 10 * 1024 * 1024) { document.getElementById('fileError').textContent = 'Le fichier dépasse 10 Mo.'; valid = false; }
        else if (fichier.files[0].type !== 'application/pdf') { document.getElementById('fileError').textContent = 'Seuls les PDF sont acceptés.'; valid = false; }
    }
    
    if (!valid) e.preventDefault();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
