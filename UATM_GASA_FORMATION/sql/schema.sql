-- =====================================================
-- BASE DE DONNÉES : UATM GASA FORMATION
-- Gestion des Mémoires Universitaires
-- Compatible phpMyAdmin / MySQL 8.0+
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

-- Insertion des rôles par défaut
INSERT INTO `roles` (`nom`, `description`) VALUES
('administrateur', 'Gestion complète du système'),
('directeur', 'Supervision et validation finale'),
('professeur', 'Évaluation et validation des mémoires'),
('etudiant', 'Dépot et consultation des mémoires');

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

-- =====================================================
-- TABLE : departements
-- =====================================================
CREATE TABLE `departements` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nom` VARCHAR(150) NOT NULL,
    `code` VARCHAR(20) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `statut` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_departements_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    `professeur_id` INT UNSIGNED DEFAULT NULL,
    `filiere_id` INT UNSIGNED NOT NULL,
    `titre` VARCHAR(500) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `fichier_pdf` VARCHAR(255) NOT NULL,
    `taille_fichier` INT UNSIGNED DEFAULT NULL,
    `annee_academique` VARCHAR(20) NOT NULL,
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
-- COMPTE ADMINISTRATEUR PAR DÉFAUT
-- =====================================================
INSERT INTO `utilisateurs` (`role_id`, `nom`, `prenom`, `email`, `password`, `statut`)
VALUES (
    1,
    'Admin',
    'Systeme',
    'admin@uatm-gasa.cd',
    '$2y$10$YourHashedPasswordHere12345678901234567890123456',
    'actif'
);
