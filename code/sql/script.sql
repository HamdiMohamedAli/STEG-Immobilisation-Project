-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 08, 2026 at 10:54 PM
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
  `idElement` int(11) NOT NULL,
  `idPlan` int(11) NOT NULL,
  `id_bloc` varchar(50) DEFAULT NULL COMMENT 'Identifiant si la cellule appartient à un bloc fusionné',
  `row_start` int(11) NOT NULL DEFAULT 1,
  `row_end` int(11) NOT NULL DEFAULT 1,
  `col_start` int(11) NOT NULL DEFAULT 1,
  `col_end` int(11) NOT NULL DEFAULT 1,
  `is_master` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = Master (affiche le texte), 0 = Esclave (texte masqué)',
  `idLocal` int(11) DEFAULT NULL,
  `matriculeVoiture` varchar(9) DEFAULT NULL,
  `idMagasin` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `element_plan`
--

INSERT INTO `element_plan` (`idElement`, `idPlan`, `id_bloc`, `row_start`, `row_end`, `col_start`, `col_end`, `is_master`, `idLocal`, `matriculeVoiture`, `idMagasin`) VALUES
(33, 1, NULL, 1, 1, 1, 1, 1, NULL, NULL, 7),
(34, 1, NULL, 4, 4, 5, 5, 1, NULL, NULL, 9),
(35, 1, NULL, 5, 5, 1, 1, 1, NULL, NULL, 9);

-- --------------------------------------------------------

--
-- Table structure for table `equipements`
--

