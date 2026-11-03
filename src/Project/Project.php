<?php

declare(strict_types=1);

namespace Src\Project;

/**
 * A SuperKernel application directory whose Composer dependencies are installed.
 */
final readonly class Project
{
    public function __construct(public string $directory, public string $vendorDirectory)
    {
    }
}
