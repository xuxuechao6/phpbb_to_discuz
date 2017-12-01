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
	//表情
	$DDB->update("DELETE FROM {$pw_prefix}smiles WHERE id > 1");
	$DDB->update("ALTER TABLE {$pw_prefix}smiles AUTO_INCREMENT = 0");
	$k=1;
	$DDB->update("INSERT INTO {$pw_prefix}smiles (id,path,type,name,vieworder) VALUES(2,'UBBicon',0,'leadbbs',2)");
	for($i = 3; $i < 49; $i++)
	{
		if($k < 10){$k='0'.$k;}
		$url = "EM".$k.".GIF";
		$DDB->update("INSERT INTO {$pw_prefix}smiles (id,path,type,name,vieworder) VALUES(".$i.",'".$url."',2,'',".$k.")");	
		$_pwface[] = '[s:'.$DDB->insert_id().']';
		$_dzface[] = "[EM".$k."]";
		$k++;
	}
	writeover(S_P.'tmp_face.php', "\$_pwface = ".pw_var_export($_pwface).";\n\$_dzface = ".pw_var_export($_dzface).";", true);
	report_log();
	newURL($step);
}
if ($step == '2')
{
	//用户数据
	require_once (S_P.'tmp_credit.php');
	if (!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}members");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}memberdata");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}administrators");
	}
	$head = array();
	$m = $SDB->query("SELECT ID,UserName,Pass,Mail,Sex,Birthday,ApplyTime,Prevtime,Userphoto,IP,Homepage,Underwrite,Points,OnlineTime,AnnounceNum,FaceUrl,FaceWidth,FaceHeight,AnnounceTopic,AnnounceGood,UploadNum,LastWriteTime,CachetValue,CharmPoint,ICQ,OICQ FROM {$source_prefix}User WHERE ID >= $start AND ID < $end");
	while (!$m->EOF)
	{
		$UserName = addslashes($m->Fields['UserName']->value);
		if (htmlspecialchars($UserName)!=$UserName || CK_U($UserName))
		{
			$m->MoveNext();
			continue;
		}
		$ID = (int)$m->Fields['ID']->value;
		if ($m->Fields['FaceUrl']->value)
		{
			$apre = strtolower(substr($m->Fields['FaceUrl']->value,0,7));
			if($apre == '../imag')
			{
				$FaceUrl = substr($m->Fields['FaceUrl']->value, 22).'|3|'.$m->Fields['FaceWidth']->value.'|'.$m->Fields['FaceHeight']->value.'|';
			}
			elseif ($apre == 'http://')
			{
	     		 $FaceUrl = $m->Fields['FaceUrl']->value.'|2|'.$m->Fields['FaceWidth']->value.'|'.$m->Fields['FaceHeight']->value.'|';
	    	}
	   		$face = explode("/",$m->Fields['FaceUrl']->value);
	    	$head[$face[count($face)-1]] = $m->Fields['ID']->value;
		}
		else
		{
			$FaceUrl = str_pad($m->Fields['Userphoto']->value, 4, '0', STR_PAD_LEFT).'.gif|1|||';
		}
		if($m->Fields['Sex']->value == '男')
		{
			$Sex = 1;
		}
		elseif ($m->Fields['Sex']->value == '女')
		{
			$Sex = 2;
		}
		else
		{
			$Sex = 0;
		}
		$ApplyTime = dt2ut(RestoreTime($m->Fields['ApplyTime']->value));
		$Birthday = $m->Fields['Birthday']->value ? mkbd($m->Fields['Birthday']->value) : '0000-00-00';
		eval($creditdata);
		$Underwrite = lead_ubb($m->Fields['Underwrite']->value);
		$signchange = (convert($Underwrite) == $Underwrite) ? '1' : '2';
		$userstatus=($signchange-1)*256+128+1*64+4;//用户位状态设置
		$DDB->update("INSERT INTO {$pw_prefix}members (uid,username,password,email,groupid,icon,gender,regdate,signature,oicq,icq,site,bday,yz,userstatus) VALUES (".$m->Fields['ID']->value.",'".addslashes($UserName)."','".$m->Fields['Pass']->value."','".addslashes($m->Fields['Mail']->value)."',-1,'".addslashes($FaceUrl)."','".$Sex."','".$ApplyTime."','".addslashes($Underwrite)."','".addslashes($m->Fields['OICQ']->value)."','".addslashes($m->Fields['ICQ']->value)."','".addslashes($m->Fields['Homepage']->value)."','".$Birthday."',1,'".$userstatus."')");
		$DDB->update("INSERT INTO {$pw_prefix}memberdata (uid,digests,postnum,rvrc,money,credit,currency,lastvisit,uploadnum) VALUES (".$m->Fields['ID']->value.",".$m->Fields['AnnounceGood']->value.",".(int)$m->Fields['AnnounceNum']->value.",".$rvrc.",".$money.",".$credit.",".$currency.",'".dt2ut(RestoreTime($m->Fields['LastWriteTime']->value))."',".$m->Fields['UploadNum']->value.")");
		$s_c++;
		$m->MoveNext();
	}
	writeover(S_P.'tmp_head.php', "\$_head = ".pw_var_export($head).";", true);
	$row = $SDB->query("SELECT COUNT(*) AS num FROM {$source_prefix}User WHERE ID >= $end");
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
elseif ($step == '3')
{
	//版块信息
	$DDB->query("TRUNCATE TABLE {$pw_prefix}forums");
	$DDB->query("TRUNCATE TABLE {$pw_prefix}forumdata");
	
	$rt = $SDB->query("SELECT BoardID,BoardAssort,BoardName,BoardIntro,TopicNum,AnnounceNum,HiddenFlag,ForumPass,MasterList FROM {$source_prefix}Boards ORDER BY BoardID");
	$insertforum = $insertforumdb = '';
	$forumdb = array();
	while(!$rt->EOF)
	{
		$forumdb[$rt->Fields['BoardID']->value] = array('BoardID'=>$rt->Fields['BoardID']->value,'BoardAssort'=>$rt->Fields['BoardAssort']->value,'BoardName'=>$rt->Fields['BoardName']->value,'BoardIntro'=>$rt->Fields['BoardIntro']->value,'TopicNum'=>$rt->Fields['TopicNum']->value,'AnnounceNum'=>$rt->Fields['AnnounceNum']->value,'HiddenFlag'=>$rt->Fields['HiddenFlag']->value,'ForumPass'=>$rt->Fields['ForumPass']->value,'MasterList'=>$rt->Fields['MasterList']->value);
		$rt->MoveNext();
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

	$g = $SDB->query("SELECT AssortID,AssortName,AssortMaster FROM {$source_prefix}Assort");
	while(!$g->EOF)
	{
		$newmaster = '';
		$tmp = explode(',', trim($g->Fields['AssortMaster']->value));
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
		$DDB->update("INSERT INTO {$pw_prefix}forums (childid,type,name,forumadmin) VALUES (1,'category','".addslashes($g->Fields['AssortName']->value)."','".$newmaster."')");
		$fid = $DDB->insert_id();
		$DDB->update("INSERT INTO {$pw_prefix}forumdata (fid) VALUES (".$fid.")");
		$DDB->update("UPDATE {$pw_prefix}forums SET fup = $fid, fupadmin = '$newmaster' WHERE fup = ".$g->Fields['AssortID']->value);
		$g->MoveNext();
	}
	report_log();
	newURL($step);
}
elseif ($step == '4')
{
	//主题信息
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}threads");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}polls");
	}
	$t = $SDB->query("SELECT ID,BoardID,RootID,ChildNum,Title,FaceIcon,ndatetime,LastTime,Hits,UserName,UserID,LastUser,VisitIP,TopicType,GoodFlag,PollNum FROM {$source_prefix}Topic WHERE ID >= $start AND ID < $end");
	while (!$t->EOF)
	{
		if ($t->Fields['BoardID']->value == 444)
		{
			$t->MoveNext();
			continue;
		}
		
		$t_LastTime = dt2ut(RestoreTime($t->Fields['LastTime']->value));
		$t_ndatetime = dt2ut(RestoreTime($t->Fields['ndatetime']->value));
		if ($t->Fields['TopicType']->value)
		{
			$special = '1';
		}
		else
		{
			$special = '0';
		}
		
		$DDB->update("INSERT INTO {$pw_prefix}threads (tid,fid,icon,author,authorid,subject,ifcheck,postdate,lastpost,lastposter,hits,replies,digest,special) VALUES (".$t->Fields['ID']->value.",".$t->Fields['BoardID']->value.",'".$t->Fields['FaceIcon']->value."','".addslashes($t->Fields['UserName']->value)."',".$t->Fields['UserID']->value.",'".addslashes($t->Fields['Title']->value)."',1,'".$t_ndatetime."','".$t_LastTime."','".addslashes($t->Fields['LastUser']->value)."',".$t->Fields['Hits']->value.",".$t->Fields['ChildNum']->value.",".$t->Fields['GoodFlag']->value.",".$special.")");
		if ($t->Fields['TopicType']->value)
		{
			$v = $SDB->query("SELECT VoteName,VoteNum FROM {$source_prefix}VoteItem WHERE AnnounceID = ".$t->Fields['ID']->value);
			while (!$v->EOF)
			{
				$voteoptions = array();
				$voteoptions[][0] = $v->Fields['VoteName']->value;
				$voteoptions[][1] = $v->Fields['VoteNum']->value;
				$v2 = $SDB->query("SELECT UserName,VoteItem FROM {$source_prefix}VoteUser WHERE AnnounceID = ".$t->Fields['ID']->value);
				while (!$v2->EOF)
				{
					$voteoptions[][2][] = $v->Fields['VoteName']->value;
					$v2->MoveNext();
				}
				$v->MoveNext();
			}
			$voteoptions && $DDB->update("INSERT INTO {$pw_prefix}polls (tid,voteopts,modifiable,previewable,timelimit) VALUES ('".$t->Fields['ID']->value."','".addslashes(serialize(array('options'=>$voteoptions,'multiple'=>array(1,count($voteoptions)))))."',1,1,0)");
		}
		$s_c++;
		$t->MoveNext();
	}
	$row = $SDB->query("SELECT COUNT(*) AS num FROM {$source_prefix}Topic WHERE ID >= $end");
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
	//回复
	require_once(S_P.'tmp_face.php');
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}tmsgs");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}posts");
	}
	$bbs = $SDB->query("SELECT ID,ParentID,BoardID,RootID,Title,Content,ndatetime,UserName,UserID,IPAddress,NotReplay,UserName FROM {$source_prefix}Announce WHERE ID >= $start AND ID < $end");
	$ifupload = '';
	while (!$bbs->EOF)
	{
		$aid = '';
		$Content = $bbs->Fields['Content']->value;
		$load = preg_match('~\[upload=(\d+?),\d+?].+?\[/upload\]~i',$Content);
		if($load)
		{
			//取得附件信息
			$a = $SDB->query("SELECT * FROM {$source_prefix}Upload WHERE announceid = ".$bbs->Fields['ID']->value);
			$attdata = array();
			while(!$a->EOF)
			{
				switch ($a->Fields['FileType']->value)
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
				$attdata[$a->Fields['ID']->value] = array('aid'=>$a->Fields['ID']->value,'name'=>addslashes($a->Fields['FileName']->value),'type'=>$filetype,'attachurl'=>addslashes($a->Fields['PhotoDir']->value),'needrvrc'=>0,'size'=>round($a->Fields['FileName']->value/1024),'hits'=>$a->Fields['hits']->value,'desc'=>'','ifthumb'=>0);
				$a->MoveNext();
			}
			$aid = addslashes(serialize($attdata));
		}
		$bbs_ndatetime = dt2ut(RestoreTime($bbs->Fields['ndatetime']->value));
		$bbs_Content = lead_ubb(str_replace($_dzface,$_pwface,$Content));
		$ifconvert = (convert($bbs_Content) == $bbs_Content) ? 1 : 2;
		if ($bbs->Fields['ParentID']->value)
		{
			$DDB->update("REPLACE INTO {$pw_prefix}posts (pid,fid,tid,aid,author,authorid,postdate,subject,userip,ifsign,ifconvert,content,ifcheck) VALUES (".$bbs->Fields['ID']->value.",".$bbs->Fields['BoardID']->value.",".$bbs->Fields['ParentID']->value.",'','".addslashes($bbs->Fields['UserName']->value)."','".$bbs->Fields['UserID']->value."','".$bbs_ndatetime."','".addslashes($bbs->Fields['Title']->value)."','".addslashes($bbs->Fields['IPAddress']->value)."',1,'".$ifconvert."','".addslashes($bbs_Content)."',1)");
		}
		else
		{
			$iflock = $bbs->Fields['NotReplay']->value;
			$DDB->update("REPLACE INTO {$pw_prefix}tmsgs (tid,aid,userip,ifsign,ifconvert,content) VALUES (".$bbs->Fields['ID']->value.",'".$aid."','".addslashes($bbs->Fields['IPAddress']->value)."',1,'".$ifconvert."','".addslashes($bbs_Content)."')");
			$DDB->update("UPDATE {$pw_prefix}threads Set locked = ".$iflock.",ifupload='".$ifupload."' Where tid=".$bbs->Fields['ID']->value);
		}
		$s_c++;
		$bbs->MoveNext();
	}
	$row = $SDB->query("SELECT COUNT(*) AS num FROM {$source_prefix}Announce WHERE ID >= $end");
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
elseif ($step == '6')
{
	//附件信息
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}attachs");
	}
	$a = $SDB->query("SELECT ID,UserID,PhotoDir,NdateTime,FileType,FileName,FileSize,announceid,boardid,Info,VisitIP,hits FROM {$source_prefix}Upload WHERE ID >= $start AND ID < $end");
	while (!$a->EOF)
	{
		if ($a->Fields['announceid']->value)
		{
			$FileType = $a->Fields['FileType']->value;
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
			$thread = $SDB->query("SELECT ParentID FROM {$source_prefix}Announce WHERE ID = ".$a->Fields['announceid']->value);

			if ($thread->EOF)
			{
				$f_c++;
				errors_log($a->Fields['ID']->value."\t".$a->Fields['FileName']->value);
				$a->MoveNext();
				continue;
			}
			$DDB->update("INSERT INTO {$pw_prefix}attachs (aid,fid,uid,tid,pid,name,type,size,attachurl,hits,uploadtime,descrip) VALUES ('".$a->Fields['ID']->value."','".$a->Fields['boardid']->value."','".$a->Fields['UserID']->value."','".($thread->Fields['ParentID']->value ? $thread->Fields['ParentID']->value : $a->Fields['announceid']->value)."','".($thread->Fields['ParentID']->value ? $a->Fields['announceid']->value : 0)."','".addslashes($a->Fields['FileName']->value)."','".$FileType."','".(ceil($a->Fields['FileSize']->value / 1024))."','".addslashes($a->Fields['PhotoDir']->value)."','".$a->Fields['hits']->value."','".dt2ut(RestoreTime($a->Fields['NdateTime']->value))."','".addslashes($a->Fields['Info']->value)."')");
			if ($thread->Fields['ParentID']->value)
			{
				$attable = 'posts';
				$add = ' pid = '.$a->Fields['announceid']->value;
			}
			else
			{
				$attable = 'tmsgs';
				$add = ' tid = '.$a->Fields['announceid']->value;
			}
			$attdata = array();
			$atta = $DDB->get_one("SELECT aid FROM {$pw_prefix}{$attable} WHERE".$add);
			if (!$atta) {$a->MoveNext();continue;}
			$atta['aid'] && $attdata = unserialize($atta['aid']);
			$attdata[$a['ID']] = array('aid'=>$a->Fields['ID']->value,'name'=>addslashes($a->Fields['FileName']->value),'type'=>$FileType,'attachurl'=>addslashes($a->Fields['PhotoDir']->value),'needrvrc'=>0,'size'=>round($a->Fields['FileSize']->value/1024),'hits'=>$a->Fields['hits']->value,'desc'=>addslashes($a->Fields['Info']->value),'ifthumb'=>0);
			$DDB->query("UPDATE {$pw_prefix}{$attable} SET aid = '".addslashes(serialize($attdata))."' WHERE".$add);
		}
		$a->MoveNext();
	}
	$row = $SDB->query("SELECT COUNT(*) AS num FROM {$source_prefix}Upload WHERE ID >= $end");
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
	//友情链接
	require_once S_P.'lang_'.$dest_charset.'.php';
	$DDB->query("TRUNCATE TABLE {$pw_prefix}sharelinks");
	$l = $SDB->query("SELECT * FROM {$source_prefix}Link");
	$ilink = '';
	while (!$l->EOF)
	{
		$ilink .= "('".$l->Fields['OrderID']->value."','".addslashes($l->Fields['SiteName']->value)."','".addslashes($l->Fields['SiteUrl']->value)."','".addslashes($l->Fields['LogoUrl']->value)."','',1),";
		
		$l->MoveNext();
	}
	$ilink .= $lang['link'];
	$ilink && $DDB->update("INSERT INTO {$pw_prefix}sharelinks (threadorder,name,url,logo,descrip,ifcheck) VALUES ".$ilink);
	report_log();
	newURL($step);
}

