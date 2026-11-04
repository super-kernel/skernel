<?php

declare(strict_types=1);

namespace Src\Tests\Unit\Toolchain;

use Src\Toolchain\DailyCache;
use Src\Toolchain\Home;
use Src\Toolchain\Platform;
use Src\Toolchain\Runtime;
use Src\Toolchain\RuntimeProvider;
use Src\Toolchain\StaticPhpCli;
use Src\Toolchain\ZigMirrors;
use Symfony\Component\Console\Output\BufferedOutput;

use function dirname;
use function mkdir;
use function touch;

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

    private function provider(): RuntimeProvider
    {
        return new RuntimeProvider(
            new Home(),
            new StaticPhpCli(new Home(), new ZigMirrors(new DailyCache(new Home()))),
        );
    }
}
