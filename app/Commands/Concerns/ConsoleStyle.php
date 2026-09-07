<?php

namespace App\Commands\Concerns;

use CodeIgniter\CLI\CLI;

trait ConsoleStyle
{
    private bool $richConsoleOutput = false;
    private int $consoleWidth = 58;

    protected function initializeConsoleStyle(): void
    {
        $isTty = function_exists('stream_isatty')
            ? @stream_isatty(STDOUT)
            : CLI::hasColorSupport(STDOUT);
        $noColor = isset($_SERVER['NO_COLOR']) || getenv('NO_COLOR') !== false;
        $noAnsi = CLI::getOption('no-ansi') !== null;

        $this->richConsoleOutput = $isTty && ! $noColor && ! $noAnsi && CLI::hasColorSupport(STDOUT);
    }

    protected function banner(string $title): void
    {
        $topLeft = $this->richConsoleOutput ? '╭' : '+';
        $topRight = $this->richConsoleOutput ? '╮' : '+';
        $bottomLeft = $this->richConsoleOutput ? '╰' : '+';
        $bottomRight = $this->richConsoleOutput ? '╯' : '+';
        $horizontal = $this->richConsoleOutput ? '─' : '-';
        $vertical = $this->richConsoleOutput ? '│' : '|';
        $innerWidth = $this->consoleWidth;
        $content = '  ' . $title;

        $this->styleLine($topLeft . str_repeat($horizontal, $innerWidth) . $topRight, 'light_cyan');
        $this->styleLine($vertical . $this->padRight($content, $innerWidth) . $vertical, 'light_cyan');
        $this->styleLine($bottomLeft . str_repeat($horizontal, $innerWidth) . $bottomRight, 'light_cyan');
        $this->blankLine();
    }

    protected function sectionRow(string $emoji, string $label, string $value, string $color = 'white'): void
    {
        $glyph = $this->glyph($emoji, '[--]');
        $labelText = $this->paint($this->padRight($label, 10), 'light_gray');
        $valueText = $this->paint($value, $color);

        $this->styleLine('  ' . $glyph . '  ' . $labelText . $valueText);
    }

    protected function migrationRow(object $migration, float $seconds): void
    {
        $version = $this->padRight((string) $migration->version, 22);
        $name = $this->padRight((string) $migration->name, 31);
        $namespace = $this->padRight((string) $migration->namespace, 8);
        $line = sprintf(
            '  %s  %s%s%s%s',
            $this->glyph('✅', '[ok]'),
            $this->paint($version, 'light_green'),
            $name,
            $namespace,
            $this->formatDuration($seconds),
        );

        $this->styleLine($line);

        if ($migration->name === 'CreateBantayGamitSchema') {
            $branch = $this->richConsoleOutput ? '└─' : '+-';
            $this->styleLine('      ' . $branch . ' 10 tables · 15 indexes · 13 foreign keys', 'dark_gray');
        }
    }

    protected function seederRow(string $class, array $stats, float $seconds): void
    {
        $changes = (int) ($stats['inserted'] ?? 0) + (int) ($stats['updated'] ?? 0);
        $statusGlyph = $changes > 0 ? $this->glyph('✅', '[ok]') : $this->glyph('⏭️', '[--]');
        $icon = $this->seederIcon($class);
        $name = $this->padRight($this->shortClass($class), 31);
        $summary = $this->padRight($this->formatSeedStats($stats), 29);

        $this->styleLine(sprintf(
            '  %s  %s  %s%s%s',
            $statusGlyph,
            $icon,
            $name,
            $summary,
            $this->formatDuration($seconds),
        ));
    }

    protected function failedSeederRow(string $class, float $seconds, string $message): void
    {
        $this->styleLine(sprintf(
            '  %s  %s  %s%s',
            $this->glyph('❌', '[xx]'),
            $this->seederIcon($class),
            $this->padRight($this->shortClass($class), 60),
            $this->formatDuration($seconds),
        ), 'light_red');
        $this->styleLine('      ' . $message, 'light_red');
    }

