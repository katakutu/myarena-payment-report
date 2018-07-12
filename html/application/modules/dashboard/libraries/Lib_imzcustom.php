<?php
if ( ! defined('BASEPATH')) { exit('No direct script access allowed'); }
class Lib_imzcustom {
	private static $instance;
	function __construct() {
		$php_input_request = self::php_input_request();
		$this->set_php_input_request($php_input_request);
	}
	private static function get_instance() {
		if (!self::$instance) {
            self::$instance = new Lib_imzcustom();
        }
		//self::$instance->set_instance(self::$instance->table, self::$instance->transaction_data, self::$instance->merchant_system);
        return self::$instance;
	}
	// Set utils instance
	function set_php_input_request($php_input_request) {
		$this->php_input_request = $php_input_request;
		return $this;
	}
	function get_php_input_request() {
		return $this->php_input_request;
	}
	//--------------------------------------------------
	// Utils
	//--------------------------------------------------
	public static function parse_raw_http_request($content_type) {
		$input = file_get_contents('php://input');
		preg_match('/boundary=(.*)$/', $content_type, $bound_matches);
		$boundary = (isset($bound_matches[1]) ? $bound_matches[1] : '');
		$a_blocks = preg_split("/-+{$boundary}/", $input);
		array_pop($a_blocks);
		$a_data = array();
		// loop data blocks
		$i = 0;
		foreach ($a_blocks as $id => $block) {
			if (empty($block)) {
				continue;
			}
			// you'll have to var_dump $block to understand this and maybe replace \n or \r with a visibile char
			// parse uploaded files
			if (strpos($block, 'application/octet-stream') !== FALSE) {
				// match "name", then everything after "stream" (optional) except for prepending newlines 
				preg_match("/name=\"([^\"]*)\".*stream[\n|\r]+([^\n\r].*)?$/s", $block, $matches);
			} else {
				// match "name" and optional value in between newline sequences
				preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $block, $matches);
			}
			if (isset($matches[1]) && isset($matches[2])) {
				$a_data[$matches[1]] = $matches[2];
			}
			$i += 1;
		}
		return $a_data;
	}
	public static function php_input_request() {
		###############################
		# Request Input
		###############################
		$RequestInputParams = array();
		$RequestInput = array();
		$incomingHeaders = self::apache_headers();
		if (isset($incomingHeaders['Content-Type'])) {
			if ((!is_array($incomingHeaders['Content-Type'])) && (!is_object($incomingHeaders['Content-Type']))) {
				$incomingHeaders['Content-Type'] = strtolower($incomingHeaders['Content-Type']);
				if (strpos($incomingHeaders['Content-Type'], 'application/json') !== FALSE) {
					$RequestInput = file_get_contents("php://input");
					if (!$RequestInputJson = json_decode($RequestInput, true)) {
						parse_str($RequestInput, $RequestInputParams);
					} else {
						$RequestInputParams = $RequestInputJson;
					}
				} else if (strpos($incomingHeaders['Content-Type'], 'application/x-www-form-urlencoded') !== FALSE) {
					$RequestInput = file_get_contents("php://input");
					parse_str($RequestInput, $RequestInputParams);
				} else if (strpos($incomingHeaders['Content-Type'], 'application/xml') !== FALSE) {
					$RequestInput = file_get_contents("php://input");
					$RequestInputParams = $RequestInput;
				} else if (strpos($incomingHeaders['Content-Type'], 'multipart/form-data') !== FALSE) {
					$RequestInput = self::parse_raw_http_request($incomingHeaders['Content-Type']);
					$RequestInputParams = $RequestInput;
					if ($_SERVER['REQUEST_METHOD'] == 'POST') {
						if (isset($_POST) && (count($_POST) > 0)) {
							foreach ($_POST as $k => $v) {
								$RequestInputParams = array_merge(array($k => $v), $RequestInputParams);
							}
						}
						if (isset($_FILES) && (count($_FILES) > 0)) {
							foreach ($_FILES as $k => $v) {
								$RequestInputParams = array_merge(array($k => $v), $RequestInputParams);
							}
						}
					}
				} else {
					$RequestInput['kontol'] = 'latos';
					self::parse_raw_http_request($incomingHeaders['Content-Type'], $RequestInput);
					$RequestInputParams = $RequestInput;
					if ($_SERVER['REQUEST_METHOD'] == 'POST') {
						if (isset($_POST) && (count($_POST) > 0)) {
							foreach ($_POST as $k => $v) {
								$RequestInputParams = array_merge(array($k => $v), $RequestInputParams);
							}
						}
						if (isset($_FILES) && (count($_FILES) > 0)) {
							foreach ($_FILES as $k => $v) {
								$RequestInputParams = array_merge(array($k => $v), $RequestInputParams);
							}
						}
					}
				}
			}
		} else {
			$RequestInput = file_get_contents("php://input");
			parse_str($RequestInput, $RequestInputParams);
		}
		$params['input'] = $RequestInput;
		$params['header'] = $incomingHeaders;
		$params['body'] = $RequestInputParams;
		return $params;
	}
	public static function php_input_querystring() {
		$__GET = (isset($_GET) ? $_GET : array());
		$request_uri = ((isset($_SERVER['REQUEST_URI']) && (!empty($_SERVER['REQUEST_URI']))) ? $_SERVER['REQUEST_URI'] : '');
		$query_string = (isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '');
		parse_str(parse_url(html_entity_decode($request_uri), PHP_URL_QUERY), $array);
		if (count($array) > 0) {
			foreach ($array as $key => $val) {
				$__GET[$key] = $val;
			}
		}
		return $__GET;
	}
	public static function _GET(){
		$__GET = (isset($_GET) ? $_GET : array());
		$request_uri = ((isset($_SERVER['REQUEST_URI']) && (!empty($_SERVER['REQUEST_URI']))) ? $_SERVER['REQUEST_URI'] : '');
		$_get_str = explode('?', $request_uri);
		if( !isset($_get_str[1]) ) return $__GET;
		$params = explode('&', $_get_str[1]);
		foreach ($params as $p) {
			$parts = explode('=', $p);
			$parts[0] = (is_string($parts[0]) ? strtolower($parts[0]) : $parts[0]);
			$__GET[$parts[0]] = isset($parts[1]) ? $parts[1] : '';
		}
		return $__GET;
	}
	public static function apache_headers() {
		if (function_exists('apache_request_headers')) {
			$headers = apache_request_headers();
			$out = array();
			foreach ($headers AS $key => $value) {
				$key = str_replace(" ", "-", ucwords(strtolower(str_replace("-", " ", $key))));
				$out[$key] = $value;
			}
			if	(isset($_SERVER['CONTENT_TYPE'])) {
				$out['Content-Type'] = $_SERVER['CONTENT_TYPE'];
			}
			if (isset($_ENV['CONTENT_TYPE'])) {
				$out['Content-Type'] = $_ENV['CONTENT_TYPE'];
			}
		} else {
			$out = array();
			if	(isset($_SERVER['CONTENT_TYPE'])) {
				$out['Content-Type'] = $_SERVER['CONTENT_TYPE'];
			}
			if (isset($_ENV['CONTENT_TYPE'])) {
				$out['Content-Type'] = $_ENV['CONTENT_TYPE'];
			}
			if (isset($_SERVER)) {
				if (count($_SERVER) > 0) {
					foreach ($_SERVER as $key => $value) {
						if (substr($key, 0, 5) == "HTTP_") {
							$key = str_replace(" ", "-", ucwords(strtolower(str_replace("_", " ", substr($key, 5)))));
							$out[$key] = $value;
						}
					}
				}
			}
		}
		return $out;
	}
	
	//-------------------------
	function custom_file_exists($file_path='') {
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
	public function get_pagination_start($page, $per_page, $rows_count) {
		$end_page = ceil($rows_count / $per_page);
		if ($end_page < 2) { $end_page = 1; }
		$page = isset($page) ? intval($page) : 1;
		//if ($page < 1 || $page > $end_page) { $page = 1; }
		$start = ceil(($page * $per_page) - $per_page);
		if ($start > 0) {
			return $start;
		}
		return 0;
	}
	public function generate_pagination($self, &$page, $per_page, $rows_count, &$start) {
		$sum_pages = ceil($rows_count / $per_page);
		if ($sum_pages < 2) { $sum_pages = 1; }
		$page = isset($page) ? intval($page) : 1;
		if ($page < 1 || $page > $sum_pages) { $page = 1; }
		$start = ceil(($page * $per_page) - $per_page);
		$page_display = '<p class="text-right">Page ' . $page . ' from ' . $sum_pages . '</p>';
		$page_display .= '<ul class="pagination pull-right">';
		$page_display .= '<li class="arrow first">' . '<a href="' . sprintf($self, ($sum_pages / $sum_pages)) . '" rel="first"><i class="fa fa-angle-double-left"></i></a>';
		if ($sum_pages <= 0) {
			if ($page > 1) {
				$page_display .= '<li class="prev"><a href="' . sprintf($self, ($page - 1)) . '"><i class="fa fa-angle-left"></i></a></li>';
			} else {
				$page_display .= '<li class="prev"><span><i class="fa fa-angle-left"></i></span></li>';
			}
			$i = 1;
			while ($i <= $sum_pages) {
				$page_display .= '<li><a href="' . sprintf($self, $i) . '">' . $i . '</a></li>';
				$i++;
			}
			$page_display .= '<li class="next"><a href="' . sprintf($self, ($page + 1)) . '" rel="next"><i class="fa fa-angle-right"></i></a></li>';
		} else {
			if ($page > 1) {
				$page_display .= '<li class="prev"><a href="' . sprintf($self, ($page - 1)) . '"><i class="fa fa-angle-left"></i></a></li>';
			} else {
				$page_display .= '<li class="prev"><span><i class="fa fa-angle-left"></i></span></li>';
			}
			for ($i = ($page - 2); $i < $page; $i++) {
				if ($i > 0) {
					$page_display .= '<li><a href="' . sprintf($self, $i) . '">' . $i . '</a></li>';
				}
			}
			$page_display .= '<li class="active"><span>' . $page . '</span></li>';
			for ($i = ($page + 1); $i <= ($page + 2); $i++) {
				if ($i <= $sum_pages) {
					$page_display .= '<li><a href="' . sprintf($self, $i) . '">' . $i . '</a></li>';
				}
			}
			if (($page + 1) > $sum_pages) {
				$page_display .= '<li class="next"><span><i class="fa fa-angle-right"></i></span></li>';
			} else {
				$page_display .= '<li class="next"><a href="' . sprintf($self, ($page + 1)) . '" rel="next"><i class="fa fa-angle-right"></i></a></li>';
			}
		}
		$page_display .= '<li class="arrow last"><a href="' . sprintf($self, $sum_pages) . '" rel="last"><i class="fa fa-angle-double-right"></i></a></li>';
		$page_display .= '</ul>';
		return $page_display;
	}
	
	
	
}