<?php
!defined('R_P') && exit('Forbidden!');

//会员
//禁言会员处理数据初始化
$banusersql = $banuids = array();

//会员
$insertadmin = '';
$_specialgroup = array();
require_once (S_P.'tmp_credit.php');
require_once (S_P.'tmp_uc.php');    //uc表

if (!$start)
{
    $DDB->query("TRUNCATE TABLE {$pw_prefix}members");
    $DDB->query("TRUNCATE TABLE {$pw_prefix}memberdata");
    $DDB->query("TRUNCATE TABLE {$pw_prefix}membercredit");
    $DDB->query("TRUNCATE TABLE {$pw_prefix}memberinfo");
    $DDB->query("TRUNCATE TABLE {$pw_prefix}administrators");
    $DDB->query("TRUNCATE TABLE {$pw_prefix}banuser");
    $DDB->query("TRUNCATE TABLE {$pw_prefix}credits");

    foreach ($expandCredit as $v)//自定义积分
    {
        $DDB->update("INSERT INTO {$pw_prefix}credits (name,unit) VALUES ('".addslashes($v[0])."','".addslashes($v[1])."')");
    }
    if(is_array($expandMember))//自定义用户栏目
    {
        foreach ($expandMember as $v)
        {
            $DDB->update("INSERT INTO {$pw_prefix}customfield (id,title) VALUES ('".addslashes($v[1])."','".addslashes($v[0])."')");
            $DDB->update("ALTER TABLE {$pw_prefix}memberinfo ADD field_".addslashes($v[1])." CHAR(50) ".$DDB->collation()." NOT NULL DEFAULT ''");
        }
    }

    writeover(S_P.'tmp_group.php', "\$_specialgroup = ".pw_var_export(changegroups()).";", true);//更新用户组并保存特殊组到临时文件

    //更改数据库结构
    $addfields = TRUE;
    $query = $DDB->query("SHOW COLUMNS FROM {$pw_prefix}members");
    while ($mc = $DDB->fetch_array($query))
    {
        if (strpos(strtolower($mc['Field']), 'salt') !== FALSE)
        {
            $addfields = FALSE;
            break;
        }
    }
    $addfields && $DDB->update("ALTER TABLE {$pw_prefix}members ADD salt CHAR(6) ".$DDB->collation()." NOT NULL DEFAULT ''");
}
//增加uc
$charset_change = 1;
$UCDB = new mysql($uc_db_host, $uc_db_user, $uc_db_password, $uc_db_name, '');

require_once (S_P.'ubb.php');
require_once (S_P.'tmp_group.php');

    $querysql = '';
    if(is_array($expandMember))//自定义用户栏目
    {
        foreach ($expandMember as $k => $v)
        {
            $querysql .= ",mf.field_".$v[1];
        }
    }
