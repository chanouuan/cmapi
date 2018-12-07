<?php

namespace models;

use library\Crud;

class TradeModel extends Crud {

    /**
     * 更新交易单支付参数
     */
    public function savePayParam ($id, $param)
    {
        return $this->getDb()->update('__tablepre__payments', $param, 'id = ' . $id);
    }

    /**
     * 获取交易单
     */
    public function get ($id, $where = null, $field = '*')
    {
        if (isset($id)) {
            if (is_array($where)) {
                $where[] = 'id = ' . $id;
            } else {
                $where = $where ? ($where . ' and id = ' . $id) : (' id = ' . $id);
            }
        }
        if (!$where) {
            return null;
        }
        return $this->getDb()->table('__tablepre__payments')->field($field)->where($where)->find();
    }

    /**
     * 支付确认
     */
    public function payQuery ($uid, $tradeid)
    {
        $tradeid = intval($tradeid);
        if (!$trade_info = $this->getDb()
            ->field('id,pay,payway,mchid,status')
            ->table('__tablepre__payments')
            ->where('id = ' . $tradeid . ' and trade_id = ' . $uid)
            ->find()) {
            return error('参数错误');
        }

        if ($trade_info['status'] == 1) {
            return success('支付成功');
        } elseif ($trade_info['status'] != 0) {
            return error('不是待支付订单');
        }

        if (!$trade_info['payway']) {
            return error('还未支付');
        }

        // 查询订单
        try {
            $result = https_request(gurl($trade_info['payway'] . '/query', 'ajax=1&tradeid=' . $trade_info['id']));
        } catch (\Exception $e) {
            return error($e->getMessage());
        }
        if ($result['errorcode'] !== 0) {
            return error($result['message']);
        }
        $result = $result['data'];
        if ($result['pay_success'] !== 'SUCCESS') {
            return error($result['trade_status']);
        }
        if ($result['mchid'] != $trade_info['mchid'] || $result['total_fee'] != $trade_info['pay']) {
            return error('支付验证失败');
        }

        // 支付成功
        return $this->paySuccess($trade_info['payway'], $result['out_trade_no'], $result['trade_no'], $result['mchid'], $result['trade_type'], $result['trade_status']);
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
    public function paySuccess ($payway, $out_trade_no, $trade_no, $mchid, $trade_type, $trade_status = '')
    {
        if (!$trade_info = $this->get(null, 'status = 0 and ordercode = "' . $out_trade_no . '"', 'id,type,trade_id,param_id,pay,money,status')) {
            return error($out_trade_no . '未找到');
        }

        $_trade = [
            'payway' => strtolower($payway),
            'status' => 1,
            'trade_no' => $trade_no,
            'paytime' => date('Y-m-d H:i:s', TIMESTAMP),
            'mchid' => $mchid,
            'trade_type' => $trade_type,
            'trade_status' => $trade_status
        ];

        if ($trade_info['type'] == 'xc') {
            // 洗车机支付成功
            if (!$this->getDb()->update('__tablepre__payments', $_trade, 'status = 0 and id = ' . $trade_info['id'])) {
                return error('交易更新失败');
            }

            $userModel = new \models\UserModel();
            $xicheModel = new \models\XicheModel();

            // 获取设备
            $device_info = $xicheModel->getDeviceById($trade_info['param_id']);

            // 账户充值
            $ret = $userModel->recharge([
                'platform' => 3,
                'authcode' => md5('xc' . $trade_info['trade_id']),
                'trade_no' => $trade_no,
                'money' => $trade_info['pay']
            ]);
            if ($ret['errorcode'] !== 0) {
                // 日志
                $xicheModel->log('recharge_error', [
                    'name' => '支付回调成功,账户充值(' . round_dollar($trade_info['pay']) . '元)异常',
                    'uid' => $trade_info['trade_id'],
                    'devcode' => $device_info['devcode'],
                    'content' => [
                        'trade' => $trade_info,
                        'result' => $ret
                    ]
                ]);
            }
            // 账户消费
            $ret = $userModel->consume([
                'platform' => 3,
                'authcode' => md5('xc' . $trade_info['trade_id']),
                'trade_no' => $out_trade_no,
                'money' => $trade_info['money']
            ]);
            if ($ret['errorcode'] !== 0) {
                // 日志
                $xicheModel->log('consume_error', [
                    'name' => '支付回调成功,账户消费(' . round_dollar($trade_info['money']) . '元)异常',
                    'uid' => $trade_info['trade_id'],
                    'devcode' => $device_info['devcode'],
                    'content' => [
                        'trade' => $trade_info,
                        'result' => $ret
                    ]
                ]);
            }

            // 保存订单到洗车机
            $ret = $xicheModel->XiCheCOrder($device_info['devcode'], $out_trade_no, $trade_info['money']);
            if ($ret['errorcode'] !== 0) {
                // 记录日志
                $xicheModel->log('api_error', [
                    'name' => '保存订单到洗车机异常',
                    'uid' => $trade_info['trade_id'],
                    'devcode' => $device_info['devcode'],
                    'content' => [
                        'trade' => $trade_info,
                        'result' => $ret
                    ]
                ]);
            }

        } else {

            return error($out_trade_no . '未知交易类型');
        }

        return success('支付成功');
    }

}