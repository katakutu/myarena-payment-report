<?php 
if ( ! defined('BASEPATH')) { exit('No direct script access allowed: ' . (__FILE__)); }
class Model_paymentreport extends CI_Model {
	private $databases = array();
	protected $db_paymentreport;
	protected $paymentreport_tables = array();
	function __construct() {
		parent::__construct();
		$this->load->config('paymentreport/base_paymentreport');
		$this->base_paymentreport = $this->config->item('base_paymentreport');
		$this->load->library('dashboard/Lib_imzers', $this->base_paymentreport, 'imzers');
		$this->db_mutasi = $this->load->database('mutasi', TRUE);
		$this->db_paymentreport = $this->load->database('paymentreport', TRUE);
		$this->mutasi_tables = (isset($this->base_paymentreport['mutasi_tables']) ? $this->base_paymentreport['mutasi_tables'] : array());
		$this->paymentreport_tables = (isset($this->base_paymentreport['paymentreport_tables']) ? $this->base_paymentreport['paymentreport_tables'] : array());
		
	}
	
	function get_payment_providers() {
		return $this->db_paymentreport->get($this->paymentreport_tables['providers'])->result();
	}
	function get_all_yesterday_payments() {
		$sql = sprintf("SELECT p.payment_currency AS payment_currency, SUM(p.payment_unit) AS payment_units, SUM(p.payment_summary) AS payment_amounts
			FROM %s AS p WHERE (p.payment_date = DATE_ADD(CURDATE(), INTERVAL -1 DAY)) 
			GROUP BY p.payment_currency ORDER BY p.payment_currency ASC", $this->paymentreport_tables['report_payments']);
		try {
			$sql_query = $this->db_paymentreport->query($sql);
		} catch (Exception $ex) {
			throw $ex;
			return false;
		}
		return $sql_query->result();
	}
	function get_all_summaries_payments() {
		return $this->db_paymentreport->select('SUM(payment_unit) AS value')->get($this->paymentreport_tables['report_payments'])->row();
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	function get_bank() {
		return $this->db_mutasi->get($this->mutasi_tables['bank'])->result();
	}
	function get_bank_type_by_all() {
		$bank_type_all = new stdClass();
		$bank_type_all->seq = 0;
		$bank_type_all->bank_code = 'all';
		$bank_type_all->bank_name = 'All';
		$bank_type_all->bank_is_active = 'Y';
		$bank_type_all->bank_url_address = '-';
		$bank_type_all->bank_description = 'All available bank instance';
		$bank_type_all->bank_scheduler_unit = 'minute';
		$bank_type_all->bank_scheduler_amount = 5;
		$bank_type_all->bank_datetime_starting = '01:00:00';
		$bank_type_all->bank_datetime_stopping = '22:59:59';
		$bank_type_all->bank_restricted_datetime = 'Y';
		return $bank_type_all;
	}
	function get_bank_type_by($by_type, $by_value) {
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
				$value = sprintf("%s", $value);
			break;
			case 'seq':
			default:
				if (!preg_match('/^[1-9][0-9]*$/', $by_value)) {
					$value = 0;
				} else {
					$value = sprintf('%d', $value);
				}
			break;
		}
		$sql = sprintf("SELECT * FROM %s WHERE", $this->mutasi_tables['bank']);
		switch (strtolower($by_type)) {
			case 'code':
				$sql .= sprintf(" LOWER(bank_code) = LOWER('%s')", $this->db_mutasi->escape_str($value));
			break;
			case 'seq':
			default:
				$sql .= sprintf(" seq = '%d'", $this->db_mutasi->escape_str($value));
			break;
		}
		return $this->db_mutasi->query($sql)->row();
	}
	function set_bank_type_time_by($by_type, $by_value, $input_params) {
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
				$value = sprintf("%s", $value);
			break;
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
			case 'code':
				$this->db_mutasi->where('bank_code', $value);
			break;
			case 'seq':
			default:
				$this->db_mutasi->where('seq', $value);
			break;
		}
		$this->db_mutasi->update($this->mutasi_tables['bank'], $input_params);
		return $this->db_mutasi->affected_rows();
	}
	function get_transaction_types_to_show() {
		if (isset($this->base_paymentreport['transaction_types_to_show'])) {
			$transaction_types = $this->base_paymentreport['transaction_types_to_show'];
		} else {
			$transaction_types = array('all', 'deposit', 'transfer');
		}
		return $transaction_types;
	}
	//---------------------------------------------------------------------------------------------------------------------
	function get_transaction_daterange_by($by_type, $by_value, $daterange = array()) {
		$by_type = (is_string($by_type) ? strtolower($by_type) : 'seq');
		$value = "";
		if (isset($by_value)) {
			if (is_numeric($by_value)) {
				$value = sprintf("%d", $by_value);
			} else if (is_string($by_value)) {
				$value = sprintf("%s", $by_value);
			} else {
				$value = "";
			}
		}
		$value = ((is_string($value) || is_numeric($value)) ? $value : '');
		$dateobject = array();
		switch (strtolower($by_type)) {
			case 'daterange':
				$value = sprintf("%s", $value);
				if (is_array($daterange) && (count($daterange) > 0)) {
					if (isset($daterange['starting'])) {
						$dateobject['starting'] = new DateTime($daterange['starting']);
					}
					if (isset($daterange['stopping'])) {
						$dateobject['stopping'] = new DateTime($daterange['stopping']);
					}
				} else {
					$dateobject['starting'] = new DateTime();
					$dateobject['stopping'] = new DateTime();
				}
			break;
			case 'seq':
			default:
				if (!preg_match('/^[1-9][0-9]*$/', $by_value)) {
					$value = 0;
				} else {
					$value = sprintf('%d', $value);
				}
			break;
		}
		$this->db_mutasi->select('*')->from($this->mutasi_tables['transaction_daterange']);
		switch (strtolower($by_type)) {
			case 'daterange':
				$this->db_mutasi->where('date_starting', $dateobject['starting']->format('Y-m-d'));
				$this->db_mutasi->where('date_stopping', $dateobject['stopping']->format('Y-m-d'));
			break;
			case 'seq':
			default:
				$this->db_mutasi->where('seq', $value);
			break;
		}
		$sql_query = $this->db_mutasi->get();
		return $sql_query->row();
	}
	function set_transaction_daterange_by($by_type, $by_value, $daterange = array()) {
		$by_type = (is_string($by_type) ? strtolower($by_type) : 'seq');
		$value = "";
		if (isset($by_value)) {
			if (is_numeric($by_value)) {
				$value = sprintf("%d", $by_value);
			} else if (is_string($by_value)) {
				$value = sprintf("%s", $by_value);
			} else {
				$value = "";
			}
		}
		$value = ((is_string($value) || is_numeric($value)) ? $value : '');
		$dateobject = array();
		switch (strtolower($by_type)) {
			case 'daterange':
				$value = sprintf("%s", $value);
				if (is_array($daterange) && (count($daterange) > 0)) {
					if (isset($daterange['starting'])) {
						$dateobject['starting'] = new DateTime($daterange['starting']);
					}
					if (isset($daterange['stopping'])) {
						$dateobject['stopping'] = new DateTime($daterange['stopping']);
					}
				} else {
					$dateobject['starting'] = new DateTime();
					$dateobject['stopping'] = new DateTime();
				}
			break;
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
			case 'daterange':
				$this->db_mutasi->where('date_starting', $dateobject['starting']->format('Y-m-d'));
				$this->db_mutasi->where('date_stopping', $dateobject['stopping']->format('Y-m-d'));
			break;
			case 'seq':
			default:
				$this->db_mutasi->where('seq', $value);
			break;
		}
		$this->db_mutasi->set('insert_datetime', 'NOW()', FALSE);
		$dateobject_params = array(
			'date_starting'		=> $dateobject->format('Y-m-d'),
			'date_stopping'		=> $dateobject->format('Y-m-d'),
		);
		$this->db_mutasi->update($this->mutasi_tables['transaction_daterange'], $dateobject_params);
		return $this->db_mutasi->affected_rows();
	}
	function insert_transaction_daterange_by($daterange = array()) {
		$dateobject = array();
		if (is_array($daterange) && (count($daterange) > 0)) {
			if (isset($daterange['starting'])) {
				$dateobject['starting'] = new DateTime($daterange['starting']);
			}
			if (isset($daterange['stopping'])) {
				$dateobject['stopping'] = new DateTime($daterange['stopping']);
			}
		} else {
			$dateobject['starting'] = new DateTime();
			$dateobject['stopping'] = new DateTime();
		}
		$dateobject_params = array(
			'date_starting'		=> $dateobject->format('Y-m-d'),
			'date_stopping'		=> $dateobject->format('Y-m-d'),
		);
		$this->db_mutasi->set('insert_datetime', 'NOW()', FALSE);
		$this->db_mutasi->insert($this->mutasi_tables['transaction_daterange'], $dateobject_params);
		$new_insert_seq = $this->db_mutasi->insert_id();
		return $new_insert_seq;
	}
	//---------------------------------------------------------------------------------------------------------------------
	function get_all_bank_account_count_by($is_active = 0, $search_text = '')  {
		$is_active = (is_numeric($is_active) ? (int)$is_active : 0);
		$sql = sprintf("SELECT COUNT(acc.seq) AS value FROM %s AS acc", $this->mutasi_tables['bank_account']);
		if ($is_active > 0) {
			$sql .= sprintf(" WHERE acc.account_is_active = '%s'", 'Y');
		} else {
			$sql .= " WHERE acc.account_is_active IN('Y', 'N')";
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
						$sql_likes .= " AND (CONCAT('', acc.account_username, '') LIKE '%{$this->db_mutasi->escape_str($val)}%')";
					} else {
						$sql_likes .= " (CONCAT('', acc.account_username, '') LIKE '%{$this->db_mutasi->escape_str($val)}%')";
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
	function get_all_bank_account_data_by($is_active = 0, $search_text = '', $start = 0, $per_page = 10)  {
		$is_active = (is_numeric($is_active) ? (int)$is_active : 0);
		$sql = sprintf("SELECT * FROM %s AS acc", $this->mutasi_tables['bank_account']);
		if ($is_active > 0) {
			$sql .= sprintf(" WHERE acc.account_is_active = '%s'", 'Y');
		} else {
			$sql .= " WHERE acc.account_is_active IN('Y', 'N')";
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
						$sql_likes .= " AND (CONCAT('', acc.account_username, '') LIKE '%{$this->db_mutasi->escape_str($val)}%')";
					} else {
						$sql_likes .= " (CONCAT('', acc.account_username, '') LIKE '%{$this->db_mutasi->escape_str($val)}%')";
					}
					$for_i++;
				}	
			} else {
				$sql_likes .= " 1=1";
			}
			$sql_likes .= ")";
			$sql .= $sql_likes;
		}
		$sql .= " ORDER BY acc.account_ordering ASC";
		$sql .= sprintf(" LIMIT %d, %d", $start, $per_page);
		$sql_query = $this->db_mutasi->query($sql);
		return $sql_query->result();
    }
	//--
	function get_bank_account_count_by($by_type, $by_value, $search_text = '')  {
		$by_type = (is_string($by_type) ? strtolower($by_type) : 'bank');
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
			case 'create_by':
			case 'edit_by':
			case 'all':
				$value = sprintf("%s", $value);
			break;
			case 'bank':
			default:
				if (!preg_match('/^[1-9][0-9]*$/', $by_value)) {
					$value = 0;
				} else {
					$value = sprintf('%d', $value);
				}
			break;
		}
		$sql = sprintf("SELECT COUNT(rek.seq) AS value FROM %s AS rek LEFT JOIN %s AS acc ON rek.account_seq = acc.seq WHERE",
			$this->mutasi_tables['bank_rekening'],
			$this->mutasi_tables['bank_account']
		);
		$sql_wheres = "";
		switch (strtolower($by_type)) {
			case 'create_by':
				$sql_wheres .= sprintf(" acc.account_by_add = '%s'", $this->db_mutasi->escape_str($value));
			break;
			case 'edit_by':
				$sql_wheres .= sprintf(" acc.account_by_edit = '%s'", $this->db_mutasi->escape_str($value));
			break;
			case 'all':
				$sql_wheres .= sprintf(" acc.bank_seq > '%d'", 0);
			break;
			case 'bank':
			default:
				$sql_wheres .= sprintf(" acc.bank_seq = '%d'", $this->db_mutasi->escape_str($value));
			break;
		}
		$sql .= $sql_wheres;
		$search_text = (is_string($search_text) ? $search_text : '');
		if (!empty($search_text) && (strlen($search_text) > 0)) {
			$sql_likes = " AND (";
			$search_array = base_permalink($search_text);
			$search_array = explode("-", $search_array);
			if (count($search_array) > 0) {
				$for_i = 0;
				foreach ($search_array as $val) {
					if ($for_i > 0) {
						$sql_likes .= " AND (CONCAT('', acc.account_username, '') LIKE '%{$this->db_mutasi->escape_str($val)}%' OR CONCAT('', rek.rekening_number, '') LIKE '%{$this->db_mutasi->escape_str($val)}%' OR CONCAT('', rek.rekening_name, '') LIKE '%{$this->db_mutasi->escape_str($val)}%')";
					} else {
						$sql_likes .= " (CONCAT('', acc.account_username, '') LIKE '%{$this->db_mutasi->escape_str($val)}%' OR CONCAT('', rek.rekening_number, '') LIKE '%{$this->db_mutasi->escape_str($val)}%' OR CONCAT('', rek.rekening_name, '') LIKE '%{$this->db_mutasi->escape_str($val)}%')";
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
	function get_bank_account_data_by($by_type, $by_value, $search_text = '', $start = 0, $per_page = 10) {
		$by_type = (is_string($by_type) ? strtolower($by_type) : 'bank');
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
			case 'create_by':
			case 'edit_by':
			case 'all':
				$value = sprintf("%s", $value);
			break;
			case 'bank':
			default:
				if (!preg_match('/^[1-9][0-9]*$/', $by_value)) {
					$value = 0;
				} else {
					$value = sprintf('%d', $value);
				}
			break;
		}
		$sql = sprintf("SELECT rek.seq AS rekening_seq, rek.rekening_number, rek.rekening_name, rek.rekening_is_active, rek.rekening_owner, acc.*, bank.bank_code, bank.bank_name FROM %s AS rek LEFT JOIN %s AS acc ON acc.seq = rek.account_seq LEFT JOIN %s AS bank ON bank.seq = acc.bank_seq WHERE",
			$this->mutasi_tables['bank_rekening'],
			$this->mutasi_tables['bank_account'],
			$this->mutasi_tables['bank']
		);
		switch (strtolower($by_type)) {
			case 'create_by':
				$sql_wheres = sprintf(" acc.account_by_add = '%s'", $this->db_mutasi->escape_str($value));
			break;
			case 'edit_by':
				$sql_wheres = sprintf(" acc.account_by_edit = '%s'", $this->db_mutasi->escape_str($value));
			break;
			case 'all':
				$sql_wheres = sprintf(" acc.bank_seq > '%d'", 0);
			break;
			case 'bank':
			default:
				$sql_wheres = sprintf(" acc.bank_seq = '%d'", $this->db_mutasi->escape_str($value));
			break;
		}
		$sql .= $sql_wheres;
		$search_text = (is_string($search_text) ? $search_text : '');
		if (!empty($search_text) && (strlen($search_text) > 0)) {
			$sql_likes = " AND (";
			$search_array = base_permalink($search_text);
			$search_array = explode("-", $search_array);
			if (count($search_array) > 0) {
				$for_i = 0;
				foreach ($search_array as $val) {
					if ($for_i > 0) {
						$sql_likes .= " AND (CONCAT('', acc.account_username, '') LIKE '%{$this->db_mutasi->escape_str($value)}%' OR CONCAT('', rek.rekening_number, '') LIKE '%{$this->db_mutasi->escape_str($value)}%' OR CONCAT('', rek.rekening_name, '') LIKE '%{$this->db_mutasi->escape_str($value)}%')";
					} else {
						$sql_likes .= " (CONCAT('', acc.account_username, '') LIKE '%{$this->db_mutasi->escape_str($value)}%' OR CONCAT('', rek.rekening_number, '') LIKE '%{$this->db_mutasi->escape_str($value)}%' OR CONCAT('', rek.rekening_name, '') LIKE '%{$this->db_mutasi->escape_str($value)}%')";
					}
					$for_i++;
				}
			} else {
				$sql_likes .= " 1=1";
			}
			$sql_likes .= ")";
			$sql .= $sql_likes;
		}
		$sql .= " ORDER BY acc.account_ordering ASC, acc.seq ASC";
		$sql .= sprintf(" LIMIT %d, %d", $start, $per_page);
		$sql_query = $this->db_mutasi->query($sql);
		return $sql_query->result();
	}
	//---------------------------------------------------
	function get_bank_instance_count_by($by_type, $by_value, $search_text = '')  {
		$by_type = (is_string($by_type) ? strtolower($by_type) : 'bank');
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
			case 'is_active':
			case 'scheduler_unit':
				$value = sprintf("%s", $value);
			break;
			case 'all':
			default:
				if (!preg_match('/^[1-9][0-9]*$/', $by_value)) {
					$value = 0;
				} else {
					$value = sprintf('%d', $value);
				}
			break;
		}
		$sql = sprintf("SELECT COUNT(b.seq) AS value FROM %s AS b WHERE", $this->mutasi_tables['bank']);
		$sql_wheres = "";
		switch (strtolower($by_type)) {
			case 'is_active':
				$sql_wheres .= sprintf(" b.bank_is_active = '%s'", $this->db_mutasi->escape_str($value));
			break;
			case 'scheduler_unit':
				$sql_wheres .= sprintf(" b.bank_scheduler_unit = '%s'", $this->db_mutasi->escape_str($value));
			break;
			case 'all':
			default:
				$sql_wheres .= sprintf(" b.seq > '%d'", $this->db_mutasi->escape_str($value));
			break;
		}
		$sql .= $sql_wheres;
		$search_text = (is_string($search_text) ? $search_text : '');
		if (!empty($search_text) && (strlen($search_text) > 0)) {
			$sql_likes = " AND (";
			$search_array = base_permalink($search_text);
			$search_array = explode("-", $search_array);
			if (count($search_array) > 0) {
				$for_i = 0;
				foreach ($search_array as $val) {
					if ($for_i > 0) {
						$sql_likes .= " AND (CONCAT('', b.bank_name, '') LIKE '%{$this->db_mutasi->escape_str($val)}%')";
					} else {
						$sql_likes .= " (CONCAT('', b.bank_name, '') LIKE '%{$this->db_mutasi->escape_str($val)}%')";
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
	function get_bank_instance_data_by($by_type, $by_value, $search_text = '', $start = 0, $per_page = 10)  {
		$by_type = (is_string($by_type) ? strtolower($by_type) : 'bank');
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
			case 'is_active':
			case 'scheduler_unit':
				$value = sprintf("%s", $value);
			break;
			case 'all':
			default:
				if (!preg_match('/^[1-9][0-9]*$/', $by_value)) {
					$value = 0;
				} else {
					$value = sprintf('%d', $value);
				}
			break;
		}
		$sql = sprintf("SELECT b.* FROM %s AS b WHERE", $this->mutasi_tables['bank']);
		$sql_wheres = "";
		switch (strtolower($by_type)) {
			case 'is_active':
				$sql_wheres .= sprintf(" b.bank_is_active = '%s'", $this->db_mutasi->escape_str($value));
			break;
			case 'scheduler_unit':
				$sql_wheres .= sprintf(" b.bank_scheduler_unit = '%s'", $this->db_mutasi->escape_str($value));
			break;
			case 'all':
			default:
				$sql_wheres .= sprintf(" b.seq > '%d'", $this->db_mutasi->escape_str($value));
			break;
		}
		$sql .= $sql_wheres;
		$search_text = (is_string($search_text) ? $search_text : '');
		if (!empty($search_text) && (strlen($search_text) > 0)) {
			$sql_likes = " AND (";
			$search_array = base_permalink($search_text);
			$search_array = explode("-", $search_array);
			if (count($search_array) > 0) {
				$for_i = 0;
				foreach ($search_array as $val) {
					if ($for_i > 0) {
						$sql_likes .= " AND (CONCAT('', b.bank_name, '') LIKE '%{$this->db_mutasi->escape_str($val)}%')";
					} else {
						$sql_likes .= " (CONCAT('', b.bank_name, '') LIKE '%{$this->db_mutasi->escape_str($val)}%')";
					}
					$for_i++;
				}	
			} else {
				$sql_likes .= " 1=1";
			}
			$sql_likes .= ")";
			$sql .= $sql_likes;
		}
		$sql .= " ORDER BY b.bank_name ASC";
		$sql .= sprintf(" LIMIT %d, %d", $start, $per_page);
		$sql_query = $this->db_mutasi->query($sql);
		return $sql_query->result();
    }
	//----------------------------------------------------------------------------------------------------------------------------
	//============================================================================================================================
	// Menu Item: Insert, Update, Delete
	function insert_bank_account_by($by_bank, $by_value, $input_params = array()) {
		$by_bank = (is_string($by_bank) ? strtolower($by_bank) : 'seq');
		$value = "";
		if (isset($by_value)) {
			if (is_numeric($by_value) || is_string($value)) {
				$value = sprintf("%s", $by_value);
			}
		}
		$value = ((is_string($value) || is_numeric($value)) ? $value : '');
		switch (strtolower($by_bank)) {
			case 'code':
			case 'slug':
				if (!preg_match('/^[a-z0-9_\-]*$/', $value)) {
					$value = '';
				} else {
					$value = sprintf('%s', $value);
				}
			break;
			case 'seq':
			case 'id':
			case 'all':
			default:
				if (!preg_match('/^[1-9][0-9]*$/', $value)) {
					$value = 0;
				} else {
					$value = sprintf('%d', $value);
				}
			break;
		}
		$sql = sprintf("SELECT a.seq AS value FROM %s AS a LEFT JOIN %s AS b ON b.seq = a.bank_seq WHERE", $this->mutasi_tables['bank_account'], $this->mutasi_tables['bank']);
		switch (strtolower($by_bank)) {
			case 'code':
			case 'slug':
				$sql .= sprintf(" b.bank_code = '%s'", $this->db_mutasi->escape_str($value));
			break;
			case 'seq':
			case 'id':
			default:
				$sql .= sprintf(" b.seq = '%d'", $this->db_mutasi->escape_str($value));
			break;
		}
		$query_params = array(
			'bank_seq'						=> (isset($input_params['bank_seq']) ? $input_params['bank_seq'] : 0),
			'account_title'					=> (isset($input_params['account_title']) ? $input_params['account_title'] : ''),
			'account_slug'					=> (isset($input_params['account_slug']) ? $input_params['account_slug'] : ''),
			'account_username'				=> (isset($input_params['account_username']) ? $input_params['account_username'] : ''),
			'account_password'				=> (isset($input_params['account_password']) ? $input_params['account_password'] : ''),
			'account_edit_datetime'			=> (isset($input_params['account_edit_datetime']) ? $input_params['account_edit_datetime'] : date('Y-m-d H:i:s')),
			'account_is_multiple_rekening'	=> (isset($input_params['account_is_multiple_rekening']) ? $input_params['account_is_multiple_rekening'] : 'N'),
			'account_is_active'				=> (isset($input_params['account_is_active']) ? $input_params['account_is_active'] : 'N'),
			'account_ordering'				=> (isset($input_params['account_ordering']) ? $input_params['account_ordering'] : 0),
			'account_owner'					=> (isset($input_params['account_owner']) ? $input_params['account_owner'] : 0),
			
			'account_by_add'	=> (isset($this->authentication->localdata['account_email']) ? $this->authentication->localdata['account_email'] : 'system@root'),
			'account_by_edit'	=> (isset($this->authentication->localdata['account_email']) ? $this->authentication->localdata['account_email'] : 'system@root'),
		);
		$sql .= sprintf(" AND account_username = '%s'", $this->db_mutasi->escape_str($query_params['account_username']));
		$sql_query = $this->db_mutasi->query($sql);
		$rows = $sql_query->row();
		if (isset($rows->value)) {
			return FALSE;
		}
		//=== Check Bank Account Login
		switch (strtolower($by_bank)) {
			case 'code':
			case 'slug':
				$bank_code = strtolower($value);
			break;
			case 'seq':
			case 'id':
			default:
				$bank_data = $this->get_bank_type_by('seq', $value);
				if (!isset($bank_data->bank_code)) {
					$bank_code = 'bca';
				} else {
					$bank_code = strtolower($bank_data->bank_code);
				}
			break;
		}
		switch (strtolower($bank_code)) {
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
		try {
			$login_page = $this->lib_bank->login($query_params['account_username'], $query_params['account_password']);
			switch (strtolower($bank_code)) {
				case 'bni':
					if (isset($login_page['form_data']['form_logout']['url']) && isset($login_page['form_data']['form_logout']['query_params'])) {
						$informasi_rekening = $this->lib_bank->get_informasi_rekening($login_page['form_data']['form_logout']['url'], $login_page['form_data']['form_logout']['query_params']);
						$this->lib_bank->logout($login_page['form_data']['form_logout']['url'], $login_page['form_data']['form_logout']['query_params']);
					}
				break;
				case 'bca':
				case 'mandiri':
				case 'bri':
				default:
					$informasi_rekening = $this->lib_bank->get_informasi_rekening();
					$this->lib_bank->logout();
				break;
			}
		} catch (Exception $ex) {
			throw $ex;
			return FALSE;
		}
		if ($informasi_rekening != FALSE) {
			$rekening_params = array(
				'account_seq' => 0,
				'bank_seq' => $query_params['bank_seq'],
				'rekening_number' => sprintf("%s", $informasi_rekening),
				'rekening_name' => base_permalink($query_params['account_title']),
				'rekening_is_active' => 'Y',
				'rekening_owner' => (isset($this->authentication->localdata['seq']) ? $this->authentication->localdata['seq'] : 0),
				'rekening_by_add' => (isset($this->authentication->localdata['account_email']) ? $this->authentication->localdata['account_email'] : 'system@root'),
				'rekening_by_edit' => (isset($this->authentication->localdata['account_email']) ? $this->authentication->localdata['account_email'] : 'system@root'),
				'rekening_edit_datetime' => date('Y-m-d H:i:s'),
			);
			try {
				$this->db_mutasi->trans_start();
				$this->db_mutasi->insert($this->mutasi_tables['bank_account'], $query_params);
				$rekening_params['account_seq'] = $this->db_mutasi->insert_id();
				$this->db_mutasi->trans_complete();
				$this->db_mutasi->trans_start();
				$this->db_mutasi->insert($this->mutasi_tables['bank_rekening'], $rekening_params);
				$this->db_mutasi->trans_complete();
			} catch (Exception $ex) {
				throw $ex;
				$rekening_params['account_seq'] = 0;
			}
			return $rekening_params['account_seq'];
		} else {
			return FALSE;
		}
	}
	function set_bank_account_by($by_type, $by_value, $input_params = array()) {
		$by_type = (is_string($by_type) ? strtolower($by_type) : 'seq');
		$value = "";
		if (isset($by_value)) {
			if (is_numeric($by_value) || is_string($value)) {
				$value = sprintf("%s", $by_value);
			}
		}
		$value = ((is_string($value) || is_numeric($value)) ? $value : '');
		switch (strtolower($by_type)) {
			case 'code':
			case 'slug':
				if (!preg_match('/^[a-z0-9_\-]*$/', $value)) {
					$value = '';
				} else {
					$value = sprintf('%s', $value);
				}
			break;
			case 'seq':
			case 'id':
			case 'all':
			default:
				if (!preg_match('/^[1-9][0-9]*$/', $value)) {
					$value = 0;
				} else {
					$value = sprintf('%d', $value);
				}
			break;
		}
		$query_params = array(
			'bank_seq'						=> (isset($input_params['bank_seq']) ? $input_params['bank_seq'] : 0),
			'account_title'					=> (isset($input_params['account_title']) ? $input_params['account_title'] : ''),
			'account_slug'					=> (isset($input_params['account_slug']) ? $input_params['account_slug'] : ''),
			'account_username'				=> (isset($input_params['account_username']) ? $input_params['account_username'] : ''),
			'account_password'				=> (isset($input_params['account_password']) ? $input_params['account_password'] : ''),
			'account_edit_datetime'			=> (isset($input_params['account_edit_datetime']) ? $input_params['account_edit_datetime'] : date('Y-m-d H:i:s')),
			'account_is_multiple_rekening'	=> (isset($input_params['account_is_multiple_rekening']) ? $input_params['account_is_multiple_rekening'] : 'N'),
			'account_is_active'				=> (isset($input_params['account_is_active']) ? $input_params['account_is_active'] : 'N'),
			'account_ordering'				=> (isset($input_params['account_ordering']) ? $input_params['account_ordering'] : 0),
			'account_owner'					=> (isset($input_params['account_owner']) ? $input_params['account_owner'] : 0),
			
			'account_by_edit'	=> (isset($this->authentication->localdata['account_email']) ? $this->authentication->localdata['account_email'] : 'system@root'),
		);
		switch (strtolower($by_type)) {
			case 'code':
			case 'slug':
				$this->db_mutasi->where('account_slug', $this->db_mutasi->escape_str($value));
			break;
			case 'seq':
			case 'id':
			case 'all':
			default:
				$this->db_mutasi->where('seq', $this->db_mutasi->escape_str($value));
			break;
		}
		$this->db_mutasi->update($this->mutasi_tables['bank_account'], $query_params);
		$affected_rows = $this->db_mutasi->affected_rows();
		return $affected_rows;
	}
	function set_bank_account_power_by($by_type, $by_value, $input_params = array()) {
		$by_type = (is_string($by_type) ? strtolower($by_type) : 'seq');
		$value = "";
		if (isset($by_value)) {
			if (is_numeric($by_value) || is_string($value)) {
				$value = sprintf("%s", $by_value);
			}
		}
		$value = ((is_string($value) || is_numeric($value)) ? $value : '');
		switch (strtolower($by_type)) {
			case 'code':
			case 'slug':
				if (!preg_match('/^[a-z0-9_\-]*$/', $value)) {
					$value = '';
				} else {
					$value = sprintf('%s', $value);
				}
			break;
			case 'seq':
			case 'id':
			case 'all':
			default:
				if (!preg_match('/^[1-9][0-9]*$/', $value)) {
					$value = 0;
				} else {
					$value = sprintf('%d', $value);
				}
			break;
		}
		switch (strtolower($by_type)) {
			case 'code':
			case 'slug':
				$this->db_mutasi->where('account_slug', $this->db_mutasi->escape_str($value));
			break;
			case 'seq':
			case 'id':
			case 'all':
			default:
				$this->db_mutasi->where('seq', $this->db_mutasi->escape_str($value));
			break;
		}
		$this->db_mutasi->update($this->mutasi_tables['bank_account'], $input_params);
		$affected_rows = $this->db_mutasi->affected_rows();
		return $affected_rows;
	}
	function insert_mutasi_log($before, $after) {
		$query_params = array(
			'log_data_before'		=> json_encode($before, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
			'log_data_after'		=> json_encode($after, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
		);
		$this->db_mutasi->set('log_datetime', 'NOW()', FALSE);
		$this->db_mutasi->insert($this->mutasi_tables['log_mutasi_account'], $query_params);
	}
	//==============================================================
	// Check by controller
	function get_bank_account_item_single_with_type_seq($type_seq, $by_type, $by_value, $is_item_seq = 0) {
		$type_seq = (int)$type_seq;
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
			case 'title':
			case 'code':
			case 'slug':
				$value = sprintf("%s", $value);
			break;
			case 'seq':
			default:
				if (!preg_match('/^[1-9][0-9]*$/', $by_value)) {
					$value = 0;
				} else {
					$value = sprintf('%d', $value);
				}
			break;
		}
		$sql = sprintf("SELECT seq AS value FROM %s WHERE (", $this->mutasi_tables['bank_account']);
		$sql .= sprintf("bank_seq = '%d'", $this->db_mutasi->escape_str($type_seq));
		switch (strtolower($by_type)) {
			case 'title':
				$sql .= sprintf(" AND account_title = '%s'", $this->db_mutasi->escape_str($value));
			break;
			case 'code':
			case 'slug':
				$sql .= sprintf(" AND account_slug = '%s'", $this->db_mutasi->escape_str($value));
			break;
			case 'seq':
			default:
				$sql .= sprintf(" AND seq = '%d'", $this->db_mutasi->escape_str($value));
			break;
		}
		$sql .= ")";
		if ($is_item_seq > 0) {
			if ($by_type !== 'seq') {
				$sql .= sprintf(" AND (seq != '%d')", $is_item_seq);
			}
		}
		$sql_query = $this->db_mutasi->query($sql);
		while ($row = $sql_query->result()) {
			return $row;
		}
		return false;
	}
	function check_account_owner($account_seq, $owner_seq = null) {
		if (!isset($owner_seq)) {
			$owner_seq = (isset($this->authentication->localdata['seq']) ? $this->authentication->localdata['seq'] : 0);
		}
		$account_seq = (is_numeric($account_seq) ? (int)$account_seq : 0);
		$sql = sprintf("SELECT * FROM %s WHERE seq = '%d'", $this->mutasi_tables['bank_account'], $this->db_mutasi->escape_str($account_seq));
		$sql_query = $this->db_mutasi->query($sql);
		$row = $sql_query->row();
		if (isset($row->account_owner)) {
			if ($row->account_owner === $owner_seq) {
				return true;
			} else {
				return false;
			}
		}
		return FALSE;
	}
	//========== Get account data by
	function get_account_item_by($by_type, $by_value, $input_params = array()) {
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
				$sql .= sprintf(" WHERE r.rekening_number = '%s'", $this->db_mutasi->escape_str($value));
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
	function get_account_item_single_by($by_type, $by_value) {
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
				$value = sprintf("%s", $value);
			break;
			case 'seq':
			default:
				if (!preg_match('/^[1-9][0-9]*$/', $by_value)) {
					$value = 0;
				} else {
					$value = sprintf('%d', $value);
				}
			break;
		}
		$sql = sprintf("SELECT a.*, b.seq AS b_bank_seq, b.bank_code, b.bank_name, b.bank_restricted_datetime FROM %s AS a INNER JOIN %s AS b ON b.seq = a.bank_seq",
			$this->mutasi_tables['bank_account'],
			$this->mutasi_tables['bank']
		);
		switch (strtolower($by_type)) {
			case 'code':
			case 'slug':
				$sql .= sprintf(" WHERE a.account_slug = '%s'", $this->db_mutasi->escape_str($value));
			break;
			case 'seq':
			default:
				$sql .= sprintf(" WHERE a.seq = '%d'", $this->db_mutasi->escape_str($value));
			break;
		}
		$sql .= " LIMIT 1";
		$row = $this->db_mutasi->query($sql)->row();
		if (isset($row->seq)) {
			$row->rekening_data = $this->get_rekening_data_by('account_seq', $row->seq);
		}
		return $row;
	}
	function get_rekening_data_by($by_type, $by_value) {
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
			case 'account_slug':
			case 'code':
			case 'slug':
				$value = sprintf("%s", $value);
			break;
			case 'account_seq':
			case 'seq':
			default:
				if (!preg_match('/^[1-9][0-9]*$/', $by_value)) {
					$value = 0;
				} else {
					$value = sprintf('%d', $value);
				}
			break;
		}
		$sql = sprintf("SELECT r.*, a.seq AS a_account_seq, a.account_slug, a.account_username, a.account_password, a.account_owner, a.account_ordering, a.account_is_multiple_rekening, a.account_is_active FROM %s AS r INNER JOIN %s AS a ON a.seq = r.account_seq", 
			$this->mutasi_tables['bank_rekening'],
			$this->mutasi_tables['bank_account']
		);
		switch (strtolower($by_type)) {
			case 'account_seq':
				$sql .= sprintf(" WHERE r.account_seq = '%d'", $this->db_mutasi->escape_str($value));
			break;
			case 'account_slug':
			case 'code':
			case 'slug':
				$sql .= sprintf(" WHERE a.account_slug = '%s'", $this->db_mutasi->escape_str($value));
			break;
			case 'seq':
			default:
				$sql .= sprintf(" WHERE r.seq = '%d'", $this->db_mutasi->escape_str($value));
			break;
		}
		try {
			$sql_query = $this->db_mutasi->query($sql);
		} catch (Exception $ex) {
			throw $ex;
			return FALSE;
		}
		return $sql_query->result();
	}
	// +++++++++++++++++++++++++++++++++++++++
	// Bank Mutasi Transactions
	// +++++++++++++++++++++++++++++++++++++++
	//==============================================================
	function get_bank_mutasi_transaction_count_by($by_type, $by_value, $transaction_date = array(), $search_text = '') {
		$by_type = (is_string($by_type) ? strtolower($by_type) : 'seq');
		$value = "";
		if (isset($by_value)) {
			if (is_numeric($by_value) || is_string($value)) {
				$value = sprintf("%s", $by_value);
			}
		}
		$value = ((is_string($value) || is_numeric($value)) ? $value : '');
		switch (strtolower($by_type)) {
			case 'code':
			case 'slug':
				if (!preg_match('/^[a-z0-9_\-]*$/', $value)) {
					$value = '';
				} else {
					$value = sprintf('%s', $value);
				}
			break;
			case 'rekening_seq':
			case 'account_seq':
			case 'seq':
			case 'id':
			default:
				if (!preg_match('/^[1-9][0-9]*$/', $value)) {
					$value = 0;
				} else {
					$value = sprintf('%d', $value);
				}
			break;
		}
		$sql_dates = array();
		if (isset($transaction_date['starting'])) {
			$sql_dates['starting'] = $transaction_date['starting']->format('Y-m-d');
		}
		if (isset($transaction_date['stopping'])) {
			$sql_dates['stopping'] = $transaction_date['stopping']->format('Y-m-d');
		}
		switch (strtolower($by_type)) {
			case 'code':
			case 'slug':
				$sql = sprintf("SELECT COUNT(t.seq) AS value FROM %s AS t INNER JOIN %s AS a ON a.seq = t.account_seq",
					$this->mutasi_tables['rekening_transaction'],
					$this->mutasi_tables['bank_account']
				);
				$sql .= sprintf(" WHERE (a.account_slug = '%s')", $this->db_mutasi->escape_str($value));
			break;
			case 'rekening_seq':
				$sql = sprintf("SELECT COUNT(t.seq) AS value FROM %s AS t INNER JOIN %s AS r ON r.seq = t.rekening_seq",
					$this->mutasi_tables['rekening_transaction'],
					$this->mutasi_tables['bank_rekening']
				);
				$sql .= sprintf(" WHERE (t.rekening_seq = '%d')", $this->db_mutasi->escape_str($value));
			break;
			case 'account_seq':
				$sql = sprintf("SELECT COUNT(t.seq) AS value FROM %s AS t INNER JOIN %s AS a ON a.seq = t.account_seq",
					$this->mutasi_tables['rekening_transaction'],
					$this->mutasi_tables['bank_account']
				);
				$sql .= sprintf(" WHERE (t.account_seq = '%d')", $this->db_mutasi->escape_str($value));
			break;
			case 'seq':
			default:
				$sql = sprintf("SELECT COUNT(t.seq) AS value FROM %s AS t INNER JOIN %s AS r ON r.seq = t.rekening_seq LEFT JOIN %s AS a ON a.seq = r.account_seq",
					$this->mutasi_tables['rekening_transaction'],
					$this->mutasi_tables['bank_rekening'],
					$this->mutasi_tables['bank_account']
				);
				$sql .= sprintf(" WHERE (t.seq = '%d')", $this->db_mutasi->escape_str($value));
			break;
		}
		$sql_wheres = "";
		if (count($sql_dates) > 0) {
			if (strtotime($sql_dates['starting']) && strtotime($sql_dates['stopping'])) {
				$sql_wheres .= " AND (";
				$sql_wheres .= sprintf("t.transaction_date BETWEEN '%s' AND '%s'", $sql_dates['starting'], $sql_dates['stopping']);
				$sql_wheres .= ")";
			} else {
				$sql_wheres .= " AND (t.transaction_date = CURDATE())";
			}
			$sql .= $sql_wheres;
		}
		$sql_likes = "";
		$search_text = (is_string($search_text) ? $search_text : '');
		if (!empty($search_text) && (strlen($search_text) > 0)) {
			$sql_likes = " AND (";
			$search_array = base_permalink($search_text);
			$search_array = explode("-", $search_array);
			if (count($search_array) > 0) {
				$for_i = 0;
				foreach ($search_array as $val) {
					if ($for_i > 0) {
						$sql_likes .= " AND (CONCAT('', t.transaction_details, '') LIKE '%{$this->db_mutasi->escape_str($val)}%' OR CONCAT('', t.transaction_informasi_rekening, '') LIKE '%{$this->db_mutasi->escape_str($val)}%')";
					} else {
						$sql_likes .= " (CONCAT('', t.transaction_details, '') LIKE '%{$this->db_mutasi->escape_str($val)}%' OR CONCAT('', t.transaction_informasi_rekening, '') LIKE '%{$this->db_mutasi->escape_str($val)}%')";
					}
					$for_i++;
				}	
			} else {
				$sql_likes .= " 1=1";
			}
			$sql_likes .= ")";
			$sql .= $sql_likes;
		}
		// Only is_deleted = N
		$sql .= " AND (t.is_deleted = 'N')";
		try {
			$sql_query = $this->db_mutasi->query($sql);
		} catch (Exception $ex) {
			throw $ex;
			$ret = new stdClass();
			$ret->value = 0;
			return $ret;
		}
		return $sql_query->row();
	}
	function get_bank_mutasi_transaction_data_by($by_type, $by_value, $transaction_date = array(), $search_text = '', $start = 0, $per_page = 10) {
		$by_type = (is_string($by_type) ? strtolower($by_type) : 'seq');
		$value = "";
		if (isset($by_value)) {
			if (is_numeric($by_value) || is_string($value)) {
				$value = sprintf("%s", $by_value);
			}
		}
		$value = ((is_string($value) || is_numeric($value)) ? $value : '');
		switch (strtolower($by_type)) {
			case 'code':
			case 'slug':
				if (!preg_match('/^[a-z0-9_\-]*$/', $value)) {
					$value = '';
				} else {
					$value = sprintf('%s', $value);
				}
			break;
			case 'rekening_seq':
			case 'account_seq':
			case 'seq':
			case 'id':
			default:
				if (!preg_match('/^[1-9][0-9]*$/', $value)) {
					$value = 0;
				} else {
					$value = sprintf('%d', $value);
				}
			break;
		}
		$sql_dates = array();
		if (isset($transaction_date['starting'])) {
			$sql_dates['starting'] = $transaction_date['starting']->format('Y-m-d');
		}
		if (isset($transaction_date['stopping'])) {
			$sql_dates['stopping'] = $transaction_date['stopping']->format('Y-m-d');
		}
		switch (strtolower($by_type)) {
			case 'code':
			case 'slug':
				$sql = sprintf("SELECT t.* FROM %s AS t INNER JOIN %s AS a ON a.seq = t.account_seq",
					$this->mutasi_tables['rekening_transaction'],
					$this->mutasi_tables['bank_account']
				);
				$sql .= sprintf(" WHERE (a.account_slug = '%s')", $this->db_mutasi->escape_str($value));
			break;
			case 'rekening_seq':
				$sql = sprintf("SELECT t.* FROM %s AS t INNER JOIN %s AS r ON r.seq = t.rekening_seq",
					$this->mutasi_tables['rekening_transaction'],
					$this->mutasi_tables['bank_rekening']
				);
				$sql .= sprintf(" WHERE (t.rekening_seq = '%d')", $this->db_mutasi->escape_str($value));
			break;
			case 'account_seq':
				$sql = sprintf("SELECT t.* FROM %s AS t INNER JOIN %s AS a ON a.seq = t.account_seq",
					$this->mutasi_tables['rekening_transaction'],
					$this->mutasi_tables['bank_account']
				);
				$sql .= sprintf(" WHERE (t.account_seq = '%d')", $this->db_mutasi->escape_str($value));
			break;
			case 'seq':
			default:
				$sql = sprintf("SELECT t.* FROM %s AS t INNER JOIN %s AS r ON r.seq = t.rekening_seq LEFT JOIN %s AS a ON a.seq = r.account_seq",
					$this->mutasi_tables['rekening_transaction'],
					$this->mutasi_tables['bank_rekening'],
					$this->mutasi_tables['bank_account']
				);
				$sql .= sprintf(" WHERE (t.seq = '%d')", $this->db_mutasi->escape_str($value));
			break;
		}
		$sql_wheres = "";
		if (count($sql_dates) > 0) {
			if (strtotime($sql_dates['starting']) && strtotime($sql_dates['stopping'])) {
				$sql_wheres .= " AND (";
				$sql_wheres .= sprintf("t.transaction_date BETWEEN '%s' AND '%s'", $sql_dates['starting'], $sql_dates['stopping']);
				$sql_wheres .= ")";
			} else {
				$sql_wheres .= " AND (t.transaction_date = CURDATE())";
			}
			$sql .= $sql_wheres;
		}
		$sql_likes = "";
		$search_text = (is_string($search_text) ? $search_text : '');
		if (!empty($search_text) && (strlen($search_text) > 0)) {
			$sql_likes = " AND (";
			$search_array = base_permalink($search_text);
			$search_array = explode("-", $search_array);
			if (count($search_array) > 0) {
				$for_i = 0;
				foreach ($search_array as $val) {
					if ($for_i > 0) {
						$sql_likes .= " AND (CONCAT('', t.transaction_details, '') LIKE '%{$this->db_mutasi->escape_str($val)}%' OR CONCAT('', t.transaction_informasi_rekening, '') LIKE '%{$this->db_mutasi->escape_str($val)}%')";
					} else {
						$sql_likes .= " (CONCAT('', t.transaction_details, '') LIKE '%{$this->db_mutasi->escape_str($val)}%' OR CONCAT('', t.transaction_informasi_rekening, '') LIKE '%{$this->db_mutasi->escape_str($val)}%')";
					}
					$for_i++;
				}	
			} else {
				$sql_likes .= " 1=1";
			}
			$sql_likes .= ")";
			$sql .= $sql_likes;
		}
		// Only is_deleted = N
		$sql .= " AND (t.is_deleted = 'N')";
		$sql .= " ORDER BY t.transaction_datetime_insert DESC";
		$sql .= sprintf(" LIMIT %d, %d", $start, $per_page);
		try {
			$sql_query = $this->db_mutasi->query($sql);
		} catch (Exception $ex) {
			throw $ex;
			return FALSE;
		}
		return $sql_query->result();
	}
	//-----------------------------------------------------------------
	function get_condition_bank_mutasi_transaction_groups_by($by_type, $by_value, $condition_params = array(), $transaction_date = array(), $search_text = '') {
		$by_type = (is_string($by_type) ? strtolower($by_type) : 'seq');
		$value = "";
		if (isset($by_value)) {
			if (is_numeric($by_value) || is_string($value)) {
				$value = sprintf("%s", $by_value);
			}
		}
		$value = ((is_string($value) || is_numeric($value)) ? $value : '');
		switch (strtolower($by_type)) {
			case 'code':
			case 'slug':
				if (!preg_match('/^[a-z0-9_\-]*$/', $value)) {
					$value = '';
				} else {
					$value = sprintf('%s', $value);
				}
			break;
			case 'rekening_seq':
			case 'account_seq':
			case 'seq':
			case 'id':
			default:
				if (!preg_match('/^[1-9][0-9]*$/', $value)) {
					$value = 0;
				} else {
					$value = sprintf('%d', $value);
				}
			break;
		}
		$sql_dates = array();
		if (isset($transaction_date['starting'])) {
			$sql_dates['starting'] = $transaction_date['starting']->format('Y-m-d');
		}
		if (isset($transaction_date['stopping'])) {
			$sql_dates['stopping'] = $transaction_date['stopping']->format('Y-m-d');
		}
		switch (strtolower($by_type)) {
			case 'code':
			case 'slug':
				$sql = sprintf("SELECT COUNT(t.seq) AS count_value, SUM(t.transaction_amount) AS sum_value, t.transaction_code AS group_code FROM %s AS t INNER JOIN %s AS a ON a.seq = t.account_seq",
					$this->mutasi_tables['rekening_transaction'],
					$this->mutasi_tables['bank_account']
				);
				$sql .= sprintf(" WHERE (a.account_slug = '%s')", $this->db_mutasi->escape_str($value));
			break;
			case 'rekening_seq':
				$sql = sprintf("SELECT COUNT(t.seq) AS count_value, SUM(t.transaction_amount) AS sum_value, t.transaction_code AS group_code FROM %s AS t INNER JOIN %s AS r ON r.seq = t.rekening_seq",
					$this->mutasi_tables['rekening_transaction'],
					$this->mutasi_tables['bank_rekening']
				);
				$sql .= sprintf(" WHERE (t.rekening_seq = '%d')", $this->db_mutasi->escape_str($value));
			break;
			case 'account_seq':
				$sql = sprintf("SELECT COUNT(t.seq) AS count_value, SUM(t.transaction_amount) AS sum_value, t.transaction_code AS group_code FROM %s AS t INNER JOIN %s AS a ON a.seq = t.account_seq",
					$this->mutasi_tables['rekening_transaction'],
					$this->mutasi_tables['bank_account']
				);
				$sql .= sprintf(" WHERE (t.account_seq = '%d')", $this->db_mutasi->escape_str($value));
			break;
			case 'seq':
			default:
				$sql = sprintf("SELECT COUNT(t.seq) AS count_value, SUM(t.transaction_amount) AS sum_value, t.transaction_code AS group_code FROM %s AS t INNER JOIN %s AS r ON r.seq = t.rekening_seq LEFT JOIN %s AS a ON a.seq = r.account_seq",
					$this->mutasi_tables['rekening_transaction'],
					$this->mutasi_tables['bank_rekening'],
					$this->mutasi_tables['bank_account']
				);
				$sql .= sprintf(" WHERE (t.seq = '%d')", $this->db_mutasi->escape_str($value));
			break;
		}
		$sql_wheres = "";
		if (count($sql_dates) > 0) {
			if (strtotime($sql_dates['starting']) && strtotime($sql_dates['stopping'])) {
				$sql_wheres .= " AND (";
				$sql_wheres .= sprintf("t.transaction_date BETWEEN '%s' AND '%s'", $sql_dates['starting'], $sql_dates['stopping']);
				$sql_wheres .= ")";
			} else {
				$sql_wheres .= " AND (t.transaction_date = CURDATE())";
			}
			$sql .= $sql_wheres;
		}
		$sql_likes = "";
		$search_text = (is_string($search_text) ? $search_text : '');
		if (!empty($search_text) && (strlen($search_text) > 0)) {
			$sql_likes = " AND (";
			$search_array = base_permalink($search_text);
			$search_array = explode("-", $search_array);
			if (count($search_array) > 0) {
				$for_i = 0;
				foreach ($search_array as $val) {
					if ($for_i > 0) {
						$sql_likes .= " AND (CONCAT('', t.transaction_details, '') LIKE '%{$this->db_mutasi->escape_str($val)}%' OR CONCAT('', t.transaction_informasi_rekening, '') LIKE '%{$this->db_mutasi->escape_str($val)}%')";
					} else {
						$sql_likes .= " (CONCAT('', t.transaction_details, '') LIKE '%{$this->db_mutasi->escape_str($val)}%' OR CONCAT('', t.transaction_informasi_rekening, '') LIKE '%{$this->db_mutasi->escape_str($val)}%')";
					}
					$for_i++;
				}	
			} else {
				$sql_likes .= " 1=1";
			}
			$sql_likes .= ")";
			$sql .= $sql_likes;
		}
		if (isset($condition_params['transaction_amount'])) {
			$sql .= sprintf(" AND (t.transaction_amount = '%d')", $this->db_mutasi->escape_str($condition_params['transaction_amount']));
		}
		if (isset($condition_params['is_deleted'])) {
			if (is_string($condition_params['is_deleted'])) {
				if (!in_array($condition_params['is_deleted'], array('Y', 'N'))) {
					$condition_params['is_deleted'] = 'N';
				}
			} else {
				$condition_params['is_deleted'] = 'N';
			}
			$sql .= sprintf(" AND (t.is_deleted = '%s')", $this->db_mutasi->escape_str($condition_params['is_deleted']));
		}
		if (isset($condition_params['is_approved'])) {
			if (is_string($condition_params['is_approved'])) {
				if (!in_array($condition_params['is_approved'], array('Y', 'N'))) {
					$condition_params['is_approved'] = 'N';
				}
			} else {
				$condition_params['is_approved'] = 'N';
			}
			$sql .= sprintf(" AND (t.is_approved = '%s')", $this->db_mutasi->escape_str($condition_params['is_approved']));
		}
		if (isset($condition_params['transaction_action_status'])) {
			$transaction_action_status = "";
			if (is_array($condition_params['transaction_action_status'])) {
				if (count($condition_params['transaction_action_status']) > 0) {
					$for_i = 0;
					foreach ($condition_params['transaction_action_status'] as $trans_action_status) {
						if ($for_i > 0) {
							$transaction_action_status .= sprintf(", '%s'", $this->db_mutasi->escape_str($trans_action_status));
						} else {
							$transaction_action_status .= sprintf("'%s'", $this->db_mutasi->escape_str($trans_action_status));
						}
						$for_i++;
					}
				}
			} else if (is_string($condition_params['transaction_action_status']) || is_numeric($condition_params['transaction_action_status'])) {
				$transaction_action_status .= sprintf("%s", $condition_params['transaction_action_status']);
			} else {
				$transaction_action_status .= "";
			}
			// transaction_action_status
			$transaction_action_status = strtolower($transaction_action_status);
			if (strlen($transaction_action_status) > 0) {
				if (is_array($condition_params['transaction_action_status'])) {
					$sql .= " AND (LOWER(t.transaction_action_status) IN({$transaction_action_status}))";
				} else {
					$sql .= sprintf(" AND (LOWER(t.transaction_action_status) = LOWER('%s'))", $this->db_mutasi->escape_str($transaction_action_status));
				}
			}
		}
		//==== GROUPING
		$sql .= " GROUP BY t.transaction_code";
		try {
			$sql_query = $this->db_mutasi->query($sql);
		} catch (Exception $ex) {
			throw $ex;
			$ret = new stdClass();
			$ret->value = 0;
			return $ret;
		}
		return $sql_query->result();
	}
	//=====================================
	function get_condition_bank_mutasi_transaction_count_by($by_type, $by_value, $condition_params = array(), $transaction_date = array(), $search_text = '') {
		$by_type = (is_string($by_type) ? strtolower($by_type) : 'seq');
		$value = "";
		if (isset($by_value)) {
			if (is_numeric($by_value) || is_string($value)) {
				$value = sprintf("%s", $by_value);
			}
		}
		$value = ((is_string($value) || is_numeric($value)) ? $value : '');
		switch (strtolower($by_type)) {
			case 'code':
			case 'slug':
				if (!preg_match('/^[a-z0-9_\-]*$/', $value)) {
					$value = '';
				} else {
					$value = sprintf('%s', $value);
				}
			break;
			case 'rekening_seq':
			case 'account_seq':
			case 'seq':
			case 'id':
			default:
				if (!preg_match('/^[1-9][0-9]*$/', $value)) {
					$value = 0;
				} else {
					$value = sprintf('%d', $value);
				}
			break;
		}
		$sql_dates = array();
		if (isset($transaction_date['starting'])) {
			$sql_dates['starting'] = $transaction_date['starting']->format('Y-m-d');
		}
		if (isset($transaction_date['stopping'])) {
			$sql_dates['stopping'] = $transaction_date['stopping']->format('Y-m-d');
		}
		switch (strtolower($by_type)) {
			case 'code':
			case 'slug':
				$sql = sprintf("SELECT COUNT(t.seq) AS value FROM %s AS t INNER JOIN %s AS a ON a.seq = t.account_seq",
					$this->mutasi_tables['rekening_transaction'],
					$this->mutasi_tables['bank_account']
				);
				$sql .= sprintf(" WHERE (a.account_slug = '%s')", $this->db_mutasi->escape_str($value));
			break;
			case 'rekening_seq':
				$sql = sprintf("SELECT COUNT(t.seq) AS value FROM %s AS t INNER JOIN %s AS r ON r.seq = t.rekening_seq",
					$this->mutasi_tables['rekening_transaction'],
					$this->mutasi_tables['bank_rekening']
				);
				$sql .= sprintf(" WHERE (t.rekening_seq = '%d')", $this->db_mutasi->escape_str($value));
			break;
			case 'account_seq':
				$sql = sprintf("SELECT COUNT(t.seq) AS value FROM %s AS t INNER JOIN %s AS a ON a.seq = t.account_seq",
					$this->mutasi_tables['rekening_transaction'],
					$this->mutasi_tables['bank_account']
				);
				$sql .= sprintf(" WHERE (t.account_seq = '%d')", $this->db_mutasi->escape_str($value));
			break;
			case 'seq':
			default:
				$sql = sprintf("SELECT COUNT(t.seq) AS value FROM %s AS t INNER JOIN %s AS r ON r.seq = t.rekening_seq LEFT JOIN %s AS a ON a.seq = r.account_seq",
					$this->mutasi_tables['rekening_transaction'],
					$this->mutasi_tables['bank_rekening'],
					$this->mutasi_tables['bank_account']
				);
				$sql .= sprintf(" WHERE (t.seq = '%d')", $this->db_mutasi->escape_str($value));
			break;
		}
		$sql_wheres = "";
		if (count($sql_dates) > 0) {
			if (strtotime($sql_dates['starting']) && strtotime($sql_dates['stopping'])) {
				$sql_wheres .= " AND (";
				$sql_wheres .= sprintf("t.transaction_date BETWEEN '%s' AND '%s'", $sql_dates['starting'], $sql_dates['stopping']);
				$sql_wheres .= ")";
			} else {
				$sql_wheres .= " AND (t.transaction_date = CURDATE())";
			}
			$sql .= $sql_wheres;
		}
		$sql_likes = "";
		$search_text = (is_string($search_text) ? $search_text : '');
		if (!empty($search_text) && (strlen($search_text) > 0)) {
			$sql_likes = " AND (";
			$search_array = base_permalink($search_text);
			$search_array = explode("-", $search_array);
			if (count($search_array) > 0) {
				$for_i = 0;
				foreach ($search_array as $val) {
					if ($for_i > 0) {
						$sql_likes .= " AND (CONCAT('', t.transaction_details, '') LIKE '%{$this->db_mutasi->escape_str($val)}%' OR CONCAT('', t.transaction_informasi_rekening, '') LIKE '%{$this->db_mutasi->escape_str($val)}%')";
					} else {
						$sql_likes .= " (CONCAT('', t.transaction_details, '') LIKE '%{$this->db_mutasi->escape_str($val)}%' OR CONCAT('', t.transaction_informasi_rekening, '') LIKE '%{$this->db_mutasi->escape_str($val)}%')";
					}
					$for_i++;
				}	
			} else {
				$sql_likes .= " 1=1";
			}
			$sql_likes .= ")";
			$sql .= $sql_likes;
		}
		//-------------------------------------------------------------------------------------
		// Condition Params
		if (isset($condition_params['transaction_code'])) {
			$condition_params['transaction_code'] = (is_string($condition_params['transaction_code']) ? strtoupper($condition_params['transaction_code']) : '');
			$sql .= sprintf(" AND (UPPER(t.transaction_code) = '%s')", $this->db_mutasi->escape_str($condition_params['transaction_code']));
		}
		if (isset($condition_params['transaction_amount'])) {
			$sql .= sprintf(" AND (t.transaction_amount = '%d')", $this->db_mutasi->escape_str($condition_params['transaction_amount']));
		}
		if (isset($condition_params['is_deleted'])) {
			if (is_string($condition_params['is_deleted'])) {
				if (!in_array($condition_params['is_deleted'], array('Y', 'N'))) {
					$condition_params['is_deleted'] = 'N';
				}
			} else {
				$condition_params['is_deleted'] = 'N';
			}
			$sql .= sprintf(" AND (t.is_deleted = '%s')", $this->db_mutasi->escape_str($condition_params['is_deleted']));
		}
		if (isset($condition_params['is_approved'])) {
			if (is_string($condition_params['is_approved'])) {
				if (!in_array($condition_params['is_approved'], array('Y', 'N'))) {
					$condition_params['is_approved'] = 'N';
				}
			} else {
				$condition_params['is_approved'] = 'N';
			}
			$sql .= sprintf(" AND (t.is_approved = '%s')", $this->db_mutasi->escape_str($condition_params['is_approved']));
		}
		if (isset($condition_params['transaction_action_status'])) {
			$transaction_action_status = "";
			if (is_array($condition_params['transaction_action_status'])) {
				if (count($condition_params['transaction_action_status']) > 0) {
					$for_i = 0;
					foreach ($condition_params['transaction_action_status'] as $trans_action_status) {
						if ($for_i > 0) {
							$transaction_action_status .= sprintf(", '%s'", $this->db_mutasi->escape_str($trans_action_status));
						} else {
							$transaction_action_status .= sprintf("'%s'", $this->db_mutasi->escape_str($trans_action_status));
						}
						$for_i++;
					}
				}
			} else if (is_string($condition_params['transaction_action_status']) || is_numeric($condition_params['transaction_action_status'])) {
				$transaction_action_status .= sprintf("%s", $condition_params['transaction_action_status']);
			} else {
				$transaction_action_status .= "";
			}
			// transaction_action_status
			$transaction_action_status = strtolower($transaction_action_status);
			if (strlen($transaction_action_status) > 0) {
				if (is_array($condition_params['transaction_action_status'])) {
					$sql .= " AND (LOWER(t.transaction_action_status) IN({$transaction_action_status}))";
				} else {
					$sql .= sprintf(" AND (LOWER(t.transaction_action_status) = LOWER('%s'))", $this->db_mutasi->escape_str($transaction_action_status));
				}
			}
		}
		//-------------------------------------------------------------------------------------
		try {
			$sql_query = $this->db_mutasi->query($sql);
		} catch (Exception $ex) {
			throw $ex;
			$ret = new stdClass();
			$ret->value = 0;
			return $ret;
		}
		return $sql_query->row();
	}
	function get_condition_bank_mutasi_transaction_data_by($by_type, $by_value, $condition_params = array(), $transaction_date = array(), $search_text = '', $start = 0, $per_page = 10) {
		$by_type = (is_string($by_type) ? strtolower($by_type) : 'seq');
		$value = "";
		if (isset($by_value)) {
			if (is_numeric($by_value) || is_string($value)) {
				$value = sprintf("%s", $by_value);
			}
		}
		$value = ((is_string($value) || is_numeric($value)) ? $value : '');
		switch (strtolower($by_type)) {
			case 'code':
			case 'slug':
				if (!preg_match('/^[a-z0-9_\-]*$/', $value)) {
					$value = '';
				} else {
					$value = sprintf('%s', $value);
				}
			break;
			case 'rekening_seq':
			case 'account_seq':
			case 'seq':
			case 'id':
			default:
				if (!preg_match('/^[1-9][0-9]*$/', $value)) {
					$value = 0;
				} else {
					$value = sprintf('%d', $value);
				}
			break;
		}
		$sql_dates = array();
		if (isset($transaction_date['starting'])) {
			$sql_dates['starting'] = $transaction_date['starting']->format('Y-m-d');
		}
		if (isset($transaction_date['stopping'])) {
			$sql_dates['stopping'] = $transaction_date['stopping']->format('Y-m-d');
		}
		switch (strtolower($by_type)) {
			case 'code':
			case 'slug':
				$sql = sprintf("SELECT t.* FROM %s AS t INNER JOIN %s AS a ON a.seq = t.account_seq",
					$this->mutasi_tables['rekening_transaction'],
					$this->mutasi_tables['bank_account']
				);
				$sql .= sprintf(" WHERE (a.account_slug = '%s')", $this->db_mutasi->escape_str($value));
			break;
			case 'rekening_seq':
				$sql = sprintf("SELECT t.* FROM %s AS t INNER JOIN %s AS r ON r.seq = t.rekening_seq",
					$this->mutasi_tables['rekening_transaction'],
					$this->mutasi_tables['bank_rekening']
				);
				$sql .= sprintf(" WHERE (t.rekening_seq = '%d')", $this->db_mutasi->escape_str($value));
			break;
			case 'account_seq':
				$sql = sprintf("SELECT t.* FROM %s AS t INNER JOIN %s AS a ON a.seq = t.account_seq",
					$this->mutasi_tables['rekening_transaction'],
					$this->mutasi_tables['bank_account']
				);
				$sql .= sprintf(" WHERE (t.account_seq = '%d')", $this->db_mutasi->escape_str($value));
			break;
			case 'seq':
			default:
				$sql = sprintf("SELECT t.* FROM %s AS t INNER JOIN %s AS r ON r.seq = t.rekening_seq LEFT JOIN %s AS a ON a.seq = r.account_seq",
					$this->mutasi_tables['rekening_transaction'],
					$this->mutasi_tables['bank_rekening'],
					$this->mutasi_tables['bank_account']
				);
				$sql .= sprintf(" WHERE (t.seq = '%d')", $this->db_mutasi->escape_str($value));
			break;
		}
		$sql_wheres = "";
		if (count($sql_dates) > 0) {
			if (strtotime($sql_dates['starting']) && strtotime($sql_dates['stopping'])) {
				$sql_wheres .= " AND (";
				$sql_wheres .= sprintf("t.transaction_date BETWEEN '%s' AND '%s'", $sql_dates['starting'], $sql_dates['stopping']);
				$sql_wheres .= ")";
			} else {
				$sql_wheres .= " AND (t.transaction_date = CURDATE())";
			}
			$sql .= $sql_wheres;
		}
		$sql_likes = "";
		$search_text = (is_string($search_text) ? $search_text : '');
		if (!empty($search_text) && (strlen($search_text) > 0)) {
			$sql_likes = " AND (";
			$search_array = base_permalink($search_text);
			$search_array = explode("-", $search_array);
			if (count($search_array) > 0) {
				$for_i = 0;
				foreach ($search_array as $val) {
					if ($for_i > 0) {
						$sql_likes .= " AND (CONCAT('', t.transaction_details, '') LIKE '%{$this->db_mutasi->escape_str($val)}%' OR CONCAT('', t.transaction_informasi_rekening, '') LIKE '%{$this->db_mutasi->escape_str($val)}%')";
					} else {
						$sql_likes .= " (CONCAT('', t.transaction_details, '') LIKE '%{$this->db_mutasi->escape_str($val)}%' OR CONCAT('', t.transaction_informasi_rekening, '') LIKE '%{$this->db_mutasi->escape_str($val)}%')";
					}
					$for_i++;
				}	
			} else {
				$sql_likes .= " 1=1";
			}
			$sql_likes .= ")";
			$sql .= $sql_likes;
		}
		//-------------------------------------------------------------------------------------
		// Condition Params
		if (isset($condition_params['transaction_code'])) {
			$condition_params['transaction_code'] = (is_string($condition_params['transaction_code']) ? strtoupper($condition_params['transaction_code']) : '');
			$sql .= sprintf(" AND (UPPER(t.transaction_code) = '%s')", $this->db_mutasi->escape_str($condition_params['transaction_code']));
		}
		if (isset($condition_params['transaction_amount'])) {
			$sql .= sprintf(" AND (t.transaction_amount = '%d')", $this->db_mutasi->escape_str($condition_params['transaction_amount']));
		}
		if (isset($condition_params['is_deleted'])) {
			if (is_string($condition_params['is_deleted'])) {
				if (!in_array($condition_params['is_deleted'], array('Y', 'N'))) {
					$condition_params['is_deleted'] = 'N';
				}
			} else {
				$condition_params['is_deleted'] = 'N';
			}
			$sql .= sprintf(" AND (t.is_deleted = '%s')", $this->db_mutasi->escape_str($condition_params['is_deleted']));
		}
		if (isset($condition_params['is_approved'])) {
			if (is_string($condition_params['is_approved'])) {
				if (!in_array($condition_params['is_approved'], array('Y', 'N'))) {
					$condition_params['is_approved'] = 'N';
				}
			} else {
				$condition_params['is_approved'] = 'N';
			}
			$sql .= sprintf(" AND (t.is_approved = '%s')", $this->db_mutasi->escape_str($condition_params['is_approved']));
		}
		if (isset($condition_params['transaction_action_status'])) {
			$transaction_action_status = "";
			if (is_array($condition_params['transaction_action_status'])) {
				if (count($condition_params['transaction_action_status']) > 0) {
					$for_i = 0;
					foreach ($condition_params['transaction_action_status'] as $trans_action_status) {
						if ($for_i > 0) {
							$transaction_action_status .= sprintf(", '%s'", $this->db_mutasi->escape_str($trans_action_status));
						} else {
							$transaction_action_status .= sprintf("'%s'", $this->db_mutasi->escape_str($trans_action_status));
						}
						$for_i++;
					}
				}
			} else if (is_string($condition_params['transaction_action_status']) || is_numeric($condition_params['transaction_action_status'])) {
				$transaction_action_status .= sprintf("%s", $condition_params['transaction_action_status']);
			} else {
				$transaction_action_status .= "";
			}
			// transaction_action_status
			$transaction_action_status = strtolower($transaction_action_status);
			if (strlen($transaction_action_status) > 0) {
				if (is_array($condition_params['transaction_action_status'])) {
					$sql .= " AND (LOWER(t.transaction_action_status) IN({$transaction_action_status}))";
				} else {
					$sql .= sprintf(" AND (LOWER(t.transaction_action_status) = LOWER('%s'))", $this->db_mutasi->escape_str($transaction_action_status));
				}
			}
		}
		//-------------------------------------------------------------------------------------
		$sql .= " ORDER BY t.transaction_datetime_insert DESC, t.transaction_from_mutasi_position DESC";
		$sql .= sprintf(" LIMIT %d, %d", $start, $per_page);
		try {
			$sql_query = $this->db_mutasi->query($sql);
		} catch (Exception $ex) {
			throw $ex;
			return FALSE;
		}
		return $sql_query->result();
	}
	//==============================================================
	
	
	
	
	
	
	
	
	function set_bank_mutasi_transaction_by($by_type, $by_value, $transaction_date = array()) {
		$by_type = (is_string($by_type) ? strtolower($by_type) : 'seq');
		$value = "";
		if (isset($by_value)) {
			if (is_numeric($by_value) || is_string($value)) {
				$value = sprintf("%s", $by_value);
			}
		}
		$value = ((is_string($value) || is_numeric($value)) ? $value : '');
		switch (strtolower($by_type)) {
			case 'code':
			case 'slug':
				if (!preg_match('/^[a-z0-9_\-]*$/', $value)) {
					$value = '';
				} else {
					$value = sprintf('%s', $value);
				}
			break;
			case 'seq':
			case 'id':
			default:
				if (!preg_match('/^[1-9][0-9]*$/', $value)) {
					$value = 0;
				} else {
					$value = sprintf('%d', $value);
				}
			break;
		}
		
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//---------------------------------------------------------------
	//---------------------------------------------------------------
	//---------------------------------------------------------------
	
	#################################################################################################
	#### Live Fetch from Bank Website
	function get_rekening_transaction_by($by_type, $by_value, $input_params = array()) {
		$by_type = (is_string($by_type) ? strtolower($by_type) : 'seq');
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
			case 'code':
			case 'slug':
				$bank_account_data = $this->get_account_item_single_by('slug', $value);
			break;
			case 'seq':
			case 'id':
			default:
				$bank_account_data = $this->get_account_item_single_by('seq', $value);
			break;
		}
		if (is_array($input_params)) {
			if (isset($input_params['date'])) {
				if (!isset($input_params['date']['starting']) || (!isset($input_params['date']['stopping']))) {
					$transaction_datetime = array(
						'starting'	=> new DateTime(date('Y-m-d')),
						'stopping'	=> new DateTime(date('Y-m-d')),
					);
				} else {
					$transaction_datetime = array(
						'starting'	=> $input_params['date']['starting'],
						'stopping'	=> $input_params['date']['stopping'],
					);
				}
			} else {
				$transaction_datetime = array(
					'starting'	=> new DateTime(date('Y-m-d')),
					'stopping'	=> new DateTime(date('Y-m-d')),
				);
			}
		}
		if (isset($bank_account_data->b_bank_seq)) {
			try {
				$bank_data = $this->get_bank_type_by('seq', $bank_account_data->b_bank_seq);
			} catch (Exception $ex) {
				throw $ex;
				return FALSE;
			}
			if (isset($bank_data->bank_code)) {
				// Load Library Bank Mutasi Parser
				switch (strtolower($bank_data->bank_code)) {
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
		}
		if (isset($bank_account_data->account_username) && isset($bank_account_data->account_password)) {
			$transactions_data = false;
			try {
				//$this->lib_bank->set_curl_init($this->lib_bank->create_curl_headers($this->lib_bank->headers), $bank_account_data->account_username);
				$bank_login = $this->lib_bank->login($bank_account_data->account_username, $bank_account_data->account_password);
				switch (strtolower($bank_account_data->bank_code)) {
					case 'bni':
						if (isset($bank_login['form_data']['form_logout']['url']) && isset($bank_login['form_data']['form_logout']['query_params'])) {
							$transactions_data = $this->lib_bank->get_mutasi_transactions($bank_login['form_data']['form_logout']['url'], $bank_login['form_data']['form_logout']['query_params'], $transaction_datetime);
							if (isset($transactions_data['logout_params'])) {
								$this->lib_bank->logout($bank_login['form_data']['form_logout']['url'], $transactions_data['logout_params']);
							}
						} else {
							$transactions_data = false;
						}
					break;
					case 'bri':
						$bank_account_rekening_number = '';
						if (isset($bank_account_data->rekening_data)) {
							if (is_array($bank_account_data->rekening_data) && (count($bank_account_data->rekening_data) > 0)) {
								foreach ($bank_account_data->rekening_data as $rekdata) {
									$bank_account_rekening_number = (isset($rekdata->rekening_number) ? sprintf("%s", $rekdata->rekening_number) : '');
								}
							}
						}
						$bank_account_rekening_number = trim($bank_account_rekening_number);
						if ($bank_login != FALSE) {
							$transactions_data = $this->lib_bank->get_mutasi_transactions($transaction_datetime, sprintf("%s", $bank_account_rekening_number));
						} else {
							$transactions_data = false;
						}
						$this->lib_bank->logout();
					break;
					case 'bca':
					case 'mandiri':
					default:
						if ($bank_login != FALSE) {
							$transactions_data = $this->lib_bank->get_mutasi_transactions($transaction_datetime);
							$this->lib_bank->logout();
						} else {
							$transactions_data = false;
						}
					break;
				}
			} catch (Exception $ex) {
				throw $ex;
				return FALSE;
			}
			return $transactions_data;
		}
		return FALSE;
	}
	#### Check database data compare with val of transaction
	function insert_transaction_fetch_by($by_type, $by_value, $account_bank_data, $val, $transdate, $this_item_position = 0, $insert_i = 0) {
		$is_insert = FALSE;
		$is_update = FALSE;
		$dateObject = $this->authentication->create_dateobject(ConstantConfig::$timezone, 'Y-m-d H:i:s', date('Y-m-d H:i:s'));
		// Set Params
		$query_params = array();
		if (!isset($account_bank_data)) {
			return -11;
		} else {
			$query_params['bank_seq'] = (isset($account_bank_data->b_bank_seq) ? $account_bank_data->b_bank_seq : 0);
			$query_params['rekening_seq'] = 0;
		}
		if (!isset($account_bank_data->bank_code)) {
			return -12;
		}
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
			case 'account_slug':
				$value = sprintf("%s", $value);
			break;
			case 'account_seq':
			case 'seq':
			default:
				if (!preg_match('/^[1-9][0-9]*$/', $by_value)) {
					$value = 0;
				} else {
					$value = sprintf('%d', $value);
				}
			break;
		}
		if (isset($val['transaction_code'])) {
			$query_params['transaction_code'] = (is_string($val['transaction_code']) ? strtoupper($val['transaction_code']) : '');
		}
		if (isset($val['transaction_payment'])) {
			$query_params['transaction_type'] = (is_string($val['transaction_payment']) ? strtolower($val['transaction_payment']) : '');
		}
		if (isset($val['transaction_amount'])) {
			$query_params['transaction_amount'] = (is_numeric($val['transaction_amount']) ? sprintf("%.02f", $val['transaction_amount']) : '0');
		}
		if (isset($val['informasi_rekening'])) {
			#* Check rekening-number
			if (isset($val['informasi_rekening']['rekening_number']) && isset($account_bank_data->rekening_data)) {
				$val['informasi_rekening']['rekening_number'] = (is_string($val['informasi_rekening']['rekening_number']) ? sprintf("%s", $val['informasi_rekening']['rekening_number']) : '');
				if (is_array($account_bank_data->rekening_data) && (count($account_bank_data->rekening_data) > 0)) {
					foreach ($account_bank_data->rekening_data as $rekVal) {
						if ($rekVal->rekening_number == $val['informasi_rekening']['rekening_number']) {
							$query_params['rekening_seq'] = $rekVal->seq;
						}
					}
				}
			}
		}
		#### Check position of transactions
		$val['transaction_type'] = (is_string($val['transaction_type']) || is_numeric($val['transaction_type'])) ? sprintf("%s", $val['transaction_type']) : '';
		$query_params['transaction_from_mutasi_position_current'] = $insert_i;	
		if (strtolower($account_bank_data->bank_code) === 'bni') {
			if (isset($query_params['transaction_from_mutasi_position_current'])) {
				unset($query_params['transaction_from_mutasi_position_current']);
			}
		}
		#### Check Transaction Date
		if (isset($val['transaction_date_string'])) {
			$timezone = new DateTimeZone(ConstantConfig::$timezone); 
			switch (strtolower($account_bank_data->bank_code)) {
				case 'bni':
					try {
						$transaction_date_string = DateTime::createFromFormat('d-M-Y', $val['transaction_date_string'], $timezone);
					} catch (Exception $ex) {
						throw $ex;
						return false;
					}
				break;
				case 'bri':
					try {
						$transaction_date_string = DateTime::createFromFormat('d/m/y', $val['transaction_date_string'], $timezone);
					} catch (Exception $ex) {
						throw $ex;
						return false;
					}
				break;
				case 'mandiri':
				case 'bca':
				default:
					try {
						$transaction_date_string = DateTime::createFromFormat('d/m/Y', $val['transaction_date_string'], $timezone);
					} catch (Exception $ex) {
						throw $ex;
						return false;
					}
				break;
			}
			if ($transaction_date_string != FALSE) {
				$query_params['transaction_bank_date'] = $transaction_date_string->format('Y-m-d');
			} else {
				return false;
			}
		}
		if (isset($val['transaction_detail'])) {
			if (is_array($val['transaction_detail'])) {
				$count_transaction_detail = count($val['transaction_detail']);
				switch ($count_transaction_detail) {
					case 4:
						$query_params['transaction_from_acc_name'] = (isset($val['transaction_detail'][1]) ? $val['transaction_detail'][1] : '');
						break;
					case 5:
						$query_params['transaction_from_acc_name'] = (isset($val['transaction_detail'][2]) ? $val['transaction_detail'][2] : '');
						break;
					case 6:
						if (strtoupper($val['transaction_code']) === 'DB') {
							$query_params['transaction_from_acc_name'] = (isset($val['transaction_detail'][3]) ? $val['transaction_detail'][3] : '');
						} else {
							if (isset($val['transaction_detail'][4])) {
								if (sprintf("%s", $val['transaction_detail'][4]) === '0000') {
									$query_params['transaction_from_acc_name'] = (isset($val['transaction_detail'][3]) ? $val['transaction_detail'][3] : '');
								} else {
									$query_params['transaction_from_acc_name'] = (isset($val['transaction_detail'][3]) ? $val['transaction_detail'][3] : '');
								}
							} else {
								$query_params['transaction_from_acc_name'] = (isset($val['transaction_detail'][2]) ? $val['transaction_detail'][2] : '');
							}
						}
					break;
					case 7:
						$query_params['transaction_from_acc_name'] = (isset($val['transaction_detail'][4]) ? $val['transaction_detail'][4] : '');
					break;
					case 8:
						$query_params['transaction_from_acc_name'] = (isset($val['transaction_detail'][5]) ? $val['transaction_detail'][5] : '');
					break;
					default:
						$query_params['transaction_from_acc_name'] = (isset($val['transaction_detail'][2]) ? $val['transaction_detail'][2] : '');
					break;
				}
			}
		}
		#######################################
		# Check if already exists
		#######################################
		$this->db_mutasi->select('COUNT(seq) AS value')->from($this->mutasi_tables['rekening_transaction']);
		$this->db_mutasi->where("(transaction_insert_date BETWEEN '{$transdate['starting']->format('Y-m-d')}' AND '{$transdate['stopping']->format('Y-m-d')}')", NULL, FALSE);
		$sql = sprintf("SELECT COUNT(seq) AS value FROM %s WHERE (transaction_insert_date BETWEEN '%s' AND '%s')",
			$this->mutasi_tables['rekening_transaction'],
			$transdate['starting']->format('Y-m-d'),
			$transdate['stopping']->format('Y-m-d')
		);
		$sql_for_i = 0;
		if (count($query_params) > 0) {
			foreach ($query_params as $queryKey => $queryVal) {
				$sql .= sprintf(" AND LOWER(%s) = LOWER('%s')", $queryKey, $this->db_mutasi->escape_str($queryVal));
				$this->db_mutasi->where($queryKey, $queryVal);
				$sql_for_i++;
			}
		} else {
			$sql .= " AND (1 = 1)";
			$this->db_mutasi->where('1 = 1', NULL, FALSE);
		}
		$query_params['query_params'] = json_encode($query_params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
		try {
			//$sql_query = $this->db_mutasi->query($sql);
			$sql_query = $this->db_mutasi->get();
		} catch (Exception $ex) {
			throw $ex;
			return -21;
		}
		$query_params['query_row'] = $sql_query->row();
		if (isset($query_params['query_row']->value)) {
			if ((int)$query_params['query_row']->value === 0) {
				$is_insert = TRUE;
			}
		}
		$query_params['query_row'] = json_encode($query_params['query_row'], JSON_PRETTY_PRINT);
		//============================================
		// Allow Insert New
		//============================================
		#### Set last rekening saldo
		if (isset($val['transaction_saldo'])) {
			$query_params['last_rekening_saldo'] = sprintf("%.02f", $val['transaction_saldo']);
		} else {
			$query_params['last_rekening_saldo'] = 0;
		}
		#### Set Transaction Position
		$query_params['transaction_from_mutasi_position'] = (int)$this_item_position;
		$query_params['transaction_from_mutasi_position_current'] = $insert_i;
		#### Set Transaction Remark
		if (isset($val['transaction_type'])) {
			$query_params['transaction_remark'] = (is_string($val['transaction_type']) ? strtolower($val['transaction_type']) : '');
		}
		$query_params['transaction_insert_date'] = $dateObject->format('Y-m-d');
		if (isset($val['transaction_detail'][1])) {
			$query_params['transaction_remark_date'] = 	$val['transaction_detail'][1];
			$query_params['transaction_remark_date'] = sprintf("%s", $query_params['transaction_remark_date']);
		}
		// SET bank-transaction-date
		if (isset($val['transaction_date_format'])) {
			try {
				$make_transaction_date = DateTime::createFromFormat('Y-m-d', $val['transaction_date_format']);
			} catch (Exception $ex) {
				throw $ex;
				$make_transaction_date = FALSE;
			}
			if ($make_transaction_date != FALSE) {
				$query_params['transaction_date'] = $make_transaction_date->format('Y-m-d');
			} else {
				$query_params['transaction_date'] = NULL;
			}
		} else {
			$query_params['transaction_date'] = NULL;
		}
		//================================================================================
		// Set Transaction Detail
		if (isset($val['transaction_detail'])) {
			if (is_array($val['transaction_detail'])) {
				try {
					$transaction_detail = json_encode($val['transaction_detail'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
				} catch (Exception $ex) {
					throw $ex;
					$transaction_detail = '';
				}
			} else {
				$transaction_detail = '';
			}
			$query_params['transaction_details'] = $transaction_detail;
			if (is_array($val['transaction_detail'])) {
				$count_transaction_detail = count($val['transaction_detail']);
				switch ($count_transaction_detail) {
					case 4:
						$query_params['transaction_from_acc_rekening'] = (isset($val['transaction_detail'][0]) ? $val['transaction_detail'][0] : '');
					break;
					case 5:
						$query_params['transaction_from_acc_rekening'] = (isset($val['transaction_detail'][1]) ? $val['transaction_detail'][1] : '');
					break;
					case 6:
						if (strtoupper($val['transaction_code']) === 'DB') {
							$query_params['transaction_from_acc_rekening'] = (isset($val['transaction_detail'][2]) ? $val['transaction_detail'][2] : '');
						} else {
							if (isset($val['transaction_detail'][4])) {
								if (sprintf("%s", $val['transaction_detail'][4]) === '0000') {
									$query_params['transaction_from_acc_rekening'] = (isset($val['transaction_detail'][1]) ? $val['transaction_detail'][1] : '');
								} else {
									$query_params['transaction_from_acc_rekening'] = (isset($val['transaction_detail'][2]) ? $val['transaction_detail'][2] : '');
								}
							} else {
								$query_params['transaction_from_acc_rekening'] = (isset($val['transaction_detail'][3]) ? $val['transaction_detail'][3] : '');
							}
						}
					break;
					case 7:
						$query_params['transaction_from_acc_rekening'] = (isset($val['transaction_detail'][1]) ? $val['transaction_detail'][1] : '');
					break;
					case 8:
						$query_params['transaction_from_acc_rekening'] = (isset($val['transaction_detail'][4]) ? $val['transaction_detail'][4] : '');
					break;
					default:
						$query_params['transaction_from_acc_rekening'] = (isset($val['transaction_detail'][1]) ? $val['transaction_detail'][1] : '');
					break;
				}
			}
		}
		if (isset($val['informasi_rekening'])) {
			if (is_array($val['informasi_rekening'])) {
				$query_params['transaction_informasi_rekening'] = json_encode($val['informasi_rekening'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
			} else if (is_string($val['informasi_rekening']) || is_numeric($val['informasi_rekening'])) {
				$query_params['transaction_informasi_rekening'] = sprintf('%s', $val['informasi_rekening']);
			} else {
				$query_params['transaction_informasi_rekening'] = '';
			}
		}
		// SET account_seq
		$query_params['account_seq'] = (isset($account_bank_data->seq) ? $account_bank_data->seq : $value);
		// SET description
		if (isset($val['transaction_description'])) {
			$query_params['transaction_description'] = (is_string($val['transaction_description']) || is_numeric($val['transaction_description'])) ? sprintf("%s", $val['transaction_description']) : '';
		}
		// Set Datetime
		$query_params['transaction_datetime_insert'] = $dateObject->format('Y-m-d H:i:s');
		$query_params['transaction_datetime_update'] = $dateObject->format('Y-m-d H:i:s');
		// Set status mutasi
		$query_params['transaction_action_status'] = 'new';
		$query_params['is_new_fetch'] = 'Y';
		// Set actual saldo
		$query_params['actual_rekening_saldo'] = (isset($val['actual_rekening_saldo']) ? $val['actual_rekening_saldo'] : 0);
		#############################
		######### INSERT NEW ########
		#############################
		if ($is_insert === TRUE) {
			//$this->db_mutasi->trans_start();
			$this->db_mutasi->insert($this->mutasi_tables['rekening_transaction'], $query_params);
			$new_transaction_seq = $this->db_mutasi->insert_id();
			//$this->db_mutasi->trans_complete();
		} else {
			$new_transaction_seq = 0;
		}
		#############################
		return $new_transaction_seq;
	}
	//========================================================================
	function get_count_items_by_seq_with_transdate_and_insertdate($account_seq, $dateobject, $insertdate) {
		$sql = sprintf("SELECT COUNT(seq) AS value FROM %s WHERE (account_seq = '%d') AND (transaction_date BETWEEN '%s' AND '%s') AND (DATE(transaction_datetime_insert) = '%s')",
			$this->mutasi_tables['rekening_transaction'],
			$this->db_mutasi->escape_str($account_seq),
			$this->db_mutasi->escape_str($dateobject['starting']->format('Y-m-d')),
			$this->db_mutasi->escape_str($dateobject['stopping']->format('Y-m-d')),
			$this->db_mutasi->escape_str($insertdate)
		);
		$sql_query = $this->db_mutasi->query($sql);
		return $sql_query->row();
	}
	function get_count_items_by_seq_with_transdate($account_seq, $dateobject) {
		$sql = sprintf("SELECT COUNT(seq) AS value FROM %s WHERE (account_seq = '%d') AND (transaction_date BETWEEN '%s' AND '%s')",
			$this->mutasi_tables['rekening_transaction'],
			$this->db_mutasi->escape_str($account_seq),
			$this->db_mutasi->escape_str($dateobject['starting']->format('Y-m-d')),
			$this->db_mutasi->escape_str($dateobject['stopping']->format('Y-m-d'))
		);
		$sql_query = $this->db_mutasi->query($sql);
		return $sql_query->row();
	}
	function get_count_items_by_seq_with_insertdate($account_seq, $dateobject) {
		$this->db_mutasi->select('COUNT(seq) AS value');
		$this->db_mutasi->from($this->mutasi_tables['rekening_transaction']);
		$this->db_mutasi->where('account_seq', $account_seq);
		$this->db_mutasi->where("transaction_insert_date BETWEEN '{$this->db_mutasi->escape_str($dateobject['starting']->format('Y-m-d'))}' AND '{$this->db_mutasi->escape_str($dateobject['stopping']->format('Y-m-d'))}'", NULL, FALSE);
		try {
			$sql_query = $this->db_mutasi->get();
		} catch (Exception $ex) {
			throw $ex;
			return false;
		}
		//return $this->db_mutasi->last_query();
		return $sql_query->row();
	}
	//-------------------------------
	function get_mb_transdata_for_mutasi_action($account_seq, $dateobject, $query_params = array(), $sort_params = array()) {
		$this->db_mutasi->select('*')->from($this->mutasi_tables['rekening_transaction']);
		$this->db_mutasi->where('account_seq', $account_seq);
		$this->db_mutasi->where("transaction_insert_date BETWEEN '{$dateobject['starting']->format('Y-m-d')}' AND '{$dateobject['stopping']->format('Y-m-d')}'");
		if (count($query_params) > 0) {
			foreach ($query_params as $querykey => $queryval) {
				if (strtolower($querykey) === 'transaction_action_status') {
					if (is_array($queryval) && (count($queryval) > 0)) {
						$this->db_mutasi->where_in($querykey, $queryval);
					} else if (is_string($queryval)) {
						$this->db_mutasi->where($querykey, $queryval);
					} else {
						$this->db_mutasi->where($querykey, '');
					}
				} else {
					$this->db_mutasi->where($querykey, $queryval);
				}
			}
		}
		$search_text = $this->input->post('search_text');
		$search_text = (is_string($search_text) ? $search_text : '');
		if (!empty($search_text) && (strlen($search_text) > 0)) {
			$search_array = base_permalink($search_text);
			$search_array = explode("-", $search_array);
			if (count($search_array) > 0) {
				$for_i = 0;
				$sql_wheres = "";
				foreach ($search_array as $val) {
					if ($for_i > 0) {
						$sql_wheres .= " AND (CONCAT('', transaction_details, '') LIKE '%{$this->db_mutasi->escape_str($val)}%')";
					} else {
						$sql_wheres .= " (CONCAT('', transaction_details, '') LIKE '%{$this->db_mutasi->escape_str($val)}%')";
					}
					$for_i++;
				}
				$this->db_mutasi->where($sql_wheres, NULL, FALSE);
			}
		}
		
		
		$sql_query = $this->db_mutasi->get();
		return $sql_query->result();
	}
	
	
	function get_transaction_by($by_type, $by_value, $limit = 0) {
		$by_type = (is_string($by_type) ? strtolower($by_type) : 'seq');
		$value = "";
		if (isset($by_value)) {
			if (is_numeric($by_value) || is_string($value)) {
				$value = sprintf("%s", $by_value);
			}
		}
		$value = ((is_string($value) || is_numeric($value)) ? $value : '');
		switch (strtolower($by_type)) {
			case 'bank_code':
				if (!preg_match('/^[a-z0-9_\-]*$/', $value)) {
					$value = '';
				} else {
					$value = sprintf('%s', $value);
				}
			break;
			case 'bank':
			case 'rekening':
			case 'account':
			case 'seq':
			case 'id':
			default:
				if (!preg_match('/^[1-9][0-9]*$/', $value)) {
					$value = 0;
				} else {
					$value = sprintf('%d', $value);
				}
			break;
		}
		$sql = sprintf("SELECT t.*, r.seq AS r_rekening_seq, r.rekening_number, r.rekening_owner, r.rekening_is_active, r.rekening_name, a.seq AS a_account_seq, a.account_title, a.account_username, a.account_password, a.account_is_active, a.account_is_multiple_rekening, a.account_owner, b.seq AS b_bank_seq, b.bank_code, b.bank_name, b.bank_url_address, b.bank_is_active FROM %s AS t LEFT JOIN %s AS r ON r.seq = t.rekening_seq INNER JOIN %s AS a ON a.seq = r.account_seq INNER JOIN %s AS b ON b.seq = a.bank_seq",
			$this->mutasi_tables['rekening_transaction'],
			$this->mutasi_tables['bank_rekening'],
			$this->mutasi_tables['bank_account'],
			$this->mutasi_tables['bank']
		);
		switch (strtolower($by_type)) {
			case 'bank_code':
				$sql_wheres = sprintf(" WHERE b.bank_code = '%s'", $this->db_mutasi->escape_str($value));
			break;
			case 'bank':
				$sql_wheres = sprintf(" WHERE b.seq = '%d'", $this->db_mutasi->escape_str($value));
			break;
			case 'account':
				$sql_wheres = sprintf(" WHERE a.seq = '%d'", $this->db_mutasi->escape_str($value));
			break;
			case 'rekening':
				$sql_wheres = sprintf(" WHERE r.seq = '%d'", $this->db_mutasi->escape_str($value));
			break;
			case 'seq':
			case 'id':
			default:
				$sql_wheres = sprintf(" WHERE t.seq = '%d'", $this->db_mutasi->escape_str($value));
			break;
		}
		$sql .= $sql_wheres;
		if ((int)$limit > 0) {
			$sql .= sprintf(" LIMIT 0, %d", $limit);
		} else {
			$sql .= " LIMIT 1";
		}
		$sql_query = $this->db_mutasi->query($sql);
		return $sql_query->result();
	}
	
	
	//-----------------------------------------------------
	function delete_bank_account_by($by_type, $by_value) {
		$by_type = (is_string($by_type) ? strtolower($by_type) : 'seq');
		$value = "";
		if (isset($by_value)) {
			if (is_numeric($by_value) || is_string($value)) {
				$value = sprintf("%s", $by_value);
			}
		}
		$value = ((is_string($value) || is_numeric($value)) ? $value : '');
		try {
			$bank_account_data = $this->get_account_item_single_by($by_type, $value);
		} catch (Exception $ex) {
			throw $ex;
			return FALSE;
		}
		if (!isset($bank_account_data->seq)) {
			return FALSE;
		}
		$delete_params = array(
			'bank_account'				=> array(
				'seq'						=> $bank_account_data->seq,
			),
			'bank_rekening'				=> array(
				'account_seq'				=> $bank_account_data->seq,
				'bank_seq'					=> $bank_account_data->bank_seq,
			),
			'rekening_transaction'		=> array(
				'account_seq'				=> $bank_account_data->seq,
				'bank_seq'					=> $bank_account_data->bank_seq,
			),
			'sb_transaction'			=> array(
				'mutasi_bank_seq'			=> $bank_account_data->bank_seq,
				'mutasi_bank_account_seq'	=> $bank_account_data->bank_seq,
			),
		);
		//------------ Wipe Data
		# Bank Account
		$this->db_mutasi->delete($this->mutasi_tables['bank_account'], $delete_params['bank_account']);
		$this->db_mutasi->delete($this->mutasi_tables['bank_rekening'], $delete_params['bank_rekening']);
		$this->db_mutasi->delete($this->mutasi_tables['rekening_transaction'], $delete_params['rekening_transaction']);
		$this->db_mutasi->delete($this->mutasi_tables['sb_transaction'], $delete_params['sb_transaction']);
		return $this->db_mutasi->affected_rows();
	}
	
	
	//---------------------------------------------------------------
	function pulled_tmpdata_insert_to_tmp_database($account_seq, $pulled_data) {
		$insert_params = array(
			'account_seq'				=> $account_seq,
			'pulled_data'				=> $pulled_data,
		);
		$this->db_mutasi->set('pulled_datetime', 'NOW()', FALSE);
		$this->db_mutasi->insert('pull_mutasi_tmp_holder', $insert_params);
		return $this->db_mutasi->insert_id();
	}
	function get_pulled_tmpdata_from_tmp_database($seq) {
		$this->db_mutasi->where('seq', $seq);
		$sql_query = $this->db_mutasi->get('pull_mutasi_tmp_holder');
		return $sql_query->row();
	}
	//--
	function massedit_transactions_by_transactionseqs($by_type, $by_value, $transaction_action, $push_params) {
		$by_type = (is_string($by_type) ? strtolower($by_type) : 'seq');
		$value = "";
		if (isset($by_value)) {
			if (is_numeric($by_value) || is_string($value)) {
				$value = sprintf("%s", $by_value);
			}
		}
		$value = ((is_string($value) || is_numeric($value)) ? $value : '');
		try {
			$bank_account_data = $this->get_account_item_single_by($by_type, $value);
		} catch (Exception $ex) {
			throw $ex;
			return FALSE;
		}
		if (!isset($bank_account_data->seq)) {
			return FALSE;
		}
		$transaction_action = (is_string($transaction_action) ? strtolower($transaction_action) : '');
		if (!in_array($transaction_action, $this->base_paymentreport['mutasi_actions'])) {
			return false;
		}
		$sql_affected_rows = 0;
		if (isset($push_params['sequences']) && isset($push_params['data'])) {
			if (is_array($push_params['sequences']) && (count($push_params['sequences']) > 0)) {
				$this->db_mutasi->trans_start();
				foreach ($push_params['sequences'] as $seq) {
					if (is_numeric($seq)) {
						$this->db_mutasi->where('seq', $seq);
						$this->db_mutasi->update($this->mutasi_tables['rekening_transaction'], $push_params['data']);
						$sql_affected_rows += $this->db_mutasi->affected_rows();
					}
				}
				$this->db_mutasi->trans_complete();
			}
		}
		
		
		return $sql_affected_rows;
	}
	//---------------------------------------------------------------
	//---------------------------------------------------------------
	function is_datetime_between_range($datetime_input, $datetime_starting, $datetime_stopping) {
		$return = FALSE;
		if (($datetime_input > $datetime_starting) && ($datetime_input < $datetime_stopping)) {
			$return = TRUE;
		}
		return $return;
	}
	function get_bank_active_datetime($bank_codes = array()) {
		$banks_active_time = array();
		$all_banks = $this->get_bank();
		if (is_array($all_banks) && (count($all_banks) > 0)) {
			foreach ($all_banks as $val) {
				if (isset($val->bank_code)) {
					if (in_array($val->bank_code, $bank_codes)) {
						$banks_active_time[$val->bank_code] = array(
							'starting'				=> $val->bank_datetime_starting,
							'stopping'				=> $val->bank_datetime_stopping,
						);
					}
				}
			}
		}
		return $banks_active_time;
	}
	
}