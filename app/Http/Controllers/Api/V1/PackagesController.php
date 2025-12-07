<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\SlugHelper;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\Paginates;
use App\Http\Requests\StorePackageRequest;
use App\Http\Requests\UpdatePackageRequest;
use App\Http\Resources\PackageCollection;
use App\Http\Resources\PackageResource;
use App\Models\Packages;
use Illuminate\Support\Str;

class PackagesController extends Controller
{
    use Paginates;

    /**
     * Display a listing of packages.
     * 
     * GET /api/v1/packages
     * GET /api/v1/packages?per_page=all (untuk semua data)
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
     *   "title": "Package Title",                                  // Required - will be used for both en_title and id_title
     *   "en_title": "English Title",                               // Optional - override English title
     *   "id_title": "Judul Indonesia",                             // Optional - override Indonesian title
     *   "tagline": "Short tagline",                                // Optional - will be used for both en/id
     *   "en_tagline": "English tagline",                           // Optional
     *   "id_tagline": "Tagline Indonesia",                         // Optional
     *   "highlight_image": "https://supabase.../package.jpg",      // Optional - Supabase bucket URL
     *   "reference_image": ["https://...jpg", "https://...jpg"],   // Optional - Array of Supabase bucket URLs
     *   "duration_by_day": 3,                                      // Optional - Number of days
     *   "duration_by_night": 2,                                    // Optional - Number of nights
     *   "spesific_gender": "both",                                 // Optional - both/male/female
     *   "medical_content": "Medical package details...",           // Optional - will be used for both en/id
     *   "en_medical_package_content": "English medical content",   // Optional
     *   "id_medical_package_content": "Konten medis Indonesia",    // Optional
     *   "wellness_content": "Wellness package details...",         // Optional - will be used for both en/id
     *   "en_wellness_package_content": "English wellness content", // Optional
     *   "id_wellness_package_content": "Konten wellness Indonesia",// Optional
     *   "included": ["spa", "yoga", "meditation"],                 // Optional - Array of included items
     *   "vendor_id": "uuid-of-vendor",                             // Required - Vendor UUID
     *   "hotel_id": "uuid-of-hotel",                               // Required - Hotel UUID
     *   "real_price": "3000000",                                   // Optional - Original price (text)
     *   "discount_price": "2500000",                               // Optional - Discounted price (text)
     *   "status": "draft"                                          // Optional - draft/published/archived
     * }
     */
    public function store(StorePackageRequest $request)
    {
        $data = $request->validated();

        $payload = [
            'en_title' => $data['en_title'] ?? $data['title'] ?? '',
            'id_title' => $data['id_title'] ?? $data['title'] ?? '',
            'slug' => SlugHelper::generate($data['en_title'] ?? $data['title'] ?? Str::random(10)),
            'en_tagline' => $data['en_tagline'] ?? $data['tagline'] ?? '',
            'id_tagline' => $data['id_tagline'] ?? $data['tagline'] ?? '',
            'highlight_image' => $data['highlight_image'] ?? '',
            'reference_image' => $data['reference_image'] ?? [],
            'duration_by_day' => $data['duration_by_day'] ?? 0,
            'duration_by_night' => $data['duration_by_night'] ?? null,
            'spesific_gender' => $data['spesific_gender'] ?? 'both',
            'en_medical_package_content' => $data['en_medical_package_content'] ?? $data['medical_content'] ?? '',
            'id_medical_package_content' => $data['id_medical_package_content'] ?? $data['medical_content'] ?? '',
            'en_wellness_package_content' => $data['en_wellness_package_content'] ?? $data['wellness_content'] ?? '',
            'id_wellness_package_content' => $data['id_wellness_package_content'] ?? $data['wellness_content'] ?? '',
            'included' => $data['included'] ?? [],
            'vendor_id' => $data['vendor_id'],
            'hotel_id' => $data['hotel_id'],
            'real_price' => $data['real_price'] ?? '',
            'discount_price' => $data['discount_price'] ?? null,
            'status' => $data['status'] ?? 'draft',
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

        $payload = [];

        // Handle title
        if (isset($data['title'])) {
            $newTitle = $data['en_title'] ?? $data['title'];
            $payload['en_title'] = $newTitle;
            $payload['id_title'] = $data['id_title'] ?? $data['title'];
            $newSlug = SlugHelper::regenerateIfChanged($newTitle, $package->slug, $package->en_title);
            if ($newSlug) {
                $payload['slug'] = $newSlug;
            }
        } else {
            if (isset($data['en_title'])) {
                $payload['en_title'] = $data['en_title'];
                $newSlug = SlugHelper::regenerateIfChanged($data['en_title'], $package->slug, $package->en_title);
                if ($newSlug) {
                    $payload['slug'] = $newSlug;
                }
            }
            if (isset($data['id_title'])) $payload['id_title'] = $data['id_title'];
        }

        // Handle tagline
        if (isset($data['tagline'])) {
            $payload['en_tagline'] = $data['en_tagline'] ?? $data['tagline'];
            $payload['id_tagline'] = $data['id_tagline'] ?? $data['tagline'];
        } else {
            if (isset($data['en_tagline'])) $payload['en_tagline'] = $data['en_tagline'];
            if (isset($data['id_tagline'])) $payload['id_tagline'] = $data['id_tagline'];
        }

        // Handle medical content
        if (isset($data['medical_content'])) {
            $payload['en_medical_package_content'] = $data['en_medical_package_content'] ?? $data['medical_content'];
            $payload['id_medical_package_content'] = $data['id_medical_package_content'] ?? $data['medical_content'];
        } else {
            if (isset($data['en_medical_package_content'])) $payload['en_medical_package_content'] = $data['en_medical_package_content'];
            if (isset($data['id_medical_package_content'])) $payload['id_medical_package_content'] = $data['id_medical_package_content'];
        }

        // Handle wellness content
        if (isset($data['wellness_content'])) {
            $payload['en_wellness_package_content'] = $data['en_wellness_package_content'] ?? $data['wellness_content'];
            $payload['id_wellness_package_content'] = $data['id_wellness_package_content'] ?? $data['wellness_content'];
        } else {
            if (isset($data['en_wellness_package_content'])) $payload['en_wellness_package_content'] = $data['en_wellness_package_content'];
            if (isset($data['id_wellness_package_content'])) $payload['id_wellness_package_content'] = $data['id_wellness_package_content'];
        }

        // Direct fields
        $directFields = ['highlight_image', 'reference_image', 'duration_by_day', 'duration_by_night',
                         'spesific_gender', 'included', 'vendor_id', 'hotel_id', 'real_price', 'discount_price', 'status'];
        foreach ($directFields as $key) {
            if (array_key_exists($key, $data)) {
                $payload[$key] = $data[$key];
            }
        }

        $package->update($payload);

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
