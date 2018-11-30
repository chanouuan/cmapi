<?php

namespace models;

use library\Crud;
use library\DB;

class UserModel extends Crud {

    /**
     * 检查交易号
     */
    protected function checkTradeNo ($post) {
        if (!isset($post['type'])) {
            return DB::getInstance()->delete('__tablepre__trades', 'platform = :platform and trade_no = :trade_no', ['platform' => $post['platform'], 'trade_no' => $post['trade_no']]);
        }
        if (DB::getInstance()
            ->table('__tablepre__trades')
            ->field('id')
            ->where('platform = ? and trade_no = ?')
            ->bindValue([$post['platform'], $post['trade_no']])
            ->count()) {
            return false;
        }
        if (!DB::getInstance()->insert('__tablepre__trades', [
            'platform' => $post['platform'],
            'trade_no' => $post['trade_no'],
            'money' => $post['money'],
            'type' => $post['type'],
            'uid' => $post['uid'],
            'createtime' => date('Y-m-d H:i:s', TIMESTAMP)
        ])) {
            return false;
        }
        return true;
    }

    /**
     * 获取绑定用户
     */
    public function getBindingUser ($post) {
        return DB::getInstance()
            ->table('__tablepre__loginbinding')
            ->field('uid')
            ->where('platform = ? and authcode = ?')
            ->bindValue([$post['platform'], $post['authcode']])
            ->count();
    }

    /**
     * 获取用户信息
     */
    public function getUserInfo ($uid) {
        $uid = intval($uid);

        $user_info = $this->getDb('chemiv2')
            ->table('chemi_member')
            ->field('member_id, member_name, member_sex, member_avatar, nickname, available_predeposit')
            ->where('member_state = 1 and member_id = ?')
            ->bindValue([$uid])
            ->find();
        if (!$user_info) {
            return error('用户不存在或已禁用！');
        }

        $result = [
            'uid' => $user_info['member_id'],
            'telephone' => $user_info['member_name'],
            'avatar' => $user_info['member_avatar'] ? ('/mobile/data/upload/shop/mobile/avatar/' . $user_info['member_avatar']) : '',
            'nickname' => strval($user_info['nickname']),
            'sex' => strval($user_info['member_sex']),
            'money' => floatval($user_info['available_predeposit']) * 100
        ];
        unset($user_info);

        return success($result);
    }

    /**
     * 第三方平台绑定
     */
    public function loginBinding ($post) {
        $post['platform'] = intval($post['platform']);
        $post['type'] = msubstr(trim($post['type']), 0, 5);
        $post['authcode'] = msubstr(trim($post['authcode']), 0, 32);
        $post['nickname'] = msubstr(trim($post['nickname']), 0, 20);
        $post['telephone'] = trim($post['telephone']);
        $post['msgcode'] = addslashes($post['msgcode']);

        if (empty($post['authcode'])) {
            return error('参数错误：authcode不能为空！');
        }
        if (!preg_match("/^1[0-9]{10}$/", $post['telephone'])) {
            return error('参数错误：telephone格式不正确！');
        }

        // 查询绑定
        $userid = $this->getBindingUser($post);

        if ($userid) {
            // 查询用户信息
            return $this->getUserInfo($userid);
        }

        // 验证短信验证码
        // 公司平台不验证
        if (!($post['platform'] == 2 && $post['msgcode'] == '111111')) {
            if (!$this->checkSmsCode($post['telephone'], $post['msgcode'])) {
                return error('验证码错误！');
            }
        }

        // 查询手机号是否存在
        $user_info = DB::getInstance('chemiv2')
            ->table('chemi_member')
            ->field('member_id, member_name')
            ->where('member_name = ?')
            ->bindValue([$post['telephone']])
            ->limit(1)
            ->find();

        if (!$user_info) {
            // 注册新用户
            if (!$uid = DB::getInstance('chemiv2')->transaction(function  ($db) use( $post) {
                if (!$db->insert('chemi_member', [
                    'member_name' => $post['telephone'],
                    'member_time' => TIMESTAMP,
                    'member_old_login_time'=>0,
                    'member_login_time'=>0,
                    'member_login_num'=>0
                ])) {
                    return false;
                }
                $uid = $db->getlastid();
                if (!DB::getInstance()->insert('__tablepre__loginbinding', [
                    'platform' => $post['platform'],
                    'uid' => $uid,
                    'type' => $post['type'],
                    'authcode' => $post['authcode'],
                    'nickname' => $post['nickname'],
                    'tel' => $post['telephone'],
                    'activetime' => date('Y-m-d H:i:s', TIMESTAMP)
                ])) {
                    return false;
                }
                return $uid;
            })) {
                return error('创建用户失败！');
            }
            $user_info = [
                'member_id' => $uid,
                'member_name' => $post['telephone']
            ];
        } else {
            // 已有用户直接绑定
            if (!DB::getInstance()->insert('__tablepre__loginbinding', [
                'platform' => $post['platform'],
                'uid' => $user_info['member_id'],
                'type' => $post['type'],
                'authcode' => $post['authcode'],
                'nickname' => $post['nickname'],
                'tel' => $post['telephone'],
                'activetime' => date('Y-m-d H:i:s', TIMESTAMP)
            ])) {
                return error('绑定失败！');
            }
        }

        return $this->getUserInfo($user_info['member_id']);
    }

