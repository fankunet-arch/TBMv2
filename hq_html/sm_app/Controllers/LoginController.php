<?php
namespace SmApp\Controllers;

use SmCore\Database;

class LoginController extends BaseController {
    private $db;

    public function __construct() {
        // 先调用父类构造函数（Session 启动，但 LoginController 在白名单中无需鉴权）
        parent::__construct();
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * 显示登录页面
     */
    public function index() {
        // 如果已经登录，重定向到首页
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/');
        }

        // 渲染登录视图（不包含 header/footer）
        $viewFile = APP_PATH . '/Views/login/index.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            die("Login view not found");
        }
    }

    /**
     * 处理登录 POST 请求
     */
    public function doLogin() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            die("Invalid Request");
        }

        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $this->showLoginError('用户名和密码不能为空');
            return;
        }

        // 验证 cpsys_users 表
        $stmt = $this->db->prepare("SELECT * FROM cpsys_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user) {
            $this->showLoginError('用户名不存在');
            return;
        }

        // 验证密码（假设数据库存储的是 MD5 加密的密码）
        // 如果是明文密码，直接比较；如果是 hash，使用 password_verify
        $passwordMatch = false;

        // 尝试 MD5 比对
        if (strlen($user['password']) === 32) {
            $passwordMatch = (md5($password) === $user['password']);
        } else {
            // 尝试 password_verify (如果是 bcrypt/argon2)
            $passwordMatch = password_verify($password, $user['password']);
        }

        // 如果都不匹配，尝试明文比对（不推荐，但有些老系统可能这样）
        if (!$passwordMatch) {
            $passwordMatch = ($password === $user['password']);
        }

        if (!$passwordMatch) {
            $this->showLoginError('密码错误');
            return;
        }

        // 登录成功，设置 Session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['login_time'] = time();

        // 重定向到首页
        $this->redirect('/');
    }

    /**
     * 退出登录
     */
    public function logout() {
        session_destroy();
        $this->redirect('/login/index');
    }

    /**
     * 显示登录错误
     */
    private function showLoginError($message) {
        $error = $message;
        $viewFile = APP_PATH . '/Views/login/index.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        }
        exit;
    }
}
