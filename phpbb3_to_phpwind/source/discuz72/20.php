<?php
/**
*
*  Copyright (c) 2003-06  PHPWind.net. All rights reserved.
*  Support : http://www.phpwind.net
*  This software is the proprietary information of PHPWind.com.
*
*/

!defined('R_P') && exit('Forbidden!');

//头像
$_avatar = array();
$pw_avatar = R_P.'pwavatar';
if (empty($start)){
    $dirname = array();
    $uc_avatar = R_P.'avatar';
    if (!is_dir($pw_avatar) || !is_dir($uc_avatar) || !is_readable($uc_avatar) || !N_writable($pw_avatar)){
        ShowMsg('用于转换头像的 avatar 或者 pwavatar 目录不存在或者无法写入。<br /><br />1、请将 UCenter安装目录/data/ 下的 avatar 目录复制到 PWBuilder 根目录。<br /><br />2、在PWBuilder 根目录下建立一个名为：pwavatar 的目录，且设定权限为777。<br /><br />', true);
    }
    PWListDir($uc_avatar, $dirname);
    writeover(S_P.'tmp_avatar.php', "\$_avatar = ".pw_var_export($dirname).";", true);
}
require_once(S_P.'tmp_avatar.php');
if ($start >= count($_avatar)){
    report_log();
    newURL($step);
}
$dh = opendir($_avatar[$start]);
while (($file = readdir($dh)) !== FALSE){
    $match = array();
    if ($file != '.' && $file != '..' && preg_match('/^[a-z0-9\:\/\._]*?\/avatar\/(\d{3})\/(\d{2})\/(\d{2})\/(\d{2})\_(real_)?avatar_middle\.jpg$/i', $_avatar[$start].'/'.$file, $match)){
        $uid = intval($match[1].$match[2].$match[3].$match[4]);
        $size = GetImgSize($_avatar[$start].'/'.$file);
        $savedir = str_pad(substr($uid,-2),2,'0',STR_PAD_LEFT);
        if (!is_dir($pw_avatar.'/'.$savedir)){
            @mkdir($pw_avatar.'/'.$savedir);
            @chmod($pw_avatar.'/'.$savedir,0777);
        }
        @copy($_avatar[$start].'/'.$file, $pw_avatar.'/'.$savedir.'/'.$uid.'.jpg');
        $DDB->update("UPDATE {$pw_prefix}members SET icon = '".$savedir.'/'.$uid.".jpg|3|".$size['width']."|".$size['height']."' WHERE uid = ".$uid);
        $s_c ++;
    }
}
$end = ++$start;
refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);