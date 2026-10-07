<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RfidTapRequest;
use Illuminate\Http\JsonResponse;
use App\Services\RfidTapService;

class RfidGateController extends Controller
{
    public function tap(
        RfidTapRequest $request,
        RfidTapService $rfidTapService
    ): JsonResponse
    {
        $hasil = $rfidTapService->process($request->validated());

        return response()->json([
            'message' => $hasil['message'],
            'data' => $hasil['data'],
        ], $hasil['status_code']);
    }
}
