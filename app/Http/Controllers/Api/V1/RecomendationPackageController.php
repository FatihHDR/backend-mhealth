<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RecomendationPackage;

class RecomendationPackageController extends Controller
{
    public function index()
    {
        $recommendations = RecomendationPackage::orderBy('created_at', 'desc')->paginate(15);

        return response()->json($recommendations);
    }

    public function show($id)
    {
        $recommendation = RecomendationPackage::findOrFail($id);

        return response()->json($recommendation);
    }
}
