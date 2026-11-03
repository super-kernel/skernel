<?php

declare(strict_types=1);

namespace Src\Toolchain;

use PharData;
use Src\Filesystem\Directory;
use Src\Process\ProcessRunner;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function chmod;
use function is_file;
use function rename;
use function sprintf;
use function str_ends_with;
use function str_starts_with;
use function uniqid;
use function unlink;

/**
 * Runs the pinned static-php-cli release, downloading and verifying its self-contained binary on first use.
 *
 * Its downloads and prebuilt libraries live in the SKERNEL home; sources and build output stay in the caller's job.
 * SKERNEL never runs its doctor command, which may try to install system packages with sudo.
 */
final readonly class StaticPhpCli
{
    private const string VERSION = '2.8.5';

    private const string RELEASES = 'https://github.com/crazywhalecc/static-php-cli/releases/download/';

    /** @var array<string, array{string, string}> Release asset and SHA-256 digest by platform */
    private const array ASSETS = [
        'linux-aarch64' => [
            'spc-linux-aarch64.tar.gz',
            '675a3840dcdc4ed041fe20eaa54310ce019a9984c1c03951df9ec66df5795213',
        ],
        'linux-x86_64' => [
            'spc-linux-x86_64.tar.gz',
            '523ba4279c54c7a377156c0dd3a36adf92ee64b01e9a7f5e9e2ec084b8e458e5',
        ],
        'macos-aarch64' => [
            'spc-macos-aarch64.tar.gz',
            'acf2f25d56d0cbf8e65aa82e5054fef555f7be7c5c38046c6e0819f266d83225',
        ],
        'macos-x86_64' => [
            'spc-macos-x86_64.tar.gz',
            'e8b798048f62ca4960764196543b60ae703f7174aa418824cf542aeec1d2cd6a',
        ],
        'windows-x86_64' => [
            'spc-windows-x64.exe',
            '425b54ab857e21409c1fd9b818899ffebabd1e2817ef0a0ed5ae8a3d9f5b463b',
        ],
    ];

    public function __construct(private Home $home)
    {
    }

    public function version(): string
    {
        return self::VERSION;
    }

    /**
     * Runs a static-php-cli command in a job directory, which also holds its sources and build output.
     *
     * @param non-empty-list<string> $arguments
     *
     * @return int The exit status of static-php-cli
     *
     * @throws RuntimeException When the platform is unsupported or the binary cannot be installed or started
     */
    public function run(array $arguments, string $job, OutputInterface $output): int
    {
        $home = $this->home->directory();
        $platform = Platform::current();
        [$asset, $digest] = self::ASSETS[$platform] ?? throw new RuntimeException(sprintf(
            'static-php-cli %s has no release for %s.',
            self::VERSION,
            $platform,
        ));
        $directory = $home . '/tools/spc/' . self::VERSION;
        $executable = $directory . (str_starts_with($platform, 'windows-') ? '/spc.exe' : '/spc');

        if (!is_file($executable)) {
            $staging = $directory . '.' . uniqid('', true);

            try {
                Directory::create($staging);
                Download::file(self::RELEASES . self::VERSION . '/' . $asset, $staging . '/' . $asset, $digest);

                if (str_ends_with($asset, '.tar.gz')) {
                    new PharData($staging . '/' . $asset)->extractTo($staging, null, true);
                    unlink($staging . '/' . $asset);
                } else {
                    rename($staging . '/' . $asset, $staging . '/spc.exe');
                }

                chmod($staging . (str_starts_with($platform, 'windows-') ? '/spc.exe' : '/spc'), 0o755);

                if (!@rename($staging, $directory) && !is_file($executable)) {
                    throw new RuntimeException(sprintf('Unable to install static-php-cli into %s.', $directory));
                }
            } catch (RuntimeException $exception) {
                throw $exception;
            } catch (Throwable $throwable) {
                throw new RuntimeException(
                    sprintf('Unable to unpack static-php-cli %s.', self::VERSION),
                    0,
                    $throwable,
                );
            } finally {
                Directory::remove($staging);
            }
        }

        return ProcessRunner::run([
            $executable,
            ...$arguments,
        ], $job, $output, sprintf('static-php-cli %s', self::VERSION), [
            'BUILD_ROOT_PATH' => $job . '/buildroot',
            'DOWNLOAD_PATH'   => $home . '/downloads',
            'PKG_ROOT_PATH'   => $home . '/pkgroot/' . $platform,
            'SOURCE_PATH'     => $job . '/source',
        ]);
    }
}
