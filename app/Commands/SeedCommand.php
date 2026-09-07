<?php

namespace App\Commands;

use App\Commands\Concerns\ConsoleStyle;
use App\Database\Seeds\BantayGamitSeeder;
use CodeIgniter\CLI\BaseCommand;
use Config\Database;
use Throwable;

class SeedCommand extends BaseCommand
{
    use ConsoleStyle;

    protected $group = 'Database';
    protected $name = 'db:seed';
    protected $description = 'Runs BantayGamit seeders with per-seeder change statistics.';
    protected $usage = 'db:seed [seeder_name] [--no-ansi]';
    protected $arguments = [
        'seeder_name' => 'Optional seeder name. Defaults to BantayGamitSeeder.',
    ];
    protected $options = [
        '--no-ansi' => 'Disable colour, emoji, and box-drawing output',
    ];

    public function run(array $params)
    {
        $this->initializeConsoleStyle();
        $this->banner('BantayGamit · Database Seeding');

        $seedName = array_shift($params) ?: 'BantayGamitSeeder';
        $targetClass = $this->resolveSeederClass((string) $seedName);

        if (! class_exists($targetClass)) {
            $this->errorLine('Seeder not found: ' . $seedName);

            return EXIT_ERROR;
        }

        $sequence = $targetClass === BantayGamitSeeder::class
            ? BantayGamitSeeder::SEQUENCE
            : [$targetClass];

        $this->sectionRow(
            '🎯',
            'Target',
            $this->shortClass($targetClass) . '  ·  ' . $this->pluralize(count($sequence), 'seeder'),
            'light_cyan',
        );
        $this->sectionRow('🕒', 'Started', date('Y-m-d H:i:s') . '  (' . date_default_timezone_get() . ')');
        $this->blankLine();

        $config = new Database();
        $totals = [
            'inserted' => 0,
            'updated' => 0,
            'unchanged' => 0,
        ];
        $unchangedSeeders = 0;
        $overallStart = microtime(true);

        foreach ($sequence as $seederClass) {
            $start = microtime(true);

            try {
                $seeder = new $seederClass($config);
                $seeder->setSilent(true);
                $seeder->run();
                $hasStats = method_exists($seeder, 'seedStats');
                $stats = $hasStats
                    ? $seeder->seedStats()
                    : ['inserted' => 0, 'updated' => 0, 'unchanged' => 0];
                $elapsed = microtime(true) - $start;

                foreach ($totals as $key => $value) {
                    $totals[$key] += (int) ($stats[$key] ?? 0);
                }

                $hasChanges = (int) ($stats['inserted'] ?? 0) > 0
                    || (int) ($stats['updated'] ?? 0) > 0;

                if ($hasChanges || ! $hasStats) {
                    $this->seederRow($seederClass, $stats, $elapsed);
                } else {
                    $unchangedSeeders++;
                }
            } catch (Throwable $e) {
                $elapsed = microtime(true) - $start;
                $this->failedSeederRow($seederClass, $elapsed, $e->getMessage());
                $this->blankLine();
                $this->divider();
                $this->summaryLine('❌', 'Seeding aborted after a failure.', 'light_red');

                return EXIT_ERROR;
            }
        }

        $elapsed = microtime(true) - $overallStart;
        $this->blankLine();
        $this->divider();

        if ($totals['inserted'] === 0 && $totals['updated'] === 0) {
            $this->summaryLine('✨', 'No pending seed data — database is already up to date.', 'light_green');
            $this->sectionRow(
                '📚',
                'Checked',
                $this->pluralize(count($sequence), 'seeder') . '  ·  '
                . $this->pluralize($totals['unchanged'], 'existing row') . '  ·  '
                . $this->formatDuration($elapsed),
                'light_gray',
            );
        } else {
            if ($unchangedSeeders > 0) {
                $this->sectionRow(
                    '⏭️',
                    'Existing',
                    $this->pluralize($unchangedSeeders, 'seeder') . ' already up to date',
                    'light_gray',
                );
            }

            $this->summaryLine(
                '🌱',
                'Seeded  ·  ' . $totals['inserted'] . ' new  ·  ' . $totals['updated'] . ' updated  ·  '
                . $totals['unchanged'] . ' unchanged  ·  ' . $this->formatDuration($elapsed),
            );
        }

        if ($targetClass === BantayGamitSeeder::class) {
            $this->sectionRow('🔑', 'Sign in as', 'admin / Admin@12345      (development only)', 'light_yellow');
        }

        return EXIT_SUCCESS;
    }

    private function resolveSeederClass(string $seedName): string
    {
        $seedName = trim(str_replace('.php', '', $seedName));

        if (str_contains($seedName, '\\')) {
            return $seedName;
        }

        return APP_NAMESPACE . '\\Database\\Seeds\\' . $seedName;
    }
}
