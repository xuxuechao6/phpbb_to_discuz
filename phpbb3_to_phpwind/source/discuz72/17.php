<?php
!defined('R_P') && exit('Forbidden!');

//收藏
if(!$start)
{
    $DDB->query("TRUNCATE TABLE {$pw_prefix}favors");
}

$goon = 0;
$query = $SDB->query("SELECT uid,tid FROM {$source_prefix}favorites LIMIT $start, $percount");

while($f = $SDB->fetch_array($query))
{
    $DDB->pw_update("SELECT uid FROM {$pw_prefix}favors WHERE uid = ".$f['uid'],
                    "UPDATE {$pw_prefix}favors SET tids = CONCAT_WS(',',tids,'".$f['tid']."') WHERE uid = ".$f['uid'],
                    "REPLACE INTO {$pw_prefix}favors (uid,tids) VALUES (".$f['uid'].", '".$f['tid']."')");
    $goon ++;
    $s_c ++;
}
if ($goon == $percount)
{
    refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
}
else
{
    $maxid = $SDB->get_value("SELECT max(uid) FROM {$source_prefix}favorites");
    report_log();
    newURL($step);
}
