<?php
namespace SmApp\Controllers;

class BaseController {

    public function __construct() {
        // 1. 启动 Session (与 CPSYS 系统共享会话)
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // ============================================================
        // 🛡️ 安全检查第一关：登录状态检测
        // ============================================================
        if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            http_response_code(403);
            echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><title>访问被中止</title></head>';
            echo '<body style="font-family: system-ui, sans-serif; text-align: center; padding-top: 100px; background-color: #f8f9fa;">';
            echo '<h1 style="color: #dc3545;">⛔ 访问被中止 (Access Stopped)</h1>';
            echo '<p style="font-size: 18px; color: #333;">未检测到有效的用户登录会话。</p>';
            echo '</body></html>';
            exit;
        }

        // ============================================================
        // 🛡️ 安全检查第二关：角色权限检测 (Super Admin Only)
        // ============================================================
        $SUPER_ADMIN_ROLE_ID = 1;

        if (empty($_SESSION['role_id']) || $_SESSION['role_id'] != $SUPER_ADMIN_ROLE_ID) {
            http_response_code(403);
            echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><title>权限不足</title></head>';
            echo '<body style="font-family: system-ui, sans-serif; text-align: center; padding-top: 100px; background-color: #f8f9fa;">';
            echo '<h1 style="color: #856404;">⚠️ 权限不足 (Access Denied)</h1>';
            echo '<p style="font-size: 18px; color: #333;">您的账户权限无法访问此系统。</p>';
            echo '</body></html>';
            exit;
        }
    }

    // ... (保留原有的 view, json, redirect 方法) ...

    protected function view($viewName, $data = []) {
        extract($data);
        $viewFile = APP_PATH . '/Views/' . $viewName . '.php';
        if (file_exists($viewFile)) {
            require_once APP_PATH . '/Views/layout/header.php';
            require $viewFile;
            require_once APP_PATH . '/Views/layout/footer.php';
        } else {
            die("View file not found: " . $viewName);
        }
    }

    protected function json($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function redirect($url) {
        header("Location: /smsys" . $url);
        exit;
    }
}
