<?php
namespace SmApp\Controllers;
use SmCore\Database;

class DevicesController extends BaseController {
    private $db;

    public function __construct() {
        parent::__construct();
        $this->db = Database::getInstance()->getConnection();
    }

    public function index() {
        $devices = $this->db->query("SELECT * FROM sm_devices ORDER BY status ASC, last_heartbeat DESC")->fetchAll();
        $this->view('devices/index', ['devices' => $devices]);
    }

    /**
     * 激活设备并绑定门店
     * [BUG修复] 添加输入验证
     */
    public function activate() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') die("Invalid Request Method");

        $id = $_POST['id'] ?? 0;
        $shop_id = $_POST['shop_id'] ?? 0; // 简单的门店ID输入，后续可对接 shops 表
        $name = $_POST['device_name'] ?? '门店终端';

        // [BUG修复] 验证输入
        if (!$id || !is_numeric($id) || $id <= 0) {
            die("Invalid device ID");
        }

        if (!is_numeric($shop_id) || $shop_id < 0) {
            die("Invalid shop ID");
        }

        // [BUG修复] 限制设备名称长度，防止XSS
        $name = htmlspecialchars(trim($name), ENT_QUOTES, 'UTF-8');
        if (strlen($name) > 100) {
            die("Device name too long");
        }

        if ($id) {
            $sql = "UPDATE sm_devices SET status = 1, shop_id = ?, device_name = ? WHERE id = ?";
            $this->db->prepare($sql)->execute([$shop_id, $name, $id]);
        }
        $this->redirect('/devices/index');
    }

    /**
     * 禁用设备
     * [BUG修复] 改为POST请求，避免CSRF攻击
     */
    public function block() {
        // [BUG修复] 只接受POST请求
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            die("Invalid Request Method. Use POST.");
        }

        $id = $_POST['id'] ?? 0;

        // [BUG修复] 验证输入
        if (!$id || !is_numeric($id) || $id <= 0) {
            die("Invalid device ID");
        }

        if ($id) {
            $this->db->prepare("UPDATE sm_devices SET status = 2 WHERE id = ?")->execute([$id]);
        }
        $this->redirect('/devices/index');
    }
}