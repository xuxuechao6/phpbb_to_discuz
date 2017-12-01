<?php
/**
*
*  Copyright (c) 2003-06  PHPWind.net. All rights reserved.
*  Support : http://www.phpwind.net
*  This software is the proprietary information of PHPWind.com.
*
*/

!defined('R_P') && exit('Forbidden!');

//活动参加者
if(!$start){
    $DDB->update("TRUNCATE TABLE {$pw_prefix}activitymembers");
}
$goon=0;
$query = $SDB->query("SELECT * FROM {$source_prefix}activityapplies LIMIT $start,$percount");
while($act = $SDB->fetch_array($query))
{
    ADD_S($act);
    $goon++;
    $actarr[] = "(".$act['applyid'].",".$act['tid'].",".$act['uid'].",1,'".$act['username']."','a:1:{i:0;i:1;}',1,'".$act['contact']."','".$act['message']."',".$act['dateline'].")";
    $s_c++;
    //(2-$act['verified'])
}
if($actarr){
    $DDB->update("INSERT INTO {$pw_prefix}activitymembers (actuid,tid,uid,actmid,username,signupdetail,signupnum,mobile,message,signuptime) VALUES ".implode(",",$actarr));
}
if ($goon == $percount){
    refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
}else{
    report_log();
    newURL($step);
}