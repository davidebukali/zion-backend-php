<?php

namespace Modules\Media\Services;

use Aws\S3\S3Client;

class R2StorageService
{
    private S3Client $client;

    public function __construct()
    {
        $this->client = new S3Client([
            'version' => 'latest',
            'region' => config('filesystems.disks.r2.region', 'auto'),
            'endpoint' => config('filesystems.disks.r2.endpoint'),
            'credentials' => [
                'key' => config('filesystems.disks.r2.key'),
                'secret' => config('filesystems.disks.r2.secret'),
            ],
        ]);
    }

    public function head(string $path): ?array
    {
        try {
            $result = $this->client->headObject([
                'Bucket' => config('filesystems.disks.r2.bucket'),
                'Key' => $path,
            ]);

            return $result->toArray();
        } catch (\Aws\S3\Exception\S3Exception $e) {
            if ($e->getStatusCode() === 404) {
                return null;
            }

            throw $e;
        }
    }
}