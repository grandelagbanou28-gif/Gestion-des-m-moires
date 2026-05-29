<?php
$pageTitle = 'UATM GASA FORMATION - Accueil';
require_once 'includes/header.php';

// Statistiques publiques
$db = getDBConnection();
$stats = [];
try {
    $stmt = $db->query("SELECT COUNT(*) FROM memoires WHERE statut = 'valide'");
    $stats['memoires'] = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(*) FROM utilisateurs WHERE statut = 'actif'");
    $stats['etudiants'] = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(*) FROM filieres WHERE statut = 'active'");
    $stats['filieres'] = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(*) FROM memoires WHERE statut = 'archive'");
    $stats['archives'] = $stmt->fetchColumn();
} catch (Exception $e) {
    $stats = ['memoires' => 0, 'etudiants' => 0, 'filieres' => 0, 'archives' => 0];
}
?>

<section class="hero">
    <div class="hero-container">
        <h1 class="hero-title">UATM GASA FORMATION</h1>
        <p class="hero-subtitle">Universite Africaine de Technologie et de Management</p>
        <p class="hero-description">
            Université Africaine de Technologie et de Management — Le générateur d'avenir depuis 1992.
            Déposez, consultez et gérez les mémoires de fin d'études de manière numérique.
        </p>
        <div class="hero-buttons">
            <?php if (isLoggedIn()): ?>
                <a href="dashboard/" class="btn btn-primary btn-lg">Accéder au Dashboard</a>
            <?php else: ?>
                <a href="auth/login.php" class="btn btn-primary btn-lg">Se connecter</a>
                <a href="auth/register.php" class="btn btn-outline btn-lg">Créer un compte</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="stats-section">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['memoires'] ?></div>
                <div class="stat-label">Mémoires validés</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['etudiants'] ?></div>
                <div class="stat-label">Utilisateurs actifs</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['filieres'] ?></div>
                <div class="stat-label">Filières</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['archives'] ?></div>
                <div class="stat-label">Archives</div>
            </div>
        </div>
    </div>
</section>

<section class="features-section">
    <div class="container">
        <h2 class="section-title">Fonctionnalités</h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">&#128196;</div>
                <h3>Dépôt numérique</h3>
                <p>Déposez vos mémoires en format PDF de manière sécurisée.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">&#9989;</div>
                <h3>Validation académique</h3>
                <p>Suivi en temps réel de la validation par les professeurs.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">&#128269;</div>
                <h3>Recherche avancée</h3>
                <p>Trouvez rapidement les mémoires par titre, auteur ou filière.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">&#128451;</div>
                <h3>Archivage sécurisé</h3>
                <p>Archives numériques pérennes et accessibles à tout moment.</p>
            </div>
        </div>
    </div>
</section>

<section class="contact-section">
    <div class="container">
        <h2 class="section-title">Contactez-nous</h2>
        <div class="contact-grid">
            <div class="contact-card">
                <div class="contact-icon-large">&#128222;</div>
                <h3>Téléphone</h3>
                <p>+229 65 78 77 21</p>
                <p>(Cotonou &amp; Abomey-Calavi)</p>
                <p style="margin-top: 0.5rem;">+229 95 42 98 18</p>
                <p>(Porto-Novo)</p>
            </div>
            <div class="contact-card">
                <div class="contact-icon-large">&#9993;</div>
                <h3>Email</h3>
                <p><a href="mailto:info@uatm-gasa.com">info@uatm-gasa.com</a></p>
            </div>
            <div class="contact-card">
                <div class="contact-icon-large">&#127968;</div>
                <h3>Campus</h3>
                <p><strong>Cotonou :</strong> Gbégamey, Agla, Akpakpa</p>
                <p><strong>Abomey-Calavi :</strong> Annexe</p>
                <p><strong>Porto-Novo :</strong> Dangbéklounon</p>
            </div>
            <div class="contact-card">
                <div class="contact-icon-large">&#127760;</div>
                <h3>Site Web</h3>
                <p><a href="http://www.uatm-gasa.com" target="_blank" rel="noopener">www.uatm-gasa.com</a></p>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
