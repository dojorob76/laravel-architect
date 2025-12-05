<?php

declare(strict_types=1);

namespace LaravelArchitect\Base\Traits;

use Illuminate\Database\Eloquent\Collection;

/**
 * Trait providing bulk soft delete operations for Eloquent models.
 *
 * Use this trait in repositories that implement BulkSoftDeleteRepositoryInterface.
 * Requires $this->model to be set and SoftDeleteEloquentRepository trait.
 */
trait BulkSoftDeleteEloquentRepository
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

        return $this->model->withTrashed()->whereIn('id', $ids)->restore();
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

        return $this->model->withTrashed()->whereIn('id', $ids)->forceDelete();
    }

    /**
     * Find multiple records including soft-deleted
     *
     * @param array<int> $ids
     */
    public function findManyWithTrashed(array $ids): Collection
    {
        if (empty($ids)) {
            return new Collection();
        }

        return $this->model->withTrashed()->whereIn('id', $ids)->get();
    }
}
