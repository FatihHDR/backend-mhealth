<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\HotelResource;

class HotelsController extends Controller
{
    public function index()
    {
        $rows = $this->index();
        return HotelResource::collection($rows);
    }
}
