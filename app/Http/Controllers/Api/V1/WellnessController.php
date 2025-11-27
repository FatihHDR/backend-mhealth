<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\WellnessResource;

class WellnessController extends Controller
{
    public function index()
    {
        $rows = $this->index();
        return WellnessResource::collection($rows);
    }
}
