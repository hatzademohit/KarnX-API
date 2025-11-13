<?php

namespace App\Models\BookingInquiries;

use Illuminate\Database\Eloquent\Model;
use App\Models\BookingInquiries\BookingInquiries;
use App\Models\BookingInquiries\AircraftPreference;

class BookingInquiriesAircraftPreference extends Model
{
    protected $table = 'booking_inquiries_aircraft_preferences';
    protected $fillable = [
        'booking_inquiry_id', 'aircraft_type_id'
    ];

    public function aircraftPreference() { return $this->belongsTo(AircraftPreference::class); }
    public function bookingInquiry() { return $this->belongsTo(BookingInquiries::class); }
}
