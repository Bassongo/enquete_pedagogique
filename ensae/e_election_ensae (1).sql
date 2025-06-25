-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mar. 24 juin 2025 à 04:21
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
-- Base de données : `e_election_ensae`
--

-- --------------------------------------------------------

--
-- Structure de la table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'inscription', 'Nouvelle inscription utilisateur', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 12:36:01'),
(2, 2, 'inscription', 'Nouvelle inscription utilisateur', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 13:53:11'),
(3, 2, 'connexion', 'Connexion réussie', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 13:57:26'),
(4, 2, 'deconnexion', 'Déconnexion utilisateur', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 14:04:00'),
(5, 2, 'duree_session', 'Session de 6.57 minutes', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 14:04:00'),
(6, 2, 'connexion', 'Connexion réussie', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 14:28:25'),
(7, 2, 'deconnexion', 'Déconnexion utilisateur', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 14:55:04'),
(8, 2, 'duree_session', 'Session de 26.65 minutes', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 14:55:04'),
(9, 2, 'connexion', 'Connexion réussie', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 15:03:34'),
(10, 2, 'deconnexion', 'Déconnexion utilisateur', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 15:30:54'),
(11, 2, 'duree_session', 'Session de 27.33 minutes', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 15:30:55'),
(12, 2, 'connexion', 'Connexion réussie', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 15:36:07'),
(13, 2, 'deconnexion', 'Déconnexion utilisateur', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 15:36:10'),
(14, 2, 'duree_session', 'Session de 0.05 minutes', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 15:36:10'),
(15, 2, 'connexion', 'Connexion réussie', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 15:37:24'),
(16, 2, 'deconnexion', 'Déconnexion utilisateur', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 15:51:04'),
(17, 2, 'duree_session', 'Session de 13.67 minutes', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 15:51:04'),
(18, 2, 'connexion', 'Connexion réussie', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 16:06:30'),
(19, 2, 'deconnexion', 'Déconnexion utilisateur', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 16:06:40'),
(20, 2, 'duree_session', 'Session de 0.17 minutes', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 16:06:40'),
(21, 2, 'connexion', 'Connexion réussie', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 16:07:21'),
(22, 2, 'deconnexion', 'Déconnexion utilisateur', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 16:07:39'),
(23, 2, 'duree_session', 'Session de 0.3 minutes', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 16:07:39'),
(24, 2, 'tentative_connexion_echouee', 'Mot de passe incorrect', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 16:08:21'),
(25, 2, 'connexion', 'Connexion réussie', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 16:20:55'),
(26, 2, 'deconnexion', 'Déconnexion utilisateur', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 16:22:16'),
(27, 2, 'duree_session', 'Session de 1.35 minutes', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 16:22:16'),
(28, 2, 'connexion', 'Connexion réussie', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 16:22:33'),
(29, 2, 'deconnexion', 'Déconnexion utilisateur', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 16:22:57'),
(30, 2, 'duree_session', 'Session de 0.4 minutes', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 16:22:57'),
(31, 2, 'connexion', 'Connexion réussie', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 16:49:58'),
(32, 2, 'deconnexion', 'Déconnexion utilisateur', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 16:56:19'),
(33, 2, 'duree_session', 'Session de 6.35 minutes', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 16:56:19'),
(34, 2, 'connexion', 'Connexion réussie', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 16:56:42'),
(35, 3, 'inscription', 'Nouvelle inscription utilisateur', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 17:04:15'),
(36, 4, 'inscription', 'Nouvelle inscription utilisateur', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 17:21:44'),
(37, 5, 'inscription', 'Nouvelle inscription utilisateur', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 17:29:13'),
(38, 6, 'inscription', 'Nouvelle inscription utilisateur', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 17:34:05'),
(39, 7, 'inscription', 'Nouvelle inscription utilisateur', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36 Edg/137.0.0.0', '2025-06-22 17:45:13'),
(40, 8, 'inscription', 'Nouvelle inscription utilisateur', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36 Edg/137.0.0.0', '2025-06-22 18:00:22'),
(41, 2, 'deconnexion', 'Déconnexion utilisateur', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 18:16:19'),
(42, 2, 'duree_session', 'Session de 79.62 minutes', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 18:16:19'),
(43, 9, 'inscription', 'Nouvelle inscription utilisateur', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 18:21:26'),
(44, 9, 'connexion', 'Connexion réussie', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 18:22:02'),
(45, 9, 'deconnexion', 'Déconnexion utilisateur', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 18:22:31'),
(46, 9, 'duree_session', 'Session de 0.48 minutes', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 18:22:31'),
(47, 2, 'connexion', 'Connexion réussie', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 18:22:56'),
(48, 2, 'connexion', 'Connexion réussie', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 23:14:13'),
(49, 2, 'deconnexion', 'Déconnexion utilisateur', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 23:57:20'),
(50, 2, 'duree_session', 'Session de 43.12 minutes', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 23:57:20'),
(51, 2, 'connexion', 'Connexion réussie', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-22 23:57:53'),
(52, 10, 'inscription', 'Nouvelle inscription utilisateur', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-23 18:36:53'),
(53, 10, 'connexion', 'Connexion réussie', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-23 18:36:59'),
(54, 10, 'deconnexion', 'Déconnexion utilisateur', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-23 18:37:34'),
(55, 10, 'duree_session', 'Session de 0.58 minutes', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-23 18:37:34'),
(56, 10, 'connexion', 'Connexion réussie', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-23 18:37:49'),
(57, 10, 'deconnexion', 'Déconnexion utilisateur', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-23 18:38:51'),
(58, 10, 'duree_session', 'Session de 1.03 minutes', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-23 18:38:51'),
(59, 10, 'connexion', 'Connexion réussie', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-23 18:38:57'),
(60, 10, 'start_vote_session', 'Session de vote créée pour le type 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-23 19:00:09'),
(61, 10, 'delete_vote_session', 'Session de vote 1 supprimée', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-23 19:06:39'),
(62, 10, 'start_candidature_session', 'Session de candidature créée pour le type 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-23 19:07:39'),
(63, 10, 'start_vote_session', 'Session de vote créée pour le type 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-23 19:08:18'),
(64, 10, 'remove_member', 'Membre 2 retiré du comité', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-23 19:45:59'),
(65, 10, 'pause_vote_session', 'Session de vote 2 mise en pause', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-23 20:11:33'),
(66, 10, 'activate_vote_session', 'Session de vote 2 activée', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-23 20:12:15'),
(67, 10, 'start_vote_session', 'Session de vote créée pour le type 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-23 20:14:16'),
(68, 10, 'deconnexion', 'Déconnexion utilisateur', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-23 20:45:56'),
(69, 10, 'duree_session', 'Session de 126.98 minutes', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-23 20:45:56'),
(70, 10, 'connexion', 'Connexion réussie', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-23 20:46:00'),
(71, 11, 'inscription', 'Nouvelle inscription utilisateur', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-23 21:41:36'),
(72, 10, 'deconnexion', 'Déconnexion utilisateur', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-23 21:43:17'),
(73, 10, 'duree_session', 'Session de 57.28 minutes', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-23 21:43:17'),
(74, 11, 'connexion', 'Connexion réussie', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-23 21:44:07'),
(75, 1, 'submit_candidature', 'Candidature 1 soumise pour le type 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-24 01:13:59'),
(76, 1, 'deconnexion', 'Déconnexion utilisateur', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-24 01:15:45'),
(77, 1, 'duree_session', 'Session de 211.63 minutes', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-24 01:15:45'),
(78, 10, 'connexion', 'Connexion réussie', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-24 01:16:06'),
(79, 10, 'approve_candidate', 'Candidature 1 approuvée', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-24 01:16:52'),
(80, 10, 'deconnexion', 'Déconnexion utilisateur', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-24 01:18:18'),
(81, 10, 'duree_session', 'Session de 2.2 minutes', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-24 01:18:18'),
(82, 11, 'connexion', 'Connexion réussie', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-24 01:18:55'),
(83, 11, 'submit_candidature', 'Candidature 2 soumise pour le type 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-24 01:19:41'),
(84, 11, 'deconnexion', 'Déconnexion utilisateur', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-24 01:19:56'),
(85, 11, 'duree_session', 'Session de 1.02 minutes', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-24 01:19:56'),
(86, 10, 'connexion', 'Connexion réussie', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-24 01:20:43'),
(87, 10, 'reject_candidate', 'Candidature 2 rejetée - Raison: Vous nette pas ponctuelle', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-24 01:21:54'),
(88, 10, 'delete_candidate', 'Candidature 2 supprimée', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-24 01:22:01'),
(89, 10, 'deconnexion', 'Déconnexion utilisateur', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-24 01:22:17'),
(90, 10, 'duree_session', 'Session de 1.57 minutes', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-24 01:22:17'),
(91, 10, 'connexion', 'Connexion réussie', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-24 01:22:43'),
(92, 10, 'deconnexion', 'Déconnexion utilisateur', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-24 01:22:52'),
(93, 10, 'duree_session', 'Session de 0.15 minutes', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-24 01:22:52'),
(94, 11, 'connexion', 'Connexion réussie', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-24 01:23:27'),
(95, 1, 'deconnexion', 'Déconnexion utilisateur', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-24 02:02:57'),
(96, 1, 'duree_session', 'Session de 39.5 minutes', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-24 02:02:57'),
(97, 10, 'connexion', 'Connexion réussie', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-24 02:03:40'),
(98, 10, 'submit_candidature', 'Candidature 3 soumise pour le type 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-24 02:06:23'),
(99, 10, 'approve_candidate', 'Candidature 3 approuvée', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-24 02:07:40'),
(100, 10, 'deconnexion', 'Déconnexion utilisateur', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-24 02:19:43'),
(101, 10, 'duree_session', 'Session de 16.05 minutes', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-24 02:19:43'),
(102, 10, 'connexion', 'Connexion réussie', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0', '2025-06-24 02:19:48');

-- --------------------------------------------------------

--
-- Structure de la table `candidatures`
--

CREATE TABLE `candidatures` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `election_type_id` int(11) NOT NULL,
  `position_id` int(11) DEFAULT NULL,
  `club_id` int(11) DEFAULT NULL,
  `programme` text NOT NULL,
  `photo_url` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `candidatures`
--

INSERT INTO `candidatures` (`id`, `user_id`, `election_type_id`, `position_id`, `club_id`, `programme`, `photo_url`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, NULL, 'Ceci est un programme de test avec plus de 50 caractères pour valider la soumission de candidature.', NULL, 'approved', '2025-06-24 01:13:59', '2025-06-24 01:16:52'),
(3, 10, 1, 1, NULL, 'je seraije seraije seraije seraije seraije seraije seraije seraije seraije seraije seraije seraije seraije seraije seraije seraije seraije seraije seraije seraije serai', 'assets/img/candidates/candidate_1750730783_685a081f6e40e.png', 'approved', '2025-06-24 02:06:23', '2025-06-24 02:07:40');

-- --------------------------------------------------------

--
-- Structure de la table `candidature_sessions`
--

CREATE TABLE `candidature_sessions` (
  `id` int(11) NOT NULL,
  `election_type_id` int(11) NOT NULL,
  `club_id` int(11) DEFAULT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `candidature_sessions`
--

INSERT INTO `candidature_sessions` (`id`, `election_type_id`, `club_id`, `start_time`, `end_time`, `is_active`, `created_by`, `created_at`) VALUES
(1, 3, NULL, '2025-06-23 20:07:00', '2025-08-23 20:07:00', 1, 10, '2025-06-23 19:07:39'),
(2, 1, NULL, '2025-06-23 23:07:08', '2025-06-30 23:07:08', 1, 2, '2025-06-23 22:07:08'),
(3, 2, 1, '2025-06-23 23:07:08', '2025-06-30 23:07:08', 1, 2, '2025-06-23 22:07:08');

-- --------------------------------------------------------

--
-- Structure de la table `clubs`
--

CREATE TABLE `clubs` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `clubs`
--

INSERT INTO `clubs` (`id`, `name`, `description`, `is_active`, `created_at`) VALUES
(1, 'Club Informatique', 'Club dédié à l\'informatique et aux nouvelles technologies', 1, '2025-06-23 18:55:16'),
(2, 'Club Économie', 'Club d\'économie et de finance', 1, '2025-06-23 18:55:16'),
(3, 'Club Statistique', 'Club de statistique et d\'analyse de données', 1, '2025-06-23 18:55:16'),
(4, 'Club Culturel', 'Club culturel et artistique', 1, '2025-06-23 18:55:16'),
(5, 'Club Sportif', 'Club sportif et activités physiques', 1, '2025-06-23 18:55:16');

-- --------------------------------------------------------

--
-- Structure de la table `election_types`
--

CREATE TABLE `election_types` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `election_types`
--

INSERT INTO `election_types` (`id`, `name`, `description`, `is_active`) VALUES
(1, 'AES', 'Association des Étudiants de l\'ENSAE', 1),
(2, 'Club', 'Élections des clubs étudiants', 1),
(3, 'Classe', 'Élections des délégués de classe', 1);

-- --------------------------------------------------------

--
-- Structure de la table `gmail`
--

CREATE TABLE `gmail` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `gmail`
--

INSERT INTO `gmail` (`id`, `nom`, `email`) VALUES
(1, 'Kossivi Moïse ATTISSO', 'mosesattisso@gmail.com'),
(2, 'Abdou BA', 'ba2664890@gmail.com'),
(3, 'Pape Mamadou BADJI', 'papemamadoubadji82@gmail.com'),
(4, 'Fatoumata BAH', 'fatimabah2210@gmail.com'),
(5, 'Enagnon Justin BOGNON', 'justinbognon96@gmail.com'),
(6, 'Marianne DAÏFERLE', 'mariannedaiferle002@gmail.com'),
(7, 'Mouhammadou DIA', 'mouhammadoudia194@gmail.com'),
(8, 'Papa Magatte DIOP', 'papamagatte8diop@gmail.com'),
(9, 'Seydina Mouhamed DIOP', 'diopseydinamohamed9@gmail.com'),
(10, 'Armand DJEKONBE NDOASNAN', 'djekonbe1er@gmail.com'),
(11, 'Kouami Emmanuel DOSSEKOU', 'dossekoumano@gmail.com'),
(12, 'Aïssatou GUEYE', 'aissatoug15@gmail.com'),
(13, 'Awa GUEYE', 'awag2485@gmail.com'),
(14, 'Josee Clemence JEAZE NGUEMEZI', 'jeazejosee80gmail.com'),
(15, 'Maxwell KASSI MAMADOU', 'kmaxmamadou2902@gmail.com'),
(16, 'Marc MARE', 'marcmare570@gmail.com'),
(17, 'David Christ MEKONTCHOU NZONDE', 'christnzonde@gmail.com'),
(18, 'Saer NDAO', 'saerndao469@gmail.com'),
(19, 'Gilbert OUMSAORE', 'oumsaoregilbert@gmail.com'),
(20, 'Cheikh Oumar SAKHO', 'stade1996@gmail.com'),
(21, 'Ndeye Khary SALL', 'ndeyekharysall0@gmail.com'),
(22, 'Mouhamadou Moustapha SARR', 'mouhamadoumoustaphasarr10@gmail.com'),
(23, 'Wilfred Rod TCHAPDA KOUADJO', 'wilfredkouadjo006'),
(24, 'Naba Amadou Seydou TOURE', 'nabasko70@gmail.com'),
(25, 'Ndeye Salla TOURE', 'ndeyesallatoure0@gmail.com'),
(26, 'Fatou Soumaya WADE', 'wadesoumaya2004@gmail.com'),
(27, 'Poko Ibrahim NOBA', 'nobapokoibrahim@gmail.com');

-- --------------------------------------------------------

--
-- Structure de la table `positions`
--

CREATE TABLE `positions` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `election_type_id` int(11) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `positions`
--

INSERT INTO `positions` (`id`, `name`, `description`, `election_type_id`, `is_active`) VALUES
(1, 'Président', 'Président de l\'Association des Étudiants', 1, 1),
(2, 'Vice-Président', 'Vice-Président de l\'Association des Étudiants', 1, 1),
(3, 'Secrétaire Général', 'Secrétaire Général de l\'Association des Étudiants', 1, 1),
(4, 'Trésorier', 'Trésorier de l\'Association des Étudiants', 1, 1),
(5, 'Président Club', 'Président du club', 2, 1),
(6, 'Vice-Président Club', 'Vice-Président du club', 2, 1),
(7, 'Délégué de Classe', 'Délégué représentant la classe', 3, 1),
(8, 'Sous-Délégué', 'Sous-délégué de classe', 3, 1);

-- --------------------------------------------------------

--
-- Structure de la table `results`
--

CREATE TABLE `results` (
  `id` int(11) NOT NULL,
  `candidature_id` int(11) NOT NULL,
  `election_type_id` int(11) NOT NULL,
  `vote_session_id` int(11) NOT NULL,
  `total_votes` int(11) DEFAULT 0,
  `percentage` decimal(5,2) DEFAULT 0.00,
  `rank` int(11) DEFAULT 0,
  `is_winner` tinyint(1) DEFAULT 0,
  `calculated_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `statistics`
--

CREATE TABLE `statistics` (
  `id` int(11) NOT NULL,
  `election_type_id` int(11) NOT NULL,
  `vote_session_id` int(11) NOT NULL,
  `total_voters` int(11) DEFAULT 0,
  `total_votes_cast` int(11) DEFAULT 0,
  `participation_rate` decimal(5,2) DEFAULT 0.00,
  `calculated_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `classe` enum('AS1','AS2','AS3') NOT NULL,
  `role` enum('student','admin','committee') DEFAULT 'student',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `email`, `username`, `password_hash`, `classe`, `role`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'marc@ensae.sn', 'Marko', '$2y$10$OoEkW/KqHYn9C0s.04hfDOtXU.JHV6ADKR1yvXvkM.7wiqt2aVNpe', 'AS2', 'student', 1, '2025-06-22 12:36:01', '2025-06-22 12:36:01'),
(2, 'marcmare570@gmail.com', 'MARE', '$2y$10$p4rnbe1qpwizq7tl1GoihOv8EK3yJ21LTlUYp3m/luJxWNeTeYR2S', 'AS2', 'student', 1, '2025-06-22 13:53:11', '2025-06-23 19:45:59'),
(3, 'nabasko70@gmail.com', 'Naba', '$2y$10$eyysmAjVYj6SHMtlUczq3e57Q9quvzHwy3nq4Rjqd/md0qNFc.RRe', 'AS3', 'student', 1, '2025-06-22 17:04:15', '2025-06-22 17:04:15'),
(4, 'christnzonde@gmail.com', 'Ngmahaze', '$2y$10$yEDxWNga86IJxaRNo9Gba.wFk9Jw7Rik7CXUws9NAP7u3JibeBKaS', 'AS2', 'student', 1, '2025-06-22 17:21:44', '2025-06-22 17:21:44'),
(5, 'oumsaoregilbert@gmail.com', 'gilbert', '$2y$10$YlDrSLtbtkHbFnXhr5HoSeP4yXouElAKuua9JKBzDqSwCTJoJkADK', 'AS2', 'student', 1, '2025-06-22 17:29:13', '2025-06-22 17:29:13'),
(6, 'dossekoumano@gmail.com', 'emmanuel', '$2y$10$Y.AI.KGogllzB4K2.N/Ea.hA7KcUN.NYyjAI/37ekWzOS9utk8Jfi', 'AS3', 'student', 1, '2025-06-22 17:34:04', '2025-06-22 17:34:04'),
(7, 'mosesattisso@gmail.com', 'moises', '$2y$10$/kCUxbjBkt4tfv4voK/f/uDlNXx40N3a4xZ/PkiAusdNC6T1.h.R6', 'AS1', 'student', 1, '2025-06-22 17:45:12', '2025-06-22 17:45:12'),
(8, 'ba2664890@gmail.com', 'abou', '$2y$10$f5XgXQ1zff6VlvGRD/12ueEsdZbn86nM7LX5P1mTt1dqNyjs3loy6', 'AS1', 'student', 1, '2025-06-22 18:00:22', '2025-06-22 18:00:22'),
(9, 'stade1996@gmail.com', 'Sakho', '$2y$10$BPpYH.BTh6NAfnK27Tj/o..kf/LTnjYCiLwWjRJvdUTQaUjqHcR0e', 'AS3', 'student', 1, '2025-06-22 18:21:26', '2025-06-22 18:21:26'),
(10, 'papamagatte8diop@gmail.com', 'MOHAMED', '$2y$10$ZwKt5cmiY6EDwt7aviL1QeKL0KDZn5W7RcMqIcN8OJ3JSvkWN1r7m', 'AS3', 'admin', 1, '2025-06-23 18:36:53', '2025-06-23 18:38:46'),
(11, 'diopseydinamohamed9@gmail.com', 'Nguema', '$2y$10$Oqnf6ELAdT8iqjj3OUqs3OnvC9/QTrxn8jubY91A5ge1vkVGdt.ua', 'AS2', 'student', 1, '2025-06-23 21:41:36', '2025-06-23 21:41:36');

-- --------------------------------------------------------

--
-- Structure de la table `votes`
--

CREATE TABLE `votes` (
  `id` int(11) NOT NULL,
  `voter_id` int(11) NOT NULL,
  `candidature_id` int(11) NOT NULL,
  `election_type_id` int(11) NOT NULL,
  `vote_session_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `votes`
--

INSERT INTO `votes` (`id`, `voter_id`, `candidature_id`, `election_type_id`, `vote_session_id`, `created_at`) VALUES
(1, 1, 1, 1, 4, '2025-06-24 02:00:45'),
(2, 10, 1, 1, 4, '2025-06-24 02:04:25');

-- --------------------------------------------------------

--
-- Structure de la table `vote_sessions`
--

CREATE TABLE `vote_sessions` (
  `id` int(11) NOT NULL,
  `election_type_id` int(11) NOT NULL,
  `club_id` int(11) DEFAULT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `vote_sessions`
--

INSERT INTO `vote_sessions` (`id`, `election_type_id`, `club_id`, `start_time`, `end_time`, `is_active`, `created_by`, `created_at`) VALUES
(2, 3, NULL, '2025-06-23 20:08:00', '2025-08-16 20:08:00', 1, 10, '2025-06-23 19:08:18'),
(3, 2, 3, '2025-06-23 21:14:00', '2025-07-23 21:14:00', 1, 10, '2025-06-23 20:14:16'),
(4, 1, NULL, '2025-06-23 23:07:08', '2025-06-26 23:07:08', 1, 2, '2025-06-23 22:07:08');

-- --------------------------------------------------------

--
-- Structure de la table `committee_election_types`
--

CREATE TABLE `committee_election_types` (
  `user_id` int(11) NOT NULL,
  `election_type_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Structure de la table `committees`
--

CREATE TABLE `committees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Structure de la table `committee_members`
--

CREATE TABLE `committee_members` (
  `committee_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`committee_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `candidatures`
--
ALTER TABLE `candidatures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `election_type_id` (`election_type_id`),
  ADD KEY `position_id` (`position_id`),
  ADD KEY `club_id` (`club_id`);

--
-- Index pour la table `candidature_sessions`
--
ALTER TABLE `candidature_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `election_type_id` (`election_type_id`),
  ADD KEY `club_id` (`club_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Index pour la table `clubs`
--
ALTER TABLE `clubs`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `election_types`
--
ALTER TABLE `election_types`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `gmail`
--
ALTER TABLE `gmail`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `positions`
--
ALTER TABLE `positions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `election_type_id` (`election_type_id`);

--
-- Index pour la table `results`
--
ALTER TABLE `results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `candidature_id` (`candidature_id`),
  ADD KEY `election_type_id` (`election_type_id`),
  ADD KEY `vote_session_id` (`vote_session_id`);

--
-- Index pour la table `statistics`
--
ALTER TABLE `statistics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `election_type_id` (`election_type_id`),
  ADD KEY `vote_session_id` (`vote_session_id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Index pour la table `votes`
--
ALTER TABLE `votes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_vote` (`voter_id`,`candidature_id`,`vote_session_id`),
  ADD KEY `candidature_id` (`candidature_id`),
  ADD KEY `election_type_id` (`election_type_id`),
  ADD KEY `vote_session_id` (`vote_session_id`);

--
-- Index pour la table `vote_sessions`
--
ALTER TABLE `vote_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `election_type_id` (`election_type_id`),
  ADD KEY `club_id` (`club_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Index pour la table `committee_election_types`
--
ALTER TABLE `committee_election_types`
  ADD PRIMARY KEY (`user_id`,`election_type_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `election_type_id` (`election_type_id`);



-- Index pour la table `committee_members`
ALTER TABLE `committee_members`
  ADD KEY `committee_id` (`committee_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

--
-- AUTO_INCREMENT pour la table `candidatures`
--
ALTER TABLE `candidatures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `candidature_sessions`
--
ALTER TABLE `candidature_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `clubs`
--
ALTER TABLE `clubs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `election_types`
--
ALTER TABLE `election_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `gmail`
--
ALTER TABLE `gmail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT pour la table `positions`
--
ALTER TABLE `positions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `results`
--
ALTER TABLE `results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `statistics`
--
ALTER TABLE `statistics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `votes`
--
ALTER TABLE `votes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `vote_sessions`
--
ALTER TABLE `vote_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

-- AUTO_INCREMENT pour la table `committees`
ALTER TABLE `committees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `candidatures`
--
ALTER TABLE `candidatures`
  ADD CONSTRAINT `candidatures_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `candidatures_ibfk_2` FOREIGN KEY (`election_type_id`) REFERENCES `election_types` (`id`),
  ADD CONSTRAINT `candidatures_ibfk_3` FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`),
  ADD CONSTRAINT `candidatures_ibfk_4` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`);

--
-- Contraintes pour la table `candidature_sessions`
--
ALTER TABLE `candidature_sessions`
  ADD CONSTRAINT `candidature_sessions_ibfk_1` FOREIGN KEY (`election_type_id`) REFERENCES `election_types` (`id`),
  ADD CONSTRAINT `candidature_sessions_ibfk_2` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`),
  ADD CONSTRAINT `candidature_sessions_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `positions`
--
ALTER TABLE `positions`
  ADD CONSTRAINT `positions_ibfk_1` FOREIGN KEY (`election_type_id`) REFERENCES `election_types` (`id`);

--
-- Contraintes pour la table `results`
--
ALTER TABLE `results`
  ADD CONSTRAINT `results_ibfk_1` FOREIGN KEY (`candidature_id`) REFERENCES `candidatures` (`id`),
  ADD CONSTRAINT `results_ibfk_2` FOREIGN KEY (`election_type_id`) REFERENCES `election_types` (`id`),
  ADD CONSTRAINT `results_ibfk_3` FOREIGN KEY (`vote_session_id`) REFERENCES `vote_sessions` (`id`);

--
-- Contraintes pour la table `statistics`
--
ALTER TABLE `statistics`
  ADD CONSTRAINT `statistics_ibfk_1` FOREIGN KEY (`election_type_id`) REFERENCES `election_types` (`id`),
  ADD CONSTRAINT `statistics_ibfk_2` FOREIGN KEY (`vote_session_id`) REFERENCES `vote_sessions` (`id`);

--
-- Contraintes pour la table `votes`
--
ALTER TABLE `votes`
  ADD CONSTRAINT `votes_ibfk_1` FOREIGN KEY (`voter_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `votes_ibfk_2` FOREIGN KEY (`candidature_id`) REFERENCES `candidatures` (`id`),
  ADD CONSTRAINT `votes_ibfk_3` FOREIGN KEY (`election_type_id`) REFERENCES `election_types` (`id`),
  ADD CONSTRAINT `votes_ibfk_4` FOREIGN KEY (`vote_session_id`) REFERENCES `vote_sessions` (`id`);

--
-- Contraintes pour la table `vote_sessions`
--
ALTER TABLE `vote_sessions`
  ADD CONSTRAINT `vote_sessions_ibfk_1` FOREIGN KEY (`election_type_id`) REFERENCES `election_types` (`id`),
  ADD CONSTRAINT `vote_sessions_ibfk_2` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`),
  ADD CONSTRAINT `vote_sessions_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `committee_election_types`
--
ALTER TABLE `committee_election_types`
  ADD CONSTRAINT `committee_election_types_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `committee_election_types_ibfk_2` FOREIGN KEY (`election_type_id`) REFERENCES `election_types` (`id`);

-- Contraintes pour la table `committee_members`
ALTER TABLE `committee_members`
  ADD CONSTRAINT `committee_members_ibfk_1` FOREIGN KEY (`committee_id`) REFERENCES `committees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `committee_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

-- Contraintes pour la table `committees`
ALTER TABLE `committees`
  ADD CONSTRAINT `committees_unique_name` UNIQUE (`name`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
