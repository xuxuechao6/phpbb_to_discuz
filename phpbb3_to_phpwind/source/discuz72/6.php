<?php
/**
*
*  Copyright (c) 2003-06  PHPWind.net. All rights reserved.
*  Support : http://www.phpwind.net
*  This software is the proprietary information of PHPWind.com.
*
*/

!defined('R_P') && exit('Forbidden!');

//主题
$_ttype = $_pwface = $_dzface = '';
$threadsql = $tmsgssql = '';
require_once S_P.'tmp_ttype.php';
require_once S_P.'tmp_face.php';
require_once S_P.'tmp_credit.php';//多个

if(empty($start)){
    $DDB->query("TRUNCATE TABLE {$pw_prefix}threads");
    $DDB->query("TRUNCATE TABLE {$pw_prefix}tmsgs");
    $DDB->query("TRUNCATE TABLE {$pw_prefix}recycle");
    $DDB->query("TRUNCATE TABLE {$pw_prefix}poststopped");
}

$query = $SDB->query("SELECT t.tid,t.iconid,t.subject,t.typeid,t.readperm,t.price,t.lastpost,t.lastposter,
					  t.views,t.replies,t.displayorder,t.highlight,t.digest,t.special,t.attachment,
					  t.moderated,t.closed,t.itemid,
					  p.pid,p.fid,p.first,p.author,p.authorid,p.dateline,p.message,p.useip,p.invisible,
					  p.anonymous,p.usesig,p.htmlon,p.bbcodeoff,p.smileyoff,p.parseurloff,p.attachment,
					  p.rate,p.ratetimes,p.status
			          FROM {$source_prefix}threads t FORCE INDEX(PRIMARY)
			          INNER JOIN {$source_prefix}posts p USING (tid)
			          WHERE t.tid >= $start AND p.first = 1
			          ORDER BY t.tid ASC 
			          LIMIT $percount");
unset($lastid);
while($t = $SDB->fetch_array($query)){
    $lastid = $t['tid'];
    if (!$t['fid']){
        $f_c++;
        errors_log($t['fid']."\t".$t['pid']."\t".$t['subject']);
        continue;
    }

    switch ($t['special']){
        case '1':
            $special = 1;//投票
            break;
        case '2':
            $special = 4;//交易
            break;
        case '3':
            $special = 3;//悬赏
            break;
        case '4':
            $special = 8;//活动
            break;
        case '5':
            $special = 5;//辩论
            break;
        default:
            $special = 0;//普通
            break;
    }

    $fid = $t['fid'];
    $ifcheck = '1';
    $topped = '0';

    switch ($t['displayorder']){
        case -1://回收站
            $modtidsql[] = $t['tid'];
            $t['fid'] = 0;
            break;
        case -2://需要审核
            $ifcheck = 0;
            break;
        case 1:
            $topped = 1;
            break;
        case 2:
            $topped = 2;
            break;
        case 3:
            $topped = 3;
            break;
    }
    if($topped != '0'){
        setForumsTopped($t['tid'],$t['fid'],$topped,0);
    }

    $titlefont = $tag = $aid = $ifupload = '';
    if($t['highlight']){
        $title1 = $title2 = $title3 = $title4 = '';
        $string = sprintf('%02d', $t['highlight']);
        $stylestr = sprintf('%03b', $string[0]);
        $stylestr[0] && $title2 = '1';
        $stylestr[1] && $title3 = '1';
        $stylestr[2] && $title4 = '1';
        if ($string[1]){
            $colorarray = array('', 'red', 'orange', '#996600', 'green', 'cyan', 'blue', 'purple', 'gray');
            $title1 = $colorarray[$string[1]];
        }
        $titlefont = "$title1~$title2~$title3~$title4~~~";
    }

    $ifupload = $t['attachment'];
    $t['typeid'] = (int)$_ttype[$fid][$t['typeid']];
    $t['message'] = addslashes(dz_ubb(str_replace($_dzface,$_pwface,$t['message'])));
    $ifcheck = $t['invisible'] < 0 ? '0' : '1';  //DZ中-1为放入回收站 0为审核通过
    $ifmark='';//评分

    if(empty($speed)){//一条一条插
        $threadsqlstr = "(".$t['tid'].",".$t['fid'].",'".addslashes($titlefont)."','".addslashes($t['author'])."',".$t['authorid'].",'".addslashes($t['subject'])."','$ifcheck',".$t['typeid'].",".$t['dateline'].",".$t['lastpost'].",'".addslashes($t['lastposter'])."',".$t['views'].",".$t['replies'].",{$topped},".$t['closed'].",".$t['digest'].",{$special},'".$ifupload."','".$ifmarkcount."',".$t['status'].",".$t['anonymous'].")";
        $tmsgssqlstr = "(".$t['tid'].",'".$t['attachment']."','".$t['useip']."',".$t['usesig'].",'','','".addslashes($tag)."',".((convert($t['message']) == $t['message'])? 1 : 2).",'".$t['message']."','".$ifmark."')";

        !empty($threadsqlstr) && $DDB->update("REPLACE INTO {$pw_prefix}threads (tid,fid,titlefont,author,authorid,subject,ifcheck,type,postdate,lastpost,lastposter,hits,replies,topped,locked,digest,special,ifupload,ifmark,ifshield,anonymous) VALUES $threadsqlstr ");
        !empty($tmsgssqlstr) && $DDB->update("REPLACE INTO {$pw_prefix}tmsgs (tid,aid,userip,ifsign,buy,ipfrom,tags,ifconvert,content,ifmark) VALUES $tmsgssqlstr "); 
    }

    if(!empty($speed)){//批量插
        $threadsql[] = "(".$t['tid'].",".$t['fid'].",'".addslashes($titlefont)."','".addslashes($t['author'])."',".$t['authorid'].",'".addslashes($t['subject'])."','$ifcheck',".$t['typeid'].",".$t['dateline'].",".$t['lastpost'].",'".addslashes($t['lastposter'])."',".$t['views'].",".$t['replies'].",{$topped},".$t['closed'].",".$t['digest'].",{$special},'".$ifupload."','".$ifmarkcount."',".$t['status'].",".$t['anonymous'].")";
        $tmsgssql[] = "(".$t['tid'].",'".$t['attachment']."','".$t['useip']."',".$t['usesig'].",'','','".addslashes($tag)."',".((convert($t['message']) == $t['message'])? 1 : 2).",'".$t['message']."','".$ifmark."')";
    }
    $s_c++;
}
$SDB->free_result($query);

if(!empty($speed)){ //批量插
    if(!empty($threadsql)){
        $threadsqlstr = implode(",",$threadsql);
        !empty($threadsqlstr) && $DDB->update("REPLACE INTO {$pw_prefix}threads (tid,fid,titlefont,author,authorid,subject,ifcheck,type,postdate,lastpost,lastposter,hits,replies,topped,locked,digest,special,ifupload,ifmark,ifshield,anonymous) VALUES $threadsqlstr ");
    }
    if(!empty($tmsgssql)){
        $tmsgssqlstr = implode(",",$tmsgssql);
        !empty($tmsgssqlstr) && $DDB->update("REPLACE INTO {$pw_prefix}tmsgs (tid,aid,userip,ifsign,buy,ipfrom,tags,ifconvert,content,ifmark) VALUES $tmsgssqlstr ");
    }
}
//回车站的帖子处理
if(!empty($modtidsql)){
    $modsql = array();
    
    $query_mod = $SDB->query("SELECT tm.tid,tm.username,tm.dateline,t.fid 
    						  FROM {$source_prefix}threadsmod tm 
    						  LEFT JOIN {$source_prefix}threads t USING(tid) 
    						  WHERE tm.tid in (".implode(",",$modtidsql).") AND tm.action = 'DEL'");
    while($threadmod = $SDB->fetch_array($query_mod)){
        $modsql[] = "(0,".$threadmod['tid'].",".$threadmod['fid'].",'".$threadsmod['dateline']."','".addslashes($threadsmod['username'])."')";
    }
    $SDB->free_result($query_mod);
    
    if(!empty($modsql)){
        $modsqlstr = implode(",",$modsql);
        !empty($modsqlstr) && $DDB->update("REPLACE INTO {$pw_prefix}recycle (pid,tid,fid,deltime,admin) VALUES $modsqlstr ");
    }
}

$maxid = $SDB->get_value("SELECT MAX(tid) FROM {$source_prefix}threads LIMIT 1");
empty($lastid) && $lastid = $end;

echo '最大id',$maxid.'<br>最后id',$lastid;
if ($lastid < $maxid){
    refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
}else{
    report_log();
    newURL($step);
}