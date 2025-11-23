# Toptea SoundMatrix - 技术规格说明书 (DB & API)

**版本：** 1.1 (Security Update)
**关联项目：** `toptea-cpsys` (Shared DB)

## 1. 数据库设计 (MySQL)
*(保持原表结构不变，重点强调 `sm_devices` 的状态定义)*

**`sm_devices` (设备表)**
* `id` (PK, INT)
* `shop_id` (INT): 关联门店ID (激活时绑定)
* `mac_address` (VARCHAR): 硬件标识 (Unique)
* `status` (TINYINT): **0=未激活(拒绝访问), 1=已激活(正常服务), 2=禁用**
* `last_heartbeat` (DATETIME): 最后在线时间

## 2. API 通信协议

### 2.0 安全头 (Security Headers)
所有请求必须包含：
`X-Toptea-Secret: [我们在代码中约定的硬编码密钥]`

### 2.1 心跳与检查更新
**Endpoint:** `POST /api/soundmatrix/v1/heartbeat` (或 `check_update`)
**Request:**
```json
{
  "mac_address": "AA:BB:CC:DD:EE:FF",
  "current_config_version": "1732250000"
}


Response (场景 A: 设备未激活):

HTTP Code: 200 OK

{
  "status": "error",
  "code": 403,
  "message": "Device Not Activated. Please contact admin.",
  "device_info": { "mac": "AA:BB:..." }
}
客户端行为：显示“设备未激活”全屏提示，每分钟重试一次。

Response (场景 B: 正常 - 无更新):
{ "status": "latest" }

Response (场景 C: 正常 - 有更新):
{
  "status": "update_required",
  "new_version": "1732260000",
  "config": {
    "resources": [ ... ],
    "playlists": { ... },
    "assignments": { ... },
    "holiday_dates": [ ... ]
  }
}
3. 安卓端处理流程 (Android Processing)
Boot: 开机自启 -> 启动 KeepAliveService (前台服务)。

Auth: 向 API 发起握手。

若返回 403 Not Activated: 停留在“等待激活”界面，轮询。

若返回 Success: 进入播放逻辑。

Playback:

申请 PARTIAL_WAKE_LOCK。

根据 assignments 策略计算当前应播歌单。

使用 ExoPlayer 播放本地文件。
---

### 我的建议 (Next Step)

您可以将这两段内容复制并创建为两个 `.md` 文件。

**当您下次准备开始写代码，或者需要调整需求时，只需对我说：**

> “Gemini，请读取 `docs/soundmatrix_master_plan.md` 和 `docs/soundmatrix_tech_specs.md`，我想开始开发后台的数据库迁移脚本...”

我就会完全理解您的上下文，不会出现任何幻想或偏差。您现在是否需要我为您生成第一步的**SQL 建表语句**代码？
