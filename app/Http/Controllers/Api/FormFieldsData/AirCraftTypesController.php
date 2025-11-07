<?php

namespace App\Http\Controllers\Api\FormFieldsData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FormFieldsData\AirCraftTypes;

class AirCraftTypesController extends Controller
{
    public function index()
    {
        $aircraftTypes = AirCraftTypes::where(['is_active' => 1])->select('id', 'name', 'description', 'is_active', 'order_by')->orderBy('order_by', 'asc')->get();
        return response()->json(['status' => true, 'data' => $aircraftTypes, 'message' => 'Aircraft types retrieved successfully']);
    }
}
