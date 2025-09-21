<?php


namespace Arrowpay\ArrowBaze\Facades;


use Illuminate\Support\Facades\Facade;


class ArrowPay extends Facade
{
    protected static function getFacadeAccessor()
    {
    return 'arrowpay';
    }
}