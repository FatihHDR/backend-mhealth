<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\Paginates;
use App\Http\Resources\LatestPackageResource;
use App\Models\Packages;

class PackagesController extends Controller
{
    use Paginates;
    public function index()
    {
        // allow clients to set `per_page` via query string, with sane defaults and limits
        $perPage = (int) request()->query('per_page', 15);
        if ($perPage < 1) $perPage = 15;
        $perPage = min($perPage, 100);

        $query = Packages::orderBy('created_at', 'desc');
        $packages = $this->paginateQuery($query);

        return LatestPackageResource::collection($packages);
    }

    public function show($id)
    {
        $package = Packages::findOrFail($id);
        return new LatestPackageResource($package);
    }
}
