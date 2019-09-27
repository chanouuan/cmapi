<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/26
 * Time: 16:55
 */
error_reporting(1);
set_time_limit(10);

function asyncRequest()
{
    $errno = 0;
    $errstr = '';
    $timeout = 2;

    $fp = fsockopen('192.168.1.164', 84, $errno, $errstr, $timeout);
    if (!$fp) {
        return '连接失败'.$errstr;
    }

    stream_set_timeout($fp, 0, $timeout * 1000);
    //stream_set_blocking($fp, 0);

    $out = "GET /parkWash/login HTTP/1.1\r\n";
    $out .= "Host: 192.168.1.164\r\n";
    $out .= "Connection: Close\r\n\r\n";

    ECHO $result = fwrite($fp, $out);

    while (!feof($fp)) {
        $result .= fgets($fp,2048);
    }
    fclose($fp);
    return $result;
}
//echo "start\r\n";
//echo asyncRequest();
//echo "\r\nend";
//
$fp = fsockopen('ssl://betaparkwash.chemi.ren', 443);
if ($fp) {
    stream_set_blocking($fp,0);
    fwrite($fp, "GET /parkWash/task HTTP/1.1\r\nHost: betaparkwash.chemi.ren\r\nConnection: Close\r\n\r\n");
    fclose($fp);
}

exit;

//$cm = curl_multi_init();
//
//$ch1 = curl_init();
//curl_setopt($ch1, CURLOPT_URL, 'https://parkwash.chemi.ren/parkWash/task');
//curl_setopt($ch1, CURLOPT_USERAGENT, 'Plan-Task');
//curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, FALSE);
//curl_setopt($ch1, CURLOPT_SSL_VERIFYHOST, FALSE);
//curl_setopt($ch1, CURLOPT_RETURNTRANSFER, 0);
//curl_multi_add_handle($cm, $ch1);
//
//$ch2 = curl_init();
//curl_setopt($ch2, CURLOPT_URL, 'http://192.168.1.164:84/parkWash/task');
//curl_setopt($ch2, CURLOPT_USERAGENT, 'Plan-Task');
//curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, FALSE);
//curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, FALSE);
//curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 0);
//curl_multi_add_handle($cm, $ch2);
//
//curl_multi_exec($cm, $active);
//
//curl_multi_remove_handle($cm, $ch1);
//curl_multi_remove_handle($cm, $ch2);
//
//curl_multi_close($cm);



exit;

function http_multi_exec (Closure $addHandle, $return)
{
    $mh = curl_multi_init();
    $curls = $addHandle($mh);

    if (empty($curls)) {
        return null;
    }

    $active = null;

    if (!$return) {
        curl_multi_exec($mh,$active);
    } else {
        do {
            $mrc = curl_multi_exec($mh, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active && $mrc == CURLM_OK) {
            if (curl_multi_select($mh) != -1) {
                do {
                    $mrc = curl_multi_exec($mh, $active);
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }
        }
    }

    $reponse = [];
    foreach ($curls as $k => $v) {
        if ($return) {
            $reponse[$k]['http_code'] = curl_getinfo($v, CURLINFO_HTTP_CODE);
            $reponse[$k]['errno'] = curl_errno($v);
            if ($reponse[$k]['errno']) {
                $reponse[$k]['error'] = curl_error($v);
            } else {
                $reponse[$k]['content'] = curl_multi_getcontent($v);
            }
        }
        curl_multi_remove_handle($mh, $v);
    }

    unset($curls);
    curl_multi_close($mh);

    return $reponse;
}

function http_multi_request (array $urls, $return = true)
{
    return http_multi_exec(function($mh) use($urls, $return) {
        foreach ($urls as $k => $v) {
            if (empty($v['url'])) {
                unset($urls[$k]);
                continue;
            }
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $v['url']);
            curl_setopt($ch, CURLOPT_TIMEOUT, $v['timeout'] ? $v['timeout'] : 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            if (!$return) {
                $v['header'] = $v['header'] ? $v['header'] : [];
                $v['header'][] = 'Connection: Close';
            }
            if ($v['header']) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $v['header']);
            }
            if ($v['post']) {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($v['post']) ? http_build_query($v['post']) : $v['post']);
            }
            $urls[$k] = $ch;
            curl_multi_add_handle($mh, $ch);
        }
        return $urls;
    }, $return);
}

print_r(http_multi_request([
    ['url' => 'http://192.168.1.164:84/parkWash/login'],
    ['url' => 'http://192.168.1.164:84/parkWash/login'],
    ['url' => 'http://192.168.1.164:84/parkWash/login'],
    ['url' => 'http://192.168.1.164:84/parkWash/login']
], false));
