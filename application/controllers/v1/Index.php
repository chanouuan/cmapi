<?php
/**
 * @SWG\Swagger(
 *     swagger="2.0",
 *     schemes={"http"},
 *     basePath="/v1/",
 *     @SWG\Info(
 *          title="App客户端接口",
 *          version="1.0",
 *          description="说明：{用户头像:uid 取余 32}/{用户ID}/avatar/origin.jpg
车辆行驶证:{uid 取余 32}/{用户ID}/travel_license/16位随机码.jpg
身份认证:{uid 取余 32}/{用户ID}/idcard/16位随机码.jpg
共享车位图片：{uid 取余 32}/{用户ID}/shareparkinglot/16位随机码.jpg
驾驶证：{uid 取余 32}/{用户ID}/driving_licence/16位随机码.jpg
例如张三用户ID为12，头像路径为：12/12/avatar/origin.jpg"
 *     )
 * )
 */

/**
 * @SWG\Definition(
 *   definition="ReturnStatus",
 *              @SWG\Property(
 *                  property="status",
 *                  type="integer",
 *                  description="1成功，0失败"
 *              ),
 *              @SWG\Property(
 *                  property="msg",
 *                  type="string",
 *                  description="错误原因"
 *              )
 * )
 */

namespace app\controllers;

use app\library\DB;
//use function Swagger\scan;

class Index extends \ActionPDO {

    public function __init ()
    {
        \DebugLog::_debug(false);
        header('Access-Control-Allow-Origin: *'); // 允许任意域名发起的跨域请求
        header('Access-Control-Allow-Headers: X-Requested-With,X_Requested_With');
        if (!isset($_SERVER['PHP_AUTH_USER']) ||
            !isset($_SERVER['PHP_AUTH_PW']) ||
            $_SERVER['PHP_AUTH_USER'] != 'admin' ||
            $_SERVER['PHP_AUTH_PW'] != 'chemi_123456') {
            header('HTTP/1.1 401 Unauthorized');
            http_response_code(401);
            header('WWW-Authenticate: Basic realm="Administrator Secret"');
            exit('Administrator Secret!');
        }
    }

    /**
     * @SWG\Tag(
     *   name="公共接口",
     *   description="公共调用接口",
     * )
     */

    /**
     *
     * @SWG\Post(
     *     path="/public/trafficviolation",
     *     summary="违章查询",
     *     tags={"公共接口"},
     *     @SWG\Parameter(
     *         name="city",
     *         type="string",
     *         in="query",
     *         required=true,
     *         description="城市代码"
     *     ),
     *     @SWG\Parameter(
     *         name="carNo",
     *         type="integer",
     *         in="query",
     *         required=true,
     *         description="号牌号码完整7位"
     *     ),
     *     @SWG\Parameter(
     *         name="buyer",
     *         type="array",
     *         in="query",
     *         description="购买方信息",
     *         @SWG\Items(
     *             type="string",
     *             description="图片链接"
     *         )
     *     ),
     *     @SWG\Response(
     *          response=200,
     *          description="Example extended response",
     *          ref="$/responses/Json",
     *          @SWG\Schema(
     *              @SWG\Property(
     *                  property="data",
     *                  ref="$/definitions/ReturnStatus"
     *              )
     *          )
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="接口地址错误"
     *     ),
     * )
     */
    public function index () {

//        $swagger = scan(dirname(__DIR__) . '/../', ['exclude'=>['AdminController.php']]);
//        $swagger = json_encode($swagger);
//        echo $swagger;
//        $client = new \JPush\Client('ee348c39803e470ba7ed826c', '24b5cfd0f3d96968e236f8ba');
//        $client->push()
//            ->setPlatform('all')
//            ->addAllAudience()
//            ->setNotificationAlert('Hello, JPush')
//            ->send();

//        $parkWashModel = new \app\models\ParkWashModel();
//        echo '<br>新订单已发: <br>';
//        print_r($parkWashModel->sendJPush('您有新的订单', '车秘未来洗车', [
//            'action'  => 'newOrderNotification',
//            'orderid' => 1
//        ]));
//        echo '<br>取消订单已发: <br>';
//        print_r($parkWashModel->sendJPush('用户取消订单', '车秘未来洗车', [
//            'action'  => 'cancelOrderNotification',
//            'orderid' => 1
//        ], null, 1));
//        echo '<br>开始服务已发: <br>';
//        print_r($parkWashModel->sendJPush('老王已开始服务', '车秘未来洗车', [
//            'action'  => 'takeOrderNotification',
//            'orderid' => 1
//        ], null, 1));
    }

