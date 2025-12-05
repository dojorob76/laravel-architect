<?php

declare(strict_types=1);

namespace LaravelArchitect\Base\Contracts;

use Illuminate\Support\Collection;

/**
 * Interface for bulk/batch repository operations.
 */
interface BulkRepositoryInterface
{
    /**
     * Find multiple records by IDs
     *
     * @param array<int> $ids
     */
    public function findMany(array $ids): Collection;

    /**
     * Find multiple records by column criteria
     *
     * @param array<string, mixed> $criteria Column-value pairs (e.g., ['status' => 'active'] or ['name' => 'Claude', 'model' => 'Opus'])
     */
    public function findManyByColumns(array $criteria): Collection;

    /**
     * Create multiple records in a single transaction
     *
     * @param array<array<string, mixed>> $records
     */
    public function createMany(array $records): Collection;

    /**
     * Update multiple records in a single transaction
     *
     * @param array<array<string, mixed>> $records Each must contain 'id'
     */
    public function updateMany(array $records): int;

    /**
     * Delete multiple records by IDs
     *
     * @param array<int> $ids
     */
    public function deleteMany(array $ids): int;
}
