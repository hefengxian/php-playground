<?php
/*
* Copyright © 2018-present, Knowlesys, Ltd.
* All rights reserved.
* 
* microtime.php
* 
* Author: HFX 2018-10-17 16:42
*/

var_dump(date('YmdHis'));
var_dump(md5(microtime()));
var_dump(microtime(true));

$ext = pathinfo('/tmp/abc.zip.abc.xz', PATHINFO_EXTENSION);
var_dump($ext);