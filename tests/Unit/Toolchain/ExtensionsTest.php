<?php

declare(strict_types=1);

namespace Src\Tests\Unit\Toolchain;

use Src\Project\Project;
use Src\Toolchain\Extensions;

use function file_put_contents;
use function json_encode;
use function mkdir;

final class ExtensionsTest extends ToolchainTestCase
{
    public function testListsTheExtensionsThatTheInstallationRequires(): void
    {
        self::assertSame([
            'opcache',
            'pcntl',
            'phar',
            'swoole',
            'tokenizer',
        ], Extensions::required($this->project(false)));
    }

    public function testCountsDevelopmentRequirementsOnlyWhenTheyAreInstalled(): void
    {
        self::assertSame([
            'dom',
            'opcache',
            'pcntl',
            'phar',
            'swoole',
            'tokenizer',
        ], Extensions::required($this->project(true)));
    }

    private function project(bool $dev): Project
    {
        self::assertTrue(mkdir($this->home . '/vendor/composer', 0o700, true));
        self::assertNotFalse(file_put_contents($this->home . '/composer.json', (string) json_encode([
            'require' => [
                'php'              => '>=8.4',
                'ext-swoole'       => '*',
                'ext-json'         => '*',
                'ext-zend-opcache' => '*',
            ],
            'require-dev' => [
                'ext-dom' => '*',
            ],
        ])));
        self::assertNotFalse(file_put_contents($this->home . '/vendor/composer/installed.json', (string) json_encode([
            'packages' => [
                [
                    'name'    => 'acme/process',
                    'require' => [
                        'ext-PCNTL'      => '*',
                        'ext-reflection' => '*',
                        'ext-tokenizer'  => '*',
                    ],
                ],
            ],
            'dev' => $dev,
        ])));

        return new Project($this->home, $this->home . '/vendor', 'acme/app', '>=8.4');
    }
}
