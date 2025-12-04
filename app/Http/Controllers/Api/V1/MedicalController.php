<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\SlugHelper;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\Paginates;
use App\Http\Resources\MedicalResource;
use App\Models\Medical;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MedicalController extends Controller
{
    use Paginates;

    public function index()
    {
        $query = Medical::orderBy('created_at', 'desc');
        $rows = $this->paginateQuery($query);
        return MedicalResource::collection($rows);
    }

    public function show($id)
    {
        $row = Medical::findOrFail($id);
        return new MedicalResource($row);
    }

    /**
     * Create a new Medical Package.
     * 
     * POST /api/v1/medical
     * 
     * Payload:
     * {
     *   "title": "Medical Package Title",                          // Required - will be used for both en_title and id_title
     *   "en_title": "English Title",                               // Optional - override English title
     *   "id_title": "Judul Indonesia",                             // Optional - override Indonesian title
     *   "tagline": "Short tagline",                                // Optional - will be used for both en/id
     *   "en_tagline": "English tagline",                           // Optional
     *   "id_tagline": "Tagline Indonesia",                         // Optional
     *   "content": "Package description...",                       // Optional - will be used for both en/id package content
     *   "en_medical_package_content": "English content...",        // Optional
     *   "id_medical_package_content": "Konten Indonesia...",       // Optional
     *   "highlight_image": "https://supabase.../package.jpg",      // Optional - Supabase bucket URL
     *   "reference_image": [                                       // Optional - Array of Supabase bucket URLs
     *     "https://supabase.../img1.jpg",
     *     "https://supabase.../img2.jpg"
     *   ],
     *   "duration_by_day": 3,                                      // Optional - Number of days
     *   "duration_by_night": 2,                                    // Optional - Number of nights
     *   "spesific_gender": "all",                                  // Optional - all/male/female
     *   "included": ["consultation", "medicine", "room"],          // Optional - Array of included items
     *   "vendor_id": "uuid-of-vendor",                             // Optional - Vendor UUID
     *   "real_price": 5000000,                                     // Optional - Original price
     *   "discount_price": 4500000,                                 // Optional - Discounted price
     *   "status": "active"                                         // Optional - active/inactive
     * }
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required_without_all:en_title,id_title|string|max:255',
            'en_title' => 'nullable|string|max:255',
            'id_title' => 'nullable|string|max:255',
            'tagline' => 'nullable|string|max:255',
            'en_tagline' => 'nullable|string|max:255',
            'id_tagline' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'en_medical_package_content' => 'nullable|string',
            'id_medical_package_content' => 'nullable|string',
            'highlight_image' => 'nullable|url',
            'reference_image' => 'nullable|array',
            'reference_image.*' => 'url',
            'duration_by_day' => 'nullable|integer|min:0',
            'duration_by_night' => 'nullable|integer|min:0',
            'spesific_gender' => 'nullable|string|in:all,male,female',
            'included' => 'nullable|array',
            'included.*' => 'string',
            'vendor_id' => 'nullable|uuid',
            'real_price' => 'nullable|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        $payload = [
            'en_title' => $data['en_title'] ?? $data['title'] ?? '',
            'id_title' => $data['id_title'] ?? $data['title'] ?? '',
            'slug' => SlugHelper::generate($data['en_title'] ?? $data['title'] ?? Str::random(10)),
            'en_tagline' => $data['en_tagline'] ?? $data['tagline'] ?? null,
            'id_tagline' => $data['id_tagline'] ?? $data['tagline'] ?? null,
            'en_medical_package_content' => $data['en_medical_package_content'] ?? $data['content'] ?? null,
            'id_medical_package_content' => $data['id_medical_package_content'] ?? $data['content'] ?? null,
            'highlight_image' => $data['highlight_image'] ?? null,
            'reference_image' => $data['reference_image'] ?? [],
            'duration_by_day' => $data['duration_by_day'] ?? null,
            'duration_by_night' => $data['duration_by_night'] ?? null,
            'spesific_gender' => $data['spesific_gender'] ?? 'all',
            'included' => $data['included'] ?? [],
            'vendor_id' => $data['vendor_id'] ?? null,
            'real_price' => $data['real_price'] ?? null,
            'discount_price' => $data['discount_price'] ?? null,
            'status' => $data['status'] ?? 'active',
        ];

        $medical = Medical::create($payload);

        return (new MedicalResource($medical))->response()->setStatusCode(201);
    }

    /**
     * Update a Medical Package.
     * 
     * PUT/PATCH /api/v1/medical/{id}
     * 
     * Payload: Same as store, all fields optional
     */
    public function update(Request $request, $id)
    {
        $medical = Medical::findOrFail($id);

        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'en_title' => 'nullable|string|max:255',
            'id_title' => 'nullable|string|max:255',
            'tagline' => 'nullable|string|max:255',
            'en_tagline' => 'nullable|string|max:255',
            'id_tagline' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'en_medical_package_content' => 'nullable|string',
            'id_medical_package_content' => 'nullable|string',
            'highlight_image' => 'nullable|url',
            'reference_image' => 'nullable|array',
            'reference_image.*' => 'url',
            'duration_by_day' => 'nullable|integer|min:0',
            'duration_by_night' => 'nullable|integer|min:0',
            'spesific_gender' => 'nullable|string|in:all,male,female',
            'included' => 'nullable|array',
            'included.*' => 'string',
            'vendor_id' => 'nullable|uuid',
            'real_price' => 'nullable|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        $payload = [];

        // Handle title
        if (isset($data['title'])) {
            $newTitle = $data['en_title'] ?? $data['title'];
            $payload['en_title'] = $newTitle;
            $payload['id_title'] = $data['id_title'] ?? $data['title'];
            $newSlug = SlugHelper::regenerateIfChanged($newTitle, $medical->slug, $medical->en_title);
            if ($newSlug) {
                $payload['slug'] = $newSlug;
            }
        } else {
            if (isset($data['en_title'])) {
                $payload['en_title'] = $data['en_title'];
                $newSlug = SlugHelper::regenerateIfChanged($data['en_title'], $medical->slug, $medical->en_title);
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
            $payload['en_medical_package_content'] = $data['en_medical_package_content'] ?? $data['content'];
            $payload['id_medical_package_content'] = $data['id_medical_package_content'] ?? $data['content'];
        } else {
            if (isset($data['en_medical_package_content'])) $payload['en_medical_package_content'] = $data['en_medical_package_content'];
            if (isset($data['id_medical_package_content'])) $payload['id_medical_package_content'] = $data['id_medical_package_content'];
        }

        // Direct fields
        $directFields = ['highlight_image', 'reference_image', 'duration_by_day', 'duration_by_night',
                         'spesific_gender', 'included', 'vendor_id', 'real_price', 'discount_price', 'status'];
        foreach ($directFields as $key) {
            if (array_key_exists($key, $data)) {
                $payload[$key] = $data[$key];
            }
        }

        $medical->update($payload);

        return new MedicalResource($medical->fresh());
    }

    /**
     * Delete a Medical Package.
     * 
     * DELETE /api/v1/medical/{id}
     */
    public function destroy($id)
    {
        $medical = Medical::findOrFail($id);
        $medical->delete();

        return response()->json(['message' => 'Medical package deleted successfully']);
    }
}
