<?php
if (!defined('BASEPATH')) {
	exit("Cannot load script directly");
}
class Lib_bni {
	protected $CI;
	protected $curl_options, $client;
	protected $cookies_path = __DIR__;
	protected $ch = NULL;
	protected $curlcontent = NULL;
	public $mbparam = '';
	protected $jsessionid = '';
	function __construct() {
		$this->CI = &get_instance();
		$this->CI->load->config('mutasi/base_mutasi');
		$base_mutasi = $this->CI->config->item('base_mutasi');
		$this->curl_options = array(
			'user_agent'		=> 'Api.Context' . time(),
			
		);
		if (isset($base_mutasi['cookies']['bni'])) {
			$this->cookies_path = $base_mutasi['cookies']['bni'];
		}
		$this->client = (isset($base_mutasi['client']) ? $base_mutasi['client'] : array());
		if (isset($this->client['user_ip'])) {
			$this->client['user_ip'] = $this->set_client_ip($this->client['user_ip']);
		}
		$this->set_headers();
		$this->add_headers('Content-type', 'application/x-www-form-urlencoded');
		//-- Set ajax
		//$this->add_headers('X-Requested-With', 'XMLHttpRequest');
		// Set Curl Init
		$this->cookie_filename = (isset($base_mutasi['cookies_filename']) ? $base_mutasi['cookies_filename'] : 'cookies.txt');
		$this->cookie_filename = (is_string($this->cookie_filename) ? strtolower($this->cookie_filename) : 'cookies.txt');
		//$this->set_curl_init($this->create_curl_headers($this->headers), $this->cookie_filename);
	}
	private function set_mbparam($mbparam) {
		$this->mbparam = $mbparam;
		return $this;
	}
	private function set_jsessionid($jsessionid) {
		$this->jsessionid = $jsessionid;
		return $this->jsessionid;
	}
	function curlexec() {
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, TRUE);
        $this->curlcontent = curl_exec($this->ch);
    }
	function set_curl_init($headers = null, $cookie_filename = 'cookies.txt') {
		$this->ch = curl_init();
		curl_setopt($this->ch, CURLOPT_URL, 'https://ibank.bni.co.id/MBAWeb/FMB');
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
		$collectData = array(
			'mbparam'			=> '',
			'init_headers'		=> $this->create_curl_headers($this->headers),
			'collect'			=> array(),
			'account'			=> array(
				'username'			=> $username,
				'password'			=> $password,
			),
		);
		$this->set_curl_init($collectData['init_headers'], $username);
		$this->curlexec();
		$collectData['collect']['init'] = array(
			'browser'	=> $this->curlcontent,
		);
		$collectData['browser_ajax_params'] = array(
			'AJAXSupport'			=> 'YES',
			'browsercodename'		=> 'Mozilla',
			'availheight'			=> '640',
			'availwidth'			=> '320',
			'browsername'			=> 'Netscape',
			'browserversion'		=> '5.0+(MobileOS)',
			'category'				=> 'tablet',
			'colordepth'			=> '24',
			'deviceDetected'		=> 'true',
			'height'				=> '320',
			'mconnectUrl'			=> '/MBAWeb/FMB',
			'pixeldepth'			=> '24',
			'platform'				=> 'Win64',
			'useragent'				=> $this->curl_options['user_agent'],
			'width'					=> '640',
		);
		curl_setopt($this->ch, CURLOPT_URL, 'https://ibank.bni.co.id/MBAWeb/FMB');
		curl_setopt($this->ch, CURLOPT_REFERER, 'https://ibank.bni.co.id/MBAWeb/FMB');
		curl_setopt($this->ch, CURLOPT_POST, TRUE);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($collectData['browser_ajax_params']));
		$this->curlexec();
		$collectData['collect']['init']['formbuilder'] = $this->curlcontent;
		$collectData['rows'] = array(
			'dom'	=> array(),
			'form'	=> array(),
		);
		if (strlen($collectData['collect']['init']['formbuilder']) > 0) {
			libxml_use_internal_errors(true);
			$collectData['rows']['dom']['doc'] = new DOMDocument;
			$collectData['rows']['dom']['doc']->preserveWhiteSpace = false;
			$collectData['rows']['dom']['doc']->validateOnParse = false;
			$collectData['rows']['dom']['doc']->loadHTML($collectData['collect']['init']['formbuilder']);
			$collectData['rows']['dom']['xpath'] = new DOMXPath($collectData['rows']['dom']['doc']);
			$collectData['rows']['dom']['input'] = $collectData['rows']['dom']['xpath']->query("//input[@type='hidden']");
			if (isset($collectData['rows']['dom']['input']->length)) {
				$collectData['rows']['dom']['items'] = array();
				for ($input_i = 0; $input_i < $collectData['rows']['dom']['input']->length; $input_i++) {
					$collectData['rows']['dom']['items'][$input_i] = array(
						'dom_element'		=> $collectData['rows']['dom']['input']->item($input_i),
					);
					$collectData['rows']['dom']['items'][$input_i]['attributes'] = array(
						'id'		=> $collectData['rows']['dom']['items'][$input_i]['dom_element']->getAttribute('id'),
						'name'		=> $collectData['rows']['dom']['items'][$input_i]['dom_element']->getAttribute('name'),
						'value'		=> $collectData['rows']['dom']['items'][$input_i]['dom_element']->getAttribute('value'),
					);
				}
			}
		}
		//--
		if (isset($collectData['rows']['dom']['items'])) {
			$collectData['rows']['form']['url'] = "";
			$collectData['rows']['form']['query_params'] = array();
			if (is_array($collectData['rows']['dom']['items']) && (count($collectData['rows']['dom']['items']) > 0)) {
				$for_i = 0;
				foreach ($collectData['rows']['dom']['items'] as $itemval) {
					$collectData['rows']['form']['query_params'][$for_i] = array();
					if (isset($itemval['attributes'])) {
						if (is_array($itemval['attributes']) && (count($itemval['attributes']) > 0)) {
							foreach ($itemval['attributes'] as $attrkey => $attrval) {
								if (strtolower($attrkey) === strtolower('name')) {
									$collectData['rows']['form']['query_params'][$for_i]['name'] = $attrval;
									if ($attrval == 'formAction') {
										$collectData['rows']['form']['url'] = $collectData['rows']['dom']['items'][$for_i]['attributes']['value'];
									}
								} else if (strtolower($attrkey) === strtolower('value')) {
									$collectData['rows']['form']['query_params'][$for_i]['value'] = $attrval;
								} else {
									$collectData['rows']['form']['query_params'][$for_i]['id'] = $attrval;
								}
							}
						}
					}
					$for_i += 1;
				}
			}
			//====
			$collectData['form_data'] = array(
				'url'		=> ((strlen($collectData['rows']['form']['url']) > 0) ? $collectData['rows']['form']['url'] : ''),
				'params'	=> array(),
			);
			//====
			if (is_array($collectData['rows']['form']['query_params']) && (count($collectData['rows']['form']['query_params']) > 0)) {
				foreach ($collectData['rows']['form']['query_params'] as $paramval) {
					if (isset($paramval['name'])) {
						$paramskey = $paramval['name'];
						$collectData['form_data']['params'][$paramskey] = (isset($paramval['value']) ? $paramval['value'] : '');
					}
				}
			}
			if (strlen($collectData['form_data']['url']) > 0) {
				curl_setopt($this->ch, CURLOPT_URL, $collectData['form_data']['url']);
				curl_setopt($this->ch, CURLOPT_REFERER, 'https://ibank.bni.co.id/MBAWeb/FMB');
				curl_setopt($this->ch, CURLOPT_POST, TRUE);
				curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($collectData['form_data']['params']));
				$this->curlexec();
				$collectData['form_data']['response'] = $this->curlcontent;
				// Get login page URL
				if (strlen($collectData['form_data']['response']) > 0) {
					libxml_use_internal_errors(true);
					$collectData['form_data']['dom'] = array('doc'	=> new DOMDocument);
					$collectData['form_data']['dom']['doc']->preserveWhiteSpace = false;
					$collectData['form_data']['dom']['doc']->validateOnParse = false;
					$collectData['form_data']['dom']['doc']->loadHTML($collectData['form_data']['response']);
					$collectData['form_data']['dom']['xpath'] = new DOMXPath($collectData['form_data']['dom']['doc']);
					$collectData['form_data']['dom']['a'] = $collectData['form_data']['dom']['xpath']->query("//a[@id='Login']");
					if (isset($collectData['form_data']['dom']['a']->length)) {
						$collectData['form_data']['dom']['items'] = array();
						for ($input_i = 0; $input_i < $collectData['form_data']['dom']['a']->length; $input_i++) {
							$collectData['form_data']['dom']['items'][$input_i] = array(
								'dom_element'		=> $collectData['form_data']['dom']['a']->item($input_i),
							);
							$collectData['form_data']['dom']['items'][$input_i]['attributes'] = array(
								'id'		=> $collectData['form_data']['dom']['items'][$input_i]['dom_element']->getAttribute('id'),
								'name'		=> $collectData['form_data']['dom']['items'][$input_i]['dom_element']->getAttribute('name'),
								'href'		=> $collectData['form_data']['dom']['items'][$input_i]['dom_element']->getAttribute('href'),
							);
						}
					}
				}
				// Go to login page
				$collectData['form_data']['login_url'] = '';
				if (isset($collectData['form_data']['dom']['items'])) {
					if (is_array($collectData['form_data']['dom']['items']) && (count($collectData['form_data']['dom']['items']) > 0)) {
						foreach ($collectData['form_data']['dom']['items'] as $loginval) {
							if (isset($loginval['attributes']['href'])) {
								$collectData['form_data']['login_url'] = $loginval['attributes']['href'];
							}
						}
					}
				}
				if (strlen($collectData['form_data']['login_url']) > 0) {
					$collectData['form_data']['login_url'] = str_replace('HomePage.xml', 'Thin_SignOnRetRq.xml', $collectData['form_data']['login_url']);
					curl_setopt($this->ch, CURLOPT_URL, $collectData['form_data']['login_url']);
					curl_setopt($this->ch, CURLOPT_REFERER, $collectData['form_data']['url']);
					curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'GET');
					curl_setopt($this->ch, CURLOPT_POST, FALSE);
					$this->curlexec();
					$collectData['form_data']['login_page'] = $this->curlcontent;
					if (strlen($collectData['form_data']['login_page']) > 0) {
						$collectData['form_data']['dom']['doc']->loadHTML($collectData['form_data']['login_page']);
						$collectData['form_data']['dom']['xpath'] = new DOMXPath($collectData['form_data']['dom']['doc']);
						$collectData['form_data']['dom']['login'] = array(
							'input'		=> $collectData['form_data']['dom']['xpath']->query("//input"),
							'form'		=> $collectData['form_data']['dom']['xpath']->query("//form[@id='form']"),
						);
						$collectData['form_data']['dom']['login_items'] = array(
							'form'		=> array(),
							'input'		=> array(),
						);
						if (isset($collectData['form_data']['dom']['login']['form']->length)) {
							for ($input_i = 0; $input_i < $collectData['form_data']['dom']['login']['form']->length; $input_i++) {
								$collectData['form_data']['dom']['login_items']['form'][$input_i] = array(
									'dom_element'		=> $collectData['form_data']['dom']['login']['form']->item($input_i),
								);
								$collectData['form_data']['dom']['login_items']['form'][$input_i]['attributes'] = array(
									'action'	=> $collectData['form_data']['dom']['login_items']['form'][$input_i]['dom_element']->getAttribute('action'),
									'id'		=> $collectData['form_data']['dom']['login_items']['form'][$input_i]['dom_element']->getAttribute('id'),
								);
							}
						}
						if (isset($collectData['form_data']['dom']['login']['input']->length)) {
							for ($input_i = 0; $input_i < $collectData['form_data']['dom']['login']['input']->length; $input_i++) {
								$collectData['form_data']['dom']['login_items']['input'][$input_i] = array(
									'dom_element'		=> $collectData['form_data']['dom']['login']['input']->item($input_i),
								);
								$collectData['form_data']['dom']['login_items']['input'][$input_i]['attributes'] = array(
									'value'		=> $collectData['form_data']['dom']['login_items']['input'][$input_i]['dom_element']->getAttribute('value'),
									'id'		=> $collectData['form_data']['dom']['login_items']['input'][$input_i]['dom_element']->getAttribute('id'),
									'name'		=> $collectData['form_data']['dom']['login_items']['input'][$input_i]['dom_element']->getAttribute('name'),
								);
							}
						}
						// Execute Login
						$collectData['form_data']['form'] = array(
							'url'				=> '',
							'query_params'		=> array(),
						);
						if (isset($collectData['form_data']['dom']['login_items']['input']) && isset($collectData['form_data']['dom']['login_items']['form'])) {
							if (is_array($collectData['form_data']['dom']['login_items']['input']) && (count($collectData['form_data']['dom']['login_items']['input']) > 0)) {
								$for_i = 0;
								foreach ($collectData['form_data']['dom']['login_items']['input'] as $itemval) {
									$collectData['form_data']['form']['query_params'][$for_i] = array();
									if (isset($itemval['attributes'])) {
										if (is_array($itemval['attributes']) && (count($itemval['attributes']) > 0)) {
											foreach ($itemval['attributes'] as $attrkey => $attrval) {
												if (strtolower($attrkey) === strtolower('name')) {
													$collectData['form_data']['form']['query_params'][$for_i]['name'] = $attrval;
												} else if (strtolower($attrkey) === strtolower('value')) {
													$collectData['form_data']['form']['query_params'][$for_i]['value'] = $attrval;
												} else {
													$collectData['form_data']['form']['query_params'][$for_i]['id'] = $attrval;
												}
											}
										}
									}
									$for_i += 1;
								}
							}
							if (is_array($collectData['form_data']['dom']['login_items']['form']) && (count($collectData['form_data']['dom']['login_items']['form']) > 0)) {
								$for_i = 0;
								foreach ($collectData['form_data']['dom']['login_items']['form'] as $itemval) {
									if (isset($itemval['attributes'])) {
										if (is_array($itemval['attributes']) && (count($itemval['attributes']) > 0)) {
											foreach ($itemval['attributes'] as $attrkey => $attrval) {
												if (strtolower($attrkey) === strtolower('action')) {
													$collectData['form_data']['form']['url'] = $attrval;
												}
											}
										}
									}
									$for_i += 1;
								}
							}
						}
						//====
						$collectData['form_data']['login_execute'] = array(
							'url'		=> '',
							'params'	=> array(),
						);
						//====
						if (strlen($collectData['form_data']['form']['url']) > 0) {
							$collectData['form_data']['login_execute']['url'] = $collectData['form_data']['form']['url'];
						}
						if (is_array($collectData['form_data']['form']['query_params']) && (count($collectData['form_data']['form']['query_params']) > 0)) {
							foreach ($collectData['form_data']['form']['query_params'] as $paramval) {
								if (isset($paramval['name'])) {
									$paramskey = $paramval['name'];
									if ($paramskey == 'CorpId') {
										$collectData['form_data']['login_execute']['params'][$paramskey] = $collectData['account']['username'];
									} else if ($paramskey == 'PassWord') {
										$collectData['form_data']['login_execute']['params'][$paramskey] = $collectData['account']['password'];
									} else {
										$collectData['form_data']['login_execute']['params'][$paramskey] = (isset($paramval['value']) ? $paramval['value'] : '');
									}
								}
							}
						}
					}
				}
				//0000----0000----0000----0000----0000----0000----0000----0000----0000----0000----0000----0000
				//--------------------------------------------------------------------------------------------
				if (isset($collectData['form_data']['login_execute'])) {
					if (isset($collectData['form_data']['login_execute']['params']['formAction'])) {
						unset($collectData['form_data']['login_execute']['params']['formAction']);
					}
					if (isset($collectData['form_data']['login_execute']['params']['mConnectUrl'])) {
						unset($collectData['form_data']['login_execute']['params']['mConnectUrl']);
					}
					if (strlen($collectData['form_data']['login_execute']['url']) > 0) {
						curl_setopt($this->ch, CURLOPT_URL, $collectData['form_data']['login_execute']['url']);
						curl_setopt($this->ch, CURLOPT_REFERER, $collectData['form_data']['login_url']);
						curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'POST');
						curl_setopt($this->ch, CURLOPT_POST, TRUE);
						curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($collectData['form_data']['login_execute']['params']));
						$this->curlexec();
						$collectData['form_data']['login_execute']['response'] = $this->curlcontent;
						libxml_use_internal_errors(true);
						$collectData['form_data']['login_execute']['response_dom'] = array('doc' => new DOMDocument);
						$collectData['form_data']['login_execute']['response_dom']['doc']->preserveWhiteSpace = false;
						$collectData['form_data']['login_execute']['response_dom']['doc']->validateOnParse = false;
						$collectData['form_data']['login_execute']['response_dom']['doc']->loadHTML($collectData['form_data']['login_execute']['response']);
						$collectData['form_data']['login_execute']['response_dom']['xpath'] = new DOMXPath($collectData['form_data']['login_execute']['response_dom']['doc']);
						$collectData['form_data']['login_execute']['response_dom']['input'] = $collectData['form_data']['login_execute']['response_dom']['xpath']->query("//input");
						$collectData['form_data']['login_execute']['response_dom']['form'] = $collectData['form_data']['login_execute']['response_dom']['xpath']->query("//form");
						$collectData['form_data']['login_execute']['response_dom']['items'] = array();
						if (isset($collectData['form_data']['login_execute']['response_dom']['input']->length)) {
							for ($input_i = 0; $input_i < $collectData['form_data']['login_execute']['response_dom']['input']->length; $input_i++) {
								$collectData['form_data']['login_execute']['response_dom']['items'][$input_i] = array(
									'dom_element'		=> $collectData['form_data']['login_execute']['response_dom']['input']->item($input_i),
								);
								$collectData['form_data']['login_execute']['response_dom']['items'][$input_i]['attributes'] = array(
									'name'	=> $collectData['form_data']['login_execute']['response_dom']['items'][$input_i]['dom_element']->getAttribute('name'),
									'value'	=> $collectData['form_data']['login_execute']['response_dom']['items'][$input_i]['dom_element']->getAttribute('value'),
									'id'	=> $collectData['form_data']['login_execute']['response_dom']['items'][$input_i]['dom_element']->getAttribute('id'),
								);
							}
						}
						$collectData['form_data']['login_execute']['response_dom']['form_items'] = array();
						if (isset($collectData['form_data']['login_execute']['response_dom']['form']->length)) {
							for ($input_i = 0; $input_i < $collectData['form_data']['login_execute']['response_dom']['form']->length; $input_i++) {
								$collectData['form_data']['login_execute']['response_dom']['form_items'][$input_i] = array(
									'dom_element'		=> $collectData['form_data']['login_execute']['response_dom']['form']->item($input_i),
								);
								$collectData['form_data']['login_execute']['response_dom']['form_items'][$input_i]['attributes'] = array(
									'action'	=> $collectData['form_data']['login_execute']['response_dom']['form_items'][$input_i]['dom_element']->getAttribute('action'),
									'id'		=> $collectData['form_data']['login_execute']['response_dom']['form_items'][$input_i]['dom_element']->getAttribute('id'),
								);
							}
						}
						// Execute Logout
						$collectData['form_data']['form_logout'] = array(
							'url'				=> '',
							'query_params'		=> array(),
						);
						if (isset($collectData['form_data']['login_execute']['response_dom']['form_items']) && isset($collectData['form_data']['login_execute']['response_dom']['items'])) {
							if (is_array($collectData['form_data']['login_execute']['response_dom']['form_items']) && (count($collectData['form_data']['login_execute']['response_dom']['form_items']) > 0)) {
								$for_i = 0;
								foreach ($collectData['form_data']['login_execute']['response_dom']['form_items'] as $itemval) {
									if (isset($itemval['attributes'])) {
										if (is_array($itemval['attributes']) && (count($itemval['attributes']) > 0)) {
											foreach ($itemval['attributes'] as $attrkey => $attrval) {
												if (strtolower($attrkey) === strtolower('action')) {
													$collectData['form_data']['form_logout']['url'] = $attrval;
												}
												if (strtolower($attrkey) === strtolower('mbparam')) {
													$this->set_mbparam($attrval);
													$collectData['mbparam'] = $this->mbparam;
												}
											}
										}
									}
									$for_i += 1;
								}
							}
							if (is_array($collectData['form_data']['login_execute']['response_dom']['items']) && (count($collectData['form_data']['login_execute']['response_dom']['items']) > 0)) {
								$for_i = 0;
								foreach ($collectData['form_data']['login_execute']['response_dom']['items'] as $itemval) {
									$collectData['form_data']['form_logout']['query_params'][$for_i] = array();
									if (isset($itemval['attributes'])) {
										if (is_array($itemval['attributes']) && (count($itemval['attributes']) > 0)) {
											foreach ($itemval['attributes'] as $attrkey => $attrval) {
												if (strtolower($attrkey) === strtolower('name')) {
													$collectData['form_data']['form_logout']['query_params'][$for_i]['name'] = $attrval;
												} else if (strtolower($attrkey) === strtolower('value')) {
													$collectData['form_data']['form_logout']['query_params'][$for_i]['value'] = $attrval;
												} else {
													$collectData['form_data']['form_logout']['query_params'][$for_i]['id'] = $attrval;
												}
											}
										}
									}
									$for_i += 1;
								}
							}
						}
					}
				}
			}
		}
		
		return $collectData;
    }
	function logout($logout_url = '', $logout_params = array()) {
		$collectData = array(
			'form'		=> array(
				'url'		=> (is_string($logout_url) || is_numeric($logout_url)) ? sprintf('%s', $logout_url) : '',
				'params'	=> array(),
			),
		);
		
		if (is_array($logout_params) && (count($logout_params) > 0)) {
			foreach ($logout_params as $paramval) {
				if (isset($paramval['name'])) {
					$paramskey = $paramval['name'];
					if (!in_array($paramskey, array('mConnectUrl', 'formAction'))) {
						$collectData['form']['params'][$paramskey] = (isset($paramval['value']) ? $paramval['value'] : '');
					}
				}
			}
		}
		// Page Logout
		if (strlen($collectData['form']['url']) > 0) {
			curl_setopt($this->ch, CURLOPT_URL, $collectData['form']['url']);
			curl_setopt($this->ch, CURLOPT_REFERER, $collectData['form']['url']);
			curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($this->ch, CURLOPT_POST, TRUE);
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($collectData['form']['params']));
			$this->curlexec();
			libxml_use_internal_errors(true);
			$collectData['form']['logout'] = array('doc' => new DOMDocument);
			$collectData['form']['logout']['doc']->preserveWhiteSpace = false;
			$collectData['form']['logout']['doc']->validateOnParse = false;
			$collectData['form']['logout']['doc']->loadHTML($this->curlcontent);
			$collectData['form']['logout']['xpath'] = new DOMXPath($collectData['form']['logout']['doc']);
			$collectData['form']['logout']['input'] = $collectData['form']['logout']['xpath']->query("//input");
			$collectData['form']['logout']['form_data'] = array(
				'action'			=> '',
				'items'				=> array(),
				'query_params'		=> array(),
			);
			if (isset($collectData['form']['logout']['input']->length)) {
				for ($input_i = 0; $input_i < $collectData['form']['logout']['input']->length; $input_i++) {
					$collectData['form']['logout']['form_data']['items'][$input_i] = array(
						'dom_element'		=> $collectData['form']['logout']['input']->item($input_i),
					);
					$collectData['form']['logout']['form_data']['items'][$input_i]['attributes'] = array(
						'id'		=> $collectData['form']['logout']['form_data']['items'][$input_i]['dom_element']->getAttribute('id'),
						'name'		=> $collectData['form']['logout']['form_data']['items'][$input_i]['dom_element']->getAttribute('name'),
						'value'		=> $collectData['form']['logout']['form_data']['items'][$input_i]['dom_element']->getAttribute('value'),
					);
					$param_key = $collectData['form']['logout']['form_data']['items'][$input_i]['attributes']['name'];
					if (strtolower($param_key) === strtolower('formAction')) {
						$collectData['form']['logout']['form_data']['action'] = $collectData['form']['logout']['form_data']['items'][$input_i]['dom_element']->getAttribute('value');
					}
					$collectData['form']['logout']['form_data']['query_params'][$param_key] = $collectData['form']['logout']['form_data']['items'][$input_i]['dom_element']->getAttribute('value');
				}
			}
			curl_setopt($this->ch, CURLOPT_URL, $collectData['form']['logout']['form_data']['action']);
			curl_setopt($this->ch, CURLOPT_REFERER, $collectData['form']['url']);
			curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($this->ch, CURLOPT_POST, TRUE);
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($collectData['form']['logout']['form_data']['query_params']));
			$this->curlexec();
			$collectData['form']['manual_logout'] = array('doc' => new DOMDocument);
			$collectData['form']['manual_logout']['doc']->preserveWhiteSpace = false;
			$collectData['form']['manual_logout']['doc']->validateOnParse = false;
			$collectData['form']['manual_logout']['doc']->loadHTML($this->curlcontent);
			$collectData['form']['manual_logout']['xpath'] = new DOMXPath($collectData['form']['manual_logout']['doc']);
			$collectData['form']['manual_logout']['input'] = $collectData['form']['manual_logout']['xpath']->query("//input");
			$collectData['form']['manual_logout']['form_data'] = array(
				'items'				=> array(),
				'query_params'		=> array(),
			);
			if (isset($collectData['form']['manual_logout']['input']->length)) {
				for ($input_i = 0; $input_i < $collectData['form']['manual_logout']['input']->length; $input_i++) {
					$collectData['form']['manual_logout']['form_data']['items'][$input_i] = array(
						'dom_element'		=> $collectData['form']['manual_logout']['input']->item($input_i),
					);
					$collectData['form']['manual_logout']['form_data']['items'][$input_i]['attributes'] = array(
						'id'		=> $collectData['form']['manual_logout']['form_data']['items'][$input_i]['dom_element']->getAttribute('id'),
						'name'		=> $collectData['form']['manual_logout']['form_data']['items'][$input_i]['dom_element']->getAttribute('name'),
						'value'		=> $collectData['form']['manual_logout']['form_data']['items'][$input_i]['dom_element']->getAttribute('value'),
					);
					$param_key = $collectData['form']['manual_logout']['form_data']['items'][$input_i]['attributes']['name'];
					$collectData['form']['manual_logout']['form_data']['query_params'][$param_key] = $collectData['form']['manual_logout']['form_data']['items'][$input_i]['dom_element']->getAttribute('value');
				}
			}
			$collectData['form']['manual_logout']['query_params'] = array(
				'Num_Field_Err'				=> '"Please enter digits only!"',
				'Mand_Field_Err'			=> '"Mandatory field is empty!"',
				'__LOGOUT__'				=> 'Keluar',
				'mbparam'					=> (isset($collectData['form']['manual_logout']['form_data']['query_params']['mbparam']) ? $collectData['form']['manual_logout']['form_data']['query_params']['mbparam'] : ''),
				'uniqueURLStatus'			=> 'disabled',
				'imc_service_page'			=> 'SignOffUrlRq',
				'Alignment'					=> 'LEFT',
				'page'						=> 'SignOffUrlRq',
				'locale'					=> 'bh',
				'PageName'					=> 'LoginRs',
				'serviceType'				=> 'Dynamic',
			);
			curl_setopt($this->ch, CURLOPT_URL, $collectData['form']['logout']['form_data']['action']);
			curl_setopt($this->ch, CURLOPT_REFERER, $collectData['form']['logout']['form_data']['action']);
			curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($this->ch, CURLOPT_POST, TRUE);
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($collectData['form']['manual_logout']['query_params']));
			$this->curlexec();
			$collectData['form']['logout_done'] = $this->curlcontent;
			curl_close($this->ch);
		}
		return $collectData;
    }
	
    //====================
	// BNI Actions
	function get_informasi_rekening($url, $query_params = array()) {
		$collectData = array(
			'url_ref'			=> $url,
			'url'				=> '',
			'mbparam'			=> '',
			'query_params'		=> array(),
			'jsessions'			=> array(),
			'jsessionid'		=> '',
			'form'				=> array(
				'url'		=> (is_string($url) || is_numeric($url)) ? sprintf('%s', $url) : '',
				'params'	=> array(),
			),
		);
		if (strlen($url) > 0) {
			$collectData['parsed_querystring'] = parse_url($url);
			if (isset($collectData['parsed_querystring']['path'])) {
				$collectData['collect'] = explode(";", $collectData['parsed_querystring']['path']);
				if (isset($collectData['collect'][1])) {
					parse_str($collectData['collect'][1], $collectData['jsessions']);
					$collectData['jsessionid'] = (isset($collectData['jsessions']['jsessionid']) ? $this->set_jsessionid($collectData['jsessions']['jsessionid']) : '');
				}
			}
		}
		if (is_array($query_params) && (count($query_params) > 0)) {
			foreach ($query_params as $paramval) {
				if (isset($paramval['name'])) {
					$paramskey = $paramval['name'];
					if (!in_array($paramskey, array('mConnectUrl', 'formAction'))) {
						$collectData['query_params'][$paramskey] = (isset($paramval['value']) ? $paramval['value'] : '');
					}
				}
			}
		}
		if (isset($collectData['query_params']['mbparam'])) {
			$collectData['mbparam'] = $collectData['query_params']['mbparam'];
			$collectData['url'] = "https://ibank.bni.co.id/MBAWeb/FMB;jsessionid=[##JSESSIONID##]?page=AccountsUrlRq&mbparam={$collectData['query_params']['mbparam']}";
			if (isset($collectData['jsessionid'])) {
				$collectData['url'] = str_replace('[##JSESSIONID##]', $collectData['jsessionid'], $collectData['url']);
			}
			curl_setopt($this->ch, CURLOPT_URL, $collectData['form']['url']);
			curl_setopt($this->ch, CURLOPT_REFERER, $collectData['form']['url']);
			curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($this->ch, CURLOPT_POST, TRUE);
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($collectData['query_params']));
			$this->curlexec();
			$collectData['rekening_page'] = $this->curlcontent;
			libxml_use_internal_errors(true);
			$collectData['rekening_dom'] = array('doc' => new DOMDocument);
			$collectData['rekening_dom']['doc']->preserveWhiteSpace = false;
			$collectData['rekening_dom']['doc']->validateOnParse = false;
			$collectData['rekening_dom']['doc']->loadHTML($collectData['rekening_page']);
			$collectData['rekening_dom']['xpath'] = new DOMXPath($collectData['rekening_dom']['doc']);
			$collectData['rekening_dom']['a'] = $collectData['rekening_dom']['xpath']->query("//a[@id='db_acc1']");
			$collectData['rekening_dom']['items'] = array();
			if (isset($collectData['rekening_dom']['a']->length)) {
				for ($input_i = 0; $input_i < $collectData['rekening_dom']['a']->length; $input_i++) {
					$collectData['rekening_dom']['items'][$input_i] = array(
						'dom_element'		=> $collectData['rekening_dom']['a']->item($input_i),
					);
					$collectData['rekening_dom']['items'][$input_i]['attributes'] = array(
						'id'		=> $collectData['rekening_dom']['items'][$input_i]['dom_element']->getAttribute('id'),
						'href'		=> $collectData['rekening_dom']['items'][$input_i]['dom_element']->getAttribute('href'),
						'node'		=> $collectData['rekening_dom']['items'][$input_i]['dom_element']->nodeValue,
					);
				}
			}
		}
		if (isset($collectData['rekening_dom']['items'])) {
			if (is_array($collectData['rekening_dom']['items']) && (count($collectData['rekening_dom']['items']) > 0)) {
				foreach ($collectData['rekening_dom']['items'] as $itemval) {
					if (isset($itemval['attributes']['node'])) {
						return sprintf("%s", $itemval['attributes']['node']);
					}
				}
			}
		}
		return false;
	}
	function get_mutasi_transactions($url, $query_params, $transaction_datetime = array()) {
		$collectData = array(
			'url_ref'			=> $url,
			'url'				=> '',
			'mbparam'			=> '',
			'query_params'		=> array(),
			'jsessions'			=> array(),
			'jsessionid'		=> '',
			'form'				=> array(
				'url'		=> (is_string($url) || is_numeric($url)) ? sprintf('%s', $url) : '',
				'params'	=> array(),
			),
			'url_transaction'	=> '',
		);
		if (strlen($url) > 0) {
			$collectData['parsed_querystring'] = parse_url($url);
			if (isset($collectData['parsed_querystring']['path'])) {
				$collectData['collect'] = explode(";", $collectData['parsed_querystring']['path']);
				if (isset($collectData['collect'][1])) {
					parse_str($collectData['collect'][1], $collectData['jsessions']);
					$collectData['jsessionid'] = (isset($collectData['jsessions']['jsessionid']) ? $this->set_jsessionid($collectData['jsessions']['jsessionid']) : '');
				}
			}
		}
		if (is_array($query_params) && (count($query_params) > 0)) {
			foreach ($query_params as $paramval) {
				if (isset($paramval['name'])) {
					$paramskey = $paramval['name'];
					if (!in_array($paramskey, array('mConnectUrl', 'formAction'))) {
						$collectData['query_params'][$paramskey] = (isset($paramval['value']) ? $paramval['value'] : '');
					}
				}
			}
		}
		//----
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
		//----
		$collectData['collect'] = array();
		if (isset($collectData['query_params']['mbparam'])) {
			$collectData['mbparam'] = $collectData['query_params']['mbparam'];
			$collectData['url'] = "https://ibank.bni.co.id/MBAWeb/FMB;jsessionid=[##JSESSIONID##]?page=AccountsUrlRq&mbparam={$collectData['query_params']['mbparam']}";
			if (isset($collectData['jsessionid'])) {
				$collectData['url'] = str_replace('[##JSESSIONID##]', $collectData['jsessionid'], $collectData['url']);
			}
			curl_setopt($this->ch, CURLOPT_URL, $collectData['form']['url']);
			curl_setopt($this->ch, CURLOPT_REFERER, $collectData['form']['url']);
			curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($this->ch, CURLOPT_POST, TRUE);
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($collectData['query_params']));
			$this->curlexec();
			curl_setopt($this->ch, CURLOPT_URL, $collectData['url']);
			curl_setopt($this->ch, CURLOPT_REFERER, $collectData['form']['url']);
			curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'GET');
			curl_setopt($this->ch, CURLOPT_POST, FALSE);
			$this->curlexec();
			$collectData['collect']['rekening_page'] = $this->curlcontent;
			libxml_use_internal_errors(true);
			$collectData['rekening_dom'] = array('doc' => new DOMDocument);
			$collectData['rekening_dom']['doc']->preserveWhiteSpace = false;
			$collectData['rekening_dom']['doc']->validateOnParse = false;
			$collectData['rekening_dom']['doc']->loadHTML($collectData['collect']['rekening_page']);
			$collectData['rekening_dom']['xpath'] = new DOMXPath($collectData['rekening_dom']['doc']);
			$collectData['rekening_dom']['a'] = $collectData['rekening_dom']['xpath']->query("//a[@id='AccountMenuList']");
			$collectData['rekening_dom']['items'] = array();
			if (isset($collectData['rekening_dom']['a']->length)) {
				for ($input_i = 0; $input_i < $collectData['rekening_dom']['a']->length; $input_i++) {
					$collectData['rekening_dom']['items'][$input_i] = array(
						'dom_element'		=> $collectData['rekening_dom']['a']->item($input_i),
					);
					$collectData['rekening_dom']['items'][$input_i]['attributes'] = array(
						'id'		=> $collectData['rekening_dom']['items'][$input_i]['dom_element']->getAttribute('id'),
						'href'		=> $collectData['rekening_dom']['items'][$input_i]['dom_element']->getAttribute('href'),
					);
				}
			}
			if (isset($collectData['rekening_dom']['items'][2]['attributes']['href'])) {
				curl_setopt($this->ch, CURLOPT_URL, $collectData['rekening_dom']['items'][2]['attributes']['href']);
				curl_setopt($this->ch, CURLOPT_REFERER, $collectData['url']);
				curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'GET');
				curl_setopt($this->ch, CURLOPT_POST, FALSE);
				$this->curlexec();
				$collectData['collect']['rekening_page_form'] = $this->curlcontent;
				$collectData['rekening_dom']['doc'] = new DOMDocument;
				$collectData['rekening_dom']['query_params'] = array();
				libxml_use_internal_errors(true);
				$collectData['rekening_dom']['doc']->preserveWhiteSpace = false;
				$collectData['rekening_dom']['doc']->validateOnParse = false;
				$collectData['rekening_dom']['doc']->loadHTML($collectData['collect']['rekening_page_form']);
				$collectData['rekening_dom']['xpath'] = new DOMXPath($collectData['rekening_dom']['doc']);
				$collectData['rekening_dom']['input'] = $collectData['rekening_dom']['xpath']->query("//input");
				$collectData['rekening_dom']['input_items'] = array();
				if (isset($collectData['rekening_dom']['input']->length)) {
					for ($input_i = 0; $input_i < $collectData['rekening_dom']['input']->length; $input_i++) {
						$collectData['rekening_dom']['input_items'][$input_i] = array(
							'dom_element'		=> $collectData['rekening_dom']['input']->item($input_i),
						);
						$collectData['rekening_dom']['input_items'][$input_i]['attributes'] = array(
							'id'		=> $collectData['rekening_dom']['input_items'][$input_i]['dom_element']->getAttribute('id'),
							'href'		=> $collectData['rekening_dom']['input_items'][$input_i]['dom_element']->getAttribute('href'),
							'node'		=> $collectData['rekening_dom']['input_items'][$input_i]['dom_element']->nodeValue,
							'name'		=> $collectData['rekening_dom']['input_items'][$input_i]['dom_element']->getAttribute('name'),
							'value'		=> $collectData['rekening_dom']['input_items'][$input_i]['dom_element']->getAttribute('value'),
						);
						$param_key = $collectData['rekening_dom']['input_items'][$input_i]['attributes']['name'];
						if (!in_array($param_key, array('LogOut', '__HOME__', '__BACK__'))) {
							$collectData['rekening_dom']['query_params'][$param_key] = $collectData['rekening_dom']['input_items'][$input_i]['dom_element']->getAttribute('value');
						}
					}
				}
				//------------
				$collectData['rekening_dom']['query_params']['MAIN_ACCOUNT_TYPE'] = 'OPR'; // Currenctly only "Tabungan & Giro" is supported
				//------------
				curl_setopt($this->ch, CURLOPT_URL, $collectData['rekening_dom']['query_params']['formAction']);
				curl_setopt($this->ch, CURLOPT_REFERER, $collectData['rekening_dom']['items'][2]['attributes']['href']);
				curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'POST');
				curl_setopt($this->ch, CURLOPT_POST, TRUE);
				unset($collectData['rekening_dom']['query_params']['formAction']);
				unset($collectData['rekening_dom']['query_params']['mConnectUrl']);
				curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($collectData['rekening_dom']['query_params']));
				$this->curlexec();
				$collectData['rekening_dom']['doc'] = new DOMDocument;
				$collectData['rekening_dom']['query_params'] = array();
				$collectData['rekening_dom']['logout_params'] = array();
				libxml_use_internal_errors(true);
				$collectData['rekening_dom']['doc']->preserveWhiteSpace = false;
				$collectData['rekening_dom']['doc']->validateOnParse = false;
				$collectData['rekening_dom']['doc']->loadHTML($this->curlcontent);
				$collectData['rekening_dom']['xpath'] = new DOMXPath($collectData['rekening_dom']['doc']);
				$collectData['rekening_dom']['input'] = $collectData['rekening_dom']['xpath']->query("//input");
				$collectData['rekening_dom']['input_items'] = array();
				if (isset($collectData['rekening_dom']['input']->length)) {
					for ($input_i = 0; $input_i < $collectData['rekening_dom']['input']->length; $input_i++) {
						$collectData['rekening_dom']['input_items'][$input_i] = array(
							'dom_element'		=> $collectData['rekening_dom']['input']->item($input_i),
						);
						$collectData['rekening_dom']['input_items'][$input_i]['attributes'] = array(
							'id'		=> $collectData['rekening_dom']['input_items'][$input_i]['dom_element']->getAttribute('id'),
							'href'		=> $collectData['rekening_dom']['input_items'][$input_i]['dom_element']->getAttribute('href'),
							'node'		=> $collectData['rekening_dom']['input_items'][$input_i]['dom_element']->nodeValue,
							'name'		=> $collectData['rekening_dom']['input_items'][$input_i]['dom_element']->getAttribute('name'),
							'value'		=> $collectData['rekening_dom']['input_items'][$input_i]['dom_element']->getAttribute('value'),
						);
						$param_key = $collectData['rekening_dom']['input_items'][$input_i]['attributes']['name'];
						if (!in_array($param_key, array('LogOut', 'mConnectUrl'))) {
							$collectData['rekening_dom']['query_params'][$param_key] = $collectData['rekening_dom']['input_items'][$input_i]['dom_element']->getAttribute('value');
						}
						$collectData['rekening_dom']['logout_params'][$input_i] = array(
							'name'		=> $collectData['rekening_dom']['input_items'][$input_i]['dom_element']->getAttribute('name'),
							'value'		=> $collectData['rekening_dom']['input_items'][$input_i]['dom_element']->getAttribute('value'),
						);	
					}
				}
				//------------
				$collectData['rekening_dom']['query_params']['MAIN_ACCOUNT_TYPE'] = 'OPR'; // Currenctly only "Tabungan & Giro" is supported
				$collectData['rekening_dom']['query_params']['TxnPeriod'] = '-1';
				// Transaction Date
				$collectData['rekening_dom']['query_params']['txnSrcFromDate'] = $transaction_datetime['starting']->format('d-M-Y');
				$collectData['rekening_dom']['query_params']['txnSrcToDate'] = $transaction_datetime['stopping']->format('d-M-Y');
				//------------
				curl_setopt($this->ch, CURLOPT_URL, $collectData['rekening_dom']['query_params']['formAction']);
				curl_setopt($this->ch, CURLOPT_REFERER, $collectData['rekening_dom']['query_params']['formAction']);
				curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'POST');
				curl_setopt($this->ch, CURLOPT_POST, TRUE);
				unset($collectData['rekening_dom']['query_params']['formAction']);
				curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($collectData['rekening_dom']['query_params']));
				$this->curlexec();
				$collectData['transaction_data_html'] = $this->curlcontent;
			}
			// Parse HTML Transactions
			//========================
			if (isset($collectData['transaction_data_html'])) {
				$collectData['transaction_html'] = array(
					'doc' => new DOMDocument,
					'query_params' => array(),
				);
				libxml_use_internal_errors(true);
				$collectData['transaction_html']['doc']->preserveWhiteSpace = false;
				$collectData['transaction_html']['doc']->validateOnParse = false;
				$collectData['transaction_html']['doc']->loadHTML($collectData['transaction_data_html']);
				$collectData['transaction_html']['xpath'] = new DOMXPath($collectData['transaction_html']['doc']);
				$collectData['transaction_html']['rekening'] = $collectData['transaction_html']['xpath']->query("//td[@class='clsComboTd']");
				$collectData['informasi_rekening'] = array();
				if (isset($collectData['transaction_html']['rekening']->length)) {
					$informasi_rekening['rekening_currency'] = 'IDR';
					if ((int)$collectData['transaction_html']['rekening']->length > 5) {
						$collectData['transaction_rakening_name'] = $collectData['transaction_html']['rekening']->item(0);
						$collectData['informasi_rekening']['rekening_name'] = explode('Selamat Datang', $collectData['transaction_rakening_name']->nodeValue);
						$informasi_rekening['rekening_name'] = (isset($collectData['informasi_rekening']['rekening_name'][1]) ? trim($collectData['informasi_rekening']['rekening_name'][1]) : '');
						$collectData['transaction_rakening_number'] = $collectData['transaction_html']['rekening']->item(2);
						$collectData['informasi_rekening']['rekening_number'] = explode('Nomor Rekening', $collectData['transaction_rakening_number']->nodeValue);
						$informasi_rekening['rekening_number'] = (isset($collectData['informasi_rekening']['rekening_number'][1]) ? trim($collectData['informasi_rekening']['rekening_number'][1]) : '');
					}
				}
				$collectData['transaction_html']['td'] = array(
					'tanggal'		=> $collectData['transaction_html']['xpath']->query("//td[@id='s1']/span[@id='H']"),
					'description'	=> $collectData['transaction_html']['xpath']->query("//td[@id='s2']/span[@id='H']"),
					'type'			=> $collectData['transaction_html']['xpath']->query("//td[@id='s3']/span[@id='H']"),
					'amount'		=> $collectData['transaction_html']['xpath']->query("//td[@id='s4']/span[@id='H']"),
					'saldo'			=> $collectData['transaction_html']['xpath']->query("//td[@id='s5']/span[@id='H']"),
				);
				$collectData['transaction_data'] = array();
				$collectData['transaction_rows'] = array();
				foreach ($collectData['transaction_html']['td'] as $td_key => $td_val) {
					$collectData['transaction_data'][$td_key] = array();
					if (isset($td_val->length)) {
						$i = 0;
						switch (strtolower($td_key)) {
							case 'tanggal':
								$length_i = 1;
							break;
							case 'type':
								$length_i = 1;
							break;
							case 'description':
							default:
								$length_i = 0;
							break;
						}
						while ($length_i < $td_val->length) {
							$collectData['transaction_data'][$td_key][$i] = array(
								'dom'			=> $td_val->item($length_i),
							);
							$collectData['transaction_data'][$td_key][$i] = array(
								'dom'			=> $td_val->item($length_i),
							);
							$collectData['transaction_data'][$td_key][$i]['items'] = array(
								'id'			=> $collectData['transaction_data'][$td_key][$i]['dom']->getAttribute('id'),
								'node'			=> $collectData['transaction_data'][$td_key][$i]['dom']->nodeValue,
							);
							// Push to rows items
							$collectData['transaction_rows'][$i][$td_key] = $collectData['transaction_data'][$td_key][$i];
							$i += 1;
							switch (strtolower($td_key)) {
								case 'tanggal':
									$length_i += 2;
								break;
								case 'description':
								default:
									$length_i += 1;
								break;
							}
						}
					}
					// Sorting from max to min
					krsort($collectData['transaction_data'][$td_key], SORT_NUMERIC);
				}
				krsort($collectData['transaction_rows'], SORT_NUMERIC);
				// => Set Final Sort Array Data
				$collectData['rows'] = array(
					'items'		=> array(),
				);
				if (count($collectData['transaction_rows']) > 0) {
					$key = 0;
					foreach ($collectData['transaction_rows'] as $trans_key => $trans_val) {
						$collectData['rows']['items'][$key] = array(
							'transaction_date_string'	=> (isset($trans_val['tanggal']['items']['node']) ? $trans_val['tanggal']['items']['node'] : ''),
							'transaction_type'			=> 'PEND',
							'transaction_description'	=> (isset($trans_val['description']['items']['node']) ? $trans_val['description']['items']['node'] : ''),
							'transaction_amount_string'	=> (isset($trans_val['amount']['items']['node']) ? $trans_val['amount']['items']['node'] : 'IDR 0'),
							'transaction_code'			=> (isset($trans_val['type']['items']['node']) ? $trans_val['type']['items']['node'] : ''),
							'transaction_saldo'			=> (isset($trans_val['saldo']['items']['node']) ? $trans_val['saldo']['items']['node'] : 'IDR 0'),
						);
						$collectData['rows']['items'][$key]['transaction_desc'] = trim(sprintf("%s", $collectData['rows']['items'][$key]['transaction_description']));
						// Format date
						try {
							$collectData['rows']['items'][$key]['transaction_date_object'] = DateTime::createFromFormat('d-M-Y', $collectData['rows']['items'][$key]['transaction_date_string']);
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
							$collectData['rows']['items'][$key]['transaction_date_format'] = '';
						}
						$collectData['rows']['items'][$key]['transaction_amount_array'] = explode('IDR', $collectData['rows']['items'][$key]['transaction_amount_string']);
						$collectData['rows']['items'][$key]['transaction_amount'] = (isset($collectData['rows']['items'][$key]['transaction_amount_array'][1]) ? trim($collectData['rows']['items'][$key]['transaction_amount_array'][1]) : 0);
						$collectData['rows']['items'][$key]['transaction_amount'] = $this->parse_number($collectData['rows']['items'][$key]['transaction_amount'], ',');
						$collectData['rows']['items'][$key]['transaction_saldo'] = explode('IDR', $collectData['rows']['items'][$key]['transaction_saldo']);
						$collectData['rows']['items'][$key]['transaction_saldo'] = (isset($collectData['rows']['items'][$key]['transaction_saldo'][1]) ? $collectData['rows']['items'][$key]['transaction_saldo'][1] : 0);
						$collectData['rows']['items'][$key]['transaction_saldo'] = $this->parse_number($collectData['rows']['items'][$key]['transaction_saldo'], ',');
						# Saldo Aktual
						$collectData['rows']['items'][$key]['actual_rekening_saldo'] = $collectData['rows']['items'][$key]['transaction_saldo'];
						//$collectData['rows']['items'][$key]['transaction_amount'] = sprintf("%.02f", $collectData['rows']['items'][$key]['transaction_amount']);
						$collectData['rows']['items'][$key]['informasi_rekening'] = $informasi_rekening;
						$collectData['rows']['items'][$key]['transaction_code'] = strtoupper($collectData['rows']['items'][$key]['transaction_code']);
						if ($collectData['rows']['items'][$key]['transaction_code'] === 'CR') {
							$collectData['rows']['items'][$key]['transaction_payment'] = 'deposit';
						} else {
							$collectData['rows']['items'][$key]['transaction_payment'] = 'transfer';
						}
						//----
						$key += 1;
					}
				}
				$collectData['rows']['logout_params'] = $collectData['rekening_dom']['logout_params'];
				return $collectData['rows'];
			}
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