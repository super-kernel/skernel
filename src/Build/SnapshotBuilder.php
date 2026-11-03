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
 * Writes a project's scan snapshots by bootstrapping its container, without running the application, in a separate
 * PHP process.
 */
final readonly class SnapshotBuilder
{
    private const string ENTRY = <<<'PHP'
        <?php

        declare(strict_types=1);

        require %s;

        SuperKernel\Context\ApplicationContext::getContainer();

        PHP;

    /**
     * Runs the scan in the project directory with the given PHP binary, a path or a name looked up in PATH, and
     * forwards everything the process writes.
     *
     * @return int The exit status of the scan process
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
            throw new RuntimeException('Unable to create the temporary scan entry script.');
        }

        try {
            $script = sprintf(self::ENTRY, var_export($project->vendorDirectory . '/autoload.php', true));

            if (false === file_put_contents($entry, $script)) {
                throw new RuntimeException(sprintf('Unable to write the temporary scan entry script %s.', $entry));
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
