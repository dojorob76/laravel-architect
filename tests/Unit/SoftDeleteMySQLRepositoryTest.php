<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use LaravelArchitect\Base\BaseMySQLRepository;
use LaravelArchitect\Base\SoftDeleteRepositoryInterface;
use LaravelArchitect\Base\Traits\SoftDeleteMySQLRepository;

class TestSoftDeleteMySQLRepository extends BaseMySQLRepository implements SoftDeleteRepositoryInterface
{
    use SoftDeleteMySQLRepository;

    public function __construct()
    {
        $this->table = 'test_table';
    }
}

describe('SoftDeleteMySQLRepository trait', function () {
    beforeEach(function () {
        $this->repository = new TestSoftDeleteMySQLRepository();
    });

    describe('restore', function () {
        test('generates correct SQL to restore a record', function () {
            DB::shouldReceive('update')
                ->with('UPDATE test_table SET deleted_at = NULL WHERE id = ? AND deleted_at IS NOT NULL', [1])
                ->once()
                ->andReturn(1);

            expect($this->repository->restore(1))->toBeTrue();
        });

        test('returns false if no record was restored', function () {
            DB::shouldReceive('update')
                ->with('UPDATE test_table SET deleted_at = NULL WHERE id = ? AND deleted_at IS NOT NULL', [999])
                ->once()
                ->andReturn(0);

            expect($this->repository->restore(999))->toBeFalse();
        });
    });

    describe('forceDelete', function () {
        test('generates correct SQL to permanently delete a record', function () {
            DB::shouldReceive('delete')
                ->with('DELETE FROM test_table WHERE id = ?', [1])
                ->once()
                ->andReturn(1);

            expect($this->repository->forceDelete(1))->toBeTrue();
        });

        test('returns false if no record was deleted', function () {
            DB::shouldReceive('delete')
                ->with('DELETE FROM test_table WHERE id = ?', [999])
                ->once()
                ->andReturn(0);

            expect($this->repository->forceDelete(999))->toBeFalse();
        });
    });

    describe('restoreMany', function () {
        test('generates correct SQL with ? placeholders', function () {
            DB::shouldReceive('update')
                ->with('UPDATE test_table SET deleted_at = NULL WHERE id IN (?,?,?) AND deleted_at IS NOT NULL', [1, 2, 3])
                ->once()
                ->andReturn(3);

            expect($this->repository->restoreMany([1, 2, 3]))->toBe(3);
        });

        test('returns zero for empty array', function () {
            expect($this->repository->restoreMany([]))->toBe(0);
        });
    });

    describe('forceDeleteMany', function () {
        test('generates correct SQL with ? placeholders', function () {
            DB::shouldReceive('delete')
                ->with('DELETE FROM test_table WHERE id IN (?,?,?)', [1, 2, 3])
                ->once()
                ->andReturn(3);

            expect($this->repository->forceDeleteMany([1, 2, 3]))->toBe(3);
        });

        test('returns zero for empty array', function () {
            expect($this->repository->forceDeleteMany([]))->toBe(0);
        });
    });

    describe('findWithTrashed', function () {
        test('generates correct SQL without deleted_at filter', function () {
            $record = (object) ['id' => 1, 'name' => 'Test'];

            DB::shouldReceive('selectOne')
                ->with('SELECT * FROM test_table WHERE id = ?', [1])
                ->once()
                ->andReturn($record);

            expect($this->repository->findWithTrashed(1))->toBe($record);
        });

        test('returns null if not found', function () {
            DB::shouldReceive('selectOne')
                ->with('SELECT * FROM test_table WHERE id = ?', [999])
                ->once()
                ->andReturn(null);

            expect($this->repository->findWithTrashed(999))->toBeNull();
        });
    });

    describe('findOnlyTrashed', function () {
        test('generates correct SQL with deleted_at filter', function () {
            $record = (object) ['id' => 1, 'name' => 'Test', 'deleted_at' => '2024-01-01'];

            DB::shouldReceive('selectOne')
                ->with('SELECT * FROM test_table WHERE id = ? AND deleted_at IS NOT NULL', [1])
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
});
