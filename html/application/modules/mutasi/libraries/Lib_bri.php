<?php
if (!defined('BASEPATH')) {
	exit("Cannot load script directly");
}
class Lib_bri {
	protected $CI;
	protected $base_mutasi;
	protected $curl_options, $client;
	protected $cookies_path = __DIR__;
	protected $ch = NULL;
	protected $curlcontent = NULL;
	protected $dom_document;
	const ACCOUNT_REKENING_INT = 1;
	const CAPTCHA_MINIMUM_CHAR = 4;
	private $csrf_token_newib = '';
	private $j_code = '';
	// Captcha purpose
	private $captcha_path_dataset = null;
	private $captcha_path_storage = null;
	private $target_color = '000';
	
	function __construct() {
		$this->CI = &get_instance();
		$this->CI->load->config('mutasi/base_mutasi');
		$base_mutasi = $this->CI->config->item('base_mutasi');
		$this->base_mutasi = $base_mutasi;
		$this->curl_options = (isset($base_mutasi['curl_options']) ? $base_mutasi['curl_options'] : array('user_agent' => 'Mozilla/5.0 (Linux; U; Android 4.1.7; en-us; Sony Ericson) edited by: (imzers@gmail.com)'));
		$this->client = (isset($base_mutasi['client']) ? $base_mutasi['client'] : array());
		if (isset($this->client['user_ip'])) {
			$this->client['user_ip'] = $this->set_client_ip($this->client['user_ip']);
		}
		if (isset($base_mutasi['cookies']['bri'])) {
			$this->cookies_path = $base_mutasi['cookies']['bri'];
		}
		$this->set_headers();
		$this->add_headers('Content-type', 'application/x-www-form-urlencoded');
		// Set Curl Init
		$this->cookie_filename = (isset($base_mutasi['cookies_filename']) ? $base_mutasi['cookies_filename'] : 'cookies.txt');
		$this->cookie_filename = (is_string($this->cookie_filename) ? strtolower($this->cookie_filename) : 'cookies.txt');
		//$this->set_curl_init($this->create_curl_headers($this->headers), $this->cookie_filename);
		$this->set_dom_document();
		// Init Captcha Dataset
		if (isset($this->base_mutasi['caches']['bri'])) {
			$this->set_captcha_path_dataset($this->base_mutasi['caches']['bri'] . DIRECTORY_SEPARATOR . 'dataset');
			$this->set_captcha_path_storage($this->base_mutasi['caches']['bri'] . DIRECTORY_SEPARATOR . 'storage');
		}
		// Exit if empty dataset and storage
		if (isset($this->captcha_path_dataset) && isset($this->captcha_path_storage)) {
			if (!is_dir($this->captcha_path_dataset) || !is_dir($this->captcha_path_storage)) {
				exit("Dataset and storage of cache should be a directory and exists");
			}
		} else {
			exit("Storage and dataset object should be not empty.");
		}
	}
	private function set_dom_document() {
		libxml_use_internal_errors(true);
		$this->dom_document = new DOMDocument;
	}
	private function set_csrf_token_newib($csrf_token_newib) {
		$this->csrf_token_newib = $csrf_token_newib;
		return $this;
	}
	public function get_csrf_token_newib() {
		return $this->csrf_token_newib;
	}
	private function set_j_code($j_code) {
		$this->j_code = $j_code;
		return $this;
	}
	public function get_j_code() {
		return $this->j_code;
	}
	//-- Captcha
	private function set_captcha_path_dataset($captcha_path_dataset) {
		$this->captcha_path_dataset = $captcha_path_dataset;
	}
	public function get_captcha_path_dataset() {
		return $this->captcha_path_dataset;
	}
	private function set_captcha_path_storage($captcha_path_storage) {
		$this->captcha_path_storage = $captcha_path_storage;
	}
	public function get_captcha_path_storage() {
		return $this->captcha_path_storage;
	}
	function curlexec() {
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, TRUE);
        $this->curlcontent = curl_exec($this->ch);
    }
	function set_curl_init($headers = null, $cookie_filename = 'cookies.txt') {
		$this->ch = curl_init();
		curl_setopt($this->ch, CURLOPT_URL, 'https://ib.bri.co.id/ib-bri/Homepage.html');
		curl_setopt($this->ch, CURLOPT_COOKIESESSION, TRUE);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->ch, CURLOPT_VERBOSE, TRUE);
		$cookie_filename = (is_string($cookie_filename) ? trim($cookie_filename) : '');
		$cookie_filename = base_permalink($cookie_filename);
		curl_setopt($this->ch, CURLOPT_COOKIEFILE, ($this->cookies_path . DIRECTORY_SEPARATOR . $cookie_filename . '.log'));
        curl_setopt($this->ch, CURLOPT_COOKIEJAR, ($this->cookies_path . DIRECTORY_SEPARATOR . $cookie_filename . '.log'));
		if (isset($headers)) {
			curl_setopt($this->ch, CURLOPT_HEADER, false);
			curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
		} else {
			curl_setopt($this->ch, CURLOPT_HEADER, false);
		}
		curl_setopt($this->ch, CURLOPT_USERAGENT, $this->curl_options['user_agent']);
		//curl_setopt($this->ch, CURLOPT_FAILONERROR, TRUE);
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($this->ch, CURLOPT_ENCODING, "");
		curl_setopt($this->ch, CURLOPT_AUTOREFERER, TRUE);
	}
	private function get_bri_captcha() {
		$try_again 		= true;
		$captcha_crack 	= '';
		$try = 0;
		do {
			if($try > 10) {
				$try_again = false;
			}
			curl_setopt($this->ch, CURLOPT_URL, 'https://ib.bri.co.id/ib-bri/login/captcha');
			curl_setopt($this->ch, CURLOPT_REFERER, 'https://ib.bri.co.id/ib-bri/Homepage.html');
			curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'GET');
			$this->curlexec();
			if (!empty($this->curlcontent)) {
				$img_storage = ($this->captcha_path_storage . DIRECTORY_SEPARATOR . 'captcha.png');
				$fp = fopen($img_storage, 'w');
				fwrite($fp, $this->curlcontent);
				fclose($fp);
				try {
					$captcha_crack 	= $this->bri_captcha_decoder($img_storage, $this->captcha_path_dataset, self::CAPTCHA_MINIMUM_CHAR, $this->target_color);
				} catch (Exception $ex) {
					throw $ex;
					return FALSE;
				}
				if (strlen($captcha_crack) < self::CAPTCHA_MINIMUM_CHAR) {
					$try_again = true;
					$try++;
				} else {
					$try_again = false;
				}
			} else {
				$try++;
			}
		} while ($try_again == true);
		$this->set_j_code($captcha_crack);
		return $this->j_code;
	}
	private function get_bri_csrf($curlcontent = null) {
		if (!isset($curlcontent)) {
			$curlcontent = $this->curlcontent;
		}
		if ($curlcontent == FALSE) {
			return false; // No curl content executed yet
		}
		$this->dom_document->preserveWhiteSpace = false;
		$this->dom_document->validateOnParse = false;
		$this->dom_document->loadHTML($curlcontent);
		$dom_xpath = new DOMXPath($this->dom_document);
		$dom_csrf = $dom_xpath->query("//input[@type='hidden' and @name='csrf_token_newib']");
		$dom_elements = array();
		if (isset($dom_csrf->length) && ((int)$dom_csrf->length > 0)) {
			for ($i = 0; $i < $dom_csrf->length; $i++) {
				$dom_elements[$i]['element'] = $dom_csrf->item($i);
				$dom_elements[$i]['attributes'] = array(
					'type'		=> $dom_elements[$i]['element']->getAttribute('type'),
					'name'		=> $dom_elements[$i]['element']->getAttribute('name'),
					'value'		=> $dom_elements[$i]['element']->getAttribute('value'),
				);
			}
		}
		if (count($dom_elements) > 0) {
			foreach ($dom_elements as $response) {
				return $response;
				break;
			}
		}
		return false;
	}
	
	
	function login($username, $password) {
		$this->set_curl_init($this->create_curl_headers($this->headers), $username);
		$this->curlexec();
		// Get csrf
		try {
			$csrf = $this->get_bri_csrf($this->curlcontent);
		} catch (Exception $ex) {
			throw $ex;
			$csrf = false;
		}
		if ($csrf != FALSE) {
			if (isset($csrf['attributes']['value'])) {
				$this->set_csrf_token_newib($csrf['attributes']['value']);
			}
		}
		// Get captcha
		try {
			$captcha = $this->get_bri_captcha();
		} catch (Exception $ex) {
			throw $ex;
			$captcha = false;
		}
		//--------------------------
		// Build Login Params
		$login_params = array(
			'csrf_token_newib'		=> (isset($this->csrf_token_newib) ? $this->csrf_token_newib : ''),
			'j_code'				=> (isset($this->j_code) ? $this->j_code : ''),
			'j_language'			=> 'en_EN',
			'j_password'			=> $password,
			'j_plain_password'		=> '',
			'j_plain_username'		=> $username,
			'j_username'			=> $username,
			'preventAutoPass'		=> '',
		);
				
		curl_setopt($this->ch, CURLOPT_URL, 'https://ib.bri.co.id/ib-bri/Homepage.html');
        curl_setopt($this->ch, CURLOPT_REFERER, 'https://ib.bri.co.id/ib-bri/Homepage.html');
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($login_params));
        curl_setopt($this->ch, CURLOPT_POST, TRUE);
        $this->curlexec();
		if (!empty($this->curlcontent)) {
			$dom_elements = array();
			$this->dom_document->preserveWhiteSpace = false;
			$this->dom_document->validateOnParse = false;
			$this->dom_document->loadHTML($this->curlcontent);
			$dom_xpath = new DOMXPath($this->dom_document);
			$dom_account = $dom_xpath->query("//a[@id='myaccounts-fix']");
			if (isset($dom_account->length) && ((int)$dom_account->length > 0)) {
				// Return Login Homepage
				return $this->curlcontent;
			}
		}
		return false;
    }
	function logout() {
		curl_setopt($this->ch, CURLOPT_URL, 'https://ib.bri.co.id/ib-bri/Logout.html');
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'GET');
		$this->curlexec();
		curl_setopt($this->ch, CURLOPT_URL, 'https://ib.bri.co.id/ib-bri/en/logout.htm');
		curl_setopt($this->ch, CURLOPT_REFERER, 'https://ib.bri.co.id/ib-bri/Logout.html');
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'GET');
		$this->curlexec();
        curl_close($this->ch);
		return $this->curlcontent;
    }
	//====================
	// BRI Actions
	function get_informasi_rekening() {
		curl_setopt($this->ch, CURLOPT_URL, 'https://ib.bri.co.id/ib-bri/MyAccount.html');
		curl_setopt($this->ch, CURLOPT_REFERER, 'https://ib.bri.co.id/ib-bri/Homepage.html');
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'GET');
		curl_setopt($this->ch, CURLOPT_POST, FALSE);
		$this->curlexec();
		curl_setopt($this->ch, CURLOPT_URL, 'https://ib.bri.co.id/ib-bri/BalanceInquiry.html');
		curl_setopt($this->ch, CURLOPT_REFERER, 'https://ib.bri.co.id/ib-bri/MyAccount.html');
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'GET');
		$this->curlexec();
		// Get Rekening Number
		$collectData = array(
			'dom'			=> array(
				'items'			=> array(),
			),
		);
		if (empty($this->curlcontent)) {
			return false;
		}
		$this->dom_document->preserveWhiteSpace = false;
		$this->dom_document->validateOnParse = false;
		$this->dom_document->loadHTML($this->curlcontent);
		$collectData['dom']['xpath'] = new DOMXPath($this->dom_document);
		$collectData['dom']['dom_rekening'] = $collectData['dom']['xpath']->query("//table[@id='tabel-saldo']/tbody");
		if (isset($collectData['dom']['dom_rekening']->length) && ((int)$collectData['dom']['dom_rekening']->length > 0)) {
			for ($i = 0; $i < $collectData['dom']['dom_rekening']->length; $i++) {
				$collectData['dom']['items'][$i] = array(
					'elements'		=> $collectData['dom']['dom_rekening']->item($i),
					'node_string'	=> array(),
				);
				if (isset($collectData['dom']['items'][$i]['elements']->nodeValue)) {
					$nodeValue = trim($collectData['dom']['items'][$i]['elements']->nodeValue);
					$collectData['dom']['items'][$i]['node_string'] = preg_split('/$\R?^/m', $nodeValue);
					if (count($collectData['dom']['items'][$i]['node_string']) > 0) {
						foreach ($collectData['dom']['items'][$i]['node_string'] as &$stringVal) {
							$stringVal = trim($stringVal);
						}
					}
				}
			}
		}
		// Return no-rekening
		if (count($collectData['dom']['items']) > 0) {
			if (isset($collectData['dom']['items'][0]['node_string'][0])) {
				return trim(sprintf('%s', $collectData['dom']['items'][0]['node_string'][0]));
			}
		} else {
			return false;
		}
	}
	function get_mutasi_transactions($transaction_datetime = array(), $rekening_number = null) {
		if (!isset($rekening_number)) {
			$rekening_number = '';
		}
		$informasi_rekening = array(
			'rekening_number'			=> $rekening_number,
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
		// Get Rekening Number
		$collectData = array(
			'dom'			=> array(
				'rekening'			=> array(),
				'tmp_items'			=> array(),
				'items'				=> array(),
			),
		);
		if (empty($this->curlcontent)) {
			return false;
		}
		//---------------------------------------
		// Set query params
		//---------------------------------------
		$collectData['query_params'] = array();
		//---------------------------------------
		// Set account rekening
		$collectData['query_params']['ACCOUNT_NO'] = sprintf("%s", $informasi_rekening['rekening_number']);
		$collectData['query_params']['ACCOUNT_NO'] = trim($collectData['query_params']['ACCOUNT_NO']);
		$collectData['query_params']['csrf_token_newib'] = $this->get_csrf_token_newib();
		// Set Date Interval
		$collectData['query_params']['DDAY1'] = $transaction_datetime['starting']->format('d');
		$collectData['query_params']['DDAY2'] = $transaction_datetime['stopping']->format('d');
		$collectData['query_params']['DMON1'] = $transaction_datetime['starting']->format('m');
		$collectData['query_params']['DMON2'] = $transaction_datetime['stopping']->format('m');
		$collectData['query_params']['DYEAR1'] = $transaction_datetime['starting']->format('Y');
		$collectData['query_params']['DYEAR2'] = $transaction_datetime['stopping']->format('Y');
		$collectData['query_params']['FROM_DATE'] = $transaction_datetime['starting']->format('Y-m-d');
		$collectData['query_params']['TO_DATE'] = $transaction_datetime['stopping']->format('Y-m-d');
		// Set constant
		$collectData['query_params']['VIEW_TYPE'] = '2'; // Tampilkan
		$collectData['query_params']['submitButton'] = 'Tampilkan';
		$collectData['query_params']['download'] = '';
		// Set current date query
		$collectData['query_params']['MONTH'] = $transaction_datetime['stopping']->format('m');
		$collectData['query_params']['YEAR'] = $transaction_datetime['stopping']->format('Y');
		//------------------------------------------
		$collectData['return'] = array();
		curl_setopt($this->ch, CURLOPT_URL, 'https://ib.bri.co.id/ib-bri/MyAccount.html');
		curl_setopt($this->ch, CURLOPT_REFERER, 'https://ib.bri.co.id/ib-bri/Homepage.html');
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'GET');
		$this->curlexec();
		curl_setopt($this->ch, CURLOPT_URL, 'https://ib.bri.co.id/ib-bri/Br11600d.html');
		curl_setopt($this->ch, CURLOPT_REFERER, 'https://ib.bri.co.id/ib-bri/MyAccount.html');
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($this->ch, CURLOPT_POST, TRUE);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($collectData['query_params']));
		curl_setopt($this->ch, CURLOPT_HEADER, FALSE);
		$this->curlexec();
		if (empty($this->curlcontent)) {
			return false;
		}
		$collectData['return']['mutasi'] = $this->curlcontent;
		$collectData['return']['getinfo'] = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
		curl_setopt($this->ch, CURLOPT_URL, 'https://ib.bri.co.id/ib-bri/Logout.html');
        curl_setopt($this->ch, CURLOPT_REFERER, 'https://ib.bri.co.id/ib-bri/Br11600d.html');
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'GET');
		curl_setopt($this->ch, CURLOPT_POST, FALSE);
		curl_setopt($this->ch, CURLOPT_HEADER, FALSE);
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($this->ch, CURLOPT_AUTOREFERER, TRUE);
		$this->curlexec();
		//=====================================================================================================
		$collectData['dom']['dom_elements'] = array();
		$this->dom_document->preserveWhiteSpace = false;
		$this->dom_document->validateOnParse = false;
		$this->dom_document->loadHTML($collectData['return']['mutasi']);
		$collectData['dom']['xpath'] = new DOMXPath($this->dom_document);
		$collectData['dom']['dom_rekening'] = $collectData['dom']['xpath']->query("//div[@id='divToPrint']/div/table");
		$collectData['dom']['dom_mutasi'] = $collectData['dom']['xpath']->query("//div[@id='divToPrint']/div/div[@class='rekkor-box']/table");
		if (isset($collectData['dom']['dom_rekening']->length) && ((int)$collectData['dom']['dom_rekening']->length > 0)) {
			for ($i = 0; $i < $collectData['dom']['dom_rekening']->length; $i++) {
				// Set dom_elements
				$collectData['dom']['dom_elements'][$i] = array(
					'item_rekening'			=> $collectData['dom']['dom_rekening']->item($i),
					'attributes'			=> array(),
					'informasi_rekening'	=> array(),
				);
				if (isset($collectData['dom']['dom_elements'][$i]['item_rekening']->childNodes)) {
					$collectData['dom']['dom_elements'][$i]['attributes']['trs'] = $collectData['dom']['dom_elements'][$i]['item_rekening']->getElementsByTagName('tr');
					if (isset($collectData['dom']['dom_elements'][$i]['attributes']['trs']->length)) {
						if ($collectData['dom']['dom_elements'][$i]['attributes']['trs']->length > 0) {
							$rek_i = 0;
							foreach ($collectData['dom']['dom_elements'][$i]['attributes']['trs'] as $tr) {
								$collectData['dom']['dom_elements'][$i]['informasi_rekening'][$rek_i] = $tr->getElementsByTagName('td');
								$rek_i++;
							}
						}
					}
				}
			}
		}
		if (count($collectData['dom']['dom_elements']) > 0) {
			$collectData['dom']['informasi_rekening'] = array();
			if (isset($collectData['dom']['dom_elements'][0]['informasi_rekening'])) {
				if (is_array($collectData['dom']['dom_elements'][0]['informasi_rekening']) && (count($collectData['dom']['dom_elements'][0]['informasi_rekening']) > 0)) {
					foreach ($collectData['dom']['dom_elements'][0]['informasi_rekening'] as $inforek) {
						if ($inforek->length > 1) {
							$collectData['dom']['informasi_rekening'][] = trim($inforek->item(1)->nodeValue);
						}
					}
				}
			}
		}
		$informasi_rekening['rekening_number'] = (isset($collectData['dom']['informasi_rekening'][1]) ? trim($collectData['dom']['informasi_rekening'][1]) : '');
		$informasi_rekening['rekening_number'] = preg_replace('/\s+/', '', $informasi_rekening['rekening_number']);
		$informasi_rekening['rekening_number'] = preg_replace("/[^0-9]/s", "", $informasi_rekening['rekening_number']);
		$informasi_rekening['rekening_number'] = sprintf("%s", $informasi_rekening['rekening_number']);
		$informasi_rekening['rekening_name'] = (isset($collectData['dom']['informasi_rekening'][0]) ? $collectData['dom']['informasi_rekening'][0] : '');
		$informasi_rekening['rekening_periode'] = (isset($collectData['dom']['informasi_rekening'][3]) ? trim($collectData['dom']['informasi_rekening'][3]) : '');
		$informasi_rekening['rekening_currency'] = (isset($collectData['dom']['informasi_rekening'][2]) ? $collectData['dom']['informasi_rekening'][2] : 'IDR');
		$collectData['informasi_rekening'] = $informasi_rekening;
		// Parse HTML Transactions
		//=======================================================
		$collectData['dom']['item_tables'] = array();
		if (isset($collectData['dom']['dom_mutasi']->length)) {
			for ($i = 0; $i < $collectData['dom']['dom_mutasi']->length; $i++) {
				$collectData['dom']['item_tables'][$i] = array(
					'elements'		=> $collectData['dom']['dom_mutasi']->item($i),
				);
				$collectData['dom']['item_tables'][$i]['attributes'] = array(
					'name'		=> $collectData['dom']['item_tables'][$i]['elements']->childNodes,
					'trs'		=> $collectData['dom']['item_tables'][$i]['elements']->getElementsByTagName('tr'),
				);
				if (isset($collectData['dom']['item_tables'][$i]['attributes']['trs']->length)) {
					$tr_i = 0;
					$collectData['dom']['item_tables'][$i]['attributes']['trs_data'] = array();
					foreach ($collectData['dom']['item_tables'][$i]['attributes']['trs'] as $tr) {
						$collectData['dom']['item_tables'][$i]['attributes']['trs_data'][$tr_i] = array(
							'tds'				=> $tr->getElementsByTagName('td'),
							'html'				=> $this->dom_document->saveXML($tr),
							'node_string'		=> preg_replace("/\s+/", ' ', preg_replace("/\n+/", '', $tr->nodeValue)),
						);
						$tr_i++;
					}
				}
			}
		}
		if (count($collectData['dom']['item_tables']) > 0) {
			$item_key = 0;
			foreach ($collectData['dom']['item_tables'] as $item_table) {
				if (isset($item_table['attributes']['trs_data'])) {
					if (is_array($item_table['attributes']['trs_data']) && (count($item_table['attributes']['trs_data']) > 0)) {
						foreach ($item_table['attributes']['trs_data'] as $trs_data) {
							$collectData['dom']['items'][] = $trs_data;
						}
					}
				}
				$item_key += 1;
			}
		}
		//-- Make mutasi structured
		$collectData['transaction_data'] = array();
		if (count($collectData['dom']['items']) > 0) {
			$last_three_items = (count($collectData['dom']['items']) - 3);
			$indexing_loop = 0;
			foreach ($collectData['dom']['items'] as $itemVal) {
				if (isset($itemVal['tds']->length)) {
					if ($indexing_loop < $last_three_items) {
						if ((int)$itemVal['tds']->length > 0) {
							$collectData['transaction_data'][] = array(
								'dom'			=> $itemVal['tds'],
								'string'		=> $itemVal['node_string'],
							);
						}
					}
				}
				$indexing_loop++;
			}
		}
		//========= SET ROWS ITEMS ==========
		$collectData['rows'] = array(
			'tmp_items'	=> array(),
			'items'		=> array(),
		);
		if (count($collectData['transaction_data']) > 0) {
			$saldo_awal = 0;
			$saldo_aktual = $saldo_awal;
			$key = 0;
			foreach ($collectData['transaction_data'] as $trans_key => &$trans_val) {
				$trans_val['tds'] = array();
				if (isset($trans_val['dom']->length)) {
					if ($trans_val['dom']->length > 0) {
						$trans_val['tds']['items'] = array();
						foreach ($trans_val['dom'] as $node) {
							$trans_val['tds']['items'][] = $node->nodeValue;
						}
					}
				}
				//============================== Make structured
				if ((int)$trans_key > 0) {
					$collectData['rows']['tmp_items'][$key] = array(
						'transaction_date_string'	=> (isset($trans_val['tds']['items'][0]) ? $trans_val['tds']['items'][0] : ''),
						'transaction_type'			=> 'PEND',
						'transaction_description'	=> (isset($trans_val['string']) ? $trans_val['string'] : ''),
						'transaction_desc'			=> (isset($trans_val['tds']['items'][1]) ? trim($trans_val['tds']['items'][1]) : ''),
						'transaction_saldo_string'	=> (isset($trans_val['tds']['items'][4]) ? $trans_val['tds']['items'][4] : '0'),
					);
					$collectData['rows']['tmp_items'][$key]['debit_amount'] = (isset($trans_val['tds']['items'][2]) ? $this->parse_number($trans_val['tds']['items'][2], ',') : 0);
					$collectData['rows']['tmp_items'][$key]['credit_amount'] = (isset($trans_val['tds']['items'][3]) ? $this->parse_number($trans_val['tds']['items'][3], ',') : 0);
					// Amount debet or credit
					if ($collectData['rows']['tmp_items'][$key]['credit_amount'] >= $collectData['rows']['tmp_items'][$key]['debit_amount']) {
						$collectData['rows']['tmp_items'][$key]['transaction_payment'] = 'deposit';
						$collectData['rows']['tmp_items'][$key]['transaction_code'] = 'CR';
						$collectData['rows']['tmp_items'][$key]['transaction_amount_string'] = (isset($trans_val['tds']['items'][3]) ? $trans_val['tds']['items'][3] : '');
						$collectData['rows']['tmp_items'][$key]['transaction_amount'] = $collectData['rows']['tmp_items'][$key]['credit_amount'];
					} else {
						$collectData['rows']['tmp_items'][$key]['transaction_payment'] = 'transfer';
						$collectData['rows']['tmp_items'][$key]['transaction_code'] = 'DB';
						$collectData['rows']['tmp_items'][$key]['transaction_amount_string'] = (isset($trans_val['tds']['items'][2]) ? $trans_val['tds']['items'][2] : '');
						$collectData['rows']['tmp_items'][$key]['transaction_amount'] = $collectData['rows']['tmp_items'][$key]['debit_amount'];
					}
					// Make saldo
					$collectData['rows']['tmp_items'][$key]['transaction_saldo_tmp'] = $this->parse_number($collectData['rows']['tmp_items'][$key]['transaction_saldo_string'], ',');
					$saldo_awal += $collectData['rows']['tmp_items'][$key]['transaction_amount'];
					$collectData['rows']['tmp_items'][$key]['transaction_saldo'] = $saldo_awal;
					# Saldo Aktual
					$collectData['rows']['tmp_items'][$key]['actual_rekening_saldo'] = $collectData['rows']['tmp_items'][$key]['transaction_saldo_tmp'];
					/*
					if (strtoupper($collectData['rows']['tmp_items'][$key]['transaction_code']) === strtoupper('CR')) {
						$saldo_aktual += $collectData['rows']['tmp_items'][$key]['transaction_amount'];
					} else {
						$saldo_aktual -= $collectData['rows']['tmp_items'][$key]['transaction_amount'];
					}
					$collectData['rows']['tmp_items'][$key]['actual_rekening_saldo'] = $saldo_aktual;
					*/
					// Format date
					try {
						$collectData['rows']['tmp_items'][$key]['transaction_date_object'] = DateTime::createFromFormat('d/m/y', $collectData['rows']['tmp_items'][$key]['transaction_date_string']);
					} catch (Exception $ex) {
						throw $ex;
						$collectData['rows']['tmp_items'][$key]['transaction_date_object'] = FALSE;
					}
					//----
					if ($collectData['rows']['tmp_items'][$key]['transaction_date_object'] != FALSE) {
						$collectData['rows']['tmp_items'][$key]['transaction_date_format'] = $collectData['rows']['tmp_items'][$key]['transaction_date_object']->format('Y-m-d');
						$collectData['rows']['tmp_items'][$key]['transaction_date'] = $collectData['rows']['tmp_items'][$key]['transaction_date_object']->format('d/m');
						// Transaction Details
						$collectData['rows']['tmp_items'][$key]['transaction_detail'] = array(
							$collectData['rows']['tmp_items'][$key]['transaction_date_string'],
							sprintf("%s %s", $collectData['rows']['tmp_items'][$key]['transaction_date_string'], $collectData['rows']['tmp_items'][$key]['transaction_saldo_string']),
							$collectData['rows']['tmp_items'][$key]['transaction_desc'],
							$collectData['rows']['tmp_items'][$key]['transaction_code'],
							$collectData['rows']['tmp_items'][$key]['transaction_amount_string'],
						);
					} else {
						$collectData['rows']['tmp_items'][$key]['transaction_date'] = 'PEND';
						$collectData['rows']['tmp_items'][$key]['transaction_detail'] = array();
					}
					$collectData['rows']['tmp_items'][$key]['informasi_rekening'] = $informasi_rekening;
				}
				$key += 1;
			}
			if (count($collectData['rows']['tmp_items']) > 0) {
				// Push to items
				foreach ($collectData['rows']['tmp_items'] as $rowval) {
					array_push($collectData['rows']['items'], $rowval);
				}
			}
			return $collectData['rows'];
		}
		//-----------------------------------------------------------------------------------
		return false;
	}
	
	
	//---------------------------------------------------------------------------------------------------
	// BRI Captcha
	//---------------------------------------------------------------------------------------------------
	function bri_captcha_decoder($captcha_path, $sample_path, $str_num = 1, $target_color = '') {
		if (!isset($target_color)) {
			$target_color = $this->target_color;
		}
		//Get Captcha Sample Info
		$db_text = ($sample_path . DIRECTORY_SEPARATOR . 'dataset.json');
		if (file_exists($db_text)) {
			$db_file 		= fopen($db_text, 'r');
			$captcha_lib 	= fread($db_file, filesize($db_text));
			$captcha_crack 	= unserialize($captcha_lib);
		} else {
			$list_sample = scandir($sample_path);
			$index = 0;
			foreach($list_sample as $key => $c) {
				$str_explode	= explode('.', $c);
				$ext 			= (isset($str_explode[1]) ? $str_explode[1] : '');
				if ($c == '.' || $c == '..') {
					continue;
				} elseif ($ext == 'json') {
					continue;
				} else {
					exit("There is no json files");
				}
				$captcha_sample_path 	= ($sample_path . DIRECTORY_SEPARATOR . $c);
				$captcha_crack[$index] 	= $this->get_sample_captcha_info($captcha_sample_path, $target_color);
				$index++;			
			}				
			$db_file = fopen($db_text, 'w');
			fwrite($db_file, serialize($captcha_crack));
		}
		fclose($db_file);
		//Get Captcha to cracked
		$captcha_get 	= imagecreatefrompng($captcha_path);
		$captcha_size	= getimagesize($captcha_path);		
		$captcha_width	= $captcha_size[0];
		$captcha_height	= $captcha_size[1];
		//Run as captcha width
		$result = '';
		$string = 1;
		for ($cw = 0; $cw < $captcha_width; $cw++) {
			//Run as captcha height
			for ($ch = 0; $ch < $captcha_height; $ch++) {
				$xxx = 0;
				//Run Sample and get per pixel
				foreach($captcha_crack as $key => $c) {
					$target_width 	= $c['width'] + $cw;
					$target_height	= $c['height'] + $ch;
					if($target_width > $captcha_width) {
						continue;						
					}
					if($target_height > $captcha_height) {
						continue;
					}

					$try_captcha 	= array();
					$cor_x = 0;
					for($x = $cw; $x < $target_width; $x++) {
						$cor_y = 0;
						for($y = $ch; $y < $target_height; $y++) {
							$rgb 	= @imagecolorat($captcha_get, $x, $y);
							$colors = @imagecolorsforindex($captcha_get, $rgb);	
							if(!empty($colors)) {
								$colors_res = $colors['red'] . $colors['green'] . $colors['blue'];
								if (!empty($target_color)) {
									if ($target_color != $colors_res) {
										$cor_y++;						
										continue;
									}
								}
								$try_captcha[] = array(
									'color'	=> $colors_res,
									'x'		=> $cor_x,
									'y'		=> $cor_y,
									'r_x'	=> $x,
									'r_y'	=> $y,
								); 
							}	
							$cor_y++;						
						}
						$cor_x++;
					}
					if (count($try_captcha) > 0) {
						$coordinate_captcha_count 	= count($try_captcha);
						$coordinate_sample_count	= count($c['color_coordinat']);
						$found = 0;
						if($coordinate_sample_count == $coordinate_captcha_count) {
							foreach ($try_captcha as $kex => $x) {
								if (($x['color'] == $c['color_coordinat'][$kex]['color']) && ($x['x'] == $c['color_coordinat'][$kex]['x']) && ($x['y'] == $c['color_coordinat'][$kex]['y'])) {
									$found += 1;
								}
							}
						}
						if($found == $coordinate_sample_count) {
							$result .= $c['string'];
							$xxx 	= 1;
							$string++;
							break;
						}
					}
					if($xxx == 1) {
						$cw 	+= $c['width'];
						break;
					}
				}
				if (strlen($result) == $str_num) {
					break;
				}
			}
			if(count($result) == $str_num) {
				break;
			}
		}
		return $result;
	}
	private function get_sample_captcha_info($file, $target_color = null) {
		if (!isset($target_color)) {
			$target_color = $this->target_color;
		}
		$c = basename($file);
		$str_explode = explode('.', $c);
		$captcha_sample_get 	= imagecreatefrompng($file);
		$captcha_sample_size	= getimagesize($file);		
		$captcha_sample_width	= $captcha_sample_size[0];
		$captcha_sample_height	= $captcha_sample_size[1];
		$no 	= $str_explode[0];
		if(strpos($no, '_') > 0) {
			$str_explode = explode('_', $no);
			$no = $no[0];
		}
		$captcha_crack['string']			= $no;
		$captcha_crack['width']				= $captcha_sample_width;
		$captcha_crack['height']			= $captcha_sample_height;
		$captcha_crack['color_coordinat']	= array();
		for($sw = 0; $sw < $captcha_sample_width; $sw++) {
			$i = 0;
			for($sh = 0; $sh < $captcha_sample_height; $sh++) {
				$rgb 	= @imagecolorat($captcha_sample_get, $sw, $sh);
				$colors = @imagecolorsforindex($captcha_sample_get, $rgb);	
				if(!empty($colors)) {
					$colors_res = $colors['red'] . $colors['green'] . $colors['blue'];
					if (!empty($target_color)) {
						if($target_color != $colors_res) {
							continue;
						}
					}
					$captcha_crack['color_coordinat'][] = array(
						'color'	=> $colors_res,
						'x'		=> $sw,
						'y'		=> $sh,
					); 
				}
			}
		}
		return $captcha_crack;
	}
	//===========================================================================
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
	private function parse_number($number, $dec_point = null) {
		if (empty($dec_point)) {
			$locale = localeconv();
			$dec_point = $locale['decimal_point'];
		}
		return floatval(str_replace($dec_point, '.', preg_replace('/[^\d' . preg_quote($dec_point).']/', '', $number)));
	}
}



















































