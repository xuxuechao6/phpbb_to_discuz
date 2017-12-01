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
	//板块
	$DDB->query("TRUNCATE TABLE {$pw_prefix}forums");
	$DDB->query("TRUNCATE TABLE {$pw_prefix}forumdata");
	$DDB->query("TRUNCATE TABLE {$pw_prefix}forumsextra");
	$DDB->query("ALTER TABLE {$pw_prefix}forums CHANGE descrip descrip TEXT ".$DDB->collation()." NOT NULL");
	$DDB->query("ALTER TABLE {$pw_prefix}forums CHANGE keywords keywords TEXT ".$DDB->collation()." NOT NULL");
	$DDB->query("TRUNCATE TABLE {$pw_prefix}topictype");//75新增主题分类表
}

require_once S_P.'tmp_grelation.php';//用户组
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
while($f = $SDB->fetch_array($query)){//版块信息
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
            $typearray[$f['fid']][$kk] = $topictypeid;//用新的
            $order++;
        }
        $t_type = rtrim($t_type); //主题分类
    }
    getupadmin($f['fid'], $upadmin);//把该版块的上级管理员账号传给upadmin
    
    //pw_forums 表数据
    $pw_forums = array(
	    'fid' 			=>	$f['fid'],	//板块id
	    'fup' 			=>	$f['fup'],	//上级板块id
	    'ifsub' 		=>	$f['type'] == 'sub' ? 1 : 0,	//是否为子板块
	    'childid' 		=>	getIfHasChild($catedb,$fid),	//此板块是否有下级子板块  //返回1说明是有子版块
	    'type' 			=>	$f['type'],	//类型（'category'-分类 'forum'-板块 'sub'-子板块)
	    'logo' 			=>	$f['icon'],	//板块logo
	    'name' 			=>	$f['name'],	//板块名称
	    'descrip' 		=>	$f['description'],	//板块介绍
	    'dirname' 		=>	'',	//版块二级目录设置(分类)
	    'keywords' 		=>	$f['keywords'],	//版块关键字
	    'vieworder' 	=>	$f['displayorder'],	//板块排序
	    'forumadmin' 	=>	$f['moderators'] ? ','.str_replace("\t",',', $f['moderators']).',' : '', 	//版主名单
	    'fupadmin' 		=>	$upadmin,	//版块上级版主
	    'style' 		=>	'',	//板块风格
	    'across' 		=>	$f['forumcolumns'],	//板快排列方式(默认0表示列排，大于0的整数表示横排)
	    'allowhtm' 		=>	0,	//是否静态页面
	    'allowhide' 	=>	1,	//是否允许发隐藏贴
	    'allowsell' 	=>	'1',	//是否允许发出售帖
	    'allowtype' 	=>	'31',	//允许发表的主题类型
	    'copyctrl' 		=>	$f['jammer'],//是否使用水印
	    'allowencode' 	=>	0,	//是否允许加密贴
	    'password' 		=>	$f['password'] ? md5($f['password']) : '',	//板块密码（md5）
	    'viewsub' 		=>	$f['simple'] & 1,	//是否显示子版
	    'allowvisit' 	=>	allow_group_str($f['viewperm']),	//允许浏览版块用户组
	    'allowread' 	=>	allow_group_str($f['viewperm']),	//允许浏览帖子用户组
	    'allowpost' 	=>	allow_group_str($f['postperm']),	//允许发表主题用户组
	    'allowrp' 		=>	allow_group_str($f['replyperm']),	//允许发表回复用户组
	    'allowdownload' =>	allow_group_str($f['getattachperm']),	//允许下载附件用户组
	    'allowupload' 	=>	allow_group_str($f['postattachperm']),	//允许上传附件用户组
	    'f_type' 		=>	$f['type'] == 'category' ? '' : 'forum',	//板块类型（加密，开放。。。）
	    'forumsell' 	=>	'',//版块出售积分类型
	    'f_check' 		=>	($f['modnewposts'] == '2') ? '3' : (int)$f['modnewposts'],//发帖审核
	    't_type'        =>	$t_type,   //主题分类
	    'cms' 			=>	0,//文章系统分类id
	    'ifhide' 		=>	1,//是否隐藏
	    'showsub' 		=>	0,//是否在首页显示子版块
    );
   !empty($pw_forums) && $DDB->update("INSERT INTO {$pw_prefix}forums SET ".pwSqlSingle($pw_forums));

    //pw_forumdata 表数据
    $pw_forumdata = array(
	    'fid' 		=>	$f['fid'],//板快id
	    'tpost' 	=>	$f['todayposts'],//今日发帖数
	    'topic' 	=>	$f['threads'],//板块中的主题
	    'article' 	=>	$f['posts'],//帖子个数
	    'subtopic' 	=>	0,//子板块主题
	    'top1' 		=>	0,//本板块置顶数统计
	    'top2' 		=>	0,//分类置顶和总置顶数统计
	    'aid' 		=>	'',//单个公告ID
	    'aidcache' 	=>	'',//公告缓存更新时间
	    'aids' 		=>	'',//多个公告ID
	    'lastpost' 	=>	$f['lastpost'] ? getLastpost($f['lastpost']) : '',//最后一帖信息
    );
    !empty($pw_forumdata) && $DDB->update("INSERT INTO {$pw_prefix}forumdata SET ".pwSqlSingle($pw_forumdata));

    //pw_forumsextra 表数据
    $arr_forumset = array('addtpctype' => $addtpctype);//是否在标题前面加上主题分类名称
    $pw_forumsextra = array(
    	'fid'		=>	$f['fid'], //板快id
	    'creditset'	=>	'',        //后台板块管理版块积分设置
	    'forumset' 	=>	addslashes(serialize($arr_forumset)), //TODO 后台板块管理基本资料设置
	    'commend'  	=>	'',
    );
    !empty($pw_forumsextra) && $DDB->update("INSERT INTO {$pw_prefix}forumsextra SET ".pwSqlSingle($pw_forumsextra));
    $s_c++;
}
writeover(S_P.'tmp_ttype.php', "\$_ttype = ".pw_var_export($typearray).";", true);
report_log();
newURL($step);
?>