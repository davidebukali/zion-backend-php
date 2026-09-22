<?php

namespace Modules\Media\Actions;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Auth\Models\User;
use Modules\Media\Enums\MediaStatus;
use Modules\Media\Models\Media;

class GenerateUploadUrl
{
    public function __invoke(
        User $user,
        array $data
    ): array {
        $mimeType = $data['mime_type'];
        $size = (int) $data['size'];

        $pending = Media::query()
            ->where('user_id', $user->id)
            ->where('status', MediaStatus::Pending)
            ->count();

        if ($pending >= 20) {
            abort(
                429,
                'Too many pending uploads.'
            );
        }

        $media = Media::create([
            'id' => (string) Str::ulid(),
            'user_id' => $user->id,
            'disk' => 'r2',
            'path' => $this->generatePath($user),
            'type' => 'image',
            'mime_type' => $mimeType,
            'size' => $size,
            'status' => MediaStatus::Pending,
        ]);

        $expiresAt = now()->addMinutes(60);

        $s3 = Storage::disk('r2')->getClient();

        $cmd = $s3->getCommand('PutObject', [
            'Bucket' => 'zion-files',
            'Key' => $media->path,
        ]);

        $request = $s3->createPresignedRequest($cmd, '+1 hour');
        $presignedUrl = (string) $request->getUri();

        return [
            'media_id' => $media->id,
            'upload_url' => $presignedUrl,
            'expires_at' => $expiresAt,
        ];
    }

    protected function generatePath(User $user): string
    {
        $path = sprintf(
            'media/%s/%s/%s/original',
            now()->format('Y/m/d'),
            $user->id,
            (string) Str::ulid(),
        );

        return $path;
    }
}
