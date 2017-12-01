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
    'bbrules'	    =>'rg_regdetail',
    'bbrulestxt'    =>'rg_rgpermit',
    //'regadvance'	=>'rg_regdetail',
    'censoruser'	=>array('rg_banname','dz_banname'),
    'regverify'		=>array('rg_emailcheck','dz_regcheck'),
    'censoremail'	=>array('rg_email','dz_regemail'),
    'regctrl'		=>'rg_allowsameip',
    //'bbrules'		=>'rg_reg',
    'frameon'		=>'db_columns',
    'seotitle'		=>'db_bbstitle',
    'seokeywords'	=>'db_metakeyword',
    'seodescription'=>'db_metadescrip',
    'adminemail'	=>	'db_ceoemail',
    'modreasons'	=>	'db_adminreason',
    'statcode'		=>	'db_statscode',
);

$query = $SDB->query("SELECT skey, svalue FROM {$source_prefix}common_setting WHERE skey IN ('".implode('\',\'', array_keys($siteConfig))."')");
while ($s = $SDB->fetch_array($query))
{
    if (is_array($siteConfig[$s['skey']]))
    {
        $db_value = $siteConfig[$s['skey']][1]($s['svalue']);
        $db_name  = $siteConfig[$s['skey']][0];
    }
    else
    {
        $db_value = $s['svalue'];
        $db_name  = $siteConfig[$s['skey']];
    }
    $DDB->update("UPDATE {$pw_prefix}config SET db_value = '".addslashes($db_value)."' WHERE db_name = '$db_name'");
    $s_c++;
}

$historyposts = $SDB->get_value("SELECT svalue FROM {$source_prefix}common_setting WHERE skey = 'historyposts'");
$historyposts = $historyposts ? explode("\t", $historyposts) : array();
$onlinerecord = $SDB->get_value("SELECT svalue FROM {$source_prefix}common_setting WHERE skey = 'onlinerecord'");
$onlinerecord = $onlinerecord ? explode("\t", $onlinerecord) : array();

$DDB->update("UPDATE {$pw_prefix}bbsinfo SET higholnum = '".(int)$onlinerecord[0]."', higholtime = '".(int)$onlinerecord[1]."', yposts = '".(int)$historyposts[0]."', hposts = '".(int)$historyposts[1]."' WHERE id = 1");
$DDB->update("UPDATE {$pw_prefix}config SET db_value = 1 WHERE db_name IN ('db_topped','db_gdcheck')");
$DDB->update("UPDATE {$pw_prefix}config SET db_value = 600 WHERE db_name IN ('db_signheight')");
$DDB->update("UPDATE {$pw_prefix}config SET db_value = 3 WHERE db_name = 'db_attachdir'");
$s_c += 4;

$query = $SDB->query("SELECT find,replacement FROM {$source_prefix}common_word");
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
$SDB->free_result($query);

//回复分卷
$ptables = $SDB->get_value("SELECT svalue FROM {$source_prefix}common_setting WHERE skey='posttableids'");
$ptables = unserialize($ptables);
writeover(S_P.'tmp_ptables.php', "\$_ptables = ".pw_var_export($ptables).";\n;", true);

report_log();
newURL($step);