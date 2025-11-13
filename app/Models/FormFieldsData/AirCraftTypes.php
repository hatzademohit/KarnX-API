<?php

namespace App\Models\FormFieldsData;

use Illuminate\Database\Eloquent\Model;

class AirCraftTypes extends Model
{
    protected $table = 'aircraft_types';
    protected $fillable = ['name', 'description', 'is_active', 'order_by'];
}
