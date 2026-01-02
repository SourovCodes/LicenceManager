<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ValidateSftpRequest extends FormRequest
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
            'sftp_host' => ['required', 'string', 'max:255'],
            'sftp_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'sftp_username' => ['required', 'string', 'max:255'],
            'sftp_password' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sftp_host.required' => 'SFTP host is required.',
            'sftp_host.max' => 'SFTP host must not exceed 255 characters.',
            'sftp_port.required' => 'SFTP port is required.',
            'sftp_port.integer' => 'SFTP port must be a valid integer.',
            'sftp_port.min' => 'SFTP port must be at least 1.',
            'sftp_port.max' => 'SFTP port must not exceed 65535.',
            'sftp_username.required' => 'SFTP username is required.',
            'sftp_username.max' => 'SFTP username must not exceed 255 characters.',
            'sftp_password.required' => 'SFTP password is required.',
            'sftp_password.max' => 'SFTP password must not exceed 255 characters.',
        ];
    }
}
