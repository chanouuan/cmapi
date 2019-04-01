<?php

namespace library;

class StatusCodes
{
    const STATUS_OK                          = 200;
    const STATUS_ERROR                       = 500;
    const STATUS_404                         = 404;
    const STATUS_PERMISSION_DENIED           = 403;
    const STATUS_UNAUTHORIZED                = 401;

    const TOKEN_VALIDATE_FAIL                = 3001;
    const TOKEN_UNAUTHORIZED                 = 3002;
    const SIG_EXPIRE                         = 3003;
    const SIG_ERROR                          = 3004;
    const REQUEST_METHOD_ERROR               = 3005;

    const USER_PARAMETER_ERROR               = 5001;

    const COUPON_CREATE_PARAMETER_ERROR      = 6001;
    const COUPON_NOT_EXIST                   = 6002;
    const COUPON_UPDATE_PARAMETER_ERROR      = 6003;
    const COUPON_DELETE_ERROR                = 6004;
    const COMPANY_CREATE_PARAMETER_ERROR     = 6005;
    const COMPANY_DELETE_ERROR               = 6006;
    const ORDER_NOT_EXIST                    = 6007;
    const CONFIG_UPDATE_PARAMETER_ERROR      = 6008;
    const CONFIG_FIND_PARAMETER_ERROR        = 6009;

    const USER_NOT_LOGIN_ERROR               = 3010;

    const PUBLISH_PLCACE_ERROR               = 7001;
    const TIME_PUBLISH_ERROR                 = 7002;


    static $message = array(
        200  => '成功',
        500  => '未知错误',
        3001 => 'token验证失败',
        3002 => '请求未授权',
        3003 => '签名过期',
        3004 => '签名错误',
        3005 => '请求方法错误',
        3010 => '用户未登录',
        5001 => '用户参数错误',
        7001 => '发布车位出错',
        7002 => '共享时段设置不正确',
    );

    public static function getMessage(int $code) {
        return isset(self::$message[$code]) ? self::$message[$code] : '';
    }
}
