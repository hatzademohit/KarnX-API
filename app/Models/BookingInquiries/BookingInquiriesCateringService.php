<?php

namespace App\Models\BookingInquiries;

use Illuminate\Database\Eloquent\Model;
use App\Models\BookingInquiries\BookingInquiries;
use App\Models\BookingInquiries\CateringService;

class BookingInquiriesCateringService extends Model
{
    protected $table = 'booking_inquiries_catering_services';
    protected $fillable = [
        'booking_inquiries_id', 'dietary_required', 'allergy_notes', 'drink_preferences', 'custom_services'
    ];

    public function cateringService() { return $this->belongsTo(CateringService::class); }
    public function bookingInquiry() { return $this->belongsTo(BookingInquiries::class); }
}
