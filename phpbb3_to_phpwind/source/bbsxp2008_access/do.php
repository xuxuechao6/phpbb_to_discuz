<?php
/**
*
*  Copyright (c) 2003-06  PHPWind.net. All rights reserved.
*  Support : http://www.phpwind.net
*  This software is the proprietary information of PHPWind.com.
*
*/

!defined('R_P') && exit('Forbidden!');

$db_table = $step_data[$step];

if ($step == '1')
{
	//用户
	if (!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}members");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}memberdata");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}administrators");
		writeover(S_P.'tmp_group.php', "\$_specialgroup = ".pw_var_export(changegroups()).";", true);
	}

	$insertadmin = $_specialgroup = '';
	require_once (S_P.'tmp_group.php');
	require_once (S_P.'tmp_credit.php');

	$m = $SDB->query("SELECT * FROM {$source_prefix}Users WHERE UserID >= $start AND UserID < $end");
	while (!$m->EOF)
	{
		$UserName = addslashes($m->Fields['UserName']->value);
		if (htmlspecialchars($UserName) != $UserName || CK_U($UserName))
		{
			$f_c++;
			errors_log($m->Fields['UserID']->value."\t".$UserName);
			continue;
		}
		$UserID = (int)$m->Fields['UserID']->value;
		switch ($m->Fields['UserRoleID']->value)
		{
			case '1'://管理员
				$groupid = '3';
				$insertadmin .= "(".$UserID.", '".$UserName."', 3),";
				break;
			case '2'://总版主
				$groupid = '4';
				$insertadmin .= "(".$UserID.", '".$UserName."', 4),";
				break;
			case '3':
				$groupid = ($m->Fields['UserAccountStatus']->value == 1) ? '-1' : '7';
				break;
			default :
				$groupid = isset($_specialgroup[$m->Fields['UserRoleID']->value]) ? (int)$_specialgroup[$m->Fields['UserRoleID']->value] : '-1';
				break;
		}
		$UserFaceUrl = $m->Fields['UserFaceUrl']->value;
		if ($m['UserFaceUrl']->value)
		{
			$apre = strtolower(substr($m->Fields['UserFaceUrl']->value,0,6));
			if($apre == 'upfile')
			{
				$UserFaceUrl = substr($UserFaceUrl, strrpos($UserFaceUrl,'/')+1).'|3|||';
			}
			elseif ($apre == 'images')
			{
				$UserFaceUrl = substr($UserFaceUrl, strrpos($UserFaceUrl,'/')+1).'|1|||';
			}
		}

		eval($creditdata);

		$UserRegisterTime = strtotime($m->Fields['UserRegisterTime']->value);

		$Birthday = $m['Birthday']->value ? $m->Fields['Birthday']->value : '0000-00-00';
		$userSign = bbsxp_ubb($m['UserSign']->value);
		$signchange = (convert($userSign) == $userSign) ? '1' : '2';
		$userstatus=($signchange-1)*256+128+1*64+4;//用户位状态设置
		$DDB->update("INSERT INTO {$pw_prefix}members (uid,username,password,email,groupid,icon,gender,regdate,signature,introduce,oicq,icq,msn,yahoo,site,bday,userstatus) VALUES (".$UserID.",'".$UserName."','".strtolower($m->Fields['UserPassword']->value)."','".$m->Fields['UserEmail']->value."','".$groupid."','".$UserFaceUrl."','".$m->Fields['UserSex']->value."','".$UserRegisterTime."','".addslashes($userSign)."','".addslashes($m->Fields['UserBio']->value)."','".$m->Fields['QQ']->value."','".$m->Fields['ICQ']->value."','".$m->Fields['MSN']->value."','".$m->Fields['Yahoo']->value."','".addslashes($m->Fields['WebAddress']->value)."','".$Birthday."','".$userstatus."')");
		$DDB->update("INSERT INTO {$pw_prefix}memberdata (uid,postnum,rvrc,money,credit,currency,lastvisit,thisvisit) VALUES (".$UserID.",".(int)$m->Fields['TotalPosts']->value.",".$rvrc.",".$money.",".$credit.",".$currency.",'".dt2ut($m->Fields['UserActivityTime']->value)."','".dt2ut($m->Fields['UserActivityTime']->value)."')");
		$s_c++;
		$m->MoveNext();
	}
	$insertadmin && $DDB->update("INSERT INTO {$pw_prefix}administrators (uid,username,groupid) VALUES ".substr($insertadmin, 0, -1));
	$row = $SDB->query("SELECT COUNT(*) AS num FROM {$source_prefix}Users WHERE UserID >= $end");
	if ($row->Fields['num']->value)
	{
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
		report_log();
		newURL($step);
	}
}
elseif ($step == '2')
{
	//版块
	$DDB->query("TRUNCATE TABLE {$pw_prefix}forums");
	$DDB->query("TRUNCATE TABLE {$pw_prefix}forumdata");
	$g = $SDB->query("SELECT * FROM {$source_prefix}Groups");
	while(!$g->EOF)
	{
		$newmaster = '';
		$tmp = explode('|', trim($g->Fields['Moderated']->value));
		foreach ($tmp as $v)
		{
			$v = addslashes(trim($v));
			if ($v)
			{
				$uid = $DDB->query("SELECT uid, groupid FROM {$pw_prefix}members WHERE username = '".$v."'");
				if ($uid)
				{
					$newmaster .= $v.',';
					if ($uid->Fields['groupid']->value == -1)
					{
						$DDB->update("UPDATE {$pw_prefix}members SET groupid = 5 WHERE uid = '".$uid->Fields['uid']->value."' LIMIT 1");
						$DDB->update("INSERT INTO {$pw_prefix}administrators (uid,username,groupid) VALUES (".$uid->Fields['uid']->value.",'".$v."',5)");
					}
				}
			}
		}
		$newmaster && $newmaster = ','.$newmaster;
		$DDB->update("INSERT INTO {$pw_prefix}forums (fid,fup,ifsub,childid,type,name,descrip,vieworder,forumadmin,across) VALUES (".$g->Fields['GroupID']->value.",0,0,1,'category','".addslashes($g->Fields['GroupName']->value)."','".addslashes($g->Fields['GroupDescription']->value)."',".(int)$g->Fields['SortOrder']->value.",'".addslashes($newmaster)."',".(int)$g->Fields['ForumColumns']->value.")");
		$DDB->update("INSERT INTO {$pw_prefix}forumdata (fid) VALUES (".$g->Fields['GroupID']->value.")");
		$s_c++;
		$g->MoveNext();
	}
	$maxfid = $SDB->query("SELECT MAX(GroupID) AS id FROM {$source_prefix}Groups");
	$maxfid = (int)$maxfid->Fields['id']->value;
	$rt = $SDB->query("SELECT * FROM {$source_prefix}Forums");
	$insertforum = $insertforumdb = '';
	$forumdb = array();
	while (!$rt->EOF)
	{
		$forumdb[$rt->Fields['ForumID']->value] = array('ForumID'=>$rt->Fields['ForumID']->value,'ParentID'=>$rt->Fields['ParentID']->value,'GroupID'=>$rt->Fields['GroupID']->value,'Moderated'=>$rt->Fields['Moderated']->value,'ForumName'=>$rt->Fields['ForumName']->value,'ForumDescription'=>$rt->Fields['ForumDescription']->value,'SortOrder'=>$rt->Fields['SortOrder']->value,'TodayPosts'=>$rt->Fields['TodayPosts']->value,'TotalThreads'=>$rt->Fields['TotalThreads']->value,'TotalPosts'=>$rt->Fields['TotalPosts']->value);
		$rt->MoveNext();
	}
	foreach ($forumdb as $k => $v)
	{
		if ($v['ParentID'])
		{
			$ftype = 'sub';
			$ifsub = '1';
		}
		else
		{
			$ftype = 'forum';
			$ifsub = 0;
		}
		$fup = $v['ParentID'] ? ($v['ParentID'] + $maxfid) : $v['GroupID'];
		$newfid = (int)$k + $maxfid;
		$newmaster = '';
		$tmp = explode('|', trim($v['Moderated']));

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
		$upadmin = '';
		getupadmin($k, $upadmin);
		$v['ForumDescription'] = str_replace(array('[url=','[/url]','[URL=','[/URL]','[SIZE=5]','[SIZE=4]','[SIZE=6]','[/SIZE]','[size=5]','[size=4]','[size=6]','[/size]','[B]','[b]','[/B]','[/b]','[COLOR=BLUE]','[COLOR=RED]','[color=blue]','[color=red]','[/COLOR]','[/color]','[IMG]','[img]','[/IMG]','[/img]'),array('<a href=','</a>','<a href=','</a>','<font size=5>','<font size=4>','<font size=6>','</font>','<font size=5>','<font size=4>','<font size=6>','</font>','<strong>','<strong>','</strong>','</strong>','<font color=blue>','<font color=red>','<font color=blue>','<font color=red>','</font>','</font>','<img src=','<img src=','/>','/>'),$v['ForumDescription']);
		$insertforum .= "(".$newfid.",".(int)$fup.",0,1,'".$ftype."','".addslashes($v['ForumName'])."','".addslashes($v['ForumDescription'])."','".$v['SortOrder']."','".addslashes($newmaster)."','".addslashes($upadmin)."'),";
		$insertforumdb .= "(".$newfid.",'".$v['TodayPosts']."','".$v['TotalThreads']."','".$v['TotalPosts']."'),";
		$s_c++;
	}
	$insertforum && $DDB->update("INSERT INTO {$pw_prefix}forums (fid,fup,ifsub,childid,type,name,descrip,vieworder,forumadmin,fupadmin) VALUES ".substr($insertforum, 0, -1));
	$insertforumdb && $DDB->update("INSERT INTO {$pw_prefix}forumdata (fid,tpost,topic,article) VALUES ".substr($insertforumdb, 0, -1));
	writeover(S_P.'tmp_maxfid.php', "\$_maxfid=".$maxfid.";",true);
	report_log();
	newURL($step);
}
elseif ($step == '3')
{
	//主题
	require(S_P.'tmp_maxfid.php');
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}threads");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}tmsgs");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}posts");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}polls");
	}
	$t = $SDB->query("SELECT * FROM {$source_prefix}Threads WHERE ThreadID >= $start AND ThreadID < $end");
	while (!$t->EOF)
	{
		$LastTime = dt2ut($t->Fields['LastTime']->value);
		$PostTime = dt2ut($t->Fields['PostTime']->value);

		$fid = (int)$t->Fields['ForumID']->value + $_maxfid;
		if ($t->Fields['ThreadTop']->value == 2)
		{
			$Body = $SDB->query("SELECT Body From {$source_prefix}Posts Where ThreadID = ".$t['ThreadID']);
			$announce = bbsxp_ubb($Body->Fields['Body']->value);
			$DDB->update("INSERT INTO {$pw_prefix}announce (fid,author,ifopen,startdate,subject,content,ifconvert) VALUES ($fid,'".addslashes($t->Fields['PostAuthor']->value)."',1,'".$t->Fields['PostTime']->value."','".addslashes($t->Fields['Topic']->value)."','".addslashes($announce)."',".((convert($announce) == $announce) ? 0 : 1).")");
			$s_c++;
			$t->MoveNext();
			continue;
		}
		$special = $t->Fields['IsVote']->value ? 1 : 0;
		$bbs = $SDB->query("SELECT PostID,ThreadID,ParentID,PostAuthor,Subject,Body,IPAddress,Visible,PostDate FROM {$source_prefix}Posts WHERE ThreadID = ".$t['ThreadID']);

		while (!$bbs->EOF)
		{
			$bbs_PostDate = dt2ut($bbs->Fields['PostDate']->value);
			$bbs_PostAuthor = addslashes($bbs->Fields['PostAuthor']->value);
			$uid = $DDB->get_one("SELECT uid FROM {$pw_prefix}members WHERE username = '".$bbs_PostAuthor."'");
			$uid = (int)$uid['uid'];
			$bbs_Body = bbsxp_ubb($bbs->Fields['Body']->value);
			$ifconvert = (convert($bbs_Body) == $bbs_Body) ? 1 : 2;
			if ($bbs->Fields['ParentID']->value != '0')
			{
				$DDB->update("INSERT INTO {$pw_prefix}posts (fid,tid,aid,author,authorid,postdate,subject,userip,ifsign,ifconvert,ifcheck,content) VALUES (".$fid.",".$bbs->Fields['ThreadID']->value.",'','".$bbs_PostAuthor."',".$uid.",'".$bbs_PostDate."','".addslashes($bbs->Fields['Subject']->value)."','".addslashes($bbs->Fields['IPAddress']->value)."',1,'".$ifconvert."',".$bbs->Fields['Visible']->value.",'".$bbs_Body."')");
			}
			else
			{
				$tuid = $uid;
				$DDB->update("Replace INTO {$pw_prefix}tmsgs (tid,aid,userip,ifsign,ifconvert,content) VALUES (".$bbs->Fields['ThreadID']->value.",'','".addslashes($bbs->Fields['IPAddress']->value)."',1,'".$ifconvert."','".$bbs_Body."')");
			}
			$s_c++;
			$bbs->MoveNext();
		}
		$DDB->update("INSERT INTO {$pw_prefix}threads (tid,fid,icon,author,authorid,subject,ifcheck,postdate,lastpost,lastposter,hits,replies,topped,locked,digest,special) VALUES (".$t->Fields['ThreadID']->value.",".$fid.",'".$t->Fields['ThreadEmoticonID']->value."','".addslashes($t->Fields['PostAuthor']->value)."',".(int)$tuid.",'".addslashes($t->Fields['Topic']->value)."',".$t->Fields['Visible']->value.",'".$PostTime."','".$LastTime."','".addslashes($t->Fields['LastName']->value)."',".$t->Fields['TotalViews']->value.",".$t->Fields['TotalReplies']->value.",".$t->Fields['ThreadTop']->value.",".$t->Fields['IsLocked']->value.",".$t->Fields['IsGood']->value.",".$special.")");
		if ($t->Fields['IsVote']->value)
		{
			$vote = $SDB->query("SELECT * FROM {$source_prefix}Votes WHERE ThreadID = ".$t->Fields['ThreadID']->value);
			if ($vote)
			{
				$voteoptions = array();
				$voteitem = explode('|',$vote->Fields['Items']->value);
				$voteresult = explode('|',$vote->Fields['Result']->value);
				foreach ($voteitem as $k => $i)
				{
					$voteoptions[$k][0] = $i;
					$voteoptions[$k][1] = $voteresult[$k];
					$voteoptions[$k][2] = array();
				}
				$DDB->update("INSERT INTO {$pw_prefix}polls (tid,voteopts,modifiable,previewable,timelimit) VALUES ('".$t->Fields['ThreadID']->value."','".addslashes(serialize(array('options'=>$voteoptions,'multiple'=>array($vote->Fields['IsMultiplePoll']->value,count($voteitem)))))."',1,1,".ceil((dt2ut($vote->Fields['Expiry']->value) - $t->Fields['PostTime']->value)/86400).")");
			}
		}
		$s_c++;
		$t->MoveNext();
	}
	$row = $SDB->query("SELECT COUNT(*) AS num FROM {$source_prefix}Threads WHERE ThreadID >= $end");
	if ($row->Fields['num']->value)
	{
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
		report_log();
		newURL($step);
	}
}
elseif ($step == '4')
{
	//附件
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}attachs");
	}
	$a = $SDB->query("SELECT * FROM {$source_prefix}PostAttachments WHERE UpFileID >= $start AND UpFileID < $end");
	require(S_P.'tmp_maxfid.php');

	while (!$a->EOF)
	{
		if ($a->Fields['PostID']->value)
		{
			$tp = substr(strtolower($a->Fields['ContentType']->value), 0, strpos($a->Fields['ContentType']->value, '/'));
			$ContentType = $a->Fields['ContentType']->value;
			switch ($tp)
			{
				case 'image':
					$ContentType = 'img';
					break;
				case 'text':
					$ContentType = 'txt';
					break;
				default:
					$ContentType = 'zip';
					break;
			}
			$thread = $SDB->query("SELECT * FROM {$source_prefix}Posts WHERE PostID = ".$a->Fields['PostID']->value);

			if ($thread->EOF)
			{
				$f_c++;
				errors_log($a->Fields['UpFileID']->value."\r\t".$a->Fields['FileName']->value);
				$a->MoveNext();
				continue;
			}
			$forumid = $SDB->query("SELECT ForumID FROM {$source_prefix}Threads WHERE ThreadID = ".$thread->Fields['ThreadID']->value);
			if(!$forumid->EOF){
				$uid = $DDB->get_one("SELECT uid FROM {$pw_prefix}members WHERE username = '".addslashes($a->Fields['UserName']->value)."'");
				if($uid)
				{
					$UpFile = str_replace("UpFile/","",$a->Fields['FilePath']->value);
					$DDB->update("REPLACE INTO {$pw_prefix}attachs (aid,fid,uid,tid,pid,name,type,size,attachurl,uploadtime) VALUES ('".$a->Fields['UpFileID']->value."','".intval($_maxfid + $forumid->Fields['ForumID']->value)."','".(int)$uid['uid']."','".$thread->Fields['ThreadID']->value."','".($thread->Fields['ParentID']->value ? $thread->Fields['PostID']->value : 0)."','".addslashes($a->Fields['FileName']->value)."','".$ContentType."','".(ceil($a->Fields['ContentSize']->value / 1024))."','".addslashes($UpFile)."','".dt2ut($a->Fields['Created']->value)."')");
				}
			}
			if ($thread->Fields['ParentID']->value)
			{
				$attable = 'posts';
				$add = ' pid = '.$a->Fields['PostID']->value;
			}
			else
			{
				$attable = 'tmsgs';
				$add = ' tid = '.$thread->Fields['ThreadID']->value;
			}
			$attdata = array();
			$atta = $DDB->get_one("SELECT aid FROM {$pw_prefix}{$attable} WHERE".$add);
			if (!$atta) {
				$a->MoveNext();
				continue;
			}
			$atta['aid'] && $attdata = unserialize($atta['aid']);
			$attdata[$a->Fields['UpFileID']->value] = array('aid'=>$a->Fields['UpFileID']->value,'name'=>addslashes($a->Fields['FileName']->value),'type'=>$a->Fields['ContentType']->value,'attachurl'=>addslashes($a->Fields['FilePath']->value),'needrvrc'=>0,'size'=>round($a->Fields['ContentSize']->value/1024),'hits'=>0,'desc'=>'','ifthumb'=>0);
			$DDB->query("UPDATE {$pw_prefix}{$attable} SET aid = '".addslashes(serialize($attdata))."' WHERE".$add);
			$s_c++;
		}
		else
		{
			$f_c++;
			errors_log($a->Fields['UpFileID']->value."\t".$a->Fields['FileName']->value."\t帖子不存在\r");
		}
		$a->MoveNext();
	}
	$row = $SDB->query("SELECT COUNT(*) AS num FROM {$source_prefix}PostAttachments WHERE UpFileID >= $end");
	if ($row->Fields['num']->value)
	{
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
		report_log();
		newURL($step);
	}
}
elseif ($step == '5')
{
	//标签
	$DDB->query("TRUNCATE TABLE {$pw_prefix}tags");
	$DDB->query("TRUNCATE TABLE {$pw_prefix}tagdata");
	$t = $SDB->query("SELECT TagID,TagName,TotalPosts,ThreadID FROM {$source_prefix}PostTags");
	while (!$t->EOF)
	{
		$DDB->update("INSERT INTO {$pw_prefix}tags (tagid,tagname,num) VALUES (".$t->Fields['TagID']->value.",'".addslashes($t->Fields['TagName']->value)."',".$t->Fields['TotalPosts']->value.")");
		$s_c++;
		$t->MoveNext();
	}
	$t = $SDB->query("SELECT pt.TagID,p.ThreadID FROM {$source_prefix}PostInTags pt LEFT JOIN {$source_prefix}Posts p ON pt.PostID = p.PostID");
	while (!$t->EOF)
	{
		$DDB->update("INSERT INTO {$pw_prefix}tagdata (tagid,tid) VALUES (".$t->Fields['TagID']->value.",".$t->Fields['ThreadID']->value.")");
		$t->MoveNext();
	}
	report_log();
	newURL($step);
}
elseif ($step == '6')
{
	//短信
    $message_sql = $relations_sql = $replies_sql = array();
	if(!$start)
	{
        $DDB->update("TRUNCATE TABLE {$pw_prefix}ms_messages");
        $DDB->update("TRUNCATE TABLE {$pw_prefix}ms_relations");
        $DDB->update("TRUNCATE TABLE {$pw_prefix}ms_replies");
	}
	$m = $SDB->query("SELECT * FROM {$source_prefix}PrivateMessages WHERE MessageID >= $start AND MessageID < $end");
	while(!$m->EOF)
	{
		$a = $m->Fields['Subject']->value;
		$m_Body = strval($m->Fields['Body']->value);

		if (empty($m->Fields['SenderUserName']->value) || empty($m->Fields['RecipientUserName']->value) || empty($m_Body))
		{
			$f_c++;
			errors_log($m->Fields['MessageID']->value."\t".$m->Fields['Subject']->value);
			$m->MoveNext();
			continue;
		}

		$touid = $DDB->get_one("SELECT uid FROM {$pw_prefix}members WHERE username = '".addslashes($m->Fields['RecipientUserName']->value)."'");
		$fromuid = $DDB->get_one("SELECT uid FROM {$pw_prefix}members WHERE username = '".addslashes($m->Fields['SenderUserName']->value)."'");
		if ($touid && $fromuid)
		{
			$createtime = dt2ut($m->Fields['CreateTime']->value);


	        $message_sql[] = "('".$m['id']."',".$fromuid['uid'].",'".addslashes($m->Fields['SenderUserName']->value)."','".addslashes($m->Fields['Subject']->value)."','".addslashes($m_Body)."','".serialize(array('categoryid'=>1,'typeid'=>100))."',".$createtime.",".$createtime.",'".serialize(addslashes($m->Fields['SenderUserName']->value))."')";
	        $replies_sql[] = "('".$m['id']."',".$m['pmid'].",'".$fromuid['uid']."','".addslashes($m->Fields['SenderUserName']->value)."','".addslashes($m_Body)."','".$m['message']."','1',".$createtime.",".$createtime.")";

            $userIds = "";
	        $userIds = array($touid['uid'],$fromuid['uid']);
	        foreach($userIds as $otherId){
	            $relations_sql[] = "(".$otherId.",'".$m['id']."','1','100','0',".(($otherId == $fromuid['uid']) ? 1 : 0).",".$createtime.",".$createtime.")";
            }
            /*
			if ($m->Fields['IsRecipientDelete']->value=='0')
			{
				$DDB->update("INSERT INTO {$pw_prefix}msg (touid,fromuid,username,type,ifnew,mdate) VALUES (".$touid['uid'].",".$fromuid['uid'].",'".addslashes($m->Fields['SenderUserName']->value)."','rebox',".($m->Fields['IsRead']->value^1).",'".$createtime."')");
				$mid = $DDB->insert_id();
				$DDB->update("INSERT INTO {$pw_prefix}msgc (mid,title,content) VALUES ($mid,'".addslashes($m->Fields['Subject']->value)."','".addslashes($m_Body)."')");
				if ($touid['uid'] != $fromuid['uid'])
				{
					$DDB->update("INSERT INTO {$pw_prefix}msglog (mid,uid,withuid,mdate,mtype) VALUES ($mid,".$fromuid['uid'].",".$touid['uid'].",'$createtime','send')");
					$DDB->update("INSERT INTO {$pw_prefix}msglog (mid,uid,withuid,mdate,mtype) VALUES ($mid,".$touid['uid'].",".$fromuid['uid'].",'$createtime','receive')");
				}
			}
			if ($m->Fields['IsSenderDelete']->value=='0')
			{
				$DDB->update("INSERT INTO {$pw_prefix}msg (touid,fromuid,username,type,ifnew,mdate) VALUES (".$touid['uid'].",".$fromuid['uid'].",'".addslashes($m->Fields['RecipientUserName']->value)."','sebox',0,'".$createtime."')");
				$mid = $DDB->insert_id();
				$DDB->update("INSERT INTO {$pw_prefix}msgc (mid,title,content) VALUES ($mid,'".addslashes($m->Fields['Subject']->value)."','".addslashes($m_Body)."')");
				$s_c++;
			}*/
		}
		else
		{
			$f_c++;
			errors_log($m->Fields['MessageID']->value."\t".$m->Fields['Subject']->value);
		}
		$m->MoveNext();
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

	$row = $SDB->query("SELECT COUNT(*) AS num FROM {$source_prefix}PrivateMessages WHERE MessageID >= $end");
	if ($row->Fields['num']->value)
	{
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
		report_log();
		newURL($step);
	}
}
elseif ($step == '7')
{
	//好友
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}friends");
	}
	$f = $SDB->query("SELECT FavoriteID,OwnerUserName,FriendUserName FROM {$source_prefix}FavoriteUsers WHERE FavoriteID >= $start AND FavoriteID < $end");
	while(!$f->EOF)
	{
		$uid = $DDB->get_one("SELECT uid FROM {$pw_prefix}members WHERE username = '".addslashes($f->Fields['OwnerUserName']->value)."'");
		$friendid = $DDB->get_one("SELECT uid FROM {$pw_prefix}members WHERE username = '".addslashes($f->Fields['FriendUserName']->value)."'");
		if ($uid && $friendid)
		{
			$DDB->update("INSERT INTO {$pw_prefix}friends (uid,friendid,status) VALUES (".$uid['uid'].",".$friendid['uid'].",0)");
		}
		$s_c++;
		$f->MoveNext();
	}
	$row = $SDB->query("SELECT COUNT(*) AS num FROM {$source_prefix}FavoriteUsers WHERE FavoriteID >= $end");
	if ($row->Fields['num']->value)
	{
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
		report_log();
		newURL($step);
	}
}
elseif ($step == '8')
{
	//收藏
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}favors");
	}
	$f = $SDB->query("SELECT FavoriteID,OwnerUserName,PostID FROM {$source_prefix}FavoritePosts WHERE FavoriteID >= $start AND FavoriteID < $end");
	while(!$f->EOF)
	{
		$uid = $DDB->get_one("SELECT uid FROM {$pw_prefix}members WHERE username = '".addslashes($f->Fields['OwnerUserName']->value)."'");
		if ($uid)
		{
			$ucount = $DDB->get_one("SELECT COUNT(*) num FROM {$pw_prefix}favors WHERE uid = ".$uid['uid']);
			if ($ucount['num'])
			{
				$DDB->update("UPDATE {$pw_prefix}favors SET tids = CONCAT_WS(',',tids,'".$f->Fields['PostID']->value."') WHERE uid = ".$uid['uid']." LIMIT 1");
			}
			else
			{
				$DDB->update("INSERT INTO {$pw_prefix}favors (uid, tids) VALUES (".$uid['uid'].", '".$f->Fields['PostID']->value."')");
			}
			$s_c++;
		}
		$f->MoveNext();
	}
	$row = $SDB->query("SELECT COUNT(*) AS num FROM {$source_prefix}FavoritePosts WHERE FavoriteID >= $end");
	if ($row->Fields['num']->value)
	{
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
		report_log();
		newURL($step);
	}
}
elseif ($step == '9')
{
	//友情链接
	require_once S_P.'lang_'.$dest_charset.'.php';
	$DDB->query("TRUNCATE TABLE {$pw_prefix}sharelinks");
	$l = $SDB->query("SELECT * FROM {$source_prefix}Links");
	$ilink = '';
	while (!$l->EOF)
	{
		$ilink .= "('".$l->Fields['SortOrder']->value."','".addslashes($l->Fields['Name']->value)."','".addslashes($l->Fields['URL']->value)."','".addslashes($l->Fields['Intro']->value)."','".addslashes($l->Fields['Logo']->value)."',1),";
		$s_c++;
		$l->MoveNext();
	}
	$ilink .= $lang['link'];
	$DDB->update("INSERT INTO {$pw_prefix}sharelinks (threadorder,name,url,descrip,logo,ifcheck) VALUES ".$ilink);
	report_log();
	newURL($step);
}
elseif($step == '10')
{
	//设置
	$s = $SDB->query("SELECT * FROM {$source_prefix}SiteSettings");
	$site = strval($s->Fields['SiteSettingsXML']->value);
	preg_match_all('~<([a-z]+?)>(.*?)<\/\\1>~is',$site,$m,PREG_SET_ORDER);
	foreach($m as $key=>$value)
	{
		$tmp[$value[1]] = $value[2];
	}
	Add_S($tmp);
	$DDB->update("UPDATE {$pw_prefix}config SET db_value = '".$tmp['SiteName']."' WHERE db_name = 'db_bbsname'");
	$DDB->update("UPDATE {$pw_prefix}config SET db_value = '".$tmp['SiteUrl']."' WHERE db_name = 'db_bbsurl'");
	$DDB->update("UPDATE {$pw_prefix}config SET db_value = '".$tmp['WebMasterEmail']."' WHERE db_name = 'db_ceoemail'");
	$DDB->update("UPDATE {$pw_prefix}config SET db_value = '".$tmp['MetaDescription']."' WHERE db_name = 'db_metadescrip'");
	$DDB->update("UPDATE {$pw_prefix}config SET db_value = '".$tmp['MetaKeywords']."' WHERE db_name = 'db_metakeyword'");
	$tmp['SiteDisabled'] = $tmp['SiteDisabled']=='0' ? '1' : '0';
	$DDB->update("UPDATE {$pw_prefix}config SET db_value = '".$tmp['SiteDisabled']."' WHERE db_name = 'db_bbsifopen'");
	$DDB->update("UPDATE {$pw_prefix}config SET db_value = '".$tmp['SiteDisabledReason']."' WHERE db_name = 'db_whybbsclose'");
	$DDB->update("UPDATE {$pw_prefix}config SET db_value = '".$tmp['DisplayWhoIsOnline']."' WHERE db_name = 'db_indexonline'");
	$DDB->update("UPDATE {$pw_prefix}config SET db_value = '".$tmp['DisplayBirthdays']."' WHERE db_name = 'db_indexshowbirth'");
	$DDB->update("UPDATE {$pw_prefix}config SET db_value = '".$tmp['DisplayLink']."' WHERE db_name = 'db_indexlink'");
	$DDB->update("UPDATE {$pw_prefix}config SET db_value = '".$tmp['AllowNewUserRegistration']."' WHERE db_name = 'rg_allowregister'");
	$namelen = $tmp['UserNameMinLength']."\t".$tmp['UserNameMaxLength'];
	$DDB->update("UPDATE {$pw_prefix}config SET db_value = '".$namelen."' WHERE db_name = 'rg_namelen'");
	$DDB->update("UPDATE {$pw_prefix}config SET db_value = '".$tmp['MaxVoteOptions']."' WHERE db_name = 'db_selcount'");
	$DDB->update("UPDATE {$pw_prefix}config SET db_value = '".$tmp['DisplayForumUsers']."' WHERE db_name = 'db_threadonline'");
	$DDB->update("UPDATE {$pw_prefix}config SET db_value = '".$tmp['DisplayThreadUsers']."' WHERE db_name = 'db_showonline'");

	$wordfb = explode("|",$tmp['BannedText']);
	foreach($wordfb as $key=>$value)
	{
		$DDB->update("INSERT INTO {$pw_prefix}wordfb(word,type)values('".addslashes($value)."',1)");
	}
	$tmp['BannedIP'] = str_replace("|",",",$tmp['BannedIP']);
	$DDB->update("UPDATE {$pw_prefix}config SET db_value = '".$tmp['BannedIP']."' WHERE db_name = 'db_ipban'");
	$tmp['BannedRegUserName'] = str_replace("|",",",$tmp['BannedRegUserName']);
	$DDB->update("UPDATE {$pw_prefix}config SET db_value = '".$tmp['BannedRegUserName']."' WHERE db_name = 'rg_banname'");

	$reg = $tmp['EnableAntiSpamTextGenerateForRegister'];
	$log = $tmp['EnableAntiSpamTextGenerateForLogin'];
	$post = $tmp['EnableAntiSpamTextGenerateForPost'];
	if($reg!='0'){$reg=1;}
	if($log!='0'){$log=2;}
	if($post!='0'){$post=4;}
	$B = ($reg+$log+$post)> 0 ? $reg+$log+$post : '';
	$DDB->update("UPDATE {$pw_prefix}config SET db_value = '".$B."' WHERE db_name = 'db_gdcheck'");
	$s_c = 19;
	report_log();
	newURL($step);
}
else
{
	ObHeader($basename.'?action=finish&dbtype='.$dbtype);
}

