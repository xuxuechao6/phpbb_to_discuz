<?php
define('PRO','1');
define('SCR','login');
require_once('global.php');
if ($db_pptifopen && $db_ppttype == 'client') {
	Showmsg('passport_login');
}
InitGP(array('action','forward'));
!$db_pptifopen && $forward = '';
$pre_url = $pwServer['HTTP_REFERER'] ? $pwServer['HTTP_REFERER'] : $db_bbsurl.'/'.$db_bfn;

if (strpos($pre_url,'login.php') !== false || strpos($pre_url,$db_registerfile) !== false) {
	$pre_url = $db_bfn;
}
!$action && $action = "login";

if ($groupid != 'guest' && $action != 'quit') {
	
	if ($db_pptifopen && $db_ppttype == 'server' && ($db_ppturls || $forward)) {
		$jumpurl = $forward ? $forward : $db_ppturls;
		$forward = $pre_url;
		require_once(R_P.'require/passport_server.php');
	} elseif (empty($_POST)) {
		ObHeader($pre_url);
//		Showmsg('login_have');
	}
}
list(,$loginq)	= explode("\t",$db_qcheck);

if ($action == 'login') {

	if (empty($_POST['step'])) {
		$arr_logintype = array();
		if ($db_logintype) {
			for ($i = 0; $i < 3; $i++) {
				if ($db_logintype & pow(2,$i)) {
					$arr_logintype[] = $i;
				}
			}
		} else {
			$arr_logintype[0] = 0;
		}
		if (GetCookie('o_invite') && $db_modes['o']['ifopen']==1) {
			InitGP(array('jumpurl'));
		} else {
			$jumpurl = $pre_url;
		}
		require_once(R_P.'require/header.php');
		require_once PrintEot('login');footer();

	} else {

		PostCheck(0,$db_gdcheck & 2,$loginq,0);
		require_once(R_P . 'require/checkpass.php');

		InitGP(array('pwuser','pwpwd','question','customquest','answer','cktime','hideid','jumpurl','lgt','keepyear'),'P');

		$jumpurl = str_replace(array('&#61;','&amp;'),array('=','&'),$jumpurl);

		if (!$pwuser || !$pwpwd) {
			Showmsg('login_empty');
		}
		$md5_pwpwd = md5($pwpwd);
		$safecv = $db_ifsafecv ? questcode($question, $customquest, $answer) : '';

		//list($winduid, $groupid, $windpwd, $showmsginfo) = checkpass($pwuser, $md5_pwpwd, $safecv, $lgt);
		$logininfo = checkpass($pwuser, $md5_pwpwd, $safecv, $lgt,$pwpwd);
		if (!is_array($logininfo)) {
			Showmsg($logininfo);
		}
		list($winduid, $groupid, $windpwd, $showmsginfo) = $logininfo;
		
		/*update cache*/
		$_cache = getDatastore();
		$_cache->delete("UID_".$winduid);
		
		if (file_exists(D_P."data/groupdb/group_$groupid.php")) {
			require_once Pcv(D_P."data/groupdb/group_$groupid.php");
		} else {
			require_once(D_P."data/groupdb/group_1.php");
		}
		(int)$keepyear && $cktime = '31536000';
		$cktime != 0 && $cktime += $timestamp;
		Cookie("winduser",StrCode($winduid."\t".$windpwd."\t".$safecv),$cktime);
		Cookie("ck_info",$db_ckpath."\t".$db_ckdomain);
		//Cookie("ucuser",'cc',$cktime);
		Cookie('lastvisit','',0);//��$lastvist����Խ���ע��Ļ�Ա������յ��û�Ա��
		if ($db_autoban) {
			require_once(R_P.'require/autoban.php');
			autoban($winduid);
		}
		($_G['allowhide'] && $hideid) ? Cookie('hideid',"1",$cktime) : Loginipwrite($winduid);
		(empty($jumpurl) || false !== strpos($jumpurl, $regurl)) && $jumpurl = $db_bfn;

		if (GetCookie('o_invite') && $db_modes['o']['ifopen'] == 1) {
			list($o_u,$hash,$app) = explode("\t",GetCookie('o_invite'));
			if (is_numeric($o_u) && strlen($hash) == 18) {
				require_once(R_P.'require/o_invite.php');
			}
		}
		//passport
		if ($db_pptifopen && $db_ppttype == 'server' && ($db_ppturls || $forward)) {
			$tmp = $jumpurl;
			$jumpurl = $forward ? $forward : $db_ppturls;
			$forward = $tmp;
			require_once(R_P.'require/passport_server.php');
		}
		//passport
	
		refreshto($jumpurl,'have_login');
	}
} elseif ($action == 'quit') {

	if (!$db_pptifopen || !$db_pptcmode) {
		checkVerify('loginhash');
	}
	require_once(R_P.'require/checkpass.php');

	if ($groupid == '6') {
		$bandb = $db->get_one("SELECT type FROM pw_banuser WHERE uid=".pwEscape($winduid)." AND fid='0'");
		if ($bandb['type'] == 3) {
			Cookie('force',$winduid);
		}
	}
	Loginout();
	require_once(R_P . 'uc_client/uc_client.php');
	$showmsginfo = uc_user_synlogout();

	//passport
	if ($db_pptifopen && $db_ppttype == 'server' && ($db_ppturls || $forward)) {
		$jumpurl = $forward ? $forward : $db_ppturls;
		$forward = $pre_url;
		require_once(R_P.'require/passport_server.php');
	}
	//passport
	Cookie("jobpop",0);/*jobpop*/

	if (preg_match('/u.php$/i', $pre_url)) {
		$pre_url = $db_bfn;
	}
	refreshto($pre_url,'login_out');/*�˳�url ��Ҫʹ��$pre_url ��Ϊ������޸����������һ��ѭ����ת*/
}
?>