<?php
if ( ! defined('BASEPATH')) { exit('No direct script access allowed'); }
$config['base_dashboard'] = array(
	'site-name'				=> 'MyArena By Goodgames - True Digital Plus',
	'site-version'			=> 'v.1.01',
	'site-copyright'		=> date('Y') . ' - MyArena By Goodgames',
	'base_path'				=> 'dashboard',
	'base_password_forget'	=> 5,
	'email_vendor'			=> 'localsmtp',
	'email_template'		=> (dirname(APPPATH) . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'email-template.html'),
	'super_admin_role'		=> 4,
	'admin_role'			=> array(4, 6),
	'editor_role'			=> array(3, 4, 5, 6),
	'merchant_role'			=> array(3, 5),
	'rows_per_page'			=> 10,
	'upload_img'		=> array(
		'local'				=> array(
			'upload_dir'		=> (FCPATH . 'media'),
			'upload_path' 		=> (FCPATH . 'media' . DIRECTORY_SEPARATOR . 'images'),
			'allowed_types'		=> 'jpg|jpeg|png|gif|png',
			'allowed_mimes'		=> array('image/*', 'icon/*'),
			'forbidden_mimes'	=> array('application/*'),
			'min_size'			=> 0,
			'max_size' 			=> 512400,
			'max_width' 		=> 3260,
			'max_height' 		=> 3260,
			'encrypt_name'		=> TRUE,
			'file_name'			=> (uniqid() . time()),
			'file_id'			=> 'tdpid_images',
		),
		'resize'			=> array(
			'path'				=> array(),
			'library'			=> array(
				'image_library' 	=> 'gd2',
				'source_image'		=> '',
				'thumb_marker'		=> '_thumb',
				'create_thumb'		=> true,
				'maintain_ratio'	=> true,
				'overwrite'			=> true,
				'new_image'			=> (FCPATH . 'media' . DIRECTORY_SEPARATOR . 'images'),
				'master_dim'		=> 'width',
				'width'				=> 215,
				'height'			=> 215,
			),
			
		),
		'cdn'				=> array(
			'hostname' 				=> 'ftp.cdn-hostname.com',
			'port'					=> 21,
			'username' 				=> 'static@cdn-hostname.com',
			'password' 				=> 'cdn-password',
			'debug'					=> TRUE,
			'url' 					=> 'https://static.cdn-hostname.com',
			'path' 					=> '/media/images',
		),
	),
	
);
//============
// From native
//============
try {
	$base_database = ConstantConfig::get_dashboard_database('dashboard', ConstantConfig::THIS_SERVER_MODE);
} catch (Exception $ex){
	throw $ex;
	$base_database = FALSE;
}
$config['base_dashboard']['get_database'] = array();
if ($base_database) {
	$config['base_dashboard']['get_database']['db_type'] = 'mysql';
	$config['base_dashboard']['get_database']['db_host'] = (isset($base_database['hostname']) ? $base_database['hostname'] : 'localhost');
	$config['base_dashboard']['get_database']['db_port'] = (isset($base_database['dbport']) ? (((int)$base_database['dbport'] > 0) ? $base_database['dbport'] : 3306) : 3306);
	$config['base_dashboard']['get_database']['db_user'] = (isset($base_database['username']) ? $base_database['username'] : '');
	$config['base_dashboard']['get_database']['db_pass'] = (isset($base_database['password']) ? $base_database['password'] : '');
	$config['base_dashboard']['get_database']['db_name'] = (isset($base_database['database']) ? $base_database['database'] : '');
	$config['base_dashboard']['get_database']['db_table'] = (isset($base_database['dbprefix']) ? $base_database['dbprefix'] : '');
	$config['base_dashboard']['get_database']['db_session_max'] = 3600;
}
$config['base_dashboard']['get_tables'] = array(
	'dashboard_account'						=> 'dashboard_account',
	'dashboard_account_properties'			=> 'dashboard_account_properties',
	'dashboard_account_roles'				=> 'dashboard_account_roles',
	'dashboard_account_social'				=> 'dashboard_account_social',
	
	'dashboard_data_address_district'		=> 'dashboard_data_address_district',
	
	'data_addressbook_group'				=> 'data_addressbook_groups',
	'data_addressbook_item'					=> 'data_addressbook_items',
);
$config['base_dashboard']['get_email_vendors'] = array(
	'sendmail'				=> array(
		'protocol'				=> 'mail',
		'mailpath'				=> '/usr/sbin/sendmail',
		'smtp_host' 			=> 'localhost',
		'smtp_user' 			=> 'demo@myarena.id',
		'smtp_pass' 			=> 'PASSWORD',
		'smtp_port' 			=> 25,
		'sender_address' 		=> 'demo@myarena.id',
		'sender_name' 			=> 'Demo Myarena',
	),
	'google'				=> array(
		'protocol'				=> 'smtp',
		'smtp_host' 			=> 'smtp.googlemail.com',
		'smtp_user' 			=> 'email-username@gmail.com',
		'smtp_pass' 			=> 'email-password',
		'smtp_port' 			=> 465,
		'smtp_method'			=> 'ssl',
		'sender_address' 		=> 'email-username@gmail.com',
		'sender_name' 			=> 'Demo Myarena',
	),
	'mailgun'				=> array(
		'protocol'				=> 'smtp',
		'smtp_host' 			=> 'smtp.mailgun.org',
		'smtp_user' 			=> 'postmaster@mailgun-domain.com',
		'smtp_pass' 			=> 'MAILGUN_API_KEY',
		'smtp_port' 			=> 587,
		'smtp_method'			=> 'tls',
		'sender_address' 		=> 'demo@myarena.id',
		'sender_name' 			=> 'Demo Myarena',
	),
	// Example local SMTP Email
	'localsmtp'				=> array(
		'smtp_host' 			=> 'srv003-sg.indodax.cc',
		'smtp_user' 			=> 'sb@augipt.com',
		'smtp_pass' 			=> 'xExDml^3r3a8',
		'smtp_port' 			=> 465,
		'smtp_method'			=> 'ssl',
		'sender_address' 		=> 'sb@augipt.com',
		'sender_name' 			=> 'SB Augipt',
	),
);
$config['base_dashboard']['email_vendor'] = 'localsmtp';

