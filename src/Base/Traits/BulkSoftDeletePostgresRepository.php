<?php

declare(strict_types=1);

namespace LaravelArchitect\Base\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Trait providing bulk soft delete operations for raw PostgreSQL queries.
 *
 * Use this trait in repositories that extend BasePostgresRepository
 * and implement BulkSoftDeleteRepositoryInterface.
 * Requires $this->table to be set.
 * Uses $1, $2 positional parameters for PostgreSQL prepared statements.
 */
trait BulkSoftDeletePostgresRepository
{
    use PostgresPlaceholderHelper;

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

        $placeholders = $this->buildPositionalPlaceholders(count($ids));
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

        $placeholders = $this->buildPositionalPlaceholders(count($ids));
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

        $placeholders = $this->buildPositionalPlaceholders(count($ids));
        $sql = "SELECT * FROM {$this->table} WHERE id IN ({$placeholders})";

        return collect(DB::select($sql, $ids));
    }
}
