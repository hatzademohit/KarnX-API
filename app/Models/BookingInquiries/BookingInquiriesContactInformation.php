<?php

namespace App\Models\BookingInquiries;

use Illuminate\Database\Eloquent\Model;
use App\Models\BookingInquiries\BookingInquiries;
use App\Models\BookingInquiries\ContactInformation;

class BookingInquiriesContactInformation extends Model
{
    protected $table = 'booking_inquiries_contact_information';
    protected $fillable = [
        'booking_inquiry_id', 'contact_name', 'contact_email', 'contact_phone', 'special_requirements'
    ];

    public function contactInformation() { return $this->belongsTo(ContactInformation::class); }
    public function bookingInquiry() { return $this->belongsTo(BookingInquiries::class); }
}
