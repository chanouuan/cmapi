<?php

namespace app\controllers;

use ActionPDO;
use app\library\JSSDK;
use app\models\ParkWashModel;
use app\models\XicheModel;
use app\models\UserModel;
use app\models\TradeModel;

/**
 * 停车场洗车
 * @cyq
 */
class ParkWash extends ActionPDO {

    /**
     * 登录
     * @param $telephone 用户手机号
     * @param $msgcode 短信验证码 ( msgcode 与 password 任选其一)
     * @param $password 车秘密码 ( msgcode 与 password 任选其一)
     * @param $code 小程序登录凭证
     * @return array
     * {
     * "errNo":0, // 错误码 0成功 -1失败 500未绑定小程序
     * "message":"", //错误消息
     * "result":{
     *     "uid":10, //用户ID
     *     "telephone":"", //手机号
     *     "avatar":"", //头像地址
     *     "nickname":"", //昵称
     *     "sex":1, //性别 0未知 1男 2女
     *     "money":0, //余额 (分)
     *     "ispw":0, //是否已设置密码
     *     "token":"", //登录效验码
     * }}
     */
    public function login () {

        // 客户端是微信
        if (CLIENT_TYPE == 'wx') {
            // 小程序登录
            $code = getgpc('code');
            if ($code) {
                $wxConfig = getSysConfig('xiche', 'wx');
                $jssdk = new JSSDK($wxConfig['appid'], $wxConfig['appsecret']);
                $reponse = $jssdk->code2Session($code);
                if ($reponse['errorcode'] !== 0) {
                    return $reponse;
                }
                if ($loginInfo = (new XicheModel())->checkLogin($reponse['result'])) {
                    (new ParkWashModel())->saveUserCount($loginInfo['uid']);
                    $userInfo = (new UserModel())->getUserInfo($loginInfo['uid']);
                    $userInfo['result']['token'] = $loginInfo['token'];
                    return $userInfo;
                }
                // 未绑定小程序就将 openid 传给账号密码登录执行绑定
                $_POST['__authcode'] = $reponse['result']['authcode'];
            }
        }

        // 账号密码登录
        $result = (new XicheModel())->login($_POST);
        if ($result['errorcode'] === 0) {
            // 插入 userCount 表
            (new ParkWashModel())->saveUserCount($result['result']['uid']);
        } else {
            // 返回错误码为未绑定小程序
            $result['errNo'] = 500;
        }
        return $result;
    }

    /**
     * 获取用户信息
     * @login
     * @param uid 用户ID
     * @return array
     * {
     * "errNo":0, // 错误码 0成功 -1失败
     * "message":"", //错误消息
     * "result":{
     *     "uid":10, //用户ID
     *     "telephone":"", //手机号
     *     "avatar":"", //头像地址
     *     "nickname":"", //昵称
     *     "sex":1, //性别 0未知 1男 2女
     *     "money":0, //余额 (分)
     *     "ispw":0, //是否已设置密码
     * }}
     */
    public function getUserInfo () {
        return (new ParkWashModel())->getUserInfo($this->_G['user']['uid']);
    }

    /**
     * 解绑小程序
     * @login
     * @return array
     * {
     * "errNo":0, // 错误码 0成功 -1失败
     * "message":"", //错误消息
     * "result":[]
     * }
     */
    public function unbindMiniprogram () {
        return (new XicheModel())->unbind($this->_G['user']['uid']);
    }

    /**
     * 发送短信验证码
     * @param $telephone 手机号
     * @return array
     * {
     * "errNo":0, // 错误码 0成功 -1失败
     * "message":"", //错误消息
     * "result":[]
     * }
     */
    public function sendSms () {
        return (new UserModel())->sendSmsCode($_POST);
    }

