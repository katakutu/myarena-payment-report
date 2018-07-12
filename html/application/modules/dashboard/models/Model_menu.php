<?php 
if ( ! defined('BASEPATH')) { exit('No direct script access allowed: ' . (__FILE__)); }
class Model_menu extends CI_Model {
	private $databases = array();
	protected $db_web;
	protected $web_tables = array();
	function __construct() {
		parent::__construct();
		$this->load->config('dashboard/base_dashboard');
		$this->base_dashboard = $this->config->item('base_dashboard');
		$this->load->library('dashboard/Lib_authentication', $this->base_dashboard, 'authentication');
		$this->load->library('dashboard/Lib_imzers', $this->base_dashboard, 'imzers');
		$this->db_web = $this->load->database('web', TRUE);
		$this->web_tables = (isset($this->base_dashboard['web_tables']) ? $this->base_dashboard['web_tables'] : array());
	}
	
	
	//---------------------------------------------------------------
	function get_menu_types() {
		return $this->db_web->get($this->web_tables['menu_type'])->result();
	}
	function get_menu_type_by($by_type, $by_value) {
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
		$sql = sprintf("SELECT * FROM %s WHERE", $this->web_tables['menu_type']);
		switch (strtolower($by_type)) {
			case 'code':
				$sql .= sprintf(" type_code = '%s'", $this->imzers->sql_addslashes($value));
			break;
			case 'seq':
			default:
				$sql .= sprintf(" seq = '%d'", $this->imzers->sql_addslashes($value));
			break;
		}
		return $this->db_web->query($sql)->row();
	}
	//-----
	function get_menu_item_count_by($by_type, $by_value, $search_text = '')  {
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
			case 'menu_creator':
				$value = sprintf("%s", $value);
			break;
			case 'menu_parent':
			case 'menu_type':
			default:
				if (!preg_match('/^[1-9][0-9]*$/', $by_value)) {
					$value = 0;
				} else {
					$value = sprintf('%d', $value);
				}
			break;
		}
		$this->db_web->select("COUNT(seq) AS value")->from($this->web_tables['menu_items']);
		switch (strtolower($by_type)) {
			case 'menu_creator':
				$this->db_web->where('menu_created_by', $this->db_web->escape_str($value));
			break;
			case 'menu_parent':
				$this->db_web->where('menu_parent', $this->db_web->escape_str($value));
			break;
			case 'menu_type':
			default:
				$this->db_web->where('menu_type', $this->db_web->escape_str($value));
			break;
		}
		$search_text = (is_string($search_text) ? $search_text : '');
		if (!empty($search_text) && (strlen($search_text) > 0)) {
			$search_array = base_permalink($search_text);
			$search_array = explode("-", $search_array);
			if (count($search_array) > 0) {
				$for_i = 0;
				foreach ($search_array as $val) {
					$this->db_web->like('menu_title', $this->db_web->escape_str($val), 'both');
					$for_i++;
				}
			}
		}
		return $this->db_web->get()->row();
    }
	function get_menu_item_data_by($by_type, $by_value, $search_text = '', $start = 0, $per_page = 10) {
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
			case 'menu_creator':
				$value = sprintf("%s", $value);
			break;
			case 'menu_parent':
			case 'menu_type':
			default:
				if (!preg_match('/^[1-9][0-9]*$/', $by_value)) {
					$value = 0;
				} else {
					$value = sprintf('%d', $value);
				}
			break;
		}
		$sql = sprintf("SELECT i.*, t.seq AS type_seq, t.type_code, t.type_name FROM %s AS i LEFT JOIN %s AS t ON t.seq = i.menu_type WHERE",
			$this->web_tables['menu_items'],
			$this->web_tables['menu_type']
		);
		switch (strtolower($by_type)) {
			case 'menu_creator':
				$sql_wheres = sprintf(" i.menu_created_by = '%s'", $this->db_web->escape_str($value));
			break;
			case 'menu_parent':
				$sql_wheres = sprintf(" i.menu_parent = '%d'", $this->db_web->escape_str($value));
			break;
			case 'menu_type':
			default:
				$sql_wheres = sprintf(" i.menu_type = '%d'", $this->db_web->escape_str($value));
			break;
		}
		$sql .= $sql_wheres;
		$search_text = (is_string($search_text) ? $search_text : '');
		if (!empty($search_text) && (strlen($search_text) > 0)) {
			$sql .= " AND (";
			$sql_likes = "";
			$search_array = base_permalink($search_text);
			$search_array = explode("-", $search_array);
			if (count($search_array) > 0) {
				$for_i = 0;
				foreach ($search_array as $val) {
					if ($for_i > 0) {
						$sql_likes .= " AND i.menu_title LIKE '%{$this->db_web->escape_str($value)}%'";
					} else {
						$sql_likes .= " i.menu_title LIKE '%{$this->db_web->escape_str($value)}%'";
					}
					$for_i++;
				}
			}
			$sql .= $sql_likes;
			$sql .= ")";
		}
		$sql .= " ORDER BY i.menu_order ASC";
		$sql .= sprintf(" LIMIT %d, %d", $start, $per_page);
		$sql_query = $this->db_web->query($sql);
		return $sql_query->result();
	}
	function get_menu_item_by($by_type, $by_value, $input_params = array()) {
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
			case 'menu_type':
			case 'menu_parent':
			case 'seq':
			default:
				if (!preg_match('/^[1-9][0-9]*$/', $by_value)) {
					$value = 0;
				} else {
					$value = sprintf('%d', $value);
				}
			break;
		}
		$sql = sprintf("SELECT i.*, t.seq AS type_seq, t.type_code, t.type_name FROM %s AS i LEFT JOIN %s AS t ON t.seq = i.menu_type WHERE", 
			$this->web_tables['menu_items'],
			$this->web_tables['menu_type']
		);
		switch (strtolower($by_type)) {
			case 'code':
			case 'slug':
				$sql .= sprintf(" i.menu_slug = '%s'", $this->db_web->escape_str($value));
			break;
			case 'menu_type':
				$sql .= sprintf(" i.menu_type = '%d'", $this->db_web->escape_str($value));
				if (is_array($input_params)) {
					if (count($input_params) > 0) {
						$for_i = 0;
						$sql .= " AND (";
						foreach ($input_params as $key => $val) {
							if ($for_i > 0) {
								$sql .= sprintf(" AND i.%s = '%s'", $this->db_web->escape_str($key), $this->db_web->escape_str($val));
							} else {
								$sql .= sprintf("i.%s = '%s'", $this->db_web->escape_str($key), $this->db_web->escape_str($val));
							}
							$for_i++;
						}
						$sql .= ")";
					}
				}
			break;
			case 'menu_parent':
				$sql .= sprintf(" i.menu_parent = '%d'", $this->db_web->escape_str($value));
			break;
			case 'seq':
			default:
				$sql .= sprintf(" i.seq = '%d'", $this->db_web->escape_str($value));
			break;
		}
		$sql .= " ORDER BY i.menu_order ASC";
		$sql_query = $this->db_web->query($sql);
		return $sql_query->result();
	}
	function get_menu_item_single_by($by_type, $by_value) {
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
		$this->db_web->select('*')->from($this->web_tables['menu_items']);
		switch (strtolower($by_type)) {
			case 'code':
			case 'slug':
				$this->db_web->where('menu_slug', $this->db_web->escape_str($value));
			break;
			case 'seq':
			default:
				$this->db_web->where('seq', $this->db_web->escape_str($value));
			break;
		}
		$this->db_web->limit(1);
		return $this->db_web->get()->row();
	}
	function get_menu_item_single_with_type_seq($type_seq, $by_type, $by_value, $is_item_seq = 0) {
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
		$sql = sprintf("SELECT seq AS value FROM %s WHERE (", $this->web_tables['menu_items']);
		$sql .= sprintf("menu_type = '%d'", $this->db_web->escape_str($type_seq));
		switch (strtolower($by_type)) {
			case 'title':
				$sql .= sprintf(" AND menu_title = '%s'", $this->db_web->escape_str($value));
			break;
			case 'code':
			case 'slug':
				$sql .= sprintf(" AND menu_slug = '%s'", $this->db_web->escape_str($value));
			break;
			case 'seq':
			default:
				$sql .= sprintf(" AND seq = '%d'", $this->db_web->escape_str($value));
			break;
		}
		$sql .= ")";
		if ($is_item_seq > 0) {
			if ($by_type !== 'seq') {
				$sql .= sprintf(" AND (seq != '%d')", $is_item_seq);
			}
		}
		$sql_query = $this->db_web->query($sql);
		while ($row = $sql_query->result()) {
			return $row;
		}
		return false;
	}
	//===========================
	// Menu Item: Insert, Update, Delete
	function insert_menu_item($input_params = array()) {
		$query_params = array(
			'menu_type'			=> (isset($input_params['menu_type']) ? $input_params['menu_type'] : 0),
			'menu_parent'		=> (isset($input_params['menu_parent']) ? $input_params['menu_parent'] : 0),
			'menu_title'		=> (isset($input_params['menu_title']) ? $input_params['menu_title'] : ''),
			'menu_slug'			=> (isset($input_params['menu_slug']) ? $input_params['menu_slug'] : ''),
			'menu_path'			=> (isset($input_params['menu_path']) ? $input_params['menu_path'] : ''),
			'menu_description'	=> (isset($input_params['menu_description']) ? $input_params['menu_description'] : ''),
			'menu_is_parent'	=> (isset($input_params['menu_is_parent']) ? $input_params['menu_is_parent'] : 'N'),
			'menu_is_active'	=> (isset($input_params['menu_is_active']) ? $input_params['menu_is_active'] : 'N'),
			'menu_order'		=> (isset($input_params['menu_order']) ? $input_params['menu_order'] : 0),
			
			'menu_created_datetime'	=> (isset($input_params['menu_created_datetime']) ? $input_params['menu_created_datetime'] : date('Y-m-d H:i:s')),
			'menu_edited_datetime'	=> (isset($input_params['menu_edited_datetime']) ? $input_params['menu_edited_datetime'] : date('Y-m-d H:i:s')),
		);
		$query_params['menu_created_by'] = (isset($this->authentication->localdata['account_email']) ? $this->authentication->localdata['account_email'] : 'system@root');
		$query_params['menu_edited_by'] = (isset($this->authentication->localdata['account_email']) ? $this->authentication->localdata['account_email'] : 'system@root');
		try {
			$this->db_web->trans_start();
			$this->db_web->insert($this->web_tables['menu_items'], $query_params);
			$new_inserted_menu_seq = $this->db_web->insert_id();
			$this->db_web->trans_complete();
		} catch (Exception $ex) {
			throw $ex;
			$new_inserted_menu_seq = 0;
		}
		
		return $new_inserted_menu_seq;
	}
	function set_menu_item($item_seq, $input_params = array()) {
		$item_seq = (is_numeric($item_seq) ? (int)$item_seq : 0);
		$query_params = array(
			'menu_type'			=> (isset($input_params['menu_type']) ? $input_params['menu_type'] : 0),
			'menu_parent'		=> (isset($input_params['menu_parent']) ? $input_params['menu_parent'] : 0),
			'menu_title'		=> (isset($input_params['menu_title']) ? $input_params['menu_title'] : ''),
			'menu_slug'			=> (isset($input_params['menu_slug']) ? $input_params['menu_slug'] : ''),
			'menu_path'			=> (isset($input_params['menu_path']) ? $input_params['menu_path'] : ''),
			'menu_description'	=> (isset($input_params['menu_description']) ? $input_params['menu_description'] : ''),
			'menu_is_parent'	=> (isset($input_params['menu_is_parent']) ? $input_params['menu_is_parent'] : 'N'),
			'menu_is_active'	=> (isset($input_params['menu_is_active']) ? $input_params['menu_is_active'] : 'N'),
			'menu_order'		=> (isset($input_params['menu_order']) ? $input_params['menu_order'] : 0),
			'menu_edited_datetime'	=> (isset($input_params['menu_edited_datetime']) ? $input_params['menu_edited_datetime'] : date('Y-m-d H:i:s')),
		);
		$query_params['menu_edited_by'] = (isset($this->authentication->localdata['account_email']) ? $this->authentication->localdata['account_email'] : 'system@root');
		$this->db_web->where('seq', $item_seq);
		$this->db_web->update($this->web_tables['menu_items'], $query_params);
		return $this->db_web->affected_rows();
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	#################################################################################################
	
	
	function get_item_data_by($by_key, $by_value, $start, $per_page, $localdata = null) {
		if (!isset($localdata)) {
			$localdata = $this->authentication->localdata;
		}
		$local_seq = (isset($localdata['seq']) ? (int)$localdata['seq'] : 0);
		$by_key = (is_string($by_key) ? strtolower($by_key) : 'seq');
		$value = "";
		$by_value = ((is_string($by_value) || is_numeric($by_value)) ? $by_value : '');
		if (!preg_match('/^[1-9][0-9]*$/', $by_value)) {
			$value = sprintf("%s", $by_value);
		} else {
			$value = sprintf("%d", $by_value);
		}
		$rows = array();
		$sql = sprintf("SELECT item.*, gr.seq AS item_group_seq, gr.group_name_text AS item_group_name, gr.group_name_url AS item_group_url, pr.seq AS parent_group_seq, pr.group_name_text AS parent_group_name, pr.group_name_url AS parent_group_url FROM %s AS item LEFT JOIN %s AS gr ON gr.seq = item.group_seq LEFT JOIN %s AS pr ON pr.seq = gr.group_parent_seq WHERE item.item_owner = '%d'", 
			$this->authentication->tables['data_addressbook_item'],
			$this->authentication->tables['data_addressbook_group'],
			$this->authentication->tables['data_addressbook_group'],
			$this->imzers->sql_addslashes($local_seq)
		);
		switch (strtolower($by_key)) {
			case 'group_seq':
				$sql .= sprintf(" AND (item.group_seq = '%d')", $this->imzers->sql_addslashes($value));
			break;
			case 'is_active':
				$sql .= sprintf(" AND (item.item_is_active = '%s')", $this->imzers->sql_addslashes($value));
			break;
			case 'name':
				$sql .= " AND (CONCAT('', item.item_name_text, '') LIKE '%{$this->imzers->sql_addslashes($value)}%')";
			break;
			case 'seq':
			default:
				$sql .= sprintf(" AND (item.seq = '%d')", $this->imzers->sql_addslashes($value));
			break;
		}
		$sql .= " ORDER BY item.item_add_datetime DESC";
		$sql .= sprintf(" LIMIT %d, %d", $start, $per_page);
		$sql_query = $this->imzers->db_query($sql);
		while ($row = $sql_query->fetch_object()) {
			$rows[] = $row;
		}
        return $rows;
	}

	function set_group_parent_childs($parent_seq = 0, $localdata = null) {
		$parent_seq = (is_numeric($parent_seq) ? (int)$parent_seq : 0);
		if (!isset($localdata)) {
			$localdata = $this->authentication->localdata;
		}
		$local_seq = (isset($localdata['seq']) ? (int)$localdata['seq'] : 0);
		$sql = sprintf("SELECT COUNT(seq) AS value, MAX(seq) AS max_seq FROM %s WHERE (group_parent_seq = '%d' AND group_owner = '%d')",
			$this->authentication->tables['data_addressbook_group'],
			$this->imzers->sql_addslashes($parent_seq),
			$this->imzers->sql_addslashes($local_seq)
		);
		$sql_query = $this->imzers->db_query($sql);
		$return = array('value' => 0, 'max_seq' => 0);
		while ($row = $sql_query->fetch_assoc()) {
			$return['value'] = (isset($row['value']) ? $row['value'] : 0);
			$return['max_seq'] = (isset($row['max_seq']) ? $row['max_seq'] : 0);
		}
		$sql = sprintf("UPDATE %s SET group_childs = '%d', group_last_child_seq = '%d', group_is_parent = '%s' WHERE seq = '%d'",
			$this->authentication->tables['data_addressbook_group'],
			$this->imzers->sql_addslashes($return['value']),
			$this->imzers->sql_addslashes($return['max_seq']),
			'Y',
			$this->imzers->sql_addslashes($parent_seq)
		);
		$this->imzers->db_query($sql);
	}
	function set_group_items($parent_seq = 0, $localdata = null) {
		$parent_seq = (is_numeric($parent_seq) ? (int)$parent_seq : 0);
		if (!isset($localdata)) {
			$localdata = $this->authentication->localdata;
		}
		$local_seq = (isset($localdata['seq']) ? (int)$localdata['seq'] : 0);
		$return = array(
			'group_items' => 0, 
			'group_last_item_seq' => 0
		);
		$sql = sprintf("SELECT COUNT(seq) AS group_items, MAX(seq) AS group_last_item_seq FROM %s WHERE (group_seq = '%d' AND item_owner = '%d' AND item_is_active = '%s')",
			$this->authentication->tables['data_addressbook_item'],
			$this->imzers->sql_addslashes($parent_seq),
			$this->imzers->sql_addslashes($local_seq),
			'Y'
		);
		$sql_query = $this->imzers->db_query($sql);
		while ($row = $sql_query->fetch_assoc()) {
			$return['group_items'] = (isset($row['group_items']) ? $row['group_items'] : 0);
			$return['group_last_item_seq'] = (isset($row['group_last_item_seq']) ? $row['group_last_item_seq'] : 0);
		}
		$sql = sprintf("UPDATE %s SET group_items = '%d', group_last_item_seq = '%d' WHERE seq = '%d'",
			$this->authentication->tables['data_addressbook_group'],
			$this->imzers->sql_addslashes($return['group_items']),
			$this->imzers->sql_addslashes($return['group_last_item_seq']),
			$this->imzers->sql_addslashes($parent_seq)
		);
		$this->imzers->db_query($sql);
	}
	//-------------------------------------------------------
	function get_addressbook_group_match_by($by_value, $by_type = null, $seq = 0) {
		$rows = array();
		if (!isset($by_type)) {
			$by_type = 'seq';
		}
		$seq = (is_numeric($seq) ? (int)$seq : 0);
		$sql_wheres = array();
		switch (strtolower($by_type)) {
			case 'url':
				$sql_wheres['group_name_url'] = $by_value;
			break;
			case 'code':
				$sql_wheres['group_code'] = $by_value;
			break;
			case 'seq':
			case 'id':
			default:
				if (is_numeric($by_value)) {
					$sql_wheres['seq'] = $by_value;
				} else {
					$sql_wheres['group_code'] = $by_value;
				}
			break;
		}
		$sql = sprintf("SELECT seq FROM %s WHERE (seq != '%d') AND (",
			$this->authentication->tables['data_addressbook_group'],
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
	function get_addressbook_item_match_by($by_value, $by_type = null, $seq = 0) {
		$rows = array();
		if (!isset($by_type)) {
			$by_type = 'seq';
		}
		$seq = (is_numeric($seq) ? (int)$seq : 0);
		$sql_wheres = array();
		switch (strtolower($by_type)) {
			case 'url':
				$sql_wheres['item_name_url'] = $by_value;
			break;
			case 'code':
				$sql_wheres['item_code'] = $by_value;
			break;
			case 'seq':
			case 'id':
			default:
				if (is_numeric($by_value)) {
					$sql_wheres['seq'] = $by_value;
				} else {
					$sql_wheres['item_code'] = $by_value;
				}
			break;
		}
		$sql = sprintf("SELECT seq FROM %s WHERE (seq != '%d') AND (",
			$this->authentication->tables['data_addressbook_item'],
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
	
	
	
	
	
	
	
	
	
	//---------------------------------------------------------------
	function add_group($input_params = array()) {
		if (is_array($input_params)) {
			$sql = sprintf("INSERT INTO %s(", $this->authentication->tables['data_addressbook_group']);
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
		} else {
			return FALSE;
		}
	}
	function add_item($input_params = array()) {
		if (is_array($input_params)) {
			$sql = sprintf("INSERT INTO %s(", $this->authentication->tables['data_addressbook_item']);
			$values = "";
			if (count($input_params) > 0) {
				$i = 0;
				foreach ($input_params as $key => $val) {
					if ($i > 0) {
						$sql .= sprintf(", %s", $key);
						if (!is_null($val)) {
							$values .= sprintf(", '%s'", $val);
						} else {
							$values .= ", NULL";
						}
					} else {
						$sql .= sprintf("%s", $key);
						if (!is_null($val)) {
							$values .= sprintf("'%s'", $val);
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
		} else {
			return FALSE;
		}
	}
	
	
	//--------------------------------------------------------------
	function check_group_parent_owner($group_seq, $owner_seq = null) {
		if (!isset($owner_seq)) {
			$owner_seq = (isset($this->authentication->localdata['seq']) ? $this->authentication->localdata['seq'] : 0);
		}
		$group_seq = (is_numeric($group_seq) ? (int)$group_seq : 0);
		$sql = sprintf("SELECT * FROM %s WHERE seq = '%d'", $this->authentication->tables['data_addressbook_group'], $this->imzers->sql_addslashes($group_seq));
		$sql_query = $this->imzers->db_query($sql);
		while ($row = $sql_query->fetch_assoc()) {
			if ($row['group_owner'] === $owner_seq) {
				return true;
			} else {
				return false;
			}
		}
		return FALSE;
	}
	
}