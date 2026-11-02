<?php

declare (strict_types=1);

namespace Src\Process;

use SuperKernel\Attribute\Autowired;
use SuperKernel\Contract\StdoutLoggerInterface;
use SuperKernel\Process\Attribute\Process;
use SuperKernel\Process\Contract\ProcessInterface;
use Swoole\Coroutine;
use Swoole\Process\Pool;

/**
 * A long-running supervised example, disabled by default. Replace the heartbeat with application work before enabling.
 * Returning from execute() makes the Pool replace this worker; it is intentionally kept alive.
 */
#[Process]
final class TestProcess implements ProcessInterface
{
    #[Autowired]
    protected StdoutLoggerInterface $logger;

    public bool $enabled {
        get => false;
    }
    public bool $enableCoroutine {
        get => true;
    }

    public function execute(Pool $pool, int $workerId): void
    {
        while (true) {
            $this->logger->info('Example worker heartbeat.', [
                'workerId' => $workerId,
            ]);
            Coroutine::sleep(30);
        }

    }
}
