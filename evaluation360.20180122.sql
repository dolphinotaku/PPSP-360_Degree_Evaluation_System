-- phpMyAdmin SQL Dump
-- version 4.5.1
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Jan 22, 2018 at 04:39 PM
-- Server version: 10.1.9-MariaDB
-- PHP Version: 5.5.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `evaluation360`
--

-- --------------------------------------------------------

--
-- Table structure for table `department`
--

DROP TABLE IF EXISTS `department`;
CREATE TABLE IF NOT EXISTS `department` (
  `DepartmentCode` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `Description` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`DepartmentCode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `department`
--

INSERT INTO `department` (`DepartmentCode`, `Description`) VALUES
('AC', 'Finance'),
('DVL', 'Development'),
('ENG', 'Engineering'),
('EXO', 'Executive Office'),
('FO', 'Front Office'),
('HR', 'Human Resources'),
('IT', 'Information Technology'),
('MGT', 'Management'),
('MKT', 'Marketing'),
('PCM', 'Procurement'),
('PR', 'Public Relations'),
('PRJ', 'Project'),
('PROP', 'Property');

-- --------------------------------------------------------

--
-- Table structure for table `evaluation`
--

DROP TABLE IF EXISTS `evaluation`;
CREATE TABLE IF NOT EXISTS `evaluation` (
  `EvaluationCode` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `Description` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `StartDate` date DEFAULT NULL,
  `EndDate` date DEFAULT NULL,
  `ProbationEndDate` date DEFAULT NULL,
  `ReminderStartDate` date DEFAULT NULL,
  `ReminderEndDate` date DEFAULT NULL,
  PRIMARY KEY (`EvaluationCode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `evaluation`
--

INSERT INTO `evaluation` (`EvaluationCode`, `Description`, `StartDate`, `EndDate`, `ProbationEndDate`, `ReminderStartDate`, `ReminderEndDate`) VALUES
('201612', 'second review in the 2016', '2016-12-01', '2016-12-31', '2016-12-01', '2016-12-15', '2016-12-31'),
('201706', 'first review in the 2017', '2017-03-01', '2017-03-31', '2017-03-01', '2017-03-25', '2017-03-31'),
('201712', 'second review in 2017', '2017-12-01', '2017-12-31', '2017-11-01', '2017-12-25', '2017-12-31');

-- --------------------------------------------------------

--
-- Table structure for table `evaproposal`
--

DROP TABLE IF EXISTS `evaproposal`;
CREATE TABLE IF NOT EXISTS `evaproposal` (
  `EvaProposalID` int(10) NOT NULL AUTO_INCREMENT,
  `Evaluatee` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `Evaluator` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `EvaluationCode` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `EvaTypeCode` char(1) COLLATE utf8_unicode_ci NOT NULL,
  `EvaProQtnStatusCode` char(1) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`EvaProposalID`),
  KEY `FKEvaProposa728857` (`Evaluatee`),
  KEY `FKEvaProposa729180` (`Evaluator`),
  KEY `FKEvaProposa584451` (`EvaluationCode`),
  KEY `FKEvaProposa783043` (`EvaTypeCode`),
  KEY `EvaProQtnStatusCode` (`EvaProQtnStatusCode`)
) ENGINE=InnoDB AUTO_INCREMENT=556 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `evaproposal`
--

