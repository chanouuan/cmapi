<?php

namespace app\controllers;

use ActionPDO;
use app\common\ParkWashCache;
use app\common\ParkWashPayWay;
use app\common\ParkWashRole;
use app\library\Aes;
use app\models\AdminModel;
use app\models\XicheManageModel;
use app\models\UserModel;

class XicheManage extends ActionPDO {

    public function __init ()
    {
        // cookie
        $adminid = json_decode(Aes::decrypt($_COOKIE['adminid']), true);
        define('ROLE', $adminid['role']); // 角色
        define('PERMISSION', $adminid['permission']); // 权限

        if (!in_array($this->_action, ['login', 'checkImgCode', 'orderAlert', 'noticeAlert'])) {
            $this->_G['user'] = $this->loginCheck();
            if (empty($this->_G['user'])) {
                $this->error('用户校验失败', gurl('xicheManage/login'));
            }
            if ($this->_G['user']['uid'] != $adminid['uid']) {
                $this->logout();
            }
            $this->_G['user']['nickname'] = $adminid['nickname'];
        }

        if (!in_array($this->_action, ['login', 'checkImgCode', 'index', 'welcome', 'logout'])) {
            // 权限验证
            $combineAuth = [
                'parkOrderStatusUpdate' => 'parkOrder',
                'parkOrderView' => 'parkOrder',
                'entryParkInfo' => 'parkOrder',
                'employeeAdd' => 'employee',
                'employeeUpdate' => 'employee'
            ];
            if (empty(array_intersect(['ANY', isset($combineAuth[$this->_action]) ? $combineAuth[$this->_action] : $this->_action], PERMISSION))) {
                $this->error('权限不足');
            }
        }
    }

    public function __style ()
    {
        if ($this->_action == 'login') {
            return CLIENT_TYPE == 'pc' ? 'default' : 'mobile';
        }
        return 'default';
    }

    public function index ()
    {
        return [
            'user_info' => $this->_G['user']
        ];
    }

    /**
     * home页
     */
    public function welcome ()
    {
        return [
            'user_info' => $this->_G['user']
        ];
    }

    /**
     * 停车场列表
     */
    public function park ()
    {
        $condition = [];
        if ($_GET['name']) {
            $condition['name'] = ['like', '%' . $_GET['name'] . '%'];
        }

        $model = new XicheManageModel();
        $count = $model->getCount('parkwash_park', $condition);
        $pagesize = getPageParams($_GET['page'], $count);

        $list = $model->getList('parkwash_park', $condition, $pagesize['limitstr']);

        return compact('pagesize', 'list');
    }

    /**
     * 停车场添加
     */
    public function parkAdd ()
    {
        $model = new XicheManageModel();
        if (submitcheck()) {
            return $model->parkAdd($_POST);
        }
        return [];
    }

    /**
     * 停车场编辑
     */
    public function parkUpdate ()
    {
        $model = new XicheManageModel();
        if (submitcheck()) {
            return $model->parkUpdate($_POST);
        }
        $info = $model->getInfo('parkwash_park', ['id' => getgpc('id')]);
        return compact('info');
    }

    /**
     * 员工添加
     */
    public function employeeAdd ()
    {
        $model = new XicheManageModel();
        if (submitcheck()) {
            // 角色权限
            if (!in_array(ParkWashRole::ADMIN, ROLE)) {
                $_POST['role_id'] = 0;
            }
            return $model->employeeAdd($_POST);
        }
        // 角色权限
        $condition = [];
        if (in_array(ParkWashRole::OWNER, ROLE)) {
            $condition['id'] = ParkWashRole::getOwnerStoreId($this->_G['user']['uid']);
        }
        $stores = $model->getList('parkwash_store', $condition, null, null, 'id,name');
        $items  = $model->getList('parkwash_item', null, null, null, 'id,name,car_type_id');
        $carTypes = ParkWashCache::getCarType();
        foreach ($items as $k => $v) {
            $items[$k]['name'] = ($v['car_type_id'] ? $carTypes[$v['car_type_id']]['name'] . '·' : '') . $v['name'];
        }
        return compact('stores', 'items');
    }

    /**
     * 员工编辑
     */
    public function employeeUpdate ()
    {
        $model = new XicheManageModel();
        if (submitcheck()) {
            // 角色权限
            if (!in_array(ParkWashRole::ADMIN, ROLE)) {
                // 店长不能修改角色
                if (!$employeeInfo = \app\library\DB::getInstance()->table('parkwash_employee')->field('role_id')->where(['id' => $_POST['id']])->find()) {
                    return error('该员工不存在');
                }
                $_POST['role_id'] = $employeeInfo['role_id'];
            }
            return $model->employeeUpdate($_POST);
        }
        // 角色权限
        $condition = [];
        if (in_array(ParkWashRole::OWNER, ROLE)) {
            $condition['id'] = ParkWashRole::getOwnerStoreId($this->_G['user']['uid']);
        }
        $stores = $model->getList('parkwash_store', $condition, null, null, 'id,name');
        $items  = $model->getList('parkwash_item', null, null, null, 'id,name,car_type_id');
        $carTypes = ParkWashCache::getCarType();
        foreach ($items as $k => $v) {
            $items[$k]['name'] = ($v['car_type_id'] ? $carTypes[$v['car_type_id']]['name'] . '·' : '') . $v['name'];
        }
        $info = $model->getInfo('parkwash_employee', ['id' => getgpc('id')]);
        $info['avatar'] = $info['avatar'] ? '<img height="30" src="' . httpurl($info['avatar']) . '">' : '';
        return compact('stores', 'items', 'info');
    }

    /**
     * 员工管理
     */
    public function employee ()
    {
        $condition = [];
        // 角色权限
        if (in_array(ParkWashRole::OWNER, ROLE)) {
            $condition['store_id'] = ParkWashRole::getOwnerStoreId($this->_G['user']['uid']);
        }
        if ($_GET['store_name']) {
            $condition['store_name'] = ['like', '%' . $_GET['store_name'] . '%'];
        }
        if ($_GET['realname']) {
            $condition['realname'] = ['like', '%' . $_GET['realname'] . '%'];
        }
        if ($_GET['telephone']) {
            $condition['telephone'] = ['like', $_GET['telephone'] . '%'];
        }
        if (isset($_GET['status']) && $_GET['status'] !== '') {
            $condition['status'] = $_GET['status'];
        }

        $model = new XicheManageModel();
        $count = $model->getCount('parkwash_employee', $condition);
        $pagesize = getPageParams($_GET['page'], $count);

        $carTypes = ParkWashCache::getCarType();
        $items = $model->getList('parkwash_item', null, null, null);
        $items = array_column($items, null, 'id');
        foreach ($items as $k => $v) {
            $items[$k] = ($v['car_type_id'] ? $carTypes[$v['car_type_id']]['name'] . '·' : '') . $v['name'];
        }
        $list = $model->getList('parkwash_employee', $condition, $pagesize['limitstr']);
        foreach ($list as $k => $v) {
            $list[$k]['avatar']  = $v['avatar'] ? '<a onclick="xadmin.open(\'IMG\',\'' . httpurl($v['avatar']) . '\')" href="javascript:;" target="_blank"><img height="30" src="' . httpurl($v['avatar']) . '"></a>' : '';
            $list[$k]['item_id'] = strtr(trim($v['item_id'], ','), $items);
            $list[$k]['item_id'] = '<span title="' . $list[$k]['item_id'] . '">' . msubstr($list[$k]['item_id'], 0, 10, 'utf-8', true) . '</span>';
        }

        return compact('pagesize', 'list');
    }

