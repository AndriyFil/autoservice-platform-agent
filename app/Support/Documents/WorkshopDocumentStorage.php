<?php

namespace App\Support\Documents;

use Illuminate\Support\Facades\Storage;

class WorkshopDocumentStorage
{
    /**
     * @return array{disk: string, path: string, size_bytes: int, checksum_sha256: string}
     */
    public function put(string $path, string $contents): array
    {
        $disk = config('documents.disk');

        Storage::disk($disk)->put($path, $contents, [
            'visibility' => 'private',
        ]);

        return [
            'disk' => $disk,
            'path' => $path,
            'size_bytes' => strlen($contents),
            'checksum_sha256' => hash('sha256', $contents),
        ];
    }
}
