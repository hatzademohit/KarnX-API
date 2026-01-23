<?php

namespace App\Http\Controllers\Api\Configure;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\FormFieldsData\AirportCities;
use App\Models\Asset;

class ClientController extends Controller
{
    // GET all clients
    public function index(Request $request)
    {
        try {
            $clients = Client::all();
            
            return response()->json([
                'status' => true,
                'message' => 'Clients retrieved successfully',
                'data' => $clients,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // GET single client
    public function show(Request $request, $id)
    {
        try {
            $client = Client::find($id);
            
            $client['operating_reginons'] = array_map(fn($id) => (int) $id, explode(',', $client->operating_reginons));
            $regionCities = AirportCities::where(['is_active' => 1, 'country_name' => 'India']) ->select('id','city_name as title','country_name')->get();
            $data['client'] = $client;
            $data['regionCities'] = $regionCities;
            $data['totalAircraft'] = Asset::where(['client_id' => $client->id, 'is_active' => 1])->count();
            $data['rating'] = 4;
            $data['totalFlights'] = 2847;

            if (!$client) {
                return response()->json(['status' => false, 'message' => 'Client not found'], 404);
            }
            return response()->json(['status' => true, 'message' => 'Client get successfully', 'data' => $data], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // POST create client
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:100',
                'type' => 'required|string',
                'contact_person' => 'nullable|string|max:100',
                'phone' => 'required|numeric|digits:10',
                'email' => 'required|email|max:100',
                'address_line1' => 'required|string|max:255',
                'address_line2' => 'required|string|max:255',
                'area' => 'required|string|max:100',
                'city' => 'required|string|max:100',
                'state' => 'required|string|max:100',
                'pincode' => 'required|string|max:20',
                'country' => 'required|string|max:100',
                'website' => 'nullable|string|max:255',
                'safety_ratings' => 'nullable|number|max:255',
                'operating_reginons' => 'nullable|string|max:255',
                'certifications' => 'nullable|string|max:255',
                'specialties' => 'nullable|string|max:255',                
            ]);
            $validated['is_active'] = true;
            $client = Client::create($validated);

            return response()->json(['status' => true, 'message' => 'Client created successfully', 'data' => $client], 201);
        } catch(\Exception $e){
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // PUT update client
    public function update(Request $request, $id)
    {
        return response()->json(['status' => false, 'message' => dd($request->all())], 422);
        try {
            $client = Client::find($id);
            
            if (!$client) {
                return response()->json(['message' => 'Client not found'], 404);
            }
            
            
            $validated = $request->validate([
                'name' => 'sometimes|string|max:100',
                'type' => 'sometimes|string',
                'contact_person' => 'nullable|string|max:100',
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:100',
                'address_line1' => 'nullable|string|max:255',
                'address_line2' => 'nullable|string|max:255',
                'area' => 'nullable|string|max:100',
                'city' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'pincode' => 'nullable|string|max:20',
                'country' => 'nullable|string|max:100',
                'website' => 'nullable|string|max:255',
                'safety_ratings' => 'nullable|string|max:255',
                'operating_reginons' => 'nullable|array|max:255',
                'certifications' => 'nullable|string|max:255',
                'specialties' => 'nullable|string|max:255',
                'is_active' => 'boolean'
            ]);

            if ($request->hasFile('terms_conditions_policis')) {
                $poilicies = $request->file('terms_conditions_policis')->store('terms_conditions_policis', 'public');
                $validated['terms_conditions_policis'] = $poilicies;
            }
            
            $validated['operating_reginons'] = implode(',', array_unique($validated['operating_reginons']));
            $client->update($validated);
            
            return response()->json(['status' => true, 'message' => 'Client updated successfully', 'data' => $client], 200);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'message' => $th->getMessage()], 422);
        }
    }

    // DELETE client
    public function destroy(Request $request, $id)
    {
        try {        
            $client = Client::find($id);
            if (!$client) {
                return response()->json(['status' => false,'message' => 'Client not found'], 404);
            }

            $client->delete();
            return response()->json(['status' => true, 'message' => 'Client deleted successfully'], 200);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'message' => $th->getMessage()], 200);
        }
    }
}
