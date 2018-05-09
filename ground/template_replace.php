<?php
/*
* Copyright © 2018-present, Knowlesys, Inc.
* All rights reserved.
* 
* template_replace.php
* 
* Author: HFX 2018-03-19 15:11
*/

$search = ['${a}', '${b}'];
$replace = [
    'AA',
    'BB',
];

$template = 'Hello ${a} World ${b}';

echo str_replace($search, $replace, $template);