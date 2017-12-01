<?php
!defined('R_P') && exit('Forbidden!');

//友情
require_once S_P.'lang_'.$dest_charset.'.php';
$DDB->query("TRUNCATE TABLE {$pw_prefix}sharelinks");
$query = $SDB->query("SELECT * FROM {$source_prefix}forumlinks");

$insert = '';
while($link = $SDB->fetch_array($query))
{
    if (strpos(strtolower($link['name']), 'discuz') === FALSE)
    {
        $insert .= "(".$link['displayorder'].",'".addslashes($link['name'])."', '".addslashes($link['url'])."','".addslashes($link['description'])."','".addslashes($link['logo'])."', 1),";
    }
    $s_c ++;
}

$insert .= $lang['link'];
$DDB->update("INSERT INTO {$pw_prefix}sharelinks (threadorder, name, url, descrip, logo, ifcheck) VALUES ".$insert);

report_log();
newURL($step);
