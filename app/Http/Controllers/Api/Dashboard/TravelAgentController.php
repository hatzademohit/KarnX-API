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
class TravelAgentController extends Controller
{
    public function cardCount()
    { 
        return response()->json([
            'success' => true,
            'data' => [
                'this_month_mybooking' => BookingInquiries::Join('booking_inquiry_process_statuses as a', 'a.booking_inquiries_id', '=', 'booking_inquiries.id')
                ->Join('booking_status as b', 'b.id', '=', 'a.status_id')
                ->where(['booking_inquiries.requester_id' => Auth::user()->id, 'a.is_active' => 1])->whereMonth('booking_inquiries.created_at', now()->month)->whereIn('b.id', [11,12,13,14])->count(),

                'my_booking_active_clients' => 0,

                'total_inquiries_this_month' => BookingInquiries::Join('booking_inquiry_process_statuses as a', 'a.booking_inquiries_id', '=', 'booking_inquiries.id')
                ->Join('booking_status as b', 'b.id', '=', 'a.status_id')
                ->where(['booking_inquiries.requester_id' => Auth::user()->id, 'a.is_active' => 1])->whereMonth('booking_inquiries.created_at', now()->month)->whereIn('b.id', [2])->count(),

                'inquiry_pending_this_month' => BookingInquiries::Join('booking_inquiry_process_statuses as a', 'a.booking_inquiries_id', '=', 'booking_inquiries.id')
                ->Join('booking_status as b', 'b.id', '=', 'a.status_id')
                ->where(['booking_inquiries.requester_id' => Auth::user()->id, 'a.is_active' => 1])->whereMonth('booking_inquiries.created_at', now()->month)->whereNotIn('b.id', [2,3,4,5,6])->count(),

                'confirmed_booking_this_week' => BookingInquiries::Join('booking_inquiry_process_statuses as a', 'a.booking_inquiries_id', '=', 'booking_inquiries.id')
                ->Join('booking_status as b', 'b.id', '=', 'a.status_id')
                ->whereBetween('a.created_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->where(['booking_inquiries.requester_id' => Auth::user()->id, 'a.is_active' => 1])->whereMonth('booking_inquiries.created_at', now()->month)->whereIn('b.id', [11])->count(),

                'earning' => 0,
            ],
        ]);
    }   

    public function getCharterInquiries(Request $request)
    {
        // Fetch inquiry data dynamically with relations
        $sub = \DB::table('booking_inquiry_process_statuses as a') ->select('a.booking_inquiries_id', 'a.status_id') ->where('a.is_active', 1) ->where('a.user_client_id', Auth::user()->client_id);
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
        ->leftJoinSub($sub, 'ps', function ($join) { 
            $join->on('ps.booking_inquiries_id', '=', 'booking_inquiries.id'); 
        })
        ->leftJoin('booking_status as b', 'b.id', '=', 'ps.status_id') 
        ->leftJoin('clients as c', 'c.id', '=', 'booking_inquiries.manager_id') 
        ->select('booking_inquiries.*', 'b.status_name', 'b.color_code', 'c.name as operator') 
        ->where('booking_inquiries.requester_id', Auth::user()->id);

        if ($request->has('client_id')) {
            $query->where('booking_inquiries.client_id', $request->client_id);
        }
        
        $query->where('booking_inquiries.requester_id', Auth::user()->id);

        if ($request->has('search')) {
            $query->where('booking_inquiries.booking_reference', 'like', '%' . $request->search . '%');
        }

        $bookings = $query->orderByDesc('booking_inquiries.id')->get();
        
        // Map to desired format
        $inquiries = $bookings->map(function ($item) {
            // Compose route
            $route = null;
            if ($item->flightDetails) {
                $dep = $item->flightDetails->departure_location;
                $arr = $item->flightDetails->arrival_location;
                $dep = AirportCities::find($dep)->code;
                $arr = AirportCities::find($arr)->code;
                $route = $dep . ' → ' . $arr;
            }
            // Compose aircraft type
            $aircraftType = null;
            if ($item->aircraftPreference && isset($item->aircraftPreference->aircraft_type_id)) {
                $aircraftType = AirCraftTypes::find($item->aircraftPreference->aircraft_type_id)->name;
            }
            // Client name
            $client = null;
            if(isset($item->contactInformation->contact_name)){
                $client = $item->contactInformation->contact_name;
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
                $formattedDate = $item->flightDetails->departure_time !== null ? date('F d, Y', strtotime($item->flightDetails->departure_time)) : null;
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
                'aircraft' => $aircraftType,
                'status' => $status,
                'status_color' => $status_color,
                'operators' => $item->operator,
                'value' => 'val',
            ];
        })->toArray();
        return response()->json(['status' => true, 'data' => $inquiries, 'message' => 'Booking inquiries retrieved successfully']);
    }

}