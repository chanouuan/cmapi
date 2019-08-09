<?php
/**
 * 停车场洗车角色
 */

namespace app\common;

use app\library\DB;

class ParkWashRole
{

    const ADMIN = 1;
    const OWNER = 2;

    static $message = [
        1 => '超管',
        2 => '店长'
    ];

    /**
     * 获取店长所属店铺
     * @param $adminid
     * @return int
     */
    public static function getOwnerStoreId ($adminid)
    {
        if (!$adminInfo = DB::getInstance()->table('admin_user')->field('telephone')->where(['id' => $adminid])->limit(1)->find()) {
            return null;
        }
        if (!$employeeInfo = DB::getInstance()->table('parkwash_employee')->field('store_id')->where(['telephone' => $adminInfo['telephone'], 'role_id' => self::OWNER])->limit(1)->find()) {
            return null;
        }
        return $employeeInfo['store_id'];
    }

    public static function getMessage ($code)
    {
        return isset(self::$message[$code]) ? self::$message[$code] : $code;
    }

}
