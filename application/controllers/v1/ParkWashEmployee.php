<?php

namespace app\controllers;

use ActionPDO;
use app\models\ParkWashEmployeeModel;

/**
 * 停车场洗车员工APP端接口
 * @Date 2019-06-17
 */
class ParkWashEmployee extends ActionPDO {

    public function __ratelimit ()
    {
        return [
            'login'           => ['interval' => 1000],
            'setpw'           => ['interval' => 1000],
            'getEmployeeInfo' => ['interval' => 1000],
            'getOrderList'    => [],
            'getOrderCount'   => ['interval' => 1000],
            'getOrderInfo'    => ['interval' => 1000],
            'getHelperList'   => ['interval' => 1000],
            'checkTakeOrder'  => ['interval' => 1000],
            'takeOrder'       => ['interval' => 2000],
            'completeOrder'   => ['interval' => 2000],
            'remarkOrder'     => ['interval' => 1000],
            'onLine'          => ['interval' => 1000],
            'onRemind'        => ['interval' => 1000],
            'statistics'      => ['interval' => 1000]
        ];
    }

    /**
     * 登录
     * @param *telephone 手机号
     * @param *msgcode 短信验证码（短信或密码任选其一）
     * @param *password 密码（短信或密码任选其一）
     * @param *clientapp APP端类型（android/ios）
     * @param *stoken 设备码（用于APP消息推送）
     * @return array
     * {
     * "errNo":0, // 错误码 0成功 -1失败
     * "message":"", //错误消息
     * "result":{
     *     "id":1, //用户ID
     *     "telephone":"", //手机号
     *     "avatar":"", //头像地址
     *     "realname":"", //姓名
     *     "gender":1, //性别 0未知 1男 2女
     *     "store_name":"", //工作店铺
     *     "state_online":0, //在线状态 1在线 0离线
     *     "state_remind":0, //订单提醒状态 1启用 0关闭
     *     "token":"", //登录凭证
     * }}
     */
    public function login ()
    {
        return (new ParkWashEmployeeModel())->login($_POST, [
            'clienttype' => 'yee',
            'clientapp'  => $_POST['clientapp'],
            'stoken'     => $_POST['stoken']
        ]);
    }

    /**
     * 设置密码
     * @param *telephone 手机号
     * @param *msgcode 短信验证码
     * @param *password 密码（6-32位密码）
     * @return array
     * {
     * "errNo":0, // 错误码 0成功 -1失败
     * "message":"", //错误消息
     * "result":[]
     * }
     */
    public function setpw ()
    {
        return (new ParkWashEmployeeModel())->setpw($_POST);
    }

    /**
     * 获取员工信息
     * @login
     * @return array
     * {
     * "errNo":0, // 错误码 0成功 -1失败
     * "message":"", //错误消息
     * "result":{
     *     "id":1, //用户ID
     *     "telephone":"", //手机号
     *     "avatar":"", //头像地址
     *     "realname":"", //姓名
     *     "gender":1, //性别 0未知 1男 2女
     *     "store_name":"", //工作店铺
     *     "state_online":0, //在线状态 1在线 0离线
     *     "state_remind":0, //订单提醒状态 1启用 0关闭
     * }}
     */
    public function getEmployeeInfo ()
    {
        return (new ParkWashEmployeeModel())->getEmployeeInfo($this->_G['user']['uid']);
    }

    /**
     * 获取订单列表
     * @login
     * @param status 订单状态(1新订单3服务中4已完成)，默认为1
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
     *          "car_number":"", //车牌号
     *          "place":"A002", //车位号
     *          "item_name":“”, //洗车套餐
     *          "order_time":"", //预约时间
     *          "create_time":"", //下单时间
     *          "brand_name":"斯柯达", //汽车品牌名
     *          "series_name":"昊锐", //汽车款型
     *          "car_type_name":"", //车型
     *          "area_floor":"负一楼", //楼层
     *          "area_name":"A区", //区域
     *          "status":1, //订单状态 (1已支付 3服务中 4已完成服务 5顾客已确认完成)
     *      }]
     * }}
     */
    public function getOrderList ()
    {
        return (new ParkWashEmployeeModel())->getOrderList($this->_G['user']['uid'], $_POST);
    }

    /**
     * 获取订单数量
     * @login
     * @return array
     * {
     * "errNo":0, //错误码 0成功 -1失败
     * "message":"",
     * "result":{
     *     "new":0, //新订单
     *     "service":0, //服务中
     *     "complete":0, //已完成
     *     "cancel":0, //已取消
     * }}
     */
    public function getOrderCount ()
    {
        return (new ParkWashEmployeeModel())->getOrderCount($this->_G['user']['uid']);
    }

