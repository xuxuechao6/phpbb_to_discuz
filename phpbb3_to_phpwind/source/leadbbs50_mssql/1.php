<?php
!defined('R_P') && exit('Forbidden!');

//ฑํว้
$DDB->update("DELETE FROM {$pw_prefix}smiles WHERE id > 1");
$DDB->update("ALTER TABLE {$pw_prefix}smiles AUTO_INCREMENT = 0");
$k=1;
$DDB->update("INSERT INTO {$pw_prefix}smiles (id,path,type,name,vieworder) VALUES(2,'UBBicon',0,'leadbbs',2)");
for($i = 3; $i < 49; $i++)
{
    if($k < 10){$k='0'.$k;}
    $url = "EM".$k.".GIF";
    $DDB->update("INSERT INTO {$pw_prefix}smiles (id,path,type,name,vieworder) VALUES(".$i.",'".$url."',2,'',".$k.")");
    $_pwface[] = '[s:'.$DDB->insert_id().']';
    $_dzface[] = "[EM".$k."]";
    $k++;
}
writeover(S_P.'tmp_face.php', "\$_pwface = ".pw_var_export($_pwface).";\n\$_dzface = ".pw_var_export($_dzface).";", true);
report_log();
newURL($step);
