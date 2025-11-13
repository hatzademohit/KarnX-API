<?php
namespace App\Http\Controllers\Api\InquiryOperators\KXManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Auth;
use App\Models\Client;
use App\Models\Asset;

class InquiryOperatorsController extends Controller
{
    public function getOperators(Request $request)
    {
        
        try{
           $query = Client::where(['is_active' => 1, 'type' => 'Aircraft Operator'])->select('clients.id', 'clients.name',  DB::raw("4.8 as rating"),
            DB::raw("2587 as flights"),
            DB::raw("'< 2 hours' as duration"),
            DB::raw("JSON_ARRAY('Luxury Travel', 'Group Charters', 'VIP Transport') as tags"), 
            DB::raw('(SELECT count(a. id) FROM assets AS a WHERE a.client_id = clients.id AND a.is_active = 1) as aircraft'))->orderBy('name', 'asc')->get()->map(function ($item) {
                $item->tags = json_decode($item->tags);
                return $item;
            });
            
            return response()->json(['status' => true, 'data' => $query, 'message' => 'Operators fetched successfully'], 200);
        }catch (\Exception $e){
            return response()->json(['status' => false, 'data' => [], 'message' => 'Error fetching operators'], 500);
        }
        
    }

    public function assignOperators(Request $request){
        
        return response()->json(['status' => true, 'data' => $request->all(), 'message' => 'Operators added successfully'], 200);
    }

    public function removeOperator(Request $request){
        return response()->json(['status' => true, 'data' => $request->all(), 'message' => 'Operators deleted successfully'], 200);
    }
    public function getAssignedOperators(Request $request){
        $operator = [
            [
            'id' => 1,
            'name' => 'Elite Aviation Services ggg',
            'rating' => 4.9,
            'flights' => 2847,
            'safety_rating' => 'ARGUS Gold',
            'response_time' => '< 2 hours',
            'fleet_overview' => [
                'total_aircraft' => 6,
                'aircraft_types' => [
                    ['type' => 'Light', 'count' => 1],
                    ['type' => 'Mid-Size', 'count' => 2]
                ]
            ],
            'operating_regions' => ['Delhi', 'Mumbai', 'Pune'],
            'certifications' => ['IS-BAO', 'Wyvern Wingman', 'ARGUS Gold'],
            'contact_methods' => [
                'email' => true,
                'call' => true,
                'website' => true
            ],
            'specialties' => ['IS-BAO', 'Wyvern Wingman', 'ARGUS Gold']
        ],
        [
            'id' => 2,
            'name' => 'Elite Aviation Services 2',
            'rating' => 4.9,
            'flights' => 2847,
            'safety_rating' => 'ARGUS Gold',
            'response_time' => '< 2 hours',
            'fleet_overview' => [
                'total_aircraft' => 6,
                'aircraft_types' => [
                    ['type' => 'Light', 'count' => 1],
                    ['type' => 'Mid-Size', 'count' => 2]
                ]
            ],
            'operating_regions' => ['Delhi', 'Mumbai', 'Pune'],
            'certifications' => ['IS-BAO', 'Wyvern Wingman', 'ARGUS Gold'],
            'contact_methods' => [
                'email' => true,
                'call' => true,
                'website' => true
            ],
            'specialties' => ['IS-BAO', 'Wyvern Wingman', 'ARGUS Gold']
        ],

        [        
            'id' => 3,
            'name' => 'Elite Aviation Services 3',
            'rating' => 4.9,
            'flights' => 2847,
            'safety_rating' => 'ARGUS Gold',
            'response_time' => '< 2 hours',
            'fleet_overview' => [
                'total_aircraft' => 6,
                'aircraft_types' => [
                    ['type' => 'Light', 'count' => 1],
                    ['type' => 'Mid-Size', 'count' => 2]
                ]
            ],
            'operating_regions' => ['Delhi', 'Mumbai', 'Pune'],
            'certifications' => ['IS-BAO', 'Wyvern Wingman', 'ARGUS Gold'],
            'contact_methods' => [
                'email' => true,
                'call' => true,
                'website' => true
            ],
            'specialties' => ['IS-BAO', 'Wyvern Wingman', 'ARGUS Gold']
        ]
    ];

        return response()->json(['status' => true, 'data' => $operator, 'message' => 'Operators fetched successfully'], 200);
    }
}

