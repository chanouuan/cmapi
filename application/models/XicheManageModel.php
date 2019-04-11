<?php

namespace app\models;

use Crud;
use app\library\Geohash;
use app\library\LocationUtils;

class XicheManageModel extends Crud {

    /**
     * 套餐删除
     */
    public function itemDelete ($id) {
        $id = intval($id);
        if ($id == 1) {
            return error('该套餐项目已锁定，不能删除');
        }
        if (!$this->getDb()->delete('parkwash_item', ['id' => $id])) {
            return error('删除失败');
        }
        // 关联删除所有门店套餐
        $this->getDb()->delete('parkwash_store_item', ['item_id' => $id]);
        return success('OK');
    }

    /**
     * 套餐编辑
     */
    public function itemUpdate ($post) {
        $post['name'] = trim_space($post['name']);
        $post['price'] = intval($post['price']);
        $post['price'] = $post['price'] < 0 ? 0 : $post['price'];
        $post['price'] = $post['price'] * 100;

        if (empty($post['name'])) {
            return error('项目名不能为空');
        }

        if (false === $this->getDb()->update('parkwash_item', [
            'name' => $post['name'], 'price' => $post['price']
        ], ['id' => $post['id']])) {
            return error('修改失败');
        }

        return success('OK');
    }

    /**
     * 套餐添加
     */
    public function itemAdd ($post) {
        $post['name'] = trim_space($post['name']);
        $post['price'] = intval($post['price']);
        $post['price'] = $post['price'] < 0 ? 0 : $post['price'];
        $post['price'] = $post['price'] * 100;

        if (empty($post['name'])) {
            return error('项目名不能为空');
        }

        if (!$this->getDb()->insert('parkwash_item', [
            'name' => $post['name'], 'price' => $post['price']
        ])) {
            return error('添加失败');
        }

        return success('OK');
    }

