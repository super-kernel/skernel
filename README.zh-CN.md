<p align="right">
  <a href="README.md">English</a> | <strong>中文文档</strong>
</p>

# SKERNEL

`super-kernel/skernel` 即 SKERNEL，是 SuperKernel 应用的命令行项目工具，作用相当于 Java 项目中的 Maven。它本身也是用 SuperKernel 构建的控制台应用，计划以独立的二进制文件发布。

> [!NOTE]
> SKERNEL 仍在积极开发中。目前提供 `build` 和 `package` 命令；创建、运行、清理项目以及更新 SKERNEL 自身的命令还没有实现。控制台另有 SKERNEL 自己的 `help` 和 `list` 命令、Symfony Console 的 `completion` 命令，以及 `--version` 选项。二进制文件将通过本仓库的 GitHub Releases 和 Packages 发布，目前还没有发布任何内容。

## 控制台

不带命令运行 SKERNEL，或运行 `list` 时，纯文本的命令总览上方会打印 SKERNEL 标志。raw、JSON、XML 和 Markdown 格式的列表、按命名空间列出的列表以及命令帮助都不打印标志。`--version` 输出 `SKERNEL`，包声明了版本时后面跟上版本号。

SKERNEL 把 WARNING 及以上级别的日志（包括框架的扫描汇总和异常报告）写到 STDERR，STDOUT 只承载命令本身的输出；例如 `list --format=json` 在 STDOUT 上输出的就是纯 JSON。

命令不存在、选项不存在这类用法错误，会在 STDERR 上打印一条简短的提示；如果是某个命令的输入有误，后面还会打印该命令的用法。退出码为 1。加上 `-v` 时还会显示异常类名和堆栈。

各命令处理的是当前目录中的 SuperKernel 应用，或全局选项 `-d`/`--working-dir` 指定的目录中的应用。

## 配置文件

命令行上没有给出的选项，从项目根目录的 `skernel.json` 读取；`skernel.json` 不存在时改读 `skernel.json.dist`。两个文件从不合并。把项目共享的设置写进 `skernel.json.dist` 并提交，本机特有的覆盖写进被忽略的 `skernel.json`。

```json
{
    "$schema": "https://raw.githubusercontent.com/super-kernel/skernel/main/skernel.schema.json",
    "php": "8.4",
    "package": {
        "runtime": "build/micro.sfx",
        "output": "target/my-app",
        "composer": "composer",
        "dev": false,
        "phar": false
    }
}
```

键名与命令选项一一对应：`php` 适用于所有命令，`package` 一节适用于 `package`。命令行选项优先于配置文件，配置文件优先于内置默认值。配置文件里的相对路径相对于项目根目录解析，命令行上的相对路径则相对于当前目录解析。出现未知的键或类型不对的值时，命令会停止，并给出包含文件名和键名的提示。[`skernel.schema.json`](skernel.schema.json) 为支持 JSON Schema 的编辑器描述了这个文件。

## 受管 PHP

SKERNEL 用自己构建的静态 PHP 来运行和打包项目，而不是系统上的 PHP。PHP 版本写成 `major.minor`（例如 `8.4`）时，选用 php.net 上该分支的最新发布；写成 `major.minor.patch`（例如 `8.4.27`）时精确使用该版本。不指定版本时，SKERNEL 选用满足根包 `require.php` 的最新受支持 PHP 版本。

