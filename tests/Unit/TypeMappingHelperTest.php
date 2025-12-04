<?php

declare(strict_types=1);

use LaravelArchitect\Console\Commands\Support\TypeMappingHelper;

describe('TypeMappingHelper', function () {
    describe('getCast', function () {
        test('returns boolean for boolean type', function () {
            expect(TypeMappingHelper::getCast('boolean'))->toBe('boolean');
        });

        test('returns integer for integer type', function () {
            expect(TypeMappingHelper::getCast('integer'))->toBe('integer');
        });

        test('returns integer for unsignedBigInteger type', function () {
            expect(TypeMappingHelper::getCast('unsignedBigInteger'))->toBe('integer');
        });

        test('returns integer for bigInteger type', function () {
            expect(TypeMappingHelper::getCast('bigInteger'))->toBe('integer');
        });

        test('returns decimal:2 for decimal type', function () {
            expect(TypeMappingHelper::getCast('decimal'))->toBe('decimal:2');
        });

        test('returns decimal:2 for float type', function () {
            expect(TypeMappingHelper::getCast('float'))->toBe('decimal:2');
        });

        test('returns decimal:2 for double type', function () {
            expect(TypeMappingHelper::getCast('double'))->toBe('decimal:2');
        });

        test('returns date for date type', function () {
            expect(TypeMappingHelper::getCast('date'))->toBe('date');
        });

        test('returns datetime for datetime type', function () {
            expect(TypeMappingHelper::getCast('datetime'))->toBe('datetime');
        });

        test('returns datetime for timestamp type', function () {
            expect(TypeMappingHelper::getCast('timestamp'))->toBe('datetime');
        });

        test('returns array for json type', function () {
            expect(TypeMappingHelper::getCast('json'))->toBe('array');
        });

        test('returns null for string type', function () {
            expect(TypeMappingHelper::getCast('string'))->toBeNull();
        });

        test('returns null for text type', function () {
            expect(TypeMappingHelper::getCast('text'))->toBeNull();
        });

        test('returns null for email type', function () {
            expect(TypeMappingHelper::getCast('email'))->toBeNull();
        });

        test('returns null for unknown type', function () {
            expect(TypeMappingHelper::getCast('unknown'))->toBeNull();
        });
    });

    describe('getSupportedTypes', function () {
        test('returns array of all supported types', function () {
            $types = TypeMappingHelper::getSupportedTypes();
            expect($types)->toBeArray();
            expect($types)->toContain('string', 'integer', 'boolean', 'json');
        });

        test('includes all expected types', function () {
            $types = TypeMappingHelper::getSupportedTypes();
            $expectedTypes = [
                'boolean', 'integer', 'unsignedBigInteger', 'bigInteger',
                'decimal', 'float', 'double', 'date', 'datetime', 'timestamp',
                'json', 'string', 'text', 'email'
            ];

            foreach ($expectedTypes as $type) {
                expect($types)->toContain($type);
            }
        });
    });

    describe('requiresCast', function () {
        test('returns true for types that need casts', function () {
            expect(TypeMappingHelper::requiresCast('boolean'))->toBeTrue();
            expect(TypeMappingHelper::requiresCast('integer'))->toBeTrue();
            expect(TypeMappingHelper::requiresCast('json'))->toBeTrue();
            expect(TypeMappingHelper::requiresCast('decimal'))->toBeTrue();
            expect(TypeMappingHelper::requiresCast('date'))->toBeTrue();
        });

        test('returns false for types that do not need casts', function () {
            expect(TypeMappingHelper::requiresCast('string'))->toBeFalse();
            expect(TypeMappingHelper::requiresCast('text'))->toBeFalse();
            expect(TypeMappingHelper::requiresCast('email'))->toBeFalse();
        });

        test('returns false for unknown types', function () {
            expect(TypeMappingHelper::requiresCast('unknown'))->toBeFalse();
        });
    });

    describe('getAllCasts', function () {
        test('returns only non-null casts', function () {
            $casts = TypeMappingHelper::getAllCasts();
            expect($casts)->not->toContain(null);
            expect($casts)->toHaveKey('boolean');
            expect($casts)->not->toHaveKey('string');
        });

        test('includes all types that require casts', function () {
            $casts = TypeMappingHelper::getAllCasts();

            // Should have these keys
            expect($casts)->toHaveKey('boolean');
            expect($casts)->toHaveKey('integer');
            expect($casts)->toHaveKey('unsignedBigInteger');
            expect($casts)->toHaveKey('bigInteger');
            expect($casts)->toHaveKey('decimal');
            expect($casts)->toHaveKey('float');
            expect($casts)->toHaveKey('double');
            expect($casts)->toHaveKey('date');
            expect($casts)->toHaveKey('datetime');
            expect($casts)->toHaveKey('timestamp');
            expect($casts)->toHaveKey('json');

            // Should NOT have these keys (they return null)
            expect($casts)->not->toHaveKey('string');
            expect($casts)->not->toHaveKey('text');
            expect($casts)->not->toHaveKey('email');
        });

        test('returns correct cast values', function () {
            $casts = TypeMappingHelper::getAllCasts();

            expect($casts['boolean'])->toBe('boolean');
            expect($casts['integer'])->toBe('integer');
            expect($casts['decimal'])->toBe('decimal:2');
            expect($casts['date'])->toBe('date');
            expect($casts['datetime'])->toBe('datetime');
            expect($casts['json'])->toBe('array');
        });
    });
});
