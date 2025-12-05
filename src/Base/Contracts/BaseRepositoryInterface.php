<?php

declare(strict_types=1);

namespace LaravelArchitect\Base\Contracts;

use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Base repository interface for standard CRUD operations.
 *
 * For soft delete support, also implement SoftDeleteRepositoryInterface.
 * For bulk operations, also implement BulkRepositoryInterface.
 *
 * Note: Return types use generic types (object, Collection) to support
 * both Eloquent and raw SQL (MySQL/PostgreSQL) implementations.
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
    public function find(int $id): ?object;

    /**
     * Create a new record
     */
    public function create(array $data): object;

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
}
