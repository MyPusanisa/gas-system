-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 19, 2026 at 03:47 PM
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
-- Database: `gas_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `username`, `password`, `name`) VALUES
(1, 'admin', '1234', 'ผู้ดูแลระบบ'),
(2, 'manager', '5678', 'ผู้จัดการ'),
(3, 'superadmin', '9999', 'หัวหน้าผู้ดูแล');

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `customer_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `map_pin` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`customer_id`, `name`, `phone`, `address`, `map_pin`) VALUES
(1, 'Kang', '08694052365', 'ภูเก็ต', '4984321654984'),
(2, 'Gas', '08654628', 'phuket', '8484187'),
(3, 'Gas', '08654628', 'phuket', '8484187'),
(4, 'Gas', '08654628', 'phuket', '8484187'),
(5, 'Gas', '08654628', 'phuket', '8484187'),
(6, 'asda', 'asd', 'asd', 'asd'),
(7, 'LungF', '086548651', 'aaaaaa', 'aaaaaaa'),
(8, 'ข้าว', '086846510', 'ฟหกผปแไ', 'ฟผปแฟไกฟปผแ'),
(9, 'ข้าว', '086846510', 'ฟหกผปแไ', 'ฟผปแฟไกฟปผแ'),
(10, 'จันทร์', '5108435198', 'หำ', '3621684'),
(11, 'จันทร์', '5108435198', 'หำ', '3621684'),
(12, 'DKUB', '089656548', 'aaaa', 'aaaaaa'),
(13, '15984', '08689405871', 'asdas', 'dasdasd'),
(14, 'Nice', '0869551388', 'ภูเก็ต', 'ภูเก็ตเมือง'),
(15, 'Lose', '084517111', 'กรุงเทพ', '18323.822.15555'),
(16, 'Lose', '084517111', 'กรุงเทพ', '18323.822.15555'),
(17, 'Lose', '084517111', 'กรุงเทพ', '18323.822.15555'),
(18, '๋JJ', '07591111', 'pangnga', '156.359.257'),
(19, 'หนูดี', '014451567496', 'กรุงเทพ', '125.66.5872'),
(20, 'ไท', '0412365555', 'กรุงเทพ', '153.6255'),
(21, 'ไก่', '08455511355', 'กรุวงเทพ', '125.531.5222'),
(22, 'หหห', 'ฟหก', 'ฟหกฟ', 'หก'),
(23, 'Din', '085555555', 'กรุงเทพ', '1555.55555'),
(24, 'หหหห', 'หหหห', 'หหหห', 'หหห'),
(25, '4444', 'ฟฟฟฟ', 'ฟ', '11111.5555'),
(26, '4444', 'ฟฟฟฟ', 'ฟ', 'ฟฟฟฟหกหก'),
(27, 'a', '1828989744875487', 'sd', '152.441474'),
(28, 'Grace', '08646515498', 'ภูเก็ต', '1556.513'),
(29, 'D', '08552321', 'กรุงเทพ', '152.365.323'),
(30, 'การ์น', '0866515916', 'กรุงเทพ', '13.546541');

-- --------------------------------------------------------

--
-- Table structure for table `delivery`
--

