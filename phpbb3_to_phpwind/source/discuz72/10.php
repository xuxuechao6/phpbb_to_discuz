<?php
/**
*
*  Copyright (c) 2003-06  PHPWind.net. All rights reserved.
*  Support : http://www.phpwind.net
*  This software is the proprietary information of PHPWind.com.
*
*/

!defined('R_P') && exit('Forbidden!');

//活动
if(empty($start)){
    $DDB->update("TRUNCATE TABLE {$pw_prefix}activitydefaultvalue");
}

$query = $SDB->query("SELECT a.*,t.fid,t.author,t.dateline 
                      FROM {$source_prefix}activities a 
                      LEFT JOIN {$source_prefix}threads t USING(tid) 
                      WHERE a.tid >= $start 
                      LIMIT $percount");
unset($lastid);
while($act = $SDB->fetch_array($query)){
    $lastid = $act['tid'];
    $fid =$act['fid'];

    //$acttid = $SDB->get_one("SELECT fid FROM $thread_table WHERE tid=".$act['tid']);
    //如果没有结束时间就自动加上10天
    if($act['starttimeto'] == 0){
        //$act['starttimeto']=$act['starttimefrom']+864000;
    }
    $price[] = array(
        'condition' => '所有人',
        'money'     => $act['cost'],
    );
    //a:2:{s:9:"condition";s:6:"所有人";s:5:"money";s:4:"3000";}
    //a:1:{i:0;a:2:{s:9:"condition";s:6:"所有人";s:5:"money";s:4:"6555";}}
    $pwSQL = array(
        'tid'		        => $act['tid'],
        'fid'		        => $act['fid'],
        'actmid'		    => 1,
        'iscertified'       => 1,
        'signupstarttime'   => $act['dateline'],
        'signupendtime'	    => $act['expiration'],
        'starttime'		    => $act['starttimefrom'],
        'endtime'	        => $act['starttimefrom']+60*60*24*30,
        'location'		    => addslashes($act['place']),
        'contact'           => $act['author'],
        'telephone'         => $act['contact'],
        'maxparticipant'    => $act['number'],
        'genderlimit'       => $act['gender'],
        'userlimit'         => 1,
        'paymethod'         => 2,
        'fees'		        => serialize($price),
    );
    $tidarr[] = $act['tid'];
    !empty($pwSQL) && $DDB->update("REPLACE INTO {$pw_prefix}activitydefaultvalue SET ".pwSqlSingle($pwSQL));
    $s_c++;
}
$SDB->free_result($query);

$maxid = $SDB->get_value("SELECT MAX(tid) FROM {$source_prefix}activities");
empty($lastid) && $lastid = $end;

if($lastid < $maxid){
    refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
}else{
    report_log();
    newURL($step);
}