<?php

namespace library;

class JSSDK {

    private $appId;

    private $appSecret;

    public function __construct ($appId, $appSecret)
    {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
    }

    /**
     * 网页授权
     */
    public function connectAuth ($redirect_url, $scope = 'snsapi_userinfo')
    {
        session_start();
        $state = $_GET['state'];
        $code = $_GET['code'];
        if (!$code || !$state) {
            // 授权回调
            $_SESSION['state'] = md5(uniqid(rand(), TRUE));
            $authorize_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $this->appId . '&redirect_uri=' . rawurlencode($redirect_url) . '&response_type=code&scope=' . $scope . '&state=' . $_SESSION['state'] . '#wechat_redirect';
            header('Cache-Control: no-cache');
            header('Pragma: no-cache');
            header('Location: ' . $authorize_url, true, 301);
            exit();
        }
        // 检查state
        if ($state != $_SESSION['state']) {
            return success([]);
        }
        $_SESSION['state'] = null;
        unset($_SESSION['state']);
        // 用Code获取Openid
        $userToken = $this->getSnsapiBase($code);
        if ($userToken['errorcode'] !== 0) {
            return $userToken;
        }
        // 获取微信用户信息
        if ($scope == 'snsapi_base') {
            $userInfo = $this->getUserInfo(null, $userToken['data']['openid']);
            if ($userInfo['errorcode'] !== 0) {
                return $userInfo;
            }
        } else if ($scope == 'snsapi_userinfo') {
            $userInfo = $this->snsapi_userinfo($userToken['data']['access_token'], $userToken['data']['openid']);
            if ($userInfo['errorcode'] !== 0) {
                return $userInfo;
            }
        }
        return success(array_merge($userToken['data'], $userInfo ? $userInfo['data'] : []));
    }

    /**
     * 通过code换取网页授权access_token
     */
    public function getSnsapiBase ($code)
    {
        try {
            $reponse = $this->httpGet('https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $this->appId . '&secret=' . $this->appSecret . '&code=' . $code . '&grant_type=authorization_code');
        } catch (\Exception $e) {
            return error($e->getMessage());
        }
        if ($reponse['errcode']) {
            return error($reponse['errmsg']);
        }
        return success($reponse);
    }

    /**
     * snsapi_userinfo方式获取用户信息
     */
    public function snsapi_userinfo ($accessToken, $openid)
    {
        try {
            $reponse = $this->httpGet('https://api.weixin.qq.com/sns/userinfo?access_token=' . $accessToken . '&openid=' . $openid . '&lang=zh_CN');
        } catch (\Exception $e) {
            return error($e->getMessage());
        }
        if ($reponse['errcode']) {
            return error($reponse['errmsg']);
        }
        return success($reponse);
    }

    /**
     * 获取微信用户信息
     */
    public function getUserInfo ($accessToken, $openid)
    {
        // 获取用户基本信息(UnionID机制)，所以accessToken用接口凭证
        $_access_token = $this->getAccessToken();
        if ($_access_token['errorcode'] !== 0) {
            return $_access_token;
        }
        $accessToken = $_access_token['data']['access_token'];
        try {
            $reponse = $this->httpGet('https://api.weixin.qq.com/cgi-bin/user/info?access_token=' . $accessToken . '&openid=' . $openid . '&lang=zh_CN');
        } catch (\Exception $e) {
            return error($e->getMessage());
        }
        if ($reponse['errcode']) {
            return error($reponse['errmsg']);
        }
        return success($reponse);
    }

    public function getSignPackage ()
    {
        $_jsapiTicket = $this->getJsApiTicket();
        if ($_jsapiTicket['errorcode'] !== 0) {
            return $_jsapiTicket;
        }
        $jsapiTicket = $_jsapiTicket['data']['jsapi_ticket'];
        
        // 注意 URL 一定要动态获取，不能 hardcode.
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        
        $timestamp = time();
        $nonceStr = $this->createNonceStr();
        
        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
        
        $signature = sha1($string);
        
        $signPackage = array(
                "appId" => $this->appId, 
                "nonceStr" => $nonceStr, 
                "timestamp" => $timestamp, 
                "url" => $url, 
                "signature" => $signature, 
                "rawString" => $string
        );
        return success($signPackage);
    }

    public function getJsApiTicket ()
    {
        $data = \library\Cache::getInstance(['type' => 'file'])->get('jsapi_ticket' . $this->appId);

        if (!$data) {
            $_access_token = $this->getAccessToken();
            if ($_access_token['errorcode'] !== 0) {
                return $_access_token;
            }
            $accessToken = $_access_token['data']['access_token'];
            try {
                $reponse = $this->httpGet("https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken");
            } catch (\Exception $e) {
                return error($e->getMessage());
            }
            if ($reponse['errcode']) {
                return error($reponse['errmsg']);
            }
            \library\Cache::getInstance(['type' => 'file'])->set('jsapi_ticket' . $this->appId, $reponse['ticket'], $reponse['expires_in'] - 100);

            return success(array(
                    'jsapi_ticket' => $reponse['ticket']
            ));
        }

        return success(array(
                'jsapi_ticket' => $data
        ));
    }

    public function getAccessToken ()
    {
        $data = \library\Cache::getInstance(['type' => 'file'])->get('access_token' . $this->appId);

        if (!$data) {
            try {
                $reponse = $this->httpGet("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret");
            } catch (\Exception $e) {
                return error($e->getMessage());
            }
            if ($reponse['errcode']) {
                return error($reponse['errmsg']);
            }
            \library\Cache::getInstance(['type' => 'file'])->set('access_token' . $this->appId, $reponse['access_token'], $reponse['expires_in'] - 100);

            return success(array(
                'access_token' => $reponse['access_token']
            ));
        }

        return success(array(
                'access_token' => $data
        ));
    }

    private function createNonceStr ($length = 16)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    private function httpGet ($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        // 为保证第三方服务器与微信服务器之间数据传输的安全性，所有微信接口采用https方式调用，必须使用下面2行代码打开ssl安全校验。
        // 如果在部署过程中代码在此处验证失败，请到 http://curl.haxx.se/ca/cacert.pem 下载新的证书判别文件。
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        $reponse = curl_exec($curl);
        if (curl_errno($curl)) {
            throw new \Exception(curl_error($curl), 0);
        }
        curl_close($curl);
        if (!$_reponse = json_decode($reponse, true)) {
            throw new \Exception($reponse, 0);
        }
        return $_reponse;
    }

}