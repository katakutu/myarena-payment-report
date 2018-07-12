<?php
if ( ! defined('BASEPATH')) { exit('No direct script access allowed'); }

switch (strtolower(ConstantConfig::THIS_SERVER_MODE)) {
	case 'live':
		$NFS_PATH_LOGS = '/home/logs/logs';
		$NFS_PATH_CACHE = '/home/logs/cache';
	break;
	case 'dev':
	default:
		$NFS_PATH_LOGS = (dirname(dirname(dirname(dirname(dirname(__DIR__))))) . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'logs');
		$NFS_PATH_CACHE = (dirname(dirname(dirname(dirname(dirname(__DIR__))))) . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'cache');
	break;
}

defined('NFS_PATH_LOGS') or define('NFS_PATH_LOGS', $NFS_PATH_LOGS);
defined('NFS_PATH_CACHE') or define('NFS_PATH_CACHE', $NFS_PATH_CACHE);




$config['base_mutasi'] = array(
	'site-name'				=> 'Mutasi Bank',
	'site-version'			=> 'v.1.4',
	'site-copyright'		=> '2018 - mutasi@Augipt',
	'base_path'				=> 'mutasi',
	'base_password_forget'	=> 5,
	'email_vendor'			=> 'localsmtp',
	'email_template'		=> (FCPATH . 'media' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'email-template.html'),
	'super_admin_role'		=> 4,
	'admin_role'			=> array(4, 6),
	'editor_role'			=> array(3, 4, 5, 6),
	'merchant_role'			=> array(3, 5),
	'rows_per_page'			=> 10,
);
//==================
// Base Mutasi Bank
$config['base_mutasi']['banks'] = array(
	'bca',
	'bri',
	'mandiri',
	'bni',
	'danamon',
);
$config['base_mutasi']['banks_active_time'] = array(
	'bca'			=> array('starting' => '04:00:00', 'stopping' => '22:59:59'),
	'bri'			=> array('starting' => '01:00:00', 'stopping' => '22:59:59'),
	'mandiri'		=> array('starting' => '01:00:00', 'stopping' => '22:59:59'),
	'bni'			=> array('starting' => '01:00:00', 'stopping' => '22:59:59'),
	'danamon'		=> array('starting' => '01:00:00', 'stopping' => '22:59:59'),
);
$config['base_mutasi']['interval_daterange'] = array(
	'bca'			=> array(
		'unit'			=> 'day',
		'amount'		=> 1,
	),
	'bri'			=> array(
		'unit'			=> 'day',
		'amount'		=> 1,
	),
	'mandiri'		=> array(
		'unit'			=> 'day',
		'amount'		=> 1,
	),
	'bni'			=> array(
		'unit'			=> 'day',
		'amount'		=> 1,
	),
	'danamon'		=> array(
		'unit'			=> 'day',
		'amount'		=> 1,
	),
);
# Cache Path
$config['base_mutasi']['caches'] = array(
	'bca'			=> (NFS_PATH_CACHE . DIRECTORY_SEPARATOR . $config['base_mutasi']['base_path'] . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'bca'),
	'bri'			=> (NFS_PATH_CACHE . DIRECTORY_SEPARATOR . $config['base_mutasi']['base_path'] . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'bri'),
	'mandiri'		=> (NFS_PATH_CACHE . DIRECTORY_SEPARATOR . $config['base_mutasi']['base_path'] . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'mandiri'),
	'bni'			=> (NFS_PATH_CACHE . DIRECTORY_SEPARATOR . $config['base_mutasi']['base_path'] . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'bni'),
	'danamon'		=> (NFS_PATH_CACHE . DIRECTORY_SEPARATOR . $config['base_mutasi']['base_path'] . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'danamon'),
);
# Cookies Path
$config['base_mutasi']['cookies'] = array(
	'bca'			=> (NFS_PATH_CACHE . DIRECTORY_SEPARATOR . $config['base_mutasi']['base_path'] . DIRECTORY_SEPARATOR . 'cookies' . DIRECTORY_SEPARATOR . 'bca'),
	'bri'			=> (NFS_PATH_CACHE . DIRECTORY_SEPARATOR . $config['base_mutasi']['base_path'] . DIRECTORY_SEPARATOR . 'cookies' . DIRECTORY_SEPARATOR . 'bri'),
	'mandiri'		=> (NFS_PATH_CACHE . DIRECTORY_SEPARATOR . $config['base_mutasi']['base_path'] . DIRECTORY_SEPARATOR . 'cookies' . DIRECTORY_SEPARATOR . 'mandiri'),
	'bni'			=> (NFS_PATH_CACHE . DIRECTORY_SEPARATOR . $config['base_mutasi']['base_path'] . DIRECTORY_SEPARATOR . 'cookies' . DIRECTORY_SEPARATOR . 'bni'),
	'danamon'		=> (NFS_PATH_CACHE . DIRECTORY_SEPARATOR . $config['base_mutasi']['base_path'] . DIRECTORY_SEPARATOR . 'cookies' . DIRECTORY_SEPARATOR . 'danamon'),
);
$config['base_mutasi']['cookies_filename'] = 'cookies.txt';
$config['base_mutasi']['client'] = array(
	'user_ip' => (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] :
							(isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] :
								(getenv('HTTP_X_FORWARDED_FOR') ? getenv('HTTP_X_FORWARDED_FOR') :
									(isset($_ENV['HTTP_X_FORWARDED_FOR']) ? $_ENV['HTTP_X_FORWARDED_FOR'] :
										(getenv('HTTP_CLIENT_IP') ? getenv('HTTP_CLIENT_IP') :
											(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] :
												(getenv('REMOTE_ADDR') ? getenv('REMOTE_ADDR') :
													(isset($_ENV['REMOTE_ADDR']) ? $_ENV['REMOTE_ADDR'] :
														'0.0.0.0')))))))),
	'user_proxy' => (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : (getenv('REMOTE_ADDR') ? getenv('REMOTE_ADDR') : (isset($_ENV['REMOTE_ADDR']) ? $_ENV['REMOTE_ADDR'] : '0.0.0.0'))),
	'user_browser' => ((isset($_SERVER['HTTP_USER_AGENT']) && !empty($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown.Browser.UA'),
	'server_name' => ((isset($_SERVER['SERVER_NAME']) && (!empty($_SERVER['SERVER_NAME']))) ? $_SERVER['SERVER_NAME'] : 'rw.gg.in.th'),
	'request_uri' => ((isset($_SERVER['REQUEST_URI']) && (!empty($_SERVER['REQUEST_URI']))) ? strtolower(preg_replace('/\&/', '&amp;', $_SERVER['REQUEST_URI'])) : '/index.php'),
);
// Important things for curl-options
$config['base_mutasi']['curl_options'] = array(
	'user_agent'		=> 'Mozilla/5.0 (Linux; U; Android 4.1.7; en-us; Sony Ericson) edited by: (imzers@gmail.com)',
	
);
// Mutasi Database Tables
$config['base_mutasi']['mutasi_tables'] = array(
	'bank'						=> 'mutasi_bank',
	'bank_account'				=> 'mutasi_bank_accounts',
	'bank_rekening'				=> 'mutasi_bank_accounts_rekenings',
	'rekening_transaction'		=> 'mutasi_bank_accounts_rekenings_transactions',
	'transaction_daterange'		=> 'mutasi_bank_accounts_transaction_daterange',
	//---- Suksesbugil
	'sb_bank'					=> 'suksesbugil_banks',
	'sb_account'				=> 'suksesbugil_banks_accounts',
	'sb_transaction'			=> 'suksesbugil_transactions',
	'sb_login'					=> 'suksesbugil_login',
	//-- LOGS
	'log_mutasi_account'		=> 'log_mb_account',
	'log_approve'				=> 'log_approve_data',
	'log_autoapprove_run'		=> 'log_auto_approve_running',
	//-- SB Description
	'deposit_description'		=> 'suksesbugil_transactions_auto_approve_status_details',
	//-- Setting
	'setting'					=> 'configuration_settings',
);
$config['base_mutasi']['show_types'] = array(
	'all',
	'new',
	'unprocessed',
	'approved',
	'already',
	'deleted',
);
$config['base_mutasi']['mutasi_actions'] = array(
	'waiting',
	'canceled',
	'approved',
	'deleted',
	'failed',
);
// Transaction types to show
$config['base_mutasi']['transaction_types_to_show'] = array(
	'all',
	'deposit',
	'transfer',
);
//============
// From native
//============
try {
	$base_database = ConstantConfig::get_dashboard_database('mutasi', ConstantConfig::THIS_SERVER_MODE);
} catch (Exception $ex){
	throw $ex;
	$base_database = FALSE;
}
$config['base_mutasi']['get_database'] = array();
if ($base_database) {
	$config['base_mutasi']['get_database']['db_type'] = 'mysql';
	$config['base_mutasi']['get_database']['db_host'] = (isset($base_database['hostname']) ? $base_database['hostname'] : 'localhost');
	$config['base_mutasi']['get_database']['db_port'] = (isset($base_database['dbport']) ? (((int)$base_database['dbport'] > 0) ? $base_database['dbport'] : 3306) : 3306);
	$config['base_mutasi']['get_database']['db_user'] = (isset($base_database['username']) ? $base_database['username'] : '');
	$config['base_mutasi']['get_database']['db_pass'] = (isset($base_database['password']) ? $base_database['password'] : '');
	$config['base_mutasi']['get_database']['db_name'] = (isset($base_database['database']) ? $base_database['database'] : '');
	$config['base_mutasi']['get_database']['db_table'] = (isset($base_database['dbprefix']) ? $base_database['dbprefix'] : '');
	$config['base_mutasi']['get_database']['db_session_max'] = 3600;
}
//================
$config['base_mutasi']['altorouter'] = array(
	'mapping'	=> array(
		array('GET|POST', '/[*:version]/[*:service]/[*:method]/[*:segment]/[*:transaction]', (__DIR__ . '/controllers/'), 'version-service-method-segment-transaction'),
		array('GET|POST', '/[*:version]/[*:service]/[*:method]/[*:segment]', (__DIR__ . '/controllers/'), 'version-service-method-segment'),
		array('GET|POST', '/[*:version]/[*:service]/[*:method]', (__DIR__ . '/controllers/'), 'version-service-method'),
		array('GET|POST', '/[*:version]/[*:service]', (__DIR__ . '/controllers/'), 'version-service'),
		array('GET|POST', '/[*:version]', (__DIR__ . '/controllers/'), 'version'),
		array('GET|POST|HEAD|OPTIONS|PUT|DELETE', "*", (__DIR__ . '/UiRequest.php'), 'wwwroot'),
	),
);
//====================






