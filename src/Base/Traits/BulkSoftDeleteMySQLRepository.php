<?php

declare(strict_types=1);

namespace LaravelArchitect\Base\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Trait providing bulk soft delete operations for raw MySQL queries.
 *
 * Use this trait in repositories that extend BaseMySQLRepository
 * and implement BulkSoftDeleteRepositoryInterface.
 * Requires $this->table to be set.
 * Uses ? placeholder parameters for MySQL prepared statements.
 */
trait BulkSoftDeleteMySQLRepository
{
    /**
     * Restore multiple soft-deleted records
     *
     * @param array<int> $ids
     */
    public function restoreMany(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "UPDATE {$this->table} SET deleted_at = NULL WHERE id IN ({$placeholders}) AND deleted_at IS NOT NULL";

        return DB::update($sql, $ids);
    }

    /**
     * Permanently delete multiple records
     *
     * @param array<int> $ids
     */
    public function forceDeleteMany(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "DELETE FROM {$this->table} WHERE id IN ({$placeholders})";

        return DB::delete($sql, $ids);
    }

    /**
     * Find multiple records including soft-deleted
     *
     * @param array<int> $ids
     */
    public function findManyWithTrashed(array $ids): Collection
    {
        if (empty($ids)) {
            return collect();
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT * FROM {$this->table} WHERE id IN ({$placeholders})";

        return collect(DB::select($sql, $ids));
    }
}
