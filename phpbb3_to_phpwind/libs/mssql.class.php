<?php
/**
*
*  Copyright (c) 2003-06  PHPWind.net. All rights reserved.
*  Support : http://www.phpwind.net
*  This software is the proprietary information of PHPWind.com.
*
*/

!defined('R_P') && exit('Forbidden!');

class mssql
{
	var $sql = NULL;
    function mssql($dbhost,$dbuser,$dbpw,$dbname)
    {
    	!function_exists('mssql_connect') && $this->halt('ms1001');
    	$this->sql = @mssql_connect($dbhost, $dbuser, $dbpw) OR $this->halt('ms1002');
    	!@mssql_select_db($dbname, $this->sql) && $this->halt('ms1003');
    }
	function query($SQL)
	{
		$query = mssql_query($SQL,$this->sql);
		
		!$query && $this->halt('',$SQL);
		
		return $query;
	}
	function fetch_array($query, $result_type = MSSQL_ASSOC)
	{
		return mssql_fetch_array($query, $result_type);
	}
	function get_one($SQL, $result_type = MSSQL_ASSOC)
	{
		$rt = & mssql_fetch_array($this->query($SQL), $result_type);
		return $rt;
	}
    function get_value($SQL,$result_type = MYSQL_NUM,$field=0) {
		$query = $this->query($SQL);
		$rt =& $this->fetch_array($query,$result_type);
		return isset($rt[$field]) ? $rt[$field] : false;
	}
	function free_result($query)
	{
		return mssql_free_result($query);
	}
	function halt($errno = '', $SQL='')
	{
		require_once(R_P.'libs/mssql_error.php');
		$error = mssql_get_last_message();
		Showmsg(sprintf($error_msg, ($error ? $error : 'NULL'), ($SQL ? $SQL : 'NULL')), true);
	}
}
?>