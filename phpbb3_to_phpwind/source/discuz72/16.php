<?php
!defined('R_P') && exit('Forbidden!');

//好友
require_once S_P.'tmp_uc.php';
$charset_change = 1;
$UCDB = new mysql($uc_db_host, $uc_db_user, $uc_db_password, $uc_db_name, '');
if(empty($start)){
    $DDB->query("TRUNCATE TABLE {$pw_prefix}friends");
}

$goon = 0;
$query = $UCDB->query("SELECT uid,friendid,comment FROM {$uc_db_prefix}friends LIMIT $start, $percount");

//好友好像没有添加时间,也没有验证状态
while($f = $UCDB->fetch_array($query)){
    $DDB->update("REPLACE INTO {$pw_prefix}friends (uid,friendid,descrip,iffeed) VALUES (".$f['uid'].",".$f['friendid'].",'".addslashes($f['comment'])."',1)");
    $DDB->update("REPLACE INTO {$pw_prefix}friends (friendid,uid,descrip,iffeed) VALUES (".$f['uid'].",".$f['friendid'].",'".addslashes($f['comment'])."',1)");
    $goon ++;
    $s_c ++;
}
if ($goon == $percount){
    refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
}else{
    $maxid = $UCDB->get_value("SELECT max(uid) FROM {$uc_db_prefix}friends");
    report_log();
    newURL($step);
}
