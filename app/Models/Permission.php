<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BaseModelLoggingTrait;
class Permission extends Model
{
    use BaseModelLoggingTrait;
    protected $table = 'permissions';
    protected $guard_name = 'api';
    protected $fillable = [
        'name',
        'guard_name',
        'slug',
    ];
}