    /**
     * 员工收益
     */
    public function employeeSalary ()
    {
        $model = new XicheManageModel();
        $condition = [];
        // 角色权限
        if (in_array(ParkWashRole::OWNER, ROLE)) {
            $condition['order_table.store_id'] = ParkWashRole::getOwnerStoreId($this->_G['user']['uid']);
        }
        if ($_GET['telephone']) {
            $employeeInfo = $model->getInfo('parkwash_employee', ['telephone' => $_GET['telephone']], 'id');
            $condition['helper_table.employee_id'] = $employeeInfo ? $employeeInfo['id'] : 0;
        }
        if ($_GET['start_time'] && $_GET['end_time']) {
            $condition['order_table.complete_time'] = ['between', [$_GET['start_time'] . ' 00:00:00', $_GET['end_time'] . ' 23:59:59']];
        }

        if (empty($_GET['export'])) {
            $row = \app\library\DB::getInstance()
                ->table('parkwash_order_helper helper_table left join parkwash_order order_table on order_table.id = helper_table.orderid')
                ->field('count(*) as count,sum(helper_table.employee_salary) as money')
                ->where($condition)
                ->find();
            $count = $row['count'];
            $totalMoney = round_dollar($row['money']);
            $pagesize = getPageParams($_GET['page'], $count);
        }
        $list = $model->getList('parkwash_order_helper helper_table left join parkwash_order order_table on order_table.id = helper_table.orderid', $condition, $pagesize['limitstr'], 'order_table.id desc', 'order_table.id,helper_table.employee_id,helper_table.employee_salary,helper_table.identity,order_table.car_number,order_table.brand_id,order_table.car_type_id,order_table.item_name,order_table.complete_time');

        if ($list) {
            $employees = $model->getList('parkwash_employee', ['id' => ['in', array_column($list, 'employee_id')]], null, null, 'id,realname,telephone');
            $employees = array_column($employees, null, 'id');
            $brands    = ParkWashCache::getBrand();
            $carTypes  = ParkWashCache::getCarType();
            foreach ($list as $k => $v) {
                $list[$k]['identity']        = $v['identity'] ? '' : '帮手';
                $list[$k]['employee_name']   = $employees[$v['employee_id']]['realname'];
                $list[$k]['employee_tel']    = $employees[$v['employee_id']]['telephone'];
                $list[$k]['brand_name']      = $brands[$v['brand_id']]['name'];
                $list[$k]['car_type_name']   = $carTypes[$v['car_type_id']]['name'];
                $list[$k]['employee_salary'] = round_dollar($v['employee_salary']);
            }
            unset($employees, $brands, $carTypes);
        }

        // 导出
        if ($_GET['export']) {
            $input = [];
            foreach ($list as $k => $v) {
                $input[] = [$v['id'], $v['employee_name'], $v['employee_tel'], $v['identity'], $v['car_number'], $v['brand_name'], $v['car_type_name'], $v['item_name'], $v['employee_salary'], $v['complete_time']];
            }
            $model->exportCsv('员工收益', '订单编号,员工姓名,员工手机,是否帮手,车牌号,品牌,车型,服务项目,收益,完成时间', $input);
        }

        return compact('pagesize', 'list', 'totalMoney');
    }

    /**
     * 品牌列表
     */
    public function carBrand ()
    {
        $list = ParkWashCache::getBrand();
        foreach ($list as $k => $v) {
            $list[$k]['logo'] = $v['logo'] ? '<a onclick="xadmin.open(\'IMG\',\'' . httpurl($v['logo']) . '\')" href="javascript:;" target="_blank"><img height="30" src="' . httpurl($v['logo']) . '"></a>' : '';
        }
        return compact('list');
    }

    /**
     * 车型添加
     */
    public function carBrandAdd ()
    {
        if (submitcheck()) {
            return (new XicheManageModel())->carBrandAdd($_POST);
        }
        return [];
    }

    /**
     * 车型编辑
     */
    public function carBrandUpdate ()
    {
        $model = new XicheManageModel();
        if (submitcheck()) {
            return $model->carBrandUpdate($_POST);
        }
        $info = $model->getInfo('parkwash_car_brand', ['id' => getgpc('id')]);
        $info['logo'] = $info['logo'] ? '<img height="30" src="' . httpurl($info['logo']) . '">' : '';
        return compact('info');
    }

    /**
     * 导入车系
     */
    public function carSeriesImport ()
    {
        $model = new XicheManageModel();

        if ($_GET['tpl']) {
            // 下载模板
            $model->exportCsv('车系导入模板', '品牌,车系,车型,状态', []);
        }
        if ($_GET['upload']) {
            // 上传
            return $model->carSeriesImport();
        }
        return [];
    }

    /**
     * 车系列表
     */
    public function carSeries ()
    {
        $condition = [];
        if ($_GET['brand_id']) {
            $condition['brand_id'] = intval($_GET['brand_id']);
        }
        if ($_GET['name']) {
            $condition['name'] = ['like', '%' . $_GET['name'] . '%'];
        }
        if ($_GET['car_type_id']) {
            $condition['car_type_id'] = intval($_GET['car_type_id']);
        }
        if (isset($_GET['status']) && $_GET['status'] !== '') {
            $condition['status'] = intval($_GET['status']);
        }

        $model = new XicheManageModel();
        $order = 'brand_id';
        if (empty($_GET['export'])) {
            $count = $model->getCount('parkwash_car_series', $condition);
            $pagesize = getPageParams($_GET['page'], $count);
            $order = 'id desc';
        }

        $list = $model->getList('parkwash_car_series', $condition, $pagesize['limitstr'], $order);
        $carType = $model->getCarTypeItem();
        $brands = ParkWashCache::getBrand();

        foreach ($list as $k => $v) {
            $list[$k]['car_type_name'] = $carType[$v['car_type_id']];
            $list[$k]['brand_name'] = $brands[$v['brand_id']]['name'];
        }

        // 导出
        if ($_GET['export']) {
            $input = [];
            foreach ($list as $k => $v) {
                $input[] = [$v['id'], $v['brand_name'], $v['name'], $v['car_type_name'], $v['status'] ? '显示' : '隐藏'];
            }
            $model->exportCsv('汽车车系', '编号,品牌,车系,车型,状态', $input);
        }

        return compact('pagesize', 'list', 'carType', 'brands');
    }

