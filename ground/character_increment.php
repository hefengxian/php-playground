<?php
/*
* Copyright © 2018-present, Knowlesys, Inc.
* All rights reserved.
* 
* character_increment.php
* 
* Author: HFX 2018-03-12 11:30
*/


// http://php.net/manual/zh/language.operators.increment.php

// PHP 自增、自减运算对字符的操作

$char = 'Z';
$char++;
var_dump($char);

$char = 'AZ';
var_dump(++$char);

$string = 'xaa';
do {
    echo $string++ . PHP_EOL;
} while ($string <= 'xal');


$string = 'xaa';
while ($string <= 'xal') {
    echo $string . PHP_EOL;
    $string++;
}