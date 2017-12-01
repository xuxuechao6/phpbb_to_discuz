<?php
/**
*
*  Copyright (c) 2003-09  PHPWind.net. All rights reserved.
*  Support : http://www.phpwind.net
*  This software is the proprietary information of PHPWind.com.
*
*/

###############自定义函数################
function Add_S(&$array)
{
	if (is_array($array))
	{
		foreach ($array as $key => $value)
		{
			if (!is_array($value))
			{
				$array[$key] = addslashes($value);
			}
			else
			{
				Add_S($array[$key]);
			}
		}
	}
}
function setForumsTopped($tid,$fid,$topped,$t=0){
	if ($tid && $fid && $topped > 0) {
		global $DDB,$pw_prefix;
		list($catedbs,$top_1,$top_2,$top_3) = getForumListForHeadTopic($fid);
		$topAll = array();
		if ($topped == 1) {
			$topAll = array_keys((array)$top_1);
		} elseif ($topped == 2) {
			$topAll = array_keys((array)$top_2);
		} elseif ($topped == 3) {
			$topAll = array_keys((array)$top_3);
		}
		$_topped = array();
		foreach ($topAll as $key => $value) {
			$_topped[] = array('fid'=>$value,
							   'tid'=>$tid,
							   'pid'=>'0',
							   'floor'=>$topped,
							   'uptime'=>$fid,
							   'overtime'=>$t);
		}
		!empty($_topped) && $DDB->update("REPLACE INTO {$pw_prefix}poststopped (fid,tid,pid,floor,uptime,overtime) values ".pwSqlMulti($_topped));
	}
}

function getForumListForHeadTopic($fid){
	global $DDB,$pw_prefix;
	global $groupid;
	$sub1 = $sub2 = $forumdb = array();
	$query = $DDB->query("SELECT fid,t_type,type,fup,name,allowvisit,f_type FROM {$pw_prefix}forums");
	while ($rt = $DDB->fetch_array($query)) {
		if ($rt['f_type'] != 'hidden' || ( $rt['f_type'] == 'hidden' && strpos($rt['allowvisit'],','.$groupid.',') !== false )) {
			$rt['fid'] == $fid && $currentForum = $rt;
			if ($rt['type'] == 'category') {
				$catedb[] = $rt;
			} elseif ($rt['type'] == 'forum') {
				$forumdb[$rt['fup']] || $forumdb[$rt['fup']] = array();
				$forumdb[$rt['fup']][] = $rt;
			} elseif ($rt['type'] == 'sub') {
				$sub1[$rt['fup']] || $sub1[$rt['fup']] = array();
				$sub1[$rt['fup']][] = $rt;
			} else {
				$sub2[$rt['fup']] || $sub2[$rt['fup']] = array();
				$sub2[$rt['fup']][] = $rt;
			}
		}
	}
	$top_3 = $top_2 = $top_1 = $catedbs = array();
	foreach ((array)$catedb as $k1 => $v1) {
		$catedbs[$v1['fid']] = array();
		foreach ((array)$forumdb[$v1['fid']] as $k2 => $v2) {
			$catedbs[$v1['fid']][] = $v2['fid'];
			foreach ((array)$sub1[$v2['fid']] as $k3 => $v3) {
				$catedbs[$v1['fid']][] = $v3['fid'];
				foreach ((array)$sub2[$v3['fid']] as $k4 => $v4) {
					$catedbs[$v1['fid']][] = $v4['fid'];
				}
			}
		}
	}
	foreach ((array)$catedb as $k1 => $v1) {
		$v1['name'] = strip_tags($v1['name']);
		$top_3[$v1['fid']] = "&gt;&gt;".$v1['name'];
		if (in_array($currentForum['fid'],$catedbs[$v1['fid']])) {
			$top_2[$v1['fid']] = "&gt;&gt;".$v1['name'];
		}
		foreach ((array)$forumdb[$v1['fid']] as $k2 => $v2) {
			$v2['name'] = strip_tags($v2['name']);
			if ($v2['fid'] == $currentForum['fid']) {
				$top_1[$v2['fid']] = "&nbsp;|-".$v2['name'];
			}
			if (in_array($currentForum['fid'],$catedbs[$v1['fid']])) {
				$top_2[$v2['fid']] = "&nbsp;|-".$v2['name'];
			}
			$top_3[$v2['fid']] = "&nbsp;|-".$v2['name'];
			if (!is_array($sub1[$v2['fid']])) {
				continue;
			}
			foreach ((array)$sub1[$v2['fid']] as $k3 => $v3) {
				$_subs = array();
				$v3['name'] = strip_tags($v3['name']);
				if ($v3['fid'] == $currentForum['fid']) {
					$top_1[$v3['fid']] = "&nbsp;|-".$v3['name'];
				}
				if ($v3['fup'] == $currentForum['fid']) {
					$_subs[] = $v3['fid'];
					$top_1[$v3['fid']] = "&nbsp;&nbsp;&nbsp;|-".$v3['name'];
				}
				$v1['fid'] == $currentForum['fup'] && $top_2[$v3['fid']] = "&nbsp;&nbsp;&nbsp;|-".$v3['name'];
				if (in_array($currentForum['fid'],$catedbs[$v1['fid']])) {
					$top_2[$v3['fid']] = "&nbsp;&nbsp;&nbsp;|-".$v3['name'];
				}
				$top_3[$v3['fid']] = "&nbsp;&nbsp;&nbsp;|-".$v3['name'];
				if (!is_array($sub2[$v3['fid']])) {
					continue;
				}
				foreach ((array)$sub2[$v3['fid']] as $k4 => $v4) {
					$v4['name'] = strip_tags($v4['name']);
					if ($v4['fid'] == $currentForum['fid']) {
						$top_1[$v4['fid']] = "&nbsp;|-".$v4['name'];
					}
					if (in_array($v4['fup'],$_subs)) {
						$top_1[$v4['fid']] =  "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;|-".$v4['name'];
					}
					if (in_array($currentForum['fid'],$catedbs[$v1['fid']])) {
						$top_2[$v4['fid']] = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;|-".$v4['name'];
					}
					$top_3[$v4['fid']] = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;|-".$v4['name'];
				}
			}
		}
	}
	return array($catedbs,$top_1,$top_2,$top_3);
}

