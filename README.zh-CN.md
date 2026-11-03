<p align="right">
  <a href="README.md">English</a> | <strong>中文文档</strong>
</p>

# SKernel

`super-kernel/skernel` 即 SKernel，是 SuperKernel 应用的命令行项目工具，作用相当于 Java 项目中的 Maven。它本身也是用 SuperKernel 构建的控制台应用，计划以独立的二进制文件发布。

> [!NOTE]
> SKernel 仍在积极开发中，目前还没有提供项目、构建或打包命令。控制台现在有 SKernel 自己的 `help` 和 `list` 命令、Symfony Console 的 `completion` 命令，以及 `--version` 选项。二进制文件将通过本仓库的 GitHub Releases 和 Packages 发布，目前还没有发布任何内容。

## 控制台

不带命令运行 SKernel，或运行 `list` 时，纯文本的命令总览上方会打印 SKernel 标志。raw、JSON、XML 和 Markdown 格式的列表、按命名空间列出的列表以及命令帮助都不打印标志。`--version` 输出 `SKernel`，包声明了版本时后面跟上版本号。

SKernel 把 WARNING 及以上级别的日志（包括框架的扫描汇总和异常报告）写到 STDERR，STDOUT 只承载命令本身的输出；例如 `list --format=json` 在 STDOUT 上输出的就是纯 JSON。

命令不存在、选项不存在这类用法错误，会在 STDERR 上打印一条简短的提示；如果是某个命令的输入有误，后面还会打印该命令的用法。退出码为 1。加上 `-v` 时还会显示异常类名和堆栈。

## 环境要求

- PHP `>=8.4.27 <8.5`；安装时由 Composer 检查所需的 PHP 扩展。

## 从源码运行

在二进制文件发布之前，可以从本仓库的克隆中运行 SKernel：

```bash
git clone https://github.com/super-kernel/skernel.git
cd skernel
composer install --no-dev
php bin/bootstrap.php list
```

## 许可证

SKernel 以 [MIT 许可证](LICENSE) 发布。
