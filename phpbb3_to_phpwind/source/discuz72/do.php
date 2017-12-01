<?php
/**
*
*  Copyright (c) 2003-06  PHPWind.net. All rights reserved.
*  Support : http://www.phpwind.net
*  This software is the proprietary information of PHPWind.com.
*
*/

!defined('R_P') && exit('Forbidden!');

$db_table = $step_data[$step];
if ($pwsamedb)
{
	$SDB = &$DDB;
}
else
{
	$charset_change = 1;
	$SDB = new mysql($source_db_host, $source_db_user, $source_db_password, $source_db_name, $source_charset, '');
}

if ($step == 1)
{
	//论坛设置
	require_once S_P.'1.php';
}
elseif ($step == 2)
{
	//表情
	require_once S_P.'2.php';
}
elseif ($step == 3)
{
    //勋章
	require_once S_P.'3.php';
}
elseif ($step == '4')
{
    //版块
	require_once S_P.'4.php';
}
elseif ($step == '5')
{
    //会员
	require_once S_P.'5.php';
}
elseif ($step == '6')
{
    //主题
	require_once S_P.'6.php';
}
elseif ($step == '7')
{
    //交易
	require_once S_P.'7.php';
}
elseif ($step == '8')
{
    //悬赏
	require_once S_P.'8.php';
}
elseif ($step == '9')
{
    //投票
	require_once S_P.'9.php';
}
elseif ($step == '10')
{
    //活动
	require_once S_P.'10.php';
}
elseif ($step == '11')
{
    //活动参加者
	require_once S_P.'11.php';
}
elseif ($step == '12')
{
    //回复
	require_once S_P.'12.php';
}
elseif ($step == '13')
{
    //附件
	require_once S_P.'13.php';
}
elseif ($step == '14')
{
    //公告
	require_once S_P.'14.php';
}
elseif ($step == '15')
{
    //短信
	require_once S_P.'15.php';
}
elseif ($step == '16')
{
    //好友
	require_once S_P.'16.php';
}
elseif ($step == '17')
{
    //收藏
	require_once S_P.'17.php';
}
elseif ($step == '18')
{
    //标签
	require_once S_P.'18.php';
}
elseif ($step == '19')
{
    //友情
	require_once S_P.'19.php';
}