function refreshto($refreshurl, $refreshtime = '500')
{
	global $db_table,$start,$end;
	require_once R_P . 'template/refreshto.htm';footer();
}

function Showmsg($msg_info, $restart = false)
{
	global $basename, $action, $dbtype, $step, $extra;
	require_once R_P . 'template/showmsg.htm';footer();
}

function footer()
{
	$output = str_replace(array('<!--<!---->','<!---->',substr(R_P,0,-1)),'',ob_get_contents());
	ob_end_clean();
	function_exists('ob_gzhandler') ? ob_start('ob_gzhandler') : ob_start();
	echo $output;
	exit;
}

function readover($filename,$method='rb')
{
	$filedata = '';
	if ($handle = fopen($filename,$method))
	{
		flock($handle,LOCK_SH);
		$filedata = fread($handle,filesize($filename));
		fclose($handle);
	}
	return $filedata;
}

function writeover($filename,$data,$safe = false,$method='wb')
{
	$safe && $data = "<?php\n!defined('R_P') && exit('Forbidden!');\n".$data."\n?>";
	$handle = fopen($filename,$method);
	fwrite($handle,$data);
	fclose($handle);
}

function P_unlink($filename)
{
	return unlink($filename);
}

function substrs($content,$length,$add='Y')
{
	if ($length && strlen($content)>$length)
	{
		global $dest_charset;
		if ($dest_charset!='utf8')
		{
			$retstr = '';
			for ($i=0;$i<$length-2;$i++)
			{
				$retstr .= ord($content[$i]) > 127 ? $content[$i].$content[++$i] : $content[$i];
			}
			return $retstr.($add=='Y' ? ' ..' : '');
		}
		return utf8_trim(substr($content,0,$length)).($add=='Y' ? ' ..' : '');
	}
	return $content;
}

function utf8_trim($str)
{
	$hex = '';
	$len = strlen($str)-1;
	for ($i=$len;$i>=0;$i-=1)
	{
		$ch = ord($str[$i]);
		$hex .= " $ch";
		if (($ch & 128)==0 || ($ch & 192)==192)
		{
			return substr($str,0,$i);
		}
	}
	return $str.$hex;
}

function pw_strlen($text)
{
	global $dest_charset;
	if (function_exists('mb_strlen'))
	{
		return mb_strlen($text, $dest_charset);
	}
	elseif ($dest_charset == 'utf8')
	{
		return preg_match_all("/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/", $text, $tnum);
	}
	else
	{
		for ($i = 0, $limit = strlen($text), $tnum = 0; $i < $limit; $i++)
		{
			ord($text[$i]) > 127 && ++$i;
			$tnum ++;
		}
		return $tnum;
	}
}

function N_writable($pathfile)
{
	//Copyright (c) 2003-08 PHPWind
	//fix windows acls bug
	$unlink = false;
	substr($pathfile,-1)=='/' && $pathfile = substr($pathfile,0,-1);
	if (is_dir($pathfile))
	{
		$unlink = true;
		mt_srand((double)microtime()*1000000);
		$pathfile = "$pathfile/pw_".uniqid(mt_rand()).'.tmp';
	}
	$fp = @fopen($pathfile,'ab');
	if ($fp===false) return false;
	fclose($fp);
	if ($unlink) @unlink($pathfile);
	return true;
}

