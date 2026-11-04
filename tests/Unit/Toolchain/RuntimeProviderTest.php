<?php

declare(strict_types=1);

namespace Src\Tests\Unit\Toolchain;

use PHPUnit\Framework\Attributes\RequiresOperatingSystemFamily;
use Src\Toolchain\DailyCache;
use Src\Toolchain\Home;
use Src\Toolchain\Platform;
use Src\Toolchain\Runtime;
use Src\Toolchain\RuntimeProvider;
use Src\Toolchain\StaticPhpCli;
use Src\Toolchain\ZigMirrors;
use Symfony\Component\Console\Output\BufferedOutput;

use function dirname;
use function file;
use function mkdir;
use function touch;

use const FILE_IGNORE_NEW_LINES;

final class RuntimeProviderTest extends ToolchainTestCase
{
    public function testNamesRuntimesByPlatformVersionAndExtensions(): void
    {
        $provider = $this->provider();
        $directory = $provider->directory('8.4.27', [
            'phar',
            'swoole',
        ]);

        self::assertSame($this->home . '/runtimes/' . Platform::current() . '/php-8.4.27', dirname($directory));
        self::assertMatchesRegularExpression('/\/[0-9a-f]{16}\z/', $directory);
        self::assertNotSame($directory, $provider->directory('8.4.27', [
            'phar',
        ]));
    }

    public function testReusesAPublishedRuntimeWithoutRunningStaticPhpCli(): void
    {
        $provider = $this->provider();
        $directory = $provider->directory('8.4.27', [
            'phar',
        ]);
        self::assertTrue(mkdir($directory, 0o700, true));
        self::assertTrue(touch($directory . '/manifest.json'));
        $output = new BufferedOutput();

        self::assertEquals(
            new Runtime('8.4.27', $directory . '/php', $directory . '/micro.sfx'),
            $provider->provide('8.4.27', [
                'phar',
            ], $output),
        );
        self::assertSame('', $output->fetch());
        self::assertDirectoryDoesNotExist($this->home . '/tools');
        self::assertDirectoryDoesNotExist($this->home . '/work');
    }

    #[RequiresOperatingSystemFamily('Linux')]
    public function testBuildsAMicroRuntimeThatReportsTheCliSapi(): void
    {
        $this->staticPhpCli(
            'echo "$*" >> ' . $this->home . "/spc.log\n"
            . 'if [ "$1" = build ]; then mkdir -p "$BUILD_ROOT_PATH/bin"; '
            . ': > "$BUILD_ROOT_PATH/bin/php"; : > "$BUILD_ROOT_PATH/bin/micro.sfx"; fi',
        );
        $this->executable($this->home . '/pkgroot/' . Platform::current() . '/bin/pkg-config', 'exit 0');
        $provider = $this->provider();

        $runtime = $provider->provide('8.4.27', [
            'phar',
        ], new BufferedOutput());

        self::assertSame([
            'download --with-php=8.4 --for-extensions=phar '
            . '--custom-url=php-src:https://www.php.net/distributions/php-8.4.27.tar.xz '
            . '--without-suggestions --retry=2',
            'build phar --build-cli --build-micro --with-micro-fake-cli',
        ], file($this->home . '/spc.log', FILE_IGNORE_NEW_LINES));
        self::assertFileExists($runtime->micro);
    }

    private function provider(): RuntimeProvider
    {
        return new RuntimeProvider(
            new Home(),
            new StaticPhpCli(new Home(), new ZigMirrors(new DailyCache(new Home()))),
        );
    }
}
