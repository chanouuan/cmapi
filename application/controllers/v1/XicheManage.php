<?php

namespace controllers;

use \models\XicheManageModel;

class XicheManage extends \ActionPDO {

    public function __init ()
    {
        if (!in_array($this->_action, ['login', 'checkImgCode'])) {
            if (empty($this->_G['user'])) {
                $this->error('用户校验失败', gurl('xicheManage/login'));
            }
        }
    }

    public function __style () {
        return 'default';
    }

    public function index () {
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

        $device_info = (new XicheManageModel())->getDeviceByCode(getgpc('devcode'));

        return compact('device_info');
    }

    /**
     * 设备添加
     */
    public function deviceAdd () {
        if (submitcheck()) {
            return (new XicheManageModel())->deviceAdd($_POST);
        }

        $area_list = (new XicheManageModel())->getDevArea();
        if ($area_list['errorcode'] !== 0) {
            $this->error($area_list['message']);
        }
        $area_list = $area_list['data'];

        return compact('area_list');
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
        $userModel = new \models\UserModel();

        if ($_GET['telephone']) {
            $user_list = $userModel->getUserByBinding([
                'platform = 3',
                'tel = "' . addslashes($_GET['telephone']) . '"'
            ]);
            if ($user_list) {
                $condition[] = 'trade_id = ' . $user_list[0]['uid'];
            }
        }
        if ($_GET['devcode']) {
            $device_info = $modle->getDeviceByCode($_GET['devcode']);
            if ($device_info) {
                $condition[] = 'param_id = ' . $device_info['id'];
            }
        }

        $count = $modle->getCount('payments', $condition);
        $pagesize = getPageParams($_GET['page'], $count);
        $list = $modle->getList('payments', $condition, $pagesize['limitstr']);

        if ($list) {
            $paystatus = [
                0 => '未支付',
                1 => '已付款'
            ];
            $dev_list = $modle->getDeviceById(array_column($list, 'param_id'));
            $dev_list = array_column($dev_list, 'devcode', 'id');
            $user_list = $userModel->getUserByBinding([
                'platform = 3',
                'uid in (' . implode(',', array_column($list, 'trade_id')) . ')'
            ]);
            $user_list = array_column($user_list, 'tel', 'uid');
            foreach ($list as $k => $v) {
                $list[$k]['devcode'] = isset($dev_list[$v['param_id']]) ? $dev_list[$v['param_id']] : '';
                $list[$k]['uname'] = isset($user_list[$v['trade_id']]) ? $user_list[$v['trade_id']] : '';
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
     * 登录
     */
    public function login () {

        // 管理员白名单
        $administrator = [
            '15208666791'
        ];

        if (submitcheck()) {
            // 提交登录
            if (!in_array($_POST['telephone'], $administrator)) {
                return error('权限不足');
            }
            $model = new \models\XicheModel();
            if (!$this->checkImgCode(strval($_POST['imgcode']))) {
                return error('验证码错误');
            }
            return $model->login($_POST);
        }

        return [];
    }

}