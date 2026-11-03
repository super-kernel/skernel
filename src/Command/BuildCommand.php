<?php

declare(strict_types=1);

namespace Src\Command;

use Src\Build\ProjectBuilder;
use Src\Project\ProjectLocator;
use SuperKernel\Command\Attribute\Command as AttributeCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function sprintf;

use const PHP_BINARY;

/**
 * Scans the project and writes the build artifacts that restore-only and Phar execution load.
 */
#[AttributeCommand(name: 'build', description: 'Scan the project and write its build artifacts')]
final class BuildCommand extends Command
{
    public function __construct(
        private readonly ProjectLocator $projectLocator,
        private readonly ProjectBuilder $projectBuilder,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('php', null, InputOption::VALUE_REQUIRED, 'The PHP binary that runs the project', PHP_BINARY);
    }

    /**
     * @throws RuntimeException When the project cannot be located, or the build cannot start or fails
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $project = $this->projectLocator->locate($input->getOption('working-dir'));
        $errorOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
        $status = $this->projectBuilder->build($project, $input->getOption('php'), $errorOutput);

        if (0 !== $status) {
            throw new RuntimeException(sprintf('The project build failed with exit status %d.', $status));
        }

        $output->writeln(
            sprintf('Build artifacts written to %s/.cache.', $project->vendorDirectory),
            OutputInterface::OUTPUT_RAW,
        );

        return self::SUCCESS;
    }
}