    protected function migrationStatusRow(object $migration, ?object $history): void
    {
        $applied = $history !== null;
        $glyph = $applied ? $this->glyph('✅', '[ok]') : $this->glyph('⏭️', '[--]');
        $status = $applied
            ? 'batch #' . $history->batch . ' · ' . date('Y-m-d H:i:s', (int) $history->time)
            : 'pending';

        $this->styleLine(sprintf(
            '  %s  %s%s%s',
            $glyph,
            $this->padRight((string) $migration->version, 22),
            $this->padRight((string) $migration->name, 31),
            $status,
        ), $applied ? 'light_green' : 'light_yellow');
    }

    protected function divider(): void
    {
        $character = $this->richConsoleOutput ? '─' : '-';
        $this->styleLine('  ' . str_repeat($character, $this->consoleWidth), 'dark_gray');
    }

    protected function summaryLine(string $emoji, string $text, string $color = 'light_green'): void
    {
        $fallbacks = ['❌' => '[xx]', '⚠️' => '[!!]', '⏭️' => '[--]'];
        $this->styleLine('  ' . $this->glyph($emoji, $fallbacks[$emoji] ?? '[ok]') . '  ' . $text, $color);
    }

    protected function hintLine(string $text): void
    {
        $this->styleLine('  ' . $this->glyph('💡', '[--]') . '  ' . $text, 'light_cyan');
    }

    protected function errorLine(string $message): void
    {
        $this->styleLine('  ' . $this->glyph('❌', '[xx]') . '  ' . $message, 'light_red');
    }

    protected function warningLine(string $message): void
    {
        $this->styleLine('  ' . $this->glyph('⚠️', '[!!]') . '  ' . $message, 'light_yellow');
    }

    protected function blankLine(): void
    {
        $this->styleLine('');
    }

    protected function formatDuration(float $seconds): string
    {
        if ($seconds < 1) {
            return (string) max(1, (int) round($seconds * 1000)) . ' ms';
        }

        return number_format($seconds, 2) . 's';
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        $units = ['KB', 'MB', 'GB', 'TB'];
        $value = $bytes / 1024;

        foreach ($units as $unit) {
            if ($value < 1024 || $unit === 'TB') {
                return number_format($value, 1) . ' ' . $unit;
            }

            $value /= 1024;
        }

        return $bytes . ' B';
    }

    protected function pluralize(int $count, string $singular, ?string $plural = null): string
    {
        return $count . ' ' . ($count === 1 ? $singular : ($plural ?? $singular . 's'));
    }

    protected function shortClass(string $class): string
    {
        $position = strrpos($class, '\\');

        return $position === false ? $class : substr($class, $position + 1);
    }

    protected function styleLine(string $text, ?string $color = null): void
    {
        if ($color !== null && $this->richConsoleOutput) {
            $text = CLI::color($text, $color);
        }

        CLI::write($text);
    }

    private function paint(string $text, string $color): string
    {
        return $this->richConsoleOutput ? CLI::color($text, $color) : $text;
    }

    private function glyph(string $emoji, string $fallback): string
    {
        return $this->richConsoleOutput ? $emoji : $fallback;
    }

    private function seederIcon(string $class): string
    {
        if (! $this->richConsoleOutput) {
            return '[--]';
        }

        return match ($this->shortClass($class)) {
            'UserSeeder' => '👤',
            'EquipmentCategorySeeder' => '🏷️',
            'EquipmentLocationSeeder' => '📍',
            'EquipmentSeeder' => '🧰',
            'BorrowingSeeder' => '📋',
            'MaintenanceSeeder' => '🔧',
            'ReportDataSeeder' => '📊',
            'NotificationSeeder' => '🔔',
            'SettingsSeeder' => '⚙️',
            'AuditLogSeeder' => '🧾',
            default => '🌱',
        };
    }

    private function formatSeedStats(array $stats): string
    {
        $inserted = (int) ($stats['inserted'] ?? 0);
        $updated = (int) ($stats['updated'] ?? 0);
        $unchanged = (int) ($stats['unchanged'] ?? 0);
        $parts = [];

        if ($inserted > 0) {
            $parts[] = $inserted . ' new';
        }
        if ($updated > 0) {
            $parts[] = $updated . ' updated';
        }
        if ($unchanged > 0) {
            $parts[] = $unchanged . ' unchanged';
        }

        return $parts === [] ? 'completed' : implode(' · ', $parts);
    }

    private function padRight(string $text, int $width): string
    {
        $padding = max(0, $width - mb_strwidth($text));

        return $text . str_repeat(' ', $padding);
    }
}
