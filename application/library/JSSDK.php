<?php

namespace app\library;

class JSSDK {

    private $appId;

    private $appSecret;

    private $accessTokenUrl;

    public function __construct ($appId, $appSecret, $accessTokenUrl = null)
    {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->accessTokenUrl = $accessTokenUrl;
    }

    /**
     * 微信小程序开放数据校验与解密
     * @param
     * {
     *     "code":"小程序登录凭证"
     *     "getPhoneNumber":{
     *         "encryptedData":"",
     *         "iv":""
     *     },
     *     "getUserInfo":{
     *         "rawData":"",
     *         "signature":"",
     *         "encryptedData":"",
     *         "iv":""
     *     }
     * }
     * @return
     * {
     *     "openid":"",
     *     "session_key":"",
     *     "unionid":"",
     *     "authcode":"",
     *     "type":"wx",
     *     "telephone":"",
     *     "nickname":"",
     *     "gender":"",
     *     "avatarurl":""
     * }
     */
    public function wXBizDataCrypt ($post)
    {
        // 获取session_key
        $result = $this->code2Session($post['code']);
        if ($result['errorcode'] !== 0) {
            return $result;
        }
        $result = $result['result'];

        // 生成authcode
        $result['type'] = 'wx';
        $result['authcode'] = (isset($result['unionid']) && $result['unionid']) ? $result['unionid'] : $result['openid'];

        // 小程序解密类
        $pc = new WXBizDataCrypt($this->appId, $result['session_key']);

        // 解密手机号
        if (isset($post['getPhoneNumber']) && $post['getPhoneNumber']['encryptedData'] && $post['getPhoneNumber']['iv']) {
            // aes 解密
            $errCode = $pc->decryptData($post['getPhoneNumber']['encryptedData'], $post['getPhoneNumber']['iv'], $data);
            if ($errCode !== 0) {
                return error('getPhoneNumber解密失败！[' . $errCode . ']');
            }
            // {"phoneNumber": "13580006666","purePhoneNumber": "13580006666","countryCode": "86","watermark": {"appid": "APPID","timestamp": TIMESTAMP}}
            $data = json_decode($data, true);
            $result['telephone'] = $data['purePhoneNumber']; // 手机号
        }

        // 解密用户信息
        if (isset($post['getUserInfo']) && $post['getUserInfo']['encryptedData'] && $post['getUserInfo']['iv']) {
            // 数据签名校验
            $signature = sha1($post['getUserInfo']['rawData'] . $result['session_key']);
            if ($signature != $post['getUserInfo']['signature']) {
                return error('getUserInfo签名验证失败！');
            }
            // aes 解密
            $errCode = $pc->decryptData($post['getUserInfo']['encryptedData'], $post['getUserInfo']['iv'], $data);
            if ($errCode !== 0) {
                return error('getUserInfo解密失败！[' . $errCode . ']');
            }
            // {"openId":"oGZUI0egBJY1zhBYw2KhdUfwVJJE","nickName":"Band","gender":1,"language":"zh_CN","city":"Guangzhou","province":"Guangdong","country":"CN","avatarUrl":"","unionId":"ocMvos6NjeKLIBqg5Mr9QjxrP1FA","watermark":{"timestamp":1477314187,"appid":"wx4f4bc4dec97d474b"}}
            foreach ($data as $k => $v) {
                $result[strtolower($k)] = $v; // 转成小写
            }
        }

        unset($data);
        return success($result);
    }

    /**
     * 小程序登录凭证校验
     * @param string $code 小程序 wx.login
     * @return array
     * openid 用户唯一标识
     * session_key 会话密钥
     * unionid 用户在开放平台的唯一标识符
     */
    public function code2Session ($code)
    {
        try {
            $reponse = https_request('https://api.weixin.qq.com/sns/jscode2session?appid=' . $this->appId . '&secret=' . $this->appSecret . '&js_code=' . $code . '&grant_type=authorization_code');
        } catch (\Exception $e) {
            return error($e->getMessage());
        }
        if ($reponse['errcode']) {
            return error($reponse['errmsg']);
        }

        return success($reponse);
    }

