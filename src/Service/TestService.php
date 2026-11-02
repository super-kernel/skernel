<?php

declare(strict_types=1);

namespace Src\Service;

final class TestService
{
    public function __construct(private int $a = 0)
    {
    }

    public function getA(): int
    {
        return ++$this->a;
    }
}
