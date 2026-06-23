<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Thin client for the legacy DCM ERP's internal endpoints. Money-receipt
 * commits are delegated to DCM so the proven RechargeHelper / journal / radius
 * engines run exactly as they do for gateway payments.
 */
class DcmClient
{
    /**
     * Record a customer money receipt (and optionally recharge) via DCM.
     *
     * @param  array{customer_id:int,amount:float|int,ledger_id:int,user_id:int,recharge:bool,remarks?:string}  $payload
     * @return array{ok:bool,status:int,body:array}
     */
    public function moneyReceipt(array $payload): array
    {
        $base = rtrim((string) config('services.dcm.url'), '/');
        $secret = (string) config('services.dcm.secret');

        if ($base === '' || $secret === '') {
            return [
                'ok' => false,
                'status' => 0,
                'body' => ['status' => false, 'message' => 'DCM integration is not configured (DCM_INTERNAL_URL / INTERNAL_API_SECRET).'],
            ];
        }

        try {
            $response = Http::asJson()
                ->acceptJson()
                ->timeout((int) config('services.dcm.timeout', 30))
                ->withHeaders(['X-Internal-Secret' => $secret])
                ->post($base.'/api/internal/money-receipt', $payload);

            return [
                'ok' => $response->successful(),
                'status' => $response->status(),
                'body' => is_array($response->json()) ? $response->json() : [],
            ];
        } catch (Throwable $e) {
            Log::error('DcmClient moneyReceipt failed', [
                'customer_id' => $payload['customer_id'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'status' => 0,
                'body' => ['status' => false, 'message' => 'Could not reach the recharge service. Please try again.'],
            ];
        }
    }
}
