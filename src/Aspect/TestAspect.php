<?php

declare(strict_types=1);

namespace Src\Aspect;

use Src;
use SuperKernel\Aop\Attribute\Aspect;
use SuperKernel\Aop\Contract\AspectInterface;
use SuperKernel\Aop\Contract\ProceedingJoinPointInterface;
use SuperKernel\Contract\StdoutLoggerInterface;

#[Aspect(methods: [
    Src\Service\TestService::class => [
        'getA',
    ],
])]
final readonly class TestAspect implements AspectInterface
{
    public function __construct(private StdoutLoggerInterface $logger)
    {
    }

    public function process(ProceedingJoinPointInterface $point): mixed
    {
        $this->logger->info('Before Src\Service\TestService::getA().');

        try {
            return $point->proceed();
        } finally {
            $this->logger->info('After Src\Service\TestService::getA().');
        }
    }
}
