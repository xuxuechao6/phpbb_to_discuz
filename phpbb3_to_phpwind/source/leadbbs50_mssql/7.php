<?php
!defined('R_P') && exit('Forbidden!');

//¶ÌÐÅ
$message_sql = $relations_sql = $replies_sql = array();
//¶ÌÐÅ
if(!$start)
{
    $DDB->update("TRUNCATE TABLE {$pw_prefix}ms_messages");
    $DDB->update("TRUNCATE TABLE {$pw_prefix}ms_relations");
    $DDB->update("TRUNCATE TABLE {$pw_prefix}ms_replies");
}
$touid = $fromuid = '';
$query = $SDB->query("SELECT * FROM {$source_prefix}InfoBox WHERE ID >= $start AND ID < $end");
while($m = $SDB->fetch_array($query))
{
    ADD_S($m);
    if($m['ToUser'] == '')
    {
        $f_c++;
        errors_log($m['ID']."\t".$m['title']);
        $m->MoveNext();
        continue;
    }

    if($m['FromUser']=='[LeadBBS]')
    {
        $touid = '0';
        $fromuid = '0';
        $type = 'public';
    }

    else
    {
        $tou = $DDB->get_one("Select uid From {$pw_prefix}members Where username='".$m['ToUser']."'");
        $from = $DDB->get_one("Select uid From {$pw_prefix}members Where username='".$m['FromUser']."'");
        $touid = $tou['uid'] ? $tou['uid'] : '0';
        $fromuid = $from['uid'] ? $from['uid'] : '0';
        $type    = 'rebox';
    }
    $postdatetime = dt2ut(RestoreTime($m['ExpiresDate']));

if(!$postdatetime)$postdatetime=0;

        $message_sql[] = "('".$m['ID']."',".$fromuid.",'".$m['FromUser']."','".$m['title']."','".$m['Content']."','".serialize(array('categoryid'=>1,'typeid'=>100))."',".$postdatetime.",".$postdatetime.",'".serialize(array($m['ToUser']))."')";
        $replies_sql[] = "('".$m['ID']."',".$m['ID'].",'".$fromuid."','".$m['FromUser']."','".$m['title']."','".$m['Content']."','1',".$postdatetime.",".$postdatetime.")";

        $userIds = "";
        $userIds = array($touid,$fromuid);
        foreach($userIds as $otherId){
            $relations_sql[] = "(".$otherId.",'".$m['ID']."','1','100','0',".(($otherId == $fromuid) ? 1 : 0).",".$postdatetime.",".$postdatetime.")";
        }

/*
    $DDB->update("REPLACE INTO {$pw_prefix}msg (mid,touid,fromuid,username,type,ifnew,mdate) VALUES (".$m['ID'].",".$touid.",".$fromuid.",'".addslashes($m['FromUser'])."','".$type."','0','".$postdatetime."')");
    $DDB->update("REPLACE INTO {$pw_prefix}msgc (mid,title,content) VALUES (".$m['ID'].",'".addslashes($m['title'])."','".addslashes($m['Content'])."')");
    if (($m['FromUser'] != $m['ToUser']) && ($type == 'rebox'))
    {
        $DDB->update("REPLACE INTO {$pw_prefix}msglog (mid,uid,withuid,mdate,mtype) VALUES (".$m['ID'].",".$fromuid.",".$touid.",'".$postdatetime."','send')");
        $DDB->update("REPLACE INTO {$pw_prefix}msglog (mid,uid,withuid,mdate,mtype) VALUES (".$m['ID'].",".$touid.",".$fromuid.",'".$postdatetime."','receive')");
    }*/
    $s_c++;
    //$m->MoveNext();
}

if($message_sql)
{
    $DDB->update("REPLACE INTO {$pw_prefix}ms_messages (mid,create_uid,create_username,title,content,expand,created_time,modified_time,extra) VALUES ".implode(",",$message_sql));
}
if($relations_sql)
{
    $DDB->update("INSERT INTO {$pw_prefix}ms_relations (uid,mid,categoryid,typeid,status,isown,created_time,modified_time) VALUES ".implode(",",$relations_sql));
}
if($replies_sql)
{
    $DDB->update("REPLACE INTO {$pw_prefix}ms_replies(id,parentid,create_uid,create_username,title,content,status,created_time,modified_time) VALUES ".implode(",",$replies_sql));
}

$row = $SDB->get_one("SELECT COUNT(*) as num FROM {$source_prefix}InfoBox WHERE ID >= $end");
echo $row['num'];
if ($row['num'])
{
    refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c.'&medal='.$medal);
}
else
{
    report_log();
    exit;
    newURL($step);
}