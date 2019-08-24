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

    public function __ratelimit ()
    {
        return [
            'login'              => ['interval' => 1000],
            'getUserInfo'        => [],
            'getLastOrderInfo'   => [],
            'unbindMiniprogram'  => [],
            'sendSms'            => ['rule' => '5|10|20', 'interval' => 1000],
            'checkSmsCode'       => [],
            'getImgCode'         => [],
            'getStoreList'       => [],
            'getNearbyStore'     => [],
            'getXicheDeviceList' => [],
            'getBrandList'       => [],
            'getSeriesList'      => [],
            'getParkArea'        => [],
            'getCarport'         => [],
            'addCarport'         => [],
            'updateCarport'      => [],
            'deleteCarport'      => [],
            'getPoolList'        => [],
            'getStoreItem'       => [],
            'createCard'         => ['interval' => 2000],
            'payQuery'           => ['interval' => 2000],
            'getNoticeList'      => [],
            'cancelOrder'        => [],
            'confirmOrder'       => [],
            'getOrderList'       => [],
            'getOrderInfo'       => [],
            'updatePlace'        => ['interval' => 2000],
            'recharge'           => ['interval' => 2000],
            'getTradeList'       => [],
            'getCardTypeList'    => [],
            'getCardList'        => [],
            'deleteMemberCard'   => [],
            'renewalsCard'       => ['interval' => 2000]
        ];
    }

    /**
     * 登录
     * @description 只支持微信小程序登录
     * @param *code 小程序登录凭证
     * @param encryptedData 手机号加密数据
     * @param iv 加密算法的初始向量（当encryptedData填写时，此值必填）
     * @param telephone 手机号
     * @param msgcode 短信验证码（当telephone填写时，此值必填）
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
        $reponse = $reponse['result'];
        $reponse['type'] = 'mp';
        if ($loginInfo = (new XicheModel())->checkLogin($reponse, ['clienttype' => 'mp'])) {
            (new ParkWashModel())->saveUserCount($loginInfo['uid']);
            $userInfo = (new UserModel())->getUserInfo($loginInfo['uid']);
            if ($userInfo['errorcode'] !== 0) {
                return $userInfo;
            }
            $userInfo['result']['token'] = $loginInfo['token'];
            return $userInfo;
        }

        if (empty($reponse['telephone'])) {
            // 手机号方式登录
            $reponse['telephone'] = getgpc('telephone');
            $reponse['msgcode']   = strval(getgpc('msgcode'));
        }

        // 绑定小程序
        $reponse['__authcode'] = $reponse['authcode'];
        $parkwashModel = new ParkWashModel();
        $result = $parkwashModel->miniprogramLogin($reponse);
        if ($result['errorcode'] === 0) {
            // 插入 userCount 表
            $parkwashModel->saveUserCount($result['result']['uid']);
        } else {
            // 兼容前端小程序逻辑
            return success([
                'token' => ''
            ]);
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
     *     "give":0, //充值赠送金额 (分)
     *     "ispw":0, //是否已设置密码
     *     "vip_status"1, //vip状态 0不是vip 1未过期 -1已过期
     *     "vip_expire","", //vip截止时间
     * }}
     */
    public function getUserInfo ()
    {
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
     *     "status":1, //订单状态(-1已取消1已支付3服务中4已完成5确认完成)
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
        return (new XicheModel())->unbind($this->_G['user']['uid'], 'mp');
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
        $_POST['len'] = 4;
        return (new UserModel())->sendSmsCode($_POST);
        // 不启用图片验证码
        $userModel = new UserModel();
        if (!$userModel->checkImgCode(getgpc('imgcode'))) {
            return error('验证码错误');
        }
        $result = $userModel->sendSmsCode($_POST);
        if ($result['errorcode'] === 0) {
            $userModel->checkImgCode(getgpc('imgcode'), false);
        }
        return $result;
    }

    /**
     * 获取图片验证码
     * @return jpg
     */
    public function getImgCode () {
        $checkcode = new \app\library\Checkcode();
        $checkcode->doimage();
        (new UserModel())->saveImgCode($checkcode->get_code());
        return null;
    }

    /**
     * 验证短信验证码
     * @param *telephone 手机号
     * @param *code 短信验证码
     * @return array
     * {
     * "errNo":0, // 错误码 0成功 -1失败
     * "message":"", //错误消息
     * "result":[]
     * }
     */
    public function checkSmsCode () {
        if ((new UserModel())->checkSmsCode($_POST['telephone'], $_POST['code'])) {
            return success('OK');
        }
        return error('验证码错误');
    }

    /**
     * 获取洗车店列表 <span style="color:red">改动</span>
     * @param *adcode 城市代码(贵阳520100)
     * @param *lon 经度(精确到6位)
     * @param *lat 维度(精确到6位)
     * @param name string 店铺名称 <span style="color:red">新增搜索字段</span>
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
     *         "name":"", //门店名称
     *         "logo":"", //门店图片地址
     *         "address":"", //门店地址
     *         "tel":"", //电话号码
     *         "market":"半价", //活动描述
     *         "business_hours":"", //营业时间
     *         "is_business_hour":1, //是否在营业时间 1是 0否
     *         "price":10, //洗车价(分)
     *         "order_count":1, //下单数
     *         "status":1, //门店状态 1正常 0建设中
     *         "distance":0, //距离(公里)
     *         "location":“”, //经纬度
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
        //$xiches = $model->getNearbyXicheDevice($_POST);
        return success([
            'stores' => $stores['result']
            //'xiches' => $xiches['result']
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
     * 获取车型 <span style="color:red">新增</span>
     * @return array
     * {
     * "errNo":0, //错误码 0成功 -1失败
     * "message":"",
     * "result":[{
     *      "id":1, //车型ID
     *      "name":"小型车", //车型名称
     * }]}
     */
    public function getCarType ()
    {
        return (new ParkWashModel())->getCarType();
    }

    /**
     * 获取停车场区域
     * @param *store_id string 店铺ID
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
     * 获取我的车辆 <span style="color:red">改动</span>
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
     *      brand_id:1, //品牌ID
     *      brand_name:"", //品牌名称
     *      car_number:"", //车牌号
     *      name:"", //车全称
     *      place:"", //车位号
     *      car_type_id:1, //车型ID <span style="color:red">新增字段</span>
     *      car_type_name:"", //车型名称 <span style="color:red">新增字段</span>
     *      isdefault:0, //是否默认选择,
     *      isvip:1, //是否vip 1是 0不是vip或已过期
     * }]}
     */
    public function getCarport ()
    {
        return (new ParkWashModel())->getCarport($this->_G['user']['uid']);
    }

    /**
     * 添加车辆 <span style="color:red">改动</span>
     * @login
     * @param *car_number 车牌号
     * @param brand_id 品牌ID
     * @param *car_type_id string 车型ID <span style="color:red">新增字段</span>
     * @return array
     * {
     * "errNo":0, // 错误码 0成功 -1失败
     * "message":"", //错误消息
     * "result":[]
     * }
     */
    public function addCarport ()
    {
        return (new ParkWashModel())->addCarport($this->_G['user']['uid'], $_POST);
    }

    /**
     * 编辑车辆 <span style="color:red">改动</span>
     * @login
     * @param *id 车辆ID
     * @param *car_number 车牌号
     * @param brand_id 品牌ID
     * @param car_type_id string 车型ID <span style="color:red">新增字段</span>
     * @param area_id 区域ID
     * @param place 车位号
     * @param isdefault 是否默认车
     * @return array
     * {
     * "errNo":0, // 错误码 0成功 -1失败
     * "message":"", //错误消息
     * "result":[]
     * }
     */
    public function updateCarport ()
    {
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
    public function deleteCarport ()
    {
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
     * 获取洗车店洗车套餐 <span style="color:red">改动</span>
     * @login
     * @param *store_id 门店ID
     * @param *car_type_id string 车型ID <span style="color:red">新增字段</span>
     * @return array
     * {
     * "errNo":0, //错误码 0成功 -1失败
     * "message":"",
     * "result":[{
     *      "id":1, //套餐项目ID
     *      "name":"车辆外观", //项目名称
     *      "price":0, //价格 (分)
     *      "firstorder":0, //首单免费 1是 0否
     * }]}
     */
    public function getStoreItem ()
    {
        return (new ParkWashModel())->getStoreItem($this->_G['user']['uid'], $_POST);
    }

    /**
     * 下单
     * @login
     * @param *store_id 门店ID
     * @param *carport_id 车辆ID
     * @param area_id 区域ID
     * @param place 车位号
     * @param *pool_id 排班ID
     * @param *items 套餐ID
     * @param *payway 支付方式(cbpay车币支付wxpaywash小程序支付)
     * @return array
     * {
     * "errNo":0, //错误码 0成功 -1失败
     * "message":"",
     * "result":{
     *      "tradeid":1, //交易单ID (用于后续发起支付)
     * }}
     */
    public function createCard ()
    {
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
     * 用户确认完成订单
     * @login
     * @param *orderid 订单ID
     * @return array
     * {
     * "errNo":0, //错误码 0成功 -1失败
     * "message":"", //返回信息
     * "result":[]
     * }
     */
    public function confirmOrder () {
        return (new ParkWashModel())->confirmOrder($this->_G['user']['uid'], $_POST);
    }

    /**
     * 我的订单 <span style="color:red">改动</span>
     * @login
     * @param status 订单状态(-1已取消1已支付3服务中4已完成5确认完成)
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
     *          "order_code":"", //订单号
     *          "car_number":"", //车牌号
     *          "place":"", //车位号
     *          "pay":0, //支付金额 (分)
     *          "refundpay":0, //自助洗车退款金额 (分)
     *          "payway":"车币支付", //支付方式
     *          "items":[], //洗车套餐JSON
     *          "order_time":"", //预约时间
     *          "create_time":"", //下单时间
     *          "update_time":"", //更新时间
     *          "brand_name":"", //汽车品牌名
     *          "car_type_name":"", //车型 <span style="color:red">新增字段</span>
     *          "area_floor":"", //楼层
     *          "area_name":"", //区域
     *          "store_name":"" //服务网点
     *          "status":1, //订单状态 (-1已取消 1已支付 3服务中 4已完成 5确认完成)
     *      }]
     * }}
     */
    public function getOrderList () {
        return (new ParkWashModel())->getOrderList($this->_G['user']['uid'], $_POST);
    }

    /**
     * 获取订单详情 <span style="color:red">改动</span>
     * @login
     * @param *orderid 订单ID
     * @return array
     * {
     * "errNo":0, //错误码 0成功 -1失败
     * "message":"", //返回信息
     * "result":{
     *      "id":4, //订单ID
     *      "order_type":"parkwash", //订单类型 (xc自助洗车 parkwash停车场洗车)
     *      "order_code":"", //订单号
     *      "store_id":1, //门店ID
     *      "brand_id":1, //品牌ID
     *      "area_id":1, //区域ID
     *      "car_number":"", //车牌号
     *      "place":"A002", //车位号
     *      "pay":0, //支付金额 (分)
     *      "refundpay":0, //自助洗车退款金额 (分)
     *      "payway":"车币支付", //支付方式
     *      "items":[], //洗车套餐JSON
     *      "order_time":"", //预约时间
     *      "create_time":"", //下单时间
     *      "update_time":"", //更新时间
     *      "brand_name":"斯柯达", //汽车品牌名
     *      "car_type_id":1, //车型ID <span style="color:red">新增字段</span>
     *      "car_type_name":"", //车型 <span style="color:red">新增字段</span>
     *      "area_floor":"", //楼层
     *      "area_name":"", //区域
     *      "store_name":"" //服务网点
     *      "location":"", //经纬度
     *      "status":1, //订单状态 (-1已取消 1已支付 3服务中 4已完成 5确认完成)
     * }}
     */
    public function getOrderInfo ()
    {
        return (new ParkWashModel())->getOrderInfo($this->_G['user']['uid'], $_POST);
    }

    /**
     * 修改订单车位
     * @login
     * @description 订单状态为已支付才能修改订单车位
     * @param *orderid 订单ID
     * @param *place 车位号
     * @param *area_id 区域ID
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
     * 获取充值卡类型
     * @return array
     * {
     * "errNo":0, // 错误码 0成功 -1失败
     * "message":"", //错误消息
     * "result":[{
     *     "id":10, //卡ID
     *     "price":1, //价格 (元)
     *     "give":0, //赠送金额 (元)
     * }]}
     */
    public function getRechargeCardType ()
    {
        return (new ParkWashModel())->getRechargeCardType();
    }

    /**
     * 充值 <span style="color:red">改动</span>
     * @login
     * @param *type_id string 充值卡类型ID
     * @param *payway 支付方式(wxpaywash小程序支付)
     * @param promo_name string 推荐人 <span style="color:red">新增字段</span>
     * @return array
     * {
     * "errNo":0, //错误码 0成功 -1失败
     * "message":"",
     * "result":{
     *      "tradeid":1, //交易单ID (用于后续发起支付)
     * }}
     */
    public function recharge ()
    {
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

    /**
     * 获取会员卡类型
     * @return array
     * {
     * "errNo":0, // 错误码 0成功 -1失败
     * "message":"", //错误消息
     * "result":[{
     *     "id":10, //卡ID
     *     "name":"月卡", //卡名
     *     "price":"1", //价格 (分)
     *     "duration":"1个月", //免费时长
     * }]}
     */
    public function getCardTypeList () {
        return (new ParkWashModel())->getCardTypeList();
    }

    /**
     * 获取我的会员卡
     * @login
     * @return array
     * {
     * "errNo":0, // 错误码 0成功 -1失败
     * "message":"", //错误消息
     * "result":[{
     *     "id":10, //ID
     *     "car_number":"", //车牌号
     *     "end_time":"1", //到期时间
     *     "status":1, //状态 0禁用 1正常 -1已过期
     * }]}
     */
    public function getCardList () {
        return (new ParkWashModel())->getCardList($this->_G['user']['uid']);
    }

    /**
     * 删除会员卡
     * @login
     * @param *id 会员卡ID
     * @return array
     * {
     * "errNo":0, //错误码 0成功 -1失败
     * "message":"",
     * "result":[]
     * }
     */
    public function deleteMemberCard () {
        return (new ParkWashModel())->deleteMemberCard($this->_G['user']['uid'], $_POST);
    }

    /**
     * 会员卡开卡/续费
     * @login
     * @param *car_number 车牌号
     * @param *card_type_id 会员卡类型ID
     * @param *payway 支付方式(cbpay车币支付wxpaywash小程序支付)
     * @return array
     * {
     * "errNo":0, //错误码 0成功 -1失败
     * "message":"",
     * "result":{
     *      "tradeid":1, //交易单ID (用于后续发起支付)
     * }}
     */
    public function renewalsCard () {
        return (new ParkWashModel())->renewalsCard($this->_G['user']['uid'], $_POST);
    }

    public function task () {
        \DebugLog::_debug(false);
        $timewheel = new \app\library\TimeWheel();
        $timer = $timewheel->tick();
        return (new ParkWashModel())->task(implode('', $timer));
    }

}
