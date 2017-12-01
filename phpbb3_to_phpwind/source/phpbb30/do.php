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

if ($step == 1)
{
	//设置
	$DDB->query("TRUNCATE TABLE {$pw_prefix}wordfb");
	$query = $SDB->query("SELECT word,replacement FROM {$source_prefix}words");
	while ($b = $SDB->fetch_array($query))
	{
		$DDB->update("INSERT INTO {$pw_prefix}wordfb (word,wordreplace,type) VALUES ('".addslashes($b['word'])."','".addslashes($b['replacement'])."',1)");
		$s_c++;
	}
	report_log();
	newURL($step);
}
elseif ($step == 2)
{
	//表情
	$DDB->update("INSERT INTO {$pw_prefix}smiles (path,name,vieworder,type) VALUES ('phpbb','phpbb',1,0)");
	$typeid = $DDB->insert_id();
	$query = $SDB->query("SELECT code,smiley_url,smiley_order FROM {$source_prefix}smilies");
	$_face = array();
	while ($f = $SDB->fetch_array($query))
	{
		$DDB->update("INSERT INTO {$pw_prefix}smiles (path,vieworder,type) VALUES('".addslashes($f['smiley_url'])."',".$f['smiley_order'].",".$typeid.")");
		$_face[$f['code']] = $DDB->insert_id();
		$s_c++;
	}
	writeover(S_P.'tmp_face.php', "\$_face = ".pw_var_export($_face).";", true);
	report_log();
	newURL($step);
}
elseif ($step == 3)
{
	//用户
	if (!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}members");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}memberdata");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}administrators");
		$addfields = TRUE;
		$query = $DDB->query("SHOW COLUMNS FROM {$pw_prefix}members");
		while ($mc = $DDB->fetch_array($query))
		{
			if (strpos($mc['Field'], 'phpbbsalt') !== FALSE)
			{
				$addfields = FALSE;
				break;
			}
		}
		$addfields && $DDB->update("ALTER TABLE {$pw_prefix}members ADD phpbbsalt tinyint( 1 ) NOT NULL DEFAULT 1");
	}
	$uid = $SDB->get_one("SELECT user_id FROM {$source_prefix}users LIMIT $start,1");
	if (!$uid)
	{
		report_log();
		newURL($step);
	}
	$query = $SDB->query("SELECT * FROM {$source_prefix}users WHERE user_id >= ".$uid['user_id']." LIMIT $percount");
	$insertadmin = '';
	while ($rt = $SDB->fetch_array($query))
	{
		$rt['username'] = addslashes($rt['username']);
		if (!$rt['username'] || htmlspecialchars($rt['username']) != $rt['username'] || CK_U($rt['username']))
		{
			$f_c++;
			errors_log($rt['user_id']."\t".$rt['username']);
			continue;
		}
		if ($rt['group_id'] == 6 || $rt['group_id'] == 1)
		{
			$f_c++;
			errors_log($rt['user_id']."\t".$rt['group_id']);
			continue;
		}
		switch ($rt['group_id'])
		{
			case '5'://管理员
				$groupid = '3';
				$insertadmin .= "(".$rt['user_id'].", '".$rt['username']."', 3),";
				break;
			case '4'://总版主
				$groupid = '4';
				$insertadmin .= "(".$rt['user_id'].", '".$rt['username']."', 4),";
				break;
			default :
				$groupid = '-1';
				break;
		}
		if ($rt['user_avatar_type'] == 2)
		{
			$userface = $rt['user_avatar']."|2|".$rt['user_avatar_width']."|".$rt['user_avatar_height'];
		}
		elseif ($rt['user_avatar_type'] == 1)
		{
			$userface = $rt['user_avatar'].'|1||';
		}
		elseif ($rt['user_avatar_type'] == 3)
		{
			$userface = substr(strrchr($rt['user_avatar'],'/'),1)."|3|".$rt['user_avatar_width']."|".$rt['user_avatar_height'];
		}
		else
		{
			$userface = '';
		}
		$signchange  = ($rt['user_sig'] == convert($rt['user_sig'])) ? 1 : 2;
		$userstatus=($signchange-1)*256+128+1*64+4;//用户位状态设置
		if ($rt['user_birthday'])
		{
			list($bd, $bm, $by) = explode('-', $rt['user_birthday']);
			$bday = (int)$by.'-'.(int)$bm.'-'.(int)$bd;
		}
		else
		{
			$bday = '0000-00-00';
		}
		$DDB->update("INSERT INTO {$pw_prefix}members (uid,username,password,email,groupid,icon,regdate,signature,icq,site,location,bday,userstatus) VALUES (".$rt['user_id'].",'".$rt['username']."','".$rt['user_password']."','".addslashes($rt['user_email'])."',".$groupid.",'".addslashes($userface)."',".$rt['user_regdate'].",'".addslashes($rt['user_sig'])."','".$rt['user_icq']."','".addslashes($rt['user_website'])."','".addslashes($rt['user_from'])."','".$bday."',$userstatus)");
		$DDB->update("INSERT INTO {$pw_prefix}memberdata (uid,postnum,rvrc,money,lastvisit,thisvisit,lastpost) VALUES (".$rt['user_id'].",".$rt['user_posts'].",0,10,'".$rt['user_lastvisit']."','".$rt['user_lastvisit']."','".$rt['user_lastpost_time']."')");
		$s_c++;
	}
	$insertadmin && $DDB->update("INSERT INTO {$pw_prefix}administrators (uid,username,groupid) VALUES ".substr($insertadmin, 0, -1));
	refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
}
elseif ($step == 4)
{
	//板块
	$DDB->query("TRUNCATE TABLE {$pw_prefix}forums");
	$DDB->query("TRUNCATE TABLE {$pw_prefix}forumdata");

	$query = $SDB->query("SELECT * FROM {$source_prefix}forums");
	$catedb = array();
	while($f = $SDB->fetch_array($query))
	{
		$catedb[$f['forum_id']] = $f;
	}
	foreach($catedb as $fid => $forum)
	{
		$f_tmp = parent_upfid($forum['forum_id'],'parent_id',0);
		$childid = (int)parent_ifchildid($forum['forum_id'],'parent_id');
		$ifsub = ($f_tmp[0] == 'sub') ? 1 : 0;
		$ftype = $f_tmp[0];
		$DDB->update("INSERT INTO {$pw_prefix}forums (fid,fup,ifsub,childid,type,name,descrip,logo,password) VALUES (".(int)$forum['forum_id'].",".(int)$f_tmp[1].",".$ifsub.",$childid,'".$ftype."','".addslashes($forum['forum_name'])."','".addslashes($forum['forum_desc'])."','".addslashes($forum['forum_image'])."','".addslashes($forum['forum_password'])."')");
		$DDB->update("INSERT INTO {$pw_prefix}forumdata (fid,topic,article) VALUES (".(int)$forum['forum_id'].",'".$forum['forum_topics']."','".$forum['forum_posts']."')");
		$s_c++;
	}
	report_log();
	newURL($step);
}
elseif ($step == 5)
{
	//主题
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}threads");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}tmsgs");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}posts");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}polls");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}attachs");
	}
	require_once S_P.'tmp_face.php';
	$topicid = $SDB->get_one("SELECT topic_id FROM {$source_prefix}topics LIMIT $start,1");
	if (!$topicid)
	{
		report_log();
		newURL($step);
	}
	$query = $SDB->query("SELECT * FROM {$source_prefix}topics WHERE topic_id >= ".$topicid['topic_id']." LIMIT $percount");
	while ($t = $SDB->fetch_array($query))
	{
		$attdata = array();
		$istop = ($t['topic_type'] == '1') ? 1 : 0;
		if ($t['poll_title'])
		{
			$special = 1;
			$voteoptions = array();
			$vquery1 = $SDB->query("SELECT * FROM {$source_prefix}poll_options WHERE topic_id = ".$t['topic_id']);
			while ($v1 = $SDB->fetch_array($vquery1))
			{
				$voteoptions[][0] = $v1['poll_option_text'];
				$voteoptions[][1] = $v1['poll_option_total'];
				$vquery2 = $SDB->query("SELECT u.username FROM {$source_prefix}poll_votes v LEFT JOIN {$source_prefix}users u ON v.vote_user_id = u.user_id WHERE v.topic_id = ".$t['topic_id']." AND v.poll_option_id = ".$v1['poll_option_id']);
				while ($v2 = $SDB->fetch_array($vquery2))
				{
					$v2['username'] && $voter[] = $v2['username'];
				}
				$voteoptions[][2] = $voter;
			}
			$DDB->update("INSERT INTO {$pw_prefix}polls (tid,voteopts,previewable,timelimit) VALUES ('".$t['topic_id']."','".addslashes(serialize(array('options'=>$voteoptions,'multiple'=>array(1,$t['poll_max_options']))))."',1,".ceil($t['poll_length']/86400).")");
		}
		else
		{
			$special = 0;
		}
		$query2 = $SDB->query("SELECT p.*,u.username FROM {$source_prefix}posts p LEFT JOIN {$source_prefix}users u ON p.poster_id = u.user_id WHERE p.topic_id = ".$t['topic_id']);
		while ($bbs = $SDB->fetch_array($query2))
		{
			$aid = '';
			if($bbs['post_attachment'])
			{
				$qa = $SDB->query("SELECT * FROM {$source_prefix}attachments WHERE post_msg_id = ".$bbs['post_id']);
				while ($a = $DDB->fetch_array($qa))
				{
					switch ($a['extension'])
					{
						case 'jpg':
						case 'jpeg':
						case 'bmp':
						case 'png':
						case 'gif':
							$ftype = 'img';
							break;
						case 'text':
							$ftype = 'txt';
							break;
						default:
							$ftype = 'zip';
							break;
					}
					$attdata[$a['attach_id']] = array('aid'=>$a['attach_id'],'name'=>$a['real_filename'],'type'=>$ftype,'attachurl'=>$a['physical_filename'].'.'.$a['extension'],'needrvrc'=>0,'size'=>ceil($a['filesize']/1024),'hits'=>$a['download_count'],'desc'=>$a['attach_comment'],'ifthumb'=>0);
					$DDB->update("INSERT INTO {$pw_prefix}attachs (aid,fid,uid,tid,pid,name,type,size,attachurl,hits,needrvrc,uploadtime,descrip) VALUES ('".$a['attach_id']."','".$t['forum_id']."','".$a['poster_id']."','".$a['topic_id']."','".$a['post_msg_id']."','".addslashes($a['real_filename'])."','".$ftype."','".ceil($a['filesize']/1024)."','".addslashes($a['physical_filename'].'.'.$a['extension'])."',".$a['download_count'].",0,".$a['filetime'].",'".addslashes($a['attach_comment'])."')");
				}
				$aid = addslashes(serialize($attdata));
			}
			$bbs['post_text'] = phpbb_ubb($bbs['post_text']);
			$ifconvert = (convert($bbs['post_text']) == $bbs['post_text']) ? 1 : 2;
			if ($t['topic_first_post_id'] == $bbs['post_id'])
			{
				$DDB->update("INSERT INTO {$pw_prefix}tmsgs (tid,aid,userip,ifsign,buy,ifconvert,content) VALUES (".$t['topic_id'].",'".$aid."','".addslashes($bbs['poster_ip'])."',1,'','".$ifconvert."','".addslashes($bbs['post_text'])."')");
			}
			else
			{
				$DDB->update("INSERT INTO {$pw_prefix}posts (pid,aid,fid,tid,author,authorid,postdate,subject,userip,ifsign,ifconvert,ifcheck,content) VALUES (".$bbs['post_id'].",'".$aid."',".$bbs['forum_id'].",".$bbs['topic_id'].",'".addslashes($bbs['username'])."','".$bbs['poster_id']."','".$t['topic_time']."','".addslashes($t['topic_title'])."','".addslashes($bbs['poster_ip'])."',1,'".$ifconvert."',".(int)$bbs['post_approved'].",'".addslashes($bbs['post_text'])."')");
			}
		}
		if ($t['topic_type'] == '3' || $t['topic_type'] == '2')
		{
			$DDB->update("INSERT INTO {$pw_prefix}announce (fid,author,startdate,url,subject,content) VALUES (-1,'".addslashes($t['topic_first_poster_name'])."',".$t['topic_time'].",'".addslashes($bbs['post_text'])."','".addslashes($t['topic_title'])."','".addslashes($bbs['post_text'])."')");//75里面本没这个字段ffid
		}
		$DDB->update("INSERT INTO {$pw_prefix}threads (tid,fid,icon,author,authorid,subject,ifcheck,postdate,lastpost,lastposter,hits,replies,topped,locked,special) VALUES (".$t['topic_id'].",".$t['forum_id'].",'".$t['icon_id']."','".addslashes($t['topic_first_poster_name'])."',".$t['topic_poster'].",'".addslashes($t['topic_title'])."',".$t['topic_approved'].",'".$t['topic_time']."','".$t['topic_last_post_time']."','".addslashes($t['topic_last_poster_name'])."',".$t['topic_views'].",".$t['topic_replies'].",".$istop.",".($t['topic_status'] == 1 ? 1 : 0).",".$special.")");
		$s_c++;
	}
	refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
}
elseif ($step == 6)
{
	//短信
	if(!$start)
	{
		$DDB->update("TRUNCATE TABLE {$pw_prefix}msg");
		$DDB->update("TRUNCATE TABLE {$pw_prefix}msgc");
		$DDB->update("TRUNCATE TABLE {$pw_prefix}msglog");
	}
	$msg_id = $SDB->get_one("SELECT msg_id FROM {$source_prefix}privmsgs LIMIT $start,1");
	if (!$msg_id)
	{
		report_log();
		newURL($step);
	}
	$query = $SDB->query("SELECT * FROM {$source_prefix}privmsgs WHERE msg_id >= $start LIMIT $percount");
	while($m = $SDB->fetch_array($query))
	{
		if ($m['to_address'])
		{
			$type = 'rebox';
			$touid = substr($m['to_address'], strpos($m['to_address'],'_')+1);
		}
		elseif ($m['bcc_address'])
		{
			$type = 'sebox';
			$touid = $m['author_id'];
		}
		else
		{
			$f_c++;
			errors_log($m['msg_id']);
			continue;
		}
		$mt = $DDB->get_one("SELECT username FROM {$pw_prefix}members WHERE uid = '".$touid."'");
		if ($mt['username'])
		{
			//6.3.2
			$DDB->update("INSERT INTO {$pw_prefix}msg (touid,fromuid,username,type,ifnew,mdate) VALUES (".$touid.",".$m['author_id'].",'".addslashes($mt['username'])."','".$type."',0,'".$m['message_time']."')");
			$mid = $DDB->insert_id();
			$DDB->update("INSERT INTO {$pw_prefix}msgc (mid,title,content) VALUES ($mid,'".addslashes($m['message_subject'])."','".addslashes($m['message_text'])."')");
			if (($touid != $m['author_id']) && ($type == 'rebox'))
			{
				$DDB->update("INSERT INTO {$pw_prefix}msglog (mid,uid,withuid,mdate,mtype) VALUES ($mid,".$m['author_id'].",$touid,'".$m['message_time']."','send')");
				$DDB->update("INSERT INTO {$pw_prefix}msglog (mid,uid,withuid,mdate,mtype) VALUES ($mid,$touid,".$m['author_id'].",'".$m['message_time']."','receive')");
			}
		}
		$s_c++;
	}
	refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
}
else
{
	ObHeader($basename.'?action=finish&dbtype='.$dbtype);
}

