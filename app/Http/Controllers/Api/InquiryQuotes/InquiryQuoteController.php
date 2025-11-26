<?php
namespace App\Http\Controllers\Api\InquiryQuotes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Auth;
use App\Models\Asset;
use App\Models\InquiryQuoteDetails;
use App\Models\FormFieldsData\AvailableAmenties;
use App\Models\Client;
use App\Models\BookingInquiries\BookingInquiries;

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
             
            $myAccessType = Client::where('id', Auth::user()->client_id)->first()->type;

            if($myAccessType === 'Aircraft Operator'){
               $lookup['client_id'] = Auth::user()->client_id;
            }

            

            $quotes = InquiryQuoteDetails::withRelations()->where($lookup);
                if($myAccessType === 'Aircraft Travel Agent'){
                    $quotes = $quotes->whereIn('is_selected', ['selected', 'approved']);
                }
            $quotes = $quotes->orderBy('total', 'asc')->get();  
           
            $quotes[0]['rating'] = 4.5;       
            $data['quotes'] = $quotes;
            $notRejectedQuotes = InquiryQuoteDetails::withRelations()->where($lookup);
                if($myAccessType === 'Aircraft Travel Agent'){
                    $notRejectedQuotes = $notRejectedQuotes->whereIn('is_selected', ['selected', 'approved']);
                }
            $notRejectedQuotes = $notRejectedQuotes->where('is_selected', '!=', 'rejected')->orderBy('total', 'asc');
            $data['non_rejected_quotes'] = $notRejectedQuotes->count() > 1? $notRejectedQuotes->get(): [];

            $bestQuote = InquiryQuoteDetails::select(DB::raw('min(total) as total'))->where($lookup)->whereNot('is_selected', 'rejected');
                if($myAccessType === 'Aircraft Travel Agent'){
                    $bestQuote = $bestQuote->whereIn('is_selected', ['selected', 'approved']);
                }
            $bestQuote = $bestQuote->first();
            $data['best_quote'] = $bestQuote;

            return response()->json(['status' => true, 'data' => $data, 'message' => 'Quote(s) fetched successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function rejectQuote(Request $request){
        try {
            $data = $request->all();            
            $quote = InquiryQuoteDetails::find($data['quoteId']);
            $quote->is_selected = 'rejected';
            $quote->rejected_reason = $data['message'];
            $quote->save();
            setInquiryStatuses($data['inquiryId'], [9], [$quote->client_id]); //rejected sts Id
            return response()->json(['status' => true, 'message' => 'Quote rejected successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function acceptQuote(Request $request){

        try {
            $data = $request->all();
           
            $acceptedQId = $data['acceptedQId'];
            $quoteIds = array_unique($data['quoteIds']);
            //$quoteIds = array_push($quoteIds, $acceptedQId);
            $bookingInfo = BookingInquiries::find($data['inquiryId']);
            $quoteIds = array_merge($quoteIds, [$acceptedQId]);
            $quoteId = array_unique($quoteId);
            //return response()->json(['status' => false, 'message' => $quoteIds], 500);
            foreach ($quoteIds as $key => $qId) {
                $quote = InquiryQuoteDetails::find($qId);
                if($qId === $acceptedQId){
                    $quote->is_selected = 'selected';
                    $quote->rejected_reason = NULL;
                    $quote->kx_mgr_commission_per = $data['kxMgrCommissionPercent'];
                    $quote->save();
                    setInquiryStatuses($data['inquiryId'], [8], [$quote->client_id]); //selected sts Id
                }else{
                    $quote->is_selected = 'rejected';
                    $quote->rejected_reason = $data['message'][$key];
                    $quote->save();
                    setInquiryStatuses($data['inquiryId'], [9], [$quote->client_id]); //rejected sts Id
                }                
            }
            setInquiryStatuses($data['inquiryId'], [7,10], [Auth::user()->client_id, $bookingInfo->client_id]); //approved sts Id
            return response()->json(['status' => true, 'message' => 'Quote accepted successfully'], 200);
        } catch (\Exception $e) {
             return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }

    }
}
