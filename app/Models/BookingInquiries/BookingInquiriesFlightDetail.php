<?php

namespace App\Models\BookingInquiries;

use Illuminate\Database\Eloquent\Model;
use App\Models\BookingInquiries\BookingInquiries;
use App\Models\BookingInquiries\FlightDetail;

class BookingInquiriesFlightDetail extends Model
{
    protected $table = 'booking_inquiries_flight_details';
    protected $fillable = [
        'booking_inquiries_id', 'departure_location', 'arrival_location', 'departure_time', 'return_date_time'
    ];  

    public function flightDetail() { return $this->belongsTo(FlightDetail::class); }
    public function bookingInquiry() { return $this->belongsTo(BookingInquiries::class); }
}
