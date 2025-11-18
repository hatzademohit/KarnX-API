<?php

namespace App\Models\FormFieldsData;

use Illuminate\Database\Eloquent\Model;
use App\Models\Asset;
class AirCraftTypes extends Model
{
    protected $table = 'aircraft_types';
    protected $fillable = ['name', 'description', 'is_active', 'order_by'];

    public function assets()
    {
        return $this->hasMany(Asset::class, 'aircraft_type_id');
    }
}
