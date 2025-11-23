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
            // [修改点] 按照您的要求：不跳转，直接报错并终止
            // 返回 403 Forbidden 状态码，表明拒绝访问
            http_response_code(403);

            // 输出人类可读的明确错误信息
            echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><title>会话无效</title></head>';
            echo '<body style="font-family: system-ui, sans-serif; text-align: center; padding-top: 80px; background-color: #f8f9fa;">';

            echo '<div style="display: inline-block; background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); max-width: 600px;">';
                echo '<h1 style="color: #dc3545; margin-top: 0;">⛔ 访问被中止 (Access Stopped)</h1>';
                echo '<p style="font-size: 18px; color: #333;"><strong>未检测到有效的用户登录会话。</strong></p>';
                echo '<p style="color: #666; margin: 20px 0;">SoundMatrix 系统依赖于 CPSYS 总控系统的登录状态。<br>系统未接收到您的身份凭证，或您的会话已过期。</p>';

                // 提供手动跳转链接，而不是自动跳转
                echo '<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">';
                    echo '<p style="font-size: 14px; color: #999;">请通过正规入口登录后再试：</p>';
                    echo '<a href="/html/cpsys/login.php" style="display: inline-block; padding: 10px 20px; background-color: #0d6efd; color: white; text-decoration: none; border-radius: 4px; font-weight: bold;">前往 CPSYS 登录页面</a>';
                echo '</div>';
            echo '</div>';

            echo '</body></html>';
            exit; // ⛔ 绝对禁止继续执行
        }

        // ============================================================
        // 🛡️ 安全检查第二关：角色权限检测 (Super Admin Only)
        // ============================================================
        // [配置项] 请务必确认数据库中 Super Admin 的 ID (默认为 1)
        $SUPER_ADMIN_ROLE_ID = 1;

        if (empty($_SESSION['role_id']) || $_SESSION['role_id'] != $SUPER_ADMIN_ROLE_ID) {
            http_response_code(403);

            echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><title>权限不足</title></head>';
            echo '<body style="font-family: system-ui, sans-serif; text-align: center; padding-top: 80px; background-color: #f8f9fa;">';

            echo '<div style="display: inline-block; background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-top: 5px solid #ffc107;">';
                echo '<h1 style="color: #856404; margin-top: 0;">⚠️ 权限不足 (Access Denied)</h1>';
                echo '<p style="font-size: 16px; color: #333;">您已登录，但您的账户权限无法访问此系统。</p>';
                echo '<ul style="text-align: left; color: #666; background: #f8f9fa; padding: 15px 30px; border-radius: 4px;">';
                    echo '<li><strong>当前账户:</strong> ' . htmlspecialchars($_SESSION['username'] ?? 'Unknown') . '</li>';
                    echo '<li><strong>当前角色ID:</strong> ' . ($_SESSION['role_id'] ?? 'N/A') . '</li>';
                    echo '<li><strong>要求权限:</strong> Super Administrator (ID: '.$SUPER_ADMIN_ROLE_ID.')</li>';
                echo '</ul>';
                echo '<br>';
                echo '<a href="/html/cpsys/index.php" style="color: #6c757d; text-decoration: underline;">&larr; 返回总控台 Dashboard</a>';
            echo '</div>';

            echo '</body></html>';
            exit; // ⛔ 绝对禁止继续执行
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
