<?php

namespace App\Http\Controllers\Api\InquiryDetails;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BookingInquiries\BookingInquiries;
use DB;
use App\Models\FormFieldsData\AirCraftTypes;
use App\Models\Client;
use App\Models\FormFieldsData\AirportCities;
use App\Models\BookingStatus;
use Auth;
use App\Models\FormFieldsData\TravelingPurpose;
use App\Models\BookingInquiries\BookingInquiriesAircraftPreference;
use App\Models\BookingInquiries\BookingInquiriesCateringService;
use App\Models\FormFieldsData\CateringDietary;
use App\Models\FormFieldsData\CrewRequirements;
use App\Models\BookingInquiries\BookingInquiriesPetTravel;
use App\Models\BookingInquiries\BookingInquiriesMedicalAssistance;
use App\Models\BookingInquiries\BookingInquiriesDocument;

class InquiryDetailsController extends Controller
{
    public function index(Request $request, $id)
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

        if ($id) {
            $query->where('id', $id);
        }
        

        $booking = $query->get();
        //dd($booking);
        // Map to desired format
        $inquiries = $booking->map(function ($item) {
            // Compose route
            $route = [];
            if ($item->flightDetails) {
                $flightDetails = $item->flightDetails;
                foreach($flightDetails as $fd){
                    $dep = $fd->departure_location;
                    $arr = $fd->arrival_location;
                    $dep = AirportCities::find($dep)->code;
                    $arr = AirportCities::find($arr)->code;
                    // $route[] = $dep . ' → ' . $arr;
                    $depTime = 'NA';
                    $retTime = 'NA'; 
                    //dd($fd->departure_time);
                    if($item->is_flexible_dates === 0){
                        $depTime = date('F d, Y \a\\t h:i A', strtotime($fd->departure_time));
                        $retTime = $fd->return_date_time?date('F d, Y \a\\t h:i A', strtotime($fd->return_date_time)):'';
                    }
                    
                    $route[] = ['departure_location' => $dep, 'arrival_location' => $arr, 'flight_departure_time' => $depTime, 'flight_return_time' => $retTime];
                }                
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
            // $formattedDate = null;
            // if($item->flightDetails){
            //     $formattedDate = $item->flightDetails->departure_time !== null ? date('F d, Y', strtotime($item->flightDetails->departure_time)) : null;
            // }
            $travel_purpose = null;
            if($item->travel_purpose_id){
                $travel_purpose = TravelingPurpose::find($item->travel_purpose_id)->name;
            }
            //return response()->json(['status' => false, 'datasss' => $item ]);
            $requested_service = [];
            if($item->cateringServices){
                if($item->cateringServices->dietary_required){
                    $requested_service = CateringDietary::whereIn('id', json_decode($item->cateringServices->dietary_required))->select('name')->pluck('name')->toArray();
                }
                if($item->cateringServices->allergy_notes){
                    $requested_service[] = $item->cateringServices->allergy_notes;
                }

                if($item->cateringServices->drink_preferences){
                    $requested_service[] = $item->cateringServices->drink_preferences;
                }
                if($item->cateringServices->custom_services){
                    $requested_service[] = $item->cateringServices->custom_services;
                }
            }

            
            $crewRequirements = [];
            if($item->crewRequirements){
                $crewReqIds = json_decode($item->crewRequirements->crew_req_id);
                foreach($crewReqIds as $key => $crewReqId){ 
                    if($crewReqId != null && (int)$crewReqId > 0){
                        $record = CrewRequirements::from('inquiry_crew_requirements_options as a')
                        ->join('inquiry_crew_requirements_options as b', 'b.id', '=', 'a.self_parent_id')
                        ->where('a.id', $crewReqId)
                        ->select('a.name as child', 'b.name as parent')
                        ->first();
                        $crewRequirements[] = array('lable' => $record->parent, 'value' => $record->child);
                    } 
                }
                
                if($item->crewRequirements->additional_notes){
                    $crewRequirements[] = array('lable' => 'Additional Notes', 'value' => $item->crewRequirements->additional_notes);
                }
            }

            $petTravels = [];
            if($item->petTravels){
                $petDetail = BookingInquiriesPetTravel::where('booking_inquiries_id', $item->id)->first();
               
                if($petDetail){
                    $petTravels[] = array('lable' => 'Pet Type', 'value' => $petDetail->pet_type);
                    $petTravels[] = array('lable' => 'Pet Size', 'value' => $petDetail->pet_size);
                    $petTravels[] = array('lable' => 'Additional Notes', 'value' => $petDetail->additional_notes);
                }
            }

            
            $medicalReqAssistance = [];
            if($item->medicalAssistance){
                $medicalReq = BookingInquiriesMedicalAssistance::from('booking_inquiries_medical_need_assist as a')
                        ->join('medical_assistance_needed_option as b', 'b.id', '=', 'a.medical_assist_id')
                        ->where('a.booking_inquiries_id', $item->id)
                        ->select('b.name', 'a.other_requirements')
                        ->get();
                
                foreach($medicalReq as $key => $req){                     
                    if($req != null){    
                        $otherOption = ($req->other_requirements === null)?'':': '.$req->other_requirements;                    
                        $medicalReqAssistance[] = $req->name.$otherOption;
                    } 
                }
            }
            
            $uploadedDocumentsFiles = $requiredDocumentsname = [];
            if($item->documents){
                $documents = BookingInquiriesDocument::from('booking_inquiries_documents as a')
                            ->join('required_document_option as b', function ($join) {
                                $join->on(DB::raw('JSON_CONTAINS(a.document_type, CAST(b.id AS CHAR), "$")'), '=', DB::raw('1'));
                            })
                            ->where('a.booking_inquiries_id', $item->id)
                            ->select('b.name', 'a.document_path')
                            ->get();
                
                $uploadedDocumentsFiles = array_unique(array_map(function ($val) {
                                                return $val['document_path'];
                                            }, $documents->toArray()));
                $requiredDocumentsname = array_unique(array_map(function ($val) {
                                                return $val['name'];
                                            }, $documents->toArray()));
                
            }
            
            return [
                'id' => $item->id,
                'inquiryId' => $item->booking_reference ?? null,
                'priority' => $priority,
                'route' => $route,
                'clientName' => $item->contactInformation->contact_name,
                'flexible_date_range' => $item->flexible_range ?? 'NA',
                'clientEmail' => $item->contactInformation->contact_email,
                'clientPhone' => $item->contactInformation->contact_phone,
                'checkedBag' => $item->checked_bag ?? 0,
                'carryOnBag' => $item->carry_bag ?? 0,
                'is_traveling_pets' => $item->is_traveling_pets == 0 ?'No':'Yes',
                'inquiryDate' => $bookingDate,
                'created_on' => date("F d, Y \a\\t h:i A", strtotime($item->created_at)),
                //'date' => $formattedDate,
                'passangers' => $item->passenger_info_total ?? null,
                'aircraft' => $aircraftType,
                'traveling_purpose' => $travel_purpose,
                'assign' => $assign,
                'status' => $status,
                'status_color' => $status_color,
                'operators' => 'opt',
                'value' => 'val',
                'requested_service' => $requested_service ?? [],
                'crew_requirements' => $crewRequirements ?? [],
                'pet_travels' => $petTravels ?? [],
                'medical_assistance_req' => $medicalReqAssistance ?? [],
                'uploaded_documents_path' => $uploadedDocumentsFiles ?? [],
                'required_documents_name' => $requiredDocumentsname ?? [],
                'trip_type' => ucwords(str_replace('_', ' ', $item->trip_type)) ?? 'NA',
                'is_flexible_date' => $item->is_flexible_dates ?? 0,
            ];
        })->toArray();
        return response()->json(['status' => true, 'data' => $inquiries[0] ?? [], 'message' => 'Booking inquiries retrieved successfully', ]);
    }
}
