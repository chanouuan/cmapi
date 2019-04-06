<?php

/**
 * 经纬度操作类
 * @cyq
 */

namespace app\library;

class LocationUtils {

    public static $earthRadius = 6372.796924;

    public static function getRandomLocation ($center, $distance = 1000)
    {
        if (!$center = self::checkLocation($center)) {
            return null;
        }
        $distance = $distance < 50 ? 50 : $distance;
        $distance = $distance / 1000;
        $cost = 0.01 * $distance;
        $location = [];
        $location['lat'] = self::formatGPS($center['lat'] + mt_rand(-$cost * 1000000, $cost * 1000000) / 1000000);
        $location['lon'] = self::formatGPS($center['lon'] + mt_rand(-$cost * 1000000, $cost * 1000000) / 1000000);
        return $location;
    }

    public static function getDistance ($location_left, $location_right)
    {
        if (!is_array($location_left)) {
            list ($left_lon, $left_lat) = explode(',', $location_left);
        } else {
            $left_lon = isset($location_left['lon']) ? $location_left['lon'] : $location_left[0];
            $left_lat = isset($location_left['lat']) ? $location_left['lat'] : $location_left[1];
        }
        if (!$left_lon || !$left_lat) {
            return 0;
        }
        if (!is_array($location_right)) {
            list ($right_lon, $right_lat) = explode(',', $location_right);
        } else {
            $right_lon = isset($location_right['lon']) ? $location_right['lon'] : $location_right[0];
            $right_lat = isset($location_right['lat']) ? $location_right['lat'] : $location_right[1];
        }
        if (!$right_lon || !$right_lat) {
            return 0;
        }
        $lat1 = ($left_lat * pi()) / 180;
        $lng1 = ($left_lon * pi()) / 180;
        $lat2 = ($right_lat * pi()) / 180;
        $lng2 = ($right_lon * pi()) / 180;
        $calcLongitude = $lng2 - $lng1;
        $calcLatitude = $lat2 - $lat1;
        $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
        $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
        $calculatedDistance = (self::$earthRadius * 1000) * $stepTwo;
        // 米
        return round($calculatedDistance);
    }

    public static function getCityCenter ($city_name)
    {
        $city_name = trim($city_name);
        if (empty($city_name)) {
            return null;
        }
        $center_location = F('CityCenterLocation');
        if (isset($center_location[$city_name])) {
            return $center_location[$city_name];
        }
        $scan = 'http://restapi.amap.com/v3/config/district?key=93970d0444d2abd81cf00c2c59ae096e&keywords=' . $city_name . '&subdistrict=0&extensions=base';
        try {
            $result = https_request($scan);
        } catch (\Exception $e) {
            return null;
        }
        if ($result['status'] != 1 || !$result['districts'][0]['center']) {
            return null;
        }
        $center_location[$city_name] = [];
        list ($center_location[$city_name]['lon'], $center_location[$city_name]['lat']) = explode(',', $result['districts'][0]['center']);
        $center_location[$city_name] = self::checkLocation($center_location[$city_name]);
        if (!$center_location[$city_name]) {
            return null;
        }
        F('CityCenterLocation', $center_location);
        return $center_location[$city_name];
    }

    public static function checkLocation ($location)
    {
        if (empty($location)) {
            return null;
        }
        $is_string = false;
        if (!is_array($location)) {
            $is_string = true;
            $location = explode(',', $location);
        }
        foreach ($location as $k => $v) {
            if (!self::verifyGPS($v)) {
                return null;
            }
            $location[$k] = self::formatGPS($v);
            if ($k == 1) {
                break;
            }
        }
        return $is_string ? implode(',', $location) : $location;
    }

    private static function verifyGPS ($gps)
    {
        return $gps ? preg_match('/^(-)?\d{1,3}\.\d{1,16}$/', $gps, $match) : false;
    }

    private static function formatGPS ($gps)
    {
        return $gps ? round($gps, 6) : '';
    }

}
