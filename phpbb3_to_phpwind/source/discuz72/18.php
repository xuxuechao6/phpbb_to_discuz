<?php
!defined('R_P') && exit('Forbidden!');

//标签
$goon = 0;
if(empty($start)){
    $DDB->update("TRUNCATE TABLE {$pw_prefix}tags");
    $DDB->update("TRUNCATE TABLE {$pw_prefix}tagdata");
    if ($pwsamedb){
        $DDB->update("INSERT INTO {$pw_prefix}tags (tagname,num) SELECT tagname,total FROM {$source_prefix}tags");
    }else{
        $query = $SDB->query("SELECT tagname,total FROM {$source_prefix}tags");
        $it = '';
        while ($r = $SDB->fetch_array($query)){
            $it .= "('".addslashes($r['tagname'])."','".$r['total']."'),";
        }
        !empty($it) && $DDB->update("INSERT INTO {$pw_prefix}tags (tagname,num) VALUES ".substr($it, 0, -1));
    }
}

$query = $SDB->query("SELECT * FROM {$source_prefix}threadtags LIMIT $start, $percount");
while ($r = $SDB->fetch_array($query)){
    $tagid = $DDB->get_one("SELECT tagid FROM {$pw_prefix}tags WHERE tagname = '".$r['tagname']."'");
    $tagid && $DDB->update("INSERT INTO {$pw_prefix}tagdata (tagid,tid) VALUES (".$tagid['tagid'].", ".$r['tid'].")");

    if($tagarr[$r['tid']]){
        $tagarr[$r['tid']] = $tagarr[$r['tid']].','.$r['tagname'];
    }else{
        $tagarr[$r['tid']] = $r['tagname'];
    }
    $goon ++;
    $s_c ++;
}
$SDB->free_result($query);

foreach($tagarr as $k => $v){
    $DDB->update("UPDATE {$pw_prefix}tmsgs SET tags = '".addslashes($v)."' WHERE tid=$k");
}
if ($goon == $percount){
    refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
}else{
    report_log();
    newURL($step);
}