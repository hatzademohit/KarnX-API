<?php
namespace App\Http\Controllers\Api\InquiryQuotes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Auth;
use App\Models\Asset;
use App\Models\InquiryQuoteDetails;

class InquiryQuoteController extends Controller
{
    public function getMyAircraft(Request $request){
       
        try {
            $data = Asset::with([
                'aircraftType',
            ])->where(['client_id' => Auth::user()->client_id, 'is_active' => 1])->get();

            return response()->json(['status' => true, 'data' => $data,'message' => 'Assets fetched successfully'], 200);
            
        } catch (\Exception $e) {
           return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }        
    }

    public function submitQuote(Request $request){
        
        try {
            $data = $request->all();
            $quote = new InquiryQuoteDetails();
            $quote->booking_inquiries_id  = $data['inquiryId'];
            $quote->client_id = Auth::user()->client_id;
            $quote->aircraft_id = $data['aircraft'];
            $quote->estimated_flight_time = 2;//$data['estimatedFlightTime'];
            $quote->base_fare = $data['baseFare'];
            $quote->fluel_cost = $data['fuel'];
            $quote->taxes_fees = $data['taxes'];
            $quote->crew_fees = $data['crewFees'];
            $quote->handling_fees = $data['handlingFees'];
            $quote->catering_fees = $data['catering'];
            $quote->total = $data['totalAmount'];
            $quote->validate_till = date('Y-m-d',  strtotime($data['quoteValidUntil']));  
            $quote->cancellation_policy_id = $data['cancellationPolicy']; 
            $quote->special_offers_promotions = $data['specialOffers'];
            $quote->additional_notes = $data['addtionalNotes'];
            $quote->save();
            return response()->json(['status' => true, 'message' => 'Quote submitted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
