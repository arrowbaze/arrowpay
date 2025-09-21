<?php

namespace Arrowpay\ArrowBaze;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Arrowpay\ArrowBaze\Helpers\ConfigValidator;
use Arrowpay\ArrowBaze\Helpers\TokenHelper;
use Arrowpay\ArrowBaze\Helpers\PayloadValidator;
use Arrowpay\ArrowBaze\Helpers\License;
use Arrowpay\ArrowBaze\Events\PaymentReturned;
use Arrowpay\ArrowBaze\Events\PaymentCancelled;
use Arrowpay\ArrowBaze\Events\PaymentNotified;
use InvalidArgumentException;

class ArrowPay
{
    /**
     * Initialize a payment request
     *
     * @param array $payload
     * @return array
     */

     public function __construct()
    {
        License::validate();
    }

    public function initializePayment(array $payload): array
    {
        // Validate critical configs
        ConfigValidator::validate();

        // Validate required payload keys
        PayloadValidator::validate($payload, ['amount']);

        $orderId = $payload['order_id'] ?? 'ABZ_' . uniqid();

        $options = [
            'merchant_key' => config('arrowbaze.merchant_key'),
            'currency'     => $payload['currency'] ?? config('arrowbaze.currency'),
            'order_id'     => $orderId,
            'amount'       => $payload['amount'],
            'return_url'   => $payload['return_url'] ?? url(str_replace('{orderId}', $orderId, config('arrowbaze.routes.return'))),
            'cancel_url'   => $payload['cancel_url'] ?? url(config('arrowbaze.routes.cancel')),
            'notif_url'    => $payload['notif_url'] ?? url(config('arrowbaze.routes.notify')),
            'lang'         => $payload['lang'] ?? 'fr',
            'reference'    => $payload['reference'] ?? 'Partenaire',
        ];

        try {
            $token = TokenHelper::getValidToken();

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token->access_token,
                'Content-Type'  => 'application/json',
            ])->post(config('arrowbaze.initialize_url'), $options);

            $response->throw();

            return $response->json();
        } catch (\Exception $e) {
            Log::error('ArrowBaze initialize payment failed', [
                'payload'  => $options,
                'message'  => $e->getMessage(),
                'response' => $e->response?->body(),
            ]);

            return [
                'status' => 'error',
                'message' => 'Payment initialization failed',
            ];
        }
    }

    /**
     * Check transaction status by order ID
     *
     * @param string $orderId
     * @return array
     */
    public function checkStatus(string $orderId): array
    {
        ConfigValidator::validate();

        try {
            $token = TokenHelper::getValidToken();

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token->access_token,
            ])->get(config('arrowbaze.transaction_status_url'), [
                'order_id' => $orderId,
            ]);

            $response->throw();

            return $response->json();
        } catch (\Exception $e) {
            Log::error('ArrowBaze checkStatus failed', [
                'order_id' => $orderId,
                'message'  => $e->getMessage(),
                'response' => $e->response?->body(),
            ]);

            return [
                'status' => 'error',
                'message' => 'Unable to retrieve transaction status',
            ];
        }
    }

    /**
     * Handle return callback
     *
     * @param string $orderId
     * @param array|null $responseOverride
     * @return \Illuminate\Http\JsonResponse
     */
    public function paymentReturn(string $orderId, ?array $responseOverride = null)
    {
        event(new PaymentReturned($orderId, [
            'timestamp' => now(),
            'request_ip' => request()->ip(),
        ]));

        return response()->json($responseOverride ?? [
            'status'   => 'return_received',
            'order_id' => $orderId,
        ]);
    }

    /**
     * Handle cancel callback
     *
     * @param array|null $responseOverride
     * @return \Illuminate\Http\JsonResponse
     */
    public function paymentCancel(?array $responseOverride = null)
    {
        event(new PaymentCancelled([
            'timestamp' => now(),
            'request_ip' => request()->ip(),
        ]));

        return response()->json($responseOverride ?? [
            'status' => 'cancelled',
        ]);
    }

    /**
     * Handle payment notification callback
     *
     * @param Request $request
     * @param array|null $responseOverride
     * @return \Illuminate\Http\JsonResponse
     */
    public function notify(Request $request, ?array $responseOverride = null)
    {
        $data = $request->all();

        event(new PaymentNotified($data + [
            'timestamp' => now(),
            'request_ip' => $request->ip(),
        ]));

        return response()->json($responseOverride ?? [
            'status' => 'ok',
        ]);
    }
}
