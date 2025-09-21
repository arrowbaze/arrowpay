<?php

namespace Arrowpay\ArrowBaze\Events;

class PaymentNotified
{
    public array $data;
    public array $metadata;

    public function __construct(array $data, array $metadata = [])
    {
        $this->data = $data;
        $this->metadata = $metadata;
    }
}
