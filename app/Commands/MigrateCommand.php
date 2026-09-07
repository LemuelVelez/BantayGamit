<?php

namespace App\Commands;

use App\Commands\Concerns\ConsoleStyle;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

class MigrateCommand extends BaseCommand
{
    use ConsoleStyle;

    protected $group = 'Database';
    protected $name = 'migrate';
    protected $description = 'Runs pending BantayGamit migrations with styled progress output.';
    protected $usage = 'migrate [options]';
    protected $options = [
        '-n' => 'Set migration namespace',
        '-g' => 'Set database group',
        '--all' => 'Run migrations for all namespaces; ignores -n',
        '--no-ansi' => 'Disable colour, emoji, and box-drawing output',
    ];

    public function run(array $params)
    {
        $this->initializeConsoleStyle();
        $this->banner('BantayGamit · Database Migrations');

        $runner = service('migrations');
        $runner->clearCliMessages();
        $runner->setSilent(true);
        $namespace = $params['n'] ?? CLI::getOption('n');
        $group = $params['g'] ?? CLI::getOption('g');

        try {
            if (array_key_exists('all', $params) || CLI::getOption('all')) {
                $runner->setNamespace(null);
            } elseif ($namespace) {
                $runner->setNamespace((string) $namespace);
            }

            $db = db_connect($group ?: null);
            $migrations = $runner->findMigrations();
            $history = $runner->getHistory((string) $group);
            $pending = $migrations;

            foreach ($history as $row) {
                unset($pending[$runner->getObjectUid($row)]);
            }

            $this->sectionRow('📦', 'Database', $db->database . ' @ ' . $db->hostname . '  ·  ' . $db->DBDriver, 'light_cyan');

            if ($pending === []) {
                $this->summaryLine('✨', 'Up to date — no pending migrations.');
                $this->blankLine();

                $latestBatch = 0;
                $latestTime = 0;
                foreach ($history as $row) {
                    $latestBatch = max($latestBatch, (int) $row->batch);
                    $latestTime = max($latestTime, (int) $row->time);
                }

                $latestText = $latestTime > 0 ? date('Y-m-d H:i:s', $latestTime) : 'never';
                $this->sectionRow('📚', 'Applied', count($history) . ' applied  ·  latest batch #' . $latestBatch . '  ·  ' . $latestText);
                $this->hintLine('Reset with  php spark migrate:refresh   (development only)');

                return EXIT_SUCCESS;
            }

            $startedAt = date('Y-m-d H:i:s');
            $this->sectionRow('🕒', 'Started', $startedAt . '  (' . date_default_timezone_get() . ')');
            $this->sectionRow('⏳', 'Pending', $this->pluralize(count($pending), 'migration'));
            $this->blankLine();

            $batch = $runner->getLastBatch() + 1;
            $start = microtime(true);
            $success = $runner->latest($group ? (string) $group : null);
            $elapsed = microtime(true) - $start;

            if (! $success) {
                $this->errorLine('Migration runner reported a failure.');

                return EXIT_ERROR;
            }

            $perMigration = count($pending) === 1 ? $elapsed : $elapsed / count($pending);
            foreach ($pending as $migration) {
                $this->migrationRow($migration, $perMigration);
            }

            $this->blankLine();
            $this->divider();
            $this->summaryLine(
                '🎉',
                'Done  ·  ' . count($pending) . ' applied  ·  batch #' . $batch . '  ·  ' . $this->formatDuration($elapsed),
            );

            return EXIT_SUCCESS;
        } catch (Throwable $e) {
            $this->errorLine($e->getMessage());

            return EXIT_ERROR;
        }
    }
}
