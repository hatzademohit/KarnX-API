<?php

namespace App\Models\BookingInquiries;

use Illuminate\Database\Eloquent\Model;
use App\Models\BookingInquiries\BookingInquiries;
use App\Models\BookingInquiries\PetTravel;

class BookingInquiriesPetTravel extends Model
{
    protected $table = 'booking_inquiries_pet_travel';
    protected $fillable = [
        'booking_inquiry_id', 'pet_type', 'additional_notes', 'pet_size'
    ];

    public function petTravel() { return $this->belongsTo(PetTravel::class); }
    public function bookingInquiry() { return $this->belongsTo(BookingInquiries::class); }
}
