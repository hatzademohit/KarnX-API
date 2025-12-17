<?php
namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BookingInquiries\BookingInquiries;
use DB;
use App\Models\FormFieldsData\AirCraftTypes;
use App\Models\Client;
use App\Models\FormFieldsData\AirportCities;
use App\Models\BookingStatus;
use Auth;
class AircraftOperatorController extends Controller
{
    public function cardCount()
    { 
        $assignedInquiry = BookingInquiries::Join('booking_inquiry_operator_assignments as c', 'c.booking_inquiries_id', '=', 'booking_inquiries.id')
                        ->Join('booking_inquiry_process_statuses as a', 'a.booking_inquiries_id', '=', 'booking_inquiries.id')
                        ->where('a.is_active', 1)->whereIn('a.status_id', [4])->where('a.user_client_id', Auth::user()->client_id)
                        ->where('c.operator_id', Auth::user()->client_id);
        $quotePending = BookingInquiries::Join('booking_inquiry_operator_assignments as c', 'c.booking_inquiries_id', '=', 'booking_inquiries.id')
                        ->Join('booking_inquiry_process_statuses as a', 'a.booking_inquiries_id', '=', 'booking_inquiries.id')
                        ->where('a.is_active', 1)->whereIn('a.status_id', [5,8])->where('a.user_client_id', Auth::user()->client_id)
                        ->where('c.operator_id', Auth::user()->client_id);
        $upcommingFlights = BookingInquiries::Join('booking_inquiry_operator_assignments as c', 'c.booking_inquiries_id', '=', 'booking_inquiries.id')
                        ->Join('booking_inquiry_process_statuses as a', 'a.booking_inquiries_id', '=', 'booking_inquiries.id')
                        ->where('a.is_active', 1)->whereIn('a.status_id', [12])->where('a.user_client_id', Auth::user()->client_id)
                        ->where('c.operator_id', Auth::user()->client_id);
        $liveFlights = BookingInquiries::Join('booking_inquiry_operator_assignments as c', 'c.booking_inquiries_id', '=', 'booking_inquiries.id')
                        ->Join('booking_inquiry_process_statuses as a', 'a.booking_inquiries_id', '=', 'booking_inquiries.id')
                        ->where('a.is_active', 1)->whereIn('a.status_id', [13])->where('a.user_client_id', Auth::user()->client_id)
                        ->where('c.operator_id', Auth::user()->client_id);
        $completedBookings = BookingInquiries::Join('booking_inquiry_operator_assignments as c', 'c.booking_inquiries_id', '=', 'booking_inquiries.id')
                        ->Join('booking_inquiry_process_statuses as a', 'a.booking_inquiries_id', '=', 'booking_inquiries.id')
                        ->where('a.is_active', 1)->whereIn('a.status_id', [11,14,15])->where('a.user_client_id', Auth::user()->client_id)
                        ->where('c.operator_id', Auth::user()->client_id);              
        return response()->json([
            'success' => true,
            'data' => [
                'assigned_inquiries' => $assignedInquiry->count(),
                'assigned_inquiries_ids' => $assignedInquiry->select('booking_inquiries.id')->get()->map(function($val){ return $val->id;}),

                'quote_pending' => $quotePending->count(),
                'quote_pending_ids' => $quotePending->select('booking_inquiries.id')->get()->map(function($val){ return $val->id;}),

                'upcoming_flights' => $upcommingFlights->count(),
                'upcoming_flights_ids' => $upcommingFlights->select('booking_inquiries.id')->get()->map(function($val){ return $val->id;}),

                'live_flights' => $liveFlights->count(),
                'live_flights_ids' => $liveFlights->select('booking_inquiries.id')->get()->map(function($val){ return $val->id;}),

                'completed_bookings' => $completedBookings->count(),
                'completed_bookings_ids' => $completedBookings->select('booking_inquiries.id')->get()->map(function($val){ return $val->id;}),
            ],
        ]);
    }   

