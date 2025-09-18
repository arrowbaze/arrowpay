<?php

namespace Arrowpay\ArrowBaze\Events;

class PaymentReturned
{
    public $orderId;
    public $metadata;

    public function __construct($orderId, array $metadata = [])
    {
        $this->orderId = $orderId;
        $this->metadata = $metadata;
    }
}
