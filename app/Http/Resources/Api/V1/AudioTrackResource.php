<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\BookFile;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin BookFile */
final class AudioTrackResource extends JsonResource
{
    public function __construct(
        BookFile $resource,
        private readonly string $streamUrl,
        private readonly CarbonInterface $expiresAt,
    ) {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'sort_order' => $this->sort_order,
            'duration_seconds' => $this->duration_seconds,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'checksum_sha256' => $this->checksum_sha256,
            'stream_url' => $this->streamUrl,
            'expires_at' => $this->expiresAt->utc()->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
