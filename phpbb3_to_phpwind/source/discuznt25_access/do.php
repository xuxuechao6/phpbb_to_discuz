<div></div><?php
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
	$facearray = array();
	$DDB->query("TRUNCATE TABLE {$pw_prefix}smiles");
	$rs = $SDB->query("SELECT * FROM {$source_prefix}smilies");

	while (!$rs->EOF)
	{
		$url = $rs->Fields['url']->value=='default' ? 'dznt' : $rs->Fields['url']->value;
		$default = explode('/',$url);
		if(count($default) > 1)
		{
			$url = $default[1];
		}

		$DDB->update("INSERT INTO {$pw_prefix}smiles (id,path,type,name,vieworder) VALUES(".$rs->Fields['id']->value.",'".$url."',".$rs->Fields['type']->value.",'".$rs->Fields['code']->value."',".$rs->Fields['displayorder']->value.")");
		$_pwface[] = '[s:'.$DDB->insert_id().']';
		$_dzface[] = $rs->Fields['code']->value;
		$s_c++;
		$rs->MoveNext();
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
	$x = $SDB->query("SELECT medalid,name,image FROM {$source_prefix}medals Where available = 1");
	while (!$x->EOF)
	{
		$DDB->update("INSERT INTO {$pw_prefix}medalinfo (id,name,intro,picurl) VALUES (".$x->Fields['medalid']->value.",'".addslashes($x->Fields['name']->value)."','".addslashes($x->Fields['name']->value)."','".addslashes($x->Fields['image']->value)."')");
		$s_c++;
		$x->MoveNext();
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
		$DDB->query("ALTER TABLE `{$pw_prefix}members` CHANGE `username` `username` VARCHAR( 20 ) ".$DDB->collation()." NOT NULL DEFAULT ''");
		changegroups();
	}

	$insertadmin = $_specialdata = '';
	require_once (S_P.'tmp_specialdatastr.php');
	$SQL = "SELECT us.uid, us.username, us.credits, us.extcredits1, us.extcredits2,
			      us.extcredits3,us.extcredits4,us.extcredits5,us.extcredits6,us.extcredits7,us.extcredits8,
			      us.password, us.email, us.showemail, us.gender, us.groupid, us.joindate, us.bday, us.newsletter, us.posts, us.digestposts, us.oltime, us.lastip,
			      us.lastvisit, us.lastactivity, us.lastpost, us.tpp, us.ppp, us.newpm,
			      uf.avatar, uf.avatarwidth, uf.avatarheight,
			      uf.signature, uf.bio, uf.qq,
			      uf.yahoo, uf.msn, uf.icq, uf.website,
			      uf.location, uf.customstatus,uf.medals
			FROM {$source_prefix}users AS us INNER JOIN
			      {$source_prefix}userfields AS uf ON us.uid = uf.uid
			WHERE (us.uid >= $start) AND (us.uid < $end)";
	$rs = $SDB->query($SQL);
	$i = 0;
	while (!$rs->EOF)
	{
		$username= addslashes($rs->Fields['username']->value);
		if (htmlspecialchars($username)!=$username || CK_U($username))
		{
			$f_c++;
			errors_log($rs->Fields['uid']->value."\t".$rs->Fields['username']->value);
			$rs->MoveNext();
			continue;
		}

		switch ($rs->Fields['groupid']->value)
		{
			case '1'://管理员
				$groupid = '3';
				$insertadmin .= "(".$rs->Fields['uid']->value.", '".$username."', 3),";
				break;
			case '2'://总版主
				$groupid = '4';
				$insertadmin .= "(".$rs->Fields['uid']->value.", '".$username."', 4),";
				break;
			case '3'://版主
				$groupid = '5';
				$insertadmin .= "(".$rs->Fields['uid']->value.", '".$username."', 5),";
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
				$groupid = isset($_specialdata[$rs->Fields['groupid']->value]) ? $rs->Fields['groupid']->value : '-1';
				break;
		}
		$userface = $rs->Fields['avatar']->value;
		$avatarwidth = $rs->Fields['avatarwidth']->value==''? 100 : $rs->Fields['avatarwidth']->value;
		$avatarheight = $rs->Fields['avatarheight']->value==''? 100 : $rs->Fields['avatarheight']->value;
		if ($rs->Fields['avatar']->value)
		{
			$avatarpre = substr($rs->Fields['avatar']->value, 0, 7);
			switch ($avatarpre)
			{
				case 'http://':
					$userface = $rs->Fields['avatar']->value.'|2|'.$avatarwidth.'|'.$avatarheight;
					break;
				case 'avatars':
					$userface = substr($rs->Fields['avatar']->value, 8).'|1';
					break;
				case '/avatar':
					$userface = substr($rs->Fields['avatar']->value, 9).'|3|'.$avatarwidth.'|'.$avatarheight;
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
				$expandCreditSQL .= '('.$rs->Fields['uid']->value.','.($k + 2).','.(int)($rs->Fields[$v[2]]->value).'),';
			}
			$expandCreditSQL && $DDB->update("INSERT INTO {$pw_prefix}membercredit (uid, cid, value) VALUES ".substr($expandCreditSQL, 0, -1));
		}
		$bday = $rs->Fields['bday'] ? date('Y-m-d', strtotime($rs->Fields['bday']->value)) : '0000-00-00';
		$signchange = (convert($rs->Fields['signature']->value) == $rs->Fields['signature']->value) ? 1 : 2;

		$userstatus=($signchange-1)*256+128+$rs->Fields['showemail']->value*64+4;//用户位状态设置

		$medals = $medal ? str_replace("\t", ',', $rs->Fields['medals']->value) : '';
		$password = $rs->Fields['password']->value;
		if(strlen($rs->Fields['password']->value) > 16)
		{
			$password = substr($rs->Fields['password']->value,8,16);
		}
		$password = strtolower($password);
		
		$DDB->update("INSERT INTO {$pw_prefix}members (uid,username,password,medals,email,groupid,icon,gender,regdate,signature,introduce,oicq,icq,msn,yahoo,site,location,honor,bday,yz,style,t_num,p_num,newpm,userstatus) VALUES (".$rs->Fields['uid']->value.",'".$username."','".$password."','".$medals."','".addslashes($rs->Fields['email']->value)."',".$groupid.",'".addslashes($userface)."',".$rs->Fields['gender']->value.",'".dt2ut($rs->Fields['joindate']->value)."','".addslashes($rs->Fields['signature']->value)."','".addslashes($rs->Fields['bio']->value)."','".addslashes($rs->Fields['qq']->value)."','".addslashes($rs->Fields['icq']->value)."','".addslashes($rs->Fields['msn']->value)."','".addslashes($rs->Fields['yahoo']->value)."','".addslashes($rs->Fields['website']->value)."','".addslashes($rs->Fields['location']->value)."','".addslashes($rs->Fields['customstatus']->value)."','".$bday."',1,'wind',".$rs->Fields['tpp']->value.",".$rs->Fields['ppp']->value.",".$rs->Fields['newpm']->value.",'".$userstatus."')");

		$DDB->update("INSERT INTO {$pw_prefix}memberdata (uid,postnum,digests,rvrc,money,credit,currency,lastvisit,thisvisit,lastpost,onlinetime) VALUES (".$rs->Fields['uid']->value.",".$rs->Fields['posts']->value.",".$rs->Fields['digestposts']->value.",".$rvrc.",".$money.",".$credit.",".$currency.",".dt2ut($rs->Fields['lastvisit']->value).",".dt2ut($rs->Fields['lastactivity']->value).",".dt2ut($rs->Fields['lastpost']->value).",".$rs->Fields['oltime']->value.")");

		$s_c++;
		$i++;
		$rs->MoveNext();
	}
	$insertadmin && $DDB->update("REPLACE INTO {$pw_prefix}administrators (uid,username,groupid) VALUES ".substr($insertadmin, 0, -1));
	$row = $SDB->query("SELECT COUNT(*) as num FROM {$source_prefix}users WHERE uid >= $end");
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
elseif ($step == '4')
{

	//板块数据
	$DDB->query("TRUNCATE TABLE {$pw_prefix}forums");
	$DDB->query("TRUNCATE TABLE {$pw_prefix}forumdata");
	$DDB->query("TRUNCATE TABLE {$pw_prefix}forumsextra");
	$forumdb = $_newgroup = $n_group = array();
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

	$SQL = "SELECT f.fid,f.layer,f.parentid,f.parentidlist,f.subforumcount,f.name,f.colcount,f.displayorder,f.topics,f.posts,f.istrade,f.modnewposts,f.jammer,f.disablewatermark,f.topics,f.posts,f.todayposts,f.status,
			fd.icon,fd.attachextensions,fd.viewperm,fd.postperm,fd.replyperm,fd.getattachperm,fd.postattachperm,fd.moderators,fd.description,fd.topictypes,fd.postbytopictype,fd.topictypeprefix,fd.redirect,fd.applytopictype
			FROM {$source_prefix}forums f
			LEFT JOIN {$source_prefix}forumfields fd
			ON f.fid = fd.fid";

	$rt = $SDB->query($SQL);
	while(!$rt->EOF)
	{
		strip_space($rt);
		$forumdb[$rt->Fields['fid']->value] = array('fid'=>$rt->Fields['fid']->value,'layer'=>$rt->Fields['layer']->value,'subforumcount'=>$rt->Fields['fid']->value,'parentid'=>$rt->Fields['parentid']->value,'viewperm'=>$rt->Fields['viewperm']->value,'postperm'=>$rt->Fields['postperm']->value,'replyperm'=>$rt->Fields['replyperm']->value,'getattachperm'=>$rt->Fields['getattachperm']->value,'postattachperm'=>$rt->Fields['postattachperm']->value,'todayposts'=>$rt->Fields['todayposts']->value,'topics'=>$rt->Fields['topics']->value,'posts'=>$rt->Fields['posts']->value,'icon'=>$rt->Fields['icon']->value,'name'=>$rt->Fields['name']->value,'description'=>addslashes($rt->Fields['description']->value),'displayorder'=>$rt->Fields['displayorder']->value,'moderators'=>$rt->Fields['moderators']->value,'colcount'=>$rt->Fields['colcount']->value,'istrade'=>$rt->Fields['istrade']->value,'disablewatermark'=>$rt->Fields['disablewatermark']->value,'modnewposts'=>$rt->Fields['modnewposts']->value,'status'=>$rt->Fields['status']->value,'topictypes'=>$rt->Fields['topictypes']->value,'redirect'=>$rt->Fields['redirect']->value,'postbytopictype'=>$rt->Fields['postbytopictype']->value,'applytopictype'=>$rt->Fields['applytopictype']->value,'topictypeprefix'=>$rt->Fields['topictypeprefix']->value);
		$rt->MoveNext();
	}
	foreach($forumdb as $fid => $fm)
	{
		switch ($fm['layer'])
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
		$childid = $fm['subforumcount'] ? 1 : 0;
		$upadmin = '';
		getupadmin($fm['fid'], $upadmin);
		$allowread = $allowpost = $allowrp = $allowdownload = $allowupload = '';
		if ($fm['viewperm'])
		{
			$viewperm = explode(',', $fm['viewperm']);
			foreach ($viewperm as $v)
			{
				$allowread .= $n_group[$v].',';
			}
			$allowread = ','.$allowread;
		}
		if ($fm['postperm'])
		{
			$postperm = explode(',', $fm['postperm']);
			foreach ($postperm as $v)
			{
				$allowpost .= $n_group[$v].',';
			}
			$allowpost = ','.$allowpost;
		}
		if ($fm['replyperm'])
		{
			$replyperm = explode(',', $fm['replyperm']);
			foreach ($replyperm as $v)
			{
				$allowrp .= $n_group[$v].',';
			}
			$allowrp = ','.$allowrp;
		}
		if ($fm['getattachperm'])
		{
			$getattachperm = explode(',', $fm['getattachperm']);
			foreach ($getattachperm as $v)
			{
				$allowdownload .= $n_group[$v].',';
			}
			$allowdownload = ','.$allowdownload;
		}
		if ($fm['postattachperm'])
		{
			$postattachperm = explode(',', $fm['postattachperm']);
			foreach ($postattachperm as $v)
			{
				$allowupload .= $n_group[$v].',';
			}
			$allowupload = ','.$allowupload;
		}

		$t_type = '';
		$t_typeid = '';
		$addtpctype = '0';
		if($fm['topictypes'] && $fm['topictypes']!='')
		{
			$Arrt_type = explode('|',$fm['topictypes']);
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
			$ft_typeid .= $fm['fid'] . "|" . $t_typeid . "%";
			$f_check = ($fm['postbytopictype']=='1')?2:$fm['applytopictype'];
			$t_type = $f_check . "\t" .$t_type;
			$addtpctype = $fm['topictypeprefix'];
		}
		$insertforumsextra['orderway'] = 'hits';
		$insertforumsextra['asc'] = 'ASC';
		$insertforumsextra['link'] = $fm['redirect'];
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

		$forumsextra = "(".$fm['fid'].",'','".addslashes(serialize($insertforumsextra))."',''),";

		$DDB->update("INSERT INTO {$pw_prefix}forums (fid,fup,ifsub,childid,type,logo,name,descrip,vieworder,forumadmin,fupadmin,across,allowsell,copyctrl,allowread,allowpost,allowrp,allowdownload,allowupload,f_check,ifhide,allowtype,t_type) VALUES (".$fm['fid'].",".$fm['parentid'].",".$ifsub.",".$childid.",'".$ftype."','".addslashes($fm['icon'])."','".addslashes(substrs(preg_replace('/<\/?[^\>]*>/im','',$fm['name']),50,0))."','".addslashes($fm['description'])."',".$fm['displayorder'].",'".addslashes($fm['moderators'])."','".$upadmin."',".($fm['colcount']==1 ? 0 : $fm['colcount']).",".$fm['istrade'].",".$fm['disablewatermark'].",'".$allowread."','".$allowpost."','".$allowrp."','".$allowdownload."','".$allowupload."',".$fm['modnewposts'].",".$fm['status'].",31,'".addslashes($t_type)."')");
		$DDB->update("INSERT INTO {$pw_prefix}forumdata (fid,tpost,topic,article) VALUES (".$fm['fid'].",".$fm['todayposts'].",".$fm['topics'].",".$fm['posts'].")");
		$forumsextra && $DDB->update("INSERT INTO {$pw_prefix}forumsextra (fid,creditset,forumset,commend) VALUES ".substr($forumsextra, 0, -1));
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
	$link = $SDB->query("SELECT displayorder, name, url, note, logo FROM {$source_prefix}forumlinks");

	$insert = '';
	while(!$link->EOF)
	{
		if (stripos($link->Fields['name']->value, 'discuz') === FALSE)
		{
			$insert .= "(".$link->Fields['displayorder']->value.",'".addslashes($link->Fields['name']->value)."', '".addslashes($link->Fields['url']->value)."','".addslashes($link->Fields['note']->value)."','".addslashes($link->Fields['logo']->value)."', 1),";
			$s_c++;
		}
		$link->MoveNext();
	}

	$insert && $DDB->update("INSERT INTO {$pw_prefix}sharelinks (threadorder, name, url, descrip, logo, ifcheck) VALUES ".substr($insert, 0, -1));

	report_log();
	newURL($step);
}
elseif ($step == '6')
{
	//主题数据
	$_pwface = $_dzface = '';
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}threads");
	}

	$rt = $SDB->query("SELECT tid,fid,iconid,poster,posterid,title,postdatetime,lastpost,
							lastposter,views,replies,displayorder,digest,rate,hide,attachment,closed,special,typeid
							FROM {$source_prefix}topics WHERE tid >= $start AND tid < $end");
	$_face = $_replace1 = $_replace2 = array();

	require_once(S_P.'tmp_typeinfo.php');

	while(!$rt->EOF)
	{
		if (!$rt->Fields['fid']->value)
		{
			$f_c++;
			errors_log($rt->Fields['fid']->value."\t".$rt->Fields['subject']->value);
			$rt->MoveNext();
			continue;
		}
		$ifcheck = '1';
		$topped = '0';
		switch ($rt->Fields['displayorder']->value)
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
		switch ($rt->Fields['special']->value)
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
		RtFidTypeid($rt->Fields['fid']->value,$rt->Fields['typeid']->value,$_typeinfo,$keyid);
		$DDB->update("INSERT INTO {$pw_prefix}threads (tid,fid,icon,author,authorid,subject,ifcheck,postdate,lastpost,lastposter,hits,replies,topped,digest,ifupload,anonymous,special,type) VALUES (".$rt->Fields['tid']->value.",".$rt->Fields['fid']->value.",".$rt->Fields['iconid']->value.",'".addslashes($rt->Fields['poster']->value)."',".($rt->Fields['posterid']->value == '-1' ? 0 : $rt->Fields['posterid']->value).",'".addslashes($rt->Fields['title']->value)."',".($rt->Fields['displayorder']->value == '-2' ? 0 : 1).",'".dt2ut($rt->Fields['postdatetime']->value)."','".dt2ut($rt->Fields['lastpost']->value)."','".addslashes($rt->Fields['lastposter']->value)."',".$rt->Fields['views']->value.",".$rt->Fields['replies']->value.",".$topped.",".$rt->Fields['digest']->value.",".($rt->Fields['attachment']->value ? 1 : 0).",".($rt->Fields['posterid']->value == '-1' ? 1 : 0).",".$special.",".$keyid.")");
		$s_c++;
		$rt->MoveNext();
	}

	$row = $SDB->query("SELECT COUNT(*) as num FROM {$source_prefix}topics WHERE tid >= $end");
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
	//附件数据
	if(!$start)
	{
		$DDB->update("TRUNCATE TABLE {$pw_prefix}attachs");
	}
	$rt = $SDB->query("SELECT a.aid,a.uid,a.tid,a.pid,a.postdatetime,a.filename,a.description,a.filetype,a.filesize,a.attachment,a.downloads,p.fid,p.layer FROM {$source_prefix}attachments a LEFT JOIN {$source_prefix}posts1 p ON a.pid = p.pid WHERE a.aid >= $start AND a.aid < $end");

	while(!$rt->EOF)
	{
		$filetype = $rt->Fields['filetype']->value;
		switch (substr($rt->Fields['filetype']->value, 0, strpos($rt->Fields['filetype']->value, '/')))
		{
			case 'image':
				$filetype = 'img';
				break;
			case 'text':
				$filetype = 'txt';
				break;
			default:
				$filetype = 'zip';
				break;
		}
		$DDB->update("INSERT INTO {$pw_prefix}attachs (aid,fid,uid,tid,pid,name,type,size,attachurl,hits,uploadtime,descrip,ifthumb) VALUES (".$rt->Fields['aid']->value.",'".$rt->Fields['fid']->value."','".$rt->Fields['uid']->value."','".$rt->Fields['tid']->value."',".($rt->Fields['layer']->value ? $rt->Fields['pid']->value : 0).",'".addslashes($rt->Fields['attachment']->value)."','".$filetype."',".(round($rt->Fields['filesize']->value/1024)).",'".addslashes(str_replace('\\','/',$rt->Fields['filename']->value))."',".$rt->Fields['downloads']->value.",".dt2ut($rt->Fields['postdatetime']->value).",'".addslashes($rt->Fields['description']->value)."',0)");
		$s_c++;
		$rt->MoveNext();
	}
	$row = $SDB->query("SELECT COUNT(*) as num FROM {$source_prefix}attachments WHERE aid >= $end");
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
	//公告数据
	$DDB->update("TRUNCATE TABLE {$pw_prefix}announce");
	$a = $SDB->query("SELECT id,poster,posterid,title,displayorder,starttime,endtime,message FROM {$source_prefix}announcements");
	while(!$a->EOF)
	{
		//6.3.2
		$DDB->update("INSERT INTO {$pw_prefix}announce (aid,fid,ifopen,vieworder,author,startdate,enddate,subject,content,ifconvert) VALUES (".$a->Fields['id']->value.",-1,1,".$a->Fields['displayorder']->value.",'".addslashes($a->Fields['poster']->value)."',".dt2ut($a->Fields['starttime']->value).",".dt2ut($a->Fields['endtime']->value).",'".addslashes($a->Fields['title']->value)."','".addslashes($a->Fields['message']->value)."',".((convert($a->Fields['message']->value) == $a->Fields['message']->value)? 0 : 1).")");
		$s_c++;
		$a->MoveNext();
	}
	report_log();
	newURL($step);
}
elseif ($step == '9')
{
	//短信数据
	if(!$start)
	{
		$DDB->update("TRUNCATE TABLE {$pw_prefix}msg");
		$DDB->update("TRUNCATE TABLE {$pw_prefix}msgc");
		$DDB->update("TRUNCATE TABLE {$pw_prefix}msglog");
	}
	$m = $SDB->query("SELECT pmid,msgfrom,msgfromid,msgtoid,msgto,folder,new,subject,postdatetime,message FROM {$source_prefix}pms WHERE pmid >= $start AND pmid < $end");
	while(!$m->EOF)
	{
		$msgfrom = $m->Fields['msgfrom']->value;
		switch ($m->Fields['folder'])
		{
			case 0:
				$type = 'rebox';
				break;
			case 1:
				$type = 'sebox';
				$msgfrom = $m->Fields['msgto']->value;
				break;
			default :
				$type = 'public';
				break;
		}
		//6.3.2
		$postdatetime = dt2ut($m->Fields['postdatetime']->value);
		$DDB->update("INSERT INTO {$pw_prefix}msg (mid,touid,fromuid,username,type,ifnew,mdate) VALUES (".$m->Fields['pmid']->value.",".$m->Fields['msgtoid']->value.",".$m->Fields['msgfromid']->value.",'".addslashes($m->Fields['msgfrom']->value)."','".$type."',".$m->Fields['new']->value.",'".$postdatetime."')");
		$DDB->update("INSERT INTO {$pw_prefix}msgc (mid,title,content) VALUES (".$m->Fields['pmid']->value.",'".addslashes($m->Fields['subject']->value)."','".addslashes($m->Fields['message']->value)."')");
		if (($m->Fields['msgtoid']->value != $m->Fields['msgfromid']->value) && !$m->Fields['folder']->value && $m->Fields['msgtoid']->value!='-1')
		{
			$DDB->update("INSERT INTO {$pw_prefix}msglog (mid,uid,withuid,mdate,mtype) VALUES (".$m->Fields['pmid']->value.",".$m->Fields['msgfromid']->value.",".$m->Fields['msgtoid']->value.",'".$postdatetime."','send')");
			$DDB->update("INSERT INTO {$pw_prefix}msglog (mid,uid,withuid,mdate,mtype) VALUES (".$m->Fields['pmid']->value.",".$m->Fields['msgtoid']->value.",".$m->Fields['msgfromid']->value.",'".$postdatetime."','receive')");
		}
		$s_c++;
		$m->MoveNext();
	}
	$row = $SDB->query("SELECT COUNT(*) as num FROM {$source_prefix}pms WHERE pmid >= $end");
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
	//收藏数据
	$DDB->query("TRUNCATE TABLE {$pw_prefix}favors");
	$f = $SDB->query("SELECT uid,tid FROM {$source_prefix}favorites");
	while(!$f->EOF)
	{
		$uid = $DDB->get_one("SELECT COUNT(*) as num FROM {$pw_prefix}favors WHERE uid = ".$f->Fields['uid']->value);

		if ($uid['num'])
		{
			$DDB->update("UPDATE {$pw_prefix}favors SET tids = CONCAT_WS(',',tids,'".$f->Fields['tid']->value."') WHERE uid = ".$f->Fields['uid']->value);
		}
		else
		{
			$DDB->update("INSERT INTO {$pw_prefix}favors (uid,tids) VALUES (".$f->Fields['uid']->value.", '".$f->Fields['tid']->value."')");
		}
		$s_c++;
		$f->MoveNext();
	}
	report_log();
	newURL($step);
}
elseif ($step == '11')
{
	//回复主题
	$_tablelist = $attdata = $_dzface = $_pwface = array();
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}posts");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}tmsgs");
		$t = $SDB->query("SELECT id FROM {$source_prefix}tablelist");
		while (!$t->EOF)
		{
			$_tablelist[] = $t->Fields['id']->value;
			$t->MoveNext();
		}
		writeover(S_P.'tmp_tablelist.php', "\$_tablelist = ".var_export($_tablelist, TRUE).";",true);
	}
	require_once(S_P.'tmp_tablelist.php');
	$tableid = (int)$tableid;
	$truetableid = $_tablelist[$tableid];

	if ($ckminid)
	{
		$minid = $SDB->query("SELECT MIN(pid) AS pid FROM {$source_prefix}posts{$truetableid}");
		$start = (int)$midid->Fields['pid']->value;
		$end = $start + $percount;
	}
	$p = $SDB->query("SELECT pid,fid,tid,message,title,poster,posterid,postdatetime,ip,invisible,usesig,attachment,layer FROM {$source_prefix}posts{$truetableid} WHERE pid >= $start AND pid < $end");
	$_face = $_replace1 = $_replace2 = array();
	require_once(S_P.'tmp_face.php');

	while(!$p->EOF)
	{
		if (!$p->Fields['fid']->value || !$p->Fields['tid']->value)
		{
			$f_c++;
			errors_log($p->Fields['pid']->value."\t".$p->Fields['fid']->value."\t".$p->Fields['tid']->value);
			$p->MoveNext();
		}
		$aid = $ifupload = '';
		if ($p->Fields['attachment']->value)
		{
			//取得附件信息
			$att = $SDB->query("SELECT * FROM {$source_prefix}attachments WHERE pid = ".$p->Fields['pid']->value);
			$attdata = array();
			while (!$att->EOF)
			{
				$tp = substr($att->Fields['filetype']->value, 0, strpos($att->Fields['filetype']->value, '/'));
				$filetype = $att->Fields['filetype']->value;
				switch ($tp)
				{
					case 'image':
						$filetype = 'img';
						$ifupload = 1;
						break;
					case 'text':
						$filetype = 'txt';
						$ifupload = 2;
						break;
					default:
						$filetype = 'zip';
						$ifupload = 3;
						break;
				}
				$attdata[$att->Fields['aid']->value] = array('aid'=>$att->Fields['aid']->value,'name'=>addslashes($att->Fields['attachment']->value),'type'=>$filetype,'attachurl'=>addslashes(str_replace('\\','/',$att->Fields['filename']->value)),'needrvrc'=>0,'size'=>round($att->Fields['filesize']->value/1024),'hits'=>$a['downloads'],'desc'=>addslashes($att->Fields['description']->value),'ifthumb'=>($filetype=='img' ? 0 : 1));
				$att->MoveNext();
			}
			$aid = addslashes(serialize($attdata));
		}
		$title = addslashes($p->Fields['title']->value);
		$message = addslashes(dz_ubb(str_replace($_dzface,$_pwface,$p->Fields['message']->value)));
		$ifconvert = (convert($message) == $message)? 1 : 2;
		if($p->Fields['layer']->value=='0')
		{
			$DDB->update("INSERT INTO {$pw_prefix}tmsgs (tid,aid,userip,ifsign,ifconvert,content) VALUES (".$p->Fields['tid']->value.",'".$aid."','".$p->Fields['ip']->value."',".$p->Fields['usesig']->value.",".((convert($message) == $message)? 1 : 2).",'".addslashes($message)."')");
			$DDB->update("Update {$pw_prefix}threads Set ifshield = ".$p->Fields['invisible']->value.", ifupload='".$ifupload."' Where tid = ".$p->Fields['tid']->value);
		}
		else
		{
			$DDB->update("INSERT INTO {$pw_prefix}posts (pid,fid,tid,aid,author,authorid,postdate,subject,userip,ifsign,buy,ifconvert,ifcheck,content,ifshield,anonymous) VALUES (".$p->Fields['pid']->value.",".$p->Fields['fid']->value.",".$p->Fields['tid']->value.",'".$aid."','".addslashes($p->Fields['poster']->value)."',".($p->Fields['posterid']->value == '-1' ? 0 : $p->Fields['posterid']->value).",".dt2ut($p->Fields['postdatetime']->value).",'".addslashes($title)."','".$p->Fields['ip']->value."',".$p->Fields['usesig']->value.",'',".$ifconvert.",".($p->Fields['invisible']->value ? 0 : 1).",'".$message."',".$p->Fields['invisible']->value.",".($p->Fields['posterid']->value == '-1' ? 1 : 0).")");
		}
		$s_c++;
		$p->MoveNext();
	}
	$row = $SDB->query("SELECT COUNT(*) as num FROM {$source_prefix}posts{$truetableid} WHERE layer <> 0 AND pid >= $end");

	if ($row->Fields['num']->value)
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
		$match = array();
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

	if (!$tid->Fields['num']->value)
	{
		report_log();
		newURL($step);
	}
	//"SELECT us.uid,uf.medals 		FROM {$source_prefix}users AS us INNER JOIN  {$source_prefix}userfields AS uf ON us.uid = uf.uid			WHERE (us.uid >= $start) AND (us.uid < $end)"
	$v = $SDB->query("SELECT p.*, t.postdatetime FROM {$source_prefix}polls as p LEFT JOIN {$source_prefix}topics as t ON p.tid = t.tid WHERE p.tid >= ".$start." And p.tid<=".$end."");
	$ipoll = '';
	while(!$v->EOF)
	{
		$votearray = array();
		$vop = $SDB->query("SELECT * FROM {$source_prefix}polloptions WHERE tid = ".$v->Fields['tid']->value." ORDER BY polloptionid");

		while(!$vop->EOF)
		{
			$voteuser = array();
			if ($vop->Fields['voternames']->value)
			{
				$vtname = explode(",",$vop->Fields['voternames']->value);
				foreach($vtname as $n => $vn)
				{
					$voteuser[] = $vn;
				}
			}
			$votearray[] = array($vop->Fields['polloption']->value,$vop->Fields['votes']->value);
			$vop->MoveNext();
		}
		//$votearray['multiple'] = array($v->Fields['multiple']->value,$v->Fields['maxchoices']->value);
		$votearray	= addslashes(serialize($votearray));
		$timelimit	= $v->Fields['expiration']->value ? (($v->Fields['expiration']->value - $v->Fields['postdatetime']->value) / 86400) : 0;

		$ipoll = "(".$v->Fields['tid']->value.",'{$votearray}',1,".(1^$v->Fields['visible']->value).",{$timelimit},{$v->Fields['multiple']->value},{$v->Fields['maxchoices']->value})";
		$DDB->update("INSERT INTO {$pw_prefix}polls (tid,voteopts,modifiable,previewable,timelimit,multiple,mostvotes) VALUES ".$ipoll);

		$s_c++;
		$v->MoveNext();
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
	global $pw_prefix, $source_prefix, $SDB, $DDB, $dest_charset;
	require_once S_P.'lang_'.$dest_charset.'.php';

	$DDB->update("TRUNCATE TABLE {$pw_prefix}usergroups");
	$DDB->update($lang['group']);

	$specialdata = $_newgroup = array();
	$rs = $SDB->query("SELECT * FROM {$source_prefix}usergroups WHERE system = 0");
	while (!$rs->EOF)
	{
		if ($rs->Fields['radminid']->value)
		{
			$specialdata[$rs->Fields['groupid']->value] = '1';
			$gptype = 'special';
		}
		else
		{
			$gptype = 'member';
		}
		pwGroupref(array('gid'=>$rs->Fields['groupid']->value,'gptype'=>$gptype,'grouptitle'=>$rs->Fields['grouptitle']->value,'grouppost'=>$rs->Fields['creditslower']->value,'maxmsg'=>$rs->Fields['maxpmnum']->value,'allowhide'=>$rs->Fields['allowinvisible']->value,'allowread'=>$rs->Fields['readaccess']->value ? 1 : 0,'allowportait'=>$rs->Fields['allowavatar']->value ? 1 : 0,'upload'=>$rs->Fields['allowavatar']->value==3 ? 1 : 0,'allowrp'=>$rs->Fields['allowreply']->value,'allowhonor'=>$rs->Fields['allowcstatus']->value,'allowdelatc'=>1,'allowpost'=>$rs->Fields['allowpost'],'allownewvote'=>$rs->Fields['allowpostpoll']->value,'allowvote'=>$rs->Fields['allowvote']->value,'htmlcode'=>$rs->Fields['allowhtml']->value,'allowhidden'=>$rs->Fields['allowhidecode']->value,'allowencode'=>$rs->Fields['allowsetreadperm']->value,'allowsearch'=>$rs->Fields['allowsearch']->value,'allowprofile'=>$rs->Fields['allowviewpro']->value,'allowreport'=>1,'allowmessage'=>1,'allowsort'=>$rs->Fields['allowviewstats']->value,'alloworder'=>1,'allowupload'=>$rs->Fields['allowpostattach']->value,'allowdownload'=>$rs->Fields['allowgetattach']->value,'allowloadrvrc'=>$rs->Fields['allowsetattachperm']->value,'allownum'=>50,'edittime'=>0,'postpertime'=>0,'searchtime'=>10,'signnum'=>$rs->Fields['maxsigsize']->value));

		$grouptitle=getGrouptitle($rs->Fields['groupid']->value,$rs->Fields['grouptitle']->value,false);
		$DDB->update("INSERT INTO {$pw_prefix}usergroups (gid,gptype,grouptitle,groupimg,grouppost) VALUES ('".$rs->Fields['groupid']->value."','$gptype','$grouptitle','8','".$rs->Fields['creditslower']->value."')");

		$gpid=$rs->Fields['groupid']->value;
		$_newgroup[$gpid] = $DDB->insert_id();
		$rs->MoveNext();
	}
	//写入配置信息
	$specialdatastr = "\$_specialdata = ".pw_var_export($specialdata).";";
	writeover(S_P.'tmp_specialdatastr.php', $specialdatastr,true);
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
	$content = preg_replace(array('~\[media=mp3,\d+?,\d+?,(?:1|0)\](.+?)\[\/media\]~i','~\[media=(?:wmv|mov),(\d+?),(\d+?),(1|0)\](.+?)\[\/media\]~i','~\[media=(rm|ra),(\d+?),(\d+?),(1|0)\](.+?)\[\/media\]~i','~\[hide\](.+?)\[\/hide\]~is','~\[localimg=[0-9]+,[0-9]+\]([0-9]+)\[\/localimg\]~is','~\[local\]([0-9]+)\[\/local\]~is','~\[attach\]([0-9]+)\[\/attach\]~is','/\[img=[0-9]+,[0-9]+\]/i','/\[size=(\d+(\.\d+)?(px|pt|in|cm|mm|pc|em|ex|%)+?)\]/i'),array('[wmv=0]\\1[/wmv]','[media=wmv,\\1,\\2,\\3]\\4[/media]','[media=\\1,\\2,\\3,\\4]\\5[/media]','[post]\\1[/post]','[attachment=\\1]','[attachment=\\1]','[attachment=\\1]','[img]',''),$content);
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