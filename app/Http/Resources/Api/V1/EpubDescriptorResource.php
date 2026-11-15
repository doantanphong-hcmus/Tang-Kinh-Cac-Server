<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\BookFile;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin BookFile */
final class EpubDescriptorResource extends JsonResource
{
    public function __construct(
        BookFile $resource,
        private readonly string $downloadUrl,
        private readonly CarbonInterface $expiresAt,
    ) {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'file_id' => $this->id,
            'book_id' => $this->book_id,
            'version' => $this->version,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'checksum_sha256' => $this->checksum_sha256,
            'download_url' => $this->downloadUrl,
            'expires_at' => $this->expiresAt->utc()->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
