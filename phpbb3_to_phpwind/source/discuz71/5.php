<?php
!defined('R_P') && exit('Forbidden!');

//���
$DDB->query("TRUNCATE TABLE {$pw_prefix}forums");
$DDB->query("TRUNCATE TABLE {$pw_prefix}forumdata");
$DDB->query("TRUNCATE TABLE {$pw_prefix}forumsextra");
$DDB->query("ALTER TABLE {$pw_prefix}forums CHANGE descrip descrip TEXT ".$DDB->collation()." NOT NULL");
$DDB->query("ALTER TABLE {$pw_prefix}forums CHANGE keywords keywords TEXT ".$DDB->collation()." NOT NULL");
$DDB->query("TRUNCATE TABLE {$pw_prefix}topictype");//75������������

require_once S_P.'tmp_grelation.php';//�û���
$catedb = $insertforumsextra = $typearray = array();
$fright = array('viewperm'=>'allowvisit','postperm'=>'allowpost','replyperm'=>'allowrp',
'postattachperm'=>'allowupload','getattachperm'=>'allowdownload');
$insertforums = $insertforumdata = $forumsextra = '';
$query = $SDB->query("SELECT f.fid,f.fup,f.type,f.name,f.status,f.displayorder,f.styleid,f.threads,f.posts,f.todayposts,f.lastpost,f.allowsmilies,f.allowhtml,
f.allowbbcode,f.allowimgcode,f.allowmediacode,f.allowanonymous,f.allowshare,f.allowpostspecial,f.allowspecialonly,f.alloweditrules,f.recyclebin,f.modnewposts,
f.jammer,f.disablewatermark,f.inheritedmod,f.autoclose,f.forumcolumns,f.threadcaches,f.alloweditpost,f.simple,f.modworks,f.allowtag,fd.description,fd.password,
fd.icon,fd.postcredits,fd.replycredits,fd.getattachcredits,fd.postattachcredits,fd.digestcredits,fd.redirect,fd.attachextensions,fd.formulaperm,fd.moderators,
fd.rules,fd.threadtypes,fd.viewperm,fd.postperm,fd.replyperm,fd.getattachperm,fd.postattachperm,fd.keywords,fd.supe_pushsetting,fd.modrecommend,fd.tradetypes,
fd.typemodels FROM {$source_prefix}forums f LEFT JOIN {$source_prefix}forumfields fd USING(fid)");

while($f = $SDB->fetch_array($query))//�����Ϣ
{
    $catedb[$f['fid']] = $f;
}

Add_S($catedb);

foreach($catedb as $fid => $f)
{
    $addtpctype  = '';
    $t_type      = '';
    if ($f['fid'] == $f['fup'])
    {
        $f['fup'] = 0;
    }

    if ('group' == $f['type'])
    {
        $f['type'] = 'category';
    }

    if ($f['threadtypes'])
    {
        $threadtypes = unserialize(stripcslashes($f['threadtypes']));
        $addtpctype  = (int)$threadtypes['prefix'];
        //$i = 1;
        $t_type .= ($threadtypes['required'] ? '2' : '1')."\t";
        $order = 0;
        foreach ($threadtypes['types'] as $kk => $vv)
        {
            //$typearray[$f['fid']][$kk] = $i++;//ԭ��ȥ��
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
    $pw_forums['fid'] 			= $f['fid'];	//���id
    $pw_forums['fup'] 			= $f['fup'];	//�ϼ����id
    $pw_forums['ifsub'] 		= $f['type'] == 'sub' ? 1 : 0;	//�Ƿ�Ϊ�Ӱ��
    $pw_forums['childid'] 		= getIfHasChild($catedb,$fid);	//�˰���Ƿ����¼��Ӱ��  //����1˵�������Ӱ��
    $pw_forums['type'] 			= $f['type'];	//���ͣ�'category'-���� 'forum'-��� 'sub'-�Ӱ��)
    $pw_forums['logo'] 			= $f['icon'];	//���logo
    $pw_forums['name'] 			= $f['name'];	//�������
    $pw_forums['descrip'] 		= $f['description'];	//������
    $pw_forums['dirname'] 		= '';	//������Ŀ¼����(����)
    $pw_forums['keywords'] 		= $f['keywords'];	//���ؼ���
    $pw_forums['vieworder'] 	= $f['displayorder'];	//�������
    $pw_forums['forumadmin'] 	= $f['moderators'] ? ','.str_replace("\t",',', $f['moderators']).',' : ''; 	//��������
    $pw_forums['fupadmin'] 		= $upadmin;	//����ϼ�����
    $pw_forums['style'] 		= '';	//�����
    $pw_forums['across'] 		= $f['forumcolumns'];	//������з�ʽ(Ĭ��0��ʾ���ţ�����0��������ʾ����)
    $pw_forums['allowhtm'] 		= 0;	//�Ƿ�̬ҳ��
    $pw_forums['allowhide'] 	= 1;	//�Ƿ�����������
    $pw_forums['allowsell'] 	= '1';	//�Ƿ�����������
    $pw_forums['allowtype'] 	= '31';	//���������������
    $pw_forums['copyctrl'] 		= $f['jammer'];//�Ƿ�ʹ��ˮӡ
    $pw_forums['allowencode'] 	= 0;	//�Ƿ����������
    $pw_forums['password'] 		= $f['password'] ? md5($f['password']) : '';	//������루md5��
    $pw_forums['viewsub'] 		= $f['simple'] & 1;	//�Ƿ���ʾ�Ӱ�
    $pw_forums['allowvisit'] 	= allow_group_str($f['viewperm']);	//�����������û���
    $pw_forums['allowread'] 	= allow_group_str($f['viewperm']);	//������������û���
    $pw_forums['allowpost'] 	= allow_group_str($f['postperm']);	//�����������û���
    $pw_forums['allowrp'] 		= allow_group_str($f['replyperm']);	//������ظ��û���
    $pw_forums['allowdownload'] = allow_group_str($f['getattachperm']);	//�������ظ����û���
    $pw_forums['allowupload'] 	= allow_group_str($f['postattachperm']);	//�����ϴ������û���
    $pw_forums['f_type'] 		= $f['type'] == 'category' ? '' : 'forum';	//������ͣ����ܣ����š�������
    $pw_forums['forumsell'] 	= '';//�����ۻ�������
    $pw_forums['f_check'] 		= ($f['modnewposts'] == '2') ? '3' : (int)$f['modnewposts'];//�������
    $pw_forums['t_type']        = $t_type;   //�������
    $pw_forums['cms'] 			= 0;//����ϵͳ����id
    $pw_forums['ifhide'] 		= 1;//�Ƿ�����
    $pw_forums['showsub'] 		= 0;//�Ƿ�����ҳ��ʾ�Ӱ��
    //$pw_forums['forumtype'] 	= '';//����ģʽ�·���ҳ�����չʾ����//75���������ֶ�ȥ����

    $DDB->update("INSERT INTO {$pw_prefix}forums SET ".pwSqlSingle($pw_forums));

    //pw_forumdata ������
    $pw_forumdata['fid'] 		= $f['fid'];//���id
    $pw_forumdata['tpost'] 		= $f['todayposts'];//���շ�����
    $pw_forumdata['topic'] 		= $f['threads'];//����е�����
    $pw_forumdata['article'] 	= $f['posts'];//���Ӹ���
    $pw_forumdata['subtopic'] 	= 0;//�Ӱ������
    $pw_forumdata['top1'] 		= 0;//������ö���ͳ��
    $pw_forumdata['top2'] 		= 0;//�����ö������ö���ͳ��
    $pw_forumdata['aid'] 		= '';//��������ID
    $pw_forumdata['aidcache'] 	= '';//���滺�����ʱ��
    $pw_forumdata['aids'] 		= '';//�������ID
    $pw_forumdata['lastpost'] 	= $f['lastpost'] ? getLastpost($f['lastpost']) : '';//���һ����Ϣ

    $DDB->update("INSERT INTO {$pw_prefix}forumdata SET ".pwSqlSingle($pw_forumdata));

    //pw_forumsextra ������
    $pw_forumsextra['fid']         	= $f['fid']; //���id
    $pw_forumsextra['creditset']	= '';        //��̨���������������

    $arr_forumset['addtpctype'] = $addtpctype; //�Ƿ��ڱ���ǰ����������������
    $pw_forumsextra['forumset'] = addslashes(serialize($arr_forumset)); //TODO ��̨�����������������
    $pw_forumsextra['commend']  = '';

    $DDB->update("INSERT INTO {$pw_prefix}forumsextra SET ".pwSqlSingle($pw_forumsextra));

    $s_c++;
}
writeover(S_P.'tmp_ttype.php', "\$_ttype = ".pw_var_export($typearray).";", true);
report_log();
newURL($step);
