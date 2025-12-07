<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\SlugHelper;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\Paginates;
use App\Http\Controllers\Concerns\Searchable;
use App\Http\Resources\VendorCollection;
use App\Http\Resources\VendorResource;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VendorsController extends Controller
{
    use Paginates, Searchable;

    /**
     * Display a listing of vendors.
     * 
     * GET /api/v1/vendors
     * GET /api/v1/vendors?per_page=all (untuk semua data)
     * GET /api/v1/vendors?search=keyword (search by name)
     */
    public function index(Request $request)
    {
        $query = Vendor::orderBy('created_at', 'desc');
        $query = $this->applySearch($query, $request, ['name']);
        $paginator = $this->paginateQuery($query);

        return new VendorCollection($paginator);
    }

    public function show($id)
    {
        $vendor = Vendor::findOrFail($id);
        return new VendorResource($vendor);
    }

    /**
     * Create a new Vendor.
     * 
     * POST /api/v1/vendors
     * 
     * Payload:
     * {
     *   "name": "Vendor Name",                                     // Required
     *   "description": "Vendor description...",                    // Optional - will be used for both en/id
     *   "en_description": "English description...",                // Optional
     *   "id_description": "Deskripsi Indonesia...",                // Optional
     *   "category": "hospital",                                    // Optional - hospital/clinic/lab/pharmacy
     *   "specialist": ["cardiology", "neurology"],                 // Optional - Array of specialties
     *   "logo": "https://supabase.../logo.png",                    // Optional - Supabase bucket URL
     *   "highlight_image": "https://supabase.../vendor.jpg",       // Optional - Supabase bucket URL
     *   "reference_image": [                                       // Optional - Array of Supabase bucket URLs
     *     "https://supabase.../img1.jpg",
     *     "https://supabase.../img2.jpg"
     *   ],
     *   "location_map": "https://maps.google.com/..."              // Optional - Google Maps URL
     * }
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'en_description' => 'nullable|string',
            'id_description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'specialist' => 'nullable|array',
            'specialist.*' => 'string',
            'logo' => 'nullable|url',
            'highlight_image' => 'nullable|url',
            'reference_image' => 'nullable|array',
            'reference_image.*' => 'url',
            'location_map' => 'nullable|url',
        ]);

        $payload = [
            'name' => $data['name'],
            'slug' => SlugHelper::generate($data['name']),
            'en_description' => $data['en_description'] ?? $data['description'] ?? null,
            'id_description' => $data['id_description'] ?? $data['description'] ?? null,
            'category' => $data['category'] ?? null,
            'specialist' => $data['specialist'] ?? [],
            'logo' => $data['logo'] ?? null,
            'highlight_image' => $data['highlight_image'] ?? null,
            'reference_image' => $data['reference_image'] ?? [],
            'location_map' => $data['location_map'] ?? null,
        ];

        $vendor = Vendor::create($payload);

        return (new VendorResource($vendor))->response()->setStatusCode(201);
    }

    /**
     * Update a Vendor.
     * 
     * PUT/PATCH /api/v1/vendors/{id}
     * 
     * Payload: Same as store, all fields optional
     */
    public function update(Request $request, $id)
    {
        $vendor = Vendor::findOrFail($id);

        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'en_description' => 'nullable|string',
            'id_description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'specialist' => 'nullable|array',
            'specialist.*' => 'string',
            'logo' => 'nullable|url',
            'highlight_image' => 'nullable|url',
            'reference_image' => 'nullable|array',
            'reference_image.*' => 'url',
            'location_map' => 'nullable|url',
        ]);

        $payload = [];

        if (isset($data['name'])) {
            $payload['name'] = $data['name'];
            $newSlug = SlugHelper::regenerateIfChanged($data['name'], $vendor->slug, $vendor->name);
            if ($newSlug) {
                $payload['slug'] = $newSlug;
            }
        }

        if (isset($data['description'])) {
            $payload['en_description'] = $data['en_description'] ?? $data['description'];
            $payload['id_description'] = $data['id_description'] ?? $data['description'];
        } else {
            if (isset($data['en_description'])) $payload['en_description'] = $data['en_description'];
            if (isset($data['id_description'])) $payload['id_description'] = $data['id_description'];
        }

        $directFields = ['category', 'specialist', 'logo', 'highlight_image', 'reference_image', 'location_map'];
        foreach ($directFields as $key) {
            if (array_key_exists($key, $data)) {
                $payload[$key] = $data[$key];
            }
        }

        $vendor->update($payload);

        return new VendorResource($vendor->fresh());
    }

    /**
     * Delete a Vendor.
     * 
     * DELETE /api/v1/vendors/{id}
     */
    public function destroy($id)
    {
        $vendor = Vendor::findOrFail($id);
        $vendor->delete();

        return response()->json(['message' => 'Vendor deleted successfully']);
    }
}
