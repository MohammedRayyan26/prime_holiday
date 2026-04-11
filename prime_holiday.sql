-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 11, 2026 at 08:15 PM
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
-- Database: `prime_holiday`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action_type` varchar(100) NOT NULL,
  `entity_type` varchar(100) NOT NULL,
  `entity_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_reference` varchar(50) NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `package_id` bigint(20) UNSIGNED NOT NULL,
  `travel_date` date NOT NULL,
  `number_of_passengers` int(11) NOT NULL DEFAULT 1,
  `customer_name` varchar(150) NOT NULL,
  `customer_email` varchar(150) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `pickup_point` varchar(255) DEFAULT NULL,
  `special_request` text DEFAULT NULL,
  `package_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `booking_status` enum('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',
  `payment_status` enum('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `booked_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `booking_reference`, `user_id`, `package_id`, `travel_date`, `number_of_passengers`, `customer_name`, `customer_email`, `customer_phone`, `pickup_point`, `special_request`, `package_price`, `total_amount`, `booking_status`, `payment_status`, `booked_at`, `created_at`, `updated_at`) VALUES
(24, 'PH20260405181614454', 2, 8, '2026-04-03', 1, 'Prime Holiday Admin', 'offrayyan@gmail.com', '9999999999', NULL, NULL, 2.99, 2.99, 'pending', 'pending', '2026-04-05 21:46:14', '2026-04-05 21:46:14', '2026-04-05 21:46:14'),
(25, 'PH20260410061310639', 8, 11, '2026-04-21', 1, 'Zayd Mushtaq', 'zaydmush04@gmail.com', '9880995400', NULL, 'hGSCjhgfAJKD', 5.99, 5.99, 'confirmed', 'paid', '2026-04-10 09:43:11', '2026-04-10 09:43:11', '2026-04-10 09:43:36'),
(26, 'PH20260410092819288', 2, 9, '2026-04-23', 1, 'Prime Holiday Admin', 'offrayyan@gmail.com', '9999999999', NULL, NULL, 3.99, 3.99, 'pending', 'pending', '2026-04-10 12:58:20', '2026-04-10 12:58:20', '2026-04-10 12:58:20'),
(27, 'PH20260411095059671', 2, 11, '2026-04-16', 1, 'Prime Holiday Admin', 'offrayyan@gmail.com', '9999999999', NULL, NULL, 5.99, 5.99, 'pending', 'pending', '2026-04-11 13:21:00', '2026-04-11 13:21:00', '2026-04-11 13:21:00'),
(28, 'PH20260411095225210', 2, 11, '2026-04-23', 1, 'Prime Holiday Admin', 'offrayyan@gmail.com', '9999999999', NULL, NULL, 5.99, 5.99, 'pending', 'pending', '2026-04-11 13:22:26', '2026-04-11 13:22:26', '2026-04-11 13:22:26'),
(29, 'PH20260411095436386', 2, 11, '2026-04-17', 1, 'Prime Holiday Admin', 'offrayyan@gmail.com', '9999999999', 'cvxcvx', NULL, 5.99, 5.99, 'pending', 'pending', '2026-04-11 13:24:37', '2026-04-11 13:24:37', '2026-04-11 13:24:37');

-- --------------------------------------------------------

--
-- Table structure for table `booking_passengers`
--

CREATE TABLE `booking_passengers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `passenger_name` varchar(150) NOT NULL,
  `passenger_age` int(11) DEFAULT NULL,
  `passenger_gender` enum('male','female','other') DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text NOT NULL,
  `is_replied` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `full_name`, `email`, `phone`, `subject`, `message`, `is_replied`, `created_at`) VALUES
(2, 'Admin', 'Cloudwithrayyan@gmail.com', '4567891235', 'UI', 'WOrk on website UI make it more professional and more easy', 1, '2026-04-02 23:55:08');

-- --------------------------------------------------------

--
-- Table structure for table `destinations`
--

CREATE TABLE `destinations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `slug` varchar(180) NOT NULL,
  `short_description` text DEFAULT NULL,
  `full_description` longtext DEFAULT NULL,
  `state_name` varchar(120) DEFAULT NULL,
  `country_name` varchar(120) NOT NULL DEFAULT 'India',
  `hero_image` varchar(255) DEFAULT NULL,
  `gallery_image_1` varchar(255) DEFAULT NULL,
  `gallery_image_2` varchar(255) DEFAULT NULL,
  `gallery_image_3` varchar(255) DEFAULT NULL,
  `map_embed_url` text DEFAULT NULL,
  `is_trending` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `destinations`
--

INSERT INTO `destinations` (`id`, `name`, `slug`, `short_description`, `full_description`, `state_name`, `country_name`, `hero_image`, `gallery_image_1`, `gallery_image_2`, `gallery_image_3`, `map_embed_url`, `is_trending`, `is_active`, `meta_title`, `meta_description`, `created_at`, `updated_at`) VALUES
(1, 'Goa', 'goa', 'Sunny beaches and vibrant nightlife.', 'Goa is one of the most loved holiday destinations with beaches, churches, cafes, shopping streets, and water sports.', 'Goa', 'India', 'uploads/destinations/destination_20260405_174129_8bea25225c.webp', NULL, NULL, NULL, NULL, 1, 1, NULL, NULL, '2026-03-31 21:07:02', '2026-04-05 21:11:29'),
(2, 'Ooty', 'ooty', 'Cool climate and scenic hills.', 'Ooty is a peaceful hill station known for tea gardens, mountain views, lakes, and family-friendly nature spots.', 'Tamil Nadu', 'India', 'uploads/destinations/ooty.jpg', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2026-03-31 21:07:02', '2026-04-05 21:11:35'),
(3, 'Manali', 'manali', 'Snow, mountains, and adventure.', 'Manali is popular for snow trips, honeymoon packages, river rafting, mountain roads, and scenic valleys.', 'Himachal Pradesh', 'India', 'uploads/destinations/manali.jpg', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2026-03-31 21:07:02', '2026-04-05 21:11:40'),
(42, 'Mysore', 'mysore', 'Palaces, heritage, and culture.', 'Mysore is famous for palaces, gardens, cultural beauty, and nearby tourist attractions.', 'Karnataka', 'India', 'uploads/destinations/destination_20260405_174104_0861139af0.jpg', NULL, NULL, NULL, NULL, 1, 1, NULL, NULL, '2026-04-01 02:55:46', '2026-04-05 21:11:04'),
(43, 'Coorg', 'coorg', 'Coffee hills and misty views.', 'Coorg is a top weekend and holiday destination known for coffee plantations and greenery.', 'Karnataka', 'India', 'uploads/destinations/destination_20260405_173846_704f58008d.jpg', NULL, NULL, NULL, NULL, 1, 1, NULL, NULL, '2026-04-01 02:55:46', '2026-04-05 21:08:46'),
(44, 'Chikmagalur', 'chikmagalur', 'Hill escape near Bangalore.', 'Chikmagalur is loved for hill views, coffee estates, waterfalls, and peaceful stays.', 'Karnataka', 'India', NULL, NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2026-04-01 02:55:46', '2026-04-05 21:11:46'),
(46, 'Munnar', 'munnar', 'Tea gardens and mountain air.', 'Munnar offers tea plantations, cool weather, scenic drives, and honeymoon-friendly stays.', 'Kerala', 'India', NULL, NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2026-04-01 02:55:46', '2026-04-05 21:13:46'),
(47, 'Kodaikanal', 'kodaikanal', 'Lake views and cool weather.', 'Kodaikanal is famous for lakes, viewpoints, cycling, boating, and family trips.', 'Tamil Nadu', 'India', NULL, NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2026-04-01 02:55:46', '2026-04-05 21:13:33'),
(48, 'Pondicherry', 'pondicherry', 'French streets and beach vibes.', 'Pondicherry combines beaches, cafes, French architecture, and spiritual tourism.', 'Puducherry', 'India', NULL, NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2026-04-01 02:55:46', '2026-04-05 21:13:25'),
(50, 'Gokarna', 'gokarna', 'Quiet beaches and temple town.', 'Gokarna is famous for peaceful beaches, trekking trails, and spiritual travel.', 'Karnataka', 'India', 'uploads/destinations/destination_20260405_174042_a228476cd1.jpg', NULL, NULL, NULL, NULL, 1, 1, NULL, NULL, '2026-04-01 02:55:46', '2026-04-05 21:10:42'),
(51, 'Sakleshpur', 'sakleshpur', 'Green hill drives near Bangalore.', 'Sakleshpur is known for greenery, trekking, homestays, and monsoon beauty.', 'Karnataka', 'India', NULL, NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2026-04-01 02:55:46', '2026-04-05 21:13:17'),
(52, 'Yercaud', 'yercaud', 'Compact hill station getaway.', 'Yercaud is a calm hill station perfect for weekend trips from Bangalore and Chennai.', 'Tamil Nadu', 'India', NULL, NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2026-04-01 02:55:46', '2026-04-05 21:13:13'),
(53, 'BR Hills', 'br-hills', 'Forest and hill experience.', 'BR Hills is a nice forest and hill destination for nature lovers.', 'Karnataka', 'India', NULL, NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2026-04-01 02:55:46', '2026-04-05 21:13:08'),
(54, 'Kabini', 'kabini', 'Wildlife and river stays.', 'Kabini is ideal for wildlife safaris and riverside resort stays.', 'Karnataka', 'India', NULL, NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2026-04-01 02:55:46', '2026-04-05 21:12:59'),
(55, 'Nandi Hills', 'nandi-hills', 'Sunrise point near Bangalore.', 'Nandi Hills is one of the most popular quick getaways near Bangalore.', 'Karnataka', 'India', NULL, NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2026-04-01 02:55:46', '2026-04-05 21:13:04'),
(56, 'Lepakshi', 'lepakshi', 'Temple art and short trip.', 'Lepakshi is known for historic temple architecture and is close to Bangalore.', 'Andhra Pradesh', 'India', NULL, NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2026-04-01 02:55:46', '2026-04-05 21:12:55'),
(57, 'Yelagiri', 'yelagiri', 'Small scenic hill retreat.', 'Yelagiri is a simple hill station for a short peaceful retreat.', 'Tamil Nadu', 'India', NULL, NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2026-04-01 02:55:46', '2026-04-05 21:12:49'),
(58, 'Horsley Hills', 'horsley-hills', 'Cool getaway in Andhra.', 'Horsley Hills is a scenic and cool hill destination for nearby travel.', 'Andhra Pradesh', 'India', NULL, NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2026-04-01 02:55:46', '2026-04-05 21:12:45'),
(59, 'Gandikota', 'gandikota', 'The Grand Canyon of India.', 'Gandikota is famous for dramatic canyon views and fort landscapes.', 'Andhra Pradesh', 'India', NULL, NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2026-04-01 02:55:46', '2026-04-05 21:12:40'),
(60, 'Jaipur', 'jaipur', 'Royal palaces and forts.', 'Jaipur is one of the best tourist cities for heritage, shopping, and architecture.', 'Rajasthan', 'India', NULL, NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2026-04-01 02:55:46', '2026-04-05 21:12:37'),
(61, 'Udaipur', 'udaipur', 'Lakes and royal beauty.', 'Udaipur is famous for lakes, palaces, romance, and heritage tourism.', 'Rajasthan', 'India', NULL, NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2026-04-01 02:55:46', '2026-04-05 21:12:31'),
(62, 'Jaisalmer', 'jaisalmer', 'Desert forts and safaris.', 'Jaisalmer is known for desert camps, golden forts, and camel rides.', 'Rajasthan', 'India', NULL, NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2026-04-01 02:55:46', '2026-04-05 21:12:26'),
(63, 'Rishikesh', 'rishikesh', 'River adventure and spirituality.', 'Rishikesh combines rafting, yoga, temples, and mountain surroundings.', 'Uttarakhand', 'India', 'uploads/destinations/destination_20260408_072453_2abb3fd752.jpg', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2026-04-01 02:55:46', '2026-04-08 10:54:53'),
(64, 'Haridwar', 'haridwar', 'Holy city and ghats.', 'Haridwar is a major spiritual destination on the banks of the Ganga.', 'Uttarakhand', 'India', 'uploads/destinations/destination_20260408_072441_713998d76c.webp', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2026-04-01 02:55:46', '2026-04-08 10:54:41'),
(65, 'Nainitall', 'nainital', 'Lakes and hills.', 'Nainital is a favorite hill destination known for lake views and cool weather.', 'Uttarakhand', 'India', 'uploads/destinations/destination_20260408_072414_23603fbdd2.jpg', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2026-04-01 02:55:46', '2026-04-11 13:48:15'),
(66, 'Shimla', 'shimla', 'Colonial hill beauty.', 'Shimla is known for snow, hills, old architecture, and family holidays.', 'Himachal Pradesh', 'India', 'uploads/destinations/destination_20260408_072358_99f94fe89f.jpg', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2026-04-01 02:55:46', '2026-04-08 10:53:58'),
(67, 'Dharamshala', 'dharamshala', 'Monasteries and mountain air.', 'Dharamshala is famous for Tibetan culture and beautiful mountain scenery.', 'Himachal Pradesh', 'India', 'uploads/destinations/destination_20260408_072342_ddbb7d9173.webp', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2026-04-01 02:55:46', '2026-04-08 10:53:42'),
(68, 'Kasol', 'kasol', 'Valley views and backpacking.', 'Kasol is a popular scenic destination for young travelers and mountain lovers.', 'Himachal Pradesh', 'India', 'uploads/destinations/destination_20260410_060318_e724e39885.jpg', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2026-04-01 02:55:46', '2026-04-10 09:33:18'),
(69, 'Srinagar', 'srinagar', 'Lakes and Kashmir beauty.', 'Srinagar is known for Dal Lake, houseboats, gardens, and snow-region charm.', 'Jammu and Kashmir', 'India', 'uploads/destinations/destination_20260408_071918_67502f8159.webp', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2026-04-01 02:55:46', '2026-04-08 10:49:18'),
(70, 'Gulmarg', 'gulmarg', 'Snow and ski destination.', 'Gulmarg is a top tourist place for snow, skiing, and scenic mountain beauty.', 'Jammu and Kashmir', 'India', 'uploads/destinations/destination_20260405_173803_8e422995fa.webp', NULL, NULL, NULL, NULL, 1, 1, NULL, NULL, '2026-04-01 02:55:46', '2026-04-05 21:08:03'),
(71, 'Pahalgam', 'pahalgam', 'Valleys and river landscapes.', 'Pahalgam is a beautiful destination for family travel and scenic nature stays.', 'Jammu and Kashmir', 'India', 'uploads/destinations/destination_20260406_072831_25f227fd84.jpg', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2026-04-01 02:55:46', '2026-04-06 10:58:31'),
(72, 'Darjeeling', 'darjeeling', 'Tea gardens and toy train.', 'Darjeeling is famous for tea estates, Himalayan views, and heritage charm.', 'West Bengal', 'India', 'uploads/destinations/destination_20260406_072705_7786d18d40.jpg', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2026-04-01 02:55:46', '2026-04-06 10:57:05'),
(73, 'Gangtok', 'gangtok', 'Mountain capital of Sikkim.', 'Gangtok is known for monasteries, views, clean streets, and mountain travel.', 'Sikkim', 'India', 'uploads/destinations/destination_20260406_072614_0d623cf6ac.webp', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2026-04-01 02:55:46', '2026-04-06 10:56:14'),
(74, 'Shillong', 'shillong', 'Cloud city and waterfalls.', 'Shillong is a scenic northeast destination with cool climate and waterfalls.', 'Meghalaya', 'India', 'uploads/destinations/destination_20260406_072538_57cea5a94b.jpg', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2026-04-01 02:55:46', '2026-04-06 10:55:38'),
(75, 'Andaman', 'andaman', 'Island beaches and blue waters.', 'Andaman is famous for island travel, beaches, diving, and honeymoon packages.', 'Andaman Nicobar', 'India', 'uploads/destinations/destination_20260405_173957_f2b2113f71.jpg', NULL, NULL, NULL, NULL, 1, 1, NULL, NULL, '2026-04-01 02:55:46', '2026-04-05 21:20:57'),
(76, 'Lakshadweep', 'lakshadweep', 'Clear lagoons and island escape.', 'Lakshadweep offers turquoise waters, beaches, and peaceful island stays.', 'Lakshadweep', 'India', 'uploads/destinations/destination_20260411_101751_76a4307595.webp', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, '2026-04-01 02:55:46', '2026-04-11 13:47:51');

-- --------------------------------------------------------

--
-- Table structure for table `email_otps`
--

CREATE TABLE `email_otps` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `otp_code` varchar(10) NOT NULL,
  `purpose` enum('login','signup','email_verification','forgot_password') NOT NULL DEFAULT 'login',
  `expires_at` datetime NOT NULL,
  `verified_at` datetime DEFAULT NULL,
  `is_used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `email_otps`
--

INSERT INTO `email_otps` (`id`, `user_id`, `email`, `otp_code`, `purpose`, `expires_at`, `verified_at`, `is_used`, `created_at`) VALUES
(1, 5, 'maazmohammad556@gmail.com', '152655', 'signup', '2026-04-01 12:50:06', '2026-04-01 12:41:25', 1, '2026-04-01 12:40:06'),
(2, 5, 'maazmohammad556@gmail.com', '890958', 'login', '2026-04-01 12:52:10', '2026-04-01 12:42:37', 1, '2026-04-01 12:42:10'),
(3, 2, 'offrayyan@gmail.com', '767343', 'login', '2026-04-01 23:39:15', '2026-04-01 23:30:03', 1, '2026-04-01 23:29:15'),
(4, 6, 'syed49789@gmail.com', '172725', 'login', '2026-04-03 12:02:24', '2026-04-03 11:52:58', 1, '2026-04-03 11:52:24'),
(5, 2, 'offrayyan@gmail.com', '599950', 'login', '2026-04-03 14:48:36', '2026-04-03 14:38:54', 1, '2026-04-03 14:38:36'),
(6, 6, 'syed49789@gmail.com', '452019', 'login', '2026-04-03 15:14:51', NULL, 0, '2026-04-03 15:04:51'),
(7, 2, 'offrayyan@gmail.com', '953487', 'login', '2026-04-03 23:55:02', '2026-04-03 23:45:25', 1, '2026-04-03 23:45:02'),
(8, 3, 'maesterintern@gmail.com', '382610', 'login', '2026-04-04 09:33:27', '2026-04-04 09:24:12', 1, '2026-04-04 09:23:27'),
(9, 3, 'maesterintern@gmail.com', '968687', 'login', '2026-04-04 09:51:31', NULL, 1, '2026-04-04 09:41:31'),
(10, 3, 'maesterintern@gmail.com', '542614', 'login', '2026-04-04 09:52:05', '2026-04-04 09:42:52', 1, '2026-04-04 09:42:05'),
(11, 2, 'offrayyan@gmail.com', '523781', 'login', '2026-04-05 13:35:57', '2026-04-05 13:26:28', 1, '2026-04-05 13:25:57'),
(12, 3, 'maesterintern@gmail.com', '640267', 'login', '2026-04-05 22:25:19', '2026-04-05 22:16:37', 1, '2026-04-05 22:15:19'),
(13, 2, 'offrayyan@gmail.com', '661451', 'login', '2026-04-05 22:29:58', '2026-04-05 22:20:26', 1, '2026-04-05 22:19:58'),
(14, 2, 'offrayyan@gmail.com', '411495', 'login', '2026-04-06 10:59:23', '2026-04-06 10:50:26', 1, '2026-04-06 10:49:23'),
(15, 7, 'hassu744@gmail.com', '589362', 'login', '2026-04-06 13:26:50', '2026-04-06 13:17:20', 1, '2026-04-06 13:16:50'),
(16, 2, 'offrayyan@gmail.com', '828661', 'login', '2026-04-06 13:28:53', '2026-04-06 13:20:07', 1, '2026-04-06 13:18:53'),
(17, 2, 'offrayyan@gmail.com', '778380', 'login', '2026-04-08 10:55:30', '2026-04-08 10:46:03', 1, '2026-04-08 10:45:30'),
(18, 8, 'zaydmush04@gmail.com', '750047', 'login', '2026-04-10 09:49:44', '2026-04-10 09:40:08', 1, '2026-04-10 09:39:44'),
(19, 2, 'offrayyan@gmail.com', '483927', 'login', '2026-04-10 09:55:28', '2026-04-10 09:45:57', 1, '2026-04-10 09:45:28'),
(24, 3, 'maesterintern@gmail.com', '791117', 'login', '2026-04-10 12:57:42', NULL, 1, '2026-04-10 12:47:42'),
(25, 3, 'maesterintern@gmail.com', '308059', 'login', '2026-04-10 12:58:04', '2026-04-10 12:48:35', 1, '2026-04-10 12:48:04'),
(26, 8, 'zaydmush04@gmail.com', '696915', 'login', '2026-04-10 13:00:39', '2026-04-10 12:51:14', 1, '2026-04-10 12:50:39'),
(27, 2, 'offrayyan@gmail.com', '554519', 'login', '2026-04-10 13:04:18', '2026-04-10 12:55:06', 1, '2026-04-10 12:54:18'),
(28, 3, 'maesterintern@gmail.com', '888003', 'forgot_password', '2026-04-10 14:44:40', NULL, 0, '2026-04-10 14:34:40'),
(29, 2, 'offrayyan@gmail.com', '587537', 'login', '2026-04-11 12:55:42', '2026-04-11 12:46:34', 1, '2026-04-11 12:45:42');

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE `packages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `destination_id` bigint(20) UNSIGNED NOT NULL,
  `package_name` varchar(200) NOT NULL,
  `slug` varchar(220) NOT NULL,
  `short_description` text DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `recommendations` longtext DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `offer_price` decimal(10,2) DEFAULT NULL,
  `duration_days` int(11) NOT NULL DEFAULT 1,
  `duration_nights` int(11) NOT NULL DEFAULT 0,
  `bus_number` varchar(100) DEFAULT NULL,
  `driver_name` varchar(150) DEFAULT NULL,
  `departure_from` varchar(150) DEFAULT NULL,
  `seats_available` int(11) NOT NULL DEFAULT 0,
  `featured_image` varchar(255) DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `packages`
--

INSERT INTO `packages` (`id`, `destination_id`, `package_name`, `slug`, `short_description`, `description`, `recommendations`, `price`, `offer_price`, `duration_days`, `duration_nights`, `bus_number`, `driver_name`, `departure_from`, `seats_available`, `featured_image`, `cover_image`, `is_featured`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(6, 43, 'Coorg Weekend Escape', 'coorg-weekend-escape', 'A refreshing hill getaway through coffee estates, waterfalls, and scenic viewpoints.', 'Enjoy a relaxing Coorg trip with beautiful plantation roads, famous viewpoints, local sightseeing, and a peaceful hill-stay experience. This package is ideal for travelers looking for greenery, fresh weather, and a comfortable short holiday from Bangalore.', 'Best for couples, families, friends, and weekend travelers.', 2.00, 0.99, 3, 2, 'KA05TR4589', 'Manjunath', 'Bangalore', 24, 'uploads/packages/1775391870_8622_coorg.jpg', 'uploads/packages/1775391870_3424_coorg.jpg', 1, 1, 2, '2026-04-05 17:54:30', '2026-04-05 18:07:34'),
(7, 50, 'Gokarna Beach Retreat', 'gokarna-beach-retreat', 'A peaceful beach holiday with coastal views, temple town charm, and relaxing stays.', 'Take a refreshing trip to Gokarna and enjoy calm beaches, beachside views, temple visits, and laid-back coastal travel. This package is perfect for travelers who want a less crowded beach destination with a mix of spirituality and leisure.', 'Best for friends, couples, solo travelers, and beach lovers.', 3.00, 1.99, 3, 2, 'KA09TR9087', 'Rafiq Ahmed', 'Bangalore', 28, 'uploads/packages/1775393378_3818_dfgh.jpg', 'uploads/packages/1775393378_3402_dgfh.webp', 1, 1, 2, '2026-04-05 18:12:29', '2026-04-05 20:27:44'),
(8, 42, 'Mysore Heritage Delight', 'mysore-heritage-delight', 'royal short trip covering Mysore Palace, gardens, and cultural attractions.', 'Experience the cultural beauty of Mysore with palace visits, heritage landmarks, gardens, and a relaxed city tour. This package is ideal for families and travelers wanting a short heritage escape.', 'Best for families, students, senior travelers, and short-trip groups.', 4.00, 2.99, 2, 1, 'KA11TR2001', 'Shivakumar', 'Bangalore', 30, 'uploads/packages/1775393599_2493_XZCXZ.jpg', 'uploads/packages/1775393599_8296_dsfg.webp', 1, 1, 2, '2026-04-05 18:22:36', '2026-04-05 18:27:13'),
(9, 70, 'Gulmarg Snow Experience', 'gulmarg-snow-experience', 'A scenic Kashmir snow package with mountain views, gondola access, and winter charm.', 'Discover the snowy beauty of Gulmarg with breathtaking landscapes, cable car experiences, meadows, and a refreshing mountain holiday. This package is perfect for travelers looking for snow and premium nature experiences.', 'Best for honeymooners, families, photographers, and snow lovers.', 4.99, 3.99, 5, 4, NULL, 'Syed', 'Srinagar', 0, 'uploads/packages/1775401461_2797_snow.webp', 'uploads/packages/1775401461_1550_dgfh.jpg', 1, 1, 2, '2026-04-05 20:34:21', '2026-04-05 20:46:30'),
(10, 75, 'Andaman Island Delight', 'andaman-island-delight', 'An island holiday package with beaches, ferry rides, and crystal-clear coastal beauty.', 'Enjoy a memorable Andaman vacation with island sightseeing, beaches, water activity options, and comfortable stays. This package is designed for travelers who want tropical beauty, beach relaxation, and a complete island travel experience.', 'Best for couples, honeymooners, families, and premium travelers.', 5.99, 4.99, 6, 5, NULL, 'Syed Uff', 'Port Blair', 0, 'uploads/packages/1775402396_1878_island.webp', 'uploads/packages/1775402396_2342_asdds.webp', 1, 1, 2, '2026-04-05 20:49:56', '2026-04-11 13:28:02'),
(11, 1, 'Goa Beach Holiday', 'goa-beach-holiday', 'Enjoy sunny beaches, sightseeing, shopping, and a fun-filled coastal getaway in Goa.', 'Goa Beach Holiday is a perfect travel package for beach lovers, couples, friends, and families. The trip covers popular beaches, local sightseeing, shopping streets, and a relaxing stay experience. It offers a balanced mix of leisure, scenic beauty, and fun coastal vibes for a memorable holiday.', 'Best for couples, friends, college groups, and family travelers.', 6.99, 5.99, 4, 3, 'KA07TR4589', 'Naveen Kumar', 'Bangalore', 17, 'uploads/packages/1775403026_5125_Goa.jpg', 'uploads/packages/1775403026_3667_fghgfhgfh.jpg', 1, 1, 2, '2026-04-05 21:00:26', '2026-04-11 13:49:37');

-- --------------------------------------------------------

--
-- Table structure for table `package_excludes`
--

CREATE TABLE `package_excludes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `package_id` bigint(20) UNSIGNED NOT NULL,
  `item_text` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `package_excludes`
--

INSERT INTO `package_excludes` (`id`, `package_id`, `item_text`, `sort_order`, `created_at`) VALUES
(5, 6, 'lunch and dinner', 1, '2026-04-05 17:55:11'),
(6, 6, 'entry tickets', 2, '2026-04-05 18:03:40'),
(7, 6, 'personal expenses', 3, '2026-04-05 18:03:53'),
(8, 6, 'adventure activities', 4, '2026-04-05 18:04:00'),
(9, 7, 'water sports', 1, '2026-04-05 18:16:27'),
(10, 7, 'personal shopping', 2, '2026-04-05 18:16:49'),
(11, 7, 'meals other than breakfast', 3, '2026-04-05 18:16:59'),
(12, 7, 'beach activity charges', 4, '2026-04-05 18:17:10'),
(13, 8, 'monument entry tickets', 1, '2026-04-05 18:25:50'),
(14, 8, 'lunch and dinner', 2, '2026-04-05 18:26:06'),
(15, 8, 'shopping expenses', 3, '2026-04-05 18:26:11'),
(16, 9, 'flight tickets', 1, '2026-04-05 20:35:28'),
(17, 9, 'gondola tickets', 2, '2026-04-05 20:43:25'),
(18, 9, 'pony rides', 3, '2026-04-05 20:43:33'),
(19, 9, 'personal expenses', 4, '2026-04-05 20:43:41'),
(20, 10, 'flight tickets', 1, '2026-04-05 20:51:13'),
(21, 10, 'scuba/snorkeling charges', 2, '2026-04-05 20:51:26'),
(22, 10, 'lunch and dinner', 3, '2026-04-05 20:51:36'),
(23, 10, 'personal shopping', 4, '2026-04-05 20:51:43'),
(24, 11, 'personal expenses', 1, '2026-04-05 21:02:00'),
(25, 11, 'lunch and dinner', 2, '2026-04-05 21:02:20'),
(26, 11, 'water sports charges', 3, '2026-04-05 21:02:34'),
(27, 11, 'entry tickets not mentioned', 4, '2026-04-05 21:02:41');

-- --------------------------------------------------------

--
-- Table structure for table `package_images`
--

CREATE TABLE `package_images` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `package_id` bigint(20) UNSIGNED NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `package_images`
--

INSERT INTO `package_images` (`id`, `package_id`, `image_path`, `alt_text`, `sort_order`, `created_at`) VALUES
(10, 6, 'uploads/packages/1775391870_2534_bzxzcvb.jpg', 'Coorg Weekend Escape image', 1, '2026-04-05 17:54:30'),
(11, 6, 'uploads/packages/1775392654_8326_asdfsda.webp', 'Coorg Weekend Escape image', 2, '2026-04-05 18:07:34'),
(12, 6, 'uploads/packages/1775392654_5498_asdasd.webp', 'Coorg Weekend Escape image', 3, '2026-04-05 18:07:34'),
(13, 6, 'uploads/packages/1775392654_7044_asdas.jpg', 'Coorg Weekend Escape image', 4, '2026-04-05 18:07:34'),
(14, 6, 'uploads/packages/1775392654_4219_AXSD.jpg', 'Coorg Weekend Escape image', 5, '2026-04-05 18:07:34'),
(15, 6, 'uploads/packages/1775392654_5646_bzxzcvb.jpg', 'Coorg Weekend Escape image', 6, '2026-04-05 18:07:34'),
(16, 6, 'uploads/packages/1775392654_8928_coorg.jpg', 'Coorg Weekend Escape image', 7, '2026-04-05 18:07:34'),
(24, 7, 'uploads/packages/1775393378_1170_fhj.jpg', 'Gokarna Beach Retreat image', 1, '2026-04-05 18:19:38'),
(25, 7, 'uploads/packages/1775393378_4042_asdf.jpg', 'Gokarna Beach Retreat image', 2, '2026-04-05 18:19:38'),
(26, 7, 'uploads/packages/1775393378_3573_dgfh.webp', 'Gokarna Beach Retreat image', 3, '2026-04-05 18:19:38'),
(27, 7, 'uploads/packages/1775393378_3341_dfgh.jpg', 'Gokarna Beach Retreat image', 4, '2026-04-05 18:19:38'),
(28, 8, 'uploads/packages/1775393556_1357_asdfasd.webp', 'Mysore Heritage Delight image', 1, '2026-04-05 18:22:36'),
(29, 8, 'uploads/packages/1775393556_9407_dasfasdf.webp', 'Mysore Heritage Delight image', 2, '2026-04-05 18:22:36'),
(30, 8, 'uploads/packages/1775393556_2138_dadsfdas.jpg', 'Mysore Heritage Delight image', 3, '2026-04-05 18:22:36'),
(31, 8, 'uploads/packages/1775393556_5108_dsfg.webp', 'Mysore Heritage Delight image', 4, '2026-04-05 18:22:36'),
(32, 8, 'uploads/packages/1775393556_8606_XZCXZ.jpg', 'Mysore Heritage Delight image', 5, '2026-04-05 18:22:36'),
(35, 9, 'uploads/packages/1775401461_1736_nbv.webp', 'Gulmarg Snow Experience image', 1, '2026-04-05 20:34:21'),
(36, 9, 'uploads/packages/1775401461_6118_cbvn.webp', 'Gulmarg Snow Experience image', 2, '2026-04-05 20:34:21'),
(37, 9, 'uploads/packages/1775401461_5557_dgfh.jpg', 'Gulmarg Snow Experience image', 3, '2026-04-05 20:34:21'),
(38, 9, 'uploads/packages/1775401461_8642_snow.webp', 'Gulmarg Snow Experience image', 4, '2026-04-05 20:34:21'),
(39, 10, 'uploads/packages/1775402396_9317_jhjh.webp', 'Andaman Island Delight image', 1, '2026-04-05 20:49:56'),
(40, 10, 'uploads/packages/1775402396_7825_dsa.webp', 'Andaman Island Delight image', 2, '2026-04-05 20:49:56'),
(41, 10, 'uploads/packages/1775402396_3529_asdds.webp', 'Andaman Island Delight image', 3, '2026-04-05 20:49:56'),
(42, 10, 'uploads/packages/1775402396_9700_island.webp', 'Andaman Island Delight image', 4, '2026-04-05 20:49:56'),
(43, 11, 'uploads/packages/1775403026_9813_fdsfret.jpg', 'Goa Beach Holiday image', 1, '2026-04-05 21:00:26'),
(44, 11, 'uploads/packages/1775403026_4653_fghgfhgfh.jpg', 'Goa Beach Holiday image', 2, '2026-04-05 21:00:26'),
(45, 11, 'uploads/packages/1775403026_4783_sddasdas.jpg', 'Goa Beach Holiday image', 3, '2026-04-05 21:00:26'),
(46, 11, 'uploads/packages/1775403026_9543_dsaasd.webp', 'Goa Beach Holiday image', 4, '2026-04-05 21:00:26'),
(47, 11, 'uploads/packages/1775403026_1427_kjhgfd.jpg', 'Goa Beach Holiday image', 5, '2026-04-05 21:00:26'),
(48, 11, 'uploads/packages/1775403026_2754_asdg.webp', 'Goa Beach Holiday image', 6, '2026-04-05 21:00:26'),
(49, 11, 'uploads/packages/1775403026_8713_dsasf.jpg', 'Goa Beach Holiday image', 7, '2026-04-05 21:00:26'),
(50, 11, 'uploads/packages/1775403026_8360_Goa.jpg', 'Goa Beach Holiday image', 8, '2026-04-05 21:00:26');

-- --------------------------------------------------------

--
-- Table structure for table `package_includes`
--

CREATE TABLE `package_includes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `package_id` bigint(20) UNSIGNED NOT NULL,
  `item_text` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `package_includes`
--

INSERT INTO `package_includes` (`id`, `package_id`, `item_text`, `sort_order`, `created_at`) VALUES
(6, 6, 'transportation from Bangalore', 1, '2026-04-05 17:54:55'),
(7, 6, '2 nights stay', 2, '2026-04-05 17:59:02'),
(8, 6, 'sightseeing', 3, '2026-04-05 18:02:17'),
(9, 6, 'breakfast', 4, '2026-04-05 18:02:35'),
(10, 6, 'driver allowance', 5, '2026-04-05 18:02:47'),
(13, 7, 'sleeper/semi-sleeper transport', 1, '2026-04-05 18:15:32'),
(14, 7, 'hotel stay', 1, '2026-04-05 18:15:43'),
(15, 7, 'local sightseeing', 3, '2026-04-05 18:16:01'),
(16, 7, 'breakfast', 4, '2026-04-05 18:16:11'),
(17, 8, 'transportation', 1, '2026-04-05 18:24:46'),
(18, 8, 'one night accommodation', 2, '2026-04-05 18:25:01'),
(19, 8, 'sightseeing', 3, '2026-04-05 18:25:09'),
(20, 8, 'breakfast', 4, '2026-04-05 18:25:31'),
(21, 9, 'hotel stay', 1, '2026-04-05 20:34:36'),
(22, 9, 'local transfers', 2, '2026-04-05 20:34:45'),
(23, 9, 'sightseeing', 3, '2026-04-05 20:34:58'),
(24, 9, 'breakfast and dinner', 4, '2026-04-05 20:35:03'),
(25, 10, 'accommodation', 1, '2026-04-05 20:50:21'),
(26, 10, 'airport/ferry transfers', 2, '2026-04-05 20:50:32'),
(27, 10, 'sightseeing', 3, '2026-04-05 20:50:43'),
(28, 10, 'breakfast', 4, '2026-04-05 20:50:51'),
(29, 11, 'hotel accommodation', 1, '2026-04-05 21:00:41'),
(30, 11, 'transportation', 2, '2026-04-05 21:00:54'),
(31, 11, 'Goa sightseeing', 3, '2026-04-05 21:01:02'),
(32, 11, 'breakfast', 4, '2026-04-05 21:01:17'),
(33, 11, 'pickup and drop support', 5, '2026-04-05 21:01:23');

-- --------------------------------------------------------

--
-- Table structure for table `package_itinerary_days`
--

CREATE TABLE `package_itinerary_days` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `package_id` bigint(20) UNSIGNED NOT NULL,
  `day_number` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` longtext DEFAULT NULL,
  `overnight_stay` varchar(150) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `package_itinerary_days`
--

INSERT INTO `package_itinerary_days` (`id`, `package_id`, `day_number`, `title`, `description`, `overnight_stay`, `created_at`, `updated_at`) VALUES
(6, 6, 1, 'Departure from Bangalore', 'Departure from Bangalore, arrival, check-in, evening leisure, overnight in Coorg', 'overnight in Coorg', '2026-04-05 17:56:25', '2026-04-05 17:56:25'),
(7, 6, 2, 'Abbey Falls', 'Abbey Falls, Raja’s Seat, Madikeri Fort, shopping, overnight in Coorg', 'overnight in Coorg', '2026-04-05 18:05:18', '2026-04-05 18:05:18'),
(8, 6, 3, 'Dubare/plantation visit', 'Dubare/plantation visit, checkout, return to Bangalore', 'overnight in Coorg', '2026-04-05 18:05:43', '2026-04-05 18:05:43'),
(9, 7, 1, 'Travel', 'Overnight departure from Bangalore', NULL, '2026-04-05 18:17:43', '2026-04-05 18:17:43'),
(10, 7, 2, 'Visits', 'Arrival, check-in, Om Beach and Kudle Beach visit, overnight in Gokarna', 'overnight in Gokarna', '2026-04-05 18:18:11', '2026-04-05 18:18:11'),
(11, 7, 3, 'Temple visit,', 'Temple visit, Half Moon/nearby beach points, return journey', 'return journey', '2026-04-05 18:18:34', '2026-04-05 18:18:34'),
(12, 8, 1, 'Mysore Palace,', 'Departure, Mysore Palace, St. Philomena’s Church, Brindavan Gardens', 'Mysore', '2026-04-05 18:26:41', '2026-04-05 18:26:41'),
(13, 8, 2, 'Chamundi Hills', 'Chamundi Hills, zoo or local shopping, return', 'return journey', '2026-04-05 18:27:08', '2026-04-05 18:27:08'),
(14, 9, 1, 'Arrival', 'Arrival in Srinagar, transfer, leisure', 'Srinagar', '2026-04-05 20:44:18', '2026-04-05 20:44:18'),
(15, 9, 2, 'Srinagar to Gulmarg,', 'Srinagar to Gulmarg, local sightseeing', NULL, '2026-04-05 20:44:39', '2026-04-05 20:44:39'),
(16, 9, 3, 'snow activities', 'Gondola and snow activities', 'Gondola', '2026-04-05 20:45:24', '2026-04-05 20:45:24'),
(17, 9, 4, 'sightseeing', 'Nearby sightseeing / leisure', 'Gondola', '2026-04-05 20:45:48', '2026-04-05 20:45:48'),
(18, 9, 5, 'departure', 'Checkout and departure', NULL, '2026-04-05 20:46:07', '2026-04-05 20:46:07'),
(19, 10, 1, 'Arrival Port Blair,', 'Arrival in Port Blair, local sightseeing', 'Port Blair,', '2026-04-05 20:52:14', '2026-04-05 20:52:14'),
(20, 10, 2, 'beach visit', 'Ferry to Havelock, beach visit', 'Andaman', '2026-04-05 20:52:51', '2026-04-05 20:52:51'),
(21, 10, 3, 'leisure', 'Radhanagar Beach and leisure', 'Andaman', '2026-04-05 20:53:11', '2026-04-05 20:53:11'),
(22, 10, 4, 'Water activity', 'Water activity options / transfer', 'Andaman', '2026-04-05 20:53:31', '2026-04-05 20:53:31'),
(23, 10, 5, 'sightseeing', 'Local island sightseeing', 'Andaman', '2026-04-05 20:53:48', '2026-04-05 20:53:48'),
(24, 10, 6, 'Return', 'Return and departure', NULL, '2026-04-05 20:54:10', '2026-04-05 20:54:10'),
(25, 11, 1, 'arrival in Goa', 'Departure, arrival in Goa, hotel check-in, evening leisure', 'Goa', '2026-04-05 21:03:23', '2026-04-05 21:03:23'),
(26, 11, 2, 'beaches', 'North Goa sightseeing and beaches', 'Goa', '2026-04-05 21:03:46', '2026-04-05 21:03:46'),
(27, 11, 3, 'shopping', 'South Goa sightseeing, shopping, and relaxation', 'Goa', '2026-04-05 21:04:04', '2026-04-05 21:04:04'),
(28, 11, 4, 'Checkout', 'Checkout and return journey', 'return journey', '2026-04-05 21:04:27', '2026-04-05 21:04:27');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `payment_gateway` enum('razorpay','manual') NOT NULL DEFAULT 'razorpay',
  `razorpay_order_id` varchar(120) DEFAULT NULL,
  `razorpay_payment_id` varchar(120) DEFAULT NULL,
  `razorpay_signature` varchar(255) DEFAULT NULL,
  `transaction_reference` varchar(120) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'INR',
  `payment_status` enum('created','success','failed','refunded') NOT NULL DEFAULT 'created',
  `paid_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `booking_id`, `user_id`, `payment_gateway`, `razorpay_order_id`, `razorpay_payment_id`, `razorpay_signature`, `transaction_reference`, `amount`, `currency`, `payment_status`, `paid_at`, `created_at`, `updated_at`) VALUES
(23, 24, 2, 'razorpay', 'order_SZsFWW1zNBtQGO', NULL, NULL, NULL, 2.99, 'INR', 'created', NULL, '2026-04-05 21:46:14', '2026-04-05 21:46:14'),
(24, 25, 8, 'razorpay', 'order_SbebIklVKXlPWn', 'pay_SbebS7sOh9buvd', 'd091667db9027ae3ed8129040a7a35cffd82c9e88adc36f5ad4b8ea4f3f96335', NULL, 5.99, 'INR', 'success', '2026-04-10 09:43:36', '2026-04-10 09:43:11', '2026-04-10 09:43:36'),
(25, 26, 2, 'razorpay', 'order_SbhvRbMOfTaVWw', NULL, NULL, NULL, 3.99, 'INR', 'created', NULL, '2026-04-10 12:58:20', '2026-04-10 12:58:20'),
(26, 27, 2, 'razorpay', 'order_Sc6qVrwIbQEowp', NULL, NULL, NULL, 5.99, 'INR', 'created', NULL, '2026-04-11 13:21:00', '2026-04-11 13:21:00'),
(27, 28, 2, 'razorpay', 'order_Sc6s21OPCGw5tF', NULL, NULL, NULL, 5.99, 'INR', 'created', NULL, '2026-04-11 13:22:26', '2026-04-11 13:22:26'),
(28, 29, 2, 'razorpay', 'order_Sc6uKpLFqFKBsL', NULL, NULL, NULL, 5.99, 'INR', 'created', NULL, '2026-04-11 13:24:37', '2026-04-11 13:24:37');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `package_id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rating` tinyint(4) NOT NULL,
  `review_text` text DEFAULT NULL,
  `is_approved` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `package_id`, `booking_id`, `rating`, `review_text`, `is_approved`, `created_at`, `updated_at`) VALUES
(13, 8, 11, 25, 4, 'Zayd', 1, '2026-04-10 09:44:04', '2026-04-10 09:44:04');

-- --------------------------------------------------------

--
-- Table structure for table `testimonials`
--

CREATE TABLE `testimonials` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `customer_name` varchar(150) NOT NULL,
  `customer_role` varchar(150) DEFAULT NULL,
  `rating` tinyint(4) NOT NULL DEFAULT 5,
  `testimonial_text` text NOT NULL,
  `customer_image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `testimonials`
--

INSERT INTO `testimonials` (`id`, `customer_name`, `customer_role`, `rating`, `testimonial_text`, `customer_image`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Aarav Sharma', 'Traveler', 5, 'Prime Holiday made our family trip smooth and memorable. Everything was well arranged.', NULL, 1, 1, '2026-03-31 21:07:02', '2026-03-31 21:07:02'),
(2, 'Sana Khan', 'Tourist', 5, 'The package details were clear and the booking process was very easy.', NULL, 1, 2, '2026-03-31 21:07:02', '2026-03-31 21:07:02'),
(3, 'Rohit Verma', 'Customer', 4, 'Good support, clean design, and nice travel planning experience overall.', NULL, 1, 3, '2026-03-31 21:07:02', '2026-03-31 21:07:02');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `email_verified_at` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `gender`, `password_hash`, `role`, `is_active`, `email_verified_at`, `last_login_at`, `created_at`, `updated_at`) VALUES
(2, 'Prime Holiday Admin', 'offrayyan@gmail.com', '9999999999', 'other', '$2y$10$Qy.sswE3rD7VBM2/y..dC.yCS9BkhodsQ1Jxi2zNASfWljqoG6nNS', 'admin', 1, NULL, '2026-04-11 12:46:34', '2026-04-01 01:31:33', '2026-04-11 12:46:34'),
(3, 'mohammed', 'maesterintern@gmail.com', '9876543214', 'male', '$2y$10$mAtVPwE6Syo/ykRdYgFjZuhqJe2htWGv89xLeG1XGN4xfJQIFGvpS', 'user', 1, NULL, '2026-04-10 12:48:35', '2026-04-01 01:53:57', '2026-04-10 12:48:35'),
(4, 'sibghat', 'sibgathshariff72@gmail.com', '8088981348', 'male', '$2y$10$10lPomRxURNeEK.HBBSBuuzGSc4v5rvZQKJK6iZ67nZK4/bTZPtG6', 'user', 1, NULL, '2026-04-01 12:20:56', '2026-04-01 12:20:32', '2026-04-01 12:20:56'),
(5, 'Maaz', 'maazmohammad556@gmail.com', '9964860531', 'male', '$2y$10$rYXZiXz7KWbm7aRkvq/ScuKW9.T3q81q0dfPYdJoSmkdDkP4yUxyq', 'user', 1, '2026-04-01 12:41:25', '2026-04-01 12:42:37', '2026-04-01 12:40:06', '2026-04-11 13:37:21'),
(6, 'Syed Abid', 'syed49789@gmail.com', '9916531201', 'male', '$2y$10$UK5yB48d1DoRXk2uMGnBR.1QJqv2K/Lwai8zPyrUy0u9K3TlBnA36', 'user', 1, NULL, '2026-04-03 11:52:58', '2026-04-03 11:52:01', '2026-04-03 11:52:58'),
(7, 'Hassan', 'hassu744@gmail.com', '7448876658', 'male', '$2y$10$Y4Jmq3gNIAvF1pPoahg2YusK5hwWKG/sTUaKl.lUFO8ZWDTe4AILm', 'user', 1, NULL, '2026-04-06 13:17:20', '2026-04-06 13:15:50', '2026-04-06 13:17:20'),
(8, 'Zayd Mushtaq', 'zaydmush04@gmail.com', '9880995400', 'male', '$2y$10$nZpKwxB4/mQGCkoMuhLMWONXpGtE7DmcVu9Hj5dCiDRb7GFRMG37i', 'user', 1, NULL, '2026-04-10 12:51:14', '2026-04-10 09:38:17', '2026-04-10 12:51:14');

-- --------------------------------------------------------

--
-- Table structure for table `user_profiles`
--

CREATE TABLE `user_profiles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `dob` date DEFAULT NULL,
  `nationality` varchar(100) DEFAULT NULL,
  `marital_status` enum('single','married','other') DEFAULT NULL,
  `address_line1` varchar(255) DEFAULT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_profiles`
--

INSERT INTO `user_profiles` (`id`, `user_id`, `dob`, `nationality`, `marital_status`, `address_line1`, `address_line2`, `city`, `state`, `country`, `postal_code`, `profile_photo`, `created_at`, `updated_at`) VALUES
(1, 3, NULL, 'Indian', NULL, NULL, NULL, 'bangalore', 'Karnataka', 'India', '560032', NULL, '2026-04-01 01:53:57', '2026-04-01 04:58:17'),
(2, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-01 12:20:32', '2026-04-01 12:20:32'),
(3, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-01 12:40:06', '2026-04-01 12:40:06'),
(4, 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-03 11:52:01', '2026-04-03 11:52:01'),
(5, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-06 13:15:50', '2026-04-06 13:15:50'),
(6, 8, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-10 09:38:17', '2026-04-10 09:38:17');

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_package_rating_summary`
-- (See below for the actual view)
--
CREATE TABLE `vw_package_rating_summary` (
`package_id` bigint(20) unsigned
,`package_name` varchar(200)
,`total_reviews` bigint(21)
,`average_rating` decimal(5,1)
);

-- --------------------------------------------------------

--
-- Structure for view `vw_package_rating_summary`
--
DROP TABLE IF EXISTS `vw_package_rating_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_package_rating_summary`  AS SELECT `p`.`id` AS `package_id`, `p`.`package_name` AS `package_name`, count(`r`.`id`) AS `total_reviews`, ifnull(round(avg(`r`.`rating`),1),0) AS `average_rating` FROM (`packages` `p` left join `reviews` `r` on(`r`.`package_id` = `p`.`id` and `r`.`is_approved` = 1)) GROUP BY `p`.`id`, `p`.`package_name` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activity_logs_user` (`user_id`),
  ADD KEY `idx_activity_logs_entity` (`entity_type`,`entity_id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_reference` (`booking_reference`),
  ADD KEY `idx_bookings_user` (`user_id`),
  ADD KEY `idx_bookings_package` (`package_id`),
  ADD KEY `idx_bookings_travel_date` (`travel_date`),
  ADD KEY `idx_bookings_status` (`booking_status`,`payment_status`);

--
-- Indexes for table `booking_passengers`
--
ALTER TABLE `booking_passengers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_booking_passengers_booking` (`booking_id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contact_messages_email` (`email`),
  ADD KEY `idx_contact_messages_replied` (`is_replied`);

--
-- Indexes for table `destinations`
--
ALTER TABLE `destinations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_destinations_name` (`name`),
  ADD KEY `idx_destinations_active` (`is_active`),
  ADD KEY `idx_destinations_trending` (`is_trending`);

--
-- Indexes for table `email_otps`
--
ALTER TABLE `email_otps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_email_otps_user` (`user_id`),
  ADD KEY `idx_email_otps_email` (`email`),
  ADD KEY `idx_email_otps_expires` (`expires_at`);

--
-- Indexes for table `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `fk_packages_created_by` (`created_by`),
  ADD KEY `idx_packages_destination` (`destination_id`),
  ADD KEY `idx_packages_name` (`package_name`),
  ADD KEY `idx_packages_active` (`is_active`),
  ADD KEY `idx_packages_featured` (`is_featured`);

--
-- Indexes for table `package_excludes`
--
ALTER TABLE `package_excludes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_package_excludes_package` (`package_id`);

--
-- Indexes for table `package_images`
--
ALTER TABLE `package_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_package_images_package` (`package_id`);

--
-- Indexes for table `package_includes`
--
ALTER TABLE `package_includes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_package_includes_package` (`package_id`);

--
-- Indexes for table `package_itinerary_days`
--
ALTER TABLE `package_itinerary_days`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_package_day` (`package_id`,`day_number`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_razorpay_payment_id` (`razorpay_payment_id`),
  ADD KEY `idx_payments_booking` (`booking_id`),
  ADD KEY `idx_payments_user` (`user_id`),
  ADD KEY `idx_payments_status` (`payment_status`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_reviews_booking` (`booking_id`),
  ADD KEY `idx_reviews_package` (`package_id`),
  ADD KEY `idx_reviews_user` (`user_id`),
  ADD KEY `idx_reviews_approved` (`is_approved`);

--
-- Indexes for table `testimonials`
--
ALTER TABLE `testimonials`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone` (`phone`);

--
-- Indexes for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_profiles_user` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `booking_passengers`
--
ALTER TABLE `booking_passengers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `destinations`
--
ALTER TABLE `destinations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `email_otps`
--
ALTER TABLE `email_otps`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `package_excludes`
--
ALTER TABLE `package_excludes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `package_images`
--
ALTER TABLE `package_images`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `package_includes`
--
ALTER TABLE `package_includes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `package_itinerary_days`
--
ALTER TABLE `package_itinerary_days`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `testimonials`
--
ALTER TABLE `testimonials`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `user_profiles`
--
ALTER TABLE `user_profiles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `fk_activity_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `fk_bookings_package` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bookings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `booking_passengers`
--
ALTER TABLE `booking_passengers`
  ADD CONSTRAINT `fk_booking_passengers_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `email_otps`
--
ALTER TABLE `email_otps`
  ADD CONSTRAINT `fk_email_otps_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `packages`
--
ALTER TABLE `packages`
  ADD CONSTRAINT `fk_packages_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_packages_destination` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `package_excludes`
--
ALTER TABLE `package_excludes`
  ADD CONSTRAINT `fk_package_excludes_package` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `package_images`
--
ALTER TABLE `package_images`
  ADD CONSTRAINT `fk_package_images_package` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `package_includes`
--
ALTER TABLE `package_includes`
  ADD CONSTRAINT `fk_package_includes_package` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `package_itinerary_days`
--
ALTER TABLE `package_itinerary_days`
  ADD CONSTRAINT `fk_itinerary_package` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_payments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_reviews_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_reviews_package` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD CONSTRAINT `fk_user_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
