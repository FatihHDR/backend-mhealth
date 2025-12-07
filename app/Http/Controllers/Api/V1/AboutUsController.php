<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAboutUsRequest;
use App\Http\Resources\AboutUsResource;
use App\Models\AboutUs;

class AboutUsController extends Controller
{
    /**
     * Get all about us entries.
     * 
     * GET /api/v1/about-us
     */
    public function index()
    {
        $rows = AboutUs::orderBy('created_at', 'desc')->get();
        return AboutUsResource::collection($rows);
    }

    /**
     * Get a single about us entry.
     * 
     * GET /api/v1/about-us/{id}
     */
    public function show($id)
    {
        $aboutUs = AboutUs::findOrFail($id);
        return new AboutUsResource($aboutUs);
    }

    /**
     * Update an about us entry.
     * 
     * PATCH /api/v1/about-us/{id}
     */
    public function update(UpdateAboutUsRequest $request, $id)
    {
        $aboutUs = AboutUs::findOrFail($id);
        $data = $request->validated();

        $payload = [];

        // Handle title
        if (isset($data['title'])) {
            $payload['en_title'] = $data['en_title'] ?? $data['title'];
            $payload['id_title'] = $data['id_title'] ?? $data['title'];
        } else {
            if (isset($data['en_title'])) {
                $payload['en_title'] = $data['en_title'];
            }
            if (isset($data['id_title'])) {
                $payload['id_title'] = $data['id_title'];
            }
        }

        // Handle about content
        if (isset($data['about_content'])) {
            $payload['en_about_content'] = $data['en_about_content'] ?? $data['about_content'];
            $payload['id_about_content'] = $data['id_about_content'] ?? $data['about_content'];
        } else {
            if (isset($data['en_about_content'])) {
                $payload['en_about_content'] = $data['en_about_content'];
            }
            if (isset($data['id_about_content'])) {
                $payload['id_about_content'] = $data['id_about_content'];
            }
        }

        // Handle brand tagline
        if (isset($data['brand_tagline'])) {
            $payload['en_brand_tagline'] = $data['en_brand_tagline'] ?? $data['brand_tagline'];
            $payload['id_brand_tagline'] = $data['id_brand_tagline'] ?? $data['brand_tagline'];
        } else {
            if (isset($data['en_brand_tagline'])) {
                $payload['en_brand_tagline'] = $data['en_brand_tagline'];
            }
            if (isset($data['id_brand_tagline'])) {
                $payload['id_brand_tagline'] = $data['id_brand_tagline'];
            }
        }

        if (!empty($payload)) {
            $aboutUs->update($payload);
        }

        return new AboutUsResource($aboutUs->fresh());
    }
}
