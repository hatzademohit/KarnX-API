<?php

namespace App\Http\Controllers\Api\Configure;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Asset;
use Exception;

class AssetController extends Controller
{
    // GET all assets
    public function index(Request $request)
    {
        try {
            $query = Asset::with('client');

            // 🔍 Search filter
            if ($request->has('search')) {
                $search = $request->search;
                $query->where('asset_name', 'LIKE', "%{$search}%")
                      ->orWhere('registration_no', 'LIKE', "%{$search}%")
                      ->orWhere('aircraft_model', 'LIKE', "%{$search}%");
            }

            // 📑 Pagination
            $assets = $query->paginate($request->get('per_page', 10));

            return response()->json([
                'status' => true,
                'status_code' => 200,
                'message' => 'Assets fetched successfully',
                'data' => $assets
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'status_code' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // GET single asset
    public function show($id)
    {
        try {
            $asset = Asset::with('client')->find($id);

            if (!$asset) {
                return response()->json([
                    'status' => false,
                    'status_code' => 404,
                    'message' => 'Asset not found'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'status_code' => 200,
                'message' => 'Asset fetched successfully',
                'data' => $asset
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'status_code' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // POST create asset
    public function store(Request $request)
    {
        //dd($request->client_id);
        try {
            $validated = $request->validate([
                'client_id' => 'required|exists:clients,id',
                'asset_name' => 'required|string|max:100',
                'asset_type' => 'required|string|max:50',
                'aircraft_model' => 'required|string|max:100',
                'aircraft_type_id' => 'required|string|max:100',
                'registration_no' => 'required|string|max:100|unique:assets',
                'capacity' => 'required|numeric',
                'cabin_size' => 'required|string|max:100',
                'status' => 'required|in:Available,In Use,Maintenance,Active,Inactive',
                'details' => 'required|string',
            ]);
           
            if($request->has('images')){
                $validated['images'] = json_encode($this->uploadImages($request));
            }

            $asset = Asset::create($validated);

            return response()->json([
                'status' => true,
                'status_code' => 201,
                'message' => 'Asset created successfully',
                'data' => $asset
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'status_code' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // PUT update asset
    public function update(Request $request, $id)
    {
        try {
            $asset = Asset::find($id);

            if (!$asset) {
                return response()->json([
                    'status' => false,
                    'status_code' => 404,
                    'message' => 'Asset not found'
                ], 404);
            }

            $validated = $request->validate([
                'client_id' => 'required|exists:clients,id',
                'asset_name' => 'required|string|max:100',
                'asset_type' => 'required|string|max:50',
                'aircraft_model' => 'required|string|max:100',
                'aircraft_type_id' => 'required|string|max:100',
                'registration_no' => 'required|string|max:100|unique:assets',
                'capacity' => 'required|numeric',
                'cabin_size' => 'required|string|max:100',
                'status' => 'required|in:Available,In Use,Maintenance,Active,Inactive',
                'details' => 'required|string',
            ]);
           
            if($request->has('images')){
                $validated['images'] = json_encode($this->uploadImages($request));
            }
            
            $asset->update($validated);

            return response()->json([
                'status' => true,
                'status_code' => 200,
                'message' => 'Asset updated successfully',
                'data' => $asset
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'status_code' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // DELETE asset
    public function destroy($id)
    {
        try {
            $asset = Asset::find($id);

            if (!$asset) {
                return response()->json([
                    'status' => false,
                    'status_code' => 404,
                    'message' => 'Asset not found'
                ], 404);
            }

            $asset->delete();

            return response()->json([
                'status' => true,
                'status_code' => 200,
                'message' => 'Asset deleted successfully'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'status_code' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function uploadImages($request)
    {
        /**this function only  return image path and store in public/storage/fleetImages folder in loop*/
        try {
            $validated = $request->validate([
                'images' => 'required|array',
                'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            $images = $request->file('images');
            $imagePaths = [];
            foreach ($images as $image) {
                /**image should be dynamic name of the time */
                $imageName = time() . '.' . $image->getClientOriginalExtension();
                $imagePath = $image->storeAs('fleetImages', $imageName, 'public'); 
                $imagePaths[] = $imagePath;
            }

            return $imagePaths;

        } catch (Exception $e) {
            return [];
        }
    }
}