<?php

declare(strict_types=1);

namespace Src\Controller;

use Src\Service\TestService;
use SuperKernel\HttpServer\Attribute\Controller;
use SuperKernel\HttpServer\Attribute\RequestMapping;

#[
    Controller('/'),
]
final readonly class TestController
{
    public function __construct(private TestService $testService)
    {
    }

    #[
        RequestMapping('/index')
    ]
    public function test(): int
    {
        return $this->testService->getA();
    }

    /** @return array{id: int, name: string} */
    #[RequestMapping('/user')]
    public function index(): array
    {
        return [
            'id'   => 1,
            'name' => 'Example',
        ];
    }
}
