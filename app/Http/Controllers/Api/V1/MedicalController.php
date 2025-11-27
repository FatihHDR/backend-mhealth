<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\MedicalResource;
use App\Models\Medical;

class MedicalController extends Controller
{
    public function index()
    {
        $perPage = (int) request()->query('per_page', 15);
        if ($perPage < 1) $perPage = 15;
        $perPage = min($perPage, 100);

        $rows = Medical::orderBy('created_at', 'desc')->paginate($perPage);
        return MedicalResource::collection($rows);
    }

    public function show($id)
    {
        $row = Medical::findOrFail($id);
        return new MedicalResource($row);
    }
}
