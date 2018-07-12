<?php
if ( ! defined('BASEPATH')) { exit('No direct script access allowed'); }
class Lib_imzers {
	public $server_root;
	public $log_root;
	public $this_server_protocol;
	public $this_server_name;
	protected $session_id, $db_resource, $db_result;
	protected $db_resources = array();
	public $config = array();
	public $errors = array();
	public $queries = array();
	public $session_data = array();
	private $db_type;
	protected $scripting_time_starting, $scripting_time_stopping;
	public $session_global_id = NULL;
	public $base_path = '';
	protected $CI;
	function __construct($config = array()) {
		$this->config = (isset($config['get_database']) ? $config['get_database'] : array());
		$this->db_type = (isset($this->config['db_type']) ? $this->config['db_type'] : 'mysql');
		$this->db_connect($this->config, $this->db_type);
		$this->scripting_time_starting = microtime(true);
		$this->base_path = (isset($config['base_path']) ? $config['base_path'] : '');
		$this->CI = &get_instance();
	}
	/***********************************************************************
	* Error page
	************************************************************************/
	function add_error($msg) {
		array_push($this->errors, $msg);
	}
	function error($msg) {
		array_push($this->errors, $msg);
		$this->show_msg('Error', $msg);
	}
	/***********************************************************************
	* Databases
	************************************************************************/
	function db_connect($database, $type = 'mysql') {
		$type = (is_string($type) ? strtolower($type) : 'mysql');
		if (strlen($type) === 0) {
			$type = 'mysql';
		}
		switch ($type) {
			case 'sqlsrv_odbc':
				try {
					$this->db_resource = odbc_connect($database['db_host'], $database['db_user'], $database['db_pass']);
				} catch (Exception $e) {
					$this->add_error('Could not connect to database server.');
					$this->add_error($e->getMessage());
					return false;
				}
			break;
			case 'sqlsrv_pdo':
				$dsn = "sqlsrv:database={$database['db_name']};server={$database['db_host']}";
				try {
					$this->db_resource = new PDO($dsn, $database['db_user'], $database['db_pass']);
				} catch(PDOException $e) {
					$this->add_error('Could not connect to database server: ' . $e->getMessage());
					return false;
				}
			break;
			case 'mysql':
				mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
				try {
					$this->db_resource = new mysqli($database['db_host'], $database['db_user'], $database['db_pass'], $database['db_name']);
				} catch (Exception $e) {
					$this->add_error('Could not connect to database server: ' . $e->getMessage());
					return false;
				}
				if (!$this->db_query("SET NAMES utf8")) {
					$this->add_error('Cannot set collation name as UTF-8.');
					return false;
				}
			break;
		}
		return $this;
	}
	function sql_addslashes($sql, $type = 'mysql') {
		$sql_string = ((is_array($sql) || is_object($sql)) ? "" : $sql);
		switch ($type) {
			case 'sqlsrv_odbc':
				$sql_string = (is_string($sql_string) || is_numeric($sql_string)) ? sprintf("%s", $sql_string) : '';
				$return = str_replace("'", "", $sql_string);
				/*$return = $this->checkApostrophes($return);*/
			break;
			case 'sqlsrv_pdo':
				$sql_string = (is_string($sql_string) || is_numeric($sql_string)) ? sprintf("%s", $sql_string) : '';
				$return = $sql_string;
			break;
			case 'mysql':
				if (!isset($result)) {
					$result = $this->db_resource;
				}
				$return = $result->real_escape_string($sql_string);
			break;
		}
		return $return;
	}
	function db_free($type = 'mysql', $result = null) {
		if (!isset($result)) {
			$result = $this->db_result;
		}
		switch ($type) {
			case 'sqlsrv_odbc':
				odbc_free_result($result);
			break;
			case 'sqlsrv_pdo':
				$result->closeCursor();
			break;
			case 'mysql':
				$result->free();
			break;
		}
	}
	function db_close($type = 'mysql', $resource = null) {
		if (!isset($resource)) {
			$resource = $this->db_resource;
		}
		switch ($type) {
			case 'sqlsrv_odbc':
				odbc_close($resource);
			break;
			case 'sqlsrv_pdo':
				$resource = NULL;
			break;
			case 'mysql':
				$resource->close();
			break;
		}
	}
	function db_insert_id($type = 'mysql', $resource = null) {
		if (!isset($resource)) {
			$resource = $this->db_resource;
		}
		switch ($type) {
			case 'sqlsrv_odbc':
				return $this->db_query("SELECT @"."@IDENTITY AS Ident", $type);
			break;
			case 'sqlsrv_pdo':
				return $resource->lastInsertId();
			break;
			case 'mysql':
				return $resource->insert_id;
			break;
		}
	}
	function db_prepare($sql, $type = 'mysql', $resources = null) {
		if (!isset($resources)) {
			$resources = $this->db_resource;
		}
		$stmt = null;
		switch ($type) {
			case 'sqlsrv_odbc':
				$stmt = odbc_prepare($resources, $sql);
			break;
			case 'sqlsrv_pdo':
				$stmt = $resources->prepare($sql);
			break;
			case 'mysql':
				$stmt = $resources->prepare($sql);
			break;
		}
		return $stmt;
	}
	function db_execute($type, $stmt, $arrayVal = array(), $resources = null) {
		if (!isset($resources)) {
			$resources = $this->db_resource;
		}
		$return = false;
		switch ($type) {
			case 'sqlsrv_odbc':
				$return = odbc_execute($stmt, $arrayVal);
			break;
			case 'sqlsrv_pdo':
				$return = $resources->execute($arrayVal);
			break;
			case 'mysql':
				$return = $resources->execute();
			break;
		}
		return $return;
	}
	function db_query($sql, $type = 'mysql', $resources = null) {
		if (!isset($resources)) {
			$resources = $this->db_resource;
		}
		array_push($this->queries, $sql);
		switch ($type) {
			case 'sqlsrv_odbc':
				if ($this->db_result = odbc_exec($resources, $sql)) {
					return $this->db_result;
				}
			break;
			case 'sqlsrv_pdo':
				if ($this->db_result = $resources->query($sql)) {
					return $this->db_result;
				}
			break;
			case 'mysql':
				if ($this->db_result = $resources->query($sql)) {
					return $this->db_result;
				}
			break;
		}
		return false;
	}
	function db_fetch($type = 'mysql', $result = null) {
		if (!isset($result)) {
			$result = $this->db_result;
		}
		switch ($type) {
			case 'sqlsrv_odbc':
				return odbc_fetch_array($result);
			break;
			case 'sqlsrv_pdo':
				return $result->fetch(PDO::FETCH_ASSOC);
			break;
			case 'mysql':
				return $result->fetch_assoc();
			break;
		}
	}
	function db_num_rows($type = 'mysql', $result = null) {
		if (!isset($result)) {
			$result = $this->db_result;
		}
		switch ($type) {
			case 'sqlsrv_odbc':
				return odbc_num_rows($result);
			break;
			case 'sqlsrv_pdo':
				return $result->rowCount();
			break;
			case 'mysql':
				return $result->num_rows;
			break;
		}
	}
	function db_error($type = 'mysql', $resources = null) {
		if (!isset($resources)) {
			$resources = $this->db_resource;
		}
		switch ($type) {
			case 'sqlsrv_odbc':
				return odbc_errormsg($resources);
			break;
			case 'sqlsrv_pdo':
				return $resources->errorInfo();
			break;
			case 'mysql':
				return $resources->error;
			break;
		}
		return true;
	}
    /***********************************************************************
	* Session
	************************************************************************/
	function is_session_started() {
		if (php_sapi_name() !== 'cli') {
			if (version_compare(phpversion(), '5.4.0', '>=')) {
				return session_status() === PHP_SESSION_ACTIVE ? TRUE : FALSE;
			} else {
				return session_id() === '' ? FALSE : TRUE;
			}
		}
		return FALSE;
	}
	function session_start() {
		if (!$this->is_session_started()) {
			session_start();
		}
		$this->session_data = &$_SESSION;
	}

