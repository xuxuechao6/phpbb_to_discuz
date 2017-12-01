<?php
/**
 * X-Space 4.0.1 ת������
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
	$SDB = new mysql($source_db_host, $source_db_user, $source_db_password, $source_db_name, $source_charset);
}
/*
CREATE TABLE IF NOT EXISTS `a_tmp` (
  `old_max_id` int(10) unsigned NOT NULL,
  `time` int(10) unsigned NOT NULL,
  `name` varchar(20) NOT NULL,
  `idname` varchar(10) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
*/

if ($step == 1){
	//����
	/*
	if(!$start){
		$DDB->query("TRUNCATE TABLE {$pw_prefix}friends");
	}
	*/
    //http://119.164.219.101/pwb/pwbuilder.php?action=build&dbtype=xspace&step=1
    //echo 'test';exit;
	$query_sql_for_frends = "SELECT * FROM {$source_prefix}friends LIMIT $start, $percount";
	$source_friends = $SDB->query($query_sql_for_frends);
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
	//��־����
	if(!$start){
		//$DDB->query("TRUNCATE TABLE {$pw_prefix}diarytype");
        $dtid = $DDB->get_value("SELECT max(dtid) FROM {$pw_prefix}diarytype");
		$DDB->query("INSERT INTO a_tmp(old_max_id,time,name,idname) VALUES(".$dtid.",".time().",'diarytype','dtid')");
	}

	//itemtypes  => ���˷���� 204
	//spaceitems => ��Ϣ�� 457
	$query_diary_category = "SELECT a.typeid, a.uid, a.typename ,COUNT(b.itemid) AS num FROM "
						  . "{$source_prefix}itemtypes AS a LEFT JOIN {$source_prefix}spaceitems "
						  . "AS b ON a.typeid = b.itemtypeid WHERE b.type = 'blog' "
						  . "GROUP BY b.itemtypeid ORDER BY a.typeid ASC ";
	$diary_category = $SDB->query($query_diary_category);
	//$goon = 0;
	while($rt = $SDB->fetch_array($diary_category)){
		add_s($rt);
		$sql_insert_diary_type = "INSERT INTO {$pw_prefix}diarytype (`uid`, `name`, `num`) "
							   . "VALUES ({$rt['uid']}, '{$rt['typename']}', {$rt['num']})";
		$DDB->update($sql_insert_diary_type);
		$dtid = $DDB->insert_id();
		$category[$rt['typeid']] = $dtid;
		//$goon++;
		$s_c ++;
	}
	writeover(S_P.'tmp_category.php', "\$_category = ".pw_var_export($category).";\n", true);

	report_log();
	newURL($step);
} else if($step == 3){
	//����(������)
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
	//��־
    //http://119.164.219.101/pwb/pwbuilder.php?action=build&dbtype=xspace&step=4&start=1637765
	require_once S_P .'tmp_category.php';

	if(!$start){
		$DDB->query("CREATE TABLE IF NOT EXISTS `a_diary` (
  `did` int(10) unsigned NOT NULL,
  `old_itemid` int(10) unsigned NOT NULL,
  KEY `old_itemid` (`old_itemid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
");
		$DDB->query("TRUNCATE TABLE a_diary");
        $did = $DDB->get_value("SELECT max(did) FROM {$pw_prefix}diary");
		$DDB->query("INSERT INTO a_tmp(old_max_id,time,name,idname) VALUES(".$did.",".time().",'diary','did')");
	}
//todo, a.password������
	$query_diary = "SELECT a.itemid, a.username, a.uid, a.itemtypeid, a.subject, a.dateline,
					a.viewnum, a.replynum, a.haveattach, b.message  FROM {$source_prefix}spaceitems a
					LEFT JOIN {$source_prefix}spaceblogs b ON  a.itemid = b.itemid WHERE a.type = 'blog' AND a.itemid > $start ORDER BY itemid LIMIT $percount";
	$diary_handle = $SDB->query($query_diary);
	$goon = 0;

	$pic_source_url = $xspace_url .  '/attachments';
	$pic_url = $pw_url . '/attachment/photo';

	while($rt = $SDB->fetch_array($diary_handle)){
        $lastid = $rt['itemid'];
		//add_s($rt);
		//����־���������룬ת����ĵ�Ȩ��Ϊ�����Լ��ɼ���
		if(strlen($rt['password']) != 0){
			$rt['privacy'] = 2;
		} else {
			$rt['privacy'] = 0;
		}
		//ȥ��x-space �е��Զ�Tag������
		$tag_regx = '/<a\s+?href=\s*\".+?tag.php\?k=.+?\"\s*.*?>(.+?)<\/a>/is';
		preg_match_all($tag_regx, $rt['message'], $match);

		if(!empty($match)){
			foreach($match[1] as $tag){
				$tag_regx = '/<a\s+?href=\s*\".+?tag.php\?k=.+?\"\s*.*?>\s*' . $tag . '\s*<\/a>/is';
				$rt['message'] = preg_replace($tag_regx, $tag, $rt['message']);
			}
		}
		//ȥ��ͼƬ����������
        /*
		$img_regx = '/<a\s+?href=\s*\".+?batch\.download\.php\?aid=(.+?)\"\s*.*?>(.*?)<\/a>/ies';
		preg_match_all($img_regx, $rt['message'], $match);
        $rt['message'] = preg_replace($img_regx, "replace_att('\\1','\\2')", $rt['message']);
        */
/*
		if(!empty($match)){
			foreach($match[1] as $img){
                //print_r($img);echo '---';
				$tag_regx = '/<a\s+?href=\s*\".+?batch\.download\.php\?aid=.+?\"\s*.*?>\s*' . $img . '\s*<\/a>/is';
				$rt['message'] = preg_replace($img_regx, $img, $rt['message']);
			}
		}*/
		//��HTML����ת����UBB����
		//$rt['message'] = pwEscape($rt['message']);
		/*
		$rt['message'] = html2bbcode($rt['message']);

		//ȥ��ͼƬ����
		$star_regx = '/(\[url.*?\])\s*\[img\]/is';
		$rt['message'] = preg_replace($star_regx , '[img]', $rt['message']);
		$end_regx = '/\[\/img\]\s*(\[\/url\])/is';
		$rt['message'] = preg_replace($end_regx, '[/img]', $rt['message']);
		*/
		/*
		//ת��ͼƬ��URL��ַ
		$url_regx = '/\[img\]\s*' . addcslashes($pic_source_url, ".:/") . '/is';
		$rt['message'] = preg_replace($url_regx, '[img]' . $pic_url, $rt['message']);

		//ת�������ַ�ľ��Ե�ַ
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
        /*
		$insert_diary = "REPLACE INTO {$pw_prefix}diary (did,uid, dtid, username, subject, content, r_num, c_num,
				postdate, ifupload, privacy, ifconvert) VALUES ({$rt['itemid']}, {$rt['uid']}, {$rt['itemtypeid']}, '{$rt['username']}',
				'{$rt['subject']}', '{$rt['message']}',{$rt['viewnum']}, {$rt['replynum']}, {$rt['dateline']},
				{$rt['haveattach']}, {$rt['privacy']}, {$ifconvert})";
                */
		$insert_diary = "INSERT INTO {$pw_prefix}diary (uid, dtid, username, subject, content, r_num, c_num,
				postdate, ifupload, privacy, ifconvert) VALUES ({$rt['uid']}, {$rt['itemtypeid']}, '{$rt['username']}',
				'{$rt['subject']}', '{$rt['message']}',{$rt['viewnum']}, {$rt['replynum']}, {$rt['dateline']},
				{$rt['haveattach']}, {$rt['privacy']}, {$ifconvert})";

		$DDB->update($insert_diary);
		$did = $DDB->insert_id();
        //echo $did;exit;
        $DDB->update("INSERT INTO a_diary(did,old_itemid) VALUES({$did},{$rt['itemid']})");
		//$diary[$rt['itemid']] = $did;
		unset($rt);
		$goon++;
		$s_c ++;
	}
    /*
	$tmp_file_name = S_P.'tmp_diary.php';
	if(file_exists($tmp_file_name)){
		require_once S_P.'tmp_diary.php';
		//$_diary = array_merge($_diary, $diary);
		$_diary = $_diary + $diary;
		writeover(S_P.'tmp_diary.php', "\$_diary = ".pw_var_export($_diary).";\n", true);
	} else {
		writeover(S_P.'tmp_diary.php', "\$_diary = ".pw_var_export($diary).";\n", true);
	}
    */
	$maxid = $SDB->get_value("SELECT max(itemid) FROM {$source_prefix}spaceitems WHERE type = 'blog'");
    echo '���id',$maxid.'<br>���id',$lastid;
    if ($lastid < $maxid)
    {
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
    {
		report_log();
		newURL($step);
	}
} else if($step == 5) {
	if(!$start){
		$DDB->query("CREATE TABLE IF NOT EXISTS `a_attachs` (
  `aid` int(10) unsigned NOT NULL,
  `old_aid` int(10) unsigned NOT NULL,
  KEY `old_aid` (`old_aid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
");
		//$DDB->query("TRUNCATE TABLE a_attachs");
        $aid = $DDB->get_value("SELECT max(aid) FROM {$pw_prefix}attachs");
		$DDB->query("INSERT INTO a_tmp(old_max_id,time,name,idname) VALUES(".$aid.",".time().",'attachs','aid')");
	}
	//������
    /*
	$query = $SDB->query("SELECT a.* FROM {$source_prefix}attachments a
						 LEFT JOIN {$source_prefix}spaceitems  d USING(itemid) WHERE a.aid >=$start AND a.aid < $end
						 AND a.type = 'blog' ");
*/
$lastid = $start;
	$query = $SDB->query("SELECT a.* FROM {$source_prefix}attachments a WHERE a.aid > $start AND a.type = 'blog' ORDER BY aid LIMIT $percount");
	while($a = $SDB->fetch_array($query)){
        $lastid = $a['aid'];
		add_s($a);
		$fileinfo = getfileinfo($a['filename']);
		$a['filetype'] = $fileinfo['type'];
        $newdid = $DDB->get_value("SELECT did FROM a_diary WHERE old_itemid={$a['itemid']}");
		$sql = "INSERT INTO {$pw_prefix}attachs (uid, did, name, type, size, uploadtime, attachurl,hits)
			VALUES ({$a['uid']}, '{$newdid}', '{$a['filename']}', '{$a['filetype']}', {$a['size']}, {$a['dateline']},
			'{$a['filepath']}', {$a['downloads']} )";
		$DDB->update($sql);
		$aid = $DDB->insert_id();
        $DDB->update("INSERT INTO a_attachs(aid,old_aid) VALUES({$aid},{$a['aid']})");
        $DDB->update("UPDATE pw_diary SET aid=1 WHERE did=".$newdid);
		$s_c++;
	}
//echo "SELECT max(aid) FROM {$source_prefix}attachments WHERE type = 'blog'";exit;
	$maxid = $SDB->get_value("SELECT max(aid) FROM {$source_prefix}attachments WHERE type = 'blog'");
    //echo '���id',$maxid.'<br>���id',$lastid;exit;
    if ($lastid < $maxid)
    {
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}
    else
    {
		report_log();
		newURL($step);
	}

} else if($step == 6){

	$query = $DDB->query("SELECT * FROM pw_diary WHERE did > $start ORDER BY did LIMIT $percount");
	while($rt = $DDB->fetch_array($query)){
        $lastid = $rt['did'];
		//ȥ��ͼƬ����������
		$img_regx = '/<a\s+?href=\s*\".+?batch\.download\.php\?aid=(.+?)\"\s*.*?>(.*?)<\/a>/ies';
		//preg_match_all($img_regx, $rt['message'], $match);
        $content = preg_replace($img_regx, "replace_att('\\1','\\2')", $rt['content']);
        if($rt['content']!=$content){
            $DDB->update("UPDATE pw_diary SET content='".addslashes($content)."' WHERE did=".$rt['did']);
        }
    }
	$maxid = $DDB->get_value("SELECT max(did) FROM pw_diary");
    echo '���id',$maxid.'<br>���id',$lastid;
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
	//����pw_diary��aid�ĳ���
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
    echo '���id',$maxid.'<br>���id',$lastid;
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
        $aid = $DDB->get_value("SELECT max(aid) FROM {$pw_prefix}cnalbum");
        $pid = $DDB->get_value("SELECT max(pid) FROM {$pw_prefix}cnphoto");
        if(!$aid)$aid=0;
        if(!$pid)$pid=0;
		$DDB->query("INSERT INTO a_tmp(old_max_id,time,name,idname) VALUES(".$aid.",".time().",'cnalbum','aid')");
		$DDB->query("INSERT INTO a_tmp(old_max_id,time,name,idname) VALUES(".$pid.",".time().",'cnphoto','pid')");
	}

	//�������
	$sql = "SELECT d.itemid, d.uid, d.username,d.dateline , d.subject, d.lastpost, c.message, c.image, c.imagenum FROM {$source_prefix}spaceitems d LEFT JOIN {$source_prefix}spaceimages c  USING (itemid) WHERE d.type = 'image' AND itemid>$start ORDER BY itemid LIMIT $percount";
	$query = $SDB->query($sql);
	while($rt = $SDB->fetch_array($query)){
        $lastid = $rt['itemid'];
		add_s($rt);
		$insert_album = "INSERT INTO {$pw_prefix}cnalbum (aname, aintro, ownerid, owner, photonum, crtime,lasttime, lastphoto)
						VALUES ('{$rt['subject']}', '{$rt['message']}', {$rt['uid']}, '{$rt['username']}',
						{$rt['imagenum']}, {$rt['dateline']}, {$rt['lastpost']}, '{$rt['image']}')";
		$DDB->update($insert_album);
		$newcid = $DDB->insert_id();
        $DDB->update("INSERT INTO a_cnalbum(cid,old_itemid) VALUES({$newcid},'".$rt['itemid']."')");
		$photo_sql = "SELECT * FROM {$source_prefix}attachments WHERE itemid = {$rt['itemid']} AND type='image'";
		$photo_handle = $SDB->query($photo_sql);

		while($photo = $SDB->fetch_array($photo_handle)){
			$insert_cnphoto = "INSERT INTO {$pw_prefix}cnphoto (aid, path, uploader, uptime )
							VALUES($newcid, '{$photo['filepath']}', '{$rt['username']}', {$photo['dateline']})";
			$DDB->update($insert_cnphoto);
			$s_c ++;
		}
	}
	$maxid = $SDB->get_value("SELECT max(itemid) FROM {$source_prefix}spaceitems WHERE type = 'image'");
    echo '���id',$maxid.'<br>���id',$lastid;
    if ($lastid < $maxid)
	{
		refreshto($cpage.'&step='.$step.'&start='.$lastid.'&f_c='.$f_c.'&s_c='.$s_c);
	}
	else
	{
		report_log();
		newURL($step);
	}
} else if($step == 9) {
	//�����û��ղص���־
	//	require_once S_P.'tmp_diary.php';
	$fav_query_sql = "SELECT * FROM {$source_prefix}favorites  LIMIT $start, $percount";
	$fav_handle = $SDB->query($fav_query_sql);
	$goon = 0;
	while($fav = $SDB->fetch_array($fav_handle)){
		add_s($fav);
		//$did = $_diary[$fav['itemid']];
		//var_dump($fav); var_dump($did);
		//echo "<br/>";
        $newdid = $DDB->get_value("SELECT did FROM a_diary WHERE old_itemid={$fav['itemid']}");
		if(!empty($newdid)) {
			$insert_fav_sql = "REPLACE INTO {$pw_prefix}favors (uid, tids) VALUES ({$fav['uid']}, {$newdid})";
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
	//������־����
	//require_once S_P.'tmp_diary.php';
	if(!$start){
		//$DDB->query("TRUNCATE TABLE {$pw_prefix}comment");
        $id = $DDB->get_value("SELECT max(id) FROM {$pw_prefix}comment");
		$DDB->query("INSERT INTO a_tmp(old_max_id,time,name,idname) VALUES(".$id.",".time().",'comment','id')");
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
	//������־����
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
	//����ͼƬ����
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
	//�����û���־����ͼƬ��
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
    echo 14;exit;
		newURL($step);
	//�����û�����
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
} else if($step == 15) {
    //echo 14;exit;
	$query = $DDB->query("SELECT * FROM a_tmp");
	while($rt = $DDB->fetch_array($query)){
        echo "DELETE FROM {$pw_prefix}".$rt['name']." WHERE ".$rt['idname'].">".$rt['old_max_id'].';<br>';
        echo "ALTER TABLE {$pw_prefix}".$rt['name']." AUTO_INCREMENT =100;<br>";
		//$DDB->update("DELETE FROM {$pw_prefix}".$rt['name']." WHERE ".$rt['idname'].">".$rt['old_max_id']);
	}
    echo "TRUNCATE TABLE a_tmp;";
    //$DDB->query("TRUNCATE TABLE a_tmp");
    exit;
} else {
    echo 111;exit;
	if(!file_exists(S_P."tmp_uch.php")){
		P_unlink(S_P."tmp_uch.php");
	}
	ObHeader($basename.'?action=finish&dbtype='.$dbtype);
}


//ȡ�ø�������
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

function replace_att($a,$b)
{
    global $DDB;
    if(is_numeric($a)){
        //echo "SELECT aid FROM a_attachs WHERE old_aid=".$a;exit;
        $newaid = $DDB->get_value("SELECT aid FROM a_attachs WHERE old_aid=".$a);
        //echo $newaid;exit;
	    return "[attachment=$newaid]";
    }else{
        return $b;
    }

}

function getDiaryNum($typeid, $SDB, $source_prefix = 'supe_') {
	$count = $SDB->get_value("SELECT COUNT(itemid) FROM {$source_prefix}spaceitems WHERE type = 'blog' AND itemtypeid =".pwEscape($typeid)) ;
	return $count;
}
