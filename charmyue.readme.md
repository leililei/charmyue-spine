# 绯月絮语 Spine 资源提取工具

从《绯月絮语》游戏 CDN 自动下载、解包并索引 Spine 2D 骨骼动画资源。

## 环境要求

- **PHP** (CLI)
- **aria2** (批量下载)
- **AssetStudio.CLI** — 从 Unity AssetBundle 中提取资源
  - 下载地址：https://github.com/Razviar/assetstudio/releases
  - 推荐版本：https://github.com/Razviar/assetstudio/releases/download/v2.4.1/AssetStudio-net10.0-win.zip

## 使用流程

### 第一步：解析清单并生成下载列表

```bash
php parse_charmyue.php 1.0.178 charmyue
```

参数说明：
- 参数1：版本号（默认 `1.0.178`）
- 参数2：下载目录（默认 `charmyue`）

自动完成：
1. 从 CDN 下载版本文件 `version_<版本号>.bytes`
2. 解析二进制清单，提取 `spine_*` 资源路径
3. 输出 `aria2_charmyue.txt` 下载列表

### 第二步：下载资源

```bash
aria2c -i aria2_charmyue.txt -d charmyue --continue
```

### 第三步：使用 AssetStudio 提取资源

```bash
AssetStudio.CLI charmyue ResPackage --game Normal --group_assets ByContainer --types Texture2D --types TextAsset --containers "^assets/res/spine"
```


## 目录结构

```
交错战线2026/
├── parse_charmyue.php       # 清单解析：下载版本文件并生成 aria2 列表
├── rename.php               # Spine 资源整理：复制并重命名 .prefab → .json/.atlas
│
├── charmyue/           # 阶段1：原始下载
├── ResPackage/             # 阶段2：AssetStudio 解包输出
```

## CDN 地址

```
版本文件: https://myres.res.charmyue.cn/Res/StreamingAssets/WebGL/data/Versions/version_<版本号>.bytes
清单文件: https://myres.res.charmyue.cn/Res/StreamingAssets/WebGL/data/ManifestFiles/<hash>/PackageManifest_ResPackage.bytes
资源文件: https://myres.res.charmyue.cn/Res/StreamingAssets/WebGL/data/ResPackage/<prefix>/<hash>.unity3d
```
