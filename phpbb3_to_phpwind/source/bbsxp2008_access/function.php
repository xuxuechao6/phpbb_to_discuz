<?php

!defined('R_P') && exit('Forbidden!');

function bbsxp2008_access()
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

$info = <<<EOT
<?php
!defined('R_P') && exit('Forbidden!');

\$creditdata = "\\\$rvrc = 10 * intval($rvrcdata);\\\$money = intval($moneydata);\\\$credit = intval($creditdata);\\\$currency = intval($currencydata);";

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
		$newexp .= (preg_match('~^(usermoney|reputation|experience)$~is', $v) ? "(int)\$m->Fields['".$v."']->value" : $v).$sign[0][$k];
	}
	return $newexp;
}

?>