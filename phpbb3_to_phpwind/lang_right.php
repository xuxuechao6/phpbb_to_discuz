<?php
!function_exists('readover') && exit('Forbidden');

$lang['right_title'] = array(
	'basic'		=> '����Ȩ��',
	'read'		=> '����Ȩ��',
	'att'		=> '����Ȩ��',
	'special'	=> '�û��鹺��',
	'system'	=> '����Ȩ��'
);
$lang['right'] = array (
	'basic' => array(
		'allowhide' => array(
			'title'	=> '�������¼',
			'html'	=> '<input type="radio" value="1" name="group[allowhide]" $allowhide_Y />��
			<input type="radio" value="0" name="group[allowhide]" $allowhide_N />��'
		),
		'allowread'	=> array(
			'title'	=> '���������',
			'html'	=> '<input type="radio" value="1" name="group[allowread]" $allowread_Y />��
			<input type="radio" value="0" name="group[allowread]" $allowread_N />��'
		),
		'allowsearch'	=> array(
			'title'	=> '��������',
			'html'	=> '<input type="radio" value="0" name="group[allowsearch]" $allowsearch_0 />������ <br />
			<input type="radio" value="1" name="group[allowsearch]" $allowsearch_1 />ֻ������������<br />
			<input type="radio" value="2" name="group[allowsearch]" $allowsearch_2 />�����������������'
		),
		'allowmember'	=> array(
			'title'	=> '�ɲ鿴��Ա�б�',
			'html'	=> '<input type="radio" value="1" name="group[allowmember]" $allowmember_Y />��
			<input type="radio" value="0" name="group[allowmember]" $allowmember_N />��'
		),
		'allowprofile'	=> array(
			'title'	=> '�ɲ鿴��Ա����',
			'html'	=> '<input type="radio" value="1" name="group[allowprofile]" $allowprofile_Y />��
			<input type="radio" value="0" name="group[allowprofile]" $allowprofile_N />��'
		),
		'atclog' => array(
			'title'	=> '�Ƿ�����鿴���Ӳ�����¼',
			'desc'	=> '(�����û��鿴�Լ������ӱ��������)',
			'html'	=> '<input type="radio" value="1" name="group[atclog]" $atclog_Y />��
			<input type="radio" value="0" name="group[atclog]" $atclog_N />��'
		),
		'show' => array(
			'title'	=> '�Ƿ�����ʹ��չ������',
			'html'	=> '<input type="radio" value="1" name="group[show]" $show_Y />��
			<input type="radio" value="0" name="group[show]" $show_N />��'
		),
		'allowreport' => array(
			'title'	=> '��ʹ�ñ��湦��',
			'html'	=> '<input type="radio" value="1" name="group[allowreport]" $allowreport_Y />��
			<input type="radio" value="0" name="group[allowreport]" $allowreport_N />��'
		),
		'upload' => array(
			'title'	=> '��ͷ���ϴ�����',
			'html'	=> '<input type="radio" value="1" name="group[upload]" $upload_Y />��
			<input type="radio" value="0" name="group[upload]" $upload_N />��'
		),
		'allowportait'	=> array(
			'title'	=> '���Զ���ͷ��',
			'html'	=> '<input type="radio" value="1" name="group[allowportait]" $allowportait_Y />��
			<input type="radio" value="0" name="group[allowportait]" $allowportait_N />��'
		),
		'allowhonor'	=> array(
			'title'	=> '��ʹ�ø���ǩ������',
			'html'	=> '<input type="radio" value="1" name="group[allowhonor]" $allowhonor_Y />��
			<input type="radio" value="0" name="group[allowhonor]" $allowhonor_N />��'
		),
		'allowmessege'	=> array(
			'title'	=> '�ܷ��Ͷ���Ϣ',
			'html'	=> '<input type="radio" value="1" name="group[allowmessege]" $allowmessege_Y />��
			<input type="radio" value="0" name="group[allowmessege]" $allowmessege_N />��'
		),
		'allowsort'	=> array(
			'title'	=> '�ܲ鿴ͳ������',
			'html'	=> '<input type="radio" value="1" name="group[allowsort]" $allowsort_Y />��
			<input type="radio" value="0" name="group[allowsort]" $allowsort_N />��'
		),
		'alloworder'	=> array(
			'title'	=> '��ʹ����������(�����б�ҳ��)',
			'html'	=> '<input type="radio" value="1" name="group[alloworder]" $alloworder_Y />��
			<input type="radio" value="0" name="group[alloworder]" $alloworder_N />��'
		),
		'viewipfrom'	=> array(
			'title'	=> '�ܲ鿴ip��Դ',
			'desc'	=> '(�����̳���������йرմ˹��ܣ������������Ч)',
			'html'	=> '<input type="radio" value="1" name="group[viewipfrom]" $viewipfrom_Y />��
			<input type="radio" value="0" name="group[viewipfrom]" $viewipfrom_N />��'
		),
		'searchtime'	=> array(
			'title'	=> '��������ʱ����(��)',
			'html'	=> '<input size="35" class="input" name="group[searchtime]" value="$searchtime" />'
		),
		'schtime' => array(
			'title'	=> '��������ʱ�䷶Χ',
			'html'	=> '<select name="group[schtime]">
				<option value="all" $schtime_all>��������</option>
				<option value="86400" $schtime_86400>1���ڵ�����</option>
				<option value="172800" $schtime_172800>2���ڵ�����</option>
				<option value="604800" $schtime_604800>1�����ڵ�����</option>
				<option value="2592000" $schtime_2592000>1�����ڵ�����</option>
				<option value="5184000" $schtime_5184000>2�����ڵ�����</option>
				<option value="7776000" $schtime_7776000>3�����ڵ�����</option>
				<option value="15552000" $schtime_15552000>6�����ڵ�����</option>
				<option value="31536000" $schtime_31536000>1���ڵ�����</option>
			</select>'
		),
		'signnum' => array(
			'title'	=> '����ǩ������ֽ���',
			'html'	=> '<input size="35" class="input" name="group[signnum]" value="$signnum" />'
		),
		'imgwidth' => array(
			'title'	=> 'ǩ���е�ͼƬ�����',
			'desc'	=> '������ʹ�ú���������ã�',
			'html'	=> '<input size="5" class="input" name="group[imgwidth]" value="$imgwidth" />'
		),
		'imgheight' => array(
			'title'	=> 'ǩ���е�ͼƬ���߶�',
			'desc'	=> '������ʹ�ú���������ã�',
			'html'	=> '<input class="input" size="5" name="group[imgheight]" value="$imgheight" />'
		),
		'fontsize'	=> array(
			'title'	=> 'ǩ����[size]��ǩ�����ֵ',
			'desc'	=> '������ʹ�ú���������ã�',
			'html'	=> '<input class="input" name="group[fontsize]" value="$fontsize" />'
		),
		'maxmsg'	=> array(
			'title'	=> '������Ϣ��Ŀ',
			'html'	=> '<input size="35" class="input" value="$maxmsg" name="group[maxmsg]" />'
		),
		'maxsendmsg'	=> array(
			'title'	=> 'ÿ������Ͷ���Ϣ��Ŀ',
			'html'	=> '<input size="35" class="input" value="$maxsendmsg" name="group[maxsendmsg]" />'
		),
		'msggroup'	=> array(
			'title'	=> '��ʹ�á�ֻ�����ض��û���Ķ���Ϣ���Ĺ���',
			'html'	=> '<input type="radio" value="1" name="group[msggroup]" $msggroup_Y />��
			<input type="radio" value="0" name="group[msggroup]" $msggroup_N />��'
		),
		'ifmemo'	=> array(
			'title'	=> '��ʹ�ñ��Ĺ���',
			'html'	=> '<input type="radio" value="1" name="group[ifmemo]" $ifmemo_Y />��
			<input type="radio" value="0" name="group[ifmemo]" $ifmemo_N />��'
		),
		'pergroup' =>	array(
			'title'	=> '��Щ�û���Ȩ�޿��Բ鿴',
			'html'	=> '<input type="checkbox" name="group[pergroup][]" value="member" $pergroup_sel[member] />��Ա�� <input type="checkbox" name="group[pergroup][]" value="system" $pergroup_sel[system] />ϵͳ�� <input type="checkbox" name="group[pergroup][]" value="special" $pergroup_sel[special] />������'
		),
		'maxfavor'	=> array(
			'title'	=> '�ղؼ�����',
			'html'	=> '<input size="35" class="input" value="$maxfavor" name="group[maxfavor]" />'
		),
		'maxgraft'	=> array(
			'title'	=> '�ݸ�������',
			'html'	=> '<input size="35" class="input" value="$maxgraft" name="group[maxgraft]" />'
		),
		'pwdlimitime'	=> array(
			'title'	=> 'ǿ���û���������',
			'desc'	=> '(������Ϊ������)',
			'html'	=> '<input size="5" class="input" value="$pwdlimitime" name="group[pwdlimitime]" /> ��'
		),
		'maxcstyles'	=> array(
			'title'	=> '�Զ���������',
			'desc'	=> '(����0�����գ�������ʹ���Զ�����)',
			'html'	=> '<input size="5" class="input" value="$maxcstyles" name="group[maxcstyles]" />'
		)
	),
	'read'	=> array(
		'allowpost'	=> array(
			'title'	=> '�ɷ�������',
			'html'	=> '<input type="radio" value="1" name="group[allowpost]" $allowpost_Y />��
			<input type="radio" value="0" name="group[allowpost]" $allowpost_N />��'
		),
		'allowrp'	=> array(
			'title'	=> '�ɻظ�����',
			'html'	=> '<input type="radio" value="1" name="group[allowrp]" $allowrp_Y />��
			<input type="radio" value="0" name="group[allowrp]" $allowrp_N />��'
		),
		'allownewvote'	=> array(
			'title'	=> '�ɷ���ͶƱ',
			'html'	=> '<input type="radio" value="1" name="group[allownewvote]" $allownewvote_Y />��
			<input type="radio" value="0" name="group[allownewvote]" $allownewvote_N />��'
		),
		'modifyvote'	=> array(
			'title'	=> 'ͶƱ���������޸�ͶƱѡ��',
			'html'	=> '<input type="radio" value="1" name="group[modifyvote]" $modifyvote_Y />��
			<input type="radio" value="0" name="group[modifyvote]" $modifyvote_N />��'
		),
		'allowvote'	=> array(
			'title' => '�ɲ���ͶƱ',
			'html'	=> '<input type="radio" value="1" name="group[allowvote]" $allowvote_Y />��
			<input type="radio" value="0" name="group[allowvote]" $allowvote_N />��'
		),
		'viewvote'	=> array(
			'title'	=> '�ܲ鿴ͶƱ�û�',
			'html'	=> '<input type="radio" value="1" name="group[viewvote]" $viewvote_Y />��
			<input type="radio" value="0" name="group[viewvote]" $viewvote_N />��'
		),
		'allowactive'	=> array(
			'title'	=> '�ɷ����',
			'html'	=> '<input type="radio" value="1" name="group[allowactive]" $allowactive_Y />��
			<input type="radio" value="0" name="group[allowactive]" $allowactive_N />��'
		),
		'allowreward'	=> array(
			'title'	=> '�ɷ�������',
			'html'	=> '<input type="radio" value="1" name="group[allowreward]" $allowreward_Y />��
			<input type="radio" value="0" name="group[allowreward]" $allowreward_N />��'
		),
		'allowgoods'	=> array(
			'title'	=> '�ɷ���Ʒ��',
			'html'	=> '<input type="radio" value="1" name="group[allowgoods]" $allowgoods_Y />��
			<input type="radio" value="0" name="group[allowgoods]" $allowgoods_N />��'
		),
		'allowdebate'	=> array(
			'title'	=> '�ɷ�������',
			'html'	=> '<input type="radio" value="1" name="group[allowdebate]" $allowdebate_Y />��
			<input type="radio" value="0" name="group[allowdebate]" $allowdebate_N />��'
		),
		'htmlcode'	=> array(
			'title'	=> '�ɷ�html��',
			'desc'	=> '�⽫ʹ�û�ӵ��ֱ�ӱ༭ html Դ�����Ȩ��!',
			'html'	=> '<input type="radio" value="1" name="group[htmlcode]" $htmlcode_Y />��
			<input type="radio" value="0" name="group[htmlcode]" $htmlcode_N />��'
		),
		'media'	=> array(
			'title'	=> '������ý���Ƿ������Զ�����',
			'html'	=> '<input type="checkbox" name="group[media][]" value="flash" $media_sel[flash] />flash
			<input type="checkbox" name="group[media][]" value="wmv" $media_sel[wmv] />wmv
			<input type="checkbox" name="group[media][]" value="rm" $media_sel[rm] />rm
			<input type="checkbox" name="group[media][]" value="mp3" $media_sel[mp3] />mp3'
		),
		'allowhidden'	=> array(
			'title'	=> '�ɷ�������',
			'html'	=> '<input type="radio" value="1" name="group[allowhidden]" $allowhidden_Y />��
			<input type="radio" value="0" name="group[allowhidden]" $allowhidden_N />��'
		),
		'allowsell'	=> array(
			'title'	=> '�ɷ�������',
			'html'	=> '<input type="radio" value="1" name="group[allowsell]" $allowsell_Y />��
			<input type="radio" value="0" name="group[allowsell]" $allowsell_N />��'
		),
		'allowencode'	=> array(
			'title'	=> '�ɷ�������',
			'html'	=> '<input type="radio" value="1" name="group[allowencode]" $allowencode_Y />��
			<input type="radio" value="0" name="group[allowencode]" $allowencode_N />��'
		),
		'anonymous'	=> array(
			'title'	=> '�ɷ�������',
			'html'	=> '<input type="radio" value="1" name="group[anonymous]" $anonymous_Y />��
			<input type="radio" value="0" name="group[anonymous]" $anonymous_N />��'
		),
		'dig'	=> array(
			'title'	=> '�Ƿ������Ƽ�����',
			'html'	=> '<input type="radio" value="1" name="group[dig]" $dig_Y />��
			<input type="radio" value="0" name="group[dig]" $dig_N />��'
		),
		'leaveword'	=>	array(
			'title'	=> '¥��������Ȩ��',
			'html'	=> '<input type="radio" value="1" name="group[leaveword]" $leaveword_Y />��
			<input type="radio" value="0" name="group[leaveword]" $leaveword_N />��'
		),
		'allowdelatc'	=> array(
			'title'	=> '��ɾ���Լ�������',
			'html'	=> '<input type="radio" value="1" name="group[allowdelatc]" $allowdelatc_Y />��
			<input type="radio" value="0" name="group[allowdelatc]" $allowdelatc_N />��'
		),
		'atccheck'	=> array(
			'title'	=> '����������Ƿ���Ҫ����Ա���',
			'desc'	=> '������ֻ���ǿ�������������ʱ��Ч��',
			'html'	=> '<input type="radio" value="1" name="group[atccheck]" $atccheck_Y />��
			<input type="radio" value="0" name="group[atccheck]" $atccheck_N />��'
		),
		'markable'	=> array(
			'title'	=> '��̳����Ȩ��',
			'html'	=> '<input type="radio" value="0" name="group[markable]" $markable_0 />��<br />
			<input type="radio" value="1" name="group[markable]" $markable_1 />��������<br />
			<input type="radio" value="2" name="group[markable]" $markable_2 />�����ظ�����'
		),
		'maxcredit'	=> array(
			'title' => '��������<font color=blue> ˵����</font>ÿ�������������ֵ���',
			'html'	=> '<input type="text" class="input" value="$maxcredit" name="group[maxcredit]" />'
		),
		'marklimit' => array(
			'title'	=> '��������<font color=blue> ˵����</font>ÿ�����ֵ�������Сֵ',
			'html'	=> '��С <input type=text size="3" class="input" value="$minper" name="group[marklimit][0]" /> ��� <input type=text size="3" class="input" value="$maxper" name="group[marklimit][1]" />'
		),
		'markctype'	=> array(
			'title'	=> '�������������',
			'html'	=> '$credit_type'
		),
		'markdt'	=> array(
			'title'	=> '�����Ƿ���Ҫ�۳�������Ӧ�Ļ���',
			'html'	=> '<input type="radio" value="1" name="group[markdt]" $markdt_Y />��
			<input type="radio" value="0" name="group[markdt]" $markdt_N />��'
		),
		'postlimit'	=> array(
			'title'	=> 'ÿ��������������ƪ����',
			'desc'	=> '(����Ϊ0������)',
			'html'	=> '<input size="35" class="input" value="$postlimit" name="group[postlimit]" />'
		),
		'postpertime'	=> array(
			'title'	=> '��ˮԤ��',
			'desc'	=> '(���������ڲ��ܷ���,��Ϊ0��������)',
			'html'	=> '<input size="35" class="input" value="$postpertime" name="group[postpertime]" />'
		),
		'edittime'	=> array(
			'title'	=> '�༭ʱ��Լ��(����)',
			'desc'	=> '�����趨ʱ���ܾ��û��༭�����ջ��߼���0��û��Լ��',
			'html'	=> '<input size="35" class="input" value="$edittime" name="group[edittime]" />'
		)
	),
	'att'	=> array(
		'allowupload'	=> array(
			'title'	=> '�ϴ�����Ȩ��',
			'desc'	=> '<font color=blue> ˵����</font>���ڰ�����ô������ϴ�������������۳�����̳����',
			'html'	=> '<input type="radio" value="0" name="group[allowupload]" $allowupload_0 />�������ϴ�����<br /><input type="radio" value="1" name="group[allowupload]" $allowupload_1 />�����ϴ����������հ�����ý�����۳���̳����<br /><input type="radio" value="2" name="group[allowupload]" $allowupload_2 />�����ϴ���������������۳���̳����'
		),
		'allowdownload'	=> array(
			'title'	=> '���ظ���Ȩ��',
			'desc'	=> '<font color=blue> ˵����</font>���ڰ�����ô��������ظ�����������۳�����̳����',
			'html'	=> '<input type="radio" value="0" name="group[allowdownload]" $allowdownload_0 />���������ظ���<br /><input type="radio" value="1" name="group[allowdownload]" $allowdownload_1 />�������ظ��������հ�����ý�����۳���̳����<br /><input type="radio" value="2" name="group[allowdownload]" $allowdownload_2 />�������ظ�������������۳���̳����'
		),
		'allownum'	=> array(
			'title'	=> 'һ������ϴ���������',
			'html'	=> '<input size="35" class="input" value="$allownum" name="group[allownum]" />'
		),
		'uploadtype'	=> array(
			'title'	=> '�ϴ����������׺�����ߴ�',
			'desc'	=> '<font color=blue> ˵����</font>����ʹ����̳���������е�����',
			'html'	=> '<table width="220">
				<tbody id="mode" style="display:none"><tr class="tr3">
					<td><input class="input" size="10" name="filetype[]" value=""></td>
					<td><input class="input" size="10" name="maxsize[]" value=""> <a style="cursor:pointer;color:#FA891B" onclick="removecols(this);">[ɾ��]</a></td>
				</tr></tbody>
				<tr class="tr3">
					<td>��׺��(Сд)</td>
					<td>���ߴ�(KB) <a style="cursor:pointer;color:blue" onclick="addcols(\'mode\',\'ft\');">[���]</a></td>
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
			'title'	=> '������',
			'desc'	=> "�����ù��ܺ���ͬʱ��<a href=\"$admin_file?adminjob=plantodo\"><font color=\"blue\">�ƻ�����</font></a>����������ͷ���Զ����ա�����.",
			'html'	=> '<input type="radio" value="1" name="group[allowbuy]" $allowbuy_Y />��
			<input type="radio" value="0" name="group[allowbuy]" $allowbuy_N />��'
		),
		'selltype'	=> array(
			'title'	=> '�����鹺��ʹ�ñ���',
			'html'	=> '<select name="group[selltype]">$special_type</select>'
		),
		'sellprice'	=> array(
			'title'	=> '�û�ʹ�û��ֹ���������Ȩ�޵�ÿ�ռ۸�',
			'html'	=> '<input type="text" class="input" name="group[sellprice]" value="$sellprice" />'
		),
		'selllimit'	=> array(
			'title'	=> '�û�������Ҫ�������������������',
			'html'	=> '<input type="text" class="input" name="group[selllimit]" value="$selllimit" />'
		),
		'sellinfo'	=> array(
			'title'	=> '����������',
			'desc'	=> '������д����˵���͸��û���ӵ�е�����Ȩ��',
			'html'	=> '<textarea name="group[sellinfo]" rows="5" cols="30">$sellinfo</textarea>'
		)
	),
	'system'	=> array(
		'allowadmincp'	=> array(
			'title'	=> '�ɽ���̨',
			'html'	=> '<input type="radio" value="1" name="group[allowadmincp]" $allowadmincp_Y />��
			<input type="radio" value="0" name="group[allowadmincp]" $allowadmincp_N />��'
		),
		'superright' => array(
			'title'	=> '��������Ȩ��',
			'desc'	=> "<br /><font color=\"red\">��</font>������������԰���Ȩ�����ö����а����Ч�����磺����Ա��<br /><font color=\"red\">��</font>������������԰���Ȩ�����ö����а����Ч����ʱ���Ҫ���õ������Ĺ���Ȩ�ޣ���Ҫ��<a href=\"$admin_file?adminjob=setforum\">������</a>��������ã����磺������",
			'html'	=> '<input type="radio" value="1" name="group[superright]" $superright_Y />��
			<input type="radio" value="0" name="group[superright]" $superright_N />��'
		)
	),
	'systemforum' => array(
		'viewhide'	=> array(
			'title'	=> '�鿴������(���أ����ܣ����ۣ�����)',
			'html'	=> '<input type="radio" value="1" name="group[viewhide]" $viewhide_Y />��
			<input type="radio" value="0" name="group[viewhide]" $viewhide_N />��'
		),
		'postpers'	=> array(
			'title'	=> '��ˮ<font color=blue> ˵����</font>���ܹ�ˮʱ������',
			'html'	=> '<input type="radio" value="1" name="group[postpers]" $postpers_Y />��
			<input type="radio" value="0" name="group[postpers]" $postpers_N />��'
		),
		'replylock'	=> array(
			'title'	=> '�ظ�������',
			'html'	=> '<input type=radio value=1 $replylock_Y name=group[replylock]>��
			<input type=radio value=0 $replylock_N name=group[replylock]>��'
		),
		'viewip'	=> array(
			'title'	=> '�鿴IP<font color=blue> ˵����</font>�������ʱ��ʾ',
			'html'	=> '<input type="radio" value="1" name="group[viewip]" $viewip_Y />��
			<input type="radio" value="0" name="group[viewip]" $viewip_N />��'
		),
		'topped'	=> array(
			'title'	=> '�ö�Ȩ��',
			'html'	=> '<input type="radio" value="0" name="group[topped]" $topped_0 />��<br />
			<input type="radio" value="1" name="group[topped]" $topped_1 />����ö�<br />
			<input type="radio" value="2" name="group[topped]" $topped_2 />����ö�,�����ö�<br />
			<input type="radio" value="3" name="group[topped]" $topped_3 />����ö�,�����ö�,���ö�'
		),
		'typeadmin'	=> array(
			'title'	=> 'ǰ̨" <font color=blue>���������ᡢ����ѹ</font> "����Ȩ��',
			'html'	=> '<input type="radio" value="1" name="group[typeadmin]" $typeadmin_Y />��
			<input type="radio" value="0" name="group[typeadmin]" $typeadmin_N />��'
		),
		'tpctype'	=> array(
			'title'	=> '����<font color="blue">����������</font>',
			'desc'	=> '<font color=blue> ˵����</font>���������������Ȩ��',
			'html'	=> '<input type="radio" value="1" name="group[tpctype]" $tpctype_Y />��
			<input type="radio" value="0" name="group[tpctype]" $tpctype_N />��'
		),
		'tpccheck'	=> array(
			'title'	=> '����<font color="blue">������֤����</font>',
			'desc'	=> '<font color=blue> ˵����</font>ǰ̨������֤����Ȩ��',
			'html'	=> '<input type="radio" value="1" name="group[tpccheck]" $tpccheck_Y />��
			<input type="radio" value="0" name="group[tpccheck]" $tpccheck_N />��'
		),
		'delatc'	=> array(
			'title'	=> '����ɾ������',
			'desc'	=> '<font color=blue> ˵����</font>ǰ̨���ӹ���Ȩ��',
			'html'	=> '<input type="radio" value="1" name="group[delatc]" $delatc_Y />��
			<input type="radio" value="0" name="group[delatc]" $delatc_N />��'
		),
		'moveatc'	=> array(
			'title'	=> '�����ƶ�����',
			'desc'	=> '<font color=blue> ˵����</font>ǰ̨���ӹ���Ȩ��',
			'html'	=> '<input type="radio" value="1" name="group[moveatc]" $moveatc_Y />��
			<input type="radio" value="0" name="group[moveatc]" $moveatc_N />��'
		),
		'copyatc'	=> array(
			'title'	=> '������������',
			'desc'	=> '<font color=blue> ˵����</font>ǰ̨���ӹ���Ȩ��',
			'html'	=> '<input type="radio" value="1" name="group[copyatc]" $copyatc_Y />��
			<input type="radio" value="0" name="group[copyatc]" $copyatc_N />��'
		),
		'modother'	=> array(
			'title'	=> 'ɾ����һ���ӣ������ظ���',
			'html'	=> '<input type="radio" value="1" name="group[modother]" $modother_Y />��
			<input type="radio" value="0" name="group[modother]" $modother_N />��'
		),
		'deltpcs'	=> array(
			'title'	=> '�༭�û�����',
			'html'	=> '<input type="radio" value="1" name="group[deltpcs]" $deltpcs_Y />��
			<input type="radio" value="0" name="group[deltpcs]" $deltpcs_N />��'
		),
		'viewcheck'	=> array(
			'title'	=> '���Բ鿴��Ҫ��֤������',
			'html'	=> '<input type="radio" value="1" name="group[viewcheck]" $viewcheck_Y />��
			<input type="radio" value="0" name="group[viewcheck]" $viewcheck_N />��'
		),
		'viewclose'	=> array(
			'title'	=> '���Բ鿴�ر�����',
			'html'	=> '<input type="radio" value="1" name="group[viewclose]" $viewclose_Y />��
			<input type="radio" value="0" name="group[viewclose]" $viewclose_N />��'
		),
		'delattach'	=> array(
			'title'	=> '����ɾ������',
			'html'	=> '<input type="radio" value="1" name="group[delattach]" $delattach_Y />��
			<input type="radio" value="0" name="group[delattach]" $delattach_N />��'
		),
		'shield'	=> array(
			'title'	=> '���ε�һ����',
			'html'	=> '<input type="radio" value="1" name="group[shield]" $shield_Y />��
			<input type="radio" value="0" name="group[shield]" $shield_N />��'
		),
		'unite'	=> array(
			'title'	=> '�ϲ�����',
			'html'	=> '<input type="radio" value="1" name="group[unite]" $unite_Y />��
			<input type="radio" value="0" name="group[unite]" $unite_N />��'
		),
		'remind'	=> array(
			'title'	=> '���ӹ������ѹ���',
			'html'	=> '<input type="radio" value="1" name="group[remind]" $remind_Y />��
			<input type="radio" value="0" name="group[remind]" $remind_N />��'
		),
		'inspect'	=> array(
			'title'	=> 'ӵ�С�����������ġ�Ȩ��',
			'html'	=> '<input type="radio" value="1" name="group[inspect]" $inspect_Y />��
			<input type="radio" value="0" name="group[inspect]" $inspect_N />��'
		),
		'allowtime'	=> array(
			'title'	=> '���ܰ�顰����ʱ����Ȩ�ޡ�����',
			'html'	=> '<input type="radio" value="1" name="group[allowtime]" $allowtime_Y />��
			<input type="radio" value="0" name="group[allowtime]" $allowtime_N />��'
		),
		'banuser'	=> array(
			'title'	=> '�����û���Ȩ��',
			'desc'	=> '<font color="blue">˵��:</font><br />
			<font color="red">�޽���Ȩ��:</font>���û�����Ȩ�޶Ի�Ա���н��Բ���<br />
			<font color="red">���а��:</font>(�н���Ȩ��)���ұ����Ի�Ա�����а���ж�ûȨ�޷���<br />
			<font color="red">��һ���</font>(�н���Ȩ��)���ұ����Ի�Ա���������ڰ��ûȨ�޷���,������������п��Է���',
			'html'	=> '<input type="radio" value="0" name="group[banuser]" $banuser_0 />�޽���Ȩ��<br />
			<input type="radio" value="2" name="group[banuser]" $banuser_2 />��һ���<br />
			<input type="radio" value="1" name="group[banuser]" $banuser_1 />���а��'
		),
		'bantype'	=> array(
			'title'	=> '���ý����û�',
			'html'	=> '<input type="radio" value="1" name="group[bantype]" $bantype_Y />��
			<input type="radio" value="0" name="group[bantype]" $bantype_N />��'
		),
		'banmax'	=> array(
			'title'	=> '����ʱ������',
			'desc'	=> '<font color=blue> ˵����</font>�����Ա���������',
			'html'	=> '<input type=text size="3" class="input" value="$banmax" name="group[banmax]" />'
		)
	)
);
?>