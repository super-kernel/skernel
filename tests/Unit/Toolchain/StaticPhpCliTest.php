<?php

declare(strict_types=1);

namespace Src\Tests\Unit\Toolchain;

use PHPUnit\Framework\Attributes\RequiresOperatingSystemFamily;
use Src\Toolchain\DailyCache;
use Src\Toolchain\Home;
use Src\Toolchain\Platform;
use Src\Toolchain\StaticPhpCli;
use Src\Toolchain\ZigMirrors;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Output\BufferedOutput;

use function chmod;
use function dirname;
use function file_put_contents;
use function mkdir;
use function strlen;
use function substr;

/**
 * Runs a recording static-php-cli script with a Zig that is installed already, so that nothing is downloaded.
 */
#[RequiresOperatingSystemFamily('Linux')]
final class StaticPhpCliTest extends ToolchainTestCase
{
    private const string RECORD = 'printf "%s|%s|%s|%s\n" "$*" "$SPC_TARGET" "$PKG_ROOT_PATH" "$SOURCE_PATH"';

    public function testRunsWithTheInstalledZigForAStaticMuslTarget(): void
    {
        $packages = $this->home . '/pkgroot/' . Platform::current();
        $staticPhpCli = $this->staticPhpCli(self::RECORD);
        $this->executable($packages . '/bin/pkg-config', 'exit 0');
        $job = $this->job();
        $output = new BufferedOutput();

        self::assertSame(0, $staticPhpCli->run([
            'download',
            '--for-extensions=phar',
        ], $job, $output));
        self::assertSame(
            'download --for-extensions=phar|' . self::target() . '|' . $packages . '|' . $job . "/source\n",
            $output->fetch(),
        );
    }

    public function testInstallsPkgConfigThroughStaticPhpCliBeforeTheFirstRun(): void
    {
        $packages = $this->home . '/pkgroot/' . Platform::current();
        $staticPhpCli = $this->staticPhpCli(self::RECORD);
        $job = $this->job();
        $output = new BufferedOutput();

        self::assertSame(0, $staticPhpCli->run([
            'download',
        ], $job, $output));
        self::assertSame(
            'install-pkg pkg-config|' . self::target() . '|' . $packages . '|' . $job . "/source\n"
            . 'download|' . self::target() . '|' . $packages . '|' . $job . "/source\n",
            $output->fetch(),
        );
    }

    public function testFailsWhenPkgConfigCannotBeInstalled(): void
    {
        $staticPhpCli = $this->staticPhpCli('exit 3');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs('static-php-cli could not install pkg-config (exit status 3).');

        $staticPhpCli->run([
            'download',
        ], $this->job(), new BufferedOutput());
    }

    /** Installs a static-php-cli script and a Zig that needs no download */
    private function staticPhpCli(string $script): StaticPhpCli
    {
        $staticPhpCli = new StaticPhpCli(new Home(), new ZigMirrors(new DailyCache(new Home())));
        $packages = $this->home . '/pkgroot/' . Platform::current();
        $this->executable($this->home . '/tools/spc/' . $staticPhpCli->version() . '/spc', $script);
        $this->executable($packages . '/zig/zig', 'echo 0.17.1');

        foreach ([
            'zig-cc',
            'zig-c++',
            'zig-ar',
            'zig-ld.lld',
            'zig-ranlib',
            'zig-objcopy',
        ] as $wrapper) {
            $this->executable($packages . '/zig/' . $wrapper, 'exit 0');
        }

        return $staticPhpCli;
    }

    private static function target(): string
    {
        return substr(Platform::current(), strlen('linux-')) . '-linux-musl';
    }

    private function job(): string
    {
        $job = $this->home . '/work/job';
        self::assertTrue(mkdir($job, 0o700, true));

        return $job;
    }

    private function executable(string $path, string $script): void
    {
        @mkdir(dirname($path), 0o700, true);
        self::assertNotFalse(file_put_contents($path, "#!/bin/sh\n" . $script . "\n"));
        self::assertTrue(chmod($path, 0o755));
    }
}
