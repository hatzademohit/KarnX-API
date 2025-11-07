<?php

namespace App\Http\Controllers\Api\FormFieldsData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FormFieldsData\AirportCities;

class AirportCitiesController extends Controller
{
    public function index()
    {
        $airportCities = AirportCities::where(['is_active' => 1, 'country_name' => 'India'])->select('id', 'airport_name', 'city_name', 'country_name', 'code')->get();
        return response()->json(['status' => true, 'data' => $airportCities, 'message' => 'Airport cities retrieved successfully']);
    }
}
