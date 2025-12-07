<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\Paginates;
use App\Http\Requests\StorePackageRequest;
use App\Http\Requests\UpdatePackageRequest;
use App\Http\Resources\PackageCollection;
use App\Http\Resources\PackageResource;
use App\Models\Packages;

class PackagesController extends Controller
{
    use Paginates;

    /**
     * Display a listing of packages.
     * 
     * GET /api/v1/packages
     */
    public function index()
    {
        $query = Packages::orderBy('created_at', 'desc');
        $packages = $this->paginateQuery($query);

        return new PackageCollection($packages);
    }

    /**
     * Display the specified package.
     * 
     * GET /api/v1/packages/{id}
     */
    public function show($id)
    {
        $package = Packages::findOrFail($id);
        return new PackageResource($package);
    }

    /**
     * Create a new Package.
     * 
     * POST /api/v1/packages
     * 
     * Payload:
     * {
     *   "name": "Package Name",                                    // Required
     *   "description": "Package description...",                   // Optional
     *   "price": 5000000,                                          // Optional - Package price
     *   "duration_by_day": 3,                                      // Optional - Number of days
     *   "duration_by_night": 2,                                    // Optional - Number of nights
     *   "medical_package": "Medical package details",              // Optional
     *   "entertain_package": "Entertainment package details",      // Optional
     *   "is_medical": true,                                        // Optional - Boolean
     *   "is_entertain": false,                                     // Optional - Boolean
     *   "spesific_gender": "all",                                  // Optional - all/male/female
     *   "image": "https://supabase.../package.jpg",                // Optional - Supabase bucket URL
     *   "location": "Jakarta, Indonesia"                           // Optional - Location string
     * }
     */
    public function store(StorePackageRequest $request)
    {
        $data = $request->validated();

        $payload = [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price' => $data['price'] ?? null,
            'duration_by_day' => $data['duration_by_day'] ?? null,
            'duration_by_night' => $data['duration_by_night'] ?? null,
            'medical_package' => $data['medical_package'] ?? null,
            'entertain_package' => $data['entertain_package'] ?? null,
            'is_medical' => $data['is_medical'] ?? false,
            'is_entertain' => $data['is_entertain'] ?? false,
            'spesific_gender' => $data['spesific_gender'] ?? 'both',
            'image' => $data['image'] ?? null,
            'location' => $data['location'] ?? null,
        ];

        $package = Packages::create($payload);

        return (new PackageResource($package))->response()->setStatusCode(201);
    }

    /**
     * Update a Package.
     * 
     * PUT/PATCH /api/v1/packages/{id}
     * 
     * Payload: Same as store, all fields optional
     */
    public function update(UpdatePackageRequest $request, $id)
    {
        $package = Packages::findOrFail($id);
        $data = $request->validated();

        $package->update($data);

        return new PackageResource($package->fresh());
    }

    /**
     * Delete a Package.
     * 
     * DELETE /api/v1/packages/{id}
     */
    public function destroy($id)
    {
        $package = Packages::findOrFail($id);
        $package->delete();

        return response()->json(['message' => 'Package deleted successfully']);
    }
}
