<?php
!function_exists('readover') && exit('Forbidden');

$lang['right_title'] = array(
	'basic'		=> '基本权限',
	'read'		=> '帖子权限',
	'att'		=> '附件权限',
	'special'	=> '用户组购买',
	'system'	=> '管理权限'
);
$lang['right'] = array (
	'basic' => array(
		'allowhide' => array(
			'title'	=> '可隐身登录',
			'html'	=> '<input type="radio" value="1" name="group[allowhide]" $allowhide_Y />是
			<input type="radio" value="0" name="group[allowhide]" $allowhide_N />否'
		),
		'allowread'	=> array(
			'title'	=> '可浏览帖子',
			'html'	=> '<input type="radio" value="1" name="group[allowread]" $allowread_Y />是
			<input type="radio" value="0" name="group[allowread]" $allowread_N />否'
		),
		'allowsearch'	=> array(
			'title'	=> '搜索控制',
			'html'	=> '<input type="radio" value="0" name="group[allowsearch]" $allowsearch_0 />不允许 <br />
			<input type="radio" value="1" name="group[allowsearch]" $allowsearch_1 />只允许搜索主题<br />
			<input type="radio" value="2" name="group[allowsearch]" $allowsearch_2 />允许搜索主题和内容'
		),
		'allowmember'	=> array(
			'title'	=> '可查看会员列表',
			'html'	=> '<input type="radio" value="1" name="group[allowmember]" $allowmember_Y />是
			<input type="radio" value="0" name="group[allowmember]" $allowmember_N />否'
		),
		'allowprofile'	=> array(
			'title'	=> '可查看会员资料',
			'html'	=> '<input type="radio" value="1" name="group[allowprofile]" $allowprofile_Y />是
			<input type="radio" value="0" name="group[allowprofile]" $allowprofile_N />否'
		),
		'atclog' => array(
			'title'	=> '是否允许查看帖子操作记录',
			'desc'	=> '(允许用户查看自己的帖子被操作情况)',
			'html'	=> '<input type="radio" value="1" name="group[atclog]" $atclog_Y />是
			<input type="radio" value="0" name="group[atclog]" $atclog_N />否'
		),
		'show' => array(
			'title'	=> '是否允许使用展区功能',
			'html'	=> '<input type="radio" value="1" name="group[show]" $show_Y />是
			<input type="radio" value="0" name="group[show]" $show_N />否'
		),
		'allowreport' => array(
			'title'	=> '可使用报告功能',
			'html'	=> '<input type="radio" value="1" name="group[allowreport]" $allowreport_Y />是
			<input type="radio" value="0" name="group[allowreport]" $allowreport_N />否'
		),
		'upload' => array(
			'title'	=> '有头像上传功能',
			'html'	=> '<input type="radio" value="1" name="group[upload]" $upload_Y />是
			<input type="radio" value="0" name="group[upload]" $upload_N />否'
		),
		'allowportait'	=> array(
			'title'	=> '能自定义头像',
			'html'	=> '<input type="radio" value="1" name="group[allowportait]" $allowportait_Y />是
			<input type="radio" value="0" name="group[allowportait]" $allowportait_N />否'
		),
		'allowhonor'	=> array(
			'title'	=> '能使用个性签名功能',
			'html'	=> '<input type="radio" value="1" name="group[allowhonor]" $allowhonor_Y />是
			<input type="radio" value="0" name="group[allowhonor]" $allowhonor_N />否'
		),
		'allowmessege'	=> array(
			'title'	=> '能发送短消息',
			'html'	=> '<input type="radio" value="1" name="group[allowmessege]" $allowmessege_Y />是
			<input type="radio" value="0" name="group[allowmessege]" $allowmessege_N />否'
		),
		'allowsort'	=> array(
			'title'	=> '能查看统计排行',
			'html'	=> '<input type="radio" value="1" name="group[allowsort]" $allowsort_Y />是
			<input type="radio" value="0" name="group[allowsort]" $allowsort_N />否'
		),
		'alloworder'	=> array(
			'title'	=> '能使用主题排序(主题列表页面)',
			'html'	=> '<input type="radio" value="1" name="group[alloworder]" $alloworder_Y />是
			<input type="radio" value="0" name="group[alloworder]" $alloworder_N />否'
		),
		'viewipfrom'	=> array(
			'title'	=> '能查看ip来源',
			'desc'	=> '(如果论坛核心设置中关闭此功能，则此项设置无效)',
			'html'	=> '<input type="radio" value="1" name="group[viewipfrom]" $viewipfrom_Y />是
			<input type="radio" value="0" name="group[viewipfrom]" $viewipfrom_N />否'
		),
		'searchtime'	=> array(
			'title'	=> '两次搜索时间间隔(秒)',
			'html'	=> '<input size="35" class="input" name="group[searchtime]" value="$searchtime" />'
		),
		'schtime' => array(
			'title'	=> '搜索发表时间范围',
			'html'	=> '<select name="group[schtime]">
				<option value="all" $schtime_all>所有主题</option>
				<option value="86400" $schtime_86400>1天内的主题</option>
				<option value="172800" $schtime_172800>2天内的主题</option>
				<option value="604800" $schtime_604800>1星期内的主题</option>
				<option value="2592000" $schtime_2592000>1个月内的主题</option>
				<option value="5184000" $schtime_5184000>2个月内的主题</option>
				<option value="7776000" $schtime_7776000>3个月内的主题</option>
				<option value="15552000" $schtime_15552000>6个月内的主题</option>
				<option value="31536000" $schtime_31536000>1年内的主题</option>
			</select>'
		),
		'signnum' => array(
			'title'	=> '个人签名最大字节数',
			'html'	=> '<input size="35" class="input" name="group[signnum]" value="$signnum" />'
		),
		'imgwidth' => array(
			'title'	=> '签名中的图片最大宽度',
			'desc'	=> '（留空使用核心里的设置）',
			'html'	=> '<input size="5" class="input" name="group[imgwidth]" value="$imgwidth" />'
		),
		'imgheight' => array(
			'title'	=> '签名中的图片最大高度',
			'desc'	=> '（留空使用核心里的设置）',
			'html'	=> '<input class="input" size="5" name="group[imgheight]" value="$imgheight" />'
		),
		'fontsize'	=> array(
			'title'	=> '签名中[size]标签的最大值',
			'desc'	=> '（留空使用核心里的设置）',
			'html'	=> '<input class="input" name="group[fontsize]" value="$fontsize" />'
		),
		'maxmsg'	=> array(
			'title'	=> '最大短消息数目',
			'html'	=> '<input size="35" class="input" value="$maxmsg" name="group[maxmsg]" />'
		),
		'maxsendmsg'	=> array(
			'title'	=> '每日最大发送短消息数目',
			'html'	=> '<input size="35" class="input" value="$maxsendmsg" name="group[maxsendmsg]" />'
		),
		'msggroup'	=> array(
			'title'	=> '可使用“只接收特定用户组的短消息”的功能',
			'html'	=> '<input type="radio" value="1" name="group[msggroup]" $msggroup_Y />是
			<input type="radio" value="0" name="group[msggroup]" $msggroup_N />否'
		),
		'ifmemo'	=> array(
			'title'	=> '能使用便笺的功能',
			'html'	=> '<input type="radio" value="1" name="group[ifmemo]" $ifmemo_Y />是
			<input type="radio" value="0" name="group[ifmemo]" $ifmemo_N />否'
		),
		'pergroup' =>	array(
			'title'	=> '哪些用户组权限可以查看',
			'html'	=> '<input type="checkbox" name="group[pergroup][]" value="member" $pergroup_sel[member] />会员组 <input type="checkbox" name="group[pergroup][]" value="system" $pergroup_sel[system] />系统组 <input type="checkbox" name="group[pergroup][]" value="special" $pergroup_sel[special] />特殊组'
		),
		'maxfavor'	=> array(
			'title'	=> '收藏夹容量',
			'html'	=> '<input size="35" class="input" value="$maxfavor" name="group[maxfavor]" />'
		),
		'maxgraft'	=> array(
			'title'	=> '草稿箱容量',
			'html'	=> '<input size="35" class="input" value="$maxgraft" name="group[maxgraft]" />'
		),
		'pwdlimitime'	=> array(
			'title'	=> '强制用户组密码变更',
			'desc'	=> '(留空则为不限制)',
			'html'	=> '<input size="5" class="input" value="$pwdlimitime" name="group[pwdlimitime]" /> 天'
		),
		'maxcstyles'	=> array(
			'title'	=> '自定义风格数量',
			'desc'	=> '(设置0或留空，则不允许使用自定义风格)',
			'html'	=> '<input size="5" class="input" value="$maxcstyles" name="group[maxcstyles]" />'
		)
	),
	'read'	=> array(
		'allowpost'	=> array(
			'title'	=> '可发表主题',
			'html'	=> '<input type="radio" value="1" name="group[allowpost]" $allowpost_Y />是
			<input type="radio" value="0" name="group[allowpost]" $allowpost_N />否'
		),
		'allowrp'	=> array(
			'title'	=> '可回复主题',
			'html'	=> '<input type="radio" value="1" name="group[allowrp]" $allowrp_Y />是
			<input type="radio" value="0" name="group[allowrp]" $allowrp_N />否'
		),
		'allownewvote'	=> array(
			'title'	=> '可发起投票',
			'html'	=> '<input type="radio" value="1" name="group[allownewvote]" $allownewvote_Y />是
			<input type="radio" value="0" name="group[allownewvote]" $allownewvote_N />否'
		),
		'modifyvote'	=> array(
			'title'	=> '投票发起人能修改投票选项',
			'html'	=> '<input type="radio" value="1" name="group[modifyvote]" $modifyvote_Y />是
			<input type="radio" value="0" name="group[modifyvote]" $modifyvote_N />否'
		),
		'allowvote'	=> array(
			'title' => '可参与投票',
			'html'	=> '<input type="radio" value="1" name="group[allowvote]" $allowvote_Y />是
			<input type="radio" value="0" name="group[allowvote]" $allowvote_N />否'
		),
		'viewvote'	=> array(
			'title'	=> '能查看投票用户',
			'html'	=> '<input type="radio" value="1" name="group[viewvote]" $viewvote_Y />是
			<input type="radio" value="0" name="group[viewvote]" $viewvote_N />否'
		),
		'allowactive'	=> array(
			'title'	=> '可发活动帖',
			'html'	=> '<input type="radio" value="1" name="group[allowactive]" $allowactive_Y />是
			<input type="radio" value="0" name="group[allowactive]" $allowactive_N />否'
		),
		'allowreward'	=> array(
			'title'	=> '可发悬赏帖',
			'html'	=> '<input type="radio" value="1" name="group[allowreward]" $allowreward_Y />是
			<input type="radio" value="0" name="group[allowreward]" $allowreward_N />否'
		),
		'allowgoods'	=> array(
			'title'	=> '可发商品帖',
			'html'	=> '<input type="radio" value="1" name="group[allowgoods]" $allowgoods_Y />是
			<input type="radio" value="0" name="group[allowgoods]" $allowgoods_N />否'
		),
		'allowdebate'	=> array(
			'title'	=> '可发辩论帖',
			'html'	=> '<input type="radio" value="1" name="group[allowdebate]" $allowdebate_Y />是
			<input type="radio" value="0" name="group[allowdebate]" $allowdebate_N />否'
		),
		'htmlcode'	=> array(
			'title'	=> '可发html帖',
			'desc'	=> '这将使用户拥有直接编辑 html 源代码的权利!',
			'html'	=> '<input type="radio" value="1" name="group[htmlcode]" $htmlcode_Y />是
			<input type="radio" value="0" name="group[htmlcode]" $htmlcode_N />否'
		),
		'media'	=> array(
			'title'	=> '发布多媒体是否允许自动播放',
			'html'	=> '<input type="checkbox" name="group[media][]" value="flash" $media_sel[flash] />flash
			<input type="checkbox" name="group[media][]" value="wmv" $media_sel[wmv] />wmv
			<input type="checkbox" name="group[media][]" value="rm" $media_sel[rm] />rm
			<input type="checkbox" name="group[media][]" value="mp3" $media_sel[mp3] />mp3'
		),
		'allowhidden'	=> array(
			'title'	=> '可发隐藏帖',
			'html'	=> '<input type="radio" value="1" name="group[allowhidden]" $allowhidden_Y />是
			<input type="radio" value="0" name="group[allowhidden]" $allowhidden_N />否'
		),
		'allowsell'	=> array(
			'title'	=> '可发出售帖',
			'html'	=> '<input type="radio" value="1" name="group[allowsell]" $allowsell_Y />是
			<input type="radio" value="0" name="group[allowsell]" $allowsell_N />否'
		),
		'allowencode'	=> array(
			'title'	=> '可发加密帖',
			'html'	=> '<input type="radio" value="1" name="group[allowencode]" $allowencode_Y />是
			<input type="radio" value="0" name="group[allowencode]" $allowencode_N />否'
		),
		'anonymous'	=> array(
			'title'	=> '可发匿名帖',
			'html'	=> '<input type="radio" value="1" name="group[anonymous]" $anonymous_Y />是
			<input type="radio" value="0" name="group[anonymous]" $anonymous_N />否'
		),
		'dig'	=> array(
			'title'	=> '是否允许推荐帖子',
			'html'	=> '<input type="radio" value="1" name="group[dig]" $dig_Y />是
			<input type="radio" value="0" name="group[dig]" $dig_N />否'
		),
		'leaveword'	=>	array(
			'title'	=> '楼主有留言权限',
			'html'	=> '<input type="radio" value="1" name="group[leaveword]" $leaveword_Y />是
			<input type="radio" value="0" name="group[leaveword]" $leaveword_N />否'
		),
		'allowdelatc'	=> array(
			'title'	=> '可删除自己的帖子',
			'html'	=> '<input type="radio" value="1" name="group[allowdelatc]" $allowdelatc_Y />是
			<input type="radio" value="0" name="group[allowdelatc]" $allowdelatc_N />否'
		),
		'atccheck'	=> array(
			'title'	=> '发表的文章是否需要管理员审核',
			'desc'	=> '（此项只有是开启版块文章审核时有效）',
			'html'	=> '<input type="radio" value="1" name="group[atccheck]" $atccheck_Y />是
			<input type="radio" value="0" name="group[atccheck]" $atccheck_N />否'
		),
		'markable'	=> array(
			'title'	=> '论坛评分权限',
			'html'	=> '<input type="radio" value="0" name="group[markable]" $markable_0 />无<br />
			<input type="radio" value="1" name="group[markable]" $markable_1 />允许评分<br />
			<input type="radio" value="2" name="group[markable]" $markable_2 />允许重复评分'
		),
		'maxcredit'	=> array(
			'title' => '评分上限<font color=blue> 说明：</font>每天允许的最大评分点数',
			'html'	=> '<input type="text" class="input" value="$maxcredit" name="group[maxcredit]" />'
		),
		'marklimit' => array(
			'title'	=> '评分限制<font color=blue> 说明：</font>每次评分的最大和最小值',
			'html'	=> '最小 <input type=text size="3" class="input" value="$minper" name="group[marklimit][0]" /> 最大 <input type=text size="3" class="input" value="$maxper" name="group[marklimit][1]" />'
		),
		'markctype'	=> array(
			'title'	=> '允许的评分类型',
			'html'	=> '$credit_type'
		),
		'markdt'	=> array(
			'title'	=> '评分是否需要扣除自身相应的积分',
			'html'	=> '<input type="radio" value="1" name="group[markdt]" $markdt_Y />是
			<input type="radio" value="0" name="group[markdt]" $markdt_N />否'
		),
		'postlimit'	=> array(
			'title'	=> '每天最多允许发表多少篇帖子',
			'desc'	=> '(设置为0不限制)',
			'html'	=> '<input size="35" class="input" value="$postlimit" name="group[postlimit]" />'
		),
		'postpertime'	=> array(
			'title'	=> '灌水预防',
			'desc'	=> '(多少秒间隔内不能发帖,设为0则不做限制)',
			'html'	=> '<input size="35" class="input" value="$postpertime" name="group[postpertime]" />'
		),
		'edittime'	=> array(
			'title'	=> '编辑时间约束(分钟)',
			'desc'	=> '超过设定时间后拒绝用户编辑。留空或者键入0则没有约束',
			'html'	=> '<input size="35" class="input" value="$edittime" name="group[edittime]" />'
		)
	),
	'att'	=> array(
		'allowupload'	=> array(
			'title'	=> '上传附件权限',
			'desc'	=> '<font color=blue> 说明：</font>可在版块设置处设置上传附件“奖励或扣除”论坛积分',
			'html'	=> '<input type="radio" value="0" name="group[allowupload]" $allowupload_0 />不允许上传附件<br /><input type="radio" value="1" name="group[allowupload]" $allowupload_1 />允许上传附件，按照版块设置奖励或扣除论坛积分<br /><input type="radio" value="2" name="group[allowupload]" $allowupload_2 />允许上传附件，不奖励或扣除论坛积分'
		),
		'allowdownload'	=> array(
			'title'	=> '下载附件权限',
			'desc'	=> '<font color=blue> 说明：</font>可在版块设置处设置下载附件“奖励或扣除”论坛积分',
			'html'	=> '<input type="radio" value="0" name="group[allowdownload]" $allowdownload_0 />不允许下载附件<br /><input type="radio" value="1" name="group[allowdownload]" $allowdownload_1 />允许下载附件，按照版块设置奖励或扣除论坛积分<br /><input type="radio" value="2" name="group[allowdownload]" $allowdownload_2 />允许下载附件，不奖励或扣除论坛积分'
		),
		'allownum'	=> array(
			'title'	=> '一天最多上传附件个数',
			'html'	=> '<input size="35" class="input" value="$allownum" name="group[allownum]" />'
		),
		'uploadtype'	=> array(
			'title'	=> '上传附件允许后缀和最大尺寸',
			'desc'	=> '<font color=blue> 说明：</font>留空使用论坛核心设置中的设置',
			'html'	=> '<table width="220">
				<tbody id="mode" style="display:none"><tr class="tr3">
					<td><input class="input" size="10" name="filetype[]" value=""></td>
					<td><input class="input" size="10" name="maxsize[]" value=""> <a style="cursor:pointer;color:#FA891B" onclick="removecols(this);">[删除]</a></td>
				</tr></tbody>
				<tr class="tr3">
					<td>后缀名(小写)</td>
					<td>最大尺寸(KB) <a style="cursor:pointer;color:blue" onclick="addcols(\'mode\',\'ft\');">[添加]</a></td>
				</tr>
				{$upload_type}
				<tbody id="ft"></tbody>
			</table>
			<script language="JavaScript">
			addcols(\'mode\',\'ft\');
			</script>'
		)
	),
	'special' => array(
		'allowbuy'	=> array(
			'title'	=> '允许购买',
			'desc'	=> "开启该功能后，需同时到<a href=\"$admin_file?adminjob=plantodo\"><font color=\"blue\">计划任务</font></a>开启“限期头衔自动回收”功能.",
			'html'	=> '<input type="radio" value="1" name="group[allowbuy]" $allowbuy_Y />是
			<input type="radio" value="0" name="group[allowbuy]" $allowbuy_N />否'
		),
		'selltype'	=> array(
			'title'	=> '特殊组购买使用币种',
			'html'	=> '<select name="group[selltype]">$special_type</select>'
		),
		'sellprice'	=> array(
			'title'	=> '用户使用积分购买特殊组权限的每日价格',
			'html'	=> '<input type="text" class="input" name="group[sellprice]" value="$sellprice" />'
		),
		'selllimit'	=> array(
			'title'	=> '用户至少需要购买特殊组的天数限制',
			'html'	=> '<input type="text" class="input" name="group[selllimit]" value="$selllimit" />'
		),
		'sellinfo'	=> array(
			'title'	=> '特殊组描述',
			'desc'	=> '可以填写购买说明和该用户组拥有的特殊权限',
			'html'	=> '<textarea name="group[sellinfo]" rows="5" cols="30">$sellinfo</textarea>'
		)
	),
	'system'	=> array(
		'allowadmincp'	=> array(
			'title'	=> '可进后台',
			'html'	=> '<input type="radio" value="1" name="group[allowadmincp]" $allowadmincp_Y />是
			<input type="radio" value="0" name="group[allowadmincp]" $allowadmincp_N />否'
		),
		'superright' => array(
			'title'	=> '超级管理权限',
			'desc'	=> "<br /><font color=\"red\">是</font>：表明以下针对版块的权限设置对所有版块生效（例如：管理员）<br /><font color=\"red\">否</font>：表明以下针对版块的权限设置对所有版块无效，此时如果要配置单个版块的管理权限，需要到<a href=\"$admin_file?adminjob=setforum\">版块管理</a>里进行配置（例如：版主）",
			'html'	=> '<input type="radio" value="1" name="group[superright]" $superright_Y />是
			<input type="radio" value="0" name="group[superright]" $superright_N />否'
		)
	),
	'systemforum' => array(
		'viewhide'	=> array(
			'title'	=> '查看加密帖(隐藏，加密，出售，匿名)',
			'html'	=> '<input type="radio" value="1" name="group[viewhide]" $viewhide_Y />是
			<input type="radio" value="0" name="group[viewhide]" $viewhide_N />否'
		),
		'postpers'	=> array(
			'title'	=> '灌水<font color=blue> 说明：</font>不受灌水时间限制',
			'html'	=> '<input type="radio" value="1" name="group[postpers]" $postpers_Y />是
			<input type="radio" value="0" name="group[postpers]" $postpers_N />否'
		),
		'replylock'	=> array(
			'title'	=> '回复锁定帖',
			'html'	=> '<input type=radio value=1 $replylock_Y name=group[replylock]>是
			<input type=radio value=0 $replylock_N name=group[replylock]>否'
		),
		'viewip'	=> array(
			'title'	=> '查看IP<font color=blue> 说明：</font>浏览帖子时显示',
			'html'	=> '<input type="radio" value="1" name="group[viewip]" $viewip_Y />是
			<input type="radio" value="0" name="group[viewip]" $viewip_N />否'
		),
		'topped'	=> array(
			'title'	=> '置顶权限',
			'html'	=> '<input type="radio" value="0" name="group[topped]" $topped_0 />无<br />
			<input type="radio" value="1" name="group[topped]" $topped_1 />版块置顶<br />
			<input type="radio" value="2" name="group[topped]" $topped_2 />版块置顶,分类置顶<br />
			<input type="radio" value="3" name="group[topped]" $topped_3 />版块置顶,分类置顶,总置顶'
		),
		'typeadmin'	=> array(
			'title'	=> '前台" <font color=blue>精、锁、提、亮、压</font> "管理权限',
			'html'	=> '<input type="radio" value="1" name="group[typeadmin]" $typeadmin_Y />是
			<input type="radio" value="0" name="group[typeadmin]" $typeadmin_N />否'
		),
		'tpctype'	=> array(
			'title'	=> '进行<font color="blue">主题分类管理</font>',
			'desc'	=> '<font color=blue> 说明：</font>主题分类批量管理权限',
			'html'	=> '<input type="radio" value="1" name="group[tpctype]" $tpctype_Y />是
			<input type="radio" value="0" name="group[tpctype]" $tpctype_N />否'
		),
		'tpccheck'	=> array(
			'title'	=> '进行<font color="blue">主题验证管理</font>',
			'desc'	=> '<font color=blue> 说明：</font>前台主题验证管理权限',
			'html'	=> '<input type="radio" value="1" name="group[tpccheck]" $tpccheck_Y />是
			<input type="radio" value="0" name="group[tpccheck]" $tpccheck_N />否'
		),
		'delatc'	=> array(
			'title'	=> '批量删除主题',
			'desc'	=> '<font color=blue> 说明：</font>前台帖子管理权限',
			'html'	=> '<input type="radio" value="1" name="group[delatc]" $delatc_Y />是
			<input type="radio" value="0" name="group[delatc]" $delatc_N />否'
		),
		'moveatc'	=> array(
			'title'	=> '批量移动帖子',
			'desc'	=> '<font color=blue> 说明：</font>前台帖子管理权限',
			'html'	=> '<input type="radio" value="1" name="group[moveatc]" $moveatc_Y />是
			<input type="radio" value="0" name="group[moveatc]" $moveatc_N />否'
		),
		'copyatc'	=> array(
			'title'	=> '批量复制帖子',
			'desc'	=> '<font color=blue> 说明：</font>前台帖子管理权限',
			'html'	=> '<input type="radio" value="1" name="group[copyatc]" $copyatc_Y />是
			<input type="radio" value="0" name="group[copyatc]" $copyatc_N />否'
		),
		'modother'	=> array(
			'title'	=> '删除单一帖子（包括回复）',
			'html'	=> '<input type="radio" value="1" name="group[modother]" $modother_Y />是
			<input type="radio" value="0" name="group[modother]" $modother_N />否'
		),
		'deltpcs'	=> array(
			'title'	=> '编辑用户帖子',
			'html'	=> '<input type="radio" value="1" name="group[deltpcs]" $deltpcs_Y />是
			<input type="radio" value="0" name="group[deltpcs]" $deltpcs_N />否'
		),
		'viewcheck'	=> array(
			'title'	=> '可以查看需要验证的帖子',
			'html'	=> '<input type="radio" value="1" name="group[viewcheck]" $viewcheck_Y />是
			<input type="radio" value="0" name="group[viewcheck]" $viewcheck_N />否'
		),
		'viewclose'	=> array(
			'title'	=> '可以查看关闭帖子',
			'html'	=> '<input type="radio" value="1" name="group[viewclose]" $viewclose_Y />是
			<input type="radio" value="0" name="group[viewclose]" $viewclose_N />否'
		),
		'delattach'	=> array(
			'title'	=> '可以删除附件',
			'html'	=> '<input type="radio" value="1" name="group[delattach]" $delattach_Y />是
			<input type="radio" value="0" name="group[delattach]" $delattach_N />否'
		),
		'shield'	=> array(
			'title'	=> '屏蔽单一主题',
			'html'	=> '<input type="radio" value="1" name="group[shield]" $shield_Y />是
			<input type="radio" value="0" name="group[shield]" $shield_N />否'
		),
		'unite'	=> array(
			'title'	=> '合并主题',
			'html'	=> '<input type="radio" value="1" name="group[unite]" $unite_Y />是
			<input type="radio" value="0" name="group[unite]" $unite_N />否'
		),
		'remind'	=> array(
			'title'	=> '帖子管理提醒功能',
			'html'	=> '<input type="radio" value="1" name="group[remind]" $remind_Y />是
			<input type="radio" value="0" name="group[remind]" $remind_N />否'
		),
		'inspect'	=> array(
			'title'	=> '拥有“版主标记已阅”权限',
			'html'	=> '<input type="radio" value="1" name="group[inspect]" $inspect_Y />是
			<input type="radio" value="0" name="group[inspect]" $inspect_N />否'
		),
		'allowtime'	=> array(
			'title'	=> '不受版块“发帖时间域权限”限制',
			'html'	=> '<input type="radio" value="1" name="group[allowtime]" $allowtime_Y />是
			<input type="radio" value="0" name="group[allowtime]" $allowtime_N />否'
		),
		'banuser'	=> array(
			'title'	=> '禁言用户的权限',
			'desc'	=> '<font color="blue">说明:</font><br />
			<font color="red">无禁言权限:</font>该用户组无权限对会员进行禁言操作<br />
			<font color="red">所有版块:</font>(有禁言权限)并且被禁言会员在所有版块中都没权限发言<br />
			<font color="red">单一版块</font>(有禁言权限)并且被禁言会员在帖子所在版块没权限发言,而在其他版块中可以发言',
			'html'	=> '<input type="radio" value="0" name="group[banuser]" $banuser_0 />无禁言权限<br />
			<input type="radio" value="2" name="group[banuser]" $banuser_2 />单一版块<br />
			<input type="radio" value="1" name="group[banuser]" $banuser_1 />所有版块'
		),
		'bantype'	=> array(
			'title'	=> '永久禁言用户',
			'html'	=> '<input type="radio" value="1" name="group[bantype]" $bantype_Y />是
			<input type="radio" value="0" name="group[bantype]" $bantype_N />否'
		),
		'banmax'	=> array(
			'title'	=> '禁言时间限制',
			'desc'	=> '<font color=blue> 说明：</font>封禁会员的最大天数',
			'html'	=> '<input type=text size="3" class="input" value="$banmax" name="group[banmax]" />'
		)
	)
);
?>