    public function total () {
        $condition = [];
        if ($_GET['platform']) {
            $condition[] = 'platform = ' . intval($_GET['platform']);
        }
        if ($_GET['trade_no']) {
            $condition[] = 'trade_no = ' . addslashes($_GET['trade_no']);
        }
        if ($_GET['type']) {
            $condition[] = 'type = ' . intval($_GET['type']);
        }
        if ($_GET['uid']) {
            $condition[] = 'uid = ' . intval($_GET['uid']);
        }
        $count = DB::getInstance()->table('__tablepre__trades')->field('count(1)')->where($condition)->count();
        $pagesize = getPageParams($_GET['page'], $count, 50);
        $list = DB::getInstance()->table('__tablepre__trades')->field('*')->where($condition)->order('id desc')->limit($pagesize['limitstr'])->select();

        return [
            'pagesize' => $pagesize,
            'list' => $list
        ];
    }

    public function entryPage ()
    {
        exit;
        set_time_limit(0);
        \DebugLog::_debug(true);
        $start_date = strtotime('2018-5-1');
        do {
            $end_date = mktime(0, 0, 0, date('m', $start_date) + 1,date('d', $start_date),date('Y', $start_date)) - 1;
            $date[date('Y-m-d', $start_date)] = [];
            for ($i = $start_date; $i < $end_date; $i += 86400) {
                $date[date('Y-m-d', $start_date)][] = [$i, $i + 86400 - 1];
            }
            $start_date = $end_date + 1;
        } while ($start_date < TIMESTAMP);

        $error = [];
        foreach ($date as $k => $v) {
            $table = 'chemi_stop_entry_' . date('Ym', strtotime($k));
            $table_exists = DB::getInstance('park')->find('SELECT table_name FROM information_schema.TABLES WHERE table_name = "' . $table . '" LIMIT 1');
            if (!$table_exists) {
                $show_table = DB::getInstance('park')->find('show create table chemi_stop_entry');
                $create_table = $show_table['Create Table'];
                $create_table = str_replace('chemi_stop_entry', $table, $create_table);
                DB::getInstance('park')->query($create_table);
            }
            foreach ($v as $kk => $vv) {
                $column = DB::getInstance('park')->query('insert into ' . $table . ' select * from chemi_stop_entry where outpark_time between ' . $vv[0] . ' and ' . $vv[1]);
                if (!$column) {
                    $error[$table] = $column;
                } else {
                    DB::getInstance('park')->query('delete from chemi_stop_entry where outpark_time between ' . $vv[0] . ' and ' . $vv[1]);
                }
            }
        }

        print_r($error);
        exit;
    }

    public function logger ()
    {
        $path = trim_space(ltrim($_GET['path'], '/'));
        $path = ltrim(str_replace('.', '', $path), '/');
        $path = $path ? $path : (date('Ym') . '/' . date('Ymd') . '_debug');
        $path = APPLICATION_PATH . '/log/' . $path . '.log';
        if ($_GET['dir']) {
            $list = get_list_dir(APPLICATION_PATH . '/log');
            if (count($list) > 30) {
                $list = array_slice($list, count($list) - 30);
            }
            foreach ($list as $k => $v) {
                $list[$k] =  '<a href="' . (APPLICATION_URL . '/index/logger?path=' . str_replace(APPLICATION_PATH . '/log/', '', substr($v, 0, -4)) . '&dir=1') . '">' . str_replace(APPLICATION_PATH . '/log', '', $v) . '</a> ' . byte_convert(filesize($v)) . ' <a href="' . APPLICATION_URL . '/index/logger?path=' . str_replace([APPLICATION_PATH . '/log', '.log'], '', $v) . '&dir=1&clear=1">DEL</a>';
            }
        }
        if ($_GET['clear']) {
            if (file_exists($path)) {
                unlink($path);
            }
        }
        ?>
        <!doctype html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title></title>
            <meta name="viewport" content="width=device-width,user-scalable=yes, minimum-scale=1, initial-scale=1"/>
        </head>
        <body>
            <pre><?=implode("\n",$list)?></pre>
            <pre><?=file_exists($path)?file_get_contents($path):'404'?></pre>
        </body>
        </html>
        <?php
        exit(0);
    }

}
