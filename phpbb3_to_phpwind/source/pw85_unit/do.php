<?php
/**
*
*  Copyright (c) 2003-10  PHPWind.net. All rights reserved.
*  Support : http://www.phpwind.net
*  This software is the proprietary information of PHPWind.com.
*  Code By rickyleo(riqi@aliyun-inc.com)
*
*/

!defined('R_P') && exit('Forbidden!');
$db_table = $step_data[$step];
if ($pwsamedb){
	$SDB = &$DDB;
}else{
	$charset_change = 1;
	$SDB = new mysql($source_db_host, $source_db_user, $source_db_password, $source_db_name, $source_charset, '');
}

if ($step == 1){
	//用户组
	$groupArray = array();
	$query = $SDB->query("SELECT * FROM {$source_prefix}usergroups
						  WHERE grouptitle!='' AND (gptype != 'default' OR gid IN ('3', '4', '5', '6', '7'))
			  			  ORDER BY gid ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);

		$gid = $DDB->get_value("SELECT gid FROM {$pw_prefix}usergroups WHERE grouptitle = ".pwEscape($rt['grouptitle']));
		if (empty($gid)){
			$DDB->update("INSERT INTO {$pw_prefix}usergroups (gptype,grouptitle,groupimg,grouppost,ifdefault)
					      VALUES ('$rt[gptype]','$rt[grouptitle]','$rt[groupimg]','$rt[grouppost]','$rt[ifdefault]')");
			$gid = $DDB->insert_id();

			if ($rt['gptype'] != 'member'){
				$gptype2 = 'groups';
			}else{
				$gptype2 = 'member';
			}

			$groupArray[$rt['gid']] = array(
				'gid1'		=>	$gid,
				'gptype2' 	=>	$gptype2
			);
			//全部导完后参考groupArray来提示用户将groupimg拷过来
		}
		$s_c++;
	}
	$SDB->free_result($query);

	if (!empty($groupArray)){
		writeover(S_P.'temp_1.php', "\r\n\$groupArray = ".pw_var_export($groupArray).";\n", true);
	}

	report_log();
	newURL($step);
}elseif ($step == 2){
	//积分
	$credArray = array();

	$query = $SDB->query("SELECT * FROM {$source_prefix}credits
						  WHERE name != ''
						  ORDER BY cid ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);

		$cid = $DDB->get_value("SELECT cid FROM {$pw_prefix}credits WHERE name =".pwEscape($rt['name']));
		if (empty($cid)){
			$DDB->update("INSERT INTO {$pw_prefix}credits (name,unit,description) VALUES ('$rt[name]','$rt[unit]','$rt[description]')");
			$cid = $DDB->insert_id();

			$credArray[$rt['cid']] = array(
				'cid1'	=>	$cid,
				'cid2'	=>	$rt['cid']
			);
		}
		$s_c++;
	}
	$SDB->free_result($query);

	if (!empty($credArray)){
		writeover(S_P.'temp_2.php', "\r\n\$credArray = ".pw_var_export($credArray).";\n", true);
	}

	report_log();
	newURL($step);
}elseif ($step == 3){
	//标签
	$lastid = '0';
	$tagsArray = array();
	file_exists(S_P.'temp_3.php') && require_once(S_P.'temp_3.php');

	$query = $SDB->query("SELECT * FROM {$source_prefix}tags
						  WHERE tagid > $start AND tagname != ''
						  ORDER BY tagid ");

	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['tagid'];

		$tagid = $DDB->get_value("SELECT tagid FROM {$pw_prefix}tags WHERE tagname=".pwEscape($rt['tagname']));
		if (!$tagid){
			$DDB->update("INSERT INTO {$pw_prefix}tags (tagname,num,ifhot) VALUES ('$rt[tagname]','$rt[num]','$rt[ifhot]')");
			$tagid = $DDB->insert_id();

			$tagsArray[$rt['tagid']] = array(
				'tagid1'	=>	$tagid,
				'tagid2'	=>	$rt['tagid']);
		}else{
			$DDB->update("UPDATE {$pw_prefix}tags SET num=num+".pwEscape($rt['num'])." WHERE tagid=".pwEscape($tagid));
		}
		$s_c++;
	}
	$SDB->free_result($query);

	if (!empty($tagsArray)){
		writeover(S_P.'temp_3.php', "\r\n\$tagsArray = ".pw_var_export($tagsArray).";\n", true);
	}

    $maxid = $SDB->get_value("SELECT MAX(tagid) FROM {$source_prefix}tags LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '4'){
	//道具
	$query = $SDB->query("SELECT * FROM {$source_prefix}tools
						  WHERE id > $start AND name != ''
						  ORDER BY id");

	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);

		$id = $DDB->get_value("SELECT id FROM {$pw_prefix}tools WHERE name=".pwEscape($rt['name']));
		if (!$id){
			$DDB->update("INSERT INTO {$pw_prefix}tools (name,filename,descrip,vieworder,logo,state,price,creditype,type,stock,conditions)
						  VALUES ('$rt[name]','$rt[filename]','$rt[descrip]','$rt[vieworder]','$rt[logo]','$rt[state]','$rt[price]','$rt[creditype]','$rt[type]','$rt[stock]','$rt[conditions]')");
			$id = $DDB->insert_id();

			$toolsArray[$rt['id']] = array(
				'id1'	=>	$id,
				'id2'	=>	$rt['id']
			);
			//全部导完后要保存用户提示信息
		}
		$s_c++;
	}
	$SDB->free_result($query);

	if (!empty($toolsArray)){
		writeover(S_P.'temp_4.php', "\r\n\$toolsArray = ".pw_var_export($toolsArray).";\n", true);
	}

	report_log();
    newURL($step);
}elseif ($step == '5'){
	//勋章
	$medaliArray = array();

	$query = $SDB->query("SELECT * FROM {$source_prefix}medalinfo
						  WHERE id > $start AND name != ''
						  ORDER BY id");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);

		$id = $DDB->get_value("SELECT id FROM {$pw_prefix}medalinfo WHERE name =".pwEscape($rt['name']));
		if (!$id){
			$DDB->update("INSERT INTO {$pw_prefix}medalinfo (name,intro,picurl) VALUES ('$rt[name]','$rt[intro]','$rt[picurl]')");
			$id = $DDB->insert_id();

			$medaliArray[$rt['id']] = array(
				'id1'	=>	$id,
				'id2'	=>	$rt['id']
			);
			//全部导完后要保存用户提示信息
		}
		$s_c++;
	}
	$SDB->free_result($query);

	if (!empty($medaliArray)){
		writeover(S_P.'temp_5.php', "\r\n\$medaliArray = ".pw_var_export($medaliArray).";\n", true);
	}

	report_log();
	newURL($step);
}elseif ($step == '6'){
	//用户数据
	if(!$start){
		$SDB->query("DROP TABLE IF EXISTS {$source_prefix}bakuids;");
		$SDB->query("CREATE TABLE {$source_prefix}bakuids (
					 id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					 uid2 INT(10) NOT NULL,
					 uid1 INT(10) NOT NULL,
					 icon VARCHAR(255) NOT NULL DEFAULT '',
					 username VARCHAR(50) NOT NULL,
					 newname VARCHAR(50) NOT NULL,
					 PRIMARY KEY (id),
					 KEY `uid2` (`uid2`),
					 KEY `username` (`username`))
					 ENGINE = MYISAM ;");
		$DDB->query("ALTER TABLE {$pw_prefix}members MODIFY COLUMN username VARCHAR(50);");

		//更改数据库结构
		$addfields = TRUE;
		$query = $DDB->query("SHOW COLUMNS FROM {$pw_prefix}members");
		while ($mc = $DDB->fetch_array($query)){
			if (strpos(strtolower($mc['Field']), 'salt') !== FALSE){
				$addfields = FALSE;
				break;
			}
		}

		$addfields && $DDB->update("ALTER TABLE {$pw_prefix}members ADD salt CHAR(6) ".$DDB->collation()." NOT NULL DEFAULT ''");
	}

	$lastid = '0';
	$memberArray = $bakuids = $repeatUser = array();
	file_exists(S_P."temp_1.php") && include_once(S_P."temp_1.php");//用户组
	file_exists(S_P."temp_5.php") && include_once(S_P."temp_5.php");//勋章

	echo "SELECT m.*,md.*,
						  mi.adsips,mi.credit,mi.deposit,mi.startdate,mi.ddeposit,mi.dstartdate,mi.regreason,mi.readmsg,mi.delmsg,
						  mi.tooltime,mi.replyinfo,mi.lasttime,mi.digtid,mi.customdata,mi.tradeinfo
						  FROM {$source_prefix}members m
						  LEFT JOIN {$source_prefix}memberdata md USING(uid)
						  LEFT JOIN {$source_prefix}memberinfo mi USING(uid)
						  WHERE m.uid > $start AND m.uid < $end AND m.username != ''
						  ORDER BY m.uid ASC "."<hr>";
	$query = $SDB->query("SELECT m.*,md.*,
						  mi.adsips,mi.credit,mi.deposit,mi.startdate,mi.ddeposit,mi.dstartdate,mi.regreason,mi.readmsg,mi.delmsg,
						  mi.tooltime,mi.replyinfo,mi.lasttime,mi.digtid,mi.customdata,mi.tradeinfo
						  FROM {$source_prefix}members m
						  LEFT JOIN {$source_prefix}memberdata md USING(uid)
						  LEFT JOIN {$source_prefix}memberinfo mi USING(uid)
						  WHERE m.uid > $start AND m.uid < $end AND m.username != ''
						  ORDER BY m.uid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);

		$lastid = $rt['uid'];
		$username = $rt['username'];
		$newname = '';

		$uid = $DDB->get_value("SELECT uid FROM {$pw_prefix}members WHERE username=".pwEscape($username));
		if ($uid){
			$newname = $repeatname.$username;
			$newuid = $DDB->get_value("SELECT uid FROM {$pw_prefix}members WHERE username=".pwEscape($newname));
			if(!$newuid){
				/**
				 *替换用户组,如果有新增的用户组，且该用户属于用新增用户组，
				 *则把该用户在副表的用户组对应到主表新加的用户组中,如果不是新增的用户组就不进行处理
				 **/
				if(!empty($groupArray) && !empty($groupArray[$rt['groupid']]['gid1'])){
					$rt['groupid'] = $groupArray[$rt['groupid']]['gid1'];
				}
				//会员组同理
				if(!empty($groupArray) && !empty($groupArray[$rt['memberid']]['gid1'])){
					$rt['memberid'] = $groupArray[$rt['memberid']]['gid1'];
				}
				//同理替换附加用户组头衔id
				if(!empty($groupArray)){
				  foreach ($groupArray as $key => $value){
				  	$rt['groups'] = str_replace($key, $value['gid1'], $rt['groups']);//这里可能有点问题
				  }
				}
				if(!empty($medaliArray) && count($medaliArray)>=1){
				  foreach ($medaliArray as $key => $value){
				  	$rt['medals'] = str_replace($key, $value['id1'], $rt['medals']);
				  }
				}
				//保存会员表
				$DDB->update("INSERT INTO {$pw_prefix}members (username,password,safecv,email,groupid,memberid,groups,icon,gender,regdate,signature,introduce,oicq,aliww,icq,msn,yahoo,site,location,honor,bday,lastaddrst,yz,timedf,style,datefm,t_num,p_num,attach,hack,newpm,banpm,msggroups,medals,userstatus,shortcut,salt)VALUES('$newname','$rt[password]','$rt[safecv]','$rt[email]','$rt[groupid]','$rt[memberid]','$rt[groups]','$rt[icon]','$rt[gender]','$rt[regdate]','$rt[signature]','$rt[introduce]','$rt[oicq]','$rt[aliww]','$rt[icq]','$rt[msn]','$rt[yahoo]','$rt[site]','$rt[location]','$rt[honor]','$rt[bday]','$rt[lastaddrst]','$rt[yz]','$rt[timedf]','$rt[style]','$rt[datefm]','$rt[t_num]','$rt[p_num]','$rt[attach]','$rt[hack]','$rt[newpm]','$rt[banpm]','$rt[msggroups]','$rt[medals]','$rt[userstatus]','$rt[shortcut]','$rt[salt]')");
				$uid = $DDB->insert_id();

				$DDB->update("INSERT INTO {$pw_prefix}memberdata (uid,postnum,digests,rvrc,money,credit,currency,lastvisit,thisvisit,lastpost,onlinetime,monoltime,todaypost,monthpost,uploadtime,uploadnum,onlineip,starttime,pwdctime,postcheck)values ('$uid','$rt[postnum]','$rt[digests]','$rt[rvrc]','$rt[money]','$rt[credit]','$rt[currency]','$rt[lastvisit]','$rt[thisvisit]','$rt[lastpost]','$rt[onlinetime]','$rt[monoltime]','$rt[todaypost]','$rt[monthpost]','$rt[uploadtime]','$rt[uploadnum]','$rt[onlineip]','$rt[starttime]','$rt[pwdctime]','$rt[postcheck]')");
				$DDB->update("INSERT INTO {$pw_prefix}memberinfo (uid,adsips,credit,deposit,startdate,ddeposit,dstartdate,regreason,readmsg,delmsg,tooltime,replyinfo,lasttime,digtid,customdata,tradeinfo)values ('$uid','$rt[adsips]','$rt[credit]','$rt[deposit]','$rt[startdate]','$rt[ddeposit]','$rt[dstartdate]','$rt[regreason]','$rt[readmsg]','$rt[delmsg]','$rt[tooltime]','$rt[replyinfo]','$rt[lasttime]','$rt[digtid]','$rt[customdata]','$rt[tradeinfo]')");

				$memberArray[] = array(
					'uid1' 		=> $uid,
					'uid2' 		=> $rt['uid'],
					'icon' 		=> $rt['icon'],
					'username' 	=> $username,
					'newname' 	=> $newname,
					'lastvisit'	=> $rt['lastvisit'],
				);
			}
		}elseif(!$uid){
				//替换用户组,如果有新增的用户组，且该用户属于用新增用户组，
				//则把该用户在副表的用户组对应到主表新加的用户组中,如果不是新增的用户组就不进行处理
				if(!empty($groupArray) && !empty($groupArray[$rt['groupid']]['gid1'])){
					$rt['groupid'] = $groupArray[$rt['groupid']]['gid1'];
				}
				//会员组同理
				if(!empty($groupArray) && !empty($groupArray[$rt['memberid']]['gid1'])){
					$rt['memberid'] = $groupArray[$rt['memberid']]['gid1'];
				}
				//同理替换附加用户组头衔id
				if(!empty($groupArray)){
				  foreach ($groupArray as $key => $value){
				  	$rt['groups'] = str_replace($key, $value['gid1'], $rt['groups']);//这里可能有点问题
				  }
				}
				if(!empty($medaliArray) && count($medaliArray)>=1){
				  foreach ($medaliArray as $key => $value){
				  	$rt['medals'] = str_replace($key, $value['id1'], $rt['medals']);
				  }
				}
				//保存会员表
				$DDB->update("INSERT INTO {$pw_prefix}members (username,password,safecv,email,groupid,memberid,groups,icon,gender,regdate,signature,introduce,oicq,aliww,icq,msn,yahoo,site,location,honor,bday,lastaddrst,yz,timedf,style,datefm,t_num,p_num,attach,hack,newpm,banpm,msggroups,medals,userstatus,shortcut,salt)VALUES('$username','$rt[password]','$rt[safecv]','$rt[email]','$rt[groupid]','$rt[memberid]','$rt[groups]','$rt[icon]','$rt[gender]','$rt[regdate]','$rt[signature]','$rt[introduce]','$rt[oicq]','$rt[aliww]','$rt[icq]','$rt[msn]','$rt[yahoo]','$rt[site]','$rt[location]','$rt[honor]','$rt[bday]','$rt[lastaddrst]','$rt[yz]','$rt[timedf]','$rt[style]','$rt[datefm]','$rt[t_num]','$rt[p_num]','$rt[attach]','$rt[hack]','$rt[newpm]','$rt[banpm]','$rt[msggroups]','$rt[medals]','$rt[userstatus]','$rt[shortcut]','$rt[salt]')");
				$uid = $DDB->insert_id();

				$DDB->update("INSERT INTO {$pw_prefix}memberdata (uid,postnum,digests,rvrc,money,credit,currency,lastvisit,thisvisit,lastpost,onlinetime,monoltime,todaypost,monthpost,uploadtime,uploadnum,onlineip,starttime,pwdctime,postcheck)VALUES('$uid','$rt[postnum]','$rt[digests]','$rt[rvrc]','$rt[money]','$rt[credit]','$rt[currency]','$rt[lastvisit]','$rt[thisvisit]','$rt[lastpost]','$rt[onlinetime]','$rt[monoltime]','$rt[todaypost]','$rt[monthpost]','$rt[uploadtime]','$rt[uploadnum]','$rt[onlineip]','$rt[starttime]','$rt[pwdctime]','$rt[postcheck]')");
				$DDB->update("INSERT INTO {$pw_prefix}memberinfo (uid,adsips,credit,deposit,startdate,ddeposit,dstartdate,regreason,readmsg,delmsg,tooltime,replyinfo,lasttime,digtid,customdata,tradeinfo)values ('$uid','$rt[adsips]','$rt[credit]','$rt[deposit]','$rt[startdate]','$rt[ddeposit]','$rt[dstartdate]','$rt[regreason]','$rt[readmsg]','$rt[delmsg]','$rt[tooltime]','$rt[replyinfo]','$rt[lasttime]','$rt[digtid]','$rt[customdata]','$rt[tradeinfo]')");

				$memberArray[] = array(
					'uid1'		=>	$uid,
					'uid2'		=>	$rt['uid'],
					'icon'		=>	$rt['icon'],
					'username'	=>	$username,
					'newname'	=>	$username,
					'lastvisit'	=> $rt['lastvisit'],
				);
		}
		$s_c++;
	}
	$SDB->free_result($query);

	if (!empty($memberArray)){
		file_exists(S_P.'tmp_repeatUser.php') && require_once (S_P.'tmp_repeatUser.php');

		foreach($memberArray as $value){
			if($value['uid2'] == "" || $value['uid1'] == ""){
				continue;
			}

			$bakuids[] = array(
				'uid2'		=>	$value['uid2'],
				'uid1'		=>	$value['uid1'],
				'icon'		=>	$value['icon'],
				'username'	=>	$value['username'],
				'newname'	=>	$value['newname']
			);

			if($value['username'] != $value['newname']){
				if(!empty($value['lastvisit'])){
					$value['lastvisit'] = date("Y-m-d H:i:s", $value['lastvisit']);
				}
				$repeatUser[] = "[".$value['username']."] => [".$value['newname']."] <".$value['lastvisit'].">";
			}
		}
		writeover(S_P.'tmp_repeatUser.php', "\r\n\$repeatUser = ".pw_var_export($repeatUser).";", true);

		if(!empty($bakuids)){
			$SDB->update("REPLACE INTO {$source_prefix}bakuids (uid2,uid1,icon,username,newname)VALUES".pwSqlMulti($bakuids));
		}
	}
	unset($memberArray, $bakuids, $repeatUser);

	$maxid = $SDB->get_value("SELECT MAX(uid) FROM {$source_prefix}members LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '7'){
	//头像
	$lastid = '0';
	$avatar_dir2 = R_P.'upload';
	$avatar_dir1 = R_P.'attachment/upload';

	if (!is_dir($avatar_dir2) || !is_dir($avatar_dir1) || !is_readable($avatar_dir2) || !N_writable($avatar_dir1)){
		echo "1、请将<font color=red><b>从论坛</b></font>的attachment/upload文件夹移动到 pwb根目录，且设定权限为777<br>
			  2、在pwb根目录下创建attachment/upload文件夹,且设定权限为777";
		exit;
	}
	//从数据库入手找到相应的文件重命名
	$query = $SDB->query("SELECT id,icon,uid1,uid2
						  FROM {$source_prefix}bakuids
						  WHERE id > $start
						  ORDER BY id ASC
						  LIMIT $percount");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];

		if($rt['icon']){
			$icon2Array = explode('|',$rt['icon']);
			$icon1Array = $icon2Array;

			if('3' == $icon2Array['1']){//如果是上传头像则处理
				$file2 = $avatar_dir2."/".$icon2Array['0'];
				$file2Array = explode('/',$icon2Array['0']);
				$file1Array = $file2Array;
				$uid2Array = explode('.',$file2Array['1']);
				$uid1Array = $uid2Array;
				$uid1Array['0'] = $rt['uid1'];
				$file1Array['1'] = implode('.',$uid1Array);
				$file1Array['0'] = str_pad(substr($rt['uid1'],-2),2,'0',STR_PAD_LEFT);
				$icon1Array['0'] = implode('/',$file1Array);
				$file1 = $avatar_dir1."/".$icon1Array['0'];
				$icon1 = implode('|',$icon1Array);
				$dir1 = $avatar_dir1."/".$file1Array['0'];
				$dir1_middle = $avatar_dir1."/middle/".$file1Array['0'];
				$dir1_small = $avatar_dir1."/small/".$file1Array['0'];
				if(!file_exists($dir1)){
					 @mkdir($dir1,0777);
					 @mkdir($dir1_middle,0777);
					 @mkdir($dir1_small,0777);
				}
				@copy($file2, $file1);//原图OK
				//中图和小图
				$file2_middle = $avatar_dir2."/middle/".$file2Array['0']."/".$rt['uid2'].".jpg";
				$file1_middle = $avatar_dir1."/middle/".$file1Array['0']."/".$rt['uid1'].".jpg";

				@copy($file2_middle, $file1_middle);

				$file2_small = $avatar_dir2."/small/".$file2Array['0']."/".$rt['uid2'].".jpg";
				$file1_small = $avatar_dir1."/small/".$file1Array['0']."/".$rt['uid1'].".jpg";

				@copy($file2_small, $file1_small);
				$DDB->update("UPDATE {$pw_prefix}members SET icon =".pwEscape($icon1)." WHERE uid=".pwEscape($rt['uid1']));
			}
		}
		$s_c++;
	}
	$SDB->free_result($query);

	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}bakuids LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '8'){
	//自定义积分
	$lastid = '0';
	file_exists(S_P."temp_2.php") && include_once(S_P."temp_2.php");

	$query = $SDB->query("SELECT m.*,bu.uid1
						  FROM {$source_prefix}membercredit m
						  LEFT JOIN {$source_prefix}bakuids bu ON m.uid=bu.uid2
						  WHERE m.uid > $start AND m.uid < $end
						  ORDER BY m.uid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['uid'];

		if(!empty($credArray) && !empty($credArray[$rt['cid']]['id1'])){
			$rt['cid'] = $credArray[$rt['cid']]['id1'];
		}

		$uid = $DDB->get_value("SELECT uid FROM {$pw_prefix}membercredit
								WHERE uid='".$rt['uid1']."' AND cid=".pwEscape($rt['cid'])." AND value=".pwEscape($rt['value']));
		if (!$uid){
			$DDB->update("INSERT INTO {$pw_prefix}membercredit (uid,cid,value) VALUES ('$rt[uid1]','$rt[cid]','$rt[value]')");
		}else{
			$DDB->update("UPDATE {$pw_prefix}membercredit SET value=value+".pwEscape($rt['value'])." WHERE uid=".pwEscape($uid)." AND cid=".pwEscape($rt['cid'])." LIMIT 1");
		}
		$s_c++;
	}
	$SDB->free_result($query);

	$maxid = $SDB->get_value("SELECT MAX(uid) FROM {$source_prefix}membercredit LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '9'){
	//勋章日志
	$lastid = '0';
	file_exists(S_P."temp_5.php") && require_once(S_P."temp_5.php");

	$query = $SDB->query("SELECT * from {$source_prefix}medalslogs
						   WHERE id > $start
						   ORDER BY id ASC
						   LIMIT $percount");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];

		//判断被颁发者的用户名是否被修改过
		$burt_ee = $SDB->get_one("SELECT * FROM {$source_prefix}bakuids WHERE username=".pwEscape($rt[awardee]));
		if(!empty($burt_ee['newname'])){
			$rt['awardee'] = $burt_ee['newname'];
		}
		//颁发者
		$burt_er = $SDB->get_one("SELECT * FROM {$source_prefix}bakuids WHERE username=".pwEscape($rt[awarder]));
		if(!empty($burt_er['newname'])){
			$rt['awarder'] = $burt_er['newname'];
		}
		if(!empty($medaliArray) && !empty($medaliArray[$rt['level']]['id2'])){
			$rt['level'] = $medaliArray[$rt['level']]['id1'];
		}
		$id = $DDB->get_value("SELECT id FROM {$pw_prefix}medalslogs WHERE awardee=".pwEscape($rt['awardee'])." AND awarder=".pwEscape($rt['awarder'])." AND awardtime=".pwEscape($rt['awardtime']));
		if (!$id){
			$DDB->update("INSERT INTO {$pw_prefix}medalslogs (awardee,awarder,awardtime,timelimit,state,level,action,why)VALUES('$rt[awardee]','$rt[awarder]','$rt[awardtime]','$rt[timelimit]','$rt[state]','$rt[level]','$rt[action]','$rt[why]')");
			//$DDB->update("INSERT INTO {$pw_prefix}medal_user (uid,mid) VALUES ('$burt_ee[uid1]','$rt[level]')");
		}
		$s_c++;
	}
	$SDB->free_result($query);

	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}medalslogs LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '10'){
    //短信
	$lastid = '0';

	echo "SELECT mm.mid AS mid,mm.create_uid AS mm_create_uid,mm.create_username AS mm_create_username,
						  mm.title AS mm_title,mm.content AS mm_content,mm.extra AS mm_extra,mm.expand AS mm_expand,
						  mm.attach AS mm_attach,mm.created_time AS mm_created_time,mm.modified_time AS mm_modified_time,
						  mr.uid AS mr_uid,mr.categoryid AS mr_categoryid,mr.typeid AS mr_typeid,mr.status AS mr_status,
						  mr.isown AS mr_isown,mr.created_time AS mr_created_time,mr.actived_time AS mr_actived_time,
						  mr.modified_time AS mr_modified_time,mr.relation AS mr_relation,
						  mp.create_uid AS mp_create_uid,mp.create_username AS mp_create_username,mp.title AS mp_title,
						  mp.content AS mp_content,mp.status AS mp_status,mp.created_time AS mp_created_time,mp.modified_time AS mp_modified_time
						  FROM {$source_prefix}ms_messages mm
						  LEFT JOIN {$source_prefix}ms_relations mr ON mm.mid=mr.mid
						  LEFT JOIN {$source_prefix}ms_replies mp ON mm.mid=mp.parentid
						  WHERE mm.mid > $start AND mm.mid < $end AND mm.create_username != ''"."<hr>";

	$query = $SDB->query("SELECT mm.mid AS mid,mm.create_uid AS mm_create_uid,mm.create_username AS mm_create_username,
						  mm.title AS mm_title,mm.content AS mm_content,mm.extra AS mm_extra,mm.expand AS mm_expand,
						  mm.attach AS mm_attach,mm.created_time AS mm_created_time,mm.modified_time AS mm_modified_time,
						  mr.uid AS mr_uid,mr.categoryid AS mr_categoryid,mr.typeid AS mr_typeid,mr.status AS mr_status,
						  mr.isown AS mr_isown,mr.created_time AS mr_created_time,mr.actived_time AS mr_actived_time,
						  mr.modified_time AS mr_modified_time,mr.relation AS mr_relation,
						  mp.create_uid AS mp_create_uid,mp.create_username AS mp_create_username,mp.title AS mp_title,
						  mp.content AS mp_content,mp.status AS mp_status,mp.created_time AS mp_created_time,mp.modified_time AS mp_modified_time
						  FROM {$source_prefix}ms_messages mm
						  LEFT JOIN {$source_prefix}ms_relations mr ON mm.mid=mr.mid
						  LEFT JOIN {$source_prefix}ms_replies mp ON mm.mid=mp.parentid
						  WHERE mm.mid > $start AND mm.mid < $end AND mm.create_username != ''");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['mid'];

		if($rt['mm_create_uid'] == '0' && $rt['mm_create_username'] == '系统'){
			$new_create_uid1 = $rt['mm_create_uid'];
			$new_create_username1 = $rt['mm_create_username'];
		}else{
			$new_create = $SDB->get_one("SELECT uid1,newname FROM {$source_prefix}bakuids
										 WHERE uid2=".pwEscape($rt['mm_create_uid'])." LIMIT 1");
			$new_create_uid1 = $new_create['uid1'];
			$new_create_username1 = $new_create['newname'];

			unset($new_create);
		}

		if(!empty($new_create_username1)){
			$messages[$rt['mid']] = array(
				'create_uid'		=>	$new_create_uid1,
				'create_username'	=>	$new_create_username1,
				'title'				=>	$rt['mm_title'],
				'content'			=>	$rt['mm_content'],
				'extra'				=>	$rt['mm_extra'],
				'expand'			=>	$rt['mm_expand'],
				'attach'			=>	$rt['mm_attach'],
				'created_time'		=>	$rt['mm_created_time'],
				'modified_time'		=>	$rt['mm_modified_time']
			);
		}

		//pw_ms_relations表
		if($rt['mr_uid'] != '0'){
			$new_uid = $SDB->get_value("SELECT uid1 FROM {$source_prefix}bakuids WHERE uid2=".pwEscape($rt['mr_uid'])." LIMIT 1");
		}else{
			$new_uid = $rt['mr_uid'];
		}
		if(!empty($rt['mr_relation'])){
			$relations[$rt['mid']][] = array(
				'uid'			=>	$new_uid,
				'categoryid'	=>	$rt['mr_categoryid'],
				'typeid'		=>	$rt['mr_typeid'],
				'status'		=>	$rt['mr_status'],
				'isown'			=>	$rt['mr_isown'],
				'created_time'	=>	$rt['mr_created_time'],
				'actived_time'	=>	$rt['mr_actived_time'],
				'modified_time'	=>	$rt['mr_modified_time'],
				'relation'		=>	$rt['mr_relation']
			);
		}

		//pw_ms_replies表
		if($rt['mp_create_uid'] == '0' && $rt['mp_create_username'] == '系统'){
			$new_create_uid2 = $rt['mp_create_uid'];
			$new_create_username2 = $rt['mp_create_username'];
		}else{
			$new_create2 = $SDB->get_one("SELECT uid1,newname FROM {$source_prefix}bakuids
										  WHERE uid2=".pwEscape($rt['mp_create_uid'])." LIMIT 1");
			$new_create_uid2 = $new_create2['uid1'];
			$new_create_username2 = $new_create2['newname'];
		}

		if(!empty($new_create_username2) && $rt['mr_isown'] == '0'){
			$replies[$rt['mid']][] = array(
				'create_uid'		=>	$new_create_uid2,
				'create_username'	=>	$new_create_username2,
				'title'				=>	$rt['mp_title'],
				'content'			=>	$rt['mp_content'],
				'status'			=>	$rt['mp_status'],
				'created_time'		=>	$rt['mp_created_time'],
				'modified_time'		=>	$rt['mp_modified_time']
			);
		}
		$s_c++;
	}
	$SDB->free_result($query);

	foreach ($messages AS $k => $v){
		$v && $DDB->update("REPLACE INTO {$pw_prefix}ms_messages SET ".pwSqlSingle($v));
		$new_mid = $DDB->insert_id();

		//pw_ms_relations数据
		foreach ($relations[$k] AS $kk => $vv){
			$vv['mid'] = $new_mid;
			$relations[$k][$kk] = $vv;
		}
		if(!empty($relations[$k])){
			$DDB->update("REPLACE INTO {$pw_prefix}ms_relations(uid,categoryid,typeid,status,isown,created_time,actived_time,modified_time,relation,mid)VALUES".pwSqlMulti($relations[$k]));
		}

		//pw_me_replies数据
		foreach ($replies[$k] AS $kk => $vv){
			$vv['parentid'] = $new_mid;
			$replies[$k][$kk] = $vv;
		}
		if(!empty($replies[$k])){
			$DDB->update("REPLACE INTO {$pw_prefix}ms_replies(create_uid,create_username,title,content,status,created_time,modified_time,parentid)VALUES".pwSqlMulti($replies[$k]));
		}
	}
	unset($messages, $relations, $replies);

	$maxid = $SDB->get_value("SELECT MAX(mid) FROM {$source_prefix}ms_messages LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '11'){
	//草稿箱
	$lastid  = '0';
	$draftArr = array();

	$query	= $SDB->query("SELECT dr.*,bu.uid1
						   FROM {$source_prefix}draft dr
						   LEFT JOIN {$source_prefix}bakuids bu on dr.uid=bu.uid2
						   WHERE dr.did > $start AND dr.did < $end
						   ORDER BY dr.did ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['did'];

		$did = $DDB->get_value("SELECT did FROM {$pw_prefix}draft
								WHERE uid=".pwEscape($rt['uid1'])." and content=".pwEscape($rt['content'])."
								LIMIT 1");
		if (!$did){
			$draftArr[] = array(
				'uid'		=>	$rt['uid1'],
				'content'	=>	$rt['content'],
			);
		}
		$s_c++;
	}
	$SDB->free_result($query);

	if(!empty($draftArr)){
		$DDB->update("INSERT INTO {$pw_prefix}draft (uid,content)VALUES".pwSqlMulti($draftArr));
		unset($draftArr);
	}

	$maxid = $SDB->get_value("SELECT MAX(did) FROM {$source_prefix}draft LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '12'){
	//动态
	$lastid = '0';
	$feedArr = array();

	echo "SELECT f.*,bu.uid1
						   FROM {$source_prefix}feed f
						   LEFT JOIN {$source_prefix}bakuids bu ON f.uid=bu.uid2
						   WHERE f.id > $start AND f.id < $end
						   ORDER BY f.id ASC"."<hr>";
	$query	= $SDB->query("SELECT f.*,bu.uid1
						   FROM {$source_prefix}feed f
						   LEFT JOIN {$source_prefix}bakuids bu ON f.uid=bu.uid2
						   WHERE f.id > $start AND f.id < $end
						   ORDER BY f.id ASC");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];

		if($rt['uid1']){
			$id = $DDB->get_value("SELECT id FROM {$pw_prefix}feed
								   WHERE uid=".$rt['uid1']." and timestamp=".pwEscape($rt['timestamp'])."
								   LIMIT 1");
			if (!$id){
				$feedArr[] = array(
					'uid'		=>	$rt['uid1'],
					'type'		=>	$rt['type'],
					'descrip'	=>	$rt['descrip'],
					'timestamp'	=>	$rt['timestamp'],
				);
			}
		}
		$s_c++;
	}
	$SDB->free_result($query);

	if(!empty($feedArr)){
		$DDB->update("INSERT INTO {$pw_prefix}feed (uid,type,descrip,timestamp)VALUES".pwSqlMulti($feedArr));
		unset($feedArr);
	}

	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}feed LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '13'){
	//好友
	$friendsArr = array();

	$query	= $SDB->query("SELECT * FROM {$source_prefix}friends
						   LIMIT $start, $percount");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);

		$request = $SDB->query("SELECT * FROM {$source_prefix}bakuids
								WHERE uid2=".pwEscape($rt['uid'])." OR uid2=".pwEscape($rt['friendid']));
		while ($us = $SDB->fetch_array($request)){
		    if($rt['uid'] != 0 && $rt['uid'] == $us['uid2']){
		    	$rt['uid'] = $us['uid1'];
		    }
		    if($rt['friendid'] != 0 && $rt['friendid'] == $us['uid2']){
		    	$rt['friendid'] = $us['uid1'];
		    }
		}

		$id = $SDB->get_value("SELECT uid FROM {$pw_prefix}friends
							   WHERE uid=".pwEscape($rt['uid'])." AND friendid=".pwEscape($rt['friendid'])." LIMIT 1");
		if (!$id){
			$friendsArr[] = array(
				'uid'		=>	$rt['uid'],
				'friendid'	=>	$rt['friendid'],
				'status'	=>	$rt['status'],
				'joindate'	=>	$rt['joindate'],
				'descrip'	=>	$rt['descrip'],
			);
		}
		$s_c++;
	}
	$SDB->free_result($query);

	if(!empty($friendsArr)){
		$DDB->update("REPLACE INTO {$pw_prefix}friends (uid,friendid,status,joindate,descrip)VALUES".pwSqlMulti($friendsArr));
		unset($friendsArr);
	}

	$maxid = $SDB->get_value("SELECT COUNT(*) AS count FROM {$source_prefix}friends");
	echo "最大id：".$maxid."<br>最后id：".$end;

	if($end < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '14'){
	//便签
	$lastid = '0';
	$memoArr = array();

	$query	= $SDB->query("SELECT * FROM {$source_prefix}memo
						   WHERE mid > $start AND mid < $end
						   ORDER BY mid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['mid'];

		$us = $SDB->get_one("select * from {$source_prefix}bakuids where username=".pwEscape($rt['username']));
		if($us['newname'] != ""){
			$rt['username'] = $us['newname'];
		}

		$mid = $SDB->get_value("SELECT mid FROM {$pw_prefix}memo
								WHERE username='".pwEscape($rt['username'])."' AND postdate=".pwEscape($rt['postdate'])."
								LIMIT 1");
		if (empty($mid)){
			$memoArr[] = array(
				'username'	=>	$rt['username'],
				'postdate'	=>	$rt['postdate'],
				'content'	=>	$rt['content'],
				'isuser'	=>	$rt['isuser'],
			);
		}
		$s_c++;
	}
	$SDB->free_result($query);

	if(!empty($memoArr)){
		$DDB->update("INSERT INTO {$pw_prefix}memo (username,postdate,content,isuser)VALUES".pwSqlMulti($memoArr));
		unset($memoArr);
	}

	$maxid = $SDB->get_value("SELECT MAX(mid) FROM {$source_prefix}memo LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '15'){
	//版块
	$lastid = '0';
	$forumArray = $forums = $forumdata = array();
	if(!$start){
		$SDB->query("DROP TABLE IF EXISTS {$source_prefix}bakfids;");
		$SDB->query("CREATE TABLE {$source_prefix}bakfids(
					 id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					 fid2 INT(10) NOT NULL ,
					 fid1 INT(10) NOT NULL ,
					 fname2 varchar(50) NOT NULL default '',
					 fname1 varchar(50) NOT NULL default '',
					 fup smallint(6) unsigned NOT NULL default '0',
					 PRIMARY KEY (id),
					 KEY `fid2` (`fid2`))ENGINE = MYISAM ;");
	}
	$query	= $SDB->query("SELECT f.*,
						   fd.tpost,fd.topic,fd.article,fd.subtopic,fd.top1,fd.top2,fd.topthreads,fd.aid,fd.aidcache,fd.aids,fd.lastpost
						   FROM {$source_prefix}forums f
						   LEFT JOIN {$source_prefix}forumdata fd USING(fid)
						   WHERE f.fid > $start AND f.fid < $end AND f.name != ''
						   ORDER BY f.fid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['fid'];

		//处理版主用户名
		$forumadmin = explode(",", $rt['forumadmin']);
		foreach ($forumadmin AS $k => $v){
			$newname = $SDB->get_value("SELECT newname FROM {$source_prefix}bakuids WHERE username=".pwEscape($v));
			$forumadmin[$k] = $newname;
		}
		$rt['forumadmin'] = implode(",", $forumadmin);

		$fid = $DDB->get_value("SELECT fid FROM {$pw_prefix}forums WHERE name=".pwEscape(strip_tags($rt['name'])));
		$newname = empty($fid) ? $rt['name'] : $repeatname.$rt['name'];

		$forums = array(
			'fup'			=>	$rt['fup'],
			'ifsub'			=>	$rt['ifsub'],
			'childid'		=>	$rt['childid'],
			'type'			=>	$rt['type'],
			'logo'			=>	$rt['logo'],
			'name'			=>	$newname,
			'descrip'		=>	$rt['descrip'],
			'title'			=>	$rt['title'],
			'dirname'		=>	$rt['dirname'],
			'metadescrip'	=>	$rt['metadescrip'],
			'keywords'		=>	$rt['keywords'],
			'vieworder'		=>	$rt['vieworder'],
			'forumadmin'	=>	$rt['forumadmin'],
			'fupadmin'		=>	$rt['fupadmin'],
			'style'			=>	$rt['style'],
			'across'		=>	$rt['across'],
			'allowhtm'		=>	$rt['allowhtm'],
			'allowhide'		=>	$rt['allowhide'],
			'allowsell'		=>	$rt['allowsell'],
			'allowtype'		=>	$rt['allowtype'],
			'copyctrl'		=>	$rt['copyctrl'],
			'allowencode'	=>	$rt['allowencode'],
			'password'		=>	$rt['password'],
			'viewsub'		=>	$rt['viewsub'],
			'allowvisit'	=>	$rt['allowvisit'],
			'allowread'		=>	$rt['allowread'],
			'allowpost'		=>	$rt['allowpost'],
			'allowrp'		=>	$rt['allowrp'],
			'allowdownload'	=>	$rt['allowdownload'],
			'allowupload'	=>	$rt['allowupload'],
			'modelid'		=>	$rt['modelid'],
			'forumsell'		=>	$rt['forumsell'],
			'pcid'			=>	$rt['pcid'],
			'f_type'		=>	$rt['f_type'],
			'f_check'		=>	$rt['f_check'],
			't_type'		=>	$rt['t_type'],
			'cms'			=>	$rt['cms'],
			'ifhide'		=>	$rt['ifhide'],
			'showsub'		=>	$rt['showsub'],
			'ifcms'			=>	$rt['ifcms'],
		);
		$DDB->update("INSERT INTO {$pw_prefix}forums SET ".pwSqlSingle($forums));
		$fid = $DDB->insert_id();

		$forumdata = array(
			'fid'		=>	$fid,
			'tpost'		=>	$rt['tpost'],
			'topic'		=>	$rt['topic'],
			'article'	=>	$rt['article'],
			'subtopic'	=>	$rt['subtopic'],
			'top1'		=>	$rt['top1'],
			'top2'		=>	$rt['top2'],
			'aid'		=>	$rt['aid'],
			'aidcache'	=>	$rt['aidcache'],
			'aids'		=>	$rt['aids'],
			'lastpost'	=>	$rt['lastpost'],
		);
		$DDB->update("INSERT INTO {$pw_prefix}forumdata SET ".pwSqlSingle($forumdata));
		unset($forums, $forumdata);

		$forumArray[] = array(
			'fid1'		=>	$fid,
			'fid2'		=>	$rt['fid'],
			'fname1'	=>	$rt['name'],
			'fname2'	=>	$newname,
			'fup'		=>	$rt['fup'],
		);
		$s_c++;
	}
	$SDB->free_result($query);

	if (!empty($forumArray)){
		$SDB->update("INSERT INTO {$source_prefix}bakfids (fid1,fid2,fname1,fname2,fup) VALUES".pwSqlMulti($forumArray));

		foreach ($forumArray as $k => $v){
			$newfup = $SDB->get_value("SELECT fid1 FROM {$source_prefix}bakfids WHERE fid2=".pwEscape($v['fup'])." LIMIT 1");
			$DDB->update("UPDATE {$pw_prefix}forums SET fup=".pwEscape($newfup)." WHERE fid=".pwEscape($v['fid1']));
		}
	}
	unset($forumArray);

	$maxid = $SDB->get_value("SELECT MAX(fid) FROM {$source_prefix}forums LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '16'){
	//版块数据
	$lastid = '0';

	$query	= $SDB->query("SELECT fe.*,bf.fid1
						   FROM {$source_prefix}forumsextra fe
						   LEFT JOIN {$source_prefix}bakfids bf ON bf.fid2=fe.fid
						   WHERE fe.fid > $start AND fe.fid < $end AND bf.fid1 != ''
						   ORDER BY fe.fid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['fid'];

		$fid = $DDB->get_value("SELECT fid FROM {$pw_prefix}forumsextra WHERE fid=".pwEscape($rt['fid1'])." LIMIT 1");
		if (!$fid){
			$DDB->update("INSERT INTO {$pw_prefix}forumsextra (fid,creditset,forumset)VALUES('$rt[fid1]','$rt[creditset]','$rt[forumset]')");
		}
		$s_c++;
	}
	$SDB->free_result($query);

	$maxid = $SDB->get_value("SELECT MAX(fid) FROM {$source_prefix}forumsextra LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '17'){
	//帖子
	$lastid = '0';
	$baktids = $tmsgsArr = array();

	if(!$start){
		$SDB->query("DROP TABLE IF EXISTS {$source_prefix}baktids;");
		$SDB->query("CREATE TABLE {$source_prefix}baktids(
					 id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					 tid2 INT(10) NOT NULL,
					 tid1 INT(10) NOT NULL,
					 uid1 INT(10) NOT NULL,
					 newname varchar(15) NOT NULL default '',
					 fid1 INT(10) NOT NULL,
					 PRIMARY KEY (id),
					 KEY `tid2` (`tid2`))ENGINE = MYISAM ;");
	}

	echo "SELECT t.*,tm.*,bf.fid1,bu.uid1,bu.newname
						  FROM {$source_prefix}threads t
						  LEFT JOIN {$source_prefix}tmsgs tm ON tm.tid=t.tid
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=t.authorid
						  LEFT JOIN {$source_prefix}bakfids bf ON bf.fid2=t.fid
						  WHERE t.tid > $start AND t.tid < $end
						  ORDER BY t.tid ASC "."<hr>";
	$query = $SDB->query("SELECT t.*,tm.*,bf.fid1,bu.uid1,bu.newname
						  FROM {$source_prefix}threads t
						  LEFT JOIN {$source_prefix}tmsgs tm ON tm.tid=t.tid
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=t.authorid
						  LEFT JOIN {$source_prefix}bakfids bf ON bf.fid2=t.fid
						  WHERE t.tid > $start AND t.tid < $end
						  ORDER BY t.tid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['tid'];
		$rt['author'] = $rt['newname'] ? $rt['newname'] : $rt['author'];

		$tid = $DDB->get_value("SELECT fid FROM {$pw_prefix}threads
								WHERE authorid=".pwEscape($rt['authorid'])." AND subject=".pwEscape($rt['subject'])." and postdate=".pwEscape($rt['postdate'])."
								LIMIT 1");
		if(!$tid){
			$DDB->update("INSERT INTO {$pw_prefix}threads (fid,icon,titlefont,author,authorid,subject,toolinfo,toolfield,ifcheck,type,postdate,lastpost,lastposter,hits,replies,favors,modelid,shares,topped,topreplays,locked,digest,special,state,ifupload,ifmail,ifmark,ifshield,anonymous,dig,fight,ptable,ifmagic,ifhide,inspect,tpcstatus)VALUES('$rt[fid1]','$rt[icon]','$rt[titlefont]','$rt[author]','$rt[uid1]','$rt[subject]','$rt[toolinfo]','$rt[toolfield]','$rt[ifcheck]','$rt[type]','$rt[postdate]','$rt[lastpost]','$rt[lastposter]','$rt[hits]','$rt[replies]','$rt[favors]','$rt[modelid]','$rt[shares]','$rt[topped]','$rt[topreplays]','$rt[locked]','$rt[digest]','$rt[special]','$rt[state]','$rt[ifupload]','$rt[ifmail]','$rt[ifmark]','$rt[ifshield]','$rt[anonymous]','$rt[dig]','$rt[fight]','$rt[ptable]','$rt[ifmagic]','$rt[ifhide]','$rt[inspect]','$rt[tpcstatus]')");
			$tid = $DDB->insert_id();

			$tmsgsArr[] = array(
				'tid'			=>	$tid,
				'aid'			=>	$rt['aid'],
				'userip'		=>	$rt['userip'],
				'ifsign'		=>	$rt['ifsign'],
				'buy'			=>	$rt['buy'],
				'ipfrom'		=>	$rt['ipfrom'],
				'alterinfo'		=>	$rt['alterinfo'],
				'remindinfo'	=>	$rt['remindinfo'],
				'tags'			=>	$rt['tags'],
				'ifconvert'		=>	$rt['ifconvert'],
				'ifwordsfb'		=>	$rt['ifwordsfb'],
				'content'		=>	$rt['content'],
				'form'			=>	$rt['form'],
				'ifmark'		=>	$rt['ifmark'],
				'c_from'		=>	$rt['c_from'],
				'magic'			=>	$rt['magic'],
			);

			$baktids[] = array(
				'tid1'		=>	$tid,
				'tid2'		=>	$rt['tid'],
				'uid1'		=>	$rt['uid1'],
				'newname'	=>	$rt['author'],
				'fid1'		=>	$rt['fid1'],
			);
		}
		$s_c++;
	}
	$SDB->free_result($query);

	if(!empty($tmsgsArr)){
		$DDB->update("INSERT INTO {$pw_prefix}tmsgs (tid,aid,userip,ifsign,buy,ipfrom,alterinfo,remindinfo,tags,ifconvert,ifwordsfb,content,form,ifmark,c_from,magic)
		VALUES".pwSqlMulti($tmsgsArr));
		unset($tmsgsArr);
	}

	if (!empty($baktids)){
		$SDB->update("INSERT INTO {$source_prefix}baktids(tid1,tid2,uid1,newname,fid1)VALUES".pwSqlMulti($baktids));
		unset($baktids);
	}

	$maxid = $SDB->get_value("SELECT MAX(tid) FROM {$source_prefix}threads LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '18'){
	//帖子标签
	$lastid = '0';
	$tagdata = array();
	file_exists(S_P."temp_3.php") && require_once(S_P."temp_3.php");

	$query = $SDB->query("SELECT td.tid,bt.fid1,bt.tid1,tg.*
						  FROM {$source_prefix}tagdata td
						  LEFT JOIN {$source_prefix}baktids bt ON td.tid=bt.tid2
						  LEFT JOIN {$source_prefix}tags tg ON td.tagid=tg.tagid
						  WHERE td.tagid > $start AND td.tagid < $end
						  ORDER BY td.tagid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['tagid'];

		if(!empty($tagsArray) && !empty($tagsArray[$rt['tagid']]['tagid1'])){
			$rt['tagid'] = $tagsArray[$rt['tagid']]['tagid1'];
		}

		$tid = $DDB->get_value("SELECT tid FROM {$pw_prefix}tagdata
								WHERE tid=".pwEscape($rt['tid1'])." AND tagid=".pwEscape($rt['tagid']));
		if (!$tid){
			$tagdata[] = array(
				'tagid'	=>	$rt['tagid'],
				'tid'	=>	$rt['tid1'],
			);
		}
		$s_c++;
	}
	$SDB->free_result($query);
	if(!empty($tagdata)){
		$DDB->update("INSERT INTO {$pw_prefix}tagdata (tagid,tid) VALUES".pwSqlMulti($tagdata));
		unset($tagdata);
	}

	$maxid = $SDB->get_value("SELECT MAX(tagid) FROM {$source_prefix}tagdata LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '19'){
	//辩论护具
	$lastid = '0';
	$debatesArr = array();

	$query	= $SDB->query("SELECT de.*,bt.uid1,bt.tid1,bt.fid1,bu.username,bu.newname
						   FROM {$source_prefix}debates de
						   LEFT JOIN {$source_prefix}baktids bt on de.tid=bt.tid2
						   LEFT JOIN {$source_prefix}bakuids bu on de.authorid=bu.uid2
						   WHERE de.tid > $start AND de.tid < $end
						   ORDER BY de.tid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['tid'];

		$tid = $DDB->get_value("SELECT tid FROM {$pw_prefix}debates WHERE tid=".pwEscape($rt['tid1'])." LIMIT 1");
		if (!$tid){
			$debatesArr[] = array(
				'tid'			=>	$rt['tid1'],
				'authorid'		=>	$rt['uid1'],
				'postdate'		=>	$rt['postdate'],
				'obtitle'		=>	$rt['obtitle'],
				'retitle'		=>	$rt['retitle'],
				'endtime'		=>	$rt['endtime'],
				'obvote'		=>	$rt['obvote'],
				'revote'		=>	$rt['revote'],
				'obposts'		=>	$rt['obposts'],
				'reposts'		=>	$rt['reposts'],
				'umpire'		=>	$rt['umpire'],
				'umpirepoint'	=>	$rt['umpirepoint'],
				'debater'		=>	$rt['debater'],
				'judge'			=>	$rt['judge'],
			);
		}
		$s_c++;
	}
	$SDB->free_result($query);

	if(!empty($debatesArr)){
		$DDB->update("INSERT INTO {$pw_prefix}debates (tid,authorid,postdate,obtitle,retitle,endtime,obvote,revote,obposts,reposts,umpire,umpirepoint,debater,judge)
		VALUES".pwSqlMulti($debatesArr));
		unset($debatesArr);
	}

	$maxid = $SDB->get_value("SELECT MAX(tid) FROM {$source_prefix}debates LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '20'){
	//投票
	$lastid = '0';
	$pollsArr = array();

	$query	= $SDB->query("SELECT p.*,bt.tid1
						   FROM {$source_prefix}polls p
						   LEFT JOIN {$source_prefix}baktids bt ON p.tid=bt.tid2
						   WHERE p.pollid > $start AND p.pollid < $end
						   ORDER BY p.pollid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['pollid'];

		$tid = $DDB->get_value("SELECT tid FROM {$pw_prefix}polls WHERE tid=".pwEscape($rt['tid1'])." LIMIT 1");
		if (!$tid && $rt['tid1']){
			$pollsArr[] = array(
				'tid'			=>	$rt['tid1'],
				'voteopts'		=>	$rt['voteopts'],
				'modifiable'	=>	$rt['modifiable'],
				'previewable'	=>	$rt['previewable'],
				'multiple'		=>	$rt['multiple'],
				'mostvotes'		=>	$rt['mostvotes'],
				'voters'		=>	$rt['voters'],
				'timelimit'		=>	$rt['timelimit'],
				'leastvotes'	=>	$rt['leastvotes'],
				'regdatelimit'	=>	$rt['regdatelimit'],
				'creditlimit'	=>	$rt['creditlimit'],
				'postnumlimit'	=>	$rt['postnumlimit'],
			);
		}
		$s_c++;
	}
	$SDB->free_result($query);

	if(!empty($pollsArr)){
		$DDB->update("INSERT INTO {$pw_prefix}polls (tid,voteopts,modifiable,previewable,multiple,mostvotes,voters,timelimit,leastvotes,regdatelimit,creditlimit,postnumlimit)
		VALUES".pwSqlMulti($pollsArr));
		unset($pollsArr);
	}

	$maxid = $SDB->get_value("SELECT MAX(pollid) FROM {$source_prefix}polls LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '21'){
	//悬赏
	$lastid = '0';
	$rewardArr = array();

	$query = $SDB->query("SELECT r.*,bt.tid1
						  FROM {$source_prefix}reward r
						  LEFT JOIN {$source_prefix}baktids bt ON r.tid=bt.tid2
						  WHERE r.tid > $start AND r.tid < $end
						  ORDER BY r.tid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['tid'];

		$tid = $DDB->get_value("SELECT tid FROM {$pw_prefix}reward WHERE tid=".pwEscape($rt['tid1'])." LIMIT 1");
		if (!$tid){
			$rewardArr[] = array(
				'tid'		=>	$rt['tid1'],
				'cbtype'	=>	$rt['cbtype'],
				'catype'	=>	$rt['catype'],
				'cbval'		=>	$rt['cbval'],
				'caval'		=>	$rt['caval'],
				'timelimit'	=>	$rt['timelimit'],
				'author'	=>	$rt['author'],
				'pid'		=>	$rt['pid'],
			);
		}
		$s_c++;
	}
	$SDB->free_result($query);

	if(!empty($rewardArr)){
		$DDB->update("INSERT INTO {$pw_prefix}reward(tid,cbtype,catype,cbval,caval,timelimit,author,pid)VALUES".pwSqlMulti($rewardArr));
		unset($rewardArr);
	}

	$maxid = $SDB->get_value("SELECT MAX(tid) FROM {$source_prefix}reward LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '22'){
	//交易
	$lastid = '0';
	$tradeArr = array();

	$query	= $SDB->query("SELECT t.*,bt.tid1,bt.uid1
						   FROM {$source_prefix}trade t
						   LEFT JOIN {$source_prefix}baktids bt ON t.tid=bt.tid2
						   WHERE t.tid > $start AND t.tid < $end
						   ORDER BY t.tid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['tid'];

		$tid = $DDB->get_value("SELECT tid FROM {$pw_prefix}trade WHERE tid=".pwEscape($rt['tid1'])." LIMIT 1");
		if (!$tid && $rt['tid1']){
			$tradeArr[] = array(
				'tid'			=>	$rt['tid1'],
				'uid'			=>	$rt['uid1'],
				'name'			=>	$rt['name'],
				'icon'			=>	$rt['icon'],
				'degree'		=>	$rt['degree'],
				'type'			=>	$rt['type'],
				'num'			=>	$rt['num'],
				'salenum'		=>	$rt['salenum'],
				'price'			=>	$rt['price'],
				'costprice'		=>	$rt['costprice'],
				'locus'			=>	$rt['locus'],
				'paymethod'		=>	$rt['paymethod'],
				'transport'		=>	$rt['transport'],
				'mailfee'		=>	$rt['mailfee'],
				'expressfee'	=>	$rt['expressfee'],
				'emsfee'		=>	$rt['emsfee'],
				'deadline'		=>	$rt['deadline'],
			);
		}
		$s_c++;
	}
	$SDB->free_result($query);

	if(!empty($tradeArr)){
		$DDB->update("INSERT INTO {$pw_prefix}trade (tid,uid,name,icon,degree,type,num,salenum,price,costprice,locus,paymethod,transport,mailfee,expressfee,emsfee,deadline)VALUES".pwSqlMulti($tradeArr));
		unset($tradeArr);
	}

	$maxid = $SDB->get_value("SELECT MAX(tid) FROM {$source_prefix}trade LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '23'){
    //活动
	$lastid = '0';
	$query	= $SDB->query("SELECT a.*,bt.tid1,bt.uid1
						   FROM {$source_prefix}activity a
						   LEFT JOIN {$source_prefix}baktids bt ON a.tid=bt.tid2
						   WHERE a.tid > $start AND a.tid < $end
						   ORDER BY a.tid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['tid'];

		$tid = $DDB->get_value("SELECT tid FROM {$pw_prefix}activity WHERE tid=".pwEscape($rt['tid1'])." LIMIT 1");
		if (!$tid && $rt['tid1']){
			$DDB->update("INSERT INTO {$pw_prefix}activity (tid,subject,admin,starttime,endtime,location,num,sexneed,costs,deadline)VALUES('$rt[tid1]','$rt[subject]','$rt[uid1]','$rt[starttime]','$rt[endtime]','$rt[location]','$rt[num]','$rt[sexneed]','$rt[costs]','$rt[deadline]')");
		}
		$s_c++;
	}
	$SDB->free_result($query);

	$maxid = $SDB->get_value("SELECT MAX(tid) FROM {$source_prefix}activity LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '24'){
	//活动成员
	$lastid = '0';
	$query	= $SDB->query("SELECT a.*,bt.tid1,bu.uid1
						   FROM {$source_prefix}actmember a
						   LEFT JOIN {$source_prefix}baktids bt ON a.actid=bt.tid2
						   LEFT JOIN {$source_prefix}bakuids bu on a.winduid=bu.uid2
						   WHERE a.id > $start AND a.id < $end
						   ORDER BY a.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];

		$tid = $DDB->get_value("SELECT id FROM {$pw_prefix}actmember
								WHERE actid=".pwEscape($rt['tid1'])." AND winduid=".pwEscape($rt['uid1'])."
								LIMIT 1");
		if (!$tid){
			$DDB->update("INSERT INTO {$pw_prefix}actmember (actid,winduid,state,applydate,contact,message)
						  VALUES('$rt[tid1]','$rt[uid1]','$rt[state]','$rt[applydate]','$rt[contact]','$rt[message]')");
		}
		$s_c++;
	}
	$SDB->free_result($query);

	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}actmember LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '25'){
	//回复
	$lastid = '0';
	$posts = array();
	if(!$start){
		$SDB->query("DROP TABLE IF EXISTS {$source_prefix}bakpids;");
		$SDB->query("CREATE TABLE {$source_prefix}bakpids (
					 id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					 pid2 INT(10) NOT NULL,
					 pid1 INT(10) NOT NULL,
					 uid1 INT(10) NOT NULL,
					 PRIMARY KEY (id),
					 KEY `pid2` (`pid2`)
					 )ENGINE = MYISAM ;");
	}

	echo "SELECT p.*,t.tid1,t.fid1,u.uid1,u.newname
						   FROM {$source_prefix}posts p
						   LEFT JOIN {$source_prefix}baktids t ON t.tid2=p.tid
						   LEFT JOIN {$source_prefix}bakuids u ON u.uid2=p.authorid
						   WHERE p.pid > $start AND p.pid < $end"."<hr>";
	$query	= $SDB->query("SELECT p.*,t.tid1,t.fid1,u.uid1,u.newname
						   FROM {$source_prefix}posts p
						   LEFT JOIN {$source_prefix}baktids t ON t.tid2=p.tid
						   LEFT JOIN {$source_prefix}bakuids u ON u.uid2=p.authorid
						   WHERE p.pid > $start AND p.pid < $end");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['pid'];
		$rt['newname'] != "" && $rt['author'] = $rt['newname'];

		$DDB->update("INSERT INTO {$pw_prefix}posts (fid,tid,aid,author,authorid,icon,postdate,subject,userip,ifsign,buy,alterinfo,remindinfo,leaveword,ipfrom,ifconvert,ifwordsfb,ifcheck,content,ifmark,ifreward,ifshield,anonymous,ifhide)VALUES ('$rt[fid1]','$rt[tid1]','$rt[aid]','$rt[author]','$rt[uid1]','$rt[icon]','$rt[postdate]','$rt[subject]','$rt[userip]','$rt[ifsign]','$rt[buy]','$rt[alterinfo]','$rt[remindinfo]','$rt[leaveword]','$rt[ipfrom]','$rt[ifconvert]','$rt[ifwordsfb]','$rt[ifcheck]','$rt[content]','$rt[ifmark]','$rt[ifreward]','$rt[ifshield]','$rt[anonymous]','$rt[ifhide]')");
		$pid = $DDB->insert_id();

		$posts[] = array(
			'pid2'	=>	$rt['pid'],
			'pid1'	=>	$pid,
			'uid1'	=>	$rt['uid1']
		);
		$s_c++;
	}
	$SDB->free_result($query);
	if(!empty($posts)){
		$SDB->update("INSERT INTO {$source_prefix}bakpids (pid2,pid1,uid1) VALUES".pwSqlMulti($posts));
		unset($posts);
	}

	$maxid = $SDB->get_value("SELECT MAX(pid) FROM {$source_prefix}posts LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '26'){
	//帖子回收站
	$lastid = '0';
	$recycleArr = array();

	$query	= $SDB->query("SELECT re.*,bt.tid1,bbf.fid1,bf.pid1
						   FROM {$source_prefix}recycle re
						   LEFT JOIN {$source_prefix}baktids bt ON re.tid=bt.tid2
						   LEFT JOIN {$source_prefix}bakpids bf ON bf.pid2=re.pid
						   LEFT JOIN {$source_prefix}bakfids bbf ON re.fid=bbf.fid2
						   WHERE re.pid > $start AND re.pid < $end
						   ORDER BY re.pid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['pid'];
		$rt['pid1'] == "" && $rt['pid1'] = '0';

		$pid = $DDB->get_value("SELECT pid FROM {$pw_prefix}recycle
								WHERE tid=".pwEscape($rt['tid1'])." AND pid=".pwEscape($rt['pid1'])."
								LIMIT 1");
		if (!$pid){
			$recycleArr[] = array(
				'pid'	=>	$rt['pid1'],
				'tid'	=>	$rt['tid1'],
				'fid'	=>	$rt['fid1'],
				'deltime'	=>	$rt['deltime'],
				'admin'		=>	$rt['admin'],
			);
		}
		$s_c++;
	}
	$SDB->free_result($query);

	if(!empty($recycleArr)){
		$DDB->update("INSERT INTO {$pw_prefix}recycle (pid,tid,fid,deltime,admin)VALUES".pwSqlMulti($recycleArr));
		unset($recycleArr);
	}

	$maxid = $SDB->get_value("SELECT MAX(pid) FROM {$source_prefix}recycle LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '27'){
	//辩论支持记录
	$lastid = '0';
	$debatedataArr = array();

	$query	= $SDB->query("SELECT de.*,bt.tid1,bu.uid1,bf.pid1
						   FROM {$source_prefix}debatedata de
						   LEFT JOIN {$source_prefix}baktids bt ON de.tid=bt.tid2
						   LEFT JOIN {$source_prefix}bakuids bu ON de.authorid=bu.uid2
						   LEFT JOIN {$source_prefix}bakpids bf ON bf.pid2=de.pid
						   WHERE de.pid > $start AND de.pid < $end
						   ORDER BY de.pid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['pid'];
		$rt['pid1'] == "" && $rt['pid1'] = 0;

		$pid = $DDB->get_value("SELECT pid FROM {$pw_prefix}debatedata
								WHERE tid=".pwEscape($rt['tid1'])." AND pid=".pwEscape($rt['pid1'])." AND authorid=".pwEscape($rt['uid1'])."
								LIMIT 1");
		if (!$pid){
			$debatedataArr[] = array(
				'pid'			=>	$rt['pid1'],
				'tid'			=>	$rt['tid1'],
				'authorid'		=>	$rt['uid1'],
				'standpoint'	=>	$rt['standpoint'],
				'postdate'		=>	$rt['postdate'],
				'vote'			=>	$rt['vote'],
				'voteids'		=>	$rt['voteids'],
			);
		}
		$s_c++;
	}
	$SDB->free_result($query);

	if(!empty($debatedataArr)){
		$DDB->update("REPLACE INTO {$pw_prefix}debatedata (pid,tid,authorid,standpoint,postdate,vote,voteids)VALUES".pwSqlMulti($debatedataArr));
		unset($debatedataArr);
	}

	$maxid = $SDB->get_value("SELECT MAX(pid) FROM {$source_prefix}debatedata LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '28'){
	//帖子报告
	$lastid = '0';
	$reportArr = array();

	$query	= $SDB->query("SELECT de.*,bt.tid1,bu.uid1,bf.pid1
						   FROM {$source_prefix}report de
						   LEFT JOIN {$source_prefix}baktids bt ON de.tid=bt.tid2
						   LEFT JOIN {$source_prefix}bakuids bu ON de.uid=bu.uid2
						   LEFT JOIN {$source_prefix}bakpids bf ON bf.pid2=de.pid
						   WHERE de.id > $start AND de.id < $end
						   ORDER BY de.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];
		$rt['pid1'] == "" && $rt['pid1'] = 0;

		$id = $DDB->get_value("SELECT id FROM {$pw_prefix}report WHERE tid=".pwEscape($rt['tid1'])." AND pid=".pwEscape($rt['pid1']));
		if (!$id){
			$reportArr[] = array(
				'tid'		=>	$rt['tid1'],
				'pid'		=>	$rt['pid1'],
				'uid'		=>	$rt['uid1'],
				'type'		=>	$rt['type'],
				'reason'	=>	$rt['reason'],
			);
		}
		$s_c++;
	}
	$SDB->free_result($query);

	if(!empty($reportArr)){
		$DDB->update("INSERT INTO {$pw_prefix}report(tid,pid,uid,type,reason)VALUES".pwSqlMulti($reportArr));
		unset($reportArr);
	}

	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}report LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '29'){
	//个人收藏夹
	$lastid = '0';
	$favors = array();
	$uid = $uid1 = '';

	$query	= $SDB->query("SELECT *
						   FROM {$source_prefix}favors
						   LIMIT $start, $percount");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid++;
		if(!empty($rt['tids'])){
			$tidsArr = array();

			$btquery = $SDB->query("SELECT tid1 FROM {$source_prefix}baktids WHERE tid2 IN (".$rt['tids'].")");
			while ($btrt = $SDB->fetch_array($btquery)){
				$tidsArr[] = $btrt['tid1'];
			}
			$rt['tids'] = implode(',',$tidsArr);
			unset($tidsArr);
		}

		$uid = $DDB->get_value("SELECT uid FROM {$pw_prefix}favors WHERE uid=".pwEscape($uid1)." LIMIT 1");
		if (!$uid){
			$uid1 = $SDB->get_value("SELECT uid1 FROM {$source_prefix}bakuids WHERE uid2=".pwEscape($rt['uid'])." LIMIT 1");
			$favors[] = array(
				'uid'	=>	$uid1,
				'tids'	=>	$rt['tids'],
				'type'	=>	$rt['type']
			);
		}
		$s_c++;
	}
	$SDB->free_result($query);

	if(!empty($favors)){
		$DDB->update("REPLACE INTO {$pw_prefix}favors (uid,tids,type)VALUES".pwSqlMulti($favors));
		unset($favors);
	}

	if($lastid == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '30'){
	//友情链接
	$lastid = '0';
	$sharelinksArr = array();

	$query	= $SDB->query("SELECT * FROM {$source_prefix}sharelinks
						   LIMIT $start, $percount");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid++;

		$sid = $DDB->get_value("SELECT sid FROM {$pw_prefix}sharelinks WHERE url=".pwEscape($rt['url'])." LIMIT 1");
		if (!$sid){
			$sharelinksArr[] = array(
				'threadorder'	=>	$rt['threadorder'],
				'name'			=>	$rt['name'],
				'url'			=>	$rt['url'],
				'descrip'		=>	$rt['descrip'],
				'logo'			=>	$rt['logo'],
				'ifcheck'		=>	$rt['ifcheck'],
			);
		}
		$s_c++;
	}
	$SDB->free_result($query);

	if(!empty($sharelinksArr)){
		$DDB->update("INSERT INTO {$pw_prefix}sharelinks(threadorder,name,url,descrip,logo,ifcheck)VALUES".pwSqlMulti($sharelinksArr));
		unset($sharelinksArr);
	}

	if($lastid == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '31'){
	//群组分类
	$lastid = '0';
	$cnclassArr = array();

	$query	= $SDB->query("SELECT cc.*,bf.fid1
						   FROM {$source_prefix}cnclass cc
						   LEFT JOIN {$source_prefix}bakfids bf ON bf.fid2=cc.fid
						   WHERE cc.fid > $start AND cc.fid < $end AND cc.cname != ''
						   ORDER BY cc.fid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['fid'];

		$fid = $DDB->get_value("SELECT fid FROM {$pw_prefix}cnclass WHERE cname=".pwEscape($rt['cname'])." LIMIT 1");
		if (!$fid){
			$cnclassArr[] = array(
				'fid'		=>	$rt['fid1'],
				'cname'		=>	$rt['cname'],
				'cnsum'		=>	$rt['cnsum'],
				'ifopen'	=>	$rt['ifopen'],
			);
		}
		$s_c++;
	}
	$SDB->free_result($query);

	if(!empty($cnclassArr)){
		$DDB->update("INSERT INTO {$pw_prefix}cnclass (fid,cname,cnsum,ifopen)VALUES".pwSqlMulti($cnclassArr));
		unset($cnclassArr);
	}

	$maxid = $SDB->get_value("SELECT MAX(fid) FROM {$source_prefix}cnclass LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '32'){
    //群组
	$lastid = '0';
	$colonysArray = array();
	$cnimg_dir2 = R_P.'cn_img';
	$cnimg_dir1 = R_P.'attachment/cn_img';
	file_exists(S_P."temp_32.php") && require_once(S_P."temp_32.php");

	if (!is_dir($cnimg_dir2) || !is_dir($cnimg_dir1) || !is_readable($cnimg_dir2) || !N_writable($cnimg_dir1)){
		echo "请将次论坛的attachment/cn_img/文件夹移动到pwb根目录，且设定权限为777。";
		exit;
	}

	$query	= $SDB->query("SELECT cl.*,bf.fid1,bu.newname
						   FROM {$source_prefix}colonys cl
						   LEFT JOIN {$source_prefix}bakfids bf ON bf.fid2=cl.classid
						   LEFT JOIN {$source_prefix}bakuids bu ON bu.username=cl.admin
						   WHERE cl.id > $start AND cl.id < $end
						   ORDER BY cl.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];
		$rt['newname'] != "" && $rt['admin'] = $rt['newname'];

		$id = $DDB->get_value("SELECT id FROM {$pw_prefix}colonys WHERE cname=".pwEscape($rt['cname'])." AND classid=".pwEscape($rt['fid1']));
		if (!$id){
		    $DDB->update("INSERT INTO {$pw_prefix}colonys (classid,cname,admin,members,ifcheck,ifopen,cnimg,banner,createtime,annouce,albumnum,annoucesee,descrip,visitor)VALUES('$rt[fid1]','$rt[cname]','$rt[admin]','$rt[members]','$rt[ifcheck]','$rt[ifopen]','$rt[cnimg]','$rt[banner]','$rt[createtime]','$rt[annouce]','$rt[albumnum]','$rt[annoucesee]','$rt[descrip]','$rt[visitor]')");
		    $id = $DDB->insert_id();

		    //更新头像信息
		    $cnimgArr = explode('.',$rt['cnimg']);
		    $newcnimg = "colony_".$id.".".$cnimgArr['1'];
		    $DDB->update("UPDATE {$pw_prefix}colonys SET cnimg=".pwEscape($newcnimg)." WHERE id=".pwEscape($id));

		    @copy($cnimg_dir2."/".$rt['cnimg'], $cnimg_dir1."/".$newcnimg);
		    $colonysArray[$rt['id']] = array(
				'id1'	=>	$id,
				'id2'	=>	$rt['id'],
				'cname'	=>	$rt['cname'],
				'cnimg'	=>	$newcnimg,
			);
		}
		$s_c++;
	}
	$SDB->free_result($query);

    if (!empty($colonysArray)){
		writeover(S_P.'temp_32.php',"\r\n\$colonysArray=".pw_var_export($colonysArray).";\n", TRUE);
	}

	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}colonys LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '33'){
	//群组成员
	$lastid = '0';
	$cmembers = array();
	file_exists(S_P."temp_32.php") && require_once(S_P."temp_32.php");

	$query	= $SDB->query("SELECT cm.*,bu.uid1,bu.newname
						   FROM {$source_prefix}cmembers cm
						   LEFT JOIN {$source_prefix}bakuids bu ON cm.uid=bu.uid2
						   WHERE cm.id > $start AND cm.id < $end
						   ORDER BY cm.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];
		$rt['newname'] != "" && $rt['username'] = $rt['newname'];

		if(!empty($colonysArray) && !empty($colonysArray[$rt['colonyid']]['id1'])){
			$rt['colonyid'] = $colonysArray[$rt['colonyid']]['id1'];
		}

		$id = $DDB->get_value("SELECT id FROM {$pw_prefix}cmembers WHERE uid=".pwEscape($rt['uid1'])." and username=".pwEscape($rt['username'])." and colonyid=".pwEscape($rt['colonyid']));
		if (!$id){
			$cmembers[] = array(
				'uid'	=>	$rt['uid1'],
				'username'	=>	$rt['username'],
				'realname'	=>	$rt['realname'],
				'ifadmin'	=>	$rt['ifadmin'],
				'gender'	=>	$rt['gender'],
				'tel'		=>	$rt['tel'],
				'email'		=>	$rt['email'],
				'colonyid'	=>	$rt['colonyid'],
				'address'	=>	$rt['address'],
				'introduce'	=>	$rt['introduce'],
				'addtime'	=>	$rt['addtime'],
				'lastvisit'	=>	$rt['lastvisit']
			);
		}
		$s_c++;
	}
	$SDB->free_result($query);

	if(!empty($cmembers)){
		$DDB->update("INSERT INTO {$pw_prefix}cmembers(uid,username,realname,ifadmin,gender,tel,email,colonyid,address,introduce,addtime,lastvisit)VALUES".pwSqlMulti($cmembers));
		unset($cmembers);
	}

	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}cmembers LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '34'){
	//群组帖子
	$lastid = '0';
	$argumentArr = array();
	file_exists(S_P."temp_32.php") && require_once(S_P."temp_32.php");

	$query	= $SDB->query("SELECT *
						   FROM {$source_prefix}argument
						   LIMIT $start, $percount");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid++;

		if(!empty($colonysArray) && !empty($colonysArray[$rt['cyid']]['id1'])){
			$rt['cyid'] = $colonysArray[$rt['cyid']]['id1'];
		}

		$tid1 = $SDB->get_value("SELECT tid1 FROM {$source_prefix}baktids WHERE tid2=".pwEscape($rt['tid'])." LIMIT 1");
		$tid = $DDB->get_value("SELECT tid FROM {$pw_prefix}argument WHERE tid=".pwEscape($tid1)." LIMIT 1");
		if (!$tid){
			$argumentArr[] = array(
				'tid'	=>	$rt['tid1'],
				'cyid'	=>	$rt['cyid'],
				'topped'	=>	$rt['topped'],
				'postdate'	=>	$rt['postdate'],
				'lastpost'	=>	$rt['lastpost'],
			);
		}
		$s_c++;
	}
	$SDB->free_result($query);

	if(!empty($argumentArr)){
		$DDB->update("INSERT INTO {$pw_prefix}argument (tid,cyid,topped,postdate,lastpost)VALUES".pwSqlMulti($argumentArr));
		unset($argumentArr);
	}

	$maxid = $SDB->get_value("SELECT COUNT(*) AS count FROM {$source_prefix}argument LIMIT 1");
	echo "最大id：".$maxid."<br>最后id：".$end;

	if($lastid == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '35'){
    //相册
	$lastid = '0';
	$albumArray = array();
	file_exists(S_P."temp_32.php") && include_once(S_P."temp_32.php");
	file_exists(S_P."temp_35.php") && include_once(S_P."temp_35.php");

	$query	= $SDB->query("SELECT * FROM {$source_prefix}cnalbum
						   WHERE aid > $start AND aid < $end
						   ORDER BY aid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['aid'];

		//群组相册和个人相册不同处理方法
		if($rt['atype'] == '0'){ //个人
			$buquery = $SDB->query("SELECT uid1,newname FROM {$source_prefix}bakuids WHERE uid2=".pwEscape($rt['ownerid']));
			while ($burt = $SDB->fetch_array($buquery)){
				$rt['ownerid'] = $burt['uid1'];
				if($burt['newname'] != ""){
					$rt['owner'] = $burt['newname'];
				}
			}
		}elseif($rt['atype'] == '1'){//群组
			if(!empty($colonysArray) && !empty($colonysArray[$rt['ownerid']]['id1']) && empty($colonysArray[$rt['ownerid']]['cname'])){
				$temp_ownerid = $rt['ownerid'];
				$rt['ownerid'] = $colonysArray[$temp_ownerid]['id1'];
				$rt['owner'] = $colonysArray[$temp_ownerid]['cname'];
			}
		}
		$aid = $DDB->get_value("SELECT aid FROM {$pw_prefix}cnalbum WHERE aname=".pwEscape($rt['aname'])." AND ownerid=".pwEscape($rt['ownerid'])." AND atype=".pwEscape($rt['atype']));
		if (!$aid){
			$DDB->update("INSERT INTO {$pw_prefix}cnalbum (aname,aintro,atype,private,albumpwd,ownerid,owner,photonum,lastphoto,lasttime,lastpid,crtime)VALUES('$rt[aname]','$rt[aintro]','$rt[atype]','$rt[private]','$rt[albumpwd]','$rt[ownerid]','$rt[owner]','$rt[photonum]','$rt[lastphoto]','$rt[lasttime]','$rt[lastpid]','$rt[crtime]')");
			$aid = $DDB->insert_id();

			$albumArray[$rt['aid']] = array(
				'aid1'	=>	$aid,
				'aid2'	=>	$rt['aid']);
		}
		$s_c++;
	}
	$SDB->free_result($query);

	if (!empty($albumArray)){
		writeover(S_P.'temp_35.php',"\r\n\$albumArray=".pw_var_export($albumArray).";\n", TRUE);
	}

	$maxid = $SDB->get_value("SELECT MAX(aid) FROM {$source_prefix}cnalbum LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '36'){
	//照片数据
	$lastid = '0';
	$newname = '';
	$photoArray = array();
	file_exists(S_P."temp_35.php") && include_once(S_P."temp_35.php");
	file_exists(S_P."temp_36.php") && include_once(S_P."temp_36.php");

	$query = $SDB->query("SELECT * FROM {$source_prefix}cnphoto
						  WHERE pid > $start AND pid < $end
						  ORDER BY pid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['pid'];

		//相册ID
		if(!empty($albumArray) && !empty($albumArray[$rt['aid']]['aid1'])){
			$rt['aid'] = $albumArray[$rt['aid']]['aid1'];
		}

		//用户ID
		$newname = $SDB->get_value("SELECT newname FROM {$source_prefix}bakuids WHERE username=".pwEscape($rt['uploader']));
		$rt['uploader'] = !empty($newname) ? $burt['newname'] : $rt['uploader'];

		$pid = $DDB->get_value("SELECT pid FROM {$pw_prefix}cnphoto WHERE path=".pwEscape($rt['path']));
		if (!$pid){
			$DDB->update("insert into {$pw_prefix}cnphoto (aid,pintro,path,uploader,uptime,hits,ifthumb,c_num)VALUES('$rt[aid]','$rt[pintro]','$rt[path]','$rt[uploader]','$rt[uptime]','$rt[hits]','$rt[ifthumb]','$rt[c_num]')");
			$pid = $DDB->insert_id();

			$photoArray[$rt['pid']] = array(
				'pid1'	=>	$pid,
				'pid2'	=>	$rt['pid']);
		}
		$s_c++;
	}
	$SDB->free_result($query);
	if (!empty($photoArray)){
		writeover(S_P.'temp_36.php',"\r\n\$photoArray=".pw_var_export($photoArray).";\n", TRUE);
	}

	$maxid = $SDB->get_value("SELECT MAX(pid) FROM {$source_prefix}cnphoto LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '37'){
	//记录
	$lastid = '0';
	$writeArray = array();
	file_exists(S_P."temp_37.php") && include_once(S_P."temp_37.php");

	$query = $SDB->query("SELECT * FROM {$source_prefix}owritedata
						  WHERE id > $start AND id < $end
						  ORDER BY id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];

		//用户ID
		$buquery = $SDB->query("SELECT uid1,uid2
								FROM {$source_prefix}bakuids
								WHERE uid2=".pwEscape($rt['uid'])." OR uid2=".pwEscape($rt['touid'])." LIMIT 1");
		while ($burt = $SDB->fetch_array($buquery)){
			if($rt['uid'] != 0 && $rt['uid'] == $burt['uid2']){
				$rt['uid'] = $burt['uid1'];
			}
		    if($rt['touid'] != 0 && $rt['touid'] == $burt['uid2']){
				$rt['touid'] = $burt['uid1'];
			}
		}

		$id = $DDB->get_value("SELECT id FROM {$pw_prefix}owritedata
							   WHERE uid=".pwEscape($rt['uid'])." AND touid=".pwEscape($rt['touid'])." AND postdate=".pwEscape($rt['postdate'])."
							   LIMIT 1");
		if (!$id){
		  $DDB->update("INSERT INTO {$pw_prefix}owritedata (uid,touid,postdate,isshare,source,content,c_num)VALUES('$rt[uid]','$rt[touid]','$rt[postdate]','$rt[isshare]','$rt[source]','$rt[content]','$rt[c_num]')");
		  $id = $DDB->insert_id();

		  $writeArray[$rt['id']] = array(
			'id1'	=>	$id,
			'id2'	=>	$rt['id']);
		}
		$s_c++;
	}
	$SDB->free_result($query);
	if (!empty($writeArray)){
		writeover(S_P.'temp_37.php',"\r\n\$writeArray=".pw_var_export($writeArray).";\n", TRUE);
	}

	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}owritedata LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '38'){
    //日志分类
	$lastid = '0';
	$diarytypeArray = array();
	file_exists(S_P."temp_38.php") && include_once(S_P."temp_38.php");

	$query	= $SDB->query("SELECT dt.*,bu.uid1
						   FROM {$source_prefix}diarytype dt
						   LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=dt.uid
						   WHERE dt.dtid > $start AND dt.dtid < $end
						   ORDER BY dt.dtid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['dtid'];

		$dtid = $DDB->get_value("SELECT dtid FROM {$pw_prefix}diarytype
								 WHERE uid=".pwEscape($rt['uid1'])." AND name=".pwEscape($rt['name'])."
								 LIMIT 1");
		if (!$dtid){
			$DDB->update("INSERT INTO {$pw_prefix}diarytype (uid,name,num)VALUES('$rt[uid1]','$rt[name]','$rt[num]')");
			$dtid = $DDB->insert_id();

			$diarytypeArray[$rt['dtid']] = array(
				'dtid1'	=>	$dtid,
				'dtid2'	=>	$rt['dtid']);
		}
		$s_c++;
	}
	$SDB->free_result($query);
	if (!empty($diarytypeArray)){
		writeover(S_P.'temp_38.php',"\r\n\$diarytypeArray=".pw_var_export($diarytypeArray).";\n", TRUE);
	}

	$maxid = $SDB->get_value("SELECT MAX(dtid) FROM {$source_prefix}diarytype LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif($step == '39'){
	//日志
	$lastid = '0';
	$diaryArray = array();
	file_exists(S_P."temp_38.php") && include_once(S_P."temp_38.php");
	file_exists(S_P."temp_39.php") && include_once(S_P."temp_39.php");

	$query	= $SDB->query("SELECT d.*,bu.uid1,bu.newname
						   FROM {$source_prefix}diary d
						   LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=d.uid
						   WHERE d.did > $start AND d.did < $end
						   ORDER BY d.did ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['did'];
		$rt['newname'] != "" && $rt['username'] = $rt['newname'];

		if(!empty($diarytypeArray) && !empty($diarytypeArray[$rt['dtid']]['dtid1'])){
			$rt['dtid'] = $diarytypeArray[$rt['dtid']]['dtid1'];
		}

		$did = $DDB->get_value("SELECT dtid FROM {$pw_prefix}diary
								WHERE uid=".pwEscape($rt['uid1'])." and subject=".pwEscape($rt['subject'])." AND postdate=".pwEscape($rt['postdate'])."
								LIMIT 1");
		if (!$did){
			$DDB->update("INSERT INTO {$pw_prefix}diary (uid,dtid,aid,username,privacy,subject,content,ifcopy,copyurl,ifconvert,ifwordsfb,ifupload,r_num,c_num,postdate)VALUES('$rt[uid1]','$rt[dtid]','$rt[aid]','$rt[username]','$rt[privacy]','$rt[subject]','$rt[content]','$rt[ifcopy]','$rt[copyurl]','$rt[ifconvert]','$rt[ifwordsfb]','$rt[ifupload]','$rt[r_num]','$rt[c_num]','$rt[postdate]')");
			$did = $DDB->insert_id();

			$diaryArray[$rt['did']] = array(
				'did1'	=>	$did,
				'did2'	=>	$rt['did']);
		}
		$s_c++;
	}
	$SDB->free_result($query);

	if (!empty($diaryArray)){
		writeover(S_P.'temp_39.php',"\r\n\$diaryArray=".pw_var_export($diaryArray).";\n", TRUE);
	}

	$maxid = $SDB->get_value("SELECT MAX(did) FROM {$source_prefix}diary LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif($step == '40'){
	//留言板
	$lastid = '0';
	$oboardArr = array();

	$query = $SDB->query("SELECT * FROM {$source_prefix}oboard
						  WHERE id > $start AND id < $end
						  ORDER BY id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];

		$buquery = $SDB->query("SELECT uid1,uid2 FROM {$source_prefix}bakuids
								WHERE uid2=".pwEscape($rt['uid'])." or uid2=".pwEscape($rt['touid']));
		while ($burt = $SDB->fetch_array($buquery)){
			if($rt['uid'] != '0' && $rt['uid'] == $burt['uid2']){
				$rt['uid'] = $burt['uid1'];
				$rt['username'] = $burt['newname'] ? $burt['newname'] : $rt['username'];
			}
		    if($rt['touid'] != '0' && $rt['touid'] == $burt['uid2']){
				$rt['touid'] = $burt['uid1'];
			}
		}
		$id = $DDB->get_value("SELECT id FROM {$pw_prefix}oboard
							   WHERE uid=".pwEscape($rt['uid'])." AND touid=".pwEscape($rt['touid'])." AND title=".pwEscape($rt['title'])." AND postdate=".pwEscape($rt['postdate'])."
							   LIMIT 1");
		if (!$id){
			$oboardArr[] = array(
				'uid'		=>	$rt['uid'],
				'username'	=>	$rt['username'],
				'touid'		=>	$rt['touid'],
				'title'		=>	$rt['title'],
				'postdate'	=>	$rt['postdate'],
				'c_num'		=>	$rt['c_num'],
				'ifwordsfb'	=>	$rt['ifwordsfb'],
			);
		}
		$s_c++;
	}
	$SDB->free_result($query);

	if(!empty($oboardArr)){
		$DDB->update("INSERT INTO {$pw_prefix}oboard(uid,username,touid,title,postdate,c_num,ifwordsfb)VALUES".pwSqlMulti($oboardArr));
		unset($oboardArr);
	}

	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}oboard LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif($step == '41'){
	//新鲜事
	$lastid = '0';
	$extra = '';
	$bakweibos = $newforum = $weiboconent = array();
	file_exists(S_P."temp_39.php") && include_once(S_P."temp_39.php");

	if(!$start){
		$SDB->query("DROP TABLE IF EXISTS {$source_prefix}bakweibos;");
		$SDB->query("CREATE TABLE {$source_prefix}bakweibos (
					 id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					 mid1 INT(10) NOT NULL,
					 mid2 INT(10) NOT NULL,
					 PRIMARY KEY (id),
					 KEY `mid2` (`mid2`)
					 )ENGINE = MYISAM ;");
	}
	$query = $SDB->query("SELECT wc.*,bu.uid1,bu.newname,bt.tid1,bt.fid1
						  FROM {$source_prefix}weibo_content wc
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=wc.uid
						  LEFT JOIN {$source_prefix}baktids bt ON bt.tid2=wc.objectid
						  WHERE wc.mid > $start AND wc.mid < $end
						  ORDER BY wc.mid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		$lastid = $rt['mid'];

		$mid = $DDB->get_value("SELECT mid FROM {$pw_prefix}weibo_content WHERE uid=".pwEscape($rt['uid'])." AND content=".pwEscape($rt['content']." AND postdate=".pwEscape($rt['postdate'])));
		if(!$mid){
			$extra = unserialize($rt['extra']);
			$newforum = $SDB->get_value("SELECT fid1,fname1 FROM {$source_prefix}bakfids
										 WHERE fid2=".pwEscape($extra['fid'])." AND fname2=".pwEscape($extra['fname'])."
										 LIMIT 1");
			$rt['extra'] = array(
				'title'	=>	$extra['title'],
				'fid'	=>	$newforum['fid1'],
				'fname'	=>	$newforum['fname1'],
			);
			$rt['extra'] = serialize($rt['extra']);
			switch($rt['type']){
				case '10':
					$rt['objectid'] = $rt['tid1'];
					break;
				case '20':
					$rt['objectid'] = $diaryArray[$rt['objectid']]['did1'];
					break;
				case '30':
					$rt['objectid'] = '0';
					break;
				case '3':
					$rt['objectid'] = '0';
					break;
				default :
					$rt['objectid'] = '0';
					break;
			}

			$weiboconent = array(
				'uid'			=>	$rt['uid1'],
				'content'		=>	$rt['content'],
				'extra'			=>	$rt['extra'],
				'contenttype'	=>	$rt['contenttype'],
				'type'			=>	$rt['type'],
				'objectid'		=>	$rt['objectid'],
				'replies'		=>	$rt['replies'],
				'transmit'		=>	$rt['transmit'],
				'postdate'		=>	$rt['postdate'],
			);
			$DDB->update("INSERT INTO {$pw_prefix}weibo_content SET ".pwSqlSingle($weiboconent));
			$mid = $DDB->insert_id();

			$bakweibos[] = array(
				'mid1'	=>	$mid,
				'mid2'	=>	$rt['mid'],
			);
		}
		$s_c++;
	}
	$SDB->free_result($query);
	if(!empty($bakweibos)){
		$SDB->update("INSERT INTO {$source_prefix}bakweibos(mid1,mid2)VALUES".pwSqlMulti($bakweibos));
		unset($bakweibos);
	}

	$maxid = $SDB->get_value("SELECT MAX(mid) FROM {$source_prefix}weibo_content LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif($step == '42'){
	//附件
	$lastid = '0';
	$bakaids = array();
	if(!$start){
		$SDB->query("DROP TABLE IF EXISTS {$source_prefix}bakaids;");
		$SDB->query("CREATE TABLE {$source_prefix}bakaids (
					 id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					 aid1 INT(10) NOT NULL,
					 aid2 INT(10) NOT NULL,
					 tid1 INT(10) NOT NULL,
					 tid2 INT(10) NOT NULL,
					 PRIMARY KEY (id),
					 KEY `aid2` (`aid2`),
					 KEY `tid2` (`tid2`)
					 )ENGINE = MYISAM ;");
	}
	$query	 = $SDB->query("SELECT at.*,bu.uid1,bf.fid1,bt.tid1
							FROM {$source_prefix}attachs at
							LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2 =at.uid
							LEFT JOIN {$source_prefix}bakfids bf ON bf.fid2=at.fid
							LEFT JOIN {$source_prefix}baktids bt ON bt.tid2=at.tid
							WHERE at.aid > $start AND at.aid < $end
							ORDER BY at.aid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['aid'];

		if($rt['pid']){
			$rt['pid'] = $SDB->get_value("SELECT pid1 FROM {$source_prefix}bakpids WHERE pid2=".pwEscape($rt['pid']));
		}
		$aid = $DDB->get_value("SELECT aid FROM {$pw_prefix}attachs WHERE attachurl=".pwEscape($rt['attachurl']));
		$rt['descrip'] = !empty($rt['descrip']) ? addslashes($rt['descrip']) : '';
		if (!$aid){
			$DDB->update("INSERT INTO {$pw_prefix}attachs (fid,uid,tid,pid,did,name,type,size,attachurl,hits,needrvrc,special,ctype,uploadtime,descrip,ifthumb)VALUES('$rt[fid1]','$rt[uid1]','$rt[tid1]','$rt[pid]','$rt[did]','".addslashes($rt[name])."','$rt[type]','$rt[size]','$rt[attachurl]','$rt[hits]','$rt[needrvrc]','$rt[special]','$rt[ctype]','$rt[uploadtime]','$rt[descrip]','$rt[ifthumb]')");
			$aid = $DDB->insert_id();

			$bakaids[] = array(
				'aid1'	=>	$aid,
				'aid2'	=>	$rt['aid'],
				'tid1'	=>	$rt['tid1'],
				'tid2'	=>	$rt['tid'],
			);
		}
		$s_c++;
	}
	$SDB->free_result($query);

	if(!empty($bakaids)){
		$SDB->update("INSERT INTO {$source_prefix}bakaids (aid1,aid2,tid1,tid2)VALUES".pwSqlMulti($bakaids));
		unset($bakaids);
	}

	$maxid = $SDB->get_value("SELECT MAX(aid) FROM {$source_prefix}attachs LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif($step == '43'){
	//附件出售记录
	$lastid = '0';
	$attachbuyArr = array();

	$query	= $SDB->query("SELECT ab.*,ba.aid1,bu.uid1
						   FROM {$source_prefix}attachbuy ab
						   LEFT JOIN {$source_prefix}bakaids ba ON ba.aid2=ab.aid
						   LEFT JOIN {$source_prefix}bakuids bu ON ab.uid=bu.uid2
						   WHERE ab.aid > $start AND ab.aid < $end
						   ORDER BY ab.aid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['aid'];

		$aid = $DDB->get_value("SELECT aid FROM {$pw_prefix}attachbuy
								WHERE uid=".pwEscape($rt['uid1'])." AND ctype=".pwEscape($rt['ctype'])." AND cost=".pwEscape($rt['cost'])." LIMIT 1");
		if (!$aid && $rt['aid1']){
			$attachbuyArr[] = array(
				'aid'	=>	$rt['aid1'],
				'uid'	=>	$rt['uid1'],
				'ctype'	=>	$rt['ctype'],
				'cost'	=>	$rt['cost'],
			);
		}
		$s_c++;
	}
	$SDB->free_result($query);

	if(!empty($attachbuyArr)){
		$DDB->update("INSERT INTO {$pw_prefix}attachbuy(aid,uid,ctype,cost)VALUES".pwSqlMulti($attachbuyArr));
		unset($attachbuyArr);
	}

	$maxid = $SDB->get_value("SELECT MAX(aid) FROM {$source_prefix}attachbuy LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '44') {
	//积分日志
	$lastid = '0';
	$creditlogArr = array();

	$query	= $SDB->query("SELECT cl.*,bu.uid1,bu.newname
						   FROM {$source_prefix}creditlog cl
						   LEFT JOIN {$source_prefix}bakuids bu ON cl.uid=bu.uid2
						   WHERE cl.id > $start AND cl.id < $end
						   ORDER BY cl.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];
		$rt['newname'] != "" && $rt['username'] = $rt['newname'];

		$aid = $DDB->get_value("SELECT id FROM {$pw_prefix}creditlog
								WHERE uid=".pwEscape($rt['uid1'])." AND ctype=".pwEscape($rt['ctype'])." AND affect=".pwEscape($rt['affect'])." LIMIT 1");
		if (!$aid){
			$creditlogArr[] = array(
				'uid'		=>	$rt['uid1'],
				'username'	=>	$rt['username'],
				'ctype'		=>	$rt['ctype'],
				'affect'	=>	$rt['affect'],
				'adddate'	=>	$rt['adddate'],
				'logtype'	=>	$rt['logtype'],
				'ip'		=>	$rt['ip'],
				'descrip'	=>	$rt['descrip'],
			);
		}
		$s_c++;
	}
	$SDB->free_result($query);

	if(!empty($creditlogArr)){
		$DDB->update("INSERT INTO {$pw_prefix}creditlog(uid,username,ctype,affect,adddate,logtype,ip,descrip)VALUES".pwSqlMulti($creditlogArr));
		unset($creditlogArr);
	}

	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}creditlog LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '45') {
	//交易记录
	$lastid = '0';
	$tradeorderArr = array();

	$query	= $SDB->query("SELECT tr.*,t.tid1
						   FROM {$source_prefix}tradeorder tr
						   LEFT JOIN {$source_prefix}baktids t ON tr.tid=t.tid2
						   WHERE tr.oid > $start AND tr.oid < $end
						   ORDER BY tr.oid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['oid'];

		$request = $SDB->query("SELECT * FROM {$source_prefix}bakuids WHERE uid2=".pwEscape($rt['buyer'])." OR uid2=".pwEscape($rt['seller']));
		while ($us = $SDB->fetch_array($request)){
			if($rt['buyer'] == $us['uid2']){
				$rt['buyer'] = $us['uid1'];
			}
			if($rt['seller'] == $us['uid2']){
				$rt['seller'] = $us['uid1'];
			}
		}
		$SDB->free_result($request);

		$oid = $DDB->get_value("SELECT oid FROM {$pw_prefix}tradeorder WHERE order_no=".pwEscape($rt['order_no'])." LIMIT 1");
		if (!$oid){
			$tradeorderArr[] = array(
				'order_no'		=>	$rt['order_no'],
				'tid'			=>	$rt['tid1'],
				'subject'		=>	$rt['subject'],
				'buyer'			=>	$rt['buyer'],
				'seller'		=>	$rt['seller'],
				'price'			=>	$rt['price'],
				'quantity'		=>	$rt['quantity'],
				'transportfee'	=>	$rt['transportfee'],
				'transport'		=>	$rt['transport'],
				'buydate'		=>	$rt['buydate'],
				'ifpay'			=>	$rt['ifpay'],
				'address'		=>	$rt['address'],
				'consignee'		=>	$rt['consignee'],
				'tel'			=>	$rt['tel'],
				'zip'			=>	$rt['zip'],
				'descrip'		=>	$rt['descrip'],
				'payment'		=>	$rt['payment'],
				'tradeinfo'		=>	$rt['tradeinfo'],
				'tradedate'		=>	$rt['tradedate'],
			);
		}
		$s_c++;
	}
	$SDB->free_result($query);

	if(!empty($tradeorderArr)){
		$DDB->update("INSERT INTO {$pw_prefix}tradeorder (order_no,tid,subject,buyer,seller,price,quantity,transportfee,transport,buydate,ifpay,address,consignee,tel,zip,descrip,payment,tradeinfo,tradedate)VALUES".pwSqlMulti($tradeorderArr));
		unset($tradeorderArr);
	}

	$maxid = $SDB->get_value("SELECT MAX(oid) FROM {$source_prefix}tradeorder LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '46') {
	//道具交易
	$lastid = '0';
	$usertoolArr = array();

	file_exists(S_P."temp_4.php") && include_once(S_P."temp_4.php");

	$query	= $SDB->query("SELECT *
						   FROM {$source_prefix}usertool
						   LIMIT $start, $percount");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid++;

		$uid1 = $SDB->get_value("SELECT uid1 FROM {$source_prefix}bakuids WHERE uid2=".pwEscape($rt['uid'])." LIMIT 1");
		if(!empty($toolsArray) && !empty($toolsArray[$rt['toolid']]['id1'])){
			$rt['toolid'] = $toolsArray[$rt['toolid']]['id1'];
		}

		$uid = $DDB->get_value("SELECT uid FROM {$pw_prefix}usertool
								WHERE uid=".pwEscape($uid1)." AND toolid=".pwEscape($rt['toolid'])." AND nums=".pwEscape($rt['nums'])."
								LIMIT 1");
		if (!$uid){
			$usertoolArr[] = array(
				'uid'			=>	$uid1,
				'toolid'		=>	$rt[toolid],
				'nums'			=>	$rt[nums],
				'sellnums'		=>	$rt[sellnums],
				'sellprice'		=>	$rt[sellprice],
				'sellstatus'	=>	$rt[sellstatus],
			);
		}
		$s_c++;
	}
	$SDB->free_result($query);

	if(!empty($usertoolArr)){
		$DDB->update("INSERT INTO {$pw_prefix}usertool (uid,toolid,nums,sellnums,sellprice,sellstatus)VALUES".pwSqlMulti($usertoolArr));
		unset($usertoolArr);
	}

	if($lastid == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '47'){
	//道具操作记录
	$lastid = '0';
	$toollogArr = array();

	$query	= $SDB->query("SELECT * FROM {$source_prefix}toollog ORDER BY id LIMIT $start, $percount");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];

		$request = $SDB->query("SELECT * FROM {$source_prefix}bakuids
								WHERE uid2=".pwEscape($rt['uid'])." OR uid2=".pwEscape($rt['touid']));
		while ($us = $SDB->fetch_array($request)){
			if($rt['uid'] == $us['uid2']){
				$rt['uid'] = $us['uid1'];
				if($us["newname"] != ""){
					$rt['username'] = $us["newname"];
				}
			}
			if($rt['touid'] == $us['uid2']){
				$rt['touid'] = $us['uid1'];
			}
		}

		$uid = $DDB->get_value("SELECT id FROM {$pw_prefix}toollog
								WHERE type=".pwEscape($rt['type'])." AND nums=".pwEscape($rt['nums'])." AND uid=".pwEscape($rt['uid'])." AND touid=".pwEscape($rt['touid'])."
								LIMIT 1");
		if (!$uid){
			$toollogArr[] = array(
				'type'		=>	$rt['type'],
				'nums'		=>	$rt['nums'],
				'money'		=>	$rt['money'],
				'descrip'	=>	$rt['descrip'],
				'uid'		=>	$rt['uid'],
				'username'	=>	$rt['username'],
				'ip'		=>	$rt['ip'],
				'time'		=>	$rt['time'],
				'filename'	=>	$rt['filename'],
				'touid'		=>	$rt['touid'],
			);
		}
		$s_c++;
	}
	$SDB->free_result($query);

	if(!empty($toollogArr)){
		$DDB->update("INSERT INTO {$pw_prefix}toollog (type,nums,money,descrip,uid,username,ip,time,filename,touid)VALUES".pwSqlMulti($toollogArr));
		unset($toollogArr);
	}

	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}toollog LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '48'){
	//文章栏目
	$cms_columns = $cms_bakfids = array();

	if(!$start){
		$SDB->query("DROP TABLE IF EXISTS {$source_prefix}cms_bakfids;");
		$SDB->query("CREATE TABLE {$source_prefix}cms_bakfids (
					 id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					 column_id1 INT(10) NOT NULL,
					 column_id2 INT(10) NOT NULL,
					 PRIMARY KEY (id),
					 KEY `column_id2` (`column_id2`)
					 )ENGINE = MYISAM ;");
	}

	$query = $SDB->query("SELECT * FROM {$source_prefix}cms_column
						  ORDER BY column_id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);

		$column_id = $DDB->get_value("SELECT column_id FROM {$pw_prefix}cms_column WHERE name=".pwEscape($rt['name'])." LIMIT 1");
		if(!$column_id){
			$cms_columns = array(
				'parent_id'		=>	$rt['parent_id'],
				'name'			=>	$rt['name'],
				'allowoffer'	=>	$rt['allowoffer'],
				'seotitle'		=>	$rt['seotitle'],
				'seodesc'		=>	$rt['seodesc'],
				'seokeywords'	=>	$rt['seokeywords'],
			);
			if(!empty($cms_columns)){
				$DDB->update("INSERT INTO {$pw_prefix}cms_column SET `order`=".pwEscape($rt['order']).",".pwSqlSingle($cms_columns));
				$column_id = $DDB->insert_id();

				$cms_bakfids[] = array(
					'column_id1'	=>	$column_id,
					'column_id2'	=>	$rt['column_id'],
				);
			}
		}else{
			$cms_bakfids[] = array(
				'column_id1'	=>	$column_id,
				'column_id2'	=>	$rt['column_id'],
			);
		}
		$s_c++;
	}
	$SDB->free_result($query);

	if(!empty($cms_bakfids)){
		$SDB->update("INSERT INTO {$source_prefix}cms_bakfids(column_id1,column_id2)VALUES".pwSqlMulti($cms_bakfids));
		unset($cms_bakfids, $cms_columns);
	}
	report_log();
	newURL($step);
}elseif ($step == '49'){
	//文章帖子
	$lastid = '0';
	$cms_article = array();
	if(!$start){
		$SDB->query("DROP TABLE IF EXISTS {$source_prefix}cms_baktids;");
		$SDB->query("CREATE TABLE {$source_prefix}cms_baktids (
					 id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					 article_id1 INT(10) NOT NULL,
					 article_id2 INT(10) NOT NULL,
					 PRIMARY KEY (id),
					 KEY `article_id2` (`article_id2`)
					 )ENGINE = MYISAM ;");
	}

	$query = $SDB->query("SELECT ca.*,cc.content,cc.relatearticle,ce.hits,cb.column_id1
						  FROM {$source_prefix}cms_article ca
						  LEFT JOIN {$source_prefix}cms_articlecontent cc ON ca.article_id=cc.article_id
						  LEFT JOIN {$source_prefix}cms_articleextend ce ON ca.article_id=ce.article_id
						  LEFT JOIN {$source_prefix}cms_bakfids cb ON ca.column_id=cb.column_id2
						  WHERE ca.article_id > $start AND ca.article_id < $end
						  ORDER BY ca.article_id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['article_id'];

		$article_id = $DDB->get_value("SELECT article_id FROM {$pw_prefix}cms_article WHERE subject=".pwEscape($rt['subject'])." AND descrip=".pwEscape($rt['descrip'])." AND postdate=".pwEscape($rt['postdate']));
		if(!$article_id){
			$newuser = $SDB->get_one("SELECT uid1,newname FROM {$source_prefix}bakuids
									  WHERE uid2=".pwEscape($rt['userid'])." AND username=".pwEscape($rt['username']));
			$newauthor = $SDB->get_value("SELECT newname FROM {$source_prefix}bakuids
										  WHERE username=".pwEscape($rt['author']));
			$cms_article = array(
				'subject'	=>	$rt['subject'],
				'descrip'	=>	$rt['descrip'],
				'author'	=>	$newauthor ? $newauthor : $rt['author'],
				'username'	=>	$newuser['newname'] ? $newuser['newname'] : $rt['username'],
				'userid'	=>	$newuser['uid1'] ? $newuser['uid1'] : $rt['userid'],
				'jumpurl'	=>	$rt['jumpurl'],
				'frominfo'	=>	$rt['frominfo'],
				'fromurl'	=>	$rt['fromurl'],
				'column_id'	=>	$rt['column_id1'],
				'ifcheck'	=>	$rt['ifcheck'],
				'postdate'	=>	$rt['postdate'],
				'modifydate'=>	$rt['modifydate'],
				'ifattach'	=>	$rt['ifattach'],
				'sourcetype'=>	$rt['sourcetype'],
				'sourceid'	=>	$rt['sourceid'],
			);
			if(!empty($cms_article)){
				$DDB->update("INSERT INTO {$pw_prefix}cms_article SET ".pwSqlSingle($cms_article));
				$article_id = $DDB->insert_id();

				$DDB->update("INSERT INTO {$pw_prefix}cms_articlecontent(article_id,content,relatearticle)VALUES(".pwEscape($article_id).",".pwEscape($rt['content']).",".pwEscape($rt['relatearticle']).")");
				$DDB->update("INSERT INTO {$pw_prefix}cms_articleextend(article_id,hits)VALUES(".pwEscape($article_id).",".pwEscape($rt['hits']).")");
			}
			$cms_baktids[] = array(
				'article_id1'	=>	$article_id,
				'article_id2'	=>	$rt['article_id'],
			);
			unset($cms_article);
		}
		$s_c++;
	}
	$SDB->free_result($query);

	if(!empty($cms_baktids)){
		$SDB->update("INSERT INTO {$source_prefix}cms_baktids(article_id1,article_id2)VALUES".pwSqlMulti($cms_baktids));
		unset($cms_baktids);
	}

	$maxid = $SDB->get_value("SELECT MAX(article_id) FROM {$source_prefix}cms_article LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '50'){
	//文章附件
	$lastid = '0';
	$cms_attach = $cms_bakaids = array();
	if(!$start){
		$SDB->query("DROP TABLE IF EXISTS {$source_prefix}cms_bakaids;");
		$SDB->query("CREATE TABLE {$source_prefix}cms_bakaids (
					 id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					 attach_id1 INT(10) NOT NULL,
					 attach_id2 INT(10) NOT NULL,
					 article_id1 INT(10) NOT NULL,
					 article_id2 INT(10) NOT NULL,
					 PRIMARY KEY (id),
					 KEY `attach_id2` (`attach_id2`),
					 KEY `article_id2` (`article_id2`)
					 )ENGINE = MYISAM ;");
	}
	$query = $SDB->query("SELECT ca.*,cb.article_id1,article_id2
						  FROM {$source_prefix}cms_attach ca
						  LEFT JOIN {$source_prefix}cms_baktids cb ON ca.article_id=cb.article_id2
						  WHERE ca.attach_id > $start AND ca.attach_id < $end
						  ORDER BY ca.attach_id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['attach_id'];

		$attach_id = $DDB->get_value("SELECT attach_id FROM {$pw_prefix}cms_attach
									  WHERE name=".pwEscape($rt['name'])." AND uploadtime=".pwEscape($rt['uploadtime'])." AND attachurl=".pwEscape($rt['attachurl'])."
									  LIMIT 1");
		if(!$attach_id){
			$cms_attach = array(
				'name'		=>	$rt['name'],
				'descrip'	=>	$rt['descrip'],
				'article_id'=>	$rt['article_id1'],
				'type'		=>	$rt['type'],
				'size'		=>	$rt['size'],
				'uploadtime'=>	$rt['uploadtime'],
				'attachurl'	=>	$rt['attachurl'],
				'ifthumb'	=>	$rt['ifthumb'],
			);
			if(!empty($cms_attach)){
				$DDB->update("INSERT INTO {$pw_prefix}cms_attach SET ".pwSqlSingle($cms_attach));
				$attach_id = $DDB->insert_id();

				$cms_bakaids[] = array(
					'attach_id1'	=>	$attach_id,
					'attach_id2'	=>	$rt['attach_id'],
					'article_id1'	=>	$rt['article_id1'],
					'article_id2'	=>	$rt['article_id2'],
				);
			}
		}
		$s_c++;
	}
	$SDB->free_result($query);

	if(!empty($cms_bakaids)){
		$SDB->update("INSERT INTO {$source_prefix}cms_bakaids(attach_id1,attach_id2,article_id1,article_id2)VALUES".pwSqlMulti($cms_bakaids));
		unset($cms_bakaids);
	}

	$maxid = $SDB->get_value("SELECT MAX(attach_id) FROM {$source_prefix}cms_attach LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '51'){
	//主题附件内容更新
	$lastid = '0';

	$query = $SDB->query("SELECT bt.id,bt.tid2,bt.tid1,ba.aid1,ba.aid2,t.content
						  FROM {$source_prefix}baktids bt
						  LEFT JOIN {$source_prefix}bakaids ba ON bt.tid2=ba.tid2
						  LEFT JOIN {$source_prefix}tmsgs t ON bt.tid2=t.tid
						  WHERE bt.id > $start AND bt.id < $end AND ba.aid1 != '' AND ba.aid2 != ''
						  ORDER BY bt.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];

		$content = $rt['content'];
		$attachment1 = $attachment2 = array();
		if(str_replace("[attachment=", "", $content) != $content){
			preg_match_all("/\[attachment=(\d+)\]/eis", $content, $tmp_attachment);
			$attachment2 = $tmp_attachment['1'];
			foreach ($attachment2 AS $k => $v){
				$attachment2[$k] = "[attachment=".$v."]";
				$v1 = $SDB->get_value("SELECT aid1 FROM {$source_prefix}bakaids WHERE aid2=".pwEscape($v)." LIMIT 1");
				$attachment1[$k] = "[attachment=".$v1."]";
			}
			$content = str_replace($attachment2, $attachment1, $content);
			if($content != $rt['content'] && $content){
				$DDB->update("UPDATE {$pw_prefix}tmsgs SET content=".pwEscape($content)." WHERE tid=".pwEscape($rt['tid1']));
			}
			unset($attachment1, $attachment2);
		}
		$s_c++;
	}
	$SDB->free_result($query);

	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}baktids LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '52'){
	//回复附件内容更新
	$lastid = '0';

	$query = $SDB->query("SELECT bp.id,bp.pid1,p.content
						  FROM {$source_prefix}bakpids bp
						  LEFT JOIN {$source_prefix}posts p ON bp.pid2=p.pid
						  WHERE bp.id > $start AND bp.id < $end
						  ORDER BY bp.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];

		$content = $rt['content'];
		$attachment1 = $attachment2 = array();
		if(str_replace("[attachment=", "", $content) != $content){
			preg_match_all("/\[attachment=(\d+)\]/eis", $content, $tmp_attachment);
			$attachment2 = $tmp_attachment['1'];
			foreach ($attachment2 AS $k => $v){
				$attachment2[$k] = "[attachment=".$v."]";
				$v1 = $SDB->get_value("SELECT aid1 FROM {$source_prefix}bakaids WHERE aid2=".pwEscape($v)." LIMIT 1");
				$attachment1[$k] = "[attachment=".$v1."]";
			}
			$content = str_replace($attachment2, $attachment1, $content);
			if($content != $rt['content'] && $content){
				$DDB->update("UPDATE {$pw_prefix}posts SET content=".pwEscape($content)." WHERE pid=".pwEscape($rt['pid1']));
			}
			unset($attachment1, $attachment2);
		}
		$s_c++;
	}
	$SDB->free_result($query);

	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}bakpids LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '53'){
	//文章附件内容更新
	$lastid = '0';
	$query = $SDB->query("SELECT bt.id,bt.article_id2,bt.article_id1,ba.attach_id1,ba.attach_id2,t.content
						  FROM {$source_prefix}cms_baktids bt
						  LEFT JOIN {$source_prefix}cms_bakaids ba ON bt.article_id2=ba.article_id2
						  LEFT JOIN {$source_prefix}cms_articlecontent t ON bt.article_id2=t.article_id
						  WHERE bt.id > $start AND bt.id < $end AND ba.attach_id1 != '' AND ba.attach_id2 != ''
						  ORDER BY bt.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];

		$content = $rt['content'];
		$attachment1 = $attachment2 = array();
		if(str_replace("[attachment=", "", $content) != $content){
			preg_match_all("/\[attachment=(\d+)\]/eis", $content, $tmp_attachment);
			$attachment2 = $tmp_attachment['1'];
			foreach ($attachment2 AS $k => $v){
				$attachment2[$k] = "[attachment=".$v."]";
				$v1 = $SDB->get_value("SELECT attach_id1 FROM {$source_prefix}cms_bakaids WHERE attach_id2=".pwEscape($v)." LIMIT 1");
				$attachment1[$k] = "[attachment=".$v1."]";
			}
			$content = str_replace($attachment2, $attachment1, $content);
			if($content != $rt['content'] && $content){
				$DDB->update("UPDATE {$pw_prefix}cms_articlecontent SET content=".pwEscape($content)." WHERE article_id=".pwEscape($rt['article_id1']));
			}
			unset($attachment1, $attachment2);
		}
		$s_c++;
	}
	$SDB->free_result($query);

	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}cms_baktids LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif($step == '54'){
	//收藏和分享
	$lastid = '0';
	$shareArray = $collections = array();
	file_exists(S_P."temp_32.php") && require_once(S_P."temp_32.php");
	file_exists(S_P."temp_35.php") && require_once(S_P."temp_35.php");
	file_exists(S_P."temp_36.php") && require_once(S_P."temp_36.php");
	file_exists(S_P."temp_39.php") && require_once(S_P."temp_39.php");

	$query	= $SDB->query("SELECT c.*,bu.uid1,bu.newname,bt.tid1
						   FROM {$source_prefix}collection c
						   LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=c.uid
						   LEFT JOIN {$source_prefix}baktids bt ON bt.tid2=c.typeid
						   WHERE c.id > $start AND c.id < $end
						   ORDER BY c.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		$lastid = $rt['id'];
		$rt['newname'] != "" && $rt['username'] = $rt['newname'];

		$id = $DDB->get_value("SELECT id FROM {$pw_prefix}collection
							   WHERE type=".pwEscape($rt['type'])." AND username=".pwEscape($rt['newname'])." AND postdate=".pwEscape($rt['postdate']));
		if(!$id){
			switch($rt['type']){
				case 'postfavor'://主题
					$rt['typeid'] = $rt['tid1'];
					$arr = unserialize($rt['content']);
					list($tidlink, ) = explode("=", $arr['link']);
					$newuser = $SDB->get_one("SELECT uid1,newname FROM {$source_prefix}bakuids
											  WHERE uid2=".pwEscape($arr['uid'])." AND username=".pwEscape($arr['username'])." LIMIT 1");
					$rt['content'] = array(
						'uid'		=>	$newuser['uid1'],
						'lastpost'	=>	$arr['lastpost'],
						'link'		=>	$tidlink."=".$rt['tid1'],
						'postfavor'	=>	$arr['postfavor'],
						'username'	=>	$newuser['newname'],
					);
					$rt['content'] = serialize($rt['content']);
					break;
				case 'tucool'://图库
					$rt['typeid'] = $SDB->get_value("SELECT aid1 FROM {$source_prefix}bakaids WHERE aid2=".pwEscape($rt['typeid']));
					$arr = unserialize($rt['content']);
					$arr['tucool'] = array(
						'image'		=>	$arr['tucool']['image'],
						'name'		=>	$arr['tucool']['name'],
						'tid'		=>	$rt['tid1'],
						'subject'	=>	$arr['tucool']['subject'],
					);
					$newuser = $SDB->get_one("SELECT uid1,newname FROM {$source_prefix}bakuids
											  WHERE uid2=".pwEscape($arr['uid'])." AND username=".pwEscape($arr['username'])." LIMIT 1");
					$rt['content'] = array(
						'type'		=>	$arr['type'],
						'uid'		=>	$newuser['uid1'],
						'tucool'	=>	$arr['tucool'],
						'link'		=>	$arr['link'],
						'username'	=>	$newuser['newname'],
					);
					$rt['content'] = serialize($rt['content']);
					break;
				case 'weibo'://新鲜事
					$rt['typeid'] = $SDB->get_value("SELECT mid1 FROM {$source_prefix}bakweibos WHERE mid2=".pwEscape($rt['typeid']));
					$arr = unserialize($rt['content']);
					$extra = unserialize($arr['extra']);
					$newfid = $SDB->get_value("SELECT fid1 FROM {$source_prefix}bakfids WHERE fid2=".pwEscape($extra['fid']));
					$newuser = $SDB->get_one("SELECT uid1,newname FROM {$source_prefix}bakuids
											  WHERE uid2=".pwEscape($arr['uid'])." AND username=".pwEscape($arr['username'])." LIMIT 1");
					$extra = array(
						'title'	=>	$extra['title'],
						'fid'	=>	$newfid,
						'fname'	=>	$extra['fname'],
					);
					$arr['extra'] = serialize($extra);
					$rt['content'] = array(
						'type'		=>	$arr['type'],
						'uid'		=>	$newuser['uid1'],
						'content'	=>	$arr['content'],
						'objectid'	=>	$rt['tid1'],
						'extra'		=>	$arr['extra'],
						'authorid'	=>	$newuser['uid1'],
						'username'	=>	$newuser['newname'],
					);
					$rt['content'] = serialize($rt['content']);
					break;
				case 'cms'://文章
					$rt['typeid'] = $SDB->get_value("SELECT article_id1 FROM {$source_prefix}cms_baktids
													 WHERE article_id2=".pwEscape($rt['typeid']));
					$arr = unserialize($rt['content']);
					$link_pre = substr($arr['link'], 0, strpos($arr['link'], 'id')+3);
					$article_id = $SDB->get_value("SELECT article_id1 FROM {$source_prefix}cms_baktids
												   WHERE article_id2=".pwEscape($rt['typeid']));
					$newuser = $SDB->get_one("SELECT uid1,newname FROM {$source_prefix}bakuids
											  WHERE uid2=".pwEscape($arr['uid'])." AND username=".pwEscape($arr['username'])." LIMIT 1");
					$arr['link'] = $link_pre.$article_id;
					$content = array(
						'uid'		=>	$newuser['uid1'],
						'link'		=>	$arr['link'],
						'cms'		=>	$arr['cms'],
						'username'	=>	$newuser['newname'],
					);
					$rt['content'] = serialize($content);
					break;
				case 'diary'://日志
					$rt['typeid'] = $diaryArray[$rt['typeid']]['did1'];
					$arr = unserialize($rt['content']);
					$link_pre = substr($arr['link'], 0, strpos($arr['link'], 'uid')+4);
					$newuser = $SDB->get_one("SELECT uid1,newname FROM {$source_prefix}bakuids
											  WHERE uid2=".pwEscape($arr['uid'])." AND username=".pwEscape($arr['username'])." LIMIT 1");
					$arr['link'] = $link_pre.$newuser['uid1']."&did=".$diaryArray[$rt['typeid']]['did1'];
					$rt['content'] = array(
						'uid'		=>	$newuser['uid1'],
						'link'		=>	$arr['link'],
						'diary'		=>	array('subject'	=>	$arr['diary']['subject']),
						'username'	=>	$newuser['newname'],
					);
					$rt['content'] = serialize($rt['content']);
					break;
				case 'photo'://照片
					$rt['typeid'] = $photoArray[$rt['typeid']]['pid1'];
					$arr = unserialize($rt['content']);
					$link_pre = substr($arr['link'], 0, strpos($arr['link'], 'uid')+4);
					$newuser = $SDB->get_one("SELECT uid1,newname FROM {$source_prefix}bakuids
											  WHERE uid2=".pwEscape($arr['uid'])." AND username=".pwEscape($arr['username'])." LIMIT 1");
					$newaid = $SDB->get_value("SELECT aid FROM {$source_prefix}cnphoto WHERE pid=".pwEscape($rt['typeid']));
					$arr['link'] = $link_pre.$newuser['uid1']."&aid=".$newaid."&pid=".$rt['typeid'];
					$rt['content'] = array(
						'type'		=>	$arr['type'],
						'uid'		=>	$newuser['uid1'],
						'link'		=>	$arr['link'],
						'photo'		=>	$arr['photo'],
						'username'	=>	$newuser['newname'],
					);
					$rt['content'] = serialize($rt['content']);
					break;
				case 'group'://群组
					$rt['typeid'] = $colonysArray[$rt['typeid']]['id1'];
					$arr = unserialize($rt['content']);
					$link_pre = substr($arr['link'], 0, strpos($arr['link'], 'cyid')+5);
					$newuser = $SDB->get_one("SELECT uid1,newname FROM {$source_prefix}bakuids
											  WHERE uid2=".pwEscape($arr['uid'])." AND username=".pwEscape($arr['username'])." LIMIT 1");
					$newcyid = $rt['typeid'];
					$newcname = $colonysArray[$rt['typeid']]['cname'];
					$newcnimg = $colonysArray[$rt['typeid']]['cnimg'];
					$arr['link'] = $link_pre.$newcyid;
					$rt['content'] = array(
						'username'	=>	$newuser['newname'],
						'link'		=>	$arr['link'],
						'group'		=>	array('name' =>	$newcname, 'image' => $newcnimg),
						'uid'		=>	$newuser['uid1'],
					);
					$rt['content'] = serialize($rt['content']);
					break;
				case 'active'://活动
					break;
				case 'web'://网页
					break;
				case 'multimedia'://多媒体
					break;
				default :
					break;
			}

			$collections[] = array(
				'type'		=>	$rt['type'],
				'typeid'	=>	$rt['typeid'],
				'uid'		=>	$rt['uid1'],
				'username'	=>	$rt['newname'],
				'postdate'	=>	$rt['postdate'],
				'content'	=>	$rt['content'],
				'ifhidden'	=>	$rt['ifhidden'],
				'c_num'		=>	$rt['c_num'],
				'ctid'		=>	$rt['ctid'],
			);
		}
		$s_c++;
	}
	$SDB->free_result($query);

	if(!empty($collections)){
		$DDB->update("INSERT INTO {$pw_prefix}collection(type,typeid,uid,username,postdate,content,ifhidden,c_num,ctid)
					  VALUES".pwSqlMulti($collections));
		unset($collections);
	}

	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}collection LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '55'){
	//统计重复邮箱数据
	$lastid = '0';
	$repeatEmailUser = array();
	file_exists(S_P.'temp_repeatEmail.php') && require_once(S_P.'temp_repeatEmail.php');

	$query = $SDB->query("SELECT m.email,bu.uid1,bu.uid2,bu.username,bu.newname
						  FROM {$source_prefix}members m
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=m.uid
						  WHERE m.uid > $start AND m.uid < $end AND m.email != ''
						  ORDER BY m.uid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['uid'];

		$query2 = $DDB->query("SELECT username,email FROM {$pw_prefix}members
							   WHERE uid !=".pwEscape($rt['uid1'])."
							   AND username !=".pwEscape($rt['newname'])."
							   AND email=".pwEscape($rt['email']));
		while ($rt2 = $DDB->fetch_array($query2)){
			$repeatEmailUser[$rt2['email']][] = "[".$rt['newname']."] => [".$rt2['username']."]";
		}
		$DDB->free_result($query2);
		$s_c++;
	}
	$SDB->free_result($query);

	if (!empty($repeatEmailUser)){
		writeover(S_P.'temp_repeatEmail.php',"\r\n\$repeatEmailUser=".pw_var_export($repeatEmailUser).";\n", TRUE);
		unset($repeatEmailUser);
	}

	$maxid = $SDB->get_value("SELECT MAX(uid) FROM {$source_prefix}members LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '56'){
	//广告
	$lastid = '0';
	$advertArray = array();

	$query = $SDB->query("SELECT a.*,bu.uid1,bu.username,bu.newname
						  FROM {$source_prefix}advert a
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.uid
						  WHERE a.id > $start AND a.id < $end AND a.uid != '0'
						  ORDER BY a.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		$lastid = $rt['id'];

		$id = $DDB->get_value("SELECT id FROM {$pw_prefix}advert
							   WHERE ckey=".pwEscape($rt['ckey'])." AND stime=".pwEscape($rt['stime'])."
							   AND etime=".pwEscape($rt['etime'])." AND descrip=".pwEscape($rt['descrip'])."
							   LIMIT 1");
		if(!$id){
			$advertArray[$rt['id']] = array(
				'type'		=>	$rt['type'],
				'uid'		=>	$rt['uid1'],
				'ckey'		=>	$rt['ckey'],
				'stime'		=>	$rt['stime'],
				'etime'		=>	$rt['etime'],
				'ifshow'	=>	$rt['ifshow'],
				'orderby'	=>	$rt['orderby'],
				'descrip'	=>	$rt['descrip'],
				'config'	=>	$rt['config']
			);
		}
		$s_c++;
	}
	$SDB->free_result($query);

	if (!empty($advertArray)){
		$DDB->update("INSERT INTO {$pw_prefix}advert(type,uid,ckey,stime,etime,ifshow,orderby,descrip,config)VALUES".pwSqlMulti($advertArray));
		unset($advertArray);
	}

	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}advert LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '57'){
	//马甲
	$lastid = '0';
	$userbindArray = array();

	$query = $SDB->query("SELECT ub.id,ub.password,bu.uid1,bu.newname
						  FROM {$source_prefix}userbinding ub
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=ub.uid
						  WHERE ub.id > $start AND ub.id < $end AND ub.uid != '0'
						  ORDER BY ub.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];

		$userbindArray[$rt['id']][] = array(
			'uid'		=>	$rt['uid1'],
			'password'	=>	$rt['password'],
		);
		$s_c++;
	}
	$SDB->free_result($query);

	if (!empty($userbindArray)){
		foreach ($userbindArray AS $k => $v){
			$maxid = $DDB->get_value("SELECT MAX(id) FROM {$pw_prefix}userbinding LIMIT 1");
			$maxid++;
			foreach ($v AS $kk => $vv){
				$v[$kk]['id'] = $maxid;
			}
			$DDB->update("INSERT INTO {$pw_prefix}userbinding(uid,password,id)VALUES".pwSqlMulti($v));
		}
		unset($userbindArray);
	}

	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}userbinding LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '58'){
	//表情
	$lastid = '0';
	$smileArray = array();
	file_exists(S_P.'tmp_58.php') && require_once(S_P.'tmp_58.php');

	$query = $SDB->query("SELECT * FROM {$source_prefix}smiles
						  WHERE id > $start AND id < $end
						  ORDER BY id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];

		$id = $DDB->get_value("SELECT id FROM {$pw_prefix}smiles
							   WHERE path=".pwEscape($rt['path'])." AND type=".pwEscape($rt['type']));
		if(!$id){
			$smileArray = array(
				'path'		=>	$rt['path'],
				'name'		=>	$rt['name'],
				'descipt'	=>	$rt['descipt'],
				'vieworder'	=>	$rt['vieworder'],
				'type'		=>	$rt['type'],
			);
		}
		if(!empty($smileArray)){
			$DDB->update("INSERT INTO {$pw_prefix}smiles SET".pwSqlSingle($smileArray));
			$id = $DDB->insert_id();

			$baksmiles[$rt['id']] = array(
				'id1'	=>	"[s:".$id."]",
				'id2'	=>	"[s:".$rt['id']."]",
			);
		}
		$s_c++;
	}
	$SDB->free_result($query);

	if(!empty($baksmiles)){
		writeover(S_P.'tmp_58.php', "\r\n\$baksmiles=".pw_var_export($baksmiles).";\n", TRUE);
		unset($baksmiles);
	}

	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}smiles LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '59'){
	//帖子表情内容更新
	$lastid = '0';
	file_exists(S_P.'tmp_58.php') && require_once(S_P.'tmp_58.php');

	$query = $SDB->query("SELECT bt.id,bt.tid2,bt.tid1,t.content
						  FROM {$source_prefix}baktids bt
						  LEFT JOIN {$source_prefix}tmsgs t ON bt.tid2=t.tid
						  WHERE bt.id > $start AND bt.id < $end
						  ORDER BY bt.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		$smile1 = $smile2 = array();
		Add_S($rt);
		$lastid = $rt['id'];
		$content = $rt['content'];

		if(str_replace("[s:", "", $content) != $content){
			preg_match_all("/\[s:(\d+)\]/eis", $content, $tmp_smile);
			$smile2 = $tmp_smile['0'];
			foreach ($tmp_smile['1'] AS $k => $v){
				$smile1[] = $baksmiles[$v]['id1'];
			}

			$content = str_replace($smile2, $smile1, $content);
			if($content != $rt['content'] && $content){
				$DDB->update("UPDATE {$pw_prefix}tmsgs SET content=".pwEscape($content)." WHERE tid=".pwEscape($rt['tid1']));
			}
			unset($smile1, $smile2);
		}
		$s_c++;
	}
	$SDB->free_result($query);

	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}baktids LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;

	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}else{
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

	$query = $SDB->query("SELECT g.*,g.* FROM {$source_prefix}common_usergroup g LEFT JOIN {$source_prefix}common_usergroup_field f " .
			"ON g.groupid=f.groupid WHERE g.type = 'member' OR g.type = 'special'");
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
	writeover(S_P.'tmp_grelation.php', "\r\n\$_grelation = ".pw_var_export($grelation).";", true);
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
	global $SDB,$source_prefix;
	$num = $SDB->get_value("SELECT COUNT(*) AS num FROM {$source_prefix}home_comment WHERE idtype='picid' AND id=".pwEscape($pid));
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
	global $SDB;

	$thread_info = $SDB->get_one("SELECT lastpost,subject FROM {$source_prefix}forum_thread WHERE tid=".pwEscape($tid));
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
	global $SDB,$source_prefix;
	$count = $SDB->get_value("SELECT COUNT(*) FROM {$source_prefix}home_blog WHERE classid=".pwEscape($classid));
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