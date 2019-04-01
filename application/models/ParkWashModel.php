<?php

namespace models;

use library\Crud;
use library\LocationUtils;
use library\Geohash;
use library\Cache;

class ParkWashModel extends Crud {

    /**
     * 获取通知列表
     */
    public function getNoticeList ($uid, $post) {

        // 最后排序字段
        $post['lastpage'] = intval($post['lastpage']);

        // 结果返回
        $result = [
            'limit' => 10,
            'lastpage' => '',
            'list' => []
        ];

        $condition = [
            'receiver' => 1,
            'uid' => $uid,
        ];

        if ($post['lastpage'] > 0) {
            $condition['id'] = ['<', $post['lastpage']];
        }

        // 获取通知
        if (!$noticeList = $this->getDb()->table('parkwash_notice')->field('id,title,content,is_read,create_time')->where($condition)->order('id desc')->limit($result['limit'])->select()) {
            return success($result);
        }

        $reads = [];
        foreach ($noticeList as $k => $v) {
            $result['lastpage'] = $v['id'];
            if (!$v['is_read']) {
                $reads[] = $v['id'];
            }
        }

        // 更新已读状态
        if ($reads) {
            $this->getDb()->update('parkwash_notice', [
                'is_read' => 1
            ], [
                'id' => ['in', $reads]
            ]);
        }

        $result['list'] = $noticeList;
        unset($noticeList);
        return success($result);
    }

    /**
     * 获取用户信息
     */
    public function getUserInfo ($uid) {
        return (new UserModel())->getUserInfo($uid);
    }

    /**
     * 保存 userCount
     */
    public function saveUserCount ($uid) {
        if ($uid) {
            if (!$this->getDb()->table('parkwash_usercount')->where(['uid' => $uid])->count()) {
                return $this->getDb()->insert('parkwash_usercount', [
                    'uid' => $uid, 'create_time' => date('Y-m-d H:i:s', TIMESTAMP)
                ]);
            }
        }
        return true;
    }

    /**
     * 编辑车辆
     */
    public function updateCarport ($uid, $post) {

        $post['brand_id'] = intval($post['brand_id']);
        $post['series_id'] = intval($post['series_id']);
        $post['area_id'] = intval($post['area_id']);
        $post['isdefault'] = $post['isdefault'] == 1 ? 1 : 0;

        if (!check_car_license($post['car_number'])) {
            return error('车牌号错误');
        }
        if ($post['place'] && strlen($post['place']) > 10) {
            return error('车位号最多10个字符');
        }
        if (!$brandInfo = $this->getDb()->table('parkwash_car_brand')->field('name')->where(['id' => $post['brand_id']])->find()) {
            return error('品牌为空或不存在');
        }
        if (!$seriesInfo = $this->getDb()->table('parkwash_car_series')->field('name')->where(['id' => $post['series_id'], 'brand_id' => $post['brand_id']])->find()) {
            return error('车系为空或不存在');
        }
        if ($post['area_id']) {
            if (!$this->getDb()->table('parkwash_park_area')->where(['id' => $post['area_id']])->count()) {
                return error('区域为空或不存在');
            }
        }

        // 编辑增车
        if (!$this->saveCarport($uid, [
            'id' => $post['id'], 'car_number' => $post['car_number'], 'brand_id' => $post['brand_id'], 'series_id' => $post['series_id'], 'area_id' => $post['area_id'], 'name' => $brandInfo['name'] . ' ' . $seriesInfo['name'], 'place' => $post['place'], 'isdefault' => $post['isdefault']
        ])) {
            return error('更新车辆失败，请检查该是否存在相同车牌！');
        }

        return success('OK');
    }

