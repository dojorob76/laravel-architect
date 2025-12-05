<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use LaravelArchitect\Base\BasePostgresRepository;
use LaravelArchitect\Base\Contracts\BulkSoftDeleteRepositoryInterface;
use LaravelArchitect\Base\Traits\BulkSoftDeletePostgresRepository;

class TestBulkSoftDeletePostgresRepository extends BasePostgresRepository implements BulkSoftDeleteRepositoryInterface
{
    use BulkSoftDeletePostgresRepository;

    public function __construct()
    {
        $this->table = 'test_table';
    }
}

describe('BulkSoftDeletePostgresRepository trait', function () {
    beforeEach(function () {
        $this->repository = new TestBulkSoftDeletePostgresRepository();
    });

    describe('restoreMany', function () {
        test('generates correct SQL with positional placeholders', function () {
            DB::shouldReceive('update')
                ->with('UPDATE test_table SET deleted_at = NULL WHERE id IN ($1, $2, $3) AND deleted_at IS NOT NULL', [1, 2, 3])
                ->once()
                ->andReturn(3);

            expect($this->repository->restoreMany([1, 2, 3]))->toBe(3);
        });

        test('returns zero for empty array', function () {
            expect($this->repository->restoreMany([]))->toBe(0);
        });
    });

    describe('forceDeleteMany', function () {
        test('generates correct SQL with positional placeholders', function () {
            DB::shouldReceive('delete')
                ->with('DELETE FROM test_table WHERE id IN ($1, $2, $3)', [1, 2, 3])
                ->once()
                ->andReturn(3);

            expect($this->repository->forceDeleteMany([1, 2, 3]))->toBe(3);
        });

        test('returns zero for empty array', function () {
            expect($this->repository->forceDeleteMany([]))->toBe(0);
        });
    });

    describe('findManyWithTrashed', function () {
        test('generates correct SQL with positional placeholders', function () {
            $records = [(object) ['id' => 1], (object) ['id' => 2]];

            DB::shouldReceive('select')
                ->with('SELECT * FROM test_table WHERE id IN ($1, $2, $3)', [1, 2, 3])
                ->once()
                ->andReturn($records);

            $result = $this->repository->findManyWithTrashed([1, 2, 3]);
            expect($result->toArray())->toBe($records);
        });

        test('returns empty collection for empty array', function () {
            $result = $this->repository->findManyWithTrashed([]);
            expect($result)->toBeEmpty();
        });
    });

    describe('buildPositionalPlaceholders', function () {
        test('builds correct placeholders starting at 1', function () {
            $method = new ReflectionMethod($this->repository, 'buildPositionalPlaceholders');
            $method->setAccessible(true);

            expect($method->invoke($this->repository, 3))->toBe('$1, $2, $3');
        });

        test('builds correct placeholders starting at custom position', function () {
            $method = new ReflectionMethod($this->repository, 'buildPositionalPlaceholders');
            $method->setAccessible(true);

            expect($method->invoke($this->repository, 2, 4))->toBe('$4, $5');
        });
    });
});
