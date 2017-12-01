<?php
/**
*
*  Copyright (c) 2003-06  PHPWind.net. All rights reserved.
*  Support : http://www.phpwind.net
*  This software is the proprietary information of PHPWind.com.
*
*/

!defined('R_P') && exit('Forbidden!');
//ÐüÉÍ  //TODO
if(empty($start)){
    $DDB->query("TRUNCATE TABLE {$pw_prefix}reward");
}

$query = $SDB->query("SELECT tid,authorid,answererid,dateline 
					  FROM {$source_prefix}rewardlog 
					  WHERE authorid <> 0 AND tid >= $start
					  LIMIT $percount");
unset($lastid);
while($r = $SDB->fetch_array($query)){
    $lastid = $r['tid'];
    $rewardinfo = $SDB->get_one("SELECT t.price,p.author 
    							 FROM {$source_prefix}posts p 
    							 LEFT JOIN {$source_prefix}threads t USING (tid) 
    							 WHERE p.tid = ".$r['tid']." AND p.first = 0
    							 LIMIT 1");
    if (empty($rewardinfo)){
        $f_c++;
        errors_log($r['tid']);
        continue;
    }
    if ($r['answererid']){
        $reward_author = addslashes($rewardinfo['author']);
        $reward_pid = $r['answererid'];
        !empty($r['tid']) && $DDB->update("UPDATE {$pw_prefix}threads SET state = 1 WHERE tid = ".$r['tid']);
    }else{
    	$reward_author = $sql = '';
        $reward_pid = 0;
    }
    $DDB->update("REPLACE INTO {$pw_prefix}reward (tid,cbtype,catype,cbval,caval,timelimit,author,pid) 
    			  VALUES (".$r['tid'].",'money','money','".abs($rewardinfo['price'])."',0,".($r['dateline']+864000).",'".$reward_author."',$reward_pid)");
    $s_c ++;
}
$SDB->free_result($query);

$maxid = $SDB->get_value("SELECT MAX(tid) FROM {$source_prefix}rewardlog ");
empty($lastid) && $lastid = $end;

if ($lastid < $maxid){
    refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
}else{
    report_log();
    newURL($step);
}