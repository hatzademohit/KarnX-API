<?php

namespace App\Http\Controllers\Api\FormFieldsData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FormFieldsData\RequiredDocumentOption;

class RequiredDocumentOptionController extends Controller
{
    public function index()
    {
        $reqDocOptions = RequiredDocumentOption::where(['is_active' => 1])->select('id', 'name', 'is_active', 'order_by')->orderBy('order_by', 'asc')->get();
        return response()->json(['status' => true, 'data' => $reqDocOptions, 'message' => 'Required document options retrieved successfully']);
    }
}
