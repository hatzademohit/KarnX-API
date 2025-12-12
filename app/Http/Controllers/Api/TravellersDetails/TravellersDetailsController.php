<?php

namespace App\Http\Controllers\Api\TravellersDetails;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BookingInquiries\BookingTravellerDetails;
use DB;
use Auth;
use App\Models\TravellerPassangerData;
use App\Models\BookingInquiries\BookingTravellerContactDetails;

class TravellersDetailsController extends Controller
{
    public function getTravellerDetails(Request $request, $inquiryId){
        try {
            $data['passangers'] = BookingTravellerDetails::join('traveller_passanger_data as tpd', 'tpd.id', '=', 'traveller_persons_details.passenger_id')
            ->where('traveller_persons_details.booking_inquiries_id', $inquiryId)->select('tpd.id', 'tpd.name', 'tpd.age', DB::raw("CASE WHEN traveller_persons_details.selected = 1 THEN true ELSE false END AS selected"))->get();
            $data['contact_details'] = BookingTravellerContactDetails::where('booking_inquiries_id', $inquiryId)->first();
           
            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'message' => $th->getMessage()], 500);
        }
        
    }

    public function getTravellerPassengerData(Request $request){
        try {
            $data = TravellerPassangerData::where('client_id', Auth::user()->client_id)->get()
            ->groupBy(function($row) {

                if ($row->age < 2) return 'infant';
                if ($row->age <= 12) return 'child';
                return 'adult';

            });

            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'message' => $th->getMessage()], 500);
        }
    }
}
