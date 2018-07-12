<?php
defined('BASEPATH') OR exit('No direct script access allowed: Dashboard');
class Mutasi extends MY_Controller {
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
		
		# Load Model Mutasi
		$this->load->model('mutasi/Model_mutasi', 'mod_mutasi');
		# Load mutasi config
		$this->load->config('mutasi/base_mutasi');
		$this->base_mutasi = $this->config->item('base_mutasi');
		
		# Load Codeigniter helpers
		$this->load->helper('security');
		$this->load->helper('form');
		$this->load->library('form_validation');
		
		# Set Bank Active Time
		$this->set_bank_active_datetime();
	}
	private function set_bank_active_datetime() {
		$this->base_mutasi['banks_active_time'] = $this->mod_mutasi->get_bank_active_datetime($this->base_mutasi['banks']);
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
		$this->listaccount('all', 0);
	}
	function listaccount($bank_code = 'all', $pgnumber = 0) {
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
			$collectData['page'] = 'mutasi-account-list';
			$collectData['title'] = 'List Bank Account';
			
			$this->load->view("{$this->base_mutasi['base_path']}/mutasi.php", $collectData);
		} else {
			$this->session->set_flashdata('error', TRUE);
			$error_to_show = "";
			foreach ($this->error_msg as $keval) {
				$error_to_show .= $keval;
			}
			$this->session->set_flashdata('action_message', $error_to_show);
			redirect(base_url($this->imzers->base_path . '/index'));
			exit;
		}
	}
	function listbank($pgnumber = 0) {
		$collectData = array(
			'page'					=> 'mutasi-bank-list',
			'title'					=> 'Available Bank',
			'base_path'				=> $this->base_mutasi['base_path'],
			'base_dashboard_path'	=> 'dashboard',
			'collect'				=> array(),
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
		$collectData['bank_seq'] = 0;
		try {
			$collectData['collect']['bank_instances']['count'] = $this->mod_mutasi->get_bank_instance_count_by('all', $collectData['bank_seq'], $collectData['search_text']);
		} catch (Exception $ex) {
			$this->error = true;
			$this->error_msg[] = "Error exception while get all bank on count: {$ex->getMessage()}.";
		}
		if (!$this->error) {
			if (isset($collectData['collect']['bank_instances']['count']->value)) {
				if ((int)$collectData['collect']['bank_instances']['count']->value > 0) {
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
					$collectData['pagination']['start'] = $this->imzcustom->get_pagination_start($collectData['pagination']['page'], base_config('rows_per_page'), $collectData['collect']['bank_instances']['count']->value);
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
			$collectData['collect']['pagination'] = $this->imzcustom->generate_pagination(base_url("{$collectData['base_path']}/listbank/%d"), $collectData['pagination']['page'], base_config('rows_per_page'), $collectData['collect']['bank_instances']['count']->value, $collectData['pagination']['start']);
			try {
				$collectData['collect']['bank_instances']['data'] = $this->mod_mutasi->get_bank_instance_data_by('all', $collectData['bank_seq'], $collectData['search_text'], $collectData['pagination']['start'], base_config('rows_per_page'));
			} catch (Exception $ex) {
				$this->error = true;
				$this->error_msg[] = "Error while get account data items data by bank with exception: {$ex->getMessage()}";
			}
		}
		if (!$this->error) {
	
			$this->load->view("{$this->base_mutasi['base_path']}/mutasi.php", $collectData);
		} else {
			$this->session->set_flashdata('error', TRUE);
			$error_to_show = "";
			foreach ($this->error_msg as $keval) {
				$error_to_show .= $keval;
			}
			$this->session->set_flashdata('action_message', $error_to_show);
			redirect(base_url($this->imzers->base_path . '/index'));
			exit;
		}
	}
	##############################################################
	function addbank() {
		$collectData = array(
			'page'					=> 'mutasi-developer-bank-add',
			'title'					=> 'Mutasi Bank',
			'base_path'				=> $this->base_mutasi['base_path'],
			'base_dashboard_path'	=> 'dashboard',
			'collect'				=> array(),
		);
		$collectData['collect']['developer_message'] = 'Please contact developer to add bank.';
		if ($this->authentication->localdata) {
			$collectData['collect']['ggdata'] = $this->authentication->userdata;
			$collectData['collect']['userdata'] = $this->authentication->localdata;
			$collectData['collect']['roles'] = $this->mod_account->get_dashboard_roles();
			$collectData['collect']['match'] = $this->authentication->get_altorouter_match();
		} else {
			header("Location: " . base_url("{$this->imzers->base_path}/account/login"));
			exit;
		}
		$this->load->view("{$this->base_mutasi['base_path']}/mutasi.php", $collectData);
	}
	function editbank($bank_seq = 0) {
		$collectData = array(
			'page'					=> 'mutasi-developer-bank-add',
			'title'					=> 'Mutasi Bank',
			'base_path'				=> $this->base_mutasi['base_path'],
			'base_dashboard_path'	=> 'dashboard',
			'collect'				=> array(),
		);
		$collectData['collect']['developer_message'] = 'Please contact developer to edit bank.';
		if ($this->authentication->localdata) {
			$collectData['collect']['ggdata'] = $this->authentication->userdata;
			$collectData['collect']['userdata'] = $this->authentication->localdata;
			$collectData['collect']['roles'] = $this->mod_account->get_dashboard_roles();
			$collectData['collect']['match'] = $this->authentication->get_altorouter_match();
		} else {
			header("Location: " . base_url("{$this->imzers->base_path}/account/login"));
			exit;
		}
		$this->load->view("{$this->base_mutasi['base_path']}/mutasi.php", $collectData);
	}
	function add() {
		$collectData = array(
			'page'					=> 'mutasi-account-add',
			'title'					=> 'Mutasi Dashboard',
			'base_path'				=> $this->base_mutasi['base_path'],
			'base_dashboard_path'	=> 'dashboard',
			'collect'				=> array(),
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
			$collectData['input_params'] = array(
				'account_title' => $this->input->post('account_title'),
				'account_username' => $this->input->post('account_username'),
				'account_password' => $this->input->post('account_password'),
				'account_bank_seq' => $this->input->post('account_bank_seq'),
				'account_is_multiple_rekening' => $this->input->post('account_is_multiple_rekening'),
				'account_ordering' => $this->input->post('account_ordering'),
				'account_is_active' => $this->input->post('account_is_active'),
			);
		}
		
		
		//====== IF NOT ERROR
		if (!$this->error) {
			$collectData['page'] = 'mutasi-account-add';
			$collectData['title'] = 'Add Bank Account';
			
			$this->load->view("{$this->base_mutasi['base_path']}/mutasi.php", $collectData);
		} else {
			$this->session->set_flashdata('error', TRUE);
			$error_to_show = "";
			foreach ($this->error_msg as $keval) {
				$error_to_show .= $keval;
			}
			$this->session->set_flashdata('action_message', $error_to_show);
			redirect(base_url($this->imzers->base_path . '/index'));
			exit;
		}
	}
	function additem() {
		$this->add();
	}
	function addaccount() {
		$collectData = array(
			'page'					=> 'mutasi-account-add',
			'title'					=> 'Mutasi Bank',
			'base_path'				=> $this->base_mutasi['base_path'],
			'base_dashboard_path'	=> 'dashboard',
			'collect'				=> array(),
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
		$this->form_validation->set_rules('account_title', 'Account Title', 'required|max_length[64]|xss_clean');
		$this->form_validation->set_rules('account_username', 'Bank Username', 'required|max_length[32]|trim|xss_clean');
		$this->form_validation->set_rules('account_password', 'Account Password', 'required|max_length[128]');
		$this->form_validation->set_rules('account_password_confirm', 'Account Password Confirm', 'required|matches[account_password]|max_length[128]');
		$this->form_validation->set_rules('account_bank_seq', 'Account Bank', 'required|max_length[1]|numeric');
		$this->form_validation->set_rules('account_is_multiple_rekening', 'Account is Multiple Rekening', 'max_length[1]|trim|xss_clean');
		$this->form_validation->set_rules('account_ordering', 'Account Sorting', 'numeric');
		$this->form_validation->set_rules('account_is_active', 'Account is Active', 'max_length[1]|trim|xss_clean');
		//================================================================
		if ($this->form_validation->run() == FALSE) {
			$this->error = true;
			$this->error_msg[] = "Form validation return error.";
			$collectData['collect']['form_validation'] = validation_errors('<div class="alert alert-danger alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>', '</div>');
			$this->session->set_flashdata('error', TRUE);
			$this->session->set_flashdata('action_message', $collectData['collect']['form_validation']);
			
			
			redirect(base_url("{$collectData['base_path']}/mutasi/listaccount"));
			exit;
		} else {
			$collectData['query_params'] = array();
			$collectData['input_params'] = array(
				'account_title' => $this->input->post('account_title'),
				'account_username' => $this->input->post('account_username'),
				'account_password' => $this->input->post('account_password'),
				'account_bank_seq' => $this->input->post('account_bank_seq'),
				'account_is_multiple_rekening' => $this->input->post('account_is_multiple_rekening'),
				'account_ordering' => $this->input->post('account_ordering'),
				'account_is_active' => $this->input->post('account_is_active'),
			);
			$collectData['input_params']['account_bank_seq'] = (is_string($collectData['input_params']['account_bank_seq']) || is_numeric($collectData['input_params']['account_bank_seq']) ? strtolower($collectData['input_params']['account_bank_seq']) : '0');
			try {
				$collectData['collect']['bank_type_data'] = $this->mod_mutasi->get_bank_type_by('seq', $collectData['input_params']['account_bank_seq']);
			} catch (Exception $ex) {
				$this->error = true;
				$this->error_msg[] = "Error exception while get bank-data by seq: {$ex->getMessage()}";
			}
			if (!$this->error) {
				if (!isset($collectData['collect']['bank_type_data']->seq)) {
					$this->error = true;
					$this->error_msg[] = "Bank type data not exists on database.";
				} else {
					$collectData['bank_code'] = $collectData['collect']['bank_type_data']->bank_code;
					$collectData['query_params']['bank_seq'] = $collectData['collect']['bank_type_data']->seq;
				}
			}
			if (!$this->error) {
				if (is_string($collectData['input_params']['account_is_multiple_rekening'])) {
					if (!in_array($collectData['input_params']['account_is_multiple_rekening'], array('Y', 'N'))) {
						$collectData['query_params']['account_is_multiple_rekening'] = 'N';
					} else {
						if ($collectData['input_params']['account_is_multiple_rekening'] === 'N') {
							$collectData['query_params']['account_is_multiple_rekening'] = 'N';
						} else {
							$collectData['query_params']['account_is_multiple_rekening'] = 'Y'; // Later ===========>
						}
					}
				} else {
					$collectData['query_params']['account_is_multiple_rekening'] = 'N';
				}
				if (isset($collectData['input_params']['account_is_active'])) {
					if (is_string($collectData['input_params']['account_is_active'])) {
						if (!in_array($collectData['input_params']['account_is_active'], array('Y', 'N'))) {
							$collectData['query_params']['account_is_active'] = 'N';
						} else {
							$collectData['query_params']['account_is_active'] = $collectData['input_params']['account_is_active'];
						}
					} else {
						$collectData['query_params']['account_is_active'] = 'N';
					}
				} else {
					$collectData['input_params']['account_is_active'] = 'N';
				}
				if (is_numeric($collectData['input_params']['account_ordering'])) {
					$collectData['query_params']['account_ordering'] = (int)$collectData['input_params']['account_ordering'];
				} else {
					$collectData['query_params']['account_ordering'] = 0;
				}
			}
			if (!$this->error) {
				if (is_string($collectData['input_params']['account_title'])) {
					$collectData['query_params']['account_title'] = $this->imzers->safe_text_post($collectData['input_params']['account_title'], 64);
					$collectData['query_params']['account_slug'] = base_permalink($collectData['query_params']['account_title']);
				} else {
					$this->error = true;
					$this->error_msg[] = "Account title should be in string format.";
				}
			}
			if (!$this->error) {
				if (is_string($collectData['input_params']['account_username'])) {
					$collectData['query_params']['account_username'] = $this->imzers->safe_text_post($collectData['input_params']['account_username'], 32);
				}
				if (is_string($collectData['input_params']['account_password'])) {
					$collectData['query_params']['account_password'] = $this->imzers->safe_text_post($collectData['input_params']['account_password'], 128);
				}
			}
			if (!$this->error) {
				try {
					$collectData['account_item_by_type'] = $this->mod_mutasi->get_bank_account_item_single_with_type_seq($collectData['query_params']['bank_seq'], 'slug', $collectData['query_params']['account_slug']);
				} catch (Exception $ex) {
					$this->error = true;
					$this->error_msg[] = "Error exception while checking account-slug and account-title with the same bank-type from database: {$ex->getMessage()}";
				}
			}
			if (!$this->error) {
				if ($collectData['account_item_by_type'] != FALSE) {
					$this->error = true;
					$this->error_msg[] = "Account bank item title on the bank-type already exists.";
				} else {
					$collectData['query_params']['account_edit_datetime'] = $this->DateObject->format('Y-m-d H:i:s');
					$collectData['query_params']['account_owner'] = (isset($this->authentication->localdata['seq']) ? $this->authentication->localdata['seq'] : 0);
					//==== Doing insert new menu with menu-type:
					try {
						$collectData['new_account_item_seq'] = $this->mod_mutasi->insert_bank_account_by('seq', $collectData['collect']['bank_type_data']->seq, $collectData['query_params']);
					} catch (Exception $ex) {
						$this->error = true;
						$this->error_msg[] = "Error exception while insert new bank account item to bank-type with exception: {$ex->getMessage()}.";
					}
				}
			}
			if (!$this->error) {
				if ($collectData['new_account_item_seq'] != FALSE) {
					if ($collectData['new_account_item_seq'] === 0) {
						$this->error = true;
						$this->error_msg[] = "Error while insert data to database: Isert menu item.";
					} else {
						//==== Redirect to Item Type Selected
						redirect(base_url($collectData['base_path'] . '/mutasi'));
						exit;
					}
				} else {
					$this->error = true;
					$this->error_msg[] = "Error login to bank while add bank account.";
				}
			}
					
			
			//======= ERROR PERSIST ========//
			if ($this->error) {
				$action_message_string = "";
				foreach ($this->error_msg as $errorVal) {
					$action_message_string .= $errorVal . "<br/>\n";
				}
				$this->session->set_flashdata('error', TRUE);
				$this->session->set_flashdata('action_message', $action_message_string);
				redirect(base_url($collectData['base_path'] . '/mutasi/listaccount'));
			}
		}
	}
	function edititem($account_seq = 0) {
		$collectData = array(
			'page'					=> 'mutasi-account-edit',
			'title'					=> 'Mutasi Dashboard',
			'base_path'				=> $this->base_mutasi['base_path'],
			'base_dashboard_path'	=> 'dashboard',
			'collect'				=> array(),
			'account_seq'			=> (is_numeric($account_seq) ? (int)$account_seq : 0),
		);
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
			$collectData['account_data'] = $this->mod_mutasi->get_account_item_single_by('seq', $collectData['account_seq']);
		} catch (Exception $ex) {
			$this->error = true;
			$this->error_msg[] = "Error exception while get account-data by seq: {$ex->getMessage()}";
		}
		if (!$this->error) {
			if (!isset($collectData['account_data']->seq)) {
				$this->error = true;
				$this->error_msg[] = "Account data bank not exists on database.";
			}
		}
		if (!$this->error) {
			
			//=== Load View
			$collectData['title'] = "Edit: {$collectData['account_data']->account_title}";
			$this->load->view($collectData['base_path'] . '/mutasi.php', $collectData);
		} else {
			$action_message_string = "";
			foreach ($this->error_msg as $errorVal) {
				$action_message_string .= $errorVal . "<br/>\n";
			}
			$this->session->set_flashdata('error', TRUE);
			$this->session->set_flashdata('action_message', $action_message_string);
			redirect(base_url($collectData['base_path'] . '/mutasi/listaccount'));
		}
	}
	function editaccount($account_seq = 0) {
		$collectData = array(
			'page'					=> 'mutasi-account-edit',
			'title'					=> 'Mutasi Bank',
			'base_path'				=> $this->base_mutasi['base_path'],
			'base_dashboard_path'	=> 'dashboard',
			'collect'				=> array(),
			'account_seq'			=> (is_numeric($account_seq) ? (int)$account_seq : 0),
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
			$collectData['account_data'] = $this->mod_mutasi->get_account_item_single_by('seq', $collectData['account_seq']);
		} catch (Exception $ex) {
			$this->error = true;
			$this->error_msg[] = "Error exception while get account-data by seq: {$ex->getMessage()}";
		}
		if (!$this->error) {
			if (!isset($collectData['account_data']->seq)) {
				$this->error = true;
				$this->error_msg[] = "Account data bank not exists on database.";
			}
		}
		//================================================================
		$this->form_validation->set_rules('account_title', 'Account Title', 'required|max_length[64]|xss_clean');
		$this->form_validation->set_rules('account_username', 'Bank Username', 'required|max_length[32]|trim|xss_clean');
		$this->form_validation->set_rules('account_password', 'Account Password', 'required|max_length[128]');
		$this->form_validation->set_rules('account_password_confirm', 'Account Password Confirm', 'required|matches[account_password]|max_length[128]');
		$this->form_validation->set_rules('account_bank_seq', 'Account Bank', 'required|max_length[1]|numeric');
		$this->form_validation->set_rules('account_is_multiple_rekening', 'Account is Multiple Rekening', 'max_length[1]|trim|xss_clean');
		$this->form_validation->set_rules('account_ordering', 'Account Sorting', 'numeric');
		$this->form_validation->set_rules('account_is_active', 'Account is Active', 'max_length[1]|trim|xss_clean');
		//================================================================
		if ($this->form_validation->run() == FALSE) {
			$this->error = true;
			$this->error_msg[] = "Form validation return error.";
			$collectData['collect']['form_validation'] = validation_errors('<div class="alert alert-danger alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>', '</div>');
			$this->session->set_flashdata('error', TRUE);
			$this->session->set_flashdata('action_message', $collectData['collect']['form_validation']);
			
			
			redirect(base_url("{$collectData['base_path']}/mutasi/edititem/{$collectData['account_data']->seq}"));
			exit;
		} else {
			$collectData['query_params'] = array();
			$collectData['input_params'] = array(
				'account_title' => $this->input->post('account_title'),
				'account_username' => $this->input->post('account_username'),
				'account_password' => $this->input->post('account_password'),
				'account_bank_seq' => $this->input->post('account_bank_seq'),
				'account_is_multiple_rekening' => $this->input->post('account_is_multiple_rekening'),
				'account_ordering' => $this->input->post('account_ordering'),
				'account_is_active' => $this->input->post('account_is_active'),
			);
			$collectData['input_params']['account_bank_seq'] = (is_string($collectData['input_params']['account_bank_seq']) || is_numeric($collectData['input_params']['account_bank_seq']) ? strtolower($collectData['input_params']['account_bank_seq']) : '0');
			try {
				$collectData['collect']['bank_type_data'] = $this->mod_mutasi->get_bank_type_by('seq', $collectData['input_params']['account_bank_seq']);
			} catch (Exception $ex) {
				$this->error = true;
				$this->error_msg[] = "Error exception while get bank-data by seq: {$ex->getMessage()}";
			}
			if (!$this->error) {
				if (!isset($collectData['collect']['bank_type_data']->seq)) {
					$this->error = true;
					$this->error_msg[] = "Bank type data not exists on database.";
				} else {
					$collectData['bank_code'] = $collectData['collect']['bank_type_data']->bank_code;
					$collectData['query_params']['bank_seq'] = $collectData['collect']['bank_type_data']->seq;
				}
			}
			# Check if bank is active
			if (!$this->error) {
				if (strtoupper($collectData['collect']['bank_type_data']->bank_is_active) !== 'Y') {
					$this->error = true;
					$this->error_msg[] = "Bank instance is not active, please contact developer to activate bank instance.";
				}
			}
			if (!$this->error) {
				if (is_string($collectData['input_params']['account_is_multiple_rekening'])) {
					if (!in_array($collectData['input_params']['account_is_multiple_rekening'], array('Y', 'N'))) {
						$collectData['query_params']['account_is_multiple_rekening'] = 'N';
					} else {
						if ($collectData['input_params']['account_is_multiple_rekening'] === 'N') {
							$collectData['query_params']['account_is_multiple_rekening'] = 'N';
						} else {
							$collectData['query_params']['account_is_multiple_rekening'] = 'Y'; // Later ===========>
						}
					}
				} else {
					$collectData['query_params']['account_is_multiple_rekening'] = 'N';
				}
				if (isset($collectData['input_params']['account_is_active'])) {
					if (is_string($collectData['input_params']['account_is_active'])) {
						if (!in_array($collectData['input_params']['account_is_active'], array('Y', 'N'))) {
							$collectData['query_params']['account_is_active'] = 'N';
						} else {
							$collectData['query_params']['account_is_active'] = $collectData['input_params']['account_is_active'];
						}
					} else {
						$collectData['query_params']['account_is_active'] = 'N';
					}
				} else {
					$collectData['input_params']['account_is_active'] = 'N';
				}
				if (is_numeric($collectData['input_params']['account_ordering'])) {
					$collectData['query_params']['account_ordering'] = (int)$collectData['input_params']['account_ordering'];
				} else {
					$collectData['query_params']['account_ordering'] = 0;
				}
			}
			if (!$this->error) {
				if (is_string($collectData['input_params']['account_title'])) {
					$collectData['query_params']['account_title'] = $this->imzers->safe_text_post($collectData['input_params']['account_title'], 64);
					$collectData['query_params']['account_slug'] = base_permalink($collectData['query_params']['account_title']);
				} else {
					$this->error = true;
					$this->error_msg[] = "Account title should be in string format.";
				}
			}
			if (!$this->error) {
				if (is_string($collectData['input_params']['account_username'])) {
					$collectData['query_params']['account_username'] = $this->imzers->safe_text_post($collectData['input_params']['account_username'], 32);
				}
				if (is_string($collectData['input_params']['account_password'])) {
					$collectData['query_params']['account_password'] = $this->imzers->safe_text_post($collectData['input_params']['account_password'], 128);
				}
			}
			if (!$this->error) {
				try {
					$collectData['account_item_by_type'] = $this->mod_mutasi->get_bank_account_item_single_with_type_seq($collectData['query_params']['bank_seq'], 'slug', $collectData['query_params']['account_slug'], $collectData['account_data']->seq);
				} catch (Exception $ex) {
					$this->error = true;
					$this->error_msg[] = "Error exception while checking account-slug and account-title with the same bank-type from database: {$ex->getMessage()}";
				}
			}
			
			//-------------------
			if (!$this->error) {
				if ($collectData['account_item_by_type'] != FALSE) {
					$this->error = true;
					$this->error_msg[] = "Account bank item title on the bank-type already exists.";
				} else {
					$collectData['query_params']['account_edit_datetime'] = $this->DateObject->format('Y-m-d H:i:s');
					$collectData['query_params']['account_owner'] = (isset($this->authentication->localdata['seq']) ? $this->authentication->localdata['seq'] : 0);
					$this->mod_mutasi->insert_mutasi_log($collectData['account_data'], $collectData['query_params']);
					//==== Doing editing bank account by seq:
					try {
						$collectData['updated_account_item_seq'] = $this->mod_mutasi->set_bank_account_by('seq', $collectData['account_data']->seq, $collectData['query_params']);
					} catch (Exception $ex) {
						$this->error = true;
						$this->error_msg[] = "Error exception while editing bank account item to bank-type with exception: {$ex->getMessage()}.";
					}
				}
			}
			if (!$this->error) {
				if ($collectData['updated_account_item_seq'] != FALSE) {
					if ($collectData['updated_account_item_seq'] === 0) {
						$this->error = true;
						$this->error_msg[] = "Error while editing data to database: Affected rows return 0.";
					} else {
						//==== Redirect to Item Type Selected
						redirect(base_url($collectData['base_path'] . '/mutasi/listaccount/' . $collectData['collect']['bank_type_data']->bank_code));
						exit;
					}
				} else {
					$this->error = true;
					$this->error_msg[] = "Error login to bank while editing bank account.";
				}
			}
			//======= ERROR PERSIST ========//
			if ($this->error) {
				$action_message_string = "";
				foreach ($this->error_msg as $errorVal) {
					$action_message_string .= $errorVal . "<br/>\n";
				}
				$this->session->set_flashdata('error', TRUE);
				$this->session->set_flashdata('action_message', $action_message_string);
				redirect(base_url($collectData['base_path'] . '/mutasi/listaccount'));
			}
		}
	}
	function switchaccount($account_seq = 0) {
		$collectData = array(
			'page'					=> 'mutasi-account-edit',
			'title'					=> 'Mutasi Bank',
			'base_path'				=> $this->base_mutasi['base_path'],
			'base_dashboard_path'	=> 'dashboard',
			'collect'				=> array(),
			'account_seq'			=> (is_numeric($account_seq) ? (int)$account_seq : 0),
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
			$collectData['account_data'] = $this->mod_mutasi->get_account_item_single_by('seq', $collectData['account_seq']);
		} catch (Exception $ex) {
			$this->error = true;
			$this->error_msg[] = "Error exception while get account-data by seq: {$ex->getMessage()}";
		}
		if (!$this->error) {
			if (!isset($collectData['account_data']->seq)) {
				$this->error = true;
				$this->error_msg[] = "Account data bank not exists on database.";
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
				redirect(base_url("{$collectData['base_path']}/mutasi/listaccount"));
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
				'account_is_active'			=> ((strtolower($collectData['input_params']['power']) === 'on') ? 'Y' : 'N'),
				'account_edit_datetime'		=> $this->DateObject->format('Y-m-d H:i:s'),
				'account_by_edit'			=> (isset($this->authentication->localdata['account_email']) ? $this->authentication->localdata['account_email'] : 'system@root'),
			);
			try {
				$collectData['updated_account_item_rows'] = $this->mod_mutasi->set_bank_account_power_by('seq', $collectData['account_data']->seq, $collectData['query_params']);
			} catch (Exception $ex) {
				$this->error = true;
				$this->error_msg[] = "Error exception while update account mutasi power: {$ex->getMessage()}";
			}
		}
		if (!$this->error) {
			if ((int)$collectData['updated_account_item_rows'] > 0) {
				$collectData['collect']['response'] = array(
					'status'		=> "SUCCESS",
					'redirect'		=> base_url($collectData['base_path'] . '/mutasi/listaccount/' . $collectData['account_data']->bank_code),
					'errors'		=> FALSE,
				);
			} else {
				$collectData['collect']['response'] = array(
					'status'		=> "FAILED",
					'redirect'		=> base_url($collectData['base_path'] . '/mutasi/listaccount/' . $collectData['account_data']->bank_code),
					'errors'		=> FALSE,
				);
			}
		} else {
			$collectData['collect']['response'] = array(
				'status'		=> "SUCCESS",
				'redirect'		=> base_url($collectData['base_path'] . '/mutasi/listaccount'),
				'errors'		=> $this->error_msg,
			);
		}
		echo json_encode($collectData['collect']['response'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
	}
	
	
	
	
	
	//------------------------
	function transactions($account_seq = 0, $pgnumber = 0) {
		$collectData = array(
			'page'					=> 'mutasi-account-transaction',
			'title'					=> 'Mutasi Dashboard',
			'base_path'				=> $this->base_mutasi['base_path'],
			'base_dashboard_path'	=> 'dashboard',
			'collect'				=> array(),
			'account_seq'			=> (is_numeric($account_seq) ? (int)$account_seq : 0),
			'pgnumber'				=> (is_numeric($pgnumber) ? $pgnumber : 0),
		);
		$collectData['search_text'] = (isset($this->imzcustom->php_input_request['body']['search_text']) ? $this->imzcustom->php_input_request['body']['search_text'] : '');
		$collectData['search_text'] = (is_string($collectData['search_text']) || is_numeric($collectData['search_text'])) ? sprintf("%s", $collectData['search_text']) : '';
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
		try {
			$collectData['account_data'] = $this->mod_mutasi->get_account_item_single_by('seq', $collectData['account_seq']);
		} catch (Exception $ex) {
			$this->error = true;
			$this->error_msg[] = "Error exception while get account-data by seq: {$ex->getMessage()}";
		}
		if (!$this->error) {
			if (!isset($collectData['account_data']->seq)) {
				$this->error = true;
				$this->error_msg[] = "Account data bank not exists on database.";
			}
		}
		$collectData['transaction_date'] = (isset($this->imzcustom->php_input_request['body']['transaction_date']) ? $this->imzcustom->php_input_request['body']['transaction_date'] : array());
		if (!$this->error) {
			if (isset($collectData['transaction_date']['starting'])) {
				$collectData['transaction_date']['starting'] = (is_string($collectData['transaction_date']['starting']) ? $this->imzers->safe_text_post($collectData['transaction_date']['starting'], 32) : $this->DateObject->format('Y-m-d'));
			} else {
				// Make 30 Days before today as transaction date starting
				$transaction_date_starting_object = $this->authentication->create_dateobject(ConstantConfig::$timezone, 'Y-m-d', date('Y-m-d'));
				//$transaction_date_starting_object->sub(new DateInterval("P30D"));
				$transaction_date_starting_object->sub(new DateInterval("P0D"));
				$collectData['transaction_date']['starting'] = $transaction_date_starting_object->format('Y-m-d');
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
		####
		if (!$this->error) {
			$collectData['collect']['transaction_data'] = array();
			try {
				$collectData['collect']['transaction_data']['count'] = $this->mod_mutasi->get_bank_mutasi_transaction_count_by('account_seq', $collectData['account_data']->seq, $collectData['collect']['transaction_date'], $collectData['search_text']);
			} catch (Exception $ex) {
				$this->error = true;
				$this->error_msg[] = "Error exception while get count transaction items by account-seq: {$ex->getMessage()}";
			}
		}
		####
		/*
		echo "<pre>";
		if (!$this->error) {
			print_r($collectData);
		} else {
			print_r($this->error_msg);
		}
		exit;
		*/
		if (!$this->error) {
			if (isset($collectData['collect']['transaction_data']['count']->value)) {
				if ((int)$collectData['collect']['transaction_data']['count']->value > 0) {
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
					$collectData['pagination']['start'] = $this->imzcustom->get_pagination_start($collectData['pagination']['page'], base_config('rows_per_page'), $collectData['collect']['transaction_data']['count']->value);
				} else {
					$collectData['pagination'] = array(
						'page'		=> 1,
						'start'		=> 0,
					);
				}
				try {
					$collectData['collect']['transaction_data']['data'] = $this->mod_mutasi->get_bank_mutasi_transaction_data_by('account_seq', $collectData['account_data']->seq, $collectData['collect']['transaction_date'], $collectData['search_text'], $collectData['pagination']['start'], base_config('rows_per_page'));
				} catch (Exception $ex) {
					$this->error = true;
					$this->error_msg[] = "Error while get transaction item data by with exception: {$ex->getMessage()}";
				}
			} else {
				$this->error = true;
				$this->error_msg[] = "Should have value as total rows.";
			}
		}
		if (!$this->error) {
			$collectData['collect']['pagination'] = $this->imzcustom->generate_pagination(base_url("{$collectData['base_path']}/mutasi/transactions/{$collectData['account_data']->seq}/%d"), $collectData['pagination']['page'], base_config('rows_per_page'), $collectData['collect']['transaction_data']['count']->value, $collectData['pagination']['start']);
		}
		/*
		echo "<pre>";
		if (!$this->error) {
			print_r($collectData);
		} else {
			print_r($this->error_msg);
		}
		exit;
		*/
		if (!$this->error) {
			
			
			//=== Load View
			$collectData['title'] = "Mutasi Transaction: {$collectData['account_data']->account_title}";
			$collectData['page'] = 'mutasi-account-transaction';
			$this->load->view($collectData['base_path'] . '/mutasi.php', $collectData);
		} else {
			print_r($this->error_msg);
			
		}
		
	}
	function showmutasi($show_type = 'all', $account_seq = 0, $pgnumber = 0) {
		$collectData = array(
			'page'					=> 'mutasi-account-transaction-condition',
			'title'					=> 'Mutasi Dashboard',
			'base_path'				=> $this->base_mutasi['base_path'],
			'base_dashboard_path'	=> 'dashboard',
			'collect'				=> array(
				'mutasi_actions'		=> $this->base_mutasi['mutasi_actions'],
			),
			'show_type'				=> (is_string($show_type) ? strtolower($show_type) : 'all'),
			'account_seq'			=> (is_numeric($account_seq) ? (int)$account_seq : 0),
			'pgnumber'				=> (is_numeric($pgnumber) ? $pgnumber : 0),
		);
		$collectData['search_text'] = (isset($this->imzcustom->php_input_request['body']['search_text']) ? $this->imzcustom->php_input_request['body']['search_text'] : '');
		$collectData['search_text'] = (is_string($collectData['search_text']) || is_numeric($collectData['search_text'])) ? sprintf("%s", $collectData['search_text']) : '';
		$collectData['transaction_search_amount'] = (isset($this->imzcustom->php_input_request['body']['transaction_search_amount']) ? $this->imzcustom->php_input_request['body']['transaction_search_amount'] : '');
		$collectData['transaction_search_amount'] = (is_string($collectData['transaction_search_amount']) || is_numeric($collectData['transaction_search_amount'])) ? sprintf("%d", $collectData['transaction_search_amount']) : '0';
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
		$collectData['show_type'] = str_replace("_", "-", $collectData['show_type']);
		$collectData['collect']['show_types'] = (isset($this->base_mutasi['show_types']) ? $this->base_mutasi['show_types'] : array());
		if (!in_array($collectData['show_type'], $collectData['collect']['show_types'])) {
			$this->error = true;
			$this->error_msg[] = "Allowed show type are:";
			if (count($collectData['collect']['show_types']) > 0) {
				foreach ($this->base_mutasi['show_types'] as $show_type) {
					$this->error_msg[] = $show_type;
				}
			}
		}
		if (!$this->error) {
			try {
				$collectData['account_data'] = $this->mod_mutasi->get_account_item_single_by('seq', $collectData['account_seq']);
			} catch (Exception $ex) {
				$this->error = true;
				$this->error_msg[] = "Error exception while get account-data by seq: {$ex->getMessage()}";
			}
		}
		if (!$this->error) {
			if (!isset($collectData['account_data']->seq)) {
				$this->error = true;
				$this->error_msg[] = "Account data bank not exists on database.";
			}
		}
		$collectData['transaction_date'] = (isset($this->imzcustom->php_input_request['body']['transaction_date']) ? $this->imzcustom->php_input_request['body']['transaction_date'] : array());
		if (!$this->error) {
			if (isset($collectData['transaction_date']['starting'])) {
				$collectData['transaction_date']['starting'] = (is_string($collectData['transaction_date']['starting']) ? $this->imzers->safe_text_post($collectData['transaction_date']['starting'], 32) : $this->DateObject->format('Y-m-d'));
			} else {
				// Make 30 Days before today as transaction date starting
				$transaction_date_starting_object = $this->authentication->create_dateobject(ConstantConfig::$timezone, 'Y-m-d H:i:s', date('Y-m-d H:i:s'));
				//$transaction_date_starting_object->sub(new DateInterval("P30D"));
				$collectData['transaction_date']['starting'] = $transaction_date_starting_object->format('Y-m-d');
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
		// Show option of show type if administrator
		$collectData['collect']['transaction_types'] = $this->mod_mutasi->get_transaction_types_to_show();
		####
		$collectData['show_type_params'] = array();
		// Show credit or debit
		$collectData['collect']['transaction_type_to_show'] = $this->input->get('type', TRUE);
		if (is_string($collectData['collect']['transaction_type_to_show'])) {
			$collectData['collect']['transaction_type_to_show'] = strtolower($collectData['collect']['transaction_type_to_show']);
		} else {
			$collectData['collect']['transaction_type_to_show'] = 'deposit';
		}
		if (!in_array($collectData['collect']['transaction_type_to_show'], $collectData['collect']['transaction_types'])) {
			$collectData['collect']['transaction_type_to_show'] = 'deposit';
		}
		switch (strtolower($collectData['collect']['transaction_type_to_show'])) {
			case 'all':
				if (isset($collectData['show_type_params']['transaction_code'])) {
					unset($collectData['show_type_params']['transaction_code']);
				}
			break;
			case 'transfer':
				$collectData['show_type_params']['transaction_code'] = 'DB';
			break;
			case 'deposit':
			default:
				$collectData['show_type_params']['transaction_code'] = 'CR';
			break;
		}
		// Type of transaction
		switch (strtolower($collectData['show_type'])) {
			case 'approved':
				$collectData['show_type_params']['is_approved'] = 'Y';
				$collectData['show_type_params']['is_deleted'] = 'Y';
				$collectData['show_type_params']['transaction_action_status'] = 'approve';
			break;
			case 'new':
				$collectData['show_type_params']['transaction_action_status'] = array(
					'new', 'update',
				);
			break;
			case 'deleted':
				$collectData['show_type_params']['is_deleted'] = 'Y';
			break;
			case 'already':
				$collectData['show_type_params']['is_deleted'] = 'Y';
				$collectData['show_type_params']['transaction_action_status'] = 'already';
			break;
			case 'unprocessed':
				$collectData['show_type_params']['transaction_action_status'] = 'new';
				$collectData['show_type_params']['is_approved'] = 'N';
				$collectData['show_type_params']['is_deleted'] = 'N';
			break;
			case 'all':
			default:
				// Nothing
			break;
		}
		if ((int)$collectData['transaction_search_amount'] > 0) {
			$collectData['show_type_params']['transaction_amount'] = (int)$collectData['transaction_search_amount'];
		}
		//--
		if (!$this->error) {
			$collectData['collect']['transaction_data'] = array();
			try {
				$collectData['collect']['transaction_data']['count'] = $this->mod_mutasi->get_condition_bank_mutasi_transaction_count_by('account_seq', $collectData['account_data']->seq, $collectData['show_type_params'], $collectData['collect']['transaction_date'], $collectData['search_text']);
			} catch (Exception $ex) {
				$this->error = true;
				$this->error_msg[] = "Error exception while get count transaction items by account-seq and condition: {$ex->getMessage()}";
			}
		}
		if (!$this->error) {
			try {
				$collectData['collect']['transaction_data']['summaries'] = $this->mod_mutasi->get_condition_bank_mutasi_transaction_groups_by('account_seq', $collectData['account_data']->seq, $collectData['show_type_params'], $collectData['collect']['transaction_date'], $collectData['search_text']);
			} catch (Exception $ex) {
				$this->error = true;
				$this->error_msg[] = "Error exception while get summaries of mutasi transactions: {$ex->getMessage()}.";
			}
		}
		####
		/*
		echo "<pre>";
		if (!$this->error) {
			print_r($collectData);
		} else {
			print_r($this->error_msg);
		}
		exit;
		*/
		if (!$this->error) {
			if (isset($collectData['collect']['transaction_data']['count']->value)) {
				if ((int)$collectData['collect']['transaction_data']['count']->value > 0) {
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
					$collectData['pagination']['start'] = $this->imzcustom->get_pagination_start($collectData['pagination']['page'], base_config('rows_per_page'), $collectData['collect']['transaction_data']['count']->value);
				} else {
					$collectData['pagination'] = array(
						'page'		=> 1,
						'start'		=> 0,
					);
				}
				try {
					$collectData['collect']['transaction_data']['data'] = $this->mod_mutasi->get_condition_bank_mutasi_transaction_data_by('account_seq', $collectData['account_data']->seq, $collectData['show_type_params'], $collectData['collect']['transaction_date'], $collectData['search_text'], $collectData['pagination']['start'], base_config('rows_per_page'));
				} catch (Exception $ex) {
					$this->error = true;
					$this->error_msg[] = "Error while get transaction item data by with exception: {$ex->getMessage()}";
				}
			} else {
				$this->error = true;
				$this->error_msg[] = "Should have value as total rows.";
			}
		}
		if (!$this->error) {
			// Pagination Links
			$collectData['collect']['pagination'] = $this->imzcustom->generate_pagination(base_url("{$collectData['base_path']}/mutasi/showmutasi/{$collectData['show_type']}/{$collectData['account_data']->seq}/%d?type={$collectData['collect']['transaction_type_to_show']}"), $collectData['pagination']['page'], base_config('rows_per_page'), $collectData['collect']['transaction_data']['count']->value, $collectData['pagination']['start']);
			
		}

		/*
		echo "<pre>";
		if (!$this->error) {
			print_r($collectData);
		} else {
			print_r($this->error_msg);
		}
		exit;
		*/
		if (!$this->error) {
			
			
			//=== Load View
			$collectData['title'] = "Mutasi Transaction: {$collectData['account_data']->account_title}";
			$this->load->view($collectData['base_path'] . '/mutasi.php', $collectData);
		}
		
		
	}
	//==================================================================================
	// Mutasi Action
	//==================================================================================
	function mutasiaction($action_type = 'move', $trans_seq = 0) {
		$this->load->model('mutasi/Model_suksesbugil', 'mod_suksesbugil');
		$collectData = array(
			'page'					=> 'mutasi-account-transaction-condition-modal',
			'title'					=> 'Transaction Action',
			'base_path'				=> $this->base_mutasi['base_path'],
			'base_dashboard_path'	=> 'dashboard',
			'collect'				=> array(),
			'action_type'			=> (is_string($action_type) ? strtolower($action_type) : 'move'),
			'trans_seq'				=> (is_numeric($trans_seq) ? (int)$trans_seq : 0),
		);
		$collectData['search_text'] = (isset($this->imzcustom->php_input_request['body']['search_text']) ? $this->imzcustom->php_input_request['body']['search_text'] : '');
		$collectData['search_text'] = (is_string($collectData['search_text']) || is_numeric($collectData['search_text'])) ? sprintf("%s", $collectData['search_text']) : '';
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
		try {
			$collectData['transaction_data'] = $this->mod_mutasi->get_transaction_by('seq', $collectData['trans_seq']);
		} catch (Exception $ex) {
			$this->error = true;
			$this->error_msg[] = "Error exception while get transaction data: {$ex->getMessage()}.";
		}
		if (!$this->error) {
			if (is_array($collectData['transaction_data'])) {
				if (!isset($collectData['transaction_data'][0])) {
					$this->error = true;
					$this->error_msg[] = "Transaction data by trans-seq not exists on database.";
					$this->error_msg[] = $collectData['transaction_data'];
				} else {
					$collectData['collect']['transaction_data'] = $collectData['transaction_data'][0];
				}
			}
		}
		if (!$this->error) {
			if (!isset($collectData['collect']['transaction_data']->seq)) {
				$this->error = true;
				$this->error_msg[] = "Transaction data not in properly format.";
			} else {
				try {
					$collectData['collect']['sb_trans_data'] = $this->mod_suksesbugil->get_sb_trans_single_by('seq', $collectData['collect']['transaction_data']->auto_deposit_trans_seq);
				} catch (Exception $ex) {
					$this->error = true;
					$this->error_msg[] = "Error exception while get deposit sb trans data: {$ex->getMessage()}.";
				}
			}
		}
		
		
		
		
		
		
		if (!$this->error) {
			//print_r($collectData);
			$this->load->view($collectData['base_path'] . '/mutasi/mutasi-account-transaction-condition-modal.php', $collectData);
		} else {
			$collectData['error'] = array(
				'status'		=> true,
				'message'		=> $this->error_msg,
			);
			$this->load->view($collectData['base_path'] . '/mutasi/mutasi-account-transaction-condition-modal.php', $collectData);
		}
		
		
	}
	function mutasiactionprepare($action_type = 'move', $trans_seq = 0) {
		$collectData = array(
			'page'					=> 'mutasi-account-transaction-condition-prepare',
			'title'					=> 'Transaction Action',
			'base_path'				=> $this->base_mutasi['base_path'],
			'base_dashboard_path'	=> 'dashboard',
			'collect'				=> array(),
			'action_type'			=> (is_string($action_type) ? strtolower($action_type) : 'move'),
			'trans_seq'				=> (is_numeric($trans_seq) ? (int)$trans_seq : 0),
		);
		$collectData['search_text'] = (isset($this->imzcustom->php_input_request['body']['search_text']) ? $this->imzcustom->php_input_request['body']['search_text'] : '');
		$collectData['search_text'] = (is_string($collectData['search_text']) || is_numeric($collectData['search_text'])) ? sprintf("%s", $collectData['search_text']) : '';
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
		try {
			$collectData['transaction_data'] = $this->mod_mutasi->get_transaction_by('seq', $collectData['trans_seq']);
		} catch (Exception $ex) {
			$this->error = true;
			$this->error_msg[] = "Error exception while get transaction data: {$ex->getMessage()}.";
		}
		if (!$this->error) {
			if (is_array($collectData['transaction_data'])) {
				if (!isset($collectData['transaction_data'][0])) {
					$this->error = true;
					$this->error_msg[] = "Transaction data by trans-seq not exists on database.";
					$this->error_msg[] = $collectData['transaction_data'];
				} else {
					$collectData['collect']['transaction_data'] = $collectData['transaction_data'][0];
				}
			}
		}
		if (!$this->error) {
			if (!isset($collectData['collect']['transaction_data']->seq)) {
				$this->error = true;
				$this->error_msg[] = "Transaction data not in properly format.";
			}
			
			
		}
		
		
		
		
		
		
		if (!$this->error) {
			$this->load->view($collectData['base_path'] . '/mutasi/mutasi-account-transaction-condition-prepare.php', $collectData);
		} else {
			$collectData['error'] = array(
				'status'		=> true,
				'message'		=> $this->error_msg,
			);
			$this->load->view($collectData['base_path'] . '/mutasi/mutasi-account-transaction-condition-prepare.php', $collectData);
		}
		
		
	}
	
	
	
	
	
	
	
	
	//==================================================================================
	// Daily Transaction Update !== Hurray!
	//==================================================================================
	# DB tmp pulled data to real database
	private function db_update_transaction_daily($tmp_seq, $account_seq = 0) {
		$collectData = array(
			'page'					=> 'cli-mutasi-update-transaction-daily',
			'title'					=> 'Mutasi Dashboard',
			'base_path'				=> $this->base_mutasi['base_path'],
			'base_dashboard_path'	=> 'dashboard',
			'collect'				=> array(),
			'tmp_seq'				=> (is_numeric($tmp_seq) ? (int)$tmp_seq : 0),
			'account_seq'			=> (is_numeric($account_seq) ? (int)$account_seq : 0),
		);
		$collectData['transaction_date_default'] = array(
			'starting'			=> date('Y-m-d'),
			'stopping'			=> date('Y-m-d'),
		);
		$collectData['transaction_date_post'] = (isset($this->imzcustom->php_input_request['body']['transaction_date']) ? $this->imzcustom->php_input_request['body']['transaction_date'] : $collectData['transaction_date_default']);
		$collectData['date_stopping_min'] =  $this->authentication->create_dateobject(ConstantConfig::$timezone, 'Y-m-d H:i:s', date('Y-m-d H:i:s'));
		$collectData['date_stopping_min']->sub(new DateInterval("P30D")); // Minimum date-stopping
		//=================================
		if (!$this->error) {
			$collectData['collect']['account_bank_data'] = $this->mod_mutasi->get_account_item_single_by('seq', $collectData['account_seq']);
			if (!isset($collectData['collect']['account_bank_data']->seq)) {
				$this->error = true;
				$this->error_msg[] = "Account bank data not exists on database.";
			}
		}
		//============= Check Time Active
		if (!$this->error) {
			$bank_code = $collectData['collect']['account_bank_data']->bank_code;
			$collectData['between_datetime'] = array(
				'starting'	=> $this->authentication->create_dateobject(ConstantConfig::$timezone, 'Y-m-d H:i:s', $this->DateObject->format('Y-m-d') . ' ' . $this->base_mutasi['banks_active_time'][$bank_code]['starting']),
				'stopping'	=> $this->authentication->create_dateobject(ConstantConfig::$timezone, 'Y-m-d H:i:s', $this->DateObject->format('Y-m-d') . ' ' . $this->base_mutasi['banks_active_time'][$bank_code]['stopping']),
			);
			if (strtoupper($collectData['collect']['account_bank_data']->bank_restricted_datetime) === 'Y') {
				if ($this->mod_mutasi->is_datetime_between_range($this->DateObject, $collectData['between_datetime']['starting'], $collectData['between_datetime']['stopping']) !== TRUE) {
					$this->error = true;
					$this->error_msg[] = "Active bank schedule is from {$this->base_mutasi['banks_active_time'][$bank_code]['starting']} to {$this->base_mutasi['banks_active_time'][$bank_code]['stopping']}";
					$this->error_msg[] = json_encode($collectData['between_datetime'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
				}
			}
		}
		//=============
		if (!$this->error) {
			if (isset($collectData['transaction_date_post']['starting']) && isset($collectData['transaction_date_post']['stopping'])) {
				if (strtotime($collectData['transaction_date_post']['stopping']) > 0) {
					try {
						$collectData['date_stopping_object'] = $this->authentication->create_dateobject(ConstantConfig::$timezone, 'Y-m-d', $collectData['transaction_date_post']['stopping']);
					} catch (Exception $ex) {
						$this->error = true;
						$this->error_msg[] = "Error exception while create datetime object from input date-stopping: {$ex->getMessage}";
					}
				} else {
					$this->error = true;
					$this->error_msg[] = "Transaction date-stopping should be in YYYY-MM-DD format";
				}
				if (strtotime($collectData['transaction_date_post']['starting']) > 0) {
					try {
						$collectData['date_starting_object'] = $this->authentication->create_dateobject(ConstantConfig::$timezone, 'Y-m-d', $collectData['transaction_date_post']['starting']);
					} catch (Exception $ex) {
						$this->error = true;
						$this->error_msg[] = "Error exception while create datetime object from input date-starting: {$ex->getMessage}";
					}
				} else {
					$this->error = true;
					$this->error_msg[] = "Transaction date-starting should be in YYYY-MM-DD format";
				}	
			} else {
				$collectData['transaction_date'] = array(
					'stopping'			=> date('Y-m-d'),
				);
				$collectData['date_starting_object'] = $this->authentication->create_dateobject(ConstantConfig::$timezone, 'Y-m-d', $collectData['transaction_date']['stopping']);
				# ---- reduce date
				$reduce_date_interval = $this->base_mutasi['interval_daterange'][$bank_code];
				switch (strtolower($reduce_date_interval['unit'])) {
					case 'hour':
						$collectData['date_starting_object']->sub(new DateInterval("PT{$reduce_date_interval['amount']}H"));
					break;
					case 'day':
					default:
						$collectData['date_starting_object']->sub(new DateInterval("P{$reduce_date_interval['amount']}D"));
					break;
				}
				$collectData['transaction_date']['starting'] = $collectData['date_starting_object']->format('Y-m-d');
			}
		}
		if (!$this->error) {
			if (isset($collectData['transaction_date_post']['starting']) && isset($collectData['transaction_date_post']['stopping'])) {
				# Date Stopping
				if ($collectData['date_stopping_object']->format('Y-m-d') > $this->DateObject->format('Y-m-d')) {
					$this->error = true;
					$this->error_msg[] = "Transaction date-stopping must be today or lower of this day.";
				} else {
					if ($collectData['date_stopping_object']->format('Y-m-d') < $collectData['date_stopping_min']->format('Y-m-d')) {
						$this->error = true;
						$this->error_msg[] = "Error: Transaction date-stopping minimum is {$collectData['date_stopping_min']->format('Y-m-d')}";
					}
				}
				# Date Starting
				if ($collectData['date_starting_object']->format('Y-m-d') > $this->DateObject->format('Y-m-d')) {
					$this->error = true;
					$this->error_msg[] = "Transaction date-starting must be today or lower of this day.";
				} else {
					if ($collectData['date_starting_object']->format('Y-m-d') < $collectData['date_stopping_min']->format('Y-m-d')) {
						$this->error = true;
						$this->error_msg[] = "Error: Transaction date-starting minimum is {$collectData['date_stopping_min']->format('Y-m-d')}";
					}
				}
			}
		}
		if (!$this->error) {
			if (isset($collectData['transaction_date_post']['starting']) && isset($collectData['transaction_date_post']['stopping'])) {
				if ($collectData['date_starting_object']->format('Y-m-d') > $collectData['date_stopping_object']->format('Y-m-d')) {
					$this->error = true;
					$this->error_msg[] = "Transaction date-starting must be lower or equal than date-stopping.";
				} else {
					$collectData['transaction_date'] = array(
						'starting'			=> $collectData['date_starting_object']->format('Y-m-d'),
						'stopping'			=> $collectData['date_stopping_object']->format('Y-m-d'),
					);
				}
			}
		}
		if (!$this->error) {
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
			$collectData['input_params'] = array(
				'date'		=> array(
					'starting'		=> $collectData['collect']['transaction_date']['starting'],
					'stopping'		=> $collectData['collect']['transaction_date']['stopping'],
				),
			);
			$collectData['collect']['transaction_date_yesterday'] = array(
				'stopping'			=> $collectData['collect']['transaction_date']['starting'],
			);
			$collectData['collect']['transaction_date_yesterday']['starting'] = new DateTime($collectData['collect']['transaction_date']['starting']->format('Y-m-d'));
			switch (strtolower($this->base_mutasi['interval_daterange'][$bank_code]['unit'])) {
				case 'hour':
					$collectData['collect']['transaction_date_yesterday']['starting']->sub(new DateInterval("PT{$this->base_mutasi['interval_daterange'][$bank_code]['amount']}H"));
				break;
				case 'day':
				default:
					$collectData['collect']['transaction_date_yesterday']['starting']->sub(new DateInterval("P{$this->base_mutasi['interval_daterange'][$bank_code]['amount']}D"));
				break;
			}
		}
		
		//==== GET MUTASI
		if (!$this->error) {
			try {
				$collectData['tmp_database_data'] = $this->mod_mutasi->get_pulled_tmpdata_from_tmp_database($collectData['tmp_seq']);
			} catch (Exception $ex) {
				$this->error = true;
				$this->error_msg[] = "Error exception while get data transaction mutasi from tmp-db: {$ex->getMessage()}";
			}
		}
		if (!$this->error) {
			if (isset($collectData['tmp_database_data']->pulled_data)) {
				try {
					$collectData['collect']['transactions_data'] = json_decode($collectData['tmp_database_data']->pulled_data, true);
				} catch (Exception $ex) {
					$this->error = true;
					$this->error_msg[] = "It was an error occured while json decode pulled data: {$ex->getMessage()}.";
				}
			} else {
				$this->error = true;
				$this->error_msg[] = "data from tmp-db not containing pulled_data.";
			}
		}
		if (!isset($collectData['collect']['account_bank_data']->bank_code)) {
			$this->error = true;
			$this->error_msg[] = "Bank code should be exists from collect of account-bank-data.";
		}
		if (!$this->error) {
			if (!isset($collectData['collect']['transactions_data']['items'])) {
				$this->error = true;
				$this->error_msg[] = "No items of mutasi transaction-data by today.";
				$this->error_msg[] = $collectData['collect']['transactions_data'];
			} else {
				if (!is_array($collectData['collect']['transactions_data']['items'])) {
					$this->error = true;
					$this->error_msg[] = "Transaction item data not in array datatype.";
				}
			}
		}
		if (!$this->error) {
			$collectData['collect_count_items'] = array(
				'fetch'				=> count($collectData['collect']['transactions_data']['items']),
				'fetched_items'		=> $collectData['collect']['transactions_data']['items'],
			);
			try {
				$collectData['collect_count_items']['database_today'] = $this->mod_mutasi->get_count_items_by_seq_with_insertdate($collectData['collect']['account_bank_data']->seq, $collectData['collect']['transaction_date']);
				$collectData['collect_count_items']['database_yesterday'] = $this->mod_mutasi->get_count_items_by_seq_with_insertdate($collectData['collect']['account_bank_data']->seq, $collectData['collect']['transaction_date_yesterday']);
			} catch (Exception $ex) {
				$this->error = true;
				$this->error_msg[] = "Cannot count inserted data fetched to database_today.";
			}
		}
		if (!$this->error) {
			if (!isset($collectData['collect_count_items']['database_today']->value)) {
				$this->error = true;
				$this->error_msg[] = "No value count from database_today insert fetch.";
			}
		}
		//------------------
		if (!$this->error) {
			$collectData['check_data_of_db'] = array();
			$collectData['insert_data_to_db'] = array();
			$mutasi_i = 0;
			switch (strtolower($collectData['collect']['account_bank_data']->bank_code)) {
				case 'bni':
					$collectData['collect_count_items']['diff'] = ($collectData['collect_count_items']['fetch'] - $collectData['collect_count_items']['database_today']->value);
					$mutasi_i = 0;
					foreach ($collectData['collect']['transactions_data']['items'] as $transitemVal) {
						if (isset($transitemVal['transaction_date'])) {
							if (is_string($transitemVal['transaction_date']) || is_numeric($transitemVal['transaction_date'])) {
								$transaction_date = trim($transitemVal['transaction_date']);
								try {
									$transaction_date_object = DateTime::createFromFormat('d/m/Y', "{$transitemVal['transaction_date']}/{$this->DateObject->format('Y')}");
								} catch (Exception $ex) {
									throw $ex;
									$transaction_date_object = FALSE;
								}
								if ($transaction_date_object != FALSE) {
									if ($transaction_date_object->format('Y-m-d') === $this->DateObject->format('Y-m-d')) {
										$collectData['insert_data_to_db'][$mutasi_i] = $transitemVal;
									}
								}
							}
						}
						$mutasi_i += 1;
					}
				break;
				case 'mandiri':
				case 'bri':
					$collectData['collect_count_items']['diff'] = ($collectData['collect_count_items']['fetch'] - $collectData['collect_count_items']['database_today']->value);
					$mutasi_i = 0;
					foreach ($collectData['collect']['transactions_data']['items'] as $transitemVal) {
						if (isset($transitemVal['transaction_date'])) {
							if (is_string($transitemVal['transaction_date']) || is_numeric($transitemVal['transaction_date'])) {
								$transaction_date = trim($transitemVal['transaction_date']);
								try {
									$transaction_date_object = DateTime::createFromFormat('d/m/Y', "{$transitemVal['transaction_date']}/{$this->DateObject->format('Y')}");
								} catch (Exception $ex) {
									throw $ex;
									$transaction_date_object = FALSE;
								}
								if ($transaction_date_object != FALSE) {
									if ($transaction_date_object->format('Y-m-d') === $this->DateObject->format('Y-m-d')) {
										$collectData['insert_data_to_db'][$mutasi_i] = $transitemVal;
									}
								}
							}
						}
						$mutasi_i += 1;
					}
				break;
				case 'bca':
				default:
					$collectData['collect_count_items']['diff'] = ($collectData['collect_count_items']['fetch'] - $collectData['collect_count_items']['database_today']->value);
					$mutasi_i = 0;
					foreach ($collectData['collect']['transactions_data']['items'] as $transitemVal) {
						if (isset($transitemVal['transaction_date'])) {
							if (is_string($transitemVal['transaction_date']) || is_numeric($transitemVal['transaction_date'])) {
								$transaction_date = trim($transitemVal['transaction_date']);
								try {
									$transaction_date_object = DateTime::createFromFormat('d/m/Y', "{$transitemVal['transaction_date']}/{$this->DateObject->format('Y')}");
								} catch (Exception $ex) {
									throw $ex;
									$transaction_date_object = FALSE;
								}
								if ($transaction_date_object != FALSE) {
									if ($transaction_date_object->format('Y-m-d') === $this->DateObject->format('Y-m-d')) {
										$collectData['insert_data_to_db'][$mutasi_i] = $transitemVal;
									}
								}
							}
						}
						$mutasi_i += 1;
					}
				break;
			}
			$collectData['collect_count_items']['allow_insert'] = count($collectData['insert_data_to_db']);
		}
		//======================================================================
		// Debug
		/*
		echo "<pre>";
		if (!$this->error) {
			print_r($collectData['insert_data_to_db']);
			
		} else {
			print_r($this->error_msg);
		}
		exit;
		*/
		//======================================================================
		
		if (!$this->error) {
			$collectData['allow_insert'] = FALSE;
			$collectData['difference_between_count'] = array();
			switch (strtolower($collectData['collect']['account_bank_data']->bank_code)) {
				case 'bni':
					$collectData['allow_insert'] = TRUE;
					$collectData['difference_between_count'] = array(
						'plus'	=> ($collectData['collect_count_items']['fetch'] - $collectData['collect_count_items']['database_yesterday']->value),
						'minus'	=> ($collectData['collect_count_items']['database_yesterday']->value - $collectData['collect_count_items']['fetch']),
					);
				break;
				case 'mandiri':
				case 'bri':
					if ($collectData['collect_count_items']['fetch'] >= $collectData['collect_count_items']['database_today']->value) {
						$collectData['allow_insert'] = TRUE;
						$collectData['difference_between_count'] = array(
							'plus'	=> ($collectData['collect_count_items']['fetch'] - $collectData['collect_count_items']['database_yesterday']->value),
							'minus'	=> ($collectData['collect_count_items']['database_yesterday']->value - $collectData['collect_count_items']['fetch']),
						);
					}
				break;
				case 'bca':
				default:
					if ($collectData['collect_count_items']['fetch'] >= $collectData['collect_count_items']['database_today']->value) {
						$collectData['allow_insert'] = TRUE;
						$collectData['difference_between_count'] = array(
							'plus'	=> ($collectData['collect_count_items']['fetch'] - $collectData['collect_count_items']['database_yesterday']->value),
							'minus'	=> ($collectData['collect_count_items']['database_yesterday']->value - $collectData['collect_count_items']['fetch']),
						);
					}
				break;
			}
		}
		//-----------------------------------------
		//-----------------------------------------
		if (!$this->error) {
			if ($collectData['allow_insert'] === TRUE) {
				$insert_i = 0;
				foreach ($collectData['insert_data_to_db'] as $insertKey => $insertVal) {
					$collectData['check_data_of_db'][] = $this->mod_mutasi->insert_transaction_fetch_by('account_seq', $collectData['collect']['account_bank_data']->seq, $collectData['collect']['account_bank_data'], $insertVal, $collectData['collect']['transaction_date'], $insertKey, $insert_i);
					$insert_i++;
				}
			}
		}
		/*
		echo "<pre>";
		if (!$this->error) {
			print_r($collectData['check_data_of_db']);
			
		} else {
			print_r($this->error_msg);
		}
		exit;
		*/
		//-----------------------------------------
		//-----------------------------------------
		// Show Collectdata or error
		if (!$this->error) {
			$return = array(
				'insert_data_to_db'			=> $collectData['insert_data_to_db'],
				'check_data_of_db'			=> $collectData['check_data_of_db'],
				'collect_count_items'		=> $collectData['collect_count_items'],
				'difference_between_count'	=> $collectData['difference_between_count'],
				'allow_insert'				=> $collectData['allow_insert'],
			);
		} else {
			$return = $this->error_msg;
		}
		return $return;
	}
	//-----------------------------------------------------------------------------------------------------------
	// Push to database
	//-----------------------------------------------------------------------------------------------------------
	function push_mutasi_data() {
		$collectData = array(
			'page'					=> 'cli-mutasi-push-mutasi-data',
			'title'					=> 'Mutasi Dashboard',
			'base_path'				=> $this->base_mutasi['base_path'],
			'base_dashboard_path'	=> 'dashboard',
			'collect'				=> array(),
		);
		//=================================
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
			$this->form_validation->set_rules('tmp_seq', 'Temp Table Data Holder', 'numeric|max_length[11]|xss_clean');
			$this->form_validation->set_rules('account_seq', 'Bank Account Seq', 'numeric|max_length[11]|xss_clean');
			if ($this->form_validation->run() == FALSE) {
				$this->error = true;
				$this->error_msg[] = "Form validation return error.";
				$this->session->set_flashdata('error', TRUE);
				$this->session->set_flashdata('action_message', validation_errors('<div class="btn btn-warning btn-sm">', '</div>'));
				redirect(base_url("{$collectData['base_path']}/mutasi"));
				exit;
			} else {
				$collectData['query_params'] = array(
					'tmp_seq'				=> (is_numeric($this->input->post('tmp_seq')) ? (int)$this->input->post('tmp_seq') : 0),
					'account_seq'			=> (is_numeric($this->input->post('account_seq')) ? (int)$this->input->post('account_seq') : 0),
				);
				try {
					$collectData['collect']['push_mutasi_data'] = $this->db_update_transaction_daily($collectData['query_params']['tmp_seq'], $collectData['query_params']['account_seq']);
				} catch (Exception $ex) {
					$this->error = true;
					$this->error_msg[] = "Cannot using db_update_transaction_daily with exception: {$ex->getMessage()}";
				}
			}
		}
		// Redirect to success add
		if (!$this->error) {
			$this->session->set_flashdata('error', FALSE);
			$this->session->set_flashdata('action_message', 'Success push new data to database');
			redirect(base_url("{$collectData['base_path']}/mutasi/showmutasi/all/{$collectData['query_params']['account_seq']}"));
		} else {
			$error_msg = "";
			foreach ($this->error_msg as $msg) {
				$error_msg .= $msg;
			}
			$this->session->set_flashdata('error', TRUE);
			$this->session->set_flashdata('action_message', $error_msg);
			if (isset($collectData['query_params']['account_seq'])) {
				redirect(base_url("{$collectData['base_path']}/mutasi/showmutasi/all/{$collectData['query_params']['account_seq']}"));
			} else {
				redirect(base_url("{$collectData['base_path']}/mutasi/listaccount"));
			}
		}
	}
	//---------------------------------------------- pull data from live bank
	function pull_mutasi_data($account_seq = 0) {
		$collectData = array(
			'page'					=> 'mutasi-account-transaction-pulled-modal',
			'title'					=> 'Mutasi Dashboard',
			'base_path'				=> $this->base_mutasi['base_path'],
			'base_dashboard_path'	=> 'dashboard',
			'collect'				=> array(),
			'account_seq'			=> (is_numeric($account_seq) ? (int)$account_seq : 0),
		);
		$collectData['transaction_date_post'] = (isset($this->imzcustom->php_input_request['body']['transaction_date']) ? $this->imzcustom->php_input_request['body']['transaction_date'] : array());
		$collectData['date_stopping_min'] =  $this->authentication->create_dateobject(ConstantConfig::$timezone, 'Y-m-d H:i:s', date('Y-m-d H:i:s'));
		$collectData['date_stopping_min']->sub(new DateInterval("P30D")); // Minimum date-stopping
		//=================================
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
			$collectData['collect']['account_bank_data'] = $this->mod_mutasi->get_account_item_single_by('seq', $collectData['account_seq']);
			if (!isset($collectData['collect']['account_bank_data']->seq)) {
				$this->error = true;
				$this->error_msg[] = "Account bank data not exists on database.";
			}
		}
		//=============
		if (!$this->error) {
			$bank_code = $collectData['collect']['account_bank_data']->bank_code;
			if (isset($collectData['transaction_date_post']['starting']) && isset($collectData['transaction_date_post']['stopping'])) {
				if (strtotime($collectData['transaction_date_post']['stopping']) > 0) {
					try {
						$collectData['date_stopping_object'] = $this->authentication->create_dateobject(ConstantConfig::$timezone, 'Y-m-d', $collectData['transaction_date_post']['stopping']);
					} catch (Exception $ex) {
						$this->error = true;
						$this->error_msg[] = "Error exception while create datetime object from input date-stopping: {$ex->getMessage}";
					}
				} else {
					$this->error = true;
					$this->error_msg[] = "Transaction date-stopping should be in YYYY-MM-DD format";
				}
				if (strtotime($collectData['transaction_date_post']['starting']) > 0) {
					try {
						$collectData['date_starting_object'] = $this->authentication->create_dateobject(ConstantConfig::$timezone, 'Y-m-d', $collectData['transaction_date_post']['starting']);
					} catch (Exception $ex) {
						$this->error = true;
						$this->error_msg[] = "Error exception while create datetime object from input date-starting: {$ex->getMessage}";
					}
				} else {
					$this->error = true;
					$this->error_msg[] = "Transaction date-starting should be in YYYY-MM-DD format";
				}	
			} else {
				$collectData['transaction_date'] = array(
					'stopping'			=> date('Y-m-d'),
				);
				$collectData['date_starting_object'] = $this->authentication->create_dateobject(ConstantConfig::$timezone, 'Y-m-d', $collectData['transaction_date']['stopping']);
				# ---- reduce date
				$reduce_date_interval = $this->base_mutasi['interval_daterange'][$bank_code];
				switch (strtolower($reduce_date_interval['unit'])) {
					case 'hour':
						$collectData['date_starting_object']->sub(new DateInterval("PT{$reduce_date_interval['amount']}H"));
					break;
					case 'day':
					default:
						$collectData['date_starting_object']->sub(new DateInterval("P{$reduce_date_interval['amount']}D"));
					break;
				}
				$collectData['transaction_date']['starting'] = $collectData['date_starting_object']->format('Y-m-d');
			}
		}
		if (!$this->error) {
			if (isset($collectData['transaction_date_post']['starting']) && isset($collectData['transaction_date_post']['stopping'])) {
				# Date Stopping
				if ($collectData['date_stopping_object']->format('Y-m-d') > $this->DateObject->format('Y-m-d')) {
					$this->error = true;
					$this->error_msg[] = "Transaction date-stopping must be today or lower of this day.";
				} else {
					if ($collectData['date_stopping_object']->format('Y-m-d') < $collectData['date_stopping_min']->format('Y-m-d')) {
						$this->error = true;
						$this->error_msg[] = "Error: Transaction date-stopping minimum is {$collectData['date_stopping_min']->format('Y-m-d')}";
					}
				}
				# Date Starting
				if ($collectData['date_starting_object']->format('Y-m-d') > $this->DateObject->format('Y-m-d')) {
					$this->error = true;
					$this->error_msg[] = "Transaction date-starting must be today or lower of this day.";
				} else {
					if ($collectData['date_starting_object']->format('Y-m-d') < $collectData['date_stopping_min']->format('Y-m-d')) {
						$this->error = true;
						$this->error_msg[] = "Error: Transaction date-starting minimum is {$collectData['date_stopping_min']->format('Y-m-d')}";
					}
				}
			}
		}
		if (!$this->error) {
			if (isset($collectData['transaction_date_post']['starting']) && isset($collectData['transaction_date_post']['stopping'])) {
				if ($collectData['date_starting_object']->format('Y-m-d') > $collectData['date_stopping_object']->format('Y-m-d')) {
					$this->error = true;
					$this->error_msg[] = "Transaction date-starting must be lower or equal than date-stopping.";
				} else {
					$collectData['transaction_date'] = array(
						'stopping'			=> $collectData['date_stopping_object']->format('Y-m-d'),
						'starting'			=> $collectData['date_starting_object']->format('Y-m-d'),
					);
				}
			}
		}
		if (!$this->error) {
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
			$collectData['input_params'] = array(
				'date'		=> array(
					'starting'		=> $collectData['collect']['transaction_date']['starting'],
					'stopping'		=> $collectData['collect']['transaction_date']['stopping'],
				),
			);
			$collectData['collect']['transaction_date_yesterday'] = array(
				'stopping'			=> $collectData['collect']['transaction_date']['starting'],
			);
			$collectData['collect']['transaction_date_yesterday']['starting'] = new DateTime($collectData['collect']['transaction_date']['starting']->format('Y-m-d'));
			switch (strtolower($this->base_mutasi['interval_daterange'][$bank_code]['unit'])) {
				case 'hour':
					$collectData['collect']['transaction_date_yesterday']['starting']->sub(new DateInterval("PT{$this->base_mutasi['interval_daterange'][$bank_code]['amount']}H"));
				break;
				case 'day':
				default:
					$collectData['collect']['transaction_date_yesterday']['starting']->sub(new DateInterval("P{$this->base_mutasi['interval_daterange'][$bank_code]['amount']}D"));
				break;
			}
		}
		
		//==== GET MUTASI
		if (!$this->error) {
			try {
				$collectData['collect']['transactions_data'] = $this->mod_mutasi->get_rekening_transaction_by('seq', $collectData['collect']['account_bank_data']->seq, $collectData['input_params']);
			} catch (Exception $ex) {
				$this->error = true;
				$this->error_msg[] = "Error exception while get data transaction mutasi from mod-mutasi: {$ex->getMessage()}";
			}
		}
		if (!$this->error) {
			if (!isset($collectData['collect']['transactions_data']['items'])) {
				$this->error = true;
				$this->error_msg[] = "No items of mutasi transaction-data by today, maybe failed while logged in to internet banking.";
				$this->error_msg[] = $collectData['collect']['transactions_data'];
			} else {
				if (!is_array($collectData['collect']['transactions_data']['items'])) {
					$this->error = true;
					$this->error_msg[] = "Transaction item data not in array datatype.";
				}
			}
		}
		//==================================================================================================
		if (!$this->error) {
			if (count($collectData['collect']['transactions_data']['items']) > 0) {
				$collectData['tmp_data_to_tmp_database'] = json_encode($collectData['collect']['transactions_data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
				try {
					$collectData['new_insert_tmp_data_seq'] = $this->mod_mutasi->pulled_tmpdata_insert_to_tmp_database($collectData['collect']['account_bank_data']->seq, $collectData['tmp_data_to_tmp_database']);
				} catch (Exception $ex) {
					$this->error = true;
					$this->error_msg[] = "Error while insert tmp pulled-data to tmp database: {$ex->getMessage()}";
				}
			}
		}
		// Check if post to insert to database
		//====================================
		if (!$this->error) {
			$this->load->view($collectData['base_path'] . '/mutasi/mutasi-account-transaction-pulled-modal.php', $collectData);
		} else {
			$error_data = array('collect' => $this->error_msg);
			$this->load->view($collectData['base_path'] . '/mutasi/mutasi-modal-error.php', $error_data);
		}
	}
	//=============================================================================================================================================
	function delete_bank_account($action_type = 'view', $account_seq = 0) {
		$collectData = array(
			'page'					=> 'mutasi-account-delete-modal',
			'title'					=> 'Mutasi Dashboard',
			'base_path'				=> $this->base_mutasi['base_path'],
			'base_dashboard_path'	=> 'dashboard',
			'collect'				=> array(),
			'action_type'			=> (is_string($action_type) ? strtolower($action_type) : 'view'),
			'account_seq'			=> (is_numeric($account_seq) ? (int)$account_seq : 0),
		);
		//=================================
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
		try {
			$collectData['collect']['account_bank_data'] = $this->mod_mutasi->get_account_item_single_by('seq', $collectData['account_seq']);
		} catch (Exception $ex) {
			$this->error = true;
			$this->error_msg[] = "Cannot get bank account data with exception: {$ex->getMessage()}";
		}
		if (!$this->error) {
			if (!isset($collectData['collect']['account_bank_data']->seq)) {
				$this->error = true;
				$this->error_msg[] = "Account data not exists on database.";
			} else {
				switch (strtolower($collectData['action_type'])) {
					case 'view':
						$this->load->view($collectData['base_path'] . '/mutasi/mutasi-account-delete-modal.php', $collectData);
					break;
					case 'delete':
					default:
						$this->form_validation->set_rules('account_bank_data_seq', 'Account sequence', 'numeric');
						if ($this->form_validation->run() == FALSE) {
							$this->error = true;
							$this->error_msg[] = "Form validation return error.";
							$this->error_msg[] = validation_errors('<div class="fa fa-axis">', '</div>');
							$this->session->set_flashdata('error', TRUE);
							$error_msg = "";
							foreach ($this->error_msg as $msg) {
								$error_msg .= $msg;
							}
							$this->session->set_flashdata('action_message', $error_msg);
							redirect(base_url("{$collectData['base_path']}/mutasi/listaccount"));
							exit;
						}
					break;
				}
			}
		}
		if (!$this->error) {
			if (strtolower($collectData['action_type']) === 'delete') {
				$collectData['input_params'] = array(
					'account_bank_data_seq'			=> $this->input->post('account_bank_data_seq'),
				);
				if ($collectData['input_params']['account_bank_data_seq'] != FALSE) {
					$collectData['input_params']['account_bank_data_seq'] = (is_numeric($collectData['input_params']['account_bank_data_seq']) ? (int)$collectData['input_params']['account_bank_data_seq'] : 0);
					if ($collectData['input_params']['account_bank_data_seq'] === 0) {
						$this->error = true;
						$this->error_msg[] = "Account bank seq is zero value.";
					} else {
						if ((int)$collectData['input_params']['account_bank_data_seq'] !== (int)$collectData['collect']['account_bank_data']->seq) {
							$this->error = true;
							$this->error_msg[] = "Input account bank seq not same with account-seq collectdata.";
						} else {
							try {
								$collectData['deleted_bank_account_response'] = $this->mod_mutasi->delete_bank_account_by('seq', $collectData['collect']['account_bank_data']->seq);
							} catch (Exception $ex) {
								$this->error = true;
								$this->error_msg[] = "Error exception while delete and wipe all of bank account data: {$ex->getMessage()}.";
							}
						}
					}
				}
			}
		}
		if (!$this->error) {
			if (strtolower($collectData['action_type']) === 'delete') {
				if ($collectData['deleted_bank_account_response'] != FALSE) {
					$this->session->set_flashdata('error', FALSE);
					$this->session->set_flashdata('action_message', 'Success delete bank account data.');
				} else {
					$this->session->set_flashdata('error', TRUE);
					$this->session->set_flashdata('action_message', 'Some error persist while deleting data.');
				}
				redirect(base_url("{$collectData['base_path']}/mutasi/listaccount/{$collectData['collect']['account_bank_data']->bank_code}"));
				exit;
			}
		} else {
			$error_msg = "";
			foreach ($this->error_msg as $msg) {
				$error_msg .= $msg;
			}
			$this->session->set_flashdata('error', TRUE);
			$this->session->set_flashdata('action_message', $error_msg);
			redirect(base_url("{$collectData['base_path']}/mutasi/listaccount"));
		}
	}
	function massedit($account_seq = 0) {
		$collectData = array(
			'page'					=> 'mutasi-account-transaction-massedit-edit',
			'title'					=> 'Mutasi Dashboard',
			'base_path'				=> $this->base_mutasi['base_path'],
			'base_dashboard_path'	=> 'dashboard',
			'collect'				=> array(),
			'account_seq'			=> (is_numeric($account_seq) ? (int)$account_seq : 0),
		);
		$collectData['transaction_date_post'] = (isset($this->imzcustom->php_input_request['body']['transaction_date']) ? $this->imzcustom->php_input_request['body']['transaction_date'] : array());
		$collectData['date_stopping_min'] =  $this->authentication->create_dateobject(ConstantConfig::$timezone, 'Y-m-d H:i:s', date('Y-m-d H:i:s'));
		$collectData['date_stopping_min']->sub(new DateInterval("P30D")); // Minimum date-stopping
		//=================================
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
			$collectData['collect']['account_bank_data'] = $this->mod_mutasi->get_account_item_single_by('seq', $collectData['account_seq']);
			if (!isset($collectData['collect']['account_bank_data']->seq)) {
				$this->error = true;
				$this->error_msg[] = "Account bank data not exists on database.";
			}
		}
		//=============
		if (!$this->error) {
			$this->form_validation->set_rules('transactionseq[]', 'Trasactions Data', 'required|xss_clean');
			$this->form_validation->set_rules('auto_deposit_trans_response', 'Transaction Status', 'required|max_length[16]|xss_clean');
			if ($this->form_validation->run() == FALSE) {
				$this->error = true;
				$this->error_msg[] = "Form validation return error.";
				$this->session->set_flashdata('error', TRUE);
				$this->session->set_flashdata('action_message', validation_errors('<div class="text text-warning">', '</div>'));
				redirect(base_url("{$collectData['base_path']}/mutasi"));
				exit;
			} else {
				$collectData['query_params'] = array(
					'seqs'				=> (is_array($this->input->post('transactionseq')) ? $this->input->post('transactionseq') : array()),
					'trans_status'		=> (is_string($this->input->post('auto_deposit_trans_response')) ? strtolower($this->input->post('auto_deposit_trans_response')) : ''),
				);
				if (!in_array($collectData['query_params']['trans_status'], $this->base_mutasi['mutasi_actions'])) {
					$this->error = true;
					$this->error_msg[] = "Transaction status should be one of this following: " . print_r($this->base_mutasi['mutasi_actions'], true);
				}
			}
		}
		if (!$this->error) {
			$collectData['push_params'] = array(
				'sequences'		=> $collectData['query_params']['seqs'],
				'data'			=> array(),
			);
			$collectData['push_params']['data']['is_new_fetch'] = 'N';
			switch (strtolower($collectData['query_params']['trans_status'])) {
				case 'waiting':
					$collectData['push_params']['data']['auto_deposit_trans_response'] = 'waiting';
					$collectData['push_params']['data']['is_deleted'] = 'N';
					$collectData['push_params']['data']['is_approved'] = 'N';
					$collectData['push_params']['data']['transaction_action_status'] = 'waiting';
				break;
				case 'canceled':
					$collectData['push_params']['data']['auto_deposit_trans_response'] = 'Set Canceled' . (isset($collectData['collect']['userdata']['account_email']) ? " by {$collectData['collect']['userdata']['account_email']}" : '');
					$collectData['push_params']['data']['is_deleted'] = 'Y';
					$collectData['push_params']['data']['is_approved'] = 'N';
					$collectData['push_params']['data']['transaction_action_status'] = 'already';
				break;
				case 'approved':
					$collectData['push_params']['data']['auto_deposit_trans_response'] = 'Set Approved' . (isset($collectData['collect']['userdata']['account_email']) ? " by {$collectData['collect']['userdata']['account_email']}" : '');
					$collectData['push_params']['data']['is_deleted'] = 'Y';
					$collectData['push_params']['data']['is_approved'] = 'Y';
					$collectData['push_params']['data']['transaction_action_status'] = 'approve';
				break;
				case 'failed':
					$collectData['push_params']['data']['auto_deposit_trans_response'] = 'Set Failed' . (isset($collectData['collect']['userdata']['account_email']) ? " by {$collectData['collect']['userdata']['account_email']}" : '');
					$collectData['push_params']['data']['is_deleted'] = 'Y';
					$collectData['push_params']['data']['is_approved'] = 'Y';
					$collectData['push_params']['data']['transaction_action_status'] = 'already';
				break;
				case 'deleted':
				default:
					$collectData['push_params']['data']['auto_deposit_trans_response'] = 'Set Deleted' . (isset($collectData['collect']['userdata']['account_email']) ? " by {$collectData['collect']['userdata']['account_email']}" : '');
					$collectData['push_params']['data']['is_deleted'] = 'Y';
					$collectData['push_params']['data']['is_approved'] = 'N';
					$collectData['push_params']['data']['transaction_action_status'] = 'already';
				break;
			}
			try {
				$collectData['update_mutasi_transaction_data'] = $this->mod_mutasi->massedit_transactions_by_transactionseqs('seq', $collectData['collect']['account_bank_data']->seq, $collectData['query_params']['trans_status'], $collectData['push_params']);
			} catch (Exception $ex) {
				$this->error = true;
				$this->error_msg[] = "Cannot execute update mutasi transaction data: {$ex->getMessage()}";
			}
		}
		if (!$this->error) {
			if ((int)$collectData['update_mutasi_transaction_data'] > 0) {
				$this->session->set_flashdata('error', FALSE);
				$this->session->set_flashdata('action_message', "Success updating {$collectData['update_mutasi_transaction_data']} rows.");
			} else {
				$this->error = true;
				$this->error_msg[] = "No items of transactions updated, return zero value of affected rows";
			}
		}
		if (!$this->error) {
			redirect(base_url("{$collectData['base_path']}/mutasi/showmutasi/all/{$collectData['collect']['account_bank_data']->seq}"));
		} else {
			$error_msg = "";
			foreach ($this->error_msg as $msg) {
				$error_msg .= $msg;
			}
			$this->session->set_flashdata('error', TRUE);
			$this->session->set_flashdata('action_message', $error_msg);
			redirect(base_url("{$collectData['base_path']}/mutasi/listaccount"));
		}
	}
	
	
	
	//-----------------------------------------------------------------------------------------------------------
	
	public function debuglib($account_seq = 0) {
		$account_bank_data = $this->mod_mutasi->get_account_item_single_by('seq', $account_seq);
		print_r($account_bank_data);
		
		$this->load->library('mutasi/Lib_bca', NULL, 'lib_bank_mutasi');
		$transaction_datetime = array(
			'starting'	=> new DateTime(date('Y-m-d')),
			'stopping'	=> new DateTime(date('Y-m-d')),
		);
		try {
			$this->lib_bank_mutasi->set_curl_init($this->lib_bank_mutasi->create_curl_headers($this->lib_bank_mutasi->headers));
			$this->lib_bank_mutasi->login($account_bank_data->account_username, $account_bank_data->account_password);
			$transactions_data = $this->lib_bank_mutasi->get_mutasi_transactions($transaction_datetime);
			$this->lib_bank_mutasi->logout();
		} catch (Exception $ex) {
			throw $ex;
			return FALSE;
		}
		
		
		print_r($transactions_data);
	}
	
}