    /**
     * 车系添加
     */
    public function carSeriesAdd ()
    {
        $model = new XicheManageModel();
        if (submitcheck()) {
            return $model->carSeriesAdd($_POST);
        }
        $model = new XicheManageModel();
        $carType = $model->getCarTypeItem();
        $brands = ParkWashCache::getBrand();
        $brands = array_column($brands, 'name', 'id');
        return compact('carType', 'brands');
    }

    /**
     * 车型编辑
     */
    public function carSeriesUpdate ()
    {
        $model = new XicheManageModel();
        if (submitcheck()) {
            return $model->carSeriesUpdate($_POST);
        }
        $model = new XicheManageModel();
        $carType = $model->getCarTypeItem();
        $brands = ParkWashCache::getBrand();
        $brands = array_column($brands, 'name', 'id');
        $info = $model->getInfo('parkwash_car_series', ['id' => getgpc('id')]);
        return compact('info', 'carType', 'brands');
    }

    /**
     * 车型列表
     */
    public function carType ()
    {
        return [
            'list' => (new XicheManageModel())->getList('parkwash_car_type', null, null, null)
        ];
    }

    /**
     * 车型添加
     */
    public function carTypeAdd ()
    {
        if (submitcheck()) {
            return (new XicheManageModel())->carTypeAdd($_POST);
        }
        return [];
    }

    /**
     * 车型编辑
     */
    public function carTypeUpdate ()
    {
        $model = new XicheManageModel();
        if (submitcheck()) {
            return $model->carTypeUpdate($_POST);
        }
        return [
            'info' => $model->getInfo('parkwash_car_type', ['id' => getgpc('id')])
        ];
    }

    /**
     * 套餐列表
     */
    public function item ()
    {
        $model = new XicheManageModel();
        $list = $model->getList('parkwash_item', null, null, null);
        $carType = $model->getCarTypeItem();
        foreach ($list as $k => $v) {
            $list[$k]['car_type'] = isset($carType[$v['car_type_id']]) ? $carType[$v['car_type_id']] : '不限';
            $list[$k]['firstorder'] = $v['firstorder'] ? '开启' : '关闭';
        }

        return compact('list');
    }

    /**
     * 套餐添加
     */
    public function itemAdd ()
    {
        $model = new XicheManageModel();

        if (submitcheck()) {
            return $model->itemAdd($_POST);
        }

        $carType = $model->getList('parkwash_car_type', ['status' => 1], null, null);
        $carType = array_column($carType, 'name', 'id');

        return compact('carType');
    }

    /**
     * 套餐编辑
     */
    public function itemUpdate ()
    {
        $model = new XicheManageModel();

        if (submitcheck()) {
            return $model->itemUpdate($_POST);
        }

        $info    = $model->getInfo('parkwash_item', ['id' => getgpc('id')]);
        $carType = $model->getList('parkwash_car_type', ['status' => 1], null, null);
        $carType = array_column($carType, 'name', 'id');

        return compact('info', 'carType');
    }

    /**
     * 套餐删除
     */
    public function itemDelete ()
    {
        return (new XicheManageModel())->itemDelete(getgpc('id'));
    }

    /**
     * 门店管理
     */
    public function store ()
    {
        $condition = [];
        if ($_GET['name']) {
            $condition['name'] = ['like', '%' . $_GET['name'] . '%'];
        }

        $model = new XicheManageModel();
        $count = $model->getCount('parkwash_store', $condition);
        $pagesize = getPageParams($_GET['page'], $count);
        $list = $model->getList('parkwash_store', $condition, $pagesize['limitstr'], 'status desc,id desc');

        if ($list) {
            $employees = $model->getList('parkwash_employee', ['store_id' => ['in', array_column($list, 'id')], 'status' => 1], null, null, 'store_id,count(*) as count', 'store_id');
            $employees = array_column($employees, 'count', 'store_id');
            $parks = $model->getList('parkwash_park', null, null, null);
            $parks = array_column($parks, 'name', 'id');
            $statusEnum = [1 => '正常营业', 0 => '建设中', -1 => '禁用'];
            foreach ($list as $k => $v) {
                $list[$k]['logo'] = $v['logo'] ? json_decode($v['logo'], true) : [];
                $list[$k]['logo'] = $list[$k]['logo'] ? '<a onclick="xadmin.open(\'IMG\',\'' . httpurl($list[$k]['logo'][0]) . '\')" href="javascript:;" target="_blank"><img height="30" src="' . httpurl($list[$k]['logo'][0]) . '"></a>' : '';
                $list[$k]['str_status'] = $statusEnum[$v['status']];
                $list[$k]['employee_count'] = isset($employees[$v['id']]) ? $employees[$v['id']] : 0; // 员工数
                $list[$k]['park_name'] = $parks[$v['park_id']];
                $list[$k]['money'] = round_dollar($v['money']);
            }
        }

        return [
            'pagesize' => $pagesize,
            'list' => $list
        ];
    }

    /**
     * 门店添加
     */
    public function storeAdd ()
    {
        $model = (new XicheManageModel());
        if (submitcheck()) {
            return $model->storeAdd($_POST);
        }

        $items = $model->getList('parkwash_item', null, null, null);
        $parks = $model->getList('parkwash_park', null, null, null);
        $carTypes = ParkWashCache::getCarType();
        foreach ($items as $k => $v) {
            $items[$k]['name'] = ($v['car_type_id'] ? $carTypes[$v['car_type_id']]['name'] . '·' : '') . $v['name'];
        }

        return compact('items', 'parks');
    }

    /**
     * 门店编辑
     */
    public function storeUpdate ()
    {
        $model = new XicheManageModel();
        if (submitcheck()) {
            return $model->storeUpdate($_POST);
        }

        $info = $model->getInfo('parkwash_store', ['id' => getgpc('id')]);
        $info['logo'] = $info['logo'] ? json_decode($info['logo'], true) : [];
        $info['logo'] = $info['logo'] ? '<img height="30" src="' . httpurl($info['logo'][0]) . '">' : '';
        $info['time_day'] = str_split($info['time_day']);
        $items = $model->getList('parkwash_item', null, null, null);
        $storeItems = $model->getList('parkwash_store_item', ['store_id' => getgpc('id')]);
        $storeItems = array_column($storeItems, null, 'item_id');
        $carTypes = ParkWashCache::getCarType();
        foreach ($items as $k => $v) {
            $items[$k]['name'] = ($v['car_type_id'] ? $carTypes[$v['car_type_id']]['name'] . '·' : '') . $v['name'];
            $items[$k]['price'] = isset($storeItems[$v['id']]) ? $storeItems[$v['id']]['price'] : 0;
            $items[$k]['employee_salary'] = isset($storeItems[$v['id']]) ? $storeItems[$v['id']]['employee_salary'] : 0;
        }
        $parks = $model->getList('parkwash_park', null, null, null);

        return compact('info', 'items', 'parks');
    }