    /**
     * 用户消费
     */
    public function consume ($post) {
        $post['platform'] = intval($post['platform']);
        $post['authcode'] = addslashes($post['authcode']);
        $post['trade_no'] = addslashes(msubstr($post['trade_no'], 0, 32));
        $post['money'] = intval($post['money']);
        $post['money'] = $post['money'] < 1 ? 0 : $post['money'];

        if (!$post['platform'] || !$post['authcode']) {
            return error('平台代码不能为空');
        }
        if (!$post['trade_no']) {
            return error('交易号不能为空');
        }
        if (!$post['money']) {
            return error('消费金额不能为空');
        }

        // 查询绑定
        $userid = $this->getBindingUser($post);

        if (!$userid) {
            return error('未绑定用户');
        }

        // 防止交易号重复
        if (!$this->checkTradeNo([
            'platform' => $post['platform'],
            'trade_no' => $post['trade_no'],
            'money' => $post['money'],
            'type' => 2,
            'uid' => $userid,
        ])) {
            return error('该笔交易已提交');
        }

        $user_info = $this->getUserInfo($userid);
        if ($user_info['errorcode'] !== 0) {
            return $user_info;
        }
        $user_info = $user_info['data'];

        // 验证余额
        if ($post['money'] > $user_info['money']) {
            return error('用户余额不足');
        }

        // 创建消费流水单
        // 和原系统流程不一样，原系统用business_ordersn作为唯一判断，如果有就更新，没有就插入
        // 这里做成每次都插入不一样的数据
        $data = [];
        $data['consume_ordersn'] = date('YmdHis').(rand()%10).(rand()%10).(rand()%10).(rand()%10).(rand()%10);
        $data['business_ordersn'] = date('YmdHis').(rand()%10).(rand()%10).(rand()%10).(rand()%10).(rand()%10);
        $data['business_type'] = 100 + $post['platform']; // 新定义支付场景，商城消费
        $data['discount_id'] = 0;
        $data['member_id'] = $user_info['uid'];
        $data['pay_type'] = 1; // 支付方式，车币支付
        $data['consume_amount'] = $post['money'] / 100; // 消费金额
        $data['pay_amount'] = 0;
        $data['discount_amount'] = 0;
        $data['consume_state'] = 1; // 1申请中，2成功，3失败
        $data['remark'] = '商城消费' . $post['platform'];
        $data['attr_exd_c'] = $post['trade_no'];
        $data['consume_dt'] = date('Y-m-d H:i:s', TIMESTAMP);;
        $data['update_dt'] = date('Y-m-d H:i:s', TIMESTAMP);;
        $data['insert_dt'] = date('Y-m-d H:i:s', TIMESTAMP);;
        if (!DB::getInstance('chemiaccount')->insert('t_chemi_account_consume_flow', $data)) {
            return error('创建消费流水单失败');
        }

        $res = DB::getInstance('chemiv2')->transaction(function  ($db) use($user_info, $post, $data) {

            // 更新用户余额
            if (!$db->update('chemi_member', ['available_predeposit' => '{!available_predeposit-'.$data['consume_amount'].'}'], 'member_id = ' . $user_info['uid'] . ' and available_predeposit >= ' . $data['consume_amount'])) {
                return false;
            }

            return DB::getInstance('chemiaccount')->transaction(function  ($db_1) use($user_info, $post, $data) {
                // 更新消费流水单
                if (!$db_1->update('t_chemi_account_consume_flow', ['consume_state' => 2], 'business_ordersn = "'.$data['business_ordersn'].'"')) {
                    return false;
                }

                // 记录消费记录
                $param = [];
                $param['flow_ordersn'] = date('YmdHis').(rand()%10).(rand()%10).(rand()%10).(rand()%10).(rand()%10);
                $param['business_ordersn'] = $data['business_ordersn'];
                $param['business_type'] = $data['business_type']; // 充值
                $param['flow_type'] = 2; // 1加车币，2减车币
                $param['member_id'] = $user_info['uid'];
                $param['flow_amount'] = $data['consume_amount']; // 变动金额
                $param['flow_state'] = 2; // 1申请中，2成功，3失败
                $param['member_balance'] = $user_info['money'] / 100 - $data['consume_amount']; // 变动后用户余额
                $param['present_ordersn'] = '';
                $param['attr_exd_a'] = '商城消费' . $post['platform'];
                $param['attr_exd_c'] = $post['trade_no'];
                $param['flow_dt'] = date('Y-m-d H:i:s', TIMESTAMP);;
                $param['update_dt'] = date('Y-m-d H:i:s', TIMESTAMP);;
                $param['insert_dt'] = date('Y-m-d H:i:s', TIMESTAMP);;
                if (!$db_1->insert('t_chemi_account_chebi_flow', $param)) {
                    return false;
                }
                return true;
            });
        });

        if (!$res) {
            // 回滚交易号
            $this->checkTradeNo([
                'platform' => $post['platform'],
                'trade_no' => $post['trade_no']
            ]);
            return error('消费失败');
        }

        return success('OK');
    }

