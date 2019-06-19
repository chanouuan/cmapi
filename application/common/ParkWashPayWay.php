<?php
/**
 * 停车场洗车支付方式
 */

namespace app\common;

class ParkWashPayWay
{

    const CBPAY     = 'cbpay';
    const WXPAYJS   = 'wxpayjs';
    const WXPAYH5   = 'wxpayh5';
    const WXPAYWASH = 'wxpaywash';
    const VIPPAY    = 'vippay';
    const FIRSTPAY  = 'firstpay';

    static $message = [
        'cbpay'     => '车币',
        'wxpayjs'   => '微信',
        'wxpayh5'   => '微信H5',
        'wxpaywash' => '微信',
        'vippay'    => '洗车VIP',
        'firstpay'  => '首单免费'
    ];

    public static function getMessage ($code)
    {
        return isset(self::$message[$code]) ? self::$message[$code] : $code;
    }

}