elseif ($step == '8')
{
	//头像
	$_avatar = array();
	$pw_avatar = R_P.'pwavatar';
	$dz_avatar = R_P.'face';
	if (!$start)
	{
		if (!is_dir($pw_avatar) || !N_writable($pw_avatar) || !is_readable($dz_avatar) || !N_writable($dz_avatar))
		{
			ShowMsg('用于转换头像的 upload 或者 pwavatar 目录不存在或者无法写入。<br /><br />1、请将 LeadBBS 安装目录Images/upload  下的 face 目录移动到 PWBuilder 根目录。<br /><br />2、在PWBuilder 根目录下建立一个名为：pwavatar 的目录，且设定权限为777。<br /><br />', true);
		}
		PWListDir($dz_avatar, $dirname);
		writeover(S_P.'tmp_avatar.php', "\$_avatar = ".pw_var_export($dirname).";", true);
	}
	require_once(S_P.'tmp_avatar.php');
	require_once(S_P.'tmp_head.php');
	if ($start >= count($_avatar))
	{
		report_log();
		newURL($step);
	}
	$dh = opendir($_avatar[$start]);
	while (($file = readdir($dh)) !== FALSE)
	{	
		$match = array();
		if ($file != '.' && $file != '..')
		{
			$img = explode('.',$file);
			if($img[1]=='jpg' || $img[1]=='gif' || $img[1]=='bmp' || $img[1]=='png' || $img[1]=='jpeg')
			{
				$uid = $_head[$file];
				$size = GetImgSize($_avatar[$start].'/'.$file);
				$savedir = str_pad(substr($uid,-2),2,'0',STR_PAD_LEFT);
				if (!is_dir($pw_avatar.'/'.$savedir))
				{
					@mkdir($pw_avatar.'/'.$savedir);
					@chmod($pw_avatar.'/'.$savedir,0777);
				}
				@copy($_avatar[$start].'/'.$file, $pw_avatar.'/'.$savedir.'/'.$uid.'.jpg');
				$DDB->update("UPDATE {$pw_prefix}members SET icon = '".$savedir.'/'.$uid.".jpg|3|".$size['width']."|".$size['height']."' WHERE uid = ".$uid);
				$s_c ++;
			}
		}
	}
	$end = ++$start;
	refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
}
elseif($step == '9')
{
	//短信
	if(!$start)
	{
		$DDB->update("TRUNCATE TABLE {$pw_prefix}msg");
		$DDB->update("TRUNCATE TABLE {$pw_prefix}msgc");
		$DDB->update("TRUNCATE TABLE {$pw_prefix}msglog");
	}
	$touid = $fromuid = '';
	$m = $SDB->query("SELECT * FROM {$source_prefix}InfoBox WHERE ID >= $start AND ID < $end");
	while(!$m->EOF)
	{
		if($m->Fields['ToUser']->value == '')
		{
			$f_c++;
			errors_log($m->Fields['ID']->value."\t".$m->Fields['title']->value);
			$m->MoveNext();
			continue;
		}
		
		if($m->Fields['FromUser']->value=='[LeadBBS]')
		{
			$touid = '0';
			$fromuid = '0';
			$type = 'public';
		}
		
		else
		{
			$tou = $DDB->get_one("Select uid From {$pw_prefix}members Where username='".$m->Fields['ToUser']->value."'");
			$from = $DDB->get_one("Select uid From {$pw_prefix}members Where username='".$m->Fields['FromUser']->value."'");
			$touid = $tou['uid'] ? $tou['uid'] : '0';
			$fromuid = $from['uid'] ? $from['uid'] : '0';
			$type    = 'rebox';
		}
		$postdatetime = dt2ut(RestoreTime($m->Fields['ExpiresDate']->value));
		
		$DDB->update("REPLACE INTO {$pw_prefix}msg (mid,touid,fromuid,username,type,ifnew,mdate) VALUES (".$m->Fields['ID']->value.",".$touid.",".$fromuid.",'".addslashes($m->Fields['FromUser']->value)."','".$type."','0','".$postdatetime."')");
		$DDB->update("REPLACE INTO {$pw_prefix}msgc (mid,title,content) VALUES (".$m->Fields['ID']->value.",'".addslashes($m->Fields['title']->value)."','".addslashes($m->Fields['Content']->value)."')");
		if (($m->Fields['FromUser']->value != $m->Fields['ToUser']->value) && ($type == 'rebox'))
		{
			$DDB->update("REPLACE INTO {$pw_prefix}msglog (mid,uid,withuid,mdate,mtype) VALUES (".$m->Fields['ID']->value.",".$fromuid.",".$touid.",'".$postdatetime."','send')");
			$DDB->update("REPLACE INTO {$pw_prefix}msglog (mid,uid,withuid,mdate,mtype) VALUES (".$m->Fields['ID']->value.",".$touid.",".$fromuid.",'".$postdatetime."','receive')");
		}
		$s_c++;
		$m->MoveNext();
	}
	$row = $SDB->query("SELECT COUNT(*) as num FROM {$source_prefix}InfoBox WHERE ID >= $end");
	if ($row->Fields['num']->value)
	{
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c.'&medal='.$medal);
	}
	else
	{
		report_log();
		newURL($step);
	}	
}
else
{
	ObHeader($basename.'?action=finish&dbtype='.$dbtype);
	exit;
}

