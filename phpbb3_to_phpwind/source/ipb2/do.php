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
	//会员
	if (!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}members");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}memberdata");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}administrators");
		//更改数据库结构
		$addfields = TRUE;
		$query = $DDB->query("SHOW COLUMNS FROM {$pw_prefix}members");
		while ($mc = $DDB->fetch_array($query))
		{
			if (strpos($mc['Field'], 'ipbsalt') !== FALSE)
			{
				$addfields = FALSE;
				break;
			}
		}
		$addfields && $DDB->update("ALTER TABLE {$pw_prefix}members ADD ipbsalt varchar(5) NOT NULL DEFAULT ''");
		$DDB->update("ALTER TABLE {$pw_prefix}members CHANGE username username VARCHAR( 255 ) NOT NULL  DEFAULT ''");
	}
	$uid = $SDB->get_one("SELECT id FROM {$source_prefix}members LIMIT $start,1");
	if (!$uid)
	{
		report_log();
		newURL($step);
	}
	$query = $SDB->query("SELECT m.id,m.name,m.mgroup,m.email,m.joined,m.ip_address,m.posts,m.allow_admin_mails,m.time_offset,m.hide_email,m.bday_day,m.bday_month,m.bday_year,m.new_msg,m.last_visit,m.last_post,
							mc.converge_pass_hash,mc.converge_pass_salt,
							me.icq_number,me.website,me.yahoo,me.interests,me.msnname,me.location,me.signature,me.avatar_location,me.avatar_size,me.avatar_type,
							pp.pp_bio_content,pp.pp_gender
							FROM {$source_prefix}members m LEFT JOIN {$source_prefix}members_converge mc ON m.id = mc.converge_id LEFT JOIN {$source_prefix}member_extra me ON m.id = me.id LEFT JOIN {$source_prefix}profile_portal pp ON m.id = pp.pp_member_id WHERE m.id >= ".$uid['id']." LIMIT $percount");
	$insertadmin = '';
	while ($m = $SDB->fetch_array($query))
	{
		$m['name'] = addslashes($m['name']);
		if (!$m['name'] || htmlspecialchars($m['name']) != $m['name'] || CK_U($m['name']) || $m['mgroup'] == 2)
		{
			$f_c++;
			errors_log($m['name']."\t".$m['mgroup']);
			continue;
		}
		switch ($m['mgroup'])
		{
			case '1':
				$groupid = '7';
				break;
			case '5':
				$groupid = '6';
				break;
			case '4':
			case '6'://管理员
				$groupid = '3';
				$insertadmin .= "(".$m['id'].", '".$m['name']."', 3),";
				break;
			default :
				$groupid = '-1';
				break;
		}
		$m['avatar_size'] && list($width, $height) = explode('x', $m['avatar_size']);
		if ($m['avatar_type'] == 'upload')
		{
			$userface = $m['avatar_location'].'|3|'.$width.'|'.$height;
		}
		elseif ($m['avatar_type'] == 'url')
		{
			$userface = $m['avatar_location'].'|2|'.$width.'|'.$height;
		}
		elseif ($m['avatar_type'] == 'local')
		{
			$userface = $m['avatar_location'].'|1||';
		}
		else
		{
			$userface = '';
		}
		switch ($m['pp_gender'])
		{
			case 'male':
				$gender = 1;
				break;
			case 'female':
				$gender = 2;
				break;
			default:
				$gender = 0;
		}
		$m['signature'] = ipb_ubb($m['signature']);
		$signchange  = ($m['signature'] == convert($m['signature'])) ? 1 : 2;
		$userstatus=($signchange-1)*256+128+((int)$m['hide_email']^1)*64+4;//用户位状态设置
		$bday = (int)$m['bday_day'].'-'.(int)$m['bday_month'].'-'.(int)$m['bday_year'];
		$DDB->update("INSERT INTO {$pw_prefix}members (uid,username,password,email,groupid,icon,gender,regdate,signature,introduce,icq,msn,yahoo,site,location,bday,yz,timedf,userstatus,newpm,ipbsalt) VALUES (".$m['id'].",'".$m['name']."','".$m['converge_pass_hash']."','".addslashes($m['email'])."',".$groupid.",'".addslashes($userface)."',".$gender.",".$m['joined'].",'".addslashes($m['signature'])."','".addslashes($m['pp_bio_content'])."','".$m['icq_number']."','".addslashes($m['msnname'])."','".addslashes($m['yahoo'])."','".addslashes($m['website'])."','".addslashes($m['location'])."','".$bday."',1,".(int)$m['time_offset'].",$userstatus,".($m['new_msg'] ? 1 : 0).",'".$m['converge_pass_salt']."')");
		$DDB->update("INSERT INTO {$pw_prefix}memberdata (uid,postnum,rvrc,money,lastvisit,lastpost) VALUES (".$m['id'].",".$m['posts'].",0,10,'".$m['last_visit']."','".$m['last_post']."')");
		$s_c++;
	}
	$insertadmin && $DDB->update("INSERT INTO {$pw_prefix}administrators (uid,username,groupid) VALUES ".substr($insertadmin, 0, -1));
	refreshto($cpage.'&step=1&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
}
elseif ($step == '2')
{
	//板块
	$DDB->query("TRUNCATE TABLE {$pw_prefix}forums");
	$DDB->query("TRUNCATE TABLE {$pw_prefix}forumdata");

	$query = $SDB->query("SELECT * FROM {$source_prefix}forums");
	$catedb = '';
	while($f = $SDB->fetch_array($query))
	{
		$catedb[$f['id']] = $f;
	}
	foreach ($catedb as $k => $v)
	{
		$f_tmp = parent_upfid($v['id'],'parent_id',-1);
		parent_fid($v['id'], $f_tmp[2]);
		$ifchildid = (int)parent_ifchildid($v['id'],'parent_id');
		$DDB->update("INSERT INTO {$pw_prefix}forums (fid,fup,ifsub,childid,type,name,descrip,vieworder,allowtype) VALUES (".$v['id'].",".$f_tmp[1].",".($f_tmp[0] == 'sub' ? 1 : 0).",".$ifchildid.",'".$f_tmp[0]."','".addslashes($v['name'])."','".addslashes($v['description'])."',".(int)$v['position'].",32)");
		$DDB->update("INSERT INTO {$pw_prefix}forumdata (fid,topic,article) VALUES (".$v['id'].",".(int)$v['topics'].",".(int)$v['posts'].")");
		$s_c++;
	}
	$query = $SDB->query("SELECT forum_id,member_name,member_id FROM {$source_prefix}moderators");
	$forumadmin = $_parent_fid = array();
	require(S_P.'tmp_parent_fid.php');
	while($m = $SDB->fetch_array($query))
	{
		$uid = $DDB->get_one("SELECT uid,username FROM {$pw_prefix}members WHERE uid = ".$m['member_id']);
		if ($uid)
		{
			$forumadmin[$m['forum_id']] .= ','.$uid['username'];
			$DDB->update("UPDATE {$pw_prefix}members SET groupid = 5 WHERE uid = ".$uid['uid']." AND groupid = -1 LIMIT 1");
			$DDB->update("INSERT INTO {$pw_prefix}administrators (uid,username,groupid) VALUES (".$uid['uid'].",'".$uid['username']."',5)");
		}
	}
	foreach ($forumadmin as $k => $v)
	{
		$fupadmin_tmp = '';
		if ($_parent_fid[$k])
		{
			foreach ($_parent_fid[$k] as $vv)
			{
				$fupadmin_tmp .= $forumadmin[$vv];
			}
			$fupadmin_tmp = ",fupadmin = '".$fupadmin_tmp."'";
		}
		$DDB->update("UPDATE {$pw_prefix}forums SET forumadmin = '".$v."' ".$fupadmin_tmp." WHERE fid = ".$k);
	}
	report_log();
	newURL($step);
}
elseif ($step == '3')
{
	//主题/回复
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}threads");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}tmsgs");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}posts");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}polls");
	}
	$tid = $SDB->get_one("SELECT tid FROM {$source_prefix}topics LIMIT $start,1");
	if (!$tid)
	{
		report_log();
		newURL($step);
	}
	$query = $SDB->query("SELECT * FROM {$source_prefix}topics WHERE tid >= ".$tid['tid']." LIMIT $percount");
	while ($t = $SDB->fetch_array($query))
	{
		$special = ($t['poll_state'] == '1') ? 1 : 0;
		if ($special)
		{
			$voteoptions = array();
			$pquery = $SDB->get_one("SELECT * FROM {$source_prefix}polls WHERE tid = ".$t['tid']);
			if ($pquery)
			{
				$choices = unserialize($pquery['choices']);
				foreach ($choices[1]['choice'] as $k => $v)
				{
					$voteoptions[] = array($v,$choices[1]['votes'][$k],array());
				}
				$DDB->update("INSERT INTO {$pw_prefix}polls (tid,voteopts,previewable,timelimit) VALUES ('".$t['tid']."','".addslashes(serialize(array('options'=>$voteoptions,'multiple'=>array($choices[1]['multi'],count($choices[1]['choice'])))))."',1,0)");
			}
			else
			{
				$f_c++;
				errors_log($t['tid']."\tpoll");
				continue;
			}
		}
		$t['title'] = addslashes($t['title']);
		$query2 = $SDB->query("SELECT * FROM {$source_prefix}posts WHERE topic_id = ".$t['tid']);
		$topic_exist = false;
		while ($bbs = $SDB->fetch_array($query2))
		{
			$bbs['post'] = ipb_ubb($bbs['post']);
			$ifconvert = (convert($bbs['post']) == $bbs['post']) ? 1 : 2;
			if ($bbs['new_topic'])
			{
				if ($topic_exist)
				{
					$f_c++;
					errors_log($t['tid']."\texists");
					continue;
				}
				$topic_exist = true;
				$DDB->update("INSERT INTO {$pw_prefix}tmsgs (tid,aid,userip,ifsign,buy,ifconvert,content) VALUES (".$t['tid'].",'','".addslashes($bbs['ip_address'])."',".(int)$bbs['use_sig'].",'','".$ifconvert."','".addslashes($bbs['post'])."')");
				$DDB->update("INSERT INTO {$pw_prefix}threads (tid,fid,icon,author,authorid,subject,ifcheck,postdate,lastpost,lastposter,hits,replies,topped,locked,special) VALUES (".$t['tid'].",".$t['forum_id'].",'".$bbs['icon_id']."','".addslashes($bbs['author_name'])."',".$bbs['author_id'].",'".$t['title']."',".$t['approved'].",'".$bbs['post_date']."','".$t['last_post']."','".addslashes($t['last_poster_name'])."',".$t['views'].",".$t['posts'].",".(int)$t['pinned'].",".($t['state'] == 'open' ? 0 : 1).",".$special.")");
			}
			else
			{
				$DDB->update("INSERT INTO {$pw_prefix}posts (pid,fid,tid,aid,author,authorid,icon,postdate,userip,ifsign,buy,ifconvert,ifcheck,content) VALUES (".$bbs['pid'].",".$t['forum_id'].",".$t['tid'].",'','".addslashes($bbs['author_name'])."','".$bbs['author_id']."','".$bbs['icon_id']."','".$bbs['post_date']."','".addslashes($bbs['ip_address'])."','".$bbs['use_sig']."','','".$ifconvert."',1,'".addslashes($bbs['post'])."')");
			}
		}
		$s_c++;
	}
	refreshto($cpage.'&step=3&start='.$end.'&s_c='.$s_c.'&f_c='.$f_c);
}
elseif ($step == '4')
{
	//附件
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}attachs");
	}
	$aid = $SDB->get_one("SELECT attach_id FROM {$source_prefix}attachments LIMIT $start,1");
	if (!$aid)
	{
		report_log();
		newURL($step);
	}
	$query = $SDB->query("SELECT * FROM {$source_prefix}attachments WHERE attach_id >= ".$aid['attach_id']." LIMIT $percount");
	while ($a = $SDB->fetch_array($query))
	{
		$attach_location = $a['attach_is_image'];
		if (!$a['attach_is_image'])
		{
			if (strpos($a['attach_location'],'..') === FALSE)
			{
				$f_c++;
				errors_log($a['attach_id']."\t".$a['attach_location']);
				continue;
			}
			$attach_location = R_P.'uploads/'.substr($a['attach_location'],0,strrpos($a['attach_location'],'.')).'.'.$a['attach_ext'];
			if (!@rename(R_P.'uploads/'.$a['attach_location'], $attach_location))
			{
				$f_c++;
				errors_log($a['attach_id']."\trename failed");
				continue;
			}
		}
		$t_tmp = $SDB->get_one("SELECT pid,topic_id,new_topic FROM {$source_prefix}posts WHERE pid = ".$a['attach_rel_id']);
		$old_att = $DDB->get_one($t_tmp['new_topic'] ? "SELECT tm.aid, t.fid FROM {$pw_prefix}threads t LEFT JOIN {$pw_prefix}tmsgs tm ON t.tid = tm.tid WHERE t.tid = ".(int)$t_tmp['topic_id'] : "SELECT aid,fid FROM {$pw_prefix}posts WHERE pid = ".$a['attach_rel_id']);
		if (!$t_tmp || !$old_att)
		{
			$f_c++;
			continue;
		}
		if ($a['attach_is_image'])
		{
			$atype = 'img';
		}
		elseif ($a['attach_ext'] == 'txt')
		{
			$atype = 'txt';
		}
		else
		{
			$atype = 'zip';
		}
		$a['attach_filesize'] = ceil($a['attach_filesize']/1024);
		$attachs = $old_att['aid'] ? unserialize($old_att['aid']) : array();
		$attachs[$a['attach_id']] = array(
				'aid'       => $a['attach_id'],
				'name'      => $a['attach_file'],
				'type'      => $atype,
				'attachurl' => $attach_location,
				'needrvrc'  => 0,
				'size'      => $a['attach_filesize'],
				'hits'      => $a['attach_hits'],
				'desc'		=> '',
				'ifthumb'	=> 0
		);
		$DDB->update("INSERT INTO {$pw_prefix}attachs (aid,fid,uid,tid,pid,name,type,size,attachurl,hits,uploadtime) VALUES (".$a['attach_id'].",".$old_att['fid'].",".$a['attach_member_id'].",".$t_tmp['topic_id'].",".$t_tmp['pid'].",'".addslashes($a['attach_file'])."','".$atype."',".$a['attach_filesize'].",'".addslashes($attach_location)."',".$a['attach_hits'].",".$a['attach_date'].")");
		$DDB->update("UPDATE {$pw_prefix}".($t_tmp['new_topic'] ? "tmsgs SET aid = '".addslashes(serialize($attachs))."' WHERE tid = ".$t_tmp['topic_id'] : "posts SET aid = '".addslashes(serialize($attachs))."' WHERE pid = ".$a['attach_rel_id']));
		$s_c++;
	}
	refreshto($cpage.'&step=4&start='.$end.'&s_c='.$s_c.'&f_c='.$f_c);
}
elseif ($step == '5')
{
	//短信
	if(!$start)
	{
		$DDB->update("TRUNCATE TABLE {$pw_prefix}msg");
		$DDB->update("TRUNCATE TABLE {$pw_prefix}msgc");
		$DDB->update("TRUNCATE TABLE {$pw_prefix}msglog");
	}
	$mt_id = $SDB->get_one("SELECT mt_id FROM {$source_prefix}message_topics LIMIT $start,1");
	if (!$mt_id)
	{
		report_log();
		newURL($step);
	}
	$query = $SDB->query("SELECT mt.*,mx.msg_post FROM {$source_prefix}message_topics mt LEFT JOIN {$source_prefix}message_text mx ON mt.mt_msg_id = mx.msg_id WHERE mt_id >= $start LIMIT $percount");
	while($m = $SDB->fetch_array($query))
	{
		switch ($m['mt_vid_folder'])
		{
			case 'in':
				$username = $DDB->get_one("SELECT username FROM {$pw_prefix}members WHERE uid = ".$m['mt_from_id']);
				if (!$username)
				{
					$f_c++;
					errors_log($m['mt_from_id']."\tin");
					continue;
				}
				$type = 'rebox';
				break;
			case 'sent':
				$username = $DDB->get_one("SELECT username FROM {$pw_prefix}members WHERE uid = ".$m['mt_to_id']);
				if (!$username)
				{
					$f_c++;
					errors_log($m['mt_from_id']."\tsent");
					continue;
				}
				$type = 'sebox';
				break;
			default:
				$f_c++;
				errors_log($m['mt_from_id']."\tunkonw");
				continue;
				break;
		}
		$DDB->update("INSERT INTO {$pw_prefix}msg (mid,touid,fromuid,username,type,ifnew,mdate) VALUES (".$m['mt_id'].",".$m['mt_to_id'].",".$m['mt_from_id'].",'".addslashes($username['username'])."','".$type."',".($m['mt_read']^1).",'".$m['mt_date']."')");
		$DDB->update("INSERT INTO {$pw_prefix}msgc (mid,title,content) VALUES (".$m['mt_id'].",'".addslashes($m['mt_title'])."','".addslashes($m['msg_post'])."')");
		if (($m['mt_to_id'] != $m['mt_from_id']) && ($type == 'rebox'))
		{
			$DDB->update("INSERT INTO {$pw_prefix}msglog (mid,uid,withuid,mdate,mtype) VALUES (".$m['mt_id'].",".$m['mt_from_id'].",".$m['mt_to_id'].",'".$m['mt_date']."','send')");
			$DDB->update("INSERT INTO {$pw_prefix}msglog (mid,uid,withuid,mdate,mtype) VALUES (".$m['mt_id'].",".$m['mt_to_id'].",".$m['mt_from_id'].",'".$m['mt_date']."','receive')");
		}
		$s_c++;
	}
	refreshto($cpage.'&step=5&start='.$end.'&s_c='.$s_c.'&f_c='.$f_c);
}
else
{
	ObHeader($basename.'?action=finish&dbtype='.$dbtype);
}

##########################

function ipb_ubb($content){
	$content = str_replace('style_emoticons/<#EMO_DIR#>','images/post/smile/style_emoticons',$content);
	$content = preg_replace(array('/\[attachment=(\d+?):[^]]*\]/i','/<!\-\-.+?\-\->/i'),array('[attachment=\\1]',''),$content);
	return $content;
}

?>