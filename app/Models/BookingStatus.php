<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BaseModelLoggingTrait;

class BookingStatus extends Model
{
    use BaseModelLoggingTrait;
    protected $table = 'booking_status'; // explicitly set table name

    protected $fillable = [
        'status_name',
        'description',
        'notification_template',
        'color_code',
        'is_active'
    ];
}
