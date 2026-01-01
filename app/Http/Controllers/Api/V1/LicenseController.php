<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ActivateLicenseRequest;
use App\Http\Requests\Api\V1\DeactivateLicenseRequest;
use App\Http\Requests\Api\V1\LicenseStatusRequest;
use App\Http\Requests\Api\V1\ValidateLicenseRequest;
use App\Http\Resources\Api\V1\LicenseResource;
use App\Http\Resources\Api\V1\LicenseStatusResource;
use App\Services\LicenseService;
use Illuminate\Http\JsonResponse;

class LicenseController extends Controller
{
    public function __construct(
        private LicenseService $licenseService
    ) {}

    /**
     * Activate a license on a domain.
     */
    public function activate(ActivateLicenseRequest $request): JsonResponse
    {
        $result = $this->licenseService->activate(
            licenseKey: $request->validated('license_key'),
            domain: $request->validated('domain'),
            productSlug: $request->validated('product_slug'),
            ipAddress: $request->ip()
        );

        return response()->json([
            'message' => $result['message'],
            'data' => new LicenseResource($result['license']),
        ]);
    }

    /**
     * Validate a license for a domain.
     */
    public function validate(ValidateLicenseRequest $request): JsonResponse
    {
        $result = $this->licenseService->validate(
            licenseKey: $request->validated('license_key'),
            domain: $request->validated('domain'),
            productSlug: $request->validated('product_slug')
        );

        return response()->json($result);
    }

    /**
     * Deactivate a license from a domain.
     */
    public function deactivate(DeactivateLicenseRequest $request): JsonResponse
    {
        $result = $this->licenseService->deactivate(
            licenseKey: $request->validated('license_key'),
            domain: $request->validated('domain'),
            productSlug: $request->validated('product_slug'),
            reason: $request->validated('reason')
        );

        return response()->json($result);
    }

    /**
     * Get license status and details.
     */
    public function status(LicenseStatusRequest $request): JsonResponse
    {
        $license = $this->licenseService->status(
            licenseKey: $request->validated('license_key')
        );

        return response()->json([
            'data' => new LicenseStatusResource($license),
        ]);
    }
}