INSERT INTO `evaproposal` (`EvaProposalID`, `Evaluatee`, `Evaluator`, `EvaluationCode`, `EvaTypeCode`, `EvaProQtnStatusCode`) VALUES
(263, 'GD00305', 'GD00305', '201612', 'S', 'S'),
(264, 'GD00305', 'GD00301', '201612', 'B', 'S'),
(265, 'GD00305', 'GD00312', '201612', 'C', 'S'),
(266, 'GD00305', 'GD00318', '201612', 'C', 'S'),
(500, 'GD00301', 'GD00301', '201706', 'S', 'I'),
(501, 'GD00301', 'GD00302', '201706', 'C', 'I'),
(502, 'GD00301', 'GD00304', '201706', 'C', 'I'),
(503, 'GD00301', 'GD00305', '201706', 'C', 'I'),
(504, 'GD00301', 'GD00311', '201706', 'C', 'I'),
(505, 'GD00302', 'GD00302', '201706', 'S', 'I'),
(506, 'GD00302', 'GD00301', '201706', 'B', 'I'),
(507, 'GD00302', 'GD00310', '201706', 'C', 'I'),
(508, 'GD00302', 'GD00314', '201706', 'C', 'I'),
(509, 'GD00303', 'GD00303', '201706', 'S', 'I'),
(510, 'GD00303', 'GD00308', '201706', 'B', 'I'),
(511, 'GD00304', 'GD00304', '201706', 'S', 'I'),
(512, 'GD00304', 'GD00301', '201706', 'B', 'I'),
(513, 'GD00305', 'GD00305', '201706', 'S', 'I'),
(514, 'GD00305', 'GD00301', '201706', 'B', 'I'),
(515, 'GD00305', 'GD00312', '201706', 'C', 'I'),
(516, 'GD00305', 'GD00318', '201706', 'C', 'I'),
(517, 'GD00306', 'GD00306', '201706', 'S', 'I'),
(518, 'GD00306', 'GD00315', '201706', 'B', 'I'),
(519, 'GD00307', 'GD00307', '201706', 'S', 'I'),
(520, 'GD00307', 'GD00315', '201706', 'B', 'I'),
(521, 'GD00308', 'GD00308', '201706', 'S', 'I'),
(522, 'GD00308', 'GD00310', '201706', 'B', 'I'),
(523, 'GD00308', 'GD00303', '201706', 'C', 'I'),
(524, 'GD00308', 'GD00316', '201706', 'C', 'I'),
(525, 'GD00308', 'GD00317', '201706', 'C', 'I'),
(526, 'GD00309', 'GD00309', '201706', 'S', 'I'),
(527, 'GD00309', 'GD00311', '201706', 'B', 'I'),
(528, 'GD00309', 'GD00315', '201706', 'C', 'I'),
(529, 'GD00309', 'GD00320', '201706', 'C', 'I'),
(530, 'GD00310', 'GD00310', '201706', 'S', 'I'),
(531, 'GD00310', 'GD00302', '201706', 'B', 'I'),
(532, 'GD00310', 'GD00308', '201706', 'C', 'I'),
(533, 'GD00311', 'GD00311', '201706', 'S', 'I'),
(534, 'GD00311', 'GD00301', '201706', 'B', 'I'),
(535, 'GD00311', 'GD00309', '201706', 'C', 'I'),
(536, 'GD00312', 'GD00312', '201706', 'S', 'I'),
(537, 'GD00312', 'GD00305', '201706', 'B', 'I'),
(538, 'GD00314', 'GD00314', '201706', 'S', 'I'),
(539, 'GD00314', 'GD00302', '201706', 'B', 'I'),
(540, 'GD00315', 'GD00315', '201706', 'S', 'I'),
(541, 'GD00315', 'GD00309', '201706', 'B', 'I'),
(542, 'GD00315', 'GD00306', '201706', 'C', 'I'),
(543, 'GD00315', 'GD00307', '201706', 'C', 'I'),
(544, 'GD00315', 'GD00319', '201706', 'C', 'I'),
(545, 'GD00316', 'GD00316', '201706', 'S', 'I'),
(546, 'GD00316', 'GD00308', '201706', 'B', 'I'),
(547, 'GD00317', 'GD00317', '201706', 'S', 'I'),
(548, 'GD00317', 'GD00308', '201706', 'B', 'I'),
(549, 'GD00318', 'GD00318', '201706', 'S', 'I'),
(550, 'GD00318', 'GD00305', '201706', 'B', 'S'),
(551, 'GD00319', 'GD00319', '201706', 'S', 'I'),
(552, 'GD00319', 'GD00315', '201706', 'B', 'I'),
(553, 'GD00320', 'GD00320', '201706', 'S', 'I'),
(554, 'GD00320', 'GD00309', '201706', 'B', 'I'),
(555, 'TESTING', 'TESTING', '201706', 'S', 'I');

-- --------------------------------------------------------

--
-- Table structure for table `evaproqtnstatus`
--

