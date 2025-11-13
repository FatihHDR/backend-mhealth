<?php

namespace App\Http\Controllers;

use App\Models\RecomendationPackage;
use Illuminate\Http\Request;

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
