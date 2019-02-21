<?php

namespace controllers;

class User extends \ActionPDO {

    public function __init () {
        // echo $this->setSign(['clientapp'=>'ios','apiversion'=>1,'platform'=>2]);exit;
        // 校验sign
        $this->getRequestHeader();
        $auth_result = $this->checkSignPass($this->_G['header']);
        if($auth_result['errorcode'] !== 0) {
            json(null, $auth_result['message'], -1);
        }
    }

    /**
     * 第三方授权绑定
     */
    public function extend () {
        $userModel = new \models\UserModel();
        unset($_POST['nopw']);
        $ret = $userModel->loginBinding($_POST);
        if ($ret['errorcode'] !== 0) {
            return $ret;
        }
        $ret['data']['platform'] = $this->_G['header']['platform'];
        $ret['data']['timestamp'] = microtime_float();
        $ret['data']['sign'] = $this->setSign($ret['data']);
        return $ret;
    }

    /**
     * 发送短信验证码
     */
    public function sendSms () {
        $userModel = new \models\UserModel();
        return $userModel->sendSmsCode($_POST);
    }

    /**
     * 获取用户信息
     */
    public function info () {
        $userModel = new \models\UserModel();
        $ret = $userModel->getUserInfo($_POST['uid']);
        if ($ret['errorcode'] !== 0) {
            return $ret;
        }
        $ret['data']['platform'] = $this->_G['header']['platform'];
        $ret['data']['timestamp'] = microtime_float();
        $ret['data']['sign'] = $this->setSign($ret['data']);
        return $ret;
    }

    /**
     * 用户消费
     */
    public function consume () {
        $userModel = new \models\UserModel();
        return $userModel->consume($_POST);
    }

    /**
     * 用户充值
     */
    public function recharge () {
        $userModel = new \models\UserModel();
        return $userModel->recharge($_POST);
    }

    /**
     * 生成每次请求的sign
     */
    protected function setSign($data = []) {
        if (!isset($data['timestamp'])) {
            $data['timestamp'] = microtime_float();
        }

        $kv = $this->getKv();
        $kv = $kv[$data['platform']];

        // 1 按字段排序
        ksort($data);
        // 2拼接字符串数据  &
        $string = http_build_query($data);
        // 3通过aes来加密
        $string = \library\Aes::encrypt($string, $kv['aes_key'], $kv['aes_iv']);

        return $string;
    }

    /**
     * 检查sign是否正常
     */
    protected function checkSignPass($data) {
        // 参数校验
        if(!isset($data['sign'])) {
            return error('缺少参数sign');
        }

        if(!isset($data['clientapp'])) {
            return error('缺少参数clientapp');
        }

        if(!isset($data['apiversion'])) {
            return error('缺少参数apiversion');
        }

        if(!in_array($data['clientapp'], ['android', 'ios', 'web'])) {
            return error('参数clientapp不正确');
        }

        $kv = $this->getKv();
        if (!isset($kv[$data['platform']])) {
            return error('平台代码platform不正确');
        }
        $kv = $kv[$data['platform']];

        $str = \library\Aes::decrypt($data['sign'], $kv['aes_key'], $kv['aes_iv']);

        if(empty($str)) {
            return error('授权码sign授权失败');
        }

        parse_str($str, $arr);
        if(!is_array($arr)) {
            return error('授权码sign解析失败');
        }

        if($arr['clientapp'] != $data['clientapp'] || $arr['apiversion'] != $data['apiversion']) {
            return error('授权码sign格式不正确');
        }

        // debug模式
        if (getSysConfig('debug')) {
            return success('OK');
        }

        // 时间效验
        if (abs(TIMESTAMP - $arr['timestamp']) > getSysConfig('auth_expire_time')) {
            return error('授权码sign已过期');
        }

        // 唯一性判定
        if (!\library\DB::getInstance()->insert('__tablepre__hashcheck', [
            'hash' => md5_mini($data['sign']),
            'dateline' => TIMESTAMP
        ])) {
            return error('授权码sign已失效');
        }
        \library\DB::getInstance()->delete('__tablepre__hashcheck', 'dateline < ' . (TIMESTAMP - getSysConfig('auth_expire_time') * 2));

        return success('OK');
    }

    protected function getKv () {
        if (false === F('platform')) {
            $rs = \library\DB::getInstance()->table('__tablepre__platform')->field('pfcode,aes_key,aes_iv')->where('status = 1')->select();
            $rs = array_column($rs, null, 'pfcode');
            F('platform', $rs);
        }
        return F('platform');
    }

}