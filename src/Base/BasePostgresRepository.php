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
 * Base PostgreSQL repository implementing standard CRUD operations using raw SQL.
 *
 * Child classes must define:
 * - protected string $table = 'table_name';
 * - protected string $modelClass = 'Full\Namespace\ModelName'; (per config/api_scaffolding.php namespaces.models)
 *
 * For bulk operations, use the BulkPostgresRepository trait.
 * For soft delete operations, use the SoftDeletePostgresRepository trait.
 */
abstract class BasePostgresRepository implements BaseRepositoryInterface
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
            "SELECT * FROM {$this->table} {$whereClause} ORDER BY id DESC LIMIT $1 OFFSET $2",
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
            ? "SELECT * FROM {$this->table} WHERE id = $1 AND deleted_at IS NULL LIMIT 1"
            : "SELECT * FROM {$this->table} WHERE id = $1 LIMIT 1";

        $result = DB::selectOne($sql, [$id]);

        return $result ? $this->hydrateModel($result) : null;
    }

    /**
     * Create a new record.
     */
    public function create(array $data): object
    {
        $columns = array_keys($data);
        $placeholders = array_map(fn (int $i): string => '$' . ($i + 1), array_keys($data));

        $sql = sprintf(
            "INSERT INTO %s (%s, created_at, updated_at) VALUES (%s, NOW(), NOW()) RETURNING *",
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $result = DB::selectOne($sql, array_values($data));

        if (! $result) {
            throw new \RuntimeException("Failed to create record in {$this->table}");
        }

        return $this->hydrateModel($result);
    }

    /**
     * Update a record by ID.
     */
    public function update(int $id, array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $setParts = [];
        $values = [];
        $paramIndex = 1;

        foreach ($data as $col => $value) {
            $setParts[] = "{$col} = $" . $paramIndex++;
            $values[] = $value;
        }

        $values[] = $id;

        $whereClause = $this->hasSoftDeletes()
            ? "WHERE id = \${$paramIndex} AND deleted_at IS NULL"
            : "WHERE id = \${$paramIndex}";

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
            $sql = "UPDATE {$this->table} SET deleted_at = NOW(), updated_at = NOW() WHERE id = $1 AND deleted_at IS NULL";

            return DB::update($sql, [$id]) > 0;
        }

        $sql = "DELETE FROM {$this->table} WHERE id = $1";

        return DB::delete($sql, [$id]) > 0;
    }

    /**
     * Search records by filters.
     */
    public function search(array $filters, ?int $perPage = null): LengthAwarePaginator
    {
        $whereClauses = $this->hasSoftDeletes() ? ['deleted_at IS NULL'] : [];
        $bindings = [];
        $paramIndex = 1;

        foreach ($filters as $field => $value) {
            if ($value !== null && $value !== '') {
                $whereClauses[] = "{$field} ILIKE $" . $paramIndex++;
                $bindings[] = "%{$value}%";
            }
        }

        $whereString = ! empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

        $limit = $perPage ?? ConfigHelper::paginationLimit();
        $page = LengthAwarePaginator::resolveCurrentPage();
        $offset = ($page - 1) * $limit;

        $limitParam = '$' . $paramIndex++;
        $offsetParam = '$' . $paramIndex;

        $bindings[] = $limit;
        $bindings[] = $offset;

        $results = DB::select(
            "SELECT * FROM {$this->table} {$whereString} ORDER BY id DESC LIMIT {$limitParam} OFFSET {$offsetParam}",
            $bindings
        );

        $totalResult = DB::selectOne(
            "SELECT COUNT(*) as count FROM {$this->table} {$whereString}",
            array_slice($bindings, 0, -2)
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
            $result = DB::selectOne(
                "SELECT column_name FROM information_schema.columns WHERE table_name = $1 AND column_name = $2",
                [$this->table, 'deleted_at']
            );

            $cache = $result !== null;
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
