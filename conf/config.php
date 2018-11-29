<?php
return [
        'debug' => false,
        'sms_verify' => true,
        'hour_fc' => 2,
        'day_fc' => 10,
        'auth_expire_time' => 600,
        'db' => [
            'mysql' => [
                'db' => 'mysql',
                'server' => '120.25.65.27',
                'port' => 3306,
                'database' => 'chemi_api_v1',
                'user' => 'root',
                'pwd' => '5ff4854f40b8eb4c56',
                'tablepre' => 'pro_'
            ],
            'chemiv2' => [
                'db' => 'mysql',
                'server' => '120.25.65.27',
                'port' => 3306,
                'database' => 'chemiv2',
                'user' => 'root',
                'pwd' => '5ff4854f40b8eb4c56',
                'tablepre' => ''
            ],
            'chemiaccount' => [
                'db' => 'mysql',
                'server' => '120.25.65.27',
                'port' => 3306,
                'database' => 'chemiaccount',
                'user' => 'root',
                'pwd' => '5ff4854f40b8eb4c56',
                'tablepre' => ''
            ]
        ]
];