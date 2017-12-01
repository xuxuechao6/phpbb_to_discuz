<?php
!defined('R_P') && exit('Forbidden!');

//修复头像的简单小程序(一般用不到的...)
$head = array();
$query = $SDB->query("SELECT ID,UserName,Pass,Mail,Sex,Birthday,ApplyTime,Prevtime,Userphoto,IP,Homepage,Underwrite,Points,OnlineTime,AnnounceNum,FaceUrl,FaceWidth,FaceHeight,AnnounceTopic,AnnounceGood,UploadNum,LastWriteTime,CachetValue,CharmPoint,ICQ,OICQ,UserLevel,Officer,UserTitle FROM {$source_prefix}User WHERE ID >= $start AND ID < $end");// ID >= $start AND ID < $end");
while ($rt = $SDB->fetch_array($query))
{
    $ID = (int)$rt['ID'];
    $FaceUrl = '';
    //if($ID!=63266){
//continue;
    //}
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
    //if($ID==63266){
        //echo "UPDATE pw_members SET icon='$FaceUrl' WHERE uid=$ID";exit;
    //}
    //echo "UPDATE pw_members SET icon='$FaceUrl' WHERE uid=$ID";echo '<br>';exit;
    if($FaceUrl){
    $DDB->update("UPDATE pw_members SET icon='$FaceUrl' WHERE uid=$ID");
    }
    //$m->MoveNext();
}
//exit;
//writeover(S_P.'tmp_head.php', "\$_head = ".pw_var_export($head).";", true);
$row = $SDB->get_one("SELECT COUNT(*) AS num FROM {$source_prefix}User WHERE ID >= $end");
echo $row['num'];
if ($row['num'])
{
    refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
}
else
{
    echo '会员结束';exit;
    report_log();
    newURL($step);
}