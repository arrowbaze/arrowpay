<?php


namespace Arrowpay\ArrowBaze\Helpers;


use Arrowpay\ArrowBaze\Models\Token;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;


class TokenHelper
{
    public static function getValidToken()
    {
        $token = Token::orderBy('created_at','desc')->first();
        $now = Carbon::now();
        if (! $token || Carbon::parse($token->expiration_time)->lessThanOrEqualTo($now)) {
        return static::generateAccessToken();
        }
        return $token;
    }


    public static function generateAccessToken()
    {
        $response = Http::withBasicAuth(config('arrowbaze.client_id'), config('arrowbaze.client_secret'))
        ->asForm()
        ->post(config('arrowbaze.token_url'), ['grant_type' => 'client_credentials']);


        if ($response->successful()) {
        $data = $response->json();
        $expiresIn = $data['expires_in'] ?? 3600;
        $token = Token::create([
        'access_token' => $data['access_token'],
        'expiration_time' => Carbon::now()->addSeconds($expiresIn),
        'raw' => json_encode($data)
        ]);
        return $token;
        }


        return null;
    }
}