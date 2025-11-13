<?php

namespace App\Models\FormFieldsData;

use Illuminate\Database\Eloquent\Model;

class CrewRequirements extends Model
{
    protected $table = 'inquiry_crew_requirements_options';
    protected $fillable = ['name', 'is_active', 'order_by'];
}
