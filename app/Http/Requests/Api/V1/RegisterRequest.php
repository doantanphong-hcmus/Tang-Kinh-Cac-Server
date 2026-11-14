<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

final class RegisterRequest extends FormRequest
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
            'full_name' => ['required', 'string', 'min:2', 'max:100'],
            'username' => ['required', 'string', 'min:4', 'max:20', 'regex:/^[A-Za-z0-9._]+$/'],
            'birth_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'phone_number' => ['required', 'string', 'regex:/^\+?[0-9]{9,15}$/'],
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:128', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ];
    }

    /**
     * @return array{
     *     full_name: string,
     *     username: string,
     *     username_normalized: string,
     *     birth_date: string,
     *     phone_number: string,
     *     email: string,
     *     password: string
     * }
     */
    public function registrationData(): array
    {
        return [
            'full_name' => $this->string('full_name')->toString(),
            'username' => $this->string('username')->toString(),
            'username_normalized' => Str::lower($this->string('username')->toString()),
            'birth_date' => $this->string('birth_date')->toString(),
            'phone_number' => $this->string('phone_number')->toString(),
            'email' => $this->string('email')->toString(),
            'password' => $this->string('password')->toString(),
        ];
    }

    protected function prepareForValidation(): void
    {
        $fullName = $this->input('full_name');
        $username = $this->input('username');
        $phoneNumber = $this->input('phone_number');
        $email = $this->input('email');

        // Normalize before validation so conflict checks and persistence use the same identity values.
        $this->merge([
            'full_name' => is_string($fullName) ? trim($fullName) : $fullName,
            'username' => is_string($username) ? trim($username) : $username,
            'phone_number' => is_string($phoneNumber)
                ? preg_replace('/[\s().-]+/', '', trim($phoneNumber))
                : $phoneNumber,
            'email' => is_string($email) ? Str::lower(trim($email)) : $email,
        ]);
    }
}