    /**
     * 获取门店列表
     * @param $adcode 城市代码 (贵阳 520100)
     * @param $lon 经度 (精确到 6 位)
     * @param $lon 维度 (精确到 6 位)
     * @param $lastid 分页参数
     * @param $lastorder 分页参数
     * @return array
     * {
     * "errNo":0,
     * "message":"",
     * "result":{
     *     "limit":10, //每页最大显示数
     *     "lastpage":"", //分页参数 (下一页携带的参数)
     *     "list":[{
     *             "id":1, //门店ID
     *             "name":"洗车", //门店名称
     *             "logo":"", //门店图片地址
     *             "address":"地址", //门店地址
     *             "market":"洗车半价", //活动描述
     *             "score":5, //评分
     *             "business_hours":"09:00-21:00", //营业时间
     *             "price":10, //洗车价(分)
     *             "order_count":1000, //下单数
     *             "status":1, //门店状态 1正常 0建设中
     *             "distance":0.81, //距离(公里)
     *             "location":“106.925389,27.728654”, //经纬度
     *      }]
     * }}
     */
    public function getStoreList () {
        $_POST['adcode'] = '520100';
        $_POST['lon'] = '105.989078';
        $_POST['lat'] = '26.704543';
        return (new ParkWashModel())->getStoreList($_POST);
    }

    /**
     * 获取附近洗车店
     * @param $adcode 城市代码 (贵阳 520100)
     * @param $lon 经度 (精确到 6 位)
     * @param $lon 维度 (精确到 6 位)
     * @param $distance 搜索范围 (可选 1-20 公里)
     * @return array
     * {
     * "errNo":0,
     * "message":"",
     * "result":[{
     *      "id":1, //门店ID
     *      "name":"洗车", //门店名称
     *      "logo":"", //门店图片地址
     *      "address":"地址", //门店地址
     *      "market":"洗车半价", //活动描述
     *      "score":5, //评分
     *      "business_hours":"09:00-21:00", //营业时间
     *      "price":10, //洗车价(分)
     *      "order_count":1000, //下单数
     *      "status":1, //门店状态 1正常 0建设中
     *      "distance":0.81, //距离(公里)
     *      "location":“106.925389,27.728654”, //经纬度
     * }]}
     */
    public function getNearbyStore () {
        $_POST['adcode'] = '520100';
        $_POST['lon'] = '105.989078';
        $_POST['lat'] = '26.704543';
        return (new ParkWashModel())->getNearbyStore($_POST);
    }

    /**
     * 获取汽车品牌
     * @return array
     * {
     * "errNo":0,
     * "message":"",
     * "result":[{
     *      "id":1, //品牌ID
     *      "name":"大众", //汽车品牌
     *      "logo":"", //图片地址
     *      "pinyin":"D", //拼音首写字母
     *      "ishot":0, //是否热门品牌
     * }]}
     */
    public function getBrandList () {
        return (new ParkWashModel())->getBrandList();
    }

    /**
     * 获取汽车车系
     * @param $brand_id 品牌ID
     * @return array
     * {
     * "errNo":0,
     * "message":"",
     * "result":[{
     *      "id":1, //车系ID
     *      "name":"昊锐", //车系
     * }]}
     */
    public function getSeriesList () {
        $_POST['brand_id'] = 1;
        return (new ParkWashModel())->getSeriesList($_POST);
    }

    /**
     * 获取停车场区域
     * @return array
     * {
     * "errNo":0,
     * "message":"",
     * "result":[{
     *      "id":1, //区域ID
     *      "floor":"负一楼", //楼层
     *      "area":"A区", //区域名称
     * }]}
     */
    public function getParkArea () {
        return (new ParkWashModel())->getParkArea($_POST);
    }

    /**
     * 获取我的车辆
     * @login
     * @return array
     * {
     * "errNo":0,
     * "message":"",
     * "result":[{
     *      id:10, //车辆ID
     *      area_id:1, //区域ID
     *      area_floor:"负一楼", //楼层
     *      area_name:"A区", //区域名称
     *      brand_id:15788, //品牌ID
     *      brand_logo:"", //品牌logo图片地址
     *      brand_name:"北汽幻速", //品牌名称
     *      car_number:"", //车牌号
     *      name:"北汽幻速 H2E 2016款 1.5 手动 时尚型", //车全称
     *      place:"A1001", //车位号
     *      series_id:1, //车系ID
     *      series_name:"北汽幻速", //车系名称
     *      isdefault:0, //是否默认选择
     * }]}
     */
    public function getCarport () {
        return (new ParkWashModel())->getCarport($this->_G['user']['uid']);
    }

