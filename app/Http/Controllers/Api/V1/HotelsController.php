<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\SlugHelper;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\Paginates;
use App\Http\Controllers\Concerns\Searchable;
use App\Http\Resources\HotelCollection;
use App\Http\Resources\HotelResource;
use App\Models\Hotel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HotelsController extends Controller
{
    use Paginates, Searchable;

    /**
     * Display a listing of hotels.
     * 
     * GET /api/v1/hotels
     * GET /api/v1/hotels?per_page=all (untuk semua data)
     * GET /api/v1/hotels?search=keyword (search by name)
     */
    public function index(Request $request)
    {
        $query = Hotel::orderBy('created_at', 'desc');
        $query = $this->applySearch($query, $request, ['name']);
        $rows = $this->paginateQuery($query);

        return new HotelCollection($rows);
    }

    /**
     * Display a hotel.
     * 
     * GET /api/v1/hotels/{id}      - by UUID
     * GET /api/v1/hotels/{slug}    - by slug
     */
    public function show($id)
    {
        // Auto-detect: UUID format or slug
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id) ||
            preg_match('/^[0-9a-f]{32}$/i', $id) ||
            preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', str_replace('-', '', $id))) {
            $hotel = Hotel::findOrFail($id);
        } else {
            $hotel = Hotel::where('slug', $id)->firstOrFail();
        }
        return new HotelResource($hotel);
    }

    /**
     * Display a hotel by slug.
     * 
     * GET /api/v1/hotels/slug/{slug}
     */
    public function showBySlug($slug)
    {
        $hotel = Hotel::where('slug', $slug)->firstOrFail();
        return new HotelResource($hotel);
    }

    /**
     * Create a new Hotel.
     * 
     * POST /api/v1/hotels
     * 
     * Payload:
     * {
     *   "name": "Hotel Name",                                      // Required
     *   "description": "Hotel description...",                     // Optional - will be used for both en/id
     *   "en_description": "English description...",                // Optional
     *   "id_description": "Deskripsi Indonesia...",                // Optional
     *   "logo": "https://supabase.../logo.png",                    // Optional - Supabase bucket URL
     *   "highlight_image": "https://supabase.../hotel.jpg",        // Optional - Supabase bucket URL
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
            'logo' => $data['logo'] ?? null,
            'highlight_image' => $data['highlight_image'] ?? null,
            'reference_image' => $data['reference_image'] ?? [],
            'location_map' => $data['location_map'] ?? null,
        ];

        $hotel = Hotel::create($payload);

        return (new HotelResource($hotel))->response()->setStatusCode(201);
    }

    /**
     * Update a Hotel.
     * 
     * PUT/PATCH /api/v1/hotels/{id}
     * 
     * Payload: Same as store, all fields optional
     */
    public function update(Request $request, $id)
    {
        $hotel = Hotel::findOrFail($id);

        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'en_description' => 'nullable|string',
            'id_description' => 'nullable|string',
            'logo' => 'nullable|url',
            'highlight_image' => 'nullable|url',
            'reference_image' => 'nullable|array',
            'reference_image.*' => 'url',
            'location_map' => 'nullable|url',
        ]);

        $payload = [];

        if (isset($data['name'])) {
            $payload['name'] = $data['name'];
            $newSlug = SlugHelper::regenerateIfChanged($data['name'], $hotel->slug, $hotel->name);
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

        foreach (['logo', 'highlight_image', 'reference_image', 'location_map'] as $key) {
            if (array_key_exists($key, $data)) {
                $payload[$key] = $data[$key];
            }
        }

        $hotel->update($payload);

        return new HotelResource($hotel->fresh());
    }

    /**
     * Delete a Hotel.
     * 
     * DELETE /api/v1/hotels/{id}
     */
    public function destroy($id)
    {
        $hotel = Hotel::findOrFail($id);
        $hotel->delete();

        return response()->json(['message' => 'Hotel deleted successfully']);
    }
}
