<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Book */
class BookResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'author' => $this->whenLoaded('author', fn (): array => [
                'id' => $this->author->id,
                'name' => $this->author->name,
            ]),
            'country' => $this->country,
            'access_level' => $this->access_level->value,
            'status' => $this->status->value,
            'average_rating' => $this->average_rating,
            'review_count' => $this->review_count,
            // shortcut: Cover delivery is deferred until the client requires a signed cover endpoint.
            'cover_url' => null,
            'has_epub' => (bool) ($this->has_epub ?? false),
            'has_audiobook' => (bool) ($this->has_audiobook ?? false),
        ];
    }
}
