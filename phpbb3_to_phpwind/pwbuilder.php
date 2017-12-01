<?php
/**
*
*  Copyright (c) 2003-06  PHPWind.net. All rights reserved.
*  Support : http://www.phpwind.net
*  This software is the proprietary information of PHPWind.com.
*
*/

error_reporting(E_ERROR | E_WARNING | E_PARSE);
//error_reporting(E_ALL);

@set_time_limit(0);
@ignore_user_abort(TRUE);
@set_magic_quotes_runtime(0);

$pw_version = '8.7';
$timestamp = time();
//结尾带斜杠
define('R_P', str_replace('\\', '/', dirname(__FILE__).'/'));
file_exists(R_P.'builder.lock') && exit('转换程序已经锁定，请先删除builder.lock文件。');

extract($_POST);
extract($_GET);

ini_set('date.timezone','Asia/Shanghai');
function_exists('ob_gzhandler') ? ob_start('ob_gzhandler') : ob_start();

require_once(R_P.'libs/functions.php');
require_once(R_P.'libs/mysql.class.php');

$basename  = 'http://' . $_SERVER['HTTP_HOST'] . (isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME']);
$reportURL = 'manual.phpwind.net';

define('S_P', R_P.'source/'.$dbtype.'/');		//程序路径
define('T_P', R_P.'template/'.$dbtype.'/');		//模板路径

$charset_change = 0;							//字符集切换
$s_c   = (int)$s_c;								//成功转换
$f_c   = (int)$f_c;								//忽略转换
$step  = (int)$step;							//转换步骤
$start = (int)$start;                           //起始ID
$lastid = $start;
switch ($action)
{
	case 'compile':
		require_once (S_P.'config.php');
        if(in_array($dbtype,array('discuz60','discuz61','discuz70','discuz71','discuz72','discuz_x1'))){
		    @include_once (S_P.'tmp_sql.php');
		    @include_once (S_P.'tmp_uc.php');
		    @include_once (S_P.'tmp_uchome.php');
        }
		if ($a == 'config')
		{
			call_user_func($compile_type.'_config', $dbtype);
		}
		elseif ($a == 'convert')
		{
			call_user_func('step_convert', $dbtype);
		}
		if (!N_writable(S_P)) Showmsg('./source/'.$dbtype.' 目录不可写，请设定目录权限为 777！');
		require_once (T_P.$dbtype.'_pw.htm');
		break;
	case 'build':
		require_once (S_P.'config.php');
		require_once (S_P.'tmp_sql.php');
		$DDB = new mysql($pw_db_host, $pw_db_user, $pw_db_password, $pw_db_name,$dest_charset);
		$cpage = $basename . '?action=build&dbtype='.$dbtype;
		$end = $start + $percount;
		!$start && init_error();
		require_once (S_P.'do.php');
		break;
	case 'finish':
		$hash = reportHash();
		$spendtime = spendtime();
		require_once (R_P.'libs/convert.php');
		require_once (S_P.'tmp_report.php');
		require_once (T_P.'finish.htm');
		writeover(R_P.'builder.lock','');
		break;
	default:
		require_once (R_P.'template/pwbuilder.htm');
}

footer();

?>