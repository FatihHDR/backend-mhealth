<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class UploadController extends Controller
{
    /**
     * Generic single-route file upload.
     * Accepts multipart/form-data with key `file`.
     * Optional fields: `model` (string), `id` (numeric/string), `field` (string).
     * Returns JSON: { path, url, filename, disk, size }
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:jpeg,jpg,png,gif,webp,svg,mp4,mp3,pdf,txt|max:51200', // max 50MB
            'model' => 'nullable|string',
            'id' => 'nullable',
            'field' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid upload', 'errors' => $validator->errors()], 422);
        }

        $file = $request->file('file');

        // Determine disk to use. Use 'public' by default so files are web-accessible via Storage::url().
        $disk = config('filesystems.default', 'public');

        // Build a tidy folder path. If model and id are provided, use them to namespace the file.
        $model = $request->input('model');
        $id = $request->input('id');
        $field = $request->input('field');

        $subfolder = 'uploads';
        if ($model) {
            $modelSanitized = Str::slug(substr($model, 0, 80));
            $subfolder .= "/{$modelSanitized}";
        }
        if ($id) {
            $subfolder .= "/{$id}";
        }
        if ($field) {
            $subfolder .= "/{$field}";
        }

        // Filename: timestamp + random + original extension
        $filename = now()->format('YmdHis') . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();

        // Store file
        $path = $file->storeAs($subfolder, $filename, $disk);

        // Generate URL (Storage::url works when disk is configured to public or has url)
        $url = null;
        try {
            /** @var \Illuminate\Filesystem\FilesystemAdapter $diskDriver */
            $diskDriver = Storage::disk($disk);
            $url = $diskDriver->url($path);
        } catch (\Exception $e) {
            // Fallback: return the path; caller can construct URL client-side
            $url = $path;
        }

        return response()->json([
            'message' => 'ok',
            'data' => [
                'path' => $path,
                'url' => $url,
                'filename' => $filename,
                'disk' => $disk,
                'size' => $file->getSize(),
                'model' => $model,
                'id' => $id,
                'field' => $field,
            ],
        ]);
    }
}
