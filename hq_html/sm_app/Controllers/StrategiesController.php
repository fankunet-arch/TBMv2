<?php
namespace SmApp\Controllers;

use SmCore\Database;

class StrategiesController extends BaseController {
    private $db;

    public function __construct() {
        parent::__construct();
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

        // 【新增】时间冲突校验
        $overlapError = $this->checkOverlap($timeline);
        if ($overlapError) {
            die("时间段冲突：" . $overlapError);
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

    /**
     * 检查时间段是否重叠
     * @param array $timeline 时间轴数组 [['start' => '08:00', 'end' => '12:00', ...], ...]
     * @return string|null 返回错误信息，无冲突返回 null
     */
    private function checkOverlap($timeline) {
        if (empty($timeline)) {
            return null;
        }

        // 1. 将时间转换为分钟数，便于比较
        $segments = [];
        foreach ($timeline as $slot) {
            $startMinutes = $this->timeToMinutes($slot['start']);
            $endMinutes = $this->timeToMinutes($slot['end']);

            // 验证结束时间必须大于开始时间
            if ($endMinutes <= $startMinutes) {
                return "结束时间 {$slot['end']} 必须晚于开始时间 {$slot['start']}";
            }

            $segments[] = [
                'start' => $startMinutes,
                'end' => $endMinutes,
                'start_str' => $slot['start'],
                'end_str' => $slot['end']
            ];
        }

        // 2. 按开始时间排序
        usort($segments, function($a, $b) {
            return $a['start'] - $b['start'];
        });

        // 3. 检查相邻时间段是否重叠
        for ($i = 0; $i < count($segments) - 1; $i++) {
            $current = $segments[$i];
            $next = $segments[$i + 1];

            // 如果当前段的结束时间 > 下一段的开始时间，则存在重叠
            if ($current['end'] > $next['start']) {
                return "{$current['start_str']}-{$current['end_str']} 与 {$next['start_str']}-{$next['end_str']} 存在重叠";
            }
        }

        return null; // 无冲突
    }

    /**
     * 将时间字符串转换为分钟数
     * @param string $time 格式 "HH:MM" 或 "HH:MM:SS"
     * @return int 从 00:00 开始的分钟数
     */
    private function timeToMinutes($time) {
        $parts = explode(':', $time);
        $hours = (int)$parts[0];
        $minutes = (int)($parts[1] ?? 0);
        return $hours * 60 + $minutes;
    }
}