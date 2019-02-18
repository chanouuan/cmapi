<?php

namespace controllers;

use \library\DB;

class Index extends \ActionPDO {

    public function __init ()
    {

    }

    public function index () {

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
        $count = \library\DB::getInstance()->table('__tablepre__trades')->field('count(1)')->where($condition)->count();
        $pagesize = getPageParams($_GET['page'], $count, 50);
        $list = \library\DB::getInstance()->table('__tablepre__trades')->field('*')->where($condition)->order('id desc')->limit($pagesize['limitstr'])->select();

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

}