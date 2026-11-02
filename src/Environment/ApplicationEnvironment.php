<?php

declare(strict_types=1);

namespace Src\Environment;

use SuperKernel\Attribute\Provider;
use SuperKernel\Environment\Contract\EnvironmentInterface;
use SuperKernel\Environment\Environment;
use SuperKernel\Environment\PropertySource\DotenvPropertySource;
use SuperKernel\Environment\PropertySource\ProcessEnvironmentPropertySource;

use function dirname;

#[Provider(EnvironmentInterface::class)]
final readonly class ApplicationEnvironment implements EnvironmentInterface
{
    private Environment $environment;

    public function __construct()
    {
        $this->environment = new Environment([
            new DotenvPropertySource(dirname(__DIR__, 2) . '/.env'),
            new ProcessEnvironmentPropertySource(),
        ]);
    }

    public function has(string $name): bool
    {
        return $this->environment->has($name);
    }

    public function get(string $name): string|int|float|bool|null
    {
        return $this->environment->get($name);
    }

    public function getString(string $name): ?string
    {
        return $this->environment->getString($name);
    }

    public function getInt(string $name): ?int
    {
        return $this->environment->getInt($name);
    }

    public function getFloat(string $name): ?float
    {
        return $this->environment->getFloat($name);
    }

    public function getBool(string $name): ?bool
    {
        return $this->environment->getBool($name);
    }
}