    /**
     * 添加车辆
     * @login
     * @param $car_number 车牌号
     * @param $brand_id 品牌ID
     * @param $series_id 车系ID
     * @return array
     * {
     * "errNo":0, // 错误码 0成功 -1失败
     * "message":"", //错误消息
     * "result":[]
     * }
     */
    public function addCarport () {
        return (new ParkWashModel())->addCarport($this->_G['user']['uid'], $_POST);
    }

    /**
     * 编辑车辆
     * @login
     * @param $id 车辆ID
     * @param $car_number 车牌号
     * @param $brand_id 品牌ID
     * @param $series_id 车系ID
     * @param $area_id 区域ID
     * @param $place 车位号
     * @param $isdefault 是否设置默认车
     * @return array
     * {
     * "errNo":0, // 错误码 0成功 -1失败
     * "message":"", //错误消息
     * "result":[]
     * }
     */
    public function updateCarport () {
        return (new ParkWashModel())->updateCarport($this->_G['user']['uid'], $_POST);
    }

    /**
     * 删除车辆
     * @login
     * @param $id 车辆ID
     * @return array
     * {
     * "errNo":0, // 错误码 0成功 -1失败
     * "message":"", //错误消息
     * "result":[]
     * }
     */
    public function deleteCarport () {
        return (new ParkWashModel())->deleteCarport($this->_G['user']['uid'], $_POST);
    }

    /**
     * 获取预约排班
     * @param $store_id 门店ID
     * @return array
     * {
     * "errNo":0,
     * "message":"",
     * "result":[{
     *      "id":1, //排班ID
     *      "today":"2019-03-29", //预约日期
     *      "start_time":"08:30", //预约开始时间
     *      "end_time":"09:30", //预约结束时间
     *      "amount":2, //可预约数
     * }]}
     */
    public function getPoolList () {
        $_POST['store_id'] = 1;
        return (new ParkWashModel())->getPoolList($_POST);
    }

    /**
     * 获取洗车店洗车套餐
     * @param $store_id 门店ID
     * @return array
     * {
     * "errNo":0,
     * "message":"",
     * "result":[{
     *      "id":1, //套餐项目ID
     *      "name":"车辆外观", //项目名称
     *      "price":1000, //价格 (分)
     * }]}
     */
    public function getStoreItem () {
        $_POST['store_id'] = 1;
        return (new ParkWashModel())->getStoreItem($_POST);
    }

    /**
     * 下单
     * @login
     * @param store_id 门店ID
     * @param carport_id 车辆ID
     * @param area_id 区域ID
     * @param place 车位号
     * @param pool_id 排班ID
     * @param items 套餐ID (多个用逗号分隔)
     * @return array
     * {
     * "errNo":0,
     * "message":"",
     * "result":[{
     *      "tradeid":1, //交易单ID (用于后续发起支付)
     * }]}
     */
    public function createCard () {
        return (new ParkWashModel())->createCard($this->_G['user']['uid'], $_POST);
    }

    /**
     * 查询支付是否成功
     * @login
     * @param tradeid 交易单ID ( createCard 接口获取)
     * @return array
     * {
     * "errNo":0, // 错误码 0支付成功 -1未支付成功
     * "message":"", // 返回信息
     * "result":[]
     * }
     */
    public function payQuery () {
        return (new TradeModel())->payQuery($this->_G['user']['uid'], getgpc('tradeid'));
    }

    /**
     * 获取通知列表
     * @login
     */
    public function getNoticeList () {
        return (new ParkWashModel())->getNoticeList($this->_G['user']['uid'], $_POST);
    }

}
