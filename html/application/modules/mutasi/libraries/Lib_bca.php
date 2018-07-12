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
		$this->curl_options = array(
			'user_agent'		=> (isset($base_mutasi['curl_options']['user_agent']) ? $base_mutasi['curl_options']['user_agent'] : 'Mozilla/5.0 (Linux; U; Android 4.1.7; en-us; Sony Ericson) edited by: (imzers@gmail.com)'),
			'as_fid'			=> '',
			
		);
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
		curl_setopt($this->ch, CURLOPT_URL, 'https://m.klikbca.com/login.jsp');
		curl_setopt($this->ch, CURLOPT_COOKIESESSION, TRUE);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->ch, CURLOPT_VERBOSE, TRUE);
		curl_setopt($this->ch, CURLOPT_COOKIELIST, TRUE);
		curl_setopt($this->ch, CURLOPT_COOKIEFILE, ($this->cookies_path . DIRECTORY_SEPARATOR . $cookie_filename . '.log'));
        curl_setopt($this->ch, CURLOPT_COOKIEJAR, ($this->cookies_path . DIRECTORY_SEPARATOR . $cookie_filename . '.log'));
		curl_setopt($this->ch, CURLOPT_HEADER, false);
		if (isset($headers)) {
			curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
		}
		curl_setopt($this->ch, CURLOPT_USERAGENT, $this->curl_options['user_agent']);
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($this->ch, CURLOPT_ENCODING, "");
		curl_setopt($this->ch, CURLOPT_AUTOREFERER, true);
	}
	
	function login($username, $password) {
		$this->set_curl_init($this->create_curl_headers($this->headers), $username);
		$this->curlexec();
		$collectData = array(
			'login_params'			=> array(
				'value(user_id)'		=> $username,
				'value(pswd)'			=> $password,
				'value(Submit)'			=> 'LOGIN',
				'value(actions)'		=> 'login',
				'value(user_ip)'		=> $this->curl_options['host_ip'],
				'user_ip'				=> $this->curl_options['host_ip'],
				'value(mobile)'			=> 'true',
				'value(browser_info)'	=> $this->curl_options['user_agent'],
				'mobile'				=> 'true',
			),
		);
		$this->get_as_fid($this->curlcontent);
		$collectData['login_params']['as_fid'] = $this->curl_options['as_fid'];
		curl_setopt($this->ch, CURLOPT_URL, 'https://m.klikbca.com/authentication.do' );
        curl_setopt($this->ch, CURLOPT_REFERER, 'https://m.klikbca.com/login.jsp' );
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($collectData['login_params']));
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
	function set_as_fid($as_fid) {
		$this->curl_options['as_fid'] = $as_fid;
		return $this;
	}
	function get_as_fid($html_string = null) {
		if (!isset($html_string)) {
			$html_string = $this->curlcontent;
		}
		$collectData = array(
			'dom'			=> array(
				'items'			=> array(),
			),
			'as_fid'		=> array(),
		);
		$this->dom_document->preserveWhiteSpace = false;
		$this->dom_document->validateOnParse = false;
		if (!empty($html_string)) {
			$this->dom_document->loadHTML($html_string);
			$collectData['dom']['xpath'] = new DOMXPath($this->dom_document);
			$collectData['dom']['as_fid'] = $collectData['dom']['xpath']->query("//input[@type='hidden' and @name='as_fid']");
			if (isset($collectData['dom']['as_fid']->length)) {
				for ($i = 0; $i < $collectData['dom']['as_fid']->length; $i++) {
					$collectData['as_fid'][$i] = array(
						'elements'		=> $collectData['dom']['as_fid']->item($i),
					);
					$collectData['as_fid'][$i]['attributes'] = array(
						'name'			=> $collectData['as_fid'][$i]['elements']->getAttribute('name'),
						'value'			=> $collectData['as_fid'][$i]['elements']->getAttribute('value'),
					);
				}
				if (count($collectData['as_fid']) > 0) {
					foreach ($collectData['as_fid'] as $as_fid) {
						if (isset($as_fid['attributes']['value'])) {
							$this->set_as_fid($as_fid['attributes']['value']);
						}
					}
				}
			}
		}
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
		$collectData = array(
			'dom'			=> array(
				'items'					=> array(),
				'item_rekening'			=> array(),
			),
			'dateobject'	=> $this->create_time_zone(ConstantConfig::$timezone),
			'as_fid'		=> array(),
		);
        curl_setopt($this->ch, CURLOPT_URL, 'https://m.klikbca.com/accountstmt.do?value(actions)=menu');
        curl_setopt($this->ch, CURLOPT_REFERER, 'https://m.klikbca.com/authentication.do');
        $this->curlexec();
        curl_setopt($this->ch, CURLOPT_URL, 'https://m.klikbca.com/accountstmt.do?value(actions)=acct_stmt');
        curl_setopt($this->ch, CURLOPT_REFERER, 'https://m.klikbca.com/accountstmt.do?value(actions)=menu');
        $this->curlexec();
		$collectData['query_params'] = array(
			'r1'				=> '1', 
			'value(D1)'			=> '0', 
			'value(startDt)'	=> $transaction_datetime['starting']->format('d'),
			'value(startMt)'	=> $transaction_datetime['starting']->format('n'),
			'value(startYr)'	=> $transaction_datetime['starting']->format('Y'),
			'value(endDt)'		=> $transaction_datetime['stopping']->format('d'),
			'value(endMt)'		=> $transaction_datetime['stopping']->format('n'),
			'value(endYr)'		=> $transaction_datetime['stopping']->format('Y'),
			'as_fid'			=> $this->curl_options['as_fid'],
		);
        curl_setopt($this->ch, CURLOPT_URL, 'https://m.klikbca.com/accountstmt.do?value(actions)=acctstmtview');
        curl_setopt($this->ch, CURLOPT_REFERER, 'https://m.klikbca.com/accountstmt.do?value(actions)=acct_stmt');
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->ch, CURLOPT_POST, TRUE);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($collectData['query_params']));
        $this->curlexec();
		##
		# DEBUG
		##
		if (!empty($this->curlcontent)) {
			$this->dom_document->preserveWhiteSpace = false;
			$this->dom_document->validateOnParse = false;
			$this->dom_document->loadHTML($this->curlcontent);
			$collectData['dom']['xpath'] = new DOMXPath($this->dom_document);
			// Informasi Rekening
			$collectData['dom']['informasi_rekening'] = $collectData['dom']['xpath']->query("//table[@border='0' and @width='100%' and @cellpadding='0' and @cellspacing='0' and @class='blue']/tr/td[@align='left']");
			if (isset($collectData['dom']['informasi_rekening']->length)) {
				$collectData['dom']['rekening_items'] = array();
				for ($i = 0; $i < $collectData['dom']['informasi_rekening']->length; $i++) {
					$collectData['dom']['rekening_items'][$i] = array(
						'elements'		=> $collectData['dom']['informasi_rekening']->item($i),
					);
				}
				if (count($collectData['dom']['rekening_items']) > 0) {
					$informasi_rekening['rekening_number'] = (isset($collectData['dom']['rekening_items'][1]['elements']->nodeValue) ? $collectData['dom']['rekening_items'][1]['elements']->nodeValue : '');
					$informasi_rekening['rekening_name'] = (isset($collectData['dom']['rekening_items'][3]['elements']->nodeValue) ? $collectData['dom']['rekening_items'][3]['elements']->nodeValue : '');
					$informasi_rekening['rekening_periode'] = (isset($collectData['dom']['rekening_items'][5]['elements']->nodeValue) ? $collectData['dom']['rekening_items'][5]['elements']->nodeValue : '');
					$informasi_rekening['rekening_currency'] = (isset($collectData['dom']['rekening_items'][7]['elements']->nodeValue) ? $collectData['dom']['rekening_items'][7]['elements']->nodeValue : 'IDR');
					// Unset rekening-items
					unset($collectData['dom']['rekening_items']);
				}
			}
			$collectData['informasi_rekening'] = $informasi_rekening;
			//========= SET ROWS ITEMS ==========
			//================== Set Rows
			$collectData['rows'] = array(
				'saldo'			=> array(),
				'tmp_items'		=> array(),
				'items'			=> array(),
			);
			// Get Saldo
			$collectData['dom']['saldo_mutasi'] = $collectData['dom']['xpath']->query("//table[@width='97%' and @cellspacing='0' and @class='blue']/tr");
			if (isset($collectData['dom']['saldo_mutasi']->length)) {
				if ((int)$collectData['dom']['saldo_mutasi']->length > 0) {
					for ($i = 0; $i < $collectData['dom']['saldo_mutasi']->length; $i++) {
						$collectData['rows']['saldo'][$i] = array(
							'elements'		=> $collectData['dom']['saldo_mutasi']->item($i),
						);
						$collectData['rows']['saldo'][$i]['tds'] = $collectData['rows']['saldo'][$i]['elements']->getElementsByTagName('td');
						if (isset($collectData['rows']['saldo'][$i]['tds']->length)) {
							$collectData['rows']['saldo'][$i]['attributes'] = array();
							foreach ($collectData['rows']['saldo'][$i]['tds'] as $td) {
								$collectData['rows']['saldo'][$i]['attributes'][] = $td->nodeValue;
							}
						}
					}
				}
			}
			$collectData['saldo'] = array(
				'awal'			=> (isset($collectData['rows']['saldo'][1]['attributes'][2]) ? sprintf("%s", trim($collectData['rows']['saldo'][1]['attributes'][2])) : ''),
				'akhir'			=> (isset($collectData['rows']['saldo'][4]['attributes'][2]) ? sprintf("%s", trim($collectData['rows']['saldo'][4]['attributes'][2])) : ''),
				'debet'			=> (isset($collectData['rows']['saldo'][3]['attributes'][2]) ? sprintf("%s", trim($collectData['rows']['saldo'][3]['attributes'][2])) : ''),
				'kredit'			=> (isset($collectData['rows']['saldo'][2]['attributes'][2]) ? sprintf("%s", trim($collectData['rows']['saldo'][2]['attributes'][2])) : ''),
			);
			$collectData['saldo']['awal'] = $this->parse_number($collectData['saldo']['awal'], '.');
			$collectData['saldo']['akhir'] = $this->parse_number($collectData['saldo']['akhir'], '.');
			$collectData['saldo']['aktual'] = $this->parse_number($collectData['saldo']['awal'], '.');
			// Get Item List Data
			$collectData['dom']['mutasi_tds'] = $collectData['dom']['xpath']->query("//table[@width='100%' and @class='blue']/tr");
			if (isset($collectData['dom']['mutasi_tds']->length)) {
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
							);
							$collectData['dom']['items'][$i]['attributes']['tds_data'][$td_i]['display_string'] = str_replace('<td>', '', str_replace('</td>', '', preg_replace('#(?<=<td)(.*?)(?=>)#i', '\2', $collectData['dom']['items'][$i]['attributes']['tds_data'][$td_i]['display_string'])));
							$collectData['dom']['items'][$i]['attributes']['tds_data'][$td_i]['display_string'] = preg_replace("/\s+/", ' ', preg_replace("/\n+/", '', $collectData['dom']['items'][$i]['attributes']['tds_data'][$td_i]['display_string']));
							$collectData['dom']['items'][$i]['attributes']['tds_data'][$td_i]['display_string'] = preg_replace('/undefined/i', '', $collectData['dom']['items'][$i]['attributes']['tds_data'][$td_i]['display_string']);
							// Set Transactions Details
							$collectData['dom']['items'][$i]['attributes']['tds_data'][$td_i]['transaction_details'] = explode('<br/>', $collectData['dom']['items'][$i]['attributes']['tds_data'][$td_i]['display_string']);
							$td_i++;
						}
					}
				}
			}
			//-- Make mutasi structured
			$collectData['transaction_data'] = array();
			if (count($collectData['dom']['items']) > 0) {
				foreach ($collectData['dom']['items'] as $itemKey => $itemVal) {
					if ($itemKey > 6) {
						if (isset($itemVal['attributes']['tds_data'])) {
							$collectData['transaction_data'][] = $itemVal['attributes']['tds_data'];
						}
					}
				}
			}
			if (count($collectData['transaction_data']) > 0) {
				$key = 0;
				foreach ($collectData['transaction_data'] as $trans_key => $trans_val) {
					if (is_array($trans_val) && (count($trans_val) > 0)) {
						$collectData['rows']['items'][$key] = array(
							'transaction_description'	=> (isset($trans_val[1]['display_string']) ? $trans_val[1]['display_string'] : ''),
							'transaction_desc'			=> (isset($trans_val[1]['node']) ? trim($trans_val[1]['node']) : ''),
						);
						$collectData['rows']['items'][$key]['transaction_description'] = trim($collectData['rows']['items'][$key]['transaction_description']);
						$collectData['rows']['items'][$key]['informasi_rekening'] = $informasi_rekening;
						$collectData['rows']['items'][$key]['transaction_date_string'] = '-';
						if (isset($trans_val[2]['node'])) {
							if (strtoupper(trim($trans_val[2]['node'])) === strtoupper('DB')) {
								$collectData['rows']['items'][$key]['transaction_payment'] = 'transfer';
								$collectData['rows']['items'][$key]['transaction_code'] = 'DB';
							} else {
								$collectData['rows']['items'][$key]['transaction_payment'] = 'deposit';
								$collectData['rows']['items'][$key]['transaction_code'] = 'CR';
							}
						} else {
							$collectData['rows']['items'][$key]['transaction_payment'] = 'deposit';
							$collectData['rows']['items'][$key]['transaction_code'] = 'CR';
						}
						//---- Validate fo items mutasi data
						if (isset($trans_val[1]['transaction_details'])) {
							$collectData['rows']['items'][$key]['transaction_details_count'] = count($trans_val[1]['transaction_details']);
							$collectData['rows']['items'][$key]['transaction_detail_explode'] = array();
							if (is_array($trans_val[1]['transaction_details'])) {
								foreach ($trans_val[1]['transaction_details'] as $trans_det_array) {
									array_push($collectData['rows']['items'][$key]['transaction_detail_explode'], trim($trans_det_array));
								}
								$collectData['rows']['items'][$key]['transaction_amount_string'] = end($trans_val[1]['transaction_details']);
								$collectData['rows']['items'][$key]['transaction_amount_string'] = sprintf("%s", $collectData['rows']['items'][$key]['transaction_amount_string']);
								$collectData['rows']['items'][$key]['transaction_amount'] = $this->parse_number($collectData['rows']['items'][$key]['transaction_amount_string'], '.');
								# Make saldo
								$collectData['saldo']['awal'] += $collectData['rows']['items'][$key]['transaction_amount'];
								$collectData['rows']['items'][$key]['transaction_saldo'] = $collectData['saldo']['awal'];
								# Saldo Aktual
								if (strtoupper($collectData['rows']['items'][$key]['transaction_code']) === strtoupper('CR')) {
									$collectData['saldo']['aktual'] += $collectData['rows']['items'][$key]['transaction_amount'];
								} else {
									$collectData['saldo']['aktual'] -= $collectData['rows']['items'][$key]['transaction_amount'];
								}
								$collectData['rows']['items'][$key]['actual_rekening_saldo'] = $collectData['saldo']['aktual'];
								switch ($collectData['rows']['items'][$key]['transaction_details_count']) {
									case 1:
										
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
											if (strpos($collectData['rows']['items'][$key]['transaction_detail_explode'][0], ':') !== FALSE) {
												if (strpos($collectData['rows']['items'][$key]['transaction_detail_explode'][0], sprintf("%s/%s", $collectData['dateobject']->format('m'), $collectData['dateobject']->format('d'))) !== FALSE) {
													$collectData['rows']['items'][$key]['transaction_date_string'] = sprintf("%s/%s/%s", $collectData['dateobject']->format('d'), $collectData['dateobject']->format('m'), $collectData['dateobject']->format('Y'));
												} else {
													if (!preg_match('/[0-9]{4}/', sprintf("%s", $collectData['rows']['items'][$key]['transaction_detail_explode'][2]))) {
														preg_match('/([0-9]{2}+)\/([0-9]{2}+)/', $collectData['rows']['items'][$key]['transaction_detail_explode'][0], $collectData['rows']['items'][$key]['transaction_date_string_array']);
														if (count($collectData['rows']['items'][$key]['transaction_date_string_array']) > 2) {
															$collectData['rows']['items'][$key]['transaction_date_string'] = sprintf("%s/%s/%s", $collectData['rows']['items'][$key]['transaction_date_string_array'][2], $collectData['rows']['items'][$key]['transaction_date_string_array'][1], $collectData['dateobject']->format('Y'));
														}
													} else {
														if (strpos($collectData['rows']['items'][$key]['transaction_detail_explode'][0], sprintf("%s/%s", $collectData['dateobject']->format('d'), $collectData['dateobject']->format('m'))) !== FALSE) {
															$collectData['rows']['items'][$key]['transaction_date_string'] = sprintf("%s/%s/%s", $collectData['dateobject']->format('d'), $collectData['dateobject']->format('m'), $collectData['dateobject']->format('Y'));
														} else {
															preg_match('/([0-9]{2}+)\/([0-9]{2}+)/', $collectData['rows']['items'][$key]['transaction_detail_explode'][0], $collectData['rows']['items'][$key]['transaction_date_string_array']);
															if (count($collectData['rows']['items'][$key]['transaction_date_string_array']) > 2) {
																$collectData['rows']['items'][$key]['transaction_date_string'] = sprintf("%s/%s/%s", $collectData['rows']['items'][$key]['transaction_date_string_array'][1], $collectData['rows']['items'][$key]['transaction_date_string_array'][2], $collectData['dateobject']->format('Y'));
															}
														}
													}
												}
											} else {
												if (sprintf("%s", $collectData['rows']['items'][$key]['transaction_detail_explode'][2]) == '0000') {
													if (strpos($collectData['rows']['items'][$key]['transaction_detail_explode'][0], sprintf("%s/%s", $collectData['dateobject']->format('m'), $collectData['dateobject']->format('d'))) !== FALSE) {
														$collectData['rows']['items'][$key]['transaction_date_string'] = sprintf("%s/%s/%s", $collectData['dateobject']->format('d'), $collectData['dateobject']->format('m'), $collectData['dateobject']->format('Y'));
													} else {
														preg_match('/([0-9]{2}+)\/([0-9]{2}+)/', $collectData['rows']['items'][$key]['transaction_detail_explode'][0], $collectData['rows']['items'][$key]['transaction_date_string_array']);
														if (count($collectData['rows']['items'][$key]['transaction_date_string_array']) > 2) {
															$collectData['rows']['items'][$key]['transaction_date_string'] = sprintf("%s/%s/%s", $collectData['rows']['items'][$key]['transaction_date_string_array'][2], $collectData['rows']['items'][$key]['transaction_date_string_array'][1], $collectData['dateobject']->format('Y'));
														}
													}
												} else {
													if (strpos($collectData['rows']['items'][$key]['transaction_detail_explode'][1], sprintf("%s/%s", $collectData['dateobject']->format('m'), $collectData['dateobject']->format('d'))) !== FALSE) {
														$collectData['rows']['items'][$key]['transaction_date_string'] = sprintf("%s/%s/%s", $collectData['dateobject']->format('d'), $collectData['dateobject']->format('m'), $collectData['dateobject']->format('Y'));
													} else {
														preg_match('/([0-9]{2}+)\/([0-9]{2}+)/', $collectData['rows']['items'][$key]['transaction_detail_explode'][1], $collectData['rows']['items'][$key]['transaction_date_string_array']);
														if (count($collectData['rows']['items'][$key]['transaction_date_string_array']) > 2) {
															$collectData['rows']['items'][$key]['transaction_date_string'] = sprintf("%s/%s/%s", $collectData['rows']['items'][$key]['transaction_date_string_array'][2], $collectData['rows']['items'][$key]['transaction_date_string_array'][1], $collectData['dateobject']->format('Y'));
														}
													}
												}
											}
										} else {
											if (strpos($collectData['rows']['items'][$key]['transaction_detail_explode'][1], sprintf("%s%s", $collectData['dateobject']->format('d'), $collectData['dateobject']->format('m'))) !== FALSE) {
												$collectData['rows']['items'][$key]['transaction_date_string'] = sprintf("%s/%s/%s", $collectData['dateobject']->format('d'), $collectData['dateobject']->format('m'), $collectData['dateobject']->format('Y'));
											} else if (preg_match('/([0-9]{4}+)\//', $collectData['rows']['items'][$key]['transaction_detail_explode'][0])) {
												
											} else {
												preg_match('/([0-9]{4}+)\//', $collectData['rows']['items'][$key]['transaction_detail_explode'][1], $collectData['rows']['items'][$key]['transaction_date_string_array']);
												if (count($collectData['rows']['items'][$key]['transaction_date_string_array']) > 2) {
													$collectData['rows']['items'][$key]['transaction_date_string'] = sprintf("%s/%s/%s", $collectData['rows']['items'][$key]['transaction_date_string_array'][1], $collectData['rows']['items'][$key]['transaction_date_string_array'][2], $collectData['dateobject']->format('Y'));
												}
											}
										}
									break;
									case 5:
										if (strtoupper($collectData['rows']['items'][$key]['transaction_code']) === 'CR') {
											if (strpos($collectData['rows']['items'][$key]['transaction_detail_explode'][1], ':') !== FALSE) {
												if (strpos($collectData['rows']['items'][$key]['transaction_detail_explode'][1], sprintf("%s/%s", $collectData['dateobject']->format('d'), $collectData['dateobject']->format('m'))) !== FALSE) {
													$collectData['rows']['items'][$key]['transaction_date_string'] = sprintf("%s/%s/%s", $collectData['dateobject']->format('d'), $collectData['dateobject']->format('m'), $collectData['dateobject']->format('Y'));
												} else {
													preg_match('/([0-9]{2}+)\/([0-9]{2}+)/', $collectData['rows']['items'][$key]['transaction_detail_explode'][1], $collectData['rows']['items'][$key]['transaction_date_string_array']);
													if (count($collectData['rows']['items'][$key]['transaction_date_string_array']) > 2) {
														$collectData['rows']['items'][$key]['transaction_date_string'] = sprintf("%s/%s/%s", $collectData['rows']['items'][$key]['transaction_date_string_array'][1], $collectData['rows']['items'][$key]['transaction_date_string_array'][2], $collectData['dateobject']->format('Y'));
													}
												}
											} else if (strpos($collectData['rows']['items'][$key]['transaction_detail_explode'][0], 'TRANSFER DR') !== FALSE) {
												if (isset($trans_val[0]['node'])) {
													$string_dateobject = DateTime::createFromFormat('d/m/Y', sprintf("%s/%s", trim($trans_val[0]['node']), $collectData['dateobject']->format('Y')));
													if ($string_dateobject != FALSE) {
														$collectData['rows']['items'][$key]['transaction_date_string'] = $string_dateobject->format('d/m/Y');
													}
												}
											} else if (strpos($collectData['rows']['items'][$key]['transaction_detail_explode'][0], 'FTSCY') !== FALSE) {
												if (strpos($collectData['rows']['items'][$key]['transaction_detail_explode'][0], sprintf("%s%s", $collectData['dateobject']->format('d'), $collectData['dateobject']->format('m'))) !== FALSE) {
													$collectData['rows']['items'][$key]['transaction_date_string'] = sprintf("%s/%s/%s", $collectData['dateobject']->format('d'), $collectData['dateobject']->format('m'), $collectData['dateobject']->format('Y'));
												} else {
													preg_match('/([0-9]{4}+)\//', $collectData['rows']['items'][$key]['transaction_detail_explode'][0], $collectData['rows']['items'][$key]['transaction_date_string_array']);
													if (count($collectData['rows']['items'][$key]['transaction_date_string_array']) > 1) {
														$string_dateobject = DateTime::createFromFormat('dmY', sprintf("%s%s", $collectData['rows']['items'][$key]['transaction_date_string_array'][1], $collectData['dateobject']->format('Y')));
														if ($string_dateobject != FALSE) {
															$collectData['rows']['items'][$key]['transaction_date_string'] = $string_dateobject->format('d/m/Y');
														}
													}
												}
											} else {
												if (sprintf("%s", $collectData['rows']['items'][$key]['transaction_detail_explode'][3]) == '0000') {
													if (strpos($collectData['rows']['items'][$key]['transaction_detail_explode'][0], sprintf("%s/%s", $collectData['dateobject']->format('m'), $collectData['dateobject']->format('d'))) !== FALSE) {
														$collectData['rows']['items'][$key]['transaction_date_string'] = sprintf("%s/%s/%s", $collectData['dateobject']->format('d'), $collectData['dateobject']->format('m'), $collectData['dateobject']->format('Y'));
													} else {
														preg_match('/([0-9]{2}+)\/([0-9]{2}+)/', $collectData['rows']['items'][$key]['transaction_detail_explode'][0], $collectData['rows']['items'][$key]['transaction_date_string_array']);
														if (count($collectData['rows']['items'][$key]['transaction_date_string_array']) > 2) {
															$collectData['rows']['items'][$key]['transaction_date_string'] = sprintf("%s/%s/%s", $collectData['rows']['items'][$key]['transaction_date_string_array'][2], $collectData['rows']['items'][$key]['transaction_date_string_array'][1], $collectData['dateobject']->format('Y'));
														}
													}
												} else {
													if (strpos($collectData['rows']['items'][$key]['transaction_detail_explode'][1], sprintf("%s/%s", $collectData['dateobject']->format('m'), $collectData['dateobject']->format('d'))) !== FALSE) {
														$collectData['rows']['items'][$key]['transaction_date_string'] = sprintf("%s/%s/%s", $collectData['dateobject']->format('d'), $collectData['dateobject']->format('m'), $collectData['dateobject']->format('Y'));
													} else {
														preg_match('/([0-9]{2}+)\/([0-9]{2}+)/', $collectData['rows']['items'][$key]['transaction_detail_explode'][1], $collectData['rows']['items'][$key]['transaction_date_string_array']);
														if (count($collectData['rows']['items'][$key]['transaction_date_string_array']) > 2) {
															$collectData['rows']['items'][$key]['transaction_date_string'] = sprintf("%s/%s/%s", $collectData['rows']['items'][$key]['transaction_date_string_array'][2], $collectData['rows']['items'][$key]['transaction_date_string_array'][1], $collectData['dateobject']->format('Y'));
														}
													}
												}
											}
										} else {
											if (strpos($collectData['rows']['items'][$key]['transaction_detail_explode'][0], sprintf("%s%s", $collectData['dateobject']->format('d'), $collectData['dateobject']->format('m'))) !== FALSE) {
												$collectData['rows']['items'][$key]['transaction_date_string'] = sprintf("%s/%s/%s", $collectData['dateobject']->format('d'), $collectData['dateobject']->format('m'), $collectData['dateobject']->format('Y'));
											} else {
												preg_match('/([0-9]{4}+)\//', $collectData['rows']['items'][$key]['transaction_detail_explode'][0], $collectData['rows']['items'][$key]['transaction_date_string_array']);
												if (count($collectData['rows']['items'][$key]['transaction_date_string_array']) > 1) {
													$string_dateobject = DateTime::createFromFormat('dmY', sprintf("%s%s", $collectData['rows']['items'][$key]['transaction_date_string_array'][1], $collectData['dateobject']->format('Y')));
													if ($string_dateobject != FALSE) {
														$collectData['rows']['items'][$key]['transaction_date_string'] = $string_dateobject->format('d/m/Y');
													}
												}
											}
										}
									break;
									case 6:
										if (strtoupper($collectData['rows']['items'][$key]['transaction_code']) === 'CR') {
											if (strpos($collectData['rows']['items'][$key]['transaction_detail_explode'][1], ':') !== FALSE) {
												if (strpos($collectData['rows']['items'][$key]['transaction_detail_explode'][1], sprintf("%s/%s", $collectData['dateobject']->format('d'), $collectData['dateobject']->format('m'))) !== FALSE) {
													$collectData['rows']['items'][$key]['transaction_date_string'] = sprintf("%s/%s/%s", $collectData['dateobject']->format('d'), $collectData['dateobject']->format('m'), $collectData['dateobject']->format('Y'));
												} else {
													preg_match('/([0-9]{2}+)\/([0-9]{2}+)/', $collectData['rows']['items'][$key]['transaction_detail_explode'][1], $collectData['rows']['items'][$key]['transaction_date_string_array']);
													if (count($collectData['rows']['items'][$key]['transaction_date_string_array']) > 2) {
														$collectData['rows']['items'][$key]['transaction_date_string'] = sprintf("%s/%s/%s", $collectData['rows']['items'][$key]['transaction_date_string_array'][1], $collectData['rows']['items'][$key]['transaction_date_string_array'][2], $collectData['dateobject']->format('Y'));
													}
												}
											} else if (strpos($collectData['rows']['items'][$key]['transaction_detail_explode'][0], ':') !== FALSE) {
												if (strpos($collectData['rows']['items'][$key]['transaction_detail_explode'][0], sprintf("%s/%s", $collectData['dateobject']->format('d'), $collectData['dateobject']->format('m'))) !== FALSE) {
													$collectData['rows']['items'][$key]['transaction_date_string'] = sprintf("%s/%s/%s", $collectData['dateobject']->format('d'), $collectData['dateobject']->format('m'), $collectData['dateobject']->format('Y'));
												} else {
													preg_match('/([0-9]{2}+)\/([0-9]{2}+)/', $collectData['rows']['items'][$key]['transaction_detail_explode'][0], $collectData['rows']['items'][$key]['transaction_date_string_array']);
													if (count($collectData['rows']['items'][$key]['transaction_date_string_array']) > 2) {
														$collectData['rows']['items'][$key]['transaction_date_string'] = sprintf("%s/%s/%s", $collectData['rows']['items'][$key]['transaction_date_string_array'][1], $collectData['rows']['items'][$key]['transaction_date_string_array'][2], $collectData['dateobject']->format('Y'));
													}
												}
											} else {
												if (strpos($collectData['rows']['items'][$key]['transaction_detail_explode'][0], '/') !== FALSE) {
													if (preg_match('/([0-9]+)\/([A-Z]+)\/([a-z0-9A-Z]+)/', $collectData['rows']['items'][$key]['transaction_detail_explode'][0])) {
														$collectData['rows']['items'][$key]['transaction_date_string_array'] = explode('/', $collectData['rows']['items'][$key]['transaction_detail_explode'][0]);
														if (isset($collectData['rows']['items'][$key]['transaction_date_string_array'][0])) {
															$collectData['rows']['items'][$key]['transaction_date_string_array'][0] = trim($collectData['rows']['items'][$key]['transaction_date_string_array'][0]);
															$string_dateobject = DateTime::createFromFormat('dmY', sprintf("%s%s", $collectData['rows']['items'][$key]['transaction_date_string_array'][0], $collectData['dateobject']->format('Y')));
															if ($string_dateobject != FALSE) {
																$collectData['rows']['items'][$key]['transaction_date_string'] = $string_dateobject->format('d/m/Y');
															}
														}
													} else if (strpos($collectData['rows']['items'][$key]['transaction_detail_explode'][0], sprintf("%s/%s", $collectData['dateobject']->format('d'), $collectData['dateobject']->format('m'))) !== FALSE) {
														$collectData['rows']['items'][$key]['transaction_date_string'] = sprintf("%s/%s/%s", $collectData['dateobject']->format('d'), $collectData['dateobject']->format('m'), $collectData['dateobject']->format('Y'));
													} else {
														preg_match('/([0-9]{2}+)\/([0-9]{2}+)/', $collectData['rows']['items'][$key]['transaction_detail_explode'][0], $collectData['rows']['items'][$key]['transaction_date_string_array']);
														if (count($collectData['rows']['items'][$key]['transaction_date_string_array']) > 2) {
															$collectData['rows']['items'][$key]['transaction_date_string'] = sprintf("%s/%s/%s", $collectData['rows']['items'][$key]['transaction_date_string_array'][2], $collectData['rows']['items'][$key]['transaction_date_string_array'][1], $collectData['dateobject']->format('Y'));
														}
													}
												} else {
													if (strpos($collectData['rows']['items'][$key]['transaction_detail_explode'][1], sprintf("%s/%s", $collectData['dateobject']->format('m'), $collectData['dateobject']->format('d'))) !== FALSE) {
														$collectData['rows']['items'][$key]['transaction_date_string'] = sprintf("%s/%s/%s", $collectData['dateobject']->format('d'), $collectData['dateobject']->format('m'), $collectData['dateobject']->format('Y'));
													} else {
														preg_match('/([0-9]{2}+)\/([0-9]{2}+)/', $collectData['rows']['items'][$key]['transaction_detail_explode'][1], $collectData['rows']['items'][$key]['transaction_date_string_array']);
														if (count($collectData['rows']['items'][$key]['transaction_date_string_array']) > 2) {
															$collectData['rows']['items'][$key]['transaction_date_string'] = sprintf("%s/%s/%s", $collectData['rows']['items'][$key]['transaction_date_string_array'][2], $collectData['rows']['items'][$key]['transaction_date_string_array'][1], $collectData['dateobject']->format('Y'));
														}
													}
												}
											}
										} else {
											if (strpos($collectData['rows']['items'][$key]['transaction_detail_explode'][1], sprintf("%s%s", $collectData['dateobject']->format('d'), $collectData['dateobject']->format('m'))) !== FALSE) {
												$collectData['rows']['items'][$key]['transaction_date_string'] = sprintf("%s/%s/%s", $collectData['dateobject']->format('d'), $collectData['dateobject']->format('m'), $collectData['dateobject']->format('Y'));
											} else {
												if (strpos($collectData['rows']['items'][$key]['transaction_detail_explode'][0], '/') !== FALSE) {
													if (preg_match('/([0-9]+)\/([A-Z]+)\/([a-z0-9A-Z]+)/', $collectData['rows']['items'][$key]['transaction_detail_explode'][0])) {
														$collectData['rows']['items'][$key]['transaction_date_string_array'] = explode('/', $collectData['rows']['items'][$key]['transaction_detail_explode'][0]);
														if (isset($collectData['rows']['items'][$key]['transaction_date_string_array'][0])) {
															$collectData['rows']['items'][$key]['transaction_date_string_array'][0] = trim($collectData['rows']['items'][$key]['transaction_date_string_array'][0]);
															$string_dateobject = DateTime::createFromFormat('dmY', sprintf("%s%s", $collectData['rows']['items'][$key]['transaction_date_string_array'][0], $collectData['dateobject']->format('Y')));
															if ($string_dateobject != FALSE) {
																$collectData['rows']['items'][$key]['transaction_date_string'] = $string_dateobject->format('d/m/Y');
															}
														}
													}
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
										}
									break;
									case 7:
									default:
										if (strtoupper($collectData['rows']['items'][$key]['transaction_code']) === 'CR') {
										// Nothing
										} else {
											if (strpos($trans_val[1]['transaction_details'][1], sprintf("%s%s", $collectData['dateobject']->format('d'), $collectData['dateobject']->format('m'))) !== FALSE) {
												$collectData['rows']['items'][$key]['transaction_date_string'] = sprintf("%s/%s/%s", $collectData['dateobject']->format('d'), $collectData['dateobject']->format('m'), $collectData['dateobject']->format('Y'));
											} else if (preg_match('/([0-9]{4}+)\//', $trans_val[1]['transaction_details'][1])) {
												$collectData['rows']['items'][$key]['transaction_date_string_array'] = explode('/', $trans_val[1]['transaction_details'][1]);
												if (isset($collectData['rows']['items'][$key]['transaction_date_string_array'][0])) {
													$collectData['rows']['items'][$key]['transaction_date_string_array'][0] = trim($collectData['rows']['items'][$key]['transaction_date_string_array'][0]);
													$string_dateobject = DateTime::createFromFormat('dmY', sprintf("%s%s", $collectData['rows']['items'][$key]['transaction_date_string_array'][0], $collectData['dateobject']->format('Y')));
													if ($string_dateobject != FALSE) {
														$collectData['rows']['items'][$key]['transaction_date_string'] = $string_dateobject->format('d/m/Y');
													}
												}
											} else {
												preg_match('/([0-9]{4}+)\//', $trans_val[1]['transaction_details'][0], $collectData['rows']['items'][$key]['transaction_date_string_array']);
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
							}
						}
						$collectData['rows']['items'][$key]['transaction_type'] = 'PEND';
						// Format date
						try {
							$collectData['rows']['items'][$key]['transaction_date_object'] = DateTime::createFromFormat('d/m/Y', trim($collectData['rows']['items'][$key]['transaction_date_string']));
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
							$collectData['rows']['items'][$key]['transaction_detail'] = array();
						}
					}
					$key += 1;
				}
				return $collectData['rows'];
			} else {
				return false;
			}
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
	private function parse_number($number, $dec_point = null) {
		if (empty($dec_point)) {
			$locale = localeconv();
			$dec_point = $locale['decimal_point'];
		}
		return floatval(str_replace($dec_point, '.', preg_replace('/[^\d' . preg_quote($dec_point).']/', '', $number)));
	}
} 