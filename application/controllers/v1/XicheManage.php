<?php

namespace app\controllers;

use ActionPDO;
use app\models\XicheManageModel;
use app\models\UserModel;

class XicheManage extends ActionPDO {

    public function __init ()
    {
        if (!in_array($this->_action, ['login', 'checkImgCode'])) {
            $this->_G['user'] = $this->loginCheck();
            if (empty($this->_G['user'])) {
                $this->error('用户校验失败', gurl('xicheManage/login'));
            }
        }
    }

    public function __style () {
        if ($this->_action == 'login') {
            return CLIENT_TYPE == 'pc' ? 'default' : 'mobile';
        }
        return 'default';
    }

    public function index () {
        $userList = (new UserModel())->getUserByBinding([
            'platform = 3',
            'uid = ' . $this->_G['user']['uid']
        ]);
        if ($userList) {
            $this->_G['user']['telephone'] = $userList[0]['tel'];
        }
        return [
            'user_info' => $this->_G['user']
        ];
    }

    /**
     * home页
     */
    public function welcome () {
        return [];
    }

    /**
     * 获取设备列表
     */
    public function getDev () {
        return (new XicheManageModel())->getDev(getgpc('AreaId'));
    }

    /**
     * 编辑设备
     */
    public function deviceUpdate () {
        if (submitcheck()) {
            return (new XicheManageModel())->deviceUpdate($_POST);
        }

        $deviceInfo = (new XicheManageModel())->getDeviceByCode(getgpc('devcode'));

        return ['device_info' => $deviceInfo];
    }

    /**
     * 设备添加
     */
    public function deviceAdd () {
        if (submitcheck()) {
            return (new XicheManageModel())->deviceAdd($_POST);
        }

        $areaList = (new XicheManageModel())->getDevArea();
        if ($areaList['errorcode'] !== 0) {
            $this->error($areaList['message']);
        }
        $areaList = $areaList['result'];

        return ['area_list' => $areaList];
    }

    /**
     * 同步设备参数
     */
    public function deviceSync () {
        return (new XicheManageModel())->deviceSync($_POST);
    }

    /**
     * 设备参数详情
     */
    public function deviceParamInfo () {
        $modle = new XicheManageModel();
        if (!$devInfo = $modle->getDeviceById(getgpc('id'))) {
            return error('参数错误');
        }

        $this->render('XicheManage/view.html', [
            'parameters' => print_r(json_decode($devInfo['parameters'],true),true)
        ]);
    }

    /**
     * 设备管理
     */
    public function device () {
        $condition = [];
        if ($_GET['devcode']) {
            $condition[] = 'devcode = "' . addslashes($_GET['devcode']) . '"';
        }

        $modle = new XicheManageModel();
        $count = $modle->getCount('xiche_device', $condition);
        $pagesize = getPageParams($_GET['page'], $count);
        $list = $modle->getList('xiche_device', $condition, $pagesize['limitstr']);

        return [
            'pagesize' => $pagesize,
            'list' => $list,
            'stat' => [
                0 => '空闲',
                1 => '投币洗车',
                2 => '刷卡洗车',
                3 => '微信洗车',
                4 => '停售',
                5 => '手机号洗车',
                6 => '会员扫码洗车',
                7 => '缺泡沫'
            ]
        ];
    }

