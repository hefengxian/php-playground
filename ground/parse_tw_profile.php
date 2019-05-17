<?php
/*
* Copyright © 2018-present, Knowlesys, Ltd.
* All rights reserved.
* 
* parse_tw_profile.php
* 
* Author: HFX 2018-11-22 18:54
*/

$rawData = file_get_contents(__DIR__ . '/../data/google_profile.html');

echo '<pre>';

// uid
if (preg_match('/<div.*?role="navigation" data-user-id="(\d+)">/is', $rawData, $matches) !== false) {
    var_dump($matches[1]);
}

// sname
if (preg_match('/<b class="u-linkComplex-target">(.*?)<\/b>/is', $rawData, $matches) !== false) {
    var_dump($matches[1]);
}

// rname
if (preg_match('/<a.*?class="ProfileHeaderCard-nameLink.*?">(.*?)<\/a>/is', $rawData, $matches) !== false) {
    var_dump($matches[1]);
}

// iurl
if (preg_match('/<img class="ProfileAvatar-image.*?" src="(.*?)"/is', $rawData, $matches) !== false) {
    var_dump($matches[1]);
}

// desc
if (preg_match('/<p class="ProfileHeaderCard-bio.*?".*?>(.*?)<\/p>/is', $rawData, $matches) !== false) {
    var_dump($matches[1]);
}

// gt
// ct
// nfri
if (preg_match('/data-nav="following".*?data-count="?(\d+)"?/is', $rawData, $matches) !== false) {
    var_dump($matches[1]);
}

// nfans
if (preg_match('/data-nav="followers".*?data-count="?(\d+)"?/is', $rawData, $matches) !== false) {
    var_dump($matches[1]);
}

// nfav
if (preg_match('/data-nav="favorites".*?data-count="?(\d+)"?/is', $rawData, $matches) !== false) {
    var_dump($matches[1]);
}

// nmsg
if (preg_match('/data-nav="tweets".*?data-count="?(\d+)"?/is', $rawData, $matches) !== false) {
    var_dump($matches[1]);
}

// 没有 lang
// url
// wb_isreal
if (preg_match('/<a href="(\/help\/verified)"/is', $rawData, $matches) !== false) {
    var_dump($matches[1]);
}

// loc
if (preg_match('/<span class="ProfileHeaderCard-locationText.*?>(.*?)<\/span>/is', $rawData, $matches) !== false) {
    var_dump($matches[1]);
}

// 没有 timezone
// pburl
if (preg_match('/<div class="ProfileCanopy-headerBg".*?src="(.*?)"/is', $rawData, $matches) !== false) {
    var_dump($matches[1]);
}