DROP TABLE IF EXISTS `evaproqtnstatus`;
CREATE TABLE IF NOT EXISTS `evaproqtnstatus` (
  `EvaProQtnStatusCode` char(1) COLLATE utf8_unicode_ci NOT NULL,
  `Description` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`EvaProQtnStatusCode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `evaproqtnstatus`
--

INSERT INTO `evaproqtnstatus` (`EvaProQtnStatusCode`, `Description`) VALUES
('D', 'Drafted'),
('I', 'Incomplete'),
('S', 'Submitted');

-- --------------------------------------------------------

--
-- Table structure for table `evatype`
--

DROP TABLE IF EXISTS `evatype`;
CREATE TABLE IF NOT EXISTS `evatype` (
  `EvaTypeCode` char(1) COLLATE utf8_unicode_ci NOT NULL,
  `Description` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`EvaTypeCode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `evatype`
--

INSERT INTO `evatype` (`EvaTypeCode`, `Description`) VALUES
('B', 'Boss'),
('C', 'Collaborator'),
('E', 'External Party'),
('I', 'Individual'),
('S', 'Subordinate');

-- --------------------------------------------------------

--
-- Table structure for table `position`
--

DROP TABLE IF EXISTS `position`;
CREATE TABLE IF NOT EXISTS `position` (
  `PositionCode` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `Description` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`PositionCode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `position`
--

INSERT INTO `position` (`PositionCode`, `Description`) VALUES
('AACCT', 'Assistant Accountant'),
('ACCLK', 'Accounts Clerk'),
('ACMGR', 'Accounting Manager'),
('ADMAST', 'Administration Assistant'),
('ADMOFR', 'Executive Asst & Admin Officer'),
('ALYPROG', 'Analyst Programmer'),
('AMAH', 'Amah'),
('AMKTMGR', 'Assistant Marketing Manager'),
('ASTTOMD', 'Assistant to Managing Director'),
('COMMGR', 'Communication Manager'),
('DRIVER', 'Driver'),
('EXEADM', 'Executive Administrator'),
('FAMGR', 'Finance & Admin Manager'),
('FNMGR', 'Finance Manager'),
('HROFR', 'Personal Asst & HR Officer'),
('MECOR', 'Merchandising Coordinator'),
('MEMGR', 'Merchandising Manager'),
('MERTCOR', 'Merchandising & Retail Coordinator'),
('MGTTEE', 'Management Trainee'),
('MKMEDIR', 'Marketing & Merchandising Director'),
('MKTAST', 'Marketing Assistant'),
('MKTEXE', 'Marketing Executive'),
('OFCAST', 'Office Assistant'),
('PAOFR', 'Personnel & Administration Officer'),
('PREDT', 'President'),
('PROMOTER', 'Promoter'),
('RECEPST', 'Receptionist'),
('RTLCOR', 'Retail Coordinator'),
('RTLMGR', 'Retail Manager'),
('RTMEREXE', 'Retail & Merchandising Executive'),
('RTOPNEXE', 'Retail Operation Executive'),
('RTOPNMGR', 'Retail Operation Manager'),
('SACCT', 'Senior Accountant'),
('SALCOR', 'Sales Coordinator'),
('SALEXE', 'Sales Executive'),
('SALMGR', 'Sales Manager'),
('SALSUP', 'Sales Supervisor'),
('SAST', 'Sales Assistant'),
('SAST-PT', 'Sales Assistant (Part Time)'),
('SHOPMGR', 'Shop Manager'),
('SHPCLK', 'Shipping Clerk'),
('SHPOFR', 'Shipping Officer'),
('SMKTEXE', 'Senior Marketing Executive'),
('SRSAL', 'Senior Sales'),
('SRSALEXE', 'Senior Sales Executive'),
('SSHPCLK', 'Senior Shipping Clerk'),
('SSUP', 'Sales Supervisor'),
('WSMGR', 'Wholesale Manager');

-- --------------------------------------------------------

--
-- Table structure for table `question`
--

DROP TABLE IF EXISTS `question`;
CREATE TABLE IF NOT EXISTS `question` (
  `QuestionID` int(10) NOT NULL AUTO_INCREMENT,
  `QuestionnaireID` int(10) NOT NULL,
  `Question` varchar(1000) COLLATE utf8_unicode_ci NOT NULL,
  `Type` char(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `DisplaySequence` int(1) DEFAULT NULL,
  PRIMARY KEY (`QuestionID`),
  KEY `FKQuestion232811` (`QuestionnaireID`)
) ENGINE=InnoDB AUTO_INCREMENT=82 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `question`
--

INSERT INTO `question` (`QuestionID`, `QuestionnaireID`, `Question`, `Type`, `DisplaySequence`) VALUES
(1, 1, 'Consider the degree to which an activity is completed or a result produced, at the earliest time desirable from the standpoints of corrdinating with the outputs of others, maximizing the time available for other activities.', 'M', NULL),
(2, 1, 'Consider neatness, accuracy, and dependability of results regardless of volume.', 'M', NULL),
(3, 1, 'Consider the volume of work produced under normal conditions. Disregard errors.', 'M', NULL),
(4, 1, 'Consider the degree to which you carry out a job function without either having to request supervisory assistance or requiring supervisory intervention.', 'M', NULL),
(5, 1, 'Consider the degree to which you promote feelings of self-esteem, goodwill, and cooperativeness among co-workers and leaders.', 'M', NULL),
(6, 1, 'Strengths (Please state 2-4 of the evaluatee''s strength areas)', 'O', NULL),
(7, 1, 'Area(s) for Improvement (Please state the main area(s) that you would like the evaluatee to improve)', 'O', NULL),
(66, 2, 'Consider the degree to which an activity is completed or a result produced, at the earliest time desirable from the standpoints of corrdinating with the outputs of others, maximizing the time available for other activities.', 'M', NULL),
(67, 2, 'Consider neatness, accuracy, and dependability of results regardless of volume.', 'M', NULL),
(68, 2, 'Consider the volume of work produced under normal conditions. Disregard errors.', 'M', NULL),
(69, 2, 'Consider the degree to which you carry out a job function without either having to request supervisory assistance or requiring supervisory intervention.', 'M', NULL),
(70, 2, 'Consider the degree to which you promote feelings of self-esteem, goodwill, and cooperativeness among co-workers and leaders.', 'M', NULL),
(71, 2, 'Strengths (Please state 2-4 of the evaluatee''s strength areas)', 'O', NULL),
(72, 2, 'Area(s) for Improvement (Please state the main area(s) that you would like the evaluatee to improve)', 'O', NULL),
(76, 3, 'R M Q1', 'M', 1),
(77, 3, 'R M Q2', 'M', 2),
(78, 3, 'R M Q3 Test', 'M', 3),
(79, 3, 'O E Qtn 1', 'O', 1),
(80, 3, 'O E Qtn 2', 'O', 2),
(81, 3, 'O E Qtn 3', 'O', 3);

-- --------------------------------------------------------

--
-- Table structure for table `questionnaire`
--

DROP TABLE IF EXISTS `questionnaire`;
CREATE TABLE IF NOT EXISTS `questionnaire` (
  `QuestionnaireID` int(10) NOT NULL AUTO_INCREMENT,
  `EvaluationCode` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `Description` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`QuestionnaireID`),
  KEY `FKQuestionna469445` (`EvaluationCode`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `questionnaire`
--

INSERT INTO `questionnaire` (`QuestionnaireID`, `EvaluationCode`, `Description`) VALUES
(1, '201612', 'General Questionnaire for second review in the 2016.'),
(2, '201706', 'General Questionnaire for first review in the 2017'),
(3, '201712', 'General Questionnaire for second review in the 2017');

-- --------------------------------------------------------

--
-- Table structure for table `questionnaireresult`
--

DROP TABLE IF EXISTS `questionnaireresult`;
CREATE TABLE IF NOT EXISTS `questionnaireresult` (
  `QtnResultID` int(10) NOT NULL AUTO_INCREMENT,
  `EvaProposalID` int(10) NOT NULL,
  `QuestionID` int(10) NOT NULL,
  `Result` varchar(500) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`QtnResultID`),
  KEY `FKQuestionna228062` (`EvaProposalID`),
  KEY `FKQuestionna772266` (`QuestionID`)
) ENGINE=InnoDB AUTO_INCREMENT=177 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `questionnaireresult`
--

INSERT INTO `questionnaireresult` (`QtnResultID`, `EvaProposalID`, `QuestionID`, `Result`) VALUES
(6, 264, 1, '2'),
(7, 264, 2, '2'),
(8, 264, 3, '3'),
(9, 264, 4, '4'),
(10, 264, 5, '2'),
(11, 265, 1, '5'),
(12, 265, 2, '4'),
(13, 265, 3, '4'),
(14, 265, 4, '4'),
(15, 265, 5, '5'),
(16, 266, 1, '5'),
(17, 266, 2, '4'),
(18, 266, 3, '3'),
(19, 266, 4, '5'),
(20, 266, 5, '4'),
(22, 264, 6, 'Stella is independent and able to handle things well on her own. She will not be panic in crisis but seeks solutions in first place. She shows a good role model as a senior teammate.'),
(23, 265, 6, 'Planning\r\n-Stella is very helpful in providing effective advice based on her past experience and end-in-mind to create the greatest synergy. She is good at organizing projects and consolidating parts from teammates, in a timely and quality manner.'),
(24, 266, 6, '-provide recommendation to team when teammates encounter obstacles\r\n-always follow timeline in work progress and do the best to meet the timeline'),
(26, 264, 7, '- As the team is growing larger, Stella can take up the role in supervising teammates and learn the skill of people management'),
(27, 265, 7, 'Communication\r\n-Seek first to understand, then to be understood, open herself more will be a great help for Stella to communicate smoothly with internal and external parties, so as to gain trust and respect from others.'),
(28, 266, 7, '-can be more proactive in assisting teammate'),
(156, 550, 66, '2'),
(157, 550, 67, '3'),
(158, 550, 68, '5'),
(159, 550, 69, '3'),
(160, 550, 70, '5'),
(161, 550, 71, 'you are super'),
(162, 550, 72, 'tested'),
(170, 263, 1, '5'),
(171, 263, 2, '5'),
(172, 263, 3, '4'),
(173, 263, 4, '4'),
(174, 263, 5, '5'),
(175, 263, 6, '- Proactive in understanding organization mission and target\r\n- Strong ownership on projects and strive for excellence despite of obstacles\r\n- Contribute ideas to the team, and willing to provide guidance to new join teammates\r\n- Willing to seek first to understand the perspectives of others'),
(176, 263, 7, '- Improve both people management and time management skill\r\n- Get more insights from competitors\r\n- Need to have better understanding on Beauty Service business model');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

DROP TABLE IF EXISTS `staff`;
CREATE TABLE IF NOT EXISTS `staff` (
  `StaffID` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `UserID` int(10) DEFAULT NULL,
  `FirstName` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `LastName` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ChineseName` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `DepartmentCode` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `StaffGradeCode` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `PositionCode` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `SupervisorID` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `EmploymentDate` date DEFAULT NULL,
  `ProbationEndDate` date DEFAULT NULL,
  `EmployStatus` char(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Gender` char(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `BirthDay` date DEFAULT NULL,
  `Email` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `TelNum` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `MobileNum` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`StaffID`),
  KEY `FKStaff586424` (`DepartmentCode`),
  KEY `FKStaff747337` (`StaffGradeCode`),
  KEY `FKStaff828323` (`PositionCode`),
  KEY `FKStaff66060` (`UserID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`StaffID`, `UserID`, `FirstName`, `LastName`, `ChineseName`, `DepartmentCode`, `StaffGradeCode`, `PositionCode`, `SupervisorID`, `EmploymentDate`, `ProbationEndDate`, `EmployStatus`, `Gender`, `BirthDay`, `Email`, `TelNum`, `MobileNum`) VALUES
('GD00301', NULL, 'Chan', 'Ling Ling', '陳玲玲', 'EXO', 'DIR', 'AMKTMGR', NULL, '1905-03-19', '1905-03-19', 'A', 'M', '1905-03-19', NULL, '28289784', '99664125'),
('GD00302', NULL, 'Lam', 'Che Ken', '林子健', 'ENG', 'D1', 'ASTTOMD', 'GD00301', '1905-03-19', '1905-03-19', 'A', 'M', '1905-03-19', NULL, '22233446', '98864512'),
('GD00303', NULL, 'Lo', 'Pui Chun', '羅佩珍', 'ENG', 'GENERAL', 'COMMGR', 'GD00308', '1905-03-19', '1905-03-19', 'A', 'F', '1905-03-19', NULL, '21945678', '97456134'),
('GD00304', NULL, 'Wong', 'Ho Yan', '黃可欣', 'HR', 'HR_ADM', 'DRIVER', 'GD00301', '1905-03-19', '1905-03-19', 'A', 'M', '1905-03-19', NULL, '23845126', '95468145'),
('GD00305', 2, 'Chin', 'Yee Mei', '陳柯欣', 'AC', 'D2', 'EXEADM', 'GD00301', '1905-03-19', '1905-03-19', 'A', 'F', '1905-03-19', NULL, '26975841', '95648123'),
('GD00306', NULL, 'Ho', 'Wing Keung', '何永強', 'MKT', 'G3', 'FAMGR', 'GD00315', '1905-03-19', '1905-03-19', 'A', 'M', '1905-03-19', NULL, '20196487', '95315640'),
('GD00307', NULL, 'Ho', 'Kwok Wai', '何國維', 'MKT', 'AMGR', 'FNMGR', 'GD00315', '1905-03-19', '1905-03-19', 'A', 'F', '1905-03-19', NULL, '26410048', '90546128'),
('GD00308', NULL, 'Ng', 'Yuen Lam', '吳婉琳', 'ENG', 'M3', 'HROFR', 'GD00310', '1905-03-19', '1905-03-19', 'A', 'M', '1905-03-19', NULL, '28285149', '97648513'),
('GD00309', NULL, 'Hung', 'Ka Yin', '洪家賢', 'MKT', 'D3', 'MECOR', 'GD00311', '1905-03-19', '1905-03-19', 'A', 'F', '1905-03-19', NULL, '29926464', '92361425'),
('GD00310', NULL, 'Wong', 'Kim Fung', '王劍烽', 'ENG', 'D3', 'MEMGR', 'GD00302', '1905-03-19', '1905-03-19', 'A', 'M', '1905-03-19', NULL, '22334455', '93410946'),
('GD00311', NULL, 'Ng', 'Tsz Fung', '吳子豐', 'MKT', 'D1', 'MERTCOR', 'GD00301', '1905-03-19', '1905-03-19', 'A', 'F', '1905-03-19', NULL, '20514232', '90670806'),
('GD00312', NULL, 'Chan', 'Ho Wing', '陳浩榮', 'AC', 'M2', 'MGTTEE', 'GD00305', '1905-03-19', '1905-03-19', 'A', 'F', '1905-03-19', NULL, '26549873', '91345675'),
('GD00314', NULL, 'Tam', 'Man Tat', '譚文逹', 'ENG', 'M2', 'MKTAST', 'GD00302', '1905-03-19', '1905-03-19', 'A', 'F', '1905-03-19', NULL, '29168457', '66977894'),
('GD00315', NULL, 'Chan', 'Yat Chung', '陳一中', 'MKT', 'M1', 'MKTEXE', 'GD00309', '1905-03-19', '1905-03-19', 'A', 'F', '1905-03-19', NULL, '24356857', '69453214'),
('GD00316', NULL, 'Wu', 'Wai Ching', '胡惠晶', 'ENG', 'GENERAL', 'OFCAST', 'GD00308', '1905-03-19', '1905-03-19', 'A', 'F', '1905-03-19', NULL, '25369658', '61234567'),
('GD00317', NULL, 'Ma', 'Wai Ling', '馬惠玲', 'ENG', 'GENERAL', 'PAOFR', 'GD00308', '1905-03-19', '1905-03-19', 'A', 'F', '1905-03-19', NULL, '23236598', '62345678'),
('GD00318', NULL, 'Chan', 'Yee Man', '陳綺雯', 'AC', 'GENERAL', 'PREDT', 'GD00305', '1905-03-19', '1905-03-19', 'A', 'M', '1905-03-19', NULL, '25836914', '52631478'),
('GD00319', NULL, 'Chui', 'Ka Man', '徐嘉文', 'MKT', 'NA', 'PROMOTER', 'GD00315', '1905-03-19', '1905-03-19', 'A', 'F', '1905-03-19', NULL, '21478523', '59644651'),
('GD00320', NULL, 'Chan', 'Yee Man', '陳綺文', 'MKT', 'SMGR', 'ACMGR', 'GD00309', '1905-03-19', '1905-03-19', 'A', 'M', '1905-03-19', NULL, '23698547', '54679458'),
('TESTING', 1, 'Keith', 'POON', NULL, 'DVL', 'AMGR', 'ALYPROG', NULL, '1905-03-19', '1905-03-19', 'A', 'M', '1905-03-19', 'test@gmail.com', '23458521', '51324567');

-- --------------------------------------------------------

--
-- Table structure for table `staffgrade`
--

DROP TABLE IF EXISTS `staffgrade`;
CREATE TABLE IF NOT EXISTS `staffgrade` (
  `StaffGradeCode` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `Description` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`StaffGradeCode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `staffgrade`
--

INSERT INTO `staffgrade` (`StaffGradeCode`, `Description`) VALUES
('AMGR', 'Assistant Manager'),
('D1', 'Director Grade 1'),
('D2', 'Director Grade 2'),
('D3', 'Director Grade 3'),
('DIR', 'Director'),
('G1', 'General Staff Grade 1'),
('G2', 'General Staff Grade 2'),
('G3', 'General Staff Grade 3'),
('GENERAL', 'General Staff'),
('GMGR', 'General Manager'),
('HR_ADM', 'HR Administrator'),
('M1', 'Manager Grade 1'),
('M2', 'Manager Grade 2'),
('M3', 'Manager Grade 3'),
('MGR', 'Manager'),
('NA', 'Non Applicable'),
('SMGR', 'Senior Manager');

-- --------------------------------------------------------

--
-- Table structure for table `vendor`
--

DROP TABLE IF EXISTS `vendor`;
CREATE TABLE IF NOT EXISTS `vendor` (
  `VendorID` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `UserID` int(10) NOT NULL,
  `Name` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ContactPerson` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Phone` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Email` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`VendorID`),
  KEY `FKVendor491210` (`UserID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `vendor`
--

INSERT INTO `vendor` (`VendorID`, `UserID`, `Name`, `ContactPerson`, `Phone`, `Email`) VALUES
('FACEBOOK', 0, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `webuser`
--

DROP TABLE IF EXISTS `webuser`;
CREATE TABLE IF NOT EXISTS `webuser` (
  `UserID` int(10) NOT NULL AUTO_INCREMENT,
  `LoginID` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `Password` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `AccountType` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ActivateDate` datetime DEFAULT NULL,
  `Status` char(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `LastLoginTime` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`UserID`),
  UNIQUE KEY `LoginID` (`LoginID`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `webuser`
--

INSERT INTO `webuser` (`UserID`, `LoginID`, `Password`, `AccountType`, `ActivateDate`, `Status`, `LastLoginTime`) VALUES
(1, 'admin', 'd033e22ae348aeb5660fc2140aec35850c4da997', 'admin', '2016-01-30 00:00:00', 'a', NULL),
(2, 'gd00305', '23f85d4c01ef5bb14599fe8fd3de0bd197888d83', 'staff', '2017-02-01 00:00:00', 'a', NULL);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `evaproposal`
--
ALTER TABLE `evaproposal`
  ADD CONSTRAINT `FKEvaProposa584451` FOREIGN KEY (`EvaluationCode`) REFERENCES `evaluation` (`EvaluationCode`),
  ADD CONSTRAINT `FKEvaProposa783043` FOREIGN KEY (`EvaTypeCode`) REFERENCES `evatype` (`EvaTypeCode`),
  ADD CONSTRAINT `evaproposal_ibfk_1` FOREIGN KEY (`Evaluatee`) REFERENCES `staff` (`StaffID`),
  ADD CONSTRAINT `evaproposal_ibfk_2` FOREIGN KEY (`Evaluator`) REFERENCES `staff` (`StaffID`),
  ADD CONSTRAINT `evaproposal_ibfk_3` FOREIGN KEY (`EvaProQtnStatusCode`) REFERENCES `evaproqtnstatus` (`EvaProQtnStatusCode`);

--
-- Constraints for table `question`
--
ALTER TABLE `question`
  ADD CONSTRAINT `FKQuestion232811` FOREIGN KEY (`QuestionnaireID`) REFERENCES `questionnaire` (`QuestionnaireID`);

--
-- Constraints for table `questionnaire`
--
ALTER TABLE `questionnaire`
  ADD CONSTRAINT `FKQuestionna469445` FOREIGN KEY (`EvaluationCode`) REFERENCES `evaluation` (`EvaluationCode`);

--
-- Constraints for table `questionnaireresult`
--
ALTER TABLE `questionnaireresult`
  ADD CONSTRAINT `FKQuestionna228062` FOREIGN KEY (`EvaProposalID`) REFERENCES `evaproposal` (`EvaProposalID`),
  ADD CONSTRAINT `questionnaireresult_ibfk_1` FOREIGN KEY (`QuestionID`) REFERENCES `question` (`QuestionID`);

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `FKStaff586424` FOREIGN KEY (`DepartmentCode`) REFERENCES `department` (`DepartmentCode`),
  ADD CONSTRAINT `FKStaff747337` FOREIGN KEY (`StaffGradeCode`) REFERENCES `staffgrade` (`StaffGradeCode`),
  ADD CONSTRAINT `FKStaff828323` FOREIGN KEY (`PositionCode`) REFERENCES `position` (`PositionCode`),
  ADD CONSTRAINT `staff_ibfk_2` FOREIGN KEY (`UserID`) REFERENCES `webuser` (`UserID`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