    /**
     * 编辑门店
     */
    public function storeUpdate ($post) {
        $post['adcode'] = intval($post['adcode']);
        $post['name'] = trim_space($post['name']);
        $post['market'] = trim_space($post['market']);
        $post['location'] = trim_space($post['location']);
        $post['location'] = str_replace('，', ',', $post['location']); // 将中文逗号换成英文
        $post['location'] = LocationUtils::checkLocation($post['location']);
        $post['daily_cancel_limit'] = intval($post['daily_cancel_limit']);
        $post['daily_cancel_limit'] = $post['daily_cancel_limit'] < 0 ? 0 : $post['daily_cancel_limit'];
        $post['order_count_ratio'] = intval($post['order_count_ratio']);
        $post['order_count_ratio'] = $post['order_count_ratio'] < 0 ? 0 : $post['order_count_ratio'];
        $post['status'] = $post['status'] ? 1 : 0;
        $post['time_interval'] = intval($post['time_interval']);
        $post['time_amount'] = intval($post['time_amount']);
        $post['time_day'] = array_filter($post['time_day']);
        sort($post['time_day']);
        $post['time_day'] = implode('', $post['time_day']);

        // 套餐
        $post['item'] = $post['item'] ? $post['item'] : [];
        foreach ($post['item'] as $k => $v) {
            $post['item'][$k] = $v > 0 ? $v : 0;
        }
        $post['item'] = array_filter($post['item']);

        if (strlen($post['adcode']) != 6) {
            return error('区域代码不正确');
        }
        if (empty($post['name'])) {
            return error('店名不能为空');
        }
        if (!validate_telephone($post['tel'])) {
            return error('联系手机号不正确');
        }
        if (empty($post['address'])) {
            return error('地址不能为空');
        }
        list($lon, $lat) = explode(',', $post['location']);
        if (!$lon || !$lat || $lat > $lon) {
            return error('经纬度坐标不正确,格式为“经度,纬度”,坐标系为gcj02');
        }
        if (!preg_match('/^\d{1,2}\:\d{1,2}-\d{1,2}\:\d{1,2}$/', $post['business_hours'])) {
            return error('请填写营业时间,格式为:9:00-10:00');
        }
        if (empty($post['item'])) {
            return error('请至少设置一项洗车套餐');
        }
        if ($post['time_interval'] < 20 || $post['time_interval'] > 60) {
            return error('请选择排班时段,20-60分钟');
        }
        if ($post['time_amount'] <= 0) {
            return error('排班时段下单量不能为空');
        }
        if (empty($post['time_day'])) {
            return error('请选择排班工作日');
        }

        $param = [
            'adcode' => $post['adcode'],
            'name' => $post['name'],
            'tel' => $post['tel'],
            'address' => $post['address'],
            'location' => $post['location'],
            'geohash' => (new Geohash())->encode($lat, $lon),
            'business_hours' => $post['business_hours'],
            'market' => $post['market'],
            'status' => $post['status'],
            'price' => min($post['item']) * 100,
            'daily_cancel_limit' => $post['daily_cancel_limit'],
            'order_count_ratio' => $post['order_count_ratio'],
            'time_interval' => $post['time_interval'],
            'time_amount' => $post['time_amount'],
            'time_day' => $post['time_day'],
            'update_time' => date('Y-m-d H:i:s', TIMESTAMP)
        ];

        // 上传图片
        if ($_FILES['logo'] && $_FILES['logo']['error'] == 0) {
            if ($_FILES['logo']['size'] > 1048576) {
                return error('最大上传不超过1M');
            }
            $result = uploadfile($_FILES['logo'], 'jpg,jpeg,png', 0, 0);
            if ($result['errorcode'] !== 0) {
                return $result;
            }
            $result = $result['result'];
            $param['logo'] = json_encode([$result['url']]);
        }

        // 编辑门店
        if (false === $this->getDb()->update('parkwash_store', $param, ['id' => $post['id']])) {
            return error('编辑失败');
        }

        // 更新套餐
        $item = [];
        foreach ($post['item'] as $k => $v) {
            $item[] = [
                'store_id' => $post['id'], 'item_id' => $k, 'price' => $v * 100
            ];
        }
        $this->getDb()->delete('parkwash_store_item', ['store_id' => $post['id']]);
        $this->getDb()->insert('parkwash_store_item', $item);

        return success('OK');
    }

