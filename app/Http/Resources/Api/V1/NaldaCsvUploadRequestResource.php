<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\NaldaCsvUploadRequest
 */
class NaldaCsvUploadRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'domain' => $this->domain,
            'sftp_host' => $this->sftp_host,
            'sftp_port' => $this->sftp_port,
            'sftp_username' => $this->sftp_username,
            'status' => $this->status,
            'processed_at' => $this->processed_at?->toIso8601String(),
            'error_message' => $this->error_message,
            'csv_url' => $this->getFirstMedia('csv')?->getTemporaryUrl(now()->addMinutes(30)),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