elseif ($step == '20')
{
    //头像
	require_once S_P.'20.php';
}
elseif ($step == '21')
{
	//辩论
	if(!$start){
		$DDB->query("TRUNCATE TABLE {$pw_prefix}debates");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}debatedata");
	}
	//数据debateposts
	$query = $SDB->query("SELECT * FROM {$source_prefix}debates LIMIT $start, $percount");
    $goon=0;
	while($r = $SDB->fetch_array($query)){
		//$r=Add_S($r);
		//这里的正反方人数是主题表表加观点表
		$affirmvoterids=$r['affirmvoterids'];//正方人数
		$negavoterids=$r['negavoterids'];//反方人数
		$obvote=0;
		$revote=0;//反方得票数
		$obposts=0;
		$reposts=0;//反方辩手个数

		if(!empty($affirmvoterids)){
			$affarray=explode("\t",$affirmvoterids);
			if(is_array($affarray)){//正方
			   foreach($affarray as $va){
				   if(empty($va))continue;
				   $debatedatasql[]="(0,'".$r['tid']."','".$va."',1,'','','')";
				   $obvote++;
				   $s_c ++;
			   }
			}
		}

		if(!empty($negavoterids)){
			$negarray=explode("\t",$negavoterids);
			if(is_array($negarray)){//正方
			   foreach($negarray as $va){
				   if(empty($va))continue;
				   $debatedatasql[]="(0,'".$r['tid']."','".$va."',2,'','','')";
				   $revote++;
				   $s_c ++;
			   }
			}
		}

		$data = $SDB->query("SELECT * FROM {$source_prefix}debateposts where tid=".$r['tid']);
		while($d = $SDB->fetch_array($data)){
			//$d=Add_S($d);
			if($d['stand']==0)continue;
			$debatedatasql[]="('".$d['pid']."','".$r['tid']."','".$d['uid']."','".$d['stand']."','".$d['dateline']."','','')";
			if($d['stand']==1){
				$obvote++;
				$obposts++;
			}else{
				$revote++;
				$reposts++;
			}
			$s_c ++;
		}

		$sql="REPLACE into {$pw_prefix}debates (tid,authorid,postdate,obtitle,retitle,endtime,obvote,revote,obposts,reposts,umpire,umpirepoint,debater,judge)";
		$sql.="values ('{$r[tid]}','{$r[uid]}','{$r[starttime]}','{$r[affirmpoint]}','{$r[negapoint]}','{$r[endtime]}','{$obvote}','{$revote}','{$obposts}','{$reposts}','{$r[umpire]}','{$r[umpirepoint]}','','{$r[winner]}')";
		$DDB->update($sql);
		$s_c ++;
		$goon++;

		if ($debatedatasql){
		  $sqlstr = implode(",",$debatedatasql);
	   	  $DDB->update("REPLACE INTO {$pw_prefix}debatedata (pid,tid,authorid,standpoint,postdate,vote,voteids) VALUES $sqlstr");
		}
	}

	if ($goon == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else{
		$maxid = $SDB->get_value("SELECT max(tid) FROM {$source_prefix}debates");
		report_log();
		newURL($step);
	}
}elseif ($step == '22'){
	//圈子群组分类
	if(!file_exists(S_P."tmp_uch.php")){
		newURL($step);
	}else{
		require(S_P."tmp_uch.php");
	    $charset_change = 1;
	    $UCHDB = new mysql($uch_db_host, $uch_db_user, $uch_db_password, $uch_db_name, '');
	}

	if(!$start){
		$DDB->query("TRUNCATE TABLE {$pw_prefix}cnstyles");
        //$DDB->update("INSERT INTO {$pw_prefix}forums (name,fup) VALUE ('群组版块',1)");
        //$lastfid = $DDB->insert_id();
        //$DDB->update("INSERT INTO {$pw_prefix}forumdata (fid) VALUE ($lastfid)");
	}

	$query = $UCHDB->query("SELECT fieldid,title FROM {$uch_db_prefix}profield");
	while ($rt = $UCHDB->fetch_array($query)){
		$cid	=	$rt['fieldid'];
		$cname	=	$rt['title'];
		$cnstyledb[] = array($cid,$cname,1);//待更新群组的时候统计更新？？？？？？？？？？？
		$s_c ++;
	}
	$UCHDB->free_result($query);
	!empty($cnstyledb) && $DDB->update("REPLACE INTO {$pw_prefix}cnstyles (id,cname,ifopen) VALUES ".pwSqlMulti($cnstyledb));

	report_log();
	newURL($step);
}elseif ($step == '23'){
	//圈子群组
	if(!file_exists(S_P."tmp_uch.php")){
		newURL($step);
	}else{
		require(S_P."tmp_uch.php");
	    $charset_change = 1;
	    $UCHDB = new mysql($uch_db_host, $uch_db_user, $uch_db_password, $uch_db_name, '');
	}
	if(!$start){
		$DDB->query("TRUNCATE TABLE {$pw_prefix}colonys");
	}
    //传说不绑定版块也可以看群组的帖子故这里改下
	$query = $UCHDB->query("SELECT t.username,m.tagid,m.tagname,m.fieldid,m.membernum,m.joinperm,
							m.viewperm,m.pic,m.announcement,m.threadnum,m.postnum 
							FROM {$uch_db_prefix}tagspace t 
							LEFT JOIN {$uch_db_prefix}mtag m USING(tagid)
							WHERE t.grade='9' 
							GROUP BY tagid");
	while ($rt = $UCHDB->fetch_array($query)) {
		$id			=	$rt['tagid'];
		$styleid	=	$rt['fieldid'];
		$cname		=	$rt['tagname'];
		$admin		=	$rt['username'];
		$members	=	$rt['membernum'];
		$ifcheck	=	$rt['joinperm'] == 2 ? 0 : ($rt['joinperm'] == 0 ? 2 : 1); //加入权限
		$ifopen		=	$rt['viewperm'] == 1 ? 0 : 1; //群组公开权限
		$albumopen	=	'1';
		$cnimg		=	$rt['pic'];
		$createtime =	$timestamp;
		$annouce	=	$rt['announcement'];
		$albumnum	=	0;			//uchome群组无相册功能
		$annoucesee =	0;
		$descrip	=	'';			//uchome群组无描述
		$colonysdb[] = array($id,0,$cname,$admin,$members,$ifcheck,$ifopen,$cnimg,$createtime,$annouce,$albumnum,$annoucesee,$descrip,$rt['threadnum'],$rt['postnum'],$styleid);
		$s_c ++;
	}
	$UCHDB->free_result($query);
	!empty($colonysdb) && $DDB->update("REPLACE INTO {$pw_prefix}colonys (id,classid,cname,admin,members,ifcheck,ifopen,cnimg,createtime,annouce,albumnum,annoucesee,descrip,tnum,pnum,styleid) VALUES ".pwSqlMulti($colonysdb));

	report_log();
	newURL($step);
}elseif ($step == '24'){
	//圈子群组成员
	if(!file_exists(S_P."tmp_uch.php")){
		newURL($step);
	}else{
		require(S_P."tmp_uch.php");
	    $charset_change = 1;
	    $UCHDB = new mysql($uch_db_host, $uch_db_user, $uch_db_password, $uch_db_name, '');
	}
	if(!$start){
		$DDB->query("TRUNCATE TABLE {$pw_prefix}cmembers");
	}

	$query = $UCHDB->query("SELECT * FROM {$uch_db_prefix}tagspace 
							WHERE tagid <> 0 AND uid <> 0 
							LIMIT $start, $percount");
	$goon = 0;
	while ($rt = $UCHDB->fetch_array($query)) {
		$goon++;
		$uid	  = $rt['uid'];
		$username =	$rt['username'];
		$ifadmin  = ($rt['grade'] == 9 || $rt['grade'] == 8) ? '1' : '0';
		$colonyid = $rt['tagid'];
		$cmembersdb[] = array($uid,$username,$ifadmin,$colonyid);
		$s_c ++;
	}
	$UCHDB->free_result($query);
	!empty($cmembersdb) && $DDB->update("REPLACE INTO {$pw_prefix}cmembers (uid,username,ifadmin,colonyid) VALUES ".pwSqlMulti($cmembersdb));
	
	if ($goon == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else{
		report_log();
		newURL($step);
	}
}elseif ($step == '25'){
	//圈子群组讨论区
	if(!file_exists(S_P."tmp_uch.php")){
		newURL($step);
	}else{
		require(S_P."tmp_uch.php");
	    $charset_change = 1;
	    $UCHDB = new mysql($uch_db_host, $uch_db_user, $uch_db_password, $uch_db_name, '');
	}
	if(!$start){
		$DDB->query("TRUNCATE TABLE {$pw_prefix}argument");
	}
    //$lastfid = $DDB->get_value("SELECT max(fid) FROM {$pw_prefix}forums");
    $lastfid = 0;
	$query = $UCHDB->query("SELECT * FROM {$uch_db_prefix}thread 
							LEFT JOIN {$uch_db_prefix}post USING(tid) 
							WHERE isthread = 1 
							LIMIT $start, $percount");
	$goon = 0;
	while ($rt = $UCHDB->fetch_array($query)){
        ADD_S($rt);
		$goon++;
		$s_c ++;
		$maxid=$tid	= $rt['pid'];
		//$tpcid	= $rt['isthread'] == 1 ? 0 : $rt['tid'];
		$gid	= $rt['tagid'];
		$author = $rt['username'];
		$authorid = $rt['uid'];
		//$postdate = $rt['dateline'];

		//if (1 == $rt['isthread'])
		//{
			//$thread_info = $UCHDB->get_one("SELECT lastpost,subject FROM {$uch_db_prefix}thread WHERE tid=".pwEscape($rt['tid']));
			$lastpost = $rt['lastpost']; //最后发表
			$subject  = addslashes($rt['subject']); //标题
            $DDB->update("INSERT INTO {$pw_prefix}threads (fid,author,authorid,subject,postdate,lastpost,ifcheck) VALUES ($lastfid,'$rt[username]',$rt[uid],'$subject','$rt[dateline]','$lastpost',1)");
            $lasttid = $DDB->insert_id();
            $DDB->update("INSERT INTO {$pw_prefix}tmsgs (tid,content) VALUES ($lasttid,'$rt[message]')");

		    $topped  = 0;
		    $toppedtime = 0;

            $DDB->update("INSERT INTO {$pw_prefix}argument (tid,cyid,topped,postdate,lastpost) VALUES ($lasttid,$gid,$topped,".$rt['dateline'].",$lastpost)");
		//}
        /*
		else
		{
			$lastpost = '';
			$subject  = '';
            $DDB->update("INSERT INTO {$pw_prefix}posts (fid,tid,author,authorid,subject,content,postdate,lastpost) VALUES (10000,$rt[username],$rt[uid],$subject,$rt[dateline],$lastpost)");
		}*/

        $query2 = $UCHDB->query("SELECT * FROM {$uch_db_prefix}post WHERE tid=$rt[tid] and isthread=0");
        $goon = 0;
        while ($rt2 = $UCHDB->fetch_array($query2))
        {
            ADD_S($rt2);
            $DDB->update("INSERT INTO {$pw_prefix}posts (fid,tid,author,authorid,content,postdate) VALUES ($lastfid,$lasttid,'$rt2[username]',$rt2[uid],'$rt2[message]',$rt[dateline])");
        }

		//$argumentdb[] = array($tid,$gid,$author,$authorid,$postdate,$lastpost,$topped,$toppedtime,$subject,$content);
	}
	//$argumentdb && $DDB->update("REPLACE INTO {$pw_prefix}argument (tid,cyid,gid,author,authorid,postdate,lastpost,topped,toppedtime,subject,content) VALUES ".pwSqlMulti($argumentdb));
    //$argumentdb && $DDB->update("REPLACE INTO {$pw_prefix}argument (tid,cyid,topped,postdate,lastpost) VALUES ".pwSqlMulti($argumentdb));

	if ($goon == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else{
		$maxid = $UCHDB->get_value("SELECT max(tid) FROM {$uch_db_prefix}thread");
		report_log();
		newURL($step);
	}
}elseif ($step == '26'){
	//圈子记录
	if(!file_exists(S_P."tmp_uch.php")){
		newURL($step);
	}else{
		require(S_P."tmp_uch.php");
	    $charset_change = 1;
	    $UCHDB = new mysql($uch_db_host, $uch_db_user, $uch_db_password, $uch_db_name, '');
	}
	if(!$start){
		$DDB->query("TRUNCATE TABLE {$pw_prefix}weibo_content");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}weibo_relations");
	}
	$query = $UCHDB->query("SELECT * FROM {$uch_db_prefix}doing LIMIT $start, $percount");
	$goon = 0;
	while ($rt = $UCHDB->fetch_array($query)) {
		Add_S($rt);
		$goon++;
		$s_c ++;
		$id		= $rt['doid'];
		$uid	= $rt['uid'];
		$touid	= 0;
		$postdate = $rt['dateline'];
		$isshare = 0;
		$source	= 'web';
		$content = $rt['message'];
		$c_num = $rt['replynum'];
		$DDB->update("INSERT INTO {$pw_prefix}weibo_content(uid,content,postdate) values('".$uid."','".$content."','".$postdate."');");
		$DDB->update("INSERT INTO {$pw_prefix}weibo_relations (uid,authorid,postdate) VALUES ('".$uid."','".$uid."','".$postdate."')");
	}

	if ($goon == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else{
		report_log();
		newURL($step);
	}
}elseif ($step == '27'){
	//圈子记录回复
	if(!file_exists(S_P."tmp_uch.php")){
		newURL($step);
	}else{
		require(S_P."tmp_uch.php");
	    $charset_change = 1;
	    $UCHDB = new mysql($uch_db_host, $uch_db_user, $uch_db_password, $uch_db_name, '');
	}
	if(!$start){
		$DDB->query("TRUNCATE TABLE {$pw_prefix}weibo_comment");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}weibo_cmrelations");
	}
	$query = $UCHDB->query("SELECT * FROM {$uch_db_prefix}docomment LIMIT $start, $percount");
	$goon = 0;
	while ($rt = $UCHDB->fetch_array($query)) {
		$goon++;
		$s_c ++;
		$id			=	$rt['id'];
		$uid		=	$rt['uid'];
		$username	=	$rt['username'];
		$title		=	$rt['message'];
		$type		=	'write';
		$typeid		=	$rt['doid'];
		$upid		=	$rt['upid'];
		$postdate	=	$rt['dateline'];
		$DDB->update("REPLACE INTO {$pw_prefix}weibo_comment (uid,mid,content,postdate) VALUES ('".$uid."','".$rt['doid']."','".addslashes($rt['message'])."','".$postdate."')");
		$cid=$DDB->insert_id();
		$DDB->update("REPLACE INTO {$pw_prefix}weibo_cmrelations (uid,cid) VALUES ('".$uid."','".$cid."')");
	}

	if ($goon == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else{
		report_log();
		newURL($step);
	}
}elseif ($step == '28'){
	//圈子相册(将home/attachment目录下的图片移至到phpwind论坛的attachment/photo下)
	if(!file_exists(S_P."tmp_uch.php")){
		newURL($step);
	}else{
		require(S_P."tmp_uch.php");
	    $charset_change = 1;
	    $UCHDB = new mysql($uch_db_host, $uch_db_user, $uch_db_password, $uch_db_name, '');
	}
	if(empty($start)){
		$DDB->query("TRUNCATE TABLE {$pw_prefix}cnalbum");
	}

	$query = $UCHDB->query("SELECT * FROM {$uch_db_prefix}album 
							WHERE albumid >= $start
							ORDER BY albumid ASC
							LIMIT $percount");
	unset($lastid);
	while ($rt = $UCHDB->fetch_array($query)) {
		$lastid = $rt['albumid'];
		$s_c ++;
		$aid		=	$rt['albumid'];
		$aname		=	$rt['albumname'];
		$aintro		=	'';
		$atype		=	0;
		$private	=	$rt['friend'] == 0 ? 0 : 1;
		$ownerid	=	$rt['uid'];
		$owner		=	$rt['username'];
		$photonum	=	$rt['picnum'];
		$lastphoto	=	"photo/".$rt['pic'];
		$lasttime	=	$rt['updatetime'];
		$lastpid 	=	'';
		$crtime	 	=	$rt['dateline'];
		$cnalbumdb[]=	array($aid,$aname,$aintro,$atype,$private,$ownerid,$owner,$photonum,$lastphoto,$lasttime,$lastpid,$crtime);
	}
	$UCHDB->free_result($query);
	!empty($cnalbumdb) && $DDB->update("REPLACE INTO {$pw_prefix}cnalbum (aid,aname,aintro,atype,private,ownerid,owner,photonum,lastphoto,lasttime,lastpid,crtime) VALUES ".pwSqlMulti($cnalbumdb));
	
	$maxid = $UCHDB->get_value("SELECT MAX(albumid) FROM {$uch_db_prefix}album LIMIT 1");
	empty($lastid) && $lastid = $end;
	
	if ($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else{
		report_log();
		newURL($step);
	}
}elseif ($step == '29'){
	//圈子相册照片
	if(!file_exists(S_P."tmp_uch.php")){
		newURL($step);
	}else{
		require(S_P."tmp_uch.php");
	    $charset_change = 1;
	    $UCHDB = new mysql($uch_db_host, $uch_db_user, $uch_db_password, $uch_db_name, '');
	}
	if(empty($start)){
		$DDB->query("TRUNCATE TABLE {$pw_prefix}cnphoto");
	}
	
	$query = $UCHDB->query("SELECT * FROM {$uch_db_prefix}pic
							WHERE picid > $start
							ORDER BY picid ASC
							LIMIT $percount");
	unset($lastid);
	while ($rt = $UCHDB->fetch_array($query)) {
		$s_c ++;
		$lastid		= $pid = $rt['picid'];
		$aid		=	$rt['albumid'];
		$pintro		=	$rt['title'];
		$path		=	"photo/".$rt['filepath'];
		$uploader	=	getUsernameByUid($rt['uid']);
		$uptime		=	$rt['dateline'];
		$hits		=	0;
		$ifthumb	=	0;
		$c_num		=	getPicCommentNum($pid);
		$cnphoto[] 	= array($pid,$aid,$pintro,$path,$uploader,$uptime,$hits,$ifthumb,$c_num);
	}
	$UCHDB->free_result($query);
	!empty($cnphoto) && $DDB->update("REPLACE INTO {$pw_prefix}cnphoto (pid,aid,pintro,path,uploader,uptime,hits,ifthumb,c_num) VALUES ".pwSqlMulti($cnphoto));
	
	$maxid = $UCHDB->get_value("SELECT MAX(picid) FROM {$uch_db_prefix}pic LIMIT 1");
	empty($lastid) && $lastid = $end;
	
	if ($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else{
		report_log();
		newURL($step);
	}
}elseif ($step == '30'){
	//圈子分享
	if(!file_exists(S_P."tmp_uch.php")){
		newURL($step);
	}else{
		require(S_P."tmp_uch.php");
	    $charset_change = 1;
	    $UCHDB = new mysql($uch_db_host, $uch_db_user, $uch_db_password, $uch_db_name, '');
	}
	if(empty($start)){
		$DDB->query("TRUNCATE TABLE {$pw_prefix}collection");
	}

	$query = $UCHDB->query("SELECT * FROM {$uch_db_prefix}share 
							WHERE sid > $start 
							ORDER BY sid ASC 
							LIMIT $percount");
	unset($lastid);
	while ($rt = $UCHDB->fetch_array($query)) {
		$s_c ++;
		$lastid 	= 	$id = $rt['sid'];
		$type		=	getShareType($rt['type']);
		$uid		=	$rt['uid'];
		$username	=	$rt['username'];
		$postdate	=	$rt['dateline'];
		$content 	=	getShareContent($rt['body_data'],$rt['body_general'],$rt['type'],$uid,$username,$rt['image'],$rt['image_link']);
		$ifhidden	= 	0;
		$sharedb[] 	= 	array($id,$type,$uid,$username,$postdate,$content,$ifhidden);

	}
	$UCHDB->free_result($query);
	!empty($sharedb) && $DDB->update("REPLACE INTO {$pw_prefix}collection (id,type,uid,username,postdate,content,ifhidden) VALUES ".pwSqlMulti($sharedb));
	unset($sharedb);
	
	$maxid = $UCHDB->get_value("SELECT MAX(sid) FROM {$uch_db_prefix}share LIMIT 1");
	empty($lastid) && $lastid = $end;
	
	if ($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else{
		report_log();
		newURL($step);
	}
}elseif ($step == '31'){
	//圈子日志分类
	if(!file_exists(S_P."tmp_uch.php")){
		newURL($step);
	}else{
		require(S_P."tmp_uch.php");
	    $charset_change = 1;
	    $UCHDB = new mysql($uch_db_host, $uch_db_user, $uch_db_password, $uch_db_name, '');
	}
	if(empty($start)){
		$DDB->query("TRUNCATE TABLE {$pw_prefix}diarytype");
	}
	
	$query = $UCHDB->query("SELECT * FROM {$uch_db_prefix}class 
							WHERE classid > $start 
							ORDER BY classid ASC 
							LIMIT $percount");
	unset($lastid);
	while ($rt = $UCHDB->fetch_array($query)) {
		$s_c ++;
		$lastid = $dtid	= $rt['classid'];
		$uid	=	$rt['uid'];
		$name	=	$rt['classname'];
		$num	=	getDiaryNum($dtid);
		$diarytype = array(
			'dtid'	=>	$dtid,
			'uid'	=>	$uid,
			'name'	=>	$name,
			'num'	=>	$num
		);
		!empty($diarytype) && $DDB->update("REPLACE INTO {$pw_prefix}diarytype SET ".pwSqlSingle($diarytype));
	}
	$UCHDB->free_result($query);

	$maxid = $UCHDB->get_value("SELECT MAX(classid) FROM {$uch_db_prefix}class LIMIT 1");
	empty($lastid) && $lastid = $end;
	
	if ($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else{
		report_log();
		newURL($step);
	}
}elseif ($step == '32'){
	//圈子日志
	if(!file_exists(S_P."tmp_uch.php")){
		newURL($step);
	}else{
		require(S_P."tmp_uch.php");
	    $charset_change = 1;
	    $UCHDB = new mysql($uch_db_host, $uch_db_user, $uch_db_password, $uch_db_name, '');
	}
	if(empty($start)){
		$DDB->query("TRUNCATE TABLE {$pw_prefix}diary");
	}
	
	$query = $UCHDB->query("SELECT b.blogid,b.uid,b.classid,b.username,b.friend,b.subject,bf.message,
							b.viewnum,b.replynum,b.dateline 
							FROM {$uch_db_prefix}blog b 
							LEFT JOIN {$uch_db_prefix}blogfield bf USING(blogid)
							WHERE b.blogid >= $start
							LIMIT $percount");
	unset($lastid);
	while ($rt = $UCHDB->fetch_array($query)) {
		$s_c ++;
		$lastid = $did = $rt['blogid'];
		$uid = $rt['uid'];
		$dtid = $rt['classid'];
		$username = $rt['username'];
		$privacy  =	($rt['friend'] == 3 || $rt['friend'] == 4) ? 2 : (($rt['friend'] == 1 || $rt['friend'] == 2) ? 1 :0);
		$subject  = $rt['subject'];
		$content  =	$rt['message'];
		$ifcopy	  = 1;
		$copyurl  = '';
		$ifconvert = convert($rt['message']) == $rt['message'] ? 1 : 2;
		$ifwordsfb = 1;
		$r_num	  = $rt['viewnum'];
		$c_num	  =	$rt['replynum'];
		$postdate = $rt['dateline'];
		unset($diarydb);
		$diarydb = array(
			'did' => $did,
			'uid' => $uid,
			'dtid' => $dtid,
			'username' => $username,
			'privacy' => $privacy,
			'subject' => $subject,
			'content' => $content,
			'ifcopy' => $ifcopy,
			'copyurl' => $copyurl,
			'ifconvert' => $ifconvert,
			'ifwordsfb' => $ifwordsfb,
			'r_num' => $r_num ,
			'c_num' => $c_num,
			'postdate' => $postdate
		);
		$DDB->update("REPLACE INTO {$pw_prefix}diary SET".pwSqlSingle($diarydb));
	}
	$UCHDB->free_result($query);
	
	$maxid = $UCHDB->get_value("SELECT MAX(blogid) FROM {$uch_db_prefix}blog LIMIT 1");
	empty($lastid) && $lastid = $end;

	if ($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else{
		report_log();
		newURL($step);
	}
}elseif ($step == '33'){
	//圈子日志评论
	if(!file_exists(S_P."tmp_uch.php")){
		newURL($step);
	}else{
		require(S_P."tmp_uch.php");
	    $charset_change = 1;
	    $UCHDB = new mysql($uch_db_host, $uch_db_user, $uch_db_password, $uch_db_name, '');
	}

	$query = $UCHDB->query("SELECT * FROM {$uch_db_prefix}comment
							WHERE cid >= $start 
							ORDER BY cid ASC 
							LIMIT $percount");
	unset($lastid);
	while ($rt = $UCHDB->fetch_array($query)) {
		$lastid 	= $rt['cid'];
		$uid		=	$rt['authorid'];
		$username	=	$rt['author'];
		$title		=	$rt['message'];
		$type		=	getCommentType($rt['idtype']);
		$typeid		=	$rt['id'];
		$upid		=	0;
		$postdate	=	$rt['dateline'];
		
		unset($commentdb);
		$commentdb = array(
			'uid'=> $uid,
			'username'=> $username,
			'title'=> $title,
			'type'=> $type,
			'typeid'=> $typeid,
			'upid'=> $upid,
			'postdate'=> $postdate
		);
		!empty($commentdb) && $DDB->update("INSERT INTO {$pw_prefix}comment SET".pwSqlSingle($commentdb));
		$s_c ++;
	}
	$UCHDB->free_result($query);
	
	$maxid = $UCHDB->get_value("SELECT MAX(cid) FROM {$uch_db_prefix}comment LIMIT 1");
	empty($lastid) && $lastid = $end;

	if ($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else{
		report_log();
		newURL($step);
	}
}elseif ($step == '34'){
    //银行
	$rt_check = $SDB->get_one("SHOW TABLE STATUS LIKE '{$source_prefix}bankoperation'");
	if (!$rt_check) {
        report_log();
		newURL($step);
    }
    $query = $SDB->query("SELECT id,uid,username,optype,opnum,begintime FROM {$source_prefix}bankoperation LIMIT $start, $percount");
    $goon = 0;
    while ($thread = $SDB->fetch_array($query)){
        $goon++;
        $query_memberinfo = $DDB->get_one("select uid from {$pw_prefix}memberinfo where uid='{$thread['uid']}'");
        if (1 == $thread['optype']){ //活期
            if ($query_memberinfo['uid']){
                $DDB->update("update {$pw_prefix}memberinfo set deposit=deposit+{$thread['opnum']},startdate='{$thread['begintime']}' where uid='{$thread['uid']}'");
            }else{
                $DDB->update("insert into {$pw_prefix}memberinfo (uid,deposit,startdate) values ('{$thread['uid']}','{$thread['opnum']}','{$thread['begintime']}')");
            }
        }elseif (0 == $thread['optype']){ //定期
            if ($query_memberinfo['uid']){
                $DDB->update("update {$pw_prefix}memberinfo set ddeposit=ddeposit+{$thread['opnum']},dstartdate='{$thread['begintime']}' where uid='{$thread['uid']}'");
            }else{
                $DDB->update("insert into {$pw_prefix}memberinfo (uid,ddeposit,dstartdate) values ('{$thread['uid']}','{$thread['opnum']}','{$thread['begintime']}')");
            }
        }
    }
	if ($goon == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else{
		report_log();
		newURL($step);
	}
}elseif ($step == '35'){
    //广告
	if(!$start){
	    require_once S_P.'lang_advert.php';
	}

	$query = $SDB->query("SELECT * FROM {$source_prefix}advertisements 
						  WHERE advid >= $start 
						  ORDER BY advid ASC 
						  LIMIT $percount");
	$_ckey = array(
		'headerbanner'	=> 'Site.Header',
		'footer'	=> 'Site.Footer',
		'navbanner'	=> 'Site.NavBanner',
		'popup'		=> 'Site.PopupNotice',
		'float'		=> 'Site.FloatRand',
		'leftfloat'	=> 'Site.FloatLeft',
		'rightfloat'=> 'Site.FloatRight',
		'text.1'	=> 'Mode.TextIndex',
		'text.2'	=> 'Mode.Forum.TextRead',
		'text.3'	=> 'Mode.Forum.TextThread',
		'article.1'	=> 'Mode.Forum.Layer.TidRight',
		'article.2'	=> 'Mode.Forum.Layer.TidDown',
		'article.5'	=> 'Mode.Forum.Layer.TidUp',
		'article.3'	=> 'Mode.Forum.Layer.TidAmong',
		'article.4'	=> 'Mode.Forum.Layer.Index',
	);
	unset($lastid);
	while ($adv = $SDB->fetch_array($query)){
		$lastid = $adv['advid'];
		$config = array();
        $Sconfig = unserialize($adv['parameters']);
        $advtype = $adv['type'];
        if($adv['type']=='text'){//文字广告
            if($adv['targets']!='forum'){
                $advtype = 'text.3';
            }else{
                $advtype = 'text.1';
            }
        }
        if($adv['type']=='thread'){//帖内广告
            if($Sconfig['position']==1){//帖子下方 帖子上方 帖子右侧
                $advtype = 'article.2';
            }elseif($Sconfig['position']==2){
                $advtype = 'article.5';
            }elseif($Sconfig['position']==3){
                $advtype = 'article.1';
            }else{
                $advtype = 'article.2';
            }
            $louarr = explode("\t",$Sconfig['displayorder']);
		    $config['lou'] = implode(",",$louarr);
        }
        if($adv['type']=='interthread'){//贴间广告
            $advtype = 'article.3';
        }
        $ckey = $_ckey[$advtype];
        if($adv['endtime']==0){
            $adv['endtime'] = $adv['starttime'] + 31536000;
        }

        $fidarr = explode("\t",$adv['targets']);
        $fidarr1 =array();
        foreach($fidarr as $v){
            if(is_numeric($v)){
                $fidarr1[] = $v;
            }
        }
        $config['fid'] = implode(",",$fidarr1);

		if ($Sconfig['style'] == 'text') {
			$config['type'] = 'txt';
			$config['title'] = $Sconfig['title'];
			$config['link'] = $Sconfig['link'];
			$config['color'] = $Sconfig['color'];
			$config['size'] = $Sconfig['size'];
		} elseif($Sconfig['style'] == 'image') {
			$config['type'] = 'img';
			$config['title'] = $Sconfig['title'];
			$config['url'] = $Sconfig['url'];
			$config['link'] = $Sconfig['link'];
			$config['height'] = $Sconfig['height'];
			$config['width'] = $Sconfig['width'];
		} elseif($Sconfig['style'] == 'code') {//
			$config['type'] = 'code';
			$config['htmlcode'] = $Sconfig['html'];
		} elseif($Sconfig['style'] == 'flash') {
			$config['type'] = 'flash';
			$config['link'] = $Sconfig['url'];
			$config['height'] = $Sconfig['height'];
			$config['width'] = $Sconfig['width'];
		}
		$config = addslashes(serialize($config));

		$DDB->update("INSERT INTO {$pw_prefix}advert (type,uid,ckey,stime,etime,ifshow,orderby,descrip,config) values (1,0,'".$ckey."','".$adv['starttime']."','".$adv['endtime']."','".$adv['available']."','".$adv['displayorder']."','".$adv['title']."','".$config."')");
	}
	$maxid = $SDB->get_value("SELECT max(advid) FROM {$source_prefix}advertisements");
	empty($lastid) && $lastid = $end;
	
	if ($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else{
		report_log();
		newURL($step);
	}
}elseif ($step == '36'){
	//会员
	$insertadmin = '';
	$_specialgroup = $membersql = $memberdatasql = array();
	require_once (S_P.'tmp_credit.php');
	require_once (S_P.'tmp_uc.php');    //uc表

    //增加uc
	$charset_change = 1;
	$UCDB = new mysql($uc_db_host, $uc_db_user, $uc_db_password, $uc_db_name, '');

	require_once (S_P.'ubb.php');
	require_once (S_P.'tmp_group.php');

	$query = $UCDB->query("SELECT * FROM {$uc_db_prefix}members m 
						   LEFT JOIN {$uc_db_prefix}memberfields mf USING (uid) 
						   WHERE m.uid > $start 
						   ORDER BY m.uid 
						   LIMIT $percount");
	unset($lastid);
	while ($m = $UCDB->fetch_array($query)){
		Add_S($m);
        $lastid = $m['uid'];

		$rt = $SDB->get_one("SELECT uid FROM {$source_prefix}members WHERE uid=".$m['uid']);
		if($rt){
            continue;
        }

        $groupid = '-1';
		$timedf = ($m['timeoffset'] == '9999') ? '0' : $m['timeoffset'];//时差设定
		list($introduce,) = explode("\t", $m['bio']); //bio 自我介绍
		$editor = ($m['editormode'] == '1') ? '1' : '0';//编辑器模式
		$userface = $banpm = '';
		if ($m['avatar']){//头像
			$avatarpre = substr($m['avatar'], 0, 7);
			switch ($avatarpre){
				case 'http://':
					$userface = $m['avatar'].'|2|'.$m['avatarwidth'].'|'.$m['avatarheight'];
					break;
				case 'images/':
					$userface = substr($m['avatar'],strrpos($m['avatar'],'/')+1).'|1';
					break;
				case 'customa':
					$userface = substr($m['avatar'],strrpos($m['avatar'],'/')+1).'|3|'.$m['avatarwidth'].'|'.$m['avatarheight'];
					break;
			}
		}
		$m['sightml'] = addslashes(html2bbcode(stripslashes($m['sightml'])));//个性签名
		$signchange = ($m['sightml'] == convert($m['sightml'])) ? 1 : 2;
		$userstatus = ($signchange-1)*256 + 128 + $m['showemail']*64 + 4;//用户位状态设置
		$medals = $medal ? str_replace("\t", ',', $m['medals']) : '';

        //密码
		$m['ignorepm'] && $m['ignorepm'] != '{ALL}' && $banpm = $m['ignorepm'];

		$membersql[] = "(".$m['uid'].",'".$m['username']."','".$m['password']."','".$m['email']."','".$groupid."','".$userface."','".$m['gender']."','".$m['regdate']."','".$m['sightml']."','".$introduce."','".$m['qq']."','".$m['icq']."','".$m['msn']."','".$m['yahoo']."','".$m['site']."','".$m['location']."','".$m['customstatus']."','".$m['bday']."','".$timedf."','".$m['tpp']."','".$m['ppp']."','".$newpm."','$banpm','$medals','".$userstatus."','".$m['salt']."')";
		$memdatasql[] = "(".$m['uid'].",'".$m['posts']."','".$m['digestposts']."','".$rvrc."','".$money."','".$credit."','".$currency."','".$m['lastvisit']."','".$m['lastactivity']."','".$m['lastpost']."','".intval($m['total']*60)."','".intval($m['thismonth']*60)."')";
		$s_c++;
	}

	//会员处理
	if(!empty($membersql)){
		$membersqlstr = implode(",",$membersql);
		!empty($membersqlstr) && $DDB->update("REPLACE INTO {$pw_prefix}members (uid,username,password,email,groupid,icon,gender,regdate,signature,introduce,oicq,icq,msn,yahoo,site,location,honor,bday,timedf,t_num,p_num,newpm,banpm,medals,userstatus,salt) VALUES $membersqlstr ");
	}
	if(!empty($memdatasql)){
		$memdatastr = implode(",",$memdatasql);
		!empty($memdatastr) && $DDB->update("REPLACE INTO {$pw_prefix}memberdata (uid,postnum,digests,rvrc,money,credit,currency,lastvisit,thisvisit,lastpost,onlinetime,monoltime) VALUES $memdatastr ");
	}

	$maxid = $UCDB->get_value("SELECT max(uid) FROM {$uc_db_prefix}members");
	empty($lastid) && $lastid = $end;
	
	echo "最大id：".$maxid."最后id：".$lastid;
	if ($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c.'&medal='.$medal);
	}else{
		report_log();
		newURL($step);
	}
}elseif ($step == '37'){
    //评分
	if(empty($start)){
		$DDB->query("TRUNCATE TABLE {$pw_prefix}pinglog");
	}
	require_once S_P.'tmp_credit.php';//多个
    $pinglogarr=array();//评分日志，7.3新增
    $ifmarkcount=0;
    $ifmark='';

    $query = $SDB->query("SELECT r.*,p.first,p.tid,p.fid 
    					  FROM {$source_prefix}ratelog r 
    					  LEFT JOIN {$source_prefix}posts p USING(pid) 
    					  LIMIT $start, $percount");
    $goon = 0;
    while ($rate = $SDB->fetch_array($query)){
        $goon++;
		if(!$rate['tid'] || !$rate['fid']){
			$rate['tid'] = 0;
			$rate['fid'] = 0;
		}
		if($rate['first'] == 1){
			$rate['pid'] = 0;
		}
        $ifmarkcount=0;
        $nameid = $rate['extcredits'];
        $scoret = $rate['score'];
        if($rate['score'] > 0){
            $scoret = "+" . $rate['score'];
        }
        $ifmark .= $pingcredit[$nameid] . ":" . $scoret . "(" . addslashes($rate['username']) . ")" . addslashes($rate['reason']) . "\t";
        $ifmarkcount += $rate['score'];
        $pinglogarr[] = "(" . $rate['fid'] . "," . $rate['tid'] . "," . $rate['pid'] . ",'" . $pingcredit[$nameid] . "','".$rate['score'] . "','" . addslashes($rate['username'])."','".addslashes($rate['reason'])."',".$rate['dateline'].")";

    }
    $SDB->free_result($query);

    if($pinglogarr){
        $pinglogstr = implode(",",$pinglogarr);
        $DDB->update("INSERT INTO {$pw_prefix}pinglog (fid,tid,pid,name,point,pinger,record,pingdate) VALUES $pinglogstr");
    }

	if ($goon == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else{
		report_log();
		newURL($step);
	}
}elseif ($step == '38'){
	if(!$start){
		$DDB->query("UPDATE {$pw_prefix}threads SET ifmark=''");
		$DDB->query("UPDATE {$pw_prefix}tmsgs SET ifmark=''");
		$DDB->query("UPDATE {$pw_prefix}posts SET ifmark=''");
	}
    $query = $DDB->query("SELECT * FROM {$pw_prefix}pinglog WHERE id>$start LIMIT $percount");
    unset($lastid);
    while ($rt = $DDB->fetch_array($query)){
        $lastid = $rt['id'];
        $ids = '';
        $ifmark_threads = $DDB->get_value("SELECT ifmark FROM {$pw_prefix}threads WHERE tid=".$rt['tid']);
        $ifmark_threads = $ifmark_threads+$rt['point'];
        $DDB->update("UPDATE {$pw_prefix}threads SET ifmark = '$ifmark_threads' WHERE tid=".$rt['tid']);
        if($rt['pid']==0){
            $ifmark_tmsgs = $DDB->get_value("SELECT ifmark FROM {$pw_prefix}tmsgs WHERE tid=".$rt['tid']);
            if(strpos($ifmark_tmsgs,":")){
                list($num,$ids) = explode(":",$ifmark_tmsgs);
            }
            if(!$ids){
                $num = 1;
                $ids = $rt['id'];
                $ifmark_tmsgs = $num.":".$ids;
            }else{
                $idsarr = explode(",",$ids);
                if(!in_array($rt['id'],$idsarr)){
                    $num++;
                    $ids .= ','.$rt['id'];
                    $ifmark_tmsgs = $num.":".$ids;
                }
            }
            $DDB->update("UPDATE {$pw_prefix}tmsgs SET ifmark = '$ifmark_tmsgs' WHERE tid=".$rt['tid']);
        }else{
            $ifmark_posts = $DDB->get_value("SELECT ifmark FROM {$pw_prefix}posts WHERE pid=".$rt['pid']);
            if(strpos($ifmark_posts,":")){
                list($num,$ids) = explode(":",$ifmark_posts);
            }
            if(!$ids){
                $num = 1;
                $ids = $rt['id'];
                $ifmark_posts = $num.":".$ids;
            }else{
                $idsarr = explode(",",$ids);
                if(!in_array($rt['id'],$idsarr)){
                    $num++;
                    $ids .= ','.$rt['id'];
                    $ifmark_posts = $num.":".$ids;
                }
            }
            $DDB->update("UPDATE {$pw_prefix}posts SET ifmark = '$ifmark_posts' WHERE pid=".$rt['pid']);
        }
    }
    $DDB->free_result($query);
    $maxid = $DDB->get_value("SELECT max(id) FROM {$pw_prefix}pinglog");
    empty($lastid) && $lastid = $end;
    
    echo '最大id',$maxid,'<br>','最后id',$lastid;
    if($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else{
		report_log();
		newURL($step);
	}
}elseif ($step == '39'){
	newURL($step);
}elseif ($step == '40'){
    //分类信息框架
    @include_once(S_P."lang_topicmodel.php");
    $threadtypes = $modelarray1 = $modelarray2 = $modelarray3 = array();

    $sort_type_field = 'typeid';
    $query = $SDB->query("SHOW COLUMNS FROM {$source_prefix}typevars");
    while ($mc = $SDB->fetch_array($query)){
        if (strpos(strtolower($mc['Field']), 'sortid') !== FALSE){
            $sort_type_field = 'sortid';
        }
    }

    $query = $SDB->query("SELECT * FROM {$source_prefix}threadtypes WHERE special=1");
    while ($type = $SDB->fetch_array($query)){
        $flag++;
        $type['name'] = strip_tags($type['name']);
        $DDB->update("INSERT INTO {$pw_prefix}topiccate (name,ifable,vieworder,ifdel) VALUES('".$type['name']."',1,".$type['displayorder'].",1)");
        $cateid = $DDB->insert_id();
        $DDB->update("INSERT INTO {$pw_prefix}topicmodel  (name,cateid,ifable,vieworder) VALUES('".$type['name']."',$cateid,1,".$type['displayorder'].")");
        $modelid = $DDB->insert_id();

        $modelarray1[$type['typeid']] = $modelid;
        $modelarray2[] = $type['typeid'];

        $charset = $dest_charset;//编码
        $createsql = "CREATE TABLE ".$pw_prefix."topicvalue".intval($modelid)." (`tid` mediumint(8) unsigned NOT NULL default '0',`fid` SMALLINT( 6 ) UNSIGNED NOT NULL DEFAULT  '0',`ifrecycle` tinyint(1) unsigned NOT NULL default '0',PRIMARY KEY  (`tid`))";
        if ($DDB->server_info() >= '4.1') {
            $extra = " ENGINE=MyISAM".($charset ? " DEFAULT CHARSET=$charset" : '');
        } else {
            $extra = " TYPE=MyISAM";
        }
        $createsql = $createsql.$extra;
        $DDB->query($createsql);

        //选项cdb_typevars
        $query2 = $SDB->query("SELECT o.* FROM {$source_prefix}typevars v LEFT JOIN {$source_prefix}typeoptions o USING(optionid) WHERE v.{$sort_type_field} = ".$type['typeid']);
        while ($o = $SDB->fetch_array($query2)){
            if($o['type']=='calendar'){
                $o['type'] = 'text';
            }
            $ruletmp = unserialize($o['rules']);

            if($ruletmp['choices']){
                $ruletmp['choices'] = str_replace('\"\"',"0",$ruletmp['choices']);
                $ruletmp = explode("\r\n",$ruletmp['choices']);
                $o['rules'] = serialize($ruletmp);
            }
            $DDB->update("INSERT INTO {$pw_prefix}topicfield SET ".pwSqlSingle(array('name'=>$o['title'],'modelid' => $modelid,'type'=>$o['type'],'rules'=>$o['rules'],'descrip'=>$o['description'],'ifmust'=>$o['required'],'vieworder'=>$o['displayorder'])));
            $fieldid = $DDB->insert_id();
            $fieldname = 'field'.$fieldid;
            $tablename = $pw_prefix.'topicvalue'.intval($modelid);
            $DDB->update("UPDATE ".$pw_prefix."topicfield SET fieldname=".pwEscape($fieldname)." WHERE fieldid=".pwEscape($fieldid));
            $sql = getFieldSqlByType($fieldtype);
            $DDB->query("ALTER TABLE $tablename ADD $fieldname $sql");
            $modelarray3[$type['typeid']][$o['optionid']] = $fieldname;
        }
    }
	writeover(S_P.'tmp_model.php', "\$_model = ".pw_var_export($modelarray1).";\$_model2 = ".pw_var_export($modelarray2).";\$_model3 = ".pw_var_export($modelarray3).";", true);
	newURL($step);
}elseif ($step == '41'){
	require_once S_P.'tmp_model.php';

    $lastid = $start;
	$query = $SDB->query("SELECT * FROM {$source_prefix}threads WHERE tid > $start ORDER BY tid LIMIT $percount");
	while ($v = $SDB->fetch_array($query)){
        $lastid = $v['tid'];
        if(!$v['sortid']) $v['sortid'] = $v['typeid'];
        if(!in_array($v['sortid'],$_model2)){//sortid
            continue;
        }
        $optionlist = array();
        $query2 = $SDB->query("SELECT * FROM {$source_prefix}typeoptionvars WHERE tid =".$v['tid']."");
        while($info = $SDB->fetch_array($query2)) {
            if($info['value']){
                $info['value'] = addslashes($info['value']);
                if(!$info['optionid'])continue;
                preg_match("/^[0-9]{4}-(\d+)-(\d+)$/i", $info['value'], $arr);
                if($arr[0]){
                    $info['value'] = strtotime($arr[0]);
                }
                if(!$info['sortid']) $info['sortid'] = $info['typeid'];
                $optionlist[$info['sortid']][$info['tid']][$info['optionid']] = $info['value'];
            }
        }
        $sql1 = $sql2 = array();
        if(is_array($optionlist[$v['sortid']][$v['tid']])){
            foreach($optionlist[$v['sortid']][$v['tid']] as $key => $val){
                if(!empty($optionlist[$v['sortid']][$v['tid']])){
            			$sql1[] = $_model3[$v['sortid']][$key];
                	$sql2[] = $val;
          			}
            }
        }
        if(($sql1)){
            $DDB->update("REPLACE INTO {$pw_prefix}topicvalue".$_model[$v['sortid']]."(tid,fid,".implode(",",$sql1).") VALUES(".$v['tid'].",".$v['fid'].",".pwImplode($sql2).")");
            $DDB->update("UPDATE {$pw_prefix}threads SET modelid=".$_model[$v['sortid']]." WHERE tid=".$v['tid']);
            $s_c++;
        }
    }
	$maxid = $SDB->get_value("SELECT max(tid) FROM {$source_prefix}threads");
	if ($lastid < $maxid){
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}else{
		report_log();
		newURL($step);
	}
}elseif ($step == '42'){
	//好友
	if(!file_exists(S_P."tmp_uch.php")){
		newURL($step);
	}else{
		require(S_P."tmp_uch.php");
	    $charset_change = 1;
	    $UCHDB = new mysql($uch_db_host, $uch_db_user, $uch_db_password, $uch_db_name, '');
	}
	$goon = 0;
	$query = $UCHDB->query("SELECT uid,fuid FROM {$uch_db_prefix}friend LIMIT $start, $percount");

	//好友好像没有添加时间,也没有验证状态
	while($f = $UCHDB->fetch_array($query)){
		$DDB->update("REPLACE INTO {$pw_prefix}friends (uid,friendid,descrip,iffeed) VALUES (".$f['uid'].",".$f['fuid'].",'',1)");
		$DDB->update("REPLACE INTO {$pw_prefix}friends (friendid,uid,descrip,iffeed) VALUES (".$f['uid'].",".$f['fuid'].",'',1)");
		$goon ++;
		$s_c ++;
	}
	if ($goon == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else{
		report_log();
		newURL($step);
	}
}elseif($step == '43'){
	//空间留言
	if(!file_exists(S_P."tmp_uch.php")){
		newURL($step);
	}else{
		require(S_P."tmp_uch.php");
	    $charset_change = 1;
	    $UCHDB = new mysql($uch_db_host, $uch_db_user, $uch_db_password, $uch_db_name, '');
	}
    if(!$start){
		$DDB->query("TRUNCATE TABLE {$pw_prefix}oboard");
	}
	$query = $UCHDB->query("SELECT * FROM {$uch_db_prefix}comment WHERE idtype='uid' LIMIT $start, $percount");
	$goon = 0;
	while ($rt = $UCHDB->fetch_array($query)) {
		$goon++;
		$s_c ++;
		$uid		=	$rt['authorid'];
		$username	=	$rt['author'];
        $touid	=	$rt['uid'];
		$title		=	$rt['message'];
		$postdate	=	$rt['dateline'];
		unset($oboarddb);

		$oboarddb = array(
			'uid'=> $uid,
			'username'=> $username,
            'touid'=> $touid,
			'title'=> $title,
			'postdate'=> $postdate,
            'ifwordsfb'=>1
		);

		$DDB->update("INSERT INTO {$pw_prefix}oboard SET".pwSqlSingle($oboarddb));
	}

	if ($goon == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}else{
		$maxid = $UCHDB->get_value("SELECT max(cid) FROM {$uch_db_prefix}comment WHERE idtype='uid'");
		report_log();
		newURL($step);
	}
}else{
	copy(S_P."tmp_report.php",S_P."report.php");//复制一份文件
	if(!file_exists(S_P."tmp_uch.php")){P_unlink(S_P."tmp_uch.php");}
	ObHeader($basename.'?action=finish&dbtype='.$dbtype);
}

##########################

function getFieldSqlByType($type) {
	if (in_array($type,array('number','calendar'))) {
		$sql = "INT(10) UNSIGNED NOT NULL default '0'";
	} elseif (in_array($type,array('radio','select'))){
		$sql = "TINYINT(3) UNSIGNED NOT NULL default '0'";
	} elseif ($type == 'textarea') {
		$sql = "TEXT NOT NULL";
	} else {
		$sql = "VARCHAR(255) NOT NULL";
	}
	return $sql;
}
function update_markinfo($fid, $tid, $pid) {
	global $DDB,$pw_prefix;
	$perpage = 10;
	$pid = intval($pid);
	$whereStr = " fid=".pwEscape($fid)." AND tid=".pwEscape($tid)." AND pid=" . pwEscape($pid) . " AND ifhide=0 ";
	$count = $DDB->get_value("SELECT COUNT(*) FROM {$pw_prefix}pinglog WHERE $whereStr ");
	$markInfo = "";
    $ifmarkcount=0;
	if ($count) {
		$query = $DDB->query("SELECT id,point FROM {$pw_prefix}pinglog WHERE $whereStr ORDER BY pingdate DESC LIMIT 0,$perpage");
		$ids = array();
		while ($rate = $DDB->fetch_array($query)) {
			$ids[] = $rate['id'];
            $ifmarkcount += $rate['point'];
		}
		$markInfo = $count . ":" . implode(",", $ids);
	}
	if ($pid == 0) {
		//$pw_tmsgs = GetTtable($tid);
		$pw_tmsgs = "{$pw_prefix}tmsgs";
		$DDB->update("UPDATE {$pw_prefix}threads SET ifmark=" . $ifmarkcount . " WHERE tid=" . pwEscape($tid));
		$DDB->update("UPDATE $pw_tmsgs SET ifmark=" . pwEscape($markInfo) . " WHERE tid=" . pwEscape($tid));
	} else {
		$DDB->update("UPDATE {$pw_prefix}posts SET ifmark=".pwEscape($markInfo)." WHERE pid=".pwEscape($pid));
	}
	return $markInfo;
}

function changegroups()
{
	global $pw_prefix, $source_prefix, $SDB, $DDB, $dest_charset;
	require_once S_P.'lang_'.$dest_charset.'.php';
	$DDB->update("TRUNCATE TABLE {$pw_prefix}usergroups");

	$DDB->update($lang['group']);//创建系统默认组
	$grelation = array(1=>3, 2=>4, 3=>5, 4=>6, 5=>6, 6=>6, 7=>2, 8=>7);//系统组GID

	$query = $SDB->query("SELECT * FROM {$source_prefix}usergroups WHERE type = 'member' OR type = 'special'");
	$specialdata = array();
	while ($g = $SDB->fetch_array($query))
	{
		$gid 			= $g['groupid'];
		$gptype 		= $g['type'];
		$grouptitle 	= addslashes($g['grouptitle']);
		$groupimg 		= 8;
		$grouppost 		= (int)$g['creditshigher'];
		$maxmsg 		= (int)$g['maxpmnum'];
		$allowhide 		= $g['allowinvisible'];
		$allowread 		= $g['readaccess'] ? 1 : 0;
		$allowportait 	= $g['allowavatar'] ? 1 : 0;
		$upload 		= $g['allowavatar'] == 3 ? 1 : 0;
		$allowrp 		= $g['allowreply'];
		$allowhonor 	= $g['allownickname'];//个性签名-昵称
		$allowdelatc 	= 1;
		$allowpost 		= $g['allowpost'];
		$allownewvote 	= $g['allowpostpoll'];
		$allowvote 		= $g['allowvote'];
		$allowactive 	= $g['allowpostactivity'];
		$htmlcode 		= $g['allowhtml'];
		$wysiwyg 		= 0;
		$allowhidden 	= $g['allowhidecode'];
		$allowencode 	= $g['allowsetreadperm'];
		$allowsell 		= $g['maxprice'] ? 1 : 0;
		$allowsearch 	= $g['allowsearch'];
		$allowmember 	= 1;
		$allowprofile 	= $g['allowviewpro'];
		$allowreport 	= 1;
		$allowmessege 	= $g['maxpmnum'] ? 1 : 0;
		$allowsort 		= $g['allowviewstats'];
		$alloworder 	= 1;
		$allowupload 	= $g['allowpostattach'] ? 2 : 0;
		$allowdownload 	= $g['allowgetattach'] ? 2 : 0;
		$allowloadrvrc 	= 1;
		$allownum 		= 50;
		$edittime 		= 0;
		$postpertime 	= 0;
		$searchtime 	= 0;
		$signnum 		= $g['maxsigsize'];
		$uploadtype 	= $mright = array();

		if ($g['attachextensions'])
		{
			$attachext = explode(',', $g['attachextensions']);
			foreach($attachext as $v)
			{
				$uploadtype[trim(strtolower($v))] = 1000;
			}
		}
		$mright['atclog'] = $mright['show'] = $mright['msggroup'] = $mright['ifmemo'] = $mright['modifyvote'] = $mright['viewvote'] = $mright['allowreward'] = $mright['allowencode'] = $mright['leaveword'] = $mright['viewvote'] = $mright['viewvote'] = 1;
		$mright['viewipfrom'] = $mright['anonymous'] = $mright['dig'] = $mright['atccheck'] = $mright['markable'] = $mright['postlimit'] = 0;
		$mright['imgwidth'] = $mright['imgheight'] = $mright['fontsize'] = $mright['maxsendmsg'] = $mright['maxfavor'] = $mright['maxgraft'] = '';
		$mright['uploadtype'] = $uploadtype ? addslashes(serialize($uploadtype)) : '';
		$mright['media']  = $mright['pergroup'] = '';
		$mright['markdb'] = "10|0|10||1";
		$mright['schtime'] = 'all';
		$mright = P_serialize($mright);
		$ifdefault = 0;
		$allowadmincp = $visithide = $delatc = $moveatc = $copyatc = $typeadmin = $viewcheck = $viewclose = $attachper = $delattach = $viewip = $markable = $maxcredit = $credittype = $creditlimit = $banuser = $bantype = $banmax = $viewhide = $postpers = $atccheck = $replylock = $modown = $modother = $deltpcs = 0;
		$sright = '';

		pwGroupref(array('gid'=>$gid,'gptype'=>$gptype,'grouptitle'=>$grouptitle,'groupimg'=>$groupimg,
		'grouppost'=>$grouppost,'maxmsg'=>$maxmsg,'allowhide'=>$allowhide,'allowread'=>$allowread,
		'allowportait'=>$allowportait,'upload'=>$upload,'allowrp'=>$allowrp,'allowhonor'=>$allowhonor,
		'allowdelatc'=>$allowdelatc,'allowpost'=>$allowpost,'allownewvote'=>$allownewvote,
		'allowvote'=>$allowvote,'allowactive'=>$allowactive,'htmlcode'=>$htmlcode,'wysiwyg'=>$wysiwyg,
		'allowhidden'=>$allowhidden,'allowencode'=>$allowencode,'allowsell'=>$allowsell,
		'allowsearch'=>$allowsearch,'allowmember'=>$allowmember,'allowprofile'=>$allowprofile,
		'allowreport'=>$allowreport,'allowmessage'=>$allowmessege,'allowsort'=>$allowsort,
		'alloworder'=>$alloworder,'allowupload'=>$allowupload,'allowdownload'=>$allowdownload,
		'allowloadrvrc'=>$allowloadrvrc,'allownum'=>$allownum,'edittime'=>$edittime,
		'postpertime'=>$postpertime,'searchtime'=>$searchtime,'signnum'=>$signnum,'mright'=>$mright,
		'ifdefault'=>$ifdefault,'allowadmincp'=>$allowadmincp,'visithide'=>$visithide,'delatc'=>$delatc,
		'moveatc'=>$moveatc,'copyatc'=>$copyatc,'typeadmin'=>$typeadmin,'viewcheck'=>$viewcheck,
		'viewclose'=>$viewclose,'attachper'=>$attachper,'delattach'=>$delattach,'viewip'=>$viewip,
		'markable'=>$markable,'maxcredit'=>$maxcredit,'credittype'=>$credittype,'creditlimit'=>$creditlimit,
		'banuser'=>$banuser,'bantype'=>$bantype,'banmax'=>$banmax,'viewhide'=>$viewhide,'postpers'=>$postpers,
		'atccheck'=>$atccheck,'replylock'=>$replylock,'modown'=>$modown,'modother'=>$modother,
		'deltpcs'=>$deltpcs,'sright'=>$sright));

		$grouptitle=getGrouptitle($gid,$grouptitle,false);
		$DDB->update("INSERT INTO {$pw_prefix}usergroups (gid,gptype,grouptitle,groupimg,grouppost) VALUES ('$gid','$gptype','$grouptitle','$groupimg','$grouppost')");

		if ($g['type'] == 'special')
		{
			$specialdata[$g['groupid']] = '1';
		}

		$grelation[$g['groupid']] = $g['groupid'];//所有的用户组存到临时文件
	}
	writeover(S_P.'tmp_grelation.php', "\$_grelation = ".pw_var_export($grelation).";", true);
	return $specialdata;
}

function getupadmin($fid, &$upadmin)
{
	global $catedb;
	if ($catedb[$fid]['moderators'])
	{
		$moderators = explode("\t", $catedb[$fid]['moderators']);
		foreach($moderators as $value)
		{
			$upadmin .= $upadmin ? addslashes($value).',' : ','.addslashes($value).',';
		}
	}
	if ($catedb[$fid] && $catedb[$fid]['type'] != 'group')
	{
		getupadmin($catedb[$fid]['fup'], $upadmin);
	}
}

function dz_ubb($content)
{
	$content = str_replace(array('[wma]','[/wma]','[flash]','[swf]','[/swf]','[rm]','[ra]','[php]','[/php]','[/ra]','[wmv]','[mp3]','[/mp3]','[audio]','[/audio]','[i=s]'),array('[wmv=0]','[/wmv]','[flash=314,256,1]','[flash=314,256,1]','[/flash]','[rm=314,256,1]','[rm=314,256,1]','[code]','[/code]','[/rm]','[wmv=314,256,1]','[wmv=1]','[/wmv]','[wmv=1]','[/wmv]','[i]'),$content);
	$content = preg_replace(array('~\[code\](.+?)\[\/code\]~ies','~\[media=mp3,\d+?,\d+?,(?:1|0)\](.+?)\[\/media\]~i','~\[media=(?:wmv|mov|wma),(\d+?),(\d+?),(1|0)\](.+?)\[\/media\]~i','~\[media=(rm|ra),(\d+?),(\d+?),(1|0)\](.+?)\[\/media\]~i','~\[media=swf,(\d+?),(\d+?)\](.+?)\[\/media\]~i','~\[hide=(\d+?)\](.+?)\[\/hide\]~is','~\[hide\](.+?)\[\/hide\]~is','~\[localimg=[0-9]+,[0-9]+\]([0-9]+)\[\/localimg\]~is','~\[local\]([0-9]+)\[\/local\]~is','~\[attach\]([0-9]+)\[\/attach\]~is','/\[img=[0-9]+,[0-9]+\]/i','/\[size=(\d+(\.\d+)?(px|pt|in|cm|mm|pc|em|ex|%)+?)\]/i','/\[p=(\d+)\,(\s+\d+)\,(\s+)(left|center|right|justify)\](.+?)\[\/p\]/is'),array("ccode('\\1')",'[wmv=0]\\1[/wmv]','[wmv=\\1,\\2,\\3]\\4[/wmv]','[rm=\\2,\\3,\\4]\\5[/rm]','[flash=\\1,\\2,1]\\3[/flash]','[sell=\\1]\\2[/sell]','[post]\\1[/post]','[attachment=\\1]','[attachment=\\1]','[attachment=\\1]','[img]','','<p align=\"\\4\">\\5</p>'),$content);
	return $content;
}

function ccode($code)
{
	return htmlspecialchars($code);
}

function PWListDir($root, &$dirname)
{
	$real = true;
	$rs = opendir($root);
	while (($file = readdir($rs)) !== FALSE)
	{
		$tmp = $root.'/'.$file;
		if ($file != '..' && $file != '.' && is_dir($tmp))
		{
			$real = false;
			PWListDir($tmp, $dirname);
		}
	}
	$real && $dirname[] = $root;
	closedir($rs);
	return;
}

function GetImgSize($srcFile){
	$srcdata = array();
	if (function_exists('read_exif_data')) {
		$datatemp = @read_exif_data($srcFile);
		$srcdata['width'] = $datatemp['COMPUTED']['Width'];
		$srcdata['height'] = $datatemp['COMPUTED']['Height'];
		unset($datatemp);
	}
	!$srcdata['width'] && list($srcdata['width'],$srcdata['height'],) = @getimagesize($srcFile);
	return $srcdata;
}

function getUsernameByUid($uid)
{
	global $DDB,$pw_prefix;
	$username = $DDB->get_value("SELECT username FROM {$pw_prefix}members WHERE uid='$uid'");
	return $username;
}

function getPicCommentNum($pid)
{
	global $UCHDB,$uch_db_prefix;
	$num = $UCHDB->get_value("SELECT COUNT(*) AS num FROM {$uch_db_prefix}comment WHERE idtype='picid' AND id=".pwEscape($pid));
	return $num;
}

//取得评论的类型
function getCommentType($typeid) {
	switch ($typeid) {
		case 'blogid' :
			$type = 'diary';break;
		case 'picid' :
			$type = 'photo';break;
		case 'sid' :
			$type = 'share';break;
	}
	return $type;
}

function getThreadInfo($tid,$type) //TODO
{
	global $UCHDB,$uch_db_prefix;

	$thread_info = $UCHDB->get_one("SELECT lastpost,subject FROM {$uch_db_prefix}thread WHERE tid=".pwEscape($tid));
	switch ($type)
	{
		case 'lastpost':
			$return = $thread_info['lastpost'];
			break;
		case 'subject':
			$return = addslashes($thread_info['subject']);
			echo $return;exit();
		default:
			$return = '';
			break;
	}
	return $return;
}
function getShareType($type) {
	switch ($type) {
		case 'link':
			$type = 'web';break;
		case 'pic':
			$type = 'photo';break;
		case 'mtag':
			$type = 'group';break;
		case 'thread':
			$type = '';break;
		case 'space':
			$type = 'user';break;
		case 'tag':
			$type = '';break;
		case 'blog':
			$type = 'diary';break;
		default:
			break;
	}
	return $type;
}

function getShareContent($body_data,$body_general,$type,$uid,$username,$image,$image_link) {
	global $db_bbsurl;
	$body_data_array = unserialize($body_data);
	$content = array();
	if ($type == 'space') {

		preg_match("/\<a href=\"space\.php\?uid=(\d+)\"\>(.+)\<\/a\>/is",$body_data_array['username'],$matches);
		$content['user']['username'] = $matches[2];
		$content['user']['image']	= '';//?????????????????
		$content['link'] = $db_bbsurl.'/mode.php?m=o&q=user&u='.$matches[1];

	} elseif ($type == 'link') {

		$content['link'] = $body_data_array['data'];
		$content['type'] = 'web';

	} elseif ($type == 'video') {

		$content['link'] = $body_data_array['data'];
		$content['type'] = 'video';
		list($content['video']['hash'],$content['video']['host']) = getHash($content['link']);

	} elseif ($type == 'music') {

		$content['link'] = $body_data_array['data'];
		$content['type'] = 'music';

	} elseif ($type == 'flash') {

		$content['link'] = $body_data_array['data'];
		$content['type'] = 'flash';

	} elseif ($type == 'blog') {
		preg_match("/\<a href=\"space\.php\?uid=(\d+)&do=blog&id=(\d+)\"\>(.+)\<\/a\>/is",$body_data_array['subject'],$matches);
		$content['diary']['subject'] = $matches[3];
		$content['link'] = $db_bbsurl.'/mode.php?m=o&q=diary&u='.$matches[1].'&did='.$matches[2];
		//待小均做完日志
	} elseif ($type == 'album') {

		preg_match("/\<a href=\"space\.php\?uid=(\d+)&do=album&id=(\d+)\"\>(.+)\<\/a\>/is",$body_data_array['albumname'],$matches);
		$content['album']['uid'] = $uid;
		$content['album']['username'] = $username;
		$content['album']['image'] = $image;
		$content['link'] = $db_bbsurl.'/mode.php?m=o&q=photos&a=album&aid='.$matches[2];

	} elseif ($type == 'pic') {

		preg_match("/space\.php\?uid=(\d+)&do=album&picid=(\d+)/is",$image_link,$matches);
		$content['photo']['uid'] = $uid;
		$content['photo']['username'] = $username;
		$content['photo']['image'] = $image;
		$content['link'] = $db_bbsurl.'/mode.php?m=o&q=photos&a=view&pid='.$matches[2];

	} elseif ($type == 'mtag') {

		//貌似功能漏做了

	} elseif ($type == 'thread') {

		return '';

	} elseif ($type == 'tag') {

		return '';
	}

	$content['descrip']	= $body_general;
	return serialize($content);
}

function getHash($link) {
	$parselink = parse_url($link);
	preg_match("/(youku.com|youtube.com|5show.com|ku6.com|sohu.com|sina.com.cn)$/i",$parselink['host'],$hosts);
	switch ($hosts[1]) {
		case 'youku.com':
			preg_match("/id\_(\w+)\=/",$link,$matches);
			break;
		case 'ku6.com':
			preg_match("/\/([\w\-]+)\.html/",$link,$matches);
			break;
		case 'youtube.com':
			preg_match("/v\=([\w\-]+)/",$link,$matches);
			break;
		case 'sina.com.cn':
			preg_match("/\/(\d+)-(\d+)\.html/",$link,$matches);
			break;
		case 'sohu.com':
			preg_match("/\/(\d+)\/*$/",$link,$matches);
			break;
	}
	if(!empty($matches[1])) {
		$return = $matches[1];
	} else {
		$return = '';
	}
	return array($return,$hosts[1]);
}
//统计分类中日志数
function getDiaryNum($classid) {
	global $UCHDB,$uch_db_prefix;
	$count = $UCHDB->get_value("SELECT COUNT(*) FROM {$uch_db_prefix}blog WHERE classid=".pwEscape($classid));
	return $count;
}


//取得附件类型
function getfileinfo($filename = '')
{
	$extnum		=	strrpos($filename, '.') + 1;
	$file_ext	=	strtolower(substr($filename, $extnum));
	switch ($file_ext)
	{
		case 'jpg':
			$fileinfo['type'] = 'img';
			$fileinfo['ifupload'] = 1;
		break;
		case 'jpe':
			$fileinfo['type'] = 'img';
			$fileinfo['ifupload'] = 1;
		break;
		case 'jpeg':
			$fileinfo['type'] = 'img';
			$fileinfo['ifupload'] = 1;
		break;
		case 'gif':
			$fileinfo['type'] = 'img';
			$fileinfo['ifupload'] = 1;
		break;
		case 'bmp':
			$fileinfo['type'] = 'img';
			$fileinfo['ifupload'] = 1;
		break;
		case 'png':
			$fileinfo['type'] = 'img';
			$fileinfo['ifupload'] = 1;
		break;
		case 'rar':
			$fileinfo['type'] = 'zip';
			$fileinfo['ifupload'] = 3;
		break;
		case 'zip':
			$fileinfo['type'] = 'zip';
			$fileinfo['ifupload'] = 3;
		break;
		case 'txt':
			$fileinfo['type'] = 'txt';
			$fileinfo['ifupload'] = 2;
		break;
		default:
			$fileinfo['type'] = 'zip';
			$fileinfo['ifupload'] = 3;
		break;
	}
	return $fileinfo;
}

###########################  版块转换相关函数  #######################

//有游客权限则返回空
function allow_group_str($str)
{
	$arr_str = explode("\t",$str);
	if ('' == $str || is_array($arr_str) == false)
	{
		return '';
	}

	if (strpos($str,'7') === false) //判断是否有游客权限
	{
		return ','.str_replace("\t",',', $str).',';
	}
	else
	{
		return '';
	}
}

//判断是否有子版块
function getIfHasChild($catedb,$fid)
{
	global $catedb;
	foreach ($catedb as $k => $v)
	{
		if ($fid == $v['fup'])
		{
			return 1;
		}
	}
	return 0;
}

//取得forumdata表中lastpost的值
function getLastpost($lastpost)
{
	list($ltid, $ltitle, $ltime, $lauthor) = explode("\t", $lastpost);
	$lastpost = addslashes($ltitle."\t".$lauthor."\t".$ltime."\tread.php?tid=".$ltid);
	return $lastpost;
}

?>
