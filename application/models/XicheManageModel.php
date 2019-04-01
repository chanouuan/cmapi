<?php

namespace app\models;

use Crud;

class XicheManageModel extends Crud {

    /**
     * 获取设备列表
     */
    public function getList ($table, $condition, $limit, $order = 'id desc') {
        return $this->getDb()
            ->table('__tablepre__' . $table)
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
        return $this->getDb()
            ->table('__tablepre__' . $table)
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
