<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BaseModelLoggingTrait;
class SLAPolicies extends Model
{
    /** @use HasFactory<\Database\Factories\SLAPoliciesFactory> */
    use BaseModelLoggingTrait;
    protected $guard_name = 'api';
    protected $table = 'sla_policies';
}