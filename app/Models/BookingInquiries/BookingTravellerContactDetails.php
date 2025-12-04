<?php

namespace App\Models\BookingInquiries;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BaseModelLoggingTrait;

class BookingTravellerContactDetails extends Model
{
    use BaseModelLoggingTrait;
    protected $table = 'traveller_contact_details';
    protected $fillable = [
        'booking_inquiries_id', 'client_id', 'contact_name', 'contact_email', 'contact_phone', 'pincode', 'address', 'city'
    ];
}
