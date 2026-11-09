# Lacuna（PocketMine-MP PHP 8.2 兼容版）

> 语言 / Language：**[中文](#项目简介)** ｜ **[🇬🇧 English](#english-version)**

> 一个基于 **PocketMine-MP 1.7.0（代号 Calisson）** 的 Minecraft: Bedrock Edition（基岩版 / Pocket Edition）服务端，对外品牌名 **Lacuna**。
> 本仓库改编自官方 **[PocketMine-MP 1.7dev-937 with API 3.0.0-ALPHA12](https://github.com/pmmp/PocketMine-MP/releases/tag/api%2F3.0.0-ALPHA12)**（commit `c276ef2`）源码，并做了 **PHP 8.2 兼容性改造**，可直接在现代 PHP 上运行。

---

## 📌 项目简介

本项目是一套用 PHP 编写的 Minecraft 基岩版服务端软件，源自 PocketMine-MP 1.7.0（`API 3.0.0-ALPHA12`）。
原版 PocketMine-MP 1.7.0 时代最早面向 PHP 7.x，本仓库在其基础上修复了 PHP 8.x 下的弃用语法（如隐式动态属性、暂定返回类型等），使其能够在 **PHP 8.2** 上正常启动与运行。

- 软件名称（内部常量 `NAME`）：`PocketMine-MP`
- 运行时品牌名（服务端 `getName()` 返回值 / 启动横幅 / `lacuna.yml`）：**Lacuna**
- 支持客户端版本：**Minecraft v1.2.13**（Bedrock / PE）
- 网络协议号：`223`

---

## 📎 改编来源（Upstream）

- **上游项目**：[pmmp/PocketMine-MP](https://github.com/pmmp/PocketMine-MP)
- **改编自**：[**PocketMine-MP 1.7dev-937 with API 3.0.0-ALPHA12**](https://github.com/pmmp/PocketMine-MP/releases/tag/api%2F3.0.0-ALPHA12)（开发构建 `1.7dev-937`，发布标签 `api/3.0.0-ALPHA12`，commit `c276ef2`）

### ⚠️ 项目状态与改动说明

- 本仓库**改编自**上述官方 `PocketMine-MP 1.7dev-937 with API 3.0.0-ALPHA12` 版本源码。
- 相对上游，本仓库主要做了两件事：
  1. **修复了一些已知 bug**；
  2. **适配 PHP 8+**（主要是处理 PHP 8 下的弃用语法等运行时兼容问题），使其能够在现代 PHP（建议 PHP 8.2）上正常启动与运行。
- **⚠️ 最重要的一点**：本项目（沿用上游 Alpha 预发布版）**仍有大量特性尚未完善**，缺失的玩法 / 功能属于正常现象，**并非缺陷，请勿因此提交 issue**。
- 本仓库**未改动游戏逻辑与网络协议**，协议仍为 `223`（对应 MC v1.2.13）。

---

## 🧾 版本信息

| 项目 | 值 |
| --- | --- |
| 软件名称 | PocketMine-MP |
| 版本号 | 1.7.0 |
| API 版本 | 3.0.0-ALPHA12 |
| 代号 | Calisson |
| 最低 PHP 版本 | 7.2.0（本版本实测兼容 PHP 8.2） |
| 网络协议 | 223 |
| 对应 MC 版本 | v1.2.13 |
| 许可证 | LGPL-3.0 |

---

## ✨ 特性

- 完整的核心游戏逻辑：世界生成、实体、方块、物品、背包、事件系统等。
- 插件系统（基于 `API 3.0.0-ALPHA12`），兼容同 API 版本的第三方插件。
- 内置 RakLib 网络层，支持基于 UDP 的 Minecraft 基岩版协议。
- 首次启动引导向导（Setup Wizard），自动生成 `server.properties`。
- 提供 Windows / Linux 一键启动脚本，Linux 支持循环守护重启。
- PHP 8.2 兼容性补丁，避免 PHP 8 弃用警告升级为致命错误。

---

## 🆕 API 3.0.0-ALPHA12 主要变更与新增玩法

本版本相比此前 API 有较大改动（API 因此升到 `3.0.0-ALPHA12`），以下为对插件开发与玩家体验影响较大的一部分：

### 核心与 API

- **事件处理器继承重构**（重大特性）：事件不再强制要求声明 `public static $handlerList`；可为任意非抽象事件注册处理器，例如同时监听 `EntityDamageEvent`（接收其所有子类）与专门的 `EntityDamageByEntityEvent`；抽象事件可通过 `@allowHandle` 注解允许被处理；事件调用递归深度上限为 50，超出会抛异常（不再段错误）。
- **方块更新钩子重写**：移除 `Block->onUpdate()`，改为 `onScheduledUpdate()`、`onNearbyBlockChange()`、`onRandomUpdate()`，并移除对应的 `Level::BLOCK_UPDATE_*` 常量。
- **合成系统重写**：`CraftingManager` / `CraftingTransaction` / `CraftingRecipe` / `ShapedRecipe` / `ShapelessRecipe` 等接口与构造函数均有显著变化；`BigCraftingGrid` 移除，`CraftingGrid` 构造函数现在接受网格尺寸。
- **物品与实体调整**：`Item` / `PrimedTNT` / `FallingSand` 迁移至 `object\*` 命名空间；新增 `Painting` / `PaintingMotive`；`Effect` 拆分为 `Effect`（类型）与 `EffectInstance`；`ItemFactory::fromString()` 解析失败改为抛异常。
- **玩家相关**：`Player` 构造不再接受 `clientID`；移除 `teleportImmediate()`、`getClientSecret()`；新增 `getPlayerByUUID()` / `getPlayerByRawUUID()`。
- **杂项**：通用工具函数从 `PocketMine.php` 迁移至 `Utils` 类；时区函数移至 `utils\Timezone`；`TextFormat::toANSI()` 移至 `Terminal`。
- **稳定性修复**：`AsyncTask->onCompletion()` 中抛出的异常不再导致服务器崩溃；内存转储统一存到 `memory_dumps` 目录；修复大量世界/实体在负坐标下存档错位、区域文件损坏等问题。

### 新增玩法（Gameplay）

- 实现 **画作（Paintings）**、**末影珍珠**、**喷溅药水**、**附魔瓶**。
- 实现 **暴击（Critical Hits）**、**盔甲耐久度**，并补充若干缺失食物。
- 抛射物改用射线追踪移动，显著改善箭矢等飞行表现（不再卡在半空、穿过方块角）。
- 玩家在创造模式下不再能食用需要饥饿值的食物；修复护甲槽变动在客户端不可见的问题。
- 服务器列表现可显示 Xbox Live 图标。

> 完整变更请参见官方发布说明：<https://github.com/pmmp/PocketMine-MP/releases/tag/api%2F3.0.0-ALPHA12>

---

## 💻 运行环境要求

- **PHP**：64 位 PHP **7.2.0 及以上**（推荐使用 **PHP 8.2**）。
  > ⚠️ 必须是 64 位 PHP，32 位系统 / PHP 已被明确拒绝。
- **必要 PHP 扩展**：

  | 扩展 | 说明 |
  | --- | --- |
  | `bcmath` | BC Math |
  | `curl` | cURL |
  | `json` | JSON |
  | `mbstring` | 多字节字符串 |
  | `openssl` | OpenSSL |
  | `phar` | Phar |
  | `pthreads` | 多线程（≥ 3.1.7-dev） |
  | `sockets` | Sockets |
  | `yaml` | YAML |
  | `zip` | Zip |
  | `zlib` | Zlib |

- **可选扩展**：`leveldb`（≥ 0.2.1，用于 LevelDB 世界存储）。
- **运行方式**：必须通过 **CLI（命令行）** 启动，不支持 Web 服务器模式。

### 依赖库（已内置在 `vendor/`，无需手动安装）

本版本要求并内置了以下 PocketMine 官方依赖库：

| 库 | 适配版本 |
| --- | --- |
| `pocketmine/binaryutils` | v0.0.1 |
| `pocketmine/math` | v0.1.0 |
| `pocketmine/nbt` | v0.1.0 |
| `pocketmine/raklib` | v0.11.0 |
| `pocketmine/spl` | v0.3.0 |

---

## 📥 PHP 二进制下载（PHP Binaries）

本服务端需要 64 位 PHP 8.x。你可以从以下地址下载官方 / 社区维护的 PHP 二进制，无需自行编译：

- **PHP 8.2（推荐 / Recommended）**
  - pmmp 官方构建：<https://github.com/pmmp/PHP-Binaries/releases/tag/pm4-php-8.2-latest>
- **PHP 8.0（未经测试，但预期可用 / Not tested, but should work）**
  - pmmp 官方构建：<https://github.com/pmmp/PHP-Binaries/releases/tag/pm4-php-8.0-latest>
  - ARMv8 构建（安卓 / 树莓派等，PHP8 for ARMv8）：<https://github.com/DaisukeDaisuke/AndroidPHP/releases/tag/8.0.28>

> 下载后将其解压，把 `php` 可执行文件放到本目录的 `bin/php/`（Windows 对应 `bin\php\php.exe`），或直接加入系统 `PATH`，启动脚本会自动识别并使用。

---

## 🚀 快速开始

### Windows

1. 双击运行 `start.bat`，或从命令行执行：

   ```bat
   start.bat
   ```

2. 脚本会按以下顺序查找服务端文件：
   - `bin\php\php.exe`（自带 PHP，推荐）
   - 系统环境变量中的 `php`
3. 若当前目录存在 `Genisys.phar` / `PocketMine-MP.phar` / `PocketMine-Soft.phar` / `src\pocketmine\PocketMine.php`，脚本会自动选用。
4. 首次启动会进入配置向导，按提示填写语言、游戏模式、难度、最大玩家数、服务器端口（默认 `19132`）等。
5. 在控制台输入 `stop` 并回车可关闭服务器。

### Linux / macOS

```bash
chmod +x start.sh
./start.sh
```

常用参数：

| 参数 | 说明 |
| --- | --- |
| `-p <php>` | 指定 PHP 可执行文件路径 |
| `-l` | 服务器崩溃后自动循环重启 |
| `-f <file>` | 指定启动文件（.phar 或 `src/pocketmine/PocketMine.php`） |

示例（使用系统 PHP 并开启守护循环）：

```bash
./start.sh -p $(which php) -l
```

无参数时，脚本会自动查找 `bin/php7/bin/php`、`PocketMine-iTX.phar`、`Genisys*.phar`、`PocketMine-MP.phar` 或源码目录运行。

---

## 📡 网络与端口

- 服务端默认通过 **UDP** 协议监听 `19132` 端口（可在 `server.properties` 的 `server-port` 中修改）。
- 让其他玩家连接：
  - **局域网**：在防火墙中放行该 **UDP** 端口即可。
  - **公网 / 互联网**：需在路由器上做 **UDP 端口转发**到运行服务端的机器；玩家在客户端「添加服务器」时填写你的公网 IP / 域名与端口。
- 基岩版客户端（手机 / Windows 10+ / 主机）通过「添加外部服务器」输入地址与端口即可加入。

> 注意：基岩版使用的是 **UDP**，端口转发与防火墙规则务必选择 UDP，而非 TCP。

---

## 📂 目录结构

```text
.
├── src/
│   └── pocketmine/        # 服务端核心源码（block/entity/item/network/level/plugin …）
├── vendor/
│   ├── pocketmine/        # 内置依赖：raklib / nbt / math / spl / binaryutils
│   └── autoload.php       # Composer 自动加载
├── start.bat              # Windows 启动脚本（PHP 8.2）
├── start.cmd              # Windows 启动脚本（与 .bat 等价）
├── start.sh               # Linux / macOS 启动脚本
```

> 运行时会在当前目录生成 `server.properties`、`server.log`、`plugins/`、`worlds/`（或对应世界的存档目录）等文件。

---

## ⚙️ 配置文件

首次启动向导会生成 `server.properties`，常见字段：

| 字段 | 默认值 | 说明 |
| --- | --- | --- |
| `server-name` | `Minecraft: PE Server` | 服务器名称 |
| `server-port` | `19132` | UDP 监听端口 |
| `max-players` | `20` | 最大玩家数 |
| `gamemode` | `0` | 默认游戏模式（0=生存, 1=创造, 2=冒险, 3=旁观） |
| `difficulty` | `1` | 难度（0=和平, 1=简单, 2=普通, 3=困难） |
| `level-name` | `world` | 主世界名称 |
| `motd` | — | 服务器列表显示信息 |

编辑保存后，重启服务器即可生效。

---

## 🔌 插件

1. 将 `.phar` 格式的插件放入 `plugins/` 目录。
2. 启动 / 重启服务器，插件会被自动加载。
3. 支持的插件 API 版本为 `3.0.0-ALPHA12`，请使用对应 API 的插件。

---

## 🛠 从源码运行

本仓库已内置 `vendor/` 依赖，**无需执行 Composer install** 即可直接运行：

```bash
# Linux / macOS
./start.sh -f src/pocketmine/PocketMine.php

# Windows
start.bat
# 脚本会自动识别并使用 src\pocketmine\PocketMine.php
```

> 提示：源码（非 `.phar`）模式运行时，控制台会提示“非打包安装，生产环境建议使用 phar 以获得更好性能”。开发调试可用源码模式，正式部署建议打包为 phar。

---

## ❓ 常见问题

**Q：启动时报“Couldn't find a valid Genisys installation”？**
A：说明未找到任何启动文件。请确保目录中存在 `*.phar` 或 `src/pocketmine/PocketMine.php`，或使用 `-f` 参数显式指定。（注：启动脚本沿用了早期 Genisys / iTX 分支的旧命名，报错信息里的 `Genisys` 字样可忽略，不影响实际运行。）

**Q：提示某个 PHP 扩展缺失？**
A：按上文「运行环境要求」安装对应扩展（如 `php-mbstring`、`php-yaml`、`pthreads` 等）。Windows 用户建议使用脚本自带的 `bin/php` 环境。

**Q：提示“Running with 32-bit systems/PHP is no longer supported”？**
A：请更换为 64 位 PHP。

**Q：PHP 8 下出现大量 deprecation 警告？**
A：本版本已对弃用语法做兼容处理，警告不会影响运行；若需彻底静默，可在 `php.ini` 中关闭 `E_DEPRECATED` 显示。

---

## 📜 许可证

本项目继承 PocketMine-MP 的许可证，采用 **GNU Lesser General Public License v3.0（LGPL-3.0）**。
详见源码文件头部的许可证声明。

---

## 🙏 鸣谢

- [PocketMine-MP](https://github.com/pmmp/PocketMine-MP) 及其团队 —— 本项目的核心源码基础，改编自 [**PocketMine-MP 1.7dev-937 with API 3.0.0-ALPHA12**](https://github.com/pmmp/PocketMine-MP/releases/tag/api%2F3.0.0-ALPHA12)（开发构建 `1.7dev-937`，标签 `api/3.0.0-ALPHA12`，commit `c276ef2`）发布版本。
- RakLib、NBT、LevelDB 等上游依赖的作者与维护者。

---

## 📢 声明与免责

- **商标与无关联**：`Minecraft` 是 Mojang AB（现 Microsoft 子公司）的商标。本项目与 Mojang AB / Microsoft 以及 PocketMine-MP 官方团队（pmmp）**无任何关联**，也未获得其认可、赞助或授权。
- **开源许可**：本项目基于上游 **LGPL-3.0** 许可发布。若你对受 LGPL 覆盖的源码进行了修改，应同样以 **LGPL-3.0** 开源这些改动，并保留原始版权与许可证声明。
- **无担保**：本软件按「**原样（AS IS）**」提供，**不提供任何明示或暗示的担保**（包括但不限于适销性、特定用途适用性）。使用本软件的风险与后果由使用者自行承担。
- **第三方内容**：插件及其他第三方组件归各自作者所有，其许可合规性、安全性与兼容性由使用者自行确认，本项目不对第三方内容负责。
- **反馈指引**：本版本改编自上流 **Alpha 预发布**，缺失的玩法功能属于正常情况，**请勿因此向上游或本仓库提交 issue**；如确认为本 PHP 8.2 兼容改造引入的问题，可在本仓库反馈。
- **使用条款**：使用时请遵守 Minecraft 相关使用条款及上述开源许可证。本项目为社区维护的兼容性改造版本，非官方发布。

---

# 🇬🇧 English Version

> Language: **[🇨🇳 中文](#项目简介)** ｜ **[English](#english-version)**

> A Minecraft: Bedrock Edition (Bedrock / Pocket Edition) server software based on **PocketMine-MP 1.7.0 (Codename Calisson)**, branded **Lacuna**.
> This repository is adapted from the official **[PocketMine-MP 1.7dev-937 with API 3.0.0-ALPHA12](https://github.com/pmmp/PocketMine-MP/releases/tag/api%2F3.0.0-ALPHA12)** (commit `c276ef2`) source, with **PHP 8.2 compatibility fixes** so it runs on modern PHP.

---

## 📌 Overview

This is a Minecraft Bedrock Edition server written in PHP, derived from PocketMine-MP 1.7.0 (`API 3.0.0-ALPHA12`). The original 1.7.0 era targeted PHP 7.x; this repository fixes PHP 8.x deprecation syntax (e.g. implicit dynamic properties, tentative return types) so it can boot and run on **PHP 8.2**.

- Software name (internal constant `NAME`): `PocketMine-MP`
- Runtime brand name (server `getName()` / startup banner / `lacuna.yml`): **Lacuna**
- Supported client: **Minecraft v1.2.13** (Bedrock / PE)
- Network protocol: `223`

---

## 📎 Upstream

- **Upstream project**: [pmmp/PocketMine-MP](https://github.com/pmmp/PocketMine-MP)
- **Adapted from**: [**PocketMine-MP 1.7dev-937 with API 3.0.0-ALPHA12**](https://github.com/pmmp/PocketMine-MP/releases/tag/api%2F3.0.0-ALPHA12) (dev build `1.7dev-937`, release tag `api/3.0.0-ALPHA12`, commit `c276ef2`)

### ⚠️ Project Status & Changes

- This repository is **adapted from** the official `PocketMine-MP 1.7dev-937 with API 3.0.0-ALPHA12` source above.
- Relative to upstream, this repository mainly does two things:
  1. **Fixed some known bugs**;
  2. **Adapted to PHP 8+** (mostly runtime compatibility — handling PHP 8 deprecations, etc.) so it can boot and run on modern PHP (PHP 8.2 recommended).
- **⚠️ Most important**: As an Alpha-derived build, this project **still has many features that are not yet complete**. Missing gameplay / functionality is normal, **not a defect — please do not file issues for it**.
- This repository does **not** modify game logic or the network protocol; the protocol remains `223` (MC v1.2.13).

---

## 🧾 Version Information

| Item | Value |
| --- | --- |
| Software name | PocketMine-MP (runtime brand: **Lacuna**) |
| Version | 1.7.0 |
| API version | 3.0.0-ALPHA12 |
| Codename | Calisson |
| Minimum PHP | 7.2.0 (this build tested on PHP 8.2) |
| Network protocol | 223 |
| MC version | v1.2.13 |
| License | LGPL-3.0 |

---

## ✨ Features

- Full core gameplay: world generation, entities, blocks, items, inventories, event system, and more.
- Plugin system (based on `API 3.0.0-ALPHA12`), compatible with third-party plugins of the same API.
- Built-in RakLib network layer supporting the UDP-based Minecraft Bedrock protocol.
- First-run setup wizard that auto-generates `server.properties`.
- One-click startup scripts for Windows / Linux; the Linux script supports auto-restart loop.
- PHP 8.2 compatibility patch to prevent PHP 8 deprecation warnings from escalating into fatal errors.

---

## 🆕 Major Changes & New Gameplay (API 3.0.0-ALPHA12)

This version introduced significant API changes (hence the bump to `3.0.0-ALPHA12`). Below are the parts with the greatest impact on plugin development and player experience:

### Core & API

- **Event handler inheritance rework** (major feature): events no longer require a `public static $handlerList`; handlers can be registered for any non-abstract event — e.g. listen to `EntityDamageEvent` (receiving all its subclasses) and a dedicated `EntityDamageByEntityEvent` separately; abstract events can be handled via the `@allowHandle` docblock annotation; event call recursion is capped at depth 50 (throws instead of segfaulting).
- **Block update hooks rewritten**: `Block->onUpdate()` removed, replaced by `onScheduledUpdate()`, `onNearbyBlockChange()`, and `onRandomUpdate()`; the corresponding `Level::BLOCK_UPDATE_*` constants were removed.
- **Crafting system rewrite**: `CraftingManager` / `CraftingTransaction` / `CraftingRecipe` / `ShapedRecipe` / `ShapelessRecipe` interfaces and constructors changed significantly; `BigCraftingGrid` removed, `CraftingGrid` constructor now accepts a grid size.
- **Item & entity adjustments**: `Item` / `PrimedTNT` / `FallingSand` moved to the `object\*` namespace; added `Painting` / `PaintingMotive`; `Effect` split into `Effect` (type) and `EffectInstance`; `ItemFactory::fromString()` now throws on parse failure.
- **Player-related**: `Player` constructor no longer accepts `clientID`; removed `teleportImmediate()` and `getClientSecret()`; added `getPlayerByUUID()` / `getPlayerByRawUUID()`.
- **Misc**: generic utility functions moved from `PocketMine.php` to the `Utils` class; timezone functions moved to `utils\Timezone`; `TextFormat::toANSI()` moved to `Terminal`.
- **Stability fixes**: exceptions thrown from `AsyncTask->onCompletion()` no longer crash the server; memory dumps are stored in a dedicated `memory_dumps` directory; fixed numerous world/entity save-corruption bugs at negative coordinates and region-file handling.

### New Gameplay

- Implemented **Paintings**, **Ender Pearls**, **Splash Potions**, and **Bottle o' Enchanting**.
- Implemented **Critical Hits** and **armour durability**, and added several missing food items.
- Projectiles now use ray tracing for movement, greatly improving arrow flight (no longer stuck mid-air or clipping block corners).
- Creative-mode players can no longer eat food that requires hunger; fixed armour slot changes not being visible on the client.
- The server list now displays Xbox Live icons.

> See the official release notes for the full changelog: <https://github.com/pmmp/PocketMine-MP/releases/tag/api%2F3.0.0-ALPHA12>

---

## 💻 Requirements

- **PHP**: 64-bit PHP **7.2.0 or newer** (PHP **8.2** recommended).
  > ⚠️ Must be 64-bit PHP. 32-bit systems / PHP are explicitly rejected.
- **Required PHP extensions**:

  | Extension | Description |
  | --- | --- |
  | `bcmath` | BC Math |
  | `curl` | cURL |
  | `json` | JSON |
  | `mbstring` | Multibyte String |
  | `openssl` | OpenSSL |
  | `phar` | Phar |
  | `pthreads` | Multithreading (≥ 3.1.7-dev) |
  | `sockets` | Sockets |
  | `yaml` | YAML |
  | `zip` | Zip |
  | `zlib` | Zlib |

- **Optional extension**: `leveldb` (≥ 0.2.1, for LevelDB world storage).
- **Runtime**: Must be launched via the **CLI (command line)**; web-server mode is not supported.

### Bundled Dependency Libraries (in `vendor/`, no manual install needed)

This version requires and bundles the following official PocketMine libraries:

| Library | Version |
| --- | --- |
| `pocketmine/binaryutils` | v0.0.1 |
| `pocketmine/math` | v0.1.0 |
| `pocketmine/nbt` | v0.1.0 |
| `pocketmine/raklib` | v0.11.0 |
| `pocketmine/spl` | v0.3.0 |

---

## 📥 PHP Binaries

This server requires 64-bit PHP 8.x. You can download official / community-maintained PHP binaries from the links below — no need to compile PHP yourself:

- **PHP 8.2 (Recommended)**
  - pmmp official build: <https://github.com/pmmp/PHP-Binaries/releases/tag/pm4-php-8.2-latest>
- **PHP 8.0 (Not tested, but should work)**
  - pmmp official build: <https://github.com/pmmp/PHP-Binaries/releases/tag/pm4-php-8.0-latest>
  - ARMv8 build (Android / Raspberry Pi etc., PHP8 for ARMv8): <https://github.com/DaisukeDaisuke/AndroidPHP/releases/tag/8.0.28>

> After downloading, extract it and place the `php` binary under `bin/php/` in this directory (Windows: `bin\php\php.exe`), or add it to your system `PATH`. The startup scripts will auto-detect and use it.

---

## 🚀 Quick Start

### Windows

1. Double-click `start.bat`, or run it from the command line:

   ```bat
   start.bat
   ```

2. The script looks for the server file in this order:
   - `bin\php\php.exe` (bundled PHP, recommended)
   - `php` from the system `PATH`
3. If `Genisys.phar` / `PocketMine-MP.phar` / `PocketMine-Soft.phar` / `src\pocketmine\PocketMine.php` exists in the directory, the script selects it automatically.
4. On first launch the setup wizard runs; follow the prompts for language, game mode, difficulty, max players, server port (default `19132`), etc.
5. Type `stop` and press Enter in the console to shut down the server.

### Linux / macOS

```bash
chmod +x start.sh
./start.sh
```

Common options:

| Option | Description |
| --- | --- |
| `-p <php>` | Specify the PHP binary path |
| `-l` | Auto-restart loop after a crash |
| `-f <file>` | Specify the startup file (.phar or `src/pocketmine/PocketMine.php`) |

Example (system PHP with watchdog loop):

```bash
./start.sh -p $(which php) -l
```

With no arguments, the script auto-detects `bin/php7/bin/php`, `PocketMine-iTX.phar`, `Genisys*.phar`, `PocketMine-MP.phar`, or the source directory.

---

## 📡 Networking & Ports

- The server listens on **UDP** port `19132` by default (change it via `server-port` in `server.properties`).
- To let other players connect:
  - **LAN**: Allow that **UDP** port through the firewall.
  - **Public / internet**: Set up **UDP port forwarding** on your router to the machine running the server; players enter your public IP / domain and port when adding the server in the client.
- Bedrock clients (mobile / Windows 10+ / consoles) join by adding an external server with the address and port.

> Note: Bedrock Edition uses **UDP**. Port forwarding and firewall rules must be UDP, not TCP.

---

## 📂 Directory Structure

```text
.
├── src/
│   └── pocketmine/        # Core server source (block/entity/item/network/level/plugin …)
├── vendor/
│   ├── pocketmine/        # Bundled deps: raklib / nbt / math / spl / binaryutils
│   └── autoload.php       # Composer autoloader
├── start.bat              # Windows startup script (PHP 8.2)
├── start.cmd              # Windows startup script (equivalent to .bat)
├── start.sh               # Linux / macOS startup script
```

> At runtime the following are generated in the current directory: `server.properties`, `server.log`, `plugins/`, `worlds/` (or the relevant world save directories), etc.

---

## ⚙️ Configuration

The setup wizard generates `server.properties` on first launch. Common fields:

| Field | Default | Description |
| --- | --- | --- |
| `server-name` | `Minecraft: PE Server` | Server name |
| `server-port` | `19132` | UDP listen port |
| `max-players` | `20` | Maximum players |
| `gamemode` | `0` | Default game mode (0=Survival, 1=Creative, 2=Adventure, 3=Spectator) |
| `difficulty` | `1` | Difficulty (0=Peaceful, 1=Easy, 2=Normal, 3=Hard) |
| `level-name` | `world` | Main world name |
| `motd` | — | Message shown in the server list |

Save and restart the server for changes to take effect.

---

## 🔌 Plugins

1. Place `.phar` plugins into the `plugins/` directory.
2. Start / restart the server; plugins are loaded automatically.
3. Supported plugin API version is `3.0.0-ALPHA12`; use plugins built for this API.

---

## 🛠 Running from Source

This repository bundles `vendor/` dependencies, so you can run directly **without running Composer install**:

```bash
# Linux / macOS
./start.sh -f src/pocketmine/PocketMine.php

# Windows
start.bat
# The script auto-detects and uses src\pocketmine\PocketMine.php
```

> Tip: In source (non-`.phar`) mode the console warns that "non-packaged installation" is suboptimal for production. Source mode is good for development; package as a phar for production deployments.

---

## ❓ FAQ

**Q: "Couldn't find a valid Genisys installation" on startup?**
A: No startup file was found. Ensure `*.phar` or `src/pocketmine/PocketMine.php` exists in the directory, or specify it with `-f`. (Note: the startup scripts retain legacy naming from older Genisys / iTX forks; the `Genisys` text in the message can be ignored and does not affect operation.)

**Q: A PHP extension is reported missing?**
A: Install the corresponding extension per the Requirements section (e.g. `php-mbstring`, `php-yaml`, `pthreads`). Windows users are advised to use the bundled `bin/php` environment.

**Q: "Running with 32-bit systems/PHP is no longer supported"?**
A: Switch to a 64-bit PHP build.

**Q: Lots of deprecation warnings under PHP 8?**
A: This build handles deprecation syntax; warnings do not affect operation. To silence them, disable `E_DEPRECATED` display in `php.ini`.

---

## 📜 License

This project inherits the PocketMine-MP license and is released under the **GNU Lesser General Public License v3.0 (LGPL-3.0)**. See the license headers in the source files for details.

---

## 🙏 Acknowledgements

- [PocketMine-MP](https://github.com/pmmp/PocketMine-MP) and its team — the core source base of this project, adapted from the [**PocketMine-MP 1.7dev-937 with API 3.0.0-ALPHA12**](https://github.com/pmmp/PocketMine-MP/releases/tag/api%2F3.0.0-ALPHA12) (dev build `1.7dev-937`, tag `api/3.0.0-ALPHA12`, commit `c276ef2`) release.
- The authors and maintainers of upstream dependencies such as RakLib, NBT, and LevelDB.

---

## 📢 Declarations & Disclaimers

- **Trademark & non-affiliation**: `Minecraft` is a trademark of Mojang AB (now a Microsoft subsidiary). This project is **not affiliated** with Mojang AB / Microsoft or the PocketMine-MP team (pmmp), and is not endorsed, sponsored, or authorized by them.
- **Open-source compliance**: Released under **LGPL-3.0**. If you modify LGPL-covered source, you must release those modifications under **LGPL-3.0** as well, keeping the original copyright and license notices.
- **No warranty**: This software is provided **"AS IS"**, without any express or implied warranty (including merchantability or fitness for a particular purpose). Use is at your own risk.
- **Third-party content**: Plugins and other third-party components belong to their respective authors; their license compliance, security, and compatibility are the user's responsibility. This project is not responsible for third-party content.
- **Feedback guidance**: As an Alpha-derived build, missing gameplay features are expected — **please do not file issues for them** (upstream or here). If you believe a problem is introduced by this PHP 8.2 compatibility patch, report it in this repository.
- **Terms of use**: Comply with the Minecraft terms of use and the open-source licenses above. This is a community-maintained compatibility fork, not an official release.
