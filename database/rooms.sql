-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Mar 31, 2026 at 07:23 AM
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
-- Database: `hostel_lbscek`
--

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `hostel_id` int(11) NOT NULL,
  `room_number` varchar(10) DEFAULT NULL,
  `capacity` int(11) NOT NULL DEFAULT 2,
  `current_occupancy` int(11) DEFAULT 0,
  `status` enum('available','occupied','maintenance') DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `hostel_id`, `room_number`, `capacity`, `current_occupancy`, `status`) VALUES
(1408, 1, 'G01', 2, 0, 'available'),
(1409, 1, 'G02', 2, 0, 'available'),
(1410, 1, 'G03', 2, 0, 'available'),
(1411, 1, 'G04', 2, 0, 'available'),
(1412, 1, 'G05', 2, 0, 'available'),
(1413, 1, 'G06', 2, 0, 'available'),
(1414, 1, 'G07', 2, 0, 'available'),
(1415, 1, 'G08', 2, 0, 'available'),
(1416, 1, 'G09', 2, 0, 'available'),
(1417, 1, 'G10', 2, 0, 'available'),
(1418, 1, 'G11', 2, 0, 'available'),
(1419, 1, 'G12', 2, 0, 'available'),
(1420, 1, 'G13', 2, 0, 'available'),
(1421, 1, 'G14', 2, 0, 'available'),
(1422, 1, 'G15', 2, 0, 'available'),
(1423, 1, 'G16', 2, 0, 'available'),
(1424, 1, 'G17', 2, 0, 'available'),
(1425, 1, 'G18', 2, 0, 'available'),
(1426, 1, 'G19', 2, 0, 'available'),
(1427, 1, 'G20', 2, 0, 'available'),
(1428, 1, 'G21', 2, 0, 'available'),
(1429, 1, 'G22', 2, 0, 'available'),
(1430, 1, 'G23', 2, 0, 'available'),
(1431, 1, 'G24', 2, 0, 'available'),
(1432, 1, 'G25', 2, 0, 'available'),
(1439, 1, 'F01', 2, 0, 'available'),
(1440, 1, 'F02', 2, 0, 'available'),
(1441, 1, 'F03', 2, 0, 'available'),
(1442, 1, 'F04', 2, 0, 'available'),
(1443, 1, 'F05', 2, 0, 'available'),
(1444, 1, 'F06', 2, 0, 'available'),
(1445, 1, 'F07', 2, 0, 'available'),
(1446, 1, 'F08', 2, 0, 'available'),
(1447, 1, 'F09', 2, 0, 'available'),
(1448, 1, 'F10', 2, 0, 'available'),
(1449, 1, 'F11', 2, 0, 'available'),
(1450, 1, 'F12', 2, 0, 'available'),
(1451, 1, 'F13', 2, 0, 'available'),
(1452, 1, 'F14', 2, 0, 'available'),
(1453, 1, 'F15', 2, 0, 'available'),
(1454, 1, 'F16', 2, 0, 'available'),
(1455, 1, 'F17', 2, 0, 'available'),
(1456, 1, 'F18', 2, 0, 'available'),
(1457, 1, 'F19', 2, 0, 'available'),
(1458, 1, 'F20', 2, 0, 'available'),
(1459, 1, 'F21', 2, 0, 'available'),
(1460, 1, 'F22', 2, 0, 'available'),
(1461, 1, 'F23', 2, 0, 'available'),
(1462, 1, 'F24', 2, 0, 'available'),
(1463, 1, 'F25', 2, 0, 'available'),
(1464, 1, 'F26', 2, 0, 'available'),
(1465, 1, 'F27', 2, 0, 'available'),
(1466, 1, 'F28', 2, 0, 'available'),
(1467, 1, 'F29', 2, 0, 'available'),
(1468, 1, 'F30', 2, 0, 'available'),
(1469, 1, 'F31', 2, 0, 'available'),
(1470, 1, 'F32', 2, 0, 'available'),
(1471, 1, 'F33', 2, 0, 'available'),
(1472, 1, 'F34', 2, 0, 'available'),
(1473, 1, 'F35', 2, 0, 'available'),
(1474, 1, 'F36', 2, 0, 'available'),
(1475, 1, 'F37', 2, 0, 'available'),
(1476, 1, 'F38', 2, 0, 'available'),
(1477, 1, 'F39', 2, 0, 'available'),
(1478, 1, 'F40', 2, 0, 'available'),
(1479, 1, 'F41', 2, 0, 'available'),
(1480, 1, 'F42', 2, 0, 'available'),
(1481, 1, 'F43', 2, 0, 'available'),
(1482, 1, 'F44', 2, 0, 'available'),
(1483, 1, 'F45', 2, 0, 'available'),
(1484, 1, 'F46', 2, 0, 'available'),
(1485, 1, 'F47', 2, 0, 'available'),
(1486, 1, 'F48', 2, 0, 'available'),
(1487, 1, 'F49', 2, 0, 'available'),
(1488, 1, 'F50', 2, 0, 'available'),
(1489, 1, 'F51', 2, 0, 'available'),
(1490, 1, 'F52', 2, 0, 'available'),
(1491, 1, 'F53', 2, 0, 'available'),
(1502, 1, 'S01', 2, 0, 'available'),
(1503, 1, 'S02', 2, 0, 'available'),
(1504, 1, 'S03', 2, 0, 'available'),
(1505, 1, 'S04', 2, 0, 'available'),
(1506, 1, 'S05', 2, 0, 'available'),
(1507, 1, 'S06', 2, 0, 'available'),
(1508, 1, 'S07', 2, 0, 'available'),
(1509, 1, 'S08', 2, 0, 'available'),
(1510, 1, 'S09', 2, 0, 'available'),
(1511, 1, 'S10', 2, 0, 'available'),
(1512, 1, 'S11', 2, 0, 'available'),
(1513, 1, 'S12', 2, 0, 'available'),
(1514, 1, 'S13', 2, 0, 'available'),
(1515, 1, 'S14', 2, 0, 'available'),
(1516, 1, 'S15', 2, 0, 'available'),
(1517, 1, 'S16', 2, 1, 'available'),
(1518, 1, 'S17', 2, 2, 'available'),
(1519, 1, 'S18', 2, 0, 'available'),
(1520, 1, 'S19', 2, 0, 'available'),
(1521, 1, 'S20', 2, 0, 'available'),
(1522, 1, 'S21', 2, 0, 'available'),
(1523, 1, 'S22', 2, 0, 'available'),
(1524, 1, 'S23', 2, 0, 'available'),
(1525, 1, 'S24', 2, 0, 'available'),
(1526, 1, 'S25', 2, 0, 'available'),
(1527, 1, 'S26', 2, 0, 'available'),
(1528, 1, 'S27', 2, 0, 'available'),
(1529, 1, 'S28', 2, 0, 'available'),
(1530, 1, 'S29', 2, 0, 'available'),
(1531, 1, 'S30', 2, 0, 'available'),
(1532, 1, 'S31', 2, 0, 'available'),
(1533, 1, 'S32', 2, 0, 'available'),
(1534, 1, 'S33', 2, 0, 'available'),
(1535, 1, 'S34', 2, 0, 'available'),
(1536, 1, 'S35', 2, 0, 'available'),
(1537, 1, 'S36', 2, 0, 'available'),
(1538, 1, 'S37', 2, 0, 'available'),
(1539, 1, 'S38', 2, 0, 'available'),
(1540, 1, 'S39', 2, 0, 'available'),
(1541, 1, 'S40', 2, 0, 'available'),
(1542, 1, 'S41', 2, 0, 'available'),
(1543, 1, 'S42', 2, 0, 'available'),
(1544, 1, 'S43', 2, 0, 'available'),
(1545, 1, 'S44', 2, 0, 'available'),
(1546, 1, 'S45', 2, 0, 'available'),
(1547, 1, 'S46', 2, 0, 'available'),
(1548, 1, 'S47', 2, 0, 'available'),
(1549, 1, 'S48', 2, 0, 'available'),
(1550, 1, 'S49', 2, 0, 'available'),
(1551, 1, 'S50', 2, 2, 'available'),
(1552, 1, 'S51', 2, 0, 'available'),
(1553, 1, 'S52', 2, 0, 'available'),
(1554, 1, 'S53', 2, 0, 'available'),
(1555, 1, 'S54', 2, 0, 'available'),
(1556, 1, 'S55', 2, 0, 'available'),
(1557, 1, 'S56', 2, 0, 'available'),
(1558, 1, 'S57', 2, 0, 'available'),
(1559, 1, 'S58', 2, 0, 'available'),
(1560, 1, 'S59', 2, 0, 'available'),
(1561, 1, 'S60', 2, 0, 'available');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `hostel_id` (`hostel_id`,`room_number`),
  ADD UNIQUE KEY `room_number` (`room_number`),
  ADD UNIQUE KEY `room_number_2` (`room_number`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1565;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`hostel_id`) REFERENCES `hostels` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
