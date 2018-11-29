<?php

set_time_limit(30);
date_default_timezone_set('PRC');

define('APPLICATION_PATH', dirname(__DIR__));
define('APPLICATION_URL', 'http://120.79.64.144:8081/cmapi/public');
define('TIMESTAMP', $_SERVER['REQUEST_TIME']);
define('DEBUG_PASS', '__debug');
define('DEBUG_LEVEL', 3);
if (isset($_SERVER['HTTP_APIVERSION'])) {
    define('APIVERSION', 'v' . intval($_SERVER['HTTP_APIVERSION']));
} else if (isset($_POST['apiversion'])) {
    define('APIVERSION', 'v' . intval($_POST['apiversion']));
} else if (isset($_GET['apiversion'])) {
    define('APIVERSION', 'v' . intval($_GET['apiversion']));
} else {
    define('APIVERSION', 'v1');
}

include APPLICATION_PATH . '/application/library/Common.php';
include APPLICATION_PATH . '/application/library/Init.php';

$controller->run();
