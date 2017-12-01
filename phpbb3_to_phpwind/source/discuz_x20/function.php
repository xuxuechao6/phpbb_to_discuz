<?php

!defined('R_P') && exit('Forbidden!');

function discuz_x20()
{
	$credit_array = array('rvrc','money','credit','currency');
	$rvrcdata = $moneydata = $creditdata = $currencydata = $do_chs = '';
	foreach ($credit_array as $v)
	{
		${$v.'data'} = addcslashes(evalexp($_POST[$v]), '\\\"$');
		!${$v.'data'} && ${$v.'data'} = 0;
	}
	//expand
	$expand = array();
	if ($_POST['dest_charset'] != 'gbk')
	{
		require_once R_P.'libs/chinese.php';
		$chs = new Chinese('gbk', $_POST['dest_charset']);
		$do_chs = 1;
	}

    //����
	$creditname_array = array('rvrcname','moneyname','creditname','currencyname');
    $pingcredit = array();
    $_POST['rvrc'] && $pingcredit[substr($_POST['rvrc'],10)] = $_POST['rvrcname'];
    $_POST['money'] && $pingcredit[substr($_POST['money'],10)] = $_POST['moneyname'];
    $_POST['credit'] && $pingcredit[substr($_POST['credit'],10)] = $_POST['creditname'];
    $_POST['currency'] && $pingcredit[substr($_POST['currency'],10)] = $_POST['currencyname'];

	foreach ($_POST['cname'] as $k => $v)
	{
		if (preg_match('~^(credits|extcredits\d)$~', $_POST['dname'][$k]))
		{
			$expand[] = array(Char_cv($do_chs ? addslashes($chs->Convert(stripslashes($v))) : $v), Char_cv($do_chs ? addslashes($chs->Convert(stripslashes($_POST['cunit'][$k]))) : $_POST['cunit'][$k]), $_POST['dname'][$k]);

            //����
            $pingcredit[substr($_POST['dname'][$k],10)] = $v;
		}
	}
	$expand = pw_var_export($expand);
    //����
	$pingcredit = pw_var_export($pingcredit);

    //member
	foreach ($_POST['mname'] as $k => $v)
	{
		//if (preg_match('~^(credits|extcredits\d)$~', $_POST['dname'][$k]))
		//{
        if(!empty($v)){
			$expandmember[] = array(Char_cv($do_chs ? addslashes($chs->Convert(stripslashes($v))) : $v), $_POST['dname2'][$k]);
        }
		//}
	}
	$expandmember = pw_var_export($expandmember);

$info = <<<EOT
<?php
!defined('R_P') && exit('Forbidden!');

\$creditdata = "\\\$rvrc = 10 * intval($rvrcdata);\\\$money = intval($moneydata);\\\$credit = intval($creditdata);\\\$currency = intval($currencydata);";

\$expandCredit = $expand;

\$expandMember = $expandmember;

\$pingcredit = $pingcredit;

?>
EOT;
	writeover(S_P.'tmp_credit.php', $info);

	!$_POST['uc_db_host'] && $_POST['uc_db_host'] = $_POST['pw_db_host'];
	!$_POST['uc_db_user'] && $_POST['uc_db_user'] = $_POST['pw_db_user'];
	!$_POST['uc_db_name'] && $_POST['uc_db_name'] = $_POST['pw_db_name'];
	!$_POST['uc_db_password'] && $_POST['uc_db_password'] = $_POST['pw_db_password'];

	if (!$_POST['uc_db_prefix'] || !$_POST['uc_db_host'] || !$_POST['uc_db_user'] || !$_POST['uc_db_name'])
	{
		ShowMsg('��������д UCenter ���ݿ���Ϣ��');
	}

    if($_POST['uc_db_host'] && $_POST['uc_db_user'] && $_POST['pw_db_name'] && $_POST['uc_db_prefix'])
    {
	    $UCDB = new mysql($_POST['uc_db_host'], $_POST['uc_db_user'], $_POST['uc_db_password'], $_POST['uc_db_name'], '');
	    if ($UCDB->server_info() > '4.1')
	    {
		   $rt = $UCDB->get_one("SHOW FULL FIELDS FROM ".$_POST['uc_db_prefix']."settings");
		   $dcharset = explode('_', $rt['Collation']);
		   //(strtolower($dcharset[0]) != $_POST['source_charset']) && Showmsg('UCenter �� Discuz �ı��벻һ�£�');
	    }

        $info = <<<EOT
<?php

!defined('R_P') && exit('Forbidden!');

\$uc_db_host = "{$_POST['uc_db_host']}";
\$uc_db_user = "{$_POST['uc_db_user']}";
\$uc_db_password = "{$_POST['uc_db_password']}";
\$uc_db_name = "{$_POST['uc_db_name']}";
\$uc_db_prefix = "{$_POST['uc_db_prefix']}";

?>
EOT;
		writeover(S_P.'tmp_uc.php', $info);
    }
}

function evalexp($expressions)
{
	$field = preg_split('~[+\-\*\/]~is', $expressions);
	preg_match_all('~[+\-\*\/]~is', $expressions, $sign);
	$newexp = '';
	foreach ($field as $k => $v)
	{
		$newexp .= (preg_match('~^(credits|extcredits\d)$~', $v) ? "(int)\$m['".$v."']" : $v).$sign[0][$k];
	}
	return $newexp;
}

function dz_url($url)
{
	if ($url && ord(substr($url, -1)) == 47)
	{
		$url = substr($url,0, -1);
	}
	return $url;
}

function dz_icp($icp)
{
	return strip_tags($icp);
}

function dz_siteopen($siteopen)
{
	return $siteopen ^ 1;
}

function dz_banname($banname)
{
	return preg_replace('~(\n|\r|\n\r|\r\n)~', ',', str_replace('*','',$banname));
}

function dz_regcheck($regcheck)
{
	return $regcheck ? 1 : 0;
}

function dz_regemail($regemail)
{
	return preg_replace('~(\n|\r|\n\r|\r\n)~', ',', str_replace('@','',$regemail));
}
?>