	/***********************************************************************
	* Header, footer and show page
	************************************************************************/
	function show_head($session_id = null) {
		if (defined('PAGE_HEADER')) {
			return FALSE;
		}
		define('PAGE_HEADER', TRUE);
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
		header('Cache-Control: no-cache, must-revalidate');
		header('Pragma: no-cache');
		header('Cache-Control: no-store, no-cache, must-revalidate');
		header('Cache-Control: post-check=0, pre-check=0', false);
	}
	function show_foot($result = null) {
		if (!isset($result)) {
			$result = $this->db_result;
		}
		if ($result) {
			try {
				$this->db_free($result);
			} catch (Exception $ex) {
				throw $ex;
				exit("Cannot set free current database resources: {$ex->getMessage()}");
			}
		}
		if ($this->db_resource) {
			try {
				$this->db_close();
			} catch (Exception $e) {
				throw $e;
			}
		}
		exit;
	}
	function show_msg($title, $msg) {
		echo ($msg);
		$this->show_foot();
	}
	/***********************************************************************
	* Static queries
	************************************************************************/
	
	/***********************************************************************
	* SQL and Other Security
	************************************************************************/
	function pregsplit_linebreak($text) {
		return preg_split('/$\R?^/m', $text);
	}
	function cleanspecialchar($txt) {
		$txt = strip_tags($txt);
		$txt = preg_replace('/&.+?;/', '', $txt);
		$txt = preg_replace('/\s+/', ' ', $txt);
		$txt = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', ' ', $txt);
		$txt = preg_replace('|-+|', ' ', $txt);
		$txt = preg_replace('/&#?[a-z0-9]+;/i', '', $txt);
		$txt = preg_replace('/[^%A-Za-z0-9 \_\-]/', ' ', $txt);
		$txt = trim($txt, ' ');
		return $txt;
	}
	function permalink($url) {
		$url = strtolower($url);
		$url = preg_replace('/&.+?;/', '', $url);
		$url = preg_replace('/\s+/', '_', $url);
		$url = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '_', $url);
		$url = preg_replace('|%|', '_', $url);
		$url = preg_replace('/&#?[a-z0-9]+;/i', '', $url);
		$url = preg_replace('/[^%A-Za-z0-9 \_\-]/', '_', $url);
		$url = preg_replace('|_+|', '-', $url);
		$url = preg_replace('|-+|', '-', $url);
		$url = trim($url, '-');
		$url = (strlen($url) > 128) ? substr($url, 0, 128) : $url;
		return $url;
	}
	function safe_text_post($text, $length, $allow_nl = false) {
		$text = htmlspecialchars($text, ENT_QUOTES);
		$text = trim(chop($text));
		$text = $allow_nl ? $text : preg_replace("/[\r|\n]/", "", $text);
		$text = substr($text, 0, $length);
		return $text;
	}
	function stripslashes_deep($var) {
		return is_array($var) ? array_map(array($this, 'stripslashes_deep'), $var) : stripslashes($var);
	}
	/***********************************************************************
	* Additional
	************************************************************************/
	function reading_filepath($filename, $handle = null) {
		if (!$handle = fopen($filename, 'r')) {
			$this->error('Cannot read ' . $filename);
		}
		try {
			$contents = fread($handle, filesize($filename));
			fclose($handle);
		} catch (Exception $ex) {
			throw $ex;
			return FALSE;
		}
		return preg_split('/$\R?^/m', $contents);
	}
	function reading_filehtml_path($filename, $handle = null) {
		if (!file_exists($filename)) {
			exit("File for reading not really exists: {$filename}");
		}
		if (!$handle = fopen($filename, 'r')) {
			$this->error('Cannot read ' . $filename);
		}
		try {
			$contents = fread($handle, filesize($filename));
			fclose($handle);
		} catch (Exception $ex) {
			throw $ex;
			return FALSE;
		}
		//return preg_split('/$\R?^/m', $contents);
		return $contents;
	}
	function htmlspecialcharset($text, $allow_nl = false) {
		$text = htmlspecialchars($text, ENT_QUOTES);
		$text = trim(chop($text));
		$text = $allow_nl ? $text : preg_replace("/[\r|\n]/", "", $text);
		return $text;
	}
	// Validate URL and Parse URL
	function validate_url($string) {
		$pattern = "/\b(?:(?:https?|ftp):\/\/|www\.|xshot\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i";
		preg_match($pattern, $string, $matches);
		if (count($matches) > 0) {
			return $matches[0];
		}
		return false;
	}
	function parseurl($url) {
		$validurl = $this->validate_url($url);
		if ($validurl) {
			$parseurl = parse_url($validurl);
			if (count($parseurl) == 1) {
				$validurl = 'http://'.$validurl;
			}
			return parse_url($validurl);
		}
		return false;
	}
	/***********************************************************************
	* Additional Private Functions
	************************************************************************/
	// set Header
	private function set_http_header($params = array()) {
		foreach ($params as $ke => $val) {
			if (is_array($val)) {
				$val = implode(",", $val);
			}
			header("{$ke}: {$val}");
		}
	}
	// Datetime Manual
	private function datetime_get_date($month, $year) {
		$month = (int)$month;
		$year = (int)$year;
		switch ($month) {
			case 2:
				if (($year % 4) == 0) {
					$returnMaxDate = 29;
				} else {
					$returnMaxDate = 28;
				}
			break;
			case 4:
			case 6:
			case 9:
			case 11:
				$returnMaxDate = 30;
			break;
			case 1:
			case 3:
			case 5:
			case 7:
			case 8:
			case 10:
			case 12:
			default:
				$returnMaxDate = 31;
			break;
		}
		return $returnMaxDate;
	}
	private function datetime_get_month($month) {
		$month = (int)$month;
		if (($month < 1) && ($month > 12)) {
			return false;
		}
		if (strlen($month) < 2) {
			$month = "0{$month}";
		}
		return $month;
	}
	private function datetime_get_day($day, $month, $year) {
		$day = (int)$day;
		$month = (int)$month;
		$year = (int)$year;
		if (($day < 0) && ($day > $this->datetime_get_date($month, $year))) {
			return false;
		}
		if (strlen($day) < 2) {
			$day = "0{$day}";
		}
		return $day;
	}
	// Escaping Unicode
	function unicode_escape($string, $function = 'decode', $format = 'utf-8') {
		if ($format != 'utf-8') {
			// In case if UTF-16 based C/C++/Java/Json-style:
			$string = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
			return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UTF-16BE');
			}, $string);
		} else {
			$string = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
			return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
			}, $string);
		}
		return $string;
	}
	/***********************************************************************
	* Files exists validation
	************************************************************************/
	function custom_file_mimetype($file, $encoding = true) {
        $mime = false;
        if (!file_exists($file)) {
            return false;
            exit;
        }
        if (function_exists('finfo_file')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $file);
            finfo_close($finfo);
        } else if (substr(PHP_OS, 0, 3) == 'WIN') {
            $mime = mime_content_type($file);
        } else {
            $file = escapeshellarg($file);
            $cmd  = "file -iL $file";
            exec($cmd, $output, $r);
            if ($r == 0) {
                $mime = substr($output[0], strpos($output[0], ': ') + 2);
            }
        }
        if (!$mime) {
            return false;
        }
        if ($encoding) {
            return $mime;
        }
        return $mime;
    }
	function custom_file_exists($file_path = '') {
		$file_exists=false;
		//clear cached results
		//clearstatcache();
		//trim path
		$file_dir=trim(dirname($file_path));
		//normalize path separator
		$file_dir=str_replace('/',DIRECTORY_SEPARATOR,$file_dir).DIRECTORY_SEPARATOR;
		//trim file name
		$file_name=trim(basename($file_path));
		//rebuild path
		$file_path=$file_dir."{$file_name}";
		//If you simply want to check that some file (not directory) exists, 
		//and concerned about performance, try is_file() instead.
		//It seems like is_file() is almost 2x faster when a file exists 
		//and about the same when it doesn't.
		$file_exists=is_file($file_path);
		//$file_exists=file_exists($file_path);
		return $file_exists;
	}
	function custom_file_path($file_path_and_name) {
		$file_dir = trim(dirname($file_path_and_name));
		$file_dir = str_replace('/', DIRECTORY_SEPARATOR, $file_dir).DIRECTORY_SEPARATOR;
		$file_name = trim(basename($file_path_and_name));
		$file_path = ($file_dir . $file_name);
		return $file_path;
	}
	function custom_read_csv($path_file) {
		$csv = array_map('str_getcsv', file($path_file));
		array_walk($csv, function(&$a) use ($csv) {
			$a = array_combine($csv[0], $a);
			});
		array_shift($csv);
		return $csv;
	}
	function custom_read_multicsv($path_file) {
		return array_map('str_getcsv', file($path_file));
	}
	function parse_csv($path_file) {
		$row = 1;
		$data_csv = Array();
		if (($handle = fopen($path_file, "r")) !== FALSE) {
		  while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
			$num = count($data);
			$row++;
			for ($c=0; $c < $num; $c++) {
				$data_csv[] = $data[$c];
			}
		  }
		  fclose($handle);
		}
		return Array('rows' => $row, 'data' => $data_csv);
	}
	/***********************************************************************
	* Read and write log
	************************************************************************/
	function array_stripstuff(&$elem) {
		if (is_array($elem)) {
			foreach ($elem as $key=>$value)
				$elem[str_replace(array(" ",",",".","-","+"),"_",$key)]=$value;
		}
		return $elem;
	}
	function custom_array_key(&$arr) {
		$arr = array_combine(
			array_map(
				function ($str) {
					return trim(str_replace(" ", "_", $str));
				},
				array_keys($arr)
			),
			array_values($arr)
		);
		return $arr;
	}
	function custom_float_val($string_number = '0.00', $string_point = '.') {
		$string_number = strval($string_number);
		$decimal_point = array('.', ',');
		if ($string_point == '.') {
			$string_return = str_replace($decimal_point[1], '', $string_number);
		} else {
			$string_return = str_replace($decimal_point[1], $decimal_point[0], str_replace($decimal_point[0], '', $string_number));
		}
		return floatval($string_return);
	}
	function custom_write_log($path, $content, $type = 'put') {
		$create_new_write = FALSE;
		if (!file_exists($path)) {
			$create_new_write = TRUE;
		}
		if (!$file_handle = fopen($path, 'a+')) {
			return false;
		}
		if ($create_new_write) {
			fwrite($file_handle, "\r\n{$content}");
		} else {
			fwrite($file_handle, "\r\n{$content}");
			//file_put_contents($path, $content.PHP_EOL, FILE_APPEND);
		}
		fclose($file_handle);
	}
	function custom_write_logger($path, $content, $identifier, $type = 'put') {
		$create_new_write = FALSE;
				
		$identifier_to_datepathdir = FALSE;
		$year = date('Y');
		$month = $this->datetime_get_month(date('m'));
		$date = $this->datetime_get_day(date('d'), date('m'), date('Y'));
		$datepathdir = ("{$year}{$month}{$date}");
		if ((int)$datepathdir === intval(substr($identifier, 0, 8))) {
			$identifier_to_datepathdir = TRUE;
		}
		if (!$identifier_to_datepathdir) {
			$year = substr($identifier, 0, 4);
			$month = substr($identifier, 4, 2);
			$date = substr($identifier, 6, 2);
		}
		$path_dir = ($this->log_root . DIRECTORY_SEPARATOR . $year . DIRECTORY_SEPARATOR . $month . DIRECTORY_SEPARATOR . $date);
		if (!file_exists($path)) {
			$create_new_write = TRUE;
		}
		
		
		if (!$file_handle = fopen($path, 'a+')) {
			return false;
		}
		if ($create_new_write) {
			fwrite($file_handle, "\r\n{$content}");
		} else {
			fwrite($file_handle, "\r\n{$content}");
			//file_put_contents($path, $content.PHP_EOL, FILE_APPEND);
		}
		fclose($file_handle);
	}
	/*
	Custom Check Ip
	*/
	function custom_check_ip_version($type, $ip) {
		switch($type) {
			case 'ipv6':
				if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
					return true;
				} else {
					return false;
				}
			break;
			case 'ipv4':
			default:
				if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
					return true;
				} else {
					return false;
				}
			break;
		}
	}
	/***********************************************************************
	* Api Return To API Front End
	************************************************************************/
	function apiReturn($key = null, $data = null) {
		if (!isset($key)) {
			$key = 'response';
		}
		$this->config['apiReturn'][$key] = $data;
	}
	function apiExitPrint($data = null) {
		$this->scripting_time_stopping = microtime(true);
		if (!isset($data)) {
			$data = $this->config['apiReturn'];
		}
		if (!isset($data['response'])) {
			$data['response'] = array('false', 'Error apiExit');
		}
		$static = array(
			'result',
			'remark',
			'payment',
			'time_starting',
			'time_stopping',
			'queried',
		);
		if (count($data['response']) > 1) {
			$i = 0;
			foreach ($data['response'] as $k => $val) {
				$data['response'][$static[$i]] = $val;
				unset($data['response'][$k]);
				$i++;
			}
		}
		/***************************
		Result to false for all transaction
		*******************************/
		//$data['response']['result'] = 'false';
		//$data['response']['remark'] = 'LOG|Malformed Json';
		/*******************************
		*******************************/
		$paramsHeader = array(
			'Expires'			=> 'Mon, 26 Jul 1997 05:00:00 GMT',
			'Last-Modified'		=> gmdate("D, d M Y H:i:s") . " GMT",
			'Cache-Control'		=> 'no-store, no-cache, must-revalidate',
			'Cache-Control'		=> array('post-check=0, pre-check=0', false),
			'Pragma'			=> 'no-cache',
		);
		header_remove('Content-Type');
		$paramsHeader['Content-Type'] = 'application/json;charset=utf-8';
		//====
		$data['response']['time_starting'] = (isset($this->scripting_time_starting) ? $this->scripting_time_starting : 0);
		$data['response']['time_stopping'] = (isset($this->scripting_time_stopping) ? $this->scripting_time_stopping : 0);
		$data['response']['queried'] = ($this->scripting_time_stopping - $this->scripting_time_starting);
		$CreateJson = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
		//$CreateJson = preg_replace("/\" *?: *?(\d+)/", '":"$1"', $CreateJson);
		$this->set_http_header($paramsHeader);
		return $CreateJson;
	}
	/***********************************************************************
	* Cleaning return with ob_clean
	************************************************************************/
	function return_echo_ob($func) {
		ob_start();
		$func;
		return ob_get_clean();
	}
}