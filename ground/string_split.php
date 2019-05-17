<?php
/*
* Copyright © 2018-present, Knowlesys, Inc.
* All rights reserved.
* 
* string_split.php
* 
* Author: HFX 2018-03-12 11:08
*/

// 数字字母分割（Excel 的坐标）

$coordinate = 'ADDB4523';

$pieces = preg_split('/([a-zA-Z]+)(\d+)/', $coordinate, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

print_r($pieces);

function detectCharset($source)
{
    // 直接从源码获取，获取不到再使用字符串检测（不准）
    $html5CharsetRegex = '/<meta\s+charset=["]?([0-9a-zA-Z-^>]+)["]?\s*[\/]?>/i';
    $html4CharsetRegex = '/<meta\s+http-equiv=["\']?content-type["\']?\s+content=["\']?text\/html;\s*charset=([0-9a-zA-Z-^>]+)["\']?\s*[\/]?>/i';

    $charset = '';
    $matches = [];

    // 优先检测 HTML5
    preg_match($html5CharsetRegex, $source, $matches);
    if (empty($matches[1])) {
        // 再检测 HTML4
        preg_match($html4CharsetRegex, $source, $matches);
        if (!empty($matches[1])) {
            $charset = $matches[1];
        }
    } else {
        $charset = $matches[1];
    }

    /*$charsetList = array(
        'EUC-CN' => 'GB2312',
        'CP936' => 'GBK',
    );
    if (empty($charset)) {
        $charset = mb_detect_encoding($source, 'UTF-8, GB2312, GBK', true);
        if (!empty($charset)) {
            $charset = isset($charsetList[$charset]) ? $charsetList[$charset] : $charset;
        } else {
            $charset = 'GBKKKK';
        }
    }*/
    return $charset;
}

/*$htmlString = <<<EOF
<head id="Head1"><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><title>
	建发纸业报价_优质企业_资讯帖子_ - 中国制造交易网
</title><meta name="description" content="中国制造交易网建发纸业专区，为您汇集了最全面最新的建发纸业信息,包括建发纸业价格信息,建发纸业厂家信息,建发纸业资讯等信息。" /><link rel="stylesheet" type="text/css" href="http://www.c-c.com/aversion2014/css/global.css?v1.222" /><link type="text/css" href="/page-hots/css/juhe.css?v1" rel="stylesheet" />
<script type="text/javascript" src="http://www.c-c.com/aversion2014/js/jquery 1.7.1.js"></script>
<script type="text/javascript" src="/page-hots/js/jquery.SuperSlide.2.1.1.js"></script>
</head>
EOF;*/
$htmlString = <<<EOF
<head>
<meta http-equiv='X-UA-Compatible' content='IE=edge,chrome=1'>
<meta http-equiv='content-type' content='text/html; charset=big5'>
<meta name='viewport' content='width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no'>
<title>習近平：打造中阿命運共同體 - 香港文匯報</title>
<meta name="title" content="習近平：打造中阿命運共同體">
<meta name='description' content='習近平：打造中阿命運共同體'>
<link rel='stylesheet' type='text/css' href='//assets.wenweipo.com/news/css/detail.css'>
<link rel="stylesheet" type="text/css" href="//assets.wenweipo.com/share/css/share.min.css">
<script type=text/javascript src='http://ad.wenweipo.com/adjs/modiaAdsHeader.js'></script>
</head>
EOF;

var_dump(detectCharset($htmlString));