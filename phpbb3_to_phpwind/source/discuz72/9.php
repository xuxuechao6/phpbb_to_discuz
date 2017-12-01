<?php
/**
*
*  Copyright (c) 2003-06  PHPWind.net. All rights reserved.
*  Support : http://www.phpwind.net
*  This software is the proprietary information of PHPWind.com.
*
*/

!defined('R_P') && exit('Forbidden!');

//ͶƱ
if(empty($start)){
    $DDB->query("TRUNCATE TABLE {$pw_prefix}voter");
    $DDB->query("TRUNCATE TABLE {$pw_prefix}polls");
}
$ipoll = $voterarrr = array();
$query = $SDB->query("SELECT p.*, t.dateline 
					  FROM {$source_prefix}polls p 
					  LEFT JOIN {$source_prefix}threads t USING (tid) 
					  WHERE p.tid > $start 
					  ORDER BY p.tid 
					  LIMIT $percount");
$ipoll = '';
unset($lastid);
while($v = $SDB->fetch_array($query)){
    $lastid = $v['tid'];
    $votearray = array();
    $kk=0;
    $t_votes2 = 0;
    $vop = $SDB->query("SELECT * FROM {$source_prefix}polloptions 
    					WHERE tid = ".$v['tid']." 
    					ORDER BY polloptionid");
    while($rt = $SDB->fetch_array($vop)){
        $voteuser = array();
        if ($rt['voterids']){
            $tmp_uids = explode("\t",$rt['voterids']);
            $uids = '';
            foreach ($tmp_uids as $uv){
                if ($uv && strpos($uv,'.')!==FALSE) continue;
                $uids .= ($uids ? ',' : '').(int)$uv;
            }
            if ($uids){
                $t_votes = 0;
                $q2 = $DDB->query("SELECT uid,username FROM {$pw_prefix}members WHERE uid IN (".$uids.")");
                ADD_S($q2);
                while($r2 = $SDB->fetch_array($q2)){
                    ADD_S($r2);
                    //$DDB->update("REPLACE INTO {$pw_prefix}voter (tid,uid,username,vote,time) VALUES ('{$v['tid']}','{$r2['uid']}','{$r2['username']}','$kk','0')");
                    $voterarrr[] = "('{$v['tid']}','{$r2['uid']}','{$r2['username']}','$kk','0')";
                    $t_votes++;
                    $t_votes2++;
                }
            }
        }
        $kk++;
        //$rt['votes'] = $votes;
        $votearray[] = array($rt['polloption'],$t_votes);
    }
    $votearray	= addslashes(serialize($votearray));
    $timelimit	= $v['expiration'] ? (($v['expiration'] - $v['dateline']) / 86400) : 0;
    //$ipoll = "(".$v['tid'].",'{$votearray}',1,".(1^$rt['visible']).",{$timelimit},{$v['multiple']},{$v['maxchoices']},$votes)";
    //$DDB->update("REPLACE INTO {$pw_prefix}polls (tid,voteopts,modifiable,previewable,timelimit,multiple,mostvotes,voters) VALUES ".$ipoll);
    $ipoll[] = "(".$v['tid'].",'{$votearray}',1,".(1^$rt['visible']).",{$timelimit},{$v['multiple']},{$v['maxchoices']},{$t_votes2})";
    $s_c++;
}
$SDB->free_result($query);

if($voterarrr){
    $DDB->update("REPLACE INTO {$pw_prefix}voter (tid,uid,username,vote,time) VALUES ".implode(",",$voterarrr));
}
if($ipoll){
    $DDB->update("REPLACE INTO {$pw_prefix}polls (tid,voteopts,modifiable,previewable,timelimit,multiple,mostvotes,voters) VALUES ".implode(",",$ipoll));
}
$maxid = $SDB->get_value("SELECT MAX(tid) FROM {$source_prefix}polls");
empty($lastid) && $lastid = $end;

if($lastid < $maxid){
    refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
}else{
    report_log();
    newURL($step);
}