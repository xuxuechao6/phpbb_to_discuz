<?php
/**
*
*  Copyright (c) 2003-06  PHPWind.net. All rights reserved.
*  Support : http://www.phpwind.net
*  This software is the proprietary information of PHPWind.com.
*
*/

!defined('R_P') && exit('Forbidden!');

if(empty($start)){
	//���
	$DDB->query("TRUNCATE TABLE {$pw_prefix}forums");
	$DDB->query("TRUNCATE TABLE {$pw_prefix}forumdata");
	$DDB->query("TRUNCATE TABLE {$pw_prefix}forumsextra");
	$DDB->query("ALTER TABLE {$pw_prefix}forums CHANGE descrip descrip TEXT ".$DDB->collation()." NOT NULL");
	$DDB->query("ALTER TABLE {$pw_prefix}forums CHANGE keywords keywords TEXT ".$DDB->collation()." NOT NULL");
	$DDB->query("TRUNCATE TABLE {$pw_prefix}topictype");//75������������
}

require_once S_P.'tmp_grelation.php';//�û���
$catedb = $insertforumsextra = $typearray = array();

$fright = array(
			'viewperm'		=>	'allowvisit',
			'postperm'		=>	'allowpost',
			'replyperm'		=>	'allowrp',
			'postattachperm'=>	'allowupload',
			'getattachperm'	=>	'allowdownload'
);
$insertforums = $insertforumdata = $forumsextra = '';

