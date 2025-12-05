<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use LaravelArchitect\Base\BaseEloquentRepository;
use LaravelArchitect\Base\Contracts\BulkSoftDeleteRepositoryInterface;
use LaravelArchitect\Base\Traits\BulkSoftDeleteEloquentRepository;

class TestBulkSoftDeleteEloquentRepository extends BaseEloquentRepository implements BulkSoftDeleteRepositoryInterface
{
    use BulkSoftDeleteEloquentRepository;
}

describe('BulkSoftDeleteEloquentRepository trait', function () {
    beforeEach(function () {
        $this->model = Mockery::mock(Model::class);
        $this->repository = new TestBulkSoftDeleteEloquentRepository($this->model);
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('restoreMany', function () {
        test('restores multiple soft-deleted records', function () {
            $query = Mockery::mock();
            $query->shouldReceive('whereIn')->with('id', [1, 2, 3])->andReturnSelf();
            $query->shouldReceive('restore')->once()->andReturn(3);

            $this->model->shouldReceive('withTrashed')->once()->andReturn($query);

            expect($this->repository->restoreMany([1, 2, 3]))->toBe(3);
        });

        test('returns zero for empty array', function () {
            expect($this->repository->restoreMany([]))->toBe(0);
        });
    });

    describe('forceDeleteMany', function () {
        test('permanently deletes multiple records', function () {
            $query = Mockery::mock();
            $query->shouldReceive('whereIn')->with('id', [1, 2, 3])->andReturnSelf();
            $query->shouldReceive('forceDelete')->once()->andReturn(3);

            $this->model->shouldReceive('withTrashed')->once()->andReturn($query);

            expect($this->repository->forceDeleteMany([1, 2, 3]))->toBe(3);
        });

        test('returns zero for empty array', function () {
            expect($this->repository->forceDeleteMany([]))->toBe(0);
        });
    });

    describe('findManyWithTrashed', function () {
        test('finds multiple records including trashed', function () {
            $collection = new Collection([]);

            $query = Mockery::mock();
            $query->shouldReceive('whereIn')->with('id', [1, 2, 3])->andReturnSelf();
            $query->shouldReceive('get')->once()->andReturn($collection);

            $this->model->shouldReceive('withTrashed')->once()->andReturn($query);

            expect($this->repository->findManyWithTrashed([1, 2, 3]))->toBe($collection);
        });

        test('returns empty collection for empty array', function () {
            $result = $this->repository->findManyWithTrashed([]);
            expect($result)->toBeInstanceOf(Collection::class);
            expect($result)->toBeEmpty();
        });
    });
});
