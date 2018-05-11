<?php
/*
* Copyright © 2018-present, Knowlesys, Inc.
* All rights reserved.
* 
* match_arabics.php
* 
* Author: HFX 2018-05-11 15:21
*/

$stringContainsArabic = "https://www.youtube.com/123/بسم/abc/بيان بالصفحات والمواد المرئية";

/**
 * 处理包含阿语（从右到左）的字符串
 *
 * 处理生成 MS Office Word 报表中 阿语 + 其他语言 混输导致显示错误的问题
 *
 * @param string $stringContainsArabic 包含 Arabic 的字符串
 * @return string 替换完成之后的字符串
 */
function processArabic($stringContainsArabic)
{
    $regex = "/([\p{Arabic}\s]+)/iu";
    $replace = "<w:r><w:rPr><w:rtl/></w:rPr><w:t>$1</w:t></w:r>";
    $result = preg_replace($regex, $replace, $stringContainsArabic);
    return $result;
}


/**
 * 判断一个字符串中是否包含阿语字符
 *
 * @param string $string 要判断的字符串
 * @return bool 包含返回 true，否则返回 false
 */
function isContainsArabic($string)
{
    return preg_match("/\p{Arabic}/iu", $string) == 1;
}


if (isContainsArabic($stringContainsArabic)) {
    var_dump(processArabic($stringContainsArabic));
}