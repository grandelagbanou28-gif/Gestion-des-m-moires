<?php
/**
 * INSTALLATEUR UATM GASA FORMATION
 * 1. Creer le compte InfinityFree
 * 2. Upload tous les fichiers via le File Manager
 * 3. Ouvre install.php dans ton navigateur
 * 4. Une fois installe, SUPPRIME ce fichier
 */

$host = 'sql308.infinityfree.com';
$user = 'if0_42086197';
$pass = 'test280601';
$dbname = 'if0_42086197_if0_gestiondesmemoires_uatm';

$step = $_GET['step'] ?? 1;
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 2) {
        $host = trim($_POST['db_host'] ?? '');
        $user = trim($_POST['db_user'] ?? '');
        $pass = trim($_POST['db_pass'] ?? '');
        $dbname = trim($_POST['db_name'] ?? '');

        try {
            $pdo = new PDO("mysql:host=$host", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$dbname`");

            // === TABLES ===
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `roles` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `nom` VARCHAR(50) NOT NULL,
                    `description` VARCHAR(255) DEFAULT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uk_roles_nom` (`nom`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `filieres` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `nom` VARCHAR(150) NOT NULL,
                    `code` VARCHAR(20) NOT NULL,
                    `description` TEXT DEFAULT NULL,
                    `statut` ENUM('active', 'inactive') DEFAULT 'active',
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uk_filieres_code` (`code`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `utilisateurs` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `role_id` INT UNSIGNED NOT NULL,
                    `nom` VARCHAR(100) NOT NULL,
                    `prenom` VARCHAR(100) NOT NULL,
                    `email` VARCHAR(255) NOT NULL,
                    `password` VARCHAR(255) NOT NULL,
                    `telephone` VARCHAR(20) DEFAULT NULL,
                    `avatar` VARCHAR(255) DEFAULT NULL,
                    `niveau` VARCHAR(20) DEFAULT NULL,
                    `statut` ENUM('actif', 'inactif', 'suspendu') DEFAULT 'actif',
                    `derniere_connexion` TIMESTAMP NULL DEFAULT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uk_utilisateurs_email` (`email`),
                    KEY `idx_utilisateurs_role` (`role_id`),
                    KEY `idx_utilisateurs_statut` (`statut`),
                    CONSTRAINT `fk_utilisateurs_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `memoires` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `etudiant_id` INT UNSIGNED NOT NULL,
                    `co_auteur_id` INT UNSIGNED DEFAULT NULL,
                    `professeur_id` INT UNSIGNED DEFAULT NULL,
                    `filiere_id` INT UNSIGNED NOT NULL,
                    `titre` VARCHAR(500) NOT NULL,
                    `description` TEXT DEFAULT NULL,
                    `fichier_pdf` VARCHAR(255) NOT NULL,
                    `taille_fichier` INT UNSIGNED DEFAULT NULL,
                    `annee_academique` VARCHAR(20) NOT NULL,
                    `type_travail` ENUM('individuel', 'binome') DEFAULT 'individuel',
                    `statut` ENUM('brouillon', 'soumis', 'en_revision', 'valide', 'rejete', 'archive') DEFAULT 'brouillon',
                    `date_soumission` TIMESTAMP NULL DEFAULT NULL,
                    `date_validation` TIMESTAMP NULL DEFAULT NULL,
                    `note_finale` DECIMAL(4,2) DEFAULT NULL,
                    `mot_cles` VARCHAR(500) DEFAULT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_memoires_etudiant` (`etudiant_id`),
                    KEY `idx_memoires_professeur` (`professeur_id`),
                    KEY `idx_memoires_filiere` (`filiere_id`),
                    KEY `idx_memoires_statut` (`statut`),
                    KEY `idx_memoires_annee` (`annee_academique`),
                    FULLTEXT KEY `ft_memoires_recherche` (`titre`, `description`, `mot_cles`),
                    CONSTRAINT `fk_memoires_etudiant` FOREIGN KEY (`etudiant_id`) REFERENCES `utilisateurs` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
                    CONSTRAINT `fk_memoires_professeur` FOREIGN KEY (`professeur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
                    CONSTRAINT `fk_memoires_filiere` FOREIGN KEY (`filiere_id`) REFERENCES `filieres` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `validations` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `memoire_id` INT UNSIGNED NOT NULL,
                    `validateur_id` INT UNSIGNED NOT NULL,
                    `decision` ENUM('en_attente', 'approuve', 'rejete', 'revision') DEFAULT 'en_attente',
                    `commentaire` TEXT DEFAULT NULL,
                    `note` DECIMAL(4,2) DEFAULT NULL,
                    `date_validation` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_validations_memoire` (`memoire_id`),
                    KEY `idx_validations_validateur` (`validateur_id`),
                    CONSTRAINT `fk_validations_memoire` FOREIGN KEY (`memoire_id`) REFERENCES `memoires` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT `fk_validations_validateur` FOREIGN KEY (`validateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `commentaires` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `memoire_id` INT UNSIGNED NOT NULL,
                    `auteur_id` INT UNSIGNED NOT NULL,
                    `contenu` TEXT NOT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_commentaires_memoire` (`memoire_id`),
                    KEY `idx_commentaires_auteur` (`auteur_id`),
                    CONSTRAINT `fk_commentaires_memoire` FOREIGN KEY (`memoire_id`) REFERENCES `memoires` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT `fk_commentaires_auteur` FOREIGN KEY (`auteur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `likes` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `memoire_id` INT UNSIGNED NOT NULL,
                    `utilisateur_id` INT UNSIGNED NOT NULL,
                    `type` ENUM('like', 'dislike') DEFAULT 'like',
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uk_likes_unique` (`memoire_id`, `utilisateur_id`),
                    KEY `idx_likes_utilisateur` (`utilisateur_id`),
                    CONSTRAINT `fk_likes_memoire` FOREIGN KEY (`memoire_id`) REFERENCES `memoires` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT `fk_likes_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `notifications` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `utilisateur_id` INT UNSIGNED NOT NULL,
                    `titre` VARCHAR(255) NOT NULL,
                    `message` TEXT NOT NULL,
                    `type` ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
                    `lu` TINYINT(1) DEFAULT 0,
                    `lien` VARCHAR(255) DEFAULT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_notifications_utilisateur` (`utilisateur_id`),
                    KEY `idx_notifications_lu` (`lu`),
                    CONSTRAINT `fk_notifications_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `rapports` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `titre` VARCHAR(255) NOT NULL,
                    `type_rapport` ENUM('statistiques', 'validation', 'activite', 'general') NOT NULL,
                    `auteur_id` INT UNSIGNED NOT NULL,
                    `contenu` TEXT DEFAULT NULL,
                    `date_debut` DATE DEFAULT NULL,
                    `date_fin` DATE DEFAULT NULL,
                    `fichier` VARCHAR(255) DEFAULT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_rapports_auteur` (`auteur_id`),
                    KEY `idx_rapports_type` (`type_rapport`),
                    CONSTRAINT `fk_rapports_auteur` FOREIGN KEY (`auteur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `historiques` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `utilisateur_id` INT UNSIGNED NOT NULL,
                    `action` VARCHAR(100) NOT NULL,
                    `entite` VARCHAR(50) NOT NULL,
                    `entite_id` INT UNSIGNED DEFAULT NULL,
                    `details` TEXT DEFAULT NULL,
                    `ip_address` VARCHAR(45) DEFAULT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_historiques_utilisateur` (`utilisateur_id`),
                    KEY `idx_historiques_entite` (`entite`, `entite_id`),
                    KEY `idx_historiques_date` (`created_at`),
                    CONSTRAINT `fk_historiques_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `archives` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `memoire_id` INT UNSIGNED NOT NULL,
                    `archive_par` INT UNSIGNED NOT NULL,
                    `raison` TEXT DEFAULT NULL,
                    `date_archivage` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `statut` ENUM('archive', 'restaure') DEFAULT 'archive',
                    PRIMARY KEY (`id`),
                    KEY `idx_archives_memoire` (`memoire_id`),
                    KEY `idx_archives_archive_par` (`archive_par`),
                    CONSTRAINT `fk_archives_memoire` FOREIGN KEY (`memoire_id`) REFERENCES `memoires` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT `fk_archives_archive_par` FOREIGN KEY (`archive_par`) REFERENCES `utilisateurs` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // === ROLES ===
            $pdo->exec("DELETE FROM `roles`");
            $pdo->exec("INSERT INTO `roles` (`nom`, `description`) VALUES
                ('administrateur', 'Gestion complete du systeme'),
                ('directeur', 'Supervision et validation finale'),
                ('professeur', 'Evaluation et validation des memoires'),
                ('etudiant', 'Depot et consultation des memoires')
            ");

            // === FILIERES ===
            $pdo->exec("DELETE FROM `filieres`");
            $pdo->exec("INSERT INTO `filieres` (`nom`, `code`, `description`, `statut`) VALUES
                ('Systeme Informatique et Logiciel', 'SIL', 'Formation aux systemes informatiques, developpement de logiciels et technologies du web', 'active'),
                ('Reseaux Informatique et Telecommunication', 'RIT', 'Installation, administration et securisation des reseaux informatiques et telecommunications', 'active'),
                ('Systeme Industriel', 'SI', 'Electricite, Electronique, Electrotechnique, Automatisme et Energie solaire', 'active'),
                ('Finance Comptabilite et Audit', 'FCA', 'Formation en finance d entreprise, comptabilite et audit interne/externe', 'active'),
                ('Banque Finance Assurance', 'BFA', 'Metiers de la banque, de la finance et du secteur des assurances', 'active'),
                ('Management des Ressources Humaines', 'MRH', 'Gestion du capital humain, recrutement, formation et developpement des competences', 'active'),
                ('Management Communication et Commerce', 'MCC', 'Marketing digital, communication d entreprise, commerce international et negociation', 'active'),
                ('Entrepreneuriat et Gestion des Projets', 'EGP', 'Creation d entreprise, planification et management de projets', 'active'),
                ('Transport et Logistique', 'TL', 'Logistique maritime et terrestre, gestion des transports et supply chain', 'active'),
                ('Agronomie', 'AGR', 'Production vegetale et animale, gestion des exploitations agricoles', 'active'),
                ('Biotechnologie', 'BIO', 'Biologie appliquee, biotechnologies industrielles et environnementales', 'active'),
                ('Sciences Juridiques', 'SJ', 'Droit prive, droit public, droit des affaires et droit international', 'active'),
                ('Communication et Relations Internationales', 'CRI', 'Communication publique, diplomatie et relations internationales', 'active')
            ");

            // === COMPTES ===
            $passAdmin = password_hash('admin123', PASSWORD_DEFAULT);
            $passDir = password_hash('directeur123', PASSWORD_DEFAULT);
            $passProf = password_hash('prof123', PASSWORD_DEFAULT);

            $pdo->exec("DELETE FROM `utilisateurs`");
            $stmt = $pdo->prepare("INSERT INTO `utilisateurs` (`role_id`, `nom`, `prenom`, `email`, `password`, `statut`) VALUES (?, ?, ?, ?, ?, 'actif')");

            $stmt->execute([1, 'Admin', 'Systeme', 'admin@uatm-gasa.cd', $passAdmin]);
            $stmt->execute([2, 'Directeur', 'General', 'directeur@uatm-gasa.com', $passDir]);

            $profs = [
                ['AHAMAVI', 'Tilak', 'ahamavi.tilak@uatm-gasa.com'],
                ['GBAGUIDI', 'Cyrille', 'gbaguidi.cyrille@uatm-gasa.com'],
                ['HOUDEKPE', 'Alain', 'houdekpe.alain@uatm-gasa.com'],
                ['AHONSI', 'Rodrigue', 'ahonsi.rodrigue@uatm-gasa.com'],
                ['ASSOUMA', 'Innocent', 'assouma.innocent@uatm-gasa.com'],
                ['TOGNIN', 'Chantal', 'tognin.chantal@uatm-gasa.com'],
                ['DEGBEY', 'Christian', 'degbey.christian@uatm-gasa.com'],
                ['SENON', 'Arnaud', 'senon.arnaud@uatm-gasa.com'],
                ['KIKI', 'Gustin', 'kiki.gustin@uatm-gasa.com'],
                ['DOSSA', 'Gildas', 'dossa.gildas@uatm-gasa.com'],
                ['ADJAVON', 'Bruno', 'adjavon.bruno@uatm-gasa.com'],
                ['HOUNGBEDJI', 'Jean', 'houngbedji.jean@uatm-gasa.com'],
                ['MISSON', 'Elvis', 'misson.elvis@uatm-gasa.com'],
                ['SINZOHENDON', 'Patrice', 'sinzohendon.patrice@uatm-gasa.com'],
                ['AGBODJAN', 'Fortunat', 'agbodjan.fortunat@uatm-gasa.com'],
            ];
            foreach ($profs as $p) {
                $stmt->execute([3, $p[0], $p[1], $p[2], $passProf]);
            }

            $etudiants = [
                ['LAGBANOU', 'Grandel', 'grandelagbanou2801@gmail.com', 'M2'],
                ['TOSSOU', 'Marie', 'marie.tossou@email.com', 'L3'],
                ['DAHOU', 'Kevin', 'kevin.dahou@email.com', 'L3'],
                ['GNANNOU', 'Sarah', 'sarah.gnannou@email.com', 'M2'],
                ['PEREIRA', 'Lucas', 'lucas.pereira@email.com', 'L1'],
            ];
            $stmtEtud = $pdo->prepare("INSERT INTO `utilisateurs` (`role_id`, `nom`, `prenom`, `email`, `password`, `niveau`, `statut`) VALUES (4, ?, ?, ?, ?, ?, 'actif')");
            foreach ($etudiants as $e) {
                $stmtEtud->execute([$e[0], $e[1], $e[2], $passProf, $e[3]]);
            }

            // Sauvegarder la config
            $configContent = "<?php\n";
            $configContent .= "define('DB_HOST', " . var_export($host, true) . ");\n";
            $configContent .= "define('DB_NAME', " . var_export($dbname, true) . ");\n";
            $configContent .= "define('DB_USER', " . var_export($user, true) . ");\n";
            $configContent .= "define('DB_PASS', " . var_export($pass, true) . ");\n";
            $configContent .= "define('DB_CHARSET', 'utf8mb4');\n";
            $configContent .= "\nfunction getDBConnection() {\n";
            $configContent .= "    static \$pdo = null;\n";
            $configContent .= "    if (\$pdo === null) {\n";
            $configContent .= "        try {\n";
            $configContent .= '            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;' . "\n";
            $configContent .= "            \$options = [\n";
            $configContent .= "                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,\n";
            $configContent .= "                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n";
            $configContent .= "                PDO::ATTR_EMULATE_PREPARES   => false,\n";
            $configContent .= "            ];\n";
            $configContent .= '            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);' . "\n";
            $configContent .= "        } catch (PDOException \$e) {\n";
            $configContent .= '            error_log("Erreur de connexion DB: " . $e->getMessage());' . "\n";
            $configContent .= '            die("Erreur de connexion a la base de donnees.");' . "\n";
            $configContent .= "        }\n";
            $configContent .= "    }\n";
            $configContent .= "    return \$pdo;\n";
            $configContent .= "}\n";

            file_put_contents(__DIR__ . '/config/database.php', $configContent);

            $message = "Installation terminee ! Comptes crees avec succes.";

        } catch (PDOException $e) {
            $error = "Erreur MySQL : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - UATM GASA FORMATION</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f4f8; color: #333; }
        .container { max-width: 600px; margin: 40px auto; padding: 0 20px; }
        .card { background: white; border-radius: 12px; padding: 40px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        h1 { color: #1a3a5c; text-align: center; margin-bottom: 10px; font-size: 1.5rem; }
        .subtitle { text-align: center; color: #666; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 6px; font-weight: 600; color: #1a3a5c; }
        input[type="text"], input[type="password"] {
            width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px;
            font-size: 14px; transition: border-color 0.3s;
        }
        input:focus { outline: none; border-color: #3182ce; }
        .btn {
            width: 100%; padding: 14px; background: #1a3a5c; color: white; border: none;
            border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer;
            transition: background 0.3s;
        }
        .btn:hover { background: #2c5282; }
        .success { background: #c6f6d5; border: 1px solid #9ae6b4; color: #22543d; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .error { background: #fed7d7; border: 1px solid #fc8181; color: #742a2a; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .accounts { background: #ebf8ff; border: 1px solid #90cdf4; padding: 20px; border-radius: 8px; margin-top: 20px; }
        .accounts table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .accounts th, .accounts td { padding: 8px; text-align: left; border-bottom: 1px solid #bee3f8; }
        .accounts th { color: #2b6cb0; }
        .warning { background: #fefcbf; border: 1px solid #f6e05e; color: #744210; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        a { color: #3182ce; }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h1>UATM GASA FORMATION</h1>
        <p class="subtitle">Installation de l'application de gestion des memoires</p>

        <?php if ($message): ?>
            <div class="success">
                <strong>✅ <?= $message ?></strong>
                <p style="margin-top: 10px;">
                    <a href="index.php" style="font-weight:600;">Acceder a l'application →</a>
                </p>
            </div>
            <div class="accounts">
                <h3 style="color:#2b6cb0; margin-bottom:10px;">Comptes crees :</h3>
                <table>
                    <tr><th>Role</th><th>Email</th><th>Mot de passe</th></tr>
                    <tr><td>Admin</td><td>admin@uatm-gasa.cd</td><td>admin123</td></tr>
                    <tr><td>Directeur</td><td>directeur@uatm-gasa.com</td><td>directeur123</td></tr>
                    <tr><td>Professeur</td><td>ahamavi.tilak@uatm-gasa.com</td><td>prof123</td></tr>
                    <tr><td>Etudiant</td><td>grandelagbanou2801@gmail.com</td><td>prof123</td></tr>
                </table>
            </div>
            <div class="warning" style="margin-top: 20px;">
                <strong>⚠️ Important :</strong> Supprimez le fichier <code>install.php</code> apres l'installation pour la securite.
            </div>

        <?php elseif ($error): ?>
            <div class="error"><strong>❌ <?= $error ?></strong></div>
            <p style="text-align:center; margin-top:15px;"><a href="install.php?step=2">← Reessayer</a></p>

        <?php else: ?>
            <form method="POST" action="install.php?step=2">
                <div class="form-group">
                    <label>MySQL Host ( depuis le panel InfinityFree )</label>
                    <input type="text" name="db_host" value="<?= htmlspecialchars($host) ?>" placeholder="sql123.epizy.com" required>
                </div>
                <div class="form-group">
                    <label>MySQL Database Name</label>
                    <input type="text" name="db_name" value="<?= htmlspecialchars($dbname) ?>" required>
                </div>
                <div class="form-group">
                    <label>MySQL Username</label>
                    <input type="text" name="db_user" value="<?= htmlspecialchars($user) ?>" required>
                </div>
                <div class="form-group">
                    <label>MySQL Password</label>
                    <input type="password" name="db_pass" value="<?= htmlspecialchars($pass) ?>" placeholder="Mot de passe BDD">
                </div>
                <button type="submit" class="btn">Installer la base de donnees</button>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
