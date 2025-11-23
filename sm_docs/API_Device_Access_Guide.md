# SoundMatrix 设备访问API技术规范

> **文档版本**: 1.1
> **更新时间**: 2025-11-23
> **适用对象**: Android APP开发工程师
> **系统名称**: SoundMatrix 门店背景音乐系统

---

## 📋 目录

1. [概述](#概述)
2. [设备识别机制](#设备识别机制)
3. [API认证与安全](#api认证与安全)
4. [API接口说明](#api接口说明)
5. [设备状态管理](#设备状态管理)
6. [错误处理](#错误处理)
7. [安全审计与日志](#安全审计与日志)
8. [最佳实践](#最佳实践)

---

## 概述

SoundMatrix系统采用**设备白名单机制**来管理终端访问。所有设备必须先注册并激活后才能获取音乐配置。本文档详细说明设备访问API时需要提交的信息和系统的验证逻辑。

### 核心安全策略

- ✅ **API Secret认证**: 所有API请求必须携带有效的安全密钥
- ✅ **设备白名单机制**: 新设备自动注册但默认处于"未激活"状态
- ✅ **访问日志记录**: 系统记录所有访问尝试（包括失败和被拒绝的请求）
- ✅ **IP地址追踪**: 自动记录设备的网络位置

---

## 设备识别机制

### 必需信息

设备访问API时，系统通过以下信息进行识别：

| 字段名 | 类型 | 来源 | 说明 | 是否必需 |
|--------|------|------|------|----------|
| `mac_address` | String | 请求体(JSON) | 设备MAC地址，作为唯一标识 | ✅ 必需 |
| `X-Toptea-Secret` | String | HTTP Header | API安全密钥 | ✅ 必需 |
| `current_version` | String | 请求体(JSON) | 客户端当前配置版本号 | ⚠️ 推荐 |
| IP地址 | - | 自动获取 | 系统自动从请求中提取 | 🔄 自动 |
| User-Agent | - | HTTP Header | 系统自动记录（可选） | 🔄 自动 |

### MAC地址格式要求

**标准格式**：
```
AA:BB:CC:DD:EE:FF  ✅ 推荐（冒号分隔）
AA-BB-CC-DD-EE-FF  ✅ 支持（连字符分隔）
```

**正则验证**：`^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$`

**错误示例**：
```
AABBCCDDEEFF      ❌ 缺少分隔符
aa:bb:cc:dd:ee    ❌ 长度不足
```

### 获取MAC地址的Android代码示例

```kotlin
import android.net.wifi.WifiManager
import android.content.Context

/**
 * 获取设备MAC地址（需要权限）
 * 权限要求: ACCESS_WIFI_STATE
 */
fun getMacAddress(context: Context): String? {
    val wifiManager = context.applicationContext
        .getSystemService(Context.WIFI_SERVICE) as WifiManager

    val wifiInfo = wifiManager.connectionInfo
    var mac = wifiInfo.macAddress

    // 格式化为标准格式（大写，冒号分隔）
    if (mac != null && mac != "02:00:00:00:00:00") {
        return mac.uppercase()
    }

    // Android 6.0+备用方案
    try {
        val interfaces = NetworkInterface.getNetworkInterfaces()
        while (interfaces.hasMoreElements()) {
            val networkInterface = interfaces.nextElement()
            if (networkInterface.name.equals("wlan0", ignoreCase = true)) {
                val macBytes = networkInterface.hardwareAddress ?: return null
                val sb = StringBuilder()
                for (i in macBytes.indices) {
                    sb.append(String.format("%02X:", macBytes[i]))
                }
                if (sb.isNotEmpty()) {
                    sb.deleteCharAt(sb.length - 1)
                }
                return sb.toString()
            }
        }
    } catch (e: Exception) {
        e.printStackTrace()
    }

    return null
}
```

**AndroidManifest.xml 权限声明**：
```xml
<uses-permission android:name="android.permission.ACCESS_WIFI_STATE" />
<uses-permission android:name="android.permission.INTERNET" />
```

---

## API认证与安全

### 安全密钥配置

**HTTP Header 格式**：
```http
X-Toptea-Secret: TOPTEA_SECURE_KEY_2025
```

**密钥说明**：
- 🔐 当前密钥：`TOPTEA_SECURE_KEY_2025`
- ⚠️ 密钥必须与服务器端保持一致
- 🚨 认证失败会被记录到安全日志

### OkHttp拦截器实现（推荐）

```kotlin
import okhttp3.Interceptor
import okhttp3.Response

class ApiSecretInterceptor : Interceptor {
    companion object {
        private const val API_SECRET = "TOPTEA_SECURE_KEY_2025"
    }

    override fun intercept(chain: Interceptor.Chain): Response {
        val originalRequest = chain.request()
        val requestWithSecret = originalRequest.newBuilder()
            .header("X-Toptea-Secret", API_SECRET)
            .build()
        return chain.proceed(requestWithSecret)
    }
}

// 使用示例
val client = OkHttpClient.Builder()
    .addInterceptor(ApiSecretInterceptor())
    .connectTimeout(10, TimeUnit.SECONDS)
    .readTimeout(30, TimeUnit.SECONDS)
    .build()
```

---

## API接口说明

### 1. 心跳接口（可选）

**用途**: 检测服务器连接状态

```http
GET /smsys/api/heartbeat
X-Toptea-Secret: TOPTEA_SECURE_KEY_2025
```

**响应示例**：
```json
{
  "status": "success",
  "msg": "System Online"
}
```

### 2. 配置同步接口（核心）

**用途**: 检查配置更新并获取最新音乐配置

#### 请求示例

```http
POST /smsys/api/check_update
Content-Type: application/json
X-Toptea-Secret: TOPTEA_SECURE_KEY_2025

{
  "mac_address": "AA:BB:CC:DD:EE:FF",
  "current_version": "1732348800"
}
```

#### 请求参数说明

| 参数名 | 类型 | 必需 | 说明 | 示例 |
|--------|------|------|------|------|
| `mac_address` | String | ✅ | 设备MAC地址（标准格式） | "AA:BB:CC:DD:EE:FF" |
| `current_version` | String | ⚠️ | 当前配置版本号（Unix时间戳字符串）| "1732348800" |

**版本号说明**：
- 首次请求时可传空字符串 `""`
- 版本号为纯数字字符串（Unix时间戳）
- 服务器会返回最新版本号，客户端应保存并在下次请求时使用

#### 响应场景

##### ✅ 场景1: 配置已是最新

```json
{
  "status": "latest"
}
```

##### 🔄 场景2: 需要更新配置

```json
{
  "status": "update_required",
  "new_version": "1732435200",
  "config": {
    "resources": [
      {
        "id": 101,
        "md5": "d41d8cd98f00b204e9800998ecf8427e",
        "url": "https://example.com/songs/song1.mp3",
        "size": 3145728
      }
    ],
    "playlists": {
      "1": {
        "mode": "sequence",
        "ids": [101, 102, 103]
      }
    },
    "assignments": {
      "specials": {
        "2025-12-25": [ /* 圣诞节特殊策略 */ ]
      },
      "holidays": [ /* 节假日策略 */ ],
      "weekdays": {
        "1": [ /* 周一策略 */ ]
      }
    },
    "holiday_dates": ["2025-01-01", "2025-05-01"]
  }
}
```

##### ❌ 场景3: 设备未激活（待审核）

**HTTP 状态码**: 200 OK
**业务状态**: 设备注册成功但未激活

```json
{
  "status": "error",
  "message": "Device Not Activated"
}
```

**处理建议**：
```kotlin
when (response.status) {
    "error" -> {
        when (response.message) {
            "Device Not Activated" -> {
                // 显示提示：设备已注册，请联系管理员激活
                showDialog("设备待激活", "您的设备已成功注册，请联系管理员在后台激活设备后再试。")
            }
        }
    }
}
```

##### 🚫 场景4: 设备已被禁用

```json
{
  "status": "error",
  "message": "Device Blocked"
}
```

**处理建议**: 显示错误信息，停止访问，联系管理员

##### ⚠️ 场景5: 请求格式错误

```json
{
  "status": "error",
  "message": "Invalid MAC Address"
}
```

可能的错误消息：
- `Invalid JSON` - JSON格式错误
- `Invalid MAC Address` - MAC地址格式不正确
- `Invalid Version Format` - 版本号格式错误（应为纯数字）
- `Unauthorized` - API Secret错误

---

## 设备状态管理

### 设备生命周期

```
┌─────────────────┐
│  APP首次启动     │
│  提交MAC地址     │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  自动注册        │ ◄─── 服务器自动创建设备记录
│  status = 0     │      IP地址自动记录
│  (未激活)        │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ 返回错误:        │
│ Device Not      │ ◄─── APP显示提示：请联系管理员激活
│ Activated       │
└────────┬────────┘
         │
         │  ⏳ 等待管理员在后台激活...
         │
         ▼
┌─────────────────┐
│  管理员激活      │ ◄─── 后台操作：status改为1
│  status = 1     │      可设置门店ID、设备名称
│  (已激活)        │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  正常使用        │ ◄─── 可正常获取配置
│  返回配置数据    │      每次请求更新心跳时间和IP
└─────────────────┘

         │ (如有违规)
         ▼
┌─────────────────┐
│  管理员禁用      │
│  status = 2     │ ◄─── 返回错误：Device Blocked
│  (已拉黑)        │
└─────────────────┘
```

### 状态枚举

| status值 | 状态名称 | 说明 | API响应 |
|----------|---------|------|---------|
| 0 | 未激活 | 新设备默认状态，需管理员激活 | `Device Not Activated` |
| 1 | 已激活 | 正常运行状态 | 返回配置数据 |
| 2 | 已禁用 | 被管理员拉黑，拒绝访问 | `Device Blocked` |

---

## 错误处理

### 网络层错误

| HTTP状态码 | 含义 | 处理建议 |
|-----------|------|----------|
| 403 Forbidden | API Secret错误 | 检查密钥配置 |
| 500 Internal Server Error | 服务器内部错误 | 稍后重试 |
| 网络超时 | 连接超时 | 检查网络连接 |

### 业务层错误

| 错误消息 | 原因 | 解决方案 |
|---------|------|----------|
| `Invalid JSON` | 请求体格式错误 | 检查JSON序列化 |
| `Invalid MAC Address` | MAC格式不符合规范 | 使用标准格式（AA:BB:CC:DD:EE:FF）|
| `Device Not Activated` | 设备未激活 | 提示用户联系管理员 |
| `Device Blocked` | 设备被禁用 | 提示用户联系管理员解封 |
| `Unauthorized` | Secret错误 | 检查X-Toptea-Secret头 |

### 错误处理代码示例

```kotlin
sealed class ApiResult<T> {
    data class Success<T>(val data: T) : ApiResult<T>()
    data class Error<T>(val code: String, val message: String) : ApiResult<T>()
}

suspend fun checkUpdate(mac: String, version: String): ApiResult<ConfigResponse> {
    return try {
        val response = apiService.checkUpdate(CheckUpdateRequest(mac, version))

        when (response.status) {
            "latest", "update_required" -> ApiResult.Success(response)
            "error" -> {
                when (response.message) {
                    "Device Not Activated" -> {
                        // 设备未激活，显示特定提示
                        ApiResult.Error("DEVICE_INACTIVE", "设备未激活，请联系管理员")
                    }
                    "Device Blocked" -> {
                        ApiResult.Error("DEVICE_BLOCKED", "设备已被禁用")
                    }
                    else -> {
                        ApiResult.Error("API_ERROR", response.message ?: "未知错误")
                    }
                }
            }
            else -> ApiResult.Error("UNKNOWN", "未知响应状态")
        }
    } catch (e: Exception) {
        ApiResult.Error("NETWORK_ERROR", e.message ?: "网络错误")
    }
}
```

---

## 安全审计与日志

### 访问日志记录

**系统会自动记录以下访问信息**：

| 记录项 | 说明 | 用途 |
|--------|------|------|
| MAC地址 | 设备唯一标识 | 追踪设备活动 |
| IP地址 | 网络位置 | 异常检测 |
| 访问时间 | 时间戳 | 行为分析 |
| 访问结果 | 成功/失败/拒绝 | 安全审计 |
| User-Agent | 客户端信息 | 版本统计 |

### 被记录的访问类型

✅ **成功访问**：正常获取配置
⚠️ **认证失败**：API Secret错误
⚠️ **设备未激活**：status=0的访问尝试
🚫 **设备被禁用**：status=2的访问尝试
❌ **格式错误**：MAC地址或JSON格式错误

**重要提示**：所有被拒绝的访问都会被记录到安全日志，管理员可查看并分析异常访问模式。

---

## 最佳实践

### 1. 网络请求优化

```kotlin
// 推荐配置
val okHttpClient = OkHttpClient.Builder()
    .addInterceptor(ApiSecretInterceptor())
    .connectTimeout(10, TimeUnit.SECONDS)    // 连接超时
    .readTimeout(30, TimeUnit.SECONDS)       // 读取超时
    .retryOnConnectionFailure(true)          // 连接失败自动重试
    .build()
```

### 2. 版本号管理

```kotlin
class ConfigVersionManager(private val sharedPreferences: SharedPreferences) {

    fun saveVersion(version: String) {
        sharedPreferences.edit()
            .putString(KEY_CONFIG_VERSION, version)
            .apply()
    }

    fun getVersion(): String {
        return sharedPreferences.getString(KEY_CONFIG_VERSION, "") ?: ""
    }

    companion object {
        private const val KEY_CONFIG_VERSION = "config_version"
    }
}
```

### 3. 首次启动流程

```kotlin
suspend fun initializeDevice() {
    val mac = getMacAddress(context) ?: run {
        showError("无法获取设备MAC地址")
        return
    }

    when (val result = checkUpdate(mac, "")) {
        is ApiResult.Success -> {
            // 保存版本号和配置
            versionManager.saveVersion(result.data.new_version)
            saveConfig(result.data.config)
        }
        is ApiResult.Error -> {
            when (result.code) {
                "DEVICE_INACTIVE" -> {
                    // 显示激活等待界面
                    showWaitingForActivation()
                }
                else -> {
                    showError(result.message)
                }
            }
        }
    }
}
```

### 4. 定期同步策略

```kotlin
// 使用WorkManager定期检查更新（推荐每小时检查一次）
class ConfigSyncWorker(context: Context, params: WorkerParameters)
    : CoroutineWorker(context, params) {

    override suspend fun doWork(): Result {
        val mac = getMacAddress(applicationContext) ?: return Result.failure()
        val currentVersion = versionManager.getVersion()

        return when (val result = checkUpdate(mac, currentVersion)) {
            is ApiResult.Success -> {
                if (result.data.status == "update_required") {
                    versionManager.saveVersion(result.data.new_version)
                    saveConfig(result.data.config)
                }
                Result.success()
            }
            is ApiResult.Error -> Result.retry()
        }
    }
}

// 调度任务
val syncRequest = PeriodicWorkRequestBuilder<ConfigSyncWorker>(1, TimeUnit.HOURS)
    .setConstraints(
        Constraints.Builder()
            .setRequiredNetworkType(NetworkType.CONNECTED)
            .build()
    )
    .build()

WorkManager.getInstance(context).enqueueUniquePeriodicWork(
    "config_sync",
    ExistingPeriodicWorkPolicy.KEEP,
    syncRequest
)
```

### 5. 安全注意事项

⚠️ **不要硬编码敏感信息**：
```kotlin
// ❌ 不推荐
const val API_SECRET = "TOPTEA_SECURE_KEY_2025"

// ✅ 推荐：使用BuildConfig或安全存储
val apiSecret = BuildConfig.API_SECRET
```

⚠️ **保护MAC地址**：
- MAC地址属于隐私信息，不要上传到第三方分析平台
- 仅用于与服务器通信

⚠️ **错误信息不要暴露给用户**：
```kotlin
// ❌ 不要直接显示技术错误
Toast.makeText(context, "Invalid JSON", Toast.LENGTH_SHORT).show()

// ✅ 显示友好提示
Toast.makeText(context, "网络请求失败，请稍后重试", Toast.LENGTH_SHORT).show()
```

---

## 附录：完整请求示例

### Retrofit接口定义

```kotlin
interface SoundMatrixApi {

    @GET("api/heartbeat")
    suspend fun heartbeat(): HeartbeatResponse

    @POST("api/check_update")
    suspend fun checkUpdate(@Body request: CheckUpdateRequest): ConfigResponse
}

data class CheckUpdateRequest(
    @SerializedName("mac_address")
    val macAddress: String,

    @SerializedName("current_version")
    val currentVersion: String
)

data class ConfigResponse(
    val status: String,
    val message: String? = null,
    @SerializedName("new_version")
    val newVersion: String? = null,
    val config: Config? = null
)

data class Config(
    val resources: List<Resource>,
    val playlists: Map<String, Playlist>,
    val assignments: Assignments,
    @SerializedName("holiday_dates")
    val holidayDates: List<String>
)
```

### ProGuard配置

```proguard
# SoundMatrix API Models
-keep class com.yourapp.model.** { *; }
-keepclassmembers class com.yourapp.model.** { *; }
```

---

## 技术支持

如有问题，请联系后端开发团队或参考以下资源：

- 📄 数据库结构文档: `sm_docs/sm_db_schema_structure_only.sql`
- 🔧 安全审计修复记录: `sm_docs/migration_add_device_access_log.sql`
- 📮 问题反馈: 请提交到项目Issue跟踪系统

---

**文档维护**: 后端开发团队
**最后更新**: 2025-11-23