    /**
     * 添加门店
     */
    public function storeAdd ($post) {
        $post['adcode'] = intval($post['adcode']);
        $post['name'] = trim_space($post['name']);
        $post['market'] = trim_space($post['market']);
        $post['location'] = trim_space($post['location']);
        $post['location'] = str_replace('，', ',', $post['location']); // 将中文逗号换成英文
        $post['location'] = LocationUtils::checkLocation($post['location']);
        $post['daily_cancel_limit'] = intval($post['daily_cancel_limit']);
        $post['daily_cancel_limit'] = $post['daily_cancel_limit'] < 0 ? 0 : $post['daily_cancel_limit'];
        $post['order_count_ratio'] = intval($post['order_count_ratio']);
        $post['order_count_ratio'] = $post['order_count_ratio'] < 0 ? 0 : $post['order_count_ratio'];
        $post['status'] = $post['status'] ? 1 : 0;
        $post['time_interval'] = intval($post['time_interval']);
        $post['time_amount'] = intval($post['time_amount']);
        $post['time_day'] = array_filter($post['time_day']);
        sort($post['time_day']);
        $post['time_day'] = implode('', $post['time_day']);

        // 套餐
        $post['item'] = $post['item'] ? $post['item'] : [];
        foreach ($post['item'] as $k => $v) {
            $post['item'][$k] = $v > 0 ? $v : 0;
        }
        $post['item'] = array_filter($post['item']);

        if (strlen($post['adcode']) != 6) {
            return error('区域代码不正确');
        }
        if (empty($post['name'])) {
            return error('店名不能为空');
        }
        if (!validate_telephone($post['tel'])) {
            return error('联系手机号不正确');
        }
        if (empty($post['address'])) {
            return error('地址不能为空');
        }
        list($lon, $lat) = explode(',', $post['location']);
        if (!$lon || !$lat || $lat > $lon) {
            return error('经纬度坐标不正确,格式为“经度,纬度”,坐标系为gcj02');
        }
        if (!preg_match('/^\d{1,2}\:\d{1,2}-\d{1,2}\:\d{1,2}$/', $post['business_hours'])) {
            return error('请填写营业时间,格式为:9:00-10:00');
        }
        if (empty($post['item'])) {
            return error('请至少设置一项洗车套餐');
        }
        if ($post['time_interval'] < 20 || $post['time_interval'] > 60) {
            return error('请选择排班时段,20-60分钟');
        }
        if ($post['time_amount'] <= 0) {
            return error('排班时段下单量不能为空');
        }
        if (empty($post['time_day'])) {
            return error('请选择排班工作日');
        }

        // 上传图片
        if ($_FILES['logo'] && $_FILES['logo']['error'] == 0) {
            if ($_FILES['logo']['size'] > 1048576) {
                return error('最大上传不超过1M');
            }
            $result = uploadfile($_FILES['logo'], 'jpg,jpeg,png', 0, 0);
            if ($result['errorcode'] !== 0) {
                return $result;
            }
            $result = $result['result'];
            $post['logo'] = json_encode([$result['url']]);
        }

        // 新增门店
        if (!$this->getDb()->insert('parkwash_store', [
            'adcode' => $post['adcode'],
            'name' => $post['name'],
            'logo' => $post['logo'],
            'tel' => $post['tel'],
            'address' => $post['address'],
            'location' => $post['location'],
            'geohash' => (new Geohash())->encode($lat, $lon),
            'business_hours' => $post['business_hours'],
            'market' => $post['market'],
            'status' => $post['status'],
            'price' => min($post['item']) * 100,
            'daily_cancel_limit' => $post['daily_cancel_limit'],
            'order_count_ratio' => $post['order_count_ratio'],
            'time_interval' => $post['time_interval'],
            'time_amount' => $post['time_amount'],
            'time_day' => $post['time_day'],
            'create_time' => date('Y-m-d H:i:s', TIMESTAMP),
            'update_time' => date('Y-m-d H:i:s', TIMESTAMP)
        ])) {
            return error('添加失败');
        }

        $store_id = $this->getDb()->getlastid();

        // 新增套餐
        $item = [];
        foreach ($post['item'] as $k => $v) {
            $item[] = [
                'store_id' => $store_id, 'item_id' => $k, 'price' => $v * 100
            ];
        }
        $this->getDb()->insert('parkwash_store_item', $item);

        return success('OK');
    }

    /**
     * 获取单条数据
     */
    public function getInfo ($table, $condition, $field = null) {
        return $this->getDb()
            ->table($table)
            ->field($field)
            ->where($condition)
            ->limit(1)
            ->find();
    }

    /**
     * 获取设备列表
     */
    public function getList ($table, $condition, $limit = null, $order = 'id desc') {
        if (0 !== strpos($table, 'parkwash_')) {
            $table = '__tablepre__' . $table;
        }
        return $this->getDb()
            ->table($table)
            ->field('*')
            ->where($condition)
            ->order($order)
            ->limit($limit)
            ->select();
    }

    /**
     * 获取设备数量
     */
    public function getCount ($table, $condition) {
        if (0 !== strpos($table, 'parkwash_')) {
            $table = '__tablepre__' . $table;
        }
        return $this->getDb()
            ->table($table)
            ->field('count(1)')
            ->where($condition)
            ->count();
    }

    /**
     * 获取区块列表
     */
    public function getDevArea () {
        try {
            $areaList = https_request('http://xicheba.net/chemi/API/Handler/GetAreaList', [
                'apiKey' => getConfig('xc', 'apikey')
            ]);
        } catch (\Exception $e) {
            return error($e->getMessage());
        }
        if (!$areaList['result']) {
            return error($areaList['messages']);
        }
        return success($areaList['data']);
    }

