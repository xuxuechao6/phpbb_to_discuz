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

if($step=='1')
{
	//转换表情
	$DDB->update("Delete From {$pw_prefix}smiles Where ID>0");
	$DDB->update("Alter Table {$pw_prefix}smiles auto_Increment = 0");
	$Query = $SDB->query("Select * From {$source_prefix}smile Where ID>0");
	while($rt = $SDB->fetch_array($Query))
	{
		$DDBupdate = "Insert Into {$pw_prefix}smiles (path,name,vieworder) values ('".$rt['image']."','".$rt['smiletext']."',".$rt['displayorder'].")";
		$DDB->update($DDBupdate);
		$s_c++;
	}
	report_log();
	newURL($step);
}
elseif($step=='2')
{
		//转换用户
	require_once (S_P.'tmp_credit.php');

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
			if (strpos($mc['Field'], 'salt') !== FALSE)
			{
				$addfields = FALSE;
				break;
			}
		}
		$addfields && $DDB->update("ALTER TABLE {$pw_prefix}members ADD salt CHAR( 6 ) NOT NULL DEFAULT ''");
		writeover(S_P.'tmp_group.php', "\$_specialdata = ".pw_var_export(changegroups()).";", true);
	}
	require_once (S_P.'tmp_group.php');
	if ($_specialdata) $_specialdata = unserialize($_specialdata);
	$id = $SDB->get_one("SELECT id FROM {$source_prefix}user LIMIT $start,1");
	if (!$id)
	{
		report_log();
		newURL($step);
	}
	$m_query = $SDB->query("Select u.*,ux.question,ux.answer From {$source_prefix}user u left Join {$source_prefix}userextra ux USING(id) LIMIT $start,$percount");
	$insertadmin='';
	while($rt = $SDB->fetch_array($m_query))
	{
		if (!$rt['id'] || !$rt['name'])
		{
			$f_c++;
			errors_log($rt['id']."\t".$rt['name']);
			continue;
		}
		$safecv = questcode(-1,$rt['question'],$rt['answer']);
		switch ($rt['usergroupid'])
		{
			case '4'://管理员
				$groupid = '3';
				$insertadmin .= "(".$rt['id'].", '".$rt['name']."', 3),";
				break;
			case '6'://总版主
				$groupid = '4';
				$insertadmin .= "(".$rt['id'].", '".$rt['name']."', 4),";
				break;
			case '7'://版主
				$groupid = '5';
				$insertadmin .= "(".$rt['id'].", '".$rt['name']."', 5),";
				break;
			case '5'://禁止发言
				$groupid = '6';
				break;
			case '2'://游客
				$groupid = '2';
				break;
			case '1'://未验证会员
				$groupid = '7';
				break;
			default :
				$groupid = $_specialdata[$rt['usergroupid']] ? $rt['usergroupid'] : '-1';
				break;
		}
		$icon = $rt['avatar']?"mxb/".$rt['id'].".jpg|3||":"|1";
		$members_update = "Insert into {$pw_prefix}members(";
		$members_update .= "uid,username,password,safecv,email,groupid,icon,gender,regdate,signature,bday,userstatus,style,salt)";
		$members_update .= "values(".$rt['id'].",'".$rt['name']."','".$rt['password']."','".$safecv."','".addslashes($rt['email'])."',".$groupid.",'".addslashes($icon)."',".$rt['gender'].",".$rt['joindate'].",'".$rt['signature']."','".$rt['birthday']."',196,'wind','".addslashes($rt['salt'])."')";
		$DDB->update($members_update);
		eval($creditdata);
		if($rvrcdata!='')
		{
			$rption = $SDB->get_value("Select reputation From {$source_prefix}userexpand Where id =".$rt['id']);
			$rvrc = (int)$rption;
		}
		if(!$rvrc){$rvrc='0';}

		$memberdata_update="INSERT INTO {$pw_prefix}memberdata (uid,postnum,digests,rvrc,lastvisit,lastpost) VALUES (".$rt['id'].",".(int)$rt['posts'].",".$rt['quintessence'].",".$rvrc.",".$rt['lastvisit'].",".$rt['lastpost'].")";
		$DDB->update($memberdata_update);
		$s_c++;
	}
	$insertadmin && $DDB->update("INSERT INTO {$pw_prefix}administrators (uid,username,groupid) VALUES ".substr($insertadmin, 0, -1));
	refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
}
elseif($step=='3')
{
	//板块数据
	$DDB->query("TRUNCATE TABLE {$pw_prefix}forums");
	$DDB->query("TRUNCATE TABLE {$pw_prefix}forumdata");
	$DDB->query("TRUNCATE TABLE {$pw_prefix}forumsextra");

	$insertforumsextra = array();
	$query = $SDB->query("Select * From {$source_prefix}forum");
	while($fm = $SDB->fetch_array($query))
	{
		$ifsub = $fm['parentid']=='-1'?'0':'1';
		$childid = count(explode(',',$fm['childlist']))>1?'1':'0';
		if($fm['parentid']==-1)
		{
			$type = 'category';
		}
		else
		{
				$parentid = $SDB->get_value("Select parentid From {$source_prefix}forum Where id=".$fm['parentid']);
				$type = $parentid=='-1'?'forum':'sub';
		}
		$mxb_username_t = forumAdminName($fm['id']);
		$forumadmin = $mxb_username_t ? ','.$mxb_username_t :'';
		$mxb_username_p = forumAdminName($fm['parentid'],$mxb_username);
		$fupadmin = $mxb_username_p ? ','.$mxb_username_p :'';
		$password = $fm['password'] ? md5($fm['password']):$fm['password'];
		switch ($fm['moderatepost'])
		{
				case "0":
					$f_check = '0';
					break;
				case "1":
					$f_check = '3';
					break;
				case "2":
					$f_check = '1';
					break;
				case "3":
					$f_check = '2';
					break;
		}
		$t_type = '';
		if($fm['specialtopic'])
		{
			$sptop = explode(',',$fm['specialtopic']);
			for($i=0;$i<count($sptop);$i++)
			{
				$typename .= $SDB->get_value("Select name From {$source_prefix}specialtopic Where id =".$sptop[$i]) . "\t";
			}
			$t_type = "1\t".$typename;
			$ft_typeid .= $fm['id'] . "|" .$fm['specialtopic'] . "%";
			$typename = '';
		}
		$insert_forum = "Insert into {$pw_prefix}forums (fid,fup,ifsub,childid,type,name,descrip,forumadmin,fupadmin,style,across,password,f_check,t_type) values (".$fm['id'].",".$fm['parentid'].",".$ifsub.",".$childid .",'".$type."','".$fm['name']."','".$fm['description']."','".$forumadmin."','".$fupadmin."','wind',".$fm['forumcolumns'].",'".$password."',".$f_check.",'".$t_type."')";
		$DDB->update($insert_forum);
		$insert_forumdata = "Insert Into {$pw_prefix}forumdata (fid,tpost,topic,article) values (".$fm['id'].",".$fm['this_thread'].",".$fm['thread'].",".$fm['post'].")";
		$DDB->update($insert_forumdata);
		$insertforumsextra['orderway'] = 'lastpost';
		$insertforumsextra['asc'] = 'DESC';
		$insertforumsextra['link'] = $fm['url'];
		$insertforumsextra['lock'] = $insertforumsextra['cutnums'] = $insertforumsextra['threadnum'] = $insertforumsextra['readnum'] = $insertforumsextra['newtime'] = $insertforumsextra['allowencode'] = $insertforumsextra['inspect'] = $insertforumsextra['commend'] = $insertforumsextra['autocommend'] = $insertforumsextra['rvrcneed'] = $insertforumsextra['moneyneed'] = $insertforumsextra['creditneed'] = $insertforumsextra['postnumneed'] = '0';
		$insertforumsextra['sellprice'] = array();
		$addtpctype = $fm['specialtopic']?'1':'0';
		$insertforumsextra['addtpctype'] = $addtpctype;
		$insertforumsextra['anonymous'] = $insertforumsextra['dig'] = '0';
		$insertforumsextra['commendnum'] = '0';
		$insertforumsextra['commendlength'] = '0';
		$insertforumsextra['commendtime'] = '0';
		$insertforumsextra['watermark'] = '0';
		$forumsextra .= "(".$fm['id'].",'','".addslashes(serialize($insertforumsextra))."',''),";
		$forumsextra && $DDB->update("INSERT INTO {$pw_prefix}forumsextra (fid,creditset,forumset,commend) VALUES ".substr($forumsextra, 0, -1));
		$forumsextra = '';
		$s_c++;
	}
	$ft_typeid = substr($ft_typeid, 0, -1);
	writeover(S_P.'tmp_typeinfo.php', "\$_typeinfo='".$ft_typeid."';",true);
	report_log();
	newURL($step);
}
elseif($step=='4')
{
	//主题数据
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}threads");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}tmsgs");
	}
	$tid = $SDB->get_one("SELECT tid FROM {$source_prefix}thread LIMIT $start,1");
	if (!$tid)
	{
		report_log();
		newURL($step);
	}
	require_once(S_P.'tmp_typeinfo.php');


	$query = $SDB->query("Select t.stopic,t.tid,t.title,t.forumid,t.postusername,t.postuserid,t.visible,t.lastpost,t.lastposter,t.post,t.sticky,t.open,t.quintessence,t.attach,p.dateline,p.pid,p.host,p.pagetext,p.hidepost,p.displayuptlog From {$source_prefix}thread t left join {$source_prefix}post p On t.tid = p.threadid Where p.newthread =1 LIMIT $start,$percount");

	while($th = $SDB->fetch_array($query))
	{
		RtFidTypeid($th['forumid'],$th['stopic'],$_typeinfo,$keyid);
		$locked = $th['open']=='0'?'2':'0';
		$anonymous = $th['postuserid']=='0'?'1':'0';
		$lastposter = $th['lastposter'];
		if($th['postuserid']=='0'){$lastposter='匿名';}
		$ifhide = $th['hidepost'] ? $th['hidepost']:'0';
		$insert_th = "Replace into {$pw_prefix}threads (tid,fid,author,authorid,subject,ifcheck,type,postdate,lastpost,lastposter,replies,topped,locked,digest,ifupload,anonymous,ifhide) Values (".$th['tid'].",".$th['forumid'].",'".$th['postusername']."',".$th['postuserid'].",'".addslashes($th['title'])."',".$th['visible'].",".$keyid.",".$th['dateline'].",".$th['lastpost'].",'".$lastposter."',".$th['post'].",".$th['sticky'].",".$locked.",".$th['quintessence'].",".$th['attach'].",".$anonymous.",".$ifhide.")";

		$DDB->update($insert_th);

		//取得附件信息
		$aid='';
		if($th['attach'])
		{
			$att = $SDB->query("SELECT * FROM {$source_prefix}attachment WHERE threadid = ".$th['tid']." And postid = ".$th['pid']);
			$attdata = array();
			while ($a = $SDB->fetch_array($att))
			{
				$attdata[$a['attachmentid']] = array('aid'=>$a['attachmentid'],'name'=>addslashes($a['filename']),'type'=>$a['extension'],'attachurl'=>addslashes($a['attachpath']."/".$a['location']),'needrvrc'=>0,'size'=>round($a['filesize']/1024),'hits'=>$a['counter'],'desc'=>0,'ifthumb'=>0);
			}
			if(count($attdata)>0)
			{
				$aid = addslashes(serialize($attdata));
			}
		}
		$alterinfo='';
		if($th['postuserid']!='1')
		{
			if($th['displayuptlog'])
			{
				$alterinfo = "此贴被".$th['updateuname']."在".get_date($th['updatetime'])."重新编辑";
			}
		}
		$th['pagetext'] = molyx_ubb($th['pagetext']);
		$ifconvert = (convert($th['pagetext']) == $th['pagetext']) ? 1 : 2;
		$DDB->update("INSERT INTO {$pw_prefix}tmsgs (tid,aid,userip,ifsign,ifconvert,content,alterinfo) Values (".$th['tid'].",'".$aid."','".$th['host']."',1,".$ifconvert.",'".addslashes($th['pagetext'])."','".$alterinfo."')");
		$s_c++;
	}
	refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
}
elseif($step=='5')
{
	//附件处理
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}attachs");
	}
	$atid = $SDB->get_one("SELECT attachmentid FROM {$source_prefix}attachment LIMIT $start,1");
	if (!$atid)
	{
		report_log();
		newURL($step);
	}
	$query = $SDB->query("Select * From {$source_prefix}attachment LIMIT $start,$percount");
	while($am=$SDB->fetch_array($query))
	{
		$fid = $SDB->get_value("Select forumid from {$source_prefix}thread Where tid = ".$am['threadid']);
		!$fid && $fid='0';
		$pid = $SDB->get_value("Select tid from {$source_prefix}thread Where tid = ".$am['postid']);
		$pid = !$pid ? $am['postid'] : '0';
		$attachurl = $am['userid']."/".$am['location'];
		$insert = "Insert Into {$pw_prefix}attachs (aid,fid,uid,tid,pid,name,type,size,attachurl,hits,uploadtime) values (".$am['attachmentid'].",".$fid.",".$am['userid'].",".$am['threadid'].",".$pid.",'".$am['filename']."','".$am['extension']."',".round($am['filesize']/1024).",'".addslashes($attachurl)."',".$am['counter'].",".$am['dateline'].")";
		$DDB->update($insert);
		$s_c++;
	}
	refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
}
elseif($step=='6')
{
	//回复主题处理
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}posts");
	}
	$pid = $SDB->get_one("SELECT pid FROM {$source_prefix}post LIMIT $start,1");
	if (!$pid)
	{
		report_log();
		newURL($step);
	}

	$query = $SDB->query("Select * From {$source_prefix}post Where newthread<>1 LIMIT $start,$percount");
	while($pt=$SDB->fetch_array($query))
	{
		$fid = $SDB->get_value("Select forumid From {$source_prefix}thread Where tid = ".$pt['threadid']);
		!$fid && $fid = '0';
		//取得附件信息
		$aid=$alterinfo='';
		$att = $SDB->query("SELECT * FROM {$source_prefix}attachment WHERE postid = ".$pt['pid']);
		if($att)
		{
			$attdata = array();
			while ($a = $SDB->fetch_array($att))
			{
				$attdata[$a['attachmentid']] = array('aid'=>$a['attachmentid'],'name'=>addslashes($a['filename']),'type'=>$a['extension'],'attachurl'=>addslashes($a['attachpath']."/".$a['location']),'needrvrc'=>0,'size'=>round($a['filesize']/1024),'hits'=>$a['counter'],'desc'=>0,'ifthumb'=>0);
			}
			if(count($attdata)>0)
			{
				$aid = addslashes(serialize($attdata));
			}
		}
		if($pt['userid'] != '1')
		{
			if($pt['displayuptlog'])
			{
				$alterinfo = "此贴被".$pt['updateuname']."在".get_date($pt['updatetime'])."重新编辑";
			}
		}
		$pt['pagetext'] = molyx_ubb($pt['pagetext']);
		$ifhide = $pt['hidepost'] ? $pt['hidepost'] : '0';
		$ifconvert = (convert($pt['pagetext']) == $pt['pagetext']) ? 1 : 2;

		$DDB->update("Insert into {$pw_prefix}posts (pid,fid,tid,aid,author,authorid,postdate,userip,ifsign,alterinfo,ifcheck,content,anonymous,ifhide,ifconvert) values (".$pt['pid'].",".$fid.",".$pt['threadid'].",'".$aid."','".$pt['username']."',".$pt['userid'].",".$pt['dateline'].",'".$pt['host']."',".$pt['showsignature'].",'".$alterinfo."',1,'".addslashes($pt['pagetext'])."',".$pt['anonymous'].",".$ifhide.",".$ifconvert.")");
		$s_c++;
	}
	refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
}
elseif($step=='7')
{
	//友情连接
	$DDB->query("TRUNCATE TABLE {$pw_prefix}sharelinks");
	require_once S_P.'lang_'.$dest_charset.'.php';
	$query = $SDB->query("SELECT * FROM {$source_prefix}league ORDER BY leagueid");
	$insert = '';
	while($link = $SDB->fetch_array($query))
	{
		if (strpos(strtolower($link['sitename']), 'discuz') === FALSE)
		{
			$insert .= "(".$link['displayorder'].",'".addslashes($link['sitename'])."', '".addslashes($link['siteurl'])."','".addslashes($link['siteinfo'])."','".addslashes($link['siteimage'])."', 1),";
			$s_c++;
		}
	}
	$insert .= $lang['link'];
	$DDB->update("INSERT INTO {$pw_prefix}sharelinks (threadorder, name, url, descrip, logo, ifcheck) VALUES ".$insert);

	report_log();
	newURL($step);
}
elseif($step=='8')
{
	//短信处理
	if(!$start)
	{
		$DDB->update("TRUNCATE TABLE {$pw_prefix}msg");
		$DDB->update("TRUNCATE TABLE {$pw_prefix}msgc");
	}
	$pmid = $SDB->get_one("Select pmid From {$source_prefix}pm LIMIT $start,1");
	if(!$pmid)
	{
		report_log();
		newURL($step);
	}
	$mxb_query = $SDB->query("Select * From {$source_prefix}pm LIMIT $start,$percount");
	while($rt = $SDB->fetch_array($mxb_query))
	{
			$usergroupid = $rt['usergroupid'];
			$fromuid = $rt['fromuserid'];
			if($rt['folderid']=='-1')
			{
				$userid = $rt['fromuserid'];
				$type = "sebox";
			}
			elseif($rt['folderid']=='0')
			{
				$userid = $rt['touserid'];
				$type = "rebox";
			}
			$username = $SDB->get_value("Select name From {$source_prefix}user Where id =".$userid);
			if($usergroupid)
			{
					$usergroupid = ",".$rt['usergroupid'].",";
					$fromuid = '0';
					$username = 'SYSTEM';
					$type = "public";
			}
			$content = $SDB->get_value("Select message From {$source_prefix}pmtext Where pmtextid =".$rt['messageid']);
			$DDB->update("Insert into {$pw_prefix}msg (mid,touid,togroups,fromuid,username,type,ifnew,mdate) values(".$rt['pmid'].",".$rt['touserid'].",'".$usergroupid."',".$fromuid.",'".$username."','".$type."',".$rt['pmread'].",".$rt['dateline'].")");
			$DDB->update("Insert into {$pw_prefix}msgc (mid,title,content) values (".$rt['pmid'].",'".addslashes($rt['title'])."','".addslashes($content)."')");
		$s_c++;
	}
	refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
}
elseif($step=='9')
{
	//头像处理
	$pw_avatar = R_P."pwavatar";
	$user = R_P."user";
	if(!$start)
	{
		if(!is_dir($pw_avatar) || !N_writable($pw_avatar) || !is_dir($user))
		{
			ShowMsg('用于转换头像的user 或pwavatar 目录不存在或者无法写入，<br /><br />1、请将 Molyx安装目录/data/uploads 下的 user 目录复制到 PWBuilder 根目录2、请在pwbuilder根目录下建立 pwavatar 目录，并将其属性设定为777。', true);
		}
		if(is_dir($pw_avatar) && is_readable($pw_avatar))
		{
			$dirname = array();
			PWListDir($user, $dirname);
			$insert = "<?php\r\n!defined('R_P') && exit('Forbidden!');\r\n\$d = array(";
			foreach ($dirname as $v)
			{
				$insert .= "'".addcslashes($v, '\\\'')."',";
			}
			$insert .= ");?>";
			writeover(S_P.'tmp_avatar.php', $insert);
		}
		else
		{
			ShowMsg('头像处理无法完成，user目录无法读取。');
		}
	}
	require_once(S_P.'tmp_avatar.php');
	if ($start >= count($d))
	{
		report_log();
		newURL($step);
	}
	$savedir = 'mxb';
	$dh = opendir($d[$start]);
	while(($file = readdir($dh)) !== false)
	{
		if($file != '.' && $file != '..')
		{
			$img = explode('.',$file);
			if($img[1]=='jpg' || $img[1]=='gif' || $img[1]=='bmp' || $img[1]=='png' || $img[1]=='jpeg')
			{
				$Arrid = explode('-',$img[0]);
				if($Arrid[2]=='0')
				{
					$uid = $Arrid[1];
					if(!is_dir($pw_avatar."/".$savedir))
					{
						@mkdir($pw_avatar.'/'.$savedir);
						@chmod($pw_avatar.'/'.$savedir,0777);
					}
					@copy($d[$start].'/'.$file, $pw_avatar.'/'.$savedir.'/'.$uid.'.jpg');
				}
				$s_c++;
			}
		}
	}
	$end = ++$start;
	refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
}
elseif($step=='10')
{
	//自定义积分
	$DDB->query("TRUNCATE TABLE {$pw_prefix}credits");

	$credits = $ncredits = array();
	$ncredits['Digest'] = '20';
	$ncredits['Post'] = '10';
	$ncredits['Reply'] = '10';
	$ncredits['Undigest'] = '20';
	$ncredits['Delete'] = '10';
	$ncredits['Deleterp'] = '10';
	$credits['rvrc'] = $ncredits;
	$credits['money'] = $ncredits;
	$Query = $SDB->query("Select c.*,ct.* From {$source_prefix}credit c Left Join {$source_prefix}creditrule ct USING(creditid) Where ct.type=0 and c.creditid<>1");
	while($cdt = $SDB->fetch_array($Query))
	{
		$insert_cdt = "Insert Into {$pw_prefix}credits (cid,name,unit)values(".$cdt['creditid'].",'".$cdt['name']."','".$cdt['unit']."')";
		$DDB->update($insert_cdt);

		$mxb_credit = unserialize($cdt['parameters']);
		$ncredits['Digest'] = $mxb_credit['quintessence'];
		$ncredits['Post'] = $mxb_credit['newthread'];
		$ncredits['Reply'] = $mxb_credit['newreply'];
		$ncredits['Undigest'] = $mxb_credit['quintessence'];
		$ncredits['Delete'] = $mxb_credit['delthread'];
		$ncredits['Deleterp'] = $mxb_credit['newreply'];
		$credits[$cdt['creditid']] = $ncredits;
		if($cdt['tag']!='reputation')
		{
			$Query_uxp = $SDB->query("Select id,".$cdt['tag']." From {$source_prefix}userexpand");
			while($uxp = $SDB->fetch_array($Query_uxp))
			{
				$insert_mc = "Insert Into {$pw_prefix}membercredit(uid,cid,value)values(".$uxp['id'].",".$cdt['creditid'].",".round($uxp["".$cdt['tag'].""]).")";
				$DDB->update($insert_mc);
			}
		}
		$s_c++;
	}
	$DDB->update("Update {$pw_prefix}config set db_value = '".addslashes(serialize($credits))."' Where db_name = 'db_creditset'");
	report_log();
	newURL($step);
}
else
{
	ObHeader($basename.'?action=finish&dbtype='.$dbtype);
}

