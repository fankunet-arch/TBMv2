<?php
namespace SmApp\Controllers;

use SmCore\Database;

class PlaylistsController extends BaseController {

    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * 列表页
     */
    public function index() {
        // 查询所有歌单，并统计每个歌单里有多少首歌 (粗略估计)
        $stmt = $this->db->query("SELECT * FROM sm_playlists ORDER BY id DESC");
        $playlists = $stmt->fetchAll();

        // 处理一下 song_ids_json 的显示，算出歌曲数量
        foreach ($playlists as &$p) {
            $ids = json_decode($p['song_ids_json'], true);
            $p['count'] = is_array($ids) ? count($ids) : 0;
        }

        $this->view('playlists/index', ['playlists' => $playlists]);
    }

    /**
     * 编辑/新建页面
     */
    public function edit() {
        $id = $_GET['id'] ?? 0;
        $playlist = null;
        $selected_ids = [];

        // 如果是编辑模式，查出当前歌单详情
        if ($id) {
            $stmt = $this->db->prepare("SELECT * FROM sm_playlists WHERE id = ?");
            $stmt->execute([$id]);
            $playlist = $stmt->fetch();
            if ($playlist) {
                $selected_ids = json_decode($playlist['song_ids_json'], true) ?? [];
            }
        }

        // 查出所有可用歌曲 (用于勾选)
        $stmt2 = $this->db->query("SELECT * FROM sm_songs WHERE is_active = 1 ORDER BY title ASC");
        $all_songs = $stmt2->fetchAll();

        $this->view('playlists/edit', [
            'playlist' => $playlist,
            'all_songs' => $all_songs,
            'selected_ids' => $selected_ids
        ]);
    }

    /**
     * 保存逻辑 (新建或更新)
     */
    public function save() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') die("Invalid Request");

        $id = $_POST['id'] ?? 0;
        $name = $_POST['name'] ?? '未命名歌单';
        $play_mode = $_POST['play_mode'] ?? 'sequence';
        $song_ids = $_POST['song_ids'] ?? []; // 这是一个数组

        // 必须转为 JSON 存入数据库
        // 注意：为了保证数据一致性，把 ID 转为整型
        $song_ids = array_map('intval', $song_ids);
        $json = json_encode($song_ids);

        if ($id) {
            // Update
            $sql = "UPDATE sm_playlists SET name = ?, play_mode = ?, song_ids_json = ? WHERE id = ?";
            $this->db->prepare($sql)->execute([$name, $play_mode, $json, $id]);
        } else {
            // Insert
            $sql = "INSERT INTO sm_playlists (name, play_mode, song_ids_json) VALUES (?, ?, ?)";
            $this->db->prepare($sql)->execute([$name, $play_mode, $json]);
        }

        $this->redirect('/playlists/index');
    }

    /**
     * 删除歌单
     */
    public function delete() {
        $id = $_GET['id'] ?? 0;
        if ($id) {
            $this->db->prepare("DELETE FROM sm_playlists WHERE id = ?")->execute([$id]);
        }
        $this->redirect('/playlists/index');
    }
}