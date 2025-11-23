<?php
namespace SmApp\Controllers;
use SmCore\Database;

class DevicesController extends BaseController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function index() {
        $devices = $this->db->query("SELECT * FROM sm_devices ORDER BY status ASC, last_heartbeat DESC")->fetchAll();
        $this->view('devices/index', ['devices' => $devices]);
    }

    /**
     * 激活设备并绑定门店
     */
    public function activate() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') die("Invalid");

        $id = $_POST['id'] ?? 0;
        $shop_id = $_POST['shop_id'] ?? 0; // 简单的门店ID输入，后续可对接 shops 表
        $name = $_POST['device_name'] ?? '门店终端';

        if ($id) {
            $sql = "UPDATE sm_devices SET status = 1, shop_id = ?, device_name = ? WHERE id = ?";
            $this->db->prepare($sql)->execute([$shop_id, $name, $id]);
        }
        $this->redirect('/devices/index');
    }

    /**
     * 禁用设备
     */
    public function block() {
        $id = $_GET['id'] ?? 0;
        if ($id) {
            $this->db->prepare("UPDATE sm_devices SET status = 2 WHERE id = ?")->execute([$id]);
        }
        $this->redirect('/devices/index');
    }
}