<?php 
if ( ! defined('BASEPATH')) { exit('No direct script access allowed'); }
class Model_users extends CI_Model {
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
	
	//-------------------------------------------------------
	function get_user_count($search_text = '')  {
		$search_text = (is_string($search_text) ? $search_text : '');
		$sql = sprintf("SELECT COUNT(acc.seq) AS value FROM %s AS acc LEFT JOIN %s AS role ON role.seq = acc.account_role", 
			$this->authentication->tables['dashboard_account'],
			$this->authentication->tables['dashboard_account_roles']
		);
		$sql .= sprintf(" WHERE acc.account_delete_status = '%d' AND acc.account_role != '%d'", 0, 4);
		if (!empty($search_text) && (strlen($search_text) > 0)) {
			$search_wheres = "((CONCAT('', acc.account_username, '') LIKE '%{$this->imzers->sql_addslashes($search_text)}%') OR (CONCAT('', acc.account_email, '') LIKE '%{$this->imzers->sql_addslashes($search_text)}%') OR (CONCAT('', acc.account_fullname, '') LIKE '%{$this->imzers->sql_addslashes($search_text)}%'))";
			$sql .= sprintf(" AND %s", $search_wheres);
		}
		$sql_query = $this->imzers->db_query($sql);
		return $sql_query->fetch_object();
    }
	function get_user_data($search_text = '', $start, $per_page) {
		$rows = array();
		$sql = sprintf("SELECT acc.*, role.seq AS role_seq, role.role_id, role.role_code, role.role_name FROM %s AS acc LEFT JOIN %s AS role ON role.seq = acc.account_role",
			$this->authentication->tables['dashboard_account'],
			$this->authentication->tables['dashboard_account_roles']
		);
		$sql .= sprintf(" WHERE acc.account_delete_status = '%d' AND acc.account_role != '%d'", 0, 4);
		if (!empty($search_text) && (strlen($search_text) > 0)) {
			$search_wheres = "((CONCAT('', acc.account_username, '') LIKE '%{$this->imzers->sql_addslashes($search_text)}%') OR (CONCAT('', acc.account_email, '') LIKE '%{$this->imzers->sql_addslashes($search_text)}%') OR (CONCAT('', acc.account_fullname, '') LIKE '%{$this->imzers->sql_addslashes($search_text)}%'))";
			$sql .= sprintf(" AND %s", $search_wheres);
		}
		$sql .= " ORDER BY acc.seq ASC";
		$sql .= sprintf(" LIMIT %d, %d", $start, $per_page);
		$sql_query = $this->imzers->db_query($sql);
		while ($row = $sql_query->fetch_object()) {
			$rows[] = $row;
		}
        return $rows;
    }
	//-------------------------------------------------------
	
	
	
	function get_local_user_match_by($by_value, $by_type = null, $seq = 0) {
		$rows = array();
		if (!isset($by_type)) {
			$by_type = 'email';
		}
		$seq = (is_numeric($seq) ? (int)$seq : 0);
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
		}
		$sql = sprintf("SELECT seq FROM %s WHERE (seq != '%d') AND (",
			$this->authentication->tables['dashboard_account'],
			$this->imzers->sql_addslashes($seq)
			
		);
		if (count($sql_wheres) > 0) {
			$i = 0;
			foreach ($sql_wheres as $key => $val) {
				if ($i > 0) {
					$sql .= sprintf(" AND %s = '%s'", $key, $this->imzers->sql_addslashes($val));
				} else {
					$sql .= sprintf("%s = '%s'", $key, $this->imzers->sql_addslashes($val));
				}
				$i++;
			}
		} else {
			$sql .= "1 = 1";
		}
		$sql .= ")";
		$sql_query = $this->imzers->db_query($sql);
		while ($row = $sql_query->fetch_object()) {
			$rows[] = $row;
		}
		return $rows;
	}
}