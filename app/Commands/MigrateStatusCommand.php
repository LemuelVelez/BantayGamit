<?php

namespace App\Commands;

use App\Commands\Concerns\ConsoleStyle;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

class MigrateStatusCommand extends BaseCommand
{
    use ConsoleStyle;

    protected $group = 'Database';
    protected $name = 'migrate:status';
    protected $description = 'Shows BantayGamit migration status with styled read-only output.';
    protected $usage = 'migrate:status [options]';
    protected $options = [
        '-g' => 'Set database group',
        '--no-ansi' => 'Disable colour, emoji, and box-drawing output',
    ];

    public function run(array $params)
    {
        $this->initializeConsoleStyle();
        $this->banner('BantayGamit · Migration Status');

        $runner = service('migrations');
        $runner->clearCliMessages();
        $runner->setSilent(true);
        $runner->setNamespace(null);
        $group = $params['g'] ?? CLI::getOption('g');

        try {
            $db = db_connect($group ?: null);
            $migrations = $runner->findMigrations();
            $history = $runner->getHistory((string) $group);
            $ignoredNamespaces = [
                'CodeIgniter',
                'Config',
                'Kint',
                'Laminas\\ZendFrameworkBridge',
                'Laminas\\Escaper',
                'Psr\\Log',
                'Tests\\Support',
            ];
            $migrations = array_filter(
                $migrations,
                static fn ($migration): bool => ! in_array($migration->namespace, $ignoredNamespaces, true),
            );
            $historyByUid = [];

            foreach ($history as $row) {
                $historyByUid[$runner->getObjectUid($row)] = $row;
            }

            $appliedCount = 0;
            foreach ($migrations as $uid => $migration) {
                if (isset($historyByUid[$uid])) {
                    $appliedCount++;
                }
            }
            $pendingCount = count($migrations) - $appliedCount;

            $this->sectionRow('📦', 'Database', $db->database . ' @ ' . $db->hostname . '  ·  ' . $db->DBDriver, 'light_cyan');
            $this->sectionRow('📚', 'Applied', $this->pluralize($appliedCount, 'migration'));
            $this->sectionRow('⏳', 'Pending', $this->pluralize($pendingCount, 'migration'), $pendingCount > 0 ? 'light_yellow' : 'light_green');
            $this->blankLine();

            if ($migrations === []) {
                $this->warningLine('No migrations were found.');

                return EXIT_SUCCESS;
            }

            foreach ($migrations as $uid => $migration) {
                $this->migrationStatusRow($migration, $historyByUid[$uid] ?? null);
            }

            $this->blankLine();
            $this->divider();
            $this->summaryLine('✨', 'Status complete  ·  read-only');

            return EXIT_SUCCESS;
        } catch (Throwable $e) {
            $this->errorLine($e->getMessage());

            return EXIT_ERROR;
        }
    }
}
