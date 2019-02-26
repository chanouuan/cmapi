<?php
/**
 * 微信JS支付
 */

namespace controllers;

use models\TradeModel;

class Wxpayjs extends \ActionPDO {

    public function __init ()
    {
        import_vendor('WxPayJs/WxPayPubHelper');
        $wxConfig = getSysConfig('xiche', 'wx');
        define('APPID', $wxConfig['appid']);
        define('APPSECRET', $wxConfig['appsecret']);
        define('MCHID', $wxConfig['pay_mchid']);
        define('KEY', $wxConfig['pay_key']);
        define('SSLCERT_PATH', $wxConfig['sslcert_path']);
        define('SSLKEY_PATH', $wxConfig['sslkey_path']);
        define('NOTIFY_URL', $wxConfig['notify_url']);
    }

    /** 
     * 获取支付参数
     */
    public function api ()
    {
        if (CLIENT_TYPE != 'wx') {
            $this->error('当前支付环境不支持在线支付');
        }

        // 交易单id
        $tradeid = intval(getgpc('tradeid'));

        $model = new TradeModel();

        if (!$tradeInfo = $model->get($tradeid, 'status = 0', 'type,trade_id,pay,ordercode,uses')) {
            $this->error('交易单不存在');
        }
        if ($tradeInfo['pay'] <= 0) {
            $this->error('交易金额错误');
        }

        // 更新交易单支付参数
        $model->savePayParam($tradeid, [
            'payway' => strtolower($this->_module),
            'mchid' => MCHID
        ]);

        // 获取openid
        if ($tradeInfo['type'] == 'xc') {
            $openid = (new \models\XicheModel())->getWxOpenid($tradeInfo['trade_id']);
        } else if ($tradeInfo['type'] == 'bx') {
            $openid = (new \models\BaoxianModel())->getWxOpenid($tradeInfo['trade_id']);
        }

        // 使用统一支付接口，获取prepay_id
        $unifiedOrder = new \UnifiedOrder_pub();
        $unifiedOrder->setParameter('openid', $openid); // openid
        $unifiedOrder->setParameter('body', $tradeInfo['uses']); // 商品描述
        $unifiedOrder->setParameter('out_trade_no', $tradeInfo['ordercode']); // 商户订单号
        $unifiedOrder->setParameter('total_fee', $tradeInfo['pay']); // 总金额 单位分 不能有小数点
        $unifiedOrder->setParameter('notify_url', NOTIFY_URL); // 通知地址
        $unifiedOrder->setParameter('trade_type', 'JSAPI'); // 交易类型
        $prepay_id = $unifiedOrder->getPrepayId();
        // 校验接口返回
        if ($unifiedOrder->result['return_code'] == 'FAIL') {
            $this->error($unifiedOrder->result['return_msg']);
        } else if ($unifiedOrder->result['result_code'] == 'FAIL') {
            $this->error($unifiedOrder->result['err_code'] . ':' . $unifiedOrder->result['err_code_des']);
        } else if ($unifiedOrder->result['result_code'] == 'SUCCESS') {
            $jsApi = new \JsApi_pub();
            $jsApi->setPrepayId($prepay_id);
            $jsApiParameters = json_decode($jsApi->getParameters(), true);
            // jssdk中的timestamp为小写
            $jsApiParameters['timestamp'] = $jsApiParameters['timeStamp'];
            unset($jsApiParameters['timeStamp']);
            $this->success($jsApiParameters);
        } else {
            $this->error('API ERROR');
        }
    }

    /**
     * 异步通知url
     */
    public function notify ()
    {
        // 使用通用通知接口
        $notify = new \Notify_pub();

        // 存储微信的回调
        $xml = file_get_contents('php://input');
        $notify->saveData($xml);

        // 验证签名，并回应微信。
        // 对后台通知交互时，如果微信收到商户的应答不是成功或超时，微信认为通知失败，
        // 微信会通过一定的策略（如30分钟共8次）定期重新发起通知，
        // 尽可能提高通知的成功率，但微信不保证通知最终能成功。
        if ($notify->checkSign() == FALSE) {
            $notify->setReturnParameter('return_code', 'FAIL'); // 返回状态码
            $notify->setReturnParameter('return_msg', '签名失败'); // 返回信息
        } else {
            $notify->setReturnParameter('return_code', 'SUCCESS'); // 设置返回码
        }
        $returnXml = $notify->returnXml();

        // ==商户根据实际情况设置相应的处理流程，此处仅作举例=======

        $success = false;
        $error = [];
        if ($notify->checkSign() == TRUE) {
            if ($notify->data['return_code'] == 'SUCCESS' && $notify->data['result_code'] == 'SUCCESS') {
                // 支付成功
                $model = new TradeModel();
                $result = $model->paySuccess($this->_module, $notify->data['out_trade_no'], $notify->data['transaction_id'], MCHID, $notify->data['trade_type']);
                if ($result['errorcode'] === 0) {
                    $success = true;
                } else {
                    $error[] = $result['message'];
                    $error[] = $xml;
                }
            } else {
                $error[] = '支付接口业务出错';
                $error[] = $xml;
            }
        } else {
            $error[] = '支付接口验证签名失败';
            $error[] = $xml;
        }
        if ($success) {
            echo $returnXml;
        } else {
            \library\DebugLog::_log($error, 'payerror');
        }
        return null;
    }

    /**
     * 订单查询
     */
    public function query ()
    {
        $tradeid = intval(getgpc('tradeid'));
        $model = new TradeModel();
        if (!$tradeInfo = $model->get($tradeid, 'payway = "' . strtolower($this->_module) . '"', 'ordercode')) {
            $this->error('交易单不存在');
        }
        // 使用订单查询接口
        $orderQuery = new \OrderQuery_pub();
        // 设置必填参数
        $orderQuery->setParameter('out_trade_no', $tradeInfo['ordercode']);
        // 获取订单查询结果
        if (!$orderQueryResult = $orderQuery->getResult()) {
            $this->error('查询失败');
        }
        if ($orderQueryResult['return_code'] == 'FAIL') {
            $this->error('通信出错：' . $orderQueryResult['return_msg']);
        } else if ($orderQueryResult['result_code'] == 'FAIL') {
            $this->error('错误描述：' . $orderQueryResult['err_code_des']);
        } else if ($orderQueryResult['result_code'] == 'SUCCESS') {
            $result = array();
            $result['mchid'] = $orderQueryResult['mch_id'];
            $result['out_trade_no'] = $orderQueryResult['out_trade_no'];
            $result['trade_no'] = $orderQueryResult['transaction_id'];
            $result['trade_type'] = $orderQueryResult['trade_type'];
            $result['trade_status'] = $orderQueryResult['trade_state'];
            $result['total_fee'] = $orderQueryResult['total_fee'];
            $result['time'] = strtotime($orderQueryResult['time_end']);
            // 判断支付成功
            if ($result['trade_status'] === 'SUCCESS') {
                $result['pay_success'] = 'SUCCESS';
            }
            $this->success($result);
        } else {
            $this->error('参数错误');
        }
    }

}
