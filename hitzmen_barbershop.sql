-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 01, 2026 at 05:37 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hitzmen_barbershop`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `barber_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `services_ids_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`services_ids_json`)),
  `haircut_id` int(11) DEFAULT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('Pending','Confirmed','Completed','Cancelled') DEFAULT 'Pending',
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `user_id`, `barber_id`, `appointment_date`, `appointment_time`, `services_ids_json`, `haircut_id`, `total_price`, `notes`, `status`, `rejection_reason`, `created_at`, `updated_at`) VALUES
(1, 17, 26, '2025-12-24', '11:00:00', '[\"19\"]', NULL, 100.00, '', 'Completed', NULL, '2025-12-23 23:58:09', '2025-12-23 23:59:00'),
(2, 17, 26, '2025-12-24', '12:15:00', '[\"15\"]', NULL, 20.00, '', 'Cancelled', 'Kedai tutup', '2025-12-24 00:12:04', '2025-12-24 00:12:34'),
(3, 6, 26, '2025-12-25', '12:30:00', '[\"15\"]', NULL, 20.00, '', 'Completed', NULL, '2025-12-24 03:23:56', '2025-12-24 03:29:49'),
(4, 6, 26, '2025-12-25', '12:30:00', '[\"19\"]', NULL, 100.00, '', 'Cancelled', 'test', '2025-12-24 03:30:17', '2025-12-24 03:34:42'),
(5, 6, 26, '2025-12-27', '13:30:00', '[\"19\"]', NULL, 100.00, '', 'Cancelled', 'test', '2025-12-24 03:35:51', '2025-12-24 03:36:08'),
(6, 6, 26, '2025-12-27', '13:30:00', '[\"15\"]', NULL, 20.00, '', 'Cancelled', 'test', '2025-12-24 03:37:01', '2025-12-24 04:03:44'),
(7, 17, 26, '2025-12-25', '11:00:00', '[\"19\"]', NULL, 100.00, '', 'Completed', NULL, '2025-12-25 01:55:40', '2026-01-01 14:08:16'),
(8, 17, 26, '2026-01-01', '22:15:00', '[\"15\",\"18\"]', 31, 35.00, '', 'Completed', NULL, '2026-01-01 14:02:50', '2026-01-01 14:05:36'),
(9, 17, 26, '2026-01-01', '22:30:00', '[\"19\"]', NULL, 100.00, '', 'Confirmed', NULL, '2026-01-01 14:08:56', '2026-01-01 14:09:49'),
(10, 17, 26, '2026-01-01', '22:15:00', '[\"15\"]', NULL, 20.00, '', 'Confirmed', NULL, '2026-01-01 14:09:32', '2026-01-01 14:10:10'),
(11, 17, 26, '2026-01-02', '15:30:00', '[\"15\"]', NULL, 20.00, '', 'Completed', NULL, '2026-01-01 14:11:01', '2026-01-01 14:11:21'),
(12, 17, 26, '2026-01-01', '17:15:00', '[\"15\"]', NULL, 20.00, '', 'Confirmed', NULL, '2026-01-01 14:16:53', '2026-01-01 14:17:13'),
(13, 17, 26, '2026-01-01', '22:31:00', '[\"15\"]', NULL, 20.00, '', 'Cancelled', NULL, '2026-01-01 14:31:36', '2026-01-01 14:31:48'),
(14, 17, 26, '2026-01-01', '22:38:00', '[\"15\"]', NULL, 20.00, '', 'Confirmed', NULL, '2026-01-01 14:38:53', '2026-01-01 14:39:11'),
(15, 17, 26, '2026-01-01', '22:40:00', '[\"15\"]', NULL, 20.00, '', 'Pending', NULL, '2026-01-01 14:40:12', '2026-01-01 14:40:12'),
(16, 17, 26, '2026-01-01', '22:42:00', '[\"15\"]', NULL, 20.00, '', 'Pending', NULL, '2026-01-01 14:42:16', '2026-01-01 14:42:16');

-- --------------------------------------------------------

--
-- Table structure for table `barbers`
--

CREATE TABLE `barbers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `specialty` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Available','Unavailable','Deleted') DEFAULT 'Available',
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `barbers`
--

