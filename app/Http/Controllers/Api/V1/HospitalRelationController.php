<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\HospitalRelation;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class HospitalRelationController extends Controller
{
    public function index()
    {
        $hospitals = HospitalRelation::orderBy('name', 'asc')->paginate(15);
        return response()->json($hospitals);
    }

    public function show($id)
    {
        $hospital = HospitalRelation::findOrFail($id);
        return response()->json($hospital);
    }
}
