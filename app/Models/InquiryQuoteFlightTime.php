<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use App\Traits\BaseModelLoggingTrait;

class InquiryQuoteFlightTime extends Model 
{
    use BaseModelLoggingTrait;
    protected $table = 'inquiry_quote_flight_time';
    protected $fillable = [
        'booking_inquiries_id',
        'quote_id',
        'booking_inquiries_flight_location_id',
        'departure_date_time',
        'flight_duration',
    ];
}