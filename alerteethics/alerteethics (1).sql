-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : dim. 16 nov. 2025 à 23:40
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `alerteethics`
--

-- --------------------------------------------------------

--
-- Structure de la table `admin_sessions`
--

CREATE TABLE `admin_sessions` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `session_token` varchar(64) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_expiration` timestamp NOT NULL DEFAULT current_timestamp(),
  `actif` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `nom_complet` varchar(100) NOT NULL,
  `role` enum('super_admin','admin','moderateur') DEFAULT 'moderateur',
  `actif` tinyint(1) DEFAULT 1,
  `deux_fa_secret` varchar(32) DEFAULT NULL,
  `dernier_login` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_maj` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `email`, `password_hash`, `nom_complet`, `role`, `actif`, `deux_fa_secret`, `dernier_login`, `date_creation`, `date_maj`) VALUES
(1, 'admin', 'admin@alerteethics.sn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrateur Principal', 'super_admin', 1, NULL, NULL, '2025-11-09 22:05:39', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL,
  `signalement_id` int(11) NOT NULL,
  `action_type` enum('creation','modification_statut','consultation','upload_fichier','acces_suivi') DEFAULT NULL,
  `ancien_statut` varchar(50) DEFAULT NULL,
  `nouveau_statut` varchar(50) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `hash_entree` varchar(64) NOT NULL,
  `hash_precedent` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `pieces_jointes`
--

CREATE TABLE `pieces_jointes` (
  `id` int(11) NOT NULL,
  `signalement_id` int(11) NOT NULL,
  `nom_fichier_chiffre` text NOT NULL,
  `contenu_chiffre` longblob NOT NULL,
  `type_mime` varchar(100) DEFAULT NULL,
  `taille_originale` int(11) DEFAULT NULL,
  `hash_fichier` varchar(64) NOT NULL,
  `iv_chiffrement` varchar(32) NOT NULL,
  `date_upload` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `signalements`
--

CREATE TABLE `signalements` (
  `id` int(11) NOT NULL,
  `contenu_chiffre` text NOT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_maj` timestamp NULL DEFAULT NULL,
  `statut` enum('nouveau','en_cours','traite','cloture','rejete') DEFAULT 'nouveau',
  `blockchain_hash` varchar(66) DEFAULT NULL,
  `blockchain_tx` varchar(66) DEFAULT NULL,
  `type_alerte_id` int(11) DEFAULT NULL,
  `niveau_gravite` int(11) DEFAULT NULL,
  `canal_soumission` enum('web','mobile','sms') DEFAULT 'web',
  `telephone_hash` varchar(64) DEFAULT NULL,
  `iv_chiffrement` varchar(32) NOT NULL,
  `hash_contenu` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déclencheurs `signalements`
--
DELIMITER $$
CREATE TRIGGER `audit_signalement_update` AFTER UPDATE ON `signalements` FOR EACH ROW BEGIN
    IF OLD.statut != NEW.statut THEN
        INSERT INTO audit_log (signalement_id, action_type, ancien_statut, nouveau_statut, details, hash_entree)
        VALUES (NEW.id, 'modification_statut', OLD.statut, NEW.statut, 
                'Statut modifié par le système', 
                SHA2(CONCAT(NEW.id, NOW(), RAND()), 256));
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `suivis`
--

CREATE TABLE `suivis` (
  `code_tracking` varchar(12) NOT NULL,
  `signalement_id` int(11) NOT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `dernier_acces` timestamp NULL DEFAULT NULL,
  `nombre_acces` int(11) DEFAULT 0,
  `cle_dechiffrement_hash` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `cle` varchar(50) NOT NULL,
  `valeur` text NOT NULL,
  `type` enum('string','integer','boolean','json') DEFAULT 'string',
  `description` text DEFAULT NULL,
  `date_maj` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `system_settings`
--

INSERT INTO `system_settings` (`id`, `cle`, `valeur`, `type`, `description`, `date_maj`) VALUES
(1, 'maintenance_mode', 'false', 'boolean', 'Mode maintenance du système', '2025-11-09 22:05:39'),
(2, 'max_file_size', '10485760', 'integer', 'Taille maximale des fichiers en octets', '2025-11-09 22:05:39'),
(3, 'allowed_file_types', '[\"pdf\",\"jpg\",\"jpeg\",\"png\",\"doc\",\"docx\"]', 'json', 'Types de fichiers autorisés', '2025-11-09 22:05:39'),
(4, 'auto_logout_minutes', '30', 'integer', 'Déconnexion automatique après inactivité', '2025-11-09 22:05:39'),
(5, 'blockchain_enabled', 'true', 'boolean', 'Activation/désactivation de la blockchain', '2025-11-09 22:05:39'),
(6, 'sms_enabled', 'true', 'boolean', 'Activation/désactivation des SMS', '2025-11-09 22:05:39'),
(7, 'retention_days', '1095', 'integer', 'Durée de conservation des données en jours', '2025-11-09 22:05:39');

-- --------------------------------------------------------

--
-- Structure de la table `types_alerte`
--

CREATE TABLE `types_alerte` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `gravite_min` int(11) DEFAULT 1,
  `gravite_max` int(11) DEFAULT 5,
  `couleur` varchar(7) DEFAULT '#3498db',
  `actif` tinyint(1) DEFAULT 1,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `types_alerte`
--

INSERT INTO `types_alerte` (`id`, `nom`, `description`, `gravite_min`, `gravite_max`, `couleur`, `actif`, `date_creation`) VALUES
(1, 'Corruption', 'Détournement de fonds, pots-de-vin, favoritisme', 3, 5, '#e74c3c', 1, '2025-11-09 22:05:39'),
(2, 'Harcèlement moral', 'Pression psychologique, intimidation, brimades', 2, 5, '#9b59b6', 1, '2025-11-09 22:05:39'),
(3, 'Harcèlement sexuel', 'Avances non désirées, comportement inapproprié', 3, 5, '#e67e22', 1, '2025-11-09 22:05:39'),
(4, 'Fraude', 'Falsification de documents, tromperie délibérée', 3, 5, '#f1c40f', 1, '2025-11-09 22:05:39'),
(5, 'Discrimination', 'Inégalité de traitement basée sur genre, ethnie, etc.', 2, 4, '#34495e', 1, '2025-11-09 22:05:39'),
(6, 'Conflit d\'intérêts', 'Utilisation de sa position pour un gain personnel', 2, 4, '#1abc9c', 1, '2025-11-09 22:05:39'),
(7, 'Mauvaise gestion', 'Incompétence grave, négligence dans les fonctions', 1, 3, '#3498db', 1, '2025-11-09 22:05:39'),
(8, 'Atteinte à l\'environnement', 'Non-respect des normes environnementales', 2, 5, '#27ae60', 1, '2025-11-09 22:05:39'),
(9, 'Autre manquement éthique', 'Autres situations non couvertes par les catégories', 1, 5, '#95a5a6', 1, '2025-11-09 22:05:39');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `admin_sessions`
--
ALTER TABLE `admin_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Index pour la table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_signalement_id` (`signalement_id`),
  ADD KEY `idx_audit_timestamp` (`timestamp`),
  ADD KEY `idx_audit_action_type` (`action_type`);

--
-- Index pour la table `pieces_jointes`
--
ALTER TABLE `pieces_jointes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pj_signalement_id` (`signalement_id`);

--
-- Index pour la table `signalements`
--
ALTER TABLE `signalements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_signalements_statut` (`statut`),
  ADD KEY `idx_signalements_date_creation` (`date_creation`),
  ADD KEY `idx_signalements_type_alerte` (`type_alerte_id`),
  ADD KEY `idx_signalements_blockchain` (`blockchain_hash`),
  ADD KEY `idx_signalements_gravite` (`niveau_gravite`);

--
-- Index pour la table `suivis`
--
ALTER TABLE `suivis`
  ADD PRIMARY KEY (`code_tracking`),
  ADD KEY `idx_suivis_signalement_id` (`signalement_id`),
  ADD KEY `idx_suivis_date_creation` (`date_creation`);

--
-- Index pour la table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cle` (`cle`);

--
-- Index pour la table `types_alerte`
--
ALTER TABLE `types_alerte`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `admin_sessions`
--
ALTER TABLE `admin_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `pieces_jointes`
--
ALTER TABLE `pieces_jointes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `signalements`
--
ALTER TABLE `signalements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `types_alerte`
--
ALTER TABLE `types_alerte`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `admin_sessions`
--
ALTER TABLE `admin_sessions`
  ADD CONSTRAINT `admin_sessions_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `pieces_jointes`
--
ALTER TABLE `pieces_jointes`
  ADD CONSTRAINT `pieces_jointes_ibfk_1` FOREIGN KEY (`signalement_id`) REFERENCES `signalements` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `signalements`
--
ALTER TABLE `signalements`
  ADD CONSTRAINT `signalements_ibfk_1` FOREIGN KEY (`type_alerte_id`) REFERENCES `types_alerte` (`id`);

--
-- Contraintes pour la table `suivis`
--
ALTER TABLE `suivis`
  ADD CONSTRAINT `suivis_ibfk_1` FOREIGN KEY (`signalement_id`) REFERENCES `signalements` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
