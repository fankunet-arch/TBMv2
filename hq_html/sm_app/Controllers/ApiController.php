<?php
namespace SmApp\Controllers;
use SmCore\Database;

class ApiController {
    private $db;
    // 安全密钥，需与安卓端 NetworkClient.kt 保持一致
    // [安全优化] 建议从配置文件读取，这里暂时保持硬编码以兼容现有客户端
    private const API_SECRET = 'TOPTEA_SECURE_KEY_2025';

    public function __construct() {
        // [BUG修复] API应该是无状态的，移除session依赖
        // API使用X-Toptea-Secret头进行鉴权，不需要session
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * 安全鉴权检查 - 强制验证 X-Toptea-Secret 头
     * 如果验证失败，返回 HTTP 403 并终止执行
     * [BUG修复] 改进getallheaders兼容性
     */
    private function verifyApiSecret() {
        $headers = $this->getAllHeaders();
        $secret = $headers['x-toptea-secret'] ?? '';

        if ($secret !== self::API_SECRET) {
            http_response_code(403);
            $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized']);
        }
    }

    /**
     * [BUG修复] 兼容所有环境的headers获取方法
     * 替代getallheaders()以支持非Apache环境
     */
    private function getAllHeaders() {
        $headers = [];

        // 优先使用getallheaders（Apache/PHP 7.3+）
        if (function_exists('getallheaders')) {
            $headers = array_change_key_case(getallheaders(), CASE_LOWER);
        } else {
            // 备用方案：从$_SERVER解析
            foreach ($_SERVER as $key => $value) {
                if (strpos($key, 'HTTP_') === 0) {
                    $headerKey = strtolower(str_replace('_', '-', substr($key, 5)));
                    $headers[$headerKey] = $value;
                }
            }
        }

        return $headers;
    }

    /**
     * [BUG修复] 添加MAC地址格式验证
     */
    private function isValidMacAddress($mac) {
        return preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $mac);
    }

