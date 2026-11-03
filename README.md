<p align="right">
  <strong>English</strong> | <a href="README.zh-CN.md">中文文档</a>
</p>

# SKernel

`super-kernel/skernel` is SKernel, the command-line project tool for SuperKernel applications, filling the role that Maven plays for Java projects. It is itself built as a SuperKernel console application and is intended to be distributed as a standalone binary.

> [!NOTE]
> SKernel is under active development. It provides the `build` command; the commands that create, package, run, and clean projects and that update SKernel itself are not implemented yet. Its console also offers SKernel's `help` and `list` commands, Symfony Console's `completion` command, and the `--version` option. Binaries will be published through this repository's GitHub Releases and Packages; nothing has been published yet.

## Console

Running SKernel without a command, or with `list`, prints the SKernel logo above the plain-text overview of the available commands. The raw, JSON, XML, and Markdown listings, namespace listings, and command help omit the logo. `--version` prints `SKernel`, followed by the version when the package declares one.

SKernel writes log records of WARNING and above, including the framework's scan summaries and exception reports, to STDERR, so STDOUT carries only command output; for example, `list --format=json` writes plain JSON to STDOUT.

A usage error, such as an unknown command or option, prints a short message on STDERR, followed by the running command's synopsis when a command's input is invalid, and exits with status 1. Add `-v` to also print the exception class and trace.

Commands work on the SuperKernel application in the current directory, or in the directory given with the global `-d`/`--working-dir` option.

## Commands

### build

```text
build [-d|--working-dir=<dir>] [--php=<binary>]
```

`build` runs a full source scan of the project and writes the build artifacts that restore-only and Phar execution load to the project's `<vendor>/.cache` directory, honouring its `config.vendor-dir`: the scan snapshots, and whatever the project's components write when the build publishes `SuperKernel\Event\ApplicationBuild`, such as the prepared proxies of `super-kernel/aop`. The project must have its dependencies installed, including `super-kernel/context` and `super-kernel/di`, and the scan covers the packages of that installation.

The build runs in a separate process of the PHP binary given with `--php`, a path or a name looked up in `PATH`, by default the binary that runs SKernel. That process loads the project's own autoloader, bootstraps its container, and then publishes `ApplicationBuild` through the project's PSR-14 event dispatcher, when the project has both the event and a dispatcher, without resolving or running the application. Everything the process writes, including the project's scan diagnostics, goes to STDERR, and on success STDOUT carries one line:

```text
Build artifacts written to /path/to/my-app/vendor/.cache.
```

A missing project, missing dependencies, a PHP binary that is not found, or a failed build prints a short message on STDERR and exits with status 1.

## Requirements

- PHP `>=8.4.27 <8.5`; Composer checks the required PHP extensions during installation.

## Running from source

Until binaries are published, run SKernel from a clone of this repository. SuperKernel detects an application's root from the working directory, so start SKernel from its own directory and select the project to work on with `-d`:

```bash
git clone https://github.com/super-kernel/skernel.git
cd skernel
composer install --no-dev
php bin/bootstrap.php list
php bin/bootstrap.php build -d ../my-app
```

## License

SKernel is released under the [MIT License](LICENSE).
