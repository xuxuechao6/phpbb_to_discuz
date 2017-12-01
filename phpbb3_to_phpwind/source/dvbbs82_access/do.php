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
	//配置
	$s = $SDB->query("SELECT * FROM {$source_prefix}setup");

	$chansetting = explode(',', $s->Fields['forum_chansetting']->value);
	$DDB->update("UPDATE {$pw_prefix}config SET db_value = '".(int)$chansetting[14]."' WHERE db_name = 'db_rmbrate'");
	$DDB->update("UPDATE {$pw_prefix}config SET db_value = '".addslashes($chansetting[4])."' WHERE db_name = 'ol_payto'");
	$DDB->update("UPDATE {$pw_prefix}config SET db_value = '".(intval($chansetting[3]) ^ 1)."' WHERE db_name = 'ol_onlinepay'");

	$setting = explode('|', $s->Fields['forum_setting']->value);

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

	$DDB->update("UPDATE {$pw_prefix}bbsinfo SET newmember = '".addslashes($s->Fields['forum_lastuser']->value)."', totalmember = '".(int)$s->Fields['forum_usernum']->value."', higholnum = '".(int)$s->Fields['forum_maxonline']->value."', higholtime = '".dt2ut($s->Fields['forum_maxonlinedate']->value)."', yposts = '".(int)$s->Fields['forum_yesterdaynum']->value."', hposts = '".(int)$s->Fields['forum_maxpostnum']->value."' WHERE id = 1");

	$_pwface = $_dvface = array();

	if ($s->Fields['forum_badwords']->value)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}wordfb");
		$badwords = explode('|', $s->Fields['forum_badwords']->value);
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

	$m = $SDB->query("SELECT * FROM {$source_prefix}User WHERE UserID >= $start AND UserID < $end");
	while (!$m->EOF)
	{
		$UserName = addslashes($m->Fields['UserName']->value);

		if (htmlspecialchars($UserName) != $UserName || CK_U($UserName))
		{
			$f_c++;
			errors_log($m->Fields['UserID']->value."\t".$m->Fields['UserName']->value);
			$m->MoveNext();
			continue;
		}
		$UserID = (int)$m->Fields['UserID']->value;

		switch ($m['UserGroupID']->value)
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
				$groupid = isset($_specialdata[$m->Fields['UserGroupID']->value]) ? $m->Fields['UserGroupID']->value : '-1';
				break;
		}
		if ($m->Fields['LockUser']->value == 2 || $m->Fields['LockUser']->value == 1)
		{
			$groupid = 6;
		}
		$UserFace = $m->Fields['UserFace']->value;
		if ($UserFace)
		{
			$offset = intval(strpos($UserFace, '|'));
			$offset && $offset += 1;
			if (strtolower(substr($UserFace,0+$offset,7)) == 'http://')
			{
				$UserFace = $UserFace.'|2|'.$m->Fields['UserWidth']->value.'|'.$m->Fields['UserHeight']->value.'|';
			}
			elseif(strtolower(substr($UserFace,0+$offset,10)) == 'uploadface')
			{
				$UserFace = substr($UserFace,11+$offset).'|3|'.$m->Fields['UserWidth']->value.'|'.$m->Fields['UserHeight']->value.'|';
			}
			elseif ((strtolower(substr($UserFace,0+$offset,6)) == 'images'))
			{
	        	$UserFace = substr($UserFace, 16+$offset).'|1|||';
	        }
		}
		$JoinDate = dt2ut($m->Fields['JoinDate']->value);
		$LastLogin = dt2ut($m->Fields['LastLogin']->value);
		$UserBirthday = $m->Fields['UserBirthday']->value ? $m->Fields['UserBirthday']->value : '0000-00-00';
		eval($creditdata);

		$expandCreditSQL = '';
		if($expandCredit)
		{
			foreach ($expandCredit as $k => $v)
			{
				$expandCreditSQL .= '('.$m->Fields['userid']->value.','.($k + 2).','.(int)($m->Fields[$v[2]]->value).'),';
			}
			$expandCreditSQL && $DDB->update("INSERT INTO {$pw_prefix}membercredit (uid, cid, value) VALUES ".substr($expandCreditSQL, 0, -1));
		}
		$UserSex = $m->Fields['UserSex']->value;
		!$UserSex && $UserSex = 2;
		$signchange = (convert($m->Fields['UserSign']->value) == $m->Fields['UserSign']->value) ? 1 : 2;
		$UserIM = explode('|||', $m->Fields['UserIM']->value);
		$UserInfo = explode('|||', $m->Fields['UserInfo']->value);

		$userstatus=($signchange-1)*256+128+1*64+4;//用户位状态设置
