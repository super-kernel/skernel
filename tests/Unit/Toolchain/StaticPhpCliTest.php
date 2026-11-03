<?php

declare(strict_types=1);

namespace Src\Tests\Unit\Toolchain;

use PHPUnit\Framework\Attributes\RequiresOperatingSystemFamily;
use Src\Toolchain\Home;
use Src\Toolchain\Platform;
use Src\Toolchain\StaticPhpCli;
use Symfony\Component\Console\Output\BufferedOutput;

use function chmod;
use function dirname;
use function file_put_contents;
use function mkdir;

/**
 * Runs a recording static-php-cli script with a Zig that is installed already, so that nothing is downloaded.
 */
#[RequiresOperatingSystemFamily('Linux')]
final class StaticPhpCliTest extends ToolchainTestCase
{
    public function testRunsWithTheInstalledZigForAStaticMuslTarget(): void
    {
        $staticPhpCli = new StaticPhpCli(new Home());
        $packages = $this->home . '/pkgroot/' . Platform::current();
        $this->executable(
            $this->home . '/tools/spc/' . $staticPhpCli->version() . '/spc',
            'printf "%s|%s|%s|%s\n" "$*" "$SPC_TARGET" "$PKG_ROOT_PATH" "$SOURCE_PATH"',
        );
        $this->executable($packages . '/zig/zig', 'echo 0.16.0');

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

        $job = $this->home . '/work/job';
        self::assertTrue(mkdir($job, 0o700, true));
        $output = new BufferedOutput();

        self::assertSame(0, $staticPhpCli->run([
            'download',
            '--for-extensions=phar',
        ], $job, $output));
        self::assertSame(
            'download --for-extensions=phar|native-native-musl|' . $packages . '|' . $job . "/source\n",
            $output->fetch(),
        );
    }

    private function executable(string $path, string $script): void
    {
        @mkdir(dirname($path), 0o700, true);
        self::assertNotFalse(file_put_contents($path, "#!/bin/sh\n" . $script . "\n"));
        self::assertTrue(chmod($path, 0o755));
    }
}
