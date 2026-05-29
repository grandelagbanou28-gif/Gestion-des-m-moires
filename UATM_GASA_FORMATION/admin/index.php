<?php
$pageTitle = 'Administration - UATM GASA FORMATION';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../auth/session.php';

if (!isLoggedIn() || !hasRole('administrateur')) {
    redirect($baseUrl . 'auth/login.php');
}

$db = getDBConnection();

// Statistiques globales
$stats = [];
$stmt = $db->query("SELECT COUNT(*) FROM utilisateurs WHERE statut = 'actif'");
$stats['utilisateurs'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM memoires");
$stats['memoires_total'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM memoires WHERE statut = 'valide'");
$stats['valides'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM memoires WHERE statut = 'soumis'");
$stats['en_attente'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM filieres WHERE statut = 'active'");
$stats['filieres'] = $stmt->fetchColumn();

// Répartition par rôle
$roles = $db->query("
    SELECT r.nom, COUNT(u.id) as total 
    FROM roles r 
    LEFT JOIN utilisateurs u ON r.id = u.role_id AND u.statut = 'actif'
    GROUP BY r.id, r.nom
")->fetchAll();

// Derniers utilisateurs
$derniersUsers = $db->query("
    SELECT u.*, r.nom as role_nom 
    FROM utilisateurs u 
    JOIN roles r ON u.role_id = r.id 
    ORDER BY u.created_at DESC 
    LIMIT 5
")->fetchAll();

// Dernières actions
$dernieresActions = $db->query("
    SELECT h.*, u.nom, u.prenom 
    FROM historiques h 
    JOIN utilisateurs u ON h.utilisateur_id = u.id 
    ORDER BY h.created_at DESC 
    LIMIT 8
")->fetchAll();
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1 class="dashboard-title">Administration</h1>
    </div>

    <div class="dashboard-stats">
        <div class="dash-stat-card">
            <h3>Utilisateurs</h3>
            <div class="value"><?= $stats['utilisateurs'] ?></div>
        </div>
        <div class="dash-stat-card" style="border-left-color: var(--secondary)">
            <h3>Mémoires total</h3>
            <div class="value" style="color: var(--secondary)"><?= $stats['memoires_total'] ?></div>
        </div>
        <div class="dash-stat-card" style="border-left-color: var(--success)">
            <h3>Validés</h3>
            <div class="value" style="color: var(--success)"><?= $stats['valides'] ?></div>
        </div>
        <div class="dash-stat-card" style="border-left-color: var(--warning)">
            <h3>En attente</h3>
            <div class="value" style="color: var(--warning)"><?= $stats['en_attente'] ?></div>
        </div>
        <div class="dash-stat-card" style="border-left-color: var(--info)">
            <h3>Filières actives</h3>
            <div class="value" style="color: var(--info)"><?= $stats['filieres'] ?></div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
        <!-- Répartition par rôle -->
        <div class="card">
            <div class="card-header">
                <h2 style="font-size: 1.1rem; color: var(--primary);">Répartition par rôle</h2>
            </div>
            <div class="card-body">
                <?php foreach ($roles as $r): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.6rem 0; border-bottom: 1px solid var(--gray-100);">
                    <span style="font-size: 0.9rem; text-transform: capitalize;"><?= sanitize($r['nom']) ?></span>
                    <span style="font-weight: 700; color: var(--primary);"><?= $r['total'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Derniers utilisateurs -->
        <div class="card">
            <div class="card-header">
                <h2 style="font-size: 1.1rem; color: var(--primary);">Derniers utilisateurs</h2>
                <a href="utilisateurs.php" class="btn btn-sm btn-outline">Voir tout</a>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php foreach ($derniersUsers as $u): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.6rem 1rem; border-bottom: 1px solid var(--gray-100);">
                    <div>
                        <div style="font-size: 0.9rem; font-weight: 500;"><?= sanitize($u['prenom'] . ' ' . $u['nom']) ?></div>
                        <div style="font-size: 0.8rem; color: var(--gray-400);"><?= sanitize($u['email']) ?></div>
                    </div>
                    <span style="font-size: 0.75rem; background: var(--gray-100); padding: 0.2rem 0.5rem; border-radius: 12px; text-transform: capitalize;"><?= sanitize($u['role_nom']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Dernières actions -->
        <div class="card" style="grid-column: 1 / -1;">
            <div class="card-header">
                <h2 style="font-size: 1.1rem; color: var(--primary);">Dernières actions système</h2>
                <a href="historique.php" class="btn btn-sm btn-outline">Voir tout</a>
            </div>
            <div class="card-body" style="padding: 0;">
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Utilisateur</th>
                            <th>Action</th>
                            <th>Entité</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dernieresActions as $a): ?>
                        <tr>
                            <td><?= sanitize($a['prenom'] . ' ' . $a['nom']) ?></td>
                            <td><span style="font-size: 0.8rem; background: var(--gray-100); padding: 0.15rem 0.5rem; border-radius: 4px;"><?= sanitize($a['action']) ?></span></td>
                            <td><?= sanitize($a['entite']) ?> #<?= $a['entite_id'] ?? '-' ?></td>
                            <td style="font-size: 0.85rem; color: var(--gray-400);"><?= date('d/m/Y H:i', strtotime($a['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