    /**
     * 用户充值
     */
    public function recharge ($post) {
        $post['platform'] = intval($post['platform']);
        $post['authcode'] = addslashes($post['authcode']);
        $post['trade_no'] = addslashes(msubstr($post['trade_no'], 0, 32));
        $post['money'] = intval($post['money']);
        $post['money'] = $post['money'] < 1 ? 0 : $post['money'];

        if (!$post['platform'] || !$post['authcode']) {
            return error('平台代码不能为空');
        }
        if (!$post['trade_no']) {
            return error('交易号不能为空');
        }
        if (!$post['money']) {
            return error('充值金额不能为空');
        }
        if ($post['money'] > 5000000) {
            return error('单笔充值最高50000元');
        }

        // 查询绑定
        $userid = $this->getBindingUser($post);

        if (!$userid) {
            return error('未绑定用户');
        }

        // 防止交易号重复
        if (!$this->checkTradeNo([
            'platform' => $post['platform'],
            'trade_no' => $post['trade_no'],
            'money' => $post['money'],
            'type' => 1,
            'uid' => $userid,
        ])) {
            return error('该笔交易已提交');
        }

        $user_info = $this->getUserInfo($userid);
        if ($user_info['errorcode'] !== 0) {
            return $user_info;
        }
        $user_info = $user_info['data'];

        $res = DB::getInstance('chemiv2')->transaction(function  ($db) use($user_info, $post) {
            // 生成充值订单
            $data = [];
            $data['pdr_sn'] = date('YmdHis').(rand()%10).(rand()%10).(rand()%10).(rand()%10).(rand()%10);
            $data['pdr_member_id'] = $user_info['uid'];
            $data['pdr_member_name'] = $user_info['telephone'];
            $data['pdr_amount'] = $post['money'] / 100;
            $data['pdr_payment_code'] = 100 + $post['platform']; // 新定义111为商城充值
            $data['pdr_payment_state'] = '1'; // 充值成功
            $data['pdr_add_time'] = TIMESTAMP;
            $data['pdr_admin'] = '商城充值' . $post['platform'];
            if (!$db->insert('chemi_pd_recharge', $data)) {
                return false;
            }

            // 更新用户余额
            if (!$db->update('chemi_member', ['available_predeposit' => '{!available_predeposit+'.$data['pdr_amount'].'}'], 'member_id = ' . $user_info['uid'])) {
                return false;
            }

            return DB::getInstance('chemiaccount')->transaction(function  ($db_1) use($user_info, $post, $data) {
                // 写入支付流水
                // 原系统先插入t_chemi_account_pay_flow表，成功后再删除然后插入t_chemi_account_pay_flow_log
                // 这里直接插入t_chemi_account_pay_flow_log
                $param = [];
                $param['pay_ordersn'] = date('YmdHis').(rand()%10).(rand()%10).(rand()%10).(rand()%10).(rand()%10);
                $param['business_ordersn'] = $data['pdr_sn'];
                $param['pay_level'] = 9; // 充值
                $param['pay_type'] = 100 + $post['platform']; // 商城充值
                $param['pay_amount'] = $data['pdr_amount'];
                $param['pay_state'] = 2; // 1：申请中，2：成功，3：失败
                $param['member_id'] = $user_info['uid'];
                $param['pay_dt'] = date('Y-m-d H:i:s', TIMESTAMP);
                $param['update_dt'] = date('Y-m-d H:i:s', TIMESTAMP);
                $param['insert_dt'] = date('Y-m-d H:i:s', TIMESTAMP);
                $param['log_dt'] = date('Y-m-d H:i:s', TIMESTAMP);
                $param['remark'] = '商城充值' . $post['platform'];
                $param['attr_exd_c'] = $post['trade_no'];
                if (!$db_1->insert('t_chemi_account_pay_flow_log', $param)) {
                    return false;
                }
                // 记录消费记录
                $param = [];
                $param['flow_ordersn'] = date('YmdHis').(rand()%10).(rand()%10).(rand()%10).(rand()%10).(rand()%10);
                $param['business_ordersn'] = $data['pdr_sn'];
                $param['business_type'] = 9; // 充值
                $param['flow_type'] = 1; // 1加车币，0减车币
                $param['member_id'] = $user_info['uid'];
                $param['flow_amount'] = $data['pdr_amount'];
                $param['flow_state'] = 2; // 1申请中，2成功，3失败
                $param['member_balance'] = $user_info['money'] / 100 + $data['pdr_amount']; // 变动后用户余额
                $param['present_ordersn'] = '';
                $param['attr_exd_a'] = '商城充值' . $post['platform'];
                $param['attr_exd_c'] = $post['trade_no'];
                $param['flow_dt'] = date('Y-m-d H:i:s', TIMESTAMP);;
                $param['update_dt'] = date('Y-m-d H:i:s', TIMESTAMP);;
                $param['insert_dt'] = date('Y-m-d H:i:s', TIMESTAMP);;
                if (!$db_1->insert('t_chemi_account_chebi_flow', $param)) {
                    return false;
                }
                return true;
            });
        });

        if (!$res) {
            // 回滚交易号
            $this->checkTradeNo([
                'platform' => $post['platform'],
                'trade_no' => $post['trade_no']
            ]);
            return error('充值失败');
        }

        return success('OK');
    }

