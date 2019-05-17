<?php
/*
* Copyright © 2018-present, Knowlesys, Inc.
* All rights reserved.
* 
* index.php
* 
* Author: HFX 2018-03-12 10:34
*/

/*$string = '192.168.1.1 (Login) 09-16-2015 21:46:41 UTC;192.168.1.2 ( Unknown ) 09-23-2015 05:16:09  UTC;37.216.10.224 IP Capture Date: September 23, 2017 at 04:39:53 UTC;';

// 按照 ; 切割
$ips = explode(';', $string);
foreach ($ips as $ip) {
    if (empty($ip)) {
        continue;
    }
    // 按照空格切割
    $pieces = preg_split('/\s+/', $ip);
    $len = count($pieces);

    if ($len >= 5) {
        $_ip = $pieces[0];
        $regExp = '/.*IP Capture Date: (\w+ \d{2}, \d{4}) at (\d{2}:\d{2}:\d{2}) UTC/';
        if (preg_match($regExp, $ip, $matches)) {
            if (count($matches) == 3) {
                $date = sprintf('%s %s', $matches[1], $matches[2]);
                // September 23, 2017 04:39:53
                $dateObj = DateTime::createFromFormat('F d, Y H:i:s', $date, new DateTimeZone('+0000'));
                $dateObj->setTimezone(new DateTimeZone('+0300'));
                var_dump($dateObj->format('Y-m-d H:i:s'));
            }
        } else {
            $date = sprintf('%s %s', $pieces[$len - 3], $pieces[$len - 2]);
            $dateObj = DateTime::createFromFormat('m-d-Y H:i:s', $date, new DateTimeZone('+0000'));
            $dateObj->setTimezone(new DateTimeZone('+0300'));
        }

        // var_dump($_ip);
        // var_dump($date);
        // var_dump($dateObj->format('Y-m-d H:i:s'));
    }

}*/

error_reporting(E_ALL);

// $files = explode(',', '');
// var_dump(count($files));
// var_dump($files);

$array1 = array(0 => 'zero_a', 2 => 'two_a', 3 => 'three_a');
$array2 = array(1 => 'one_b', 3 => 'three_b', 4 => 'four_b');
$result = array_merge($array1, $array2);
var_dump($result);












































