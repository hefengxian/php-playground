<?php
/*
* Copyright © 2018-present, Knowlesys, Inc.
* All rights reserved.
* 
* array_sort.php
* 
* Author: HFX 2018-03-12 10:35
*/


$data = [
    ['key' => 'A', 'value' => '9'],
    ['key' => 'B', 'value' => '3'],
    ['key' => 'D', 'value' => '5'],
    ['key' => 'C', 'value' => '2'],
    ['key' => 'E', 'value' => '8'],
];

// 参考文档： http://php.net/manual/zh/array.sorting.php

usort($data, function ($a, $b) {
    return $a['value'] > $b['value'];
});

print_r($data);
