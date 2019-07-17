<?php
/**
 * 停车场洗车订单状态
 */

namespace app\common;

class ParkWashOrderStatus
{

    const CANCEL     = -1;
    const WAIT_PAY   = 0;
    const PAY        = 1;
    const IN_SERVICE = 3;
    const COMPLETE   = 4;
    const CONFIRM    = 5;

    static $message = [
        -1 => '已取消',
        0  => '未支付',
        1  => '已支付',
        3  => '服务中',
        4  => '已完成',
        5  => '确认完成'
    ];

    /**
     * 是否开始服务状态
     * @param $code
     * @return bool
     */
    public static function inService ($code)
    {
        return in_array($code, [
            self::IN_SERVICE, self::COMPLETE, self::CONFIRM
        ]);
    }

    public static function getMessage ($code)
    {
        return isset(self::$message[$code]) ? self::$message[$code] : '';
    }

}
