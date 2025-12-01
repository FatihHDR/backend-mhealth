<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\SupabaseStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class UploadController extends Controller
{
    protected SupabaseStorageService $supabaseStorage;

    public function __construct(SupabaseStorageService $supabaseStorage)
    {
        $this->supabaseStorage = $supabaseStorage;
    }
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

        $subfolder = 'm-health-public';
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

    /**
     * Backwards-compatible alias for routes that call `upload`.
     * Some routes call UploadController@upload; delegate to `store`.
     */
    public function upload(Request $request)
    {
        return $this->store($request);
    }

    /**
     * Simple image upload endpoint using Supabase Storage REST API.
     * POST /api/v1/upload-image?folder=avatar&bucket=m-health-public
     * Accepts multipart/form-data with key `file` ONLY (no other fields).
     * Query params:
     *   - folder: subfolder path (default: 'general')
     *   - bucket: Supabase storage bucket name (default: 'm-health-public')
     * Returns JSON: { message, data: { path, url, filename, size, folder, bucket } }
     */
    public function uploadImage(Request $request)
    {
        // Only accept 'file' in form-data; reject any extra fields
        $allowedKeys = ['file'];
        $extraKeys = array_diff(array_keys($request->all()), $allowedKeys);
        if (! empty($extraKeys)) {
            return response()->json([
                'message' => 'Invalid request. Only "file" field is allowed in form-data. Use query params for folder/bucket.',
                'extra_fields' => array_values($extraKeys),
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:jpeg,jpg,png,gif,webp,svg|max:10240', // max 10MB for images
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid upload', 'errors' => $validator->errors()], 422);
        }

        $file = $request->file('file');

        // Get bucket from query param, default to 'm-health-public'
        $bucket = $request->query('bucket', 'm-health-public');

        // Get folder from query param, default to 'general'
        $folder = $request->query('folder', 'general');
        // Sanitize folder name
        $folderSanitized = Str::slug(substr($folder, 0, 80));
        if (empty($folderSanitized)) {
            $folderSanitized = 'general';
        }

        // Upload using Supabase Storage Service
        $result = $this->supabaseStorage->uploadImage(
            file: $file,
            bucket: $bucket,
            folder: $folderSanitized
        );

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message'] ?? 'Upload failed',
                'error' => $result['error'] ?? null,
                'response' => $result['response'] ?? null,
            ], $result['status_code'] ?? 500);
        }

        return response()->json([
            'message' => 'ok',
            'data' => [
                'path' => $result['path'],
                'url' => $result['url'],
                'optimized_url' => $result['optimized_url'] ?? null,
                'filename' => $result['filename'],
                'size' => $result['size'],
                'mime_type' => $result['mime_type'],
                'folder' => $folderSanitized,
                'bucket' => $bucket,
            ],
        ]);
    }
}
