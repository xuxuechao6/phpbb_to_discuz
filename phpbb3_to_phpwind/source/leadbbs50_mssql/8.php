<?php
!defined('R_P') && exit('Forbidden!');

//ͷ��
$_avatar = array();
$pw_avatar = R_P.'pwavatar';
$dz_avatar = R_P.'face';
if (!$start)
{
    if (!is_dir($pw_avatar) || !N_writable($pw_avatar) || !is_readable($dz_avatar) || !N_writable($dz_avatar))
    {
        ShowMsg('����ת��ͷ��� upload ���� pwavatar Ŀ¼�����ڻ����޷�д�롣<br /><br />1���뽫 LeadBBS ��װĿ¼Images/upload  �µ� face Ŀ¼�ƶ��� PWBuilder ��Ŀ¼��<br /><br />2����PWBuilder ��Ŀ¼�½���һ����Ϊ��pwavatar ��Ŀ¼�����趨Ȩ��Ϊ777��<br /><br />', true);
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
