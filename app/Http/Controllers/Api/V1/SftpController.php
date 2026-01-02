<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\LicenseStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ValidateSftpRequest;
use App\Models\License;
use Illuminate\Http\JsonResponse;
use phpseclib3\Net\SFTP;

class SftpController extends Controller
{
    /**
     * Validate SFTP credentials with license key authentication.
     */
    public function validate(ValidateSftpRequest $request): JsonResponse
    {
        $licenseKey = $request->header('X-License-Key');

        if (! $licenseKey) {
            return response()->json([
                'message' => 'License key is required in X-License-Key header.',
            ], 401);
        }

        $license = License::query()
            ->where('license_key', $licenseKey)
            ->first();

        if (! $license) {
            return response()->json([
                'message' => 'Invalid license key.',
            ], 401);
        }

        if ($license->isExpired()) {
            return response()->json([
                'message' => 'License has expired.',
            ], 403);
        }

        if ($license->status !== LicenseStatus::Active) {
            return response()->json([
                'message' => 'License is not active.',
            ], 403);
        }

        $sftpHost = $request->validated('sftp_host');
        $sftpPort = $request->validated('sftp_port');
        $sftpUsername = $request->validated('sftp_username');
        $sftpPassword = $request->validated('sftp_password');

        try {
            $sftp = new SFTP($sftpHost, $sftpPort);

            if (! $sftp->login($sftpUsername, $sftpPassword)) {
                return response()->json([
                    'message' => 'SFTP authentication failed. Invalid username or password.',
                ], 422);
            }

            $sftp->disconnect();

            return response()->json([
                'message' => 'SFTP credentials are valid.',
                'data' => [
                    'host' => $sftpHost,
                    'port' => $sftpPort,
                    'username' => $sftpUsername,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Unable to connect to SFTP server.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
