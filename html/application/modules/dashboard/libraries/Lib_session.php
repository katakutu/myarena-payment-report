<?php
if ( ! defined('BASEPATH')) { exit('No direct script access allowed'); }
class Lib_session implements SessionHandlerInterface {
    public $life_time;
    protected $db = FALSE;
	public $db_config;
	public $table;
    public function __construct($db_config) {
        // Read the maxlifetime setting from PHP
        $this->life_time = (isset($db_config['db_session_max']) ? $db_config['db_session_max'] : 3600);
		$this->table = (isset($db_config['db_table']) ? $db_config['db_table'] : 'weblog_sessions');
		$this->db_config = array(
			'db_host'			=> (isset($db_config['db_host']) ? $db_config['db_host'] : ''),
			'db_user'			=> (isset($db_config['db_user']) ? $db_config['db_user'] : ''),
			'db_pass'			=> (isset($db_config['db_pass']) ? $db_config['db_pass'] : ''),
			'db_name'			=> (isset($db_config['db_name']) ? $db_config['db_name'] : ''),
			'session_max'		=> (isset($db_config['db_session_max']) ? $db_config['db_session_max'] : 0),
		);
		
        // Register this object as the session handler
		/*
        session_set_save_handler(
            [$this, "open"],
            [$this, "close"],
            [$this, "read"],
            [$this, "write"],
            [$this, "destroy"],
            [$this, "gc"]
        );
		*/
    }
	public function open($save_path, $session_name) {
		$database = $this->db_config;
		$this->db = new mysqli($database['db_host'], $database['db_user'], $database['db_pass'], $database['db_name']);
		if ($this->db->connect_error) {
			return false;
		} else {
			return true;
		}
    }
    public function close() {
        if ($this->db) {
			$this->db->close();
		}
		return true;
    }
    public function read($id) {
		$datetime = date('Y-m-d H:i:s');
		$sql = sprintf("SELECT session_data FROM %s WHERE session_id = '%s' AND session_expires > '%s'",
			$this->table,
			$id,
			$datetime
		);
		$sql_query = $this->db->query($sql);
		if ($result = $sql_query->fetch_object()) {
            return base64_decode($result->session_data);
        } else {
			return '';
		}
    }
    public function write($id, $data) {
        $datetime_current = date('Y-m-d H:i:s');
        $datetime_expires = date('Y-m-d H:i:s', (strtotime($datetime_current) + $this->db_config['session_max']));
		$sql = sprintf("REPLACE %s(session_id, session_data, session_expires) VALUES('%s', '%s', '%s')",
			$this->table,
			$id,
			base64_encode($data),
			$datetime_expires
		);
		return $this->db->query($sql);
    }
    public function destroy($id) {
		$sql = sprintf("DELETE FROM %s WHERE session_id = '%s'",
			$this->table,
			$id
		);
		$sql_query = $this->db->query($sql);
        if ($sql_query) {
			return true;
		} else {
			return false;
		}
    }
    public function gc($maxlifetime = 0) {
        return $this->db->query("DELETE FROM {$this->table} WHERE session_expires < NOW();");
    }
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	

}