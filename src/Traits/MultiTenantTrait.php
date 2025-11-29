<?php

namespace LaravelArchitect\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait MultiTenantTrait
{
    protected static function bootMultiTenantTrait(): void
    {
        static::creating(function (Model $model) {
            if (empty($model->tenant_id) && auth()->check()) {
                $model->tenant_id = auth()->user()->tenant_id ?? null;
            }
        });

        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check() && auth()->user()->tenant_id) {
                $builder->where('tenant_id', auth()->user()->tenant_id);
            }
        });
    }

    public function scopeAllTenants(Builder $query): Builder
    {
        return $query->withoutGlobalScope('tenant');
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->withoutGlobalScope('tenant')->where('tenant_id', $tenantId);
    }
}
