<?php
error_reporting(E_ALL ^ E_DEPRECATED);
class ConstantConfig {
	private static $instance = NULL;
	public static $timezone = 'Asia/Bangkok';
	public static $maintenance = FALSE;
	public static $templates = 'brown';
	const THIS_SERVER_NAME 				= 'payment-myarena.goodgames.net'; // Change domain of live or sandbox
	const THIS_SERVER_MODE 				= 'dev'; // 'sandbox' || 'live'
	const THIS_SERVER_PROTOCOL			= 'http'; // 'http' || 'https'
	const THIS_SERVER_LOGPATH			= (__DIR__ . '/logs'); // Server Logs path
	## return to api caller
	const PUBLIC_URL_PROTOCOL			= 'http';
	const PUBLIC_URL_ADDRESS			= 'payment-myarena.goodgames.net';
	const PUBLIC_URL_PORT				= '8089';
	const PUBLIC_URL_PATH				= '/home';
	## DB For Service Control Check
	const CONTROL_SERVICE_ENABLED		= 'Y'; // Y = Enabled, N = Disabled
	## Another constant you can put below:
	public static $THIS_SERVER_VHOST	= NULL;
	static protected $_root				= null;
	static protected $_hostname			= null;
	static protected $_logpath			= null;
	public static $databases = array(
		'log',
		'dashboard',
		'api',
		'paymentreport',
		//---- Legacy
		'core',
		'default',
		// Mutasi Rekening
		'mutasi',
	);
	function __construct() {
		date_default_timezone_set(self::$timezone);
		self::$THIS_SERVER_VHOST = self::root();
	}
	public static function get_instance() {
		if (!self::$instance) {
            self::$instance = new ConstantConfig();
        }
		return self::$instance;
	}
	static public function root() {
		if (is_null(self::$_root)) {
			self::$_root = dirname(__DIR__);
		}
		return self::$_root;
	}
	static public function hostname() {
		if (is_null(self::$_hostname)) {
			self::$_hostname = ConstantConfig::PUBLIC_URL_ADDRESS;
			if (!in_array(ConstantConfig::PUBLIC_URL_PORT, array('80', '443'))) {
				self::$_hostname .= ":" . ConstantConfig::PUBLIC_URL_PORT;
			}
		}
		return self::$_hostname;
	}
	static public function logpath() {
		if (is_null(self::$_logpath)) {
			self::$_logpath = dirname(dirname(__DIR__));
		}
		return self::$_logpath;
	}
	static public function get_database_config() {
		$instance = self::get_instance();
		$enabled_pg_code = array(
			'all',
		);
		$database_config = array(
			'sandbox'		=> array(
				'mysql'				=> array(),
				'mssql'				=> array(),
				'postsql'			=> array(),
				'orasql'			=> array(),
			),
			'live'			=> array(
				'mysql'				=> array(),
				'mssql'				=> array(),
				'postsql'			=> array(),
				'orasql'			=> array(),
			)
		);
		foreach ($enabled_pg_code as $val) {
			$database_config['sandbox']['mysql'][$val] = array(
				'db_host' => $instance->set_dashboard_params('hostname', 'core', 'sandbox'),
				'db_port' => $instance->set_dashboard_params('dbport', 'core', 'sandbox'),
				'db_user' => $instance->set_dashboard_params('username', 'core', 'sandbox'),
				'db_pass' => $instance->set_dashboard_params('password', 'core', 'sandbox'),
				'db_name' => $instance->set_dashboard_params('database', 'core', 'sandbox'),
			);
			$database_config['live']['mysql'][$val] = array(
				'db_host' => $instance->set_dashboard_params('hostname', 'core', 'live'),
				'db_port' => $instance->set_dashboard_params('dbport', 'core', 'live'),
				'db_user' => $instance->set_dashboard_params('username', 'core', 'live'),
				'db_pass' => $instance->set_dashboard_params('password', 'core', 'live'),
				'db_name' => $instance->set_dashboard_params('database', 'core', 'live'),
			);
		}
		return $database_config;
	}
	public static function get_dashboard_database($name, $mode = 'sandbox') {
		$name = (is_string($name) ? strtolower($name) : 'log');
		$mode = (is_string($mode) ? strtolower($mode) : 'sandbox');
		$instance = self::get_instance();
		return array(
			'dsn'				=> '',
			'hostname' 			=> $instance->set_dashboard_params('hostname', $name, $mode),
			'dbport'			=> $instance->set_dashboard_params('dbport', $name, $mode),
			'username' 			=> $instance->set_dashboard_params('username', $name, $mode),
			'password' 			=> $instance->set_dashboard_params('password', $name, $mode),
			'database' 			=> $instance->set_dashboard_params('database', $name, $mode),
			'dbdriver' 			=> 'mysqli',
			'dbprefix' 			=> '',
			'pconnect' 			=> FALSE,
			'db_debug' 			=> (ENVIRONMENT !== 'production'),
			'cache_on' 			=> FALSE,
			'cachedir' 			=> '',
			'char_set' 			=> 'utf8',
			'dbcollat' 			=> 'utf8_general_ci',
			'swap_pre' 			=> '',
			'encrypt' 			=> FALSE,
			'compress' 			=> FALSE,
			'stricton' 			=> FALSE,
			'failover' 			=> array(),
			'save_queries' 		=> TRUE
		);
	}
	private function set_dashboard_params($params_name, $name, $mode = 'sandbox') {
		$mode = (is_string($mode) ? strtolower($mode) : 'sandbox');
		$name = (is_string($name) ? strtolower($name) : 'dashboard');
		$params_name = (is_string($params_name) ? $params_name : '');
		$db_params = array();
		switch (strtolower($mode)) {
			case 'live':
				switch (strtolower($name)) {
					case 'api':
						$db_params['hostname'] = 'payment-myarena.goodgames.net';
						$db_params['dbport'] = 3306;
						$db_params['username'] = 'project';
						$db_params['password'] = 'project.true';
						$db_params['database'] = 'myarena_payment_api';
					break;
					case 'dashboard':
						$db_params['hostname'] = 'payment-myarena.goodgames.net';
						$db_params['dbport'] = 3306;
						$db_params['username'] = 'project';
						$db_params['password'] = 'project.true';
						$db_params['database'] = 'myarena_payment_dashboard';
					break;
					case 'log':
						$db_params['hostname'] = 'payment-myarena.goodgames.net';
						$db_params['dbport'] = 3306;
						$db_params['username'] = 'project';
						$db_params['password'] = 'project.true';
						$db_params['database'] = 'myarena_payment_logs';
					break;
					case 'report':
					default:
						$db_params['hostname'] = 'payment-myarena.goodgames.net';
						$db_params['dbport'] = 3306;
						$db_params['username'] = 'project';
						$db_params['password'] = 'project.true';
						$db_params['database'] = 'paymentreport';
					break;
				}
			break;
			case 'sandbox':
				switch (strtolower($name)) {
					case 'api':
						$db_params['hostname'] = 'localhost';
						$db_params['dbport'] = 3306;
						$db_params['username'] = 'project';
						$db_params['password'] = 'project.true';
						$db_params['database'] = 'tdpid_api';
					break;
					case 'dashboard':
						$db_params['hostname'] = 'localhost';
						$db_params['dbport'] = 3306;
						$db_params['username'] = 'project';
						$db_params['password'] = 'project.true';
						$db_params['database'] = 'tdpid_dashboard';
					break;
					case 'log':
						$db_params['hostname'] = 'localhost';
						$db_params['dbport'] = 3306;
						$db_params['username'] = 'project';
						$db_params['password'] = 'project.true';
						$db_params['database'] = 'tdpid_logs';
					break;
					case 'report':
					default:
						$db_params['hostname'] = 'localhost';
						$db_params['dbport'] = 3306;
						$db_params['username'] = 'project';
						$db_params['password'] = 'project.true';
						$db_params['database'] = 'tdpid_core';
					break;
				}
			break;
			case 'dev':
			default:
				switch (strtolower($name)) {
					case 'api':
						$db_params['hostname'] = 'localhost';
						$db_params['dbport'] = 3306;
						$db_params['username'] = 'project';
						$db_params['password'] = 'project.true';
						$db_params['database'] = 'myarena_payment_api';
					break;
					case 'dashboard':
						$db_params['hostname'] = 'localhost';
						$db_params['dbport'] = 3306;
						$db_params['username'] = 'project';
						$db_params['password'] = 'project.true';
						$db_params['database'] = 'myarena_payment_dashboard';
					break;
					case 'log':
						$db_params['hostname'] = 'localhost';
						$db_params['dbport'] = 3306;
						$db_params['username'] = 'project';
						$db_params['password'] = 'project.true';
						$db_params['database'] = 'myarena_payment_logs';
					break;
					case 'report':
					default:
						$db_params['hostname'] = 'localhost';
						$db_params['dbport'] = 3306;
						$db_params['username'] = 'project';
						$db_params['password'] = 'project.true';
						$db_params['database'] = 'myarena_payment_report';
					break;
				}
			break;
		}
		
		if (isset($db_params[$params_name])) {
			return $db_params[$params_name];
		}
		return FALSE;
	}
	
	// Check Maintenance
	public static function get_maintenance_mode() {
		// Set return TRUE if Maintenance
		// Set return FALSE if Available
		return self::$maintenance;
	}
}
$config['constant_config'] = new ConstantConfig();