    /**
     * 订单管理
     */
    public function order () {
        $condition = [
            'type = "xc"'
        ];
        $modle = new XicheManageModel();
        $userModel = new UserModel();

        if ($_GET['telephone']) {
            $userList = $userModel->getUserByBinding([
                'platform = 3',
                'tel = "' . addslashes($_GET['telephone']) . '"'
            ]);
            if ($userList) {
                $condition[] = 'trade_id = ' . $userList[0]['uid'];
            }
        }
        if ($_GET['devcode']) {
            $deviceInfo = $modle->getDeviceByCode($_GET['devcode']);
            if ($deviceInfo) {
                $condition[] = 'param_id = ' . $deviceInfo['id'];
            }
        }
        if ($_GET['ordercode']) {
            $condition[] = 'ordercode like "' . addslashes($_GET['ordercode']) . '%"';
        }

        $count = $modle->getCount('payments', $condition);
        $pagesize = getPageParams($_GET['page'], $count);
        $list = $modle->getList('payments', $condition, $pagesize['limitstr']);

        if ($list) {
            $paystatus = [
                0 => '未支付',
                1 => '已付款'
            ];
            $devList = $modle->getDeviceById(array_column($list, 'param_id'));
            $devList = array_column($devList, 'devcode', 'id');
            $userList = $userModel->getUserByBinding([
                'platform = 3',
                'uid in (' . implode(',', array_column($list, 'trade_id')) . ')'
            ]);
            $userList = array_column($userList, 'tel', 'uid');
            foreach ($list as $k => $v) {
                $list[$k]['devcode'] = isset($devList[$v['param_id']]) ? $devList[$v['param_id']] : '';
                $list[$k]['uname'] = isset($userList[$v['trade_id']]) ? $userList[$v['trade_id']] : '';
                $list[$k]['paystatus'] = $paystatus[$v['status']];
                $list[$k]['param_a'] = $v['param_a'] ? date('Y-m-d H:i:s', $v['param_a']) : '';
                $list[$k]['param_b'] = $v['param_b'] ? date('Y-m-d H:i:s', $v['param_b']) : '';
                $list[$k]['money'] = round_dollar($v['money'], false);
                $list[$k]['refundpay'] = $v['refundpay'] ? round_dollar($v['refundpay'], false) : '';
            }
        }

        return [
            'pagesize' => $pagesize,
            'list' => $list
        ];
    }

    /**
     * 日志详情
     */
    public function logInfo () {
        $modle = new XicheManageModel();
        if (!$logInfo = $modle->getLogInfo(getgpc('id'))) {
            return error('参数错误');
        }

        $this->render('XicheManage/view.html', [
            'parameters' => print_r(json_decode($logInfo['content'],true),true)
        ]);
    }

    /**
     * 日志删除
     */
    public function logDelete () {
        return (new XicheManageModel())->logDelete(getgpc('id'));
    }

    /**
     * 日志管理
     */
    public function log () {
        $condition = [];
        if ($_GET['uid']) {
            $condition[] = 'uid = ' . intval($_GET['uid']);
        }
        if ($_GET['devcode']) {
            $condition[] = 'devcode = "' . addslashes($_GET['devcode']) . '"';
        }
        if ($_GET['orderno']) {
            $condition[] = 'orderno = "' . addslashes($_GET['orderno']) . '"';
        }

        $modle = new XicheManageModel();
        $count = $modle->getCount('xiche_log', $condition);
        $pagesize = getPageParams($_GET['page'], $count);
        $list = $modle->getList('xiche_log', $condition, $pagesize['limitstr']);

        return [
            'pagesize' => $pagesize,
            'list' => $list
        ];
    }

    /**
     * 系统配置
     */
    public function config () {

        $list = (new XicheManageModel())->getList('config', ['app' => 'xc'], null);
        return compact('list');
    }

    /**
     * 编辑配置
     */
    public function configUpdate () {
        if (submitcheck()) {
            return (new XicheManageModel())->configUpdate($_POST);
        }

        $info = (new XicheManageModel())->getConfigInfo(getgpc('id'));
        return compact('info');
    }

    /**
     * 登录
     */
    public function login () {

        // 提交登录
        if (submitcheck()) {

            // 管理员白名单
            $administrator = [
                '15208666791'
            ];
            $config = getConfig('xc', 'admin');
            $config = $config ? explode("\n", $config) : [];
            $administrator = array_merge($administrator, $config);

            if (!in_array($_POST['telephone'], $administrator)) {
                return error('权限不足');
            }

            if (!$this->checkImgCode(strval($_POST['imgcode']))) {
                return error('验证码错误');
            }

            $model =  new UserModel();
            $userInfo = $model->getUserInfoCondition([
                    'member_name'=> $_POST['telephone']
                ], 'member_id,member_passwd');

            if ($userInfo['member_passwd'] != md5(md5($_POST['password']))) {
                return error('用户名或密码错误！');
            }

            // 登录成功
            $loginret = $model->setloginstatus($userInfo['member_id'], uniqid());
            if ($loginret['errorcode'] !== 0) {
                return $loginret;
            }

            return success('OK');
        }

        return [];
    }

    /**
     * 登出
     */
    public function logout () {
        (new UserModel())->logout($this->_G['user']['uid']);
        $this->success('登出成功', gurl('xicheManage/login'), 0);
    }

}
