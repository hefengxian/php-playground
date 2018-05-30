<?php
/*
* Copyright © 2018-present, Knowlesys, Inc.
* All rights reserved.
* 
* php_error_reporting_level.php
* 
* Author: HFX 2018-05-28 11:50
*/

error_reporting(E_ALL);

$options = [];

if (isset($options['similar'])) {
    echo 'hello similar';
} else {
    echo 'hello';
}