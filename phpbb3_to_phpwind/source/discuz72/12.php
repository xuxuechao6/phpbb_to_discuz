<?php
/**
*
*  Copyright (c) 2003-06  PHPWind.net. All rights reserved.
*  Support : http://www.phpwind.net
*  This software is the proprietary information of PHPWind.com.
*
*/

!defined('R_P') && exit('Forbidden!');

//回复
$_ttype = $_pwface = $_dzface = '';
require_once S_P.'tmp_ttype.php';
require_once S_P.'tmp_face.php';
require_once S_P.'tmp_credit.php';

if(empty($start)){
    $DDB->update("TRUNCATE TABLE {$pw_prefix}posts");
}

$query = $SDB->query("SELECT * FROM {$source_prefix}posts 
                      WHERE pid > $start AND first = '0'
                      ORDER BY pid ASC
                      LIMIT $percount");//用主键来搜索快
unset($lastid);
while($p = $SDB->fetch_array($query)){
    $mission++;
    $lastid = $p['pid'];

    if (!$p['fid'] || !$p['tid'] || $p['first'] == 1){//first =1无须处理
            if($p['first']!=1){
            $f_c++;
            errors_log($p['pid']."\t".$p['fid']."\t".$p['tid']);
        }
        continue;
    }
    $ifmark='';
    $p['subject'] = addslashes($p['subject']);
    $p['message'] = addslashes(dz_ubb(str_replace($_dzface,$_pwface,$p['message'])));
    $ifconvert = (convert($p['message']) == $p['message'])? 1 : 2;

    if(!$speed){//一条一条插
        $postsqlstr =  "(".$p['pid'].",".$p['fid'].",".$p['tid'].",'".$p['attachment']."','".addslashes($p['author'])."',".$p['authorid'].",".$p['dateline'].",'".$p['subject']."','".$p['useip']."',".$p['usesig'].",'',".$ifconvert.",".($p['invisible'] < 0 ? 0 : 1).",'".$p['message']."',".$p['status'].",".$p['anonymous'].",'".$ifmark."')";
        
        !empty($postsqlstr) && $DDB->update("REPLACE INTO {$pw_prefix}posts (pid,fid,tid,aid,author,authorid,postdate,subject,userip,ifsign,buy,ifconvert,ifcheck,content,ifshield,anonymous,ifmark) VALUES $postsqlstr ");
    }

    if($speed){//批量插
        $postsql[] =  "(".$p['pid'].",".$p['fid'].",".$p['tid'].",'".$p['attachment']."','".addslashes($p['author'])."',".$p['authorid'].",".$p['dateline'].",'".$p['subject']."','".$p['useip']."',".$p['usesig'].",'',".$ifconvert.",".($p['invisible'] < 0 ? 0 : 1).",'".$p['message']."',".$p['status'].",".$p['anonymous'].",'".$ifmark."')";
    }
    $s_c++;
}
$SDB->free_result($query);

if($speed){//批量插
    if($postsql){
        $postsqlstr = implode(",",$postsql);
        !empty($postsqlstr) && $DDB->update("REPLACE INTO {$pw_prefix}posts (pid,fid,tid,aid,author,authorid,postdate,subject,userip,ifsign,buy,ifconvert,ifcheck,content,ifshield,anonymous,ifmark) VALUES $postsqlstr ");
    }
}

$maxid = $SDB->get_value("SELECT max(pid) FROM {$source_prefix}posts");
empty($lastid) && $lastid = $end;

echo '最大id',$maxid.'最后id',$lastid;
if($lastid < $maxid){    
    refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
}else{
    report_log();
    newURL($step);
}