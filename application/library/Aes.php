<?php

namespace app\library;

class Aes {

    public static $key = '52r5hFeQcM4F99MbsTVlIJowBP3ktuAY5E111YADRhE=';
    public static $iv = '21hKK49gjevM7l9U3XOCLA==';

    /**
     * 加密
     * @param String input 加密的字符串
     * @param String key   解密的key
     * @return HexString
     */
    public static function encrypt($input = '', $key = '', $iv = '') {
        $key = $key ? $key : self::$key;
        $iv = $iv ? $iv : self::$iv;
        $data = openssl_encrypt($input, 'aes-256-cbc', base64_decode($key), OPENSSL_RAW_DATA, base64_decode($iv));
        $data = base64_encode($data);
        return $data;

    }

    /**
     * 解密
     * @param String input 解密的字符串
     * @param String key   解密的key
     * @return String
     */
    public static function decrypt($sStr, $key = '', $iv = '') {
        $key = $key ? $key : self::$key;
        $iv = $iv ? $iv : self::$iv;
        $data = openssl_decrypt(base64_decode($sStr), 'aes-256-cbc', base64_decode($key), OPENSSL_RAW_DATA, base64_decode($iv));
        return $data;
    }

    public static function kv () {
        return [
            'key' => base64_encode(openssl_random_pseudo_bytes(32)),
            'iv' => base64_encode(openssl_random_pseudo_bytes(16))
        ];
    }

}
