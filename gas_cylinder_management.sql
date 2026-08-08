-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 07, 2026 at 09:52 PM
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
-- Database: `gas_cylinder_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `role` varchar(20) DEFAULT 'admin',
  `status` varchar(20) DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `username`, `password`, `name`, `role`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', '12345', 'ผู้ดูแลระบบ', 'admin', 'active', NULL, '2026-08-07 22:28:47', '2026-08-08 02:22:10'),
(2, 'manager', '$2y$10$YourHashedPasswordHere', 'ผู้จัดการ', 'manager', 'active', NULL, '2026-08-07 22:28:47', '2026-08-07 22:28:47');

-- --------------------------------------------------------

--
-- Table structure for table `admin_activity_log`
--

CREATE TABLE `admin_activity_log` (
  `log_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `customer_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text DEFAULT NULL,
  `map_pin` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `tax_id` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`customer_id`, `name`, `phone`, `address`, `map_pin`, `email`, `tax_id`, `created_at`, `updated_at`) VALUES
(1, 'บริษัท แก๊สเซ็นเตอร์ จำกัด', '021234567', '789 ถ.พระราม 9 กรุงเทพฯ', '13.7563,100.5018', NULL, NULL, '2026-08-07 22:28:47', '2026-08-07 22:28:47'),
(2, 'ร้านอาหารครัวคุณแม่', '086543210', '101 ถ.สีลม กรุงเทพฯ', '13.7293,100.5249', NULL, NULL, '2026-08-07 22:28:47', '2026-08-07 22:28:47'),
(3, 'ฟหก', '08765646894', 'ฟหกฟหกผปแำ ฟหก', NULL, NULL, NULL, '2026-08-08 02:31:48', '2026-08-08 02:31:48');

-- --------------------------------------------------------

--
-- Table structure for table `delivery`
--

CREATE TABLE `delivery` (
  `delivery_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `serial_number` varchar(50) DEFAULT NULL,
  `gas_type` varchar(50) DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `size` varchar(20) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `delivery_date` datetime DEFAULT NULL,
  `accepted_at` datetime DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `proof_image_path` varchar(255) DEFAULT NULL,
  `delivered_map_pin` varchar(50) DEFAULT NULL,
  `received_serial_number` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery`
--

INSERT INTO `delivery` (`delivery_id`, `customer_id`, `admin_id`, `staff_id`, `serial_number`, `gas_type`, `brand`, `size`, `status`, `delivery_date`, `accepted_at`, `started_at`, `completed_at`, `delivered_at`, `proof_image_path`, `delivered_map_pin`, `received_serial_number`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 'SN0012345678', 'LPG', 'นครหลวง', '15kg', 'pending', '2026-08-07 22:28:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-07 22:28:47', '2026-08-07 22:28:47'),
(2, 2, 1, 2, 'SN0098765432', 'LPG', 'ปตท.', '48kg', 'delivering', '2026-08-07 22:28:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-07 22:28:47', '2026-08-07 22:28:47'),
(3, 3, NULL, 2, 'SN0012345678', 'LPG', 'นครหลวง', '15kg', 'pending', '2026-08-08 02:31:48', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-08 02:31:48', '2026-08-08 02:31:48');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_staff`
--

