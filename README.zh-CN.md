<p align="right">
  <a href="README.md">English</a> | <strong>中文文档</strong>
</p>

# SKernel

`super-kernel/skernel` 即 SKernel，是 SuperKernel 应用的命令行项目工具，作用相当于 Java 项目中的 Maven。它本身也是用 SuperKernel 构建的控制台应用，计划以独立的二进制文件发布。

> [!NOTE]
> SKernel 仍在积极开发中。目前提供 `build` 命令；创建、打包、运行、清理项目以及更新 SKernel 自身的命令还没有实现。控制台另有 SKernel 自己的 `help` 和 `list` 命令、Symfony Console 的 `completion` 命令，以及 `--version` 选项。二进制文件将通过本仓库的 GitHub Releases 和 Packages 发布，目前还没有发布任何内容。

## 控制台

不带命令运行 SKernel，或运行 `list` 时，纯文本的命令总览上方会打印 SKernel 标志。raw、JSON、XML 和 Markdown 格式的列表、按命名空间列出的列表以及命令帮助都不打印标志。`--version` 输出 `SKernel`，包声明了版本时后面跟上版本号。

SKernel 把 WARNING 及以上级别的日志（包括框架的扫描汇总和异常报告）写到 STDERR，STDOUT 只承载命令本身的输出；例如 `list --format=json` 在 STDOUT 上输出的就是纯 JSON。

命令不存在、选项不存在这类用法错误，会在 STDERR 上打印一条简短的提示；如果是某个命令的输入有误，后面还会打印该命令的用法。退出码为 1。加上 `-v` 时还会显示异常类名和堆栈。

各命令处理的是当前目录中的 SuperKernel 应用，或全局选项 `-d`/`--working-dir` 指定的目录中的应用。

## 命令

### build

```text
build [-d|--working-dir=<dir>] [--php=<binary>]
```

`build` 对项目做一次完整的源码扫描，把只恢复快照运行和 Phar 运行所需的扫描快照写到项目的 `<vendor>/.cache` 目录，vendor 目录遵循项目的 `config.vendor-dir`。项目必须已经安装好依赖，其中包括 `super-kernel/context` 和 `super-kernel/di`；扫描覆盖的包以这次安装为准。

扫描在一个单独的 PHP 进程中运行，所用的 PHP 由 `--php` 指定，可以是路径，也可以是在 `PATH` 中查找的名称，默认是运行 SKernel 的那个 PHP。该进程加载项目自己的 autoloader，只引导出项目的容器，不解析也不运行应用。该进程写出的所有内容（包括项目的扫描诊断）都转到 STDERR；成功时 STDOUT 只有一行：

```text
Scan snapshots written to /path/to/my-app/vendor/.cache.
```

项目不存在、依赖没有安装、找不到 PHP 或扫描失败时，会在 STDERR 上打印一条简短的提示，退出码为 1。

## 环境要求

- PHP `>=8.4.27 <8.5`；安装时由 Composer 检查所需的 PHP 扩展。

## 从源码运行

在二进制文件发布之前，可以从本仓库的克隆中运行 SKernel。SuperKernel 根据工作目录识别应用的根目录，所以要在 SKernel 自己的目录中启动它，再用 `-d` 选择要处理的项目：

```bash
git clone https://github.com/super-kernel/skernel.git
cd skernel
composer install --no-dev
php bin/bootstrap.php list
php bin/bootstrap.php build -d ../my-app
```

## 许可证

SKernel 以 [MIT 许可证](LICENSE) 发布。
