-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Aug 15, 2025 at 09:50 AM
-- Server version: 10.11.11-MariaDB-0+deb12u1
-- PHP Version: 8.4.8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ursgpoo`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_actions`
--

CREATE TABLE `admin_actions` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `target_user_id` int(11) DEFAULT NULL,
  `ban_userId` int(11) DEFAULT NULL,
  `ban_username` varchar(20) DEFAULT NULL,
  `ban_email` varchar(20) DEFAULT NULL,
  `target_game_username` varchar(20) DEFAULT NULL,
  `action_type` enum('Censor Bio','Censor Picture','Ban','Updated Currency','Added Character','Dismissed','Requesting ban','Censor Bio and Picture', 'Added Item', 'Removed Item', 'Added Partner', 'Removed Partner') NOT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `banned_users`
--

CREATE TABLE `banned_users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `ban_date` datetime DEFAULT current_timestamp(),
  `reason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `block`
--

CREATE TABLE `block` (
  `block_id` int(11) NOT NULL,
  `block_senderId` int(11) NOT NULL,
  `block_receiverId` int(11) NOT NULL,
  `block_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chatmessage`
--

CREATE TABLE `chatmessage` (
  `chat_id` int(11) NOT NULL,
  `chat_senderId` int(11) NOT NULL,
  `chat_receiverId` int(11) NOT NULL,
  `chat_message` text NOT NULL,
  `chat_status` varchar(20) NOT NULL,
  `chat_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `chat_replyTo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `discord`
--

CREATE TABLE `discord` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `discord_id` varchar(255) NOT NULL,
  `discord_username` varchar(255) NOT NULL,
  `discord_email` varchar(255) DEFAULT NULL,
  `discord_avatar` varchar(255) DEFAULT NULL,
  `access_token` text DEFAULT NULL,
  `refresh_token` text DEFAULT NULL,
  `channel_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `friendrequest`
--

CREATE TABLE `friendrequest` (
  `fr_id` int(11) NOT NULL,
  `fr_senderId` int(11) NOT NULL,
  `fr_receiverId` int(11) NOT NULL,
  `fr_date` date NOT NULL,
  `fr_status` varchar(20) NOT NULL,
  `fr_rejectedAt` timestamp NULL DEFAULT NULL,
  `fr_acceptedAt` timestamp NULL DEFAULT NULL,
  `fr_notifReadPending` tinyint(1) NOT NULL DEFAULT 0,
  `fr_notifReadAccepted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `game`
--

CREATE TABLE `game` (
  `game_username` varchar(255) NOT NULL,
  `game_main` varchar(255) NOT NULL,
  `hint_affiliation` varchar(255) DEFAULT NULL,
  `hint_gender` varchar(50) DEFAULT NULL,
  `hint_guess` varchar(255) DEFAULT NULL,
  `game_picture` text DEFAULT NULL,
  `game_date` date DEFAULT NULL,
  `game_game` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `googleuser`
--

CREATE TABLE `googleuser` (
  `google_userId` int(11) NOT NULL,
  `google_id` varchar(255) NOT NULL,
  `google_fullName` varchar(100) NOT NULL,
  `google_firstName` varchar(50) DEFAULT NULL,
  `google_lastName` varchar(50) DEFAULT NULL,
  `google_email` varchar(100) NOT NULL,
  `google_confirmEmail` tinyint(1) DEFAULT NULL,
  `google_masterToken` varchar(128) DEFAULT NULL,
  `google_masterTokenWebsite` varchar(128) DEFAULT NULL,
  `google_createdWithRSO` tinyint(1) NOT NULL DEFAULT 0,
  `google_createdWithDiscord` tinyint(4) NOT NULL DEFAULT 0,
  `google_unsubscribeMails` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `items_id` int(11) NOT NULL,
  `items_name` varchar(255) NOT NULL,
  `items_price` int(10) NOT NULL,
  `items_desc` text DEFAULT NULL,
  `items_picture` varchar(255) DEFAULT NULL,
  `items_category` varchar(100) DEFAULT NULL,
  `items_discount` decimal(5,2) DEFAULT 0.00,
  `items_isActive` tinyint(1) DEFAULT 1,
  `items_createdAt` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leagueoflegends`
--

CREATE TABLE `leagueoflegends` (
  `lol_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `lol_noChamp` smallint(6) NOT NULL DEFAULT 0,
  `lol_main1` varchar(20) DEFAULT NULL,
  `lol_main2` varchar(20) DEFAULT NULL,
  `lol_main3` varchar(20) DEFAULT NULL,
  `lol_rank` varchar(20) NOT NULL,
  `lol_role` varchar(20) NOT NULL,
  `lol_server` varchar(20) NOT NULL,
  `lol_account` varchar(20) DEFAULT NULL,
  `lol_verificationCode` varchar(15) DEFAULT NULL,
  `lol_verified` tinyint(1) NOT NULL DEFAULT 0,
  `lol_sUsername` varchar(40) DEFAULT NULL,
  `lol_sUsernameId` varchar(200) DEFAULT NULL,
  `lol_sPuuid` varchar(100) DEFAULT NULL,
  `lol_sLevel` int(11) DEFAULT NULL,
  `lol_sRank` varchar(30) DEFAULT NULL,
  `lol_sProfileIcon` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `matchingscore`
--

CREATE TABLE `matchingscore` (
  `match_id` int(11) NOT NULL,
  `match_userMatching` int(11) NOT NULL,
  `match_userMatched` int(11) NOT NULL,
  `match_score` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications_queue`
--

CREATE TABLE `notifications_queue` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `type` enum('phone','browser') NOT NULL,
  `endpoint` text DEFAULT NULL,
  `p256dh` text DEFAULT NULL,
  `auth` text DEFAULT NULL,
  `expoToken` varchar(128) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `sent` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `partners`
--

CREATE TABLE `partners` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `picture_path` varchar(255) NOT NULL,
  `social_links` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`social_links`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `playerfinder`
--

CREATE TABLE `playerfinder` (
  `pf_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `pf_role` varchar(50) DEFAULT NULL,
  `pf_rank` varchar(50) DEFAULT NULL,
  `pf_description` text DEFAULT NULL,
  `pf_voiceChat` tinyint(1) DEFAULT 0,
  `pf_game` varchar(20) DEFAULT NULL,
  `pf_createdAt` timestamp NULL DEFAULT current_timestamp(),
  `pf_peopleInterest` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL,
  `reporter_id` int(11) NOT NULL,
  `reported_id` int(11) DEFAULT NULL,
  `content_id` int(11) DEFAULT NULL,
  `content_type` enum('post','comment','profile') NOT NULL,
  `reason` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `status` enum('pending','reviewed','Dismissed','Request Ban') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `temporary_channels`
--

CREATE TABLE `temporary_channels` (
  `id` int(11) NOT NULL,
  `channel_id` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `expiry_time` timestamp NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user_id` int(11) NOT NULL,
  `google_userId` int(11) NOT NULL,
  `user_username` varchar(100) NOT NULL,
  `user_gender` varchar(30) NOT NULL,
  `user_age` int(11) NOT NULL,
  `user_kindOfGamer` varchar(50) NOT NULL,
  `user_shortBio` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `user_picture` varchar(200) DEFAULT NULL,
  `user_bonusPicture` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`user_bonusPicture`)),
  `user_discord` varchar(200) DEFAULT NULL,
  `user_instagram` varchar(200) DEFAULT NULL,
  `user_twitter` varchar(200) DEFAULT NULL,
  `user_twitch` varchar(200) DEFAULT NULL,
  `user_bluesky` varchar(200) DEFAULT NULL,
  `user_game` varchar(20) NOT NULL,
  `user_token` varchar(128) DEFAULT NULL,
  `user_deletionToken` varchar(128) DEFAULT NULL,
  `user_deletionTokenExpiry` timestamp NULL DEFAULT NULL,
  `user_currency` int(10) DEFAULT 0,
  `user_isVip` tinyint(1) NOT NULL DEFAULT 0,
  `user_isPartner` tinyint(4) NOT NULL DEFAULT 0,
  `user_isCertified` tinyint(4) NOT NULL DEFAULT 0,
  `user_hasChatFilter` tinyint(1) DEFAULT 0,
  `user_lastRequestTime` timestamp NULL DEFAULT NULL,
  `user_lastReward` timestamp NULL DEFAULT NULL,
  `user_streak` int(11) NOT NULL DEFAULT 0,
  `user_isOnline` tinyint(4) NOT NULL DEFAULT 0,
  `user_lastSeen` datetime DEFAULT NULL,
  `user_arcane` varchar(20) DEFAULT NULL,
  `user_ignore` tinyint(4) NOT NULL DEFAULT 0,
  `arcane_snapshot` int(10) DEFAULT NULL,
  `user_isLooking` tinyint(4) NOT NULL DEFAULT 0,
  `user_requestIsLooking` timestamp NOT NULL,
  `user_lastCompletedGame` date DEFAULT NULL,
  `user_totalCompletedGame` int(11) NOT NULL DEFAULT 0,
  `user_friendsInvited` int(5) DEFAULT 0,
  `user_notificationPermission` tinyint(4) NOT NULL DEFAULT 0,
  `user_notificationEndPoint` text DEFAULT NULL,
  `user_notificationP256dh` text DEFAULT NULL,
  `user_notificationAuth` text DEFAULT NULL,
  `user_personalityTestResult` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `userlookingfor`
--

CREATE TABLE `userlookingfor` (
  `lf_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `lf_gender` varchar(20) NOT NULL,
  `lf_kindofgamer` varchar(50) NOT NULL,
  `lf_game` varchar(20) NOT NULL,
  `lf_filteredServer` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`lf_filteredServer`)),
  `lf_lolNoChamp` smallint(6) NOT NULL DEFAULT 0,
  `lf_lolmain1` varchar(20) DEFAULT NULL,
  `lf_lolmain2` varchar(20) DEFAULT NULL,
  `lf_lolmain3` varchar(20) DEFAULT NULL,
  `lf_lolrank` varchar(20) DEFAULT NULL,
  `lf_lolrole` varchar(20) DEFAULT NULL,
  `lf_valNoChamp` smallint(6) NOT NULL DEFAULT 0,
  `lf_valmain1` varchar(20) DEFAULT NULL,
  `lf_valmain2` varchar(20) DEFAULT NULL,
  `lf_valmain3` varchar(20) DEFAULT NULL,
  `lf_valrank` varchar(20) DEFAULT NULL,
  `lf_valrole` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_activity_log`
--

CREATE TABLE `user_activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_time` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_items`
--

CREATE TABLE `user_items` (
  `userItems_id` int(11) NOT NULL,
  `userItems_userId` int(11) NOT NULL,
  `userItems_itemId` int(11) NOT NULL,
  `userItems_boughtAt` timestamp NULL DEFAULT current_timestamp(),
  `userItems_isUsed` tinyint(4) NOT NULL DEFAULT 0,
  `userItems_givenAsPartner` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_ratings`
--

CREATE TABLE `user_ratings` (
  `id` int(11) NOT NULL,
  `rater_id` int(11) NOT NULL,
  `rated_user_id` int(11) NOT NULL,
  `match_id` varchar(50) NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `valorant`
--

CREATE TABLE `valorant` (
  `valorant_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `valorant_noChamp` smallint(6) NOT NULL DEFAULT 0,
  `valorant_main1` varchar(20) DEFAULT NULL,
  `valorant_main2` varchar(20) DEFAULT NULL,
  `valorant_main3` varchar(20) DEFAULT NULL,
  `valorant_rank` varchar(20) NOT NULL,
  `valorant_role` varchar(20) NOT NULL,
  `valorant_server` varchar(20) NOT NULL,
  `valorant_account` varchar(20) DEFAULT NULL,
  `valorant_verified` tinyint(1) NOT NULL DEFAULT 0,
  `valorant_aUsername` varchar(40) DEFAULT NULL,
  `valorant_aUsernameId` varchar(200) DEFAULT NULL,
  `valorant_aPuuid` varchar(100) DEFAULT NULL,
  `valorant_aLevel` int(11) DEFAULT NULL,
  `valorant_aRank` varchar(30) DEFAULT NULL,
  `valorant_aProfileIcon` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_actions`
--
ALTER TABLE `admin_actions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `admin_actions_ibfk_2` (`target_user_id`);

--
-- Indexes for table `banned_users`
--
ALTER TABLE `banned_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `block`
--
ALTER TABLE `block`
  ADD PRIMARY KEY (`block_id`),
  ADD KEY `block_senderId` (`block_senderId`),
  ADD KEY `block_receiverId` (`block_receiverId`);

--
-- Indexes for table `chatmessage`
--
ALTER TABLE `chatmessage`
  ADD PRIMARY KEY (`chat_id`),
  ADD KEY `chat_senderId` (`chat_senderId`),
  ADD KEY `chat_receiverId` (`chat_receiverId`);

--
-- Indexes for table `discord`
--
ALTER TABLE `discord`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `discord_id` (`discord_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `friendrequest`
--
ALTER TABLE `friendrequest`
  ADD PRIMARY KEY (`fr_id`),
  ADD UNIQUE KEY `unique_friend_request` (`fr_senderId`,`fr_receiverId`),
  ADD KEY `fr_senderId` (`fr_senderId`),
  ADD KEY `fr_receiverId` (`fr_receiverId`);

--
-- Indexes for table `game`
--
ALTER TABLE `game`
  ADD PRIMARY KEY (`game_username`);

--
-- Indexes for table `googleuser`
--
ALTER TABLE `googleuser`
  ADD PRIMARY KEY (`google_userId`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`items_id`);

--
-- Indexes for table `leagueoflegends`
--
ALTER TABLE `leagueoflegends`
  ADD PRIMARY KEY (`lol_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `matchingscore`
--
ALTER TABLE `matchingscore`
  ADD PRIMARY KEY (`match_id`),
  ADD UNIQUE KEY `unique_match` (`match_userMatching`,`match_userMatched`),
  ADD KEY `match_userMatching` (`match_userMatching`);

--
-- Indexes for table `notifications_queue`
--
ALTER TABLE `notifications_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `sender_id` (`sender_id`);

--
-- Indexes for table `partners`
--
ALTER TABLE `partners`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `playerfinder`
--
ALTER TABLE `playerfinder`
  ADD PRIMARY KEY (`pf_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `reporter_id_fk` (`reporter_id`),
  ADD KEY `reported_id_fk` (`reported_id`);

--
-- Indexes for table `temporary_channels`
--
ALTER TABLE `temporary_channels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_temp_channels_user` (`user_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `google_userId` (`google_userId`);

--
-- Indexes for table `userlookingfor`
--
ALTER TABLE `userlookingfor`
  ADD PRIMARY KEY (`lf_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `activity_time` (`activity_time`);

--
-- Indexes for table `user_items`
--
ALTER TABLE `user_items`
  ADD PRIMARY KEY (`userItems_id`),
  ADD KEY `fk_user` (`userItems_userId`),
  ADD KEY `fk_item` (`userItems_itemId`);

--
-- Indexes for table `user_ratings`
--
ALTER TABLE `user_ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_rating` (`rater_id`,`rated_user_id`,`match_id`),
  ADD KEY `rated_user_id` (`rated_user_id`);

--
-- Indexes for table `valorant`
--
ALTER TABLE `valorant`
  ADD PRIMARY KEY (`valorant_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_actions`
--
ALTER TABLE `admin_actions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `banned_users`
--
ALTER TABLE `banned_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `block`
--
ALTER TABLE `block`
  MODIFY `block_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chatmessage`
--
ALTER TABLE `chatmessage`
  MODIFY `chat_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `discord`
--
ALTER TABLE `discord`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `friendrequest`
--
ALTER TABLE `friendrequest`
  MODIFY `fr_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `googleuser`
--
ALTER TABLE `googleuser`
  MODIFY `google_userId` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `items_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leagueoflegends`
--
ALTER TABLE `leagueoflegends`
  MODIFY `lol_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `matchingscore`
--
ALTER TABLE `matchingscore`
  MODIFY `match_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications_queue`
--
ALTER TABLE `notifications_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `partners`
--
ALTER TABLE `partners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `playerfinder`
--
ALTER TABLE `playerfinder`
  MODIFY `pf_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `temporary_channels`
--
ALTER TABLE `temporary_channels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `userlookingfor`
--
ALTER TABLE `userlookingfor`
  MODIFY `lf_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_items`
--
ALTER TABLE `user_items`
  MODIFY `userItems_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_ratings`
--
ALTER TABLE `user_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `valorant`
--
ALTER TABLE `valorant`
  MODIFY `valorant_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_actions`
--
ALTER TABLE `admin_actions`
  ADD CONSTRAINT `admin_actions_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `user` (`user_id`),
  ADD CONSTRAINT `admin_actions_ibfk_2` FOREIGN KEY (`target_user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `block`
--
ALTER TABLE `block`
  ADD CONSTRAINT `fk_block_receiverrId` FOREIGN KEY (`block_receiverId`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_block_senderId` FOREIGN KEY (`block_senderId`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `chatmessage`
--
ALTER TABLE `chatmessage`
  ADD CONSTRAINT `fk_chat_receiverId` FOREIGN KEY (`chat_receiverId`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_chat_senderId` FOREIGN KEY (`chat_senderId`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `discord`
--
ALTER TABLE `discord`
  ADD CONSTRAINT `discord_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `friendrequest`
--
ALTER TABLE `friendrequest`
  ADD CONSTRAINT `fk_fr_receiverId` FOREIGN KEY (`fr_receiverId`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_fr_senderId` FOREIGN KEY (`fr_senderId`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_ratings`
--
ALTER TABLE `user_ratings`
  ADD CONSTRAINT `user_ratings_ibfk_1` FOREIGN KEY (`rater_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_ratings_ibfk_2` FOREIGN KEY (`rated_user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;
COMMIT;

ALTER TABLE `googleuser` ADD COLUMN `last_notified_at` DATETIME DEFAULT NULL;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- Insert Google users
INSERT INTO googleuser (google_userId, google_id, google_fullName, google_firstName, google_lastName, google_email, google_confirmEmail)
VALUES
  (1, 'g123456', 'Alice Smith', 'Alice', 'Smith', 'alice@example.com', 1),
  (2, 'g654321', 'Bob Johnson', 'Bob', 'Johnson', 'bob@example.com', 1),
  (3, 'g789012', 'Charlie Lee', 'Charlie', 'Lee', 'charlie@example.com', 1);

-- Insert users
INSERT INTO user (user_id, google_userId, user_username, user_gender, user_age, user_kindOfGamer, user_shortBio, user_game, user_isVip, user_isOnline, user_lastRequestTime, user_lastReward)
VALUES
  (1, 1, 'AliceGamer', 'Female', 25, 'Casual', 'Love cozy games and chill vibes.', 'Valorant', 1, 1, NOW(), NOW()),
  (2, 2, 'BobThePro', 'Male', 28, 'Competitive', 'FPS enthusiast, always up for a challenge.', 'League of Legends', 0, 1, NOW(), NOW()),
  (3, 3, 'CharlieChill', 'Non-binary', 22, 'Social', 'Here to make friends and have fun!', 'Valorant', 0, 0, NOW(), NOW());

-- Insert playerfinder entries
INSERT INTO playerfinder (pf_id, user_id, pf_role, pf_rank, pf_description, pf_voiceChat, pf_game)
VALUES
  (1, 1, 'Support', 'Gold', 'Looking for friendly teammates.', 1, 'League of Legends'),
  (2, 2, 'ADC', 'Platinum', 'Serious ranked grind.', 1, 'League of Legends'),
  (3, 3, 'Flex', 'Silver', 'Just want to play and chat.', 0, 'League of Legends');

-- Insert chat messages
INSERT INTO chatmessage (chat_id, chat_senderId, chat_receiverId, chat_message, chat_status)
VALUES
  (1, 1, 2, 'Hey Bob, want to play Valorant tonight?', 'sent'),
  (2, 2, 1, 'Sure Alice, lets do it!', 'sent'),
  (3, 3, 1, 'Hi Alice, nice to meet you!', 'sent');

-- Insert friend requests
INSERT INTO friendrequest (fr_id, fr_senderId, fr_receiverId, fr_date, fr_status)
VALUES
  (1, 1, 2, '2025-08-10', 'accepted'),
  (2, 2, 3, '2025-08-11', 'pending'),
  (3, 3, 1, '2025-08-12', 'rejected');

-- Insert items
INSERT INTO items (items_id, items_name, items_price, items_desc, items_category)
VALUES
  (1, 'VIP Badge', 100, 'Special badge for VIP users.', 'Cosmetic'),
  (2, 'Double XP', 50, 'Earn double XP for 24 hours.', 'Boost');

-- Give items to users
INSERT INTO user_items (userItems_id, userItems_userId, userItems_itemId, userItems_isUsed)
VALUES
  (1, 1, 1, 1),
  (2, 2, 2, 0);

-- League of Legends profiles (bound to user)
INSERT INTO leagueoflegends (lol_id, user_id, lol_noChamp, lol_main1, lol_main2, lol_main3, lol_rank, lol_role, lol_server)
VALUES
  (1, 1, 0, 'Lux', 'Janna', 'Sona', 'Gold', 'Support', 'Europe West'),
  (2, 2, 0, 'Ezreal', 'Jhin', 'Caitlyn', 'Platinum', 'ADC', 'Europe West'),
  (3, 3, 0, 'Thresh', 'Leona', 'Nautilus', 'Silver', 'Support', 'Europe West');

INSERT INTO valorant (valorant_id, user_id, valorant_noChamp, valorant_main1, valorant_rank, valorant_role, valorant_server)
VALUES
  (1, 1, 0, 'Sage', 'Gold', 'Support', 'EU'),
  (2, 3, 0, 'Jett', 'Silver', 'Flex', 'NA');

-- Insert a few activity logs
INSERT INTO user_activity_log (id, user_id, activity_time)
VALUES
  (1, 1, '2025-08-15 10:00:00'),
  (2, 2, '2025-08-15 10:05:00'),
  (3, 3, '2025-08-15 10:10:00');

INSERT INTO userlookingfor (lf_id, user_id, lf_gender, lf_kindofgamer, lf_game, lf_lolNoChamp, lf_lolmain1, lf_lolmain2, lf_lolmain3, lf_lolrank, lf_lolrole)
VALUES
  (1, 1, 'Any', 'Casual', 'League of Legends', 0, 'Jinx', 'Seraphine', 'Nami', 'Gold', 'ADC'),
  (2, 2, 'Female', 'Competitive', 'League of Legends', 0, 'Lux', 'Morgana', 'Karma', 'Platinum', 'Support'),
  (3, 3, 'Any', 'Social', 'League of Legends', 0, 'Ashe', 'Miss Fortune', 'Sivir', 'Silver', 'ADC');
