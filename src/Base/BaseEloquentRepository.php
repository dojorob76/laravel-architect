<?php

declare(strict_types=1);

namespace LaravelArchitect\Base;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use LaravelArchitect\Console\Commands\Support\ConfigHelper;

/**
 * Base Eloquent repository implementing standard CRUD operations.
 *
 * For soft delete support, extend this class and use SoftDeleteEloquentRepository trait.
 * For bulk operations, extend this class and use BulkEloquentRepository trait.
 */
class BaseEloquentRepository implements BaseRepositoryInterface
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Get all records
     */
    public function all(): Collection
    {
        return $this->model->all();
    }

    /**
     * Get paginated records
     */
    public function paginate(?int $perPage = null): LengthAwarePaginator
    {
        return $this->model->paginate($perPage ?? ConfigHelper::paginationLimit());
    }

    /**
     * Find a single record by ID
     */
    public function find(int $id): ?object
    {
        return $this->model->find($id);
    }

    /**
     * Create a new record
     */
    public function create(array $data): object
    {
        return $this->model->create($data);
    }

    /**
     * Update a record by ID
     */
    public function update(int $id, array $data): bool
    {
        $record = $this->model->find($id);

        if (!$record) {
            return false;
        }

        return $record->update($data);
    }

    /**
     * Delete a record by ID
     */
    public function delete(int $id): bool
    {
        $record = $this->model->find($id);

        if (!$record) {
            return false;
        }

        return $record->delete();
    }

    /**
     * Search records by filters
     */
    public function search(array $filters, ?int $perPage = null): LengthAwarePaginator
    {
        $query = $this->model->newQuery();
        foreach ($filters as $field => $value) {
            if ($value !== null && $value !== '') {
                $query->where($field, 'LIKE', "%{$value}%");
            }
        }
        return $query->paginate($perPage ?? ConfigHelper::paginationLimit());
    }
}
