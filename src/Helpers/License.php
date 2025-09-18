<?php

namespace Arrowpay\ArrowBaze\Helpers;

use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class License
{
    protected static ?string $licenseKey = null;

    /**
     * Set the license key
     */
    public static function setKey(string $key): void
    {
        self::$licenseKey = $key;
    }

    /**
     * Validate the license key for the current domain
     *
     * Throws exception if invalid, expired, or used by another partner/domain.
     */
    public static function validate(): void
    {
        if (!self::$licenseKey) {
            throw new InvalidArgumentException("License key is required.");
        }

        try {
            $response = Http::post('https://secure-app-link-handover-license-verify-hmd-ctrdd-treated.arrowbaze.tech/validate', [
                'license_key' => self::$licenseKey,
                'domain' => request()->getHost(), // enforce domain binding
            ]);

            $json = $response->json();

            if (!$response->successful() || !isset($json['valid']) || $json['valid'] !== true) {
                $message = $json['message'] ?? 'License key is invalid, expired, or already in use by another partner.';
                throw new InvalidArgumentException($message);
            }
        } catch (\Exception $e) {
            throw new InvalidArgumentException("License validation failed: " . $e->getMessage());
        }
    }

    /**
     * Automatically set and validate license from config
     */
    public static function bootFromConfig(): void
    {
        self::setKey(config('arrowbaze.license_key') ?? '');
        self::validate();
    }
}
