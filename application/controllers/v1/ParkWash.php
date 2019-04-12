<?php

namespace app\controllers;

use ActionPDO;
use app\models\ParkWashModel;
use app\models\XicheModel;
use app\models\UserModel;

/**
 * 停车场洗车前端接口
 * @Date 2019-04-03
 */
class ParkWash extends ActionPDO {

    /**
     * 登录
     * @description 只支持微信小程序登录
     * @param *encryptedData 手机号加密数据
     * @param *iv 加密算法的初始向量
     * @param *code 小程序登录凭证
     * @return array
     * {
     * "errNo":0, // 错误码 0成功 -1失败
     * "message":"", //错误消息
     * "result":{
     *     "uid":10, //用户ID
     *     "telephone":"", //手机号
     *     "avatar":"", //头像地址
     *     "nickname":"", //昵称
     *     "gender":1, //性别 0未知 1男 2女
     *     "money":0, //余额 (分)
     *     "ispw":0, //是否已设置密码
     *     "token":"", //登录凭证
     * }}
     */
    public function login () {

        // 客户端是微信
        if (CLIENT_TYPE != 'wx') {
            return error('当前环境不支持登录');
        }

        $code = getgpc('code');
        if (empty($code)) {
            return error('请填写小程序登录凭证');
        }

        $wxConfig = getSysConfig('parkwash', 'wx');
        $jssdk = new \app\library\JSSDK($wxConfig['appid'], $wxConfig['appsecret']);
        $reponse = $jssdk->wXBizDataCrypt([
            'code' => $code,
            'getPhoneNumber' => [
                'encryptedData' => getgpc('encryptedData'),
                'iv' => getgpc('iv')
            ]
        ]);
        if ($reponse['errorcode'] !== 0) {
            return $reponse;
        }
        if ($loginInfo = (new XicheModel())->checkLogin($reponse['result'], ['clienttype' => 'mp'])) {
            (new ParkWashModel())->saveUserCount($loginInfo['uid']);
            $userInfo = (new UserModel())->getUserInfo($loginInfo['uid']);
            $userInfo['result']['token'] = $loginInfo['token'];
            return $userInfo;
        }

        // 绑定小程序
        $post = $reponse['result'];
        $post['__authcode'] = $post['authcode'];
        $parkwashModel = new ParkWashModel();
        $result = $parkwashModel->miniprogramLogin($post);
        if ($result['errorcode'] === 0) {
            // 插入 userCount 表
            $parkwashModel->saveUserCount($result['result']['uid']);
        }
        return $result;
    }

    /**
     * 获取用户信息
     * @login
     * @return array
     * {
     * "errNo":0, // 错误码 0成功 -1失败
     * "message":"", //错误消息
     * "result":{
     *     "uid":10, //用户ID
     *     "telephone":"", //手机号
     *     "avatar":"", //头像地址
     *     "nickname":"", //昵称
     *     "gender":1, //性别 0未知 1男 2女
     *     "money":0, //余额 (分)
     *     "ispw":0, //是否已设置密码
     * }}
     */
    public function getUserInfo () {
        return (new ParkWashModel())->getUserInfo($this->_G['user']['uid']);
    }

