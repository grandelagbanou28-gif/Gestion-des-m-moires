<?php
$pageTitle = 'Déposer un Mémoire - UATM GASA FORMATION';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/../controllers/upload.php';

if (!isLoggedIn() || !hasRole('etudiant')) {
    redirect($baseUrl . 'auth/login.php');
}

$db = getDBConnection();
$userId = $_SESSION['user_id'];
$errors = [];
$success = '';

// Récupérer les filières
$filieres = $db->query("SELECT * FROM filieres WHERE statut = 'active' ORDER BY nom")->fetchAll();

// Récupérer les professeurs
$professeurs = $db->query("SELECT id, nom, prenom FROM utilisateurs WHERE role_id = 3 AND statut = 'actif' ORDER BY nom, prenom")->fetchAll();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de securite invalide.';
    } else {
        $titre = trim($_POST['titre'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $filiere_id = intval($_POST['filiere_id'] ?? 0);
        $professeur_id = intval($_POST['professeur_id'] ?? 0);
        $annee_academique = trim($_POST['annee_academique'] ?? '');
        $mot_cles = trim($_POST['mot_cles'] ?? '');

        // Validation
        if (empty($titre)) $errors[] = 'Le titre est requis.';
        if (strlen($titre) < 5) $errors[] = 'Le titre doit contenir au moins 5 caracteres.';
        if (strlen($titre) > 500) $errors[] = 'Le titre ne doit pas depasser 500 caracteres.';
        if (empty($description)) $errors[] = 'La description est requise.';
        if ($filiere_id <= 0) $errors[] = 'Veuillez selectionner une filiere.';
        if ($professeur_id <= 0) $errors[] = 'Veuillez choisir un maitre de memoire.';
        if (empty($annee_academique)) $errors[] = 'L\'annee academique est requise.';

        // Vérifier le fichier
        if (!isset($_FILES['fichier_pdf']) || $_FILES['fichier_pdf']['error'] === UPLOAD_ERR_NO_FILE) {
            $errors[] = 'Le fichier PDF est requis.';
        }

        // Upload
        if (empty($errors)) {
            $uploadResult = handleMemoireUpload($_FILES['fichier_pdf'], $userId);
            
            if ($uploadResult['success']) {
                // Enregistrer en base
                $stmt = $db->prepare("
                    INSERT INTO memoires (etudiant_id, filiere_id, professeur_id, titre, description, fichier_pdf, taille_fichier, annee_academique, statut, mot_cles) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'soumis', ?)
                ");
                $stmt->execute([
                    $userId,
                    $filiere_id,
                    $professeur_id,
                    $titre,
                    $description,
                    $uploadResult['filename'],
                    $uploadResult['size'],
                    $annee_academique,
                    $mot_cles
                ]);

                $memoireId = $db->lastInsertId();

                // Journaliser
                logAction($userId, 'deposer', 'memoire', $memoireId, 'Depot memoire: ' . $titre);

                // Notifier le professeur choisi
                createNotification(
                    $professeur_id,
                    'Nouveau memoire soumis',
                    $_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom'] . ' vous a designe comme maitre de memoire pour "' . $titre . '".',
                    'info',
                    'professeur/voir.php?id=' . $memoireId
                );

                setFlash('success', 'Mémoire déposé avec succès !');
                redirect('memoires.php');
            } else {
                $errors[] = $uploadResult['message'];
            }
        }
    }
}
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1 class="dashboard-title">Déposer un Mémoire</h1>
        <a href="index.php" class="btn btn-outline btn-sm">← Retour</a>
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
            <form method="POST" action="" enctype="multipart/form-data" id="deposerForm" novalidate>
                <?= csrfField() ?>

                <div class="form-group">
                    <label for="titre">Titre du mémoire *</label>
                    <input type="text" id="titre" name="titre" class="form-control" 
                           value="<?= sanitize($_POST['titre'] ?? '') ?>" required maxlength="500">
                    <span class="form-error" id="titreError"></span>
                </div>

                <div class="form-group">
                    <label for="description">Description / Résumé *</label>
                    <textarea id="description" name="description" class="form-control" rows="5" required><?= sanitize($_POST['description'] ?? '') ?></textarea>
                    <span class="form-error" id="descriptionError"></span>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="filiere_id">Filiere *</label>
                        <select id="filiere_id" name="filiere_id" class="form-control" required>
                            <option value="">-- Selectionner --</option>
                            <?php foreach ($filieres as $f): ?>
                            <option value="<?= $f['id'] ?>" <?= (intval($_POST['filiere_id'] ?? 0) == $f['id']) ? 'selected' : '' ?>>
                                <?= sanitize($f['nom']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="form-error" id="filiereError"></span>
                    </div>

                    <div class="form-group">
                        <label for="professeur_id">Maitre de memoire *</label>
                        <select id="professeur_id" name="professeur_id" class="form-control" required>
                            <option value="">-- Selectionner --</option>
                            <?php foreach ($professeurs as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= (intval($_POST['professeur_id'] ?? 0) == $p['id']) ? 'selected' : '' ?>>
                                <?= sanitize($p['prenom'] . ' ' . $p['nom']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="form-error" id="professeurError"></span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="annee_academique">Annee academique *</label>
                    <input type="text" id="annee_academique" name="annee_academique" class="form-control" 
                           placeholder="ex: 2024-2025" value="<?= sanitize($_POST['annee_academique'] ?? '') ?>" required>
                    <span class="form-error" id="anneeError"></span>
                </div>

                <div class="form-group">
                    <label for="mot_cles">Mots-cles</label>
                    <input type="text" id="mot_cles" name="mot_cles" class="form-control" 
                           placeholder="Séparés par des virgules" value="<?= sanitize($_POST['mot_cles'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="fichier_pdf">Fichier PDF * (max 10 Mo)</label>
                    <input type="file" id="fichier_pdf" name="fichier_pdf" class="form-control" 
                           accept=".pdf,application/pdf" required>
                    <span class="form-help" id="fileInfo"></span>
                    <span class="form-error" id="fileError"></span>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary">Soumettre le mémoire</button>
                    <a href="index.php" class="btn btn-outline">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('deposerForm').addEventListener('submit', function(e) {
    let valid = true;
    
    // Reset erreurs
    document.querySelectorAll('.form-error').forEach(el => el.textContent = '');
    
    // Titre
    const titre = document.getElementById('titre');
    if (!titre.value.trim()) { document.getElementById('titreError').textContent = 'Le titre est requis.'; valid = false; }
    else if (titre.value.length < 5) { document.getElementById('titreError').textContent = 'Minimum 5 caractères.'; valid = false; }
    
    // Description
    const desc = document.getElementById('description');
    if (!desc.value.trim()) { document.getElementById('descriptionError').textContent = 'La description est requise.'; valid = false; }
    
    // Filiere
    const filiere = document.getElementById('filiere_id');
    if (!filiere.value) { document.getElementById('filiereError').textContent = 'Selectionnez une filiere.'; valid = false; }
    
    // Professeur
    const prof = document.getElementById('professeur_id');
    if (!prof.value) { document.getElementById('professeurError').textContent = 'Selectionnez un maitre de memoire.'; valid = false; }
    
    // Annee
    const annee = document.getElementById('annee_academique');
    if (!annee.value.trim()) { document.getElementById('anneeError').textContent = 'L\'année académique est requise.'; valid = false; }
    
    // Fichier
    const fichier = document.getElementById('fichier_pdf');
    if (!fichier.files.length) { document.getElementById('fileError').textContent = 'Le fichier PDF est requis.'; valid = false; }
    else if (fichier.files[0].size > 10 * 1024 * 1024) { document.getElementById('fileError').textContent = 'Le fichier dépasse 10 Mo.'; valid = false; }
    else if (fichier.files[0].type !== 'application/pdf') { document.getElementById('fileError').textContent = 'Seuls les PDF sont acceptés.'; valid = false; }
    
    if (!valid) e.preventDefault();
});

document.getElementById('fichier_pdf').addEventListener('change', function() {
    const info = document.getElementById('fileInfo');
    if (this.files.length) {
        const file = this.files[0];
        const size = (file.size / (1024 * 1024)).toFixed(2);
        info.textContent = file.name + ' (' + size + ' Mo)';
        info.style.color = file.type === 'application/pdf' && file.size <= 10 * 1024 * 1024 ? 'var(--success)' : 'var(--danger)';
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