function convert($message)
{
	$message =str_replace(array('[u]','[/u]','[b]','[/b]','[i]','[/i]','[list]','[li]','[/li]','[/list]','[sub]', '[/sub]','[sup]','[/sup]','[strike]','[/strike]','[blockquote]','[/blockquote]','[hr]','[/backcolor]', '[/color]','[/font]','[/size]','[/align]'), array('<u>','</u>','<b>','</b>','<i>','</i>','<ul style="margin:0 0 0 15px">','<li>', '</li>','</ul>','<sub>','</sub>','<sup>','</sup>','<strike>','</strike>','<blockquote>','</blockquote>', '<hr />','</span>','</span>','</span>','</font>','</div>'), $message);
	$searcharray = array(
		"/\[list=([aA1]?)\](.+?)\[\/list\]/is",
		"/\[payto\](.+?)\[\/payto\]/is",
		"/\[font=([^\[\(&]+?)\]/is",
		"/\[color=([#0-9a-z]{1,10})\]/is",
		"/\[backcolor=([#0-9a-z]{1,10})\]/is",
		"/\[email=([^\[]*)\]([^\[]*)\[\/email\]/is",
	    "/\[email\]([^\[]*)\[\/email\]/is",
		"/\[size=(\d+)\]/is",
		"/\[align=(left|center|right|justify)\]/is",
		"/\[glow=(\d+)\,([0-9a-zA-Z]+?)\,(\d+)\](.+?)\[\/glow\]/is",
		"/\[img\](.+?)\[\/img\]/is",
		"/\[url=(https?|ftp|gopher|news|telnet|mms|rtsp|thunder)([^\[\s]+?)\](.+?)\[\/url\]/is",
		"/\[url\]www\.([^\[]+?)\[\/url\]/is",
		"/\[url\](https?|ftp|gopher|news|telnet|mms|rtsp|thunder)([^\[]+?)\[\/url\]/is",
		"/\[fly\]([^\[]*)\[\/fly\]/is",
		"/\[move\]([^\[]*)\[\/move\]/is",
		"/\[post\](.+?)\[\/post\]/is",
		"/\[hide=(.+?)\](.+?)\[\/hide\]/is",
		"/\[sell=(.+?)\](.+?)\[\/sell\]/is",
		"/\[quote\](.+?)\[\/quote\]/is",
		"/\[flash=(\d+?)\,(\d+?)(\,(0|1))?\](.+?)\[\/flash\]/is",
		"/\[table(=(\d{1,3}(%|px)?))?\](.*?)\[\/table\]/is",
		"/\[wmv=[01]{1}\](.+?)\[\/wmv\]/is",
		"/\[wmv(?:=[0-9]{1,3}\,[0-9]{1,3}\,[01]{1})?\](.+?)\[\/wmv\]/is",
		"/\[rm(?:=[0-9]{1,3}\,[0-9]{1,3}\,[01]{1})\](.+?)\[\/rm\]/is",
		"/\[iframe\](.+?)\[\/iframe\]/is",
		"/\[code\](.+?)\[\/code\]/is"
	);
	return preg_replace($searcharray,'',$message);
}

function CK_U($username)
{
	global $DDB,$pw_prefix;
	$rt = $DDB->get_one("SELECT uid FROM {$pw_prefix}members WHERE username = '".$username."'");
	return $rt;
}

function mysql_config($source_mark = '', $s_ck = '')
{
	$SDB = $DDB = $pwsamedb = $ckdb = $charset_change = '';
	$charset_data = array('gbk','utf8','big5','latin1');
	if (!$_POST['pw_db_host'] || !$_POST['pw_db_user'] || !$_POST['pw_db_name'] || !$_POST['pw_prefix'])
	{
		ShowMsg('请完整填写 PHPWIND 论坛数据库信息！');
	}

	if (!$_POST['source_db_host'] || $_POST['source_db_host'] == $_POST['pw_db_host'])
	{
		$_POST['source_db_host'] = $_POST['pw_db_host'];
		$ckdb++;
	}

	if (!$_POST['source_db_user'] || $_POST['source_db_user'] == $_POST['pw_db_user'])
	{
		$_POST['source_db_user'] = $_POST['pw_db_user'];
		$ckdb++;
	}

	if (!$_POST['source_db_name'] || $_POST['source_db_name'] == $_POST['pw_db_name'])
	{
		$_POST['source_db_name'] = $_POST['pw_db_name'];
		$ckdb++;
	}

	if (!$_POST['source_db_password'] || $_POST['source_db_password'] == $_POST['pw_db_password'])
	{
		$_POST['source_db_password'] = $_POST['pw_db_password'];
		$ckdb++;
	}

	if ($ckdb == 4 && $_POST['dest_charset'] == $_POST['source_charset'])
	{
		$pwsamedb = '1';
	}
	elseif (!$_POST['source_prefix'] || !$_POST['source_db_host'] || !$_POST['source_db_user'] || !$_POST['source_db_name'])
	{
		ShowMsg('请完整填写 '.strtoupper($source_mark).' 论坛数据库信息！');
	}
	$percount = $_POST['percount'] ? (int)$_POST['percount'] : 1000;
	$speed = $_POST['speed'];
	$repeatname = $_POST['repeatname'] ? $_POST['repeatname'] : '';
	if (!in_array($_POST['dest_charset'], $charset_data))
	{
		ShowMsg('您选择的 PHPWIND论坛 编码格式不正确！');
	}
	if (!in_array($_POST['source_charset'], $charset_data))
	{
		ShowMsg('您选择的 '.strtoupper($source_mark).' 论坛 编码格式不正确！');
	}
	if (!in_array($_POST['page_charset'], $charset_data))
	{
		ShowMsg('您选择的 '.strtoupper($source_mark).' 论坛 页面编码格式不正确！');
	}

	$DDB = new mysql($_POST['pw_db_host'], $_POST['pw_db_user'], $_POST['pw_db_password'], $_POST['pw_db_name'], '');

	if ($pwsamedb)
	{
		$SDB = &$DDB;
	}
	else
	{
		$charset_change = 1;
		$SDB = new mysql($_POST['source_db_host'], $_POST['source_db_user'], $_POST['source_db_password'], $_POST['source_db_name'], '');
	}

	//检查编码
	if ($DDB->server_info() > '4.1')
	{
		$rt = $DDB->get_one("SHOW FULL FIELDS FROM ".$_POST['pw_prefix']."config");
		$dcharset = explode('_', $rt['Collation']);
		(strtolower($dcharset[0]) != $_POST['dest_charset']) && Showmsg('您选择的 PHPWIND论坛 编码格式不正确！');
	}
	elseif ($_POST['dest_charset'] != $_POST['source_charset'])
	{
		Showmsg('你的数据库版本低于4.1，无法进行不同编码之间的转换，请安装 '.$_POST['source_charset'].' 编码的 PHPWIND 论坛程序！');
	}

$dbinfo = <<<EOT
<?php

!defined('R_P') && exit('Forbidden!');

\$percount = $percount;
\$speed = $speed;
\$repeatname = "{$repeatname}";

\$pw_db_host = "{$_POST['pw_db_host']}";
\$pw_db_user = "{$_POST['pw_db_user']}";
\$pw_db_password = "{$_POST['pw_db_password']}";
\$pw_db_name = "{$_POST['pw_db_name']}";
\$pw_prefix = "{$_POST['pw_prefix']}";
\$dest_charset = "{$_POST['dest_charset']}";

\$pwsamedb = "{$pwsamedb}";

\$source_db_host = "{$_POST['source_db_host']}";
\$source_db_user = "{$_POST['source_db_user']}";
\$source_db_password = "{$_POST['source_db_password']}";
\$source_db_name = "{$_POST['source_db_name']}";
\$source_prefix = "{$_POST['source_prefix']}";
\$source_charset = "{$_POST['source_charset']}";
\$page_charset = "{$_POST['page_charset']}";

?>
EOT;
	writeover(S_P.'tmp_sql.php', $dbinfo);

	require_once S_P.'function.php';
	if (is_callable($source_mark))
	{
		call_user_func($source_mark);
	}
}

