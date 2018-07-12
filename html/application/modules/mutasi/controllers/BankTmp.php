<?php
defined('BASEPATH') OR exit('No direct script access allowed: Dashboard');
class BankTmp extends MY_Controller {
	public $is_editor = FALSE;
	public $error = FALSE, $error_msg = array();
	protected $DateObject;
	protected $email_vendor;
	protected $base_dashboard, $base_mutasi = array();
	function __construct() {
		parent::__construct();
		$this->load->helper('dashboard/dashboard_functions');
		$this->load->config('dashboard/base_dashboard');
		$this->base_dashboard = $this->config->item('base_dashboard');
		$this->email_vendor = (isset($this->base_dashboard['email_vendor']) ? $this->base_dashboard['email_vendor'] : '');
		$this->load->library('dashboard/Lib_authentication', $this->base_dashboard, 'authentication');
		$this->load->model('dashboard/Model_account', 'mod_account');
		$this->DateObject = $this->authentication->create_dateobject(ConstantConfig::$timezone, 'Y-m-d H:i:s', date('Y-m-d H:i:s'));
		if (($this->authentication->localdata != FALSE)) {
			if (in_array((int)$this->authentication->localdata['account_role'], base_config('editor_role'))) {
				$this->is_editor = TRUE;
			}
		}
		
		# Load Models
		$this->load->model('mutasi/Model_mutasi', 'mod_mutasi');
	}
	private function parse_number($number, $dec_point = null) {
		if (empty($dec_point)) {
			$locale = localeconv();
			$dec_point = $locale['decimal_point'];
		}
		return floatval(str_replace($dec_point, '.', preg_replace('/[^\d' . preg_quote($dec_point).']/', '', $number)));
	}
	function index() {
		echo 'hello..';
	}
	function bri() {
		echo "<pre>";
		
		$transactions = $this->mod_mutasi->get_rekening_transaction_by('seq', 6, array());
		
		
		if (isset($transactions->bank_code)) {
			// Load Library Bank Mutasi Parser
			switch (strtolower($transactions->bank_code)) {
				case 'mandiri':
					$this->load->library('mutasi/Lib_mandiri', NULL, 'lib_bank');
				break;
				case 'bri':
					$this->load->library('mutasi/Lib_bri', NULL, 'lib_bank');
				break;
				case 'bni':
					$this->load->library('mutasi/Lib_bni', NULL, 'lib_bank');
				break;
				case 'danamon':
					$this->load->library('mutasi/Lib_danamon', NULL, 'lib_bank');
				break;
				case 'bca':
				default:
					$this->load->library('mutasi/Lib_bca', NULL, 'lib_bank');
				break;
			}
		}
		$login = $this->lib_bank->login($transactions->account_username, $transactions->account_password);
		$transaction_date = array(
			'starting'	=> $this->authentication->create_dateobject(ConstantConfig::$timezone, 'Y-m-d', '2018-05-20'),
			'stopping'	=> $this->authentication->create_dateobject(ConstantConfig::$timezone, 'Y-m-d', date('Y-m-d')),
		);
		$bank_account_rekening_number = '';
		if (isset($transactions->rekening_data)) {
			if (is_array($transactions->rekening_data) && (count($transactions->rekening_data) > 0)) {
				foreach ($transactions->rekening_data as $rekdata) {
					$bank_account_rekening_number = (isset($rekdata->rekening_number) ? sprintf("%s", $rekdata->rekening_number) : '');
				}
			}
		}
		$bank_account_rekening_number = trim($bank_account_rekening_number);
		$transactions = $this->lib_bank->get_mutasi_transactions($transaction_date, $bank_account_rekening_number);
		$this->lib_bank->logout();
		
		print_r($transactions);
		
	}
	function briconstant() {
		echo "<pre>";
		
		$transactions = $this->mod_mutasi->get_rekening_transaction_by('seq', 8, array());
		print_r($transactions);
		exit;
		
		if (isset($transactions->bank_code)) {
			// Load Library Bank Mutasi Parser
			switch (strtolower($transactions->bank_code)) {
				case 'mandiri':
					$this->load->library('mutasi/Lib_mandiri', NULL, 'lib_bank');
				break;
				case 'bri':
					$this->load->library('mutasi/Lib_bri', NULL, 'lib_bank');
				break;
				case 'bni':
					$this->load->library('mutasi/Lib_bni', NULL, 'lib_bank');
				break;
				case 'danamon':
					$this->load->library('mutasi/Lib_danamon', NULL, 'lib_bank');
				break;
				case 'bca':
				default:
					$this->load->library('mutasi/Lib_bca', NULL, 'lib_bank');
				break;
			}
		}
		$bank_account_rekening_number = '';
		if (isset($transactions->rekening_data)) {
			if (is_array($transactions->rekening_data) && (count($transactions->rekening_data) > 0)) {
				foreach ($transactions->rekening_data as $rekdata) {
					$bank_account_rekening_number = (isset($rekdata->rekening_number) ? sprintf("%s", $rekdata->rekening_number) : '');
				}
			}
		}
		/*
		//$login = $this->lib_bank->login('Hendra9625', 'Anakripas123');
		$login = $this->lib_bank->login($transactions->account_username, $transactions->account_password);
		$transaction_date = array(
			'starting'	=> $this->authentication->create_dateobject(ConstantConfig::$timezone, 'Y-m-d', '2018-05-20'),
			'stopping'	=> $this->authentication->create_dateobject(ConstantConfig::$timezone, 'Y-m-d', date('Y-m-d')),
		);
		//$transactions = $this->lib_bank->get_mutasi_transactions($transaction_date, $bank_account_rekening_number);
		$transactions = $this->lib_bank->get_mutasi_transactions($transaction_date, $bank_account_rekening_number);
		$logout = $this->lib_bank->logout();
		
		print_r($transactions);
		exit;
		*/
		
		$login = $this->lib_bank->login($transactions->account_username, $transactions->account_password);
		$transaction_date = array(
			'starting'	=> $this->authentication->create_dateobject(ConstantConfig::$timezone, 'Y-m-d', '2018-05-20'),
			'stopping'	=> $this->authentication->create_dateobject(ConstantConfig::$timezone, 'Y-m-d', date('Y-m-d')),
		);
		
		$bank_account_rekening_number = trim($bank_account_rekening_number);
		$transactions = $this->lib_bank->get_mutasi_transactions($transaction_date, $bank_account_rekening_number);
		$this->lib_bank->logout();
		
		print_r($transactions);
		
	}
	
	
	
	
	
	
	
}