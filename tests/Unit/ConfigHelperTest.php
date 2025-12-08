<?php

declare(strict_types=1);

use LaravelArchitect\Console\Commands\Support\ConfigHelper;

describe('ConfigHelper', function () {
    describe('namespaces', function () {
        test('returns namespace for models', function () {
            expect(ConfigHelper::namespace('models'))->toBe('App\\Models');
        });

        test('returns namespace for controllers', function () {
            expect(ConfigHelper::namespace('controllers'))->toBe('App\\Http\\Controllers\\Api');
        });

        test('returns all namespaces as object', function () {
            $namespaces = ConfigHelper::namespaces();
            expect($namespaces)->toBeObject();
            expect($namespaces->models)->toBe('App\\Models');
        });
    });

    describe('paths', function () {
        test('returns path for models', function () {
            expect(ConfigHelper::path('models'))->toBe('app/Models');
        });

        test('returns base path for models', function () {
            $basePath = ConfigHelper::basePath('models');
            expect($basePath)->toEndWith('app/Models');
        });
    });

    describe('status codes', function () {
        test('returns success status code', function () {
            expect(ConfigHelper::statusCode('success'))->toBe(200);
        });

        test('returns created status code', function () {
            expect(ConfigHelper::statusCode('created'))->toBe(201);
        });

        test('throws exception for unknown key', function () {
            expect(fn() => ConfigHelper::statusCode('unknown'))
                ->toThrow(\RuntimeException::class, 'Missing required config value: api_scaffolding.status_codes.unknown');
        });
    });

    describe('messages', function () {
        test('returns created message', function () {
            expect(ConfigHelper::message('created'))->toContain('created');
        });

        test('throws exception for unknown key', function () {
            expect(fn() => ConfigHelper::message('unknown'))
                ->toThrow(\RuntimeException::class, 'Missing required config value: api_scaffolding.messages.unknown');
        });
    });

    describe('response settings', function () {
        test('returns pagination limit', function () {
            expect(ConfigHelper::paginationLimit())->toBe(15);
        });

        test('returns json key', function () {
            expect(ConfigHelper::jsonKey())->toBe('data');
        });
    });
});