    /**
     * 获取设备列表
     */
    public function getDev ($AreaId) {
        // 区块ID
        if (!$AreaId) {
            return error('请选择区块');
        }

        // 获取设备列表
        try {
            $deviceList = https_request('http://xicheba.net/chemi/API/Handler/DevList', [
                'apiKey' => getConfig('xc', 'apikey')
            ]);
        } catch (\Exception $e) {
            return error($e->getMessage());
        }
        if (!$deviceList['result']) {
            return error($deviceList['messages']);
        }
        $deviceList = $deviceList['data'];

        $deviceList = array_filter($deviceList, function($v) use($AreaId){
            return $v['AreaId'] == $AreaId;
        });
        $deviceList = array_values($deviceList);

        // 获取设备参数
        try {
            $deviceParam = https_request('http://xicheba.net/chemi/API/Handler/DevParam', [
                'apiKey' => getConfig('xc', 'apikey'),
                'AreaId' => $AreaId
            ]);
        } catch (\Exception $e) {
            return error($e->getMessage());
        }
        if (!$deviceParam['result']) {
            return error($deviceParam['messages']);
        }
        $deviceParam = $deviceParam['data'];

        foreach ($deviceList as $k => $v) {
            $deviceList[$k]['AreaName'] = $deviceParam['AreaName'];
            $deviceList[$k]['Price'] = $deviceParam['Price'];
        }
        return success($deviceList);
    }

    /**
     * 编辑设备
     */
    public function deviceUpdate ($post) {
        $post['id'] = intval($post['id']);
        $post['areaname'] = addslashes(trim($post['areaname']));
        $post['isonline'] = $post['isonline'] ? 1 : 0;
        $post['price'] = round(floatval($post['price']), 2);
        $post['usetime'] = $post['usetime'] ? 1 : 0;

        if (!$post['areaname']) {
            return error('请填写区块名称');
        }
        if ($post['price'] <= 0) {
            return error('价格不能小于等于零');
        }

        $param = [
            'areaname' => $post['areaname'],
            'isonline' => $post['isonline'],
            'price' => $post['price'] * 100
        ];
        if ($post['usetime']) {
            $param['usetime'] = 0;
        }
        if (false === $this->getDb()->update('__tablepre__xiche_device', $param, 'id = ' . $post['id'])) {
            return error('操作失败');
        }

        return success('OK');
    }

    /**
     * 同步设备参数
     */
    public function deviceSync ($post) {
        $post['devcode'] = trim($post['devcode']);

        // 获取设备信息
        try {
            $deviceInfo = https_request('http://xicheba.net/chemi/API/Handler/DeviceOne', [
                'apiKey' => getConfig('xc', 'apikey'),
                'DevCode' => $post['devcode']
            ]);
        } catch (\Exception $e) {
            return error($e->getMessage());
        }
        if (!$deviceInfo['result']) {
            return error($deviceInfo['messages']);
        }
        $deviceInfo = $deviceInfo['data'];

        // 获取设备参数
        try {
            $deviceParam = https_request('http://xicheba.net/chemi/API/Handler/DevParam', [
                'apiKey' => getConfig('xc', 'apikey'),
                'AreaId' => $deviceInfo['AreaId']
            ]);
        } catch (\Exception $e) {
            return error($e->getMessage());
        }
        if (!$deviceParam['result']) {
            return error($deviceParam['messages']);
        }
        $deviceParam = $deviceParam['data'];

        if (!$this->getDb()->update('__tablepre__xiche_device', [
            'isonline' => $deviceInfo['IsOnline'],
            'usestate' => $deviceInfo['UseState'],
            'updated_at' => date('Y-m-d H:i:s', TIMESTAMP),
            'areaid' => $deviceParam['AreaID'],
            'areaname' => $deviceParam['AreaName'],
            'price' => $deviceParam['Price'] * 100,
            'parameters' => json_unicode_encode($deviceParam)
        ], ['devcode' => $post['devcode']])) {
            return error('同步设备参数失败');
        }

        return success('OK');
    }