    /**
     * 网页授权
     */
    public function connectAuth ($redirect_url, $scope = 'snsapi_userinfo', $fetch_userinfo = true)
    {
        session_start();
        $state = isset($_GET['state']) ? $_GET['state'] : null;
        $code = isset($_GET['code']) ? $_GET['code'] : null;
        if (!$code || !$state) {
            // 授权回调
            $_SESSION['state'] = md5(uniqid(rand(), TRUE));
            $authorize_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?' . http_build_query([
                'appid' => $this->appId,
                'redirect_uri' => $redirect_url,
                'response_type' => 'code',
                'scope' => $scope,
                'state' => $_SESSION['state']
            ]) . '#wechat_redirect';
            header('Cache-Control: no-cache');
            header('Pragma: no-cache');
            header('Location: ' . $authorize_url, true, 301);
            exit(0);
        }

        // 检查state
        if ($state != $_SESSION['state']) {
            return error('微信授权效验失败');
        }

        $_SESSION['state'] = null;
        unset($_SESSION['state'], $_GET['state'], $_GET['code']);

        // 用Code获取Openid
        $userToken = $this->getSnsapiBase($code);
        if ($userToken['errorcode'] !== 0) {
            return $userToken;
        }
        $userToken = $userToken['result'];

        // 获取微信用户信息
        $userInfo = [];
        if ($fetch_userinfo === true) {
            if ($scope == 'snsapi_base') {
                $userInfo = $this->getUserInfo(null, $userToken['openid']);
                if ($userInfo['errorcode'] !== 0) {
                    return $userInfo;
                }
            } else if ($scope == 'snsapi_userinfo') {
                $userInfo = $this->snsapi_userinfo($userToken['access_token'], $userToken['openid']);
                if ($userInfo['errorcode'] !== 0) {
                    return $userInfo;
                }
            }
        }

        $userInfo = isset($userInfo['result']) ? $userInfo['result'] : [];
        $userInfo = array_merge($userToken, $userInfo);
        $userInfo['authcode'] = (isset($userInfo['unionid']) && $userInfo['unionid']) ? $userInfo['unionid'] : $userInfo['openid'];
        $userInfo['type'] = 'wx';
        return success($userInfo);
    }

    /**
     * 通过code换取网页授权access_token
     */
    public function getSnsapiBase ($code)
    {
        try {
            $reponse = https_request('https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $this->appId . '&secret=' . $this->appSecret . '&code=' . $code . '&grant_type=authorization_code');
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
            $reponse = https_request('https://api.weixin.qq.com/sns/userinfo?access_token=' . $accessToken . '&openid=' . $openid . '&lang=zh_CN');
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
        $accessToken = $_access_token['result']['access_token'];
        try {
            $reponse = https_request('https://api.weixin.qq.com/cgi-bin/user/info?access_token=' . $accessToken . '&openid=' . $openid . '&lang=zh_CN');
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
        $jsapiTicket = $_jsapiTicket['result']['jsapi_ticket'];

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
        $data = Cache::getInstance(['type' => 'file'])->get('jsapi_ticket' . $this->appId);

        if (!$data) {
            $_access_token = $this->getAccessToken();
            if ($_access_token['errorcode'] !== 0) {
                return $_access_token;
            }
            $accessToken = $_access_token['result']['access_token'];
            try {
                $reponse = https_request("https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken");
            } catch (\Exception $e) {
                return error($e->getMessage());
            }
            if ($reponse['errcode']) {
                return error($reponse['errmsg']);
            }
            Cache::getInstance(['type' => 'file'])->set('jsapi_ticket' . $this->appId, $reponse['ticket'], $reponse['expires_in'] - 100);

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
        if ($this->accessTokenUrl) {
            try {
                $reponse = https_request($this->accessTokenUrl, null, null, 4, null);
            } catch (\Exception $e) {
                return error($e->getMessage());
            }
            return success(array(
                'access_token' => $reponse
            ));
        }

        $data = Cache::getInstance(['type' => 'file'])->get('access_token' . $this->appId);

        if (!$data) {
            try {
                $reponse = https_request("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret");
            } catch (\Exception $e) {
                return error($e->getMessage());
            }
            if ($reponse['errcode']) {
                return error($reponse['errmsg']);
            }
            Cache::getInstance(['type' => 'file'])->set('access_token' . $this->appId, $reponse['access_token'], $reponse['expires_in'] - 100);

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

}
