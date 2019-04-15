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
use function Swagger\scan;

class Index extends \ActionPDO {

    public function __init ()
    {
        header('Access-Control-Allow-Origin: *'); // 允许任意域名发起的跨域请求
        header('Access-Control-Allow-Headers: X-Requested-With,X_Requested_With');
        if (!isset($_SERVER['PHP_AUTH_USER']) ||
            !isset($_SERVER['PHP_AUTH_PW']) ||
            $_SERVER['PHP_AUTH_USER'] != 'admin' ||
            $_SERVER['PHP_AUTH_PW'] != 'chemi') {
            header('HTTP/1.1 401 Unauthorized');
            header('WWW-Authenticate: Basic realm="Administrator Secret"');
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

    public function entryPage () {

        $start_date = strtotime('2018-5-1');
        do {
            $end_date = mktime(0, 0, 0, date('m', $start_date) + 1,date('d', $start_date),date('Y', $start_date)) - 1;
            $date[date('Y-m-d', $start_date)] = [];
            for ($i = $start_date; $i < $end_date; $i += 86400) {
                $date[date('Y-m-d', $start_date)][] = [$i, $i + 86400 - 1];
            }
            $start_date = $end_date + 1;
        } while ($start_date < TIMESTAMP);

        foreach ($date as $k => $v) {
            $table_exists = DB::getInstance('park')->find('SELECT table_name FROM information_schema.TABLES WHERE table_name = "chemi_stop_entry_' . date('Ym', strtotime($k)) . '" LIMIT 1');
            if (!$table_exists) {
                $show_table = DB::getInstance('park')->find('show create table chemi_stop_entry');
                $create_table = $show_table['Create Table'];
                $create_table = str_replace('chemi_stop_entry', 'chemi_stop_entry_' . date('Ym', strtotime($k)), $create_table);
                var_dump(DB::getInstance('park')->query($create_table));
                exit;
                if (!DB::selectOne('SELECT table_name FROM information_schema.TABLES WHERE table_name = "' . $table_name . '" LIMIT 1')) {
                    return false;
                }
            }

            foreach ($v as $kk => $vv) {
                $list = DB::getInstance('park')->table('chemi_stop_entry')->where(['outpark_time' => ['between', $vv]])->select();
                if ($list) {
                    echo count($list);
                    exit;
                }

            }
        }

        exit;

        $table_exists = DB::getInstance('park')->query('SELECT table_name FROM information_schema.TABLES WHERE table_name = "' . $table_name . '" LIMIT 1');

    }

    public function logger () {

        $path = trim_space(ltrim($_GET['path'], '/'));
        $path = ltrim(str_replace('.', '', $path), '/');
        $path = $path ? $path : (date('Ym') . '/' . date('Ymd') . '_debug');
        $path = APPLICATION_PATH . '/log/' . $path . '.log';
        $list = get_list_dir(APPLICATION_PATH . '/log');
        foreach ($list as $k => $v) {
            $list[$k] = str_replace(APPLICATION_PATH . '/log', '', $v);
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
