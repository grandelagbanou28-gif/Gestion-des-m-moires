<?php
$pageTitle = 'Rapports & Statistiques - UATM GASA FORMATION';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../auth/session.php';

if (!isLoggedIn() || !hasRole(['administrateur', 'directeur'])) {
    redirect($baseUrl . 'auth/login.php');
}

$db = getDBConnection();

// Stats générales
$stats = [];

$stmt = $db->query("SELECT COUNT(*) FROM utilisateurs WHERE statut = 'actif'");
$stats['utilisateurs_actifs'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM memoires");
$stats['memoires_total'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM memoires WHERE statut = 'valide'");
$stats['valides'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM memoires WHERE statut = 'soumis'");
$stats['soumis'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM memoires WHERE statut = 'rejete'");
$stats['rejetes'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM memoires WHERE statut = 'archive'");
$stats['archives'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM commentaires");
$stats['commentaires'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM likes");
$stats['likes'] = $stmt->fetchColumn();

// Taux de réussite
$tauxReussite = $stats['memoires_total'] > 0 ? round(($stats['valides'] / $stats['memoires_total']) * 100, 1) : 0;

// Mémoires par filière
$parFiliere = $db->query("
    SELECT f.nom, COUNT(m.id) as total,
           SUM(CASE WHEN m.statut = 'valide' THEN 1 ELSE 0 END) as valides,
           SUM(CASE WHEN m.statut = 'rejete' THEN 1 ELSE 0 END) as rejetes,
           ROUND(AVG(CASE WHEN m.statut = 'valide' THEN m.note_finale END), 1) as moyenne_notes
    FROM filieres f
    LEFT JOIN memoires m ON f.id = m.filiere_id
    WHERE f.statut = 'active'
    GROUP BY f.id, f.nom
    ORDER BY total DESC
")->fetchAll();

// Mémoires par année
$parAnnee = $db->query("
    SELECT annee_academique, COUNT(*) as total,
           SUM(CASE WHEN statut = 'valide' THEN 1 ELSE 0 END) as valides,
           SUM(CASE WHEN statut = 'rejete' THEN 1 ELSE 0 END) as rejetes
    FROM memoires
    GROUP BY annee_academique
    ORDER BY annee_academique DESC
")->fetchAll();

// Professeurs les plus actifs
$profActifs = $db->query("
    SELECT u.nom, u.prenom, COUNT(v.id) as nb_validations,
           SUM(CASE WHEN v.decision = 'approuve' THEN 1 ELSE 0 END) as nb_approuves,
           SUM(CASE WHEN v.decision = 'rejete' THEN 1 ELSE 0 END) as nb_rejetes
    FROM utilisateurs u
    LEFT JOIN validations v ON u.id = v.validateur_id
    WHERE u.role_id = 3
    GROUP BY u.id, u.nom, u.prenom
    HAVING nb_validations > 0
    ORDER BY nb_validations DESC
    LIMIT 10
")->fetchAll();

// Étudiants les plus productifs
$etudActifs = $db->query("
    SELECT u.nom, u.prenom, COUNT(m.id) as nb_memoires,
           SUM(CASE WHEN m.statut = 'valide' THEN 1 ELSE 0 END) as nb_valides,
           ROUND(AVG(CASE WHEN m.statut = 'valide' THEN m.note_finale END), 1) as moyenne
    FROM utilisateurs u
    LEFT JOIN memoires m ON u.id = m.etudiant_id
    WHERE u.role_id = 4
    GROUP BY u.id, u.nom, u.prenom
    HAVING nb_memoires > 0
    ORDER BY nb_memoires DESC
    LIMIT 10
")->fetchAll();

// Top mémoires likés
$topLikes = $db->query("
    SELECT m.titre, u.nom, u.prenom, COUNT(l.id) as nb_likes
    FROM memoires m
    JOIN likes l ON m.id = l.memoire_id
    JOIN utilisateurs u ON m.etudiant_id = u.id
    GROUP BY m.id, m.titre, u.nom, u.prenom
    ORDER BY nb_likes DESC
    LIMIT 5
")->fetchAll();

// Activité mensuelle
$activiteMensuelle = $db->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as mois, COUNT(*) as total
    FROM historiques
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY mois
    ORDER BY mois ASC
")->fetchAll();
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1 class="dashboard-title">Rapports & Statistiques</h1>
    </div>

    <!-- KPIs -->
    <div class="dashboard-stats">
        <div class="dash-stat-card">
            <h3>Mémoires total</h3>
            <div class="value"><?= $stats['memoires_total'] ?></div>
        </div>
        <div class="dash-stat-card" style="border-left-color: var(--success)">
            <h3>Taux de réussite</h3>
            <div class="value" style="color: var(--success)"><?= $tauxReussite ?>%</div>
        </div>
        <div class="dash-stat-card" style="border-left-color: var(--warning)">
            <h3>En attente</h3>
            <div class="value" style="color: var(--warning)"><?= $stats['soumis'] ?></div>
        </div>
        <div class="dash-stat-card" style="border-left-color: var(--info)">
            <h3>Commentaires</h3>
            <div class="value" style="color: var(--info)"><?= $stats['commentaires'] ?></div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
        <!-- Par filière -->
        <div class="card">
            <div class="card-header">
                <h2 style="font-size: 1.1rem; color: var(--primary);">Par filière</h2>
            </div>
            <div class="card-body" style="padding: 0;">
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Filière</th>
                            <th>Total</th>
                            <th>Validés</th>
                            <th>Rejetés</th>
                            <th>Moy.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($parFiliere as $f): ?>
                        <tr>
                            <td><strong><?= sanitize($f['nom']) ?></strong></td>
                            <td><?= $f['total'] ?></td>
                            <td style="color: var(--success);"><?= $f['valides'] ?></td>
                            <td style="color: var(--danger);"><?= $f['rejetes'] ?></td>
                            <td><?= $f['moyenne_notes'] ? number_format($f['moyenne_notes'], 1) : '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Par année -->
        <div class="card">
            <div class="card-header">
                <h2 style="font-size: 1.1rem; color: var(--primary);">Par année académique</h2>
            </div>
            <div class="card-body" style="padding: 0;">
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Année</th>
                            <th>Total</th>
                            <th>Validés</th>
                            <th>Rejetés</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($parAnnee)): ?>
                        <tr><td colspan="4" style="text-align: center; color: var(--gray-400); padding: 1rem;">Aucune donnée.</td></tr>
                        <?php else: ?>
                        <?php foreach ($parAnnee as $a): ?>
                        <tr>
                            <td><strong><?= sanitize($a['annee_academique']) ?></strong></td>
                            <td><?= $a['total'] ?></td>
                            <td style="color: var(--success);"><?= $a['valides'] ?></td>
                            <td style="color: var(--danger);"><?= $a['rejetes'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Professeurs actifs -->
        <div class="card">
            <div class="card-header">
                <h2 style="font-size: 1.1rem; color: var(--primary);">Professeurs les plus actifs</h2>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if (empty($profActifs)): ?>
                <p style="text-align: center; color: var(--gray-400); padding: 1.5rem;">Aucune donnée.</p>
                <?php else: ?>
                <?php foreach ($profActifs as $p): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.6rem 1rem; border-bottom: 1px solid var(--gray-100);">
                    <div>
                        <strong style="font-size: 0.9rem;"><?= sanitize($p['prenom'] . ' ' . $p['nom']) ?></strong>
                        <div style="font-size: 0.8rem; color: var(--gray-500);">
                            <?= $p['nb_approuves'] ?> validés / <?= $p['nb_rejetes'] ?> rejetés
                        </div>
                    </div>
                    <span style="background: var(--gray-100); padding: 0.2rem 0.6rem; border-radius: 12px; font-size: 0.85rem; font-weight: 600;">
                        <?= $p['nb_validations'] ?> évaluations
                    </span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Étudiants productifs -->
        <div class="card">
            <div class="card-header">
                <h2 style="font-size: 1.1rem; color: var(--primary);">Étudiants les plus productifs</h2>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if (empty($etudActifs)): ?>
                <p style="text-align: center; color: var(--gray-400); padding: 1.5rem;">Aucune donnée.</p>
                <?php else: ?>
                <?php foreach ($etudActifs as $e): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.6rem 1rem; border-bottom: 1px solid var(--gray-100);">
                    <div>
                        <strong style="font-size: 0.9rem;"><?= sanitize($e['prenom'] . ' ' . $e['nom']) ?></strong>
                        <div style="font-size: 0.8rem; color: var(--gray-500);">
                            <?= $e['nb_valides'] ?> validés sur <?= $e['nb_memoires'] ?>
                        </div>
                    </div>
                    <span style="font-weight: 600; color: <?= $e['moyenne'] && $e['moyenne'] >= 10 ? 'var(--success)' : 'var(--gray-500)' ?>;">
                        <?= $e['moyenne'] ? number_format($e['moyenne'], 1) . '/20' : '-' ?>
                    </span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Top likés -->
        <div class="card">
            <div class="card-header">
                <h2 style="font-size: 1.1rem; color: var(--primary);">Top mémoires likés</h2>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if (empty($topLikes)): ?>
                <p style="text-align: center; color: var(--gray-400); padding: 1.5rem;">Aucune donnée.</p>
                <?php else: ?>
                <?php foreach ($topLikes as $i => $tl): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.6rem 1rem; border-bottom: 1px solid var(--gray-100);">
                    <div>
                        <span style="font-weight: 700; color: var(--primary); margin-right: 0.5rem;">#<?= $i + 1 ?></span>
                        <strong style="font-size: 0.9rem;"><?= sanitize(substr($tl['titre'], 0, 40)) ?><?= strlen($tl['titre']) > 40 ? '...' : '' ?></strong>
                        <div style="font-size: 0.8rem; color: var(--gray-500);"><?= sanitize($tl['prenom'] . ' ' . $tl['nom']) ?></div>
                    </div>
                    <span style="color: var(--danger); font-weight: 600;">&#9829; <?= $tl['nb_likes'] ?></span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Activite mensuelle -->
        <div class="card" style="grid-column: 1 / -1;">
            <div class="card-header">
                <h2 style="font-size: 1.1rem; color: var(--primary);">Activite mensuelle (12 derniers mois)</h2>
            </div>
            <div class="card-body">
                <?php if (empty($activiteMensuelle)): ?>
                <p style="text-align: center; color: var(--gray-400); padding: 2rem;">Aucune donnee.</p>
                <?php else: ?>
                <div style="overflow-x: auto;">
                    <div style="display: flex; align-items: flex-end; gap: 2px; height: 180px; padding: 1rem 0.5rem 0; min-width: 600px;">
                        <?php 
                        $maxVal = max(array_column($activiteMensuelle, 'total'));
                        foreach ($activiteMensuelle as $am): 
                            $height = $maxVal > 0 ? max(8, ($am['total'] / $maxVal) * 140) : 8;
                        ?>
                        <div style="flex: 1; display: flex; flex-direction: column; align-items: center; gap: 4px;">
                            <span style="font-size: 0.7rem; font-weight: 700; color: var(--primary);"><?= $am['total'] ?></span>
                            <div style="width: 100%; max-width: 50px; height: <?= $height ?>px; background: linear-gradient(180deg, var(--accent) 0%, var(--primary) 100%); border-radius: 4px 4px 0 0;"></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="display: flex; gap: 2px; padding: 0 0.5rem; border-top: 1px solid var(--gray-200); min-width: 600px;">
                        <?php foreach ($activiteMensuelle as $am): ?>
                        <div style="flex: 1; text-align: center; padding: 0.5rem 0;">
                            <span style="font-size: 0.7rem; color: var(--gray-500);"><?= substr($am['mois'], 5) ?>/<?= substr($am['mois'], 2, 2) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
