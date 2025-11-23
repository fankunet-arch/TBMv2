<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SoundMatrix Admin</title>
    <!-- 引入基础样式 -->
    <style>
        /* 全局样式 */
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; background: #f4f4f4; color: #333; }
        .container { max-width: 1200px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        
        /* 导航栏样式 */
        nav { margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px; display: flex; align-items: center; }
        .brand { font-size: 1.4em; font-weight: bold; margin-right: 30px; color: #2c3e50; text-decoration: none; }
        
        /* 菜单链接 */
        .menu-item { margin-right: 20px; text-decoration: none; color: #555; font-weight: 600; padding: 5px 10px; border-radius: 4px; transition: all 0.2s; }
        .menu-item:hover { background: #f0f0f0; color: #007bff; }
        .menu-item.active { color: #007bff; background: #e8f0fe; }

        /* 表格样式 */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background: #f8f9fa; color: #495057; font-weight: bold; }
        tr:hover { background-color: #f1f1f1; }

        /* 按钮样式 */
        .btn { padding: 6px 12px; background: #007bff; color: #fff; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; font-size: 14px; }
        .btn:hover { background: #0056b3; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        
        /* 提示框 */
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .alert-info { background-color: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>
<div class="container">
    <nav>
        <a href="/smsys/" class="brand">SoundMatrix</a>
        
        <!-- 菜单链接 (全量展示) -->
        <a href="/smsys/songs/index" class="menu-item">🎵 曲库管理</a>
        <a href="/smsys/playlists/index" class="menu-item">💿 歌单管理</a>
        <a href="/smsys/strategies/index" class="menu-item">🕒 策略排期</a>
        <a href="/smsys/assignments/index" class="menu-item">📅 规则指派</a>
        <a href="/smsys/devices/index" class="menu-item">📱 设备监控</a>
    </nav>

    <!-- 页面主体内容开始 -->