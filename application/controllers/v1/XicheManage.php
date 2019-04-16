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
        $this->_G['user']['nickname'] = get_real_val($userList[0]['tel'], $this->_G['user']['uid']);
        return [
            'user_info' => $this->_G['user']
        ];
    }

    /**
     * home页
     */
    public function welcome () {
        $userList = (new UserModel())->getUserByBinding([
            'platform = 3',
            'uid = ' . $this->_G['user']['uid']
        ]);
        $this->_G['user']['nickname'] = get_real_val($userList[0]['tel'], $this->_G['user']['uid']);
        return [
            'user_info' => $this->_G['user']
        ];
    }

    /**
     * 套餐列表
     */
    public function item () {

        $list = (new XicheManageModel())->getList('parkwash_item', null, null, null);

        return [
            'list' => $list
        ];
    }

    /**
     * 套餐添加
     */
    public function itemAdd () {

        if (submitcheck()) {
            return (new XicheManageModel())->itemAdd($_POST);
        }

        return [];
    }

    /**
     * 套餐编辑
     */
    public function itemUpdate () {

        if (submitcheck()) {
            return (new XicheManageModel())->itemUpdate($_POST);
        }

        $info = (new XicheManageModel())->getInfo('parkwash_item', ['id' => getgpc('id')]);
        return [
            'info' => $info
        ];
    }

    /**
     * 套餐删除
     */
    public function itemDelete () {

        return (new XicheManageModel())->itemDelete(getgpc('id'));
    }

    /**
     * 门店管理
     */
    public function store () {

        $condition = [];
        if ($_GET['name']) {
            $condition['name'] = ['like', '%' . $_GET['name'] . '%'];
        }

        $modle = new XicheManageModel();
        $count = $modle->getCount('parkwash_store', $condition);
        $pagesize = getPageParams($_GET['page'], $count);
        $list = $modle->getList('parkwash_store', $condition, $pagesize['limitstr']);
        foreach ($list as $k => $v) {
            $list[$k]['logo'] = $v['logo'] ? json_decode($v['logo'], true) : [];
            $list[$k]['logo'] = $list[$k]['logo'] ? '<a onclick="x_admin_show(\'IMG\',\'' . httpurl($list[$k]['logo'][0]) . '\')" href="javascript:;" target="_blank"><img height="30" src="' . httpurl($list[$k]['logo'][0]) . '"></a>' : '';
            $list[$k]['str_status'] = $v['status'] ? '正常营业' : '建设中';
        }
        return [
            'pagesize' => $pagesize,
            'list' => $list
        ];
    }

    /**
     * 门店添加
     */
    public function storeAdd () {

        if (submitcheck()) {
            return (new XicheManageModel())->storeAdd($_POST);
        }

        $items = (new XicheManageModel())->getList('parkwash_item', null, null, null);
        return [
            'items' => $items
        ];
    }

    /**
     * 门店编辑
     */
    public function storeUpdate () {

        if (submitcheck()) {
            return (new XicheManageModel())->storeUpdate($_POST);
        }

        $model = new XicheManageModel();
        $info = $model->getInfo('parkwash_store', ['id' => getgpc('id')]);
        $info['logo'] = $info['logo'] ? json_decode($info['logo'], true) : [];
        $info['logo'] = $info['logo'] ? '<img height="30" src="' . httpurl($info['logo'][0]) . '">' : '';
        $info['time_day'] = str_split($info['time_day']);
        $items = $model->getList('parkwash_item', null, null, null);
        $storeItems = $model->getList('parkwash_store_item', ['store_id' => getgpc('id')]);
        $storeItems = array_column($storeItems, 'price', 'item_id');
        foreach ($items as $k => $v) {
            $items[$k]['price'] = isset($storeItems[$v['id']]) ? $storeItems[$v['id']] : 0;
        }
        $lastPool = $model->getInfo('parkwash_pool', ['store_id' => getgpc('id')], 'max(today) as today');
        $lastPool = strtotime($lastPool['today']);
        $lastPool = $lastPool > TIMESTAMP ? $lastPool : TIMESTAMP;
        $lastPool += 86400;
        $lastPool = date('Y-m-d', $lastPool);
        return [
            'info' => $info,
            'items' => $items,
            'lastPool' => $lastPool
        ];
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
        foreach ($list as $k => $v) {
            // 洗车时长
            $v['parameters'] = json_decode($v['parameters'], true);
            $list[$k]['duration'] = intval($v['parameters']['WashTotal']);
        }
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
     * 更新停车场洗车订单状态
     */
    public function parkOrderStatusUpdate () {
        return (new XicheManageModel())->parkOrderStatusUpdate($_POST);
    }

    /**
     * 查看停车场洗车订单详情
     */
    public function parkOrderView (){

        $modle = new XicheManageModel();
        $orderInfo = $modle->getInfo('parkwash_order', ['id' => getgpc('id')]);
        $payway = [
            'cbpay' => '车币', 'wxpayjs' => '微信', 'wxpayh5' => '微信H5', 'wxpaywash' => '微信'
        ];
        $brandInfo = $modle->getInfo('parkwash_car_brand', ['id' => $orderInfo['brand_id']], 'name');
        $seriesInfo = $modle->getInfo('parkwash_car_series', ['id' => $orderInfo['series_id']], 'name');
        $areaInfo = $modle->getInfo('parkwash_park_area', ['id' => $orderInfo['area_id']], 'floor,name');
        $storeInfo = $modle->getInfo('parkwash_store', ['id' => $orderInfo['store_id']], 'name,tel,address,order_count,money');
        $orderInfo['order_code'] = str_replace(['-', ' ', ':'], '', $orderInfo['create_time']) . $orderInfo['id'];
        $orderInfo['brand_name'] = $brandInfo['name'];
        $orderInfo['series_name'] = $seriesInfo['name'];
        $orderInfo['area_floor'] = $areaInfo['floor'];
        $orderInfo['area_name'] = $areaInfo['name'];
        $orderInfo['store_name'] = $storeInfo['name'];
        $orderInfo['store_tel'] = $storeInfo['tel'];
        $orderInfo['store_address'] = $storeInfo['address'];
        $orderInfo['store_order_count'] = $storeInfo['order_count'];
        $orderInfo['store_money'] = $storeInfo['money'];
        $orderInfo['items'] = json_decode($orderInfo['items'], true);
        $orderInfo['payway'] = isset($payway[$orderInfo['payway']]) ? $payway[$orderInfo['payway']] : $orderInfo['payway'];
        // 获取订单时序表
        $orderInfo['sequence'] = $modle->getlist('parkwash_order_sequence', ['orderid' => $orderInfo['id']], null, 'id desc', 'title,create_time');
        // 判断状态
        if ($orderInfo['status'] == 2 && $orderInfo['entry_park_id']) {
            // 等待服务
            $orderInfo['status'] = 23;
        }
        $orderInfo['status_str'] = $modle->getParkOrderStatus($orderInfo['status']);
        // 获取出入场信息
        if ($orderInfo['entry_park_id']) {
            $userModel = new UserModel();
            $entryPark = $userModel->getCheMiParkingCondition(['id' => $orderInfo['entry_park_id']], 'id,stoping_name', 1);
            $entryPark = $entryPark[0];
            $orderInfo['park_name'] = $entryPark['stoping_name'];
            // 查询出场信息
            $outPark = $userModel->getCheMiOutParkCondition([
                'license_number' => $orderInfo['car_number'], 'order_sn' => $orderInfo['entry_order_sn']
            ], 'outpark_time', 1);
            $outParkTime = $outPark ? $outPark[0]['outpark_time'] : 0;
            $orderInfo['out_park_time'] = $outParkTime ? date('Y-m-d H:i:s', $outParkTime) : '未出场/无出场信息';
        }
        return [
            'info' => $orderInfo
        ];
    }

    /**
     * 停车场洗车订单管理
     */
    public function parkOrder () {

        $condition = [
            'xc_trade_id' => 0
        ];
        if ($_GET['order_id']) {
            $condition['id'] = $_GET['order_id'];
        }
        if ($_GET['telephone']) {
            $condition['telephone'] = $_GET['telephone'];
        }
        if ($_GET['car_number']) {
            $condition['car_number'] = ['like', $_GET['car_number'] . '%'];
        }
        if ($_GET['place']) {
            $condition['place'] = ['like', $_GET['place'] . '%'];
        }
        if ($_GET['status']) {
            if ($_GET['status'] == 23) {
                // 等待服务状态
                $condition['status'] = 2;
                $condition['entry_park_id'] = ['>', 0];
            } else {
                $condition['status'] = $_GET['status'];
            }
        } else {
            $condition['status'] = ['<>', 0];
        }
        if ($_GET['start_time'] && $_GET['end_time']) {
            $condition['order_time'] = ['between', [$_GET['start_time'] . ' 00:00:00', $_GET['end_time'] . ' 23:59:59']];
        }

        $modle = new XicheManageModel();
        $count = $modle->getCount('parkwash_order', $condition);
        $pagesize = getPageParams($_GET['page'], $count);
        $list = $modle->getList('parkwash_order', $condition, $pagesize['limitstr'], 'id desc', 'id,entry_park_id,entry_park_time,store_id,create_time,car_number,brand_id,series_id,user_tel,order_time,area_id,place,items,pay,status');

        if ($list) {
            $brandList = $modle->getList('parkwash_car_brand', ['id' => ['in', array_column($list, 'brand_id')]], null, null, 'id,name');
            $brandList = array_column($brandList, null, 'id');
            $seriesList = $modle->getList('parkwash_car_series', ['id' => ['in', array_column($list, 'series_id')]], null, null, 'id,name');
            $seriesList = array_column($seriesList, null, 'id');
            $areaList = $modle->getList('parkwash_park_area', ['id' => ['in', array_column($list, 'area_id')]], null, null, 'id,floor,name');
            $areaList = array_column($areaList, null, 'id');
            $storeList = $modle->getList('parkwash_store', ['id' => ['in', array_column($list, 'store_id')]], null, null, 'id,name');
            $storeList = array_column($storeList, null, 'id');
            $entryPark = [];
            foreach ($list as $k => $v) {
                $list[$k]['create_time'] = substr($v['create_time'], 0, -3);
                $list[$k]['order_time'] = substr($v['order_time'], 0, -3);
                $list[$k]['car_name'] = $brandList[$v['brand_id']]['name'] . ' ' . $seriesList[$v['series_id']]['name'];
                $list[$k]['area_floor'] = $areaList[$v['area_id']]['floor'];
                $list[$k]['area_name'] = $areaList[$v['area_id']]['name'];
                $list[$k]['store_name'] = $storeList[$v['store_id']]['name'];
                $list[$k]['items'] = implode(',', array_column(json_decode($v['items'], true), 'name'));
                $list[$k]['pay'] = round_dollar($v['pay'], false);
                // 判断等待服务状态
                if ($v['status'] == 2 && $v['entry_park_id']) {
                    $list[$k]['status'] = 23; // 等待服务
                    if ($v['entry_park_id']) {
                        $entryPark[] = $v['entry_park_id'];
                    }
                }
                $list[$k]['status_str'] = $modle->getParkOrderStatus($list[$k]['status']);
            }
            if ($entryPark) {
                $entryPark = (new UserModel())->getCheMiParkingCondition(['id' => ['in', $entryPark]], 'id,stoping_name');
                $entryPark = array_column($entryPark, null, 'id');
                foreach ($list as $k => $v) {
                    if ($v['status'] == 23) {
                        $list[$k]['entry_park'] = '「' . $v['entry_park_time'] . '」进入' . $entryPark[$v['entry_park_id']]['stoping_name'];
                    }
                }
            }
            unset($brandList, $seriesList, $areaList, $storeList, $entryPark);
        }

        return [
            'pagesize' => $pagesize,
            'list' => $list,
            'dateTime' => $modle->getSearchDateTime(),
            'statusList' => $modle->getParkOrderStatus()
        ];
    }

    /**
     * 自助洗车订单管理
     */
    public function xicheOrder () {
        $condition = [
            'type = "xc"'
        ];
        $modle = new XicheManageModel();
        $userModel = new UserModel();

        if ($_GET['telephone']) {
            $userInfo = $userModel->getUserInfoCondition([
                'member_name' => $_GET['telephone']
            ]);
            if ($userInfo) {
                $condition[] = 'trade_id = ' . $userInfo['member_id'];
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
     * 会员管理
     */
    public function user () {
        $modle = new XicheManageModel();
        $userModel = new UserModel();

        $condition = [];
        if ($_GET['telephone']) {
            $userInfo = $userModel->getUserInfoCondition([
                'member_name' => $_GET['telephone']
            ]);
            $condition['uid'] = $userInfo ? $userInfo['member_id'] : 0;
        }
        if ($_GET['start_time'] && $_GET['end_time']) {
            $condition['create_time'] = ['between', [$_GET['start_time'] . ' 00:00:00', $_GET['end_time'] . ' 23:59:59']];
        }

        $count = $modle->getCount('parkwash_usercount', $condition);
        $pagesize = getPageParams($_GET['page'], $count);
        $list = $modle->getList('parkwash_usercount', $condition, $pagesize['limitstr'], 'uid desc');
        if ($list) {
            $cmUserList = $userModel->getUserList(['member_id' => ['in', array_column($list, 'uid')]], 'member_id,member_name,available_predeposit');
            $cmUserList = array_column($cmUserList, null, 'member_id');
            foreach ($list as $k => $v) {
                $list[$k]['telephone'] = $cmUserList[$v['uid']]['member_name'];
                $list[$k]['money'] = $cmUserList[$v['uid']]['available_predeposit'];
            }
            unset($cmUserList);
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
