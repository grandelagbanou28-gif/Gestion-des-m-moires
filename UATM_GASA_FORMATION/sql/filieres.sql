-- =====================================================
-- INSERTION DES FILIERES UATM GASA FORMATION
-- Universite Africaine de Technologie et de Management
-- =====================================================

INSERT INTO `filieres` (`nom`, `code`, `description`, `statut`) VALUES

-- 1. Sciences Numeriques, Informatique et Technologies
('Systeme Informatique et Logiciel', 'SIL', 'Formation aux systemes informatiques, developpement de logiciels et technologies du web', 'active'),
('Reseaux Informatique et Telecommunication', 'RIT', 'Installation, administration et securisation des reseaux informatiques et telecommunications', 'active'),
('Systeme Industriel', 'SI', 'Electricite, Electronique, Electrotechnique, Automatisme et Energie solaire', 'active'),

-- 2. Sciences de Gestion et Management
('Finance Comptabilite et Audit', 'FCA', 'Formation en finance d\'entreprise, comptabilite et audit interne/externe', 'active'),
('Banque Finance Assurance', 'BFA', 'Metiers de la banque, de la finance et du secteur des assurances', 'active'),
('Management des Ressources Humaines', 'MRH', 'Gestion du capital humain, recrutement, formation et development des competences', 'active'),
('Management Communication et Commerce', 'MCC', 'Marketing digital, communication d\'entreprise, commerce international et negociation', 'active'),
('Entrepreneuriat et Gestion des Projets', 'EGP', 'Creation d\'entreprise, planification et management de projets', 'active'),
('Transport et Logistique', 'TL', 'Logistique maritime et terrestre, gestion des transports et supply chain', 'active'),

-- 3. Sciences Agronomiques et Biotechnologies
('Agronomie', 'AGR', 'Production vegetale et animale, gestion des exploitations agricoles', 'active'),
('Biotechnologie', 'BIO', 'Biologie appliquee, biotechnologies industrielles et environnementales', 'active'),

-- 4. Sciences Juridiques et Relations Internationales
('Sciences Juridiques', 'SJ', 'Droit prive, droit public, droit des affaires et droit international', 'active'),
('Communication et Relations Internationales', 'CRI', 'Communication publique, diplomatie et relations internationales', 'active');
