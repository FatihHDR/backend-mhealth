<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\WellnessPackageResource;

class WellnessPackagesController extends Controller
{
    public function index()
    {
        $rows = $this->index();
        return WellnessPackageResource::collection($rows);
    }
}
