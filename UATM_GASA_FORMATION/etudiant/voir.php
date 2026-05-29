<?php
$pageTitle = 'Détail Mémoire - UATM GASA FORMATION';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../auth/session.php';

if (!isLoggedIn() || !hasRole('etudiant')) {
    redirect($baseUrl . 'auth/login.php');
}

$db = getDBConnection();
$userId = $_SESSION['user_id'];
$memoireId = intval($_GET['id'] ?? 0);

if ($memoireId <= 0) {
    redirect('memoires.php');
}

// Récupérer le mémoire (appartient à l'étudiant)
$stmt = $db->prepare("
    SELECT m.*, f.nom as filiere_nom, u.nom as etudiant_nom, u.prenom as etudiant_prenom
    FROM memoires m 
    LEFT JOIN filieres f ON m.filiere_id = f.id 
    LEFT JOIN utilisateurs u ON m.etudiant_id = u.id
    WHERE m.id = ? AND m.etudiant_id = ?
");
$stmt->execute([$memoireId, $userId]);
$memoire = $stmt->fetch();

if (!$memoire) {
    setFlash('error', 'Mémoire non trouvé.');
    redirect('memoires.php');
}

// Validations
$stmt = $db->prepare("
    SELECT v.*, u.nom, u.prenom 
    FROM validations v 
    JOIN utilisateurs u ON v.validateur_id = u.id 
    WHERE v.memoire_id = ? 
    ORDER BY v.date_validation DESC
");
$stmt->execute([$memoireId]);
$validations = $stmt->fetchAll();

// Commentaires
$stmt = $db->prepare("
    SELECT c.*, u.nom, u.prenom, r.nom as role_nom
    FROM commentaires c 
    JOIN utilisateurs u ON c.auteur_id = u.id 
    JOIN roles r ON u.role_id = r.id
    WHERE c.memoire_id = ? 
    ORDER BY c.created_at DESC
");
$stmt->execute([$memoireId]);
$commentaires = $stmt->fetchAll();

// Likes
$stmt = $db->prepare("SELECT COUNT(*) FROM likes WHERE memoire_id = ?");
$stmt->execute([$memoireId]);
$nbLikes = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT id FROM likes WHERE memoire_id = ? AND utilisateur_id = ?");
$stmt->execute([$memoireId, $userId]);
$hasLiked = (bool) $stmt->fetch();

// Traiter le commentaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'comment') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $contenu = trim($_POST['contenu'] ?? '');
        if (!empty($contenu)) {
            $stmt = $db->prepare("INSERT INTO commentaires (memoire_id, auteur_id, contenu) VALUES (?, ?, ?)");
            $stmt->execute([$memoireId, $userId, $contenu]);
            
            // Notifier le professeur
            if ($memoire['professeur_id']) {
                createNotification(
                    $memoire['professeur_id'],
                    'Nouveau commentaire',
                    $_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom'] . ' a repondu a votre commentaire.',
                    'info',
                    'professeur/voir.php?id=' . $memoireId
                );
            }
            
            setFlash('success', 'Commentaire envoye.');
            redirect('voir.php?id=' . $memoireId);
        }
    }
}
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1 class="dashboard-title"><?= sanitize($memoire['titre']) ?></h1>
        <a href="memoires.php" class="btn btn-outline btn-sm">← Retour à la liste</a>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; align-items: start;">
        <!-- Détails principaux -->
        <div>
            <div class="card" style="margin-bottom: 1.5rem;">
                <div class="card-header">
                    <h2 style="font-size: 1.1rem; color: var(--primary);">Informations</h2>
                    <span class="status-badge <?= getStatusClass($memoire['statut']) ?>"><?= getStatusLabel($memoire['statut']) ?></span>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div>
                            <strong style="color: var(--gray-500); font-size: 0.85rem;">Filière</strong>
                            <p><?= sanitize($memoire['filiere_nom'] ?? '-') ?></p>
                        </div>
                        <div>
                            <strong style="color: var(--gray-500); font-size: 0.85rem;">Année académique</strong>
                            <p><?= sanitize($memoire['annee_academique']) ?></p>
                        </div>
                        <div>
                            <strong style="color: var(--gray-500); font-size: 0.85rem;">Date de dépôt</strong>
                            <p><?= date('d/m/Y à H:i', strtotime($memoire['created_at'])) ?></p>
                        </div>
                        <div>
                            <strong style="color: var(--gray-500); font-size: 0.85rem;">Taille</strong>
                            <p><?= formatFileSize($memoire['taille_fichier'] ?? 0) ?></p>
                        </div>
                    </div>

                    <?php if (!empty($memoire['mot_cles'])): ?>
                    <div style="margin-bottom: 1rem;">
                        <strong style="color: var(--gray-500); font-size: 0.85rem;">Mots-clés</strong>
                        <p style="margin-top: 0.25rem;">
                            <?php foreach (explode(',', $memoire['mot_cles']) as $kc): ?>
                            <span style="background: var(--gray-100); padding: 0.2rem 0.6rem; border-radius: 12px; font-size: 0.8rem; margin-right: 0.3rem; display: inline-block; margin-bottom: 0.3rem;">
                                <?= sanitize(trim($kc)) ?>
                            </span>
                            <?php endforeach; ?>
                        </p>
                    </div>
                    <?php endif; ?>

                    <div style="margin-bottom: 1rem;">
                        <strong style="color: var(--gray-500); font-size: 0.85rem;">Description</strong>
                        <p style="margin-top: 0.25rem; white-space: pre-wrap;"><?= sanitize($memoire['description']) ?></p>
                    </div>

                    <?php if ($memoire['note_finale']): ?>
                    <div>
                        <strong style="color: var(--gray-500); font-size: 0.85rem;">Note finale</strong>
                        <p style="font-size: 1.3rem; font-weight: 700; color: var(--primary);"><?= number_format($memoire['note_finale'], 1) ?>/20</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Commentaires -->
            <div class="card" style="margin-bottom: 1.5rem;">
                <div class="card-header">
                    <h2 style="font-size: 1.1rem; color: var(--primary);">Commentaires (<?= count($commentaires) ?>)</h2>
                </div>
                <div class="card-body">
                    <!-- Formulaire commentaire -->
                    <form method="POST" action="" style="margin-bottom: 1.5rem;">
                        <?= csrfField() ?>
                        <input type="hidden" name="action_type" value="comment">
                        <div class="form-group">
                            <textarea name="contenu" class="form-control" rows="3" placeholder="Repondre au professeur ou ajouter un commentaire..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Envoyer</button>
                    </form>

                    <?php if (empty($commentaires)): ?>
                    <p style="color: var(--gray-400); text-align: center;">Aucun commentaire.</p>
                    <?php else: ?>
                    <?php foreach ($commentaires as $c): ?>
                    <div style="border-bottom: 1px solid var(--gray-100); padding: 0.75rem 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong><?= sanitize($c['prenom'] . ' ' . $c['nom']) ?></strong>
                                <span style="font-size: 0.75rem; color: var(--gray-400); background: var(--gray-100); padding: 0.1rem 0.4rem; border-radius: 4px; margin-left: 0.3rem;"><?= sanitize($c['role_nom']) ?></span>
                            </div>
                            <span style="font-size: 0.8rem; color: var(--gray-400);"><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></span>
                        </div>
                        <p style="margin-top: 0.5rem; color: var(--gray-600); white-space: pre-wrap;"><?= sanitize($c['contenu']) ?></p>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div>
            <!-- Fichier -->
            <div class="card" style="margin-bottom: 1.5rem;">
                <div class="card-header">
                    <h2 style="font-size: 1.1rem; color: var(--primary);">Fichier</h2>
                </div>
                <div class="card-body" style="text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 0.5rem;">&#128196;</div>
                    <p style="font-size: 0.9rem; color: var(--gray-600); margin-bottom: 1rem;">
                        <?= sanitize($memoire['fichier_pdf']) ?>
                    </p>
                    <a href="<?= $baseUrl ?>pages/lire.php?id=<?= $memoireId ?>" class="btn btn-primary btn-block" style="margin-bottom: 0.5rem;">
                        Lire le memoire
                    </a>
                    <div style="margin-top: 0.5rem;">
                        <button class="like-btn" data-id="<?= $memoireId ?>" 
                                style="background: none; border: 1px solid <?= $hasLiked ? 'var(--danger)' : 'var(--gray-300)' ?>; 
                                       padding: 0.4rem 1rem; border-radius: var(--radius); cursor: pointer; color: <?= $hasLiked ? 'var(--danger)' : 'var(--gray-500)' ?>;">
                            <?= $hasLiked ? '&#9829;' : '&#9825;' ?> <span class="like-count"><?= $nbLikes ?></span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Statut -->
            <div class="card" style="margin-bottom: 1.5rem;">
                <div class="card-header">
                    <h2 style="font-size: 1.1rem; color: var(--primary);">Statut</h2>
                </div>
                <div class="card-body">
                    <?php if (in_array($memoire['statut'], ['brouillon', 'rejete'])): ?>
                    <p style="font-size: 0.9rem; color: var(--gray-500); margin-bottom: 1rem;">
                        <?php if ($memoire['statut'] === 'brouillon'): ?>
                            Ce mémoire n'a pas encore été soumis.
                        <?php else: ?>
                            Ce mémoire a été rejeté. Vous pouvez le modifier et le resoumettre.
                        <?php endif; ?>
                    </p>
                    <a href="modifier.php?id=<?= $memoire['id'] ?>" class="btn btn-primary btn-block">Modifier et resoumettre</a>
                    <?php elseif ($memoire['statut'] === 'soumis'): ?>
                    <p style="font-size: 0.9rem; color: var(--warning);">&#8987; En attente de validation par un professeur.</p>
                    <?php elseif ($memoire['statut'] === 'en_revision'): ?>
                    <p style="font-size: 0.9rem; color: var(--info);">&#128269; En révision par le professeur.</p>
                    <?php elseif ($memoire['statut'] === 'valide'): ?>
                    <p style="font-size: 0.9rem; color: var(--success);">&#9989; Mémoire validé !</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Validations -->
            <?php if (!empty($validations)): ?>
            <div class="card">
                <div class="card-header">
                    <h2 style="font-size: 1.1rem; color: var(--primary);">Validations</h2>
                </div>
                <div class="card-body" style="padding: 0;">
                    <?php foreach ($validations as $v): ?>
                    <div style="padding: 0.75rem 1rem; border-bottom: 1px solid var(--gray-100);">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <strong style="font-size: 0.85rem;"><?= sanitize($v['prenom'] . ' ' . $v['nom']) ?></strong>
                            <span class="status-badge status-<?= $v['decision'] === 'approuve' ? 'approved' : ($v['decision'] === 'rejete' ? 'rejected' : 'review') ?>">
                                <?= $v['decision'] ?>
                            </span>
                        </div>
                        <?php if ($v['note']): ?>
                        <p style="font-size: 0.85rem; margin-top: 0.25rem;">Note: <?= number_format($v['note'], 1) ?>/20</p>
                        <?php endif; ?>
                        <?php if ($v['commentaire']): ?>
                        <p style="font-size: 0.85rem; color: var(--gray-500); margin-top: 0.25rem;"><?= sanitize($v['commentaire']) ?></p>
                        <?php endif; ?>
                        <span style="font-size: 0.75rem; color: var(--gray-400);"><?= date('d/m/Y', strtotime($v['date_validation'])) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
