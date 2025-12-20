<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\SlugHelper;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\Paginates;
use App\Http\Controllers\Concerns\Searchable;
use App\Http\Requests\StoreWellnessRequest;
use App\Http\Requests\UpdateWellnessRequest;
use App\Http\Resources\WellnessCollection;
use App\Http\Resources\WellnessResource;
use App\Models\Wellness;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WellnessController extends Controller
{
    use Paginates, Searchable;

    /**
     * Display a listing of wellness packages.
     * 
     * GET /api/v1/wellness
     * GET /api/v1/wellness?search=keyword (search by title)
     */
    public function index(Request $request)
    {
        $query = Wellness::orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $query = $this->applySearch($query, $request);
        $rows = $this->paginateQuery($query);

        return new WellnessCollection($rows);
    }

    /**
     * Display the specified wellness package.
     * 
     * GET /api/v1/wellness/{id}      - by UUID
     * GET /api/v1/wellness/{slug}    - by slug
     */
    public function show($id)
    {
        // Auto-detect: UUID format or slug
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id) ||
            preg_match('/^[0-9a-f]{32}$/i', $id) ||
            preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', str_replace('-', '', $id))) {
            $wellness = Wellness::findOrFail($id);
        } else {
            $wellness = Wellness::where('slug', $id)->firstOrFail();
        }
        return new WellnessResource($wellness);
    }

    /**
     * Display a wellness package by slug.
     * 
     * GET /api/v1/wellness/slug/{slug}
     */
    public function showBySlug($slug)
    {
        $wellness = Wellness::where('slug', $slug)->firstOrFail();
        return new WellnessResource($wellness);
    }

    /**
     * Create a new Wellness Package.
     * 
     * POST /api/v1/wellness
     * 
     * Payload:
     * {
     *   "title": "Wellness Package Title",                         // Required - will be used for both en_title and id_title
     *   "en_title": "English Title",                               // Optional - override English title
     *   "id_title": "Judul Indonesia",                             // Optional - override Indonesian title
     *   "tagline": "Short tagline",                                // Optional - will be used for both en/id
     *   "en_tagline": "English tagline",                           // Optional
     *   "id_tagline": "Tagline Indonesia",                         // Optional
     *   "content": "Package description...",                       // Optional - will be used for both en/id
     *   "en_wellness_package_content": "English content...",       // Optional
     *   "id_wellness_package_content": "Konten Indonesia...",      // Optional
     *   "highlight_image": "https://supabase.../wellness.jpg",     // Optional - Supabase bucket URL
     *   "reference_image": [                                       // Optional - Array of Supabase bucket URLs
     *     "https://supabase.../img1.jpg",
     *     "https://supabase.../img2.jpg"
     *   ],
     *   "duration_by_day": 3,                                      // Optional - Number of days
     *   "duration_by_night": 2,                                    // Optional - Number of nights
     *   "spesific_gender": "all",                                  // Optional - all/male/female
     *   "included": ["spa", "yoga", "meditation"],                 // Optional - Array of included items
     *   "hotel_id": "uuid-of-hotel",                               // Optional - Hotel UUID
     *   "real_price": 3000000,                                     // Optional - Original price
     *   "discount_price": 2500000,                                 // Optional - Discounted price
     *   "status": "draft"                                         // Optional - draft/published/archived
     * }
     */
    public function store(StoreWellnessRequest $request)
    {
        $data = $request->validated();

        $payload = [
            'en_title' => $data['en_title'] ?? $data['title'] ?? '',
            'id_title' => $data['id_title'] ?? $data['title'] ?? '',
            'slug' => SlugHelper::generate($data['en_title'] ?? $data['title'] ?? Str::random(10)),
            'en_tagline' => $data['en_tagline'] ?? $data['tagline'] ?? null,
            'id_tagline' => $data['id_tagline'] ?? $data['tagline'] ?? null,
            'en_wellness_package_content' => $data['en_wellness_package_content'] ?? $data['content'] ?? null,
            'id_wellness_package_content' => $data['id_wellness_package_content'] ?? $data['content'] ?? null,
            'highlight_image' => $data['highlight_image'] ?? null,
            'reference_image' => $data['reference_image'] ?? [],
            'duration_by_day' => $data['duration_by_day'] ?? null,
            'duration_by_night' => $data['duration_by_night'] ?? null,
            'spesific_gender' => $data['spesific_gender'] ?? 'both',
            'included' => $data['included'] ?? [],
            'vendor_id' => $data['vendor_id'] ?? null,
            'hotel_id' => $data['hotel_id'] ?? null,
            'real_price' => $data['real_price'] ?? null,
            'discount_price' => $data['discount_price'] ?? null,
            'status' => $data['status'] ?? 'draft',
        ];

        $wellness = Wellness::create($payload);

        return (new WellnessResource($wellness))->response()->setStatusCode(201);
    }

    /**
     * Update a Wellness Package.
     * 
     * PUT/PATCH /api/v1/wellness/{id}
     * 
     * Payload: Same as store, all fields optional
     */
    public function update(UpdateWellnessRequest $request, $id)
    {
        $wellness = Wellness::findOrFail($id);
        $data = $request->validated();

        $payload = [];

        // Handle title
        if (isset($data['title'])) {
            $newTitle = $data['en_title'] ?? $data['title'];
            $payload['en_title'] = $newTitle;
            $payload['id_title'] = $data['id_title'] ?? $data['title'];
            $newSlug = SlugHelper::regenerateIfChanged($newTitle, $wellness->slug, $wellness->en_title);
            if ($newSlug) {
                $payload['slug'] = $newSlug;
            }
        } else {
            if (isset($data['en_title'])) {
                $payload['en_title'] = $data['en_title'];
                $newSlug = SlugHelper::regenerateIfChanged($data['en_title'], $wellness->slug, $wellness->en_title);
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

        // Handle content
        if (isset($data['content'])) {
            $payload['en_wellness_package_content'] = $data['en_wellness_package_content'] ?? $data['content'];
            $payload['id_wellness_package_content'] = $data['id_wellness_package_content'] ?? $data['content'];
        } else {
            if (isset($data['en_wellness_package_content'])) $payload['en_wellness_package_content'] = $data['en_wellness_package_content'];
            if (isset($data['id_wellness_package_content'])) $payload['id_wellness_package_content'] = $data['id_wellness_package_content'];
        }

        // Direct fields
        $directFields = ['highlight_image', 'reference_image', 'duration_by_day', 'duration_by_night', 'spesific_gender', 'included', 'vendor_id', 'hotel_id', 'real_price', 'discount_price', 'status'];
        foreach ($directFields as $key) {
            if (array_key_exists($key, $data)) {
                $payload[$key] = $data[$key];
            }
        }

        $wellness->update($payload);

        return new WellnessResource($wellness->fresh());
    }

    /**
     * Delete a Wellness Package.
     * 
     * DELETE /api/v1/wellness/{id}
     */
    public function destroy($id)
    {
        $wellness = Wellness::findOrFail($id);
        $wellness->delete();

        return response()->json(['message' => 'Wellness package deleted successfully']);
    }
}
