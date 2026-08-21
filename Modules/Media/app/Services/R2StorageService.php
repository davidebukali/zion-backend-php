<?php

namespace Modules\Media\Services;

use Illuminate\Support\Facades\Storage;

class R2StorageService
{
    public function createUploadUrl(
        string $path,
        string $mimeType,
        int $expiresIn = 600
    ): string {
        // Generate presigned PUT URL
    }

    public function exists(string $path): bool
    {
        // Check R2 object
    }

    public function delete(string $path): void
    {
        // Delete R2 object
    }
}