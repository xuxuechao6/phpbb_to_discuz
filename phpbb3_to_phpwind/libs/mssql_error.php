<?php

switch ($errno)
{
	case 'ms1001':
		$tip_msg = '�޷�����MSSQL���������޸�php�����ļ�';
		break;
	case 'ms1002':
		$tip_msg = '�޷����� MS SERVER ���ݿ⣬�������ݿ��û������������Ƿ���ȷ';
		break;
	case 'ms1003':
		$tip_msg = '���ݿⲻ���ڣ��������ݿ�����д�Ƿ���ȷ';
		break;
	case '08S01':
		$tip_msg = '�޷��������ݿ⣬�������ݿ��Ƿ����������ݿ��������ַ�Ƿ���ȷ';
		break;
	case '28000':
		$tip_msg = '�޷��������ݿ⣬�������ݿ��û������������Ƿ���ȷ';
		break;
	case '37000':
		$tip_msg = '���ݿⲻ���ڣ��������ݿ�����д�Ƿ���ȷ';
		break;
	default:
		$tip_msg = 'δ�������';
		break;
}

$error_msg = <<<EOT
<h1>���ݿ����ִ�й����з�����һ������</h1>
<div class="error">
<h2>ϵͳ���صĴ�����Ϣ��</h2>%s
</div><br />
<div class="sql">
<h2>���������SQL��䣺</h2>%s
</div><br />
<div class="tip">
<h2>����ԭ����߿��ܵ��Ŵ�����</h2>$tip_msg
</div>
EOT;

?>