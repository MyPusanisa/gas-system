-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Aug 29, 2026 at 07:55 PM
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
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `map_pin` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `name`, `phone`, `address`, `map_pin`) VALUES
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
(30, 'การ์น', '0866515916', 'กรุงเทพ', '13.546541'),
(34, 'ฟ้า', '0854563254', 'เพชรหึงษ์ 2', ''),
(35, 'ฟ้าลดา', '0859874563', 'เพชรหึงษ์ 3', ''),
(36, 'pp', '0898745656', 'ล็อกเอาต์ แล้วล็อกอินด้วยบัญชี Admin', '');

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
  `customer_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `map_pin` varchar(255) DEFAULT NULL,
  `brand` varchar(50) DEFAULT NULL,
  `gas_type` varchar(50) DEFAULT 'LPG',
  `size` varchar(20) DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `status` enum('pending','delivering','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deliveries`
--

INSERT INTO `deliveries` (`delivery_id`, `customer_name`, `phone`, `address`, `map_pin`, `brand`, `gas_type`, `size`, `staff_id`, `status`, `created_at`) VALUES
(1, 'พำะำพะ', '0855225545', 'หฟำพั', '', 'วันอังคาร', 'N2O', '36 กก.', 1, 'pending', '2026-08-10 09:52:52');

-- --------------------------------------------------------

--
-- Table structure for table `delivery`
--