    /**
     * 更新车辆
     */
    public function saveCarport ($uid, $post) {

        if (!$post['id']) {
            return false;
        }

        // 获取车辆全称
        if ($post['brand_id'] && $post['series_id'] && !$post['name']) {
            $brandInfo = $this->getDb()->table('parkwash_car_brand')->field('name')->where(['id' => $post['brand_id']])->find();
            $seriesInfo = $this->getDb()->table('parkwash_car_series')->field('name')->where(['id' => $post['series_id']])->find();
            $post['name'] = $brandInfo['name'] . ' ' . $seriesInfo['name'];
        }

        // 车位为 null 就不更新
        if (!isset($post['place'])) {
            unset($post['place']);
        }

        $post['update_time'] = date('Y-m-d H:i:s', TIMESTAMP);

        if (!$this->getDb()->update('parkwash_carport', $post, [
            'id' => $post['id'], 'uid' => $uid
        ])) {
            return false;
        }

        // 更新默认车
        if ($post['isdefault']) {
            $this->getDb()->update('parkwash_carport', ['isdefault' => 0], ['uid' => $uid, 'id' => ['<>', $post['id']]]);
        }

        return true;
    }

    /**
     * 删除车辆
     */
    public function deleteCarport ($uid, $post) {

        if (!$this->getDb()->delete('parkwash_carport', [
            'id' => $post['id'], 'uid' => $uid
        ])) {
            return error('删除车辆失败');
        }

        // 更新默认车
        if ($carportInfo = $this->getDb()->table('parkwash_carport')->field('id')->where(['uid' => $uid])->order('id desc')->limit(1)->find()) {
            $this->getDb()->update('parkwash_carport', ['isdefault' => 0], ['uid' => $uid]);
            $this->getDb()->update('parkwash_carport', ['isdefault' => 1], ['id' => $carportInfo['id']]);
        }

        return success('OK');
    }

    /**
     * 添加车辆
     */
    public function addCarport ($uid, $post) {

        $post['brand_id'] = intval($post['brand_id']);
        $post['series_id'] = intval($post['series_id']);

        if (!check_car_license($post['car_number'])) {
            return error('车牌号错误');
        }
        if (!$brandInfo = $this->getDb()->table('parkwash_car_brand')->field('name')->where(['id' => $post['brand_id']])->find()) {
            return error('品牌为空或不存在');
        }
        if (!$seriesInfo = $this->getDb()->table('parkwash_car_series')->field('name')->where(['id' => $post['series_id'], 'brand_id' => $post['brand_id']])->find()) {
            return error('车系为空或不存在');
        }

        // 限制添加数
        if ($this->getDb()->table('parkwash_carport')->where(['uid' => $uid])->count() >= 5) {
            return error('每个用户最多添加 5 辆车信息');
        }

        // 新增车
        if (!$this->getDb()->insert('parkwash_carport', [
            'uid' => $uid, 'car_number' => $post['car_number'], 'brand_id' => $post['brand_id'], 'series_id' => $post['series_id'], 'name' => $brandInfo['name'] . ' ' . $seriesInfo['name'], 'isdefault' => 1, 'create_time' => date('Y-m-d H:i:s', TIMESTAMP), 'update_time' => date('Y-m-d H:i:s', TIMESTAMP)
        ])) {
            return error('添加车辆失败，请检查该是否存在相同车牌！');
        }

        // 更新默认车
        $this->getDb()->update('parkwash_carport', ['isdefault' => 0], ['uid' => $uid, 'id' => ['<>', $this->getDb()->getlastid()]]);

        return success('OK');
    }

    /**
     * 获取我的车辆
     */
    public function getCarport ($uid) {

        if (!$carportList = $this->getDb()->table('parkwash_carport')
            ->field('id,car_number,brand_id,series_id,area_id,place,name,isdefault')->where(['uid' => $uid])->order('id desc')->select()) {
            return success([]);
        }

        $brandList = $this->getBrandList();
        $brandList = $brandList['result'];
        $brandList = array_column($brandList, null, 'id');
        $seriesList = $this->getDb()->table('parkwash_car_series')->field('id,name')->where(['id' => ['in', array_unique(array_column($carportList, 'series_id'))]])->select();
        $seriesList = array_column($seriesList, null, 'id');
        $areaList = $this->getDb()->table('parkwash_park_area')->field('id,floor,area')->where(['id' => ['in', array_unique(array_column($carportList, 'area_id'))]])->select();
        $areaList = array_column($areaList, null, 'id');

        foreach ($carportList as $k => $v) {
            $carportList[$k]['brand_name'] = $brandList[$v['brand_id']]['name'];
            $carportList[$k]['brand_logo'] = $brandList[$v['brand_id']]['logo'];
            $carportList[$k]['series_name'] = $seriesList[$v['series_id']]['name'];
            $carportList[$k]['area_floor'] = $areaList[$v['area_id']]['floor'];
            $carportList[$k]['area_name'] = $areaList[$v['area_id']]['area'];
        }

        unset($brandList, $seriesList, $areaList);

        return success($carportList);
    }