    private function jsonResponse($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public function heartbeat() {
        $this->verifyApiSecret(); // 强制鉴权
        $this->jsonResponse(['status'=>'success', 'msg'=>'System Online']);
    }

    /**
     * 核心同步接口 (安全升级版)
     * POST /smsys/api/check_update
     * [BUG修复] 添加完善的输入验证和错误处理
     */
    public function check_update() {
        try {
            // 0. 安全鉴权 - 强制检查
            $this->verifyApiSecret();

            // 1. 获取并验证输入
            $input = json_decode(file_get_contents('php://input'), true);

            // [BUG修复] 验证JSON格式
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->jsonResponse(['status'=>'error', 'message'=>'Invalid JSON']);
            }

            $mac = $input['mac_address'] ?? '';
            $clientVer = $input['current_version'] ?? '';

            // [BUG修复] 验证MAC地址格式
            if (!$mac || !$this->isValidMacAddress($mac)) {
                $this->jsonResponse(['status'=>'error', 'message'=>'Invalid MAC Address']);
            }

            // [BUG修复] 验证版本号格式（应该是数字字符串）
            if ($clientVer !== '' && !ctype_digit($clientVer)) {
                $this->jsonResponse(['status'=>'error', 'message'=>'Invalid Version Format']);
            }

            // 2. 设备注册/获取状态
            $device = $this->getOrRegisterDevice($mac);

            // 3. 核心安全拦截：如果设备未激活 (status = 0)
            // 返回文档规定的标准错误格式
            if ($device['status'] == 0) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Device Not Activated'
                ]);
            }

            // 如果被禁用 (status = 2)
            if ($device['status'] == 2) {
                $this->jsonResponse(['status'=>'error', 'message'=>'Device Blocked']);
            }

            // 4. 增强版版本号计算 - 综合三张配置表的最后更新时间
            // 确保任何配置变更（指派/歌单/策略）都能触发客户端更新
            $sql = "
                SELECT MAX(updated_at) as max_time FROM (
                    SELECT MAX(updated_at) as updated_at FROM sm_assignments
                    UNION ALL
                    SELECT MAX(updated_at) as updated_at FROM sm_playlists
                    UNION ALL
                    SELECT MAX(updated_at) as updated_at FROM sm_strategies
                ) as all_updates
            ";
            $lastMod = $this->db->query($sql)->fetch()['max_time'];
            $serverVer = strtotime($lastMod ?? 'now');

            if ($clientVer == $serverVer) {
                $this->jsonResponse(['status' => 'latest']);
            }

            // 5. 构建全量配置
            $config = [
                'resources' => $this->db->query("SELECT id, file_md5 as md5, file_url as url, file_size as size FROM sm_songs WHERE is_active=1")->fetchAll(),
                'playlists' => $this->fetchPlaylists(),
                'assignments' => $this->fetchAssignments(),
                'holiday_dates' => $this->fetchHolidays()
            ];

            $this->jsonResponse([
                'status' => 'update_required',
                'new_version' => (string)$serverVer,
                'config' => $config
            ]);

        } catch (\PDOException $e) {
            // [BUG修复] 捕获数据库异常，不暴露敏感信息
            error_log("API check_update DB Error: " . $e->getMessage());
            $this->jsonResponse(['status'=>'error', 'message'=>'Database Error']);
        } catch (\Exception $e) {
            // [BUG修复] 捕获其他异常
            error_log("API check_update Error: " . $e->getMessage());
            $this->jsonResponse(['status'=>'error', 'message'=>'Internal Server Error']);
        }
    }

    // --- Helpers ---

    private function getOrRegisterDevice($mac) {
        // 先查询
        $stmt = $this->db->prepare("SELECT * FROM sm_devices WHERE mac_address = ?");
        $stmt->execute([$mac]);
        $device = $stmt->fetch();

        if ($device) {
            // 更新心跳
            $this->db->prepare("UPDATE sm_devices SET last_heartbeat = NOW() WHERE id = ?")->execute([$device['id']]);
            return $device;
        } else {
            // 新设备：默认 status = 0 (未激活)
            $sql = "INSERT INTO sm_devices (mac_address, last_heartbeat, status) VALUES (?, NOW(), 0)";
            $this->db->prepare($sql)->execute([$mac]);
            
            // 再次查询返回
            $stmt->execute([$mac]);
            return $stmt->fetch();
        }
    }

    /**
     * [代码审计注释] 获取所有歌单配置
     * sm_playlists表无is_active字段，返回所有歌单
     * 后续如需过滤，可在管理端删除不需要的歌单
     */
    private function fetchPlaylists() {
        $rows = $this->db->query("SELECT id, play_mode, song_ids_json FROM sm_playlists")->fetchAll();
        $result = [];
        foreach ($rows as $r) {
            $result[$r['id']] = [
                'mode' => $r['play_mode'],
                'ids' => json_decode($r['song_ids_json'])
            ];
        }
        return $result;
    }

    /**
     * [代码审计注释] 获取所有规则指派
     * sm_assignments和sm_strategies表无is_active字段，返回所有配置
     * 后续如需过滤，可在管理端删除不需要的配置
     */
    private function fetchAssignments() {
        $rows = $this->db->query("SELECT * FROM sm_assignments")->fetchAll();
        $res = ['specials' => [], 'holidays' => null, 'weekdays' => []];

        // 预加载策略详情
        $strategies = [];
        $sRows = $this->db->query("SELECT id, timeline_json FROM sm_strategies")->fetchAll();
        foreach ($sRows as $s) $strategies[$s['id']] = json_decode($s['timeline_json']);

        foreach ($rows as $r) {
            $sId = $r['strategy_id'];
            $sData = $strategies[$sId] ?? [];

            if ($r['priority'] == 3) {
                $res['specials'][$r['condition_key']] = $sData;
            } elseif ($r['priority'] == 2) {
                $res['holidays'] = $sData;
            } elseif ($r['priority'] == 1) {
                $res['weekdays'][$r['condition_key']] = $sData;
            }
        }
        return $res;
    }

    private function fetchHolidays() {
        return $this->db->query("SELECT calendar_date FROM sm_calendar WHERE day_type=1 AND calendar_date >= CURDATE()")->fetchAll(\PDO::FETCH_COLUMN);
    }
}