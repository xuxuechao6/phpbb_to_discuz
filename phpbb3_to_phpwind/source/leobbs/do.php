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
	//配置
	$ls_data = L_B.'data/boardstats.cgi';
	$lc_data = L_B.'data/counter.cgi';
	if (!file_exists($ls_data) || !is_readable($ls_data) || !file_exists($lc_data) || !is_readable($lc_data)) Showmsg('LeoBBS 论坛统计文件无法读取。', true);
	$keydata = array('lastregisteredmember'=>'newmember','totalmembers'=>'totalmember');
	$updatesql = '';
	$stats = file($ls_data);
	foreach ($stats as $v)
	{
		list($key, $value) = explode(' = ', $v);
		$key = trim(substr($key, 1));
		if (array_key_exists($key, $keydata))
		{
			$updatesql .= $keydata[$key].'='.intval(substr($value,1)).',';
			$s_c++;
		}
	}
	$fp = fopen($lc_data, 'rb');
	flock($fp, LOCK_SH);
	$counter = explode("\t", fgets($fp));
	fclose($fp);
	$DDB->update("UPDATE {$pw_prefix}bbsinfo SET ".$updatesql."higholnum = ".(int)$counter[2].",higholtime = ".(int)$counter[3]." WHERE id = 1");
	$s_c += 2;
	report_log();
	newURL($step);
}
elseif ($step == 2)
{
	//会员
	$lm_data = $lm_dir.'alluser.pl';
	if (!$start)
	{
		if (!file_exists($lm_data) || !is_readable($lm_data)) Showmsg('LeoBBS 论坛用户数据备份文件无法读取。请确认'.$lm_data.'文件存在且可读！', true);
		$DDB->query("TRUNCATE TABLE {$pw_prefix}members");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}memberdata");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}administrators");
	}
	$i = 0;
	$fp = fopen($lm_data, 'rb');
	flock($fp, LOCK_SH);
	fseek($fp, $start);
	if (!ord(fgetc($fp)))
	{
		report_log();
		newURL($step);
	}
	$insertadmin = array();
	while ($i < $percount && !feof($fp))
	{
		$mdata = explode("\t", fgets($fp));
		$mdata[1] = addslashes($mdata[1]);
		if (!$mdata[1] || htmlspecialchars($mdata[1]) != $mdata[1] || CK_U($mdata[1]) || strpos($mdata[1],"\x80") !== FALSE)
		{
			$f_c++;
			errors_log($mdata[1]);
			continue;
		}
		switch ($mdata[33])
		{
			case 'm':
				$sex = 1;
			case 'f':
				$sex = 2;
			default:
				$sex = 0;
		}
		if ($mdata[23])
		{
			if($mdata[23]{0} == '$')
			{
				$mdata[23] = substr($mdata[23], strrpos($mdata[23], '/')+1).'|3|'.$mdata[24].'|'.$mdata[25];
			}
			elseif (substr($mdata[23], 0, 7) == 'http://')
			{
				$mdata[23] = $mdata[23].'|2|'.$mdata[24].'|'.$mdata[25];
			}
			else
			{
				$mdata[23] .= '|1||';
			}
		}
		$mdata[2] = substr($mdata[2],0,3) == 'lEO' ? substr($mdata[2],3) : md5($mdata[2]);
		$mdata[3] = (!$mdata[2] || $mdata[2] == 'member') ? '' : $mdata[3];
		switch ($mdata[4])
		{
			case 'ad'://管理员
				$groupid = 3;
				$insertadmin[3][] = $mdata[1];
				break;
			case 'smo'://总版主
				$groupid = 4;
				$insertadmin[4][] = $mdata[1];
				break;
			case 'mo':
			case 'amo'://版主
				$groupid = 5;
				$insertadmin[5][] = $mdata[1];
				break;
			case 'banned':
			case 'masked':
				$groupid = 6;
				break;
			default:
				$groupid = -1;
				break;
		}
		list($pt1,$pt2) = explode('|', $mdata[5]);
		$postnum = (int)($pt1+$pt2);
		$regip = $mdata[8] == '保密' ? '127.0.0.1' : $mdata[8];
		$signature = leobbs_ubb(substr($mdata[16],0 , strpos($mdata[16], 'aShDFSiod')));
		$signchange = (convert($signature) == $signature) ? '1' : '2';
		$userstatus=($signchange-1)*256+128+(($mdata[7] == 'no') ? 0 : 1)*64+4;//用户位状态设置
		$mdata[37] = $mdata[37] ? str_replace('/','-',$mdata[37]) : '0000-00-00';
		$DDB->update("INSERT INTO {$pw_prefix}members (username,password,email,groupid,icon,gender,regdate,signature,introduce,oicq,icq,site,location,bday,userstatus,banpm) VALUES ('".$mdata[1]."','".addslashes($mdata[2])."','".addslashes($mdata[6])."',".$groupid.",'".addslashes($mdata[23])."',".$sex.",".(int)$mdata[14].",'".addslashes($signature)."','".addslashes($mdata[13])."',".(int)$mdata[10].",".(int)$mdata[11].",'".addslashes(($mdata[9] == 'http://' ? '' : $mdata[9]))."','".addslashes($mdata[12])."','".$mdata[37]."',$userstatus,'')");
		$uid = $DDB->insert_id();
		$DDB->update("INSERT INTO {$pw_prefix}memberdata (uid,postnum,digests,rvrc,money,lastvisit,thisvisit,lastpost,onlinetime) VALUES ($uid,$postnum,".(int)$mdata[41].",".(10*(int)$mdata[46]).",".(int)$mdata[31].",".(int)$mdata[27].",".(int)$mdata[27].",".(int)$mdata[15].",".(int)$mdata[43].")");
		$i++;
		$s_c++;
	}
	foreach ($insertadmin as $k => $v)
	{
		foreach ($v as $name)
		{
			$name = addslashes($name);
			$uid = $DDB->get_one("SELECT uid FROM {$pw_prefix}members WHERE username = '".$name."'");
			$DDB->update("INSERT INTO {$pw_prefix}administrators (uid,username,groupid) VALUES (".$uid['uid'].",'".$name."',$k)");
		}
	}
	flock($fp, LOCK_UN);
	refreshto($cpage.'&step='.$step.'&start='.ftell($fp).'&f_c='.$f_c.'&s_c='.$s_c);
}
elseif ($step == 3)
{
	//板块
	$DDB->query("TRUNCATE TABLE {$pw_prefix}forums");
	$DDB->query("TRUNCATE TABLE {$pw_prefix}forumdata");
	$lb_data = L_B.'data/allforums.cgi';
	if (!file_exists($lb_data) || !is_readable($lb_data)) Showmsg('LeoBBS 论坛板块数据文件无法读取。请确认'.$lb_data.'文件存在且可读！', true);
	$b_data = file($lb_data);
	$class = $catedata = $catedatalink = array();
	foreach ($b_data as $v)
	{
		$bbs = explode("\t", trim($v));
		$forumadmin = explode(',', $bbs[5]);
		$newmaster = $newfmaster = '';
		foreach ($forumadmin as $fa)
		{
			$fa = addslashes(trim($fa));
			$f = $DDB->get_one("SELECT groupid FROM {$pw_prefix}members WHERE username = '$fa'");
			if ($f)
			{
				$newmaster .= $fa.',';
				if ($f['groupid'] == -1) $DDB->update("UPDATE {$pw_prefix}members SET groupid = 5 WHERE username = '".$fa."' LIMIT 1");
			}
		}
		$newmaster && $newmaster = ','.$newmaster;
		if (!array_key_exists($bbs[2], $class))
		{
			$class[$bbs[2]][0] = $bbs[1];//分类名称
			$DDB->update("INSERT INTO {$pw_prefix}forums (childid,type,name) VALUES (1,'category','".addslashes($bbs[1])."')");
			$class[$bbs[2]][1] = $DDB->insert_id();//分类id
		}
		if (preg_match('/^childforum-(\d+?)$/i', $bbs[1], $m))
		{
			$ifsub = 1;
			$fup = (int)$catedatalink[$m[1]];
			$ftype = 'sub';
			$DDB->update("UPDATE {$pw_prefix}forums SET childid = 1 WHERE fid = $fup");
			$subfadmin = $DDB->get_one("SELECT forumadmin FROM {$pw_prefix}forums WHERE fid = $fup");
			$newfmaster = $subfadmin['forumadmin'];
		}
		else
		{
			$ifsub = 0;
			$fup = (int)$class[$bbs[2]][1];
			$ftype = 'forum';
		}
		$DDB->update("INSERT INTO {$pw_prefix}forums (fup,ifsub,type,name,descrip,forumadmin,fupadmin) VALUES (".$fup.",$ifsub,'$ftype','".addslashes($bbs[3])."','".addslashes($bbs[4])."','".$newmaster."','".$newfmaster."')");
		$newfid = $DDB->insert_id();
		$DDB->update("INSERT INTO {$pw_prefix}forumdata (fid) VALUES (".$newfid.")");
		$catedata[] = $bbs[0].'|'.$newfid;
		$catedatalink[$bbs[0]] = $newfid;
		//精华主题
		$digestfile = L_B.'boarddata/jinghua'.$bbs[0].'.cgi';
		$digestarray = array();
		if(file_exists($digestfile))
		{
			$digestarray = array_filter(file($digestfile));
		}
		writeover(S_P.'tmp_digest_'.$bbs[0].'.php', "\$_digest = ".pw_var_export($digestarray).";", TRUE);

		//置顶主题
		$topfile = L_B.'boarddata/ontop'.$bbs[0].'.cgi';
		$toparray = array();
		if(file_exists($topfile))
		{
			$toparray = array_filter(file($topfile));
		}
		writeover(S_P.'tmp_top_'.$bbs[0].'.php', "\$_top = ".pw_var_export($toparray).";", TRUE);
		$s_c++;
	}
	writeover(S_P.'tmp_catedata.php', "\$_catedata = ".pw_var_export($catedata).";", TRUE);
	report_log();
	newURL($step);
}
elseif ($step == 4)
{
	//短信
	if(!$start && !$dir)
	{
		$DDB->update("TRUNCATE TABLE {$pw_prefix}msg");
		$DDB->update("TRUNCATE TABLE {$pw_prefix}msgc");
		$DDB->update("TRUNCATE TABLE {$pw_prefix}msglog");
	}
	$dh = null;
	(!$dir || !in_array($dir,array('in','out'))) && $dir = 'in';
	$li_dir .= $dir;
	if (!is_dir($li_dir) || !($dh = opendir($li_dir))) Showmsg('LeoBBS 用户短信数据目录不存在或者打开失败。');
	$i = 0;
	$end = $start + $percount;
	while (($file = readdir($dh)) !== FALSE && $i < $end)
	{
		if ($i++ >= $start)
		{
			if ($file == '.' || $file == '..' || !preg_match('/^(.+?)\_(?:msg|out)\.cgi$/i', $file, $m)) continue;
			$username = addslashes($m[1]);
			$udata = $DDB->get_one("SELECT uid FROM {$pw_prefix}members WHERE username = '".$username."'");
			if (!$udata)
			{
				$f_c++;
				errors_log($file);
				continue;
			}
			if ($dir == 'in')
			{
				$mtype = 'rebox';
				$touid = $udata['uid'];
			}
			else
			{
				$mtype = 'sebox';
				$fromuid = $udata['uid'];
			}
			$mdata = file($li_dir.'/'.$file);
			foreach ($mdata as $v)
			{
				$v = trim($v);
				$v = explode("\t", substr($v,10));
				$v[0] = addslashes($v[0]);
				if ($dir == 'in')
				{
					$fromuid = $DDB->get_one("SELECT uid FROM {$pw_prefix}members WHERE username = '".$v[0]."'");
					$fromuid = (int)$fromuid['uid'];
				}
				else
				{
					$touid = $DDB->get_one("SELECT uid FROM {$pw_prefix}members WHERE username = '".$v[0]."'");
					$touid = (int)$touid['uid'];
				}
				//6.3.2
				$DDB->update("INSERT INTO {$pw_prefix}msg (touid,fromuid,username,type,ifnew,mdate) VALUES (".$touid.",".$fromuid.",'".$v[0]."','".$mtype."',".($v[1] == 'no' ? 1 : 0).",'".$v[2]."')");
				$mid = $DDB->insert_id();
				$DDB->update("INSERT INTO {$pw_prefix}msgc (mid,title,content) VALUES ($mid,'".addslashes($v[3])."','".addslashes($v[4])."')");
				if (($username != $v[0]) && ($dir == 'in'))
				{
					$DDB->update("INSERT INTO {$pw_prefix}msglog (mid,uid,withuid,mdate,mtype) VALUES ($mid,$fromuid,$touid,'".$v[2]."','send')");
					$DDB->update("INSERT INTO {$pw_prefix}msglog (mid,uid,withuid,mdate,mtype) VALUES ($mid,$touid,$fromuid,'".$v[2]."','receive')");
				}
			}
		}
		$s_c++;
	}
	if ($file === FALSE)
	{
		if ($dir == 'in')
		{
			$dir = 'out';
			$end = 0;
		}
		else
		{
			report_log();
			newURL($step);
		}
	}
	refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c.'&dir='.$dir);
}
elseif ($step == 5)
{
	//好友
	if(!$start)
	{
		$DDB->update("TRUNCATE TABLE {$pw_prefix}friends");
	}
	$dh = null;
	if (!is_dir($lf_dir) || !($dh = opendir($lf_dir))) Showmsg('LeoBBS 用户好友数据目录不存在或者打开失败。');
	$i = 0;
	$end = $start + $percount;
	while (($file = readdir($dh)) !== FALSE && $i < $end)
	{
		if ($i++ >= $start)
		{
			if ($file == '.' || $file == '..' || !preg_match('/^(.+?)\.cgi$/i', $file, $m)) continue;
			$udata = $DDB->get_one("SELECT uid FROM {$pw_prefix}members WHERE username = '".addslashes($m[1])."'");
			if (!$udata)
			{
				$f_c++;
				errors_log($file);
				continue;
			}
			$uid = (int)$udata['uid'];
			$mdata = file($lf_dir.'/'.$file);
			foreach ($mdata as $v)
			{
				$friendid = $DDB->get_one("SELECT uid FROM {$pw_prefix}members WHERE username = '".addslashes(trim(substr($v,10)))."'");
				if (!$friendid) continue;
				$DDB->update("INSERT INTO {$pw_prefix}friends (uid,friendid) VALUES ($uid,".(int)$friendid['uid'].")");
			}
		}
		$s_c++;
	}
	if ($file === FALSE)
	{
		report_log();
		newURL($step);
	}
	refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
}
elseif ($step == 6)
{
	//主题
	require_once(S_P.'tmp_catedata.php');
	$table = (int)$table;
	if(!$start && !$table)
	{
		$DDB->update("TRUNCATE TABLE {$pw_prefix}threads");
		$DDB->update("TRUNCATE TABLE {$pw_prefix}tmsgs");
		$DDB->update("TRUNCATE TABLE {$pw_prefix}posts");
		$DDB->update("TRUNCATE TABLE {$pw_prefix}attachs");
		$DDB->update("TRUNCATE TABLE {$pw_prefix}polls");
	}
	list($oldcid, $newcid) = explode('|', $_catedata[$table]);
	$dh = null;
	$la_dir .= $oldcid.'/';
	if (!is_dir($la_dir) || !($dh = opendir($la_dir))) Showmsg('LeoBBS 主题数据目录不存在或者打开失败。');
	$i = 0;
	//$end = $start + $percount;
	$_digest = $_top = array();
	require_once(S_P.'tmp_digest_'.$oldcid.'.php');
	require_once(S_P.'tmp_top_'.$oldcid.'.php');
	while (($file = readdir($dh)) !== FALSE && $i < $end)
	{
		if ($i++ >= $start)
		{
			if ($file == '.' || $file == '..' || !preg_match('/^(\d+?)\.pl$/i', $file, $m))
			{
				$f_c++;
				errors_log($file);
				continue;
			}
			$fp = fopen($la_dir.$file, 'rb');
			flock($fp, LOCK_SH);
			$tdata = explode("\t", trim(fgets($fp)));
			$subject = addslashes(substr($tdata[1],10));
			$speical = $locked = 0;
			switch ($tdata[3])
			{
				case 'closed':
					$locked = 1;
					break;
				case 'poll':
					$speical = 1;
					break;
				case 'pollclosed':
					$speical = $locked = 1;
					break;
			}
			$author = addslashes($tdata[6]);
			$authorid = $DDB->get_one("SELECT uid FROM {$pw_prefix}members WHERE username = '$author'");
			$authorid = (int)$authorid['uid'];
			if (!($detail = file($la_dir.$m[1].'.thd.cgi')))
			{
				$f_c++;
				errors_log($file);
				continue;
			}
			foreach ($detail as $k => $v)
			{
				$tmsgs = explode("\t", $v);
				$subject = addslashes(str_replace('’…‘’', '', str_replace('＊＃！＆＊', '', $tmsgs[1])));
				list($ip_1) = explode('=', $tmsgs[2]);
				$ifshield = (strpos($tmsgs[6], '[POSTISDELETE= ]') === FALSE) ? 0 : 1;
				$content = leobbs_ubb($tmsgs[6]);
				$ifconvert = (convert($content) == $content) ? 1 : 2;
				if ($k)
				{
					$pauthor = addslashes($tmsgs[0]);
					$pauthorid = $DDB->get_one("SELECT uid FROM {$pw_prefix}members WHERE username = '$pauthor'");
					$DDB->update("INSERT INTO {$pw_prefix}posts (fid,tid,aid,author,authorid,postdate,subject,userip,buy,ifconvert,ifcheck,content,ifshield) VALUES ($newcid,$tid,'','".$pauthor."',".((int)$pauthorid['uid']).",".$tmsgs[5].",'".$subject."','".$ip_1."','',$ifconvert,1,'".addslashes($content)."',$ifshield)");
					$pid = $DDB->insert_id();
				}
				else
				{
					$DDB->update("INSERT INTO {$pw_prefix}threads (fid,author,authorid,subject,ifcheck,postdate,lastpost,lastposter,hits,replies,topped,locked,digest,special,ifshield) VALUES ($newcid,'".$author."','".$authorid."','".$subject."',1,'".$tdata[7]."','".$tdata[9]."','".$tdata[8]."','".$tdata[5]."','".$tdata[4]."',".(in_array($tdata[0],$_top) ? 1 : 0).",$locked,".(in_array($tdata[0],$_digest) ? 1 : 0).",$speical,$ifshield)");
					$tid = $DDB->insert_id();
					$DDB->update("INSERT INTO {$pw_prefix}tmsgs (tid,aid,userip,buy,ifconvert,content) VALUES ($tid,'','".$ip_1."','',$ifconvert,'".addslashes($content)."')");
					if ($speical && file_exists($la_dir.$m[1].'.poll.cgi') && ($poll = file($la_dir.$m[1].'.poll.cgi')))
					{
						$polluser = $voteoptions = array();
						foreach ($poll as $per)
						{
							list($t1,$t2,) = explode("\t", $per);
							$polluser[$t2][] = str_replace('＊！＃＆＊','',$t1);
						}
						$vtmp = explode('<BR>', str_replace('<br>', '<BR>', $tmsgs[7]));
						$ii = 0;
						foreach ($vtmp as $pk => $pv)
						{
							$voteoptions[$ii][0] = $pv;
							$voteoptions[$ii][1] = count($polluser[++$pk]);
							$voteoptions[$ii++][2] = $polluser[$pk];
						}
						$DDB->update("INSERT INTO {$pw_prefix}polls (tid,voteopts,previewable,timelimit) VALUES ('".$tid."','".addslashes(serialize(array('options'=>$voteoptions,'multiple'=>array(1,count($vtmp)))))."',1,0)");
					}
				}
				if (preg_match_all('/\[UploadFile=(.+?)\]/i', $tmsgs[6], $am, PREG_SET_ORDER))
				{
					$attdata = array();
					$withattachment = 1;
					$tmptid = $tdata[0] % 100;
					foreach ($am as $av)
					{
						if (file_exists($lt_dir.$oldcid.'/'.$av[1]))
						{
							$att_file = $lt_dir.$oldcid.'/'.$av[1];
						}
						elseif (file_exists($lt_dir.$oldcid.'/'.$tdata[0].'/'.$av[1]))
						{
							$att_file = $lt_dir.$oldcid.'/'.$tdata[0].'/'.$av[1];
						}
						elseif (file_exists($lt_dir.$oldcid.'/'.$tmptid.'/'.$av[1]))
						{
							$att_file = $lt_dir.$oldcid.'/'.$tmptid.'/'.$av[1];
						}
						elseif (file_exists($lt_dir.$oldcid.'/'.$tdata[0].'/'.$k.'/'.$av[1]))
						{
							$att_file = $lt_dir.$oldcid.'/'.$tdata[0].'/'.$k.'/'.$av[1];
						}
						else
						{
							$withattachment = 0;
						}
						if ($withattachment)
						{
							$ext = substr($att_file, strrpos($att_file,'.')+1);
							switch ($ext)
							{
								case 'jpg':
								case 'jpeg':
								case 'gif':
								case 'png':
								case 'bmp':
									$ftype = 'img';
									break;
								case 'txt':
									$ftype = 'txt';
									break;
								default:
									$ftype = 'zip';
									break;
							}
							$fsize = ceil(filesize($att_file)/1024);
							$DDB->update("INSERT INTO {$pw_prefix}attachs (fid,uid,tid,pid,name,type,size,attachurl,uploadtime) VALUES ($newcid,$authorid,".(int)$tid.",".(int)$pid.",'".addslashes($av[1])."','$ftype',".$fsize.",'".addslashes($oldcid.'/'.$tdata[0].'/'.$av[1])."',".(int)$tmsgs[5].")");
							$aid = $DDB->insert_id();
							$attdata[$aid] = array('aid'=>$aid,'name'=>addslashes($av[1]),'type'=>$ftype,'attachurl'=>addslashes(substr($att_file, strlen($lt_dir))),'needrvrc'=>0,'size'=>$fsize,'hits'=>0,'desc'=>'','ifthumb'=>0);
						}
					}
					if ($attdata)
					{
						$DDB->update("UPDATE {$pw_prefix}".($k ? 'posts' : 'tmsgs')." SET aid = '".addslashes(serialize($attdata))."', content = '".addslashes(preg_replace('/\[UploadFile=(.*?)\]/i','',$tmsgs[6]))."' WHERE tid = $tid".($k ? " AND pid = $pid" : ''));
					}
				}
			}
			fclose($fp);
		}
		$s_c++;
	}
	closedir($dh);
	if ($file === FALSE)
	{
		if (isset($_catedata[++$table]))
		{
			refreshto($cpage.'&step='.$step.'&table='.$table.'&f_c='.$f_c.'&s_c='.$s_c);
		}
		else
		{
			report_log();
			newURL($step);
		}
	}
	refreshto($cpage.'&step='.$step.'&start='.$end.'&table='.$table.'&f_c='.$f_c.'&s_c='.$s_c);
}
else
{
	ObHeader($basename.'?action=finish&dbtype='.$dbtype);
}