##########################

function getupadmin($cid, &$upadmin)
{
	global $forumdb;
	if ($forumdb[$cid]['Moderated'])
	{
		$BoardMaster = explode('|', $forumdb[$cid]['Moderated']);
		foreach($BoardMaster as $value)
		{
			$upadmin .= $upadmin ? addslashes($value).',' : ','.addslashes($value).',';
		}
	}
	if ($forumdb[$cid]['ParentID'])
	{
		getupadmin($forumdb[$cid]['ParentID'], $upadmin);
	}
}

//转换用户组
function changegroups()
{
	global $pw_prefix, $source_prefix, $SDB, $DDB, $dest_charset;
	$DDB->update("TRUNCATE TABLE {$pw_prefix}usergroups");
	require_once S_P.'lang_'.$dest_charset.'.php';
	$DDB->update($lang['group']);
	$rt = $SDB->query("SELECT RoleID, Name FROM {$source_prefix}Roles WHERE RoleID > 3");
	$specialdata = array();


	$mright['atclog'] = $mright['show'] = $mright['msggroup'] = $mright['ifmemo'] = $mright['modifyvote'] = $mright['viewvote'] = $mright['allowreward'] = $mright['allowencode'] = $mright['leaveword'] = $mright['viewvote'] = $mright['viewvote'] = 1;
	$mright['viewipfrom'] = $mright['anonymous'] = $mright['dig'] = $mright['atccheck'] = $mright['markable'] = $mright['postlimit'] = 0;
	$mright['imgwidth'] = $mright['imgheight'] = $mright['fontsize'] = $mright['maxsendmsg'] = $mright['maxfavor'] = $mright['maxgraft'] = '';
	$mright['uploadtype'] = $uploadtype ? addslashes(serialize($uploadtype)) : '';
	$mright['media']  = $mright['pergroup'] = '';
	$mright['markdb'] = "10|0|10||1";
	$mright['schtime'] = 'all';
	$mright = P_serialize($mright);

	while (!$rt->EOF)
	{
		//pwGroupref(array('gptype'=>'special','grouptitle'=>addslashes($rt->Fields['Name']->value),'groupimg'=>8,'grouppost'=>0,'maxmsg'=>10,'allowhide'=>0,'allowread'=>1,'allowportait'=>0,'upload'=>0,'allowrp'=>1,'allowhonor'=>0,'allowdelatc'=>1,'allowpost'=>1,'allownewvote'=>0,'allowvote'=>1,'allowactive'=>0,'htmlcode'=>0,'wysiwyg'=>0,'allowhidden'=>1,'allowencode'=>0,'allowsell'=>0,'allowsearch'=>1,'allowmember'=>0,'allowprofile'=>0,'allowreport'=>1,'allowmessage'=>1,'allowsort'=>0,'alloworder'=>1,'allowupload'=>1,'allowdownload'=>1,'allowloadrvrc'=>0,'allownum'=>50,'edittime'=>0,'postpertime'=>0,'searchtime'=>10,'signnum'=>30,'mright'=>'show	0\n1\nviewipfrom	0\n1\nimgwidth	\n1\nimgheight	\n1\nfontsize	3\n1\nmsggroup	0\n1\nmaxfavor	50\n1\nviewvote	0\n1\natccheck	1\n1\nmarkable	0\n1\npostlimit	\n1\nuploadmaxsize	0\n1\nuploadtype	\n1\nmarkdb	|||','ifdefault'=>0,'allowadmincp'=>0,'visithide'=>0,'delatc'=>0,'moveatc'=>0,'copyatc'=>0,'typeadmin'=>0,'viewcheck'=>0,'viewclose'=>0,'attachper'=>0,'delattach'=>0,'viewip'=>0,'markable'=>0,'maxcredit'=>0,'credittype'=>'','creditlimit'=>'','banuser'=>0,'bantype'=>0,'banmax'=>0,'viewhide'=>0,'postpers'=>0,'atccheck'=>0,'replylock'=>0,'modown'=>0,'modother'=>0,'deltpcs'=>0,'sright'=>''));
		//$grouptitle=getGrouptitle($gid,$grouptitle,false);
		pwGroupref(array('gptype'=>'special','grouptitle'=>addslashes($rt->Fields['Name']->value),'groupimg'=>8,'grouppost'=>0,'maxmsg'=>10,'allowhide'=>0,'allowread'=>1,'allowportait'=>0,'upload'=>0,'allowrp'=>1,'allowhonor'=>0,'allowdelatc'=>1,'allowpost'=>1,'allownewvote'=>0,'allowvote'=>1,'allowactive'=>0,'htmlcode'=>0,'wysiwyg'=>0,'allowhidden'=>1,'allowencode'=>0,'allowsell'=>0,'allowsearch'=>1,'allowmember'=>0,'allowprofile'=>0,'allowreport'=>1,'allowmessage'=>1,'allowsort'=>0,'alloworder'=>1,'allowupload'=>1,'allowdownload'=>1,'allowloadrvrc'=>0,'allownum'=>50,'edittime'=>0,'postpertime'=>0,'searchtime'=>10,'signnum'=>30,'mright'=>$mright,'ifdefault'=>0,'allowadmincp'=>0,'visithide'=>0,'delatc'=>0,'moveatc'=>0,'copyatc'=>0,'typeadmin'=>0,'viewcheck'=>0,'viewclose'=>0,'attachper'=>0,'delattach'=>0,'viewip'=>0,'markable'=>0,'maxcredit'=>0,'credittype'=>'','creditlimit'=>'','banuser'=>0,'bantype'=>0,'banmax'=>0,'viewhide'=>0,'postpers'=>0,'atccheck'=>0,'replylock'=>0,'modown'=>0,'modother'=>0,'deltpcs'=>0,'sright'=>''));

		$DDB->update("INSERT INTO {$pw_prefix}usergroups (gptype,grouptitle,groupimg,grouppost) VALUES ('special','".addslashes($rt->Fields['Name']->value)."','8','0')");

		$specialdata[$rt->Fields['RoleID']->value] = $DDB->insert_id();
		$rt->MoveNext();
	}
	$rt = $SDB->query("SELECT RankName, PostingCountMin FROM {$source_prefix}Ranks WHERE RoleID = 0");

	while (!$rt->EOF)
	{
		pwGroupref(
		array(
		'gptype'=>'member',
		'grouptitle'=>addslashes($rt->Fields['RankName']->value),
		'groupimg'=>8,
		'grouppost'=>$rt->Fields['PostingCountMin']->value,
		'maxmsg'=>10,
		'allowhide'=>0,
		'allowread'=>1,
		'allowportait'=>0,
		'upload'=>0,
		'allowrp'=>1,
		'allowhonor'=>0,
		'allowdelatc'=>1,
		'allowpost'=>1,
		'allownewvote'=>0,
		'allowvote'=>1,
		'allowactive'=>0,
		'htmlcode'=>0,
		'wysiwyg'=>0,
		'allowhidden'=>1,
		'allowencode'=>0,
		'allowsell'=>0,
		'allowsearch'=>1,
		'allowmember'=>0,
		'allowprofile'=>0,
		'allowreport'=>1,
		'allowmessage'=>1,
		'allowsort'=>0,
		'alloworder'=>1,
		'allowupload'=>1,
		'allowdownload'=>1,
		'allowloadrvrc'=>0,
		'allownum'=>50,
		'edittime'=>0,
		'postpertime'=>0,
		'searchtime'=>10,
		'signnum'=>30,
		'mright'=>$mright,
		'ifdefault'=>0,
		'allowadmincp'=>0,
		'visithide'=>0,
		'delatc'=>0,
		'moveatc'=>0,
		'copyatc'=>0,
		'typeadmin'=>0,
		'viewcheck'=>0,
		'viewclose'=>0,
		'attachper'=>0,
		'delattach'=>0,
		'viewip'=>0,
		'markable'=>0,
		'maxcredit'=>0,
		'credittype'=>'',
		'creditlimit'=>'',
		'banuser'=>0,
		'bantype'=>0,
		'banmax'=>0,
		'viewhide'=>0,
		'postpers'=>0,
		'atccheck'=>0,
		'replylock'=>0,
		'modown'=>0,
		'modother'=>0,
		'deltpcs'=>0,
		'sright'=>'',));

		//$grouptitle=getGrouptitle($gid,$grouptitle,false);

		$DDB->update("INSERT INTO {$pw_prefix}usergroups (gptype,grouptitle,groupimg,grouppost)VALUES ('member','".addslashes($rt->Fields['RankName']->value)."','8','".$rt->Fields['PostingCountMin']->value."')");

		$rt->MoveNext();
	}
	return $specialdata;
}

function bbsxp_ubb($content)
{
	return preg_replace(array('/\[em(\d+?)\]/i','/\[mp=(\d+?),(\d+?),(?:true|false)\](.+?)\[\/mp\]/si','/\[rm=(\d+?),(\d+?),(?:true|false)\](.+?)\[\/rm\]/si'),array('[s:\\1]','[wmv=\\1,\\2,0]\\3[/wmv]','[rm=\\1,\\2,0]\\3[/rm]'),str_replace(array('[BR]','[B]','[/B]','[I]','[/I]','[U]','[/U]','[SIZE]','[/SIZE]','[center]','[left]','[right]','[/left]','[/right]','[/center]','[URL=','[/URL]','[EMAIL]','[/EMAIL]','[IMG]','[/IMG]','[QUOTE]','[/QUOTE]','[replyview]','[/replyview]','UpFile/UpAttachment/'),array('','[b]','[/b]','[i]','[/i]','[u]','[/u]','[size]','[/size]','[align=center]','[align=left]','[align=right]','[/align]','[/align]','[/align]','[url=','[/url]','[email]','[/email]','[img]','[/img]','[quote]','[/quote]','[post]','[/post]','attachment/UpAttachment/'),$content));
}
?>