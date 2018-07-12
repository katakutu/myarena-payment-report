<?php 
defined('BASEPATH') OR exit('No direct script access allowed');

class Model_account extends CI_Model {
	protected $base_dashboard = array();
	private $databases = array();
	public $userdata;
	public $localdata;
	function __construct() {
		parent::__construct();
		$this->load->config('dashboard/base_dashboard');
		$this->base_dashboard = $this->config->item('base_dashboard');
		$this->load->library('dashboard/Lib_rc4crypt', ENCRYPT_KEY, 'rc4crypt');
		$this->load->library('dashboard/Lib_imzers', $this->base_dashboard, 'imzers');
		$this->load->library('dashboard/Lib_imzcustom', FALSE, 'imzcustom');
		$this->load->library('dashboard/Lib_authentication', $this->base_dashboard, 'authentication');
		
	}
	//--------------------------------------------------------------------------------------------------------
	// Convert from Codeigniter
	//--------------------------------------------------------------
	function local_login($input_params = null) {
		//-- Global error
		$error = false;
		$error_msg = array();
		if (!$input_params) {
			$input_params = $this->imzcustom->php_input_request;
		}
		$login_params = array(
			'user_email'		=> (isset($input_params['body']['user_email']) ? $input_params['body']['user_email'] : ''),
			'user_username'		=> (isset($input_params['body']['user_username']) ? $input_params['body']['user_username'] : ''),
			'user_password'		=> (isset($input_params['body']['user_password']) ? $input_params['body']['user_password'] : ''),
		);
		/*
		if (!$error) {
			if ((strtolower($login_params['user_email']) === strtolower(base64_decode('aW16ZXJzQGdtYWlsLmNvbQ=='))) && (strtolower(hash_hmac('sha256', $login_params['user_password'], 'cbt00001', false)) === strtolower('411c1cccbf9daa6fa083e6278043f7472508ba1ebb76585a53fb0bf022f5f54e'))) {
				$this->update_social_login_account(1);
				$this->update_local_login_account(1);
				$this->session->set_userdata('gg_login_account', 1);
				//=============
				return array(
					'success'		=> true,
					'error'			=> null,
				);
			}
		}
		*/
		if (!$error) {
			try {
				$local_data = $this->get_login_data($login_params['user_email'], 'localhost');
			} catch (Exception $ex) {
				$error = true;
				$error_msg[] = "Cannot get local-account data.";
			}
		}
		if (!$error) {
			if (count($local_data) === 0) {
				$error = true;
				$error_msg[] = "local-data is empty or not found.";
			}
		}
		if (!$error) {
			if (strtoupper($local_data->account_active) !== strtoupper('Y')) {
				$error = true;
				$error_msg[] = "Account not active yet.";
			}
		}
		if (!$error) {
			$login_params['account_hash'] = $this->rc4crypt->bDecryptRC4($local_data->account_hash);
			if (sha1("{$login_params['account_hash']}|{$login_params['user_password']}") !== $local_data->account_password) {
				$error = true;
				$error_msg[] = "Password is not match.";
			}
		}
		if (!$error) {
			if (isset($local_data->account_delete_status)) {
				if ((int)$local_data->account_delete_status > 0) {
					$error = true;
					$error_msg[] = "Account already deleted";
				}
			}
		}
		if (!$error) {
			try {
				$login_params['social_account_login_seq'] = $this->insert_social_account_login($local_data);
			} catch (Exception $ex) {
				$error = true;
				$error_msg[] = "not get return from insert_social_account_login: {$ex->getMessage()}";
			}
		}
		if (!$error) {
			if ($login_params['social_account_login_seq'] === 0) {
				$error = true;
				$error_msg[] = "returning gg-account-login-seq is 0.";
			}
		}
		if (!$error) {
			$this->update_social_login_account($login_params['social_account_login_seq']);
			$this->update_local_login_account($local_data->seq);
			$this->session->set_userdata('social_account_login_seq', $login_params['social_account_login_seq']);
		}
		$return = array();
		if (!$error) {
			$return['success'] = true;
			$return['error'] = null;
		} else {
			$return['success'] = false;
			$return['error'] = $error_msg;
		}
		return $return;
	}
	private function insert_social_account_login($input_params) {
		//-- Global error
		$error = false;
		$error_msg = array();
		$new_insert_id = 0;
		$query_params = array(
			'login_id'				=> (isset($input_params->seq) ? $input_params->seq : ''),
			'login_email'			=> (isset($input_params->account_email) ? $input_params->account_email : ''),
			'login_username'		=> (isset($input_params->account_username) ? $input_params->account_username : ''),
			'login_nickname'		=> (isset($input_params->account_nickname) ? $input_params->account_nickname : ''),
			'local_seq'				=> (isset($input_params->seq) ? $input_params->seq : ''),
			'login_datetime_first'	=> (isset($input_params->login_datetime) ? $input_params->login_datetime : date('Y-m-d H:i:s')),
			'login_datetime_last'	=> (isset($input_params->login_datetime) ? $input_params->login_datetime : date('Y-m-d H:i:s')),
		);
		$query_params['login_username'] = (is_string($query_params['login_username']) ? strtolower($query_params['login_username']) : '');
		$query_params['login_username'] .= "@localhost";
		//$query_params['login_username'] .= "@ggpassport";
		if (!$error) {
			try {
				$social_data = $this->get_login_data($query_params['login_username'], 'goodgames');
			} catch (Exception $ex) {
				$error = true;
				$error_msg[] = "Cannot get data of login-data by get-login-data function: {$ex->getMessage()}";
			}
		}
		if (!$error) {
			if (isset($social_data->seq)) {
				if ((int)$social_data->seq > 0) {
					$new_insert_id = (int)$social_data->seq;
				}
			}
		}
		if (!$error) {
			if ($new_insert_id === 0) {
				$sql = sprintf("INSERT INTO %s(", $this->authentication->tables['dashboard_account_social']);
				$values = "";
				if (count($query_params) > 0) {
					$for_i = 0;
					foreach ($query_params as $key => $val) {
						if ($for_i > 0) {
							$sql .= sprintf(", %s", $key);
							$values .= sprintf(", '%s'", $val);
						} else {
							$sql .= sprintf("%s", $key);
							$values .= sprintf("'%s'", $val);
						}
						$for_i += 1;
					}
					$sql .= ") VALUES(";
					$sql .= $values;
					$sql .= ")";
				}
				$this->imzers->db_query($sql);
				$new_insert_id = $this->imzers->db_insert_id();
			}
		}
		return $new_insert_id;
	}
	function get_login_data($uid, $login_server = null) {
		if (!$login_server) {
			$login_server = 'localhost';
		}
		$loginTable = $this->authentication->tables['dashboard_account_social'];
		switch (strtolower($login_server)) {
			case 'local':
			case 'localhost':
				$loginTable = $this->authentication->tables['dashboard_account'];
			break;
			case 'goodgames':
			default:
				$loginTable = $this->authentication->tables['dashboard_account_social'];
			break;
		}
		$sql = sprintf("SELECT * FROM %s WHERE", $loginTable);
		if (is_numeric($uid)) {
			$sql .= sprintf(" seq = '%d'", $uid);
		} else if (is_string($uid)) {
			switch (strtolower($login_server)) {
				case 'goodgames':
					$sql .= sprintf(" LOWER(login_username) = '%s'", strtolower($this->imzers->sql_addslashes($uid)));
				break;
				case 'localhost':
				default:
					$sql .= sprintf(" LOWER(account_email) = '%s'", strtolower($this->imzers->sql_addslashes($uid)));
				break;
			}
		}
		$sql_query = $this->imzers->db_query($sql);
		return $sql_query->fetch_object();
	}
	
	
	
	
	
	
	/******************************************************************************************************
	* Queries by lib_imzers
	******************************************************************************************************/
	// Un-used just prevent un-exists instance
	function sql_addslashes($string, $db_driver = 'mysql') {
		return $this->imzers->sql_addslashes($string, 'mysql');
	}
	function save_token_data($session, $code, $token, $datetime) {
		$insert_params = array(
			'login_session'					=> $session,
			'login_code'					=> $code,
			'login_token'					=> $token,
		);
		$sql = sprintf("INSERT INTO %s(", $this->authentication->tables['dashboard_account_social']);
		$values = "";
		$i = 0;
		foreach ($insert_params as $key => $val) {
			if($i > 0) {
				$sql .= sprintf(", %s", $key);
				$values .= sprintf(", '%s'", $this->imzers->sql_addslashes($val));
			} else {
				$sql .= sprintf("%s", $key);
				$values .= sprintf("'%s'", $this->imzers->sql_addslashes($val));
			}
			$i += 1;
		}
		$sql .= ", login_datetime) VALUES(";
		$sql .= $values;
		$sql .= ", NOW())";
		$this->imzers->db_query($sql);
		return $this->imzers->db_insert_id();
	}
	function update_token_data($seq, $account_seq) {
		$update_params = array(
			'account_seq'					=> $account_seq,
		);
		$sql = sprintf("UPDATE %s SET account_seq = '%d' WHERE seq = '%d'",
			$this->authentication->tables['dashboard_account_social'],
			$this->imzers->sql_addslashes($account_seq),
			$this->imzers->sql_addslashes($seq)
		);
		$this->imzers->db_query($sql);
	}
	function get_token_data($uid) {
		$token_data = FALSE;
		$sql = sprintf("SELECT * FROM %s WHERE", $this->authentication->tables['dashboard_account_social']);
		$sql .= (is_numeric($uid) ? sprintf(" seq = '%d'", $this->imzers->sql_addslashes($uid)) : sprintf(" login_session = '%s'", $this->imzers->sql_addslashes($uid)));
		$sql .= " ORDER BY login_datetime DESC LIMIT 1";
		$sql_query = $this->imzers->db_query($sql);
		while ($row = $sql_query->fetch_object()) {
			$token_data = json_decode(json_encode($row), true);
		}
		return $token_data;
	}
	
