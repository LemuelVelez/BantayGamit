<?php

namespace App\Database\Seeds;

trait SeedSupport
{
    protected function upsertSeedRow(string $table, array $where, array $data): int
    {
        $builder = $this->db->table($table);
        $existing = $builder->where($where)->get()->getRowArray();

        if ($existing) {
            $this->db->table($table)->where('id', (int) $existing['id'])->update($data);
            return (int) $existing['id'];
        }

        $this->db->table($table)->insert(array_merge($where, $data));
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
}
