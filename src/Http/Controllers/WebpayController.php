<?php


namespace Arrowpay\ArrowBaze\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Arrowpay\ArrowBaze\Helpers\TokenHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;


class WebpayController extends Controller
{
    const DEFAULT_INIT = 'initialize_url';
    const DEFAULT_STATUS = 'transaction_status_url';


    public function initialize(Request $request)
    {
        $payload = $request->validate([
            'orange_payment_id' => 'required|integer',
            'amount' => 'required|numeric',
        ]);


        $orderId = 'ABZ_'.$payload['orange_payment_id'].'_'.mt_rand(99,998).mt_rand(999,9999);


        $option = [
        'merchant_key' => config('arrowbaze.merchant_key'),
        'currency' => config('arrowbaze.currency'),
        'order_id' => $orderId,
        'amount' => $payload['amount'],
        'return_url' => url(str_replace('{orderId}',''.$orderId, config('arrowbaze.routes.return'))),
        'cancel_url' => url(config('arrowbaze.routes.cancel')),
        'notif_url' => url(config('arrowbaze.routes.notify')),
        'lang' => $request->get('lang','fr'),
        'reference' => $request->get('reference','ARROWBAZE')
        ];


        $token = TokenHelper::getValidToken();


        $response = Http::withHeaders([
        'Authorization' => 'Bearer '. $token->access_token,
        'Content-Type' => 'application/json'
        ])->post(config('arrowbaze.'.self::DEFAULT_INIT), $option);


        if ($response->successful()) {
        $data = $response->json();
        return redirect($data['payment_url']);
        }


        TokenHelper::generateAccessToken();
    }
}