$query = $SDB->query("SELECT f.fid,f.fup,f.type,f.name,f.status,f.displayorder,f.styleid,f.threads,f.posts,
					  f.todayposts,f.lastpost,f.allowsmilies,f.allowhtml,f.allowbbcode,f.allowimgcode,
					  f.allowmediacode,f.allowanonymous,f.allowshare,f.allowpostspecial,f.allowspecialonly,
					  f.alloweditrules,f.recyclebin,f.modnewposts,f.jammer,f.disablewatermark,f.inheritedmod,
					  f.autoclose,f.forumcolumns,f.threadcaches,f.alloweditpost,f.simple,f.modworks,
					  f.allowtag,fd.description,fd.password,
					  fd.icon,fd.postcredits,fd.replycredits,fd.getattachcredits,fd.postattachcredits,fd.digestcredits,fd.redirect,fd.attachextensions,fd.formulaperm,fd.moderators,
					  fd.rules,fd.threadtypes,fd.viewperm,fd.postperm,fd.replyperm,fd.getattachperm,fd.postattachperm,fd.keywords,fd.supe_pushsetting,fd.modrecommend,fd.tradetypes,
					  fd.typemodels 
					  FROM {$source_prefix}forums f 
					  LEFT JOIN {$source_prefix}forumfields fd USING(fid)");
while($f = $SDB->fetch_array($query)){//�����Ϣ
    $catedb[$f['fid']] = $f;
}
$SDB->free_result($query);

Add_S($catedb);
foreach($catedb as $fid => $f){
    $addtpctype  = '';
    $t_type      = '';
    $pw_forums = $pw_forumdata = $pw_forumsextra = array();
    if ($f['fid'] == $f['fup']){
        $f['fup'] = 0;
    }
    if ('group' == $f['type']){
        $f['type'] = 'category';
    }
    if ($f['threadtypes']){
        $threadtypes = unserialize(stripcslashes($f['threadtypes']));
        $addtpctype  = (int)$threadtypes['prefix'];
        $t_type .= ($threadtypes['required'] ? '2' : '1')."\t";
        $order = 0;
        foreach ($threadtypes['types'] as $kk => $vv){
            $t_type .= $vv."\t";
            $pw_topictype['fid'] = $f['fid'];
            $pw_topictype['name'] = $vv;
            $pw_topictype['vieworder'] = $order;
            $DDB->update("INSERT INTO {$pw_prefix}topictype SET ".pwSqlSingle($pw_topictype));
            $topictypeid = $DDB->insert_id();
            $typearray[$f['fid']][$kk] = $topictypeid;//���µ�
            $order++;
        }
        $t_type = rtrim($t_type); //�������
    }
    getupadmin($f['fid'], $upadmin);//�Ѹð����ϼ�����Ա�˺Ŵ���upadmin
    
    //pw_forums ������
    $pw_forums = array(
	    'fid' 			=>	$f['fid'],	//���id
	    'fup' 			=>	$f['fup'],	//�ϼ����id
	    'ifsub' 		=>	$f['type'] == 'sub' ? 1 : 0,	//�Ƿ�Ϊ�Ӱ��
	    'childid' 		=>	getIfHasChild($catedb,$fid),	//�˰���Ƿ����¼��Ӱ��  //����1˵�������Ӱ��
	    'type' 			=>	$f['type'],	//���ͣ�'category'-���� 'forum'-��� 'sub'-�Ӱ��)
	    'logo' 			=>	$f['icon'],	//���logo
	    'name' 			=>	$f['name'],	//�������
	    'descrip' 		=>	$f['description'],	//������
	    'dirname' 		=>	'',	//������Ŀ¼����(����)
	    'keywords' 		=>	$f['keywords'],	//���ؼ���
	    'vieworder' 	=>	$f['displayorder'],	//�������
	    'forumadmin' 	=>	$f['moderators'] ? ','.str_replace("\t",',', $f['moderators']).',' : '', 	//��������
	    'fupadmin' 		=>	$upadmin,	//����ϼ�����
	    'style' 		=>	'',	//�����
	    'across' 		=>	$f['forumcolumns'],	//������з�ʽ(Ĭ��0��ʾ���ţ�����0��������ʾ����)
	    'allowhtm' 		=>	0,	//�Ƿ�̬ҳ��
	    'allowhide' 	=>	1,	//�Ƿ�����������
	    'allowsell' 	=>	'1',	//�Ƿ�����������
	    'allowtype' 	=>	'31',	//���������������
	    'copyctrl' 		=>	$f['jammer'],//�Ƿ�ʹ��ˮӡ
	    'allowencode' 	=>	0,	//�Ƿ����������
	    'password' 		=>	$f['password'] ? md5($f['password']) : '',	//������루md5��
	    'viewsub' 		=>	$f['simple'] & 1,	//�Ƿ���ʾ�Ӱ�
	    'allowvisit' 	=>	allow_group_str($f['viewperm']),	//�����������û���
	    'allowread' 	=>	allow_group_str($f['viewperm']),	//������������û���
	    'allowpost' 	=>	allow_group_str($f['postperm']),	//�����������û���
	    'allowrp' 		=>	allow_group_str($f['replyperm']),	//������ظ��û���
	    'allowdownload' =>	allow_group_str($f['getattachperm']),	//�������ظ����û���
	    'allowupload' 	=>	allow_group_str($f['postattachperm']),	//�����ϴ������û���
	    'f_type' 		=>	$f['type'] == 'category' ? '' : 'forum',	//������ͣ����ܣ����š�������
	    'forumsell' 	=>	'',//�����ۻ�������
	    'f_check' 		=>	($f['modnewposts'] == '2') ? '3' : (int)$f['modnewposts'],//�������
	    't_type'        =>	$t_type,   //�������
	    'cms' 			=>	0,//����ϵͳ����id
	    'ifhide' 		=>	1,//�Ƿ�����
	    'showsub' 		=>	0,//�Ƿ�����ҳ��ʾ�Ӱ��
    );
   !empty($pw_forums) && $DDB->update("INSERT INTO {$pw_prefix}forums SET ".pwSqlSingle($pw_forums));

    //pw_forumdata ������
    $pw_forumdata = array(
	    'fid' 		=>	$f['fid'],//���id
	    'tpost' 	=>	$f['todayposts'],//���շ�����
	    'topic' 	=>	$f['threads'],//����е�����
	    'article' 	=>	$f['posts'],//���Ӹ���
	    'subtopic' 	=>	0,//�Ӱ������
	    'top1' 		=>	0,//������ö���ͳ��
	    'top2' 		=>	0,//�����ö������ö���ͳ��
	    'aid' 		=>	'',//��������ID
	    'aidcache' 	=>	'',//���滺�����ʱ��
	    'aids' 		=>	'',//�������ID
	    'lastpost' 	=>	$f['lastpost'] ? getLastpost($f['lastpost']) : '',//���һ����Ϣ
    );
    !empty($pw_forumdata) && $DDB->update("INSERT INTO {$pw_prefix}forumdata SET ".pwSqlSingle($pw_forumdata));

    //pw_forumsextra ������
    $arr_forumset = array('addtpctype' => $addtpctype);//�Ƿ��ڱ���ǰ����������������
    $pw_forumsextra = array(
    	'fid'		=>	$f['fid'], //���id
	    'creditset'	=>	'',        //��̨���������������
	    'forumset' 	=>	addslashes(serialize($arr_forumset)), //TODO ��̨�����������������
	    'commend'  	=>	'',
    );
    !empty($pw_forumsextra) && $DDB->update("INSERT INTO {$pw_prefix}forumsextra SET ".pwSqlSingle($pw_forumsextra));
    $s_c++;
}
writeover(S_P.'tmp_ttype.php', "\$_ttype = ".pw_var_export($typearray).";", true);
report_log();
newURL($step);
?>