构建出的 PHP 包含项目安装所需的扩展，也就是根清单中、已安装时的开发依赖中，以及每个已安装包中的 `ext-*` 要求，另外再加上 `phar`。SKERNEL 使用固定版本的 [static-php-cli](https://github.com/crazywhalecc/static-php-cli)，按平台、版本和扩展组合各构建一次，产出静态 PHP CLI 和用于独立二进制的 micro 运行时，之后重复使用。在 Linux 上，两者都静态链接 musl，可以在任何 Linux 发行版上运行；SKERNEL 用固定版本的 [Zig](https://ziglang.org) 构建它们，首次使用时校验并安装到自己的主目录中。按照 Zig 软件基金会对下载工具的要求，Zig 以随机顺序从[社区镜像](https://ziglang.org/download/community-mirrors/)下载，没有镜像能提供时才从 ziglang.org 下载。

SKERNEL 下载和构建的所有内容都放在它的主目录 `~/.skernel` 中，或者环境变量 `SKERNEL_HOME` 指定的目录中：

```text
~/.skernel/
├── cache/                       php.net release answers and Zig mirrors, refreshed daily
├── downloads/                   source archives, shared by every build
├── pkgroot/<platform>/          Zig and the other packages static-php-cli installs
├── runtimes/<platform>/php-<version>/<extension-hash>/
│   ├── php                      the static PHP CLI
│   ├── micro.sfx                the micro runtime
│   └── manifest.json
├── tools/spc/<version>/         static-php-cli
└── work/<job>/                  sources and build output of one build
```

首次构建某个运行时需要联网，还需要 static-php-cli 要求的构建工具，例如 C 编译器、`perl`、`re2c`、`autoconf`、`automake`、`cmake`、`libtool` 和 `gettext`。SKERNEL 从不安装系统软件包；构建失败时，static-php-cli 在 STDERR 上的输出会指出缺少什么，该次构建的目录会保留下来供检查。static-php-cli 的二进制和 Zig 在运行前都会按固定的 SHA-256 摘要校验，所以镜像无法篡改或替换 Zig。php.net 的查询结果和 Zig 镜像列表都缓存一天，php.net 或 ziglang.org 无法访问时沿用缓存的结果。

下载时使用环境变量指定的代理，规则与 curl 相同：HTTPS 用 `https_proxy` 或 `HTTPS_PROXY`，HTTP 用 `http_proxy` 或 `HTTP_PROXY`；`no_proxy` 或 `NO_PROXY` 列出不走代理的主机（以逗号分隔，包括其子域名）。SKERNEL 调用的 static-php-cli 和 Composer 也读取这些变量。

## 命令

### build

```text
build [-d|--working-dir=<dir>] [--php=<version>]
```

`build` 对项目做一次完整的源码扫描，把只恢复快照运行和 Phar 运行要加载的构建产物写到项目的 `<vendor>/.cache` 目录，vendor 目录遵循项目的 `config.vendor-dir`：包括扫描快照，以及构建发布 `SuperKernel\Event\ApplicationBuild` 时项目各组件写出的产物，例如 `super-kernel/aop` 预先准备好的代理。项目必须已经安装好依赖，其中包括 `super-kernel/context` 和 `super-kernel/di`；扫描覆盖的包以这次安装为准。

构建在受管 PHP CLI 的一个单独进程中运行。该进程加载项目自己的 autoloader，引导出项目的容器；项目同时具备该事件和事件分发器时，再通过项目的 PSR-14 事件分发器发布 `ApplicationBuild`。整个过程不解析也不运行应用。该进程写出的所有内容（包括项目的扫描诊断）都转到 STDERR；成功时 STDOUT 只有一行：

```text
Build artifacts written to /path/to/my-app/vendor/.cache.
```

项目或依赖不存在、配置文件或 `--php` 的值无效、无法提供受管 PHP，或构建失败时，会在 STDERR 上打印一条简短的提示，退出码为 1。

### package

```text
package [-d|--working-dir=<dir>] [--php=<version>] [--phar] [--dev] [--output=<path>]
        [--composer=<binary>] [--runtime=<micro.sfx>]
```

`package` 把项目做成可直接执行的独立二进制，指定 `--phar` 时则做成 PHAR。它在项目的临时副本上工作，不会改动项目本身：

1. 复制项目，但不包括 vendor 目录、`.git` 等 VCS 元数据、`target/` 目录，以及根目录下的 `.env` 和 `.env.*` 文件（这些文件通常保存本机密钥）；打包后的应用从进程环境变量读取环境配置。
2. 按项目的 `composer.lock` 为副本安装依赖，使用的 Composer 由 `--composer` 指定，可以是路径，也可以是在 `PATH` 中查找的名称，默认是 `composer`。除非指定 `--dev`，否则不安装开发依赖。项目必须有 `composer.lock`，但项目自身不必安装依赖。Composer 不会按运行它的那个 PHP 检查平台要求，因为包的运行平台是受管 PHP。
3. 用该次安装对应的受管 PHP 对副本运行 `build`。
4. 把副本归档为 PHAR，它的 stub 加载副本的 autoloader、获取应用容器并运行应用，因此不需要入口脚本。
5. 把受管 micro 运行时（或 `--runtime` 指定的文件）和 PHAR 依次写入输出路径；指定 `--phar` 时只写入 PHAR。结果文件会被设为可执行。

输出路径由 `--output` 指定，默认是 `target/<name>`，指定 `--phar` 时是 `target/<name>.phar`；其中 `<name>` 是根包名称的最后一段，根包没有名称时取项目目录名。副本的 Composer、构建和归档进程写出的所有内容都转到 STDERR；成功时 STDOUT 只有一行：

```text
Package written to /path/to/my-app/target/my-app.
```

缺少 `composer.lock`、找不到运行时文件、安装、构建或归档失败，或出现 `build` 的任一种失败时，会在 STDERR 上打印一条简短的提示，退出码为 1。

## 环境要求

- PHP `>=8.4.27 <8.5`，并带有 `openssl`、`phar` 和 `zlib` 扩展；安装时由 Composer 检查所需的 PHP 扩展。
- 首次构建受管 PHP 时需要联网，以及 static-php-cli 的构建工具，详见[受管 PHP](#受管-php)。
- `package` 需要 Composer。

## 从源码运行

在二进制文件发布之前，请从本仓库的克隆中运行 SKERNEL。SuperKernel 根据工作目录识别应用的根目录，所以请在 SKERNEL 自己的目录中启动它，再用 `-d` 选择要处理的项目：

```bash
git clone https://github.com/super-kernel/skernel.git
cd skernel
composer install --no-dev
php bin/bootstrap.php list
php bin/bootstrap.php build -d ../my-app
php bin/bootstrap.php package -d ../my-app
```

## 许可证

SKERNEL 以 [MIT 许可证](LICENSE) 发布。