function mkbd($v){
	return substr($v,0,4).'-'.substr($v,4,2).'-'.substr($v,6,2);
}

function lead_ubb($Content)
{
	$search = array('[UL]','[/UL]','[OL]','[/OL]','[LI]','[/LI]','[B]','[/B]','[i]','[/i]','[u]','[/u]','[ALIGN=','[/ALIGN]','[URL]','[URL=','[/URL]','[EMAIL=Mailto:','[EMAIL]','[/EMAIL]','[IMGA]','[/IMGA]','[IMG]','[/IMG]','[QUOTE]','[/QUOTE]','[Flash]','[/Flash]','[CODE]','[/CODE]','[FLY]','[/FLY]','[GLOW=','[/GLOW]','[COLOR=','[/COLOR]','[SIZE=','[/SIZE]','[/FACE]','[/RM]','[/MP]','[TABLE][TR][TD]',array('[PRE]','[/PRE]','[LIGHT]','[/LIGHT]','[SOUND]','[/SOUND]','[/FIELDSET]','[/BGCOLOR]','[STRIKE]','[HR]','[/STRIKE]'));
	$replace = array('[ul]','[/ul]','[ol]','[/ol]','[li]','[/li]','[b]','[/b]','[i]','[/i]','[u]','[/u]','[align=','[/align]','[url]','[url=','[/url]','[email=','[email]','[/email]','[img]','[/img]','[img]','[/img]','[quote]','[/quote]','[flash=314,256,0]','[/flash]','[code]','[/code]','[fly]','[/fly]','[glow=','[/glow]','[color=','[/color]','[size=','[/size]','[/font]','[/rm]','[/wmv]','[table][tr][td]','');
	$preg_search = array('/\[IMGA?=([,0-9a-z]+?)\]/i','/\[Flash?=(\d+?),(\d+?)\]/i','/\[FACE=(.+?)\]/i','/\[RM=(\d+?),(\d+?)\]/i','/\[MP=(\d+?),(\d+?)\]/i','/\[TABLE=(.+?)\]/i','/\[FIELDSET=(.+?)\]/i','/\[BGCOLOR=(.+?)\]/i');
	$preg_replace = array('[img]','[flash=\\2,\\1]','[font=\\1]','[rm=\\2,\\1,0]','[wmv=\\2,\\1,0]','[table=100%]','','');
	$Content = preg_replace($preg_search,$preg_replace,str_replace($search,$replace,$Content));
	$Content = preg_replace('~\[upload=(\d+?),\d+?].+?\[/upload\]~i','[attachment=\\1]',$Content);
	return $Content;
}

