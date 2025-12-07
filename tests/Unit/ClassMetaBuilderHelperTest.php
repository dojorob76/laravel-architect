<?php

use LaravelArchitect\Console\Commands\Support\ClassMetaBuilderHelper;
use LaravelArchitect\Console\Commands\Support\ConfigHelper;

describe('ClassMetaBuilderHelper', function () {

    describe('build() method', function () {

        test('throws exception for unknown file type', function () {
            expect(fn () => ClassMetaBuilderHelper::build('unknown', 'Video'))
                ->toThrow(\InvalidArgumentException::class, 'Unknown file type: unknown');
        });

        test('builds model metadata', function () {
            $result = ClassMetaBuilderHelper::build('model', 'Video');

            expect($result['class_name'])->toBe('Video')
                ->and($result['file_name'])->toBe('Video.php')
                ->and($result['path'])->toBe(ConfigHelper::basePath('models') . '/Video.php')
                ->and($result['namespace'])->toBe(ConfigHelper::namespace('models'))
                ->and($result['fqcn'])->toBe(ConfigHelper::namespace('models') . '\\Video')
                ->and($result['extends'])->toBe('BaseModel')
                ->and($result['extends_import'])->toBe('LaravelArchitect\\Base\\BaseModel');
        });

        test('builds service metadata', function () {
            $result = ClassMetaBuilderHelper::build('service', 'Video');

            expect($result['class_name'])->toBe('VideoService')
                ->and($result['file_name'])->toBe('VideoService.php')
                ->and($result['path'])->toBe(ConfigHelper::basePath('services') . '/VideoService.php')
                ->and($result['namespace'])->toBe(ConfigHelper::namespace('services'))
                ->and($result['extends'])->toBe('BaseService')
                ->and($result['extends_import'])->toBe('LaravelArchitect\\Base\\BaseService');
        });

        test('builds controller metadata', function () {
            $result = ClassMetaBuilderHelper::build('controller', 'Video');

            expect($result['class_name'])->toBe('VideoController')
                ->and($result['file_name'])->toBe('VideoController.php')
                ->and($result['path'])->toBe(ConfigHelper::basePath('controllers') . '/VideoController.php')
                ->and($result['namespace'])->toBe(ConfigHelper::namespace('controllers'))
                ->and($result['extends'])->toBe('Controller')
                ->and($result['extends_import'])->toBe(ConfigHelper::namespace('base_controller') . '\\Controller');
        });

        test('builds request metadata', function () {
            $result = ClassMetaBuilderHelper::build('request', 'Video');

            expect($result['class_name'])->toBe('VideoRequest')
                ->and($result['file_name'])->toBe('VideoRequest.php')
                ->and($result['path'])->toBe(ConfigHelper::basePath('requests') . '/VideoRequest.php')
                ->and($result['namespace'])->toBe(ConfigHelper::namespace('requests'))
                ->and($result['extends'])->toBe('BaseFormRequest')
                ->and($result['extends_import'])->toBe('LaravelArchitect\\Base\\BaseFormRequest');
        });

        test('builds resource metadata', function () {
            $result = ClassMetaBuilderHelper::build('resource', 'Video');

            expect($result['class_name'])->toBe('VideoResource')
                ->and($result['file_name'])->toBe('VideoResource.php')
                ->and($result['path'])->toBe(ConfigHelper::basePath('resources') . '/VideoResource.php')
                ->and($result['namespace'])->toBe(ConfigHelper::namespace('resources'))
                ->and($result['extends'])->toBe('JsonResource')
                ->and($result['extends_import'])->toBe('Illuminate\\Http\\Resources\\Json\\JsonResource');
        });

        test('builds factory metadata', function () {
            $result = ClassMetaBuilderHelper::build('factory', 'Video');

            expect($result['class_name'])->toBe('VideoFactory')
                ->and($result['file_name'])->toBe('VideoFactory.php')
                ->and($result['path'])->toBe(ConfigHelper::basePath('factories') . '/VideoFactory.php')
                ->and($result['namespace'])->toBe(ConfigHelper::namespace('factories'))
                ->and($result['extends'])->toBe('Factory')
                ->and($result['extends_import'])->toBe('Illuminate\\Database\\Eloquent\\Factories\\Factory');
        });

        test('builds test metadata', function () {
            $result = ClassMetaBuilderHelper::build('test', 'Video');

            expect($result['class_name'])->toBe('VideoTest')
                ->and($result['file_name'])->toBe('VideoTest.php')
                ->and($result['path'])->toBe(ConfigHelper::basePath('tests') . '/VideoTest.php')
                ->and($result['namespace'])->toBeNull()
                ->and($result['extends'])->toBeNull()
                ->and($result['extends_import'])->toBeNull();
        });

    });

    describe('build() for interface', function () {

        test('builds crud interface metadata', function () {
            $result = ClassMetaBuilderHelper::build('interface', 'Video', 'crud');

            expect($result['class_name'])->toBe('VideoRepositoryInterface')
                ->and($result['file_name'])->toBe('VideoRepositoryInterface.php')
                ->and($result['path'])->toBe(ConfigHelper::basePath('contracts') . '/VideoRepositoryInterface.php')
                ->and($result['namespace'])->toBe(ConfigHelper::namespace('contracts'))
                ->and($result['extends'])->toBe('BaseRepositoryInterface')
                ->and($result['extends_import'])->toBe('LaravelArchitect\\Base\\Contracts\\BaseRepositoryInterface');
        });

        test('builds bulk interface metadata', function () {
            $result = ClassMetaBuilderHelper::build('interface', 'Video', 'bulk');

            expect($result['class_name'])->toBe('VideoBulkRepositoryInterface')
                ->and($result['extends'])->toBe('BulkRepositoryInterface')
                ->and($result['extends_import'])->toBe('LaravelArchitect\\Base\\Contracts\\BulkRepositoryInterface');
        });

        test('builds soft_delete interface metadata', function () {
            $result = ClassMetaBuilderHelper::build('interface', 'Video', 'soft_delete');

            expect($result['class_name'])->toBe('VideoSoftDeleteRepositoryInterface')
                ->and($result['extends'])->toBe('SoftDeleteRepositoryInterface')
                ->and($result['extends_import'])->toBe('LaravelArchitect\\Base\\Contracts\\SoftDeleteRepositoryInterface');
        });

        test('builds bulk_soft_delete interface metadata', function () {
            $result = ClassMetaBuilderHelper::build('interface', 'Video', 'bulk_soft_delete');

            expect($result['class_name'])->toBe('VideoBulkSoftDeleteRepositoryInterface')
                ->and($result['extends'])->toBe('BulkSoftDeleteRepositoryInterface')
                ->and($result['extends_import'])->toBe('LaravelArchitect\\Base\\Contracts\\BulkSoftDeleteRepositoryInterface');
        });

        test('defaults to crud when operationType is null', function () {
            $result = ClassMetaBuilderHelper::build('interface', 'Video');

            expect($result['class_name'])->toBe('VideoRepositoryInterface')
                ->and($result['extends'])->toBe('BaseRepositoryInterface');
        });

    });

    describe('build() for repository', function () {

        test('builds crud eloquent repository metadata', function () {
            $result = ClassMetaBuilderHelper::build('repository', 'Video', 'crud', 'eloquent');

            expect($result['class_name'])->toBe('EloquentVideoRepository')
                ->and($result['file_name'])->toBe('EloquentVideoRepository.php')
                ->and($result['path'])->toBe(ConfigHelper::basePath('repositories') . '/EloquentVideoRepository.php')
                ->and($result['namespace'])->toBe(ConfigHelper::namespace('repositories'))
                ->and($result['extends'])->toBe('BaseEloquentRepository')
                ->and($result['extends_import'])->toBe('LaravelArchitect\\Base\\BaseEloquentRepository')
                ->and($result['implements'])->toBe('VideoRepositoryInterface')
                ->and($result['implements_import'])->toBe(ConfigHelper::namespace('contracts') . '\\VideoRepositoryInterface')
                ->and($result['uses_trait'])->toBeNull()
                ->and($result['trait_import'])->toBeNull();
        });

        test('builds crud mysql repository metadata', function () {
            $result = ClassMetaBuilderHelper::build('repository', 'Video', 'crud', 'mysql');

            expect($result['class_name'])->toBe('MySQLVideoRepository')
                ->and($result['extends'])->toBe('BaseMySQLRepository')
                ->and($result['extends_import'])->toBe('LaravelArchitect\\Base\\BaseMySQLRepository');
        });

        test('builds crud postgres repository metadata', function () {
            $result = ClassMetaBuilderHelper::build('repository', 'Video', 'crud', 'postgres');

            expect($result['class_name'])->toBe('PostgresVideoRepository')
                ->and($result['extends'])->toBe('BasePostgresRepository')
                ->and($result['extends_import'])->toBe('LaravelArchitect\\Base\\BasePostgresRepository');
        });

        test('builds bulk eloquent repository metadata with trait', function () {
            $result = ClassMetaBuilderHelper::build('repository', 'Video', 'bulk', 'eloquent');

            expect($result['class_name'])->toBe('EloquentVideoBulkRepository')
                ->and($result['implements'])->toBe('VideoBulkRepositoryInterface')
                ->and($result['extends'])->toBeNull()
                ->and($result['extends_import'])->toBeNull()
                ->and($result['uses_trait'])->toBe('BulkEloquentRepository')
                ->and($result['trait_import'])->toBe('LaravelArchitect\\Base\\Traits\\BulkEloquentRepository');
        });

        test('builds soft_delete mysql repository metadata with trait', function () {
            $result = ClassMetaBuilderHelper::build('repository', 'Video', 'soft_delete', 'mysql');

            expect($result['class_name'])->toBe('MySQLVideoSoftDeleteRepository')
                ->and($result['implements'])->toBe('VideoSoftDeleteRepositoryInterface')
                ->and($result['uses_trait'])->toBe('SoftDeleteMySQLRepository')
                ->and($result['trait_import'])->toBe('LaravelArchitect\\Base\\Traits\\SoftDeleteMySQLRepository');
        });

        test('builds bulk_soft_delete postgres repository metadata with trait', function () {
            $result = ClassMetaBuilderHelper::build('repository', 'Video', 'bulk_soft_delete', 'postgres');

            expect($result['class_name'])->toBe('PostgresVideoBulkSoftDeleteRepository')
                ->and($result['implements'])->toBe('VideoBulkSoftDeleteRepositoryInterface')
                ->and($result['uses_trait'])->toBe('BulkSoftDeletePostgresRepository')
                ->and($result['trait_import'])->toBe('LaravelArchitect\\Base\\Traits\\BulkSoftDeletePostgresRepository');
        });

        test('defaults to crud and eloquent when operationType and driver are null', function () {
            $result = ClassMetaBuilderHelper::build('repository', 'Video');

            expect($result['class_name'])->toBe('EloquentVideoRepository')
                ->and($result['extends'])->toBe('BaseEloquentRepository');
        });

        test('throws exception for unknown driver', function () {
            expect(fn () => ClassMetaBuilderHelper::build('repository', 'Video', 'crud', 'sqlite'))
                ->toThrow(\InvalidArgumentException::class, 'Unknown driver: sqlite');
        });

    });

    describe('driverName() method', function () {

        test('returns Eloquent for eloquent', function () {
            expect(ClassMetaBuilderHelper::driverName('eloquent'))->toBe('Eloquent');
        });

        test('returns MySQL for mysql', function () {
            expect(ClassMetaBuilderHelper::driverName('mysql'))->toBe('MySQL');
        });

        test('returns Postgres for postgres', function () {
            expect(ClassMetaBuilderHelper::driverName('postgres'))->toBe('Postgres');
        });

        test('throws exception for unknown driver', function () {
            expect(fn () => ClassMetaBuilderHelper::driverName('sqlite'))
                ->toThrow(\InvalidArgumentException::class, 'Unknown driver: sqlite');
        });

    });

    describe('operationPrefix() method', function () {

        test('returns Base for crud', function () {
            expect(ClassMetaBuilderHelper::operationPrefix('crud'))->toBe('Base');
        });

        test('returns Bulk for bulk', function () {
            expect(ClassMetaBuilderHelper::operationPrefix('bulk'))->toBe('Bulk');
        });

        test('returns SoftDelete for soft_delete', function () {
            expect(ClassMetaBuilderHelper::operationPrefix('soft_delete'))->toBe('SoftDelete');
        });

        test('returns BulkSoftDelete for bulk_soft_delete', function () {
            expect(ClassMetaBuilderHelper::operationPrefix('bulk_soft_delete'))->toBe('BulkSoftDelete');
        });

        test('throws exception for unknown operation type', function () {
            expect(fn () => ClassMetaBuilderHelper::operationPrefix('unknown'))
                ->toThrow(\InvalidArgumentException::class, 'Unknown operation type: unknown');
        });

    });

    describe('operationTypes() method', function () {

        test('returns all valid operation types', function () {
            $types = ClassMetaBuilderHelper::operationTypes();

            expect($types)->toBe(['crud', 'bulk', 'soft_delete', 'bulk_soft_delete']);
        });

    });

    describe('drivers() method', function () {

        test('returns all valid drivers', function () {
            $drivers = ClassMetaBuilderHelper::drivers();

            expect($drivers)->toBe(['eloquent', 'mysql', 'postgres']);
        });

    });

});
