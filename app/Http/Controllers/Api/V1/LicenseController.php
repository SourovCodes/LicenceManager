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

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 422);
        }

        return response()->json([
            'success' => true,
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

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'expires_at' => $result['expires_at'],
            'days_remaining' => $result['days_remaining'],
        ]);
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

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
        ]);
    }

    /**
     * Get license status and details.
     */
    public function status(LicenseStatusRequest $request): JsonResponse
    {
        $result = $this->licenseService->status(
            licenseKey: $request->validated('license_key')
        );

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new LicenseStatusResource($result['license']),
        ]);
    }
}
