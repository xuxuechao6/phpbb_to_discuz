<?php
!function_exists('readover') && exit('Forbidden');

function Loginout() {
	global $db,$timestamp,$db_onlinetime,$groupid,$windid,$winduid,$db_ckpath,$db_ckdomain,$db_online;
	$thisvisit=$timestamp-$db_onlinetime*1.5;
	$db->update('UPDATE pw_memberdata SET thisvisit='.pwEscape($thisvisit).' WHERE uid='.pwEscape($winduid));
	list($db_ckpath,$db_ckdomain)=explode("\t",GetCookie('ck_info'));
	Cookie('winduser','',0);
	Cookie('hideid','',0);
	Cookie('lastvisit','',0);
	$pwdcheck = GetCookie('pwdcheck');
	if (is_array($pwdcheck)) {
		foreach ($pwdcheck as $key => $value) {
			Cookie("pwdcheck[$key]",'',0);
		}
	}
	Cookie('ck_info','',0);
	Cookie('msghide','',0,false);
	$windid = $winduid = '';
}
function Loginipwrite($winduid) {
	global $db,$timestamp,$onlineip,$montime;
	$logininfo="$onlineip|$timestamp|6";
	$pwSQL = "monoltime=IF(lastvisit<'$montime',0,monoltime),";
	$pwSQL .= pwSqlSingle(array(
		'lastvisit'	=> $timestamp,
		'thisvisit'	=> $timestamp,
		'onlineip'	=> $logininfo
	));
	$db->update("UPDATE pw_memberdata SET $pwSQL WHERE uid=".pwEscape($winduid));
}
function checkpass($username,$password,$safecv,$lgt=0) {
	global $db,$timestamp,$onlineip,$db_ckpath,$db_ckdomain,$men_uid,$db_ifsafecv,$db_ifpwcache,$db_logintype;
	$str_logintype = '';
	if ($db_logintype) {
		for ($i = 0; $i < 3; $i++) {
			${'logintype_'.$i} = ($db_logintype & pow(2,$i)) ? 1 : 0;
		}
	} else {
		$logintype_0 = 1;
	}
	!${'logintype_'.$lgt} && Showmsg('login_errortype');
	switch (intval($lgt)) {
		case 0:
			$str_logintype = 'username';
			break;
		case 1:
			$str_logintype = 'uid';
			break;
		case 2:
			!preg_match("/^[-a-zA-Z0-9_\.]+@([0-9A-Za-z][0-9A-Za-z-]+\.)+[A-Za-z]{2,5}$/",$username) && Showmsg('illegal_email');
			$str_logintype = 'email';
			break;
		default:
			$str_logintype = 'username';
			break;
	}
	$men_uid = '';
	if (intval($lgt) == 2) {
		$query = $db->query("SELECT m.uid,m.username,m.password,m.safecv,m.groupid,m.memberid,m.yz,m.salt,md.onlineip,md.postnum,md.rvrc,md.money,md.credit,md.currency,md.lastpost,md.onlinetime,md.todaypost,md.monthpost,md.monoltime,md.digests "
				. " FROM pw_members m LEFT JOIN pw_memberdata md ON md.uid=m.uid"
				. " WHERE m.".$str_logintype."=".pwEscape($username)." LIMIT 2");
		$int_querynum = $db->num_rows($query);
		if (!$int_querynum) {
			Showmsg('user_not_exists');
		} elseif ($int_querynum == 1) {
			$men = $db->fetch_array($query);
		} else {
			Showmsg('reg_email_have_same');
		}
	} else {
		$men = $db->get_one("SELECT m.uid,m.username,m.password,m.safecv,m.groupid,m.memberid,m.yz,m.salt,md.onlineip,md.postnum,md.rvrc,md.money,md.credit,md.currency,md.lastpost,md.onlinetime,md.todaypost,md.monthpost"
				. " FROM pw_members m LEFT JOIN pw_memberdata md ON md.uid=m.uid"
				. " WHERE m.".$str_logintype."=".pwEscape($username));
	}
	if ($men) {
		$e_login = explode("|",$men['onlineip']);
		if ($e_login[0] != $onlineip.' *' || ($timestamp-$e_login[1])>600 || $e_login[2]>1 ) {
			$men_uid = $men['uid'];
			$men_pwd = $men['password'];
			$check_pwd = $password;
			$men['yz'] > 2 && Showmsg('login_jihuo');

			if (strlen($men_pwd) == 16) {
				$check_pwd=substr($password,8,16);/*支持 16 位 md5截取密码*/
			}
			if ($men['salt']) {
				$check_pwd = md5($password.$men['salt']);/*molyx密码*/
			}
			
			if ($men_pwd == $check_pwd && (!$db_ifsafecv || $men['safecv'] == $safecv)) {
				if (strlen($men_pwd)==16) {
					$db->update("UPDATE pw_members SET password=".pwEscape($password)."WHERE uid=".pwEscape($men_uid));
				}
				if ($men['salt']) {
					$db->update("UPDATE pw_members SET password='$password', salt = '' WHERE uid='$men_uid'");
				}
				$L_groupid = $men['groupid']=='-1' ? $men['memberid'] : $men['groupid'];
				Cookie("ck_info",$db_ckpath."\t".$db_ckdomain);
			} else {
				global $L_T;
				$L_T = ($timestamp-$e_login[1])>600 ? 5 : $e_login[2];
				$L_T ? $L_T--:$L_T=5;
				$F_login = "$onlineip *|$timestamp|$L_T";
				$db->update("UPDATE pw_memberdata SET onlineip=".pwEscape($F_login)."WHERE uid=".pwEscape($men_uid));
				Showmsg('login_pwd_error');
			}
		} else {
			global $L_T;
			$L_T=600-($timestamp-$e_login[1]);
			Showmsg('login_forbid');
		}
	} else {
		global $errorname;
		$errorname = $username;
		Showmsg('user_not_exists');
	}
	//Start Here会员排行榜
	if ($db_ifpwcache & 1) {
		require_once(R_P.'require/elementupdate.class.php');
		$elementupdate = new ElementUpdate();
		$elementupdate->userSortUpdate($men);
	}
	//End Here
	return array($men_uid,$L_groupid,PwdCode($password));
}
function questcode($question,$customquest,$answer) {
	$question = $question=='-1' ? $customquest : $question;
	return $question ? substr(md5(md5($question).md5($answer)),8,10) : '';
}
?>