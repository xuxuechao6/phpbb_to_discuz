<?php

!defined('R_P') && exit('Forbidden!');

function leobbs()
{
	$leo_host = str_replace(array('\\\\', '.'), array('/',''), $_POST['leo_host']);
	$leo_host = (substr($leo_host,-1) == '/') ? $leo_host : $leo_host.'/';

	$lm_dir = $leo_host.'cgi-bin/'.$_POST['leo_member'].'/';
	if (!is_dir($lm_dir) || !is_readable($lm_dir))
	{
		Showmsg('�û������ļ�Ŀ¼�����ڻ��߲��ɶ�����ȷ��������ȷ��');
	}
	$li_dir = $leo_host.'cgi-bin/'.$_POST['leo_message'].'/';
	if (!is_dir($li_dir) || !is_readable($li_dir))
	{
		Showmsg('�û������ļ�Ŀ¼�����ڻ��߲��ɶ�����ȷ��������ȷ��');
	}
	$lt_dir = $leo_host.'non-cgi/'.$_POST['leo_attachment'].'/';
	if (!is_dir($lt_dir) || !is_readable($lt_dir))
	{
		Showmsg('����Ŀ¼�����ڻ��߲��ɶ�����ȷ��������ȷ��');
	}
	$percount = $_POST['percount'] ? (int)$_POST['percount'] : 1000;
	if (!$_POST['pw_db_host'] || !$_POST['pw_db_user'] || !$_POST['pw_db_name'] || !$_POST['pw_prefix']) ShowMsg('��������д PHPWIND ��̳���ݿ���Ϣ��');
	if (!in_array($_POST['dest_charset'], array('gbk','utf8','big5'))) ShowMsg('��ѡ�����̳�����ʽ����ȷ��');
	$DDB = new mysql($_POST['pw_db_host'], $_POST['pw_db_user'], $_POST['pw_db_password'], $_POST['pw_db_name'], '');
	if ($DDB->server_info() > '4.1')
	{
		$rt = $DDB->get_one("SHOW FULL FIELDS FROM ".$_POST['pw_prefix']."config");
		$dcharset = explode('_', $rt['Collation']);
		(strtolower($dcharset[0]) != $_POST['dest_charset']) && Showmsg('��ѡ�����̳�����ʽ����ȷ��');
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