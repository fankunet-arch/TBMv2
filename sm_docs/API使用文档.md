# SoundMatrix API 使用文档

## 文档版本
- **版本**: v1.0
- **最后更新**: 2025-11-23
- **适用于**: App开发工程师

---

## 目录
1. [概述](#概述)
2. [鉴权机制](#鉴权机制)
3. [API端点列表](#api端点列表)
4. [详细接口说明](#详细接口说明)
5. [错误处理](#错误处理)
6. [数据模型](#数据模型)
7. [安全注意事项](#安全注意事项)
8. [常见问题](#常见问题)

---

## 概述

SoundMatrix API 是一个音乐播放配置管理系统的后端接口，为Android终端设备提供音乐资源、播放策略的同步服务。

### 基本信息
- **Base URL**: `https://hq.toptea.es/smsys/api/`
- **协议**: HTTPS
- **数据格式**: JSON
- **字符编码**: UTF-8
- **鉴权方式**: HTTP Header (`X-Toptea-Secret`)

### 核心特性
- ✅ **无状态设计**: API不依赖Session，完全基于Header鉴权
- ✅ **设备管理**: 自动注册和激活管理
- ✅ **增量更新**: 基于版本号的智能更新机制
- ✅ **安全加固**: 输入验证、异常处理、防伪造攻击

---

## 鉴权机制

### HTTP Header 鉴权

所有API请求必须在HTTP Header中携带安全密钥：

```http
X-Toptea-Secret: TOPTEA_SECURE_KEY_2025
```

### 鉴权失败响应

如果密钥不正确或缺失，服务器将返回：

```http
HTTP/1.1 403 Forbidden
Content-Type: application/json

{
  "status": "error",
  "message": "Unauthorized"
}
```

### Android Kotlin 示例

```kotlin
class NetworkClient {
    companion object {
        private const val API_SECRET = "TOPTEA_SECURE_KEY_2025"
        private const val BASE_URL = "https://hq.toptea.es/smsys/api/"
    }

    private val client = OkHttpClient.Builder()
        .connectTimeout(30, TimeUnit.SECONDS)
        .readTimeout(30, TimeUnit.SECONDS)
        .build()

    fun checkUpdate(macAddress: String, currentVersion: String): Response {
        val json = JSONObject().apply {
            put("mac_address", macAddress)
            put("current_version", currentVersion)
        }

        val request = Request.Builder()
            .url("${BASE_URL}check_update")
            .addHeader("X-Toptea-Secret", API_SECRET)
            .addHeader("Content-Type", "application/json")
            .post(json.toString().toRequestBody("application/json".toMediaType()))
            .build()

        return client.newCall(request).execute()
    }
}
```

---

## API端点列表

| 端点 | 方法 | 功能 | 鉴权 |
|------|------|------|------|
| `/api/heartbeat` | GET/POST | 心跳检测 | ✅ 必需 |
| `/api/check_update` | POST | 配置同步 | ✅ 必需 |

---

## 详细接口说明

### 1. 心跳检测 (Heartbeat)

#### 基本信息
- **端点**: `/api/heartbeat`
- **方法**: `GET` 或 `POST`
- **功能**: 检测服务器在线状态

#### 请求示例

```http
GET /smsys/api/heartbeat HTTP/1.1
Host: hq.toptea.es
X-Toptea-Secret: TOPTEA_SECURE_KEY_2025
```

#### 成功响应

```json
{
  "status": "success",
  "msg": "System Online"
}
```

#### 响应字段说明

| 字段 | 类型 | 说明 |
|------|------|------|
| `status` | string | 状态码: `success` |
| `msg` | string | 消息: `System Online` |

---

### 2. 配置同步 (Check Update)

#### 基本信息
- **端点**: `/api/check_update`
- **方法**: `POST`
- **功能**: 获取设备配置和音乐资源列表

#### 请求体

```json
{
  "mac_address": "AA:BB:CC:DD:EE:FF",
  "current_version": "1732348800"
}
```

#### 请求字段说明

| 字段 | 类型 | 必需 | 格式要求 | 说明 |
|------|------|------|----------|------|
| `mac_address` | string | ✅ | 正则: `^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$` | 设备MAC地址 |
| `current_version` | string | ❌ | 纯数字字符串 | 客户端当前版本号（Unix时间戳） |

#### MAC地址格式示例

✅ **有效格式**:
- `AA:BB:CC:DD:EE:FF`
- `aa:bb:cc:dd:ee:ff`
- `AA-BB-CC-DD-EE-FF`

❌ **无效格式**:
- `AABBCCDDEEFF` (缺少分隔符)
- `AA:BB:CC:DD:EE` (长度不足)
- `GG:HH:II:JJ:KK:LL` (非十六进制字符)

#### 响应场景

##### 场景1: 设备未激活

当设备首次连接或未被管理员激活时：

```json
{
  "status": "error",
  "message": "Device Not Activated"
}
```

**处理建议**: 显示"设备待激活"提示，并定期重试（建议间隔: 5分钟）

---

##### 场景2: 设备被禁用

当设备被管理员禁用时：

```json
{
  "status": "error",
  "message": "Device Blocked"
}
```

**处理建议**: 显示"设备已被禁用"提示，停止播放音乐

---

##### 场景3: 配置为最新

当客户端版本号与服务器一致时：

```json
{
  "status": "latest"
}
```

**处理建议**: 不需要更新配置，继续使用本地配置

---

##### 场景4: 需要更新

当服务器有新配置时：

```json
{
  "status": "update_required",
  "new_version": "1732348800",
  "config": {
    "resources": [
      {
        "id": 1,
        "md5": "d41d8cd98f00b204e9800998ecf8427e",
        "url": "https://hq.toptea.es/smsys/uploads/d41d8cd98f00b204e9800998ecf8427e.mp3",
        "size": 3456789
      }
    ],
    "playlists": {
      "1": {
        "mode": "sequence",
        "ids": [1, 2, 5]
      },
      "2": {
        "mode": "random",
        "ids": [3, 4, 6, 7]
      }
    },
    "assignments": {
      "specials": {
        "2025-12-25": [
          {
            "start": "08:00",
            "end": "12:00",
            "playlist_id": 1
          }
        ]
      },
      "holidays": [
        {
          "start": "10:00",
          "end": "22:00",
          "playlist_id": 2
        }
      ],
      "weekdays": {
        "1": [
          {
            "start": "08:00",
            "end": "18:00",
            "playlist_id": 1
          }
        ]
      }
    },
    "holiday_dates": [
      "2025-01-01",
      "2025-12-25"
    ]
  }
}
```

#### 响应字段详解

##### 顶层字段

| 字段 | 类型 | 说明 |
|------|------|------|
| `status` | string | 状态: `update_required` |
| `new_version` | string | 新版本号（Unix时间戳字符串） |
| `config` | object | 配置对象 |

##### config.resources (音乐资源列表)

| 字段 | 类型 | 说明 |
|------|------|------|
| `id` | integer | 歌曲ID |
| `md5` | string | 文件MD5校验值（32位） |
| `url` | string | 下载URL（HTTPS） |
| `size` | integer | 文件大小（字节） |

**下载流程建议**:
1. 检查本地是否存在该MD5文件
2. 如不存在，从`url`下载
3. 下载后验证MD5是否匹配
4. 验证通过后保存到本地

---

##### config.playlists (歌单配置)

歌单以`对象`形式返回，键为歌单ID：

| 字段 | 类型 | 可选值 | 说明 |
|------|------|--------|------|
| `mode` | string | `sequence`, `random` | 播放模式 |
| `ids` | array | 整数数组 | 歌曲ID列表 |

**播放模式**:
- `sequence`: 顺序播放
- `random`: 随机播放（使用洗牌算法）

---

##### config.assignments (播放规则)

规则分为三个优先级：

```
优先级 3 (最高): specials  - 特定日期规则
优先级 2 (中等): holidays  - 节假日规则
优先级 1 (最低): weekdays  - 星期规则 (1=周一, 7=周日)
```

**时间轴格式**:

| 字段 | 类型 | 格式 | 说明 |
|------|------|------|------|
| `start` | string | `HH:MM` | 开始时间 (24小时制) |
| `end` | string | `HH:MM` | 结束时间 (24小时制) |
| `playlist_id` | integer | 正整数 | 关联的歌单ID |

**规则匹配算法**:

```kotlin
fun getCurrentPlaylist(now: LocalDateTime): Int? {
    val today = now.toLocalDate()
    val currentTime = now.toLocalTime()

    // 1. 检查特定日期规则 (优先级最高)
    val specialRule = assignments.specials[today.toString()]
    if (specialRule != null) {
        return findMatchingPlaylist(specialRule, currentTime)
    }

    // 2. 检查节假日规则
    if (today.toString() in holidayDates) {
        return findMatchingPlaylist(assignments.holidays, currentTime)
    }

    // 3. 检查星期规则
    val dayOfWeek = today.dayOfWeek.value // 1-7
    val weekdayRule = assignments.weekdays[dayOfWeek.toString()]
    if (weekdayRule != null) {
        return findMatchingPlaylist(weekdayRule, currentTime)
    }

    return null // 无匹配规则，停止播放
}

private fun findMatchingPlaylist(timeline: List<TimeSlot>, time: LocalTime): Int? {
    return timeline.find { slot ->
        time >= LocalTime.parse(slot.start) && time < LocalTime.parse(slot.end)
    }?.playlist_id
}
```

---

##### config.holiday_dates (节假日列表)

| 类型 | 格式 | 说明 |
|------|------|------|
| array | `YYYY-MM-DD` | 未来节假日日期列表 |

**注意**: 只包含 >= 今天的日期

---

## 错误处理

### 错误响应格式

所有错误响应统一格式：

```json
{
  "status": "error",
  "message": "错误描述"
}
```

### 常见错误类型

| HTTP状态码 | message | 原因 | 解决方案 |
|-----------|---------|------|----------|
| 403 | `Unauthorized` | 密钥错误或缺失 | 检查`X-Toptea-Secret`头 |
| 200 | `Invalid JSON` | JSON格式错误 | 检查请求体格式 |
| 200 | `Invalid MAC Address` | MAC地址格式错误 | 使用标准MAC地址格式 |
| 200 | `Invalid Version Format` | 版本号不是纯数字 | 使用Unix时间戳字符串 |
| 200 | `Device Not Activated` | 设备未激活 | 等待管理员激活 |
| 200 | `Device Blocked` | 设备被禁用 | 联系管理员解除禁用 |
| 200 | `Database Error` | 数据库异常 | 稍后重试，或联系技术支持 |
| 200 | `Internal Server Error` | 服务器内部错误 | 稍后重试，或联系技术支持 |

### Android 错误处理示例

```kotlin
sealed class ApiResult {
    data class Success(val config: Config) : ApiResult()
    data class Latest(val message: String = "配置已是最新") : ApiResult()
    data class Error(val message: String) : ApiResult()
    data class NotActivated(val message: String = "设备待激活") : ApiResult()
    data class Blocked(val message: String = "设备已被禁用") : ApiResult()
}

fun parseResponse(response: Response): ApiResult {
    return try {
        val jsonString = response.body?.string() ?: return ApiResult.Error("空响应")
        val json = JSONObject(jsonString)

        when (val status = json.getString("status")) {
            "success" -> ApiResult.Success(parseConfig(json.getJSONObject("config")))
            "latest" -> ApiResult.Latest()
            "error" -> {
                val message = json.getString("message")
                when (message) {
                    "Device Not Activated" -> ApiResult.NotActivated()
                    "Device Blocked" -> ApiResult.Blocked()
                    else -> ApiResult.Error(message)
                }
            }
            "update_required" -> ApiResult.Success(parseConfig(json.getJSONObject("config")))
            else -> ApiResult.Error("未知状态: $status")
        }
    } catch (e: Exception) {
        ApiResult.Error("解析失败: ${e.message}")
    }
}
```

---

## 数据模型

### 设备状态枚举

```kotlin
enum class DeviceStatus(val code: Int) {
    PENDING(0),      // 待激活
    ACTIVATED(1),    // 已激活（正常）
    BLOCKED(2)       // 已禁用
}
```

### 播放模式枚举

```kotlin
enum class PlayMode {
    SEQUENCE,  // 顺序播放
    RANDOM     // 随机播放
}
```

### 数据类定义

```kotlin
data class MusicResource(
    val id: Int,
    val md5: String,
    val url: String,
    val size: Long
)

data class Playlist(
    val mode: PlayMode,
    val songIds: List<Int>
)

data class TimeSlot(
    val start: String,  // "HH:MM"
    val end: String,    // "HH:MM"
    val playlistId: Int
)

data class Assignments(
    val specials: Map<String, List<TimeSlot>>,  // "YYYY-MM-DD" -> TimeSlots
    val holidays: List<TimeSlot>?,
    val weekdays: Map<String, List<TimeSlot>>   // "1"-"7" -> TimeSlots
)

data class Config(
    val version: String,
    val resources: List<MusicResource>,
    val playlists: Map<Int, Playlist>,
    val assignments: Assignments,
    val holidayDates: List<String>
)
```

---

## 安全注意事项

### 1. 密钥管理

⚠️ **重要**: `X-Toptea-Secret` 密钥必须保密，不能在代码中明文硬编码。

**推荐做法**:

```kotlin
// 方案1: 使用 BuildConfig（构建时注入）
buildConfigField("String", "API_SECRET", "\"TOPTEA_SECURE_KEY_2025\"")

// 方案2: 使用 NDK 保护密钥
external fun getApiSecret(): String
```

### 2. HTTPS 强制校验

强制使用HTTPS，并验证SSL证书：

```kotlin
val client = OkHttpClient.Builder()
    .certificatePinner(
        CertificatePinner.Builder()
            .add("hq.toptea.es", "sha256/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=")
            .build()
    )
    .build()
```

### 3. MD5 校验

下载音乐文件后，务必验证MD5：

```kotlin
fun verifyFile(file: File, expectedMd5: String): Boolean {
    val md5 = MessageDigest.getInstance("MD5")
    val hash = file.inputStream().use { input ->
        md5.digest(input.readBytes())
    }
    return hash.joinToString("") { "%02x".format(it) } == expectedMd5
}
```

### 4. 重试机制

网络请求失败时，使用指数退避重试：

```kotlin
fun exponentialBackoff(attempt: Int): Long {
    return min(2.0.pow(attempt).toLong() * 1000, 60000) // 最大60秒
}
```

---

## 常见问题

### Q1: 为什么首次连接返回 "Device Not Activated"？

**A**: 新设备首次连接时，系统会自动注册但状态为"待激活"。管理员需要在后台激活设备后才能正常使用。

---

### Q2: current_version 应该传什么值？

**A**: 传递上次成功同步时服务器返回的 `new_version` 值。首次连接可以传空字符串 `""`。

---

### Q3: 如何判断需要重新下载音乐文件？

**A**:
1. 比较`resources`列表中的MD5
2. 如果本地不存在该MD5文件，则下载
3. 如果MD5已存在，跳过下载

---

### Q4: 播放策略如何生效？

**A**:
1. 每分钟检查一次当前时间
2. 根据优先级（特定日期 > 节假日 > 星期）匹配规则
3. 找到匹配的时间段，播放对应歌单

---

### Q5: 时间段跨夜如何处理？

**A**: 当前版本不支持跨夜时间段（如 `22:00-02:00`）。建议拆分为两个时间段：
- 第一天: `22:00-23:59`
- 第二天: `00:00-02:00`

---

### Q6: API会受到Session影响吗？

**A**: ❌ **不会**。API完全无状态，仅通过 `X-Toptea-Secret` 头鉴权，不依赖任何Session或Cookie。

---

## 附录

### 完整请求示例 (cURL)

```bash
curl -X POST https://hq.toptea.es/smsys/api/check_update \
  -H "X-Toptea-Secret: TOPTEA_SECURE_KEY_2025" \
  -H "Content-Type: application/json" \
  -d '{
    "mac_address": "AA:BB:CC:DD:EE:FF",
    "current_version": "1732348800"
  }'
```

### 完整 Kotlin 客户端示例

参考完整示例代码请联系技术团队获取 `NetworkClient.kt` 模板。

---

## 技术支持

如有问题，请联系：

- **后端负责人**: 技术团队
- **文档更新**: 2025-11-23

---

**版权所有 © 2025 Toptea. All Rights Reserved.**
