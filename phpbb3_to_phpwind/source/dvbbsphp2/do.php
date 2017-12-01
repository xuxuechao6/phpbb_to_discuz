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
if ($pwsamedb)
{
	$SDB = &$DDB;
}
else
{
	$charset_change = 1;
	$SDB = new mysql($source_db_host, $source_db_user, $source_db_password, $source_db_name, '');
}

if ($step == '1')
{

	//配置
	$s = $SDB->get_one("SELECT * FROM {$source_prefix}setup");
	$chansetting = explode(',', $s['forum_chansetting']);
	$DDB->update("UPDATE {$pw_prefix}config SET db_value = '".intval($chansetting[14])."' WHERE db_name = 'db_rmbrate'");
	$DDB->update("UPDATE {$pw_prefix}config SET db_value = '".addslashes($chansetting[4])."' WHERE db_name = 'ol_payto'");
	$DDB->update("UPDATE {$pw_prefix}config SET db_value = '".(intval($chansetting[3]) ^ 1)."' WHERE db_name = 'ol_onlinepay'");

	$setting = explode('|', $s['forum_setting']);

	$setting_38 = explode(',', $setting[38]);
	$setting_6  = explode(',', $setting[6]);
	$setting_0  = explode(',', $setting[0]);

	$DDB->update("UPDATE {$pw_prefix}config SET db_value = '".addslashes($setting[79])."' WHERE db_name = 'db_whybbsclose'");
	$DDB->update("UPDATE {$pw_prefix}config SET db_value = '".addslashes($setting_0[0])."' WHERE db_name = 'db_bbsname'");
	$DDB->update("UPDATE {$pw_prefix}config SET db_value = '".addslashes($setting_0[5])."' WHERE db_name = 'db_ceoemail'");
	$DDB->update("UPDATE {$pw_prefix}config SET db_value = '".addslashes($setting_0[1])."' WHERE db_name = 'db_bbsurl'");
	$DDB->update("UPDATE {$pw_prefix}config SET db_value = 1 WHERE db_name IN ('db_topped','db_gdcheck')");
	$DDB->update("UPDATE {$pw_prefix}config SET db_value = 600 WHERE db_name = 'db_signheight'");
	$DDB->update("UPDATE {$pw_prefix}config SET db_value = 3 WHERE db_name = 'db_attachdir'");

	$DDB->update("UPDATE {$pw_prefix}bbsinfo SET newmember = '".addslashes($s['forum_lastuser'])."', totalmember = '".intval($s['forum_usernum'])."', higholnum = '".intval($s['forum_maxonline'])."', higholtime = '".dt2ut($s['forum_maxonlinedate'])."', yposts = '".intval($s['forum_yesterdaynum'])."', hposts = '".intval($s['forum_maxpostnum'])."' WHERE id = 1");

	$_pwface = $_dvface = array();

	if ($s['forum_badwords'])
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}wordfb");
		$badwords = explode('|', $s['forum_badwords']);
		foreach ($badwords as $v)
		{
			$DDB->update("INSERT INTO {$pw_prefix}wordfb (word,wordreplace,type) VALUES ('".$v."','',1)");
		}
	}
	$s_c = 15;
	report_log();
	newURL($step);
}
if ($step == '2')
{
	//用户
	require_once S_P.'tmp_credit.php';
	$_specialdata = $insertadmin = '';
	if (!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}members");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}memberdata");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}membercredit");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}administrators");
		$DDB->query("DELETE FROM {$pw_prefix}credits WHERE cid > 1");
		$DDB->query("ALTER TABLE {$pw_prefix}credits AUTO_INCREMENT = 2");
		foreach ($expandCredit as $v)
		{
			$DDB->update("INSERT INTO {$pw_prefix}credits (name,unit) VALUES ('".addslashes($v[0])."','".addslashes($v[1])."')");
		}
		writeover(S_P.'tmp_specialdata.php', "\$_specialdata = ".pw_var_export(changegroups()).";", true);
	}

	require_once (S_P.'tmp_specialdata.php');
	$query = $SDB->query("SELECT * FROM {$source_prefix}User WHERE UserID >= $start AND UserID < $end");
	while ($r=$SDB->fetch_array($query))
	{
		$UserName = addslashes($r['UserName']);

		if (htmlspecialchars($UserName) != $UserName || CK_U($UserName))
		{
			$f_c++;
			errors_log($r['UserID']."\t".$r['UserName']);
			continue;
		}
		$UserID = (int)$r['UserID'];

		switch ($r['UserGroupID'])
		{
			case '1'://管理员
				$groupid = '3';
				$insertadmin .= "(".$UserID.", '".$UserName."', 3),";
				break;
			case '2'://总版主
				$groupid = '4';
				$insertadmin .= "(".$UserID.", '".$UserName."', 4),";
				break;
			case '3'://版主
				$groupid = '5';
				$insertadmin .= "(".$UserID.", '".$UserName."', 5),";
				break;
			default :
				$groupid = isset($_specialdata[$r['UserGroupID']]) ? $r['UserGroupID'] : '-1';
				break;
		}
		if ($r['LockUser'] == 2 || $r['LockUser'] == 1)
		{
			$groupid = 6;
		}
		$UserFace = $r['UserFace'];
		if ($UserFace)
		{
			$offset = intval(strpos($UserFace, '|'));
			$offset && $offset += 1;
			if (strtolower(substr($UserFace,0+$offset,7)) == 'http://')
			{
				$UserFace = $UserFace.'|2|'.$r['UserWidth'].'|'.$r['UserHeight'].'|';
			}
			elseif(strtolower(substr($UserFace,0+$offset,10)) == 'uploadface')
			{
				$UserFace = substr($UserFace,11+$offset).'|3|'.$r['UserWidth'].'|'.$r['UserHeight'].'|';
			}
			elseif ((strtolower(substr($UserFace,0+$offset,6)) == 'images'))
			{
	        	$UserFace = substr($UserFace, 16+$offset).'|1|||';
	        }
		}
		$JoinDate = dt2ut($r['JoinDate']);
		$LastLogin = dt2ut($r['LastLogin']);
		$UserBirthday = $r['UserBirthday'] ? $r['UserBirthday'] : '0000-00-00';
		eval($creditdata);

		$expandCreditSQL = '';
		if($expandCredit)
		{
			foreach ($expandCredit as $k => $v)
			{
				$expandCreditSQL .= '('.$r['userid'].','.($k + 2).','.(int)($r[$v[2]]).'),';
			}
			$expandCreditSQL && $DDB->update("INSERT INTO {$pw_prefix}membercredit (uid, cid, value) VALUES ".substr($expandCreditSQL, 0, -1));
		}
		$UserSex = $r['UserSex'];
		!$UserSex && $UserSex = 2;
		$signchange = (convert($r['UserSign']) == $r['UserSign']) ? 1 : 2;
		$UserIM = explode('|||', $r['UserIM']);
		$UserInfo = explode('|||', $r['UserInfo']);

		$userstatus=($signchange-1)*256+128+1*64+4;//用户位状态设置

		$DDB->update("REPLACE INTO {$pw_prefix}members (uid,username,password,email,groupid,icon,gender,regdate,signature,introduce,oicq,icq,msn,yahoo,site,bday,yz,userstatus) VALUES (".$UserID.",'".$UserName."','".$r['UserPassword']."','".addslashes($r['UserEmail'])."','".$groupid."','".$UserFace."','".$UserSex."','".$JoinDate."','".addslashes($r['UserSign'])."','".addslashes($UserInfo[2])."','".$UserIM[1]."','".$UserIM[2]."','".$UserIM[3]."','".$UserIM[4]."','".$UserIM[0]."','".$UserBirthday."',1,'".$userstatus."')");

		$DDB->update("REPLACE INTO {$pw_prefix}memberdata (uid,postnum,digests,rvrc,money,credit,currency,lastvisit,thisvisit) VALUES (".$UserID.",".(int)$r['UserPost'].",".(int)$r['UserIsBest'].",".$rvrc.",".$money.",".$credit.",".$currency.",'".$LastLogin."','".$LastLogin."')");

		$s_c++;
	}
	$insertadmin && $DDB->update("REPLACE INTO {$pw_prefix}administrators (uid,username,groupid) VALUES ".substr($insertadmin, 0, -1));
	$maxid = $SDB->get_value("SELECT max(userid) FROM {$source_prefix}user");
	if ($maxid>$start)
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
	//友情链接
	require_once S_P.'lang_'.$dest_charset.'.php';
	$DDB->query("TRUNCATE TABLE {$pw_prefix}sharelinks");
	$query= $SDB->query("SELECT id,boardname,readme,url,logo,islogo FROM {$source_prefix}BbsLink");
	$ilink = '';
	while ($r=$SDB->fetch_array($query))
	{
		$ilink .= "('".addslashes($r['boardname'])."','".addslashes($r['url'])."','".addslashes($r['readme'])."','".addslashes($r['logo'])."',1),";
		$s_c++;
	}
	$ilink .= $lang['link'];
	$DDB->update("REPLACE INTO {$pw_prefix}sharelinks (name,url,descrip,logo,ifcheck) VALUES ".$ilink);
	report_log();
	newURL($step);
}
elseif ($step == '4')
{
	//版块信息
	$forumdb = $catedb = array();
	$DDB->query("TRUNCATE TABLE {$pw_prefix}forums");
	$DDB->query("TRUNCATE TABLE {$pw_prefix}forumdata");
	$query= $SDB->query("SELECT * FROM {$source_prefix}Board");

	$insertforum = $insertforumdb = '';

	while($r=$SDB->fetch_array($query))
	{
		$catedb[$r['boardid']] = array('boardid'=>$r['boardid'],'BoardType'=>$r['BoardType'],'ParentID'=>$r['ParentID'],'ParentStr'=>$r['ParentStr'],'Depth'=>$r['Depth'],'RootID'=>$r['RootID'],'Child'=>$r['Child'],'orders'=>$r['orders'],'readme'=>$r['readme'],'BoardMaster'=>$r['BoardMaster'],'Postnum'=>$r['Postnum'],'TopicNum'=>$r['TopicNum'],'indexIMG'=>$r['indexIMG'],'todayNum'=>$r['todayNum'],'LastPost'=>$r['LastPost'],'sid'=>$r['sid'],'Board_Setting'=>$r['Board_Setting']);
	}
	foreach ($catedb as $k => $v)
	{
		$f_tmp = parent_upfid($v['boardid'],'ParentID',0);

		$v_Child = (int)parent_ifchildid($v['boardid'],'ParentID');
		$ifsub = ($f_tmp[0] == 'sub') ? 1 : 0;
		$ftype = $f_tmp[0];
		$v_BoardMaster = $v['BoardMaster'] ? (',' . (str_replace('|',',',$v['BoardMaster'])) . ',') : '';
		$upadmin = '';
		getupadmin($k, $upadmin);

		$fset = explode(',', $v['Board_Setting']);
		$insertforum .= "(".(int)$k.",".(int)$f_tmp[1].",".$ifsub.",".$v_Child.",'".$ftype."','".addslashes($v['indexIMG'])."','".addslashes($v['BoardType'])."','".addslashes($v['readme'])."','".$v['orders']."','".addslashes($v_BoardMaster)."','".addslashes($upadmin)."','".$fset[41]."'),";
		$insertforumdb .= "(".(int)$k.",'".$v['todayNum']."','".$v['Postnum']."','".$v['TopicNum']."'),";
		$s_c++;
	}

	$insertforum && $DDB->update("INSERT INTO {$pw_prefix}forums (fid,fup,ifsub,childid,type,logo,name,descrip,vieworder,forumadmin,fupadmin,across) VALUES ".substr($insertforum, 0, -1));

	$insertforumdb && $DDB->update("INSERT INTO {$pw_prefix}forumdata (fid,tpost,topic,article) VALUES ".substr($insertforumdb, 0, -1));
	report_log();
	newURL($step);
}
elseif ($step == '5')
{
	//附件信息
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}attachs");
	}
	$query = $SDB->query("SELECT * FROM {$source_prefix}Upfile WHERE F_ID >= $start AND F_ID < $end");
	while ($r=$SDB->fetch_array($query))
	{
		if ($r['F_AnnounceID'])
		{
			$F_AddTime = dt2ut($r['F_AddTime']);
			$hits= $a['F_DownNum'] + $r['F_ViewNum'];
			$F_FileType = strtolower($r['F_FileType']);
			switch ($F_FileType)
			{
				case 'jpg':
				case 'gif':
				case 'png':
				case 'bmp':
					$type = 'img';
					break;
				case 'txt':
					$type = 'txt';
					break;
				default:
					$type = 'zip';
					break;
			}
			$F_AnnounceID = explode('|', $r['F_AnnounceID']);
			$size = ceil($r['F_FileSize'] / 1024);
			if ('' != $r['F_OldName'])
			{
				$F_name = addslashes($r['F_OldName']);
			}
			else
			{
				$F_name = addslashes($r['F_Filename']);
			}

			$DDB->update("REPLACE INTO {$pw_prefix}attachs (aid,fid,uid,tid,pid,name,type,size,attachurl,hits,uploadtime) VALUES ('".$r['F_ID']."','".$r['F_BoardID']."','".$r['F_UserID']."','".$F_AnnounceID[0]."','".$F_AnnounceID[1]."','".$F_name."','".$type."','".$size."','".addslashes($r['F_Filename'])."','".$hits."','".$F_AddTime."')");
		}
		else
		{
			$f_c++;
			errors_log($r['F_ID']."\t".$r['F_Filename']);
		}
		$s_c++;
	}
	$maxid = $SDB->get_value("SELECT max(f_id) FROM {$source_prefix}upfile");
	if ($maxid>$start)
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
	//主题信息
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}threads");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}tmsgs");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}posts");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}polls");
	}
	$query= $SDB->query("SELECT * FROM {$source_prefix}Topic WHERE TopicID >= $start AND TopicID < $end");

	while ($r=$SDB->fetch_array($query))
	{
		if (!$r['BoardID'])
		{
			$f_c++;
			errors_log($r['TopicID']."\t".$r['Title']);
			continue;
		}
		$titlefont = '';
		$t_DateAndTime = dt2ut($r['DateAndTime']);
		$t_LastPostTime = dt2ut($r['LastPostTime']);
		$t_Expression = $r['Expression'];
		$t_Expression && $t_Expression = getface($r['Expression']);
		$t_Expression = $t_Expression ? $t_Expression : 0;

		$t_Title = strip_tags($r['Title']);
		$t_LastPost = explode('$', $r['LastPost']);
		$t_istop = $r['istop'];
		if($t_istop == 2) $t_istop = 3;
		if ($r['TopicMode'])
		{
			switch ($r['TopicMode'])
			{
				case '2':
					$titlefont = 'red';
					break;
				case '3':
					$titlefont = 'blue';
					break;
				case '4':
					$titlefont = 'green';
					break;
			}
			$titlefont = $titlefont.'~~~~~~';
		}
		if ($r['IsSmsTopic']=='2')
		{
			$special = '4';
		}
		elseif ($r['isvote'])
		{
			$special = '1';
		}
		else
		{
			$special = '0';
		}
		$result = $SDB->query("SELECT * FROM ".$r['PostTable']." WHERE RootID = ".$r['TopicID']);
		while ($bbs=$SDB->fetch_array($result))
		{
			$aid = '';
			$ifupload = 0;
			$attdata = array();
			$bbs_locktopic = $bbs['locktopic'] ? '1' : '0';
			$bbs_DateAndTime = dt2ut($bbs['DateAndTime']);
			$bbs_Body = $bbs['Body'];

			if(preg_match('~\[upload=(?:jpg|gif|jpeg|bmp)[^]]*?\]uploadfile([^]]+?)\[\/upload\]~i',$bbs_Body))
			{
				$ifupload = 1;
			}
			$bbs_Body = preg_replace(array('~\[upload=(?:jpg|gif|jpeg|bmp)[^]]*?\]uploadfile([^]]+?)\[\/upload\]~i','~\[upload=.*?\].*?\[\/upload\]~is'),array('[img]attachment\\1[/img]',''),$bbs_Body);
			$qa = $DDB->query("SELECT * FROM {$pw_prefix}attachs WHERE tid = '".$bbs['RootID']."' AND pid = '".$bbs['AnnounceID']."' AND type <>'img' And type <>'bmp' And type <>'jpg' And type <>'gif'");
			while ($a = $DDB->fetch_array($qa))
			{
				$attdata[$a['aid']] = array('aid'=>$a['aid'],'name'=>$a['name'],'type'=>$a['type'],'attachurl'=>$a['attachurl'],'needrvrc'=>0,'size'=>$a['size'],'hits'=>$a['hits'],'desc'=>'','ifthumb'=>0);
				if($a['type']=='zip') {$ifupload = 3;}
				if($a['type']=='txt') {$ifupload = 2;}
			}
			if(count($attdata) > 0)
			{
				$aid = addslashes(serialize($attdata));
			}


			$bbs_Body = dvbbs_ubb($bbs_Body);
			if ($bbs['GetMoney'] && $bbs['GetMoneyType'] == 3)
			{
				$bbs_Body = '[sell='.(int)$bbs['GetMoney'].',money]'.$bbs_Body.'[/sell]';
			}
			$ifconvert = (convert($bbs_Body) == $bbs_Body) ? 1 : 2;
			if ($bbs['ParentID'])
			{
				$DDB->update("REPLACE INTO {$pw_prefix}posts (aid,fid,tid,author,authorid,postdate,subject,userip,ifsign,ifconvert,ifcheck,content,ifshield) VALUES ('".$aid."',".$bbs['BoardID'].",".$bbs['RootID'].",'".addslashes($bbs['UserName'])."','".$bbs['postuserid']."','".$bbs_DateAndTime."','".addslashes(@strip_tags($bbs['Topic']))."','".addslashes($bbs['ip'])."',1,'".$ifconvert."',".((int)$bbs['isaudit']^1).",'".addslashes($bbs_Body)."','".$bbs_locktopic."')");
				$bbs['isupload'] && $DDB->update("UPDATE {$pw_prefix}attachs SET pid = ".$DDB->insert_id()." WHERE tid = '".$bbs['RootID']."' AND pid = '".$bbs['AnnounceID']."'");
			}
			else
			{
				$ifshield = $bbs_locktopic;
				$ifcheck = ((int)$bbs['isaudit']^1);
				if ($special == '4')
				{
					preg_match_all('/\((seller|subject|body|price|demo|ww|qq)\)(.+?)\(\/\\1\)/is', $bbs_Body, $m);
					$bbs_Body = '[payto]'.$m[0][0].$m[0][1].strip_tags($m[0][2]).$m[0][3].'(ordinary_fee)0(/ordinary_fee)(express_fee)0(/express_fee)(contact)'.$m[2][5].'(/contact)'.$m[0][4].'(method)4(/method)[/payto]';
					$ifconvert = '2';
				}
				$DDB->update("REPLACE INTO {$pw_prefix}tmsgs (tid,aid,userip,ifsign,ifconvert,content) VALUES (".$bbs['RootID'].",'".$aid."','".addslashes($bbs['ip'])."',1,'".$ifconvert."','".addslashes($bbs_Body)."')");
				$bbs['isupload'] && $DDB->update("UPDATE {$pw_prefix}attachs SET pid = 0 WHERE tid = '".$bbs['RootID']."' AND pid = '".$bbs['AnnounceID']."'");
			}
			$s_c++;
		}
		$bbs =  NULL;

		$ifshield = $ifshield ? $ifshield : 0;
		$ifupload = $ifupload ? $ifupload : 0;

		$DDB->update("REPLACE INTO {$pw_prefix}threads (tid,fid,icon,titlefont,author,authorid,subject,ifcheck,postdate,lastpost,lastposter,hits,replies,topped,locked,digest,special,ifshield,ifupload) VALUES (".$r['TopicID'].",".$r['BoardID'].",'".$t_Expression."','".$titlefont."','".addslashes($r['PostUserName'])."',".(int)$r['PostUserID'].",'".addslashes(@strip_tags($t_Title))."','".$ifcheck."','".$t_DateAndTime."','".$t_LastPostTime."','".addslashes($t_LastPost[0])."',".$r['hits'].",".$r['Child'].",".$t_istop.",".$r['LockTopic'].",".$r['isbest'].",".$special.",'".$ifshield."',$ifupload)");
		if ($r['isvote'] && $r['PollID'])
		{
			$result1 = $SDB->query("SELECT * FROM {$source_prefix}Vote WHERE voteid = ".$r['PollID']);
			if ($vote=$SDB->fetch_array($result1))
			{
				$voteoptions = array();
				$voteitem = explode('|',$vote['vote']);
				$votenum = explode('|',$vote['votenum']);
				foreach ($voteitem as $k => $i)
				{
					$voteoptions[$k][0] = $i;
					$voteoptions[$k][1] = $votenum[$k];
				}
				$query2 = $SDB->query("SELECT * FROM {$source_prefix}VoteUser WHERE VoteID = ".(int)$vote['voteid']);
				while ($vt=$SDB->fetch_array($query2))
				{
					$username = $DDB->get_one("SELECT username FROM {$pw_prefix}members WHERE uid = ".$vt['UserID']);
					if ($username)
					{
						$tvid = explode(',', $vt['VoteOption']);
						foreach ($tvid as $n)
						{
							$n && $voteoptions[$n][2][] = $username['username'];
						}
					}
				}
				$DDB->update("REPLACE INTO {$pw_prefix}polls (tid,voteopts,previewable,timelimit) VALUES ('".$r['TopicID']."','".addslashes(serialize(array('options'=>$voteoptions,'multiple'=>array($vote['votetype'],count($voteitem)))))."',1,".ceil((dt2ut($vote['TimeOut']) - $t_DateAndTime)/86400).")");
			}
		}
		$s_c++;
	}
	$maxid = $SDB->get_value("SELECT max(TopicID) FROM {$source_prefix}Topic");
	if ($maxid>$start)
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
	//公告信息
	$DDB->query("TRUNCATE TABLE {$pw_prefix}announce");
	$result = $SDB->query("SELECT * FROM {$source_prefix}BbsNews");

	while ($r=$SDB->fetch_array($result))
	{
		$addtime = dt2ut($r['addtime']);
		$boardid = (int)$r['boardid'];
		!$boardid && $boardid = '-1';
		$content = dvbbs_ubb($r['content']);
		$DDB->update("replace INTO {$pw_prefix}announce (aid,fid,ifopen,author,startdate,subject,content,ifconvert) VALUES (".$r['id'].",".$boardid.",1,'".addslashes($a['username'])."','".addslashes($addtime)."','".addslashes($r['title'])."','".addslashes($content)."',".((convert($content) == $content) ? 0 : 1).")");
		$s_c++;
	}
	report_log();
	newURL($step);
}
elseif ($step == '8')
{
	//论坛短信
	if(!$start)
	{
        $DDB->update("TRUNCATE TABLE {$pw_prefix}ms_messages");
        $DDB->update("TRUNCATE TABLE {$pw_prefix}ms_relations");
        $DDB->update("TRUNCATE TABLE {$pw_prefix}ms_replies");
	}
    $message_sql = $relations_sql = $replies_sql = array();
	$query = $SDB->query("SELECT * FROM {$source_prefix}message WHERE id >= $start AND id < $end");
	while($m=$SDB->fetch_array($query))
	{
		if (!$m['incept'] || !$m['sender'] || !$m['content'])
		{
			$f_c++;
			errors_log($m['id']."\t".$m['title']."\r");
			continue;
		}
		$touid = $DDB->get_one("SELECT uid FROM {$pw_prefix}members WHERE username = '".addslashes($m['incept'])."'");
		$fromuid = $DDB->get_one("SELECT uid FROM {$pw_prefix}members WHERE username = '".addslashes($m['sender'])."'");
		if ($touid && $fromuid)
		{
			//6.3.2
			$sendtime = dt2ut($m['sendtime']);
			if (!$m['delR'] && !$m['delS'])
			{
                /*
				$DDB->update("INSERT INTO {$pw_prefix}msg (mid,touid,fromuid,username,type,ifnew,mdate) VALUES (".$m['id'].",".$touid['uid'].",".$fromuid['uid'].",'".($m['isSend'] ? addslashes($m['sender']) : addslashes($m['incept']))."','".($m['isSend'] ? 'rebox' : 'sebox')."',".($m['flag']^1).",'".$sendtime."')");
				$DDB->update("INSERT INTO {$pw_prefix}msgc (mid,title,content) VALUES (".$m['id'].",'".addslashes($m['title'])."','".addslashes($m['content'])."')");
				if (($touid['uid'] != $fromuid['uid']) && $m['isSend'])
				{
					$DDB->update("INSERT INTO {$pw_prefix}msglog (mid,uid,withuid,mdate,mtype) VALUES (".$m['id'].",".$fromuid['uid'].",".$touid['uid'].",'$sendtime','send')");
					$DDB->update("INSERT INTO {$pw_prefix}msglog (mid,uid,withuid,mdate,mtype) VALUES (".$m['id'].",".$touid['uid'].",".$fromuid['uid'].",'$sendtime','receive')");
					$s_c++;
				}*/

	        $message_sql[] = "('".$m['id']."',".$fromuid['uid'].",'".addslashes($m['sender'])."','".addslashes($m['title'])."','".addslashes($m['content'])."','".serialize(array('categoryid'=>1,'typeid'=>100))."',".$sendtime.",".$sendtime.",'".serialize(array(addslashes($m['incept'])))."')";
	        $replies_sql[] = "('".$m['id']."',".$m['id'].",'".$fromuid['uid']."','".addslashes($m['sender'])."','".addslashes($m['title'])."','".addslashes($m['content'])."','1',".$sendtime.",".$sendtime.")";

            $userIds = "";
	        $userIds = array($touid['uid'],$fromuid['uid']);
	        foreach($userIds as $otherId){
	            $relations_sql[] = "(".$otherId.",'".$m['id']."','1','100','0',".(($otherId == $fromuid['uid']) ? 1 : 0).",".$sendtime.",".$sendtime.")";
            }
            $s_c++;
			}
		}
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
	$maxid = $SDB->get_value("SELECT max(id) FROM {$source_prefix}message");
	if ($maxid>$start)
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
	//论坛好友
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}friends");
	}
	$query = $SDB->query("SELECT F_id,F_friend,F_addtime,F_Mod,F_userid FROM {$source_prefix}friend WHERE f_id >= $start AND f_id < $end");
	while($f=$SDB->fetch_array($query))
	{
		if ($f['F_Mod']!=2)
		{
			$friendid = $DDB->get_one("SELECT uid FROM {$pw_prefix}members WHERE username = '".$f['F_friend']."'");
			if ($friendid)
			{
				$f_addtime_friend = dt2ut($f['F_addtime']);
				if ('' ==  $f_addtime_friend)
				{
					$f_addtime_friend = 0;
				}

				$DDB->update("INSERT INTO {$pw_prefix}friends (uid,friendid,status,joindate) VALUES (".$f['F_userid'].",".$friendid['uid'].",0,".$f_addtime_friend.")");
				$s_c++;
			}
		}
	}
	$maxid = $SDB->get_value("SELECT max(f_id) FROM {$source_prefix}friend");
	if ($maxid>$start)
	{
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
		report_log();
		newURL($step);
	}
}
elseif ($step == '10')
{
	//朋友圈
	if (!$start && !$substep)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}cnclass");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}cmembers");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}cnalbum");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}cnphoto");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}colonys");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}argument");
	}
	switch ($substep)
	{
		case 1:
			//圈子
			require_once S_P.'tmp_colonys.php';
			$query = $SDB->query("SELECT * FROM {$source_prefix}groupname");
			while ($g=$SDB->fetch_array($query))
			{
				$DDB->update("INSERT INTO {$pw_prefix}colonys (id,classid,cname,admin,members,ifcheck,ifopen,albumopen,cmoney,cnimg,createtime,intomoney,annouce,albumnum,annoucesee,descrip) VALUES (".$g['id'].",".($_colonys[$g['id']] ? $_colonys[$g['id']] : 1).",'".addslashes($g['GroupName'])."','".addslashes($g['AppUserName'])."','".$g['UserNum']."','".($g['Locked'] ^ 1)."','".($g['viewflag'] ^ 1)."',1,0,'','".strtotime($g['AppDate'])."',0,'',0,1,'".addslashes($g['GroupInfo'])."')");
				$s_c++;
			}
			refreshto($cpage.'&step='.$step.'&start=0&f_c='.$f_c.'&s_c='.$s_c.'&substep=2');
			break;
		case 2:
			//主题
			$query2 = $SDB->query("SELECT * FROM {$source_prefix}group_topic WHERE topicid >= $start AND topicid < $end");
			while ($t=$SDB->fetch_array($query2))
			{
				$result = $SDB->query("SELECT * FROM {$source_prefix}group_bbs1 WHERE rootid = ".$t['topicid']);
				while ($bbs=$SDB->fetch_array($result))
				{
					$DDB->update("INSERT INTO {$pw_prefix}argument (tid,tpcid,gid,author,authorid,postdate,lastpost,topped,toppedtime,subject,content) VALUES ('".$bbs['announceid']."','".$bbs['parentid']."','".$t['groupid']."','".addslashes($bbs['username'])."',".$bbs['postuserid'].",'".$t['dateandtime']."','".$t['lastposttime']."',".$t['istop'].",0,'".addslashes($t['title'])."','".addslashes($bbs['body'])."')");
					$s_c++;
				}
			}
			$row = $SDB->query("SELECT COUNT(*) AS num FROM {$source_prefix}group_topic WHERE topicid >= $end");
			if ($row['num'])
			{
				refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c.'&substep=2');
			}
			else
			{
				refreshto($cpage.'&step='.$step.'&start=0&f_c='.$f_c.'&s_c='.$s_c.'&substep=3');
			}
			break;
		case 3:
			//成员
			$result2 = $SDB->query("SELECT groupid,userid,username,islock,intro FROM {$source_prefix}group_user WHERE id >= $start AND id < $end");
			while ($m=$SDB->fetch_array($result2))
			{
				$DDB->update("INSERT INTO {$pw_prefix}cmembers (uid,username,ifadmin,colonyid,introduce) VALUES ('".$m['userid']."','".addslashes($m['UserName'])."',".($m['islock'] == 2 ? 1 : 0).",".$m['groupid'].",'".addslashes($m['intro'])."')");
				$s_c++;
			}
			$row = $SDB->query("SELECT COUNT(*) AS num FROM {$source_prefix}group_user WHERE id >= $end");
			if ($row['num'])
			{
				refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c.'&substep=3');
			}
			else
			{
				report_log();
				newURL($step);
			}
			break;
		default:
			//分类
			$_colonys = array();
			$result4 = $SDB->query("SELECT id, rootid, boardname FROM {$source_prefix}group_board");
			while ($b=$SDB->fetch_array($result4))
			{
				$n = $SDB->query("SELECT COUNT(topicid) as num FROM {$source_prefix}group_topic Where boardid = ".$b['id']);
				$DDB->update("INSERT INTO {$pw_prefix}cnclass (cid,cname,cnsum) VALUES (".$b['id'].",'".addslashes($b['boardname'])."','".$n['num']."')");
				$_colonys[$b['rootid']] = $b['id'];
			}
			writeover(S_P.'tmp_colonys.php', "\$_colonys = ".pw_var_export($_colonys).";", true);
			refreshto($cpage.'&step='.$step.'&substep=1');
			break;
	}
}
elseif ($step == '11')
{
	//收藏
	if (!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}favors");
	}

	$query = $SDB->query("SELECT id,username,url FROM {$source_prefix}bookmark WHERE id >= $start AND id < $end");

	while ($f=$SDB->fetch_array($query))
	{
		$uid = $DDB->get_one("SELECT uid FROM {$pw_prefix}members WHERE username = '".addslashes($f['username'])."'");
		if ($uid)
		{
			$favorite = $DDB->get_one("SELECT tids FROM {$pw_prefix}favors WHERE uid = ".$uid['uid']);
			$inserttid = intval(substr($f['url'],strrpos($f['url'], '=')+1));
			$favorite ? $DDB->update("UPDATE {$pw_prefix}favors SET tids = CONCAT_WS(',', tids, '".$inserttid."') WHERE uid = ".$uid['uid']) : $DDB->update("INSERT INTO {$pw_prefix}favors (uid,tids) VALUES (".$uid['uid'].",$inserttid)");
			$s_c++;
		}
		else
		{
			$f_c++;
			errors_log($f['username']);
			continue;
		}
	}
	$maxid = $SDB->get_value("SELECT max(id) FROM {$source_prefix}bookmark");
	if ($maxid>$start)
	{
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
		report_log();
		newURL($step);
	}
}
else
{
	copy(S_P."tmp_report.php",S_P."report.php");//复制一份文件
	ObHeader($basename.'?action=finish&dbtype='.$dbtype);
}
##########################

