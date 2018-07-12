<?php
if ( ! defined('BASEPATH')) { exit('No direct script access allowed: ' . (__FILE__)); }
class Model_suksesbugil extends CI_Model {
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
		
		
		# Load Suksesbugil Lib
		$this->load->library('mutasi/Lib_suksesbugil', FALSE, 'suksesbugil');
		# Load Configs
		$this->load->config('mutasi/base_suksesbugil');
		$this->base_suksesbugil = $this->config->item('base_suksesbugil');
	}
	function get_sb_banks($is_active = 0) {
		$is_active = (is_numeric($is_active) ? (int)$is_active : 0);
		if ($is_active > 0) {
			$this->db_mutasi->where('bank_is_active', 'Y');
		}
		$this->db_mutasi->order_by('bank_code', 'ASC');
		$banks = $this->db_mutasi->get($this->mutasi_tables['sb_bank']);
		return $banks->result();
	}
	//==============
	function get_sb_deposit_groups_by($by_type, $by_value, $is_approved = '', $transaction_amount = 0, $transaction_date = null, $search_text = '')  {
		$is_approved = (is_string($is_approved) ? strtolower($is_approved) : '');
		$by_type = (is_string($by_type) ? strtolower($by_type) : 'bank');
		$value = "";
		if (isset($by_value)) {
			if (is_numeric($by_value) || is_string($by_value)) {
				$value = sprintf("%s", $by_value);
			} else {
				$value = "";
			}
		}
		$value = ((is_string($value) || is_numeric($value)) ? $value : '');
		switch (strtolower($by_type)) {
			case 'code':
			case 'slug':
				$value = sprintf("%s", $value);
			break;
			case 'bank':
			case 'account':
				if (!preg_match('/^[1-9][0-9]*$/', $by_value)) {
					$value = 0;
				} else {
					$value = sprintf('%d', $value);
				}
			break;
			case 'all':
				$value = sprintf("%s", $value);
			break;
		}
		switch (strtolower($by_type)) {
			case 'code':
			case 'slug':
				$sql = sprintf("SELECT COUNT(t.seq) AS count_value, SUM(t.transaction_amount) AS amount_value FROM %s AS t LEFT JOIN %s AS b ON b.seq = t.mutasi_bank_seq WHERE (b.bank_code = '%s')",
					$this->mutasi_tables['sb_transaction'],
					$this->mutasi_tables['bank'],
					$this->db_mutasi->escape_str($value)
				);
			break;
			case 'bank':
				$sql = sprintf("SELECT COUNT(t.seq) AS count_value, SUM(t.transaction_amount) AS amount_value FROM %s AS t LEFT JOIN %s AS b ON b.seq = t.mutasi_bank_seq WHERE (b.seq = '%d')",
					$this->mutasi_tables['sb_transaction'],
					$this->mutasi_tables['bank'],
					$this->db_mutasi->escape_str($value)
				);
			break;
			case 'account':
				$sql = sprintf("SELECT COUNT(t.seq) AS count_value, SUM(t.transaction_amount) AS amount_value FROM %s AS t LEFT JOIN %s AS a ON a.seq = t.mutasi_bank_account_seq WHERE (a.seq = '%d')",
					$this->mutasi_tables['sb_transaction'],
					$this->mutasi_tables['bank_account'],
					$this->db_mutasi->escape_str($value)
				);
			break;
			case 'all':
			default:
				$sql = sprintf("SELECT COUNT(t.seq) AS count_value, SUM(t.transaction_amount) AS amount_value FROM %s AS t WHERE (t.seq > 0)", $this->mutasi_tables['sb_transaction']);
			break;
		}
		if ((strlen($is_approved) > 0) && ($is_approved != '')) {
			$sql .= sprintf(" AND (t.auto_approve_status = '%s')", $this->db_mutasi->escape_str($is_approved));
		}
		$transaction_amount = (int)$transaction_amount;
		if ($transaction_amount > 0) {
			$sql .= sprintf(" AND (t.transaction_amount = '%d')", $this->db_mutasi->escape_str($transaction_amount));
		}
		if (isset($transaction_date['starting']) && isset($transaction_date['stopping'])) {
			$sql .= sprintf(" AND (t.transaction_date BETWEEN '%s' AND '%s')",
				$this->db_mutasi->escape_str($transaction_date['starting']->format('Y-m-d')),
				$this->db_mutasi->escape_str($transaction_date['stopping']->format('Y-m-d'))
			);
		}
		
		
		$search_text = (is_string($search_text) ? $search_text : '');
		if (!empty($search_text) && (strlen($search_text) > 0)) {
			$sql_likes = " AND (";
			$search_array = base_permalink($search_text);
			$search_array = explode("-", $search_array);
			if (count($search_array) > 0) {
				$for_i = 0;
				foreach ($search_array as $val) {
					if ($for_i > 0) {
						$sql_likes .= sprintf(" AND (CONCAT('', t.transaction_from_acc_rekening, '') LIKE '%%%s%%' OR CONCAT('', t.transaction_sb_account, '') LIKE '%%%s%%' OR CONCAT('', t.transaction_from_acc_name, '') LIKE '%%%s%%')", 
							$this->db_mutasi->escape_str($val),
							$this->db_mutasi->escape_str($val),
							$this->db_mutasi->escape_str($val)
						);
					} else {
						$sql_likes .= sprintf(" (CONCAT('', t.transaction_from_acc_rekening, '') LIKE '%%%s%%' OR CONCAT('', t.transaction_sb_account, '') LIKE '%%%s%%' OR CONCAT('', t.transaction_from_acc_name, '') LIKE '%%%s%%')",
							$this->db_mutasi->escape_str($val),
							$this->db_mutasi->escape_str($val),
							$this->db_mutasi->escape_str($val)
						);
					}
					$for_i++;
				}	
			} else {
				$sql_likes .= " 1=1";
			}
			$sql_likes .= ")";
			$sql .= $sql_likes;
		}
		$sql_query = $this->db_mutasi->query($sql);
		return $sql_query->result();
	}
	//==============
	function get_sb_deposit_count_by($by_type, $by_value, $is_approved = '', $transaction_amount = 0, $transaction_date = null, $search_text = '')  {
		$is_approved = (is_string($is_approved) ? strtolower($is_approved) : '');
		$by_type = (is_string($by_type) ? strtolower($by_type) : 'bank');
		$value = "";
		if (isset($by_value)) {
			if (is_numeric($by_value) || is_string($by_value)) {
				$value = sprintf("%s", $by_value);
			} else {
				$value = "";
			}
		}
		$value = ((is_string($value) || is_numeric($value)) ? $value : '');
		switch (strtolower($by_type)) {
			case 'code':
			case 'slug':
				$value = sprintf("%s", $value);
			break;
			case 'bank':
			case 'account':
				if (!preg_match('/^[1-9][0-9]*$/', $by_value)) {
					$value = 0;
				} else {
					$value = sprintf('%d', $value);
				}
			break;
			case 'all':
				$value = sprintf("%s", $value);
			break;
		}
		switch (strtolower($by_type)) {
			case 'code':
			case 'slug':
				$sql = sprintf("SELECT COUNT(t.seq) AS value FROM %s AS t LEFT JOIN %s AS b ON b.seq = t.mutasi_bank_seq WHERE (b.bank_code = '%s')",
					$this->mutasi_tables['sb_transaction'],
					$this->mutasi_tables['bank'],
					$this->db_mutasi->escape_str($value)
				);
			break;
			case 'bank':
				$sql = sprintf("SELECT COUNT(t.seq) AS value FROM %s AS t LEFT JOIN %s AS b ON b.seq = t.mutasi_bank_seq WHERE (b.seq = '%d')",
					$this->mutasi_tables['sb_transaction'],
					$this->mutasi_tables['bank'],
					$this->db_mutasi->escape_str($value)
				);
			break;
			case 'account':
				$sql = sprintf("SELECT COUNT(t.seq) AS value FROM %s AS t LEFT JOIN %s AS a ON a.seq = t.mutasi_bank_account_seq WHERE (a.seq = '%d')",
					$this->mutasi_tables['sb_transaction'],
					$this->mutasi_tables['bank_account'],
					$this->db_mutasi->escape_str($value)
				);
			break;
			case 'all':
			default:
				$sql = sprintf("SELECT COUNT(t.seq) AS value FROM %s AS t WHERE (t.seq > 0)", $this->mutasi_tables['sb_transaction']);
			break;
		}
		if ((strlen($is_approved) > 0) && ($is_approved != '')) {
			$sql .= sprintf(" AND (t.auto_approve_status = '%s')", $this->db_mutasi->escape_str($is_approved));
		}
		$transaction_amount = (int)$transaction_amount;
		if ($transaction_amount > 0) {
			$sql .= sprintf(" AND (t.transaction_amount = '%d')", $this->db_mutasi->escape_str($transaction_amount));
		}
		if (isset($transaction_date['starting']) && isset($transaction_date['stopping'])) {
			$sql .= sprintf(" AND (t.transaction_date BETWEEN '%s' AND '%s')",
				$this->db_mutasi->escape_str($transaction_date['starting']->format('Y-m-d')),
				$this->db_mutasi->escape_str($transaction_date['stopping']->format('Y-m-d'))
			);
		}
		
		
		$search_text = (is_string($search_text) ? $search_text : '');
		if (!empty($search_text) && (strlen($search_text) > 0)) {
			$sql_likes = " AND (";
			$search_array = base_permalink($search_text);
			$search_array = explode("-", $search_array);
			if (count($search_array) > 0) {
				$for_i = 0;
				foreach ($search_array as $val) {
					if ($for_i > 0) {
						$sql_likes .= sprintf(" AND (CONCAT('', t.transaction_from_acc_rekening, '') LIKE '%%%s%%' OR CONCAT('', t.transaction_sb_account, '') LIKE '%%%s%%' OR CONCAT('', t.transaction_from_acc_name, '') LIKE '%%%s%%')", 
							$this->db_mutasi->escape_str($val),
							$this->db_mutasi->escape_str($val),
							$this->db_mutasi->escape_str($val)
						);
					} else {
						$sql_likes .= sprintf(" (CONCAT('', t.transaction_from_acc_rekening, '') LIKE '%%%s%%' OR CONCAT('', t.transaction_sb_account, '') LIKE '%%%s%%' OR CONCAT('', t.transaction_from_acc_name, '') LIKE '%%%s%%')",
							$this->db_mutasi->escape_str($val),
							$this->db_mutasi->escape_str($val),
							$this->db_mutasi->escape_str($val)
						);
					}
					$for_i++;
				}	
			} else {
				$sql_likes .= " 1=1";
			}
			$sql_likes .= ")";
			$sql .= $sql_likes;
		}
		$sql_query = $this->db_mutasi->query($sql);
		return $sql_query->row();
    }
	function get_sb_deposit_data_by($by_type, $by_value, $is_approved = '', $transaction_amount = 0, $transaction_date = null, $search_text = '', $start = 0, $per_page = 10)  {
		$is_approved = (is_string($is_approved) ? strtolower($is_approved) : '');
		$by_type = (is_string($by_type) ? strtolower($by_type) : 'bank');
		$value = "";
		if (isset($by_value)) {
			if (is_numeric($by_value) || is_string($by_value)) {
				$value = sprintf("%s", $by_value);
			} else {
				$value = "";
			}
		}
		$value = ((is_string($value) || is_numeric($value)) ? $value : '');
		switch (strtolower($by_type)) {
			case 'code':
			case 'slug':
				$value = sprintf("%s", $value);
			break;
			case 'bank':
			case 'account':
				if (!preg_match('/^[1-9][0-9]*$/', $by_value)) {
					$value = 0;
				} else {
					$value = sprintf('%d', $value);
				}
			break;
			case 'all':
				$value = sprintf("%s", $value);
			break;
		}
		switch (strtolower($by_type)) {
			case 'code':
			case 'slug':
				$sql = sprintf("SELECT t.* FROM %s AS t LEFT JOIN %s AS b ON b.seq = t.mutasi_bank_seq WHERE (b.bank_code = '%s')",
					$this->mutasi_tables['sb_transaction'],
					$this->mutasi_tables['bank'],
					$this->db_mutasi->escape_str($value)
				);
			break;
			case 'bank':
				$sql = sprintf("SELECT t.* FROM %s AS t LEFT JOIN %s AS b ON b.seq = t.mutasi_bank_seq WHERE (b.seq = '%d')",
					$this->mutasi_tables['sb_transaction'],
					$this->mutasi_tables['bank'],
					$this->db_mutasi->escape_str($value)
				);
			break;
			case 'account':
				$sql = sprintf("SELECT t.* FROM %s AS t LEFT JOIN %s AS a ON a.seq = t.mutasi_bank_account_seq WHERE (a.seq = '%d')",
					$this->mutasi_tables['sb_transaction'],
					$this->mutasi_tables['bank_account'],
					$this->db_mutasi->escape_str($value)
				);
			break;
			case 'all':
			default:
				$sql = sprintf("SELECT t.* FROM %s AS t WHERE (t.seq > 0)", $this->mutasi_tables['sb_transaction']);
			break;
		}
		if ((strlen($is_approved) > 0) && ($is_approved != '')) {
			$sql .= sprintf(" AND (t.auto_approve_status = '%s')", $this->db_mutasi->escape_str($is_approved));
		}
		$transaction_amount = (int)$transaction_amount;
		if ($transaction_amount > 0) {
			$sql .= sprintf(" AND (t.transaction_amount = '%d')", $this->db_mutasi->escape_str($transaction_amount));
		}
		if (isset($transaction_date['starting']) && isset($transaction_date['stopping'])) {
			$sql .= sprintf(" AND (t.transaction_date BETWEEN '%s' AND '%s')",
				$this->db_mutasi->escape_str($transaction_date['starting']->format('Y-m-d')),
				$this->db_mutasi->escape_str($transaction_date['stopping']->format('Y-m-d'))
			);
		}
		$search_text = (is_string($search_text) ? $search_text : '');
		if (!empty($search_text) && (strlen($search_text) > 0)) {
			$sql_likes = " AND (";
			$search_array = base_permalink($search_text);
			$search_array = explode("-", $search_array);
			if (count($search_array) > 0) {
				$for_i = 0;
				foreach ($search_array as $val) {
					if ($for_i > 0) {
						$sql_likes .= sprintf(" AND (CONCAT('', t.transaction_from_acc_rekening, '') LIKE '%%%s%%' OR CONCAT('', t.transaction_sb_account, '') LIKE '%%%s%%' OR CONCAT('', t.transaction_from_acc_name, '') LIKE '%%%s%%')", 
							$this->db_mutasi->escape_str($val),
							$this->db_mutasi->escape_str($val),
							$this->db_mutasi->escape_str($val)
						);
					} else {
						$sql_likes .= sprintf(" (CONCAT('', t.transaction_from_acc_rekening, '') LIKE '%%%s%%' OR CONCAT('', t.transaction_sb_account, '') LIKE '%%%s%%' OR CONCAT('', t.transaction_from_acc_name, '') LIKE '%%%s%%')",
							$this->db_mutasi->escape_str($val),
							$this->db_mutasi->escape_str($val),
							$this->db_mutasi->escape_str($val)
						);
					}
					$for_i++;
				}	
			} else {
				$sql_likes .= " 1=1";
			}
			$sql_likes .= ")";
			$sql .= $sql_likes;
		}
		$sql .= " ORDER BY t.transaction_datetime DESC";
		$sql .= sprintf(" LIMIT %d, %d", $start, $per_page);
		$sql_query = $this->db_mutasi->query($sql);
		return $sql_query->result();
    }
	//-----------------------------
	// For auto-approve cli system
	//-----------------------------
	function set_suksesbugil_transaction($trans_seq, $input_params) {
		$trans_seq = (is_numeric($trans_seq) ? (int)$trans_seq : 0);
		$this->db_mutasi->where('seq', $trans_seq);
		$this->db_mutasi->update($this->mutasi_tables['sb_transaction'], $input_params);
		return $this->db_mutasi->affected_rows();
	}
	
	//==============
	function insert_suksesbugil_transactions($transaction_params) {
		$this->db_mutasi->set('transaction_datetime_insert', 'NOW()', FALSE);
		$this->db_mutasi->set('transaction_datetime_update', 'NOW()', FALSE);
		$this->db_mutasi->set('auto_approve_datetime_executed', 'NULL', FALSE);
		$transaction_params['auto_approve_status'] = 'waiting';
		$this->db_mutasi->insert($this->mutasi_tables['sb_transaction'], $transaction_params);
		return $this->db_mutasi->insert_id();
	}
	function get_suksesbugil_transaction_item_by_bankaccount_and_date($bank_seq, $acc_seq, $trans_date, $input_params = array(), $is_approved = 0) {
		$collectData = array(
			'bank_seq'		=> (is_numeric($bank_seq) ? (int)$bank_seq : 0),
			'acc_seq'		=> (is_numeric($acc_seq) ? (int)$acc_seq : 0),
			'trans_date'	=> (is_string($trans_date) || is_numeric($trans_date)) ? $trans_date : date('Y-m-d'),
		);
		$trans_dateobject = new DateTime($collectData['trans_date']);
		
		$sql = sprintf("SELECT seq AS value FROM %s WHERE (mutasi_bank_seq = '%d' AND mutasi_bank_account_seq = '%d')", 
			$this->mutasi_tables['sb_transaction'],
			$this->db_mutasi->escape_str($collectData['bank_seq']),
			$this->db_mutasi->escape_str($collectData['acc_seq'])
		);
		if ($trans_dateobject != FALSE) {
			$sql .= sprintf(" AND transaction_date = '%s'", $this->db_mutasi->escape_str($trans_dateobject->format('Y-m-d')));
		}
		if (is_array($input_params) && (count($input_params) > 0)) {
			$sql_wheres = " AND (";
			$for_i = 0;
			foreach ($input_params as $inputkey => $inputval) {
				if ($for_i > 0) {
					$sql_wheres .= sprintf(" AND %s = '%s'", $inputkey, $this->db_mutasi->escape_str($inputval));
				} else {
					$sql_wheres .= sprintf(" %s = '%s'", $inputkey, $this->db_mutasi->escape_str($inputval));
				}
				$for_i++;
			}
			$sql_wheres .= ")";
			$sql .= $sql_wheres;
		}
		try {
			$sql_query = $this->db_mutasi->query($sql);
		} catch (Exception $ex) {
			throw $ex;
			return FALSE;
		}
		return $sql_query->row();
	}
	
	
	
	
	
	
	
	//-----------------------------------------------------------------------
	function generate_suksesbugil_transaction_data($collectData = array()) {
		$transaction_data = array();
		if (!isset($collectData['dom_table_data'])) {
			return FALSE;
			// collectData not in propher format
		}
		if (is_array($collectData['dom_table_data']) && (count($collectData['dom_table_data']) > 0)) {
			$for_i = 1;
			$total_rows = count($collectData['dom_table_data']);
			foreach ($collectData['dom_table_data'] as $dataKey => $dataVal) {
				$item = array();
				if ((int)$dataKey > 0) {
					if ((isset($dataVal['items']) && isset($dataVal['childs'])) && ($for_i < $total_rows)) {
						$item['trans_details_bank_from'] = (isset($dataVal['items'][6]->nodeValue) ? $dataVal['items'][6]->nodeValue : '');
						$item['trans_details_bank_to'] = (isset($dataVal['childs'][7]['parent']['nodevalue']) ? trim($dataVal['childs'][7]['parent']['nodevalue']) : '');
						if (isset($dataVal['items'][3]->nodeValue)) {
							try {
								$transaction_dateobject = new DateTime($dataVal['items'][3]->nodeValue);
							} catch (Exception $ex) {
								throw $ex;
								exit("Cannot create datetime object from dom table data datetime.");
							}
							if ($transaction_dateobject != FALSE) {
								$transaction_dateobject->setTimezone(new DateTimeZone(ConstantConfig::$timezone));
							} else {
								$transaction_dateobject = new DateTime(date('Y-m-d H:i:s'));
							}
						} else {
							$transaction_dateobject = new DateTime(date('Y-m-d H:i:s'));
						}
						$item['transaction_date'] = $transaction_dateobject->format('Y-m-d');
						$item['transaction_datetime'] = $transaction_dateobject->format('Y-m-d H:i:s');
						$item['transaction_amount'] = 0;
						if (isset($dataVal['childs'][4]['items'])) {
							if (is_array($dataVal['childs'][4]['items'])) {
								if (count($dataVal['childs'][4]['items']) > 0) {
									foreach ($dataVal['childs'][4]['items'] as $amountVal) {
										if (isset($amountVal['name']) && isset($amountVal['value'])) {
											if (strtolower($amountVal['name']) === 'b') {
												$amount_value = str_replace(",", "", $amountVal['value']);
												$amount_value = str_replace(".", "", $amount_value);
												$item['transaction_amount'] = sprintf("%.02f", $amount_value);
											}
										}
									}
								}
							}
						}
						$transaction_from_acc = explode(",", $dataVal['childs'][6]['parent']['nodevalue']);
						$item['transaction_from_acc_name'] = (isset($transaction_from_acc[2]) ? sprintf("%s", $transaction_from_acc[2]) : '');
						$item['transaction_from_acc_rekening'] = (isset($transaction_from_acc[1]) ? sprintf("%s", $transaction_from_acc[1]) : '');
						$item['transaction_from_acc_rekening'] = str_replace("-", "", $item['transaction_from_acc_rekening']);
						$item['transaction_from_acc_rekening'] = trim($item['transaction_from_acc_rekening']);
						$item['transaction_from_acc_bank'] = sprintf("%s", $transaction_from_acc[0]);
						if (isset($dataVal['childs'][1]['parent']['is_have_input'])) {
							if ($dataVal['childs'][1]['parent']['is_have_input'] == TRUE) {
								$auto_approve_params = array(
									'action'			=> 1,
									'submit'			=> 'Accept',
								);
								if (isset($dataVal['childs'][1]['parent']['input_params']['attributes']['name'])) {
									$approve_id_name = strtolower($dataVal['childs'][1]['parent']['input_params']['attributes']['name']);
									$item['transaction_sb_unique_identifier'] = $approve_id_name;
									$auto_approve_params[$approve_id_name] = 'on';
								}
								$item['auto_approve_params'] = json_encode($auto_approve_params, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
							} else {
								$item['auto_approve_params'] = '';
							}
						}
						$transaction_sb_account = preg_split('/$\R?^/m', $dataVal['items'][2]->nodeValue);
						$item['transaction_sb_account'] = (isset($transaction_sb_account[0]) ? $transaction_sb_account[0] : '');
						
						$trans_details_bank_to = explode(":", $item['trans_details_bank_to']);
						if (isset($trans_details_bank_to[1])) {
							$trans_details_bank_mutasi = explode(",", ($trans_details_bank_to[1]));
							$item['mutasi_bank_account_seq'] = $this->get_transitem_bank_account_seq($trans_details_bank_mutasi);
							$item['mutasi_bank_seq'] = $this->get_transitem_bank_seq($trans_details_bank_mutasi);
						} else {
							$item['mutasi_bank_account_seq'] = 0;
							$item['mutasi_bank_seq'] = 0;
						}
						
						
						// Add to transaction_data
						$transaction_data[] = $item;
					}
				}
				$for_i += 1;
			}
		}
		return $transaction_data;
	}
	private function get_transitem_bank_account_seq($input_params = array()) {
		$account_seq = 0;
		$query_params = array(
			'bank_account_seq' => 0,
		);
		if (count($input_params) > 2) {
			$query_params['account_rekening'] = (isset($input_params[1]) ? trim($input_params[1]) : '');
			$query_params['account_rekening'] = str_replace("-", '', $query_params['account_rekening']);
			$query_params['account_rekening'] = trim($query_params['account_rekening']);
			$query_params['account_rekening'] = sprintf("%s", $query_params['account_rekening']);
			$query_params['account_name'] = (isset($input_params[2]) ? trim($input_params[2]) : '');
			$query_params['account_bank'] =  (isset($input_params[0]) ? trim($input_params[0]) : '');
			$sb_bank_data = $this->get_sb_bank_by('name', $query_params['account_bank']);
			if (isset($sb_bank_data->mb_seq)) {
				$query_params['bank_seq'] = $sb_bank_data->mb_seq;
			} else {
				$query_params['bank_seq'] = 0;
			}
			try {
				$account_data = $this->get_sb_account_item_by('rekening', $query_params['account_rekening'], $query_params);
			} catch (Exception $ex) {
				throw $ex;
				$account_data = FALSE;
			}
			if ($account_data != FALSE) {
				if (is_array($account_data)) {
					if (count($account_data) > 0) {
						foreach ($account_data as $row) {
							$query_params['bank_account_seq'] = $row->account_seq;
						}
					}
				}
			}
		}
		return $query_params['bank_account_seq'];
	}
	private function get_transitem_bank_seq($input_params = array()) {
		$bank_name = (isset($input_params[0]) ? $input_params[0] : '');
		$bank_name = trim($bank_name);
		$bank_name = sprintf("%s", $bank_name);
		try {
			$row = $this->get_sb_bank_by('name', $bank_name);
		} catch (Exception $ex) {
			throw $ex;
			return 0;
		}
		if (isset($row->mb_seq)) {
			return $row->mb_seq;
		}
		return 0;
	}
	function get_sb_bank_by($by_type, $by_value) {
		$by_type = (is_string($by_type) ? strtolower($by_type) : 'code');
		$value = "";
		if (isset($by_value)) {
			if (is_numeric($by_value) || is_string($by_value)) {
				$value = sprintf("%s", $by_value);
			} else {
				$value = "";
			}
		}
		$value = ((is_string($value) || is_numeric($value)) ? $value : '');
		$sql = sprintf("SELECT s.seq, s.mutasi_bank_seq, s.bank_code, s.bank_codename, mb.seq AS mb_seq, mb.bank_code AS mb_bank_code FROM %s AS s INNER JOIN %s AS mb ON mb.seq = s.mutasi_bank_seq WHERE",
			$this->mutasi_tables['sb_bank'],
			$this->mutasi_tables['bank']
		);
		switch ($by_type) {
			case 'seq':
				$sql .= sprintf(" s.seq = '%d'", $this->db_mutasi->escape_str($value));
			break;
			case 'name':
				$value = strtoupper($value);
				$sql .= sprintf(" s.bank_codename = '%s'", $this->db_mutasi->escape_str($value));
			break;
			case 'code':
			default:
				$value = strtolower($value);
				$sql .= sprintf(" s.bank_code = '%s'", $this->db_mutasi->escape_str($value));
			break;
		}
		$sql .= " LIMIT 1";
		$sql_query = $this->db_mutasi->query($sql);
		return $sql_query->row();
	}
	function get_sb_account_item_by($by_type, $by_value, $input_params = array()) {
		$by_type = (is_string($by_type) ? strtolower($by_type) : 'seq');
		$value = "";
		if (isset($by_value)) {
			if (is_numeric($by_value)) {
				$value = (int)$by_value;
			} else if (is_string($by_value)) {
				$value = sprintf("%s", $by_value);
			} else {
				$value = "";
			}
		}
		$value = ((is_string($value) || is_numeric($value)) ? $value : '');
		switch (strtolower($by_type)) {
			case 'code':
			case 'slug':
			case 'rekening':
				$value = sprintf("%s", $value);
			break;
			case 'bank':
			case 'seq':
			default:
				if (!preg_match('/^[1-9][0-9]*$/', $by_value)) {
					$value = 0;
				} else {
					$value = sprintf('%d', $value);
				}
			break;
		}
		switch (strtolower($by_type)) {
			case 'bank':
				$sql = sprintf("SELECT a.*, b.seq AS b_bank_seq, b.bank_code FROM %s AS a LEFT JOIN %s AS b ON b.seq = a.bank_seq",
					$this->mutasi_tables['bank_account'],
					$this->mutasi_tables['bank']
				);
				$sql .= sprintf(" WHERE b.seq = '%d'", $this->db_mutasi->escape_str($value));
			break;
			case 'code':
			case 'slug':
				$sql = sprintf("SELECT a.*, b.seq AS b_bank_seq, b.bank_code FROM %s AS a LEFT JOIN %s AS b ON b.seq = a.bank_seq",
					$this->mutasi_tables['bank_account'],
					$this->mutasi_tables['bank']
				);
				$sql .= sprintf(" WHERE a.account_slug = '%s'", $this->db_mutasi->escape_str($value));
			break;
			case 'rekening':
				$sql = sprintf("SELECT r.*, a.seq AS a_account_seq, a.account_username, a.account_password, a.account_owner, a.account_is_active, b.seq AS b_bank_seq, b.bank_code FROM %s AS r LEFT JOIN %s AS a ON a.seq = r.account_seq LEFT JOIN %s AS b ON b.seq = a.bank_seq",
					$this->mutasi_tables['bank_rekening'],
					$this->mutasi_tables['bank_account'],
					$this->mutasi_tables['bank']
				);
				if (isset($input_params['bank_seq'])) {
					$input_params['bank_seq'] = (int)$input_params['bank_seq'];
					$sql .= sprintf(" WHERE (r.bank_seq = '%d' AND CAST(r.rekening_number AS UNSIGNED) = '%d')", 
						$this->db_mutasi->escape_str($input_params['bank_seq']), 
						$this->db_mutasi->escape_str($value)
					);
				} else {
					$sql .= sprintf(" WHERE r.rekening_number = '%s'", $this->db_mutasi->escape_str($value));
				}
			break;
			case 'seq':
			default:
				$sql = sprintf("SELECT a.*, b.seq AS b_bank_seq, b.bank_code FROM %s AS a LEFT JOIN %s AS b ON b.seq = a.bank_seq",
					$this->mutasi_tables['bank_account'],
					$this->mutasi_tables['bank']
				);
				$sql .= sprintf(" WHERE a.seq = '%d'", $this->db_mutasi->escape_str($value));
			break;
		}
		$sql .= " ORDER BY a.account_ordering ASC";
		$sql_query = $this->db_mutasi->query($sql);
		return $sql_query->result();
	}
	//----------------------------------------------------------------------------------
	function get_data_suksesbugil_by($by_type, $by_value) {
		$by_type = (is_string($by_type) ? strtolower($by_type) : 'bank');
		$value = "";
		if (is_numeric($by_value) || is_string($by_value)) {
			$value = sprintf("%s", $by_value);
		} else {
			$value = "";
		}
		$value = ((is_string($value) || is_numeric($value)) ? $value : '');
		$value = strtolower($value);
		switch (strtolower($by_type)) {
			case 'seq':
				$row = $this->get_sb_bank_by('seq', $value);
				if (isset($row->bank_code)) {
					$transaction_data = $this->suksesbugil->get_data_transaction_by('bank', $row->bank_code);
				} else {
					$transaction_data = FALSE;
				}
			break;
			case 'bank':
			default:
				try {
					$transaction_data = $this->suksesbugil->get_data_transaction_by('bank', $value);
				} catch (Exception $ex) {
					throw $ex;
					$transaction_data = FALSE;
				}
			break;
		}
		return $transaction_data;
	}
	function get_sb_trans_single_by($by_type, $by_value) {
		$by_type = (is_string($by_type) ? strtolower($by_type) : 'bank');
		$value = "";
		if (is_numeric($by_value) || is_string($by_value)) {
			$value = sprintf("%s", $by_value);
		} else {
			$value = "";
		}
		$value = ((is_string($value) || is_numeric($value)) ? $value : '');
		$value = strtolower($value);
		
		$this->db_mutasi->select('t.*, b.bank_code, b.bank_name, b.bank_scheduler_unit, b.bank_scheduler_amount, b.bank_is_active');
		$this->db_mutasi->from("{$this->mutasi_tables['sb_transaction']} t");
		$this->db_mutasi->join("{$this->mutasi_tables['bank']} b", 'b.seq = t.mutasi_bank_seq', 'left');
		switch (strtolower($by_type)) {
			case 'seq':
			default:
				$this->db_mutasi->where('t.seq', $value);
			break;
		}
		$this->db_mutasi->limit(1);
		try {
			$sql_query = $this->db_mutasi->get();
		} catch (Exception $ex) {
			throw $ex;
			return FALSE;
		}
		return $sql_query->row();
	}
	
	
	
	
	
	
	
	//------
	function parse($html_string) {
		$collectData = array(
			'html_string' 	=> $html_string,
			'collect'		=> array(),
		);
		//==== Load libxml
		libxml_use_internal_errors(true);
		$collectData['collect']['dom'] = new DOMDocument;
		$collectData['collect']['dom']->preserveWhiteSpace = false;
		$collectData['collect']['dom']->validateOnParse = false;
		$collectData['collect']['dom']->loadHTML($collectData['html_string']);
		$collectData['collect']['xpath'] = new DOMXPath($collectData['collect']['dom']);
		$collectData['collect']['queries'] = array(
			'table'		=> $collectData['collect']['xpath']->query("//table"),
			'form'		=> $collectData['collect']['xpath']->query("//form"),
		);
		$collectData['dom_form'] = array();
		if (isset($collectData['collect']['queries']['form'])) {
			if ((int)$collectData['collect']['queries']['form']->length > 0) {
				for ($i = 0; $i < $collectData['collect']['queries']['form']->length; $i++) {
					$collectData['dom_form'][] = $collectData['collect']['queries']['form']->item($i);
				}
			}
		}
		
		$collectData['dom_form_data'] = array();
		$collectData['dom_table_data'] = array();
		$collectData['dom_table_td'] = array();
		if (isset($collectData['dom_form'][0])) {
			foreach ($collectData['dom_form'][0]->getElementsByTagName('tr') as $i => $trnode) {
				$collectData['dom_table_data'][$i] = array(
					'nodepath'		=> $trnode->getNodePath()
				);
				$collectData['dom_table_data'][$i]['xpath'] = $collectData['collect']['xpath']->query($collectData['dom_table_data'][$i]['nodepath'] . '//td');
				$collectData['dom_table_data'][$i]['items'] = array();
				$collectData['dom_table_data'][$i]['childs'] = array();
				if (($collectData['dom_table_data'][$i]['xpath']->length) > 0) {
					for ($x_i = 0; $x_i < $collectData['dom_table_data'][$i]['xpath']->length; $x_i++) {
						$collectData['dom_table_data'][$i]['items'][$x_i] = $collectData['dom_table_data'][$i]['xpath']->item($x_i);
						$collectData['dom_table_data'][$i]['childs'][$x_i] = array(
							'parent'		=> array(),
							'items'			=> array(),
						);
						foreach($collectData['dom_table_data'][$i]['items'][$x_i]->childNodes as $item) {
							$collectData['dom_table_data'][$i]['childs'][$x_i]['parent'] = array(
								'nodepath'		=> $item->getNodePath(),
								'nodevalue'		=> $item->nodeValue,
								'nodename'		=> $item->nodeName,
								'parentpath'	=> $item->parentNode->getNodePath(),
							);
							if ($item->hasChildNodes()) {
								$collectData['dom_table_data'][$i]['childs'][$x_i]['parent']['is_have_child'] = 1;
							} else {
								$collectData['dom_table_data'][$i]['childs'][$x_i]['parent']['is_have_child'] = 0;
							}
							$collectData['dom_table_data'][$i]['childs'][$x_i]['parent']['inputs'] = $collectData['dom_table_data'][$i]['items'][$x_i]->getElementsByTagName('input');
							$collectData['dom_table_data'][$i]['childs'][$x_i]['parent']['input_params'] = array();
							if ($collectData['dom_table_data'][$i]['childs'][$x_i]['parent']['inputs']->length > 0) {
								$collectData['dom_table_data'][$i]['childs'][$x_i]['parent']['is_have_input'] = TRUE;
								for ($input_i = 0; $input_i < $collectData['dom_table_data'][$i]['childs'][$x_i]['parent']['inputs']->length; $input_i++) {
									$collectData['dom_table_data'][$i]['childs'][$x_i]['parent']['input_params'][$input_i] = $collectData['dom_table_data'][$i]['childs'][$x_i]['parent']['inputs']->item($input_i);
									$collectData['dom_table_data'][$i]['childs'][$x_i]['parent']['input_params']['attributes'] = array(
										'type'		=> $collectData['dom_table_data'][$i]['childs'][$x_i]['parent']['input_params'][$input_i]->getAttribute('type'),
										'name'		=> $collectData['dom_table_data'][$i]['childs'][$x_i]['parent']['input_params'][$input_i]->getAttribute('name'),
										'value'		=> $collectData['dom_table_data'][$i]['childs'][$x_i]['parent']['input_params'][$input_i]->getAttribute('value'),
										'id'		=> $collectData['dom_table_data'][$i]['childs'][$x_i]['parent']['input_params'][$input_i]->getAttribute('id'),
									);
								}
							} else {
								$collectData['dom_table_data'][$i]['childs'][$x_i]['parent']['is_have_input'] = FALSE;
							}
							if ($item->hasChildNodes()) {
								$childs = $item->childNodes;
								foreach($childs as $child) {
									$collectData['dom_table_data'][$i]['childs'][$x_i]['items'][] = array(
										'name'		=> $child->nodeName,
										'value'		=> $child->nodeValue
									);
								}
							}
						}
					}
				}
            }
		}
		return $collectData;		
	}
	//------------------------------------------------------------------------------------------------------
	function get_auto_approve_by_indexed() {
		$this->db_mutasi->select('*')->from($this->mutasi_tables['deposit_description']);
		$this->db_mutasi->where('auto_approve_status !=', 'all');
		try {
			$sql_query = $this->db_mutasi->get();
		} catch (Exception $ex) {
			throw $ex;
			return false;
		}
		return $sql_query->result();
	}
	function get_auto_approve_by($by_type, $by_value) {
		$by_type = (is_string($by_type) ? strtolower($by_type) : 'bank');
		$value = "";
		if (is_numeric($by_value) || is_string($by_value)) {
			$value = sprintf("%s", $by_value);
		} else {
			$value = "";
		}
		$value = ((is_string($value) || is_numeric($value)) ? $value : '');
		$value = strtolower($value);
		switch (strtolower($by_type)) {
			case 'status':
			case 'slug':
			case 'code':
				$value = sprintf("%s", $value);
			break;
			case 'id':
			case 'seq':
			default:
				if (!preg_match('/^[1-9][0-9]*$/', $by_value)) {
					$value = 0;
				} else {
					$value = sprintf('%d', $value);
				}
			break;
		}
		$this->db_mutasi->select('*')->from($this->mutasi_tables['deposit_description']);
		switch (strtolower($by_type)) {
			case 'status':
			case 'slug':
			case 'code':
				$this->db_mutasi->where('auto_approve_status', $value);
			break;
			case 'id':
			case 'seq':
			default:
				$this->db_mutasi->where('seq', $value);
			break;
		}
		$sql_query = $this->db_mutasi->get();
		return $sql_query->row();
	}
	function set_auto_approve_description($seq = 0, $input_params) {
		$seq = (int)$seq;
		$this->db_mutasi->where('seq', $seq);
		$this->db_mutasi->set('description_update', 'NOW()', FALSE);
		$this->db_mutasi->update($this->mutasi_tables['deposit_description'], $input_params);
		$affected_rows = $this->db_mutasi->affected_rows();
		return $affected_rows;
	}
	function set_bank_power_by_seq($bank_seq, $input_params) {
		$bank_seq = (is_numeric($bank_seq) ? (int)$bank_seq : 0);
		$this->db_mutasi->where('seq', $bank_seq);
		$this->db_mutasi->update($this->mutasi_tables['sb_bank'], $input_params);
		return $this->db_mutasi->affected_rows();
	}
	//-------------------------------------------------------------------------------------------------------
	
	
	
	
	
}



