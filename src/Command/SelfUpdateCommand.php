<?php

declare(strict_types=1);

namespace Src\Command;

use Phar;
use Src\Update\SelfUpdater;
use SuperKernel\Command\Attribute\Command as AttributeCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function sprintf;

/**
 * Replaces a standalone SKERNEL with the binary of the latest GitHub Release.
 */
#[AttributeCommand(name: 'self-update', description: 'Replace SKERNEL with the binary of the latest GitHub Release')]
final class SelfUpdateCommand extends Command
{
    public function __construct(private readonly SelfUpdater $selfUpdater)
    {
        parent::__construct();
    }

    /**
     * @throws RuntimeException When SKERNEL runs from source, the latest release has no verified binary for this
     *                          platform, or the binary cannot be replaced
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $binary = Phar::running(false);

        if ('' === $binary) {
            throw new RuntimeException('SKERNEL is running from source; update it with Git instead.');
        }

        $tag = $this->selfUpdater->update($binary);

        $output->writeln(
            null === $tag ? 'SKERNEL is up to date.' : sprintf('SKERNEL updated to %s.', $tag),
            OutputInterface::OUTPUT_RAW,
        );

        return self::SUCCESS;
    }
}
