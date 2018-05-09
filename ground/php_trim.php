<?php
/*
* Copyright © 2018-present, Knowlesys, Inc.
* All rights reserved.
* 
* php_trim.php
* 
* Author: HFX 2018-03-22 16:01
*/

// trim 函数的 bug，全角的空格去不掉

$str = "　　ABC   　   ";

echo trim(trim($str, "　")) . 'BCD';

echo json_encode(['a' => 1] + ['b' => 2, 'a' => 3, 'b']);

echo PHP_EOL;

$param = 1;
$funcA = function ($val) use (&$param) {
    $param++;
    return $param + $val;
};
$funcB = function () use ($param) {
    var_dump($param);
    $param++;
    return $param;
};
var_dump($funcA(2)); //结果:

var_dump($funcB()); //结果:
// var_dump($param); //结果:


var_dump(date('Y-m-d H:i:s'));