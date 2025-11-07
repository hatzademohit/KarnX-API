<?php

namespace App\Models\BookingInquiries;

use Illuminate\Database\Eloquent\Model;
use App\Models\BookingInquiries\BookingInquiries;
use App\Models\BookingInquiries\PassengersInformation;

class BookingInquiriesPassengersInformation extends Model
{
    protected $table = 'booking_inquiries_passengers_information';
    protected $fillable = [
        'booking_inquiry_id', 'adults', 'children', 'total_passengers'
    ];

    public function passengersInformation() { return $this->belongsTo(PassengersInformation::class); }
    public function bookingInquiry() { return $this->belongsTo(BookingInquiries::class); }
}
