<?php

declare(strict_types=1);

namespace Src\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Src\Service\TestService;

final class TestServiceTest extends TestCase
{
    public function testGetAIncrementsTheCounterOnEachCall(): void
    {
        $service = new TestService();

        self::assertSame(1, $service->getA());
        self::assertSame(2, $service->getA());
    }

    public function testGetAStartsFromTheSuppliedValue(): void
    {
        self::assertSame(11, new TestService(10)->getA());
    }
}
