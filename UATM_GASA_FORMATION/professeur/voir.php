<?php
$pageTitle = 'Examiner Memoire - UATM GASA FORMATION';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../auth/session.php';

if (!isLoggedIn() || !hasRole('professeur')) {
    redirect($baseUrl . 'auth/login.php');
}

$db = getDBConnection();
$userId = $_SESSION['user_id'];
$memoireId = intval($_GET['id'] ?? 0);

if ($memoireId <= 0) {
    redirect('memoires.php');
}

$stmt = $db->prepare("
    SELECT m.*, f.nom as filiere_nom, u.nom as etudiant_nom, u.prenom as etudiant_prenom, u.email as etudiant_email
    FROM memoires m 
    LEFT JOIN filieres f ON m.filiere_id = f.id 
    LEFT JOIN utilisateurs u ON m.etudiant_id = u.id
    WHERE m.id = ? AND m.professeur_id = ?
");
$stmt->execute([$memoireId, $userId]);
$memoire = $stmt->fetch();

if (!$memoire) {
    setFlash('error', 'Memoire non trouve ou non assigne.');
    redirect('memoires.php');
}

$stmt = $db->prepare("
    SELECT v.*, u.nom, u.prenom 
    FROM validations v 
    JOIN utilisateurs u ON v.validateur_id = u.id 
    WHERE v.memoire_id = ? 
    ORDER BY v.date_validation DESC
");
$stmt->execute([$memoireId]);
$validations = $stmt->fetchAll();

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

$stmt = $db->prepare("SELECT COUNT(*) FROM likes WHERE memoire_id = ?");
$stmt->execute([$memoireId]);
$nbLikes = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT id FROM likes WHERE memoire_id = ? AND utilisateur_id = ?");
$stmt->execute([$memoireId, $userId]);
$hasLiked = (bool) $stmt->fetch();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de securite invalide.';
    } else {
        $action = $_POST['action_type'];

        if ($action === 'decision') {
            $decision = $_POST['decision'] ?? '';
            $commentaire = trim($_POST['commentaire_decision'] ?? '');
            $note = $_POST['note'] !== '' ? floatval($_POST['note']) : null;

            if (!in_array($decision, ['approuve', 'rejete', 'revision'])) {
                $errors[] = 'Decision invalide.';
            }

            if ($decision === 'approuve' && ($note === null || $note < 0 || $note > 20)) {
                $errors[] = 'La note doit etre comprise entre 0 et 20.';
            }

            if (empty($errors)) {
                $stmt = $db->prepare("
                    INSERT INTO validations (memoire_id, validateur_id, decision, commentaire, note) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$memoireId, $userId, $decision, $commentaire, $note]);

                if ($decision === 'approuve') {
                    $newStatut = 'valide';
                    $noteFinale = $note;
                } elseif ($decision === 'rejete') {
                    $newStatut = 'rejete';
                    $noteFinale = null;
                } else {
                    $newStatut = 'en_revision';
                    $noteFinale = null;
                }

                $stmt = $db->prepare("
                    UPDATE memoires SET statut = ?, note_finale = ?, date_validation = NOW(), updated_at = NOW() WHERE id = ?
                ");
                $stmt->execute([$newStatut, $noteFinale, $memoireId]);

                if ($decision === 'approuve') {
                    $msg = 'Votre memoire "' . $memoire['titre'] . '" a ete valide avec la note: ' . number_format($note, 1) . '/20.';
                } elseif ($decision === 'rejete') {
                    $msg = 'Votre memoire "' . $memoire['titre'] . '" a ete rejete.' . ($commentaire ? ' Raison: ' . $commentaire : '');
                } else {
                    $msg = 'Votre memoire "' . $memoire['titre'] . '" necessite des revisions.';
                }
                $type = $decision === 'approuve' ? 'success' : ($decision === 'rejete' ? 'error' : 'warning');
                createNotification($memoire['etudiant_id'], 'Mise a jour de votre memoire', $msg, $type, 'etudiant/voir.php?id=' . $memoireId);

                logAction($userId, 'valider', 'memoire', $memoireId, 'Decision: ' . $decision);

                setFlash('success', 'Decision enregistree avec succes.');
                redirect('voir.php?id=' . $memoireId);
            }
        }

        if ($action === 'comment') {
            $contenu = trim($_POST['contenu'] ?? '');
            if (empty($contenu)) {
                $errors[] = 'Le commentaire ne peut pas etre vide.';
            } else {
                $stmt = $db->prepare("INSERT INTO commentaires (memoire_id, auteur_id, contenu) VALUES (?, ?, ?)");
                $stmt->execute([$memoireId, $userId, $contenu]);

                createNotification(
                    $memoire['etudiant_id'],
                    'Nouveau commentaire',
                    $_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom'] . ' a commente votre memoire.',
                    'info',
                    'etudiant/voir.php?id=' . $memoireId
                );

                logAction($userId, 'commenter', 'memoire', $memoireId);

                setFlash('success', 'Commentaire ajoute.');
                redirect('voir.php?id=' . $memoireId);
            }
        }
    }
}
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1 class="dashboard-title">Examiner le Memoire</h1>
        <a href="memoires.php" class="btn btn-outline btn-sm">&larr; Retour a la liste</a>
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

    <div style="display: flex; gap: 1.5rem; align-items: start; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 300px;">
            <div class="card" style="margin-bottom: 1.5rem;">
                <div class="card-header">
                    <h2 style="font-size: 1.1rem; color: var(--primary);">Details du memoire</h2>
                    <span class="status-badge <?= getStatusClass($memoire['statut']) ?>"><?= getStatusLabel($memoire['statut']) ?></span>
                </div>
                <div class="card-body">
                    <h3 style="font-size: 1.2rem; color: var(--gray-800); margin-bottom: 1rem;"><?= sanitize($memoire['titre']) ?></h3>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div>
                            <strong style="color: var(--gray-500); font-size: 0.85rem;">Etudiant</strong>
                            <p><?= sanitize($memoire['etudiant_prenom'] . ' ' . $memoire['etudiant_nom']) ?></p>
                        </div>
                        <div>
                            <strong style="color: var(--gray-500); font-size: 0.85rem;">Email</strong>
                            <p><?= sanitize($memoire['etudiant_email']) ?></p>
                        </div>
                        <div>
                            <strong style="color: var(--gray-500); font-size: 0.85rem;">Filiere</strong>
                            <p><?= sanitize($memoire['filiere_nom'] ?? '-') ?></p>
                        </div>
                        <div>
                            <strong style="color: var(--gray-500); font-size: 0.85rem;">Annee academique</strong>
                            <p><?= sanitize($memoire['annee_academique']) ?></p>
                        </div>
                    </div>

                    <?php if (!empty($memoire['mot_cles'])): ?>
                    <div style="margin-bottom: 1rem;">
                        <strong style="color: var(--gray-500); font-size: 0.85rem;">Mots-cles</strong>
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

            <?php if (in_array($memoire['statut'], ['soumis', 'en_revision'])): ?>
            <div class="card" style="margin-bottom: 1.5rem; border: 2px solid var(--accent);">
                <div class="card-header" style="background: var(--gray-50);">
                    <h2 style="font-size: 1.1rem; color: var(--primary);">Decision de validation</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="" id="decisionForm">
                        <?= csrfField() ?>
                        <input type="hidden" name="action_type" value="decision">

                        <div class="form-group">
                            <label for="decision">Decision *</label>
                            <select id="decision" name="decision" class="form-control" required>
                                <option value="">-- Choisir --</option>
                                <option value="approuve">Approuver</option>
                                <option value="revision">Demander des revisions</option>
                                <option value="rejete">Rejeter</option>
                            </select>
                        </div>

                        <div class="form-group" id="noteGroup" style="display: none;">
                            <label for="note">Note (sur 20) *</label>
                            <input type="number" id="note" name="note" class="form-control" min="0" max="20" step="0.5" placeholder="ex: 15.5">
                        </div>

                        <div class="form-group">
                            <label for="commentaire_decision">Commentaire</label>
                            <textarea id="commentaire_decision" name="commentaire_decision" class="form-control" rows="4" placeholder="Ajouter un commentaire ou une justification..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary" id="submitDecision">Enregistrer la decision</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <div class="card" style="margin-bottom: 1.5rem;">
                <div class="card-header">
                    <h2 style="font-size: 1.1rem; color: var(--primary);">Commentaires (<?= count($commentaires) ?>)</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="" style="margin-bottom: 1.5rem;">
                        <?= csrfField() ?>
                        <input type="hidden" name="action_type" value="comment">
                        <div class="form-group">
                            <textarea name="contenu" class="form-control" rows="3" placeholder="Ajouter un commentaire..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-secondary btn-sm">Poster le commentaire</button>
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

            <?php if (!empty($validations)): ?>
            <div class="card">
                <div class="card-header">
                    <h2 style="font-size: 1.1rem; color: var(--primary);">Historique des validations</h2>
                </div>
                <div class="card-body" style="padding: 0;">
                    <?php foreach ($validations as $v): ?>
                    <div style="padding: 0.75rem 1rem; border-bottom: 1px solid var(--gray-100);">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <strong style="font-size: 0.85rem;"><?= sanitize($v['prenom'] . ' ' . $v['nom']) ?></strong>
                            <span class="status-badge status-<?= $v['decision'] === 'approuve' ? 'approved' : ($v['decision'] === 'rejete' ? 'rejected' : 'review') ?>">
                                <?= ucfirst($v['decision']) ?>
                            </span>
                        </div>
                        <?php if ($v['note']): ?>
                        <p style="font-size: 0.85rem; margin-top: 0.25rem;">Note: <?= number_format($v['note'], 1) ?>/20</p>
                        <?php endif; ?>
                        <?php if ($v['commentaire']): ?>
                        <p style="font-size: 0.85rem; color: var(--gray-500); margin-top: 0.25rem;"><?= sanitize($v['commentaire']) ?></p>
                        <?php endif; ?>
                        <span style="font-size: 0.75rem; color: var(--gray-400);"><?= date('d/m/Y H:i', strtotime($v['date_validation'])) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div>
            <div class="card" style="margin-bottom: 1.5rem;">
                <div class="card-header">
                    <h2 style="font-size: 1.1rem; color: var(--primary);">Fichier PDF</h2>
                </div>
                <div class="card-body" style="text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 0.5rem;">&#128196;</div>
                    <p style="font-size: 0.85rem; color: var(--gray-600); margin-bottom: 1rem; word-break: break-all;">
                        <?= sanitize($memoire['fichier_pdf']) ?>
                    </p>
                    <p style="font-size: 0.85rem; color: var(--gray-500); margin-bottom: 1rem;">
                        Taille: <?= formatFileSize($memoire['taille_fichier'] ?? 0) ?>
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

            <div class="card">
                <div class="card-header">
                    <h2 style="font-size: 1.1rem; color: var(--primary);">Actions rapides</h2>
                </div>
                <div class="card-body">
                    <p style="font-size: 0.85rem; color: var(--gray-500); margin-bottom: 1rem;">
                        Lisez le memoire puis utilisez le formulaire de decision pour valider, demander des revisions ou rejeter.
                    </p>
                    <a href="<?= $baseUrl ?>assets/uploads/<?= sanitize($memoire['fichier_pdf']) ?>" class="btn btn-outline btn-block" download>
                        Telecharger le PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('decision').addEventListener('change', function() {
    var noteGroup = document.getElementById('noteGroup');
    var noteInput = document.getElementById('note');
    if (this.value === 'approuve') {
        noteGroup.style.display = 'block';
        noteInput.required = true;
    } else {
        noteGroup.style.display = 'none';
        noteInput.required = false;
        noteInput.value = '';
    }
});

document.getElementById('decisionForm').addEventListener('submit', function(e) {
    var decision = document.getElementById('decision').value;
    if (!decision) { e.preventDefault(); alert('Veuillez choisir une decision.'); return; }
    if (decision === 'approuve') {
        var note = parseFloat(document.getElementById('note').value);
        if (isNaN(note) || note < 0 || note > 20) { e.preventDefault(); alert('La note doit etre entre 0 et 20.'); return; }
    }
    if (!confirm('Confirmer cette decision ?')) e.preventDefault();
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
