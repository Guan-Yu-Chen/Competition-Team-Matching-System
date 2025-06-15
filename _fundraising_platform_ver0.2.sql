-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
-- 產生時間： 2025-06-07 13:18:47
-- 伺服器版本： 10.4.32-MariaDB
-- PHP 版本： 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 資料庫： `	fundraising_platform_ver.2`
--

-- --------------------------------------------------------

--
-- 資料表結構 `administrator`
--

CREATE TABLE `administrator` (
  `AID` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `administrator`
--

INSERT INTO `administrator` (`AID`) VALUES
('AID001'),
('AID002'),
('AID003');

-- --------------------------------------------------------

--
-- 資料表結構 `announcement`
--

CREATE TABLE `announcement` (
  `AnnouncementID` varchar(50) NOT NULL,
  `Title` varchar(100) NOT NULL,
  `Content` text NOT NULL,
  `Published_By` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `announcement`
--

INSERT INTO `announcement` (`AnnouncementID`, `Title`, `Content`, `Published_By`) VALUES
('ANN001', '競賽開幕', '2025年全國競賽即將開始！', 'AID001'),
('ANN002', '系統更新', '平台將於6月10日維護。', 'AID002'),
('ANN003', '新競賽發布', 'AI創新競賽現已開放報名。', 'AID001'),
('ANN004', '黑名單公告', '請遵守平台規則。', 'AID002'),
('ANN005', '獎金提升', '部分競賽獎金已提高！', 'AID001'),
('ANN006', '新功能上線', '自動組隊功能已啟用！', 'AID003');

-- --------------------------------------------------------

--
-- 資料表結構 `applicationlist`
--

CREATE TABLE `applicationlist` (
  `ApplicationID` varchar(50) NOT NULL,
  `TeamID` varchar(50) NOT NULL,
  `ApplicantID` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `applicationlist`
--

INSERT INTO `applicationlist` (`ApplicationID`, `TeamID`, `ApplicantID`) VALUES
('APP001', 'TID001', 'SID002'),
('APP002', 'TID002', 'SID003'),
('APP003', 'TID003', 'SID004'),
('APP004', 'TID004', 'SID005'),
('APP005', 'TID005', 'SID006'),
('APP006', 'TID006', 'SID007'),
('APP007', 'TID007', 'SID008'),
('APP008', 'TID008', 'SID001');

-- --------------------------------------------------------

--
-- 資料表結構 `award`
--

CREATE TABLE `award` (
  `SID` varchar(50) NOT NULL,
  `Award_Title` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `award`
--

INSERT INTO `award` (`SID`, `Award_Title`) VALUES
('SID001', '全國程式設計一等獎'),
('SID002', '機器人競賽金獎'),
('SID003', 'ACM比賽二等獎'),
('SID004', '數據分析大賽優勝'),
('SID005', '創業比賽最佳創意'),
('SID006', '設計大賽銀獎'),
('SID007', '數學建模一等獎'),
('SID008', '駭客松最佳解決方案');

-- --------------------------------------------------------

--
-- 資料表結構 `blacklist`
--

CREATE TABLE `blacklist` (
  `SID` varchar(50) NOT NULL,
  `Reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `blacklist`
--

INSERT INTO `blacklist` (`SID`, `Reason`) VALUES
('SID001', '違反平台規則'),
('SID002', '惡意評價'),
('SID003', '重複註冊'),
('SID004', '詐騙行為'),
('SID005', '不當言論');

-- --------------------------------------------------------

--
-- 資料表結構 `competition`
--

CREATE TABLE `competition` (
  `CID` varchar(50) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Organizing_Units` varchar(255) NOT NULL,
  `Field` varchar(100) NOT NULL,
  `Registration_Deadline` date NOT NULL,
  `Prize_Money` decimal(10,2) DEFAULT NULL,
  `Eligibility_Requirements` text DEFAULT NULL,
  `Required_Number` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `competition`
--

INSERT INTO `competition` (`CID`, `Name`, `Organizing_Units`, `Field`, `Registration_Deadline`, `Prize_Money`, `Eligibility_Requirements`, `Required_Number`) VALUES
('CID001', 'AI創新大賽', '科技大學', '人工智能', '2025-07-01', 5000.00, '大學生', 4),
('CID002', '機器人競賽', '工程協會', '機器人', '2025-08-01', 3000.00, '無限制', 3),
('CID003', '程式設計挑戰', 'IT公司', '程式設計', '2025-06-30', 2000.00, '大學生', 2),
('CID004', '數據分析賽', '數據中心', '數據科學', '2025-07-15', 4000.00, '研究生', 5),
('CID005', '創業競賽', '商學院', '創業', '2025-09-01', 6000.00, '無限制', 4),
('CID006', '設計大賽', '藝術協會', '平面設計', '2025-07-10', 2500.00, '大學生', 3),
('CID007', '數學建模', '數學學會', '數學', '2025-06-25', 1500.00, '研究生', 3),
('CID008', '駭客松', '科技公司', '網路安全', '2025-08-15', 3500.00, '無限制', 4);

-- --------------------------------------------------------

--
-- 資料表結構 `competitionrequireskill`
--

CREATE TABLE `competitionrequireskill` (
  `Competition` varchar(50) NOT NULL,
  `Skill` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `competitionrequireskill`
--

INSERT INTO `competitionrequireskill` (`Competition`, `Skill`) VALUES
('CID001', 'Python'),
('CID001', '機器學習'),
('CID002', '電子工程'),
('CID003', 'Java'),
('CID004', 'R'),
('CID005', '商業計劃'),
('CID006', 'Photoshop'),
('CID007', 'Matlab');

-- --------------------------------------------------------

--
-- 資料表結構 `invitationlist`
--

CREATE TABLE `invitationlist` (
  `InvitationID` varchar(50) NOT NULL,
  `TeamID` varchar(50) NOT NULL,
  `InviterID` varchar(50) NOT NULL,
  `InviteeID` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `invitationlist`
--

INSERT INTO `invitationlist` (`InvitationID`, `TeamID`, `InviterID`, `InviteeID`) VALUES
('INV001', 'TID001', 'SID001', 'SID002'),
('INV002', 'TID002', 'SID002', 'SID003'),
('INV003', 'TID003', 'SID003', 'SID004'),
('INV004', 'TID004', 'SID004', 'SID005'),
('INV005', 'TID005', 'SID005', 'SID006'),
('INV006', 'TID006', 'SID006', 'SID007'),
('INV007', 'TID007', 'SID007', 'SID008'),
('INV008', 'TID008', 'SID008', 'SID001');

-- --------------------------------------------------------

--
-- 資料表結構 `participation`
--

CREATE TABLE `participation` (
  `Competition` varchar(50) NOT NULL,
  `Team` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `participation`
--

INSERT INTO `participation` (`Competition`, `Team`) VALUES
('CID001', 'TID001'),
('CID002', 'TID002'),
('CID003', 'TID003'),
('CID004', 'TID004'),
('CID005', 'TID005'),
('CID006', 'TID006'),
('CID007', 'TID007'),
('CID008', 'TID008');

-- --------------------------------------------------------

--
-- 資料表結構 `portfolio`
--

CREATE TABLE `portfolio` (
  `SID` varchar(50) NOT NULL,
  `Title` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `portfolio`
--

INSERT INTO `portfolio` (`SID`, `Title`) VALUES
('SID001', 'AI聊天機器人'),
('SID002', '四足機器人設計'),
('SID003', '線上編輯器'),
('SID004', '銷售預測模型'),
('SID005', '創業計劃書'),
('SID006', '品牌Logo設計'),
('SID007', '數學建模報告'),
('SID008', '網路安全工具');

-- --------------------------------------------------------

--
-- 資料表結構 `skill`
--

CREATE TABLE `skill` (
  `SID` varchar(50) NOT NULL,
  `Skill` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `skill`
--

INSERT INTO `skill` (`SID`, `Skill`) VALUES
('SID001', 'Python'),
('SID002', '電子工程'),
('SID003', 'Java'),
('SID004', 'R'),
('SID005', '商業計劃'),
('SID006', 'Photoshop'),
('SID007', 'Matlab'),
('SID008', '網路安全');

-- --------------------------------------------------------

--
-- 資料表結構 `student`
--

CREATE TABLE `student` (
  `SID` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `student`
--

INSERT INTO `student` (`SID`) VALUES
('SID001'),
('SID002'),
('SID003'),
('SID004'),
('SID005'),
('SID006'),
('SID007'),
('SID008');

-- --------------------------------------------------------

--
-- 資料表結構 `tag`
--

CREATE TABLE `tag` (
  `Competition` varchar(50) NOT NULL,
  `Tag` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `tag`
--

INSERT INTO `tag` (`Competition`, `Tag`) VALUES
('CID001', 'AI'),
('CID001', '創新'),
('CID002', '機器人'),
('CID003', '程式設計'),
('CID004', '數據科學'),
('CID005', '創業'),
('CID006', '設計'),
('CID007', '數學');

-- --------------------------------------------------------

--
-- 資料表結構 `team`
--

CREATE TABLE `team` (
  `TID` varchar(50) NOT NULL,
  `Team_Name` varchar(100) NOT NULL,
  `Leader` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `team`
--

INSERT INTO `team` (`TID`, `Team_Name`, `Leader`) VALUES
('TID001', 'AI先鋒', 'SID001'),
('TID002', '機器人戰隊', 'SID002'),
('TID003', '程式達人', 'SID003'),
('TID004', '數據之星', 'SID004'),
('TID005', '創業夢想', 'SID005'),
('TID006', '設計精靈', 'SID006'),
('TID007', '數學精英', 'SID007'),
('TID008', '安全衛士', 'SID008');

-- --------------------------------------------------------

--
-- 資料表結構 `teamdispute`
--

CREATE TABLE `teamdispute` (
  `DisputeID` varchar(50) NOT NULL,
  `ComplainantID` varchar(50) NOT NULL,
  `RespondentID` varchar(50) NOT NULL,
  `DisputeDetails` text NOT NULL,
  `HandlerID` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `teamdispute`
--

INSERT INTO `teamdispute` (`DisputeID`, `ComplainantID`, `RespondentID`, `DisputeDetails`, `HandlerID`) VALUES
('DISP001', 'SID001', 'SID002', '任務分配不均', 'AID001'),
('DISP002', 'SID003', 'SID004', '未按時提交成果', 'AID002'),
('DISP003', 'SID005', 'SID006', '溝通問題影響進度', 'AID003'),
('DISP004', 'SID007', 'SID008', '擅自更改計劃', 'AID001'),
('DISP005', 'SID002', 'SID003', '未參加會議', 'AID002'),
('DISP006', 'SID004', 'SID005', '成果品質爭議', 'AID003'),
('DISP007', 'SID006', 'SID007', '分配不公', 'AID001'),
('DISP008', 'SID008', 'SID001', '未履行承諾', 'AID002');

-- --------------------------------------------------------

--
-- 資料表結構 `teammembershiphistory`
--

CREATE TABLE `teammembershiphistory` (
  `Team` varchar(50) NOT NULL,
  `Member` varchar(50) NOT NULL,
  `Join_Date` date NOT NULL,
  `Leave_Date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `teammembershiphistory`
--

INSERT INTO `teammembershiphistory` (`Team`, `Member`, `Join_Date`, `Leave_Date`) VALUES
('TID001', 'SID001', '2025-05-01', NULL),
('TID002', 'SID002', '2025-05-02', NULL),
('TID003', 'SID003', '2025-05-03', '2025-06-01'),
('TID004', 'SID004', '2025-05-04', NULL),
('TID005', 'SID005', '2025-05-05', NULL),
('TID006', 'SID006', '2025-05-06', NULL),
('TID007', 'SID007', '2025-05-07', '2025-06-02'),
('TID008', 'SID008', '2025-05-08', NULL);

-- --------------------------------------------------------

--
-- 資料表結構 `teamratings`
--

CREATE TABLE `teamratings` (
  `Team` varchar(50) NOT NULL,
  `Reviewer` varchar(50) NOT NULL,
  `Reviewee` varchar(50) NOT NULL,
  `Rating` int(11) NOT NULL,
  `Comment` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `teamratings`
--

INSERT INTO `teamratings` (`Team`, `Reviewer`, `Reviewee`, `Rating`, `Comment`) VALUES
('TID001', 'SID001', 'SID002', 4, '合作愉快'),
('TID002', 'SID002', 'SID003', 3, '需改善溝通'),
('TID003', 'SID003', 'SID004', 5, '表現出色'),
('TID004', 'SID004', 'SID005', 2, '遲交任務'),
('TID005', 'SID005', 'SID006', 4, '積極參與'),
('TID006', 'SID006', 'SID007', 3, '需更專注'),
('TID007', 'SID007', 'SID008', 5, '領導力強'),
('TID008', 'SID008', 'SID001', 4, '技術優秀');

-- --------------------------------------------------------

--
-- 資料表結構 `teamrecruitment`
--

CREATE TABLE `teamrecruitment` (
  `Team` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `teamrecruitment`
--

INSERT INTO `teamrecruitment` (`Team`) VALUES
('TID001'),
('TID002'),
('TID003'),
('TID004'),
('TID005'),
('TID006'),
('TID007'),
('TID008');

-- --------------------------------------------------------

--
-- 資料表結構 `teamrequireskill`
--

CREATE TABLE `teamrequireskill` (
  `Team` varchar(50) NOT NULL,
  `Skill` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `teamrequireskill`
--

INSERT INTO `teamrequireskill` (`Team`, `Skill`) VALUES
('TID001', 'Python'),
('TID001', 'TensorFlow'),
('TID002', '機械設計'),
('TID003', 'C++'),
('TID004', 'SQL'),
('TID005', '市場分析'),
('TID006', 'Illustrator'),
('TID007', '統計學');

-- --------------------------------------------------------

--
-- 資料表結構 `user`
--

CREATE TABLE `user` (
  `Account` varchar(50) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `user`
--

INSERT INTO `user` (`Account`, `Password`, `Name`) VALUES
('AID001', 'admin123', 'Admin 張'),
('AID002', 'admin123', 'Admin 李'),
('AID003', 'admin123', 'Admin 陳'),
('SID001', 'pass123', '張偉'),
('SID002', 'pass123', '李娜'),
('SID003', 'pass123', '王芳'),
('SID004', 'pass123', '陳陽'),
('SID005', 'pass123', '劉洋'),
('SID006', 'pass123', '趙靜'),
('SID007', 'pass123', '黃磊'),
('SID008', 'pass123', '孫梅');

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `administrator`
--
ALTER TABLE `administrator`
  ADD PRIMARY KEY (`AID`);

--
-- 資料表索引 `announcement`
--
ALTER TABLE `announcement`
  ADD PRIMARY KEY (`AnnouncementID`),
  ADD KEY `Published_By` (`Published_By`);

--
-- 資料表索引 `applicationlist`
--
ALTER TABLE `applicationlist`
  ADD PRIMARY KEY (`ApplicationID`),
  ADD KEY `TeamID` (`TeamID`),
  ADD KEY `ApplicantID` (`ApplicantID`);

--
-- 資料表索引 `award`
--
ALTER TABLE `award`
  ADD PRIMARY KEY (`SID`,`Award_Title`);

--
-- 資料表索引 `blacklist`
--
ALTER TABLE `blacklist`
  ADD PRIMARY KEY (`SID`);

--
-- 資料表索引 `competition`
--
ALTER TABLE `competition`
  ADD PRIMARY KEY (`CID`);

--
-- 資料表索引 `competitionrequireskill`
--
ALTER TABLE `competitionrequireskill`
  ADD PRIMARY KEY (`Competition`,`Skill`);

--
-- 資料表索引 `invitationlist`
--
ALTER TABLE `invitationlist`
  ADD PRIMARY KEY (`InvitationID`),
  ADD KEY `TeamID` (`TeamID`),
  ADD KEY `InviterID` (`InviterID`),
  ADD KEY `InviteeID` (`InviteeID`);

--
-- 資料表索引 `participation`
--
ALTER TABLE `participation`
  ADD PRIMARY KEY (`Competition`,`Team`),
  ADD KEY `Team` (`Team`);

--
-- 資料表索引 `portfolio`
--
ALTER TABLE `portfolio`
  ADD PRIMARY KEY (`SID`,`Title`);

--
-- 資料表索引 `skill`
--
ALTER TABLE `skill`
  ADD PRIMARY KEY (`SID`,`Skill`);

--
-- 資料表索引 `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`SID`);

--
-- 資料表索引 `tag`
--
ALTER TABLE `tag`
  ADD PRIMARY KEY (`Competition`,`Tag`);

--
-- 資料表索引 `team`
--
ALTER TABLE `team`
  ADD PRIMARY KEY (`TID`),
  ADD KEY `Leader` (`Leader`);

--
-- 資料表索引 `teamdispute`
--
ALTER TABLE `teamdispute`
  ADD PRIMARY KEY (`DisputeID`),
  ADD KEY `ComplainantID` (`ComplainantID`),
  ADD KEY `RespondentID` (`RespondentID`),
  ADD KEY `HandlerID` (`HandlerID`);

--
-- 資料表索引 `teammembershiphistory`
--
ALTER TABLE `teammembershiphistory`
  ADD PRIMARY KEY (`Team`,`Member`,`Join_Date`),
  ADD KEY `Member` (`Member`);

--
-- 資料表索引 `teamratings`
--
ALTER TABLE `teamratings`
  ADD PRIMARY KEY (`Team`,`Reviewer`,`Reviewee`),
  ADD KEY `Reviewer` (`Reviewer`),
  ADD KEY `Reviewee` (`Reviewee`);

--
-- 資料表索引 `teamrecruitment`
--
ALTER TABLE `teamrecruitment`
  ADD PRIMARY KEY (`Team`);

--
-- 資料表索引 `teamrequireskill`
--
ALTER TABLE `teamrequireskill`
  ADD PRIMARY KEY (`Team`,`Skill`);

--
-- 資料表索引 `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`Account`);

--
-- 已傾印資料表的限制式
--

--
-- 資料表的限制式 `administrator`
--
ALTER TABLE `administrator`
  ADD CONSTRAINT `administrator_ibfk_1` FOREIGN KEY (`AID`) REFERENCES `user` (`Account`) ON DELETE CASCADE;

--
-- 資料表的限制式 `announcement`
--
ALTER TABLE `announcement`
  ADD CONSTRAINT `announcement_ibfk_1` FOREIGN KEY (`Published_By`) REFERENCES `administrator` (`AID`) ON DELETE CASCADE;

--
-- 資料表的限制式 `applicationlist`
--
ALTER TABLE `applicationlist`
  ADD CONSTRAINT `applicationlist_ibfk_1` FOREIGN KEY (`TeamID`) REFERENCES `team` (`TID`) ON DELETE CASCADE,
  ADD CONSTRAINT `applicationlist_ibfk_2` FOREIGN KEY (`ApplicantID`) REFERENCES `student` (`SID`) ON DELETE CASCADE;

--
-- 資料表的限制式 `award`
--
ALTER TABLE `award`
  ADD CONSTRAINT `award_ibfk_1` FOREIGN KEY (`SID`) REFERENCES `student` (`SID`) ON DELETE CASCADE;

--
-- 資料表的限制式 `blacklist`
--
ALTER TABLE `blacklist`
  ADD CONSTRAINT `blacklist_ibfk_1` FOREIGN KEY (`SID`) REFERENCES `student` (`SID`) ON DELETE CASCADE;

--
-- 資料表的限制式 `competitionrequireskill`
--
ALTER TABLE `competitionrequireskill`
  ADD CONSTRAINT `competitionrequireskill_ibfk_1` FOREIGN KEY (`Competition`) REFERENCES `competition` (`CID`) ON DELETE CASCADE;

--
-- 資料表的限制式 `invitationlist`
--
ALTER TABLE `invitationlist`
  ADD CONSTRAINT `invitationlist_ibfk_1` FOREIGN KEY (`TeamID`) REFERENCES `team` (`TID`) ON DELETE CASCADE,
  ADD CONSTRAINT `invitationlist_ibfk_2` FOREIGN KEY (`InviterID`) REFERENCES `student` (`SID`) ON DELETE CASCADE,
  ADD CONSTRAINT `invitationlist_ibfk_3` FOREIGN KEY (`InviteeID`) REFERENCES `student` (`SID`) ON DELETE CASCADE;

--
-- 資料表的限制式 `participation`
--
ALTER TABLE `participation`
  ADD CONSTRAINT `participation_ibfk_1` FOREIGN KEY (`Competition`) REFERENCES `competition` (`CID`) ON DELETE CASCADE,
  ADD CONSTRAINT `participation_ibfk_2` FOREIGN KEY (`Team`) REFERENCES `team` (`TID`) ON DELETE CASCADE;

--
-- 資料表的限制式 `portfolio`
--
ALTER TABLE `portfolio`
  ADD CONSTRAINT `portfolio_ibfk_1` FOREIGN KEY (`SID`) REFERENCES `student` (`SID`) ON DELETE CASCADE;

--
-- 資料表的限制式 `skill`
--
ALTER TABLE `skill`
  ADD CONSTRAINT `skill_ibfk_1` FOREIGN KEY (`SID`) REFERENCES `student` (`SID`) ON DELETE CASCADE;

--
-- 資料表的限制式 `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `student_ibfk_1` FOREIGN KEY (`SID`) REFERENCES `user` (`Account`) ON DELETE CASCADE;

--
-- 資料表的限制式 `tag`
--
ALTER TABLE `tag`
  ADD CONSTRAINT `tag_ibfk_1` FOREIGN KEY (`Competition`) REFERENCES `competition` (`CID`) ON DELETE CASCADE;

--
-- 資料表的限制式 `team`
--
ALTER TABLE `team`
  ADD CONSTRAINT `team_ibfk_1` FOREIGN KEY (`Leader`) REFERENCES `student` (`SID`) ON DELETE CASCADE;

--
-- 資料表的限制式 `teamdispute`
--
ALTER TABLE `teamdispute`
  ADD CONSTRAINT `teamdispute_ibfk_1` FOREIGN KEY (`ComplainantID`) REFERENCES `student` (`SID`) ON DELETE CASCADE,
  ADD CONSTRAINT `teamdispute_ibfk_2` FOREIGN KEY (`RespondentID`) REFERENCES `student` (`SID`) ON DELETE CASCADE,
  ADD CONSTRAINT `teamdispute_ibfk_3` FOREIGN KEY (`HandlerID`) REFERENCES `administrator` (`AID`) ON DELETE CASCADE;

--
-- 資料表的限制式 `teammembershiphistory`
--
ALTER TABLE `teammembershiphistory`
  ADD CONSTRAINT `teammembershiphistory_ibfk_1` FOREIGN KEY (`Team`) REFERENCES `team` (`TID`) ON DELETE CASCADE,
  ADD CONSTRAINT `teammembershiphistory_ibfk_2` FOREIGN KEY (`Member`) REFERENCES `student` (`SID`) ON DELETE CASCADE;

--
-- 資料表的限制式 `teamratings`
--
ALTER TABLE `teamratings`
  ADD CONSTRAINT `teamratings_ibfk_1` FOREIGN KEY (`Team`) REFERENCES `team` (`TID`) ON DELETE CASCADE,
  ADD CONSTRAINT `teamratings_ibfk_2` FOREIGN KEY (`Reviewer`) REFERENCES `student` (`SID`) ON DELETE CASCADE,
  ADD CONSTRAINT `teamratings_ibfk_3` FOREIGN KEY (`Reviewee`) REFERENCES `student` (`SID`) ON DELETE CASCADE;

--
-- 資料表的限制式 `teamrecruitment`
--
ALTER TABLE `teamrecruitment`
  ADD CONSTRAINT `teamrecruitment_ibfk_1` FOREIGN KEY (`Team`) REFERENCES `team` (`TID`) ON DELETE CASCADE;

--
-- 資料表的限制式 `teamrequireskill`
--
ALTER TABLE `teamrequireskill`
  ADD CONSTRAINT `teamrequireskill_ibfk_1` FOREIGN KEY (`Team`) REFERENCES `team` (`TID`) ON DELETE CASCADE;
COMMIT;

---- ver0.2 新增 TeamDispute 表的 Decision 欄位
ALTER TABLE TeamDispute
ADD COLUMN Decision VARCHAR(50) DEFAULT NULL COMMENT '判決結果：申述成立、不申述、惡意誣告';


-- ver0.1 新增status欄位到 InvitationList 和 ApplicationList 表
ALTER TABLE invitationlist ADD COLUMN status ENUM('pending', 'accepted', 'rejected') NOT NULL DEFAULT 'pending';
ALTER TABLE applicationlist ADD COLUMN status ENUM('pending', 'accepted', 'rejected') NOT NULL DEFAULT 'pending';

UPDATE invitationlist SET status = 'pending';
UPDATE applicationlist SET status = 'pending';

ALTER TABLE invitationlist DROP PRIMARY KEY;
ALTER TABLE applicationlist DROP PRIMARY KEY;

ALTER TABLE invitationlist DROP COLUMN InvitationID;
ALTER TABLE applicationlist DROP COLUMN ApplicationID;

ALTER TABLE invitationlist ADD PRIMARY KEY (TeamID, InviteeID);
ALTER TABLE applicationlist ADD PRIMARY KEY (TeamID, ApplicantID);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
