<?php

namespace App\Models\FormFieldsData;

use Illuminate\Database\Eloquent\Model;

class CateringDietary extends Model
{
    protected $table = 'catering_dietary_options';
    protected $fillable = ['name', 'is_active', 'order_by'];
}