    /**
     * 获取洗车店洗车套餐
     */
    public function getStoreItem ($post) {

        $post['store_id'] = intval($post['store_id']);

        if (!$itemList = $this->getDb()
            ->table('parkwash_store_item store_item')
            ->join('join parkwash_item item on item.id = store_item.item_id')
            ->field('item.id,item.name,store_item.price')->where(['store_item.store_id' => $post['store_id']])->select()) {
            return success([]);
        }

        return success($itemList);
    }

    /**
     * 获取预约排班
     */
    public function getPoolList ($post) {

        $post['store_id'] = intval($post['store_id']);

        $condition = [
            'store_id' => $post['store_id'],
            'today' => ['BETWEEN', [date('Y-m-d', TIMESTAMP), date('Y-m-d', TIMESTAMP + 2 * 86400)]]
        ];

        if (!$poolList = $this->getDb()->table('parkwash_pool')->field('id,today,left(start_time,5) as start_time,left(end_time,5) as end_time,amount')->where($condition)->select()) {
            return success([]);
        }

        foreach ($poolList as $k => $v) {
            // 去掉已过期的排班
            if (strtotime($v['today'] . ' ' . $v['end_time']) < TIMESTAMP) {
                unset($poolList[$k]);
            }
        }

        return success(array_values($poolList));
    }

    /**
     * 获取停车场区域
     */
    public function getParkArea ($post) {

        $post['park_id'] = get_real_val($post['park_id'], 1);

        $list = $this->getDb()->table('parkwash_park_area')->field('id,floor,area')->where(['park_id' => $post['park_id']])->select();

        return success($list);
    }

    /**
     * 检查停车位是否支持洗车服务
     * @param $area_id 区域ID
     * @param $place 车位号
     * @return bool
     */
    public function checkParkingState ($area_id, $place) {

        return $this->getDb()->table('parkwash_parking')->where(['area_id' => $area_id, 'place' => $place, 'status' => 1])->count();
    }

    /**
     * 获取汽车车系
     */
    public function getSeriesList ($post) {

        if (false === F('CarSeries')) {
            $list = $this->getDb()->table('parkwash_car_series')->field('id,brand_id,name')->select();
            $data = [];
            foreach ($list as $k => $v) {
                $data[$v['brand_id']][] = [
                    'id' => $v['id'], 'name' => $v['name']
                ];
            }
            unset($list);
            F('CarSeries', $data);
        }
        $data = F('CarSeries');

        return success(var_exists($data, $post['brand_id'], []));
    }

    /**
     * 获取汽车品牌
     */
    public function getBrandList () {

        if (false === F('CarBrand')) {
            $list = $this->getDb()->table('parkwash_car_brand')->field('id,name,logo,pinyin,ishot')->select();
            foreach ($list as $k => $v) {
                $list[$k]['logo'] = httpurl($v['logo']);
            }
            F('CarBrand', $list);
        }

        return success(F('CarBrand'));
    }

