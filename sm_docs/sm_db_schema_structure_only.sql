-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- 主机： mhdlmskv3gjbpqv3.mysql.db
-- 生成日期： 2025-11-23 02:09:56
-- 服务器版本： 8.4.6-6
-- PHP 版本： 8.1.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `mhdlmskv3gjbpqv3`
--
CREATE DATABASE IF NOT EXISTS `mhdlmskv3gjbpqv3` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `mhdlmskv3gjbpqv3`;

-- --------------------------------------------------------

--
-- 表的结构 `cpsys_users`
--

DROP TABLE IF EXISTS `cpsys_users`;
CREATE TABLE `cpsys_users` (
  `id` int UNSIGNED NOT NULL,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '显示名称',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '账户是否激活',
  `role_id` int UNSIGNED NOT NULL COMMENT '外键, 关联 cpsys_roles 表',
  `last_login_at` datetime(6) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT (utc_timestamp(6)),
  `updated_at` datetime(6) NOT NULL DEFAULT (utc_timestamp(6)) ON UPDATE CURRENT_TIMESTAMP(6),
  `deleted_at` datetime(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台系统用户表';

-- --------------------------------------------------------

--
-- 表的结构 `kds_stores`
--

DROP TABLE IF EXISTS `kds_stores`;
CREATE TABLE `kds_stores` (
  `id` int UNSIGNED NOT NULL,
  `store_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '门店码 (e.g., A1001)',
  `store_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '门店名称',
  `invoice_prefix` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '票号前缀 (e.g., S1)',
  `tax_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '门店税号 (NIF/CIF)，用于票据合规',
  `default_vat_rate` decimal(5,2) NOT NULL DEFAULT '10.00' COMMENT '门店默认增值税率(%)',
  `store_city` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '所在城市',
  `store_address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '详细地址',
  `store_phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '联系电话',
  `store_cif` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'CIF/税号',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime(6) NOT NULL DEFAULT (utc_timestamp(6)),
  `updated_at` datetime(6) NOT NULL DEFAULT (utc_timestamp(6)) ON UPDATE CURRENT_TIMESTAMP(6),
  `deleted_at` datetime(6) DEFAULT NULL,
  `billing_system` enum('TICKETBAI','VERIFACTU','NONE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'NONE' COMMENT '该门店使用的票据合规系统',
  `eod_cutoff_hour` int NOT NULL DEFAULT '3',
  `pr_receipt_type` enum('NONE','WIFI','BLUETOOTH','USB') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'NONE' COMMENT '角色1: 小票打印机类型',
  `pr_receipt_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '角色1: IP地址',
  `pr_receipt_port` int DEFAULT NULL COMMENT '角色1: 端口',
  `pr_receipt_mac` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '角色1: 蓝牙MAC',
  `pr_sticker_type` enum('NONE','WIFI','BLUETOOTH','USB') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'NONE' COMMENT '角色2: 杯贴打印机类型',
  `pr_sticker_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '角色2: IP地址',
  `pr_sticker_port` int DEFAULT NULL COMMENT '角色2: 端口',
  `pr_sticker_mac` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '角色2: 蓝牙MAC',
  `pr_kds_type` enum('NONE','WIFI','BLUETOOTH','USB') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'NONE' COMMENT '角色3: KDS厨房/效期打印机',
  `pr_kds_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '角色3: IP地址',
  `pr_kds_port` int DEFAULT NULL COMMENT '角色3: 端口',
  `pr_kds_mac` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '角色3: 蓝牙MAC'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='KDS - 门店表';

-- --------------------------------------------------------

--
-- 表的结构 `sm_assignments`
--

DROP TABLE IF EXISTS `sm_assignments`;
CREATE TABLE `sm_assignments` (
  `id` int UNSIGNED NOT NULL,
  `priority` tinyint(1) NOT NULL COMMENT '优先级: 3=Special, 2=Holiday, 1=Weekly',
  `condition_key` varchar(50) NOT NULL COMMENT '条件键: 日期(2025-12-01) / HOLIDAY / 星期(1-7)',
  `strategy_id` int UNSIGNED NOT NULL COMMENT '关联策略ID',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='SoundMatrix-规则指派表';

-- --------------------------------------------------------

--
-- 表的结构 `sm_calendar`
--

DROP TABLE IF EXISTS `sm_calendar`;
CREATE TABLE `sm_calendar` (
  `calendar_date` date NOT NULL COMMENT '具体日期',
  `day_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=节假日(Holiday), 0=调休工作日(Workday)',
  `description` varchar(100) DEFAULT NULL COMMENT '备注(如:国庆节)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='SoundMatrix-日历表';

-- --------------------------------------------------------

--
-- 表的结构 `sm_devices`
--

DROP TABLE IF EXISTS `sm_devices`;
CREATE TABLE `sm_devices` (
  `id` int UNSIGNED NOT NULL,
  `shop_id` int DEFAULT NULL COMMENT '关联门店ID',
  `device_name` varchar(100) DEFAULT NULL,
  `mac_address` varchar(64) NOT NULL COMMENT '硬件唯一标识',
  `current_version` varchar(64) DEFAULT NULL COMMENT '当前同步版本',
  `last_heartbeat` datetime DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1' COMMENT '1=正常, 0=禁用'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='SoundMatrix-设备终端表';

-- --------------------------------------------------------

--
-- 表的结构 `sm_playlists`
--

DROP TABLE IF EXISTS `sm_playlists`;
CREATE TABLE `sm_playlists` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL COMMENT '歌单名称',
  `play_mode` enum('sequence','random') DEFAULT 'sequence' COMMENT '播放模式: 顺序/随机',
  `song_ids_json` json NOT NULL COMMENT 'JSON数组: [101, 102, 5]',
  `created_by` int DEFAULT NULL COMMENT '创建人ID(关联admin)',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='SoundMatrix-歌单表';

-- --------------------------------------------------------

--
-- 表的结构 `sm_songs`
--

DROP TABLE IF EXISTS `sm_songs`;
CREATE TABLE `sm_songs` (
  `id` int UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL COMMENT '歌曲标题',
  `artist` varchar(100) DEFAULT 'Unknown' COMMENT '歌手',
  `file_url` varchar(500) NOT NULL COMMENT '云端存储URL',
  `file_md5` char(32) NOT NULL COMMENT '文件MD5指纹',
  `file_size` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '文件大小(Bytes)',
  `duration` int UNSIGNED DEFAULT '0' COMMENT '时长(秒)',
  `is_active` tinyint(1) DEFAULT '1' COMMENT '1=启用, 0=软删除',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='SoundMatrix-曲库表';

-- --------------------------------------------------------

--
-- 表的结构 `sm_strategies`
--

DROP TABLE IF EXISTS `sm_strategies`;
CREATE TABLE `sm_strategies` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL COMMENT '策略名称(如:标准工作日)',
  `timeline_json` json NOT NULL COMMENT '时间轴配置 [{"start":"08:00","end":"12:00","playlist_id":1},...]',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='SoundMatrix-策略定义表';

--
-- 转储表的索引
--

--
-- 表的索引 `cpsys_users`
--
ALTER TABLE `cpsys_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `idx_cpsys_users_email` (`email`);

--
-- 表的索引 `kds_stores`
--
ALTER TABLE `kds_stores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `store_code` (`store_code`),
  ADD UNIQUE KEY `uniq_invoice_prefix` (`invoice_prefix`);

--
-- 表的索引 `sm_assignments`
--
ALTER TABLE `sm_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_condition` (`priority`,`condition_key`);

--
-- 表的索引 `sm_calendar`
--
ALTER TABLE `sm_calendar`
  ADD PRIMARY KEY (`calendar_date`);

--
-- 表的索引 `sm_devices`
--
ALTER TABLE `sm_devices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_mac` (`mac_address`);

--
-- 表的索引 `sm_playlists`
--
ALTER TABLE `sm_playlists`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `sm_songs`
--
ALTER TABLE `sm_songs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_md5` (`file_md5`) USING BTREE;

--
-- 表的索引 `sm_strategies`
--
ALTER TABLE `sm_strategies`
  ADD PRIMARY KEY (`id`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `cpsys_users`
--
ALTER TABLE `cpsys_users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `kds_stores`
--
ALTER TABLE `kds_stores`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `sm_assignments`
--
ALTER TABLE `sm_assignments`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `sm_devices`
--
ALTER TABLE `sm_devices`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `sm_playlists`
--
ALTER TABLE `sm_playlists`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `sm_songs`
--
ALTER TABLE `sm_songs`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `sm_strategies`
--
ALTER TABLE `sm_strategies`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 限制导出的表
--

--
-- 限制表 `cpsys_users`
--
ALTER TABLE `cpsys_users`
  ADD CONSTRAINT `cpsys_users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `cpsys_roles` (`id`) ON DELETE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
