<?php


namespace Arrowpay\ArrowBaze\Facades;


use Illuminate\Support\Facades\Facade;


class ArrowBaze extends Facade
{
    protected static function getFacadeAccessor()
    {
    return 'arrowbaze';
    }
}