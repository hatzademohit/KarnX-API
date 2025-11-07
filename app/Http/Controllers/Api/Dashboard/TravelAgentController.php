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
                'this_month_mybooking' => BookingInquiries::where('user_id', Auth::user()->id)->whereMonth('created_at', now()->month)->count(),
                'my_booking_active_clients' => 2,
                'total_inquiries_this_month' => BookingInquiries::where(['user_id' => Auth::user()->id])->whereMonth('created_at', now()->month)->count(),
                'inquiry_pending_this_month' => BookingInquiries::where(['user_id' => Auth::user()->id])->where('status_id', 1)->count(),
                'confirmed_booking_this_week' => BookingInquiries::where('user_id', Auth::user()->id)
    ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
    ->where('status_id', 3)
    ->count(),
                'earning' => 5,
            ],
        ]);
    }   

    public function getCharterInquiries(Request $request)
    {
        // Fetch inquiry data dynamically with relations
        $query = BookingInquiries::with([
            'flightDetails',
            'aircraftPreference',
            'contactInformation',
            'cateringServices',
            'crewRequirements',
            'medicalAssistance',
            'petTravels',
            'documents',
        ]);

        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }
        
        $query->where('user_id', Auth::user()->id);

        if ($request->has('search')) {
            $query->where('booking_reference', 'like', '%' . $request->search . '%');
        }

        $bookings = $query->orderByDesc('id')->get();
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
            if($item->client_id){
                $client = Client::find($item->client_id)->name;
            }
            // Priority (example, needs mapping depending on status_id, business rule can be customized)
            $priority = [];
            if ($item->status_id == 1) {
                $priority = ['NEW', 'MEDIUM PRIORITY'];
            } elseif ($item->status_id == 2) {
                $priority = ['ACCEPTED', 'HIGH PRIORITY'];
            } elseif ($item->status_id == 3) {
                $priority = ['REJECTED', 'LOW PRIORITY'];
            }
            // Status actions
            $status = ''; $status_color = '';
            if ($item->status_id == 1) {
                $status = BookingStatus::find($item->status_id)->status_name;
                $status_color = BookingStatus::find($item->status_id)->color_code;
                //$status = 'Pending';//['Pending','Accept', 'Reject'];
            }
            // Assign string (just demo, real assignment calculation can go here)
            $assign = 'Assigned';
            // Date formatting
            $bookingDate = $item->booking_date ? date('M/d/Y', strtotime($item->booking_date)) : null;
            $formattedDate = null;
            if($item->flightDetails){
                $formattedDate = $item->flightDetails->departure_time !== null ? date('F d, Y', strtotime($item->flightDetails->departure_time)) : null;
            }
            return [
                'id' => $item->id,
                'inquiryId' => $item->booking_reference ?? null,
                'priority' => $priority,
                'route' => $route,
                'clientName' => $client,
                'clientEmail' => $item->contactInformation->email ?? null,
                'inquiryDate' => $bookingDate,
                'created_on' => date("F d, Y \a\\t h:i A", strtotime($item->created_at)),
                'date' => $formattedDate,
                'passangers' => $item->passenger_info_total ?? null,
                'aircraft' => $aircraftType,
                'assign' => $assign,
                'status' => $status,
                'status_color' => $status_color,
                'operators' => 'opt',
                'value' => 'val',
            ];
        })->toArray();
        return response()->json(['status' => true, 'data' => $inquiries, 'message' => 'Booking inquiries retrieved successfully']);
    }

}