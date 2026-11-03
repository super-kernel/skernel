<?php

declare(strict_types=1);

namespace Src\Command;

use Src\Build\ProjectBuilder;
use Src\Configuration\ConfigurationLoader;
use Src\Project\ProjectLocator;
use Src\Toolchain\ManagedPhp;
use Src\Toolchain\PhpVersion;
use SuperKernel\Command\Attribute\Command as AttributeCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function sprintf;

/**
 * Scans the project with its managed PHP and writes the build artifacts that restore-only and Phar execution load.
 */
#[AttributeCommand(name: 'build', description: 'Scan the project and write its build artifacts')]
final class BuildCommand extends Command
{
    public function __construct(
        private readonly ProjectLocator      $projectLocator,
        private readonly ConfigurationLoader $configurationLoader,
        private readonly ManagedPhp          $managedPhp,
        private readonly ProjectBuilder      $projectBuilder,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'php',
            null,
            InputOption::VALUE_REQUIRED,
            'The PHP version that runs the project, such as 8.4 or 8.4.27',
        );
    }

    /**
     * @throws RuntimeException When the project or its configuration is invalid, its managed PHP cannot be provided,
     *                          or the build cannot start or fails
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $project = $this->projectLocator->locate($input->getOption('working-dir'));
        $php = $input->getOption('php');

        if (null !== $php && !PhpVersion::isValid($php)) {
            throw new RuntimeException('The --php option must be a PHP version such as 8.4 or 8.4.27.');
        }

        $errorOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
        $runtime = $this->managedPhp->runtime(
            $project,
            $php ?? $this->configurationLoader->load($project->directory)->php,
            $errorOutput,
        );
        $status = $this->projectBuilder->build($project, $runtime->php, $errorOutput);

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
