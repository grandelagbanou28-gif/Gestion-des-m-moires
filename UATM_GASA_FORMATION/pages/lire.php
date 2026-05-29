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
    WHERE m.id = ? AND (m.statut = 'valide' OR m.professeur_id = ? OR m.etudiant_id = ?)
");
$stmt->execute([$memoireId, $_SESSION['user_id'], $_SESSION['user_id']]);
$memoire = $stmt->fetch();

if (!$memoire) {
    setFlash('error', 'Memoire non trouve ou acces non autorise.');
    redirect($baseUrl . 'pages/bibliotheque.php');
}

$fichierPath = $baseUrl . 'assets/uploads/' . $memoire['fichier_pdf'];

$userId = $_SESSION['user_id'];
$stmt = $db->prepare("SELECT COUNT(*) FROM likes WHERE memoire_id = ?");
$stmt->execute([$memoireId]);
$nbLikes = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT id FROM likes WHERE memoire_id = ? AND utilisateur_id = ?");
$stmt->execute([$memoireId, $userId]);
$hasLiked = (bool) $stmt->fetch();
?>

<style>
body { overflow: hidden; margin: 0; padding: 0; }
#viewer-container { width: 100%; height: calc(100vh - 64px); overflow-y: auto; background: #525659; text-align: center; }
#viewer-container canvas { margin: 10px auto; display: block; box-shadow: 0 2px 8px rgba(0,0,0,0.3); }
.page-info { color: #ccc; padding: 10px; font-size: 14px; }
.nav-buttons { position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); z-index: 100; display: flex; gap: 10px; background: rgba(0,0,0,0.7); padding: 10px 20px; border-radius: 8px; }
.nav-buttons button { background: #fff; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: bold; }
.nav-buttons button:hover { background: #ddd; }
#pageInput { width: 60px; text-align: center; border: none; padding: 8px; border-radius: 4px; }
</style>

<div id="viewer-container">
    <div class="page-info" id="pageInfo">Chargement du PDF...</div>
    <canvas id="pdfCanvas"></canvas>
    <div class="nav-buttons">
        <button onclick="prevPage()">&laquo; Precedent</button>
        <input type="number" id="pageInput" value="1" min="1" onchange="goToPage(this.value)">
        <button onclick="nextPage()">Suivant &raquo;</button>
        <span id="totalPages" style="color: #fff; padding: 8px;"></span>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

var pdfDoc = null;
var pageNum = 1;
var pageRendering = false;
var pageNumPending = null;
var scale = 1.5;
var canvas = document.getElementById('pdfCanvas');
var ctx = canvas.getContext('2d');

function renderPage(num) {
    pageRendering = true;
    pdfDoc.getPage(num).then(function(page) {
        var viewport = page.getViewport({ scale: scale });
        canvas.height = viewport.height;
        canvas.width = viewport.width;
        var renderContext = { canvasContext: ctx, viewport: viewport };
        page.render(renderContext).promise.then(function() {
            pageRendering = false;
            if (pageNumPending !== null) {
                renderPage(pageNumPending);
                pageNumPending = null;
            }
        });
    });
    document.getElementById('pageInfo').textContent = 'Page ' + num;
    document.getElementById('pageInput').value = num;
}

function queueRenderPage(num) {
    if (pageRendering) { pageNumPending = num; } else { renderPage(num); }
}

function prevPage() { if (pageNum <= 1) return; pageNum--; queueRenderPage(pageNum); }
function nextPage() { if (pageNum >= pdfDoc.numPages) return; pageNum++; queueRenderPage(pageNum); }
function goToPage(num) { num = parseInt(num); if (num >= 1 && num <= pdfDoc.numPages) { pageNum = num; queueRenderPage(num); } }

var url = '<?= $fichierPath ?>';
pdfjsLib.getDocument(url).promise.then(function(pdf) {
    pdfDoc = pdf;
    document.getElementById('totalPages').textContent = '/ ' + pdf.numPages;
    renderPage(pageNum);
});
</script>

<script>
document.addEventListener('contextmenu', function(e) { e.preventDefault(); return false; });
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && (e.key === 's' || e.key === 'p' || e.key === 'u' || e.key === 'j')) { e.preventDefault(); return false; }
    if (e.key === 'F12' || (e.ctrlKey && e.shiftKey && e.key === 'I')) { e.preventDefault(); return false; }
    if (e.key === 'PrintScreen') { navigator.clipboard.writeText(''); e.preventDefault(); return false; }
});

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
                if (data.liked) {
                    el.style.borderColor = 'var(--danger)';
                    el.style.color = 'var(--danger)';
                    el.innerHTML = '&#9829; <span class="like-count">' + data.count + '</span>';
                } else {
                    el.style.borderColor = 'var(--gray-300)';
                    el.style.color = 'var(--gray-500)';
                    el.innerHTML = '&#9825; <span class="like-count">' + data.count + '</span>';
                }
            }
        });
    });
});
</script>
    
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
