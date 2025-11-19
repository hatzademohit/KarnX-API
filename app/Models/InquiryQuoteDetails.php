<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BaseModelLoggingTrait;

class InquiryQuoteDetails extends Model
{
    use BaseModelLoggingTrait;
    protected $table = 'inquiry_quote_details';
    protected $fillable = [
        'booking_inquiries_id',
        'client_id',
        'aircraft_id',
        'estimated_flight_time',
        'base_fare',
        'fluel_cost',
        'taxes_fees',
        'crew_fees',
        'handling_fees',
        'catering_fees',
        'total',
        'validate_till',
        'cancellation_policy_id',
        'special_offers_promotions',
        'additional_notes',
        'amenities_ids',
    ];
}
