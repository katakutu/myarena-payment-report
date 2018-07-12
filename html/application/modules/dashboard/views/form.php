<?php
if ( ! defined('BASEPATH')) { exit('No direct script access allowed'); }
defined('PHP_MYSQL_CRUD_NATIVE') OR define('PHP_MYSQL_CRUD_NATIVE', TRUE);

include_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'form-includes' . DIRECTORY_SEPARATOR . 'header.php');




$page = (isset($page) ? $page : 'login');
$page = (is_string($page) ? strtolower($page) : 'login');
switch (strtolower($page)) {
	
	case 'form-password-change':
		$file_included = 'form/form-password-change.php';
	break;
	case 'form-password':
		$file_included = 'form/form-password.php';
	break;
	case 'form-activation':
		$file_included = 'form/form-activation.php';
	break;
	case 'form-register':
	case 'register':
		$file_included = 'form/form-register.php';
	break;
	case 'form-login':
	case 'login':
	default:
		$file_included = 'form/form-login.php';
	break;
}
include($file_included);
include_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'form-includes' . DIRECTORY_SEPARATOR . 'footer.php');
