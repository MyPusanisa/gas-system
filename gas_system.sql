-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3308
-- Generation Time: Sep 10, 2026 at 05:13 PM
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
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text DEFAULT NULL,
  `map_pin` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `name`, `phone`, `address`, `map_pin`) VALUES
(19, 'ฟ้าลดา', '014451567496', 'กรุงเทพ', '125.66.5872'),
(20, 'ไท', '0412365555', 'กรุงเทพ', '153.6255'),
(18, '๋JJ', '07591111', 'pangnga', '156.359.257'),
(15, 'Lose', '084517111', 'กรุงเทพ', '18323.822.15555'),
(21, 'ไก่', '08455511355', 'กรุวงเทพ', '125.531.5222'),
(34, 'ฟ้า', '0854563254', 'เพชรหึงษ์ 2', ''),
(29, 'D', '08552321', 'กรุงเทพ', '152.365.323'),
(23, 'Din', '085555555', 'กรุงเทพ', '1555.55555'),
(35, 'ฟ้าลดา', '0859874563', 'เพชรหึงษ์ 3', ''),
(28, 'Grace', '08646515498', 'ภูเก็ต', '1556.513'),
(2, 'Gas', '08654628', 'phuket', '8484187'),
(7, 'LungF', '086548651', 'aaaaaa', 'aaaaaaa'),
(30, 'การ์น', '0866515916', 'กรุงเทพ', '13.546541'),
(8, 'ข้าว', '086846510', 'ฟหกผปแไ', 'ฟผปแฟไกฟปผแ'),
(13, '15984', '08689405871', 'asdas', 'dasdasd'),
(1, 'Kang', '08694052365', 'ภูเก็ต', '4984321654984'),
(14, 'Nice', '0869551388', 'ภูเก็ต', 'ภูเก็ตเมือง'),
(12, 'DKUB', '089656548', 'aaaa', 'aaaaaa'),
(36, 'pp', '0898745656', 'ล็อกเอาต์ แล้วล็อกอินด้วยบัญชี Admin', ''),
(27, 'a', '1828989744875487', 'sd', '152.441474'),
(10, 'จันทร์', '5108435198', 'หำ', '3621684'),
(6, 'asda', 'asd', 'asd', 'asd'),
(25, '4444', 'ฟฟฟฟ', 'ฟ', '11111.5555'),
(22, 'หหห', 'ฟหก', 'ฟหกฟ', 'หก'),
(24, 'หหหห', 'หหหห', 'หหหห', 'หหห');

-- --------------------------------------------------------

--
-- Table structure for table `cylinders`
--

