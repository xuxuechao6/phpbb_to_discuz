<?php
/**
 * ���±�ǩ���ݵ�pw_tagdata,�������ܴ򿪱�ǩ��ص����ӿ�
 * ���ű��ŵ�pw��̳��Ŀ¼��ִ�м���
 */
require_once 'global.php';
$basename = $db_bbsurl.'/'.end(explode('/',$_SERVER['PHP_SELF']));
$percount = 500; // ÿ�δ������Ŀ��ѯ��

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
	redirect($basename."?start=".$end,"�ӵ� $start ��ת���� $end ��");
} else {
	echo "��ǩ���ݸ������";exit;
}

// ��ת
function redirect($url,$msg) {
	echo"<script>";
	echo"function redirect() {window.location.replace('$url');}\n";
	echo"setTimeout('redirect();', 500);\n";
	echo"</script>";
	echo"<a href=\"$url\">������������û���Զ���ת����������</a> $msg";
	exit;
}