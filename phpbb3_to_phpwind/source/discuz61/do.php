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
	$SDB = new mysql($source_db_host, $source_db_user, $source_db_password, $source_db_name, $source_charset);
}

if ($step == 1)
{
	//论坛设置
	$DDB->query("TRUNCATE TABLE {$pw_prefix}wordfb");//
	require_once S_P.'function.php';
	$siteConfig = array(
		'bbname'=>'db_bbsname',
		'siteurl'=>array('db_bbsurl','dz_url'),
		'icp'=>array('db_icp','dz_icp'),
		'bbclosed'=>array('db_bbsifopen','dz_siteopen'),
		'closedreason'=>'db_whybbsclose',
		'regadvance'=>'rg_regdetail',
		'censoruser'=>array('rg_banname','dz_banname'),
		'regverify'=>array('rg_emailcheck','dz_regcheck'),
		'censoremail'=>array('rg_email','dz_regemail'),
		'regctrl'=>'rg_allowsameip',
		'bbrules'=>'rg_reg',
		'frameon'=>'db_columns',
		'seotitle'=>'db_bbstitle',
		'seokeywords'=>'db_metakeyword',
		'seodescription'=>'db_metadescrip',);

	$query = $SDB->query("SELECT variable, value FROM {$source_prefix}settings WHERE variable IN ('".implode('\',\'', array_keys($siteConfig))."')");

	while ($s = $SDB->fetch_array($query))
	{
		if (is_array($siteConfig[$s['variable']]))
		{
			$db_value = $siteConfig[$s['variable']][1]($s['value']);
			$db_name = $siteConfig[$s['variable']][0];
		}
		else
		{
			$db_value = $s['value'];
			$db_name = $siteConfig[$s['variable']];
		}
		$DDB->update("UPDATE {$pw_prefix}config SET db_value = '".addslashes($db_value)."' WHERE db_name = '$db_name'");
		$s_c++;
	}

	$historyposts = $SDB->get_one("SELECT value FROM {$source_prefix}settings WHERE variable = 'historyposts'");
	$historyposts = $historyposts['value'] ? explode("\t", $historyposts['value']) : array();

	$onlinerecord = $SDB->get_one("SELECT value FROM {$source_prefix}settings WHERE variable = 'onlinerecord'");
	$onlinerecord = $onlinerecord['value'] ? explode("\t", $onlinerecord['value']) : array();

	$DDB->update("UPDATE {$pw_prefix}bbsinfo SET higholnum = '".(int)$onlinerecord[0]."', higholtime = '".(int)$onlinerecord[1]."', yposts = '".(int)$historyposts[0]."', hposts = '".(int)$historyposts[1]."' WHERE id = 1");
	$DDB->update("UPDATE {$pw_prefix}config SET db_value = 1 WHERE db_name IN ('db_topped','db_gdcheck')");
	$DDB->update("UPDATE {$pw_prefix}config SET db_value = 600 WHERE db_name IN ('db_signheight')");
	$DDB->update("UPDATE {$pw_prefix}config SET db_value = 3 WHERE db_name = 'db_attachdir'");
	$s_c += 4;

	$query = $SDB->query("SELECT find,replacement FROM {$source_prefix}words");
	while ($b = $SDB->fetch_array($query))
	{
		$replacement = '';
		switch ($b['replacement'])
		{
			case '{MOD}':
				$type = 2;
				break;
			case '{BANNED}':
				$type = 1;
				break;
			default:
				$type = 0;
				$replacement = addslashes($b['replacement']);
				break;
		}
		$DDB->update("INSERT INTO {$pw_prefix}wordfb (word,wordreplace,type) VALUES ('".addslashes(preg_replace('~{\d+?}~is','',$b['find']))."','$replacement',$type)");
		$s_c++;
	}
	report_log();
	newURL($step);
}
elseif ($step == 2)
{
	//表情
	$_pwface = $_dzface = array();
	$DDB->query("TRUNCATE TABLE {$pw_prefix}smiles");
	$query = $SDB->query("SELECT typeid,directory,name,displayorder FROM {$source_prefix}imagetypes WHERE type = 'smiley'");
	while ($s = $SDB->fetch_array($query))
	{
		$DDB->update("INSERT INTO {$pw_prefix}smiles (id,path,name,vieworder,type) VALUES (".$s['typeid'].",'".addslashes($s['directory'])."','".addslashes($s['name'])."','".$s['displayorder']."',0)");
		$s_c++;
	}

	$query = $DDB->query("SELECT id,path,name,vieworder FROM {$pw_prefix}smiles");
	while ($i = $DDB->fetch_array($query))
	{
		$query2 = $SDB->query("SELECT displayorder,code,url FROM {$source_prefix}smilies WHERE typeid = ".$i['id']);
		while($s = $SDB->fetch_array($query2))
		{
			$DDB->update("INSERT INTO {$pw_prefix}smiles (path,vieworder,type) VALUES('".addslashes($s['url'])."',".$s['displayorder'].",".$i['id'].")");
			$_pwface[] = '[s:'.$DDB->insert_id().']';
			$_dzface[] = $s['code'];
		}
	}
	writeover(S_P.'tmp_face.php', "\$_pwface = ".pw_var_export($_pwface).";\n\$_dzface = ".pw_var_export($_dzface).";", true);
	report_log();
	newURL($step);
}
elseif ($step == 3)
{
	//勋章
	$DDB->query("TRUNCATE TABLE {$pw_prefix}medal_info");
	$DDB->query("TRUNCATE TABLE {$pw_prefix}medalslogs");
	$query = $SDB->query("SELECT medalid,name,image FROM {$source_prefix}medals");
	while ($m = $SDB->fetch_array($query))
	{
		$DDB->update("INSERT INTO {$pw_prefix}medal_info (medal_id,name,descrip,image,type) VALUES (".$m['medalid'].",'".addslashes($m['name'])."','".addslashes($m['name'])."','".addslashes($m['image'])."','2')");
		$s_c++;
	}

    $lastid = $start;
    $medallog = array();
	$query = $SDB->query("SELECT * FROM {$source_prefix}medallog m");
	while ($l = $SDB->fetch_array($query))
	{
        $awardee = $SDB->get_value("SELECT username FROM {$source_prefix}members WHERE uid=".$l['id']);
        $timelimit = floor(($l['expiration'] - $l['dateline']) / 60*60*24*30);
        //$timelimit = $l['expiration'];
        $l['status'] = 1;
        $medallog[] = "(".$l['id'].",'$awardee','$awarder','".$l['dateline']."','$timelimit','','".$l['medalid']."','".$l['status']."','')";
		$s_c++;
	}
    if($medallog){
        $medallogarr = implode(",",$medallog);
	    $DDB->update("INSERT INTO {$pw_prefix}medalslogs (id,awardee,awarder,awardtime,timelimit,state,level,action,why) VALUES $medallogarr");
    }
	report_log();
	newURL($step, '&medal=yes');
}
elseif ($step == '4')
{
	//会员
	$insertadmin = '';
	$_specialgroup = array();

	require_once (S_P.'tmp_credit.php');
	require_once (S_P.'tmp_uc.php');//uc表
	if (!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}members");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}memberdata");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}membercredit");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}memberinfo");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}administrators");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}banuser");

		$DDB->query("TRUNCATE TABLE {$pw_prefix}credits");
		foreach ($expandCredit as $v)
		{
			$DDB->update("INSERT INTO {$pw_prefix}credits (name,unit) VALUES ('".addslashes($v[0])."','".addslashes($v[1])."')");
		}

        if(is_array($expandMember))//自定义用户栏目
        {
            foreach ($expandMember as $v)
            {
                $DDB->update("INSERT INTO {$pw_prefix}customfield (id,title) VALUES ('".addslashes($v[1])."','".addslashes($v[0])."')");
                $DDB->update("ALTER TABLE {$pw_prefix}memberinfo ADD field_".addslashes($v[1])." CHAR(50) ".$DDB->collation()." NOT NULL DEFAULT ''");
            }
        }

		writeover(S_P.'tmp_group.php', "\$_specialgroup = ".pw_var_export(changegroups()).";", true); //更新用户组并保存特殊组到临时文件

		//更改数据库结构
		$addfields = TRUE;
		$query = $DDB->query("SHOW COLUMNS FROM {$pw_prefix}members");
		while ($mc = $DDB->fetch_array($query))
		{
			if (strpos(strtolower($mc['Field']), 'salt') !== FALSE)
			{
				$addfields = FALSE;
				break;
			}
		}
		$addfields && $DDB->update("ALTER TABLE {$pw_prefix}members ADD salt CHAR( 6 ) ".$DDB->collation()." NOT NULL DEFAULT ''");
	}
    //增加uc
	$charset_change = 1;
	$UCDB = new mysql($uc_db_host, $uc_db_user, $uc_db_password, $uc_db_name, '');

	require_once (S_P.'ubb.php');
	require_once (S_P.'tmp_group.php');

    $querysql = '';
    if(is_array($expandMember))//自定义用户栏目
    {
        foreach ($expandMember as $k => $v)
        {
            $querysql .= ",mf.field_".$v[1];
        }
    }
	$query = $SDB->query("SELECT m.uid,m.username,m.password,m.secques,m.gender,m.adminid,m.groupid,m.groupexpiry,m.extgroupids,m.regip,m.regdate,m.lastip,m.lastvisit,m.lastactivity,m.lastpost,m.posts,m.digestposts,m.oltime,m.pageviews,m.credits,m.extcredits1,m.extcredits2,m.extcredits3,m.extcredits4,m.extcredits5,m.extcredits6,m.extcredits7,m.extcredits8,m.email,m.bday,m.sigstatus,m.tpp,m.ppp,m.styleid,m.dateformat,m.timeformat,m.pmsound,m.showemail,m.newsletter,m.invisible,m.timeoffset,m.newpm,m.accessmasks,m.editormode,
			mf.nickname,mf.site,mf.alipay,mf.icq,mf.qq,mf.yahoo,mf.msn,mf.taobao,mf.location,mf.customstatus,mf.medals,mf.avatar,mf.avatarwidth,mf.avatarheight,mf.bio,mf.sightml,mf.ignorepm,mf.groupterms,mf.authstr,mf.spacename,mf.buyercredit,mf.sellercredit".$querysql.",
			ol.thismonth, ol.total
			FROM {$source_prefix}members m
			LEFT JOIN {$source_prefix}memberfields mf USING(uid)
			LEFT JOIN {$source_prefix}onlinetime ol USING(uid)
			WHERE m.uid >= $start ORDER BY uid LIMIT $percount");

	//禁言会员处理数据初始化
	$banusersql = $banuids = array();

	while ($m = $SDB->fetch_array($query))
	{
		Add_S($m);
        $lastid = $m['uid'];
		if (!$m['uid'] || !$m['username'])
		{
			$f_c++;
			errors_log($m['uid']."\t".$m['username']);
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
			case '6':
			case '7'://禁止发言
				$groupid = '6';
				break;
			case '8'://未验证会员
				$groupid = '7';
				break;
			default :
				$groupid = $_specialgroup[$m['groupid']] ? $m['groupid'] : '-1';
				break;
		}

		if($groupid == '6')
		{
			//禁言会员处理
			$timestamp=time();
			if ($m['groupexpiry'])
			{
				if($m['groupexpiry'] > $timestamp)
				{
					$days = ceil(($m['groupexpiry'] - $timestamp)/86400);
					$banusersql[] = array($m['uid'],0,1,$timestamp,$days,'','');
					$banuids[]    = $m['uid'];
				}
			}
			else
			{
				$banusersql[] = array($m['uid'],0,2,$timestamp,0,'','');
				$banuids[]    = $m['uid'];
			}
		}

		//会员积分处理
		eval($creditdata);
		$expandCreditSQL = '';
		if($expandCredit)
		{
			foreach ($expandCredit as $k => $v)
			{
				$expandCreditSQL .= '('.$m['uid'].','.($k + 1).','.(int)($m[$v[2]]).'),';
			}
			$expandCreditSQL && $DDB->update("INSERT INTO {$pw_prefix}membercredit (uid, cid, value) VALUES ".substr($expandCreditSQL, 0, -1));
		}

        //自定义用户栏目 处理
		$expandMemberSQL1 = '';
		$expandMemberSQL2 = '';
		if($expandMember)//自定义积分
		{
			foreach ($expandMember as $k => $v)
			{
				$expandMemberSQL1 .= ",field_".$v[1];
				$expandMemberSQL2 .= ",'".$m["field_$v[1]"]."'";
			}
			$expandMemberSQL1 && $DDB->update("INSERT INTO {$pw_prefix}memberinfo (uid".$expandMemberSQL1.") VALUES (".$m['uid'].$expandMemberSQL2.")");
		}

		$timedf = ($m['timeoffset'] == '9999') ? '0' : $m['timeoffset'];
		list($introduce,) = explode("\t", $m['bio']);
		$editor = ($m['editormode'] == '1') ? '1' : '0';
		$userface = $banpm = '';
		if ($m['avatar'])
		{
			$avatarpre = substr($m['avatar'], 0, 7);
			switch ($avatarpre)
			{
				case 'http://':
					$userface = $m['avatar'].'|2|'.$m['avatarwidth'].'|'.$m['avatarheight'];
					break;
				case 'images/':
					$userface = substr($m['avatar'],strrpos($m['avatar'],'/')+1).'|1';
					break;
				case 'customa':
					$userface = substr($m['avatar'],strrpos($m['avatar'],'/')+1).'|3|'.$m['avatarwidth'].'|'.$m['avatarheight'];
					break;
			}
		}
		$m['sightml'] = addslashes(html2bbcode(stripslashes($m['sightml'])));
		$signchange  = ($m['sightml'] == convert($m['sightml'])) ? 1 : 2;
		$userstatus=($signchange-1)*256+128+$m['showemail']*64+4;//用户位状态设置
		//$medals = $m['medals'] ? str_replace("\t", ',', $m['medals']) : '';

		$medals = '';//勋章add by zhaojun 100317
        if($m['medals']){
            $medals = '';
            $medalarr = explode("\t",$m['medals']);
            if($medalarr){
                foreach($medalarr as $v){
                    if(strpos($v,"|")!=false){
                        /*原来的勋章是15|1279036800这样的话，新的就获取不到了就会出问题了*/
                        $v = substr($v,0,strpos($v,"|"));
                    }
                    $medals .= $v.',';
                    if($v!=''){
                        $medaluser[] = "(".$m['uid'].",".$v.")";
                    }
                }
                $medals = substr($medals,0,-1);
            }
        }

		//密码
		$uc = $UCDB->get_one("SELECT m.password,m.salt,mf.blacklist FROM {$uc_db_prefix}members m LEFT JOIN {$uc_db_prefix}memberfields mf USING (uid) WHERE m.uid = '".$m['uid']."'");
		$uc['blacklist'] && $uc['blacklist'] != '{ALL}' && $banpm = $uc['blacklist'];

		$m['newpm'] = $m['newpm'] ? $m['newpm'] : 0;
		$m['ignorepm'] && $m['ignorepm'] != '{ALL}' && $banpm = $m['ignorepm'];

		unset($membersql);
		unset($memdatasql);
		$membersql  = "(".$m['uid'].",'".$m['username']."','".$uc['password']."','".$m['email']."',".$groupid.",'".addslashes($m['extgroupids'])."','".$userface."','".$m['gender']."',".$m['regdate'].",'".$m['sightml']."','".$introduce."','".$m['qq']."','".$m['icq']."','".$m['msn']."','".$m['yahoo']."','".$m['site']."','".$m['location']."','".$m['customstatus']."','".$m['bday']."','".$timedf."','".$m['tpp']."','".$m['ppp']."',".$m['newpm'].",'$banpm','$medals','".$userstatus."','".$uc['salt']."')";
		$memdatasql = "(".$m['uid'].",".$m['posts'].",".$m['digestposts'].",".$rvrc.",".$money.",".$credit.",".$currency.",'".$m['lastvisit']."','".$m['lastactivity']."','".$m['lastpost']."','".intval($m['total']*60)."','".intval($m['thismonth']*60)."')";

		if($membersql)
		{
			$DDB->update("REPLACE INTO {$pw_prefix}members (uid,username,password,email,groupid,groups,icon,gender,regdate,signature,introduce,oicq,icq,msn,yahoo,site,location,honor,bday,timedf,t_num,p_num,newpm,banpm,medals,userstatus,salt) VALUES $membersql ");
		}

		if($memdatasql)
		{
			$DDB->update("REPLACE INTO {$pw_prefix}memberdata (uid,postnum,digests,rvrc,money,credit,currency,lastvisit,thisvisit,lastpost,onlinetime,monoltime) VALUES $memdatasql ");
		}

		$s_c++;
	}

	//禁言会员处理
	if ($banusersql)
	{
		$DDB->update("REPLACE INTO {$pw_prefix}banuser (uid,fid,type,startdate,days,admin,reason) VALUES ".pwSqlMulti($banusersql));
	}
	if ($banuids)
	{
		$DDB->update("UPDATE {$pw_prefix}members SET groupid='6' WHERE uid IN (".pwImplode($banuids).") AND groupid!=6");
	}

    //系统组
    if($insertadmin){
        $DDB->update("REPLACE INTO {$pw_prefix}administrators (uid,username,groupid) VALUES ".substr($insertadmin, 0, -1));
    }

    //勋章
	if($medaluser)
	{
		$medaluserstr = implode(",",$medaluser);
		//$DDB->update("REPLACE INTO {$pw_prefix}medal_user (uid,mid) VALUES $medaluserstr ");
	}

	$maxid = $SDB->get_value("SELECT max(uid) FROM {$source_prefix}members");
    echo '最大id',$maxid.'<br>最后id',$lastid;
    if ($lastid < $maxid)
	{
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c.'&medal='.$medal);
	}
	else
	{
		report_log('&medal='.$medal);
		newURL($step);
		exit();
	}
}
elseif ($step == '5')
{
	//板块
	$DDB->query("TRUNCATE TABLE {$pw_prefix}forums");
	$DDB->query("TRUNCATE TABLE {$pw_prefix}forumdata");
	$DDB->query("TRUNCATE TABLE {$pw_prefix}forumsextra");
	$DDB->query("ALTER TABLE {$pw_prefix}forums CHANGE descrip descrip TEXT ".$DDB->collation()." NOT NULL");
	$DDB->query("TRUNCATE TABLE {$pw_prefix}topictype");//75新增主题分类表

	require_once S_P.'tmp_grelation.php';
	$catedb = $insertforumsextra = $typearray = array();
	$fright = array('viewperm'=>'allowvisit','postperm'=>'allowpost','replyperm'=>'allowrp','postattachperm'=>'allowupload','getattachperm'=>'allowdownload');
	$insertforums = $insertforumdata = $forumsextra = '';

	$query = $SDB->query("SELECT f.fid,f.fup,f.type,f.name,f.status,f.displayorder,f.styleid,f.threads,f.posts,f.todayposts,f.lastpost,f.allowsmilies,f.allowhtml,f.allowbbcode,f.allowimgcode,f.allowmediacode,f.allowanonymous,f.allowshare,f.allowpostspecial,f.allowspecialonly,f.alloweditrules,f.recyclebin,f.modnewposts,f.jammer,f.disablewatermark,f.inheritedmod,f.autoclose,f.forumcolumns,f.threadcaches,f.alloweditpost,f.simple,
			fd.description,fd.password,fd.icon,fd.postcredits,fd.replycredits,fd.getattachcredits,fd.postattachcredits,fd.digestcredits,fd.redirect,fd.attachextensions,fd.formulaperm,fd.moderators,fd.rules,fd.threadtypes,fd.viewperm,fd.postperm,fd.replyperm,fd.getattachperm,fd.postattachperm,fd.keywords,fd.supe_pushsetting,fd.modrecommend,fd.tradetypes,fd.typemodels
			FROM {$source_prefix}forums f
			LEFT JOIN {$source_prefix}forumfields fd
			USING(fid)");

	while($f = $SDB->fetch_array($query))
	{
		$catedb[$f['fid']] = $f;
	}

	foreach($catedb as $fid => $forum)
	{
		$upadmin = $t_type = $addtpctype = $lastpost = '';

		$f_tmp = parent_upfid($forum['fid'],'fup',0);
		$childid = (int)parent_ifchildid($forum['fid'],'fup');
		$ifsub = ($f_tmp[0] == 'sub') ? 1 : 0;
		$ftype = $f_tmp[0];

		$forumadmin = $forum['moderators'] ? ','.str_replace("\t",',', $forum['moderators']).',' : '';

		getupadmin($forum['fup'], $upadmin);

		if ($forum['threadtypes'])
		{
			$threadtypes = unserialize($forum['threadtypes']);
			$addtpctype = (int)$threadtypes['prefix'];
			$t_type .= ($threadtypes['required'] ? '2' : '1')."\t";
            $order = 0;
			foreach ($threadtypes['types'] as $kk => $vv)
			{
				$t_type .= $vv."\t";
				//$typearray[$fid][$kk] = $i++;
                $pw_topictype['fid'] = $forum['fid'];
                $pw_topictype['name'] = $vv;
                $pw_topictype['vieworder'] = $order;
                $DDB->update("INSERT INTO {$pw_prefix}topictype SET ".pwSqlSingle($pw_topictype));
			    $topictypeid = $DDB->insert_id();
			    $typearray[$forum['fid']][$kk] = $topictypeid;//用新的
                $order++;
			}
			$t_type = rtrim($t_type);
		}

		$f_check = ($forum['modnewposts'] == '2') ? '3' : (int)$forum['modnewposts'];

		$modrecommend = unserialize($forum['modrecommend']);
		$simple = sprintf('%08b', $forum['simple']);

		$orderfield = bindec($simple{0}.$simple{1});
		switch ($orderfield)
		{
			case '0':
				$insertforumsextra['orderway'] = 'lastpost';
				break;
			case '1':
				$insertforumsextra['orderway'] = 'postdate';
				break;
			case '2':
				$insertforumsextra['orderway'] = 'replies';
				break;
			default:
				$insertforumsextra['orderway'] = 'hits';
				break;
		}

		$insertforumsextra['asc'] = $simple{2} ? 'ASC' : 'DESC';

		$showsub = (bindec($simple{3}.$simple{4}) == '2') ? 0 : 1;

		$viewsub = $forum['simple'] & 1;

		if ($forum['icon'] && ($forum['icon']{0} == '/' || $forum['icon']{0} == '\\'))  {$forum['icon'] = substr($forum['icon'],1);}

		$allowvisit = $allowpost = $allowrp = $allowupload = $allowdownload = '';
		foreach ($fright as $skey => $sright)
		{
			if ($forum[$skey])
			{
				$tmp_right = explode("\t", $forum[$skey]);
				foreach ($tmp_right as $ssright)
				{
					if ($ssright)
					{
						$$sright .= ($$sright ? ',' : '').$_grelation[$ssright];
					}
				}
			}
		}
		//修改版块权限问题
        if(strpos($allowvisit,",")!==false && strpos($allowvisit,",")!=0)
        {
		  	$allowvisit=",".$allowvisit;
		}
        if(strpos($allowpost,",")!==false && strpos($allowpost,",")!=0)
        {
		  	$allowpost=",".$allowpost;
		}
        if(strpos($allowrp,",")!==false && strpos($allowrp,",")!=0)
        {
		  	$allowrp=",".$allowrp;
		}
        if(strpos($allowdownload,",")!==false && strpos($allowdownload,",")!=0)
        {
		  	$allowdownload=",".$allowdownload;
		}
        if(strpos($allowupload,",")!==false && strpos($allowupload,",")!=0)
        {
		  	$allowupload=",".$allowupload;
		}

		$insertforums = "(".$forum['fid'].",".$forum['fup'].",$ifsub,$childid,'".$ftype."','".addslashes($forum['icon'])."','".addslashes(str_replace('"','&quot;',$forum['name']))."','".addslashes($forum['description'])."','".addslashes($forum['keywords'])."',".$forum['displayorder'].",'".addslashes($forumadmin)."','".addslashes($upadmin)."',".$forum['forumcolumns'].",31,".$forum['jammer'].",'".($forum['password'] ? md5($forum['password']) : '')."',{$viewsub},'".$allowvisit."','".$allowpost."','".$allowrp."','".$allowdownload."','".$allowupload."',{$f_check},'".addslashes($t_type)."',{$showsub})";

		if ($forum['lastpost'])
		{
			list($ltid, $ltitle, $ltime, $lauthor) = explode("\t", $forum['lastpost']);
			$lastpost = addslashes($ltitle."\t".$lauthor."\t".$ltime."\tread.php?tid=".$ltid);
		}

		$insertforumdata = "(".$forum['fid'].",'".$forum['todayposts']."','".$forum['threads']."','".$forum['posts']."','".$lastpost."')";
		$insertforumsextra['link'] = $forum['redirect'];
		$insertforumsextra['lock'] = $insertforumsextra['cutnums'] = $insertforumsextra['threadnum'] = $insertforumsextra['readnum'] = $insertforumsextra['newtime'] = $insertforumsextra['allowencode'] = $insertforumsextra['inspect'] = $insertforumsextra['commend'] = $insertforumsextra['autocommend'] = $insertforumsextra['rvrcneed'] = $insertforumsextra['moneyneed'] = $insertforumsextra['creditneed'] = $insertforumsextra['postnumneed'] = 0;
		$insertforumsextra['commendlist'] = $insertforumsextra['forumsell'] = $insertforumsextra['uploadset'] = $insertforumsextra['rewarddb'] = $insertforumsextra['allowtime'] = '';
		$insertforumsextra['sellprice'] = array();
		$insertforumsextra['addtpctype'] = $addtpctype;
		$insertforumsextra['anonymous'] = $forum['allowanonymous'];
		$insertforumsextra['dig'] = $insertforumsextra['commend'] = $modrecommend['open'];
		$insertforumsextra['commendnum'] = $modrecommend['num'];
		$insertforumsextra['commendlength'] = $modrecommend['maxlength'];
		$insertforumsextra['commendtime'] = $modrecommend['cachelife'];
		$insertforumsextra['watermark'] = $forum['disablewatermark']^1;
		$insertforumsextra['lock'] = abs($forum['autoclose']);
		$insertforumsextra['cutnums'] = $modrecommend['maxlength'];
		$insertforumsextra['autocommend'] = $modrecommend['sort'] ? ($modrecommend['orderby'] + 1) : 0;
		$forumsextra = "(".$forum['fid'].",'','".addslashes(serialize($insertforumsextra))."','')";

		$DDB->update("INSERT INTO {$pw_prefix}forums (fid,fup,ifsub,childid,type,logo,name,descrip,keywords,vieworder,forumadmin,fupadmin,across,allowtype,copyctrl,password,viewsub,allowvisit,allowpost,allowrp,allowdownload,allowupload,f_check,t_type,showsub) VALUES ".$insertforums);
		$DDB->update("INSERT INTO {$pw_prefix}forumdata (fid,tpost,topic,article,lastpost) VALUES ".$insertforumdata);
		$DDB->update("INSERT INTO {$pw_prefix}forumsextra (fid,creditset,forumset,commend) VALUES ".$forumsextra);

		$s_c++;
	}
	writeover(S_P.'tmp_ttype.php', "\$_ttype = ".pw_var_export($typearray).";", true);
	report_log();
	newURL($step);
}

elseif ($step == '6')
{
	//主题
	$_ttype = $_pwface = $_dzface = '';
	$threadsql = $tmsgssql = '';
	require_once S_P.'tmp_ttype.php';
	require_once S_P.'tmp_face.php';
	require_once S_P.'tmp_credit.php';//多个

	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}threads");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}tmsgs");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}recycle");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}poststopped");
	}

	$query = $SDB->query("SELECT t.tid,t.iconid,t.subject,t.typeid,t.readperm,t.price,t.lastpost,t.lastposter,t.views,t.replies,t.displayorder,t.highlight,t.digest,t.special,t.attachment,t.subscribed,t.moderated,t.closed,t.itemid,
			p.pid,p.fid,p.first,p.author,p.authorid,p.dateline,p.message,p.useip,p.invisible,p.anonymous,p.usesig,p.htmlon,p.bbcodeoff,p.smileyoff,p.parseurloff,p.attachment,p.rate,p.ratetimes,p.status
			FROM {$source_prefix}threads t force index(PRIMARY)
			INNER JOIN {$source_prefix}posts p
			USING (tid)
			WHERE p.first = 1 AND t.tid > $start
			ORDER BY tid LIMIT $percount");

	while($t = $SDB->fetch_array($query))
	{
        $lastid = $t['tid'];
		if (!$t['fid'])
		{
			$f_c++;
			errors_log($t['fid']."\t".$t['pid']."\t".$t['subject']);
			continue;
		}
		switch ($t['special'])
		{
			case '1':
				$special = 1;//投票
				break;
			case '2':
				$special = 4;//交易
				break;
			case '3':
				$special = 3;//悬赏
				break;
			case '4':
				$special = 8;//活动
				break;
			default:
				$special = 0;//普通
				break;
		}
		$fid = $t['fid'];
		$ifcheck = '1';
		$topped = '0';
		switch ($t['displayorder'])
		{
			case -1://回收站
                $modtidsql[] = $t['tid'];
				$t['fid'] = 0;
				break;
			case -2://需要审核
				$ifcheck = 0;
				break;
			case 1:
				$topped = 1;
				break;
			case 2:
				$topped = 2;
				break;
			case 3:
				$topped = 3;
				break;
		}
        if($topped != '0'){
		    setForumsTopped($t['tid'],$t['fid'],$topped,0);
        }

		$titlefont = $tag = $aid = $ifupload = '';
		if($t['highlight'])
		{
			$title1 = $title2 = $title3 = $title4 = '';
			$string = sprintf('%02d', $t['highlight']);
			$stylestr = sprintf('%03b', $string[0]);
			$stylestr[0] && $title2 = '1';
			$stylestr[1] && $title3 = '1';
			$stylestr[2] && $title4 = '1';
			if ($string[1])
			{
				$colorarray = array('', 'red', 'orange', '#996600', 'green', 'cyan', 'blue', 'purple', 'gray');
				$title1 = $colorarray[$string[1]];
			}
			$titlefont = "$title1~$title2~$title3~$title4~~~";
		}

        $ifupload = $t['attachment'];
		$t['typeid'] = (int)$_ttype[$fid][$t['typeid']];
		$t['message'] = addslashes(dz_ubb(str_replace($_dzface,$_pwface,$t['message'])));
		$ifcheck = $t['invisible'] < 0 ? '0' : '1';
		$ifmark=$t['rate'];//评分

        if(!$speed){//0
            $threadsql = "(".$t['tid'].",".$t['fid'].",'".addslashes($titlefont)."','".addslashes($t['author'])."',".$t['authorid'].",'".addslashes($t['subject'])."','$ifcheck',".$t['typeid'].",".$t['dateline'].",".$t['lastpost'].",'".addslashes($t['lastposter'])."',".$t['views'].",".$t['replies'].",{$topped},".$t['closed'].",".$t['digest'].",{$special},'".$ifupload."',".$t['subscribed'].",'".$ifmark."',".$t['status'].",".$t['anonymous'].",0)";
            $tmsgssql  = "(".$t['tid'].",'".$t['attachment']."','".$t['useip']."',".$t['usesig'].",'','','".addslashes($tag)."',".((convert($t['message']) == $t['message'])? 1 : 2).",'".$t['message']."','".$ifmark."')";

            if($threadsql)
            {
                $DDB->update("REPLACE INTO {$pw_prefix}threads (tid,fid,titlefont,author,authorid,subject,ifcheck,type,postdate,lastpost,lastposter,hits,replies,topped,locked,digest,special,ifupload,ifmail,ifmark,ifshield,anonymous,ptable) VALUES $threadsql ");
            }
            if($tmsgssql)
            {
                $DDB->update("REPLACE INTO {$pw_prefix}tmsgs (tid,aid,userip,ifsign,buy,ipfrom,tags,ifconvert,content,ifmark) VALUES $tmsgssql ");
            }
        }

        if($speed){//1
            $threadsql[] = "(".$t['tid'].",".$t['fid'].",'".addslashes($titlefont)."','".addslashes($t['author'])."',".$t['authorid'].",'".addslashes($t['subject'])."','$ifcheck',".$t['typeid'].",".$t['dateline'].",".$t['lastpost'].",'".addslashes($t['lastposter'])."',".$t['views'].",".$t['replies'].",{$topped},".$t['closed'].",".$t['digest'].",{$special},'".$ifupload."',".$t['subscribed'].",'".$ifmark."',".$t['status'].",".$t['anonymous'].")";
            $tmsgssql[] = "(".$t['tid'].",'".$t['attachment']."','".$t['useip']."',".$t['usesig'].",'','','".addslashes($tag)."',".((convert($t['message']) == $t['message'])? 1 : 2).",'".$t['message']."','".$ifmark."')";
        }

		$s_c++;
	}

    if($speed){//1
		if($threadsql)
		{
		    $threadsqlstr = implode(",",$threadsql);
			$DDB->update("REPLACE INTO {$pw_prefix}threads (tid,fid,titlefont,author,authorid,subject,ifcheck,type,postdate,lastpost,lastposter,hits,replies,topped,locked,digest,special,ifupload,ifmail,ifmark,ifshield,anonymous) VALUES $threadsqlstr ");
		}
		if($tmsgssql)
		{
		    $tmsgssqlstr = implode(",",$tmsgssql);
			$DDB->update("REPLACE INTO {$pw_prefix}tmsgs (tid,aid,userip,ifsign,buy,ipfrom,tags,ifconvert,content,ifmark) VALUES $tmsgssqlstr ");
		}
    }
    //回车站的帖子处理
    if($modtidsql){
        $modsql = array();
        $query_mod = $SDB->query("SELECT tm.tid,tm.username,tm.dateline,t.fid FROM {$source_prefix}threadsmod tm LEFT JOIN {$source_prefix}threads t USING(tid) WHERE tm.tid in (".implode(",",$modtidsql).") AND tm.action = 'DEL'");

        while($threadmod = $SDB->fetch_array($query_mod)){
            $modsql[] = "(0,".$threadmod['tid'].",".$threadmod['fid'].",'".$threadsmod['dateline']."','".addslashes($threadsmod['username'])."')";
        }
        if($modsql)
        {
            $modsqlstr = implode(",",$modsql);
            $DDB->update("REPLACE INTO {$pw_prefix}recycle (pid,tid,fid,deltime,admin) VALUES $modsqlstr ");
        }
    }

	$maxid = $SDB->get_value("SELECT max(tid) FROM {$source_prefix}threads");
    echo '最大id',$maxid;
    echo '最后id',$lastid;
	if ($lastid < $maxid)
	{
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
		report_log();
		newURL($step);
		exit();
	}
}
elseif ($step == '7')
{
	//交易
	$tid = $SDB->get_one("SELECT tid FROM {$source_prefix}trades LIMIT $start, 1");
	if (!$tid)
	{
		report_log();
		newURL($step);
	}

	$query = $SDB->query("SELECT t.*, p.message
							FROM {$source_prefix}trades t
							INNER JOIN {$source_prefix}posts p
							USING (tid)
							WHERE p.first = 1 AND t.tid >= ".$tid['tid']."
							LIMIT $percount");

	while($a = $SDB->fetch_array($query))
	{
		$aidd=$SDB->get_one("SELECT filetype,filesize,attachment FROM {$source_prefix}attachments WHERE aid='".$a['aid']."'");
		list($ifupload,$aidd['filetype']) = getattfiletype($aidd['attachment']);
		$aid=serialize(array('type'=>$aidd['filetype'],'attachurl'=>$aidd['attachment'],'size'=>ceil($aidd['filesize']/1024)));
		$sql=array(
			'tid'=>$a['tid'],
			'uid'=>$a['sellerid'],
			'name'=>$a['subject'],
			'num'=>$a['amount'],
			'salenum'=>$a['transport'],
			'price'=>$a['price'],
			'costprice'=>$a['costprice'],
			'locus'=>$a['locus'],
			'mailfee'=>$a['ordinaryfee'],
			'expressfee'=>$a['expressfee'],
			'emsfee'=>$a['emsfee'],
			'deadline'=>$a['deadline']
		);
		$DDB->update("REPLACE INTO {$pw_prefix}trade SET ".pwSqlSingle($sql));
		$DDB->update("UPDATE {$pw_prefix}tmsgs SET aid = '$aid' WHERE tid = ".$a['tid']);
		$s_c++;
	}
	refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
}
elseif ($step == '8')
{
	//悬赏
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}reward");
	}

	$goon = 0;
	$query = $SDB->query("SELECT tid,authorid,answererid,dateline FROM {$source_prefix}rewardlog WHERE authorid <> 0 LIMIT $start, $percount");

	while($r = $SDB->fetch_array($query))
	{
		$goon ++;
		$rewardinfo = $SDB->get_one("SELECT t.price,p.author FROM {$source_prefix}posts p LEFT JOIN {$source_prefix}threads t USING (tid) WHERE p.tid = ".$r['tid']." ORDER BY p.dateline ASC LIMIT 1");
		if (!$rewardinfo)
		{
			$f_c ++;
			errors_log($r['tid']);
			continue;
		}
		if ($r['answererid'])
		{
			$reward_author = addslashes($rewardinfo['author']);
			$reward_pid = $r['answererid'];
			$DDB->update("UPDATE {$pw_prefix}threads SET state = 1 WHERE tid = ".$r['tid']);
		}
		else
		{
			$reward_author = $sql = '';
			$reward_pid = 0;
		}
		$DDB->update("REPLACE INTO {$pw_prefix}reward (tid,cbtype,catype,cbval,caval,timelimit,author,pid) VALUES (".$r['tid'].",'money','money','".abs($rewardinfo['price'])."',0,".($r['dateline']+864000).",'".$reward_author."',$reward_pid)");
		$s_c ++;
	}
	if ($goon == $percount)
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
	//投票
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}voter");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}polls");
	}
    $ipoll = $voterarrr = array();
	$query = $SDB->query("SELECT p.*, t.dateline FROM {$source_prefix}polls p LEFT JOIN {$source_prefix}threads t USING (tid) WHERE p.tid > $start ORDER BY p.tid LIMIT $percount");
	$ipoll = '';
	while($v = $SDB->fetch_array($query))
	{
        $lastid = $v['tid'];
		$votearray = array();
		$kk=0;
        $t_votes2 = 0;
		$vop = $SDB->query("SELECT * FROM {$source_prefix}polloptions WHERE tid = ".$v['tid']." ORDER BY polloptionid");
		while($rt = $SDB->fetch_array($vop))
		{
			$voteuser = array();
			if ($rt['voterids'])
			{
				$tmp_uids = explode("\t",$rt['voterids']);
				$uids = '';
				foreach ($tmp_uids as $uv)
				{
					if ($uv && strpos($uv,'.')!==FALSE) continue;
					$uids .= ($uids ? ',' : '').(int)$uv;
				}
				if ($uids)
				{
                    $t_votes = 0;
					$q2 = $DDB->query("SELECT uid,username FROM {$pw_prefix}members WHERE uid IN (".$uids.")");
                    ADD_S($q2);
					while($r2 = $SDB->fetch_array($q2))
					{
						ADD_S($r2);
						//$DDB->update("REPLACE INTO {$pw_prefix}voter (tid,uid,username,vote,time) VALUES ('{$v['tid']}','{$r2['uid']}','{$r2['username']}','$kk','0')");
                        $voterarrr[] = "('{$v['tid']}','{$r2['uid']}','{$r2['username']}','$kk','0')";
						$t_votes++;
						$t_votes2++;
					}
				}
			}
			$kk++;
			//$rt['votes'] = $votes;
			$votearray[] = array($rt['polloption'],$t_votes);
		}
		$votearray	= addslashes(serialize($votearray));
		$timelimit	= $v['expiration'] ? (($v['expiration'] - $v['dateline']) / 86400) : 0;
		//$ipoll = "(".$v['tid'].",'{$votearray}',1,".(1^$rt['visible']).",{$timelimit},{$v['multiple']},{$v['maxchoices']},$votes)";
		//$DDB->update("REPLACE INTO {$pw_prefix}polls (tid,voteopts,modifiable,previewable,timelimit,multiple,mostvotes,voters) VALUES ".$ipoll);
		$ipoll[] = "(".$v['tid'].",'{$votearray}',1,".(1^$rt['visible']).",{$timelimit},{$v['multiple']},{$v['maxchoices']},{$t_votes2})";
		$s_c++;
	}
    if($voterarrr){
        $DDB->update("REPLACE INTO {$pw_prefix}voter (tid,uid,username,vote,time) VALUES ".implode(",",$voterarrr));
    }
    if($ipoll){
        $DDB->update("REPLACE INTO {$pw_prefix}polls (tid,voteopts,modifiable,previewable,timelimit,multiple,mostvotes,voters) VALUES ".implode(",",$ipoll));
    }
    $maxid = $SDB->get_value("SELECT max(tid) FROM {$source_prefix}polls");
    if($lastid < $maxid)
    {
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
		report_log();
		newURL($step);
	}
}
elseif ($step == '911')
{
	//投票
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}voter");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}polls");
	}
	$percount=1000;
	$end =$start+$percount;
	$tid = $SDB->get_one("SELECT tid FROM {$source_prefix}polls LIMIT $start, 1");
	if (!$tid)
	{
		report_log();
		newURL($step);
	}
	$query = $SDB->query("SELECT p.*, t.dateline FROM {$source_prefix}polls p LEFT JOIN {$source_prefix}threads t USING (tid) WHERE p.tid >= ".$tid['tid']." LIMIT $percount");
	$ipoll = '';
	while($v = $SDB->fetch_array($query))
	{
		$votearray = array();
		$votes =$kk=0;
		$vop = $SDB->query("SELECT * FROM {$source_prefix}polloptions WHERE tid = ".$v['tid']." ORDER BY polloptionid");
		while($rt = $SDB->fetch_array($vop))
		{
			$voteuser = array();
			if ($rt['voterids'])
			{
				$tmp_uids = explode("\t",$rt['voterids']);
				$uids = '';
				foreach ($tmp_uids as $uv)
				{
					if ($uv && strpos($uv,'.')!==FALSE) continue;
					$uids .= ($uids ? ',' : '').(int)$uv;


				}
				if ($uids)
				{
					$q2 = $DDB->query("SELECT uid,username FROM {$pw_prefix}members WHERE uid IN (".$uids.")");
                    ADD_S($q2);
					while($r2 = $SDB->fetch_array($q2))
					{
						ADD_S($r2);
						$DDB->update("REPLACE INTO {$pw_prefix}voter (tid,uid,username,vote,time) VALUES ('{$v['tid']}','{$r2['uid']}','{$r2['username']}','$kk','0')");
						$votes++;
					}
				}
			}
			$kk++;
			$rt['votes'] = $rt['votes'] ? $rt['votes'] : 0;
			$votearray[] = array($rt['polloption'],$rt['votes']);
		}
		$votearray	= addslashes(serialize($votearray));
		$timelimit	= $v['expiration'] ? (($v['expiration'] - $v['dateline']) / 86400) : 0;
		$ipoll = "(".$v['tid'].",'{$votearray}',1,".(1^$rt['visible']).",{$timelimit},{$v['multiple']},{$v['maxchoices']},$votes)";
		$DDB->update("REPLACE INTO {$pw_prefix}polls (tid,voteopts,modifiable,previewable,timelimit,multiple,mostvotes,voters) VALUES ".$ipoll);
		$s_c++;
	}
	refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
}
elseif ($step == '10')
{
	//活动
	if(!$start)
	{
		$DDB->update("TRUNCATE TABLE {$pw_prefix}activitydefaultvalue");
	}
	$query = $SDB->query("SELECT a.*,t.fid,t.author,t.dateline FROM {$source_prefix}activities a LEFT JOIN {$source_prefix}threads t USING(tid) WHERE a.tid > $start LIMIT $percount");
    $lastid = $start;
	while($act = $SDB->fetch_array($query))
	{
        $lastid = $act['tid'];
        $fid =$act['fid'];

        //$acttid = $SDB->get_one("SELECT fid FROM $thread_table WHERE tid=".$act['tid']);
		//如果没有结束时间就自动加上10天
		if($act['starttimeto'] == 0)
		{
			//$act['starttimeto']=$act['starttimefrom']+864000;
		}
        $price[] = array(
            'condition' => '所有人',
            'money'     => $act['cost'],
        );
        $pwSQL = array(
            'tid'		        => $act['tid'],
            'fid'		        => $act['fid'],
            'actmid'		    => 1,
            'iscertified'       => 1,
            'signupstarttime'   => $act['dateline'],
            'signupendtime'	    => $act['expiration'],
            'starttime'		    => $act['starttimefrom'],
            'endtime'	        => $act['starttimefrom']+60*60*24*30,
            'location'		    => addslashes($act['place']),
            'contact'           => $act['author'],
            'telephone'         => $act['contact'],
            'maxparticipant'    => $act['number'],
            'genderlimit'       => $act['gender'],
            'userlimit'         => 1,
            'paymethod'         => 2,
            'fees'		        => serialize($price),
        );
        $tidarr[] = $act['tid'];
        $DDB->update("REPLACE INTO {$pw_prefix}activitydefaultvalue SET ".pwSqlSingle($pwSQL));
		$s_c++;
	}
    if($tidarr){
        //$DDB->update("UPDATE {$pw_prefix}threads SET special=22 WHERE tid in (".implode(',',$tidarr).")");
    }
    $maxid = $SDB->get_value("SELECT max(tid) FROM {$source_prefix}activities");
    if($maxid > $lastid)
    {
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
        report_log();
        newURL($step);
        exit();
	}
}
elseif ($step == '11')
{
	//活动参加者
	if(!$start)
	{
		$DDB->update("TRUNCATE TABLE {$pw_prefix}activitymembers");
	}
    $goon=0;
	$query = $SDB->query("SELECT * FROM {$source_prefix}activityapplies LIMIT $start,$percount");
	while($act = $SDB->fetch_array($query))
	{
        ADD_S($act);
        $goon++;
        $actarr[] = "(".$act['applyid'].",".$act['tid'].",".$act['uid'].",1,'".$act['username']."','a:1:{i:0;i:1;}',1,'".$act['contact']."','".$act['message']."',".$act['dateline'].")";
		$s_c++;
        //(2-$act['verified'])
	}
    if($actarr){
        $DDB->update("INSERT INTO {$pw_prefix}activitymembers (actuid,tid,uid,actmid,username,signupdetail,signupnum,mobile,message,signuptime) VALUES ".implode(",",$actarr));
    }
	if ($goon == $percount){
	    refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else{
		report_log();
		newURL($step);
    }
}
elseif ($step == '12')
{
	//回复
	$_ttype = $_pwface = $_dzface = '';
	require_once S_P.'tmp_ttype.php';
	require_once S_P.'tmp_face.php';
	require_once S_P.'tmp_credit.php';

	if(!$start)
	{
		$DDB->update("TRUNCATE TABLE {$pw_prefix}posts");
	}

	$query = $SDB->query("SELECT * FROM {$source_prefix}posts where pid >= $start order by pid LIMIT $percount");//用主键来搜索快
	while($p = $SDB->fetch_array($query))
	{
        $lastid = $p['pid'];
		if ($p['first']==1)//first
		{
			continue;
		}
		if (!$p['fid'] || !$p['tid'])//first
		{
			$f_c++;
			errors_log($p['pid']."\t".$p['fid']."\t".$p['tid']);
			continue;
		}
		$ifmark = $p['rate'];
		$p['subject'] = addslashes($p['subject']);
		$p['message'] = addslashes(dz_ubb(str_replace($_dzface,$_pwface,$p['message'])));
		$ifconvert = (convert($p['message']) == $p['message'])? 1 : 2;

        if(!$speed){//一条一条插
            $postsqlstr =  "(".$p['pid'].",".$p['fid'].",".$p['tid'].",'".$p['attachment']."','".addslashes($p['author'])."',".$p['authorid'].",".$p['dateline'].",'".$p['subject']."','".$p['useip']."',".$p['usesig'].",'',".$ifconvert.",".($p['invisible'] < 0 ? 0 : 1).",'".$p['message']."',".$p['status'].",".$p['anonymous'].",'".$ifmark."')";

            if($postsqlstr)
            {
                $DDB->update("REPLACE INTO {$pw_prefix}posts (pid,fid,tid,aid,author,authorid,postdate,subject,userip,ifsign,buy,ifconvert,ifcheck,content,ifshield,anonymous,ifmark) VALUES $postsqlstr ");
            }
        }

        if($speed){//批量插
    		$postsql[] =  "(".$p['pid'].",".$p['fid'].",".$p['tid'].",'".$p['attachment']."','".addslashes($p['author'])."',".$p['authorid'].",".$p['dateline'].",'".$p['subject']."','".$p['useip']."',".$p['usesig'].",'',".$ifconvert.",".($p['invisible'] < 0 ? 0 : 1).",'".$p['message']."',".$p['status'].",".$p['anonymous'].",'".$ifmark."')";
        }

		$s_c++;
	}

    if($speed){//批量插
		if($postsql)
		{
            $postsqlstr = implode(",",$postsql);
			$DDB->update("REPLACE INTO {$pw_prefix}posts (pid,fid,tid,aid,author,authorid,postdate,subject,userip,ifsign,buy,ifconvert,ifcheck,content,ifshield,anonymous,ifmark) VALUES $postsqlstr ");
		}
    }

    $maxid = $SDB->get_value("SELECT max(pid) FROM {$source_prefix}posts");
    echo '最大id',$maxid;
    echo '最后id',$lastid;
    if($maxid > $lastid)
    {
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
		report_log();
		newURL($step);
	}
}
elseif ($step == '13')
{
	//附件
	if(!$start)
	{
		$DDB->update("TRUNCATE TABLE {$pw_prefix}attachs");
	}

	$query = $SDB->query("SELECT a.*,p.fid,p.first FROM {$source_prefix}attachments a LEFT JOIN {$source_prefix}posts p USING(pid) WHERE a.aid >= $start ORDER BY a.aid LIMIT $percount");
	while($a = $SDB->fetch_array($query))
	{
        $lastid = $a['aid'];
		/*附件类型转换*/
		$fileinfo = getfileinfo($a['filename']);
		$a['filetype'] 	= $fileinfo['type'];
		$ifupload 		= $fileinfo['ifupload'];

        if($a['price']){
            $ctype = 'money';
        }else{
            $ctype = '';
        }
		$attachesql = '';
		$attachesql = "(".$a['aid'].",'".$a['fid']."','".$a['uid']."','".$a['tid']."',".($a['first'] ? 0 : $a['pid']).",'".addslashes($a['filename'])."','".$a['filetype']."',".(round($a['filesize']/1024)).",'".addslashes($a['attachment'])."',".$a['downloads'].",'".$a['price']."',".$a['dateline'].",'".addslashes($a['description'])."','$ctype')";

		if('' != $attachesql)
		{
			$DDB->update("REPLACE INTO {$pw_prefix}attachs (aid,fid,uid,tid,pid,name,type,size,attachurl,hits,needrvrc,uploadtime,descrip,ctype) VALUES $attachesql ");
		}

		$s_c++;
	}

    $maxid = $SDB->get_value("SELECT max(aid) FROM {$source_prefix}attachments");
    echo '最大id',$maxid;
    echo '最后id',$lastid;
    if($maxid > $lastid)
    {
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
		report_log();
		newURL($step);
	}
}
elseif ($step == '14')
{
	//公告
	$DDB->update("TRUNCATE TABLE {$pw_prefix}announce");

	$query = $SDB->query("SELECT * FROM {$source_prefix}announcements");
	while($a = $SDB->fetch_array($query))
	{
		$DDB->update("REPLACE INTO {$pw_prefix}announce (aid,fid,ifopen,vieworder,author,startdate,url,enddate,subject,content,ifconvert) VALUES (".$a['id'].",-1,1,".$a['displayorder'].",'".addslashes($a['author'])."',".$a['starttime'].",'".addslashes((($a['type'] & 1) ? $a['message'] : ''))."',".$a['endtime'].",'".addslashes($a['subject'])."','".addslashes($a['message'])."',".((convert($a['message']) == $a['message'])? 0 : 1).")");
		$s_c++;
	}

    //版块公告
    $rt = $DDB->get_one("SELECT max(aid) as maxaid FROM {$pw_prefix}announce");
    $b_i = $rt['maxaid']+1;
	$query = $SDB->query("SELECT cff.fid,cff.rules,cf.name FROM {$source_prefix}forumfields cff LEFT JOIN {$source_prefix}forums cf USING(fid) WHERE cff.rules!=''");
	while($b = $SDB->fetch_array($query))
	{
		$DDB->update("REPLACE INTO {$pw_prefix}announce (aid,fid,ifopen,vieworder,author,startdate,url,enddate,subject,content,ifconvert) VALUES (".$b_i.",".$b['fid'].",1,1,'admin',".$timestamp.",'','','".addslashes($b['name'])."','".addslashes($b['rules'])."',1)");
        $b_i++;
		$s_c++;
	}
    //版块公告

	report_log();
	newURL($step);
}
elseif ($step == '15')
{
	require_once S_P.'tmp_uc.php';
	$charset_change = 1;
	$UCDB = new mysql($uc_db_host, $uc_db_user, $uc_db_password, $uc_db_name, '');

    $message_sql = $relations_sql = $replies_sql = array();
	//短信
	if(!$start)
	{
        $DDB->update("TRUNCATE TABLE {$pw_prefix}ms_messages");
        $DDB->update("TRUNCATE TABLE {$pw_prefix}ms_relations");
        $DDB->update("TRUNCATE TABLE {$pw_prefix}ms_replies");
	}
	$query = $UCDB->query("SELECT * FROM {$uc_db_prefix}pms WHERE pmid >= $start ORDER BY pmid LIMIT $percount");
    while($m = $UCDB->fetch_array($query))
	{
        $lastid = $m['pmid'];
        ADD_S($m);
        if($m['related']==0){
            continue;
        }
		switch ($m['folder'])
		{
			case 'inbox':
				$type = 'rebox';
				$m_tmp = $DDB->get_one("SELECT username FROM {$pw_prefix}members WHERE uid = ".$m['msgtoid']);
				$m['msgto'] = addslashes($m_tmp['username']);
				break;
			case 'outbox':
				$type = 'sebox';
				$m_tmp = $DDB->get_one("SELECT username FROM {$pw_prefix}members WHERE uid = ".$m['msgtoid']);
				$m['msgfrom'] = addslashes($m_tmp['username']);
				break;
		}
/*
		$msgsql = '';
		$msgcsql = '';
		$msglogsql = array();
*/
		if ($m['delstatus'] != 2)
		{
			//$msgsql = "(".$m['pmid'].",".$m['msgtoid'].",".$m['msgfromid'].",'".($m['msgfrom'])."','".$type."',".$m['new'].",'".$m['dateline']."')";

			//$msgcsql = "(".$m['pmid'].",'".($m['subject'])."','".($m['message'])."')";

/*
			if (($m['msgtoid'] != $m['msgfromid']) && ($type == 'rebox'))
			{
				$msglogsql[]="(".$m['pmid'].",".$m['msgfromid'].",".$m['msgtoid'].",'".$m['dateline']."','send')";
				$msglogsql[]="(".$m['pmid'].",".$m['msgtoid'].",".$m['msgfromid'].",'".$m['dateline']."','receive')";
			}
*/
	        $message_sql[] = "('".$m['pmid']."',".$m['msgfromid'].",'".$m['msgfrom']."','".$m['subject']."','".$m['message']."','".serialize(array('categoryid'=>1,'typeid'=>100))."',".$m['dateline'].",".$m['dateline'].",'".serialize(array($m['msgto']))."')";
	        $replies_sql[] = "('".$m['pmid']."',".$m['pmid'].",'".$m['msgfromid']."','".$m['msgfrom']."','".$m['subject']."','".$m['message']."','1',".$m['dateline'].",".$m['dateline'].")";

            $userIds = "";
	        $userIds = array($m['msgtoid'],$m['msgfromid']);
	        foreach($userIds as $otherId){
	            $relations_sql[] = "(".$otherId.",'".$m['pmid']."','1','100','0',".(($otherId == $m['msgfromid']) ? 1 : 0).",".$m['dateline'].",".$m['dateline'].")";
            }
		    $s_c++;
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
    $maxid = $UCDB->get_value("SELECT max(pmid) FROM {$uc_db_prefix}pms");
    if($maxid > $lastid)
    {
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
		report_log();
		newURL($step);
	}
}
elseif ($step == '16')
{
	//好友
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}friends");
	}
	$goon = 0;
	$query = $SDB->query("SELECT uid,buddyid,dateline,description FROM {$source_prefix}buddys LIMIT $start, $percount");
	while($f = $SDB->fetch_array($query))
	{
		$DDB->update("REPLACE INTO {$pw_prefix}friends (uid,friendid,joindate,descrip,iffeed) VALUES (".$f['uid'].",".$f['buddyid'].",".$f['dateline'].",'".addslashes($f['description'])."',1)");
		$DDB->update("REPLACE INTO {$pw_prefix}friends (friendid,uid,joindate,descrip,iffeed) VALUES (".$f['uid'].",".$f['buddyid'].",".$f['dateline'].",'".addslashes($f['description'])."',1)");

		$goon ++;
		$s_c ++;
	}

	if ($goon == $percount)
	{
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
		report_log();
		newURL($step);
	}
}
elseif ($step == '17')
{
	//收藏
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}favors");
	}

	$goon = 0;
	$query = $SDB->query("SELECT uid,tid FROM {$source_prefix}favorites LIMIT $start, $percount");

	while($f = $SDB->fetch_array($query))
	{
		$DDB->pw_update("SELECT uid FROM {$pw_prefix}favors WHERE uid = ".$f['uid'],
						"UPDATE {$pw_prefix}favors SET tids = CONCAT_WS(',',tids,'".$f['tid']."') WHERE uid = ".$f['uid'],
						"REPLACE INTO {$pw_prefix}favors (uid,tids) VALUES (".$f['uid'].", '".$f['tid']."')");
		$goon ++;
		$s_c ++;
	}
	if ($goon == $percount)
	{
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
		report_log();
		newURL($step);
	}
}