CREATE TABLE `delivery` (
  `delivery_id` int(11) NOT NULL,
  `cylinder_id` varchar(50) DEFAULT NULL,
  `received_cylinder_id` varchar(50) DEFAULT NULL,
  `customer_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `delivery_date` date DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `status` enum('pending','accepted','delivering','success','cancelled','pending_approval') DEFAULT 'pending',
  `accepted_at` datetime DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `delivered_map_pin` varchar(255) DEFAULT NULL,
  `proof_image_path` varchar(255) DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `gas_type` varchar(50) DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `size` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery`
--

INSERT INTO `delivery` (`delivery_id`, `cylinder_id`, `received_cylinder_id`, `customer_id`, `admin_id`, `staff_id`, `delivery_date`, `return_date`, `status`, `accepted_at`, `started_at`, `completed_at`, `created_at`, `delivered_map_pin`, `proof_image_path`, `delivered_at`, `gas_type`, `brand`, `size`) VALUES
(33, 'CY001', NULL, 30, 1, 1, '2026-05-19', NULL, 'pending', NULL, NULL, NULL, '2026-05-19 09:10:12', NULL, NULL, NULL, 'LPG', 'PTT', '15kg');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_staff`
--

CREATE TABLE `delivery_staff` (
  `staff_id` int(11) NOT NULL,
  `staff_name` varchar(100) DEFAULT NULL,
  `staff_phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery_staff`
--

INSERT INTO `delivery_staff` (`staff_id`, `staff_name`, `staff_phone`, `address`, `username`, `password`, `status`) VALUES
(1, 'สมชาย ใจดี', '0812345678', 'เชียงใหม่', 'somchai', '1234', 'active'),
(2, 'สมหญิง พูนสุข', '0898765432', 'ลำปาง', 'somying', '5678', 'active'),
(3, 'วีระชัย ทองคำ', '0821112233', 'กรุงเทพ', 'weerachai', '9999', 'inactive');

-- --------------------------------------------------------

--
-- Table structure for table `gas_cylinder`
--

CREATE TABLE `gas_cylinder` (
  `cylinder_id` varchar(50) NOT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `size` varchar(50) DEFAULT NULL,
  `manufacture_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `qr_code` text DEFAULT NULL,
  `brand` varchar(50) DEFAULT NULL,
  `gas_type` varchar(50) DEFAULT NULL,
  `last_check_date` date DEFAULT NULL,
  `next_check_date` date DEFAULT NULL,
  `delivered_date` date DEFAULT NULL,
  `current_location` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gas_cylinder`
--

INSERT INTO `gas_cylinder` (`cylinder_id`, `serial_number`, `status`, `size`, `manufacture_date`, `expiry_date`, `qr_code`, `brand`, `gas_type`, `last_check_date`, `next_check_date`, `delivered_date`, `current_location`, `created_at`, `updated_at`) VALUES
('asd', 'asd', 'ในคลัง', '11.5 กก.', '2026-05-04', '2036-05-04', 'https://yourdomain.com/cylinder/asd', 'World Gas', 'LPG', '2026-05-17', '2029-05-04', '2026-05-19', 'คลัง', '2026-05-17 08:14:26', '2026-05-19 09:09:15'),
('CY001', 'SN-1001', 'กำลังจัดส่ง', '15kg', '2022-01-10', '2032-01-10', 'QR001', 'PTT', 'LPG', '2025-05-01', '2026-05-13', '2025-01-15', 'รอจัดส่ง', '2026-05-08 20:24:47', '2026-05-19 09:10:12'),
('CY002', 'SN-1002', 'ในคลัง', '48kg', '2021-03-12', '2031-03-12', 'QR002', 'WorldGas', 'LPG', '2025-04-15', '2026-05-23', '2025-02-10', 'คลัง B', '2026-05-08 20:24:47', '2026-05-13 14:19:23');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance`
--

CREATE TABLE `maintenance` (
  `maintenance_id` int(11) NOT NULL,
  `maintenance_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `maintenance_type` varchar(100) DEFAULT NULL,
  `result` text DEFAULT NULL,
  `next_maintenance_date` date DEFAULT NULL,
  `cylinder_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance`
--

INSERT INTO `maintenance` (`maintenance_id`, `maintenance_date`, `description`, `maintenance_type`, `result`, `next_maintenance_date`, `cylinder_id`, `admin_id`) VALUES
(2, '2026-05-08', 'asd', 'ตรวจสภาพ', 'ผ่าน', '2026-08-06', 0, NULL),
(3, '2026-05-08', 'เยียมมาก', 'ตรวจสภาพ', 'ผ่าน', '2026-08-06', 0, NULL),
(4, '2026-05-08', '', 'บำรุงรักษา', 'ผ่าน', '2026-08-06', 0, 1),
(5, '2026-05-15', '', 'ตรวจสภาพ', 'ผ่าน', '2026-08-13', 0, 1);

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
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `delivery`
--
ALTER TABLE `delivery`
  ADD PRIMARY KEY (`delivery_id`),
  ADD KEY `fk_delivery_staff` (`staff_id`),
  ADD KEY `fk_delivery_customer` (`customer_id`),
  ADD KEY `fk_delivery_admin` (`admin_id`),
  ADD KEY `fk_delivery_cylinder` (`cylinder_id`),
  ADD KEY `fk_delivery_received_cylinder` (`received_cylinder_id`);

--
-- Indexes for table `delivery_staff`
--
ALTER TABLE `delivery_staff`
  ADD PRIMARY KEY (`staff_id`),
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
  ADD KEY `cylinder_id` (`cylinder_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `delivery`
--
ALTER TABLE `delivery`
  MODIFY `delivery_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `delivery_staff`
--
ALTER TABLE `delivery_staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `maintenance`
--
ALTER TABLE `maintenance`
  MODIFY `maintenance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `delivery`
--
ALTER TABLE `delivery`
  ADD CONSTRAINT `fk_delivery_admin` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`),
  ADD CONSTRAINT `fk_delivery_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`),
  ADD CONSTRAINT `fk_delivery_cylinder` FOREIGN KEY (`cylinder_id`) REFERENCES `gas_cylinder` (`cylinder_id`),
  ADD CONSTRAINT `fk_delivery_received_cylinder` FOREIGN KEY (`received_cylinder_id`) REFERENCES `gas_cylinder` (`cylinder_id`),
  ADD CONSTRAINT `fk_delivery_staff` FOREIGN KEY (`staff_id`) REFERENCES `delivery_staff` (`staff_id`);

--
-- Constraints for table `maintenance`
--
ALTER TABLE `maintenance`
  ADD CONSTRAINT `maintenance_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