function mssql_config($source_mark = '', $s_ck = '')
{
	$charset_data = array('gbk','utf8','big5','latin1');
	if (!$_POST['pw_db_host'] || !$_POST['pw_db_user'] || !$_POST['pw_db_name'] || !$_POST['pw_prefix']) ShowMsg('请完整填写 PHPWIND 论坛数据库信息！');
	$percount = $_POST['percount'] ? (int)$_POST['percount'] : 1000;
	if (!in_array($_POST['dest_charset'], $charset_data)) ShowMsg('您选择的 PHPWIND论坛 编码格式不正确！');
	$DDB = new mysql($_POST['pw_db_host'], $_POST['pw_db_user'], $_POST['pw_db_password'], $_POST['pw_db_name'], '');
	if ($DDB->server_info() > '4.1')
	{
		$rt = $DDB->get_one("SHOW FULL FIELDS FROM ".$_POST['pw_prefix']."config");
		$dcharset = explode('_', $rt['Collation']);
		(strtolower($dcharset[0]) != $_POST['dest_charset']) && Showmsg('您选择的 PHPWIND论坛 编码格式不正确！');
	}
	if (!$_POST['source_db_host'] || !$_POST['source_db_user'] || !$_POST['source_db_name'] || !$_POST['source_prefix']) ShowMsg('请完整填写 '.strtoupper($source_mark).' 论坛数据库信息！');
	$source_owner = preg_match('~^[a-z0-9\_\-]+?$~is', $_POST['source_owner']) ? $_POST['source_owner'].'.' : '';
	$source_tablenum = (int)$_POST['source_tablenum'];
	$source_class_type = $_POST['source_class_type'] == 'mssql' ? 'mssql' : 'odbc';
	require_once R_P.'libs/'.$source_class_type.'.class.php';
	$SDB = new mssql($_POST['source_db_host'], $_POST['source_db_user'], $_POST['source_db_password'], $_POST['source_db_name']);
$dbinfo = <<<EOT
<?php

!defined('R_P') && exit('Forbidden!');

\$percount = $percount;

\$pw_db_host = "{$_POST['pw_db_host']}";
\$pw_db_user = "{$_POST['pw_db_user']}";
\$pw_db_password = "{$_POST['pw_db_password']}";
\$pw_db_name = "{$_POST['pw_db_name']}";
\$pw_prefix = "{$_POST['pw_prefix']}";
\$dest_charset = "{$_POST['dest_charset']}";

require_once R_P.'libs/{$source_class_type}.class.php';

\$source_db_host = "{$_POST['source_db_host']}";
\$source_db_user = "{$_POST['source_db_user']}";
\$source_db_password = "{$_POST['source_db_password']}";
\$source_db_name = "{$_POST['source_db_name']}";
\$source_prefix = "{$source_owner}{$_POST['source_prefix']}";
\$source_tablenum = $source_tablenum;
\$page_charset = "binary";

@ini_set('mssql.textlimit', 2147483647);
@ini_set('mssql.textsize', 2147483647);
@ini_set('odbc.defaultlrl', 16777215);

\$SDB = new mssql('{$_POST['source_db_host']}', '{$_POST['source_db_user']}', '{$_POST['source_db_password']}', '{$_POST['source_db_name']}');

?>
EOT;
		writeover(S_P.'tmp_sql.php', $dbinfo);
		require_once S_P.'function.php';
		if (is_callable($source_mark))
		{
			call_user_func($source_mark);
		}
}