//================
$config['base_dashboard']['altorouter'] = array(
	'mapping'	=> array(
		array('GET|POST', '/[*:version]/[*:service]/[*:method]/[*:segment]/[*:transaction]/[*:pagenum]', (__DIR__ . '/controllers/'), 'version-service-method-segment-transaction-pagenum'),
		array('GET|POST', '/[*:version]/[*:service]/[*:method]/[*:segment]/[*:transaction]', (__DIR__ . '/controllers/'), 'version-service-method-segment-transaction'),
		array('GET|POST', '/[*:version]/[*:service]/[*:method]/[*:segment]', (__DIR__ . '/controllers/'), 'version-service-method-segment'),
		array('GET|POST', '/[*:version]/[*:service]/[*:method]', (__DIR__ . '/controllers/'), 'version-service-method'),
		array('GET|POST', '/[*:version]/[*:service]', (__DIR__ . '/controllers/'), 'version-service'),
		array('GET|POST', '/[*:version]', (__DIR__ . '/controllers/'), 'version'),
		array('GET|POST|HEAD|OPTIONS|PUT|DELETE', "*", (__DIR__ . '/UiRequest.php'), 'wwwroot'),
	),
);



//====================
// Web Database Tables
$config['base_dashboard']['web_tables'] = array(
	'menu_type'			=> 'web_menu_types',
	'menu_items'		=> 'web_menu_types_item',
	'banner'			=> 'web_banner_slider',
	'banner_location'	=> 'web_banner_location',
	'banner_slider'		=> 'web_banner_slider',
	
	
);





