<?php

namespace app\controllers;

use ActionPDO;
use app\models\XicheManageModel;
use app\models\UserModel;

class XicheManage extends ActionPDO {

    public function __init ()
    {
        if (!in_array($this->_action, ['login', 'checkImgCode', 'orderAlert', 'noticeAlert'])) {
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
        return [
            'info' => $info,
            'items' => $items
        ];
    }

    /**
     * 车位状态管理
     */
    public function parking () {
        $condition = [];
        if ($_GET['place']) {
            $condition['place'] = ['like', '%' . $_GET['place'] . '%'];
        }

        $modle = new XicheManageModel();
        $count = $modle->getCount('parkwash_parking', $condition);
        $pagesize = getPageParams($_GET['page'], $count);
        $list = $modle->getList('parkwash_parking', $condition, $pagesize['limitstr']);
        if ($list) {
            $areaList = $modle->getList('parkwash_park_area', [
                'id' => ['in', array_column($list, 'area_id')]
            ]);
            $areaList = array_column($areaList, null, 'id');
            foreach ($list as $k => $v) {
                $list[$k]['area_floor'] = $areaList[$v['area_id']]['floor'];
                $list[$k]['area_name'] = $areaList[$v['area_id']]['name'];
            }
        }

        return [
            'pagesize' => $pagesize,
            'list' => $list
        ];
    }

    /**
     * 车位状态添加
     */
    public function parkingAdd () {
        if (submitcheck()) {
            return (new XicheManageModel())->parkingAdd($_POST);
        }
        $modle = new XicheManageModel();
        $areaList = $modle->getList('parkwash_park_area', ['status' => 1]);
        return compact('areaList');
    }

    /**
     * 车位状态编辑
     */
    public function parkingUpdate () {
        if (submitcheck()) {
            return (new XicheManageModel())->parkingUpdate($_POST);
        }

        $model = new XicheManageModel();
        $info = $model->getInfo('parkwash_parking', ['id' => getgpc('id')]);
        $areaList = $model->getList('parkwash_park_area', ['status' => 1]);
        return compact('info', 'areaList');
    }

    /**
     * 车位状态删除
     */
    public function parkingDelete () {
        return (new XicheManageModel())->parkingDelete(getgpc('id'));
    }

    /**
     * 车位区域管理
     */
    public function area () {
        $condition = [];
        if ($_GET['name']) {
            $condition['name'] = ['like', '%' . $_GET['name'] . '%'];
        }

        $model = new XicheManageModel();
        $count = $model->getCount('parkwash_park_area', $condition);
        $pagesize = getPageParams($_GET['page'], $count);
        $list = $model->getList('parkwash_park_area', $condition, $pagesize['limitstr']);

        return [
            'pagesize' => $pagesize,
            'list' => $list
        ];
    }

    /**
     * 车位区域添加
     */
    public function areaAdd () {
        if (submitcheck()) {
            return (new XicheManageModel())->areaAdd($_POST);
        }

        return [];
    }

    /**
     * 车位区域添加
     */
    public function areaUpdate () {
        if (submitcheck()) {
            return (new XicheManageModel())->areaUpdate($_POST);
        }

        $model = new XicheManageModel();
        $info = $model->getInfo('parkwash_park_area', ['id' => getgpc('id')]);
        return compact('info');
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
     * 获取车辆入场信息
     */
    public function entryParkInfo () {
        return (new XicheManageModel())->entryParkInfo(getgpc('id'));
    }

    /**
     * 查看停车场洗车订单详情
     */
    public function parkOrderView (){

        $modle = new XicheManageModel();
        $orderInfo = $modle->getInfo('parkwash_order', ['id' => getgpc('id')]);
        $payway = [
            'cbpay' => '车币', 'wxpayjs' => '微信', 'wxpayh5' => '微信H5', 'wxpaywash' => '微信', 'vippay' => '洗车VIP', 'firstpay' => '首单免费'
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
        if ($orderInfo['status'] == 1 && $orderInfo['entry_park_id']) {
            // 等待服务
            $orderInfo['status'] = 13;
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

        $modle = new XicheManageModel();
        $condition = [
            'xc_trade_id' => 0
        ];
        if ($_GET['store_name']) {
            $searchStoreInfo = $modle->getInfo('parkwash_store', ['name' => ['like', '%' . $_GET['store_name'] . '%']], 'id');
            $condition['store_id'] = intval($searchStoreInfo['id']);
        }
        if ($_GET['order_id']) {
            $condition['id'] = intval($_GET['order_id']);
        }
        if ($_GET['user_tel']) {
            $condition['user_tel'] = ['like', $_GET['user_tel'] . '%'];
        }
        if ($_GET['car_number']) {
            $condition['car_number'] = ['like', $_GET['car_number'] . '%'];
        }
        if ($_GET['place']) {
            $condition['place'] = ['like', $_GET['place'] . '%'];
        }
        if ($_GET['payway']) {
            $condition['payway'] = $_GET['payway'];
        }
        if ($_GET['status']) {
            if ($_GET['status'] == 13) {
                // 等待服务状态
                $condition['status'] = 1;
                $condition['entry_park_id'] = ['>', 0];
            } else if ($_GET['status'] == 45) {
                // 异常订单
                $condition['status'] = ['in', [4,5]];
                $condition['fail_reason'] = ['<>', ''];
            } else {
                $condition['status'] = $_GET['status'];
                $condition['fail_reason'] = '';
            }
        } else {
            $condition['status'] = ['<>', 0];
        }
        if ($_GET['start_time'] && $_GET['end_time']) {
            $condition['order_time'] = ['between', [$_GET['start_time'] . ' 00:00:00', $_GET['end_time'] . ' 23:59:59']];
        }

        $count = $modle->getCount('parkwash_order', $condition);
        $pagesize = getPageParams($_GET['page'], $count);
        $list = $modle->getList('parkwash_order', $condition, $pagesize['limitstr'], 'id desc', 'id,entry_park_id,entry_park_time,store_id,create_time,car_number,brand_id,series_id,user_tel,order_time,area_id,place,items,pay,payway,status,fail_reason');

        if ($list) {
            $payway = [
                'cbpay' => '车币', 'wxpayjs' => '微信', 'wxpayh5' => '微信H5', 'wxpaywash' => '微信', 'vippay' => '洗车VIP', 'firstpay' => '首单免费'
            ];
            $brandList = $modle->getList('parkwash_car_brand', ['id' => ['in', array_column($list, 'brand_id')]], null, null, 'id,name');
            $brandList = array_column($brandList, null, 'id');
            $seriesList = $modle->getList('parkwash_car_series', ['id' => ['in', array_column($list, 'series_id')]], null, null, 'id,name');
            $seriesList = array_column($seriesList, null, 'id');
            $areaList = array_filter(array_column($list, 'area_id'));
            if ($areaList) {
                $areaList = $modle->getList('parkwash_park_area', ['id' => ['in', array_column($list, 'area_id')]], null, null, 'id,floor,name');
                $areaList = array_column($areaList, null, 'id');
            }
            $storeList = $modle->getList('parkwash_store', ['id' => ['in', array_column($list, 'store_id')]], null, null, 'id,name');
            $storeList = array_column($storeList, null, 'id');
            foreach ($list as $k => $v) {
                $list[$k]['create_time'] = substr($v['create_time'], 0, -3);
                $list[$k]['order_time'] = substr($v['order_time'], 0, -3);
                $list[$k]['car_name'] = $brandList[$v['brand_id']]['name'] . ' ' . $seriesList[$v['series_id']]['name'];
                $list[$k]['area_floor'] = isset($areaList[$v['area_id']]) ? $areaList[$v['area_id']]['floor'] : '';
                $list[$k]['area_name'] = isset($areaList[$v['area_id']]) ? $areaList[$v['area_id']]['name'] : '';
                $list[$k]['store_name'] = $storeList[$v['store_id']]['name'];
                $list[$k]['items'] = implode(',', array_column(json_decode($v['items'], true), 'name'));
                $list[$k]['pay'] = round_dollar($v['pay'], false);
                $list[$k]['payway'] = isset($payway[$v['payway']]) ? $payway[$v['payway']] : $v['payway'];
                // 判断等待服务状态
                if ($v['status'] == 1 && $v['entry_park_id']) {
                    $list[$k]['status'] = 13; // 等待服务
                }
                if (($v['status'] == 4 || $v['status'] == 5) && $v['fail_reason']) {
                    $list[$k]['status'] = 45; // 异常订单
                }
                $list[$k]['status_str'] = $modle->getParkOrderStatus($list[$k]['status']);
            }
            unset($brandList, $seriesList, $areaList, $storeList);
        }

        return [
            'pagesize' => $pagesize,
            'list' => $list,
            'dateTime' => $modle->getSearchDateTime(),
            'statusList' => $modle->getParkOrderStatus()
        ];
    }

    /**
     * 订单提醒
     */
    public function orderAlert () {

        \DebugLog::_debug(false);
        // 没有填写车位的订单数量
        $noPlaceCount = \app\library\DB::getInstance()
            ->table('parkwash_order')
            ->where([
                'order_time' => ['between', [date('Y-m-d H:i:s', TIMESTAMP - 1800), date('Y-m-d H:i:s', TIMESTAMP + 600)]],
                'status' => 1,
                'place' => ''
            ])
            ->count();

        return success([
            'noPlaceCount' => $noPlaceCount
        ]);
    }

    /**
     * 商家通知
     */
    public function noticeAlert () {

        \DebugLog::_debug(false);
        $noticeList = \app\library\DB::getInstance()
            ->table('parkwash_notice')
            ->where([
                'receiver' => 2, 'notice_type' => 2, 'is_read' => 0, 'create_time' => ['>', date('Y-m-d', TIMESTAMP)]
            ])
            ->field('id,title,content')
            ->select();
        if ($noticeList) {
            \app\library\DB::getInstance()->update('parkwash_notice', ['is_read' => 1], [
                'id' => ['in', array_column($noticeList, 'id')]
            ]);
            $noticeData = [];
            $audioPath = [
                'create' => APPLICATION_URL . '/static/audio/create.mp3',
                'updatePlace' => APPLICATION_URL . '/static/audio/entryCar.mp3',
                'entryCar' => APPLICATION_URL . '/static/audio/entryCar.mp3'
            ];
            foreach ($noticeList as $k => $v) {
                if (isset($audioPath[$v['content']])) {
                    $noticeData[$v['title']]['title'] = $v['title'];
                    $noticeData[$v['title']]['audio'] = $audioPath[$v['content']];
                    $noticeData[$v['title']]['num'] ++;
                }
            }
            unset($noticeList);
        }
        return success([
            'noticeData' => $noticeData
        ]);
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
     * 缴费记录
     */
    public function cardRecord () {

        $modle = new XicheManageModel();

        $condition = [];
        if ($_GET['uid']) {
            $condition['uid'] = intval($_GET['uid']);
        }
        if ($_GET['user_tel']) {
            $condition['user_tel'] = ['like', $_GET['user_tel'] . '%'];
        }
        if ($_GET['car_number']) {
            $condition['car_number'] = ['like', '%' . $_GET['car_number'] . '%'];
        }
        if ($_GET['card_type_id']) {
            $condition['card_type_id'] = intval($_GET['card_type_id']);
        }
        if ($_GET['start_time'] && $_GET['end_time']) {
            $condition['create_time'] = ['between', [$_GET['start_time'] . ' 00:00:00', $_GET['end_time'] . ' 23:59:59']];
        }

        $row = \app\library\DB::getInstance()
            ->table('parkwash_card_record')
            ->field('count(*) as count,sum(money) as money')
            ->where($condition)
            ->find();
        $count = $row['count'];
        $totalMoney = $row['money'];
        $pagesize = getPageParams($_GET['page'], $count);
        $list = $modle->getList('parkwash_card_record', $condition, $pagesize['limitstr']);
        $cardType = $modle->getList('parkwash_card_type', null, null, 'sort desc');
        $cardType = array_column($cardType, 'name', 'id');

        foreach ($list as $k => $v) {
            $list[$k]['card_type_name'] = isset($cardType[$v['card_type_id']]) ? $cardType[$v['card_type_id']] : $v['card_type_id'];
        }

        return [
            'pagesize' => $pagesize,
            'list' => $list,
            'cardType' => $cardType,
            'totalMoney' => round_dollar($totalMoney)
        ];
    }

    /**
     * 卡类型管理
     */
    public function cardType () {
        $modle = new XicheManageModel();
        $list = $modle->getList('parkwash_card_type', null, null, 'sort desc');
        return [
            'list' => $list
        ];
    }

    /**
     * 卡类型添加
     */
    public function cardTypeAdd () {

        if (submitcheck()) {
            return (new XicheManageModel())->cardTypeAdd($_POST);
        }

        return [];
    }

    /**
     * 卡类型编辑
     */
    public function cardTypeUpdate () {

        if (submitcheck()) {
            return (new XicheManageModel())->cardTypeUpdate($_POST);
        }

        $info = (new XicheManageModel())->getInfo('parkwash_card_type', ['id' => getgpc('id')]);
        return [
            'info' => $info
        ];
    }

    /**
     * 会员管理
     */
    public function user () {
        $modle = new XicheManageModel();
        $userModel = new UserModel();

        $condition = [];
        if ($_GET['status']) {
            if ($_GET['status'] == 1) {
                // 普通用户
                $condition['vip_expire'] = null;
            } else if ($_GET['status'] == 2) {
                // 会员用户
                $condition['vip_expire'] = ['>', date('Y-m-d H:i:s', TIMESTAMP)];
            } else if ($_GET['status'] == 3) {
                // 过期会员
                $condition['vip_expire'] = ['<', date('Y-m-d H:i:s', TIMESTAMP)];
            }
        }
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
        $list = $modle->getList('parkwash_usercount', $condition, $pagesize['limitstr'], 'create_time desc');
        if ($list) {
            $cmUserList = $userModel->getUserList(['member_id' => ['in', array_column($list, 'uid')]], 'member_id,member_name,available_predeposit');
            $cmUserList = array_column($cmUserList, null, 'member_id');
            foreach ($list as $k => $v) {
                $list[$k]['telephone'] = isset($cmUserList[$v['uid']]) ? $cmUserList[$v['uid']]['member_name'] : '已删';
                $list[$k]['money'] = isset($cmUserList[$v['uid']]) ? $cmUserList[$v['uid']]['available_predeposit'] : '已删';
                $list[$k]['isvip'] = $v['vip_expire'] ? (strtotime($v['vip_expire']) > TIMESTAMP ? '是' : '已过期') : '否';
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
