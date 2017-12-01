<?php
/**
*
*  Copyright (c) 2003-06  PHPWind.net. All rights reserved.
*  Support : http://www.phpwind.net
*  This software is the proprietary information of PHPWind.com.
*
*/

!defined('R_P') && exit('Forbidden!');

class access
{
	var $sql = NULL;
    function access($dbhost,$dbuser='',$dbpw='')
    {
		$this->sql = new com('adodb.connection');
		$this->sql->open("DRIVER={Microsoft Access Driver (*.mdb)};dbq=$dbhost;uid=$dbuser;pwd=$dbpw");
		if(!$this->sql->state)
		{
			$this->sql->Open("Provider=Microsoft.Jet.OLEDB.4.0; Data Source=$dbhost");
			!$this->sql->state && $this->halt('系统无法访问数据库文件，请检查配置信息是否正确。');
		}
		return $this->sql;
    }
	function query($SQL)
    {
        $rs = $this->sql->Execute($SQL);
        !$rs && $this->halt('SQL 执行错误',$SQL);
        return $rs;
    }
    function close()
    {    
        $this->sql->close();
        $this->sql->Release();
    }
	function halt($error = '', $SQL='')
	{
		require_once(R_P.'libs/access_error.php');
		Showmsg(sprintf($error_msg, $error, ($SQL ? $SQL : 'NULL')), true);
	}
}
?>