CREATE TABLE `delivery_staff` (
  `staff_id` int(11) NOT NULL,
  `staff_name` varchar(100) NOT NULL,
  `staff_phone` varchar(20) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery_staff`
--

INSERT INTO `delivery_staff` (`staff_id`, `staff_name`, `staff_phone`, `username`, `password`, `address`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'สมชาย ใจดี', '0812345678', 'staff1', 'admin', '123 ถ.สุขุมวิท กรุงเทพฯ', 'active', NULL, '2026-08-07 22:28:47', '2026-08-08 02:21:51'),
(2, 'สมหญิง รักงาน', '0898765432', 'staff2', '$2y$10$/BuFH1A044sJuiL.fUqZLeGY1ay.n18Ml9MgD89lousOlRaNDhNam', '456 ถ.พหลโยธิน กรุงเทพฯ', 'active', NULL, '2026-08-07 22:28:47', '2026-08-08 01:34:42');

-- --------------------------------------------------------

--
-- Table structure for table `gas_cylinder`
--

CREATE TABLE `gas_cylinder` (
  `cylinder_id` int(11) NOT NULL,
  `serial_number` varchar(50) NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `gas_type` varchar(50) DEFAULT 'LPG',
  `size` varchar(20) DEFAULT NULL,
  `manufacture_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `last_check_date` date DEFAULT NULL,
  `next_check_date` date DEFAULT NULL,
  `delivered_date` date DEFAULT NULL,
  `current_location` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'ในคลัง',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gas_cylinder`
--

INSERT INTO `gas_cylinder` (`cylinder_id`, `serial_number`, `brand`, `gas_type`, `size`, `manufacture_date`, `expiry_date`, `qr_code`, `last_check_date`, `next_check_date`, `delivered_date`, `current_location`, `status`, `created_at`, `updated_at`) VALUES
(1, 'SN0012345678', 'นครหลวง', 'LPG', '15kg', '2022-01-15', '2032-01-15', NULL, NULL, '2025-01-15', NULL, 'คลัง', 'กำลังจัดส่ง', '2026-08-07 22:28:47', '2026-08-08 02:31:48'),
(2, 'SN0098765432', 'ปตท.', 'LPG', '48kg', '2021-06-20', '2031-06-20', NULL, NULL, '2024-06-20', NULL, 'กำลังจัดส่ง', 'กำลังส่ง', '2026-08-07 22:28:47', '2026-08-07 22:28:47'),
(3, 'SN0056789012', 'นครหลวง', 'LPG', '15kg', '2023-03-10', '2033-03-10', NULL, NULL, '2026-03-10', NULL, 'ลูกค้า', 'อยู่กับลูกค้า', '2026-08-07 22:28:47', '2026-08-07 22:28:47');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance`
--

CREATE TABLE `maintenance` (
  `maintenance_id` int(11) NOT NULL,
  `maintenance_date` datetime DEFAULT current_timestamp(),
  `description` text DEFAULT NULL,
  `maintenance_type` varchar(50) DEFAULT NULL,
  `result` varchar(50) DEFAULT NULL,
  `cylinder_id` varchar(50) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `next_maintenance_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance`
--

INSERT INTO `maintenance` (`maintenance_id`, `maintenance_date`, `description`, `maintenance_type`, `result`, `cylinder_id`, `admin_id`, `next_maintenance_date`, `created_at`, `updated_at`) VALUES
(1, '2026-08-07 22:28:47', 'ตรวจสอบสภาพทั่วไป อยู่ในเกณฑ์ดี', 'ตรวจสภาพ', 'ผ่าน', 'SN0012345678', 1, '2025-07-15', '2026-08-07 22:28:47', '2026-08-07 22:28:47'),
(2, '2026-08-07 22:28:47', 'เปลี่ยนวาล์วและตรวจสอบรอยรั่ว', 'บำรุงรักษา', 'ผ่าน', 'SN0098765432', 1, '2024-09-20', '2026-08-07 22:28:47', '2026-08-07 22:28:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `admin_activity_log`
--
ALTER TABLE `admin_activity_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `phone` (`phone`);

--
-- Indexes for table `delivery`
--
ALTER TABLE `delivery`
  ADD PRIMARY KEY (`delivery_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `serial_number` (`serial_number`);

--
-- Indexes for table `delivery_staff`
--
ALTER TABLE `delivery_staff`
  ADD PRIMARY KEY (`staff_id`),
  ADD UNIQUE KEY `staff_phone` (`staff_phone`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `gas_cylinder`
--
ALTER TABLE `gas_cylinder`
  ADD PRIMARY KEY (`cylinder_id`),
  ADD UNIQUE KEY `serial_number` (`serial_number`);

--
-- Indexes for table `maintenance`
--
ALTER TABLE `maintenance`
  ADD PRIMARY KEY (`maintenance_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `admin_activity_log`
--
ALTER TABLE `admin_activity_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `delivery`
--
ALTER TABLE `delivery`
  MODIFY `delivery_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `delivery_staff`
--
ALTER TABLE `delivery_staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `gas_cylinder`
--
ALTER TABLE `gas_cylinder`
  MODIFY `cylinder_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `maintenance`
--
ALTER TABLE `maintenance`
  MODIFY `maintenance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_activity_log`
--
ALTER TABLE `admin_activity_log`
  ADD CONSTRAINT `admin_activity_log_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`) ON DELETE CASCADE;

--
-- Constraints for table `delivery`
--
ALTER TABLE `delivery`
  ADD CONSTRAINT `delivery_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `delivery_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `delivery_ibfk_3` FOREIGN KEY (`staff_id`) REFERENCES `delivery_staff` (`staff_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `delivery_ibfk_4` FOREIGN KEY (`serial_number`) REFERENCES `gas_cylinder` (`serial_number`) ON DELETE SET NULL;

--
-- Constraints for table `maintenance`
--
ALTER TABLE `maintenance`
  ADD CONSTRAINT `maintenance_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
