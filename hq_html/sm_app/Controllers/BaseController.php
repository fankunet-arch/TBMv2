<?php
namespace SmApp\Controllers;

class BaseController {

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