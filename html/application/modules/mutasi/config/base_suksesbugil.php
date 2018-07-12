<?php
if (!defined('BASEPATH')) {
	exit("Cannot load script directly.");
}
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

$config['base_suksesbugil'] = array(
	'credential'	=> array(
		'username'			=> '#[USERNAME]#',
		'password'			=> '#[PASSWORD]#',
		'pin'				=> '#[PIN]#',
	),
	'init'			=> 'http://ag.suksesbogil.com',
	'url'			=> array(
		'login'				=> 'http://ag.suksesbogil.com',
		'pin'				=> 'http://ag.suksesbogil.com',
		'usercode'			=> 'http://ag.suksesbogil.com/index.php',
		'authcode'			=> 'http://ag.suksesbogil.com/usercode.php?logx=',
		'xpage'				=> 'http://ag.suksesbogil.com/xpage.php',
		'agent'				=> 'http://ag.suksesbogil.com/agent.php',
		
		'logout'			=> 'http://ag.suksesbogil.com/logoff.php',
		'logout_off'		=> 'http://ag.suksesbogil.com/index.php',
		
		'menu'				=> 'http://ag.suksesbogil.com/agent_bt.php',
		
		// Approve status
		'approve'			=> array(
			'bca'				=> 'http://ag.suksesbogil.com/agen_playermoneyx.php?action=1&bank=BCA',
			'bri'				=> 'http://ag.suksesbogil.com/agen_playermoneyx.php?action=1&bank=BRI',
			'bni'				=> 'http://ag.suksesbogil.com/agen_playermoneyx.php?action=1&bank=BNI',
			'danamon'			=> 'http://ag.suksesbogil.com/agen_playermoneyx.php?action=1&bank=DANAMON',
			'mandiri'			=> 'http://ag.suksesbogil.com/agen_playermoneyx.php?action=1&bank=MANDIRI',
		),
	),
	'approve'				=> array(
		'action'					=>	1, // Approve
		'id_14436736'				=> 'on', // Active id_transaction
		'submit'					=> 'Accept', // Accept Deposit
	),
);
$config['base_suksesbugil']['url']['authcode'] .= $config['base_suksesbugil']['credential']['username'];
$config['base_suksesbugil']['params'] = array(
	'login'			=> array(
		'entered_login'				=> $config['base_suksesbugil']['credential']['username'],
		'entered_password'			=> '',
		'vb_login_md5password'		=> $config['base_suksesbugil']['credential']['password'],
		'vb_login_md5password_utf'	=> $config['base_suksesbugil']['credential']['password'],
	),
	'pin'			=> array(
		'input_pin'					=> 'Submit',
		'pin'						=> $config['base_suksesbugil']['credential']['pin'],
	),
	
);
$config['base_suksesbugil']['method'] = array(
	'login'				=> 'POST',
	'pin'				=> 'POST',
	'usercode'			=> 'GET',
	'authcode'			=> 'GET',
	'xpage'				=> 'GET',
	'agent'				=> 'GET',
	
	'logout'			=> 'GET',
	'logout_off'		=> 'GET',
);

$config['base_suksesbugil']['base_path'] = 'mutasi';
$config['base_suksesbugil']['cookie_path'] = (NFS_PATH_CACHE . DIRECTORY_SEPARATOR . $config['base_suksesbugil']['base_path'] . DIRECTORY_SEPARATOR . 'cookies' . DIRECTORY_SEPARATOR . 'suksesbugil');
$config['base_suksesbugil']['cookie_path_approve'] = (NFS_PATH_CACHE . DIRECTORY_SEPARATOR . $config['base_suksesbugil']['base_path'] . DIRECTORY_SEPARATOR . 'cookies' . DIRECTORY_SEPARATOR . 'suksesbugil_approve');
$config['base_suksesbugil']['useragent'] = 'Mozilla/5.0 (Linux; U; Android 4.1.7; en-us; Sony Ericson) edited by: (imzers@gmail.com)';
$config['base_suksesbugil']['client'] = array(
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
//----
$config['base_suksesbugil']['get_data_url'] = array(
	'all'			=> 'http://ag.suksesbogil.com/agen_playermoneyx.php?action=1',
	'bca'			=> 'http://ag.suksesbogil.com/agen_playermoneyx.php?action=1&bank=BCA',
	'bri'			=> 'http://ag.suksesbogil.com/agen_playermoneyx.php?action=1&bank=BRI',
	'bni'			=> 'http://ag.suksesbogil.com/agen_playermoneyx.php?action=1&bank=BNI',
	'danamon'		=> 'http://ag.suksesbogil.com/agen_playermoneyx.php?action=1&bank=DANAMON',
	'mandiri'		=> 'http://ag.suksesbogil.com/agen_playermoneyx.php?action=1&bank=MANDIRI',
);

//---------------
$config['base_suksesbugil']['cli'] = array(
	'auto_approve'	=> array(
		'interval_deposit'		=> 2,
		'interval_mutasi'		=> 0, // 0 DAY (On the same day)
		'interval_delete'		=> array(
			'unit'						=> 'minute', // minute, hour, day
			'amount'					=> 10, // Nilai tiap berapa di delete
		),
	),
	'status'		=> TRUE,
);









