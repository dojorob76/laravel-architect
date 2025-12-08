<?php

namespace LaravelArchitect\Base;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

abstract class BaseModel extends Model
{
    use HasFactory;

    protected static function boot(): void
    {
        parent::boot();
    }
}
