# Toptea SoundMatrix - Android Client Documentation

**版本：** 1.1 (Phase 3 - Step 2 Specification)

(前略...)

## 3. 代码结构说明 (Code Structure)

### 3.3 业务逻辑层 (Business Logic)
* **SyncManager.kt:**
    * 新增 `autoPoll()` 方法，使用 Coroutines 实现 5 分钟循环检查。
    * 在 `processConfig` 完成下载后，发送 `ACTION_PLAYLIST_UPDATED` 广播。
* **DownloadManager.kt:**
    * 优化：下载任务完成后，触发 SyncManager 的回调或发送广播。
* **LogUtils.kt:**
    * **新增功能:** 实现日志轮替 (Ring Buffer)，内存中只保留最近 100 条日志，防止 OOM。

### 3.4 服务层 (Service Layer)
* **service/MusicService.kt:**
    * **初始化:** 启动时注册 `ACTION_PLAYLIST_UPDATED` 广播接收器。
    * **播放逻辑更新:**
        * 不再播放所有 `ready` 歌曲。
        * **必须** 解析当前策略的 `timeSlotsJson`，获取 `playlist_id`。
        * 根据 `playlist_id` 查询 `RemotePlaylist` (需存入本地 DB 或 Config)，提取 `song_ids`。
        * 仅加载这些 `song_ids` 对应的本地文件到 ExoPlayer。
    * **熔断机制:** 若 API 返回 `Device Blocked`，立即调用 `player.stop()`。

### 3.5 UI 层 (Presentation Layer) - Dark Mode Overhaul
* **Design:** 彻底移除白色背景。
    * `activity_main.xml` 根布局背景色 -> `#000000`。
    * CardView 背景色 -> `#1E1E1E` (Surface Color)。
    * TextView 颜色 -> `#E0E0E0` (Primary), `#B0B0B0` (Secondary)。
* **Dashboard Elements:**
    * 新增 `tvMacAddress`: 显示设备 ID。
    * 新增 `tvNowPlaying`: 绑定 MusicService 的播放状态回调。
    * 新增 `tvVolumeWarning`: 监听 `STREAM_MUSIC` 音量，为 0 时显示可见。