function access_config($source_mark = '', $s_ck = '')
{
	if (!$_POST['pw_db_host'] || !$_POST['pw_db_user'] || !$_POST['pw_db_name'] || !$_POST['pw_prefix']) ShowMsg('请完整填写 PHPWIND 论坛数据库信息！');
	if (!$_POST['source_db_host'] || !$_POST['source_db_prefix']){ShowMsg('请完整填写 '.strtoupper($source_mark).' 论坛数据库信息！');}
	if (!file_exists(R_P.$_POST['source_db_host']) || !is_readable(R_P.$_POST['source_db_host']))
	{
		Showmsg('系统无法访问 '.strtoupper($source_mark).' 数据库文件，请确认数据库文件已经放置在 PWBuilder 根目录下！');
	}
	if (!in_array($_POST['dest_charset'], array('gbk','utf8','big5','latin1'))) ShowMsg('您选择的 PHPWIND论坛 编码格式不正确！');
	$DDB = new mysql($_POST['pw_db_host'], $_POST['pw_db_user'], $_POST['pw_db_password'], $_POST['pw_db_name'], '');
	if ($DDB->server_info() > '4.1')
	{
		$rt = $DDB->get_one("SHOW FULL FIELDS FROM ".$_POST['pw_prefix']."config");
		$dcharset = explode('_', $rt['Collation']);
		(strtolower($dcharset[0]) != $_POST['dest_charset']) && Showmsg('您选择的 PHPWIND论坛 编码格式不正确！');
	}
	$percount = $_POST['percount'] ? (int)$_POST['percount'] : 1000;

$dbinfo = <<<EOT
<?php

!defined('R_P') && exit('Forbidden!');

\$percount = $percount;

\$pw_db_host = "{$_POST['pw_db_host']}";
\$pw_db_user = "{$_POST['pw_db_user']}";
\$pw_db_password = "{$_POST['pw_db_password']}";
\$pw_db_name = "{$_POST['pw_db_name']}";
\$pw_prefix = "{$_POST['pw_prefix']}";
\$dest_charset = "{$_POST['dest_charset']}";

\$source_db_host = "{$_POST['source_db_host']}";
\$source_db_user = "{$_POST['source_db_user']}";
\$source_db_password = "{$_POST['source_db_password']}";
\$source_prefix = "{$_POST['source_db_prefix']}";
\$page_charset = "binary";

require_once R_P.'libs/access.class.php';

\$SDB = new access(R_P.'{$_POST['source_db_host']}', '{$_POST['source_db_user']}', '{$_POST['source_db_password']}');

?>
EOT;
	writeover(S_P.'tmp_sql.php', $dbinfo);
	require_once S_P.'function.php';
	if (is_callable($source_mark))
	{
		call_user_func($source_mark);
	}
}

function special_config($source_mark = '', $s_ck = '')
{
	require_once S_P.'function.php';
	call_user_func($source_mark);
}

function spendtime()
{
	$newtime = time();
	$buildtime = (int)$_COOKIE['buildtime'];
	if ($buildtime) {
		setcookie('buildtime', '', time() - 3600);
		$spendtime = $newtime - $buildtime;
		$timestr = '';
		$hours = floor($spendtime / 3600);
		if ($hours > 0)
		{
			$spendtime -= $hours * 3600;
			$timestr .= $hours.'小时 ';
		}
		$minutes = floor($spendtime / 60);
		if ($minutes > 0)
		{
			$spendtime -= $minutes * 60;
			$timestr .= $minutes.'分 ';
		}
		$timestr .= $spendtime.'秒';
		return $timestr;
	} else {
		setcookie('buildtime', $newtime);
	}
}

function ObHeader($URL)
{
	header("Location: $URL");exit;
}

function report_log($extra = '')
{
	global $step,$db_table,$f_c,$s_c,$timestamp,$maxid;
	$maxid=empty($maxid)? 0:$maxid;
	$_report = array();
	if (!@include_once(S_P.'tmp_report.php'))
	{
		writeover(S_P.'tmp_report.php', "\$_report = array();", true);
	}
	$_report[$step] = array($f_c,$s_c,$s_c+$f_c,$db_table,rawurlencode($extra),gmdate('Y-m-d H:i:s',$timestamp),$maxid);
	writeover(S_P.'tmp_report.php', "\$_report = ".pw_var_export($_report).";",true);
}

function init_log()
{
	writeover(S_P.'tmp_report.php', "\$_report = array();", true);
}

function init_error()
{
	global $step,$table;
	writeover(S_P.'tmp_error_'.$step.$table.'.txt', '');
}

function errors_log($error)
{
	global $step,$table;
	writeover(S_P.'tmp_error_'.$step.$table.'.txt', $error."\n", false, 'ab');
}

function parent_upfid($fid, $upfiled = '', $stopv = 0, $level = array(), $i = 0)
{
	global $catedb;
	$return = array();
	if ($catedb[$fid][$upfiled] == $stopv)
	{
		if (count($level) > 3)
		{
			$fupid = $level[count($level)-3];
		}
		else
		{
			$fupid = (int)$level[0];
		}
		$ftype = !$i ? 'category' : ($i > 1 ? 'sub' : 'forum');
		$return = array($ftype, $fupid, $level);
	}
	else
	{
		foreach ($catedb as $k => $v)
		{
			if ($k == $catedb[$fid][$upfiled])
			{
				$level[] = $k;
				$return = parent_upfid($k, $upfiled, $stopv, $level, ++$i);
			}
		}
	}
	return $return;
}

function parent_ifchildid($fid,$upfiled)
{
	global $catedb;
	foreach ($catedb as $k => $v)
	{
		if ($fid == $v[$upfiled])
		{
			return 1;
		}
	}
}

function parent_fid($fid, $level)
{
	static $_parent_fid = '';
	!is_array($level) && settype($level, 'array');
	if (!$_parent_fid)
	{
		if (!@include(S_P.'tmp_parent_fid.php'))
		{
			writeover(S_P.'tmp_parent_fid.php',"\$_parent_fid = array();",true);
		}
	}
	$_parent_fid[$fid] = $level;
	writeover(S_P.'tmp_parent_fid.php', "\$_parent_fid = ".pw_var_export($_parent_fid).";", true);
}

