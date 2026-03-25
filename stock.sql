-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 25, 2026 at 04:46 AM
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
-- Database: `stock`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `Admin_ID` varchar(5) NOT NULL COMMENT 'รหัสผู้ดูแล',
  `Username` varchar(20) NOT NULL COMMENT 'ชื่อผู้ดูแล',
  `Password` varchar(20) NOT NULL COMMENT 'รหัสผ่าน',
  `Note` varchar(10) DEFAULT NULL COMMENT 'ชื่อ'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='ผู้ดูแล';

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`Admin_ID`, `Username`, `Password`, `Note`) VALUES
('1', 'admin', 'th670016', 'ครูโบว์'),
('2', 'admin', 'th670013', 'ครูมุก'),
('3', 'admin', 'th5620539', 'ครูบู'),
('4', 'admin', 'th680001', 'ครูตาล'),
('5', 'admin', 'admin', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `buy`
--

CREATE TABLE `buy` (
  `Buy_ID` int(5) NOT NULL COMMENT 'รหัสการซื้อ',
  `Buy_Date` varchar(16) NOT NULL COMMENT 'วัน/เวลาที่ซื้อ',
  `Item_ID` varchar(5) NOT NULL COMMENT 'รหัสพัสดุ',
  `Buy_Total` int(5) NOT NULL COMMENT 'จำนวนที่ซื้อ',
  `Officer` varchar(30) NOT NULL COMMENT 'ชื่อเจ้าหน้าที่'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='รายการซื้อ';

--
-- Dumping data for table `buy`
--

INSERT INTO `buy` (`Buy_ID`, `Buy_Date`, `Item_ID`, `Buy_Total`, `Officer`) VALUES
(2, '2025-12-23 09:30', 'P006', 15, 'ครูโบว์'),
(3, '2025-12-23 10:45', 'P027', 5, 'ครูโบว์'),
(4, '2025-12-23 13:20', 'P016', 5, 'ครูโบว์'),
(5, '2026-01-07 14:10', 'P001', 20, 'ครูโบว์'),
(6, '2026-01-25 15:30', 'P032', 10, 'ครูโบว์'),
(7, '2026-01-25 16:45', 'P037', 10, 'ครูโบว์'),
(8, '2026-01-28 08:50', 'P018', 10, 'ครูโบว์'),
(9, '2026-01-28 11:25', 'P001', 50, 'ครูโบว์'),
(10, '2026-01-30 09:10', 'P001', 20, 'ครูโบว์'),
(11, '2026-01-30 14:40', 'P006', 50, 'ครูโบว์'),
(12, '2026-02-09 11:08', 'P001', 50, 'ครูโบว์');

-- --------------------------------------------------------

--
-- Table structure for table `item`
--

CREATE TABLE `item` (
  `Item_ID` varchar(5) NOT NULL COMMENT 'รหัสพัสดุ',
  `Item_Name` varchar(30) NOT NULL COMMENT 'ชื่อพัสดุ',
  `Unit` varchar(20) NOT NULL COMMENT 'หน่วยนับ',
  `Item_Total` int(5) NOT NULL COMMENT 'ยอดคงเหลือ'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='พัสดุ';

--
-- Dumping data for table `item`
--

INSERT INTO `item` (`Item_ID`, `Item_Name`, `Unit`, `Item_Total`) VALUES
('P001', 'ดินสอ', 'แท่ง', 53),
('P002', 'ปากกา', 'แท่ง', 40),
('P003', 'ยางลบ', 'อัน', 50),
('P004', 'เทปกาว', 'ม้วน', 50),
('P005', 'สก็อตเทปใส', 'ม้วน', 50),
('P006', 'กระดาษ A4', 'รีม', 55),
('P007', 'กระดาษ F4', 'แผ่น', 50),
('P008', 'ปากกาไวท์บอร์ด', 'แท่ง', 50),
('P009', 'หมึกปากกา', 'ขวด', 50),
('P010', 'กระดาษสี', 'แผ่น', 30),
('P011', 'ถุงดำ', 'ใบ', 35),
('P012', 'เชือกฟาง', 'ม้วน', 50),
('P013', 'ลิ้นแฟ้ม', 'อัน', 50),
('P014', 'ลูกกลิ้งเคมี', 'อัน', 50),
('P015', 'แท่นติดสก็อตเทป', 'อัน', 50),
('P016', 'กระดาษ ต2ก', 'แผ่น', 55),
('P017', 'เยื่อกาว', 'แผ่น', 50),
('P018', 'ถ่าน 2A', 'ก้อน', 59),
('P019', 'สเปรย์ปรับอากาศ', 'กระป๋อง', 50),
('P020', 'ตลับหมึก Canon 810', 'ตลับ', 50),
('P021', 'น้ำหมึก Epson', 'ขวด', 50),
('P022', 'กระดาษบรูฟ', 'แผ่น', 50),
('P023', 'คลิปหนีบกระดาษ', 'กล่อง', 50),
('P024', 'สันรูด', 'อัน', 50),
('P025', 'กาวสองหน้า', 'ม้วน', 50),
('P026', 'กาว UHU', 'แท่ง', 50),
('P027', 'กรรไกร', 'อัน', 49),
('P028', 'ไม้บรรทัด', 'อัน', 50),
('P029', 'แฟ้มเอกสาร', 'เล่ม', 50),
('P030', 'ปากกาลบคำผิด', 'แท่ง', 45),
('P031', 'แม็กเย็บกระดาษ', 'อัน', 50),
('P032', 'กาว TOA', 'ขวด', 56),
('P033', 'คลิปหนีบกระดาษ ใหญ่', 'กล่อง', 49),
('P034', 'สก็อตเทป', 'ม้วน', 50),
('P035', 'สก็อตเทปผ้า', 'ม้วน', 50),
('P036', 'ใบมีดคัตเตอร์', 'กล่อง', 50),
('P037', 'กระดาษโฟโต้', 'แผ่น', 60),
('P038', 'ลูกแม็กซ์', 'กล่อง', 50),
('P039', 'พลาสติกเคลือบ', 'แผ่น', 50),
('P040', 'หมึกเติมไวท์บอร์ด สีแดง', 'ขวด', 50),
('P041', 'หมึกเติมไวท์บอร์ด สีน้ำเงิน', 'ขวด', 50),
('P042', 'กระดาษการ์ด', 'แผ่น', 49),
('P043', 'ซองน้ำตาล A4', 'แผ่น', 50),
('P044', 'ซองน้ำตาล A4 ขยายข้าง', 'แผ่น', 50),
('P045', 'ลวดเสียบกระดาษ', 'กล่อง', 50),
('P046', 'ถุงดำดำ', 'แผ่น', 20);

-- --------------------------------------------------------

--
-- Table structure for table `request`
--

CREATE TABLE `request` (
  `Req_ID` int(5) NOT NULL COMMENT 'รหัสการเบิก',
  `Req_Name` varchar(50) NOT NULL COMMENT 'ชื่อผู้ขอเบิก',
  `Req_Phone` varchar(10) DEFAULT NULL COMMENT 'เบอร์โทรผู้ขอเบิก',
  `Req_Date` varchar(16) NOT NULL COMMENT 'วัน/เวลาที่เบิก',
  `Item_ID` varchar(5) NOT NULL COMMENT 'รหัสพัสดุ',
  `Req_Total` int(5) NOT NULL COMMENT 'จำนวนที่เบิก',
  `Officer` varchar(30) NOT NULL COMMENT 'ชื่อเจ้าหน้าที่',
  `Department` varchar(30) NOT NULL COMMENT 'สาขา'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='รายการเบิก';

--
-- Dumping data for table `request`
--

INSERT INTO `request` (`Req_ID`, `Req_Name`, `Req_Phone`, `Req_Date`, `Item_ID`, `Req_Total`, `Officer`, `Department`) VALUES
(4, 'พัสสน', '0812345678', '2025-12-23 08:30', 'P030', 5, 'th000001', 'การท่องเที่ยว'),
(5, 'วีรวัฒน์', '0823456789', '2025-12-23 09:15', 'P032', 3, 'ครูโบว์', 'ภาษาต่างประเทศธุรกิจบริการ'),
(7, 'ครูสร้อย', '0845678901', '2026-01-05 11:45', 'P006', 5, 'ครูโบว์', 'การตลาด'),
(8, 'ครูเจ๋ง', '0856789012', '2026-01-25 13:10', 'P011', 5, 'ครูโบว์', 'การตลาด'),
(9, 'ครูภา', '0867890123', '2026-01-25 14:25', 'P011', 10, 'ครูโบว์', 'พัฒนากิจการนักเรียนนักศึกษา'),
(10, 'วีระ', '0878901234', '2026-01-27 15:40', 'P032', 1, 'ครูโบว์', 'การตลาด'),
(11, 'พัสสะนะ', '0889012345', '2026-01-28 16:15', 'P042', 1, 'ครูโบว์', 'สามัญ'),
(12, 'ครูอาร์', '0890123456', '2026-01-28 08:45', 'P018', 1, 'ครูโบว์', 'เทคโนโลยีธุรกิจดิจิทัล'),
(13, 'พัสสะนะ', '0901234567', '2026-01-28 10:10', 'P001', 20, 'ครูโบว์', 'การจัดการ'),
(14, 'ครูอาร์', '0912345678', '2026-01-30 11:30', 'P001', 50, 'ครูโบว์', 'ดิจิทัลกราฟิก'),
(15, 'ครูมุก', '0923456789', '2026-01-30 14:50', 'P001', 20, 'ครูโบว์', 'สามัญ'),
(16, 'ครูโก้', '0934567890', '2026-01-30 15:20', 'P006', 10, 'ครูโบว์', 'สามัญ'),
(17, 'ครูอาร์', '0945678901', '2026-01-30 16:35', 'P001', 1, 'ครูโบว์', 'เทคโนโลยีธุรกิจดิจิทัล'),
(18, 'ครูอาร์', '0956789012', '2026-01-30 09:05', 'P010', 20, 'ครูโบว์', 'ภาษาต่างประเทศธุรกิจบริการ'),
(21, 'ครูสัน', '0856312478', '2026-02-04 09:53', 'P033', 1, 'ครูโบว์', 'สามัญ'),
(22, 'ครูอิส', '0141111111', '2026-02-04 14:26', 'P006', 2, 'ครูโบว์', 'สามัญ'),
(23, 'พัสสน', '0226555558', '2026-02-04 14:45', 'P001', 10, 'ครูโบว์', 'การจัดการ'),
(28, 'วาดา', '0785786786', '2026-02-06 14:07', 'P006', 2, 'ครูโบว์', 'งบประมาณและการเงิน'),
(32, 'ณัฐสิทธิ์', '0624230924', '2026-02-09 10:56', 'P001', 2, 'ครูโบว์', 'เทคโนโลยีธุรกิจดิจิทัล'),
(33, 'ณัฐสิทธิ์', '0624230924', '2026-02-09 10:56', 'P002', 10, 'ครูโบว์', 'เทคโนโลยีธุรกิจดิจิทัล'),
(34, 'อังสนา', '0868960060', '2026-02-09 20:54', 'P001', 5, 'ครูโบว์', 'สามัญ'),
(35, 'อังสนา', '0868960060', '2026-02-09 20:54', 'P027', 5, 'ครูโบว์', 'ดิจิทัลกราฟิก'),
(37, 'ปรมา', '0865414615', '2026-02-11 21:39', 'P027', 1, 'ครูโบว์', 'เทคโนโลยีธุรกิจดิจิทัล');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`Admin_ID`);

--
-- Indexes for table `buy`
--
ALTER TABLE `buy`
  ADD PRIMARY KEY (`Buy_ID`),
  ADD KEY `Item_ID` (`Item_ID`);

--
-- Indexes for table `item`
--
ALTER TABLE `item`
  ADD PRIMARY KEY (`Item_ID`);

--
-- Indexes for table `request`
--
ALTER TABLE `request`
  ADD PRIMARY KEY (`Req_ID`),
  ADD KEY `Item_ID` (`Item_ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `buy`
--
ALTER TABLE `buy`
  MODIFY `Buy_ID` int(5) NOT NULL AUTO_INCREMENT COMMENT 'รหัสการซื้อ', AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `request`
--
ALTER TABLE `request`
  MODIFY `Req_ID` int(5) NOT NULL AUTO_INCREMENT COMMENT 'รหัสการเบิก', AUTO_INCREMENT=38;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `buy`
--
ALTER TABLE `buy`
  ADD CONSTRAINT `buy_ibfk_2` FOREIGN KEY (`Item_ID`) REFERENCES `item` (`Item_ID`);

--
-- Constraints for table `request`
--
ALTER TABLE `request`
  ADD CONSTRAINT `request_ibfk_2` FOREIGN KEY (`Item_ID`) REFERENCES `item` (`Item_ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
