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
            throw new InvalidArgumentException("License key is required in package config.");
        }

        $serverUrl = rtrim(config('arrowbaze.license_server_url', ''), '/');
        $secret    = config('arrowbaze.license_api_secret', '');
        $ttl       = config('arrowbaze.license_cache_ttl', 3600);

        if (!$serverUrl || !$secret) {
            throw new InvalidArgumentException("License server URL or API secret missing in package config.");
        }

        // Determine domain safely (works in CLI too)
        $domain = request()?->getHost() ?? parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';

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
            ->timeout(5)        // 5 seconds timeout
            ->retry(3, 1000)    // retry 3 times, 1s apart
            ->post($serverUrl . '/validate', $payload);

            if (!$response->successful()) {
                throw new Exception("HTTP error: {$response->status()}");
            }

            $data = $response->json();

            if (!($data['valid'] ?? false)) {
                Cache::put($cacheKey, $data, $ttl);
                throw new InvalidArgumentException($data['message'] ?? 'License validation failed.');
            }

            // Cache successful validation
            Cache::put($cacheKey, $data, $ttl);

        } catch (Exception $e) {
            Log::error("ArrowBaze License validation failed for domain '{$domain}'");
            throw new InvalidArgumentException("License server unreachable or failed. Please check your network or license server.");
        }
    }
}
