<?php

namespace LaravelArchitect\Base;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use LaravelArchitect\Console\Commands\Support\ConfigHelper;

abstract class BaseEloquentRepository
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function all(): Collection
    {
        return $this->model->all();
    }

    public function paginate(?int $perPage = null): LengthAwarePaginator
    {
        return $this->model->paginate($perPage ?? ConfigHelper::paginationLimit());
    }

    public function find(int $id): ?Model
    {
        return $this->model->find($id);
    }

    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): bool
    {
        $record = $this->model->find($id);
        return $record ? $record->update($data) : false;
    }

    public function delete(int $id): bool
    {
        $record = $this->model->find($id);
        if (!$record) {
            return false;
        }
        return $record->delete();
    }

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

    public function restore(int $id): bool
    {
        $record = $this->model->withTrashed()->find($id);
        if (!$record || !method_exists($record, 'restore')) {
            return false;
        }
        return $record->restore();
    }

    public function forceDelete(int $id): bool
    {
        $record = $this->model->withTrashed()->find($id);
        if (!$record) {
            return false;
        }
        return $record->forceDelete();
    }

    public function findMany(array $ids): Collection
    {
        return $this->model->whereIn('id', $ids)->get();
    }

    public function createMany(array $records): Collection
    {
        $created = collect();
        DB::transaction(function () use ($records, $created) {
            foreach ($records as $data) {
                $created->push($this->model->create($data));
            }
        });
        return $created;
    }

    public function updateMany(array $records): int
    {
        $updated = 0;
        DB::transaction(function () use ($records, &$updated) {
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

    public function deleteMany(array $ids): int
    {
        return $this->model->whereIn('id', $ids)->delete();
    }

    public function restoreMany(array $ids): int
    {
        if (!method_exists($this->model, 'restore')) {
            return 0;
        }
        return $this->model->withTrashed()->whereIn('id', $ids)->restore();
    }

    public function forceDeleteMany(array $ids): int
    {
        return $this->model->withTrashed()->whereIn('id', $ids)->forceDelete();
    }
}
