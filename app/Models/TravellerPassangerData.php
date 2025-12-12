<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TravellerPassangerData extends Model
{
    protected $table = 'traveller_passanger_data';
    protected $primaryKey = 'id';
    protected $fillable = ['client_id', 'user_id', 'name', 'age'];
}
