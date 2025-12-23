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
    foreach ($forUser as $key => $usr) {        
        $isInserted = BookingInquiriesStatuses::create([
            'booking_inquiries_id' => $inquiry_id,
            'status_id' => $statusIds[$key],
            'user_client_id' => $usr,
            'updated_by' => Auth::user()->id,
        ]);

        if($isInserted){
            BookingInquiriesStatuses::where(['booking_inquiries_id' => $inquiry_id, 'user_client_id' => $usr])->where('id', '!=', $isInserted->id)->update(['is_active' => 0]);
        }        
    }
}
