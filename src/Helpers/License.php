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

        $domain = request()?->getHost() ?? parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';

        Log::debug("Domain found '{$domain}'");

        $cacheKey = self::CACHE_PREFIX . md5($licenseKey . $domain);

        // Return cached result if available
        if ($cached = Cache::get($cacheKey)) {
            if (!($cached['valid'] ?? false)) {
                throw new InvalidArgumentException($cached['message'] ?? 'License validation failed (cached).');
            }
            return;
        }

        $payload = [
            'license_key' => $licenseKey,
            'domain'      => $domain,
        ];

        // Compute HMAC on **JSON-encoded payload**
        $jsonPayload = json_encode($payload);
        $signature = hash_hmac('sha256', $jsonPayload, $secret);

        try {
            $response = Http::withHeaders([
                'X-SIGNATURE' => $signature,
                'Accept'      => 'application/json',
                'Content-Type'=> 'application/json',
            ])
            ->timeout(5)
            ->retry(3, 1000)
            // **Send raw JSON body to avoid extra quotes**
            ->send('POST', $serverUrl . '/validate', ['body' => $jsonPayload]);

            if (!$response->successful()) {
                Log::error("ArrowBaze License server HTTP error", [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new Exception("HTTP error: {$response->status()}");
            }

            $data = $response->json();

            if (!($data['valid'] ?? false)) {
                Cache::put($cacheKey, $data, $ttl);
                Log::warning("ArrowBaze License validation failed", [
                    'domain' => $domain,
                    'response' => $data
                ]);
                throw new InvalidArgumentException($data['message'] ?? 'License validation failed.');
            }

            Cache::put($cacheKey, $data, $ttl);

        } catch (Exception $e) {
            Log::error("ArrowBaze License request exception for domain '{$domain}': " . $e->getMessage());
            if (isset($response)) {
                Log::error("Response body: " . $response->body());
            }
            throw new InvalidArgumentException("License server unreachable or failed. Please check your network or license server.");
        }
    }
}
