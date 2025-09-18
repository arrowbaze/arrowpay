<?php

namespace Arrowpay\ArrowBaze\Helpers;

use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class PayloadValidator
{
    /**
     * Validate required keys in payload
     *
     * @param array $payload
     * @param array $requiredKeys
     * @return void
     * @throws InvalidArgumentException
     */
    public static function validate(array $payload, array $requiredKeys): void
    {
        foreach ($requiredKeys as $key) {
            if (!isset($payload[$key]) || empty($payload[$key])) {
                Log::error("ArrowBaze payload missing: {$key}", $payload);
                throw new InvalidArgumentException("Missing required payload key: {$key}");
            }
        }
    }
}