    /**
     * 车位状态管理
     */
    public function parking ()
    {
        $model = new XicheManageModel();

        $condition = [];
        if ($_GET['park_id']) {
            $areas = $model->getList('parkwash_park_area', ['park_id' => intval($_GET['park_id'])], null, null, 'id');
            $condition['area_id'] = $areas ? ['in', array_column($areas, 'id')] : 0;
        }
        if ($_GET['place']) {
            $condition['place'] = ['like', '%' . $_GET['place'] . '%'];
        }

        $count = $model->getCount('parkwash_parking', $condition);
        $pagesize = getPageParams($_GET['page'], $count);
        $list = $model->getList('parkwash_parking', $condition, $pagesize['limitstr']);
        $parks = $model->getList('parkwash_park', null, null, null);
        $parks = array_column($parks, 'name', 'id');
        if ($list) {
            $areaList = $model->getList('parkwash_park_area', [
                'id' => ['in', array_column($list, 'area_id')]
            ]);
            $areaList = array_column($areaList, null, 'id');
            foreach ($list as $k => $v) {
                $list[$k]['area_floor'] = $areaList[$v['area_id']]['floor'];
                $list[$k]['area_name'] = $areaList[$v['area_id']]['name'];
                $list[$k]['park_name'] = $parks[$areaList[$v['area_id']]['park_id']];
            }
        }

        return compact('pagesize', 'list', 'parks');
    }

    /**
     * 车位状态添加
     */
    public function parkingAdd ()
    {
        $model = new XicheManageModel();
        if (submitcheck()) {
            return $model->parkingAdd($_POST);
        }

        $list = $model->getList('parkwash_park_area', ['status' => 1]);
        $areas = [];
        foreach ($list as $k => $v) {
            $areas[$v['park_id']][] = $v;
        }
        unset($list);
        $parks = $model->getList('parkwash_park');
        return compact('areas', 'parks');
    }

    /**
     * 车位状态编辑
     */
    public function parkingUpdate ()
    {
        $model = new XicheManageModel();
        if (submitcheck()) {
            return $model->parkingUpdate($_POST);
        }

        $info = $model->getInfo('parkwash_parking', ['id' => getgpc('id')]);
        $list = $model->getList('parkwash_park_area');
        $areas = [];
        foreach ($list as $k => $v) {
            $areas[$v['park_id']][] = $v;
            if ($v['id'] == $info['area_id']) {
                $info['park_id'] = $v['park_id'];
            }
        }
        unset($list);
        $parks = $model->getList('parkwash_park');
        return compact('info', 'areas', 'parks');
    }

    /**
     * 车位状态删除
     */
    public function parkingDelete () {
        return (new XicheManageModel())->parkingDelete(getgpc('id'));
    }

    /**
     * 车位区域管理
     */
    public function area ()
    {
        $condition = [];
        if ($_GET['park_id']) {
            $condition['park_id'] = intval($_GET['park_id']);
        }
        if ($_GET['name']) {
            $condition['name'] = ['like', '%' . $_GET['name'] . '%'];
        }

        $model = new XicheManageModel();
        $count = $model->getCount('parkwash_park_area', $condition);
        $pagesize = getPageParams($_GET['page'], $count);
        $list = $model->getList('parkwash_park_area', $condition, $pagesize['limitstr']);
        $parks = $model->getList('parkwash_park', null, null, null);
        $parks = array_column($parks, 'name', 'id');
        foreach ($list as $k => $v) {
            $list[$k]['park_name'] = $parks[$v['park_id']];
        }

        return compact('pagesize', 'list', 'parks');
    }

    /**
     * 车位区域添加
     */
    public function areaAdd ()
    {
        $model = new XicheManageModel();
        if (submitcheck()) {
            return $model->areaAdd($_POST);
        }

        $parks = $model->getList('parkwash_park', null, null, null);
        $parks = array_column($parks, 'name', 'id');

        return compact('parks');
    }

    /**
     * 车位区域编辑
     */
    public function areaUpdate ()
    {
        $model = new XicheManageModel();
        if (submitcheck()) {
            return $model->areaUpdate($_POST);
        }

        $info = $model->getInfo('parkwash_park_area', ['id' => getgpc('id')]);
        $parks = $model->getList('parkwash_park', null, null, null);
        $parks = array_column($parks, 'name', 'id');

        return compact('info', 'parks');
    }

    /**
     * 获取设备列表
     */
    public function getDev () {
        return (new XicheManageModel())->getDev(getgpc('AreaId'));
    }

    /**
     * 编辑设备
     */
    public function deviceUpdate () {
        if (submitcheck()) {
            return (new XicheManageModel())->deviceUpdate($_POST);
        }

        $deviceInfo = (new XicheManageModel())->getDeviceByCode(getgpc('devcode'));

        return ['device_info' => $deviceInfo];
    }

    /**
     * 设备添加
     */
    public function deviceAdd () {
        if (submitcheck()) {
            return (new XicheManageModel())->deviceAdd($_POST);
        }

        $areaList = (new XicheManageModel())->getDevArea();
        if ($areaList['errorcode'] !== 0) {
            $this->error($areaList['message']);
        }
        $areaList = $areaList['result'];

        return ['area_list' => $areaList];
    }

    /**
     * 同步设备参数
     */
    public function deviceSync () {
        return (new XicheManageModel())->deviceSync($_POST);
    }

    /**
     * 设备参数详情
     */
    public function deviceParamInfo () {
        $model = new XicheManageModel();
        if (!$devInfo = $model->getDeviceById(getgpc('id'))) {
            return error('参数错误');
        }

        $this->render('XicheManage/view.html', [
            'parameters' => print_r(json_decode($devInfo['parameters'],true),true)
        ]);
    }

    /**
     * 设备管理
     */
    public function device () {
        $condition = [];
        if ($_GET['devcode']) {
            $condition[] = 'devcode = "' . addslashes($_GET['devcode']) . '"';
        }

        $model = new XicheManageModel();
        $count = $model->getCount('xiche_device', $condition);
        $pagesize = getPageParams($_GET['page'], $count);
        $list = $model->getList('xiche_device', $condition, $pagesize['limitstr']);
        foreach ($list as $k => $v) {
            // 洗车时长
            $v['parameters'] = json_decode($v['parameters'], true);
            $list[$k]['duration'] = intval($v['parameters']['WashTotal']);
        }
        return [
            'pagesize' => $pagesize,
            'list' => $list,
            'stat' => [
                0 => '空闲',
                1 => '投币洗车',
                2 => '刷卡洗车',
                3 => '微信洗车',
                4 => '停售',
                5 => '手机号洗车',
                6 => '会员扫码洗车',
                7 => '缺泡沫'
            ]
        ];
    }

    /**
     * 更新停车场洗车订单状态
     */
    public function parkOrderStatusUpdate ()
    {
        return (new XicheManageModel())->parkOrderStatusUpdate($_POST);
    }

