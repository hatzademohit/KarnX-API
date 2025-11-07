<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BaseModelLoggingTrait;

class Client extends Model
{
    use BaseModelLoggingTrait;
    protected $table = 'clients';
    protected $guard_name = 'api';
    protected $fillable = [
        'name',
        'type',
        'contact_person',
        'phone',
        'email',
        'address_line1',
        'address_line2',
        'area',
        'city',
        'state',
        'pincode',
        'country',
        'is_active',
    ];
}
