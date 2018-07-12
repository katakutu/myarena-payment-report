<?php
if ( ! defined('BASEPATH')) { exit('No direct script access allowed'); }
class Lib_pdo extends PDO {
	private $db_host;
	private $db_port;
	private $db_user;
	private $db_pass;
	private $db_name;
    protected $CI;
	protected $db_string;
	public function __construct($params) { 
		$this->db_host = (isset($params['db_host']) ? $params['db_host'] : '');
		$this->db_host = (isset($params['db_port']) ? $params['db_port'] : '');
		$this->db_user = (isset($params['db_user']) ? $params['db_user'] : '');
		$this->db_pass = (isset($params['db_pass']) ? $params['db_pass'] : '');
		$this->db_name = (isset($params['db_name']) ? $params['db_name'] : '');
        if ( PHP_OS == "Linux" ) {
		    $this->db_string = "dblib:dbname={$this->db_name};host={$this->db_host}";
			//$this->db_string = "sqlsrv:database={$this->db_name};server={$this->db_host}";
		} else {
			$this->db_string = "sqlsrv:database={$this->db_name};server={$this->db_host}";
		}
		try {
			parent::__construct($this->db_string, $this->db_user, $this->db_pass); 
		} catch(PDOException $e) {
			var_dump($e);
			log_message('error', "Connection failed: {$e->getMessage()}");
			exit;
		}
	} 
}