function PWListDir($root, &$dirname)
{
	$real = true;
	$rs = opendir($root);
	while (($file = readdir($rs)) !== FALSE)
	{
		$tmp = $root.'/'.$file;
		if ($file != '..' && $file != '.' && is_dir($tmp))
		{
			$real = false;
			PWListDir($tmp, $dirname);
		}
	}
	$real && $dirname[] = $root;
	closedir($rs);
	return;
}
function GetImgSize($srcFile){
	$srcdata = array();
	if (function_exists('read_exif_data')) {
		$datatemp = @read_exif_data($srcFile);
		$srcdata['width'] = $datatemp['COMPUTED']['Width'];
		$srcdata['height'] = $datatemp['COMPUTED']['Height'];
		unset($datatemp);
	}
	!$srcdata['width'] && list($srcdata['width'],$srcdata['height'],) = @getimagesize($srcFile);
	return $srcdata;
}


function RestoreTime($DateString) //leadbbs50 时间转换
{
	if (strlen($DateString) < 8)
	{
		return $DateString;
	}
	else
	{
		if (strlen($DateString) < 14)
		{
			$DateString = substr($DateString,0,4) . '-' . substr($DateString,4,2) .'-' . substr($DateString,6,2);
			return $DateString;
		}
		else
		{
			$DateString = substr($DateString,0,4) . '-' . substr($DateString,4,2) .'-' . substr($DateString,6,2) . ' '.substr($DateString,8,2).':'.substr($DateString,10,2) .':'.substr($DateString,12,2);
			return $DateString;
		}
	}
}

?>