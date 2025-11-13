<?php

namespace App\Models\BookingInquiries;

use Illuminate\Database\Eloquent\Model;
use App\Models\BookingInquiries\BookingInquiries;
use App\Models\BookingInquiries\Document; 

class BookingInquiriesDocument extends Model
{
    protected $table = 'booking_inquiries_documents';
    protected $fillable = [
        'booking_inquiry_id', 'document_type', 'document_path'
    ];

    public function document() { return $this->belongsTo(Document::class); }
    public function bookingInquiry() { return $this->belongsTo(BookingInquiries::class); }
}
