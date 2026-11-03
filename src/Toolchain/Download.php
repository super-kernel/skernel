<?php

declare(strict_types=1);

namespace Src\Toolchain;

use Symfony\Component\Console\Exception\RuntimeException;

use function dirname;
use function fclose;
use function file_get_contents;
use function fopen;
use function hash_equals;
use function hash_file;
use function is_file;
use function rename;
use function sprintf;
use function stream_context_create;
use function stream_copy_to_stream;
use function tempnam;
use function unlink;

/**
 * Downloads over HTTPS, following redirects, and publishes verified files by atomic rename.
 */
final class Download
{
    private const array OPTIONS = [
        'http' => [
            'follow_location' => 1,
            'timeout'         => 60,
            'user_agent'      => 'SKernel',
        ],
    ];

    /** @throws RuntimeException When the URL cannot be read */
    public static function contents(string $url): string
    {
        $contents = @file_get_contents($url, false, stream_context_create(self::OPTIONS));

        if (false === $contents) {
            throw new RuntimeException(sprintf('Unable to download %s.', $url));
        }

        return $contents;
    }

    /**
     * Writes the download to a temporary file beside the target and renames it once its SHA-256 digest matches.
     *
     * @throws RuntimeException When the URL cannot be read, the digest differs, or the file cannot be published
     */
    public static function file(string $url, string $target, string $sha256): void
    {
        $temporary = @tempnam(dirname($target), '.download-');

        if (false === $temporary) {
            throw new RuntimeException(sprintf('Unable to create a temporary file beside %s.', $target));
        }

        try {
            $source = @fopen($url, 'rb', false, stream_context_create(self::OPTIONS));
            $destination = @fopen($temporary, 'wb');

            if (false === $source || false === $destination || false === stream_copy_to_stream($source, $destination)) {
                throw new RuntimeException(sprintf('Unable to download %s.', $url));
            }

            fclose($source);
            fclose($destination);

            if (!hash_equals($sha256, (string) hash_file('sha256', $temporary))) {
                throw new RuntimeException(sprintf('The download of %s does not match its expected checksum.', $url));
            }

            if (!@rename($temporary, $target)) {
                throw new RuntimeException(sprintf('Unable to write %s.', $target));
            }
        } finally {
            if (is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }
}
