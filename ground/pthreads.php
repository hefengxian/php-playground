<?php
/*
* Copyright © 2018-present, Knowlesys, Inc.
* All rights reserved.
* 
* pthreads.php
* 
* Author: HFX 2018-06-13 12:54
*/

if (!extension_loaded('pthreads')) {
    echo 'pthreads not load' . PHP_EOL;
}

class w extends Thread {
    public function run()
    {
        echo "Hello World {$this->getThreadId()}\n";
    }
}

for ($i = 0; $i < 5; $i++) {
    $thread = new W();
    $thread->start();
}