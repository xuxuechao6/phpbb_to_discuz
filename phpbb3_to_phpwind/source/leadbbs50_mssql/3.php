<?php
!defined('R_P') && exit('Forbidden!');

//°æ¿éÐÅÏ¢
$DDB->query("TRUNCATE TABLE {$pw_prefix}forums");
$DDB->query("TRUNCATE TABLE {$pw_prefix}forumdata");

$insertforum = $insertforumdb = '';
$forumdb = array();
$query = $SDB->query("SELECT BoardID,BoardAssort,BoardName,BoardIntro,TopicNum,AnnounceNum,HiddenFlag,ForumPass,MasterList FROM {$source_prefix}Boards ORDER BY BoardID");
while($rt = $SDB->fetch_array($query))
{
    $forumdb[$rt['BoardID']] = array('BoardID'=>$rt['BoardID'],'BoardAssort'=>$rt['BoardAssort'],'BoardName'=>$rt['BoardName'],'BoardIntro'=>$rt['BoardIntro'],'TopicNum'=>$rt['TopicNum'],'AnnounceNum'=>$rt['AnnounceNum'],'HiddenFlag'=>$rt['HiddenFlag'],'ForumPass'=>$rt['ForumPass'],'MasterList'=>$rt['MasterList']);
    //$rt->MoveNext();
}
foreach ($forumdb as $k => $v)
{
    $newmaster = '';
    $tmp = explode(',', trim($v['MasterList']));
    foreach ($tmp as $vv)
    {
        $vv = addslashes(trim($vv));
        if ($vv)
        {
            $uid = $DDB->get_one("SELECT uid, groupid FROM {$pw_prefix}members WHERE username = '".$vv."'");
            if ($uid)
            {
                $newmaster .= $vv.',';
                if ($uid['groupid'] == -1)
                {
                    $DDB->update("UPDATE {$pw_prefix}members SET groupid = 5 WHERE uid = '".$uid['uid']."' LIMIT 1");
                    $DDB->update("INSERT INTO {$pw_prefix}administrators (uid,username,groupid) VALUES (".$uid['uid'].",'".$vv."',5)");
                }
            }
        }
    }
    $newmaster && $newmaster = ','.$newmaster;
    $allowvisit ='';
    $insertforum .= "(".$v['BoardID'].",".$v['BoardAssort'].",'".addslashes($v['BoardName'])."','".addslashes($v['BoardIntro'])."','".addslashes($newmaster)."','".$allowvisit."'),";
    $insertforumdb .= "(".$v['BoardID'].",'".$v['TopicNum']."','".$v['AnnounceNum']."'),";
    $s_c++;
}
$insertforum && $DDB->update("INSERT INTO {$pw_prefix}forums (fid,fup,name,descrip,forumadmin,allowvisit) VALUES ".substr($insertforum, 0, -1));
$insertforumdb && $DDB->update("INSERT INTO {$pw_prefix}forumdata (fid,topic,article) VALUES ".substr($insertforumdb, 0, -1));

$query = $SDB->query("SELECT AssortID,AssortName,AssortMaster FROM {$source_prefix}Assort");
while($g = $SDB->fetch_array($query))
{
    $newmaster = '';
    $tmp = explode(',', trim($g['AssortMaster']));
    foreach ($tmp as $v)
    {
        $v = addslashes(trim($v));
        if ($v)
        {
            $uid = $DDB->get_one("SELECT uid, groupid FROM {$pw_prefix}members WHERE username = '".$v."'");
            if ($uid)
            {
                $newmaster .= $v.',';
                if ($uid['groupid'] == -1)
                {
                    $DDB->update("UPDATE {$pw_prefix}members SET groupid = 5 WHERE uid = '".$uid['uid']."' LIMIT 1");
                    $DDB->update("INSERT INTO {$pw_prefix}administrators (uid,username,groupid) VALUES (".$uid['uid'].",'".$v."',5)");
                }
            }
        }
    }
    $newmaster && $newmaster = addslashes(','.$newmaster);
    $DDB->update("INSERT INTO {$pw_prefix}forums (childid,type,name,forumadmin) VALUES (1,'category','".addslashes($g['AssortName'])."','".$newmaster."')");
    $fid = $DDB->insert_id();
    $DDB->update("INSERT INTO {$pw_prefix}forumdata (fid) VALUES (".$fid.")");
    $DDB->update("UPDATE {$pw_prefix}forums SET fup = $fid, fupadmin = '$newmaster' WHERE fup = ".$g['AssortID']);
    //$g->MoveNext();
}
report_log();
newURL($step);
