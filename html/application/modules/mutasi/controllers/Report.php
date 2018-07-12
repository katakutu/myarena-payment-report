<?php
defined('BASEPATH') OR exit('No direct script access allowed: Dashboard');
class Report extends MY_Controller {
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
		$this->load->model('mutasi/Model_bank_report', 'mod_bankreport');
		# Load mutasi config
		$this->load->config('mutasi/base_mutasi');
		$this->base_mutasi = $this->config->item('base_mutasi');
		
		# Load Codeigniter helpers
		$this->load->helper('security');
		$this->load->helper('form');
		$this->load->library('form_validation');
	}
	private function accessDenied($collectData = null) {
		if (!isset($collectData)) {
			exit("This page is available if have collectData object.");
		}
		$collectData['page'] = 'error-access-denied';
		
		echo "<h1>Access Denied</h1>";
	}
	//=========================================================
	function viewmutasi($bank_code = 'all', $pgnumber = 0) {
		$collectData = array(
			'page'					=> 'mutasi-account-list',
			'title'					=> 'Mutasi Dashboard',
			'base_path'				=> $this->base_mutasi['base_path'],
			'base_dashboard_path'	=> 'dashboard',
			'collect'				=> array(),
			'bank_code'				=> (is_string($bank_code) ? strtolower($bank_code) : 'all'),
			'pgnumber'				=> (is_numeric($pgnumber) ? $pgnumber : 0),
		);
		$collectData['search_text'] = (isset($this->imzcustom->php_input_request['body']['search_text']) ? $this->imzcustom->php_input_request['body']['search_text'] : '');
		$collectData['search_text'] = (is_string($collectData['search_text']) ? $collectData['search_text'] : '');
		if ($this->authentication->localdata) {
			$collectData['collect']['ggdata'] = $this->authentication->userdata;
			$collectData['collect']['userdata'] = $this->authentication->localdata;
			$collectData['collect']['roles'] = $this->mod_account->get_dashboard_roles();
			$collectData['collect']['match'] = $this->authentication->get_altorouter_match();
		} else {
			header("Location: " . base_url("{$this->imzers->base_path}/account/login"));
			exit;
		}
		if (!$this->is_editor) {
			$this->error = true;
			$this->error_msg[] = "You are prohibited to access page, need editor privileges.";
			$this->accessDenied($collectData);
		}
		//=================================
		if (!$this->error) {
			$collectData['collect']['bank_type'] = $this->mod_mutasi->get_bank();
			array_push($collectData['collect']['bank_type'], $this->mod_mutasi->get_bank_type_by_all());
			$collectData['query_accounts_by'] = 'bank';
			if (strtolower($collectData['bank_code']) === 'all') {
				$collectData['query_accounts_by'] = 'all';
				$collectData['collect']['bank_type_data'] = $this->mod_mutasi->get_bank_type_by_all();
			} else {
				$collectData['collect']['bank_type_data'] = $this->mod_mutasi->get_bank_type_by('code', $collectData['bank_code']);
			}
			if (!isset($collectData['collect']['bank_type_data']->seq)) {
				$this->error = true;
				$this->error_msg[] = "Bank type code not exists on database.";
			} else {
				$collectData['collect']['bank_accounts'] = array();
				try {
					$collectData['collect']['bank_accounts']['count'] = $this->mod_mutasi->get_bank_account_count_by($collectData['query_accounts_by'], $collectData['collect']['bank_type_data']->seq, $collectData['search_text']);
				} catch (Exception $ex) {
					$this->error = true;
					$this->error_msg[] = "Error exception while get all bank accounts on bank type: {$ex->getMessage()}.";
				}
			}
		}
		if (!$this->error) {
			if (isset($collectData['collect']['bank_accounts']['count']->value)) {
				if ((int)$collectData['collect']['bank_accounts']['count']->value > 0) {
					$collectData['pagination'] = array(
						'page'		=> (isset($collectData['pgnumber']) ? $collectData['pgnumber'] : 1),
						'start'		=> 0,
					);
					$collectData['pagination']['page'] = (is_numeric($collectData['pagination']['page']) ? sprintf("%d", $collectData['pagination']['page']) : 1);
					if ($collectData['pagination']['page'] > 0) {
						$collectData['pagination']['page'] = (int)$collectData['pagination']['page'];
					} else {
						$collectData['pagination']['page'] = 1;
					}
					$collectData['pagination']['start'] = $this->imzcustom->get_pagination_start($collectData['pagination']['page'], base_config('rows_per_page'), $collectData['collect']['bank_accounts']['count']->value);
				} else {
					$collectData['pagination'] = array(
						'page'		=> 1,
						'start'		=> 0,
					);
				}
			} else {
				$this->error = true;
				$this->error_msg[] = "Should have value as total rows.";
			}
		}
		if (!$this->error) {
			$collectData['collect']['pagination'] = $this->imzcustom->generate_pagination(base_url("{$collectData['base_path']}/{$collectData['collect']['bank_type_data']->bank_code}/%d"), $collectData['pagination']['page'], base_config('rows_per_page'), $collectData['collect']['bank_accounts']['count']->value, $collectData['pagination']['start']);
			
			try {
				$collectData['collect']['bank_accounts']['data'] = $this->mod_mutasi->get_bank_account_data_by($collectData['query_accounts_by'], $collectData['collect']['bank_type_data']->seq, $collectData['search_text'], $collectData['pagination']['start'], base_config('rows_per_page'));
			} catch (Exception $ex) {
				$this->error = true;
				$this->error_msg[] = "Error while get account data items data by bank with exception: {$ex->getMessage()}";
			}
		}
		if (!$this->error) {
			if (is_array($collectData['collect']['bank_accounts']['data']) && (count($collectData['collect']['bank_accounts']['data']) > 0)) {
				foreach ($collectData['collect']['bank_accounts']['data'] as &$keval) {
					$keval->transaction_all = array(
						'deposit'		=> $this->mod_bankreport->get_amount_and_unit_transaction_all_bytype($keval->seq, 'deposit'),
						'transfer'		=> $this->mod_bankreport->get_amount_and_unit_transaction_all_bytype($keval->seq, 'transfer'),
					);
				}
			}
		}
		
		if (!$this->error) {
			$collectData['page'] = 'bankreport-account-list';
			$collectData['title'] = 'List Account';
			
			$this->load->view("{$this->base_mutasi['base_path']}/mutasi.php", $collectData);
		} else {
			print_r($this->error_msg);
		}
	}
	function viewdeposit($bank_code = 'all', $pgnumber = 0) {
		$collectData = array(
			'page'					=> 'bankreport-autodeposit-list',
			'title'					=> 'List Deposit',
			'base_path'				=> $this->base_mutasi['base_path'],
			'base_dashboard_path'	=> 'dashboard',
			'collect'				=> array(),
			'bank_code'				=> (is_string($bank_code) ? strtolower($bank_code) : 'all'),
			'pgnumber'				=> (is_numeric($pgnumber) ? $pgnumber : 0),
		);
		$collectData['search_text'] = (isset($this->imzcustom->php_input_request['body']['search_text']) ? $this->imzcustom->php_input_request['body']['search_text'] : '');
		$collectData['search_text'] = (is_string($collectData['search_text']) ? $collectData['search_text'] : '');
		if ($this->authentication->localdata) {
			$collectData['collect']['ggdata'] = $this->authentication->userdata;
			$collectData['collect']['userdata'] = $this->authentication->localdata;
			$collectData['collect']['roles'] = $this->mod_account->get_dashboard_roles();
			$collectData['collect']['match'] = $this->authentication->get_altorouter_match();
		} else {
			header("Location: " . base_url("{$this->imzers->base_path}/account/login"));
			exit;
		}
		if (!$this->is_editor) {
			$this->error = true;
			$this->error_msg[] = "You are prohibited to access page, need editor privileges.";
			$this->accessDenied($collectData);
		}
		//=================================
		if (!$this->error) {
			$collectData['collect']['bank_type'] = $this->mod_mutasi->get_bank();
			array_push($collectData['collect']['bank_type'], $this->mod_mutasi->get_bank_type_by_all());
			$collectData['query_accounts_by'] = 'bank';
			if (strtolower($collectData['bank_code']) === 'all') {
				$collectData['query_accounts_by'] = 'all';
				$collectData['collect']['bank_type_data'] = $this->mod_mutasi->get_bank_type_by_all();
			} else {
				$collectData['collect']['bank_type_data'] = $this->mod_mutasi->get_bank_type_by('code', $collectData['bank_code']);
			}
			if (!isset($collectData['collect']['bank_type_data']->seq)) {
				$this->error = true;
				$this->error_msg[] = "Bank type code not exists on database.";
			} else {
				$collectData['collect']['bank_accounts'] = array();
				try {
					$collectData['collect']['bank_accounts']['count'] = $this->mod_mutasi->get_bank_account_count_by($collectData['query_accounts_by'], $collectData['collect']['bank_type_data']->seq, $collectData['search_text']);
				} catch (Exception $ex) {
					$this->error = true;
					$this->error_msg[] = "Error exception while get all bank accounts on bank type: {$ex->getMessage()}.";
				}
			}
		}
		if (!$this->error) {
			if (isset($collectData['collect']['bank_accounts']['count']->value)) {
				if ((int)$collectData['collect']['bank_accounts']['count']->value > 0) {
					$collectData['pagination'] = array(
						'page'		=> (isset($collectData['pgnumber']) ? $collectData['pgnumber'] : 1),
						'start'		=> 0,
					);
					$collectData['pagination']['page'] = (is_numeric($collectData['pagination']['page']) ? sprintf("%d", $collectData['pagination']['page']) : 1);
					if ($collectData['pagination']['page'] > 0) {
						$collectData['pagination']['page'] = (int)$collectData['pagination']['page'];
					} else {
						$collectData['pagination']['page'] = 1;
					}
					$collectData['pagination']['start'] = $this->imzcustom->get_pagination_start($collectData['pagination']['page'], base_config('rows_per_page'), $collectData['collect']['bank_accounts']['count']->value);
				} else {
					$collectData['pagination'] = array(
						'page'		=> 1,
						'start'		=> 0,
					);
				}
			} else {
				$this->error = true;
				$this->error_msg[] = "Should have value as total rows.";
			}
		}
		if (!$this->error) {
			$collectData['collect']['pagination'] = $this->imzcustom->generate_pagination(base_url("{$collectData['base_path']}/{$collectData['collect']['bank_type_data']->bank_code}/%d"), $collectData['pagination']['page'], base_config('rows_per_page'), $collectData['collect']['bank_accounts']['count']->value, $collectData['pagination']['start']);
			
			try {
				$collectData['collect']['bank_accounts']['data'] = $this->mod_mutasi->get_bank_account_data_by($collectData['query_accounts_by'], $collectData['collect']['bank_type_data']->seq, $collectData['search_text'], $collectData['pagination']['start'], base_config('rows_per_page'));
			} catch (Exception $ex) {
				$this->error = true;
				$this->error_msg[] = "Error while get account data items data by bank with exception: {$ex->getMessage()}";
			}
		}
		if (!$this->error) {
			if (is_array($collectData['collect']['bank_accounts']['data']) && (count($collectData['collect']['bank_accounts']['data']) > 0)) {
				foreach ($collectData['collect']['bank_accounts']['data'] as &$keval) {
					$keval->transaction_all = $this->mod_bankreport->get_amount_and_unit_suksesbugil_all_bytype($keval->seq, 'all');
				}
			}
		}
		
		if (!$this->error) {
			$collectData['page'] = 'bankreport-autodeposit-list';
			$collectData['title'] = 'List Account';
			
			$this->load->view("{$this->base_mutasi['base_path']}/mutasi.php", $collectData);
		} else {
			print_r($this->error_msg);
		}
	}
	function viewmutasitransaction($account_seq = 0) {
		$collectData = array(
			'page'					=> 'bankreport-account-transaction',
			'title'					=> 'Report Transaction',
			'base_path'				=> $this->base_mutasi['base_path'],
			'base_dashboard_path'	=> 'dashboard',
			'collect'				=> array(),
			'account_seq'			=> (is_numeric($account_seq) ? (int)$account_seq : 0),
		);
		$collectData['search_text'] = (isset($this->imzcustom->php_input_request['body']['search_text']) ? $this->imzcustom->php_input_request['body']['search_text'] : '');
		$collectData['search_text'] = (is_string($collectData['search_text']) ? $collectData['search_text'] : '');
		if ($this->authentication->localdata) {
			$collectData['collect']['ggdata'] = $this->authentication->userdata;
			$collectData['collect']['userdata'] = $this->authentication->localdata;
			$collectData['collect']['roles'] = $this->mod_account->get_dashboard_roles();
			$collectData['collect']['match'] = $this->authentication->get_altorouter_match();
		} else {
			header("Location: " . base_url("{$this->imzers->base_path}/account/login"));
			exit;
		}
		if (!$this->is_editor) {
			$this->error = true;
			$this->error_msg[] = "You are prohibited to access page, need editor privileges.";
			$this->accessDenied($collectData);
		}
		$collectData['transaction_date'] = (isset($this->imzcustom->php_input_request['body']['transaction_date']) ? $this->imzcustom->php_input_request['body']['transaction_date'] : array());
		if (!$this->error) {
			if (isset($collectData['transaction_date']['month'])) {
				$collectData['transaction_date']['month'] = ((is_string($collectData['transaction_date']['month']) || is_numeric($collectData['transaction_date']['month'])) ? $this->imzers->safe_text_post($collectData['transaction_date']['month'], 2) : $this->DateObject->format('m'));
			} else {
				$transaction_date_month_object = $this->authentication->create_dateobject(ConstantConfig::$timezone, 'Y-m-d H:i:s', date('Y-m-d H:i:s'));
				$collectData['transaction_date']['month'] = $transaction_date_month_object->format('m');
			}
			if (isset($collectData['transaction_date']['year'])) {
				$collectData['transaction_date']['year'] = ((is_string($collectData['transaction_date']['year']) || is_numeric($collectData['transaction_date']['year'])) ? $this->imzers->safe_text_post($collectData['transaction_date']['year'], 4) : $this->DateObject->format('Y'));
			} else {
				$collectData['transaction_date']['year'] = $this->DateObject->format('Y');
			}
			try {
				$collectData['collect']['transaction_date']['maxdate'] = $this->mod_bankreport->get_maxdate_in_month($collectData['transaction_date']['year'], $collectData['transaction_date']['month']);
			} catch (Exception $ex) {
				$this->error = true;
				$this->error_msg[] = "Cannot create dateobject of transaction-date both start and end.";
			}
		}
		if (!$this->error) {
			$collectData['collect']['transaction_date']['report_dates'] = array();
			if ((int)$collectData['collect']['transaction_date']['maxdate'] > 0) {
				for ($i = 1; $i <= $collectData['collect']['transaction_date']['maxdate']; $i++) {
					$collectData['collect']['transaction_date']['report_dates'][] = new DateTime("{$collectData['transaction_date']['year']}-{$collectData['transaction_date']['month']}-{$i}");
				}
			}
		}
		//=================================
		if (!$this->error) {
			$collectData['collect']['bank_type'] = $this->mod_mutasi->get_bank();
			array_push($collectData['collect']['bank_type'], $this->mod_mutasi->get_bank_type_by_all());
			try {
				$collectData['collect']['account_data'] = $this->mod_mutasi->get_account_item_single_by('seq', $collectData['account_seq']);
			} catch (Exception $ex) {
				$this->error = true;
				$this->error_msg[] = "Error exception while get account-data by seq: {$ex->getMessage()}";
			}
		}
		if (!$this->error) {
			if (!isset($collectData['collect']['account_data']->seq)) {
				$this->error = true;
				$this->error_msg[] = "Account data bank not exists on database.";
			} else {
				$collectData['collect']['transaction_data_by_date'] = array();
				if (count($collectData['collect']['transaction_date']['report_dates']) > 0) {
					foreach ($collectData['collect']['transaction_date']['report_dates'] as $report_date) {
						$collectData['collect']['transaction_data_by_date'][] = array(
							'date'		=> $report_date->format('Y-m-d'),
							'deposit'	=> $this->mod_bankreport->get_amount_and_unit_transaction_by_date_type($collectData['collect']['account_data']->seq, $report_date->format('Y-m-d'), 'deposit'),
							'transfer'	=> $this->mod_bankreport->get_amount_and_unit_transaction_by_date_type($collectData['collect']['account_data']->seq, $report_date->format('Y-m-d'), 'transfer'),
						);
					}
				} else {
					$this->error = true;
					$this->error_msg[] = "No report-date of daterange.";
				}
			}
		}
		if (!$this->error) {
			$collectData['collect']['input_dates'] = array(
				'year'		=> $this->mod_bankreport->get_report_years(),
				'month'		=> $this->mod_bankreport->get_report_months(),
			);
		}
		
		if (!$this->error) {
			$this->load->view("{$this->base_mutasi['base_path']}/mutasi.php", $collectData);
		} else {
			print_r($this->error_msg);
		}
		
	}
	
}