    /**
     * 获取附近的洗车店
     */
    public function getNearbyStore ($post) {

        // 城市
        $post['adcode'] = intval($post['adcode']);
        // 距离
        $post['distance'] = intval($post['distance']);
        $post['distance'] = $post['distance'] < 1 ? 1 : $post['distance'];
        $post['distance'] = $post['distance'] > 20 ? 20 : $post['distance'];

        if (!$geohash = $this->geoOrder($post['lon'], $post['lat'])) {
            // 经纬度错误
            return success([]);
        }

        // 查询字段
        $field = [
            'id', 'name', 'logo', 'address', 'location', 'score', 'business_hours', 'market', 'price', 'order_count', 'status'
        ];
        $field[] = $geohash . ' as geohash';

        // 距离换算
        $geohashLength = [
            5 => 2, 4 => 20, 3 => 78, 2 => 360, 1 => 2500
        ];
        $len = 0;
        foreach ($geohashLength as $k => $v) {
            if ($post['distance'] <= $v) {
                $len = 12 - $k;
                break;
            }
        }

        $condition = [
            'adcode' => $post['adcode'],
            'geohash' => ['<', $len]
        ];

        // 获取门店
        if (!$storeList = $this->getDb()->table('parkwash_store')->field($field)->where($condition)->order('geohash')->limit(1000)->select()) {
            return success([]);
        }

        foreach ($storeList as $k => $v) {
            // 获取门店第一张缩略图
            if ($v['logo']) {
                $v['logo'] = json_decode($v['logo'], true);
                $storeList[$k]['logo'] = httpurl(getthumburl($v['logo'][0]));
            }
            // 获取距离公里
            $storeList[$k]['distance'] = round(LocationUtils::getDistance($v['location'], $post) / 1000, 2);
            unset($storeList[$k]['geohash']);
            // 去掉距离大于 distance 的记录
            if ($post['distance'] < $storeList[$k]['distance']) {
                unset($storeList[$k]);
            }
        }

        return success(array_values($storeList));
    }

    /**
     * 获取门店列表
     */
    public function getStoreList ($post) {

        // 城市
        $post['adcode'] = intval($post['adcode']);
        // 最后排序字段
        $post['lastpage'] = trim($post['lastpage']);
        // 排序字段
        $post['ordername'] = 'geohash';
        $post['ordersort'] = 'asc';

        // 查询字段
        $field = [
            'id', 'name', 'logo', 'address', 'location', 'score', 'business_hours', 'market', 'price', 'order_count', 'status', 'sort'
        ];
        // 结果返回
        $result = [
            'limit' => 10,
            'lastpage' => '',
            'list' => []
        ];

        // 经纬度
        if ($post['ordername'] == 'geohash') {
            $geohash = $this->geoOrder($post['lon'], $post['lat']);
            if (!$geohash) {
                $post['ordername'] = 'sort';
                $post['ordersort'] = 'desc';
                $post['lastpage'] = '';
            } else {
                $field[] = $geohash . ' as geohash';
            }
        }

        // 分页参数
        list($lastid, $lastorder) = explode(',', $post['lastpage']);

        $condition = [
            'adcode = ' . $post['adcode']
        ];
        if ($lastid > 0) {
            if ($post['ordersort'] == 'desc') {
                $condition[] = '(' . (isset($geohash) ? $geohash : $post['ordername']) . ' < ' . $lastorder . ' or (' . (isset($geohash) ? $geohash : $post['ordername']) . ' = ' . $lastorder . ' and id > ' . $lastid . '))';
            } else {
                $condition[] = '(' . (isset($geohash) ? $geohash : $post['ordername']) . ' > ' . $lastorder . ' or (' . (isset($geohash) ? $geohash : $post['ordername']) . ' = ' . $lastorder . ' and id > ' . $lastid . '))';
            }
        }
        $order = $post['ordername'] . ' ' . $post['ordersort'] . ', id asc';

        // 获取门店
        if (!$storeList = $this->getDb()->table('parkwash_store')->field($field)->where($condition)->order($order)->limit($result['limit'])->select()) {
            return success($result);
        }

        foreach ($storeList as $k => $v) {
            $result['lastpage'] = $v['id'] . ',' . $v[$post['ordername']];
            // 获取门店第一张缩略图
            if ($v['logo']) {
                $v['logo'] = json_decode($v['logo'], true);
                $storeList[$k]['logo'] = httpurl(getthumburl($v['logo'][0]));
            }
            if ($post['ordername'] == 'geohash') {
                // 获取距离公里
                $storeList[$k]['distance'] = round(LocationUtils::getDistance($v['location'], $post) / 1000, 2);
            }
            unset($storeList[$k]['geohash'], $storeList[$k]['sort']);
        }

        $result['list'] = $storeList;
        unset($storeList);
        return success($result);
    }

