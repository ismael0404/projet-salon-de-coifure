-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : sam. 02 mai 2026 à 22:24
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `tya_stylex`
--
CREATE DATABASE IF NOT EXISTS `tya_stylex`;
USE `tya_stylex`;

DELIMITER $$
--
-- Procédures
--
CREATE PROCEDURE `update_niveau_fidelite` (IN `p_id_cliente` INT)   BEGIN
    DECLARE nb_rdv INT;
    DECLARE nouveau_niveau INT;
    DECLARE nouveau_taux INT;
    
    
    SELECT COUNT(*) INTO nb_rdv 
    FROM rendez_vous 
    WHERE id_cliente = p_id_cliente AND statut = 'termine';
    
    
    IF nb_rdv >= 10 THEN
        SET nouveau_niveau = 2;
        SET nouveau_taux = 20;
    ELSEIF nb_rdv >= 5 THEN
        SET nouveau_niveau = 1;
        SET nouveau_taux = 10;
    ELSE
        SET nouveau_niveau = 0;
        SET nouveau_taux = 0;
    END IF;
    
    
    UPDATE clientes 
    SET nombre_rendezvous = nb_rdv,
        niveau_fidelite = nouveau_niveau,
        reduction = nouveau_taux
    WHERE id_cliente = p_id_cliente;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `avis`
--

