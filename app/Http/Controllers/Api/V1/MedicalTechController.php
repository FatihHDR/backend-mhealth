<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\MedicalTech;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class MedicalTechController extends Controller
{
    public function index()
    {
        $medicalTechs = MedicalTech::orderBy('created_at', 'desc')->paginate(15);
        return response()->json($medicalTechs);
    }

    public function show($id)
    {
        $medicalTech = MedicalTech::findOrFail($id);
        return response()->json($medicalTech);
    }
}
