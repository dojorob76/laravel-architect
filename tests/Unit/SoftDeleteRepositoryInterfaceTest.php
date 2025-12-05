<?php

declare(strict_types=1);

use LaravelArchitect\Base\Contracts\BaseRepositoryInterface;
use LaravelArchitect\Base\Contracts\SoftDeleteRepositoryInterface;

describe('SoftDeleteRepositoryInterface', function () {
    test('interface exists', function () {
        expect(interface_exists(SoftDeleteRepositoryInterface::class))->toBeTrue();
    });

    test('defines restore method', function () {
        $reflection = new ReflectionClass(SoftDeleteRepositoryInterface::class);
        expect($reflection->hasMethod('restore'))->toBeTrue();

        $method = $reflection->getMethod('restore');
        expect($method->isPublic())->toBeTrue();

        $params = $method->getParameters();
        expect($params)->toHaveCount(1);
        expect($params[0]->getName())->toBe('id');
        expect($params[0]->getType()->getName())->toBe('int');
    });

    test('defines forceDelete method', function () {
        $reflection = new ReflectionClass(SoftDeleteRepositoryInterface::class);
        expect($reflection->hasMethod('forceDelete'))->toBeTrue();

        $method = $reflection->getMethod('forceDelete');
        expect($method->getReturnType()->getName())->toBe('bool');
    });

    test('defines restoreMany method', function () {
        $reflection = new ReflectionClass(SoftDeleteRepositoryInterface::class);
        expect($reflection->hasMethod('restoreMany'))->toBeTrue();

        $method = $reflection->getMethod('restoreMany');
        $params = $method->getParameters();
        expect($params[0]->getType()->getName())->toBe('array');
        expect($method->getReturnType()->getName())->toBe('int');
    });

    test('defines forceDeleteMany method', function () {
        $reflection = new ReflectionClass(SoftDeleteRepositoryInterface::class);
        expect($reflection->hasMethod('forceDeleteMany'))->toBeTrue();
    });

    test('defines findWithTrashed method', function () {
        $reflection = new ReflectionClass(SoftDeleteRepositoryInterface::class);
        expect($reflection->hasMethod('findWithTrashed'))->toBeTrue();
    });

    test('defines findOnlyTrashed method', function () {
        $reflection = new ReflectionClass(SoftDeleteRepositoryInterface::class);
        expect($reflection->hasMethod('findOnlyTrashed'))->toBeTrue();
    });

    test('defines allWithTrashed method', function () {
        $reflection = new ReflectionClass(SoftDeleteRepositoryInterface::class);
        expect($reflection->hasMethod('allWithTrashed'))->toBeTrue();
    });

    test('defines onlyTrashed method', function () {
        $reflection = new ReflectionClass(SoftDeleteRepositoryInterface::class);
        expect($reflection->hasMethod('onlyTrashed'))->toBeTrue();
    });
});

describe('BaseRepositoryInterface after refactor', function () {
    test('does not include soft delete methods', function () {
        $reflection = new ReflectionClass(BaseRepositoryInterface::class);

        expect($reflection->hasMethod('restore'))->toBeFalse();
        expect($reflection->hasMethod('forceDelete'))->toBeFalse();
        expect($reflection->hasMethod('restoreMany'))->toBeFalse();
        expect($reflection->hasMethod('forceDeleteMany'))->toBeFalse();
    });

    test('still includes basic CRUD methods', function () {
        $reflection = new ReflectionClass(BaseRepositoryInterface::class);

        expect($reflection->hasMethod('all'))->toBeTrue();
        expect($reflection->hasMethod('find'))->toBeTrue();
        expect($reflection->hasMethod('create'))->toBeTrue();
        expect($reflection->hasMethod('update'))->toBeTrue();
        expect($reflection->hasMethod('delete'))->toBeTrue();
        expect($reflection->hasMethod('search'))->toBeTrue();
    });
});
