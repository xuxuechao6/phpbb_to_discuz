<?php
!defined('R_P') && exit('Forbidden!');

//附件信息
if(!$start)
{
    //$DDB->query("TRUNCATE TABLE {$pw_prefix}attachs");
}
$query = $SDB->query("SELECT ID,UserID,PhotoDir,NdateTime,FileType,FileName,FileSize,announceid,boardid,Info,VisitIP,hits FROM {$source_prefix}Upload WHERE ID >= $start AND ID < $end");
while($a = $SDB->fetch_array($query))
{
    if ($a['announceid'])
    {
        $FileType = $a['FileType'];
        switch ($FileType)
        {
            case 0:
                $FileType = 'img';
                break;
            case 2:
                $FileType = 'txt';
                break;
            default:
                $FileType = 'zip';
                break;
        }
        $thread = $SDB->get_one("SELECT ParentID FROM {$source_prefix}Announce WHERE ID = ".$a['announceid']);

        if ($thread['ParentID'])
        {
            $f_c++;
            //errors_log($a['ID']."\t".$a['FileName']);
            //$a->MoveNext();
            //continue;
        }else{
            $thread['ParentID'] = 0;
        }
        //if($a['ID']==180406){
//echo $thread['ParentID'].'--'.$a['announceid'];exit;
        //}
        $DDB->update("REPLACE INTO {$pw_prefix}attachs (aid,fid,uid,tid,pid,name,type,size,attachurl,hits,uploadtime,descrip) VALUES ('".$a['ID']."','".$a['boardid']."','".$a['UserID']."','".($thread['ParentID'] ? $thread['ParentID'] : $a['announceid'])."','".($thread['ParentID'] ? $a['announceid'] : 0)."','".addslashes($a['FileName'])."','".$FileType."','".(ceil($a['FileSize'] / 1024))."','".addslashes($a['PhotoDir'])."','".$a['hits']."','".dt2ut(RestoreTime($a['NdateTime']))."','".addslashes($a['Info'])."')");
        if ($thread['ParentID'])
        {
            $attable = 'posts';
            $add = ' pid = '.$a['announceid'];
        }
        else
        {
            $attable = 'tmsgs';
            $add = ' tid = '.$a['announceid'];
        }
        $attdata = array();
        $atta = $DDB->get_one("SELECT aid FROM {$pw_prefix}{$attable} WHERE".$add);
        if (!$atta) {continue;}
        $atta['aid'] && $attdata = unserialize($atta['aid']);
        //$attdata[$a['ID']] = array('aid'=>$a['ID'],'name'=>addslashes($a['FileName']),'type'=>$FileType,'attachurl'=>addslashes($a['PhotoDir']),'needrvrc'=>0,'size'=>round($a['FileSize']/1024),'hits'=>$a['hits'],'desc'=>addslashes($a['Info']),'ifthumb'=>0);
        $DDB->query("UPDATE {$pw_prefix}{$attable} SET aid = '1' WHERE".$add);
    }
    //$a->MoveNext();
}
//exit;
$row = $SDB->get_one("SELECT COUNT(*) AS num FROM {$source_prefix}Upload WHERE ID >= $end");
echo $row['num'];
if ($row['num'])
{
    refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
}
else
{
    echo '附件结束';exit;
    report_log();
    newURL($step);
}