elseif ($step == '18')
{
	//标签
	$goon = 0;
	if(!$start)
	{
		$DDB->update("TRUNCATE TABLE {$pw_prefix}tags");
		$DDB->update("TRUNCATE TABLE {$pw_prefix}tagdata");
		if ($pwsamedb)
		{
			$DDB->update("INSERT INTO {$pw_prefix}tags (tagname,num) SELECT tagname,total FROM {$source_prefix}tags");
		}
		else
		{
			$query = $SDB->query("SELECT tagname,total FROM {$source_prefix}tags");
			$it = '';
			while ($r = $SDB->fetch_array($query))
			{
				$it .= "('".addslashes($r['tagname'])."','".$r['total']."'),";
			}
			$it && $DDB->update("INSERT INTO {$pw_prefix}tags (tagname,num) VALUES ".substr($it, 0, -1));
		}
	}

	$query = $SDB->query("SELECT * FROM {$source_prefix}threadtags LIMIT $start, $percount");

	while ($r = $SDB->fetch_array($query))
	{
		$tagid = $DDB->get_one("SELECT tagid FROM {$pw_prefix}tags WHERE tagname = '".$r['tagname']."'");
		$tagid && $DDB->update("INSERT INTO {$pw_prefix}tagdata (tagid,tid) VALUES (".$tagid['tagid'].", ".$r['tid'].")");

        if($tagarr[$r['tid']]){
            $tagarr[$r['tid']] = $tagarr[$r['tid']].','.$r['tagname'];
        }else{
            $tagarr[$r['tid']] = $r['tagname'];
        }
        /*
		$tg = $SDB->query("SELECT * FROM {$source_prefix}threadtags WHERE tid = ".$t['tid']);
		while($rtg = $SDB->fetch_array($tg))
		{
			$tag .= $rtg['tagname'].' ';
		}
		$tag && $tag = substr($tag, 0, -1);*/

		$goon ++;
		$s_c ++;
	}
    foreach($tagarr as $k => $v){
        $DDB->update("UPDATE {$pw_prefix}tmsgs SET tags = '".addslashes($v)."' WHERE tid=$k");
    }
	if ($goon == $percount)
	{
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
		report_log();
		newURL($step);
	}
}
elseif ($step == '19')
{
	//友情
	require_once S_P.'lang_'.$dest_charset.'.php';
	$DDB->query("TRUNCATE TABLE {$pw_prefix}sharelinks");

	$query = $SDB->query("SELECT * FROM {$source_prefix}forumlinks");

	$insert = '';
	while($link = $SDB->fetch_array($query))
	{
		if (strpos(strtolower($link['name']), 'discuz') === FALSE)
		{
			$insert .= "(".$link['displayorder'].",'".addslashes($link['name'])."', '".addslashes($link['url'])."','".addslashes($link['description'])."','".addslashes($link['logo'])."', 1),";
		}
		$s_c ++;
	}
	$insert .= $lang['link'];
	$DDB->update("INSERT INTO {$pw_prefix}sharelinks (threadorder, name, url, descrip, logo, ifcheck) VALUES ".$insert);

	report_log();
	newURL($step);
}
elseif ($step == '20')
{
	//头像
	$_avatar = array();
	$pw_avatar = R_P.'pwavatar';
	if (!$start)
	{
		$dirname = array();
		$uc_avatar = R_P.'avatar';
		if (!is_dir($pw_avatar) || !is_dir($uc_avatar) || !is_readable($uc_avatar) || !N_writable($pw_avatar))
		{
			ShowMsg('用于转换头像的 avatar 或者 pwavatar 目录不存在或者无法写入。<br /><br />1、请将 UCenter安装目录/data/ 下的 avatar 目录复制到 PWBuilder 根目录。<br /><br />2、在PWBuilder 根目录下建立一个名为：pwavatar 的目录，且设定权限为777。<br /><br />', true);
		}
		PWListDir($uc_avatar, $dirname);
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
		if ($file != '.' && $file != '..' && preg_match('/^[a-z0-9\:\/\._]*?\/avatar\/(\d{3})\/(\d{2})\/(\d{2})\/(\d{2})\_(real_)?avatar_middle\.jpg$/i', $_avatar[$start].'/'.$file, $match))
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
		}
	}
	$end = ++$start;
	refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
}
elseif ($step == '21')
{
    //银行
	$rt_check = $SDB->get_one("SHOW TABLE STATUS LIKE '{$source_prefix}bankoperation'");
	if (!$rt_check) {
        report_log();
		newURL($step);
    }
    $query = $SDB->query("SELECT id,uid,username,optype,opnum,begintime FROM {$source_prefix}bankoperation LIMIT $start, $percount");
    $goon = 0;
    while ($thread = $SDB->fetch_array($query))
    {
        $goon++;
        $query_memberinfo = $DDB->get_one("select uid from {$pw_prefix}memberinfo where uid='{$thread['uid']}'");
        if (1 == $thread['optype']) //活期
        {
            if ($query_memberinfo['uid'])
            {
                $DDB->update("update {$pw_prefix}memberinfo set deposit=deposit+{$thread['opnum']},startdate='{$thread['begintime']}' where uid='{$thread['uid']}'");
            }
            else
            {
                $DDB->update("insert into {$pw_prefix}memberinfo (uid,deposit,startdate) values ('{$thread['uid']}','{$thread['opnum']}','{$thread['begintime']}')");
            }
        }
        elseif (0 == $thread['optype']) //定期
        {
            if ($query_memberinfo['uid'])
            {
                $DDB->update("update {$pw_prefix}memberinfo set ddeposit=ddeposit+{$thread['opnum']},dstartdate='{$thread['begintime']}' where uid='{$thread['uid']}'");
            }
            else
            {
                $DDB->update("insert into {$pw_prefix}memberinfo (uid,ddeposit,dstartdate) values ('{$thread['uid']}','{$thread['opnum']}','{$thread['begintime']}')");
            }
        }
    }
	if ($goon == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else{
		report_log();
		newURL($step);
	}
}
elseif ($step == '22')
{
    //广告
	if(!$start)
	{
	    require_once S_P.'lang_advert.php';
	}

	$query = $SDB->query("SELECT * FROM {$source_prefix}advertisements a WHERE a.advid >= $start AND a.advid < $end");
	$goon = 0;

	$_ckey = array(
		'headerbanner'	=> 'Site.Header',
		'footer'	=> 'Site.Footer',
		'navbanner'	=> 'Site.NavBanner',
		'popup'		=> 'Site.PopupNotice',
		'float'		=> 'Site.FloatRand',
		'leftfloat'	=> 'Site.FloatLeft',
		'rightfloat'=> 'Site.FloatRight',
		'text.1'	=> 'Mode.TextIndex',
		'text.2'	=> 'Mode.Forum.TextRead',
		'text.3'	=> 'Mode.Forum.TextThread',
		'article.1'	=> 'Mode.Forum.Layer.TidRight',
		'article.2'	=> 'Mode.Forum.Layer.TidDown',
		'article.5'	=> 'Mode.Forum.Layer.TidUp',
		'article.3'	=> 'Mode.Forum.Layer.TidAmong',
		'article.4'	=> 'Mode.Forum.Layer.Index',
	);

	while ($adv = $SDB->fetch_array($query))
	{
		$config = array();
        $Sconfig = unserialize($adv['parameters']);
        $advtype = $adv['type'];
        if($adv['type']=='text'){//文字广告
            if($adv['targets']!='forum'){
                $advtype = 'text.3';
            }else{
                $advtype = 'text.1';
            }
        }
        if($adv['type']=='thread'){//帖内广告
            if($Sconfig['position']==1){//帖子下方 帖子上方 帖子右侧
                $advtype = 'article.2';
            }elseif($Sconfig['position']==2){
                $advtype = 'article.5';
            }elseif($Sconfig['position']==3){
                $advtype = 'article.1';
            }else{
                $advtype = 'article.2';
            }
            $louarr = explode("\t",$Sconfig['displayorder']);
		    $config['lou'] = implode(",",$louarr);
        }
        if($adv['type']=='interthread'){//贴间广告
            $advtype = 'article.3';
        }
        $ckey = $_ckey[$advtype];
        if($adv['endtime']==0){
            $adv['endtime'] = $adv['starttime'] + 31536000;
        }

        $fidarr = explode("\t",$adv['targets']);
        $fidarr1 =array();
        foreach($fidarr as $v){
            if(is_numeric($v)){
                $fidarr1[] = $v;
            }
        }
        $config['fid'] = implode(",",$fidarr1);

		if ($Sconfig['style'] == 'text') {
			$config['type'] = 'txt';
			$config['title'] = $Sconfig['title'];
			$config['link'] = $Sconfig['link'];
			$config['color'] = $Sconfig['color'];
			$config['size'] = $Sconfig['size'];
		} elseif($Sconfig['style'] == 'image') {
			$config['type'] = 'img';
			$config['title'] = $Sconfig['title'];
			$config['url'] = $Sconfig['url'];
			$config['link'] = $Sconfig['link'];
			$config['height'] = $Sconfig['height'];
			$config['width'] = $Sconfig['width'];
		} elseif($Sconfig['style'] == 'code') {//
			$config['type'] = 'code';
			$config['htmlcode'] = $Sconfig['html'];
		} elseif($Sconfig['style'] == 'flash') {
			$config['type'] = 'flash';
			$config['link'] = $Sconfig['url'];
			$config['height'] = $Sconfig['height'];
			$config['width'] = $Sconfig['width'];
		}
		$config = addslashes(serialize($config));

		$DDB->update("INSERT INTO {$pw_prefix}advert (type,uid,ckey,stime,etime,ifshow,orderby,descrip,config) values (1,0,'".$ckey."','".$adv['starttime']."','".$adv['endtime']."','".$adv['available']."','".$adv['displayorder']."','".$adv['title']."','".$config."')");
	}
	$advid = $SDB->get_one("SELECT max(advid) as advid FROM {$source_prefix}advertisements");
	if ($start < $advid['advid'])
	{
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
		report_log();
		newURL($step);
		exit();
	}
}
elseif ($step == '23')
{
	//会员
	$insertadmin = '';
	$_specialgroup = array();
	require_once (S_P.'tmp_credit.php');
	require_once (S_P.'tmp_uc.php');    //uc表

    //增加uc
	$charset_change = 1;
	$UCDB = new mysql($uc_db_host, $uc_db_user, $uc_db_password, $uc_db_name, '');

	require_once (S_P.'ubb.php');
	require_once (S_P.'tmp_group.php');

	$query = $UCDB->query("SELECT * FROM {$uc_db_prefix}members m LEFT JOIN {$uc_db_prefix}memberfields mf USING (uid) WHERE m.uid >= $start AND m.uid < $end ORDER BY m.uid");
	while ($m = $SDB->fetch_array($query))
	{
		Add_S($m);

		$rt = $SDB->get_one("SELECT uid FROM {$source_prefix}members WHERE uid=".$m['uid']);
		if($rt){
			continue;
		}
/*
		if (!$m['uid'] || !$m['username'] || CK_U($m['username']))
		{
			$f_c++;
			errors_log($m['uid']."\t".$m['username']);
			continue;
		}
*/

        $groupid = '-1';

		$timedf = ($m['timeoffset'] == '9999') ? '0' : $m['timeoffset'];//时差设定
		list($introduce,) = explode("\t", $m['bio']); //bio 自我介绍
		$editor = ($m['editormode'] == '1') ? '1' : '0';//编辑器模式
		$userface = $banpm = '';
		if ($m['avatar'])//头像
		{
			$avatarpre = substr($m['avatar'], 0, 7);
			switch ($avatarpre)
			{
				case 'http://':
					$userface = $m['avatar'].'|2|'.$m['avatarwidth'].'|'.$m['avatarheight'];
					break;
				case 'images/':
					$userface = substr($m['avatar'],strrpos($m['avatar'],'/')+1).'|1';
					break;
				case 'customa':
					$userface = substr($m['avatar'],strrpos($m['avatar'],'/')+1).'|3|'.$m['avatarwidth'].'|'.$m['avatarheight'];
					break;
			}
		}
		$m['sightml'] = addslashes(html2bbcode(stripslashes($m['sightml'])));//个性签名
		$signchange = ($m['sightml'] == convert($m['sightml'])) ? 1 : 2;
		$userstatus = ($signchange-1)*256 + 128 + $m['showemail']*64 + 4;//用户位状态设置
		$medals = $medal ? str_replace("\t", ',', $m['medals']) : '';

        //密码
		$m['ignorepm'] && $m['ignorepm'] != '{ALL}' && $banpm = $m['ignorepm'];

		$membersql[] = "(".$m['uid'].",'".$m['username']."','".$m['password']."','".$m['email']."','".$groupid."','".$userface."','".$m['gender']."','".$m['regdate']."','".$m['sightml']."','".$introduce."','".$m['qq']."','".$m['icq']."','".$m['msn']."','".$m['yahoo']."','".$m['site']."','".$m['location']."','".$m['customstatus']."','".$m['bday']."','".$timedf."','".$m['tpp']."','".$m['ppp']."','".$newpm."','$banpm','$medals','".$userstatus."','".$m['salt']."')";
		$memdatasql[] = "(".$m['uid'].",'".$m['posts']."','".$m['digestposts']."','".$rvrc."','".$money."','".$credit."','".$currency."','".$m['lastvisit']."','".$m['lastactivity']."','".$m['lastpost']."','".intval($m['total']*60)."','".intval($m['thismonth']*60)."')";
		$s_c++;
	}

	//会员处理
	if($membersql)
	{
		$membersqlstr = implode(",",$membersql);
		$DDB->update("REPLACE INTO {$pw_prefix}members (uid,username,password,email,groupid,icon,gender,regdate,signature,introduce,oicq,icq,msn,yahoo,site,location,honor,bday,timedf,t_num,p_num,newpm,banpm,medals,userstatus,salt) VALUES $membersqlstr ");
	}

	if($memdatasql)
	{
		$memdatastr = implode(",",$memdatasql);
		$DDB->update("REPLACE INTO {$pw_prefix}memberdata (uid,postnum,digests,rvrc,money,credit,currency,lastvisit,thisvisit,lastpost,onlinetime,monoltime) VALUES $memdatastr ");
	}

	$members_maxid = $SDB->get_value("SELECT max(uid) FROM {$source_prefix}members");

	if ($members_maxid > $start)
	{
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c.'&medal='.$medal);
	}
	else
	{
		report_log('');
		newURL($step);
		exit();
	}
}
elseif ($step == '24')
{
    //评分
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}pinglog");
	}
	require_once S_P.'tmp_credit.php';//多个
    $pinglogarr=array();//评分日志，7.3新增
    $ifmarkcount=0;
    $ifmark='';
    $query = $SDB->query("SELECT r.*,p.first,p.tid,p.fid FROM {$source_prefix}ratelog r LEFT JOIN {$source_prefix}posts p USING(pid) LIMIT $start, $percount");
    $goon = 0;
    while ($rate = $SDB->fetch_array($query))
    {
        $goon++;
		if(!$rate['tid'] || !$rate['fid']){
			$rate['tid']=0;
			$rate['fid']=0;
		}
		if($rate['first']==1){
			$rate['pid'] = 0;
		}
        $ifmarkcount=0;
        $nameid = $rate['extcredits'];
        $scoret = $rate['score'];
        if($rate['score']>0)
        {
            $scoret = "+" . $rate['score'];
        }
        $ifmark .= $pingcredit[$nameid] . ":" . $scoret . "(" . addslashes($rate['username']) . ")" . addslashes($rate['reason']) . "\t";
        $ifmarkcount += $rate['score'];
        $pinglogarr[] = "(" . $rate['fid'] . "," . $rate['tid'] . "," . $rate['pid'] . ",'" . $pingcredit[$nameid] . "','".$rate['score'] . "','" . addslashes($rate['username'])."','".addslashes($rate['reason'])."',".$rate['dateline'].")";
    }
    if($pinglogarr)
    {
        $pinglogstr = implode(",",$pinglogarr);
        $DDB->update("INSERT INTO {$pw_prefix}pinglog (fid,tid,pid,name,point,pinger,record,pingdate) VALUES $pinglogstr");
    }
	if ($goon == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else{
		report_log();
		newURL($step);
	}
}
elseif ($step == '25')
{
	if(!$start)
	{
		$DDB->query("UPDATE {$pw_prefix}threads SET ifmark=''");
		$DDB->query("UPDATE {$pw_prefix}tmsgs SET ifmark=''");
		$DDB->query("UPDATE {$pw_prefix}posts SET ifmark=''");
	}
    $query = $DDB->query("SELECT * FROM {$pw_prefix}pinglog WHERE id>$start LIMIT $percount");
    while ($rt = $DDB->fetch_array($query))
    {
        $lastid = $rt['id'];
        $ids = '';
        $ifmark_threads = $DDB->get_value("SELECT ifmark FROM {$pw_prefix}threads WHERE tid=".$rt['tid']);
        $ifmark_threads = $ifmark_threads+$rt['point'];
        $DDB->update("UPDATE {$pw_prefix}threads SET ifmark = '$ifmark_threads' WHERE tid=".$rt['tid']);
        if($rt['pid']==0){
            $ifmark_tmsgs = $DDB->get_value("SELECT ifmark FROM {$pw_prefix}tmsgs WHERE tid=".$rt['tid']);
            if(strpos($ifmark_tmsgs,":")){
                list($num,$ids) = explode(":",$ifmark_tmsgs);
            }
            if(!$ids){
                $num = 1;
                $ids = $rt['id'];
                $ifmark_tmsgs = $num.":".$ids;
            }else{
                $idsarr = explode(",",$ids);
                if(!in_array($rt['id'],$idsarr)){
                    $num++;
                    $ids .= ','.$rt['id'];
                    $ifmark_tmsgs = $num.":".$ids;
                }
            }
            //echo "UPDATE pw_tmsgs SET ifmark = '$ifmark_tmsgs' WHERE tid=".$rt['tid'].'<br>';
            $DDB->update("UPDATE {$pw_prefix}tmsgs SET ifmark = '$ifmark_tmsgs' WHERE tid=".$rt['tid']);
        }else{
            $ifmark_posts = $DDB->get_value("SELECT ifmark FROM {$pw_prefix}posts WHERE pid=".$rt['pid']);
            if(strpos($ifmark_posts,":")){
                list($num,$ids) = explode(":",$ifmark_posts);
            }
            if(!$ids){
                $num = 1;
                $ids = $rt['id'];
                $ifmark_posts = $num.":".$ids;
            }else{
                $idsarr = explode(",",$ids);
                if(!in_array($rt['id'],$idsarr)){
                    $num++;
                    $ids .= ','.$rt['id'];
                    $ifmark_posts = $num.":".$ids;
                }
            }
            $DDB->update("UPDATE {$pw_prefix}posts SET ifmark = '$ifmark_posts' WHERE pid=".$rt['pid']);
        }
    }
    $maxid = $DDB->get_value("SELECT max(id) FROM {$pw_prefix}pinglog");
    echo '最大id',$maxid,'<br>','最后id',$lastid;
    if($lastid < $maxid)
    {
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else{
		report_log();
		newURL($step);
	}
}
elseif ($step == '26')
{
	newURL($step);
}
elseif ($step == '27')
{
    //分类信息框架
    @include_once(S_P."lang_topicmodel.php");
    $threadtypes = $modelarray1 = $modelarray2 = $modelarray3 = array();

    $sort_type_field = 'typeid';
    $query = $SDB->query("SHOW COLUMNS FROM {$source_prefix}typevars");
    while ($mc = $SDB->fetch_array($query))
    {
        if (strpos(strtolower($mc['Field']), 'sortid') !== FALSE)
        {
            $sort_type_field = 'sortid';
        }
    }

    $query = $SDB->query("SELECT * FROM {$source_prefix}threadtypes WHERE special=1");
    while ($type = $SDB->fetch_array($query))
    {
        $flag++;
        $type['name'] = strip_tags($type['name']);
        $DDB->update("INSERT INTO {$pw_prefix}topiccate (name,ifable,vieworder,ifdel) VALUES('".$type['name']."',1,".$type['displayorder'].",1)");
        $cateid = $DDB->insert_id();
        $DDB->update("INSERT INTO {$pw_prefix}topicmodel  (name,cateid,ifable,vieworder) VALUES('".$type['name']."',$cateid,1,".$type['displayorder'].")");
        $modelid = $DDB->insert_id();

        $modelarray1[$type['typeid']] = $modelid;
        $modelarray2[] = $type['typeid'];

        $charset = $dest_charset;//编码
        $createsql = "CREATE TABLE ".$pw_prefix."topicvalue".intval($modelid)." (`tid` mediumint(8) unsigned NOT NULL default '0',`fid` SMALLINT( 6 ) UNSIGNED NOT NULL DEFAULT  '0',`ifrecycle` tinyint(1) unsigned NOT NULL default '0',PRIMARY KEY  (`tid`))";
        if ($DDB->server_info() >= '4.1') {
            $extra = " ENGINE=MyISAM".($charset ? " DEFAULT CHARSET=$charset" : '');
        } else {
            $extra = " TYPE=MyISAM";
        }
        $createsql = $createsql.$extra;
        $DDB->query($createsql);

        //选项cdb_typevars
        $query2 = $SDB->query("SELECT o.* FROM {$source_prefix}typevars v LEFT JOIN {$source_prefix}typeoptions o USING(optionid) WHERE v.{$sort_type_field} = ".$type['typeid']);
        while ($o = $SDB->fetch_array($query2)){
            if($o['type']=='calendar'){
                $o['type'] = 'text';
            }
            $ruletmp = unserialize($o['rules']);

            if($ruletmp['choices']){
                $ruletmp['choices'] = str_replace('\"\"',"0",$ruletmp['choices']);
                $ruletmp = explode("\r\n",$ruletmp['choices']);
                $o['rules'] = serialize($ruletmp);
            }
            $DDB->update("INSERT INTO {$pw_prefix}topicfield SET ".pwSqlSingle(array('name'=>$o['title'],'modelid' => $modelid,'type'=>$o['type'],'rules'=>$o['rules'],'descrip'=>$o['description'],'ifmust'=>$o['required'],'vieworder'=>$o['displayorder'])));
            $fieldid = $DDB->insert_id();
            $fieldname = 'field'.$fieldid;
            $tablename = $pw_prefix.'topicvalue'.intval($modelid);
            $DDB->update("UPDATE ".$pw_prefix."topicfield SET fieldname=".pwEscape($fieldname)." WHERE fieldid=".pwEscape($fieldid));
            $sql = getFieldSqlByType($fieldtype);
            $DDB->query("ALTER TABLE $tablename ADD $fieldname $sql");
            $modelarray3[$type['typeid']][$o['optionid']] = $fieldname;
        }
    }
	writeover(S_P.'tmp_model.php', "\$_model = ".pw_var_export($modelarray1).";\$_model2 = ".pw_var_export($modelarray2).";\$_model3 = ".pw_var_export($modelarray3).";", true);
	newURL($step);
}
elseif ($step == '28')
{
	require_once S_P.'tmp_model.php';

    $lastid = $start;
	$query = $SDB->query("SELECT * FROM {$source_prefix}threads WHERE tid > $start ORDER BY tid LIMIT $percount");
	while ($v = $SDB->fetch_array($query))
	{
        $lastid = $v['tid'];
        if(!$v['sortid']) $v['sortid'] = $v['typeid'];
        if(!in_array($v['sortid'],$_model2)){//sortid
            continue;
        }
        $optionlist = array();
        $query2 = $SDB->query("SELECT * FROM {$source_prefix}typeoptionvars WHERE tid =".$v['tid']."");
        while($info = $SDB->fetch_array($query2)) {
            if($info['value']){
                $info['value'] = addslashes($info['value']);
                if(!$info['optionid'])continue;
                preg_match("/^[0-9]{4}-(\d+)-(\d+)$/i", $info['value'], $arr);
                if($arr[0]){
                    $info['value'] = strtotime($arr[0]);
                }
                if(!$info['sortid']) $info['sortid'] = $info['typeid'];
                $optionlist[$info['sortid']][$info['tid']][$info['optionid']] = $info['value'];
            }
        }
        $sql1 = $sql2 = array();
        if(is_array($optionlist[$v['sortid']][$v['tid']])){
            foreach($optionlist[$v['sortid']][$v['tid']] as $key => $val){
                if($_model3[$v['sortid']][$key]){
                    $sql1[] = $_model3[$v['sortid']][$key];
                    $sql2[] = $val;
                }
            }
        }
        if(($sql1)){
            $DDB->update("REPLACE INTO {$pw_prefix}topicvalue".$_model[$v['sortid']]."(tid,fid,".implode(",",$sql1).") VALUES(".$v['tid'].",".$v['fid'].",".pwImplode($sql2).")");
            $DDB->update("UPDATE {$pw_prefix}threads SET modelid=".$_model[$v['sortid']]." WHERE tid=".$v['tid']);
            $s_c++;
        }
    }
	$maxid = $SDB->get_value("SELECT max(tid) FROM {$source_prefix}threads");
	if ($lastid < $maxid)
	{
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
		report_log();
		newURL($step);
		exit();
	}
}
elseif ($step == '29')
{
	//圈子群组分类
	if(!file_exists(S_P."tmp_uch.php"))
	{
		newURL($step);
	}
	else
	{
		require(S_P."tmp_uch.php");
	    $charset_change = 1;
	    $UCHDB = new mysql($uch_db_host, $uch_db_user, $uch_db_password, $uch_db_name, '');
	}

	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}cnstyles");
        //$DDB->update("INSERT INTO {$pw_prefix}forums (name,fup) VALUE ('群组版块',1)");
        //$lastfid = $DDB->insert_id();
        //$DDB->update("INSERT INTO {$pw_prefix}forumdata (fid) VALUE ($lastfid)");
	}

	$query = $UCHDB->query("SELECT fieldid,title FROM {$uch_db_prefix}profield");
	while ($rt = $UCHDB->fetch_array($query))
	{
		$cid	=	$rt['fieldid'];
		$cname	=	$rt['title'];
		$cnstyledb[] = array($cid,$cname,1);//待更新群组的时候统计更新？？？？？？？？？？？
		$s_c ++;
	}
	$DDB->update("REPLACE INTO {$pw_prefix}cnstyles (id,cname,ifopen) VALUES ".pwSqlMulti($cnstyledb));

	report_log();
	newURL($step);
}
elseif ($step == '30')
{
	//圈子群组
	if(!file_exists(S_P."tmp_uch.php")){
		newURL($step);
	}
	else{
		require(S_P."tmp_uch.php");
	    $charset_change = 1;
	    $UCHDB = new mysql($uch_db_host, $uch_db_user, $uch_db_password, $uch_db_name, '');
	}
	if(!$start){
		$DDB->query("TRUNCATE TABLE {$pw_prefix}colonys");
	}

    $lastfid = $DDB->get_value("SELECT max(fid) FROM {$pw_prefix}forums");
	$query = $UCHDB->query("SELECT t.username,m.tagid,m.tagname,m.fieldid,m.membernum,m.joinperm,m.viewperm,m.pic,m.announcement,m.threadnum,m.postnum FROM {$uch_db_prefix}tagspace t LEFT JOIN {$uch_db_prefix}mtag m ON t.tagid=m.tagid WHERE t.grade='9' GROUP BY tagid");
	while ($rt = $UCHDB->fetch_array($query)) {
		$id			=	$rt['tagid'];
		$styleid	=	$rt['fieldid'];
		$cname		=	$rt['tagname'];
		$admin		=	$rt['username'];
		$members	=	$rt['membernum'];
		$ifcheck	=	$rt['joinperm'] == 2 ? 0 : ($rt['joinperm'] == 0 ? 2 : 1); //加入权限
		$ifopen		=	$rt['viewperm'] == 1 ? 0 : 1; //群组公开权限
		$albumopen	=	'1';
		$cnimg		=	$rt['pic'];
		$createtime =	$timestamp;
		$annouce	=	$rt['announcement'];
		$albumnum	=	0;			//uchome群组无相册功能
		$annoucesee =	0;
		$descrip	=	'';			//uchome群组无描述
		$colonysdb[] = array($id,$lastfid,$cname,$admin,$members,$ifcheck,$ifopen,$cnimg,$createtime,$annouce,$albumnum,$annoucesee,$descrip,$rt['threadnum'],$rt['postnum'],$styleid);
		$s_c ++;
	}
	$colonysdb && $DDB->update("REPLACE INTO {$pw_prefix}colonys (id,classid,cname,admin,members,ifcheck,ifopen,cnimg,createtime,annouce,albumnum,annoucesee,descrip,tnum,pnum,styleid) VALUES ".pwSqlMulti($colonysdb));

	report_log();
	newURL($step);
}
elseif ($step == '31')
{
	//圈子群组成员
	if(!file_exists(S_P."tmp_uch.php")){
		newURL($step);
	}
	else{
		require(S_P."tmp_uch.php");
	    $charset_change = 1;
	    $UCHDB = new mysql($uch_db_host, $uch_db_user, $uch_db_password, $uch_db_name, '');
	}
	if(!$start){
		$DDB->query("TRUNCATE TABLE {$pw_prefix}cmembers");
	}

	$query = $UCHDB->query("SELECT * FROM {$uch_db_prefix}tagspace LIMIT $start, $percount");

	$goon = 0;
	while ($rt = $UCHDB->fetch_array($query)) {
		$goon++;
		$uid	  = $rt['uid'];
		$username =	$rt['username'];
		$ifadmin  = ($rt['grade'] == 9 || $rt['grade'] == 8) ? '1' : '0';
		$colonyid = $rt['tagid'];
		$cmembersdb[] = array($uid,$username,$ifadmin,$colonyid);
		$s_c ++;
	}
	$cmembersdb && $DDB->update("REPLACE INTO {$pw_prefix}cmembers (uid,username,ifadmin,colonyid) VALUES ".pwSqlMulti($cmembersdb));


	if ($goon == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else{
		report_log();
		newURL($step);
	}
}
elseif ($step == '32')
{
	//圈子群组讨论区
	if(!file_exists(S_P."tmp_uch.php")){
		newURL($step);
	}
	else{
		require(S_P."tmp_uch.php");
	    $charset_change = 1;
	    $UCHDB = new mysql($uch_db_host, $uch_db_user, $uch_db_password, $uch_db_name, '');
	}
	if(!$start){
		$DDB->query("TRUNCATE TABLE {$pw_prefix}argument");
	}
    $lastfid = $DDB->get_value("SELECT max(fid) FROM {$pw_prefix}forums");
	$query = $UCHDB->query("SELECT * FROM {$uch_db_prefix}thread LEFT JOIN {$uch_db_prefix}post USING(tid) WHERE isthread=1 LIMIT $start, $percount");
	$goon = 0;
	while ($rt = $UCHDB->fetch_array($query))
	{
        ADD_S($rt);
		$goon++;
		$s_c ++;
		$tid	= $rt['pid'];
		//$tpcid	= $rt['isthread'] == 1 ? 0 : $rt['tid'];
		$gid	= $rt['tagid'];
		$author = $rt['username'];
		$authorid = $rt['uid'];
		$postdate = $rt['dateline'];

		//if (1 == $rt['isthread'])
		//{
			//$thread_info = $UCHDB->get_one("SELECT lastpost,subject FROM {$uch_db_prefix}thread WHERE tid=".pwEscape($rt['tid']));
			$lastpost = $rt['lastpost']; //最后发表
			$subject  = addslashes($rt['subject']); //标题
            $DDB->update("INSERT INTO {$pw_prefix}threads (fid,author,authorid,subject,postdate,lastpost,ifcheck) VALUES ($lastfid,'$rt[username]',$rt[uid],'$subject','$rt[dateline]','$lastpost',1)");
            $lasttid = $DDB->insert_id();
            $DDB->update("INSERT INTO {$pw_prefix}tmsgs (tid,content) VALUES ($lasttid,'$rt[message]')");

		    $topped  = 0;
		    $toppedtime = 0;

            $DDB->update("INSERT INTO {$pw_prefix}argument (tid,cyid,topped,postdate,lastpost) VALUES ($lasttid,$gid,$topped,$postdate,$lastpost)");
		//}
        /*
		else
		{
			$lastpost = '';
			$subject  = '';
            $DDB->update("INSERT INTO {$pw_prefix}posts (fid,tid,author,authorid,subject,content,postdate,lastpost) VALUES (10000,$rt[username],$rt[uid],$subject,$rt[dateline],$lastpost)");
		}*/

        $query2 = $UCHDB->query("SELECT * FROM {$uch_db_prefix}post WHERE tid=$rt[tid] and isthread=0");
        $goon = 0;
        while ($rt2 = $UCHDB->fetch_array($query2))
        {
            ADD_S($rt2);
            $DDB->update("INSERT INTO {$pw_prefix}posts (fid,tid,author,authorid,content,postdate) VALUES ($lastfid,$lasttid,'$rt2[username]',$rt2[uid],'$rt2[message]',$rt[dateline])");
        }

		//$argumentdb[] = array($tid,$gid,$author,$authorid,$postdate,$lastpost,$topped,$toppedtime,$subject,$content);
	}
	//$argumentdb && $DDB->update("REPLACE INTO {$pw_prefix}argument (tid,cyid,gid,author,authorid,postdate,lastpost,topped,toppedtime,subject,content) VALUES ".pwSqlMulti($argumentdb));
    //$argumentdb && $DDB->update("REPLACE INTO {$pw_prefix}argument (tid,cyid,topped,postdate,lastpost) VALUES ".pwSqlMulti($argumentdb));

	if ($goon == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else{
		report_log();
		newURL($step);
	}
}
elseif ($step == '33')
{
	//圈子记录
	if(!file_exists(S_P."tmp_uch.php")){
		newURL($step);
	}
	else{
		require(S_P."tmp_uch.php");
	    $charset_change = 1;
	    $UCHDB = new mysql($uch_db_host, $uch_db_user, $uch_db_password, $uch_db_name, '');
	}
	if(!$start){
		$DDB->query("TRUNCATE TABLE {$pw_prefix}weibo_content");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}weibo_relations");
	}
	$query = $UCHDB->query("SELECT * FROM {$uch_db_prefix}doing LIMIT $start, $percount");
	$goon = 0;
	while ($rt = $UCHDB->fetch_array($query)) {
		Add_S($rt);
		$goon++;
		$s_c ++;
		$id		= $rt['doid'];
		$uid	= $rt['uid'];
		$touid	= 0;
		$postdate = $rt['dateline'];
		$isshare = 0;
		$source	= 'web';
		$content = $rt['message'];
		$c_num = $rt['replynum'];
		$DDB->update("INSERT INTO {$pw_prefix}weibo_content(uid,content,postdate) values('".$uid."','".$content."','".$postdate."');");
		$DDB->update("INSERT INTO {$pw_prefix}weibo_relations (uid,authorid,postdate) VALUES ('".$uid."','".$uid."','".$postdate."')");
	}

	if ($goon == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else{
		report_log();
		newURL($step);
	}
}
elseif ($step == '34')
{
	//圈子记录回复
	if(!file_exists(S_P."tmp_uch.php")){
		newURL($step);
	}
	else{
		require(S_P."tmp_uch.php");
	    $charset_change = 1;
	    $UCHDB = new mysql($uch_db_host, $uch_db_user, $uch_db_password, $uch_db_name, '');
	}
	if(!$start){
		$DDB->query("TRUNCATE TABLE {$pw_prefix}weibo_comment");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}weibo_cmrelations");
	}
	$query = $UCHDB->query("SELECT * FROM {$uch_db_prefix}docomment LIMIT $start, $percount");
	$goon = 0;
	while ($rt = $UCHDB->fetch_array($query)) {
		$goon++;
		$s_c ++;
		$id			=	$rt['id'];
		$uid		=	$rt['uid'];
		$username	=	$rt['username'];
		$title		=	$rt['message'];
		$type		=	'write';
		$typeid		=	$rt['doid'];
		$upid		=	$rt['upid'];
		$postdate	=	$rt['dateline'];
		$DDB->update("REPLACE INTO {$pw_prefix}weibo_comment (uid,mid,content,postdate) VALUES ('".$uid."','".$rt['doid']."','".addslashes($rt['message'])."','".$postdate."')");
		$cid=$DDB->insert_id();
		$DDB->update("REPLACE INTO {$pw_prefix}weibo_cmrelations (uid,cid) VALUES ('".$uid."','".$cid."')");
	}

	if ($goon == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else{
		report_log();
		newURL($step);
	}
}
elseif ($step == '35')
{
	//圈子相册(将home/attachment目录下的图片移至到phpwind论坛的attachment/photo下)
	if(!file_exists(S_P."tmp_uch.php")){
		newURL($step);
	}
	else{
		require(S_P."tmp_uch.php");
	    $charset_change = 1;
	    $UCHDB = new mysql($uch_db_host, $uch_db_user, $uch_db_password, $uch_db_name, '');
	}
	if(!$start){
		$DDB->query("TRUNCATE TABLE {$pw_prefix}cnalbum");
	}
	$query = $UCHDB->query("SELECT * FROM {$uch_db_prefix}album LIMIT $start, $percount");
	$goon = 0;
	while ($rt = $UCHDB->fetch_array($query)) {
		$goon++;
		$s_c ++;
		$aid	=	$rt['albumid'];
		$aname	=	$rt['albumname'];
		$aintro	=	'';
		$atype	=	0;
		$private=	$rt['friend'] == 0 ? 0 : 1;
		$ownerid=	$rt['uid'];
		$owner	=	$rt['username'];
		$photonum=	$rt['picnum'];
		$lastphoto=	"photo/".$rt['pic'];
		$lasttime=	$rt['updatetime'];
		$lastpid =	'';
		$crtime	 =  $rt['dateline'];
		$cnalbumdb[] = array($aid,$aname,$aintro,$atype,$private,$ownerid,$owner,$photonum,$lastphoto,$lasttime,$lastpid,$crtime);
	}
	$cnalbumdb && $DDB->update("REPLACE INTO {$pw_prefix}cnalbum (aid,aname,aintro,atype,private,ownerid,owner,photonum,lastphoto,lasttime,lastpid,crtime) VALUES ".pwSqlMulti($cnalbumdb));
	if ($goon == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else{
		report_log();
		newURL($step);
	}
}
elseif ($step == '36')
{
	//圈子相册照片
	if(!file_exists(S_P."tmp_uch.php")){
		newURL($step);
	}
	else{
		require(S_P."tmp_uch.php");
	    $charset_change = 1;
	    $UCHDB = new mysql($uch_db_host, $uch_db_user, $uch_db_password, $uch_db_name, '');
	}
	if(!$start){
		$DDB->query("TRUNCATE TABLE {$pw_prefix}cnphoto");
	}
	$query = $UCHDB->query("SELECT * FROM {$uch_db_prefix}pic LIMIT $start, $percount");
	$goon = 0;
	while ($rt = $UCHDB->fetch_array($query)) {
		$goon++;
		$s_c ++;
		$pid	=	$rt['picid'];
		$aid	=	$rt['albumid'];
		$pintro	=	$rt['title'];
		$path	=	"photo/".$rt['filepath'];
		$uploader=	getUsernameByUid($rt['uid']);
		$uptime	=	$rt['dateline'];
		$hits	=	0;
		$ifthumb=	0;
		$c_num	=	getPicCommentNum($pid);
		$cnphoto[] = array($pid,$aid,$pintro,$path,$uploader,$uptime,$hits,$ifthumb,$c_num);
	}
	$cnphoto && $DDB->update("REPLACE INTO {$pw_prefix}cnphoto (pid,aid,pintro,path,uploader,uptime,hits,ifthumb,c_num) VALUES ".pwSqlMulti($cnphoto));

	if ($goon == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else{
		report_log();
		newURL($step);
	}
}
elseif ($step == '37')
{
	//圈子分享
	if(!file_exists(S_P."tmp_uch.php")){
		newURL($step);
	}
	else{
		require(S_P."tmp_uch.php");
	    $charset_change = 1;
	    $UCHDB = new mysql($uch_db_host, $uch_db_user, $uch_db_password, $uch_db_name, '');
	}
	if(!$start){
		$DDB->query("TRUNCATE TABLE {$pw_prefix}collection");
	}
	$query = $UCHDB->query("SELECT * FROM {$uch_db_prefix}share LIMIT $start, $percount");
	$goon = 0;
	while ($rt = $UCHDB->fetch_array($query)) {
		$goon++;
		$s_c ++;
		$id		=	$rt['sid'];
		$type	=	getShareType($rt['type']);
		$uid	=	$rt['uid'];
		$username=	$rt['username'];
		$postdate=	$rt['dateline'];
		$content =	getShareContent($rt['body_data'],$rt['body_general'],$rt['type'],$uid,$username,$rt['image'],$rt['image_link']);
		$ifhidden= 0;
		$sharedb[] = array($id,$type,$uid,$username,$postdate,$content,$ifhidden);

	}
	$sharedb && $DDB->update("REPLACE INTO {$pw_prefix}collection (id,type,uid,username,postdate,content,ifhidden) VALUES ".pwSqlMulti($sharedb));
	if ($goon == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else{
		report_log();
		newURL($step);
	}
}
elseif ($step == '38')
{
	//圈子日志分类
	if(!file_exists(S_P."tmp_uch.php")){
		newURL($step);
	}
	else{
		require(S_P."tmp_uch.php");
	    $charset_change = 1;
	    $UCHDB = new mysql($uch_db_host, $uch_db_user, $uch_db_password, $uch_db_name, '');
	}
	if(!$start){
		$DDB->query("TRUNCATE TABLE {$pw_prefix}diarytype");
	}
	$query = $UCHDB->query("SELECT * FROM {$uch_db_prefix}class LIMIT $start, $percount");
	$goon = 0;
	while ($rt = $UCHDB->fetch_array($query)) {
		$goon++;
		$s_c ++;
		$dtid	=	$rt['classid'];
		$uid	=	$rt['uid'];
		$name	=	$rt['classname'];
		$num	=	getDiaryNum($dtid);
		$diarytypedb[] = array($dtid,$uid,$name,$num);
	}
	$diarytypedb && $DDB->update("REPLACE INTO {$pw_prefix}diarytype (dtid,uid,name,num) VALUES ".pwSqlMulti($diarytypedb));
	if ($goon == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else{
		report_log();
		newURL($step);
	}
}
elseif ($step == '39')
{
	//圈子日志
	if(!file_exists(S_P."tmp_uch.php")){
		newURL($step);
	}
	else{
		require(S_P."tmp_uch.php");
	    $charset_change = 1;
	    $UCHDB = new mysql($uch_db_host, $uch_db_user, $uch_db_password, $uch_db_name, '');
	}
	if(!$start){
		$DDB->query("TRUNCATE TABLE {$pw_prefix}diary");
	}
	$query = $UCHDB->query("SELECT b.blogid,b.uid,b.classid,b.username,b.friend,b.subject,bf.message,b.viewnum,b.replynum,b.dateline FROM {$uch_db_prefix}blog b LEFT JOIN {$uch_db_prefix}blogfield bf ON b.blogid=bf.blogid  LIMIT $start, $percount");
	$goon = 0;
	while ($rt = $UCHDB->fetch_array($query)) {
		$goon++;
		$s_c ++;
		$did = $rt['blogid'];
		$uid = $rt['uid'];
		$dtid = $rt['classid'];
		$username = $rt['username'];
		$privacy  =	($rt['friend'] == 3 || $rt['friend'] == 4) ? 2 : (($rt['friend'] == 1 || $rt['friend'] == 2) ? 1 :0);
		$subject  = $rt['subject'];
		$content  =	$rt['message'];
		$ifcopy	  = 1;
		$copyurl  = '';
		$ifconvert = convert($rt['message']) == $rt['message'] ? 1 : 2;
		$ifwordsfb = 1;
		$r_num	  = $rt['viewnum'];
		$c_num	  =	$rt['replynum'];
		$postdate = $rt['dateline'];
		unset($diarydb);
		$diarydb = array(
			'did' => $did,
			'uid' => $uid,
			'dtid' => $dtid,
			'username' => $username,
			'privacy' => $privacy,
			'subject' => $subject,
			'content' => $content,
			'ifcopy' => $ifcopy,
			'copyurl' => $copyurl,
			'ifconvert' => $ifconvert,
			'ifwordsfb' => $ifwordsfb,
			'r_num' => $r_num ,
			'c_num' => $c_num,
			'postdate' => $postdate
		);

		$DDB->update("REPLACE INTO {$pw_prefix}diary SET".pwSqlSingle($diarydb));

		//$diarydb && $DDB->update("REPLACE INTO {$pw_prefix}diary (did,uid,dtid,username,privacy,subject,content,ifcopy,copyurl,ifconvert,ifwordsfb,r_num,c_num,postdate) VALUES ".pwSqlMulti($diarydb));
	}

	if ($goon == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else{
		report_log();
		newURL($step);
	}
}
elseif ($step == '40')
{
	//圈子日志
	if(!file_exists(S_P."tmp_uch.php")){
		newURL($step);
	}
	else{
		require(S_P."tmp_uch.php");
	    $charset_change = 1;
	    $UCHDB = new mysql($uch_db_host, $uch_db_user, $uch_db_password, $uch_db_name, '');
	}
	$query = $UCHDB->query("SELECT * FROM {$uch_db_prefix}comment LIMIT $start, $percount");
	$goon = 0;
	while ($rt = $UCHDB->fetch_array($query)) {
		$goon++;
		$s_c ++;
		$uid		=	$rt['authorid'];
		$username	=	$rt['author'];
		$title		=	$rt['message'];
		$type		=	getCommentType($rt['idtype']);
		$typeid		=	$rt['id'];
		$upid		=	0;
		$postdate	=	$rt['dateline'];
		unset($commentdb);

		$commentdb = array(
			'uid'=> $uid,
			'username'=> $username,
			'title'=> $title,
			'type'=> $type,
			'typeid'=> $typeid,
			'upid'=> $upid,
			'postdate'=> $postdate
		);

		$DDB->update("INSERT INTO {$pw_prefix}comment SET".pwSqlSingle($commentdb));
		//$commentdb && $DDB->update("INSERT INTO {$pw_prefix}comment (uid,username,title,type,typeid,upid,postdate) VALUES ".pwSqlMulti($commentdb));
	}

	if ($goon == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else{
		report_log();
		newURL($step);
	}
}
elseif ($step == '41')
{
	//好友
	if(!file_exists(S_P."tmp_uch.php")){
		newURL($step);
	}
	else{
		require(S_P."tmp_uch.php");
	    $charset_change = 1;
	    $UCHDB = new mysql($uch_db_host, $uch_db_user, $uch_db_password, $uch_db_name, '');
	}
	if(!$start)
	{
		//$DDB->query("TRUNCATE TABLE {$pw_prefix}friends");
	}

	$goon = 0;
	$query = $UCHDB->query("SELECT uid,fuid FROM {$uch_db_prefix}friend LIMIT $start, $percount");

	//好友好像没有添加时间,也没有验证状态
	while($f = $UCHDB->fetch_array($query))
	{
		$DDB->update("REPLACE INTO {$pw_prefix}friends (uid,friendid,descrip,iffeed) VALUES (".$f['uid'].",".$f['fuid'].",'',1)");
		$DDB->update("REPLACE INTO {$pw_prefix}friends (friendid,uid,descrip,iffeed) VALUES (".$f['uid'].",".$f['fuid'].",'',1)");
		$goon ++;
		$s_c ++;
	}
	if ($goon == $percount)
	{
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
		report_log();
		newURL($step);
	}
}
elseif($step == '42')
{
	//空间留言
	if(!file_exists(S_P."tmp_uch.php")){
		newURL($step);
	}
	else{
		require(S_P."tmp_uch.php");
	    $charset_change = 1;
	    $UCHDB = new mysql($uch_db_host, $uch_db_user, $uch_db_password, $uch_db_name, '');
	}
    if(!$start){
		$DDB->query("TRUNCATE TABLE {$pw_prefix}oboard");
	}
	$query = $UCHDB->query("SELECT * FROM {$uch_db_prefix}comment WHERE idtype='uid' LIMIT $start, $percount");
	$goon = 0;
	while ($rt = $UCHDB->fetch_array($query)) {
		$goon++;
		$s_c ++;
		$uid		=	$rt['authorid'];
		$username	=	$rt['author'];
        $touid	=	$rt['uid'];
		$title		=	$rt['message'];
		$postdate	=	$rt['dateline'];
		unset($oboarddb);

		$oboarddb = array(
			'uid'=> $uid,
			'username'=> $username,
            'touid'=> $touid,
			'title'=> $title,
			'postdate'=> $postdate,
            'ifwordsfb'=>1
		);

		$DDB->update("INSERT INTO {$pw_prefix}oboard SET".pwSqlSingle($oboarddb));
	}

	if ($goon == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else{
		$maxid = $UCHDB->get_value("SELECT max(cid) FROM {$uch_db_prefix}comment WHERE idtype='uid'");
		report_log();
		newURL($step);
	}
}
else
{
	ObHeader($basename.'?action=finish&dbtype='.$dbtype);
}

##########################

function getFieldSqlByType($type) {
	if (in_array($type,array('number','calendar'))) {
		$sql = "INT(10) UNSIGNED NOT NULL default '0'";
	} elseif (in_array($type,array('radio','select'))){
		$sql = "TINYINT(3) UNSIGNED NOT NULL default '0'";
	} elseif ($type == 'textarea') {
		$sql = "TEXT NOT NULL";
	} else {
		$sql = "VARCHAR(255) NOT NULL";
	}
	return $sql;
}
function update_markinfo($fid, $tid, $pid) {
	global $DDB,$pw_prefix;
	$perpage = 10;
	$pid = intval($pid);
	$whereStr = " fid=".pwEscape($fid)." AND tid=".pwEscape($tid)." AND pid=" . pwEscape($pid) . " AND ifhide=0 ";
	$count = $DDB->get_value("SELECT COUNT(*) FROM {$pw_prefix}pinglog WHERE $whereStr ");
	$markInfo = "";
    $ifmarkcount=0;
	if ($count) {
		$query = $DDB->query("SELECT id,point FROM {$pw_prefix}pinglog WHERE $whereStr ORDER BY pingdate DESC LIMIT 0,$perpage");
		$ids = array();
		while ($rate = $DDB->fetch_array($query)) {
			$ids[] = $rate['id'];
            $ifmarkcount += $rate['point'];
		}
		$markInfo = $count . ":" . implode(",", $ids);
	}
	if ($pid == 0) {
		//$pw_tmsgs = GetTtable($tid);
		$pw_tmsgs = "{$pw_prefix}tmsgs";
		$DDB->update("UPDATE {$pw_prefix}threads SET ifmark=" . $ifmarkcount . " WHERE tid=" . pwEscape($tid));
		$DDB->update("UPDATE $pw_tmsgs SET ifmark=" . pwEscape($markInfo) . " WHERE tid=" . pwEscape($tid));
	} else {
		$DDB->update("UPDATE {$pw_prefix}posts SET ifmark=".pwEscape($markInfo)." WHERE pid=".pwEscape($pid));
	}
	return $markInfo;
}

function changegroups()
{
	global $pw_prefix, $source_prefix, $SDB, $DDB, $dest_charset;
	require_once S_P.'lang_'.$dest_charset.'.php';
	$DDB->update("TRUNCATE TABLE {$pw_prefix}usergroups");
	$DDB->update($lang['group']);
	$grelation = array(1=>3,2=>4,3=>5,4=>6,5=>6,6=>6,7=>2,8=>7);
	$query = $SDB->query("SELECT * FROM {$source_prefix}usergroups WHERE type = 'member' OR type = 'special'");
	$specialdata = array();
	while ($g = $SDB->fetch_array($query))
	{
		$gid = $g['groupid'];
		$gptype = $g['type'];
		$grouptitle = addslashes($g['grouptitle']);
		$groupimg = 8;
		$grouppost = (int)$g['creditshigher'];
		$maxmsg = (int)$g['maxpmnum'];
		$allowhide = $g['allowinvisible'];
		$allowread = $g['readaccess'] ? 1 : 0;
		$allowportait = $g['allowavatar'] ? 1 : 0;
		$upload = $g['allowavatar'] == 3 ? 1 : 0;
		$allowrp = $g['allowreply'];
		$allowhonor = $g['allownickname'];//个性签名-昵称
		$allowdelatc = 1;
		$allowpost = $g['allowpost'];
		$allownewvote = $g['allowpostpoll'];
		$allowvote = $g['allowvote'];
		$allowactive = $g['allowpostactivity'];
		$htmlcode = $g['allowhtml'];
		$wysiwyg = 0;
		$allowhidden = $g['allowhidecode'];
		$allowencode = $g['allowsetreadperm'];
		$allowsell = $g['maxprice'] ? 1 : 0;
		$allowsearch = $g['allowsearch'];
		$allowmember = 1;
		$allowprofile = $g['allowviewpro'];
		$allowreport = 1;
		$allowmessege = $g['maxpmnum'] ? 1 : 0;
		$allowsort = $g['allowviewstats'];
		$alloworder = 1;
		$allowupload = $g['allowpostattach'] ? 2 : 0;
		$allowdownload = $g['allowgetattach'] ? 2 : 0;
		$allowloadrvrc = 1;
		$allownum = 50;
		$edittime = 0;
		$postpertime = 0;
		$searchtime = 0;
		$signnum = $g['maxsigsize'];
		$uploadtype = $mright = array();
		if ($g['attachextensions'])
		{
			$attachext = explode(',', $g['attachextensions']);
			foreach($attachext as $v)
			{
				$uploadtype[trim(strtolower($v))] = 1000;
			}
		}
		$mright['atclog'] = $mright['show'] = $mright['msggroup'] = $mright['ifmemo'] = $mright['modifyvote'] = $mright['viewvote'] = $mright['allowreward'] = $mright['allowencode'] = $mright['leaveword'] = $mright['viewvote'] = $mright['viewvote'] = 1;
		$mright['viewipfrom'] = $mright['anonymous'] = $mright['dig'] = $mright['atccheck'] = $mright['markable'] = $mright['postlimit'] = 0;
		$mright['imgwidth'] = $mright['imgheight'] = $mright['fontsize'] = $mright['maxsendmsg'] = $mright['maxfavor'] = $mright['maxgraft'] = '';
		$mright['uploadtype'] = $uploadtype ? addslashes(serialize($uploadtype)) : '';
		$mright['media']  = $mright['pergroup'] = '';
		$mright['markdb'] = "10|0|10||1";
		$mright['schtime'] = 'all';
		$mright = P_serialize($mright);
		$ifdefault = 0;
		$allowadmincp = $visithide = $delatc = $moveatc = $copyatc = $typeadmin = $viewcheck = $viewclose = $attachper = $delattach = $viewip = $markable = $maxcredit = $credittype = $creditlimit = $banuser = $bantype = $banmax = $viewhide = $postpers = $atccheck = $replylock = $modown = $modother = $deltpcs = 0;
		$sright = '';
		pwGroupref(array('gid'=>$gid,'gptype'=>$gptype,'grouptitle'=>$grouptitle,'groupimg'=>$groupimg,'grouppost'=>$grouppost,'maxmsg'=>$maxmsg,'allowhide'=>$allowhide,'allowread'=>$allowread,'allowportait'=>$allowportait,'upload'=>$upload,'allowrp'=>$allowrp,'allowhonor'=>$allowhonor,'allowdelatc'=>$allowdelatc,'allowpost'=>$allowpost,'allownewvote'=>$allownewvote,'allowvote'=>$allowvote,'allowactive'=>$allowactive,'htmlcode'=>$htmlcode,'wysiwyg'=>$wysiwyg,'allowhidden'=>$allowhidden,'allowencode'=>$allowencode,'allowsell'=>$allowsell,'allowsearch'=>$allowsearch,'allowmember'=>$allowmember,'allowprofile'=>$allowprofile,'allowreport'=>$allowreport,'allowmessage'=>$allowmessege,'allowsort'=>$allowsort,'alloworder'=>$alloworder,'allowupload'=>$allowupload,'allowdownload'=>$allowdownload,'allowloadrvrc'=>$allowloadrvrc,'allownum'=>$allownum,'edittime'=>$edittime,'postpertime'=>$postpertime,'searchtime'=>$searchtime,'signnum'=>$signnum,'mright'=>$mright,'ifdefault'=>$ifdefault,'allowadmincp'=>$allowadmincp,'visithide'=>$visithide,'delatc'=>$delatc,'moveatc'=>$moveatc,'copyatc'=>$copyatc,'typeadmin'=>$typeadmin,'viewcheck'=>$viewcheck,'viewclose'=>$viewclose,'attachper'=>$attachper,'delattach'=>$delattach,'viewip'=>$viewip,'markable'=>$markable,'maxcredit'=>$maxcredit,'credittype'=>$credittype,'creditlimit'=>$creditlimit,'banuser'=>$banuser,'bantype'=>$bantype,'banmax'=>$banmax,'viewhide'=>$viewhide,'postpers'=>$postpers,'atccheck'=>$atccheck,'replylock'=>$replylock,'modown'=>$modown,'modother'=>$modother,'deltpcs'=>$deltpcs,'sright'=>$sright));
		$grouptitle=getGrouptitle($gid,$grouptitle,false);
		$DDB->update("INSERT INTO {$pw_prefix}usergroups (gid,gptype,grouptitle,groupimg,grouppost) VALUES ('$gid','$gptype','$grouptitle','$groupimg','$grouppost')");

		if ($g['type'] == 'special')
		{
			$specialdata[$g['groupid']] = '1';
		}
		$grelation[$g['groupid']] = $g['groupid'];
	}
	writeover(S_P.'tmp_grelation.php', "\$_grelation = ".pw_var_export($grelation).";", true);
	return $specialdata;
}
function getupadmin($fid, &$upadmin)
{
	global $catedb;
	if ($catedb[$fid]['moderators'])
	{
		$moderators = explode("\t", $catedb[$fid]['moderators']);
		foreach($moderators as $value)
		{
			$upadmin .= $upadmin ? addslashes($value).',' : ','.addslashes($value).',';
		}
	}
	if ($catedb[$fid] && $catedb[$fid]['type'] != 'group')
	{
		getupadmin($catedb[$fid]['fup'], $upadmin);
	}
}

function dz_ubb($content)
{
	$content = str_replace(array('[wma]','[/wma]','[flash]','[swf]','[/swf]','[rm]','[ra]','[php]','[/php]','[/ra]','[wmv]','[mp3]','[/mp3]','[audio]','[/audio]','[i=s]'),array('[wmv=0]','[/wmv]','[flash=314,256,1]','[flash=314,256,1]','[/flash]','[rm=314,256,1]','[rm=314,256,1]','[code]','[/code]','[/rm]','[wmv=314,256,1]','[wmv=1]','[/wmv]','[wmv=1]','[/wmv]','[i]'),$content);
	$content = preg_replace(array('~\[code\](.+?)\[\/code\]~ies','~\[media=mp3,\d+?,\d+?,(?:1|0)\](.+?)\[\/media\]~i','~\[media=(?:wmv|mov|wma),(\d+?),(\d+?),(1|0)\](.+?)\[\/media\]~i','~\[media=(rm|ra),(\d+?),(\d+?),(1|0)\](.+?)\[\/media\]~i','~\[media=swf,(\d+?),(\d+?)\](.+?)\[\/media\]~i','~\[hide=(\d+?)\](.+?)\[\/hide\]~is','~\[hide\](.+?)\[\/hide\]~is','~\[localimg=[0-9]+,[0-9]+\]([0-9]+)\[\/localimg\]~is','~\[local\]([0-9]+)\[\/local\]~is','~\[attach\]([0-9]+)\[\/attach\]~is','/\[img=[0-9]+,[0-9]+\]/i','/\[size=(\d+(\.\d+)?(px|pt|in|cm|mm|pc|em|ex|%)+?)\]/i','/\[p=(\d+)\,(\s+\d+)\,(\s+)(left|center|right|justify)\](.+?)\[\/p\]/is'),array("ccode('\\1')",'[wmv=0]\\1[/wmv]','[wmv=\\1,\\2,\\3]\\4[/wmv]','[rm=\\2,\\3,\\4]\\5[/rm]','[flash=\\1,\\2,1]\\3[/flash]','[sell=\\1]\\2[/sell]','[post]\\1[/post]','[attachment=\\1]','[attachment=\\1]','[attachment=\\1]','[img]','','<p align=\"\\4\">\\5</p>'),$content);
	return $content;
}

function ccode($code)
{
	return htmlspecialchars($code);
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

//取得附件类型
function getfileinfo($filename = '')
{
	$extnum		=	strrpos($filename, '.') + 1;
	$file_ext	=	strtolower(substr($filename, $extnum));
	switch ($file_ext)
	{
		case 'jpg':
			$fileinfo['type'] = 'img';
			$fileinfo['ifupload'] = 1;
		break;
		case 'jpe':
			$fileinfo['type'] = 'img';
			$fileinfo['ifupload'] = 1;
		break;
		case 'jpeg':
			$fileinfo['type'] = 'img';
			$fileinfo['ifupload'] = 1;
		break;
		case 'gif':
			$fileinfo['type'] = 'img';
			$fileinfo['ifupload'] = 1;
		break;
		case 'bmp':
			$fileinfo['type'] = 'img';
			$fileinfo['ifupload'] = 1;
		break;
		case 'png':
			$fileinfo['type'] = 'img';
			$fileinfo['ifupload'] = 1;
		break;
		case 'rar':
			$fileinfo['type'] = 'zip';
			$fileinfo['ifupload'] = 3;
		break;
		case 'zip':
			$fileinfo['type'] = 'zip';
			$fileinfo['ifupload'] = 3;
		break;
		case 'txt':
			$fileinfo['type'] = 'txt';
			$fileinfo['ifupload'] = 2;
		break;
		default:
			$fileinfo['type'] = 'zip';
			$fileinfo['ifupload'] = 3;
		break;
	}
	return $fileinfo;
}
?>
