<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use LaravelArchitect\Base\BaseMySQLRepository;
use LaravelArchitect\Base\Contracts\BulkRepositoryInterface;
use LaravelArchitect\Base\Traits\BulkMySQLRepository;

class TestBulkMySQLRepository extends BaseMySQLRepository implements BulkRepositoryInterface
{
    use BulkMySQLRepository;

    public function __construct()
    {
        $this->table = 'test_table';
    }
}

describe('BulkMySQLRepository trait', function () {
    beforeEach(function () {
        $this->repository = new TestBulkMySQLRepository();
    });

    describe('findMany', function () {
        test('generates correct SQL with ? placeholders', function () {
            DB::shouldReceive('select')
                ->with('SELECT * FROM test_table WHERE id IN (?,?,?)', [1, 2, 3])
                ->once()
                ->andReturn([]);

            $this->repository->findMany([1, 2, 3]);
        });

        test('returns empty collection for empty array', function () {
            $result = $this->repository->findMany([]);
            expect($result)->toBeEmpty();
        });
    });

    describe('findManyByColumns', function () {
        test('generates correct SQL for single column criteria', function () {
            DB::shouldReceive('select')
                ->with('SELECT * FROM test_table WHERE status = ?', ['active'])
                ->once()
                ->andReturn([]);

            $this->repository->findManyByColumns(['status' => 'active']);
        });

        test('generates correct SQL for multiple column criteria', function () {
            DB::shouldReceive('select')
                ->with('SELECT * FROM test_table WHERE name = ? AND model = ?', ['Claude', 'Opus'])
                ->once()
                ->andReturn([]);

            $this->repository->findManyByColumns(['name' => 'Claude', 'model' => 'Opus']);
        });

        test('returns empty collection for empty criteria', function () {
            $result = $this->repository->findManyByColumns([]);
            expect($result)->toBeEmpty();
        });
    });

    describe('deleteMany', function () {
        test('generates correct DELETE SQL', function () {
            DB::shouldReceive('delete')
                ->with('DELETE FROM test_table WHERE id IN (?,?,?)', [1, 2, 3])
                ->once()
                ->andReturn(3);

            expect($this->repository->deleteMany([1, 2, 3]))->toBe(3);
        });

        test('returns zero for empty array', function () {
            expect($this->repository->deleteMany([]))->toBe(0);
        });
    });
});