    /**
     * 下单
     */
    public function createCard ($uid, $post) {

        $post['store_id'] = intval($post['store_id']); // 门店
        $post['carport_id'] = intval($post['carport_id']); // 车辆
        $post['area_id'] = intval($post['area_id']); // 区域
        $post['place'] = trim($post['place']); // 车位号
        $post['pool_id'] = intval($post['pool_id']); // 排班
        $post['items'] = array_filter(explode(',', substr(trim($post['items']), 0, 200))); // 套餐 逗号分隔

        if (!$post['store_id']) {
            return error('请选择门店');
        }
        if (!$post['carport_id']) {
            return error('请选择车辆');
        }
        if (!$post['area_id']) {
            return error('请选择区域');
        }
        if (!check_car_license($post['car_number'])) {
            return error('车牌号不正确');
        }
        if ($post['place'] && strlen($post['place']) > 10) { // 车位不必填
            return error('车位号最多10个字符');
        }
        if (!$post['pool_id']) {
            return error('请选择服务时间');
        }
        if (!$post['items']) {
            return error('请选择洗车套餐');
        }
        if (!$post['payway']) {
            return error('请选择支付方式');
        }

        // 判断门店状态
        if (!$storeInfo = $this->getDb()->table('parkwash_store')->field('adcode,status')->where(['id' => $post['store_id']])->find()) {
            return error('该门店不存在');
        }
        if ($storeInfo['status'] == 0) {
            return error('该门店正在建设中');
        }

        // 判断车辆状态
        if (!$carportInfo = $this->getDb()->table('parkwash_carport')->field('brand_id,series_id')->where(['id' => $post['carport_id'], 'uid' => $uid])->find()) {
            return error('该车辆不存在');
        }

        // 判断区域
        if (!$this->getDb()->table('parkwash_park_area')->where(['id' => $post['area_id']])->count()) {
            return error('该车位区域不存在');
        }

        // 判断车位状态
        if ($post['place']) {
            if (!$this->checkPlaceState($post['area_id'], $post['place'])) {
                return error('该车位不支持洗车服务，请您更换停车位');
            }
        }

        // 判断服务时间
        if (!$poolInfo = $this->getDb()->table('parkwash_pool')->field('today,start_time,end_time')->where(['id' => $post['pool_id'], 'store_id' => $post['store_id'], 'amount' => ['>', 0]])->find()) {
            return error('当前门店已预订完');
        }
        $post['order_time'] = $poolInfo['today'] . ' ' . $poolInfo['start_time'];
        $post['abort_time'] = $poolInfo['today'] . ' ' . $poolInfo['end_time'];
        if (strtotime($post['abort_time']) < TIMESTAMP) {
            return error('不能预订该时间段');
        }

        // 判断套餐
        $items = $this->getStoreItem(['store_id' => $post['store_id']]);
        $items = $items['result'];
        if (!$items) {
            return error('当前门店未设置套餐');
        }
        foreach ($items as $k => $v) {
            if (!in_array($v['id'], $post['items'])) {
                unset($items[$k]);
            }
        }
        $items = array_values($items);
        if (!$items) {
            return error('已选择套餐不存在');
        }
        $totalPrice = array_sum(array_column($items, 'price')); // 套餐总价
        if ($totalPrice <= 0) {
            return error('套餐价格未设置');
        }

        // 判断账户余额
        $userModel = new UserModel();
        $userInfo = $userModel->getUserInfo($uid);
        if ($userInfo['errorcode'] !== 0) {
            return $userInfo;
        }
        $userInfo = $userInfo['result'];

        // 支付方式
        if ($post['payway'] == 'cbpay') {
            // 车币支付
            if ($totalPrice > $userInfo['money']) {
                return error('余额不足');
            }
        }

        // 订单预生成数据
        $orderParam = [
            'adcode' => $storeInfo['adcode'], 'pool_id' => $post['pool_id'], 'store_id' => $post['store_id'], 'uid' => $uid, 'car_number' => $post['car_number'], 'brand_id' => $carportInfo['brand_id'], 'series_id' => $carportInfo['series_id'], 'area_id' => $post['area_id'], 'place' => $post['place'], 'pay' => $totalPrice, 'deduct' => 0, 'items' => json_unicode_encode($items), 'order_time' => $post['order_time'], 'abort_time' => $post['abort_time']
        ];

        // 更新车辆
        $this->saveCarport($uid, [
            'id' => $post['carport_id'], 'car_number' => $orderParam['car_number'], 'brand_id' => $orderParam['brand_id'], 'series_id' => $orderParam['series_id'], 'area_id' => $orderParam['area_id'], 'place' => $orderParam['place'] ? $orderParam['place'] : null, 'isdefault' => 1
        ]);

        // 订单号
        $orderCode = $this->generateOrderCode($uid);

        // 防止重复扣费
        if ($lastTradeInfo = $this->getDb()->table('__tablepre__payments')->field('id,createtime,payway')->where([
                'trade_id' => $uid, 'mark' => md5(json_encode($orderParam)), 'status' => 0
             ])->limit(1)->find()) {
            // 支付方式改变或超时后更新订单号
            if ($lastTradeInfo['payway'] != $post['payway'] || strtotime($lastTradeInfo['createtime']) < TIMESTAMP - 600) {
                if (false === $this->getDb()->update('__tablepre__payments', [
                        'ordercode' => $orderCode, 'createtime' => date('Y-m-d H:i:s', TIMESTAMP)
                    ], 'id = ' . $lastTradeInfo['id'])) {
                    return error('更新订单失败');
                }
            }
            return success([
                'tradeid' => $lastTradeInfo['id']
            ]);
        }

        // 预约号减 1
        if (!$this->getDb()->update('parkwash_pool', [
            'amount' => '{!amount-1}'
            ], 'id = ' . $post['pool_id'] . ' and amount > 0')) {
            return error('该时间段已订完,请选择其他时间');
        }

        // 生成新订单
        if (!$cardId = $this->getDb()->transaction(function ($db) use($totalPrice, $orderCode, $post, $orderParam) {
            $orderParam['create_time'] = date('Y-m-d H:i:s', TIMESTAMP);
            $orderParam['update_time'] = $orderParam['create_time'];
            if (!$db->insert('parkwash_order', $orderParam)) {
                return false;
            }
            if (!$orderid = $db->getlastid()) {
                return false;
            }
            unset($orderParam['create_time'], $orderParam['update_time']);
            if (!$db->insert('__tablepre__payments', [
                'type' => 'parkwash', 'uses' => '洗车服务', 'trade_id' => $orderParam['uid'], 'param_id' => $orderid, 'pay' => $totalPrice, 'money' => $totalPrice, 'payway' => $post['payway'] == 'cbpay' ? 'cbpay' : '', 'ordercode' => $orderCode, 'createtime' => date('Y-m-d H:i:s', TIMESTAMP), 'mark' => md5(json_encode($orderParam))
            ])) {
                return false;
            }
            return $db->getlastid();
        })) {
            return error('订单生成失败');
        }

        if ($totalPrice === 0) {
            // 免支付金额（抵扣金额大于支付金额）
            $result = $this->handleCardSuc($cardId);
            if ($result['errorcode'] !== 0) {
                return $result;
            }
        } else {
            // 车币支付
            if ($post['payway'] == 'cbpay') {
                $result = $userModel->consume([
                    'platform' => 3,
                    'authcode' => md5('xc' . $uid),
                    'trade_no' => $orderCode,
                    'money' => $totalPrice,
                    'remark' => '支付停车场洗车费'
                ]);
                if ($result['errorcode'] !== 0) {
                    // 回滚交易表
                    $this->handleCardFail($cardId);
                    return $result;
                }
                // 车币消费成功
                $result = $this->handleCardSuc($cardId);
                if ($result['errorcode'] !== 0) {
                    return $result;
                }
            }
        }

        return success([
            'tradeid' => $cardId
        ]);
    }

