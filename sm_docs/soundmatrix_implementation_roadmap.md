# Toptea SoundMatrix - 项目实施路线图

**版本：** 1.1 (2025-11-22 更新)
**当前状态：** Phase 2 完成，Phase 3 启动中

**角色分配：**
* **总设计师 (Architect):** Gemini
* **后端/数据库 (Backend Dev):** JULES (已交付)
* **安卓开发 (Android Dev):** CEO (User) (执行中)

## Phase 1: 基石构建 (Infrastructure)
* [x] **Step 1.1 [Data]:** 数据库表结构设计与迁移脚本 (SQL)。 -> *Completed (2025-11-22)*
* [x] **Step 1.2 [Spec]:** API 接口详细定义 (Mock Data)。 -> *Completed (2025-11-22)*
* [ ] **Step 1.3 [Android]:** 安卓项目初始化与架构搭建 (Room/Retrofit)。 -> *Assignee: CEO (Next Step)*

## Phase 2: 后端核心 (Backend Core)
* [x] **Step 2.1 [Admin]:** 搭建独立后台框架，复用 Toptea 样式。 -> *Completed (2025-11-22)*
* [x] **Step 2.2 [Logic]:** 实现歌曲上传 (MD5计算) 与歌单管理。 -> *Completed (2025-11-22)*
* [x] **Step 2.3 [Logic]:** 实现排期策略 (Time Slots) 与日历管理逻辑。 -> *Completed (2025-11-22)*
* [x] **Step 2.4 [API]:** 开发 `/check-update` 差异化对比接口。 -> *Completed (2025-11-22)*
    * *验证通过：API 正确返回 `Missing MAC` 错误，证明路由与逻辑已生效。*

## Phase 3: 客户端开发 (Android Client)
* [ ] **Step 3.1 [Sync]:** 实现“下载管理器” (断点续传、MD5校验)。 -> *Assignee: CEO*
* [ ] **Step 3.2 [DB]:** 本地 Room 数据库读写逻辑。 -> *Assignee: CEO*
* [ ] **Step 3.3 [Play]:** 实现播放引擎 (ExoPlayer) 与无缝切换。 -> *Assignee: CEO*
* [ ] **Step 3.4 [Job]:** 实现“守护服务”与“开机自启”。 -> *Assignee: CEO*

## Phase 4: 联调与部署 (Integration)
* [ ] **Step 4.1 [Test]:** 模拟全周期的策略下发与执行测试。
* [ ] **Step 4.2 [Deploy]:** 部署代码至生产环境，安卓端打包 APK。