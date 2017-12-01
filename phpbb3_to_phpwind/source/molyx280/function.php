<?php

!defined('R_P') && exit('Forbidden!');

function molyx280()
{
	$credit_array = array('rvrc');
	$rvrcdata = $moneydata = $creditdata = $currencydata = $do_chs = '';
	foreach ($credit_array as $v)
	{
		${$v.'data'} = addcslashes($_POST[$v], '\\\"$');
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

\$creditdata = "\\\$rvrcdata = '$rvrcdata';";

?>
EOT;
		writeover(S_P.'tmp_credit.php', $info);
}
?>