function getface($face)
{
	$t = explode('|', $face);
	return str_replace('face', '', substr($t[1], 0, strrpos($t[1], '.')));
}
function getupadmin($cid, &$upadmin)
{
	global $catedb;
	if ($catedb[$cid]['BoardMaster'])
	{
		$BoardMaster = explode('|', $catedb[$cid]['BoardMaster']);
		foreach($BoardMaster as $value)
		{
			$upadmin .= $upadmin ? addslashes($value).',' : ','.addslashes($value).',';
		}
	}
	if ($catedb[$cid] && $catedb[$cid]['Depth'])
	{
		getupadmin($catedb[$cid]['ParentID'], $upadmin);
	}
}
function changegroups()
{
	global $pw_prefix, $source_prefix, $SDB, $DDB, $dest_charset;
	require_once S_P.'lang_'.$dest_charset.'.php';

	$DDB->update("TRUNCATE TABLE {$pw_prefix}usergroups");

	$DDB->update($lang['group']);

	$result = $SDB->query("SELECT * FROM {$source_prefix}UserGroups WHERE ParentGID IN (2,3)");
	$_specialdata = array();
	$gptype = '';
	while (!$g=$SDB->fetch_array($result))
	{
		$gid = $g['UserGroupID'];
		if ($g['ParentGID'] == 3)
		{
			$gptype = 'member';
			$grouppost = (int)$g['MinArticle'];
		}
		else
		{
			$gptype = 'special';
			$_specialdata[$g['UserGroupID']] = 1;
			$grouppost = 0;
		}

		$grouptitle = addslashes($g['usertitle']);

		$groupimg = substr($g['GroupPic'], 0, strrpos($g['GroupPic'], '.'));
		$groupsetting = explode(',', $g['GroupSetting']);

		$maxmsg = (int)$groupsetting[35];
		$allowhide = 1;
		$allowread = (int)$groupsetting[0];
		$allowportait = 1;
		$upload = 1;
		$allowrp = $groupsetting[5];
		$allowhonor = 1;
		$allowdelatc = $groupsetting[11];
		$allowpost = $groupsetting[3];
		$allownewvote = $groupsetting[8];
		$allowvote = $groupsetting[9];
		$allowactive = 1;
		$htmlcode = 0;
		$wysiwyg = 0;
		$allowhidden = 1;
		$allowencode = 1;
		$allowsell = 1;
		$allowsearch = $groupsetting[14];
		$allowmember = $allowprofile = $groupsetting[1];
		$allowreport = 1;
		$allowmessege = $groupsetting[32];
		$allowsort = 1;
		$alloworder = 1;
		$allowupload = $groupsetting[7] ? 2 : 0;
		$allowdownload = $groupsetting[61] ? 2 : 0;
		$allowloadrvrc = 1;
		$allownum = $groupsetting[50];
		$edittime = 0;
		$postpertime = 0;
		$searchtime = 0;
		$signnum = $groupsetting[56];
		$uploadtype = $mright = array();
		$mright['atclog'] = $mright['show'] = $mright['msggroup'] = $mright['ifmemo'] = $mright['modifyvote'] = $mright['viewvote'] = $mright['allowreward'] = $mright['allowencode'] = $mright['leaveword'] = $mright['viewvote'] = $mright['viewvote'] = 1;
		$mright['viewipfrom'] = $mright['anonymous'] = $mright['dig'] = $mright['atccheck'] = $mright['markable'] = $mright['postlimit'] = 0;
		$mright['imgwidth'] = $mright['imgheight'] = $mright['fontsize'] = $mright['maxsendmsg'] = $mright['maxfavor'] = $mright['maxgraft'] = $mright['uploadtype'] = $mright['media'] = $mright['pergroup'] = '';
		$mright['markdb'] = "10|0|10||1";
		$mright['schtime'] = 'all';
		$mright = P_serialize($mright);
		$ifdefault = 0;
		$allowadmincp = $visithide = $delatc = $moveatc = $copyatc = $typeadmin = $viewcheck = $viewclose = $attachper = $delattach = $viewip = $markable = $maxcredit = $credittype = $creditlimit = $banuser = $bantype = $banmax = $viewhide = $postpers = $atccheck = $replylock = $modown = $modother = $deltpcs = 0;
		$sright = '';

		pwGroupref(array('gid'=>$gid,'gptype'=>$gptype,'grouptitle'=>$grouptitle,'groupimg'=>$groupimg,'grouppost'=>$grouppost,'maxmsg'=>$maxmsg,'allowhide'=>$allowhide,'allowread'=>$allowread,'allowportait'=>$allowportait,'upload'=>$upload,'allowrp'=>$allowrp,'allowhonor'=>$allowhonor,'allowdelatc'=>$allowdelatc,'allowpost'=>$allowpost,'allownewvote'=>$allownewvote,'allowvote'=>$allowvote,'allowactive'=>$allowactive,'htmlcode'=>$htmlcode,'wysiwyg'=>$wysiwyg,'allowhidden'=>$allowhidden,'allowencode'=>$allowencode,'allowsell'=>$allowsell,'allowsearch'=>$allowsearch,'allowmember'=>$allowmember,'allowprofile'=>$allowprofile,'allowreport'=>$allowreport,'allowmessage'=>$allowmessege,'allowsort'=>$allowsort,'alloworder'=>$alloworder,'allowupload'=>$allowupload,'allowdownload'=>$allowdownload,'allowloadrvrc'=>$allowloadrvrc,'allownum'=>$allownum,'edittime'=>$edittime,'postpertime'=>$postpertime,'searchtime'=>$searchtime,'signnum'=>$signnum,'mright'=>$mright,'ifdefault'=>$ifdefault,'allowadmincp'=>$allowadmincp,'visithide'=>$visithide,'delatc'=>$delatc,'moveatc'=>$moveatc,'copyatc'=>$copyatc,'typeadmin'=>$typeadmin,'viewcheck'=>$viewcheck,'viewclose'=>$viewclose,'attachper'=>$attachper,'delattach'=>$delattach,'viewip'=>$viewip,'markable'=>$markable,'maxcredit'=>$maxcredit,'credittype'=>$credittype,'creditlimit'=>$creditlimit,'banuser'=>$banuser,'bantype'=>$bantype,'banmax'=>$banmax,'viewhide'=>$viewhide,'postpers'=>$postpers,'atccheck'=>$atccheck,'replylock'=>$replylock,'modown'=>$modown,'modother'=>$modother,'deltpcs'=>$deltpcs,'sright'=>$sright));

		$DDB->update("INSERT INTO {$pw_prefix}usergroups (gptype,grouptitle,groupimg,grouppost) VALUES ('$gptype','$grouptitle','$groupimg','$grouppost')");
	}
	return $_specialdata;
}
function dvbbs_ubb($content)
{
	return preg_replace(array('/\[em(\d+?)\]/i','/\[mp=(\d+?),(\d+?),(?:true|false)\](.+?)\[\/mp\]/si','/\[rm=(\d+?),(\d+?),(?:true|false)\](.+?)\[\/rm\]/si'),array('<img src="images/post/smile/dvbbs/em\\1.gif" />','[wmv=\\1,\\2,0]\\3[/wmv]','[rm=\\1,\\2,0]\\3[/rm]'),str_replace(array('[BR]','[B]','[/B]','[I]','[/I]','[U]','[/U]','[SIZE]','[/SIZE]','[center]','[left]','[right]','[/left]','[/right]','[/center]','[URL=','[/URL]','[EMAIL]','[/EMAIL]','[IMG]','[/IMG]','[QUOTE]','[/QUOTE]','[replyview]','[/replyview]'),array('','[b]','[/b]','[i]','[/i]','[u]','[/u]','[size]','[/size]','[align=center]','[align=left]','[align=right]','[/align]','[/align]','[/align]','[url=','[/url]','[email]','[/email]','[img]','[/img]','[quote]','[/quote]','[post]','[/post]'),$content));
}
function dvattachment($id)
{
	global $SDB, $source_prefix;
	$att = $SDB->query("SELECT F_Filename, F_OldName FROM {$source_prefix}Upfile WHERE F_ID = ".(int)$id);
	return !$att->EOF ? '<a href="attachment/'.$att['F_Filename'].'">'.htmlspecialchars($att['F_OldName']).'</a>' : '';
}
?>