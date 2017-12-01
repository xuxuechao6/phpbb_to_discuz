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

$source_prefix = '';
if ($step == 1)
{
	if (!$start)
	{
        //勋章
        $DDB->query("TRUNCATE TABLE {$pw_prefix}medalinfo");
        $DDB->query("TRUNCATE TABLE {$pw_prefix}medalslogs");
        $DDB->query("ALTER TABLE {$pw_prefix}medalinfo CHANGE id id SMALLINT( 6 ) NOT NULL AUTO_INCREMENT");
        $query = $SDB->query("SELECT * FROM {$source_prefix}award");
        while ($m = $SDB->fetch_array($query))
        {
        $url = addslashes($m['award_icon_url']);
        $url = explode('medals/',$url);
        $DDB->update("INSERT INTO {$pw_prefix}medalinfo (id,name,intro,picurl) VALUES (".$m['award_id'].",'".addslashes($m['award_name'])."','".addslashes($m['award_desc'])."','$url[1]')");
        $s_c++;
        }
    }
    $lastid = $start;
    $medallog = array();
	$query = $SDB->query("SELECT * FROM {$source_prefix}award_user WHERE issue_id > $start ORDER BY issue_id LIMIT $percount");
	while ($l = $SDB->fetch_array($query))
	{
        $lastid = $l['issue_id'];
        $awardee = $SDB->get_value("SELECT username FROM {$source_prefix}user WHERE userid=".$l['award_id']);
        $timelimit = 0;
        //$timelimit = $l['expiration'];
        $l['status'] = 1;
        $medallog[] = "(".$l['issue_id'].",'".addslashes($awardee)."','','".$l['issue_time']."','$timelimit','','".$l['award_id']."','1','".addslashes($l['issue_reason'])."')";
        $medaluser[] = "(".$l['userid'].",".$l['award_id'].")";
		$s_c++;
	}
    if($medallog){
        $medallogarr = implode(",",$medallog);
        $medaluser = implode(",",$medaluser);
	    $DDB->update("INSERT INTO {$pw_prefix}medalslogs (id,awardee,awarder,awardtime,timelimit,state,level,action,why) VALUES $medallogarr");
        //$DDB->update("INSERT INTO {$pw_prefix}medal_user(uid,mid) VALUES $medaluser");
    }
	$maxid = $SDB->get_value("SELECT max(issue_id) FROM {$source_prefix}award_user");
    if ($lastid < $maxid)
	{
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
	    report_log();
	    newURL($step,'&medal=yes');
		exit();
	}
}elseif ($step == 2)
{
	//表情
    $_pwface = $_vbbface = array();
	$DDB->query("TRUNCATE TABLE {$pw_prefix}smiles");
	$query = $SDB->query("SELECT * FROM {$source_prefix}imagecategory  WHERE imagetype = '3'");
	while ($s = $SDB->fetch_array($query))
	{
        $path = $SDB->get_value("SELECT {$source_prefix}smiliepath FROM smilie WHERE imagecategoryid=".$s['imagecategoryid']." LIMIT 1");
        $path = explode('smilies/',$path);
        $a = strpos($path['1'],"/");
        if(!$a){
        $path['0'] = '';
        }else{
        $path = explode('/',$path['1']);
        }
        $path = $path['0'];
		$DDB->update("INSERT INTO {$pw_prefix}smiles (id,path,name,vieworder,type) VALUES (".$s['imagecategoryid'].",'$path','".addslashes($s['title'])."','".$s['displayorder']."',0)");
		$s_c++;
	}
    $query = $DDB->query("SELECT * FROM {$pw_prefix}smiles");
	while ($i = $DDB->fetch_array($query))
	{
		$query2 = $SDB->query("SELECT * FROM {$source_prefix}smilie WHERE imagecategoryid = ".$i['id']);
		while($s = $SDB->fetch_array($query2))
		{
            $path = explode('smilies/',$s['smiliepath']);
            $a = strpos($path['1'],"/");
            if(!$a){
            $path = $path['1'];
            }else{
            $path = explode('/',$path['1']);
            $path = $path['1'];
            }
			$DDB->update("INSERT INTO {$pw_prefix}smiles (path,vieworder,type,name) VALUES('".$path."',".$s['displayorder'].",".$i['id'].",'".addslashes($s['title'])."')");
			$_pwface[] = '[s:'.$DDB->insert_id().']';
			$_vbbface[] = $s['smilietext'];
		}
	}
	writeover(S_P.'tmp_face.php', "\$_pwface = ".pw_var_export($_pwface).";\n\$_vbbface = ".pw_var_export($_vbbface).";", true);
	report_log();
	newURL($step);
}
elseif($step == 3) {
    ////转换用户数据
    //禁言会员处理数据初始化
    	$banusersql = $banuids = array();
    $avatarurl = $SDB->get_value("SELECT value FROM {$source_prefix}setting WHERE varname='avatarurl'");
	if(!$start){
		$DDB->query("TRUNCATE TABLE {$pw_prefix}members");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}memberdata");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}membercredit");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}memberinfo");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}administrators");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}banuser");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}credits");
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
		//转换特殊用户组
        //writeover(S_P.'tmp_group.php', "\$_specialgroup = ".pw_var_export(changegroups()).";", true);
        //更新用户组并保存特殊组到临时文件
		//changespecialuser($vbvpre,$PW);
	}
	$query = $SDB->query("SELECT u.*,uf.*,ut.*,g.importusergroupid FROM {$source_prefix}user u LEFT JOIN {$source_prefix}userfield uf ON u.userid=uf.userid LEFT JOIN {$source_prefix}usertextfield ut ON u.userid=ut.userid LEFT JOIN {$source_prefix}usergroup g ON u.usergroupid=g.usergroupid WHERE u.userid > $start ORDER BY u.userid LIMIT $percount");
    echo ("SELECT u.*,uf.*,ut.*,g.importusergroupid FROM user u LEFT JOIN userfield uf ON u.userid=uf.userid LEFT JOIN usertextfield ut ON u.userid=ut.userid LEFT JOIN usergroup g ON u.usergroupid=g.usergroupid WHERE u.userid > $start ORDER BY u.userid LIMIT $percount").'<br />';
	while($user = $SDB->fetch_array($query)) {
        $lastid = $user['userid'];
        if (!$user['userid'] || !$user['username'])
		{
			$f_c++;
			errors_log($user['uid']."\t".$user['username']);
			continue;
		}

		if($user['usergroupid']==5){ //超级版主
			$groupid = 4;
		} elseif($user['usergroupid']==6 || $user['usergroupid']==35){ //admin
			$groupid = 3;
		} elseif($user['usergroupid']==7){ //版主
			$groupid = 5;
		} elseif($user['usergroupid']==32){ //禁言组
			$groupid = 6;
		} elseif($user['importusergroupid']!=0){ //自定义用户组
			$groupid = $user['usergroupid']+20;
		} else{
			$groupid = -1;
		}
		//$sign_test = convert($user['signature']);
		//$sign_test == $user['signature']?$ifconvert=1:$ifconvert=2;
       // unset($sign_test);
        $m['sightml'] = addslashes(html2bbcode(stripslashes($m['sightml'])));//个性签名
		$signchange = ($m['sightml'] == convert($m['sightml'])) ? 1 : 2;

		$rvrc	= $user['reputation']*10;//威望=原来的积分*10,如果没有安装插件，就将这行代码注释
		$money	= intval($user['credits']+$user['credits_saved']);//如果没有安装银行插件，就将这行代码注释
        $medals = $DDB->query("SELECT mid from {$pw_prefix}medal_user where uid = $lastid");
        while($rt1 = $DDB->fetch_array($medals)){
        $medals1 .= $rt1[mid].',';
        }
       // $medals1 && exit($medals1);
        $ificon = $user['avatarrevision'];
        if(!$ificon){
        $icon = '';
        }else{
         //print_r($_SERVER['HTTP_HOST']);exit;
         //$avatarurl
         $url_temp = $_SERVER['HTTP_HOST'];
        $icon = "$url_temp/attachment/upload/vbb/customavatars/avatar{$lastid}_{$ificon }.gif|2|120|120||1";
        }
		$DDB->query("REPLACE INTO {$pw_prefix}members (uid,username, password, email, groupid, memberid,icon, gender, regdate, signature, introduce, oicq, icq, site, location, bday,salt,yz,msn,medals) VALUES('$user[userid]','".addslashes($user['username'])."', '$user[password]', '".($user['email'])."', '$groupid',8,'$icon', '', '$user[joindate]', '".addslashes($user['signature'])."','".addslashes($user['field1']).addslashes($user['field2']).addslashes($user['field3']).addslashes($user['field4'])."', '$user[oicq]', '$user[icq]', '".($user['homepage'])."', '".addslashes($user['field2'])."','$user[birthday_search]','".addslashes($user['salt'])."','1','".addslashes($user['msn'])."','".$medals1."')");

		$DDB->query("REPLACE INTO {$pw_prefix}memberdata(uid, postnum, rvrc, money, lastvisit, thisvisit, onlineip,onlinetime)VALUES('$user[userid]','$user[posts]','$rvrc','$money','$user[lastvisit]', '$user[lastvisit]','$user[ipaddress]','$user[online_time]')");
		$s_c++;
	}

    $maxid = $SDB->get_value("SELECT max(userid) FROM {$source_prefix}user");
    echo '最大id',$maxid,'<br>','最后id',$lastid;
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
} elseif ($step == 4) {

    //板块
    $DDB->query("TRUNCATE TABLE {$pw_prefix}forums");
	$DDB->query("TRUNCATE TABLE {$pw_prefix}forumdata");
	$DDB->query("TRUNCATE TABLE {$pw_prefix}forumsextra");
	$DDB->query("ALTER TABLE {$pw_prefix}forums CHANGE descrip descrip TEXT ".$DDB->collation()." NOT NULL");
	$DDB->query("ALTER TABLE {$pw_prefix}forums CHANGE keywords keywords TEXT ".$DDB->collation()." NOT NULL");
	$DDB->query("TRUNCATE TABLE {$pw_prefix}topictype");//75新增主题分类表

	$query = $SDB->query("SELECT * FROM {$source_prefix}forum ORDER BY forumid") ;
	while($forum = $SDB->fetch_array($query)) {
		$forumdb[$forum['forumid']] = $forum;
	}
	foreach($forumdb as $fid=>$forum){
		$forum['description'] = addslashes($forum['description']);
		$adminfid = "'$forum[forumid]'";
		if($forum['parentid']==-1){
			$forum['type'] = 'category';
            $forum[parentid] = 0;
		} elseif($forum['parentid']!=-1 && $forumdb[$forum['parentid']]['parentid']==-1){
			$forum['type'] = 'forum';
			$adminfid.=",'$forum[parentid]'";
		} elseif($forum['parentid']!=-1 && $forumdb[$forum['parentid']]['parentid']!=-1){
			$forum['type'] = 'sub';
			if($forumdb[$forum['parentid']]['parentid']['parentid'] !='-1' ){
				$adminfid .= ",'$forum[parentid]','".$forumdb[$forum['parentid']]['parentid']."'";
			}else{
				$adminfid.=",'$forum[parentid]','".$forumdb[$forum['parentid']]['parentid']."','".$forumdb[$forum['parentid']]['parentid']['parentid']."'";
			}
		}
        $childlist = count(explode(',',$forum['childlist']));
        $childid = $childlist <= '2' ? 1 : 0;
        $ifsub = $forum['type'] == 'sub' ? 1 : 0;
		$DDB->query("INSERT INTO {$pw_prefix}forums (vieworder,fid,fup,type,name,descrip,ifsub,childid,showsub,allowhide,allowsell,allowtype,allowencode,viewsub) VALUES ('$forum[displayorder]','$forum[forumid]','$forum[parentid]','$forum[type]','".addslashes($forum['title'])."','".($forum['description'])."','$ifsub','$childid','1','1','1','3','1','0')");
		$DDB->query("INSERT INTO {$pw_prefix}forumdata (fid,topic) VALUES ('$forum[forumid]','$forum[threadcount]')");
	}
    report_log();
	newURL($step);
} elseif ($step == 5) {

  //http://www.52gc.cn/pw/pwb/pwbuilder.php?action=build&dbtype=vbb3&step=5
    //主题数据
	if(!$start) {
		$start = 0;
		$DDB->query("TRUNCATE TABLE {$pw_prefix}threads");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}polls");
	}
   // echo ("SELECT *,t.dateline AS tdateline,p.dateline AS pdateline FROM thread t LEFT JOIN poll p USING(pollid) WHERE t.threadid > $start LIMIT $percount");
	$query = $SDB->query("SELECT *,t.dateline AS tdateline,p.dateline AS pdateline FROM {$source_prefix}thread t LEFT JOIN {$source_prefix}poll p USING(pollid) WHERE t.threadid > $start LIMIT $percount");
	while($threads = $SDB->fetch_array($query)) {
        $lastid = $threads['threadid'];
		if (!$threads['forumid'])
		{
			$f_c++;
			errors_log($threads['forumid']."\t".$threads['pid']."\t".$threads['title']);
			continue;
		}
		$ifupload=$threads['attach']? 3 : 0;

		$poll =	$threads['pollid'] ? 1 : 0;
		if($poll){
			if ($threads['options']) {
				$votearray=array();
				$pollid = $threads['pollid'];
				$optionsArray	=	explode("|||", $threads['options']);
				$votessArray	=	explode("|||", $threads['votes']);
				foreach ($optionsArray as $key => $value){
					$comma = $voterids = '';
					$votes = $votessArray[$key];
					$keys  = $key + 1;
					$vt_userarray = array();
					$p2_query = $SDB->query("SELECT u.username FROM {$source_prefix}pollvote p LEFT JOIN {$source_prefix}user u ON u.userid=p.userid WHERE p.pollid = '$pollid'AND p.voteoption = '$keys'") ;
					while($p2 = $SDB->fetch_array($p2_query)){
						$vt_userarray[] = $p2['username'];
					}
					$votearray['options'][] = array($value,$votes,$vt_userarray);
				}
				$multiple	= $threads['multiple']? '1' :'';
				$maxchoices	= $multiple ? 0 : 1;
				$votearray['multiple'] = array($multiple,$maxchoices);
				$voteopts	= addslashes(serialize($votearray));
				$visible	= $threads['active'];
				$expiration	= $threads['timeout']==0 ? 0 : $threads['timeout']*86400 + $threads['pdateline'];
				$timelimit	= $expiration ? ($expiration-$threads['pdateline'])/(24*60*60*60) : '0';
				$state=0;
				if($expiration && $timestamp>=$expiration){
					$state=1;
				}
				$DDB->query("REPLACE INTO {$pw_prefix}polls(tid,voteopts,modifiable,previewable,timelimit) VALUES ('$threads[threadid]','$voteopts','0','$visible','$timelimit')");
			}else{
				$poll = 0;
			}
		}
        if(!$speed){
		$DDB->query("REPLACE INTO {$pw_prefix}threads(tid,fid,author,authorid,subject,ifcheck,postdate,lastpost,lastposter,hits,replies,topped,digest,ifupload,ifshield,special) VALUES('$threads[threadid]','$threads[forumid]','".addslashes($threads['postusername'])."','$threads[postuserid]','".addslashes($threads['title'])."','1','$threads[tdateline]','".($threads['lastpost'])."','".addslashes($threads['lastposter'])."','$threads[views]','$threads[replycount]','$threads[sticky]','$threads[goodnees]','$ifupload','0','$poll')");
        }
        if($speed){
         $threadssql[] =  "('$threads[threadid]','$threads[forumid]','".addslashes($threads['postusername'])."','$threads[postuserid]','".addslashes($threads['title'])."','1','$threads[tdateline]','".($threads['lastpost'])."','".addslashes($threads['lastposter'])."','$threads[views]','$threads[replycount]','$threads[sticky]','$threads[goodnees]','$ifupload','0','$poll')";
        }
		$s_c++;
	}
    if($speed){
     $DDB->query("REPLACE INTO {$pw_prefix}threads(tid,fid,author,authorid,subject,ifcheck,postdate,lastpost,lastposter,hits,replies,topped,digest,ifupload,ifshield,special) VALUES ".implode (",",$threadssql));
    }
	$maxid = $SDB->get_value("SELECT max(threadid) FROM {$source_prefix}thread");
    echo '最大id',$maxid,'<br>','最后id',$lastid;
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
}elseif($step == 6){
    //回复数据
    require_once S_P.'tmp_face.php';
    if(!$start) {
		$start = 0;
        $DDB->query("TRUNCATE TABLE {$pw_prefix}posts");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}tmsgs");
	 }
   echo ("SELECT p.*,t.forumid FROM post p LEFT JOIN thread t ON p.threadid=t.threadid WHERE p.postid > $start LIMIT $percount").'<br />';
        $query  = $SDB->query("SELECT p.*,t.forumid FROM {$source_prefix}post p LEFT JOIN {$source_prefix}thread t ON p.threadid=t.threadid WHERE p.postid > $start LIMIT $percount");
		while($posts = $SDB->fetch_array($query)){
		    if (!$posts['threadid'] || !$posts['forumid'])
		    {
			    $f_c++;
			    errors_log($$posts['postid']."\t".$posts['threadid']."\t".$posts['forumid']);
			continue;
		    }
            $lastid = $posts['postid'];
			$attach = '';
            $posts['pagetext'] = str_replace($_vbbface,$_pwface,$posts['pagetext']);
			$posts['pagetext'] = strtolower($posts['pagetext']);
			$posts['pagetext'] = preg_replace("/\[attach\]([0-9]+)\[\/attach\]/ei","'[attachment=\\1]'",$posts['pagetext']);
			$posts['pagetext'] =preg_replace("/\[size=\"([0-9]+)\"\]/ei","'[size=\\1]'",$posts['pagetext']);
			$posts['pagetext'] = preg_replace("/\[font=\"([#0-9a-z]{1,10})\"\]/ei","'[font=\\1]'",$posts['pagetext']);
			$posts['pagetext'] = preg_replace("/\[color=\"([#0-9a-z]{1,10})\"\]/ei","'[color=\\1]'",$posts['pagetext']);
			$posts['pagetext'] =preg_replace("/\[url=\"([^\[]*)\"\](.+?)\[\/url\]/is","'[url=\\1]\\2[/url]'",$posts['pagetext']);
			$posts['pagetext'] =preg_replace("/\[QUOTE=([#0-9a-z]*)\](.+?)\[\/QUOTE\]/is","'[quote]\\2[/quote]'",$posts['pagetext']);
			$posts['pagetext'] =preg_replace("/\[post=([#0-9a-z]*)\](.+?)\[\/post\]/is","'[url=\\1]\\2[/url]'",$posts['pagetext']);
			$posts['pagetext'] =preg_replace("/\[post\](.+?)\[\/post\]/is","'[url=\\1]\\1[/url]'",$posts['pagetext']);

			$posts['pagetext'] = str_replace('[center]','[align=center]',$posts['pagetext']);
			$posts['pagetext'] = str_replace('[/center]','[/align]',$posts['pagetext']);
			$posts['pagetext'] = str_replace('[right]','[align=right]',$posts['pagetext']);
			$posts['pagetext'] = str_replace('[/right]','[/align]',$posts['pagetext']);
			$posts['pagetext'] = str_replace('[left]','[align=left]',$posts['pagetext']);
			$posts['pagetext'] = str_replace('[/left]','[/align]',$posts['pagetext']);
//			$posts['pagetext'] = str_replace('[QUOTE=','[quote]',$posts['pagetext']);
//			$posts['pagetext'] = str_replace('[/QUOTE]','[/quote]',$posts['pagetext']);
			$posts['pagetext'] = str_replace('[rm]','[rm=316,241,1]',$posts['pagetext']);
			$posts['pagetext'] = str_replace('[RM]','[rm=316,241,1]',$posts['pagetext']);
			$posts['pagetext'] = str_replace('[hide]','[post]',$posts['pagetext']);
			$posts['pagetext'] = str_replace('[/hide]','[/post]',$posts['pagetext']);

			$posts_test = convert($posts['pagetext']);
			$posts['pagetext'] = safeconvert($posts['pagetext']);
			$posts_test == $posts['pagetext']?$ifconvert=1:$ifconvert=2;
            $iftopic = $posts['parentid'] ? 0 : 1;
            if(!$speed){
			if($iftopic){
				$DDB->query("REPLACE INTO {$pw_prefix}tmsgs(tid,userip,content,ifconvert,aid,ifsign) VALUES('$posts[threadid]','$posts[ipaddress]','".($posts['pagetext'])."','$ifconvert','$posts[attach]','1')");
			} else{
				$DDB->query("REPLACE INTO {$pw_prefix}posts(pid,fid,tid,aid,author,authorid,icon,postdate,subject,userip,ifconvert,ifcheck,content,ifsign) VALUES('$posts[postid]','$posts[forumid]','$posts[threadid]','$posts[attach]','".addslashes($posts['username'])."','$posts[userid]','$posts[icon]','$posts[dateline]','".addslashes($posts['title'])."','$posts[ipaddress]','$ifconvert',1,'".($posts['pagetext'])."','1')");
			}
            }

            if($speed){

            if($iftopic){
			$tmsgssql[] = 	"('$posts[threadid]','$posts[ipaddress]','".($posts['pagetext'])."','$ifconvert','$posts[attach]','1')";
			} else{
			$postssql[] = "('$posts[postid]','$posts[forumid]','$posts[threadid]','$posts[attach]','".addslashes($posts['username'])."','$posts[userid]','$posts[icon]','$posts[dateline]','".addslashes($posts['title'])."','$posts[ipaddress]','$ifconvert',1,'".($posts['pagetext'])."','1')";
			}
            }
            $s_c++;
	}
    if($speed){

      if($tmsgssql){
        $DDB->query("REPLACE INTO {$pw_prefix}tmsgs(tid,userip,content,ifconvert,aid,ifsign) VALUES ". implode(",",$tmsgssql));
       }
       if($postssql){
        $DDB->query("REPLACE INTO {$pw_prefix}posts(pid,fid,tid,aid,author,authorid,icon,postdate,subject,userip,ifconvert,ifcheck,content,ifsign) VALUES ".implode(",",$postssql));
       }
    }
  	$maxid = $SDB->get_value("SELECT max(postid) FROM {$source_prefix}post");
    echo '最大id',$maxid,'<br>','最后id',$lastid;
    if($maxid > $lastid)
    {
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&table='.$table.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
		report_log();
		newURL($step);
	}

}elseif ($step == 7) {//附件
    $attachfile = $SDB->get_value("SELECT value FROM {$source_prefix}setting WHERE varname='attachfile'");
    if(!$start)
    {
		$start = 0;
		$DDB->query("TRUNCATE TABLE {$pw_prefix}attachs");
	}
    //echo ("SELECT a.*,p.threadid ,p.parentid,t.forumid FROM attachment a LEFT JOIN post p ON a.postid = p.postid LEFT JOIN thread t ON p.threadid=t.threadid WHERE a.attachmentid > $start LIMIT $percount");
	$query= $SDB->query("SELECT a.*,p.threadid ,p.parentid,t.forumid FROM {$source_prefix}attachment a LEFT JOIN {$source_prefix}post p ON a.postid = p.postid LEFT JOIN {$source_prefix}thread t ON p.threadid=t.threadid WHERE a.attachmentid > $start LIMIT $percount");
    while($attachs = $SDB->fetch_array($query))
	{
    $lastid = $attachs['attachmentid'];
    if (!$attachs['attachmentid'])
		{
			$f_c++;
			errors_log($attachs['attachmentid']."\t".$attachs['threadid']."\t".$attachs['forumid']);
			continue;
		}
	$aid = $attachs['attachmentid'];
	$name	= $attachs['filename'];
	$size	= $attachs['filesize']/1024;
	$hits	= $attachs['counter'];
    $type   = strtolower($attachs['extension']);
	$attachurl	= $attachs['forumid'].'_'.$aid.'.'.$type;
	$fid		= $attachs['forumid'];
	$userid		= $attachs['userid'];
	if(strpos('gif|jpg|jpeg|png|bmp',$type)!==false){
	    $type	  = 'img';
		$ifupload = '1';
		} elseif(strpos('zip|rar',$type)!==false){
		    $type	  = 'zip';
			$ifupload = '3';
		} elseif($type=='txt'){
			$type	  = 'txt';
			$ifupload = '2';
		} else{
			$type = 'zip';
			$ifupload = '3';
	}
	$user_id = '';
	if(strlen($userid)>1){
	for($i = 0;$i<strlen($userid);$i++){
		$user_id .= $userid[$i].'/';
	}
	} else{
		$user_id = $userid.'/';
	}
	$attachpath  = "$path/$attachdir/".$user_id.$aid . '.attach';
	if (!$attachfile) {
	    $savedir = '';
		switch($db_attachdir) {
		case 0: $savedir = '';break;
		case 1: $savedir = 'Fid_'.$fid; break;
		case 2: $savedir = 'Type_'.$type; break;
		case 3: $savedir = 'Mon_'.date('ym',$postdate); break;
		case 4: $savedir = 'Day_'.date('ymd',$postdate); break;
		default: $savedir = '';break;
	}
	    $attdir = $pwattachpath.'/'.$savedir;
	    if(!is_dir($attdir)) {
		    @mkdir($attdir, 0777);
		    @fclose(@fopen($attdir.'/index.html', 'w'));
	       }
	    $attachurl = $savedir ? $savedir.'/'.$attachurl : $attachurl;
	    writeover("$pwattachpath/$attachurl",$attachs['filedata']);
	}else{
		$user_id = '';
		if(strlen($userid)>1){
		for($i = 0;$i<strlen($userid);$i++){
			$user_id .= $userid[$i].'/';
		}
		} else{
			$user_id = $userid.'/';
		}
			$attachurl  = "vbb/".$user_id.$aid.'.attach';
	}
    $pid = $attachs['parentid'] ? $attachs['postid'] : 0;

    if($pid == '0' && $attachs['threadid']){
    //echo ("UPDATE {$pw_prefix}threads set ifupload = $ifupload WHERE tid = $attachs[threadid]");
    $DDB->query("UPDATE {$pw_prefix}threads set ifupload = $ifupload WHERE tid = ".$attachs['threadid']);
    }
	$DDB->query("REPLACE INTO {$pw_prefix}attachs(aid,fid,uid,tid,pid,name,type,size,attachurl,hits,needrvrc,uploadtime) VALUES('$aid','$attachs[forumid]','$attachs[userid]','$attachs[threadid]','$pid','".addslashes($name)."','$type','$size','$attachurl','$hits','$needrvrc','$attachs[dateline]')");
    $s_c ++;
	}
    $maxid = $SDB->get_value("SELECT max(attachmentid) FROM {$source_prefix}attachment");
    echo '最大id',$maxid,'<br>','最后id',$lastid;
    if($maxid > $lastid)
    {
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
		report_log();
		newURL($step);
	}
}elseif ($step == 8) {
    //短消息
	if(!$start) {

		$start = 0;
		$DDB->update("TRUNCATE TABLE {$pw_prefix}ms_messages");
        $DDB->update("TRUNCATE TABLE {$pw_prefix}ms_relations");
        $DDB->update("TRUNCATE TABLE {$pw_prefix}ms_replies");

	}

	$query	=$SDB->query("SELECT * FROM {$source_prefix}pmtext WHERE pmtextid >$start LIMIT $percount");

	while ($pms	= $SDB->fetch_array($query)) {
        $lastid = $pms['pmtextid'];
         if(!$pms['pmtextid']){
            $f_c++;
            errors_log($pms['pmtextid']."\t".$pms['fromuserid']."\t".$pms['fromusername']);
		    continue;
         }
		if ($pms['title'] && $pms['fromusername']) {
			$pmid		= '';
            $extra       =  unserialize($pms['touserarray']);
            if($extra['cc']){
             $extra = $extra['cc'];
            }elseif($extra['bcc']){
             $extra = $extra['bcc'];
            }else{
              var_dump($extra);exit;
            }
           foreach($extra as $k => $v){
            $touid = $k;
            $extra = serialize(array(0=>$v));
           }
			$msgfrom	= addslashes($pms['fromusername']);
			$msgfromid	= addslashes($pms['fromuserid']);
			$new		= $pms['messageread'] > 0 ? 0 : 1;
			$subject	= addslashes(substrs(@strip_tags(trim($pms['title'])),70));
			$dateline	= $pms['dateline'];
			$message	= addslashes(@strip_tags(trim($pms['message'])));

            $DDB->query("REPLACE INTO {$pw_prefix}ms_messages (mid,create_uid,create_username,title,created_time,modified_time,content,extra,expand) VALUES ('$pms[pmtextid]','$msgfromid','".$msgfrom."','".addslashes($subject)."','$dateline','$dateline','$message','".addslashes($extra)."','".serialize(array('categoryid'=>1,'typeid'=>100))."')");
            $DDB->update("INSERT INTO {$pw_prefix}ms_relations (uid,mid,categoryid,typeid,status,isown,created_time,modified_time) VALUES ('$msgfromid','$pms[pmtextid]','1','100','0','1','$dateline','$dateline')");
            if($touid && $pms[pmtextid]){
              $DDB->update("INSERT INTO {$pw_prefix}ms_relations (uid,mid,categoryid,typeid,status,isown,created_time,modified_time) VALUES ('$touid','$pms[pmtextid]','1','100','0','0','$dateline','$dateline')");
            }
            $DDB->update("INSERT INTO {$pw_prefix}ms_replies (parentid,create_uid 	,create_username,title,content,status,created_time,modified_time) VALUES ('$pms[pmtextid]','$msgfromid','$msgfrom','$msgfrom','$message','0','$dateline','$dateline')");
		}
		$s_c++;
	}
    $maxid = $SDB->get_value("SELECT max(pmtextid) FROM {$source_prefix}pmtext");
     echo '最大id',$maxid,'<br>','最后id',$lastid;
    if($maxid > $lastid)
    {
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
		report_log();
		newURL($step);
	}
}elseif ($step == 9) {
    //友情链接
	$DDB->query("TRUNCATE TABLE {$pw_prefix}sharelinks");
	$DDB->query("REPLACE INTO {$pw_prefix}sharelinks (sid,threadorder,name,url,descrip,logo,ifcheck) VALUES('1','0','PHPwind Board','http://www.phpwind.net','PHPwind官方论坛','logo.gif','1')");
	$query  = $SDB->query("SELECT * FROM {$source_prefix}sitelink") ;
	while($link = $SDB->fetch_array($query)) {
		if ($link['title']!="Discuz! Board")
		{
			$DDB->query("INSERT INTO {$pw_prefix}sharelinks (threadorder,name,url,descrip,logo,ifcheck) VALUES('$link[displayorder]','".($link['title'])."', '".($link['url'])."','".($link['description'])."','".($link['logourl'])."','1')");
		}
		$s_c++;
	}
	report_log();
	newURL($step);
}elseif ($step == 10) {
    ////公告
	$DDB->query("TRUNCATE TABLE {$pw_prefix}announce");
	$message = array();
	$message = $SDB->query("SELECT a.*,u.username FROM {$source_prefix}announcement a LEFT JOIN {$source_prefix}user u USING(userid)");
	while($announce=$SDB->fetch_array($message)){
		$url = '';
        $ifopen = $announce[enddate] > $timestamp ? 1 : 0;
		$DDB->query("REPLACE INTO {$pw_prefix}announce(aid,fid,vieworder,author,startdate,url,enddate,subject,content,ifopen) VALUES ('$announce[announcementid]','-1','0','".($announce['username'])."','$announce[startdate]','".($url)."','$announce[enddate]','".($announce['title'])."','".($announce['pagetext'])."','$ifopen')");
	}
	report_log();
	newURL($step);
}elseif ($step == 11) {
    //收藏夹
	if(!$start) {
		$start = 0;
		$DDB->query("TRUNCATE TABLE {$pw_prefix}collection");
	}
	$query  = $SDB->query("SELECT s.*,u.username,p.userid as uid,p.dateline,p.username as name,p.title FROM {$source_prefix}subscribethread s LEFT JOIN {$source_prefix}user u ON s.userid = u.userid LEFT JOIN {$source_prefix}post p ON s.threadid = p.threadid WHERE subscribethreadid > $start AND p.parentid = 0 LIMIT $percount") ;
	while($favors = $SDB->fetch_array($query)) {
		$type = 'postfavor';
        $typeid = $favors['threadid'];
		$uid = $favors['userid'];
        $username = $favors['username'];
        $title = addslashes($favors['title']);
        $name =  addslashes($favors['name']);
        $content = Array(
          'uid' => $favors['uid'],
          'lastpost' => $favors['dateline'],
          'link' => "read.php?tid=$typeid",
          'postfavor' => Array(
          'subject' => $title
           ),
          'username' => $name
         );

        $content = serialize($content);
        $postdate = $favors['dateline'];
		$DDB->query("INSERT INTO {$pw_prefix}collection (type,typeid,uid,username,postdate,content,ifhidden,c_num,ctid)VALUES('postfavor','$typeid','$uid','$name','$postdate','$content','0','0','-1')");
		$s_c++;
	}
   $maxid = $SDB->get_value("SELECT max(subscribethreadid) FROM {$source_prefix}subscribethread");
    echo '最大id',$maxid,'<br>','最后id',$lastid;
    if($maxid > $lastid)
    {
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	} else {
     report_log();
	 newURL($step);
     exit;
	}
}elseif ($step == '12')
{
	//群组分类
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}cnstyles");
	}

	$query = $SDB->query("SELECT * FROM {$source_prefix}socialgroupcategory");
	while ($rt = $SDB->fetch_array($query))
	{
		$cnstylesdb[] = array($rt['socialgroupcategoryid'],addslashes($rt['title']),1,0,1);
		$s_c ++;
	}
	$cnstylesdb && $DDB->update("REPLACE INTO {$pw_prefix}cnstyles (id,cname,ifopen,upid,vieworder) VALUES ".pwSqlMulti($cnstylesdb));
	report_log();
	newURL($step);
}
elseif ($step == '13')
{

	//群组
	if(!$start){
		$DDB->query("TRUNCATE TABLE {$pw_prefix}colonys");
	}
    $colonys_fid = array();

	$query = $SDB->query("SELECT s.*,u.username FROM {$source_prefix}socialgroup s LEFT JOIN {$source_prefix}user u ON s.creatoruserid = u.userid");
	while ($rt = $SDB->fetch_array($query)) {

		//加入方式 jointype      -1:关闭,1:邀请加入,2:审核加入,0:自由加入
		//ifcheck 2 完全开放 1 审核加入 0关闭
		//是否公开 gviewperm     0:内部,1:全站

		$id			=	$rt['groupid'];
		$classid	=	'';
		$cname		=	$rt['name'];
		$admin		=	$rt['username'];
		$members	=	$rt['members'];
		$ifcheck	=	$rt['type'] == 'public' ? 2 : 1; //加入权限
		$ifopen		=	$rt['type'] == 'public' ? 1 : 0; //群组公开权限
		$cnimg		=	'';
		$createtime =	$rt['dateline'];
		$annouce	=	'';
		$albumnum	=	0;
		$annoucesee =	0;
        $photonum = $rt['picturecount'];
		$descrip	=	$rt['description'];
        $commonlevel = $rt['socialgroupcategoryid'];
		$colonysdb[] = array($id,$classid,$cname,$admin,$members,$ifcheck,$ifopen,$cnimg,$createtime,$annouce,$albumnum,$annoucesee,$descrip,$classid,2,1,$rt['discussions'],$rt['article'],$photonum,$commonlevel);

		$s_c ++;
	}
	$colonysdb && $DDB->update("REPLACE INTO {$pw_prefix}colonys (id,classid,cname,admin,members,ifcheck,ifopen,cnimg,createtime,annouce,albumnum,annoucesee,descrip,styleid,viewtype,ifshow,tnum,pnum,photonum,commonlevel) VALUES ".pwSqlMulti($colonysdb));
	report_log();
	newURL($step);
}
elseif ($step == '14')
{

	//群组成员
	if(!$start){
		$DDB->query("TRUNCATE TABLE {$pw_prefix}cmembers");
	}

	$query = $SDB->query("SELECT s.*,u.username FROM {$source_prefix}socialgroupmember s LEFT JOIN {$source_prefix}user u ON s.userid = u.userid LIMIT $start, $percount");
	$goon = 0;
	while ($rt = $SDB->fetch_array($query)) {
		$goon++;
		$uid	  = $rt['userid'];
		$username =	addslashes($rt['username']);
        $ifadmin = $DDB->get_value("SELECT count(*) FROM {$pw_prefix}colonys WHERE `admin` LIKE '$username' ");
		$ifadmin  = $ifadmin ? 1 : 0;
		$colonyid = $rt['groupid'];
        $addtime =  $rt['dateline'];
		$cmembersdb[] = array($uid,$username,$ifadmin,$colonyid,$addtime);
		$s_c ++;
	}
	$cmembersdb && $DDB->update("REPLACE INTO {$pw_prefix}cmembers (uid,username,ifadmin,colonyid,addtime) VALUES ".pwSqlMulti($cmembersdb));


	if ($goon == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else{
		$maxid = $SDB->get_value("SELECT max(userid) FROM socialgroupmember");
		report_log();
		newURL($step);
	}
}
elseif ($step == '15')
{
    report_log();
	newURL($step);
    //群组讨论区帖子更新
    require_once(S_P.'tmp_colonys_fid.php');
	$query = $SDB->query("SELECT t.tid,t.fid,t.dateline,t.lastpost,t.displayorder FROM {$source_prefix}forum_thread t
			WHERE t.isgroup = 1 AND t.tid > $start
			ORDER BY t.tid LIMIT $percount");
            /*
	$query = $SDB->query("SELECT t.tid,t.fid,t.postdate,t.lastpost,t.topped FROM {$source_prefix}forum_thread t force index(PRIMARY) INNER JOIN {$source_prefix}forum_post p
			USING (tid)
			WHERE p.first = 1 AND t.isgroup = 1 AND t.tid > $start
			ORDER BY tid LIMIT $percount");
*/
	while ($rt = $SDB->fetch_array($query))
	{
        $lastid = $rt['tid'];
        $newfid = $colonys_fid[$rt['fid']];
        $newfid && $DDB->update("UPDATE {$pw_prefix}threads SET fid = ".$newfid.",tpcstatus=1 WHERE tid = ".$rt['tid']);
        $newfid && $DDB->update("UPDATE {$pw_prefix}posts SET fid = ".$newfid." WHERE tid = ".$rt['tid']);
        $DDB->update("REPLACE INTO {$pw_prefix}argument (tid,cyid,postdate,lastpost,topped) VALUES (".$rt['tid'].",".$rt['fid'].",'".$rt['dateline']."','".$rt['lastpost']."','".$rt['displayorder']."')");
    }
	$maxid = $SDB->get_value("SELECT max(tid) FROM {$source_prefix}forum_thread WHERE isgroup = 1");
    echo '最大id',$maxid,'<br>','最后id',$lastid;
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
elseif ($step == '16')
{
	//圈子相册(将home/attachment目录下的图片移至到phpwind论坛的attachment/photo下)
	if(!$start){
		$DDB->query("TRUNCATE TABLE {$pw_prefix}cnalbum");
	}
	$query = $SDB->query("SELECT a.*,u.username FROM {$source_prefix}album a LEFT JOIN {$source_prefix}user u ON a.userid=u.userid LIMIT $start, $percount");
	$goon = 0;
	while ($rt = $SDB->fetch_array($query)) {
		$goon++;
		$s_c ++;
		$aid	=	$rt['albumid'];
		$aname	=	$rt['title'];
		$aintro	=	'';
		$atype	=	0;
		$private=	0;
		$ownerid=	$rt['userid'];
		$owner	=	$rt['username'];
		$photonum=	$rt['visible'];
		//$lastphoto=	"photo/".$rt['pic'];
		$lasttime=	$rt['lastpicturedate'];
		$lastpid =	'';
		$crtime	 =  $rt['createdate'];
		$cnalbumdb[] = array($aid,$aname,$aintro,$atype,$private,$ownerid,$owner,$photonum,$lasttime,$lastpid,$crtime);
	}
	$cnalbumdb && $DDB->update("REPLACE INTO {$pw_prefix}cnalbum (aid,aname,aintro,atype,private,ownerid,owner,photonum,lasttime,lastpid,crtime) VALUES ".pwSqlMulti($cnalbumdb));
	if ($goon == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else{
		$maxid = $SDB->get_value("SELECT max(albumid) FROM {$source_prefix}album");
		report_log();
		newURL($step);
	}
}
elseif ($step == '17')
{
    $album_picpath = $SDB->get_value("SELECT value FROM {$source_prefix}setting WHERE varname='album_picpath'");
	//圈子相册照片
	if(!$start){
		$DDB->query("TRUNCATE TABLE {$pw_prefix}cnphoto");
	}
	$query = $SDB->query("SELECT l.*,a.*,p.* FROM {$source_prefix}albumpicture l LEFT JOIN {$source_prefix}album a ON l. albumid = a.albumid LEFT JOIN {$source_prefix}picture p ON l.pictureid = p.pictureid  WHERE l.pictureid > $start LIMIT $percount");
	while ($rt = $SDB->fetch_array($query)) {
		$pid	=	$rt['pictureid'];
        $lastid = $pid;
        if(strlen($pid) < 4){
        $temp_path = '0';
        }else{
        $temp_path = substr($pid,0,-3);
        }
        $hash = $rt['idhash'];
        $extension = $rt['extension'];
        $DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
        $path_check = "$DOCUMENT_ROOT/fourm/$album_picpath/$temp_path/$pid.picture";
        //if(file_exists($path_check)){
        $path = "photo/vbb/$album_picpath/$temp_path/$pid.picture";

       // }else{
       // $path = "photo/vbb/$album_picpath/$temp_path/{$hash}_$pid.$extension"; //缩略图地址
       // }
		$aid	=	$rt['albumid'];
		$pintro	=	$rt['caption'];
		$uploader=	getUsernameByUid($rt['userid']);
		$uptime	=	$rt['dateline'];
		$hits	=	0;
		$ifthumb=	0;
		$c_num	=	0;
		$cnphoto[] = array($pid,$aid,$pintro,$path,$uploader,$uptime,$hits,$ifthumb,$c_num);
        $s_c ++;
	}
	$cnphoto && $DDB->update("REPLACE INTO {$pw_prefix}cnphoto (pid,aid,pintro,path,uploader,uptime,hits,ifthumb,c_num) VALUES ".pwSqlMulti($cnphoto));
    $maxid = $SDB->get_value("SELECT max(pictureid) FROM {$source_prefix}albumpicture");
    echo '最大id',$maxid,'<br>','最后id',$lastid;
	if ($maxid > $lastid){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else{
		$maxid = $SDB->get_value("SELECT max(pictureid) FROM {$source_prefix}albumpicture");
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
	global $pw_prefix,$SDB,$DDB,$dest_charset;
	require_once S_P.'lang_'.$dest_charset.'.php';
	$DDB->update("TRUNCATE TABLE {$pw_prefix}usergroups");

	$DDB->update($lang['group']);//创建系统默认组
	$grelation = array(1=>3, 2=>4, 3=>5, 4=>6, 5=>6, 6=>6, 7=>2, 8=>7);//系统组GID

	$query = $SDB->query("SELECT * FROM {}usergroups WHERE type = 'member' OR type = 'special'");
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

function html2bbcode($text) {

	if(!$text) return '';
	$text = strip_tags($text, '<table><tr><td><b><strong><i><em><u><a><div><span><p><strike><blockquote><ol><ul><li><font><img><br><br/><h1><h2><h3><h4><h5><h6><script>');

	$text = preg_replace("/(?<!<br>|<br \/>|\r)(\r\n|\n|\r)/", ' ', $text);

	$pregfind = array(
		"/<script.*>.*<\/script>/siU",
		'/on(mousewheel|mouseover|click|load|onload|submit|focus|blur)="[^"]*"/i',
		"/(\r\n|\n|\r)/",
		"/<table([^>]*(width|background|background-color|bgcolor)[^>]*)>/siUe",
		"/<table.*>/siU",
		"/<tr.*>/siU",
		"/<td>/i",
		"/<td(.+)>/siUe",
		"/<\/td>/i",
		"/<\/tr>/i",
		"/<\/table>/i",
		'/<h([0-9]+)[^>]*>(.*)<\/h\\1>/siU',
		"/<img[^>]+smilieid=\"(\d+)\".*>/esiU",
		"/<img([^>]*src[^>]*)>/eiU",
		"/<a\s+?name=.+?\".\">(.+?)<\/a>/is",
		"/<br.*>/siU",
		"/<span\s+?style=\"float:\s+(left|right);\">(.+?)<\/span>/is",
	);
	$pregreplace = array(
		'',
		'',
		'',
		"tabletag('\\1')",
		'[table]',
		'[tr]',
		'[td]',
		"tdtag('\\1')",
		'[/td]',
		'[/tr]',
		'[/table]',
		"[size=\\1]\\2[/size]\n\n",
		"smileycode('\\1')",
		"imgtag('\\1')",
		'\1',
		"\n",
		"[float=\\1]\\2[/float]",
	);
	$text = preg_replace($pregfind, $pregreplace, $text);

	$text = recursion('b', $text, 'simpletag', 'b');
	$text = recursion('strong', $text, 'simpletag', 'b');
	$text = recursion('i', $text, 'simpletag', 'i');
	$text = recursion('em', $text, 'simpletag', 'i');
	$text = recursion('u', $text, 'simpletag', 'u');
	$text = recursion('a', $text, 'atag');
	$text = recursion('font', $text, 'fonttag');
	$text = recursion('blockquote', $text, 'simpletag', 'indent');
	$text = recursion('ol', $text, 'listtag');
	$text = recursion('ul', $text, 'listtag');
	$text = recursion('div', $text, 'divtag');
	$text = recursion('span', $text, 'spantag');
	$text = recursion('p', $text, 'ptag');

	$pregfind = array("/(?<!\r|\n|^)\[(\/list|list|\*)\]/", "/<li>(.*)((?=<li>)|<\/li>)/iU", "/<p.*>/iU", "/<p><\/p>/i", "/(<a>|<\/a>|<\/li>)/is", "/<\/?(A|LI|FONT|DIV|SPAN)>/siU", "/\[url[^\]]*\]\[\/url\]/i", "/\[url=javascript:[^\]]*\](.+?)\[\/url\]/is");
	$pregreplace = array("\n[\\1]", "\\1\n", "\n", '', '', '', '', "\\1");
	$text = preg_replace($pregfind, $pregreplace, $text);

	$strfind = array('&nbsp;', '&lt;', '&gt;', '&amp;');
	$strreplace = array(' ', '<', '>', '&');
	$text = str_replace($strfind,$strreplace,$text);

	return trim($text);
}

function safeconvert($msg){
	$msg = str_replace('&amp;','&',$msg);
	$msg = str_replace('&nbsp;',' ',$msg);
	if(strpos($msg,"&ensp;")===false){
		$msg = str_replace('&','&amp;',$msg);/*对技术论坛有效*/
	}
	$msg = str_replace('"','&quot;',$msg);
	$msg = str_replace("'",'&#39',$msg);
	$msg = str_replace("\t","   &nbsp;  &nbsp;",$msg);
	$msg = str_replace("<","&lt;",$msg);
	$msg = str_replace(">","&gt;",$msg);
	$msg = str_replace("\r","",$msg);
	$msg = str_replace("   "," &nbsp; ",$msg);#编辑格式时比较有效
    $msg = addslashes($msg);
	return $msg;
}
/**
 * 获取目录路径
 *
 * @param string $path 文件路径
 * @return string
 */
function getdirname($path = null) {
	if (!empty($path)) {
		if (strpos($path, '\\') !== false) {
			return substr($path, 0, strrpos($path, '\\')) . '/';
		} elseif (strpos($path, '/') !== false) {
			return substr($path, 0, strrpos($path, '/')) . '/';
		}
	}
	return './';
}
?>
