<?php

namespace App\Http\Controllers\Api\FormFieldsData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FormFieldsData\CrewRequirements;

class CrewRequirementsController extends Controller
{
    public function index()
    {
        $data = CrewRequirements::where('is_active', 1)
                ->orderBy('self_parent_id')
                ->orderBy('order_by')
                ->orderBy('id')
                ->get();

        $parents = $data->where('self_parent_id', 0);

        $result = $parents->map(function ($parent) use ($data) {
            return [
                'inputLabel' => $parent->name,
                'var_key' => $parent->var_key,
                'options' => $data->where('self_parent_id', $parent->id)->select('name', 'id')->values()
                ];
            })->values();

        return response()->json(['status' => true, 'data' => $result, 'message' => 'Crew requirements retrieved successfully']);
    }
}
