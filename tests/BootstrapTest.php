<?php

declare(strict_types=1);

namespace Src\Tests;

use JsonException;
use PHPUnit\Framework\TestCase;

use function array_filter;
use function dirname;
use function end;
use function escapeshellarg;
use function exec;
use function file_get_contents;
use function implode;
use function json_decode;

use const JSON_THROW_ON_ERROR;
use const PHP_BINARY;

final class BootstrapTest extends TestCase
{
    /** @throws JsonException When the root manifest is not valid JSON */
    public function testVersionReportsTheRootPackageIdentity(): void
    {
        $root = dirname(__DIR__);
        $manifest = json_decode((string) file_get_contents($root . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);
        $expected = isset($manifest['version']) ? $manifest['name'] . ' ' . $manifest['version'] : $manifest['name'];
        $bootstrap = escapeshellarg($root . '/bin/bootstrap.php');

        exec(escapeshellarg(PHP_BINARY) . ' ' . $bootstrap . ' --version 2>&1', $output, $status);

        $lines = array_filter($output);

        self::assertSame(0, $status, implode("\n", $output));
        self::assertSame($expected, end($lines));
    }
}
