<?php

namespace App\Models\BookingInquiries;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BaseModelLoggingTrait;

class BookingTravellerDetails extends Model
{
    use BaseModelLoggingTrait;
    protected $table = 'traveller_persons_details';
    protected $fillable = [
        'booking_inquiries_id', 'client_id', 'quote_id', 'user_id', 'name', 'age'
    ];
}