CREATE TABLE `avis` (
  `id_avis` int(11) NOT NULL,
  `id_rdv` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `note` int(11) DEFAULT NULL CHECK (`note` >= 1 and `note` <= 5),
  `commentaire` text DEFAULT NULL,
  `date_avis` datetime DEFAULT current_timestamp(),
  `statut` enum('visible','masque') DEFAULT 'visible',
  `reponse_admin` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `clientes`
--

CREATE TABLE `clientes` (
  `id_cliente` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `nombre_rendezvous` int(11) DEFAULT 0,
  `niveau_fidelite` int(11) DEFAULT 0,
  `reduction` int(11) DEFAULT 0,
  `date_dernier_rdv` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `clientes`
--

INSERT INTO `clientes` (`id_cliente`, `id_utilisateur`, `nombre_rendezvous`, `niveau_fidelite`, `reduction`, `date_dernier_rdv`) VALUES
(1, 3, 0, 0, 0, NULL),
(2, 4, 0, 0, 0, NULL),
(3, 5, 0, 0, 0, NULL),
(4, 6, 0, 0, 0, NULL),
(5, 7, 0, 0, 0, NULL),
(6, 8, 0, 0, 0, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `employes`
--

CREATE TABLE `employes` (
  `id_employe` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `specialite` varchar(100) DEFAULT NULL,
  `disponibilite` enum('disponible','occupe','conges','absente') DEFAULT 'disponible',
  `horaire_debut` time DEFAULT '08:00:00',
  `horaire_fin` time DEFAULT '18:00:00',
  `commission` decimal(5,2) DEFAULT 0.00,
  `date_embauche` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `employes`
--

INSERT INTO `employes` (`id_employe`, `id_utilisateur`, `specialite`, `disponibilite`, `horaire_debut`, `horaire_fin`, `commission`, `date_embauche`) VALUES
(1, 2, 'Coupe - Brushing - Tresses', 'disponible', '08:00:00', '18:00:00', 0.00, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `fidelite_parametres`
--

CREATE TABLE `fidelite_parametres` (
  `id_param` int(11) NOT NULL,
  `niveau` int(11) NOT NULL,
  `seuil_min` int(11) NOT NULL,
  `seuil_max` int(11) DEFAULT NULL,
  `taux_reduction` int(11) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `fidelite_parametres`
--

INSERT INTO `fidelite_parametres` (`id_param`, `niveau`, `seuil_min`, `seuil_max`, `taux_reduction`, `description`) VALUES
(1, 0, 0, 4, 0, 'Nouvelle cliente - aucun avantage'),
(2, 1, 5, 9, 10, 'Cliente fidèle - 10% de réduction'),
(3, 2, 10, NULL, 20, 'Cliente très fidèle - 20% de réduction');

-- --------------------------------------------------------

--
-- Structure de la table `log_activites`
--

CREATE TABLE `log_activites` (
  `id_log` int(11) NOT NULL,
  `id_utilisateur` int(11) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_adresse` varchar(45) DEFAULT NULL,
  `date_action` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `planning`
--

CREATE TABLE `planning` (
  `id_planning` int(11) NOT NULL,
  `id_employe` int(11) NOT NULL,
  `date_planning` date NOT NULL,
  `heure_debut` time DEFAULT NULL,
  `heure_fin` time DEFAULT NULL,
  `pause_debut` time DEFAULT NULL,
  `pause_fin` time DEFAULT NULL,
  `est_disponible` enum('oui','non') DEFAULT 'oui',
  `remarque` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `rendez_vous`
--

CREATE TABLE `rendez_vous` (
  `id_rdv` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_employe` int(11) NOT NULL,
  `id_service` int(11) NOT NULL,
  `date_rdv` date NOT NULL,
  `heure_rdv` time NOT NULL,
  `duree` int(11) NOT NULL,
  `prix_total` decimal(10,2) NOT NULL,
  `reduction_appliquee` int(11) DEFAULT 0,
  `statut` enum('en_attente','confirme','refuse','termine','annule') DEFAULT 'en_attente',
  `commentaire` text DEFAULT NULL,
  `date_reservation` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déclencheurs `rendez_vous`
--
DELIMITER $$
CREATE TRIGGER `after_annulation_rdv` AFTER UPDATE ON `rendez_vous` FOR EACH ROW BEGIN
    IF NEW.statut = 'annule' AND OLD.statut != 'annule' THEN
        INSERT INTO log_activites (id_utilisateur, action, description)
        SELECT NEW.id_cliente, 'Annulation RDV', CONCAT('Annulation du RDV n°', NEW.id_rdv)
        FROM clientes c
        WHERE c.id_cliente = NEW.id_cliente;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `services`
--

CREATE TABLE `services` (
  `id_service` int(11) NOT NULL,
  `nom_service` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `duree` int(11) NOT NULL,
  `prix_standard` decimal(10,2) NOT NULL,
  `categorie` varchar(50) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `statut` enum('actif','inactif') DEFAULT 'actif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `services`
--

INSERT INTO `services` (`id_service`, `nom_service`, `description`, `duree`, `prix_standard`, `categorie`, `image`, `statut`) VALUES
(1, 'Coupe femme', 'Coupe personnalisée selon votre morphologie', 45, 5000.00, 'Coupe', NULL, 'actif'),
(2, 'Tresse classique', 'Tresses classiques réalisées avec soin', 120, 2000.00, 'Tresses', NULL, 'actif'),
(3, 'Tresse mèches', 'Tresses avec ajout de mèches colorées', 150, 3500.00, 'Tresses', NULL, 'actif'),
(4, 'Shampoing + Brushing', 'Lavage et brushing professionnel', 60, 4000.00, 'Soins', NULL, 'actif'),
(5, 'Coloration', 'Coloration complète sans ammoniaque', 90, 8000.00, 'Couleur', NULL, 'actif'),
(6, 'Mèches', 'Mèches fines ou épaisses', 120, 10000.00, 'Couleur', NULL, 'actif'),
(7, 'Lissage brésilien', 'Lissage à la kératine', 180, 15000.00, 'Soins', NULL, 'actif'),
(8, 'Manucure', 'Soin des mains et pose vernis', 45, 3000.00, 'Onglerie', NULL, 'actif');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `id_utilisateur` int(11) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `prenom` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `role` enum('client','employe','admin') DEFAULT 'client',
  `statut` enum('actif','inactif','suspendu') DEFAULT 'actif',
  `date_inscription` datetime DEFAULT current_timestamp(),
  `derniere_connexion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id_utilisateur`, `nom`, `prenom`, `email`, `mot_de_passe`, `telephone`, `adresse`, `role`, `statut`, `date_inscription`, `derniere_connexion`) VALUES
(1, 'Admin', 'Système', 'admin@tyastylex.com', '0192023a7bbd73250516f069df18b500', '0101010101', NULL, 'admin', 'actif', '2026-04-27 14:40:36', NULL),
(2, 'Koné', 'Aminata', 'amine@tyastylex.com', '5e146d35b747a6da7dbecae5310fbf20', '0707070707', NULL, 'employe', 'actif', '2026-04-27 14:40:36', NULL),
(3, 'Traoré', 'Fatou', 'fatou@email.com', '3677b23baa08f74c28aba07f0cb6554e', '0505050505', NULL, 'client', 'actif', '2026-04-27 14:40:36', NULL),
(4, 'diarr', 'zayn', 'Adjata.traore@iua.ci', '$2y$10$b4/PfNepz0yA57FCqBQ62ex5vLKGCsTzY25A3E9Nk4Dkph26bLW4O', '0707686890', NULL, 'client', 'actif', '2026-04-27 16:53:31', NULL),
(5, 'diarr', 'zayn', 'zay.traore@iua.ci', '$2y$10$7dxRGjoPZw7JOb8ksi7oh.1K8FnRVcudc8kvzJYEgpp.OyAlOcA5u', '0707686890', NULL, 'client', 'actif', '2026-04-27 16:57:02', NULL),
(6, 'diarr', 'zayn', 'ay.traore@iua.ci', '$2y$10$PlGjmTu.xtaAzJ7udMs6te6dOhXt/IbH4fpOSyMUKE869eo9l4ptW', '0707686890', NULL, 'client', 'actif', '2026-04-27 17:05:38', NULL),
(7, 'naon', 'serge', 'naonserge123@gmail.com', '$2y$10$/WTFvwhaFol7BYESytl1OuSg03/kPV8QUzM3fNoNku8Z50LaCgf3C', '0142520909', NULL, 'client', 'actif', '2026-04-28 10:36:09', NULL),
(8, 'diarr', 'zayn', 'adja.traore@iua.ci', '$2y$10$GmHbZCWMJ1Q.xGfmI4sEgeZbZJXOCQwdvlnVZ6pD/umdPtcZ/nxqa', '0707686890', NULL, 'client', 'actif', '2026-04-29 09:25:08', NULL);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_rendezvous_complet`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_rendezvous_complet` (
`id_rdv` int(11)
,`id_cliente` int(11)
,`nom_cliente` varchar(50)
,`prenom_cliente` varchar(50)
,`id_employe` int(11)
,`nom_employe` varchar(50)
,`prenom_employe` varchar(50)
,`nom_service` varchar(100)
,`duree` int(11)
,`date_rdv` date
,`heure_rdv` time
,`prix_total` decimal(10,2)
,`reduction_appliquee` int(11)
,`statut` enum('en_attente','confirme','refuse','termine','annule')
,`date_reservation` datetime
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_stats_fidelite`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_stats_fidelite` (
`niveau_fidelite` int(11)
,`nombre_clientes` bigint(21)
,`moyenne_rdv` decimal(14,4)
);

-- --------------------------------------------------------

--
-- Structure de la vue `v_rendezvous_complet`
--
DROP TABLE IF EXISTS `v_rendezvous_complet`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_rendezvous_complet`  AS SELECT `r`.`id_rdv` AS `id_rdv`, `c`.`id_cliente` AS `id_cliente`, `u`.`nom` AS `nom_cliente`, `u`.`prenom` AS `prenom_cliente`, `e`.`id_employe` AS `id_employe`, `ue`.`nom` AS `nom_employe`, `ue`.`prenom` AS `prenom_employe`, `s`.`nom_service` AS `nom_service`, `s`.`duree` AS `duree`, `r`.`date_rdv` AS `date_rdv`, `r`.`heure_rdv` AS `heure_rdv`, `r`.`prix_total` AS `prix_total`, `r`.`reduction_appliquee` AS `reduction_appliquee`, `r`.`statut` AS `statut`, `r`.`date_reservation` AS `date_reservation` FROM (((((`rendez_vous` `r` join `clientes` `c` on(`r`.`id_cliente` = `c`.`id_cliente`)) join `utilisateurs` `u` on(`c`.`id_utilisateur` = `u`.`id_utilisateur`)) join `employes` `e` on(`r`.`id_employe` = `e`.`id_employe`)) join `utilisateurs` `ue` on(`e`.`id_utilisateur` = `ue`.`id_utilisateur`)) join `services` `s` on(`r`.`id_service` = `s`.`id_service`)) ;

-- --------------------------------------------------------

--
-- Structure de la vue `v_stats_fidelite`
--
DROP TABLE IF EXISTS `v_stats_fidelite`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_stats_fidelite`  AS SELECT `clientes`.`niveau_fidelite` AS `niveau_fidelite`, count(0) AS `nombre_clientes`, avg(`clientes`.`nombre_rendezvous`) AS `moyenne_rdv` FROM `clientes` GROUP BY `clientes`.`niveau_fidelite` ;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `avis`
--
ALTER TABLE `avis`
  ADD PRIMARY KEY (`id_avis`),
  ADD KEY `id_rdv` (`id_rdv`),
  ADD KEY `id_cliente` (`id_cliente`);

--
-- Index pour la table `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id_cliente`),
  ADD KEY `id_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `employes`
--
ALTER TABLE `employes`
  ADD PRIMARY KEY (`id_employe`),
  ADD KEY `id_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `fidelite_parametres`
--
ALTER TABLE `fidelite_parametres`
  ADD PRIMARY KEY (`id_param`);

--
-- Index pour la table `log_activites`
--
ALTER TABLE `log_activites`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `id_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `planning`
--
ALTER TABLE `planning`
  ADD PRIMARY KEY (`id_planning`),
  ADD KEY `id_employe` (`id_employe`),
  ADD KEY `idx_planning_date` (`date_planning`);

--
-- Index pour la table `rendez_vous`
--
ALTER TABLE `rendez_vous`
  ADD PRIMARY KEY (`id_rdv`),
  ADD KEY `id_cliente` (`id_cliente`),
  ADD KEY `id_employe` (`id_employe`),
  ADD KEY `id_service` (`id_service`),
  ADD KEY `idx_rendezvous_date` (`date_rdv`),
  ADD KEY `idx_rendezvous_statut` (`statut`);

--
-- Index pour la table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id_service`);

--
-- Index pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id_utilisateur`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_utilisateurs_email` (`email`),
  ADD KEY `idx_utilisateurs_role` (`role`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `avis`
--
ALTER TABLE `avis`
  MODIFY `id_avis` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `employes`
--
ALTER TABLE `employes`
  MODIFY `id_employe` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `fidelite_parametres`
--
ALTER TABLE `fidelite_parametres`
  MODIFY `id_param` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `log_activites`
--
ALTER TABLE `log_activites`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `planning`
--
ALTER TABLE `planning`
  MODIFY `id_planning` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `rendez_vous`
--
ALTER TABLE `rendez_vous`
  MODIFY `id_rdv` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `services`
--
ALTER TABLE `services`
  MODIFY `id_service` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id_utilisateur` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `avis`
--
ALTER TABLE `avis`
  ADD CONSTRAINT `avis_ibfk_1` FOREIGN KEY (`id_rdv`) REFERENCES `rendez_vous` (`id_rdv`),
  ADD CONSTRAINT `avis_ibfk_2` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`);

--
-- Contraintes pour la table `clientes`
--
ALTER TABLE `clientes`
  ADD CONSTRAINT `clientes_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE CASCADE;

--
-- Contraintes pour la table `employes`
--
ALTER TABLE `employes`
  ADD CONSTRAINT `employes_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE CASCADE;

--
-- Contraintes pour la table `log_activites`
--
ALTER TABLE `log_activites`
  ADD CONSTRAINT `log_activites_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`);

--
-- Contraintes pour la table `planning`
--
ALTER TABLE `planning`
  ADD CONSTRAINT `planning_ibfk_1` FOREIGN KEY (`id_employe`) REFERENCES `employes` (`id_employe`);

--
-- Contraintes pour la table `rendez_vous`
--
ALTER TABLE `rendez_vous`
  ADD CONSTRAINT `rendez_vous_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`),
  ADD CONSTRAINT `rendez_vous_ibfk_2` FOREIGN KEY (`id_employe`) REFERENCES `employes` (`id_employe`),
  ADD CONSTRAINT `rendez_vous_ibfk_3` FOREIGN KEY (`id_service`) REFERENCES `services` (`id_service`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
