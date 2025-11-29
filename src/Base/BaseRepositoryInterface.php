<?php

namespace LaravelArchitect\Base;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface BaseRepositoryInterface
{
    public function all(): Collection;
    public function paginate(?int $perPage = null): LengthAwarePaginator;
    public function find(int $id): ?Model;
    public function create(array $data): Model;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
    public function search(array $filters, ?int $perPage = null): LengthAwarePaginator;
    public function restore(int $id): bool;
    public function forceDelete(int $id): bool;
    public function findMany(array $ids): Collection;
    public function createMany(array $records): Collection;
    public function updateMany(array $records): int;
    public function deleteMany(array $ids): int;
    public function restoreMany(array $ids): int;
    public function forceDeleteMany(array $ids): int;
}