    /**
     * 获取车辆入场信息
     */
    public function entryParkInfo ()
    {
        return (new XicheManageModel())->entryParkInfo(getgpc('id'));
    }

    /**
     * 查看停车场洗车订单详情
     */
    public function parkOrderView ()
    {
        $model = new XicheManageModel();
        $orderInfo   = $model->getInfo('parkwash_order', ['id' => getgpc('id')]);
        $brandInfo   = $model->getInfo('parkwash_car_brand', ['id' => $orderInfo['brand_id']], 'name');
        $carTypeInfo = $model->getInfo('parkwash_car_type', ['id' => $orderInfo['car_type_id']], 'name');
        $areaInfo    = $model->getInfo('parkwash_park_area', ['id' => $orderInfo['area_id']], 'floor,name');
        $storeInfo   = $model->getInfo('parkwash_store', ['id' => $orderInfo['store_id']], 'name,tel,address,order_count,money');
        $orderInfo['order_code']        = str_replace(['-', ' ', ':'], '', $orderInfo['create_time']) . $orderInfo['id'];
        $orderInfo['brand_name']        = $brandInfo['name'];
        $orderInfo['car_type_name']     = $carTypeInfo['name'];
        $orderInfo['area_floor']        = $areaInfo['floor'];
        $orderInfo['area_name']         = $areaInfo['name'];
        $orderInfo['store_name']        = $storeInfo['name'];
        $orderInfo['store_tel']         = $storeInfo['tel'];
        $orderInfo['store_address']     = $storeInfo['address'];
        $orderInfo['store_order_count'] = $storeInfo['order_count'];
        $orderInfo['store_money']       = $storeInfo['money'];
        $orderInfo['payway']            = ParkWashPayWay::getMessage($orderInfo['payway']);
        // 获取订单时序表
        $orderInfo['sequence'] = $model->getlist('parkwash_order_sequence', ['orderid' => $orderInfo['id']], null, 'id asc', 'title,create_time');
        // 判断状态
        if ($orderInfo['status'] == 1 && $orderInfo['entry_park_id']) {
            // 等待服务
            $orderInfo['status'] = 13;
        }
        $orderInfo['status_str'] = $model->getParkOrderStatus($orderInfo['status']);
        // 获取出入场信息
        if ($orderInfo['entry_park_id']) {
            $userModel = new UserModel();
            $entryPark = $userModel->getCheMiParkingCondition(['id' => $orderInfo['entry_park_id']], 'id,stoping_name', 1);
            $entryPark = $entryPark[0];
            $orderInfo['park_name'] = $entryPark['stoping_name'];
            // 查询出场信息
            $outPark = $userModel->getCheMiOutParkCondition([
                'license_number' => $orderInfo['car_number'], 'order_sn' => $orderInfo['entry_order_sn']
            ], 'outpark_time', 1);
            $outParkTime = $outPark ? $outPark[0]['outpark_time'] : 0;
            $orderInfo['out_park_time'] = $outParkTime ? date('Y-m-d H:i:s', $outParkTime) : '未出场/无出场信息';
        }
        // 员工与帮手
        $helper = $model->getList('parkwash_order_helper', ['orderid' => $orderInfo['id']], null, 'id');
        if ($helper) {
            $employee = $model->getlist('parkwash_employee', ['id' => ['in', array_column($helper, 'employee_id')]], null, null, 'id,realname');
            $employee = array_column($employee, 'realname', 'id');
            $orderInfo['employee_name'] = $employee[$helper[0]['employee_id']];
            foreach ($helper as $k => $v) {
                $helper[$k]['realname'] = $employee[$v['employee_id']];
                $helper[$k]['employee_salary'] = round_dollar($v['employee_salary']);
            }
            $orderInfo['helper'] = $helper;
        }

        return [
            'info' => $orderInfo
        ];
    }

    /**
     * 停车场洗车订单管理
     */
    public function parkOrder ()
    {
        $model = new XicheManageModel();
        $condition = [
            'xc_trade_id' => 0
        ];
        // 角色权限
        if (in_array(ParkWashRole::OWNER, ROLE)) {
            $condition['store_id'] = ParkWashRole::getOwnerStoreId($this->_G['user']['uid']);
        }
        if ($_GET['store_name']) {
            $searchStoreInfo = $model->getInfo('parkwash_store', ['name' => ['like', '%' . $_GET['store_name'] . '%']], 'id');
            $condition['store_id'] = intval($searchStoreInfo['id']);
        }
        if ($_GET['order_id']) {
            $condition['id'] = intval($_GET['order_id']);
        }
        if ($_GET['user_tel']) {
            $condition['user_tel'] = ['like', $_GET['user_tel'] . '%'];
        }
        if ($_GET['car_number']) {
            $condition['car_number'] = ['like', $_GET['car_number'] . '%'];
        }
        if ($_GET['place']) {
            $condition['place'] = ['like', $_GET['place'] . '%'];
        }
        if ($_GET['payway']) {
            $condition['payway'] = $_GET['payway'];
        }
        if ($_GET['status']) {
            if ($_GET['status'] == 13) {
                // 等待服务状态
                $condition['status'] = 1;
                $condition['entry_park_id'] = ['>', 0];
            } else if ($_GET['status'] == 45) {
                // 异常订单
                $condition['status'] = ['in', [4,5]];
                $condition['fail_reason'] = ['<>', ''];
            } else {
                $condition['status'] = $_GET['status'];
                $condition['fail_reason'] = '';
            }
        } else {
            $condition['status'] = ['<>', 0];
        }
        if ($_GET['start_time'] && $_GET['end_time']) {
            $condition['order_time'] = ['between', [$_GET['start_time'] . ' 00:00:00', $_GET['end_time'] . ' 23:59:59']];
        }
        if ($_GET['start_time_create'] && $_GET['end_time_create']) {
            $condition['create_time'] = ['between', [$_GET['start_time_create'] . ' 00:00:00', $_GET['end_time_create'] . ' 23:59:59']];
        }

        if (empty($_GET['export'])) {
            $count = $model->getCount('parkwash_order', $condition);
            $pagesize = getPageParams($_GET['page'], $count);
        }
        $list = $model->getList('parkwash_order', $condition, $pagesize['limitstr'], 'id desc', 'id,entry_park_id,entry_park_time,store_id,create_time,car_number,brand_id,car_type_id,user_tel,order_time,area_id,place,item_name,pay,deduct,payway,status,fail_reason');

        if ($list) {
            $brands   = ParkWashCache::getBrand();
            $carTypes = ParkWashCache::getCarType();
            $areas    = ParkWashCache::getParkArea();
            $stores   = ParkWashCache::getStore();
            foreach ($list as $k => $v) {
                $list[$k]['create_time'] = substr($v['create_time'], 0, -3);
                $list[$k]['order_time']  = substr($v['order_time'], 0, -3);
                $list[$k]['car_name']    = $brands[$v['brand_id']]['name'] . ' ' . $carTypes[$v['car_type_id']]['name'];
                $list[$k]['area_floor']  = strval($areas[$v['area_id']]['floor']);
                $list[$k]['area_name']   = strval($areas[$v['area_id']]['name']);
                $list[$k]['store_name']  = $stores[$v['store_id']]['name'];
                $list[$k]['pay']         = round_dollar($v['pay']);
                $list[$k]['deduct']      = round_dollar($v['deduct']);
                $list[$k]['payway']      = ParkWashPayWay::getMessage($v['payway']);
                // 判断等待服务状态
                if ($v['status'] == 1 && $v['entry_park_id']) {
                    $list[$k]['status'] = 13; // 等待服务
                }
                if (($v['status'] == 4 || $v['status'] == 5) && $v['fail_reason']) {
                    $list[$k]['status'] = 45; // 异常订单
                }
                $list[$k]['status_str'] = $model->getParkOrderStatus($list[$k]['status']);
            }
            unset($brands, $carTypes, $areas, $stores);
        }

        // 导出
        if ($_GET['export']) {
            $input = [];
            foreach ($list as $k => $v) {
                $input[] = [$v['id'], $v['store_name'], $v['create_time'], $v['order_time'], $v['car_number'], $v['car_name'], $v['user_tel'], $v['area_name'] . $v['area_floor'], $v['place'], $v['item_name'], $v['pay'] + $v['deduct'], $v['pay'], $v['payway'], $v['status_str'], $v['entry_park_time']];
            }
            $model->exportCsv('停车场洗车', '编号,店铺,下单时间,取车时间,车牌,车型,用户手机,区域,车位号,套餐,价格,已支付,支付方式,状态,入场时间', $input);
        }

        return [
            'pagesize' => $pagesize,
            'list' => $list,
            'dateTime' => $model->getSearchDateTime(),
            'statusList' => $model->getParkOrderStatus()
        ];
    }

