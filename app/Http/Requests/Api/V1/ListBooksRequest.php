<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\BookAccessLevel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListBooksRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:200'],
            'genre' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:100'],
            'access_level' => ['nullable', Rule::enum(BookAccessLevel::class)],
            'sort' => ['nullable', Rule::in(['title', 'newest', 'rating'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,50'],
        ];
    }

    /**
     * @return array{
     *     q: string|null,
     *     genre: string|null,
     *     country: string|null,
     *     access_level: BookAccessLevel|null,
     *     sort: string,
     *     per_page: int
     * }
     */
    public function filters(): array
    {
        return [
            'q' => $this->filled('q') ? $this->string('q')->toString() : null,
            'genre' => $this->filled('genre') ? $this->string('genre')->toString() : null,
            'country' => $this->filled('country') ? $this->string('country')->toString() : null,
            'access_level' => $this->enum('access_level', BookAccessLevel::class),
            'sort' => $this->string('sort', 'title')->toString(),
            'per_page' => $this->integer('per_page', 20),
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (['q', 'genre', 'country', 'access_level', 'sort'] as $field) {
            $value = $this->input($field);

            if (is_string($value)) {
                $normalized[$field] = trim($value);
            }
        }

        foreach (['genre', 'access_level', 'sort'] as $field) {
            if (isset($normalized[$field])) {
                $normalized[$field] = mb_strtolower($normalized[$field]);
            }
        }

        $this->merge($normalized);
    }
}
