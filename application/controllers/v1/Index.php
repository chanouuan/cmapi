<?php

namespace controllers;

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

}