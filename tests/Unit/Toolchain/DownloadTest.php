<?php

declare(strict_types=1);

namespace Src\Tests\Unit\Toolchain;

use Src\Toolchain\Download;
use Symfony\Component\Console\Exception\RuntimeException;

use function file_put_contents;
use function glob;
use function hash;

use const GLOB_BRACE;

final class DownloadTest extends ToolchainTestCase
{
    public function testPublishesAFileWhoseDigestMatches(): void
    {
        self::assertNotFalse(file_put_contents($this->home . '/source', 'runtime'));

        Download::file('file://' . $this->home . '/source', $this->home . '/target', hash('sha256', 'runtime'));

        self::assertStringEqualsFile($this->home . '/target', 'runtime');
    }

    public function testDiscardsAFileWhoseDigestDiffers(): void
    {
        self::assertNotFalse(file_put_contents($this->home . '/source', 'tampered'));

        try {
            Download::file('file://' . $this->home . '/source', $this->home . '/target', hash('sha256', 'runtime'));
            self::fail('A download with a different digest must be rejected.');
        } catch (RuntimeException $exception) {
            self::assertSame(
                'The download of file://' . $this->home . '/source does not match its expected checksum.',
                $exception->getMessage(),
            );
        }

        self::assertSame([
            $this->home . '/source',
        ], glob($this->home . '/{,.}*[!.]', GLOB_BRACE));
    }
}
