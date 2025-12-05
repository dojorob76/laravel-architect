<?php

declare(strict_types=1);

namespace LaravelArchitect\Base\Traits;

/**
 * Shared helper for building PostgreSQL positional placeholders ($1, $2, $3, ...).
 *
 * Use this trait in any PostgreSQL repository trait that needs to build
 * parameterized queries with multiple placeholders.
 */
trait PostgresPlaceholderHelper
{
    /**
     * Build PostgreSQL positional placeholders ($1, $2, $3, ...)
     */
    protected function buildPositionalPlaceholders(int $count, int $startAt = 1): string
    {
        $placeholders = [];
        for ($i = $startAt; $i < $startAt + $count; $i++) {
            $placeholders[] = '$' . $i;
        }
        return implode(', ', $placeholders);
    }
}
