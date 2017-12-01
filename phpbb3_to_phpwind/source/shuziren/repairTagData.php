<?php
/**
 * 更新标签数据到pw_tagdata,帖子中能打开标签相关的帖子框
 * 将脚本放到pw论坛根目录，执行即可
 */
require_once 'global.php';
$basename = $db_bbsurl.'/'.end(explode('/',$_SERVER['PHP_SELF']));
$percount = 500; // 每次处理的数目查询数

InitGP(array('start'));
empty($start) && $start = 0;

$query = $db->query("SELECT tid,tags FROM pw_tmsgs "
                   ."WHERE tid >0 AND tags !='' "
				   ."ORDER BY tid ASC "
				   ."LIMIT $start, $percount");
while ($rt = $db->fetch_array($query)){
	$tagArr = explode(' ', $rt['tags']);
    foreach ($tagArr AS $val){
        $tagid = $db->get_value("SELECT tagid FROM pw_tags WHERE tagname =".pwEscape($val)." LIMIT 1");
        $tagid && $db->update("REPLACE INTO pw_tagdata(tagid,tid)VALUES(".$tagid.", ".$rt['tid'].")");
    }
}
$db->free_result($query);

$maxid = $db->get_value("SELECT MAX(tid) FROM pw_threads WHERE 1");
$end = $start + $percount;

if($maxid > $end){
	redirect($basename."?start=".$end,"从第 $start 跳转到第 $end 条");
} else {
	echo "标签数据更新完成";exit;
}

// 跳转
function redirect($url,$msg) {
	echo"<script>";
	echo"function redirect() {window.location.replace('$url');}\n";
	echo"setTimeout('redirect();', 500);\n";
	echo"</script>";
	echo"<a href=\"$url\">如果您的浏览器没有自动跳转，请点击这里</a> $msg";
	exit;
}