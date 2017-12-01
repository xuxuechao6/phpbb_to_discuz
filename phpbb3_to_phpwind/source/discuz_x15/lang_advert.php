<?php
!defined('R_P') && exit('Forbidden!');

//$DDB->UPDATE("DROP TABLE {$pw_prefix}advert;");
$sql = "CREATE TABLE IF NOT EXISTS {$pw_prefix}advert (`id` int(10) unsigned NOT NULL auto_increment,`type` tinyint(1) NOT NULL default '0',`uid` int(10) unsigned NOT NULL default '0',`ckey` varchar(32) NOT NULL,`stime` int(10) unsigned NOT NULL default '0',`etime` int(10) unsigned NOT NULL default '0',`ifshow` tinyint(1) NOT NULL default '0',`orderby` tinyint(1) NOT NULL default '0',`descrip` varchar(255) NOT NULL,`config` text NOT NULL,PRIMARY KEY  (`id`)) ";

if ($DDB->server_info() > '4.1') {
    $sql .= "ENGINE=MyISAM".($dest_charset ? " DEFAULT CHARSET=".$dest_charset : '');
} else {
    $sql .= "TYPE=MyISAM";
}
$sql .= "  AUTO_INCREMENT=100";
$DDB->query($sql);

$arrSQL = array(
	"REPLACE INTO pw_advert VALUES(1, 0, 0, 'Site.Header', 0, 0, 1, 0, 'ͷ�����~	~��ʾ��ҳ���ͷ����һ����ͼƬ��flash��ʽ��ʾ���������ʱϵͳ�����ѡȡһ����ʾ', 'a:1:{s:7:\"display\";s:4:\"rand\";}');",

	"REPLACE INTO pw_advert VALUES(2, 0, 0, 'Site.Footer', 0, 0, 1, 0, '�ײ����~	~��ʾ��ҳ��ĵײ���һ����ͼƬ��flash��ʽ��ʾ���������ʱϵͳ�����ѡȡһ����ʾ', 'a:1:{s:7:\"display\";s:4:\"rand\";}');",

	"REPLACE INTO pw_advert VALUES(3, 0, 0, 'Site.NavBanner1', 0, 0, 1, 0, '����ͨ��[1]~	~��ʾ�������������棬һ����ͼƬ��flash��ʽ��ʾ���������ʱϵͳ�����ѡȡһ����ʾ', 'a:1:{s:7:\"display\";s:4:\"rand\";}');",

	"REPLACE INTO pw_advert VALUES(4, 0, 0, 'Site.NavBanner2', 0, 0, 1, 0, '����ͨ��[2]~	~��ʾ��ͷ��ͨ�����[1]λ�õ�����,��ͨ�����[1]��һ����ʾ,һ��ΪͼƬ���', 'a:1:{s:7:\"display\";s:4:\"rand\";}');",

	"REPLACE INTO pw_advert VALUES(5, 0, 0, 'Site.PopupNotice', 0, 0, 1, 0, '�������[����]~	~��ҳ�����½��Ը����Ĳ㵯����ʾ���˹��������Ҫ����������ش��ڲ���', 'a:1:{s:7:\"display\";s:4:\"rand\";}');",

	"REPLACE INTO pw_advert VALUES(6, 0, 0, 'Site.FloatRand', 0, 0, 1, 0, 'Ư�����[���]~	~�Ը�����ʽ��ҳ�������Ư���Ĺ��', 'a:1:{s:7:\"display\";s:4:\"rand\";}');",

	"REPLACE INTO pw_advert VALUES(7, 0, 0, 'Site.FloatLeft', 0, 0, 1, 0, 'Ư�����[��]~	~�Ը�����ʽ��ҳ�����Ư���Ĺ�棬�׳ƶ������[��]', 'a:1:{s:7:\"display\";s:4:\"rand\";}');",

	"REPLACE INTO pw_advert VALUES(8, 0, 0, 'Site.FloatRight', 0, 0, 1, 0, 'Ư�����[��]~	~�Ը�����ʽ��ҳ���ұ�Ư���Ĺ�棬�׳ƶ������[��]', 'a:1:{s:7:\"display\";s:4:\"rand\";}');",

	"REPLACE INTO pw_advert VALUES(9, 0, 0, 'Mode.TextIndex', 0, 0, 1, 0, '���ֹ��[��̳��ҳ]~	~��ʾ��ҳ��ĵ������棬һ�������ַ�ʽ��ʾ��ÿ��������棬����������������ʾ', 'a:1:{s:7:\"display\";s:3:\"all\";}');",

	"REPLACE INTO pw_advert VALUES(10, 0, 0, 'Mode.Forum.TextRead', 0, 0, 1, 0, '���ֹ��[����ҳ]~	~��ʾ��ҳ��ĵ������棬һ�������ַ�ʽ��ʾ��ÿ��������棬����������������ʾ', 'a:1:{s:7:\"display\";s:3:\"all\";}');",

	"REPLACE INTO pw_advert VALUES(11, 0, 0, 'Mode.Forum.TextThread', 0, 0, 1, 0, '���ֹ��[����ҳ]~	~��ʾ��ҳ��ĵ������棬һ�������ַ�ʽ��ʾ��ÿ��������棬����������������ʾ', 'a:1:{s:7:\"display\";s:3:\"all\";}');",

	"REPLACE INTO pw_advert VALUES(12, 0, 0, 'Mode.Forum.Layer.TidRight', 0, 0, 1, 0, '¥����[�����Ҳ�]~	~�����������Ҳ࣬һ����ͼƬ��������ʾ������������ʱϵͳ�����ѡȡһ����ʾ', 'a:1:{s:7:\"display\";s:4:\"rand\";}');",

	"REPLACE INTO pw_advert VALUES(13, 0, 0, 'Mode.Forum.Layer.TidDown', 0, 0, 1, 0, '¥����[�����·�]~	~�����������·���һ����ͼƬ��������ʾ������������ʱϵͳ�����ѡȡһ����ʾ', 'a:1:{s:7:\"display\";s:4:\"rand\";}');",

	"REPLACE INTO pw_advert VALUES(14, 0, 0, 'Mode.Forum.Layer.TidUp', 0, 0, 1, 0, '¥����[�����Ϸ�]~	~�����������Ϸ���һ����ͼƬ��������ʾ������������ʱϵͳ�����ѡȡһ����ʾ', 'a:1:{s:7:\"display\";s:4:\"rand\";}');",

	"REPLACE INTO pw_advert VALUES(15, 0, 0, 'Mode.Forum.Layer.TidAmong', 0, 0, 1, 0, '¥����[¥���м�]~	~����������¥��֮�䣬һ����ͼƬ��������ʾ������������ʱϵͳ�����ѡȡһ����ʾ', 'a:1:{s:7:\"display\";s:4:\"rand\";}');",

	"REPLACE INTO pw_advert VALUES(16, 0, 0, 'Mode.Layer.Index', 0, 0, 1, 0, '��̳��ҳ�����~	~��������ҳ�����֮�䣬һ����ͼƬ��������ʾ������������ʱϵͳ�����ѡȡһ����ʾ', 'a:1:{s:7:\"display\";s:4:\"rand\";}');",

	"REPLACE INTO pw_advert VALUES(17, 0, 0, 'Mode.area.IndexMain', 0, 0, 1, 0, '�Ż���ҳ�м�~	~�Ż���ҳѭ�����������м���Ҫ���λ,һ��ΪͼƬ���', 'a:1:{s:7:\"display\";s:4:\"rand\";}');",

	"REPLACE INTO pw_advert VALUES(18, 0, 0, 'Mode.Layer.area.IndexLoop', 0, 0, 1, 0, '�Ż���ҳѭ��~	~�Ż���ҳ�м�ѭ��ģ��֮��Ĺ��Ͷ�ţ�һ��ΪͼƬ���', 'a:1:{s:7:\"display\";s:4:\"rand\";}');",

	"REPLACE INTO pw_advert VALUES(19, 0, 0, 'Mode.Layer.area.IndexSide', 0, 0, 1, 0, '�Ż���ҳ���~	~�Ż���ҳ���ÿ��һ��ģ�鶼��һ�����λ��ʾ,λ��˳���Ӧѡ���¥����.һ��ΪСͼƬ���', 'a:1:{s:7:\"display\";s:4:\"rand\";}');",

	"REPLACE INTO pw_advert VALUES(20, 0, 0, 'Mode.Forum.area.CateMain', 0, 0, 1, 0, '�Ż�Ƶ���м�~	~�Ż�Ƶ������������м���Ҫ���λ,һ��ΪͼƬ���', 'a:1:{s:7:\"display\";s:4:\"rand\";}');",

	"REPLACE INTO pw_advert VALUES(21, 0, 0, 'Mode.Forum.Layer.area.CateLoop', 0, 0, 1, 0, '�Ż�Ƶ��ѭ��~	~�Ż�Ƶ���м�ѭ��ģ��֮��Ĺ��Ͷ�ţ�һ��ΪͼƬ���', 'a:1:{s:7:\"display\";s:4:\"rand\";}');",

	"REPLACE INTO pw_advert VALUES(22, 0, 0, 'Mode.Forum.Layer.area.CateSide', 0, 0, 1, 0, '�Ż�Ƶ�����~	~�Ż�Ƶ�����ÿ��һ��ģ�鶼��һ�����λ��ʾ,λ��˳���Ӧѡ���¥����.һ��ΪСͼƬ���', 'a:1:{s:7:\"display\";s:4:\"rand\";}');",

	"REPLACE INTO pw_advert VALUES(23, 0, 0, 'Mode.Forum.Layer.area.ThreadTop', 0, 0, 1, 0, '�Ż������б�ҳ����~	~�����б�ҳ�Ż�ģʽ���ʱ�����Ϸ��Ĺ��λ', 'a:1:{s:7:\"display\";s:4:\"rand\";}');",

	"REPLACE INTO pw_advert VALUES(24, 0, 0, 'Mode.Forum.Layer.area.ThreadBtm', 0, 0, 1, 0, '�Ż������б�ҳ����~	~�����б�ҳ�Ż�ģʽ���ʱ�����·��Ĺ��λ', 'a:1:{s:7:\"display\";s:4:\"rand\";}');",

	"REPLACE INTO pw_advert VALUES(25, 0, 0, 'Mode.Forum.Layer.area.ReadTop', 0, 0, 1, 0, '�Ż���������ҳ����~	~��������ҳ�Ż�ģʽ���ʱ�����Ϸ��Ĺ��λ', 'a:1:{s:7:\"display\";s:4:\"rand\";}');",

	"REPLACE INTO pw_advert VALUES(26, 0, 0, 'Mode.Forum.Layer.area.ReadBtm', 0, 0, 1, 0, '�Ż���������ҳ����~	~��������ҳ�Ż�ģʽ���ʱ�����·��Ĺ��λ', 'a:1:{s:7:\"display\";s:4:\"rand\";}');",
);

foreach ($arrSQL as $sql) {
	if (trim($sql)) {
		$DDB->update($sql);
	}
}

$arrUpdate = array(
	'Site.NavBanner'		=> 'Site.NavBanner1',
	'Mode.Layer.TidRight'	=> 'Mode.Forum.Layer.TidRight',
	'Mode.Layer.TidDown'	=> 'Mode.Forum.Layer.TidDown',
	'Mode.Layer.TidUp'		=> 'Mode.Forum.Layer.TidUp',
	'Mode.Layer.TidAmong'	=> 'Mode.Forum.Layer.TidAmong'
);

foreach ($arrUpdate as $key=>$value) {
	$DDB->update("UPDATE {$pw_prefix}advert SET ckey=".pwEscape($value,false)."WHERE ckey=".pwEscape($key,false));
}

?>