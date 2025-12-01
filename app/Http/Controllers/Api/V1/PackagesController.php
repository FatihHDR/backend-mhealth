<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\Paginates;
use App\Http\Resources\LatestPackageResource;
use App\Models\Packages;
use Illuminate\Http\Request;

class PackagesController extends Controller
{
    use Paginates;

    public function index()
    {
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
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'duration_by_day' => 'nullable|integer|min:0',
            'duration_by_night' => 'nullable|integer|min:0',
            'medical_package' => 'nullable|string',
            'entertain_package' => 'nullable|string',
            'is_medical' => 'nullable|boolean',
            'is_entertain' => 'nullable|boolean',
            'spesific_gender' => 'nullable|string|in:all,male,female',
            'image' => 'nullable|url',
            'location' => 'nullable|string|max:255',
        ]);

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
            'spesific_gender' => $data['spesific_gender'] ?? 'all',
            'image' => $data['image'] ?? null,
            'location' => $data['location'] ?? null,
        ];

        $package = Packages::create($payload);

        return (new LatestPackageResource($package))->response()->setStatusCode(201);
    }

    /**
     * Update a Package.
     * 
     * PUT/PATCH /api/v1/packages/{id}
     * 
     * Payload: Same as store, all fields optional
     */
    public function update(Request $request, $id)
    {
        $package = Packages::findOrFail($id);

        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'duration_by_day' => 'nullable|integer|min:0',
            'duration_by_night' => 'nullable|integer|min:0',
            'medical_package' => 'nullable|string',
            'entertain_package' => 'nullable|string',
            'is_medical' => 'nullable|boolean',
            'is_entertain' => 'nullable|boolean',
            'spesific_gender' => 'nullable|string|in:all,male,female',
            'image' => 'nullable|url',
            'location' => 'nullable|string|max:255',
        ]);

        $package->update($data);

        return new LatestPackageResource($package->fresh());
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
