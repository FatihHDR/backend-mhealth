<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\SlugHelper;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\Paginates;
use App\Http\Resources\MedicalEquipmentResource;
use App\Models\MedicalEquipment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MedicalEquipmentController extends Controller
{
    use Paginates;

    public function index()
    {
        $query = MedicalEquipment::orderBy('created_at', 'desc');
        $rows = $this->paginateQuery($query);
        return MedicalEquipmentResource::collection($rows);
    }

    public function show($id)
    {
        $equipment = MedicalEquipment::findOrFail($id);
        return new MedicalEquipmentResource($equipment);
    }

    /**
     * Create a new Medical Equipment.
     * 
     * POST /api/v1/medical-equipment
     * 
     * Payload:
     * {
     *   "title": "Equipment Name",                                 // Required - will be used for both en_title and id_title
     *   "en_title": "English Title",                               // Optional - override English title
     *   "id_title": "Judul Indonesia",                             // Optional - override Indonesian title
     *   "description": "Equipment description...",                 // Optional - will be used for both en/id
     *   "en_description": "English description...",                // Optional
     *   "id_description": "Deskripsi Indonesia...",                // Optional
     *   "highlight_image": "https://supabase.../equipment.jpg",    // Optional - Supabase bucket URL
     *   "reference_image": [                                       // Optional - Array of Supabase bucket URLs
     *     "https://supabase.../img1.jpg",
     *     "https://supabase.../img2.jpg"
     *   ],
     *   "spesific_gender": "all",                                  // Optional - all/male/female
     *   "real_price": 1000000,                                     // Optional - Original price
     *   "discount_price": 900000,                                  // Optional - Discounted price
     *   "status": "active"                                         // Optional - active/inactive
     * }
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required_without_all:en_title,id_title|string|max:255',
            'en_title' => 'nullable|string|max:255',
            'id_title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'en_description' => 'nullable|string',
            'id_description' => 'nullable|string',
            'highlight_image' => 'nullable|url',
            'reference_image' => 'nullable|array',
            'reference_image.*' => 'url',
            'spesific_gender' => 'nullable|string|in:all,male,female',
            'real_price' => 'nullable|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        $payload = [
            'en_title' => $data['en_title'] ?? $data['title'] ?? '',
            'id_title' => $data['id_title'] ?? $data['title'] ?? '',
            'slug' => SlugHelper::generate($data['en_title'] ?? $data['title'] ?? Str::random(10)),
            'en_description' => $data['en_description'] ?? $data['description'] ?? null,
            'id_description' => $data['id_description'] ?? $data['description'] ?? null,
            'highlight_image' => $data['highlight_image'] ?? null,
            'reference_image' => $data['reference_image'] ?? [],
            'spesific_gender' => $data['spesific_gender'] ?? 'all',
            'real_price' => $data['real_price'] ?? null,
            'discount_price' => $data['discount_price'] ?? null,
            'status' => $data['status'] ?? 'active',
        ];

        $equipment = MedicalEquipment::create($payload);

        return (new MedicalEquipmentResource($equipment))->response()->setStatusCode(201);
    }

    /**
     * Update a Medical Equipment.
     * 
     * PUT/PATCH /api/v1/medical-equipment/{id}
     * 
     * Payload: Same as store, all fields optional
     */
    public function update(Request $request, $id)
    {
        $equipment = MedicalEquipment::findOrFail($id);

        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'en_title' => 'nullable|string|max:255',
            'id_title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'en_description' => 'nullable|string',
            'id_description' => 'nullable|string',
            'highlight_image' => 'nullable|url',
            'reference_image' => 'nullable|array',
            'reference_image.*' => 'url',
            'spesific_gender' => 'nullable|string|in:all,male,female',
            'real_price' => 'nullable|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        $payload = [];

        if (isset($data['title'])) {
            $newTitle = $data['en_title'] ?? $data['title'];
            $payload['en_title'] = $newTitle;
            $payload['id_title'] = $data['id_title'] ?? $data['title'];
            $newSlug = SlugHelper::regenerateIfChanged($newTitle, $equipment->slug, $equipment->en_title);
            if ($newSlug) {
                $payload['slug'] = $newSlug;
            }
        } else {
            if (isset($data['en_title'])) {
                $payload['en_title'] = $data['en_title'];
                $newSlug = SlugHelper::regenerateIfChanged($data['en_title'], $equipment->slug, $equipment->en_title);
                if ($newSlug) {
                    $payload['slug'] = $newSlug;
                }
            }
            if (isset($data['id_title'])) $payload['id_title'] = $data['id_title'];
        }

        if (isset($data['description'])) {
            $payload['en_description'] = $data['en_description'] ?? $data['description'];
            $payload['id_description'] = $data['id_description'] ?? $data['description'];
        } else {
            if (isset($data['en_description'])) $payload['en_description'] = $data['en_description'];
            if (isset($data['id_description'])) $payload['id_description'] = $data['id_description'];
        }

        $directFields = ['highlight_image', 'reference_image', 'spesific_gender', 'real_price', 'discount_price', 'status'];
        foreach ($directFields as $key) {
            if (array_key_exists($key, $data)) {
                $payload[$key] = $data[$key];
            }
        }

        $equipment->update($payload);

        return new MedicalEquipmentResource($equipment->fresh());
    }

    /**
     * Delete a Medical Equipment.
     * 
     * DELETE /api/v1/medical-equipment/{id}
     */
    public function destroy($id)
    {
        $equipment = MedicalEquipment::findOrFail($id);
        $equipment->delete();

        return response()->json(['message' => 'Medical equipment deleted successfully']);
    }
}
