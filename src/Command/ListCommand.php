<?php

declare(strict_types=1);

namespace Src\Command;

use Symfony\Component\Console\Command\ListCommand as ConsoleListCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Lists SKernel commands and opens the plain-text application overview with the SKernel logo.
 */
final class ListCommand extends ConsoleListCommand
{
    private const string LOGO = <<<'LOGO'
           _____ __ __                     __
          / ___// //_/__  _________  ___  / /
          \__ \/ ,< / _ \/ ___/ __ \/ _ \/ /
         ___/ / /| /  __/ /  / / / /  __/ /
        /____/_/ |_\___/_/  /_/ /_/\___/_/
        LOGO;

    protected function configure(): void
    {
        parent::configure();

        $this->setDescription('List SKernel commands');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ('txt' === $input->getOption('format')
            && !$input->getOption('raw')
            && null === $input->getArgument('namespace')) {
            $output->writeln(self::LOGO, OutputInterface::OUTPUT_RAW);
        }

        return parent::execute($input, $output);
    }
}
