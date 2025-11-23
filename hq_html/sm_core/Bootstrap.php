<?php
namespace SmCore;

class Bootstrap {
    
    public function __construct() {
        // 注册自动加载函数
        spl_autoload_register([$this, 'autoload']);
    }

    /**
     * 自动加载类文件
     * 规则: 
     * SmCore\Database -> /hq_html/sm_core/Database.php
     * SmApp\Controllers\HomeController -> /hq_html/sm_app/Controllers/HomeController.php
     */
    private function autoload($className) {
        $parts = explode('\\', $className);
        $rootNamespace = array_shift($parts);
        
        $path = '';
        if ($rootNamespace === 'SmCore') {
            $path = CORE_PATH . DS . implode(DS, $parts) . '.php';
        } elseif ($rootNamespace === 'SmApp') {
            $path = APP_PATH . DS . implode(DS, $parts) . '.php';
        }

        if ($path && file_exists($path)) {
            require_once $path;
        }
    }

    /**
     * 极简路由解析与分发
     * URL 模式: /smsys/controller/action
     * 默认: HomeController -> index()
     */
    public function run() {
        $uri = $_SERVER['REQUEST_URI'];
        
        // 移除 URL 中的 /smsys/ 前缀和 GET 参数
        $scriptName = dirname($_SERVER['SCRIPT_NAME']); //通常是 /smsys
        if (strpos($uri, $scriptName) === 0) {
            $uri = substr($uri, strlen($scriptName));
        }
        $uri = explode('?', $uri)[0];
        $parts = array_values(array_filter(explode('/', $uri)));

        // 解析控制器 (默认: Home)
        $controllerName = !empty($parts[0]) ? ucfirst($parts[0]) : 'Home';
        $controllerClass = "SmApp\\Controllers\\{$controllerName}Controller";

        // 解析方法 (默认: index)
        $actionName = !empty($parts[1]) ? $parts[1] : 'index';

        // 检查并执行
        if (class_exists($controllerClass)) {
            $controller = new $controllerClass();
            if (method_exists($controller, $actionName)) {
                $controller->$actionName();
            } else {
                throw new \Exception("Action '{$actionName}' not found in {$controllerName}.");
            }
        } else {
            // 如果是 API 请求，返回 JSON 错误
            if (strtolower($controllerName) === 'api') {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Invalid API Endpoint']);
            } else {
                throw new \Exception("Controller '{$controllerName}' not found.");
            }
        }
    }
}