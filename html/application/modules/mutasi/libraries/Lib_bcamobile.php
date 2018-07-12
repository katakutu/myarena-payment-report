<?php
if (!defined('BASEPATH')) {
	exit("Cannot load script directly");
}
class Lib_bcamobile {
	protected $CI;
	protected $curl_options, $client;
	protected $cookies_path = __DIR__;
	protected $ch = NULL;
	protected $curlcontent = NULL;
	function __construct() {
		$this->CI = &get_instance();
		$this->CI->load->config('mutasi/base_mutasi');
		$base_mutasi = $this->CI->config->item('base_mutasi');
		$this->curl_options = array(
			'user_agent'		=> (isset($base_mutasi['curl_options']['user_agent']) ? $base_mutasi['curl_options']['user_agent'] : 'Mozilla/5.0 (Linux; U; Android 4.1.7; en-us; Sony Ericson) edited by: (imzers@gmail.com)'),
			
		);
		if (isset($base_mutasi['cookies']['bca'])) {
			$this->cookies_path = $base_mutasi['cookies']['bca'];
		}
		$this->client = (isset($base_mutasi['client']) ? $base_mutasi['client'] : array());
		if (isset($this->client['user_ip'])) {
			$this->client['user_ip'] = $this->set_client_ip($this->client['user_ip']);
		}
		$this->set_headers();
		$this->add_headers('Content-type', 'application/x-www-form-urlencoded');
		
		// Set Curl Init
		$this->cookie_filename = (isset($base_mutasi['cookies_filename']) ? $base_mutasi['cookies_filename'] : 'cookies.txt');
		$this->cookie_filename = (is_string($this->cookie_filename) ? strtolower($this->cookie_filename) : 'cookies.txt');
		$this->set_curl_init($this->create_curl_headers($this->headers, $this->cookie_filename));
		//--------------
		/*
		$DateStarting = $this->create_time_zone(ConstantConfig::$timezone, $this->date_periode['starting']);
		$DateStopping = $this->create_time_zone(ConstantConfig::$timezone, $this->date_periode['stopping']);
		*/
	}
	function set_curl_init($headers = null, $cookie_filename = 'cookies.txt') {
		$this->ch = curl_init();
		curl_setopt($this->ch, CURLOPT_URL, 'https://m.klikbca.com/login.jsp');
		curl_setopt($this->ch, CURLOPT_COOKIESESSION, TRUE);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->ch, CURLOPT_VERBOSE, TRUE);
		curl_setopt($this->ch, CURLOPT_COOKIELIST, TRUE);
		curl_setopt($this->ch, CURLOPT_COOKIEFILE, ($this->cookies_path . DIRECTORY_SEPARATOR . $cookie_filename));
        curl_setopt($this->ch, CURLOPT_COOKIEJAR, ($this->cookies_path . DIRECTORY_SEPARATOR . $cookie_filename));
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
        $this->curlcontent = curl_exec($this->ch);
    }
	function login($username, $password) {
		$this->curlexec();
		//curl_close($this->ch);
		$login_params = array(
			'value(user_id)=' . $username, 
			'value(pswd)=' . $password, 
			'value(Submit)=LOGIN', 
			'value(actions)=login', 
			'value(user_ip)=' . $this->client['user_ip'], 
			'user_ip=' . $this->client['user_ip'], 
			'value(mobile)=true', 
			'mobile=true',
		);
        $params = implode('&', $login_params);
		curl_setopt($this->ch, CURLOPT_URL, 'https://m.klikbca.com/authentication.do' );
        curl_setopt($this->ch, CURLOPT_REFERER, 'https://m.klikbca.com/login.jsp' );
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($this->ch, CURLOPT_POST, TRUE);
        $this->curlexec();
		return $this->curlcontent;
    }
	function logout() {
		curl_setopt($this->ch, CURLOPT_URL, 'https://m.klikbca.com/authentication.do?value(actions)=logout');
        curl_setopt($this->ch, CURLOPT_REFERER, 'https://m.klikbca.com/authentication.do?value(actions)=menu');
		$this->curlexec();
        curl_close($this->ch);
		return $this->curlcontent;
    }
    //====================
	// BCA Actions
	function get_informasi_rekening() {
		curl_setopt($this->ch, CURLOPT_URL, 'https://m.klikbca.com/accountstmt.do?value(actions)=menu');
        curl_setopt($this->ch, CURLOPT_REFERER, 'https://m.klikbca.com/authentication.do');
		$this->curlexec();
		curl_setopt($this->ch, CURLOPT_URL, 'https://m.klikbca.com/balanceinquiry.do');
		curl_setopt($this->ch, CURLOPT_REFERER, 'https://m.klikbca.com/accountstmt.do?value(actions)=menu');
		$this->curlexec();
		libxml_use_internal_errors(true);
		$doc = new DOMDocument;
		$doc->preserveWhiteSpace = false;
		$doc->validateOnParse = false;
		$doc->loadHTML($this->curlcontent);
		$xpath = new DOMXPath($doc);
		$fonts = $xpath->query("//font");
		if (isset($fonts->length)) {
			if ((int)$fonts->length < 5) {
				return false;
			}
			$informasi_rekening = $fonts->item(4);
			return sprintf('%s', $informasi_rekening->nodeValue);
		} else {
			return FALSE;
		}
	}
	function get_mutasi_transactions($transaction_datetime = array()) {
		$informasi_rekening = array(
			'rekening_number'			=> '',
			'rekening_name'				=> '',
			'rekening_periode'			=> '',
			'rekening_currency'			=> '',
		);
		if (!is_array($transaction_datetime)) {
			return FALSE;
		}
		if (!isset($transaction_datetime['starting']) || (!isset($transaction_datetime['stopping']))) {
			return FALSE;
		}
        curl_setopt($this->ch, CURLOPT_URL, 'https://m.klikbca.com/accountstmt.do?value(actions)=menu');
        curl_setopt($this->ch, CURLOPT_REFERER, 'https://m.klikbca.com/authentication.do');
        $this->curlexec();
        curl_setopt($this->ch, CURLOPT_URL, 'https://m.klikbca.com/accountstmt.do?value(actions)=acct_stmt');
        curl_setopt($this->ch, CURLOPT_REFERER, 'https://m.klikbca.com/accountstmt.do?value(actions)=menu');
        $this->curlexec();
		$query_params = array(
			'r1=1', 
			'value(D1)=0', 
			'value(startDt)=' . $transaction_datetime['starting']->format('d'),
			'value(startMt)=' . $transaction_datetime['starting']->format('m'),
			'value(startYr)=' . $transaction_datetime['starting']->format('Y'),
			'value(endDt)=' . $transaction_datetime['stopping']->format('d'),
			'value(endMt)=' . $transaction_datetime['stopping']->format('m'),
			'value(endYr)=' . $transaction_datetime['stopping']->format('Y'),
		);
        $params = implode('&', $query_params);
        curl_setopt($this->ch, CURLOPT_URL, 'https://m.klikbca.com/accountstmt.do?value(actions)=acctstmtview');
        curl_setopt($this->ch, CURLOPT_REFERER, 'https://m.klikbca.com/accountstmt.do?value(actions)=acct_stmt');
        curl_setopt($this->ch, CURLOPT_POST, TRUE);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $params);
        $this->curlexec();
		##
		# DEBUG
		##
		/*
		return $this->curlcontent;
		exit;
		*/
		$rows = array();
		try {
			$rows['parse'] = explode('<table width="100%" class="blue">', $this->curlcontent);
		} catch (Exception $ex) {
			throw $ex;
			return FALSE;
		}
		$rows['items'] = array();
		$rows['content'] = array();
		$parsed = array(
			'first' => (isset($rows['parse'][1]) ? $rows['parse'][1] : NULL),
			'html'	=> (isset($rows['parse'][0]) ? $rows['parse'][0] : ''),
		);
		if (is_string($parsed['html'])) {
			$rows['dom'] = array();
			if (strlen($parsed['html']) > 0) {
				libxml_use_internal_errors(true);
				$rows['dom']['doc'] = new DOMDocument;
				$rows['dom']['doc']->preserveWhiteSpace = false;
				$rows['dom']['doc']->validateOnParse = false;
				$rows['dom']['doc']->loadHTML($parsed['html']);
				$rows['dom']['xpath'] = new DOMXPath($rows['dom']['doc']);
				$rows['dom']['tds'] = $rows['dom']['xpath']->query("//td[@align='left']");
				if (isset($rows['dom']['tds']->length)) {
					$rows['dom']['items'] = array();
					for ($tds_i = 0; $tds_i < $rows['dom']['tds']->length; $tds_i++) {
						$rows['dom']['items'][] = $rows['dom']['tds']->item($tds_i);
					}
					if (isset($rows['dom']['items'][1])) {
						$informasi_rekening['rekening_number'] = sprintf("%s", $rows['dom']['items'][1]->nodeValue);
					}
					if (isset($rows['dom']['items'][3])) {
						$informasi_rekening['rekening_name'] = sprintf("%s", $rows['dom']['items'][3]->nodeValue);
					}
					if (isset($rows['dom']['items'][5])) {
						$informasi_rekening['rekening_periode'] = sprintf("%s", $rows['dom']['items'][5]->nodeValue);
					}
					if (isset($rows['dom']['items'][7])) {
						$informasi_rekening['rekening_currency'] = sprintf("%s", $rows['dom']['items'][7]->nodeValue);
					}
				}
			}
		}
		if ($parsed['first']) {
			$table = explode('</table>', $parsed['first']);
			if (count($table) > 0) {
				$content = explode('<tr',  $table[0]);
				if (count($content) > 0) {
					foreach($content as $val) {
						if (substr($val, 0, 8) == ' bgcolor') {
							$rows['content'][] = $val;
						}
					}
				}
				$rows['table'] = $table;
			}
			if (count($rows['content']) > 0) {
				$saldo_awal = 0;
				foreach($rows['content'] as $key => $val) {
					$rows['items'][$key] = explode('</td>', $val);
					if (count($rows['items'][$key]) > 0) {
						if (strlen($rows['items'][$key][0]) > 0) {
							$rows['items'][$key]['transaction_date'] = substr($rows['items'][$key][0], -5); // Get Date
						}
						if (strpos(strtolower($rows['items'][$key]['transaction_date']), 'pend') !== FALSE) {
							$rows['items'][$key]['transaction_type'] = 'PEND';
						} else {
							$rows['items'][$key]['transaction_type'] = $rows['items'][$key]['transaction_date'];
						}
						if (strtoupper($rows['items'][$key]['transaction_type']) !== 'PEND') {
							$rows['items'][$key]['transaction_date_string'] = $rows['items'][$key]['transaction_type'] . '/' . date('Y');
						} else {
							$rows['items'][$key]['transaction_date_string'] = date('d/m/Y');
						}
						unset($rows['items'][$key][0]);
						if (isset($rows['items'][$key][1])) {
							$transaction_detail = explode("<td valign='top'>", $rows['items'][$key][1]);
							if (count($transaction_detail) > 0) {
								$rows['items'][$key]['transaction_description'] = str_replace('<td>', '', $transaction_detail[0]);
								$rows['items'][$key]['transaction_detail']  = explode('<br>', $transaction_detail[0]);
								$rows['items'][$key]['transaction_code'] = (isset($transaction_detail[1]) ? $transaction_detail[1] : '');
							}
							if (count($rows['items'][$key]['transaction_detail']) > 0) {
								foreach($rows['items'][$key]['transaction_detail'] as $k => &$v ) {
									$v = trim(strip_tags($v));
								}
							}
							$last_transaction_detail = count($rows['items'][$key]['transaction_detail']);
							$rows['items'][$key]['transaction_amount'] = end($rows['items'][$key]['transaction_detail']);
							$rows['items'][$key]['transaction_amount'] = str_replace(",", "", $rows['items'][$key]['transaction_amount']);
							# Maks: 2,147,483,647 for transaction_amount
							$rows['items'][$key]['transaction_amount'] = sprintf("%.02f", $rows['items'][$key]['transaction_amount']);
							$rows['items'][$key]['informasi_rekening'] = $informasi_rekening;
							if (is_string($rows['items'][$key]['transaction_code'])) {
								$rows['items'][$key]['transaction_code'] = trim($rows['items'][$key]['transaction_code']);
								switch (strtoupper($rows['items'][$key]['transaction_code'])) {
									case 'CR':
										$rows['items'][$key]['transaction_payment'] = 'deposit';
									break;
									case 'DB':
									default:
										$rows['items'][$key]['transaction_payment'] = 'transfer';
									break;
								}
							}
							# Make saldo
							$saldo_awal += $rows['items'][$key]['transaction_amount'];
							$rows['items'][$key]['transaction_saldo'] = $saldo_awal;
							### unset all not needed array
							if (isset($rows['items'][$key][1])) {
								unset($rows['items'][$key][1]);
							}
							if (isset($rows['items'][$key][2])) {
								unset($rows['items'][$key][2]);
							}
						}
					}
				}
			}
			return $rows;
		}
		return false;
    }
	function get_rekening_transaction_by($by_type, $input_params = array()) {
		$by_type = (is_string($by_type) ? strtolower($by_type) : 'date');
		if (!is_array($input_params)) {
			return FALSE;
		}
		switch (strtolower($by_type)) {
			case 'current':
				$transaction_datetime = array(
					'starting'	=> new DateTime(date('Y-m-d')),
					'stopping'	=> new DateTime(date('Y-m-d')),
				);
			break;
			case 'date':
			default:
				if (!isset($input_params['date'])) {
					return FALSE;
				}
				if (!isset($input_params['date']['starting']) || (!isset($input_params['date']['stopping']))) {
					return false;
				}
				$transaction_datetime = array(
					'starting'	=> $input_params['date']['starting'],
					'stopping'	=> $input_params['date']['stopping'],
				);
			break;
		}
        curl_setopt($this->ch, CURLOPT_URL, 'https://m.klikbca.com/accountstmt.do?value(actions)=menu');
        curl_setopt($this->ch, CURLOPT_REFERER, 'https://m.klikbca.com/authentication.do');
        $this->curlexec();
        curl_setopt($this->ch, CURLOPT_URL, 'https://m.klikbca.com/accountstmt.do?value(actions)=acct_stmt');
        curl_setopt($this->ch, CURLOPT_REFERER, 'https://m.klikbca.com/accountstmt.do?value(actions)=menu');
        $this->curlexec();
		$query_params = array(
			'r1=1', 
			'value(D1)=0', 
			'value(startDt)=' . $transaction_datetime['starting']->format('d'),
			'value(startMt)=' . $transaction_datetime['starting']->format('m'),
			'value(startYr)=' . $transaction_datetime['starting']->format('Y'),
			'value(endDt)=' . $transaction_datetime['stopping']->format('d'),
			'value(endMt)=' . $transaction_datetime['stopping']->format('m'),
			'value(endYr)=' . $transaction_datetime['stopping']->format('Y'),
		);
        $params = implode('&', $query_params);
        curl_setopt($this->ch, CURLOPT_URL, 'https://m.klikbca.com/accountstmt.do?value(actions)=acctstmtview');
        curl_setopt($this->ch, CURLOPT_REFERER, 'https://m.klikbca.com/accountstmt.do?value(actions)=acct_stmt');
        curl_setopt($this->ch, CURLOPT_POST, TRUE);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $params);
        $this->curlexec();
		##
		# DEBUG
		##
		
		return $this->curlcontent;
		//exit;
	}
	
	
	
	
	function getBalance() {
        curl_setopt($this->ch, CURLOPT_URL, 'https://m.klikbca.com/accountstmt.do?value(actions)=menu');
        curl_setopt($this->ch, CURLOPT_REFERER, 'https://m.klikbca.com/authentication.do');
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'GET');
        $this->curlexec();
        curl_setopt($this->ch, CURLOPT_URL, 'https://m.klikbca.com/balanceinquiry.do');
        curl_setopt($this->ch, CURLOPT_REFERER, 'https://m.klikbca.com/accountstmt.do?value(actions)=menu');
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'GET');
        $src = $this->curlexec();
		$parse = explode("<td align='right'><font size='1' color='#0000a7'><b>", $src );
		$parse01 = (isset($parse[1]) ? $parse[1] : NULL);
		$balance = array(
			'balance_amount'		=> NULL,
		);
		if ($parse01) {
			$content = explode('</td>', $parse01);
			if (empty($content[0])) {
				return false;
			}
			$balance['balance_amount'] = str_replace(',', '', $content[0]);
			$balance['balance_amount'] = sprintf("%s", $balance['balance_amount']);
			return $balance;
		}
		return false;
    }




    
	
	
	
	
	
	
	
	
	
	//============
	// Utils
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