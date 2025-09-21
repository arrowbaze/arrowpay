<?php

namespace Arrowpay\ArrowBaze\Helpers;

use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Exception;

class License
{
    protected static ?string $licenseKey = null;

    /**
     * Set the license key manually (optional, defaults to config)
     */
    public static function setKey(string $key): void
    {
        self::$licenseKey = $key;
    }

    /**
     * Boot and validate license from config (runtime use)
     */
    public static function bootFromConfig(): void
    {
        // Set license key from config
        self::setKey(config('arrowbaze.license_key') ?? '');

        // Pull server and secret from jfrey.php
        $jfrey = require __DIR__ . '/jfrey.php';
        $serverUrl = rtrim($jfrey['license_server_url'] ?? '', '/');
        $secret = $jfrey['license_api_secret'] ?? '';

        $domain = request()?->getHost() ?? parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';

        if (!self::$licenseKey || !$serverUrl || !$secret) {
            throw new InvalidArgumentException("License key, server URL, or secret missing.");
        }

        self::sendValidationRequest(self::$licenseKey, $serverUrl, $secret, $domain);
    }

    /**
     * Runtime validation (throws exception if invalid)
     */
    public static function validate(): void
    {
        self::bootFromConfig();
    }

    /**
     * Composer pre-install/pre-update validation
     * Exits immediately if validation fails
     */
    public static function validateComposer(): void
    {
        $licenseKey = getenv('ARROWPAY_LICENSE_KEY') ?: '';
        $domain     = getenv('ARROWPAY_DOMAIN') ?: 'localhost';

        // Pull server and secret from jfrey.php
        $jfrey = require __DIR__ . '/jfrey.php';
        $serverUrl = rtrim($jfrey['license_server_url'] ?? '', '/');
        $secret = $jfrey['license_api_secret'] ?? '';

        if (!$licenseKey || !$serverUrl || !$secret) {
            fwrite(STDERR, "ArrowPay: License key missing.\n");
            exit(1);
        }

        try {
            self::sendValidationRequest($licenseKey, $serverUrl, $secret, $domain);
        } catch (Exception $e) {
            fwrite(STDERR, "ArrowPay: License validation failed. Installation aborted.\n");
            exit(1);
        }
    }

    /**
     * Shared internal method for sending validation request
     */
    protected static function sendValidationRequest(string $licenseKey, string $serverUrl, string $secret, string $domain): void
    {
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

            $data = $response->json();

            if (!($data['valid'] ?? false)) {
                throw new InvalidArgumentException('License validation failed. Please check your license key and domain');
            }
        } catch (Exception $e) {
            throw new InvalidArgumentException('A valid and unique purchase license key along with its domain name is required.');
        }
    }
}
