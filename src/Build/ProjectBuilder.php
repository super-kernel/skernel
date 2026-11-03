<?php

declare(strict_types=1);

namespace Src\Build;

use Src\Project\Project;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessStartFailedException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

use function file_put_contents;
use function is_executable;
use function is_file;
use function sprintf;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;
use function var_export;

/**
 * Writes a project's build artifacts in a separate PHP process without creating or running the application.
 *
 * The process bootstraps the project's container, which writes its scan snapshots, then publishes
 * `SuperKernel\Event\ApplicationBuild` so that components write the artifacts restore-only hosts load. Projects
 * without that event or a PSR-14 dispatcher have no listener for it, so the event is skipped.
 */
final readonly class ProjectBuilder
{
    private const string ENTRY = <<<'PHP'
        <?php

        declare(strict_types=1);

        require %s;

        $container = SuperKernel\Context\ApplicationContext::getContainer();

        if (
            class_exists(SuperKernel\Event\ApplicationBuild::class)
            && $container->has(Psr\EventDispatcher\EventDispatcherInterface::class)
        ) {
            $container->get(Psr\EventDispatcher\EventDispatcherInterface::class)
                ->dispatch(new SuperKernel\Event\ApplicationBuild());
        }

        PHP;

    /**
     * Runs the build in the project directory with the given PHP binary, a path or a name looked up in PATH, and
     * forwards everything the process writes.
     *
     * @return int The exit status of the build process
     *
     * @throws RuntimeException When the PHP binary is not found or cannot be started, or the temporary entry script
     *                          cannot be written
     */
    public function build(Project $project, string $phpBinary, OutputInterface $output): int
    {
        $executable = is_file($phpBinary) && is_executable($phpBinary)
            ? $phpBinary
            : new ExecutableFinder()->find($phpBinary);

        if (null === $executable) {
            throw new RuntimeException(sprintf('The PHP binary %s was not found.', $phpBinary));
        }

        $entry = tempnam(sys_get_temp_dir(), 'skernel-build-');

        if (false === $entry) {
            throw new RuntimeException('Unable to create the temporary build entry script.');
        }

        try {
            $script = sprintf(self::ENTRY, var_export($project->vendorDirectory . '/autoload.php', true));

            if (false === file_put_contents($entry, $script)) {
                throw new RuntimeException(sprintf('Unable to write the temporary build entry script %s.', $entry));
            }

            $process = new Process([
                $executable,
                $entry,
            ], $project->directory, null, null, null);

            return $process->run(static function (string $type, string $buffer) use ($output): void {
                $output->write($buffer, false, OutputInterface::OUTPUT_RAW);
            });
        } catch (ProcessStartFailedException $exception) {
            throw new RuntimeException(sprintf('Unable to start the PHP binary %s.', $phpBinary), 0, $exception);
        } finally {
            unlink($entry);
        }
    }
}
