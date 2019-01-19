<?php

namespace models;

use library\Crud;

class XicheManageModel extends Crud {

    /**
     * 获取设备列表
     */
    public function getList ($table, $condition, $limit) {
        return $this->getDb()
            ->table('__tablepre__' . $table)
            ->field('*')
            ->where($condition)
            ->order('id desc')
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
            $area_list = https_request('http://xicheba.net/chemi/API/Handler/GetAreaList', [
                'apiKey' => getConfig('xc', 'apikey')
            ]);
        } catch (\Exception $e) {
            return error($e->getMessage());
        }
        if (!$area_list['result']) {
            return error($area_list['messages']);
        }
        return success($area_list['data']);
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
            $device_list = https_request('http://xicheba.net/chemi/API/Handler/DevList', [
                'apiKey' => getConfig('xc', 'apikey')
            ]);
        } catch (\Exception $e) {
            return error($e->getMessage());
        }
        if (!$device_list['result']) {
            return error($device_list['messages']);
        }
        $device_list = $device_list['data'];

        $device_list = array_filter($device_list, function($v) use($AreaId){
            return $v['AreaId'] == $AreaId;
        });

        // 获取设备参数
        try {
            $device_param = https_request('http://xicheba.net/chemi/API/Handler/DevParam', [
                'apiKey' => getConfig('xc', 'apikey'),
                'AreaId' => $AreaId
            ]);
        } catch (\Exception $e) {
            return error($e->getMessage());
        }
        if (!$device_param['result']) {
            return error($device_param['messages']);
        }
        $device_param = $device_param['data'];

        foreach ($device_list as $k => $v) {
            $device_list[$k]['AreaName'] = $device_param['AreaName'];
            $device_list[$k]['Price'] = $device_param['Price'];
        }
        return success($device_list);
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
            $device_info = https_request('http://xicheba.net/chemi/API/Handler/DeviceOne', [
                'apiKey' => getConfig('xc', 'apikey'),
                'DevCode' => $post['devcode']
            ]);
        } catch (\Exception $e) {
            return error($e->getMessage());
        }
        if (!$device_info['result']) {
            return error($device_info['messages']);
        }
        $device_info = $device_info['data'];

        // 获取设备参数
        try {
            $device_param = https_request('http://xicheba.net/chemi/API/Handler/DevParam', [
                'apiKey' => getConfig('xc', 'apikey'),
                'AreaId' => $device_info['AreaId']
            ]);
        } catch (\Exception $e) {
            return error($e->getMessage());
        }
        if (!$device_param['result']) {
            return error($device_param['messages']);
        }
        $device_param = $device_param['data'];

        if (!$this->getDb()->insert('__tablepre__xiche_device', [
            'devcode' => $post['devcode'],
            'isonline' => $device_info['IsOnline'],
            'usestate' => $device_info['UseState'],
            'created_at' => date('Y-m-d H:i:s', TIMESTAMP),
            'updated_at' => date('Y-m-d H:i:s', TIMESTAMP),
            'areaid' => $device_param['AreaID'],
            'areaname' => $device_param['AreaName'],
            'price' => $device_param['Price'] * 100,
            'parameters' => json_unicode_encode($device_param)
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