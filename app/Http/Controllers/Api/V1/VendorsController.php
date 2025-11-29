<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\VendorResource;
use App\Models\Vendor;
use App\Http\Controllers\Concerns\Paginates;

class VendorsController extends Controller
{
    use Paginates;

    public function index()
    {
        $query = Vendor::orderBy('created_at', 'desc');

        $paginator = $this->paginateQuery($query);

        return VendorResource::collection($paginator);
    }
}
