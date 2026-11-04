<?php

declare(strict_types=1);

namespace Src\Toolchain;

use Src\Project\JsonFile;
use Src\Project\Project;
use Symfony\Component\Console\Exception\RuntimeException;

use function array_keys;
use function array_unique;
use function array_values;
use function in_array;
use function is_array;
use function sort;
use function str_starts_with;
use function strtolower;
use function substr;

/**
 * Lists the PHP extensions that a project's installation requires, by their static-php-cli names.
 *
 * The root manifest's requirements, its development requirements when they are installed, and the requirements of
 * every installed package count. Extensions compiled into every PHP build are left out; phar is always included,
 * because packaging and packaged applications need it.
 */
final class Extensions
{
    private const array BUILT_IN = [
        'core',
        'date',
        'hash',
        'json',
        'pcre',
        'random',
        'reflection',
        'spl',
        'standard',
    ];

    private const array RENAMED = [
        'zend-opcache' => 'opcache',
    ];

    /**
     * @return list<string> Sorted extension names
     *
     * @throws RuntimeException When the manifest or the installed package list cannot be read
     */
    public static function required(Project $project): array
    {
        $manifest = JsonFile::decode($project->directory . '/composer.json');
        $installed = JsonFile::decode($project->vendorDirectory . '/composer/installed.json');
        $manifest = is_array($manifest) ? $manifest : [];
        $installed = is_array($installed) ? $installed : [];
        $requirements = [
            $manifest['require'] ?? [],
        ];

        if (true === ($installed['dev'] ?? false)) {
            $requirements[] = $manifest['require-dev'] ?? [];
        }

        foreach (is_array($installed['packages'] ?? null) ? $installed['packages'] : [] as $package) {
            $requirements[] = is_array($package) ? $package['require'] ?? [] : [];
        }

        $extensions = [
            'phar',
        ];

        foreach ($requirements as $names) {
            foreach (is_array($names) ? array_keys($names) : [] as $name) {
                $name = strtolower((string) $name);

                if (!str_starts_with($name, 'ext-')) {
                    continue;
                }

                $extension = substr($name, 4);
                $extension = self::RENAMED[$extension] ?? $extension;

                if (!in_array($extension, self::BUILT_IN, true)) {
                    $extensions[] = $extension;
                }
            }
        }

        $extensions = array_values(array_unique($extensions));
        sort($extensions);

        return $extensions;
    }
}
