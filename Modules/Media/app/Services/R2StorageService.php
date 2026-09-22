<?php

namespace Modules\Media\Services;

use Aws\S3\S3Client;

class R2StorageService
{
    private S3Client \;

    public function __construct()
    {
        \->client = new S3Client([
            'version' => 'latest',
            'region' => config('filesystems.disks.r2.region', 'auto'),
            'endpoint' => config('filesystems.disks.r2.endpoint'),
            'credentials' => [
                'key' => config('filesystems.disks.r2.key'),
                'secret' => config('filesystems.disks.r2.secret'),
            ],
        ]);
    }

    public function head(string \): ?array
    {
        try {
            \ = \->client->headObject([
                'Bucket' => config('filesystems.disks.r2.bucket'),
                'Key' => \,
            ]);

            return \->toArray();
        } catch (\Aws\S3\Exception\S3Exception \) {
            if (\->getStatusCode() === 404) {
                return null;
            }

            throw \;
        }
    }

    public function get(string \): ?string
    {
        try {
            \ = \->client->getObject([
                'Bucket' => config('filesystems.disks.r2.bucket'),
                'Key' => \,
            ]);

            return (string) \['Body'];
        } catch (\Aws\S3\Exception\S3Exception \) {
            if (\->getStatusCode() === 404) {
                return null;
            }

            throw \;
        }
    }

    public function put(string \, mixed \, string \ = 'application/octet-stream'): array
    {
        \ = \->client->putObject([
            'Bucket' => config('filesystems.disks.r2.bucket'),
            'Key' => \,
            'Body' => \,
            'ContentType' => \,
        ]);

        return \->toArray();
    }
}
