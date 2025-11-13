<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BaseModelLoggingTrait;

class Asset extends Model
{
    use BaseModelLoggingTrait;
    protected $fillable = [
        'client_id',
        'asset_name',
        'asset_type',
        'aircraft_model',
        'aircraft_type',
        'registration_no',
        'capacity',
        'cabin_size',
        'images',
        'status',
        'details'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
