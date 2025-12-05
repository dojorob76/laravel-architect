<?php

declare(strict_types=1);

namespace LaravelArchitect\Base\Contracts;

use Illuminate\Support\Collection;

/**
 * Interface for bulk soft delete repository operations.
 *
 * Implement this interface alongside BulkRepositoryInterface
 * when your model uses the SoftDeletes trait.
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
     */
    public function findManyWithTrashed(array $ids): Collection;
}
