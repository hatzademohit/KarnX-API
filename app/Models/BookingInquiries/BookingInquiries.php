<?php

namespace App\Models\BookingInquiries;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BaseModelLoggingTrait;
use App\Models\BookingInquiries\BookingInquiriesAssignOperators;
use App\Models\InquiryQuoteDetails;

class BookingInquiries extends Model
{
    use BaseModelLoggingTrait;
    protected $table = 'booking_inquiries'; // explicitly set table name
    protected $fillable = [
        'client_id', 'requester_id', 'booking_reference',
        'booking_date', 'status_id', 'remarks', 'is_confirmed', 'trip_type',
        'is_flexible_dates', 'flexible_range', 'passanger_info_adults',
        'passanger_info_children', 'passanger_info_infants', 'passenger_info_total', 'is_traveling_pets',
        'is_medical_assistance_req', 'checked_bag', 'carry_bag', 'oversized_item', 'travel_purpose_id', 'other_travel_purpose', 'is_catering_service_req'
    ];

    public function petTravels() { return $this->hasMany(BookingInquiriesPetTravel::class); }
    public function medicalAssistance() { return $this->hasOne(BookingInquiriesMedicalAssistance::class); }
    public function aircraftPreference() { return $this->hasOne(BookingInquiriesAircraftPreference::class); }
    public function crewRequirements() { return $this->hasOne(BookingInquiriesCrewRequirement::class); }

    public function cateringServices() { return $this->hasOne(BookingInquiriesCateringService::class); }
    public function contactInformation() { return $this->hasOne(BookingInquiriesContactInformation::class); }
    
    public function documents() { return $this->hasMany(BookingInquiriesDocument::class); }
    
    public function flightDetails() { return $this->hasOne(BookingInquiriesFlightDetail::class); }
    //public function passengersInformation() { return $this->hasOne(BookingInquiriesPassengersInformation::class); }
    public function assigndOperators(){
        return $this->hasMany(BookingInquiriesAssignOperators::class, 'booking_inquiries_id');
    }

    public function assignedQuotes(){
        return $this->hasMany(InquiryQuoteDetails::class, 'booking_inquiries_id');
    }

    public function scopeWithRelations($query)
    {
        return $query->with([
            'petTravels',
            'medicalAssistance',
            'aircraftPreference',
            'crewRequirements',
            'cateringServices',            
            'contactInformation',                     
            'documents',
            'flightDetails',
            'assigndOperators',
            'assignedQuotes'
            /*'passengersInformation'*/
            
        ]);
    }

    public function loadRelations()
    {
        return $this->load([
            'petTravels',
            'medicalAssistance',
            'aircraftPreference',
            'crewRequirements',
            'cateringServices',
            'contactInformation',            
            'documents',            
            'flightDetails',
            'assigndOperators',
            'assignedQuotes'
            /*'passengersInformation'*/
            
        ]);
    }

}
