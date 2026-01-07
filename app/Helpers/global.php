<?php
use App\Models\Client;
use App\Models\BookingInquiries\BookingInquiriesStatuses;
use Illuminate\Support\Facades\Auth;


function getDefualtClient()
{
    return Client::where('id', 1)->value('id');
}

function setInquiryStatuses($inquiry_id, $statusIds, $toIds)
{
    $forUser = $toIds;
    $authId = Auth::user()->id;

    foreach ($forUser as $key => $usr) {        
        $isInserted = BookingInquiriesStatuses::create([
            'booking_inquiries_id' => $inquiry_id,
            'status_id' => $statusIds[$key],
            'user_client_id' => $usr,
            'updated_by' => $authId,
        ]);

        if($isInserted){
            BookingInquiriesStatuses::where(['booking_inquiries_id' => $inquiry_id, 'user_client_id' => $usr])->where('id', '!=', $isInserted->id)->update(['is_active' => 0]);
        }        
    }
}


function formatHours($hours)
{
        if ($hours < 24) return "$hours hours";

        $days = intdiv($hours, 24);
        $hrs  = $hours % 24;

        return "{$days} day" . ($days > 1 ? 's' : '') .
               ($hrs ? " {$hrs} hour" . ($hrs > 1 ? 's' : '') : '');
}
