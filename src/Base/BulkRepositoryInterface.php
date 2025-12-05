<?php

declare(strict_types=1);

namespace LaravelArchitect\Base;

use Illuminate\Support\Collection;

/**
 * Interface for repositories that support bulk/batch operations.
 *
 * Implement this interface alongside BaseRepositoryInterface
 * when you need batch CRUD capabilities.
 *
 * Consider using raw SQL implementations (MySQL/Postgres repositories)
 * for better performance with large datasets.
 *
 * Note: Uses Illuminate\Support\Collection (not Eloquent\Collection) to support
 * both Eloquent and raw SQL implementations.
 */
interface BulkRepositoryInterface
{
    /**
     * Find multiple records by IDs
     *
     * @param array<int> $ids
     * @return Collection
     */
    public function findMany(array $ids): Collection;

    /**
     * Create multiple records in a single transaction
     *
     * @param array<array<string, mixed>> $records
     * @return Collection Created records
     */
    public function createMany(array $records): Collection;

    /**
     * Update multiple records in a single transaction
     * Each record must contain 'id' key
     *
     * @param array<array<string, mixed>> $records
     * @return int Number of records updated
     */
    public function updateMany(array $records): int;

    /**
     * Delete multiple records by IDs
     *
     * @param array<int> $ids
     * @return int Number of records deleted
     */
    public function deleteMany(array $ids): int;
}
