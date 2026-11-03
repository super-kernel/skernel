<?php

declare(strict_types=1);

namespace Src\Tests\Unit\Build;

use PHPUnit\Framework\TestCase;
use Src\Build\SnapshotBuilder;
use Src\Project\Project;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Output\BufferedOutput;

use function dirname;

final class SnapshotBuilderTest extends TestCase
{
    public function testRejectsAPhpBinaryThatIsNotFound(): void
    {
        $root = dirname(__DIR__, 3);
        $output = new BufferedOutput();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs('The PHP binary skernel-missing-php was not found.');

        try {
            new SnapshotBuilder()->build(new Project($root, $root . '/vendor'), 'skernel-missing-php', $output);
        } finally {
            self::assertSame('', $output->fetch());
        }
    }
}
