<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ActivateLicenseRequest extends FormRequest
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
            'product_slug' => ['required', 'string', 'max:100'],
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
            'product_slug.required' => 'Product identifier is required.',
        ];
    }
}
