<?php

switch ($errno)
{
	case 'ms1001':
		$tip_msg = '无法操作MSSQL函数，请修改php配置文件';
		break;
	case 'ms1002':
		$tip_msg = '无法连接 MS SERVER 数据库，请检查数据库用户名或者密码是否正确';
		break;
	case 'ms1003':
		$tip_msg = '数据库不存在，请检查数据库名填写是否正确';
		break;
	case '08S01':
		$tip_msg = '无法连接数据库，请检查数据库是否启动，数据库服务器地址是否正确';
		break;
	case '28000':
		$tip_msg = '无法连接数据库，请检查数据库用户名或者密码是否正确';
		break;
	case '37000':
		$tip_msg = '数据库不存在，请检查数据库名填写是否正确';
		break;
	default:
		$tip_msg = '未定义错误';
		break;
}

$error_msg = <<<EOT
<h1>数据库语句执行过程中发生了一个错误</h1>
<div class="error">
<h2>系统返回的错误信息：</h2>%s
</div><br />
<div class="sql">
<h2>发生错误的SQL语句：</h2>%s
</div><br />
<div class="tip">
<h2>错误原因或者可能的排错方法：</h2>$tip_msg
</div>
EOT;

?>