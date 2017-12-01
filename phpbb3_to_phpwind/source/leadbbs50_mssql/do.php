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
	//表情
	require_once S_P.$step.'.php';
}
if ($step == 'x')
{
    //未使用
	$query = $SDB->query("SELECT ID,OrderID,BoardID,AssortName,GoodNum FROM {$source_prefix}GoodAssort");
	while ($rt = $SDB->fetch_array($query))
	{
        $DDB->update("INSERT INTO pw_topictype (id,fid,name,vieworder) VALUES(".$rt['ID'].",'".$rt['BoardID']."','".$rt['AssortName']."','".$rt['OrderID']."')");
    }
}
elseif ($step == '2')
{
	//用户
	require_once S_P.$step.'.php';
}
elseif ($step == '3')
{
	//版块
	require_once S_P.$step.'.php';
}
elseif ($step == '4')
{
    //主题和回复
	require_once S_P.'4.php';
	echo '进5';exit;
	//主题信息
	if(!$start)
	{
		//$DDB->query("TRUNCATE TABLE {$pw_prefix}threads");
		//$DDB->query("TRUNCATE TABLE {$pw_prefix}polls");
	}
	$t = $SDB->query("SELECT ID,BoardID,RootID,ChildNum,Title,FaceIcon,ndatetime,LastTime,Hits,UserName,UserID,LastUser,VisitIP,TopicType,GoodFlag,PollNum FROM {$source_prefix}Topic WHERE ID >= $start AND ID < $end");
	while($t = $SDB->fetch_array($query))
	{
		if ($t['BoardID'] == 444)
		{
			$t->MoveNext();
			continue;
		}

		$t_LastTime = dt2ut(RestoreTime($t['LastTime']));
		$t_ndatetime = dt2ut(RestoreTime($t['ndatetime']));
		if ($t['TopicType'])
		{
			$special = '1';
		}
		else
		{
			$special = '0';
		}

		$DDB->update("INSERT INTO {$pw_prefix}threads (tid,fid,icon,author,authorid,subject,ifcheck,postdate,lastpost,lastposter,hits,replies,digest,special) VALUES (".$t['ID'].",".$t['BoardID'].",'".$t['FaceIcon']."','".addslashes($t['UserName'])."',".$t['UserID'].",'".addslashes($t['Title'])."',1,'".$t_ndatetime."','".$t_LastTime."','".addslashes($t['LastUser'])."',".$t['Hits'].",".$t['ChildNum'].",".$t['GoodFlag'].",".$special.")");
		if ($t['TopicType'])
		{
			$v = $SDB->query("SELECT VoteName,VoteNum FROM {$source_prefix}VoteItem WHERE AnnounceID = ".$t['ID']);
			while (!$v->EOF)
			{
				$voteoptions = array();
				$voteoptions[][0] = $v->Fields['VoteName'];
				$voteoptions[][1] = $v->Fields['VoteNum'];
				$v2 = $SDB->query("SELECT UserName,VoteItem FROM {$source_prefix}VoteUser WHERE AnnounceID = ".$t['ID']);
				while (!$v2->EOF)
				{
					$voteoptions[][2][] = $v->Fields['VoteName'];
					//$v2->MoveNext();
				}
				//$v->MoveNext();
			}
			$voteoptions && $DDB->update("INSERT INTO {$pw_prefix}polls (tid,voteopts,modifiable,previewable,timelimit) VALUES ('".$t['ID']."','".addslashes(serialize(array('options'=>$voteoptions,'multiple'=>array(1,count($voteoptions)))))."',1,1,0)");
		}
		$s_c++;
		$t->MoveNext();
	}
	$row = $SDB->query("SELECT COUNT(*) AS num FROM {$source_prefix}Topic WHERE ID >= $end");
	if ($row->Fields['num'])
	{
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
		report_log();
		newURL($step);
	}
}
elseif ($step == '5')
{
    //附件信息
	require_once S_P.$step.'.php';
}
elseif ($step == '6')
{
	//友情链接
	//require_once S_P.'lang_'.$dest_charset.'.php';
	//$DDB->query("TRUNCATE TABLE {$pw_prefix}sharelinks");
	$query = $SDB->query("SELECT * FROM {$source_prefix}Link");
	$ilink = '';
	while($l = $SDB->fetch_array($query))
	{
        ADD_S($l);
		$ilink .= "('".$l['OrderID']."','".addslashes($l['SiteName'])."','".addslashes($l['SiteUrl'])."','".addslashes($l['LogoUrl'])."','',1),";

		//$l->MoveNext();
	}
	//$ilink .= $lang['link'];
    //echo $ilink;exit;
    $ilink = substr($ilink,0,-1);
	$ilink && $DDB->update("INSERT INTO {$pw_prefix}sharelinks (threadorder,name,url,logo,descrip,ifcheck) VALUES ".$ilink);
	report_log();
    exit;
	newURL($step);
}
elseif ($step == '7')
{
    //短消息
	require_once S_P.$step.'.php';
}
elseif ($step == '8')
{
	//头像
	require_once S_P.$step.'.php';
}
elseif ($step == 'x')
{
    //修复头像的简单小程序
	require_once S_P.$step.'.php';
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
function RestoreTime($DateString) //leadbbs50 时间转换
{
	if (strlen($DateString) < 8)
	{
		return $DateString;
	}
	else
	{
		if (strlen($DateString) < 14)
		{
			$DateString = substr($DateString,0,4) . '-' . substr($DateString,4,2) .'-' . substr($DateString,6,2);
			return $DateString;
		}
		else
		{
			$DateString = substr($DateString,0,4) . '-' . substr($DateString,4,2) .'-' . substr($DateString,6,2) . ' '.substr($DateString,8,2).':'.substr($DateString,10,2) .':'.substr($DateString,12,2);
			return $DateString;
		}
	}
}
function dt2ut2($v)
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
function mkbd($v){
	return substr($v,0,4).'-'.substr($v,4,2).'-'.substr($v,6,2);
}
function lead_ubb($Content)
{
	$search = array('[UL]','[/UL]','[OL]','[/OL]','[LI]','[/LI]','[B]','[/B]','[i]','[/i]','[u]','[/u]','[ALIGN=','[/ALIGN]','[URL]','[URL=','[/URL]','[EMAIL=Mailto:','[EMAIL]','[/EMAIL]','[IMGA]','[/IMGA]','[IMG]','[/IMG]','[QUOTE]','[/QUOTE]','[Flash]','[FLASH]','[/Flash]','[/FLASH]','[CODE]','[/CODE]','[FLY]','[/FLY]','[GLOW=','[/GLOW]','[COLOR=','[/COLOR]','[SIZE=','[/SIZE]','[/FACE]','[/RM]','[/MP]','[TABLE][TR][TD]',array('[PRE]','[/PRE]','[LIGHT]','[/LIGHT]','[SOUND]','[/SOUND]','[/FIELDSET]','[/BGCOLOR]','[STRIKE]','[HR]','[/STRIKE]'));
	$replace = array('[ul]','[/ul]','[ol]','[/ol]','[li]','[/li]','[b]','[/b]','[i]','[/i]','[u]','[/u]','[align=','[/align]','[url]','[url=','[/url]','[email=','[email]','[/email]','[img]','[/img]','[img]','[/img]','[quote]','[/quote]','[flash=314,256,0]','[flash=314,256,0]','[/flash]','[/flash]','[code]','[/code]','[fly]','[/fly]','[glow=','[/glow]','[color=','[/color]','[size=','[/size]','[/font]','[/rm]','[/wmv]','[table][tr][td]','');
	$preg_search = array('/\[IMGA?=([,0-9a-z]+?)\]/i','/\[Flash?=(\d+?),(\d+?)\]/i','/\[FACE=(.+?)\]/i','/\[RM=(\d+?),(\d+?)\]/i','/\[MP=(\d+?),(\d+?)\]/i','/\[TABLE=(.+?)\]/i','/\[FIELDSET=(.+?)\]/i','/\[BGCOLOR=(.+?)\]/i');
	$preg_replace = array('[img]','[flash=\\2,\\1]','[font=\\1]','[rm=\\2,\\1,0]','[wmv=\\2,\\1,0]','[table=100%]','','');
	$Content = preg_replace($preg_search,$preg_replace,str_replace($search,$replace,$Content));
	$Content = preg_replace('~\[upload=(\d+?),\d+?].+?\[/upload\]~i','[attachment=\\1]',$Content);
	return $Content;
}
function convert2($message)
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
?>