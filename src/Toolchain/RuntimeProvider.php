<?php

declare(strict_types=1);

namespace Src\Toolchain;

use Src\Filesystem\Directory;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;

use function array_slice;
use function dirname;
use function explode;
use function file_put_contents;
use function hash;
use function hash_file;
use function implode;
use function is_file;
use function json_encode;
use function rename;
use function sprintf;
use function str_starts_with;
use function substr;
use function uniqid;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;

/**
 * Provides the managed static PHP CLI and micro runtime of a PHP version and extension set.
 *
 * A missing runtime is built with static-php-cli in its own job below `work/` and published to
 * `runtimes/<platform>/php-<version>/<extension-hash>/` by atomic rename; the job is removed after success and kept
 * for inspection after a failure.
 */
final readonly class RuntimeProvider
{
    public function __construct(private Home $home, private StaticPhpCli $staticPhpCli)
    {
    }

    /**
     * @param string       $version    An exact PHP version
     * @param list<string> $extensions Sorted static-php-cli extension names
     *
     * @throws RuntimeException When static-php-cli cannot download or build the runtime, or it cannot be published
     */
    public function provide(string $version, array $extensions, OutputInterface $output): Runtime
    {
        $home = $this->home->directory();
        $platform = Platform::current();
        $list = implode(',', $extensions);
        $directory = $this->directory($version, $extensions);
        $php = str_starts_with($platform, 'windows-') ? 'php.exe' : 'php';
        $runtime = new Runtime($version, $directory . '/' . $php, $directory . '/micro.sfx');

        if (is_file($directory . '/manifest.json')) {
            return $runtime;
        }

        $job = $home . '/work/' . uniqid('runtime-', true);
        Directory::create($job);
        $status = $this->staticPhpCli->run([
            'download',
            '--with-php=' . implode('.', array_slice(explode('.', $version), 0, 2)),
            '--for-extensions=' . $list,
            '--custom-url=php-src:https://www.php.net/distributions/php-' . $version . '.tar.xz',
            '--prefer-pre-built',
        ], $job, $output);

        if (0 !== $status) {
            throw new RuntimeException(sprintf(
                'static-php-cli could not download PHP %s and its libraries (exit status %d); the job is kept in %s.',
                $version,
                $status,
                $job,
            ));
        }

        $status = $this->staticPhpCli->run([
            'build',
            $list,
            '--build-cli',
            '--build-micro',
        ], $job, $output);

        if (0 !== $status) {
            throw new RuntimeException(sprintf(
                'static-php-cli could not build PHP %s with %s (exit status %d); install the build tools it reports. '
                . 'The job is kept in %s.',
                $version,
                $list,
                $status,
                $job,
            ));
        }

        $staged = $job . '/runtime';
        Directory::create($staged);
        $manifest = [
            'php'            => $version,
            'extensions'     => $extensions,
            'static-php-cli' => $this->staticPhpCli->version(),
            'platform'       => $platform,
        ];

        if (
            !@rename($job . '/buildroot/bin/' . $php, $staged . '/' . $php)
            || !@rename($job . '/buildroot/bin/micro.sfx', $staged . '/micro.sfx')
        ) {
            throw new RuntimeException(sprintf('static-php-cli left no PHP binaries in %s/buildroot/bin.', $job));
        }

        $manifest['sha256'] = [
            $php        => hash_file('sha256', $staged . '/' . $php),
            'micro.sfx' => hash_file('sha256', $staged . '/micro.sfx'),
        ];
        $encoded = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (false === $encoded || false === file_put_contents($staged . '/manifest.json', $encoded . "\n")) {
            throw new RuntimeException(sprintf('Unable to write %s/manifest.json.', $staged));
        }

        Directory::create(dirname($directory));

        if (!@rename($staged, $directory) && !is_file($directory . '/manifest.json')) {
            throw new RuntimeException(sprintf('Unable to publish the runtime to %s.', $directory));
        }

        Directory::remove($job);

        return $runtime;
    }

    /**
     * Names the directory of a runtime: its platform, PHP version, and a hash of its extensions and of the
     * static-php-cli release that builds it.
     *
     * @param list<string> $extensions Sorted static-php-cli extension names
     *
     * @throws RuntimeException When the home directory cannot be located or the platform is unsupported
     */
    public function directory(string $version, array $extensions): string
    {
        $key = substr(hash('sha256', implode(',', $extensions) . "\0" . $this->staticPhpCli->version()), 0, 16);

        return sprintf('%s/runtimes/%s/php-%s/%s', $this->home->directory(), Platform::current(), $version, $key);
    }
}
