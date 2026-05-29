<?php
$pageTitle = 'Lecture Memoire - UATM GASA FORMATION';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../auth/session.php';

if (!isLoggedIn()) {
    redirect($baseUrl . 'auth/login.php');
}

$db = getDBConnection();
$memoireId = intval($_GET['id'] ?? 0);

if ($memoireId <= 0) {
    redirect($baseUrl . 'pages/bibliotheque.php');
}

$stmt = $db->prepare("
    SELECT m.*, f.nom as filiere_nom, u.nom as etudiant_nom, u.prenom as etudiant_prenom
    FROM memoires m 
    LEFT JOIN filieres f ON m.filiere_id = f.id 
    LEFT JOIN utilisateurs u ON m.etudiant_id = u.id
    WHERE m.id = ? AND m.statut = 'valide'
");
$stmt->execute([$memoireId]);
$memoire = $stmt->fetch();

if (!$memoire) {
    setFlash('error', 'Memoire non trouve ou non valide.');
    redirect($baseUrl . 'pages/bibliotheque.php');
}

$fichierPath = $baseUrl . 'assets/uploads/' . $memoire['fichier_pdf'];

// Likes
$userId = $_SESSION['user_id'];
$stmt = $db->prepare("SELECT COUNT(*) FROM likes WHERE memoire_id = ?");
$stmt->execute([$memoireId]);
$nbLikes = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT id FROM likes WHERE memoire_id = ? AND utilisateur_id = ?");
$stmt->execute([$memoireId, $userId]);
$hasLiked = (bool) $stmt->fetch();
?>

<style>
body {
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
    overflow: hidden;
}
.no-screenshot {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 9999;
    pointer-events: none;
}
</style>

<div class="no-screenshot" id="noScreenshot"></div>

<div style="display: flex; height: calc(100vh - 64px); overflow: hidden;">
    <!-- PDF Viewer -->
    <div style="flex: 1; position: relative; background: #525659;">
        <iframe src="<?= $fichierPath ?>" 
                style="width: 100%; height: 100%; border: none;"
                id="pdfFrame"
                sandbox="allow-same-origin allow-scripts"
                allowfullscreen>
        </iframe>
        
        <!-- Overlay invisible pour bloquer clic droit -->
        <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 10;" 
             oncontextmenu="return false;" 
             onmousedown="return false;">
        </div>
    </div>
    
    <!-- Sidebar info -->
    <div style="width: 320px; background: var(--white); overflow-y: auto; border-left: 1px solid var(--gray-200); padding: 1.5rem;">
        <h2 style="font-size: 1.1rem; color: var(--primary); margin-bottom: 1rem; line-height: 1.4;">
            <?= sanitize($memoire['titre']) ?>
        </h2>
        
        <div style="margin-bottom: 1rem;">
            <p style="font-size: 0.85rem; color: var(--gray-500);">Etudiant</p>
            <p style="font-size: 0.95rem; font-weight: 600;"><?= sanitize($memoire['etudiant_prenom'] . ' ' . $memoire['etudiant_nom']) ?></p>
        </div>
        
        <div style="margin-bottom: 1rem;">
            <p style="font-size: 0.85rem; color: var(--gray-500);">Filiere</p>
            <p style="font-size: 0.95rem;"><?= sanitize($memoire['filiere_nom'] ?? '-') ?></p>
        </div>
        
        <div style="margin-bottom: 1rem;">
            <p style="font-size: 0.85rem; color: var(--gray-500);">Annee academique</p>
            <p style="font-size: 0.95rem;"><?= sanitize($memoire['annee_academique']) ?></p>
        </div>
        
        <div style="margin-bottom: 1rem;">
            <p style="font-size: 0.85rem; color: var(--gray-500);">Description</p>
            <p style="font-size: 0.9rem; color: var(--gray-600);"><?= sanitize($memoire['description']) ?></p>
        </div>
        
        <?php if (!empty($memoire['mot_cles'])): ?>
        <div style="margin-bottom: 1rem;">
            <p style="font-size: 0.85rem; color: var(--gray-500);">Mots-cles</p>
            <div style="margin-top: 0.25rem;">
                <?php foreach (explode(',', $memoire['mot_cles']) as $kc): ?>
                <span style="background: var(--gray-100); padding: 0.2rem 0.5rem; border-radius: 8px; font-size: 0.75rem; margin-right: 0.2rem; display: inline-block; margin-bottom: 0.3rem;">
                    <?= sanitize(trim($kc)) ?>
                </span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <hr style="border: none; border-top: 1px solid var(--gray-200); margin: 1rem 0;">
        
        <!-- Like -->
        <div style="margin-bottom: 1rem;">
            <button class="like-btn" data-id="<?= $memoireId ?>" 
                    style="width: 100%; padding: 0.6rem; border: 2px solid <?= $hasLiked ? 'var(--danger)' : 'var(--gray-300)' ?>; 
                           background: <?= $hasLiked ? '#fff5f5' : 'var(--white)' ?>; 
                           border-radius: var(--radius); cursor: pointer; font-size: 0.95rem; font-weight: 600;
                           color: <?= $hasLiked ? 'var(--danger)' : 'var(--gray-600)' ?>;">
                <?= $hasLiked ? '&#9829; Aime' : '&#9825; Aimer' ?> 
                (<span class="like-count"><?= $nbLikes ?></span>)
            </button>
        </div>
        
        <!-- Retour -->
        <a href="<?= $baseUrl ?>pages/bibliotheque.php" class="btn btn-outline btn-block">
            &larr; Retour a la bibliotheque
        </a>
    </div>
</div>

<script>
// Bloquer clic droit
document.addEventListener('contextmenu', function(e) { e.preventDefault(); return false; });

// Bloquer clic molette
document.addEventListener('mousedown', function(e) { if (e.button === 1) { e.preventDefault(); return false; } });

// Bloquer raccourcis clavier
document.addEventListener('keydown', function(e) {
    // Ctrl+S = sauvegarder
    if (e.ctrlKey && e.key === 's') { e.preventDefault(); return false; }
    // Ctrl+P = imprimer
    if (e.ctrlKey && e.key === 'p') { e.preventDefault(); return false; }
    // Ctrl+U = source
    if (e.ctrlKey && e.key === 'u') { e.preventDefault(); return false; }
    // Ctrl+J = telecharger
    if (e.ctrlKey && e.key === 'j') { e.preventDefault(); return false; }
    // Ctrl+Shift+I = dev tools
    if (e.ctrlKey && e.shiftKey && e.key === 'I') { e.preventDefault(); return false; }
    // F12 = dev tools
    if (e.key === 'F12') { e.preventDefault(); return false; }
    // Print Screen
    if (e.key === 'PrintScreen') { 
        navigator.clipboard.writeText(''); 
        e.preventDefault(); 
        return false; 
    }
});

// Like AJAX
document.querySelectorAll('.like-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var id = this.dataset.id;
        var el = this;
        fetch('<?= $baseUrl ?>pages/like.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'memoire_id=' + id
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                var countEl = el.querySelector('.like-count');
                countEl.textContent = data.count;
                if (data.liked) {
                    el.style.borderColor = 'var(--danger)';
                    el.style.background = '#fff5f5';
                    el.style.color = 'var(--danger)';
                    el.innerHTML = '&#9829; Aime (<span class="like-count">' + data.count + '</span>)';
                } else {
                    el.style.borderColor = 'var(--gray-300)';
                    el.style.background = 'var(--white)';
                    el.style.color = 'var(--gray-600)';
                    el.innerHTML = '&#9825; Aimer (<span class="like-count">' + data.count + '</span>)';
                }
            }
        });
    });
});

// Detecter Print Screen
window.addEventListener('keyup', function(e) {
    if (e.key === 'PrintScreen') {
        alert('La capture d\'ecran n\'est pas autorisee.');
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
