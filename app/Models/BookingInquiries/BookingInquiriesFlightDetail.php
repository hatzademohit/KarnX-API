<?php

namespace App\Models\BookingInquiries;

use Illuminate\Database\Eloquent\Model;
use App\Models\BookingInquiries\BookingInquiries;
use App\Models\BookingInquiries\FlightDetail;
use App\Models\FormFieldsData\AirportCities;

class BookingInquiriesFlightDetail extends Model
{
    protected $table = 'booking_inquiries_flight_details';
    protected $fillable = [
        'booking_inquiries_id', 'departure_location', 'arrival_location', 'departure_time', 'return_date_time'
    ];  
   
    public function flightDetail() { return $this->belongsTo(FlightDetail::class); }
    public function bookingInquiry() { return $this->belongsTo(BookingInquiries::class); }

    public function airportDepartureLocation()
    {
        return $this->belongsTo(AirportCities::class, 'departure_location');
    }

    public function airportArrivalLocation()
    {
        return $this->belongsTo(AirportCities::class, 'arrival_location');
    }

    public function getAirportLocationsAttribute()
    {
        return [
            'departure' => $this->airportDepartureLocation,
            'arrival'   => $this->airportArrivalLocation,
        ];
    }
}
