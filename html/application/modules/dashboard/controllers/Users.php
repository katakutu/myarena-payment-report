<?php
defined('BASEPATH') OR exit('No direct script access allowed: Dashboard');
class Users extends MY_Controller {
	public $is_admin = FALSE;
	public $is_merchant = FALSE;
	public $error = FALSE, $error_msg = array();
	protected $DateObject;
	protected $email_vendor;
	function __construct() {
		parent::__construct();
		$this->load->config('dashboard/base_dashboard');
		$this->base_dashboard = $this->config->item('base_dashboard');
		$this->load->library('dashboard/Lib_authentication', $this->base_dashboard, 'authentication');
		$this->load->model('dashboard/Model_account', 'mod_account');
		$this->load->model('dashboard/Model_users', 'mod_users');
		$this->load->helper('dashboard/dashboard_functions');
		$this->email_vendor = (isset($this->base_dashboard['email_vendor']) ? $this->base_dashboard['email_vendor'] : '');
		$this->DateObject = $this->authentication->create_dateobject(ConstantConfig::$timezone, 'Y-m-d H:i:s', date('Y-m-d H:i:s'));
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
	public function index() {
		$this->lists();
	}
	//-------------------------------------------------------------------------------------
	public function lists() {
		$collectData = array();
		$collectData['page'] = 'user-list';
		$collectData['title'] = 'User Lists';
		$collectData['base_path'] = $this->imzers->base_path;
		$collectData['collect'] = array();
		//================================
		if ($this->authentication->localdata) {
			$collectData['collect']['ggdata'] = $this->authentication->userdata;
			$collectData['collect']['userdata'] = $this->authentication->localdata;
			$collectData['collect']['datetime'] = $this->DateObject->format('Y-m-d H:i:s');
			$collectData['collect']['match'] = $this->authentication->get_altorouter_match();
			if (!isset($collectData['collect']['userdata']['account_role'])) {
				$this->error = true;
				$this->error_msg[] = "System not detect account-role of logged-in user";
			}
			if (!$this->error) {
				if (!$this->is_admin) {
					$this->error = true;
					$this->accessDenied($collectData);
				}
			}
			if (!$this->error) {
				$collectData['search_text'] = (isset($this->imzcustom->php_input_request['body']['search_text']) ? $this->imzcustom->php_input_request['body']['search_text'] : '');
				$collectData['search_text'] = (is_string($collectData['search_text']) ? $collectData['search_text'] : '');
				try {
					$collectData['count'] = $this->mod_users->get_user_count($collectData['search_text']);
				} catch (Exception $ex) {
					$this->error = true;
					$this->error_msg[] = "Error get count with search-text: {$ex->getMessage()}";
				}
			}
			if (!$this->error) {
				if (isset($collectData['count']->value)) {
					$collectData['pagination'] = array(
						'page'		=> (isset($collectData['collect']['match']['params']['segment']) ? $collectData['collect']['match']['params']['segment'] : 1),
						'start'		=> 0,
					);
					$collectData['pagination']['page'] = (is_numeric($collectData['pagination']['page']) ? sprintf("%d", $collectData['pagination']['page']) : 1);
					if ($collectData['pagination']['page'] > 0) {
						$collectData['pagination']['page'] = (int)$collectData['pagination']['page'];
					} else {
						$collectData['pagination']['page'] = 1;
					}
					$collectData['pagination']['start'] = $this->imzcustom->get_pagination_start($collectData['pagination']['page'], base_config('rows_per_page'), $collectData['count']->value);
					
					$collectData['collect']['pagination'] = $this->imzcustom->generate_pagination(base_url("{$collectData['base_path']}/users/lists/%d"), $collectData['pagination']['page'], base_config('rows_per_page'), $collectData['count']->value, $collectData['pagination']['start']);
				} else {
					$this->error = true;
					$this->error_msg[] = "Should have value as total rows.";
				}
			}
			if (!$this->error) {
				$collectData['collect']['users'] = $this->mod_users->get_user_data($collectData['search_text'], $collectData['pagination']['start'], base_config('rows_per_page'));
				$collectData['collect']['roles'] = $this->mod_account->get_dashboard_roles();
				$this->load->view('dashboard/dashboard.php', $collectData);
			}
		} else {
			header("Location: " . base_url("{$this->imzers->base_path}/account/login"));
			exit;
		}
	}
	
	
	
	
	
	
	function add() {
		$collectData = array();
		$collectData['page'] = 'user-add';
		$collectData['title'] = 'Add New User';
		$collectData['base_path'] = $this->imzers->base_path;
		$collectData['collect'] = array();
		//================================
		if ($this->authentication->localdata) {
			$collectData['collect']['ggdata'] = $this->authentication->userdata;
			$collectData['collect']['userdata'] = $this->authentication->localdata;
			$collectData['collect']['match'] = $this->authentication->get_altorouter_match();
			if (!isset($collectData['collect']['userdata']['account_role'])) {
				$this->error = true;
				$this->error_msg[] = "System not detect account-role of logged-in user";
			}
			if (!$this->error) {
				if (!$this->is_admin) {
					$this->error = true;
					$this->accessDenied($collectData);
				}
			}
			if (!$this->error) {
				$collectData['search_text'] = (isset($this->imzcustom->php_input_request['body']['search_text']) ? $this->imzcustom->php_input_request['body']['search_text'] : '');
				$collectData['search_text'] = (is_string($collectData['search_text']) ? $collectData['search_text'] : '');
				$collectData['collect']['roles'] = $this->mod_account->get_dashboard_roles();
				$collectData['collect']['address-province'] = $this->mod_account->get_province(360);
				
				$this->load->view('dashboard/dashboard.php', $collectData);
			}
		} else {
			header("Location: " . base_url("{$this->imzers->base_path}/account/login"));
			exit;
		}
	}
	
	function addaction() {
		$collectData = array();
		$collectData['page'] = 'user-add';
		$collectData['title'] = 'Add New User';
		$collectData['base_path'] = $this->imzers->base_path;
		$collectData['collect'] = array();
		//================================
		if ($this->authentication->localdata) {
			$collectData['collect']['ggdata'] = $this->authentication->userdata;
			$collectData['collect']['userdata'] = $this->authentication->localdata;
			$collectData['collect']['match'] = $this->authentication->get_altorouter_match();
			if (!isset($collectData['collect']['userdata']['account_role'])) {
				$this->error = true;
				$this->error_msg[] = "System not detect account-role of logged-in user";
			}
			if (!$this->error) {
				if (!$this->is_admin) {
					$this->error = true;
					$this->accessDenied($collectData);
				}
			}
		} else {
			header("Location: " . base_url("{$this->imzers->base_path}/account/login"));
			exit;
		}
		//----------------------------------------------------------------------------------------------------------
		if ($this->is_admin) {
			$collectData['collect']['roles'] = $this->mod_account->get_dashboard_roles();
			$input_params = array();
			$user_params = array();
			$user_params['body'] = array(
				'user_email' => (isset($this->imzcustom->php_input_request['body']['user_email']) ? $this->imzcustom->php_input_request['body']['user_email'] : ''),
				'user_password' => (isset($this->imzcustom->php_input_request['body']['user_password']) ? $this->imzcustom->php_input_request['body']['user_password'] : ''),
				'user_password_confirm' => (isset($this->imzcustom->php_input_request['body']['user_password_confirm']) ? $this->imzcustom->php_input_request['body']['user_password_confirm'] : ''),
				'user_role' => (isset($this->imzcustom->php_input_request['body']['user_role']) ? $this->imzcustom->php_input_request['body']['user_role'] : ''),
				'account_active' => (isset($this->imzcustom->php_input_request['body']['account_active']) ? $this->imzcustom->php_input_request['body']['account_active'] : ''),
			);
			$user_params['body']['user_email'] = (is_string($user_params['body']['user_email']) ? $user_params['body']['user_email'] : '');
			$user_params['body']['user_password'] = (is_string($user_params['body']['user_password']) ? $user_params['body']['user_password'] : '');
			$user_params['body']['user_fullname'] = (isset($this->imzcustom->php_input_request['body']['user_fullname']) ? $this->imzcustom->php_input_request['body']['user_fullname'] : '');
			$user_params['body']['user_nickname'] = (isset($this->imzcustom->php_input_request['body']['user_nickname']) ? $this->imzcustom->php_input_request['body']['user_nickname'] : '');
			$user_params['body']['user_address'] = (isset($this->imzcustom->php_input_request['body']['user_address']) ? $this->imzcustom->php_input_request['body']['user_address'] : '');
			$user_params['body']['user_address_province'] = (isset($this->imzcustom->php_input_request['body']['user_address_province']) ? $this->imzcustom->php_input_request['body']['user_address_province'] : '');
			$user_params['body']['user_address_city'] = (isset($this->imzcustom->php_input_request['body']['user_address_city']) ? $this->imzcustom->php_input_request['body']['user_address_city'] : '');
			$user_params['body']['user_address_district'] = (isset($this->imzcustom->php_input_request['body']['user_address_district']) ? $this->imzcustom->php_input_request['body']['user_address_district'] : '');
			$user_params['body']['user_address_area'] = (isset($this->imzcustom->php_input_request['body']['user_address_area']) ? $this->imzcustom->php_input_request['body']['user_address_area'] : '');
			$user_params['body']['account_activation_ending'] = (isset($this->imzcustom->php_input_request['body']['account_activation_ending']) ? $this->imzcustom->php_input_request['body']['account_activation_ending'] : '');
			$user_params['body']['account_remark'] = (isset($this->imzcustom->php_input_request['body']['account_remark']) ? $this->imzcustom->php_input_request['body']['account_remark'] : '');
			//----
			$form_validation = TRUE;
			//----
			if ((strlen($user_params['body']['user_email']) === 0) || (strlen($user_params['body']['user_password']) === 0) || (strlen($user_params['body']['user_fullname']) === 0)) {
				$form_validation = FALSE;
				$this->error_msg[] = "Input cannot be empty.";
			}
			if ($form_validation) {
				if (!is_string($user_params['body']['user_fullname']) || !is_string($user_params['body']['user_password'])) {
					$form_validation = FALSE;
					$this->error_msg[] = "You should use string full name.";
				}
			}
			if ($form_validation) {
				if (!filter_var($user_params['body']['user_email'], FILTER_VALIDATE_EMAIL)) {
					$form_validation = FALSE;
					$this->error_msg[] = "You must use valid email format.";
				}
			}
			if ($form_validation) {
				if ($user_params['body']['user_password'] !== $user_params['body']['user_password_confirm']) {
					$form_validation = FALSE;
					$this->error_msg[] = "Password and Confirm password should be same.";
				}
			}
			if ($form_validation) {
				if (!is_numeric($user_params['body']['user_role'])) {
					$form_validation = FALSE;
					$this->error_msg[] = "Role should be numeric value.";
				}
			}
			if ($form_validation) {
				if (count($collectData['collect']['roles']) > 0) {
					$available_roles = array();
					foreach ($collectData['collect']['roles'] as $keval) {
						$available_roles[] = (isset($keval->role_seq) ? $keval->role_seq : 1);
					}
					if (!in_array($user_params['body']['user_role'], $available_roles)) {
						$form_validation = FALSE;
						$this->error_msg[] = "Role should be in seq that already available on database.";
					}
				}
			}
			if ($form_validation) {
				if (strlen($user_params['body']['account_activation_ending']) > 0) {
					if (!$this->authentication->create_dateobject("Asia/Bangkok", 'Y-m-d', $user_params['body']['account_activation_ending'])) {
						$form_validation = FALSE;
						$this->error_msg[] = "Date activation end should be a valid date format.";
					}
				}
			}
			if ($form_validation) {
				if (!in_array($user_params['body']['account_active'], array('Y', 'N'))) {
					$user_params['body']['account_active'] = 'N';
				}
			}
			//======================
			if (!$form_validation) {
				$this->session->set_flashdata('error', TRUE);
				$error_string = "";
				if (count($this->error_msg) > 0) {
					foreach ($this->error_msg as $errorVal) {
						$error_string .= "- {$errorVal}";
					}
				}
				$this->session->set_flashdata('action_message', $error_string);
				header('Location: ' . base_url("{$this->imzers->base_path}/users/add"));
				exit;
			} else {
				$input_params = array(
					'user_fullname'						=> $user_params['body']['user_fullname'],
					'user_nickname'						=> $user_params['body']['user_nickname'],
					'user_username'						=> $user_params['body']['user_email'], // required unique
					'user_email'						=> $user_params['body']['user_email'], // required unique
					'user_password'						=> $user_params['body']['user_password'],
					'user_password_confirm'				=> $user_params['body']['user_password_confirm'],
					'user_phonenumber'					=> '',
					'user_phonemobile'					=> '',
					'user_address'						=> $user_params['body']['user_address'],
					'user_role'							=> $user_params['body']['user_role'],
					'subscription_starting'				=> $this->DateObject->format('Y-m-d H:i:s'),
					'subscription_expiring'				=> $this->DateObject->format('Y-m-d H:i:s'),
					'account_activation_ending'			=> $user_params['body']['account_activation_ending'],
					'account_active'					=> $user_params['body']['account_active'],
					'account_remark'					=> $user_params['body']['account_remark'],
				);
				$input_params['user_email'] = filter_var($input_params['user_email'], FILTER_VALIDATE_EMAIL);
				$input_params['user_phonenumber'] = sprintf('%s', $input_params['user_phonenumber']);
				$input_params['user_phonemobile'] = sprintf('%s', $input_params['user_phonemobile']);
				$input_params['user_role'] = sprintf('%d', $input_params['user_role']);
				$input_params['user_fullname'] = sprintf('%s', $input_params['user_fullname']);
				$input_params['user_nickname'] = sprintf('%s', $input_params['user_nickname']);
				
				$query_params = array(
					'account_username'				=> ((strlen($input_params['user_username']) > 0) ? strtolower($input_params['user_username']) : ''),
					'account_email'					=> ((strlen($input_params['user_email']) > 0) ? strtolower($input_params['user_email']) : ''),
					'account_hash'					=> '', // Later
					'account_password'				=> '', // Later
					'account_inserting_datetime'	=> $this->DateObject->format('Y-m-d H:i:s'),
					'account_inserting_remark'		=> ((strlen($input_params['account_remark']) > 0) ? $input_params['account_remark'] : ''),
					'account_activation_code'		=> '', // Later,
					'account_activation_starting'	=> $this->DateObject->format('Y-m-d H:i:s'),
					'account_activation_ending'		=> '', // Later
					'account_activation_datetime'	=> NULL,
					'account_activation_status'		=> 'N', // N as default
					'account_activation_by'			=> '', // Should be user itself or by administrator (Set Later)
					'account_active'				=> ((strlen($input_params['account_active']) > 0) ? strtoupper($input_params['account_active']) : ''),
					'account_role'					=> ((strlen($input_params['user_role']) > 0) ? $input_params['user_role'] : 0),
					'account_nickname'				=> ((strlen($input_params['user_nickname']) > 0) ? $input_params['user_nickname'] : ''),
					'account_fullname'				=> ((strlen($input_params['user_fullname']) > 0) ? ucwords(strtolower($input_params['user_fullname'])) : ''),
					'account_address'				=> ((strlen($input_params['user_address']) > 0) ? $input_params['user_address'] : ''),
					'account_phonenumber'			=> ((strlen($input_params['user_phonenumber']) > 0) ? $input_params['user_phonenumber'] : ''),
					'account_phonemobile'			=> ((strlen($input_params['user_phonemobile']) > 0) ? $input_params['user_phonemobile'] : ''),
					'account_delete_status'			=> 0, // 0 as default(not deleted)
					'account_delete_datetime'		=> NULL,
					'account_delete_by'				=> '', // Should be user itself or system or by administrator (Set Later)
					'account_edited_datetime'		=> NULL,
					'account_edited_by'				=> NULL,
					'subscription_starting'			=> ((strlen($input_params['subscription_starting']) > 0) ? $input_params['subscription_starting'] : ''),
					'subscription_expiring'			=> ((strlen($input_params['subscription_expiring']) > 0) ? $input_params['subscription_expiring'] : ''),
				);
				//-------------------------------------
				if (!$this->error) {
					if ($input_params['user_password'] !== $input_params['user_password_confirm']) {
						$this->error = true;
						$this->error_msg[] = "Password and confirm password not match";
					}
				}
				if (!$this->error) {
					try {
						$collectData['subscription']['subscription_starting'] = new DateTime($query_params['subscription_starting']);
					} catch (Exception $ex) {
						$this->error = true;
						$this->error_msg[] = "collect subscription_starting : " . $ex->getMessage();
					}
				}
				if (!$this->error) {
					$query_params['subscription_starting'] = $collectData['subscription']['subscription_starting']->format('Y-m-d H:i:s');
					try {
						$collectData['subscription']['subscription_expiring'] = new DateTime($query_params['subscription_expiring']);
					} catch (Exception $ex) {
						$this->error = true;
						$this->error_msg[] = "query subscription_starting: " . $ex->getMessage();
					}
				}
				if (!$this->error) {
					$query_params['subscription_expiring'] = $collectData['subscription']['subscription_expiring']->format('Y-m-d H:i:s');
					try {
						$input_params['unique_datetime'] = $this->authentication->create_unique_datetime("Asia/Bangkok");
					} catch (Exception $ex) {
						$this->error = true;
						$this->error_msg[] = "Cannot create new unique datetime: {$ex->getMessage()}";
					}
				}
				if (!$this->error) {
					$query_params['account_hash'] = $this->rc4crypt->bEncryptRC4($input_params['unique_datetime']);
					try {
						$input_params['account_password'] = ("{$this->rc4crypt->bDecryptRC4($query_params['account_hash'])}|{$input_params['user_password']}");
					} catch (Exception $ex) {
						$this->error = true;
						$this->error_msg[] = "query account_password: " . $ex->getMessage();
					}
				}
				if (!$this->error) {
					$query_params['account_password'] = sha1($input_params['account_password']);
					$query_params['account_activation_code'] = md5($query_params['account_hash']);
					try {
						$collectData['activation']['account_activation_ending'] = new DateTime($input_params['account_activation_ending']);
					} catch (Exception $ex) {
						$this->error = true;
						$this->error_msg[] = "Cannot create object account-activation-ending: {$ex->getMessage()}";
					}
				}
				if (!$this->error) {
					if (strtoupper($query_params['account_active']) === strtoupper('Y')) {
						$query_params['account_activation_status'] = 'Y';
					}
				}
				if (!$this->error) {
					$query_params['account_activation_ending'] = $collectData['activation']['account_activation_ending']->format('Y-m-d H:i:s');
					if (strtoupper($query_params['account_activation_status']) === strtoupper('Y')) {
						$query_params['account_activation_datetime'] = $this->DateObject->format('Y-m-d H:i:s');
						$query_params['account_activation_by'] = (isset($collectData['collect']['userdata']['account_username']) ? $collectData['collect']['userdata']['account_username'] : 'system');
					}
					try {
						$collectData['local_users'] = $this->mod_account->get_local_user_by($query_params['account_username'], 'username');
					} catch (Exception $ex) {
						$this->error = true;
						$this->error_msg[] = "Cannot check username is exists from model from add-account: {$ex->getMessage()}";
					}
				}
				if (!$this->error) {
					if (count($collectData['local_users']) > 0) {
						$this->error = true;
						$this->error_msg[] = "Username already taken.";
					}
				}
				if (!$this->error) {
					try {
						$collectData['local_users'] = $this->mod_account->get_local_user_by($query_params['account_email'], 'email');
					} catch (Exception $ex) {
						$this->error = true;
						$this->error_msg[] = "Cannot check email is exists from model.";
					}
				}
				if (!$this->error) {
					if (count($collectData['local_users']) > 0) {
						$this->error = true;
						$this->error_msg[] = "Email already taken.";
					}
				}
				// -- Add User
				if (!$this->error) {
					try {
						$collectData['new_account_seq'] = $this->mod_account->add_user($query_params);
					} catch (Exception $ex) {
						$this->error = true;
						$this->error_msg[] = "Cannot insert new user to local-account: {$ex->getMessage()}";
					}
				}
				//-- Add User Properties
				if (!$this->error) {
					$collectData['local_properties_params'] = array();
					$collectData['local_data_properties'] = array();
					$collectData['local_data_properties']['user_address_province'] = (isset($this->imzcustom->php_input_request['body']['user_address_province']) ? $this->imzcustom->php_input_request['body']['user_address_province'] : '');
					$collectData['local_data_properties']['user_address_city'] = (isset($this->imzcustom->php_input_request['body']['user_address_city']) ? $this->imzcustom->php_input_request['body']['user_address_city'] : '');
					$collectData['local_data_properties']['user_address_district'] = (isset($this->imzcustom->php_input_request['body']['user_address_district']) ? $this->imzcustom->php_input_request['body']['user_address_district'] : '');
					$collectData['local_data_properties']['user_address_area'] = (isset($this->imzcustom->php_input_request['body']['user_address_area']) ? $this->imzcustom->php_input_request['body']['user_address_area'] : '');
					$collectData['local_data_properties']['user_address'] = (isset($this->imzcustom->php_input_request['body']['user_address']) ? $this->imzcustom->php_input_request['body']['user_address'] : '');
					if ((int)$collectData['new_account_seq'] > 0) {
						foreach ($collectData['local_data_properties'] as $key => $val) {
							if (strlen($val) > 0) {
								$collectData['local_properties_params'] = array(
									'properties_key'				=> strtolower($key),
									'properties_value'				=> $this->imzers->safe_text_post($val, 512),
								);
								$new_propertie_seq = $this->mod_account->insert_local_user_properties($collectData['new_account_seq'], $collectData['local_properties_params']);
							}
						}
					}
				}
				//===============
				// Done Adding
				if (!$this->error) {
					$this->session->set_flashdata('error', FALSE);
					$this->session->set_flashdata('action_message', 'Success add new user.');
					header('Location: ' . base_url("{$collectData['base_path']}/users/lists"));
					exit;
				} else {
					$this->session->set_flashdata('error', TRUE);
					$error_to_show = "";
					foreach ($this->error_msg as $keval) {
						$error_to_show .= $keval;
					}
					$this->session->set_flashdata('action_message', $error_to_show);
				}
			}
			header('Location: ' . base_url("{$collectData['base_path']}/users/add"));
		}
		exit;
	}
	function edit($seq = 0) {
		$collectData = array();
		$collectData['page'] = 'user-edit';
		$collectData['title'] = 'Edit User: ';
		$collectData['base_path'] = $this->imzers->base_path;
		$collectData['collect'] = array();
		//================================
		if ($this->authentication->localdata) {
			$collectData['collect']['ggdata'] = $this->authentication->userdata;
			$collectData['collect']['userdata'] = $this->authentication->localdata;
			$collectData['collect']['match'] = $this->authentication->get_altorouter_match();
			if (!isset($collectData['collect']['userdata']['account_role'])) {
				$this->error = true;
				$this->error_msg[] = "System not detect account-role of logged-in user";
			}
			if (!$this->error) {
				if (!$this->is_admin) {
					$this->error = true;
					$this->accessDenied($collectData);
				}
			}
		} else {
			header("Location: " . base_url("{$this->imzers->base_path}/account/login"));
			exit;
		}
		//----------------------------------------------------------------------------------------------------------
		$collectData['seq'] = (is_numeric($seq) ? (int)$seq : 0);
		if ($collectData['seq'] === 0) {
			header("Location: " . base_url("{$this->imzers->base_path}/users/lists"));
			exit;
		}
		if ($this->is_admin) {
			if (!$this->error) {
				$collectData['collect']['roles'] = $this->mod_account->get_dashboard_roles();
				$collectData['collect']['address-province'] = $this->mod_account->get_province(360);
				$collectData['collect']['localuser'] = $this->mod_account->get_local_user_by($collectData['seq'], 'seq');
				if (count((array)$collectData['collect']['localuser']) === 0) {
					$this->error = true;
					$this->error_msg[] = "localuser not found";
				}
			}
			if (isset($collectData['collect']['localuser']->account_role)) {
				if ((int)$collectData['collect']['localuser']->account_role === base_config('super_admin_role')) {
					$this->error = true;
					$this->error_msg[] = "Standard admin cannot editing super-admin account";
				}
			}
			if (!$this->error) {
				$collectData['collect']['local-properties'] = $this->mod_account->get_local_user_properties($collectData['seq']);
				$collectData['collect']['user-properties'] = array();
				if (count($collectData['collect']['local-properties']) > 0) {
					foreach ($collectData['collect']['local-properties'] as $keval) {
						$collectData['collect']['user-properties'][$keval->properties_key] = $keval->properties_value;
					}
				}
				$collectData['address_params'] = array(
					'country_code'		=> '360',
					'province_code'		=> (isset($collectData['collect']['user-properties']['user_address_province']) ? $collectData['collect']['user-properties']['user_address_province'] : ''),
					'city_code'			=> (isset($collectData['collect']['user-properties']['user_address_city']) ? $collectData['collect']['user-properties']['user_address_city'] : ''),
					'district_code'		=> (isset($collectData['collect']['user-properties']['user_address_district']) ? $collectData['collect']['user-properties']['user_address_district'] : ''),
					'area_code'			=> (isset($collectData['collect']['user-properties']['user_address_area']) ? $collectData['collect']['user-properties']['user_address_area'] : ''),
				);
				$collectData['collect']['address-city'] = ((strlen($collectData['address_params']['province_code']) > 0) ? $this->mod_account->get_city($collectData['address_params']) : array());
				$collectData['collect']['address-district'] = ((strlen($collectData['address_params']['city_code']) > 0) ? $this->mod_account->get_district($collectData['address_params']) : array());
				$collectData['collect']['address-area'] = ((strlen($collectData['address_params']['district_code']) > 0) ? $this->mod_account->get_area($collectData['address_params']) : array());
			}
			//-----
			if (!$this->error) {
				if (isset($collectData['collect']['localuser']->account_email)) {
					$collectData['title'] .= $collectData['collect']['localuser']->account_email;
				}
				
				$this->load->view('dashboard/dashboard.php', $collectData);
			} else {
				$this->session->set_flashdata('error', TRUE);
				$error_to_show = "";
				foreach ($this->error_msg as $keval) {
					$error_to_show .= $keval;
				}
				$this->session->set_flashdata('action_message', $error_to_show);
				header("Location: " . base_url("{$this->imzers->base_path}/users/lists"));
				exit;
			}
		}
	}
	function editaction($seq = 0) {
		$collectData = array();
		$collectData['page'] = 'user-edit';
		$collectData['title'] = 'Edit User: ';
		$collectData['base_path'] = $this->imzers->base_path;
		$collectData['collect'] = array();
		//================================
		if ($this->authentication->localdata) {
			$collectData['collect']['ggdata'] = $this->authentication->userdata;
			$collectData['collect']['userdata'] = $this->authentication->localdata;
			$collectData['collect']['match'] = $this->authentication->get_altorouter_match();
			if (!isset($collectData['collect']['userdata']['account_role'])) {
				$this->error = true;
				$this->error_msg[] = "System not detect account-role of logged-in user";
			}
			if (!$this->error) {
				if (!$this->is_admin) {
					$this->error = true;
					$this->accessDenied($collectData);
				}
			}
		} else {
			header("Location: " . base_url("{$this->imzers->base_path}/account/login"));
			exit;
		}
		//----------------------------------------------------------------------------------------------------------
		$collectData['seq'] = (is_numeric($seq) ? (int)$seq : 0);
		if ($collectData['seq'] === 0) {
			header("Location: " . base_url("{$this->imzers->base_path}/users/lists"));
			exit;
		}
		if ($this->is_admin) {
			if (!$this->error) {
				$collectData['collect']['roles'] = $this->mod_account->get_dashboard_roles();
				$collectData['collect']['address-province'] = $this->mod_account->get_province(360);
				$collectData['collect']['localuser'] = $this->mod_account->get_local_user_by($collectData['seq'], 'seq');
				if (count((array)$collectData['collect']['localuser']) === 0) {
					$this->error = true;
					$this->error_msg[] = "localuser not found";
				}
			}
			$user_params = array();
			$user_params['body'] = array(
				'user_username' => (isset($this->imzcustom->php_input_request['body']['user_username']) ? $this->imzcustom->php_input_request['body']['user_username'] : ''),
				'user_password' => (isset($this->imzcustom->php_input_request['body']['user_password']) ? $this->imzcustom->php_input_request['body']['user_password'] : ''),
				'user_password_confirm' => (isset($this->imzcustom->php_input_request['body']['user_password_confirm']) ? $this->imzcustom->php_input_request['body']['user_password_confirm'] : ''),
				'user_role' => (isset($this->imzcustom->php_input_request['body']['user_role']) ? $this->imzcustom->php_input_request['body']['user_role'] : ''),
				'account_active' => (isset($this->imzcustom->php_input_request['body']['account_active']) ? $this->imzcustom->php_input_request['body']['account_active'] : ''),
			);
			$user_params['body']['user_fullname'] = (isset($this->imzcustom->php_input_request['body']['user_fullname']) ? $this->imzcustom->php_input_request['body']['user_fullname'] : '');
			$user_params['body']['user_nickname'] = (isset($this->imzcustom->php_input_request['body']['user_nickname']) ? $this->imzcustom->php_input_request['body']['user_nickname'] : '');
			$user_params['body']['user_address'] = (isset($this->imzcustom->php_input_request['body']['user_address']) ? $this->imzcustom->php_input_request['body']['user_address'] : '');
			$user_params['body']['user_address_province'] = (isset($this->imzcustom->php_input_request['body']['user_address_province']) ? $this->imzcustom->php_input_request['body']['user_address_province'] : '');
			$user_params['body']['user_address_city'] = (isset($this->imzcustom->php_input_request['body']['user_address_city']) ? $this->imzcustom->php_input_request['body']['user_address_city'] : '');
			$user_params['body']['user_address_district'] = (isset($this->imzcustom->php_input_request['body']['user_address_district']) ? $this->imzcustom->php_input_request['body']['user_address_district'] : '');
			$user_params['body']['user_address_area'] = (isset($this->imzcustom->php_input_request['body']['user_address_area']) ? $this->imzcustom->php_input_request['body']['user_address_area'] : '');
			$user_params['body']['account_activation_ending'] = (isset($this->imzcustom->php_input_request['body']['account_activation_ending']) ? $this->imzcustom->php_input_request['body']['account_activation_ending'] : '');
			$user_params['body']['account_remark'] = (isset($this->imzcustom->php_input_request['body']['account_remark']) ? $this->imzcustom->php_input_request['body']['account_remark'] : '');
			//====
			$user_params['body']['user_phonenumber'] = (isset($this->imzcustom->php_input_request['body']['user_phonenumber']) ? $this->imzcustom->php_input_request['body']['user_phonenumber'] : '');
			$user_params['body']['user_phonemobile'] = (isset($this->imzcustom->php_input_request['body']['user_phonemobile']) ? $this->imzcustom->php_input_request['body']['user_phonemobile'] : '');
			$user_params['body']['subscription_expiring'] = (isset($this->imzcustom->php_input_request['body']['subscription_expiring']) ? $this->imzcustom->php_input_request['body']['subscription_expiring'] : '');
			//--
			$user_params['body']['account_delete_status'] = (isset($this->imzcustom->php_input_request['body']['account_delete_status']) ? $this->imzcustom->php_input_request['body']['account_delete_status'] : '');
			//----
			$form_validation = TRUE;
			//----
			if (strlen($user_params['body']['user_fullname']) === 0) {
				$form_validation = FALSE;
				$error_msg[] = "Input Fullname cannot be empty.";
			}
			if ($form_validation) {
				if (!is_string($user_params['body']['user_fullname'])) {
					$form_validation = FALSE;
					$this->error_msg[] = "You should use string full name.";
				}
			}
			if ($form_validation) {
				if (!is_numeric($user_params['body']['user_role'])) {
					$form_validation = FALSE;
					$this->error_msg[] = "Role should be numeric value.";
				}
			}
			if ($form_validation) {
				if (count($collectData['collect']['roles']) > 0) {
					$available_roles = array();
					foreach ($collectData['collect']['roles'] as $keval) {
						$available_roles[] = (isset($keval->role_seq) ? $keval->role_seq : 1);
					}
					if (!in_array($user_params['body']['user_role'], $available_roles)) {
						$form_validation = FALSE;
						$this->error_msg[] = "Role should be in seq that already available on database.";
					}
				}
			}
			if ($form_validation) {
				if (strlen($user_params['body']['subscription_expiring']) > 0) {
					if (!$this->authentication->create_dateobject("Asia/Bangkok", 'Y-m-d', $user_params['body']['subscription_expiring'])) {
						$form_validation = FALSE;
						$this->error_msg[] = "Date account expiring should be a valid date format.";
					}
				}
			}
			if ($form_validation) {
				if (!in_array($user_params['body']['account_active'], array('Y', 'N'))) {
					$user_params['body']['account_active'] = 'N';
				}
			}
			if ($form_validation) {
				if ($user_params['body']['user_password'] !== $user_params['body']['user_password_confirm']) {
					$form_validation = FALSE;
					$this->error_msg[] = "Password and Confirm password should be same.";
				}
			}
			//======================
			if (!$form_validation) {
				$this->session->set_flashdata('error', TRUE);
				$error_string = "";
				if (count($this->error_msg) > 0) {
					foreach ($this->error_msg as $errorVal) {
						$error_string .= "- {$errorVal}";
					}
				}
				$this->session->set_flashdata('action_message', $error_string);
				header('Location: ' . base_url("{$this->imzers->base_path}/users/edit/{$collectData['seq']}"));
				exit;
			} else {
				if (!$this->error) {
					if (isset($collectData['collect']['localuser']->account_role)) {
						if ((int)$collectData['collect']['localuser']->account_role === base_config('super_admin_role')) {
							$this->error = true;
							$this->error_msg[] = "Standard admin cannot editing super-admin account";
						}
					}
				}
				$input_params = array(
					'user_fullname'						=> $user_params['body']['user_fullname'],
					'user_nickname'						=> $user_params['body']['user_nickname'],
					'user_username'						=> $user_params['body']['user_username'],
					'user_password'						=> (is_string($user_params['body']['user_password']) || is_numeric($user_params['body']['user_password'])) ? $user_params['body']['user_password'] : '',
					'user_password_confirm'				=> (is_string($user_params['body']['user_password_confirm']) || is_numeric($user_params['body']['user_password_confirm'])) ? $user_params['body']['user_password_confirm'] : '',
					'user_phonenumber'					=> (is_string($user_params['body']['user_phonenumber']) || is_numeric($user_params['body']['user_phonenumber'])) ? sprintf("%s", $user_params['body']['user_phonenumber']) : '',
					'user_phonemobile'					=> (is_string($user_params['body']['user_phonemobile']) || is_numeric($user_params['body']['user_phonemobile'])) ? sprintf("%s", $user_params['body']['user_phonemobile']) : '',
					'user_address'						=> $user_params['body']['user_address'],
					'user_role'							=> $user_params['body']['user_role'],
					'subscription_expiring'				=> $user_params['body']['subscription_expiring'],
					'account_delete_status'				=> (is_numeric($user_params['body']['account_delete_status']) ? (int)$user_params['body']['account_delete_status'] : 0),
					'account_active'					=> (is_string($user_params['body']['account_active']) ? $user_params['body']['account_active'] : ''),
					'account_remark'					=> $user_params['body']['account_remark'],
				);
				$input_params['user_phonenumber'] = sprintf('%s', $input_params['user_phonenumber']);
				$input_params['user_phonemobile'] = sprintf('%s', $input_params['user_phonemobile']);
				$input_params['user_role'] = sprintf('%d', $input_params['user_role']);
				$input_params['user_fullname'] = sprintf('%s', $input_params['user_fullname']);
				$input_params['user_nickname'] = sprintf('%s', $input_params['user_nickname']);
				$input_params['user_username'] = sprintf('%s', $input_params['user_username']);
				//$input_params['user_email'] = filter_var($input_params['user_email'], FILTER_VALIDATE_EMAIL);
				$query_params = array(
					'account_username'				=> ((strlen($input_params['user_username']) > 0) ? strtolower($input_params['user_username']) : ''),
					'account_hash'					=> '', // Later
					'account_password'				=> '', // Later
					'account_inserting_datetime'	=> $this->DateObject->format('Y-m-d H:i:s'),
					'account_inserting_remark'		=> ((strlen($input_params['account_remark']) > 0) ? $input_params['account_remark'] : ''),
					'account_activation_code'		=> '', // Later,
					'account_activation_starting'	=> $this->DateObject->format('Y-m-d H:i:s'),
					//'account_activation_ending'		=> '', // Later
					'account_activation_datetime'	=> $this->DateObject->format('Y-m-d H:i:s'),
					'account_activation_status'		=> 'Y', // Y for editing
					'account_activation_by'			=> '', // Should be user itself or by administrator (Set Later)
					'account_active'				=> ((strlen($input_params['account_active']) > 0) ? strtoupper($input_params['account_active']) : ''),
					'account_role'					=> ((strlen($input_params['user_role']) > 0) ? $input_params['user_role'] : 0),
					'account_nickname'				=> ((strlen($input_params['user_nickname']) > 0) ? $input_params['user_nickname'] : ''),
					'account_fullname'				=> ((strlen($input_params['user_fullname']) > 0) ? ucwords(strtolower($input_params['user_fullname'])) : ''),
					'account_address'				=> ((strlen($input_params['user_address']) > 0) ? $input_params['user_address'] : ''),
					'account_phonenumber'			=> ((strlen($input_params['user_phonenumber']) > 0) ? sprintf('%s', $input_params['user_phonenumber']) : ''),
					'account_phonemobile'			=> ((strlen($input_params['user_phonemobile']) > 0) ? sprintf("%s", $input_params['user_phonemobile']) : ''),
					'account_delete_status'			=> (is_numeric($input_params['account_delete_status']) ? $input_params['account_delete_status'] : 0),
					'account_delete_datetime'		=> NULL,
					'account_edited_datetime'		=> $this->DateObject->format('Y-m-d H:i:s'),
					'account_edited_by'				=> '', // Should be user itself or system or by administrator (Set Later)
					//'subscription_starting'			=> ((strlen($input_params['subscription_starting']) > 0) ? $input_params['subscription_starting'] : ''),
					'subscription_expiring'			=> ((strlen($input_params['subscription_expiring']) > 0) ? $input_params['subscription_expiring'] : ''),
				);
				//-------------------------------------
				if (!$this->error) {
					$collectData['upload_img'] = base_config('upload_img');
					if (!isset($collectData['upload_img']['local']) || !isset($collectData['upload_img']['cdn'])) {
						$this->error = true;
						$this->error_msg[] = "Config dont have upload image preference or cdn preference";
					} else {
						//============================
						// Load Library Upload and CDN
						
						
						//============================
					}
				}
				if (!$this->error) {
					if (isset($_FILES['user_picture']['tmp_name'])) {
						$collectData['upload_data'] = array();
						if ($_FILES['user_picture']['tmp_name'] != null) {
							$collectData['VerotUpload'] = new VerotUpload($_FILES['user_picture']);
							if ($collectData['VerotUpload']->uploaded) {
								$collectData['VerotUpload']->file_new_name_body = $collectData['upload_img']['local']['file_name'];
								$collectData['VerotUpload']->file_safe_name = true;
								$collectData['VerotUpload']->file_overwrite = false;
								$collectData['VerotUpload']->file_auto_rename = true;
								$collectData['VerotUpload']->file_max_size = $collectData['upload_img']['local']['max_size'];
								$collectData['VerotUpload']->mime_check = true;
								$collectData['VerotUpload']->allowed = $collectData['upload_img']['local']['allowed_mimes'];
								$collectData['VerotUpload']->forbidden = $collectData['upload_img']['local']['forbidden_mimes'];
								$collectData['VerotUpload']->image_resize = true;
								$collectData['VerotUpload']->image_x = 215;
								$collectData['VerotUpload']->image_y = 215;
								$collectData['VerotUpload']->image_ratio_y = true;
								// Validate Upload
								$collectData['upload_data']['source'] = array(
									'src'		=> $collectData['VerotUpload']->file_src_name,
									'name'		=> $collectData['VerotUpload']->file_src_name_body,
									'ext'		=> $collectData['VerotUpload']->file_src_name_ext,
									'path'		=> $collectData['VerotUpload']->file_src_pathname,
									'mime'		=> $collectData['VerotUpload']->file_src_mime,
									'size'		=> $collectData['VerotUpload']->file_src_size,
									'is_image'	=> $collectData['VerotUpload']->file_is_image,
									'error'		=> $collectData['VerotUpload']->file_src_error,
								);
								if ($collectData['upload_data']['source']['is_image'] !== TRUE) {
									$this->error = true;
									$this->error_msg[] = "Source should be an image file.";
								}
							}
						}
					}
				}
				if (!$this->error) {
					if (isset($_FILES['user_picture']['tmp_name'])) {
						if ($_FILES['user_picture']['tmp_name'] != null) {
							// Process Upload
							if ($collectData['VerotUpload']->uploaded) {
								$collectData['VerotUpload']->process($collectData['upload_img']['local']['upload_path'] . DIRECTORY_SEPARATOR);
								if ($collectData['VerotUpload']->processed) {
									$collectData['upload_data']['result'] = array(
										'path'			=> $collectData['VerotUpload']->file_dst_pathname,
										'name'			=> $collectData['VerotUpload']->file_dst_name_body,
										'ext'			=> $collectData['VerotUpload']->file_dst_name_ext,
										'name_ext'		=> $collectData['VerotUpload']->file_dst_name,
										'image_x'		=> $collectData['VerotUpload']->image_dst_x,
										'image_y'		=> $collectData['VerotUpload']->image_dst_y,
										'image_type'	=> $collectData['VerotUpload']->image_dst_type,
										'image_url'		=> base_url('media/images/' . $collectData['VerotUpload']->file_dst_name),
									);
									$query_params['account_picture'] = $collectData['upload_data']['result']['image_url'];
									// Cleaning image
									$collectData['VerotUpload']->clean();
								} else {
									$this->error = true;
									$this->error_msg[] = "Cannot process upload and resize image : " . $collectData['VerotUpload']->error;
								}
							}
						}
					}
				}
				//-------------------------------------
				if (!$this->error) {
					if ($input_params['user_password'] !== $input_params['user_password_confirm']) {
						$this->error = true;
						$this->error_msg[] = "Password and confirm password not match";
					}
				}
				if (!$this->error) {
					try {
						$input_params['unique_datetime'] = $this->mod_account->create_unique_datetime("Asia/Bangkok");
					} catch (Exception $ex) {
						$this->error = true;
						$this->error_msg[] = "Cannot create new unique datetime.";
					}
				}
				if (!$this->error) {
					if (strlen($input_params['user_password']) > 0) {
						$query_params['account_hash'] = $this->rc4crypt->bEncryptRC4($input_params['unique_datetime']);
						$query_params['account_activation_code'] = md5($query_params['account_hash']);
						try {
							$query_params['account_password'] = sha1("{$input_params['unique_datetime']}|{$input_params['user_password']}");
						} catch (Exception $ex) {
							$this->error = true;
							$this->error_msg[] = "Cannot create sha1 of encrypted password: " . $ex->getMessage();
						}
					} else {
						if (isset($query_params['account_hash'])) {
							unset($query_params['account_hash']);
						}
						if (isset($query_params['account_activation_code'])) {
							unset($query_params['account_activation_code']);
						}
						if (isset($query_params['account_password'])) {
							unset($query_params['account_password']);
						}
					}
				}
				
				if (!$this->error) {
					try {
						$collectData['subscription']['subscription_expiring'] = new DateTime($query_params['subscription_expiring']);
					} catch (Exception $ex) {
						$this->error = true;
						$this->error_msg[] = "Subscription expiring date format not valid: {$ex->getMessage()}";
					}
				}
				if (!$this->error) {
					$query_params['subscription_expiring'] = $collectData['subscription']['subscription_expiring']->format('Y-m-d H:i:s');
					if (strtoupper($query_params['account_active']) === strtoupper('Y')) {
						$query_params['account_activation_status'] = 'Y';
					}
					if (strtoupper($query_params['account_activation_status']) === strtoupper('Y')) {
						$query_params['account_activation_datetime'] = $this->DateObject->format('Y-m-d H:i:s');
						$query_params['account_activation_by'] = (isset($this->authentication->localdata['account_username']) ? $this->authentication->localdata['account_username'] : 'system');
					}
					$query_params['account_edited_by'] = (isset($this->authentication->localdata['account_username']) ? $this->authentication->localdata['account_username'] : 'system');
					try {
						$collectData['local_users'] = $this->mod_users->get_local_user_match_by($query_params['account_username'], 'username', $collectData['seq']);
					} catch (Exception $ex) {
						$this->error = true;
						$this->error_msg[] = "Cannot check username is exists from model from edit-account: {$ex->getMessage()}";
					}
				}
				if (!$this->error) {
					if (count($collectData['local_users']) > 0) {
						$this->error = true;
						$this->error_msg[] = "Username already taken.";
					}
				}
				if (!$this->error) {
					if ((int)$query_params['account_delete_status'] > 0) {
						$query_params['account_delete_datetime'] = $this->DateObject->format('Y-m-d H:i:s');
						$query_params['account_delete_by'] = (isset($this->authentication->localdata['account_username']) ? $this->authentication->localdata['account_username'] : 'system');
					} else {
						unset($query_params['account_delete_datetime']);
					}
				}
				//=== Editing User
				if (!$this->error) {
					try {
						$collectData['new_account_seq'] = $this->mod_account->edit_user($collectData['seq'], $query_params);
					} catch (Exception $ex) {
						$this->error = true;
						$this->error_msg[] = "Cannot editing user to local-account: {$ex->getMessage()}";
					}
				}
				//-- Editing User Properties
				if (!$this->error) {
					$collectData['local_properties_params'] = array();
					$collectData['local_data_properties'] = array();
					$collectData['local_data_properties']['user_address_province'] = (isset($this->imzcustom->php_input_request['body']['user_address_province']) ? $this->imzcustom->php_input_request['body']['user_address_province'] : '');
					$collectData['local_data_properties']['user_address_city'] = (isset($this->imzcustom->php_input_request['body']['user_address_city']) ? $this->imzcustom->php_input_request['body']['user_address_city'] : '');
					$collectData['local_data_properties']['user_address_district'] = (isset($this->imzcustom->php_input_request['body']['user_address_district']) ? $this->imzcustom->php_input_request['body']['user_address_district'] : '');
					$collectData['local_data_properties']['user_address_area'] = (isset($this->imzcustom->php_input_request['body']['user_address_area']) ? $this->imzcustom->php_input_request['body']['user_address_area'] : '');
					$collectData['local_data_properties']['user_address'] = (isset($this->imzcustom->php_input_request['body']['user_address']) ? $this->imzcustom->php_input_request['body']['user_address'] : '');
					if ((int)$collectData['new_account_seq'] > 0) {
						foreach ($collectData['local_data_properties'] as $key => $val) {
							if (strlen($val) > 0) {
								$collectData['local_properties_params'] = array(
									'properties_key'				=> strtolower($key),
									'properties_value'				=> $this->imzers->safe_text_post($val, 512),
								);
								$new_propertie_seq = $this->mod_account->update_local_user_properties($collectData['new_account_seq'], $collectData['local_properties_params']);
							}
						}
					}
				}
				//===============
				// Done Editing
				if (!$this->error) {
					$this->session->set_flashdata('error', FALSE);
					$this->session->set_flashdata('action_message', 'Success edit user.');
					header('Location: ' . base_url("{$collectData['base_path']}/users/edit/{$collectData['new_account_seq']}"));
					exit;
				} else {
					$this->session->set_flashdata('error', TRUE);
					$error_to_show = "";
					foreach ($this->error_msg as $keval) {
						$error_to_show .= $keval;
					}
					$this->session->set_flashdata('action_message', $error_to_show);
				}
			}
			header('Location: ' . base_url("{$collectData['base_path']}/users/edit/{$collectData['seq']}"));
		}
		
		exit;
	}
	function deleteaction() {
		$collectData = array();
		$collectData['page'] = 'user-edit';
		$collectData['title'] = 'Edit User: ';
		$collectData['base_path'] = $this->imzers->base_path;
		$collectData['collect'] = array();
		//================================
		if ($this->authentication->localdata) {
			$collectData['collect']['ggdata'] = $this->authentication->userdata;
			$collectData['collect']['userdata'] = $this->authentication->localdata;
			$collectData['collect']['match'] = $this->authentication->get_altorouter_match();
			if (!isset($collectData['collect']['userdata']['account_role'])) {
				$this->error = true;
				$this->error_msg[] = "System not detect account-role of logged-in user";
			}
			if (!$this->error) {
				if (!$this->is_admin) {
					$this->error = true;
					$this->accessDenied($collectData);
				}
			}
		} else {
			$collectData['collect']['data'] = array(
				'status'		=> false,
				'message'		=> 'You need login to access page',
			);
			echo json_encode($collectData['collect']['data']);
			exit;
		}
		
		
		//----------------------------------------------------------------------------------------------------------
		$collectData['seq'] = (isset($this->imzcustom->php_input_request['body']['user_seq']) ? $this->imzcustom->php_input_request['body']['user_seq'] : '');
		$collectData['seq'] = (is_numeric($collectData['seq']) ? (int)$collectData['seq'] : 0);
		if ($collectData['seq'] === 0) {
			$collectData['collect']['data'] = array(
				'status'		=> false,
				'message'		=> 'We need integer user-seq',
			);
			echo json_encode($collectData['collect']['data']);
			exit;
		}
		if ($this->is_admin) {
			if (!$this->error) {
				$collectData['collect']['roles'] = $this->mod_account->get_dashboard_roles();
				$collectData['collect']['address-province'] = $this->mod_account->get_province(360);
				$collectData['collect']['localuser'] = $this->mod_account->get_local_user_by($collectData['seq'], 'seq');
				if (count((array)$collectData['collect']['localuser']) === 0) {
					$this->error = true;
					$this->error_msg[] = "localuser not found";
				}
			}
			if (!$this->error) {
				$query_params = array();
				$query_params['account_delete_status'] = 1;
				$query_params['account_delete_by'] = (isset($this->authentication->localdata['account_username']) ? $this->authentication->localdata['account_username'] : 'system');
				$query_params['account_delete_datetime'] = $this->DateObject->format('Y-m-d H:i:s');
				try {
					$collectData['delete_account_seq'] = $this->mod_account->edit_user($collectData['seq'], $query_params);
				} catch (Exception $ex) {
					$this->error = true;
					$this->error_msg[] = "Cannot editing to delete user on local-account: {$ex->getMessage()}";
				}
			}
			if (!$this->error) {
				$collectData['collect']['data'] = array(
					'status'		=> true,
					'message'		=> 'Success delete user',
				);
			} else {
				$error_to_show = "";
				foreach ($this->error_msg as $keval) {
					$error_to_show .= $keval;
				}
				$collectData['collect']['data'] = array(
					'status'		=> false,
					'message'		=> $error_to_show,
				);
			}
			if (isset($collectData['collect']['data'])) {
				echo json_encode($collectData['collect']['data']);
			}
		} else {
			$this->accessDenied($collectData);
		}
	}
	function view($seq = 0) {
		$collectData = array();
		$collectData['page'] = 'user-view';
		$collectData['title'] = 'View User: ';
		$collectData['base_path'] = $this->imzers->base_path;
		$collectData['collect'] = array();
		//================================
		if ($this->authentication->localdata) {
			$collectData['collect']['ggdata'] = $this->authentication->userdata;
			$collectData['collect']['userdata'] = $this->authentication->localdata;
			$collectData['collect']['match'] = $this->authentication->get_altorouter_match();
			if (!isset($collectData['collect']['userdata']['account_role'])) {
				$this->error = true;
				$this->error_msg[] = "System not detect account-role of logged-in user";
			}
			if (!$this->error) {
				if (!$this->is_admin) {
					$this->error = true;
					$this->accessDenied($collectData);
				}
			}
		} else {
			header("Location: " . base_url("{$this->imzers->base_path}/account/login"));
			exit;
		}
		//----------------------------------------------------------------------------------------------------------
		$collectData['seq'] = (is_numeric($seq) ? (int)$seq : 0);
		if ($collectData['seq'] === 0) {
			header("Location: " . base_url("{$this->imzers->base_path}/users/lists"));
			exit;
		}
		if ($this->is_admin) {
			if (!$this->error) {
				$collectData['collect']['roles'] = $this->mod_account->get_dashboard_roles();
				$collectData['collect']['address-province'] = $this->mod_account->get_province(360);
				$collectData['collect']['localuser'] = $this->mod_account->get_local_user_by($collectData['seq'], 'seq');
				if (count((array)$collectData['collect']['localuser']) === 0) {
					$this->error = true;
					$this->error_msg[] = "localuser not found";
				}
			}
			if (!$this->error) {
				$collectData['collect']['local-properties'] = $this->mod_account->get_local_user_properties($collectData['seq']);
				$collectData['collect']['user-properties'] = array();
				if (count($collectData['collect']['local-properties']) > 0) {
					foreach ($collectData['collect']['local-properties'] as $keval) {
						$collectData['collect']['user-properties'][$keval->properties_key] = $keval->properties_value;
					}
				}
				$collectData['address_params'] = array(
					'country_code'		=> '360',
					'province_code'		=> (isset($collectData['collect']['user-properties']['user_address_province']) ? $collectData['collect']['user-properties']['user_address_province'] : ''),
					'city_code'			=> (isset($collectData['collect']['user-properties']['user_address_city']) ? $collectData['collect']['user-properties']['user_address_city'] : ''),
					'district_code'		=> (isset($collectData['collect']['user-properties']['user_address_district']) ? $collectData['collect']['user-properties']['user_address_district'] : ''),
					'area_code'			=> (isset($collectData['collect']['user-properties']['user_address_area']) ? $collectData['collect']['user-properties']['user_address_area'] : ''),
				);
				$collectData['collect']['address-province'] = ((strlen($collectData['address_params']['province_code']) > 0) ? $this->mod_account->get_province($collectData['address_params']) : array());
				$collectData['collect']['address-city'] = ((strlen($collectData['address_params']['province_code']) > 0) ? $this->mod_account->get_city($collectData['address_params']) : array());
				$collectData['collect']['address-district'] = ((strlen($collectData['address_params']['city_code']) > 0) ? $this->mod_account->get_district($collectData['address_params']) : array());
				$collectData['collect']['address-area'] = ((strlen($collectData['address_params']['district_code']) > 0) ? $this->mod_account->get_area($collectData['address_params']) : array());
				
				$collectData['collect']['address-values'] = array();
				
				if (count($collectData['collect']['address-province']) > 0) {
					foreach ($collectData['collect']['address-province'] as $keval) {
						if ((int)$keval->province_code === (int)$collectData['address_params']['province_code']) {
							$collectData['collect']['address-values']['province'] = $keval->province_name;
						}
					}
				}
				if (count($collectData['collect']['address-city']) > 0) {
					foreach ($collectData['collect']['address-city'] as $keval) {
						if ((int)$keval->city_code === (int)$collectData['address_params']['city_code']) {
							$collectData['collect']['address-values']['city'] = $keval->city_name;
						}
					}
				}
				if (count($collectData['collect']['address-district']) > 0) {
					foreach ($collectData['collect']['address-district'] as $keval) {
						if ((int)$keval->district_code === (int)$collectData['address_params']['district_code']) {
							$collectData['collect']['address-values']['district'] = $keval->district_name;
						}
					}
				}
				if (count($collectData['collect']['address-area']) > 0) {
					foreach ($collectData['collect']['address-area'] as $keval) {
						if ($keval->area_name === $collectData['address_params']['area_code']) {
							$collectData['collect']['address-values']['area'] = $keval->area_name;
						}
					}
				}
				
			}
			
			//-----
			if (!$this->error) {
				if (isset($collectData['collect']['localuser']->account_email)) {
					$collectData['title'] .= $collectData['collect']['localuser']->account_email;
				}
				
				$this->load->view('dashboard/dashboard.php', $collectData);
			} else {
				$this->session->set_flashdata('error', TRUE);
				$error_to_show = "";
				foreach ($this->error_msg as $keval) {
					$error_to_show .= $keval;
				}
				$this->session->set_flashdata('action_message', $error_to_show);
				header("Location: " . base_url("{$this->imzers->base_path}/users/lists"));
				exit;
			}
			
			
		} else {
			$this->accessDenied($collectData);
		}
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
}




