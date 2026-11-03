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
use function preg_replace;

use const JSON_THROW_ON_ERROR;
use const PHP_BINARY;

final class BootstrapTest extends TestCase
{
    private const string LOGO = <<<'LOGO'
           _____ __ __                     __
          / ___// //_/__  _________  ___  / /
          \__ \/ ,< / _ \/ ___/ __ \/ _ \/ /
         ___/ / /| /  __/ /  / / / /  __/ /
        /____/_/ |_\___/_/  /_/ /_/\___/_/
        LOGO;

    /** @throws JsonException When the root manifest is not valid JSON */
    public function testVersionReportsSKernelWithTheRootPackageVersion(): void
    {
        $manifest = json_decode(
            (string) file_get_contents(dirname(__DIR__) . '/composer.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $expected = isset($manifest['version']) ? 'SKernel ' . $manifest['version'] : 'SKernel';

        $output = $this->bootstrap('--version', $status);
        $lines = array_filter($output);

        self::assertSame(0, $status, implode("\n", $output));
        self::assertSame($expected, end($lines));
    }

    public function testOverviewOpensWithTheSKernelLogo(): void
    {
        $output = $this->bootstrap('--no-ansi', $status);

        self::assertSame(0, $status, implode("\n", $output));
        self::assertStringContainsString(self::LOGO . "\nSKernel", implode("\n", $output));
    }

    /** @throws JsonException When standard output is not exactly the JSON listing */
    public function testStandardOutputCarriesOnlyTheCommandOutput(): void
    {
        $output = $this->bootstrap('list --format=json', $status, '2>/dev/null');
        $listing = json_decode(implode("\n", $output), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(0, $status);
        self::assertSame('SKernel', $listing['application']['name']);
    }

    public function testLogRecordsBelowWarningAreNotWritten(): void
    {
        $output = $this->bootstrap('list --format=json', $status, '2>&1 >/dev/null');

        self::assertSame(0, $status, implode("\n", $output));
        self::assertStringNotContainsString('[INFO]', implode("\n", $output));
    }

    public function testBuildWritesTheScanSnapshotsOfTheProject(): void
    {
        $root = dirname(__DIR__);
        $output = $this->bootstrap('build -d ' . escapeshellarg($root), $status, '2>/dev/null');

        self::assertSame(0, $status);
        self::assertSame([
            'Scan snapshots written to ' . $root . '/vendor/.cache.',
        ], $output);
        self::assertFileExists($root . '/vendor/.cache/packages.ser');
    }

    public function testBuildRejectsADirectoryWithoutAProject(): void
    {
        $missing = dirname(__DIR__) . '/missing';
        $output = $this->bootstrap('build -d ' . escapeshellarg($missing), $status, '2>&1 >/dev/null');
        $message = (string) preg_replace('/\s+/', ' ', implode(' ', $output));

        self::assertSame(1, $status);
        self::assertStringContainsString('The project directory ' . $missing . ' does not exist.', $message);
    }

    /** @return list<string> */
    private function bootstrap(string $arguments, ?int &$status, string $redirection = '2>&1'): array
    {
        $bootstrap = escapeshellarg(dirname(__DIR__) . '/bin/bootstrap.php');

        exec(escapeshellarg(PHP_BINARY) . ' ' . $bootstrap . ' ' . $arguments . ' ' . $redirection, $output, $status);

        return $output;
    }
}
