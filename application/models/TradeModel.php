<?php

namespace app\models;

use Crud;

class TradeModel extends Crud {

    /**
     * 更新交易单支付参数
     */
    public function savePayParam ($id, $param)
    {
        return $this->getDb()->update('__tablepre__payments', $param, 'id = ' . $id);
    }

    /**
     * 获取单个交易单
     */
    public function get ($id, $where = null, $field = '*')
    {
        if (isset($id)) {
            if (is_array($where)) {
                $where['id'] = $id;
            } else {
                $where = $where ? ($where . ' and id = ' . $id) : (' id = ' . $id);
            }
        }
        if (!$where) {
            return null;
        }
        return $this->getDb()->table('__tablepre__payments')->field($field)->where($where)->limit(1)->find();
    }

    /**
     * 获取多个交易单
     */
    public function select ($where, $field = '*', $limit = null, $order = null)
    {
        return $this->getDb()->table('__tablepre__payments')->field($field)->where($where)->limit($limit)->order($order)->select();
    }

    /**
     * 更新状态退款中
     */
    public function update ($param, $condition)
    {
        return $this->getDb()->update('__tablepre__payments', $param, $condition);
    }

    /**
     * 支付确认
     */
    public function payQuery ($uid, $tradeid)
    {
        if (!$tradeInfo = $this->get(null, [
            'id' => $tradeid, 'trade_id' => $uid
        ], 'id,pay,payway,mchid,ordercode,status')) {
            return error('交易单不存在或无效');
        }

        if ($tradeInfo['status'] == 1) {
            return success('支付成功');
        } elseif ($tradeInfo['status'] != 0) {
            return error('不是待支付订单');
        }

        if (!$tradeInfo['payway']) {
            return error('还未支付');
        }
        if ($tradeInfo['payway'] == 'cbpay') {
            // 车币支付，直接支付成功
            return $this->paySuccess($tradeInfo['payway'], $tradeInfo['ordercode']);
        }

        // 查询订单
        $className = 'app\\controllers\\' . ucfirst($tradeInfo['payway']);
        $referer = new $className();
        $referer->_module = $tradeInfo['payway'];
        $referer->__init();
        $result = call_user_func([$referer, 'query']);

        if ($result['errorcode'] !== 0) {
            return error($result['message']);
        }
        $result = $result['result'];
        if ($result['pay_success'] !== 'SUCCESS') {
            return error($result['trade_status']);
        }
        if ($result['mchid'] != $tradeInfo['mchid'] || $result['total_fee'] != $tradeInfo['pay']) {
            return error('支付验证失败');
        }

        // 支付成功
        return $this->paySuccess($tradeInfo['payway'], $result['out_trade_no'], $result['trade_no'], $result['mchid'], $result['trade_type'], $result['trade_status']);
    }

    /**
     * 支付成功回调
     * @param string $payway 支付方式
     * @param string $out_trade_no 商户订单号
     * @param string $trade_no 第三方支付订单号
     * @param string $mchid 商户ID
     * @param string $trade_type 支付类型
     * @param string $trade_status 支付状态
     * @return bool
     */
    public function paySuccess ($payway, $out_trade_no, $trade_no = '', $mchid = '', $trade_type = '', $trade_status = '')
    {
        if (!$tradeInfo = $this->get(null, [
            'ordercode' => $out_trade_no, 'status' => 0
        ], 'id,type')) {
            return error($out_trade_no . '未找到');
        }

        $tradeParam = [
            'payway' => strtolower($payway),
            'status' => 1,
            'trade_no' => $trade_no,
            'paytime' => date('Y-m-d H:i:s', TIMESTAMP),
            'mchid' => $mchid,
            'trade_type' => $trade_type,
            'trade_status' => $trade_status
        ];

        $model = null;
        if ($tradeInfo['type'] == 'xc') {
            // 洗车机支付成功
            $model = new XicheModel();
        } elseif ($tradeInfo['type'] == 'bx') {
            // 保险支付成功
            $model = new BaoxianModel();
        } elseif ($tradeInfo['type'] == 'parkwash' || $tradeInfo['type'] == 'pwadd' || $tradeInfo['type'] == 'vipcard') {
            // 停车场洗车支付成功
            $model = new ParkWashModel();
        }

        if ($model) {
            return $model->handleCardSuc($tradeInfo['id'], $tradeParam);
        }

        return success('OK');
    }

}
