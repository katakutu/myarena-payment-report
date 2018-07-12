<?php
if (!defined('BASEPATH')) {
	exit("Cannot load script directly");
}
class Lib_bca {
	protected $CI;
	protected $curl_options, $client;
	protected $cookies_path = __DIR__;
	protected $ch = NULL;
	protected $curlcontent = NULL;
	protected $dom_document;
	function __construct() {
		$this->CI = &get_instance();
		$this->CI->load->config('mutasi/base_mutasi');
		$base_mutasi = $this->CI->config->item('base_mutasi');
		$this->curl_options = array('user_agent' => 'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:56.0) Gecko/20100101 Firefox/56.0');
		if (!isset($this->curl_options['host_ip'])) {
			$this->curl_options['host_ip'] = gethostbyname(gethostname());
		}
		$this->client = (isset($base_mutasi['client']) ? $base_mutasi['client'] : array());
		if (isset($this->client['user_ip'])) {
			$this->client['user_ip'] = $this->set_client_ip($this->client['user_ip']);
		}
		if (isset($base_mutasi['cookies']['bca'])) {
			$this->cookies_path = $base_mutasi['cookies']['bca'];
		}
		$this->set_headers();
		$this->add_headers('Content-type', 'application/x-www-form-urlencoded');
		//-- Set ajax
		//$this->add_headers('X-Requested-With', 'XMLHttpRequest');
		$this->cookie_filename = (isset($base_mutasi['cookies_filename']) ? $base_mutasi['cookies_filename'] : 'cookies.txt');
		$this->cookie_filename = (is_string($this->cookie_filename) ? strtolower($this->cookie_filename) : 'cookies.txt');
		$this->set_curl_init($this->create_curl_headers($this->headers, $this->cookie_filename));
		$this->set_dom_document();
	}
	private function set_dom_document() {
		libxml_use_internal_errors(true);
		$this->dom_document = new DOMDocument;
	}
	
	
	
