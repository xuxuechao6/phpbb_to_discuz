<?php

switch ($errno) {
	case '1005':
		$mysql_error = '������ʧ��';
		break;
	case '1006':
		$mysql_error = '�������ݿ�ʧ��';
		break;
	case '1007':
	case '1008':
		$mysql_error = '���ݿⲻ���ڣ�ɾ�����ݿ�ʧ��';
		break;
	case '1016':
		$mysql_error = '�޷��������ļ�������ʹ�� phpmyadmin �����޸�';
		break;
	case '1017':
		$mysql_error = '�������Ƿ��ػ����������ݿ��ļ���';
		break;
	case '1020':
		$mysql_error = '��¼�ѱ������û��޸ģ���ǰ�޷�ʹ��';
		break;
	case '1021':
		$mysql_error = '�ؼ����ظ������ļ�¼ʧ��';
		break;
	case '1037':
		$mysql_error = 'ϵͳ�ڴ治�㣬���������ݿ������������';
		break;
	case '1040':
		$mysql_error = '���ݿ��ѵ�����������������ڷ��������е�ʱ������ת������';
		break;
	case '1041':
		$mysql_error = 'ϵͳ�ڴ治�㣬���ڷ��������е�ʱ������ת������';
		break;
	case '1042':
		$mysql_error = '��Ч��������������ȷ�������ݿ����';
		break;
	case '1043':
		$mysql_error = '��Ч���ӣ�����ȷ�������ݿ����';
		break;
	case '1044':
		$mysql_error = '��ǰ�û�û�з������ݿ��Ȩ�ޣ��������ݿ��û������������Ƿ���ȷ';
		break;
	case '1045':
		$mysql_error = '�޷��������ݿ⣬�������ݿ��û������������Ƿ���ȷ';
		break;
	case '1046':
		$mysql_error = '���ݱ����ڣ�����Դ�����ת�������Ƿ��Ӧ';
		break;
	case '1048':
		$mysql_error = '�ֶβ���Ϊ��';
		break;
	case '1049':
		$mysql_error = '���ݿⲻ���ڣ��������ݿ�����д�Ƿ���ȷ';
		break;
	case '1050':
		$mysql_error = '���ݱ��Ѵ���';
		break;
	case '1051':
		$mysql_error = '���ݱ�����';
		break;
	case '1054':
		$mysql_error = '���ݿ��ֶβ�����';
		break;
	case '1060':
	case '1062':
		$mysql_error = '�ֶ�ֵ�ظ������ʧ��';
		break;
	case '1064':
		$mysql_error = 'SQLִ�з�������1.���ݳ��������Ͳ�ƥ�䣻2.���ݿ��¼�ظ�';
		break;
	case '1065':
		$mysql_error = '��Ч��SQL��䣬SQL���Ϊ��';
		break;
	case '1081':
		$mysql_error = '���ܽ���Socket����';
		break;
	case '1114':
		$mysql_error = '���ݱ����������������κμ�¼';
		break;
	case '1115':
		$mysql_error = '���õ��ַ����� MySQL ��û��֧��';
		break;
	case '1129':
		$mysql_error = '���ݿ�����쳣�����������ݿ�';
		break;
	case '1130':
		$mysql_error = '�������ݿ�ʧ�ܣ�û���������ݿ��Ȩ��';
		break;
	case '1133':
		$mysql_error = '���ݿ��û�������';
		break;
	case '1135':
		$mysql_error = '1��������ϵͳ�ڴ������2������ϵͳ�𻵻�ϵͳ�𻵡�����ϵ�ռ��̽��';
		break;
	case '1136':
		$mysql_error = '�ֶθ�����ƥ��';
		break;
	case '1141':
		$mysql_error = '��ǰ�û���Ȩ�������ݿ�';
		break;
	case '1142':
		$mysql_error = '��ǰ�û���Ȩ�������ݱ�';
		break;
	case '1143':
		$mysql_error = '��ǰ�û���Ȩ�������ݱ��е��ֶ�';
		break;
	case '1146':
		$mysql_error = '���ݱ�����';
		break;
	case '1149':
		$mysql_error = 'SQL����﷨����';
		break;
	case '1158':
	case '1159':
	case '1160':
	case '1161':
		$mysql_error = '�������������������״��';
		break;
	case '1169':
		$mysql_error = '�ֶ�ֵ�ظ������¼�¼ʧ��';
		break;
	case '1177':
		$mysql_error = '�����ݱ�ʧ��';
		break;
	case '1227':
		$mysql_error = 'Ȩ�޲��㣬����Ȩ���д˲���';
		break;
	case '1267':
		$mysql_error = '���Ϸ��Ļ���ַ���';
		break;
	case '2002':
		$mysql_error = '�������˿ڲ��ԣ�����ѯ�ռ�����ȷ�Ķ˿�';
		break;
	case '2003':
	case '2013':
		$mysql_error = '�޷��������ݿ⣬�������ݿ��Ƿ����������ݿ��������ַ�Ƿ���ȷ';
		break;
	case '2005':
		$mysql_error = '���ݿ������������';
		break;
	case '10048':
		$mysql_error = '������ˢ��,�Ͻ�ˢ��̫�죬������my.ini�ļ����޸����������';
		break;
	case '10055':
		$mysql_error = 'û�л���ռ������';
		break;
	case '10061':
		$mysql_error = '���ݿ������δ�����ɹ��������� my.ini ������';
		break;
	default:
		$mysql_error = 'δ����SQLִ�д�����������<a href="http://www.phpwind.net/thread-htm-fid-94.html" target="_blank">����</a>���BUG��лл���ĺ�����';
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
<h2>����ԭ����߿��ܵ��Ŵ�����</h2>{$mysql_error}
</div>
EOT;

?>