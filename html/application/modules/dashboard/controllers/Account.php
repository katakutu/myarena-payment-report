<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Account extends MY_Controller {
	public $is_admin = FALSE;
	public $is_merchant = FALSE;
	protected $base_dashboard;
	protected $DateObject;
	protected $email_vendor;
	function __construct() {
		parent::__construct();
		$this->load->config('dashboard/base_dashboard');
		$this->base_dashboard = $this->config->item('base_dashboard');
		$this->email_vendor = (isset($this->base_dashboard['email_vendor']) ? $this->base_dashboard['email_vendor'] : '');
		$this->load->helper('dashboard/dashboard_functions');
		$this->load->library('dashboard/Lib_imzers', $this->base_dashboard, 'imzers');
		$this->load->library('dashboard/Lib_imzcustom', FALSE, 'imzcustom');
		$this->load->library('dashboard/Lib_authentication', $this->base_dashboard, 'authentication');
		// Encrypt
		$this->load->library('dashboard/Lib_rc4crypt', ENCRYPT_KEY, 'rc4crypt');
		$this->rijandelconfig = array('ENCRYPT_KEY' => ENCRYPT_KEY, 'ENCRYPT_IV' => ENCRYPT_IV);
		$this->load->library('dashboard/Lib_rijandelcrypt', $this->rijandelconfig, 'rijandelcrypt');
		$this->load->model('dashboard/Model_account', 'mod_account');
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
	
	//=======================================================
	function login() {
		$collectData = array();
		$collectData['page'] = 'form-login';
		$collectData['title'] = 'Login to system';
		$collectData['base_path'] = $this->imzers->base_path;
		$collectData['collect'] = array();
		$collectData['collect']['match'] = $this->authentication->get_altorouter_match();
		if (!$this->authentication->localdata) {
			$collectData['collect']['address-province'] = $this->mod_account->get_province(360);
			
			$this->load->view('dashboard/form.php', $collectData);
		} else {
			redirect(base_url("{$collectData['base_path']}/dashboard/index"));
			exit;
		}
	}
	function loginaction() {
		$collectData = array();
		$collectData['page'] = 'form-login';
		$collectData['base_path'] = $this->imzers->base_path;
		$input_params = array();
		$user_server = (isset($this->imzcustom->php_input_request['body']['user_server']) ? $this->imzcustom->php_input_request['body']['user_server'] : '');
		if (strlen($user_server) === 0) {
			$user_server = 'localhost';
		}
		$input_params['body'] = array(
			'user_email'		=> (isset($this->imzcustom->php_input_request['body']['user_email']) ? $this->imzcustom->php_input_request['body']['user_email'] : ''),
			'user_username'		=> (isset($this->imzcustom->php_input_request['body']['user_username']) ? $this->imzcustom->php_input_request['body']['user_username'] : ''),
			'user_password'		=> (isset($this->imzcustom->php_input_request['body']['user_password']) ? $this->imzcustom->php_input_request['body']['user_password'] : ''),
		);
		switch (strtolower($user_server)) {
			case 'goodgames':
				$input_params['body']['user_username'] = $this->imzers->safe_text_post($input_params['body']['user_username'], 128);
				$input_params['body']['loginGoodgames'] = 'true';
				$collectData['login'] = $this->mod_account->gg_login($input_params);
			break;
			case 'local':
			case 'localhost':
			default:
				$input_params['body']['user_email'] = $this->imzers->safe_text_post($input_params['body']['user_email'], 128);
				$input_params['body']['loginLocal'] = 'true';
				$collectData['login'] = $this->mod_account->local_login($input_params);
			break;
		}
				
		if ($collectData['login']) {
			if (isset($collectData['login']['success']) && isset($collectData['login']['error'])) {
				if ($collectData['login']['success'] === true) {
					$this->session->set_flashdata('error', FALSE);
					$this->session->set_flashdata('action_message', '');
				} else {
					$this->session->set_flashdata('error', TRUE);
					if (is_array($collectData['login']['error'])) {
						$login_error = "";
						foreach ($collectData['login']['error'] as $val) {
							$login_error .= "- {$val}<br/>\r\n";
						}
						$this->session->set_flashdata('action_message', $login_error);
					} else {
						$this->session->set_flashdata('action_message', 'Unknown error');
					}
					redirect(base_url("{$this->imzers->base_path}/account/login"));
					exit;
				}
			}
			header("Location: " . base_url("{$this->imzers->base_path}/dashboard/index"));
			exit;
		}
	}
	
	function logout() {
		$this->authentication->userdata = null;
		$this->authentication->localdata = null;
		$this->session->unset_userdata('gg_login_account');
		$this->session->unset_userdata('local_login_account');
		$this->session->unset_userdata('social_account_login_seq');
		# Destroy session
		$this->session->sess_destroy();
		
		//session_destroy();
		header("Location: " . base_url("{$this->imzers->base_path}/account/login"));
		exit;
	}
	public function index() {
		$collectData = array(
			'collect'				=> array(),
			'page'					=> 'account-profile',
		);
		$collectData['title'] = 'Profile';
		if (!$this->authentication->localdata) {
			$collectData['page'] = 'form-login';
			header("Location: " . base_url("{$this->imzers->base_path}/account/login"));
			exit;
		} else {
			$collectData['collect']['ggdata'] = $this->authentication->userdata;
			$collectData['collect']['userdata'] = $this->authentication->localdata;
			if (isset($collectData['collect']['userdata']['account_fullname'])) {
				$collectData['title'] .= " - {$collectData['collect']['userdata']['account_fullname']}";
			}
			$collectData['collect']['datetime'] = date('Y-m-d H:i:s');
			if ((int)$this->authentication->localdata['account_role'] > 1) {
				$collectData['page'] = 'account-profile';
				$collectData['collect']['users'] = array(
					'total' => $this->mod_account->get_local_users('total'),
				);
				$this->load->view('dashboard/form.php', $collectData);
			} else {
				$this->accessDenied($collectData);
			}
		}
	}
	//------------------------------------------------
	function register() {
		$collectData = array();
		$collectData['page'] = 'form-register';
		$collectData['base_path'] = $this->imzers->base_path;
		$collectData['title'] = 'Register new account';
		$collectData['collect'] = array();
		if (!$this->authentication->localdata) {
			$collectData['collect']['address-province'] = $this->mod_account->get_province(360);
			$collectData['collect']['roles'] = $this->mod_account->get_dashboard_roles();
			
			$this->load->view('dashboard/form.php', $collectData);
		} else {
			header("Location: " . base_url("{$this->imzers->base_path}/dashboard/index"));
			exit;
		}
	}
	function registeraction() {
		//--------------------
		$error = FALSE;
		$error_msg = [];
		$collectData = array();
		$collectData['page'] = 'form-register';
		$collectData['base_path'] = $this->imzers->base_path;
		$collectData['collect'] = array();
		if ($this->authentication->localdata) {
			header('Location: ' . base_url("{$this->imzers->base_path}/dashboard/index"));
			exit;
		}
		//----------------------------------------------------------------------------------------------------------
		$collectData['collect']['address-province'] = $this->mod_account->get_province(360);
		$collectData['collect']['roles'] = $this->mod_account->get_dashboard_roles();
		$user_params = array();
		if (!$this->authentication->localdata) {
			$user_params['body'] = array(
				'user_fullname' => (isset($this->imzcustom->php_input_request['body']['user_fullname']) ? $this->imzcustom->php_input_request['body']['user_fullname'] : ''),
				'user_email' => (isset($this->imzcustom->php_input_request['body']['user_email']) ? $this->imzcustom->php_input_request['body']['user_email'] : ''),
				'user_password' => (isset($this->imzcustom->php_input_request['body']['user_password']) ? $this->imzcustom->php_input_request['body']['user_password'] : ''),
				'user_password_confirm' => (isset($this->imzcustom->php_input_request['body']['user_password_confirm']) ? $this->imzcustom->php_input_request['body']['user_password_confirm'] : ''),
			);
			
			$form_validation = TRUE;
			if ((strlen($user_params['body']['user_email']) === 0) || (strlen($user_params['body']['user_password']) === 0) || (strlen($user_params['body']['user_fullname']) === 0)) {
				$form_validation = FALSE;
				$error_msg[] = "Input cannot be empty.";
			}
			if (!is_string($user_params['body']['user_fullname']) || !is_string($user_params['body']['user_password'])) {
				$form_validation = FALSE;
				$error_msg[] = "You should use string full name.";
			}
			if (!filter_var($user_params['body']['user_email'], FILTER_VALIDATE_EMAIL)) {
				$form_validation = FALSE;
				$error_msg[] = "You must use valid email format.";
			}
			if ($user_params['body']['user_password'] !== $user_params['body']['user_password_confirm']) {
				$form_validation = FALSE;
				$error_msg[] = "Password and Confirm password should be same.";
			}
			
			
			if (!$form_validation) {
				$this->session->set_flashdata('error', TRUE);
				$error_string = "";
				if (count($error_msg) > 0) {
					foreach ($error_msg as $errorVal) {
						$error_string .= "- {$errorVal}<br/>\n";
					}
				}
				$this->session->set_flashdata('action_message', $error_string);
				header('Location: ' . base_url("{$this->imzers->base_path}/account/register"));
				exit;
			} else {
				$input_params = array(
					'user_fullname'						=> $this->imzers->safe_text_post($user_params['body']['user_fullname'], 64),
					'user_nickname'						=> $this->imzers->safe_text_post($user_params['body']['user_email'], 128),
					'user_username'						=> $this->imzers->safe_text_post($user_params['body']['user_email'], 128), // required unique
					'user_email'						=> $this->imzers->safe_text_post($user_params['body']['user_email'], 128), // required unique
					'user_password'						=> $user_params['body']['user_password'],
					'user_password_confirm'				=> $user_params['body']['user_password_confirm'],
					'user_phonenumber'					=> '',
					'user_phonemobile'					=> '',
					'user_address'						=> '',
					'user_role'							=> '1',
					'subscription_starting'				=> $this->DateObject->format('Y-m-d H:i:s'),
					'subscription_expiring'				=> '', // Later
					'account_activation_ending'			=> '', // Later -> get 1 week
					'account_active'					=> 'N',
					'account_remark'					=> '',
				);
				$input_params['user_email'] = filter_var($input_params['user_email'], FILTER_VALIDATE_EMAIL);
				$input_params['user_phonenumber'] = sprintf('%s', $input_params['user_phonenumber']);
				$input_params['user_phonemobile'] = sprintf('%s', $input_params['user_phonemobile']);
				$input_params['user_role'] = sprintf('%d', $input_params['user_role']);
				$input_params['user_fullname'] = sprintf('%s', $input_params['user_fullname']);
				
				
				$query_params = array(
					'account_username'				=> ((strlen($input_params['user_username']) > 0) ? strtolower($input_params['user_username']) : ''),
					'account_email'					=> ((strlen($input_params['user_email']) > 0) ? strtolower($input_params['user_email']) : ''),
					'account_hash'					=> '', // Later
					'account_password'				=> '', // Later
					'account_inserting_datetime'	=> $this->DateObject->format('Y-m-d H:i:s'),
					'account_inserting_remark'		=> ((strlen($input_params['account_remark']) > 0) ? $input_params['account_remark'] : ''),
					'account_activation_code'		=> '', // Later,
					'account_activation_starting'	=> $this->DateObject->format('Y-m-d H:i:s'),
					'account_activation_ending'		=> $this->DateObject->format('Y-m-d H:i:s'), // Later will add 7 days
					'account_activation_datetime'	=> NULL,
					'account_activation_status'		=> 'N', // N as default
					'account_activation_by'			=> '', // Should be user itself or by administrator (Set Later)
					'account_active'				=> ((strlen($input_params['account_active']) > 0) ? strtoupper($input_params['account_active']) : 'N'),
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
					'subscription_starting'			=> $this->DateObject->format('Y-m-d H:i:s'), // NOW()
					'subscription_expiring'			=> $this->DateObject->format('Y-m-d H:i:s'), // Current NOW() and will add 1 Year
				);
				//=============================================================
				if (!$error) {
					if ($input_params['user_password'] !== $input_params['user_password_confirm']) {
						$error = true;
						$error_msg[] = "Password and confirm password not match";
					}
				}
				if (!$error) {
					try {
						$account_activation_ending = new DateTime($query_params['account_activation_ending']);
					} catch (Exception $ex) {
						$error = true;
						$error_msg[] = "account-activation-ending: {$ex->getMessage()}";
					}
				}
				if (!$error) {
					$query_params['account_activation_ending'] = $account_activation_ending->add(new DateInterval('P7D')); // Add 7 Days for date-activation-expired
					$query_params['account_activation_ending'] = $account_activation_ending->format('Y-m-d H:i:s');
					try {
						$subscription_starting = new DateTime($query_params['subscription_starting']);
					} catch (Exception $ex) {
						$error = true;
						$error_msg[] = "account-activation-ending: {$ex->getMessage()}";
					}
				}
				if (!$error) {
					$query_params['subscription_starting'] = $subscription_starting->format('Y-m-d H:i:s');
					try {
						$subscription_expiring = new DateTime($query_params['subscription_expiring']);
					} catch (Exception $ex) {
						$error = true;
						$error_msg[] = "subscription-expiring: {$ex->getMessage()}";
					}
				}
				if (!$error) {
					$query_params['subscription_expiring'] = $subscription_expiring->add(new DateInterval('P1Y')); // Add 1 Year For Default
					$query_params['subscription_expiring'] = $subscription_expiring->format('Y-m-d H:i:s');
				}
				if (!$error) {
					try {
						$input_params['unique_datetime'] = $this->mod_account->create_unique_datetime("Asia/Bangkok");
					} catch (Exception $ex) {
						$error = true;
						$error_msg[] = "Cannot create new unique datetime: {$ex->getMessage()}";
					}
				}
				if (!$error) {
					$query_params['account_hash'] = $this->rc4crypt->bEncryptRC4($input_params['unique_datetime']);
					try {
						$input_params['account_password'] = ("{$this->rc4crypt->bDecryptRC4($query_params['account_hash'])}|{$input_params['user_password']}");
					} catch (Exception $ex) {
						$error = true;
						$error_msg[] = $ex->getMessage();
					}
				}
				if (!$error) {
					$query_params['account_password'] = sha1($input_params['account_password']);
					$query_params['account_activation_code'] = md5($query_params['account_hash']);
				}
				if (!$error) {
					if (strtoupper($query_params['account_active']) === strtoupper('Y')) {
						$query_params['account_activation_status'] = 'Y';
					} else {
						$query_params['account_activation_status'] = 'N';
					}
				}
				if (!$error) {
					if (strtoupper($query_params['account_activation_status']) === strtoupper('Y')) {
						$query_params['account_activation_datetime'] = $this->DateObject->format('Y-m-d H:i:s');
						$query_params['account_activation_by'] = 'register-system';
					}
				}
				//-- Check username or email exists
				if (!$error) {
					try {
						$collectData['local_users_by_username'] = $this->mod_account->get_local_user_by($query_params['account_username'], 'username');
					} catch (Exception $ex) {
						$error = true;
						$error_msg[] = "Cannot check username is exists from model.";
					}
				}
				if (!$error) {
					if (count($collectData['local_users_by_username']) > 0) {
						$error = true;
						$error_msg[] = "Username already taken.";
					}
				}
				if (!$error) {
					try {
						$collectData['local_users_by_email'] = $this->mod_account->get_local_user_by($query_params['account_email'], 'email');
					} catch (Exception $ex) {
						$error = true;
						$error_msg[] = "Cannot check email is exists from model.";
					}
				}
				if (!$error) {
					if (count($collectData['local_users_by_email']) > 0) {
						$error = true;
						$error_msg[] = "Email already taken.";
					}
				}
				//-- Add User to Database
				if (!$error) {
					try {
						$collectData['new_account_seq'] = $this->mod_account->add_user($query_params);
					} catch (Exception $ex) {
						$error = true;
						$error_msg[] = "Cannot insert new user to local-account: {$ex->getMessage()}";
					}
				}
				//-- Add User Properties
				if (!$error) {
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
				# Send email
				if (!$error) {
					$query_params['account_action_subject'] = "New User Registration";
					$query_params['account_action_body'] = "";
					try {
						$collectData['local_data'] = $this->mod_account->get_local_user_by($collectData['new_account_seq'], 'seq');
					} catch (Exception $ex) {
						$error = true;
						$error_msg[] = "Cannot get new registration of local-user data.";
					}
				}
				if (!$error) {
					$query_params['account_action_body'] .= "You just register on " . base_config('site-name') . "<br/>";
					$query_params['account_action_body'] .= "Activate your account by visit activation code below:<br/>";
					$query_params['account_action_body'] .= "<a href='" . base_url("{$collectData['base_path']}/account/activation/{$collectData['local_data']->account_activation_code}") . "'>" . base_url("{$collectData['base_path']}/account/activation/{$collectData['local_data']->account_activation_code}") . "</a><br/>";
					
					$query_params['account_action_body'] .= "<br/>----<br/>";
					$query_params['account_action_body'] .= base_config('site-name') . "<br/>";
					$query_params['account_action_body'] .= base_config('site-version');
					//-----------------------
					# Send-email action
					try {
						$collectData['send_email'] = $this->authentication->send_email($this->email_vendor, $query_params);
					} catch (Exception $ex) {
						$error = true;
						$error_msg[] = "Cannot send email for new registration user.";
					}
				}
				//-------------------------------------
				if (!$error) {
					$this->session->set_flashdata('error', FALSE);
					$this->session->set_flashdata('action_message', 'Success register new user, please check your email for activation code.');
					header('Location: ' . base_url("{$collectData['base_path']}/account/login"));
				} else {
					$this->session->set_flashdata('error', TRUE);
					$error_to_show = "";
					foreach ($error_msg as $keval) {
						$error_to_show .= "- {$keval}<br/>\r\n";
					}
					$this->session->set_flashdata('action_message', $error_to_show);
					header('Location: ' . base_url("{$collectData['base_path']}/account/register"));
				}
				exit;
			}
		}
		exit;
	}
	function activation($activation_code = '') {
		$error = false;
		$error_msg = [];
		$collectData = array();
		$collectData['page'] = 'form-activation';
		$collectData['base_path'] = $this->imzers->base_path;
		$collectData['collect'] = array();
		$activation_code = (is_string($activation_code) ? strtolower($activation_code) : '');
		$activation_code = sprintf('%s', $activation_code);
		if (strlen($activation_code) === 0) {
			$this->session->set_flashdata('error', TRUE);
			$this->session->set_flashdata('action_message', 'Activation code should not be empty.');
			header('Location: ' . base_url("{$collectData['base_path']}/account/activationForm"));
			exit;
		}
		$query_params = array();
		$input_params = array(
			'account_activation_code'		=> substr($activation_code, 0, 32),
		);
		try {
			$collectData['sql_result'] = $this->mod_account->get_local_user_by($input_params['account_activation_code'], 'activation');
		} catch (Exception $ex) {
			$error = true;
			$error_msg[] = "Exception error while getting userdata: {$ex->getMessage()}";
		}
		if (!$error) {
			if (count($collectData['sql_result']) === 0) {
				$error = true;
				$error_msg[] = "Result of userdata returning empty data.";
			}
		}
		if (!$error) {
			if (strtoupper($collectData['sql_result']->account_active) === strtoupper('Y')) {
				$error = true;
				$error_msg[] = "Account already activated at {$collectData['sql_result']->account_activation_datetime}, please login at <a href='" . base_url("{$collectData['base_path']}/account/login") . "'>Login page</a>";
			}
		}
		if (!$error) {
			$collectData['account_activation_ending'] = new DateTime($collectData['sql_result']->account_activation_ending);
			if (strtotime($this->DateObject->format('Y-m-d H:i:s')) > strtotime($collectData['account_activation_ending']->format('Y-m-d H:i:s'))) {
				$error = true;
				$query_params['account_activation_datetime'] = $this->DateObject->format('Y-m-d H:i:s');
				$query_params['account_activation_status'] = 'Y';
				$query_params['account_inserting_remark'] = 'Activation code is expired';
				$this->mod_account->update_local_account_data($collectData['sql_result']->seq, $query_params);
				$error_msg[] = "Account activation code already expired at {$collectData['sql_result']->account_activation_ending} by {$this->DateObject->format('Y-m-d H:i:s')}, please request new activation code";
			}
		}
		if (!$error) {
			try {
				$query_params['account_activation_code'] = $this->mod_account->create_unique_datetime("Asia/Bangkok");
				$query_params['account_activation_code'] = md5($query_params['account_activation_code']);
			} catch (Exception $ex) {
				$error = true;
				$error_msg[] = "Error for creating new activation code: {$ex->getMessage()}";
			}
		}
		if (!$error) {
			$query_params['account_activation_by'] = $collectData['sql_result']->account_username;
			$query_params['account_active'] = 'Y';
			$query_params['account_activation_datetime'] = $this->DateObject->format('Y-m-d H:i:s');
			$query_params['account_activation_status'] = 'Y';
			$query_params['account_inserting_remark'] = 'Activation by user by activation code';
			$this->mod_account->update_local_account_data($collectData['sql_result']->seq, $query_params);
			
			$this->session->set_flashdata('error', FALSE);
			$this->session->set_flashdata('action_message', 'Account successfully activated, thankyou for confirming you email.');
			
			header('Location: ' . base_url("{$collectData['base_path']}/account/login"));
		} else {
			$this->session->set_flashdata('error', TRUE);
			$error_to_show = "";
			foreach ($error_msg as $keval) {
				$error_to_show .= "- {$keval}<br/>\n";
			}
			$this->session->set_flashdata('action_message', $error_to_show);
			header('Location: ' . base_url("{$collectData['base_path']}/account/activationForm"));
		}
	}
	function activationForm() {
		$error = false;
		$error_msg = [];
		$collectData = array();
		$collectData['page'] = 'form-activation';
		$collectData['base_path'] = $this->imzers->base_path;
		$collectData['collect'] = array();
		if (!$this->authentication->localdata) {
			$collectData['collect']['address-province'] = $this->mod_account->get_province(360);
			$collectData['collect']['roles'] = $this->mod_account->get_dashboard_roles();
			
			$this->load->view('dashboard/form.php', $collectData);
		} else {
			header('Location: ' . base_url("{$collectData['base_path']}/dashboard/index"));
			exit;
		}
	}
	
	function activationaction() {
		$error = false;
		$error_msg = [];
		$collectData = array();
		$collectData['page'] = 'form-activation';
		$collectData['base_path'] = $this->imzers->base_path;
		$collectData['collect'] = array();
		if ($this->session->userdata('social_account_login_seq') != FALSE) {
			header('Location: ' . base_url("{$collectData['base_path']}/dashboard/index"));
			exit;
		}
		//----------------------------------------------------------------------------------------------------------
		$collectData['collect']['roles'] = $this->mod_account->get_dashboard_roles();
		$user_params = array();
		if (!$this->authentication->localdata) {
			$user_params['body'] = array(
				'user_email' => (isset($this->imzcustom->php_input_request['body']['user_email']) ? $this->imzcustom->php_input_request['body']['user_email'] : ''),
			);
			$form_validation = TRUE;
			if (strlen($user_params['body']['user_email']) === 0) {
				$form_validation = FALSE;
				$error_msg[] = "Input cannot be empty.";
			}
			if (!is_string($user_params['body']['user_email'])) {
				$form_validation = FALSE;
				$error_msg[] = "You should use string email name.";
			}
			if (!filter_var($user_params['body']['user_email'], FILTER_VALIDATE_EMAIL)) {
				$form_validation = FALSE;
				$error_msg[] = "You must use valid email format.";
			}
			if(!$form_validation) {
				$this->session->set_flashdata('error', TRUE);
				$error_string = "";
				if (count($error_msg) > 0) {
					foreach ($error_msg as $errorVal) {
						$error_string .= "- {$errorVal}<br/>\n";
					}
				}
				$this->session->set_flashdata('action_message', $error_string);
				header('Location: ' . base_url("{$this->imzers->base_path}/account/activationForm"));
				exit;
			} else {
				$input_params = array(
					'user_email'	=> $this->imzers->safe_text_post($user_params['body']['user_email'], 128), // required unique
				);
				$input_params['user_email'] = filter_var($input_params['user_email'], FILTER_VALIDATE_EMAIL);
				$query_params = array(
					'account_email'					=> ((strlen($input_params['user_email']) > 0) ? strtolower($input_params['user_email']) : ''),
					'account_inserting_remark'		=> '',
					'account_activation_code'		=> $this->mod_account->create_unique_datetime("Asia/Bangkok"),
					'account_activation_starting'	=> $this->DateObject->format('Y-m-d H:i:s'),
					'account_activation_ending'		=> $this->DateObject->format('Y-m-d H:i:s'), // Later will add 7 days
					'account_activation_datetime'	=> NULL,
					'account_activation_status'		=> 'N', // N as default
					'account_activation_by'			=> '', // Should be user itself or by administrator (Set Later)
					'account_active'				=> 'N',
					'account_edited_datetime'		=> $this->DateObject->format('Y-m-d H:i:s'),
					'account_edited_by'				=> NULL, // Later by local-data object
					'subscription_starting'			=> $this->DateObject->format('Y-m-d H:i:s'), // NOW()
					'subscription_expiring'			=> $this->DateObject->format('Y-m-d H:i:s'), // Current NOW() and will add 1 Year
				);
				//-------------------------------------
				//-- Check email exists
				if (!$error) {
					try {
						$collectData['local_users'] = $this->mod_account->get_local_user_by($query_params['account_email'], 'email');
					} catch (Exception $ex) {
						$error = true;
						$error_msg[] = "Cannot check email is exists from model while doing activation request.";
					}
				}
				if (!$error) {
					if (count((array)$collectData['local_users']) === 0) {
						$error = true;
						$error_msg[] = "Email is not registered on system.";
					}
				}
				if (!$error) {
					if (strtoupper($collectData['local_users']->account_active) === strtoupper('Y')) {
						$error = true;
						$error_msg[] = "Account already activated at {$collectData['local_users']->account_activation_datetime}, please login.";
					}
				}
				if (!$error) {
					try {
						$collectData['account_activation_ending'] = new DateTime($query_params['account_activation_ending']);
					} catch (Exception $ex) {
						$error = true;
						$error_msg[] = "account-activation-ending: {$ex->getMessage()}";
					}
				}
				if (!$error) {
					try {
						$collectData['localdata_activation_ending'] = new DateTime($collectData['local_users']->account_activation_ending);
					} catch (Exception $ex) {
						$error = true;
						$error_msg[] = "localdata-activation-ending: {$ex->getMessage()}";
					}
				}
				if (!$error) {
					$query_params['account_activation_ending'] = $collectData['account_activation_ending']->add(new DateInterval('P7D')); // Add 7 Days for date-activation-expired
					$query_params['account_activation_ending'] = $collectData['account_activation_ending']->format('Y-m-d H:i:s');
					$query_params['account_activation_code'] = md5($query_params['account_activation_code']);
				}
				//# Check amount of max-allowed activation-code request per-day
				if (!$error) {
					try {
						$collectData['local_user_properties'] = $this->mod_account->get_local_user_properties_by($collectData['local_users']->seq, '', 'activation');
					} catch (Exception $ex) {
						$error = true;
						$error_msg[] = "Cannot get user properties by: {$ex->getMessage()}";
					}
				}
				if (!$error) {
					if (count((array)$collectData['local_user_properties']) === 0) {
						try {
							$collectData['set_local_user_properties_seq'] = $this->mod_account->set_local_user_properties_by($collectData['local_users']->seq, 1, 'activation');
						} catch (Exception $ex) {
							$error = true;
							$error_msg[] = "Cannot set local-user-propeties with key activation: {$ex->getMessage()}";
						}
					} else {
						$collectData['set_local_user_properties_seq'] = 0;
					}
				}
				if (!$error) {
					if ((int)$collectData['set_local_user_properties_seq'] > 0) {
						try {
							$collectData['local_user_properties'] = $this->mod_account->get_local_user_properties_by($collectData['local_users']->seq, $collectData['set_local_user_properties_seq'], 'seq');
						} catch (Exception $ex) {
							$error = true;
							$error_msg[] = "Cannot get user properties by seq: {$ex->getMessage()}";
						}
					}
				}
				if (!$error) {
					if (!isset($collectData['local_user_properties']->seq)) {
						$error = true;
						$error_msg[] = "local-user properties data no as expected index format";
					}
				}
				// set only 5 time per-day
				if (!$error) {
					if (intval($collectData['local_user_properties']->properties_value) > 5) {
						$error = true;
						$error_msg[] = "Only 5 times per-day for requesting new activation code, please try again on another day or tomorrow.";
					}
				}
				if (!$error) {
					$collectData['activation_code_count'] = 1;
					if (date('Ymd', strtotime($collectData['local_user_properties']->properties_datetime)) === date('Ymd')) {
						$collectData['activation_code_count'] = (intval($collectData['local_user_properties']->properties_value) + 1);
					}
					try {
						$collectData['set_local_user_properties_seq'] = $this->mod_account->set_local_user_properties_by($collectData['local_users']->seq, $collectData['activation_code_count'], 'activation');
					} catch (Exception $ex) {
						$error = true;
						$error_msg[] = "Cannot set local-user-propeties by update on activation: {$ex->getMessage()}";
					}
				}
				//-------------------------------------
				if (!$error) {
					$query_params['account_activation_by'] = $collectData['local_users']->account_username;
					$query_params['account_active'] = 'N';
					$query_params['account_activation_datetime'] = $this->DateObject->format('Y-m-d H:i:s');
					$query_params['account_activation_status'] = 'N';
					$query_params['account_inserting_remark'] = 'Request new activation code';
				}
				# Send email
				if (!$error) {
					#### Update new activation-code
					$this->mod_account->update_local_account_data($collectData['local_users']->seq, $query_params);
					try {
						$collectData['local_data'] = $this->mod_account->get_local_user_by($collectData['local_users']->seq, 'seq');
					} catch (Exception $ex) {
						$error = true;
						$error_msg[] = "Cannot get new registration of local-user data.";
					}
				}
				if (!$error) {
					if (!isset($collectData['local_data']->seq)) {
						$error = true;
						$error_msg[] = "Un-expected local-data index from models.";
					}
				}
				if (!$error) {
					$query_params['account_action_subject'] = 'New Activation Code';
					try {
						$collectData['email_template'] = $this->imzers->reading_filehtml_path(base_config('email_template'));
					} catch (Exception $ex) {
						$error = true;
						$error_msg[] = "Exception error while reading html filepath.";
					}
				}
				if (!$error) {
					$collectData['email_array'] = array(
						'TAG_EMAIL_TITLE'				=> $query_params['account_action_subject'],
						'TAG_EMAIL_DESCRIPTION'			=> "You just request new activation code on " . base_config('site-name') . "<br/>" . "Activate your account by visit activation code below:<br/>" . "<a href='" . base_url("{$collectData['base_path']}/account/activation/{$collectData['local_data']->account_activation_code}") . "'>" . base_url("{$collectData['base_path']}/account/activation/{$collectData['local_data']->account_activation_code}") . "</a><br/>" . "<br/>----<br/>" . base_config('site-name') . "<br/>" . base_config('site-version'),
						'TAG_EMAIL_LINK_ADDRESS'		=> base_url("{$collectData['base_path']}/account/activation/{$collectData['local_data']->account_activation_code}"),
						'TAG_EMAIL_LINK_NAME'			=> "Activate Account",
						'TAG_EMAIL_TICKET_SUBJECT'		=> $query_params['account_action_subject'],
						'TAG_EMAIL_TICKET_DATE'			=> $this->DateObject->format('Y-m-d H:i:s'),
					);
					$query_params['account_action_body'] = $collectData['email_template'];
					foreach ($collectData['email_array'] as $key => $val) {
						$query_params['account_action_body'] = str_replace("[{$key}]", $val, $query_params['account_action_body']);
					}
					//-----------------------
					# Send-email action
					try {
						$collectData['send_email'] = $this->authentication->send_email($this->email_vendor, $query_params);
					} catch (Exception $ex) {
						$error = true;
						$error_msg[] = "Cannot send email for request activation code.";
					}
				}
				//---- Redirect back to login page
				if (!$error) {
					$this->session->set_flashdata('error', FALSE);
					$this->session->set_flashdata('action_message', 'New activation code already send to your email at ' . $collectData['local_users']->account_email);
					
					header('Location: ' . base_url("{$collectData['base_path']}/account/login"));
				} else {
					$this->session->set_flashdata('error', TRUE);
					$error_to_show = "";
					foreach ($error_msg as $keval) {
						$error_to_show .= "- {$keval}<br/>";
					}
					$this->session->set_flashdata('action_message', $this->imzers->sql_addslashes($error_to_show));
					
					
					header('Location: ' . base_url("{$collectData['base_path']}/account/activationForm"));
				}
				exit;
			}
		} else {
			header("Location: " . base_url("{$collectData['base_path']}/dashboard/index"));
			exit;
		}
	}
	function passwordForget() {
		$error = false;
		$error_msg = [];
		$collectData = array();
		$collectData['page'] = 'form-password';
		$collectData['title'] = 'Reset Password';
		$collectData['base_path'] = $this->imzers->base_path;
		$collectData['collect'] = array();
		if (!$this->authentication->localdata) {
			$collectData['collect']['address-province'] = $this->mod_account->get_province(360);
			$collectData['collect']['roles'] = $this->mod_account->get_dashboard_roles();
			$this->load->view('dashboard/form.php', $collectData);
		} else {
			header('Location: ' . base_url("{$collectData['base_path']}/dashboard/index"));
			exit;
		}
	}
	function passwordForgetAction() {
		$error = false;
		$error_msg = [];
		$collectData = array();
		$collectData['page'] = 'form-password';
		$collectData['base_path'] = $this->imzers->base_path;
		$collectData['collect'] = array();
		if ($this->session->userdata('social_account_login_seq') != FALSE) {
			header('Location: ' . base_url("{$collectData['base_path']}/dashboard/index"));
			exit;
		}
		//----------------------------------------------------------------------------------------------------------
		$collectData['collect']['roles'] = $this->mod_account->get_dashboard_roles();
		$user_params = array();
		if (!$this->authentication->localdata) {
			$user_params['body'] = array(
				'user_email' => (isset($this->imzcustom->php_input_request['body']['user_email']) ? $this->imzcustom->php_input_request['body']['user_email'] : ''),
			);
			$form_validation = TRUE;
			if (strlen($user_params['body']['user_email']) === 0) {
				$form_validation = FALSE;
				$error_msg[] = "Input cannot be empty.";
			}
			if (!is_string($user_params['body']['user_email'])) {
				$form_validation = FALSE;
				$error_msg[] = "You should use string email name.";
			}
			if (!filter_var($user_params['body']['user_email'], FILTER_VALIDATE_EMAIL)) {
				$form_validation = FALSE;
				$error_msg[] = "You must use valid email format.";
			}
			if(!$form_validation) {
				$this->session->set_flashdata('error', TRUE);
				$error_string = "";
				if (count($error_msg) > 0) {
					foreach ($error_msg as $errorVal) {
						$error_string .= "- {$errorVal}<br/>\n";
					}
				}
				$this->session->set_flashdata('action_message', $error_string);
				header('Location: ' . base_url("{$this->imzers->base_path}/account/passwordForget"));
				exit;
			} else {
				$input_params = array(
					'user_email'	=> $this->imzers->safe_text_post($user_params['body']['user_email'], 128), // required unique
				);
				$input_params['user_email'] = filter_var($input_params['user_email'], FILTER_VALIDATE_EMAIL);
				$query_params = array(
					'account_email'					=> ((strlen($input_params['user_email']) > 0) ? strtolower($input_params['user_email']) : ''),
					'account_inserting_remark'		=> 'Request new password',
					'account_activation_code'		=> sha1($this->mod_account->create_unique_datetime("Asia/Bangkok")),
				);
				//-------------------------------------
				//-- Check email exists
				if (!$error) {
					try {
						$collectData['local_users'] = $this->mod_account->get_local_user_by($query_params['account_email'], 'email');
					} catch (Exception $ex) {
						$error = true;
						$error_msg[] = "Cannot check email is exists from model while doing activation request.";
					}
				}
				if (!$error) {
					if (count((array)$collectData['local_users']) === 0) {
						$error = true;
						$error_msg[] = "Email is not registered on system.";
					}
				}
				if (!$error) {
					if (!isset($collectData['local_users']->seq)) {
						$error = true;
						$error_msg[] = "Un-expected data index from models.";
					}
				}
				if (!$error) {
					$collectData['local_user_properties'] = array();
					$query_params['account_password_salt'] = $this->rijandelcrypt->encrypt($query_params['account_activation_code']);
					$query_params['account_password_string'] = "{$query_params['account_password_salt']}|{$query_params['account_activation_code']}";
					$query_params['account_password_hash'] = $this->rc4crypt->EncryptRC4($query_params['account_password_string']);
					//# Check amount of max-allowed request new-password per-day
					# password-count
					try {
						$collectData['local_user_properties']['password_count'] = $this->mod_account->get_local_user_properties_by($collectData['local_users']->seq, '', 'password-count');
					} catch (Exception $ex) {
						$error = true;
						$error_msg[] = "Cannot get user properties by password-count: {$ex->getMessage()}";
					}
				}
				if (!$error) {
					if (count((array)$collectData['local_user_properties']['password_count']) === 0) {
						try {
							$collectData['set_local_user_properties_seq'] = $this->mod_account->set_local_user_properties_by($collectData['local_users']->seq, 1, 'password-count');
						} catch (Exception $ex) {
							$error = true;
							$error_msg[] = "Cannot set local-user-propeties with key password-count: {$ex->getMessage()}";
						}
					} else {
						$collectData['set_local_user_properties_seq'] = 0;
					}
				}
				if ((int)$collectData['set_local_user_properties_seq'] > 0) {
					try {
						$collectData['local_user_properties']['password_count'] = $this->mod_account->get_local_user_properties_by($collectData['local_users']->seq, $collectData['set_local_user_properties_seq'], 'seq');
					} catch (Exception $ex) {
						$error = true;
						$error_msg[] = "Cannot get user properties by seq on password-count: {$ex->getMessage()}";
					}
				}
				if (!$error) {
					if (!isset($collectData['local_user_properties']['password_count']->seq)) {
						$collectData['local_user_properties']['password_count']->seq = $collectData['local_users']->seq;
						$collectData['local_user_properties']['password_count']->properties_key = 'user-request-password-count';
						$collectData['local_user_properties']['password_count']->properties_value = 0;
						$collectData['local_user_properties']['password_count']->properties_datetime = date('Y-m-d H:i:s');
					}
				}
				# password-string
				if (!$error) {
					try {
						$collectData['local_user_properties']['password_string'] = $this->mod_account->get_local_user_properties_by($collectData['local_users']->seq, $query_params['account_password_string'], 'password-string');
					} catch (Exception $ex) {
						$error = true;
						$error_msg[] = "Cannot get user properties by password-string: {$ex->getMessage()}";
					}
				}
				if (!$error) {
					if (!$collectData['local_user_properties']['password_string']) {
						try {
							$collectData['set_local_user_properties_seq'] = $this->mod_account->set_local_user_properties_by($collectData['local_users']->seq, $query_params['account_password_string'], 'password-string');
						} catch (Exception $ex) {
							$error = true;
							$error_msg[] = "Cannot set local-user-propeties with key password-string: {$ex->getMessage()}";
						}
					} else {
						$collectData['set_local_user_properties_seq'] = $collectData['local_user_properties']['password_string']->seq;
						$query_params['account_password_hash'] = $this->rc4crypt->EncryptRC4($collectData['local_user_properties']['password_string']->properties_value);
					}
				}
				if ((int)$collectData['set_local_user_properties_seq'] > 0) {
					try {
						$collectData['local_user_properties']['password_string'] = $this->mod_account->get_local_user_properties_by($collectData['local_users']->seq, $collectData['set_local_user_properties_seq'], 'seq');
					} catch (Exception $ex) {
						$error = true;
						$error_msg[] = "Cannot get user properties by seq on password-string: {$ex->getMessage()}";
					}
				}
				if (!$error) {
					if (!isset($collectData['local_user_properties']['password_string']->seq)) {
						$error = true;
						$error_msg[] = "local-user properties data no as expected index format by password-string";
					}
				}
				# password-hash
				if (!$error) {
					try {
						$collectData['local_user_properties']['password_hash'] = $this->mod_account->get_local_user_properties_by($collectData['local_users']->seq, $query_params['account_password_hash'], 'password-hash');
					} catch (Exception $ex) {
						$error = true;
						$error_msg[] = "Cannot get user properties by password-hash: {$ex->getMessage()}";
					}
				}
				if (!$error) {
					if (count((array)$collectData['local_user_properties']['password_hash']) === 0) {
						try {
							$collectData['set_local_user_properties_seq'] = $this->mod_account->set_local_user_properties_by($collectData['local_users']->seq, $query_params['account_password_hash'], 'password-hash');
						} catch (Exception $ex) {
							$error = true;
							$error_msg[] = "Cannot set local-user-propeties with key password-hash: {$ex->getMessage()}";
						}
					} else {
						$collectData['set_local_user_properties_seq'] = 0;
					}
				}
				if ((int)$collectData['set_local_user_properties_seq'] > 0) {
					try {
						$collectData['local_user_properties']['password_hash'] = $this->mod_account->get_local_user_properties_by($collectData['local_users']->seq, $collectData['set_local_user_properties_seq'], 'seq');
					} catch (Exception $ex) {
						$error = true;
						$error_msg[] = "Cannot get user properties by seq on password-hash: {$ex->getMessage()}";
					}
				}
				if (!$error) {
					if (!isset($collectData['local_user_properties']['password_hash']->seq)) {
						$error = true;
						$error_msg[] = "local-user properties data no as expected index format by password-hash";
					}
				}
				//--------------------------------------------------------------------------------------------------
				// set only n time per-day
				if (!$error) {
					if (intval($collectData['local_user_properties']['password_count']->properties_value) > (int)base_config('base_password_forget')) {
						$error = true;
						$error_msg[] = "Only " . (int)base_config('base_password_forget') . " times per-day for requesting new password, please try again on another day or tomorrow.";
					}
				}
				if (!$error) {
					$collectData['request_password_count'] = 1;
					if (date('Ymd', strtotime($collectData['local_user_properties']['password_count']->properties_datetime)) === date('Ymd')) {
						$collectData['request_password_count'] = (intval($collectData['local_user_properties']['password_count']->properties_value) + 1);
					}
					try {
						$collectData['set_local_user_properties_seq'] = $this->mod_account->set_local_user_properties_by($collectData['local_users']->seq, $collectData['request_password_count'], 'password-count');
					} catch (Exception $ex) {
						$error = true;
						$error_msg[] = "Cannot set local-user-propeties by update on password-count: {$ex->getMessage()}";
					}
				}
				//-------------------------------------
				# Send email
				if (!$error) {
					$query_params['account_action_subject'] = 'New Password Request';
					try {
						$collectData['email_template'] = $this->imzers->reading_filehtml_path(base_config('email_template'));
					} catch (Exception $ex) {
						$error = true;
						$error_msg[] = "Exception error while reading html filepath.";
					}
				}
				if (!$error) {
					$collectData['email_array'] = array(
						'TAG_EMAIL_TITLE'				=> $query_params['account_action_subject'],
						'TAG_EMAIL_DESCRIPTION'			=> "You just request new password code on " . base_config('site-name') . "<br/>",
						'TAG_EMAIL_LINK_ADDRESS'		=> base_url("{$collectData['base_path']}/account/passwordNew/{$collectData['local_user_properties']['password_hash']->properties_value}"),
						'TAG_EMAIL_LINK_NAME'			=> "Change Password",
						'TAG_EMAIL_TICKET_SUBJECT'		=> $query_params['account_action_subject'],
						'TAG_EMAIL_TICKET_DATE'			=> $this->DateObject->format('Y-m-d H:i:s'),
					);
					$collectData['email_array']['TAG_EMAIL_DESCRIPTION'] .= "Please change your new password by visit activation code below:<br/>";
					$collectData['email_array']['TAG_EMAIL_DESCRIPTION'] .= "<a href='" . base_url("{$collectData['base_path']}/account/passwordNew/{$collectData['local_user_properties']['password_hash']->properties_value}") . "'>" . base_url("{$collectData['base_path']}/account/passwordNew/{$collectData['local_user_properties']['password_hash']->properties_value}") . "</a><br/>";
					$collectData['email_array']['TAG_EMAIL_DESCRIPTION'] .= "<br/>----<br/>";
					$collectData['email_array']['TAG_EMAIL_DESCRIPTION'] .= base_config('site-name') . "<br/>";
					$collectData['email_array']['TAG_EMAIL_DESCRIPTION'] .= base_config('site-version');
					$query_params['account_action_body'] = $collectData['email_template'];
					foreach ($collectData['email_array'] as $key => $val) {
						$query_params['account_action_body'] = str_replace("[{$key}]", $val, $query_params['account_action_body']);
					}
					//-----------------------
					# Send-email action
					try {
						$collectData['send_email'] = $this->authentication->send_email($this->email_vendor, $query_params);
					} catch (Exception $ex) {
						$error = true;
						$error_msg[] = "Cannot send email for request lost password.";
					}
				}
				//---- Redirect back to login page
				if (!$error) {
					$this->session->set_flashdata('error', FALSE);
					$this->session->set_flashdata('action_message', 'New password activation code already emailed to ' . $collectData['local_users']->account_email);
					
					header('Location: ' . base_url("{$collectData['base_path']}/account/login"));
				} else {
					$this->session->set_flashdata('error', TRUE);
					$error_to_show = "";
					foreach ($error_msg as $keval) {
						$error_to_show .= "- {$keval}<br/>\n";
					}
					$this->session->set_flashdata('action_message', $error_to_show);
					header('Location: ' . base_url("{$collectData['base_path']}/account/passwordForget"));
				}
				exit;
			}
		}
	}
	function passwordNew($activation_code = '') {
		$error = false;
		$error_msg = [];
		$collectData = array();
		$collectData['page'] = 'form-password';
		$collectData['base_path'] = $this->imzers->base_path;
		$collectData['collect'] = array();
		//----
		$activation_code = (is_string($activation_code) ? strtolower($activation_code) : '');
		$activation_code = sprintf('%s', $activation_code);
		if (strlen($activation_code) === 0) {
			$this->session->set_flashdata('error', TRUE);
			$this->session->set_flashdata('action_message', 'Password forget code should not be empty.');
			header('Location: ' . base_url("{$collectData['base_path']}/account/passwordForget"));
			exit;
		}
		$query_params = array();
		$input_params = array(
			'account_activation_code'		=> sprintf("%s", $activation_code),
		);
		//---------------------------------------------------------------------
		$collectData['local_user_properties'] = array();
		# get local-properties-data by password-hash
		if (!$error) {
			try {
				$collectData['local_user_properties']['password_hash'] = $this->mod_account->get_local_user_properties_by('user-request-password-hash', $input_params['account_activation_code'], 'properties-value');
			} catch (Exception $ex) {
				$error = true;
				$error_msg[] = "Cannot get user properties by password-hash by user-request-password-hash on properties-value: {$ex->getMessage()}";
			}
		}
		if (!$error) {
			if (!isset($collectData['local_user_properties']['password_hash']->local_seq)) {
				$error = true;
				$error_msg[] = "Password reset code not found, maybe already expired";
				//$error_msg[] = json_encode($collectData['local_user_properties']);
			}
		}
		if (!$error) {
			if ((int)$collectData['local_user_properties']['password_hash']->local_seq === 0) {
				$error = true;
				$error_msg[] = "local-seq from local-properties-data is 0.";
			}
		}
		if (!$error) {
			try {
				$collectData['local_users'] = $this->mod_account->get_local_user_by($collectData['local_user_properties']['password_hash']->local_seq, 'seq');
			} catch (Exception $ex) {
				$error = true;
				$error_msg[] = "Cannot check local-user by get_local_user_by on passwordNew.";
			}
		}
		if (!$error) {
			if (count((array)$collectData['local_users']) === 0) {
				$error = true;
				$error_msg[] = "Local user is not exists on system.";
			}
		}
		if (!$error) {
			if (!isset($collectData['local_users']->seq)) {
				$error = true;
				$error_msg[] = "Un-expected data index from models by get local-user-data by sequence.";
			}
		}
		# get local-properties-data by password-string
		if (!$error) {
			$query_params['account_password_salt'] = $this->rc4crypt->DecryptRC4($input_params['account_activation_code']);
			try {
				$collectData['local_user_properties']['password_string'] = $this->mod_account->get_local_user_properties_by($collectData['local_users']->seq, $input_params['account_activation_code'], 'password-string');
			} catch (Exception $ex) {
				$error = true;
				$error_msg[] = "Cannot get user properties by password-string on passwordNew: {$ex->getMessage()}";
			}
		}
		if (!$error) {
			if (!isset($collectData['local_user_properties']['password_string']->properties_value)) {
				$error = true;
				$error_msg[] = "local-user properties data no as expected index format by password-string on passwordNew";
			}
		}
		if (!$error) {
			try {
				$collectData['local_user_properties']['password_decrypt'] = $this->rc4crypt->DecryptRC4($input_params['account_activation_code']);
			} catch (Exception $ex) {
				$error = true;
				$error_msg[] = "Error exception while decrypt password code: {$ex->getMessage()}.";
			}
		}
		if (!$error) {
			if ($collectData['local_user_properties']['password_string']->properties_value !== $collectData['local_user_properties']['password_decrypt']) {
				$error = true;
				$error_msg[] = "Password string from database should be same with decrypt from input code.";
			} else {
				try {
					$collectData['local_user_properties']['password_decrypt_items'] = explode("|", $collectData['local_user_properties']['password_decrypt']);
				} catch (Exception $ex) {
					$error = true;
					$error_msg[] = "Error exception while exolode password: {$ex->getMessage()}.";
				}
			}
		}
		if (!$error) {
			if (!isset($collectData['local_user_properties']['password_decrypt_items'][0]) && !isset($collectData['local_user_properties']['password_decrypt_items'][1])) {
				$error = true;
				$error_msg[] = "Explode password not containing password hashes or password rijandel encrypt";
			} else {
				try {
					$collectData['local_user_properties']['password_rijandel'] = $this->rijandelcrypt->decrypt($collectData['local_user_properties']['password_decrypt_items'][0]);
				} catch (Exception $ex) {
					$error = true;
					$error_msg[] = "Decrypt by rijandel returning error: {$ex->getMessage()}.";
				}
			}
		}
		if (!$error) {
			if ($collectData['local_user_properties']['password_rijandel'] !== $collectData['local_user_properties']['password_decrypt_items'][1]) {
				$error = true;
				$error_msg[] = "Hashing password string should be same with password hash by salt.";
			}
		}
		/*
		echo "<pre>";
		if (!$error) {
			print_r($collectData);
			echo "<hr/>";
			print_r($query_params);
		} else {
			print_r($error_msg);
			echo "<hr/>";
			print_r($collectData);
			echo "<hr/>";
			print_r($query_params);
		}
		exit;
		*/
		
		//---------------------------------------------------------------------------------------------------
		if (!$error) {
			$query_params['account_salt_new'] = $collectData['local_user_properties']['password_decrypt_items'][0];
			$query_params['account_hash'] = $this->rc4crypt->bEncryptRC4($query_params['account_salt_new']);
			$query_params['account_password_new'] = $collectData['local_user_properties']['password_decrypt_items'][1];
			// set userdata of new password
			$this->session->set_userdata('tmp_password', $query_params['account_password_new']);
			$query_params['account_password_string'] = "{$query_params['account_salt_new']}|{$query_params['account_password_new']}";
			$query_params['account_password'] = sha1($query_params['account_password_string']);
			//----
			$query_params['account_edited_datetime'] = $this->DateObject->format('Y-m-d H:i:s');
			$query_params['account_edited_by'] = $collectData['local_users']->account_username;
			$query_params['account_inserting_remark'] = "New request password confirmed.";
			//----
			try {
				$collectData['password_hash_seq'] = $this->mod_account->set_local_user_properties_by($collectData['local_users']->seq, $query_params['account_password'], 'password-hash');
				$collectData['password_string_seq'] = $this->mod_account->set_local_user_properties_by($collectData['local_users']->seq, $query_params['account_password_string'], 'password-string');
			} catch (Exception $ex) {
				$error = true;
				$error_msg[] = "Cannot re-set local-user-propeties with key password-hash and password-string: {$ex->getMessage()}";
			}
		}
		//----------------------------------------------------------------------------------------------------
		if (!$error) {
			if (isset($query_params['account_password_salt'])) {
				unset($query_params['account_password_salt']);
			}
			if (isset($query_params['account_salt_new'])) {
				unset($query_params['account_salt_new']);
			}
			if (isset($query_params['account_password_new'])) {
				unset($query_params['account_password_new']);
			}
			if (isset($query_params['account_password_string'])) {
				unset($query_params['account_password_string']);
			}
			$this->mod_account->update_local_account_data($collectData['local_users']->seq, $query_params);
			//----
			// Local login
			//----
			$collectData['login_params'] = array(
				'body'			=> array(
					'user_email'		=> $collectData['local_users']->account_email,
					'user_username'		=> $collectData['local_users']->account_username,
					'user_password'		=> $this->session->userdata('tmp_password'),
				),
			);
			try {
				$collectData['local_login'] = $this->mod_account->local_login($collectData['login_params']);
			} catch (Exception $ex) {
				$error = true;
				$error_msg[] = "Cannot doing local-login using temporary passwords.";
			}
		}
		if (!$error) {
			if (isset($collectData['local_login']['success'])) {
				if ($collectData['local_login']['success'] != TRUE) {
					$error = true;
					$error_msg[] = "Local login failed.";
				}
			} else {
				$error = true;
				$error_msg[] = "Local login by succees failed.";
			}
		}
		//---- Redirect back to dashboard
		if (!$error) {
			$this->session->set_flashdata('error', FALSE);
			$this->session->set_flashdata('action_message', 'Reset password success, please change your password with your new password.');
			
			header('Location: ' . base_url("{$collectData['base_path']}/account/passwordChange"));
		} else {
			$this->session->set_flashdata('error', TRUE);
			if ($this->session->userdata('tmp_password') != FALSE) {
				$this->session->unset_userdata('tmp_password');
			}
			$error_to_show = "";
			foreach ($error_msg as $keval) {
				$error_to_show .= "- {$keval}<br/>\n";
			}
			$this->session->set_flashdata('action_message', $error_to_show);
			header('Location: ' . base_url("{$collectData['base_path']}/account/passwordForget"));
		}
		exit;
	}
	function passwordChange() {
		$error = false;
		$error_msg = [];
		$collectData = array();
		$collectData['page'] = 'form-password';
		$collectData['base_path'] = $this->imzers->base_path;
		$collectData['collect'] = array();
		//----
		if ($this->session->userdata('social_account_login_seq') != FALSE) {
			if ($this->session->userdata('tmp_password') != FALSE) {
				$collectData['collect']['ggdata'] = $this->authentication->userdata;
				$collectData['collect']['userdata'] = $this->authentication->localdata;
			} else {
				header('Location: ' . base_url("{$collectData['base_path']}/dashboard/index"));
				exit;
			}
		} else {
			//header('Location: ' . base_url("{$collectData['base_path']}/account/login"));
			echo "rr";
			exit;
		}
		//----
		$query_params = array();
		$user_params = array();
		$user_params['body'] = array(
			'user_password_current' => ($this->session->userdata('tmp_password') != FALSE) ? $this->session->userdata('tmp_password') : '',
			'user_password_new' => (isset($this->imzcustom->php_input_request['body']['user_password_new']) ? $this->imzcustom->php_input_request['body']['user_password_new'] : ''),
			'user_password_confirm' => (isset($this->imzcustom->php_input_request['body']['user_password_confirm']) ? $this->imzcustom->php_input_request['body']['user_password_confirm'] : ''),
		);
		$form_validation = TRUE;
		if (strlen($user_params['body']['user_password_current']) === 0) {
			$form_validation = FALSE;
			$error_msg[] = "Input current password cannot be empty.";
		}
		if (strlen($user_params['body']['user_password_new']) === 0) {
			$form_validation = FALSE;
			$error_msg[] = "Input new password cannot be empty.";
		}
		if (strlen($user_params['body']['user_password_new']) > 64) {
			$form_validation = FALSE;
			$error_msg[] = "Input new password maximum is 64 length.";
		}
		if ($user_params['body']['user_password_new'] !== $user_params['body']['user_password_confirm']) {
			$form_validation = FALSE;
			$error_msg[] = "New password and Confirm password should be same.";
		}
		if(!$form_validation) {
			$this->session->set_flashdata('error', TRUE);
			$error_string = "";
			if (count($error_msg) > 0) {
				foreach ($error_msg as $errorVal) {
					$error_string .= "- {$errorVal}<br/>\n";
				}
			}
			$this->session->set_flashdata('action_message', $error_string);
			//header('Location: ' . base_url("{$this->imzers->base_path}/account/passwordChange"));
			$this->passwordLoad();
		} else {
			$input_params = array(
				'user_password_current'	=> $this->imzers->safe_text_post($user_params['body']['user_password_current'], 64),
				'user_password_new'	=> $user_params['body']['user_password_new'],
				'user_password_confirm'	=> $user_params['body']['user_password_confirm'],
			);
			if (!$error) {
				if ($input_params['user_password_new'] !== $input_params['user_password_confirm']) {
					$error = true;
					$error_msg[] = "New passoword and confirm password should be match.";
				}
			}
			if (!$error) {
				$query_params['current_password'] = (strlen($input_params['user_password_current']) ? $input_params['user_password_current'] : '');
				$query_params['update_password'] = (strlen($input_params['user_password_new']) ? $input_params['user_password_new'] : '');
				try {
					$query_params['account_salt'] = $this->rc4crypt->bDecryptRC4($this->authentication->localdata['account_hash']);
				} catch (Exception $ex) {
					$error = true;
					$error_msg[] = "Cannot decrypt of account-hash by rc4: {$ex->getMessage()}";
				}
			}
			if (!$error) {
				$query_params['account_password_string'] = "{$query_params['account_salt']}|{$query_params['current_password']}";
				if (trim(strtolower(sha1($query_params['account_password_string']))) !== trim(strtolower($this->authentication->localdata['account_password']))) {
					$error = true;
					$error_msg[] = "Current password not match";
				}
			}
			if (!$error) {
				$query_params['account_hash'] = $this->rc4crypt->bEncryptRC4($query_params['account_salt']);
				$query_params['account_password_string'] = "{$query_params['account_salt']}|{$query_params['update_password']}";
				$query_params['account_password'] = sha1($query_params['account_password_string']);
				if (isset($query_params['account_password_string'])) {
					unset($query_params['account_password_string']);
				}
				if (isset($query_params['current_password'])) {
					unset($query_params['current_password']);
				}
				if (isset($query_params['update_password'])) {
					unset($query_params['update_password']);
				}
				if (isset($query_params['account_salt'])) {
					unset($query_params['account_salt']);
				}
			}
			if (!$error) {
				try {
					$seq = $this->mod_account->edit_user($this->authentication->localdata['seq'], $query_params);
				} catch (Exception $ex) {
					$error = true;
					$error_msg[] = "Cannot update password of user: {$ex->getMessage()}.";
				}
			}
			if (!$error) {
				// Reset if have tmp_password
				if ($this->session->userdata('tmp_password') != FALSE) {
					$this->session->unset_userdata('tmp_password');
				}
				
				$this->session->set_flashdata('error', FALSE);
				$this->session->set_flashdata('action_message', 'Password already changed.');
			} else {
				$this->session->set_flashdata('error', true);
				$error_string = "";
				foreach ($error_msg as $keval) {
					$error_string .= $keval . "<br/>\n";
				}
				$this->session->set_flashdata('action_message', $error_string);
			}
			
			if (!$error) {
				if (isset($this->imzers->userdata)) {
					unset($this->imzers->userdata);
				}
				if (isset($this->imzers->localdata)) {
					unset($this->imzers->localdata);
				}
				$this->session->unset_userdata('social_account_login_seq');
				header("Location: " . base_url($collectData['base_path'] . '/account/login'));
			} else {
				header("Location: " . base_url($collectData['base_path'] . '/account/passwordChange'));
			}
			exit;
		}
	}
	private function passwordLoad() {
		$error = false;
		$error_msg = [];
		$collectData = array();
		$collectData['page'] = 'form-password-change';
		$collectData['base_path'] = $this->imzers->base_path;
		$collectData['collect'] = array();
		//----
		if ($this->session->userdata('social_account_login_seq') != FALSE) {
			$collectData['collect']['ggdata'] = $this->authentication->userdata;
			$collectData['collect']['userdata'] = $this->authentication->localdata;
			$collectData['collect']['address-province'] = $this->mod_account->get_province(360);
			$collectData['collect']['roles'] = $this->mod_account->get_dashboard_roles();
			$this->load->view('dashboard/form.php', $collectData);
		} else {
			header('Location: ' . base_url("{$collectData['base_path']}/account/login"));
			exit;
		}
		//----
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	public function __destruct() {
		$this->session->unset_userdata('action_message');
	}
	
}

