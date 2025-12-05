<?php

declare(strict_types=1);

use LaravelArchitect\Base\BulkRepositoryInterface;
use LaravelArchitect\Base\BulkSoftDeleteRepositoryInterface;

describe('BulkRepositoryInterface', function () {
    test('interface exists', function () {
        expect(interface_exists(BulkRepositoryInterface::class))->toBeTrue();
    });

    test('defines findMany method', function () {
        $reflection = new ReflectionClass(BulkRepositoryInterface::class);
        expect($reflection->hasMethod('findMany'))->toBeTrue();

        $method = $reflection->getMethod('findMany');
        $params = $method->getParameters();
        expect($params[0]->getName())->toBe('ids');
        expect($params[0]->getType()->getName())->toBe('array');
    });

    test('defines createMany method', function () {
        $reflection = new ReflectionClass(BulkRepositoryInterface::class);
        expect($reflection->hasMethod('createMany'))->toBeTrue();

        $method = $reflection->getMethod('createMany');
        $params = $method->getParameters();
        expect($params[0]->getName())->toBe('records');
    });

    test('defines updateMany method', function () {
        $reflection = new ReflectionClass(BulkRepositoryInterface::class);
        expect($reflection->hasMethod('updateMany'))->toBeTrue();

        $method = $reflection->getMethod('updateMany');
        expect($method->getReturnType()->getName())->toBe('int');
    });

    test('defines deleteMany method', function () {
        $reflection = new ReflectionClass(BulkRepositoryInterface::class);
        expect($reflection->hasMethod('deleteMany'))->toBeTrue();

        $method = $reflection->getMethod('deleteMany');
        expect($method->getReturnType()->getName())->toBe('int');
    });
});

describe('BulkSoftDeleteRepositoryInterface', function () {
    test('interface exists', function () {
        expect(interface_exists(BulkSoftDeleteRepositoryInterface::class))->toBeTrue();
    });

    test('defines restoreMany method', function () {
        $reflection = new ReflectionClass(BulkSoftDeleteRepositoryInterface::class);
        expect($reflection->hasMethod('restoreMany'))->toBeTrue();
    });

    test('defines forceDeleteMany method', function () {
        $reflection = new ReflectionClass(BulkSoftDeleteRepositoryInterface::class);
        expect($reflection->hasMethod('forceDeleteMany'))->toBeTrue();
    });

    test('defines findManyWithTrashed method', function () {
        $reflection = new ReflectionClass(BulkSoftDeleteRepositoryInterface::class);
        expect($reflection->hasMethod('findManyWithTrashed'))->toBeTrue();
    });
});

describe('BaseRepositoryInterface after refactor', function () {
    test('does not include bulk methods', function () {
        $reflection = new ReflectionClass(\LaravelArchitect\Base\BaseRepositoryInterface::class);

        expect($reflection->hasMethod('findMany'))->toBeFalse();
        expect($reflection->hasMethod('createMany'))->toBeFalse();
        expect($reflection->hasMethod('updateMany'))->toBeFalse();
        expect($reflection->hasMethod('deleteMany'))->toBeFalse();
    });
});