INSERT INTO `barbers` (`id`, `name`, `specialty`, `image`, `created_at`, `status`, `user_id`) VALUES
(6, 'Atef', 'Low Taper', '685c866e448e2_eefbc502-ae5b-4776-b429-046fc98dcff8.jpeg', '2025-06-25 23:29:50', 'Deleted', NULL),
(12, 'Zarruq', 'Botak', '69002e59d267b_60f419e1-9b5b-405f-835e-81b30b164699.jpeg', '2025-10-28 02:45:45', 'Deleted', NULL),
(14, 'Zarruq', 'Taper Fade', '6943a2e780b27_Gemini_Generated_Image_i8nn5zi8nn5zi8nn.png', '2025-12-18 06:44:55', 'Deleted', NULL),
(15, 'Habib', 'Taper Fade', '6943a3ca5a994_Low Taper vs Mid Taper_ Key Differences Explained.jpg', '2025-12-18 06:48:42', 'Deleted', NULL),
(16, 'Zarruq', 'Taper Fade', '6943a3fb934df_Gemini_Generated_Image_i8nn5zi8nn5zi8nn.png', '2025-12-18 06:49:31', 'Deleted', NULL),
(17, 'Atef', 'Fade', '6943a4cdbe383_8d49cb86-ad7b-4f3a-842d-c47e32fcc566.jpeg', '2025-12-18 06:53:01', 'Deleted', NULL),
(19, 'Ruq', 'Botak', '6943adf4d597f_Gemini_Generated_Image_i8nn5zi8nn5zi8nn.png', '2025-12-18 07:32:04', 'Deleted', NULL),
(25, 'Zarruq', 'Buzz Cut', '69465bb7da5bf_Gemini_Generated_Image_i8nn5zi8nn5zi8nn.png', '2025-12-20 08:17:59', 'Deleted', NULL),
(26, 'Zarruq', 'Buzz Cut', '694662aca1afc_Gemini_Generated_Image_i8nn5zi8nn5zi8nn.png', '2025-12-20 08:47:40', 'Available', 12),
(27, 'Barber 2', 'Low Fade', '694939343e121_HunMaze di TikTok.jpg', '2025-12-22 12:27:32', 'Deleted', 13);

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `barber_id` int(11) DEFAULT NULL,
  `shop_rating` int(11) NOT NULL CHECK (`shop_rating` between 1 and 5),
  `service_rating` int(11) NOT NULL CHECK (`service_rating` between 1 and 5),
  `staff_rating` int(11) NOT NULL CHECK (`staff_rating` between 1 and 5),
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `appointment_id`, `user_id`, `barber_id`, `shop_rating`, `service_rating`, `staff_rating`, `comments`, `created_at`) VALUES
(1, 1, 17, 26, 5, 5, 5, 'mantap laju', '2025-12-23 23:59:32');

-- --------------------------------------------------------

--
-- Table structure for table `haircuts`
--