	function curlexec() {
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, TRUE);
        $this->curlcontent = curl_exec($this->ch);
    }
	function set_curl_init($headers = null, $cookie_filename = 'cookies.txt') {
		$this->ch = curl_init();
		curl_setopt($this->ch, CURLOPT_URL, 'https://ibank.klikbca.com/login.jsp');
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
	function login($username, $password) {
		$this->curlexec();
		//curl_close($this->ch);
		$login_params = array(
			'value(actions)'		=> 'login',
			'value(browser_info)'	=> $this->curl_options['user_agent'],
			'value(mobile)'			=> 'false',
			'value(pswd)'			=> $password,
			'value(Submit)'			=> 'LOGIN',
			'value(user_id)'		=> $username,
			'value(user_ip)'		=> $this->curl_options['host_ip'],
		);
		
		curl_setopt($this->ch, CURLOPT_URL, 'https://ibank.klikbca.com/authentication.do');
        curl_setopt($this->ch, CURLOPT_REFERER, 'https://ibank.klikbca.com/login.jsp');
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($login_params));
        curl_setopt($this->ch, CURLOPT_POST, TRUE);
        $this->curlexec();
		curl_setopt($this->ch, CURLOPT_URL, 'https://ibank.klikbca.com/authentication.do?value(actions)=welcome');
		curl_setopt($this->ch, CURLOPT_REFERER, 'https://ibank.klikbca.com/nav_bar/account_information_menu.htm');
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'GET');
		curl_setopt($this->ch, CURLOPT_POST, FALSE);
		$this->curlexec();
		// Chek Login DOM
		$dom_elements = array();
		libxml_use_internal_errors(true);
		$this->dom_document->preserveWhiteSpace = false;
		$this->dom_document->validateOnParse = false;
		if (!empty($this->curlcontent)) {
			$this->dom_document->loadHTML($this->curlcontent);
			$dom_xpath = new DOMXPath($this->dom_document);
			$dom_logins = $dom_xpath->query("//table[@border='0' and @cellpadding='0' and @cellspacing='0' and @width='590']/tr/td[@height='18']/font");
			if (isset($dom_logins->length) && ((int)$dom_logins->length > 0)) {
				for ($i = 0; $i < $dom_logins->length; $i++) {
					$dom_elements[$i]['response'] = $dom_logins->item($i);
					$dom_elements[$i]['string'] = $dom_elements[$i]['response']->nodeValue;
				}
			}
			//----
			if (isset($dom_elements[0]['string'])) {
				if (strpos(strtolower($dom_elements[0]['string']), strtolower('Your last Login was on')) !== FALSE) {
					return $this->curlcontent;
				}
			}
		}
		return false;
    }
	function logout() {
		curl_setopt($this->ch, CURLOPT_URL, 'https://ibank.klikbca.com/authentication.do?value(actions)=logout');
        curl_setopt($this->ch, CURLOPT_REFERER, 'https://ibank.klikbca.com/top.htm');
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->ch, CURLOPT_POST, TRUE);
		$this->curlexec();
        curl_close($this->ch);
		return $this->curlcontent;
    }
	//====================
	// BCA Actions
	function get_informasi_rekening() {
		curl_setopt($this->ch, CURLOPT_URL, 'https://ibank.klikbca.com/balanceinquiry.do');
		curl_setopt($this->ch, CURLOPT_REFERER, 'https://ibank.klikbca.com/nav_bar/account_information_menu.htm');
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($this->ch, CURLOPT_POST, TRUE);
		$this->curlexec();
		$collectData = array(
			'dom'			=> array(
				'items'			=> array(),
			),
		);
		$this->dom_document->preserveWhiteSpace = false;
		$this->dom_document->validateOnParse = false;
		$this->dom_document->loadHTML($this->curlcontent);
		$collectData['dom']['xpath'] = new DOMXPath($this->dom_document);
		$collectData['dom']['rekening'] = $collectData['dom']['xpath']->query("//table[@border='0' and @cellpadding='0' and @cellspacing='0' and @width='590']/tr/td[@width='25%' and @bgcolor='#e0e0e0']/div[@align='center']/font[@face='Verdana']");
		if (isset($collectData['dom']['rekening']->length)) {
			for ($i = 0; $i < $collectData['dom']['rekening']->length; $i++) {
				$collectData['dom']['items'][$i] = array(
					'elements'		=> $collectData['dom']['rekening']->item($i),
				);
			}
		}
		if (count($collectData['dom']['items']) > 0) {
			if (isset($collectData['dom']['items'][0]['elements']->nodeValue)) {
				return sprintf('%s', trim($collectData['dom']['items'][0]['elements']->nodeValue));
			}
		} else {
			return false;
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
		$collectData = array(
			'dom'			=> array(
				'items'					=> array(),
				'item_rekening'			=> array(),
			),
			'dateobject'	=> $this->create_time_zone(ConstantConfig::$timezone),
		);
		/*
		curl_setopt($this->ch, CURLOPT_URL, 'https://ibank.klikbca.com/nav_bar/menu_bar.jsp');
		curl_setopt($this->ch, CURLOPT_REFERER, 'https://ibank.klikbca.com/authentication.do');
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'GET');
		curl_setopt($this->ch, CURLOPT_POST, FALSE);
		$this->curlexec();
		curl_setopt($this->ch, CURLOPT_URL, 'https://ibank.klikbca.com/nav_bar/account_information_menu.htm');
		curl_setopt($this->ch, CURLOPT_REFERER, 'https://ibank.klikbca.com/nav_bar/menu_bar.jsp');
		$this->curlexec();
		*/
        curl_setopt($this->ch, CURLOPT_URL, 'https://ibank.klikbca.com/accountstmt.do?value(actions)=acct_stmt');
        curl_setopt($this->ch, CURLOPT_REFERER, 'https://ibank.klikbca.com/nav_bar/account_information_menu.htm');
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($this->ch, CURLOPT_POST, TRUE);
        $this->curlexec();
		//----
		$collectData['required_params'] = array();
		$collectData['query_params'] = array(
			'value(D1)'						=> '0',
			'value(endDt)'					=> $transaction_datetime['stopping']->format('d'),
			'value(endMt)'					=> $transaction_datetime['stopping']->format('n'),
			'value(endYr)'					=> $transaction_datetime['stopping']->format('Y'),
			'value(fDt)'					=> '',
			'value(r1)'						=> '1',
			'value(startDt)'				=> $transaction_datetime['starting']->format('d'),
			'value(startMt)'				=> $transaction_datetime['starting']->format('n'),
			'value(startYr)'				=> $transaction_datetime['starting']->format('Y'),
			'value(submit1)'				=> 'View Account Statement',
			'value(tDt)'					=> '',
		);
		//================
		if (!empty($this->curlcontent)) {
			curl_setopt($this->ch, CURLOPT_URL, 'https://ibank.klikbca.com/accountstmt.do?value(actions)=acctstmtview');
			curl_setopt($this->ch, CURLOPT_REFERER, 'https://ibank.klikbca.com/acco…mt.do?value(actions)=acct_stmt');
			curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($this->ch, CURLOPT_POST, TRUE);
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($collectData['query_params']));
			$this->curlexec();
			
			// Parse HTML Transactions
			//========================
			$this->dom_document->preserveWhiteSpace = false;
			$this->dom_document->validateOnParse = false;
			$this->dom_document->loadHTML($this->curlcontent);
			$collectData['dom']['xpath'] = new DOMXPath($this->dom_document);
			// Informasi Rekening
			$collectData['dom']['informasi_rekening'] = $collectData['dom']['xpath']->query("//table[@border='0' and @width='90%' and @cellpadding='0' and @cellspacing='0']/tr/td/font[@face='Verdana' and @color='#0000bb']");
			if (isset($collectData['dom']['informasi_rekening']->length)) {
				$collectData['dom']['rekening_items'] = array();
				for ($i = 0; $i < $collectData['dom']['informasi_rekening']->length; $i++) {
					$collectData['dom']['rekening_items'][$i] = array(
						'elements'		=> $collectData['dom']['informasi_rekening']->item($i),
					);
				}
				if (count($collectData['dom']['rekening_items']) > 0) {
					$informasi_rekening['rekening_number'] = (isset($collectData['dom']['rekening_items'][2]['elements']->nodeValue) ? $collectData['dom']['rekening_items'][2]['elements']->nodeValue : '');
					$informasi_rekening['rekening_name'] = (isset($collectData['dom']['rekening_items'][5]['elements']->nodeValue) ? $collectData['dom']['rekening_items'][5]['elements']->nodeValue : '');
					$informasi_rekening['rekening_periode'] = (isset($collectData['dom']['rekening_items'][8]['elements']->nodeValue) ? $collectData['dom']['rekening_items'][8]['elements']->nodeValue : '');
					$informasi_rekening['rekening_currency'] = (isset($collectData['dom']['rekening_items'][11]['elements']->nodeValue) ? $collectData['dom']['rekening_items'][11]['elements']->nodeValue : 'IDR');
					// Unset rekening-items
					unset($collectData['dom']['rekening_items']);
				}
			}
			$collectData['informasi_rekening'] = $informasi_rekening;
			//================== Set Rows
			$collectData['rows'] = array(
				'saldo'			=> array(),
				'tmp_items'		=> array(),
				'items'			=> array(),
			);
			// Get Saldo Awal
			$collectData['dom']['saldo_mutasi_awal'] = $collectData['dom']['xpath']->query("//table[@border='0' and @width='70%' and @bordercolor='#ffffff']/tr[@bgcolor='#e0e0e0']");
			$collectData['dom']['saldo_mutasi_akhir'] = $collectData['dom']['xpath']->query("//table[@border='0' and @width='70%' and @bordercolor='#ffffff']/tr[@bgcolor='#f0f0f0']");
			if (isset($collectData['dom']['saldo_mutasi_awal']->length)) {
				if ((int)$collectData['dom']['saldo_mutasi_awal']->length > 0) {
					$collectData['rows']['saldo']['awal'] = array();
					for ($i = 0; $i < $collectData['dom']['saldo_mutasi_awal']->length; $i++) {
						$collectData['rows']['saldo']['awal'][$i] = array(
							'elements'		=> $collectData['dom']['saldo_mutasi_awal']->item($i),
						);
						$collectData['rows']['saldo']['awal'][$i]['fonts'] = $collectData['rows']['saldo']['awal'][$i]['elements']->getElementsByTagName('font');
						if (isset($collectData['rows']['saldo']['awal'][$i]['fonts']->length)) {
							$collectData['rows']['saldo']['awal'][$i]['attributes'] = array();
							foreach ($collectData['rows']['saldo']['awal'][$i]['fonts'] as $font) {
								$collectData['rows']['saldo']['awal'][$i]['attributes'][] = $font->nodeValue;
							}
						}
					}
				}
			}
			if (isset($collectData['dom']['saldo_mutasi_akhir']->length)) {
				if ((int)$collectData['dom']['saldo_mutasi_akhir']->length > 0) {
					$collectData['rows']['saldo']['akhir'] = array();
					for ($i = 0; $i < $collectData['dom']['saldo_mutasi_akhir']->length; $i++) {
						$collectData['rows']['saldo']['akhir'][$i] = array(
							'elements'		=> $collectData['dom']['saldo_mutasi_akhir']->item($i),
						);
						$collectData['rows']['saldo']['akhir'][$i]['fonts'] = $collectData['rows']['saldo']['akhir'][$i]['elements']->getElementsByTagName('font');
						if (isset($collectData['rows']['saldo']['akhir'][$i]['fonts']->length)) {
							$collectData['rows']['saldo']['akhir'][$i]['attributes'] = array();
							foreach ($collectData['rows']['saldo']['akhir'][$i]['fonts'] as $font) {
								$collectData['rows']['saldo']['akhir'][$i]['attributes'][] = $font->nodeValue;
							}
						}
					}
				}
			}
			// Get Item List Data
			$collectData['dom']['mutasi_tds'] = $collectData['dom']['xpath']->query("//table[@border='1' and @width='100%' and @cellpadding='0' and @cellspacing='0' and @bordercolor='#ffffff']/tr");
			if (isset($collectData['dom']['mutasi_tds']->length)) {
				$collectData['dom']['items'] = array();
				for ($i = 0; $i < $collectData['dom']['mutasi_tds']->length; $i++) {
					$collectData['dom']['items'][$i] = array(
						'elements'		=> $collectData['dom']['mutasi_tds']->item($i),
					);
					$collectData['dom']['items'][$i]['attributes'] = array(
						'tds'		=> $collectData['dom']['items'][$i]['elements']->getElementsByTagName('td'),
					);
					if (isset($collectData['dom']['items'][$i]['attributes']['tds']->length)) {
						$td_i = 0;
						$collectData['dom']['items'][$i]['attributes']['tds_data'] = array();
						foreach ($collectData['dom']['items'][$i]['attributes']['tds'] as $td) {
							$collectData['dom']['items'][$i]['attributes']['tds_data'][$td_i] = array(
								'display_html'		=> $this->dom_document->saveXML($td),
								'display_string'	=> str_replace('&#13;', '', str_replace('<td>', '', str_replace('</td>', '', preg_replace('#(?<=<td)(.*?)(?=>)#i', '\2', $this->dom_document->saveXML($td))))),
								'node'				=> preg_replace("/\s+/", ' ', preg_replace("/\n+/", '', $td->nodeValue)),
								'name'				=> $td->getAttribute('name'),
							);
							$collectData['dom']['items'][$i]['attributes']['tds_data'][$td_i]['display_string'] = str_replace('<div>', '', str_replace('</div>', '', preg_replace('#(?<=<div)(.*?)(?=>)#i', '\2', $collectData['dom']['items'][$i]['attributes']['tds_data'][$td_i]['display_string'])));
							$collectData['dom']['items'][$i]['attributes']['tds_data'][$td_i]['display_string'] = str_replace('<font>', '', str_replace('</font>', '', preg_replace('#(?<=<font)(.*?)(?=>)#i', '\2', $collectData['dom']['items'][$i]['attributes']['tds_data'][$td_i]['display_string'])));
							$td_i++;
						}
					}
				}
			}
			//-- Make mutasi structured
			$collectData['transaction_data'] = array();
			if (count($collectData['dom']['items']) > 0) {
				foreach ($collectData['dom']['items'] as $itemKey => $itemVal) {
					if ($itemKey > 0) {
						if (isset($itemVal['attributes']['tds_data'])) {
							$collectData['transaction_data'][] = $itemVal['attributes']['tds_data'];
						}
					}
				}
			}
			$collectData['rows']['html'] = $this->curlcontent;
			if (count($collectData['transaction_data']) > 0) {
				if (isset($collectData['rows']['saldo']['awal'][0]['attributes'][2])) {
					$saldo_awal = sprintf("%.02f", $collectData['rows']['saldo']['awal'][0]['attributes'][2]);
				} else {
					$saldo_awal = 0;
				}
				$key = 0;
				foreach ($collectData['transaction_data'] as $trans_key => $trans_val) {
					if (is_array($trans_val) && (count($trans_val) > 0)) {
						$collectData['rows']['items'][$key] = array(
							'transaction_description'	=> (isset($trans_val[1]['display_string']) ? $trans_val[1]['display_string'] : ''),
							'transaction_desc'			=> (isset($trans_val[1]['node']) ? trim($trans_val[1]['node']) : ''),
						);
						$collectData['rows']['items'][$key]['transaction_description'] = trim($collectData['rows']['items'][$key]['transaction_description']);
						$collectData['rows']['items'][$key]['transaction_date_string'] = '-';
						//---- Validate fo items mutasi data
						if (isset($trans_val[4]['node'])) {
							if (strtoupper(trim($trans_val[4]['node'])) === strtoupper('DB')) {
								$collectData['rows']['items'][$key]['transaction_payment'] = 'transfer';
								$collectData['rows']['items'][$key]['transaction_code'] = 'DB';
							} else {
								$collectData['rows']['items'][$key]['transaction_payment'] = 'deposit';
								$collectData['rows']['items'][$key]['transaction_code'] = 'CR';
							}
						} else {
							$collectData['rows']['items'][$key]['transaction_payment'] = 'transfer';
							$collectData['rows']['items'][$key]['transaction_code'] = 'DB';
						}
						// Transaction Details
						$collectData['rows']['items'][$key]['transaction_detail_explode'] = explode('<br/>', $collectData['rows']['items'][$key]['transaction_description']);
						switch (count($collectData['rows']['items'][$key]['transaction_detail_explode'])) {
							case 1:
								// Nothing
							break;
							case 2:
							case 3:
								if (strtoupper($collectData['rows']['items'][$key]['transaction_code']) === 'CR') {
									if (strpos($collectData['rows']['items'][$key]['transaction_detail_explode'][1], sprintf("%s/%s", $collectData['dateobject']->format('d'), $collectData['dateobject']->format('m'))) !== FALSE) {
										$collectData['rows']['items'][$key]['transaction_date_string'] = sprintf("%s/%s/%s", $collectData['dateobject']->format('d'), $collectData['dateobject']->format('m'), $collectData['dateobject']->format('Y'));
									} else {
										preg_match('/([0-9]{2}+)\/([0-9]{2}+)/', $collectData['rows']['items'][$key]['transaction_detail_explode'][1], $collectData['rows']['items'][$key]['transaction_date_string_array']);
										if (count($collectData['rows']['items'][$key]['transaction_date_string_array']) > 0) {
											$collectData['rows']['items'][$key]['transaction_date_string'] = sprintf("%s/%s", $collectData['rows']['items'][$key]['transaction_date_string_array'][0], $collectData['dateobject']->format('Y'));
										} else {
											if (isset($trans_val[0]['node'])) {
												$string_dateobject = DateTime::createFromFormat('d/m/Y', sprintf("%s/%s", trim($trans_val[0]['node']), $collectData['dateobject']->format('Y')));
												if ($string_dateobject != FALSE) {
													$collectData['rows']['items'][$key]['transaction_date_string'] = $string_dateobject->format('d/m/Y');
												}
											}
										}
									}
								} else {
									if (strpos($collectData['rows']['items'][$key]['transaction_detail_explode'][1], sprintf("%s%s", $collectData['dateobject']->format('d'), $collectData['dateobject']->format('m'))) !== FALSE) {
										$collectData['rows']['items'][$key]['transaction_date_string'] = sprintf("%s/%s/%s", $collectData['dateobject']->format('d'), $collectData['dateobject']->format('m'), $collectData['dateobject']->format('Y'));
									} else {
										preg_match('/([0-9]{4}+)\//', $collectData['rows']['items'][$key]['transaction_detail_explode'][1], $collectData['rows']['items'][$key]['transaction_date_string_array']);
										if (count($collectData['rows']['items'][$key]['transaction_date_string_array']) > 1) {
											$string_dateobject = DateTime::createFromFormat('dmY', sprintf("%s%s", $collectData['rows']['items'][$key]['transaction_date_string_array'][1], $collectData['dateobject']->format('Y')));
											if ($string_dateobject != FALSE) {
												$collectData['rows']['items'][$key]['transaction_date_string'] = $string_dateobject->format('d/m/Y');
											}
										} else {
											if (isset($trans_val[0]['node'])) {
												$string_dateobject = DateTime::createFromFormat('d/m/Y', sprintf("%s/%s", trim($trans_val[0]['node']), $collectData['dateobject']->format('Y')));
												if ($string_dateobject != FALSE) {
													$collectData['rows']['items'][$key]['transaction_date_string'] = $string_dateobject->format('d/m/Y');
												}
											}
										}
									}
								}
							break;
							case 4:
								if (strtoupper($collectData['rows']['items'][$key]['transaction_code']) === 'CR') {
									if (strpos($collectData['rows']['items'][$key]['transaction_detail_explode'][1], sprintf("%s/%s", $collectData['dateobject']->format('m'), $collectData['dateobject']->format('d'))) !== FALSE) {
										$collectData['rows']['items'][$key]['transaction_date_string'] = sprintf("%s/%s/%s", $collectData['dateobject']->format('d'), $collectData['dateobject']->format('m'), $collectData['dateobject']->format('Y'));
									} else {
										preg_match('/([0-9]{2}+)\/([0-9]{2}+)/', $collectData['rows']['items'][$key]['transaction_detail_explode'][1], $collectData['rows']['items'][$key]['transaction_date_string_array']);
										if (count($collectData['rows']['items'][$key]['transaction_date_string_array']) > 2) {
											$collectData['rows']['items'][$key]['transaction_date_string'] = sprintf("%s/%s/%s", $collectData['rows']['items'][$key]['transaction_date_string_array'][2], $collectData['rows']['items'][$key]['transaction_date_string_array'][1], $collectData['dateobject']->format('Y'));
										}
									}
								} else {
									if (strpos($collectData['rows']['items'][$key]['transaction_detail_explode'][1], sprintf("%s%s", $collectData['dateobject']->format('d'), $collectData['dateobject']->format('m'))) !== FALSE) {
										$collectData['rows']['items'][$key]['transaction_date_string'] = sprintf("%s/%s/%s", $collectData['dateobject']->format('d'), $collectData['dateobject']->format('m'), $collectData['dateobject']->format('Y'));
									} else {
										preg_match('/([0-9]{2}+)\/([0-9]{2}+)/', $collectData['rows']['items'][$key]['transaction_detail_explode'][1], $collectData['rows']['items'][$key]['transaction_date_string_array']);

									}
								}
							break;
							case 5:
							case 6:
								if (strtoupper($collectData['rows']['items'][$key]['transaction_code']) === 'CR') {
									if (strpos($collectData['rows']['items'][$key]['transaction_detail_explode'][1], sprintf("%s/%s", $collectData['dateobject']->format('d'), $collectData['dateobject']->format('m'))) !== FALSE) {
										$collectData['rows']['items'][$key]['transaction_date_string'] = sprintf("%s/%s/%s", $collectData['dateobject']->format('d'), $collectData['dateobject']->format('m'), $collectData['dateobject']->format('Y'));
									} else {
										preg_match('/([0-9]{2}+)\/([0-9]{2}+)/', $collectData['rows']['items'][$key]['transaction_detail_explode'][1], $collectData['rows']['items'][$key]['transaction_date_string_array']);
										if (count($collectData['rows']['items'][$key]['transaction_date_string_array']) > 2) {
											$collectData['rows']['items'][$key]['transaction_date_string'] = sprintf("%s/%s/%s", $collectData['rows']['items'][$key]['transaction_date_string_array'][2], $collectData['rows']['items'][$key]['transaction_date_string_array'][1], $collectData['dateobject']->format('Y'));
										}
									}
								} else {
									if (strpos($collectData['rows']['items'][$key]['transaction_detail_explode'][1], sprintf("%s/%s", $collectData['dateobject']->format('d'), $collectData['dateobject']->format('m'))) !== FALSE) {
										$collectData['rows']['items'][$key]['transaction_date_string'] = sprintf("%s/%s/%s", $collectData['dateobject']->format('d'), $collectData['dateobject']->format('m'), $collectData['dateobject']->format('Y'));
									} else {
										preg_match('/([0-9]{2}+)\/([0-9]{2}+)/', $collectData['rows']['items'][$key]['transaction_detail_explode'][1], $collectData['rows']['items'][$key]['transaction_date_string_array']);
										if (count($collectData['rows']['items'][$key]['transaction_date_string_array']) > 2) {
											$collectData['rows']['items'][$key]['transaction_date_string'] = sprintf("%s/%s/%s", $collectData['rows']['items'][$key]['transaction_date_string_array'][1], $collectData['rows']['items'][$key]['transaction_date_string_array'][2], $collectData['dateobject']->format('Y'));
										}
									}
								}
							break;
							case 7:
							default:
								if (strtoupper($collectData['rows']['items'][$key]['transaction_code']) === 'CR') {
									// Nothing
								} else {
									if (strpos($collectData['rows']['items'][$key]['transaction_detail_explode'][1], sprintf("%s%s", $collectData['dateobject']->format('d'), $collectData['dateobject']->format('m'))) !== FALSE) {
										$collectData['rows']['items'][$key]['transaction_date_string'] = sprintf("%s/%s/%s", $collectData['dateobject']->format('d'), $collectData['dateobject']->format('m'), $collectData['dateobject']->format('Y'));
									} else {
										preg_match('/([0-9]{4}+)\//', $collectData['rows']['items'][$key]['transaction_detail_explode'][1], $collectData['rows']['items'][$key]['transaction_date_string_array']);
										if (count($collectData['rows']['items'][$key]['transaction_date_string_array']) > 1) {
											$string_dateobject = DateTime::createFromFormat('dmY', sprintf("%s%s", $collectData['rows']['items'][$key]['transaction_date_string_array'][1], $collectData['dateobject']->format('Y')));
											if ($string_dateobject != FALSE) {
												$collectData['rows']['items'][$key]['transaction_date_string'] = $string_dateobject->format('d/m/Y');
											}
										}
									}
								}
							break;
						}
						if ($collectData['rows']['items'][$key]['transaction_date_string'] == $collectData['dateobject']->format('d/m/Y')) {
							$collectData['rows']['items'][$key]['transaction_type'] = 'PEND';
						} else {
							$collectData['rows']['items'][$key]['transaction_type'] = '-';
						}
						$collectData['rows']['items'][$key]['transaction_amount_string'] = (isset($trans_val[3]['node']) ? trim($trans_val[3]['node']) : '0');
						$collectData['rows']['items'][$key]['transaction_amount'] = $this->parse_number($collectData['rows']['items'][$key]['transaction_amount_string']);
						# Make saldo
						$saldo_awal += $collectData['rows']['items'][$key]['transaction_amount'];
						$collectData['rows']['items'][$key]['transaction_saldo'] = (isset($trans_val[5]['node']) ? trim($trans_val[5]['node']) : '0');
						$collectData['rows']['items'][$key]['transaction_saldo'] = $this->parse_number($collectData['rows']['items'][$key]['transaction_saldo'], '.');
						// Format date
						try {
							$collectData['rows']['items'][$key]['transaction_date_object'] = DateTime::createFromFormat('d/m/Y', $collectData['rows']['items'][$key]['transaction_date_string']);
						} catch (Exception $ex) {
							throw $ex;
							$collectData['rows']['items'][$key]['transaction_date_object'] = FALSE;
						}
						if ($collectData['rows']['items'][$key]['transaction_date_object'] != FALSE) {
							$collectData['rows']['items'][$key]['transaction_date_format'] = $collectData['rows']['items'][$key]['transaction_date_object']->format('Y-m-d');
							$collectData['rows']['items'][$key]['transaction_date'] = $collectData['rows']['items'][$key]['transaction_date_object']->format('d/m');
							// Transaction Details
							$collectData['rows']['items'][$key]['transaction_detail'] = array(
								$collectData['rows']['items'][$key]['transaction_date_string'],
								sprintf("%s %s", $collectData['rows']['items'][$key]['transaction_date_object']->format('d/m'), $collectData['rows']['items'][$key]['transaction_saldo']),
								trim($collectData['rows']['items'][$key]['transaction_desc']),
								$collectData['rows']['items'][$key]['transaction_code'],
								$collectData['rows']['items'][$key]['transaction_amount_string'],
							);
						} else {
							$collectData['rows']['items'][$key]['transaction_date'] = 'PEND';
							// Transaction Details
							$collectData['rows']['items'][$key]['transaction_detail'] = $collectData['rows']['items'][$key]['transaction_detail_explode'];
						}
						$collectData['rows']['items'][$key]['informasi_rekening'] = $collectData['informasi_rekening'];
					}
					$key += 1;
				}
				return $collectData['rows'];
			}
		} else {
			return false;
		}
		return false;
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



















































