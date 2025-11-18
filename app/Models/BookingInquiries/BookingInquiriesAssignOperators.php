<?php

namespace App\Models\BookingInquiries;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BaseModelLoggingTrait;
use App\Models\Client;

class BookingInquiriesAssignOperators extends Model
{
    use BaseModelLoggingTrait;
    protected $table = 'booking_inquiry_operator_assignments';
    protected $fillable = [
        'booking_inquiries_id', 'manager_id', 'operator_id'
    ];

    public function client()
    {
        return $this->belongsTo(client::class, 'operator_id');
    }
}