$query = $SDB->query("SELECT m.uid,m.username,m.password,m.secques,m.gender,m.adminid,m.groupid,m.groupexpiry,m.extgroupids,m.regip,m.regdate,m.lastip,m.lastvisit,m.lastactivity,m.lastpost,m.posts,m.digestposts,m.oltime,m.pageviews,m.credits,m.extcredits1,m.extcredits2,m.extcredits3,m.extcredits4,m.extcredits5,m.extcredits6,m.extcredits7,m.extcredits8,m.email,m.bday,m.sigstatus,m.tpp,m.ppp,m.styleid,m.dateformat,m.timeformat,m.pmsound,m.showemail,m.newsletter,m.invisible,m.timeoffset,m.accessmasks,m.editormode,
        mf.nickname,mf.site,mf.alipay,mf.icq,mf.qq,mf.yahoo,mf.msn,mf.taobao,mf.location,mf.customstatus,mf.medals,mf.avatar,mf.avatarwidth,mf.avatarheight,mf.bio,mf.sightml,mf.ignorepm,mf.groupterms,mf.authstr,mf.spacename,mf.buyercredit,mf.sellercredit".$querysql.",
        ol.thismonth, ol.total
        FROM {$source_prefix}members m
        LEFT JOIN {$source_prefix}memberfields mf USING(uid)
        LEFT JOIN {$source_prefix}onlinetime ol USING(uid)
        WHERE m.uid > $start
        ORDER BY uid LIMIT $percount");

while ($m = $SDB->fetch_array($query))
{
    Add_S($m);
    $lastid = $m['uid'];
    if (!$m['uid'] || !$m['username'])
    {
        $f_c++;
        errors_log($m['uid']."\t".$m['username']);
        continue;
    }
    //判断用户是否有新短信
    $newpm = $UCDB->get_value("select count(pmid) from {$uc_db_prefix}pms where msgtoid=".$m['uid']." and new=1");
    if($newpm > 1)$newpm=1;else $newpm=0;

    switch ($m['groupid'])
    {
        case '1'://管理员
            $groupid = '3';
            $insertadmin .= "(".$m['uid'].", '".$m['username']."', 3),";
            break;
        case '2'://总版主
            $groupid = '4';
            $insertadmin .= "(".$m['uid'].", '".$m['username']."', 4),";
            break;
        case '3'://版主
            $groupid = '5';
            $insertadmin .= "(".$m['uid'].", '".$m['username']."', 5),";
            break;
        case '4':
        case '5':
        case '6':
        case '7'://禁止发言
            $groupid = '6';
            break;
        case '8'://未验证会员
            $groupid = '7';
            break;
        default :
            $groupid = $_specialgroup[$m['groupid']] ? $m['groupid'] : '-1';
            break;
    }

    //禁言会员处理
    if($groupid == '6')
    {
        $timestamp=time();
        if ($m['groupexpiry'])
        {	//用户组有效期
            if($m['groupexpiry'] > $timestamp)
            {
                $days = ceil(($m['groupexpiry'] - $timestamp)/86400);
                $banusersql[] = array($m['uid'],0,1,$timestamp,$days,'','');
                $banuids[] = $m['uid'];
            }
        }
        else
        {
            $banusersql[] = array($m['uid'],0,2,$timestamp,0,'','');
            $banuids[] = $m['uid'];
        }
    }

    //自定义积分 处理
    eval($creditdata);
    $expandCreditSQL = '';
    if($expandCredit)//自定义积分
    {
        foreach ($expandCredit as $k => $v)
        {
            $expandCreditSQL .= '('.$m['uid'].','.($k + 1).','.(int)($m[$v[2]]).'),';
        }
        $expandCreditSQL && $DDB->update("INSERT INTO {$pw_prefix}membercredit (uid, cid, value) VALUES ".substr($expandCreditSQL, 0, -1));
    }

    //自定义用户栏目 处理
    $expandMemberSQL1 = '';
    $expandMemberSQL2 = '';
    if($expandMember)//自定义积分
    {
        foreach ($expandMember as $k => $v)
        {
            $expandMemberSQL1 .= ",field_".$v[1];
            $expandMemberSQL2 .= ",'".$m["field_$v[1]"]."'";
        }
        $expandMemberSQL1 && $DDB->update("INSERT INTO {$pw_prefix}memberinfo (uid".$expandMemberSQL1.") VALUES (".$m['uid'].$expandMemberSQL2.")");
    }

    $timedf = ($m['timeoffset'] == '9999') ? '0' : $m['timeoffset'];//时差设定
    list($introduce,) = explode("\t", $m['bio']); //bio 自我介绍
    $editor = ($m['editormode'] == '1') ? '1' : '0';//编辑器模式
    $userface = $banpm = '';
    if ($m['avatar'])//头像
    {
        $avatarpre = substr($m['avatar'], 0, 7);
        switch ($avatarpre)
        {
            case 'http://':
                $userface = $m['avatar'].'|2|'.$m['avatarwidth'].'|'.$m['avatarheight'];
                break;
            case 'images/':
                $userface = substr($m['avatar'],strrpos($m['avatar'],'/')+1).'|1';
                break;
            case 'customa':
                $userface = substr($m['avatar'],strrpos($m['avatar'],'/')+1).'|3|'.$m['avatarwidth'].'|'.$m['avatarheight'];
                break;
        }
    }
    $m['sightml'] = addslashes(html2bbcode(stripslashes($m['sightml'])));//个性签名
    $signchange = ($m['sightml'] == convert($m['sightml'])) ? 1 : 2;
    $userstatus = ($signchange-1)*256 + 128 + $m['showemail']*64 + 4;//用户位状态设置
    //$medals = $m['medals'] ? str_replace("\t", ',', $m['medals']) : '';

    $medals = '';//勋章add by zhaojun 100317
    if($m['medals']){
        $medals = '';
        $medalarr = explode("\t",$m['medals']);
        if($medalarr){
            foreach($medalarr as $v){
                if(strpos($v,"|")!=false){
                    /*原来的勋章是15|1279036800这样的话，新的就获取不到了就会出问题了*/
                    $v = substr($v,0,strpos($v,"|"));
                }
                $medals .= $v.',';
                if($v!=''){
                    $medaluser[] = "(".$m['uid'].",".$v.")";
                }
            }
            $medals = substr($medals,0,-1);
        }
    }

    //密码
    $uc = $UCDB->get_one("SELECT m.password,m.salt,mf.blacklist FROM {$uc_db_prefix}members m LEFT JOIN {$uc_db_prefix}memberfields mf USING (uid) WHERE m.uid=".$m['uid']);
    $uc['blacklist'] && $uc['blacklist'] != '{ALL}' && $banpm = $uc['blacklist'];

    //密码
    $m['ignorepm'] && $m['ignorepm'] != '{ALL}' && $banpm = $m['ignorepm'];

    $membersql[]  = "(".$m['uid'].",'".$m['username']."','".$uc['password']."','".$m['email']."',".$groupid.",'".addslashes($m['extgroupids'])."','".$userface."','".$m['gender']."',".$m['regdate'].",'".$m['sightml']."','".$introduce."','".$m['qq']."','".$m['icq']."','".$m['msn']."','".$m['yahoo']."','".$m['site']."','".$m['location']."','".$m['customstatus']."','".$m['bday']."','".$timedf."','".$m['tpp']."','".$m['ppp']."',".$newpm.",'$banpm','$medals','".$userstatus."','".$uc['salt']."')";
    $memdatasql[] = "(".$m['uid'].",".$m['posts'].",".$m['digestposts'].",".$rvrc.",".$money.",".$credit.",".$currency.",'".$m['lastvisit']."','".$m['lastactivity']."','".$m['lastpost']."','".intval($m['total']*60)."','".intval($m['thismonth']*60)."')";
    $s_c++;
}

//禁言会员处理
if ($banusersql)
{
    $DDB->update("REPLACE INTO {$pw_prefix}banuser (uid,fid,type,startdate,days,admin,reason) VALUES ".pwSqlMulti($banusersql));
}
if ($banuids)
{
    $DDB->update("UPDATE {$pw_prefix}members SET groupid='6' WHERE uid IN (".pwImplode($banuids).") AND groupid!=6");
}

//会员处理
if($membersql)
{
    $membersqlstr = implode(",",$membersql);
    $DDB->update("REPLACE INTO {$pw_prefix}members (uid,username,password,email,groupid,groups,icon,gender,regdate,signature,introduce,oicq,icq,msn,yahoo,site,location,honor,bday,timedf,t_num,p_num,newpm,banpm,medals,userstatus,salt) VALUES $membersqlstr ");
}

if($memdatasql)
{
    $memdatastr = implode(",",$memdatasql);
    $DDB->update("REPLACE INTO {$pw_prefix}memberdata (uid,postnum,digests,rvrc,money,credit,currency,lastvisit,thisvisit,lastpost,onlinetime,monoltime) VALUES $memdatastr ");
}

//系统组
if($insertadmin){
    $DDB->update("REPLACE INTO {$pw_prefix}administrators (uid,username,groupid) VALUES ".substr($insertadmin, 0, -1));
}

//勋章
if($medaluser)
{
    $medaluserstr = implode(",",$medaluser);
    $DDB->update("REPLACE INTO {$pw_prefix}medaluser (uid,mid) VALUES $medaluserstr ");
}

$maxid = $SDB->get_value("SELECT max(uid) FROM {$source_prefix}members");
echo '最大id',$maxid.'<br>最后id',$lastid;
if ($lastid < $maxid)
{
    refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c.'&medal='.$medal);
}
else
{
    report_log('&medal='.$medal);
    newURL($step);
    exit();
}