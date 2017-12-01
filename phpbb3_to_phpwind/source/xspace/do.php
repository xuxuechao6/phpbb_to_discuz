<?php
/**
 * X-Space 4.0.1 转换程序
 *
 */
!defined('R_P') && exit('Forbidden!');
require_once(S_P . "ubb.php");
require_once(S_P . "config.php");

if ($pwsamedb){
	$SDB = &$DDB;
}
else {
	$charset_change = 1;
	$SDB = new mysql($source_db_host, $source_db_user, $source_db_password, $source_db_name, '');
}

if ($step == 1){
	//好友
	if(!$start){
		//$DDB->query("TRUNCATE TABLE {$pw_prefix}friends");
	}
	$source_friends = $SDB->query("SELECT * FROM {$source_prefix}friends LIMIT $start, $percount");
	$goon = 0;

	while($rt = $SDB->fetch_array($source_friends)){
		add_s($rt);
		$sql_for_insert_friends = "REPLACE INTO {$pw_prefix}friends (`uid`, `friendid`,  `joindate`)
						VALUES ({$rt['uid']}, {$rt['frienduid']}, {$rt['dateline']})";
		$DDB->update($sql_for_insert_friends);
		$goon ++;
		$s_c ++;
	}
	if ($goon == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	} else {
		report_log();
		newURL($step);
	}
} else if($step == 2) {
	//日志分类
	if(!$start){
		//$DDB->query("TRUNCATE TABLE {$pw_prefix}diarytype");
	}

	//itemtypes  => 个人分类表 204
	//spaceitems => 信息表 457
	$query_diary_category = "SELECT a.typeid, a.uid, a.typename ,COUNT(b.itemid) AS num FROM "
						  . "{$source_prefix}itemtypes AS a LEFT JOIN {$source_prefix}spaceitems "
						  . "AS b ON a.typeid = b.itemtypeid WHERE b.type = 'blog' "
						  . "GROUP BY b.itemtypeid ORDER BY a.typeid ASC ";
	$diary_category = $SDB->query($query_diary_category);
	while($rt = $SDB->fetch_array($diary_category)){
		add_s($rt);
		$sql_insert_diary_type = "INSERT INTO {$pw_prefix}diarytype (`uid`, `name`, `num`) "
							   . "VALUES ({$rt['uid']}, '{$rt['typename']}', {$rt['num']})";
		$DDB->update($sql_insert_diary_type);
		$dtid = $DDB->insert_id();
		$category[$rt['typeid']] = $dtid;
		$s_c ++;
	}

	writeover(S_P.'tmp_category.php', "\$_category = ".pw_var_export($category).";\n", true);

	report_log();
	newURL($step);
} else if($step == 3){
	//表情
	newURL($step);
	$_pwface = $_dzface = array();

	//$DDB->query("TRUNCATE TABLE {$pw_prefix}smiles");

	$query = $SDB->query("SELECT typeid,directory,name,displayorder FROM {$bbs_prefix}imagetypes WHERE type = 'smiley'");
	while ($s = $SDB->fetch_array($query)) {
		add_s($s);
		$DDB->update("INSERT INTO {$pw_prefix}smiles (id,path,name,vieworder,type) VALUES (".$s['typeid'].",'".addslashes($s['directory'])."','".addslashes($s['name'])."','".$s['displayorder']."',0)");
		$s_c++;
	}

	$query = $DDB->query("SELECT id,path,name,vieworder FROM {$pw_prefix}smiles");
	while ($i = $DDB->fetch_array($query))
	{
		$query2 = $SDB->query("SELECT displayorder,code,url FROM {$bbs_prefix}smilies WHERE typeid = ".$i['id']);
		while($s = $SDB->fetch_array($query2)){
			$DDB->update("INSERT INTO {$pw_prefix}smiles (path,vieworder,type) VALUES('".addslashes($s['url'])."',".$s['displayorder'].",".$i['id'].")");
			$_pwface[] = '[s:'.$DDB->insert_id().']';
			$_dzface[] = $s['code'];
		}
	}
	writeover(S_P.'tmp_face.php', "\$_pwface = ".pw_var_export($_pwface).";\n\$_dzface = ".pw_var_export($_dzface).";", true);
	report_log();
	newURL($step);
} else if($step == 4) {
	//日志
	require_once S_P .'tmp_category.php';

	if(!$start){
		//$DDB->query("TRUNCATE TABLE {$pw_prefix}diary");
		$DDB->query("CREATE TABLE IF NOT EXISTS `a_diary` (
  `did` int(10) unsigned NOT NULL,
  `old_itemid` int(10) unsigned NOT NULL,
  KEY `old_itemid` (`old_itemid`)
) ENGINE=MyISAM DEFAULT CHARSET={$dest_charset};
");

    /*
	$query_diary = "SELECT a.itemid, a.username, a.uid, a.itemtypeid, a.subject, a.dateline, a.password,
					a.viewnum, a.replynum, a.haveattach, b.message  FROM {$source_prefix}spaceitems a
					LEFT JOIN {$source_prefix}spaceblogs b ON  a.itemid = b.itemid WHERE a.type = 'blog'
					LIMIT $start, $percount ";*/
//todo, a.password不见了
	$query_diary = "SELECT a.itemid, a.username, a.uid, a.itemtypeid, a.subject, a.dateline,
					a.viewnum, a.replynum, a.haveattach, b.message  FROM {$source_prefix}spaceitems a
					LEFT JOIN {$source_prefix}spaceblogs b ON  a.itemid = b.itemid WHERE a.type = 'blog' AND a.itemid > $start ORDER BY itemid LIMIT $percount";
	$diary_handle = $SDB->query($query_diary);
	//$goon = 0;
	$pic_source_url = $xspace_url .  '/attachments';
	$pic_url = $pw_url . '/attachment/photo';

	while($rt = $SDB->fetch_array($diary_handle)){
        $lastid = $rt['itemid'];
		//add_s($rt);
		//当日志设置了密码，转换后的的权限为“仅自己可见”
		if(strlen($rt['password']) != 0){
			$rt['privacy'] = 2;
		} else {
			$rt['privacy'] = 0;
		}

		//去除x-space 中的自动Tag的链接
		$tag_regx = '/<a\s+?href=\s*\".+?tag.php\?k=.+?\"\s*.*?>(.+?)<\/a>/is';
		preg_match_all($tag_regx, $rt['message'], $match);

		if(!empty($match)){
			foreach($match[1] as $tag){
				$tag_regx = '/<a\s+?href=\s*\".+?tag.php\?k=.+?\"\s*.*?>\s*' . $tag . '\s*<\/a>/is';
				$rt['message'] = preg_replace($tag_regx, $tag, $rt['message']);
			}
		}
		//去除图片的下载链接
        /*
		$img_regx = '/<a\s+?href=\s*\".+?batch\.download\.php\?aid=.+?\"\s*.*?>(.+?)<\/a>/is';
		preg_match_all($img_regx, $rt['message'], $match);

		if(!empty($match)){
			foreach($match[1] as $img){
				$tag_regx = '/<a\s+?href=\s*\".+?batch\.download\.php\?aid=.+?\"\s*.*?>\s*' . $img . '\s*<\/a>/is';
				$rt['message'] = preg_replace($img_regx, $img, $rt['message']);
			}
		}*/

		//将HTML代码转换成UBB代码
		//$rt['message'] = pwEscape($rt['message']);
		/*
		$rt['message'] = html2bbcode($rt['message']);

		//去除图片链接
		$star_regx = '/(\[url.*?\])\s*\[img\]/is';
		$rt['message'] = preg_replace($star_regx , '[img]', $rt['message']);
		$end_regx = '/\[\/img\]\s*(\[\/url\])/is';
		$rt['message'] = preg_replace($end_regx, '[/img]', $rt['message']);
		*/
		/*
		//转换图片的URL地址
		$url_regx = '/\[img\]\s*' . addcslashes($pic_source_url, ".:/") . '/is';
		$rt['message'] = preg_replace($url_regx, '[img]' . $pic_url, $rt['message']);

		//转换表情地址的绝对地址
		$face_regx = '/\[img\]\s*' . addcslashes($xspace_url, ".:/") . '/is';
		$rt['message'] = preg_replace($face_regx, '[img]' . $pw_url .'/attachment', $rt['message']);
		*/

		//$rt['message'] = addslashes(dz_ubb(str_replace($_dzface,$_pwface,$rt['message'])));

		$rt['itemtypeid'] = $_category[$rt['itemtypeid']];

		if(strlen($rt['itemtypeid']) == 0 ) {
			$rt['itemtypeid'] = 0;
		}
		$ifconvert = convert($rt['message']) == $rt['message'] ? 1 : 2;
		add_s($rt);

        //如果原先pw_diary有数据，用insert方式比较好，并且a_diary有作用，如果无数据，直接用replace简单点
        /*
		$insert_diary = "REPLACE INTO {$pw_prefix}diary (did,uid, dtid, username, subject, content, r_num, c_num,
				postdate, ifupload, privacy, ifconvert) VALUES ({$rt['itemid']}, {$rt['uid']}, {$rt['itemtypeid']}, '{$rt['username']}',
				'{$rt['subject']}', '{$rt['message']}',{$rt['viewnum']}, {$rt['replynum']}, {$rt['dateline']},
				{$rt['haveattach']}, {$rt['privacy']}, {$ifconvert})";*/
		$insert_diary = "INSERT INTO {$pw_prefix}diary (uid, dtid, username, subject, content, r_num, c_num,
				postdate, ifupload, privacy, ifconvert) VALUES ({$rt['uid']}, {$rt['itemtypeid']}, '{$rt['username']}',
				'{$rt['subject']}', '{$rt['message']}',{$rt['viewnum']}, {$rt['replynum']}, {$rt['dateline']},
				{$rt['haveattach']}, {$rt['privacy']}, {$ifconvert})";
		$DDB->update($insert_diary);
		$did = $DDB->insert_id();
        $DDB->update("INSERT INTO a_diary(did,old_itemid) VALUES({$did},{$rt['itemid']})");
		//$diary[$rt['itemid']] = $did;
		unset($rt);
		$s_c ++;
	}
    /*之前用的文本记录，但是如果日志太多会有性能问题，故统计改为表存储
	$tmp_file_name = S_P.'tmp_diary.php';
	if(file_exists($tmp_file_name) && $s_c !== 1000){
		require_once S_P.'tmp_diary.php';
		//$_diary = array_merge($_diary, $diary);
		$_diary = $_diary + $diary;

		writeover(S_P.'tmp_diary.php', "\$_diary = ".pw_var_export($_diary).";\n", true);
	} else {
		writeover(S_P.'tmp_diary.php', "\$_diary = ".pw_var_export($diary).";\n", true);
	}
    */
	$maxid = $SDB->get_value("SELECT max(itemid) FROM {$source_prefix}spaceitems WHERE type = 'blog'");
    echo '最大id',$maxid.'<br>最后id',$lastid;
    if ($lastid < $maxid)
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else{
		report_log();
		newURL($step);
	}
} else if($step == 5) {
	//附件
	if(!$start){
		//$DDB->query("TRUNCATE TABLE a_attachs");
		$DDB->query("CREATE TABLE IF NOT EXISTS `a_attachs` (
  `aid` int(10) unsigned NOT NULL,
  `old_aid` int(10) unsigned NOT NULL,
  KEY `old_aid` (`old_aid`)
) ENGINE=MyISAM DEFAULT CHARSET={$dest_charset};
");

	$query = $SDB->query("SELECT a.* FROM {$source_prefix}attachments a WHERE a.aid > $start AND a.type = 'blog' ORDER BY aid LIMIT $percount");
	while($a = $SDB->fetch_array($query)){
		add_s($a);
        $lastid = $a['aid'];
		$fileinfo = getfileinfo($a['filename']);
		$a['filetype'] 	= $fileinfo['type'];
        $newdid = $DDB->get_value("SELECT did FROM a_diary WHERE old_itemid={$a['itemid']}");
		$sql = "INSERT INTO {$pw_prefix}attachs (uid, did, name, type, size, uploadtime, attachurl,hits)
			VALUES ({$a['uid']}, {$a['itemid']}, '{$a['filename']}', '{$a['filetype']}', {$a['size']}, {$a['dateline']},
			'{$a['filepath']}', {$a['downloads']} )";
		$DDB->update($sql);
		$aid = $DDB->insert_id();
        $DDB->update("INSERT INTO a_attachs(aid,old_aid) VALUES({$aid},{$a['aid']})");
        $DDB->update("UPDATE pw_diary SET aid=1 WHERE did=".$newdid);
		$s_c++;
	}

	$maxid = $SDB->get_value("SELECT max(aid) FROM {$source_prefix}attachments WHERE type = 'blog'");
	if ($lastid < $maxid)
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	} else {
		report_log();
		newURL($step);
	}
} else if($step == 6){

	$query = $DDB->query("SELECT * FROM pw_diary WHERE did > $start ORDER BY did LIMIT $percount");
	while($rt = $DDB->fetch_array($query)){
        $lastid = $rt['did'];
		//去除图片的下载链接
		$img_regx = '/<a\s+?href=\s*\".+?batch\.download\.php\?aid=(.+?)\"\s*.*?>(.*?)<\/a>/ies';
		//preg_match_all($img_regx, $rt['message'], $match);
        $content = preg_replace($img_regx, "replace_att('\\1','\\2')", $rt['content']);
        if($rt['content']!=$content){
            $DDB->update("UPDATE pw_diary SET content='".addslashes($content)."' WHERE did=".$rt['did']);
        }
    }
	$maxid = $DDB->get_value("SELECT max(did) FROM pw_diary");
    echo '最大id',$maxid.'<br>最后id',$lastid;
    if ($lastid < $maxid)
    {
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}
    else
    {
		report_log();
		newURL($step);
	}
} else if($step == 7) {
	if(!$start){
        $start = $DDB->get_value("SELECT min(did) FROM a_diary");
        $start = $start - 1;
	}
    $percount = 1000;
	//序列化pw_diary的aid
	$query = $DDB->query("SELECT did,aid FROM {$pw_prefix}diary WHERE did > $start ORDER BY did LIMIT $percount");
	while($rt = $DDB->fetch_array($query)){
        $lastid = $rt['did'];
        if(!$rt['aid'])continue;
        $query2 = $DDB->query("SELECT * FROM {$pw_prefix}attachs WHERE did={$rt['did']}");
        $attach = array();
	    while($rt2 = $DDB->fetch_array($query2)){
            $attach[$rt2['aid']] = array(
                'aid' => $rt2['aid'],
                'name' => $rt2['name'],
                'type' => $rt2['type'],
                'attachurl' => $rt2['attachurl'],
                'needrvrc' => $rt2['needrvrc'],
                'special' => $rt2['special'],
                'ctype' => $rt2['ctype'],
                'hits' => $rt2['hits'],
                'size' => $rt2['size'],
                'desc' => $rt2['desc'],
                'ifthumb' => $rt2['ifthumb']
            );
        }
		$DDB->update("UPDATE {$pw_prefix}diary SET aid='".serialize($attach)."' WHERE did=".$rt['did']);
		$s_c++;
	}

	$maxid = $DDB->get_value("SELECT max(did) FROM {$pw_prefix}diary");
    echo '最大id',$maxid.'<br>最后id',$lastid;
    if ($lastid < $maxid)
    {
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}
    else
    {
		report_log();
		newURL($step);
	}
} else if($step == 8){
	if(!$start){
		//$DDB->query("TRUNCATE TABLE {$pw_prefix}cnalbum");
		//$DDB->query("TRUNCATE TABLE {$pw_prefix}cnphoto");
		$DDB->query("CREATE TABLE IF NOT EXISTS `a_cnalbum` (
  `cid` int(10) unsigned NOT NULL,
  `old_itemid` int(10) unsigned NOT NULL,
  KEY `old_itemid` (`old_itemid`)
) ENGINE=MyISAM DEFAULT CHARSET={$dest_charset};
");
	}

	//处理相册
	$sql = "SELECT d.itemid,d.uid,d.username,d.dateline,d.subject,d.lastpost,c.message,c.image,c.imagenum FROM {$source_prefix}spaceitems d LEFT JOIN {$source_prefix}spaceimages c  USING (itemid) WHERE d.type = 'image' AND itemid>$start LIMIT $percount";
	$result = $SDB->query($sql);
	while($rt = $SDB->fetch_array($result)){
        $lastid = $rt['itemid'];
		add_s($rt);
        /*
		$insert_album = "REPLACE INTO {$pw_prefix}cnalbum (aid, aname, aintro, ownerid, owner, photonum, crtime,lasttime, lastphoto)
						VALUES ('{$rt['itemid']}', '{$rt['subject']}', '{$rt['message']}', {$rt['uid']}, '{$rt['username']}',
						{$rt['imagenum']}, {$rt['dateline']}, {$rt['lastpost']}, '{$rt['image']}')";*/
		$insert_album = "INSERT INTO {$pw_prefix}cnalbum (aname, aintro, ownerid, owner, photonum, crtime,lasttime, lastphoto)
						VALUES ('{$rt['subject']}', '{$rt['message']}', {$rt['uid']}, '{$rt['username']}',
						{$rt['imagenum']}, {$rt['dateline']}, {$rt['lastpost']}, '{$rt['image']}')";
		$DDB->update($insert_album);
		$aid = $DDB->insert_id();
        $DDB->update("INSERT INTO a_cnalbum(cid,old_itemid) VALUES({$newcid},'".$rt['itemid']."')");
		$photo_sql = "SELECT * FROM {$source_prefix}attachments WHERE itemid = {$rt['itemid']} AND type='image'";
		$photo_handle = $SDB->query($photo_sql);
		while($photo = $SDB->fetch_array($photo_handle)){
			$insert_cnphoto = "REPLACE INTO {$pw_prefix}cnphoto (aid, path, uploader, uptime )
							VALUES($aid, '{$photo['filepath']}', '{$rt['username']}', {$photo['dateline']})";
			$DDB->update($insert_cnphoto);
			$s_c ++;
		}
	}
	$itemid = $SDB->get_value("SELECT max(itemid) FROM {$source_prefix}spaceitems WHERE type = 'image'");
    echo '最大id',$maxid.'<br>最后id',$lastid;
	if ($lastid < $itemid)
	{
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
		report_log();
		newURL($step);
	}
} else if($step == 9) {
	//处理用户收藏的日志
	//	require_once S_P.'tmp_diary.php';
	$fav_query_sql = "SELECT * FROM {$source_prefix}favorites LIMIT $start, $percount";
	$fav_handle = $SDB->query($fav_query_sql);
	$goon = 0;
	while($fav = $SDB->fetch_array($fav_handle)){
		add_s($fav);
		//$did = $_diary[$fav['itemid']];
		//var_dump($fav); var_dump($did);
		//echo "<br/>";
        $newdid = $DDB->get_value("SELECT did FROM a_diary WHERE old_itemid={$fav['itemid']}");
		if(!empty($newdid)) {
			$insert_fav_sql = "REPLACE INTO {$pw_prefix}favors (uid, tids) VALUES ({$fav['uid']}, {$did})";
			$DDB->update($insert_fav_sql);
		}
		$goon ++;
		$s_c ++;
	}

	if ($goon == $percount)
	{
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else {
		report_log();
		newURL($step);
	}

} else if($step == 10) {
	//处理日志评论
	require_once S_P.'tmp_diary.php';
	if(!$start){
		//$DDB->query("TRUNCATE TABLE {$pw_prefix}comment");
	}

	$query_comments_sql = "SELECT * FROM  `{$source_prefix}spacecomments` WHERE type = 'blog' ORDER BY cid LIMIT $start, $percount";
	$comments_handle = $SDB->query($query_comments_sql);

	while($c = $SDB->fetch_array($comments_handle)){
		add_s($c);
		//$diary_id = $_diary[$c['itemid']];
        $newdid = $DDB->get_value("SELECT did FROM a_diary WHERE old_itemid={$c['itemid']}");
		$c['message'] = addslashes(dz_ubb(str_replace($_dzface,$_pwface,$c['message'])));
		$insert_comments_sql = "INSERT INTO {$pw_prefix}comment (uid, username, title, type, typeid, upid, postdate, ifwordsfb )
								VALUES({$c['authorid']}, '{$c['author']}', '{$c['message']}', 'diary', {$newdid}, 0, {$c['dateline']}, 1);";
		$DDB->update($insert_comments_sql);
		$s_c++;
	}

	$comments_maxid = $SDB->get_value("SELECT max(cid) FROM {$source_prefix}spacecomments  WHERE type = 'blog' ");

	if ($comments_maxid > $start){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	} else {
		report_log();
		newURL($step);
	}
} else if($step == 11){
	//处理日志总数
	$blog_num_sql = "SELECT uid, COUNT(itemid) AS num FROM {$source_prefix}spaceitems WHERE type = 'blog'  GROUP BY uid";
	$blog_num_handle = $SDB->query($blog_num_sql);

	while($rt = $SDB->fetch_array($blog_num_handle)){
		$insert_blog_num = "REPLACE INTO {$pw_prefix}ouserdata  (`uid`, `diarynum`) VALUES ({$rt['uid']}, {$rt['num']}) ";
		$DDB->update($insert_blog_num);
		$goon ++;
		$s_c ++;
	}
	if ($goon == $percount)
	{
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else {
		report_log();
		newURL($step);
	}

} else if($step == 12){
	//处理图片总数，处理ouserdata表的时候要谨慎！
	$photo_num_sql = "SELECT uid, COUNT(itemid) AS num FROM {$source_prefix}spaceitems WHERE type = 'image' GROUP BY uid";
	$photo_num_handle = $SDB->query($photo_num_sql);
	while($rt = $SDB->fetch_array($photo_num_handle)){
		//$insert_photo_num = "REPLACE INTO {$pw_prefix}ouserdata  (`uid`, `photonum`) VALUES ({$rt['uid']}, {$rt['num']})";
        $insert_photo_num = "UPDATE {$pw_prefix}ouserdata SET `photonum`={$rt['num']} WHERE `uid`={$rt['uid']}";
		$DDB->update($insert_photo_num);
		$goon ++;
		$s_c ++;
	}

	if ($goon == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else {
		report_log();
		newURL($step);
	}


} else if($step == 13) {
	//处理用户日志数和图片数，处理ouserdata表的时候要谨慎！
	$space_num_sql = "SELECT uid, spaceblognum, spaceimagenum FROM {$source_prefix}userspaces GROUP BY uid";
	$space_num_handle = $SDB->query($space_num_sql);

	while($rt = $SDB->fetch_array($space_num_handle)){
		//$insert_num = "REPLACE INTO {$pw_prefix}ouserdata  (`uid`, `diarynum`, `photonum`) VALUES ({$rt['uid']}, {$rt['spaceblognum']}, {$rt['spaceimagenum']})";
        $insert_num = "UPDATE {$pw_prefix}ouserdata SET `diarynum`={$rt['spaceblognum']}, `photonum`={$rt['spaceimagenum']} WHERE `uid`={$rt['uid']}";
		$DDB->update($insert_num);
		$goon ++;
		$s_c ++;
	}

	if ($goon == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else {
		report_log();
		newURL($step);
	}
} else if($step == 14) {
	//处理用户留言，处理ouserdata表的时候要谨慎！
	$space_num_sql = "SELECT uid, spaceblognum, spaceimagenum FROM {$source_prefix}guestbooks GROUP BY uid";
	$space_num_handle = $SDB->query($space_num_sql);

	while($rt = $SDB->fetch_array($space_num_handle)){
		//$insert_num = "REPLACE INTO {$pw_prefix}ouserdata  (`uid`, `diarynum`, `photonum`) VALUES ({$rt['uid']}, {$rt['spaceblognum']}, {$rt['spaceimagenum']})";
        $insert_num = "UPDATE {$pw_prefix}ouserdata SET `diarynum`={$rt['spaceblognum']}, `photonum`={$rt['spaceimagenum']} WHERE `uid`={$rt['uid']}";
		$DDB->update($insert_num);
		$goon ++;
		$s_c ++;
	}

	if ($goon == $percount){
		refreshto($cpage.'&step='.$step.'&start='.$end.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else {
		report_log();
		newURL($step);
	}
} else {
	if(!file_exists(S_P."tmp_uch.php")){
		P_unlink(S_P."tmp_uch.php");
	}
	ObHeader($basename.'?action=finish&dbtype='.$dbtype);
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

function dz_ubb($content)
{
	$content = str_replace(array('[wma]','[/wma]','[flash]','[swf]','[/swf]','[rm]','[ra]','[php]','[/php]','[/ra]','[wmv]','[mp3]','[/mp3]'),array('[wmv=0]','[/wmv]','[flash=314,256,1]','[flash=314,256,1]','[/flash]','[rm=314,256,1]','[rm=314,256,1]','[code]','[/code]','[/rm]','[wmv=314,256,1]','[wmv=1]','[/wmv]'),$content);
	$content = preg_replace(array('~\[code\](.+?)\[\/code\]~ies','~\[media=mp3,\d+?,\d+?,(?:1|0)\](.+?)\[\/media\]~i','~\[media=(?:wmv|mov|wma),(\d+?),(\d+?),(1|0)\](.+?)\[\/media\]~i','~\[media=(rm|ra),(\d+?),(\d+?),(1|0)\](.+?)\[\/media\]~i','~\[hide=(\d+?)\](.+?)\[\/hide\]~is','~\[hide\](.+?)\[\/hide\]~is','~\[localimg=[0-9]+,[0-9]+\]([0-9]+)\[\/localimg\]~is','~\[local\]([0-9]+)\[\/local\]~is','~\[attach\]([0-9]+)\[\/attach\]~is','/\[img=[0-9]+,[0-9]+\]/i','/\[size=(\d+(\.\d+)?(px|pt|in|cm|mm|pc|em|ex|%)+?)\]/i'),array("ccode('\\1')",'[wmv=0]\\1[/wmv]','[wmv=\\1,\\2,\\3]\\4[/wmv]','[rm=\\2,\\3,\\4]\\5[/rm]','[sell=\\1]\\2[/sell]','[post]\\1[/post]','[attachment=\\1]','[attachment=\\1]','[attachment=\\1]','[img]',''),$content);
	return $content;
}

function getDiaryNum($typeid, $SDB, $source_prefix = 'supe_') {
	$count = $SDB->get_value("SELECT COUNT(itemid) FROM {$source_prefix}spaceitems WHERE type = 'blog' AND itemtypeid =".pwEscape($typeid)) ;
	return $count;
}
