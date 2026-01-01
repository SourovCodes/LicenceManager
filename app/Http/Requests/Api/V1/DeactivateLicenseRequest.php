<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class DeactivateLicenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'license_key' => ['required', 'string', 'max:50'],
            'domain' => ['required', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'license_key.required' => 'License key is required.',
            'domain.required' => 'Domain is required.',
        ];
    }
}