$UserIM[0] = addslashes($UserIM[0]);
		$DDB->update("REPLACE INTO {$pw_prefix}members (uid,username,password,email,groupid,icon,gender,regdate,signature,introduce,oicq,icq,msn,yahoo,site,bday,yz,userstatus) VALUES (".$UserID.",'".$UserName."','".$m->Fields['UserPassword']->value."','".addslashes($m->Fields['UserEmail']->value)."','".$groupid."','".$UserFace."','".$UserSex."','".$JoinDate."','".addslashes($m->Fields['UserSign']->value)."','".addslashes($UserInfo[2])."','".$UserIM[1]."','".$UserIM[2]."','".$UserIM[3]."','".$UserIM[4]."','".$UserIM[0]."','".$UserBirthday."',1,'".$userstatus."')");

		$DDB->update("INSERT INTO {$pw_prefix}memberdata (uid,postnum,digests,rvrc,money,credit,currency,lastvisit,thisvisit) VALUES (".$UserID.",".(int)$m->Fields['UserPost']->value.",".(int)$m->Fields['UserIsBest']->value.",".$rvrc.",".$money.",".$credit.",".$currency.",'".$LastLogin."','".$LastLogin."')");

		$s_c++;
		$m->MoveNext();
	}
	$insertadmin && $DDB->update("INSERT INTO {$pw_prefix}administrators (uid,username,groupid) VALUES ".substr($insertadmin, 0, -1));
	$row = $SDB->query("SELECT COUNT(*) AS num FROM {$source_prefix}User WHERE UserID >= $end");
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
	//友情链接
	require_once S_P.'lang_'.$dest_charset.'.php';
	$DDB->query("TRUNCATE TABLE {$pw_prefix}sharelinks");
	$l = $SDB->query("SELECT id,boardname,readme,url,logo,islogo FROM {$source_prefix}BbsLink");
	$ilink = '';
	while (!$l->EOF)
	{
		$ilink .= "('".addslashes($l->Fields['boardname']->value)."','".addslashes($l->Fields['url']->value)."','".addslashes($l->Fields['readme']->value)."','".addslashes($l->Fields['logo']->value)."',1),";
		$s_c++;
		$l->MoveNext();
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
	$v = $SDB->query("SELECT * FROM {$source_prefix}Board");

	$insertforum = $insertforumdb = '';

	while(!$v->EOF)
	{
		$catedb[$v->Fields['boardid']->value] = array('boardid'=>$v->Fields['boardid']->value,'BoardType'=>$v->Fields['BoardType']->value,'ParentID'=>$v->Fields['ParentID']->value,'ParentStr'=>$v->Fields['ParentStr']->value,'Depth'=>$v->Fields['Depth']->value,'RootID'=>$v->Fields['RootID']->value,'Child'=>$v->Fields['Child']->value,'orders'=>$v->Fields['orders']->value,'readme'=>$v->Fields['readme']->value,'BoardMaster'=>$v->Fields['BoardMaster']->value,'Postnum'=>$v->Fields['Postnum']->value,'TopicNum'=>$v->Fields['TopicNum']->value,'indexIMG'=>$v->Fields['indexIMG']->value,'todayNum'=>$v->Fields['todayNum']->value,'LastPost'=>$v->Fields['LastPost']->value,'sid'=>$v->Fields['sid']->value,'Board_Setting'=>$v->Fields['Board_Setting']->value);
		$v->MoveNext();
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
	$a = $SDB->query("SELECT * FROM {$source_prefix}Upfile WHERE F_ID >= $start AND F_ID < $end");
	while (!$a->EOF)
	{
		if ($a->Fields['F_AnnounceID']->value)
		{
			$F_AddTime = dt2ut($a->Fields['F_AddTime']->value);
			$hits= $a->Fields['F_DownNum']->value + $a->Fields['F_ViewNum']->value;
			$F_FileType = strtolower($a->Fields['F_FileType']->value);
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
			$F_AnnounceID = explode('|', $a->Fields['F_AnnounceID']->value);
			$size = ceil($a->Fields['F_FileSize']->value / 1024);
			if ('' != $a->Fields['F_OldName']->value)
			{
				$F_name = addslashes($a->Fields['F_OldName']->value);
			}
			else
			{
				$F_name = addslashes($a->Fields['F_Filename']->value);
			}

			$DDB->update("REPLACE INTO {$pw_prefix}attachs (aid,fid,uid,tid,pid,name,type,size,attachurl,hits,uploadtime) VALUES ('".$a->Fields['F_ID']->value."','".$a->Fields['F_BoardID']->value."','".$a->Fields['F_UserID']->value."','".$F_AnnounceID[0]."','".$F_AnnounceID[1]."','".$F_name."','".$type."','".$size."','".addslashes($a->Fields['F_Filename']->value)."','".$hits."','".$F_AddTime."')");
		}
		else
		{
			$f_c++;
			errors_log($a->Fields['F_ID']->value."\t".$a->Fields['F_Filename']->value);
		}
		$s_c++;
		$a->MoveNext();
	}
	$row = $SDB->query("SELECT COUNT(*) AS num FROM {$source_prefix}Upfile WHERE F_ID >= $end");
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
	//主题信息
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}threads");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}tmsgs");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}posts");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}polls");
        $DDB->update("TRUNCATE TABLE {$pw_prefix}voter");
	}
	$t = $SDB->query("SELECT * FROM {$source_prefix}Topic WHERE TopicID >= $start AND TopicID < $end");

	while (!$t->EOF)
	{
		if (!$t->Fields['BoardID']->value)
		{
			$f_c++;
			errors_log($t->Fields['TopicID']->value."\t".$t->Fields['Title']->value);
			$t->MoveNext();
			continue;
		}
		$titlefont = '';
		$t_DateAndTime = dt2ut($t->Fields['DateAndTime']->value);
		$t_LastPostTime = dt2ut($t->Fields['LastPostTime']->value);
		$t_Expression = $t->Fields['Expression']->value;
		$t_Expression && $t_Expression = getface($t->Fields['Expression']->value);
		$t_Expression = $t_Expression ? $t_Expression : 0;

		$t_Title = strip_tags($t->Fields['Title']->value);
		$t_LastPost = explode('$', $t->Fields['LastPost']->value);
		$t_istop = $t->Fields['istop']->value;
		if($t_istop == 2) $t_istop = 3;
		if ($t->Fields['TopicMode']->value)
		{
			switch ($t->Fields['TopicMode']->value)
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
		if ($t->Fields['IsSmsTopic']->value=='2')
		{
			$special = '4';
		}
		elseif ($t->Fields['isvote']->value)
		{
			$special = '1';
		}
		else
		{
			$special = '0';
		}
		$bbs = $SDB->query("SELECT * FROM ".$t->Fields['PostTable']->value." WHERE RootID = ".$t->Fields['TopicID']->value);
		while (!$bbs->EOF)
		{
			$aid = '';
			$ifupload = 0;
			$attdata = array();
			$bbs_locktopic = $bbs->Fields['locktopic']->value ? '1' : '0';
			$bbs_DateAndTime = dt2ut($bbs->Fields['DateAndTime']->value);
			$bbs_Body = $bbs->Fields['Body']->value;

			if(preg_match('~\[upload=(?:jpg|gif|jpeg|bmp)[^]]*?\]uploadfile([^]]+?)\[\/upload\]~i',$bbs_Body))
			{
				$ifupload = 1;
			}
			$bbs_Body = preg_replace(array('~\[upload=(?:jpg|gif|jpeg|bmp)[^]]*?\]uploadfile([^]]+?)\[\/upload\]~i','~\[upload=.*?\].*?\[\/upload\]~is'),array('[img]attachment\\1[/img]',''),$bbs_Body);
			$qa = $DDB->query("SELECT * FROM {$pw_prefix}attachs WHERE tid = '".$bbs->Fields['RootID']->value."' AND pid = '".$bbs->Fields['AnnounceID']->value."' AND type <>'img' And type <>'bmp' And type <>'jpg' And type <>'gif'");
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
			if ($bbs->Fields['ParentID']->value)
			{
				$DDB->update("REPLACE INTO {$pw_prefix}posts (aid,fid,tid,author,authorid,postdate,subject,userip,ifsign,ifconvert,ifcheck,content,ifshield) VALUES ('".$aid."',".$bbs->Fields['BoardID']->value.",".$bbs->Fields['RootID']->value.",'".addslashes($bbs->Fields['UserName']->value)."','".$bbs->Fields['postuserid']->value."','".$bbs_DateAndTime."','".addslashes(@strip_tags($bbs->Fields['Topic']->value))."','".addslashes($bbs->Fields['ip']->value)."',1,'".$ifconvert."',".((int)$bbs->Fields['isaudit']->value^1).",'".addslashes($bbs_Body)."','".$bbs_locktopic."')");
				$bbs->Fields['isupload']->value && $DDB->update("UPDATE {$pw_prefix}attachs SET pid = ".$DDB->insert_id()." WHERE tid = '".$bbs->Fields['RootID']->value."' AND pid = '".$bbs->Fields['AnnounceID']->value."'");
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
				$DDB->update("REPLACE INTO {$pw_prefix}tmsgs (tid,aid,userip,ifsign,ifconvert,content) VALUES (".$bbs->Fields['RootID']->value.",'".$aid."','".addslashes($bbs->Fields['ip']->value)."',1,'".$ifconvert."','".addslashes($bbs_Body)."')");
				$bbs->Fields['isupload']->value && $DDB->update("UPDATE {$pw_prefix}attachs SET pid = 0 WHERE tid = '".$bbs->Fields['RootID']->value."' AND pid = '".$bbs->Fields['AnnounceID']->value."'");
			}
			$s_c++;
			$bbs->MoveNext();
		}
		$bbs->Close();
		$bbs =  NULL;

		$ifshield = $ifshield ? $ifshield : 0;
		$ifupload = $ifupload ? $ifupload : 0;

		$DDB->update("REPLACE INTO {$pw_prefix}threads (tid,fid,icon,titlefont,author,authorid,subject,ifcheck,postdate,lastpost,lastposter,hits,replies,topped,locked,digest,special,ifshield,ifupload) VALUES (".$t->Fields['TopicID']->value.",".$t->Fields['BoardID']->value.",'".$t_Expression."','".$titlefont."','".addslashes($t->Fields['PostUserName']->value)."',".(int)$t->Fields['PostUserID']->value.",'".addslashes(@strip_tags($t_Title))."','".$ifcheck."','".$t_DateAndTime."','".$t_LastPostTime."','".addslashes($t_LastPost[0])."',".$t->Fields['hits']->value.",".$t->Fields['Child']->value.",".$t_istop.",".$t->Fields['LockTopic']->value.",".$t->Fields['isbest']->value.",".$special.",'".$ifshield."',$ifupload)");
		if ($t->Fields['isvote']->value && $t->Fields['PollID']->value)
		{
			$vote = $SDB->query("SELECT * FROM {$source_prefix}Vote WHERE voteid = ".$t->Fields['PollID']->value);
			if (!$vote->EOF)
			{
				$voteoptions = array();
				$voteitem = explode('|',$vote->Fields['vote']->value);
				$votenum =$vote->Fields['voters']->value;     
                $votearray = array();				
				$vt = $SDB->query("SELECT * FROM {$source_prefix}VoteUser WHERE VoteID = ".(int)$vote->Fields['voteid']->value);
				while (!$vt->EOF)
				{
					$username = $DDB->get_one("SELECT username FROM {$pw_prefix}members WHERE uid = ".$vt->Fields['UserID']->value);
					if ($username)
					{
						$tvid = explode(',', $vt->Fields['VoteOption']->value);
						foreach ($tvid as $n)
						{
                           
							$n && $voteoptions[$n][2][0] = $username['username'];                         
                            $DDB->update("REPLACE INTO {$pw_prefix}voter (tid,uid,username,vote,time) VALUES ('".$t->Fields['TopicID']->value."','".$vt->Fields['UserID']->value."','".$username['username']."','$n','0')");
                            $n && $votenumtemp[$n] = $DDB->get_value("SELECT count(*) FROM {$pw_prefix}voter WHERE tid = '".$t->Fields['TopicID']->value."' and vote = ".$n);
						}
					}
					$vt->MoveNext();
				}
                foreach($voteitem as $k => $v){       
              
                 $votearray[] = array($v,$votenumtemp[$k]);
                
                }              
                $votearray	= addslashes(serialize($votearray));
				$DDB->update("REPLACE INTO {$pw_prefix}polls (tid,voteopts,voters,previewable,timelimit) VALUES ('".$t->Fields['TopicID']->value."','".$votearray."','".$vote->Fields['Voters']->value."',1,".ceil((dt2ut($vote->Fields['TimeOut']->value) - $t_DateAndTime)/86400).")");
             }   


		}
		$s_c++;
		$t->MoveNext();
	}
	$row = $SDB->query("SELECT COUNT(*) AS num FROM {$source_prefix}Topic WHERE TopicID >= $end");
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
	//公告信息
	$DDB->query("TRUNCATE TABLE {$pw_prefix}announce");
	$a = $SDB->query("SELECT * FROM {$source_prefix}BbsNews");

	while (!$a->EOF)
	{
		$addtime = dt2ut($a->Fields['addtime']->value);
		$boardid = (int)$a->Fields['boardid']->value;
		!$boardid && $boardid = '-1';
		$content = dvbbs_ubb($a->Fields['content']->value);
		$DDB->update("INSERT INTO {$pw_prefix}announce (aid,fid,ifopen,author,startdate,subject,content,ifconvert) VALUES (".$a->Fields['id']->value.",".$boardid.",1,'".addslashes($a->Fields['username']->value)."','".addslashes($addtime)."','".addslashes($a->Fields['title']->value)."','".addslashes($content)."',".((convert($content) == $content) ? 0 : 1).")");
		$s_c++;
		$a->MoveNext();
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
	$m = $SDB->query("SELECT * FROM {$source_prefix}message WHERE id >= $start AND id < $end");
	while(!$m->EOF)
	{
		if (!$m->Fields['incept']->value || !$m->Fields['sender']->value || !$m->Fields['content']->value)
		{
			$f_c++;
			errors_log($m->Fields['id']->value."\t".$m->Fields['title']->value."\r");
			$m->MoveNext();
			continue;
		}
		$touid = $DDB->get_one("SELECT uid FROM {$pw_prefix}members WHERE username = '".addslashes($m->Fields['incept']->value)."'");
		$fromuid = $DDB->get_one("SELECT uid FROM {$pw_prefix}members WHERE username = '".addslashes($m->Fields['sender']->value)."'");
		if ($touid && $fromuid)
		{
			//6.3.2
			$sendtime = dt2ut($m->Fields['sendtime']->value);
           // echo $m->Fields['sendtime'];exit;
			if (!$m->Fields['delR']->value && !$m->Fields['delS']->value)
			{
                /*
				$DDB->update("INSERT INTO {$pw_prefix}msg (mid,touid,fromuid,username,type,ifnew,mdate) VALUES (".$m->Fields['id']->value.",".$touid['uid'].",".$fromuid['uid'].",'".($m->Fields['isSend']->value ? addslashes($m->Fields['sender']->value) : addslashes($m->Fields['incept']->value))."','".($m->Fields['isSend']->value ? 'rebox' : 'sebox')."',".($m->Fields['flag']->value^1).",'".$sendtime."')");
				$DDB->update("INSERT INTO {$pw_prefix}msgc (mid,title,content) VALUES (".$m->Fields['id']->value.",'".addslashes($m->Fields['title']->value)."','".addslashes($m->Fields['content']->value)."')");
				if (($touid['uid'] != $fromuid['uid']) && $m->Fields['isSend']->value)
				{
					$DDB->update("INSERT INTO {$pw_prefix}msglog (mid,uid,withuid,mdate,mtype) VALUES (".$m->Fields['id']->value.",".$fromuid['uid'].",".$touid['uid'].",'$sendtime','send')");
					$DDB->update("INSERT INTO {$pw_prefix}msglog (mid,uid,withuid,mdate,mtype) VALUES (".$m->Fields['id']->value.",".$touid['uid'].",".$fromuid['uid'].",'$sendtime','receive')");
					$s_c++;
				}*/


	        $message_sql[] = "('".$m->Fields['id']->value."',".$fromuid['uid'].",'".addslashes($m->Fields['sender']->value)."','".addslashes($m->Fields['title']->value)."','".addslashes($m->Fields['content']->value)."','".serialize(array('categoryid'=>1,'typeid'=>100))."','".$sendtime."','".$sendtime."','".serialize(array(addslashes($m->Fields['incept']->value)))."')";
	        $replies_sql[] = "('".$m->Fields['id']->value."','".$m->Fields['id']->value."','".$fromuid['uid']."','".addslashes($m->Fields['sender']->value)."','".addslashes($m->Fields['title']->value)."','".addslashes($m->Fields['content']->value)."','1',".$sendtime.",".$sendtime.")";

            $userIds = "";
	        $userIds = array($touid['uid'],$fromuid['uid']);
	        foreach($userIds as $otherId){
	            $relations_sql[] = "(".$otherId.",'".$m->Fields['id']->value."','1','100','0',".(($otherId == $fromuid['uid']) ? 1 : 0).",'".$sendtime."','".$sendtime."')";
            }
			}
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
	$row = $SDB->query("SELECT COUNT(*) AS num FROM {$source_prefix}message WHERE id >= $end");
	if ($row['num']->value)
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
	$f = $SDB->query("SELECT F_id,F_friend,F_addtime,F_Mod,F_userid FROM {$source_prefix}friend WHERE f_id >= $start AND f_id < $end");
	while(!$f->EOF)
	{
		if ($f->Fields['F_Mod']->value!=2)
		{
			$friendid = $DDB->get_one("SELECT uid FROM {$pw_prefix}members WHERE username = '".$f->Fields['F_friend']->value."'");
			if ($friendid)
			{
				$f_addtime_friend = dt2ut($f->Fields['F_addtime']->value);
				if ('' ==  $f_addtime_friend)
				{
					$f_addtime_friend = 0;
				}

				$DDB->update("INSERT INTO {$pw_prefix}friends (uid,friendid,status,joindate) VALUES (".$f->Fields['F_userid']->value.",".$friendid['uid'].",0,".$f_addtime_friend.")");
				$s_c++;
			}
		}
		$f->MoveNext();
	}
	$row = $SDB->query("SELECT COUNT(*) AS num FROM {$source_prefix}friend WHERE f_id >= $end");
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
			$g = $SDB->query("SELECT * FROM {$source_prefix}groupname");
			while (!$g->EOF)
			{
				$DDB->update("INSERT INTO {$pw_prefix}colonys (id,classid,cname,admin,members,ifcheck,ifopen,cnimg,createtime,annouce,albumnum,annoucesee,descrip) VALUES (".$g->Fields['id']->value.",".($_colonys[$g->Fields['id']->value] ? $_colonys[$g->Fields['id']->value] : 1).",'".addslashes($g->Fields['GroupName']->value)."','".addslashes($g->Fields['AppUserName']->value)."','".$g->Fields['UserNum']->value."','".($g->Fields['Locked']->value ^ 1)."','".($g->Fields['viewflag']->value ^ 1)."','','".strtotime($g->Fields['AppDate']->value)."','',0,1,'".addslashes($g->Fields['GroupInfo']->value)."')");
				$s_c++;
				$g->MoveNext();
			}
			refreshto($cpage.'&step='.$step.'&start=0&f_c='.$f_c.'&s_c='.$s_c.'&substep=2');
			break;
		case 2:
				refreshto($cpage.'&step='.$step.'&start=0&f_c='.$f_c.'&s_c='.$s_c.'&substep=3');
			//主题
			$t = $SDB->query("SELECT * FROM {$source_prefix}group_topic WHERE topicid >= $start AND topicid < $end");
			while (!$t->EOF)
			{
				$bbs = $SDB->query("SELECT * FROM {$source_prefix}group_bbs1 WHERE rootid = ".$t->Fields['topicid']->value);
				while (!$bbs->EOF)
				{
					$DDB->update("INSERT INTO {$pw_prefix}argument (tid,tpcid,gid,author,authorid,postdate,lastpost,topped,toppedtime,subject,content) VALUES ('".$bbs->Fields['announceid']->value."','".$bbs->Fields['parentid']->value."','".$t->Fields['groupid']->value."','".addslashes($bbs->Fields['username']->value)."',".$bbs->Fields['postuserid']->value.",'".$t->Fields['dateandtime']->value."','".$t->Fields['lastposttime']->value."',".$t->Fields['istop']->value.",0,'".addslashes($t->Fields['title']->value)."','".addslashes($bbs->Fields['body']->value)."')");
					$s_c++;
					$bbs->MoveNext();
				}
				$t->MoveNext();
			}
			$row = $SDB->query("SELECT COUNT(*) AS num FROM {$source_prefix}group_topic WHERE topicid >= $end");
			if ($row->Fields['num']->value)
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
			$m = $SDB->query("SELECT groupid,userid,username,islock,intro FROM {$source_prefix}groupuser WHERE id >= $start AND id < $end");
			while (!$m->EOF)
			{
				$DDB->update("INSERT INTO {$pw_prefix}cmembers (uid,username,ifadmin,colonyid,introduce) VALUES ('".$m->Fields['userid']->value."','".addslashes($m->Fields['UserName']->value)."',".($m->Fields['islock']->value == 2 ? 1 : 0).",".$m->Fields['groupid']->value.",'".addslashes($m->Fields['intro']->value)."')");
				$s_c++;
				$m->MoveNext();
			}
			$row = $SDB->query("SELECT COUNT(*) AS num FROM {$source_prefix}groupuser WHERE id >= $end");
			if ($row->Fields['num']->value)
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
			$b = $SDB->query("SELECT id, rootid, boardname FROM {$source_prefix}group_board");
			while (!$b->EOF)
			{
				$n = $SDB->query("SELECT COUNT(topicid) as num FROM {$source_prefix}group_topic Where boardid = ".$b->Fields['id']->value);
				$DDB->update("INSERT INTO {$pw_prefix}cnclass (cid,cname,cnsum) VALUES (".$b->Fields['id']->value.",'".addslashes($b->Fields['boardname']->value)."','".$n->Fields['num']->value."')");
				$_colonys[$b->Fields['rootid']->value] = $b->Fields['id']->value;
				$b->MoveNext();
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

	$f = $SDB->query("SELECT id,username,url FROM {$source_prefix}bookmark WHERE id >= $start AND id < $end");

	while (!$f->EOF)
	{
		$uid = $DDB->get_one("SELECT uid FROM {$pw_prefix}members WHERE username = '".addslashes($f->Fields['username']->value)."'");
		if ($uid)
		{
			$favorite = $DDB->get_one("SELECT tids FROM {$pw_prefix}favors WHERE uid = ".$uid['uid']);
			$inserttid = intval(substr($f->Fields['url']->value,strrpos($f->Fields['url']->value, '=')+1));
			$favorite ? $DDB->update("UPDATE {$pw_prefix}favors SET tids = CONCAT_WS(',', tids, '".$inserttid."') WHERE uid = ".$uid['uid']) : $DDB->update("INSERT INTO {$pw_prefix}favors (uid,tids) VALUES (".$uid['uid'].",$inserttid)");
			$s_c++;
		}
		else
		{
			$f_c++;
			errors_log($f->Fields['username']->value);
			$f->MoveNext();
			continue;
		}
		$f->MoveNext();
	}
	$row = $SDB->query("SELECT COUNT(*) AS num FROM {$source_prefix}bookmark WHERE id >= $end");
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
else
{
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

	$g = $SDB->query("SELECT * FROM {$source_prefix}UserGroups WHERE ParentGID IN (2,3)");
	$_specialdata = array();
	$gptype = '';
	while (!$g->EOF)
	{
		$gid = $g->Fields['UserGroupID']->value;
		if ($g->Fields['ParentGID']->value == 3)
		{
			$gptype = 'member';
			$grouppost = (int)$g->Fields['MinArticle']->value;
		}
		else
		{
			$gptype = 'special';
			$_specialdata[$g->Fields['UserGroupID']->value] = 1;
			$grouppost = 0;
		}

		$grouptitle = addslashes($g->Fields['usertitle']->value);

		$groupimg = substr($g->Fields['GroupPic'], 0, strrpos($g->Fields['GroupPic']->value, '.'));
		$groupsetting = explode(',', $g->Fields['GroupSetting']->value);

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

		$g->MoveNext();
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
	return !$att->EOF ? '<a href="attachment/'.$att->Fields['F_Filename']->value.'">'.htmlspecialchars($att->Fields['F_OldName']->value).'</a>' : '';
}
?>