<?php

namespace App\Http\Controllers\Api\FormFieldsData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FormFieldsData\MedicalSupports;

class MedicalSupportsController extends Controller
{
    public function index()
    {
        $medicalSupports = MedicalSupports::where(['is_active' => 1])->select('id', 'name', 'is_active', 'order_by')->orderBy('order_by', 'asc')->get();
        return response()->json(['status' => true, 'data' => $medicalSupports, 'message' => 'Medical supports retrieved successfully']);
    }
}
