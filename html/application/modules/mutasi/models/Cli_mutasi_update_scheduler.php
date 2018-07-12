<?php
if ( ! defined('BASEPATH')) { exit('No direct script access allowed: ' . (__FILE__)); }
class Cli_mutasi_update_scheduler extends CI_Model {
	protected $db_mutasi;
	protected $base_dashboard, $base_mutasi = array(), $base_suksesbugil;
	protected $mutasi_tables = array();
	function __construct() {
		parent::__construct();
		$this->load->config('mutasi/base_mutasi');
		$this->base_mutasi = $this->config->item('base_mutasi');
		$this->load->library('dashboard/Lib_imzers', $this->base_mutasi, 'imzers');
		$this->db_mutasi = $this->load->database('mutasi', TRUE);
		$this->mutasi_tables = (isset($this->base_mutasi['mutasi_tables']) ? $this->base_mutasi['mutasi_tables'] : array());
		# Load Configs
		$this->load->config('mutasi/base_suksesbugil');
		$this->base_suksesbugil = $this->config->item('base_suksesbugil');
		# Load Suksesbugil Lib as sb_approve
		$this->load->library('mutasi/Lib_suksesbugil_approve', FALSE, 'sb_approve');
	}
	function get_bank_account_with_status($is_active = 0) {
		$is_active = (is_numeric($is_active) ? $is_active : 0);
		$this->db_mutasi->select('a.*, r.rekening_number, r.rekening_name');
		$this->db_mutasi->from("{$this->mutasi_tables['bank_account']} a");
		$this->db_mutasi->join("{$this->mutasi_tables['bank_rekening']} r", 'r.account_seq = a.seq', 'left');
		if ($is_active > 0) {
			$this->db_mutasi->where('a.account_is_active', 'Y');
		}
		$sql_query = $this->db_mutasi->get();
		return $sql_query;
	}
	function get_bank_account_with_bankcode_status($bank_code, $is_active = 0) {
		$bank_code = (is_string($bank_code) ? strtolower($bank_code) : '');
		$is_active = (is_numeric($is_active) ? $is_active : 0);
		$this->db_mutasi->select('a.*, b.seq AS b_bank_seq, b.bank_code AS b_bank_code, r.rekening_number, r.rekening_name');
		$this->db_mutasi->from("{$this->mutasi_tables['bank_account']} a");
		$this->db_mutasi->join("{$this->mutasi_tables['bank']} b", 'b.seq = a.bank_seq', 'inner');
		$this->db_mutasi->join("{$this->mutasi_tables['bank_rekening']} r", 'r.account_seq = a.seq', 'left');
		if (strlen($bank_code) > 0) {
			$this->db_mutasi->where('b.bank_code', $bank_code);
		}
		if ($is_active > 0) {
			$this->db_mutasi->where('a.account_is_active', 'Y');
		}
		$sql_query = $this->db_mutasi->get();
		return $sql_query;
	}
	//========================
	
	function get_setting_by_code($setting_code = '') {
		$this->db_mutasi->select('setting_value')->from($this->mutasi_tables['setting'])->where('setting_code', $setting_code);
		return $this->db_mutasi->get()->row();
	}
	
