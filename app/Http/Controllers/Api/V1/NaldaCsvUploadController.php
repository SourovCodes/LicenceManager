<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreNaldaCsvUploadRequest;
use App\Http\Resources\Api\V1\NaldaCsvUploadRequestResource;
use App\Models\License;
use App\Models\NaldaCsvUploadRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NaldaCsvUploadController extends Controller
{
    /**
     * Store a new CSV upload request.
     */
    public function store(StoreNaldaCsvUploadRequest $request): JsonResponse
    {
        $license = $this->getLicenseFromHeader($request);

        $uploadRequest = NaldaCsvUploadRequest::create([
            'license_id' => $license->id,
            'domain' => $request->validated('domain'),
            'sftp_host' => $request->validated('sftp_host'),
            'sftp_port' => $request->validated('sftp_port', 22),
            'sftp_username' => $request->validated('sftp_username'),
            'sftp_password' => $request->validated('sftp_password'),
        ]);

        if ($request->hasFile('csv')) {
            $uploadRequest->addMediaFromRequest('csv')->toMediaCollection('csv');
        }

        return response()->json([
            'message' => 'CSV upload request created successfully.',
            'data' => new NaldaCsvUploadRequestResource($uploadRequest),
        ], 201);
    }

    /**
     * Get paginated list of CSV upload requests for the authenticated license.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $license = $this->getLicenseFromHeader($request);

        $uploadRequests = NaldaCsvUploadRequest::where('license_id', $license->id)
            ->latest()
            ->paginate($request->input('per_page', 15));

        return NaldaCsvUploadRequestResource::collection($uploadRequests);
    }

    /**
     * Get license from X-License-Key header.
     */
    private function getLicenseFromHeader(Request $request): License
    {
        $licenseKey = $request->header('X-License-Key');

        abort_if(! $licenseKey, 401, 'X-License-Key header is required.');

        $license = License::where('license_key', $licenseKey)->first();

        abort_if(! $license, 401, 'Invalid license key.');

        return $license;
    }
}
