<?php

namespace App\Models\FormFieldsData;

use Illuminate\Database\Eloquent\Model;

class TravelingPurpose extends Model
{
    protected $table = 'travel_purpose_options';
    protected $fillable = ['name', 'is_active', 'order_by'];
}
