<?php

class Chinese
{
	var $unicode_table = array();
	var $ctf;
	var $SourceText = "";
	var $config = array(
		'codetable_dir'			=> 'libs/encode/',   
		'SourceLang'			=> '',
		'TargetLang'			=> '',
		'GBtoBIG5_table'		=> 'gb-big5.table',  
		'BIG5toGB_table'		=> 'big5-gb.table',  
		'GBtoUnicode_table'		=> 'gb-unicode.table',  
		'BIG5toUnicode_table'	=> 'big5-unicode.table'  
	);
	function Chinese($SourceLang,$TargetLang){
		$this->config['SourceLang'] = $this->getcode($SourceLang);
		$this->config['TargetLang'] = $this->getcode($TargetLang);
		$this->OpenTable();
	}
	function getcode($code){
		if(strtoupper(substr($code,0,2))=='GB'){
			return 'GB2312';
		} elseif(strtoupper(substr($code,0,3))=='BIG'){
			return 'BIG5';
		} elseif(strtoupper(substr($code,0,3))=='UTF'){
			return 'UTF8';
		} elseif(strtoupper(substr($code,0,3))=='UNI'){
			return 'UNICODE';
		} else{
			return 'GB2312';
		}
	}
	function _hex2bin( $hexdata ){
		for($i=0;$i<strlen($hexdata);$i+=2){
			$bindata .= chr(hexdec(substr($hexdata,$i,2)));
		}
		return $bindata;
	}
	function OpenTable(){
		if($this->config['SourceLang']=="GB2312") {
			if($this->config['TargetLang'] == "UTF8") {
				$tmp = openfile(R_P.$this->config['codetable_dir'].$this->config['GBtoUnicode_table']);
				$this->unicode_table = array();
				while(list($key,$value)=each($tmp)){
					$this->unicode_table[hexdec(substr($value,0,6))]=substr($value,7,6);
				}
			}
			if($this->config['TargetLang'] == "UNICODE") {
				$tmp = openfile(R_P.$this->config['codetable_dir'].$this->config['GBtoUnicode_table']);
				$this->unicode_table = array();
				while(list($key,$value)=each($tmp)){
					$this->unicode_table[hexdec(substr($value,0,6))]=substr($value,9,4);
				}
			}
		}
		if($this->config['SourceLang']=="BIG5") {
			if($this->config['TargetLang'] == "UTF8") {
				$tmp = openfile(R_P.$this->config['codetable_dir'].$this->config['BIG5toUnicode_table']);
				$this->unicode_table = array();
				while(list($key,$value)=each($tmp)){
					$this->unicode_table[hexdec(substr($value,0,6))]=substr($value,7,6);
				}
			}
			if($this->config['TargetLang'] == "UNICODE") {
				$tmp = openfile(R_P.$this->config['codetable_dir'].$this->config['BIG5toUnicode_table']);
				$this->unicode_table = array();
				while(list($key,$value)=each($tmp)){
					$this->unicode_table[hexdec(substr($value,0,6))]=substr($value,9,4);
				}
			}
		}
		if($this->config['SourceLang']=="UTF8") {
			if($this->config['TargetLang'] == "GB2312") {
				$tmp = openfile(R_P.$this->config['codetable_dir'].$this->config['GBtoUnicode_table']);
				$this->unicode_table = array();
				while(list($key,$value)=each($tmp)){
					$this->unicode_table[hexdec(substr($value,7,6))]=substr($value,0,6);
				}
			}
			if($this->config['TargetLang'] == "BIG5") {
				$tmp = openfile(R_P.$this->config['codetable_dir'].$this->config['BIG5toUnicode_table']);
				$this->unicode_table = array();
				while(list($key,$value)=each($tmp)){
					$this->unicode_table[hexdec(substr($value,7,6))]=substr($value,0,6);
				}
			}
		}
	}
	function CHSUtoUTF8($c){
		$str="";
		if($c < 0x80) {
			$str.=$c;
		}elseif($c < 0x800) {
			$str.=(0xC0 | $c>>6);
			$str.=(0x80 | $c & 0x3F);
		}elseif($c < 0x10000) {
			$str.=(0xE0 | $c>>12);
			$str.=(0x80 | $c>>6 & 0x3F);
			$str.=(0x80 | $c & 0x3F);
		}elseif($c < 0x200000) {
			$str.=(0xF0 | $c>>18);
			$str.=(0x80 | $c>>12 & 0x3F);
			$str.=(0x80 | $c>>6 & 0x3F);
			$str.=(0x80 | $c & 0x3F);
		}
		return $str;
	}
	function CHSUTF8toU($c) {
		switch(strlen($c)) {
			case 1:
				return ord($c);
			case 2:
				$n = (ord($c[0]) & 0x3f) << 6;
				$n += ord($c[1]) & 0x3f;
				return $n;
			case 3:
				$n = (ord($c[0]) & 0x1f) << 12;
				$n += (ord($c[1]) & 0x3f) << 6;
				$n += ord($c[2]) & 0x3f;
				return $n;
			case 4:
				$n = (ord($c[0]) & 0x0f) << 18;
				$n += (ord($c[1]) & 0x3f) << 12;
				$n += (ord($c[2]) & 0x3f) << 6;
				$n += ord($c[3]) & 0x3f;
				return $n;
		}
	}
	function CHStoUTF8(){
		if($this->config["SourceLang"]=="BIG5" || $this->config["SourceLang"]=="GB2312") {
			$ret="";
			while($this->SourceText){
				if(ord(substr($this->SourceText,0,1))>127){
					if($this->config["SourceLang"]=="BIG5") {
						$utf8=$this->CHSUtoUTF8(hexdec($this->unicode_table[hexdec(bin2hex(substr($this->SourceText,0,2)))]));
					}
					if($this->config["SourceLang"]=="GB2312") {
						$utf8=$this->CHSUtoUTF8(hexdec($this->unicode_table[hexdec(bin2hex(substr($this->SourceText,0,2)))-0x8080]));
					}
					for($i=0;$i<strlen($utf8);$i+=3){
						$ret.=chr(substr($utf8,$i,3));
					}
					$this->SourceText=substr($this->SourceText,2,strlen($this->SourceText));
				}else{
					$ret.=substr($this->SourceText,0,1);
					$this->SourceText=substr($this->SourceText,1,strlen($this->SourceText));
				}
			}
			$this->unicode_table = array();
			$this->SourceText = "";
			return $ret;
		}
		if($this->config["SourceLang"]=="UTF8") {
			$out = "";
			$len = strlen($this->SourceText);
			$i = 0;
			while($i < $len) {
				$c = ord( substr( $this->SourceText, $i++, 1 ) );
				switch($c >> 4)
				{ 
					case 0: case 1: case 2: case 3: case 4: case 5: case 6: case 7:
						// 0xxxxxxx
						$out .= substr( $this->SourceText, $i-1, 1 );
					break;
					case 12: case 13:
						// 110x xxxx   10xx xxxx
						$char2 = ord( substr( $this->SourceText, $i++, 1 ) );
						$char3 = $this->unicode_table[(($c & 0x1F) << 6) | ($char2 & 0x3F)];

						if($this->config["TargetLang"]=="GB2312")
							$out .= $this->_hex2bin( dechex(  $char3 + 0x8080 ) );

						if($this->config["TargetLang"]=="BIG5")
							$out .= $this->_hex2bin( $char3 );
					break;
					case 14:
						// 1110 xxxx  10xx xxxx  10xx xxxx
						$char2 = ord( substr( $this->SourceText, $i++, 1 ) );
						$char3 = ord( substr( $this->SourceText, $i++, 1 ) );
						$char4 = $this->unicode_table[(($c & 0x0F) << 12) | (($char2 & 0x3F) << 6) | (($char3 & 0x3F) << 0)];

						if($this->config["TargetLang"]=="GB2312")
							$out .= $this->_hex2bin( dechex ( $char4 + 0x8080 ) );

						if($this->config["TargetLang"]=="BIG5")
							$out .= $this->_hex2bin( $char4 );
					break;
				}
			}
			return $out;
		}
	}
	function CHStoUNICODE()
	{
		$utf="";
		while($this->SourceText)
		{
			if(function_exists('iconv')){
				if(ord(substr($this->SourceText, 0, 1)) > 127) {
					$utf .= "&#x".dechex($this->CHSUTF8toU(iconv(($this->config['SourceLang']=='GB2312' ? 'GBK' : $this->config['SourceLang']),"UTF-8", substr($this->SourceText, 0, 2)))).";";
					$this->SourceText = substr($this->SourceText, 2, strlen($this->SourceText));
				} else {
					$utf .= substr($this->SourceText, 0, 1);
					$this->SourceText = substr($this->SourceText, 1, strlen($this->SourceText));
				}
			}elseif(ord(substr($this->SourceText,0,1))>127)
			{

				if($this->config["SourceLang"]=="GB2312")
					$utf.="&#x".$this->unicode_table[hexdec(bin2hex(substr($this->SourceText,0,2)))-0x8080].";";

				if($this->config["SourceLang"]=="BIG5")
					$utf.="&#x".$this->unicode_table[hexdec(bin2hex(substr($this->SourceText,0,2)))].";";

				$this->SourceText=substr($this->SourceText,2,strlen($this->SourceText));
			}
			else
			{
				$utf.=substr($this->SourceText,0,1);
				$this->SourceText=substr($this->SourceText,1,strlen($this->SourceText));
			}
		}
		return str_replace(array('&#x;', '&#x0;'),array('??', ''),$utf);
	}
	function Convert($SourceString){
		$this->SourceText = $SourceString;
		if(($this->config['SourceLang']=="GB2312" || $this->config['SourceLang']=="BIG5" || $this->config['SourceLang']=="UTF8") && ($this->config['TargetLang']=="UTF8" || $this->config['TargetLang']=="GB2312" || $this->config['TargetLang']=="BIG5") ) {
			return $this->CHStoUTF8();
		}
		if(($this->config['SourceLang']=="GB2312" || $this->config['SourceLang']=="BIG5") && $this->config['TargetLang']=="UNICODE") {
			return $this->CHStoUNICODE();
		}
	}
}
function openfile($filename){
	$filedb = explode('<:wind:>',str_replace("\n","\n<:wind:>",readover($filename)));
	$count = count($filedb)-1;
	if ($count > -1 && (!$filedb[$count] || $filedb[$count]=="\r")) {
		unset($filedb[$count]);
	}
	empty($filedb) && $filedb[0] = '';
	return $filedb;
}
?>