//取出目标文件夹下的所有文件路径
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

//molyx安全问题
function questcode($question,$customquest,$answer){

	$question = $question=='-1' ? $customquest : $question;
	return $question ? substr(md5(md5($question).md5($answer)),8,10) : '';
}

//转换用户组
function changegroups()
{
	global $pw_prefix, $source_prefix, $SDB, $DDB, $dest_charset;

	require_once S_P.'lang_'.$dest_charset.'.php';
	$DDB->update("TRUNCATE TABLE {$pw_prefix}usergroups");
	$DDB->update($lang['group']);
	$query = $SDB->query("SELECT * FROM {$source_prefix}usergroup WHERE usergroupid>7");
	$lang = array(
		'fieldusergroup8_title' => '见习版主',
		'fieldusergroup9_title' => '管理员',
		'fieldusergroup9_rank' => '管理员',
		'fieldusergroup10_title' => '见习版主',
		'fieldusergroup10_rank' => '版主',
		'fieldusergroup11_title' => '贵宾',
		'fieldusergroup11_rank' => '版主',
		'fieldusergroup12_title' => '管理员1',
		'fieldusergroup12_rank' => '管理员',
		'fieldusergroup13_title' => '超级版主',
		'fieldusergroup13_rank' => '超级版主',
	);
	$_specialdata = array();
	while ($rt = $SDB->fetch_array($query))
	{
		$rt['readaccess'] && $rt['readaccess'] = 1;
		$rt['maxprice'] && $rt['maxprice'] = 1;
		$allowsearch = $rt['cansearch']?$rt['cansearch']:'0';
		$allowsearch && $rt['cansearchpost'] && $allowsearch=2;
		$allowmember = $rt['canviewmember'];
		$allowprofile = $rt['canviewmember'];

		$mright = array();
		$mright['atclog'] = $mright['show'] = $mright['msggroup'] = $mright['ifmemo'] = $mright['modifyvote'] = $mright['viewvote'] = $mright['allowreward'] = $mright['allowencode'] = $mright['leaveword'] = $mright['viewvote'] = $mright['viewvote'] = 1;
		$mright['viewipfrom'] = $mright['anonymous'] = $mright['dig'] = $mright['atccheck'] = $mright['markable'] = $mright['postlimit'] = 0;
		$mright['imgwidth'] = $mright['imgheight'] = $mright['fontsize'] = $mright['maxsendmsg'] = $mright['maxfavor'] = $mright['maxgraft'] = '';
		$mright['uploadtype'] =  '';
		$mright['media']  = $mright['pergroup'] = '';
		$mright['markdb'] = "";
		$mright['schtime'] = '';
		$mright = P_serialize($mright);



		pwGroupref(array('gid'=>$rt['usergroupid'],
									'gptype'=>'special',
									'grouptitle'=>$lang[$rt['grouptitle']],
									'groupimg'=>8,
									'grouppost'=>0,
									'maxmsg'=>$rt['pmquota'],
									'allowhide'=>1,
									'allowread'=>1,
									'allowportait'=>1,
									'upload'=>$rt['canuseavatar'],
									'allowrp'=>$rt['canreplyothers'],
									'allowdelatc'=>$rt['candeletepost'],
									'allowpost'=>$rt['canpostnew'],
									'allownewvote'=>$rt['canpostpoll'],
									'allowvote'=>$rt['canvote'],
									'htmlcode'=>$rt['canposthtml'],
									'allowsearch'=>$allowsearch,
									'allowmember'=>$allowmember,
									'allowprofile'=>$allowprofile,
									'allowmessage'=>1,
									'allowupload'=>1,
									'allowdownload'=>$rt['candownload'],
									'edittime'=>$rt['edittimecut'],
									'searchtime'=>$rt['searchflood'],
									'mright'=>$mright,
									'ifdefault'=>0,
									'viewclose'=>$rt['canpostclosed'],
									'postpers'=>$rt['passflood'],
									'sright'=>''));
		$grouptitle=getGrouptitle($gid,$grouptitle,false);
		$DDB->update("INSERT INTO {$pw_prefix}usergroups (gid,gptype,grouptitle,groupimg,grouppost) VALUES ('$gid','$gptype','$grouptitle','$groupimg','$grouppost')");

		$_specialdata[$rt['usergroupid']] = $DDB->insert_id();
	}

	/*
	$query = $SDB->query("SELECT * FROM {$source_prefix}usertitle");
	while ($rt = $SDB->fetch_array($query))
	{
		$DDB->update("INSERT INTO {$pw_prefix}usergroups (gptype,grouptitle,groupimg,grouppost,maxmsg,allowhide,allowread,allowportait,upload,allowrp,allowhonor,allowdelatc,allowpost,allownewvote,allowvote,allowactive,htmlcode,wysiwyg,allowhidden,allowencode,allowsell,allowsearch,allowmember,allowprofile,allowreport,allowmessege,allowsort,alloworder,allowupload,allowdownload,allowloadrvrc,allownum,edittime,postpertime,searchtime,signnum,mright,ifdefault,allowadmincp,visithide,delatc,moveatc,copyatc,typeadmin,viewcheck,viewclose,attachper,delattach,viewip,markable,maxcredit,credittype,creditlimit,banuser,bantype,banmax,viewhide,postpers,atccheck,replylock,modown,modother,deltpcs,sright) VALUES


		('member', '".addslashes($rt['title'])."', '8', ".$rt['post'].", 10, 0, 1, 0, 0, 1, 0, 1, 1, 0, 1, 0, 0, 0, 1, 0, 0, 1, 0, 0, 1, 1, 0, 1, 1, 1, 0, 50, 0, 0, 10, 30, 'show	0\n1\nviewipfrom	0\n1\nimgwidth	\n1\nimgheight	\n1\nfontsize	3\n1\nmsggroup	0\n1\nmaxfavor	50\n1\nviewvote	0\n1\natccheck	1\n1\nmarkable	0\n1\npostlimit	\n1\nuploadmaxsize	0\n1\nuploadtype	\n1\nmarkdb	|||', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '', '', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '')");

		pwGroupref(array('gptype'=>'member','grouptitle'=>addslashes($rt['title']),'groupimg'=>8,'grouppost'=>$rt['post'],'maxmsg'=>10,'allowhide'=>0,'allowread'=>1,'allowportait'=>0,'upload'=>0,'allowrp'=>1,'allowhonor'=>0,'allowdelatc'=>1,'allowpost'=>1,'allownewvote'=>0,'allowvote'=>1,'allowactive'=>0,'htmlcode'=>0,'wysiwyg'=>0,'allowhidden'=>1,'allowencode'=>0,'allowsell'=>0,'allowsearch'=>1,'allowmember'=>0,'allowprofile'=>0,'allowreport'=>1,'allowmessage'=>1,'allowsort'=>0,'alloworder'=>1,'allowupload'=>1,'allowdownload'=>1,'allowloadrvrc'=>0,'allownum'=>50,'edittime'=>0,'postpertime'=>0,'searchtime'=>10,'signnum'=>30,'mright'=>'show	0\n1\nviewipfrom	0\n1\nimgwidth	\n1\nimgheight	\n1\nfontsize	3\n1\nmsggroup	0\n1\nmaxfavor	50\n1\nviewvote	0\n1\natccheck	1\n1\nmarkable	0\n1\npostlimit	\n1\nuploadmaxsize	0\n1\nuploadtype	\n1\nmarkdb	|||','ifdefault'=>0,'allowadmincp'=>0,'visithide'=>0,'delatc'=>0,'moveatc'=>0,'copyatc'=>0,'typeadmin'=>0,'viewcheck'=>0,'viewclose'=>0,'attachper'=>0,'delattach'=>0,'viewip'=>0,'markable'=>0,'maxcredit'=>0,'credittype'=>'','creditlimit'=>'','banuser'=>0,'bantype'=>0,'banmax'=>0,'viewhide'=>0,'postpers'=>0,'atccheck'=>0,'replylock'=>0,'modown'=>0,'modother'=>0,'deltpcs'=>0,'sright'=>''));
		//$grouptitle=getGrouptitle($gid,$grouptitle,false);
		$DDB->update("INSERT INTO {$pw_prefix}usergroups (gptype,grouptitle,groupimg,grouppost) VALUES ('member','".addslashes($rt['title'])."','8','".$rt['post']."')");
	}*/
	if ($_specialdata)
	{
		return serialize($_specialdata);
	}
}
//根据论坛ID取得版主
function forumAdminName($forumid)
{
	global $source_prefix, $SDB, $DDB;
	$mxb_username = '';
	$mxb_query = $SDB->query("Select username From {$source_prefix}moderator Where forumid=".$forumid);
	while($fm_mxb = $SDB->fetch_array($mxb_query))
	{
		$mxb_username .= $fm_mxb['username'] . ",";
	}
	return $mxb_username;
}
//取板块分类
function ReturnForumType($specialid)
{
	global $source_prefix, $SDB, $DDB;
	$typename='';
	$sptop = explode(',',$specialid);
	if(count($sptop)<=1){return $typename;}
	for($i=0;$i<count($sptop);$i++)
	{
		$typename .= $SDB->get_value("Select name From {$source_prefix}specialtopic Where id =".$sptop[$i]) . "\t";
	}
	return $typename;
}

