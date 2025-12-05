<?php

declare(strict_types=1);

namespace LaravelArchitect\Base;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Base repository interface for standard CRUD operations.
 *
 * For soft delete support, also implement SoftDeleteRepositoryInterface.
 * For bulk operations, also implement BulkRepositoryInterface.
 */
interface BaseRepositoryInterface
{
    /**
     * Get all records
     */
    public function all(): Collection;

    /**
     * Get paginated records
     */
    public function paginate(?int $perPage = null): LengthAwarePaginator;

    /**
     * Find a single record by ID
     */
    public function find(int $id): ?Model;

    /**
     * Create a new record
     */
    public function create(array $data): Model;

    /**
     * Update a record by ID
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete a record by ID (soft delete if model supports it)
     */
    public function delete(int $id): bool;

    /**
     * Search records by filters
     */
    public function search(array $filters, ?int $perPage = null): LengthAwarePaginator;

    /**
     * Find multiple records by IDs
     */
    public function findMany(array $ids): Collection;

    /**
     * Create multiple records
     */
    public function createMany(array $records): Collection;

    /**
     * Update multiple records
     */
    public function updateMany(array $records): int;

    /**
     * Delete multiple records by IDs
     */
    public function deleteMany(array $ids): int;
}

