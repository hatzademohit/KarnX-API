<?php

namespace App\Http\Controllers\Api\FormFieldsData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FormFieldsData\TravelingPurpose;

class TravelingPurposeController extends Controller
{
    public function index()
    {
        $data = TravelingPurpose::where('is_active', 1)
                ->orderBy('order_by')
                ->get();

        return response()->json(['status' => true, 'data' => $data, 'message' => 'Traveling purposes retrieved successfully']);
    }
}
