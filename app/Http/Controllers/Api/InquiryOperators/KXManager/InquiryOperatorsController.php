<?php
namespace App\Http\Controllers\Api\InquiryOperators\KXManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Auth;
use App\Models\Client;
use App\Models\Asset;
use App\Models\BookingInquiries\BookingInquiriesAssignOperators;
use App\Models\FormFieldsData\AirportCities;
use App\Models\BookingInquiries\BookingInquiriesStatuses;

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
        try {
            
            if (!empty($request->inquiry_id) && $request->operator_ids > 0){
                foreach ($request->operator_ids as $operator_id) {
                    $lookup = ['booking_inquiries_id' => $request->inquiry_id, 'operator_id' => $operator_id];
                    $payload = ['manager_id' => Auth::user()->id];
                    $isAssigned = BookingInquiriesAssignOperators::updateOrCreate($lookup, $payload);
                    if ($isAssigned->wasRecentlyCreated) {
                        setInquiryStatuses($request->inquiry_id, [4,4], [Auth::user()->client_id, $operator_id]);
                    }                                   
                }
                return response()->json(['status' => true, 'message' => 'Operators assigned successfully'], 200);
            } else {
                return response()->json(['status' => false, 'message' => 'No operators selected or inquiry ID not provided'], 400);
            }
        } catch (Error $e) {
           return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
       
    }

    public function removeOperator(Request $request, $id){
        try {
          BookingInquiriesAssignOperators::find($id)->delete();
          return response()->json(['status' => true, 'message' => 'Operator removed successfully'], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json(['status' => false, 'message' => $th->getMessage()], 500);
        }
    }
    public function getAssignedOperators(Request $request){
        $operators = [];
        if(!empty($request->inquiry_id)){
        
            $assignments = BookingInquiriesAssignOperators::with([
                'client',
                'client.assets',
                'client.assets.aircraftType'
            ])
            ->where('booking_inquiries_id', $request->inquiry_id)
            ->get();
        
            $operators = $assignments->map(function ($item) {

                $client = $item->client;
                $cityIds = explode(',', $client->operating_reginons);
                $cities = AirportCities::whereIn('id', $cityIds)->pluck('city_name');
                
                $aircraftTypes = $client->assets->groupBy('aircraft_type_id')->map(function ($groups) {               
                    return [
                        'type' => $groups->map(function ($group) { 
                                        return $group->aircraftType->name; 
                                })[0],
                        'count' => $groups->count()
                    ];
                })->values();          
            
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'rating' => '4.8 as rating',
                    'flights' => 2587,
                    'safety_rating' => explode(',', $client->safety_ratings),
                    'response_time' => $client->response_time,
                    'fleet_overview' => [
                        'total_aircraft' => $client->assets->where('is_active', 1)->count(),
                        'aircraft_types' => $aircraftTypes,                    
                    ],
                    'operating_regions' =>  $cities,
                    'certifications' => explode(',', $client->certifications),
                    'contact_methods' => [
                        'email' => $client->email,
                        'call' => $client->phone,
                        'website' => $client->website
                    ],
                    'specialties' => explode(',', $client->specialties),
                ];
            });    
        }
        return response()->json(['status' => true, 'data' => $operators, 'message' => 'Operators fetched successfully'], 200);
    }
}

