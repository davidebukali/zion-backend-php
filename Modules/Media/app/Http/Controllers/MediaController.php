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
