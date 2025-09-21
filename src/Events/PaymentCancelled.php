<?php

namespace Arrowpay\ArrowBaze\Events;

class PaymentCancelled
{
    public array $metadata;

    public function __construct(array $metadata = [])
    {
        $this->metadata = $metadata;
    }
}