    /**
     * 设备添加
     */
    public function deviceAdd ($post) {
        $post['devcode'] = trim($post['devcode']);

        if (!preg_match('/^[0-9|a-z|A-Z]{14}$/', $post['devcode'])) {
            return error('请选择设备');
        }

        // 设备编码唯一限制
        if ($this->getDeviceByCode($post['devcode'])) {
            return error('该设备已存在');
        }

        // 获取设备信息
        try {
            $deviceInfo = https_request('http://xicheba.net/chemi/API/Handler/DeviceOne', [
                'apiKey' => getConfig('xc', 'apikey'),
                'DevCode' => $post['devcode']
            ]);
        } catch (\Exception $e) {
            return error($e->getMessage());
        }
        if (!$deviceInfo['result']) {
            return error($deviceInfo['messages']);
        }
        $deviceInfo = $deviceInfo['data'];

        // 获取设备参数
        try {
            $deviceParam = https_request('http://xicheba.net/chemi/API/Handler/DevParam', [
                'apiKey' => getConfig('xc', 'apikey'),
                'AreaId' => $deviceInfo['AreaId']
            ]);
        } catch (\Exception $e) {
            return error($e->getMessage());
        }
        if (!$deviceParam['result']) {
            return error($deviceParam['messages']);
        }
        $deviceParam = $deviceParam['data'];

        if (!$this->getDb()->insert('__tablepre__xiche_device', [
            'devcode' => $post['devcode'],
            'isonline' => $deviceInfo['IsOnline'],
            'usestate' => $deviceInfo['UseState'],
            'created_at' => date('Y-m-d H:i:s', TIMESTAMP),
            'updated_at' => date('Y-m-d H:i:s', TIMESTAMP),
            'areaid' => $deviceParam['AreaID'],
            'areaname' => $deviceParam['AreaName'],
            'price' => $deviceParam['Price'] * 100,
            'parameters' => json_unicode_encode($deviceParam)
        ])) {
            return error('添加设备失败');
        }

        return success('OK');
    }

    /**
     * 根据设备编号获取设备信息
     */
    public function getDeviceByCode($devcode) {

        return $this->getDb()->table('__tablepre__xiche_device')->field('*')->where('devcode = ?')->bindValue($devcode)->limit(1)->find();
    }

    /**
     * 获取设备信息
     */
    public function getDeviceById($id) {
        if (is_array($id)) {
            return $this->getDb()->table('__tablepre__xiche_device')->field('id,devcode')->where('id in (' . implode(',', $id) . ')')->select();
        }
        return $this->getDb()->table('__tablepre__xiche_device')->field('id,devcode,parameters')->where('id = ?')->bindValue($id)->find();
    }

    /**
     * 获取日志信息
     */
    public function getLogInfo ($id) {
        return $this->getDb()->table('__tablepre__xiche_log')->field('*')->where('id = ?')->bindValue($id)->find();
    }

    /**
     * 删除日志
     */
    public function logDelete ($id) {
        if (!$this->getDb()->delete('__tablepre__xiche_log', ['id' => intval($id)])) {
            return error('操作失败');
        }
        return success('OK');
    }

    /**
     * 编辑配置
     */
    public function configUpdate ($post) {
        if (!$info = $this->getConfigInfo($post['id'])) {
            return error('参数错误');
        }
        if (false === $this->getDb()->update('__tablepre__config', [
                'value' => addslashes($post['value'])
            ], ['id' => $post['id']])) {
            return error('操作失败');
        }
        F('config', null);
        return success('操作成功');
    }

    /**
     * 获取配置信息
     */
    public function getConfigInfo ($id) {
        return $this->getDb()->table('__tablepre__config')->field('*')->where(['id' => $id])->find();
    }

}
