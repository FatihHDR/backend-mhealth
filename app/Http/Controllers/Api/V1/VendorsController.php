<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\VendorResource;

class VendorsController extends Controller
{
    public function index()
    {
        $rows = $this->index();
        return VendorResource::collection($rows);
    }
}
