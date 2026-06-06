<?php
$pageTitle = 'Espace Directeur - UATM GASA FORMATION';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../auth/session.php';

if (!isLoggedIn() || !hasRole('directeur')) {
    redirect($baseUrl . 'auth/login.php');
}

$db = getDBConnection();

// Statistiques globales
$stats = [];
$stmt = $db->query("SELECT COUNT(*) FROM memoires");
$stats['total'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM memoires WHERE statut = 'soumis'");
$stats['soumis'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM memoires WHERE statut = 'valide'");
$stats['valides'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM memoires WHERE statut = 'rejete'");
$stats['rejetes'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM memoires WHERE statut = 'en_revision'");
$stats['enRevision'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM memoires WHERE statut = 'archive'");
$stats['archives'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM utilisateurs WHERE statut = 'actif'");
$stats['utilisateurs'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM filieres WHERE statut = 'active'");
$stats['filieres'] = $stmt->fetchColumn();

// Mémoires récemment validés
$validesRecents = $db->query("
    SELECT m.*, f.nom as filiere_nom, u.nom as etudiant_nom, u.prenom as etudiant_prenom
    FROM memoires m 
    LEFT JOIN filieres f ON m.filiere_id = f.id
    LEFT JOIN utilisateurs u ON m.etudiant_id = u.id
    WHERE m.statut = 'valide'
    ORDER BY m.date_validation DESC 
    LIMIT 8
")->fetchAll();

// Statistiques par filière
$statsFiliere = $db->query("
    SELECT f.nom, COUNT(m.id) as total, 
           SUM(CASE WHEN m.statut = 'valide' THEN 1 ELSE 0 END) as valides,
           SUM(CASE WHEN m.statut = 'rejete' THEN 1 ELSE 0 END) as rejetes
    FROM filieres f 
    LEFT JOIN memoires m ON f.id = m.filiere_id
    WHERE f.statut = 'active'
    GROUP BY f.id, f.nom
    ORDER BY total DESC
")->fetchAll();

// Activité récente
$activite = $db->query("
    SELECT h.*, u.nom, u.prenom 
    FROM historiques h 
    JOIN utilisateurs u ON h.utilisateur_id = u.id 
    ORDER BY h.created_at DESC 
    LIMIT 10
")->fetchAll();
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1 class="dashboard-title">Espace Directeur</h1>
        <p style="color: var(--gray-500);">Supervision globale de la plateforme</p>
    </div>

    <!-- Stats principales -->
    <div class="dashboard-stats">
        <div class="dash-stat-card">
            <h3>Mémoires total</h3>
            <div class="value"><?= $stats['total'] ?></div>
        </div>
        <div class="dash-stat-card" style="border-left-color: var(--warning)">
            <h3>Soumis</h3>
            <div class="value" style="color: var(--warning)"><?= $stats['soumis'] ?></div>
        </div>
        <div class="dash-stat-card" style="border-left-color: var(--info)">
            <h3>En révision</h3>
            <div class="value" style="color: var(--info)"><?= $stats['enRevision'] ?></div>
        </div>
        <div class="dash-stat-card" style="border-left-color: var(--success)">
            <h3>Validés</h3>
            <div class="value" style="color: var(--success)"><?= $stats['valides'] ?></div>
        </div>
        <div class="dash-stat-card" style="border-left-color: var(--danger)">
            <h3>Rejetés</h3>
            <div class="value" style="color: var(--danger)"><?= $stats['rejetes'] ?></div>
        </div>
        <div class="dash-stat-card" style="border-left-color: var(--gray-500)">
            <h3>Archivés</h3>
            <div class="value" style="color: var(--gray-500)"><?= $stats['archives'] ?></div>
        </div>
        <div class="dash-stat-card" style="border-left-color: var(--secondary)">
            <h3>Utilisateurs</h3>
            <div class="value" style="color: var(--secondary)"><?= $stats['utilisateurs'] ?></div>
        </div>
        <div class="dash-stat-card" style="border-left-color: var(--accent)">
            <h3>Filières</h3>
            <div class="value" style="color: var(--accent)"><?= $stats['filieres'] ?></div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
        <!-- Statistiques par filière -->
        <div class="card">
            <div class="card-header">
                <h2 style="font-size: 1.1rem; color: var(--primary);">Statistiques par filière</h2>
            </div>
            <div class="card-body" style="padding: 0;">
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Filière</th>
                            <th>Total</th>
                            <th>Validés</th>
                            <th>Rejetés</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($statsFiliere)): ?>
                        <tr><td colspan="4" style="text-align: center; color: var(--gray-400); padding: 1.5rem;">Aucune donnée.</td></tr>
                        <?php else: ?>
                        <?php foreach ($statsFiliere as $sf): ?>
                        <tr>
                            <td><strong><?= sanitize($sf['nom']) ?></strong></td>
                            <td><?= $sf['total'] ?></td>
                            <td style="color: var(--success); font-weight: 600;"><?= $sf['valides'] ?></td>
                            <td style="color: var(--danger); font-weight: 600;"><?= $sf['rejetes'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mémoires récemment validés -->
        <div class="card">
            <div class="card-header">
                <h2 style="font-size: 1.1rem; color: var(--primary);">Validés récemment</h2>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if (empty($validesRecents)): ?>
                <p style="text-align: center; color: var(--gray-400); padding: 1.5rem;">Aucun mémoire validé.</p>
                <?php else: ?>
                <?php foreach ($validesRecents as $vr): ?>
                <div style="padding: 0.6rem 1rem; border-bottom: 1px solid var(--gray-100);">
                    <div style="font-size: 0.85rem; font-weight: 500;">
                        <a href="#"><?= sanitize(substr($vr['titre'], 0, 50)) ?><?= strlen($vr['titre']) > 50 ? '...' : '' ?></a>
                    </div>
                    <div style="font-size: 0.8rem; color: var(--gray-500);">
                        <?= sanitize($vr['etudiant_prenom'] . ' ' . $vr['etudiant_nom']) ?>
                        — <?= sanitize($vr['filiere_nom'] ?? '-') ?>
                        <?php if ($vr['note_finale']): ?>
                        — <strong style="color: var(--success);"><?= number_format($vr['note_finale'], 1) ?>/20</strong>
                        <?php endif; ?>
                    </div>
                    <div style="font-size: 0.75rem; color: var(--gray-400);"><?= date('d/m/Y', strtotime($vr['date_validation'])) ?></div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Activité récente -->
        <div class="card" style="grid-column: 1 / -1;">
            <div class="card-header">
                <h2 style="font-size: 1.1rem; color: var(--primary);">Activité récente</h2>
            </div>
            <div class="card-body" style="padding: 0;">
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Date/Heure</th>
                            <th>Utilisateur</th>
                            <th>Action</th>
                            <th>Entité</th>
                            <th>Détails</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activite as $a): ?>
                        <tr>
                            <td style="white-space: nowrap; font-size: 0.85rem;"><?= date('d/m/Y H:i', strtotime($a['created_at'])) ?></td>
                            <td><?= sanitize($a['prenom'] . ' ' . $a['nom']) ?></td>
                            <td><span style="font-size: 0.8rem; background: var(--gray-100); padding: 0.15rem 0.5rem; border-radius: 4px;"><?= sanitize($a['action']) ?></span></td>
                            <td><?= sanitize($a['entite']) ?> <?= $a['entite_id'] ? '#' . $a['entite_id'] : '' ?></td>
                            <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= sanitize(substr($a['details'] ?? '', 0, 60)) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
