<?php

declare(strict_types=1);

namespace LaravelArchitect\Base\Traits;

use Illuminate\Support\Facades\DB;

/**
 * Trait providing soft delete repository methods for raw MySQL queries.
 *
 * Use this trait in repositories that extend BaseMySQLRepository
 * and implement SoftDeleteRepositoryInterface.
 * Requires $this->table to be set.
 * Uses ? placeholder parameters for MySQL prepared statements.
 */
trait SoftDeleteMySQLRepository
{
    /**
     * Restore a soft-deleted record
     */
    public function restore(int $id): bool
    {
        $sql = "UPDATE {$this->table} SET deleted_at = NULL WHERE id = ? AND deleted_at IS NOT NULL";

        return DB::update($sql, [$id]) > 0;
    }

    /**
     * Permanently delete a record
     */
    public function forceDelete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";

        return DB::delete($sql, [$id]) > 0;
    }

    /**
     * Find a record including soft-deleted
     */
    public function findWithTrashed(int $id): ?object
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";

        return DB::selectOne($sql, [$id]);
    }

    /**
     * Find only soft-deleted records
     */
    public function findOnlyTrashed(int $id): ?object
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ? AND deleted_at IS NOT NULL";

        return DB::selectOne($sql, [$id]);
    }

    /**
     * Get all records including soft-deleted
     */
    public function allWithTrashed(): iterable
    {
        $sql = "SELECT * FROM {$this->table}";

        return DB::select($sql);
    }

    /**
     * Get only soft-deleted records
     */
    public function onlyTrashed(): iterable
    {
        $sql = "SELECT * FROM {$this->table} WHERE deleted_at IS NOT NULL";

        return DB::select($sql);
    }
}
