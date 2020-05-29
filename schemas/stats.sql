-- phpMyAdmin SQL Dump
-- version 4.5.1
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: 2017-05-26 10:00:09
-- 服务器版本： 5.7.9
-- PHP Version: 5.6.15

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `xt_bi`
--

-- --------------------------------------------------------

--
-- 表的结构 `area`
--

CREATE TABLE `area` (
  `id` int(11) UNSIGNED NOT NULL,
  `app_id` int(11) DEFAULT '0',
  `date` date DEFAULT '0000-00-00' COMMENT '日期',
  `area` varchar(16) DEFAULT '' COMMENT '区域',
  `new_device` int(11) DEFAULT '0' COMMENT '新增设备',
  `pay_device` int(11) DEFAULT '0' COMMENT '付费设备数',
  `pay_count` int(11) DEFAULT '0' COMMENT '付费次数',
  `pay_amount` int(11) DEFAULT '0' COMMENT '付费总额'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='区域分析';

-- --------------------------------------------------------

--
-- 表的结构 `life_time`
--

CREATE TABLE `life_time` (
  `id` int(11) UNSIGNED NOT NULL,
  `app_id` int(11) DEFAULT '0' COMMENT '应用ID',
  `date` date DEFAULT '0000-00-00' COMMENT '日期(创建账号日期)',
  `life_time` int(11) DEFAULT '0' COMMENT '生命周期(天)',
  `account` int(11) DEFAULT '0' COMMENT '账号数量'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='生命周期';

-- --------------------------------------------------------

--
-- 表的结构 `ltv`
--

CREATE TABLE `ltv` (
  `id` int(11) UNSIGNED NOT NULL,
  `app_id` int(11) DEFAULT '0' COMMENT '应用ID',
  `channel` varchar(16) DEFAULT NULL COMMENT '渠道',
  `device` varchar(16) DEFAULT NULL COMMENT '设备',
  `date` date DEFAULT '0000-00-00' COMMENT '日期(创建账号日期)',
  `days` int(8) DEFAULT '0' COMMENT '天数N',
  `amount` double(10,2) DEFAULT '0.00' COMMENT '截止到N天的充值总额'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户价值(新增账号)';

-- --------------------------------------------------------

--
-- 表的结构 `payment_day`
--

CREATE TABLE `payment_day` (
  `id` int(11) UNSIGNED NOT NULL,
  `app_id` int(11) DEFAULT '0' COMMENT '应用ID',
  `channel` varchar(16) DEFAULT '' COMMENT '渠道',
  `device` varchar(16) DEFAULT '' COMMENT '设备',
  `date` date DEFAULT '0000-00-00' COMMENT '日期',
  `new_account` int(11) DEFAULT '0' COMMENT '新增付费账号数',
  `new_device` int(11) DEFAULT '0' COMMENT '新增付费设备数',
  `count_account` int(11) DEFAULT '0' COMMENT '付费账号数',
  `count_device` int(11) DEFAULT '0' COMMENT '付费设备数',
  `times` int(11) DEFAULT '0' COMMENT '付费次数',
  `amount` double(10,2) DEFAULT '0.00' COMMENT '付费总额'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='付费分析(日)';

-- --------------------------------------------------------

--
-- 表的结构 `report_day`
--

CREATE TABLE `report_day` (
  `id` int(11) UNSIGNED NOT NULL,
  `app_id` int(11) DEFAULT '0' COMMENT '应用ID',
  `channel` varchar(16) DEFAULT '' COMMENT '渠道',
  `device` varchar(16) DEFAULT '' COMMENT '设备',
  `date` date DEFAULT '0000-00-00' COMMENT '日期',
  `new_account` int(11) DEFAULT '0' COMMENT '新增账号',
  `new_device` int(11) DEFAULT '0' COMMENT '新增设备',
  `active_account` int(11) DEFAULT '0' COMMENT '活跃账号',
  `active_device` int(11) DEFAULT '0' COMMENT '活跃设备',
  `login_times` int(11) DEFAULT '0' COMMENT '登录次数'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='报告(日)';

-- --------------------------------------------------------

--
-- 表的结构 `retention`
--

CREATE TABLE `retention` (
  `id` int(11) UNSIGNED NOT NULL,
  `app_id` int(11) DEFAULT '0' COMMENT '应用ID',
  `channel` varchar(16) DEFAULT NULL COMMENT '渠道',
  `device` varchar(16) DEFAULT NULL COMMENT '设备',
  `date` date DEFAULT '0000-00-00' COMMENT '日期',
  `days` int(8) DEFAULT '0' COMMENT '留存天数',
  `count_device` int(11) DEFAULT '0' COMMENT '留存设备数'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='留存';

--
-- Indexes for dumped tables
--

--
-- Indexes for table `area`
--
ALTER TABLE `area`
  ADD PRIMARY KEY (`id`),
  ADD KEY `date` (`date`);

--
-- Indexes for table `life_time`
--
ALTER TABLE `life_time`
  ADD PRIMARY KEY (`id`),
  ADD KEY `date` (`date`);

--
-- Indexes for table `ltv`
--
ALTER TABLE `ltv`
  ADD PRIMARY KEY (`id`),
  ADD KEY `date` (`date`);

--
-- Indexes for table `payment_day`
--
ALTER TABLE `payment_day`
  ADD PRIMARY KEY (`id`),
  ADD KEY `date` (`date`);

--
-- Indexes for table `report_day`
--
ALTER TABLE `report_day`
  ADD PRIMARY KEY (`id`),
  ADD KEY `date` (`date`);

--
-- Indexes for table `retention`
--
ALTER TABLE `retention`
  ADD PRIMARY KEY (`id`),
  ADD KEY `date` (`date`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `area`
--
ALTER TABLE `area`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- 使用表AUTO_INCREMENT `life_time`
--
ALTER TABLE `life_time`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- 使用表AUTO_INCREMENT `ltv`
--
ALTER TABLE `ltv`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- 使用表AUTO_INCREMENT `payment_day`
--
ALTER TABLE `payment_day`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- 使用表AUTO_INCREMENT `report_day`
--
ALTER TABLE `report_day`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- 使用表AUTO_INCREMENT `retention`
--
ALTER TABLE `retention`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
