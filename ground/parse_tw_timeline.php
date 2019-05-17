<?php
/*
* Copyright © 2018-present, Knowlesys, Ltd.
* All rights reserved.
* 
* parse_tw_timeline.php
* 
* Author: HFX 2018-11-21 09:56
*/

$tlJson = file_get_contents(__DIR__ . '/../data/search_obama.json');

$tlData = json_decode($tlJson, true);

$tlHTML = $tlData['items_html'];
// echo $tlHTML;

file_put_contents(__DIR__ . '/../data/tw_timeline.html', $tlHTML);

// $tlText = strip_tags($tlHTML);
// echo $tlText;
/*$regex = '/id="stream-item-tweet-(\d+)".*?data-screen-name="(\w+)" data-name="(.*?)" data-user-id="(\d+)".*?data-time="(\d+)".*?<p class="TweetTextSize TweetTextSize--normal js-tweet-text tweet-text" lang="(\w+)".*?>(.*?)<\/p>.*?data-image-url="(\w+)".*?data-tweet-stat-count="(\d+)".*?data-tweet-stat-count="(\d+)".*?data-tweet-stat-count="(\d+)".*?/is';
preg_match_all($regex, $tlHTML, $matches);
print_r($matches);*/

// 图片、视频
echo '<pre>';
if (preg_match_all('/<li class="js-stream-item.*?>(.*?<ul>.*?<\/ul>.*?){1}<\/li>/is', $tlHTML, $matches) !== false) {
    foreach ($matches[1] as $match) {
        // var_dump($match);

        // 获取
        /*if (preg_match_all('/data-reply-to-users-json="(\[.*?\])"/is', $match, $mch)) {
            $jsonStr = html_entity_decode($mch[1][0]);
            var_dump(json_decode($jsonStr, true));
            continue;
        }*/

        // 类型 1:新发布微博,2:评论微博,3:转发微博 -1
        $msgType = 1;       // 新发微博
        if (preg_match_all('/data-retweet-id="(.*?)"/is', $match, $mch) !== false) {
            if (count($mch[1]) > 0) {
                $msgType = 3;
            }
        }

        $info = [];
        if (preg_match_all('/data-tweet-id="(\d+)"/is', $match, $mch) !== false) {
            $info['msg_id'] = $mch[1][0];
        }
        if (preg_match_all('/data-screen-name="(\w+)"/is', $match, $mch) !== false) {
            $info['screen_name'] = $mch[1][0];
        }
        if (preg_match_all('/data-time="(\d+)"/is', $match, $mch) !== false) {
            $info['publish_time'] = date('Y-m-d H:i:s', $mch[1][0]);
        }
        if (preg_match_all('/lang="(\w+)"/is', $match, $mch) !== false) {
            $info['language'] = $mch[1][0];
        }
        if (preg_match_all('/data-image-url="(.*?)"/is', $match, $mch) !== false) {
            $info['images'] = $mch[1];
        }
        if (preg_match_all('/data-tweet-stat-count="(\d+)".*?data-tweet-stat-count="(\d+)".*?data-tweet-stat-count="(\d+)"/is', $match, $mch) !== false) {
            $info['reply_count'] = $mch[1][0];
            $info['forward_count'] = $mch[2][0];
            $info['like_count'] = $mch[3][0];
        }
        if (preg_match_all('/<p class=".*?js-tweet-text tweet-text".*?>(.*?)<\/p>/is', $match, $mch) !== false) {
            // $info['content'] = strip_tags($mch[1][0]);
            $content = $mch[1][0];

            // 提取 @
            if (preg_match_all('/<s>@<\/s>.*?<b>(.*?)<\/b>/is', $match, $mch) !== false) {
                $info['mentioned'] = $mch[1];
            }
            // 提取 #
            if (preg_match_all('/<s>#<\/s>.*?<b>(.*?)<\/b>/is', $match, $mch) !== false) {
                $info['hashtag'] = $mch[1];
            }
            // 处理 HTML 实体
            $content = html_entity_decode($content);

            // 处理链接没有和正文间隔一个空格问题
            $content = preg_replace('/<a.*?>(.*?)<\/a>/is', ' ${1}', $content);

            // 去掉标签
            $content = strip_tags($content);
            // 去掉多余空格(HTML 实体里的空格去不掉)
            $info['content'] = preg_replace('/\s{2,}/is', ' ', $content);
        }

        // data-retweet-id="639349752887246848"
        $isRetweet = false;
        if (preg_match_all('/data-retweet-id="(.*?)"/is', $match, $mch) !== false) {
            $isRetweet = count($mch[1]) > 0;
        }
        $info['retween'] = $isRetweet;
        print_r($info);
    }
}



/*print_r($matches[2]);
foreach ($matches[2] as $match) {
    echo date('Y-m-d H:i:s', $match) . PHP_EOL;
}*/
