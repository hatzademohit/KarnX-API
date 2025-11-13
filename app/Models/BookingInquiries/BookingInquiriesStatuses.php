<?php

namespace App\Models\BookingInquiries;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BaseModelLoggingTrait;

class BookingInquiriesStatuses extends Model
{
    use BaseModelLoggingTrait;
    protected $table = 'booking_inquiry_process_statuses';
    protected $fillable = [
        'booking_inquiries_id', 'user_client_id', 'status_id'
    ];
}
