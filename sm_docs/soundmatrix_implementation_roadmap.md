# Toptea SoundMatrix - 项目实施路线图

**版本：** 1.2 (2025-11-23 更新)
**当前状态：** Phase 3 (Android Client) - 深度迭代中

**角色分配：**
* **总设计师 (Architect):** Gemini
* **后端/数据库 (Backend Dev):** JULES (已交付 Phase 2.5)
* **安卓开发 (Android Dev):** CEO (User) (执行 Phase 3)

## Phase 1: 基石构建 (Infrastructure)
* [x] **Step 1.1 [Data]:** 数据库表结构设计与迁移脚本 (SQL)。 -> *Completed*
* [x] **Step 1.2 [Spec]:** API 接口详细定义 (Mock Data)。 -> *Completed*

## Phase 2: 后端核心 (Backend Core)
* [x] **Step 2.1 [Admin]:** 后台管理界面 (Songs, Playlists, Strategies)。 -> *Completed*
* [x] **Step 2.2 [Logic]:** 策略排期与指派逻辑。 -> *Completed*
* [x] **Step 2.3 [Security]:** API 鉴权 (Header)、设备准入 (Status=0)、时长自动解析。 -> *Completed (Phase 2.5)*

## Phase 3: 客户端开发 (Android Client)
* [x] **Step 3.1 [Skeleton]:** 基础架构 (Retrofit, Room, ExoPlayer, Service) 搭建完成。 -> *Completed*
* [ ] **Step 3.2 [Logic & Stability] (当前任务):**
    * **自动轮询 (Auto-Polling):** 每 5 分钟检查更新，不再依赖重启。
    * **热重载 (Hot Reload):** 下载完成后发送广播，立即刷新播放列表。
    * **播放过滤 (Filtering):** 严格根据策略的 playlist_id 播放，而非播放所有文件。
    * **紧急熔断 (Kill Switch):** 设备禁用时立即停止播放。
    * **日志防爆 (Log Rotation):** 限制日志行数，防止 OOM。
* [ ] **Step 3.3 [UX & Ops] (视觉重构):**
    * **暗黑模式 (Dark Mode):** 全局原生黑色背景 (#000000)，防止 OLED 烧屏。
    * **运维仪表盘 (Dashboard):** 显示 MAC 地址、当前曲目、音量警告、红绿状态灯。

## Phase 4: 联调与部署 (Integration)
* [ ] **Step 4.1 [Test]:** 模拟全周期的策略下发与执行测试。
* [ ] **Step 4.2 [Deploy]:** 部署代码至生产环境，安卓端打包 APK。