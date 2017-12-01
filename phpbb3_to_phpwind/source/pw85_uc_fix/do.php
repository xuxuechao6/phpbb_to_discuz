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

if ($step == '1'){
	//用户数据合并和统计
	if(!$start){
		$sdb_maxuid = $SDB->get_value("SELECT MAX(uid) FROM {$source_prefix}members LIMIT 1");
		$ddb_maxuid = $DDB->get_value("SELECT MAX(uid) FROM {$pw_prefix}members LIMIT 1");
		
		//主站最大UID必须大于等于从站最大UID
		if($sdb_maxuid >= $ddb_maxuid){
			$sdb_maxuid++;
			$DDB->query("ALTER TABLE {$pw_prefix}members AUTO_INCREMENT=".$sdb_maxuid);
		}
		
		$SDB->query("DROP TABLE IF EXISTS {$source_prefix}bakuids;");
		$SDB->query("CREATE TABLE {$source_prefix}bakuids (
					 id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					 uid2 INT(10) NOT NULL,
					 uid1 INT(10) NOT NULL,
					 icon VARCHAR(255) NOT NULL DEFAULT '',
					 username VARCHAR(50) NOT NULL,
					 newname VARCHAR(50) NOT NULL, 
					 PRIMARY KEY (id))
					 ENGINE = MYISAM;");
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
	
	$query = $SDB->query("SELECT m.*,md.*,
						  mi.adsips,mi.credit,mi.deposit,mi.startdate,mi.ddeposit,mi.dstartdate,mi.regreason,mi.readmsg,mi.delmsg,
						  mi.tooltime,mi.replyinfo,mi.lasttime,mi.digtid,mi.customdata,mi.tradeinfo 
						  FROM {$source_prefix}members m 
						  LEFT JOIN {$source_prefix}memberdata md USING(uid) 
						  LEFT JOIN {$source_prefix}memberinfo mi USING(uid) 
						  WHERE m.uid > $start AND m.username != '' 
						  ORDER BY m.uid ASC
						  LIMIT $percount");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['uid'];
		$username = $rt['username'];
		$lastvisit = date("Y-m-d H:i:s", $rt['lastvisit']);
		$newname = '';
		
		$uid = $DDB->get_value("SELECT uid FROM {$pw_prefix}members WHERE username=".pwEscape($username));
		if ($uid){
			$newname = $repeatname.$username;
			$newuid = $DDB->get_value("SELECT uid FROM {$pw_prefix}members WHERE username=".pwEscape($newname));
			if(!$newuid){
				//保存会员表
				$DDB->update("INSERT INTO {$pw_prefix}members (username,password,safecv,email,groupid,memberid,groups,icon,gender,regdate,signature,introduce,oicq,aliww,icq,msn,yahoo,site,location,honor,bday,lastaddrst,yz,timedf,style,datefm,t_num,p_num,attach,hack,newpm,banpm,msggroups,medals,userstatus,shortcut,salt)VALUES('$newname','$rt[password]','$rt[safecv]','$rt[email]','$rt[groupid]','$rt[memberid]','$rt[groups]','$rt[icon]','$rt[gender]','$rt[regdate]','$rt[signature]','$rt[introduce]','$rt[oicq]','$rt[aliww]','$rt[icq]','$rt[msn]','$rt[yahoo]','$rt[site]','$rt[location]','$rt[honor]','$rt[bday]','$rt[lastaddrst]','$rt[yz]','$rt[timedf]','$rt[style]','$rt[datefm]','$rt[t_num]','$rt[p_num]','$rt[attach]','$rt[hack]','$rt[newpm]','$rt[banpm]','$rt[msggroups]','$rt[medals]','$rt[userstatus]','$rt[shortcut]','$rt[salt]')");
				$uid = $DDB->insert_id();
				
				$DDB->update("INSERT INTO {$pw_prefix}memberdata (uid,postnum,digests,rvrc,money,credit,currency,lastvisit,thisvisit,lastpost,onlinetime,monoltime,todaypost,monthpost,uploadtime,uploadnum,onlineip,starttime,pwdctime,postcheck)values ('$uid','$rt[postnum]','$rt[digests]','$rt[rvrc]','$rt[money]','$rt[credit]','$rt[currency]','$rt[lastvisit]','$rt[thisvisit]','$rt[lastpost]','$rt[onlinetime]','$rt[monoltime]','$rt[todaypost]','$rt[monthpost]','$rt[uploadtime]','$rt[uploadnum]','$rt[onlineip]','$rt[starttime]','$rt[pwdctime]','$rt[postcheck]')");
				
				$DDB->update("INSERT INTO {$pw_prefix}memberinfo (uid,adsips,credit,deposit,startdate,ddeposit,dstartdate,regreason,readmsg,delmsg,tooltime,replyinfo,lasttime,digtid,customdata,tradeinfo)values ('$uid','$rt[adsips]','$rt[credit]','$rt[deposit]','$rt[startdate]','$rt[ddeposit]','$rt[dstartdate]','$rt[regreason]','$rt[readmsg]','$rt[delmsg]','$rt[tooltime]','$rt[replyinfo]','$rt[lasttime]','$rt[digtid]','$rt[customdata]','$rt[tradeinfo]')");
				
				$memberArray[] = array(
					'uid1' 		=>	$uid,
					'uid2' 		=>	$rt['uid'],
					'icon' 		=>	$rt['icon'],
					'username' 	=>	$username,
					'newname' 	=>	$newname,
					'lastvisit'	=>	$lastvisit,
				);
			}
		}else{
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
				'lastvisit'	=>	$lastvisit,
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
				$repeatUser[] = "[".$value['username']."] ==> [".$value['newname']."] <".$value['lastvisit'].">";
			}
		}
		
		if(!empty($repeatUser)){
			writeover(S_P.'tmp_repeatUser.php',"\r\n\$repeatUser=".pw_var_export($repeatUser).";\n", true);
		}
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
}elseif ($step == '2'){
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
	report_log();
	newURL($step);
}elseif ($step == '3'){
	//活动附件
	$lastid = '0';
	
	$query = $SDB->query("SELECT aa.aid,aa.uid,bu.username,bu.uid1,bu.newname 
						  FROM {$source_prefix}actattachs aa 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=aa.uid
						  WHERE aa.aid > $start AND aa.aid < $end AND bu.uid1 != '0'
						  ORDER BY aa.aid ASC");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['aid'];
		
		if(!empty($rt['uid1']) && !empty($rt['uid'])){
			$SDB->update("UPDATE {$source_prefix}actattachs SET uid=".pwEscape($rt['uid1'])." WHERE uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(aid) FROM {$source_prefix}actattachs LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '4'){
	//群组活动
	$lastid = '0';
	
	$query = $SDB->query("SELECT a.id,a.uid,bu.username,bu.uid1,bu.newname 
						  FROM {$source_prefix}active a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.uid
						  WHERE a.id > $start AND a.id < $end AND bu.uid1 != '0'
						  ORDER BY a.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];
		
		if(!empty($rt['uid1']) && !empty($rt['uid'])){
			$SDB->update("UPDATE {$source_prefix}active SET uid=".pwEscape($rt['uid1'])." WHERE uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}active LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '5'){
	//活动贴信息
	$lastid = '0';

	$query = $SDB->query("SELECT a.tid,a.admin,bu.uid1,bu.newname 
						  FROM {$source_prefix}activity a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.admin
						  WHERE a.tid > $start AND a.tid < $end AND bu.uid1 != '0'
						  ORDER BY a.tid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['tid'];
		
		if(!empty($rt['uid1']) && !empty($rt['admin'])){
			$SDB->update("UPDATE {$source_prefix}activity SET admin=".pwEscape($rt['uid1'])." WHERE admin=".pwEscape($rt['admin']));
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
}elseif ($step == '6'){
	//活动帖报名信息表
	$lastid = '0';
	
	$query = $SDB->query("SELECT a.actuid,a.uid,a.username,bu.uid1,bu.newname 
						  FROM {$source_prefix}activitymembers a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.uid
						  WHERE a.actuid > $start AND a.actuid < $end AND bu.uid1 != '0'
						  ORDER BY a.actuid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['actuid'];
		
		if(!empty($rt['uid1']) && !empty($rt['uid'])){
			$SDB->update("UPDATE {$source_prefix}activitymembers SET uid=".pwEscape($rt['uid1']).",username=".pwEscape($rt['newname'])." WHERE uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(actuid) FROM {$source_prefix}activitymembers LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '7'){
	//活动支付记录
	$lastid = '0';
	
	$query = $SDB->query("SELECT a.actpid,a.uid,a.username,a.author,a.authorid,bu.uid1,bu.newname 
						  FROM {$source_prefix}activitypaylog a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.uid
						  WHERE a.actpid > $start AND a.actpid < $end AND bu.uid1 != '0'
						  ORDER BY a.actpid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['actpid'];
		
		if(!empty($rt['uid1']) && !empty($rt['uid'])){
			$SDB->update("UPDATE {$source_prefix}activitypaylog 
						  SET uid=".pwEscape($rt['uid1']).",username=".pwEscape($rt['newname'])." 
						  WHERE uid=".pwEscape($rt['uid']));
		}
		
		$author = $SDB->get_one("SELECT uid1,newname FROM {$source_prefix}bakuids 
								 WHERE uid2=".pwEscape($rt['authorid'])." AND username=".pwEscape($rt['author'])." LIMIT 1");
		if(!empty($author)){
			$SDB->update("UPDATE {$source_prefix}activitypaylog 
						  SET author=".$author['newname'].",authorid=".$author['author']." 
						  WHERE uid=".pwEscape($rt['uid1']));
		}
		unset($author);
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(actpid) FROM {$source_prefix}activitypaylog LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '8'){
	//活动贴报名成员
	$lastid = '0';
	
	$query = $SDB->query("SELECT a.id,a.winduid,bu.uid1,bu.newname 
						  FROM {$source_prefix}actmember a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.winduid
						  WHERE a.id > $start AND a.id < $end AND bu.uid1 != '0'
						  ORDER BY a.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];
		
		if(!empty($rt['uid1']) && !empty($rt['winduid'])){
			$SDB->update("UPDATE {$source_prefix}actmember 
						  SET winduid=".pwEscape($rt['uid1'])." 
						  WHERE winduid=".pwEscape($rt['winduid']));
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
}elseif ($step == '9'){
	//群组活动报名
	$lastid = '0';
	
	$query = $SDB->query("SELECT a.id,a.uid,bu.uid1,bu.newname 
						  FROM {$source_prefix}actmembers a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.uid
						  WHERE a.id > $start AND a.id < $end AND bu.uid1 != '0'
						  ORDER BY a.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];
		
		if(!empty($rt['uid1']) && !empty($rt['uid'])){
			$SDB->update("UPDATE {$source_prefix}actmembers 
						  SET uid=".pwEscape($rt['uid1'])." 
						  WHERE uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}actmembers LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '10'){
    //论坛管理组信息
	$lastid = '0';
	$query = $SDB->query("SELECT uid,username
						  FROM {$source_prefix}administrators
						  LIMIT $start, $percount");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid++;
		
		$newinfo = $SDB->get_one("SELECT uid1,newname FROM {$source_prefix}bakuids 
								  WHERE uid2=".pwEscape($rt['uid']));
		if(!empty($newinfo)){
			$SDB->update("UPDATE {$source_prefix}administrators 
						  SET uid=".pwEscape($newinfo['uid1']).",username=".pwEscape($newinfo['newname'])." 
						  WHERE uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	if($lastid == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '11'){
	//前台管理安全日志记录
	$lastid = '0';
	
	$query = $SDB->query("SELECT a.id,a.username1,a.username2,bu.uid1,bu.newname 
						  FROM {$source_prefix}adminlog a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.username=a.username2
						  WHERE a.id > $start AND a.id < $end AND bu.uid1 != '0'
						  ORDER BY a.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];
		
		if(!empty($rt['uid1']) && !empty($rt['username2'])){
			$SDB->update("UPDATE {$source_prefix}adminlog 
						  SET username2=".pwEscape($rt['newname'])." 
						  WHERE id=".pwEscape($rt['id'])." AND username2=".pwEscape($rt['username2']));
		}
		if(!empty($rt['username1'])){
			$newname = $SDB->get_value("SELECT newname FROM {$source_prefix}bakuids WHERE username=".pwEscape($rt['username1']));
			$newname && $SDB->update("UPDATE {$source_prefix}adminlog SET username1=".pwEscape($newname)." 
									  WHERE id=".pwEscape($rt['id'])." AND username1=".pwEscape($rt['username1']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}adminlog LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '12'){
	//系统广告信息
	$lastid = '0';
	
	$query = $SDB->query("SELECT a.id,a.uid,bu.uid1,bu.newname 
						  FROM {$source_prefix}advert a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.uid
						  WHERE a.id > $start AND a.id < $end AND a.uid != '0' AND bu.uid1 != '0'
						  ORDER BY a.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];
		
		if(!empty($rt['uid1']) && !empty($rt['uid'])){
			$SDB->update("UPDATE {$source_prefix}advert 
						  SET uid=".pwEscape($rt['uid1'])." 
						  WHERE uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}advert LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '13'){
	//门户权限表
	$lastid = '0';
	
	$query = $SDB->query("SELECT uid,username
						  FROM {$source_prefix}area_level
						  LIMIT $start, $percount");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid++;
		
		$newInfo = $SDB->get_one("SELECT uid1,newname FROM {$source_prefix}bakuids WHERE uid2=".pwEscape($rt['uid'])." LIMIT 1");
		if(!empty($newInfo)){
			$SDB->update("UPDATE {$source_prefix}area_level 
						  SET uid=".pwEscape($newInfo['uid1'])." AND username=".pwEscape($newInfo['newname'])."
						  WHERE uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT COUNT(*) AS count FROM {$source_prefix}area_level LIMIT 1");
	echo "最大id：".$maxid."<br>最后id：".$end;
	
	if($lastid == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '14'){
	//附件出售信息
	$lastid = '0';
	
	$query = $SDB->query("SELECT uid
						  FROM {$source_prefix}attachbuy
						  LIMIT $start, $percount");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid++;
		
		$uid1 = $SDB->get_value("SELECT uid1 FROM {$source_prefix}bakuids WHERE uid2=".pwEscape($rt['uid']));
		if(!empty($uid1) && !empty($rt['uid'])){
			$SDB->update("UPDATE {$source_prefix}attachbuy 
						  SET uid=".pwEscape($uid1)."
						  WHERE uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT COUNT(*) AS count FROM {$source_prefix}attachbuy LIMIT 1");
	echo "最大id：".$maxid."<br>最后id：".$end;
	
	if($lastid == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '15'){
	//版块下载附件扣除积分记录
	$lastid = '0';
	
	$query = $SDB->query("SELECT a.uid,bu.uid1,bu.newname 
						  FROM {$source_prefix}attachdownload a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.uid
						  WHERE a.aid > $start AND a.aid < $end AND bu.uid1 != '0'
						  ORDER BY a.aid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['aid'];
		
		if(!empty($rt['uid1']) && !empty($rt['uid'])){
			$SDB->update("UPDATE {$source_prefix}attachdownload 
						  SET uid=".pwEscape($rt['uid1'])."
						  WHERE uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(aid) FROM {$source_prefix}attachdownload LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '16'){
	//附件主表信息
	$lastid = '0';
	
	$query = $SDB->query("SELECT a.aid,a.uid,bu.uid1,bu.newname 
						  FROM {$source_prefix}attachs a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.uid
						  WHERE a.aid > $start AND a.aid < $end AND bu.uid1 != '0'
						  ORDER BY a.aid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['aid'];
		
		if(!empty($rt['uid1']) && !empty($rt['uid'])){
			$SDB->update("UPDATE {$source_prefix}attachs 
						  SET uid=".pwEscape($rt['uid1'])."
						  WHERE uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(aid) FROM {$source_prefix}attachs LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '17'){
	//我关注的朋友数据
	$lastid = '0';
	
	$query = $SDB->query("SELECT a.uid,a.friendid,bu.uid1,bu.newname 
						  FROM {$source_prefix}attention a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.uid
						  WHERE a.uid > $start AND a.uid < $end AND bu.uid1 != '0'
						  ORDER BY a.uid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['uid'];
		
		if(!empty($rt['uid1']) && !empty($rt['uid'])){
			$SDB->update("UPDATE {$source_prefix}attention 
						  SET uid=".pwEscape($rt['uid1'])."
						  WHERE uid=".pwEscape($rt['uid'])." AND friendid=".pwEscape($rt['friendid']));
		}
		if(!empty($rt['friendid'])){
			$friendid = $SDB->get_value("SELECT uid1 FROM {$source_prefix}bakuids WHERE uid2=".pwEscape($rt['friendid']));
			$SDB->update("UPDATE {$source_prefix}attention 
						  SET friendid=".pwEscape($friendid)."
						  WHERE uid=".pwEscape($rt['uid1'])." AND friendid=".pwEscape($rt['friendid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(uid2) FROM {$source_prefix}bakuids LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '18'){
	//关注禁言用户列表
	$lastid = '0';
	
	$query = $SDB->query("SELECT uid,touid
						  FROM {$source_prefix}attention_blacklist
						  LIMIT 0, $percount ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid++;
		
		$uid = $SDB->get_value("SELECT uid1 FROM {$source_prefix}bakuids WHERE uid2=".pwEscape($rt['uid']));
		$touid = $SDB->get_value("SELECT uid1 FROM {$source_prefix}bakuids WHERE uid2=".pwEscape($rt['touid']));
		
		$uid = $uid ? $uid : $rt['uid'];
		$touid = $touid ? $touid : $rt['touid'];
		
		if(!empty($uid) && !empty($touid)){
			$SDB->update("UPDATE {$source_prefix}attention_blacklist 
						  SET uid=".pwEscape($uid).",touid=".pwEscape($touid)."
						  WHERE uid=".pwEscape($rt['uid'])." AND touid=".pwEscape($rt['touid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT COUNT(*) FROM {$source_prefix}attention_blacklist LIMIT 1");
	echo "最大id：".$maxid."<br>最后id：".$end;
	
	if($end < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '19'){
	//禁止数据表
	$lastid = '0';
	
	$query = $SDB->query("SELECT a.id,a.uid,a.username,a.admin,bu.uid1,bu.newname 
						  FROM {$source_prefix}ban a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.uid
						  WHERE a.id > $start AND a.id < $end AND bu.uid1 != '0'
						  ORDER BY a.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];
		
		if(!empty($rt['uid1']) && !empty($rt['uid'])){
			$SDB->update("UPDATE {$source_prefix}ban 
						  SET uid=".pwEscape($rt['uid1']).",username=".pwEscape($rt['newname'])."
						  WHERE id=".pwEscape($rt['id'])." AND uid=".pwEscape($rt['uid']));
		}
		if(!empty($rt['admin'])){
			$admin = $SDB->get_value("SELECT newname FROM {$source_prefix}bakuids WHERE username=".pwEscape($rt['admin']));
			$admin = $admin ? $admin : $rt['admin'];
			$SDB->update("UPDATE {$source_prefix}ban 
						  SET admin=".pwEscape($admin)."
						  WHERE id=".pwEscape($rt['id'])." AND uid=".pwEscape($rt['uid1']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}ban LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '20'){
	//禁言用户列表
	$lastid = '0';
	
	$query = $SDB->query("SELECT a.id,a.uid,a.admin,bu.uid1,bu.newname 
						  FROM {$source_prefix}banuser a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.uid
						  WHERE a.id > $start AND a.id < $end AND bu.uid1 != '0'
						  ORDER BY a.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];
		
		if(!empty($rt['uid1']) && !empty($rt['uid'])){
			$SDB->update("UPDATE {$source_prefix}banuser 
						  SET uid=".pwEscape($rt['uid1'])."
						  WHERE id=".pwEscape($rt['id'])." AND uid=".pwEscape($rt['uid']));
		}
		if(!empty($rt['admin'])){
			$admin = $SDB->get_value("SELECT newname FROM {$source_prefix}bakuids WHERE username=".pwEscape($rt['admin']));
			$admin = $admin ? $admin : $rt['admin'];
			$SDB->update("UPDATE {$source_prefix}banuser 
						  SET admin=".pwEscape($admin)."
						  WHERE id=".pwEscape($rt['id'])." AND uid=".pwEscape($rt['uid1']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}banuser LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '21'){
	//论坛基本信息
	$lastid = '0';
	
	$newname = $SDB->get_value("SELECT bu.newname 
								FROM {$source_prefix}bbsinfo a 
								LEFT JOIN {$source_prefix}bakuids bu ON bu.username=a.newmember
								WHERE bu.uid1 != '0' 
								LIMIT 1");
	$SDB->update("UPDATE {$source_prefix}bbsinfo SET newmember=".pwEscape($newname));
	
	report_log();
	newURL($step);
}elseif ($step == '22'){
	//广告出租信息
	$lastid = '0';
	
	$query = $SDB->query("SELECT a.id,a.uid,bu.uid1,bu.newname 
						  FROM {$source_prefix}buyadvert a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.uid
						  WHERE a.id > $start AND a.id < $end AND bu.uid1 != '0'
						  ORDER BY a.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];
		
		if(!empty($rt['uid1']) && !empty($rt['uid'])){
			$SDB->update("UPDATE {$source_prefix}buyadvert 
						  SET uid=".pwEscape($rt['uid1'])."
						  WHERE id=".pwEscape($rt['id'])." AND uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}buyadvert LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '23'){
    //后台定单管理相关数据
	$lastid = '0';
	
	$query = $SDB->query("SELECT a.id,a.uid,bu.uid1,bu.newname 
						  FROM {$source_prefix}clientorder a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.uid
						  WHERE a.id > $start AND a.id < $end AND bu.uid1 != '0'
						  ORDER BY a.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];
		
		if(!empty($rt['uid1']) && !empty($rt['uid'])){
			$SDB->update("UPDATE {$source_prefix}clientorder 
						  SET uid=".pwEscape($rt['uid1'])."
						  WHERE id=".pwEscape($rt['id'])." AND uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}clientorder LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '24'){
	//群组成员信息
	$lastid = '0';
	
	$query = $SDB->query("SELECT a.id,a.uid,a.username,bu.uid1,bu.newname 
						  FROM {$source_prefix}cmembers a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.uid
						  WHERE a.id > $start AND a.id < $end AND bu.uid1 != '0'
						  ORDER BY a.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];
		
		if(!empty($rt['uid1']) && !empty($rt['uid'])){
			$SDB->update("UPDATE {$source_prefix}cmembers 
						  SET uid=".pwEscape($rt['uid1']).",username=".pwEscape($rt['newname'])."
						  WHERE id=".pwEscape($rt['id'])." AND uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}cmembers LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '25'){
	//文章表
	$lastid = '0';
	
	$query = $SDB->query("SELECT a.article_id,a.author,a.username,a.userid,bu.uid1,bu.newname 
						  FROM {$source_prefix}cms_article a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.userid
						  WHERE a.article_id > $start AND a.article_id < $end AND bu.uid1 != '0'
						  ORDER BY a.article_id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['article_id'];
		
		if(!empty($rt['uid1']) && !empty($rt['uid'])){
			$SDB->update("UPDATE {$source_prefix}cms_article 
						  SET username=".pwEscape($rt['newname']).",userid=".pwEscape($rt['uid1'])."
						  WHERE article_id=".pwEscape($rt['article_id'])." AND userid=".pwEscape($rt['userid']));
		}
		if(!empty($rt['author'])){
			$author = $SDB->get_value("SELECT newname FROM {$source_prefix}bakuids WHERE username=".pwEscape($rt['author']));
			$author = $author ? $author : $rt['author'];
			$SDB->update("UPDATE {$source_prefix}cms_article SET author=".pwEscape($author)." WHERE author=".pwEscape($rt['author']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(article_id) FROM {$source_prefix}cms_article LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '26'){
	//文章权限表
	$lastid = '0';
	
	$query = $SDB->query("SELECT a.purview_id,a.username,bu.uid1,bu.newname 
						  FROM {$source_prefix}cms_purview a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.username=a.username
						  WHERE a.purview_id > $start AND a.purview_id < $end AND bu.uid1 != '0'
						  ORDER BY a.purview_id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['purview_id'];
		
		if(!empty($rt['newname']) && !empty($rt['username'])){
			$SDB->update("UPDATE {$source_prefix}cms_purview 
						  SET username=".pwEscape($rt['newname'])."
						  WHERE purview_id=".pwEscape($rt['purview_id'])." AND username=".pwEscape($rt['username']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(purview_id) FROM {$source_prefix}cms_purview LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '27'){
	//群组相册
	$lastid = '0';
	
	$query = $SDB->query("SELECT a.aid,a.owner,a.ownerid,bu.uid1,bu.newname 
						  FROM {$source_prefix}cnalbum a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.ownerid
						  WHERE a.aid > $start AND a.aid < $end AND bu.uid1 != '0'
						  ORDER BY a.aid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['aid'];
		
		if(!empty($rt['newname']) && !empty($rt['uid1'])){
			$SDB->update("UPDATE {$source_prefix}cnalbum 
						  SET owner=".pwEscape($rt['newname'])." AND ownerid=".pwEscape($rt['uid1'])."
						  WHERE aid=".pwEscape($rt['aid'])." AND ownerid=".pwEscape($rt['ownerid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(aid) FROM {$source_prefix}cnalbum LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '28'){
	//群组相片
	$lastid = '0';
	
	$query = $SDB->query("SELECT a.pid,a.uploader,bu.uid1,bu.newname 
						  FROM {$source_prefix}cnphoto a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.username=a.uploader
						  WHERE a.pid > $start AND a.pid < $end AND bu.uid1 != '0'
						  ORDER BY a.pid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['pid'];
		
		if(!empty($rt['newname']) && !empty($rt['uid1'])){
			$SDB->update("UPDATE {$source_prefix}cnphoto 
						  SET uploader=".pwEscape($rt['newname'])."
						  WHERE pid=".pwEscape($rt['pid'])." AND uploader=".pwEscape($rt['uploader']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(pid) FROM {$source_prefix}cnphoto LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '29'){
	//收藏表
	$lastid = '0';
	$query = $SDB->query("SELECT a.id,a.uid,a.username,bu.uid1,bu.newname 
						  FROM {$source_prefix}collection a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.uid
						  WHERE a.id > $start AND a.id < $end AND bu.uid1 != '0'
						  ORDER BY a.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];
		
		if(!empty($rt['newname']) && !empty($rt['uid1'])){
			$SDB->update("UPDATE {$source_prefix}collection 
						  SET uid=".pwEscape($rt['uid1']).",username=".pwEscape($rt['newname'])."
						  WHERE id=".pwEscape($rt['id'])." AND uid=".pwEscape($rt['uid'])." AND username=".pwEscape($rt['username']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}collection LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '30'){
	//收藏分类表
	$lastid = '0';
	$query = $SDB->query("SELECT a.ctid,a.uid,bu.uid1,bu.newname 
						  FROM {$source_prefix}collectiontype a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.uid
						  WHERE a.ctid > $start AND a.ctid < $end AND bu.uid1 != '0'
						  ORDER BY a.ctid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['ctid'];
		
		if(!empty($rt['uid1'])){
			$SDB->update("UPDATE {$source_prefix}collectiontype 
						  SET uid=".pwEscape($rt['uid1'])."
						  WHERE ctid=".pwEscape($rt['ctid'])." AND uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(ctid) FROM {$source_prefix}collectiontype LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '31'){
	//群组信息
	$lastid = '0';
	$query = $SDB->query("SELECT a.id,a.admin,bu.uid1,bu.newname 
						  FROM {$source_prefix}colonys a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.username=a.admin
						  WHERE a.id > $start AND a.id < $end AND bu.uid1 != '0'
						  ORDER BY a.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];
		
		if(!empty($rt['newname'])){
			$SDB->update("UPDATE {$source_prefix}colonys 
						  SET admin=".pwEscape($rt['newname'])."
						  WHERE id=".pwEscape($rt['id'])." AND admin=".pwEscape($rt['admin']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}colonys LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '32'){
    //用户评论表
	$lastid = '0';
	$query = $SDB->query("SELECT a.id,a.uid,a.username,bu.uid1,bu.newname 
						  FROM {$source_prefix}comment a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.uid
						  WHERE a.id > $start AND a.id < $end AND bu.uid1 != '0'
						  ORDER BY a.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];
		
		if(!empty($rt['newname']) && !empty($rt['uid1'])){
			$SDB->update("UPDATE {$source_prefix}comment 
						  SET uid=".pwEscape($rt['uid1']).",username=".pwEscape($rt['newname'])."
						  WHERE id=".pwEscape($rt['id'])." AND uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}comment LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '33'){
	//积分日志表
	$lastid = '0';
	$query = $SDB->query("SELECT a.id,a.uid,a.username,bu.uid1,bu.newname 
						  FROM {$source_prefix}creditlog a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.uid
						  WHERE a.id > $start AND a.id < $end AND bu.uid1 != '0'
						  ORDER BY a.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];
		
		if(!empty($rt['newname']) && !empty($rt['uid1'])){
			$SDB->update("UPDATE {$source_prefix}creditlog 
						  SET uid=".pwEscape($rt['uid1']).",username=".pwEscape($rt['newname'])."
						  WHERE id=".pwEscape($rt['id'])." AND uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}creditlog LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '34'){
	//群组记录表
	$lastid = '0';
	$query = $SDB->query("SELECT a.id,a.uid,a.touid,bu.uid1,bu.newname 
						  FROM {$source_prefix}cwritedata a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.uid
						  WHERE a.id > $start AND a.id < $end AND bu.uid1 != '0'
						  ORDER BY a.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];
		
		if(!empty($rt['uid1'])){
			$SDB->update("UPDATE {$source_prefix}cwritedata 
						  SET uid=".pwEscape($rt['uid1'])."
						  WHERE id=".pwEscape($rt['id'])." AND uid=".pwEscape($rt['uid']));
		}
		if(!empty($rt['touid'])){
			$touid = $SDB->get_value("SELECT uid1 FROM {$source_prefix}bakuids WHERE uid2=".pwEscape($rt['touid']));
			$SDB->update("UPDATE {$source_prefix}cwritedata 
						  SET touid=".pwEscape($touid)." 
						  WHERE id=".pwEscape($rt['id'])." AND touid=".pwEscape($rt['touid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}cwritedata LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '35'){
    //辩论记录
	$lastid = '0';
	$query = $SDB->query("SELECT pid,authorid
						  FROM {$source_prefix}debatedata
						  LIMIT $start, $percount");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid++;
		
		$authorid = $SDB->get_value("SELECT uid1 FROM {$source_prefix}bakuids WHERE uid2=".pwEscape($rt['authorid']));
		if(!empty($authorid)){
			$SDB->update("UPDATE {$source_prefix}debatedata 
					  SET authorid=".pwEscape($authorid)."
					  WHERE authorid=".pwEscape($rt['authorid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	if($lastid == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '36'){
	//辩论帖子记录
	$lastid = '0';
	$query = $SDB->query("SELECT tid,authorid
						  FROM {$source_prefix}debates
						  LIMIT $start, $percount");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid++;
		
		$authorid = $SDB->get_value("SELECT uid1 FROM {$source_prefix}bakuids WHERE uid2=".pwEscape($rt['authorid']));
		if(!empty($authorid)){
			$SDB->update("UPDATE {$source_prefix}debates 
						  SET authorid=".pwEscape($authorid)."
						  WHERE authorid=".pwEscape($rt['authorid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	if($lastid == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '37'){
	//日志主表
	$lastid = '0';
	$query = $SDB->query("SELECT did,uid,username
						  FROM {$source_prefix}diary
						  LIMIT $start, $percount ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid++;
		
		$newInfo = $SDB->get_one("SELECT uid1,newname FROM {$source_prefix}bakuids WHERE uid2=".pwEscape($rt['uid']));
		if(!empty($newInfo)){
			$SDB->update("UPDATE {$source_prefix}diary 
						  SET uid=".pwEscape($newInfo['uid1']).",username=".pwEscape($newInfo['newname'])."
						  WHERE did=".pwEscape($rt['did'])." AND uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT COUNT(*) AS count FROM {$source_prefix}diary LIMIT 1");
	echo "最大id：".$maxid."<br>最后id：".$end;
	
	if($lastid == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '38'){
    //日志个人分类表
	$lastid = '0';
	
	$query = $SDB->query("SELECT a.dtid,a.uid,bu.uid1,bu.newname 
						  FROM {$source_prefix}diarytype a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.uid
						  WHERE a.dtid > $start AND a.dtid < $end AND bu.uid1 != '0'
						  ORDER BY a.dtid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['dtid'];
		
		if(!empty($rt['uid1']) && !empty($rt['newname'])){
			$SDB->update("UPDATE {$source_prefix}diarytype 
						  SET uid=".pwEscape($rt['uid1'])."
						  WHERE dtid=".pwEscape($rt['dtid'])." AND uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
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
	//用户草稿箱信息
	$lastid = '0';
	$query = $SDB->query("SELECT a.did,a.uid,bu.uid1,bu.newname 
						  FROM {$source_prefix}draft a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.uid
						  WHERE a.did > $start AND a.did < $end AND bu.uid1 != '0'
						  ORDER BY a.did ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['did'];
		
		if(!empty($rt['uid1'])){
			$SDB->update("UPDATE {$source_prefix}draft 
						  SET uid=".pwEscape($rt['uid1'])."
						  WHERE did=".pwEscape($rt['did'])." AND uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(did) FROM {$source_prefix}draft LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif($step == '40'){
	//购买组相关信息
	$lastid = '0';
	$query = $SDB->query("SELECT uid
						  FROM {$source_prefix}extragroups
						  LIMIT $start, $percount");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid++;
		
		$uid1 = $SDB->get_value("SELECT uid1 FROM {$source_prefix}bakuids WHERE uid2=".pwEscape($rt['uid']));
		if(!empty($uid1)){
			$SDB->update("UPDATE {$source_prefix}extragroups 
						  SET uid=".pwEscape($uid1)."
						  WHERE uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	if($lastid == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif($step == '41'){
	//个人收藏夹
	$lastid = '0';
	$query = $SDB->query("SELECT uid
						  FROM {$source_prefix}favors
						  LIMIT $start, $percount");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid++;
		
		$uid1 = $SDB->get_value("SELECT uid1 FROM {$source_prefix}bakuids WHERE uid2=".pwEscape($rt['uid']));
		if(!empty($uid1)){
			$SDB->update("UPDATE {$source_prefix}favors 
						  SET uid=".pwEscape($uid1)."
						  WHERE uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT COUNT(*) AS count FROM {$source_prefix}favors");
	echo "最大id:".$maxid."<br>最后id:".$end;
	
	if($lastid == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif($step == '42'){
	//好友动态表
	$lastid = '0';
	$query = $SDB->query("SELECT a.id,a.uid,bu.uid1,bu.newname 
						  FROM {$source_prefix}feed a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.uid
						  WHERE a.id > $start AND a.id < $end AND bu.uid1 != '0'
						  ORDER BY a.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];
		
		if(!empty($rt['uid1'])){
			$SDB->update("UPDATE {$source_prefix}feed 
						  SET uid=".pwEscape($rt['uid1'])."
						  WHERE id=".pwEscape($rt['id'])." AND uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}feed LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif($step == '43'){
	//敏感词记录表
	$lastid = '0';
	$query = $SDB->query("SELECT a.id,a.assessor,bu.uid1,bu.newname 
						  FROM {$source_prefix}filter a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.username=a.assessor
						  WHERE a.id > $start AND a.id < $end AND bu.uid1 != '0'
						  ORDER BY a.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];
		
		if(!empty($rt['newname'])){
			$SDB->update("UPDATE {$source_prefix}filter 
						  SET assessor=".pwEscape($rt['newname'])."
						  WHERE id=".pwEscape($rt['id'])." AND assessor=".pwEscape($rt['assessor']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}filter LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '44') {
	//版块日志数据
	$lastid = '0';
	$query = $SDB->query("SELECT a.id,a.username1,a.username2,bu.uid1,bu.newname 
						  FROM {$source_prefix}forumlog a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.username=a.username1
						  WHERE a.id > $start AND a.id < $end AND bu.uid1 != '0'
						  ORDER BY a.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];
		
		if(!empty($rt['username2']) && !empty($rt['newname'])){
			$username2 = $SDB->get_value("SELECT newname FROM {$source_prefix}bakuids WHERE username=".pwEscape($rt['username2']));
		}
		$username2 = $username2 ? $username2 : $rt['username2'];
		
		if(!empty($rt['newname'])){
			$SDB->update("UPDATE {$source_prefix}forumlog 
						  SET username1=".pwEscape($rt['newname']).",username2=".pwEscape($username2)."
						  WHERE id=".pwEscape($rt['id'])." AND username1=".pwEscape($rt['username1']));
		}
		
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}forumlog LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '45') {
	//版主管理记录
	$lastid = '0';
	$query = $SDB->query("SELECT a.id,a.uid,a.username,a.toname,bu.uid1,bu.newname 
						  FROM {$source_prefix}forummsg a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.uid
						  WHERE a.id > $start AND a.id < $end AND bu.uid1 != '0'
						  ORDER BY a.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];
		
		if(!empty($rt['uid']) && !empty($rt['toname'])){
			$toname = $SDB->get_value("SELECT newname FROM {$source_prefix}bakuids 
									   WHERE username=".pwEscape($rt['toname']));
		}
		$toname = $toname ? $toname : $rt['toname'];
		
		if(!empty($rt['newname'])){
			$SDB->update("UPDATE {$source_prefix}forummsg 
						  SET uid=".pwEscape($rt['uid1']).",username=".pwEscape($rt['newname']).",toname=".pwEscape($toname)."
						  WHERE id=".pwEscape($rt['id'])." AND uid=".pwEscape($rt['uid']));
		}
		
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}forummsg LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '46') {
	//版块数据
	$lastid = '0';
	$query = $SDB->query("SELECT fid,forumadmin,fupadmin 
						  FROM {$source_prefix}forums 
						  WHERE fid > $start AND fid < $end AND (forumadmin!='' OR fupadmin!='')
						  ORDER BY fid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['fid'];
		$forumadminStr = $forumupadminStr = '';
		$forumadmin = $forumupadmin = array();
		
		if(!empty($rt['forumadmin'])){
			$fadmin = explode(",", trim($rt['forumadmin'], ','));
			$forumadminStr = "'".implode("','", $fadmin)."'";
			$query2 = $SDB->query("SELECT newname FROM {$source_prefix}bakuids WHERE username IN(".$forumadminStr.")");
			while ($rt2 = $SDB->fetch_array($query2)){
				$forumadmin[] = $rt2['newname'];
			}
			$forumadminStr = ",".implode(",", $forumadmin).",";
			
			if(!empty($forumadminStr)){
				$SDB->update("UPDATE {$source_prefix}forums SET forumadmin=".pwEscape($forumadminStr)." WHERE fid=".pwEscape($rt['fid']));
			}
		}
		if(!empty($rt['fupadmin'])){
			$fupadmin = explode(",", trim($rt['fupadmin'], ','));
			$forumupadminStr = "'".implode("','", $fupadmin)."'";
			$query2 = $SDB->query("SELECT newname FROM {$source_prefix}bakuids WHERE username IN(".$forumupadminStr.")");
			while ($rt2 = $SDB->fetch_array($query2)){
				$forumupadmin[] = $rt2['newname'];
			}
			$forumupadminStr = ",".implode(",", $forumupadmin).",";
			
			if(!empty($forumupadminStr)){
				$SDB->update("UPDATE {$source_prefix}forums SET fupadmin=".pwEscape($forumupadminStr)." WHERE fid=".pwEscape($rt['fid']));
			}
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(fid) FROM {$source_prefix}forums LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '47'){
	//版块访问权限出售设置
	$lastid = '0';
	$query = $SDB->query("SELECT a.id,a.uid,bu.uid1,bu.newname 
						  FROM {$source_prefix}forumsell a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.uid
						  WHERE a.id > $start AND a.id < $end AND bu.uid1 != '0'
						  ORDER BY a.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];
		
		if(!empty($rt['uid1'])){
			$SDB->update("UPDATE {$source_prefix}forumsell 
						  SET uid=".pwEscape($rt['uid1'])."
						  WHERE id=".pwEscape($rt['id'])." AND uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}forumsell LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '48'){
	//好友功能相关信息
	$lastid = '0';
	$query = $SDB->query("SELECT uid,friendid
						  FROM {$source_prefix}friends
						  ORDER BY uid,friendid ASC
						  LIMIT 0, $percount");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid++;
		
		if(!empty($rt['uid'])){
			$uid = $SDB->get_value("SELECT uid1 FROM {$source_prefix}bakuids 
									WHERE uid2=".pwEscape($rt['uid'])." LIMIT 1");
		}
		if(!empty($rt['friendid'])){
			$friendid = $SDB->get_value("SELECT uid1 FROM {$source_prefix}bakuids 
										 WHERE uid2=".pwEscape($rt['friendid'])." LIMIT 1");
		}
		$uid = $uid ? $uid : $rt['uid'];
		$friendid = $friendid ? $friendid : $rt['friendid'];
		
		if($uid && $friendid){
			$SDB->update("UPDATE {$source_prefix}friends 
						  SET uid=".pwEscape($uid).",friendid=".pwEscape($friendid)."
					      WHERE uid=".pwEscape($rt['uid'])." AND friendid=".pwEscape($rt['friendid']));
			
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT COUNT(*) FROM {$source_prefix}friends LIMIT 1");
	echo "最大id：".$maxid."<br>最后id：".$end;
	
	if($end < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '49'){
	//好友功能相关信息
	$lastid = '0';
	$query = $SDB->query("SELECT a.ftid,a.uid,bu.uid1,bu.newname 
						  FROM {$source_prefix}friendtype a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.uid
						  WHERE a.ftid > $start AND a.ftid < $end AND bu.uid1 != '0'
						  ORDER BY a.ftid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['ftid'];
		
		if(!empty($rt['uid1'])){
			$SDB->update("UPDATE {$source_prefix}friendtype 
						  SET uid=".pwEscape($rt['uid1'])."
						  WHERE ftid=".pwEscape($rt['ftid'])." AND uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(ftid) FROM {$source_prefix}friendtype LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '50'){
	//群组回复结构表
	$lastid = '0';
	$query = $SDB->query("SELECT uid
						  FROM {$source_prefix}group_replay
						  LIMIT $start, $percount");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid++;
		
		$uid = $SDB->get_value("SELECT uid1 FROM {$source_prefix}bakuids WHERE uid2=".pwEscape($rt['uid'])." LIMIT 1");
		$uid = $uid ? $uid : $rt['uid'];		
		
		if(!empty($uid)){
			$SDB->update("UPDATE {$source_prefix}group_replay 
						  SET uid=".pwEscape($uid)."
						  WHERE uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT COUNT(*) FROM {$source_prefix}group_replay");
	echo "最大id：".$maxid."<br>最后id：".$end;
	
	if($lastid == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '51'){
	//邀请码功能相关
	$lastid = '0';
	$query = $SDB->query("SELECT a.id,a.uid,a.receiver,bu.uid1,bu.newname 
						  FROM {$source_prefix}invitecode a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.uid
						  WHERE a.id > $start AND a.id < $end AND bu.uid1 != '0'
						  ORDER BY a.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];
		
		if(!empty($rt['receiver'])){
			$receiver = $SDB->get_value("SELECT newname 
										 FROM {$source_prefix}bakuids 
										 WHERE username=".pwEscape($rt['receiver'])." LIMIT 1");
		}
		$receiver = $receiver ? $receiver : $rt['receiver'];
		
		if(!empty($rt['uid1'])){
			$SDB->update("UPDATE {$source_prefix}invitecode 
						  SET uid=".pwEscape($rt['uid1']).",receiver=".pwEscape($receiver)."
						  WHERE id=".pwEscape($rt['id'])." AND uid=".pwEscape($rt['uid'])." AND receiver=".pwEscape($receiver));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}invitecode LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '52'){
	//好友邀请宣传奖励记录表
	$lastid = '0';
	$query = $SDB->query("SELECT a.id,a.uid,a.username,bu.uid1,bu.newname 
						  FROM {$source_prefix}inviterecord a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.uid
						  WHERE a.id > $start AND a.id < $end AND bu.uid1 != '0'
						  ORDER BY a.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];

		if(!empty($rt['uid1']) && !empty($rt['newname'])){
			$SDB->update("UPDATE {$source_prefix}inviterecord 
						  SET uid=".pwEscape($rt['uid1']).",username=".pwEscape($rt['newname'])."
						  WHERE id=".pwEscape($rt['id'])." AND uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}inviterecord LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif($step == '53'){
	//任务申请表
	$lastid = '0';
	$query = $SDB->query("SELECT a.id,a.userid,bu.uid1,bu.newname 
						  FROM {$source_prefix}jober a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.userid
						  WHERE a.id > $start AND a.id < $end AND bu.uid1 != '0'
						  ORDER BY a.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];

		if(!empty($rt['uid1'])){
			$SDB->update("UPDATE {$source_prefix}jober 
						  SET userid=".pwEscape($rt['uid1'])."
						  WHERE id=".pwEscape($rt['id'])." AND userid=".pwEscape($rt['userid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}jober LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '54'){
	//勋章操作信息
	$lastid = '0';
	$query = $SDB->query("SELECT a.id,a.awardee,a.awarder,bu.uid1,bu.newname 
						  FROM {$source_prefix}medalslogs a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.username=a.awardee
						  WHERE a.id > $start AND a.id < $end AND bu.uid1 != '0'
						  ORDER BY a.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];
		
		if(!empty($rt['awarder'])){
			$awarder = $SDB->get_value("SELECT newname FROM {$source_prefix}bakuids WHERE username=".pwEscape($rt['awarder'])." LIMIT 1");
		}
		$awarder = $awarder ? $awarder : $rt['awarder'];

		if(!empty($rt['newname'])){
			$SDB->update("UPDATE {$source_prefix}medalslogs 
						  SET awardee=".pwEscape($rt['newname']).",awarder=".pwEscape($awarder)."
						  WHERE id=".pwEscape($rt['id'])." AND awardee=".pwEscape($rt['awardee']));
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
}elseif ($step == '55'){
	//用户勋章记录表
	$lastid = '0';
	$query = $SDB->query("SELECT a.id,a.uid,bu.uid1,bu.newname 
						  FROM {$source_prefix}medaluser a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.uid
						  WHERE a.id > $start AND a.id < $end AND bu.uid1 != '0'
						  ORDER BY a.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];

		if(!empty($rt['uid1'])){
			$SDB->update("UPDATE {$source_prefix}medaluser 
						  SET uid=".pwEscape($rt['uid1'])."
						  WHERE id=".pwEscape($rt['id'])." AND uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}medaluser LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '56'){
	//用户的自定义积分
	$lastid = '0';
	$query = $SDB->query("SELECT uid
						  FROM {$source_prefix}membercredit
						  ORDER BY uid ASC 
						  LIMIT 0, $percount");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid++;
		
		$uid = $SDB->get_value("SELECT uid1 FROM {$source_prefix}bakuids WHERE uid2=".pwEscape($rt['uid']));
		if(!empty($uid)){
			$SDB->update("UPDATE {$source_prefix}membercredit 
						  SET uid=".pwEscape($uid)."
						  WHERE uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT COUNT(*) FROM {$source_prefix}membercredit");
	echo "最大id：".$maxid."<br>最后id：".$end;
	
	if($end < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '57'){
	//用户基本信息
	$lastid = '0';
	$query = $SDB->query("SELECT uid
						  FROM {$source_prefix}memberdata
						  LIMIT 0, $percount");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid++;
		
		$uid = $SDB->get_value("SELECT uid1 FROM {$source_prefix}bakuids WHERE uid2=".pwEscape($rt['uid']));
		if(!empty($uid)){
			$SDB->update("UPDATE {$source_prefix}memberdata 
						  SET uid=".pwEscape($uid)."
						  WHERE uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT COUNT(*) AS count FROM {$source_prefix}memberdata LIMIT 1");
	echo "最大id：".$maxid."<br>最后id：".$end;
	
	if($end < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '58'){
	//用户相关信息
	$lastid = '0';
	$query = $SDB->query("SELECT uid
						  FROM {$source_prefix}memberinfo
						  LIMIT 0, $percount");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid++;
		
		$uid = $SDB->get_value("SELECT uid1 FROM {$source_prefix}bakuids WHERE uid2=".pwEscape($rt['uid']));
		if(!empty($uid)){
			$SDB->update("UPDATE {$source_prefix}memberinfo 
						  SET uid=".pwEscape($uid)."
						  WHERE uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT COUNT(*) AS count FROM {$source_prefix}memberinfo LIMIT 1");
	echo "最大id：".$maxid."<br>最后id：".$end;
	
	if($end < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '59'){
	//会员主信息表
	$lastid = '0';
	$query = $SDB->query("SELECT a.uid,a.username,bu.uid1,bu.newname 
						  FROM {$source_prefix}members a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.uid
						  WHERE a.uid > $start AND a.uid < $end AND bu.uid1 != '0'
						  ORDER BY a.uid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['uid'];

		if(!empty($rt['uid1'])){
			$SDB->update("UPDATE {$source_prefix}members 
						  SET uid=".pwEscape($rt['uid1']).",username=".pwEscape($rt['newname'])."
						  WHERE uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(uid2) FROM {$source_prefix}bakuids LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '60'){
	//后台管理便笺信息
	$lastid = '0';
	$query = $SDB->query("SELECT a.mid,a.username,bu.uid1,bu.newname 
						  FROM {$source_prefix}memo a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.username=a.username
						  WHERE a.mid > $start AND a.mid < $end AND bu.uid1 != '0'
						  ORDER BY a.mid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['mid'];

		if(!empty($rt['uid1'])){
			$SDB->update("UPDATE {$source_prefix}memo 
						  SET username=".pwEscape($rt['newname'])."
						  WHERE mid=".pwEscape($rt['mid'])." AND username=".pwEscape($rt['username']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(mid) FROM {$source_prefix}memo LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '61'){
	//消息中心附件表
	$lastid = '0';
	$query = $SDB->query("SELECT a.id,a.uid,bu.uid1,bu.newname 
						  FROM {$source_prefix}ms_attachs a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.uid
						  WHERE a.id > $start AND a.id < $end AND bu.uid1 != '0'
						  ORDER BY a.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];

		if(!empty($rt['uid1'])){
			$SDB->update("UPDATE {$source_prefix}ms_attachs 
						  SET uid=".pwEscape($rt['uid1'])."
						  WHERE id=".pwEscape($rt['id'])." AND uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}ms_attachs LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '62'){
	//消息配置表
	$lastid = '0';
	$query = $SDB->query("SELECT uid
						  FROM {$source_prefix}ms_configs
						  LIMIT 0, $percount");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid++;
		
		$uid = $SDB->get_value("SELECT uid1 FROM {$source_prefix}bakuids WHERE uid2=".pwEscape($rt['uid']));
		if(!empty($uid)){
			$SDB->update("UPDATE {$source_prefix}ms_configs 
						  SET uid=".pwEscape($uid)."
						  WHERE uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT COUNT(*) FROM {$source_prefix}ms_configs");
	echo "最大id：".$maxid."<br>最后id：".$end;
	
	if($end < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '63'){
	//消息体表
	$lastid = '0';
	$extra = array();
	
	$query = $SDB->query("SELECT a.mid,a.create_uid,a.create_username,a.extra,bu.uid1,bu.newname 
						  FROM {$source_prefix}ms_messages a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.create_uid
						  WHERE a.mid > $start AND a.mid < $end AND bu.uid1 != '0'
						  ORDER BY a.mid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		$lastid = $rt['mid'];
		$extraStr = "'".implode("','", unserialize($rt['extra']))."'";
		
		$query2 = $SDB->query("SELECT newname FROM {$source_prefix}bakuids WHERE username IN(".$extraStr.")");
		while ($rt2 = $SDB->fetch_array($query2)){
			$extra[] = $rt2['newname'];
		}
		$rt['extra'] = $extra ? serialize($extra) : $rt['extra'];

		if(!empty($rt['uid1'])){
			$SDB->update("UPDATE {$source_prefix}ms_messages 
						  SET create_uid=".pwEscape($rt['uid1']).",create_username=".pwEscape($rt['newname']).",extra=".pwEscape($rt['extra'])."
						  WHERE mid=".pwEscape($rt['mid'])." AND create_uid=".pwEscape($rt['create_uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(mid) FROM {$source_prefix}ms_messages LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '64'){
	//消息关系体表
	$lastid = '0';
	$query = $SDB->query("SELECT a.rid,a.uid,bu.uid1,bu.newname 
						  FROM {$source_prefix}ms_relations a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.uid
						  WHERE a.rid > $start AND a.rid < $end AND bu.uid1 != '0'
						  ORDER BY a.rid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['rid'];

		if(!empty($rt['uid1'])){
			$SDB->update("UPDATE {$source_prefix}ms_relations 
						  SET uid=".pwEscape($rt['uid1'])."
						  WHERE rid=".pwEscape($rt['rid'])." AND uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(rid) FROM {$source_prefix}ms_relations LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '65'){
	//消息回复表
	$lastid = '0';
	$query = $SDB->query("SELECT a.id,a.create_uid,a.create_username,bu.uid1,bu.newname 
						  FROM {$source_prefix}ms_replies a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.create_uid
						  WHERE a.id > $start AND a.id < $end AND bu.uid1 != '0'
						  ORDER BY a.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];

		if(!empty($rt['uid1'])){
			$SDB->update("UPDATE {$source_prefix}ms_replies 
						  SET create_uid=".pwEscape($rt['uid1']).",create_username=".pwEscape($rt['newname'])."
						  WHERE id=".pwEscape($rt['id'])." AND create_uid=".pwEscape($rt['create_uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}ms_replies LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '66'){
	//消息中心搜索关系表
	$lastid = '0';
	$query = $SDB->query("SELECT a.rid,a.uid,a.create_uid,bu.uid1,bu.newname 
						  FROM {$source_prefix}ms_searchs a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.uid
						  WHERE a.rid > $start AND a.rid < $end AND bu.uid1 != '0'
						  ORDER BY a.rid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['rid'];
		
		if(!empty($rt['create_uid'])){
			$create_uid = $SDB->get_value("SELECT uid1 FROM {$source_prefix}bakuids WHERE uid2=".pwEscape($rt['create_uid']));
		}
		$create_uid = $create_uid ? $create_uid : $rt['create_uid'];
		
		if(!empty($rt['uid1']) && !empty($create_uid)){
			$SDB->update("UPDATE {$source_prefix}ms_searchs 
						  SET uid=".pwEscape($rt['uid1']).",create_uid=".pwEscape($create_uid)."
						  WHERE rid=".pwEscape($rt['rid'])." AND uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(rid) FROM {$source_prefix}ms_searchs LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '67'){
	//圈子留言表
	$lastid = '0';
	$query = $SDB->query("SELECT a.id,a.uid,a.username,a.touid,bu.uid1,bu.newname 
						  FROM {$source_prefix}oboard a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.uid
						  WHERE a.id > $start AND a.id < $end AND bu.uid1 != '0'
						  ORDER BY a.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];
		
		if(!empty($rt['touid'])){
			$touid = $SDB->get_value("SELECT uid1 FROM {$source_prefix}bakuids WHERE uid2=".pwEscape($rt['touid']));
		}
		$touid = $touid ? $touid : $rt['touid'];
		
		if(!empty($rt['uid1']) && !empty($touid)){
			$SDB->update("UPDATE {$source_prefix}oboard 
						  SET uid=".pwEscape($rt['uid1']).",username=".pwEscape($rt['newname']).",touid=".pwEscape($touid)."
						  WHERE id=".pwEscape($rt['id'])." AND uid=".pwEscape($rt['uid'])." AND touid=".pwEscape($rt['touid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}oboard LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '68'){
	//圈子用户相关表
	$lastid = '0';
	$query = $SDB->query("SELECT uid
						  FROM {$source_prefix}ouserdata
						  LIMIT 0, $percount");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid++;
		
		$uid = $SDB->get_value("SELECT uid1 FROM {$source_prefix}bakuids WHERE uid2=".pwEscape($rt['uid']));
		if(!empty($uid)){
			$SDB->update("UPDATE {$source_prefix}ouserdata 
						  SET uid=".pwEscape($uid)."
						  WHERE uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT COUNT(*) AS count FROM {$source_prefix}ouserdata LIMIT 1");
	echo "最大id：".$maxid."<br>最后id：".$end;
	
	if($end < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '69'){
	//圈子记录表
	$lastid = '0';
	$query = $SDB->query("SELECT a.id,a.uid,a.touid,bu.uid1,bu.newname 
						  FROM {$source_prefix}owritedata a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.uid
						  WHERE a.id > $start AND a.id < $end AND bu.uid1 != '0'
						  ORDER BY a.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];
		
		if(!empty($rt['touid'])){
			$touid = $SDB->get_value("SELECT uid1 FROM {$source_prefix}bakuids WHERE uid2=".pwEscape($rt['touid']));
		}
		$touid = $touid ? $touid : $rt['touid'];
		
		if(!empty($rt['uid1']) && !empty($touid)){
			$SDB->update("UPDATE {$source_prefix}owritedata 
						  SET uid=".pwEscape($rt['uid1']).",touid=".pwEscape($touid)."
						  WHERE id=".pwEscape($rt['id'])." AND uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}owritedata LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '70'){
	//团购活动报名信息表
	$lastid = '0';
	$query = $SDB->query("SELECT a.pcmid,a.uid,a.username,bu.uid1,bu.newname 
						  FROM {$source_prefix}pcmember a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.uid
						  WHERE a.pcmid > $start AND a.pcmid < $end AND bu.uid1 != '0'
						  ORDER BY a.pcmid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['pcmid'];
		
		if(!empty($rt['uid1'])){
			$SDB->update("UPDATE {$source_prefix}pcmember 
						  SET uid=".pwEscape($rt['uid1']).",username=".pwEscape($rt['newname'])."
						  WHERE pcmid=".pwEscape($rt['pcmid'])." AND uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(pcmid) FROM {$source_prefix}pcmember LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '71'){
	//权限表
	report_log();
	newURL($step);
}elseif ($step == '72'){
	//回复贴信息表
	$lastid = '0';
	$query = $SDB->query("SELECT a.pid,a.author,a.authorid,bu.uid1,bu.newname 
						  FROM {$source_prefix}posts a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.authorid
						  WHERE a.pid > $start AND a.pid < $end AND bu.uid1 != '0'
						  ORDER BY a.pid ASC
						  LIMIT $percount ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['pid'];
		
		if(!empty($rt['uid1'])){
			$SDB->update("UPDATE {$source_prefix}posts 
						  SET author=".pwEscape($rt['newname']).",authorid=".pwEscape($rt['uid1'])."
						  WHERE pid=".pwEscape($rt['pid'])." AND authorid=".pwEscape($rt['authorid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(pid) FROM {$source_prefix}posts LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '73'){
	//空间隐私表
	$lastid = '0';
	$query = $SDB->query("SELECT uid
						  FROM {$source_prefix}privacy
						  LIMIT 0, $percount");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid++;
		
		$uid = $SDB->get_value("SELECT uid1 FROM {$source_prefix}bakuids WHERE uid2=".pwEscape($rt['uid']));
		if(!empty($uid)){
			$SDB->update("UPDATE {$source_prefix}privacy 
						  SET uid=".pwEscape($uid)."
						  WHERE uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT COUNT(*) FROM {$source_prefix}privacy LIMIT 1");
	echo "最大id：".$maxid."<br>最后id：".$end;
	
	if($end < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '74'){
	//模板自定义推送数据表
	$lastid = '0';
	$query = $SDB->query("SELECT a.id,a.editor,bu.uid1,bu.newname 
						  FROM {$source_prefix}pushdata a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.username=a.editor
						  WHERE a.id > $start AND a.id < $end AND bu.uid1 != '0'
						  ORDER BY a.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];
		
		if(!empty($rt['uid1'])){
			$SDB->update("UPDATE {$source_prefix}pushdata 
						  SET editor=".pwEscape($rt['newname'])."
						  WHERE id=".pwEscape($rt['id'])." AND editor=".pwEscape($rt['editor']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}pushdata LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '75'){
	//门户推送上传图片
	$lastid = '0';
	$query = $SDB->query("SELECT a.id,a.creator,bu.uid1,bu.newname 
						  FROM {$source_prefix}pushpic a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.username=a.creator
						  WHERE a.id > $start AND a.id < $end AND bu.uid1 != '0'
						  ORDER BY a.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];
		
		if(!empty($rt['uid1'])){
			$SDB->update("UPDATE {$source_prefix}pushpic 
						  SET creator=".pwEscape($rt['newname'])."
						  WHERE id=".pwEscape($rt['id'])." AND creator=".pwEscape($rt['creator']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}pushpic LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '76'){
	//评价统计表
	$lastid = '0';
	$query = $SDB->query("SELECT uid
						  FROM {$source_prefix}rate
						  ORDER BY objectid ASC 
						  LIMIT $start, $percount");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid++;
		
		$uid = $SDB->get_value("SELECT uid1 FROM {$source_prefix}bakuids WHERE uid2=".pwEscape($rt['uid']));
		if(!empty($uid)){
			$SDB->update("UPDATE {$source_prefix}rate 
						  SET uid=".pwEscape($uid)."
						  WHERE uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT COUNT(*) AS count FROM {$source_prefix}rate");
	echo "最大id：".$maxid."<br>最后id：".$end;
	
	if($end < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '77'){
	//评价配置表
	$lastid = '0';
	$query = $SDB->query("SELECT a.id,a.updater,bu.uid1,bu.newname 
						  FROM {$source_prefix}rateconfig a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.username=a.updater
						  WHERE a.id > $start AND a.id < $end AND bu.uid1 != '0'
						  ORDER BY a.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];
		
		if(!empty($rt['uid1'])){
			$SDB->update("UPDATE {$source_prefix}rateconfig 
						  SET updater=".pwEscape($rt['newname'])."
						  WHERE id=".pwEscape($rt['id'])." AND updater=".pwEscape($rt['updater']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}rateconfig LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '78'){
	//删除帖子相关信息
	$lastid = '0';
	$query = $SDB->query("SELECT admin
						  FROM {$source_prefix}recycle
						  LIMIT $start, $percount");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid++;

		$admin = $SDB->get_value("SELECT newname FROM {$source_prefix}bakuids WHERE username=".pwEscape($rt['admin']));
		if(!empty($admin)){
			$SDB->update("UPDATE {$source_prefix}recycle 
						  SET admin=".pwEscape($admin)."
						  WHERE admin=".pwEscape($rt['admin']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	if($lastid == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '79'){
	//帖子报告信息
	$lastid = '0';
	$query = $SDB->query("SELECT a.id,a.uid,bu.uid1,bu.newname 
						  FROM {$source_prefix}report a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.uid
						  WHERE a.id > $start AND a.id < $end AND bu.uid1 != '0'
						  ORDER BY a.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];
		
		if(!empty($rt['uid1'])){
			$SDB->update("UPDATE {$source_prefix}report 
						  SET uid=".pwEscape($rt['uid1'])."
						  WHERE id=".pwEscape($rt['id'])." AND uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}report LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '80'){
	//悬赏贴
	$lastid = '0';
	$query = $SDB->query("SELECT author
						  FROM {$source_prefix}reward
						  LIMIT $start, $percount");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid++;

		$author = $SDB->get_value("SELECT newname FROM {$source_prefix}bakuids WHERE username=".pwEscape($rt['author']));
		if(!empty($author)){
			$SDB->update("UPDATE {$source_prefix}reward 
						  SET author=".pwEscape($author)."
						  WHERE author=".pwEscape($rt['author']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	if($lastid == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '81'){
	//会员分享表
	$lastid = '0';
	$query = $SDB->query("SELECT a.sid,a.username,bu.uid1,bu.newname 
						  FROM {$source_prefix}sharelinks a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.username=a.username
						  WHERE a.sid > $start AND a.sid < $end AND a.username != '' AND bu.uid1 != '0'
						  ORDER BY a.sid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['sid'];
		
		if(!empty($rt['uid1'])){
			$SDB->update("UPDATE {$source_prefix}sharelinks 
						  SET username=".pwEscape($rt['newname'])."
						  WHERE sid=".pwEscape($rt['sid'])." AND username=".pwEscape($rt['username']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(sid) FROM {$source_prefix}sharelinks LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '82'){
	//用户指定版块权限
	$lastid = '0';
	$query = $SDB->query("SELECT uid
						  FROM {$source_prefix}singleright
						  LIMIT 0, $percount");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid++;

		$uid = $SDB->get_value("SELECT uid1 FROM {$source_prefix}bakuids WHERE uid2=".pwEscape($rt['uid']));
		if(!empty($uid)){
			$SDB->update("UPDATE {$source_prefix}singleright 
						  SET uid=".pwEscape($uid)."
						  WHERE uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT COUNT(*) FROM {$source_prefix}singleright");
	echo "最大id：".$maxid."<br>最后id：".$end;
	
	if($end < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '83'){
	//个人空间信息表
	$lastid = '0';
	
	$query = $SDB->query("SELECT uid,visitors
						  FROM {$source_prefix}space
						  LIMIT 0, $percount");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$visitorArr = array();
		$lastid++;
		
		$visitors = unserialize($rt['visitors']);
		foreach ($visitors AS $k => $v){
			$uid1 = $SDB->get_value("SELECT uid1 FROM  {$source_prefix}bakuids WHERE uid2=".pwEscape($k));
			$visitorArr[$uid1] = $v;
		}
		$rt['visitors'] = $visitorArr ? serialize($visitorArr) : $rt['visitors'];

		$uid = $SDB->get_value("SELECT uid1 FROM {$source_prefix}bakuids WHERE uid2=".pwEscape($rt['uid']));
		if(!empty($uid)){
			$SDB->update("UPDATE {$source_prefix}space 
						  SET uid=".pwEscape($uid).",visitors='".$rt['visitors']."'
						  WHERE uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT COUNT(*) FROM {$source_prefix}space");
	echo "最大id：".$maxid."<br>最后id：".$end;
	
	if($end < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '84'){
	//专题分类表
	$lastid = '0';
	$query = $SDB->query("SELECT a.id,a.creator,bu.uid1,bu.newname 
						  FROM {$source_prefix}stopiccategory a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.username=a.creator
						  WHERE a.id > $start AND a.id < $end AND a.creator != 'phpwind' AND bu.uid1 != '0'
						  ORDER BY a.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];
		
		if(!empty($rt['uid1'])){
			$SDB->update("UPDATE {$source_prefix}stopiccategory 
						  SET creator=".pwEscape($rt['newname'])."
						  WHERE id=".pwEscape($rt['id'])." AND creator=".pwEscape($rt['creator']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}stopiccategory LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '85'){
	//专题背景图片表
	$lastid = '0';
	$query = $SDB->query("SELECT a.id,a.creator,bu.uid1,bu.newname 
						  FROM {$source_prefix}stopicpictures a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.username=a.creator
						  WHERE a.id > $start AND a.id < $end AND a.creator != '' AND bu.uid1 != '0'
						  ORDER BY a.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];
		
		if(!empty($rt['uid1'])){
			$SDB->update("UPDATE {$source_prefix}stopicpictures 
						  SET creator=".pwEscape($rt['newname'])."
						  WHERE id=".pwEscape($rt['id'])." AND creator=".pwEscape($rt['creator']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}stopicpictures LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '86'){
	//论坛风格主表
	$lastid = '0';
	$query = $SDB->query("SELECT a.sid,a.uid,bu.uid1,bu.newname 
						  FROM {$source_prefix}styles a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.uid
						  WHERE a.sid > $start AND a.sid < $end AND a.uid != '' AND bu.uid1 != '0'
						  ORDER BY a.sid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['sid'];
		
		if(!empty($rt['uid1'])){
			$SDB->update("UPDATE {$source_prefix}styles 
						  SET uid=".pwEscape($rt['uid1'])."
						  WHERE sid=".pwEscape($rt['sid'])." AND uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(sid) FROM {$source_prefix}styles LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '87'){
	//帖子基本信息表
	$lastid = '0';
	$query = $SDB->query("SELECT a.tid,a.author,a.authorid,a.lastposter,bu.uid1,bu.newname 
						  FROM {$source_prefix}threads a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.authorid
						  WHERE a.tid > $start AND a.tid < $end AND bu.uid1 != '0'
						  ORDER BY a.tid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['tid'];
		
		if(!empty($rt['lastposter'])){
			$lastposter = $SDB->get_value("SELECT newname FROM {$source_prefix}bakuids 
										   WHERE username=".pwEscape($rt['lastposter'])." LIMIT 1");
		}
		$lastposter = $lastposter ? $lastposter : $rt['lastposter'];
		
		if(!empty($rt['uid1']) && !empty($rt['newname'])){
			$SDB->update("UPDATE {$source_prefix}threads 
						  SET author=".pwEscape($rt['newname']).",authorid=".pwEscape($rt['uid1']).",lastposter=".pwEscape($lastposter)."
						  WHERE tid=".pwEscape($rt['tid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(tid) FROM {$source_prefix}threads LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '88'){
	//道具操作相关信息(与交易币相关)
	$lastid = '0';
	$query = $SDB->query("SELECT a.id,a.uid,a.username,a.touid,bu.uid1,bu.newname 
						  FROM {$source_prefix}toollog a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.uid
						  WHERE a.id > $start AND a.id < $end AND bu.uid1 != '0'
						  ORDER BY a.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];
		
		if(!empty($rt['touid'])){
			$touid = $SDB->get_value("SELECT uid1 FROM {$source_prefix}bakuids 
									  WHERE uid2=".pwEscape($rt['touid'])." LIMIT 1");
		}
		$touid = $touid ? $touid : $rt['touid'];
		
		if(!empty($rt['uid1']) && !empty($rt['newname'])){
			$SDB->update("UPDATE {$source_prefix}toollog 
						  SET uid=".pwEscape($rt['uid1']).",username=".pwEscape($rt['newname']).",touid=".pwEscape($touid)."
						  WHERE id=".pwEscape($rt['id']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}toollog LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '89'){
	//交易帖子信息
	$lastid = '0';
	
	$query = $SDB->query("SELECT uid
						  FROM {$source_prefix}trade
						  ORDER BY tid ASC 
						  LIMIT $start, $percount");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid++;

		$uid = $SDB->get_value("SELECT uid1 FROM {$source_prefix}bakuids WHERE uid2=".pwEscape($rt['uid']));
		if(!empty($uid)){
			$SDB->update("UPDATE {$source_prefix}trade 
						  SET uid=".pwEscape($uid)."
						  WHERE uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	if($lastid == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '90'){
	//交易记录表
	$lastid = '0';
	$query = $SDB->query("SELECT a.oid,a.buyer,a.seller,bu.uid1,bu.newname 
						  FROM {$source_prefix}tradeorder a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.username=a.buyer
						  WHERE a.oid > $start AND a.oid < $end AND bu.uid1 != '0'
						  ORDER BY a.oid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['oid'];
		
		if(!empty($rt['seller'])){
			$seller = $SDB->get_value("SELECT newname FROM {$source_prefix}bakuids 
									  WHERE username=".pwEscape($rt['seller'])." LIMIT 1");
		}
		$seller = $seller ? $seller : $rt['seller'];
		
		if(!empty($rt['uid1']) && !empty($rt['newname'])){
			$SDB->update("UPDATE {$source_prefix}tradeorder 
						  SET buyer=".pwEscape($rt['newname']).",seller=".pwEscape($seller)."
						  WHERE oid=".pwEscape($rt['oid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(oid) FROM {$source_prefix}tradeorder LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '91'){
	//用户中心同步积分通知队列
	$lastid = '0';
	
	$query = $SDB->query("SELECT uid
						  FROM {$source_prefix}ucsyncredit
						  LIMIT 0, $percount");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid++;

		$uid = $SDB->get_value("SELECT uid1 FROM {$source_prefix}bakuids WHERE uid2=".pwEscape($rt['uid']));
		if(!empty($uid)){
			$SDB->update("UPDATE {$source_prefix}ucsyncredit 
						  SET uid=".pwEscape($uid)."
						  WHERE uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT COUNT(*) FROM {$source_prefix}ucsyncredit");
	echo "最大id：".$maxid."<br>最后id：".$end;
	
	if($end < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '92'){
	//用户添加的应用的数据表
	$lastid = '0';
	
	$query = $SDB->query("SELECT uid
						  FROM {$source_prefix}userapp
						  LIMIT 0, $percount");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid++;

		$uid = $SDB->get_value("SELECT uid1 FROM {$source_prefix}bakuids WHERE uid2=".pwEscape($rt['uid']));
		if(!empty($uid)){
			$SDB->update("UPDATE {$source_prefix}userapp 
						  SET uid=".pwEscape($uid)."
						  WHERE uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT COUNT(*) FROM {$source_prefix}userapp");
	echo "最大id：".$maxid."<br>最后id：".$end;
	
	if($end < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '93'){
	//用户帐号绑定/切换
	$lastid = '0';
	$query = $SDB->query("SELECT a.id,a.uid,bu.uid1,bu.newname 
						  FROM {$source_prefix}userbinding a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.uid
						  WHERE a.id > $start AND a.id < $end AND bu.uid1 != '0'
						  ORDER BY a.id ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['id'];
		
		if(!empty($rt['uid1'])){
			$SDB->update("UPDATE {$source_prefix}userbinding 
						  SET uid=".pwEscape($rt['uid1'])."
						  WHERE id=".pwEscape($rt['id']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(id) FROM {$source_prefix}userbinding LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '94'){
	//用户数据缓存表
	$lastid = '0';
	
	$query = $SDB->query("SELECT uid
						  FROM {$source_prefix}usercache
						  LIMIT 0, $percount");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid++;

		$uid = $SDB->get_value("SELECT uid1 FROM {$source_prefix}bakuids WHERE uid2=".pwEscape($rt['uid']));
		if(!empty($uid)){
			$SDB->update("UPDATE {$source_prefix}usercache 
						  SET uid=".pwEscape($uid)."
						  WHERE uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT COUNT(*) FROM {$source_prefix}usercache");
	echo "最大id：".$maxid."<br>最后id：".$end;
	
	if($end < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '95'){
	//道具交易数据信息
	$lastid = '0';
	
	$query = $SDB->query("SELECT uid
						  FROM {$source_prefix}usertool
						  LIMIT 0, $percount");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid++;

		$uid = $SDB->get_value("SELECT uid1 FROM {$source_prefix}bakuids WHERE uid2=".pwEscape($rt['uid']));
		if(!empty($uid)){
			$SDB->update("UPDATE {$source_prefix}usertool 
						  SET uid=".pwEscape($uid)."
						  WHERE uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT COUNT(*) FROM {$source_prefix}usertool");
	echo "最大id：".$maxid."<br>最后id：".$end;
	
	if($end < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '96'){
	//会员投票表
	$lastid = '0';
	
	$query = $SDB->query("SELECT tid,uid,username
						  FROM {$source_prefix}voter
						  ORDER BY tid ASC 
						  LIMIT $start, $percount");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid++;

		$newInfo = $SDB->get_one("SELECT uid1,newname FROM {$source_prefix}bakuids WHERE uid2=".pwEscape($rt['uid']));
		if(!empty($newInfo)){
			$SDB->update("UPDATE {$source_prefix}voter 
						  SET uid=".pwEscape($newInfo['uid1']).",username=".pwEscape($newInfo['newname'])."
						  WHERE tid=".pwEscape($rt['tid'])." AND uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	if($lastid == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '97'){
	//新鲜事评论关系表
	$lastid = '0';
	$query = $SDB->query("SELECT a.cid,a.uid,bu.uid1,bu.newname 
						  FROM {$source_prefix}weibo_cmrelations a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.uid
						  WHERE a.cid > $start AND a.cid < $end AND bu.uid1 != '0'
						  ORDER BY a.cid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['cid'];
		
		if(!empty($rt['uid1'])){
			$SDB->update("UPDATE {$source_prefix}weibo_cmrelations 
						  SET uid=".pwEscape($rt['uid1'])."
						  WHERE cid=".pwEscape($rt['cid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(cid) FROM {$source_prefix}weibo_cmrelations LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '98'){
	//新鲜事评论表
	$lastid = '0';
	$query = $SDB->query("SELECT a.cid,a.uid,bu.uid1,bu.newname 
						  FROM {$source_prefix}weibo_comment a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.uid
						  WHERE a.cid > $start AND a.cid < $end AND bu.uid1 != '0'
						  ORDER BY a.cid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['cid'];
		
		if(!empty($rt['uid1'])){
			$SDB->update("UPDATE {$source_prefix}weibo_comment 
						  SET uid=".pwEscape($rt['uid1'])."
						  WHERE cid=".pwEscape($rt['cid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(cid) FROM {$source_prefix}weibo_comment LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '99'){
	//新鲜事内容表
	$lastid = '0';
	$query = $SDB->query("SELECT a.mid,a.uid,bu.uid1,bu.newname 
						  FROM {$source_prefix}weibo_content a 
						  LEFT JOIN {$source_prefix}bakuids bu ON bu.uid2=a.uid
						  WHERE a.mid > $start AND a.mid < $end AND bu.uid1 != '0'
						  ORDER BY a.mid ASC ");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid = $rt['mid'];
		
		if(!empty($rt['uid1'])){
			$SDB->update("UPDATE {$source_prefix}weibo_content 
						  SET uid=".pwEscape($rt['uid1'])."
						  WHERE mid=".pwEscape($rt['mid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT MAX(mid) FROM {$source_prefix}weibo_content LIMIT 1");
	empty($lastid) && $lastid = $end;
	echo "最大id：".$maxid."<br>最后id：".$lastid;
	
	if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '100'){
	//新鲜事@我的关联表
	$lastid = '0';
	
	$query = $SDB->query("SELECT uid
						  FROM {$source_prefix}weibo_referto
						  LIMIT 0, $percount");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid++;

		$uid = $SDB->get_value("SELECT uid1 FROM {$source_prefix}bakuids WHERE uid2=".pwEscape($rt['uid']));
		if(!empty($uid)){
			$SDB->update("UPDATE {$source_prefix}weibo_referto 
						  SET uid=".pwEscape($uid)."
						  WHERE uid=".pwEscape($rt['uid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT COUNT(*) FROM {$source_prefix}weibo_referto");
	echo "最大id：".$maxid."<br>最后id：".$end;
	
	if($end < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else {
		report_log();
		newURL($step);
	}
}elseif ($step == '101'){
	//我关注的新鲜事关系表
	$lastid = '0';
	
	$query = $SDB->query("SELECT uid,authorid
						  FROM {$source_prefix}weibo_relations
						  LIMIT 0, $percount");
	while ($rt = $SDB->fetch_array($query)){
		Add_S($rt);
		$lastid++;
		
		$uid = $SDB->get_value("SELECT uid1 FROM {$source_prefix}bakuids WHERE uid2=".pwEscape($rt['uid']));
		$uid = $uid ? $uid : $rt['uid'];
		
		if(!empty($rt['authorid'])){
			$authorid = $SDB->get_value("SELECT uid1 FROM {$source_prefix}bakuids WHERE uid2=".pwEscape($rt['authorid'])." LIMIT 1");
		}
		$authorid = $authorid ? $authorid : $rt['authorid'];

		if(!empty($uid)){
			$SDB->update("UPDATE {$source_prefix}weibo_relations 
						  SET uid=".pwEscape($uid).",authorid=".pwEscape($authorid)."
						  WHERE uid=".pwEscape($rt['uid'])." AND authorid=".pwEscape($rt['authorid']));
		}
		$s_c++;
	}
	$SDB->free_result($query);
	
	$maxid = $SDB->get_value("SELECT COUNT(*) FROM {$source_prefix}weibo_relations");
	echo "最大id：".$maxid."<br>最后id：".$end;
	
	if($end < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
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
	writeover(S_P.'tmp_grelation.php', "\r\n\$_grelation = ".pw_var_export($grelation).";\r\n", true);
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