##########################

function leobbs_ubb($content)
{	
	return preg_replace(array('/:em(\d+?):/i','/\[real=(\d+?),(\d+?)\](.*?)\[\/real\]/i','/\[wm=(\d+?),(\d+?)\](.*?)\[\/wm\]/i','/\[post=(\d+?)\]/i','/\[shadow=[^]]+?\]/i','/\[BLUR=[^]]+?\]/i','/\[jf=(\d+?)\]/i','/LBHIDDEN\[\d+?\]LBHIDDEN/i'),array('[s:\\1]'.'[rm=\\1,\\2,0]\\3[/rm]','[wmv=\\1,\\2,0]\\3[/wmv]','[post]',''),str_replace(array('[color=&#35;','[/mms]','[/sound]','[/wma]','[ra]','[rtsp]','[rm]','[/ra]','[/rtsp]','[/rm]','[mms]','[wmv]','[sound]','[wma]','[equote]','[fquote]','[/equote]','[/fquote]','[swf]','[/swf]','[hide]','[/hide]','[html]','[/html]','[s]','[/s]','[FLIPH]','[/FLIPH]','[FLIPV]','[/FLIPV]','[INVERT]','[/INVERT]','[XRAY]','[/XRAY]','[/shadow]','[/BLUR]','[jf]','[watermark]','[/watermark]','[buyexege]','[/buyexege]','[POSTISDELETE= ]','[hidepoll]'),array('[color=#','[/wmv]','[/wmv]','[/wmv]','[rm=314,256,0]','[rm=314,256,0]','[rm=314,256,0]','[/rm]','[/rm]','[/rm]','[wmv=314,256,0]','[wmv=314,256,0]','[wmv=0]','[wmv=0]','[quote]','[quote]','[/quote]','[/quote]','[flash=314,256,0]','[/flash]','[post]','[/post]',''),$content));
}
?>