    /**
     * 验证短信验证码
     */
    public function checkSmsCode ($telephone, $code)
    {
        // 是否开启短信验证
        if (!getSysConfig('sms_verify')) {
            return true;
        }
        if (!preg_match("/^1[0-9]{10}$/", $telephone) || !preg_match("/^[0-9]{4,6}$/", $code)) {
            return false;
        }
        // 处理逻辑为同一个验证码5分钟内可以验证通过10次
        if (!$result = $this->getDb()
            ->field('id, code, errorcount, sendtime')
            ->table('__tablepre__smscode')
            ->where('tel = ?')
            ->bindValue($telephone)
            ->find()) {
            return false;
        }
        if ($result['errorcount'] <= 10) {
            // 累计次数
            $this->getDb()->update('__tablepre__smscode', [
                'errorcount' => '{!errorcount+1}'
            ], 'id = ' . $result['id']);
        }
        return $result['code'] == $code
            && $result['errorcount'] <= 10
            && $result['sendtime'] > (TIMESTAMP - 300);
    }

    /**
     * 发送短信验证码
     */
    public function sendSmsCode ($post)
    {
        if (!preg_match("/^1[0-9]{10}$/", $post['telephone'])) {
            return error('手机号为空或格式错误');
        }

        $code = (rand() % 10) . (rand() % 10) . (rand() % 10) . (rand() % 10) . (rand() % 10) . (rand() % 10);

        $result_sms = $this->getDb()
            ->table('__tablepre__smscode')
            ->field('id,sendtime,hour_fc,day_fc')
            ->where('tel = ?')
            ->bindValue($post['telephone'])
            ->find();

        if (!$result_sms) {
            $result_sms = [
                'tel' => $post['telephone']
            ];
            if (!$this->getDb()->insert('__tablepre__smscode', [
                'tel' => ':tel'
            ], [
                'tel' => $post['telephone']
            ])) {
                return error('发送失败');
            }
            $result_sms['id'] = $this->getDb()->getlastid();
        }

        $params = [
            'code' => $code,
            'errorcount' => 0,
            'sendtime' => TIMESTAMP,
            'hour_fc' => 1,
            'day_fc' => 1
        ];

        if ($result_sms['sendtime']) {
            // 限制发送频率
            if ($result_sms['sendtime'] + 10 > TIMESTAMP) {
                return error('验证码已发送,请稍后再试');
            }
            if (date('YmdH', $result_sms['sendtime']) == date('YmdH', TIMESTAMP)) {
                // 触发时级流控
                if ($result_sms['hour_fc'] >= getSysConfig('hour_fc')) {
                    return error('本时段发送次数已达上限');
                }
                $params['hour_fc'] = '{!hour_fc+1}';
            }
            if (date('Ymd', $result_sms['sendtime']) == date('Ymd', TIMESTAMP)) {
                // 触发天级流控
                if ($result_sms['day_fc'] >= getSysConfig('day_fc')) {
                    return error('今日发送次数已达上限');
                }
                $params['day_fc'] = '{!day_fc+1}';
            }
        }

        if (!$this->getDb()->update('__tablepre__smscode', $params, [
            'id = ' . $result_sms['id'],
            'hour_fc <= ' . getSysConfig('hour_fc'),
            'day_fc <= ' . getSysConfig('day_fc')
        ])) {
            return error('发送失败');
        }

        // 发送短信
        $result = $this->sendSmsServer($post['telephone'], $code);
        if ($result['errorcode'] !== 0) {
            return $result;
        }

        return success('OK');
    }

    /**
     * 短信服务
     */
    protected function sendSmsServer ($phone, $code) {
        $templete = "您的验证码是{".$code."}，5分钟内有效！ #车秘在身边、养车更简单#";
        $param = array(
            'cdkey' => '8SDK-EMY-6699-RJSUN',
            'password' => '275134',
            'phone' => $phone,
            'message' => $templete
        );
        try{
            $result = https_request('http://hprpt2.eucp.b2m.cn:8080/sdkproxy/sendsms.action', $param, 10, 'xml');
        } catch (\Exception $e) {
            return error($e->getMessage());
        }
        if (!isset($result->error) || $result->error != '0') {
            return error(json_encode($result->message));
        }
        return success('OK');
    }

}