	// Mutasi
	function get_bank_account_by_bankseq_and_active($bank_seq, $is_active = 0) {
		$is_active = (int)$is_active;
		$this->db_mutasi->where('bank_seq', $bank_seq);
		if ($is_active > 0) {
			$this->db_mutasi->where('account_is_active', 'Y');
		}
		$sql_query = $this->db_mutasi->get($this->mutasi_tables['bank_account']);
		return $sql_query->result();
	}
	function get_mb_transaction_incoming($bank_seq, $account_seq, $datetime, $params, $status = 0, $interval = 0, $bank_code = 'all') {
		$dateobject = new DateTime($datetime);
		$sql = sprintf("SELECT * FROM %s WHERE (bank_seq = '%d' AND account_seq = '%d')", 
			$this->mutasi_tables['rekening_transaction'],
			$this->db_mutasi->escape_str($bank_seq),
			$this->db_mutasi->escape_str($account_seq)
		);
		$interval = (int)$interval;
		$status = (int)$status;
		if ($interval > 0) {
			$datereduce = new DateTime($datetime);
			$datereduce->sub(new DateInterval("P{$interval}D"));
			$sql .= sprintf(" AND (transaction_insert_date BETWEEN '%s' AND '%s')", $this->db_mutasi->escape_str($datereduce->format('Y-m-d')), $this->db_mutasi->escape_str($dateobject->format('Y-m-d')));
		} else {
			$sql .= sprintf(" AND (transaction_insert_date = '%s')", $this->db_mutasi->escape_str($dateobject->format('Y-m-d')));
		}
		if ($status === 0) {
			$sql .= " AND (transaction_action_status IN('new', 'update'))";
		} else {
			switch ($status) {
				case 1:
					$sql .= " AND (transaction_action_status = 'approve')";
				break;
				case 2:
				default:
					$sql .= " AND (transaction_action_status = 'delete')";
				break;
				case 3:
					$sql .= " AND (transaction_action_status = 'already')";
				break;
			}
		}
		$sql .= sprintf(" AND (is_approved = '%s' AND is_deleted = '%s')", 'N', 'N');
		if (isset($params->transaction_amount)) {
			$sql .= sprintf(" AND (transaction_amount = '%s')", $this->db_mutasi->escape_str($params->transaction_amount));
		}
		$sql_likes = "";
		$bank_code = (is_string($bank_code) ? strtolower($bank_code) : 'all');
		switch ($bank_code) {
			case 'mandiri':
				if (isset($params->transaction_from_acc_name) && isset($params->transaction_from_acc_rekening)) {
					$params->transaction_from_acc_name = substr($params->transaction_from_acc_name, 0, 18);
					$transaction_from_acc_name = explode("-", base_permalink($params->transaction_from_acc_name));
					$params->transaction_from_acc_rekening = trim($params->transaction_from_acc_rekening);
					$transaction_from_acc_rekening = explode("-", base_permalink($params->transaction_from_acc_rekening));
					$sql_likes .= " AND (";
					if (count($transaction_from_acc_name) > 0) {
						$for_i = 0;
						$sql_likes .= "(";
						foreach ($transaction_from_acc_name as $nameval) {
							// Only 2 words of rekening acc name
							if ($for_i < 2) {
								if ($for_i === 0) {
									$sql_likes .= "(CONCAT('', LOWER(transaction_description), '') LIKE '%{$this->db_mutasi->escape_str($nameval)}%')";
								} else {
									$sql_likes .= " AND (CONCAT('', LOWER(transaction_description), '') LIKE '%{$this->db_mutasi->escape_str($nameval)}%')";
								}
							}
							$for_i++;
						}
						$sql_likes .= ")";
					}
					if (count($transaction_from_acc_rekening) > 0) {
						$for_i = 0;
						$sql_likes .= " OR (";
						foreach ($transaction_from_acc_rekening as $rekval) {
							// Only 1 words of rekening number
							if ($for_i < 1) {
								if ($for_i === 0) {
									$sql_likes .= "(CONCAT('', LOWER(transaction_description), '') LIKE '%{$this->db_mutasi->escape_str($rekval)}%')";
								} else {
									$sql_likes .= " AND (CONCAT('', LOWER(transaction_description), '') LIKE '%{$this->db_mutasi->escape_str($rekval)}%')";
								}
							}
							$for_i++;
						}
						$sql_likes .= ")";
					}
					$sql_likes .= " AND (1 = 1)";
					$sql_likes .= ")";
					$sql .= $sql_likes;
				}
			break;
			case 'all':
			case 'bca':
			case 'bni':
			case 'bri':
			default:
				if (isset($params->transaction_from_acc_name)) {
					$params->transaction_from_acc_name = substr($params->transaction_from_acc_name, 0, 18);
					$transaction_from_acc_name = explode("-", base_permalink($params->transaction_from_acc_name));
					if (count($transaction_from_acc_name) > 0) {
						$sql_likes .= " AND (";
						$for_i = 0;
						foreach ($transaction_from_acc_name as $nameval) {
							// Only 2 words of rekening acc name
							if ($for_i < 2) {
								if ($for_i === 0) {
									$sql_likes .= "(CONCAT('', LOWER(transaction_description), '') LIKE '%{$this->db_mutasi->escape_str($nameval)}%')";
								} else {
									$sql_likes .= " AND (CONCAT('', LOWER(transaction_description), '') LIKE '%{$this->db_mutasi->escape_str($nameval)}%')";
								}
							}
							$for_i++;
						}
						$sql_likes .= ")";
						$sql .= $sql_likes;
					}
				}
			break;
		}
			
		
		$sql .= " AND ((CONCAT('', transaction_remark_date, '') LIKE '%{$this->db_mutasi->escape_str($dateobject->format('m'))}%') AND (CONCAT('', transaction_remark_date, '') LIKE '%{$this->db_mutasi->escape_str($dateobject->format('d'))}%'))";
		$sql .= " ORDER BY transaction_from_mutasi_position ASC";
		$sql .= " LIMIT 1";
		//=============== Executing ===============
		try {
			$sql_query = $this->db_mutasi->query($sql);
		} catch (Exception $ex) {
			throw $ex;
			return FALSE;
		}
		return $sql_query->row();
	}
	function update_mb_transaction($seq, $query_params) {
		$this->db_mutasi->where('seq', $seq);
		$this->db_mutasi->update($this->mutasi_tables['rekening_transaction'], $query_params);
		return $this->db_mutasi->affected_rows();
	}
	
	
	
	
	// Suksesbugil
	function get_sb_deposit_data($bank_seq, $account_seq, $status, $datetime, $interval_day_amount = 0) {
		$dateobject = new DateTime($datetime);
		$this->db_mutasi->select('*')->from($this->mutasi_tables['sb_transaction']);
		$this->db_mutasi->where('mutasi_bank_seq', $bank_seq);
		$this->db_mutasi->where('mutasi_bank_account_seq', $account_seq);
		$interval_day_amount = (int)$interval_day_amount;
		if ($interval_day_amount > 0) {
			$datereduce = new DateTime($datetime);
			$datereduce->sub(new DateInterval("P{$interval_day_amount}D"));
			$this->db_mutasi->where("transaction_date BETWEEN '{$datereduce->format('Y-m-d')}' AND '{$dateobject->format('Y-m-d')}'", NULL, FALSE);
		} else {
			$this->db_mutasi->where('transaction_date', $dateobject->format('Y-m-d'));
		}
		$this->db_mutasi->where('auto_approve_status', $status);
		$sql_query = $this->db_mutasi->get();
		return $sql_query->result();
	}
	function update_sb_transaction($seq, $query_params) {
		$this->db_mutasi->where('seq', $seq);
		$this->db_mutasi->update($this->mutasi_tables['sb_transaction'], $query_params);
		return $this->db_mutasi->affected_rows();
	}
	// Insert approve log
	function insert_approve_log($trans_seq, $approve_data) {
		$query_params = array(
			'sb_transaction_seq'		=> $trans_seq,
			'approve_data'				=> $approve_data,
		);
		if (is_array($query_params['approve_data'])) {
			$query_params['approve_data'] = json_encode($query_params['approve_data'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
		} else if (is_string($query_params['approve_data'])) {
			$query_params['approve_data'] = sprintf("%s", $query_params['approve_data']);
		} else {
			$query_params['approve_data'] = sprintf("%s", $query_params['approve_data']);
		}
		$this->db_mutasi->set('approve_datetime', 'NOW()', FALSE);
		$this->db_mutasi->insert($this->mutasi_tables['log_approve'], $query_params);
		return $this->db_mutasi->affected_rows();
	}
	
	
	
	//-------------------------------------------------
	// CURL To Suksesbugil
	//-------------------------------------------------
	function set_autoapprove_by_curl($type, $bank_code, $input_params) {
		$type = (is_string($type) ? strtolower($type) : 'approve');
		switch ($type) {
			case 'reject':
				$input_params['submit'] = 'Reject';
			break;
			case 'approve':
			default:
				$input_params['submit'] = 'Accept';
			break;
		}
		$bank_code = strtolower($bank_code);
		$login_params = $this->sb_approve->get_login_params('bank', $bank_code);
		try {
			if (isset($login_params['login_params']['login_username'])) {
				$this->sb_approve->set_curl_init($this->sb_approve->create_curl_headers($this->sb_approve->headers), $login_params['login_params']['login_username']);
			} else {
				$this->sb_approve->set_curl_init($this->sb_approve->create_curl_headers($this->sb_approve->headers));
			}
			$this->sb_approve->login();
			$approve_response = $this->sb_approve->approve_deposit($bank_code, $input_params);
			$this->sb_approve->logout();
		} catch (Exception $ex) {
			throw $ex;
			$approve_response = FALSE;
		}
		return $approve_response;
	}
	function match_autoapprove_by_curl($type, $bank_code, $collected_params, $userdata = array()) {
		$collected_approveds = array();
		$dateobject = new DateTime(date('Y-m-d H:i:s'));
		if (!is_array($collected_params)) {
			return FALSE;
		}
		$type = (is_string($type) ? strtolower($type) : 'approve');
		$bank_code = strtolower($bank_code);
		$login_params = $this->sb_approve->get_login_params('bank', $bank_code);
		$collectData = array(
			'dom_item_data'		=> array(),
		);
		if (count($collected_params) > 0) {
			if (isset($login_params['login_params']['login_username'])) {
				$this->sb_approve->set_curl_init($this->sb_approve->create_curl_headers($this->sb_approve->headers), $login_params['login_params']['login_username']);
			} else {
				$this->sb_approve->set_curl_init($this->sb_approve->create_curl_headers($this->sb_approve->headers));
			}
			$this->sb_approve->login();
			foreach ($collected_params as $matchKey => $matchVal) {
				//-------------------------------------------------------------------------- Begin
				$is_logget_out = FALSE;
				//--- curl to approve
				try {
					$auto_approve_params = json_decode($matchVal['suksesbugil']->auto_approve_params, true);
				} catch (Exception $ex) {
					throw $ex;
					$auto_approve_params = array();
				}
				switch ($type) {
					case 'reject':
						$auto_approve_params['submit'] = 'Reject';
					break;
					case 'approve':
					default:
						$auto_approve_params['submit'] = 'Accept';
					break;
				}
				try {
					$approved_response = $this->sb_approve->approve_deposit($bank_code, $auto_approve_params);
				} catch (Exception $ex) {
					throw $ex;
					$approved_response = FALSE;
				}
				
				// Check suksesbugil response
				if (strlen($approved_response) > 0) {
					libxml_use_internal_errors(true);
					$collectData['dom'] = new DOMDocument;
					$collectData['dom']->preserveWhiteSpace = false;
					$collectData['dom']->validateOnParse = false;
					$collectData['dom']->loadHTML($approved_response);
					$collectData['xpath'] = new DOMXPath($collectData['dom']);
					$collectData['queries'] = array(
						'form'		=> $collectData['xpath']->query('//input[@name="entered_login"]'),
					);
					if ((int)$collectData['queries']['form']->length > 0) {
						$is_logget_out = TRUE;
					} else {
						if ($collectData['dom']->hasChildNodes()) {
							foreach ($collectData['dom']->childNodes as $item) {
								$collectData['dom_item_data'][] = array(
									'nodepath'		=> $item->getNodePath(),
									'nodevalue'		=> trim(strip_tags($item->nodeValue)),
									'nodename'		=> $item->nodeName,
									'parentpath'	=> $item->parentNode->getNodePath(),
								);
							}
						}
					}
				} else {
					$is_logget_out = TRUE;
				}
				// SET auto-approve-as
				$auto_approve_as = array(
					'seq'			=> 0,
					'email'			=> 'root@system',
				);
				if (isset($userdata['seq']) && isset($userdata['account_email'])) {
					$auto_approve_as['seq'] = (is_numeric($userdata['seq']) ? (int)$userdata['seq'] : 0);
					$auto_approve_as['email'] = (is_string($userdata['account_email']) ? strtolower($userdata['account_email']) : '');
				}
				//-----------------------------
				// Not logged out?
				//-----------------------------
				if ($is_logget_out !== TRUE) {
					if (isset($collectData['dom_item_data'][1]['nodevalue'])) {
						if (strpos($collectData['dom_item_data'][1]['nodevalue'], 'Data already approved by') !== FALSE) {
							$update_sb_transaction_params = array(
								'auto_approve_status'				=> 'already',
								'auto_approve_datetime_executed'	=> $dateobject->format('Y-m-d H:i:s'),
								'transaction_datetime_update'		=> $dateobject->format('Y-m-d H:i:s'),
								'auto_approve_mutasi_trans_seq'		=> (isset($matchVal['mutasi']->seq) ? $matchVal['mutasi']->seq : 0),
								'auto_approve_log_by_account_seq'	=> $auto_approve_as['seq'],
								'auto_approve_log_by_account_email'	=> $auto_approve_as['email'],
							);
							$this->update_sb_transaction($matchVal['suksesbugil']->seq, $update_sb_transaction_params);
							$update_mb_transaction_params = array(
								'transaction_datetime_update'		=> $dateobject->format('Y-m-d H:i:s'),
								'transaction_action_status'			=> 'already',
								'is_approved'						=> 'Y',
								'is_deleted'						=> 'Y',
								'auto_deposit_trans_seq'			=> (isset($matchVal['suksesbugil']->seq) ? $matchVal['suksesbugil']->seq : 0),
								'auto_deposit_trans_response'		=> $collectData['dom_item_data'][1]['nodevalue'],
							);
							$this->update_mb_transaction($matchVal['mutasi']->seq, $update_mb_transaction_params);
						} else if (strpos($collectData['dom_item_data'][1]['nodevalue'], 'Tidak ada transaksi terproses, silahkan tekan CTRL+F5 untuk mengrefresh halaman') !== FALSE) {
							$update_sb_transaction_params = array(
								'auto_approve_status'				=> 'already',
								'auto_approve_datetime_executed'	=> $dateobject->format('Y-m-d H:i:s'),
								'transaction_datetime_update'		=> $dateobject->format('Y-m-d H:i:s'),
								'auto_approve_mutasi_trans_seq'		=> (isset($matchVal['mutasi']->seq) ? $matchVal['mutasi']->seq : 0),
								'auto_approve_log_by_account_seq'	=> $auto_approve_as['seq'],
								'auto_approve_log_by_account_email'	=> $auto_approve_as['email'],
							);
							$this->update_sb_transaction($matchVal['suksesbugil']->seq, $update_sb_transaction_params);
							$update_mb_transaction_params = array(
								'transaction_datetime_update'		=> $dateobject->format('Y-m-d H:i:s'),
								'transaction_action_status'			=> 'already',
								'is_approved'						=> 'Y',
								'is_deleted'						=> 'Y',
								'auto_deposit_trans_seq'			=> (isset($matchVal['suksesbugil']->seq) ? $matchVal['suksesbugil']->seq : 0),
								'auto_deposit_trans_response'		=> $collectData['dom_item_data'][1]['nodevalue'],
							);
							$this->update_mb_transaction($matchVal['mutasi']->seq, $update_mb_transaction_params);
						} else {
							$update_sb_transaction_params = array(
								'auto_approve_status'				=> 'approved',
								'auto_approve_datetime_executed'	=> $dateobject->format('Y-m-d H:i:s'),
								'transaction_datetime_update'		=> $dateobject->format('Y-m-d H:i:s'),
								'auto_approve_mutasi_trans_seq'		=> (isset($matchVal['mutasi']->seq) ? $matchVal['mutasi']->seq : 0),
								'auto_approve_log_by_account_seq'	=> $auto_approve_as['seq'],
								'auto_approve_log_by_account_email'	=> $auto_approve_as['email'],
							);
							$this->update_sb_transaction($matchVal['suksesbugil']->seq, $update_sb_transaction_params);
							$update_mb_transaction_params = array(
								'transaction_datetime_update'		=> $dateobject->format('Y-m-d H:i:s'),
								'transaction_action_status'			=> 'approve',
								'is_approved'						=> 'Y',
								'is_deleted'						=> 'Y',
								'auto_deposit_trans_seq'			=> (isset($matchVal['suksesbugil']->seq) ? $matchVal['suksesbugil']->seq : 0),
								'auto_deposit_trans_response'		=> 'Auto approve success',
							);
							$this->update_mb_transaction($matchVal['mutasi']->seq, $update_mb_transaction_params);
						}
					}
					$collected_approveds[$matchKey] = $approved_response;
					/*
					if (is_array($collectData['dom_item_data'])) {
						$collected_approveds[$matchKey] = json_encode($collectData['dom_item_data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
					} else if (is_string($collectData['dom_item_data']) || is_numeric($collectData['dom_item_data'])) {
						$collected_approveds[$matchKey] = $collectData['dom_item_data'];
					} else {
						$collected_approveds[$matchKey] = (isset($collectData['dom_item_data'][1]['nodevalue']) ? $collectData['dom_item_data'][1]['nodevalue'] : '');
					}
					*/
				} else {
					$collected_approveds[$matchKey] = 'Ketendang Logout';
				}
				//-------------------------------------------------------------------------- End
			}
			$this->sb_approve->logout();
		}
		return $collected_approveds;
	}
	function action_sb_deposit_data_by_curl($type, $bank_code, $sb_trans_data = null) {
		$dateobject = new DateTime(date('Y-m-d H:i:s'));
		if (!isset($sb_trans_data->auto_approve_params)) {
			return FALSE;
		}
		$type = (is_string($type) ? strtolower($type) : 'reject');
		$bank_code = strtolower($bank_code);
		$login_params = $this->sb_approve->get_login_params('bank', $bank_code);
		$collectData = array(
			'dom_item_data'		=> array(),
		);
		$is_logget_out = FALSE;
		if (isset($login_params['login_params']['login_username'])) {
			$this->sb_approve->set_curl_init($this->sb_approve->create_curl_headers($this->sb_approve->headers), $login_params['login_params']['login_username']);
		} else {
			$this->sb_approve->set_curl_init($this->sb_approve->create_curl_headers($this->sb_approve->headers));
		}
		$this->sb_approve->login();
		try {
			$action_deposit_params = json_decode($sb_trans_data->auto_approve_params, true);
		} catch (Exception $ex) {
			throw $ex;
			$action_deposit_params = array();
		}
		switch ($type) {
			case 'approve':
				$action_deposit_params['submit'] = 'Accept';
			break;
			case 'reject':
			default:
				$action_deposit_params['submit'] = 'Reject';
			break;
		}
		//--- curl to action
		try {
			$action_deposit_response = $this->sb_approve->approve_deposit($bank_code, $action_deposit_params);
		} catch (Exception $ex) {
			throw $ex;
			$action_deposit_response = FALSE;
		}
		$insert_params = array(
			'sb_transaction_seq' => $sb_trans_data->seq,
			'approve_datetime' => $dateobject->format('Y-m-d H:i:s'),
			'approve_data' => '',
		);
		$update_sb_transaction_params = array(
			'auto_approve_datetime_executed'	=> $dateobject->format('Y-m-d H:i:s'),
			'transaction_datetime_update'		=> $dateobject->format('Y-m-d H:i:s'),
		);
		// Check suksesbugil response
		if ($action_deposit_response != FALSE) {
			libxml_use_internal_errors(true);
			$collectData['dom'] = new DOMDocument;
			$collectData['dom']->preserveWhiteSpace = false;
			$collectData['dom']->validateOnParse = false;
			if (strlen($action_deposit_response) > 0) {
				$collectData['dom']->loadHTML($action_deposit_response);
				$collectData['xpath'] = new DOMXPath($collectData['dom']);
				$collectData['queries'] = array(
					'input_login'		=> $collectData['xpath']->query("//input[@name='entered_login']"),
					'javascript'		=> $collectData['xpath']->query("//script[@language='javascript']"),
				);
				if ((int)$collectData['queries']['input_login']->length > 0) {
					$is_logget_out = TRUE;
				} else {
					//===================================================
					# Get HTMLDom
					if ($collectData['dom']->hasChildNodes()) {
						foreach ($collectData['dom']->childNodes as $item) {
							$collectData['dom_item_data'][] = array(
								'nodepath'		=> $item->getNodePath(),
								'nodevalue'		=> trim(strip_tags($item->nodeValue)),
								'nodename'		=> $item->nodeName,
								'parentpath'	=> $item->parentNode->getNodePath(),
							);
						}
					}
					// Check
					if (isset($collectData['dom_item_data'][1]['nodevalue'])) {
						if (strpos($collectData['dom_item_data'][1]['nodevalue'], 'Data already approved by') !== FALSE) {
							$update_sb_transaction_params['auto_approve_status'] = 'already';
						} else if (strpos($collectData['dom_item_data'][1]['nodevalue'], 'Tidak ada transaksi terproses, silahkan tekan CTRL+F5 untuk mengrefresh halaman') !== FALSE) {
							$update_sb_transaction_params['auto_approve_status'] = 'already';
						} else {
							$update_sb_transaction_params['auto_approve_status'] = 'approved';
						}
					}
				}
				$insert_params['approve_data'] = $action_deposit_response;
			}
		} else {
			$is_logget_out = TRUE;
		}
		// LOG Approved
		$this->db_mutasi->insert($this->mutasi_tables['log_approve'], $insert_params);
		//-----------------------------
		// Not logged out?
		//-----------------------------
		if ($is_logget_out !== TRUE) {
			$this->update_sb_transaction($sb_trans_data->seq, $update_sb_transaction_params);	
		}
	}
	
	function execute_matching_autoapprove_by_curl($type, $bank_code, $collected_params) {
		$collected_approveds = array();
		$dateobject = new DateTime(date('Y-m-d H:i:s'));
		if (!is_array($collected_params)) {
			return FALSE;
		}
		$type = (is_string($type) ? strtolower($type) : 'approve');
		$bank_code = strtolower($bank_code);
		$login_params = $this->sb_approve->get_login_params('bank', $bank_code);
		if (count($collected_params) > 0) {
			if (isset($login_params['login_params']['login_username'])) {
				$this->sb_approve->set_curl_init($this->sb_approve->create_curl_headers($this->sb_approve->headers), $login_params['login_params']['login_username']);
			} else {
				$this->sb_approve->set_curl_init($this->sb_approve->create_curl_headers($this->sb_approve->headers));
			}
			$this->sb_approve->login();
			foreach ($collected_params as $matchKey => $matchVal) {
				/*
				$update_sb_transaction_params = array(
					'auto_approve_status'				=> 'approved',
					'auto_approve_datetime_executed'	=> $dateobject->format('Y-m-d H:i:s'),
					'transaction_datetime_update'		=> $dateobject->format('Y-m-d H:i:s'),
					'auto_approve_mutasi_trans_seq'		=> (isset($matchVal['mutasi']->seq) ? $matchVal['mutasi']->seq : 0),
				);
				$this->update_sb_transaction($matchVal['suksesbugil']->seq, $update_sb_transaction_params);
				$update_mb_transaction_params = array(
					'transaction_datetime_update'		=> $dateobject->format('Y-m-d H:i:s'),
					'transaction_action_status'			=> 'approve',
					'is_approved'						=> 'Y',
					'is_deleted'						=> 'Y',
					'auto_deposit_trans_seq'			=> (isset($matchVal['suksesbugil']->seq) ? $matchVal['suksesbugil']->seq : 0),
				);
				$this->update_mb_transaction($matchVal['mutasi']->seq, $update_mb_transaction_params);
				*/
				//--- curl to approve
				try {
					$auto_approve_params = json_decode($matchVal['suksesbugil']->auto_approve_params, true);
				} catch (Exception $ex) {
					throw $ex;
					$auto_approve_params = array();
				}
				switch ($type) {
					case 'reject':
						$auto_approve_params['submit'] = 'Reject';
					break;
					case 'approve':
					default:
						$auto_approve_params['submit'] = 'Accept';
					break;
				}
				try {
					$collected_approveds[$matchKey] = $this->sb_approve->approve_deposit($bank_code, $auto_approve_params);
				} catch (Exception $ex) {
					throw $ex;
					$collected_approveds[$matchKey] = FALSE;
				}
			}
			$this->sb_approve->logout();
		}
		return $collected_approveds;
	}

	
	
	//----------------------------------
	function insert_auto_approve_log($input_params = array()) {
		$this->db_mutasi->set('log_datetime', 'NOW()', FALSE);
		$this->db_mutasi->insert($this->mutasi_tables['log_autoapprove_run'], $input_params);
		return $this->db_mutasi->insert_id();
	}
	function update_auto_approve_log($seq) {
		$this->db_mutasi->where('seq', $seq);
		$this->db_mutasi->set('running_datetime_stopping', 'NOW()', FALSE);
		$this->db_mutasi->update($this->mutasi_tables['log_autoapprove_run'], array());
		return $this->db_mutasi->affected_rows();
	}
}
















