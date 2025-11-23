<?php
namespace SmApp\Controllers;

class BaseController {

    /**
     * 构造函数 - 统一鉴权逻辑
     */
    public function __construct() {
        // 1. 启动 Session（如果尚未启动）
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 2. 获取当前控制器类名
        $currentClass = get_class($this);

        // 3. 白名单：LoginController 和 ApiController 无需登录验证
        $whiteList = [
            'SmApp\\Controllers\\LoginController',
            'SmApp\\Controllers\\ApiController'
        ];

        // 4. 如果不在白名单中，检查登录状态
        if (!in_array($currentClass, $whiteList)) {
            if (!isset($_SESSION['user_id'])) {
                // 未登录，强制重定向到登录页
                header("Location: /smsys/login/index");
                exit;
            }
        }
    }

    /**
     * 渲染视图文件
     * @param string $viewName 视图路径 (e.g., 'songs/index')
     * @param array $data 传递给视图的数据
     */
    protected function view($viewName, $data = []) {
        // 解包数组为变量，例如 ['songs' => $list] 变成 $songs
        extract($data);

        $viewFile = APP_PATH . '/Views/' . $viewName . '.php';
        
        if (file_exists($viewFile)) {
            // 可以在这里包含 header.php
            require_once APP_PATH . '/Views/layout/header.php';
            require $viewFile;
            // 可以在这里包含 footer.php
            require_once APP_PATH . '/Views/layout/footer.php';
        } else {
            die("View file not found: " . $viewName);
        }
    }

    /**
     * JSON 响应 helper
     */
    protected function json($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * 简单的重定向
     */
    protected function redirect($url) {
        header("Location: /smsys" . $url); // 注意这里硬编码了 /smsys 前缀
        exit;
    }
}