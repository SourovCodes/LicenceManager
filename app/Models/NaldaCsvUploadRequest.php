<?php

namespace App\Models;

use App\Enums\NaldaCsvUploadStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class NaldaCsvUploadRequest extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\NaldaCsvUploadRequestFactory> */
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'license_id',
        'domain',
        'sftp_host',
        'sftp_port',
        'sftp_username',
        'sftp_password',
        'status',
        'processed_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'sftp_port' => 'integer',
            'sftp_password' => 'encrypted',
            'status' => NaldaCsvUploadStatus::class,
            'processed_at' => 'datetime',
        ];
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('csv')
            ->useDisk('csv')
            ->singleFile()
            ->acceptsMimeTypes(['text/csv', 'text/plain', 'application/csv']);
    }
}
