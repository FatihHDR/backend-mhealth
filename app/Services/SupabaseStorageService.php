<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class SupabaseStorageService
{
    protected string $supabaseUrl;
    protected string $serviceRoleKey;
    protected int $timeout;

    public function __construct()
    {
        $this->supabaseUrl = config('supabase.url');
        $this->serviceRoleKey = config('supabase.service_role_key');
        $this->timeout = 30; // 30 seconds timeout
    }

    /**
     * Get the storage API base URL
     */
    protected function getStorageUrl(): string
    {
        return rtrim($this->supabaseUrl, '/') . '/storage/v1';
    }

    /**
     * Get HTTP client with authentication headers
     */
    protected function client()
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->serviceRoleKey,
            'apikey' => $this->serviceRoleKey,
        ])->timeout($this->timeout);
    }

    /**
     * Upload a file to Supabase storage
     * Returns the public URL of the uploaded file
     */
    public function uploadFile(UploadedFile $file, string $bucket, string $folder = ''): array
    {
        // Generate a unique filename to avoid collisions
        $extension = $file->getClientOriginalExtension();
        $filename = now()->format('YmdHis') . '_' . Str::random(8) . '.' . $extension;
        $path = $folder ? "{$folder}/{$filename}" : $filename;

        try {
            // Get the file contents
            $fileContents = file_get_contents($file->getRealPath());
            $mimeType = $file->getMimeType();

            // Upload to Supabase Storage REST API
            $response = $this->client()
                ->withHeaders([
                    'Content-Type' => $mimeType,
                    'x-upsert' => 'false', // Don't overwrite if exists
                ])
                ->withBody($fileContents, $mimeType)
                ->post("{$this->getStorageUrl()}/object/{$bucket}/{$path}");

            if ($response->failed()) {
                $error = $response->json();
                return [
                    'success' => false,
                    'message' => $error['message'] ?? $error['error'] ?? 'Upload failed',
                    'status_code' => $response->status(),
                    'response' => $error,
                ];
            }

            // Get the public URL
            $publicUrl = $this->getPublicUrl($bucket, $path);

            return [
                'success' => true,
                'path' => $path,
                'url' => $publicUrl,
                'filename' => $filename,
                'size' => $file->getSize(),
                'mime_type' => $mimeType,
                'bucket' => $bucket,
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return [
                'success' => false,
                'message' => 'Connection timeout - unable to reach Supabase Storage',
                'error' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Upload and optimize an image
     * Supabase can automatically resize and optimize images
     */
    public function uploadImage(
        UploadedFile $file,
        string $bucket,
        string $folder = '',
        int $maxWidth = 1920,
        int $quality = 80
    ): array {
        // Validate it's actually an image
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            return [
                'success' => false,
                'message' => 'File must be an image (jpeg, png, gif, webp, svg)',
            ];
        }

        $result = $this->uploadFile($file, $bucket, $folder);

        if ($result['success']) {
            // Get optimized URL with transformations
            // Supabase supports on-the-fly image transformations
            $result['optimized_url'] = $result['url'] .
                "?width={$maxWidth}&quality={$quality}";
        }

        return $result;
    }

    /**
     * Get public URL for a file
     */
    public function getPublicUrl(string $bucket, string $path): string
    {
        return "{$this->getStorageUrl()}/object/public/{$bucket}/{$path}";
    }

    /**
     * Delete a file from storage
     */
    public function deleteFile(string $bucket, string $path): array
    {
        try {
            $response = $this->client()
                ->delete("{$this->getStorageUrl()}/object/{$bucket}", [
                    'prefixes' => [$path],
                ]);

            if ($response->failed()) {
                return [
                    'success' => false,
                    'message' => $response->json()['message'] ?? 'Delete failed',
                ];
            }

            return ['success' => true];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * List files in a bucket/folder
     */
    public function listFiles(string $bucket, string $folder = '', int $limit = 100): array
    {
        try {
            $response = $this->client()
                ->post("{$this->getStorageUrl()}/object/list/{$bucket}", [
                    'prefix' => $folder,
                    'limit' => $limit,
                ]);

            if ($response->failed()) {
                return [
                    'success' => false,
                    'message' => $response->json()['message'] ?? 'List failed',
                    'files' => [],
                ];
            }

            return [
                'success' => true,
                'files' => $response->json(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'files' => [],
            ];
        }
    }

    /**
     * Get a signed URL for private files
     * Useful for temporary access to private content
     */
    public function getSignedUrl(string $bucket, string $path, int $expiresIn = 3600): array
    {
        try {
            $response = $this->client()
                ->post("{$this->getStorageUrl()}/object/sign/{$bucket}/{$path}", [
                    'expiresIn' => $expiresIn,
                ]);

            if ($response->failed()) {
                return [
                    'success' => false,
                    'message' => $response->json()['message'] ?? 'Failed to create signed URL',
                ];
            }

            $data = $response->json();

            return [
                'success' => true,
                'signed_url' => $this->supabaseUrl . '/storage/v1' . $data['signedURL'],
                'expires_in' => $expiresIn,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Move/rename a file
     */
    public function moveFile(string $bucket, string $fromPath, string $toPath): array
    {
        try {
            $response = $this->client()
                ->post("{$this->getStorageUrl()}/object/move", [
                    'bucketId' => $bucket,
                    'sourceKey' => $fromPath,
                    'destinationKey' => $toPath,
                ]);

            if ($response->failed()) {
                return [
                    'success' => false,
                    'message' => $response->json()['message'] ?? 'Move failed',
                ];
            }

            return ['success' => true];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Copy a file
     */
    public function copyFile(string $bucket, string $fromPath, string $toPath): array
    {
        try {
            $response = $this->client()
                ->post("{$this->getStorageUrl()}/object/copy", [
                    'bucketId' => $bucket,
                    'sourceKey' => $fromPath,
                    'destinationKey' => $toPath,
                ]);

            if ($response->failed()) {
                return [
                    'success' => false,
                    'message' => $response->json()['message'] ?? 'Copy failed',
                ];
            }

            return ['success' => true];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if bucket exists and is accessible
     */
    public function checkBucket(string $bucket): array
    {
        try {
            $response = $this->client()
                ->get("{$this->getStorageUrl()}/bucket/{$bucket}");

            if ($response->failed()) {
                return [
                    'success' => false,
                    'exists' => false,
                    'message' => $response->json()['message'] ?? 'Bucket not found or not accessible',
                ];
            }

            return [
                'success' => true,
                'exists' => true,
                'bucket' => $response->json(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'exists' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
