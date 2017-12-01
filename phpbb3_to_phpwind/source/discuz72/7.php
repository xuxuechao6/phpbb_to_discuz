<?php
/**
*
*  Copyright (c) 2003-06  PHPWind.net. All rights reserved.
*  Support : http://www.phpwind.net
*  This software is the proprietary information of PHPWind.com.
*
*/

!defined('R_P') && exit('Forbidden!');

//½»Ò×  //TODO
$tid = $SDB->get_value("SELECT tid FROM {$source_prefix}trades LIMIT $start, 1");
if (empty($tid)){
    report_log();
    newURL($step);
}

$query = $SDB->query("SELECT t.*, p.message
        			  FROM {$source_prefix}trades t
			          INNER JOIN {$source_prefix}posts p USING (tid)
			          WHERE t.tid >= ".$tid['tid']." AND p.first = 1 
			          LIMIT $percount");
while($a = $SDB->fetch_array($query)){
    $aidd = $SDB->get_one("SELECT filetype,filesize,attachment 
    					   FROM {$source_prefix}attachments 
    					   WHERE aid='".$a['aid']."'");
    list($ifupload,$aidd['filetype']) = getattfiletype($aidd['attachment']);
    $aid = serialize(array('type'=>$aidd['filetype'],'attachurl'=>$aidd['attachment'],'size'=>ceil($aidd['filesize']/1024)));
    $sql = array(
        'tid'		=>	$a['tid'],
        'uid'		=>	$a['sellerid'],
        'name'		=>	$a['subject'],
        'num'		=>	$a['amount'],
        'salenum'	=>	$a['transport'],
        'price'		=>	$a['price'],
        'costprice'	=>	$a['costprice'],
        'locus'		=>	$a['locus'],
        'mailfee'	=>	$a['ordinaryfee'],
        'expressfee'=>	$a['expressfee'],
        'emsfee'	=>	$a['emsfee'],
        'deadline'	=>	$a['deadline']
    );
    !empty($sql) && $DDB->update("REPLACE INTO {$pw_prefix}trade SET ".pwSqlSingle($sql));
    $DDB->update("UPDATE {$pw_prefix}tmsgs SET aid = '$aid' WHERE tid = ".$a['tid']);
    $s_c++;
}
$SDB->free_result($query);
refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);