function step_convert($type)
{
	global $dostep, $basename, $step_data, $dbtype;
	$new_step = array();
	foreach ($dostep as $v)
	{
		if (array_key_exists($v, $step_data))
		{
			$new_step[] = $v;
		}
	}
	writeover(S_P.'tmp_dostep.php', "\$_dostep = ".pw_var_export($new_step).";", TRUE);
	spendtime();
	init_log();
	ObHeader($basename.'?action=build&dbtype='.$dbtype.'&step='.($new_step ? $new_step[0] : 1));
}

function newURL($step, $extra = '')
{
	global $cpage;
	require_once (S_P.'tmp_dostep.php');
	if ($_dostep)
	{
		$key = array_keys($_dostep, $step);
		$key = $_dostep[++$key[0]];
	}
	else
	{
		$key = ++$step;
	}
	refreshto($cpage.'&step='.$key.$extra);
}

function reportHash()
{
	global $pw_version;

	require_once (S_P.'tmp_sql.php');
	$DDB = new mysql($pw_db_host, $pw_db_user, $pw_db_password, $pw_db_name, '');

	$members = $DDB->get_one("SELECT COUNT(*) num FROM {$pw_prefix}members");
	$threads = $DDB->get_one("SELECT COUNT(*) num FROM {$pw_prefix}threads");
	$posts   = $DDB->get_one("SELECT COUNT(*) num FROM {$pw_prefix}posts");
	$bbsurl  = $DDB->get_one("SELECT db_value FROM {$pw_prefix}config WHERE db_name = 'db_bbsurl'");
	if ($_SERVER['HTTP_X_FORWARDED_FOR'])
	{
		$onlineip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	}
	elseif ($_SERVER['HTTP_CLIENT_IP'])
	{
		$onlineip = $_SERVER['HTTP_CLIENT_IP'];
	}
	else
	{
		$onlineip = $_SERVER['REMOTE_ADDR'];
	}
	$onlineip  = preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/',$onlineip) ? $onlineip : 'Unknown';
	$db_hash = $db_siteid = $db_siteownerid = $db_sitehash = '';
	mt_srand((double)microtime()*1000000);
	$rand = '0123%^&*45ICV%^&*B6789qazw~!@#$sxedcrikolpQWER%^&*TYUNM';
	$randlen = strlen($rand);
	for ($i = 0; $i < 10; $i++)
	{
		$db_hash .= $rand[mt_rand(0, $randlen-1)];
	}
	$db_siteid = generatestr(16, $db_hash);
	$db_siteownerid = generatestr(18, $db_hash);
	$db_sitehash = '10'.SitStrCode(md5($db_siteid.$db_siteownerid),md5($db_siteownerid.$db_siteid));
	$DDB->update("UPDATE {$pw_prefix}config SET db_value = '$db_hash' WHERE db_name = 'db_hash'");
	$DDB->update("UPDATE {$pw_prefix}config SET db_value = '$db_siteid' WHERE db_name = 'db_siteid'");
	$DDB->update("UPDATE {$pw_prefix}config SET db_value = '$db_siteownerid' WHERE db_name = 'db_siteownerid'");
	$DDB->update("UPDATE {$pw_prefix}config SET db_value = '$db_sitehash' WHERE db_name = 'db_sitehash'");

	$report = 'm='.(int)$members['num'].'&t='.(int)$threads['num'].'&p='.(int)$posts['num'].'&bbsurl='.$bbsurl['db_value'].'&siteid='.$db_siteid.'&siteownerid='.$db_siteownerid.'&sitehash='.$db_sitehash.'&ip='.$onlineip.'&v='.$pw_version.'&date='.time();

	return StrCode($report);
}

function StrCode($string,$action='ENCODE')
{
	$action != 'ENCODE' && $string = base64_decode($string);
	$code = '';
	$key  = substr('1030c423045e492ca7ecbad20203924c',8,18);
	$keylen = strlen($key); $strlen = strlen($string);
	for ($i=0;$i<$strlen;$i++)
	{
		$k		= $i % $keylen;
		$code  .= $string[$i] ^ $key[$k];
	}
	return ($action!='DECODE' ? base64_encode($code) : $code);
}

function SitStrCode($string,$key,$action='ENCODE')
{
	$string	= $action == 'ENCODE' ? $string : base64_decode($string);
	$len	= strlen($key);
	$code	= '';
	for($i=0; $i<strlen($string); $i++)
	{
		$k		= $i % $len;
		$code  .= $string[$i] ^ $key[$k];
	}
	$code = $action == 'DECODE' ? $code : str_replace('=','',base64_encode($code));
	return $code;
}

function generatestr($len,$db_hash)
{
	mt_srand((double)microtime() * 1000000);
    $keychars = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWYXZ';
	$maxlen = strlen($keychars)-1;
	$str = '';
	for ($i=0;$i<$len;$i++)
	{
		$str .= $keychars[mt_rand(0,$maxlen)];
	}
	return substr(md5($str.time().'1030c423045e492ca7ecbad20203924c'.$db_hash),0,$len);
}

