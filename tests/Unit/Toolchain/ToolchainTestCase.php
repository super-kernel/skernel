<?php

declare(strict_types=1);

namespace Src\Tests\Unit\Toolchain;

use PHPUnit\Framework\TestCase;
use Src\Filesystem\Directory;

use function getenv;
use function is_string;
use function mkdir;
use function putenv;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

/**
 * Points SKERNEL_HOME at a temporary directory for each test so that no test reads or writes the user's home.
 */
abstract class ToolchainTestCase extends TestCase
{
    protected string $home;

    private string|false $previousHome;

    protected function setUp(): void
    {
        $home = tempnam(sys_get_temp_dir(), 'skernel-home-');
        self::assertIsString($home);
        self::assertTrue(unlink($home) && mkdir($home, 0o700));
        $this->home = $home;
        $this->previousHome = getenv('SKERNEL_HOME');
        putenv('SKERNEL_HOME=' . $home);
    }

    protected function tearDown(): void
    {
        putenv(is_string($this->previousHome) ? 'SKERNEL_HOME=' . $this->previousHome : 'SKERNEL_HOME');
        Directory::remove($this->home);
    }
}
