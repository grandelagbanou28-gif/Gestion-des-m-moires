-- =====================================================
-- BASE DE DONNEES : UATM GASA FORMATION
-- Installation complete pour InfinityFree
-- Importer directement dans phpMyAdmin
-- =====================================================

CREATE DATABASE IF NOT EXISTS `uatm_gasa_memoires`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `uatm_gasa_memoires`;

-- =====================================================
-- TABLE : roles
-- =====================================================
CREATE TABLE `roles` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nom` VARCHAR(50) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_roles_nom` (`nom`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`nom`, `description`) VALUES
('administrateur', 'Gestion complete du systeme'),
('directeur', 'Supervision et validation finale'),
('professeur', 'Evaluation et validation des memoires'),
('etudiant', 'Depot et consultation des memoires');

-- =====================================================
-- TABLE : filieres
-- =====================================================
CREATE TABLE `filieres` (
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

INSERT INTO `filieres` (`nom`, `code`, `description`, `statut`) VALUES
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
('Communication et Relations Internationales', 'CRI', 'Communication publique, diplomatie et relations internationales', 'active');

-- =====================================================
-- TABLE : utilisateurs
-- =====================================================
CREATE TABLE `utilisateurs` (
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

-- =====================================================
-- TABLE : memoires
-- =====================================================
CREATE TABLE `memoires` (
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

-- =====================================================
-- TABLE : validations
-- =====================================================
CREATE TABLE `validations` (
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

-- =====================================================
-- TABLE : commentaires
-- =====================================================
CREATE TABLE `commentaires` (
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

-- =====================================================
-- TABLE : likes
-- =====================================================
CREATE TABLE `likes` (
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

-- =====================================================
-- TABLE : notifications
-- =====================================================
CREATE TABLE `notifications` (
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

-- =====================================================
-- TABLE : rapports
-- =====================================================
CREATE TABLE `rapports` (
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

-- =====================================================
-- TABLE : historiques
-- =====================================================
CREATE TABLE `historiques` (
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

-- =====================================================
-- TABLE : archives
-- =====================================================
CREATE TABLE `archives` (
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

-- =====================================================
-- COMPTES PAR DEFAUT
-- =====================================================
-- Mot de passe admin123 | directeur123 | prof123

INSERT INTO `utilisateurs` (`role_id`, `nom`, `prenom`, `email`, `password`, `statut`) VALUES
(1, 'Admin', 'Systeme', 'admin@uatm-gasa.cd', '$2y$10$Goi6mpH5CqW2n3PY215Eweikfy2.vL4T2N2poEO0rYL1m4zcl2HIy', 'actif'),
(2, 'Directeur', 'General', 'directeur@uatm-gasa.com', '$2y$10$Jrpv.BsjjNN616BJKLLekeAALC.NEauk3WWKxQQ0Pbec.0RBzbEQm', 'actif');

INSERT INTO `utilisateurs` (`role_id`, `nom`, `prenom`, `email`, `password`, `statut`) VALUES
(3, 'AHAMAVI', 'Tilak', 'ahamavi.tilak@uatm-gasa.com', '$2y$10$ighBL5xFLxaKF38LYv8vL.tgt07eM86jEMqy2ZbAY1AgLYCBFNn5m', 'actif'),
(3, 'GBAGUIDI', 'Cyrille', 'gbaguidi.cyrille@uatm-gasa.com', '$2y$10$ighBL5xFLxaKF38LYv8vL.tgt07eM86jEMqy2ZbAY1AgLYCBFNn5m', 'actif'),
(3, 'HOUDEKPE', 'Alain', 'houdekpe.alain@uatm-gasa.com', '$2y$10$ighBL5xFLxaKF38LYv8vL.tgt07eM86jEMqy2ZbAY1AgLYCBFNn5m', 'actif'),
(3, 'AHONSI', 'Rodrigue', 'ahonsi.rodrigue@uatm-gasa.com', '$2y$10$ighBL5xFLxaKF38LYv8vL.tgt07eM86jEMqy2ZbAY1AgLYCBFNn5m', 'actif'),
(3, 'ASSOUMA', 'Innocent', 'assouma.innocent@uatm-gasa.com', '$2y$10$ighBL5xFLxaKF38LYv8vL.tgt07eM86jEMqy2ZbAY1AgLYCBFNn5m', 'actif'),
(3, 'TOGNIN', 'Chantal', 'tognin.chantal@uatm-gasa.com', '$2y$10$ighBL5xFLxaKF38LYv8vL.tgt07eM86jEMqy2ZbAY1AgLYCBFNn5m', 'actif'),
(3, 'DEGBEY', 'Christian', 'degbey.christian@uatm-gasa.com', '$2y$10$ighBL5xFLxaKF38LYv8vL.tgt07eM86jEMqy2ZbAY1AgLYCBFNn5m', 'actif'),
(3, 'SENON', 'Arnaud', 'senon.arnaud@uatm-gasa.com', '$2y$10$ighBL5xFLxaKF38LYv8vL.tgt07eM86jEMqy2ZbAY1AgLYCBFNn5m', 'actif'),
(3, 'KIKI', 'Gustin', 'kiki.gustin@uatm-gasa.com', '$2y$10$ighBL5xFLxaKF38LYv8vL.tgt07eM86jEMqy2ZbAY1AgLYCBFNn5m', 'actif'),
(3, 'DOSSA', 'Gildas', 'dossa.gildas@uatm-gasa.com', '$2y$10$ighBL5xFLxaKF38LYv8vL.tgt07eM86jEMqy2ZbAY1AgLYCBFNn5m', 'actif'),
(3, 'ADJAVON', 'Bruno', 'adjavon.bruno@uatm-gasa.com', '$2y$10$ighBL5xFLxaKF38LYv8vL.tgt07eM86jEMqy2ZbAY1AgLYCBFNn5m', 'actif'),
(3, 'HOUNGBEDJI', 'Jean', 'houngbedji.jean@uatm-gasa.com', '$2y$10$ighBL5xFLxaKF38LYv8vL.tgt07eM86jEMqy2ZbAY1AgLYCBFNn5m', 'actif'),
(3, 'MISSON', 'Elvis', 'misson.elvis@uatm-gasa.com', '$2y$10$ighBL5xFLxaKF38LYv8vL.tgt07eM86jEMqy2ZbAY1AgLYCBFNn5m', 'actif'),
(3, 'SINZOHENDON', 'Patrice', 'sinzohendon.patrice@uatm-gasa.com', '$2y$10$ighBL5xFLxaKF38LYv8vL.tgt07eM86jEMqy2ZbAY1AgLYCBFNn5m', 'actif'),
(3, 'AGBODJAN', 'Fortunat', 'agbodjan.fortunat@uatm-gasa.com', '$2y$10$ighBL5xFLxaKF38LYv8vL.tgt07eM86jEMqy2ZbAY1AgLYCBFNn5m', 'actif');

INSERT INTO `utilisateurs` (`role_id`, `nom`, `prenom`, `email`, `password`, `niveau`, `statut`) VALUES
(4, 'LAGBANOU', 'Grandel', 'grandelagbanou2801@gmail.com', '$2y$10$ighBL5xFLxaKF38LYv8vL.tgt07eM86jEMqy2ZbAY1AgLYCBFNn5m', 'M2', 'actif'),
(4, 'TOSSOU', 'Marie', 'marie.tossou@email.com', '$2y$10$ighBL5xFLxaKF38LYv8vL.tgt07eM86jEMqy2ZbAY1AgLYCBFNn5m', 'L3', 'actif'),
(4, 'DAHOU', 'Kevin', 'kevin.dahou@email.com', '$2y$10$ighBL5xFLxaKF38LYv8vL.tgt07eM86jEMqy2ZbAY1AgLYCBFNn5m', 'L3', 'actif'),
(4, 'GNANNOU', 'Sarah', 'sarah.gnannou@email.com', '$2y$10$ighBL5xFLxaKF38LYv8vL.tgt07eM86jEMqy2ZbAY1AgLYCBFNn5m', 'M2', 'actif'),
(4, 'PEREIRA', 'Lucas', 'lucas.pereira@email.com', '$2y$10$ighBL5xFLxaKF38LYv8vL.tgt07eM86jEMqy2ZbAY1AgLYCBFNn5m', 'L1', 'actif');
