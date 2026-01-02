<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreNaldaCsvUploadRequest extends FormRequest
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
            'domain' => ['required', 'string', 'max:255'],
            'sftp_host' => ['required', 'string', 'max:255'],
            'sftp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'sftp_username' => ['required', 'string', 'max:255'],
            'sftp_password' => ['required', 'string', 'max:255'],
            'csv' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'domain.required' => 'Domain is required.',
            'sftp_host.required' => 'SFTP host is required.',
            'sftp_username.required' => 'SFTP username is required.',
            'sftp_password.required' => 'SFTP password is required.',
            'csv.required' => 'CSV file is required.',
            'csv.mimes' => 'File must be a CSV file.',
            'csv.max' => 'CSV file must not exceed 10MB.',
        ];
    }
}
