<?php

declare(strict_types=1);

namespace LaravelArchitect\Base;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LaravelArchitect\Base\Contracts\BaseRepositoryInterface;
use LaravelArchitect\Console\Commands\Support\ConfigHelper;

/**
 * Base MySQL repository implementing standard CRUD operations using raw SQL.
 *
 * Child classes must define:
 * - protected string $table = 'table_name';
 * - protected string $modelClass = 'Full\Namespace\ModelName'; (per config/api_scaffolding.php namespaces.models)
 *
 * For bulk operations, use the BulkMySQLRepository trait.
 * For soft delete operations, use the SoftDeleteMySQLRepository trait.
 */
abstract class BaseMySQLRepository implements BaseRepositoryInterface
{
    /**
     * The database table name.
     */
    protected string $table;

    /**
     * The fully qualified model class name for hydration.
     */
    protected string $modelClass;

    /**
     * Get all records.
     */
    public function all(): Collection
    {
        $sql = $this->hasSoftDeletes()
            ? "SELECT * FROM {$this->table} WHERE deleted_at IS NULL ORDER BY id DESC"
            : "SELECT * FROM {$this->table} ORDER BY id DESC";

        $results = DB::select($sql);

        return collect($results)->map(fn (object $item): Model => $this->hydrateModel($item));
    }

    /**
     * Get paginated records.
     */
    public function paginate(?int $perPage = null): LengthAwarePaginator
    {
        $limit = $perPage ?? ConfigHelper::paginationLimit();
        $page = LengthAwarePaginator::resolveCurrentPage();
        $offset = ($page - 1) * $limit;

        $whereClause = $this->hasSoftDeletes() ? 'WHERE deleted_at IS NULL' : '';

        $results = DB::select(
            "SELECT * FROM {$this->table} {$whereClause} ORDER BY id DESC LIMIT ? OFFSET ?",
            [$limit, $offset]
        );

        $totalResult = DB::selectOne("SELECT COUNT(*) as count FROM {$this->table} {$whereClause}");
        $total = $totalResult->count ?? 0;

        $items = collect($results)->map(fn (object $item): Model => $this->hydrateModel($item));

        return new LengthAwarePaginator($items, $total, $limit, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
        ]);
    }

    /**
     * Find a single record by ID.
     */
    public function find(int $id): ?object
    {
        $sql = $this->hasSoftDeletes()
            ? "SELECT * FROM {$this->table} WHERE id = ? AND deleted_at IS NULL LIMIT 1"
            : "SELECT * FROM {$this->table} WHERE id = ? LIMIT 1";

        $result = DB::selectOne($sql, [$id]);

        return $result ? $this->hydrateModel($result) : null;
    }

    /**
     * Create a new record.
     */
    public function create(array $data): object
    {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($data), '?');

        DB::beginTransaction();

        try {
            $sql = sprintf(
                "INSERT INTO %s (%s, created_at, updated_at) VALUES (%s, NOW(), NOW())",
                $this->table,
                implode(', ', $columns),
                implode(', ', $placeholders)
            );

            DB::insert($sql, array_values($data));

            $id = DB::connection()->getPdo()->lastInsertId();
            $record = $this->find((int) $id);

            DB::commit();

            if (! $record) {
                throw new \RuntimeException("Failed to retrieve created record with ID: {$id}");
            }

            return $record;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update a record by ID.
     */
    public function update(int $id, array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $setParts = array_map(fn (string $col): string => "{$col} = ?", array_keys($data));
        $values = array_values($data);
        $values[] = $id;

        $whereClause = $this->hasSoftDeletes()
            ? "WHERE id = ? AND deleted_at IS NULL"
            : "WHERE id = ?";

        $sql = sprintf(
            "UPDATE %s SET %s, updated_at = NOW() %s",
            $this->table,
            implode(', ', $setParts),
            $whereClause
        );

        return DB::update($sql, $values) > 0;
    }

    /**
     * Delete a record by ID (soft delete if table supports it).
     */
    public function delete(int $id): bool
    {
        if ($this->hasSoftDeletes()) {
            $sql = "UPDATE {$this->table} SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL";

            return DB::update($sql, [$id]) > 0;
        }

        $sql = "DELETE FROM {$this->table} WHERE id = ?";

        return DB::delete($sql, [$id]) > 0;
    }

    /**
     * Search records by filters.
     */
    public function search(array $filters, ?int $perPage = null): LengthAwarePaginator
    {
        $whereClauses = $this->hasSoftDeletes() ? ['deleted_at IS NULL'] : [];
        $bindings = [];

        foreach ($filters as $field => $value) {
            if ($value !== null && $value !== '') {
                $whereClauses[] = "{$field} LIKE ?";
                $bindings[] = "%{$value}%";
            }
        }

        $whereString = ! empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

        $limit = $perPage ?? ConfigHelper::paginationLimit();
        $page = LengthAwarePaginator::resolveCurrentPage();
        $offset = ($page - 1) * $limit;

        $results = DB::select(
            "SELECT * FROM {$this->table} {$whereString} ORDER BY id DESC LIMIT ? OFFSET ?",
            array_merge($bindings, [$limit, $offset])
        );

        $totalResult = DB::selectOne(
            "SELECT COUNT(*) as count FROM {$this->table} {$whereString}",
            $bindings
        );
        $total = $totalResult->count ?? 0;

        $items = collect($results)->map(fn (object $item): Model => $this->hydrateModel($item));

        return new LengthAwarePaginator($items, $total, $limit, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
        ]);
    }

    /**
     * Check if the table has soft deletes enabled.
     */
    protected function hasSoftDeletes(): bool
    {
        static $cache = null;

        if ($cache === null) {
            $columns = DB::select("SHOW COLUMNS FROM {$this->table}");
            $cache = collect($columns)->contains(fn (object $col): bool => $col->Field === 'deleted_at');
        }

        return $cache;
    }

    /**
     * Hydrate a model from a database result object.
     */
    protected function hydrateModel(object $data): Model
    {
        $model = new $this->modelClass;

        foreach (get_object_vars($data) as $key => $value) {
            $model->setAttribute($key, $value);
        }

        $model->exists = true;
        $model->syncOriginal();

        return $model;
    }
}
