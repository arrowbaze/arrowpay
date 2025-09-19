<?php

namespace Arrowpay\ArrowBaze\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Exception;

class License
{
    protected static ?string $licenseKey = null;

    // Cache key prefix to avoid repeated validation
    protected const CACHE_PREFIX = 'arrowbaze_license_';
    // Cache TTL (seconds)
    protected const CACHE_TTL = 3600; // 1 hour

    /**
     * Set the license key manually (optional, defaults to config)
     */
    public static function setKey(string $key): void
    {
        self::$licenseKey = $key;
    }

    /**
     * Boot and validate license from config
     */
    public static function bootFromConfig(): void
    {
        self::setKey(config('arrowbaze.license_key') ?? '');
        self::validate();
    }

    /**
     * Validate license automatically
     *
     * Throws InvalidArgumentException on failure
     */
    public static function validate(): void
    {
        $licenseKey = self::$licenseKey ?? config('arrowbaze.license_key');
        if (!$licenseKey) {
            throw new InvalidArgumentException("License key is required in config.");
        }

        $serverUrl = rtrim(config('arrowbaze.license_server_url', ''), '/');
        $secret    = config('arrowbaze.license_api_secret', '');

        if (!$serverUrl || !$secret) {
            throw new InvalidArgumentException("License server URL or API secret missing in package config.");
        }

        $domain = request()->getHost();
        $cacheKey = self::CACHE_PREFIX . md5($licenseKey . $domain);

        // Return cached result if available
        if ($cached = Cache::get($cacheKey)) {
            if (!($cached['valid'] ?? false)) {
                throw new InvalidArgumentException($cached['message'] ?? 'License validation failed (cached).');
            }
            return;
        }

        $payload = json_encode([
            'license_key' => $licenseKey,
            'domain'      => $domain,
        ]);

        $signature = hash_hmac('sha256', $payload, $secret);

        try {
            $response = Http::withHeaders([
                'X-SIGNATURE' => $signature,
                'Accept'      => 'application/json',
                'Content-Type'=> 'application/json',
            ])
            ->timeout(5)  // 5 seconds timeout
            ->retry(3, 1000) // 3 retries, 1s apart
            ->post($serverUrl . '/validate', $payload);

            if (!$response->successful()) {
                throw new Exception("HTTP error: {$response->status()}");
            }

            $data = $response->json();

            if (!($data['valid'] ?? false)) {
                Cache::put($cacheKey, $data, self::CACHE_TTL);
                throw new InvalidArgumentException($data['message'] ?? 'License validation failed.');
            }

            // Cache successful validation
            Cache::put($cacheKey, $data, self::CACHE_TTL);

        } catch (Exception $e) {
            Log::error("ArrowBaze License validation failed: " . $e->getMessage());
            throw new InvalidArgumentException("License server unreachable or failed.");
        }
    }
}
