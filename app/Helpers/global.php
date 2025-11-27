<?php
use App\Models\Client;
use App\Models\BookingInquiries\BookingInquiriesStatuses;

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
        ]);

        if($isInserted){
            //BookingInquiriesStatuses::where(['booking_inquiries_id' => $inquiry_id, 'user_client_id' => $usr])->update(['is_active' => 0]);
        }        
    }
}
