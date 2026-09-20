<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Services\AppleIapService;
use App\Services\AppleJwsVerifier;
use App\Support\AppleIapProducts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppleBillingController extends Controller
{
    public function products(): JsonResponse
    {
        return response()->json([
            'data' => AppleIapProducts::apiCatalog(),
            'bundle_id' => AppleIapProducts::BUNDLE_ID,
        ]);
    }

    public function verify(Request $request, AppleIapService $iap): JsonResponse
    {
        $data = $request->validate([
            'signed_transaction' => ['required', 'string'],
        ]);

        $result = $iap->verifyAndActivate($request->user(), $data['signed_transaction']);
        $user = $request->user()->fresh()->loadMissing(['roles', 'company']);

        return response()->json([
            'message' => 'Langganan berhasil diaktifkan.',
            'transaction_id' => $result['transaction']->transaction_id,
            'plan' => $result['transaction']->plan_key,
            'billing' => $result['transaction']->billing,
            'expires_at' => $result['transaction']->expires_at?->toIso8601String(),
            'user' => new UserResource($user),
        ]);
    }

    public function restore(Request $request, AppleIapService $iap): JsonResponse
    {
        $data = $request->validate([
            'signed_transactions' => ['required', 'array', 'min:1'],
            'signed_transactions.*' => ['required', 'string'],
        ]);

        $result = $iap->restore($request->user(), $data['signed_transactions']);
        $user = $request->user()->fresh()->loadMissing(['roles', 'company']);

        if ($result === null) {
            return response()->json([
                'message' => 'Tidak ada langganan Apple yang dapat dipulihkan untuk perusahaan ini.',
                'user' => new UserResource($user),
            ], 422);
        }

        return response()->json([
            'message' => 'Langganan berhasil dipulihkan.',
            'transaction_id' => $result['transaction']->transaction_id,
            'plan' => $result['transaction']->plan_key,
            'billing' => $result['transaction']->billing,
            'expires_at' => $result['transaction']->expires_at?->toIso8601String(),
            'user' => new UserResource($user),
        ]);
    }

    public function notifications(Request $request, AppleJwsVerifier $jws, AppleIapService $iap): JsonResponse
    {
        $signedPayload = (string) $request->input('signedPayload', '');
        if ($signedPayload === '') {
            return response()->json(['message' => 'signedPayload required'], 400);
        }

        try {
            $notification = $jws->decodeAndVerify($signedPayload);
            $data = $notification['data'] ?? [];
            $signedTransaction = is_array($data) ? (string) ($data['signedTransactionInfo'] ?? '') : '';

            if ($signedTransaction !== '') {
                $txPayload = $jws->decodeAndVerify($signedTransaction);
                $iap->applyServerPayload($txPayload, $signedTransaction);
            }
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Unable to process notification'], 400);
        }

        return response()->json(['ok' => true]);
    }
}
