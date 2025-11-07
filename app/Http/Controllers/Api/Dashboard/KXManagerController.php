<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\bookingInquiries\BookingInquiries;
class KXManagerController extends Controller
{
    public function cardCount()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'new_inquiries' => BookingInquiries::where('status_id', 1)->count(),
                'quote_pending' => BookingInquiries::where('status_id', 1)->count(),
                'clients_decision' => BookingInquiries::where('status_id', 2)->count(),
                'confirmed_booking' => BookingInquiries::where('status_id', 3)->count(),
                'response_time' => BookingInquiries::where('status_id', 4)->count(),
                'response_time' => BookingInquiries::where('status_id', 5)->count(),
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
}
