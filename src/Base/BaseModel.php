<?php

namespace LaravelArchitect\Base;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

abstract class BaseModel extends Model
{
    use HasFactory;

    protected $dateFormat = 'Y-m-d H:i:s';

    protected static function boot(): void
    {
        parent::boot();
    }
}
