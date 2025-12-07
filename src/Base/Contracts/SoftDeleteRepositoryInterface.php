<?php

declare(strict_types=1);

namespace LaravelArchitect\Base\Contracts;

/**
 * Interface for repositories that support soft delete operations.
 *
 * Implement this interface alongside BaseRepositoryInterface
 * when your model uses the SoftDeletes trait.
 */
interface SoftDeleteRepositoryInterface
{
    /**
     * Restore a soft-deleted record
     */
    public function restore(int $id): bool;

    /**
     * Permanently delete a record (bypassing soft delete)
     */
    public function forceDelete(int $id): bool;

    /**
     * Find a record including soft-deleted
     */
    public function findWithTrashed(int $id): ?object;

    /**
     * Find only soft-deleted records
     */
    public function findOnlyTrashed(int $id): ?object;

    /**
     * Get all records including soft-deleted
     */
    public function allWithTrashed(): iterable;

    /**
     * Get only soft-deleted records
     */
    public function onlyTrashed(): iterable;
}
