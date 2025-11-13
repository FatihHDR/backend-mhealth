<?php

namespace App\Http\Controllers;

use App\Models\MedicalTech;
use Illuminate\Http\Request;

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