    /**
     * 获取用户最近一个订单的状态
     * @login
     * @return array
     * {
     * "errNo":0, // 错误码 0成功 -1失败
     * "message":"", //错误消息
     * "result":{
     *     "id":5, //订单ID
     *     "status":1, //最近一个停车场洗车订单状态(-1已取消1已支付2已接单3服务中4已完成)
     *     "create_time":"" //下单时间
     * }}
     */
    public function getLastOrderInfo () {
        return (new ParkWashModel())->getLastOrderInfo($this->_G['user']['uid']);
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
     * @param *telephone 手机号
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
     * 获取洗车店列表
     * @param *adcode 城市代码(贵阳520100)
     * @param *lon 经度(精确到6位)
     * @param *lat 维度(精确到6位)
     * @param lastpage 分页参数
     * @return array
     * {
     * "errNo":0, //错误码 0成功 -1失败
     * "message":"",
     * "result":{
     *     "limit":10, //每页最大显示数
     *     "lastpage":"", //分页参数 (下一页携带的参数)
     *     "list":[{
     *         "id":1, //门店ID
     *         "name":"洗车", //门店名称
     *         "logo":"", //门店图片地址
     *         "address":"地址", //门店地址
     *         "tel":"", //电话号码
     *         "market":"洗车半价", //活动描述
     *         "score":5, //评分
     *         "business_hours":"09:00-21:00", //营业时间
     *         "is_business_hour":1, //是否在营业时间 1是 0否
     *         "price":10, //洗车价(分)
     *         "order_count":1000, //下单数
     *         "status":1, //门店状态 1正常 0建设中
     *         "distance":0.81, //距离(公里)
     *         "location":“106.925389,27.728654”, //经纬度
     *      }]
     * }}
     */
    public function getStoreList () {
//        $_POST['adcode'] = '520100';
//        $_POST['lon'] = '105.989078';
//        $_POST['lat'] = '26.704543';
        return (new ParkWashModel())->getStoreList($_POST);
    }

    /**
     * 获取附近洗车店与洗车机
     * @param *adcode 城市代码(贵阳520100)
     * @param *lon 经度(精确到6位)
     * @param *lat 维度(精确到6位)
     * @param distance 搜索范围最大公里数(可选1-50公里，默认1公里)
     * @return array
     * {
     * "errNo":0, //错误码 0成功 -1失败
     * "message":"",
     * "result":{
     *     "stores":[{
     *         "id":1, //门店ID
     *         "store_name":"洗车", //门店名称
     *         "logo":"", //门店图片地址
     *         "address":"地址", //门店地址
     *         "tel":"", //电话号码
     *         "market":"洗车半价", //活动描述
     *         "score":5, //评分
     *         "business_hours":"09:00-21:00", //营业时间
     *         "is_business_hour":1, //是否在营业时间 1是 0否
     *         "price":10, //洗车价(分)
     *         "order_count":1000, //下单数
     *         "status":1, //门店状态 1正常 0建设中
     *         "distance":0.81, //距离(公里)
     *         "location":“106.925389,27.728654”, //经纬度
     *     }],
     *     "xiches":[{
     *         "location":"106.618478,25.953443", //同一组洗车机中心点坐标
     *         "distance":0, //距离(公里)
     *         "store_name":"A", //场地
     *         "use_state":1 //状态 0不空闲 1空闲 (组内洗车机有一台空闲，此状态就为空闲，否则为不空闲)
     *         "list":[{
     *             "id":1, //洗车机ID
     *             "areaname":"", //洗车机名称
     *             "address":"地址", //洗车机地址
     *             "price":10, //洗车价(分)
     *             "duration":20, //洗车时长 (分钟)
     *             "order_count":1000, //下单数
     *             "distance":0.81, //距离(公里)
     *             "location":“106.925389,27.728654”, //经纬度
     *             "use_state":1 //状态 0离线 1空闲 2使用中
     *         }]
     *     }]
     * }}
     */
    public function getNearbyStore () {
//        $_POST['adcode'] = '520100';
//        $_POST['lon'] = '106.649778';
//        $_POST['lat'] = '26.663843';
        $model = new ParkWashModel();
        $stores = $model->getNearbyStore($_POST);
        $xiches = $model->getNearbyXicheDevice($_POST);
        return success([
            'stores' => $stores['result'],
            'xiches' => $xiches['result']
        ]);
    }

    /**
     * 获取自助洗车机列表
     * @param *adcode 城市代码(贵阳520100)
     * @param *lon 经度(精确到6位)
     * @param *lat 维度(精确到6位)
     * @param lastpage 分页参数
     * @return array
     * {
     * "errNo":0, //错误码 0成功 -1失败
     * "message":"",
     * "result":{
     *     "limit":10, //每页最大显示数
     *     "lastpage":"", //分页参数 (下一页携带的参数)
     *     "list":[{
     *         "id":1, //洗车机ID
     *         "areaname":"", //洗车机名称
     *         "address":"地址", //洗车机地址
     *         "price":10, //洗车价(分)
     *         "duration":20, //洗车时长 (分钟)
     *         "order_count":1000, //下单数
     *         "distance":0.81, //距离(公里)
     *         "location":“106.925389,27.728654”, //经纬度
     *         "use_state":0 //状态 0离线 1空闲 2使用中
     *      }]
     * }}
     */
    public function getXicheDeviceList () {
//        $_POST['adcode'] = '520100';
//        $_POST['lon'] = '105.989078';
//        $_POST['lat'] = '26.704543';
        return (new ParkWashModel())->getXicheDeviceList($_POST);
    }

    /**
     * 获取汽车品牌
     * @return array
     * {
     * "errNo":0, //错误码 0成功 -1失败
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
     * @param *brand_id 品牌ID
     * @return array
     * {
     * "errNo":0, //错误码 0成功 -1失败
     * "message":"",
     * "result":[{
     *      "id":1, //车系ID
     *      "name":"昊锐", //车系
     * }]}
     */
    public function getSeriesList () {
        return (new ParkWashModel())->getSeriesList($_POST);
    }

    /**
     * 获取停车场区域
     * @return array
     * {
     * "errNo":0, //错误码 0成功 -1失败
     * "message":"",
     * "result":[{
     *      "id":1, //区域ID
     *      "floor":"负一楼", //楼层
     *      "name":"A区", //区域名称
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
     * "errNo":0, //错误码 0成功 -1失败
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
     * @param *car_number 车牌号
     * @param *brand_id 品牌ID
     * @param *series_id 车系ID
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
     * @param *id 车辆ID
     * @param *car_number 车牌号
     * @param *brand_id 品牌ID
     * @param *series_id 车系ID
     * @param *area_id 区域ID
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
     * @param *id 车辆ID
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
     * @param *store_id 门店ID
     * @return array
     * {
     * "errNo":0, //错误码 0成功 -1失败
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
        return (new ParkWashModel())->getPoolList($_POST);
    }

    /**
     * 获取洗车店洗车套餐
     * @param *store_id 门店ID
     * @return array
     * {
     * "errNo":0, //错误码 0成功 -1失败
     * "message":"",
     * "result":[{
     *      "id":1, //套餐项目ID
     *      "name":"车辆外观", //项目名称
     *      "price":1000, //价格 (分)
     * }]}
     */
    public function getStoreItem () {
        return (new ParkWashModel())->getStoreItem($_POST);
    }

    /**
     * 下单
     * @login
     * @param *store_id 门店ID
     * @param *carport_id 车辆ID
     * @param *area_id 区域ID
     * @param place 车位号
     * @param *pool_id 排班ID
     * @param *items 套餐ID(多个用逗号分隔)
     * @param *payway 支付方式(cbpay车币支付wxpaywash小程序支付)
     * @return array
     * {
     * "errNo":0, //错误码 0成功 -1失败
     * "message":"",
     * "result":{
     *      "tradeid":1, //交易单ID (用于后续发起支付)
     * }}
     */
    public function createCard () {
        return (new ParkWashModel())->createCard($this->_G['user']['uid'], $_POST);
    }

    /**
     * 查询支付是否成功
     * @login
     * @param *tradeid 交易单ID(createCard/recharge接口获取)
     * @return array
     * {
     * "errNo":0, // 错误码 0支付成功 -1未支付成功
     * "message":"", // 返回信息
     * "result":{
     *     "orderid":1 //洗车订单ID (只有支付成功才会返回该值)
     * }}
     */
    public function payQuery () {
        return (new ParkWashModel())->payQuery($this->_G['user']['uid'], $_POST);
    }

    /**
     * 获取微信支付JSAPI支付参数
     * @route wxpaywash/api
     * @param *tradeid 交易单ID(createCard/recharge接口获取)
     * @return array
     * {
     * "errNo":0, // 错误码 0成功 -1失败
     * "message":"", // 返回信息
     * "result":{
     *     "appId":"",
     *     "timestamp":"",
     *     "nonceStr":"",
     *     "package":"",
     *     "signType":"",
     *     "paySign":""
     * }}
     */
    protected function wxpayjs () {}

    /**
     * 获取通知列表
     * @login
     * @param lastpage 分页参数
     * @return array
     * {
     * "errNo":0, //错误码 0成功 -1失败
     * "message":"",
     * "result":{
     *     "limit":10, //每页最大显示数
     *     "lastpage":"", //分页参数 (下一页携带的参数)
     *     "list":[{
     *         "id":1, //通知ID
     *         "title":"", //通知标题
     *         "content":"", //通知内容
     *         "is_read":0, //是否已读 0未读 1已读
     *         "create_time":"", //通知时间
     *      }]
     * }}
     */
    public function getNoticeList () {
        return (new ParkWashModel())->getNoticeList($this->_G['user']['uid'], $_POST);
    }

    /**
     * 用户取消订单
     * @login
     * @param *orderid 订单ID
     * @return array
     * {
     * "errNo":0, //错误码 0成功 -1失败
     * "message":"", //返回信息
     * "result":[]
     * }
     */
    public function cancelOrder () {
        return (new ParkWashModel())->cancelOrder($this->_G['user']['uid'], $_POST);
    }

    /**
     * 我的订单
     * @login
     * @param status 订单状态(-1已取消1已支付2已接单3服务中4已完成)，搜索多个状态用逗号分隔，默认为所有
     * @param lastpage 分页参数
     * @return array
     * {
     * "errNo":0, //错误码 0成功 -1失败
     * "message":"",
     * "result":{
     *     "limit":10, //每页最大显示数
     *     "lastpage":"", //分页参数 (下一页携带的参数)
     *     "list":[{
     *          "id":5, //订单ID
     *          "order_type":"parkwash", //订单类型 (xc自助洗车 parkwash停车场洗车)
     *          "order_code":"201904010900005", //订单号
     *          "car_number":"", //车牌号
     *          "place":"A002", //车位号
     *          "pay":0, //支付金额 (分)
     *          "refundpay":0, //自助洗车退款金额 (分)
     *          "payway":"车币支付", //支付方式
     *          "items":[], //洗车套餐JSON
     *          "order_time":"2019-04-01 14:00:00", //预约时间
     *          "create_time":"2019-04-01 09:00:00", //下单时间
     *          "brand_name":"斯柯达", //汽车品牌名
     *          "series_name":"昊锐", //汽车款型
     *          "area_floor":"负一楼", //楼层
     *          "area_name":"A区", //区域
     *          "store_name":"门店0" //服务网点
     *          "status":1, //订单状态 (-1已取消 1已支付 2已接单 3服务中 4已完成)
     *      }]
     * }}
     */
    public function getOrderList () {
        return (new ParkWashModel())->getOrderList($this->_G['user']['uid'], $_POST);
    }

    /**
     * 获取订单详情
     * @login
     * @param *orderid 订单ID
     * @return array
     * {
     * "errNo":0, //错误码 0成功 -1失败
     * "message":"", //返回信息
     * "result":{
     *      "id":4, //订单ID
     *      "order_type":"parkwash", //订单类型 (xc自助洗车 parkwash停车场洗车)
     *      "order_code":"201904010900005", //订单号
     *      "store_id":1, //门店ID
     *      "brand_id":1, //品牌ID
     *      "series_id":1, //车系ID
     *      "area_id":1, //区域ID
     *      "car_number":"", //车牌号
     *      "place":"A002", //车位号
     *      "pay":0, //支付金额 (分)
     *      "refundpay":0, //自助洗车退款金额 (分)
     *      "payway":"车币支付", //支付方式
     *      "items":[], //洗车套餐JSON
     *      "order_time":"2019-04-01 14:00:00", //预约时间
     *      "create_time":"2019-04-01 09:00:00", //下单时间
     *      "brand_name":"斯柯达", //汽车品牌名
     *      "series_name":"昊锐", //汽车款型
     *      "area_floor":"负一楼", //楼层
     *      "area_name":"A区", //区域
     *      "store_name":"门店0" //服务网点
     *      "location":"106.328468,25.844113", //经纬度
     *      "status":1, //订单状态 (-1已取消 1已支付 2已接单 3服务中 4已完成)
     *      "sequence":[{
     *          "title":"下单成功，等待商家接单", //订单状态改变事件
     *          "create_time":"2019-04-02 16:38:43" //事件时间
     *      }]
     * }
     */
    public function getOrderInfo () {
        return (new ParkWashModel())->getOrderInfo($this->_G['user']['uid'], $_POST);
    }

    /**
     * 修改订单车位
     * @login
     * @description 订单状态为已支付或已接单才能修改订单车位
     * @param *orderid 订单ID
     * @param *place 车位号
     * @return array
     * {
     * "errNo":0, //错误码 0成功 -1失败
     * "message":"", //返回信息
     * "result":[]
     * }
     */
    public function updatePlace () {
        return (new ParkWashModel())->updatePlace($this->_G['user']['uid'], $_POST);
    }

    /**
     * 充值
     * @login
     * @param *money 充值金额(分)
     * @param *payway 支付方式(wxpaywash小程序支付)
     * @return array
     * {
     * "errNo":0, //错误码 0成功 -1失败
     * "message":"",
     * "result":{
     *      "tradeid":1, //交易单ID (用于后续发起支付)
     * }}
     */
    public function recharge () {
        return (new ParkWashModel())->recharge($this->_G['user']['uid'], $_POST);
    }

    /**
     * 获取个人交易记录
     * @login
     * @param lastpage 分页参数
     * @return array
     * {
     * "errNo":0, //错误码 0成功 -1失败
     * "message":"",
     * "result":{
     *     "limit":10, //每页最大显示数
     *     "lastpage":"", //分页参数 (下一页携带的参数)
     *     "list":[{
     *          "id":5, //交易ID
     *          "mark":"+", //交易类型 +余额增加 -余额减少
     *          "money":100, //变动金额 (分)
     *          "title":"充值成功", //标题
     *          "create_time":"", //创建时间
     *      }]
     * }}
     */
    public function getTradeList () {
        return (new ParkWashModel())->getTradeList($this->_G['user']['uid'], $_POST);
    }

    public function task () {
        $timewheel = new \app\library\TimeWheel();
        $timer = $timewheel->tick();
        return (new ParkWashModel())->task(implode('', $timer));
    }

}
