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
        return response()->json([
            'success' => true,
            'data' => [
                'assigned_inquiries' => BookingInquiries::Join('booking_inquiry_operator_assignments as c', 'c.booking_inquiries_id', '=', 'booking_inquiries.id')
                        ->Join('booking_inquiry_process_statuses as a', 'a.booking_inquiries_id', '=', 'booking_inquiries.id')
                        ->where('a.is_active', 1)->whereIn('a.status_id', [4])->where('a.user_client_id', Auth::user()->client_id)
                        ->where('c.operator_id', Auth::user()->client_id)->count(),

                'quote_pending' => BookingInquiries::Join('booking_inquiry_operator_assignments as c', 'c.booking_inquiries_id', '=', 'booking_inquiries.id')
                        ->Join('booking_inquiry_process_statuses as a', 'a.booking_inquiries_id', '=', 'booking_inquiries.id')
                        ->where('a.is_active', 1)->whereIn('a.status_id', [5])->where('a.user_client_id', Auth::user()->client_id)
                        ->where('c.operator_id', Auth::user()->client_id)->count(),

                'upcoming_flights' => BookingInquiries::Join('booking_inquiry_operator_assignments as c', 'c.booking_inquiries_id', '=', 'booking_inquiries.id')
                        ->Join('booking_inquiry_process_statuses as a', 'a.booking_inquiries_id', '=', 'booking_inquiries.id')
                        ->where('a.is_active', 1)->whereIn('a.status_id', [12])->where('a.user_client_id', Auth::user()->client_id)
                        ->where('c.operator_id', Auth::user()->client_id)->count(),

                'live_flights' => BookingInquiries::Join('booking_inquiry_operator_assignments as c', 'c.booking_inquiries_id', '=', 'booking_inquiries.id')
                        ->Join('booking_inquiry_process_statuses as a', 'a.booking_inquiries_id', '=', 'booking_inquiries.id')
                        ->where('a.is_active', 1)->whereIn('a.status_id', [13])->where('a.user_client_id', Auth::user()->client_id)
                        ->where('c.operator_id', Auth::user()->client_id)->count(),

                'completed_bookings' => BookingInquiries::Join('booking_inquiry_operator_assignments as c', 'c.booking_inquiries_id', '=', 'booking_inquiries.id')
                        ->Join('booking_inquiry_process_statuses as a', 'a.booking_inquiries_id', '=', 'booking_inquiries.id')
                        ->where('a.is_active', 1)->whereIn('a.status_id', [14,15])->where('a.user_client_id', Auth::user()->client_id)
                        ->where('c.operator_id', Auth::user()->client_id)->count(),
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
        ->where('c.operator_id', Auth::user()->client_id)
        ->orderByDesc('booking_inquiries.id');
        $bookings = $query->get();
        
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
                'value' => 'val',
                'status_id' => $item->status_id,
            ];
        })->toArray();
        return response()->json(['status' => true, 'data' => $inquiries, 'message' => 'Booking inquiries retrieved successfully']);
    }

}