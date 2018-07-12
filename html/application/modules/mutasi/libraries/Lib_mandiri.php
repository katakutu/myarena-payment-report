<?php
if (!defined('BASEPATH')) {
	exit("Cannot load script directly");
}
class Lib_mandiri {
	protected $CI;
	protected $curl_options, $client;
	protected $cookies_path = __DIR__;
	protected $ch = NULL;
	protected $curlcontent = NULL;
	protected $dom_document;
	const ACCOUNT_REKENING_INT = 1;
	function __construct() {
		$this->CI = &get_instance();
		$this->CI->load->config('mutasi/base_mutasi');
		$base_mutasi = $this->CI->config->item('base_mutasi');
		$this->curl_options = (isset($base_mutasi['curl_options']) ? $base_mutasi['curl_options'] : array('user_agent' => 'Mozilla/5.0 (Linux; U; Android 4.1.7; en-us; Sony Ericson) edited by: (imzers@gmail.com)'));
		$this->client = (isset($base_mutasi['client']) ? $base_mutasi['client'] : array());
		if (isset($this->client['user_ip'])) {
			$this->client['user_ip'] = $this->set_client_ip($this->client['user_ip']);
		}
		if (isset($base_mutasi['cookies']['mandiri'])) {
			$this->cookies_path = $base_mutasi['cookies']['mandiri'];
		}
		$this->set_headers();
		$this->add_headers('Content-type', 'application/x-www-form-urlencoded');
		//-- Set ajax
		//$this->add_headers('X-Requested-With', 'XMLHttpRequest');
		// Set Curl Init
		$this->cookie_filename = (isset($base_mutasi['cookies_filename']) ? $base_mutasi['cookies_filename'] : 'cookies.txt');
		$this->cookie_filename = (is_string($this->cookie_filename) ? strtolower($this->cookie_filename) : 'cookies.txt');
		//$this->set_curl_init($this->create_curl_headers($this->headers), $this->cookie_filename);
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
		curl_setopt($this->ch, CURLOPT_URL, 'https://ib.bankmandiri.co.id/retail/Login.do?action=form&lang=in_ID');
		curl_setopt($this->ch, CURLOPT_COOKIESESSION, TRUE);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->ch, CURLOPT_VERBOSE, TRUE);
		curl_setopt($this->ch, CURLOPT_COOKIELIST, TRUE);
		curl_setopt($this->ch, CURLOPT_COOKIEFILE, ($this->cookies_path . DIRECTORY_SEPARATOR . $cookie_filename . '.log'));
        curl_setopt($this->ch, CURLOPT_COOKIEJAR, ($this->cookies_path . DIRECTORY_SEPARATOR . $cookie_filename . '.log'));
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
		$this->set_curl_init($this->create_curl_headers($this->headers), $username);
		$this->curlexec();
		//curl_close($this->ch);
		$login_params = array(
			'userID'			=> $username,
			'password'			=> $password,
			'image.x'			=> 69,
			'image.y'			=> 13,
			'action'			=> 'result',
		);
		curl_setopt($this->ch, CURLOPT_URL, 'https://ib.bankmandiri.co.id/retail/Login.do');
        curl_setopt($this->ch, CURLOPT_REFERER, 'https://ib.bankmandiri.co.id/retail/Login.do?action=form&lang=in_ID');
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($login_params));
        curl_setopt($this->ch, CURLOPT_POST, TRUE);
        $this->curlexec();
		$dom_elements = array();
		libxml_use_internal_errors(true);
		$this->dom_document->preserveWhiteSpace = false;
		$this->dom_document->validateOnParse = false;
		if (!empty($this->curlcontent)) {
			$this->dom_document->loadHTML($this->curlcontent);
			$dom_xpath = new DOMXPath($this->dom_document);
			$dom_redirects = $dom_xpath->query("//frame[@name='CONTENT']");
			if (isset($dom_redirects->length) && ((int)$dom_redirects->length > 0)) {
				for ($i = 0; $i < $dom_redirects->length; $i++) {
					$dom_elements[$i]['response'] = $dom_redirects->item($i);
					$dom_elements[$i]['attributes'] = array(
						'src'		=> $dom_elements[$i]['response']->getAttribute('src'),
						'name'		=> $dom_elements[$i]['response']->getAttribute('name'),
					);
				}
			}
			//----
			if (isset($dom_elements[0]['attributes']['src'])) {
				if (trim($dom_elements[0]['attributes']['src']) === '/retail/Welcome.do?action=result') {
					curl_setopt($this->ch, CURLOPT_URL, 'https://ib.bankmandiri.co.id/retail/Welcome.do?action=result');
					curl_setopt($this->ch, CURLOPT_REFERER, 'https://ib.bankmandiri.co.id/retail/Redirect.do?action=forward');
					curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'GET');
					curl_setopt($this->ch, CURLOPT_POST, FALSE);
					$this->curlexec();
				}
			} else {
				return false;
			}
			return $this->curlcontent;
		}
		return false;
    }
	function logout() {
		curl_setopt($this->ch, CURLOPT_URL, 'https://ib.bankmandiri.co.id/retail/Logout.do?action=result');
        curl_setopt($this->ch, CURLOPT_REFERER, 'https://ib.bankmandiri.co.id/retail/Welcome.do?action=result');
		$this->curlexec();
        curl_close($this->ch);
		return $this->curlcontent;
    }
	//====================
	// Mandiri Actions
	function get_informasi_rekening() {
		curl_setopt($this->ch, CURLOPT_URL, 'https://ib.bankmandiri.co.id/retail/AccountList.do?action=acclist');
        curl_setopt($this->ch, CURLOPT_REFERER, 'https://ib.bankmandiri.co.id/retail/Welcome.do?action=result');
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'GET');
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
		$collectData['dom']['tds'] = $collectData['dom']['xpath']->query("//tr/td[@align='center']");
		if (isset($collectData['dom']['tds']->length)) {
			for ($i = 0; $i < $collectData['dom']['tds']->length; $i++) {
				$collectData['dom']['items'][$i] = array(
					'elements'		=> $collectData['dom']['tds']->item($i),
				);
			}
		}
		if (count($collectData['dom']['items']) > 0) {
			if (isset($collectData['dom']['items'][4]['elements']->nodeValue)) {
				return sprintf('%s', $collectData['dom']['items'][4]['elements']->nodeValue);
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
        curl_setopt($this->ch, CURLOPT_URL, 'https://ib.bankmandiri.co.id/retail/TrxHistoryInq.do?action=form');
        curl_setopt($this->ch, CURLOPT_REFERER, 'https://ib.bankmandiri.co.id/retail/Welcome.do?action=result');
        $this->curlexec();
		$collectData = array(
			'dom'			=> array(
				'items'				=> array(),
			),
		);
		//================
		$this->dom_document->preserveWhiteSpace = false;
		$this->dom_document->validateOnParse = false;
		if (!empty($this->curlcontent)) {
			$this->dom_document->loadHTML($this->curlcontent);
			$collectData['dom']['xpath'] = new DOMXPath($this->dom_document);
			$collectData['dom']['selects'] = $collectData['dom']['xpath']->query("//table/tr/td/select");
			if (isset($collectData['dom']['selects']->length)) {
				for ($i = 0; $i < $collectData['dom']['selects']->length; $i++) {
					$collectData['dom']['items'][$i] = array(
						'elements'		=> $collectData['dom']['selects']->item($i),
					);
					$collectData['dom']['items'][$i]['attributes'] = array(
						'name'		=> $collectData['dom']['items'][$i]['elements']->getAttribute('name'),
						'options'	=> $collectData['dom']['items'][$i]['elements']->getElementsByTagName('option'),
					);
					if (isset($collectData['dom']['items'][$i]['attributes']['options']->length)) {
						$option_i = 0;
						$collectData['dom']['items'][$i]['attributes']['option_values'] = array();
						foreach ($collectData['dom']['items'][$i]['attributes']['options'] as $option) {
							$collectData['dom']['items'][$i]['attributes']['option_values'][$option_i] = array(
								'display'			=> $option->nodeValue,
								'value'				=> $option->getAttribute('value'),
								'selected'			=> $option->getAttribute('selected'),
							);
							$option_i++;
						}
					}
					unset($collectData['dom']['items'][$i]['elements']);
				}
			}
			//----
			$collectData['required_params'] = array();
			$collectData['query_params'] = array();
			//----
			if (count($collectData['dom']['items']) > 0) {
				foreach ($collectData['dom']['items'] as $item) {
					$item_key = (isset($item['attributes']['name']) ? $item['attributes']['name'] : '');
					$item_key = sprintf("%s", $item_key);
					if (strlen($item_key) > 0) {
						$collectData['required_params'][] = $item_key;
						if ($item_key === 'fromAccountID') {
							$collectData['query_params'][$item_key] = (isset($item['attributes']['option_values'][self::ACCOUNT_REKENING_INT]['value']) ? $item['attributes']['option_values'][self::ACCOUNT_REKENING_INT]['value'] : '');
						} else {
							$collectData['query_params'][$item_key] = '';
							if (is_array($item['attributes']['option_values']) && (count($item['attributes']['option_values']) > 0)) {
								foreach ($item['attributes']['option_values'] as $option) {
									if (isset($option['selected'])) {
										if (strlen($option['selected']) > 0) {
											$collectData['query_params'][$item_key] .= (isset($option['value']) ? $option['value'] : '');
										}
									}
								}
							}
						}
					}
				}
			}
			if (!isset($collectData['query_params']['action'])) {
				$collectData['query_params']['action'] = 'result';
			}
			if (!isset($collectData['query_params']['searchType'])) {
				$collectData['query_params']['searchType'] = 'R';
			}
			if (isset($collectData['query_params']['lastTransaction'])) {
				unset($collectData['query_params']['lastTransaction']);
			}
			//----
			$collectData['query_params']['sortType'] = 'Date';
			$collectData['query_params']['orderBy'] = 'ASC';
			$collectData['query_params']['fromDay'] = $transaction_datetime['starting']->format('d');
			$collectData['query_params']['fromMonth'] = $transaction_datetime['starting']->format('n');
			$collectData['query_params']['fromYear'] = $transaction_datetime['starting']->format('Y');
			$collectData['query_params']['toDay'] = $transaction_datetime['stopping']->format('d');
			$collectData['query_params']['toMonth'] = $transaction_datetime['stopping']->format('n');
			$collectData['query_params']['toYear'] = $transaction_datetime['stopping']->format('Y');
			//----
			curl_setopt($this->ch, CURLOPT_URL, 'https://ib.bankmandiri.co.id/retail/TrxHistoryInq.do');
			curl_setopt($this->ch, CURLOPT_REFERER, 'https://ib.bankmandiri.co.id/retail/TrxHistoryInq.do?action=form');
			curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($this->ch, CURLOPT_POST, TRUE);
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($collectData['query_params']));
			$this->curlexec();
			// Parse HTML Transactions
			//========================
			$this->dom_document->preserveWhiteSpace = false;
			$this->dom_document->validateOnParse = false;
			// Informasi Rekening
			preg_match('/<\!\-\- Header \-\->(.*?)<\!\-\- End of Header \-\->/s', $this->curlcontent, $collectData['dom']['html_rekening']);
			if (isset($collectData['dom']['html_rekening'][1])) {
				$this->dom_document->loadHTML($collectData['dom']['html_rekening'][1]);
			} else {
				$this->dom_document->loadHTML($this->curlcontent);
			}
			$collectData['dom']['xpath'] = new DOMXPath($this->dom_document);
			$collectData['dom']['informasi_rekening'] = $collectData['dom']['xpath']->query("//table[@cellpadding='2' and @cellspacing='1' and @width='100%']/tr");
			if (isset($collectData['dom']['informasi_rekening']->length)) {
				$collectData['dom']['rekening_items'] = array();
				for ($i = 0; $i < $collectData['dom']['informasi_rekening']->length; $i++) {
					$collectData['dom']['rekening_items'][$i] = array(
						'elements'		=> $collectData['dom']['informasi_rekening']->item($i),
					);
					$collectData['dom']['rekening_items'][$i]['attributes'] = array(
						'name'		=> $collectData['dom']['rekening_items'][$i]['elements']->childNodes,
						'tds'		=> $collectData['dom']['rekening_items'][$i]['elements']->getElementsByTagName('td'),
					);
					if (isset($collectData['dom']['rekening_items'][$i]['attributes']['tds']->length)) {
						$td_i = 0;
						$collectData['dom']['rekening_items'][$i]['attributes']['tds_data'] = array();
						foreach ($collectData['dom']['rekening_items'][$i]['attributes']['tds'] as $td) {
							$collectData['dom']['rekening_items'][$i]['attributes']['tds_data'][$td_i] = array(
								'display_html'		=> $this->dom_document->saveXML($td),
								'display_string'	=> str_replace('&#13;', '', str_replace('<td>', '', str_replace('</td>', '', preg_replace('#(?<=<td)(.*?)(?=>)#i', '\2', $this->dom_document->saveXML($td))))),
								'node'				=> preg_replace("/\s+/", ' ', preg_replace("/\n+/", '', $td->nodeValue)),
								'name'				=> $td->getAttribute('name'),
							);
							$td_i++;
						}
					}
				}
			}
			$informasi_rekening['rekening_currency'] = 'IDR';
			$informasi_rekening['rekening_name'] = (isset($collectData['dom']['rekening_items'][0]['attributes']['tds_data'][2]['node']) ? $collectData['dom']['rekening_items'][0]['attributes']['tds_data'][2]['node'] : '');
			$informasi_rekening['rekening_number_string'] = (isset($collectData['dom']['rekening_items'][1]['attributes']['tds_data'][2]['node']) ? $collectData['dom']['rekening_items'][1]['attributes']['tds_data'][2]['node'] : '');
			$rekening_number_arr = explode(' ', $informasi_rekening['rekening_number_string']);
			$informasi_rekening['rekening_number'] = (isset($rekening_number_arr[0]) ? $rekening_number_arr[0] : '');
			$informasi_rekening['rekening_periode'] = (isset($collectData['dom']['rekening_items'][3]['attributes']['tds_data'][2]['node']) ? $collectData['dom']['rekening_items'][3]['attributes']['tds_data'][2]['node'] : '');
			unset($collectData['dom']['rekening_items']);
			//================== Set Rows
			$collectData['rows'] = array(
				'saldo'			=> array(),
				'tmp_items'		=> array(),
				'items'			=> array(),
			);
			// Get Saldo Awal
			$this->dom_document->loadHTML($this->curlcontent);
			$collectData['dom']['saldo_xpath'] = new DOMXPath($this->dom_document);
			$collectData['dom']['saldo_mutasi_awal'] = $collectData['dom']['saldo_xpath']->query("//table[@border='0' and @cellpadding='2' and @cellspacing='1' and @width='100%']/tr/td[@id='openingbal']");
			$collectData['dom']['saldo_mutasi_akhir'] = $collectData['dom']['saldo_xpath']->query("//table[@border='0' and @cellpadding='2' and @cellspacing='1' and @width='100%']/tr/td[@id='closingbal']");
			if (isset($collectData['dom']['saldo_mutasi_awal']->length)) {
				if ((int)$collectData['dom']['saldo_mutasi_awal']->length > 0) {
					foreach ($collectData['dom']['saldo_mutasi_awal'] as $saldoval) {
						$collectData['rows']['saldo']['awal'] = array(
							'string'		=> trim($saldoval->nodeValue),
						);
						$collectData['rows']['saldo']['awal']['amount'] = $this->parse_number($collectData['rows']['saldo']['awal']['string'], ',');
					}
				}
			}
			if (isset($collectData['dom']['saldo_mutasi_akhir']->length)) {
				if ((int)$collectData['dom']['saldo_mutasi_akhir']->length > 0) {
					foreach ($collectData['dom']['saldo_mutasi_akhir'] as $saldoval) {
						$collectData['rows']['saldo']['akhir'] = array(
							'string'		=> trim($saldoval->nodeValue),
						);
						$collectData['rows']['saldo']['akhir']['amount'] = $this->parse_number($collectData['rows']['saldo']['akhir']['string'], ',');
					}
				}
			}
			// Get Item List Data
			preg_match('/<\!\-\- Start of Item List \-\->(.*?)<\!\-\- End of Item List \-\->/s', $this->curlcontent, $collectData['dom']['html_items']);
			if (isset($collectData['dom']['html_items'][1])) {
				$this->dom_document->loadHTML($collectData['dom']['html_items'][1]);
			} else {
				$this->dom_document->loadHTML($this->curlcontent);
			}
			$collectData['dom']['xpath'] = new DOMXPath($this->dom_document);
			$collectData['dom']['mutasi_tds'] = $collectData['dom']['xpath']->query("//table[@border='0' and @cellpadding='2' and @cellspacing='1' and @width='100%']/tr");
			if (isset($collectData['dom']['mutasi_tds']->length)) {
				$collectData['dom']['items'] = array();
				for ($i = 0; $i < $collectData['dom']['mutasi_tds']->length; $i++) {
					$collectData['dom']['items'][$i] = array(
						'elements'		=> $collectData['dom']['mutasi_tds']->item($i),
					);
					$collectData['dom']['items'][$i]['attributes'] = array(
						'name'		=> $collectData['dom']['items'][$i]['elements']->childNodes,
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
							$td_i++;
						}
					}

				}
			}
			//-- Make mutasi structured
			$collectData['transaction_data'] = array();
			if (count($collectData['dom']['items']) > 0) {
				foreach ($collectData['dom']['items'] as $itemKey => $itemVal) {
					if (isset($itemVal['attributes']['tds_data'])) {
						$collectData['transaction_data'][] = $itemVal['attributes']['tds_data'];
					}
				}
			}
			$collectData['rows']['html'] = $this->curlcontent;
			if (count($collectData['transaction_data']) > 0) {
				if (isset($collectData['rows']['saldo']['awal']['amount'])) {
					$saldo_awal = sprintf("%.02f", $collectData['rows']['saldo']['awal']['amount']);
				} else {
					$saldo_awal = 0;
				}
				$saldo_aktual = $saldo_awal;
				$key = 0;
				foreach ($collectData['transaction_data'] as $trans_key => $trans_val) {
					if ((int)$trans_key > 0) {
						$collectData['rows']['tmp_items'][$key] = array(
							'transaction_date_string'	=> (isset($trans_val[0]['node']) ? $trans_val[0]['node'] : ''),
							'transaction_type'			=> 'PEND',
							'transaction_description'	=> (isset($trans_val[1]['display_string']) ? $trans_val[1]['display_string'] : ''),
							'transaction_desc'			=> (isset($trans_val[1]['node']) ? $trans_val[1]['node'] : ''),
						);
						//---- Validate fo tmp_items mutasi data
						$collectData['rows']['tmp_items'][$key]['debit_amount'] = (isset($trans_val[2]['node']) ? $this->parse_number($trans_val[2]['node'], ',') : 0);
						$collectData['rows']['tmp_items'][$key]['credit_amount'] = (isset($trans_val[3]['node']) ? $this->parse_number($trans_val[3]['node'], ',') : 0);
						if ($collectData['rows']['tmp_items'][$key]['credit_amount'] >= $collectData['rows']['tmp_items'][$key]['debit_amount']) {
							$collectData['rows']['tmp_items'][$key]['transaction_payment'] = 'deposit';
							$collectData['rows']['tmp_items'][$key]['transaction_code'] = 'CR';
							$collectData['rows']['tmp_items'][$key]['transaction_amount_string'] = (isset($trans_val[3]['node']) ? $trans_val[3]['node'] : '');
							$collectData['rows']['tmp_items'][$key]['transaction_amount'] = $collectData['rows']['tmp_items'][$key]['credit_amount'];
						} else {
							$collectData['rows']['tmp_items'][$key]['transaction_payment'] = 'transfer';
							$collectData['rows']['tmp_items'][$key]['transaction_code'] = 'DB';
							$collectData['rows']['tmp_items'][$key]['transaction_amount_string'] = (isset($trans_val[2]['node']) ? $trans_val[2]['node'] : '');
							$collectData['rows']['tmp_items'][$key]['transaction_amount'] = $collectData['rows']['tmp_items'][$key]['debit_amount'];
						}
						# Make saldo
						$saldo_awal += $collectData['rows']['tmp_items'][$key]['transaction_amount'];
						$collectData['rows']['tmp_items'][$key]['transaction_saldo'] = $saldo_awal;
						# Saldo Aktual
						if ($key > 0) {
							if (strtoupper($collectData['rows']['tmp_items'][$key]['transaction_code']) === strtoupper('CR')) {
								$saldo_aktual += $collectData['rows']['tmp_items'][$key]['transaction_amount'];
							} else {
								$saldo_aktual -= $collectData['rows']['tmp_items'][$key]['transaction_amount'];
							}
						}
						$collectData['rows']['tmp_items'][$key]['actual_rekening_saldo'] = $saldo_aktual;
						// Format date
						try {
							$collectData['rows']['tmp_items'][$key]['transaction_date_object'] = DateTime::createFromFormat('d/m/Y', $collectData['rows']['tmp_items'][$key]['transaction_date_string']);
						} catch (Exception $ex) {
							throw $ex;
							$collectData['rows']['tmp_items'][$key]['transaction_date_object'] = FALSE;
						}
						if ($collectData['rows']['tmp_items'][$key]['transaction_date_object'] != FALSE) {
							$collectData['rows']['tmp_items'][$key]['transaction_date_format'] = $collectData['rows']['tmp_items'][$key]['transaction_date_object']->format('Y-m-d');
							$collectData['rows']['tmp_items'][$key]['transaction_date'] = $collectData['rows']['tmp_items'][$key]['transaction_date_object']->format('d/m');
							// Transaction Details
							$collectData['rows']['tmp_items'][$key]['transaction_detail'] = array(
								$collectData['rows']['tmp_items'][$key]['transaction_date_string'],
								sprintf("%s %s", $collectData['rows']['tmp_items'][$key]['transaction_date_object']->format('d/m'), $collectData['rows']['tmp_items'][$key]['transaction_saldo']),
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
					// Sorting from max to min
					krsort($collectData['rows']['tmp_items'], SORT_NUMERIC);
					// Push to items
					foreach ($collectData['rows']['tmp_items'] as $rowval) {
						array_push($collectData['rows']['items'], $rowval);
					}
				}
				return $collectData['rows'];
			}
		} else {
			return false;
		}
		return false;
	}
	
	private function parseMutasi($html){
		$collectData = array(
			'html' 			=> $html,
			'dom'			=> array(
				'doc' 				=> new DOMDocument,
				'items'				=> array(),
			),
		);
		libxml_use_internal_errors(true);
		//================
		$collectData['dom']['doc']->preserveWhiteSpace = false;
		$collectData['dom']['doc']->validateOnParse = false;
		// Informasi Rekening
		preg_match('/<\!\-\- Header \-\->(.*?)<\!\-\- End of Header \-\->/s', $collectData['html'], $collectData['dom']['html_rekening']);
		if (isset($collectData['dom']['html_rekening'][1])) {
			$collectData['dom']['doc']->loadHTML($collectData['dom']['html_rekening'][1]);
		} else {
			$collectData['dom']['doc']->loadHTML($collectData['html']);
		}
		$collectData['dom']['xpath'] = new DOMXPath($collectData['dom']['doc']);
		$collectData['dom']['informasi_rekening'] = $collectData['dom']['xpath']->query("//table[@cellpadding='2' and @cellspacing='1' and @width='100%']/tr");
		if (isset($collectData['dom']['informasi_rekening']->length)) {
			$collectData['dom']['rekening_items'] = array();
			for ($i = 0; $i < $collectData['dom']['informasi_rekening']->length; $i++) {
				$collectData['dom']['rekening_items'][$i] = array(
					'elements'		=> $collectData['dom']['informasi_rekening']->item($i),
				);
				$collectData['dom']['rekening_items'][$i]['attributes'] = array(
					'name'		=> $collectData['dom']['rekening_items'][$i]['elements']->childNodes,
					'tds'		=> $collectData['dom']['rekening_items'][$i]['elements']->getElementsByTagName('td'),
				);
				if (isset($collectData['dom']['rekening_items'][$i]['attributes']['tds']->length)) {
					$td_i = 0;
					$collectData['dom']['rekening_items'][$i]['attributes']['tds_data'] = array();
					foreach ($collectData['dom']['rekening_items'][$i]['attributes']['tds'] as $td) {
						$collectData['dom']['rekening_items'][$i]['attributes']['tds_data'][$td_i] = array(
							'display_html'		=> $collectData['dom']['doc']->saveXML($td),
							'display_string'	=> str_replace('&#13;', '', str_replace('<td>', '', str_replace('</td>', '', preg_replace('#(?<=<td)(.*?)(?=>)#i', '\2', $collectData['dom']['doc']->saveXML($td))))),
							'node'				=> preg_replace("/\s+/", ' ', preg_replace("/\n+/", '', $td->nodeValue)),
							'name'				=> $td->getAttribute('name'),
						);
						$td_i++;
					}
				}
			}
		}
		$informasi_rekening['rekening_currency'] = 'IDR';
		$informasi_rekening['rekening_name'] = (isset($collectData['dom']['rekening_items'][0]['attributes']['tds_data'][2]['node']) ? $collectData['dom']['rekening_items'][0]['attributes']['tds_data'][2]['node'] : '');
		$informasi_rekening['rekening_number'] = (isset($collectData['dom']['rekening_items'][1]['attributes']['tds_data'][2]['node']) ? $collectData['dom']['rekening_items'][1]['attributes']['tds_data'][2]['node'] : '');
		$informasi_rekening['rekening_periode'] = (isset($collectData['dom']['rekening_items'][3]['attributes']['tds_data'][2]['node']) ? $collectData['dom']['rekening_items'][3]['attributes']['tds_data'][2]['node'] : '');
		unset($collectData['dom']['rekening_items']);
		// Get Item List Data
		preg_match('/<\!\-\- Start of Item List \-\->(.*?)<\!\-\- End of Item List \-\->/s', $collectData['html'], $collectData['dom']['html_items']);
		if (isset($collectData['dom']['html_items'][1])) {
			$collectData['dom']['doc']->loadHTML($collectData['dom']['html_items'][1]);
		} else {
			$collectData['dom']['doc']->loadHTML($collectData['html']);
		}
		$collectData['dom']['xpath'] = new DOMXPath($collectData['dom']['doc']);
		$collectData['dom']['mutasi_tds'] = $collectData['dom']['xpath']->query("//table[@border='0' and @cellpadding='2' and @cellspacing='1' and @width='100%']/tr");
		if (isset($collectData['dom']['mutasi_tds']->length)) {
			for ($i = 0; $i < $collectData['dom']['mutasi_tds']->length; $i++) {
				$collectData['dom']['items'][$i] = array(
					'elements'		=> $collectData['dom']['mutasi_tds']->item($i),
				);
				$collectData['dom']['items'][$i]['attributes'] = array(
					'name'		=> $collectData['dom']['items'][$i]['elements']->childNodes,
					'tds'		=> $collectData['dom']['items'][$i]['elements']->getElementsByTagName('td'),
				);
				if (isset($collectData['dom']['items'][$i]['attributes']['tds']->length)) {
					$td_i = 0;
					$collectData['dom']['items'][$i]['attributes']['tds_data'] = array();
					foreach ($collectData['dom']['items'][$i]['attributes']['tds'] as $td) {
						$collectData['dom']['items'][$i]['attributes']['tds_data'][$td_i] = array(
							'display_html'		=> $collectData['dom']['doc']->saveXML($td),
							'display_string'	=> str_replace('&#13;', '', str_replace('<td>', '', str_replace('</td>', '', preg_replace('#(?<=<td)(.*?)(?=>)#i', '\2', $collectData['dom']['doc']->saveXML($td))))),
							'node'				=> preg_replace("/\s+/", ' ', preg_replace("/\n+/", '', $td->nodeValue)),
							'name'				=> $td->getAttribute('name'),
						);
						$td_i++;
					}
				}

			}
		}
		//-- Make mutasi structured
		$collectData['transaction_data'] = array();
		if (count($collectData['dom']['items']) > 0) {
			foreach ($collectData['dom']['items'] as $itemKey => $itemVal) {
				if (isset($itemVal['attributes']['tds_data'])) {
					$collectData['transaction_data'][] = $itemVal['attributes']['tds_data'];
				}
			}
		}
		$collectData['rows'] = array(
			'items'		=> array(),
		);
		if (count($collectData['transaction_data']) > 0) {
			$saldo_awal = 0;
			$key = 0;
			foreach ($collectData['transaction_data'] as $trans_key => $trans_val) {
				if ((int)$trans_key > 0) {
					$collectData['rows']['items'][$key] = array(
						'transaction_date_string'	=> (isset($trans_val[0]['node']) ? $trans_val[0]['node'] : ''),
						'transaction_type'			=> 'PEND',
						'transaction_description'	=> (isset($trans_val[1]['display_string']) ? $trans_val[1]['display_string'] : ''),
						'transaction_desc'			=> (isset($trans_val[1]['node']) ? $trans_val[1]['node'] : ''),
					);
					//---- Validate fo items mutasi data
					$collectData['rows']['items'][$key]['debit_amount'] = (isset($trans_val[2]['node']) ? $this->parse_number($trans_val[2]['node'], ',') : 0);
					$collectData['rows']['items'][$key]['credit_amount'] = (isset($trans_val[3]['node']) ? $this->parse_number($trans_val[3]['node'], ',') : 0);
					if ($collectData['rows']['items'][$key]['credit_amount'] >= $collectData['rows']['items'][$key]['debit_amount']) {
						$collectData['rows']['items'][$key]['transaction_payment'] = 'deposit';
						$collectData['rows']['items'][$key]['transaction_code'] = 'CR';
						$collectData['rows']['items'][$key]['transaction_amount_string'] = (isset($trans_val[3]['node']) ? $trans_val[3]['node'] : '');
						$collectData['rows']['items'][$key]['transaction_amount'] = $collectData['rows']['items'][$key]['credit_amount'];
					} else {
						$collectData['rows']['items'][$key]['transaction_payment'] = 'transfer';
						$collectData['rows']['items'][$key]['transaction_code'] = 'DB';
						$collectData['rows']['items'][$key]['transaction_amount_string'] = (isset($trans_val[2]['node']) ? $trans_val[2]['node'] : '');
						$collectData['rows']['items'][$key]['transaction_amount'] = $collectData['rows']['items'][$key]['debit_amount'];
					}
					# Make saldo
					$saldo_awal += $collectData['rows']['items'][$key]['transaction_amount'];
					$collectData['rows']['items'][$key]['transaction_saldo'] = $saldo_awal;
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
							$collectData['rows']['items'][$key]['transaction_description'],
							$collectData['rows']['items'][$key]['transaction_code'],
							$collectData['rows']['items'][$key]['transaction_amount_string'],
						);
					} else {
						$collectData['rows']['items'][$key]['transaction_date'] = 'PEND';
						$collectData['rows']['items'][$key]['transaction_detail'] = array();
					}
					$collectData['rows']['items'][$key]['informasi_rekening'] = $informasi_rekening;
				}
				$key += 1;
			}
		}
		return $collectData;
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



















































