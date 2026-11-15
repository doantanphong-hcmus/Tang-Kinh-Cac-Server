<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Book;
use Illuminate\Http\Request;

/** @mixin Book */
final class BookDetailResource extends BookResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return array_replace(parent::toArray($request), [
            'original_title' => $this->original_title,
            'alternative_titles' => $this->whenLoaded(
                'alternativeTitles',
                fn (): array => $this->alternativeTitles->pluck('title')->values()->all(),
            ),
            'author' => $this->whenLoaded('author', fn (): array => [
                'id' => $this->author->id,
                'name' => $this->author->name,
                'original_name' => $this->author->original_name,
            ]),
            'original_language' => $this->original_language,
            'publication_period' => $this->publication_period,
            'genres' => GenreResource::collection($this->whenLoaded('genres')),
            'structure' => $this->structure,
            'historical_setting' => $this->historical_setting,
            'synopsis' => $this->synopsis,
            'keywords' => $this->whenLoaded(
                'keywords',
                fn (): array => $this->keywords->pluck('value')->values()->all(),
            ),
            'available_content_types' => array_keys(array_filter([
                'epub' => (bool) ($this->has_epub ?? false),
                'audiobook' => (bool) ($this->has_audiobook ?? false),
            ])),
        ]);
    }
}
