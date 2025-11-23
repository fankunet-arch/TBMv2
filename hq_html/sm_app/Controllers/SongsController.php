<?php
namespace SmApp\Controllers;

use SmCore\Database;

class SongsController extends BaseController {

    private $db;

    public function __construct() {
        parent::__construct();
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * 解析 MP3 文件时长（秒）
     * 优先使用 ffprobe (轻量级且准确)
     * 失败则尝试简单的 MP3 帧分析
     *
     * @param string $filePath 文件绝对路径
     * @return int 时长（秒），失败返回 0
     */
    private function getMp3Duration($filePath) {
        // 方法1: 尝试使用 ffprobe (大多数服务器都有 ffmpeg)
        $ffprobe = shell_exec("ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($filePath) . " 2>&1");
        if ($ffprobe && is_numeric(trim($ffprobe))) {
            return (int)round(floatval(trim($ffprobe)));
        }

        // 方法2: 简单 MP3 帧头解析（CBR 格式）
        // 读取文件的前几帧来估算
        $fp = fopen($filePath, 'rb');
        if (!$fp) return 0;

        $fileSize = filesize($filePath);
        $duration = 0;

        // 跳过 ID3v2 标签（如果存在）
        $header = fread($fp, 10);
        if (substr($header, 0, 3) === 'ID3') {
            $flags = ord($header[5]);
            $size = (ord($header[6]) << 21) | (ord($header[7]) << 14) | (ord($header[8]) << 7) | ord($header[9]);
            fseek($fp, $size + 10);
        } else {
            fseek($fp, 0);
        }

        // 尝试读取第一个有效的 MP3 帧头
        $frameHeader = fread($fp, 4);
        if (strlen($frameHeader) === 4) {
            $byte1 = ord($frameHeader[0]);
            $byte2 = ord($frameHeader[1]);
            $byte3 = ord($frameHeader[2]);

            // 检查帧同步字 (11111111 111)
            if ($byte1 === 0xFF && ($byte2 & 0xE0) === 0xE0) {
                // 解析 MPEG 版本和层
                $version = ($byte2 >> 3) & 0x03;
                $layer = ($byte2 >> 1) & 0x03;
                $bitrateIndex = ($byte3 >> 4) & 0x0F;
                $sampleRateIndex = ($byte3 >> 2) & 0x03;

                // MPEG1 Layer3 (MP3) 比特率表 (kbps)
                $bitrates = [0, 32, 40, 48, 56, 64, 80, 96, 112, 128, 160, 192, 224, 256, 320, 0];
                $sampleRates = [44100, 48000, 32000];

                if ($bitrateIndex > 0 && $bitrateIndex < 15 && $sampleRateIndex < 3) {
                    $bitrate = $bitrates[$bitrateIndex] * 1000; // 转为 bps
                    $sampleRate = $sampleRates[$sampleRateIndex];

                    if ($bitrate > 0) {
                        // 估算时长: 文件大小(字节) / (比特率/8) = 秒
                        $duration = (int)round($fileSize / ($bitrate / 8));
                    }
                }
            }
        }

        fclose($fp);
        return $duration;
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

        // [BUG修复] 添加MIME类型验证，防止文件伪装
        $allowedMimeTypes = ['audio/mpeg', 'audio/mp3', 'audio/aac', 'audio/x-m4a', 'audio/mp4'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedMimeTypes)) {
            die("Invalid file type. MIME type check failed: " . htmlspecialchars($mimeType));
        }

        // [BUG修复] 限制文件大小 (例如: 最大50MB)
        $maxFileSize = 50 * 1024 * 1024; // 50MB
        if ($file['size'] > $maxFileSize) {
            die("File too large. Maximum size is 50MB.");
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
        // 动态判断协议（适配 HTTPS 环境）
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $webUrl = $protocol . $_SERVER['HTTP_HOST'] . '/smsys/uploads/' . $newFileName;

        if (move_uploaded_file($file['tmp_name'], $destPath)) {
            // 6. 自动解析时长
            $duration = $this->getMp3Duration($destPath);

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