CREATE TABLE `cylinders` (
  `serial_number` varchar(50) NOT NULL,
  `brand` varchar(50) DEFAULT NULL,
  `gas_type` varchar(50) DEFAULT NULL,
  `size` varchar(20) DEFAULT NULL,
  `manufacture_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `last_check_date` date DEFAULT NULL,
  `next_check_date` date DEFAULT NULL,
  `delivered_date` date DEFAULT NULL,
  `current_location` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'ในคลัง',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deliveries`
--

CREATE TABLE `deliveries` (
  `delivery_id` int(11) NOT NULL,
  `cylinder_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `map_pin` varchar(255) DEFAULT NULL,
  `brand` varchar(50) DEFAULT NULL,
  `gas_type` varchar(50) DEFAULT 'LPG',
  `size` varchar(20) DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `proof_image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deliveries`
--

INSERT INTO `deliveries` (`delivery_id`, `cylinder_id`, `customer_name`, `phone`, `address`, `map_pin`, `brand`, `gas_type`, `size`, `staff_id`, `status`, `proof_image_path`, `created_at`) VALUES
(2, NULL, 'สมชาย การค้า', '0812345678', '123/45 ถ.สุขุมวิท กรุงเทพฯ', '13.7563,100.5018', 'PTT', 'LPG', '15 กก.', 1, 'pending', NULL, '2026-08-31 12:38:44'),
(3, NULL, 'ร้านอาหารแม่ยอม', '0898765432', '88 หมู่ 3 ต.ในเมือง อ.เมือง', '-', 'World Gas', 'LPG', '48 กก.', 2, 'success', NULL, '2026-08-31 12:38:44'),
(4, NULL, 'บริษัท ไทยกลาส จำกัด', '029998888', '555 อุตสาหกรรมบางปู สมุทรปราการ', '13.5412,100.6234', 'Unique Gas', 'N2O', '36 กก.', 4, 'pending', NULL, '2026-08-31 12:38:44'),
(5, NULL, 'วิชัย บริการ', '0861112233', '12/1 หมู่บ้านสวนหลวง กรุงเทพฯ', '-', 'PTT', 'LPG', '15 กก.', 1, 'success', 'proof_6aa12d21b01979.13398703.png', '2026-08-31 12:38:44'),
(6, NULL, 'โรงงานแก้วพัฒนา', '023456789', '99/1 บางพลี สมุทรปราการ', '13.6123,100.7123', 'World Gas', 'O2', '48 กก.', 2, 'pending', NULL, '2026-08-31 12:38:44'),
(7, NULL, 'คุณนภา ลิขิต', '0845556677', '432 ซอยลาดพร้าว 101 กรุงเทพฯ', '-', 'Unique Gas', 'LPG', '7 กก.', 4, 'pending', NULL, '2026-08-31 12:38:44'),
(8, NULL, 'ร้านเบเกอรี่โฮมเมด', '0839991122', '77 ถนนสีลม กรุงเทพฯ', '13.7234,100.5289', 'PTT', 'LPG', '15 กก.', 1, 'success', 'proof_6aa1217293b743.17317853.png', '2026-08-31 12:38:44'),
(9, NULL, 'คลินิกทันตกรรมสมบูรณ์', '028887766', '50/2 ถ.พหลโยธิน กรุงเทพฯ', '-', 'Siam Gas', 'N2O', '15 กก.', 2, 'pending', NULL, '2026-08-31 12:38:44'),
(10, NULL, 'ร้านหมูกระทะ ชาบูชิ', '0874445566', '101/5 ถ.พระราม 2 กรุงเทพฯ', '13.6512,100.4321', 'PTT', 'LPG', '48 กก.', 1, 'success', 'proof_6a965242331762.43356366.png', '2026-08-31 12:38:44'),
(11, 101, 'ฟ้า', '0822222222', 'ฟพะ่ไะัาไะัรา', '', 'PTT', 'LPG', '15 กก.', 1, 'success', NULL, '2026-09-09 12:06:16'),
(15, NULL, 'ฟ้าลดา', '014451567496', 'กรุงเทพ', '125.66.5872', 'PTT', 'LPG', '14', 1, 'success', 'proof_6aa2af6305b5a8.15312240.png', '2026-09-10 08:24:59'),
(16, NULL, 'Grace', '08646515498', 'ภูเก็ต', '1556.513', 'PTT', 'LPG', '14', 4, 'success', 'proof_6aa2b4be59bb11.01730526.png', '2026-09-10 13:25:08');

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
  `status` varchar(50) DEFAULT NULL,
  `delivery_count` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery_staff`
--

INSERT INTO `delivery_staff` (`staff_id`, `staff_name`, `staff_phone`, `address`, `username`, `password`, `status`, `delivery_count`) VALUES
(1, 'สมชาย ใจดี', '0812345678', 'เชียงใหม่', 'somchai', '12345', 'active', 5),
(2, 'สมหญิง พูนสุข', '0898765435', 'ลำปาง', 'somying', '5555525', 'active', 1),
(3, 'วีระชัย ทองคำ', '0821112233', 'กรุงเทพ', 'weerachai', '9999', 'inactive', 0),
(4, 'ภูษณิศา จันทร์นวล', '0822153045', '26/39 ม.9 เพชรหงษ์ 2 ต.ทรงคนอง', 'pimlypire', '9632', 'active', 1),
(5, 'ยย', '0822222222', 'รจบรสนร้ส', 'ยนวีนยว', '9652', 'active', 0),
(6, 'yuu', '0825632541', 'sshjstyafbDFhatja', 'aeah', '96396', 'active', 0);

-- --------------------------------------------------------

--
-- Table structure for table `gas_brands`
--

CREATE TABLE `gas_brands` (
  `id` int(11) NOT NULL,
  `brand_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gas_brands`
--

INSERT INTO `gas_brands` (`id`, `brand_name`) VALUES
(16, 'PTT'),
(15, 'ข้าวหอม'),
(1, 'ปตท.'),
(5, 'พีที (PT Gas)'),
(4, 'ยูนิคแก๊ส'),
(3, 'สยามแก๊ส'),
(2, 'เวิลด์แก๊ส');

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
('', '6003', 'กำลังส่ง', '14', '2026-09-09', '2036-09-09', NULL, 'PTT', 'LPG', NULL, '2031-09-09', NULL, 'กำลังจัดส่ง', '2026-09-10 12:45:32', '2026-09-10 12:57:36'),
('101', 'SN-3001', 'ในคลัง', '15kg', NULL, NULL, NULL, 'PTT', NULL, NULL, '2027-08-10', NULL, 'คลังสินค้า A', '2026-08-10 14:16:06', '2026-08-10 15:07:14'),
('102', 'SN-3002', 'ใช้งานอยู่', '15kg', NULL, NULL, NULL, 'World Gas', NULL, NULL, '2027-08-11', NULL, 'ร้านค้าสาขา 1', '2026-08-10 14:16:06', '2026-08-11 05:35:44'),
('103', 'SN-3003', 'ในคลัง', '11.5kg', NULL, NULL, NULL, 'Siam Gas', NULL, NULL, '2027-09-10', NULL, 'คลังสินค้า B', '2026-08-10 14:16:06', '2026-09-10 12:07:07'),
('104', 'SN-3004', 'ในคลัง', '4kg', NULL, NULL, NULL, 'Unique Gas', NULL, NULL, '2027-09-10', NULL, 'คลังสินค้า A', '2026-08-10 14:16:06', '2026-09-10 12:07:07'),
('asd', 'asd', 'ในคลัง', '11.5 กก.', '2026-05-04', '2036-05-04', 'https://yourdomain.com/cylinder/asd', 'World Gas', 'LPG', '2026-05-17', '2027-08-29', '2026-05-19', 'คลัง', '2026-05-17 08:14:26', '2026-08-29 16:45:03'),
('CY001', 'SN-1001', 'กำลังจัดส่ง', '99 kg', '2022-01-10', '2032-01-10', '', 'พีที (PT Gas)', 'NGV', '2025-05-01', '2027-09-10', '2025-01-15', 'คลัง', '2026-05-08 20:24:47', '2026-09-10 12:46:06'),
('CY002', 'SN-1002', 'กำลังส่ง', '15 กก.', '2021-03-12', '2031-03-12', '', 'PTT', 'LPG', '2025-04-15', '2027-09-10', NULL, 'กำลังจัดส่ง', '2026-05-08 20:24:47', '2026-09-10 12:46:06'),
('CYL-178904777785', '9874', 'ในคลัง', '14', NULL, '2036-09-18', NULL, 'PTT', 'LPG', NULL, '2031-09-18', NULL, NULL, '2026-09-10 13:42:57', '2026-09-10 13:42:57'),
('CYL-178904783314', '8525', 'กำลังส่ง', '14', NULL, '2036-09-02', NULL, 'PTT', 'LPG', NULL, '2031-09-02', NULL, 'กำลังจัดส่ง', '2026-09-10 13:43:53', '2026-09-10 13:46:21'),
('CYL-2026091015392657', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-10 13:39:26', '2026-09-10 13:39:26'),
('CYL-2026091015393157', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-10 13:39:31', '2026-09-10 13:39:31'),
('CYL-2026091015401054', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-10 13:40:10', '2026-09-10 13:40:10'),
('CYL-2026091015402877', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-10 13:40:28', '2026-09-10 13:40:28'),
('CYL-8001', 'SN-8001', 'ในคลัง', '14', '2026-09-15', '2036-09-15', '', 'พีที (PT Gas)', 'LPG', NULL, '2031-09-15', NULL, 'คลังสินค้า A', '2026-08-10 14:19:20', '2026-09-10 14:02:21'),
('CYL-8002', 'SN-8002', 'ใช้งานอยู่', '15kg', NULL, NULL, NULL, 'World Gas', NULL, NULL, '2026-08-01', NULL, 'ร้านค้าสาขา 1', '2026-08-10 14:19:20', '2026-08-10 14:29:29'),
('CYL-8003', 'SN-8003', 'ในคลัง', '11.5kg', NULL, NULL, NULL, 'Siam Gas', NULL, NULL, '2026-08-01', NULL, 'คลังสินค้า B', '2026-08-10 14:19:20', '2026-08-10 14:29:29'),
('CYL-8004', 'SN-8004', 'ในคลัง', '4kg', NULL, NULL, NULL, 'Unique Gas', NULL, NULL, '2026-08-01', NULL, 'คลังสินค้า A', '2026-08-10 14:19:20', '2026-08-10 14:29:29'),
('dsdhywrt', 'dsdhywrt', 'กำลังส่ง', '14', '2026-08-01', '2036-08-01', '', 'พีที (PT Gas)', 'NGV', '2026-08-08', '2027-08-29', NULL, 'คลัง', '2026-08-08 17:49:14', '2026-09-09 11:40:45'),
('etyketk', 'etyketk', 'ปกติ', '36 กก.', '2026-08-01', '2036-08-01', '', 'พีที (PT Gas)', 'LPG', '2026-08-08', '2027-08-29', NULL, 'พะเยา', '2026-08-08 17:50:42', '2026-09-09 11:40:55'),
('serhths', 'serhths', 'กำลังส่ง', '15 กก.', '2026-08-01', '2036-08-01', '', 'PTT', 'LPG', '2026-08-08', '2027-08-29', NULL, 'กำลังจัดส่ง', '2026-08-08 16:19:18', '2026-09-09 09:05:47');

-- --------------------------------------------------------

--
-- Table structure for table `gas_locations`
--

CREATE TABLE `gas_locations` (
  `id` int(11) NOT NULL,
  `location_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gas_locations`
--

INSERT INTO `gas_locations` (`id`, `location_name`) VALUES
(1, 'พะเยา'),
(2, 'เชียงราย'),
(3, 'คลัง');

-- --------------------------------------------------------

--
-- Table structure for table `gas_sensor_logs`
--

CREATE TABLE `gas_sensor_logs` (
  `id` int(11) NOT NULL,
  `gas_value` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gas_sensor_logs`
--

INSERT INTO `gas_sensor_logs` (`id`, `gas_value`, `created_at`) VALUES
(1, 450, '2026-08-31 11:22:32'),
(2, 480, '2026-08-31 11:22:32'),
(3, 510, '2026-08-31 11:22:32'),
(4, 530, '2026-08-31 11:22:32'),
(5, 490, '2026-08-31 11:22:32'),
(6, 500, '2026-08-31 11:22:32'),
(7, 520, '2026-08-31 11:22:32'),
(8, 470, '2026-08-31 11:22:32'),
(9, 515, '2026-08-31 11:22:32'),
(10, 505, '2026-08-31 11:22:32'),
(11, 450, '2026-08-31 11:26:32'),
(12, 480, '2026-08-31 11:26:32'),
(13, 510, '2026-08-31 11:26:32'),
(14, 530, '2026-08-31 11:26:32'),
(15, 490, '2026-08-31 11:26:32'),
(16, 500, '2026-08-31 11:26:32'),
(17, 520, '2026-08-31 11:26:32'),
(18, 470, '2026-08-31 11:26:32'),
(19, 515, '2026-08-31 11:26:32'),
(20, 505, '2026-08-31 11:26:32');

-- --------------------------------------------------------

--
-- Table structure for table `gas_sizes`
--

CREATE TABLE `gas_sizes` (
  `id` int(11) NOT NULL,
  `size_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gas_sizes`
--

INSERT INTO `gas_sizes` (`id`, `size_name`) VALUES
(1, '11.5 กก.'),
(10, '14'),
(2, '15 kg'),
(7, '36 kg.'),
(8, '36 กก.'),
(3, '48 kg'),
(11, '99'),
(9, '99 kg');

-- --------------------------------------------------------

--
-- Table structure for table `gas_statuses`
--

CREATE TABLE `gas_statuses` (
  `id` int(11) NOT NULL,
  `status_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gas_statuses`
--

INSERT INTO `gas_statuses` (`id`, `status_name`) VALUES
(1, 'ในคลัง'),
(2, 'กำลังส่ง'),
(3, 'ปกติ'),
(4, 'รอซ่อม'),
(5, 'ชำรุด');

-- --------------------------------------------------------

--
-- Table structure for table `gas_types`
--

CREATE TABLE `gas_types` (
  `id` int(11) NOT NULL,
  `type_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gas_types`
--

INSERT INTO `gas_types` (`id`, `type_name`) VALUES
(9, 'CP'),
(1, 'LPG'),
(8, 'NGV');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance`
--

CREATE TABLE `maintenance` (
  `maintenance_id` int(11) NOT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `maintenance_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `maintenance_type` varchar(100) DEFAULT NULL,
  `result` text DEFAULT NULL,
  `next_action` varchar(255) DEFAULT NULL,
  `next_maintenance_date` date DEFAULT NULL,
  `cylinder_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance`
--

INSERT INTO `maintenance` (`maintenance_id`, `serial_number`, `maintenance_date`, `description`, `maintenance_type`, `result`, `next_action`, `next_maintenance_date`, `cylinder_id`, `admin_id`, `created_at`) VALUES
(30, 'dsdhywrt', '2026-08-29', 'สภาพสมบูรณ์ พร้อมใช้งาน', 'ตรวจสภาพ', 'ผ่าน', 'ใช้งานต่อได้ (ปกติ)', NULL, NULL, NULL, '2026-08-29 23:45:03'),
(31, 'serhths', '2026-08-29', 'สภาพสมบูรณ์ พร้อมใช้งาน', 'ตรวจสภาพ', 'ผ่าน', 'ใช้งานต่อได้ (ปกติ)', NULL, NULL, NULL, '2026-08-29 23:45:03'),
(32, 'etyketk', '2026-08-29', 'สภาพสมบูรณ์ พร้อมใช้งาน', 'ตรวจสภาพ', 'ผ่าน', 'ใช้งานต่อได้ (ปกติ)', NULL, NULL, NULL, '2026-08-29 23:45:03'),
(33, 'asd', '2026-08-29', 'สภาพสมบูรณ์ พร้อมใช้งาน', 'ตรวจสภาพ', 'ผ่าน', 'ใช้งานต่อได้ (ปกติ)', NULL, NULL, NULL, '2026-08-29 23:45:03'),
(34, 'SN-3003', '2026-09-10', '[สิ่งที่ต้องทำต่อ: ใช้งานต่อได้ (ปกติ)] [หมายเหตุ: สภาพสมบูรณ์ พร้อมใช้งาน]', 'ตรวจสภาพ', 'ผ่าน', NULL, NULL, NULL, NULL, '2026-09-10 19:07:07'),
(35, 'SN-3004', '2026-09-10', '[สิ่งที่ต้องทำต่อ: ใช้งานต่อได้ (ปกติ)] [หมายเหตุ: สภาพสมบูรณ์ พร้อมใช้งาน]', 'ตรวจสภาพ', 'ผ่าน', NULL, NULL, NULL, NULL, '2026-09-10 19:07:07'),
(36, 'SN-1001', '2026-09-10', '[สิ่งที่ต้องทำต่อ: ใช้งานต่อได้ (ปกติ)] [หมายเหตุ: สภาพสมบูรณ์ พร้อมใช้งาน]', 'ตรวจสภาพ', 'ผ่าน', NULL, NULL, NULL, NULL, '2026-09-10 19:46:06'),
(37, 'SN-1002', '2026-09-10', '[สิ่งที่ต้องทำต่อ: ใช้งานต่อได้ (ปกติ)] [หมายเหตุ: สภาพสมบูรณ์ พร้อมใช้งาน]', 'ตรวจสภาพ', 'ผ่าน', NULL, NULL, NULL, NULL, '2026-09-10 19:46:06');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_options`
--

CREATE TABLE `maintenance_options` (
  `option_id` int(11) NOT NULL,
  `option_category` varchar(50) NOT NULL,
  `option_value` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance_options`
--

INSERT INTO `maintenance_options` (`option_id`, `option_category`, `option_value`) VALUES
(1, 'type', 'ตรวจสภาพ'),
(2, 'type', 'บำรุงรักษา'),
(3, 'type', 'ซ่อมแซม'),
(4, 'result', 'ผ่าน'),
(5, 'result', 'ไม่ผ่าน'),
(6, 'result', 'รอผลตรวจ'),
(7, 'action', 'ใช้งานต่อได้ (ปกติ)'),
(8, 'action', 'สมควรบำรุงรักษาต่อ'),
(9, 'action', 'ส่งซ่อมแซมด่วน'),
(10, 'action', 'ส่งทดสอบ Hydrostatic'),
(11, 'action', 'ปลดตระกูล / จำหน่ายออก'),
(12, 'note', 'สภาพสมบูรณ์ พร้อมใช้งาน'),
(13, 'note', 'วาล์วชำรุด สมควรเปลี่ยนวาล์ว'),
(14, 'note', 'ตัวถังมีรอยบุบ/สนิม ต้องบำรุงรักษา');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `role` enum('admin','staff') DEFAULT 'staff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`phone`);

--
-- Indexes for table `cylinders`
--
ALTER TABLE `cylinders`
  ADD PRIMARY KEY (`serial_number`);

--
-- Indexes for table `deliveries`
--
ALTER TABLE `deliveries`
  ADD PRIMARY KEY (`delivery_id`);

--
-- Indexes for table `delivery_staff`
--
ALTER TABLE `delivery_staff`
  ADD PRIMARY KEY (`staff_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `gas_brands`
--
ALTER TABLE `gas_brands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `brand_name` (`brand_name`);

--
-- Indexes for table `gas_cylinder`
--
ALTER TABLE `gas_cylinder`
  ADD PRIMARY KEY (`cylinder_id`),
  ADD UNIQUE KEY `serial_number` (`serial_number`);

--
-- Indexes for table `gas_locations`
--
ALTER TABLE `gas_locations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gas_sensor_logs`
--
ALTER TABLE `gas_sensor_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gas_sizes`
--
ALTER TABLE `gas_sizes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `size_name` (`size_name`);

--
-- Indexes for table `gas_statuses`
--
ALTER TABLE `gas_statuses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gas_types`
--
ALTER TABLE `gas_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `type_name` (`type_name`);

--
-- Indexes for table `maintenance`
--
ALTER TABLE `maintenance`
  ADD PRIMARY KEY (`maintenance_id`),
  ADD KEY `cylinder_id` (`cylinder_id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `fk_maint_gas_cylinder` (`serial_number`);

--
-- Indexes for table `maintenance_options`
--
ALTER TABLE `maintenance_options`
  ADD PRIMARY KEY (`option_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `deliveries`
--
ALTER TABLE `deliveries`
  MODIFY `delivery_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `delivery_staff`
--
ALTER TABLE `delivery_staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `gas_brands`
--
ALTER TABLE `gas_brands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `gas_locations`
--
ALTER TABLE `gas_locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `gas_sensor_logs`
--
ALTER TABLE `gas_sensor_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `gas_sizes`
--
ALTER TABLE `gas_sizes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `gas_statuses`
--
ALTER TABLE `gas_statuses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `gas_types`
--
ALTER TABLE `gas_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `maintenance`
--
ALTER TABLE `maintenance`
  MODIFY `maintenance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `maintenance_options`
--
ALTER TABLE `maintenance_options`
  MODIFY `option_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `maintenance`
--
ALTER TABLE `maintenance`
  ADD CONSTRAINT `fk_maint_gas_cylinder` FOREIGN KEY (`serial_number`) REFERENCES `gas_cylinder` (`serial_number`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `maintenance_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
