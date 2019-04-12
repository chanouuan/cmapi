<?php

namespace app\library;

class TimeWheel {

    private $timer = null;

    private $cur_slot = 0;

    private $cur_time = 0;

    function __construct ()
    {
        $timewheel = F('timewheel');
        if (!$timewheel) {
            $timewheel = [
                [
                    'name' => '10s',
                    'type' => 'polling',
                    'timeout' => 10
                ],
                [
                    'name' => '600s',
                    'type' => 'polling',
                    'timeout' => 600
                ],
                [
                    'name' => '0h',
                    'type' => 'dayofhour',
                    'timeout' => 0,
                    'lasttime' => ''
                ],
                [
                    'name' => '1h',
                    'type' => 'dayofhour',
                    'timeout' => 1,
                    'lasttime' => ''
                ]
            ];
            F('timewheel', $timewheel);
        }
        $this->cur_time = TIMESTAMP;
        $this->timer = $timewheel;
        $this->cur_slot = date('s', $this->cur_time) + date('i', $this->cur_time) * 60 + date('H', $this->cur_time) * 3600;
    }

    function tick ()
    {
        if (empty($this->timer)) {
            return [];
        }
        $timer = [];
        foreach ($this->timer as $k => $v) {
            switch ($v['type']) {
                case 'polling':
                    // 轮询
                    if ($this->cur_slot % $v['timeout'] == 0) {
                        $timer[] = $v['name'];
                    }
                    break;
                case 'dayofhour':
                    // 每天固定时间
                    if (date('G', $this->cur_time) == $v['timeout'] && date('YmdH', $this->cur_time) != $v['lasttime']) {
                        $timer[] = $v['name'];
                        $this->timer[$k]['lasttime'] = date('YmdH', $this->cur_time);
                    }
                    break;
            }
        }
        F('timewheel', $this->timer);
        return $timer;
    }

}
