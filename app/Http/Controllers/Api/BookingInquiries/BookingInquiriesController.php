<?php

namespace App\Http\Controllers\Api\BookingInquiries;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BookingInquiries\BookingInquiries;
use DB;
use App\Models\FormFieldsData\AirCraftTypes;
use App\Models\Client;
use App\Models\FormFieldsData\AirportCities;
use App\Models\BookingStatus;
use App\Models\BookingInquiries\BookingInquiriesStatuses;

class BookingInquiriesController extends Controller
{
    /**
     * List all booking inquiries
     */
    public function index(Request $request)
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
    //     $query = BookingInquiries::with([
    //         'cateringServices',
    //         'contactInformation',
    //         'crewRequirements',
    //         'documents',
    //         'aircraftPreference',
    //         'flightDetails',
    //         'petTravels',
    //         'medicalAssistance'
    //     ]);
  
    //     if($request->has('client_id')) {
    //         $query->where('client_id', $request->client_id);
    //     }

    //     if ($request->has('search')) {
    //         $query->where('booking_reference', 'like', '%' . $request->search . '%');
    //     }

    //     return $query->paginate($request->per_page ?? 10);
    // }

    /**
     * Create a new booking inquiry (with children)
     */
    public function store(Request $request)
    {        
       
       DB::beginTransaction();
        try {
            $payload = json_decode($request->input('payload'), true);
            $data['requester_id'] = auth()->user()->id;
            $data['client_id'] = auth()->user()->client_id;
            $data['booking_reference'] = 'INQ-'.date('Ym').'-'. str_pad(BookingInquiries::max('id') + 1, STR_PAD_LEFT);
            $data['is_confirmed'] = 0;
            $data['status_id'] = 1;
            $data['remarks'] = '';
            $data['booking_date'] = date('Y-m-d H:i:s');

            $data['trip_type'] = $payload['flightDetails']['trip_type'] ?? '';
            $data['is_flexible_dates'] = 0;//$payload['flightDetails']['is_flexible_dates'] ?? '';
            $data['flexible_range'] = '';//$payload['flightDetails']['flexible_range'] ?? '';

            $data['passanger_info_adults'] = $payload['passengerInfo']['passanger_info_adults'] ?? '';
            $data['passanger_info_children'] = $payload['passengerInfo']['passanger_info_children'] ?? '';
            $data['passanger_info_infants'] = $payload['passengerInfo']['passanger_info_infants'] ?? '';
            $data['passenger_info_total'] = $payload['passengerInfo']['passenger_info_total'] ?? '';

            $data['checked_bag'] = $payload['passengerInfo']['checked_bag'] ?? '';  
            $data['carry_bag'] = $payload['passengerInfo']['carry_bag'] ?? '';
            $data['oversized_item'] = $payload['passengerInfo']['oversized_items'] ?? '';

            $data['is_traveling_pets'] = $payload['passengerInfo']['is_traveling_pets'] ?? '';
            $data['is_medical_assistance_req'] = $payload['passengerInfo']['is_medical_assistance_req'] ?? '';
            
            $data['travel_purpose_id'] = $payload['passengerInfo']['travel_purpose_id'] ?? '';
            $data['other_travel_purpose'] = '';//$payload['passengerInfo']['other_travel_purpose'] ?? '';

            $data['is_catering_service_req'] = $payload['passengerInfo']['is_catering_service_req'] ?? '';        
          
        //  return response()->json(['status' => false, 'message' => 'Booking inquiry created successfully', 'data' => $data, 'error' => $payload->input('flightDetails.trip_type')], 500);
        //    try {
            $booking = BookingInquiries::create($data);

            // Save relations
            $this->saveRelations($booking, $request);
            if($booking->loadRelations()){
                $user = auth()->user()->client_id;
                $defaultClient = getDefualtClient();
                $forUser = [$user, $defaultClient];
                $byStatus = [2, 3];
                foreach ($forUser as $key => $usr) {
                    BookingInquiriesStatuses::create([
                        'booking_inquiries_id' => $booking->id,
                        'status_id' => $byStatus[$key], //Requested
                        'user_client_id' => $usr,
                    ]);
                }
            }
            DB::commit();
            return response()->json(['status' => true, 'message' => 'Booking inquiry created successfully', 'data' => $booking->loadRelations()], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Show a single booking inquiry
     */
    public function show($id)
    {       
        $booking = BookingInquiries::withRelations()->find($id);
        return response()->json(['status' => true, 'data' => $booking->loadRelations(), 'message' => 'Booking inquiry retrieved successfully']);
    }

    /**
     * Update booking inquiry & children
     */
    public function update(Request $request, $id)
    {
        
        DB::beginTransaction();
        try {
            $booking = BookingInquiries::findOrFail($id);
            $booking->update($request->only([
                'is_flexible_dates', 'flexible_range', 'trip_type', 'checked_bag', 'carry_bag', 'oversized_item',
                'passanger_info_adults', 'passanger_info_children', 'passanger_info_infants', 'passenger_info_total', 'is_traveling_pets',
                'is_medical_assistance_req', 'travel_purpose_id', 'other_travel_purpose', 'is_catering_service_req'
            ]));

            // Delete old relations & save new
            $booking->cateringServices()->delete();
            $booking->contactInformation()->delete();
            $booking->crewRequirements()->delete();
            $booking->documents()->delete();
            $booking->aircraftPreference()->delete();
            $booking->medicalAssistance()->delete();
            $booking->petTravels()->delete();
            $booking->flightDetails()->delete();
             /*
            $booking->passengersInformation()->delete();*/

            $this->saveRelations($booking, $request);

            DB::commit();
            return response()->json(['status' => true, 'data' => $booking->loadRelations(), 'message' => 'Booking inquiry updated successfully']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a booking inquiry (children deleted via FK cascade)
     */
    public function destroy($id)
    {
        $booking = BookingInquiries::findOrFail($id);
        $booking->delete();

        return response()->json(['status' => true, 'message' => 'Booking Inquiry deleted successfully']);
    }

    /**
     * Helper: Save all related tables
     */
    private function saveRelations(BookingInquiries $booking, Request $request)
    {
        $reqParam = $request;
        $payload = json_decode($request->input('payload'), true); 
        $request->merge(['payload' => $payload]);
        if ($request->has('payload.flightDetails')) {
            foreach ($request->input('payload.flightDetails.departure_location') as $index => $departure_location) {
                $flight_details['booking_inquiries_id'] = $booking->id;
                $flight_details['departure_location'] = $departure_location['id'];
                $flight_details['arrival_location'] = $request->input('payload.flightDetails.arrival_location')[$index]['id'];
                $flight_details['departure_time'] = date('Y-m-d H:i:s', strtotime($request->input('payload.flightDetails.departure_time')[$index]));
                if(count($request->input('payload.flightDetails.departure_location')) > 1 && count($request->input('payload.flightDetails.departure_location')) === $index + 1){
                    $flight_details['return_date_time'] = date('Y-m-d H:i:s', strtotime($request->input('payload.flightDetails.departure_time')[$index]));
                }
                $booking->flightDetails()->create($flight_details);
            }
        }

        if ($request->has('payload.passengerInfo') && $request->input('payload.passengerInfo.is_traveling_pets') === true) {
            if(!empty($request->input('payload.passengerInfo.pet_travels.pet_type'))){
                $booking->petTravels()->create($request->input('payload.passengerInfo.pet_travels'));
            }            
        }
        
        if ($request->has('payload.passengerInfo') && $request->input('payload.passengerInfo.is_medical_assistance_req') === true) {
            foreach ($request->input('payload.passengerInfo.medical_assistance')['medical_assist_id'] as $assistanceId) {
                $medicalAssistance['booking_inquiries_id'] = $booking->id;
                $medicalAssistance['medical_assist_id'] = $assistanceId;
                if($assistanceId === 6){
                    $medicalAssistance['other_requirements'] = $request->input('payload.passengerInfo.medical_assistance.other_requirements');
                }

                $booking->medicalAssistance()->create($medicalAssistance);
            }
        }

        if ($request->has('payload.passengerInfo.aircraft_preference')) {
            foreach ($request->input('payload.passengerInfo.aircraft_preference') as $aircraft_preference) {
                $aircrafpreference['booking_inquiries_id'] = $booking->id;
                $aircrafpreference['aircraft_type_id'] = $aircraft_preference;
                $booking->aircraftPreference()->create($aircrafpreference);
            }
        }

        if ($request->has('payload.passengerInfo.crew_requirements')) {
            $crew_requirements['booking_inquiries_id'] = $booking->id;
            $crew_requirements['additional_notes'] = $request->input('payload.passengerInfo.crew_requirements')['additional_notes'];
            $crew_requirements['crew_req_id'] = json_encode($request->input('payload.passengerInfo.crew_requirements.services'));
            $booking->crewRequirements()->create($crew_requirements);
        }

        
        if ($request->has('payload.passengerInfo.catering_services') && $request->input('payload.passengerInfo.is_catering_service_req') === true) {
            $catering_services['booking_inquiries_id'] = $booking->id;
            $catering_services['dietary_required'] = json_encode($request->input('payload.passengerInfo.catering_services.dietary_required'));
            $catering_services['allergy_notes'] = $request->input('payload.passengerInfo.catering_services.allergy_notes');
            $catering_services['drink_preferences'] = $request->input('payload.passengerInfo.catering_services.drink_preferences');
            $catering_services['custom_services'] = $request->input('payload.passengerInfo.catering_services.custom_services');
            $booking->cateringServices()->create($catering_services);
        }

        if ($reqParam->has('documents')) {
            $filesPath = $this->uploadDocFiles($reqParam);            
            foreach($filesPath as $path){                
                $docData['booking_inquiries_id '] = $booking->id;
                $docData['document_type'] = json_encode($request->input('payload.documentName'));
                $docData['document_path'] = $path;
                $booking->documents()->create($docData);
            }
        }

        if ($request->has('payload.contactInfo') && $request->input('payload.contactInfo.contact_information') !== null && $request->input('payload.contactInfo.contact_information.special_requirements') != '') {
            $booking->contactInformation()->create($request->input('payload.contactInfo.contact_information'));
        }      

       

        

        // if ($request->has('passengers_information')) {
        //     $passengers = $booking->passengersInformation()->create($request->input('passengers_information'));
        // }
        
       
    }

    public function uploadDocFiles($request){
        
        try {
            $images = $request->file('documents');
            $images = is_array($images) ? $images : ($images ? [$images] : []);
            $imagePaths = [];
            foreach ($images as $image) {
                $imageName = time() . '.' . $image->getClientOriginalExtension();
                $imagePath = $image->storeAs('bookingInquiryDocuments', $imageName, 'public'); 
                $imagePaths[] = $imagePath;
            }
            
            return $imagePaths;

        } catch (Exception $e) {
            return [];
        }
    }
}