    /**
     * 订单提醒
     */
    public function orderAlert () {

        \DebugLog::_debug(false);
        // 没有填写车位的订单数量
        $noPlaceCount = \app\library\DB::getInstance()
            ->table('parkwash_order')
            ->where([
                'order_time' => ['between', [date('Y-m-d H:i:s', TIMESTAMP - 1800), date('Y-m-d H:i:s', TIMESTAMP + 600)]],
                'status' => 1,
                'place' => ''
            ])
            ->count();

        return success([
            'noPlaceCount' => $noPlaceCount
        ]);
    }

    /**
     * 商家通知
     */
    public function noticeAlert () {

        \DebugLog::_debug(false);
        $noticeList = \app\library\DB::getInstance()
            ->table('parkwash_notice')
            ->where([
                'receiver' => 2, 'notice_type' => 2, 'is_read' => 0, 'create_time' => ['>', date('Y-m-d', TIMESTAMP)]
            ])
            ->field('id,title,content')
            ->select();
        if ($noticeList) {
            \app\library\DB::getInstance()->update('parkwash_notice', ['is_read' => 1], [
                'id' => ['in', array_column($noticeList, 'id')]
            ]);
            $noticeData = [];
            $audioPath = [
                'create' => APPLICATION_URL . '/static/audio/create.mp3',
                'updatePlace' => APPLICATION_URL . '/static/audio/entryCar.mp3',
                'entryCar' => APPLICATION_URL . '/static/audio/entryCar.mp3'
            ];
            foreach ($noticeList as $k => $v) {
                if (isset($audioPath[$v['content']])) {
                    $noticeData[$v['title']]['title'] = $v['title'];
                    $noticeData[$v['title']]['audio'] = $audioPath[$v['content']];
                    $noticeData[$v['title']]['num'] ++;
                }
            }
            unset($noticeList);
        }
        return success([
            'noticeData' => $noticeData
        ]);
    }

    /**
     * 自助洗车订单管理
     */
    public function xicheOrder ()
    {
        $condition = [
            'type' => 'xc'
        ];
        $model = new XicheManageModel();
        $userModel = new UserModel();

        if ($_GET['telephone']) {
            $userInfo = $userModel->getUserInfoCondition([
                'member_name' => $_GET['telephone']
            ]);
            if ($userInfo) {
                $condition['trade_id'] = $userInfo['member_id'];
            }
        }
        if ($_GET['devcode']) {
            $deviceInfo = $model->getDeviceByCode($_GET['devcode']);
            if ($deviceInfo) {
                $condition['param_id'] = $deviceInfo['id'];
            }
        }
        if ($_GET['ordercode']) {
            $condition['ordercode'] = $_GET['ordercode'];
        }
        if ($_GET['payway']) {
            $condition['payway'] = $_GET['payway'];
        }
        if ($_GET['start_time'] && $_GET['end_time']) {
            $condition['createtime'] = ['between', [$_GET['start_time'] . ' 00:00:00', $_GET['end_time'] . ' 23:59:59']];
        }

        if (empty($_GET['export'])) {
            $count = $model->getCount('payments', $condition);
            $pagesize = getPageParams($_GET['page'], $count);
        }
        $list = $model->getList('payments', $condition, $pagesize['limitstr']);

        if ($list) {
            $paystatus = [
                0 => '未支付',
                1 => '已付款'
            ];
            $orderstatus = [
                'wxpayjs' => '微信',
                'cbpay'   => '车币'
            ];
            $devList = $model->getDeviceById(array_column($list, 'param_id'));
            $devList = array_column($devList, 'devcode', 'id');
            $userList = $userModel->getUserByBinding([
                'platform = 3',
                'uid in (' . implode(',', array_column($list, 'trade_id')) . ')'
            ]);
            $userList = array_column($userList, 'tel', 'uid');
            foreach ($list as $k => $v) {
                $list[$k]['devcode'] = isset($devList[$v['param_id']]) ? $devList[$v['param_id']] : '';
                $list[$k]['uname'] = isset($userList[$v['trade_id']]) ? $userList[$v['trade_id']] : '';
                $list[$k]['paystatus'] = $paystatus[$v['status']];
                $list[$k]['param_a'] = $v['param_a'] ? date('Y-m-d H:i:s', $v['param_a']) : '';
                $list[$k]['param_b'] = $v['param_b'] ? date('Y-m-d H:i:s', $v['param_b']) : '';
                $list[$k]['money'] = round_dollar($v['money'], false);
                $list[$k]['refundpay'] = $v['refundpay'] ? round_dollar($v['refundpay'], false) : '';
                $list[$k]['payway'] = isset($orderstatus[$v['payway']]) ? $orderstatus[$v['payway']] : '';
            }
        }

        // 导出
        if ($_GET['export']) {
            $input = [];
            foreach ($list as $k => $v) {
                $input[] = [$v['id'], strval($v['ordercode']), $v['uname'], $v['devcode'], $v['param_a'], $v['param_b'], $v['payway'], $v['money'], $v['refundpay'], $v['createtime'], $v['paystatus']];
            }
            $model->exportCsv('自助洗车订单', '编号,订单号,用户,设备编码,开始洗车时间,结束洗车时间,支付方式,支付金额,退款金额,下单时间,状态', $input);
        }

        return [
            'pagesize' => $pagesize,
            'list' => $list
        ];
    }

