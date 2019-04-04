<?php
return [
        'debug' => false,
        'sms_verify' => true,
        'hour_fc' => 2,
        'day_fc' => 10,
        'auth_expire_time' => 600,
        'app_secret' => '123456',
        'db' => [
            'mysql' => [
                'db' => 'mysql',
                'server' => '120.79.64.144',
                'port' => 3306,
                'database' => 'chemi_api_v1',
                'user' => 'root',
                'pwd' => '32c6a8x#y9g7fa9c57p1w3v',
                'tablepre' => 'pro_'
            ],
            'chemiv2' => [
                'db' => 'mysql',
                'server' => '120.79.64.144',
                'port' => 3306,
                'database' => 'chemiv2',
                'user' => 'root',
                'pwd' => '32c6a8x#y9g7fa9c57p1w3v',
                'tablepre' => ''
            ],
            'chemiaccount' => [
                'db' => 'mysql',
                'server' => '120.79.64.144',
                'port' => 3306,
                'database' => 'chemiaccount',
                'user' => 'root',
                'pwd' => '32c6a8x#y9g7fa9c57p1w3v',
                'tablepre' => ''
            ],
            'park' => [
                'db' => 'mysql',
                'server' => '120.79.64.144',
                'port' => 3306,
                'database' => 'park',
                'user' => 'root',
                'pwd' => '32c6a8x#y9g7fa9c57p1w3v',
                'tablepre' => ''
            ]
        ]
];

// chemiv2 用于获取用户车币余额、消费车币洗车
// chemiaccount 用于记录用户洗车消费记录
