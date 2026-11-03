<p align="right">
  <strong>English</strong> | <a href="README.zh-CN.md">中文文档</a>
</p>

# SKernel

`super-kernel/skernel` is SKernel, the command-line project tool for SuperKernel applications, filling the role that Maven plays for Java projects. It is itself built as a SuperKernel console application and is intended to be distributed as a standalone binary.

> [!NOTE]
> SKernel is under active development and provides no project, build, or packaging commands yet. Its console currently offers SKernel's `help` and `list` commands, Symfony Console's `completion` command, and the `--version` option. Binaries will be published through this repository's GitHub Releases and Packages; nothing has been published yet.

## Console

Running SKernel without a command, or with `list`, prints the SKernel logo above the plain-text overview of the available commands. The raw, JSON, XML, and Markdown listings, namespace listings, and command help omit the logo. `--version` prints `SKernel`, followed by the version when the package declares one.

SKernel writes log records of WARNING and above, including the framework's scan summaries and exception reports, to STDERR, so STDOUT carries only command output; for example, `list --format=json` writes plain JSON to STDOUT.

## Requirements

- PHP `>=8.4.27 <8.5`; Composer checks the required PHP extensions during installation.

## Running from source

Until binaries are published, run SKernel from a clone of this repository:

```bash
git clone https://github.com/super-kernel/skernel.git
cd skernel
composer install --no-dev
php bin/bootstrap.php list
```

## License

SKernel is released under the [MIT License](LICENSE).
