<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\LatestPackageResource;
use App\Models\Packages;

class PackagesController extends Controller
{
    public function index()
    {
        $packages = Packages::orderBy('created_at', 'desc')->paginate(15);
        return LatestPackageResource::collection($packages);
    }

    public function show($id)
    {
        $package = Packages::findOrFail($id);
        return new LatestPackageResource($package);
    }
}
