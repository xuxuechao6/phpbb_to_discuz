<?php
!defined('R_P') && exit('Forbidden!');

//论坛设置
$DDB->query("TRUNCATE TABLE {$pw_prefix}wordfb");
require_once S_P.'function.php';

$siteConfig = array(
    'bbname'		=>'db_bbsname',
    'siteurl'		=>array('db_bbsurl','dz_url'),
    'icp'			=>array('db_icp','dz_icp'),
    'bbclosed'		=>array('db_bbsifopen','dz_siteopen'),
    'closedreason'	=>'db_whybbsclose',
    'regadvance'	=>'rg_regdetail',
    'censoruser'	=>array('rg_banname','dz_banname'),
    'regverify'		=>array('rg_emailcheck','dz_regcheck'),
    'censoremail'	=>array('rg_email','dz_regemail'),
    'regctrl'		=>'rg_allowsameip',
    'bbrules'		=>'rg_reg',
    'frameon'		=>'db_columns',
    'seotitle'		=>'db_bbstitle',
    'seokeywords'	=>'db_metakeyword',
    'seodescription'=>'db_metadescrip',
);

$query = $SDB->query("SELECT variable, value FROM {$source_prefix}settings WHERE variable IN ('".implode('\',\'', array_keys($siteConfig))."')");
while ($s = $SDB->fetch_array($query))
{
    if (is_array($siteConfig[$s['variable']]))
    {
        $db_value = $siteConfig[$s['variable']][1]($s['value']);
        $db_name  = $siteConfig[$s['variable']][0];
    }
    else
    {
        $db_value = $s['value'];
        $db_name  = $siteConfig[$s['variable']];
    }
    $DDB->update("UPDATE {$pw_prefix}config SET db_value = '".addslashes($db_value)."' WHERE db_name = '$db_name'");
    $s_c++;
}

$historyposts = $SDB->get_one("SELECT value FROM {$source_prefix}settings WHERE variable = 'historyposts'");
$historyposts = $historyposts['value'] ? explode("\t", $historyposts['value']) : array();
$onlinerecord = $SDB->get_one("SELECT value FROM {$source_prefix}settings WHERE variable = 'onlinerecord'");
$onlinerecord = $onlinerecord['value'] ? explode("\t", $onlinerecord['value']) : array();

$DDB->update("UPDATE {$pw_prefix}bbsinfo SET higholnum = '".(int)$onlinerecord[0]."', higholtime = '".(int)$onlinerecord[1]."', yposts = '".(int)$historyposts[0]."', hposts = '".(int)$historyposts[1]."' WHERE id = 1");
$DDB->update("UPDATE {$pw_prefix}config SET db_value = 1 WHERE db_name IN ('db_topped','db_gdcheck')");
$DDB->update("UPDATE {$pw_prefix}config SET db_value = 600 WHERE db_name IN ('db_signheight')");
$DDB->update("UPDATE {$pw_prefix}config SET db_value = 3 WHERE db_name = 'db_attachdir'");
$s_c += 4;

$query = $SDB->query("SELECT find,replacement FROM {$source_prefix}words");
while ($b = $SDB->fetch_array($query))
{
    $replacement = '';
    switch ($b['replacement'])
    {
        case '{MOD}':
            $type = 2;
            break;
        case '{BANNED}':
            $type = 1;
            break;
        default:
            $type = 0;
            $replacement = addslashes($b['replacement']);
            break;
    }
    $DDB->update("INSERT INTO {$pw_prefix}wordfb (word,wordreplace,type) VALUES ('".addslashes(preg_replace('~{\d+?}~is','',$b['find']))."','$replacement',$type)");
    $s_c++;
}

//勋章开始
$DDB->query("TRUNCATE TABLE {$pw_prefix}medal_info");
$query = $SDB->query("SELECT medalid,name,image FROM {$source_prefix}medals");
while ($m = $SDB->fetch_array($query))
{
    $DDB->update("INSERT INTO {$pw_prefix}medal_info (medal_id,name,descrip,image,type) VALUES (".$m['medalid'].",'".addslashes($m['name'])."','".addslashes($m['name'])."','".addslashes($m['image'])."','2')");
    $s_c++;
}
//勋章结束

report_log();
newURL($step);
