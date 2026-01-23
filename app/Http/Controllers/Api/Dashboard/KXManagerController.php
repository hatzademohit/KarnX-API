<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BookingInquiries\BookingInquiries;
use Illuminate\Support\Facades\Auth;
use App\Models\FormFieldsData\AirportCities;
use App\Models\FormFieldsData\AirCraftTypes;
use Carbon\Carbon;
use App\Models\Client;
use App\Models\User;
use App\Models\SLAPolicies;

class KXManagerController extends Controller
{
    public function cardCount()
    { 
        $clientDecision = BookingInquiries::Join('booking_inquiry_process_statuses as a', 'a.booking_inquiries_id', '=', 'booking_inquiries.id')->Join('booking_status as b', 'b.id', '=', 'a.status_id')->Join('inquiry_quote_details as iqd', 'iqd.booking_inquiries_id', '=', 'booking_inquiries.id')->where('iqd.is_selected', 'selected')->where(['a.is_active' => 1])->whereIn('b.id', [7]);
        $newInquiries = BookingInquiries::Join('booking_inquiry_process_statuses as a', 'a.booking_inquiries_id', '=', 'booking_inquiries.id')->Join('booking_status as b', 'b.id', '=', 'a.status_id')->where(['a.is_active' => 1])->whereMonth('booking_inquiries.created_at', now()->month)->whereIn('b.id', [3]);

        $quotePending = BookingInquiries::Join('booking_inquiry_process_statuses as a', 'a.booking_inquiries_id', '=', 'booking_inquiries.id')
                ->Join('booking_status as b', 'b.id', '=', 'a.status_id')
                ->where(['a.is_active' => 1])->whereMonth('booking_inquiries.created_at', now()->month)->whereIn('b.id', [4]);
        $confirmedBooking = BookingInquiries::join('booking_inquiry_process_statuses as a', 'a.booking_inquiries_id', '=', 'booking_inquiries.id')->join('booking_status as b', 'b.id', '=', 'a.status_id')->where('a.is_active', 1)->whereIn('b.id', [11])->distinct('booking_inquiries.id');
                                        
        return response()->json([
            'success' => true,
            'data' => [
                'new_inquiries' => $newInquiries->count(),
                'new_inquiries_ids' => $newInquiries->select('booking_inquiries.id')->get()->map(function($val){ return $val->id;}),

                'quote_pending' => $quotePending->count(),  
                'quote_pending_ids' => $quotePending->select('booking_inquiries.id')->get()->map(function($val){ return $val->id;}),  

                'clients_decision' => $clientDecision->count(),
                'clients_decision_ids' => $clientDecision->select('booking_inquiries.id')->get()->map(function($val){ return $val->id;}),
                'clients_decision_expiring_soon' => $clientDecision->whereBetween('validate_till', [now(), now()->addDays(8)])->count(),

                'confirmed_booking' => $confirmedBooking->count('booking_inquiries.id'),
                'confirmed_booking_ids' => $confirmedBooking->select('booking_inquiries.id')->get()->map(function($val){ return $val->id;}),

                'response_time' => 0,
                'response_time_ids' => [],
            ],
        ]);
    } 

    public function getActivityTimeline(){

        $latestPerBooking = \DB::table('booking_inquiry_process_statuses as a')
        ->join(
        \DB::raw('(
            SELECT booking_inquiries_id, MAX(id) AS max_id
            FROM booking_inquiry_process_statuses
            WHERE is_active = 1 AND user_client_id = ' . (int) Auth::user()->client_id . ' AND status_id in (3, 6, 19)
            GROUP BY booking_inquiries_id
        ) latest'),
        function ($join) {
            $join->on('latest.max_id', '=', 'a.id');
        }
        )
        ->select('a.booking_inquiries_id', 'a.status_id', 'a.updated_by', 'a.created_at');
        
        $query = BookingInquiries::with([
                'flightDetails',
                'aircraftPreference',
                'contactInformation',
                'cateringServices',
                'crewRequirements',
                'medicalAssistance',
                'petTravels',
                'documents',
                'assigndOperators',
                'assignedQuotes'
            ])
            ->leftJoinSub($latestPerBooking, 'ps', function ($join) {
                $join->on('ps.booking_inquiries_id', '=', 'booking_inquiries.id');
            })
            ->leftJoin('booking_status as b', 'b.id', '=', 'ps.status_id')
            ->leftJoin('clients as c', 'c.id', '=', 'booking_inquiries.manager_id')
            ->select('booking_inquiries.*', 'b.status_name', 'b.id as status_id', 'b.color_code', 'c.name as operator', 'ps.updated_by', 'ps.created_at as status_added_date');
        
        $bookings = $query->orderByDesc('ps.created_at')->get();
       
        // Map to desired format
        $data = $bookings->map(function ($item) {
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
            
            // Client name
            $client = null;
            if(isset($item->updated_by)){
                $client = User::find($item->updated_by)->getClient()->name;
            }

            // Status actions
            $status = ''; $status_color = '';
            if ($item->status_name) {
                $status = $item->status_name;
                $status_color = $item->color_code;
            }
            
            // Date formatting
            $formattedDate = null;
            if($item->flightDetails){
                $formattedDate = $item->flightDetails[0]->departure_time !== null ? date('F d, Y', strtotime($item->flightDetails[0]->departure_time)) : null;
            }
            
            $past = Carbon::parse($item->status_added_date);
            $now  = Carbon::now();
            $diff = Carbon::parse($past)->diffForHumans(['parts' => 2, 'short' => true,]);
            if($status != ''){
                $title = [
                    3 => 'New inquiry received', 
                    6 => 'Quote received - '. $item->assigndOperators->count().'/'.$item->assignedQuotes->count(), 
                    19 => 'Re-quote request from client', 
                ];
                return [
                'id' => $item->id,
                'inquiryId' => $item->booking_reference ?? null,
                'icon' => `<Flight color="primary" />`,
                'title' => $title[$item->status_id],
                'time' => $diff,
                'details' => $route.' • '.$formattedDate.' • '.$item->passenger_info_total.' passenger(s)',
                'subtitle' => $client,
                'status' => $status,
                'status_color' => $status_color,
                'created_on' => date("F d, Y \a\\t h:i A", strtotime($item->created_at)),
                'status_id' => $item->status_id,
                'quote_received' => $item->assignedQuotes->count() ?? 0,
                'operator_assigned' => $item->assigndOperators->count() ?? 0,
            ];
            }
            
        })->toArray();

        return response()->json([
            'success' => true,
            'data' => array_filter($data),
        ]);
    }

