<?php

namespace Arrowpay\ArrowBaze;

use Illuminate\Support\Facades\Http;
use Arrowpay\ArrowBaze\Helpers\TokenHelper;

class ArrowBaze
{
    public function initializePayment(array $payload)
    {
        $orderId = $payload['order_id'] ?? 'ABZ_'.uniqid();

        $options = [
            'merchant_key' => config('arrowbaze.merchant_key'),
            'currency'     => config('arrowbaze.currency'),
            'order_id'     => $orderId,
            'amount'       => $payload['amount'],
            'return_url'   => url(str_replace('{orderId}', $orderId, config('arrowbaze.routes.return'))),
            'cancel_url'   => url(config('arrowbaze.routes.cancel')),
            'notif_url'    => url(config('arrowbaze.routes.notify')),
            'lang'         => $payload['lang'] ?? 'fr',
            'reference'    => $payload['reference'] ?? 'ARROWBAZE'
        ];

        $token = TokenHelper::getValidToken();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$token->access_token,
            'Content-Type'  => 'application/json'
        ])->post(config('arrowbaze.initialize_url'), $options);

        return $response->json();
    }

    public function checkStatus(string $orderId)
    {
        $token = TokenHelper::getValidToken();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$token->access_token,
        ])->get(config('arrowbaze.transaction_status_url'), [
            'order_id' => $orderId
        ]);

        return $response->json();
    }
}
