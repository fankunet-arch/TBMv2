<?php
namespace SmApp\Controllers;

use SmCore\Database;

class StrategiesController extends BaseController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function index() {
        $stmt = $this->db->query("SELECT * FROM sm_strategies ORDER BY id DESC");
        $strategies = $stmt->fetchAll();
        $this->view('strategies/index', ['strategies' => $strategies]);
    }

    public function edit() {
        $id = $_GET['id'] ?? 0;
        $strategy = null;
        if ($id) {
            $stmt = $this->db->prepare("SELECT * FROM sm_strategies WHERE id = ?");
            $stmt->execute([$id]);
            $strategy = $stmt->fetch();
        }

        // 获取所有歌单供选择
        $playlists = $this->db->query("SELECT id, name FROM sm_playlists ORDER BY name ASC")->fetchAll();

        $this->view('strategies/edit', [
            'strategy' => $strategy,
            'playlists' => $playlists
        ]);
    }

    public function save() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') die("Invalid Request");

        $id = $_POST['id'] ?? 0;
        $name = $_POST['name'] ?? '未命名策略';
        
        // 接收时间轴数据 (Arrays)
        $starts = $_POST['start_time'] ?? [];
        $ends = $_POST['end_time'] ?? [];
        $pids = $_POST['playlist_id'] ?? [];

        $timeline = [];
        for ($i = 0; $i < count($starts); $i++) {
            if (!empty($starts[$i]) && !empty($ends[$i]) && !empty($pids[$i])) {
                $timeline[] = [
                    'start' => $starts[$i],
                    'end' => $ends[$i],
                    'playlist_id' => (int)$pids[$i]
                ];
            }
        }
        
        // 存为 JSON
        $json = json_encode($timeline);

        if ($id) {
            $sql = "UPDATE sm_strategies SET name = ?, timeline_json = ? WHERE id = ?";
            $this->db->prepare($sql)->execute([$name, $json, $id]);
        } else {
            $sql = "INSERT INTO sm_strategies (name, timeline_json) VALUES (?, ?)";
            $this->db->prepare($sql)->execute([$name, $json]);
        }

        $this->redirect('/strategies/index');
    }

    public function delete() {
        $id = $_GET['id'] ?? 0;
        if ($id) {
            $this->db->prepare("DELETE FROM sm_strategies WHERE id = ?")->execute([$id]);
        }
        $this->redirect('/strategies/index');
    }
}