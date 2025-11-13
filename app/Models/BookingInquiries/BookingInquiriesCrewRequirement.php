<?php

namespace App\Models\BookingInquiries;

use Illuminate\Database\Eloquent\Model;
use App\Models\BookingInquiries\BookingInquiries;
use App\Models\BookingInquiries\CrewRequirement;

class BookingInquiriesCrewRequirement extends Model
{
    protected $table = 'booking_inquiries_crew_requirements';
    protected $fillable = [
        'booking_inquiry_id', 'crew_req_id', 'additional_notes',
    ];

    public function crewRequirement() { return $this->belongsTo(CrewRequirement::class); }
    public function bookingInquiry() { return $this->belongsTo(BookingInquiries::class); }
}