    public function getCharterInquiries(Request $request)
    {
        // Fetch inquiry data dynamically with relations
        $ps = \DB::table('booking_inquiry_process_statuses as a')
            ->select('a.booking_inquiries_id', \DB::raw('MAX(a.status_id) as status_id'))
            ->where('a.is_active', 1)
            ->where('a.user_client_id', Auth::user()->client_id)
            ->groupBy('a.booking_inquiries_id');

        $query = BookingInquiries::with([
            'flightDetails',
            'aircraftPreference',
            'contactInformation',
            'cateringServices',
            'crewRequirements',
            'medicalAssistance',
            'petTravels',
            'documents',
        ])
        ->leftJoinSub($ps, 'ps', function ($join) {
            $join->on('ps.booking_inquiries_id', '=', 'booking_inquiries.id');
        })
        ->leftJoin('booking_status as b', 'b.id', '=', 'ps.status_id')
        ->join('booking_inquiry_operator_assignments as c', 'c.booking_inquiries_id', '=', 'booking_inquiries.id')
        ->select('booking_inquiries.*', 'b.status_name', 'b.id as status_id', 'b.color_code')        
        ->where('c.operator_id', Auth::user()->client_id);

        if ($request->has('ids')) {
             $query = $query->whereIn('booking_inquiries.id', explode(',', $request->ids));
        }
         $query = $query->orderByDesc('booking_inquiries.id');
        $bookings = $query->get();
        
        // Map to desired format
        $inquiries = $bookings->map(function ($item) {
            // Compose route
            $route = null;
            if ($item->flightDetails) {
                $dep = $item->flightDetails[0]->departure_location;
                $arr = $item->flightDetails[0]->arrival_location;
                $dep = AirportCities::find($dep)->code;
                $arr = AirportCities::find($arr)->code;
                $route = $dep . ' → ' . $arr;
                if($item->trip_type === 'multi_city'){
                    $arr1 = $item->flightDetails[1]->arrival_location;
                    $arr1 = AirportCities::find($arr1)->code;
                    $route = $dep . ' → ' . $arr. ' → ' . $arr1;
                }else if($item->trip_type === 'round_trip'){
                    $route = $dep . ' ⇄ ' . $arr;
                }
            }
            // Compose aircraft type
            $aircraftType = [];
            if ($item->aircraftPreference && isset($item->aircraftPreference)) {
                foreach($item->aircraftPreference as $val){
                   $aircraftType[] =  AirCraftTypes::find($val['aircraft_type_id'])->name;
                }
            }
            // Client name
            $client = null;
            if(isset($item->client_id)){
                $client = Client::where('id', $item->client_id)->first('name')->name;
            }
            
            // Status actions
            $status = ''; $status_color = '';
            if ($item->status_name) {
                $status = $item->status_name;
                $status_color = $item->color_code;
            }
            
            // Date formatting
            $bookingDate = $item->booking_date ? date('M/d/Y', strtotime($item->booking_date)) : null;
            $formattedDate = null;
            if($item->flightDetails){
                $formattedDate = $item->flightDetails[0]->departure_time !== null ? date('F d, Y', strtotime($item->flightDetails[0]->departure_time)) : null;
            }

            $quoteAmt = '-';
            
            $quote = $item->assignedQuotes->where('client_id', Auth::user()->client_id)->first();           
            if($quote != null){
                $quoteAmt = number_format($quote->total);
            }

            return [
                'id' => $item->id,
                'inquiryId' => $item->booking_reference ?? null,
                'route' => $route,
                'clientName' => $client,
                'clientEmail' => isset($item->contactInformation->email) ? $item->contactInformation->email : null,
                'inquiryDate' => $bookingDate,
                'created_on' => date("F d, Y \a\\t h:i A", strtotime($item->created_at)),
                'date' => $formattedDate,
                'passangers' => $item->passenger_info_total ?? null,
                'aircraft' => implode(', ', $aircraftType),
                'status' => $status,
                'status_color' => $status_color,
                'value' => $quoteAmt,
                'status_id' => $item->status_id,
            ];
        })->toArray();
        return response()->json(['status' => true, 'data' => $inquiries, 'message' => 'Booking inquiries retrieved successfully']);
    }

}