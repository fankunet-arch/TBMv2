<?php
/**
 * Toptea SoundMatrix - Entry Point
 * Access: http://hq.toptea.es/smsys/
 */

// 1. 定义基础路径常量
define('DS', DIRECTORY_SEPARATOR);
define('ROOT_PATH', dirname(dirname(dirname(__FILE__)))); // 指向 /hq_html/
define('APP_PATH',  ROOT_PATH . DS . 'sm_app');
define('CORE_PATH', ROOT_PATH . DS . 'sm_core');

// 2. 简单的错误报告 (开发环境开启，生产环境应关闭)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 3. 引入核心引导文件
require_once CORE_PATH . DS . 'Bootstrap.php';

// 4. 启动应用
try {
    $app = new \SmCore\Bootstrap();
    $app->run();
} catch (Exception $e) {
    // 简单的错误兜底
    header("HTTP/1.1 500 Internal Server Error");
    echo "<h1>SoundMatrix System Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}