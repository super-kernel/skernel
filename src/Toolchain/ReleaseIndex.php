<?php

declare(strict_types=1);

namespace Src\Toolchain;

use JsonException;
use Src\Filesystem\Directory;
use Symfony\Component\Console\Exception\RuntimeException;

use function array_key_exists;
use function file_get_contents;
use function file_put_contents;
use function is_array;
use function is_file;
use function is_int;
use function json_decode;
use function json_encode;
use function rename;
use function sprintf;
use function tempnam;
use function time;
use function unlink;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;

/**
 * Answers queries against the php.net release index, caching each answer in the SKERNEL home for a day.
 *
 * When php.net cannot be reached, an older cached answer is used instead.
 */
final readonly class ReleaseIndex
{
    private const string ENDPOINT = 'https://www.php.net/releases/';

    private const int LIFETIME = 86400;

    public function __construct(private Home $home)
    {
    }

    /**
     * @param string $key   The cache key of the query
     * @param string $query The path and query below the release endpoint
     *
     * @throws RuntimeException When php.net cannot be read and nothing is cached, or the cache cannot be written
     */
    public function get(string $key, string $query): mixed
    {
        $directory = $this->home->directory() . '/cache';
        $file = $directory . '/php-releases.json';
        $cache = is_file($file) ? json_decode((string) file_get_contents($file), true) : [];
        $cache = is_array($cache) ? $cache : [];
        $entry = is_array($cache[$key] ?? null) ? $cache[$key] : null;

        if (null !== $entry && is_int($entry['checked'] ?? null) && self::LIFETIME > time() - $entry['checked']) {
            return $entry['data'] ?? null;
        }

        try {
            $data = json_decode(Download::contents(self::ENDPOINT . $query), true, 512, JSON_THROW_ON_ERROR);
        } catch (RuntimeException|JsonException $exception) {
            if (null !== $entry && array_key_exists('data', $entry)) {
                return $entry['data'];
            }

            throw new RuntimeException('Unable to read the PHP release index from php.net.', 0, $exception);
        }

        $cache[$key] = [
            'checked' => time(),
            'data'    => $data,
        ];
        Directory::create($directory);
        $encoded = json_encode($cache, JSON_UNESCAPED_SLASHES);
        $temporary = @tempnam($directory, '.releases-');

        try {
            if (
                false === $encoded
                || false === $temporary
                || false === file_put_contents($temporary, $encoded)
                || !@rename($temporary, $file)
            ) {
                throw new RuntimeException(sprintf('Unable to write %s.', $file));
            }
        } finally {
            if (false !== $temporary && is_file($temporary)) {
                @unlink($temporary);
            }
        }

        return $data;
    }
}
