<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Book */
final class AudiobookDescriptorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'book_id' => $this->id,
            'title' => $this->title,
            // shortcut: Cover delivery is deferred until the client requires a signed cover endpoint.
            'cover_url' => null,
            'total_duration_seconds' => (int) $this->active_audio_total_duration_seconds,
            'track_count' => $this->active_audio_track_count,
            'available' => true,
        ];
    }
}
