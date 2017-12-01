<?php

!defined('R_P') && exit('Forbidden!');

function dvbbs82_access()
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
	foreach ($_POST['cname'] as $k => $v)
	{
		if (preg_match('~^(userwealth|userep|usercp|userpower|usermoney|userticket)$~is', $_POST['dname'][$k]))
		{
			$expand[] = array(Char_cv($do_chs ? addslashes($chs->Convert(stripslashes($v))) : $v), Char_cv($do_chs ? addslashes($chs->Convert(stripslashes($_POST['cunit'][$k]))) : $_POST['cunit'][$k]), strtolower($_POST['dname'][$k]));
		}
	}
	$expand = pw_var_export($expand);

$info = <<<EOT
<?php
!defined('R_P') && exit('Forbidden!');

\$creditdata = "\\\$rvrc = 10 * intval($rvrcdata);\\\$money = intval($moneydata);\\\$credit = intval($creditdata);\\\$currency = intval($currencydata);";

\$expandCredit = $expand;

?>
EOT;
		writeover(S_P.'tmp_credit.php', $info);
}
function evalexp($expressions)
{
	$field = preg_split('~[+\-\*\/]~is', $expressions);
	preg_match_all('~[+\-\*\/]~is', $expressions, $sign);
	$newexp = '';
	foreach ($field as $k => $v)
	{
		$newexp .= (preg_match('~^(userwealth|userep|usercp|userpower|usermoney|userticket)$~is', $v) ? "(int)\$m->Fields['".$v."']->value" : $v).$sign[0][$k];
	}
	return $newexp;
}

?>