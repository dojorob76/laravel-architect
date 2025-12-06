<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use LaravelArchitect\Base\BaseEloquentRepository;
use LaravelArchitect\Base\Contracts\SoftDeleteRepositoryInterface;
use LaravelArchitect\Base\Traits\SoftDeleteEloquentRepository;

// Create a test model that uses SoftDeletes
class TestSoftDeletableModel extends Model
{
    use SoftDeletes;

    protected $guarded = [];
}

// Create a test repository that uses the trait
class TestSoftDeleteEloquentRepository extends BaseEloquentRepository implements SoftDeleteRepositoryInterface
{
    use SoftDeleteEloquentRepository;
}

describe('SoftDeleteEloquentRepository trait', function () {
    beforeEach(function () {
        $this->model = Mockery::mock(TestSoftDeletableModel::class)->makePartial();
        $this->repository = new TestSoftDeleteEloquentRepository($this->model);
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('restore', function () {
        test('restores a soft-deleted record', function () {
            $record = Mockery::mock(TestSoftDeletableModel::class)->makePartial();
            $record->shouldReceive('restore')->once()->andReturn(true);

            $query = Mockery::mock();
            $query->shouldReceive('find')->with(1)->andReturn($record);

            $this->model->shouldReceive('withTrashed')->once()->andReturn($query);

            expect($this->repository->restore(1))->toBeTrue();
        });

        test('returns false if record not found', function () {
            $query = Mockery::mock();
            $query->shouldReceive('find')->with(999)->andReturn(null);

            $this->model->shouldReceive('withTrashed')->once()->andReturn($query);

            expect($this->repository->restore(999))->toBeFalse();
        });

        test('returns false if record does not support restore', function () {
            $record = new stdClass();

            $query = Mockery::mock();
            $query->shouldReceive('find')->with(1)->andReturn($record);

            $this->model->shouldReceive('withTrashed')->once()->andReturn($query);

            expect($this->repository->restore(1))->toBeFalse();
        });
    });

    describe('forceDelete', function () {
        test('permanently deletes a record', function () {
            $record = Mockery::mock(TestSoftDeletableModel::class)->makePartial();
            $record->shouldReceive('forceDelete')->once()->andReturn(true);

            $query = Mockery::mock();
            $query->shouldReceive('find')->with(1)->andReturn($record);

            $this->model->shouldReceive('withTrashed')->once()->andReturn($query);

            expect($this->repository->forceDelete(1))->toBeTrue();
        });

        test('returns false if record not found', function () {
            $query = Mockery::mock();
            $query->shouldReceive('find')->with(999)->andReturn(null);

            $this->model->shouldReceive('withTrashed')->once()->andReturn($query);

            expect($this->repository->forceDelete(999))->toBeFalse();
        });
    });

    describe('findWithTrashed', function () {
        test('finds record including trashed', function () {
            $record = Mockery::mock(TestSoftDeletableModel::class)->makePartial();

            $query = Mockery::mock();
            $query->shouldReceive('find')->with(1)->andReturn($record);

            $this->model->shouldReceive('withTrashed')->once()->andReturn($query);

            expect($this->repository->findWithTrashed(1))->toBe($record);
        });

        test('returns null if not found', function () {
            $query = Mockery::mock();
            $query->shouldReceive('find')->with(999)->andReturn(null);

            $this->model->shouldReceive('withTrashed')->once()->andReturn($query);

            expect($this->repository->findWithTrashed(999))->toBeNull();
        });
    });

    describe('findOnlyTrashed', function () {
        test('finds only trashed records', function () {
            $record = Mockery::mock(TestSoftDeletableModel::class)->makePartial();

            $query = Mockery::mock();
            $query->shouldReceive('find')->with(1)->andReturn($record);

            $this->model->shouldReceive('onlyTrashed')->once()->andReturn($query);

            expect($this->repository->findOnlyTrashed(1))->toBe($record);
        });
    });

    describe('allWithTrashed', function () {
        test('returns all records including trashed', function () {
            $collection = new Collection([]);

            $query = Mockery::mock();
            $query->shouldReceive('get')->once()->andReturn($collection);

            $this->model->shouldReceive('withTrashed')->once()->andReturn($query);

            expect($this->repository->allWithTrashed())->toBe($collection);
        });
    });

    describe('onlyTrashed', function () {
        test('returns only trashed records', function () {
            $collection = new Collection([]);

            $query = Mockery::mock();
            $query->shouldReceive('get')->once()->andReturn($collection);

            $this->model->shouldReceive('onlyTrashed')->once()->andReturn($query);

            expect($this->repository->onlyTrashed())->toBe($collection);
        });
    });
});
