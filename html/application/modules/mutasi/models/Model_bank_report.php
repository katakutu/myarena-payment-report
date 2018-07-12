<?php 
if ( ! defined('BASEPATH')) { exit('No direct script access allowed: ' . (__FILE__)); }
class Model_bank_report extends CI_Model {
	private $databases = array();
	protected $db_mutasi;
	protected $mutasi_tables = array();
	function __construct() {
		parent::__construct();
		$this->load->config('mutasi/base_mutasi');
		$this->base_mutasi = $this->config->item('base_mutasi');
		$this->load->library('dashboard/Lib_imzers', $this->base_mutasi, 'imzers');
		$this->db_mutasi = $this->load->database('mutasi', TRUE);
		$this->mutasi_tables = (isset($this->base_mutasi['mutasi_tables']) ? $this->base_mutasi['mutasi_tables'] : array());
		
	}
	
	function get_bank() {
		return $this->db_mutasi->get($this->mutasi_tables['bank'])->result();
	}
	function get_report_months() {
		$yearly_months = array();
		for ($m = 1; $m <= 12; $m++) {
			$month = date('F', mktime(0, 0, 0, $m, 1, date('Y')));
			$yearly_months[] = array(
				'code'	=> sprintf("%02s", $m),
				'name'	=> sprintf("%s", $month),
			);
		}
		return $yearly_months;
	}
	function get_report_years() {
		$years = array();
		$this->db_mutasi->select('MIN(YEAR(transaction_date)) AS min_year')->from($this->mutasi_tables['rekening_transaction']);
		$min_years = $this->db_mutasi->get()->row();
		for ($y = $min_years->min_year; $y <= date('Y'); $y++) {
			$years[] = $y;
		}
		return $years;
	}
	
	function get_maxdate_in_month($year, $month) {
		$year = (int)$year;
		$month = (int)$month;
		$days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
		return $days;
	}
	function create_daterange_interval($date_start, $date_stop) {
		$interval = new DateInterval('P1D');
		$date_start = new DateTime($date_start);
		$date_stop = new DateTime($date_stop);
		return new DatePeriod($date_start, $interval, $date_stop);
	}
	function get_amount_and_unit_transaction_by_date_type($account_seq, $date, $type) {
		$type = strtolower($type);
		$sql = sprintf("SELECT COUNT(seq) AS count_unit, COALESCE(SUM(transaction_amount), 0) AS sum_amount FROM %s WHERE (account_seq = '%d') AND (DATE(transaction_datetime_insert) = '%s' AND transaction_type = '%s')",
			$this->mutasi_tables['rekening_transaction'],
			$this->db_mutasi->escape_str($account_seq),
			$this->db_mutasi->escape_str($date),
			$this->db_mutasi->escape_str($type)
		);
		try {
			$sql_query = $this->db_mutasi->query($sql);
		} catch (Exception $ex) {
			throw $ex;
			return FALSE;
		}
		return $sql_query->row();
	}
	function get_amount_and_unit_transaction_all_bytype($account_seq, $type) {
		$sql = sprintf("SELECT COUNT(seq) AS count_unit, SUM(transaction_amount) AS sum_amount FROM %s WHERE (account_seq = '%d') AND (transaction_type = '%s')",
			$this->mutasi_tables['rekening_transaction'],
			$this->db_mutasi->escape_str($account_seq),
			$this->db_mutasi->escape_str($type)
		);
		try {
			$sql_query = $this->db_mutasi->query($sql);
		} catch (Exception $ex) {
			throw $ex;
			return FALSE;
		}
		return $sql_query->row();
	}
	function get_amount_and_unit_suksesbugil_all_bytype($account_seq, $type) {
		$type = strtolower($type);
		if (!in_array($type, array('waiting', 'deleted', 'already', 'approved', 'canceled', 'failed', 'all'))) {
			$type = 'all';
		}
		$this->db_mutasi->select('COUNT(seq) AS count_unit, COALESCE(SUM(transaction_amount), 0) AS sum_amount');
		$this->db_mutasi->from($this->mutasi_tables['sb_transaction']);
		$this->db_mutasi->where('mutasi_bank_account_seq', $account_seq);
		switch (strtolower($type)) {
			case 'waiting':
				$this->db_mutasi->where('auto_approve_status', 'waiting');
			break;
			case 'deleted':
				$this->db_mutasi->where('auto_approve_status', 'deleted');
			break;
			case 'already':
				$this->db_mutasi->where('auto_approve_status', 'already');
			break;
			case 'approved':
				$this->db_mutasi->where('auto_approve_status', 'approved');
			break;
			case 'canceled':
			case 'failed':
				$this->db_mutasi->where('auto_approve_status', 'failed');
			break;
			case 'all':
			default:
				$this->db_mutasi->where('seq > 0', NULL, FALSE);
			break;
		}
		$sql_query = $this->db_mutasi->get();
		return $sql_query->row();
	}
}