function pw_var_export($input,$t = null)
{
	$output = '';
	if (is_array($input))
	{
		$output .= "array(\r\n";
		foreach ($input as $key => $value)
		{
			$output .= $t."\t".pw_var_export($key,$t."\t").' => '.pw_var_export($value,$t."\t");
			$output .= ",\r\n";
		}
		$output .= $t.')';
	} elseif (is_string($input)) {
		$output .= "'".str_replace(array("\\","'"),array("\\\\","\'"),$input)."'";
	} elseif (is_int($input) || is_double($input)) {
		$output .= "'".(string)$input."'";
	} elseif (is_bool($input)) {
		$output .= $input ? 'true' : 'false';
	} else {
		$output .= 'NULL';
	}
	return $output;
}

function P_serialize($array,$ret='',$i=1)
{
	foreach($array as $k => $v)
	{
		if(is_array($v))
		{
			$next = $i+1;
			$ret .= "$k\t";
			$ret  = P_serialize($v,$ret,$next);
			$ret .= "\n$i\n";
		}
		else
		{
			$ret .= "$k\t$v\n$i\n";
		}
	}
	if(substr($ret,-3) == "\n$i\n")
	{
		$ret = substr($ret,0,-3);
	}
	return $ret;
}

function dt2ut($v)
{
	$v = trim($v);
	if (strpos($v, ' ') === false)
	{
		$tmp_ynd = explode('-',$v);
		return (count($tmp_ynd) == 3) ? mktime(0,0,0,$tmp_ynd[1],$tmp_ynd[2],$tmp_ynd[0]) : $GLOBALS['timestamp'];
	}

	$tmp_ynd = explode('-',substr($v, 0, strpos($v, ' ')));
	$tmp_hms = explode(':',substr($v, strpos($v, ' ')+1));

	return (count($tmp_ynd) == 3 && count($tmp_hms) == 3) ? mktime($tmp_hms[0],$tmp_hms[1],$tmp_hms[2],$tmp_ynd[1],$tmp_ynd[2],$tmp_ynd[0]) : $GLOBALS['timestamp'];
}

function mktm($v)
{
	$tmp_ynd = explode('-', trim($v));
    return mktime(0,0,0,$tmp_ynd[1],$tmp_ynd[2],$tmp_ynd[0]);
}

function Char_cv($msg,$isurl=null)
{
	$msg = preg_replace('/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F]/','',$msg);
	$msg = str_replace(array("\0","%00","\r"),'',$msg);
	empty($isurl) && $msg = preg_replace("/&(?!(#[0-9]+|[a-z]+);)/si",'&amp;',$msg);
	$msg = str_replace(array("%3C",'<'),'&lt;',$msg);
	$msg = str_replace(array("%3E",'>'),'&gt;',$msg);
	$msg = str_replace(array('"',"'","\t",'  '),array('&quot;','&#39;','    ','&nbsp;&nbsp;'),$msg);
	return $msg;
}
/**
 * 针对SQL语句的变量进行反斜线过滤,并两边添加单引号
 *
 * @param mixed $var 过滤前变量
 * @param boolean $strip 数据是否经过stripslashes处理
 * @return mixed 过滤后变量
 */
function pwEscape($var,$strip = true) {
	if (is_array($var)) {
		foreach ($var as $key => $value) {
			$var[$key] = pwEscape($value,$strip);
		}
		return $var;
	} elseif (is_numeric($var)) {
		return " '".$var."' ";
	} else {
		return " '".addslashes($strip ? stripslashes($var) : $var)."' ";
	}
}

/**
 * 过滤数组每个元素值,并进行单引号合并
 *
 * @param array $array 源数组
 * @param boolean $strip 数据是否经过stripslashes处理
 * @return string 合并后字符串
 */
function pwImplode($array,$strip=true)
{
	return implode(',',pwEscape($array,$strip));
}

/**
 * 构造单记录数据更新SQL语句
 *  格式: field='value',field='value'
 *
 * @param array $array 更新的数据,格式: array(field1=>'value1',field2=>'value2',field3=>'value3')
 * @param boolean $strip 数据是否经过stripslashes处理
 * @return string SQL语句
 */
function pwSqlSingle($array,$strip=true)
{
	//Copyright (c) 2003-08 PHPWind
	$array = pwEscape($array,$strip);
	$str = '';
	foreach ($array as $key => $val) {
		$str .= ($str ? ', ' : ' ').$key.'='.$val;
	}
	return $str;
}
/**
 * 构造批量数据更新SQL语句
 *  格式: ('value1[1]','value1[2]','value1[3]'),('value2[1]','value2[2]','value2[3]')
 *
 * @param array $array 更新的数据,格式: array(array(value1[1],value1[2],value1[3]),array(value2[1],value2[2],value2[3]))
 * @param boolean $strip 数据是否经过stripslashes处理
 * @return string SQL语句
 */
function pwSqlMulti($array,$strip=true)
{
	//Copyright (c) 2003-08 PHPWind
	$str = '';
	foreach ($array as $val)
	{
		if (!empty($val))
		{
			$str .= ($str ? ', ' : ' ') . '(' . pwImplode($val,$strip) .') ';
		}
	}
	return $str;
}
/**
 * SQL查询中,构造LIMIT语句
 *
 * @param integer $start 开始记录位置
 * @param integer $num 读取记录数目
 * @return string SQL语句
 */
function pwLimit($start,$num=false)
{
	return ' LIMIT '.(int)$start.($num ? ','.(int)$num : '');
}
function getstatus($status,$b,$getv = 1)
{
	return $status >> --$b & $getv;
}

function setstatus(&$status,$b,$setv = '1')
{
	--$b;
	for ($i = strlen($setv)-1; $i >= 0 ; $i--)
	{
		if ($setv[$i])
		{
			$status |= 1 << $b;
		}
		else
		{
			$status &= ~(1 << $b);
		}
		++$b;
	}
	//return $status;
}

