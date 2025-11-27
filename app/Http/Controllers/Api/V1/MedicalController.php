<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\MedicalResource;

class MedicalController extends Controller
{
    public function index()
    {
        $rows = $this->index();
        return MedicalResource::collection($rows);
    }

    public function show($id)
    {
        $row = $this->show($id);
        return new MedicalResource($row);
    }
}
