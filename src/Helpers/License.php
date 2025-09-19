<?php

namespace Arrowpay\ArrowBaze\Helpers;

use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class License
{
    protected static ?string $licenseKey = null;

    /**
     * Set the license key manually (optional, default from config)
     */
    public static function setKey(string $key): void
    {
        self::$licenseKey = $key;
    }

    /**
     * Validate license automatically
     */
    public static function validate(): void
    {
        $licenseKey = self::$licenseKey ?? config('arrowbaze.license_key');
        if (!$licenseKey) {
            throw new InvalidArgumentException("License key is required in config.");
        }

        $serverUrl = config('arrowbaze.license_server_url'); 
        $secret = config('arrowbaze.license_api_secret');

        if (!$serverUrl || !$secret) {
            throw new InvalidArgumentException("License server URL or API secret missing in package config.");
        }

        $domain = request()->getHost();
        $payload = json_encode([
            'license_key' => $licenseKey,
            'domain' => $domain,
        ]);

        $signature = hash_hmac('sha256', $payload, $secret);

        // Match your LicenseServer route: /license/validate
        $response = Http::withHeaders([
            'X-SIGNATURE' => $signature,
            'Accept' => 'application/json',
        ])->post($serverUrl . '/api/license/validate', $payload);

        if (!$response->successful()) {
            throw new InvalidArgumentException("License server unreachable or failed.");
        }

        $data = $response->json();

        if (!($data['valid'] ?? false)) {
            $message = $data['message'] ?? 'License validation failed.';
            throw new InvalidArgumentException($message);
        }

        // License is valid, package can continue
    }

    /**
     * Boot directly from package config
     */
    public static function bootFromConfig(): void
    {
        self::setKey(config('arrowbaze.license_key') ?? '');
        self::validate();
    }
}
