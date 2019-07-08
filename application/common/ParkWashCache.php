<?php
/**
 * 缓存
 */

namespace app\common;

use app\library\DB;

class ParkWashCache
{

    /**
     * 获取品牌缓存
     */
    public static function getBrand ()
    {
        if (false === F('CarBrand')) {
            $list = DB::getInstance()->table('parkwash_car_brand')->field('id,name,logo,pinyin')->select();
            foreach ($list as $k => $v) {
                $list[$k]['logo'] = httpurl($v['logo']);
            }
            F('CarBrand', $list);
            return $list;
        }
        return F('CarBrand');
    }

    /**
     * 获取车型缓存
     */
    public static function getSeries ()
    {
        if (false === F('CarSeries')) {
            $list = DB::getInstance()->table('parkwash_car_series')->field('id,brand_id,name,car_type_id')->select();
            $list = array_column($list, null, 'id');
            $list = array_key_clean($list, ['id']);
            F('CarSeries', $list);
            return $list;
        }
        return F('CarSeries');
    }

    /**
     * 获取充值卡类型缓存
     */
    public static function getRechargeCardType ()
    {
        if (false === F('RechargeCardType')) {
            $list = DB::getInstance()
                ->table('parkwash_recharge_type')
                ->where(['status' => 1])
                ->field('id,price,give')
                ->order('sort desc')
                ->select();
            F('RechargeCardType', $list);
            return $list;
        }
        return F('RechargeCardType');
    }

    /**
     * 获取会员卡类型
     */
    public static function getCardType ()
    {
        if (false === F('CardType')) {
            $list = DB::getInstance()
                ->table('parkwash_card_type')
                ->where(['status' => 1])
                ->field('id,name,price,months,days')
                ->order('sort desc')
                ->select();
            F('CardType', $list);
            return $list;
        }
        return F('CardType');
    }

    /**
     * 获取车类型缓存
     */
    public static function getCarType ()
    {
        if (false === F('CarType')) {
            $list = DB::getInstance()->table('parkwash_car_type')->field('id,name')->select();
            $list = array_column($list, 'name', 'id');
            F('CarType', $list);
            return $list;
        }
        return F('CarType');
    }

}
