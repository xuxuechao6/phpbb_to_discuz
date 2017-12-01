<?php
!defined('R_P') && exit('Forbidden!');

//短信
require_once S_P.'tmp_uc.php';
$charset_change = 1;
$UCDB = new mysql($uc_db_host, $uc_db_user, $uc_db_password, $uc_db_name, '');

$message_sql = $relations_sql = $replies_sql = array();

if(!$start){
    $DDB->update("TRUNCATE TABLE {$pw_prefix}ms_messages");
    $DDB->update("TRUNCATE TABLE {$pw_prefix}ms_relations");
    $DDB->update("TRUNCATE TABLE {$pw_prefix}ms_replies");
}
$query = $UCDB->query("SELECT * FROM {$uc_db_prefix}pms 
                       WHERE pmid >= $start 
                       ORDER BY pmid ASC
                       LIMIT $percount");
unset($lastid);
while($m = $UCDB->fetch_array($query)){
    $lastid = $m['pmid'];
    ADD_S($m);
    if($m['related']==0){
        continue;
    }
    switch ($m['folder']){
        case 'inbox':
            $type = 'rebox';
            $m_tmp = $DDB->get_one("SELECT username FROM {$pw_prefix}members WHERE uid = ".$m['msgtoid']);
            $m['msgto'] = addslashes($m_tmp['username']);
            break;
        case 'outbox':
            $type = 'sebox';
            $m_tmp = $DDB->get_one("SELECT username FROM {$pw_prefix}members WHERE uid = ".$m['msgtoid']);
            $m['msgfrom'] = addslashes($m_tmp['username']);
            break;
    }

    if ($m['delstatus'] != 2){
        $message_sql[] = "('".$m['pmid']."',".$m['msgfromid'].",'".$m['msgfrom']."','".$m['subject']."','".$m['message']."','".serialize(array('categoryid'=>1,'typeid'=>100))."',".$m['dateline'].",".$m['dateline'].",'".serialize(array($m['msgto']))."')";
        $replies_sql[] = "('".$m['pmid']."',".$m['pmid'].",'".$m['msgfromid']."','".$m['msgfrom']."','".$m['subject']."','".$m['message']."','1',".$m['dateline'].",".$m['dateline'].")";

        $userIds = "";
        $userIds = array($m['msgtoid'],$m['msgfromid']);
        foreach($userIds as $otherId){
            $relations_sql[] = "(".$otherId.",'".$m['pmid']."','1','100','0',".(($otherId == $m['msgfromid']) ? 1 : 0).",".$m['dateline'].",".$m['dateline'].")";
        }
        $s_c++;
    }
}
$UCDB->free_result($query);

!empty($message_sql) && $DDB->update("REPLACE INTO {$pw_prefix}ms_messages (mid,create_uid,create_username,title,content,expand,created_time,modified_time,extra) VALUES ".implode(",",$message_sql));

!empty($relations_sql) && $DDB->update("INSERT INTO {$pw_prefix}ms_relations (uid,mid,categoryid,typeid,status,isown,created_time,modified_time) VALUES ".implode(",",$relations_sql));

!empty($replies_sql) && $DDB->update("REPLACE INTO {$pw_prefix}ms_replies(id,parentid,create_uid,create_username,title,content,status,created_time,modified_time) VALUES ".implode(",",$replies_sql));

$maxid = $UCDB->get_value("SELECT MAX(pmid) FROM {$uc_db_prefix}pms");
empty($lastid) && $lastid = $end;

if($lastid < $maxid){
    refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
}else{
    report_log();
    newURL($step);
}