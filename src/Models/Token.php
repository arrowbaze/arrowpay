<?php


namespace Arrowpay\ArrowBaze\Models;


use Illuminate\Database\Eloquent\Model;


class Token extends Model
{
        protected $table = 'arrowbaze_tokens';
        protected $fillable = ['access_token','expiration_time','raw'];
        protected $casts = [
        'expiration_time' => 'datetime'
        ];
}