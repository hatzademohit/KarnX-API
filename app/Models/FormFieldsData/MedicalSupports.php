<?php

namespace App\Models\FormFieldsData;

use Illuminate\Database\Eloquent\Model;

class MedicalSupports extends Model
{
    protected $table = 'medical_assistance_needed_option';

    protected $fillable = [
        'id',
        'name',
        'is_active',
        'order_by'
    ];
}
