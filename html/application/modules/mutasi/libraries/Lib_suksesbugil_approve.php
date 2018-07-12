<?php
if (!defined('BASEPATH')) { exit("Cannot access script directly."); }

class Lib_suksesbugil_approve {
	protected $base_suksesbugil = array();
	protected $base_mutasi = array();
	protected $CI;
	protected $curl_options, $client;
	protected $cookies_path = __DIR__;
	protected $ch = NULL;
	protected $curlcontent = NULL;
	private $login_params = array();
	protected $db_suksesbugil;
	function __construct() {
		$this->CI = &get_instance();
		$this->CI->load->config('mutasi/base_suksesbugil');
		$this->base_suksesbugil = $this->CI->config->item('base_suksesbugil');
		$this->CI->load->config('mutasi/base_mutasi');
		$this->base_mutasi = $this->CI->config->item('base_mutasi');
		$this->db_suksesbugil = $this->CI->load->database('mutasi', TRUE);
		if (isset($this->base_suksesbugil['cookie_path_approve'])) {
			$this->cookies_path = $this->base_suksesbugil['cookie_path_approve'];
		}
		$this->set_headers();
		$this->add_headers('Content-type', 'application/x-www-form-urlencoded');
		//--------------
		$this->curl_options = array(
			'user_agent'		=>  $this->base_suksesbugil['useragent'],
		);
		$this->client = (isset($this->base_suksesbugil['client']) ? $this->base_suksesbugil['client'] : array());
		if (isset($this->client['user_ip'])) {
			$this->client['user_ip'] = $this->set_client_ip($this->client['user_ip']);
		}
		// Set Curl Init
		$this->cookie_filename = (isset($base_mutasi['cookies_filename']) ? $base_mutasi['cookies_filename'] : 'cookies.txt');
		$this->cookie_filename = (is_string($this->cookie_filename) ? strtolower($this->cookie_filename) : 'cookies.txt');
		$this->set_curl_init($this->create_curl_headers($this->headers), $this->cookie_filename);
	}
	private function set_login_params($login_params) {
		$this->login_params['username'] = (isset($login_params['login_username']) ? $login_params['login_username'] : '#[USERNAME]#');
		$this->login_params['password'] = (isset($login_params['login_password']) ? $login_params['login_password'] : '#[PASSWORD]#');
		$this->login_params['pin'] = (isset($login_params['login_pin']) ? $login_params['login_pin'] : '#[PIN]#');
		// LOAD FRESH CONFIG
		$this->base_suksesbugil = $this->CI->config->item('base_suksesbugil');
		// REPLACE WITH NEW LOGIN
		if (isset($this->base_suksesbugil['url']['authcode']) && isset($this->login_params['username'])) {
			$this->base_suksesbugil['url']['authcode'] = str_replace('#[USERNAME]#', $this->login_params['username'], $this->base_suksesbugil['url']['authcode']);
		}
		if (isset($this->base_suksesbugil['params']['login']['entered_login']) && isset($this->login_params['username'])) {
			$this->base_suksesbugil['params']['login']['entered_login'] = str_replace('#[USERNAME]#', $this->login_params['username'], $this->base_suksesbugil['params']['login']['entered_login']);
		}
		if (isset($this->base_suksesbugil['params']['login']['vb_login_md5password']) && isset($this->login_params['password'])) {
			$this->base_suksesbugil['params']['login']['vb_login_md5password'] = str_replace('#[PASSWORD]#', md5($this->login_params['password']), $this->base_suksesbugil['params']['login']['vb_login_md5password']);
		}
		if (isset($this->base_suksesbugil['params']['login']['vb_login_md5password_utf']) && isset($this->login_params['password'])) {
			$this->base_suksesbugil['params']['login']['vb_login_md5password_utf'] = str_replace('#[PASSWORD]#', md5($this->login_params['password']), $this->base_suksesbugil['params']['login']['vb_login_md5password_utf']);
		}
		if (isset($this->base_suksesbugil['params']['pin']['pin']) && isset($this->login_params['pin'])) {
			$this->base_suksesbugil['params']['pin']['pin'] = str_replace('#[PIN]#', $this->login_params['pin'], $this->base_suksesbugil['params']['pin']['pin']);
		}
		return $this->login_params;
	}
	
