<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\SlugHelper;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\Paginates;
use App\Http\Controllers\Concerns\Searchable;
use App\Http\Requests\StoreMedicalEquipmentRequest;
use App\Http\Requests\UpdateMedicalEquipmentRequest;
use App\Http\Resources\MedicalEquipmentCollection;
use App\Http\Resources\MedicalEquipmentResource;
use App\Models\MedicalEquipment;
use Illuminate\Http\Request;

class MedicalEquipmentController extends Controller
{
    use Paginates, Searchable;

    /**
     * Get list of medical equipment.
     * 
     * GET /api/v1/medical-equipment
     * 
     * Query params:
     * - per_page: number of items per page (default: 10, use 'all' for no pagination)
     * - search: search by title (case-insensitive)
     */
    public function index(Request $request)
    {
        $query = MedicalEquipment::orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $query = $this->applySearch($query, $request);
        $rows = $this->paginateQuery($query);
        return new MedicalEquipmentCollection($rows);
    }

    /**
     * Get a single medical equipment.
     * 
     * GET /api/v1/medical-equipment/{id}      - by UUID
     * GET /api/v1/medical-equipment/{slug}    - by slug
     */
    public function show($id)
    {
        // Auto-detect: UUID format or slug
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
            $equipment = MedicalEquipment::findOrFail($id);
        } else {
            $equipment = MedicalEquipment::where('slug', $id)->firstOrFail();
        }
        return new MedicalEquipmentResource($equipment);
    }

    /**
     * Display a medical equipment by slug.
     * 
     * GET /api/v1/medical-equipment/slug/{slug}
     */
    public function showBySlug($slug)
    {
        $equipment = MedicalEquipment::where('slug', $slug)->firstOrFail();
        return new MedicalEquipmentResource($equipment);
    }

    /**
     * Create a new Medical Equipment.
     * 
     * POST /api/v1/medical-equipment
     */
    public function store(StoreMedicalEquipmentRequest $request)
    {
        $data = $request->validated();

        $payload = [
            'en_title' => $data['en_title'] ?? $data['title'] ?? '',
            'id_title' => $data['id_title'] ?? $data['title'] ?? '',
            'slug' => SlugHelper::generate($data['en_title'] ?? $data['title'] ?? ''),
            'en_description' => $data['en_description'] ?? $data['description'] ?? null,
            'id_description' => $data['id_description'] ?? $data['description'] ?? null,
            'highlight_image' => $data['highlight_image'] ?? null,
            'reference_image' => $data['reference_image'] ?? [],
            'spesific_gender' => $data['spesific_gender'] ?? 'both',
            'vendor_id' => $data['vendor_id'] ?? null,
            'real_price' => isset($data['real_price']) ? (string) $data['real_price'] : null,
            'discount_price' => isset($data['discount_price']) ? (string) $data['discount_price'] : null,
            'status' => $data['status'] ?? 'draft',
        ];

        $equipment = MedicalEquipment::create($payload);

        return (new MedicalEquipmentResource($equipment))->response()->setStatusCode(201);
    }

    /**
     * Update a Medical Equipment.
     * 
     * PUT/PATCH /api/v1/medical-equipment/{id}
     */
    public function update(UpdateMedicalEquipmentRequest $request, $id)
    {
        $equipment = MedicalEquipment::findOrFail($id);
        $data = $request->validated();

        $payload = [];

        // Handle title
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
            if (isset($data['id_title'])) {
                $payload['id_title'] = $data['id_title'];
            }
        }

        // Handle description
        if (isset($data['description'])) {
            $payload['en_description'] = $data['en_description'] ?? $data['description'];
            $payload['id_description'] = $data['id_description'] ?? $data['description'];
        } else {
            if (isset($data['en_description'])) {
                $payload['en_description'] = $data['en_description'];
            }
            if (isset($data['id_description'])) {
                $payload['id_description'] = $data['id_description'];
            }
        }

        // Direct fields
        $directFields = [
            'highlight_image', 
            'reference_image', 
            'spesific_gender', 
            'vendor_id',
            'real_price', 
            'discount_price', 
            'status'
        ];

        // Normalize numeric price inputs to strings when updating
        if (array_key_exists('real_price', $data)) {
            $payload['real_price'] = $data['real_price'] === null ? null : (string) $data['real_price'];
        }
        if (array_key_exists('discount_price', $data)) {
            $payload['discount_price'] = $data['discount_price'] === null ? null : (string) $data['discount_price'];
        }

        foreach ($directFields as $key) {
            if (in_array($key, ['real_price', 'discount_price'])) continue; // already handled
            if (array_key_exists($key, $data)) {
                $payload[$key] = $data[$key];
            }
        }

        if (!empty($payload)) {
            $equipment->update($payload);
        }

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
