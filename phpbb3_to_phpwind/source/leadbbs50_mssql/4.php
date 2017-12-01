<?php
!defined('R_P') && exit('Forbidden!');

//主题和回复
require_once(S_P.'tmp_face.php');
if(!$start)
{
    $DDB->query("TRUNCATE TABLE {$pw_prefix}threads");
    $DDB->query("TRUNCATE TABLE {$pw_prefix}tmsgs");
    $DDB->query("TRUNCATE TABLE {$pw_prefix}posts");
}
//exit;
$threads_sql = $tmsgs_sql = $posts_sql = array();
$query = $SDB->query("SELECT ID,ParentID,BoardID,RootID,Title,Content,ndatetime,UserName,UserID,IPAddress,NotReplay,UserName,ChildNum,Hits,GoodFlag,LastUser,TopicType,GoodAssort,LastTime FROM {$source_prefix}Announce WHERE ID >= $start AND ID < $end AND ParentID!=0");
$ifupload = '';
while($bbs = $SDB->fetch_array($query))
{
    if($bbs['ID']>=4253561){
        echo '4253561';exit;
    }
    if($bbs['ID']>=3950000){
        echo '3950000';exit;
    }
    if($bbs['ParentID']==0)continue;
    $aid = '';
    $Content = $bbs['Content'];
    $load = preg_match('~\[upload=(\d+?),\d+?].+?\[/upload\]~i',$Content);
    //if($load)
    if(0)
    {
        //取得附件信息
        $query4 = $SDB->query("SELECT * FROM {$source_prefix}Upload WHERE announceid = ".$bbs['ID']);
        $attdata = array();
        while($a = $SDB->fetch_array($query4))
        {
            switch ($a['FileType'])
            {
                case '0':
                    $filetype = 'img';
                    $ifupload = 1;
                    break;
                case '3':
                    $filetype = 'txt';
                    $ifupload = 2;
                    break;
                default:
                    $filetype = 'zip';
                    $ifupload = 3;
                    break;
            }
            $attdata[$a['ID']] = array('aid'=>$a['ID'],'name'=>addslashes($a['FileName']),'type'=>$filetype,'attachurl'=>addslashes($a['PhotoDir']),'needrvrc'=>0,'size'=>round($a['FileName']/1024),'hits'=>$a['hits'],'desc'=>'','ifthumb'=>0);
            //$a->MoveNext();
        }
        $aid = addslashes(serialize($attdata));
    }
    //$special = 0;
    $bbs_ndatetime = dt2ut(RestoreTime($bbs['ndatetime']));
    $Title = str_ireplace($_dzface,$_pwface,$Title);
    $bbs_Content = lead_ubb(str_ireplace($_dzface,$_pwface,$Content));
    $ifconvert = (convert($bbs_Content) == $bbs_Content) ? 1 : 2;
    //echo $bbs['LastTime'];exit;
    $t_LastTime = dt2ut(RestoreTime($bbs['LastTime']));
    $t_ndatetime = dt2ut(RestoreTime($bbs['ndatetime']));
    //exit;
    if ($bbs['TopicType']){
        $votearray = array();
        $votersnum = 0;
        //echo "SELECT VoteName,VoteNum FROM {$source_prefix}VoteItem WHERE AnnounceID = ".$bbs['ID'];exit;
        $query2 = $SDB->query("SELECT VoteName,VoteNum FROM {$source_prefix}VoteItem WHERE AnnounceID = ".$bbs['ID']);
        while($v = $SDB->fetch_array($query2))
        {
            //$voteoptions = array();
            //$voteoptions[][0] = $v['VoteName'];
            //$voteoptions[][1] = $v['VoteNum'];

            $query3 = $SDB->query("SELECT vu.UserName,vu.VoteItem,u.ID FROM {$source_prefix}VoteUser vu left join	{$source_prefix}User u on vu.UserName=u.UserName WHERE vu.AnnounceID = ".$bbs['ID']);
            while($v2 = $SDB->fetch_array($query3))
            {
                //$voteoptions[][2][] = $v['VoteName'];
                $DDB->update("REPLACE INTO {$pw_prefix}voter (tid,uid,username,vote,time) VALUES ('{$bbs['ID']}','{$v2['ID']}','{$v2['UserName']}','{$v2[VoteItem]}','0')");
                //$v2->MoveNext();
                $votersnum++;
            }
            $votearray[] = array($v['VoteName'],$v['VoteNum']);
            //$rt['votes'] = $votes ? $votes : 0;
            //$v->MoveNext();
            //print_r($votearray);
        }
        $votearray	= addslashes(serialize($votearray));
        $check_tid = $DDB->get_one("SELECT tid FROM {$pw_prefix}polls WHERE tid=".$bbs['ID']);
        if($check_tid){
            $DDB->update("DELETE FROM {$pw_prefix}polls WHERE tid=".$bbs['ID']);
        }
        //print_r($votearray);exit;
        //echo "INSERT INTO {$pw_prefix}polls (tid,voteopts,modifiable,previewable,timelimit) VALUES (".$bbs['ID'].",'{$votearray}',1,1,0)";exit;
        $DDB->update("REPLACE INTO {$pw_prefix}polls (tid,voteopts,modifiable,previewable,timelimit,voters) VALUES (".$bbs['ID'].",'{$votearray}',1,1,0,".$votersnum.")");
        //
        $special = '1';
    }else{
        $special = '0';
    }

    if ($bbs['ParentID'])
    {
        $posts_sql[] = "(".$bbs['ID'].",".$bbs['BoardID'].",".$bbs['ParentID'].",'','".addslashes($bbs['UserName'])."','".$bbs['UserID']."','".$bbs_ndatetime."','".addslashes($bbs['Title'])."','".addslashes($bbs['IPAddress'])."',1,'".$ifconvert."','".addslashes($bbs_Content)."',1)";
    }
    else
    {
        $iflock = $bbs['NotReplay'];
        //$threads_sql[] = "(".$bbs['ID'].",".$bbs['BoardID'].",'".$bbs['FaceIcon']."','".addslashes($bbs['UserName'])."',".$bbs['UserID'].",'".addslashes($bbs['Title'])."','".$bbs['GoodAssort']."',1,'".$t_ndatetime."','".$t_LastTime."','".addslashes($bbs['LastUser'])."',".$bbs['Hits'].",".$bbs['ChildNum'].",".$bbs['GoodFlag'].",".$special.")";

        //$tmsgs_sql[] = "(".$bbs['ID'].",'".$aid."','".addslashes($bbs['IPAddress'])."',1,'".$ifconvert."','".addslashes($bbs_Content)."')";
        //$DDB->update("UPDATE {$pw_prefix}threads Set locked = ".$iflock.",ifupload='".$ifupload."' Where tid=".$bbs['ID']);
    }
    $s_c++;
    //$bbs->MoveNext();
}
//$threads_sql && $DDB->update("REPLACE INTO {$pw_prefix}threads (tid,fid,icon,author,authorid,subject,type,ifcheck,postdate,lastpost,lastposter,hits,replies,digest,special) VALUES ".implode(",",$threads_sql));

//$tmsgs_sql && $DDB->update("REPLACE INTO {$pw_prefix}tmsgs (tid,aid,userip,ifsign,ifconvert,content) VALUES ".implode(",",$tmsgs_sql));

$posts_sql && $DDB->update("REPLACE INTO {$pw_prefix}posts (pid,fid,tid,aid,author,authorid,postdate,subject,userip,ifsign,ifconvert,content,ifcheck) VALUES ".implode(",",$posts_sql));

$row = $SDB->get_one("SELECT COUNT(*) AS num FROM {$source_prefix}Announce WHERE ID >= $end AND ParentID!=0");
echo $row['num'];
if ($row['num'])
{
    refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
}
else
{
    echo '主体结束';exit;
    report_log();
    newURL($step);
}