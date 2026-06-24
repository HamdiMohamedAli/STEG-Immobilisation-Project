-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 22, 2026 at 10:32 AM
-- Server version: 10.4.32-MariaDB

-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `steg_immo_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `element_plan`
--

CREATE TABLE `element_plan` (
  `idElement` int(11) NOT NULL AUTO_INCREMENT,
  `idPlan` int(11) NOT NULL,
  `xPlan` int(11) NOT NULL,
  `yPlan` int(11) NOT NULL,
  `orientation` int(11) NOT NULL DEFAULT 0,
  `idLocal` int(11) DEFAULT NULL,
  `idItemMagasin` int(11) DEFAULT NULL,
  `matriculeVoiture` varchar(9) DEFAULT NULL,
  `idMagasin` int(11) DEFAULT NULL,
  PRIMARY KEY (`idElement`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `element_plan`
--

INSERT INTO `element_plan` (`idElement`, `idPlan`, `xPlan`, `yPlan`, `orientation`, `idLocal`, `idItemMagasin`, `matriculeVoiture`, `idMagasin`) VALUES
(1, 1, 150, 320, 90, NULL, NULL, NULL, 3),
(2, 1, 450, 120, 0, NULL, NULL, NULL, 2),
(3, 1, 0, 0, 0, NULL, NULL, '123 TUN 4', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `equipements`
--

CREATE TABLE `equipements` (
  `idEquip` int(11) NOT NULL AUTO_INCREMENT,
  `typeEquip` varchar(255) NOT NULL,
  `libelle` varchar(255) NOT NULL,
  PRIMARY KEY (`idEquip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `equipements`
--

INSERT INTO `equipements` (`idEquip`, `typeEquip`, `libelle`) VALUES
(1, 'Câble de connexion', 'Câble RJ45 Cat6 5m'),
(2, 'Réfrigérateur/Clim', 'Climatiseur Split Coala 12000 BTU'),
(3, 'Unité Centrale', 'Serveur Rack HP ProLiant DL360'),
(4, 'Climatisation', 'Centrale climatisation bureau'),
(5, 'Climatisation', 'Climatisation salle serveurs'),
(6, 'Climatisation', 'Climatiseur Split Coala 24000 BTU'),
(7, 'Climatisation', 'Climatiseur Split LG 9000 BTU'),
(8, 'Climatisation', 'Climatiseur Split 7000 BTU E Col P 2'),
(9, 'Climatisation', 'Climatiseur Monobloc'),
(10, 'Traitement Eau', 'Osmoseur'),
(11, 'Informatique', 'Serveurs'),
(12, 'Informatique', 'Imprimantes'),
(13, 'Informatique', 'Pc de bureaux'),
(14, 'Informatique', 'PC portable'),
(15, 'Logistique', 'Machines de mise sous pli'),
(16, 'Manutention', 'Transpalette'),
(17, 'Manutention', 'Cercleuse'),
(18, 'Traitement Air', 'Déshumidificateur'),
(19, 'Tableau Électrique', 'Tableau électrique local technique'),
(20, 'Tableau Électrique', 'Tableau électrique entrée secondaire'),
(21, 'Tableau Électrique', 'Tableau électrique stage'),
(22, 'Tableau Électrique', 'Tableau électrique provisoire salle ordinateur'),
(23, 'Tableau Électrique', 'Tableau électrique salle ordinateur'),
(24, 'Climatisation', 'Centrale 1 clim bureaux'),
(25, 'Climatisation', 'Centrale 2 clim bureaux'),
(26, 'Sécurité / Télécom', 'Transmetteur alarme Armoires A1 Armoire Télécom INSE'),
(27, 'Télécom', 'Armoire Télécom'),
(28, 'Stockage Équipements', 'Armoire pièces de rechange (Equipements)'),
(29, 'Climatisation', 'Armoire climatisation salle serveurs'),
(30, 'Réseau', 'Armoire fibre optique'),
(31, 'Serveur', 'Armoire serveur Alpha'),
(32, 'Serveur', 'Armoire serveur AD'),
(33, 'Stockage IT', 'Armoire robot de stockage'),
(34, 'Manutention', 'Chariot élévateur');

