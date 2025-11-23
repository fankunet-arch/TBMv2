<?php
namespace SmApp\Controllers;

use SmCore\Database;

class SongsController extends BaseController {

    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * 列表页：显示所有歌曲 + 上传表单
     */
    public function index() {
        $stmt = $this->db->query("SELECT * FROM sm_songs ORDER BY id DESC");
        $songs = $stmt->fetchAll();

        $this->view('songs/index', ['songs' => $songs]);
    }

    /**
     * 处理上传动作
     */
    public function upload() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['mp3_file'])) {
            die("Invalid Request");
        }

        $file = $_FILES['mp3_file'];
        
        // 1. 基础检查
        if ($file['error'] !== UPLOAD_ERR_OK) {
            die("Upload Error Code: " . $file['error']);
        }
        
        // 2. 校验格式 (简单校验后缀)
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['mp3', 'aac', 'm4a'])) {
            die("Format not supported. Only MP3/AAC allowed.");
        }

        // 3. 计算 MD5 (核心去重逻辑)
        $md5 = md5_file($file['tmp_name']);

        // 4. 查重：数据库里有没有这个 MD5?
        $stmt = $this->db->prepare("SELECT id FROM sm_songs WHERE file_md5 = ?");
        $stmt->execute([$md5]);
        if ($stmt->fetch()) {
            // 可以在这里提示“文件已存在”，或者静默成功
            echo "<script>alert('文件已存在 (MD5重复)，无需重复上传'); window.location.href='/smsys/songs/index';</script>";
            exit;
        }

        // 5. 移动文件
        // 目标目录: /hq_html/html/smsys/uploads/
        $uploadDir = ROOT_PATH . '/html/smsys/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // 为了避免文件名乱码，建议用 MD5 重命名，或者保留原名但加前缀
        // 这里我们采用: md5.mp3 格式，保证文件名纯净
        $newFileName = $md5 . '.' . $ext;
        $destPath = $uploadDir . $newFileName;
        
        // 相对路径 (用于存库和http访问)
        // 假设域名是 hqv3.toptea.es/smsys/
        $webUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/smsys/uploads/' . $newFileName;

        if (move_uploaded_file($file['tmp_name'], $destPath)) {
            // 6. 获取时长 (这是个难点，纯PHP获取时长需要 getid3 库，这里暂时填 0，后续处理)
            // 或者让安卓端下载完后自己上报时长
            $duration = 0; 
            
            // 7. 入库
            $insert = $this->db->prepare("INSERT INTO sm_songs (title, artist, file_url, file_md5, file_size, duration) VALUES (?, ?, ?, ?, ?, ?)");
            $insert->execute([
                $file['name'], // 暂时用文件名做标题
                'Unknown',     // 暂时默认歌手
                $webUrl,
                $md5,
                $file['size'],
                $duration
            ]);

            $this->redirect('/songs/index');
        } else {
            die("Failed to move uploaded file.");
        }
    }
    
    /**
     * 删除歌曲
     */
    public function delete() {
        $id = $_GET['id'] ?? 0;
        if ($id) {
            // 实际项目中建议做软删除
            // 这里演示硬删除，同时删除文件
            $stmt = $this->db->prepare("SELECT * FROM sm_songs WHERE id = ?");
            $stmt->execute([$id]);
            $song = $stmt->fetch();
            
            if ($song) {
                // 删除数据库记录
                $this->db->prepare("DELETE FROM sm_songs WHERE id = ?")->execute([$id]);
                
                // 尝试删除物理文件 (谨慎: 如果多个记录指向同一个MD5文件，不能删文件。这里暂不删文件，只删记录)
                // unlink(...); 
            }
        }
        $this->redirect('/songs/index');
    }
}