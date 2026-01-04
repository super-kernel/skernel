<?php
declare(strict_types=1);

namespace SuperKernel\Skernel;

use SuperKernel\Contract\ApplicationInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class Application extends \Composer\Console\Application implements ApplicationInterface
{
	public function __construct(string $name = 'UNKNOWN', string $version = 'UNKNOWN')
	{
		parent::__construct($name, $version);
	}

	public function run(InputInterface|null $input = null, OutputInterface|null $output = null): void
	{
	}
}