    /**
     * 洗车卡缴费记录
     */
    public function cardRecord ()
    {
        $model = new XicheManageModel();

        $condition = [];
        if ($_GET['uid']) {
            $condition['uid'] = intval($_GET['uid']);
        }
        if ($_GET['user_tel']) {
            $condition['user_tel'] = ['like', $_GET['user_tel'] . '%'];
        }
        if ($_GET['car_number']) {
            $condition['car_number'] = ['like', '%' . $_GET['car_number'] . '%'];
        }
        if ($_GET['card_type_id']) {
            $condition['card_type_id'] = intval($_GET['card_type_id']);
        }
        if ($_GET['start_time'] && $_GET['end_time']) {
            $condition['create_time'] = ['between', [$_GET['start_time'] . ' 00:00:00', $_GET['end_time'] . ' 23:59:59']];
        }

        if (empty($_GET['export'])) {
            $row = \app\library\DB::getInstance()
                ->table('parkwash_card_record')
                ->field('count(*) as count,sum(money) as money')
                ->where($condition)
                ->find();
            $count = $row['count'];
            $totalMoney = $row['money'];
            $pagesize = getPageParams($_GET['page'], $count);
        }
        $list = $model->getList('parkwash_card_record', $condition, $pagesize['limitstr']);
        $cardType = $model->getList('parkwash_card_type', null, null, 'sort desc');
        $cardType = array_column($cardType, 'name', 'id');

        foreach ($list as $k => $v) {
            $list[$k]['card_type_name'] = isset($cardType[$v['card_type_id']]) ? $cardType[$v['card_type_id']] : $v['card_type_id'];
            $list[$k]['money'] = round_dollar($v['money']);
        }

        // 导出
        if ($_GET['export']) {
            $input = [];
            foreach ($list as $k => $v) {
                $input[] = [$v['id'], $v['user_tel'], $v['car_number'], $v['card_type_name'], $v['money'], $v['end_time'], $v['duration'], $v['create_time']];
            }
            $model->exportCsv('洗车卡', '编号,用户,车牌号,卡类型,缴费(元),截止时间,时长,缴费时间', $input);
        }

        return [
            'pagesize' => $pagesize,
            'list' => $list,
            'cardType' => $cardType,
            'totalMoney' => round_dollar($totalMoney)
        ];
    }

    /**
     * 卡类型管理
     */
    public function cardType ()
    {
        $model = new XicheManageModel();
        $list = $model->getList('parkwash_card_type', null, null, 'sort desc');
        return [
            'list' => $list
        ];
    }

    /**
     * 卡类型添加
     */
    public function cardTypeAdd ()
    {
        if (submitcheck()) {
            return (new XicheManageModel())->cardTypeAdd($_POST);
        }

        return [];
    }

    /**
     * 卡类型编辑
     */
    public function cardTypeUpdate ()
    {
        if (submitcheck()) {
            return (new XicheManageModel())->cardTypeUpdate($_POST);
        }

        $info = (new XicheManageModel())->getInfo('parkwash_card_type', ['id' => getgpc('id')]);
        return [
            'info' => $info
        ];
    }

    /**
     * 充值卡类型管理
     */
    public function rechargeType ()
    {
        $model = new XicheManageModel();
        $list = $model->getList('parkwash_recharge_type', null, null, 'sort desc');
        return [
            'list' => $list
        ];
    }

    /**
     * 充值卡类型添加
     */
    public function rechargeTypeAdd ()
    {
        if (submitcheck()) {
            return (new XicheManageModel())->rechargeTypeAdd($_POST);
        }

        return [];
    }

    /**
     * 充值卡类型编辑
     */
    public function rechargeTypeUpdate ()
    {
        if (submitcheck()) {
            return (new XicheManageModel())->rechargeTypeUpdate($_POST);
        }

        $info = (new XicheManageModel())->getInfo('parkwash_recharge_type', ['id' => getgpc('id')]);
        return [
            'info' => $info
        ];
    }

    /**
     * 充值卡缴费记录
     */
    public function rechargeRecord ()
    {
        $model = new XicheManageModel();

        $condition = [];
        if ($_GET['uid']) {
            $condition['uid'] = intval($_GET['uid']);
        }
        if ($_GET['user_tel']) {
            $condition['user_tel'] = ['like', $_GET['user_tel'] . '%'];
        }
        if ($_GET['type_id']) {
            $condition['type_id'] = intval($_GET['type_id']);
        }
        if ($_GET['start_time'] && $_GET['end_time']) {
            $condition['create_time'] = ['between', [$_GET['start_time'] . ' 00:00:00', $_GET['end_time'] . ' 23:59:59']];
        }
        if ($_GET['store_name']) {
            $employees = $model->getList('parkwash_employee', ['store_name' => ['like', '%' . $_GET['store_name'] . '%']], null, null, 'id');
            $employees = $employees ? array_column($employees, 'id') : [-1];
            $condition['promo_id'] = ['in (' . implode(',', $employees) . ')'];
        }
        if ($_GET['promo_name']) {
            $employees = $model->getList('parkwash_employee', ['realname' => ['like', '%' . $_GET['promo_name'] . '%']], null, null, 'id');
            $employees = $employees ? array_column($employees, 'id') : [-1];
            $condition['promo_id'] = ['in (' . implode(',', $employees) . ')'];
        }
        if ($_GET['promo_tel']) {
            $employees = $model->getList('parkwash_employee', ['telephone' => ['like', $_GET['promo_tel'] . '%']], null, null, 'id');
            $employees = $employees ? array_column($employees, 'id') : [-1];
            $condition['promo_id'] = ['in (' . implode(',', $employees) . ')'];
        }

        if (empty($_GET['export'])) {
            $row = \app\library\DB::getInstance()
                ->table('parkwash_recharge_record')
                ->field('count(*) as count,sum(money) as money,sum(give) as give')
                ->where($condition)
                ->find();
            $count      = $row['count'];
            $totalMoney = round_dollar($row['money']);
            $totalGive  = round_dollar($row['give']);
            $pagesize   = getPageParams($_GET['page'], $count);
        }
        $list = $model->getList('parkwash_recharge_record', $condition, $pagesize['limitstr']);
        $cardType = $model->getList('parkwash_recharge_type', null, null, 'sort desc');
        $cardType = array_column($cardType, 'name', 'id');

        if ($list) {
            $promoes = array_filter(array_column($list, 'promo_id'));
            if ($promoes) {
                $employees = $model->getList('parkwash_employee', ['id' => ['in', $promoes]], null, null, 'id,store_name,realname,telephone');
                $employees = array_column($employees, null, 'id');
            }
            foreach ($list as $k => $v) {
                $list[$k]['type_name']  = isset($cardType[$v['type_id']]) ? $cardType[$v['type_id']] : $v['type_id'];
                $list[$k]['money']      = round_dollar($v['money']);
                $list[$k]['give']       = round_dollar($v['give']);
                $list[$k]['store_name'] = var_exists($employees[$v['promo_id']], 'store_name', '无');
                $list[$k]['promo_name'] = var_exists($employees[$v['promo_id']], 'realname', '无');
                $list[$k]['promo_tel']  = var_exists($employees[$v['promo_id']], 'telephone', '无');
            }
            unset($employees);
        }

        // 导出
        if ($_GET['export']) {
            $input = [];
            foreach ($list as $k => $v) {
                $input[] = [$v['id'], $v['user_tel'], $v['type_name'], $v['money'], $v['give'], $v['create_time'], $v['store_name'], $v['promo_name'], $v['promo_tel']];
            }
            $model->exportCsv('充值记录', '编号,用户,卡类型,缴费(元),赠送(元),缴费时间,推荐店铺,推荐人,推荐人手机', $input);
        }

        return [
            'pagesize'   => $pagesize,
            'list'       => $list,
            'cardType'   => $cardType,
            'totalMoney' => $totalMoney,
            'totalGive'  => $totalGive
        ];
    }

