<?php

declare(strict_types=1);

namespace LaravelArchitect\Base\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Trait providing bulk/batch repository methods for Eloquent models.
 *
 * Use this trait in repositories that implement BulkRepositoryInterface.
 * Requires $this->model to be set (typically from BaseEloquentRepository).
 */
trait BulkEloquentRepository
{
    /**
     * Find multiple records by IDs
     *
     * @param array<int> $ids
     */
    public function findMany(array $ids): Collection
    {
        if (empty($ids)) {
            return new Collection();
        }

        return $this->model->whereIn('id', $ids)->get();
    }

    /**
     * Find multiple records by column criteria
     *
     * @param array<string, mixed> $criteria Column-value pairs (e.g., ['status' => 'active'] or ['name' => 'Claude', 'model' => 'Opus'])
     */
    public function findManyByColumns(array $criteria): Collection
    {
        if (empty($criteria)) {
            return new Collection();
        }

        $query = $this->model->newQuery();

        foreach ($criteria as $column => $value) {
            $query->where($column, $value);
        }

        return $query->get();
    }

    /**
     * Create multiple records in a single transaction
     *
     * @param array<array<string, mixed>> $records
     */
    public function createMany(array $records): Collection
    {
        if (empty($records)) {
            return new Collection();
        }

        $created = new Collection();

        DB::transaction(function () use ($records, &$created): void {
            foreach ($records as $data) {
                $created->push($this->model->create($data));
            }
        });

        return $created;
    }

    /**
     * Update multiple records in a single transaction
     *
     * @param array<array<string, mixed>> $records Each must contain 'id'
     */
    public function updateMany(array $records): int
    {
        $updated = 0;

        DB::transaction(function () use ($records, &$updated): void {
            foreach ($records as $data) {
                if (!isset($data['id'])) {
                    continue;
                }

                $id = $data['id'];
                unset($data['id']);

                $record = $this->model->find($id);
                if ($record && $record->update($data)) {
                    $updated++;
                }
            }
        });

        return $updated;
    }

    /**
     * Delete multiple records by IDs
     *
     * @param array<int> $ids
     */
    public function deleteMany(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        return $this->model->whereIn('id', $ids)->delete();
    }
}
