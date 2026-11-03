<?php

declare(strict_types=1);

namespace Src\Tests\Unit\Command;

use Exception;
use PHPUnit\Framework\TestCase;
use Src\Command\HelpCommand;
use Src\Command\ListCommand;
use SuperKernel\Contract\ApplicationInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\ApplicationTester;

final class HelpCommandTest extends TestCase
{
    public function testDescribesItselfAsSKernelHelp(): void
    {
        $command = new HelpCommand();

        self::assertSame('help', $command->getName());
        self::assertSame('Display help for an SKernel command', $command->getDescription());
    }

    /** @throws Exception If Console command initialization or execution fails */
    public function testDisplaysHelpForTheGivenCommand(): void
    {
        $application = new class() extends Application implements ApplicationInterface {};
        $application->setAutoExit(false);
        $application->addCommand(new HelpCommand());
        $application->addCommand(new ListCommand());
        $tester = new ApplicationTester($application);

        self::assertSame(Command::SUCCESS, $tester->run([
            'command'      => 'help',
            'command_name' => 'list',
        ]));
        self::assertStringContainsString("Description:\n  List SKernel commands", $tester->getDisplay(true));
    }
}