    /**
     * 会员管理
     */
    public function user ()
    {
        $model = new XicheManageModel();
        $userModel = new UserModel();

        $condition = [];
        if ($_GET['status']) {
            if ($_GET['status'] == 1) {
                // 普通用户
                $condition['vip_expire'] = null;
            } else if ($_GET['status'] == 2) {
                // 会员用户
                $condition['vip_expire'] = ['>', date('Y-m-d H:i:s', TIMESTAMP)];
            } else if ($_GET['status'] == 3) {
                // 过期会员
                $condition['vip_expire'] = ['<', date('Y-m-d H:i:s', TIMESTAMP)];
            }
        }
        if ($_GET['telephone']) {
            $userInfo = $userModel->getUserInfoCondition([
                'member_name' => $_GET['telephone']
            ]);
            $condition['uid'] = $userInfo ? $userInfo['member_id'] : 0;
        }
        if ($_GET['start_time'] && $_GET['end_time']) {
            $condition['create_time'] = ['between', [$_GET['start_time'] . ' 00:00:00', $_GET['end_time'] . ' 23:59:59']];
        }

        $count = $model->getCount('parkwash_usercount', $condition);
        $pagesize = getPageParams($_GET['page'], $count);
        $list = $model->getList('parkwash_usercount', $condition, $pagesize['limitstr'], 'create_time desc');
        if ($list) {
            $cmUserList = $userModel->getUserList(['member_id' => ['in', array_column($list, 'uid')]], 'member_id,member_name,available_predeposit');
            $cmUserList = array_column($cmUserList, null, 'member_id');
            foreach ($list as $k => $v) {
                $list[$k]['telephone'] = isset($cmUserList[$v['uid']]) ? $cmUserList[$v['uid']]['member_name'] : '已删';
                $list[$k]['cb'] = isset($cmUserList[$v['uid']]) ? $cmUserList[$v['uid']]['available_predeposit'] : '已删'; // 车币
                $list[$k]['isvip'] = $v['vip_expire'] ? (strtotime($v['vip_expire']) > TIMESTAMP ? '是' : '已过期') : '否';
                $list[$k]['money'] = round_dollar($v['money']);
            }
            unset($cmUserList);
        }

        return [
            'pagesize' => $pagesize,
            'list' => $list
        ];
    }

    /**
     * 日志详情
     */
    public function logInfo () {
        $model = new XicheManageModel();
        if (!$logInfo = $model->getLogInfo(getgpc('id'))) {
            return error('参数错误');
        }

        $this->render('XicheManage/view.html', [
            'parameters' => print_r(json_decode($logInfo['content'],true),true)
        ]);
    }

    /**
     * 日志删除
     */
    public function logDelete () {
        return (new XicheManageModel())->logDelete(getgpc('id'));
    }

    /**
     * 日志管理
     */
    public function log () {
        $condition = [];
        if ($_GET['uid']) {
            $condition[] = 'uid = ' . intval($_GET['uid']);
        }
        if ($_GET['devcode']) {
            $condition[] = 'devcode = "' . addslashes($_GET['devcode']) . '"';
        }
        if ($_GET['orderno']) {
            $condition[] = 'orderno = "' . addslashes($_GET['orderno']) . '"';
        }

        $model = new XicheManageModel();
        $count = $model->getCount('xiche_log', $condition);
        $pagesize = getPageParams($_GET['page'], $count);
        $list = $model->getList('xiche_log', $condition, $pagesize['limitstr']);

        return [
            'pagesize' => $pagesize,
            'list' => $list
        ];
    }

    /**
     * 系统配置
     */
    public function config ()
    {
        $list = (new XicheManageModel())->getList('config', ['app' => 'xc'], null);
        return compact('list');
    }

    /**
     * 编辑配置
     */
    public function configUpdate ()
    {
        if (submitcheck()) {
            return (new XicheManageModel())->configUpdate($_POST);
        }

        $info = (new XicheManageModel())->getConfigInfo(getgpc('id'));
        return compact('info');
    }

    /**
     * 登录
     */
    public function login ()
    {
        // 提交登录
        if (submitcheck()) {

            if (!$this->checkImgCode(strval($_POST['imgcode']))) {
                return error('验证码错误');
            }

            // 管理员白名单
            $administrator = [
                '15208666791'
            ];
            $config = getConfig('xc', 'admin');
            $config = $config ? explode("\n", $config) : [];
            $administrator = array_merge($administrator, $config);

            if (in_array($_POST['telephone'], $administrator)) {
                // 超管登录
                $model = new UserModel();
                $userInfo = $model->getUserInfoCondition([
                    'member_name' => $_POST['telephone']
                ], 'member_id,member_passwd');

                if ($userInfo['member_passwd'] != md5(md5($_POST['password']))) {
                    return error('用户名或密码错误！');
                }

                // 登录成功
                $loginret = $model->setloginstatus($userInfo['member_id'], uniqid());
                if ($loginret['errorcode'] !== 0) {
                    return $loginret;
                }
                set_cookie('adminid', Aes::encrypt(json_encode([
                    'uid' => $userInfo['member_id'],
                    'nickname' => $_POST['telephone'],
                    'role' => [1],
                    'permission' => ['ANY']
                ])));
            } else {
                // 店长登录
                $result = (new AdminModel())->login([
                    'username' => $_POST['telephone'],
                    'password' => md5($_POST['password'])
                ]);

                if ($result['errorcode'] !== 0) {
                    return $result;
                }
                set_cookie('adminid', Aes::encrypt(json_encode($result['result'])));
            }

            return success('OK');
        }

        return [];
    }

    /**
     * 登出
     */
    public function logout ()
    {
        (new UserModel())->logout($this->_G['user']['uid']);
        set_cookie('adminid', null);
        $this->success('登出成功', gurl('xicheManage/login'), 0);
    }

}
