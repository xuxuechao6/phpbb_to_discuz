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

if ($step == '1')
{
	//会员
	$percount = $percount - 1;
	//require_once (S_P.'tmp_credit.php');
	if (!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}members");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}memberdata");
		//$DDB->query("TRUNCATE TABLE {$pw_prefix}administrators");
	}
	//$insertadmin = $_specialdata = '';
	//require_once (S_P.'tmp_specialdatastr.php');
	$query = $SDB->query("SELECT Top $percount us.UserID,CONVERT(varchar(255), us.Username) AS username,us.Email,us.Gender,us.Signature,
		convert(char,us.CreateDate,20) AS CreateDate,us.TotalOnlineTime,us.MonthOnlineTime,us.Point_1,us.Point_2,us.AvatarSrc,uv.Password,us.TotalTopics,us.Totalposts,CONVERT(varchar(255), d.Content) AS honor
			FROM bx_users AS us LEFT JOIN bx_UserVars AS uv ON us.UserID = uv.UserID LEFT JOIN bx_Doings AS d ON us.UserID = d.UserID
			WHERE us.UserID > $start ORDER BY us.UserID");

	while ($m = $SDB->fetch_array($query))
	{
		ADD_S($m);
		$lastid = $m['UserID'];
		$m['username'] = addslashes($m['username']);
		/*
		if (htmlspecialchars($m['username'])!=$m['username'] || CK_U($m['username']))
		{
			$f_c++;
			errors_log($m[0]."\t".$m['username']);
			continue;
		}*/
		switch ($m['groupid'])
		{
			case '1'://管理员
				$groupid = '3';
				$insertadmin .= "(".$m['uid'].", '".$m['username']."', 3),";
				break;
			case '2'://总版主
				$groupid = '4';
				$insertadmin .= "(".$m['uid'].", '".$m['username']."', 4),";
				break;
			case '3'://版主
				$groupid = '5';
				$insertadmin .= "(".$m['uid'].", '".$m['username']."', 5),";
				break;
			case '4':
			case '5':
			case '6'://禁止发言
				$groupid = '6';
				break;
			case '7'://游客
				$groupid = '2';
				break;
			case '8'://未验证会员
				$groupid = '7';
				break;
			default :
				$groupid = isset($_specialdata[$m['groupid']]) ? $m['groupid'] : '-1';
				break;
		}
		//$userface = $m['avatar'];
		//if($m['avatarwidth']==''){$m['avatarwidth']=100;}
		//if($m['avatarheight']==''){$m['avatarheight']=100;}
        /*
		if ($m['avatar'])
		{
			$avatarpre = substr($m['avatar'], 0, 7);
			switch ($avatarpre)
			{
				case 'http://':
					$userface = $m['avatar'].'|2|'.$m['avatarwidth'].'|'.$m['avatarheight'];
					break;
				case 'avatars':
					$userface = substr($m['avatar'], 8).'|1';
					break;
				case '/avatar':
					$userface = substr($m['avatar'], 9).'|3|'.$m['avatarwidth'].'|'.$m['avatarheight'];
					break;
			}
			$userface = str_replace('\\', '/', $userface);
			if(substr($userface,0,1)=='/')
			{
					$userface = substr($userface,1);
			}
		}*/
		eval($creditdata);
		$expandCreditSQL = '';
		if($expandCredit)
		{
			foreach ($expandCredit as $k => $v)
			{
				$expandCreditSQL .= '('.$m['uid'].','.($k + 2).','.(int)($m[$v[2]]).'),';
			}
			$expandCreditSQL && $DDB->update("INSERT INTO {$pw_prefix}membercredit (uid, cid, value) VALUES ".substr($expandCreditSQL, 0, -1));
		}
		$bday = $m['bday'] ? date('Y-m-d', strtotime($m['bday'])) : '0000-00-00';
		$signchange = (convert($m['Signature']) == $m['Signature']) ? 1 : 2;
		$m['userstatus']=($signchange-1)*256+128+$m['showemail']*64+4;//用户位状态设置
		$medals = $medal ? str_replace("\t", ',', $m['medals']) : '';
		$m['CreateDate'] = strtotime($m['CreateDate']);
		if(strlen($m['password']) > 16)
		{
			$m['Password'] = substr($m['password'],8,16);
		}
        $m['Password'] = strtolower($m['Password']);
        $m['posts'] = $m['TotalTopics']+$m['TotalPosts'];
        //$total = ($m['Point_1']+$m['Point_2']) * 5  + $m['posts'] * 10;

/*
        if($total>=10000000){
            $memberid = 17;
        }elseif($total>=5000000){
            $memberid = 16;
        }elseif($total>=100000){
            $memberid = 15;
        }elseif($total>=30000){
            $memberid = 14;
        }elseif($total>=20000){
            $memberid = 13;
        }elseif($total>=10000){
            $memberid = 12;
        }elseif($total>=5000){
            $memberid = 11;
        }elseif($total>=1000){
            $memberid = 10;
        }elseif($total>=100){
            $memberid = 9;
        }else{
            $memberid = 8;
        }
        */
        $memberid = 8;
		$m['Point_2'] = $m['Point_2']*10;
        $userface = '';
        if($m['AvatarSrc']!='' && $m['AvatarSrc']!=' '){
            $userface = 'http://bbs.ttx.cn/UserFiles/A/B/'.$m['AvatarSrc'].'|2|200|150';
        }
        //if($m['UserID']==577397){
            //echo $userface;exit;
        //}
        //if($m['UserID']==177217){
//echo $userface;exit;
        //}
		$membersql[]  = "(".$m['UserID'].",'".$m['username']."','".$m['Password']."','".$m['Email']."',".$groupid.",".$memberid.",'','".$userface."',".$m['Gender'].",".$m['CreateDate'].",'".$m['Signature']."','".$introduce."','".$m['qq']."','".$m['icq']."','".$m['msn']."','".$m['yahoo']."','".$m['site']."','".$m['location']."','".$m['honor']."','".$m['bday']."','".$timedf."','".$m['tpp']."','".$m['ppp']."',0,'$banpm','$medals','".$userstatus."')";
		$memdatasql[] = "(".$m['UserID'].",'".$m['posts']."','".$m['digestposts']."','".$m['Point_2']."','".$m['Point_1']."','".$credit."','".$currency."','".$m['lastvisit']."','".$m['lastactivity']."','','".intval($m['TotalOnlineTime']*60)."','".intval($m['MonthOnlineTime']*60)."')";

		$s_c++;
	}

    //会员处理
    if($membersql)
    {
        $membersqlstr = implode(",",$membersql);
        $DDB->update("REPLACE INTO {$pw_prefix}members (uid,username,password,email,groupid,memberid,groups,icon,gender,regdate,signature,introduce,oicq,icq,msn,yahoo,site,location,honor,bday,timedf,t_num,p_num,newpm,banpm,medals,userstatus) VALUES $membersqlstr ");
    }
    if($memdatasql)
    {
        $memdatastr = implode(",",$memdatasql);
        $DDB->update("REPLACE INTO {$pw_prefix}memberdata (uid,postnum,digests,rvrc,money,credit,currency,lastvisit,thisvisit,lastpost,onlinetime,monoltime) VALUES $memdatastr ");
    }
	$row = $SDB->get_one("SELECT max(Userid) as max FROM bx_users WHERE Userid >= $end");
	echo $row['max'].'<br>'.$lastid;
	if ($row['max'] > $lastid)
	{
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c.'&medal='.$medal);
	}
	else
	{
		report_log();
		newURL($step);
	}
}
elseif ($step == '2')
{
    //用户组权限
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE pw_administrators");
		$DDB->query("UPDATE pw_members SET groupid=-1");
	}
	$query = $SDB->query("SELECT ur.UserID,convert(varchar(255), ur.RoleID) as RoleID,u.Username FROM bx_UserRoles ur LEFT JOIN bx_Users u On ur.UserID = u.UserID");
	//$query = $SDB->query("SELECT ur.UserID,convert(varchar(255), ur.RoleID) as RoleID FROM bx_UserRoles ur ");

	while($rt = $SDB->fetch_array($query))
	{
        //unpack($rt['RoleID'],$a);
        //echo "SELECT gptype,gid FROM pw_usergroups WHERE tmp='".$rt['RoleID']."'";exit;
        //echo "SELECT gptype,gid FROM pw_usergroups WHERE tmp='".$rt['RoleID']."'";
        $g = $DDB->get_one("SELECT gptype,gid FROM pw_usergroups WHERE tmp='".$rt['RoleID']."'");
        $u = $DDB->get_one("SELECT * FROM pw_administrators WHERE uid=".$rt['UserID']);
        if($g['gid']){
        if($u){
            if($u['groups']){
                $u['groups'] = $u['groups'].','.$g['gid'];
            }else{
                $u['groups'] = $g['gid'];
            }
            $DDB->update("UPDATE pw_administrators SET groups = '".$u['groups']."' WHERE uid=".$rt['UserID']);
        }else{
            $DDB->update("UPDATE pw_members SET groupid=".$g['gid']." WHERE uid=".$rt['UserID']);
            $DDB->update("INSERT INTO pw_administrators (uid,username,groupid) VALUES(".$rt['UserID'].",'".$rt['Username']."',".$g['gid'].")");
        }
        }
    }
	newURL($step);
}
elseif ($step == '3')
{
	//板块数据
	$DDB->query("TRUNCATE TABLE {$pw_prefix}forums");
	$DDB->query("TRUNCATE TABLE {$pw_prefix}forumdata");
	$DDB->query("TRUNCATE TABLE {$pw_prefix}announce");
	$query = $SDB->query("SELECT f.ForumId,f.ParentID,ForumType,convert(varchar(255),f.ForumName) AS ForumName,convert(varchar(255),f.Description) AS Description,ColumnSpan,SortOrder,convert(text,Readme) AS Readme FROM bx_forums f");

	while($rt = $SDB->fetch_array($query))
	{
		ADD_S($rt);
		if($rt['ForumId']==-1 || $rt['ForumId']==-2)continue;
		if(!$rt['ParentID']){
			$type = 'category';
		}else{
			$type = 'forum';
		}
		$DDB->update("INSERT INTO {$pw_prefix}forums (fid,fup,ifsub,childid,type,logo,name,descrip,vieworder,forumadmin,fupadmin,across,allowsell,copyctrl,allowpost,allowrp,allowdownload,allowupload,f_check,ifhide,allowtype,t_type) VALUES
		 (".$rt['ForumId'].",".$rt['ParentID'].",'".$ifsub."','".$childid."','".$type."','".addslashes($forum['icon'])."','".$rt['ForumName']."','".$rt['Description']."','".$rt['SortOrder']."','".addslashes($forum['moderators'])."','".$upadmin."','".$rt['ColumnSpan']."','".$forum['disablewatermark']."','".$allowread."','".$allowpost."','".$allowrp."','".$allowdownload."','".$allowupload."','".$rt['modnewposts']."','".$rt['status']."',31,'".addslashes($t_type)."')");
		$DDB->update("INSERT INTO {$pw_prefix}forumdata (fid,tpost,topic,article) VALUES (".$rt['ForumId'].",0,0,0)");
        $DDB->update("INSERT INTO {$pw_prefix}announce(fid,ifopen,subject,content) VALUES(".$rt['ForumId'].",1,'".$rt['ForumName']."的公告','".$rt['Readme']."')");
		$s_c++;
	}
	//writeover(S_P.'tmp_typeinfo.php', "\$_typeinfo='".$ft_typeid."';",true);
	report_log();
	newURL($step);
}
elseif ($step == '4')
{
	$percount = $percount - 1;
	//主题数据
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}threads");
	}
	$query = $SDB->query("SELECT TOP $percount ThreadID,ForumID,ThreadType,convert(varchar(255),Subject) AS Subject,PostUserID,
		convert(varchar(255),PostNickName) AS PostNickName,
		convert(char,CreateDate,20) AS CreateDate,convert(char,UpdateDate,20) AS UpdateDate,LastPostUserID,convert(varchar(255),LastPostNickName) AS LastPostNickName,
		TotalReplies,TotalViews,ThreadStatus,isValued,SubjectStyle
		FROM bx_Threads WHERE ThreadID > $start ORDER BY ThreadID");

	$facearray = $_replace1 = $_replace2 = array();
	//require_once(S_P.'tmp_typeinfo.php');
	while($t = $SDB->fetch_array($query))
	{
		ADD_S($t);
		$lastid = $t['ThreadID'];
		$ifcheck = '1';
		$topped = '0';
		switch ($t['ThreadStatus'])
		{
			case 1:
				$topped = '0';
				break;
			case 2:
				$topped = '1';
				break;
			case 3:
				$topped = '2';
				break;
			case 4://当回车站
                $DDB->update("REPLACE INTO pw_recycle(pid,tid,fid,deltime,admin) VALUES(0,".$t['ThreadID'].",".$t['ForumID'].",0,'phpwind')");
				$t['ForumID'] = '0';
				break;
			default:
				$topped = '0';
				break;
		}
        /*
        if($t['ThreadType']==1){//投票帖
            $poll = $SDB->get_one("SELECT AlwaysEyeable,convert(char,ExpiresDate,20) AS ExpiresDate FROM bx_Polls WHERE ThreadID=".$t['ThreadID']);
            if($poll){
                $poll['ExpiresDate'] = strtotime($poll['ExpiresDate']);
		        $votearray = array();
                $pollitem = $SDB->query("SELECT ItemName,PollItemCount FROM bx_PollItems WHERE ThreadID=".$t['ThreadID']);
	            while($t = $SDB->fetch_array($query))
                {
                    $votearray[] = array($rt['ItemName'],$rt['PollItemCount']);
                }
		        $votearray	= addslashes(serialize($votearray));
		        $ipoll = "(".$t['ThreadID'].",'{$votearray}',1,1,".$poll['ExpiresDate'].",1,1)";
		        $DDB->update("REPLACE INTO {$pw_prefix}polls (tid,voteopts,modifiable,previewable,timelimit,multiple,mostvotes) VALUES ".$ipoll);
            }
        }*/
		switch ($t['special'])
		{
			case '1':
				$special = 1;//投票
				break;
			case '2':
				$special = 3;//悬赏
				break;
			default:
				$special = 0;//普通
				break;
		}

		//主题分类处理
		RtFidTypeid($t['fid'],$t['typeid'],$_typeinfo,$keyid);

        $t['CreateDate'] = strtotime($t['CreateDate']);
        $t['UpdateDate'] = strtotime($t['UpdateDate']);
		$topped = 0;

		$style = explode(";",$t['SubjectStyle']);
		foreach($style as $k => $v){
			$v2 = explode(":",$v);
			if($v2[0] == 'color'){
				$title1 = $v2[1];
			}
			if($v2[0] == 'font-weight'){
				$title2 = 1;
			}
			if($v2[0] == 'text-decoration'){
				$title3 = 1;
			}
		}
		$titlefont = "$title1~$title2~$title3~$title4~$title5~$title6~";

		$topicarr[] = "(".$t['ThreadID'].",".$t['ForumID'].",'".addslashes($t['PostNickName'])."',".$t['PostUserID'].",'".addslashes($t['Subject'])."','$titlefont',".($t['displayorder'] == '-2' ? 0 : 1).",'".$t['CreateDate']."','".$t['UpdateDate']."','".addslashes($t['LastPostNickName'])."',".$t['TotalViews'].",".$t['TotalReplies'].",".$topped.",".$t['isValued'].",0,".($t['posterid'] == '-1' ? 1 : 0).",".$special.")";
		$s_c++;
	}
	$DDB->update("REPLACE INTO {$pw_prefix}threads (tid,fid,author,authorid,subject,titlefont,ifcheck,postdate,lastpost,lastposter,hits,replies,topped,digest,ifupload,anonymous,special) VALUES ".implode(",",$topicarr));

	$row = $SDB->get_one("SELECT max(ThreadID) as max FROM bx_Threads");
	echo $row['max'].'<br>'.$lastid;
	if ($row['max']>$lastid)
	{
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
		report_log();
		newURL($step);
	}
}
elseif ($step == '5')
{
	//回复和内容
    //require_once(R_P.'libs/ip.php');
	if(!$start)
	{
		$DDB->query("TRUNCATE TABLE {$pw_prefix}posts");
		$DDB->query("TRUNCATE TABLE {$pw_prefix}tmsgs");
	}
	$percount = $percount - 1;
	$query = $SDB->query("SELECT Top $percount PostID,ThreadID,ForumID,convert(varchar(255),Subject) AS Subject,PostType,convert(text,Content) AS Content,convert(varchar(255),NickName) AS NickName,UserID,convert(char,CreateDate,20) AS CreateDate,IPAddress FROM bx_posts WHERE PostID > $start ORDER BY PostID");

	while($p = $SDB->fetch_array($query))
	{
		ADD_S($p);
		$lastid = $p['PostID'];
		$message = addslashes(bx_ubb($p['Content']));
        $ifconvert = 2;
		$p['usesig'] = 1;
        $p['CreateDate'] = strtotime($p['CreateDate']);

        //$ipfrom = cvipfrom($p['IPAddress']);获取ipfrom据说效率比较差

		if($p['PostType']=='1'){
			$tmsgs_arr[] = "(".$p['ThreadID'].",'".$p['attachment']."','".$p['IPAddress']."',".$p['usesig'].",
			$ifconvert,'".$message."')";
		}else{
			$posts_arr[] = "(".$p['PostID'].",".$p['ForumID'].",".$p['ThreadID'].",'".$p['attachment']."','".$p['NickName']."',".$p['UserID'].",'".$p['CreateDate']."','".$p['Subject']."','".$p['IPAddress']."',".$p['usesig'].",'',".$ifconvert.",".($p['invisible'] ? 0 : 1).",'".$message."',0,".($p['posterid'] == '-1' ? 1 : 0).",'".$ipfrom."')";
		}
		$s_c++;
	}
	if($tmsgs_arr){
		$DDB->update("REPLACE INTO {$pw_prefix}tmsgs (tid,aid,userip,ifsign,ifconvert,content) VALUES ".implode(",",$tmsgs_arr));
	}
	if($posts_arr){
		$DDB->update("REPLACE INTO {$pw_prefix}posts (pid,fid,tid,aid,author,authorid,postdate,subject,userip,ifsign,buy,ifconvert,
		ifcheck,content,ifshield,anonymous,ipfrom) VALUES ".implode(",",$posts_arr));
	}

	$row = $SDB->get_one("SELECT max(PostId) as max FROM bx_Posts");
	echo $row['max'].'<br>'.$lastid;
	if ($row['max'] > $lastid)
	{
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c.'&tableid='.$tableid);
	}
	else
	{
		newURL($step);
	}
}
elseif ($step == '6')
{
	//附件
	if(!$start)
	{
		$DDB->update("TRUNCATE TABLE {$pw_prefix}attachs");
	}
	$topicarr = array();
	$percount = $percount-1;
	$query = $SDB->query("SELECT Top $percount a.AttachmentID,a.PostID,convert(char, a.CreateDate, 20) AS CreateDate,
	convert(varchar(255),a.FileName) AS FileName,convert(varchar(255),a.FileType) AS FileType,a.FileSize,a.UserID,
	a.TotalDownloads,p.PostID,p.PostType,p.ForumID,p.ThreadID,f.ServerFilePath FROM bx_attachments a LEFT JOIN bx_posts p On a.PostID = p.PostID LEFT JOIN bx_files f On a.FileID = f.FileID
	WHERE a.AttachmentID > $start ORDER BY a.AttachmentID");

	while($a = $SDB->fetch_array($query))
	{
		ADD_S($a);
		$lastid = $a['AttachmentID'];
		switch ($a['FileType'])
		{
			case 'jpeg':
			case 'jpg':
			case 'gif':
            case 'JPG':
				$a['FileType'] = 'img';
				break;
			default:
				$a['FileType'] = 'zip';
				break;
		}
        $a['CreateDate'] = strtotime($a['CreateDate']);
        $attachurl = $a['ServerFilePath'];
        //$attachurl = date('Y-m',$a['CreateDate']).'/'.$a['FileName'];
		$topicarr[] = "(".$a['AttachmentID'].",'".$a['ForumID']."','".$a['UserID']."','".$a['ThreadID']."',".($a['PostType'] ? 0 : $a['PostID']).",'".$a['FileName']."','".$a['FileType']."','".$attachurl."',".$a['FileSize'].",".$a['TotalDownloads'].",".$a['CreateDate'].")";
        if($a['PostType']){
            if($a['FileType']=='img'){
                $tidarr[] = $a['ThreadID'];
            }elseif($a['FileType']=='zip'){
                $tidarr2[] = $a['ThreadID'];
            }
        }else{
            $pidarr[] = $a['PostID'];
        }
		$s_c++;
	}
	if($topicarr){
		$DDB->update("REPLACE INTO {$pw_prefix}attachs (aid,fid,uid,tid,pid,name,type,attachurl,size,hits,uploadtime) VALUES ".implode(",",$topicarr));
	}
    if($tidarr){
        $DDB->update("UPDATE pw_threads SET ifupload=1 WHERE tid in (".implode(",",$tidarr).")");
        $DDB->update("UPDATE pw_tmsgs SET aid=1 WHERE tid in (".implode(",",$tidarr).")");
    }
    if($tidarr2){
        $DDB->update("UPDATE pw_threads SET ifupload=2 WHERE tid in (".implode(",",$tidarr).")");
        $DDB->update("UPDATE pw_tmsgs SET aid=1 WHERE tid in (".implode(",",$tidarr2).")");
    }
    if($pidarr){
        $DDB->update("UPDATE pw_posts SET aid=1 WHERE pid in (".implode(",",$pidarr).")");
    }

	$row = $SDB->get_one("SELECT max(AttachmentID) as max FROM bx_attachments");
	echo $row['max'].'<br>'.$lastid;
	if ($row['max']>$lastid)
	{
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c.'&tableid='.$tableid);
	}
	else
	{
		newURL($step);
	}
}
//-----------------------------非重点数据----------
elseif ($step == '7')
{
	//个人空间留言
	if(!$start)
	{
		$DDB->update("TRUNCATE TABLE pw_oboard");
	}
	$query = $SDB->query("SELECT Top $percount c.CommentID,c.UserID,c.TargetID,convert(char,c.CreateDate,20) AS CreateDate,convert(text,c.Content) AS Content,CONVERT(varchar(255), u.username) AS username FROM bx_Comments c LEFT JOIN bx_Users u On c.UserID=u.UserID WHERE c.CommentID > $start ORDER BY c.CommentID");
	while($a = $SDB->fetch_array($query))
	{
		ADD_S($a);
        $lastid = $a['CommentID'];
        $a['CreateDate'] = strtotime($a['CreateDate']);
        $commentsarr[] = "(".$a['CommentID'].",'".$a['UserID']."','".$a['username']."','".$a['Content']."','".$a['TargetID']."',".$a['CreateDate'].",1)";
		$s_c++;
	}
    if($commentsarr){
        $DDB->update("REPLACE INTO pw_oboard (id,uid,username,title,touid,postdate,ifwordsfb) VALUES ".implode(",",$commentsarr));
    }
	$row = $SDB->get_one("SELECT max(CommentID) as max FROM bx_Comments");
	echo $row['max'].'<br>'.$lastid;
	if ($row['max']>$lastid)
	{
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c.'&tableid='.$tableid);
	}
	else
	{
		newURL($step);
	}
}
elseif ($step == '8')
{
	//短信数据
    $message_sql = $relations_sql = $replies_sql = array();
	if(!$start)
	{
        $DDB->update("TRUNCATE TABLE {$pw_prefix}ms_messages");
        $DDB->update("TRUNCATE TABLE {$pw_prefix}ms_relations");
        $DDB->update("TRUNCATE TABLE {$pw_prefix}ms_replies");
	}
	$query = $SDB->query("SELECT TOP $percount m.MessageID,m.UserID,m.TargetUserID,m.IsReceive,convert(varchar(255),u.username) AS msgfrom,convert(varchar(255),u2.username) AS msgto,convert(char,m.CreateDate,20) AS CreateDate,convert(text,m.Content) AS Content FROM bx_ChatMessages m LEFT JOIN bx_Users u ON m.UserID=u.UserID LEFT JOIN bx_Users u2 ON m.TargetUserID=u2.UserID WHERE m.MessageID > $start ORDER BY m.MessageID");
	while($m = $SDB->fetch_array($query))
	{
		ADD_S($m);
        $lastid = $m['MessageID'];
		switch ($m['folder'])
		{
			case 0:
				$type = 'rebox';
				break;
			case 1:
				$type = 'sebox';
				$m['msgfrom'] = $m['msgto'];
				break;
			default :
				$type = 'public';
				break;
		}
        $m['msgfromid'] = $m['UserID'];
        $m['msgtoid'] = $m['TargetUserID'];
        $m['subject'] = substrs($m['Content'],20);
        $postdatetime = strtotime($m['CreateDate']);

        $message_sql[] = "('".$m['MessageID']."',".$m['msgfromid'].",'".$m['msgfrom']."','".$m['subject']."','".$m['subject']."','".serialize(array('categoryid'=>1,'typeid'=>100))."',".$postdatetime.",".$postdatetime.",'".serialize(array($m['msgto']))."')";
        $replies_sql[] = "('".$m['MessageID']."',".$m['MessageID'].",'".$m['msgfromid']."','".$m['msgfrom']."','".$m['subject']."','".$m['Content']."','1',".$postdatetime.",".$postdatetime.")";

        $userIds = "";
        $userIds = array($m['msgtoid'],$m['msgfromid']);
        foreach($userIds as $otherId){
            $relations_sql[] = "(".$otherId.",'".$m['MessageID']."','1','100','0',".(($otherId == $m['msgfromid']) ? 1 : 0).",".$postdatetime.",".$postdatetime.")";
        }
		$s_c++;
	}
    if($message_sql)
    {
        $DDB->update("REPLACE INTO {$pw_prefix}ms_messages (mid,create_uid,create_username,title,content,expand,created_time,modified_time,extra) VALUES ".implode(",",$message_sql));
    }
    if($relations_sql)
    {
        $DDB->update("INSERT INTO {$pw_prefix}ms_relations (uid,mid,categoryid,typeid,status,isown,created_time,modified_time) VALUES ".implode(",",$relations_sql));
    }
    if($replies_sql)
    {
        $DDB->update("REPLACE INTO {$pw_prefix}ms_replies(id,parentid,create_uid,create_username,title,content,status,created_time,modified_time) VALUES ".implode(",",$replies_sql));
    }
	$row = $SDB->get_one("SELECT max(MessageID) as max FROM bx_ChatMessages");
	echo $row['max'].'<br>'.$lastid;
	if ($row['max']>$lastid)
	{
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
		newURL($step);
        echo '短消息结束';exit;
		report_log();
		newURL($step);
	}
}
elseif ($step == '9')
{
	//好友
	if(!$start)
	{
		$DDB->update("TRUNCATE TABLE pw_friends");
		$DDB->update("TRUNCATE TABLE pw_friendtype");
        $query = $SDB->query("SELECT Top $percount GroupID,UserID,CONVERT(varchar(255), GroupName) AS GroupName FROM bx_FriendGroups");
        while($rt = $SDB->fetch_array($query))
        {
            $DDB->update("REPLACE INTO pw_friendtype(ftid,uid,name) VALUES (".$rt['GroupID'].",".$rt['UserID'].",'".$rt['GroupName']."')");
        }
	}
	$query = $SDB->query("SELECT UserID,FriendUserID,GroupID FROM bx_Friends");
	while($f = $SDB->fetch_array($query))
	{
		ADD_S($f);
        if(!$a['GroupID'])$a['GroupID']=0;
		$DDB->update("REPLACE INTO {$pw_prefix}friends (uid,friendid,descrip,iffeed,ftid) VALUES (".$f['UserID'].",".$f['FriendUserID'].",'',1,'".$a['GroupID']."')");
		$DDB->update("REPLACE INTO {$pw_prefix}friends (friendid,uid,descrip,iffeed,ftid) VALUES (".$f['UserID'].",".$f['FriendUserID'].",'',1,'".$a['GroupID']."')");
	}

    newURL($step);
}
elseif ($step == '10')
{
	//相册
	if(!$start)
	{
		$DDB->update("TRUNCATE TABLE pw_cnalbum");
	}
	$query = $SDB->query("SELECT Top $percount a.AlbumID,a.UserID,a.TotalPhotos,convert(char,a.CreateDate,20) AS CreateDate,convert(char,a.UpdateDate,20) AS UpdateDate,CONVERT(varchar(255),a.Name) AS Name,Cover,CONVERT(varchar(255),u.username) AS username FROM bx_Albums a LEFT JOIN bx_Users u On a.UserID=u.UserID WHERE a.AlbumID > $start ORDER BY a.AlbumID");
	while($a = $SDB->fetch_array($query))
	{
		ADD_S($a);
        $lastid = $a['AlbumID'];
        $a['CreateDate'] = strtotime($a['CreateDate']);

        $f = $SDB->get_one("SELECT p.PhotoID,f.ServerFilePath FROM bx_Photos p LEFT JOIN bx_Files f On p.FileID=f.FileID WHERE p.AlbumID=".$a['AlbumID']." ORDER BY p.CreateDate DESC");
        $f['ServerFilePath'] = str_replace("\\","/",$f['ServerFilePath']);
		$cnalbumdb[] = array($a['AlbumID'],$a['Name'],$f['ServerFilePath'],0,0,$a['UserID'],$a['username'],$a['TotalPhotos'],$a['UpdateDate'],$lastpid,$a['CreateDate']);
	}
	$cnalbumdb && $DDB->update("REPLACE INTO {$pw_prefix}cnalbum (aid,aname,lastphoto,atype,private,ownerid,owner,photonum,lasttime,lastpid,crtime) VALUES ".pwSqlMulti($cnalbumdb));

	$row = $SDB->get_one("SELECT max(AlbumID) as max FROM bx_Albums");
	echo $row['max'].'<br>'.$lastid;
	if ($row['max']>$lastid)
	{
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c.'&tableid='.$tableid);
	}
	else
	{
		newURL($step);
	}
}
elseif ($step == '11')
{
	//图片
	if(!$start)
	{
		$DDB->update("TRUNCATE TABLE pw_cnphoto");
	}
	$query = $SDB->query("SELECT Top $percount p.PhotoID,p.UserID,p.AlbumID,p.TotalViews,p.TotalComments,p.FileSize,CONVERT(char,p.CreateDate,20) AS CreateDate,CONVERT(varchar(255), p.Name) AS Name,f.ServerFilePath FROM bx_Photos p LEFT JOIN bx_Users u On p.UserID=u.UserID LEFT JOIN bx_Files f On p.FileID=f.FileID WHERE p.PhotoID >$start ORDER BY p.PhotoID");
	while($p = $SDB->fetch_array($query))
	{
		ADD_S($p);
        $lastid = $p['PhotoID'];
        $p['CreateDate'] = strtotime($p['CreateDate']);
		$cnphoto[] = array($p['PhotoID'],$p['AlbumID'],$p['Name'],$p['ServerFilePath'],$p['username'],$p['CreateDate'],$p['TotalViews'],0,$p['TotalComments']);
	}
	$cnphoto && $DDB->update("REPLACE INTO {$pw_prefix}cnphoto (pid,aid,pintro,path,uploader,uptime,hits,ifthumb,c_num) VALUES ".pwSqlMulti($cnphoto));

	$row = $SDB->get_one("SELECT max(PhotoID) as max FROM bx_Photos");
	echo $row['max'].'<br>'.$lastid;
	if ($row['max']>$lastid)
	{
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c.'&tableid='.$tableid);
	}
	else
	{
		newURL($step);
	}
}
elseif ($step == '12')
{
	//投票
	if(!$start)
	{
		$DDB->update("TRUNCATE TABLE pw_polls");
	}
    $percount = 100;
	$query = $SDB->query("SELECT Top $percount ThreadID,AlwaysEyeable,convert(char,ExpiresDate,20) AS ExpiresDate FROM bx_Polls WHERE ThreadID >$start ORDER BY ThreadID");
	while($rt = $SDB->fetch_array($query))
	{
		ADD_S($rt);
        $lastid = $rt['ThreadID'];
        $votearray = array();

        $count = 0;
        $itemnum = 0;
        $query2 = $SDB->query("SELECT ItemID,ItemName,PollItemCount FROM bx_PollItems WHERE ThreadID=".$rt['ThreadID']);
        while($rt2 = $SDB->fetch_array($query2))
        {
            $votearray[] = array($rt2['ItemName'],$rt2['PollItemCount']);
            $count = $count + $rt2['PollItemCount'];
            $itemnum++;
        }
        $rt['ExpiresDate'] = strtotime($rt['ExpiresDate']);
        $votearray	= addslashes(serialize($votearray));
        $ipoll = "(".$rt['ThreadID'].",'{$votearray}',1,1,'".$rt['ExpiresDate']."',1,$itemnum,$count)";
        $DDB->update("REPLACE INTO {$pw_prefix}polls (tid,voteopts,modifiable,previewable,timelimit,multiple,mostvotes,voters) VALUES ".$ipoll);
        $DDB->update("UPDATE pw_threads SET special=1 WHERE tid=".$rt['ThreadID']);
    }

	$row = $SDB->get_one("SELECT max(ThreadID) as max FROM bx_Polls");
	echo $row['max'].'<br>'.$lastid;
	if ($row['max']>$lastid)
	{
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c.'&tableid='.$tableid);
	}
	else
	{
		newURL($step);
	}
}
elseif ($step == '13')
{
    //投票人
	$DDB->update("TRUNCATE TABLE pw_voter");
    $query = $SDB->query("SELECT pd.ItemID,pd.UserID,pd.NickName,convert(char,pd.CreateDate,20) AS CreateDate,pi.ThreadID,pi.ItemName FROM bx_PollItemDetails pd INNER JOIN bx_PollItems pi On pd.ItemID=pi.ItemID");
    while($rt = $SDB->fetch_array($query))
    {
        ADD_S($rt);
        $rt['CreateDate'] = strtotime($rt['CreateDate']);
        $v = $DDB->get_value("SELECT voteopts FROM pw_polls WHERE tid=".$rt['ThreadID']);
        $varr = unserialize($v);
        foreach($varr as $k1 => $v1){
            if($v1[0]==$rt['ItemName']){
                $vote = $k1;
                break;
            }
        }
        $DDB->update("INSERT INTO {$pw_prefix}voter (tid,uid,username,vote,time) VALUES ('".$rt['ThreadID']."','".$rt['UserID']."','".$rt['NickName']."','$vote','".$rt['CreateDate']."')");
    }

	newURL($step);
}
elseif ($step == '14')
{
	//日志分类
	$query = $SDB->query("SELECT TOP $percount CategoryID,UserID,TotalArticles,CONVERT(varchar(255), Name) AS Name FROM bx_BlogCategories");
	while($rt = $SDB->fetch_array($query))
	{
        ADD_S($rt);
		$lastid = $rt['CategoryID'];
		$DDB->update("REPLACE INTO pw_diarytype(dtid,uid,name,num) VALUES(".$rt['CategoryID'].",".$rt['UserID'].",'".$rt['Name']."',".$rt['TotalArticles'].")");
	}
	$row = $SDB->get_one("SELECT max(CategoryID) as max FROM bx_BlogCategories");
	echo $row['max'].'<br>'.$lastid;
	if ($row['max']>$lastid)
	{
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
		report_log();
		newURL($step);
	}
}
elseif ($step == '15')
{
	//日志
	if(!$start)
	{
		$DDB->update("TRUNCATE TABLE {$pw_prefix}diary");
	}
	$query = $SDB->query("SELECT TOP $percount b.ArticleID,b.UserID,b.CategoryID,b.TotalViews,b.TotalComments,CONVERT(varchar(255), b.Subject) AS Subject,convert(text,b.Content) AS Content,convert(char,b.CreateDate,20) AS CreateDate,PrivacyType,CONVERT(varchar(255), u.username) AS username FROM bx_BlogArticles b LEFT JOIN bx_users u On b.UserID=u.UserID WHERE b.ArticleID > $start ORDER BY b.ArticleID");
	while($rt = $SDB->fetch_array($query))
	{
        ADD_S($rt);
		$lastid = $rt['ArticleID'];

		unset($diarydb);
		$rt['CreateDate'] = strtotime($rt['CreateDate']);
		$diarydb = array(
			'did' => $rt['ArticleID'],
			'uid' => $rt['UserID'],
			'dtid' => $rt['CategoryID'],
			'username' => $rt['username'],
			'privacy' => $rt['PrivacyType'],
			'subject' => $rt['Subject'],
			'content' => $rt['Content'],
			'ifcopy' => 1,
			'copyurl' => '',
			'ifconvert' => 2,
			'ifwordsfb' => 1,
			'r_num' => $rt['TotalViews'] ,
			'c_num' => $rt['TotalComments'] ,
			'postdate' => $rt['CreateDate']
		);
		$DDB->update("REPLACE INTO {$pw_prefix}diary SET".pwSqlSingle($diarydb));
	}
	$row = $SDB->get_one("SELECT max(ArticleID) as max FROM bx_BlogArticles");
	echo $row['max'].'<br>'.$lastid;
	if ($row['max']>$lastid)
	{
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
		report_log();
		newURL($step);
	}
}
else
{
	ObHeader($basename.'?action=finish&dbtype='.$dbtype);
	exit;
}