CREATE TABLE `delivery` (
  `delivery_id` int(11) NOT NULL,
  `cylinder_id` varchar(50) DEFAULT NULL,
  `received_cylinder_id` varchar(50) DEFAULT NULL,
  `customer_id` int(11) NOT NULL,
  `req_brand` varchar(50) DEFAULT NULL,
  `req_gas_type` varchar(50) DEFAULT NULL,
  `req_size` varchar(50) DEFAULT NULL,
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

INSERT INTO `delivery` (`delivery_id`, `cylinder_id`, `received_cylinder_id`, `customer_id`, `req_brand`, `req_gas_type`, `req_size`, `admin_id`, `staff_id`, `delivery_date`, `return_date`, `status`, `accepted_at`, `started_at`, `completed_at`, `created_at`, `delivered_map_pin`, `proof_image_path`, `delivered_at`, `gas_type`, `brand`, `size`) VALUES
(33, 'CY001', NULL, 30, NULL, NULL, NULL, 1, 1, '2026-05-19', NULL, 'pending', NULL, NULL, NULL, '2026-05-19 09:10:12', NULL, NULL, NULL, 'LPG', 'PTT', '15kg'),
(37, NULL, NULL, 34, 'วันพฤ', 'NA', '99 kg', 1, 1, '2026-08-10', NULL, 'pending', NULL, NULL, NULL, '2026-08-10 08:03:59', NULL, NULL, NULL, 'NA', 'วันพฤ', '99 kg'),
(38, NULL, NULL, 35, 'วันพฤ', 'NA', '99 kg', 1, 1, '2026-08-10', NULL, 'pending', NULL, NULL, NULL, '2026-08-10 08:05:35', NULL, NULL, NULL, 'NA', 'วันพฤ', '99 kg'),
(39, NULL, NULL, 36, 'วันพฤ', 'NA', '99 kg', 1, 1, '2026-08-10', NULL, 'pending', NULL, NULL, NULL, '2026-08-10 09:22:26', NULL, NULL, NULL, 'NA', 'วันพฤ', '99 kg');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_orders`
--

CREATE TABLE `delivery_orders` (
  `id` int(11) NOT NULL,
  `serial_number` varchar(50) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `delivery_status` enum('รอดำเนินการ','กำลังจัดส่ง','ส่งสำเร็จ','ยกเลิก') DEFAULT 'รอดำเนินการ',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 'สมชาย ใจดี', '0812345678', 'เชียงใหม่', 'somchai', '12345', 'active', 0),
(2, 'สมหญิง พูนสุข', '0898765432', 'ลำปาง', 'somying', '11111', 'active', 0),
(3, 'วีระชัย ทองคำ', '0821112233', 'กรุงเทพ', 'weerachai', '9999', 'inactive', 0),
(4, 'ภูษณิศา จันทร์นวล', '0822153045', '26/39 ม.9 เพชรหงษ์ 2 ต.ทรงคนอง', 'pimlypire', '9632', 'active', 0),
(5, 'ยย', '0822222222', 'รจบรสนร้ส', 'ยนวีนยว', '9652', 'active', 0),
(6, 'yuu', '0825632541', 'sshjstyafbDFhatja', 'aeah', '96396', 'active', 0),
(7, 'พหฟเ', '0856963254', 'พ้พหเืหดเเเ้ำะ้ฟกดฟกดเำพำพะะ พพะ พำะ ำำพะำพะ ๆำพ ะำๆพะ ๆำพ ะ', 'พเหพ', '3698', 'active', 0);

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
(8, 'จีปี้'),
(1, 'ปตท.'),
(5, 'พีที (PT Gas)'),
(6, 'พีเอพี'),
(4, 'ยูนิคแก๊ส'),
(7, 'วันจันทร์'),
(14, 'วันพฤ'),
(13, 'วันพุธ'),
(12, 'วันอังคาร'),
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
('101', 'SN-3001', 'ในคลัง', '15kg', NULL, NULL, NULL, 'PTT', NULL, NULL, '2027-08-10', NULL, 'คลังสินค้า A', '2026-08-10 14:16:06', '2026-08-10 15:07:14'),
('102', 'SN-3002', 'ใช้งานอยู่', '15kg', NULL, NULL, NULL, 'World Gas', NULL, NULL, '2027-08-11', NULL, 'ร้านค้าสาขา 1', '2026-08-10 14:16:06', '2026-08-11 05:35:44'),
('103', 'SN-3003', 'ในคลัง', '11.5kg', NULL, NULL, NULL, 'Siam Gas', NULL, NULL, '2026-08-01', NULL, 'คลังสินค้า B', '2026-08-10 14:16:06', '2026-08-10 14:29:29'),
('104', 'SN-3004', 'ในคลัง', '4kg', NULL, NULL, NULL, 'Unique Gas', NULL, NULL, '2026-08-01', NULL, 'คลังสินค้า A', '2026-08-10 14:16:06', '2026-08-10 14:29:29'),
('asd', 'asd', 'ในคลัง', '11.5 กก.', '2026-05-04', '2036-05-04', 'https://yourdomain.com/cylinder/asd', 'World Gas', 'LPG', '2026-05-17', '2027-08-29', '2026-05-19', 'คลัง', '2026-05-17 08:14:26', '2026-08-29 16:45:03'),
('CY001', 'SN-1001', 'กำลังจัดส่ง', '15kg', '2022-01-10', '2032-01-10', 'QR001', 'PTT', 'LPG', '2025-05-01', '2026-08-01', '2025-01-15', 'รอจัดส่ง', '2026-05-08 20:24:47', '2026-08-10 14:29:29'),
('CY002', 'SN-1002', 'ในคลัง', '48kg', '2021-03-12', '2031-03-12', 'QR002', 'WorldGas', 'LPG', '2025-04-15', '2026-08-01', '2025-02-10', 'คลัง B', '2026-05-08 20:24:47', '2026-08-10 14:29:29'),
('CYL-8001', 'SN-8001', 'ในคลัง', '15kg', NULL, NULL, NULL, 'PTT', NULL, NULL, '2026-08-01', NULL, 'คลังสินค้า A', '2026-08-10 14:19:20', '2026-08-10 14:29:29'),
('CYL-8002', 'SN-8002', 'ใช้งานอยู่', '15kg', NULL, NULL, NULL, 'World Gas', NULL, NULL, '2026-08-01', NULL, 'ร้านค้าสาขา 1', '2026-08-10 14:19:20', '2026-08-10 14:29:29'),
('CYL-8003', 'SN-8003', 'ในคลัง', '11.5kg', NULL, NULL, NULL, 'Siam Gas', NULL, NULL, '2026-08-01', NULL, 'คลังสินค้า B', '2026-08-10 14:19:20', '2026-08-10 14:29:29'),
('CYL-8004', 'SN-8004', 'ในคลัง', '4kg', NULL, NULL, NULL, 'Unique Gas', NULL, NULL, '2026-08-01', NULL, 'คลังสินค้า A', '2026-08-10 14:19:20', '2026-08-10 14:29:29'),
('dsdhywrt', 'dsdhywrt', 'ในคลัง', '99 kg', '2026-08-01', '2036-08-01', 'http://192.168.1.176:5173/cylinder/dsdhywrt', 'วันพฤ', 'NA', '2026-08-08', '2027-08-29', NULL, 'เชียงราย', '2026-08-08 17:49:14', '2026-08-29 16:45:03'),
('etyketk', 'etyketk', 'ปกติ', '36 กก.', '2026-08-01', '2036-08-01', 'http://192.168.1.176:5173/cylinder/etyketk', 'วันอังคาร', 'N2O', '2026-08-08', '2027-08-29', NULL, 'พะเยา', '2026-08-08 17:50:42', '2026-08-29 16:45:03'),
('serhths', 'serhths', 'ในคลัง', '36 kg.', '2026-08-01', '2036-08-01', 'http://192.168.1.176:5173/cylinder/serhths', 'วันจันทร์', 'LPG', '2026-08-08', '2027-08-29', NULL, 'พะเยา', '2026-08-08 16:19:18', '2026-08-29 16:45:03');

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
(2, 'เชียงราย');

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
(2, '15 kg'),
(7, '36 kg.'),
(8, '36 กก.'),
(3, '48 kg'),
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
(1, 'LPG');

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
(33, 'asd', '2026-08-29', 'สภาพสมบูรณ์ พร้อมใช้งาน', 'ตรวจสภาพ', 'ผ่าน', 'ใช้งานต่อได้ (ปกติ)', NULL, NULL, NULL, '2026-08-29 23:45:03');

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
  ADD PRIMARY KEY (`customer_id`);

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
-- Indexes for table `delivery_orders`
--
ALTER TABLE `delivery_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_del_orders_cylinder` (`serial_number`),
  ADD KEY `fk_del_orders_staff` (`staff_id`);

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
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `deliveries`
--
ALTER TABLE `deliveries`
  MODIFY `delivery_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `delivery`
--
ALTER TABLE `delivery`
  MODIFY `delivery_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `delivery_orders`
--
ALTER TABLE `delivery_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `delivery_staff`
--
ALTER TABLE `delivery_staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `gas_brands`
--
ALTER TABLE `gas_brands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `gas_locations`
--
ALTER TABLE `gas_locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `gas_sizes`
--
ALTER TABLE `gas_sizes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `gas_statuses`
--
ALTER TABLE `gas_statuses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `gas_types`
--
ALTER TABLE `gas_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `maintenance`
--
ALTER TABLE `maintenance`
  MODIFY `maintenance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

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
-- Constraints for table `delivery`
--
ALTER TABLE `delivery`
  ADD CONSTRAINT `fk_delivery_admin` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`),
  ADD CONSTRAINT `fk_delivery_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`),
  ADD CONSTRAINT `fk_delivery_received_cylinder` FOREIGN KEY (`received_cylinder_id`) REFERENCES `gas_cylinder` (`cylinder_id`),
  ADD CONSTRAINT `fk_delivery_staff` FOREIGN KEY (`staff_id`) REFERENCES `delivery_staff` (`staff_id`);

--
-- Constraints for table `delivery_orders`
--
ALTER TABLE `delivery_orders`
  ADD CONSTRAINT `fk_del_orders_cylinder` FOREIGN KEY (`serial_number`) REFERENCES `cylinders` (`serial_number`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_del_orders_staff` FOREIGN KEY (`staff_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

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
