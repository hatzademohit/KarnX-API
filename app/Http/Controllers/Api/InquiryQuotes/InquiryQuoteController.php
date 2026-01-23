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
use App\Models\FormFieldsData\AirportCities;
use App\Models\BookingInquiries\BookingTravellerDetails;
use App\Models\BookingInquiries\BookingTravellerContactDetails;
use App\Models\InquiryQuoteFlightTime;
use Carbon\Carbon;
use App\Models\TravellerPassangerData;
use App\Models\BookingStatus;

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

    public function getBookingFlightDetails(Request $request, $inquiryId){
           
        try {
            $query = BookingInquiries::with(['flightDetails'])->where('id', $inquiryId)->first();
            
            $data['trip_type'] = $query->trip_type;
            $data['is_flexible_dates'] = $query->is_flexible_dates;
            $data['flexible_range'] = $query->flexible_range;
            $details = [];
            
            foreach($query->flightDetails as $fd){
                $arrCity = AirportCities::find($fd->arrival_location);
                $depCity = AirportCities::find($fd->departure_location);
                $cityTime['departure_time'] = $fd->departure_time;
                $cityTime['departure_city'] = $depCity->code;
                $cityTime['arrival_city'] = $arrCity->code;
                $cityTime['flight_details_id'] = $fd->id;

                $details[] = $cityTime;
            }

            $data['flight_time'] =  $details;

            return response()->json(['status' => true, 'data' => $data, 'message' => 'Booking details fetched successfully'], 200);
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
                'aircraft_id' => $data['aircraft_id'],
                'estimated_flight_time' => 0, // $data['estimated_flight_time'] when available
                'base_fare' => $data['base_fare'],
                'fluel_cost' => $data['fluel_cost'],
                'taxes_fees' => $data['taxes_fees'],
                'crew_fees' => $data['crew_fees'],
                'handling_fees' => $data['handling_fees'],
                'catering_fees' => $data['catering_fees'],
                'total' => $data['total'],
                'validate_till' => date('Y-m-d', strtotime($data['validate_till'])),
                'cancellation_policy_id' => $data['cancellation_policy_id']??0,
                'special_offers_promotions' => $data['special_offers_promotions']??'',
                'additional_notes' => $data['additional_notes']??'',
                'amenities_ids' => implode(',', $data['amenities_ids'] ?? []),
            ];
            
            // Eloquent updateOrCreate
            $quote = InquiryQuoteDetails::updateOrCreate($lookup, $payload);
            $estimateTime = 0;
            foreach($data['estimate'] as $key => $value) {                
                $dt = Carbon::parse($value['estimated_flight_time'])->setTimezone(env('TIME_ZONE'));               
                $minutes = ($dt->hour * 60) + $dt->minute;
                $lookup1 = [
                    'booking_inquiries_id' => $data['inquiryId'],
                    'quote_id' => $quote->id,
                    'booking_inquiries_flight_location_id' => $value['flight_details_id'],
                ];
                InquiryQuoteFlightTime::updateOrCreate($lookup1, [
                    'departure_date_time' => date('Y-m-d H:i:s', strtotime($value['departure_time'])),
                    'flight_duration' => $minutes,
                ]);
                $estimateTime += $minutes;
            }
            $quote->estimated_flight_time = $estimateTime;
            $quote->save();            
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
            $quote = InquiryQuoteDetails::with(['client',
            'aircraft',
            'cancelationPolicy',
            'inquiryQuoteFlightTime.depArriveLocation.airportDepartureLocation',
            'inquiryQuoteFlightTime.depArriveLocation.airportArrivalLocation'])
            ->where($lookup)->first();
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

    public function approveQuote(Request $request){

        try {
            $data = $request->all();
           
            $acceptedQId = $data['acceptedQId'];
            $quoteIds = isset($data['quoteIds'])?array_unique($data['quoteIds']):[];
            //$quoteIds = array_push($quoteIds, $acceptedQId);
            $bookingInfo = BookingInquiries::find($data['inquiryId']);
            $quoteIds = array_merge($quoteIds, [$acceptedQId]);
            $quoteIds = array_unique($quoteIds);
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
            return response()->json(['status' => true, 'message' => 'Quote approved successfully'], 200);
        } catch (\Exception $e) {
             return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }

    }

    public function acceptQuote(Request $request){
            $qId = $request->quoteId;
            $inquiryId = $request->inquiryId;
            try {
                $quote = InquiryQuoteDetails::find($qId); 
                $quote->is_selected = 'approved';
                $quote->save();
                setInquiryStatuses($inquiryId, [17, 17, 17], [Auth::user()->client_id, $quote->client_id, getDefualtClient()]); //selected sts Id
                $dataSts = BookingStatus::where(['id' => 17])->first();
                return response()->json(['status' => true, 'data' => $dataSts, 'message' => 'Quote accepted successfully'], 200);
            } catch (\Exception $e) {
                 return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
            }
    }

    public function rejectReQuote(Request $request){
        try {
            $data = $request->all();
            $quote = InquiryQuoteDetails::find($data['quoteId']);
            //$quote->is_selected = 'rejected';
            $quote->rejected_reason = $data['message'];
            $quote->save();
            if($data['action'] == 'rejected'){
                setInquiryStatuses($data['inquiryId'], [18, 18], [Auth::user()->client_id, getDefualtClient()]);//rejected sts Id
                return response()->json(['status' => true, 'message' => 'Quote rejected successfully'], 200);
            }else if($data['action'] == 'requote'){
                setInquiryStatuses($data['inquiryId'], [19, 19], [Auth::user()->client_id, getDefualtClient()]);//rejected sts Id
                return response()->json(['status' => true, 'message' => 'The quotation has been sent for revision'], 200);
            }           
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function confirmBooking(Request $request){
            
            $qId =  $request->quoteId['quoteId'];
            $inquiryId = $request->inquiryId;
            $data = $request->data;
            try {

                if(isset($data['infants'])){
                    $this->setTravellerNames($data, 'infants', $inquiryId, $qId);
                }

                if(isset($data['children'])){
                    $this->setTravellerNames($data, 'children', $inquiryId, $qId);
                }

                if(isset($data['adults'])){
                    $this->setTravellerNames($data, 'adults', $inquiryId, $qId);
                }
                
                BookingTravellerContactDetails::create([
                    'booking_inquiries_id' => $inquiryId,
                    'client_id' => Auth::user()->client_id,
                    'contact_name' => $data['contact_name'],
                    'contact_email' => $data['contact_email'],
                    'contact_phone' => $data['contact_phone'],
                    'pincode' => $data['pincode'],
                    'address' => $data['address'],
                    'city' => $data['city']
                ]);
                $quote = InquiryQuoteDetails::find($qId); 
                $booking = BookingInquiries::find($inquiryId);
                $booking->is_confirmed = 1;
                $booking->save();
                setInquiryStatuses($inquiryId, [11, 11, 11], [Auth::user()->client_id, $quote->client_id, getDefualtClient()]); //selected sts Id
                return response()->json(['status' => true, 'message' => 'Quote accepted successfully'], 200);
            } catch (\Exception $e) {
                 return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
            }
    }

    public function setTravellerNames($data, $arrKey, $inquiryId, $quoteId){

        foreach ($data[$arrKey] as $key => $value) {
            $id = $value['id'];
            if($value['id'] === 0){
                $id = TravellerPassangerData::insertGetId([
                    'client_id' => Auth::user()->client_id,
                    'user_id' => Auth::user()->id,
                    'name' => $value['name'],
                    'age' => $value['age'],
                ]);
                
            }
            BookingTravellerDetails::create([
                'booking_inquiries_id' => $inquiryId,
                'client_id' => Auth::user()->client_id,
                'passenger_id' => $id,
                'quote_id' => $quoteId,
                'user_id' => Auth::user()->id,
            ]);
        }        
    }
}
