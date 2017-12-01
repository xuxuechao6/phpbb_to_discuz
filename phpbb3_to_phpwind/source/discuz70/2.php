<?php
!defined('R_P') && exit('Forbidden!');

//ฑํว้
$_pwface = $_dzface = array();
$DDB->query("TRUNCATE TABLE {$pw_prefix}smiles");
$query = $SDB->query("SELECT typeid,directory,name,displayorder FROM {$source_prefix}imagetypes WHERE type = 'smiley'");
while ($s = $SDB->fetch_array($query))
{
    $DDB->update("INSERT INTO {$pw_prefix}smiles (id,path,name,vieworder,type) VALUES (".$s['typeid'].",'".addslashes($s['directory'])."','".addslashes($s['name'])."','".$s['displayorder']."',0)");
    $s_c++;
}

$query = $DDB->query("SELECT id,path,name,vieworder FROM {$pw_prefix}smiles");
while ($i = $DDB->fetch_array($query))
{
    $query2 = $SDB->query("SELECT displayorder,code,url FROM {$source_prefix}smilies WHERE typeid = ".$i['id']);
    while($s = $SDB->fetch_array($query2))
    {
        $DDB->update("INSERT INTO {$pw_prefix}smiles (path,vieworder,type) VALUES('".addslashes($s['url'])."',".$s['displayorder'].",".$i['id'].")");
        $_pwface[] = '[s:'.$DDB->insert_id().']';
        $_dzface[] = $s['code'];
    }
}
writeover(S_P.'tmp_face.php', "\$_pwface = ".pw_var_export($_pwface).";\n\$_dzface = ".pw_var_export($_dzface).";", true);
report_log();
newURL($step);