CREATE TABLE `haircuts` (
  `id` int(11) NOT NULL,
  `style_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `face_shape` varchar(50) DEFAULT NULL,
  `hair_type` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `haircuts`
--

INSERT INTO `haircuts` (`id`, `style_name`, `description`, `image`, `created_at`, `face_shape`, `hair_type`) VALUES
(30, 'French Crop', '', '', '2025-12-25 01:42:48', 'oval,square,diamond', 'straight,curly'),
(31, 'Low Taper', '', '', '2025-12-25 01:42:58', 'oval,round,heart,long', 'straight,wavy');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `type` enum('success','info','warning','error') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `type`, `is_read`, `created_at`) VALUES
(25, 6, 'Terima kasih! Sesi gunting rambut pada 22 Dec telah selesai. ✨', 'success', 1, '2025-12-22 04:09:48'),
(26, 6, 'Anda tidak hadir untuk booking pada 22 Dec (12:15 PM). Sila hubungi kami. ⚠️', 'error', 1, '2025-12-22 04:11:30'),
(27, 6, 'Anda tidak hadir untuk booking pada 22 Dec (12:15 PM). Sila hubungi kami. ⚠️', 'error', 1, '2025-12-22 04:11:34'),
(28, 6, 'Booking anda pada 22 Dec (12:15 PM) telah DITERIMA! 🎉', 'success', 1, '2025-12-22 04:12:10'),
(29, 6, 'Terima kasih! Sesi gunting rambut pada 22 Dec telah selesai. ✨', 'success', 1, '2025-12-22 04:16:01'),
(30, 6, 'Anda tidak hadir untuk booking pada 22 Dec (12:30 PM). Sila hubungi kami. ⚠️', 'error', 1, '2025-12-22 04:35:26'),
(31, 6, 'Maaf, booking pada 22 Dec (12:30 PM) telah DIBATALKAN. Sila check history. ❌', 'error', 1, '2025-12-22 04:41:32'),
(32, 6, 'Terima kasih! Sesi gunting rambut pada 22 Dec telah selesai. ✨', 'success', 1, '2025-12-22 05:22:53'),
(33, 17, 'Maaf, booking pada 22 Dec (09:30 PM) telah DIBATALKAN. Sila check history. ❌', 'error', 1, '2025-12-22 13:13:42'),
(34, 17, 'Maaf, booking pada 22 Dec (09:15 PM) telah DIBATALKAN. Sila check history. ❌', 'error', 1, '2025-12-22 13:13:49'),
(35, 17, 'Maaf, booking pada 22 Dec (09:15 PM) telah DIBATALKAN. Sila check history. ❌', 'error', 1, '2025-12-22 13:13:53'),
(36, 17, 'Maaf, booking pada 22 Dec (10:30 PM) telah DIBATALKAN. Sila check history. ❌', 'error', 1, '2025-12-22 13:20:33'),
(37, 17, 'Maaf, booking pada 22 Dec (09:30 PM) telah DIBATALKAN. Sila check history. ❌', 'error', 1, '2025-12-22 13:20:38'),
(38, 17, 'Maaf, booking pada 22 Dec (09:30 PM) telah DIBATALKAN. Sila check history. ❌', 'error', 1, '2025-12-22 13:27:16'),
(39, 17, 'Booking anda pada 24 Dec (11:00 AM) telah DITERIMA! 🎉', 'success', 1, '2025-12-23 23:58:56'),
(40, 17, 'Terima kasih! Sesi gunting rambut pada 24 Dec telah selesai. ✨', 'success', 1, '2025-12-23 23:59:00'),
(41, 17, 'Maaf, booking pada 24 Dec (12:15 PM) telah DIBATALKAN. Sila check history. ❌', 'error', 1, '2025-12-24 00:12:34'),
(42, 6, 'Booking anda pada 25 Dec (12:30 PM) telah DITERIMA! 🎉', 'success', 1, '2025-12-24 03:29:45'),
(43, 6, 'Terima kasih! Sesi gunting rambut pada 25 Dec telah selesai. ✨', 'success', 1, '2025-12-24 03:29:49'),
(44, 6, 'Maaf, booking pada 25 Dec (12:30 PM) telah DIBATALKAN. Sila check history. ❌', 'error', 1, '2025-12-24 03:34:42'),
(45, 6, 'Maaf, booking pada 27 Dec (01:30 PM) telah DIBATALKAN. Sila check history. ❌', 'error', 0, '2025-12-24 03:36:08'),
(46, 6, 'Maaf, booking pada 27 Dec (01:30 PM) telah DIBATALKAN. Sila check history. ❌', 'error', 0, '2025-12-24 04:03:44'),
(47, 17, 'Booking anda pada 01 Jan (10:15 PM) telah DITERIMA! 🎉', 'success', 1, '2026-01-01 14:03:09'),
(48, 17, 'Terima kasih! Sesi gunting rambut pada 01 Jan telah selesai. ✨', 'success', 1, '2026-01-01 14:05:36'),
(49, 17, 'Booking anda pada 25 Dec (11:00 AM) telah DITERIMA! 🎉', 'success', 1, '2026-01-01 14:07:57'),
(50, 17, 'Terima kasih! Sesi gunting rambut pada 25 Dec telah selesai. ✨', 'success', 1, '2026-01-01 14:08:16'),
(51, 17, 'Booking anda pada 01 Jan (10:30 PM) telah DITERIMA! 🎉', 'success', 1, '2026-01-01 14:09:49'),
(52, 17, 'Booking anda pada 01 Jan (10:15 PM) telah DITERIMA! 🎉', 'success', 1, '2026-01-01 14:10:10'),
(53, 17, 'Booking anda pada 02 Jan (03:30 PM) telah DITERIMA! 🎉', 'success', 0, '2026-01-01 14:11:13'),
(54, 17, 'Terima kasih! Sesi gunting rambut pada 02 Jan telah selesai. ✨', 'success', 0, '2026-01-01 14:11:21'),
(55, 17, 'Booking anda pada 01 Jan (05:15 PM) telah DITERIMA! 🎉', 'success', 0, '2026-01-01 14:17:13'),
(56, 17, 'Booking anda pada 01 Jan (10:38 PM) telah DITERIMA! 🎉', 'success', 0, '2026-01-01 14:39:11');

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('available','rest','off') NOT NULL DEFAULT 'off',
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `google_event_id` varchar(255) DEFAULT NULL,
  `last_synced` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`id`, `user_id`, `date`, `status`, `start_time`, `end_time`, `google_event_id`, `last_synced`) VALUES
(1, 1, '2025-11-29', 'available', NULL, NULL, NULL, NULL),
(14, 12, '2025-12-29', 'off', '11:00:00', '23:00:00', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `service_name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `service_type` enum('haircut','shave','beard','styling','other') DEFAULT 'other',
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `service_name`, `price`, `description`, `image_path`, `service_type`, `image`, `created_at`) VALUES
(15, 'Hair Wash', 20.00, 'Cuci cuci', NULL, 'other', '692aa1f1c0d20_3f4012e0-987b-45b7-8aa1-0cc6a8a23b53.jpeg', '2025-11-29 07:34:09'),
(18, 'Haircut', 15.00, '', NULL, 'other', '6943ac29e4418_5b032732-0c53-4249-a69a-c8ffc88ee40b.jpeg', '2025-12-18 07:24:25'),
(19, 'Keratin', 100.00, '', NULL, 'other', '694820dd3b8ff_keratin.jpeg', '2025-12-21 16:31:25');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `role` varchar(50) NOT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL,
  `google_access_token` text DEFAULT NULL,
  `google_refresh_token` text DEFAULT NULL,
  `google_calendar_id` varchar(255) DEFAULT NULL,
  `calendar_sync_enabled` tinyint(1) DEFAULT 0,
  `token_expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `google_id`, `username`, `email`, `password`, `full_name`, `phone`, `role`, `reset_token`, `reset_expiry`, `google_access_token`, `google_refresh_token`, `google_calendar_id`, `calendar_sync_enabled`, `token_expires_at`) VALUES
(1, NULL, 'admin', 'admin@example.com', '$2y$10$y8p8WMAeZJX1H8OK7liYx.JcFE/X/RxJkr4t4Mw8kLbVaA0T9MLmO', 'admin', '(018) 217-2159', 'admin', NULL, NULL, NULL, NULL, NULL, 0, NULL),
(6, NULL, 'user', 'user@example.com', '$2y$10$GrLWC55rDVEjlgNrYwRdhegulhCCrl3Y09yYn33XP4izHifveyaTe', 'Ruq', '0182172159', 'customer', NULL, NULL, NULL, NULL, NULL, 0, NULL),
(12, NULL, 'staff', 'staff@example.com', '$2y$10$Ds9MX3NfCdou7xOgcSS3wu0FndGEi1Cy5Lyt6asj3qEIqKqNnPwCa', 'Staff 1', '0182172159', 'staff', NULL, NULL, NULL, NULL, NULL, 0, NULL),
(17, '101486020729835006004', 'ZeroX', 'zarruq09@gmail.com', '$2y$10$/QmFmdtf5wLn1AKrWqovy.imVHXjqtlKQL/wkkPBqJI9w2fTdhzAq', 'Ahmad Zarruq', '0182172159', 'customer', '4f4a7a5ed44f1682d6a08483a274975861b44dca87ab51037e0e13ccff3b1dfd', '2026-01-02 01:10:21', '{\"access_token\":\"ya29.a0Aa7pCA-BWlUdBn-xGjmpqxvZqfnvXAQ9fe4ALKnXLCXKp31CIPbNfmwY92vcfvaDPyCPKynlZh75SASRe3fke95Df0Rjp7tls9VJ8BDAWA0m3mnnPTx8BZyRNiWq7GrwkR2Q4Ok0vMd4z_j-JCdlL4eoJQF2btiwHBbhA60BxMl3Ou1gLanuvEZVQuZY_lc7TKmpwRcaCgYKAecSARUSFQHGX2MiJu7UbIVjF4RF2gJQMUL3DQ0206\",\"expires_in\":3599,\"refresh_token\":\"1\\/\\/0gi16ObZQftZyCgYIARAAGBASNwF-L9IrjWYJe7SvsGLje_RpdTi8v4lrOjl7FAckvOCAyfY4mWE4PmxDeQCLrOzZbCRWJYLT6OA\",\"scope\":\"openid https:\\/\\/www.googleapis.com\\/auth\\/userinfo.email https:\\/\\/www.googleapis.com\\/auth\\/userinfo.profile\",\"token_type\":\"Bearer\",\"id_token\":\"eyJhbGciOiJSUzI1NiIsImtpZCI6IjQ5NmQwMDhlOGM3YmUxY2FlNDIwOWUwZDVjMjFiMDUwYTYxZTk2MGYiLCJ0eXAiOiJKV1QifQ.eyJpc3MiOiJodHRwczovL2FjY291bnRzLmdvb2dsZS5jb20iLCJhenAiOiI2MjUxNzg0MTkyMzYtNnAxdmkxaDR0MGZsdmtndXNrZGlrcDM2c2RxM2VjNGUuYXBwcy5nb29nbGV1c2VyY29udGVudC5jb20iLCJhdWQiOiI2MjUxNzg0MTkyMzYtNnAxdmkxaDR0MGZsdmtndXNrZGlrcDM2c2RxM2VjNGUuYXBwcy5nb29nbGV1c2VyY29udGVudC5jb20iLCJzdWIiOiIxMDE0ODYwMjA3Mjk4MzUwMDYwMDQiLCJlbWFpbCI6InphcnJ1cTA5QGdtYWlsLmNvbSIsImVtYWlsX3ZlcmlmaWVkIjp0cnVlLCJhdF9oYXNoIjoidldnS21hUkZKdTl5MWtkTUNwZFlsQSIsIm5hbWUiOiJBaG1hZCBaYXJydXEiLCJwaWN0dXJlIjoiaHR0cHM6Ly9saDMuZ29vZ2xldXNlcmNvbnRlbnQuY29tL2EvQUNnOG9jSnRITjRydzBXd2MyRUcxY2w0c1pXbDdPN2JlMWg1WGtsZlBTTW9TTUd4QnlGSjY4V2w9czk2LWMiLCJnaXZlbl9uYW1lIjoiQWhtYWQiLCJmYW1pbHlfbmFtZSI6IlphcnJ1cSIsImlhdCI6MTc2NzI4MzI4NCwiZXhwIjoxNzY3Mjg2ODg0fQ.QJIUT7l0LrpI_IsbXhQNG5vQXdseSdJ0F4_yq1CHLNf8jvOdmS0ZeZbB71udGhzBwQhS_acB2e4qN3LcHNqR6kW5B5iLhVEz_6oBmkUG8IN7qZa6mwOsWXdpDOx-XqyErm3QwieWcV085i62g3GECGInedTn2WWX0RM8ofeEvbXUzIVfjCuCAUbeKsp5zepllWRl_35w1RRaLJL42_oUg68il-0mlxNFT3Ftv2WXVq1799AnnjWfaF1WuBrQjgyvIFwmG4-26dodpTYw-9yhD1EFhdbIKrJfJmXm0z9urIjGJ375DhgRGznVAKDJinQJ4GRJXMfImlgqaeW0LyYhdA\",\"created\":1767283284}', '1//0gi16ObZQftZyCgYIARAAGBASNwF-L9IrjWYJe7SvsGLje_RpdTi8v4lrOjl7FAckvOCAyfY4mWE4PmxDeQCLrOzZbCRWJYLT6OA', NULL, 0, '2026-01-01 18:01:23');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `barber_id` (`barber_id`),
  ADD KEY `haircut_id` (`haircut_id`);

--
-- Indexes for table `barbers`
--
ALTER TABLE `barbers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `appointment_id` (`appointment_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `barber_id` (`barber_id`);

--
-- Indexes for table `haircuts`
--
ALTER TABLE `haircuts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_schedule` (`user_id`,`date`),
  ADD KEY `idx_google_event_id` (`google_event_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_calendar_sync` (`calendar_sync_enabled`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `barbers`
--
ALTER TABLE `barbers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `haircuts`
--
ALTER TABLE `haircuts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`barber_id`) REFERENCES `barbers` (`id`),
  ADD CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`haircut_id`) REFERENCES `haircuts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_ibfk_3` FOREIGN KEY (`barber_id`) REFERENCES `barbers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
