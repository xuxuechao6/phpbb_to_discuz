<?php
/**
*
*  Copyright (c) 2003-06  PHPWind.net. All rights reserved.
*  Support : http://www.phpwind.net
*  This software is the proprietary information of PHPWind.com.
*
*/

!defined('R_P') && exit('Forbidden!');

//附件
if(empty($start)){
    $DDB->update("TRUNCATE TABLE {$pw_prefix}attachs");
}

$query = $SDB->query("SELECT a.*,p.fid,p.first 
                      FROM {$source_prefix}attachments a 
                      LEFT JOIN {$source_prefix}posts p USING(pid) 
                      WHERE a.aid >$start 
                      ORDER BY a.aid ASC
                      LIMIT $percount");
unset($lastid);
while($a = $SDB->fetch_array($query)){
    $lastid = $a['aid'];
    /*附件类型转换*/
    $fileinfo = getfileinfo($a['filename']);
    $a['filetype'] 	= $fileinfo['type'];
    $ifupload 		= $fileinfo['ifupload'];
    if (0 != $a['price']){
        $needrvrc       = $a['price'];
        $special        = 2;
        $ctype          = 'money';
    }else{
        $needrvrc = 0;
        $special  = 0;
        $ctype    = '';
    }

    $attachesql = '';
    $attachesql = "(".$a['aid'].",'".$a['fid']."','".$a['uid']."','".$a['tid']."',".($a['first'] ? 0 : $a['pid']).",'".addslashes($a['filename'])."','".$a['filetype']."',".(round($a['filesize']/1024)).",'".addslashes($a['attachment'])."',".$a['downloads'].",'".$needrvrc."',".$special.",'".$ctype."',".$a['dateline'].",'".addslashes($a['description'])."')";
    if('' != $attachesql){
        $DDB->update("REPLACE INTO {$pw_prefix}attachs (aid,fid,uid,tid,pid,name,type,size,attachurl,hits,needrvrc,special,ctype,uploadtime,descrip) VALUES $attachesql ");
    }
    $s_c++;
}
$SDB->free_result($query);

$maxid = $SDB->get_value("SELECT MAX(aid) FROM {$source_prefix}attachments");
empty($lastid) && $lastid = $end;

if($lastid < $maxid){
    refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
}else{
    report_log();
    newURL($step);
}