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
	$SDB = new mysql($source_db_host, $source_db_user, $source_db_password, $source_db_name);
}

if ($step == 1)
{
	//论坛设置
	$DDB->query("TRUNCATE TABLE {$pw_prefix}wordfb");
	require_once S_P.'function.php';

	$siteConfig = array(
		'bbname'		=>'db_bbsname',
		'siteurl'		=>array('db_bbsurl','dz_url'),
		'icp'			=>array('db_icp','dz_icp'),
		'bbclosed'		=>array('db_bbsifopen','dz_siteopen'),
		'closedreason'	=>'db_whybbsclose',
		'regadvance'	=>'rg_regdetail',
		'censoruser'	=>array('rg_banname','dz_banname'),
		'regverify'		=>array('rg_emailcheck','dz_regcheck'),
		'censoremail'	=>array('rg_email','dz_regemail'),
		'regctrl'		=>'rg_allowsameip',
		'bbrules'		=>'rg_reg',
		'frameon'		=>'db_columns',
		'seotitle'		=>'db_bbstitle',
		'seokeywords'	=>'db_metakeyword',
		'seodescription'=>'db_metadescrip',
	);

	$query = $SDB->query("SELECT variable, value FROM {$source_prefix}settings WHERE variable IN ('".implode('\',\'', array_keys($siteConfig))."')");
	while ($s = $SDB->fetch_array($query))
	{
		if (is_array($siteConfig[$s['variable']]))
		{
			$db_value = $siteConfig[$s['variable']][1]($s['value']);
			$db_name  = $siteConfig[$s['variable']][0];
		}
		else
		{
			$db_value = $s['value'];
			$db_name  = $siteConfig[$s['variable']];
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
	if (!$start)
	{
        //勋章
        $DDB->query("TRUNCATE TABLE {$pw_prefix}medalinfo");
        $DDB->query("TRUNCATE TABLE {$pw_prefix}medalslogs");
        $DDB->query("ALTER TABLE {$pw_prefix}medalinfo CHANGE id id SMALLINT( 6 ) NOT NULL AUTO_INCREMENT");
        $query = $SDB->query("SELECT medalid,name,image FROM {$source_prefix}medals");
        while ($m = $SDB->fetch_array($query))
        {
            $DDB->update("INSERT INTO {$pw_prefix}medalinfo (id,name,intro,picurl) VALUES (".$m['medalid'].",'".addslashes($m['name'])."','".addslashes($m['name'])."','".addslashes($m['image'])."')");
            $s_c++;
        }
    }

    $lastid = $start;
    $medallog = array();
	$query = $SDB->query("SELECT * FROM {$source_prefix}medallog m WHERE m.id > $start ORDER BY id LIMIT $percount");
	while ($l = $SDB->fetch_array($query))
	{
        $lastid = $l['id'];
        $awardee = $SDB->get_value("SELECT username FROM {$source_prefix}members WHERE uid=".$l['uid']);
        $timelimit = floor(($l['expiration'] - $l['dateline']) / 60*60*24*30);
        //$timelimit = $l['expiration'];
        $l['status'] = 1;
        $medallog[] = "(".$l['id'].",'".addslashes($awardee)."','','".$l['dateline']."','$timelimit','','".$l['medalid']."','".$l['status']."','')";
		$s_c++;
	}
    if($medallog){
        $medallogarr = implode(",",$medallog);
	    $DDB->update("INSERT INTO {$pw_prefix}medalslogs (id,awardee,awarder,awardtime,timelimit,state,level,action,why) VALUES $medallogarr");
    }
	$maxid = $SDB->get_value("SELECT max(id) FROM {$source_prefix}medallog");
    if ($lastid < $maxid)
	{
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
	    report_log();
	    newURL($step, '&medal=yes');
		exit();
	}
}
elseif ($step == '4')
{
	//禁言会员处理数据初始化
	$banusersql = $banuids = array();

	//会员
	$insertadmin = '';
	$_specialgroup = array();
	require_once (S_P.'tmp_credit.php');
	require_once (S_P.'tmp_uc.php');

	if (!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}members");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}memberdata");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}membercredit");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}memberinfo");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}administrators");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}banuser");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}credits");
		foreach ($expandCredit as $v)//自定义积分
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

		writeover(S_P.'tmp_group.php', "\$_specialgroup = ".pw_var_export(changegroups()).";", true);//更新用户组并保存特殊组到临时文件

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
		$addfields && $DDB->update("ALTER TABLE {$pw_prefix}members ADD salt CHAR(6) ".$DDB->collation()." NOT NULL DEFAULT ''");
	}
    //增加uc
	$charset_change = 1;
	$UCDB = new mysql($uc_db_host, $uc_db_user, $uc_db_password, $uc_db_name);

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
	$query = $SDB->query("SELECT m.uid,m.username,m.password,m.secques,m.gender,m.adminid,m.groupid,m.groupexpiry,m.extgroupids,m.regip,m.regdate,m.lastip,m.lastvisit,m.lastactivity,m.lastpost,m.posts,m.digestposts,m.oltime,m.pageviews,m.credits,m.extcredits1,m.extcredits2,m.extcredits3,m.extcredits4,m.extcredits5,m.extcredits6,m.extcredits7,m.extcredits8,m.email,m.bday,m.sigstatus,m.tpp,m.ppp,m.styleid,m.dateformat,m.timeformat,m.pmsound,m.showemail,m.newsletter,m.invisible,m.timeoffset,m.accessmasks,m.editormode,
			mf.nickname,mf.site,mf.alipay,mf.icq,mf.qq,mf.yahoo,mf.msn,mf.taobao,mf.location,mf.customstatus,mf.medals,mf.avatar,mf.avatarwidth,mf.avatarheight,mf.bio,mf.sightml,mf.ignorepm,mf.groupterms,mf.authstr,mf.spacename,mf.buyercredit,mf.sellercredit".$querysql.",
			ol.thismonth, ol.total
			FROM {$source_prefix}members m
			LEFT JOIN {$source_prefix}memberfields mf USING(uid)
			LEFT JOIN {$source_prefix}onlinetime ol USING(uid)
			WHERE m.uid > $start
			ORDER BY uid LIMIT $percount");

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
		//判断用户是否有新短信
		$newpm = $UCDB->get_value("select count(pmid) from {$uc_db_prefix}pms where msgtoid=".$m['uid']." and new=1");
		if($newpm > 1)$newpm=1;else $newpm=0;

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

		//禁言会员处理
        if($groupid == '6')
        {
            $timestamp=time();
            if ($m['groupexpiry'])
            {	//用户组有效期
                if($m['groupexpiry'] > $timestamp)
                {
                    $days = ceil(($m['groupexpiry'] - $timestamp)/86400);
                    $banusersql[] = array($m['uid'],0,1,$timestamp,$days,'','');
                    $banuids[] = $m['uid'];
                }
            }
            else
            {
                $banusersql[] = array($m['uid'],0,2,$timestamp,0,'','');
                $banuids[] = $m['uid'];
                if(!$speed){
                    $DDB->update("REPLACE INTO {$pw_prefix}banuser (uid,fid,type,startdate,days,admin,reason) VALUES (".$m['uid'].",0,2,$timestamp,0,'','')");
                }
            }
        }

		//自定义积分 处理
		eval($creditdata);
		$expandCreditSQL = '';
		if($expandCredit)//自定义积分
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
		//$medals = $m['medals'] ? str_replace("\t", ',', $m['medals']) : '';

        //勋章add by yth 100317
		$medals = '';
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
		$uc = $UCDB->get_one("SELECT m.password,m.salt,mf.blacklist FROM {$uc_db_prefix}members m LEFT JOIN {$uc_db_prefix}memberfields mf USING (uid) WHERE m.uid=".$m['uid']);
		$uc['blacklist'] && $uc['blacklist'] != '{ALL}' && $banpm = $uc['blacklist'];

        //密码
		$m['ignorepm'] && $m['ignorepm'] != '{ALL}' && $banpm = $m['ignorepm'];


        if(!$speed)//一条一条插
        {
            $DDB->update("REPLACE INTO {$pw_prefix}members (uid,username,password,email,groupid,icon,gender,regdate,signature,introduce,oicq,icq,msn,yahoo,site,location,honor,bday,timedf,t_num,p_num,newpm,banpm,medals,userstatus,salt) VALUES (".$m['uid'].",'".$m['username']."','".$uc['password']."','".$m['email']."',".$groupid.",'".$userface."',".$m['gender'].",".$m['regdate'].",'".$m['sightml']."','".$introduce."','".$m['qq']."','".$m['icq']."','".$m['msn']."','".$m['yahoo']."','".$m['site']."','".$m['location']."','".$m['customstatus']."','".$m['bday']."','".$timedf."','".$m['tpp']."','".$m['ppp']."',".$newpm.",'$banpm','$medals','".$userstatus."','".$uc['salt']."')");
            $DDB->update("REPLACE INTO {$pw_prefix}memberdata (uid,postnum,digests,rvrc,money,credit,currency,lastvisit,thisvisit,lastpost,onlinetime,monoltime) VALUES (".$m['uid'].",".$m['posts'].",".$m['digestposts'].",".$rvrc.",".$money.",".$credit.",".$currency.",'".$m['lastvisit']."','".$m['lastactivity']."','".$m['lastpost']."','".intval($m['total']*60)."','".intval($m['thismonth']*60)."') ");
        }

        if($speed){//批量插
		    $membersql[]  = "(".$m['uid'].",'".$m['username']."','".$uc['password']."','".$m['email']."',".$groupid.",'".$userface."',".$m['gender'].",".$m['regdate'].",'".$m['sightml']."','".$introduce."','".$m['qq']."','".$m['icq']."','".$m['msn']."','".$m['yahoo']."','".$m['site']."','".$m['location']."','".$m['customstatus']."','".$m['bday']."','".$timedf."','".$m['tpp']."','".$m['ppp']."',".$newpm.",'$banpm','$medals','".$userstatus."','".$uc['salt']."')";
		    $memdatasql[] = "(".$m['uid'].",".$m['posts'].",".$m['digestposts'].",".$rvrc.",".$money.",".$credit.",".$currency.",'".$m['lastvisit']."','".$m['lastactivity']."','".$m['lastpost']."','".intval($m['total']*60)."','".intval($m['thismonth']*60)."')";
        }
		$s_c++;
	}

    if($speed){//批量插
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
		$DDB->update("REPLACE INTO {$pw_prefix}medaluser (uid,mid) VALUES $medaluserstr ");
	}

	$maxid = $SDB->get_value("SELECT max(uid) FROM {$source_prefix}members");
    echo '最大id',$maxid;
    echo '最后id',$lastid;
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
	$DDB->query("ALTER TABLE {$pw_prefix}forums CHANGE keywords keywords TEXT ".$DDB->collation()." NOT NULL");
	$DDB->query("TRUNCATE TABLE {$pw_prefix}topictype");//75新增主题分类表

	require_once S_P.'tmp_grelation.php';//用户组
	$catedb = $insertforumsextra = $typearray = array();
	$fright = array('viewperm'=>'allowvisit','postperm'=>'allowpost','replyperm'=>'allowrp',
	'postattachperm'=>'allowupload','getattachperm'=>'allowdownload');
	$insertforums = $insertforumdata = $forumsextra = '';
	$query = $SDB->query("SELECT f.fid,f.fup,f.type,f.name,f.status,f.displayorder,f.styleid,f.threads,f.posts,f.todayposts,f.lastpost,f.allowsmilies,f.allowhtml,
	f.allowbbcode,f.allowimgcode,f.allowmediacode,f.allowanonymous,f.allowshare,f.allowpostspecial,f.allowspecialonly,f.alloweditrules,f.recyclebin,f.modnewposts,
	f.jammer,f.disablewatermark,f.inheritedmod,f.autoclose,f.forumcolumns,f.threadcaches,f.alloweditpost,f.simple,f.modworks,f.allowtag,fd.description,fd.password,
	fd.icon,fd.postcredits,fd.replycredits,fd.getattachcredits,fd.postattachcredits,fd.digestcredits,fd.redirect,fd.attachextensions,fd.formulaperm,fd.moderators,
	fd.rules,fd.threadtypes,fd.viewperm,fd.postperm,fd.replyperm,fd.getattachperm,fd.postattachperm,fd.keywords,fd.supe_pushsetting,fd.modrecommend,fd.tradetypes,
	fd.typemodels FROM {$source_prefix}forums f LEFT JOIN {$source_prefix}forumfields fd USING(fid)");

	while($f = $SDB->fetch_array($query))//版块信息
	{
		$catedb[$f['fid']] = $f;
	}

	Add_S($catedb);

	foreach($catedb as $fid => $f)
	{
		$addtpctype  = '';
		$t_type      = '';
		if ($f['fid'] == $f['fup'])
		{
			$f['fup'] = 0;
		}

		if ('group' == $f['type'])
		{
			$f['type'] = 'category';
		}

		if ($f['threadtypes'])
		{
			$threadtypes = unserialize(stripcslashes($f['threadtypes']));
			$addtpctype  = (int)$threadtypes['prefix'];
			//$i = 1;
			$t_type .= ($threadtypes['required'] ? '2' : '1')."\t";
            $order = 0;
			foreach ($threadtypes['types'] as $kk => $vv)
			{
				//$typearray[$f['fid']][$kk] = $i++;//原来去除
				$t_type .= $vv."\t";
                $pw_topictype['fid'] = $f['fid'];
                $pw_topictype['name'] = $vv;
                $pw_topictype['vieworder'] = $order;
                $DDB->update("INSERT INTO {$pw_prefix}topictype SET ".pwSqlSingle($pw_topictype));
			    $topictypeid = $DDB->insert_id();
			    $typearray[$f['fid']][$kk] = $topictypeid;//用新的
                $order++;
			}
			$t_type = rtrim($t_type); //主题分类
		}
		getupadmin($f['fid'], $upadmin);//把该版块的上级管理员账号传给upadmin
		//pw_forums 表数据
		$pw_forums['fid'] 			= $f['fid'];	//板块id
		$pw_forums['fup'] 			= $f['fup'];	//上级板块id
		$pw_forums['ifsub'] 		= $f['type'] == 'sub' ? 1 : 0;	//是否为子板块
		$pw_forums['childid'] 		= getIfHasChild($catedb,$fid);	//此板块是否有下级子板块  //返回1说明是有子版块
		$pw_forums['type'] 			= $f['type'];	//类型（'category'-分类 'forum'-板块 'sub'-子板块)
		$pw_forums['logo'] 			= $f['icon'];	//板块logo
		$pw_forums['name'] 			= $f['name'];	//板块名称
		$pw_forums['descrip'] 		= $f['description'];	//板块介绍
		$pw_forums['dirname'] 		= '';	//版块二级目录设置(分类)
		$pw_forums['keywords'] 		= $f['keywords'];	//版块关键字
		$pw_forums['vieworder'] 	= $f['displayorder'];	//板块排序
		$pw_forums['forumadmin'] 	= $f['moderators'] ? ','.str_replace("\t",',', $f['moderators']).',' : ''; 	//版主名单
		$pw_forums['fupadmin'] 		= $upadmin;	//版块上级版主
		$pw_forums['style'] 		= '';	//板块风格
		$pw_forums['across'] 		= $f['forumcolumns'];	//板快排列方式(默认0表示列排，大于0的整数表示横排)
		$pw_forums['allowhtm'] 		= 0;	//是否静态页面
		$pw_forums['allowhide'] 	= 1;	//是否允许发隐藏贴
		$pw_forums['allowsell'] 	= '1';	//是否允许发出售帖
		$pw_forums['allowtype'] 	= '31';	//允许发表的主题类型
		$pw_forums['copyctrl'] 		= $f['jammer'];//是否使用水印
		$pw_forums['allowencode'] 	= 0;	//是否允许加密贴
		$pw_forums['password'] 		= $f['password'] ? md5($f['password']) : '';	//板块密码（md5）
		$pw_forums['viewsub'] 		= $f['simple'] & 1;	//是否显示子版
		$pw_forums['allowvisit'] 	= allow_group_str($f['viewperm']);	//允许浏览版块用户组
		$pw_forums['allowread'] 	= allow_group_str($f['viewperm']);	//允许浏览帖子用户组
		$pw_forums['allowpost'] 	= allow_group_str($f['postperm']);	//允许发表主题用户组
		$pw_forums['allowrp'] 		= allow_group_str($f['replyperm']);	//允许发表回复用户组
		$pw_forums['allowdownload'] = allow_group_str($f['getattachperm']);	//允许下载附件用户组
		$pw_forums['allowupload'] 	= allow_group_str($f['postattachperm']);	//允许上传附件用户组
		$pw_forums['f_type'] 		= $f['type'] == 'category' ? '' : 'forum';	//板块类型（加密，开放。。。）
		$pw_forums['forumsell'] 	= '';//版块出售积分类型
		$pw_forums['f_check'] 		= ($f['modnewposts'] == '2') ? '3' : (int)$f['modnewposts'];//发帖审核
		$pw_forums['t_type']        = $t_type;   //主题分类
		$pw_forums['cms'] 			= 0;//文章系统分类id
		$pw_forums['ifhide'] 		= 1;//是否隐藏
		$pw_forums['showsub'] 		= 0;//是否在首页显示子版块
		//$pw_forums['forumtype'] 	= '';//社区模式下分类页面版块的展示类型//75里面把这个字段去除了

		$DDB->update("INSERT INTO {$pw_prefix}forums SET ".pwSqlSingle($pw_forums));

		//pw_forumdata 表数据
		$pw_forumdata['fid'] 		= $f['fid'];//板快id
		$pw_forumdata['tpost'] 		= $f['todayposts'];//今日发帖数
		$pw_forumdata['topic'] 		= $f['threads'];//板块中的主题
		$pw_forumdata['article'] 	= $f['posts'];//帖子个数
		$pw_forumdata['subtopic'] 	= 0;//子板块主题
		$pw_forumdata['top1'] 		= 0;//本板块置顶数统计
		$pw_forumdata['top2'] 		= 0;//分类置顶和总置顶数统计
		$pw_forumdata['aid'] 		= '';//单个公告ID
		$pw_forumdata['aidcache'] 	= '';//公告缓存更新时间
		$pw_forumdata['aids'] 		= '';//多个公告ID
		$pw_forumdata['lastpost'] 	= $f['lastpost'] ? getLastpost($f['lastpost']) : '';//最后一帖信息

		$DDB->update("INSERT INTO {$pw_prefix}forumdata SET ".pwSqlSingle($pw_forumdata));

		//pw_forumsextra 表数据
		$pw_forumsextra['fid']         	= $f['fid']; //板快id
		$pw_forumsextra['creditset']	= '';        //后台板块管理版块积分设置

		$arr_forumset['addtpctype'] = $addtpctype; //是否在标题前面加上主题分类名称
		$pw_forumsextra['forumset'] = addslashes(serialize($arr_forumset)); //TODO 后台板块管理基本资料设置
		$pw_forumsextra['commend']  = '';

		$DDB->update("INSERT INTO {$pw_prefix}forumsextra SET ".pwSqlSingle($pw_forumsextra));

		$s_c++;
	}
	//获取版块最大fid
	$maxid=$SDB->get_value("select max(fid) from {$source_prefix}forums");
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
		//$DDB->query("TRUNCATE TABLE {$pw_prefix}poststopped");
	}

	$query = $SDB->query("SELECT t.tid,t.iconid,t.typeid,t.readperm,t.price,t.lastpost,t.lastposter,t.views,t.replies,t.displayorder,t.highlight,t.digest,t.special,t.attachment,t.moderated,t.closed,t.itemid,
			p.pid,p.fid,p.first,p.author,p.authorid,p.subject,p.dateline,p.message,p.useip,p.invisible,p.anonymous,p.usesig,p.htmlon,p.bbcodeoff,p.smileyoff,p.parseurloff,p.attachment,p.rate,p.ratetimes,p.status
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
				$special = 20;//活动
				break;
			case '5':
				$special = 5;//辩论
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
		$ifcheck = $t['invisible'] < 0 ? '0' : '1';  //DZ中-1为放入回收站 0为审核通过
		$ifmark='';//评分

        if(!$speed)//一条一条插
        {
            $threadsqlstr = "(".$t['tid'].",".$t['fid'].",'".addslashes($titlefont)."','".addslashes($t['author'])."',".$t['authorid'].",'".addslashes($t['subject'])."','$ifcheck',".$t['typeid'].",".$t['dateline'].",".$t['lastpost'].",'".addslashes($t['lastposter'])."',".$t['views'].",".$t['replies'].",{$topped},".$t['closed'].",".$t['digest'].",{$special},'".$ifupload."','".$ifmarkcount."',".$t['status'].",".$t['anonymous'].")";
            $tmsgssqlstr = "(".$t['tid'].",'".$t['attachment']."','".$t['useip']."',".$t['usesig'].",'','','".addslashes($tag)."',".((convert($t['message']) == $t['message'])? 1 : 2).",'".$t['message']."','".$ifmark."')";

            if($threadsqlstr)
            {
                $DDB->update("REPLACE INTO {$pw_prefix}threads (tid,fid,titlefont,author,authorid,subject,ifcheck,type,postdate,lastpost,lastposter,hits,replies,topped,locked,digest,special,ifupload,ifmark,ifshield,anonymous) VALUES $threadsqlstr ");
            }

            if($tmsgssqlstr)
            {
                $DDB->update("REPLACE INTO {$pw_prefix}tmsgs (tid,aid,userip,ifsign,buy,ipfrom,tags,ifconvert,content,ifmark) VALUES $tmsgssqlstr ");
            }
        }

        if($speed)//批量插
        {
            $threadsql[] = "(".$t['tid'].",".$t['fid'].",'".addslashes($titlefont)."','".addslashes($t['author'])."',".$t['authorid'].",'".addslashes($t['subject'])."','$ifcheck',".$t['typeid'].",".$t['dateline'].",".$t['lastpost'].",'".addslashes($t['lastposter'])."',".$t['views'].",".$t['replies'].",{$topped},".$t['closed'].",".$t['digest'].",{$special},'".$ifupload."','".$ifmarkcount."',".$t['status'].",".$t['anonymous'].")";
            $tmsgssql[] = "(".$t['tid'].",'".$t['attachment']."','".$t['useip']."',".$t['usesig'].",'','','".addslashes($tag)."',".((convert($t['message']) == $t['message'])? 1 : 2).",'".$t['message']."','".$ifmark."')";
        }

		$s_c++;
	}

    if($speed)//批量插
    {
		if($threadsql)
		{
		    $threadsqlstr = implode(",",$threadsql);
			$DDB->update("REPLACE INTO {$pw_prefix}threads (tid,fid,titlefont,author,authorid,subject,ifcheck,type,postdate,lastpost,lastposter,hits,replies,topped,locked,digest,special,ifupload,ifmark,ifshield,anonymous) VALUES $threadsqlstr ");
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
	if ($maxid > $lastid)
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
	//交易  //TODO
	$tid = $SDB->get_one("SELECT tid FROM {$source_prefix}trades LIMIT $start, 1");
	if (!$tid)
	{
		$maxid = $SDB->get_value("SELECT max(tid) FROM {$source_prefix}trades");
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
	//悬赏  //TODO
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}reward");
	}

	$goon = 0;
	$query = $SDB->query("SELECT tid,authorid,answererid,dateline FROM {$source_prefix}rewardlog WHERE authorid <> 0 LIMIT $start, $percount");

	while($r = $SDB->fetch_array($query))
	{
		$lastid=$r['tid'];
		$goon ++;
		$rewardinfo = $SDB->get_one("SELECT t.price,p.author FROM {$source_prefix}posts p LEFT JOIN {$source_prefix}threads t USING (tid) WHERE p.first = 0 AND p.tid = ".$r['tid']." ORDER BY p.dateline ASC LIMIT 1");
		if (!$rewardinfo)
		{
			$f_c++;
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
	$maxid=$SDB->get_value("select max(tid) from {$source_prefix}rewardlog ");
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
		$DDB->update("TRUNCATE TABLE {$pw_prefix}polls");
	}

	$query = $SDB->query("SELECT p.*, t.dateline FROM {$source_prefix}polls p LEFT JOIN {$source_prefix}threads t USING (tid) WHERE p.tid >= $start ORDER BY p.tid LIMIT $percount");
	while($v = $SDB->fetch_array($query))
	{
        $lastid = $v['tid'];
		$votearray = array();
		$vop = $SDB->query("SELECT * FROM {$source_prefix}polloptions WHERE tid = ".$v['tid']." ORDER BY polloptionid");
		while($rt = $SDB->fetch_array($vop))
		{
			$voteuser = array();
			$votes = 0;
			if ($rt['voterids'])
			{
				$tmp_uids = explode("\t",$rt['voterids']);
				$uids = '';
				foreach ($tmp_uids as $uv)
				{
					if ($uv=="") continue;
					if ($uv && strpos($uv,'.')!==FALSE) continue;

					$q2 = $DDB->get_one("SELECT uid,username FROM {$pw_prefix}members WHERE uid='$uv'");
                    ADD_S($q2);
					if ('' != $q2['uid'])
					{
						$DDB->update("REPLACE INTO {$pw_prefix}voter (tid,uid,username,vote,time) VALUES ('{$v['tid']}','{$q2['uid']}','{$q2['username']}','1','0')");
					}
					$votes++;
				}
			}
			$rt['votes'] = $votes ? $votes : 0;
			$votearray[] = array($rt['polloption'],$rt['votes']);
		}
		$votearray	= addslashes(serialize($votearray));
		$timelimit	= $v['expiration'] ? (($v['expiration'] - $v['dateline']) / 86400) : 0;

		$ipoll = '';
		$ipoll = "(".$v['tid'].",'{$votearray}',1,".(1^$rt['visible']).",{$timelimit},{$v['multiple']},{$v['maxchoices']})";

		$DDB->update("REPLACE INTO {$pw_prefix}polls (tid,voteopts,modifiable,previewable,timelimit,multiple,mostvotes) VALUES ".$ipoll);
		$s_c++;
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
elseif ($step == '10')
{
    /*
	//活动
	if(!$start)
	{
		$DDB->update("TRUNCATE TABLE {$pw_prefix}activity");
	}

	$tid = $SDB->get_one("SELECT tid FROM {$source_prefix}activities LIMIT $start, 1");
	if (!$tid)
	{
		report_log();
		newURL($step);
	}

	$query = $SDB->query("SELECT a.*, t.subject FROM {$source_prefix}activities a LEFT JOIN {$source_prefix}threads t USING (tid) WHERE a.tid >= ".$tid['tid']." LIMIT $percount");
	while($act = $SDB->fetch_array($query))
	{
		$DDB->update("INSERT INTO {$pw_prefix}activity (tid,subject,admin,starttime,endtime,location,num,sexneed,costs,deadline) VALUES (".$act['tid'].",'".addslashes($act['subject'])."',".$act['uid'].",".$act['starttimefrom'].",".$act['starttimeto'].",'".addslashes($act['place'])."',".$act['number'].",".$act['gender'].",".$act['cost'].",".$act['expiration'].")");
		$s_c++;
	}
	refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
    */

	//活动
	if(!$start)
	{
		$DDB->update("TRUNCATE TABLE {$pw_prefix}pcvalue2");
	}

	$query = $SDB->query("SELECT a.*,t.fid FROM {$source_prefix}activities a LEFT JOIN {$source_prefix}threads t USING(tid) WHERE a.tid > $start LIMIT $percount");
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

        $pwSQL = array(
            'tid'		    => $act['tid'],                         'fid'		    => $fid,
            'pctype'		=> 1,                                   'begintime'		=> $act['starttimefrom'],
            'endtime'	    => $act['expiration'],                  'address '		=> addslashes($act['place']),
            'limitnum '		=> $act['number'],                      'gender'        => $act['gender'],
            'price '		=> $act['cost'],                        'price '		=> $act['cost'],
            'tel '		    => $act['contact'],
        );
        $tidarr[] = $act['tid'];
        $DDB->update("REPLACE INTO {$pw_prefix}pcvalue2 SET ".pwSqlSingle($pwSQL));
		$s_c++;
	}
    if($tidarr){
        $DDB->update("UPDATE {$pw_prefix}threads SET special=22 WHERE tid in (".implode(',',$tidarr).")");
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
		$DDB->update("TRUNCATE TABLE {$pw_prefix}pcmember");
	}
    $goon=0;
	$query = $SDB->query("SELECT * FROM {$source_prefix}activityapplies LIMIT $start,$percount");
	while($act = $SDB->fetch_array($query))
	{
        $goon++;
        $DDB->update("INSERT INTO {$pw_prefix}pcmember (pcmid,tid,uid,pcid,username,mobile,zip,nums,message,jointime) VALUES (".$act['applyid'].",".$act['tid'].",".$act['uid'].",2,'".addslashes($act['username'])."','".addslashes($act['contact'])."','".(2-$act['verified'])."',1,'".addslashes($act['message'])."',".$act['dateline'].")");

		$s_c++;
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

	$query = $SDB->query("SELECT * FROM {$source_prefix}posts where pid > $start order by pid LIMIT $percount");//用主键来搜索快
	while($p = $SDB->fetch_array($query))
	{
		$mission++;
        $lastid = $p['pid'];

		if (!$p['fid'] || !$p['tid'] || $p['first'] == 1)//first =1无须处理
		{
			if($p['first']!=1)
			{
			    $f_c++;
			    errors_log($p['pid']."\t".$p['fid']."\t".$p['tid']);
			}
			continue;
		}
		$ifmark='';
		$p['subject'] = addslashes($p['subject']);
		$p['message'] = addslashes(dz_ubb(str_replace($_dzface,$_pwface,$p['message'])));
		$ifconvert = (convert($p['message']) == $p['message'])? 1 : 2;

        if(!$speed)//一条一条插
        {
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
	$query = $SDB->query("SELECT a.*,p.fid,p.first FROM {$source_prefix}attachments a LEFT JOIN {$source_prefix}posts p USING(pid) WHERE a.aid >=$start ORDER BY a.aid LIMIT $percount");
	while($a = $SDB->fetch_array($query))
	{
        $lastid = $a['aid'];
		/*附件类型转换*/
		$fileinfo = getfileinfo($a['filename']);
		$a['filetype'] 	= $fileinfo['type'];
		$ifupload 		= $fileinfo['ifupload'];
		if (0 != $a['price'])
		{
			$needrvrc       = $a['price'];
			$special        = 2;
			$ctype          = 'money';
		}
		else
		{
			$needrvrc = 0;
			$special  = 0;
			$ctype    = '';
		}

		$attachesql = '';
		$attachesql = "(".$a['aid'].",'".$a['fid']."','".$a['uid']."','".$a['tid']."',".($a['first'] ? 0 : $a['pid']).",'".addslashes($a['filename'])."','".$a['filetype']."',".(round($a['filesize']/1024)).",'".addslashes($a['attachment'])."',".$a['downloads'].",'".$needrvrc."',".$special.",'".$ctype."',".$a['dateline'].",'".addslashes($a['description'])."')";
		if('' != $attachesql)
		{
			$DDB->update("REPLACE INTO {$pw_prefix}attachs (aid,fid,uid,tid,pid,name,type,size,attachurl,hits,needrvrc,special,ctype,uploadtime,descrip) VALUES $attachesql ");
		}

		$s_c++;
	}

    $maxid = $SDB->get_value("SELECT max(aid) FROM {$source_prefix}attachments");
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
    $b_i  = $DDB->get_value("SELECT max(aid) FROM {$pw_prefix}announce") + 1;
	$query = $SDB->query("SELECT cff.fid,cff.rules,cf.name FROM {$source_prefix}forumfields cff LEFT JOIN {$source_prefix}forums cf USING(fid) WHERE cff.rules!=''");
	while($b = $SDB->fetch_array($query))
	{
		$DDB->update("REPLACE INTO {$pw_prefix}announce (aid,fid,ifopen,vieworder,author,startdate,url,enddate,subject,content,ifconvert) VALUES (".$b_i.",".$b['fid'].",1,1,'admin',".$timestamp.",'','','".addslashes($b['name'])."','".addslashes($b['rules'])."',1)");
        $b_i++;
		$s_c++;
	}

	report_log();
	newURL($step);
}
elseif ($step == '15')
{
	require_once S_P.'tmp_uc.php';
	$charset_change = 1;
	$UCDB = new mysql($uc_db_host, $uc_db_user, $uc_db_password, $uc_db_name);

    $message_sql = $relations_sql = $reply_sql = array();
	//短信
	if(!$start)
	{
        $DDB->update("TRUNCATE TABLE {$pw_prefix}ms_messages");
        $DDB->update("TRUNCATE TABLE {$pw_prefix}ms_relations");
        $DDB->update("TRUNCATE TABLE {$pw_prefix}ms_replies");
	}

	$query = $UCDB->query("SELECT * FROM {$uc_db_prefix}pms WHERE pmid >= $start LIMIT $percount");
    while($m = $UCDB->fetch_array($query))
	{
        $lastid = $m['pmid'];
        ADD_S($m);
		switch ($m['folder'])
		{
			case 'inbox':
				$type = 'rebox';
				$m_tmp = $DDB->get_one("SELECT username FROM {$pw_prefix}members WHERE uid = ".$m['msgtoid']);
				$m['msgto'] = $m_tmp['username'];
				break;
			case 'outbox':
				$type = 'sebox';
				$m_tmp = $DDB->get_one("SELECT username FROM {$pw_prefix}members WHERE uid = ".$m['msgtoid']);
				$m['msgfrom'] = $m_tmp['username'];
				break;
		}
        if($m['related']==0){
            continue;
        }

		$msgsql = '';
		$msgcsql = '';
		$msglogsql = array();

		if ($m['delstatus'] != 2)
		{
			//$msgsql = "(".$m['pmid'].",".$m['msgtoid'].",".$m['msgfromid'].",'".($m['msgfrom'])."','".$type."',".$m['new'].",'".$m['dateline']."')";

			//$msgcsql = "(".$m['pmid'].",'".($m['subject'])."','".($m['message'])."')";

			if (($m['msgtoid'] != $m['msgfromid']) && ($type == 'rebox'))
			{
				$msglogsql[]="(".$m['pmid'].",".$m['msgfromid'].",".$m['msgtoid'].",'".$m['dateline']."','send')";
				$msglogsql[]="(".$m['pmid'].",".$m['msgtoid'].",".$m['msgfromid'].",'".$m['dateline']."','receive')";
			}

	        $message_sql[] = "('".$m['pmid']."',".$m['msgfromid'].",'".$m['msgfrom']."','".$m['subject']."','".$m['message']."','".serialize(array('categoryid'=>1,'typeid'=>100))."',".$m['dateline'].",".$m['dateline'].",'".serialize(array($m['msgto']))."')";
	        $relations_sql[] = "(".$m['msgfromid'].",'".$m['pmid']."','1','100','0','1',".$m['dateline'].",".$m['dateline'].")";
            $reply_sql[] = "('".$m['pmid']."',".$m['msgfromid'].",'".$m['msgfrom']."','".$m['subject']."','".$m['message']."',0,".$m['dateline'].",".$m['dateline'].")";

		}

		if($message_sql)
		{
			$DDB->update("REPLACE INTO {$pw_prefix}ms_messages (mid,create_uid,create_username,title,content,expand,created_time,modified_time,extra) VALUES ".implode(",",$message_sql));
		}
		if($relations_sql)
		{
			$DDB->update("INSERT INTO {$pw_prefix}ms_relations (uid,mid,categoryid,typeid,status,isown,created_time,modified_time) VALUES ".implode(",",$relations_sql));
		}
		if($reply_sql)
		{
			$DDB->update("INSERT INTO {$pw_prefix}ms_replies (parentid,create_uid,create_username,title,content,status,created_time,modified_time) VALUES ".implode(",",$reply_sql));
		}

		$s_c++;
	}

    $maxid = $UCDB->get_value("SELECT max(pmid) FROM {$uc_db_prefix}pms");
    if($maxid > $lastid)
    {
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
        echo 12121;exit;
		report_log();
		newURL($step);
	}
}
elseif ($step == '16')
{
	//好友
	require_once S_P.'tmp_uc.php';
	$charset_change = 1;
	$UCDB = new mysql($uc_db_host, $uc_db_user, $uc_db_password, $uc_db_name);
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}friends");
	}

	$goon = 0;
	$query = $UCDB->query("SELECT uid,friendid,comment FROM {$uc_db_prefix}friends LIMIT $start, $percount");

	//好友好像没有添加时间,也没有验证状态
	while($f = $UCDB->fetch_array($query))
	{
		$DDB->update("REPLACE INTO {$pw_prefix}friends (uid,friendid,descrip) VALUES (".$f['uid'].",".$f['friendid'].",'".addslashes($f['comment'])."')");
		$goon ++;
		$s_c ++;
	}
	if ($goon == $percount)
	{
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
		$maxid = $UCDB->get_value("SELECT max(uid) FROM {$uc_db_prefix}friends");
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
		$maxid = $SDB->get_value("SELECT max(uid) FROM {$source_prefix}favorites");
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
		if ($file != '.' && $file != '..' && preg_match('/^[a-z0-9\:\/\._]*?\/avatar\/(\d{3})\/(\d{2})\/(\d{2})\/(\d{2})\_avatar_middle\.jpg$/i', $_avatar[$start].'/'.$file, $match))
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
	//辩论
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}debates");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}debatedata");
	}
	//数据debateposts
	$query = $SDB->query("SELECT * FROM {$source_prefix}debates LIMIT $start, $percount");
    $goon=0;
	while($r = $SDB->fetch_array($query))
	{
		//$r=Add_S($r);
		//这里的正反方人数是主题表表加观点表
		$affirmvoterids=$r['affirmvoterids'];//正方人数
		$negavoterids=$r['negavoterids'];//反方人数
		$obvote=0;
		$revote=0;//反方得票数
		$obposts=0;
		$reposts=0;//反方辩手个数

		if(!empty($affirmvoterids))
		{
			$affarray=explode("\t",$affirmvoterids);
			if(is_array($affarray))//正方
			{
			   foreach($affarray as $va)
			   {
				   if(empty($va))continue;
				   $debatedatasql[]="(0,'".$r['tid']."','".$va."',1,'','','')";
				   $obvote++;
				   $s_c ++;
			   }
			}
		}

		if(!empty($negavoterids))
		{
			$negarray=explode("\t",$negavoterids);
			if(is_array($negarray))//正方
			{
			   foreach($negarray as $va)
			   {
				   if(empty($va))continue;
				   $debatedatasql[]="(0,'".$r['tid']."','".$va."',2,'','','')";
				   $revote++;
				   $s_c ++;
			   }
			}
		}

		$data = $SDB->query("SELECT * FROM {$source_prefix}debateposts where tid=".$r['tid']);
		while($d = $SDB->fetch_array($data)){
			//$d=Add_S($d);
			if($d['stand']==0)continue;
			$debatedatasql[]="('".$d['pid']."','".$r['tid']."','".$d['uid']."','".$d['stand']."','".$d['dateline']."','','')";
			if($d['stand']==1)
			{
				$obvote++;
				$obposts++;
			}
			else
			{
				$revote++;
				$reposts++;
			}
			$s_c ++;
		}

		$sql="REPLACE into {$pw_prefix}debates (tid,authorid,postdate,obtitle,retitle,endtime,obvote,revote,obposts,reposts,umpire,umpirepoint,debater,judge)";
		$sql.="values ('{$r[tid]}','{$r[uid]}','{$r[starttime]}','{$r[affirmpoint]}','{$r[negapoint]}','{$r[endtime]}','{$obvote}','{$revote}','{$obposts}','{$reposts}','{$r[umpire]}','{$r[umpirepoint]}','','{$r[winner]}')";
		$DDB->update($sql);
		$s_c ++;
		$goon++;

		if ($debatedatasql)
		{
		  $sqlstr = implode(",",$debatedatasql);
	   	  $DDB->update("REPLACE INTO {$pw_prefix}debatedata (pid,tid,authorid,standpoint,postdate,vote,voteids) VALUES $sqlstr");

		}
	}

	if ($goon == $percount)
	{
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
		$maxid = $SDB->get_value("SELECT max(tid) FROM {$source_prefix}debates");
		report_log();
		newURL($step);
	}
}

elseif ($step == '22')
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
	    $UCHDB = new mysql($uch_db_host, $uch_db_user, $uch_db_password, $uch_db_name);
	}

	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}cnclass");
        $DDB->update("INSERT INTO {$pw_prefix}forums (name,fup) VALUE ('群组版块',1)");
        $lastfid = $DDB->insert_id();
        $DDB->update("INSERT INTO {$pw_prefix}forumdata (fid) VALUE ($lastfid)");
	}

	$query = $UCHDB->query("SELECT fieldid,title FROM {$uch_db_prefix}profield");
	while ($rt = $UCHDB->fetch_array($query))
	{
		//$cid	=	$rt['fieldid'];
		$cname	=	$rt['title'];
		$cnclassdb[] = array($lastfid,1,1);//待更新群组的时候统计更新？？？？？？？？？？？
		$s_c ++;
	}
	$DDB->update("REPLACE INTO {$pw_prefix}cnclass (fid,cname, cnsum) VALUES ".pwSqlMulti($cnclassdb));

	$maxid = $UCHDB->get_value("SELECT max(fieldid) FROM {$uch_db_prefix}profield");
	report_log();
	newURL($step);
}
elseif ($step == '23')
{
	//圈子群组
	if(!file_exists(S_P."tmp_uch.php")){
		newURL($step);
	}
	else{
		require(S_P."tmp_uch.php");
	    $charset_change = 1;
	    $UCHDB = new mysql($uch_db_host, $uch_db_user, $uch_db_password, $uch_db_name);
	}
	if(!$start){
		$DDB->query("TRUNCATE TABLE {$pw_prefix}colonys");
	}

    $lastfid = $DDB->get_value("SELECT max(fid) FROM {$pw_prefix}forums");
	$query = $UCHDB->query("SELECT t.username,m.tagid,m.tagname,m.fieldid,m.membernum,m.joinperm,m.viewperm,m.pic,m.announcement FROM {$uch_db_prefix}tagspace t LEFT JOIN {$uch_db_prefix}mtag m ON t.tagid=m.tagid WHERE t.grade='9' GROUP BY tagid");
	while ($rt = $UCHDB->fetch_array($query)) {
		$id			=	$rt['tagid'];
		$classid	=	$rt['fieldid'];
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
		$colonysdb[] = array($id,$lastfid,$cname,$admin,$members,$ifcheck,$ifopen,$albumopen,$cnimg,$createtime,$annouce,$albumnum,$annoucesee,$descrip);
		$s_c ++;
	}
	$colonysdb && $DDB->update("REPLACE INTO {$pw_prefix}colonys (id,classid,cname,admin,members,ifcheck,ifopen,albumopen,cnimg,createtime,annouce,albumnum,annoucesee,descrip) VALUES ".pwSqlMulti($colonysdb));

	report_log();
	newURL($step);
}
elseif ($step == '24')
{
	//圈子群组成员
	if(!file_exists(S_P."tmp_uch.php")){
		newURL($step);
	}
	else{
		require(S_P."tmp_uch.php");
	    $charset_change = 1;
	    $UCHDB = new mysql($uch_db_host, $uch_db_user, $uch_db_password, $uch_db_name);
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
		$maxid = $UCHDB->get_value("SELECT max(uid) FROM {$uch_db_prefix}tagspace");
		report_log();
		newURL($step);
	}
}
elseif ($step == '25')
{
	//圈子群组讨论区
	if(!file_exists(S_P."tmp_uch.php")){
		newURL($step);
	}
	else{
		require(S_P."tmp_uch.php");
	    $charset_change = 1;
	    $UCHDB = new mysql($uch_db_host, $uch_db_user, $uch_db_password, $uch_db_name);
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
		$maxid=$tid	= $rt['pid'];
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
		$maxid = $UCHDB->get_value("SELECT max(tid) FROM {$uch_db_prefix}thread");
		report_log();
		newURL($step);
	}
}
elseif ($step == '26')
{
	//圈子记录
	if(!file_exists(S_P."tmp_uch.php")){
		newURL($step);
	}
	else{
		require(S_P."tmp_uch.php");
	    $charset_change = 1;
	    $UCHDB = new mysql($uch_db_host, $uch_db_user, $uch_db_password, $uch_db_name);
	}
	if(!$start){
		$DDB->query("TRUNCATE TABLE {$pw_prefix}owritedata");
	}
	$query = $UCHDB->query("SELECT * FROM {$uch_db_prefix}doing LIMIT $start, $percount");
	$goon = 0;
	while ($rt = $UCHDB->fetch_array($query)) {
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
		$owritedatadb[] = array($id,$uid,$touid,$postdate,$isshare,$source,$content,$c_num);
	}
	$owritedatadb && $DDB->update("REPLACE INTO {$pw_prefix}owritedata (id,uid,touid,postdate,isshare,source,content,c_num) VALUES ".pwSqlMulti($owritedatadb));
	if ($goon == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else{
		$maxid = $UCHDB->get_value("SELECT max(doid) FROM {$uch_db_prefix}doing");

		report_log();
		newURL($step);
	}
}
elseif ($step == '27')
{
	//圈子记录回复
	if(!file_exists(S_P."tmp_uch.php")){
		newURL($step);
	}
	else{
		require(S_P."tmp_uch.php");
	    $charset_change = 1;
	    $UCHDB = new mysql($uch_db_host, $uch_db_user, $uch_db_password, $uch_db_name);
	}
	if(!$start){
		$DDB->query("TRUNCATE TABLE {$pw_prefix}comment");
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
		$replydb[] = array($id,$uid,$username,$title,$type,$typeid,$upid,$postdate);
	}
	$replydb && $DDB->update("REPLACE INTO {$pw_prefix}comment (id,uid,username,title,type,typeid,upid,postdate) VALUES ".pwSqlMulti($replydb));
	if ($goon == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else{
		$maxid = $UCHDB->get_value("SELECT max(id) FROM {$uch_db_prefix}docomment");
		report_log();
		newURL($step);
	}
}
elseif ($step == '28')
{
	//圈子相册(将home/attachment目录下的图片移至到phpwind论坛的attachment/photo下)
	if(!file_exists(S_P."tmp_uch.php")){
		newURL($step);
	}
	else{
		require(S_P."tmp_uch.php");
	    $charset_change = 1;
	    $UCHDB = new mysql($uch_db_host, $uch_db_user, $uch_db_password, $uch_db_name);
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
		$maxid = $UCHDB->get_value("SELECT max(albumid) FROM {$uch_db_prefix}album");

		report_log();
		newURL($step);
	}
}
elseif ($step == '29')
{
	//圈子相册照片
	if(!file_exists(S_P."tmp_uch.php")){
		newURL($step);
	}
	else{
		require(S_P."tmp_uch.php");
	    $charset_change = 1;
	    $UCHDB = new mysql($uch_db_host, $uch_db_user, $uch_db_password, $uch_db_name);
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
		$maxid = $UCHDB->get_value("SELECT max(picid) FROM {$uch_db_prefix}pic");
		report_log();
		newURL($step);
	}
}
elseif ($step == '30')
{
	//圈子分享
	if(!file_exists(S_P."tmp_uch.php")){
		newURL($step);
	}
	else{
		require(S_P."tmp_uch.php");
	    $charset_change = 1;
	    $UCHDB = new mysql($uch_db_host, $uch_db_user, $uch_db_password, $uch_db_name);
	}
	if(!$start){
		$DDB->query("TRUNCATE TABLE {$pw_prefix}share");
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
	$sharedb && $DDB->update("REPLACE INTO {$pw_prefix}share (id,type,uid,username,postdate,content,ifhidden) VALUES ".pwSqlMulti($sharedb));
	if ($goon == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else{
		$maxid = $UCHDB->get_value("SELECT max(sid) FROM {$uch_db_prefix}share");
		report_log();
		newURL($step);
	}
}
elseif ($step == '31')
{
	//圈子日志分类
	if(!file_exists(S_P."tmp_uch.php")){
		newURL($step);
	}
	else{
		require(S_P."tmp_uch.php");
	    $charset_change = 1;
	    $UCHDB = new mysql($uch_db_host, $uch_db_user, $uch_db_password, $uch_db_name);
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
		$maxid = $UCHDB->get_value("SELECT max(classid) FROM {$uch_db_prefix}class");
		report_log();
		newURL($step);
	}
}
elseif ($step == '32')
{
	//圈子日志
	if(!file_exists(S_P."tmp_uch.php")){
		newURL($step);
	}
	else{
		require(S_P."tmp_uch.php");
	    $charset_change = 1;
	    $UCHDB = new mysql($uch_db_host, $uch_db_user, $uch_db_password, $uch_db_name);
	}
	if(!$start){
		$DDB->query("TRUNCATE TABLE {$pw_prefix}diary");
	}
	$query = $UCHDB->query("SELECT b.blogid,b.uid,b.classid,b.username,b.friend,b.subject,bf.message,b.viewnum,b.replynum,b.dateline FROM {$uch_db_prefix}blog b LEFT JOIN {$uch_db_prefix}blogfield bf ON b.blogid=bf.blogid  LIMIT $start, $percount");
	$goon = 0;
	while ($rt = $UCHDB->fetch_array($query)) {
		$goon++;
		$s_c ++;
		$maxid=$did = $rt['blogid'];
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
		$maxid = $UCHDB->get_value("SELECT max(b.blogid) FROM {$uch_db_prefix}blog b LEFT JOIN {$uch_db_prefix}blogfield bf ON b.blogid=bf.blogid");
		report_log();
		newURL($step);
	}
}
elseif ($step == '33')
{
	//圈子日志
	if(!file_exists(S_P."tmp_uch.php")){
		newURL($step);
	}
	else{
		require(S_P."tmp_uch.php");
	    $charset_change = 1;
	    $UCHDB = new mysql($uch_db_host, $uch_db_user, $uch_db_password, $uch_db_name);
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
		$maxid = $UCHDB->get_value("SELECT max(cid) FROM {$uch_db_prefix}comment");
		report_log();
		newURL($step);
	}
}
elseif ($step == '34')
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
elseif ($step == '35')
{
    //广告
	if(!$start)
	{
      $DDB->UPDATE("DROP TABLE {$pw_prefix}advert;");
      $sql = "CREATE TABLE IF NOT EXISTS {$pw_prefix}advert (`id` int(10) unsigned NOT NULL auto_increment,`type` tinyint(1) NOT NULL default '0',`uid` int(10) unsigned NOT NULL default '0',`ckey` varchar(32) NOT NULL,`stime` int(10) unsigned NOT NULL default '0',`etime` int(10) unsigned NOT NULL default '0',`ifshow` tinyint(1) NOT NULL default '0',`orderby` tinyint(1) NOT NULL default '0',`descrip` varchar(255) NOT NULL,`config` text NOT NULL,PRIMARY KEY  (`id`)) ";

	    if ($DDB->server_info() > '4.1') {
		    $sql .= "ENGINE=MyISAM".($dest_charset ? " DEFAULT CHARSET=".$dest_charset : '');
	    } else {
		    $sql .= "TYPE=MyISAM";
	    }
	    $sql .= "  AUTO_INCREMENT=100";
	    $DDB->query($sql);

	$DDB->update("REPLACE INTO `{$pw_prefix}advert` (`id`, `type`, `uid`, `ckey`, `stime`, `etime`, `ifshow`, `orderby`, `descrip`, `config`) VALUES
(1, 0, 0, 'Site.Header', 0, 0, 1, 0, '头部横幅~	~显示在页面的头部，一般以图片或flash方式显示，多条广告时系统将随机选取一条显示', 'a:1:{s:7:\"display\";s:4:\"rand\";}'),
(2, 0, 0, 'Site.Footer', 0, 0, 1, 0, '底部横幅~	~显示在页面的底部，一般以图片或flash方式显示，多条广告时系统将随机选取一条显示', 'a:1:{s:7:\"display\";s:4:\"rand\";}'),
(3, 0, 0, 'Site.NavBanner1', 0, 0, 1, 0, '导航通栏[1]~	~显示在主导航的下面，一般以图片或flash方式显示', 'a:1:{s:7:\"display\";s:4:\"rand\";}'),
(4, 0, 0, 'Site.NavBanner2', 0, 0, 1, 0, '导航通栏[2]~	~显示在头部通栏广告[1]位置的下面,与通栏广告[1]可一起显示,一般为图片广告', 'a:1:{s:7:\"display\";s:4:\"rand\";}'),
(5, 0, 0, 'Site.PopupNotice', 0, 0, 1, 0, '弹窗广告[右下]~	~在页面右下角以浮动的层弹出显示，此广告内容需要单独设置相关窗口参数', 'a:1:{s:7:\"display\";s:4:\"rand\";}'),
(6, 0, 0, 'Site.FloatRand', 0, 0, 1, 0, '漂浮广告[随机]~	~以各种形式在页面内随机漂浮的广告', 'a:1:{s:7:\"display\";s:4:\"rand\";}'),
(7, 0, 0, 'Site.FloatLeft', 0, 0, 1, 0, '漂浮广告[左]~	~以各种形式在页面左边漂浮的广告，俗称对联广告[左]', 'a:1:{s:7:\"display\";s:4:\"rand\";}'),
(8, 0, 0, 'Site.FloatRight', 0, 0, 1, 0, '漂浮广告[右]~	~以各种形式在页面右边漂浮的广告，俗称对联广告[右]', 'a:1:{s:7:\"display\";s:4:\"rand\";}'),
(9, 0, 0, 'Mode.TextIndex', 0, 0, 1, 0, '文字广告[论坛首页]~	~显示在页面的导航下面，一般以文字方式显示，每行四条广告，超过四条将换行显示', 'a:1:{s:7:\"display\";s:3:\"all\";}'),
(10, 0, 0, 'Mode.Forum.TextRead', 0, 0, 1, 0, '文字广告[帖子页]~	~显示在页面的导航下面，一般以文字方式显示，每行四条广告，超过四条将换行显示', 'a:1:{s:7:\"display\";s:3:\"all\";}'),
(11, 0, 0, 'Mode.Forum.TextThread', 0, 0, 1, 0, '文字广告[主题页]~	~显示在页面的导航下面，一般以文字方式显示，每行四条广告，超过四条将换行显示', 'a:1:{s:7:\"display\";s:3:\"all\";}'),
(12, 0, 0, 'Mode.Forum.Layer.TidRight', 0, 0, 1, 0, '楼层广告[帖子右侧]~	~出现在帖子右侧，一般以图片或文字显示，多条帖间广告时系统将随机选取一条显示', 'a:1:{s:7:\"display\";s:4:\"rand\";}'),
(13, 0, 0, 'Mode.Forum.Layer.TidDown', 0, 0, 1, 0, '楼层广告[帖子下方]~	~出现在帖子下方，一般以图片或文字显示，多条帖间广告时系统将随机选取一条显示', 'a:1:{s:7:\"display\";s:4:\"rand\";}'),
(14, 0, 0, 'Mode.Forum.Layer.TidUp', 0, 0, 1, 0, '楼层广告[帖子上方]~	~出现在帖子上方，一般以图片或文字显示，多条帖间广告时系统将随机选取一条显示', 'a:1:{s:7:\"display\";s:4:\"rand\";}'),
(15, 0, 0, 'Mode.Forum.Layer.TidAmong', 0, 0, 1, 0, '楼层广告[楼层中间]~	~出现在帖子楼层之间，一般以图片或文字显示，多条帖间广告时系统将随机选取一条显示', 'a:1:{s:7:\"display\";s:4:\"rand\";}'),
(16, 0, 0, 'Mode.Layer.Index', 0, 0, 1, 0, '论坛首页分类间~	~出现在首页分类层之间，一般以图片或文字显示，多条帖间广告时系统将随机选取一条显示', 'a:1:{s:7:\"display\";s:4:\"rand\";}'),
(17, 0, 0, 'Mode.area.IndexMain', 0, 0, 1, 0, '门户首页中间~	~门户首页循环广告下面的中间主要广告位,一般为图片广告', 'a:1:{s:7:\"display\";s:4:\"rand\";}'),
(18, 0, 0, 'Mode.Layer.area.IndexLoop', 0, 0, 1, 0, '门户首页循环~	~门户首页中间循环模块之间的广告投放，一般为图片广告', 'a:1:{s:7:\"display\";s:4:\"rand\";}'),
(19, 0, 0, 'Mode.Layer.area.IndexSide', 0, 0, 1, 0, '门户首页侧边~	~门户首页侧边每隔一个模块都有一个广告位显示,位置顺序对应选择的楼层数.一般为小图片广告', 'a:1:{s:7:\"display\";s:4:\"rand\";}'),
(20, 0, 0, 'Mode.Forum.area.CateMain', 0, 0, 1, 0, '门户频道中间~	~门户频道焦点下面的中间主要广告位,一般为图片广告', 'a:1:{s:7:\"display\";s:4:\"rand\";}'),
(21, 0, 0, 'Mode.Forum.Layer.area.CateLoop', 0, 0, 1, 0, '门户频道循环~	~门户频道中间循环模块之间的广告投放，一般为图片广告', 'a:1:{s:7:\"display\";s:4:\"rand\";}'),
(22, 0, 0, 'Mode.Forum.Layer.area.CateSide', 0, 0, 1, 0, '门户频道侧边~	~门户频道侧边每隔一个模块都有一个广告位显示,位置顺序对应选择的楼层数.一般为小图片广告', 'a:1:{s:7:\"display\";s:4:\"rand\";}'),
(23, 0, 0, 'Mode.Forum.Layer.area.ThreadTop', 0, 0, 1, 0, '门户帖子列表页右上~	~帖子列表页门户模式浏览时，右上方的广告位', 'a:1:{s:7:\"display\";s:4:\"rand\";}'),
(24, 0, 0, 'Mode.Forum.Layer.area.ThreadBtm', 0, 0, 1, 0, '门户帖子列表页右下~	~帖子列表页门户模式浏览时，右下方的广告位', 'a:1:{s:7:\"display\";s:4:\"rand\";}'),
(25, 0, 0, 'Mode.Forum.Layer.area.ReadTop', 0, 0, 1, 0, '门户帖子内容页右上~	~帖子内容页门户模式浏览时，右上方的广告位', 'a:1:{s:7:\"display\";s:4:\"rand\";}'),
(26, 0, 0, 'Mode.Forum.Layer.area.ReadBtm', 0, 0, 1, 0, '门户帖子内容页右下~	~帖子内容页门户模式浏览时，右下方的广告位', 'a:1:{s:7:\"display\";s:4:\"rand\";}')");
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
	$maxid = $SDB->get_value("SELECT max(advid) FROM {$source_prefix}advertisements");
	if ($start < $maxid)
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
elseif ($step == '36')
{
	//会员
	$insertadmin = '';
	$_specialgroup = array();
	require_once (S_P.'tmp_credit.php');
	require_once (S_P.'tmp_uc.php');    //uc表

    //增加uc
	$charset_change = 1;
	$UCDB = new mysql($uc_db_host, $uc_db_user, $uc_db_password, $uc_db_name);

	require_once (S_P.'ubb.php');
	require_once (S_P.'tmp_group.php');

	$query = $UCDB->query("SELECT * FROM {$uc_db_prefix}members m LEFT JOIN {$uc_db_prefix}memberfields mf USING (uid) WHERE m.uid >= $start AND m.uid < $end ORDER BY m.uid");
	while ($m = $SDB->fetch_array($query))
	{
		Add_S($m);

		if (!$m['uid'])
		{
			$f_c++;
			errors_log($m['uid']."\t".$m['username']);
			continue;
		}
		$rt = $SDB->get_one("SELECT uid FROM {$source_prefix}members WHERE uid=".$m['uid']);
		if($rt){
			continue;
		}

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

	$maxid = $SDB->get_value("SELECT max(uid) FROM {$source_prefix}members");

	if ($maxid > $start)
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
elseif ($step == '37')
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

		$maxid = $SDB->get_value("SELECT max(pid) FROM {$source_prefix}ratelog");
		report_log();
		newURL($step);
	}
}
elseif ($step == '38')
{
    //根据评分信息来更新主题的ifmark字段
    $ifmark='';

    $query = $DDB->query("SELECT t.fid,t.tid FROM {$pw_prefix}threads t WHERE t.tid>$start ORDER BY tid LIMIT $percount");
    while ($t = $DDB->fetch_array($query))
    {
		$lastid = $t['tid'];
		update_markinfo($t['fid'],$t['tid'],0);
		$s_c++;
    }

    $tmsgs_maxtid = $DDB->get_value("SELECT max(tid) FROM {$pw_prefix}tmsgs");
    if($tmsgs_maxtid > $lastid)
    {
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else{
		report_log();
		newURL($step);
	}
}
elseif ($step == '39')
{
    //根据评分信息来更新回复的ifmark字段
    $ifmark='';

    $query = $DDB->query("SELECT fid,tid,pid FROM {$pw_prefix}posts WHERE pid>$start ORDER BY pid LIMIT $percount");
    while ($p = $DDB->fetch_array($query))
    {
		$lastid = $p['pid'];
		update_markinfo($p['fid'],$p['tid'],$p['pid']);
		$s_c++;
    }

    $maxid = $DDB->get_value("SELECT max(pid) FROM {$pw_prefix}posts");
    if($maxid > $lastid)
    {
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else{
		report_log();
		newURL($step);
	}
}
elseif ($step == '40')
{
    //分类信息框架
    require_once(S_P."lang_topicmodel.php");
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
elseif ($step == '41')
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
else
{
	copy(S_P."tmp_report.php",S_P."report.php");//复制一份文件
	if(!file_exists(S_P."tmp_uch.php")){P_unlink(S_P."tmp_uch.php");}
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

	$DDB->update($lang['group']);//创建系统默认组
	$grelation = array(1=>3, 2=>4, 3=>5, 4=>6, 5=>6, 6=>6, 7=>2, 8=>7);//系统组GID

	$query = $SDB->query("SELECT * FROM {$source_prefix}usergroups WHERE type = 'member' OR type = 'special'");
	$specialdata = array();
	while ($g = $SDB->fetch_array($query))
	{
		$gid 			= $g['groupid'];
		$gptype 		= $g['type'];
		$grouptitle 	= addslashes($g['grouptitle']);
		$groupimg 		= 8;
		$grouppost 		= (int)$g['creditshigher'];
		$maxmsg 		= (int)$g['maxpmnum'];
		$allowhide 		= $g['allowinvisible'];
		$allowread 		= $g['readaccess'] ? 1 : 0;
		$allowportait 	= $g['allowavatar'] ? 1 : 0;
		$upload 		= $g['allowavatar'] == 3 ? 1 : 0;
		$allowrp 		= $g['allowreply'];
		$allowhonor 	= $g['allownickname'];//个性签名-昵称
		$allowdelatc 	= 1;
		$allowpost 		= $g['allowpost'];
		$allownewvote 	= $g['allowpostpoll'];
		$allowvote 		= $g['allowvote'];
		$allowactive 	= $g['allowpostactivity'];
		$htmlcode 		= $g['allowhtml'];
		$wysiwyg 		= 0;
		$allowhidden 	= $g['allowhidecode'];
		$allowencode 	= $g['allowsetreadperm'];
		$allowsell 		= $g['maxprice'] ? 1 : 0;
		$allowsearch 	= $g['allowsearch'];
		$allowmember 	= 1;
		$allowprofile 	= $g['allowviewpro'];
		$allowreport 	= 1;
		$allowmessege 	= $g['maxpmnum'] ? 1 : 0;
		$allowsort 		= $g['allowviewstats'];
		$alloworder 	= 1;
		$allowupload 	= $g['allowpostattach'] ? 2 : 0;
		$allowdownload 	= $g['allowgetattach'] ? 2 : 0;
		$allowloadrvrc 	= 1;
		$allownum 		= 50;
		$edittime 		= 0;
		$postpertime 	= 0;
		$searchtime 	= 0;
		$signnum 		= $g['maxsigsize'];
		$uploadtype 	= $mright = array();

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

		pwGroupref(array('gid'=>$gid,'gptype'=>$gptype,'grouptitle'=>$grouptitle,'groupimg'=>$groupimg,
		'grouppost'=>$grouppost,'maxmsg'=>$maxmsg,'allowhide'=>$allowhide,'allowread'=>$allowread,
		'allowportait'=>$allowportait,'upload'=>$upload,'allowrp'=>$allowrp,'allowhonor'=>$allowhonor,
		'allowdelatc'=>$allowdelatc,'allowpost'=>$allowpost,'allownewvote'=>$allownewvote,
		'allowvote'=>$allowvote,'allowactive'=>$allowactive,'htmlcode'=>$htmlcode,'wysiwyg'=>$wysiwyg,
		'allowhidden'=>$allowhidden,'allowencode'=>$allowencode,'allowsell'=>$allowsell,
		'allowsearch'=>$allowsearch,'allowmember'=>$allowmember,'allowprofile'=>$allowprofile,
		'allowreport'=>$allowreport,'allowmessage'=>$allowmessege,'allowsort'=>$allowsort,
		'alloworder'=>$alloworder,'allowupload'=>$allowupload,'allowdownload'=>$allowdownload,
		'allowloadrvrc'=>$allowloadrvrc,'allownum'=>$allownum,'edittime'=>$edittime,
		'postpertime'=>$postpertime,'searchtime'=>$searchtime,'signnum'=>$signnum,'mright'=>$mright,
		'ifdefault'=>$ifdefault,'allowadmincp'=>$allowadmincp,'visithide'=>$visithide,'delatc'=>$delatc,
		'moveatc'=>$moveatc,'copyatc'=>$copyatc,'typeadmin'=>$typeadmin,'viewcheck'=>$viewcheck,
		'viewclose'=>$viewclose,'attachper'=>$attachper,'delattach'=>$delattach,'viewip'=>$viewip,
		'markable'=>$markable,'maxcredit'=>$maxcredit,'credittype'=>$credittype,'creditlimit'=>$creditlimit,
		'banuser'=>$banuser,'bantype'=>$bantype,'banmax'=>$banmax,'viewhide'=>$viewhide,'postpers'=>$postpers,
		'atccheck'=>$atccheck,'replylock'=>$replylock,'modown'=>$modown,'modother'=>$modother,
		'deltpcs'=>$deltpcs,'sright'=>$sright));

		$grouptitle=getGrouptitle($gid,$grouptitle,false);
		$DDB->update("INSERT INTO {$pw_prefix}usergroups (gid,gptype,grouptitle,groupimg,grouppost) VALUES ('$gid','$gptype','$grouptitle','$groupimg','$grouppost')");

		if ($g['type'] == 'special')
		{
			$specialdata[$g['groupid']] = '1';
		}

		$grelation[$g['groupid']] = $g['groupid'];//所有的用户组存到临时文件
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
	$content = preg_replace(array('~\[code\](.+?)\[\/code\]~ies','~\[media=mp3,\d+?,\d+?,(?:1|0)\](.+?)\[\/media\]~i','~\[media=(?:wmv|mov|wma),(\d+?),(\d+?),(1|0)\](.+?)\[\/media\]~i','~\[media=(rm|ra),(\d+?),(\d+?),(1|0)\](.+?)\[\/media\]~i','~\[media=swf,(\d+?),(\d+?)\](.+?)\[\/media\]~i','~\[hide=(\d+?)\](.+?)\[\/hide\]~is','~\[hide\](.+?)\[\/hide\]~is','~\[localimg=[0-9]+,[0-9]+\]([0-9]+)\[\/localimg\]~is','~\[local\]([0-9]+)\[\/local\]~is','~\[attach\]([0-9]+)\[\/attach\]~is','/\[img=[0-9]+,[0-9]+\]/i','/\[size=(\d+(\.\d+)?(px|pt|in|cm|mm|pc|em|ex|%)+?)\]/i'),array("ccode('\\1')",'[wmv=0]\\1[/wmv]','[wmv=\\1,\\2,\\3]\\4[/wmv]','[rm=\\2,\\3,\\4]\\5[/rm]','[flash=\\1,\\2,1]\\3[/flash]','[sell=\\1]\\2[/sell]','[post]\\1[/post]','[attachment=\\1]','[attachment=\\1]','[attachment=\\1]','[img]',''),$content);
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

function getUsernameByUid($uid)
{
	global $DDB,$pw_prefix;
	$username = $DDB->get_value("SELECT username FROM {$pw_prefix}members WHERE uid='$uid'");
	return $username;
}

function getPicCommentNum($pid)
{
	global $UCHDB,$uch_db_prefix;
	$num = $UCHDB->get_value("SELECT COUNT(*) AS num FROM {$uch_db_prefix}comment WHERE idtype='picid' AND id=".pwEscape($pid));
	return $num;
}

//取得评论的类型
function getCommentType($typeid) {
	switch ($typeid) {
		case 'blogid' :
			$type = 'diary';break;
		case 'picid' :
			$type = 'photo';break;
		case 'sid' :
			$type = 'share';break;
	}
	return $type;
}

function getThreadInfo($tid,$type) //TODO
{
	global $UCHDB,$uch_db_prefix;

	$thread_info = $UCHDB->get_one("SELECT lastpost,subject FROM {$uch_db_prefix}thread WHERE tid=".pwEscape($tid));
	switch ($type)
	{
		case 'lastpost':
			$return = $thread_info['lastpost'];
			break;
		case 'subject':
			$return = addslashes($thread_info['subject']);
			echo $return;exit();
		default:
			$return = '';
			break;
	}
	return $return;
}
function getShareType($type) {
	switch ($type) {
		case 'link':
			$type = 'web';break;
		case 'pic':
			$type = 'photo';break;
		case 'mtag':
			$type = 'group';break;
		case 'thread':
			$type = '';break;
		case 'space':
			$type = 'user';break;
		case 'tag':
			$type = '';break;
		case 'blog':
			$type = 'diary';break;
		default:
			break;
	}
	return $type;
}

function getShareContent($body_data,$body_general,$type,$uid,$username,$image,$image_link) {
	global $db_bbsurl;
	$body_data_array = unserialize($body_data);
	$content = array();
	if ($type == 'space') {

		preg_match("/\<a href=\"space\.php\?uid=(\d+)\"\>(.+)\<\/a\>/is",$body_data_array['username'],$matches);
		$content['user']['username'] = $matches[2];
		$content['user']['image']	= '';//?????????????????
		$content['link'] = $db_bbsurl.'/mode.php?m=o&q=user&u='.$matches[1];

	} elseif ($type == 'link') {

		$content['link'] = $body_data_array['data'];
		$content['type'] = 'web';

	} elseif ($type == 'video') {

		$content['link'] = $body_data_array['data'];
		$content['type'] = 'video';
		list($content['video']['hash'],$content['video']['host']) = getHash($content['link']);

	} elseif ($type == 'music') {

		$content['link'] = $body_data_array['data'];
		$content['type'] = 'music';

	} elseif ($type == 'flash') {

		$content['link'] = $body_data_array['data'];
		$content['type'] = 'flash';

	} elseif ($type == 'blog') {
		preg_match("/\<a href=\"space\.php\?uid=(\d+)&do=blog&id=(\d+)\"\>(.+)\<\/a\>/is",$body_data_array['subject'],$matches);
		$content['diary']['subject'] = $matches[3];
		$content['link'] = $db_bbsurl.'/mode.php?m=o&q=diary&u='.$matches[1].'&did='.$matches[2];
		//待小均做完日志
	} elseif ($type == 'album') {

		preg_match("/\<a href=\"space\.php\?uid=(\d+)&do=album&id=(\d+)\"\>(.+)\<\/a\>/is",$body_data_array['albumname'],$matches);
		$content['album']['uid'] = $uid;
		$content['album']['username'] = $username;
		$content['album']['image'] = $image;
		$content['link'] = $db_bbsurl.'/mode.php?m=o&q=photos&a=album&aid='.$matches[2];

	} elseif ($type == 'pic') {

		preg_match("/space\.php\?uid=(\d+)&do=album&picid=(\d+)/is",$image_link,$matches);
		$content['photo']['uid'] = $uid;
		$content['photo']['username'] = $username;
		$content['photo']['image'] = $image;
		$content['link'] = $db_bbsurl.'/mode.php?m=o&q=photos&a=view&pid='.$matches[2];

	} elseif ($type == 'mtag') {

		//貌似功能漏做了

	} elseif ($type == 'thread') {

		return '';

	} elseif ($type == 'tag') {

		return '';
	}

	$content['descrip']	= $body_general;
	return serialize($content);
}

function getHash($link) {
	$parselink = parse_url($link);
	preg_match("/(youku.com|youtube.com|5show.com|ku6.com|sohu.com|sina.com.cn)$/i",$parselink['host'],$hosts);
	switch ($hosts[1]) {
		case 'youku.com':
			preg_match("/id\_(\w+)\=/",$link,$matches);
			break;
		case 'ku6.com':
			preg_match("/\/([\w\-]+)\.html/",$link,$matches);
			break;
		case 'youtube.com':
			preg_match("/v\=([\w\-]+)/",$link,$matches);
			break;
		case 'sina.com.cn':
			preg_match("/\/(\d+)-(\d+)\.html/",$link,$matches);
			break;
		case 'sohu.com':
			preg_match("/\/(\d+)\/*$/",$link,$matches);
			break;
	}
	if(!empty($matches[1])) {
		$return = $matches[1];
	} else {
		$return = '';
	}
	return array($return,$hosts[1]);
}
//统计分类中日志数
function getDiaryNum($classid) {
	global $UCHDB,$uch_db_prefix;
	$count = $UCHDB->get_value("SELECT COUNT(*) FROM {$uch_db_prefix}blog WHERE classid=".pwEscape($classid));
	return $count;
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

###########################  版块转换相关函数  #######################

//有游客权限则返回空
function allow_group_str($str)
{
	$arr_str = explode("\t",$str);
	if ('' == $str || is_array($arr_str) == false)
	{
		return '';
	}

	if (strpos($str,'7') === false) //判断是否有游客权限
	{
		return ','.str_replace("\t",',', $str).',';
	}
	else
	{
		return '';
	}
}

//判断是否有子版块
function getIfHasChild($catedb,$fid)
{
	global $catedb;
	foreach ($catedb as $k => $v)
	{
		if ($fid == $v['fup'])
		{
			return 1;
		}
	}
	return 0;
}

//取得forumdata表中lastpost的值
function getLastpost($lastpost)
{
	list($ltid, $ltitle, $ltime, $lauthor) = explode("\t", $lastpost);
	$lastpost = addslashes($ltitle."\t".$lauthor."\t".$ltime."\tread.php?tid=".$ltid);
	return $lastpost;
}

?>
