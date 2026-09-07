<?php

namespace App\Database\Seeds;

trait SeedSupport
{
    private array $seedStats = [
        'inserted' => 0,
        'updated' => 0,
        'unchanged' => 0,
    ];

    public function seedStats(): array
    {
        return $this->seedStats;
    }

    public function resetSeedStats(): void
    {
        $this->seedStats = [
            'inserted' => 0,
            'updated' => 0,
            'unchanged' => 0,
        ];
    }

    protected function upsertSeedRow(string $table, array $where, array $data): int
    {
        $builder = $this->db->table($table);
        $existing = $builder->where($where)->get()->getRowArray();

        if ($existing) {
            if ($this->seedRowsMatch($existing, $data)) {
                $this->seedStats['unchanged']++;

                return (int) $existing['id'];
            }

            $this->db->table($table)->where('id', (int) $existing['id'])->update($data);
            $this->seedStats['updated']++;

            return (int) $existing['id'];
        }

        $this->db->table($table)->insert(array_merge($where, $data));
        $this->seedStats['inserted']++;

        return (int) $this->db->insertID();
    }

    protected function idBy(string $table, string $column, string $value): int
    {
        $row = $this->db->table($table)->select('id')->where($column, $value)->get()->getRowArray();
        if (! $row) {
            throw new \RuntimeException("Required seed record not found: {$table}.{$column}={$value}");
        }

        return (int) $row['id'];
    }

    protected function dateOffset(string $modifier, string $format = 'Y-m-d'): string
    {
        return date($format, strtotime($modifier));
    }

    private function seedRowsMatch(array $existing, array $data): bool
    {
        $meaningfulDifference = false;

        foreach ($data as $column => $value) {
            if (! array_key_exists($column, $existing)) {
                return false;
            }

            if ($this->seedValuesMatch($existing[$column], $value)) {
                continue;
            }

            if (str_ends_with($column, '_at')) {
                continue;
            }

            $meaningfulDifference = true;
            break;
        }

        return ! $meaningfulDifference;
    }

    private function seedValuesMatch(mixed $existing, mixed $value): bool
    {
        if ($existing === null || $value === null) {
            return $existing === $value;
        }

        if (is_bool($existing) || is_bool($value)) {
            return (bool) $existing === (bool) $value;
        }

        if (is_numeric($existing) && is_numeric($value)) {
            return abs((float) $existing - (float) $value) < 0.000001;
        }

        return (string) $existing === (string) $value;
    }
}
