<?php

namespace Modules\Media\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Media\Actions\ConfirmUpload;
use Modules\Media\Actions\GenerateUploadUrl;
use Modules\Media\Http\Requests\ConfirmUploadRequest;
use Modules\Media\Http\Requests\GenerateUploadUrlRequest;
use Modules\Media\Models\Media;
use Modules\Media\Transformers\MediaResource;

class MediaController extends Controller
{
    use RespondsWithApi;

    /**
     * @group Media Management
     * @authenticated
     * 
     * Get Media Details
     * 
     * Retrieve metadata of a media asset owned by the authenticated user.
     * 
     * @urlParam media string required The UUID of the media. Example: 9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Media retrieved successfully.",
     *   "data": {
     *     "id": "9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d",
     *     "url": "https://storage.example.com/uploads/photo.jpg",
     *     "mime_type": "image/jpeg",
     *     "size": 5242880
     *   }
     * }
     * @response 403 status=403 scenario="Forbidden" {
     *   "message": "You do not own this media."
     * }
     */
    public function show(Media $media, Request $request)
    {
        if ($request->user()->cannot('view', $media)) {
            abort(403, 'You do not own this media.');
        }

        return $this->success(
            new MediaResource($media),
            'Media retrieved successfully.'
        );
    }

    /**
     * @group Media Management
     * @authenticated
     * 
     * Generate Presigned Upload URL
     * 
     * Generate an S3 direct presigned upload URL and temporary media asset record.
     * 
     * @bodyParam filename string required Original filename including extension. Example: photo.jpg
     * @bodyParam mime_type string required Allowed MIME type (image/jpeg, image/png, image/webp). Example: image/jpeg
     * @bodyParam size integer required File size in bytes (max 10MB). Example: 5242880
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Upload URL generated.",
     *   "data": {
     *     "id": "9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d",
     *     "upload_url": "https://storage.example.com/uploads/photo.jpg?signature=..."
     *   }
     * }
     * @response 422 status=422 scenario="Validation error" {
     *   "message": "The mime_type field must be one of: image/jpeg, image/png, image/webp."
     * }
     */
    public function uploadUrl(
        GenerateUploadUrlRequest $request,
        GenerateUploadUrl $action
    ) {
        $data = $action(
            $request->user(),
            $request->validated()
        );

        return $this->success(
            $data,
            'Upload URL generated.'
        );
    }

    /**
     * @group Media Management
     * @authenticated
     * 
     * Confirm Media Upload
     * 
     * Confirm that direct file upload to storage was successful.
     * 
     * @urlParam media string required The UUID of the media. Example: 9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d
     * @bodyParam checksum string Optional checksum hash of uploaded file. Example: e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Media upload confirmed.",
     *   "data": {
     *     "id": "9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d",
     *     "status": "confirmed"
     *   }
     * }
     */
    public function confirm(
        ConfirmUploadRequest $request,
        Media $media,
        ConfirmUpload $action
    ) {
        $data = $action(
            $request->user(),
            $media,
            $request->validated('checksum')
        );

        return $this->success(
            new MediaResource($data),
            'Media upload confirmed.'
        );
    }
}
