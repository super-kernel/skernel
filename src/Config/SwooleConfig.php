<?php

declare(strict_types=1);

namespace Src\Config;

use SuperKernel\Attribute\Configuration;
use SuperKernel\Contract\VendorDirInterface;
use SuperKernel\Swoole\Config\SwooleConfigInterface;

use const DIRECTORY_SEPARATOR;

#[Configuration(SwooleConfigInterface::class)]
final readonly class SwooleConfig implements SwooleConfigInterface
{
    public function __construct(private VendorDirInterface $vendorDir)
    {
    }

    public function daemon(): bool
    {
        return false;
    }

    public function pidFile(): ?string
    {
        return $this->vendorDir . DIRECTORY_SEPARATOR . '.cache' . DIRECTORY_SEPARATOR . 'super-kernel.pid';
    }

    public function displayErrors(): bool
    {
        return true;
    }

    public function socketBufferSize(): int
    {
        return 8 * 1024 * 1024;
    }

    public function enablePreemptiveScheduler(): bool
    {
        return false;
    }
}
