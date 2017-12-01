<?php

!defined('R_P') && exit('Forbidden!');

function leobbs()
{
	$leo_host = str_replace(array('\\\\', '.'), array('/',''), $_POST['leo_host']);
	$leo_host = (substr($leo_host,-1) == '/') ? $leo_host : $leo_host.'/';

	$lm_dir = $leo_host.'cgi-bin/'.$_POST['leo_member'].'/';
	if (!is_dir($lm_dir) || !is_readable($lm_dir))
	{
		Showmsg('用户数据文件目录不存在或者不可读，请确认配置正确！');
	}
	$li_dir = $leo_host.'cgi-bin/'.$_POST['leo_message'].'/';
	if (!is_dir($li_dir) || !is_readable($li_dir))
	{
		Showmsg('用户短信文件目录不存在或者不可读，请确认配置正确！');
	}
	$lt_dir = $leo_host.'non-cgi/'.$_POST['leo_attachment'].'/';
	if (!is_dir($lt_dir) || !is_readable($lt_dir))
	{
		Showmsg('附件目录不存在或者不可读，请确认配置正确！');
	}
	$percount = $_POST['percount'] ? (int)$_POST['percount'] : 1000;
	if (!$_POST['pw_db_host'] || !$_POST['pw_db_user'] || !$_POST['pw_db_name'] || !$_POST['pw_prefix']) ShowMsg('请完整填写 PHPWIND 论坛数据库信息！');
	if (!in_array($_POST['dest_charset'], array('gbk','utf8','big5'))) ShowMsg('您选择的论坛编码格式不正确！');
	$DDB = new mysql($_POST['pw_db_host'], $_POST['pw_db_user'], $_POST['pw_db_password'], $_POST['pw_db_name'], '');
	if ($DDB->server_info() > '4.1')
	{
		$rt = $DDB->get_one("SHOW FULL FIELDS FROM ".$_POST['pw_prefix']."config");
		$dcharset = explode('_', $rt['Collation']);
		(strtolower($dcharset[0]) != $_POST['dest_charset']) && Showmsg('您选择的论坛编码格式不正确！');
	}
	$dbinfo = <<<EOT
<?php

!defined('R_P') && exit('Forbidden!');

define('L_B', "{$leo_host}cgi-bin/");
define('L_B_N', "{$leo_host}non-cgi/");

\$percount = $percount;

\$lm_dir = "$lm_dir";
\$li_dir = "$li_dir";
\$lf_dir = "{$leo_host}cgi-bin/memfriend/";
\$la_dir = "{$leo_host}cgi-bin/forum";
\$lt_dir = "$lt_dir";
\$source_charset = "latin1";

\$pw_db_host = "{$_POST['pw_db_host']}";
\$pw_db_user = "{$_POST['pw_db_user']}";
\$pw_db_password = "{$_POST['pw_db_password']}";
\$pw_db_name = "{$_POST['pw_db_name']}";
\$pw_prefix = "{$_POST['pw_prefix']}";
\$dest_charset = "{$_POST['dest_charset']}";

?>
EOT;
	writeover(S_P.'tmp_sql.php', $dbinfo);
}

?>