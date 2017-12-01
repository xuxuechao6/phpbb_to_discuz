<?php
!defined('R_P') && exit('Forbidden!');

//用户数据
//require_once (S_P.'tmp_credit.php');
if (!$start)
{
    $DDB->query("TRUNCATE TABLE {$pw_prefix}members");
    $DDB->query("TRUNCATE TABLE {$pw_prefix}memberdata");
    $DDB->query("TRUNCATE TABLE {$pw_prefix}administrators");
    $DDB->query("TRUNCATE TABLE {$pw_prefix}membercredit");
    $DDB->update("REPLACE INTO {$pw_prefix}credits (cid,name) VALUES (1,'声望')");
    $DDB->update("REPLACE INTO {$pw_prefix}credits (cid,name) VALUES (2,'财富')");
}
$levelarr = Array(8,9,10,11,12,13,14,15,18,19,20,21,22,23,24,25,26,27,28,29,30);
$head = array();
$query = $SDB->query("SELECT ID,UserName,Pass,Mail,Sex,Birthday,ApplyTime,Prevtime,Userphoto,IP,Homepage,Underwrite,Points,OnlineTime,AnnounceNum,FaceUrl,FaceWidth,FaceHeight,AnnounceTopic,AnnounceGood,UploadNum,LastWriteTime,CachetValue,CharmPoint,ICQ,OICQ,UserLevel,Officer,UserTitle FROM {$source_prefix}User WHERE ID >= $start AND ID < $end");
while ($rt = $SDB->fetch_array($query))
{
    $ID = (int)$rt['ID'];
    $UserName = addslashes($rt['UserName']);
    if (htmlspecialchars($UserName)!=$UserName)
    {
        //$m->MoveNext();
        continue;
    }
    $check_username = $DDB->get_value("SELECT username FROM pw_members WHERE username='$UserName'");
    if($check_username==$UserName){//重名的情况
        $UserName = $UserName.'1';
    }
    if ($rt['FaceUrl'])
    {
        $apre = strtolower(substr($rt['FaceUrl'],0,7));
        if($apre == '../imag')
        {
            $FaceUrl = substr($rt['FaceUrl'], 22).'|3|'.$rt['FaceWidth'].'|'.$rt['FaceHeight'].'||1';
        }
        elseif ($apre == 'http://')
        {
             $FaceUrl = $rt['FaceUrl'].'|2|'.$rt['FaceWidth'].'|'.$rt['FaceHeight'].'|';
        }
        $face = explode("/",$rt['FaceUrl']);
        $head[$face[count($face)-1]] = $rt['ID'];
    }
    else
    {
        $FaceUrl = str_pad($rt['Userphoto'], 4, '0', STR_PAD_LEFT).'.gif|1|||';
    }
    if($rt['Sex'] == '男')
    {
        $Sex = 1;
    }
    elseif ($rt['Sex'] == '女')
    {
        $Sex = 2;
    }
    else
    {
        $Sex = 0;
    }
    $money = $rt['Points'];
    $rvrc = 0;
    $credit = 0;
    $currency = 0;
    $ApplyTime = dt2ut(RestoreTime($rt['ApplyTime']));
    $Birthday = $rt['Birthday'] ? mkbd($rt['Birthday']) : '0000-00-00';
    eval($creditdata);
    $Underwrite = lead_ubb($rt['Underwrite']);
    $signchange = (convert($Underwrite) == $Underwrite) ? '1' : '2';
    $userstatus=($signchange-1)*256+128+1*64+4;//用户位状态设置
    //$DDB->update("REPLACE INTO {$pw_prefix}members (uid,username,password,email,groupid,icon,gender,regdate,signature,oicq,icq,site,bday,yz,userstatus) VALUES (".$rt['ID'].",'".addslashes($UserName)."','".$rt['Pass']."','".addslashes($rt['Mail'])."',-1,'".addslashes($FaceUrl)."','".$Sex."','".$ApplyTime."','".addslashes($Underwrite)."','".addslashes($rt['OICQ'])."','".addslashes($rt['ICQ'])."','".addslashes($rt['Homepage'])."','".$Birthday."',1,'".$userstatus."')");

//就这四种吧 声望 是版主加的。 财富是刷帖子不容易刷到的。_金币是靠发帖等动作赚的。_经验是在线时间
    $memberid = $levelarr[$rt['UserLevel']];
    if($rt['Officer']==4){//管理员
        $groupid=3;
    }elseif($rt['Officer']==3){//网站成员
        $groupid=34;
    }elseif($rt['Officer']==5){//妙音阁
        $groupid=32;
    }elseif($rt['Officer']==1){//退休
        $groupid=31;
    }elseif($rt['Officer']==2){//无耻帮
        $groupid=33;
    }
    $member_sql[] = "(".$rt['ID'].",'".addslashes($UserName)."','".$rt['Pass']."','".addslashes($rt['Mail'])."',-1,{$memberid},'".$rt['UserTitle']."','".addslashes($FaceUrl)."','".$Sex."','".$ApplyTime."','".addslashes($Underwrite)."','".addslashes($rt['OICQ'])."','".addslashes($rt['ICQ'])."','".addslashes($rt['Homepage'])."','".$Birthday."',1,'".$userstatus."')";
    $memberdata_sql[] = "(".$rt['ID'].",".$rt['AnnounceGood'].",".(int)$rt['AnnounceNum'].",".$rvrc.",".$money.",".$credit.",".$currency.",'".dt2ut(RestoreTime($rt['LastWriteTime']))."',".$rt['UploadNum'].",'".$rt['OnlineTime']."')";

    //$DDB->update("REPLACE INTO {$pw_prefix}memberdata (uid,digests,postnum,rvrc,money,credit,currency,lastvisit,uploadnum) VALUES (".$rt['ID'].",".$rt['AnnounceGood'].",".(int)$rt['AnnounceNum'].",".$rvrc.",".$money.",".$credit.",".$currency.",'".dt2ut(RestoreTime($rt['LastWriteTime']))."',".$rt['UploadNum'].")");
    $DDB->update("INSERT INTO {$pw_prefix}membercredit(uid,cid,value) VALUES(".$rt['ID'].",1,".$rt['CachetValue'].")");
    $DDB->update("INSERT INTO {$pw_prefix}membercredit(uid,cid,value) VALUES(".$rt['ID'].",2,".$rt['CharmPoint'].")");
    $s_c++;
    //$m->MoveNext();
}

$member_sql && $DDB->update("REPLACE INTO {$pw_prefix}members (uid,username,password,email,groupid,memberid,honor,icon,gender,regdate,signature,oicq,icq,site,bday,yz,userstatus) VALUES ".implode(",",$member_sql));

$memberdata_sql && $DDB->update("REPLACE INTO {$pw_prefix}memberdata (uid,digests,postnum,rvrc,money,credit,currency,lastvisit,uploadnum,onlinetime) VALUES ".implode(",",$memberdata_sql));

writeover(S_P.'tmp_head.php', "\$_head = ".pw_var_export($head).";", true);
$row = $SDB->get_one("SELECT COUNT(*) AS num FROM {$source_prefix}User WHERE ID >= $end");
echo $row['num'];
if ($row['num'])
{
    refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
}
else
{
    report_log();
    newURL($step);
}