<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\SlugHelper;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\Paginates;
use App\Http\Controllers\Concerns\Searchable;
use App\Http\Requests\StoreMedicalRequest;
use App\Http\Requests\UpdateMedicalRequest;
use App\Http\Resources\MedicalCollection;
use App\Http\Resources\MedicalResource;
use App\Models\Medical;
use Illuminate\Http\Request;

class MedicalController extends Controller
{
    use Paginates, Searchable;

    /**
     * Get list of medical packages.
     * 
     * GET /api/v1/medical
     * 
     * Query params:
     * - per_page: number of items per page (default: 10, use 'all' for no pagination)
     * - search: search by title (case-insensitive)
     */
    public function index(Request $request)
    {
        $query = Medical::orderBy('created_at', 'desc');
        $query = $this->applySearch($query, $request);
        $rows = $this->paginateQuery($query);
        return new MedicalCollection($rows);
    }

    /**
     * Get a single medical package.
     * 
     * GET /api/v1/medical/{id}
     */
    public function show($id)
    {
        $row = Medical::with('vendor')->findOrFail($id);
        return new MedicalResource($row);
    }

    /**
     * Create a new Medical Package.
     * 
     * POST /api/v1/medical
     */
    public function store(StoreMedicalRequest $request)
    {
        $data = $request->validated();

        $payload = [
            'en_title' => $data['en_title'] ?? $data['title'] ?? '',
            'id_title' => $data['id_title'] ?? $data['title'] ?? '',
            'slug' => SlugHelper::generate($data['en_title'] ?? $data['title'] ?? ''),
            'en_tagline' => $data['en_tagline'] ?? $data['tagline'] ?? null,
            'id_tagline' => $data['id_tagline'] ?? $data['tagline'] ?? null,
            'en_medical_package_content' => $data['en_medical_package_content'] ?? $data['content'] ?? null,
            'id_medical_package_content' => $data['id_medical_package_content'] ?? $data['content'] ?? null,
            'highlight_image' => $data['highlight_image'] ?? null,
            'reference_image' => $data['reference_image'] ?? [],
            'duration_by_day' => $data['duration_by_day'] ?? null,
            'duration_by_night' => $data['duration_by_night'] ?? null,
            'spesific_gender' => $data['spesific_gender'] ?? 'both',
            'included' => $data['included'] ?? [],
            'vendor_id' => $data['vendor_id'] ?? null,
            'real_price' => $data['real_price'] ?? null,
            'discount_price' => $data['discount_price'] ?? null,
            'status' => $data['status'] ?? 'draft',
        ];

        $medical = Medical::create($payload);

        return (new MedicalResource($medical))->response()->setStatusCode(201);
    }

    /**
     * Update a Medical Package.
     * 
     * PUT/PATCH /api/v1/medical/{id}
     */
    public function update(UpdateMedicalRequest $request, $id)
    {
        $medical = Medical::findOrFail($id);
        $data = $request->validated();

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
            if (isset($data['id_title'])) {
                $payload['id_title'] = $data['id_title'];
            }
        }

        // Handle tagline
        if (isset($data['tagline'])) {
            $payload['en_tagline'] = $data['en_tagline'] ?? $data['tagline'];
            $payload['id_tagline'] = $data['id_tagline'] ?? $data['tagline'];
        } else {
            if (isset($data['en_tagline'])) {
                $payload['en_tagline'] = $data['en_tagline'];
            }
            if (isset($data['id_tagline'])) {
                $payload['id_tagline'] = $data['id_tagline'];
            }
        }

        // Handle content
        if (isset($data['content'])) {
            $payload['en_medical_package_content'] = $data['en_medical_package_content'] ?? $data['content'];
            $payload['id_medical_package_content'] = $data['id_medical_package_content'] ?? $data['content'];
        } else {
            if (isset($data['en_medical_package_content'])) {
                $payload['en_medical_package_content'] = $data['en_medical_package_content'];
            }
            if (isset($data['id_medical_package_content'])) {
                $payload['id_medical_package_content'] = $data['id_medical_package_content'];
            }
        }

        // Direct fields
        $directFields = [
            'highlight_image', 
            'reference_image', 
            'duration_by_day', 
            'duration_by_night',
            'spesific_gender', 
            'included', 
            'vendor_id', 
            'real_price', 
            'discount_price', 
            'status'
        ];
        
        foreach ($directFields as $key) {
            if (array_key_exists($key, $data)) {
                $payload[$key] = $data[$key];
            }
        }

        if (!empty($payload)) {
            $medical->update($payload);
        }

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