    /**
     * 交易创建失败
     * @return array
     */
    public function handleCardFail ($cardId) {

        if (!$tradeInfo = $this->getDb()->table('__tablepre__payments')
            ->field('id,trade_id,param_id')
            ->where(['id' => $cardId])
            ->limit(1)
            ->find()) {
            return error('交易单不存在');
        }

        // 删除交易单与订单
        $this->getDb()->delete('__tablepre__payments', 'status = 0 and id = ' . $cardId);
        $this->getDb()->delete('parkwash_order', 'status = 0 and id = ' . $tradeInfo['param_id']);

        return success('OK');
    }

    /**
     * 交易成功的后续处理
     * @param $cardId 交易单ID
     * @param $tradeParam 交易单更新数据
     * @return array
     */
    public function handleCardSuc ($cardId, $tradeParam = []) {

        if (!$tradeInfo = $this->getDb()->table('__tablepre__payments')
            ->field('id,trade_id,param_id,voucher_id,pay,money,ordercode,payway')
            ->where(['id' => $cardId])
            ->limit(1)
            ->find()) {
            return error('交易单不存在');
        }

        // 更新交易单状态
        if (!$this->getDb()->transaction(function ($db) use($tradeInfo, $tradeParam) {
            $tradeParam = array_merge($tradeParam, [
                'status' => 1, 'paytime' => date('Y-m-d H:i:s', TIMESTAMP)
            ]);
            if (!$db->update('__tablepre__payments', $tradeParam, [
                'id' => $tradeInfo['id'], 'status' => 0
            ])) {
                return false;
            }
            if (!$db->update('parkwash_order', [
                'status' => 1, 'update_time' => date('Y-m-d H:i:s', TIMESTAMP)
            ], [
                'id' => $tradeInfo['param_id'], 'status' => 0
            ])) {
                return false;
            }
            return true;
        })) {
            return error('更新交易失败');
        }

        // 获取订单信息
        $orderInfo = $this->getDb()->table('parkwash_order')->field('id,uid,store_id,items,car_number,place,order_time')->where(['id' => $tradeInfo['param_id']])->limit(1)->find();

        // 获取门店信息
        $storeInfo = $this->getDb()->table('parkwash_store')->field('id,tel')->where(['id' => $orderInfo['store_id']])->limit(1)->find();

        // 更新门店下单数、收益
        $this->getDb()->update('parkwash_store', [
            'order_count' => '{!order_count+1}',
            'money' => '{!money+' . $tradeInfo['money'] . '}'
        ], [
            'id' => $orderInfo['store_id']
        ]);

        // 更新用户下单数、消费
        $this->getDb()->update('parkwash_usercount', [
            'coupon_consume' => '{!coupon_consume+' . ($tradeInfo['money'] - $tradeInfo['pay']) . '}',
            'parkwash_count' => '{!parkwash_count+1}',
            'parkwash_consume' => '{!parkwash_consume+' . $tradeInfo['money'] . '}'
        ], [
            'uid' => $orderInfo['uid']
        ]);

        // 记录资金变动
        $this->pushTrades([
            'uid' => $orderInfo['uid'], 'mark' => '-', 'money' => $tradeInfo['money'], 'title' => '支付停车场洗车费'
        ]);

        // 记录订单状态改变
        $this->pushSequence([
            'orderid' => $orderInfo['id'], 'uid' => $orderInfo['uid'], 'title' => '下单成功，支付 ' . round_dollar($tradeInfo['money']) . ' 元'
        ]);

        // 通知商家
        $this->pushNotice([
            'receiver' => 2,
            'notice_type' => 2, // 播报器
            'orderid' => $orderInfo['id'],
            'store_id' => $orderInfo['store_id'],
            'uid' => $orderInfo['uid'],
            'tel' => $storeInfo['tel'],
            'title' => '下单通知',
            'content' => template_replace('{$car_number}已下单，预约时间:{$order_time}。', [
                'car_number' => $orderInfo['car_number'], 'order_time' => $orderInfo['order_time']
            ])
        ]);

        return success('OK');
    }

