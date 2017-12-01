<?php
/**
*
*  Copyright (c) 2003-06  PHPWind.net. All rights reserved.
*  Support : http://www.phpwind.net
*  This software is the proprietary information of PHPWind.com.
*
*/

!defined('R_P') && exit('Forbidden!');

Class mysql
{
	var $sql = NULL;

	function mysql($dbhost,$dbuser,$dbpw,$dbname,$charset)
	{
		global $source_charset,$dest_charset,$charset_change,$page_charset;
		$this->sql = @mysql_connect($dbhost,$dbuser,$dbpw, true) OR $this->halt();
		$serverinfo = mysql_get_server_info($this->sql);
		if ($serverinfo > '4.1')
		{
            if($charset){
    			mysql_query("SET character_set_connection=".$charset.",character_set_results=".$charset.",character_set_client=binary",$this->sql);
            }
            elseif ($charset_change)
			{
				mysql_query("SET character_set_connection=".$source_charset.",character_set_results=".$source_charset.",character_set_client=binary",$this->sql);
			}
			else
			{
				mysql_query("SET character_set_connection=".$dest_charset.",character_set_results=".$dest_charset.",character_set_client=binary",$this->sql);
			}
		}
		if ($serverinfo > '5.0')
		{
			mysql_query("SET sql_mode=''",$this->sql);
		}
		!@mysql_select_db($dbname,$this->sql) && $this->halt();
	}
	function server_info()
	{
		return mysql_get_server_info($this->sql);
	}
	function pw_update($SQL_1,$SQL_2,$SQL_3)
	{
		$rt = $this->get_one($SQL_1,MYSQL_NUM);
		isset($rt[0]) ? $this->update($SQL_2) : $this->update($SQL_3);
	}
	function get_value($SQL,$result_type = MYSQL_NUM,$field=0)
	{
		$query = $this->query($SQL,1);
		$rt = & mysql_fetch_array($query,$result_type);
		return isset($rt[$field]) ? $rt[$field] : false;
	}
	function get_one($SQL, $result_type = MYSQL_ASSOC)
	{
		$rt = & mysql_fetch_array($this->query($SQL,1), $result_type);
		return $rt;
	}
	function update($SQL)
	{
		return $this->query($SQL,1);
	}
	function query($SQL,$method = 0)
	{
		$query = ($method == 1 && function_exists('mysql_unbuffered_query')) ? mysql_unbuffered_query($SQL,$this->sql) : mysql_query($SQL,$this->sql);
		!$query && $this->halt($SQL);
		return $query;
	}
	function fetch_array($query, $result_type = MYSQL_ASSOC)
	{
		return mysql_fetch_array($query,$result_type);
	}
	function affected_rows()
	{
		return mysql_affected_rows($this->sql);
	}
	function num_rows($query)
	{
		return mysql_num_rows($query);
	}
	function free_result($query)
	{
		return mysql_free_result($query);
	}
	function insert_id()
	{
		return mysql_insert_id($this->sql);
	}
	function close($linkid)
	{
		return @mysql_close($linkid);
	}
	function collation()
	{
		$return = '';
		if ($this->server_info() > '4.1')
		{
			switch ($GLOBALS['dest_charset'])
			{
				case 'latin1':
					$collate = 'latin1_swedish_ci';
					break;
				case 'gbk':
					$collate = 'gbk_chinese_ci';
					break;
				case 'utf8':
					$collate = 'utf8_general_ci';
					break;
				case 'big5':
					$collate = 'big5_chinese_ci';
					break;
			}
			$return = sprintf('CHARACTER SET %s COLLATE %s', $GLOBALS['dest_charset'], $collate);
		}
		return $return;
	}
	function halt($SQL='')
	{
		$errno = is_resource($this->sql) ?  mysql_errno($this->sql) : mysql_errno();
		$error = is_resource($this->sql) ?  mysql_error($this->sql) : mysql_error();
		require_once(R_P.'libs/mysql_error.php');
		Showmsg(sprintf($error_msg, $error, ($SQL ? $SQL : 'NULL')), true);
	}
/*²åÈë·½Ê½ modify by cn_hy*/
	function inserttable($table='', $bind=array())
  {
      $cols = array();
      $vals = array();
      foreach ($bind as $col => $val)
      {
          $cols[] = $col;
          if(empty($val))
          {
          	$val=addslashes('""');
          }
          $vals[] = $val;
      }

      $sql = "INSERT INTO "
           . addslashes($table)
           . ' (' . implode(', ', $cols) . ') '
           . 'VALUES (' . implode(', ', $vals) . ')';
      $stmt = $this->query($sql);
      $result = $this->insert_id();
      return $result;
  }
}
?>