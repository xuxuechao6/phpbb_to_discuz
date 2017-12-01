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
        $this->sql = @odbc_connect("Driver={SQL Server};Server=".$dbhost.";Database=".$dbname, $dbuser, $dbpw, SQL_CUR_USE_ODBC);
    	!$this->sql && $this->halt();
    }
	function query($SQL)
    {
        $r = odbc_exec($this->sql, $SQL);
        !$r && $this->halt($SQL);
        return $r;
    }
    function get_one($SQL)
    {
    	return odbc_fetch_array($this->query($SQL), 1);
    }
    function fetch_array($query)
    {
        return odbc_fetch_array($query);
    }
    function fetch_row($query)
    {
        return odbc_fetch_row($query);
    }
    function num_rows($query)
    {
        return odbc_num_rows($query);
    }
    function close()
    {    
        odbc_close($this->sql);
    }
	function halt($SQL='')
	{
		$errno = odbc_error();
		$error = odbc_errormsg();
		require_once(R_P.'libs/mssql_error.php');
		Showmsg(sprintf($error_msg, $error, ($SQL ? $SQL : 'NULL')), true);
	}
}
?>