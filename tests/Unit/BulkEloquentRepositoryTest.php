<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use LaravelArchitect\Base\BaseEloquentRepository;
use LaravelArchitect\Base\Contracts\BulkRepositoryInterface;
use LaravelArchitect\Base\Traits\BulkEloquentRepository;

class TestBulkEloquentRepository extends BaseEloquentRepository implements BulkRepositoryInterface
{
    use BulkEloquentRepository;
}

describe('BulkEloquentRepository trait', function () {
    beforeEach(function () {
        $this->model = Mockery::mock(Model::class);
        $this->repository = new TestBulkEloquentRepository($this->model);
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('findMany', function () {
        test('finds multiple records by IDs', function () {
            $collection = new Collection([]);

            $query = Mockery::mock();
            $query->shouldReceive('get')->once()->andReturn($collection);

            $this->model->shouldReceive('whereIn')
                ->with('id', [1, 2, 3])
                ->once()
                ->andReturn($query);

            $result = $this->repository->findMany([1, 2, 3]);
            expect($result)->toBe($collection);
        });

        test('returns empty collection for empty array', function () {
            $result = $this->repository->findMany([]);
            expect($result)->toBeInstanceOf(Collection::class);
            expect($result)->toBeEmpty();
        });
    });

    describe('findManyByColumns', function () {
        test('finds records with single column criteria', function () {
            $collection = new Collection([]);

            $query = Mockery::mock();
            $query->shouldReceive('where')->with('status', 'active')->once()->andReturnSelf();
            $query->shouldReceive('get')->once()->andReturn($collection);

            $this->model->shouldReceive('newQuery')->once()->andReturn($query);

            $result = $this->repository->findManyByColumns(['status' => 'active']);
            expect($result)->toBe($collection);
        });

        test('finds records with multiple column criteria', function () {
            $collection = new Collection([]);

            $query = Mockery::mock();
            $query->shouldReceive('where')->with('name', 'Claude')->once()->andReturnSelf();
            $query->shouldReceive('where')->with('model', 'Opus')->once()->andReturnSelf();
            $query->shouldReceive('get')->once()->andReturn($collection);

            $this->model->shouldReceive('newQuery')->once()->andReturn($query);

            $result = $this->repository->findManyByColumns(['name' => 'Claude', 'model' => 'Opus']);
            expect($result)->toBe($collection);
        });

        test('returns empty collection for empty criteria', function () {
            $result = $this->repository->findManyByColumns([]);
            expect($result)->toBeInstanceOf(Collection::class);
            expect($result)->toBeEmpty();
        });
    });

    describe('deleteMany', function () {
        test('deletes multiple records', function () {
            $query = Mockery::mock();
            $query->shouldReceive('delete')->once()->andReturn(3);

            $this->model->shouldReceive('whereIn')
                ->with('id', [1, 2, 3])
                ->once()
                ->andReturn($query);

            expect($this->repository->deleteMany([1, 2, 3]))->toBe(3);
        });

        test('returns zero for empty array', function () {
            expect($this->repository->deleteMany([]))->toBe(0);
        });
    });
});
