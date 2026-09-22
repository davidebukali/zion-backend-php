<?php

namespace Modules\Media\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\RespondsWithApi;
use Modules\Media\Actions\ConfirmUpload;
use Modules\Media\Actions\GenerateUploadUrl;
use Modules\Media\Http\Requests\ConfirmUploadRequest;
use Modules\Media\Http\Requests\GenerateUploadUrlRequest;
use Modules\Media\Models\Media;
use Modules\Media\Transformers\MediaResource;

class MediaController extends Controller
{
    use RespondsWithApi;

    public function uploadUrl(
        GenerateUploadUrlRequest \,
        GenerateUploadUrl     ) {
        \ = \(
            \->user(),
            \->validated()
        );

        return \->success(
            \,
            'Upload URL generated.'
        );
    }

    public function confirm(
        ConfirmUploadRequest \,
        Media \,
        ConfirmUpload     ) {
        \ = \(
            \->user(),
            \,
            \->validated('checksum')
        );

        return \->success(
            new MediaResource(\),
            'Media upload confirmed.'
        );
    }
}
