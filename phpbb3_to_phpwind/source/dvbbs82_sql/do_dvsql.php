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

if ($step == 1)
{
    echo 1111;exit;
	//ÉèÖÃ
	$s = $SDB->get_one("SELECT convert(varchar(255),forum_badwords) as forum_badwords,forum_maxonline,convert(char, forum_maxonlinedate, 20) AS forum_maxonlinedate,forum_usernum,forum_yesterdaynum,forum_maxpostnum,convert(char, forum_maxpostdate, 20) AS forum_maxpostdate,forum_lastuser FROM {$source_prefix}Setup");
	Add_S($s);

	$DDB->update("UPDATE {$pw_prefix}bbsinfo SET newmember = '".$s['forum_lastuser']."', totalmember = '".(int)$s['forum_usernum']."', higholnum = '".(int)$s['forum_maxonline']."', higholtime = '".(int)dt2ut($s['forum_maxonlinedate'])."', yposts = '".(int)$s['forum_yesterdaynum']."', hposts = '".(int)$s['forum_maxpostnum']."' WHERE id = 1");

	$DDB->update("UPDATE {$pw_prefix}config SET db_value = 1 WHERE db_name IN ('db_topped','db_gdcheck')");
	$DDB->update("UPDATE {$pw_prefix}config SET db_value = 600 WHERE db_name = 'db_signheight'");
	$DDB->update("UPDATE {$pw_prefix}config SET db_value = '".addslashes('http://'.$_SERVER['HTTP_HOST'])."' WHERE db_name = 'db_bbsurl'");
	$DDB->update("UPDATE {$pw_prefix}config SET db_value = 3 WHERE db_name = 'db_attachdir'");

	if ($s['forum_badwords'])
	{
		$badwords = explode('|', $s['forum_badwords']);
		foreach ($badwords as $v)
		{
			$DDB->update("INSERT INTO {$pw_prefix}wordfb (word,wordreplace,type) VALUES ('".$v."','',1)");
		}
	}

	$s_c = 6;
	report_log();
	newURL($step);
}
elseif ($step == 2)
{
	//ÓÃ»§
	$_specialgroup = $insertadmin = '';
	require_once (S_P.'tmp_credit.php');

	if (!$start)
	{
		//$DDB->query("TRUNCATE TABLE {$pw_prefix}members");
		//$DDB->query("TRUNCATE TABLE {$pw_prefix}memberdata");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}membercredit");
		//$DDB->query("TRUNCATE TABLE {$pw_prefix}administrators");
		//$DDB->query("ALTER TABLE {$pw_prefix}members CHANGE username username VARCHAR( 50 ) ".$DDB->collation()." NOT NULL DEFAULT ''");
		//writeover(S_P.'tmp_group.php', "\$_specialgroup = ".pw_var_export(changegroups()).";", true);
		$DDB->query("DELETE FROM {$pw_prefix}credits WHERE cid > 1");
		$DDB->query("ALTER TABLE {$pw_prefix}credits AUTO_INCREMENT = 2");
		foreach ($expandCredit as $v)
		{
			$DDB->update("INSERT INTO {$pw_prefix}credits (name,unit) VALUES ('".addslashes($v[0])."','".addslashes($v[1])."')");
		}
	}
	require_once (S_P.'tmp_group.php');

	$query = $SDB->query("SELECT userid,convert(varchar(255),username) as username,userpassword,convert(varchar(255),useremail) as useremail,userpost,usertopic,convert(varchar(255),usersign) as usersign,usersex,convert(varchar(255),userface) as userface,userwidth,userheight,convert(varchar(255),userim) as userim,convert(char,joindate,20) as joindate,convert(char,lastlogin,20) as lastlogin,userlogins,userviews,lockuser,userwealth,userep,usercp,userpower,userisbest,convert(varchar(255),usertitle) as usertitle,convert(char,userbirthday,23) as userbirthday,convert(varchar(255),userinfo) as userinfo,usergroupid,userhidden,usermoney,userticket FROM {$source_prefix}User WHERE userid >= $start AND userid < $end");
	while ($m = $SDB->fetch_array($query))
	{
		Add_S($m);
		if (!$m['username'] || htmlspecialchars($m['username']) != $m['username'] )
		{
			$f_c++;
			errors_log($m['userid']."\t".$m['username']);
			continue;
		}
		$m['userid'] = (int)$m['userid'];
		switch ($m['usergroupid'])
		{
			case '1'://¹ÜÀíÔ±
				$groupid = '3';
				$insertadmin .= "(".$m['userid'].", '".$m['username']."', 3),";
				break;
			case '2'://×Ü°æÖ÷
				$groupid = '4';
				$insertadmin .= "(".$m['userid'].", '".$m['username']."', 4),";
				break;
			case '3'://°æÖ÷
				$groupid = '5';
				$insertadmin .= "(".$m['userid'].", '".$m['username']."', 5),";
				break;
			default :
				$groupid = isset($_specialgroup[$m['usergroupid']]) ? $m['usergroupid'] : '-1';
				break;
		}
		if ($m['lockuser'] == 2 || $m['lockuser'] == 1)
		{
			$groupid = 6;
		}
		if ($m['userface'])
		{
			$offset = intval(strpos($m['userface'], '|'));
			$offset && $offset += 1;
			if (substr($m['userface'],0+$offset,7) == 'http://')
			{
				$m['userface'] = substr($m['userface'],0+$offset).'|2|'.$m['userwidth'].'|'.$m['userheight'];
			}
			elseif(substr($m['userface'],0+$offset,10) == 'UploadFace')
			{
				$m['userface'] = substr($m['userface'],11+$offset).'|3|'.$m['userwidth'].'|'.$m['userheight'];
			}
			elseif (substr($m['userface'],0+$offset,6) == 'images')
			{
				$m['userface'] = substr($m['userface'], 16+$offset).'|1||';
			}
            elseif(substr($m['userface'],0,9) == '../images')
            {
                //$m['userface'] = substr($m['userface'],9).'|3|120|120';
//echo $m['userface'];exit;
            }
            elseif(substr($m['userface'],0,5) == 'icon/')
            {
                $m['userface'] = $m['userface'].'|1||';
//echo $m['userface'];exit;
            }
            //echo substr($m['userface'],0,9);exit;
		}
		$m['joindate'] = dt2ut($m['joindate']);
		$m['lastlogin'] = dt2ut($m['lastlogin']);
		$m['userbirthday'] = $m['userbirthday'] ? $m['userbirthday'] : '0000-00-00';

		eval($creditdata);
		$expandCreditSQL = '';
		if($expandCredit)
		{
			foreach ($expandCredit as $k => $v)
			{
				$expandCreditSQL .= '('.$m['userid'].','.($k + 2).','.(int)($m[$v[2]]).'),';
			}
			$expandCreditSQL && $DDB->update("INSERT INTO {$pw_prefix}membercredit (uid, cid, value) VALUES ".substr($expandCreditSQL, 0, -1));
		}

		!$m['usersex'] && $m['usersex'] = 2;
		$signchange = (convert(stripslashes($m['usersign'])) == $m['usersign']) ? '1' : '2';
		$UserIM = explode('|||', $m['userim']);
		$UserInfo = explode('|||', $m['userinfo']);
		$userstatus=($signchange-1)*256+128+1*64+4;//ÓÃ»§Î»×´Ì¬ÉèÖÃ

		$DDB->update("REPLACE INTO {$pw_prefix}members (uid,username,password,email,groupid,icon,gender,regdate,signature,introduce,oicq,icq,msn,yahoo,site,honor,bday,userstatus) VALUES (".$m['userid'].",'".$m['username']."','".$m['userpassword']."','".$m['useremail']."','".$groupid."','".$m['userface']."','".$m['usersex']."','".$m['joindate']."','".$m['usersign']."','".$UserInfo[2]."','".$UserIM[1]."','".$UserIM[2]."','".$UserIM[3]."','".$UserIM[4]."','".$UserIM[0]."','".$m['usertitle']."','".$m['userbirthday']."','".$userstatus."')");

		$DDB->update("replace INTO {$pw_prefix}memberdata (uid,postnum,digests,rvrc,money,credit,currency,lastvisit,thisvisit) VALUES (".$m['userid'].",".(int)$m['userpost'].",".(int)$m['userisbest'].",".$rvrc.",".$money.",".$credit.",$currency,'".$m['lastlogin']."','".$m['lastlogin']."')");
		$s_c++;
	}
	$insertadmin && $DDB->update("REPLACE INTO {$pw_prefix}administrators (uid,username,groupid) VALUES ".substr($insertadmin, 0, -1));
	$row = $SDB->get_one("SELECT COUNT(*) AS num FROM {$source_prefix}User WHERE userid >= $end");
	if ($row['num'])
	{
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
        echo 2;exit;
		report_log();
		newURL($step);
	}
}
elseif ($step == 3)
{
	//°æ¿é
	$catedb = array();

	//$DDB->query("TRUNCATE TABLE {$pw_prefix}forums");
	$DDB->query("TRUNCATE TABLE {$pw_prefix}forumdata");
	$query = $SDB->query("SELECT boardid,convert(varchar(255),boardtype) as boardtype,parentid,parentstr,depth,rootid,child,todaynum,orders,convert(varchar(255),readme) as readme,convert(varchar(255),boardmaster) as boardmaster,postnum,topicnum,convert(varchar(255),indeximg) as indeximg FROM {$source_prefix}Board");

	while($f = $SDB->fetch_array($query))
	{
		$catedb[$f['boardid']] = $f;
	}

	foreach($catedb as $fid => $forum)
	{
		$f_tmp = parent_upfid($forum['boardid'],'parentid',0);
		$childid = (int)parent_ifchildid($forum['boardid'],'parentid');
		$ifsub = ($f_tmp[0] == 'sub') ? 1 : 0;
		$ftype = $f_tmp[0];

		$forum['boardmaster'] && $forum['boardmaster'] = ',' . (str_replace('|',',',$forum['boardmaster'])) . ',';
		$upadmin = '';
		getupadmin($fid, $upadmin);

		//$DDB->update("INSERT INTO {$pw_prefix}forums (fid,fup,ifsub,childid,type,logo,name,descrip,vieworder,forumadmin,fupadmin) VALUES (".(int)$fid.",".(int)$f_tmp[1].",".$ifsub.",".$childid.",'".$ftype."','".addslashes($forum['indeximg'])."','".addslashes($forum['boardtype'])."','".addslashes($forum['readme'])."','".$forum['orders']."','".addslashes($forum['boardmaster'])."','".addslashes($upadmin)."')");
		$DDB->update("REPLACE INTO {$pw_prefix}forumdata (fid,tpost,topic,article) VALUES (".(int)$fid.",'".$forum['todaynum']."','".$forum['postnum']."','".$forum['topicnum']."')");
		$s_c++;
	}

	report_log();
    echo 3;exit;
	newURL($step);
}
elseif ($step == 4)
{
	//Ö÷Ìâ
	if(!$start)
	{
		//$DDB->query("TRUNCATE TABLE {$pw_prefix}threads");
	}
	$query = $SDB->query("SELECT topicid,title,boardid,pollid,locktopic,child,postusername,postuserid,convert(char,dateandtime,20) AS dateandtime,hits,expression,votetotal,lastpost,convert(char,lastposttime,20) AS lastposttime,istop,isvote,isbest,posttable,issmstopic,topicmode FROM {$source_prefix}Topic WHERE topicid >= $start AND topicid < $end");

	while ($t = $SDB->fetch_array($query))
	{
		if (!$t['boardid'])
		{
			$f_c++;
			errors_log($t['topicid']);
			continue;
		}

		$t['dateandtime'] = dt2ut($t['dateandtime']);
		$t['lastposttime'] = dt2ut($t['lastposttime']);
		$face = explode('|', $t['expression']);
		$t['expression'] && $t['expression'] = str_replace('face', '', substr($face[1], 0, strrpos($face[1], '.')));
		$t['title'] = strip_tags($t['title']);
		$t['lastpost'] = explode('$', $t['lastpost']);
		$t['istop'] == 2 && $t['istop'] = 3;
		$special = ($t['issmstopic'] == '2') ? 4 : ($t['isvote'] ? 1 : 0);

		$ifshield = 0;
		$ifcheck = 1;
		if ($t['boardid'] == 777)
		{
			$ifcheck = 0;
			$t['boardid'] = (int)$t['locktopic'];
		}
		elseif ($t['boardid'] == 444)
		{
			//»ØÊÕÕ¾
			$t['boardid'] = (int)$t['locktopic'];
			$t['dateandtime'] = (int)$t['dateandtime'];
			$DDB->update("REPLACE INTO {$pw_prefix}recycle (pid,tid,fid,deltime,admin) VALUES (0,".$t['topicid'].",".$t['boardid'].",".$t['dateandtime'].",'phpwind')");
            $t['boardid'] = 0;
			//$ifshield = 2;
		}

		$DDB->update("REPLACE INTO {$pw_prefix}threads (tid,fid,icon,author,authorid,subject,ifcheck,postdate,lastpost,lastposter,hits,replies,topped,locked,digest,special,ifshield) VALUES (".$t['topicid'].",".$t['boardid'].",'".$t['expression']."','".addslashes($t['postusername'])."',".(int)$t['postuserid'].",'".addslashes(strip_tags($t['title']))."',$ifcheck,'".$t['dateandtime']."','".$t['lastposttime']."','".addslashes($t['lastpost'][0])."',".$t['hits'].",".$t['child'].",".intval($t['istop']).",".($t['locktopic'] == 1 ? 1 : 0).",".$t['isbest'].",".$special.",$ifshield)");
		$s_c++;
	}
	$row = $SDB->get_one("SELECT COUNT(*) AS num FROM {$source_prefix}Topic WHERE topicid >= $end");
    echo '---',$row['num'].'<br>---',$end;
	if ($row['num'])
	{
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
        echo 'topic';exit;
		report_log();
		newURL($step);
	}
}
elseif ($step == 5)
{
	//»Ø¸´
	$table = $_GET['table'] ? (int)$_GET['table'] : 1;
	if ($table == '1' && !$start)
	{
		//$DDB->query("TRUNCATE TABLE {$pw_prefix}tmsgs");
		//$DDB->query("TRUNCATE TABLE {$pw_prefix}posts");
	}
    //ECHO '11';exit;
	$query = $SDB->query("SELECT TOP $percount announceid,parentid,boardid,username,topic,convert(text,body) as body,convert(char,dateandtime,20) AS dateandtime,postuserid,rootid,ip,locktopic,isaudit,isupload,expression,getmoney,getmoneytype,phone FROM {$source_prefix}bbs{$table} WHERE announceid >= $start");

	while ($bbs = $SDB->fetch_array($query))
	{
        ADD_S($bbs);
        $lastid = $bbs['announceid'];
		$attdata = array();
		$newtopicid = '';
		$bbs['dateandtime'] = strtotime($bbs['dateandtime']);

//echo $bbs['body'];exit;
		$bbs['body'] = dvbbs_ubb($bbs['body']);
        //echo $bbs['body'];exit;
		if ($bbs['getmoney'] && $bbs['getmoneytype'] == 3)
		{
			$bbs['body'] = '[sell='.(int)$bbs['getmoney'].',money]'.$bbs['body'].'[/sell]';
		}
		$ifconvert = (convert($bbs['body']) == $bbs['body']) ? 1 : 2;
		if ($bbs['parentid'])
		{
			if ($bbs['boardid'] == 777)
			{
				$bbs['boardid'] = (int)$bbs['locktopic'];
				$bbs['isaudit'] = 1;
			}
			elseif ($bbs['boardid'] == 444)
			{
				//»ØÊÕÕ¾
				$newtopicid = (int)$bbs['rootid'];
				$bbs['boardid'] = $bbs['rootid'] = 0;
			}
/*
$check_pid = $DDB->get_value("SELECT pid FROM pw_posts WHERE pid=".$bbs['announceid']);
if(!$check_pid){
    echo $check_pid;exit;
}else{
    continue;
}*/
/*
			$DDB->update("REPLACE INTO {$pw_prefix}posts (aid,fid,tid,author,authorid,postdate,subject,userip,ifsign,ifconvert,ifcheck,content,ifshield) VALUES ('',".$bbs['boardid'].",".$bbs['rootid'].",'".addslashes($bbs['username'])."','".$bbs['postuserid']."','".$bbs['dateandtime']."','".($bbs['topic'] ? addslashes(strip_tags($bbs['topic'])) : '')."','".addslashes($bbs['ip'])."',1,'".$ifconvert."',".((int)$bbs['isaudit']^1).",'".addslashes($bbs['body'])."','".($bbs['locktopic'] == 2 ? 1 : 0)."')");
            */
            $postsql[] = "(".$bbs['announceid'].",'".$bbs['isupload']."',".$bbs['boardid'].",".$bbs['rootid'].",'".addslashes($bbs['username'])."','".$bbs['postuserid']."','".$bbs['dateandtime']."','".($bbs['topic'] ? addslashes(strip_tags($bbs['topic'])) : '')."','".addslashes($bbs['ip'])."',1,'".$ifconvert."',".((int)$bbs['isaudit']^1).",'".addslashes($bbs['body'])."','".($bbs['locktopic'] == 2 ? 1 : 0)."')";
			$t['dateandtime'] = (int)$t['dateandtime'];
			$newtopicid && $DDB->update("REPLACE INTO {$pw_prefix}recycle (pid,tid,fid,deltime,admin) VALUES (".$DDB->insert_id().",".$newtopicid.",".(int)$bbs['locktopic'].",".$t['dateandtime'].",'phpwind')");
		}
		else
		{
            /*
			$DDB->update("REPLACE INTO {$pw_prefix}tmsgs (tid,aid,userip,ifsign,ifconvert,content) VALUES (".$bbs['rootid'].",'','".addslashes($bbs['ip'])."',1,'".$ifconvert."','".addslashes($bbs['body'])."')");
            */
            /*
$check_tid = $DDB->get_value("SELECT tid FROM pw_tmsgs WHERE tid=".$bbs['rootid']);
if(!$check_tid){
    echo $check_tid;exit;
}else{
    continue;
}*/
            $tmsgssql[] = "(".$bbs['rootid'].",'".$bbs['isupload']."','".addslashes($bbs['ip'])."',1,'".$ifconvert."','".addslashes($bbs['body'])."')";
            if($bbs['phone'] && $bbs['phone'] != ' '){
            $topicvaluesql[] = "(".$bbs['rootid'].",".$bbs['boardid'].",'".$bbs['phone']."')";
            $topictid[] = $bbs['rootid'];
            }
			$bbs['locktopic'] == 2 && $DDB->update("UPDATE {$pw_prefix}threads SET ifshield = 1 WHERE tid = ".$bbs['rootid']." LIMIT 1");
		}
		$s_c++;
	}
    if($postsql){
        $DDB->update("REPLACE INTO {$pw_prefix}posts (pid,aid,fid,tid,author,authorid,postdate,subject,userip,ifsign,ifconvert,ifcheck,content,ifshield) VALUES ".implode(",",$postsql));
        ECHO "REPLACE INTO {$pw_prefix}posts (pid,aid,fid,tid,author,authorid,postdate,subject,userip,ifsign,ifconvert,ifcheck,content,ifshield) VALUES ".implode(",",$postsql);EXIT;
    }
    if($tmsgssql){
        $DDB->update("REPLACE INTO {$pw_prefix}tmsgs (tid,aid,userip,ifsign,ifconvert,content) VALUES ".implode(",",$tmsgssql));
    //echo "REPLACE INTO {$pw_prefix}tmsgs (tid,aid,userip,ifsign,ifconvert,content) VALUES ".implode(",",$tmsgssql);exit;
    }
    if($topicvaluesql){
        //echo "REPLACE INTO {$pw_prefix}topicvalue9 (tid,fid,field129) VALUES ".implode(",",$topicvaluesql);exit;
        $DDB->update("REPLACE INTO {$pw_prefix}topicvalue9 (tid,fid,field129) VALUES ".implode(",",$topicvaluesql));
        $DDB->update("UPDATE pw_threads SET modelid=9 WHERE tid IN (".implode(",",$topictid).")");
    }
	$row = $SDB->get_one("SELECT max(announceid) as max FROM {$source_prefix}bbs{$table}");
    echo '---',$row['max'].'<br>---',$lastid;
	if ($lastid < $row['max'])
	{
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&table='.$table.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	elseif ($table < $source_tablenum)
	{
        echo 'table'.$table;exit;
		refreshto($cpage.'&step='.$step.'&table='.++$table.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
        echo 5;exit;
		report_log();
		newURL($step);
	}
}
elseif ($step == 51)
{
    //echo 1;exit;
    $table = 1;
	$query = $SDB->query("SELECT TOP $percount announceid,parentid,boardid,username,topic,convert(text,body) as body,convert(char,dateandtime,20) AS dateandtime,postuserid,rootid,ip,locktopic,isaudit,isupload,expression,getmoney,getmoneytype,phone FROM {$source_prefix}bbs1 WHERE announceid >= $start");

	while ($bbs = $SDB->fetch_array($query))
	{
        ADD_S($bbs);
        $lastid = $bbs['announceid'];
		$attdata = array();
		$newtopicid = '';
		$bbs['dateandtime'] = strtotime($bbs['dateandtime']);

		$bbs['body'] = dvbbs_ubb($bbs['body']);
		if ($bbs['getmoney'] && $bbs['getmoneytype'] == 3)
		{
			$bbs['body'] = '[sell='.(int)$bbs['getmoney'].',money]'.$bbs['body'].'[/sell]';
		}
		$ifconvert = (convert($bbs['body']) == $bbs['body']) ? 1 : 2;
		if ($bbs['parentid'])
		{
			if ($bbs['boardid'] == 777)
			{
				$bbs['boardid'] = (int)$bbs['locktopic'];
				$bbs['isaudit'] = 1;
			}
			elseif ($bbs['boardid'] == 444)
			{
				//»ØÊÕÕ¾
				$newtopicid = (int)$bbs['rootid'];
				$bbs['boardid'] = $bbs['rootid'] = 0;
			}
/*
			$DDB->update("REPLACE INTO {$pw_prefix}posts (aid,fid,tid,author,authorid,postdate,subject,userip,ifsign,ifconvert,ifcheck,content,ifshield) VALUES ('',".$bbs['boardid'].",".$bbs['rootid'].",'".addslashes($bbs['username'])."','".$bbs['postuserid']."','".$bbs['dateandtime']."','".($bbs['topic'] ? addslashes(strip_tags($bbs['topic'])) : '')."','".addslashes($bbs['ip'])."',1,'".$ifconvert."',".((int)$bbs['isaudit']^1).",'".addslashes($bbs['body'])."','".($bbs['locktopic'] == 2 ? 1 : 0)."')");
            */
            $postsql[] = "(".$bbs['announceid'].",'".$bbs['isupload']."',".$bbs['boardid'].",".$bbs['rootid'].",'".addslashes($bbs['username'])."','".$bbs['postuserid']."','".$bbs['dateandtime']."','".($bbs['topic'] ? addslashes(strip_tags($bbs['topic'])) : '')."','".addslashes($bbs['ip'])."',1,'".$ifconvert."',".((int)$bbs['isaudit']^1).",'".addslashes($bbs['body'])."','".($bbs['locktopic'] == 2 ? 1 : 0)."')";
			$t['dateandtime'] = (int)$t['dateandtime'];
$ptablearr[] = $bbs['rootid'];
			//$newtopicid && $DDB->update("REPLACE INTO {$pw_prefix}recycle (pid,tid,fid,deltime,admin) VALUES (".$DDB->insert_id().",".$newtopicid.",".(int)$bbs['locktopic'].",".$t['dateandtime'].",'phpwind')");
		}
		else
		{
            /*
			$DDB->update("REPLACE INTO {$pw_prefix}tmsgs (tid,aid,userip,ifsign,ifconvert,content) VALUES (".$bbs['rootid'].",'','".addslashes($bbs['ip'])."',1,'".$ifconvert."','".addslashes($bbs['body'])."')");
            */
            $tmsgssql[] = "(".$bbs['rootid'].",'".$bbs['isupload']."','".addslashes($bbs['ip'])."',1,'".$ifconvert."','".addslashes($bbs['body'])."')";
            if($bbs['phone'] && $bbs['phone'] != ' '){
            //$topicvaluesql[] = "(".$bbs['rootid'].",".$bbs['boardid'].",'".$bbs['phone']."')";
            $topictid[] = $bbs['rootid'];
            }
			//$bbs['locktopic'] == 2 && $DDB->update("UPDATE {$pw_prefix}threads SET ifshield = 1 WHERE tid = ".$bbs['rootid']." LIMIT 1");
		}
		$s_c++;
	}
    if($postsql){
        //ECHO "REPLACE INTO {$pw_prefix}posts (pid,aid,fid,tid,author,authorid,postdate,subject,userip,ifsign,ifconvert,ifcheck,content,ifshield) VALUES ".implode(",",$postsql);EXIT;
        $DDB->update("REPLACE INTO {$pw_prefix}posts1 (pid,aid,fid,tid,author,authorid,postdate,subject,userip,ifsign,ifconvert,ifcheck,content,ifshield) VALUES ".implode(",",$postsql));
    }
    if($ptablearr){
        $DDB->update("UPDATE pw_threads SET ptable=1 WHERE tid in( ".implode(",",$ptablearr).")");
    }
    if($tmsgssql){
        $DDB->update("REPLACE INTO {$pw_prefix}tmsgs (tid,aid,userip,ifsign,ifconvert,content) VALUES ".implode(",",$tmsgssql));
    //echo "REPLACE INTO {$pw_prefix}tmsgs (tid,aid,userip,ifsign,ifconvert,content) VALUES ".implode(",",$tmsgssql);exit;
    }
    if($topicvaluesql){
        //echo "REPLACE INTO {$pw_prefix}topicvalue9 (tid,fid,field129) VALUES ".implode(",",$topicvaluesql);exit;
        //$DDB->update("REPLACE INTO {$pw_prefix}topicvalue9 (tid,fid,field129) VALUES ".implode(",",$topicvaluesql));
        //$DDB->update("UPDATE pw_threads SET modelid=9 WHERE tid IN (".implode(",",$topictid).")");
    }
	$row = $SDB->get_one("SELECT max(announceid) as max FROM {$source_prefix}bbs1");
    echo '---',$row['max'].'<br>---',$lastid;
	if ($lastid < $row['max'])
	{
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&table='.$table.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	elseif ($table < $source_tablenum)
	{
        echo 'table'.$table;exit;
		refreshto($cpage.'&step='.$step.'&table='.++$table.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
        echo 5;exit;
		report_log();
		newURL($step);
	}
}
elseif ($step == 53)
{
    //echo 1;exit;
    $table = 3;
	$query = $SDB->query("SELECT TOP $percount announceid,parentid,boardid,username,topic,convert(text,body) as body,convert(char,dateandtime,20) AS dateandtime,postuserid,rootid,ip,locktopic,isaudit,isupload,expression,getmoney,getmoneytype,phone FROM {$source_prefix}bbs3 WHERE announceid >= $start");

	while ($bbs = $SDB->fetch_array($query))
	{
        ADD_S($bbs);
        $lastid = $bbs['announceid'];
		$attdata = array();
		$newtopicid = '';
		$bbs['dateandtime'] = strtotime($bbs['dateandtime']);

		$bbs['body'] = dvbbs_ubb($bbs['body']);
		if ($bbs['getmoney'] && $bbs['getmoneytype'] == 3)
		{
			$bbs['body'] = '[sell='.(int)$bbs['getmoney'].',money]'.$bbs['body'].'[/sell]';
		}
		$ifconvert = (convert($bbs['body']) == $bbs['body']) ? 1 : 2;
		if ($bbs['parentid'])
		{
			if ($bbs['boardid'] == 777)
			{
				$bbs['boardid'] = (int)$bbs['locktopic'];
				$bbs['isaudit'] = 1;
			}
			elseif ($bbs['boardid'] == 444)
			{
				//»ØÊÕÕ¾
				$newtopicid = (int)$bbs['rootid'];
				$bbs['boardid'] = $bbs['rootid'] = 0;
			}
/*
			$DDB->update("REPLACE INTO {$pw_prefix}posts (aid,fid,tid,author,authorid,postdate,subject,userip,ifsign,ifconvert,ifcheck,content,ifshield) VALUES ('',".$bbs['boardid'].",".$bbs['rootid'].",'".addslashes($bbs['username'])."','".$bbs['postuserid']."','".$bbs['dateandtime']."','".($bbs['topic'] ? addslashes(strip_tags($bbs['topic'])) : '')."','".addslashes($bbs['ip'])."',1,'".$ifconvert."',".((int)$bbs['isaudit']^1).",'".addslashes($bbs['body'])."','".($bbs['locktopic'] == 2 ? 1 : 0)."')");
            */
            $postsql[] = "(".$bbs['announceid'].",'".$bbs['isupload']."',".$bbs['boardid'].",".$bbs['rootid'].",'".addslashes($bbs['username'])."','".$bbs['postuserid']."','".$bbs['dateandtime']."','".($bbs['topic'] ? addslashes(strip_tags($bbs['topic'])) : '')."','".addslashes($bbs['ip'])."',1,'".$ifconvert."',".((int)$bbs['isaudit']^1).",'".addslashes($bbs['body'])."','".($bbs['locktopic'] == 2 ? 1 : 0)."')";
			$t['dateandtime'] = (int)$t['dateandtime'];
$ptablearr[] = $bbs['rootid'];
			//$newtopicid && $DDB->update("REPLACE INTO {$pw_prefix}recycle (pid,tid,fid,deltime,admin) VALUES (".$DDB->insert_id().",".$newtopicid.",".(int)$bbs['locktopic'].",".$t['dateandtime'].",'phpwind')");
		}
		else
		{
            /*
			$DDB->update("REPLACE INTO {$pw_prefix}tmsgs (tid,aid,userip,ifsign,ifconvert,content) VALUES (".$bbs['rootid'].",'','".addslashes($bbs['ip'])."',1,'".$ifconvert."','".addslashes($bbs['body'])."')");
            */
            $tmsgssql[] = "(".$bbs['rootid'].",'".$bbs['isupload']."','".addslashes($bbs['ip'])."',1,'".$ifconvert."','".addslashes($bbs['body'])."')";
            if($bbs['phone'] && $bbs['phone'] != ' '){
            //$topicvaluesql[] = "(".$bbs['rootid'].",".$bbs['boardid'].",'".$bbs['phone']."')";
            $topictid[] = $bbs['rootid'];
            }
			//$bbs['locktopic'] == 2 && $DDB->update("UPDATE {$pw_prefix}threads SET ifshield = 1 WHERE tid = ".$bbs['rootid']." LIMIT 1");
		}
		$s_c++;
	}
    if($postsql){
        //ECHO "REPLACE INTO {$pw_prefix}posts (pid,aid,fid,tid,author,authorid,postdate,subject,userip,ifsign,ifconvert,ifcheck,content,ifshield) VALUES ".implode(",",$postsql);EXIT;
        $DDB->update("REPLACE INTO {$pw_prefix}posts2 (pid,aid,fid,tid,author,authorid,postdate,subject,userip,ifsign,ifconvert,ifcheck,content,ifshield) VALUES ".implode(",",$postsql));
    }
    if($ptablearr){
        $DDB->update("UPDATE pw_threads SET ptable=2 WHERE tid in( ".implode(",",$ptablearr).")");
    }
    if($tmsgssql){
        $DDB->update("REPLACE INTO {$pw_prefix}tmsgs (tid,aid,userip,ifsign,ifconvert,content) VALUES ".implode(",",$tmsgssql));
    //echo "REPLACE INTO {$pw_prefix}tmsgs (tid,aid,userip,ifsign,ifconvert,content) VALUES ".implode(",",$tmsgssql);exit;
    }
    if($topicvaluesql){
        //echo "REPLACE INTO {$pw_prefix}topicvalue9 (tid,fid,field129) VALUES ".implode(",",$topicvaluesql);exit;
        //$DDB->update("REPLACE INTO {$pw_prefix}topicvalue9 (tid,fid,field129) VALUES ".implode(",",$topicvaluesql));
        //$DDB->update("UPDATE pw_threads SET modelid=9 WHERE tid IN (".implode(",",$topictid).")");
    }
	$row = $SDB->get_one("SELECT max(announceid) as max FROM {$source_prefix}bbs3");
    echo '---',$row['max'].'<br>---',$lastid;
	if ($lastid < $row['max'])
	{
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&table='.$table.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	elseif ($table < $source_tablenum)
	{
        echo 'table'.$table;exit;
		refreshto($cpage.'&step='.$step.'&table='.++$table.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
        echo 5;exit;
		report_log();
		newURL($step);
	}
}
elseif ($step == 54)
{
    //echo 1;exit;
    $table = 4;
	$query = $SDB->query("SELECT TOP $percount announceid,parentid,boardid,username,topic,convert(text,body) as body,convert(char,dateandtime,20) AS dateandtime,postuserid,rootid,ip,locktopic,isaudit,isupload,expression,getmoney,getmoneytype,phone FROM {$source_prefix}bbs4 WHERE announceid >= $start");

	while ($bbs = $SDB->fetch_array($query))
	{
        ADD_S($bbs);
        $lastid = $bbs['announceid'];
		$attdata = array();
		$newtopicid = '';
		$bbs['dateandtime'] = strtotime($bbs['dateandtime']);
//print_r($bbs);exit;
		$bbs['body'] = dvbbs_ubb($bbs['body']);
		if ($bbs['getmoney'] && $bbs['getmoneytype'] == 3)
		{
			$bbs['body'] = '[sell='.(int)$bbs['getmoney'].',money]'.$bbs['body'].'[/sell]';
		}
		$ifconvert = (convert($bbs['body']) == $bbs['body']) ? 1 : 2;
		if ($bbs['parentid'])
		{
			if ($bbs['boardid'] == 777)
			{
				$bbs['boardid'] = (int)$bbs['locktopic'];
				$bbs['isaudit'] = 1;
			}
			elseif ($bbs['boardid'] == 444)
			{
				//»ØÊÕÕ¾
				$newtopicid = (int)$bbs['rootid'];
				$bbs['boardid'] = $bbs['rootid'] = 0;
			}
/*
			$DDB->update("REPLACE INTO {$pw_prefix}posts (aid,fid,tid,author,authorid,postdate,subject,userip,ifsign,ifconvert,ifcheck,content,ifshield) VALUES ('',".$bbs['boardid'].",".$bbs['rootid'].",'".addslashes($bbs['username'])."','".$bbs['postuserid']."','".$bbs['dateandtime']."','".($bbs['topic'] ? addslashes(strip_tags($bbs['topic'])) : '')."','".addslashes($bbs['ip'])."',1,'".$ifconvert."',".((int)$bbs['isaudit']^1).",'".addslashes($bbs['body'])."','".($bbs['locktopic'] == 2 ? 1 : 0)."')");
            */
            $postsql[] = "(".$bbs['announceid'].",'".$bbs['isupload']."',".$bbs['boardid'].",".$bbs['rootid'].",'".addslashes($bbs['username'])."','".$bbs['postuserid']."','".$bbs['dateandtime']."','".($bbs['topic'] ? addslashes(strip_tags($bbs['topic'])) : '')."','".addslashes($bbs['ip'])."',1,'".$ifconvert."',".((int)$bbs['isaudit']^1).",'".addslashes($bbs['body'])."','".($bbs['locktopic'] == 2 ? 1 : 0)."')";
			$t['dateandtime'] = (int)$t['dateandtime'];
$ptablearr[] = $bbs['rootid'];
			//$newtopicid && $DDB->update("REPLACE INTO {$pw_prefix}recycle (pid,tid,fid,deltime,admin) VALUES (".$DDB->insert_id().",".$newtopicid.",".(int)$bbs['locktopic'].",".$t['dateandtime'].",'phpwind')");
		}
		else
		{
            /*
			$DDB->update("REPLACE INTO {$pw_prefix}tmsgs (tid,aid,userip,ifsign,ifconvert,content) VALUES (".$bbs['rootid'].",'','".addslashes($bbs['ip'])."',1,'".$ifconvert."','".addslashes($bbs['body'])."')");
            */
            $tmsgssql[] = "(".$bbs['rootid'].",'".$bbs['isupload']."','".addslashes($bbs['ip'])."',1,'".$ifconvert."','".addslashes($bbs['body'])."')";
            if($bbs['phone'] && $bbs['phone'] != ' '){
            //$topicvaluesql[] = "(".$bbs['rootid'].",".$bbs['boardid'].",'".$bbs['phone']."')";
            $topictid[] = $bbs['rootid'];
            }
			//$bbs['locktopic'] == 2 && $DDB->update("UPDATE {$pw_prefix}threads SET ifshield = 1 WHERE tid = ".$bbs['rootid']." LIMIT 1");
		}
		$s_c++;
	}
    if($postsql){
        //ECHO "REPLACE INTO {$pw_prefix}posts (pid,aid,fid,tid,author,authorid,postdate,subject,userip,ifsign,ifconvert,ifcheck,content,ifshield) VALUES ".implode(",",$postsql);EXIT;
        $DDB->update("REPLACE INTO {$pw_prefix}posts3 (pid,aid,fid,tid,author,authorid,postdate,subject,userip,ifsign,ifconvert,ifcheck,content,ifshield) VALUES ".implode(",",$postsql));
        //echo "REPLACE INTO {$pw_prefix}posts3 (pid,aid,fid,tid,author,authorid,postdate,subject,userip,ifsign,ifconvert,ifcheck,content,ifshield) VALUES ".implode(",",$postsql);exit;
    }
    if($ptablearr){
        $DDB->update("UPDATE pw_threads SET ptable=3 WHERE tid in( ".implode(",",$ptablearr).")");
    }
    if($tmsgssql){
        $DDB->update("REPLACE INTO {$pw_prefix}tmsgs (tid,aid,userip,ifsign,ifconvert,content) VALUES ".implode(",",$tmsgssql));
    }
    if($topicvaluesql){
        //echo "REPLACE INTO {$pw_prefix}topicvalue9 (tid,fid,field129) VALUES ".implode(",",$topicvaluesql);exit;
        //$DDB->update("REPLACE INTO {$pw_prefix}topicvalue9 (tid,fid,field129) VALUES ".implode(",",$topicvaluesql));
        //$DDB->update("UPDATE pw_threads SET modelid=9 WHERE tid IN (".implode(",",$topictid).")");
    }
	$row = $SDB->get_one("SELECT max(announceid) as max FROM {$source_prefix}bbs4");
    echo '---',$row['max'].'<br>---',$lastid;
	if ($lastid < $row['max'])
	{
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&table='.$table.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	elseif ($table < $source_tablenum)
	{
        echo 'table'.$table;exit;
		refreshto($cpage.'&step='.$step.'&table='.++$table.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
        echo 5;exit;
		report_log();
		newURL($step);
	}
}
elseif ($step == 6)
{
    //$source_tablenum = 2;
    //¸½¼þ
	if (!$start)
	{
	}
	$query = $SDB->query("SELECT TOP $percount F_ID,F_AnnounceID,F_BoardID,F_UserID,convert(char,F_Username,20) AS F_Username,F_Filename,F_FileType,F_FileSize,F_ViewNum,convert(char,F_Readme,20) AS F_Readme,convert(char,F_AddTime,20) AS F_AddTime FROM {$source_prefix}Upfile WHERE F_ID > $start");

    $attachesql = array();
	while ($rt = $SDB->fetch_array($query))
	{
        ADD_S($rt);
        $lastid = $rt['F_ID'];
		$rt['F_AddTime'] = dt2ut($rt['F_AddTime']);
        if($rt['F_AnnounceID']){
            $tid_arr = explode("|",$rt['F_AnnounceID']);
            $tid = $tid_arr[0];
            $pid2 = $tid_arr[1];
            //echo "SELECT posttable FROM dv_topic where topicid=$tid";exit;
            $rt2 = $SDB->get_one("SELECT posttable FROM dv_topic where topicid=$tid");
            if(!$rt2)continue;
            $table2 = $rt2['posttable'];
            $bbs1 = $SDB->get_one("SELECT ParentID,RootID FROM {$table2} WHERE AnnounceID ='".$pid2."'");
            if($bbs1['ParentID']){
                $pid = $pid2;
                $post_arr[] = $pid2;
            }else{
                $pid = 0;
                $tmsg_arr[] = $tid;
            }
            $tid_arr2[] = $tid;
        }else{
            $tid = $pid = 0;
        }
        $name = explode("/",$rt['F_Filename']);
        $name = addslashes($name[1]);
        if($rt['F_FileType']=='jpg' || $rt['F_FileType']=='gif'){
            $rt['F_FileType']='img';
        }else{
            $rt['F_FileType']='zip';
        }
        $attachesql[] = "(".$rt['F_ID'].",'".$rt['F_BoardID']."','".$rt['F_UserID']."','".$tid."',".$pid.",'".$name."','".$rt['F_FileType']."',".(round($rt['F_FileSize']/1024)).",'".$rt['F_Filename']."',".$rt['F_ViewNum'].",".$rt['F_AddTime'].",'".addslashes($rt['F_Readme'])."')";
        $s_c++;
    }
    if($attachesql){
        //echo "REPLACE INTO {$pw_prefix}attachs (aid,fid,uid,tid,pid,name,type,size,attachurl,hits,uploadtime,descrip) VALUES ".implode(",",$attachesql);exit;
        $DDB->update("REPLACE INTO {$pw_prefix}attachs (aid,fid,uid,tid,pid,name,type,size,attachurl,hits,uploadtime,descrip) VALUES ".implode(",",$attachesql));
    }
    if($tid_arr2){
        $DDB->update("UPDATE {$pw_prefix}threads SET ifupload=1 WHERE tid in (".implode(",",$tid_arr2).")");
    }
    if($tmsg_arr){
        $DDB->update("UPDATE {$pw_prefix}tmsgs SET aid=1 WHERE tid in (".implode(",",$tmsg_arr).")");
    }
    if($post_arr){
        $DDB->update("UPDATE {$pw_prefix}posts SET aid=1 WHERE pid in (".implode(",",$post_arr).")");
        $DDB->update("UPDATE {$pw_prefix}posts1 SET aid=1 WHERE pid in (".implode(",",$post_arr).")");
        $DDB->update("UPDATE {$pw_prefix}posts2 SET aid=1 WHERE pid in (".implode(",",$post_arr).")");
        $DDB->update("UPDATE {$pw_prefix}posts3 SET aid=1 WHERE pid in (".implode(",",$post_arr).")");
    }
	$row = $SDB->get_one("SELECT max(F_ID) as max FROM {$source_prefix}Upfile");
    echo 'max',$row['max'].'<br>las',$lastid;
	if ($lastid < $row['max'])
    {
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}
    else
    {
        echo 6;exit;
		report_log();
		newURL($step);

	}
}
elseif ($step == 7)
{

    //todo prisk
    newURL($step);exit;
    //¸üÐÂ¸½¼þpid
	$query = $DDB->query("SELECT a.*,p.pid as pid2,p.tid as tid2 FROM {$pw_prefix}attachs a LEFT JOIN pw_posts p USING(pid) WHERE a.aid > $start LIMIT $percount");
	while ($rt = $DDB->fetch_array($query))
	{

        $lastid = $rt['aid'];

        if($rt['pid2']){
            $tid = $rt['tid2'];
            $DDB->update("UPDATE {$pw_prefix}attachs SET tid = ".$tid." WHERE aid=".$rt['aid']);
        }else{
            $rt['tid2'] && $tmsg_aid[] = $rt['tid2'];
        }
        $rt['tid2'] && $tid_arr[] = $rt['tid2'];
    }
    if($tid_arr){
        $DDB->update("UPDATE {$pw_prefix}threads SET ifupload=1 WHERE tid in (".implode(",",$tid_arr).")");
    }
    if($tmsg_aid){
        echo ("UPDATE {$pw_prefix}attachs SET pid = 0 WHERE aid in (".implode(",",$tmsg_aid).")");
        $DDB->update("UPDATE {$pw_prefix}attachs SET pid = 0 WHERE aid in (".implode(",",$tmsg_aid).")");
    }
    $row = $SDB->get_one("SELECT max(F_ID) as max FROM {$source_prefix}Upfile");
    echo '',$row['max'].'',$lastid;
	if ($lastid < $row['max'])
    {
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}
    else
    {
		report_log();

		newURL($step);
	}
}
elseif ($step == 8)
{
	//¹«¸æ

	$DDB->query("TRUNCATE TABLE {$pw_prefix}announce");

	$query = $SDB->query("SELECT id,boardid,title,convert(text,content) AS content,username,convert(char,addtime,20) AS addtime FROM {$source_prefix}BbsNews");
	while ($a = $SDB->fetch_array($query))
	{
		$a['addtime'] = strtotime($a['addtime']);
		$a['boardid'] = (int)$a['boardid'];
		!$a['boardid'] && $a['boardid'] = '-1';
		$a['content'] = addslashes(dvbbs_ubb($a['content']));
		$DDB->update("INSERT INTO {$pw_prefix}announce (aid,fid,ifopen,author,startdate,subject,content,ifconvert) VALUES (".$a['id'].",".$a['boardid'].",1,'".addslashes($a['username'])."','".addslashes($a['addtime'])."','".addslashes($a['title'])."','".$a['content']."',".((convert(stripslashes($a['content'])) == $a['content']) ? 0 : 1).")");
		$s_c++;
	}
	report_log();
	newURL($step);
}
elseif ($step == 9)
{
	//¶ÌÐÅ
	if(!$start)
	{
        $DDB->update("TRUNCATE TABLE {$pw_prefix}ms_messages");
        $DDB->update("TRUNCATE TABLE {$pw_prefix}ms_relations");
        $DDB->update("TRUNCATE TABLE {$pw_prefix}ms_replies");
	}
	$query = $SDB->query("SELECT id,sender,incept,title,convert(text,content) AS content,flag,convert(char,sendtime,20) AS sendtime,delR,delS,isSend FROM {$source_prefix}message WHERE id >= $start AND id < $end");
	while($m = $SDB->fetch_array($query))
	{
        ADD_S($m);
		if (!$m['incept'] || !$m['sender'] || !$m['content'])
		{
			$f_c++;
			errors_log($m['incept']."\t".$m['sender']."\t".$m['content']);
			continue;
		}
		$touid = $DDB->get_one("SELECT uid FROM {$pw_prefix}members WHERE username = '".addslashes($m['incept'])."'");
		$fromuid = $DDB->get_one("SELECT uid FROM {$pw_prefix}members WHERE username = '".addslashes($m['sender'])."'");
		if ($touid && $fromuid && !$m['delR'] && !$m['delS'])
		{
			$sendtime = strtotime($m['sendtime']);
            /*
			$DDB->update("INSERT INTO {$pw_prefix}msg (mid,touid,fromuid,username,type,ifnew,mdate) VALUES (".$m['id'].",".$touid['uid'].",".$fromuid['uid'].",'".($m['isSend'] ? addslashes($m['sender']) : addslashes($m['incept']))."','".($m['isSend'] ? 'rebox' : 'sebox')."',".($m['flag']^1).",'".$sendtime."')");
			$DDB->update("INSERT INTO {$pw_prefix}msgc (mid,title,content) VALUES (".$m['id'].",'".addslashes($m['title'])."','".addslashes($m['content'])."')");
			if (($touid['uid'] != $fromuid['uid']) && $m['isSend'])
			{
				$DDB->update("INSERT INTO {$pw_prefix}msglog (mid,uid,withuid,mdate,mtype) VALUES (".$m['id'].",".$fromuid['uid'].",".$touid['uid'].",'$sendtime','send')");
				$DDB->update("INSERT INTO {$pw_prefix}msglog (mid,uid,withuid,mdate,mtype) VALUES (".$m['id'].",".$touid['uid'].",".$fromuid['uid'].",'$sendtime','receive')");
			}
*/

	        $message_sql[] = "('".$m['id']."',".$fromuid['uid'].",'".$m['sender']."','".$m['title']."','".$m['content']."','".serialize(array('categoryid'=>1,'typeid'=>100))."',".$sendtime.",".$sendtime.",'".serialize(array($m['incept']))."')";
	        $replies_sql[] = "('".$m['id']."',".$m['id'].",'".$fromuid['uid']."','".$m['sender']."','".$m['title']."','".$m['content']."','1',".$sendtime.",".$sendtime.")";

            $userIds = "";
	        $userIds = array($touid['uid'],$fromuid['uid']);
	        foreach($userIds as $otherId){
	            $relations_sql[] = "(".$otherId.",'".$m['id']."','1','100','0',".(($otherId == $fromuid['uid']) ? 1 : 0).",".$sendtime.",".$sendtime.")";
            }
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
	$row = $SDB->get_one("SELECT COUNT(*) AS num FROM {$source_prefix}message WHERE id >= $end");
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
elseif ($step == 10)
{
	//ºÃÓÑ
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}friends");
	}
	$query = $SDB->query("SELECT F_id,F_friend,convert(char,F_addtime,20) AS F_addtime,F_Mod,F_userid FROM {$source_prefix}friend WHERE f_id >= $start AND f_id < $end");
	while($f = $SDB->fetch_array($query))
	{
		if ($f['F_Mod'] != 2 && $f['F_userid'])
		{
			$friendid = $DDB->get_one("SELECT uid FROM {$pw_prefix}members WHERE username = '".addslashes($f['F_friend'])."'");
			if ($friendid)
			{
				$DDB->update("REPLACE INTO {$pw_prefix}friends (uid,friendid,status,joindate) VALUES (".$f['F_userid'].",".$friendid['uid'].",0,".strtotime($f['F_addtime']).")");
			}
			$s_c++;
		}
		else
		{
			$f_c++;
			errors_log($f['F_Mod']."\t".$f['F_userid']);
			continue;
		}
	}
	$row = $SDB->get_one("SELECT COUNT(*) AS num FROM {$source_prefix}friend WHERE f_id >= $end");
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
elseif ($step == 11)
{
	//Í¶Æ±
	$DDB->query("TRUNCATE TABLE {$pw_prefix}polls");

	$query = $SDB->query("SELECT t.topicid,t.dateandtime,v.voteid,v.vote,convert(text,v.votenum) AS votenum,v.votetype,v.lockvote,v.voters,convert(char,v.timeout,20) AS timeout FROM {$source_prefix}Topic t LEFT JOIN {$source_prefix}Vote v ON t.pollid = v.voteid WHERE t.isvote = 1");
	while ($vote = $SDB->fetch_array($query))
	{
		$voteoptions = array();
		$voteitem = explode('|',$vote['vote']);
		$votenum  = explode('|',$vote['votenum']);

		foreach ($voteitem as $k => $i)
		{
			if ($votenum[$k] && $i)
			{
				$votearray[$i] = array($i,$votenum[$k]); //TODOÍ¶Æ±Ñ¡Ïî£¬Í¶Æ±ÌûÊý
			}
		}
		$queryvote = $SDB->query("SELECT id,voteid,userid,votedate,convert(text,voteoption) AS voteoption FROM {$source_prefix}VoteUser WHERE voteid = ".(int)$vote['voteid']);

		while ($vt = $SDB->fetch_array($queryvote))
		{
			$username = $DDB->get_one("SELECT username FROM {$pw_prefix}members WHERE uid = ".$vt['userid']);
			if ($username)
			{
				$tvid = explode(',', $vt['voteoption']);
				foreach ($tvid as $n)
				{
					$n && $voteoptions[$n][2][] = $username['username'];
				}
			}
		}

		$votearray = serialize($votearray);

		$ipoll = "(".$vote['topicid'].",'{$votearray}',1,1,".ceil((strtotime($vote['timeout']) - $vote['dateandtime'])/86400).")";
		$DDB->update("REPLACE INTO {$pw_prefix}polls (tid,voteopts,modifiable,previewable,timelimit) VALUES ".$ipoll);

		$s_c++;

	}
	report_log();
    echo 11;exit;
	newURL($step);
}
elseif ($step == 12)
{
	//½»Ò×
	$query = $DDB->query("SELECT t.tid,tm.content FROM {$pw_prefix}threads t LEFT JOIN {$pw_prefix}tmsgs tm USING (tid) WHERE t.special = 4");
	while ($trade = $DDB->fetch_array($query))
	{
		preg_match_all('/\((seller|subject|body|price|demo|ww|qq)\)(.+?)\(\/\\1\)/is', $trade['content'], $m);
		$trade['content'] = '[payto]'.$m[0][0].$m[0][1].strip_tags($m[0][2]).$m[0][3].'(ordinary_fee)0(/ordinary_fee)(express_fee)0(/express_fee)(contact)'.$m[2][5].'(/contact)'.$m[0][4].'(method)4(/method)[/payto]';
		$DDB->update("UPDATE {$pw_prefix}tmsgs SET content = '".addslashes($trade['content'])."', ifconvert = 2 WHERE tid = ".$trade['tid']);
		$s_c++;
	}
	report_log();
	newURL($step);
}
elseif ($step == 13)
{
	//ÓÑÇéÁ´½Ó
	require_once S_P.'lang_'.$dest_charset.'.php';

	$DDB->query("TRUNCATE TABLE {$pw_prefix}sharelinks");
	$query = $SDB->query("SELECT id,convert(varchar(255),boardname) AS boardname,convert(varchar(255),readme) AS readme,convert(varchar(255),url) AS url,logo,islogo FROM {$source_prefix}BbsLink");
	$ilink = '';
	while ($l = $SDB->fetch_array($query))
	{
		$ilink .= "('".addslashes($l['boardname'])."','".addslashes($l['url'])."','".addslashes($l['readme'])."','".addslashes($l['logo'])."',1),";
		$s_c++;
	}
	$ilink .= $lang['link'];
	$DDB->update("INSERT INTO {$pw_prefix}sharelinks (name,url,descrip,logo,ifcheck) VALUES ".$ilink);
	report_log();
	newURL($step);
}
elseif ($step == 14)
{
    //$DDB->query("");
	//return preg_replace(array('~\[upload=(?:jpg|gif|jpeg|bmp|png),[^]]*?\]uploadfile/([^]]+?)\[\/upload\]~ei','~\[upload=[^]]*?\]viewfile.asp\?id=(\d+?)\[\/upload\]~is','/\[em(\d+?)\]/i','/\[mp=(\d+?),(\d+?),(?:true|false)\](.+?)\[\/mp\]/si','/\[rm=(\d+?),(\d+?),(?:true|false)\](.+?)\[\/rm\]/si'), array("attachment('\\1')","[attachment=\\1]",'[s:\\1]','[wmv=\\1,\\2,0]\\3[/wmv]','[rm=\\1,\\2,0]\\3[/rm]'),

}
else
{
	ObHeader($basename.'?action=finish&dbtype='.$dbtype);
}

##########################

function getparcid($cid){
	global $catedb;
	if (!$catedb[$cid]['depth']) return 0;
	$lt = strrpos($catedb[$cid]['parentstr'],',');
	$lcid = ($lt === FALSE) ? $catedb[$cid]['parentstr'] : substr($catedb[$cid]['parentstr'], $lt+1);
	if ($catedb[$lcid]['depth']>3)
	{
		getparcid($catedb[$lcid]['boardid']);
	}
	return $catedb[$lcid]['boardid'];
}
function getupadmin($cid, &$upadmin)
{
	global $catedb;
	if ($catedb[$cid]['boardmaster'])
	{
		$BoardMaster = explode('|', $catedb[$cid]['boardmaster']);
		foreach($BoardMaster as $value)
		{
			$upadmin .= $upadmin ? addslashes($value).',' : ','.addslashes($value).',';
		}
	}
	if ($catedb[$cid] && $catedb[$cid]['depth'])
	{
		getupadmin($catedb[$cid]['parentid'], $upadmin);
	}
}
function changegroups()
{
	global $pw_prefix, $source_prefix, $SDB, $DDB, $dest_charset;
	require_once S_P.'lang_'.$dest_charset.'.php';
	$DDB->update("TRUNCATE TABLE {$pw_prefix}usergroups");
	$DDB->update($lang['group']);

	$query = $SDB->query("SELECT UserGroupID,convert(varchar(255),usertitle) AS usertitle,GroupSetting,GroupPic,MinArticle,ParentGID FROM {$source_prefix}UserGroups WHERE ParentGID IN (2,3)");
	$_specialdata = array();
	$gptype = '';
	while ($g = $SDB->fetch_array($query))
	{
		$gid = (int)$g['ParentGID'];
		if ($gid == 3)
		{
			$gptype = 'member';
			$grouppost = (int)$g['MinArticle'];
		}
		else
		{
			$gptype = 'special';
			$_specialdata[$gid] = 1;
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
    //[upload=gif,063ÂíÐÀè´.gif]UploadFile/2011-6/20116201727116075.gif[/upload]<br/>
    //'~\[upload=(?:jpg|gif|jpeg|bmp|png)[^]]*?\]uploadfile([^]]+?)\[\/upload\]~i'
    //'[img]attachment\\1[/img]'
    /*
    <p align="center">[upload=jpg,1.jpg]UploadFile/2011-8/20118121581694274.jpg[/upload]</p>
<p><br/><span style="WIDOWS: 2; TEXT-TRANSFORM: none; TEXT-INDENT: 0px; BORDER-COLLAPSE: separate; FONT: medium="medium"  Arial,Microsoft Yahei,Simsun,sans-serif; WHITE-SPACE: normal; ORPHANS: 2; LETTER-SPACING: normal; COLOR: rgb(0,0,0); WORD-SPACING: 0px; -webkit-border-horizontal-spacing: 0px; -webkit-border-vertical-spacing: 0px; -webkit-text-decorations-in-effect: none; -webkit-text-size-adjust: auto; -webkit-text-stroke-width: 0px" class="Apple-style-span"><span style="LINE-HEIGHT: 26px; FONT-FAMILY: ËÎÌå; FONT-SIZE: 14px" class="Apple-style-span">&nbsp; 7ÔÂ£¬È«¹ú²»ÉÙµØ·½¶¼ÔÚ¾­Àú×ÅÏÄÈÕµÄÑ×ÈÈ£¬È»¶øÔÚËÄ´¨¸Ê×ÎÕÛ¶àÉ½È´³öÏÖ´óÑ©³¡¾°£¬Ê¢ÏÄÑ©¾°ÒýµÃÓÎ¿ÍÁ÷Á¬Íü·µ¡£</span></span></p>
    */
    /*
    <p align="center">upload=gif,1.gif]UploadFile/2011-7/20117311091517389.gif[/upload]</p>
<p align="left"><br/><font size="6">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Ð¡Ê±ºò£¬ÀÏÊ¦¸æËßÎÒ£ººìÁì½íÊÇºìÆìµÄÒ»½Ç£¬ºìÆìÊÇÏÈÁÒÃÇÓÃÏÊÑªÈ¾³É£¬´øÉÏºìÁì½íÎÞ±ÈÈÙ¹â£¡ÏÖÔÚºìÆìÒÀ¾ÉÆ®Ñï£¬ÑÕÉ«ÒÀ¾ÉÏÊÑÞ£¬Ö»ÊÇ................¡£</font></p>
    */
	return preg_replace(array('~\[upload=(?:jpg|gif|jpeg|bmp|png)(,[^]]+?)\]uploadfile/([^]]+?)\[\/upload\]~ei','~\[upload=(?:jpg|gif|jpeg|bmp|png)\]uploadfile/([^]]+?)\[\/upload\]~ei','~\[upload=[^]]*?\]viewfile.asp\?id=(\d+?)\[\/upload\]~is','/\[em(\d+?)\]/i','/\[mp=(\d+?),(\d+?),(?:true|false)\](.+?)\[\/mp\]/si','/\[rm=(\d+?),(\d+?),(?:true|false)\](.+?)\[\/rm\]/si'), array("attachment('\\2')","attachment('\\1')","[attachment=\\1]",'[s:\\1]','[wmv=\\1,\\2,0]\\3[/wmv]','[rm=\\1,\\2,0]\\3[/rm]'), str_replace(array('[BR]','[B]','[/B]','[I]','[/I]','[U]','[/U]','[SIZE]','[/SIZE]','[center]','[left]','[right]','[/left]','[/right]','[/center]','[URL=','[/URL]','[EMAIL]','[/EMAIL]','[IMG]','[/IMG]','[QUOTE]','[/QUOTE]','[replyview]','[/replyview]'),array('','[b]','[/b]','[i]','[/i]','[u]','[/u]','[size]','[/size]','[align=center]','[align=left]','[align=right]','[/align]','[/align]','[/align]','[url=','[/url]','[email]','[/email]','[img]','[/img]','[quote]','[/quote]','[post]','[/post]'),$content));
}
function attachmentid($v)
{
	global $SDB, $source_prefix,$DDB;
	//$att = $SDB->get_one("SELECT F_ID FROM {$source_prefix}Upfile WHERE F_Filename = '".$v."'");
    $att = $DDB->get_one("SELECT aid FROM pw_attachs WHERE attachurl = '".$v."'");
    $aid = $att['aid'];
    return "[attachment=$aid]";
}
function dvattachment($id)
{
	global $SDB, $source_prefix;
	$att = $SDB->get_one("SELECT F_Filename FROM {$source_prefix}Upfile WHERE F_ID = ".(int)$id);
	return $att ? '<a href="attachment/'.htmlspecialchars($att['F_Filename']).'">'.htmlspecialchars($att['F_Filename']).'</a>' : '';
}
?>