    /**
     * 检查车位状态是否支持洗车服务
     * @param $area_id 区域ID
     * @param $place 车位号
     * @return int 1正常 0不支持洗车服务
     */
    protected function checkPlaceState ($area_id, $place) {

        $parkingInfo = $this->getDb()->table('parkwash_parking')->field('status')->where(['area_id' => $area_id, 'place' => $place])->find();
        return $parkingInfo ? $parkingInfo['status'] : 1;
    }

    /**
     * 写入通知
     */
    protected function pushNotice ($post) {

        return $this->getDb()->insert('parkwash_notice', [
            'receiver' => $post['receiver'], 'notice_type' => $post['notice_type'], 'orderid' => $post['orderid'], 'store_id' => $post['store_id'], 'uid' => $post['uid'], 'tel' => $post['tel'], 'title' => $post['title'], 'url' => $post['url'], 'content' => $post['content'], 'create_time' => date('Y-m-d H:i:s', TIMESTAMP)
        ]);
    }

    /**
     * 记录订单状态改变
     */
    protected function pushSequence ($post) {

        return $this->getDb()->insert('parkwash_order_sequence', [
            'orderid' => $post['orderid'], 'uid' => $post['uid'], 'title' => $post['title'], 'create_time' => date('Y-m-d H:i:s', TIMESTAMP)
        ]);
    }

