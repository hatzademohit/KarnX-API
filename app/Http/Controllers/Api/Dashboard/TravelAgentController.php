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
        $myBookings = BookingInquiries::join('booking_inquiry_process_statuses as a', 'a.booking_inquiries_id', '=', 'booking_inquiries.id')
                            ->join('booking_status as b', 'b.id', '=', 'a.status_id')
                            ->where('booking_inquiries.requester_id', Auth::id())
                            ->where('a.is_active', 1)
                            ->whereMonth('booking_inquiries.created_at', now()->month)
                            ->whereYear('booking_inquiries.created_at', now()->year)
                            ->distinct('booking_inquiries.id'); //whereIn('b.id', [11,12,13,14])->count(),
        $totalInquiry = BookingInquiries::Join('booking_inquiry_process_statuses as a', 'a.booking_inquiries_id', '=', 'booking_inquiries.id')
                ->Join('booking_status as b', 'b.id', '=', 'a.status_id')
                ->where(['booking_inquiries.requester_id' => Auth::user()->id, 'a.is_active' => 1])->whereMonth('booking_inquiries.created_at', now()->month)->whereIn('b.id', [2]);
        $inquiriesPending = BookingInquiries::Join('booking_inquiry_process_statuses as a', 'a.booking_inquiries_id', '=', 'booking_inquiries.id')
                ->Join('booking_status as b', 'b.id', '=', 'a.status_id')
                ->where(['booking_inquiries.requester_id' => Auth::user()->id, 'a.is_active' => 1, 'a.user_client_id' => Auth::user()->client_id])->whereMonth('booking_inquiries.created_at', now()->month)->whereNotIn('a.status_id', [2]);
        $confirmBookings = BookingInquiries::Join('booking_inquiry_process_statuses as a', 'a.booking_inquiries_id', '=', 'booking_inquiries.id')
                ->Join('booking_status as b', 'b.id', '=', 'a.status_id')
                ->whereBetween('a.created_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->where(['booking_inquiries.requester_id' => Auth::user()->id, 'a.is_active' => 1])->whereMonth('a.created_at', now()->month)->whereIn('b.id', [11])->distinct('booking_inquiries.id'); //->groupBy('a.booking_inquiries_id');
        return response()->json([
            'success' => true,
            'data' => [
                'this_month_mybooking' => $myBookings->count('booking_inquiries.id'),
                'this_month_mybooking_ids' => $myBookings->select('booking_inquiries.id')->get()->map(function($val){ return $val->id;}),

                'my_booking_active_clients' => 0,

                'total_inquiries_this_month' => $totalInquiry->count(),
                'total_inquiries_this_month_ids' => $totalInquiry->select('booking_inquiries.id')->get()->map(function($val){ return $val->id;}),

                'inquiry_pending_this_month' => $inquiriesPending->count(),
                'inquiry_pending_this_month_ids' => $inquiriesPending->select('booking_inquiries.id')->get()->map(function($val){ return $val->id;}),

                'confirmed_booking_this_week' => $confirmBookings->count(),
                'confirmed_booking_this_week_ids' => $confirmBookings->select('booking_inquiries.id')->get()->map(function($val){ return $val->id;}),

                'earning' => 0,
            ],
        ]);
    }   

    public function getCharterInquiries(Request $request)
    {
        // Fetch inquiry data dynamically with relations
        // $sub = \DB::table('booking_inquiry_process_statuses as a') ->select('a.booking_inquiries_id', 'a.status_id') ->where('a.is_active', 1) ->where('a.user_client_id', Auth::user()->client_id);

        $sub = \DB::table('booking_inquiry_process_statuses as a')
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
            'assignedQuotes',
            'assigndOperators'

        ]) 
        ->leftJoinSub($sub, 'ps', function ($join) { 
            $join->on('ps.booking_inquiries_id', '=', 'booking_inquiries.id'); 
        })
        ->leftJoin('booking_status as b', 'b.id', '=', 'ps.status_id') 
        ->leftJoin('clients as c', 'c.id', '=', 'booking_inquiries.manager_id') 
        ->select('booking_inquiries.*', 'b.status_name', 'b.id as status_id', 'b.color_code', 'c.name as operator') 
        ->where('booking_inquiries.requester_id', Auth::user()->id);

        if ($request->has('client_id')) {
            $query->where('booking_inquiries.client_id', $request->client_id);
        }

        if ($request->has('ids')) {
            $query->whereIn('booking_inquiries.id', explode(',', $request->ids));
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
                $dep = $item->flightDetails[0]->departure_location;
                $arr = $item->flightDetails[0]->arrival_location;
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
                $formattedDate = $item->flightDetails[0]->departure_time !== null ? date('F d, Y', strtotime($item->flightDetails[0]->departure_time)) : null;
            }
            $quoteValue = $item->assignedQuotes->whereIn('is_selected', ['selected','approved'])->first();
            $quoteAmt = '-'; $operatorName = '-';
            if($quoteValue){
                $quoteAmt = $quoteValue->total + ((((float)$quoteValue->total / 100) * (float)$quoteValue->kx_mgr_commission_per) ?? 0);
                $quoteAmt = $quoteValue->travel_agent_commission_per != null ? $quoteAmt + (($quoteAmt / 100) * $quoteValue->travel_agent_commission_per) : $quoteAmt;
                $quoteAmt = number_format($quoteAmt);

                $operatorName = Client::where('id', $quoteValue->client_id)->first('name')->name;
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
                'operator' => $operatorName,
                'value' => $quoteAmt,
                'status_id' => $item->status_id,
                'quote_received' => $item->assignedQuotes->whereIn('is_selected', ['selected','approved'])->count() ?? 0,
                'operator_assigned' => $item->assignedQuotes->whereIn('is_selected', ['selected','approved'])->count() ?? 0,
            ];
        })->toArray();
        return response()->json(['status' => true, 'data' => $inquiries, 'message' => 'Booking inquiries retrieved successfully']);
    }

}