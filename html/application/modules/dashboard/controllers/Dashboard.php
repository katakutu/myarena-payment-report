<?php
defined('BASEPATH') OR exit('No direct script access allowed: Dashboard');
class Dashboard extends MY_Controller {
	public $is_admin = FALSE;
	public $is_merchant = FALSE;
	protected $DateObject;
	protected $email_vendor;
	function __construct() {
		parent::__construct();
		$this->load->helper('dashboard/dashboard_functions');
		$this->load->config('dashboard/base_dashboard');
		$this->base_dashboard = $this->config->item('base_dashboard');
		$this->email_vendor = (isset($this->base_dashboard['email_vendor']) ? $this->base_dashboard['email_vendor'] : '');
		$this->load->library('dashboard/Lib_authentication', $this->base_dashboard, 'authentication');
		$this->DateObject = $this->authentication->create_dateobject(ConstantConfig::$timezone, 'Y-m-d H:i:s', date('Y-m-d H:i:s'));
		$this->load->model('dashboard/Model_account', 'mod_account');
		# Load Model Paymentreport
		$this->load->model('paymentreport/Model_paymentreport', 'mod_paymentreport');
		if (($this->authentication->userdata != FALSE) && ($this->authentication->localdata != FALSE)) {
			if (in_array($this->authentication->localdata['account_role'], $this->base_dashboard['admin_role'])) {
				$this->is_admin = TRUE;
			}
			if (!$this->is_admin) {
				if (in_array($this->authentication->localdata['account_role'], $this->base_dashboard['merchant_role'])) {
					$this->is_merchant = TRUE;
				}
			}
		}
	}
	private function accessDenied($collectData = null) {
		if (!isset($collectData)) {
			exit("This page is available if have collectData object.");
		}
		$collectData['page'] = 'error-access-denied';
		
		echo "<h1>Access Denied</h1>";
	}
	//=========================================================
	function index() {
		$collectData = array(
			'collect'				=> array(),
			'page'					=> 'dashboard-home',
		);
		$collectData['base_path'] = $this->imzers->base_path;
		$collectData['title'] = 'Home';
		
		if (!$this->authentication->localdata) {
			$collectData['page'] = 'form-login';
			$collectData['title'] = 'Login to system';
			$collectData['collect']['match'] = $this->authentication->get_altorouter_match();
			$collectData['collect']['address-province'] = $this->mod_account->get_province(360);
			
			$this->load->view('dashboard/form.php', $collectData);
			//redirect(base_url("{$collectData['base_path']}/account/login"));
		} else {
			$collectData['collect']['ggdata'] = $this->authentication->userdata;
			$collectData['collect']['userdata'] = $this->authentication->localdata;
			$collectData['collect']['datetime'] = $this->DateObject->format('Y-m-d H:i:s');
			$collectData['collect']['match'] = $this->authentication->get_altorouter_match();
			if ((int)$this->authentication->localdata['account_role'] > 0) {
				$collectData['page'] = 'dashboard-home';
				$collectData['collect']['users'] = array(
					'total' => $this->mod_account->get_local_users('total'),
				);
				
				$collectData['collect']['payment_providers'] = $this->mod_paymentreport->get_payment_providers();
				$collectData['collect']['payment_yesterday'] = $this->mod_paymentreport->get_all_yesterday_payments();
				$collectData['collect']['payment_summaries'] = $this->mod_paymentreport->get_all_summaries_payments();
				
				$this->load->view('dashboard/dashboard.php', $collectData);
			} else {
				$this->accessDenied($collectData);
			}
		}
	}
	function about() {
		$collectData = array(
			'collect'				=> array(),
			'page'					=> 'dashboard-about',
		);
		$collectData['base_path'] = $this->imzers->base_path;
		$collectData['title'] = 'About';
		if (!$this->authentication->localdata) {
			$collectData['page'] = 'form-login';
			header("Location: " . base_url("{$this->imzers->base_path}/account/login"));
			exit;
		} else {
			$collectData['collect']['ggdata'] = $this->authentication->userdata;
			$collectData['collect']['userdata'] = $this->authentication->localdata;
			$collectData['collect']['datetime'] = $this->DateObject->format('Y-m-d H:i:s');
			$collectData['collect']['match'] = $this->authentication->get_altorouter_match();
			if ((int)$this->authentication->localdata['account_role'] > 0) {
				$collectData['collect']['users'] = array(
					'total' => $this->mod_account->get_local_users('total'),
				);
				$collectData['collect']['match'] = $this->authentication->get_altorouter_match();
				$this->load->view('dashboard/dashboard.php', $collectData);
			} else {
				$this->accessDenied($collectData);
			}
		}
	}
	
	
	
	
	##############################################################
	public function get_address($type = 'country') {
		$type = (is_string($type) ? strtolower($type) : 'country');
		$input_params = array();
		$address_result = array();
		switch ($type) {
			case 'country':
			default:
				$input_params['country_code'] = (isset($this->imzcustom->php_input_request['body']['user_address_country']) ? $this->imzcustom->php_input_request['body']['user_address_country'] : '');
				$address_result = $this->mod_account->get_province($input_params);
			break;
			case 'province':
				$input_params['country_code'] = (isset($this->imzcustom->php_input_request['body']['user_address_country']) ? $this->imzcustom->php_input_request['body']['user_address_country'] : '');
				$input_params['province_code'] = (isset($this->imzcustom->php_input_request['body']['user_address_province']) ? $this->imzcustom->php_input_request['body']['user_address_province'] : '');
				$address_result = $this->mod_account->get_city($input_params);
			break;
			case 'city':
				$input_params['country_code'] = (isset($this->imzcustom->php_input_request['body']['user_address_country']) ? $this->imzcustom->php_input_request['body']['user_address_country'] : '');
				$input_params['province_code'] = (isset($this->imzcustom->php_input_request['body']['user_address_province']) ? $this->imzcustom->php_input_request['body']['user_address_province'] : '');
				$input_params['city_code'] = (isset($this->imzcustom->php_input_request['body']['user_address_city']) ? $this->imzcustom->php_input_request['body']['user_address_city'] : '');
				$address_result = $this->mod_account->get_district($input_params);
			break;
			case 'district':
				$input_params['country_code'] = (isset($this->imzcustom->php_input_request['body']['user_address_country']) ? $this->imzcustom->php_input_request['body']['user_address_country'] : '');
				$input_params['province_code'] = (isset($this->imzcustom->php_input_request['body']['user_address_province']) ? $this->imzcustom->php_input_request['body']['user_address_province'] : '');
				$input_params['city_code'] = (isset($this->imzcustom->php_input_request['body']['user_address_city']) ? $this->imzcustom->php_input_request['body']['user_address_city'] : '');
				$input_params['district_code'] = (isset($this->imzcustom->php_input_request['body']['user_address_district']) ? $this->imzcustom->php_input_request['body']['user_address_district'] : '');
				$address_result = $this->mod_account->get_area($input_params);
			break;
			case 'area':
				$input_params['country_code'] = (isset($this->imzcustom->php_input_request['body']['user_address_country']) ? $this->imzcustom->php_input_request['body']['user_address_country'] : '');
				$input_params['province_code'] = (isset($this->imzcustom->php_input_request['body']['user_address_province']) ? $this->imzcustom->php_input_request['body']['user_address_province'] : '');
				$input_params['city_code'] = (isset($this->imzcustom->php_input_request['body']['user_address_city']) ? $this->imzcustom->php_input_request['body']['user_address_city'] : '');
				$input_params['district_code'] = (isset($this->imzcustom->php_input_request['body']['user_address_district']) ? $this->imzcustom->php_input_request['body']['user_address_district'] : '');
				$input_params['area_code'] = (isset($this->imzcustom->php_input_request['body']['user_address_area']) ? $this->imzcustom->php_input_request['body']['user_address_area'] : '');
				$address_result = FALSE;
			break;
		}
		$htmlReturn = "";
		switch (strtolower($type)) {
			case 'country':
			default:
				$htmlReturn .= '<option value="">-- Select Province --</option>\n';
			break;
			case 'province':
				$htmlReturn .= '<option value="">-- Select Kota/Kabupaten --</option>\n';
			break;
			case 'city':
				$htmlReturn .= '<option value="">-- Select Kecamatan --</option>\n';
			break;
			case 'district':
				$htmlReturn .= '<option value="">-- Select Desa/Kelurahan --</option>\n';
			break;
			case 'area':
				$htmlReturn .= '<option value="">-- Select Province --</option>\n';
			break;
		}
		if (count($address_result) > 0) {
			foreach ($address_result as $keval) {
				switch (strtolower($type)) {
					case 'country':
					default:
						$htmlReturn .= "<option value='{$keval->province_code}'>{$keval->province_name}</option>\n";
					break;
					case 'province':
						$htmlReturn .= "<option value='{$keval->city_code}'>{$keval->city_name}</option>\n";
					break;
					case 'city':
						$htmlReturn .= "<option value='{$keval->district_code}'>{$keval->district_name}</option>\n";
					break;
					case 'district':
						$htmlReturn .= "<option value='{$keval->area_name}'>{$keval->area_name}</option>\n";
					break;
					case 'area':
						$htmlReturn .= "<option value='{$keval->province_code}'>{$keval->province_name}</option>\n";
					break;
				}
			}
		}
		echo $htmlReturn;
	}
	
	
	
}








