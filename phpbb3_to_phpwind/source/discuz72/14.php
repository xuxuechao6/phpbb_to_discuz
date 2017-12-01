<?php
!defined('R_P') && exit('Forbidden!');

//公告
$DDB->update("TRUNCATE TABLE {$pw_prefix}announce");

$query = $SDB->query("SELECT * FROM {$source_prefix}announcements");
while($a = $SDB->fetch_array($query)){
    $DDB->update("REPLACE INTO {$pw_prefix}announce (aid,fid,ifopen,vieworder,author,startdate,url,enddate,subject,content,ifconvert) VALUES (".$a['id'].",-1,1,".$a['displayorder'].",'".addslashes($a['author'])."',".$a['starttime'].",'".addslashes((($a['type'] & 1) ? $a['message'] : ''))."',".$a['endtime'].",'".addslashes($a['subject'])."','".addslashes($a['message'])."',".((convert($a['message']) == $a['message'])? 0 : 1).")");
    $s_c++;
}

//版块公告
$b_i  = $DDB->get_value("SELECT max(aid) FROM {$pw_prefix}announce") + 1;
$query = $SDB->query("SELECT cff.fid,cff.rules,cf.name 
                      FROM {$source_prefix}forumfields cff 
                      LEFT JOIN {$source_prefix}forums cf USING(fid) 
                      WHERE cff.rules!=''");
while($b = $SDB->fetch_array($query)){
    $DDB->update("REPLACE INTO {$pw_prefix}announce (aid,fid,ifopen,vieworder,author,startdate,url,enddate,subject,content,ifconvert) VALUES (".$b_i.",".$b['fid'].",1,1,'admin',".$timestamp.",'','','".addslashes($b['name'])."','".addslashes($b['rules'])."',1)");
    $b_i++;
    $s_c++;
}
$SDB->free_result($query);

report_log();
newURL($step);
