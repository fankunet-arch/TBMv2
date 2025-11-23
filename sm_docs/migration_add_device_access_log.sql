-- ========================================
-- [安全审计修复] 设备访问日志表
-- 创建时间: 2025-11-23
-- 目的: 记录所有设备访问API的尝试，包括成功、失败和被拒绝的访问
-- ========================================

-- 创建设备访问日志表
DROP TABLE IF EXISTS `sm_device_access_log`;
CREATE TABLE `sm_device_access_log` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `mac_address` varchar(64) NOT NULL COMMENT '设备MAC地址',
  `ip_address` varchar(45) DEFAULT NULL COMMENT '请求来源IP地址',
  `endpoint` varchar(100) NOT NULL COMMENT 'API端点 (如: check_update, heartbeat)',
  `access_result` enum('success','auth_failed','device_inactive','device_blocked','invalid_mac','invalid_json','db_error','other_error') NOT NULL COMMENT '访问结果',
  `error_message` varchar(255) DEFAULT NULL COMMENT '错误信息详情',
  `user_agent` varchar(500) DEFAULT NULL COMMENT 'User-Agent头',
  `request_data` text DEFAULT NULL COMMENT '请求数据(JSON格式，敏感信息已脱敏)',
  `device_id` int UNSIGNED DEFAULT NULL COMMENT '关联的设备ID（如果设备已注册）',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '访问时间',
  PRIMARY KEY (`id`),
  KEY `idx_mac` (`mac_address`),
  KEY `idx_access_result` (`access_result`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_device_id` (`device_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='SoundMatrix-设备访问日志表';

-- 为sm_devices表的status字段更新注释（修复注释不一致问题）
ALTER TABLE `sm_devices`
  MODIFY `status` tinyint(1) DEFAULT '1' COMMENT '设备状态: 0=未激活(待审核), 1=已激活(正常), 2=已禁用(拉黑)';

-- 创建查询未授权访问尝试的视图（便于安全审计）
CREATE OR REPLACE VIEW `v_unauthorized_access_attempts` AS
SELECT
    l.id,
    l.mac_address,
    l.ip_address,
    l.endpoint,
    l.access_result,
    l.error_message,
    l.created_at,
    d.device_name,
    d.shop_id,
    d.status as device_status
FROM sm_device_access_log l
LEFT JOIN sm_devices d ON l.device_id = d.id
WHERE l.access_result IN ('auth_failed', 'device_inactive', 'device_blocked', 'invalid_mac')
ORDER BY l.created_at DESC;

-- 创建索引以加速日志查询（可选：如果日志量大，建议定期清理旧日志）
-- 示例：删除30天前的日志
-- DELETE FROM sm_device_access_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
