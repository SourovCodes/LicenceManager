<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ValidatesLicenseHeader;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ValidateSftpRequest;
use Illuminate\Http\JsonResponse;
use phpseclib3\Net\SFTP;

class SftpController extends Controller
{
    use ValidatesLicenseHeader;

    /**
     * Validate SFTP credentials with license key authentication.
     */
    public function validate(ValidateSftpRequest $request): JsonResponse
    {
        $this->getLicenseFromHeader($request);

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