//取对应的主题分类ID
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

function get_date($timestamp,$timeformat=null){
	return gmdate('Y-m-d H:i',$timestamp+8*3600);
}

function molyx_ubb($content)
{
	$patterns_str = array('[flash]','[/flash]','[rm]','[ra]','[code=sql]','[code=php]','[code=xml]','[code=css]','[code=javascript]','[code=c]','[code=java]','[code=c#]','[code=ruby]','[code=python]','[code=vb]','[/ra]','[real]','[music]','[/music]','[/real]','[hide]','[/hide]');
	$replacements_str = array('[flash=314,256]','[/flash]','[rm=314,256,0]','[rm=314,256,0]','[code]','[code]','[code]','[code]','[code]','[code]','[code]','[code]','[code]','[code]','[code]','[/rm]','[wmv=314,256,0]','[wmv=0]','[/wmv]','[/wmv]','[post]','[/post]');
	$patterns = array('/\[localimg=[0-9]+,[0-9]+\]([0-9]+)\[\/localimg\]/i','/\[local\]([0-9]+)\[\/local\]/i','/\[attach\]([0-9]+)\[\/attach\]/i','/\[img=[0-9]+,[0-9]+\]/i','/\[size=(\d+(\.\d+)?(px|pt|in|cm|mm|pc|em|ex|%)+?)\]/i');
	$replacements = array('[attachment=\\1]','[attachment=\\1]','[attachment=\\1]','[img]','');
	$content = preg_replace($patterns, $replacements, str_replace($patterns_str, $replacements_str, $content));
	return $content;
}