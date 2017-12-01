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
	//转换表情
	$db_table = '转换表情';
	$facearray = array();
	$DDB->query("TRUNCATE TABLE {$pw_prefix}smiles");
	$query = $SDB->query("SELECT * FROM {$source_prefix}smilies");

	while($rs = $SDB->fetch_array($query))
	{
		strip_space($rt);
		$url = $rs['url']=='default' ? 'dznt' : $rs['url'];
		$default = explode('/',$url);
		if(count($default) > 1)
		{
			$url = $default[1];
		}

		$DDB->update("INSERT INTO {$pw_prefix}smiles (id,path,type,name,vieworder) VALUES(".$rs['id'].",'".$url."',".$rs['type'].",'".$rs['code']."',".$rs['displayorder'].")");
		$_pwface[] = '[s:'.$DDB->insert_id().']';
		$_dzface[] = $rs['code'];
		$s_c++;
	}

	writeover(S_P.'tmp_face.php', "\$_pwface = ".pw_var_export($_pwface).";\n\$_dzface = ".pw_var_export($_dzface).";", true);
	report_log();
	newURL($step);
}
elseif ($step == '2')
{
	//勋章
	$DDB->query("TRUNCATE TABLE {$pw_prefix}medalinfo");
	$DDB->query("TRUNCATE TABLE {$pw_prefix}medalslogs");
	$DDB->query("ALTER TABLE {$pw_prefix}medalinfo CHANGE id id SMALLINT( 6 ) NOT NULL AUTO_INCREMENT");
	$query = $SDB->query("SELECT medalid,name,image FROM {$source_prefix}medals Where available = 1");
	while ($x = $SDB->fetch_array($query))
	{
		$DDB->update("INSERT INTO {$pw_prefix}medalinfo (id,name,intro,picurl) VALUES (".$x['medalid'].",'".addslashes($x['name'])."','".addslashes($x['name'])."','".addslashes($x['image'])."')");
		$s_c++;
	}
	report_log();
	newURL($step, '&medal=yes');
}
elseif ($step == '3')
{
	//会员数据
	require_once (S_P.'tmp_credit.php');
	if (!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}members");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}memberdata");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}administrators");
		$DDB->query("DELETE FROM {$pw_prefix}credits WHERE cid > 1");
		$DDB->query("ALTER TABLE {$pw_prefix}credits AUTO_INCREMENT = 2");
		foreach ($expandCredit as $v)
		{
			$DDB->update("INSERT INTO {$pw_prefix}credits (name,unit) VALUES ('".addslashes($v[0])."','".addslashes($v[1])."')");
		}
		//扩展字段长度
		$DDB->query("ALTER TABLE `{$pw_prefix}members` CHANGE `username` `username` VARCHAR( 20 ) NOT NULL DEFAULT ''");
		changegroups();
	}
	$insertadmin = $_specialdata = '';
	require_once (S_P.'tmp_specialdatastr.php');
	$SQL = "SELECT us.uid,CONVERT(varchar(255), us.username) AS username, us.credits, us.extcredits1, us.extcredits2,
			      us.extcredits3,us.extcredits4,us.extcredits5,us.extcredits6,us.extcredits7,us.extcredits8,
			      us.password, us.email, us.showemail, us.gender, us.groupid, CONVERT(char, us.joindate, 20)
			      AS joindate, us.bday, us.newsletter, us.posts, us.digestposts, us.oltime, us.lastip,
			      CONVERT(char, us.lastvisit, 20) AS lastvisit, CONVERT(char, us.lastactivity, 20)
			      AS lastactivity, CONVERT(char, us.lastpost, 20) AS lastpost, us.tpp, us.ppp, us.newpm,
			      CONVERT(varchar(255), uf.avatar) AS avatar, uf.avatarwidth, uf.avatarheight,
			      CONVERT(text, uf.signature) AS signature, CONVERT(text, uf.bio) AS bio, uf.qq,
			      uf.yahoo, uf.msn, uf.icq, CONVERT(varchar(255), uf.website) AS website, uf.medals,
			      CONVERT(varchar(255), uf.location) AS location, CONVERT(varchar(255), uf.customstatus) AS customstatus
			FROM {$source_prefix}users AS us INNER JOIN
			      {$source_prefix}userfields AS uf ON us.uid = uf.uid
			WHERE (us.uid >= $start) AND (us.uid < $end)";
	$query = $SDB->query($SQL);

	while ($m = $SDB->fetch_array($query))
	{
		strip_space($m);
		$m['username'] = addslashes($m['username']);
		if (htmlspecialchars($m['username'])!=$m['username'] || CK_U($m['username']))
		{
			$f_c++;
			errors_log($m[0]."\t".$m['username']);
			continue;
		}
		switch ($m['groupid'])
		{
			case '1'://管理员
				$groupid = '3';
				$insertadmin .= "(".$m['uid'].", '".$m['username']."', 3),";
				break;
			case '2'://总版主
				$groupid = '4';
				$insertadmin .= "(".$m['uid'].", '".$m['username']."', 4),";
				break;
			case '3'://版主
				$groupid = '5';
				$insertadmin .= "(".$m['uid'].", '".$m['username']."', 5),";
				break;
			case '4':
			case '5':
			case '6'://禁止发言
				$groupid = '6';
				break;
			case '7'://游客
				$groupid = '2';
				break;
			case '8'://未验证会员
				$groupid = '7';
				break;
			default :
				$groupid = isset($_specialdata[$m['groupid']]) ? $m['groupid'] : '-1';
				break;
		}
		$userface = $m['avatar'];
		if($m['avatarwidth']==''){$m['avatarwidth']=100;}
		if($m['avatarheight']==''){$m['avatarheight']=100;}
		if ($m['avatar'])
		{
			$avatarpre = substr($m['avatar'], 0, 7);
			switch ($avatarpre)
			{
				case 'http://':
					$userface = $m['avatar'].'|2|'.$m['avatarwidth'].'|'.$m['avatarheight'];
					break;
				case 'avatars':
					$userface = substr($m['avatar'], 8).'|1';
					break;
				case '/avatar':
					$userface = substr($m['avatar'], 9).'|3|'.$m['avatarwidth'].'|'.$m['avatarheight'];
					break;
			}
			$userface = str_replace('\\', '/', $userface);
			if(substr($userface,0,1)=='/')
			{
					$userface = substr($userface,1);
			}
		}
		eval($creditdata);
		$expandCreditSQL = '';
		if($expandCredit)
		{
			foreach ($expandCredit as $k => $v)
			{
				$expandCreditSQL .= '('.$m['uid'].','.($k + 2).','.(int)($m[$v[2]]).'),';
			}
			$expandCreditSQL && $DDB->update("INSERT INTO {$pw_prefix}membercredit (uid, cid, value) VALUES ".substr($expandCreditSQL, 0, -1));
		}
		$bday = $m['bday'] ? date('Y-m-d', strtotime($m['bday'])) : '0000-00-00';
		$signchange = (convert($m['signature']) == $m['signature']) ? 1 : 2;
		$m['userstatus']=($signchange-1)*256+128+$m['showemail']*64+4;//用户位状态设置
		$medals = $medal ? str_replace("\t", ',', $m['medals']) : '';
		$password = $m['password'];
		if(strlen($m['password']) > 16)
		{
			$password = substr($m['password'],8,16);
		}
		$password = strtolower($password);
		
		$DDB->update("INSERT INTO {$pw_prefix}members (uid,username,password,medals,email,groupid,icon,gender,regdate,signature,introduce,oicq,icq,msn,yahoo,site,location,honor,bday,yz,style,t_num,p_num,newpm,userstatus,banpm) VALUES (".$m['uid'].",'".$m['username']."','".$password."','".$medals."','".addslashes($m['email'])."',".$groupid.",'".addslashes($userface)."',".$m['gender'].",'".dt2ut($m['joindate'])."','".addslashes($m['signature'])."','".addslashes($m['bio'])."','".addslashes($m['qq'])."','".addslashes($m['icq'])."','".addslashes($m['msn'])."','".addslashes($m['yahoo'])."','".addslashes($m['website'])."','".addslashes($m['location'])."','".addslashes($m['customstatus'])."','".$bday."',1,'wind',".$m['tpp'].",".$m['ppp'].",".$m['newpm'].",'".$userstatus."','')");
		$DDB->update("INSERT INTO {$pw_prefix}memberdata (uid,postnum,digests,rvrc,money,credit,currency,lastvisit,thisvisit,lastpost,onlinetime) VALUES (".$m['uid'].",".$m['posts'].",".$m['digestposts'].",".$rvrc.",".$money.",".$credit.",".$currency.",".dt2ut($m['lastvisit']).",".dt2ut($m['lastactivity']).",".dt2ut($m['lastpost']).",".$m['oltime'].")");
		$s_c++;
	}

	$insertadmin && $DDB->update("REPLACE INTO {$pw_prefix}administrators (uid,username,groupid) VALUES ".substr($insertadmin, 0, -1));
	$row = $SDB->get_one("SELECT COUNT(*) num FROM {$source_prefix}users WHERE uid >= $end");
	if ($row['num'])
	{
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c.'&medal='.$medal);
	}
	else
	{
		report_log();
		newURL($step);
	}
}
elseif ($step == '4')
{
	//板块数据
	$DDB->query("TRUNCATE TABLE {$pw_prefix}forums");
	$DDB->query("TRUNCATE TABLE {$pw_prefix}forumdata");
	$DDB->query("TRUNCATE TABLE {$pw_prefix}forumsextra");
	$forumdb = $_newgroup = array();
	require_once (S_P.'tmp_newgroupdatastr.php');
	$s_group = array('1'=>'3','2'=>'4','3'=>'5','4'=>'6','5'=>'6','6'=>'6','7'=>'2','8'=>'7');
	foreach($s_group as $key => $value)
	{
		$n_group[$key]= $value;
	}
	foreach($_newgroup as $key => $value)
	{
		$n_group[$key] = $value;
	}

	$SQL = "SELECT f.fid AS fid,f.layer AS layer,f.parentid AS parentid,f.parentidlist AS parentidlist,f.subforumcount,convert(varchar(255),f.name) AS name,f.colcount,f.displayorder,f.topics,f.posts,f.modnewposts,f.jammer,f.disablewatermark,f.topics,f.posts,f.todayposts,f.status,
			fd.icon,fd.attachextensions,fd.viewperm,fd.postperm,fd.replyperm,fd.getattachperm,fd.postattachperm,convert(text,fd.moderators) AS moderators,convert(text,fd.description) AS description, convert(text,fd.topictypes) As topictypes, fd.postbytopictype, fd.topictypeprefix, fd.redirect,fd.applytopictype
			FROM {$source_prefix}forums f
			LEFT JOIN {$source_prefix}forumfields fd
			ON f.fid = fd.fid";
	$query = $SDB->query($SQL);

	while($rt = $SDB->fetch_array($query))
	{
		strip_space($rt);
		$forumdb[$rt['fid']] = $rt;
	}
	foreach($forumdb as $fid => $forum)
	{
		switch ($forum['layer'])
		{
			case '0':
				$ftype = 'category';
				break;
			case '1':
				$ftype = 'forum';
				break;
			default:
				$ftype = 'sub';
				$ifsub = 1;
				break;
		}
		$ifsub = ($ftype == 'sub') ? '1' : '0';
		$childid = $forum['subforumcount'] ? 1 : 0;
		$upadmin = '';
		getupadmin($forum['fid'], $upadmin);
		$allowread = $allowpost = $allowrp = $allowdownload = $allowupload = '';
		if ($forum['viewperm'])
		{
			$viewperm = explode(',', $forum['viewperm']);
			foreach ($viewperm as $v)
			{
				$allowread .= $n_group[$v].',';
			}
			$allowread = ','.$allowread;
		}
		if ($forum['postperm'])
		{
			$postperm = explode(',', $forum['postperm']);
			foreach ($postperm as $v)
			{
				$allowpost .= $n_group[$v].',';
			}
			$allowpost = ','.$allowpost;
		}
		if ($forum['replyperm'])
		{
			$replyperm = explode(',', $forum['replyperm']);
			foreach ($replyperm as $v)
			{
				$allowrp .= $n_group[$v].',';
			}
			$allowrp = ','.$allowrp;
		}
		if ($forum['getattachperm'])
		{
			$getattachperm = explode(',', $forum['getattachperm']);
			foreach ($getattachperm as $v)
			{
				$allowdownload .= $n_group[$v].',';
			}
			$allowdownload = ','.$allowdownload;
		}
		if ($forum['postattachperm'])
		{
			$postattachperm = explode(',', $forum['postattachperm']);
			foreach ($postattachperm as $v)
			{
				$allowupload .= $n_group[$v].',';
			}
			$allowupload = ','.$allowupload;
		}

		$t_type = '';
		$t_typeid = '';
		$addtpctype = '0';
		if($forum['topictypes'] && $forum['topictypes']!='')
		{
			$Arrt_type = explode('|',$forum['topictypes']);
			foreach($Arrt_type as $key => $value)
			{
				if($value!='')
				{
					$type_n = explode(',',$value);
					$t_type .= $type_n[1] . "\t";
					$t_typeid .=$type_n[0] . ",";
				}
			}

			$t_type = substr($t_type, 0, -1);
			$t_typeid = substr($t_typeid, 0, -1);
			$ft_typeid .= $forum['fid'] . "|" . $t_typeid . "%";
			$f_check = ($forum['postbytopictype']=='1')?2:$forum['applytopictype'];
			$t_type = $f_check . "\t" .$t_type;
			$addtpctype = $forum['topictypeprefix'];
		}

		/*
		$insertforumsextra['orderway'] = 'hits';
		$insertforumsextra['asc'] = 'ASC';
		$insertforumsextra['link'] = $forum['redirect'];
		$insertforumsextra['lock'] = $insertforumsextra['cutnums'] = $insertforumsextra['threadnum'] = $insertforumsextra['readnum'] = $insertforumsextra['newtime'] = $insertforumsextra['allowencode'] = $insertforumsextra['inspect'] = $insertforumsextra['commend'] = $insertforumsextra['autocommend'] = $insertforumsextra['rvrcneed'] = $insertforumsextra['moneyneed'] = $insertforumsextra['creditneed'] = $insertforumsextra['postnumneed'] = '0';
		$insertforumsextra['commendlist'] = $insertforumsextra['forumsell'] = $insertforumsextra['uploadset'] = $insertforumsextra['rewarddb'] = $insertforumsextra['allowtime'] = '';
		$insertforumsextra['sellprice'] = array();
		$insertforumsextra['addtpctype'] = $addtpctype;
		$insertforumsextra['anonymous'] = '0';
		$insertforumsextra['dig'] = '';
		$insertforumsextra['commendnum'] = '';
		$insertforumsextra['commendlength'] = '';
		$insertforumsextra['commendtime'] = '';
		$insertforumsextra['watermark'] = '';
		*/
		//$forumsextra = "(".$forum['fid'].",'','".addslashes(serialize($insertforumsextra))."',''),";

		$DDB->update("INSERT INTO {$pw_prefix}forums (fid,fup,ifsub,childid,type,logo,name,descrip,vieworder,forumadmin,fupadmin,across,allowsell,copyctrl,allowpost,allowrp,allowdownload,allowupload,f_check,ifhide,allowtype,t_type) VALUES (".$forum['fid'].",".$forum['parentid'].",".$ifsub.",".$childid.",'".$ftype."','".addslashes($forum['icon'])."','".addslashes(substrs(preg_replace('/<\/?[^\>]*>/im','',$forum['name']),50,0))."','".addslashes($forum['description'])."',".$forum['displayorder'].",'".addslashes($forum['moderators'])."','".$upadmin."',".($forum['colcount']==1 ? 0 : $forum['colcount']).",".$forum['disablewatermark'].",'".$allowread."','".$allowpost."','".$allowrp."','".$allowdownload."','".$allowupload."',".$forum['modnewposts'].",".$forum['status'].",31,'".addslashes($t_type)."')");
		$DDB->update("INSERT INTO {$pw_prefix}forumdata (fid,tpost,topic,article) VALUES (".$forum['fid'].",".$forum['todayposts'].",".$forum['topics'].",".$forum['posts'].")");
		$DDB->update("INSERT INTO {$pw_prefix}forumsextra (fid,creditset,forumset,commend) VALUES ('".$forum['fid']."','','','')");

		$s_c++;
	}
	writeover(S_P.'tmp_typeinfo.php', "\$_typeinfo='".$ft_typeid."';",true);
	report_log();
	newURL($step);
}
elseif ($step == '5')
{
	//友情连接数据
	$DDB->query("TRUNCATE TABLE {$pw_prefix}sharelinks");
	$query = $SDB->query("SELECT displayorder, convert(varchar(255), name) AS name, convert(varchar(255), url) AS url, convert(varchar(255), note) AS note, convert(varchar(255), logo) AS logo FROM {$source_prefix}forumlinks");
	$insert = '';

	while($link = $SDB->fetch_array($query))
	{
		strip_space($link);
		if (stripos($link['name'], 'discuz') === FALSE)
		{
			$insert .= "(".$link['displayorder'].",'".addslashes($link['name'])."', '".addslashes($link['url'])."','".addslashes($link['note'])."','".addslashes($link['logo'])."', 1),";
			$s_c++;
		}
	}

	$insert && $DDB->update("INSERT INTO {$pw_prefix}sharelinks (threadorder, name, url, descrip, logo, ifcheck) VALUES ".substr($insert, 0, -1));
	report_log();
	newURL($step);
}
elseif ($step == '6')
{
	//主题数据
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}threads");
	}
	$query = $SDB->query("SELECT tid,fid,iconid,convert(varchar(255),poster) AS poster,posterid,convert(varchar(255),title) AS title,convert(char,postdatetime,20) AS postdatetime,convert(char,lastpost,20) AS lastpost,
							convert(varchar(255),lastposter) AS lastposter,views,replies,displayorder,digest,rate,hide,attachment,closed,special,typeid
							FROM {$source_prefix}topics WHERE tid >= $start AND tid < $end");

	$facearray = $_replace1 = $_replace2 = array();
	require_once(S_P.'tmp_typeinfo.php');
	while($t = $SDB->fetch_array($query))
	{
		strip_space($t);
		$ifcheck = '1';
		$topped = '0';
		switch ($t['displayorder'])
		{
			case -1:
				$ifcheck = '0';
				break;
			case -2:
				$ifcheck = '0';
				break;
			case 1:
				$topped = '1';
				break;
			case 2:
				$topped = '2';
				break;
			case 3:
				$topped = '3';
				break;
			default:
				$topped = '0';
				break;
		}
		switch ($t['special'])
		{
			case '1':
				$special = 1;//投票
				break;
			case '2':
				$special = 3;//悬赏
				break;
			default:
				$special = 0;//普通
				break;
		}

		//主题分类处理
		RtFidTypeid($t['fid'],$t['typeid'],$_typeinfo,$keyid);

		$DDB->update("INSERT INTO {$pw_prefix}threads (tid,fid,icon,author,authorid,subject,ifcheck,postdate,lastpost,lastposter,hits,replies,topped,digest,ifupload,anonymous,special,type) VALUES (".$t['tid'].",".$t['fid'].",".$t['iconid'].",'".addslashes($t['poster'])."',".($t['posterid'] == '-1' ? 0 : $t['posterid']).",'".addslashes($t['title'])."',".($t['displayorder'] == '-2' ? 0 : 1).",'".dt2ut($t['postdatetime'])."','".dt2ut($t['lastpost'])."','".addslashes($t['lastposter'])."',".$t['views'].",".$t['replies'].",".$topped.",".$t['digest'].",".($t['attachment'] ? 1 : 0).",".($t['posterid'] == '-1' ? 1 : 0).",".$special.",".$keyid.")");
		$s_c++;
	}
	$row = $SDB->get_one("SELECT COUNT(*) num FROM {$source_prefix}topics WHERE tid >= $end");
	if ($row['num'])
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
	//附件数据;
	if(!$start)
	{
		$DDB->update("TRUNCATE TABLE {$pw_prefix}attachs");
		$query = $SDB->query("SELECT id FROM {$source_prefix}tablelist");
		$_tablelist = array();
		while ($t = $SDB->fetch_array($query))
		{
			$_tablelist[] = $t['id'];
		}
		writeover(S_P.'tmp_tablelist.php', "\$_tablelist = ".var_export($_tablelist, TRUE).";",true);
	}
	require_once(S_P.'tmp_tablelist.php');
	$tableid = (int)$tableid;
	$truetableid = $_tablelist[$tableid];
	if ($ckminid)
	{
		$minid = $SDB->get_one("SELECT MIN(pid) AS pid FROM {$source_prefix}posts{$truetableid}");
		$start = (int)$midid['pid'];
		$end = $start + $percount;
	}
	$query = $SDB->query("SELECT a.aid,a.uid,a.tid,a.pid,convert(char, a.postdatetime, 20) AS postdatetime,convert(varchar(255),a.filename) AS filename,convert(varchar(255),a.description) AS description,convert(varchar(255),a.filetype) AS filetype,a.filesize,convert(varchar(255),a.attachment) AS attachment,a.downloads,p.fid,p.layer FROM {$source_prefix}attachments a LEFT JOIN {$source_prefix}posts{$truetableid} p ON a.pid = p.pid WHERE p.pid!='' AND a.aid >= $start AND a.aid < $end");

	while($a = $SDB->fetch_array($query))
	{
		strip_space($a);
        //if(!$a['layer'])continue;
		switch (substr($a['filetype'], 0, strpos($a['filetype'], '/')))
		{
			case 'image':
				$a['filetype'] = 'img';
				break;
			case 'text':
				$a['filetype'] = 'txt';
				break;
			default:
				$a['filetype'] = 'zip';
				break;
		}
		$DDB->update("REPLACE INTO {$pw_prefix}attachs (aid,fid,uid,tid,pid,name,type,size,attachurl,hits,uploadtime,descrip,ifthumb) VALUES (".$a['aid'].",'".$a['fid']."','".$a['uid']."','".$a['tid']."',".($a['layer'] ? $a['pid'] : 0).",'".addslashes($a['attachment'])."','".$a['filetype']."',".(round($a['filesize']/1024)).",'".addslashes(str_replace('\\','/',$a['filename']))."',".$a['downloads'].",".dt2ut($a['postdatetime']).",'".addslashes($a['description'])."',0)");
		$s_c++;
	}
	$row = $SDB->get_one("SELECT COUNT(*) num FROM {$source_prefix}attachments a LEFT JOIN {$source_prefix}posts{$truetableid} p ON a.pid = p.pid WHERE p.pid!='' and a.aid >= $end");
    echo $row['num'];
	if ($row['num'])
	{
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c.'&tableid='.$tableid);
	}
	else
	{
		$tableid += 1;
		if (count($_tablelist) == $tableid)
		{
			report_log();
			newURL($step);
		}
		else
		{
			refreshto($cpage.'&step='.$step.'&start=1&tableid='.$tableid.'&ckminid=yes');
		}
	}
}
elseif ($step == '8')
{
	//公告数据
	$DDB->update("TRUNCATE TABLE {$pw_prefix}announce");
	$query = $SDB->query("SELECT id,convert(varchar(255),poster) AS poster,posterid,convert(varchar(255),title) AS title,displayorder,convert(char,starttime,20) AS starttime,convert(char,endtime,20) AS endtime,convert(text,message) AS message FROM {$source_prefix}announcements");
	while($a = $SDB->fetch_array($query))
	{
		strip_space($a);
		$DDB->update("INSERT INTO {$pw_prefix}announce (aid,fid,ifopen,vieworder,author,startdate,subject,content,ifconvert) VALUES (".$a['id'].",-1,1,".$a['displayorder'].",'".addslashes($a['poster'])."',".dt2ut($a['starttime']).",'".addslashes($a['title'])."','".addslashes($a['message'])."',".((convert($a['message']) == $a['message'])? 0 : 1).")");
		$s_c++;
	}
	report_log();
	newURL($step);
}
elseif ($step == '9')
{
	//短信数据
    $message_sql = $relations_sql = $replies_sql = array();
	if(!$start)
	{
        $DDB->update("TRUNCATE TABLE {$pw_prefix}ms_messages");
        $DDB->update("TRUNCATE TABLE {$pw_prefix}ms_relations");
        $DDB->update("TRUNCATE TABLE {$pw_prefix}ms_replies");
	}
	$query = $SDB->query("SELECT pmid,convert(varchar(255),msgfrom) AS msgfrom,msgfromid,msgtoid,convert(varchar(255),msgto) AS msgto,folder,new,convert(varchar(255),subject) AS subject,convert(char,postdatetime,20) AS postdatetime,convert(text,message) AS message FROM {$source_prefix}pms WHERE pmid >= $start AND pmid < $end");
	while($m = $SDB->fetch_array($query))
	{
		strip_space($m);
		switch ($m['folder'])
		{
			case 0:
				$type = 'rebox';
				break;
			case 1:
				$type = 'sebox';
				$m['msgfrom'] = $m['msgto'];
				break;
			default :
				$type = 'public';
				break;
		}
		//7.0
		$postdatetime = dt2ut($m['postdatetime']);
        /*
		$DDB->update("INSERT INTO {$pw_prefix}msg (mid,touid,fromuid,username,type,ifnew,mdate) VALUES (".$m['pmid'].",".$m['msgtoid'].",".$m['msgfromid'].",'".addslashes($m['msgfrom'])."','".$type."',".$m['new'].",'".$postdatetime."')");
		$DDB->update("INSERT INTO {$pw_prefix}msgc (mid,title,content) VALUES (".$m['pmid'].",'".addslashes($m['subject'])."','".addslashes($m['message'])."')");
		if (($m['msgtoid'] != $m['msgfromid']) && !$m['folder'] && $m['msgtoid']!='-1')
		{
			$DDB->update("INSERT INTO {$pw_prefix}msglog (mid,uid,withuid,mdate,mtype) VALUES (".$m['pmid'].",".$m['msgfromid'].",".$m['msgtoid'].",'".$postdatetime."','send')");
			$DDB->update("INSERT INTO {$pw_prefix}msglog (mid,uid,withuid,mdate,mtype) VALUES (".$m['pmid'].",".$m['msgtoid'].",".$m['msgfromid'].",'".$postdatetime."','receive')");
		}*/

	        $message_sql[] = "('".$m['pmid']."',".$m['msgfromid'].",'".$m['msgfrom']."','".$m['subject']."','".$m['subject']."','".serialize(array('categoryid'=>1,'typeid'=>100))."',".$postdatetime.",".$postdatetime.",'".serialize(array($m['msgto']))."')";
	        $replies_sql[] = "('".$m['pmid']."',".$m['pmid'].",'".$m['msgfromid']."','".$m['msgfrom']."','".$m['subject']."','".$m['message']."','1',".$postdatetime.",".$postdatetime.")";

            $userIds = "";
	        $userIds = array($m['msgtoid'],$m['msgfromid']);
	        foreach($userIds as $otherId){
	            $relations_sql[] = "(".$otherId.",'".$m['pmid']."','1','100','0',".(($otherId == $m['msgfromid']) ? 1 : 0).",".$postdatetime.",".$postdatetime.")";
            }

		$s_c++;
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
	$row = $SDB->get_one("SELECT COUNT(*) num FROM {$source_prefix}pms WHERE pmid >= $end");
	if ($row['num'])
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
	//收藏数据
	$DDB->query("TRUNCATE TABLE {$pw_prefix}favors");
	$query = $SDB->query("SELECT uid,tid FROM {$source_prefix}favorites");
	while($f = $SDB->fetch_array($query))
	{
		strip_space($f);
		$uid = $DDB->get_one("SELECT COUNT(*) num FROM {$pw_prefix}favors WHERE uid = ".$f['uid']);
		if ($uid['num'])
		{
			$DDB->update("UPDATE {$pw_prefix}favors SET tids = CONCAT_WS(',',tids,'".$f['tid']."') WHERE uid = ".$f['uid']);
		}
		else
		{
			$DDB->update("INSERT INTO {$pw_prefix}favors (uid,tids) VALUES (".$f['uid'].", '".$f['tid']."')");
		}
		$s_c++;
	}
	report_log();
	newURL($step);
}
elseif ($step == '11')
{
	//回复主题
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}posts");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}tmsgs");
		$query = $SDB->query("SELECT id FROM {$source_prefix}tablelist");
		$_tablelist = array();
		while ($t = $SDB->fetch_array($query))
		{
			$_tablelist[] = $t['id'];
		}
		writeover(S_P.'tmp_tablelist.php', "\$_tablelist = ".var_export($_tablelist, TRUE).";",true);
	}
	require_once(S_P.'tmp_tablelist.php');
	$tableid = (int)$tableid;
	$truetableid = $_tablelist[$tableid];
	if ($ckminid)
	{
		$minid = $SDB->get_one("SELECT MIN(pid) AS pid FROM {$source_prefix}posts{$truetableid}");
		$start = (int)$midid['pid'];
		$end = $start + $percount;
	}
	$query = $SDB->query("SELECT pid,fid,tid,convert(text,message) AS message,convert(varchar(255),title) AS title,convert(varchar(255),poster) AS poster,posterid,convert(char,postdatetime,20) AS postdatetime,convert(varchar(255),ip) AS ip,invisible,usesig,attachment,layer FROM {$source_prefix}posts{$truetableid} WHERE pid >= $start AND pid < $end");
	$_replace1 = $_replace2 = array();
	require_once(S_P.'tmp_face.php');

	while($p = $SDB->fetch_array($query))
	{
		strip_space($p);
		$aid = $ifupload = '';
        /*
		if ($p['attachment'])
		{
			//取得附件信息
			$att = $SDB->query("SELECT * FROM {$source_prefix}attachments WHERE pid = ".$p['pid']);
			$attdata = array();
			while ($a = $SDB->fetch_array($att))
			{
				strip_space($a);
				$tp = substr($a['filetype'], 0, strpos($a['filetype'], '/'));
				switch ($tp)
				{
					case 'image':
						$a['filetype'] = 'img';
						$ifupload = 1;
						break;
					case 'text':
						$a['filetype'] = 'txt';
						$ifupload = 2;
						break;
					default:
						$a['filetype'] = 'zip';
						$ifupload = 3;
						break;
				}
				$attdata[$a['aid']] = array('aid'=>$a['aid'],'name'=>addslashes($a['attachment']),'type'=>$a['filetype'],'attachurl'=>addslashes(str_replace('\\','/',$a['filename'])),'needrvrc'=>0,'size'=>round($a['filesize']/1024),'hits'=>$a['downloads'],'desc'=>addslashes($a['description']),'ifthumb'=>($a['filetype']=='img' ? 0 : 1));
			}
			$aid = addslashes(serialize($attdata));
		}*/

		$p['title'] = addslashes($p['title']);
		$message = dz_ubb(str_replace($_dzface,$_pwface,$p['message']));
		$ifconvert = (convert($p['message']) == $p['message'])? 1 : 2;
		if($p['layer']=='0')
		{
			$DDB->update("INSERT INTO {$pw_prefix}tmsgs (tid,aid,userip,ifsign,ifconvert,content) VALUES (".$p['tid'].",'".$p['attachment']."','".$p['ip']."',".$p['usesig'].",".((convert($message) == $message)? 1 : 2).",'".addslashes($message)."')");
			$DDB->update("UPDATE {$pw_prefix}threads Set ifshield = ".$p['invisible'].",ifupload='".$ifupload."' Where tid=".$p['tid']);
		}else{
			$DDB->update("INSERT INTO {$pw_prefix}posts (pid,fid,tid,aid,author,authorid,postdate,subject,userip,ifsign,buy,ifconvert,ifcheck,content,ifshield,anonymous) VALUES (".$p['pid'].",".$p['fid'].",".$p['tid'].",'".$p['attachment']."','".addslashes($p['poster'])."',".($p['posterid'] == '-1' ? 0 : $p['posterid']).",".dt2ut($p['postdatetime']).",'".addslashes($p['title'])."','".$p['ip']."',".$p['usesig'].",'',".$ifconvert.",".($p['invisible'] ? 0 : 1).",'".addslashes($message)."',".$p['invisible'].",".($p['posterid'] == '-1' ? 1 : 0).")");
		}
		$s_c++;
	}
	$row = $SDB->get_one("SELECT COUNT(*) num FROM {$source_prefix}posts{$truetableid} WHERE layer <> 0 AND pid >= $end");
	if ($row['num'])
	{
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c.'&tableid='.$tableid);
	}
	else
	{
		$tableid += 1;
		if (count($_tablelist) == $tableid)
		{
			report_log();
			newURL($step);
		}
		else
		{
			refreshto($cpage.'&step='.$step.'&start=1&tableid='.$tableid.'&ckminid=yes');
		}
	}
}
elseif ($step == '12')
{
	//头像
	$_avatar = array();
	$pw_avatar = R_P.'pwavatar';
	$dz_avatar = R_P.'upload';
	if (!$start)
	{
		if (!is_dir($pw_avatar) || !N_writable($pw_avatar) || !is_readable($dz_avatar) || !N_writable($dz_avatar))
		{
			ShowMsg('用于转换头像的 upload 或者 pwavatar 目录不存在或者无法写入。<br /><br />1、请将 Dznt!安装目录/avatars/ 下的 upload 目录复制到 PWBuilder 根目录。<br /><br />2、在PWBuilder 根目录下建立一个名为：pwavatar 的目录，且设定权限为777。<br /><br />', true);
		}
		PWListDir($dz_avatar, $dirname);
		writeover(S_P.'tmp_avatar.php', "\$_avatar = ".pw_var_export($dirname).";", true);
	}
	require_once(S_P.'tmp_avatar.php');
	if ($start >= count($_avatar))
	{
		report_log();
		newURL($step);
	}
	$dh = opendir($_avatar[$start]);
	while (($file = readdir($dh)) !== FALSE)
	{
        //add by yth处理000/00/00/00_这种的头像
        if($file == '.' || $file == '..' || preg_match('/^[a-z0-9\:\/\._]*?\/upload\/(\d{3})\/(\d{2})\/(\d{2})\/(\d{2})\_avatar_large\.jpg$/i', $_avatar[$start].'/'.$file, $match) || preg_match('/^[a-z0-9\:\/\._]*?\/upload\/(\d{3})\/(\d{2})\/(\d{2})\/(\d{2})\_avatar_small\.jpg$/i', $_avatar[$start].'/'.$file, $match)){
            continue;
        }
        //echo $_avatar[$start].'/'.$file;exit;
        if ($file != '.' && $file != '..' && preg_match('/^[a-z0-9\:\/\._]*?\/upload\/(\d{3})\/(\d{2})\/(\d{2})\/(\d{2})\_avatar_medium\.jpg$/i', $_avatar[$start].'/'.$file, $match))
        {
            $uid = intval($match[1].$match[2].$match[3].$match[4]);
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
            continue;
        }
        //add by yth处理000/00/00/00_这种的头像

		if ($file != '.' && $file != '..')
		{
			$afile = explode(".",$file);
			if($afile[1]=='jpg' || $afile[1]=='gif' || $afile[1]=='png' || $afile[1]=='bmp')
			{
				$uid = $afile[0];
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
elseif ($step == '13')
{
	//投票
	if(!$start)
	{
		$DDB->update("TRUNCATE TABLE {$pw_prefix}polls");
	}
	$tid = $SDB->query("SELECT count(*) as num FROM {$source_prefix}polls Where tid >= $start");

	if (!$tid['num'])
	{
		report_log();
		newURL($step);
	}
	//"SELECT us.uid,uf.medals 		FROM {$source_prefix}users AS us INNER JOIN  {$source_prefix}userfields AS uf ON us.uid = uf.uid			WHERE (us.uid >= $start) AND (us.uid < $end)"
	$query = $SDB->query("SELECT p.*, t.postdatetime FROM {$source_prefix}polls as p LEFT JOIN {$source_prefix}topics as t ON p.tid = t.tid WHERE p.tid >= ".$start." And p.tid<=".$end."");
	$ipoll = '';
	while($v = $SDB->fetch_array($query))
	{
		$votearray = array();
		$query = $SDB->query("SELECT * FROM {$source_prefix}polloptions WHERE tid = ".$v['tid']." ORDER BY polloptionid");

		while($vop = $SDB->fetch_array($query))
		{
			$voteuser = array();
			if ($vop['voternames'])
			{
				$vtname = explode(",",$vop['voternames']);
				foreach($vtname as $n => $vn)
				{
					$voteuser[] = $vn;
				}
			}
			$votearray[] = array($vop['polloption'],$vop['votes']);
		}

		//$votearray['multiple'] = array($v['multiple'],$v['maxchoices']);
		$votearray	= addslashes(serialize($votearray));

		$timelimit	= $v['expiration'] ? (($v['expiration'] - $v['postdatetime']) / 86400) : 0;
		$ipoll = "(".$v['tid'].",'{$votearray}',1,".(1^$v['visible']).",{$timelimit},{$v['multiple']},{$v['maxchoices']})";

		$DDB->update("REPLACE INTO {$pw_prefix}polls (tid,voteopts,modifiable,previewable,timelimit,multiple,mostvotes) VALUES ".$ipoll);
		$s_c++;

	}
	refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
}
else
{
	ObHeader($basename.'?action=finish&dbtype='.$dbtype);
	exit;
}

function strip_space(&$array){
	if (is_array($array)) {
		foreach ($array as $key => $value) {
			if (!is_array($value)) {
				$array[$key] = trim($value);
			} else {
				strip_space($array[$key]);
			}
		}
	}
}

//转换用户组
function changegroups()
{
	global $pw_prefix, $source_prefix, $SDB, $DDB, $dest_charset;;
	require_once S_P.'lang_'.$dest_charset.'.php';

	$DDB->update("TRUNCATE TABLE {$pw_prefix}usergroups");
	$DDB->update($lang['group']);
	$query = $SDB->query("SELECT * FROM {$source_prefix}usergroups WHERE system = 0");
	$_specialdata = $_newgroup = array();


	$mright['atclog'] = $mright['show'] = $mright['msggroup'] = $mright['ifmemo'] = $mright['modifyvote'] = $mright['viewvote'] = $mright['allowreward'] = $mright['allowencode'] = $mright['leaveword'] = $mright['viewvote'] = $mright['viewvote'] = 1;
	$mright['viewipfrom'] = $mright['anonymous'] = $mright['dig'] = $mright['atccheck'] = $mright['markable'] = $mright['postlimit'] = 0;
	$mright['imgwidth'] = $mright['imgheight'] = $mright['fontsize'] = $mright['maxsendmsg'] = $mright['maxfavor'] = $mright['maxgraft'] = '';
	$mright['uploadtype'] = $uploadtype ? addslashes(serialize($uploadtype)) : '';
	$mright['media']  = $mright['pergroup'] = '';
	$mright['markdb'] = "10|0|10||1";
	$mright['schtime'] = 'all';
	$mright = P_serialize($mright);

	while ($rt = $SDB->fetch_array($query))
	{
		if ($rt['radminid'])
		{
			$_specialdata[$rt['groupid']] = '1';
			$gptype = 'special';
		}
		else
		{
			$gptype = 'member';
		}
		pwGroupref(array('gid'=>$rt['groupid'],'gptype'=>$gptype,'grouptitle'=>$rt['grouptitle'],'grouppost'=>$rt['creditslower'],'maxmsg'=>$rt['maxpmnum'],'allowhide'=>$rt['allowinvisible'],'allowread'=>$rt['readaccess'] ? 1 : 0,'allowportait'=>$rt['allowavatar'] ? 1 : 0,'upload'=>$rt['allowavatar']==3 ? 1 : 0,'allowrp'=>$rt['allowreply'],'allowhonor'=>$rt['allowcstatus'],'allowdelatc'=>1,'allowpost'=>$rt['allowpost'],'allownewvote'=>$rt['allowpostpoll'],'allowvote'=>$rt['allowvote'],'htmlcode'=>$rt['allowhtml'],'allowhidden'=>$rt['allowhidecode'],'allowencode'=>$rt['allowsetreadperm'],'allowsearch'=>$$rt['allowsearch'],'allowprofile'=>$rt['allowviewpro'],'allowreport'=>1,'allowmessage'=>1,'allowsort'=>$rt['allowviewstats'],'alloworder'=>1,'allowupload'=>$rt['allowpostattach'],'allowdownload'=>$rt['allowgetattach'],'allowloadrvrc'=>$rt['allowsetattachperm'],'allownum'=>50,'edittime'=>0,'postpertime'=>0,'searchtime'=>10,'signnum'=>$rt['maxsigsize'],'mright'=>$mright,'sright'=>''));
		$grouptitle=getGrouptitle($rt['groupid'],$rt['grouptitle'],false);
		$DDB->update("INSERT INTO {$pw_prefix}usergroups (gid,gptype,grouptitle,grouppost) VALUES ('".$rt['groupid']."','$gptype','$grouptitle','".$rt['creditslower']."')");

		$gpid=$rt['groupid'];
		$_newgroup[$gpid] = $DDB->insert_id();
	}
	//写入配置信息
	$_specialdatastr = "\$_specialdata = ".pw_var_export($_specialdata).";";
	writeover(S_P.'tmp_specialdatastr.php', $_specialdatastr,true);
	$newgroupdatastr = "\$_newgroup = ".pw_var_export($_newgroup).";";
	writeover(S_P.'tmp_newgroupdatastr.php', $newgroupdatastr,true);

}
function getupadmin($fid, &$upadmin)
{
	global $forumdb;
	$forumdb[$fid]['moderators'] = trim($forumdb[$fid]['moderators']);
	if ($forumdb[$fid]['moderators'])
	{
		$upadmin .= $upadmin ? addslashes($forumdb[$fid]['moderators']).',' : ','.addslashes($forumdb[$fid]['moderators']).',';
	}
	if ($forumdb[$fid] && $forumdb[$fid]['parentid'])
	{
		getupadmin($forumdb[$fid]['parentid'], $upadmin);
	}
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
function dz_ubb($content)
{
	$content = str_replace(array('[wma]','[/wma]','[flash]','[swf]','[/swf]','[rm]','[ra]','[php]','[/php]','[/ra]','[wmv]','[mp3]','[/mp3]'),array('[wmv=0]','[/wmv]','[flash=314,256,0]','[flash=314,256,0]','[/flash]','[rm=314,256,0]','[rm=314,256,0]','[code]','[/code]','[/rm]','[wmv=314,256,0]','[wmv=0]','[/wmv]'),$content);
	$content = preg_replace(array('~\[media=mp3,\d+?,\d+?,(?:1|0)\](.+?)\[\/media\]~i','~\[media=(?:wmv|mov),(\d+?),(\d+?),(1|0)\](.+?)\[\/media\]~i','~\[media=(rm|ra),(\d+?),(\d+?),(1|0)\](.+?)\[\/media\]~i','~\[hide\](.+?)\[\/hide\]~is','~\[localimg=[0-9]+,[0-9]+\]([0-9]+)\[\/localimg\]~is','~\[local\]([0-9]+)\[\/local\]~is','~\[attach\]([0-9]+)\[\/attach\]~is','~\[attachimg\]([0-9]+)\[\/attachimg\]~is','/\[img=[0-9]+,[0-9]+\]/i','/\[size=(\d+(\.\d+)?(px|pt|in|cm|mm|pc|em|ex|%)+?)\]/i'),array('[wmv=0]\\1[/wmv]','[media=wmv,\\1,\\2,\\3]\\4[/media]','[media=\\1,\\2,\\3,\\4]\\5[/media]','[post]\\1[/post]','[attachment=\\1]','[attachment=\\1]','[attachment=\\1]','[attachment=\\1]','[attachment=\\1]','[img]',''),$content);
	return $content;
}
function RtFidTypeid($fid,$typeid,$typeinfo,&$keyid)
{
	$keyid = 0;
	$Arr_typeinfo = explode('%',$typeinfo);
	foreach($Arr_typeinfo as $key => $value)
	{
		$Arr_fid = explode('|',$value);
		if($Arr_fid[0]==$fid)
		{
			$Arr_typeid = explode(',',$Arr_fid[1]);
			foreach($Arr_typeid as $t_key => $t_value)
			{
				if($t_value==$typeid)
				{
					$keyid = $t_key+1;
				}
			}
		}
	}
}
?>