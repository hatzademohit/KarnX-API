<?php

namespace App\Http\Controllers\Api\FormFieldsData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FormFieldsData\CateringDietary;

class CateringDietaryController extends Controller
{
    public function index()
    {
        $data = CateringDietary::where('is_active', 1)
                ->orderBy('order_by')
                ->get();

        return response()->json(['status' => true, 'data' => $data, 'message' => 'Catering dietary retrieved successfully']);
    }
}
