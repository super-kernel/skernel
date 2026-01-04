<?php
declare(strict_types=1);

namespace SuperKernel\Skernel\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

#[
	AsCommand(
		name: 'dev',
	),
]
final class DevCommand extends Command {}