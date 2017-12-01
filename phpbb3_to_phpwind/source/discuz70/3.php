<?php
!defined('R_P') && exit('Forbidden!');

//я╚уб
if (!$start)
{
    $DDB->query("TRUNCATE TABLE {$pw_prefix}medalslogs");
}
$medallog = array();
$query = $SDB->query("SELECT * FROM {$source_prefix}medallog WHERE id > $start ORDER BY id LIMIT $percount");
while ($l = $SDB->fetch_array($query))
{
    $lastid = $l['id'];
    $awardee = $SDB->get_value("SELECT username FROM {$source_prefix}members WHERE uid=".$l['uid']);
    $timelimit = floor(($l['expiration'] - $l['dateline']) / 60*60*24*30);
    //$timelimit = $l['expiration'];
    $l['status'] = 1;
    $medallog[] = "(".$l['id'].",'".addslashes($awardee)."','','".$l['dateline']."','$timelimit','','".$l['medalid']."','".$l['status']."','')";
    $s_c++;
}
if($medallog){
    $medallogarr = implode(",",$medallog);
    $DDB->update("INSERT INTO {$pw_prefix}medalslogs (id,awardee,awarder,awardtime,timelimit,state,level,action,why) VALUES $medallogarr");
}
$maxid = $SDB->get_value("SELECT max(id) FROM {$source_prefix}medallog");
if ($lastid < $maxid)
{
    refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
}
else
{
    report_log();
    newURL($step, '&medal=yes');
    exit();
}