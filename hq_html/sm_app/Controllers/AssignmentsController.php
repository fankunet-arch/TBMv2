<?php
namespace SmApp\Controllers;
use SmCore\Database;

class AssignmentsController extends BaseController {
    private $db;

    public function __construct() {
        parent::__construct();
        $this->db = Database::getInstance()->getConnection();
    }

    public function index() {
        // 1. 获取所有策略供下拉框使用
        $strategies = $this->db->query("SELECT id, name FROM sm_strategies")->fetchAll();
        
        // 2. 获取当前的指派规则
        $assignments = $this->db->query("SELECT * FROM sm_assignments")->fetchAll();
        // 将指派规则转为以 condition_key 为键的数组，方便视图查找
        $map = [];
        foreach($assignments as $a) {
            // key格式: "WEEKLY_1", "HOLIDAY", "SPECIAL_2025-12-25"
            $prefix = ($a['priority'] == 1) ? 'WEEKLY_' : (($a['priority'] == 2) ? 'HOLIDAY' : 'SPECIAL_');
            if ($a['priority'] == 2) $prefix = ''; // HOLIDAY直接用
            
            $key = $prefix . $a['condition_key'];
            $map[$key] = $a['strategy_id'];
        }

        // 3. 获取特殊日历
        $calendar = $this->db->query("SELECT * FROM sm_calendar ORDER BY calendar_date DESC LIMIT 50")->fetchAll();

        $this->view('assignments/index', [
            'strategies' => $strategies,
            'map' => $map,
            'calendar' => $calendar
        ]);
    }

    // 保存周循环和通用节假日策略
    public function save_routine() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') die("Invalid");
        
        // 处理周循环 (1-7)
        for ($i=1; $i<=7; $i++) {
            $sid = $_POST["weekly_$i"] ?? 0;
            $this->upsert(1, $i, $sid);
        }
        
        // 处理通用节假日
        $hid = $_POST["holiday_strategy"] ?? 0;
        $this->upsert(2, 'HOLIDAY', $hid);

        $this->redirect('/assignments/index');
    }

    // 添加特殊日期/节假日
    public function add_calendar() {
        $date = $_POST['date'];
        $type = $_POST['type']; // 1=Holiday, 2=Special
        $desc = $_POST['desc'];
        $sid = $_POST['strategy_id'] ?? 0;

        if ($date) {
            // 1. 写入日历表
            $sql = "REPLACE INTO sm_calendar (calendar_date, day_type, description) VALUES (?, ?, ?)";
            $this->db->prepare($sql)->execute([$date, $type, $desc]);

            // 2. 如果是特例(type=2)，还需要写入指派表
            if ($type == 2 && $sid) {
                $this->upsert(3, $date, $sid);
            }
        }
        $this->redirect('/assignments/index');
    }

    // 删除日历
    public function delete_calendar() {
        $date = $_GET['date'];
        if ($date) {
            $this->db->prepare("DELETE FROM sm_calendar WHERE calendar_date = ?")->execute([$date]);
            // 同时删除可能的特例指派
            $this->db->prepare("DELETE FROM sm_assignments WHERE priority = 3 AND condition_key = ?")->execute([$date]);
        }
        $this->redirect('/assignments/index');
    }

    // 辅助函数：插入或更新指派
    private function upsert($priority, $key, $sid) {
        if (!$sid) {
            // 如果策略ID为0或空，则删除该条规则
            $this->db->prepare("DELETE FROM sm_assignments WHERE priority=? AND condition_key=?")->execute([$priority, $key]);
            return;
        }
        // 检查是否存在
        $check = $this->db->prepare("SELECT id FROM sm_assignments WHERE priority=? AND condition_key=?");
        $check->execute([$priority, $key]);
        if ($check->fetch()) {
            $sql = "UPDATE sm_assignments SET strategy_id=? WHERE priority=? AND condition_key=?";
            $this->db->prepare($sql)->execute([$sid, $priority, $key]);
        } else {
            $sql = "INSERT INTO sm_assignments (priority, condition_key, strategy_id) VALUES (?, ?, ?)";
            $this->db->prepare($sql)->execute([$priority, $key, $sid]);
        }
    }
}