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
     * 根据绑定信息获取用户信息
     */
    public function getUserByBinding ($condition) {
        return DB::getInstance()
            ->table('__tablepre__loginbinding')
            ->field('uid,tel')
            ->where($condition)
            ->select();
    }

    /**
     * 设置登录密码
     */
    public function setpw ($post) {
        $post['password'] = trim($post['password']);

        if (strlen($post['password']) < 6 || strlen($post['password']) > 32) {
            return error('请输入6-32位密码');
        }

        $userInfo = $this->getUserInfo($post['uid']);
        if ($userInfo['errorcode'] !== 0) {
            return $userInfo;
        }
        $userInfo = $userInfo['result'];

        if ($userInfo['ispw']) {
            return error('你已设置过密码');
        }

        if (false === $this->getDb('chemiv2')->update('chemi_member', [
            'member_passwd' => md5(md5($post['password']))
        ], 'member_id = ' . $post['uid'])) {
            return error('密码设置失败');
        }
        return success('密码设置成功');
    }

    /**
     * 获取用户信息(搜索条件)
     */
    public function getUserInfoCondition ($condition, $field = 'member_id, member_name') {
        return $this->getDb('chemiv2')
            ->table('chemi_member')
            ->field($field)
            ->where($condition)
            ->limit(1)
            ->find();
    }

    /**
     * 验证车秘登录token
     */
    public function checkCmToken ($condition) {
        return $this->getDb('chemiv2')
            ->table('chemi_mb_user_token')
            ->field('member_id')
            ->where($condition)
            ->limit(1)
            ->find();
    }

    /**
     * 获取用户信息
     */
    public function getUserInfo ($uid) {
        $uid = intval($uid);

        $userInfo = $this->getDb('chemiv2')
            ->table('chemi_member')
            ->field('member_id, member_name, member_passwd, member_sex, member_avatar, nickname, available_predeposit')
            ->where('member_state = 1 and member_id = ?')
            ->bindValue([$uid])
            ->find();
        if (!$userInfo) {
            return error('用户不存在或已禁用！');
        }

        $result = [
            'uid' => $userInfo['member_id'],
            'telephone' => $userInfo['member_name'],
            'avatar' => $userInfo['member_avatar'] ? ('/mobile/data/upload/shop/mobile/avatar/' . $userInfo['member_avatar']) : '',
            'nickname' => strval($userInfo['nickname']),
            'sex' => strval($userInfo['member_sex']),
            'money' => floatval($userInfo['available_predeposit']) * 100,
            'ispw' => $userInfo['member_passwd'] ? 1 : 0
        ];
        unset($userInfo);

        return success($result);
    }

    /**
     * 注册车秘用户
     */
    public function regCm ($post) {
        if (!$this->getDb('chemiv2')->insert('chemi_member', [
            'member_name' => $post['telephone'],
            'member_time' => TIMESTAMP,
            'member_old_login_time'=>0,
            'member_login_time'=>0,
            'member_login_num'=>0
        ])) {
            return false;
        }
        return $this->getDb('chemiv2')->getlastid();
    }

    /**
     * 第三方平台绑定（手机号密码方式）
     */
    public function loginBinding ($post) {
        $post['platform'] = intval($post['platform']);
        $post['type'] = msubstr(trim($post['type']), 0, 5);
        $post['authcode'] = msubstr(trim($post['authcode']), 0, 32);
        $post['nickname'] = msubstr(trim($post['nickname']), 0, 20);
        $post['telephone'] = trim($post['telephone']);
        $post['msgcode'] = addslashes($post['msgcode']); // 短信验证码
        $post['password'] = addslashes($post['password']); // 用户密码
        $post['nopw'] = isset($post['nopw']) ? true :false; // 是否免密、免验证码

        // 公司平台不验证
        if ($post['platform'] == 2 && $post['msgcode'] == '111111') {
            $post['nopw'] = true;
        }

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

        // 查询手机号是否存在
        $userInfo = DB::getInstance('chemiv2')
            ->table('chemi_member')
            ->field('member_id, member_name, member_passwd')
            ->where('member_name = ?')
            ->bindValue([$post['telephone']])
            ->limit(1)
            ->find();

        // 验证短信验证码/密码
        if (!$post['nopw']) {
            if (!$post['password'] && !$post['msgcode']) {
                return error('验证码或密码不能为空！');
            }
            if ($post['password']) {
                // 密码验证
                if (!$userInfo) {
                    return error('用户名或密码错误！');
                }
                if ($userInfo['member_passwd'] != md5(md5($post['password']))) {
                    return error('用户名或密码错误！');
                }
            }
            if ($post['msgcode']) {
                // 短信验证
                if (!$this->checkSmsCode($post['telephone'], $post['msgcode'])) {
                    return error('验证码错误！');
                }
            }
        }

        if (!$userInfo) {
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
            $userInfo = [
                'member_id' => $uid,
                'member_name' => $post['telephone']
            ];
        } else {
            // 已有用户直接绑定
            if (!DB::getInstance()->insert('__tablepre__loginbinding', [
                'platform' => $post['platform'],
                'uid' => $userInfo['member_id'],
                'type' => $post['type'],
                'authcode' => $post['authcode'],
                'nickname' => $post['nickname'],
                'tel' => $post['telephone'],
                'activetime' => date('Y-m-d H:i:s', TIMESTAMP)
            ])) {
                return error('绑定失败！');
            }
        }

        return $this->getUserInfo($userInfo['member_id']);
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
        $post['remark'] = $post['remark'] ? $post['remark'] : '平台消费';

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

        $userInfo = $this->getUserInfo($userid);
        if ($userInfo['errorcode'] !== 0) {
            return $userInfo;
        }
        $userInfo = $userInfo['result'];

        // 验证余额
        if ($post['money'] > $userInfo['money']) {
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
        $data['member_id'] = $userInfo['uid'];
        $data['pay_type'] = 1; // 支付方式，车币支付
        $data['consume_amount'] = $post['money'] / 100; // 消费金额
        $data['pay_amount'] = 0;
        $data['discount_amount'] = 0;
        $data['consume_state'] = 1; // 1申请中，2成功，3失败
        $data['remark'] = $post['remark'];
        $data['attr_exd_c'] = $post['trade_no'];
        $data['consume_dt'] = date('Y-m-d H:i:s', TIMESTAMP);
        $data['update_dt'] = date('Y-m-d H:i:s', TIMESTAMP);
        $data['insert_dt'] = date('Y-m-d H:i:s', TIMESTAMP);
        if (!DB::getInstance('chemiaccount')->insert('t_chemi_account_consume_flow', $data)) {
            return error('创建消费流水单失败');
        }

        $res = DB::getInstance('chemiv2')->transaction(function  ($db) use($userInfo, $post, $data) {

            // 更新用户余额
            if (!$db->update('chemi_member', ['available_predeposit' => '{!available_predeposit-'.$data['consume_amount'].'}'], 'member_id = ' . $userInfo['uid'] . ' and available_predeposit >= ' . $data['consume_amount'] . ' and available_predeposit = ' . ($userInfo['money'] / 100))) {
                return false;
            }

            return DB::getInstance('chemiaccount')->transaction(function  ($db_1) use($userInfo, $post, $data) {
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
                $param['member_id'] = $userInfo['uid'];
                $param['flow_amount'] = $data['consume_amount']; // 变动金额
                $param['flow_state'] = 2; // 1申请中，2成功，3失败
                $param['member_balance'] = $userInfo['money'] / 100 - $data['consume_amount']; // 变动后用户余额
                $param['present_ordersn'] = '';
                $param['attr_exd_a'] = $post['remark'];
                $param['attr_exd_c'] = $post['trade_no'];
                $param['flow_dt'] = date('Y-m-d H:i:s', TIMESTAMP);
                $param['update_dt'] = date('Y-m-d H:i:s', TIMESTAMP);
                $param['insert_dt'] = date('Y-m-d H:i:s', TIMESTAMP + 1); // 为了排序消费记录在充值记录前面，所以这里+1
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
        $post['remark'] = $post['remark'] ? $post['remark'] : '平台充值';

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

        $userInfo = $this->getUserInfo($userid);
        if ($userInfo['errorcode'] !== 0) {
            return $userInfo;
        }
        $userInfo = $userInfo['result'];

        $res = DB::getInstance('chemiv2')->transaction(function  ($db) use($userInfo, $post) {
            // 生成充值订单
            $data = [];
            $data['pdr_sn'] = date('YmdHis').(rand()%10).(rand()%10).(rand()%10).(rand()%10).(rand()%10);
            $data['pdr_member_id'] = $userInfo['uid'];
            $data['pdr_member_name'] = $userInfo['telephone'];
            $data['pdr_amount'] = $post['money'] / 100;
            $data['pdr_payment_code'] = 100 + $post['platform']; // 新定义111为商城充值
            $data['pdr_payment_state'] = '1'; // 充值成功
            $data['pdr_add_time'] = TIMESTAMP;
            $data['pdr_admin'] = $post['remark'];
            if (!$db->insert('chemi_pd_recharge', $data)) {
                return false;
            }

            // 更新用户余额
            if (!$db->update('chemi_member', ['available_predeposit' => '{!available_predeposit+'.$data['pdr_amount'].'}'], 'member_id = ' . $userInfo['uid'] . ' and available_predeposit = ' . ($userInfo['money'] / 100))) {
                return false;
            }

            return DB::getInstance('chemiaccount')->transaction(function  ($db_1) use($userInfo, $post, $data) {
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
                $param['member_id'] = $userInfo['uid'];
                $param['pay_dt'] = date('Y-m-d H:i:s', TIMESTAMP);
                $param['update_dt'] = date('Y-m-d H:i:s', TIMESTAMP);
                $param['insert_dt'] = date('Y-m-d H:i:s', TIMESTAMP);
                $param['log_dt'] = date('Y-m-d H:i:s', TIMESTAMP);
                $param['remark'] = $post['remark'];
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
                $param['member_id'] = $userInfo['uid'];
                $param['flow_amount'] = $data['pdr_amount'];
                $param['flow_state'] = 2; // 1申请中，2成功，3失败
                $param['member_balance'] = $userInfo['money'] / 100 + $data['pdr_amount']; // 变动后用户余额
                $param['present_ordersn'] = '';
                $param['attr_exd_a'] = $post['remark'];
                $param['attr_exd_c'] = $post['trade_no'];
                $param['flow_dt'] = date('Y-m-d H:i:s', TIMESTAMP);
                $param['update_dt'] = date('Y-m-d H:i:s', TIMESTAMP);
                $param['insert_dt'] = date('Y-m-d H:i:s', TIMESTAMP);
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

        $resultSms = $this->getDb()
            ->table('__tablepre__smscode')
            ->field('id,sendtime,hour_fc,day_fc')
            ->where('tel = ?')
            ->bindValue($post['telephone'])
            ->find();

        if (!$resultSms) {
            $resultSms = [
                'tel' => $post['telephone']
            ];
            if (!$this->getDb()->insert('__tablepre__smscode', [
                'tel' => ':tel'
            ], [
                'tel' => $post['telephone']
            ])) {
                return error('发送失败');
            }
            $resultSms['id'] = $this->getDb()->getlastid();
        }

        $params = [
            'code' => $code,
            'errorcount' => 0,
            'sendtime' => TIMESTAMP,
            'hour_fc' => 1,
            'day_fc' => 1
        ];

        if ($resultSms['sendtime']) {
            // 限制发送频率
            if ($resultSms['sendtime'] + 10 > TIMESTAMP) {
                return error('验证码已发送,请稍后再试');
            }
            if (date('YmdH', $resultSms['sendtime']) == date('YmdH', TIMESTAMP)) {
                // 触发时级流控
                if ($resultSms['hour_fc'] >= getSysConfig('hour_fc')) {
                    return error('本时段发送次数已达上限');
                }
                $params['hour_fc'] = '{!hour_fc+1}';
            }
            if (date('Ymd', $resultSms['sendtime']) == date('Ymd', TIMESTAMP)) {
                // 触发天级流控
                if ($resultSms['day_fc'] >= getSysConfig('day_fc')) {
                    return error('今日发送次数已达上限');
                }
                $params['day_fc'] = '{!day_fc+1}';
            }
        }

        if (!$this->getDb()->update('__tablepre__smscode', $params, [
            'id = ' . $resultSms['id'],
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
            $result = https_request('http://hprpt2.eucp.b2m.cn:8080/sdkproxy/sendsms.action', $param, null, 4, 'xml');
        } catch (\Exception $e) {
            return error($e->getMessage());
        }
        if (!isset($result->error) || $result->error != '0') {
            return error(json_encode($result->message));
        }
        return success('OK');
    }

    /**
     * 登录状态设置
     */
    public function setloginstatus ($uid, $scode, $opt = [], $expire = 0)
    {
        if (!$uid) {
            return error('no session!');
        }
        $update = [
            'userid' => $uid,
            'scode' => $scode,
            'clienttype' => CLIENT_TYPE,
            'clientinfo' => null,
            'loginip' => get_ip(),
            'online' => 1,
            'updated_at' => date('Y-m-d H:i:s', TIMESTAMP)
        ];
        !empty($opt) && $update = array_merge($update, $opt);
        if (!$this->getDb()->norepeat('__tablepre__session', $update)) {
            return error('session error!');
        }
        $token = rawurlencode(authcode("$uid\t$scode\t{$update['clienttype']}", 'ENCODE'));
        set_cookie('token', $token, $expire);
        return success([
            'token' => $token
        ]);
    }

    /**
     * 登出
     */
    public function logout ($uid, $clienttype = null)
    {
        $this->getDb()->update('__tablepre__session', [
            'scode' => 0,
            'online' => 0,
            'updated_at' => date('Y-m-d H:i:s', TIMESTAMP)
        ], [
            'userid' => $uid,
            'clienttype' => get_real_val($clienttype, CLIENT_TYPE)
        ]);
        set_cookie('token', null);
    }

    /**
     * 获取车秘用户车辆信息
     */
    public function getUserCars ($uid)
    {
        // 获取用户车辆
        // is_confirm 1 未认证 2 已认证
        $cars = $this->getDb('chemiv2')
            ->table('chemi_member_car')
            ->field('car_id as id,license_number,is_confirm,is_default,is_newcar')
            ->where([
                'member_id' => $uid,
                'is_del' => 0,
                'is_confirm' => ['in', [1, 2]]
            ])->select();

        if (empty($cars)) {
            return success([]);
        }

        // 未认证的在 chemi_member_car_auth 表，已认证的在 chemi_member_car_auth_log 表
        $authCar = [];
        foreach ($cars as $k => $v) {
            $authCar[$v['is_confirm']][] = $v['license_number'];
        }

        // 获取车辆行驶证
        // brand_number 车辆品牌
        // frame_number 车架号
        // engine_number 发动机号
        // register_time 注册日期
        // car_name 车主姓名
        // vehicle_time 发证日期

        $cards = [];
        if (isset($authCar[1])) {
            $cards = $this->getDb('chemiv2')
                ->table('chemi_member_car_auth')
                ->field('license_number,brand_number,frame_number,engine_number,register_time,car_name,vehicle_time')
                ->where([
                    'member_id' => $uid,
                    'license_number' => ['in', $authCar[1]]
                ])->select();
        }
        if (isset($authCar[2])) {
            $cards = array_merge($cards, $this->getDb('chemiv2')
                ->table('chemi_member_car_auth_log')
                ->field('license_number,brand_number,frame_number,engine_number,register_time,car_name,vehicle_time')
                ->where([
                    'member_id' => $uid,
                    'license_number' => ['in', $authCar[2]]
                ])->select());
        }

        $cards = array_column($cards, null, 'license_number');

        foreach ($cars as $k => $v) {
            $cars[$k]['license'] = [];
            if (isset($cards[$v['license_number']])) {
                $license = $cards[$v['license_number']];
                $license['register_time'] = date('Y-m-d', $license['register_time']);
                $license['vehicle_time'] = date('Y-m-d', $license['vehicle_time']);
                $cars[$k]['license'] = $license;
            }
        }
        unset($cards);

        return success($cars);
    }

    /**
     * 添加车秘用户车辆
     */
    public function addUserCar ($uid, $post) {
        if (!check_car_license($post['license_number'])) {
            return error('车牌号错误');
        }
        $post['licenseId'] = $this->getLicenseId($post['license_number']);

        // 最多添加 7 辆新车
        $haveNum = $this->getDb('chemiv2')
            ->table('chemi_member_car')
            ->field('count(*)')
            ->where([
                'member_id' => $uid,
                'is_newcar' => 0
            ])->count();
        if ($haveNum >= 7) {
            return error('最多可添加 7 个车牌号');
        }

        $haveCar = $this->getDb('chemiv2')
            ->table('chemi_member_car')
            ->field('member_id')
            ->where([
                'license_number' => $post['license_number']
            ])->find();
        if ($haveCar) {
            if($haveCar['member_id'] == $uid) {
                return error('该车辆你已添加');
            } else {
                return error('该车辆已被其他人添加');
            }
        }

        // 是否认证过
        $lastAuth = $this->getDb('chemiv2')
            ->table('chemi_member_car_auth_log')
            ->field('auth_state')
            ->where([
                'member_id' => $uid,
                'license_number' => $post['license_number']
            ])
            ->limit(1)
            ->find();

        // 已认证通过的就不再认证
        $isConfirm = 1;
        if ($lastAuth) {
            if ($lastAuth['auth_state'] == 2) {
                $isConfirm = 2; // 已认证
            } else {
                $isConfirm = 1; // 未认证
            }
        }

        if (!$this->getDb('chemiv2')->insert('chemi_member_car', [
            'member_id' => $uid,
            'license_id' => $post['licenseId'],
            'license_number' => $post['license_number'],
            'is_confirm' => $isConfirm
        ])) {
            return error('认证失败');
        }

        return success('提交成功');
    }

    /**
     * 申请认证车辆
     */
    public function authUserCar ($uid, $post) {

        $post['id'] = intval($post['id']); // 车辆ID
        $post['license_number'] = trim($post['license_number']); //车牌号
        $post['brand_number'] = trim($post['brand_number']); //车辆型号
        $post['frame_number'] = trim($post['frame_number']); //车架号
        $post['engine_number'] = trim($post['engine_number']); //发动机号
        $post['register_dt'] = trim($post['register_dt']); //注册日期

        if (!$carInfo = $this->getDb('chemiv2')
            ->table('chemi_member_car')
            ->field('car_id,license_number,is_confirm')
            ->where([
                'member_id' => $uid,
                'car_id' => $post['id']
            ])->find()) {
            return error('该车辆不存在');
        }

        if ($carInfo['is_confirm'] == 2) {
            return error('该车辆已认证通过');
        }

        if ($carInfo['license_number'] != $post['license_number']) {
            return error('车牌号匹配错误（与待认证的车牌号不一致）');
        }

        $param = [
            'member_id' => $uid,
            'license_number' => $post['license_number'],
            'license_id' => $this->getLicenseId($post['license_number']),
            'brand_number' => $post['brand_number'],
            'frame_number' => $post['frame_number'],
            'engine_number' => $post['engine_number'],
            'register_time' => strtotime($post['register_dt']),
            'auth_state' => 1,
            'add_time' => TIMESTAMP,
            'update_time' => TIMESTAMP
        ];

        if ($this->getDb('chemiv2')
            ->table('chemi_member_car_auth')
            ->field('count(*)')
            ->where([
                'member_id' => $param['member_id'],
                'license_number' => $param['license_number']
            ])->count()) {
            // 更新
            unset($param['add_time']);
            if (!$this->getDb('chemiv2')->update('chemi_member_car_auth', $param, [
                'member_id' => $param['member_id'],
                'license_number' => $param['license_number']
            ])) {
                return error('更新失败');
            }
        } else {
            // 新增
            if (!$this->getDb('chemiv2')->insert('chemi_member_car_auth', $param)) {
                return error('新增失败');
            }
        }

        return success('申请认证已提交');
    }

    /**
     * 获取车秘用户优惠劵
     */
    public function getCouponList ($uid, $post) {

        $condition = [
            'voucher_owner_id' => $uid
        ];

        // 0 通用 1 门店 2 保险 3 停车
        if ($post['voucher_type']) {
            if (is_array($post['voucher_type'])) {
                $condition['voucher_type'] = ['in', $post['voucher_type']];
            } else {
                $condition['voucher_type'] = intval($post['voucher_type']);
            }
        }

        $voucherList = $this->getDb('chemiv2')
            ->table('chemi_voucher')
            ->field('voucher_id,voucher_type,voucher_title,voucher_price,voucher_price_type,voucher_start_date,voucher_end_date,voucher_limit,voucher_state')
            ->where($condition)
            ->order('voucher_state')
            ->select();

        // voucher_state 代金券状态(1-未用,2-已用,3-过期,4-收回,10锁定)
        // voucher_price 代金券面额
        // voucher_start_date 代金券有效期开始时间
        // voucher_end_date 代金券有效期结束时间
        // voucher_price_type 1=满减 2=立减 3=折扣满减 4=折扣立减
        // voucher_limit 消费满多少可以使用

        foreach ($voucherList as $k => $v) {
            $voucherList[$k]['voucher_state'] = ($v['voucher_state'] == 1 || $v['voucher_state'] == 2) ? $v['voucher_state'] : 3;
            $voucherList[$k]['voucher_start_date'] = $v['voucher_start_date'] ? date('Y-m-d H:i:s', $v['voucher_start_date']) : '';
            $voucherList[$k]['voucher_end_date'] = $v['voucher_start_date'] ? date('Y-m-d H:i:s', $v['voucher_end_date']) : '';
        }

        return success($voucherList);
    }

    /**
     * 添加车秘用户保险优惠劵
     */
    public function grantBaoxianCoupon ($uid, $coupons) {

        // voucher_type 1 门店 2 保险 3 停车
        // voucher_state 代金券状态(1-未用,2-已用,3-过期,4-收回,10锁定)
        // voucher_price 代金券面额
        // voucher_start_date 代金券有效期开始时间
        // voucher_end_date 代金券有效期结束时间
        // voucher_price_type 1=满减 2=立减 3=折扣满减 4=折扣立减
        // voucher_limit 消费满多少可以使用

        $rows = [];
        foreach ($coupons as $k => $v) {
            if ($v['price'] <= 0) {
                continue;
            }
            $rows[] = [
                'voucher_title' => $v['title'],
                'voucher_desc' => $v['title'],
                'voucher_type' => $v['type'],
                'voucher_price' => $v['price'],
                'voucher_price_type' => 1,
                'voucher_start_date' => mktime(0, 0, 0, date('m', TIMESTAMP), date('d', TIMESTAMP), date('Y', TIMESTAMP)),
                'voucher_end_date' => mktime(23, 59, 59, date('m', TIMESTAMP), date('d', TIMESTAMP), date('Y', TIMESTAMP) + 1),
                'voucher_limit' => 0,
                'voucher_state' => 1,
                'voucher_owner_id' => $uid,
                'operator_id' => 0
            ];
        }

        if ($rows) {
            if (!$this->getDb('chemiv2')->insert('chemi_voucher', $rows)) {
                return false;
            }
        }

        return $rows;
    }

    /**
     * 使用优惠劵
     * @param $voucher_id 优惠劵ID
     * @param $voucher_real_price 实际抵扣金额
     * @return bool
     */
    public function useVoucherInfo ($voucher_id, $voucher_real_price = 0) {

        return $this->getDb('chemiv2')->update('chemi_voucher', [
            'voucher_state' => 2, 'use_time' => TIMESTAMP, 'voucher_real_price' => $voucher_real_price
        ], ['id' => $voucher_id, 'voucher_state' => 1]);
    }

    /**
     * 获取优惠劵折扣金额
     * @param $condition 搜索条件
     * @param $total_price 待折扣金额(分)
     * @return array {"voucher_price":分}
     */
    public function getVoucherPrice ($condition, $total_price = 0) {

        if (!$voucherInfo = $this->getDb('chemiv2')
            ->table('chemi_voucher')
            ->field('voucher_id,voucher_price,voucher_price_type,voucher_start_date,voucher_end_date,voucher_limit,voucher_state')
            ->where($condition)
            ->find()) {
            return error('该优惠劵不存在');
        }

        if ($voucherInfo['voucher_state'] != 1) {
            return error('该优惠劵已使用或无效');
        }
        if (TIMESTAMP < $voucherInfo['voucher_start_date']) {
            return error('该优惠劵未到使用时间');
        }
        if (TIMESTAMP > $voucherInfo['voucher_end_date']) {
            return error('该优惠劵已过期');
        }

        $info['voucher_price'] = $voucherInfo['voucher_price'] * 100;

        // voucher_price_type 1=满减 2=立减 3=折扣满减 4=折扣立减
        // voucher_limit 消费满多少可以使用

        if ($voucherInfo['voucher_price_type'] == 1) {
            // 满减
            if ($voucherInfo['voucher_limit'] > $total_price / 100) {
                return error('满 ' . $voucherInfo['voucher_limit'] . ' 元，立减 ' . $voucherInfo['voucher_price'] . ' 元');
            }
            return success($info);
        }
        if ($voucherInfo['voucher_price_type'] == 2) {
            // 立减
            return success($info);
        }

        return error('该优惠劵不可用');
    }

    /**
     * 获取车牌号 licenseId 车秘用
     */
    private function getLicenseId($licenseNumber) {
        $dictTable=[];
        $dictTable["9"] = "19";
        $dictTable["8"] = "18";
        $dictTable["7"] = "17";
        $dictTable["6"] = "16";
        $dictTable["5"] = "15";
        $dictTable["4"] = "14";
        $dictTable["3"] = "13";
        $dictTable["2"] = "12";
        $dictTable["1"] = "11";
        $dictTable["0"] = "10";
        $dictTable["A"] = "65";
        $dictTable["B"] = "66";
        $dictTable["C"] = "67";
        $dictTable["D"] = "68";
        $dictTable["E"] = "69";
        $dictTable["F"] = "70";
        $dictTable["G"] = "71";
        $dictTable["H"] = "72";
        $dictTable["I"] = "73";
        $dictTable["J"] = "74";
        $dictTable["K"] = "75";
        $dictTable["L"] = "76";
        $dictTable["M"] = "77";
        $dictTable["N"] = "78";
        $dictTable["O"] = "79";
        $dictTable["P"] = "80";
        $dictTable["Q"] = "81";
        $dictTable["R"] = "82";
        $dictTable["S"] = "83";
        $dictTable["T"] = "84";
        $dictTable["U"] = "85";
        $dictTable["V"] = "86";
        $dictTable["W"] = "87";
        $dictTable["X"] = "88";
        $dictTable["Y"] = "89";
        $dictTable["Z"] = "90";

        $licenseId = '';
        for($i = 1; $i < mb_strlen($licenseNumber); $i ++) {
            $str = strval(mb_substr($licenseNumber, $i, 1));
            if(array_key_exists($str, $dictTable)){
                $code = $dictTable[$str];
                $licenseId .= $code . '';
            }
        }
        return $licenseId;
    }

}