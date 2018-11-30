<?php

namespace models;

use library\Crud;

class XicheModel extends Crud {

    /**
     * 接收洗车机状态上报
     */
    public function ReportStatus () {
        $apiKey = getgpc('apiKey');
        $DevCode = getgpc('DevCode'); // 设备编码
        $IsOnline = intval(getgpc('IsOnline')); // 0-离线，1-在线
        $UseState = intval(getgpc('UseState')); // 0:空闲;1:投币洗车;2:刷卡洗车;3:微信洗车;4:停售;5:手机号洗车;6:会员扫码洗车; 7:缺泡沫

        // 验证apikey
        if (getSysConfig('xiche_apikey') !== $apiKey) {
            return error('apikey错误');
        }
        if (!preg_match('/^[0-9|a-z|A-Z]{14}$/', $DevCode)) {
            return error('设备编码不能为空或格式不正确');
        }

        $rs = $this->getDb()->table('__tablepre__xiche_device')->field('id')->where('devcode = ?')->bindValue($DevCode)->find();
        if ($rs) {
            if (false === $this->getDb()->update('__tablepre__xiche_device', [
                    'isonline' => $IsOnline,
                    'usestate' => $UseState,
                    'updated_at' => date('Y-m-d H:i:s', TIMESTAMP)
                ], 'id = ' . $rs['id'])) {
                return error('更新设备失败');
            }
        } else {
            if (!$this->getDb()->insert('__tablepre__xiche_device', [
                    'devcode' => $DevCode,
                    'isonline' => $IsOnline,
                    'usestate' => $UseState,
                    'created_at' => date('Y-m-d H:i:s', TIMESTAMP),
                    'updated_at' => date('Y-m-d H:i:s', TIMESTAMP)
                ])) {
                return error('添加设备失败');
            }
        }

        return success('请求成功');
    }

}