    /**
     * 获取订单详情
     * @param *orderid 订单ID
     * @return array
     * {
     * "errNo":0, //错误码 0成功 -1失败
     * "message":"", //返回信息
     * "result":{
     *      "id":1, //订单ID
     *      "order_code":"", //订单号
     *      "car_number":"", //车牌号
     *      "place":"", //车位号
     *      "pay":0, //支付金额 (元)
     *      "payway":"车币支付", //支付方式
     *      "item_name":"", //洗车套餐
     *      "order_time":"", //预约时间
     *      "create_time":"", //下单时间
     *      "service_time":"", //开始服务时间
     *      "complete_time":"", //完成服务时间
     *      "cancel_time":"", //取消订单时间
     *      "brand_name":"斯柯达", //汽车品牌名
     *      "series_name":"昊锐", //汽车款型
     *      "car_type_name":"", //车型
     *      "area_floor":"负一楼", //楼层
     *      "area_name":"A区", //区域
     *      "remark":"", //备注
     *      "user_tel":"", //顾客手机号
     *      "employee":"", //接单员工
     *      "helper":"", //帮手(多个逗号分隔)
     *      "status":1, //订单状态 (-1已取消 1已支付 3服务中 4已完成 5确认完成)
     * }}
     */
    public function getOrderInfo ()
    {
        return (new ParkWashEmployeeModel())->getOrderInfo(getgpc('orderid'));
    }

    /**
     * 获取帮手列表
     * @login
     * @param *orderid 订单ID
     * @return array
     * {
     * "errNo":0, //错误码 0成功 -1失败
     * "message":"",
     * "result":[{
     *     "id":1, //员工ID
     *     "realname":"", //姓名
     *     "avatar":"", //头像
     *     "state_work":0, //工作状态 1工作中 0闲置中
     * }]
     * }
     */
    public function getHelperList ()
    {
        return (new ParkWashEmployeeModel())->getHelperList($this->_G['user']['uid'], getgpc('orderid'));
    }

    /**
     * 检查当前用户是否可以接单
     * @login
     * @return array
     * {
     * "errNo":0, //错误码 0成功 -1失败
     * "message":"", //错误消息
     * "result":[]
     * }
     */
    public function checkTakeOrder ()
    {
        return (new ParkWashEmployeeModel())->checkTakeOrder($this->_G['user']['uid']);
    }

    /**
     * 开始服务
     * @login
     * @param *orderid 订单ID
     * @param helper 帮手ID（多个帮手用逗号分隔，无帮手传空值）
     * @return array
     * {
     * "errNo":0, //错误码 0成功 -1失败
     * "message":"", //错误消息
     * "result":[]
     * }
     */
    public function takeOrder ()
    {
        return (new ParkWashEmployeeModel())->takeOrder($this->_G['user']['uid'], $_POST);
    }

    /**
     * 完成服务
     * @login
     * @param *orderid 订单ID
     * @return array
     * {
     * "errNo":0, //错误码 0成功 -1失败
     * "message":"", //错误消息
     * "result":[]
     * }
     */
    public function completeOrder ()
    {
        return (new ParkWashEmployeeModel())->completeOrder($this->_G['user']['uid'], $_POST);
    }


    /**
     * 添加备注
     * @login
     * @param *orderid 订单ID
     * @param *content 备注内容（30个字符内）
     * @return array
     * {
     * "errNo":0, //错误码 0成功 -1失败
     * "message":"", //错误消息
     * "result":[]
     * }
     */
    public function remarkOrder ()
    {
        return (new ParkWashEmployeeModel())->remarkOrder($this->_G['user']['uid'], $_POST);
    }

    /**
     * 设置在线状态
     * @login
     * @param state 状态（1在线0离线，默认0）
     * @return array
     * {
     * "errNo":0, //错误码 0成功 -1失败
     * "message":"", //错误消息
     * "result":[]
     * }
     */
    public function onLine ()
    {
        return (new ParkWashEmployeeModel())->onLine($this->_G['user']['uid'], getgpc('state'));
    }

    /**
     * 设置订单提醒状态
     * @login
     * @param state 状态（1启动0关闭，默认0）
     * @return array
     * {
     * "errNo":0, //错误码 0成功 -1失败
     * "message":"", //错误消息
     * "result":[]
     * }
     */
    public function onRemind ()
    {
        return (new ParkWashEmployeeModel())->onRemind($this->_G['user']['uid'], getgpc('state'));
    }

    /**
     * 统计
     * @login
     * @param start_time 开始时间（格式：2019-01-01，默认今日）
     * @param end_time 截止时间（格式：2019-01-01，默认今日）
     * @param lastpage 分页参数
     * @return array
     * {
     * "errNo":0, //错误码 0成功 -1失败
     * "message":"",
     * "result":{
     *     "limit":10, //每页最大显示数
     *     "lastpage":"", //分页参数 (下一页携带的参数)
     *     "total_pay":0, //总收入（元）
     *     "complete_count":0, //完成单数
     *     "list":[{
     *          "id":5, //订单ID
     *          "car_number":"", //车牌号
     *          "item_name":“”, //洗车套餐
     *          "complete_time":"", //完成时间
     *          "brand_name":"斯柯达", //汽车品牌名
     *          "series_name":"昊锐", //汽车款型
     *          "car_type_name":"", //车型
     *          "employee_salary":0, //收益（元）
     *      }]
     * }}
     */
    public function statistics ()
    {
        return (new ParkWashEmployeeModel())->statistics($this->_G['user']['uid'], $_POST);
    }

    /**
     * 发送短信验证码
     * @param *telephone 手机号
     * @route parkWash/sendSms
     * @return array
     * {
     * "errNo":0, // 错误码 0成功 -1失败
     * "message":"", //错误消息
     * "result":[]
     * }
     */
    protected function sendSms () {}

}
