<?php

declare(strict_types=1);

namespace Src\Tests\Unit\Update;

use PHPUnit\Framework\Attributes\RequiresOperatingSystemFamily;
use Src\Tests\Unit\Toolchain\ToolchainTestCase;
use Src\Toolchain\Platform;
use Src\Update\SelfUpdater;
use Symfony\Component\Console\Exception\RuntimeException;

use function chmod;
use function clearstatcache;
use function file_put_contents;
use function fileperms;
use function hash;
use function is_file;

/**
 * Installs releases whose assets are local files, so that nothing is downloaded.
 */
#[RequiresOperatingSystemFamily('Linux')]
final class SelfUpdaterTest extends ToolchainTestCase
{
    private string $binary;

    protected function setUp(): void
    {
        parent::setUp();
        $this->binary = $this->home . '/skernel';
        self::assertNotFalse(file_put_contents($this->binary, 'old'));
        self::assertTrue(chmod($this->binary, 0o750));
    }

    public function testReplacesTheBinaryAndKeepsItsPermissions(): void
    {
        self::assertSame('v2.0.0', new SelfUpdater()->install($this->binary, $this->release('new')));

        self::assertStringEqualsFile($this->binary, 'new');
        clearstatcache();
        self::assertSame(0o750, fileperms($this->binary) & 0o777);
        self::assertFalse(is_file($this->binary . '.update'));
    }

    public function testLeavesABinaryThatIsUpToDate(): void
    {
        $release = $this->release('old');
        self::assertNotFalse(file_put_contents($this->home . '/asset', 'unexpected'));

        self::assertNull(new SelfUpdater()->install($this->binary, $release));
        self::assertStringEqualsFile($this->binary, 'old');
    }

    public function testKeepsTheBinaryWhenTheDownloadDoesNotMatchItsDigest(): void
    {
        $release = $this->release('new');
        self::assertNotFalse(file_put_contents($this->home . '/asset', 'tampered'));

        try {
            new SelfUpdater()->install($this->binary, $release);
            self::fail('A download that does not match its digest must not be installed.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('does not match its expected checksum', $exception->getMessage());
        }

        self::assertStringEqualsFile($this->binary, 'old');
        self::assertFalse(is_file($this->binary . '.update'));
    }

    public function testRejectsAReleaseWithoutABinaryForThisPlatform(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs(
            'The SKERNEL release v2.0.0 has no binary named skernel-' . Platform::current() . '.',
        );

        new SelfUpdater()->install($this->binary, [
            'tag_name' => 'v2.0.0',
            'assets'   => [
                [
                    'name' => 'skernel-other',
                ],
            ],
        ]);
    }

    public function testRejectsABinaryWithoutADigest(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs(
            'The SKERNEL release v2.0.0 reports no SHA-256 digest for skernel-' . Platform::current() . '.',
        );

        new SelfUpdater()->install($this->binary, [
            'tag_name' => 'v2.0.0',
            'assets'   => [
                [
                    'name'                 => 'skernel-' . Platform::current(),
                    'browser_download_url' => 'file://' . $this->home . '/asset',
                    'digest'               => null,
                ],
            ],
        ]);
    }

    /** @return array<string, mixed> A release as the GitHub API describes it, whose asset holds the given contents */
    private function release(string $contents): array
    {
        self::assertNotFalse(file_put_contents($this->home . '/asset', $contents));

        return [
            'tag_name' => 'v2.0.0',
            'assets'   => [
                [
                    'name'                 => 'skernel-' . Platform::current(),
                    'browser_download_url' => 'file://' . $this->home . '/asset',
                    'digest'               => 'sha256:' . hash('sha256', $contents),
                ],
            ],
        ];
    }
}
