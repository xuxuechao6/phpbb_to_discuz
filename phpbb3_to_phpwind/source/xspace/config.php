<?php

!defined('R_P') && exit('Forbidden!');

$old_version = 'discuz 7.1'; //版本号

$compile_type = 'mysql'; //数据库类型

$bbs_prefix = 'cdb_'; //论坛数据库表前缀

//X-Space访问入口地址，一定要写全带http，结尾不要加 "/"
$xspace_url =  'http://localhost:1108/xspace';

//PW论坛访问地址,要求同上
$pw_url = 'http://test.com/75sp2';

$step_data = array(
	1 => '好友',
	2 => '分类',
	3 => '表情',
	4 => '日志',
	5 => '附件',
    6 => '去除图片的下载链接',
    7 => '序列化pw_diary的aid',
	8 => '相册',
	9 => '收藏',
	10 => '评论',
	11 => '日志数',
	12 => '图片数',
	13 => '日志和图片数',
    14 => '处理用户留言'
);
?>