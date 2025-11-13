<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BaseModelLoggingTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role as SpatieRole;
class Role extends SpatieRole
{
    use SoftDeletes;
    use BaseModelLoggingTrait;
    protected $guard_name = 'api';
    protected $table = 'roles';
    protected $fillable = [
        'name',
        'description',
        'client_id',
    ];
    protected $dates = ['deleted_at'];
}
