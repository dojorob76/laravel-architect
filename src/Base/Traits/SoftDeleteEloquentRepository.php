<?php

declare(strict_types=1);

namespace LaravelArchitect\Base\Traits;

/**
 * Trait providing soft delete repository methods for Eloquent models.
 *
 * Use this trait in repositories that implement SoftDeleteRepositoryInterface.
 * Requires $this->model to be set (typically from BaseEloquentRepository).
 */
trait SoftDeleteEloquentRepository
{
    /**
     * Restore a soft-deleted record
     */
    public function restore(int $id): bool
    {
        $record = $this->model->withTrashed()->find($id);

        if (!$record || !method_exists($record, 'restore')) {
            return false;
        }

        return $record->restore();
    }

    /**
     * Permanently delete a record
     */
    public function forceDelete(int $id): bool
    {
        $record = $this->model->withTrashed()->find($id);

        if (!$record) {
            return false;
        }

        return $record->forceDelete();
    }

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
     * Find a record including soft-deleted
     */
    public function findWithTrashed(int $id): ?object
    {
        return $this->model->withTrashed()->find($id);
    }

    /**
     * Find only soft-deleted records
     */
    public function findOnlyTrashed(int $id): ?object
    {
        return $this->model->onlyTrashed()->find($id);
    }

    /**
     * Get all records including soft-deleted
     */
    public function allWithTrashed(): iterable
    {
        return $this->model->withTrashed()->get();
    }

    /**
     * Get only soft-deleted records
     */
    public function onlyTrashed(): iterable
    {
        return $this->model->onlyTrashed()->get();
    }
}
