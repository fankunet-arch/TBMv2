Toptea SoundMatrix - Android Client Documentation
Version: 1.0 (Initial Release) Date: 2025-11-23 Package Name: com.toptea.tbm Architecture: MVVM (Model-View-ViewModel) / Service-Based
1. 项目概述 (Overview)
Toptea SoundMatrix 安卓客户端是门店背景音乐系统的执行终端。它负责与服务器同步策略、下载音乐文件，并利用 Android 前台服务实现“不死鸟”级别的稳定播放。
核心特性
•	离线优先: 所有策略和歌曲均下载至本地数据库和文件系统，断网不影响播放。
•	智能同步: 通过 SyncManager 自动比对服务器版本，仅下载增量内容。
•	稳定播放: 使用 Foreground Service (前台服务) + WakeLock (唤醒锁) 防止被系统杀后台。
•	无缝循环: 基于 ExoPlayer 实现单曲或列表的无缝循环播放。
•	WDS 采集: 内置 WdsEngine，智能计算时间窗口，静默采集第三方数据。
2. 技术栈 (Tech Stack)
•	Language: Kotlin
•	Minimum SDK: API 24 (Android 7.0)
•	Networking: Retrofit 2 + OkHttp 3
•	Database: Room 2.6 (SQLite ORM)
•	Media Player: AndroidX Media3 (ExoPlayer) 1.2.0
•	Concurrency: Kotlin Coroutines (协程)
•	Data Format: JSON (Gson)
3. 代码结构说明 (Code Structure)
所有核心代码位于 app/src/main/java/com/toptea/tbm/ 包下。
3.1 数据层 (Data Layer)
•	AppDatabase.kt: 数据库实例入口。
•	AppDao.kt: 数据库操作接口 (CRUD)。包含 getPendingSongs (查未下载歌曲) 等核心方法。
•	AppEntities.kt: 数据表定义。
o	LocalSong: 本地歌曲表 (含 downloadUrl, localPath, md5)。
o	PlaySchedule: 播放策略表 (含 JSON 格式的时间轴配置)。
o	AppConfig: 键值对配置表 (存 MAC 地址、版本号)。
3.2 网络层 (Network Layer)
•	NetworkClient.kt: Retrofit 实例工厂，配置了超时和拦截器。
•	ApiService.kt: 定义服务器接口 (/smsys/api/check_update)。
•	ApiModels.kt: 定义服务器交互的 JSON 数据模型 (CheckUpdateRequest, ApiResponse, FullConfig)。
3.3 业务逻辑层 (Business Logic)
•	SyncManager.kt: [大脑] 核心调度器。
o	负责调用 API 检查更新。
o	负责解析 FullConfig 并写入数据库。
o	负责触发 DownloadManager 和 WdsEngine。
•	DownloadManager.kt: [物流] 文件下载器。
o	查询 AppDao 获取未完成任务。
o	执行下载、校验 MD5。
o	更新数据库状态为 Ready。
•	WdsEngine.kt: [旁路] 智能采集引擎。
o	独立于主业务运行。
o	逻辑：请求 -> 计算下一窗口 -> 休眠等待 -> 循环。
3.4 服务层 (Service Layer)
•	service/MusicService.kt: [心脏] 播放服务。
o	实现 Foreground Service，显示常驻通知。
o	持有 ExoPlayer 实例。
o	持有 WakeLock 防止 CPU 休眠。
o	逻辑：查询当天策略 -> 加载本地 MP3 -> 循环播放。
•	receiver/BootReceiver.kt: 监听开机广播，自动启动 MusicService。
3.5 UI 层 (Presentation Layer)
•	MainActivity.kt: 唯一的界面。
o	显示服务状态。
o	显示实时运行日志 (通过 BroadcastReceiver 接收)。
o	提供手动强制同步入口 (点击顶部卡片)。