-- --------------------------------------------------------

--
-- Table structure for table `immobilisations`
--

CREATE TABLE `immobilisations` (
  `idImmo` int(11) NOT NULL AUTO_INCREMENT,
  `typeImmo` varchar(255) NOT NULL,
  `libelle` varchar(255) NOT NULL,
  PRIMARY KEY (`idImmo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `immobilisations`
--

INSERT INTO `immobilisations` (`idImmo`, `typeImmo`, `libelle`) VALUES
(1, 'Bureau', 'Table de bureau en bois 1.80m'),
(2, 'Chaise', 'Fauteuil ergonomique de bureau black'),
(3, 'Armoire', 'Armoire métallique forte blindée'),
(4, 'Audiovisuel', 'Rétroprojecteur'),
(5, 'Mobilier', 'Tableau'),
(6, 'Décoration', 'Drapeau STEG'),
(7, 'Bureau', 'Bureau 1,80 m'),
(8, 'Bureau', 'Bureau 1,60 m'),
(9, 'Bureau', 'Bureau 1,20 m'),
(10, 'Table', 'Table de travail'),
(11, 'Table', 'Table de téléphone'),
(12, 'Table', 'Table PC'),
(13, 'Table', 'Table imprimante'),
(14, 'Table', 'Table basse'),
(15, 'Siège', 'Fauteuil avec accoudoir'),
(16, 'Siège', 'Fauteuil orthopédique'),
(17, 'Siège', 'Chaise'),
(18, 'Sécurité', 'Armoire sécurité'),
(19, 'Sécurité', 'Armoire ignifuge'),
(20, 'Coffre-Fort', 'Coffre fort salle ordinateur'),
(21, 'Coffre-Fort', 'Coffre fort Chambre forte'),
(22, 'Coffre-Fort', 'Coffre à monnaie de secours');

-- --------------------------------------------------------

--
-- Table structure for table `journal_logs`
--

CREATE TABLE `journal_logs` (
  `idLog` int(11) NOT NULL AUTO_INCREMENT,
  `idUtilisateur` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `typeAction` varchar(50) NOT NULL,
  `cibleEntite` varchar(100) NOT NULL,
  `idCible` varchar(255) NOT NULL,
  `dateAction` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`idLog`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Logs vidés avec succès

-- --------------------------------------------------------

--
-- Table structure for table `locaux`
--

CREATE TABLE `locaux` (
  `idLocal` int(11) NOT NULL AUTO_INCREMENT,
  `typeLocal` varchar(255) NOT NULL,
  `labelLocal` varchar(255) NOT NULL,
  PRIMARY KEY (`idLocal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `locaux`
--

INSERT INTO `locaux` (`idLocal`, `typeLocal`, `labelLocal`) VALUES
(4, 'Technique', 'Local technique'),
(5, 'Entretien', 'Local entretien'),
(6, 'Bureau', 'Bureau responsable contrôle opération'),
(7, 'Bureau', 'Bureau chef centre impression'),
(8, 'Magasin', 'Magasin entrée 1'),
(9, 'Porte', 'Porte séparation 1'),
(10, 'Porte', 'Porte séparation 2'),
(11, 'Magasin', 'Magasin auxiliaire'),
(12, 'Technique', 'Local gaz FM200'),
(13, 'Salle', 'Salle ordinateur'),
(14, 'Salle', 'Local pupitrage Salle machine'),
(15, 'Bureau', 'Bureau préparation'),
(16, 'Bureau', 'Contrôle Factures'),
(17, 'Secours', 'Issue de secours salle ordinateurs'),
(18, 'Magasin', 'Magasin entrée 2'),
(19, 'Atelier', 'Atelier'),
(20, 'Bureau', 'Bureau expédition'),
(21, 'Salle', 'Salle prière'),
(23, 'Sanitaire', 'WC Femmes'),
(24, 'Vestiaire', 'Vestiaire'),
(25, 'Sanitaire', 'WC Hommes'),
(26, 'Sanitaire', 'WC 1'),
(27, 'Sanitaire', 'WC 2'),
(28, 'Chambre', 'Chambre forte'),
(29, 'Salle', 'Salle impression 1'),
(30, 'Salle', 'Salle impression 2'),
(31, 'Magasin', 'Local fourniture'),
(32, 'Salle', 'Local agents'),
(33, 'Bureau', 'Local poste de travail'),
(34, 'Porte', 'Porte piéton'),
(35, 'Porte', 'Porte entrée principale'),
(36, 'Porte', 'Porte entrée principale - Fer Forgé'),
(37, 'Porte', 'Porte sortie magasin'),
(38, 'Porte', 'Porte débarras3'),
(39, 'Porte', 'Porte surpresseur'),
(40, 'Porte', 'Porte onduleur'),
(41, 'Accès', 'Entrée groupe'),
(42, 'Porte', 'Porte groupe'),
(43, 'Accès', 'Entrée magasin'),
(44, 'Accès', 'Sortie expédition'),
(45, 'Porte', 'Porte district'),
(46, 'Secours', 'Sortie de secours'),
(47, 'Secours', 'Sortie de secours - Fer forgé'),
(48, 'Porte', 'Ancienne porte expédition'),
(49, 'Porte', 'Ancienne porte expédition - Fer forgé'),
(50, 'Poste', 'Gardiennage'),
(51, 'Accès', 'Entrée parking (Commande porte coulissante)'),
(52, 'Porte', 'Porte niches DRDSF'),
(56, 'Vestiaire', 'WC Femmes - Vestiaire'),
(61, 'Salle', 'Salle de réunion'),
(62, 'Porte', 'Porte salle de réunion'),
(63, 'Bureau', 'Bureau Secretariat'),
(64, 'Porte', 'Porte bureaux secrétariat'),
(65, 'Bureau', 'Bureau chef du centre'),
(66, 'Cuisine', 'Bureau chef du centre - Kitchenette'),
(67, 'Sanitaire', 'Bureau chef du centre - WC'),
(68, 'Bureau', 'Bureau archive'),
(69, 'Bureau', 'Bureau gestionnaire'),
(70, 'Bureau', 'Bureau photocopie'),
(71, 'Bureau', 'Bureau technicien matériel et réseau'),
(72, 'Bureau', 'Bureau responsable sécurité'),
(73, 'Bureau', 'Bureau responsable équipements annexes'),
(74, 'Bureau', 'Bureau responsable services et réseau informatique'),
(75, 'Secours', 'Issue de secours 1'),
(76, 'Secours', 'Issue de secours 2');

-- --------------------------------------------------------

--
-- Table structure for table `magasin`
--

CREATE TABLE `magasin` (
  `idMagasin` int(11) NOT NULL AUTO_INCREMENT,
  `typeEntite` enum('Equipement','Immobilisation') NOT NULL,
  `idCible` int(11) NOT NULL,
  `referenceInterne` varchar(50) DEFAULT NULL,
  `quantiteStock` int(11) NOT NULL DEFAULT 0,
  `quantiteDisponible` int(11) NOT NULL DEFAULT 0,
  `dateReception` date NOT NULL,
  `statut` enum('En stock','Deployé','En maintenance') DEFAULT 'En stock',
  PRIMARY KEY (`idMagasin`),
  UNIQUE KEY `unique_article` (`typeEntite`,`idCible`),
  UNIQUE KEY `referenceInterne` (`referenceInterne`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `magasin`
--

INSERT INTO `magasin` (`idMagasin`, `typeEntite`, `idCible`, `referenceInterne`, `quantiteStock`, `quantiteDisponible`, `dateReception`, `statut`) VALUES
(4, 'Equipement', 1, 'STEG-EQ-CAB-001', 100, 85, '2026-01-10', 'En stock'),
(5, 'Equipement', 2, 'STEG-EQ-CLM-002', 5, 2, '2026-02-14', 'Deployé'),
(6, 'Equipement', 3, 'STEG-EQ-SRV-003', 4, 1, '2026-03-20', 'En stock'),
(7, 'Immobilisation', 1, 'STEG-IMM-BUR-001', 12, 12, '2026-01-15', 'En stock'),
(8, 'Immobilisation', 2, 'STEG-IMM-CHM-002', 25, 20, '2026-01-15', 'Deployé'),
(9, 'Immobilisation', 3, 'STEG-IMM-ARM-003', 3, 3, '2026-04-02', 'En stock');

--
-- Triggers `magasin`
--
DELIMITER $$
CREATE TRIGGER `tg_magasin_insert_polymorphe` BEFORE INSERT ON `magasin` FOR EACH ROW BEGIN
    IF NEW.typeEntite = 'Equipement' THEN
        IF NOT EXISTS (SELECT 1 FROM `equipements` WHERE `idEquip` = NEW.idCible) THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Erreur : L idCible spécifié n existe pas dans la table Equipements.';
        END IF;
    ELSEIF NEW.typeEntite = 'Immobilisation' THEN
        IF NOT EXISTS (SELECT 1 FROM `immobilisations` WHERE `idImmo` = NEW.idCible) THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Erreur : L idCible spécifié n existe pas dans la table immobilisations.';
        END IF;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tg_magasin_update_polymorphe` BEFORE UPDATE ON `magasin` FOR EACH ROW BEGIN
    IF NEW.typeEntite = 'Equipement' THEN
        IF NOT EXISTS (SELECT 1 FROM `equipements` WHERE `idEquip` = NEW.idCible) THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Erreur : L idCible spécifié n existe pas dans la table Equipements.';
        END IF;
    ELSEIF NEW.typeEntite = 'Immobilisation' THEN
        IF NOT EXISTS (SELECT 1 FROM `immobilisations` WHERE `idImmo` = NEW.idCible) THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Erreur : L idCible spécifié n existe pas dans la table immobilisations.';
        END IF;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `pannes`
--

CREATE TABLE `pannes` (
  `idPanne` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(255) NOT NULL,
  `dateDeclaration` date NOT NULL,
  `gravite` varchar(255) NOT NULL,
  `idUtilisateur` int(11) NOT NULL,
  `idEquip` int(11) DEFAULT NULL,
  `matriculeVoiture` varchar(9) DEFAULT NULL,
  PRIMARY KEY (`idPanne`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pannes`
--

INSERT INTO `pannes` (`idPanne`, `description`, `dateDeclaration`, `gravite`, `idUtilisateur`, `idEquip`, `matriculeVoiture`) VALUES
(1, 'Surchauffe excessive et coupure automatique', '2026-06-10', 'Majeure', 11140, 2, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `plan_centre`
--

CREATE TABLE `plan_centre` (
  `idPlan` int(11) NOT NULL AUTO_INCREMENT,
  `versionPlan` varchar(50) NOT NULL,
  `dateCreation` date NOT NULL,
  PRIMARY KEY (`idPlan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `plan_centre`
--

INSERT INTO `plan_centre` (`idPlan`, `versionPlan`, `dateCreation`) VALUES
(1, 'v1.0-Initial', '2026-01-15');

-- --------------------------------------------------------

--
-- Table structure for table `profils`
--

CREATE TABLE `profils` (
  `idProfil` int(11) NOT NULL AUTO_INCREMENT,
  `labelProfil` varchar(255) NOT NULL,
  PRIMARY KEY (`idProfil`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `profils`
--

INSERT INTO `profils` (`idProfil`, `labelProfil`) VALUES
(1, 'Administrateur'),
(2, 'Chef du Centre Informatique'),
(3, 'Responsable de Services'),
(4, 'Responsable Équipements'),
(5, 'Gestionnaire (Voitures)'),
(6, 'Agent de Local');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `idUtilisateur` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `tel` varchar(8) NOT NULL,
  `motDePasse` varchar(255) NOT NULL,
  `idProfil` int(11) NOT NULL,
  `idLocalAssigne` varchar(50) DEFAULT NULL,
  `statut` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = Active, 0 = Inactive',
  PRIMARY KEY (`idUtilisateur`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`idUtilisateur`, `nom`, `email`, `tel`, `motDePasse`, `idProfil`, `idLocalAssigne`, `statut`) VALUES
(11110, 'Mohamed Ali Hamdi', 'admin@steg.com.tn', '99858256', '12345', 1, NULL, 1),
(11120, 'Sami Ben Ali', 's.benali@steg.com.tn', '98123456', '12345', 2, NULL, 1),
(11160, 'Sonia Ben Youssef', 's.benyoussef@steg.com.tn', '22114477', '12345', 3, NULL, 1),
(11140, 'Wajdi Mansour', 'w.mansour@steg.com.tn', '55443322', '12345', 4, NULL, 1),
(11130, 'Amel Trabelsi', 'a.trabelsi@steg.com.tn', '22345678', '12345', 5, NULL, 1),
(11190, 'Leila Marzouki', 'l.marzouki@steg.com.tn', '20336699', '12345', 6, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `voitures`
--

CREATE TABLE `voitures` (
  `matricule` varchar(9) NOT NULL,
  `marque` varchar(100) NOT NULL,
  `modele` varchar(100) DEFAULT NULL,
  `statut` varchar(50) NOT NULL DEFAULT 'Disponible',
  PRIMARY KEY (`matricule`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `voitures`
--

INSERT INTO `voitures` (`matricule`, `marque`, `modele`, `statut`) VALUES
('09-100001', 'Citroën', 'Berlingo', 'Disponible'),
('09-100002', 'Mahindra', 'Pick-up', 'Disponible'),
('09-100003', 'Peugeot', 'Partner', 'Disponible'),
('123 TUN 4', 'Isuzu', 'D-Max Double Cabine', 'Disponible'),
('222 TUN 3', 'Renault', 'Kangoo utilitaire', 'Disponible'),
('789 TUN 1', 'Peugeot', 'Partner Berline', 'En Panne');

-- --------------------------------------------------------

--
-- Indexes and Constraints Setup
--

ALTER TABLE `element_plan`
  ADD KEY `idPlan` (`idPlan`),
  ADD KEY `idLocal` (`idLocal`),
  ADD KEY `idItemMagasin` (`idItemMagasin`),
  ADD KEY `matriculeVoiture` (`matriculeVoiture`),
  ADD KEY `element_plan_ibfk_3` (`idMagasin`);

ALTER TABLE `journal_logs`
  ADD KEY `idUtilisateur` (`idUtilisateur`);

ALTER TABLE `magasin`
  ADD KEY `idx_liaison_universelle` (`typeEntite`,`idCible`);

ALTER TABLE `pannes`
  ADD KEY `idUtilisateur` (`idUtilisateur`),
  ADD KEY `idEquip` (`idEquip`),
  ADD KEY `matriculeVoiture` (`matriculeVoiture`);

ALTER TABLE `users`
  ADD KEY `idProfil` (`idProfil`),
  ADD KEY `idLocalAssigne` (`idLocalAssigne`);

--
-- Constraints for table `element_plan`
--
ALTER TABLE `element_plan`
  ADD CONSTRAINT `fk_element_local` FOREIGN KEY (`idLocal`) REFERENCES `locaux` (`idLocal`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_element_plan` FOREIGN KEY (`idPlan`) REFERENCES `plan_centre` (`idPlan`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_element_voiture` FOREIGN KEY (`matriculeVoiture`) REFERENCES `voitures` (`matricule`) ON DELETE SET NULL;

--
-- Constraints for table `journal_logs`
--
ALTER TABLE `journal_logs`
  ADD CONSTRAINT `fk_logs_user` FOREIGN KEY (`idUtilisateur`) REFERENCES `users` (`idUtilisateur`) ON UPDATE CASCADE;

--
-- Constraints for table `pannes`
--
ALTER TABLE `pannes`
  ADD CONSTRAINT `fk_pannes_equip` FOREIGN KEY (`idEquip`) REFERENCES `equipements` (`idEquip`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pannes_user` FOREIGN KEY (`idUtilisateur`) REFERENCES `users` (`idUtilisateur`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pannes_voiture` FOREIGN KEY (`matriculeVoiture`) REFERENCES `voitures` (`matricule`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_profil` FOREIGN KEY (`idProfil`) REFERENCES `profils` (`idProfil`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;