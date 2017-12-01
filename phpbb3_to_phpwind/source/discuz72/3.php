<?php
/**
*
*  Copyright (c) 2003-06  PHPWind.net. All rights reserved.
*  Support : http://www.phpwind.net
*  This software is the proprietary information of PHPWind.com.
*
*/

!defined('R_P') && exit('Forbidden!');

//я╚уб
if (empty($start)){
    $DDB->query("TRUNCATE TABLE {$pw_prefix}medalslogs");
}
$medallog = array();

$query = $SDB->query("SELECT * FROM {$source_prefix}medallog
					  WHERE id >= $start 
					  ORDER BY id ASC
					  LIMIT $percount");
while ($l = $SDB->fetch_array($query)){
    $lastid = $l['id'];
    $awardee = $SDB->get_value("SELECT username FROM {$source_prefix}members WHERE uid=".$l['uid']);
    $timelimit = floor(($l['expiration'] - $l['dateline']) / 60*60*24*30);
    $l['status'] = 1;
    $medallog[] = "(".$l['id'].",'".addslashes($awardee)."','','".$l['dateline']."','$timelimit','','".$l['medalid']."','".$l['status']."','')";
    $s_c++;
}
$SDB->free_result($query);

if(!empty($medallog)){
    $medallogarr = implode(",",$medallog);
    !empty($medallogarr) && $DDB->update("INSERT INTO {$pw_prefix}medalslogs (id,awardee,awarder,awardtime,timelimit,state,level,action,why) VALUES $medallogarr");
}
$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}medallog LIMIT 1");
empty($lastid) && $lastid = $end;
if ($lastid < $maxid){
    refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
}else{
    report_log();
    newURL($step, '&medal=yes');
}