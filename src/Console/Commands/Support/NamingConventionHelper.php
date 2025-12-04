<?php

declare(strict_types=1);

namespace LaravelArchitect\Console\Commands\Support;

use Illuminate\Support\Str;

/**
 * Centralized naming convention helper for model/table/column transformations.
 *
 * Follows Laravel conventions:
 * - Models: Singular, StudlyCase (User, BlogPost)
 * - Tables: Plural, snake_case (users, blog_posts)
 * - Foreign Keys: {singular_table}_id (user_id, blog_post_id)
 * - Pivot Tables: Alphabetical singular (post_tag, not tag_post)
 * - BelongsTo: Singular (user, blogPost)
 * - HasMany: Plural (users, blogPosts)
 */
class NamingConventionHelper
{
    /**
     * Convert model name to table name
     * User -> users, BlogPost -> blog_posts
     */
    public static function modelToTableName(string $modelName): string
    {
        return Str::snake(Str::plural($modelName));
    }

    /**
     * Convert model name to foreign key column
     * User -> user_id, BlogPost -> blog_post_id
     */
    public static function modelToForeignKey(string $modelName): string
    {
        return Str::snake($modelName) . '_id';
    }

    /**
     * Convert foreign key column to model name
     * user_id -> User, blog_post_id -> BlogPost
     */
    public static function foreignKeyToModel(string $fkColumn): string
    {
        $entityName = self::foreignKeyToEntityName($fkColumn);
        return Str::studly($entityName);
    }

    /**
     * Extract entity name from foreign key (without _id)
     * user_id -> user, blog_post_id -> blog_post
     */
    public static function foreignKeyToEntityName(string $fkColumn): string
    {
        return Str::beforeLast($fkColumn, '_id');
    }

    /**
     * Convert foreign key to BelongsTo relation name
     * user_id -> user, blog_post_id -> blogPost
     */
    public static function foreignKeyToRelationName(string $fkColumn): string
    {
        $entityName = self::foreignKeyToEntityName($fkColumn);
        return Str::camel($entityName);
    }

    /**
     * Generate pivot table name from two models (alphabetical order)
     * Post, Tag -> post_tag
     * User, Role -> role_user
     */
    public static function generatePivotTableName(string $model1, string $model2): string
    {
        $names = [
            Str::snake(Str::singular($model1)),
            Str::snake(Str::singular($model2)),
        ];
        sort($names);
        return implode('_', $names);
    }

    /**
     * Convert model name to HasMany relation method name
     * Comment -> comments, BlogPost -> blogPosts
     */
    public static function modelToHasManyMethod(string $modelName): string
    {
        return Str::camel(Str::plural($modelName));
    }

    /**
     * Convert model name to BelongsTo relation method name
     * User -> user, BlogPost -> blogPost
     */
    public static function modelToBelongsToMethod(string $modelName): string
    {
        return Str::camel($modelName);
    }

    /**
     * Convert model name to BelongsToMany relation method name
     * Tag -> tags, Category -> categories
     */
    public static function modelToBelongsToManyMethod(string $modelName): string
    {
        return Str::camel(Str::plural($modelName));
    }

    /**
     * Validate model name follows conventions
     * Must be StudlyCase, singular, start with uppercase
     */
    public static function isValidModelName(string $name): bool
    {
        // Must start with uppercase, be alphanumeric
        if (preg_match('/^[A-Z][a-zA-Z0-9]*$/', $name) !== 1) {
            return false;
        }

        // Must be singular
        return Str::singular($name) === $name;
    }

    /**
     * Check if a column name is a foreign key
     */
    public static function isForeignKeyColumn(string $columnName): bool
    {
        return str_ends_with($columnName, '_id');
    }

    /**
     * Convert table name to model name
     * users -> User, blog_posts -> BlogPost
     */
    public static function tableToModel(string $tableName): string
    {
        return Str::studly(Str::singular($tableName));
    }

    /**
     * Get singular snake_case form of model
     * BlogPost -> blog_post
     */
    public static function modelToSingularSnake(string $modelName): string
    {
        return Str::snake($modelName);
    }

    /**
     * Convert model name to class name (ensures StudlyCase)
     * blogPost -> BlogPost, blog_post -> BlogPost
     */
    public static function modelToClassName(string $name): string
    {
        return Str::studly($name);
    }

    /**
     * Convert model name to route name (plural snake_case)
     * User -> users, BlogPost -> blog_posts
     */
    public static function modelToRouteName(string $modelName): string
    {
        return Str::snake(Str::plural($modelName));
    }

    /**
     * Convert model name to route parameter (singular camelCase)
     * User -> user, BlogPost -> blogPost
     */
    public static function modelToRouteParameter(string $modelName): string
    {
        return Str::camel($modelName);
    }

    /**
     * Validate pivot table name is alphabetically ordered
     */
    public static function isValidPivotTableName(string $tableName): bool
    {
        $parts = explode('_', $tableName);
        if (count($parts) !== 2) {
            return false;
        }

        $sorted = $parts;
        sort($sorted);

        return $parts === $sorted;
    }
}
