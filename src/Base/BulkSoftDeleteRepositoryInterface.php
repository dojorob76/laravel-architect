<?php

declare(strict_types=1);

namespace LaravelArchitect\Base;

/**
 * Interface for bulk soft delete operations.
 *
 * Implement this alongside BulkRepositoryInterface and SoftDeleteRepositoryInterface
 * for models that use soft deletes and need batch restore/force delete.
 */
interface BulkSoftDeleteRepositoryInterface
{
    /**
     * Restore multiple soft-deleted records
     *
     * @param array<int> $ids
     * @return int Number of records restored
     */
    public function restoreMany(array $ids): int;

    /**
     * Permanently delete multiple records
     *
     * @param array<int> $ids
     * @return int Number of records deleted
     */
    public function forceDeleteMany(array $ids): int;

    /**
     * Find multiple records including soft-deleted
     *
     * @param array<int> $ids
     * @return iterable
     */
    public function findManyWithTrashed(array $ids): iterable;
}