    /**
     * 记录资金变动
     */
    protected function pushTrades ($post) {

        return $this->getDb()->insert('parkwash_trades', [
            'uid' => $post['uid'], 'mark' => $post['mark'], 'money' => $post['money'], 'title' => $post['title'], 'create_time' => date('Y-m-d H:i:s', TIMESTAMP)
        ]);
    }

    /**
     * 生成单号(16位)
     * @return string
     */
    protected function generateOrderCode ($uid) {

        $code[] = date('Ymd', TIMESTAMP);
        $code[] = (rand() % 10) . (rand() % 10) . (rand() % 10) . (rand() % 10);
        $code[] = str_pad(substr($uid, -4),4,'0',STR_PAD_LEFT);
        return implode('', $code);
    }

    /**
     * geo排序
     */
    protected function geoOrder ($lon, $lat, $as = '') {

        if (!$location = LocationUtils::checkLocation([$lat, $lon])) {
            return null;
        }
        $geohash = new Geohash();
        $hash = $geohash->encode($location[0], $location[1]);
        $len = strlen($hash);
        if (!$len) {
            return null;
        }
        $put[] = '(case';
        for ($i = 0; $i < $len; $i++) {
            if ($i == 0) {
                $put[] = 'when geohash="' . $hash . '" then 0';
            } else {
                $put[] = 'when left(geohash,' . ($len - $i) . ')="' . substr($hash, 0, -$i) . '" then ' . $i;
            }
        }
        $put[] = 'else ' . $len . ' end)';
        if ($as) {
            $put[] = 'as ' . $as;
        }
        $put = implode(' ', $put);
        return $put;
    }

    /**
     * 生成测试门店
     */
    protected function buildTestStore () {

        $operator = ['-', '+'];
        $status = [1, 0];
        $lon = 106.713478;
        $lat = 26.578343;
        $geohash = new \library\Geohash();
        for ($i = 0; $i < 100000; $i ++) {
            $_lon = $lon + ($operator[array_rand($operator)] . (rand(1,100000) / 100000));
            $_lat = $lat + ($operator[array_rand($operator)] . (rand(1,100000) / 100000));
            $hash = $geohash->encode($_lat, $_lon);
            $data[] = [
                'adcode' => 520100,
                'name' => '门店' . $i,
                'location' => $_lon . ',' . $_lat,
                'geohash' => $hash,
                'business_hours' => rand(0,23) . ':00-' . rand(0,23) . ':00',
                'market' => '停车半价',
                'status' => $status[array_rand($status)],
                'price' => rand(0, 2000),
                'order_count' => rand(0, 100000)
            ];
            $item[] = [
                'store_id' => $i+1,
                'item_id' => 1,
                'price' => rand(100, 10000)
            ];
        }
        \library\DB::getInstance()->insert('parkwash_store', $data);
        \library\DB::getInstance()->insert('parkwash_store_item', $item);
    }

}
