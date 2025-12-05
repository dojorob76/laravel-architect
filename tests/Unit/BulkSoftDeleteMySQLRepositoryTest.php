<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use LaravelArchitect\Base\BaseMySQLRepository;
use LaravelArchitect\Base\Contracts\BulkSoftDeleteRepositoryInterface;
use LaravelArchitect\Base\Traits\BulkSoftDeleteMySQLRepository;

class TestBulkSoftDeleteMySQLRepository extends BaseMySQLRepository implements BulkSoftDeleteRepositoryInterface
{
    use BulkSoftDeleteMySQLRepository;

    public function __construct()
    {
        $this->table = 'test_table';
    }
}

describe('BulkSoftDeleteMySQLRepository trait', function () {
    beforeEach(function () {
        $this->repository = new TestBulkSoftDeleteMySQLRepository();
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

    describe('findManyWithTrashed', function () {
        test('generates correct SQL without deleted_at filter', function () {
            $records = [(object) ['id' => 1], (object) ['id' => 2]];

            DB::shouldReceive('select')
                ->with('SELECT * FROM test_table WHERE id IN (?,?,?)', [1, 2, 3])
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
});
