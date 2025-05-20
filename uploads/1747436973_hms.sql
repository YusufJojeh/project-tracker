-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 17, 2025 at 01:04 AM
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
-- Database: `hms`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_img` varchar(255) DEFAULT NULL,
  `type` enum('super_admin','admin') NOT NULL,
  `permissions` enum('FULL Permissions','Can''t Add Or Delete Admins') NOT NULL,
  `email` varchar(120) DEFAULT NULL,
  `phone` varchar(25) DEFAULT NULL,
  `department` varchar(80) DEFAULT NULL,
  `education` varchar(150) DEFAULT NULL,
  `location` varchar(120) DEFAULT NULL,
  `skills` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `username`, `password`, `profile_img`, `type`, `permissions`, `email`, `phone`, `department`, `education`, `location`, `skills`) VALUES
(1, 'admin1', '$2y$10$HNc27Dc0d.pZC6ASlsM7d.nUvifaHRZPwyU0GwsOwBfvxLVhuTCFK', 'admin1.jpg', 'super_admin', 'FULL Permissions', 'admin1@hms.com', '0100000001', 'Dept1', 'Degree1', 'City1', 'Skill1A,Skill1B'),
(2, 'admin2', '$2y$10$k5ZZswZgLGOI7uJ14AORU.VTHnJGseoavNUfRH7QBjzlLG4iamGgG', 'admin2.jpg', 'admin', 'Can\'t Add Or Delete Admins', 'admin2@hms.com', '0100000002', 'Dept2', 'Degree2', 'City2', 'Skill2A,Skill2B'),
(3, 'admin3', '$2y$10$ZnLgqbsrXVhUfSQSENVd8.AY0KGM5JquvVFoQXOE66GdFj0myUFKK', 'admin3.jpg', 'admin', 'Can\'t Add Or Delete Admins', 'admin3@hms.com', '0100000003', 'Dept3', 'Degree3', 'City3', 'Skill3A,Skill3B'),
(4, 'admin4', '$2y$10$xT6XI0jPqOJcOhmuopDRK.ucNwUphdHJijBKZMy2LxawEp9uzSEjC', 'admin4.jpg', 'admin', 'Can\'t Add Or Delete Admins', 'admin4@hms.com', '0100000004', 'Dept4', 'Degree4', 'City4', 'Skill4A,Skill4B'),
(5, 'admin5', '$2y$10$eBDaa.7YmuEICeLI7OX0AeqvEbUhjIhFhxOk7T0AhombcpeEUSn9C', 'admin5.jpg', 'admin', 'Can\'t Add Or Delete Admins', 'admin5@hms.com', '0100000005', 'Dept5', 'Degree5', 'City5', 'Skill5A,Skill5B'),
(6, 'admin6', '$2y$10$hPh35NEDA6.U22/Ms/bLN.wmtfPdd7TxJrbWFUpYtWenrSlKH16HC', 'admin6.jpg', 'admin', 'Can\'t Add Or Delete Admins', 'admin6@hms.com', '0100000006', 'Dept6', 'Degree6', 'City6', 'Skill6A,Skill6B'),
(7, 'admin7', '$2y$10$zAlZEaAd2VZLBO05vrOcAuk4V/Oucn5A7xLGFGZqQEmyO6V.pW1x2', 'admin7.jpg', 'admin', 'Can\'t Add Or Delete Admins', 'admin7@hms.com', '0100000007', 'Dept7', 'Degree7', 'City7', 'Skill7A,Skill7B'),
(8, 'admin8', '$2y$10$FVSIzl/ic5OTR3Isim3PX.zQRAQ/ButzWoqVsqmVYXixoOunYs4Y2', 'admin8.jpg', 'admin', 'Can\'t Add Or Delete Admins', 'admin8@hms.com', '0100000008', 'Dept8', 'Degree8', 'City8', 'Skill8A,Skill8B'),
(9, 'admin9', '$2y$10$Jtq223lG.rwFxy1uILB6IOgX9xTxA7jJe5R2/g7xRct5IUm01bXki', 'admin9.jpg', 'admin', 'Can\'t Add Or Delete Admins', 'admin9@hms.com', '0100000009', 'Dept9', 'Degree9', 'City9', 'Skill9A,Skill9B'),
(10, 'admin10', '$2y$10$RorCdA8W.pjdg.i3NGpPFOuGDOwJj3YfIuB3yWt0S8V5vYdM2XYqy', 'admin10.jpg', 'admin', 'Can\'t Add Or Delete Admins', 'admin10@hms.com', '01000000010', 'Dept10', 'Degree10', 'City10', 'Skill10A,Skill10B');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `appointment_date` datetime NOT NULL,
  `symptoms` text DEFAULT NULL,
  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `doctor_id`, `patient_id`, `appointment_date`, `symptoms`, `status`, `created_at`) VALUES
(1, 1, 2, '2025-05-13 16:19:50', 'Symptom example 0', 'pending', '2025-05-13 14:19:50'),
(2, 2, 3, '2025-05-14 16:19:50', 'Symptom example 1', 'confirmed', '2025-05-13 14:19:50'),
(3, 3, 4, '2025-05-15 16:19:50', 'Symptom example 2', 'completed', '2025-05-13 14:19:50'),
(4, 4, 5, '2025-05-16 16:19:50', 'Symptom example 3', 'cancelled', '2025-05-13 14:19:50'),
(5, 5, 6, '2025-05-17 16:19:50', 'Symptom example 4', 'pending', '2025-05-13 14:19:50'),
(6, 6, 7, '2025-05-18 16:19:50', 'Symptom example 5', 'confirmed', '2025-05-13 14:19:50'),
(7, 7, 8, '2025-05-19 16:19:50', 'Symptom example 6', 'completed', '2025-05-13 14:19:50'),
(8, 8, 9, '2025-05-20 16:19:50', 'Symptom example 7', 'cancelled', '2025-05-13 14:19:50'),
(9, 9, 10, '2025-05-21 16:19:50', 'Symptom example 8', 'pending', '2025-05-13 14:19:50'),
(10, 10, 1, '2025-05-22 16:19:50', 'Symptom example 9', 'confirmed', '2025-05-13 14:19:50');

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `doctor_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `salary` decimal(10,2) DEFAULT NULL,
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `profile_img` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`doctor_id`, `first_name`, `last_name`, `username`, `email`, `gender`, `phone`, `country`, `password`, `salary`, `registration_date`, `status`, `profile_img`) VALUES
(1, 'DocFirst1', 'DocLast1', 'doc_user1', 'doc1@example.com', 'Male', '011000001', 'Country1', '$2y$10$AC9WRP673zNMrxW9S7lrYuU1GpmJx2BHbtlzbxKkjG9EUqQLbu7Nu', 3308.00, '2025-05-13 14:19:48', 'approved', 'doctor1.jpg'),
(2, 'DocFirst2', 'DocLast2', 'doc_user2', 'doc2@example.com', 'Female', '011000002', 'Country2', '$2y$10$lmpDmeY/SQyxKBdi0VDvXu2Sd//e4l711Szp8Mtb4/YqGqPkJuH/6', 7188.00, '2025-05-13 14:19:48', 'rejected', 'doctor2.jpg'),
(3, 'DocFirst3', 'DocLast3', 'doc_user3', 'doc3@example.com', 'Male', '011000003', 'Country3', '$2y$10$cvA94bxBUgwqtZ7hXUHG8ORN265KSF2nC7n4FQJhdYW8IbeFUj4nu', 6701.00, '2025-05-13 14:19:49', 'pending', 'doctor3.jpg'),
(4, 'DocFirst4', 'DocLast4', 'doc_user4', 'doc4@example.com', 'Female', '011000004', 'Country4', '$2y$10$kKtHPQFeqjw0bZgXdt3tl.YIw8FqHbmSiNfPjKomEVckxtwyudaS2', 3190.00, '2025-05-13 14:19:49', 'approved', 'doctor4.jpg'),
(5, 'DocFirst5', 'DocLast5', 'doc_user5', 'doc5@example.com', 'Male', '011000005', 'Country5', '$2y$10$aLwok.zXxlmCUYz4K4Z2lufP3NkrhzCu6zBo8yAIN8Sk/Yg06uKwm', 3056.00, '2025-05-13 14:19:49', 'rejected', 'doctor5.jpg'),
(6, 'DocFirst6', 'DocLast6', 'doc_user6', 'doc6@example.com', 'Female', '011000006', 'Country6', '$2y$10$FNrti84NwlTEkGs6VxLHaOHaCmtwWdEbgHMnllZRqUbT30uJbbQCy', 3974.00, '2025-05-13 14:19:49', 'pending', 'doctor6.jpg'),
(7, 'DocFirst7', 'DocLast7', 'doc_user7', 'doc7@example.com', 'Male', '011000007', 'Country7', '$2y$10$JhCzFSywIm6eyQNrJmXFZ.Un7tiS/17kL2XmjxJTM37HSxaPPERme', 3531.00, '2025-05-13 14:19:49', 'approved', 'doctor7.jpg'),
(8, 'DocFirst8', 'DocLast8', 'doc_user8', 'doc8@example.com', 'Female', '011000008', 'Country8', '$2y$10$Cs69d.1eMbWBQVi6GYI/be0DpEK3nWoPfNAEEhcVLbFvwNWdjpF1.', 4800.00, '2025-05-13 14:19:49', 'rejected', 'doctor8.jpg'),
(9, 'DocFirst9', 'DocLast9', 'doc_user9', 'doc9@example.com', 'Male', '011000009', 'Country9', '$2y$10$9zdPfktt91xYSASOzdyfKelt/RZVhlW1XSumfGAMHCek8H7NmXiBK', 6442.00, '2025-05-13 14:19:49', 'pending', 'doctor9.jpg'),
(10, 'DocFirst10', 'DocLast10', 'doc_user10', 'doc10@example.com', 'Female', '0110000010', 'Country10', '$2y$10$XyZRCMs.FC4e.mGegXp8tOSBPnLz4JjRdU.CiPXOrYElH6Nw75l9S', 5708.00, '2025-05-13 14:19:49', 'approved', 'doctor10.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `doctors_patients`
--

CREATE TABLE `doctors_patients` (
  `doctor_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctors_patients`
--

INSERT INTO `doctors_patients` (`doctor_id`, `patient_id`) VALUES
(1, 1),
(2, 2),
(3, 3),
(4, 4),
(5, 5),
(6, 6),
(7, 7),
(8, 8),
(9, 9),
(10, 10);

-- --------------------------------------------------------

--
-- Table structure for table `incomes`
--

CREATE TABLE `incomes` (
  `income_id` int(11) NOT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `date_discharge` date DEFAULT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `appointment_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `incomes`
--

INSERT INTO `incomes` (`income_id`, `doctor_id`, `patient_id`, `date_discharge`, `amount_paid`, `description`, `appointment_id`) VALUES
(1, 1, 1, '2025-05-13', 193.00, 'Income for appointment 1', 1),
(2, 2, 2, '2025-05-14', 372.00, 'Income for appointment 2', 2),
(3, 3, 3, '2025-05-15', 206.00, 'Income for appointment 3', 3),
(4, 4, 4, '2025-05-16', 118.00, 'Income for appointment 4', 4),
(5, 5, 5, '2025-05-17', 409.00, 'Income for appointment 5', 5),
(6, 6, 6, '2025-05-18', 236.00, 'Income for appointment 6', 6),
(7, 7, 7, '2025-05-19', 259.00, 'Income for appointment 7', 7),
(8, 8, 8, '2025-05-20', 215.00, 'Income for appointment 8', 8),
(9, 9, 9, '2025-05-21', 291.00, 'Income for appointment 9', 9),
(10, 10, 10, '2025-05-22', 484.00, 'Income for appointment 10', 10);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('admin','doctor','patient') NOT NULL,
  `message` text NOT NULL,
  `status` enum('unread','read') DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `user_type`, `message`, `status`, `created_at`) VALUES
(1, 1, 'admin', 'Admin notification 0', 'unread', '2025-05-13 14:19:50'),
(2, 2, 'doctor', 'Doctor notification 1', 'unread', '2025-05-13 14:19:50'),
(3, 3, 'patient', 'Patient notification 2', 'unread', '2025-05-13 14:19:50'),
(4, 4, 'admin', 'Admin notification 3', 'unread', '2025-05-13 14:19:50'),
(5, 5, 'doctor', 'Doctor notification 4', 'unread', '2025-05-13 14:19:50'),
(6, 6, 'patient', 'Patient notification 5', 'unread', '2025-05-13 14:19:50'),
(7, 7, 'admin', 'Admin notification 6', 'unread', '2025-05-13 14:19:50'),
(8, 8, 'doctor', 'Doctor notification 7', 'unread', '2025-05-13 14:19:50'),
(9, 9, 'patient', 'Patient notification 8', 'unread', '2025-05-13 14:19:50'),
(10, 10, 'admin', 'Admin notification 9', 'unread', '2025-05-13 14:19:50');

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `patient_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `address` text DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `profile_img` varchar(255) DEFAULT NULL,
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`patient_id`, `first_name`, `last_name`, `username`, `email`, `gender`, `phone`, `birth_date`, `address`, `password`, `profile_img`, `registration_date`) VALUES
(1, 'PatFirst1', 'PatLast1', 'pat_user1', 'pat1@example.com', 'Female', '012000001', '2024-05-13', 'Address line 1', '$2y$10$qND9Mh/waJHKYekVxUj/1eYt2qOAzJULzswK/zIWpZLpEP4PsFSjm', 'patient1.jpg', '2025-05-13 14:19:49'),
(2, 'PatFirst2', 'PatLast2', 'pat_user2', 'pat2@example.com', 'Male', '012000002', '2023-05-13', 'Address line 2', '$2y$10$cvMz2va21fwbUcAjeA1KMOuVGwOOUqKphWoje098csKmXrpi1iWvG', 'patient2.jpg', '2025-05-13 14:19:49'),
(3, 'PatFirst3', 'PatLast3', 'pat_user3', 'pat3@example.com', 'Female', '012000003', '2022-05-13', 'Address line 3', '$2y$10$jp9.vkL61JMxIEz3ZuzkZOFOP0tlOn8fWO9KMSQG8AkIKBM17BBcO', 'patient3.jpg', '2025-05-13 14:19:50'),
(4, 'PatFirst4', 'PatLast4', 'pat_user4', 'pat4@example.com', 'Male', '012000004', '2021-05-13', 'Address line 4', '$2y$10$TDQtsnNJCn7BPhKk6M6LMudjXmBryojMx0Lw.R9DOfEYnNv5pV.xG', 'patient4.jpg', '2025-05-13 14:19:50'),
(5, 'PatFirst5', 'PatLast5', 'pat_user5', 'pat5@example.com', 'Female', '012000005', '2020-05-13', 'Address line 5', '$2y$10$0r4mJjRfYG52rjp9iJF8l.lZV/hSv8H4L6wX56q02BcdyI5K2z/L.', 'patient5.jpg', '2025-05-13 14:19:50'),
(6, 'PatFirst6', 'PatLast6', 'pat_user6', 'pat6@example.com', 'Male', '012000006', '2019-05-13', 'Address line 6', '$2y$10$2yZZVZbQOZx29RAV7UF8Cewyt.Xk37oAB48c.vDWy0OG2/cPcxV0G', 'patient6.jpg', '2025-05-13 14:19:50'),
(7, 'PatFirst7', 'PatLast7', 'pat_user7', 'pat7@example.com', 'Female', '012000007', '2018-05-13', 'Address line 7', '$2y$10$WD6fobcuktfHLv4dIOgvGOnxT2xCwAgKExc7oV5vkvjA5SXDS9kPm', 'patient7.jpg', '2025-05-13 14:19:50'),
(8, 'PatFirst8', 'PatLast8', 'pat_user8', 'pat8@example.com', 'Male', '012000008', '2017-05-13', 'Address line 8', '$2y$10$FMOBjZPuxfPOxs12tGlzzeQZgTosmwpswFUwqmUoDpZFNliD/Q3Qm', 'patient8.jpg', '2025-05-13 14:19:50'),
(9, 'PatFirst9', 'PatLast9', 'pat_user9', 'pat9@example.com', 'Female', '012000009', '2016-05-13', 'Address line 9', '$2y$10$i7H0jzHulb2kWaPo7Eu3nuAYHK9cFBt9oNSviLCbR9RWn0t2T1TaC', 'patient9.jpg', '2025-05-13 14:19:50'),
(10, 'PatFirst10', 'PatLast10', 'pat_user10', 'pat10@example.com', 'Male', '0120000010', '2015-05-13', 'Address line 10', '$2y$10$9J/Tm.dWSCo8b8wk05nLD.QBOW.iTt68rMxkWP03d1nF1OL8PjPni', 'patient10.jpg', '2025-05-13 14:19:50');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `report_title` varchar(255) NOT NULL,
  `report_content` text DEFAULT NULL,
  `report_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `img` varchar(255) DEFAULT NULL,
  `file` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`report_id`, `doctor_id`, `patient_id`, `report_title`, `report_content`, `report_date`, `img`, `file`) VALUES
(1, 1, 1, 'Report Title 0', 'Content for report 1', '2025-05-13 13:19:50', 'report_img_0.jpg', 'report_file_0.pdf'),
(2, 2, 2, 'Report Title 1', 'Content for report 2', '2025-05-14 13:19:50', 'report_img_1.jpg', 'report_file_1.pdf'),
(3, 3, 3, 'Report Title 2', 'Content for report 3', '2025-05-15 13:19:50', 'report_img_2.jpg', 'report_file_2.pdf'),
(4, 4, 4, 'Report Title 3', 'Content for report 4', '2025-05-16 13:19:50', 'report_img_3.jpg', 'report_file_3.pdf'),
(5, 5, 5, 'Report Title 4', 'Content for report 5', '2025-05-17 13:19:50', 'report_img_4.jpg', 'report_file_4.pdf'),
(6, 6, 6, 'Report Title 5', 'Content for report 6', '2025-05-18 13:19:50', 'report_img_5.jpg', 'report_file_5.pdf'),
(7, 7, 7, 'Report Title 6', 'Content for report 7', '2025-05-19 13:19:50', 'report_img_6.jpg', 'report_file_6.pdf'),
(8, 8, 8, 'Report Title 7', 'Content for report 8', '2025-05-20 13:19:50', 'report_img_7.jpg', 'report_file_7.pdf'),
(9, 9, 9, 'Report Title 8', 'Content for report 9', '2025-05-21 13:19:50', 'report_img_8.jpg', 'report_file_8.pdf'),
(10, 10, 10, 'Report Title 9', 'Content for report 10', '2025-05-22 13:19:50', 'report_img_9.jpg', 'report_file_9.pdf');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`doctor_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `doctors_patients`
--
ALTER TABLE `doctors_patients`
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `incomes`
--
ALTER TABLE `incomes`
  ADD PRIMARY KEY (`income_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `appointment_id` (`appointment_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`patient_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `doctor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `incomes`
--
ALTER TABLE `incomes`
  MODIFY `income_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `patient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE;

--
-- Constraints for table `doctors_patients`
--
ALTER TABLE `doctors_patients`
  ADD CONSTRAINT `doctors_patients_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `doctors_patients_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE;

--
-- Constraints for table `incomes`
--
ALTER TABLE `incomes`
  ADD CONSTRAINT `incomes_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `incomes_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `incomes_ibfk_3` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE SET NULL;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`),
  ADD CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
