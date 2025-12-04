<?php

declare(strict_types=1);

use LaravelArchitect\Console\Commands\Support\NamingConventionHelper;

describe('NamingConventionHelper', function () {
    describe('modelToTableName', function () {
        test('converts simple model to plural snake_case', function () {
            expect(NamingConventionHelper::modelToTableName('User'))->toBe('users');
            expect(NamingConventionHelper::modelToTableName('Video'))->toBe('videos');
        });

        test('converts compound model name', function () {
            expect(NamingConventionHelper::modelToTableName('BlogPost'))->toBe('blog_posts');
            expect(NamingConventionHelper::modelToTableName('UserProfile'))->toBe('user_profiles');
        });
    });

    describe('modelToForeignKey', function () {
        test('converts simple model to foreign key', function () {
            expect(NamingConventionHelper::modelToForeignKey('User'))->toBe('user_id');
        });

        test('converts compound model to foreign key', function () {
            expect(NamingConventionHelper::modelToForeignKey('BlogPost'))->toBe('blog_post_id');
        });
    });

    describe('foreignKeyToModel', function () {
        test('converts simple foreign key to model', function () {
            expect(NamingConventionHelper::foreignKeyToModel('user_id'))->toBe('User');
        });

        test('converts compound foreign key to model', function () {
            expect(NamingConventionHelper::foreignKeyToModel('blog_post_id'))->toBe('BlogPost');
        });
    });

    describe('foreignKeyToEntityName', function () {
        test('extracts entity name from foreign key', function () {
            expect(NamingConventionHelper::foreignKeyToEntityName('user_id'))->toBe('user');
            expect(NamingConventionHelper::foreignKeyToEntityName('blog_post_id'))->toBe('blog_post');
        });
    });

    describe('foreignKeyToRelationName', function () {
        test('converts to camelCase relation name', function () {
            expect(NamingConventionHelper::foreignKeyToRelationName('user_id'))->toBe('user');
            expect(NamingConventionHelper::foreignKeyToRelationName('blog_post_id'))->toBe('blogPost');
        });
    });

    describe('generatePivotTableName', function () {
        test('generates alphabetically ordered pivot name', function () {
            expect(NamingConventionHelper::generatePivotTableName('Post', 'Tag'))->toBe('post_tag');
            expect(NamingConventionHelper::generatePivotTableName('Tag', 'Post'))->toBe('post_tag');
        });

        test('handles compound model names', function () {
            expect(NamingConventionHelper::generatePivotTableName('BlogPost', 'Category'))->toBe('blog_post_category');
        });

        test('uses singular forms', function () {
            expect(NamingConventionHelper::generatePivotTableName('Users', 'Roles'))->toBe('role_user');
        });
    });

    describe('modelToHasManyMethod', function () {
        test('converts to plural camelCase', function () {
            expect(NamingConventionHelper::modelToHasManyMethod('Comment'))->toBe('comments');
            expect(NamingConventionHelper::modelToHasManyMethod('BlogPost'))->toBe('blogPosts');
        });
    });

    describe('modelToBelongsToMethod', function () {
        test('converts to singular camelCase', function () {
            expect(NamingConventionHelper::modelToBelongsToMethod('User'))->toBe('user');
            expect(NamingConventionHelper::modelToBelongsToMethod('BlogPost'))->toBe('blogPost');
        });
    });

    describe('isValidModelName', function () {
        test('accepts valid StudlyCase singular names', function () {
            expect(NamingConventionHelper::isValidModelName('User'))->toBeTrue();
            expect(NamingConventionHelper::isValidModelName('BlogPost'))->toBeTrue();
            expect(NamingConventionHelper::isValidModelName('Category'))->toBeTrue();
        });

        test('rejects plural names', function () {
            expect(NamingConventionHelper::isValidModelName('Users'))->toBeFalse();
            expect(NamingConventionHelper::isValidModelName('Categories'))->toBeFalse();
        });

        test('rejects lowercase start', function () {
            expect(NamingConventionHelper::isValidModelName('user'))->toBeFalse();
        });

        test('rejects snake_case', function () {
            expect(NamingConventionHelper::isValidModelName('blog_post'))->toBeFalse();
        });
    });

    describe('isForeignKeyColumn', function () {
        test('returns true for _id suffix', function () {
            expect(NamingConventionHelper::isForeignKeyColumn('user_id'))->toBeTrue();
            expect(NamingConventionHelper::isForeignKeyColumn('blog_post_id'))->toBeTrue();
        });

        test('returns false for non-FK columns', function () {
            expect(NamingConventionHelper::isForeignKeyColumn('name'))->toBeFalse();
            expect(NamingConventionHelper::isForeignKeyColumn('id'))->toBeFalse();
        });
    });

    describe('tableToModel', function () {
        test('converts table name to model', function () {
            expect(NamingConventionHelper::tableToModel('users'))->toBe('User');
            expect(NamingConventionHelper::tableToModel('blog_posts'))->toBe('BlogPost');
        });
    });

    describe('isValidPivotTableName', function () {
        test('accepts alphabetically ordered names', function () {
            expect(NamingConventionHelper::isValidPivotTableName('post_tag'))->toBeTrue();
            expect(NamingConventionHelper::isValidPivotTableName('role_user'))->toBeTrue();
        });

        test('rejects non-alphabetical order', function () {
            expect(NamingConventionHelper::isValidPivotTableName('tag_post'))->toBeFalse();
            expect(NamingConventionHelper::isValidPivotTableName('user_role'))->toBeFalse();
        });

        test('rejects non-pivot format', function () {
            expect(NamingConventionHelper::isValidPivotTableName('users'))->toBeFalse();
            expect(NamingConventionHelper::isValidPivotTableName('a_b_c'))->toBeFalse();
        });
    });

    describe('modelToClassName', function () {
        test('converts to StudlyCase', function () {
            expect(NamingConventionHelper::modelToClassName('user'))->toBe('User');
            expect(NamingConventionHelper::modelToClassName('blog_post'))->toBe('BlogPost');
            expect(NamingConventionHelper::modelToClassName('blogPost'))->toBe('BlogPost');
        });
    });

    describe('modelToRouteName', function () {
        test('converts to plural snake_case', function () {
            expect(NamingConventionHelper::modelToRouteName('User'))->toBe('users');
            expect(NamingConventionHelper::modelToRouteName('BlogPost'))->toBe('blog_posts');
        });
    });

    describe('modelToRouteParameter', function () {
        test('converts to singular camelCase', function () {
            expect(NamingConventionHelper::modelToRouteParameter('User'))->toBe('user');
            expect(NamingConventionHelper::modelToRouteParameter('BlogPost'))->toBe('blogPost');
        });
    });
});
