<?php
if ( ! defined('BASEPATH')) { exit('No direct script access allowed'); }
defined('PHP_MYSQL_CRUD_NATIVE') OR define('PHP_MYSQL_CRUD_NATIVE', TRUE);
include_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'dashboard-includes' . DIRECTORY_SEPARATOR . 'header.php');
include_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'dashboard-includes' . DIRECTORY_SEPARATOR . 'sidebar.php');



$page = (isset($page) ? $page : 'welcome');
$page = (is_string($page) ? strtolower($page) : 'welcome');
switch (strtolower($page)) {
	
	
	
	
	
	# Users
	case 'user-list':
		$file_included = 'users/user-lists.php';
	break;
	case 'user-add':
		$file_included = 'users/user-add.php';
	break;
	case 'user-edit':
		$file_included = 'users/user-edit.php';
	break;
	case 'user-view':
		$file_included = 'users/user-view.php';
	break;
	
	# Profile
	case 'profile-edit':
		$file_included = 'profile/profile-edit.php';
	break;
	case 'profile-view':
		$file_included = 'profile/profile-view.php';
	break;
	
	
	
	
	# Menu
	case 'menu-lists':
		$file_included = 'menu/menu-lists.php';
	break;
	case 'menu-add-item':
		$file_included = 'menu/menu-add-item.php';
	break;
	case 'menu-edit-item':
		$file_included = 'menu/menu-edit-item.php';
	break;
	case 'menu-view-item':
		$file_included = 'menu/menu-view-item.php';
	break;
	# Banner
	case 'banner-lists':
		$file_included = 'banner/banner-lists.php';
	break;
	case 'banner-lists-ajax':
		$file_included = 'banner/banner-lists-ajax.php';
	break;
	case 'banner-add-slider':
		$file_included = 'banner/banner-add-slider.php';
	break;
	
	
	
	
	# Addressbook
	case 'addressbook-lists':
		$file_included = 'addressbook/addressbook-lists.php';
	break;
	case 'addressbook-listitem':
		$file_included = 'addressbook/addressbook-list-item.php';
	break;
	case 'addressbook-listgroup':
		$file_included = 'addressbook/addressbook-list-group.php';
	break;
	case 'addressbook-addgroup':
		$file_included = 'addressbook/addressbook-add-group.php';
	break;
	case 'addressbook-additem':
		$file_included = 'addressbook/addressbook-add-item.php';
	break;
	case 'addressbook-group':
		$file_included = 'addressbook/addressbook-group.php';
	break;
	case 'addressbook-item':
		$file_included = 'addressbook/addressbook-item.php';
	break;

	# About Dashboard Home
	case 'dashboard-about':
		$file_included = 'dashboard/dashboard-about.php';
	break;
	case 'dashboard-home':
	default:
		$file_included = 'dashboard/dashboard-home.php';
	break;
}
include($file_included);
include_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'dashboard-includes' . DIRECTORY_SEPARATOR . 'footer.php');



