<?php
defined('BASEPATH') OR exit('No direct script access allowed: Dashboard');
class Suksesbugil extends MY_Controller {
	public $is_editor = FALSE;
	public $error = FALSE, $error_msg = array();
	protected $DateObject;
	protected $email_vendor;
	protected $base_dashboard, $base_mutasi = array();
	protected $base_suksesbugil;
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
		$this->load->model('mutasi/Model_suksesbugil', 'mod_suksesbugil');
		# Load mutasi config
		$this->load->config('mutasi/base_mutasi');
		$this->base_mutasi = $this->config->item('base_mutasi');
		$this->load->config('mutasi/base_suksesbugil');
		$this->base_suksesbugil = $this->config->item('base_suksesbugil');
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
	//===============================================================================================
	function deposit($this_method = 'all', $bank_code = 'all', $pgnumber = 0) {
		$collectData = array(
			'this_method'			=> (is_string($this_method) ? strtolower($this_method) : 'all'),
			'page'					=> 'suksesbugil-transaction-list-deposit',
			'title'					=> 'Deposit Lists',
			'base_path'				=> $this->base_mutasi['base_path'],
			'base_dashboard_path'	=> 'dashboard',
			'collect'				=> array(),
			'bank_code'				=> (is_string($bank_code) ? strtolower($bank_code) : 'bca'),
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
		$collectData['transaction_search_amount'] = (isset($this->imzcustom->php_input_request['body']['transaction_search_amount']) ? $this->imzcustom->php_input_request['body']['transaction_search_amount'] : '');
		$collectData['transaction_search_amount'] = (is_string($collectData['transaction_search_amount']) || is_numeric($collectData['transaction_search_amount'])) ? sprintf("%d", $collectData['transaction_search_amount']) : '0';
		$collectData['transaction_date'] = (isset($this->imzcustom->php_input_request['body']['transaction_date']) ? $this->imzcustom->php_input_request['body']['transaction_date'] : array());
		if (!$this->error) {
			if (isset($collectData['transaction_date']['starting'])) {
				$collectData['transaction_date']['starting'] = (is_string($collectData['transaction_date']['starting']) ? $this->imzers->safe_text_post($collectData['transaction_date']['starting'], 32) : $this->DateObject->format('Y-m-d'));
			} else {
				$collectData['transaction_date']['starting'] = $this->DateObject->format('Y-m-d');
			}
			if (isset($collectData['transaction_date']['stopping'])) {
				$collectData['transaction_date']['stopping'] = (is_string($collectData['transaction_date']['stopping']) ? $this->imzers->safe_text_post($collectData['transaction_date']['stopping'], 32) : $this->DateObject->format('Y-m-d'));
			} else {
				$collectData['transaction_date']['stopping'] = $this->DateObject->format('Y-m-d');
			}
			try {
				$collectData['collect']['transaction_date'] = array(
					'starting'	=> $this->authentication->create_dateobject(ConstantConfig::$timezone, 'Y-m-d', $collectData['transaction_date']['starting']),
					'stopping'	=> $this->authentication->create_dateobject(ConstantConfig::$timezone, 'Y-m-d', $collectData['transaction_date']['stopping']),
				);
			} catch (Exception $ex) {
				$this->error = true;
				$this->error_msg[] = "Cannot create dateobject of transaction-date both start and end.";
			}
		}
		if (!$this->error) {
			// waiting, approved, canceled, deleted
			switch ($collectData['this_method']) {
				case 'waiting':
					$collectData['is_approved'] = 'waiting';
					$collectData['title'] .= ': Waiting';
				break;
				case 'deleted':
					$collectData['is_approved'] = 'deleted';
					$collectData['title'] .= ': Deleted';
				break;
				case 'already':
					$collectData['is_approved'] = 'already';
					$collectData['title'] .= ': Already';
				break;
				case 'approved':
					$collectData['is_approved'] = 'approved';
					$collectData['title'] .= ': Approved';
				break;
				case 'canceled':
					$collectData['is_approved'] = 'canceled';
					$collectData['title'] .= ': Canceled';
				break;
				case 'failed':
					$collectData['is_approved'] = 'failed';
					$collectData['title'] .= ': Failed';
				break;
				case 'all':
				default:
					$collectData['is_approved'] = '';
					$collectData['title'] .= ': All';
				break;
			}
			$collectData['collect']['bank_type'] = $this->mod_mutasi->get_bank();
			$collectData['collect']['bank_deposit'] = array();
			if (strtolower($collectData['bank_code']) !== 'all') {
				$collectData['collect']['bank_type_data'] = $this->mod_mutasi->get_bank_type_by('code', $collectData['bank_code']);
				if (!isset($collectData['collect']['bank_type_data']->seq)) {
					$this->error = true;
					$this->error_msg[] = "Bank type code not exists on database.";
				} else {
					try {
						$collectData['collect']['bank_deposit']['count'] = $this->mod_suksesbugil->get_sb_deposit_count_by('bank', $collectData['collect']['bank_type_data']->seq, $collectData['is_approved'], $collectData['transaction_search_amount'], $collectData['collect']['transaction_date'], $collectData['search_text']);
					} catch (Exception $ex) {
						$this->error = true;
						$this->error_msg[] = "Error exception while get all bank deposit on bank type: {$ex->getMessage()}.";
					}
				}
			} else {
				try {
					$collectData['collect']['bank_deposit']['count'] = $this->mod_suksesbugil->get_sb_deposit_count_by('all', $collectData['is_approved'], $collectData['is_approved'], $collectData['transaction_search_amount'], $collectData['collect']['transaction_date'], $collectData['search_text']);
				} catch (Exception $ex) {
					$this->error = true;
					$this->error_msg[] = "Error exception while get all bank deposit on all: {$ex->getMessage()}.";
				}
			}
		}
		if (!$this->error) {
			$collectData['collect']['auto_approve_description'] = $this->mod_suksesbugil->get_auto_approve_by('code', $collectData['this_method']);
			if (strtolower($collectData['bank_code']) !== 'all') {
				try {
					$collectData['collect']['bank_deposit']['summaries'] = $this->mod_suksesbugil->get_sb_deposit_groups_by('bank', $collectData['collect']['bank_type_data']->seq, $collectData['is_approved'], $collectData['transaction_search_amount'], $collectData['collect']['transaction_date'], $collectData['search_text']);
				} catch (Exception $ex) {
					$this->error = true;
					$this->error_msg[] = "Error exception while get all bank summaries on bank type: {$ex->getMessage()}.";
				}
			} else {
				try {
					$collectData['collect']['bank_deposit']['summaries'] = $this->mod_suksesbugil->get_sb_deposit_groups_by('all', $collectData['is_approved'], $collectData['is_approved'], $collectData['transaction_search_amount'], $collectData['collect']['transaction_date'], $collectData['search_text']);
				} catch (Exception $ex) {
					$this->error = true;
					$this->error_msg[] = "Error exception while get all bank summaries on all: {$ex->getMessage()}.";
				}
			}
		}
		if (!$this->error) {
			if (isset($collectData['collect']['bank_deposit']['count']->value)) {
				if ((int)$collectData['collect']['bank_deposit']['count']->value > 0) {
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
					$collectData['pagination']['start'] = $this->imzcustom->get_pagination_start($collectData['pagination']['page'], base_config('rows_per_page'), $collectData['collect']['bank_deposit']['count']->value);
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
			if (strtolower($collectData['bank_code']) !== 'all') {
				$collectData['collect']['pagination'] = $this->imzcustom->generate_pagination(base_url("{$collectData['base_path']}/suksesbugil/deposit/{$collectData['this_method']}/{$collectData['collect']['bank_type_data']->bank_code}/%d"), $collectData['pagination']['page'], base_config('rows_per_page'), $collectData['collect']['bank_deposit']['count']->value, $collectData['pagination']['start']);
				try {
					$collectData['collect']['bank_deposit']['data'] = $this->mod_suksesbugil->get_sb_deposit_data_by('bank', $collectData['collect']['bank_type_data']->seq, $collectData['is_approved'], $collectData['transaction_search_amount'], $collectData['collect']['transaction_date'], $collectData['search_text'], $collectData['pagination']['start'], base_config('rows_per_page'));
				} catch (Exception $ex) {
					$this->error = true;
					$this->error_msg[] = "Error while get suksesbugil deposit data items data by bank with exception: {$ex->getMessage()}";
				}
			} else {
				$collectData['collect']['pagination'] = $this->imzcustom->generate_pagination(base_url("{$collectData['base_path']}/suksesbugil/deposit/{$collectData['this_method']}/all/%d"), $collectData['pagination']['page'], base_config('rows_per_page'), $collectData['collect']['bank_deposit']['count']->value, $collectData['pagination']['start']);
				try {
					$collectData['collect']['bank_deposit']['data'] = $this->mod_suksesbugil->get_sb_deposit_data_by('all', $collectData['is_approved'], $collectData['is_approved'], $collectData['transaction_search_amount'], $collectData['collect']['transaction_date'], $collectData['search_text'], $collectData['pagination']['start'], base_config('rows_per_page'));
				} catch (Exception $ex) {
					$this->error = true;
					$this->error_msg[] = "Error while get suksesbugil deposit data items data by all with exception: {$ex->getMessage()}";
				}
			}
		}
		if (!$this->error) {
			if (count($collectData['collect']['bank_deposit']['data']) > 0) {
				foreach ($collectData['collect']['bank_deposit']['data'] as &$keval) {
					$keval->bank_data = $this->mod_mutasi->get_bank_type_by('seq', $keval->mutasi_bank_seq);
					$keval->bank_account_data = $this->mod_mutasi->get_account_item_by('seq', $keval->mutasi_bank_account_seq);
				}
			}
			
			$this->load->view("{$this->base_mutasi['base_path']}/mutasi.php", $collectData);
		} else {
			//=== Error persist
			$error_message = "";
			foreach ($this->error_msg as $msg) {
				$error_message .= $msg . "<br/>";
			}
			$this->set_flashdata('error', TRUE);
			$this->session->set_flashdata('action_message', $error_message);
			redirect(base_url("{$collectData['base_path']}/suksesbugil/deposit"));
		}
	}
	//------------------------------------------------------------------------------------------------------
	function count_deposit_data_by_status($this_method = 'waiting', $bank_code = 'all') {
		$collectData = array(
			'this_method'			=> (is_string($this_method) ? strtolower($this_method) : 'all'),
			'page'					=> 'suksesbugil-transaction-list-deposit',
			'title'					=> 'Deposit Lists',
			'base_path'				=> $this->base_mutasi['base_path'],
			'base_dashboard_path'	=> 'dashboard',
			'collect'				=> array(),
			'bank_code'				=> (is_string($bank_code) ? strtolower($bank_code) : 'all'),
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
		$collectData['transaction_search_amount'] = (isset($this->imzcustom->php_input_request['body']['transaction_search_amount']) ? $this->imzcustom->php_input_request['body']['transaction_search_amount'] : '');
		$collectData['transaction_search_amount'] = (is_string($collectData['transaction_search_amount']) || is_numeric($collectData['transaction_search_amount'])) ? sprintf("%d", $collectData['transaction_search_amount']) : '0';
		$collectData['transaction_date'] = (isset($this->imzcustom->php_input_request['body']['transaction_date']) ? $this->imzcustom->php_input_request['body']['transaction_date'] : array());
		if (!$this->error) {
			if (isset($collectData['transaction_date']['starting'])) {
				$collectData['transaction_date']['starting'] = (is_string($collectData['transaction_date']['starting']) ? $this->imzers->safe_text_post($collectData['transaction_date']['starting'], 32) : $this->DateObject->format('Y-m-d'));
			} else {
				$collectData['transaction_date']['starting'] = $this->DateObject->format('Y-m-d');
			}
			if (isset($collectData['transaction_date']['stopping'])) {
				$collectData['transaction_date']['stopping'] = (is_string($collectData['transaction_date']['stopping']) ? $this->imzers->safe_text_post($collectData['transaction_date']['stopping'], 32) : $this->DateObject->format('Y-m-d'));
			} else {
				$collectData['transaction_date']['stopping'] = $this->DateObject->format('Y-m-d');
			}
			try {
				$collectData['collect']['transaction_date'] = array(
					'starting'	=> $this->authentication->create_dateobject(ConstantConfig::$timezone, 'Y-m-d', $collectData['transaction_date']['starting']),
					'stopping'	=> $this->authentication->create_dateobject(ConstantConfig::$timezone, 'Y-m-d', $collectData['transaction_date']['stopping']),
				);
			} catch (Exception $ex) {
				$this->error = true;
				$this->error_msg[] = "Cannot create dateobject of transaction-date both start and end.";
			}
		}
		if (!$this->error) {
			// waiting, approved, canceled, deleted
			switch ($collectData['this_method']) {
				case 'waiting':
					$collectData['is_approved'] = 'waiting';
					$collectData['title'] .= ': Waiting';
				break;
				case 'deleted':
					$collectData['is_approved'] = 'deleted';
					$collectData['title'] .= ': Deleted';
				break;
				case 'already':
					$collectData['is_approved'] = 'already';
					$collectData['title'] .= ': Already';
				break;
				case 'approved':
					$collectData['is_approved'] = 'approved';
					$collectData['title'] .= ': Approved';
				break;
				case 'canceled':
					$collectData['is_approved'] = 'canceled';
					$collectData['title'] .= ': Canceled';
				break;
				case 'failed':
					$collectData['is_approved'] = 'failed';
					$collectData['title'] .= ': Failed';
				break;
				case 'all':
				default:
					$collectData['is_approved'] = '';
					$collectData['title'] .= ': All';
				break;
			}
			$collectData['collect']['bank_type'] = $this->mod_mutasi->get_bank();
			$collectData['collect']['bank_deposit'] = array();
			if (strtolower($collectData['bank_code']) !== 'all') {
				$collectData['collect']['bank_type_data'] = $this->mod_mutasi->get_bank_type_by('code', $collectData['bank_code']);
				if (!isset($collectData['collect']['bank_type_data']->seq)) {
					$this->error = true;
					$this->error_msg[] = "Bank type code not exists on database.";
				} else {
					try {
						$collectData['collect']['bank_deposit']['count'] = $this->mod_suksesbugil->get_sb_deposit_count_by('bank', $collectData['collect']['bank_type_data']->seq, $collectData['is_approved'], $collectData['transaction_search_amount'], $collectData['collect']['transaction_date'], $collectData['search_text']);
					} catch (Exception $ex) {
						$this->error = true;
						$this->error_msg[] = "Error exception while get all bank deposit on bank type: {$ex->getMessage()}.";
					}
				}
			} else {
				try {
					$collectData['collect']['bank_deposit']['count'] = $this->mod_suksesbugil->get_sb_deposit_count_by('all', $collectData['is_approved'], $collectData['is_approved'], $collectData['transaction_search_amount'], $collectData['collect']['transaction_date'], $collectData['search_text']);
				} catch (Exception $ex) {
					$this->error = true;
					$this->error_msg[] = "Error exception while get all bank deposit on all: {$ex->getMessage()}.";
				}
			}
		}
		if (!$this->error) {
			if (isset($collectData['collect']['bank_deposit']['count']->value)) {
				echo (int)$collectData['collect']['bank_deposit']['count']->value;
			} else {
				echo 0;
			}
		} else {
			echo 0;
		}
	}
	//------------------------------------------------------------------------------------------------------
	function depositaction($action_type = 'move', $deposit_seq = 0) {
		$collectData = array(
			'this_method'			=> 'depositaction',
			'page'					=> 'suksesbugil-transaction-list-deposit-action',
			'title'					=> 'Deposit Item',
			'base_path'				=> $this->base_mutasi['base_path'],
			'base_dashboard_path'	=> 'dashboard',
			'collect'				=> array(),
			'action_type'			=> (is_string($action_type) ? strtolower($action_type) : 'move'),
			'deposit_seq'			=> (is_numeric($deposit_seq) ? $deposit_seq : 0),
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
		$collectData['transaction_search_amount'] = (isset($this->imzcustom->php_input_request['body']['transaction_search_amount']) ? $this->imzcustom->php_input_request['body']['transaction_search_amount'] : '');
		$collectData['transaction_search_amount'] = (is_string($collectData['transaction_search_amount']) || is_numeric($collectData['transaction_search_amount'])) ? sprintf("%d", $collectData['transaction_search_amount']) : '0';
		$collectData['transaction_date'] = (isset($this->imzcustom->php_input_request['body']['transaction_date']) ? $this->imzcustom->php_input_request['body']['transaction_date'] : array());
		if (!$this->error) {
			if (isset($collectData['transaction_date']['starting'])) {
				$collectData['transaction_date']['starting'] = (is_string($collectData['transaction_date']['starting']) ? $this->imzers->safe_text_post($collectData['transaction_date']['starting'], 32) : $this->DateObject->format('Y-m-d'));
			} else {
				$collectData['transaction_date']['starting'] = $this->DateObject->format('Y-m-d');
			}
			if (isset($collectData['transaction_date']['stopping'])) {
				$collectData['transaction_date']['stopping'] = (is_string($collectData['transaction_date']['stopping']) ? $this->imzers->safe_text_post($collectData['transaction_date']['stopping'], 32) : $this->DateObject->format('Y-m-d'));
			} else {
				$collectData['transaction_date']['stopping'] = $this->DateObject->format('Y-m-d');
			}
			try {
				$collectData['collect']['transaction_date'] = array(
					'starting'	=> $this->authentication->create_dateobject(ConstantConfig::$timezone, 'Y-m-d', $collectData['transaction_date']['starting']),
					'stopping'	=> $this->authentication->create_dateobject(ConstantConfig::$timezone, 'Y-m-d', $collectData['transaction_date']['stopping']),
				);
			} catch (Exception $ex) {
				$this->error = true;
				$this->error_msg[] = "Cannot create dateobject of transaction-date both start and end.";
			}
		}
		try {
			$collectData['trans_data'] = $this->mod_suksesbugil->get_sb_trans_single_by('seq', $collectData['deposit_seq']);
		} catch (Exception $ex) {
			$this->error = true;
			$this->error_msg[] = "Error exception while get deposit trans data: {$ex->getMessage()}.";
		}
		if (!$this->error) {
			if (isset($collectData['trans_data']->auto_approve_params)) {
				$collectData['collect']['approve_params'] = json_decode($collectData['trans_data']->auto_approve_params, true);
				if ((int)$collectData['trans_data']->auto_approve_mutasi_trans_seq) {
					$collectData['collect']['mb_trans_data'] = $this->mod_mutasi->get_transaction_by('seq', $collectData['trans_data']->auto_approve_mutasi_trans_seq);
				} else {
					$collectData['collect']['mb_trans_data'] = FALSE;
				}
			} else {
				$this->error = true;
				$this->error_msg[] = "Data deposit is not exists.";
			}
		}
		//--------------------------------------------------------------------
		// Deposit action command
		if (strtolower($collectData['action_type']) === 'action') {
			if (!$this->error) {
				//Load mod cli mutasi update approved scheduler
				$this->load->model('mutasi/Cli_mutasi_update_scheduler', 'mod_cli');
				$collectData['user_input'] = array(
					'selected_mutasi_seq'		=> $this->input->post('selected_mutasi_seq'),
					'selected_mutasi_action'	=> $this->input->post('selected_mutasi_action'),
				);
				if ($collectData['user_input']['selected_mutasi_seq'] != FALSE) {
					$collectData['user_input']['selected_mutasi_seq'] = (is_numeric($collectData['user_input']['selected_mutasi_seq']) ? (int)$collectData['user_input']['selected_mutasi_seq'] : 0);
				} else {
					$collectData['user_input']['selected_mutasi_seq'] = 0;
				}
				if ($collectData['user_input']['selected_mutasi_action'] != FALSE) {
					$collectData['user_input']['selected_mutasi_action'] = (is_string($collectData['user_input']['selected_mutasi_action']) ? strtolower($collectData['user_input']['selected_mutasi_action']) : '');
					switch ($collectData['user_input']['selected_mutasi_action']) {
						case 'move':
							if ($collectData['user_input']['selected_mutasi_seq'] != FALSE) {
								if ($collectData['user_input']['selected_mutasi_seq'] > 0) {
									try {
										$collectData['collect']['selected_mutasi_data'] = $this->mod_mutasi->get_transaction_by('seq', $collectData['user_input']['selected_mutasi_seq']);
									} catch (Exception $ex) {
										$this->error = true;
										$this->error_msg[] = "Error exception while get data of mutasi selected seq: {$ex->getMessage()}.";
									}
								} else {
									$this->error = true;
									$this->error_msg[] = "Selected mutasi data cannot be zero value.";
								}
							} else {
								$this->error = true;
								$this->error_msg[] = "For moving deposit, selected mutasi seq cannot be empty.";
							}
						break;
						case 'delete':
							$collectData['deleted_deposit_params'] = array(
								'auto_approve_status'				=> 'deleted',
								'auto_approve_mutasi_trans_seq'		=> 0,
							);
							try {
								$collectData['collect']['deleted_deposit_data'] = $this->mod_suksesbugil->set_suksesbugil_transaction($collectData['user_input']['selected_mutasi_seq'], $collectData['deleted_deposit_params']);
							} catch (Exception $ex) {
								$this->error = true;
								$this->error_msg[] = "Error exception while trying to delete deposit data status: {$ex->getMessage()}.";
							}
							$this->session->set_flashdata('error', FALSE);
							$this->session->set_flashdata('action_message', "Success delete deposit data");
							redirect(base_url($collectData['base_path'] . '/suksesbugil/depositaction/delete/' . $collectData['trans_data']->seq));
							exit;
						break;
						case 'reject':
						case 'failed':
							try {
								$collectData['sb_response_rejected_data'] = $this->mod_cli->action_sb_deposit_data_by_curl('reject', $collectData['trans_data']->bank_code, $collectData['trans_data']);
							} catch (Exception $ex) {
								$this->error = true;
								$this->error_msg[] = "Reject deposit data return exception error with message: {$ex->getMessage()}.";
							}
						break;
						case 'undo':
						case 'waiting':
						default:
							$collectData['undo_deposit_params'] = array(
								'auto_approve_status'				=> 'waiting',
							);
							try {
								$collectData['collect']['deleted_deposit_data'] = $this->mod_suksesbugil->set_suksesbugil_transaction($collectData['user_input']['selected_mutasi_seq'], $collectData['undo_deposit_params']);
							} catch (Exception $ex) {
								$this->error = true;
								$this->error_msg[] = "Error exception while trying to undo deposit data status: {$ex->getMessage()}.";
							}
							$this->session->set_flashdata('error', FALSE);
							$this->session->set_flashdata('action_message', "Success undo deposit data");
							redirect(base_url($collectData['base_path'] . '/suksesbugil/depositaction/undo/' . $collectData['trans_data']->seq));
							exit;
						break;
					}
				}
			}
			if (!$this->error) {
				if ($collectData['user_input']['selected_mutasi_action'] != FALSE) {
					switch (strtolower($collectData['user_input']['selected_mutasi_action'])) {
						case 'failed':
						case 'reject':
							$collectData['failed_deposit_params'] = array(
								'auto_approve_status'				=> 'failed',
							);
							try {
								$collectData['collect']['failed_deposit_data'] = $this->mod_suksesbugil->set_suksesbugil_transaction($collectData['user_input']['selected_mutasi_seq'], $collectData['failed_deposit_params']);
							} catch (Exception $ex) {
								$this->error = true;
								$this->error_msg[] = "Error exception while trying to reject deposit data status as failed with exception: {$ex->getMessage()}.";
							}
						break;
						case 'move':
							if (is_array($collectData['collect']['selected_mutasi_data']) && (count($collectData['collect']['selected_mutasi_data']) > 0)) {
								foreach ($collectData['collect']['selected_mutasi_data'] as $mb_data_val) {
									$collectData['collect']['transaction_mutasi_data'] = $mb_data_val;
								}
								if (!isset($collectData['collect']['transaction_mutasi_data']->seq)) {
									$this->error = true;
									$this->error_msg[] = "Transaction mutasi data not in properly format as expected.";
								} else {
									// Validate of mutasi-data with deposit-data
									if (strtoupper($collectData['collect']['transaction_mutasi_data']->is_approved) === 'Y') {
										$this->error = true;
										$this->error_msg[] = "Transaction mutasi data already approved.";
									}
									if ((int)$collectData['collect']['transaction_mutasi_data']->transaction_amount !== (int)$collectData['trans_data']->transaction_amount) {
										$this->error = true;
										$this->error_msg[] = "Amount of deposit not same with transaction mutasi data amount.";
									}
									if (in_array($collectData['trans_data']->auto_approve_status, array('approved', 'failed', 'already'))) {
										$this->error = true;
										$this->error_msg[] = "Deposit data it was on this status (approved, already, or failed).";
									}
								}
							} else {
								$this->error = true;
								$this->error_msg[] = "Selected mutasi data not exists.";
							}
						break;
					}
				}
			}
			if (!$this->error) {
				if ($collectData['user_input']['selected_mutasi_action'] != FALSE) {
					switch (strtolower($collectData['user_input']['selected_mutasi_action'])) {
						case 'failed':
						case 'reject':
							$this->session->set_flashdata('error', FALSE);
							$this->session->set_flashdata('action_message', "Success reject deposit data");
							redirect(base_url($collectData['base_path'] . '/suksesbugil/depositaction/delete/' . $collectData['trans_data']->seq));
							exit;
						break;
						case 'move':
							$collectData['bank_mutasi_transaction_data_match'] = array(
								 $collectData['trans_data']->seq => array(
									'mutasi' => $collectData['collect']['transaction_mutasi_data'],
									'suksesbugil' => $collectData['trans_data'],
								),
							);
							try {
								$collectData['bank_mutasi_transaction_approved_data'] = $this->mod_cli->match_autoapprove_by_curl('approve', $collectData['collect']['transaction_mutasi_data']->bank_code, $collectData['bank_mutasi_transaction_data_match'], $collectData['collect']['userdata']);
							} catch (Exception $ex) {
								$this->error = true;
								$this->error_msg[] = "Matching auto approve return exception error with message: {$ex->getMessage()}.";
							}
						break;
					}
				}
			}
			if (!$this->error) {
				if ($collectData['user_input']['selected_mutasi_action'] != FALSE) {
					if (strtolower($collectData['user_input']['selected_mutasi_action']) === 'move') {
						$this->session->set_flashdata('error', FALSE);
						$this->session->set_flashdata('action_message', "Success moving deposit data");
						redirect(base_url($collectData['base_path'] . '/suksesbugil/depositaction/move/' . $collectData['trans_data']->seq));
						exit;
					}
				}
			}
		}
		//--------------------------------------------------------------------
		if (!$this->error) {
			if (isset($collectData['trans_data']->mutasi_bank_seq)) {
				$collectData['collect']['bank_type_data'] = $this->mod_mutasi->get_bank_type_by('seq', $collectData['trans_data']->mutasi_bank_seq);
			}
			$collectData['date_params'] = array(
				'starting'		=> new DateTime($collectData['trans_data']->transaction_datetime),
				'stopping'		=> $this->DateObject,
			);
			switch (strtolower($collectData['action_type'])) {
				case 'delete':
					$collectData['transaction_params'] = array(
						'is_approved'					=> 'N',
						'is_deleted'					=> 'N',
						'transaction_action_status'		=> array('new', 'update'),
						'transaction_code'				=> 'CR',
					);
				break;
				case 'move':
				default:
					$collectData['transaction_params'] = array(
						'is_approved'					=> 'N',
						'transaction_action_status'		=> array('new', 'update', 'already', 'failed'),
						'transaction_code'				=> 'CR',
					);
				break;
			}
			if ((int)$collectData['transaction_search_amount'] > 0) {
				$collectData['transaction_params']['transaction_amount'] = sprintf("%d", $collectData['transaction_search_amount']);
			}
			//$collectData['date_params']
			try {
				$collectData['collect']['mutasi_data'] = $this->mod_mutasi->get_mb_transdata_for_mutasi_action($collectData['trans_data']->mutasi_bank_account_seq, $collectData['collect']['transaction_date'], $collectData['transaction_params']);
			} catch (Exception $ex) {
				$this->error = true;
				$this->error_msg[] = "Error exception while get count mutasi data: {$ex->getMessage()}.";
			}
		}
		/*
		if (!$this->error) {
			if (count($collectData['collect']['mutasi_data']) === 0) {
				$this->error = true;
				$this->error_msg[] = "There is no mb trans-data for accept deposit.";
			}
		}
		*/
		
		
		
		
		
		
		if (!$this->error) {
			switch (strtolower($collectData['action_type'])) {
				case 'delete':
					$collectData['title'] .= ': Delete';
				break;
				case 'move':
				default:
					$collectData['title'] .= ': Move';
				break;
			}
			$this->load->view("{$this->base_mutasi['base_path']}/mutasi.php", $collectData);
			
		} else {
			$this->session->set_flashdata('error', TRUE);
			$error_to_show = "";
			foreach ($this->error_msg as $keval) {
				$error_to_show .= $keval;
			}
			$this->session->set_flashdata('action_message', $error_to_show);
			redirect(base_url($collectData['base_path'] . '/suksesbugil/depositaction/move/' . $collectData['deposit_seq']));
			exit;
		}
		
		//$this->load->view("{$this->base_mutasi['base_path']}/suksesbugil/suksesbugil-transaction-list-deposit-modal.php", $collectData);
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//------------------------------------------------------------------------------------------------------
	function sbdetails($detail_code = 'all') {
		$collectData = array(
			'title'					=> 'Deposit Details',
			'base_path'				=> $this->base_mutasi['base_path'],
			'base_dashboard_path'	=> 'dashboard',
			'collect'				=> array(),
			'detail_code'			=> (is_string($detail_code) ? strtolower($detail_code) : 'all'),
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
		$collectData['allowed_codes'] = array('waiting', 'approved', 'canceled', 'deleted', 'already', 'failed');
		if (!in_array($collectData['detail_code'], $collectData['allowed_codes'])) {
			$collectData['detail_code'] = 'all';
		}
		if (strtolower($collectData['detail_code']) !== 'all') {
			$collectData['page'] = 'suksesbugil-deposit-details';
			$collectData['collect']['auto_approve_description'] = $this->mod_suksesbugil->get_auto_approve_by('code', $collectData['detail_code']);
		} else {
			$collectData['page'] = 'suksesbugil-deposit-details-indexed';
			$collectData['collect']['auto_approve_description'] = $this->mod_suksesbugil->get_auto_approve_by_indexed();
		}
		
		if (!$this->error) {
			$this->load->view("{$this->base_mutasi['base_path']}/mutasi.php", $collectData);
		}
	}
	function sbdetailsedit($detail_seq = 0) {
		$collectData = array(
			'page'					=> 'suksesbugil-deposit-details',
			'title'					=> 'Deposit Details',
			'base_path'				=> $this->base_mutasi['base_path'],
			'base_dashboard_path'	=> 'dashboard',
			'collect'				=> array(),
			'detail_seq'			=> (is_numeric($detail_seq) ? (int)$detail_seq : 0),
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
		/*
		$collectData['allowed_codes'] = array('waiting', 'approved', 'canceled', 'deleted', 'already', 'failed');
		if (!in_array($collectData['detail_code'], $collectData['allowed_codes'])) {
			$collectData['detail_code'] = 'all';
		}
		*/
		if (!$this->error) {
			$collectData['collect']['auto_approve_description'] = $this->mod_suksesbugil->get_auto_approve_by('seq', $collectData['detail_seq']);
			if (!isset($collectData['collect']['auto_approve_description']->seq)) {
				$this->error = true;
				$this->error_msg[] = "Auto approve description data not exists on database.";
			}
		}
		if (!$this->error) {
			$this->form_validation->set_rules('auto_approve_status_description', 'Deposit description details', 'max_length[1024]');
			if ($this->form_validation->run() == FALSE) {
				$this->error = true;
				$this->error_msg[] = "Form validation return error.";
				$this->session->set_flashdata('error', TRUE);
				$this->session->set_flashdata('action_message', validation_errors('<div class="btn btn-warning btn-sm">', '</div>'));
				redirect(base_url("{$collectData['base_path']}/suksesbugil/sbdetails/all"));
				exit;
			} else {
				$query_params = array(
					'auto_approve_status_description'		=> $this->input->post('auto_approve_status_description'),
				);
				try {
					$collectData['update_status_description'] = $this->mod_suksesbugil->set_auto_approve_description($collectData['collect']['auto_approve_description']->seq, $query_params);
				} catch (Exception $ex) {
					$this->error = true;
					$this->error_msg[] = "Error while update deposit description: {$ex->getMessage()}";
				}
			}
		}
		if (!$this->error) {
			redirect(base_url($collectData['base_path'] . '/suksesbugil/sbdetails/' . $collectData['collect']['auto_approve_description']->auto_approve_status));
			exit;
		} else {
			$this->error_msg[] = "Form validation return error.";
			$this->session->set_flashdata('error', TRUE);
			$action_message = "";
			foreach ($this->error_msg as $error_msg) {
				$action_message .= "- {$error_msg}<br/>";
			}
			$this->session->set_flashdata('action_message', $action_message);
			redirect(base_url("{$collectData['base_path']}/suksesbugil/sbdetails/all"));
			exit;
		}
	}
	//----
	function sbdetailsofsbscheduler($detail_code = 'all') {
		$collectData = array(
			'title'					=> 'Deposit Scheduler',
			'base_path'				=> $this->base_mutasi['base_path'],
			'base_dashboard_path'	=> 'dashboard',
			'collect'				=> array(),
			'detail_code'			=> (is_string($detail_code) ? strtolower($detail_code) : 'all'),
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
		$collectData['allowed_codes'] = array('waiting', 'approved', 'canceled', 'deleted', 'already', 'failed');
		if (!in_array($collectData['detail_code'], $collectData['allowed_codes'])) {
			$collectData['detail_code'] = 'all';
		}
		if (strtolower($collectData['detail_code']) === 'all') {
			$collectData['page'] = 'suksesbugil-deposit-scheduler-index';
			$collectData['collect']['scheduler_banks'] = $this->mod_suksesbugil->get_sb_banks(0);
		} else {
			$this->error = true;
			$this->error_msg[] = "Allowed only all preview sb-bank data.";
		}
		// Load View
		if (!$this->error) {
			$this->load->view("{$this->base_mutasi['base_path']}/mutasi.php", $collectData);
		}
	}
	function switchsbdetailsofsbscheduler($bank_seq = 0) {
		$collectData = array(
			'page'					=> 'mutasi-account-edit',
			'title'					=> 'Mutasi Bank',
			'base_path'				=> $this->base_mutasi['base_path'],
			'base_dashboard_path'	=> 'dashboard',
			'collect'				=> array(),
			'bank_seq'			=> (is_numeric($bank_seq) ? (int)$bank_seq : 0),
		);
		//================================================================
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
		$collectData['collect']['bank_type'] = $this->mod_suksesbugil->get_sb_banks(0);
		try {
			$collectData['sb_bank_data'] = $this->mod_suksesbugil->get_sb_bank_by('seq', $collectData['bank_seq']);
		} catch (Exception $ex) {
			$this->error = true;
			$this->error_msg[] = "Error exception while get sb-bank-data by seq: {$ex->getMessage()}";
		}
		if (!$this->error) {
			if (!isset($collectData['sb_bank_data']->seq)) {
				$this->error = true;
				$this->error_msg[] = "Sb data bank not exists on database.";
			}
		}
		//================================================================
		if (!$this->error) {
			$this->form_validation->set_rules('power', 'Switch Power', 'required|max_length[4]|xss_clean');
			if ($this->form_validation->run() == FALSE) {
				$this->error = true;
				$this->error_msg[] = "Form validation return error.";
				$this->session->set_flashdata('error', TRUE);
				$this->session->set_flashdata('action_message', validation_errors('<div class="fa fa-ban">', '</div>'));
				redirect(base_url("{$collectData['base_path']}/suksesbugil/sbdetailsofsbscheduler/all"));
				exit;
			}
		}
		if (!$this->error) {
			$collectData['input_params'] = array(
				'power' => $this->input->post('power'),
			);
			if (!is_string($collectData['input_params']['power'])) {
				$this->error = true;
				$this->error_msg[] = "Power should bo in string datatype.";
			} else {
				$collectData['input_params']['power'] = strtolower($collectData['input_params']['power']);
				if (!in_array($collectData['input_params']['power'], array('on', 'off'))) {
					$collectData['input_params']['power'] = 'off';
				}
			}
		}
		if (!$this->error) {
			$collectData['query_params'] = array(
				'bank_is_active'			=> ((strtolower($collectData['input_params']['power']) === 'on') ? 'Y' : 'N'),
				'bank_datetime_edit'		=> $this->DateObject->format('Y-m-d H:i:s'),
				'bank_edited_by'			=> (isset($this->authentication->localdata['account_email']) ? $this->authentication->localdata['account_email'] : 'system@root'),
			);
			try {
				$collectData['updated_bank_item_rows'] = $this->mod_suksesbugil->set_bank_power_by_seq($collectData['sb_bank_data']->seq, $collectData['query_params']);
			} catch (Exception $ex) {
				$this->error = true;
				$this->error_msg[] = "Error exception while update sb-bank power: {$ex->getMessage()}";
			}
		}
		if (!$this->error) {
			if ((int)$collectData['updated_bank_item_rows'] > 0) {
				$collectData['collect']['response'] = array(
					'status'		=> "SUCCESS",
					'redirect'		=> base_url($collectData['base_path'] . '/suksesbugil/sbdetailsofsbscheduler/all'),
					'errors'		=> FALSE,
				);
			} else {
				$collectData['collect']['response'] = array(
					'status'		=> "FAILED",
					'redirect'		=> base_url($collectData['base_path'] . '/suksesbugil/sbdetailsofsbscheduler/all'),
					'errors'		=> FALSE,
				);
			}
		} else {
			$collectData['collect']['response'] = array(
				'status'		=> "SUCCESS",
				'redirect'		=> base_url($collectData['base_path'] . '/suksesbugil/sbdetailsofsbscheduler/all'),
				'errors'		=> $this->error_msg,
			);
		}
		echo json_encode($collectData['collect']['response'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
	}
	function mutasibanktime($bank_code = 'all') {
		$collectData = array(
			'page'					=> 'suksesbugil-mutasi-bank-edit-time',
			'title'					=> 'Bank Active Time',
			'base_path'				=> $this->base_mutasi['base_path'],
			'base_dashboard_path'	=> 'dashboard',
			'collect'				=> array(),
			'bank_code'				=> (is_string($bank_code) ? strtolower($bank_code) : 'all'),
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
			// Sort by bank code
			$collectData['bank_codes'] = array();
			foreach ($collectData['collect']['bank_type'] as $typekey => $typerow) {
				$collectData['bank_codes'][$typekey] = (isset($typerow->bank_code) ? $typerow->bank_code : '');
			}
			array_multisort($collectData['bank_codes'], SORT_ASC, $collectData['collect']['bank_type']);
			// Set query account by bank as default
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
			}
		}
		// Load View
		if (!$this->error) {
			$this->load->view("{$this->base_mutasi['base_path']}/mutasi.php", $collectData);
		}
	}
	function mutasibanktimeaction($bank_seq = 0) {
		$collectData = array(
			'page'					=> 'suksesbugil-mutasi-bank-edit-time',
			'title'					=> 'Bank Active Time',
			'base_path'				=> $this->base_mutasi['base_path'],
			'base_dashboard_path'	=> 'dashboard',
			'collect'				=> array(),
			'bank_seq'			=> (is_numeric($bank_seq) ? (int)$bank_seq : 0),
		);
		//================================================================
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
		$collectData['collect']['bank_type'] = $this->mod_mutasi->get_bank();
		try {
			$collectData['mutasi_bank_data'] = $this->mod_mutasi->get_bank_type_by('seq', $collectData['bank_seq']);
		} catch (Exception $ex) {
			$this->error = true;
			$this->error_msg[] = "Error exception while get mutasi-bank-data by seq: {$ex->getMessage()}";
		}
		if (!$this->error) {
			if (!isset($collectData['mutasi_bank_data']->seq)) {
				$this->error = true;
				$this->error_msg[] = "Mutasi data bank not exists on database.";
			}
		}
		//================================================================
		if (!$this->error) {
			$this->form_validation->set_rules('bank_restricted_datetime', 'Enable Active Datetime On Manual Pull Data', 'required|max_length[1]|xss_clean');
			$this->form_validation->set_rules('bank_datetime_starting', 'Active Time Start', 'required|max_length[8]|xss_clean');
			$this->form_validation->set_rules('bank_datetime_stopping', 'Active Time End', 'required|max_length[8]|xss_clean');
			if ($this->form_validation->run() == FALSE) {
				$this->error = true;
				$this->error_msg[] = "Form validation return error.";
				$this->session->set_flashdata('error', TRUE);
				$this->session->set_flashdata('action_message', validation_errors('<div class="fa fa-ban">', '</div>'));
				redirect(base_url("{$collectData['base_path']}/suksesbugil/mutasibanktime/all"));
				exit;
			}
		}
		if (!$this->error) {
			$collectData['input_params'] = array(
				'bank_restricted_datetime'		=> $this->input->post('bank_restricted_datetime'),
				'bank_datetime_starting'		=> $this->input->post('bank_datetime_starting'),
				'bank_datetime_stopping'		=> $this->input->post('bank_datetime_stopping'),
				'time'							=> array(
					'starting'						=> (isset($collectData['mutasi_bank_data']->bank_datetime_starting) ? $collectData['mutasi_bank_data']->bank_datetime_starting : '01:00:00'),
					'stopping'						=> (isset($collectData['mutasi_bank_data']->bank_datetime_stopping) ? $collectData['mutasi_bank_data']->bank_datetime_stopping : '22:59:59'),
				),
			);
			if (!is_string($collectData['input_params']['bank_restricted_datetime'])) {
				$this->error = true;
				$this->error_msg[] = "Enable Active Datetime should be in string datatype.";
			} else {
				$collectData['input_params']['bank_restricted_datetime'] = strtoupper($collectData['input_params']['bank_restricted_datetime']);
				if (!in_array($collectData['input_params']['bank_restricted_datetime'], array('Y', 'N'))) {
					$collectData['input_params']['bank_restricted_datetime'] = 'Y';
				}
			}
			if (is_string($collectData['input_params']['bank_datetime_starting']) || is_numeric($collectData['input_params']['bank_datetime_starting'])) {
				try {
					$time_starting = DateTime::createFromFormat('Y-m-d H:i:s', "{$this->DateObject->format('Y-m-d')} {$collectData['input_params']['bank_datetime_starting']}");
				} catch (Exception $ex) {
					$this->error = true;
					$this->error_msg[] = "Error exception create datetime from fromat for datetime-starting: {$ex->getMessage()}";
					$time_starting = FALSE;
				}
			}
			if (is_string($collectData['input_params']['bank_datetime_stopping']) || is_numeric($collectData['input_params']['bank_datetime_stopping'])) {
				try {
					$time_stopping = DateTime::createFromFormat('Y-m-d H:i:s', "{$this->DateObject->format('Y-m-d')} {$collectData['input_params']['bank_datetime_stopping']}");
				} catch (Exception $ex) {
					$this->error = true;
					$this->error_msg[] = "Error exception create datetime from fromat for datetime-stopping: {$ex->getMessage()}";
					$time_stopping = FALSE;
				}
			}
			if ($time_starting != FALSE) {
				$collectData['input_params']['time']['starting'] = $time_starting->format('H:i:s');
			}
			if ($time_stopping != FALSE) {
				$collectData['input_params']['time']['stopping'] = $time_stopping->format('H:i:s');
			}
		}
		if (!$this->error) {
			$collectData['query_params'] = array(
				'bank_restricted_datetime'			=> $collectData['input_params']['bank_restricted_datetime'],
				'bank_datetime_starting'			=> $collectData['input_params']['time']['starting'],
				'bank_datetime_stopping'			=> $collectData['input_params']['time']['stopping'],
			);
			try {
				$collectData['edited_bank_seq'] = $this->mod_mutasi->set_bank_type_time_by('seq', $collectData['mutasi_bank_data']->seq, $collectData['query_params']);
			} catch (Exception $ex) {
				$this->error = true;
				$this->error_msg[] = "Error while editing bank time active and restricted manual pull-data: {$ex->getMessage()}.";
			}
		}
		if (!$this->error) {
			$this->session->set_flashdata('error', FALSE);
			if ((int)$collectData['edited_bank_seq'] > 0) {
				$this->session->set_flashdata('action_message', 'Success editing bank active time');
			} else {
				$this->session->set_flashdata('action_message', 'There is no affected applied');
			}
			redirect(base_url("{$collectData['base_path']}/suksesbugil/mutasibanktime/{$collectData['mutasi_bank_data']->bank_code}"));
		} else {
			$this->session->set_flashdata('error', TRUE);
			$action_message = "";
			foreach ($this->error_msg as $error_msg) {
				$action_message .= "- {$error_msg}<br/>";
			}
			$this->session->set_flashdata('action_message', $action_message);
			if (isset($collectData['mutasi_bank_data']->bank_code)) {
				redirect(base_url("{$collectData['base_path']}/suksesbugil/mutasibanktime/{$collectData['mutasi_bank_data']->bank_code}"));
			} else {
				redirect(base_url("{$collectData['base_path']}/suksesbugil/mutasibanktime/all"));
			}
			exit;
		}
	}
	
	
	
	//------------------------------------------------------------------------------------------------------
	function debuglib() {
		$trans = $this->mod_suksesbugil->get_data_suksesbugil_by('bank', 'bca');
		print_r($trans);
	}
	
}