##########################

function changegroups()
{
	global $pw_prefix, $source_prefix, $SDB, $DDB, $dest_charset;
	require_once S_P.'lang_'.$dest_charset.'.php';
	$DDB->update("TRUNCATE TABLE {$pw_prefix}usergroups");
	$DDB->update($lang['group']);
	$query = $SDB->query("SELECT * FROM {$source_prefix}groups WHERE group_type IN (0,1)");
	while ($rt = $SDB->fetch_array($query))
	{
		$gptype = ($rt['group_type'] == 1) ? 'special' : 'member';
		$DDB->update("INSERT INTO {$pw_prefix}usergroups (gid,gptype,grouptitle,maxmsg,allowhide,allowread,allowportait,upload,allowrp,allowhonor,allowdelatc,allowpost,allownewvote,allowvote,allowactive,htmlcode,wysiwyg,allowhidden,allowencode,allowsell,allowsearch,allowmember,allowprofile,allowreport,allowmessege,allowsort,alloworder,allowupload,allowdownload,allowloadrvrc,allownum,edittime,postpertime,searchtime,signnum,mright,sright) VALUES

		(".$rt['group_id'].",'".$gptype."','".addslashes($rt['group_name'])."',0,0,1,0,0,1,1,0,1,1,1,0,50,0,0,10,30,'show	0\n1\nviewipfrom	0\n1\nimgwidth	\n1\nimgheight	\n1\nfontsize	\n1\nmsggroup	0\n1\nmaxfavor	100\n1\nviewvote	0\n1\natccheck	0\n1\nmarkable	0\n1\npostlimit	\n1\nuploadmaxsize	0\n1\nuploadtype	\n1\nmarkdb	|||','')");

		pwGroupref(array('gid'=>$rt['group_id'],'gptype'=>$gptype,'grouptitle'=>addslashes($rt['group_name']),'maxmsg'=>10,'allowhide'=>0,'allowread'=>1,'allowportait'=>0,'upload'=>0,'allowrp'=>1,'allowhonor'=>0,'allowdelatc'=>1,'allowpost'=>1,'allownewvote'=>0,'allowvote'=>1,'allowactive'=>0,'htmlcode'=>0,'wysiwyg'=>0,'allowhidden'=>1,'allowencode'=>0,'allowsell'=>0,'allowsearch'=>1,'allowmember'=>0,'allowprofile'=>0,'allowreport'=>1,'allowmessage'=>1,'allowsort'=>0,'alloworder'=>1,'allowupload'=>1,'allowdownload'=>1,'allowloadrvrc'=>0,'allownum'=>50,'edittime'=>0,'postpertime'=>0,'searchtime'=>10,'signnum'=>30,'mright'=>'show	0\n1\nviewipfrom	0\n1\nimgwidth	\n1\nimgheight	\n1\nfontsize	\n1\nmsggroup	0\n1\nmaxfavor	100\n1\nviewvote	0\n1\natccheck	0\n1\nmarkable	0\n1\npostlimit	\n1\nuploadmaxsize	0\n1\nuploadtype	\n1\nmarkdb	|||','sright'=>''));
		$grouptitle=getGrouptitle($rt['group_id'],addslashes($rt['group_name']),false);
		$DDB->update("INSERT INTO {$pw_prefix}usergroups (gid,gptype,grouptitle,groupimg,grouppost) VALUES ('".$rt['group_id']."','$gptype','$grouptitle')");

	}
}
function phpbb_ubb($content)
{
	return preg_replace(array('/\[flash=(\d+?),(\d+?):[^]]+?\](.*?)\[\/flash[^]]+?\]/is','/\[\/?size[^]]+?\]/i','/\[wmv:[^]]+?\](.*?)\[\/wmv[^]]+?\]/is','/\[youtube:[^]]+?\](.*?)\[\/youtube[^]]+?\]/is','/\[audio:[^]]+?\](.*?)\[\/audio[^]]+?\]/is','/\[video:[^]]+?\](.*?)\[\/video[^]]+?\]/is','/\[img:[^]]+?\](.+?)\[\/img:.*?\]/i','/\[img2=[^]]+?\](.+?)\[\/img2.*?\]/i','/<!-- s([^ ]+?) --><img .*? \/><!-- s\\1 -->/ise', '/\[attachment=[^]]+?\].*?\[\/attachment:[^]]+?\]/is','/\[quote[^]]+?\](.*?)\[\/quote[^]]+?\]/is','/\[b:[^]]+?\](.*?)\[\/b[^]]+?\]/is','/\[u:[^]]+?\](.*?)\[\/u[^]]+?\]/is','/\[i:[^]]+?\](.*?)\[\/i[^]]+?\]/is','/\[list[^]]+?\](.*?)\[\/list[^]]+?\]/is','/\[code[^]]+?\](.*?)\[\/code[^]]+?\]/is','/\[color=([^:]+?):[^]]+?\](.*?)\[\/color:[^]]+?\]/is'),array('[flash=\\2,\\1,0]\\3[/flash]','','[wmv=314,256,0]\\1[/wmv]','[flash=314,256,0]\\1[/flash]','[wmv=0]\\1[/wmv]','[wmv=314,256,0]\\1[/wmv]','[img]\\1[/img]','[img]\\1[/img]',"newface('\\1')",'','[quote]\\1[/quote]','[b]\\1[/b]','[u]\\1[/u]','[i]\\1[/i]','[list=a][li]\\1[/li][/list]','[code]\\1[/code]','[color=\\1]\\2[/color]'),str_replace(array('[*]','[/*]'),'',$content));
}
function newface($num)
{
	global $_face;
	return '[s:'.$_face[$num].']';
}
?>