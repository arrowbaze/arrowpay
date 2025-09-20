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
            throw new InvalidArgumentException("License key is required.");
        }

        $serverUrl = rtrim(config('arrowbaze.license_server_url', ''), '/');
        $secret    = config('arrowbaze.license_api_secret', '');
        $ttl       = config('arrowbaze.license_cache_ttl', 3600);

        if (!$serverUrl || !$secret) {
            throw new InvalidArgumentException("License server or API secret missing.");
        }

        $domain = request()?->getHost() ?? parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';
        $cacheKey = self::CACHE_PREFIX . md5($licenseKey . $domain);

        // Return cached result if available
        if ($cached = Cache::get($cacheKey)) {
            if (!($cached['valid'] ?? false)) {
                throw new InvalidArgumentException($cached['message'] ?? 'License validation failed.');
            }
            return;
        }

        $payload = [
            'license_key' => $licenseKey,
            'domain'      => $domain,
        ];

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
            ->send('POST', $serverUrl . '/validate', ['body' => $jsonPayload]);

            if (!$response->successful()) {
                // Only log the status, never sensitive content
                Log::error("ArrowPay License server HTTP error: status {$response->status()}");
                throw new Exception("License server unreachable.");
            }

            $data = $response->json();

            if (!($data['valid'] ?? false)) {
                Cache::put($cacheKey, $data, $ttl);
                // Minimal info in logs
                Log::warning("ArrowPay License validation failed.");
                throw new InvalidArgumentException('License validation failed.');
            }

            Cache::put($cacheKey, $data, $ttl);

        } catch (Exception $e) {
            // Only log generic errors
            Log::error("ArrowPay License request failed.");
            throw new InvalidArgumentException("License server unreachable or failed.");
        }
    }
}
