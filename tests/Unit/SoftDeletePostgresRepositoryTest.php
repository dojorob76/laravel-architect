<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use LaravelArchitect\Base\BasePostgresRepository;
use LaravelArchitect\Base\SoftDeleteRepositoryInterface;
use LaravelArchitect\Base\Traits\SoftDeletePostgresRepository;

class TestSoftDeletePostgresRepository extends BasePostgresRepository implements SoftDeleteRepositoryInterface
{
    use SoftDeletePostgresRepository;

    public function __construct()
    {
        $this->table = 'test_table';
    }
}

describe('SoftDeletePostgresRepository trait', function () {
    beforeEach(function () {
        $this->repository = new TestSoftDeletePostgresRepository();
    });

    describe('restore', function () {
        test('generates correct SQL with $1 placeholder', function () {
            DB::shouldReceive('update')
                ->with('UPDATE test_table SET deleted_at = NULL WHERE id = $1 AND deleted_at IS NOT NULL', [1])
                ->once()
                ->andReturn(1);

            expect($this->repository->restore(1))->toBeTrue();
        });

        test('returns false if no record was restored', function () {
            DB::shouldReceive('update')
                ->with('UPDATE test_table SET deleted_at = NULL WHERE id = $1 AND deleted_at IS NOT NULL', [999])
                ->once()
                ->andReturn(0);

            expect($this->repository->restore(999))->toBeFalse();
        });
    });

    describe('forceDelete', function () {
        test('generates correct SQL with $1 placeholder', function () {
            DB::shouldReceive('delete')
                ->with('DELETE FROM test_table WHERE id = $1', [1])
                ->once()
                ->andReturn(1);

            expect($this->repository->forceDelete(1))->toBeTrue();
        });

        test('returns false if no record was deleted', function () {
            DB::shouldReceive('delete')
                ->with('DELETE FROM test_table WHERE id = $1', [999])
                ->once()
                ->andReturn(0);

            expect($this->repository->forceDelete(999))->toBeFalse();
        });
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

    describe('findWithTrashed', function () {
        test('generates correct SQL with $1 placeholder', function () {
            $record = (object) ['id' => 1, 'name' => 'Test'];

            DB::shouldReceive('selectOne')
                ->with('SELECT * FROM test_table WHERE id = $1', [1])
                ->once()
                ->andReturn($record);

            expect($this->repository->findWithTrashed(1))->toBe($record);
        });
    });

    describe('findOnlyTrashed', function () {
        test('generates correct SQL with $1 placeholder and deleted_at filter', function () {
            $record = (object) ['id' => 1, 'name' => 'Test', 'deleted_at' => '2024-01-01'];

            DB::shouldReceive('selectOne')
                ->with('SELECT * FROM test_table WHERE id = $1 AND deleted_at IS NOT NULL', [1])
                ->once()
                ->andReturn($record);

            expect($this->repository->findOnlyTrashed(1))->toBe($record);
        });
    });

    describe('allWithTrashed', function () {
        test('generates correct SQL without deleted_at filter', function () {
            $records = [(object) ['id' => 1], (object) ['id' => 2]];

            DB::shouldReceive('select')
                ->with('SELECT * FROM test_table')
                ->once()
                ->andReturn($records);

            expect($this->repository->allWithTrashed())->toBe($records);
        });
    });

    describe('onlyTrashed', function () {
        test('generates correct SQL with deleted_at filter', function () {
            $records = [(object) ['id' => 1, 'deleted_at' => '2024-01-01']];

            DB::shouldReceive('select')
                ->with('SELECT * FROM test_table WHERE deleted_at IS NOT NULL')
                ->once()
                ->andReturn($records);

            expect($this->repository->onlyTrashed())->toBe($records);
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
