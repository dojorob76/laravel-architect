<?php

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use LaravelArchitect\Services\ValidationService;

// Mock model with validation methods including context parameter
class TestValidatableModel
{
    public static function baseRules(array $context = []): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
        ];

        // Example: composite unique using context
        if (isset($context['department_id'])) {
            $rules['floor'] = ['required', 'integer'];
        }

        return $rules;
    }

    public static function updateOverrides(?int $id = null, array $context = []): array
    {
        return [
            'email' => ['sometimes', 'email', "unique:users,email,{$id}"],
        ];
    }

    public static function validationMessages(): array
    {
        return [];
    }
}

describe('ValidationService', function () {
    beforeEach(function () {
        $this->service = new ValidationService();
    });

    describe('validateForCreate', function () {
        test('validates valid data successfully', function () {
            $data = ['name' => 'John', 'email' => 'john@example.com'];

            $validated = $this->service->validateForCreate(
                TestValidatableModel::class,
                $data
            );

            expect($validated)->toBe($data);
        });

        test('throws ValidationException for invalid data', function () {
            $data = ['name' => '', 'email' => 'invalid'];

            expect(fn() => $this->service->validateForCreate(
                TestValidatableModel::class,
                $data
            ))->toThrow(ValidationException::class);
        });

        test('passes context to baseRules', function () {
            $data = ['name' => 'John', 'email' => 'john@example.com', 'floor' => 5];
            $context = ['department_id' => 1];

            $validated = $this->service->validateForCreate(
                TestValidatableModel::class,
                $data,
                $context
            );

            expect($validated)->toHaveKey('floor');
        });
    });

    describe('validateForUpdate', function () {
        test('allows partial updates', function () {
            $data = ['name' => 'Updated Name'];

            $validated = $this->service->validateForUpdate(
                TestValidatableModel::class,
                $data,
                1
            );

            expect($validated)->toHaveKey('name');
        });

        test('passes context to updateOverrides', function () {
            $data = ['name' => 'Updated Name'];
            $context = ['department_id' => 1];

            $validated = $this->service->validateForUpdate(
                TestValidatableModel::class,
                $data,
                1,
                $context
            );

            expect($validated)->toHaveKey('name');
        });
    });

    describe('validateBatchCreate', function () {
        test('validates all records', function () {
            $records = [
                ['name' => 'User 1', 'email' => 'user1@example.com'],
                ['name' => 'User 2', 'email' => 'user2@example.com'],
            ];

            $validated = $this->service->validateBatchCreate(
                TestValidatableModel::class,
                $records
            );

            expect($validated)->toHaveKey('records');
            expect($validated['records'])->toHaveCount(2);
        });

        test('throws with indexed errors for invalid records', function () {
            $records = [
                ['name' => 'Valid', 'email' => 'valid@example.com'],
                ['name' => '', 'email' => 'invalid'],
            ];

            try {
                $this->service->validateBatchCreate(
                    TestValidatableModel::class,
                    $records
                );
                $this->fail('Expected ValidationException');
            } catch (ValidationException $e) {
                $errors = $e->errors();
                expect(array_keys($errors)[0])->toContain('records.1');
            }
        });
    });
});