CREATE TABLE `equipements` (
  `idEquip` int(11) NOT NULL,
  `typeEquip` varchar(255) NOT NULL,
  `libelle` varchar(255) NOT NULL
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
  `idImmo` int(11) NOT NULL,
  `typeImmo` varchar(255) NOT NULL,
  `libelle` varchar(255) NOT NULL
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
  `idLog` int(11) NOT NULL,
  `idUtilisateur` int(5) UNSIGNED ZEROFILL NOT NULL,
  `action` varchar(255) NOT NULL,
  `typeAction` varchar(50) NOT NULL,
  `cibleEntite` varchar(100) NOT NULL,
  `idCible` varchar(255) NOT NULL,
  `dateAction` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `journal_logs`
--

INSERT INTO `journal_logs` (`idLog`, `idUtilisateur`, `action`, `typeAction`, `cibleEntite`, `idCible`, `dateAction`) VALUES
(1, 00000, 'Modification du compte utilisateur', 'UPDATE', 'users', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Email: admin@steg.com.tn | Rôle: Administrateur | Statut: Actif | Mot de passe : réinitialisé', '2026-06-30 16:33:44'),
(2, 00000, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-06-30 16:33:46'),
(3, 00000, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-06-30 16:33:48'),
(4, 00000, 'Suppression de l\'élément : [Voiture] Isuzu D-Max Double Cabine (123 TUN 4) | Pos: (X: 0, Y: 0) | Niveau: RC', 'DELETE', 'element_plan', '3', '2026-06-30 16:35:07'),
(5, 00000, 'Placement de l\'élément : [Voiture] Citroën Berlingo (09-100001) | Pos: (X: 100, Y: 150) | Niveau: RC', 'CREATE', 'element_plan', '4', '2026-06-30 16:39:01'),
(6, 00000, 'Placement de l\'élément : [Voiture] Mahindra Pick-up (09-100002) | Pos: (X: 100, Y: 250) | Niveau: RC', 'CREATE', 'element_plan', '5', '2026-06-30 16:39:04'),
(7, 00000, 'Placement de l\'élément : [Voiture] Peugeot Partner (09-100003) | Pos: (X: 250, Y: 150) | Niveau: RC', 'CREATE', 'element_plan', '6', '2026-06-30 16:39:07'),
(8, 00000, 'Placement de l\'élément : [Voiture] Isuzu D-Max Double Cabine (123 TUN 4) | Pos: (X: 250, Y: 250) | Niveau: RC', 'CREATE', 'element_plan', '7', '2026-06-30 16:39:09'),
(9, 00000, 'Placement de l\'élément : [Voiture] Renault Kangoo utilitaire (222 TUN 3) | Pos: (X: 200, Y: 350) | Niveau: RC', 'CREATE', 'element_plan', '8', '2026-06-30 16:39:12'),
(10, 00000, 'Suppression de l\'élément : [Voiture] Citroën Berlingo (09-100001) | Pos: (X: 100, Y: 150) | Niveau: RC', 'DELETE', 'element_plan', '4', '2026-06-30 16:41:03'),
(11, 00000, 'Suppression de l\'élément : [Voiture] Mahindra Pick-up (09-100002) | Pos: (X: 100, Y: 250) | Niveau: RC', 'DELETE', 'element_plan', '5', '2026-06-30 16:41:03'),
(12, 00000, 'Suppression de l\'élément : [Voiture] Peugeot Partner (09-100003) | Pos: (X: 250, Y: 150) | Niveau: RC', 'DELETE', 'element_plan', '6', '2026-06-30 16:41:03'),
(13, 00000, 'Suppression de l\'élément : [Voiture] Isuzu D-Max Double Cabine (123 TUN 4) | Pos: (X: 250, Y: 250) | Niveau: RC', 'DELETE', 'element_plan', '7', '2026-06-30 16:41:03'),
(14, 00000, 'Suppression de l\'élément : [Voiture] Renault Kangoo utilitaire (222 TUN 3) | Pos: (X: 200, Y: 350) | Niveau: RC', 'DELETE', 'element_plan', '8', '2026-06-30 16:41:03'),
(15, 00000, 'Placement de l\'élément : [Equipement] Câble RJ45 Cat6 5m | Pos: (X: 50, Y: 150) | Niveau: RC', 'CREATE', 'element_plan', '9', '2026-06-30 16:41:07'),
(16, 00000, 'Placement de l\'élément : [Equipement] Câble RJ45 Cat6 5m | Pos: (X: 50, Y: 150) | Niveau: RC', 'CREATE', 'element_plan', '10', '2026-06-30 16:41:07'),
(17, 00000, 'Suppression de l\'élément : [Equipement] Câble RJ45 Cat6 5m | Pos: (X: 50, Y: 150) | Niveau: RC', 'DELETE', 'element_plan', '9', '2026-06-30 16:41:20'),
(18, 00000, 'Suppression de l\'élément : [Equipement] Câble RJ45 Cat6 5m | Pos: (X: 50, Y: 150) | Niveau: RC', 'DELETE', 'element_plan', '10', '2026-06-30 16:41:21'),
(19, 00000, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-01 07:15:16'),
(20, 00000, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-01 07:16:07'),
(21, 00000, 'Connexion au système', 'LOGIN', 'Utilisateur', 'Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-01 07:17:03'),
(22, 00000, 'Placement de l\'élément : [Local] Local technique | Pos: (X: 200, Y: 100) | Niveau: RC', 'CREATE', 'element_plan', '11', '2026-07-01 09:25:03'),
(23, 00000, 'Suppression de l\'élément : [Local] Local technique | Pos: (X: 200, Y: 100) | Niveau: RC', 'DELETE', 'element_plan', '11', '2026-07-01 09:36:16'),
(24, 00000, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-02 08:14:56'),
(25, 00000, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: ', '2026-07-03 08:43:15'),
(26, 00000, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: ', '2026-07-03 08:48:21'),
(27, 00000, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: ', '2026-07-03 08:48:25'),
(28, 00000, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: ', '2026-07-03 08:58:04'),
(29, 00000, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-03 08:58:06'),
(30, 00000, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-03 09:00:32'),
(31, 00000, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-03 09:00:34'),
(32, 00000, 'Création du local 0077 (typeLocal: Bureau, libelle: www)', 'CREATE', 'Local', '77', '2026-07-03 09:43:22'),
(33, 00000, 'Suppression du local 0077 (Type: Bureau, Libellé: www)', 'DELETE', 'Local', '77', '2026-07-03 09:43:31'),
(34, 00000, 'Suppression du véhicule logistique : Citroën Berlingo (09-100001)', 'DELETE', 'voitures', '09-100001', '2026-07-03 09:49:44'),
(35, 00000, 'Suppression du véhicule logistique : Isuzu D-Max Double Cabine (123 TUN 4)', 'DELETE', 'voitures', '123 TUN 4', '2026-07-03 09:49:50'),
(36, 00000, 'Suppression du véhicule logistique : Peugeot Partner Berline (789 TUN 1)', 'DELETE', 'voitures', '789 TUN 1', '2026-07-03 09:49:53'),
(37, 00000, 'Suppression du véhicule logistique : Renault Kangoo utilitaire (222 TUN 3)', 'DELETE', 'voitures', '222 TUN 3', '2026-07-03 09:49:55'),
(38, 00000, 'Ajout du véhicule logistique : Isuzu D-Max (09-475325)', 'CREATE', 'voitures', '09-475325', '2026-07-03 09:50:05'),
(39, 00000, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-06 07:05:28'),
(40, 00000, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-06 10:29:05'),
(41, 00000, 'Tentative de connexion échouée', 'ERROR', 'Utilisateur', 'ID saisi: 00001', '2026-07-06 10:29:10'),
(42, 00000, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-06 10:29:12'),
(43, 00000, 'Modification du compte utilisateur', 'UPDATE', 'users', 'ID: 00001 | Nom: Sami Ben Ali | Email: s.benali@steg.com.tn | Rôle: Chef du Centre Informatique | Statut: Actif | Mot de passe : réinitialisé', '2026-07-06 10:29:21'),
(44, 00000, 'Modification du compte utilisateur', 'UPDATE', 'users', 'ID: 00002 | Nom: Sonia Ben Youssef | Email: s.benyoussef@steg.com.tn | Rôle: Responsable de Services | Statut: Actif | Mot de passe : réinitialisé', '2026-07-06 10:29:28'),
(45, 00000, 'Modification du compte utilisateur', 'UPDATE', 'users', 'ID: 00003 | Nom: Wajdi Mansour | Email: w.mansour@steg.com.tn | Rôle: Responsable Équipements | Statut: Actif | Mot de passe : réinitialisé', '2026-07-06 10:29:34'),
(46, 00000, 'Modification du compte utilisateur', 'UPDATE', 'users', 'ID: 00004 | Nom: Amel Trabelsi | Email: a.trabelsi@steg.com.tn | Rôle: Gestionnaire (Voitures) | Statut: Actif | Mot de passe : réinitialisé', '2026-07-06 10:29:41'),
(47, 00000, 'Modification du compte utilisateur', 'UPDATE', 'users', 'ID: 00005 | Nom: Leila Marzouki | Email: l.marzouki@steg.com.tn | Rôle: Agent de Local | Statut: Actif | Mot de passe : réinitialisé', '2026-07-06 10:29:47'),
(48, 00000, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-06 10:30:34'),
(49, 00005, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-06 10:30:37'),
(50, 00005, 'Demande de réinitialisation de mot de passe soumise à l\'administrateur (Format attendu : SHA2-256)', 'REQUEST', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki', '2026-07-06 10:31:24'),
(51, 00005, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-06 10:31:26'),
(52, 00000, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-06 10:31:29'),
(53, 00000, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-06 21:49:23'),
(54, 00000, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-06 23:35:54'),
(55, 00005, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-06 23:36:02'),
(56, 00005, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-06 23:53:37'),
(57, 00000, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-06 23:53:40'),
(58, 00000, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-06 23:53:46'),
(59, 00005, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-06 23:53:49'),
(60, 00000, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-07 07:02:23'),
(61, 00000, 'Modification du compte utilisateur', 'UPDATE', 'users', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Email: hamdivrlife@gmail.com | Rôle: Administrateur | Statut: Actif', '2026-07-07 07:05:06'),
(62, 00000, 'Modification du compte utilisateur', 'UPDATE', 'users', 'ID: 00001 | Nom: Sami Ben Ali | Email: h.amdivrlife@gmail.com | Rôle: Chef du Centre Informatique | Statut: Actif', '2026-07-07 07:05:37'),
(63, 00000, 'Modification du compte utilisateur', 'UPDATE', 'users', 'ID: 00002 | Nom: Sonia Ben Youssef | Email: h..amdivrlife@gmail.com | Rôle: Responsable de Services | Statut: Actif', '2026-07-07 07:06:03'),
(64, 00000, 'Modification du compte utilisateur', 'UPDATE', 'users', 'ID: 00003 | Nom: Wajdi Mansour | Email: h...amdivrlife@gmail.com | Rôle: Responsable Équipements | Statut: Actif', '2026-07-07 07:06:20'),
(65, 00000, 'Modification du compte utilisateur', 'UPDATE', 'users', 'ID: 00004 | Nom: Amel Trabelsi | Email: a.trabelsi@steg.com.tn | Rôle: Gestionnaire (Voitures) | Statut: Actif', '2026-07-07 07:06:26'),
(66, 00000, 'Modification du compte utilisateur', 'UPDATE', 'users', 'ID: 00004 | Nom: Amel Trabelsi | Email: h....amdivrlife@gmail.com | Rôle: Gestionnaire (Voitures) | Statut: Actif', '2026-07-07 07:07:10'),
(67, 00000, 'Modification du compte utilisateur', 'UPDATE', 'users', 'ID: 00005 | Nom: Leila Marzouki | Email: h.....amdivrlife@gmail.com | Rôle: Agent de Local | Statut: Actif', '2026-07-07 07:07:27'),
(68, 00000, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-07 07:58:43'),
(69, 00005, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 07:58:46'),
(70, 00005, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 07:59:29'),
(71, 00005, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 07:59:32'),
(72, 00005, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 08:02:18'),
(73, 00005, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 08:02:20'),
(74, 00005, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 08:06:48'),
(75, 00005, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 08:06:53'),
(76, 00000, 'Tentative de connexion échouée', 'ERROR', 'Utilisateur', 'ID saisi: 00000', '2026-07-07 08:10:06'),
(77, 00000, 'Tentative de connexion échouée', 'ERROR', 'Utilisateur', 'ID saisi: 00000', '2026-07-07 08:10:43'),
(78, 00000, 'Tentative de connexion échouée', 'ERROR', 'Utilisateur', 'ID saisi: 00000', '2026-07-07 08:10:46'),
(79, 00000, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-07 08:14:10'),
(80, 00000, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-07 08:14:12'),
(81, 00005, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 08:14:14'),
(82, 00005, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 08:15:47'),
(83, 00000, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-07 08:15:50'),
(84, 00000, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-07 08:15:52'),
(85, 00005, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 08:15:55'),
(86, 00005, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 08:18:06'),
(87, 00000, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-07 08:18:09'),
(88, 00000, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-07 08:18:11'),
(89, 00005, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 08:18:14'),
(90, 00005, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 08:20:12'),
(91, 00005, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 08:20:14'),
(92, 00005, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 08:21:21'),
(93, 00005, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 08:21:24'),
(94, 00005, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 08:23:28'),
(95, 00005, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 08:23:34'),
(96, 00005, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 08:26:13'),
(97, 00005, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 08:26:16'),
(98, 00005, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 08:27:36'),
(99, 00005, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 08:27:39'),
(100, 00005, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 08:28:50'),
(101, 00005, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 08:28:53'),
(102, 00005, 'Demande de réinitialisation de mot de passe soumise à l\'administrateur (Format attendu : SHA2-256)', 'REQUEST', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki', '2026-07-07 08:28:58'),
(103, 00005, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 08:29:01'),
(104, 00005, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 08:29:03'),
(105, 00005, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 08:29:06'),
(106, 00000, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-07 08:29:08'),
(107, 00000, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-07 08:32:42'),
(108, 00005, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 08:32:45'),
(109, 00005, 'Demande de réinitialisation de mot de passe soumise à l\'administrateur (Format attendu : SHA2-256)', 'REQUEST', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki', '2026-07-07 08:32:50'),
(110, 00005, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 08:32:56'),
(111, 00000, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-07 08:32:58'),
(112, 00000, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-07 08:34:18'),
(113, 00005, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 08:34:23'),
(114, 00005, 'Demande de réinitialisation de mot de passe soumise à l\'administrateur (Format attendu : SHA2-256)', 'REQUEST', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki', '2026-07-07 08:34:27'),
(115, 00005, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 08:35:35'),
(116, 00005, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 08:35:38'),
(117, 00005, 'Déclaration d\'une panne (ID: #3) par le profil 6.', 'CREATE', 'pannes', '3', '2026-07-07 08:39:06'),
(118, 00005, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 08:39:14'),
(119, 00000, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-07 08:39:18'),
(120, 00000, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-07 08:40:21'),
(121, 00000, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-07 08:40:24'),
(122, 00000, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-07 08:48:10'),
(123, 00005, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 08:48:13'),
(124, 00005, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 08:50:02'),
(125, 00000, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-07 08:50:04'),
(126, 00000, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-07 08:52:24'),
(127, 00005, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 08:52:27'),
(128, 00005, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 08:53:33'),
(129, 00000, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-07 08:53:38'),
(130, 00000, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-07 08:56:02'),
(131, 00005, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 08:56:05'),
(132, 00005, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 08:57:52'),
(133, 00000, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-07 08:57:55'),
(134, 00000, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-07 08:58:07'),
(135, 00005, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 08:58:09'),
(136, 00005, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 09:00:14'),
(137, 00005, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 09:04:17'),
(138, 00005, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 09:05:01'),
(139, 00005, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 09:05:03'),
(140, 00005, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 09:05:20'),
(141, 00000, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-07 09:05:23'),
(142, 00000, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-07 09:05:38'),
(143, 00000, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-07 09:05:41'),
(144, 00000, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-07 09:05:43'),
(145, 00005, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 09:05:46'),
(146, 00005, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 09:08:18'),
(147, 00005, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 09:08:21'),
(148, 00005, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 09:11:10'),
(149, 00000, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-07 09:11:11'),
(150, 00000, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-07 09:22:31'),
(151, 00000, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-07 09:22:33'),
(152, 00000, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-07 09:26:37'),
(153, 00000, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-07 09:26:40'),
(154, 00000, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-07 09:36:18'),
(155, 00000, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-07 09:36:20'),
(156, 00000, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-07 10:08:06'),
(157, 00000, 'Tentative de connexion échouée', 'ERROR', 'Utilisateur', 'ID saisi: 00000', '2026-07-07 10:09:33'),
(158, 00000, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-07 10:10:10'),
(159, 00000, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-07 10:13:19'),
(160, 00005, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 10:13:36'),
(161, 00005, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00005 | Nom: Leila Marzouki | Rôle: Agent de Local', '2026-07-07 10:14:14'),
(162, 00000, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-07 10:14:19'),
(163, 00000, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-07 10:14:33'),
(164, 00001, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00001 | Nom: Sami Ben Ali | Rôle: Chef du Centre Informatique', '2026-07-07 10:14:42'),
(165, 00001, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00001 | Nom: Sami Ben Ali | Rôle: Chef du Centre Informatique', '2026-07-07 10:15:03'),
(166, 00002, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00002 | Nom: Sonia Ben Youssef | Rôle: Responsable de Services', '2026-07-07 10:15:10'),
(167, 00002, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00002 | Nom: Sonia Ben Youssef | Rôle: Responsable de Services', '2026-07-07 10:15:21'),
(168, 00002, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00002 | Nom: Sonia Ben Youssef | Rôle: Responsable de Services', '2026-07-07 10:15:32'),
(169, 00002, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00002 | Nom: Sonia Ben Youssef | Rôle: Responsable de Services', '2026-07-07 10:15:40'),
(170, 00001, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00001 | Nom: Sami Ben Ali | Rôle: Chef du Centre Informatique', '2026-07-07 10:15:48'),
(171, 00001, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00001 | Nom: Sami Ben Ali | Rôle: Chef du Centre Informatique', '2026-07-07 10:15:57'),
(172, 00003, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00003 | Nom: Wajdi Mansour | Rôle: Responsable Équipements', '2026-07-07 10:16:03'),
(173, 00003, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00003 | Nom: Wajdi Mansour | Rôle: Responsable Équipements', '2026-07-07 10:16:18'),
(174, 00004, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00004 | Nom: Amel Trabelsi | Rôle: Gestionnaire (Voitures)', '2026-07-07 10:16:24'),
(175, 00004, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00004 | Nom: Amel Trabelsi | Rôle: Gestionnaire (Voitures)', '2026-07-07 10:17:17'),
(176, 00000, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-07 10:17:20'),
(177, 00000, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-07-07 10:31:59'),
(178, 00000, 'Modification du compte utilisateur', 'UPDATE', 'users', 'ID: 00001 | Nom: Sami Ben Ali | Email: b.sami@example.com | Rôle: Chef du Centre Informatique | Statut: Actif', '2026-07-07 11:46:48'),
(179, 00000, 'Modification du compte utilisateur', 'UPDATE', 'users', 'ID: 00002 | Nom: Sonia Ben Youssef | Email: b.sonia@example.com | Rôle: Responsable de Services | Statut: Actif', '2026-07-07 11:47:01'),
(180, 00000, 'Modification du compte utilisateur', 'UPDATE', 'users', 'ID: 00003 | Nom: Wajdi Mansour | Email: m.wajdi@example.com | Rôle: Responsable Équipements | Statut: Actif', '2026-07-07 11:47:22'),
(181, 00000, 'Modification du compte utilisateur', 'UPDATE', 'users', 'ID: 00004 | Nom: Amel Trabelsi | Email: t.amel@example.com | Rôle: Gestionnaire (Voitures) | Statut: Actif', '2026-07-07 11:47:37'),
(182, 00000, 'Modification du compte utilisateur', 'UPDATE', 'users', 'ID: 00005 | Nom: m.leila@example.com | Email: h.....amdivrlife@gmail.com | Rôle: Agent de Local | Statut: Actif', '2026-07-07 11:47:46'),
(183, 00000, 'Modification du compte utilisateur', 'UPDATE', 'users', 'ID: 00005 | Nom: Leila Marzouki | Email: m.leila@example.com | Rôle: Agent de Local | Statut: Actif', '2026-07-07 11:48:13'),
(184, 00000, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-08-08 11:59:26'),
(185, 00000, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-09-07 11:20:39'),
(186, 00000, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-09-07 11:40:20'),
(187, 00000, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-09-07 11:42:54'),
(188, 00000, 'Impression du registre officiel du personnel (STEG)', 'PRINT', 'users', 'Imprimé par : ID 00000 | Nom : Mohamed Ali Hamdi | Rôle : Administrateur | Nombre d\'agents imprimés : 6', '2026-09-07 12:00:39'),
(189, 00000, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-09-07 13:14:02'),
(190, 00000, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-09-07 13:16:36'),
(191, 00000, 'Déconnexion du système', 'LOGOUT', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-09-07 13:23:21'),
(192, 00000, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-09-07 13:23:24'),
(193, 00000, 'Connexion au système', 'LOGIN', 'Utilisateur', 'ID: 00000 | Nom: Mohamed Ali Hamdi | Rôle: Administrateur', '2026-09-08 21:28:38');

-- --------------------------------------------------------

--
-- Table structure for table `locaux`
--

CREATE TABLE `locaux` (
  `idLocal` int(11) NOT NULL,
  `typeLocal` varchar(255) NOT NULL,
  `labelLocal` varchar(255) NOT NULL
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
  `idMagasin` int(11) NOT NULL,
  `typeEntite` enum('Equipement','Immobilisation') NOT NULL,
  `idCible` int(11) NOT NULL,
  `referenceInterne` varchar(50) DEFAULT NULL,
  `quantiteStock` int(11) NOT NULL DEFAULT 0,
  `quantiteDisponible` int(11) NOT NULL DEFAULT 0,
  `dateReception` date NOT NULL,
  `statut` enum('En stock','Deployé','En maintenance') DEFAULT 'En stock'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `magasin`
--

INSERT INTO `magasin` (`idMagasin`, `typeEntite`, `idCible`, `referenceInterne`, `quantiteStock`, `quantiteDisponible`, `dateReception`, `statut`) VALUES
(4, 'Equipement', 1, 'STEG-EQ-CAB-001', 100, 73, '2026-01-10', 'En stock'),
(5, 'Equipement', 2, 'STEG-EQ-CLM-002', 5, 0, '2026-02-14', 'Deployé'),
(6, 'Equipement', 3, 'STEG-EQ-SRV-003', 4, 0, '2026-03-20', 'En stock'),
(7, 'Immobilisation', 1, 'STEG-IMM-BUR-001', 12, 10, '2026-01-15', 'En stock'),
(8, 'Immobilisation', 2, 'STEG-IMM-CHM-002', 25, 16, '2026-01-15', 'Deployé'),
(9, 'Immobilisation', 3, 'STEG-IMM-ARM-003', 3, 2, '2026-04-02', 'En stock');

--
-- Triggers `magasin`
--
DELIMITER $$
CREATE TRIGGER `tg_magasin_insert_polymorphe` BEFORE INSERT ON `magasin` FOR EACH ROW BEGIN
    IF NEW.typeEntite = 'Equipement' THEN
        IF NOT EXISTS (SELECT 1 FROM `equipements` WHERE `idEquip` = NEW.idCible) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erreur : L idCible spécifié n existe pas dans la table Equipements.';
        END IF;
    ELSEIF NEW.typeEntite = 'Immobilisation' THEN
        IF NOT EXISTS (SELECT 1 FROM `immobilisations` WHERE `idImmo` = NEW.idCible) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erreur : L idCible spécifié n existe pas dans la table immobilisations.';
        END IF;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tg_magasin_update_polymorphe` BEFORE UPDATE ON `magasin` FOR EACH ROW BEGIN
    IF NEW.typeEntite = 'Equipement' THEN
        IF NOT EXISTS (SELECT 1 FROM `equipements` WHERE `idEquip` = NEW.idCible) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erreur : L idCible spécifié n existe pas dans la table Equipements.';
        END IF;
    ELSEIF NEW.typeEntite = 'Immobilisation' THEN
        IF NOT EXISTS (SELECT 1 FROM `immobilisations` WHERE `idImmo` = NEW.idCible) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erreur : L idCible spécifié n existe pas dans la table immobilisations.';
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
  `idPanne` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `dateDeclaration` date NOT NULL,
  `gravite` varchar(255) NOT NULL,
  `idUtilisateur` int(5) UNSIGNED ZEROFILL NOT NULL,
  `idEquip` int(11) DEFAULT NULL,
  `matriculeVoiture` varchar(9) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pannes`
--

INSERT INTO `pannes` (`idPanne`, `description`, `dateDeclaration`, `gravite`, `idUtilisateur`, `idEquip`, `matriculeVoiture`) VALUES
(1, 'Surchauffe excessive et coupure automatique', '2026-06-10', 'Majeure', 00003, 2, NULL),
(2, 'Génération automatique suite à l\'enregistrement du véhicule avec le statut : En Panne', '2026-07-03', 'Moyenne', 00000, NULL, '09-475325'),
(3, 'test email', '2026-07-07', 'Moyenne', 00005, 29, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `plan_centre`
--

CREATE TABLE `plan_centre` (
  `idPlan` int(11) NOT NULL,
  `versionPlan` varchar(50) NOT NULL,
  `nb_lignes` int(11) NOT NULL DEFAULT 10,
  `nb_colonnes` int(11) NOT NULL DEFAULT 10,
  `dateCreation` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `plan_centre`
--

INSERT INTO `plan_centre` (`idPlan`, `versionPlan`, `nb_lignes`, `nb_colonnes`, `dateCreation`) VALUES
(1, 'v1.0-Initial', 10, 10, '2026-01-15');

-- --------------------------------------------------------

--
-- Table structure for table `plan_draft`
--

CREATE TABLE `plan_draft` (
  `idDraft` int(11) NOT NULL,
  `idUtilisateur` int(11) NOT NULL,
  `idPlan` int(11) NOT NULL,
  `parentKey` varchar(191) NOT NULL DEFAULT '',
  `nb_lignes` int(11) NOT NULL DEFAULT 10,
  `nb_colonnes` int(11) NOT NULL DEFAULT 10,
  `draft_data` longtext NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `plan_draft`
--

INSERT INTO `plan_draft` (`idDraft`, `idUtilisateur`, `idPlan`, `parentKey`, `nb_lignes`, `nb_colonnes`, `draft_data`, `updated_at`) VALUES
(2, 0, 1, 'cell-6-5', 6, 6, '{\"blocs\":[],\"locaux\":[],\"items\":[{\"type_item\":\"magasin\",\"id_cible_originale\":\"8\",\"matricule\":null,\"label_item\":\"Fauteuil ergonomique de bureau black\",\"row_start\":2,\"col_start\":2,\"id_bloc_parent\":null}]}', '2026-07-07 08:50:22'),
(25, 0, 1, '', 10, 10, '{\"blocs\":[],\"locaux\":[],\"items\":[{\"type_item\":\"magasin\",\"id_cible_originale\":\"7\",\"matricule\":null,\"label_item\":\"Table de bureau en bois 1.80m\",\"row_start\":1,\"col_start\":1,\"id_bloc_parent\":null},{\"type_item\":\"magasin\",\"id_cible_originale\":\"9\",\"matricule\":null,\"label_item\":\"Armoire métallique forte blindée\",\"row_start\":4,\"col_start\":5,\"id_bloc_parent\":null}]}', '2026-07-07 10:57:32');

-- --------------------------------------------------------

--
-- Table structure for table `profils`
--

CREATE TABLE `profils` (
  `idProfil` int(11) NOT NULL,
  `labelProfil` varchar(255) NOT NULL
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
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `idTicket` int(11) NOT NULL,
  `idPanne` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `statut` enum('En attente','En cours','Résolu','Clôturé') NOT NULL DEFAULT 'En attente',
  `dateCreation` datetime NOT NULL DEFAULT current_timestamp(),
  `dateModification` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `idUtilisateur` int(5) UNSIGNED ZEROFILL NOT NULL,
  `nom` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `tel` varchar(15) NOT NULL,
  `motDePasse` varchar(255) NOT NULL,
  `idProfil` int(11) NOT NULL,
  `idLocalAssigne` int(11) DEFAULT NULL,
  `statut` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = Active, 0 = Inactive'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`idUtilisateur`, `nom`, `email`, `tel`, `motDePasse`, `idProfil`, `idLocalAssigne`, `statut`) VALUES
(00000, 'Mohamed Ali Hamdi', 'hamdivrlife@gmail.com', '99858256', 'e7042ac7d09c7bc41c8cfa5749e41858f6980643bc0db1a83cc793d3e24d3f77', 1, NULL, 1),
(00001, 'Sami Ben Ali', 'b.sami@example.com', '98123456', '8566ee8cc961a20f2f00208063764cfc75082eff5b90f989b36e4da08f935e2a', 2, NULL, 1),
(00002, 'Sonia Ben Youssef', 'b.sonia@example.com', '99858256', '52c74ca87333280ea94cc514f6ceefec1f32fd5a44a108905dc9e46f88b7e0a2', 3, NULL, 1),
(00003, 'Wajdi Mansour', 'm.wajdi@example.com', '99858256', '81a16fe30e67d812332cdeda986281b41f59243082b792c13f0e004af77bc0e0', 4, NULL, 1),
(00004, 'Amel Trabelsi', 't.amel@example.com', '99858256', 'a5062280775101377f2ed2a5ac021f9c5ba0be42d583135ef8e94df6316d979e', 5, NULL, 1),
(00005, 'Leila Marzouki', 'm.leila@example.com', '99858256', '707b04df356a78990ef91862af70512a4a9abe612d9181b75fd33a1d2e7ed447', 6, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `voitures`
--

CREATE TABLE `voitures` (
  `matricule` varchar(9) NOT NULL,
  `marque` varchar(100) NOT NULL,
  `modele` varchar(100) DEFAULT NULL,
  `statut` varchar(50) NOT NULL DEFAULT 'Disponible'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `voitures`
--

INSERT INTO `voitures` (`matricule`, `marque`, `modele`, `statut`) VALUES
('09-100002', 'Mahindra', 'Pick-up', 'Disponible'),
('09-100003', 'Peugeot', 'Partner', 'Disponible'),
('09-475325', 'Isuzu', 'D-Max', 'En Panne');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `element_plan`
--
ALTER TABLE `element_plan`
  ADD PRIMARY KEY (`idElement`),
  ADD KEY `idPlan` (`idPlan`),
  ADD KEY `idLocal` (`idLocal`),
  ADD KEY `matriculeVoiture` (`matriculeVoiture`),
  ADD KEY `idMagasin` (`idMagasin`);

--
-- Indexes for table `equipements`
--
ALTER TABLE `equipements`
  ADD PRIMARY KEY (`idEquip`);

--
-- Indexes for table `immobilisations`
--
ALTER TABLE `immobilisations`
  ADD PRIMARY KEY (`idImmo`);

--
-- Indexes for table `journal_logs`
--
ALTER TABLE `journal_logs`
  ADD PRIMARY KEY (`idLog`),
  ADD KEY `idUtilisateur` (`idUtilisateur`);

--
-- Indexes for table `locaux`
--
ALTER TABLE `locaux`
  ADD PRIMARY KEY (`idLocal`);

--
-- Indexes for table `magasin`
--
ALTER TABLE `magasin`
  ADD PRIMARY KEY (`idMagasin`),
  ADD UNIQUE KEY `unique_article` (`typeEntite`,`idCible`),
  ADD UNIQUE KEY `referenceInterne` (`referenceInterne`),
  ADD KEY `idx_liaison_universelle` (`typeEntite`,`idCible`);

--
-- Indexes for table `pannes`
--
ALTER TABLE `pannes`
  ADD PRIMARY KEY (`idPanne`),
  ADD KEY `idUtilisateur` (`idUtilisateur`),
  ADD KEY `idEquip` (`idEquip`),
  ADD KEY `matriculeVoiture` (`matriculeVoiture`);

--
-- Indexes for table `plan_centre`
--
ALTER TABLE `plan_centre`
  ADD PRIMARY KEY (`idPlan`);

--
-- Indexes for table `plan_draft`
--
ALTER TABLE `plan_draft`
  ADD PRIMARY KEY (`idDraft`),
  ADD UNIQUE KEY `uniq_draft` (`idUtilisateur`,`idPlan`,`parentKey`);

--
-- Indexes for table `profils`
--
ALTER TABLE `profils`
  ADD PRIMARY KEY (`idProfil`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`idTicket`),
  ADD KEY `idPanne` (`idPanne`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`idUtilisateur`),
  ADD UNIQUE KEY `unique_email` (`email`),
  ADD KEY `idProfil` (`idProfil`),
  ADD KEY `idLocalAssigne` (`idLocalAssigne`);

--
-- Indexes for table `voitures`
--
ALTER TABLE `voitures`
  ADD PRIMARY KEY (`matricule`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `element_plan`
--
ALTER TABLE `element_plan`
  MODIFY `idElement` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `equipements`
--
ALTER TABLE `equipements`
  MODIFY `idEquip` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `immobilisations`
--
ALTER TABLE `immobilisations`
  MODIFY `idImmo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `journal_logs`
--
ALTER TABLE `journal_logs`
  MODIFY `idLog` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=194;

--
-- AUTO_INCREMENT for table `locaux`
--
ALTER TABLE `locaux`
  MODIFY `idLocal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `magasin`
--
ALTER TABLE `magasin`
  MODIFY `idMagasin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `pannes`
--
ALTER TABLE `pannes`
  MODIFY `idPanne` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `plan_centre`
--
ALTER TABLE `plan_centre`
  MODIFY `idPlan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `plan_draft`
--
ALTER TABLE `plan_draft`
  MODIFY `idDraft` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `profils`
--
ALTER TABLE `profils`
  MODIFY `idProfil` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `idTicket` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `idUtilisateur` int(5) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `element_plan`
--
ALTER TABLE `element_plan`
  ADD CONSTRAINT `fk_element_local` FOREIGN KEY (`idLocal`) REFERENCES `locaux` (`idLocal`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_element_magasin` FOREIGN KEY (`idMagasin`) REFERENCES `magasin` (`idMagasin`) ON DELETE SET NULL,
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
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `fk_tickets_panne` FOREIGN KEY (`idPanne`) REFERENCES `pannes` (`idPanne`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_local` FOREIGN KEY (`idLocalAssigne`) REFERENCES `locaux` (`idLocal`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_users_profil` FOREIGN KEY (`idProfil`) REFERENCES `profils` (`idProfil`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
