<?php
/**
*
*  Copyright (c) 2003-06  PHPWind.net. All rights reserved.
*  Support : http://www.phpwind.net
*  This software is the proprietary information of PHPWind.com.
*  Code by rickyleo (liuriqi@gmail.com)
*
*/
!defined('R_P') && exit('Forbidden!');

$db_table = $step_data[$step];

if ($step == '1')
{
	//论坛信息资料
	$db_table = '论坛信息资料';
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}bbsinfo");
	}
	
	$query = $SDB->query("SELECT * FROM {$source_prefix}bbs_info");
	$x = $SDB->fetch_array($query);
	
	$DDB->update("REPLACE INTO {$pw_prefix}config (db_name,vtype,db_value) VALUES ('db_bbsname','string','".$x['mc']."')");
	$DDB->update("REPLACE INTO {$pw_prefix}config (db_name,vtype,db_value) VALUES ('db_bbsurl','string','".$x['url']."')");
	$DDB->update("REPLACE INTO {$pw_prefix}bbsinfo (newmember,totalmember,hposts) VALUES ('".$x['huiyuan']."','".$x['s1']."','".$x['s4']."')");
	
	$s_c++;
	report_log();
	newURL($step, '&medal=yes');
}
elseif ($step == '2')
{
	//转换管理员
	$db_table = '管理员数据';
	if (!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}administrators");
	}

	$query = $SDB->query("SELECT * FROM {$source_prefix}bbs_admin WHERE id between $start AND $end");	
	while($rs = $SDB->fetch_array($query))
	{	
		strip_space($rs);
		$DDB->update("REPLACE INTO {$pw_prefix}administrators(`uid`, `username`, `groupid`, `groups`, `slog`) VALUES (".$rs[id].", '".$rs[huiyuan]."', 3, '', '".time()."')");
		$s_c++;
	}
	
	$maxid = $SDB->get_value("SELECT max(id) AS maxid FROM {$source_prefix}bbs_admin");
	echo "最大id:".$maxid."<br>现在id:".$end."<br>";
	if ($end <= $maxid)
	{
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c.'&medal='.$medal);
	}
	else
	{
		report_log();
		newURL($step);
	}
}
elseif ($step == '3')
{
	//普通会员数据
	$db_table = '普通会员数据';
	require_once (S_P.'tmp_info.php');
	if (!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}members");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}memberdata");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}customfield");
		$DDB->query("REPLACE INTO `{$pw_prefix}customfield` (`id`, `title`, `maxlen`, `vieworder`, `type`, `state`, `required`, `viewinread`, `editable`, `descrip`, `viewright`, `options`) VALUES (1, '真实姓名', 0, 0, 1, 1, 1, 1, 1, '', '', ''),(2, '邮编', 0, 0, 1, 1, 1, 1, 1, '', '', ''),(3, '手机号码', 0, 0, 1, 1, 1, 1, 1, '', '', '');");
		
		//扩展字段长度
		$DDB->query("ALTER TABLE `{$pw_prefix}members` CHANGE `username` `username` VARCHAR( 20 ) NOT NULL DEFAULT ''");
		$DDB->query("ALTER TABLE `{$pw_prefix}memberinfo` ADD `field_1` varchar(255) NOT NULL");
		$DDB->query("ALTER TABLE `{$pw_prefix}memberinfo` ADD `field_2` varchar(255) NOT NULL");
		$DDB->query("ALTER TABLE `{$pw_prefix}memberinfo` ADD `field_3` varchar(255) NOT NULL");
		
		//可能最小的id会很大,这样处理能迅速定位到最小的id
		$minid = $SDB->get_value("SELECT MIN(id) AS minid FROM {$source_prefix}huiyuan");
		$start = $minid;
		$end = $start+$percount;
	}
	
	$query = $SDB->query("SELECT * FROM {$source_prefix}huiyuan WHERE id BETWEEN $start AND $end");
	while ($m = $SDB->fetch_array($query))
	{	
		strip_space($m);
		Add_S($m);
	
		$members['uid'] = (int)$m['id'];
		$members['username'] = addslashes($m['huiyuan']);
		if (htmlspecialchars($members['username'])!=$members['username'] || CK_U($members['username']))
		{
			$f_c++;
			errors_log($members[0]."\t".$members['username']);
			continue;
		}
		
		$members['password'] = $m['password'];
		$members['email'] = $m['mail'];
		$members['location'] = $m['dz'];
		list($none,$face) = explode("/",$m['img']);
		$userface = $members['icon'] = $face."|1|||";
		$members['signature'] = $m['txt'];
		if(str_replace('[img]','',$members['signature']) != $members['signature'])
		{
			//此处替换看情况而定
			$members['signature'] = preg_replace("/\[img\]\/tp\/(.+?)\[\/img\]/","[img]http://$_SERVER[HTTP_HOST]/attachment/\\1[/img]",$members['signature']);
		}
		$members['yz'] = 1;
		$members['regdate'] = get_timestamp($m['sj']);
		$membsers['gender'] = 0;
		$medals = "";
		$groupid = 1;

		$memberdata['uid'] = (int)$m['id'];
		$memberdata['onlineip'] = $m['ip']."|".time()."|0";
		$memberdata['money'] = $m[$money];
		$memberdata['onlinetime'] = $m['js']*24*3600;
        $memberdata['lastvisit'] = time();
        $memberdata['thisvisit'] = time();

		$memberinfo['field_1'] = $m['name'];//真实姓名
		$memberinfo['field_2'] = $m['yb'];//邮编
		$memberinfo['field_3'] = $m['tel'];//电话
		
		foreach ($memberinfo as $key => $value)
		{
			if(!$value)
			{
				$memberinfo[$key] = '0';
			}
		}

		if(strlen($members['password']) > 16)
		{
			$password = substr($members['password'],8,16);
		}

		$DDB->update("REPLACE INTO {$pw_prefix}members (uid,username,password,medals,email,groupid,icon,gender,regdate,signature,introduce,oicq,icq,msn,yahoo,site,location,honor,bday,yz,style,t_num,p_num,newpm,userstatus,banpm) VALUES (".$members['uid'].",'".$members['username']."','".$password."','".$medals."','".addslashes($members['email'])."',".$groupid.",'".addslashes($userface)."',".$membsers['gender'].",'".$members['regdate']."','".addslashes($members['signature'])."',' ',' ',' ',' ',' ',' ','".addslashes($members['location'])."',' ',' ',1,'wind',0,0,0,' ','')");
		$DDB->update("REPLACE INTO {$pw_prefix}memberdata (uid,money,onlineip,onlinetime,lastvisit,thisvisit) VALUES (".$memberdata['uid'].",".$memberdata['money'].",'".addslashes($memberdata['onlineip'])."',".$memberdata['onlinetime'].",".$memberdata['lastvisit'].",".$memberdata['thisvisit'].")");
		$DDB->update("REPLACE INTO {$pw_prefix}memberinfo (uid,field_1,field_2,field_3) VALUES (".$memberdata['uid'].",'".$memberinfo['field_1']."','".$memberinfo['field_2']."','".$memberinfo['field_3']."')");
		
		$s_c++;
	}

	$maxid = $SDB->get_value("SELECT max(id) AS maxid FROM {$source_prefix}huiyuan");
	echo "最大id:".$maxid."<br>现在id:".$end."<br>";
	if ($end <= $maxid)
	{
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c.'&medal='.$medal);
	}
	else
	{	
		report_log();
		newURL($step);
	}
}
elseif ($step == '4')
{
	//版块数据
	$db_table = '版块数据';
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}forums");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}forumdata");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}forumsextra");
	}
	
	$forumdb = array();
	
	$query = $SDB->query("SELECT * FROM {$source_prefix}bbs_lb");
	while($rt = $SDB->fetch_array($query))
	{
		strip_space($rt);
		Add_S($rt);
		
		$forumdb[$rt['lbid']] = $rt;
	}
	
	foreach($forumdb as $fid => $forum)
	{	
		switch ($forum['qid'])
		{
			case '0':$ftype = 'category';break;
			default:$ftype = 'forum';break;
		}
		$ifsub = ($ftype == 'sub') ? '1' : '0';
		$childid = 0;
		$forum['icon'] = "";
		
		$DDB->update("REPLACE INTO {$pw_prefix}forums (fid,fup,ifsub,childid,type,logo,name,descrip,vieworder,forumadmin,fupadmin,across,allowsell,copyctrl,allowpost,allowrp,allowdownload,allowupload,f_check,ifhide,allowtype,t_type) VALUES (".$fid.",".$forum['qid'].",".$ifsub.",".$childid.",'".$ftype."','".addslashes($forum['icon'])."','".addslashes(substrs(preg_replace('/<\/?[^\>]*>/im','',$forum['lbname']),50,0))."','".addslashes($forum['lbsm'])."',".$forum['id'].",' ',' ',' ',' ','1','1','1','1','1','0','0',31,' ')");
		$DDB->update("REPLACE INTO {$pw_prefix}forumdata (fid,tpost,topic,article) VALUES (".$fid.",'0',".$forum['s2'].",".$forum['s3'].")");
		$DDB->update("REPLACE INTO {$pw_prefix}forumsextra (fid,creditset,forumset,commend) VALUES ('".$fid."','','','')");
		
		$s_c++;
	}
	writeover(S_P.'tmp_typeinfo.php', "\$_typeinfo='".$ft_typeid."';",true);
	report_log();
	newURL($step);
}
elseif ($step == '5')
{
	//主题标题数据
	$db_table = '主题标题数据';
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}threads");
		
		//可能最小的id会很大,这样处理能迅速定位到最小的id
		$minid = $SDB->get_value("SELECT MIN(id) AS minid FROM {$source_prefix}bbs");
		$start = $minid;
		$end = $start+$percount;
	}
	
	$query = $SDB->query("SELECT * FROM {$source_prefix}bbs WHERE id BETWEEN $start AND $end");
	while($t = $SDB->fetch_array($query))
	{	
		strip_space($t);
		Add_S($t);
		
		$ifcheck = '1';
		$topped = '0';

		$t['huiyuanid'] = $SDB->get_value("SELECT id FROM {$source_prefix}huiyuan WHERE huiyuan='".$t['huiyuan']."'");
		
		$t['sj'] = get_timestamp($t['sj']);
		$t['sj2'] = get_timestamp($t['sj2']);

		$DDB->update("REPLACE INTO {$pw_prefix}threads (tid,fid,icon,author,authorid,subject,ifcheck,postdate,lastpost,lastposter,hits,replies,topped,digest,ifupload,anonymous,type) VALUES (".$t['id'].",".$t['lbid'].",' ','".addslashes($t['huiyuan'])."',".(!$t['huiyuanid'] ? 0 : $t['huiyuanid']).",'".addslashes($t['bt'])."',1,'".$t['sj']."','".$t['sj2']."','".addslashes($t['huiyuan2'])."',".$t['js'].",".$t['js2'].",".$topped.",'0','0','0','0')");
		$s_c++;
	}
	
	$maxid = $SDB->get_value("SELECT max(id) AS maxid FROM {$source_prefix}bbs");
	echo "最大id:".$maxid."<br>现在id:".$end."<br>";
	if ($end <= $maxid)
	{
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
		report_log();
		newURL($step);
	}
}
elseif ($step == '6')
{
	//主题帖和回复帖数据
	$db_table = '主题帖和回复帖数据';
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}tmsgs");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}posts");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}tags");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}attachs");
		
		$max_table = $SDB->get_value("SELECT MAX(bbs_table) FROM {$source_prefix}bbs");
		for ($i=1;$i<=$max_table;$i++)
		{
			$_tablelist[$i] = $source_prefix."bbs_table".$i;
		}
		writeover(S_P.'tmp_tablelist.php', "\$_tablelist = ".var_export($_tablelist, TRUE).";",true);
	}
	require_once(S_P.'tmp_tablelist.php');
	
	$tableid = (int)$_GET['tableid'];
	!$tableid && (int)$tableid = 1;
	$truetableid = $_tablelist[$tableid];

	if(in_array($start,array('0','1'))){
		$minid = $SDB->get_value("SELECT MIN(id) AS minid FROM ".$truetableid);
		$start = $minid;
		$end = $start+$percount;
	}

	//echo "<font color=red>读取数据SQL</font>:<br><div style=\"border:1px solid #D6E8F4;background:#F0F9FF;margin:10px;\">SELECT b.id AS tid,b.lbid AS fid,b.js AS hits,b.js2 AS replies,b.huiyuan2 AS lastposter,b.sj AS postdate,b.sj2 AS lastpost,b.huiyuan2 AS lastposter,bt.huiyuan AS author,bt.bt AS title,bt.nr AS content,bt.sj AS btpostdate,bt.ip AS ip FROM <font color=red>{$source_prefix}bbs</font> b INNER JOIN <font color=red>{$truetableid}</font> bt <font color=blue>ON b.id=bt.bbsid</font> WHERE <font color=gray>b.bbs_table=".$tableid." AND bt.bbsid BETWEEN $start AND $end ORDER BY bt.id ASC</div>";
	$query = $SDB->query("SELECT b.id AS tid,b.lbid AS fid,b.js AS hits,b.js2 AS replies,b.huiyuan2 AS lastposter,b.sj AS postdate,b.sj2 AS lastpost,b.huiyuan2 AS lastposter,bt.huiyuan AS author,bt.bt AS title,bt.nr AS content,bt.sj AS btpostdate,bt.ip AS ip FROM {$source_prefix}bbs b INNER JOIN {$truetableid} bt ON b.id=bt.bbsid WHERE b.bbs_table=".$tableid." AND bt.id BETWEEN $start AND $end");
	while($p = $SDB->fetch_array($query))
	{	
		strip_space($p);
		Add_S($p);
		$aid = $ifupload = '';
		
		$p['authorid'] = $SDB->get_value("SELECT id FROM {$source_prefix}huiyuan WHERE huiyuan='".$p['author']."'");
		$p['postdate'] = get_timestamp($p['postdate']);//主题发表时间
		$p['lastpost'] = get_timestamp($p['lastpost']);//主题最后回复时间
		
		//主题或者回复发表时间,和上面主题时间可能相等正常,插回复时间用这个
		$p['btpostdate'] = get_timestamp($p['btpostdate']);        

		if(str_replace("tag]","",$p['content']) != $p['content'])
		{
			//标签匹配
			preg_match("/\[tag\](.*)\[\/tag\]/",$p['content'],$tags);			
			$p['tags'] = str_replace(',',' ',$tags['1']);
			$p['content'] = preg_replace("/\[tag\](.*)\[\/tag\]/","",$p['content']);
			
			//标签转换
			$tag_array = explode(',',$tags['1']);
			foreach ($tag_array AS $key=>$value)
			{
				$num = $DDB->get_value("SELECT COUNT(*) FROM {$pw_prefix}tags WHERE tagname=".pwEscape($value));
				if($num)
				{
					$DDB->update("UPDATE {$pw_prefix}tags SET num=num+1 WHERE tagname=".pwEscape($value));
				}else 
				{
					$DDB->update("INSERT INTO {$pw_prefix}tags(tagname,num) VALUES ('".$value."','1')");
				}
			}
		}
		
		//处理帖子中是否含有附件,可以通过内容标签识别
		if(str_replace("[img]","",$p['content']) != $p['content'])
		{
			$p['attachment'] = 1;
			
			//转换标签处理,因为[img]为pw的图片标签
			preg_match_all("/\[img\]\/tp\/(.+?)\[\/img\]/eis",$p['content'],$image);
			$des_image = $image['1'];

			$p['content'] = preg_replace("/\[img\](.+?)\.\.(.+?)\[\/img\]/","[img]\\1\\2[/img]",$p['content']);
		}else
		{
			$p['attachment'] = 0;
		}
		
        $p['content'] = str_replace(array('[u]','[/u]','[b]','[/b]','[i]','[/i]','[list]','[li]','[/li]','[/list]','[sub]', '[/sub]','[sup]','[/sup]','[strike]','[/strike]','[blockquote]','[/blockquote]','[hr]','[/backcolor]', '[/color]','[/font]','[/size]','[/align]','<br>','<Br>','<br />','<Br />','[hide]','[/hide]','</span>'), array('<u>','</u>','<b>','</b>','<i>','</i>','<ul style="margin:0 0 0 15px">','<li>', '</li>','</ul>','<sub>','</sub>','<sup>','</sup>','<strike>','</strike>','<blockquote>','</blockquote>', '<hr />','</span>','</span>','</span>','</font>','</div>','
        ','
        ','
        ','
        ','[post]','[/post]','[/color]'),$p['content']);

		//bbs_table数据表根据标题中是否有Re字符来判断是主题还是回复
		if(str_replace("Re","",$p['title']) != $p['title'])
		{
			//回复数据
			$DDB->update("REPLACE INTO {$pw_prefix}posts (fid,tid,aid,author,authorid,postdate,subject,userip,ifsign,buy,ifconvert,ifcheck,content,ifshield,anonymous) VALUES (".$p['fid'].",".$p['tid'].",'".$p['attachment']."','".addslashes($p['author'])."',".(!$p['authorid'] ? 0 : $p['authorid']).",".$p['btpostdate'].",'".addslashes($p['title'])."','".$p['ip']."','1','','2','1','".addslashes($p['content'])."','0','0')");
			//获取自增pid
			$pid = $DDB->insert_id();
			
			//替换附件标签
			if($des_image)
			{
				for ($i=0;$i<count($des_image);$i++)
				{
					$p['authorid'] && $DDB->update("INSERT INTO {$pw_prefix}attachs(fid,uid,tid,pid,type,attachurl,uploadtime) VALUES (".$p['fid'].",".$p['authorid'].",".$p['tid'].",".$pid.",'img',".pwEscape($des_image[$i]).",".$p['postdate'].")");
					$aid = $DDB->insert_id();
					$p['content'] = str_replace("[img]/tp/".$des_image[$i]."[/img]","[attachment=".$aid."]",$p['content']);
				}

                //附件标签替换成功,更新回复内容数据
				$DDB->update("UPDATE {$pw_prefix}posts SET content=".pwEscape($p['content'])." WHERE pid=".pwEscape($pid));
			}
		}else
		{
			//主题数据
			
			//替换附件标签
			if($des_image)
			{
				for ($i=0;$i<count($des_image);$i++)
				{
					$p['authorid'] && $DDB->update("INSERT INTO {$pw_prefix}attachs(fid,uid,tid,type,attachurl,uploadtime) VALUES (".$p['fid'].",".$p['authorid'].",".$p['tid'].",'img',".pwEscape($des_image[$i]).",".$p['postdate'].")");
					$aid = $DDB->insert_id();
					$p['content'] = str_replace("[img]/tp/".$des_image[$i]."[/img]","[attachment=".$aid."]",$p['content']);
				}
			}
			
			$DDB->update("REPLACE INTO {$pw_prefix}tmsgs (tid,aid,userip,ifsign,tags,ifconvert,content) VALUES (".$p['tid'].",'1','".$p['ip']."','1','".$p['tags']."','2','".$p['content']."')");
			$DDB->update("UPDATE {$pw_prefix}threads SET ifupload=".$p['attachment']." WHERE tid=".$p['tid']);	
		}
		$s_c++;
	}
	
	$minid = $SDB->get_value("SELECT MIN(id) AS minid FROM ".$truetableid);
	$maxid = $SDB->get_value("SELECT MAX(id) AS maxid FROM $truetableid");
	
	$compled = ($end-$minid)/($maxid-$minid)*100;
	$compled = substr($compled,0,5);

	echo "<div style=\"border:1px solid #D6E8F4;background:#F0F9FF;margin:10px;\">当前数据分表--><font color=blue><b>".$truetableid."</b></font><br><font size=2 color=red>最大记录id-->".$maxid."</font><br><font size=2 color=green>最小记录id-->".$minid."</font>&nbsp;[参考值,不一定为0]<br>本次<font size=2 color=purple>起始id-->".$start."</font><br>本次<font size=2 color=red>结束id-->".$end."</font><br><font size=2 color=blue>已完成:	".$compled."%</font></div>";
	
	if ($end <= $maxid)
	{
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c.'&tableid='.$tableid);
	}
	else
	{
		if (count($_tablelist) == $tableid)
		{
			report_log();
			newURL($step);
		}
		else
		{
			$tableid += 1;
			refreshto($cpage.'&step='.$step.'&start=1&tableid='.$tableid.'&ckminid=yes');
		}
	}
}
elseif ($step == '7')
{
	//转换VIP会员[总版主、版主]数据
	$db_table = 'VIP会员[总版主、版主]数据表dbo.bbs_vip';

	$query = $SDB->query("SELECT * FROM {$source_prefix}bbs_vip");
	while($rs = $SDB->fetch_array($query))
	{
		strip_space($rs);
		Add_S($rs);

		if(@in_array($rs['lbid'],$forumid))
		{
			$forumdb[$rs['lbid']][]= $rs['huiyuan'];
		}else
		{
			$forumid[] = $rs['lbid'];
			$forumdb[$rs['lbid']][] = $rs['huiyuan'];
		}
	}

	foreach ($forumdb AS $fid => $value)
	{
		if(is_array($value))
		{
			$adminstr = implode(",",$value);
			$forumdb[$fid] = $adminstr;
		}else
		{
			$forumdb[$fid] = $value;
		}		
	}

	foreach ($forumdb AS $fid => $value)
	{
		$DDB->update("UPDATE {$pw_prefix}forums SET forumadmin='".$value."' WHERE fid=".pwEscape($fid));
		$s_c++;
	}
	
	report_log();
	newURL($step);
}
elseif ($step == '8')
{
	//空间数据
	$db_table = '空间数据';
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}space");
		
		//可能最小的id会很大,这样处理能迅速定位到最小的id
		$minid = $SDB->get_value("SELECT MIN(id) AS minid FROM {$source_prefix}blog_user");
		$start = $minid;
		$end = $start+$percount;
	}

	$query = $SDB->query("SELECT huiyuan AS username,mc AS name,s1 AS visits,s2 AS tovisits FROM {$source_prefix}blog_user WHERE id BETWEEN ".$start." AND ".$end);
	while ($kj = $SDB->fetch_array($query))
	{
		strip_space($kj);
		Add_S($kj);

		$kj['uid'] = $SDB->get_value("SELECT id FROM {$source_prefix}huiyuan WHERE huiyuan=".pwEscape($kj['username']));
		$kj['spacetype'] = 1;
		
		extract($kj);
		if($username && $uid)
		{
			$DDB->update("REPLACE INTO {$pw_prefix}space (uid,name,spacetype,visits,tovisits) VALUES (".$uid.",'".$name."',".$spacetype.",".$visits.",".$tovisits.")");			
		}
		$s_c++;
	}
	
	$maxid = $SDB->get_value("SELECT max(id) AS maxid FROM {$source_prefix}blog_user");
	echo "最大id:".$maxid."<br>现在id:".$end."<br>";
	if ($end <= $maxid)
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
	//日志分类数据
	$db_table = '日志分类数据';
	
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}diarytype");
		
		//可能最小的id会很大,这样处理能迅速定位到最小的id
		$minid = $SDB->get_value("SELECT MIN(lbid) AS minid FROM {$source_prefix}blog_lb");
		$start = $minid;
		$end = $start+$percount;
	}
	
	$query = $SDB->query("SELECT lbid AS dtid,lbmc AS name,huiyuan AS author,js AS num FROM {$source_prefix}blog_lb WHERE lbid BETWEEN ".$start." AND ".$end);	
	while ($rz = $SDB->fetch_array($query))
	{
		strip_space($rz);
		Add_S($rz);
		
		$rz['authorid'] = $SDB->get_value("SELECT id FROM {$source_prefix}huiyuan WHERE huiyuan=".pwEscape($rz['author']));

		extract($rz);
		if($author && $authorid)
		{
			$DDB->update("REPLACE INTO {$pw_prefix}diarytype (`dtid`, `uid`, `name`, `num`) VALUES (".$dtid.",".$authorid.",'".$name."',".$num.")");	
		}
		$s_c++;
	}
	
	$maxid = $SDB->get_value("SELECT MAX(lbid) AS maxid FROM {$source_prefix}blog_lb");
	echo "最大id:".$maxid."<br>现在id:".$end."<br>";
	if ($end <= $maxid)
	{
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
		report_log();
		newURL($step);
	}
}
elseif ($step == '10')
{
	//日志主题数据
	$db_table = '日志主题数据';
	
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}diary");
		
		//可能最小的id会很大,这样处理能迅速定位到最小的id
		$minid = $SDB->get_value("SELECT MIN(id) AS minid FROM {$source_prefix}blog");
		$start = $minid;
		$end = $start+$percount;
	}
	
	$query = $SDB->query("SELECT id AS did,huiyuan AS username,bt AS subject,nr AS content,lbid AS dtid,sj AS postdate,s1 AS r_num,s2 AS c_num FROM {$source_prefix}blog WHERE id BETWEEN ".$start." AND ".$end);
	while($rz = $SDB->fetch_array($query))
	{
		strip_space($rz);
		Add_S($rz);
		
		$rz['postdate'] = get_timestamp($rz['postdate']);
		$rz['uid'] = $SDB->get_value("SELECT id FROM {$source_prefix}huiyuan WHERE huiyuan=".pwEscape($rz['username']));
		$rz['aid'] = 0;
		$privacy = 0;
		$ifcopy = 1;
		$copyurl = "";
		$ifconvert = 2;
		$ifwordsfb = 0;
		$ifupload = 0;
		
		extract($rz);
		if($uid && $username)
		{
			$DDB->update("REPLACE INTO {$pw_prefix}diary (`did`, `uid`, `dtid`, `aid`, `username`, `privacy`, `subject`, `content`, `ifcopy`, `copyurl`, `ifconvert`, `ifwordsfb`, `ifupload`, `r_num`, `c_num`, `postdate`) VALUES (".$did.",".$uid.",".$dtid.",".$aid.",'".$username."',".$privacy.",'".$subject."','".$content."',".$ifcopy.",'".$copyurl."',".$ifconvert.",".$ifwordsfb.",".$ifupload.",".$r_num.",".$c_num.",'".$postdate."')");
		}
		$s_c++;
	}
	
	$maxid = $SDB->get_value("SELECT max(id) AS maxid FROM {$source_prefix}blog");
	echo "最大id:".$maxid."<br>现在id:".$end."<br>";
	if ($end <= $maxid)
	{
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
		report_log();
		newURL($step);
	}
}
elseif ($step == '11')
{
	//日志回复数据
	$db_table = '日志回复数据';	
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}comment");
		
		//可能最小的id会很大,这样处理能迅速定位到最小的id
		$minid = $SDB->get_value("SELECT MIN(id) AS minid FROM {$source_prefix}blog_hf");
		$start = $minid;
		$end = $start+$percount;
	}
	
	$query = $SDB->query("SELECT id ,huiyuan AS username,nr AS title,sj AS postdate,zid AS typeid FROM {$source_prefix}blog_hf WHERE id BETWEEN ".$start." AND ".$end);
	while($hf = $SDB->fetch_array($query))
	{
		strip_space($hf);
		Add_S($hf);

		$hf['postdate'] = get_timestamp($hf['postdate']);
		$hf['uid'] = $SDB->get_value("SELECT id FROM {$source_prefix}huiyuan WHERE huiyuan=".pwEscape($hf['username']));
		$hf['ifwordsfb'] = 0;
		$hf['type'] = 'diary';

		extract($hf);
		if($uid && $username)
		{
			$DDB->update("REPLACE INTO {$pw_prefix}comment (uid,username,title,type,typeid,postdate,ifwordsfb) VALUES (".$uid.",'".$username."','".$title."','".$type."',".$typeid.",'".$postdate."',".$ifwordsfb.")");
		}
		$s_c++;
	}
	
	$maxid = $SDB->get_value("SELECT max(id) AS maxid FROM {$source_prefix}blog_hf");
	echo "最大id:".$maxid."<br>现在id:".$end."<br>";
	if ($end <= $maxid)
	{
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
		report_log();
		newURL($step);
	}
}
elseif ($step == '12')
{
	//短消息数据
	$db_table = '短消息数据转换';
	if(!$start)
	{
        $DDB->update("TRUNCATE TABLE {$pw_prefix}ms_messages");
        $DDB->update("TRUNCATE TABLE {$pw_prefix}ms_relations");
        $DDB->update("TRUNCATE TABLE {$pw_prefix}ms_replies");
        
		//可能最小的id会很大,这样处理能迅速定位到最小的id
		$minid = $SDB->get_value("SELECT MIN(id) AS minid FROM {$source_prefix}duanxin");
		$start = $minid;
		$end = $start+$percount;
	}
	
	$query = $SDB->query("SELECT id AS mid,bt AS title,nr AS content,sj AS modified_time,name1 AS msgtouser,name2 AS msgfromuser,lx AS categoryid FROM {$source_prefix}duanxin WHERE id BETWEEN $start AND $end");
	while($m = $SDB->fetch_array($query))
	{
		strip_space($m);
		Add_S($m);
		
		$m['created_time'] = $m['modified_time'] = get_timestamp($m['modified_time']);
		$m['msgtoid'] = $SDB->get_value("SELECT id AS msgtoid FROM {$source_prefix}huiyuan WHERE huiyuan=".pwEscape($m['msgtouser']));
		$m['msgfromid'] = $SDB->get_value("SELECT id AS msgfromid FROM {$source_prefix}huiyuan WHERE huiyuan=".pwEscape($m['msgfromuser']));
		$m['categoryid'] = $m['categoryid'] == '1' ? 2 : 1;
		$m['typeid'] = $m['categoryid'] == '1' ? 100 : 200;

        if(str_replace("Re","",$m['title']) == $m['title'])
        {
        	$message_sql[] = "(".$m['mid'].",".$m['msgfromid'].",'".$m['msgfromuser']."','".$m['title']."','".$m['content']."','".serialize(array('categoryid'=>$m['categoryid'],'typeid'=>$m['typeid']))."',".$m['created_time'].",".$m['modified_time'].",'".serialize(array($m['msgtouser']))."')";
        }else
        {
        	$replies_sql[] = "('".$m['mid']."',".$m['mid'].",'".$m['msgfromid']."','".$m['msgfromuser']."','".$m['title']."','".$m['content']."','1',".$m['created_time'].",".$m['modified_time'].")";
        }
        
        $userIds = "";
	    $userIds = array($m['msgtoid'],$m['msgfromid']);
	    foreach($userIds as $otherId)
	    {
	        $relations_sql[] = "(".$otherId.",'".$m['mid']."','1','100','0',".(($otherId == $m['msgfromid']) ? 1 : 0).",".$m['created_time'].",".$m['modified_time'].")";
        }
		$s_c++;
	}
    if($message_sql)
    {
        $DDB->update("REPLACE INTO {$pw_prefix}ms_messages (mid,create_uid,create_username,title,content,expand,created_time,modified_time,extra) VALUES ".implode(",",$message_sql));
    }
    if($relations_sql)
    {
        $DDB->update("REPLACE INTO {$pw_prefix}ms_relations (uid,mid,categoryid,typeid,status,isown,created_time,modified_time) VALUES ".implode(",",$relations_sql));
    }
    if($replies_sql)
    {
        $DDB->update("REPLACE INTO {$pw_prefix}ms_replies(id,parentid,create_uid,create_username,title,content,status,created_time,modified_time) VALUES ".implode(",",$replies_sql));
    }
    
	$maxid = $SDB->get_value("SELECT max(id) AS maxid FROM {$source_prefix}duanxin");
	echo "最大id:".$maxid."<br>现在id:".$end."<br>";
	if ($end <= $maxid)
	{
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
		report_log();
		newURL($step);
	}
}
elseif ($step == '13')
{
	//更新会员发帖数,此操作83后台缓存管理已经有更新发帖数机制,可跳过	
	$db_table = '更新会员发帖数';
	if(!$start)
	{
		//可能最小的id会很大,这样处理能迅速定位到最小的id
		$minid = $DDB->get_value("SELECT MIN(uid) AS minid FROM {$pw_prefix}memberdata");
		$start = $minid;
		$end = $start+$percount;
	}
	
	$query = $DDB->query("SELECT uid,postnum FROM {$pw_prefix}memberdata WHERE uid BETWEEN ".$start." AND ".$end);
	while($mem = $DDB->fetch_array($query))
	{
		$t_num = $DDB->get_value("SELECT COUNT(*) FROM {$pw_prefix}threads FORCE INDEX(idx_authorid) WHERE authorid=".pwEscape($mem['uid']));
		$p_num = $DDB->get_value("SELECT COUNT(*) FROM {$pw_prefix}posts FORCE INDEX(idx_authorid) WHERE authorid=".pwEscape($mem['uid']));
		$postnum = $t_num + $p_num;
		
		if($postnum && $mem['uid'])
		{
			$DDB->update("UPDATE {$pw_prefix}memberdata SET postnum=".$postnum." WHERE uid=".pwEscape($mem['uid']));
		}
		$s_c++;
	}

	$maxid = $DDB->get_value("SELECT max(uid) AS maxid FROM {$pw_prefix}memberdata");
	echo "最大id:".$maxid."<br>现在id:".$end."<br>";
	if ($end <= $maxid)
	{
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
		report_log();
		newURL($step);
	}
}
elseif ($step == '14')
{
	//会员好友数据
	$db_table = '会员好友数据转换';
	
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}friends");
		
		//可能最小的id会很大,这样处理能迅速定位到最小的id
		$minid = $SDB->get_value("SELECT MIN(id) AS minid FROM {$source_prefix}haoyou");
		$start = $minid;
		$end = $start+$percount;
	}
	
	$query = $SDB->query("SELECT huiyuan AS username,haoyou AS friendname FROM {$source_prefix}haoyou WHERE id BETWEEN ".$start." AND ".$end);
	while ($hy = $SDB->fetch_array($query))
	{
		strip_space($hy);
		Add_S($hy);
		
		$status = 1;
		
		$user = $DDB->get_one("SELECT uid,regdate FROM {$pw_prefix}members WHERE username=".pwEscape($hy['username']));
		$hy['uid'] = $user['uid'];
		$hy['uid_regdate'] = $user['regdate'];
		
		$friend = $DDB->get_one("SELECT uid,regdate FROM {$pw_prefix}members WHERE username=".pwEscape($hy['friendname']));
		$hy['friendid'] = $friend['uid'];
		$hy['friend_regdate'] = $friend['regdate'];
		
		$hy['joindate'] = $hy['uid_regdate'] > $hy['friend_regdate'] ? $hy['uid_regdate'] : $hy['friend_regdate'];

		if($hy['uid'] && $hy['friendid'])
		{
			$DDB->update("REPLACE INTO {$pw_prefix}friends (uid,friendid,status,joindate) VALUES (".$hy['uid'].",".$hy['friendid'].",".$status.",".$hy['joindate'].")");
		}
		$s_c++;
	}
	
	$maxid = $SDB->get_value("SELECT max(id) AS maxid FROM {$source_prefix}haoyou");
	echo "最大id:".$maxid."<br>现在id:".$end."<br>";
	if ($end <= $maxid)
	{
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
		report_log();
		newURL($step);
	}
}
elseif ($step == '15')
{
	//相册数据
	$db_table = '相册数据';

    if(!$start)
    {
        $DDB->query("TRUNCATE TABLE {$pw_prefix}cnalbum");
        $DDB->query("TRUNCATE TABLE {$pw_prefix}cnphoto");

        //可能最小的id会很大,这样处理能迅速定位到最小的id
		$minid = $SDB->get_value("SELECT MIN(id) AS minid FROM {$source_prefix}photo_xc");
		$start = $minid;
		$end = $start+$percount;
    }

    $query = $SDB->query("SELECT id AS aid,huiyuan AS owner,mc AS aname,js AS aintro,pass AS albumpwd,sj AS crtime,sj2 AS lasttime FROM {$source_prefix}photo_xc WHERE id BETWEEN ".$start." AND ".$end);

    while($xc = $SDB->fetch_array($query))
    {
        Add_S($xc);
        
        $xc['owenerid'] = $DDB->get_value("SELECT uid FROM {$pw_prefix}members WHERE username=".pwEscape($xc['owner']));
        $xc['photonum'] = $SDB->get_value("SELECT COUNT(*) FROM {$source_prefix}photo WHERE xc_id=".pwEscape($xc['aid']));

        $lastphoto_lastpid = $SDB->get_one("SELECT dt AS lastphoto,id AS lastpid FROM {$source_prefix}photo WHERE id=(SELECT MAX(id) FROM {$source_prefix}photo WHERE xc_id=".pwEscape($xc['aid']).")");

        $xc['lastphoto'] = $lastphoto_lastpid['lastphoto'] ? $lastphoto_lastpid['lastphoto'] : "NULL";
        $xc['lastpid'] = $lastphoto_lastpid['lastpid'] ? $lastphoto_lastpid['lastpid'] : '0';
        $xc['crtime'] = get_timestamp($xc['crtime']);
        $xc['atype'] = 0;

        extract($xc);
        $DDB->update("REPLACE INTO {$pw_prefix}cnalbum(`aid`, `aname`, `aintro`, `atype`, `private`, `albumpwd`, `ownerid`, `owner`, `photonum`, `lastphoto`, `lasttime`, `lastpid`, `crtime`, `memopen`, `isdefault`) VALUES (".$aid.",'".$aname."','".$aintro."',".$atype.",'1','".$albumpwd."',".$owenerid.",'".$owner."',".$photonum.",'".$lastphoto."','".$lasttime."',".$lastpid.",'".$crtime."','1','0')");
        
        $photo_query = $SDB->query("SELECT id AS pid,xc_id AS aid,huiyuan AS uploader,dt AS path,mc AS photoname,js AS pintro,sj AS uptime FROM {$source_prefix}photo WHERE xc_id=".pwEscape($xc['aid']));
        while($p = $SDB->fetch_array($photo_query))
        {
            Add_S($p);
            strip_space($p);
            
            $photo['pid'] = $p['pid'];
            $photo['aid'] = $p['aid'];
            $photo['pintro'] = "'照片名称:".$p['photoname']."<br>".$p['pintro']."'";            
            $photo['path'] = "'".$p['path']."'";
            $photo['uploader'] = "'".$p['uploader']."'";
            $photo['uptime'] = "'".get_timestamp($p['uptime'])."'";
            $photo['hits'] = 0 ;
            $photo['ifthumb'] = 0 ;
            $photo['c_num'] = 0 ;
            
            $aid_photo .= ($aid_photo ? "," : "")."(".implode(",",$photo).")";
        }

        $aid_photo && $DDB->update("REPLACE INTO {$pw_prefix}cnphoto(`pid`, `aid`, `pintro`, `path`, `uploader`, `uptime`, `hits`, `ifthumb`, `c_num`) VALUES ".$aid_photo);
        $s_c++;
    }
    $maxid = $SDB->get_value("SELECT max(id) AS maxid FROM {$source_prefix}photo_xc");
	echo "最大id:".$maxid."<br>现在id:".$end."<br>";
	if ($end <= $maxid)
	{
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
		report_log();
		newURL($step);
	}
}
elseif ($step == '16')
{
	//投票帖投票会员数据
	$db_table = '投票帖投票会员数据';
	
	$timestamp = time();
	$polls = $voter = $voteuser_arr = array();
	$vote_user_str = "";

	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}polls");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}voter");
	}
	
	//控制批量处理数
	$end = $start + 5 ;
	
	$query = $SDB->query("SELECT v.id AS pollid,v.xx AS votecontent,v.piao AS votepiao,v.sj AS regdatelimit,b.id AS tid FROM {$source_prefix}vote v LEFT JOIN {$source_prefix}bbs b ON v.id=b.vote WHERE b.vote !='0' AND v.id BETWEEN ".$start." AND ".$end);
	while ($vt = $SDB->fetch_array($query))
	{
		Add_S($vt);
		$polls['pollid'] = $vt['pollid'];//投票帖id
		
		$voteopts = explode("|",ltrim($vt['votecontent'],"|"));
		$votepiao = explode("|",ltrim($vt['votepiao'],"|"));		
		for ($i=0;$i<count($voteopts);$i++)
		{
			$opts_piao[$i] = array($voteopts[$i],$votepiao[$i]);
		}
		$polls['voteopts'] = serialize($opts_piao);//投票内容包括选项和获得投票值
		$polls['modifiable'] = 1;
		$polls['previewable'] = 1;
		$polls['multiple'] = 1;

		$query2 = $SDB->query("SELECT huiyuan AS voteuser,xx AS votewhere FROM {$source_prefix}vote_huiyuan WHERE vote_id=".pwEscape($vt['pollid']));
		while ($vt2 = $SDB->fetch_array($query2))
		{
			$voteuser_arr[] = array($vt2['voteuser'],$vt2['votewhere']);			
			if(str_replace("|","",$vt2['votewhere']) == $vt2['votewhere'])
			{
				$polls['mostvotes'] = 1;
			}else
			{
				$ltrim = ltrim($vt2['votewhere'],"|");
				if(str_replace("|","",$ltrim) == $ltrim)
				{
					$polls['mostvotes'] = 1;
				}else 
				{
					$where = explode("|",$ltrim);
					$polls['mostvotes'] = is_array($where) ? count($where) : '1';
				}
			}
			$mostvotes = !$mostvotes ? $polls['mostvotes'] : ($mostvotes < $polls['mostvotes'] ? $polls['mostvotes'] : $mostvotes);
		}
		
		$polls['tid'] = $vt['tid'];
		$polls['mostvotes'] = !$mostvotes ? 0 : $mostvotes;//最多能选的投票个数
		$polls['voters'] = $SDB->get_value("SELECT COUNT(*) FROM {$source_prefix}vote_huiyuan WHERE vote_id=".pwEscape($vt['pollid']));
		$polls['timelimit'] = 10;//投票有效天数
		$polls['leastvotes'] = 1;//投票为多选时最少投票个数
		$polls['regdatelimit'] = get_timestamp($vt['regdatelimit']);//限制发投票帖之前注册的账号能够投票

		extract($polls);
		$DDB->update("UPDATE {$pw_prefix}threads SET special='1',ifcheck='1' WHERE tid=".pwEscape($tid));
		//将投票数据插入到投票帖数据表
		$DDB->update("REPLACE INTO {$pw_prefix}polls (`pollid`, `tid`, `voteopts`, `modifiable`, `previewable`, `multiple`, `mostvotes`, `voters`, `timelimit`, `leastvotes`, `regdatelimit`, `creditlimit`, `postnumlimit`) VALUES(".$pollid.",".$tid.",'".$voteopts."',".$modifiable.",".$previewable.",".$multiple.",".$mostvotes.",".$voters.",".$timelimit.",".$leastvotes.",'".$regdatelimit."','0','0')");
		
		//构造当前投票帖用户数据
		foreach ($voteuser_arr AS $key => $value)
		{
			if(is_array($value))
			{
				list($value['username'],$value['vote']) = $value;
				$value['uid'] = $DDB->get_value("SELECT uid FROM {$pw_prefix}members WHERE username=".pwEscape($value['username'])." LIMIT 1");
				!$value['uid'] && $value['uid'] = 0;
				
				if(str_replace("|","",$value['vote']) != $value['vote'])
				{
					$value['vote'] = ltrim($value['vote'],"|");
					if(str_replace("|","",$value['vote']) != $value['vote'])
					{
						$vote = explode("|",$value['vote']);
						foreach ($vote AS $key2 => $opts)
						{
							$vote_user_str .= ($vote_user_str ? ',' : '')."(".$tid.",".$value['uid'].",'".$value['username']."',".$opts.",'".$timestamp."')";
						}
					}else
					{
						$vote_user_str .= ($vote_user_str ? ',' : '')."(".$tid.",".$value['uid'].",'".$value['username']."',".$value['vote'].",'".$timestamp."')";
					}
				}else
				{					
					$vote_user_str .= ($vote_user_str ? ',' : '')."(".$tid.",".$value['uid'].",'".$value['username']."',".$value['vote'].",'".$timestamp."')";
				}
			}
		}
		
		//将投票用户插入投票帖用户数据表
		$vote_user_str && $DDB->update("REPLACE INTO {$pw_prefix}voter (`tid`, `uid`, `username`, `vote`, `time`) VALUES ".$vote_user_str);
		$s_c++;
	}

	$maxid = $SDB->get_value("SELECT max(id) AS maxid FROM {$source_prefix}vote");
	echo "最大id:".$maxid."<br>现在id:".$end."<br>";
	if ($end <= $maxid)
	{
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c,"500");
	}
	else
	{	
		report_log();
		newURL($step);
	}
}
elseif ($step == '17')
{
	//商圈分类数据
	$db_table = '商圈分类数据';
	$info_str = "";
	$info_lb = $info = array();
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}dianpu_categories");
	}
	//数字人两张表,pw就一张表,categoryid会部分重复,这样能跳过重复id,注意在商家数据在处理小分类的时候要加上这个值保持规则一致
	$maxlbid = $SDB->get_value("SELECT COUNT(*) FROM {$source_prefix}info_lb");	
	
	/*处理大分类*/
	$query = $SDB->query("SELECT lbid AS categoryid,lbname AS name FROM {$source_prefix}info_lb");
	while ($info_lb = $SDB->fetch_array($query))
	{
		$info_lb['createdtime'] = time();
		$info_lb['sort'] = 0;
		$info_lb['parentid'] = 0;
		Add_S($info_lb);
		$info_str .= ($info_str ? ',' : '')."(".$info_lb['categoryid'].",".$info_lb['parentid'].",'".$info_lb['name']."',".$info_lb['sort'].",'".$info_lb['createdtime']."')";
	}
	$DDB->update("REPLACE INTO {$pw_prefix}dianpu_categories VALUES ".$info_str);
	
	/*处理小分类*/
	$query = $SDB->query("SELECT xlbid AS categoryid,xlbname AS name,lbid AS parentid FROM {$source_prefix}info_xlb");
	while ($info = $SDB->fetch_array($query))
	{
		$info['categoryid'] += $maxlbid;
		$info['createdtime'] = time();
		$info['sort'] = 0;
		Add_S($info);
		$info_str .= ($info_str ? ',' : '')."(".$info['categoryid'].",".$info['parentid'].",'".$info['name']."',".$info['sort'].",'".$info['createdtime']."')";
	}
	$DDB->update("REPLACE INTO {$pw_prefix}dianpu_categories VALUES ".$info_str);
	$s_c++;
	unset($info_lb,$info,$info_str);
	
	report_log();
	newURL($step);
}elseif ($step == '18')
{
	//商家数据
	$db_table = '商家数据';

	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}dianpu_shangjia");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}dianpu_dianpubase");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}dianpu_dianpuindex");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}dianpu_dianpuextend");		
		
		//可能最小的id会很大,这样处理能迅速定位到最小的id
		$minid = $SDB->get_value("SELECT MIN(id) AS minid FROM {$source_prefix}qy_info");
		$start = $minid;
		$end = $start+$percount;
	}
	
	//数字人两张表,pw就一张表,categoryid会部分重复,这样能跳过重复id,注意在商家数据在处理小分类的时候要加上这个值保持规则一致
	$maxlbid = $SDB->get_value("SELECT COUNT(*) FROM {$source_prefix}info_lb");	
	
	$query = $SDB->query("SELECT id AS shangjiaid,lbid AS parentid,xlbid AS categoryid,mc AS title,dz AS address,tel AS phone,js AS bulletin,sj AS createtime,huiyuan AS username,logo FROM {$source_prefix}qy_info WHERE id BETWEEN ".$start." AND ".$end);
	while ($sc = $SDB->fetch_array($query))
	{
		Add_S($sc);
		$sc['createtime'] = get_timestamp($sc['createtime']);
		if($sc['username'])
		{
			$sc['uid'] = $DDB->get_value("SELECT uid FROM {$pw_prefix}members WHERE username=".pwEscape($sc['username'])." LIMIT 1");
		}

		if($sc['uid'])
		{
			$DDB->update("REPLACE INTO {$pw_prefix}dianpu_shangjia(shangjiaid,uid,username,createtime) VALUES (".$sc['shangjiaid'].",".$sc['uid'].",'".$sc['username']."','".$sc['createtime']."')");
		}else 
		{
			$DDB->update("REPLACE INTO {$pw_prefix}dianpu_shangjia(shangjiaid,createtime) VALUES (".$sc['shangjiaid'].",'".$sc['createtime']."')");
		}
		
		if(str_replace("/tp/","",$sc['logo']) != $sc['logo'])
		{
			$sc['logo'] = preg_replace("/\/tp\/(.*)/","\\1",$sc['logo']);
		}
		
		$DDB->update("REPLACE INTO {$pw_prefix}dianpu_dianpubase(shangjiaid,title,state,ifcheck,checkway,address,qq,msn,wangwang,phone,logo,bulletin,recommendlevel,createtime,modifytime) VALUES (".$sc['shangjiaid'].",'".$sc['title']."','1','1','mobile','".$sc['address']."','0','0','0','".$sc['phone']."','".$sc['logo']."','".$sc['bulletin']."','3','".$sc['createtime']."','".$sc['createtime']."')");
		
		$sc['dianpuid'] = $DDB->insert_id();
		$sc['categoryid'] += $maxlbid;
		
		$DDB->update("REPLACE INTO {$pw_prefix}dianpu_dianpuindex(dianpuid,title,state,areaid,categoryid,parentid,groupid,ifcheck,checkway,recommendlevel,modifytime) VALUES (".$sc['dianpuid'].",'".$sc['title']."','1','1',".$sc['categoryid'].",".$sc['parentid'].",'2','1','mobile','3','".$sc['createtime']."')");
		if($sc['uid'])
		{
			$DDB->update("REPLACE INTO {$pw_prefix}dianpu_dianpuextend(dianpuid,uid,username,groupid,areaid,categoryid) VALUES (".$sc['dianpuid'].",".$sc['uid'].",'".$sc['username']."','2','1',".$sc['categoryid'].")");
		}else 
		{
			$DDB->update("REPLACE INTO {$pw_prefix}dianpu_dianpuextend(dianpuid,groupid,areaid,categoryid) VALUES (".$sc['dianpuid'].",'2','1',".$sc['categoryid'].")");
		}
		unset($sc);
		$s_c++;
	}
	
	$maxid = $SDB->get_value("SELECT max(id) AS maxid FROM {$source_prefix}qy_info");
	echo "最大id:".$maxid."<br>现在id:".$end."<br>";
	if ($end <= $maxid)
	{
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c,"500");
	}
	else
	{	
		report_log();
		newURL($step);
	}
}
elseif ($step == '19')
{
    //分类信息框架
    $db_tbale = '分类信息框架';
    if(!$start)
    {
    	$DDB->query("TRUNCATE TABLE {$pw_prefix}topiccate");
    	$DDB->query("TRUNCATE TABLE {$pw_prefix}topicmodel");
    }
    //分类信息主题名称和模板名称处理
    $ifable = $ifdel = $vieworder = 1 ;
    $query = $SDB->query("SELECT id AS cateid,lbmc AS name FROM {$source_prefix}classad_lb WHERE cid='0'");
    while ($cate = $SDB->fetch_array($query))
    {
    	$model_ifable = $model_vieworder = 1 ;
    	$querymd = $SDB->query("SELECT id AS modelid,lbmc AS mname FROM {$source_prefix}classad_lb WHERE cid=".$cate['cateid']);
    	while ($model = $SDB->fetch_array($querymd))
    	{
    		$mode_str .= ($mode_str ? ',' : '')."(".$model['modelid'].",'".$model['mname']."',".$cate['cateid'].",".$model_ifable.",".$model_vieworder.")";
    		$model_vieworder++; 
    	}
    	$DDB->update("REPLACE INTO {$pw_prefix}topicmodel VALUES ".$mode_str);
    	$cate_str .= ($cate_str ? ',' : '')."(".$cate['cateid'].",'".$cate['name']."',".$ifable.",".$vieworder.",".$ifdel.")";
    	$vieworder++;
    }
	$DDB->update("REPLACE INTO {$pw_prefix}topiccate VALUES ".$cate_str);
	
	//区域字段更新
	$query = $SDB->query("SELECT id,mc FROM {$sourc_prefix}classad_dq");
	while ($qy = $SDB->fetch_array($query))
	{
		$qy_array[] = $qy['id']."=".$qy['mc'];
	}
	$qy_array && $DDB->update("UPDATE {$pw_prefix}topicfield SET rules =".pwEscape(serialize($qy_array))." WHERE name='区域'");
   	$s_c++;
   	report_log();
	newURL($step);
}
elseif ($step == '20')
{
	//分类信息帖子数据
    $db_tbale = '分类信息帖子数据';
    
    require_once (S_P.'tmp_info.php');
    
    if(!$start)
    {
        $category_exists = $DDB->get_value("SELECT COUNT(*) FROM {$pw_prefix}forums WHERE fid=".pwEscape($category_id));
        $cate_exists = $DDB->get_value("SELECT COUNT(*) FROM {$pw_prefix}forums WHERE fid=".pwEscape($cate_fid));
        
        if(!$category_exists)
        {
            $DDB->update("REPLACE INTO `pw_forums` (`fid`, `fup`, `ifsub`, `childid`, `type`, `logo`, `name`, `descrip`, `title`, `dirname`, `metadescrip`, `keywords`, `vieworder`, `forumadmin`, `fupadmin`, `style`, `across`, `allowhtm`, `allowhide`, `allowsell`, `allowtype`, `copyctrl`, `allowencode`, `password`, `viewsub`, `allowvisit`, `allowread`, `allowpost`, `allowrp`, `allowdownload`, `allowupload`, `modelid`, `forumsell`, `pcid`, `actmids`, `f_type`, `f_check`, `t_type`, `cms`, `ifhide`, `showsub`, `ifcms`) VALUES (".$category_id.", 0, 0, 0, 'category', '', '分类信息', '', '', '', '', '', 0, '', ',cxxzlw,', '0', 0, 0, 1, 1, 3, 0, 1, '', 0, '', '', '', '', '', '', '', '', '', '', 'forum', 0, 0, 0, 1, 0, 0)");
		    $DDB->update("REPLACE INTO `pw_forumdata` (`fid`, `tpost`, `topic`, `article`, `subtopic`, `top1`, `top2`, `aid`, `aidcache`, `aids`, `lastpost`, `topthreads`) VALUES (".$category_id.", 0, 0, 0, 0, 0, 5, 0, 0, '', '', '')");
        }
        if(!$cate_exists)
        {
            $DDB->update("REPLACE INTO `pw_forums` (`fid`, `fup`, `ifsub`, `childid`, `type`, `logo`, `name`, `descrip`, `title`, `dirname`, `metadescrip`, `keywords`, `vieworder`, `forumadmin`, `fupadmin`, `style`, `across`, `allowhtm`, `allowhide`, `allowsell`, `allowtype`, `copyctrl`, `allowencode`, `password`, `viewsub`, `allowvisit`, `allowread`, `allowpost`, `allowrp`, `allowdownload`, `allowupload`, `modelid`, `forumsell`, `pcid`, `actmids`, `f_type`, `f_check`, `t_type`, `cms`, `ifhide`, `showsub`, `ifcms`) VALUES (".$cate_fid.", ".$category_id.", 0, 0, 'forum', '', '分类信息版块', '', '', '', '', '', 0, '', ',cxxzlw,', '0', 0, 0, 1, 1, 3, 0, 1, '', 0, '', '', '', '', '', '', '', '', '', '', 'forum', 0, 0, 0, 1, 0, 0)");
		    $DDB->update("REPLACE INTO `pw_forumdata` (`fid`, `tpost`, `topic`, `article`, `subtopic`, `top1`, `top2`, `aid`, `aidcache`, `aids`, `lastpost`, `topthreads`) VALUES (".$cate_fid.", 0, 0, 0, 0, 0, 5, 0, 0, '', '', '')");
        }

    	//可能最小的id会很大,这样处理能迅速定位到最小的id
		$minid = $SDB->get_value("SELECT MIN(id) AS minid FROM {$source_prefix}classad");
		$start = $minid;
		$end = $start+$percount;
    }

    $query = $SDB->query("SELECT ca.id,ca.bt AS subject,cn.nr AS content,ca.tel AS tel,ca.huiyuan AS author,ca.sj AS postdate,ca.ip AS ipfrom,cd.mc AS area FROM {$source_prefix}classad ca LEFT JOIN {$source_prefix}classad_nr cn ON ca.id=cn.ad_id LEFT JOIN {$source_prefix}classad_dq cd ON ca.dqid=cd.id WHERE ca.id BETWEEN ".$start." AND ".$end);
    while ($rt = $SDB->fetch_array($query))
    {
    	Add_S($rt);
    	
    	$rt['authorid'] = $DDB->get_value("SELECT uid FROM {$pw_prefix}members WHERE username=".pwEscape($rt['author'])." LIMIT 1");
    	$rt['postdate'] = get_timestamp($rt['postdate']);
    	$rt['content'] = "<table border=1 width='100%'><tr><td width='100px'>地区:</td><td>".($rt['area'] ? $rt['area'] : '暂无')."</td></tr><tr style='background:#F7F7F7'><td width='100px'>联系方式:</td><td>".$rt['tel']."</td></tr><tr><td width='100px'>发布作者:</td><td>".$rt['author']."</td></tr><tr style='background:#F7F7F7'><td colspan='2'>".$rt['content']."</td></tr></table>";
    	extract($rt);
    	
        if($author && $authorid)
        {
            $DDB->update("INSERT INTO {$pw_prefix}threads(fid,subject,author,authorid,ifcheck,postdate,lastpost,lastposter) VALUES (".$cate_fid.",'".$subject."','".$author."',".$authorid.",'1','".$postdate."','".$postdate."','".$author."')");
    	    $tid = $DDB->insert_id();
    	    $DDB->update("REPLACE INTO {$pw_prefix}tmsgs(tid,ipfrom,content) VALUES (".$tid.",'".$ipfrom."',".pwEscape($content).")");
            $s_c++;
        }
    }
	$maxid = $SDB->get_value("SELECT max(id) AS maxid FROM {$source_prefix}classad");
	echo "最大id:".$maxid."<br>现在id:".$end."<br>";
	if ($end <= $maxid)
	{
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c,"500");
	}
	else
	{	
		report_log();
		newURL($step);
	}
}
else
{
	ObHeader($basename.'?action=finish&dbtype='.$dbtype);
	exit;
}

//转换数字人的时间戳,用strtotime()会出错
function get_timestamp($timestr){
	list($day,$month,$year_and_hour,$minute) = split("[\/\:]",$timestr);
	$year = substr($year_and_hour,0,4);
	$hour = substr($year_and_hour,4);
	$second = '00';

	return $timestamp = mktime($hour,$minute,$second,$month,$day,$year);
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
?>