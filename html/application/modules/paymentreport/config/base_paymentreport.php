<?php
if ( ! defined('BASEPATH')) { exit('No direct script access allowed'); }

switch (strtolower(ConstantConfig::THIS_SERVER_MODE)) {
	case 'live':
		$NFS_PATH_LOGS = (dirname(dirname(dirname(dirname(dirname(__DIR__))))) . DIRECTORY_SEPARATOR . 'logs');
		$NFS_PATH_CACHE = (dirname(dirname(dirname(dirname(dirname(__DIR__))))) . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'cache');
	break;
	case 'dev':
	default:
		$NFS_PATH_LOGS = (dirname(dirname(dirname(dirname(dirname(__DIR__))))) . DIRECTORY_SEPARATOR . 'logs');
		$NFS_PATH_CACHE = (dirname(dirname(dirname(dirname(dirname(__DIR__))))) . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'cache');
	break;
}

defined('NFS_PATH_LOGS') or define('NFS_PATH_LOGS', $NFS_PATH_LOGS);
defined('NFS_PATH_CACHE') or define('NFS_PATH_CACHE', $NFS_PATH_CACHE);




$config['base_paymentreport'] = array(
	'site-name'				=> 'MyArena By Goodgames - True Digital Plus',
	'site-version'			=> 'v.1.01',
	'site-copyright'		=> date('Y') . ' - MyArena By Goodgames',
	'base_path'				=> 'paymentreport',
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
$config['base_paymentreport']['banks'] = array(
	'bca',
	'bri',
	'mandiri',
	'bni',
	'danamon',
);
$config['base_paymentreport']['banks_active_time'] = array(
	'bca'			=> array('starting' => '04:00:00', 'stopping' => '22:59:59'),
	'bri'			=> array('starting' => '01:00:00', 'stopping' => '22:59:59'),
	'mandiri'		=> array('starting' => '01:00:00', 'stopping' => '22:59:59'),
	'bni'			=> array('starting' => '01:00:00', 'stopping' => '22:59:59'),
	'danamon'		=> array('starting' => '01:00:00', 'stopping' => '22:59:59'),
);
$config['base_paymentreport']['interval_daterange'] = array(
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
$config['base_paymentreport']['caches'] = array(
	'bca'			=> (NFS_PATH_CACHE . DIRECTORY_SEPARATOR . $config['base_paymentreport']['base_path'] . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'bca'),
	'bri'			=> (NFS_PATH_CACHE . DIRECTORY_SEPARATOR . $config['base_paymentreport']['base_path'] . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'bri'),
	'mandiri'		=> (NFS_PATH_CACHE . DIRECTORY_SEPARATOR . $config['base_paymentreport']['base_path'] . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'mandiri'),
	'bni'			=> (NFS_PATH_CACHE . DIRECTORY_SEPARATOR . $config['base_paymentreport']['base_path'] . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'bni'),
	'danamon'		=> (NFS_PATH_CACHE . DIRECTORY_SEPARATOR . $config['base_paymentreport']['base_path'] . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'danamon'),
);
# Cookies Path
$config['base_paymentreport']['cookies'] = array(
	'bca'			=> (NFS_PATH_CACHE . DIRECTORY_SEPARATOR . $config['base_paymentreport']['base_path'] . DIRECTORY_SEPARATOR . 'cookies' . DIRECTORY_SEPARATOR . 'bca'),
	'bri'			=> (NFS_PATH_CACHE . DIRECTORY_SEPARATOR . $config['base_paymentreport']['base_path'] . DIRECTORY_SEPARATOR . 'cookies' . DIRECTORY_SEPARATOR . 'bri'),
	'mandiri'		=> (NFS_PATH_CACHE . DIRECTORY_SEPARATOR . $config['base_paymentreport']['base_path'] . DIRECTORY_SEPARATOR . 'cookies' . DIRECTORY_SEPARATOR . 'mandiri'),
	'bni'			=> (NFS_PATH_CACHE . DIRECTORY_SEPARATOR . $config['base_paymentreport']['base_path'] . DIRECTORY_SEPARATOR . 'cookies' . DIRECTORY_SEPARATOR . 'bni'),
	'danamon'		=> (NFS_PATH_CACHE . DIRECTORY_SEPARATOR . $config['base_paymentreport']['base_path'] . DIRECTORY_SEPARATOR . 'cookies' . DIRECTORY_SEPARATOR . 'danamon'),
);
$config['base_paymentreport']['cookies_filename'] = 'cookies.txt';
$config['base_paymentreport']['client'] = array(
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
$config['base_paymentreport']['curl_options'] = array(
	'user_agent'		=> 'Mozilla/5.0 (Linux; U; Android 4.1.7; en-us; Sony Ericson) edited by: (imzers@gmail.com)',
	
);
// Paymentreport Database Tables
$config['base_paymentreport']['paymentreport_tables'] = array(
	'providers'					=> 'gg_payment',
	'report_payments'			=> 'payment_incoming',
	'incoming'					=> 'payment_incoming',
	'outgoing'					=> 'payment_outgoing',
	
	//==========================================================
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
// Mutasi Database Tables
$config['base_paymentreport']['mutasi_tables'] = array(
	'bank'						=> 'gg_payment',
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
$config['base_paymentreport']['show_types'] = array(
	'all',
	'new',
	'unprocessed',
	'approved',
	'already',
	'deleted',
);
$config['base_paymentreport']['mutasi_actions'] = array(
	'waiting',
	'canceled',
	'approved',
	'deleted',
	'failed',
);
// Transaction types to show
$config['base_paymentreport']['transaction_types_to_show'] = array(
	'all',
	'deposit',
	'transfer',
);
//============
// From native
//============
try {
	$base_database = ConstantConfig::get_dashboard_database('paymentreport', ConstantConfig::THIS_SERVER_MODE);
} catch (Exception $ex){
	throw $ex;
	$base_database = FALSE;
}
$config['base_paymentreport']['get_database'] = array();
if ($base_database) {
	$config['base_paymentreport']['get_database']['db_type'] = 'mysql';
	$config['base_paymentreport']['get_database']['db_host'] = (isset($base_database['hostname']) ? $base_database['hostname'] : 'localhost');
	$config['base_paymentreport']['get_database']['db_port'] = (isset($base_database['dbport']) ? (((int)$base_database['dbport'] > 0) ? $base_database['dbport'] : 3306) : 3306);
	$config['base_paymentreport']['get_database']['db_user'] = (isset($base_database['username']) ? $base_database['username'] : '');
	$config['base_paymentreport']['get_database']['db_pass'] = (isset($base_database['password']) ? $base_database['password'] : '');
	$config['base_paymentreport']['get_database']['db_name'] = (isset($base_database['database']) ? $base_database['database'] : '');
	$config['base_paymentreport']['get_database']['db_table'] = (isset($base_database['dbprefix']) ? $base_database['dbprefix'] : '');
	$config['base_paymentreport']['get_database']['db_session_max'] = 3600;
}
//================
$config['base_paymentreport']['altorouter'] = array(
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






