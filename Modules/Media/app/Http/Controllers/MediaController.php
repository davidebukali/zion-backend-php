<?php

namespace Modules\Media\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\RespondsWithApi;
use Illuminate\Http\Request;
use Modules\Media\Actions\GenerateUploadUrl;
use Modules\Media\Http\Requests\GenerateUploadUrlRequest;

class MediaController extends Controller
{
    use RespondsWithApi;

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
}
