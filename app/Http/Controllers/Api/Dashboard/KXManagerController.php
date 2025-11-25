<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\bookingInquiries\BookingInquiries;
use Illuminate\Support\Facades\Auth;
use App\Models\FormFieldsData\AirportCities;
use App\Models\FormFieldsData\AirCraftTypes;

class KXManagerController extends Controller
{
    public function cardCount()
    { 
        return response()->json([
            'success' => true,
            'data' => [
                'new_inquiries' => BookingInquiries::Join('booking_inquiry_process_statuses as a', 'a.booking_inquiries_id', '=', 'booking_inquiries.id')
                ->Join('booking_status as b', 'b.id', '=', 'a.status_id')
                ->where(['a.is_active' => 1])->whereMonth('booking_inquiries.created_at', now()->month)->whereIn('b.id', [3])->count(),

                'quote_pending' => BookingInquiries::Join('booking_inquiry_process_statuses as a', 'a.booking_inquiries_id', '=', 'booking_inquiries.id')
                ->Join('booking_status as b', 'b.id', '=', 'a.status_id')
                ->where(['a.is_active' => 1])->whereMonth('booking_inquiries.created_at', now()->month)->whereIn('b.id', [4])->count(),  

                'clients_decision' => 0,

                'confirmed_booking' => BookingInquiries::Join('booking_inquiry_process_statuses as a', 'a.booking_inquiries_id', '=', 'booking_inquiries.id')
                ->Join('booking_status as b', 'b.id', '=', 'a.status_id')
                ->where(['a.is_active' => 1])->whereIn('b.id', [11])->count(),

                'response_time' => 0,
            ],
        ]);
    } 

    public function getActivityTimeline(){
        $data = [
            [
                'id' => 1,
                'icon' => `<Flight color="primary" />`,
                'title' => 'New inquiry received',
                'time' => '2 minutes ago',
                'details' => 'DEL → BOM, October 25, 2025 • 4 passengers',
                'subtitle' => 'Harrison Industries',
            ],
        ];
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function getPriorityTask(){

        $data = [
            [
                'id' => 1,
                'icon' => '',
                'title' => 'Overdue Operator Response',
                'details' => 'Premium Jets – 6 hours overdue',
                'ref' => 'INQ–2024–0842',
                'priority' => 'High',
                'bg' => '#F4FBF6',
            ], 
            [
                'id' => 2,
                'icon' => '',
                'title' => 'Quote Expiring Soon',
                'details' => 'Client decision needed in 1.5 hours',
                'ref' => 'INQ–2024–0839',
                'priority' => 'Medium',
                'bg' => '#F7F7F9',
            ], 
        ];
        $highPriorityCount = collect($data)->whereStrict('priority', 'High')->count();
        return response()->json([
            'success' => true,
            'data' => $data,
            'priorityCount' => $highPriorityCount. ' High Priority', // get count of high priority task only
        ]);
    }

    public function getCharterInquiries(Request $request)
    {
        // Fetch inquiry data dynamically with relations
        $latestPerBooking = \DB::table('booking_inquiry_process_statuses as a')
        ->join(
        \DB::raw('(
            SELECT booking_inquiries_id, MAX(id) AS max_id
            FROM booking_inquiry_process_statuses
            WHERE is_active = 1 AND user_client_id = ' . (int) Auth::user()->client_id . '
            GROUP BY booking_inquiries_id
        ) latest'),
        function ($join) {
            $join->on('latest.max_id', '=', 'a.id');
        }
        )
        ->select('a.booking_inquiries_id', 'a.status_id');

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
            ->leftJoinSub($latestPerBooking, 'ps', function ($join) {
                $join->on('ps.booking_inquiries_id', '=', 'booking_inquiries.id');
            })
            ->leftJoin('booking_status as b', 'b.id', '=', 'ps.status_id')
            ->leftJoin('clients as c', 'c.id', '=', 'booking_inquiries.manager_id')
            ->select('booking_inquiries.*', 'b.status_name', 'b.id as status_id', 'b.color_code', 'c.name as operator');

        if ($request->has('client_id')) {
            //$query->where('booking_inquiries.client_id', $request->client_id);
        }

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
                'value' => '0',
                'status_id' => $item->status_id,
            ];
        })->toArray();
        return response()->json(['status' => true, 'data' => $inquiries, 'message' => 'Booking inquiries retrieved successfully']);
    }
}
