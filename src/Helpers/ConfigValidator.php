<?php

namespace Arrowpay\ArrowBaze\Helpers;

use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class ConfigValidator
{
    /**
     * Validate required configs
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public static function validate(): void
    {
        $required = [
            'merchant_key',
            'client_id',
            'client_secret',
            'initialize_url',
            'transaction_status_url',
            'currency',                 // default currency
            'routes.return',
            'routes.cancel',
            'routes.notify',
        ];

        foreach ($required as $key) {
            $value = null;

            // Support nested keys like routes.return
            if (str_contains($key, '.')) {
                [$parent, $child] = explode('.', $key);
                $value = config("arrowbaze.{$parent}")[$child] ?? null;
            } else {
                $value = config("arrowbaze.$key");
            }

            if (!$value) {
                Log::error("ArrowPay config missing: {$key}");
                throw new InvalidArgumentException("ArrowPay config missing: {$key}");
            }
        }
    }
}
