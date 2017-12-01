<?php

!defined('R_P') && exit('Forbidden!');

function shuziren()
{
$info = <<<EOT
<?php
!defined('R_P') && exit('Forbidden!');

\$money = '$_POST[money]';
\$category_id = intval($_POST[category_id]);
\$cate_fid = intval($_POST[cate_fid]);

?>
EOT;
		writeover(S_P.'tmp_info.php', $info);
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

?>