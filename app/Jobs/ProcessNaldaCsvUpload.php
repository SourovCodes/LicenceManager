<?php

namespace App\Jobs;

use App\Enums\NaldaCsvUploadStatus;
use App\Models\NaldaCsvUploadRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use phpseclib3\Net\SFTP;
use Throwable;

class ProcessNaldaCsvUpload implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $uploadRequestId,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $uploadRequest = NaldaCsvUploadRequest::findOrFail($this->uploadRequestId);

        $uploadRequest->update(['status' => NaldaCsvUploadStatus::Processing]);

        $media = $uploadRequest->getFirstMedia('csv');

        if (! $media) {
            $uploadRequest->update([
                'status' => NaldaCsvUploadStatus::Failed,
                'error_message' => 'No CSV file found.',
            ]);

            return;
        }

        $sftp = new SFTP($uploadRequest->sftp_host, $uploadRequest->sftp_port);

        if (! $sftp->login($uploadRequest->sftp_username, $uploadRequest->sftp_password)) {
            $uploadRequest->update([
                'status' => NaldaCsvUploadStatus::Failed,
                'error_message' => 'SFTP authentication failed.',
            ]);

            return;
        }

        $localPath = $media->getPath();
        $remotePath = $media->file_name;

        $fileContents = file_get_contents($localPath);

        if ($fileContents === false) {
            $uploadRequest->update([
                'status' => NaldaCsvUploadStatus::Failed,
                'error_message' => 'Failed to read CSV file from storage.',
            ]);

            return;
        }

        if (! $sftp->put($remotePath, $fileContents)) {
            $uploadRequest->update([
                'status' => NaldaCsvUploadStatus::Failed,
                'error_message' => 'Failed to upload file to SFTP server.',
            ]);

            return;
        }

        $uploadRequest->update([
            'status' => NaldaCsvUploadStatus::Processed,
            'processed_at' => now(),
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        $uploadRequest = NaldaCsvUploadRequest::find($this->uploadRequestId);

        $uploadRequest?->update([
            'status' => NaldaCsvUploadStatus::Failed,
            'error_message' => $exception?->getMessage() ?? 'An unknown error occurred.',
        ]);
    }
}
