<?php
!defined('R_P') && exit('Forbidden!');

//头像
$_avatar = array();
$pw_avatar = R_P.'pwavatar';
$dz_avatar = R_P.'face';
if (!$start)
{
    if (!is_dir($pw_avatar) || !N_writable($pw_avatar) || !is_readable($dz_avatar) || !N_writable($dz_avatar))
    {
        ShowMsg('用于转换头像的 upload 或者 pwavatar 目录不存在或者无法写入。<br /><br />1、请将 LeadBBS 安装目录Images/upload  下的 face 目录移动到 PWBuilder 根目录。<br /><br />2、在PWBuilder 根目录下建立一个名为：pwavatar 的目录，且设定权限为777。<br /><br />', true);
    }
    PWListDir($dz_avatar, $dirname);
    writeover(S_P.'tmp_avatar.php', "\$_avatar = ".pw_var_export($dirname).";", true);
}
require_once(S_P.'tmp_avatar.php');
require_once(S_P.'tmp_head.php');
if ($start >= count($_avatar))
{
    exit;
    report_log();
    newURL($step);
}
$dh = opendir($_avatar[$start]);
while (($file = readdir($dh)) !== FALSE)
{
    $match = array();
    if ($file != '.' && $file != '..')
    {
        $img = explode('.',$file);
        if($img[1]=='jpg' || $img[1]=='gif' || $img[1]=='bmp' || $img[1]=='png' || $img[1]=='jpeg')
        {
            $uid = $_head[$file];
            $size = GetImgSize($_avatar[$start].'/'.$file);
            $savedir = str_pad(substr($uid,-2),2,'0',STR_PAD_LEFT);
            if (!is_dir($pw_avatar.'/'.$savedir))
            {
                @mkdir($pw_avatar.'/'.$savedir);
                @chmod($pw_avatar.'/'.$savedir,0777);
            }
            @copy($_avatar[$start].'/'.$file, $pw_avatar.'/'.$savedir.'/'.$uid.'.jpg');
            $DDB->update("UPDATE {$pw_prefix}members SET icon = '".$savedir.'/'.$uid.".jpg|3|".$size['width']."|".$size['height']."' WHERE uid = ".$uid);
            $s_c ++;
        }
    }
}
$end = ++$start;
refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
