<?php

namespace App\Http\Controllers\Api\Configure;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BookingStatus;

class BookingStatusController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = BookingStatus::query();

            // 🔍 Search filter
            if ($request->has('search')) {
                $search = $request->search;
                $query->where('status_name', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%")
                      ->orWhere('notification_template', 'LIKE', "%{$search}%");
            }

            // 📑 Pagination
            $statuses = $query->paginate($request->get('per_page', 20));

            return response()->json([
                'status' => true,
                'status_code' => 200,
                'message' => 'Booking statuses fetched successfully',
                'data' => $statuses
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'status_code' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // GET single booking status
    public function show($id)
    {
        try {
            $status = BookingStatus::find($id);

            if (!$status) {
                return response()->json([
                    'status' => false,
                    'status_code' => 404,
                    'message' => 'Booking status not found'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'status_code' => 200,
                'message' => 'Booking status fetched successfully',
                'data' => $status
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'status_code' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // POST create booking status
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'status_name' => 'required|string|max:100',
                'description' => 'required|string',
                'notification_template' => 'nullable|string',
                'color_code' => 'required|string|max:10',
            ]);
            $validated['is_active'] = 1;
            $status = BookingStatus::create($validated);

            return response()->json([
                'status' => true,
                'status_code' => 201,
                'message' => 'Booking status created successfully',
                'data' => $status
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'status_code' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // PUT update booking status
    public function update(Request $request, $id)
    {
        
        try {
            $status = BookingStatus::find($id);

            if (!$status) {
                return response()->json([
                    'status' => false,
                    'status_code' => 404,
                    'message' => 'Booking status not found'
                ], 404);
            }
            
            $validated = $request->validate([
                'status_name' => 'required|string|max:100',
                'description' => 'required|string',
                'notification_template' => 'nullable|string',
                'color_code' => 'required|string|max:10',
                'is_active' => 'required'
            ]);

            $status->update($validated);

            return response()->json([
                'status' => true,
                'status_code' => 200,
                'message' => 'Booking status updated successfully',
                'data' => $status
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'status_code' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // DELETE booking status
    public function destroy($id)
    {
        try {
            $status = BookingStatus::find($id);
            if (!$status) {
                return response()->json([
                    'status' => false,
                    'status_code' => 404,
                    'message' => 'Booking status not found'
                ], 404);
            }

            $status->delete();

            return response()->json([
                'status' => true,
                'status_code' => 200,
                'message' => 'Booking status deleted successfully'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'status_code' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
