<?php
!defined('R_P') && exit('Forbidden!');

//板块
$DDB->query("TRUNCATE TABLE {$pw_prefix}forums");
$DDB->query("TRUNCATE TABLE {$pw_prefix}forumdata");
$DDB->query("TRUNCATE TABLE {$pw_prefix}forumsextra");
$DDB->query("ALTER TABLE {$pw_prefix}forums CHANGE descrip descrip TEXT ".$DDB->collation()." NOT NULL");
$DDB->query("ALTER TABLE {$pw_prefix}forums CHANGE keywords keywords TEXT ".$DDB->collation()." NOT NULL");
$DDB->query("TRUNCATE TABLE {$pw_prefix}topictype");//75新增主题分类表

require_once S_P.'tmp_grelation.php';//用户组
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

while($f = $SDB->fetch_array($query))//版块信息
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
            //$typearray[$f['fid']][$kk] = $i++;//原来去除
            $t_type .= $vv."\t";
            $pw_topictype['fid'] = $f['fid'];
            $pw_topictype['name'] = $vv;
            $pw_topictype['vieworder'] = $order;
            $DDB->update("INSERT INTO {$pw_prefix}topictype SET ".pwSqlSingle($pw_topictype));
            $topictypeid = $DDB->insert_id();
            $typearray[$f['fid']][$kk] = $topictypeid;//用新的
            $order++;
        }
        $t_type = rtrim($t_type); //主题分类
    }
    getupadmin($f['fid'], $upadmin);//把该版块的上级管理员账号传给upadmin
    //pw_forums 表数据
    $pw_forums['fid'] 			= $f['fid'];	//板块id
    $pw_forums['fup'] 			= $f['fup'];	//上级板块id
    $pw_forums['ifsub'] 		= $f['type'] == 'sub' ? 1 : 0;	//是否为子板块
    $pw_forums['childid'] 		= getIfHasChild($catedb,$fid);	//此板块是否有下级子板块  //返回1说明是有子版块
    $pw_forums['type'] 			= $f['type'];	//类型（'category'-分类 'forum'-板块 'sub'-子板块)
    $pw_forums['logo'] 			= $f['icon'];	//板块logo
    $pw_forums['name'] 			= $f['name'];	//板块名称
    $pw_forums['descrip'] 		= $f['description'];	//板块介绍
    $pw_forums['dirname'] 		= '';	//版块二级目录设置(分类)
    $pw_forums['keywords'] 		= $f['keywords'];	//版块关键字
    $pw_forums['vieworder'] 	= $f['displayorder'];	//板块排序
    $pw_forums['forumadmin'] 	= $f['moderators'] ? ','.str_replace("\t",',', $f['moderators']).',' : ''; 	//版主名单
    $pw_forums['fupadmin'] 		= $upadmin;	//版块上级版主
    $pw_forums['style'] 		= '';	//板块风格
    $pw_forums['across'] 		= $f['forumcolumns'];	//板快排列方式(默认0表示列排，大于0的整数表示横排)
    $pw_forums['allowhtm'] 		= 0;	//是否静态页面
    $pw_forums['allowhide'] 	= 1;	//是否允许发隐藏贴
    $pw_forums['allowsell'] 	= '1';	//是否允许发出售帖
    $pw_forums['allowtype'] 	= '31';	//允许发表的主题类型
    $pw_forums['copyctrl'] 		= $f['jammer'];//是否使用水印
    $pw_forums['allowencode'] 	= 0;	//是否允许加密贴
    $pw_forums['password'] 		= $f['password'] ? md5($f['password']) : '';	//板块密码（md5）
    $pw_forums['viewsub'] 		= $f['simple'] & 1;	//是否显示子版
    $pw_forums['allowvisit'] 	= allow_group_str($f['viewperm']);	//允许浏览版块用户组
    $pw_forums['allowread'] 	= allow_group_str($f['viewperm']);	//允许浏览帖子用户组
    $pw_forums['allowpost'] 	= allow_group_str($f['postperm']);	//允许发表主题用户组
    $pw_forums['allowrp'] 		= allow_group_str($f['replyperm']);	//允许发表回复用户组
    $pw_forums['allowdownload'] = allow_group_str($f['getattachperm']);	//允许下载附件用户组
    $pw_forums['allowupload'] 	= allow_group_str($f['postattachperm']);	//允许上传附件用户组
    $pw_forums['f_type'] 		= $f['type'] == 'category' ? '' : 'forum';	//板块类型（加密，开放。。。）
    $pw_forums['forumsell'] 	= '';//版块出售积分类型
    $pw_forums['f_check'] 		= ($f['modnewposts'] == '2') ? '3' : (int)$f['modnewposts'];//发帖审核
    $pw_forums['t_type']        = $t_type;   //主题分类
    $pw_forums['cms'] 			= 0;//文章系统分类id
    $pw_forums['ifhide'] 		= 1;//是否隐藏
    $pw_forums['showsub'] 		= 0;//是否在首页显示子版块
    //$pw_forums['forumtype'] 	= '';//社区模式下分类页面版块的展示类型//75里面把这个字段去除了

    $DDB->update("INSERT INTO {$pw_prefix}forums SET ".pwSqlSingle($pw_forums));

    //pw_forumdata 表数据
    $pw_forumdata['fid'] 		= $f['fid'];//板快id
    $pw_forumdata['tpost'] 		= $f['todayposts'];//今日发帖数
    $pw_forumdata['topic'] 		= $f['threads'];//板块中的主题
    $pw_forumdata['article'] 	= $f['posts'];//帖子个数
    $pw_forumdata['subtopic'] 	= 0;//子板块主题
    $pw_forumdata['top1'] 		= 0;//本板块置顶数统计
    $pw_forumdata['top2'] 		= 0;//分类置顶和总置顶数统计
    $pw_forumdata['aid'] 		= '';//单个公告ID
    $pw_forumdata['aidcache'] 	= '';//公告缓存更新时间
    $pw_forumdata['aids'] 		= '';//多个公告ID
    $pw_forumdata['lastpost'] 	= $f['lastpost'] ? getLastpost($f['lastpost']) : '';//最后一帖信息

    $DDB->update("INSERT INTO {$pw_prefix}forumdata SET ".pwSqlSingle($pw_forumdata));

    //pw_forumsextra 表数据
    $pw_forumsextra['fid']         	= $f['fid']; //板快id
    $pw_forumsextra['creditset']	= '';        //后台板块管理版块积分设置

    $arr_forumset['addtpctype'] = $addtpctype; //是否在标题前面加上主题分类名称
    $pw_forumsextra['forumset'] = addslashes(serialize($arr_forumset)); //TODO 后台板块管理基本资料设置
    $pw_forumsextra['commend']  = '';

    $DDB->update("INSERT INTO {$pw_prefix}forumsextra SET ".pwSqlSingle($pw_forumsextra));

    $s_c++;
}
writeover(S_P.'tmp_ttype.php', "\$_ttype = ".pw_var_export($typearray).";", true);
report_log();
newURL($step);
