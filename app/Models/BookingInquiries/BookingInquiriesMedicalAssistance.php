<?php

namespace App\Models\BookingInquiries;

use App\Models\BookingInquiries\BookingInquiries;
use App\Models\BookingInquiries\MedicalAssist;
use Illuminate\Database\Eloquent\Model;

class BookingInquiriesMedicalAssistance extends Model
{
    protected $table = 'booking_inquiries_medical_need_assist';
    protected $fillable = [
        'booking_inquiries_id', 'medical_assist_id', 'other_requirements'
    ];

    public function bookingInquiry() { return $this->belongsTo(BookingInquiries::class); }
    public function medicalAssist() { return $this->belongsTo(MedicalAssist::class); }
}
