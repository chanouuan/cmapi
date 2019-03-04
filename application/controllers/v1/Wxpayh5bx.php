<?php
/**
 * 微信H5支付
 */

namespace controllers;

class Wxpayh5bx extends Wxpayh5 {

    public function __init () {
        import_vendor('WxPayJs/WxPayPubHelper');
        $wxConfig = getSysConfig('xiche', 'wx');
        define('APPID', $wxConfig['appid']);
        define('APPSECRET', $wxConfig['appsecret']);
        define('MCHID', $wxConfig['pay_mchid']);
        define('KEY', $wxConfig['pay_key']);
        define('SSLCERT_PATH', $wxConfig['sslcert_path']);
        define('SSLKEY_PATH', $wxConfig['sslkey_path']);
        define('NOTIFY_URL', APPLICATION_URL . '/wxpayh5bx/notify');
    }

}