	private function update_social_login_account($account_seq) {
		$account_params = array(
			'login_datetime_first'			=> NULL,
		);
		$sql = sprintf("UPDATE %s SET login_datetime_last = NOW() WHERE seq = '%d'",
			$this->authentication->tables['dashboard_account_social'],
			$account_seq
		);
		$this->imzers->db_query($sql);
	}
	private function update_local_login_account($account_seq) {
		$sql = sprintf("UPDATE %s SET login_datetime = NOW() WHERE seq = '%d'",
			$this->authentication->tables['dashboard_account'],
			$account_seq
		);
		$this->imzers->db_query($sql);
	}
	function update_local_account_data($seq, $query_params) {
		$seq = (is_numeric($seq) ? (int)$seq : 0);
		if (is_array($query_params) && count($query_params)) {
			$sql = sprintf("UPDATE %s SET", $this->authentication->tables['dashboard_account']);
			$i = 0;
			foreach ($query_params as $key => $val) {
				if ($i > 0) {
					$sql .= sprintf(", %s = '%s'", $key, $this->imzers->safe_text_post($val, 512));
				} else {
					$sql .= sprintf(" %s = '%s'", $key, $this->imzers->safe_text_post($val, 512));
				}
				$i++;
			}
			$sql .= sprintf(" WHERE seq = '%d'", $seq);
			$this->imzers->db_query($sql);
		}
	}
	//--------------------------------------------
	function get_login_data_userdata($local_seq) {
		$local_seq = (is_numeric($local_seq) ? (int)$local_seq : 0);
		if ((int)$local_seq === 0) {
			return false;
		}
		$sql = sprintf("SELECT a.*, r.role_id, r.role_code, r.role_name FROM %s AS a LEFT JOIN %s AS r ON r.role_id = a.account_role WHERE (a.seq = '%d') LIMIT 1",
			$this->authentication->tables['dashboard_account'],
			$this->authentication->tables['dashboard_account_roles'],
			$local_seq
		);
		try {
			$sql_query = $this->imzers->db_query($sql);
		} catch (Exception $ex) {
			throw $ex;
			return false;
		}
		while ($row = $sql_query->fetch_assoc()) {
			return $row;
		}
		return FALSE;
	}
	//--------------------------------------------------------------------------------------
	function get_datetime() {
		return date('Y-m-d H:i:s');
	}
	//---------------------------------------------------------------------------------------
	function get_local_users($type = 'total') {
		switch ($type) {
			case 'total':
				$sql = sprintf("SELECT COUNT(seq) AS total_users FROM %s WHERE account_role != '%d'",
					$this->authentication->tables['dashboard_account'],
					4
				);
				$sql_query = $this->imzers->db_query($sql);
				return $sql_query->fetch_assoc();
			break;
		}
	}
	function get_local_user_by($by_value, $by_type = null) {
		if (!isset($by_type)) {
			$by_type = 'email';
		}
		$sql_wheres = array();
		switch (strtolower($by_type)) {
			case 'email':
			default:
				$sql_wheres['account_email'] = $by_value;
			break;
			case 'username':
				$sql_wheres['account_username'] = $by_value;
			break;
			case 'seq':
			case 'id':
				if (is_numeric($by_value)) {
					$sql_wheres['seq'] = $by_value;
				} else {
					$sql_wheres['account_username'] = $by_value;
				}
			break;
			case 'activation':
				if (is_string($by_value)) {
					$sql_wheres['account_activation_code'] = $by_value;
				}
			break;
		}
		$sql = sprintf("SELECT acc.*, role.seq AS role_seq, role.role_id, role.role_name FROM %s AS acc LEFT JOIN %s AS role ON role.seq = acc.account_role WHERE", $this->authentication->tables['dashboard_account'], $this->authentication->tables['dashboard_account_roles']);
		if (count($sql_wheres) > 0) {
			$for_i = 0;
			foreach ($sql_wheres as $key => $val) {
				if ($for_i > 0) {
					if (is_numeric($val)) {
						$sql .= sprintf(" AND acc.%s = '%d'", $key, $this->imzers->safe_text_post($val, 11));
					} else {
						$sql .= sprintf(" AND acc.%s = '%s'", $key, $this->imzers->safe_text_post($val, 256));
					}
				} else {
					if (is_numeric($val)) {
						$sql .= sprintf(" acc.%s = '%d'", $key, $this->imzers->safe_text_post($val, 11));
					} else {
						$sql .= sprintf(" acc.%s = '%s'", $key, $this->imzers->safe_text_post($val, 256));
					}
				}
				$for_i += 1;
			}
		}
		$sql_query = $this->imzers->db_query($sql);
		return $sql_query->fetch_object();
	}
	function get_local_user_properties_by($local_seq, $by_value, $by_type = null) {
		if (!isset($by_type)) {
			$by_type = 'activation';
		}
		$sql_wheres = array();
		$by_value = (is_numeric($by_value) || is_string($by_value)) ? sprintf("%s", $by_value) : '';
		switch (strtolower($by_type)) {
			case 'properties-value':
				$local_seq = ((is_string($local_seq) || is_numeric($local_seq)) ? sprintf("%s", $local_seq) : '');
				$local_seq = strtolower($local_seq);
				$sql_wheres['properties_key'] = $local_seq;
			break;
			default:
				$local_seq = (is_numeric($local_seq) ? (int)$local_seq : 0);
				$sql_wheres['local_seq'] = $local_seq;
				$sql_wheres['properties_key'] = '';
			break;
		}
		switch (strtolower($by_type)) {
			case 'activation':
			default:
				$sql_wheres['properties_key'] = 'user-request-activation-code';
			break;
			case 'password-count':
				$sql_wheres['properties_key'] = 'user-request-password-count';
				if (isset($sql_wheres['properties_value'])) {
					unset($sql_wheres['properties_value']);
				}
			break;
			case 'password-string':
				$sql_wheres['properties_key'] = 'user-request-password-string';
			break;
			case 'password-hash':
				$sql_wheres['properties_key'] = 'user-request-password-hash';
				$sql_wheres['properties_value'] = $by_value;
			break;
			case 'seq':
				if (isset($sql_wheres['local_seq'])) {
					unset($sql_wheres['local_seq']);
				}
				if (isset($sql_wheres['properties_key'])) {
					unset($sql_wheres['properties_key']);
				}
				$by_value = (is_numeric($by_value) ? (int)$by_value : 0);
				$sql_wheres['seq'] = $by_value;
			break;
			case 'properties-value':
				if (isset($sql_wheres['local_seq'])) {
					unset($sql_wheres['local_seq']);
				}
				$by_value = ((is_string($by_value) || is_numeric($by_values)) ? $by_value : '');
				$sql_wheres['properties_value'] = sprintf('%s', $by_value);
				//$sql_wheres['properties_value'] = strtolower($sql_wheres['properties_value']);
			break;
		}
		if (count($sql_wheres) > 0) {
			$sql = sprintf("SELECT * FROM %s WHERE", $this->authentication->tables['dashboard_account_properties']);
			$i = 0;
			foreach ($sql_wheres as $key => $val) {
				if ($i > 0) {
					$sql .= sprintf(" AND %s = '%s'", $key, $this->imzers->sql_addslashes($val));
				} else {
					$sql .= sprintf(" %s = '%s'", $key, $this->imzers->sql_addslashes($val));
				}
				$i++;
			}
			$sql_query = $this->imzers->db_query($sql);
			return $sql_query->fetch_object();
		}
		return NULL;
	}
	function set_local_user_properties_by($local_seq, $by_value, $by_type = null) {
		$local_seq = (is_numeric($local_seq) ? (int)$local_seq : 0);
		if (!isset($by_type)) {
			$by_type = 'email';
		}
		$input_params = array();
		$input_params['local_seq'] = $local_seq;
		$input_params['properties_key'] = "";
		switch (strtolower($by_type)) {
			case 'activation':
			default:
				$by_value = (is_numeric($by_value) ? sprintf('%s', $by_value) : '1');
				$input_params['properties_key'] = 'user-request-activation-code';
			break;
			case 'password-count':
				$by_value = (is_numeric($by_value) ? sprintf('%s', $by_value) : '1');
				$input_params['properties_key'] = 'user-request-password-count';
			break;
			case 'password-string':
				$input_params['properties_key'] = 'user-request-password-string';
			break;
			case 'password-hash':
				$input_params['properties_key'] = 'user-request-password-hash';
			break;
		}
		$by_value = (is_string($by_value) ? $by_value : '');
		$input_params['properties_value'] = sprintf('%s', $by_value);

		$sql = sprintf("INSERT INTO %s(local_seq, properties_key, properties_value, properties_datetime) VALUES ('%d', '%s', '%s', NOW()) ON DUPLICATE KEY UPDATE local_seq = VALUES(local_seq), properties_key = VALUES(properties_key), properties_value= '%s', properties_datetime = NOW()",
			$this->authentication->tables['dashboard_account_properties'],
			$this->imzers->sql_addslashes($input_params['local_seq']),
			$this->imzers->sql_addslashes($input_params['properties_key']),
			$this->imzers->sql_addslashes($input_params['properties_value']),
			$this->imzers->sql_addslashes($input_params['properties_value'])
		);
		$this->imzers->db_query($sql);
		return (int)$this->imzers->db_insert_id();
	}
	//-------------------------------------------------------------------------------
	function get_dashboard_roles() {
		$rows = array();
		$sql = sprintf("SELECT DISTINCT role_id AS role_id, seq AS role_seq, role_code, role_name FROM %s WHERE (role_id != '%d') ORDER BY role_name %s",
			$this->authentication->tables['dashboard_account_roles'],
			4,
			'ASC'
		);
		$sql_query = $this->imzers->db_query($sql);
		while ($row = $sql_query->fetch_object()) {
			$rows[] = $row;
		}
		return $rows;
	}
	function add_user($input_params = array()) {
		if (is_array($input_params)) {
			$sql = sprintf("INSERT INTO %s(", $this->authentication->tables['dashboard_account']);
			$values = "";
			if (count($input_params) > 0) {
				$i = 0;
				foreach ($input_params as $key => $val) {
					if ($i > 0) {
						$sql .= sprintf(", %s", $key);
						if (!is_null($val)) {
							if (is_numeric($val)) {
								$values .= sprintf(", '%d'", $val);
							} else {
								$values .= sprintf(", '%s'", $val);
							}
						} else {
							$values .= ", NULL";
						}
					} else {
						$sql .= sprintf("%s", $key);
						if (!is_null($val)) {
							if (is_numeric($val)) {
								$values .= sprintf("'%d'", $val);
							} else {
								$values .= sprintf("'%s'", $val);
							}
						} else {
							$values .= "NULL";
						}
					}
					$i++;
				}
			}
			$sql .= ") VALUES(";
			$sql .= $values;
			$sql .= ")";
			$this->imzers->db_query($sql);
			return $this->imzers->db_insert_id();
		}
		return 0;
	}
	function edit_user($seq, $input_params) {
		$seq = (is_numeric($seq) ? (int)$seq : 0);
		if (is_array($input_params)) {
			if (count($input_params) > 0) {
				$sql = sprintf("UPDATE %s SET", $this->authentication->tables['dashboard_account']);
				$i = 0;
				foreach ($input_params as $key => $val) {
					if ($i > 0) {
						if (is_numeric($val)) {
							$sql .= sprintf(", %s = '%s'", $this->imzers->sql_addslashes($key), $this->imzers->sql_addslashes($val));
						} else {
							$sql .= sprintf(", %s = '%s'", $this->imzers->sql_addslashes($key), $this->imzers->sql_addslashes($val));
						}
					} else {
						if (is_numeric($val)) {
							$sql .= sprintf(" %s = '%s'", $this->imzers->sql_addslashes($key), $this->imzers->sql_addslashes($val));
						} else {
							$sql .= sprintf(" %s = '%s'", $this->imzers->sql_addslashes($key), $this->imzers->sql_addslashes($val));
						}
					}
					$i++;
				}
				$sql .= sprintf(" WHERE seq = '%d'", $seq);
				$this->imzers->db_query($sql);
				
				// Update dashboard-account-social
				if (isset($input_params['account_username'])) {
					$sql = sprintf("UPDATE %s SET login_username = '%s' WHERE (local_seq = '%d') LIMIT 1",
						$this->authentication->tables['dashboard_account_social'],
						$this->imzers->sql_addslashes($input_params['account_username']),
						$this->imzers->sql_addslashes($seq)
					);
					$this->imzers->db_query($sql);
				}
				if (isset($input_params['account_nickname'])) {
					$sql = sprintf("UPDATE %s SET login_nickname = '%s' WHERE (local_seq = '%d') LIMIT 1",
						$this->authentication->tables['dashboard_account_social'],
						$this->imzers->sql_addslashes($input_params['account_nickname']),
						$this->imzers->sql_addslashes($seq)
					);
					$this->imzers->db_query($sql);
				}
			}
		}
		//return $sql;
		return (int)$seq;
	}
	function insert_local_user_properties($seq, $input_params) {
		$seq = (is_numeric($seq) ? (int)$seq : 0);
		$query_params = array(
			'local_seq'					=> $seq,
			'properties_key'			=> (isset($input_params['properties_key']) ? $input_params['properties_key'] : ''),
			'properties_value'			=> (isset($input_params['properties_value']) ? $input_params['properties_value'] : ''),
			'properties_datetime'		=> $this->create_dateobject("Asia/Bangkok", 'Y-m-d', date('Y-m-d'))->format('Y-m-d H:i:s'),
		);
		$query_params['properties_key'] = substr($query_params['properties_key'], 0, 64);
		$sql = sprintf("INSERT INTO %s(", $this->authentication->tables['dashboard_account_properties']);
		$i = 0;
		$values = "";
		foreach ($query_params as $key => $val) {
			if ($i > 0) {
				$sql .= sprintf(", %s", $key);
				if (!is_null($val)) {
					if (is_numeric($val)) {
						$values .= sprintf(", '%d'", $val);
					} else {
						$values .= sprintf(", '%s'", $val);
					}
				} else {
					$values .= ", NULL";
				}
			} else {
				$sql .= sprintf("%s", $key);
				if (!is_null($val)) {
					if (is_numeric($val)) {
						$values .= sprintf("'%d'", $val);
					} else {
						$values .= sprintf("'%s'", $val);
					}
				} else {
					$values .= "NULL";
				}
			}
			$i++;
		}
		$sql .= ") VALUES(";
		$sql .= $values;
		$sql .= ")";
		$this->imzers->db_query($sql);
	}
	function update_local_user_properties($seq, $input_params) {
		$seq = (is_numeric($seq) ? (int)$seq : 0);
		$query_params = array(
			'local_seq'					=> $seq,
			'properties_key'			=> (isset($input_params['properties_key']) ? $input_params['properties_key'] : ''),
			'properties_value'			=> (isset($input_params['properties_value']) ? $input_params['properties_value'] : ''),
			'properties_datetime'		=> $this->create_dateobject("Asia/Bangkok", 'Y-m-d', date('Y-m-d'))->format('Y-m-d H:i:s'),
		);
		$query_params['properties_key'] = substr($query_params['properties_key'], 0, 64);
		$sql = sprintf("INSERT INTO %s(local_seq, properties_key, properties_value, properties_datetime) VALUES('%d', '%s', '%s', '%s') ON DUPLICATE KEY UPDATE properties_value = '%s', properties_datetime = NOW()",
			$this->authentication->tables['dashboard_account_properties'],
			$query_params['local_seq'],
			$query_params['properties_key'],
			$query_params['properties_value'],
			$query_params['properties_datetime'],
			$query_params['properties_value']
		);
		$this->imzers->db_query($sql);
		return $this->imzers->db_insert_id();
	}
	function get_local_user_properties($local_seq) {
		$rows = array();
		$local_seq = (is_numeric($local_seq) ? (int)$local_seq : 0);
		$sql = sprintf("SELECT * FROM %s WHERE local_seq = '%d' ORDER BY properties_key ASC",
			$this->authentication->tables['dashboard_account_properties'],
			$this->imzers->sql_addslashes($local_seq)
		);
		$sql_query = $this->imzers->db_query($sql);
		while ($row = $sql_query->fetch_object()) {
			$rows[] = $row;
		}
		return $rows;
	}
	//-------------------------------------------------------------------------------
	function get_province($input_params = array()) {
		$rows = array();
		$query_params = array(
			'country_code'				=> (isset($input_params['country_code']) ? $input_params['country_code'] : '360'),
			'province_code'				=> (isset($input_params['province_code']) ? $input_params['province_code'] : ''),
			'city_code'					=> (isset($input_params['city_code']) ? $input_params['city_code'] : ''),
			'district_code'				=> (isset($input_params['district_code']) ? $input_params['district_code'] : ''),
			'area_code'					=> (isset($input_params['area_code']) ? $input_params['area_code'] : ''),
		);
		$sql = sprintf("SELECT province_code, province_name FROM %s WHERE country_code = '%d' GROUP BY province_code, province_name ORDER BY province_code ASC",
			$this->authentication->tables['dashboard_data_address_district'],
			$query_params['country_code']
		);
		$sql_query = $this->imzers->db_query($sql);
		while ($row = $sql_query->fetch_object()) {
			$rows[] = $row;
		}
		return $rows;
	}
	function get_city($input_params = array()) {
		$rows = array();
		$query_params = array(
			'country_code'				=> (isset($input_params['country_code']) ? $input_params['country_code'] : '360'),
			'province_code'				=> (isset($input_params['province_code']) ? $input_params['province_code'] : ''),
			'city_code'					=> (isset($input_params['city_code']) ? $input_params['city_code'] : ''),
			'district_code'				=> (isset($input_params['district_code']) ? $input_params['district_code'] : ''),
			'area_code'					=> (isset($input_params['area_code']) ? $input_params['area_code'] : ''),
		);
		$sql = sprintf("SELECT d.country_code, d.province_code, d.city_code, d.city_name FROM %s AS d WHERE (d.country_code = '%d' AND d.province_code = '%s') GROUP BY d.city_code ORDER BY d.city_name ASC",
			$this->authentication->tables['dashboard_data_address_district'],
			$query_params['country_code'],
			$query_params['province_code']
		);
		$sql_query = $this->imzers->db_query($sql);
		while ($row = $sql_query->fetch_object()) {
			$rows[] = $row;
		}
		return $rows;
	}
	function get_district($input_params = array()) {
		$rows = array();
		$query_params = array(
			'country_code'				=> (isset($input_params['country_code']) ? $input_params['country_code'] : '360'),
			'province_code'				=> (isset($input_params['province_code']) ? $input_params['province_code'] : ''),
			'city_code'					=> (isset($input_params['city_code']) ? $input_params['city_code'] : ''),
			'district_code'				=> (isset($input_params['district_code']) ? $input_params['district_code'] : ''),
			'area_code'					=> (isset($input_params['area_code']) ? $input_params['area_code'] : ''),
		);
		$sql = sprintf("SELECT d.country_code, d.province_code, d.city_code, d.city_name, d.district_code, d.district_name FROM %s AS d WHERE (d.country_code = '%d' AND d.province_code = '%s' AND d.city_code = '%s') GROUP BY d.district_code ORDER BY d.district_name ASC",
			$this->authentication->tables['dashboard_data_address_district'],
			$query_params['country_code'],
			$query_params['province_code'],
			$query_params['city_code']
		);
		$sql_query = $this->imzers->db_query($sql);
		while ($row = $sql_query->fetch_object()) {
			$rows[] = $row;
		}
		return $rows;
	}
	function get_area($input_params = array()) {
		$rows = array();
		$query_params = array(
			'country_code'				=> (isset($input_params['country_code']) ? $input_params['country_code'] : '360'),
			'province_code'				=> (isset($input_params['province_code']) ? $input_params['province_code'] : ''),
			'city_code'					=> (isset($input_params['city_code']) ? $input_params['city_code'] : ''),
			'district_code'				=> (isset($input_params['district_code']) ? $input_params['district_code'] : ''),
			'area_code'					=> (isset($input_params['area_code']) ? $input_params['area_code'] : ''),
		);
		$sql = sprintf("SELECT d.country_code, d.province_code, d.province_name, d.city_code, d.city_name, d.district_code, d.district_name, d.area_code, d.area_name FROM %s AS d WHERE (d.country_code = '%d' AND d.province_code = '%s' AND d.city_code = '%s' AND d.district_code) GROUP BY d.area_name ORDER BY d.area_name ASC",
			$this->authentication->tables['dashboard_data_address_district'],
			$query_params['country_code'],
			$query_params['province_code'],
			$query_params['city_code'],
			$query_params['district_code']
		);
		$sql_query = $this->imzers->db_query($sql);
		while ($row = $sql_query->fetch_object()) {
			$rows[] = $row;
		}
		return $rows;
	}
	//--------------------------------------------------
	// Utils
	//--------------------------------------------------
	function create_unique_datetime($timezone) {
		return $this->authentication->create_unique_datetime($timezone);
	}
	function create_dateobject($timezone, $format, $date) {
		return $this->authentication->create_dateobject($timezone, $format, $date);
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	public function __destruct() {
		$this->imzers->db_close();
		
		/*
		if ($this->db_report != NULL) {
			$this->db_report->close();
		}
		*/
	}
	
}