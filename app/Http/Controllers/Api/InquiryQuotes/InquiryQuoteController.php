<?php
namespace App\Http\Controllers\Api\InquiryQuotes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Auth;
use App\Models\Asset;
use App\Models\InquiryQuoteDetails;
use App\Models\FormFieldsData\AvailableAmenties;

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

            // Upsert: update if a quote already exists for this inquiry, client, and aircraft; otherwise create new
            $lookup = [
                'booking_inquiries_id' => $data['inquiryId'],
                'client_id' => Auth::user()->client_id,                
            ];

            $payload = [
                'aircraft_id' => $data['aircraft'],
                'estimated_flight_time' => 2, // $data['estimatedFlightTime'] when available
                'base_fare' => $data['baseFare'],
                'fluel_cost' => $data['fuel'],
                'taxes_fees' => $data['taxes'],
                'crew_fees' => $data['crewFees'],
                'handling_fees' => $data['handlingFees'],
                'catering_fees' => $data['catering'],
                'total' => $data['totalAmount'],
                'validate_till' => date('Y-m-d', strtotime($data['quoteValidUntil'])),
                'cancellation_policy_id' => $data['cancellationPolicy'],
                'special_offers_promotions' => $data['specialOffers'],
                'additional_notes' => $data['addtionalNotes'],
                'amenities_ids' => implode(',', $data['amenities']),
            ];
            
            // Eloquent updateOrCreate
            $quote = InquiryQuoteDetails::updateOrCreate($lookup, $payload);
            setInquiryStatuses($data['inquiryId'], [5,6], [Auth::user()->client_id, getDefualtClient()]);
            return response()->json(['status' => true, 'data' => $quote, 'message' => 'Quote saved successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function editQuote(Request $request, $inquiryId){
        try {
            $lookup = [
                'booking_inquiries_id' => $inquiryId,
                'client_id' => Auth::user()->client_id,                
            ];        
            $quote = InquiryQuoteDetails::where($lookup)->first();
            return response()->json(['status' => true, 'data' => $quote, 'message' => 'Quote fetched successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
        
    }

    public function getQuotedQuotes(Request $request, $inquiryId){
       try {
            $lookup = [
                'booking_inquiries_id' => $inquiryId
            ];

            $quotes = InquiryQuoteDetails::withRelations()->where($lookup)->orderBy('total', 'asc')->get();  
            $quotes[0]['rating'] = 4.5;       
            $data['quotes'] = $quotes;
            $data['best_quote'] = InquiryQuoteDetails::select(DB::raw('min(total) as total'))->where($lookup)->first();
            return response()->json(['status' => true, 'data' => $data, 'message' => 'Quote(s) fetched successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function acceptRejectQuote(Request $request){

    }
}
