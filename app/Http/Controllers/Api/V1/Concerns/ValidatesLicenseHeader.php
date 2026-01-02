<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use App\Enums\LicenseStatus;
use App\Models\License;
use Illuminate\Http\Request;

trait ValidatesLicenseHeader
{
    /**
     * Get and validate license from X-License-Key header.
     */
    private function getLicenseFromHeader(Request $request): License
    {
        $licenseKey = $request->header('X-License-Key');

        abort_if(! $licenseKey, 401, 'X-License-Key header is required.');

        $license = License::where('license_key', $licenseKey)->first();

        abort_if(! $license, 401, 'Invalid license key.');
        abort_if($license->isExpired(), 403, 'License has expired.');
        abort_if($license->status !== LicenseStatus::Active, 403, 'License is not active.');

        return $license;
    }
}