	//-----------------------
	function set_curl_init($headers = null, $cookie_filename = 'cookies.txt') {
		$this->ch = curl_init();
		curl_setopt($this->ch, CURLOPT_URL, $this->base_suksesbugil['init']);
		curl_setopt($this->ch, CURLOPT_COOKIESESSION, TRUE);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->ch, CURLOPT_VERBOSE, TRUE);
		curl_setopt($this->ch, CURLOPT_COOKIELIST, TRUE);
		curl_setopt($this->ch, CURLOPT_COOKIEFILE, ($this->cookies_path . DIRECTORY_SEPARATOR . "{$cookie_filename}.log"));
        curl_setopt($this->ch, CURLOPT_COOKIEJAR, ($this->cookies_path . DIRECTORY_SEPARATOR . "{$cookie_filename}.log"));
		if (isset($headers)) {
			curl_setopt($this->ch, CURLOPT_HEADER, false);
			curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
		} else {
			curl_setopt($this->ch, CURLOPT_HEADER, false);
		}
		curl_setopt($this->ch, CURLOPT_USERAGENT, $this->curl_options['user_agent']);
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($this->ch, CURLOPT_ENCODING, "");
		curl_setopt($this->ch, CURLOPT_AUTOREFERER, true);
	}
	function curlexec() {
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, TRUE);
        try {
			$this->curlcontent = curl_exec($this->ch);
		} catch (Exception $ex) {
			throw $ex;
			$this->curlcontent = NULL;
		}
    }
	//=======================================================
	private function login_authorize($type = 'pin') {
		switch (strtolower($type)) {
			case 'pin':
				curl_setopt($this->ch, CURLOPT_URL, $this->base_suksesbugil['url']['pin']);
				curl_setopt($this->ch, CURLOPT_REFERER, $this->base_suksesbugil['url']['login']);
				curl_setopt($this->ch, CURLOPT_POST, TRUE);
				curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($this->base_suksesbugil['params']['pin']));
				$this->curlexec();
			break;
			case 'code':
				curl_setopt($this->ch, CURLOPT_URL, $this->base_suksesbugil['url']['usercode']);
				curl_setopt($this->ch, CURLOPT_REFERER, $this->base_suksesbugil['url']['pin']);
				curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, $this->base_suksesbugil['method']['usercode']);
				$this->curlexec();
				curl_setopt($this->ch, CURLOPT_URL, $this->base_suksesbugil['url']['authcode']);
				curl_setopt($this->ch, CURLOPT_REFERER, $this->base_suksesbugil['url']['usercode']);
				curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, $this->base_suksesbugil['method']['authcode']);
				$this->curlexec();
			break;
			case 'agent':
				curl_setopt($this->ch, CURLOPT_URL, $this->base_suksesbugil['url']['xpage']);
				curl_setopt($this->ch, CURLOPT_REFERER, $this->base_suksesbugil['url']['authcode']);
				curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, $this->base_suksesbugil['method']['xpage']);
				$this->curlexec();
				curl_setopt($this->ch, CURLOPT_URL, $this->base_suksesbugil['url']['agent']);
				curl_setopt($this->ch, CURLOPT_REFERER, $this->base_suksesbugil['url']['xpage']);
				curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, $this->base_suksesbugil['method']['agent']);
				$this->curlexec();
			break;
		}
	}
	function login() {
		if (!isset($this->login_params['username'])) {
			exit("Login params not yet defined!");
		}
		curl_setopt($this->ch, CURLOPT_URL, $this->base_suksesbugil['url']['login']);
        curl_setopt($this->ch, CURLOPT_REFERER, $this->base_suksesbugil['init']);
        curl_setopt($this->ch, CURLOPT_POST, TRUE);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($this->base_suksesbugil['params']['login']));
        $this->curlexec();
		
		//====
		$this->login_authorize('pin');
		$this->login_authorize('code');
		$this->login_authorize('agent');
	}
	function logout() {
		curl_setopt($this->ch, CURLOPT_URL, $this->base_suksesbugil['url']['logout']);
        curl_setopt($this->ch, CURLOPT_REFERER, $this->base_suksesbugil['init']);
		$this->curlexec();
		curl_setopt($this->ch, CURLOPT_URL, $this->base_suksesbugil['url']['logout_off']);
        curl_setopt($this->ch, CURLOPT_REFERER, $this->base_suksesbugil['url']['logout']);
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, $this->base_suksesbugil['method']['logout_off']);
		$this->curlexec();
        curl_close($this->ch);
		return $this->curlcontent;
    }
	####
	function get_login_params($by_type, $by_value) {
		$collectData = array();
		$by_type = (is_string($by_type) ? strtolower($by_type) : 'bank');
		$value = "";
		if (isset($by_value)) {
			if (is_numeric($by_value) || is_string($value)) {
				$value = sprintf("%s", $by_value);
			}
		}
		$value = ((is_string($value) || is_numeric($value)) ? $value : 'all');
		$value = strtolower($value);
		$this->db_suksesbugil->where('login_bank', $value);
		$this->db_suksesbugil->limit(1);
		$sql_query = $this->db_suksesbugil->get($this->base_mutasi['mutasi_tables']['sb_login']);
		if ($sql_query != FALSE) {
			$collectData['login_params'] = (array)$sql_query->row();
			$this->set_login_params($collectData['login_params']);
		}
		return $collectData;
	}
	function get_data_transaction_by($by_type, $by_value) {
		$collectData = array();
		$by_type = (is_string($by_type) ? strtolower($by_type) : 'bank');
		$value = "";
		if (isset($by_value)) {
			if (is_numeric($by_value) || is_string($value)) {
				$value = sprintf("%s", $by_value);
			}
		}
		$value = ((is_string($value) || is_numeric($value)) ? $value : 'all');
		$value = strtolower($value);
		switch (strtolower($by_type)) {
			case 'bank':
			default:
				if (isset($this->base_suksesbugil['get_data_url'][$value])) {
					$this->set_curl_init($this->create_curl_headers($this->headers));
					$this->login();
					curl_setopt($this->ch, CURLOPT_URL, $this->base_suksesbugil['get_data_url'][$value]);
					curl_setopt($this->ch, CURLOPT_REFERER, $this->base_suksesbugil['url']['menu']);
					$this->curlexec();
					$collectData['raw_transactions'] = $this->curlcontent;
					$this->logout();
				}
			break;
		}
		
		
		return $collectData;
	}
	
	//***********************************************************
	function set_auto_approve($bank_code, $input_params) {
		if (isset($bank_code)) {
			if (is_numeric($bank_code) || is_string($bank_code)) {
				$value = sprintf("%s", $bank_code);
			}
		}
		$value = ((is_string($value) || is_numeric($value)) ? $value : 'all');
		$value = strtolower($value);
		if (!isset($this->base_suksesbugil['url']['approve'][$value])) {
			return FALSE;
		}
		$this->set_curl_init($this->create_curl_headers($this->headers));
		$this->login();
		curl_setopt($this->ch, CURLOPT_URL, $this->base_suksesbugil['get_data_url'][$value]);
		curl_setopt($this->ch, CURLOPT_REFERER, $this->base_suksesbugil['url']['menu']);
		$this->curlexec();
		curl_setopt($this->ch, CURLOPT_URL, $this->base_suksesbugil['url']['approve'][$value]);
        curl_setopt($this->ch, CURLOPT_REFERER, $this->base_suksesbugil['get_data_url'][$value]);
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->ch, CURLOPT_POST, TRUE);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($input_params));
		$this->curlexec();
		$approve_curl_response = $this->curlcontent;
		$this->logout();
		return $approve_curl_response;
	}
	function approve_deposit($bank_code, $input_params) {
		if (isset($bank_code)) {
			if (is_numeric($bank_code) || is_string($bank_code)) {
				$value = sprintf("%s", $bank_code);
			}
		}
		$value = ((is_string($value) || is_numeric($value)) ? $value : 'all');
		$value = strtolower($value);
		if (!isset($this->base_suksesbugil['url']['approve'][$value])) {
			return FALSE;
		}
		curl_setopt($this->ch, CURLOPT_URL, $this->base_suksesbugil['get_data_url'][$value]);
		curl_setopt($this->ch, CURLOPT_REFERER, $this->base_suksesbugil['url']['menu']);
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'GET');
		$this->curlexec();
		curl_setopt($this->ch, CURLOPT_URL, $this->base_suksesbugil['url']['approve'][$value]);
        curl_setopt($this->ch, CURLOPT_REFERER, $this->base_suksesbugil['get_data_url'][$value]);
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->ch, CURLOPT_POST, TRUE);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($input_params));
		$this->curlexec();
		return $this->curlcontent;
	}
	
	
	
	
	
	
	
	//=======================================================
	// Utils
	//=======================================================
	function create_time_zone($timezone, $datetime = null) {
		if (!isset($datetime)) {
			$datetime = date('Y-m-d H:i:s');
		}
		$DateObject = new DateTime($datetime);
		$DateObject->setTimezone(new DateTimeZone($timezone));
		// TO using use @DateObject->format('Y') : Year
		return $DateObject;
	}
	public function generate_curl_headers($headers = null) {
		if (!isset($headers)) {
			$headers = $this->headers;
		}
		return $this->create_curl_headers($headers);
	}
	public function create_curl_headers($headers = array()) {
		$curlheaders = array();
		foreach ($headers as $ke => $val) {
			$curlheaders[] = "{$ke}: {$val}";
		}
		return $curlheaders;
	}
	function set_headers($headers = array()) {
		$this->headers = $headers;
		return $this;
	}
	function reset_headers() {
		$this->headers = null;
		return $this;
	}
	function add_headers($key, $val) {
		if (!isset($this->headers)) {
			$this->headers = $this->get_this_headers();
		}
		if (!isset($this->headers[$key])) {
			$add_header = array($key => $val);
			$this->headers = array_merge($add_header, $this->headers);
		}
	}
	function get_this_headers() {
		return $this->headers;
	}
	private function set_client_ip($data = null) {
		// Get user IP address
		$ip = (isset($data)) ? $data : '0.0.0.0';
		if(strpos($ip, ',')) {
			$ip2 = explode(',', $ip);
			$ip = $ip2[0];
			if(strpos($ip, '192.168.') !== false && isset($ip2[1])) {
				$ip = $ip2[1];
			} elseif(strpos($ip, '10.') !== false && isset($ip2[1])) {
				$ip = $ip2[1];
			} elseif(strpos($ip, '172.16.') !== false && isset($ip2[1])) {
				$ip = $ip2[1];
			}
		}
		$ip = filter_var($ip, FILTER_VALIDATE_IP);
		$ip = ($ip === false) ? '0.0.0.0' : $ip;
		return $ip;
	}
}