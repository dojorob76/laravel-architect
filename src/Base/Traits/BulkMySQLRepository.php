<?php

declare(strict_types=1);

namespace LaravelArchitect\Base\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Trait providing bulk/batch repository methods for raw MySQL queries.
 *
 * Use this trait in repositories that extend BaseMySQLRepository.
 * Requires $this->table to be set.
 * Uses ? placeholder parameters for MySQL prepared statements.
 */
trait BulkMySQLRepository
{
    /**
     * Find multiple records by IDs
     *
     * @param array<int> $ids
     */
    public function findMany(array $ids): Collection
    {
        if (empty($ids)) {
            return collect();
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT * FROM {$this->table} WHERE id IN ({$placeholders})";

        return collect(DB::select($sql, $ids));
    }

    /**
     * Find multiple records by column criteria
     *
     * @param array<string, mixed> $criteria Column-value pairs (e.g., ['status' => 'active'] or ['name' => 'Claude', 'model' => 'Opus'])
     */
    public function findManyByColumns(array $criteria): Collection
    {
        if (empty($criteria)) {
            return collect();
        }

        $whereClauses = [];
        $values = [];

        foreach ($criteria as $column => $value) {
            $whereClauses[] = "{$column} = ?";
            $values[] = $value;
        }

        $sql = "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $whereClauses);

        return collect(DB::select($sql, $values));
    }

    /**
     * Create multiple records in a single transaction
     *
     * @param array<array<string, mixed>> $records
     */
    public function createMany(array $records): Collection
    {
        if (empty($records)) {
            return collect();
        }

        $created = collect();

        DB::transaction(function () use ($records, &$created): void {
            foreach ($records as $data) {
                $columns = implode(', ', array_keys($data));
                $placeholders = implode(', ', array_fill(0, count($data), '?'));
                $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";

                DB::insert($sql, array_values($data));
                $id = DB::getPdo()->lastInsertId();

                $created->push((object) array_merge(['id' => (int) $id], $data));
            }
        });

        return $created;
    }

    /**
     * Update multiple records in a single transaction
     *
     * @param array<array<string, mixed>> $records Each must contain 'id'
     */
    public function updateMany(array $records): int
    {
        $updated = 0;

        DB::transaction(function () use ($records, &$updated): void {
            foreach ($records as $data) {
                if (!isset($data['id'])) {
                    continue;
                }

                $id = $data['id'];
                unset($data['id']);

                if (empty($data)) {
                    continue;
                }

                $setClauses = [];
                $values = [];
                foreach ($data as $column => $value) {
                    $setClauses[] = "{$column} = ?";
                    $values[] = $value;
                }
                $values[] = $id;

                $sql = "UPDATE {$this->table} SET " . implode(', ', $setClauses) . " WHERE id = ?";

                if (DB::update($sql, $values) > 0) {
                    $updated++;
                }
            }
        });

        return $updated;
    }

    /**
     * Delete multiple records by IDs
     *
     * @param array<int> $ids
     */
    public function deleteMany(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "DELETE FROM {$this->table} WHERE id IN ({$placeholders})";

        return DB::delete($sql, $ids);
    }
}