function pwGroupref($array)
{
	global $pw_prefix,$DDB;
	require R_P.'lang_right.php';//引入权限语言数组
	$basicdb = array_merge($lang['right']['basic'],$lang['right']['read'],$lang['right']['att']);

	$vipdb	 = $lang['right']['special'];
	$sysdb	 = $lang['right']['system'];
	$sysfdb	 = $lang['right']['systemforum'];
	foreach($array as $key=>$value)
	{
		switch ($key)
		{
			case 'mright' :
				$_M = array_merge($_M,P_unserialize($value));
				if ($_M['markdb'])
				{
					$tmpMarkdb = explode('|',$_M['markdb']);
					$tmpArray  = array(
						'maxcredit'	=> $tmpMarkdb[0],
						'marklimit'	=> $tmpMarkdb[1].','.$tmpMarkdb[2],
						'markctype'	=> $tmpMarkdb[3],
						'markdt'	=> $tmpMarkdb[4]
					);
					$_M = array_merge($_M,$tmpArray);
				}

				break;
			case 'sright' :
				$_M = array_merge($_M,P_unserialize($value));
				if (in_array($array['gptype'],array('system','special')) && $_M['rightwhere']) {
					$rightwhere = explode(',',$_M['rightwhere']);
				}
				break;
			/*case 'gid' :
			case 'gptype' :
			case 'grouptitle' :
			case 'groupimg' :
			case 'grouppost' :
			case 'ifdefault' :
			case 'markable' :
			case 'maxcredit' :
			case 'credittype' :
			case 'creditlimit' :
				break;*/
			default :
				$_M[$key] = $value;
		}
	}
	foreach ($_M as $key=>$value)
	{
		if (isset($basicdb[$key]))
		{
			$keytype = 'basic';
		}
		elseif (isset($sysfdb[$key]) && in_array($array['gptype'],array('system','special')))
		{
			$keytype = 'systemforum';
		}
		elseif (isset($sysdb[$key]) && in_array($array['gptype'],array('system','special')))
		{
			$keytype = 'system';
		}
		elseif (isset($vipdb[$key]) && $array['gptype'] == 'special')
		{
			$keytype = 'special';
		}
		else
		{
			continue;
		}
		is_array($value) && $v = '';
		$groupdb[] = array(0,0,$array['gid'],$key,$keytype,$value);
		if ($rightwhere)
		{
			foreach ($rightwhere as $k=>$v)
			{
				$groupdb[] = array(0,$v,$array['gid'],$key,$keytype,$value);
			}
		}
	}
	if (in_array($array['gptype'],array('system','special')))
	{
		if ($rightwhere || $array['gid']==5)
		{
			$groupdb[] = array(0,0,$array['gid'],'superright','system',0);
		}
		else
		{
			$groupdb[] = array(0,0,$array['gid'],'superright','system',1);
		}
	}
	$DDB->update("REPLACE INTO {$pw_prefix}permission (uid,fid,gid,rkey,type,rvalue) VALUES".pwSqlMulti($groupdb));
}

function getGrouptitle($gid,$grouptitle,$cv=false){
    if($gid<3 && $gid>0){
        $gid==1 && $grouptitle="default";
        $gid==2 && $grouptitle= $cv ? pwGrouptitle("level_".$gid) : $grouptitle;
    }else{
        $grouptitle= $cv ? pwGrouptitle("level_".$gid) : $grouptitle;
    }
    return $grouptitle;

}

function pwGrouptitle($key)
{
    $ga=array(
    'level_1'            => '游客',
    'level_3'            => '管理员',
    'level_4'            => '总版主',
    'level_5'            => '论坛版主',
    'level_6'            => '禁止发言',
    'level_7'            => '未验证会员',
    'level_8'            => '新手上路',
    'level_9'            => '侠客',
    'level_10'            => '骑士',
    'level_11'            => '圣骑士',
    'level_12'            => '精灵王',
    'level_13'            => '风云使者',
    'level_14'            => '光明使者',
    'level_15'            => '天使',
    'level_16'            => '荣誉会员');
    return $ga[$key];

}

function P_unserialize($str,$array=array(),$i=1)
{
	$str = explode("\n$i\n",$str);
	foreach ($str as $key => $value)
	{
		$k = substr($value,0,strpos($value,"\t"));
		$v = substr($value,strpos($value,"\t")+1);
		if (strpos($v,"\n") !== false)
		{
			$next  = $i+1;
			$array[$k] = P_unserialize($v,$array[$k],$next);
		}
		elseif(strpos($v,"\t") !== false)
		{
			$array[$k] = P_array($array[$k],$v);
		}
		else
		{
			$array[$k] = $v;
		}
	}
	return $array;
}

function P_array($array,$string){
	$k = substr($string,0,strpos($string,"\t"));
	$v = substr($string,strpos($string,"\t")+1);
	if (strpos($v,"\t") !== false){
		$array[$k] = P_array($array[$k],$v);
	} else {
		$array[$k] = $v;
	}
	return  $array;
}
function getattfiletype($value)
{
	$value = strtolower(substr($value, strrpos($value, '.')+1));
	switch ($value)
	{
		case 'jpg':
		case 'bmp':
		case 'gif':
		case 'png':
		case 'jpeg':
			return array(1, 'img');
			break;
		case 'txt':
			return array(2, 'txt');
			break;
		default:
			return array(3, 'zip');
			break;
	}
}
?>