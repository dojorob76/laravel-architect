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

    describe('getValidationRule', function () {
        test('returns string for string type', function () {
            expect(TypeMappingHelper::getValidationRule('string'))->toBe('string');
        });

        test('returns string for text type', function () {
            expect(TypeMappingHelper::getValidationRule('text'))->toBe('string');
        });

        test('returns email for email type', function () {
            expect(TypeMappingHelper::getValidationRule('email'))->toBe('email');
        });

        test('returns integer for integer type', function () {
            expect(TypeMappingHelper::getValidationRule('integer'))->toBe('integer');
        });

        test('returns integer for unsignedBigInteger type', function () {
            expect(TypeMappingHelper::getValidationRule('unsignedBigInteger'))->toBe('integer');
        });

        test('returns integer for bigInteger type', function () {
            expect(TypeMappingHelper::getValidationRule('bigInteger'))->toBe('integer');
        });

        test('returns boolean for boolean type', function () {
            expect(TypeMappingHelper::getValidationRule('boolean'))->toBe('boolean');
        });

        test('returns numeric for decimal type', function () {
            expect(TypeMappingHelper::getValidationRule('decimal'))->toBe('numeric');
        });

        test('returns numeric for float type', function () {
            expect(TypeMappingHelper::getValidationRule('float'))->toBe('numeric');
        });

        test('returns numeric for double type', function () {
            expect(TypeMappingHelper::getValidationRule('double'))->toBe('numeric');
        });

        test('returns date for date type', function () {
            expect(TypeMappingHelper::getValidationRule('date'))->toBe('date');
        });

        test('returns date for datetime type', function () {
            expect(TypeMappingHelper::getValidationRule('datetime'))->toBe('date');
        });

        test('returns date for timestamp type', function () {
            expect(TypeMappingHelper::getValidationRule('timestamp'))->toBe('date');
        });

        test('returns array for json type', function () {
            expect(TypeMappingHelper::getValidationRule('json'))->toBe('array');
        });

        test('returns string as default for unknown type', function () {
            expect(TypeMappingHelper::getValidationRule('unknown'))->toBe('string');
        });
    });

    describe('buildValidationRules', function () {
        test('builds required rules for required field', function () {
            $field = ['name' => 'title', 'type' => 'string', 'required' => true];
            $rules = TypeMappingHelper::buildValidationRules($field);

            expect($rules)->toContain('required');
            expect($rules)->toContain('string');
            expect($rules)->not->toContain('nullable');
        });

        test('builds nullable rules for optional field', function () {
            $field = ['name' => 'description', 'type' => 'text'];
            $rules = TypeMappingHelper::buildValidationRules($field);

            expect($rules)->toContain('nullable');
            expect($rules)->toContain('string');
            expect($rules)->not->toContain('required');
        });

        test('uses correct type rule', function () {
            $field = ['name' => 'email', 'type' => 'email', 'required' => true];
            $rules = TypeMappingHelper::buildValidationRules($field);

            expect($rules)->toContain('email');
        });

        test('defaults to string type when type is missing', function () {
            $field = ['name' => 'field'];
            $rules = TypeMappingHelper::buildValidationRules($field);

            expect($rules)->toContain('string');
        });
    });

    describe('getAllValidationRules', function () {
        test('returns all validation mappings', function () {
            $rules = TypeMappingHelper::getAllValidationRules();

            expect($rules)->toBeArray();
            expect($rules)->toHaveKey('string');
            expect($rules)->toHaveKey('integer');
            expect($rules)->toHaveKey('boolean');
            expect($rules)->toHaveKey('email');
        });

        test('includes correct validation rules', function () {
            $rules = TypeMappingHelper::getAllValidationRules();

            expect($rules['string'])->toBe('string');
            expect($rules['integer'])->toBe('integer');
            expect($rules['email'])->toBe('email');
            expect($rules['json'])->toBe('array');
        });
    });

    describe('getFakerMethod', function () {
        test('returns word for string type', function () {
            expect(TypeMappingHelper::getFakerMethod('string'))->toBe('fake()->word()');
        });

        test('returns paragraph for text type', function () {
            expect(TypeMappingHelper::getFakerMethod('text'))->toBe('fake()->paragraph()');
        });

        test('returns safeEmail for email type', function () {
            expect(TypeMappingHelper::getFakerMethod('email'))->toBe('fake()->safeEmail()');
        });

        test('returns numberBetween for integer type', function () {
            expect(TypeMappingHelper::getFakerMethod('integer'))->toBe('fake()->numberBetween(1, 1000)');
        });

        test('returns numberBetween for unsignedBigInteger type', function () {
            expect(TypeMappingHelper::getFakerMethod('unsignedBigInteger'))->toBe('fake()->numberBetween(1, 1000)');
        });

        test('returns boolean for boolean type', function () {
            expect(TypeMappingHelper::getFakerMethod('boolean'))->toBe('fake()->boolean()');
        });

        test('returns randomFloat for decimal type', function () {
            expect(TypeMappingHelper::getFakerMethod('decimal'))->toBe('fake()->randomFloat(2, 0, 1000)');
        });

        test('returns randomFloat for float type', function () {
            expect(TypeMappingHelper::getFakerMethod('float'))->toBe('fake()->randomFloat(2, 0, 1000)');
        });

        test('returns date for date type', function () {
            expect(TypeMappingHelper::getFakerMethod('date'))->toBe('fake()->date()');
        });

        test('returns dateTime for datetime type', function () {
            expect(TypeMappingHelper::getFakerMethod('datetime'))->toBe('fake()->dateTime()');
        });

        test('returns dateTime for timestamp type', function () {
            expect(TypeMappingHelper::getFakerMethod('timestamp'))->toBe('fake()->dateTime()');
        });

        test('returns array for json type', function () {
            expect(TypeMappingHelper::getFakerMethod('json'))->toContain('fake()->word()');
        });

        test('returns word for unknown type', function () {
            expect(TypeMappingHelper::getFakerMethod('unknown'))->toBe('fake()->word()');
        });
    });

    describe('getSemanticFakerMethod', function () {
        test('returns safeEmail for email column', function () {
            expect(TypeMappingHelper::getSemanticFakerMethod('user_email'))->toBe('fake()->safeEmail()');
        });

        test('returns phoneNumber for phone column', function () {
            expect(TypeMappingHelper::getSemanticFakerMethod('phone_number'))->toBe('fake()->phoneNumber()');
        });

        test('returns words for name column', function () {
            expect(TypeMappingHelper::getSemanticFakerMethod('first_name'))->toBe('fake()->words(3, true)');
        });

        test('returns words for title column', function () {
            expect(TypeMappingHelper::getSemanticFakerMethod('post_title'))->toBe('fake()->words(3, true)');
        });

        test('returns paragraph for description column', function () {
            expect(TypeMappingHelper::getSemanticFakerMethod('description'))->toBe('fake()->paragraph()');
        });

        test('returns url for url column', function () {
            expect(TypeMappingHelper::getSemanticFakerMethod('website_url'))->toBe('fake()->url()');
        });

        test('returns address for address column', function () {
            expect(TypeMappingHelper::getSemanticFakerMethod('home_address'))->toBe('fake()->address()');
        });

        test('returns city for city column', function () {
            expect(TypeMappingHelper::getSemanticFakerMethod('city'))->toBe('fake()->city()');
        });

        test('returns country for country column', function () {
            expect(TypeMappingHelper::getSemanticFakerMethod('country'))->toBe('fake()->country()');
        });

        test('returns postcode for zip column', function () {
            expect(TypeMappingHelper::getSemanticFakerMethod('zip_code'))->toBe('fake()->postcode()');
        });

        test('returns null for non-semantic column', function () {
            expect(TypeMappingHelper::getSemanticFakerMethod('quantity'))->toBeNull();
        });

        test('excludes filename from name matching', function () {
            expect(TypeMappingHelper::getSemanticFakerMethod('filename'))->toBeNull();
        });

        test('excludes classname from name matching', function () {
            expect(TypeMappingHelper::getSemanticFakerMethod('classname'))->toBeNull();
        });

        test('excludes tablename from name matching', function () {
            expect(TypeMappingHelper::getSemanticFakerMethod('tablename'))->toBeNull();
        });

        test('excludes pathname from name matching', function () {
            expect(TypeMappingHelper::getSemanticFakerMethod('pathname'))->toBeNull();
        });

        test('still matches name when not excluded', function () {
            expect(TypeMappingHelper::getSemanticFakerMethod('person_name'))->toBe('fake()->words(3, true)');
        });
    });

    describe('getBestFakerMethod', function () {
        test('prefers semantic match over type', function () {
            // Column named 'email' with type 'string' should get email faker
            expect(TypeMappingHelper::getBestFakerMethod('string', 'email'))->toBe('fake()->safeEmail()');
        });

        test('prefers semantic match for phone over string type', function () {
            expect(TypeMappingHelper::getBestFakerMethod('string', 'phone_number'))->toBe('fake()->phoneNumber()');
        });

        test('falls back to type when no semantic match', function () {
            expect(TypeMappingHelper::getBestFakerMethod('integer', 'quantity'))->toBe('fake()->numberBetween(1, 1000)');
        });

        test('uses type when column name is null', function () {
            expect(TypeMappingHelper::getBestFakerMethod('boolean', null))->toBe('fake()->boolean()');
        });

        test('uses semantic match for description', function () {
            expect(TypeMappingHelper::getBestFakerMethod('text', 'description'))->toBe('fake()->paragraph()');
        });
    });

    describe('getSemanticPatterns', function () {
        test('returns array of semantic patterns', function () {
            $patterns = TypeMappingHelper::getSemanticPatterns();

            expect($patterns)->toBeArray();
            expect($patterns)->toContain('email', 'phone', 'address', 'city');
        });
    });

    describe('getAllFakerMethods', function () {
        test('returns all faker mappings', function () {
            $fakers = TypeMappingHelper::getAllFakerMethods();

            expect($fakers)->toBeArray();
            expect($fakers)->toHaveKey('string');
            expect($fakers)->toHaveKey('integer');
            expect($fakers)->toHaveKey('boolean');
        });

        test('includes correct faker methods', function () {
            $fakers = TypeMappingHelper::getAllFakerMethods();

            expect($fakers['string'])->toBe('fake()->word()');
            expect($fakers['email'])->toBe('fake()->safeEmail()');
            expect($fakers['boolean'])->toBe('fake()->boolean()');
        });
    });

    describe('getRequiredImports', function () {
        test('returns Str import for token faker', function () {
            $imports = TypeMappingHelper::getRequiredImports('Str::random(60)');

            expect($imports)->toBeArray();
            expect($imports)->toContain('Illuminate\Support\Str');
        });

        test('returns empty array for standard faker methods', function () {
            $imports = TypeMappingHelper::getRequiredImports('fake()->word()');

            expect($imports)->toBeArray();
            expect($imports)->toBeEmpty();
        });

        test('returns empty array for fake methods', function () {
            $imports = TypeMappingHelper::getRequiredImports('fake()->email()');

            expect($imports)->toBeArray();
            expect($imports)->toBeEmpty();
        });
    });
});
