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
     * [安全审计修复] 添加认证失败日志记录
     */
    private function verifyApiSecret($endpoint = 'unknown', $mac = null) {
        $headers = $this->getAllHeaders();
        $secret = $headers['x-toptea-secret'] ?? '';

        if ($secret !== self::API_SECRET) {
            // [安全审计] 记录认证失败的访问尝试
            $this->logAccess(
                $mac ?? 'unknown',
                $endpoint,
                'auth_failed',
                'Invalid API Secret'
            );

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
     * [安全审计新增] 获取客户端真实IP地址
     * 考虑代理、负载均衡等情况
     */
    private function getClientIp() {
        $ip = null;

        // 检查代理头（按优先级）
        $headers = [
            'HTTP_CF_CONNECTING_IP',  // Cloudflare
            'HTTP_X_REAL_IP',         // Nginx proxy
            'HTTP_X_FORWARDED_FOR',   // 标准代理头
            'REMOTE_ADDR'             // 直连IP
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // X-Forwarded-For可能包含多个IP，取第一个
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                break;
            }
        }

        // 验证IP格式
        if ($ip && filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }

        return null;
    }

    /**
     * [安全审计新增] 记录设备访问日志
     * @param string $mac MAC地址
     * @param string $endpoint API端点
     * @param string $result 访问结果
     * @param string $errorMsg 错误信息（可选）
     * @param int $deviceId 设备ID（可选）
     * @param array $requestData 请求数据（可选，会脱敏）
     */
    private function logAccess($mac, $endpoint, $result, $errorMsg = null, $deviceId = null, $requestData = null) {
        try {
            $ip = $this->getClientIp();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

            // 脱敏处理：移除敏感数据
            $sanitizedData = null;
            if ($requestData) {
                $sanitizedData = $requestData;
                // 移除敏感字段（如果有）
                unset($sanitizedData['password'], $sanitizedData['secret']);
                $sanitizedData = json_encode($sanitizedData, JSON_UNESCAPED_UNICODE);
                // 限制长度
                if (strlen($sanitizedData) > 1000) {
                    $sanitizedData = substr($sanitizedData, 0, 1000) . '...';
                }
            }

            $sql = "INSERT INTO sm_device_access_log
                    (mac_address, ip_address, endpoint, access_result, error_message, user_agent, request_data, device_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $this->db->prepare($sql)->execute([
                $mac,
                $ip,
                $endpoint,
                $result,
                $errorMsg,
                $userAgent,
                $sanitizedData,
                $deviceId
            ]);
        } catch (\Exception $e) {
            // 日志记录失败不应影响主流程，仅记录到错误日志
            error_log("Failed to log device access: " . $e->getMessage());
        }
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
        $this->verifyApiSecret('heartbeat'); // 强制鉴权

        // [安全审计] 记录心跳访问
        $this->logAccess('heartbeat', 'heartbeat', 'success');

        $this->jsonResponse(['status'=>'success', 'msg'=>'System Online']);
    }

    /**
     * 核心同步接口 (安全升级版)
     * POST /smsys/api/check_update
     * [BUG修复] 添加完善的输入验证和错误处理
     * [安全审计修复] 添加完整的访问日志记录
     */
    public function check_update() {
        $mac = null;
        $deviceId = null;

        try {
            // 1. 获取并验证输入
            $input = json_decode(file_get_contents('php://input'), true);

            // [BUG修复] 验证JSON格式
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logAccess('unknown', 'check_update', 'invalid_json', 'JSON parse error');
                $this->jsonResponse(['status'=>'error', 'message'=>'Invalid JSON']);
            }

            $mac = $input['mac_address'] ?? '';
            $clientVer = $input['current_version'] ?? '';

            // [BUG修复] 验证MAC地址格式
            if (!$mac || !$this->isValidMacAddress($mac)) {
                $this->logAccess($mac ?: 'invalid', 'check_update', 'invalid_mac', 'Invalid MAC address format', null, $input);
                $this->jsonResponse(['status'=>'error', 'message'=>'Invalid MAC Address']);
            }

            // 0. 安全鉴权 - 强制检查（现在有MAC地址了）
            $this->verifyApiSecret('check_update', $mac);

            // [BUG修复] 验证版本号格式（应该是数字字符串）
            if ($clientVer !== '' && !ctype_digit($clientVer)) {
                $this->logAccess($mac, 'check_update', 'other_error', 'Invalid version format', null, $input);
                $this->jsonResponse(['status'=>'error', 'message'=>'Invalid Version Format']);
            }

            // 2. 设备注册/获取状态
            $device = $this->getOrRegisterDevice($mac);
            $deviceId = $device['id'];

            // 3. 核心安全拦截：如果设备未激活 (status = 0)
            // 返回文档规定的标准错误格式
            if ($device['status'] == 0) {
                // [安全审计] 记录未激活设备的访问尝试
                $this->logAccess($mac, 'check_update', 'device_inactive', 'Device not activated', $deviceId, $input);

                $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Device Not Activated'
                ]);
            }

            // 如果被禁用 (status = 2)
            if ($device['status'] == 2) {
                // [安全审计] 记录被禁用设备的访问尝试
                $this->logAccess($mac, 'check_update', 'device_blocked', 'Device has been blocked', $deviceId, $input);

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
                // [安全审计] 记录成功的访问（配置已是最新）
                $this->logAccess($mac, 'check_update', 'success', null, $deviceId, ['mac_address' => $mac, 'current_version' => $clientVer, 'result' => 'latest']);

                $this->jsonResponse(['status' => 'latest']);
            }

            // 5. 构建全量配置
            $config = [
                'resources' => $this->db->query("SELECT id, file_md5 as md5, file_url as url, file_size as size FROM sm_songs WHERE is_active=1")->fetchAll(),
                'playlists' => $this->fetchPlaylists(),
                'assignments' => $this->fetchAssignments(),
                'holiday_dates' => $this->fetchHolidays()
            ];

            // [安全审计] 记录成功的访问
            $this->logAccess($mac, 'check_update', 'success', null, $deviceId, $input);

            $this->jsonResponse([
                'status' => 'update_required',
                'new_version' => (string)$serverVer,
                'config' => $config
            ]);

        } catch (\PDOException $e) {
            // [BUG修复] 捕获数据库异常，不暴露敏感信息
            // [安全审计] 记录数据库错误
            $this->logAccess($mac ?? 'unknown', 'check_update', 'db_error', 'Database error: ' . $e->getMessage(), $deviceId);
            error_log("API check_update DB Error: " . $e->getMessage());
            $this->jsonResponse(['status'=>'error', 'message'=>'Database Error']);
        } catch (\Exception $e) {
            // [BUG修复] 捕获其他异常
            // [安全审计] 记录其他错误
            $this->logAccess($mac ?? 'unknown', 'check_update', 'other_error', 'Exception: ' . $e->getMessage(), $deviceId);
            error_log("API check_update Error: " . $e->getMessage());
            $this->jsonResponse(['status'=>'error', 'message'=>'Internal Server Error']);
        }
    }

    // --- Helpers ---

    /**
     * [安全审计修复] 添加IP地址记录
     */
    private function getOrRegisterDevice($mac) {
        $ip = $this->getClientIp();

        // 先查询
        $stmt = $this->db->prepare("SELECT * FROM sm_devices WHERE mac_address = ?");
        $stmt->execute([$mac]);
        $device = $stmt->fetch();

        if ($device) {
            // 更新心跳和IP地址
            $this->db->prepare("UPDATE sm_devices SET last_heartbeat = NOW(), ip_address = ? WHERE id = ?")->execute([$ip, $device['id']]);
            // 更新返回的设备信息中的IP
            $device['ip_address'] = $ip;
            return $device;
        } else {
            // 新设备：默认 status = 0 (未激活)
            $sql = "INSERT INTO sm_devices (mac_address, ip_address, last_heartbeat, status) VALUES (?, ?, NOW(), 0)";
            $this->db->prepare($sql)->execute([$mac, $ip]);

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