    public function getPriorityTask(){
        $latestPerBooking = \DB::table('booking_inquiry_process_statuses as a')
        ->join(
        \DB::raw('(
            SELECT booking_inquiries_id, MAX(id) AS max_id
            FROM booking_inquiry_process_statuses
            WHERE is_active = 1 AND user_client_id = ' . (int) Auth::user()->client_id . ' AND status_id in (3, 6, 19)
            GROUP BY booking_inquiries_id
        ) latest'),
        function ($join) {
            $join->on('latest.max_id', '=', 'a.id');
        }
        )
        ->select('a.booking_inquiries_id', 'a.status_id', 'a.updated_by', 'a.created_at');
        
        $query = BookingInquiries::with([
                'flightDetails',
                'aircraftPreference',
                'contactInformation',
                'cateringServices',
                'crewRequirements',
                'medicalAssistance',
                'petTravels',
                'documents',
                'assigndOperators',
                'assignedQuotes'
            ])
            ->leftJoinSub($latestPerBooking, 'ps', function ($join) {
                $join->on('ps.booking_inquiries_id', '=', 'booking_inquiries.id');
            })
            ->leftJoin('booking_status as b', 'b.id', '=', 'ps.status_id')
            ->leftJoin('clients as c', 'c.id', '=', 'booking_inquiries.manager_id')
            ->select('booking_inquiries.*', 'b.status_name', 'b.id as status_id', 'b.color_code', 'c.name as operator', 'ps.updated_by', 'ps.created_at as status_added_date');
        
        $bookings = $query->orderByDesc('ps.created_at')->get();
       
        $slaPolicies = SLAPolicies::where('task_slug', 'priority_task')->select('name', 'priority', 'response_hours')->orderBy('priority', 'asc')->get();
        $taskDetails = [];
        foreach($bookings as $item){
            // Client name
            $client = null;
            if(isset($item->updated_by)){
                $client = User::find($item->updated_by)->getClient()->name;
            }
            // Status actions
            $status = ''; $status_color = '';
            if ($item->status_name) {
                $status = $item->status_name;
                $status_color = $item->color_code;
            }

            $deadline = null;
            if($item->status_id === 3){
                $deadline = Carbon::parse($item->flightDetails[0]->departure_time);
            }else if($item->status_id === 6){
                $quoteEndDate = $item->assignedQuotes->min('validate_till');
                $deadline = Carbon::parse($quoteEndDate);
            }else{
                $deadline = Carbon::parse($item->flightDetails[0]->departure_time);
            }            
            $remainingHours = now()->diffInHours($deadline, false);
            $remainingHours = round($remainingHours);
            $highRange = $slaPolicies[0]->response_hours;
            $midiumRange = $slaPolicies[1]->response_hours;
            $lowRange = $slaPolicies[2]->response_hours;
            $high = $mid = $low = [];
            switch ($remainingHours) {
                case ($remainingHours <= $highRange && $remainingHours > 0):
                    # HIGH...
                    $high = array(
                        'status' => $status,
                        'status_color' => $status_color,
                        'created_on' => date("F d, Y \a\\t h:i A", strtotime($item->created_at)),
                        'status_id' => $item->status_id,
                        'quote_received' => $item->assignedQuotes->count() ?? 0,
                        'operator_assigned' => $item->assigndOperators->count() ?? 0,

                        'id' => $item->id,
                        'type' => 'High',
                        'task_name' => 'Overdue Operator Response',
                        'description' => 'Premium Jets',
                        'client' => $client,
                        'inquiry_number' => $item->booking_reference,
                        'time_overdue' => formatHours($remainingHours)         
                    );
                    
                    break;                
                case ($remainingHours <= $midiumRange && $remainingHours > $midiumRange):
                    # MEDIUM...
                    $mid = array(
                        'status' => $status,
                        'status_color' => $status_color,
                        'created_on' => date("F d, Y \a\\t h:i A", strtotime($item->created_at)),
                        'status_id' => $item->status_id,
                        'quote_received' => $item->assignedQuotes->count() ?? 0,
                        'operator_assigned' => $item->assigndOperators->count() ?? 0,

                        'id' => $item->id,
                        'type' => 'Medium',
                        'task_name' => 'Overdue Operator Response',
                        'description' => 'Premium Jets',
                        'client' => $client,
                        'inquiry_number' => $item->booking_reference,
                        'time_overdue' => formatHours($remainingHours)
                    
                    );
                   
                    break;
                case ($remainingHours <= $lowRange && $remainingHours > 0):
                    # LOW...
                    $low = array(
                        'status' => $status,
                        'status_color' => $status_color,
                        'created_on' => date("F d, Y \a\\t h:i A", strtotime($item->created_at)),
                        'status_id' => $item->status_id,
                        'quote_received' => $item->assignedQuotes->count() ?? 0,
                        'operator_assigned' => $item->assigndOperators->count() ?? 0,

                        'id' => $item->id,
                        'type' => 'Low',
                        'task_name' => 'Overdue Operator Response',
                        'description' => 'Premium Jets',
                        'client' => $client,
                        'inquiry_number' => $item->booking_reference,
                        'time_overdue' => formatHours($remainingHours)
                    
                    );
                    break;
            }
            
            $taskDetails['high'][] = $high;
            $taskDetails['mid'][] = $mid;
            $taskDetails['low'][] = $low;
        }
       // dd($taskDetails);
        $data = [
            [
                'id' => 1,
                'icon' => 'warning',  
                'title' => 'High Priority',
                'priority' => 'high',
                'bg' => '#fef2f2',
                'color' => '#dc2626',
                'hover_bg' => '#fee2e2',
                'border_color' => '#fecaca',
                'tasks_details' => array_values(array_filter($taskDetails['high']))
            ],
            [
                'id' => 2,
                'icon' => 'info',
                'title' => 'Medium Priority',
                'priority' => 'medium',
                'bg' => '#fff7ed',
                'color' => '#f97316',
                'hover_bg' => '#ffedd5',
                'border_color' => '#fed7aa',
                'tasks_details' => array_values(array_filter($taskDetails['mid']))
            ],
            [
                'id' => 3,
                'icon' => 'info',
                'title' => 'Low Priority',
                'priority' => 'low',
                'bg' => '#eff6ff',
                'color' => '#3b82f6',
                'hover_bg' => '#dbeafe',
                'border_color' => '#bfdbfe',
                'tasks_details' => array_values(array_filter($taskDetails['low']))
            ],
        ];
        $total_task = [
            'total_pending_task' => count(array_filter($taskDetails['high'])) + count(array_filter($taskDetails['mid'])) + count(array_filter($taskDetails['low'])),
        ];
       
        return response()->json([
            'success' => true,
            'data' => $data,
            //'priorityCount' => $highPriorityCount. ' High Priority', // get count of high priority task only
            'total_task' => $total_task,
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
                'assigndOperators',
                'assignedQuotes'
            ])
            ->leftJoinSub($latestPerBooking, 'ps', function ($join) {
                $join->on('ps.booking_inquiries_id', '=', 'booking_inquiries.id');
            })
            ->leftJoin('booking_status as b', 'b.id', '=', 'ps.status_id')
            ->leftJoin('clients as c', 'c.id', '=', 'booking_inquiries.manager_id')
            ->select('booking_inquiries.*', 'b.status_name', 'b.id as status_id', 'b.color_code', 'c.name as operator');
        
        if ($request->has('ids')) {
            $query->whereIn('booking_inquiries.id', explode(',', $request->ids));
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

            $quoteValue = $item->assignedQuotes->whereIn('is_selected', ['selected','approved'])->first();
            $quoteAmt = '-'; //$operatorName = '-';
            if($quoteValue){
                $quoteAmt = $quoteValue->total + ((((float)$quoteValue->total / 100) * (float)$quoteValue->kx_mgr_commission_per) ?? 0);
                $quoteAmt = $quoteValue->travel_agent_commission_per != null ? $quoteAmt + (($quoteAmt / 100) * $quoteValue->travel_agent_commission_per) : $quoteAmt;
                $quoteAmt = number_format($quoteAmt);

                //$operatorName = Client::where('id', $quoteValue->client_id)->first('name')->name;
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
                'operators' => $item->assigndOperators->count().'/'.$item->assignedQuotes->count().' responses',
                'value' => $quoteAmt,
                'status_id' => $item->status_id,
                'quote_received' => $item->assignedQuotes->count() ?? 0,
                'operator_assigned' => $item->assigndOperators->count() ?? 0,
            ];
        })->toArray();
        return response()->json(['status' => true, 'data' => $inquiries, 'message' => 'Booking inquiries retrieved successfully']);
    }
}