function strip_space(&$array){
	if (is_array($array)) {
		foreach ($array as $key => $value) {
			if (!is_array($value)) {
				$array[$key] = trim($value);
			} else {
				strip_space($array[$key]);
			}
		}
	}
}

//转换用户组
function changegroups()
{
	global $pw_prefix, $source_prefix, $SDB, $DDB, $dest_charset;;
	require_once S_P.'lang_'.$dest_charset.'.php';

	$DDB->update("TRUNCATE TABLE {$pw_prefix}usergroups");
	$DDB->update($lang['group']);
	$query = $SDB->query("SELECT * FROM {$source_prefix}usergroups WHERE system = 0");
	$_specialdata = $_newgroup = array();


	$mright['atclog'] = $mright['show'] = $mright['msggroup'] = $mright['ifmemo'] = $mright['modifyvote'] = $mright['viewvote'] = $mright['allowreward'] = $mright['allowencode'] = $mright['leaveword'] = $mright['viewvote'] = $mright['viewvote'] = 1;
	$mright['viewipfrom'] = $mright['anonymous'] = $mright['dig'] = $mright['atccheck'] = $mright['markable'] = $mright['postlimit'] = 0;
	$mright['imgwidth'] = $mright['imgheight'] = $mright['fontsize'] = $mright['maxsendmsg'] = $mright['maxfavor'] = $mright['maxgraft'] = '';
	$mright['uploadtype'] = $uploadtype ? addslashes(serialize($uploadtype)) : '';
	$mright['media']  = $mright['pergroup'] = '';
	$mright['markdb'] = "10|0|10||1";
	$mright['schtime'] = 'all';
	$mright = P_serialize($mright);

	while ($rt = $SDB->fetch_array($query))
	{
		if ($rt['radminid'])
		{
			$_specialdata[$rt['groupid']] = '1';
			$gptype = 'special';
		}
		else
		{
			$gptype = 'member';
		}
		pwGroupref(array('gid'=>$rt['groupid'],'gptype'=>$gptype,'grouptitle'=>$rt['grouptitle'],'grouppost'=>$rt['creditslower'],'maxmsg'=>$rt['maxpmnum'],'allowhide'=>$rt['allowinvisible'],'allowread'=>$rt['readaccess'] ? 1 : 0,'allowportait'=>$rt['allowavatar'] ? 1 : 0,'upload'=>$rt['allowavatar']==3 ? 1 : 0,'allowrp'=>$rt['allowreply'],'allowhonor'=>$rt['allowcstatus'],'allowdelatc'=>1,'allowpost'=>$rt['allowpost'],'allownewvote'=>$rt['allowpostpoll'],'allowvote'=>$rt['allowvote'],'htmlcode'=>$rt['allowhtml'],'allowhidden'=>$rt['allowhidecode'],'allowencode'=>$rt['allowsetreadperm'],'allowsearch'=>$$rt['allowsearch'],'allowprofile'=>$rt['allowviewpro'],'allowreport'=>1,'allowmessage'=>1,'allowsort'=>$rt['allowviewstats'],'alloworder'=>1,'allowupload'=>$rt['allowpostattach'],'allowdownload'=>$rt['allowgetattach'],'allowloadrvrc'=>$rt['allowsetattachperm'],'allownum'=>50,'edittime'=>0,'postpertime'=>0,'searchtime'=>10,'signnum'=>$rt['maxsigsize'],'mright'=>$mright,'sright'=>''));
		$grouptitle=getGrouptitle($rt['groupid'],$rt['grouptitle'],false);
		$DDB->update("INSERT INTO {$pw_prefix}usergroups (gid,gptype,grouptitle,grouppost) VALUES ('".$rt['groupid']."','$gptype','$grouptitle','".$rt['creditslower']."')");

		$gpid=$rt['groupid'];
		$_newgroup[$gpid] = $DDB->insert_id();
	}
	//写入配置信息
	$_specialdatastr = "\$_specialdata = ".pw_var_export($_specialdata).";";
	writeover(S_P.'tmp_specialdatastr.php', $_specialdatastr,true);
	$newgroupdatastr = "\$_newgroup = ".pw_var_export($_newgroup).";";
	writeover(S_P.'tmp_newgroupdatastr.php', $newgroupdatastr,true);

}
function getupadmin($fid, &$upadmin)
{
	global $forumdb;
	$forumdb[$fid]['moderators'] = trim($forumdb[$fid]['moderators']);
	if ($forumdb[$fid]['moderators'])
	{
		$upadmin .= $upadmin ? addslashes($forumdb[$fid]['moderators']).',' : ','.addslashes($forumdb[$fid]['moderators']).',';
	}
	if ($forumdb[$fid] && $forumdb[$fid]['parentid'])
	{
		getupadmin($forumdb[$fid]['parentid'], $upadmin);
	}
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
function dz_ubb($content)
{
	$content = str_replace(array('[wma]','[/wma]','[flash]','[swf]','[/swf]','[rm]','[ra]','[php]','[/php]','[/ra]','[wmv]','[mp3]','[/mp3]'),array('[wmv=0]','[/wmv]','[flash=314,256,0]','[flash=314,256,0]','[/flash]','[rm=314,256,0]','[rm=314,256,0]','[code]','[/code]','[/rm]','[wmv=314,256,0]','[wmv=0]','[/wmv]'),$content);
	$content = preg_replace(array('~\[media=mp3,\d+?,\d+?,(?:1|0)\](.+?)\[\/media\]~i','~\[media=(?:wmv|mov),(\d+?),(\d+?),(1|0)\](.+?)\[\/media\]~i','~\[media=(rm|ra),(\d+?),(\d+?),(1|0)\](.+?)\[\/media\]~i','~\[hide\](.+?)\[\/hide\]~is','~\[localimg=[0-9]+,[0-9]+\]([0-9]+)\[\/localimg\]~is','~\[local\]([0-9]+)\[\/local\]~is','~\[attach\]([0-9]+)\[\/attach\]~is','~\[attachimg\]([0-9]+)\[\/attachimg\]~is','/\[img=[0-9]+,[0-9]+\]/i','/\[size=(\d+(\.\d+)?(px|pt|in|cm|mm|pc|em|ex|%)+?)\]/i'),array('[wmv=0]\\1[/wmv]','[media=wmv,\\1,\\2,\\3]\\4[/media]','[media=\\1,\\2,\\3,\\4]\\5[/media]','[post]\\1[/post]','[attachment=\\1]','[attachment=\\1]','[attachment=\\1]','[attachment=\\1]','[attachment=\\1]','[img]',''),$content);
	return $content;
}
function bx_ubb($content)
{
    //$a='<img src="{$root}/max-assets/icon-emoticon/2.gif" emoticon="[em:2]" alt="" />';
    //$a = addslashes($a);
    //$a = stripslashes($a);
    //$a = preg_replace(array("/src=[\"]\{[$]root\}\/([^\<\r\n\"']+?)[\"](\s)*emoticon=/is"),array('[s:]'),$a);
    //echo ($a);exit;
	//$content = preg_replace(array('~\[attachimg=\d+,\d+\](.+?)\[\/attachimg\]~i',"/\<img\ssrc=\'/is"),array('[attachment=\\1]','[s:\\2]'),$content);

    $content = stripslashes($content);

	$content =str_replace(array('[u]','[/u]','[b]','[/b]','[i]','[/i]','[list]','[li]','[/li]','[/list]','[sub]', '[/sub]','[sup]','[/sup]','[strike]','[/strike]','[blockquote]','[/blockquote]','[hr]','[/backcolor]', '[/color]','[/font]','[/size]','[/align]','<br>','<Br>','<br />','<Br />','[hide]','[/hide]','</span>'), array('<u>','</u>','<b>','</b>','<i>','</i>','<ul style="margin:0 0 0 15px">','<li>', '</li>','</ul>','<sub>','</sub>','<sup>','</sup>','<strike>','</strike>','<blockquote>','</blockquote>', '<hr />','</span>','</span>','</span>','</font>','</div>','
','
','
','
','[post]','[/post]','[/color]'), $content);

/*
<table><tr><td><span style="color:#000000;"><span style="color:#000000;"> &nbsp; &nbsp; &nbsp; 1月7日中午12时许在南海区穗盐路的一间电缆材料厂突发大火，内里存放的大量塑料胶材料更加刷火势的猛烈，几公里外均可见遮天蔽日的浓烟，十多台消防车赶赴现场扑救，并引来众多人们围观，直至下午15时许大火才被全部扑灭。</span></span><span style="color:#000000;"><span style="color:#000000;">桂和路的由桂江大桥往大沥方向也因消防车占用车道救火，造成交通挤塞严重。(更多图片稍候更新)</span></span></td></tr></table>
*/
//<img src="{$root}/userfiles/Emoticons/0/4/0412FD625C0760D67595F6F8BFD836A3_27325.gif" emoticon="{face:9229}" alt="" />TMD天收咖

	$searcharray = array(
		"/\[attachimg=\d+,\d+\](.+?)\[\/attachimg\]/is",
		"/\[attachimg\](.+?)\[\/attachimg\]/is",
		"/<img\ssrc=[\"]\{[$]root\}\/([^\<\r\n\"']+?)[\"]\semoticon=\"\[em:(\d+)\]\"\salt=\"\"\s\/>/is",
        //"/<img\ssrc=[\"]\{[$]root\}\/([^\<\r\n\"']+?)[\"]\semoticon=\"\{face:(\d+)\}\"\salt=\"\"\s\/>/is",
		"/\<span\sstyle=\"color:([#0-9a-z]{1,15})[;]\"\>/is"
	);
	$replacearray = array(
		"[attachment=\\1]",
		"[attachment=\\1]",
		"[s:\\2]",
		//"[img][/img]",
		"[color=\\1]"
	);
	$content = preg_replace($searcharray,$replacearray,$content);

    //$content = preg_replace(array('~\[attachimg=\d+,\d+\](.+?)\[\/attachimg\]~i','~\[attachimg\](.+?)\[\/attachimg\]~i',"/<img\ssrc=[\"]\{[$]root\}\/([^\<\r\n\"']+?)[\"]\semoticon=\"\[em:(\d+)\]\"\salt=\"\"\s\/>/is","~\<span\sstyle=\"color:(.*)[;]\"\>(.*)</span>~is"),array('[attachment=\\1]','[attachment=\\1]','[s:\\2]','[color=\\1]\\2[/color]'),$content);
    //echo $content;exit;
	return $content;
}
function RtFidTypeid($fid,$typeid,$typeinfo,&$keyid)
{
	$keyid = 0;
	$Arr_typeinfo = explode('%',$typeinfo);
	foreach($Arr_typeinfo as $key => $value)
	{
		$Arr_fid = explode('|',$value);
		if($Arr_fid[0]==$fid)
		{
			$Arr_typeid = explode(',',$Arr_fid[1]);
			foreach($Arr_typeid as $t_key => $t_value)
			{
				if($t_value==$typeid)
				{
					$keyid = $t_key+1;
				}
			}
		}
	}
}
?>